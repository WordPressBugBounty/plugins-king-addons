<?php
/**
 * Auto Tagging module for AI SEO Tools.
 *
 * @package King_Addons
 */

namespace King_Addons\AI_SEO_Tools;

if (!defined('ABSPATH')) {
    exit;
}

class Auto_Tagging_Module
{
    private const BULK_OPTION_PENDING = 'king_addons_ai_seo_bulk_tags_pending_ids';
    private const BULK_OPTION_PROGRESS = 'king_addons_ai_seo_bulk_tags_progress';
    private const CRON_HOOK = 'king_addons_ai_seo_bulk_tags_cron';

    private const BULK_APPEND_PENDING = 'king_addons_ai_seo_bulk_append_tags_pending_ids';
    private const BULK_APPEND_PROGRESS = 'king_addons_ai_seo_bulk_append_tags_progress';
    private const CRON_APPEND_HOOK = 'king_addons_ai_seo_bulk_append_tags_cron';

    private const BULK_REGEN_PENDING = 'king_addons_ai_seo_bulk_regenerate_tags_pending_ids';
    private const BULK_REGEN_PROGRESS = 'king_addons_ai_seo_bulk_regenerate_tags_progress';
    private const CRON_REGEN_HOOK = 'king_addons_ai_seo_bulk_regenerate_tags_cron';

    private const BATCH_SIZE = 1;

