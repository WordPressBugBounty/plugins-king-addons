<?php
/**
 * Bulk Alt Text module for AI SEO Tools.
 *
 * @package King_Addons
 */

namespace King_Addons\AI_SEO_Tools;

use King_Addons\Alt_Text_Generator;

if (!defined('ABSPATH')) {
    exit;
}

class Bulk_Alt_Text_Module
{
    private const BULK_OPTION_PENDING = 'king_addons_ai_seo_bulk_alt_pending_ids';
    private const BULK_OPTION_PROGRESS = 'king_addons_ai_seo_bulk_alt_progress';
    private const BULK_OPTION_LOCK = 'king_addons_ai_seo_bulk_alt_lock';
    private const CRON_HOOK = 'king_addons_ai_seo_bulk_alt_cron';
    private const BATCH_SIZE = 1;

    private Alt_Text_Generator $alt_text_generator;

    public function __construct(Alt_Text_Generator $alt_text_generator)
    {
        $this->alt_text_generator = $alt_text_generator;

        add_action('wp_ajax_king_addons_ai_seo_start_bulk_alt', [$this, 'handle_ajax_start_bulk_alt']);
        add_action('wp_ajax_king_addons_ai_seo_get_bulk_alt_status', [$this, 'handle_ajax_get_bulk_alt_status']);
        add_action('wp_ajax_king_addons_ai_seo_stop_bulk_alt', [$this, 'handle_ajax_stop_bulk_alt']);
        add_action('wp_ajax_king_addons_ai_seo_get_alt_stats', [$this, 'handle_ajax_get_stats']);
        add_action(self::CRON_HOOK, [$this, 'process_bulk_alt_text_batch']);
    }

