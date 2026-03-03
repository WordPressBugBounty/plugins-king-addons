<?php
/**
 * Post Generator module for AI SEO Tools.
 *
 * Generates full blog posts (title, HTML content, excerpt, tags)
 * via OpenAI and optionally generates a matching featured image.
 *
 * @package King_Addons
 */

namespace King_Addons\AI_SEO_Tools;

if (!defined('ABSPATH')) {
    exit;
}

class Post_Generator_Module
{
    private const BULK_OPTION_PENDING  = 'king_addons_ai_seo_post_gen_pending';
    private const BULK_OPTION_PROGRESS = 'king_addons_ai_seo_post_gen_progress';
    private const BULK_OPTION_LOCK     = 'king_addons_ai_seo_post_gen_lock';
    private const CRON_HOOK            = 'king_addons_ai_seo_post_gen_cron';
    private const BATCH_SIZE           = 1;

    public function __construct()
    {
        add_action('wp_ajax_king_addons_ai_seo_start_post_gen',      [$this, 'handle_ajax_start']);
        add_action('wp_ajax_king_addons_ai_seo_get_post_gen_status', [$this, 'handle_ajax_status']);
        add_action('wp_ajax_king_addons_ai_seo_stop_post_gen',       [$this, 'handle_ajax_stop']);
        add_action(self::CRON_HOOK, [$this, 'process_batch']);
    }