    public function __construct()
    {
        add_filter('manage_posts_columns', [$this, 'add_tagging_column']);
        add_action('manage_posts_custom_column', [$this, 'display_tagging_column'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_post_list_scripts']);

        add_action('wp_ajax_king_addons_ai_seo_generate_single_tags', [$this, 'handle_ajax_generate_single_tags']);
        add_action('wp_ajax_king_addons_ai_seo_append_single_tags', [$this, 'handle_ajax_append_single_tags']);
        add_action('wp_ajax_king_addons_ai_seo_regenerate_single_tags', [$this, 'handle_ajax_regenerate_single_tags']);
        add_action('wp_ajax_king_addons_ai_seo_clear_single_tags', [$this, 'handle_ajax_clear_single_tags']);

        add_action('wp_ajax_king_addons_ai_seo_start_bulk_tags', [$this, 'handle_ajax_start_bulk_tags']);
        add_action('wp_ajax_king_addons_ai_seo_get_bulk_tags_status', [$this, 'handle_ajax_get_bulk_tags_status']);
        add_action('wp_ajax_king_addons_ai_seo_stop_bulk_tags', [$this, 'handle_ajax_stop_bulk_tags']);
        add_action(self::CRON_HOOK, [$this, 'process_bulk_tags_batch']);

        add_action('wp_ajax_king_addons_ai_seo_start_bulk_append_tags', [$this, 'handle_ajax_start_bulk_append_tags']);
        add_action('wp_ajax_king_addons_ai_seo_get_bulk_append_tags_status', [$this, 'handle_ajax_get_bulk_append_tags_status']);
        add_action('wp_ajax_king_addons_ai_seo_stop_bulk_append_tags', [$this, 'handle_ajax_stop_bulk_append_tags']);
        add_action(self::CRON_APPEND_HOOK, [$this, 'process_bulk_append_tags_batch']);

        add_action('wp_ajax_king_addons_ai_seo_start_bulk_regenerate_tags', [$this, 'handle_ajax_start_bulk_regenerate_tags']);
        add_action('wp_ajax_king_addons_ai_seo_get_bulk_regenerate_tags_status', [$this, 'handle_ajax_get_bulk_regenerate_tags_status']);
        add_action('wp_ajax_king_addons_ai_seo_stop_bulk_regenerate_tags', [$this, 'handle_ajax_stop_bulk_regenerate_tags']);
        add_action(self::CRON_REGEN_HOOK, [$this, 'process_bulk_regenerate_tags_batch']);
    }

    public function enqueue_post_list_scripts(string $hook): void
    {
        if ($hook !== 'edit.php') {
            return;
        }

        global $post_type;
        if ($post_type !== 'post') {
            return;
        }

        wp_enqueue_script(
            'king-addons-ai-seo-post-list',
            KING_ADDONS_URL . 'includes/extensions/AI_SEO_Tools/assets/ai-seo-post-list.js',
            ['jquery'],
            KING_ADDONS_VERSION,
            true
        );

        wp_localize_script('king-addons-ai-seo-post-list', 'kingAddonsAiSeoPostList', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'generateNonce' => wp_create_nonce('king_addons_ai_seo_generate_single_tags_nonce'),
            'appendNonce' => wp_create_nonce('king_addons_ai_seo_append_single_tags_nonce'),
            'regenerateNonce' => wp_create_nonce('king_addons_ai_seo_regenerate_single_tags_nonce'),
            'clearNonce' => wp_create_nonce('king_addons_ai_seo_clear_single_tags_nonce'),
            'generatingText' => esc_html__('Generating...', 'king-addons'),
            'appendingText' => esc_html__('Appending...', 'king-addons'),
            'regeneratingText' => esc_html__('Regenerating...', 'king-addons'),
            'clearingText' => esc_html__('Clearing...', 'king-addons'),
            'errorText' => esc_html__('Error', 'king-addons'),
        ]);
    }

    public function add_tagging_column(array $columns): array
    {
        $new_columns = [];
        foreach ($columns as $key => $title) {
            if ($key === 'date') {
                $new_columns['king_addons_ai_seo_tags'] = esc_html__('AI Tags', 'king-addons');
            }
            $new_columns[$key] = $title;
        }

        if (!isset($new_columns['king_addons_ai_seo_tags'])) {
            $new_columns['king_addons_ai_seo_tags'] = esc_html__('AI Tags', 'king-addons');
        }

        return $new_columns;
    }

    public function display_tagging_column(string $column_name, int $post_id): void
    {
        if ($column_name !== 'king_addons_ai_seo_tags') {
            return;
        }

        $tags = wp_get_post_tags($post_id, ['fields' => 'names']);

        echo '<div class="king-addons-ai-seo-tag-status" data-post-id="' . esc_attr((string) $post_id) . '">';
        if (!empty($tags)) {
            echo '<span>' . esc_html(implode(', ', $tags)) . '</span> ';
            echo '<button type="button" class="button button-secondary button-small king-addons-ai-seo-append-tags" data-post-id="' . esc_attr((string) $post_id) . '">' . esc_html__('Append', 'king-addons') . '</button> ';
            echo '<button type="button" class="button button-secondary button-small king-addons-ai-seo-regenerate-tags" data-post-id="' . esc_attr((string) $post_id) . '">' . esc_html__('Regenerate', 'king-addons') . '</button> ';
            echo '<button type="button" class="button button-secondary button-small king-addons-ai-seo-clear-tags" data-post-id="' . esc_attr((string) $post_id) . '">' . esc_html__('Clear', 'king-addons') . '</button>';
        } else {
            echo '<button type="button" class="button button-secondary button-small king-addons-ai-seo-generate-tags" data-post-id="' . esc_attr((string) $post_id) . '">' . esc_html__('Generate Tags', 'king-addons') . '</button>';
        }
        echo '<span class="king-addons-ai-seo-tags-result" style="margin-left:8px;"></span>';
        echo '<span class="spinner" style="float:none;"></span>';
        echo '</div>';
    }

    public function handle_ajax_generate_single_tags(): void
    {
        check_ajax_referer('king_addons_ai_seo_generate_single_tags_nonce', 'nonce');
        $this->handle_single_tags_request('generate');
    }

    public function handle_ajax_append_single_tags(): void
    {
        check_ajax_referer('king_addons_ai_seo_append_single_tags_nonce', 'nonce');
        $this->handle_single_tags_request('append');
    }

    public function handle_ajax_regenerate_single_tags(): void
    {
        check_ajax_referer('king_addons_ai_seo_regenerate_single_tags_nonce', 'nonce');
        $this->handle_single_tags_request('regenerate');
    }

    public function handle_ajax_clear_single_tags(): void
    {
        check_ajax_referer('king_addons_ai_seo_clear_single_tags_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        if (!$post_id || !get_post($post_id)) {
            wp_send_json_error(['message' => esc_html__('Invalid post ID.', 'king-addons')], 400);
        }

        wp_set_post_tags($post_id, [], false);
        wp_send_json_success(['message' => esc_html__('Tags removed.', 'king-addons')]);
    }

    private function handle_single_tags_request(string $mode): void
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        if (!$post_id || !get_post($post_id)) {
            wp_send_json_error(['message' => esc_html__('Invalid post ID.', 'king-addons')], 400);
        }

        $replace = $mode === 'regenerate';
        $append = $mode === 'append';

        $result = $this->generate_tags_for_post($post_id, true, $replace, $append);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 500);
        }

        wp_send_json_success(['tags' => $result]);
    }

    public function generate_tags_for_post(int $post_id, bool $is_ajax = false, bool $replace_existing = false, bool $append_only = false)
    {
        $post = get_post($post_id);
        if (!$post) {
            $message = esc_html__('Invalid post.', 'king-addons');
            return $is_ajax ? new \WP_Error('invalid_post', $message) : $message;
        }

        $options = get_option('king_addons_ai_options', []);
        $api_key = $options['openai_api_key'] ?? '';
        $max_tags = isset($options['auto_tagging_max_tags']) ? (int) $options['auto_tagging_max_tags'] : 5;
        $max_tags = max(1, min(20, $max_tags));
        $stop_list = trim((string) ($options['auto_tagging_stop_words'] ?? ''));

        if ($api_key === '') {
            $message = esc_html__('OpenAI API key is missing.', 'king-addons');
            return $is_ajax ? new \WP_Error('missing_api_key', $message) : $message;
        }

        $title = $post->post_title;
        $content = wp_strip_all_tags($post->post_content);

        $lang_enabled = !empty($options['content_language_custom_enable']);
        $lang = trim($options['content_language_custom'] ?? '');

        $prompt = sprintf(
            'Generate up to %d relevant WordPress tags. Return comma-separated tags only. ',
            $max_tags
        );
        if ($stop_list !== '') {
            $prompt .= 'Exclude these words: ' . $stop_list . '. ';
        }
        if ($lang_enabled && $lang !== '') {
            $prompt .= 'Use language: ' . $lang . '. ';
        }
        $prompt .= 'Title: "' . $title . '". Content: "' . $content . '".';

        $payload = [
            'model' => $options['openai_model'] ?? 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens' => 80,
        ];

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($payload),
            'timeout' => 60,
            'data_format' => 'body',
        ]);

        if (is_wp_error($response)) {
            return $is_ajax ? $response : $response->get_error_message();
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($code !== 200 || empty($body['choices'][0]['message']['content'])) {
            $api_error = $body['error']['message'] ?? esc_html__('API request failed.', 'king-addons');
            return $is_ajax ? new \WP_Error('api_error', $api_error) : $api_error;
        }

        $raw = sanitize_text_field(trim((string) $body['choices'][0]['message']['content']));
        $raw = trim($raw, '., ');
        $tags = array_filter(array_map('trim', explode(',', $raw)));
        if (empty($tags)) {
            $message = esc_html__('No tags generated.', 'king-addons');
            return $is_ajax ? new \WP_Error('empty_tags', $message) : $message;
        }

        if ($replace_existing) {
            wp_set_post_tags($post_id, array_slice($tags, 0, $max_tags), false);
        } elseif ($append_only) {
            wp_set_post_tags($post_id, array_slice($tags, 0, $max_tags), true);
        } else {
            $existing = wp_get_post_tags($post_id, ['fields' => 'names']);
            if (!empty($existing)) {
                wp_set_post_tags($post_id, array_slice($tags, 0, $max_tags), true);
            } else {
                wp_set_post_tags($post_id, array_slice($tags, 0, $max_tags), false);
            }
        }

        $current_tags = wp_get_post_tags($post_id, ['fields' => 'names']);
        return $is_ajax ? implode(', ', $current_tags) : true;
    }

    public static function get_tagging_stats(): array
    {
        $all = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        $total = count($all);
        $with_tags = 0;
        foreach ($all as $post_id) {
            if (!empty(wp_get_post_tags((int) $post_id))) {
                $with_tags++;
            }
        }

        $without_tags = max(0, $total - $with_tags);
        $percent_with = $total > 0 ? ($with_tags / $total) * 100 : 0;

        return [
            'total' => $total,
            'with_tags' => $with_tags,
            'without_tags' => $without_tags,
            'percent_with_tags' => $percent_with,
            'percent_without_tags' => 100 - $percent_with,
        ];
    }

    public function handle_ajax_start_bulk_tags(): void
    {
        check_ajax_referer('king_addons_ai_seo_bulk_tags_start_nonce', 'nonce');
        $this->start_bulk_mode(self::BULK_OPTION_PENDING, self::BULK_OPTION_PROGRESS, self::CRON_HOOK, $this->get_posts_without_tags());
    }

    public function handle_ajax_get_bulk_tags_status(): void
    {
        check_ajax_referer('king_addons_ai_seo_bulk_tags_status_nonce', 'nonce');
        $this->send_bulk_status(self::BULK_OPTION_PROGRESS, self::BULK_OPTION_PENDING, self::CRON_HOOK, false, false);
    }

    public function handle_ajax_stop_bulk_tags(): void
    {
        check_ajax_referer('king_addons_ai_seo_bulk_tags_stop_nonce', 'nonce');
        $this->stop_bulk_mode(self::BULK_OPTION_PENDING, self::BULK_OPTION_PROGRESS, self::CRON_HOOK);
    }

    public function process_bulk_tags_batch(): void
    {
        $this->process_bulk_mode(self::BULK_OPTION_PENDING, self::BULK_OPTION_PROGRESS, self::CRON_HOOK, false, false);
    }

    public function handle_ajax_start_bulk_append_tags(): void
    {
        check_ajax_referer('king_addons_ai_seo_bulk_append_tags_start_nonce', 'nonce');
        $this->start_bulk_mode(self::BULK_APPEND_PENDING, self::BULK_APPEND_PROGRESS, self::CRON_APPEND_HOOK, $this->get_posts_with_tags());
    }

    public function handle_ajax_get_bulk_append_tags_status(): void
    {
        check_ajax_referer('king_addons_ai_seo_bulk_append_tags_status_nonce', 'nonce');
        $this->send_bulk_status(self::BULK_APPEND_PROGRESS, self::BULK_APPEND_PENDING, self::CRON_APPEND_HOOK, false, true);
    }

    public function handle_ajax_stop_bulk_append_tags(): void
    {
        check_ajax_referer('king_addons_ai_seo_bulk_append_tags_stop_nonce', 'nonce');
        $this->stop_bulk_mode(self::BULK_APPEND_PENDING, self::BULK_APPEND_PROGRESS, self::CRON_APPEND_HOOK);
    }

    public function process_bulk_append_tags_batch(): void
    {
        $this->process_bulk_mode(self::BULK_APPEND_PENDING, self::BULK_APPEND_PROGRESS, self::CRON_APPEND_HOOK, false, true);
    }

    public function handle_ajax_start_bulk_regenerate_tags(): void
    {
        check_ajax_referer('king_addons_ai_seo_bulk_regenerate_tags_start_nonce', 'nonce');
        $this->start_bulk_mode(self::BULK_REGEN_PENDING, self::BULK_REGEN_PROGRESS, self::CRON_REGEN_HOOK, $this->get_posts_with_tags());
    }

    public function handle_ajax_get_bulk_regenerate_tags_status(): void
    {
        check_ajax_referer('king_addons_ai_seo_bulk_regenerate_tags_status_nonce', 'nonce');
        $this->send_bulk_status(self::BULK_REGEN_PROGRESS, self::BULK_REGEN_PENDING, self::CRON_REGEN_HOOK, true, false);
    }

    public function handle_ajax_stop_bulk_regenerate_tags(): void
    {
        check_ajax_referer('king_addons_ai_seo_bulk_regenerate_tags_stop_nonce', 'nonce');
        $this->stop_bulk_mode(self::BULK_REGEN_PENDING, self::BULK_REGEN_PROGRESS, self::CRON_REGEN_HOOK);
    }

    public function process_bulk_regenerate_tags_batch(): void
    {
        $this->process_bulk_mode(self::BULK_REGEN_PENDING, self::BULK_REGEN_PROGRESS, self::CRON_REGEN_HOOK, true, false);
    }

    private function start_bulk_mode(string $pending_option, string $progress_option, string $cron_hook, array $ids): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }

        $ka_ai_opts = get_option('king_addons_ai_options', []);
        if (empty($ka_ai_opts['openai_api_key'])) {
            wp_send_json_error(['message' => esc_html__('OpenAI API key is not set. Please add it in AI Settings.', 'king-addons'), 'code' => 'no_api_key'], 400);
        }

        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 0;
        wp_clear_scheduled_hook($cron_hook);

        $total = count($ids);
        if ($limit > 0 && $limit < $total) {
            $ids = array_slice($ids, 0, $limit);
            $total = $limit;
        }

        update_option($pending_option, $ids, false);
        update_option($progress_option, [
            'status' => empty($ids) ? 'complete' : 'running',
            'total' => $total,
            'processed' => 0,
            'last_run' => 0,
            'errors' => [],
            'current_id' => null,
            'current_item' => null,
            'recent_success_ids' => [],
            'last_success' => null,
        ], false);

        if (!empty($ids)) {
            wp_schedule_single_event(time(), $cron_hook);
        }

        wp_send_json_success(['status' => empty($ids) ? 'complete' : 'running', 'total' => $total, 'processed' => 0]);
    }

    private function send_bulk_status(string $progress_option, string $pending_option, string $cron_hook, bool $replace_existing, bool $append_only): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }

        $progress = get_option($progress_option, ['status' => 'idle', 'total' => 0, 'processed' => 0, 'last_run' => 0, 'errors' => [], 'current_id' => null, 'current_item' => null, 'recent_success_ids' => [], 'last_success' => null]);
        $progress = $this->maybe_kick_bulk_processing($pending_option, $progress_option, $cron_hook, $progress, $replace_existing, $append_only);
        wp_send_json_success($progress);
    }

    private function stop_bulk_mode(string $pending_option, string $progress_option, string $cron_hook): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }

        wp_clear_scheduled_hook($cron_hook);
        delete_option($pending_option);
        delete_transient($this->get_lock_key($progress_option));

        $progress = get_option($progress_option, []);
        $progress['status'] = 'stopped';
        $progress['current_id'] = null;
        $progress['current_item'] = null;
        update_option($progress_option, $progress, false);

        wp_send_json_success($progress);
    }

    private function process_bulk_mode(string $pending_option, string $progress_option, string $cron_hook, bool $replace_existing, bool $append_only): void
    {
        $lock_key = $this->get_lock_key($progress_option);
        if ((bool) get_transient($lock_key)) {
            return;
        }

        set_transient($lock_key, 1, 60);

        $pending_ids = get_option($pending_option, []);
        $progress = get_option($progress_option, []);

        if (empty($pending_ids) || ($progress['status'] ?? '') !== 'running') {
            wp_clear_scheduled_hook($cron_hook);
            delete_transient($lock_key);
            return;
        }

        $batch = array_slice($pending_ids, 0, self::BATCH_SIZE);
        $processed = 0;
        $errors = [];
        $success_ids = [];
        $success_items = [];

        foreach ($batch as $post_id_raw) {
            $post_id = (int) $post_id_raw;
            $progress['current_id'] = $post_id;
            $progress['current_item'] = $this->get_post_debug_data($post_id);
            update_option($progress_option, $progress, false);

            $result = $this->generate_tags_for_post($post_id, true, $replace_existing, $append_only);
            if (is_wp_error($result)) {
                $errors[$post_id] = $result->get_error_message();
            } else {
                $success_ids[] = $post_id;
                $item = $this->get_post_debug_data($post_id);
                $item['result_text'] = (string) $result;
                $success_items[] = $item;
            }
            $processed++;
        }

        $remaining = array_slice($pending_ids, $processed);
        update_option($pending_option, $remaining, false);

        $progress['processed'] = (int) ($progress['processed'] ?? 0) + $processed;
        $progress['last_run'] = time();
        $progress['errors'] = array_slice(array_merge($progress['errors'] ?? [], $errors), -20);
        $progress['recent_success_ids'] = array_slice(array_merge($success_ids, $progress['recent_success_ids'] ?? []), 0, 5);
        $progress['current_id'] = null;
        $progress['current_item'] = null;
        if (!empty($success_items)) {
            $progress['last_success'] = $success_items[0];
        }

        if (empty($remaining)) {
            $progress['status'] = 'complete';
            delete_option($pending_option);
            wp_clear_scheduled_hook($cron_hook);
        } else {
            wp_schedule_single_event(time() + 2, $cron_hook);
        }

        update_option($progress_option, $progress, false);
        delete_transient($lock_key);
    }

    private function maybe_kick_bulk_processing(string $pending_option, string $progress_option, string $cron_hook, array $progress, bool $replace_existing, bool $append_only): array
    {
        if (($progress['status'] ?? 'idle') !== 'running') {
            return $progress;
        }

        $next_scheduled = wp_next_scheduled($cron_hook);
        $last_run = (int) ($progress['last_run'] ?? 0);
        $is_stale = $last_run > 0 && (time() - $last_run) > 12;

        if (($next_scheduled === false && !(bool) get_transient($this->get_lock_key($progress_option))) || $is_stale) {
            $this->process_bulk_mode($pending_option, $progress_option, $cron_hook, $replace_existing, $append_only);
            return get_option($progress_option, $progress);
        }

        return $progress;
    }

    private function get_lock_key(string $progress_option): string
    {
        return 'ka_ai_seo_lock_' . md5($progress_option);
    }

    private function get_post_debug_data(int $post_id): array
    {
        return [
            'id' => $post_id,
            'title' => (string) get_the_title($post_id),
            'thumb_url' => (string) get_the_post_thumbnail_url($post_id, 'thumbnail'),
        ];
    }

    /**
     * @return int[]
     */
    private function get_posts_without_tags(): array
    {
        $all = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        $result = [];
        foreach ($all as $post_id) {
            if (empty(wp_get_post_tags((int) $post_id))) {
                $result[] = (int) $post_id;
            }
        }

        return $result;
    }

    /**
     * @return int[]
     */
    private function get_posts_with_tags(): array
    {
        $all = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        $result = [];
        foreach ($all as $post_id) {
            if (!empty(wp_get_post_tags((int) $post_id))) {
                $result[] = (int) $post_id;
            }
        }

        return $result;
    }
}