    public function handle_ajax_start_bulk_alt(): void
    {
        check_ajax_referer('king_addons_ai_seo_bulk_alt_start_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }

        $ka_ai_opts = get_option('king_addons_ai_options', []);
        if (empty($ka_ai_opts['openai_api_key'])) {
            wp_send_json_error(['message' => esc_html__('OpenAI API key is not set. Please add it in AI Settings.', 'king-addons'), 'code' => 'no_api_key'], 400);
        }

        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 0;

        wp_clear_scheduled_hook(self::CRON_HOOK);

        $pending_ids = $this->get_images_without_alt();
        $total_missing = count($pending_ids);

        if ($limit > 0 && $limit < $total_missing) {
            $pending_ids = array_slice($pending_ids, 0, $limit);
            $total_to_process = $limit;
        } else {
            $total_to_process = $total_missing;
        }

        if (empty($pending_ids)) {
            update_option(self::BULK_OPTION_PROGRESS, [
                'status' => 'complete',
                'total' => 0,
                'processed' => 0,
                'errors' => [],
                'current_id' => null,
                'current_item' => null,
                'recent_success_ids' => [],
                'last_success' => null,
            ], false);
            delete_option(self::BULK_OPTION_PENDING);
            wp_send_json_success([
                'status' => 'complete',
                'message' => esc_html__('No images without alt text were found.', 'king-addons'),
            ]);
            return;
        }

        update_option(self::BULK_OPTION_PENDING, $pending_ids, false);
        update_option(self::BULK_OPTION_PROGRESS, [
            'status' => 'running',
            'total' => $total_to_process,
            'processed' => 0,
            'last_run' => 0,
            'errors' => [],
            'current_id' => null,
            'current_item' => null,
            'recent_success_ids' => [],
            'last_success' => null,
        ], false);

        wp_schedule_single_event(time(), self::CRON_HOOK);

        wp_send_json_success([
            'status' => 'running',
            'total' => $total_to_process,
            'processed' => 0,
        ]);
    }

    public function handle_ajax_get_bulk_alt_status(): void
    {
        check_ajax_referer('king_addons_ai_seo_bulk_alt_status_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }

        $progress = get_option(self::BULK_OPTION_PROGRESS, [
            'status' => 'idle',
            'total' => 0,
            'processed' => 0,
            'last_run' => 0,
            'errors' => [],
            'current_id' => null,
            'current_item' => null,
            'recent_success_ids' => [],
            'last_success' => null,
        ]);

        $progress = $this->maybe_kick_bulk_processing($progress);

        wp_send_json_success($progress);
    }

    public function handle_ajax_stop_bulk_alt(): void
    {
        check_ajax_referer('king_addons_ai_seo_bulk_alt_stop_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }

        wp_clear_scheduled_hook(self::CRON_HOOK);
        delete_option(self::BULK_OPTION_PENDING);
        delete_transient(self::BULK_OPTION_LOCK);

        $progress = get_option(self::BULK_OPTION_PROGRESS, []);
        $progress['status'] = 'stopped';
        $progress['current_id'] = null;
        $progress['current_item'] = null;
        update_option(self::BULK_OPTION_PROGRESS, $progress, false);

        wp_send_json_success($progress);
    }

    public function process_bulk_alt_text_batch(): void
    {
        if ((bool) get_transient(self::BULK_OPTION_LOCK)) {
            return;
        }

        set_transient(self::BULK_OPTION_LOCK, 1, 60);

        $pending_ids = get_option(self::BULK_OPTION_PENDING, []);
        $progress = get_option(self::BULK_OPTION_PROGRESS, []);

        if (empty($pending_ids) || !is_array($pending_ids) || ($progress['status'] ?? '') !== 'running') {
            wp_clear_scheduled_hook(self::CRON_HOOK);
            delete_transient(self::BULK_OPTION_LOCK);
            return;
        }

        $batch_ids = array_slice($pending_ids, 0, self::BATCH_SIZE);

        $processed_in_batch = 0;
        $batch_errors = [];
        $batch_success_ids = [];
        $batch_success_items = [];

        foreach ($batch_ids as $attachment_id_raw) {
            $attachment_id = (int) $attachment_id_raw;
            $progress['current_id'] = $attachment_id;
            $progress['current_item'] = $this->get_image_debug_data($attachment_id);
            update_option(self::BULK_OPTION_PROGRESS, $progress, false);

            $current_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            if (wp_attachment_is_image($attachment_id) && empty($current_alt)) {
                $result = $this->alt_text_generator->generate_alt_text_for_image($attachment_id, true);
                if (is_wp_error($result)) {
                    $batch_errors[$attachment_id] = $result->get_error_message();
                } elseif (!is_string($result) || $result === '') {
                    $batch_errors[$attachment_id] = esc_html__('Unknown error.', 'king-addons');
                } else {
                    $batch_success_ids[] = $attachment_id;
                    $item = $this->get_image_debug_data($attachment_id);
                    $item['alt_text'] = $result;
                    $batch_success_items[] = $item;
                }
            }

            $processed_in_batch++;
        }

        $remaining_ids = array_slice($pending_ids, $processed_in_batch);
        update_option(self::BULK_OPTION_PENDING, $remaining_ids, false);

        $progress['processed'] = (int) ($progress['processed'] ?? 0) + $processed_in_batch;
        $progress['last_run'] = time();
        $progress['errors'] = array_slice(array_merge($progress['errors'] ?? [], $batch_errors), -20);
        $progress['recent_success_ids'] = array_slice(array_merge($batch_success_ids, $progress['recent_success_ids'] ?? []), 0, 5);
        $progress['current_id'] = null;
        $progress['current_item'] = null;
        if (!empty($batch_success_items)) {
            $progress['last_success'] = $batch_success_items[0];
        }

        if (empty($remaining_ids)) {
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

    private function maybe_kick_bulk_processing(array $progress): array
    {
        if (($progress['status'] ?? 'idle') !== 'running') {
            return $progress;
        }

        $next_scheduled = wp_next_scheduled(self::CRON_HOOK);
        $last_run = (int) ($progress['last_run'] ?? 0);
        $delay = $this->get_bulk_delay();
        $is_stale = $last_run > 0 && (time() - $last_run) > ($delay + 5);

        if (($next_scheduled === false && !(bool) get_transient(self::BULK_OPTION_LOCK)) || $is_stale) {
            $this->process_bulk_alt_text_batch();
            return get_option(self::BULK_OPTION_PROGRESS, $progress);
        }

        return $progress;
    }

    private function get_bulk_delay(): int
    {
        $options = get_option('king_addons_ai_options', []);
        $delay = isset($options['ai_seo_bulk_processing_delay']) ? (int) $options['ai_seo_bulk_processing_delay'] : 2;
        return max(1, min(30, $delay));
    }

    private function get_image_debug_data(int $attachment_id): array
    {
        $file_path = get_attached_file($attachment_id);
        $file_name = is_string($file_path) ? wp_basename($file_path) : '';

        return [
            'id' => $attachment_id,
            'title' => (string) get_the_title($attachment_id),
            'filename' => (string) $file_name,
            'thumb_url' => (string) wp_get_attachment_image_url($attachment_id, 'thumbnail'),
        ];
    }

    public function handle_ajax_get_stats(): void
    {
        check_ajax_referer('king_addons_ai_seo_get_alt_stats_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'king-addons')], 403);
        }

        wp_send_json_success(Alt_Text_Generator::get_alt_text_stats());
    }

    /**
     * @return int[]
     */
    private function get_images_without_alt(): array
    {
        $ids = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'relation' => 'OR',
                    [
                        'key' => '_wp_attachment_image_alt',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key' => '_wp_attachment_image_alt',
                        'value' => '',
                        'compare' => '=',
                    ],
                ],
            ],
        ]);

        return array_map('intval', $ids);
    }
}