    public function handle_ajax_start(): void
    {
        check_ajax_referer('king_addons_ai_seo_post_gen_start_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }

        $ka_ai_opts = get_option('king_addons_ai_options', []);
        if (empty($ka_ai_opts['openai_api_key'])) {
            wp_send_json_error(['message' => esc_html__('OpenAI API key is not set. Please add it in AI Settings.', 'king-addons'), 'code' => 'no_api_key'], 400);
        }

        $description  = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
        $count        = max(1, min(50, absint($_POST['count'] ?? 1)));
        $post_status  = in_array($_POST['post_status'] ?? 'draft', ['draft', 'publish'], true)
            ? sanitize_key(wp_unslash($_POST['post_status']))
            : 'draft';
        $length       = in_array($_POST['length'] ?? 'medium', ['short', 'medium', 'long'], true)
            ? sanitize_key(wp_unslash($_POST['length']))
            : 'medium';
        $category_raw = sanitize_text_field(wp_unslash($_POST['category_id'] ?? 'auto'));
        $category_id  = ($category_raw === 'auto' || $category_raw === '0') ? $category_raw : (string) absint($category_raw);
        $gen_image    = !empty($_POST['generate_image']) && king_addons_freemius()->can_use_premium_code();
        $image_model  = in_array($_POST['image_model'] ?? 'dall-e-3', ['dall-e-3', 'gpt-image-1'], true)
            ? sanitize_key(wp_unslash($_POST['image_model']))
            : 'dall-e-3';
        $image_quality = sanitize_key(wp_unslash($_POST['image_quality'] ?? 'standard'));
        $image_size    = sanitize_key(wp_unslash($_POST['image_size'] ?? '1024x1024'));

        if ($description === '') {
            wp_send_json_error(['message' => esc_html__('Please provide a description.', 'king-addons')], 400);
        }

        wp_clear_scheduled_hook(self::CRON_HOOK);
        delete_transient(self::BULK_OPTION_LOCK);

        $pending = range(1, $count);

        update_option(self::BULK_OPTION_PENDING, $pending, false);
        update_option(self::BULK_OPTION_PROGRESS, [
            'status'       => 'running',
            'total'        => $count,
            'processed'    => 0,
            'last_run'     => 0,
            'started_at'   => time(),
            'errors'       => [],
            'current_item' => null,
            'last_success' => null,
            'settings'     => [
                'description'    => $description,
                'post_status'    => $post_status,
                'length'         => $length,
                'category_id'    => $category_id,
                'generate_image' => $gen_image,
                'image_model'    => $image_model,
                'image_quality'  => $image_quality,
                'image_size'     => $image_size,
            ],
        ], false);

        wp_schedule_single_event(time(), self::CRON_HOOK);

        wp_send_json_success([
            'status'    => 'running',
            'total'     => $count,
            'processed' => 0,
        ]);
    }

    public function handle_ajax_status(): void
    {
        check_ajax_referer('king_addons_ai_seo_post_gen_status_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }

        $progress = get_option(self::BULK_OPTION_PROGRESS, ['status' => 'idle']);
        $progress = $this->maybe_kick($progress);

        wp_send_json_success($progress);
    }

    public function handle_ajax_stop(): void
    {
        check_ajax_referer('king_addons_ai_seo_post_gen_stop_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }

        wp_clear_scheduled_hook(self::CRON_HOOK);
        delete_option(self::BULK_OPTION_PENDING);
        delete_transient(self::BULK_OPTION_LOCK);

        $progress = get_option(self::BULK_OPTION_PROGRESS, []);
        $progress['status']       = 'stopped';
        $progress['current_item'] = null;
        update_option(self::BULK_OPTION_PROGRESS, $progress, false);

        wp_send_json_success($progress);
    }

    public function process_batch(): void
    {
        if ((bool) get_transient(self::BULK_OPTION_LOCK)) {
            return;
        }

        // Extend execution time — image generation can take 60-120s.
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        @set_time_limit(300);
        set_transient(self::BULK_OPTION_LOCK, 1, 180);

        $pending  = get_option(self::BULK_OPTION_PENDING, []);
        $progress = get_option(self::BULK_OPTION_PROGRESS, []);

        if (empty($pending) || !is_array($pending) || ($progress['status'] ?? '') !== 'running') {
            wp_clear_scheduled_hook(self::CRON_HOOK);
            delete_transient(self::BULK_OPTION_LOCK);
            return;
        }

        $settings = (array) ($progress['settings'] ?? []);
        $batch    = array_slice($pending, 0, self::BATCH_SIZE);
        $errors   = [];

        foreach ($batch as $idx) {
            $idx = (int) $idx;

            // Signal current item so the status poll can show it.
            $progress['current_item'] = [
                'id'    => $idx,
                'title' => sprintf(esc_html__('Generating post %d of %d…', 'king-addons'), $idx, $progress['total'] ?? '?'),
                'prompt' => $this->build_prompt_preview(
                    (string) ($settings['description'] ?? ''),
                    $idx,
                    (string) ($settings['length'] ?? 'medium'),
                    (string) ($settings['category_id'] ?? 'auto')
                ),
            ];
            update_option(self::BULK_OPTION_PROGRESS, $progress, false);

            // 1. Generate content via OpenAI.
            $post_data = $this->generate_post_content(
                (string) ($settings['description'] ?? ''),
                $idx,
                (string) ($settings['length'] ?? 'medium'),
                (string) ($settings['category_id'] ?? 'auto')
            );
            if (is_wp_error($post_data)) {
                $errors[$idx] = $post_data->get_error_message();
                continue;
            }

            // 2. Insert post into WordPress.
            $post_id = wp_insert_post([
                'post_title'   => $post_data['title'],
                'post_content' => $post_data['content'],
                'post_excerpt' => $post_data['excerpt'],
                'post_status'  => $settings['post_status'] ?? 'draft',
                'post_type'    => 'post',
            ]);

            if (is_wp_error($post_id) || !$post_id) {
                $errors[$idx] = is_wp_error($post_id)
                    ? $post_id->get_error_message()
                    : esc_html__('Failed to insert post.', 'king-addons');
                continue;
            }

            // 3. Assign tags.
            if (!empty($post_data['tags']) && is_array($post_data['tags'])) {
                wp_set_post_tags($post_id, $post_data['tags'], false);
            }

            // 4. Assign category.
            $cat_id_setting = $settings['category_id'] ?? 'auto';
            if ($cat_id_setting === 'auto' && !empty($post_data['category'])) {
                // Find or create the AI-suggested category using core functions (safe in cron context).
                $cat_name = sanitize_text_field((string) $post_data['category']);
                $term     = term_exists($cat_name, 'category');
                if ($term) {
                    $resolved = (int) (is_array($term) ? $term['term_id'] : $term);
                } else {
                    $inserted = wp_insert_term($cat_name, 'category');
                    $resolved = (!is_wp_error($inserted) && isset($inserted['term_id'])) ? (int) $inserted['term_id'] : 0;
                }
                if ($resolved > 0) {
                    wp_set_post_categories($post_id, [$resolved], false);
                }
            } elseif (is_numeric($cat_id_setting) && (int) $cat_id_setting > 0) {
                wp_set_post_categories($post_id, [(int) $cat_id_setting], false);
            }

            // 4. Optionally generate & set featured image.
            $thumb_url = '';
            if (!empty($settings['generate_image'])) {
                $attach_id = $this->generate_and_attach_image(
                    $post_data['title'],
                    (string) ($settings['description'] ?? ''),
                    (string) ($settings['image_model']   ?? 'dall-e-3'),
                    (string) ($settings['image_quality'] ?? 'standard'),
                    (string) ($settings['image_size']    ?? '1024x1024'),
                    $post_id
                );
                if (!is_wp_error($attach_id)) {
                    set_post_thumbnail($post_id, $attach_id);
                    $thumb_url = (string) wp_get_attachment_image_url($attach_id, 'thumbnail');
                }
            }

            $progress['last_success'] = [
                'id'          => $post_id,
                'title'       => $post_data['title'],
                'result_text' => implode(', ', $post_data['tags'] ?? []),
                'thumb_url'   => $thumb_url,
                'edit_url'    => (string) get_edit_post_link($post_id),
            ];
        }

        $remaining = array_slice($pending, count($batch));
        update_option(self::BULK_OPTION_PENDING, $remaining, false);

        $progress['processed']   = (int) ($progress['processed'] ?? 0) + count($batch);
        $progress['last_run']    = time();
        $progress['errors']      = array_slice(array_merge($progress['errors'] ?? [], $errors), -20);
        $progress['current_item'] = null;

        if (empty($remaining)) {
            $progress['status'] = 'complete';
            delete_option(self::BULK_OPTION_PENDING);
            wp_clear_scheduled_hook(self::CRON_HOOK);
        } else {
            $delay = $this->get_bulk_delay();
            wp_schedule_single_event(time() + $delay, self::CRON_HOOK);
        }

        update_option(self::BULK_OPTION_PROGRESS, $progress, false);
        delete_transient(self::BULK_OPTION_LOCK);
    }

    /**
     * Call OpenAI Chat Completions to generate post fields as JSON.
     *
     * @param string $description User-supplied topic/description.
     * @param int    $post_num    Index within the batch (for uniqueness).
     * @return array|\WP_Error   Associative array with keys: title, content, excerpt, tags.
     */
    /**
     * Build the OpenAI prompt string (same logic as generate_post_content) without making an API call.
     * Used to expose the current prompt in the status response.
     */
    private function build_prompt_preview(string $description, int $post_num, string $length = 'medium', string $category_mode = 'auto'): string
    {
        $word_targets = ['short' => 300, 'medium' => 600, 'long' => 1200];
        $word_count   = $word_targets[$length] ?? 600;

        $category_instruction = ($category_mode === 'auto' || $category_mode === '0')
            ? ' "category" (string — one concise, relevant category name for this post, 1–3 words),'
            : '';

        $opts_prev   = get_option('king_addons_ai_options', []);
        $lang_enabled_prev = !empty($opts_prev['content_language_custom_enable']);
        $lang_prev   = trim($opts_prev['content_language_custom'] ?? '');
        $lang_instr  = ($lang_enabled_prev && $lang_prev !== '') ? ' Write everything in ' . $lang_prev . '.' : '';

        return 'Generate a high-quality, original WordPress blog post about the following topic: "' . $description . '".' .
            ' This is post number ' . $post_num . ' in a series — make it unique and distinctly different from other posts on the same topic.' .
            ' The post content should be approximately ' . $word_count . ' words long.' .
            $lang_instr .
            ' Return ONLY a valid JSON object with these exact keys:' .
            ' "title" (string — a compelling, SEO-friendly headline),' .
            ' "content" (string — HTML using <p>, <h2>, <h3>, <ul>, <li> tags only, no inline styles, approximately ' . $word_count . ' words),' .
            ' "excerpt" (string — 1–2 sentence summary),' .
            ' "tags" (array of 5–8 relevant string tags),' .
            $category_instruction .
            ' Important: do NOT wrap the JSON in markdown code blocks. Return raw JSON only.';
    }

    private function generate_post_content(string $description, int $post_num, string $length = 'medium', string $category_mode = 'auto')
    {
        $options = get_option('king_addons_ai_options', []);
        $api_key = $options['openai_api_key'] ?? '';
        $model   = $options['openai_model'] ?? 'gpt-4o-mini';

        if ($api_key === '') {
            return new \WP_Error('missing_api_key', esc_html__('OpenAI API key is missing.', 'king-addons'));
        }

        $word_targets = ['short' => 300, 'medium' => 600, 'long' => 1200];
        $word_count   = $word_targets[$length] ?? 600;

        $category_instruction = $category_mode === 'auto'
            ? ' "category" (string — one concise, relevant category name for this post, 1–3 words),'
            : '';

        $lang_enabled = !empty($options['content_language_custom_enable']);
        $lang         = trim($options['content_language_custom'] ?? '');
        $lang_instr   = ($lang_enabled && $lang !== '') ? ' Write everything in ' . $lang . '.' : '';

        $prompt =
            'Generate a high-quality, original WordPress blog post about the following topic: "' . $description . '".' .
            ' This is post number ' . $post_num . ' in a series — make it unique and distinctly different from other posts on the same topic.' .
            ' The post content should be approximately ' . $word_count . ' words long.' .
            $lang_instr .
            ' Return ONLY a valid JSON object with these exact keys:' .
            ' "title" (string — a compelling, SEO-friendly headline),' .
            ' "content" (string — HTML using <p>, <h2>, <h3>, <ul>, <li> tags only, no inline styles, approximately ' . $word_count . ' words),' .
            ' "excerpt" (string — 1–2 sentence summary),' .
            ' "tags" (array of 5–8 relevant string tags),' .
            $category_instruction .
            ' Important: do NOT wrap the JSON in markdown code blocks. Return raw JSON only.';

        $max_tokens_map = ['short' => 700, 'medium' => 1200, 'long' => 2400];
        $max_tokens     = $max_tokens_map[$length] ?? 1200;

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'        => wp_json_encode([
                'model'      => $model,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => $max_tokens,
            ]),
            'timeout'     => 120,
            'data_format' => 'body',
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200 || empty($body['choices'][0]['message']['content'])) {
            $api_error = $body['error']['message'] ?? esc_html__('API request failed.', 'king-addons');
            return new \WP_Error('api_error', $api_error);
        }

        $raw = trim((string) $body['choices'][0]['message']['content']);

        // Strip potential markdown code fences that some models add despite instructions.
        $raw = (string) preg_replace('/^```(?:json)?\s*/i', '', $raw);
        $raw = (string) preg_replace('/\s*```$/m', '', $raw);

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['title']) || empty($data['content'])) {
            return new \WP_Error('parse_error', esc_html__('Could not parse AI response as JSON.', 'king-addons'));
        }

        return [
            'title'   => sanitize_text_field((string) $data['title']),
            'content' => wp_kses_post((string) $data['content']),
            'excerpt' => sanitize_textarea_field((string) ($data['excerpt'] ?? '')),
            'tags'    => is_array($data['tags'])
                ? array_map('sanitize_text_field', $data['tags'])
                : [],
            'category' => sanitize_text_field((string) ($data['category'] ?? '')),
        ];
    }

    /**
     * Generate an image via OpenAI Images API and attach it to a post.
     *
     * @param string $title       Post title (used as image prompt context).
     * @param string $description Overall topic description.
     * @param string $model       'dall-e-3' or 'gpt-image-1'.
     * @param string $quality     Quality setting for the selected model.
     * @param string $size        Image dimensions string.
     * @param int    $post_id     Post to attach the image to.
     * @return int|\WP_Error      Attachment ID on success, WP_Error on failure.
     */
    private function generate_and_attach_image(string $title, string $description, string $model, string $quality, string $size, int $post_id)
    {
        $options = get_option('king_addons_ai_options', []);
        $api_key = $options['openai_api_key'] ?? '';

        if ($api_key === '') {
            return new \WP_Error('missing_api_key', esc_html__('OpenAI API key is missing.', 'king-addons'));
        }

        $prompt = 'Professional blog featured image for an article titled: "' . $title . '". Topic: ' . $description . '. Photorealistic style, no text overlays, no watermarks.';

        $body = ['model' => $model, 'prompt' => $prompt, 'size' => $size];

        if ($model === 'dall-e-3') {
            $body['n']       = 1;
            $body['quality'] = ($quality === 'hd') ? 'hd' : 'standard';
        } elseif ($model === 'gpt-image-1') {
            $body['quality'] = in_array($quality, ['low', 'medium', 'high', 'auto'], true) ? $quality : 'auto';
        }

        $response = wp_remote_post('https://api.openai.com/v1/images/generations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode($body),
            'timeout' => 120,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($model === 'gpt-image-1') {
            $b64 = $data['data'][0]['b64_json'] ?? '';
            if ($b64 === '') {
                $err = $data['error']['message'] ?? esc_html__('No image data returned.', 'king-addons');
                return new \WP_Error('api_error', $err);
            }

            $bytes = base64_decode($b64);
            if (!$bytes) {
                return new \WP_Error('decode_error', esc_html__('Failed to decode image data.', 'king-addons'));
            }

            $tmp = wp_tempnam('postgen.png');
            if (!$tmp || !file_put_contents($tmp, $bytes)) {
                return new \WP_Error('write_error', esc_html__('Failed to write temp image file.', 'king-addons'));
            }

            return media_handle_sideload([
                'name'     => substr(sanitize_file_name($title), 0, 80) . '.png',
                'tmp_name' => $tmp,
            ], $post_id, $title);
        }

        // DALL·E 3 (URL-based).
        if ($code !== 200 || empty($data['data'][0]['url'])) {
            $err = $data['error']['message'] ?? esc_html__('Image generation failed.', 'king-addons');
            return new \WP_Error('api_error', $err);
        }

        return media_sideload_image(esc_url_raw($data['data'][0]['url']), $post_id, $title, 'id');
    }

    /**
     * Kick the batch processor if cron missed its schedule or went stale.
     * Works even when DISABLE_WP_CRON is set or cron misfires in local dev environments.
     *
     * @param array $progress Current progress array.
     * @return array Possibly-refreshed progress array.
     */
    private function maybe_kick(array $progress): array
    {
        if (($progress['status'] ?? 'idle') !== 'running') {
            return $progress;
        }

        $lock_held  = (bool) get_transient(self::BULK_OPTION_LOCK);
        $last       = (int) ($progress['last_run']   ?? 0);
        $started_at = (int) ($progress['started_at'] ?? 0);
        $now        = time();

        // Lock age: how long ago the current lock was set (approximated via last_run or started_at).
        $lock_age   = $last > 0 ? ($now - $last) : ($started_at > 0 ? ($now - $started_at) : 999);

        // Force-clear a lock that has clearly outlived any legitimate run (>200s = beyond the 180s TTL + buffer).
        if ($lock_held && $lock_age > 200) {
            delete_transient(self::BULK_OPTION_LOCK);
            $lock_held = false;
        }

        if ($lock_held) {
            // A batch is legitimately in progress — do nothing.
            return $progress;
        }

        // No lock is held. Run the next batch directly from this AJAX request.
        // This makes progress independent of WP Cron firing (works on Local, staging, etc.).
        $this->process_batch();
        return (array) get_option(self::BULK_OPTION_PROGRESS, $progress);
    }

    private function get_bulk_delay(): int
    {
        $options = get_option('king_addons_ai_options', []);
        $delay   = isset($options['ai_seo_bulk_processing_delay']) ? (int) $options['ai_seo_bulk_processing_delay'] : 3;
        return max(1, min(30, $delay));
    }
}
