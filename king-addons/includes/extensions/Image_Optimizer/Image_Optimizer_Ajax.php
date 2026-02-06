<?php
/**
 * Image Optimizer AJAX handlers.
 *
 * @package King_Addons
 */

namespace King_Addons\Image_Optimizer;

if (!defined('ABSPATH')) {
    exit;
}

class Image_Optimizer_Ajax
{
    private static ?Image_Optimizer_Ajax $instance = null;

    public static function instance(): Image_Optimizer_Ajax
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        // Get image data for optimization
        add_action('wp_ajax_king_img_get_image_data', [$this, 'get_image_data']);
        
        // Save optimized image
        add_action('wp_ajax_king_img_save_optimized', [$this, 'save_optimized']);
        
        // Apply WebP URLs (replace in database)
        add_action('wp_ajax_king_img_apply_webp_urls', [$this, 'apply_webp_urls']);
        
        // Restore original URLs
        add_action('wp_ajax_king_img_restore_original', [$this, 'restore_original']);
        
        // Get images for bulk optimization
        add_action('wp_ajax_king_img_get_bulk_images', [$this, 'get_bulk_images']);
        
        // Save settings
        add_action('wp_ajax_king_img_save_settings', [$this, 'save_settings']);

        // Get settings (used by Media Library auto-optimize to avoid stale localized settings)
        add_action('wp_ajax_king_img_get_settings', [$this, 'get_settings']);

        // Get fresh Attachment Details card HTML (used to update media modal without re-select)
        add_action('wp_ajax_king_img_get_attachment_card_html', [$this, 'get_attachment_card_html']);
        
        // Get global stats
        add_action('wp_ajax_king_img_get_stats', [$this, 'get_stats']);

        // Get image format breakdown (for dynamic UI refresh)
        add_action('wp_ajax_king_img_get_breakdown', [$this, 'get_breakdown']);
        
        // Full restore (delete WebP files)
        add_action('wp_ajax_king_img_full_restore', [$this, 'full_restore']);
        
        // Get optimization state for resume
        add_action('wp_ajax_king_img_get_state', [$this, 'get_optimization_state']);
        
        // Save optimization state
        add_action('wp_ajax_king_img_save_state', [$this, 'save_optimization_state']);
        
        // Clear optimization state
        add_action('wp_ajax_king_img_clear_state', [$this, 'clear_optimization_state']);

        // Mark image as skipped (e.g., too small)
        add_action('wp_ajax_king_img_mark_skipped', [$this, 'mark_skipped']);

        // Mark image as failed (so it doesn't stay pending)
        add_action('wp_ajax_king_img_mark_failed', [$this, 'mark_failed']);

        // Media Library sync (fix attachment paths/filesizes after optimization)
        add_action('wp_ajax_king_img_get_sync_ids', [$this, 'get_sync_ids']);
        add_action('wp_ajax_king_img_sync_batch', [$this, 'sync_media_library_batch']);
        
        // Get optimized image IDs for bulk restore
        add_action('wp_ajax_king_img_get_optimized_ids', [$this, 'get_optimized_ids']);
        
        // Bulk restore single image
        add_action('wp_ajax_king_img_bulk_restore_single', [$this, 'bulk_restore_single']);
    }

    /**
     * Get current settings.
     */
    public function get_settings(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $optimizer = Image_Optimizer::instance();

        wp_send_json_success([
            'settings' => $optimizer->get_settings(),
        ]);
    }

    /**
     * Get freshly rendered Attachment Details "King Image Optimizer" card HTML.
     */
    public function get_attachment_card_html(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $attachment_id = absint($_POST['attachment_id'] ?? 0);
        if (!$attachment_id || !wp_attachment_is_image($attachment_id)) {
            wp_send_json_error(['message' => 'Invalid attachment'], 400);
        }

        $optimizer = Image_Optimizer::instance();
        $html = $optimizer->get_attachment_optimizer_card_html($attachment_id);

        wp_send_json_success([
            'html' => $html,
        ]);
    }

    /**
     * Verify nonce and capabilities.
     */
    private function verify_request(): bool
    {
        if (!check_ajax_referer('king_img_optimizer_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'king-addons')]);
            return false;
        }

        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => __('Permission denied.', 'king-addons')]);
            return false;
        }

        return true;
    }

    /**
     * Get image data for optimization.
     */
    public function get_image_data(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $attachment_id = absint($_POST['attachment_id'] ?? 0);
        $sizes = isset($_POST['sizes']) ? (is_array($_POST['sizes']) ? array_map('sanitize_text_field', $_POST['sizes']) : sanitize_text_field($_POST['sizes'])) : 'all';

        if (!$attachment_id || !wp_attachment_is_image($attachment_id)) {
            wp_send_json_error(['message' => __('Invalid attachment ID.', 'king-addons')]);
            return;
        }

        $file = get_attached_file($attachment_id);
        if (!$file || !file_exists($file)) {
            wp_send_json_error(['message' => __('File not found.', 'king-addons')]);
            return;
        }

        $metadata = wp_get_attachment_metadata($attachment_id);
        $upload_dir = wp_upload_dir();
        $base_dir = trailingslashit(dirname($file));
        $mime_type = get_post_mime_type($attachment_id);

        $images = [];

        // Full size
        if ($sizes === 'all' || in_array('full', (array) $sizes, true)) {
            $images['full'] = [
                'url' => wp_get_attachment_url($attachment_id),
                'path' => $file,
                'width' => $metadata['width'] ?? 0,
                'height' => $metadata['height'] ?? 0,
                'filesize' => filesize($file),
                'mime_type' => $mime_type,
            ];
        }

        // Registered sizes
        if (!empty($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size_name => $size_data) {
                if ($sizes !== 'all' && !in_array($size_name, (array) $sizes, true)) {
                    continue;
                }

                $size_file = $base_dir . $size_data['file'];
                if (file_exists($size_file)) {
                    $images[$size_name] = [
                        'url' => $upload_dir['baseurl'] . '/' . dirname($metadata['file']) . '/' . $size_data['file'],
                        'path' => $size_file,
                        'width' => $size_data['width'],
                        'height' => $size_data['height'],
                        'filesize' => filesize($size_file),
                        'mime_type' => $size_data['mime-type'] ?? $mime_type,
                    ];
                }
            }
        }

        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'images' => $images,
            'base_dir' => $base_dir,
        ]);
    }

    /**
     * Save optimized image.
     */
    public function save_optimized(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $attachment_id = absint($_POST['attachment_id'] ?? 0);
        $size = sanitize_text_field($_POST['size'] ?? 'full');
        $format = 'webp';
        $image_data = $_POST['image_data'] ?? ''; // Base64 encoded image
        $original_size = absint($_POST['original_size'] ?? 0);
        $optimized_size = absint($_POST['optimized_size'] ?? 0);
        $method = 'canvas';

        if (!$attachment_id || !wp_attachment_is_image($attachment_id)) {
            wp_send_json_error(['message' => __('Invalid attachment ID.', 'king-addons')]);
            return;
        }

        if (empty($image_data)) {
            wp_send_json_error(['message' => __('No image data provided.', 'king-addons')]);
            return;
        }

        $optimizer = Image_Optimizer::instance();
        $is_pro = $optimizer->is_pro();
        $month_key = wp_date('Y-m', (int) current_time('timestamp'));

        // Quota is consumed once per attachment per month (not per-size).
        $existing_meta = Image_Optimizer_DB::get_optimization_meta($attachment_id) ?: [];
        $already_counted = ((string) ($existing_meta['quota_month_key'] ?? '') === $month_key);

        if (!$is_pro && !$already_counted) {
            $quota = $optimizer->get_free_quota_state();
            if (!empty($quota['remaining']) && (int) $quota['remaining'] <= 0) {
                wp_send_json_error([
                    'message' => __('Free plan limit reached (200 optimizations/month). Upgrade to Unlimited to continue.', 'king-addons'),
                    'code' => 'quota_exceeded',
                    'quota' => $quota,
                    'upgrade_url' => $optimizer->get_upgrade_url('kng-img-optimizer'),
                ], 403);
                return;
            }
        }

        // Decode base64 image
        $image_data = preg_replace('#^data:image/\w+;base64,#i', '', $image_data);
        $decoded = base64_decode($image_data);
        
        if ($decoded === false) {
            wp_send_json_error(['message' => __('Failed to decode image data.', 'king-addons')]);
            return;
        }

        // Get original file path
        $file = get_attached_file($attachment_id);
        $metadata = wp_get_attachment_metadata($attachment_id);
        $base_dir = trailingslashit(dirname($file));

        // Determine target file path
        if ($size === 'full') {
            $original_path = $file;
        } else {
            if (empty($metadata['sizes'][$size]['file'])) {
                wp_send_json_error(['message' => __('Size not found.', 'king-addons')]);
                return;
            }
            $original_path = $base_dir . $metadata['sizes'][$size]['file'];
        }

        // Get original extension
        $original_ext = strtolower(pathinfo($original_path, PATHINFO_EXTENSION));
        
        // Create optimized file path
        // If format is same as original, we still save as separate file for rollback capability
        if ($format === $original_ext || ($format === 'jpg' && $original_ext === 'jpeg') || ($format === 'jpeg' && $original_ext === 'jpg')) {
            // Same format - add suffix before extension to preserve original
            $optimized_path = preg_replace('/\.([^.]+)$/', '-ka-opt.$1', $original_path);
        } else {
            // Different format - change extension
            $optimized_path = preg_replace('/\.[^.]+$/', '.' . $format, $original_path);
        }
        
        // Make sure we're writing to uploads directory
        $upload_dir = wp_upload_dir();
        if (strpos($optimized_path, $upload_dir['basedir']) !== 0) {
            wp_send_json_error(['message' => __('Invalid file path.', 'king-addons')]);
            return;
        }

        // Save the optimized file
        $bytes_written = file_put_contents($optimized_path, $decoded);
        
        if ($bytes_written === false) {
            wp_send_json_error(['message' => __('Failed to save optimized image.', 'king-addons')]);
            return;
        }

        // Use real on-disk sizes (client-provided sizes are only estimates).
        $actual_original_size = (file_exists($original_path) ? (int) filesize($original_path) : (int) $original_size);
        $actual_optimized_size = (file_exists($optimized_path) ? (int) filesize($optimized_path) : (int) $bytes_written);

        // Update optimization metadata
        $meta = $existing_meta;
        
        if (!isset($meta['sizes'])) {
            $meta['sizes'] = [];
        }

        $saved_bytes = max(0, $actual_original_size - $actual_optimized_size);
        
        $meta['sizes'][$size] = [
            'original_path' => $original_path,
            'optimized_path' => $optimized_path,
            'webp_path' => $optimized_path, // Alias for backward compatibility
            'original_size' => $actual_original_size,
            'optimized_size' => $actual_optimized_size,
            'saved_bytes' => $saved_bytes,
            'format' => $format,
            'method' => $method,
            'optimized_at' => current_time('mysql'),
        ];

        // Calculate totals
        $meta['total_original_bytes'] = 0;
        $meta['total_optimized_bytes'] = 0;
        $meta['total_saved_bytes'] = 0;

        foreach ($meta['sizes'] as $s) {
            $meta['total_original_bytes'] += $s['original_size'];
            $meta['total_optimized_bytes'] += $s['optimized_size'];
            $meta['total_saved_bytes'] += $s['saved_bytes'];
        }

        $meta['savings_percent'] = $meta['total_original_bytes'] > 0 
            ? round(($meta['total_saved_bytes'] / $meta['total_original_bytes']) * 100, 1) 
            : 0;

        $meta['status'] = 'optimized';
        $meta['completed_at'] = current_time('mysql');
        $meta['format'] = $format;
        $meta['method'] = $method;

        // Consume quota only once per attachment per month.
        $quota_state = null;
        if (!$is_pro && !$already_counted) {
            $meta['quota_month_key'] = $month_key;
            $quota_state = $optimizer->consume_free_quota(1);
        } else {
            $quota_state = $optimizer->get_free_quota_state();
        }

        Image_Optimizer_DB::set_optimization_meta($attachment_id, $meta);

        // Sync Media Library attachment to the optimized files (keeps originals via backup metas)
        Image_Optimizer_DB::sync_attachment_to_optimized($attachment_id, $meta);

        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'size' => $size,
            'optimized_path' => $optimized_path,
            'original_size' => $actual_original_size,
            'optimized_size' => $actual_optimized_size,
            'saved_bytes' => $saved_bytes,
            'savings_percent' => $meta['savings_percent'],
            'quota' => $quota_state,
        ]);
    }

    /**
     * Mark an image as skipped (e.g., too small to optimize).
     */
    public function mark_skipped(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $attachment_id = absint($_POST['attachment_id'] ?? 0);
        $reason = sanitize_text_field($_POST['reason'] ?? '');

        if (!$attachment_id || !wp_attachment_is_image($attachment_id)) {
            wp_send_json_error(['message' => __('Invalid attachment ID.', 'king-addons')]);
            return;
        }

        $meta = Image_Optimizer_DB::get_optimization_meta($attachment_id) ?: [];
        $meta['status'] = 'skipped';
        $meta['skipped_reason'] = $reason ?: 'skipped';
        $meta['skipped_at'] = current_time('mysql');
        $meta['total_original_bytes'] = $meta['total_original_bytes'] ?? 0;
        $meta['total_optimized_bytes'] = $meta['total_optimized_bytes'] ?? 0;
        $meta['total_saved_bytes'] = $meta['total_saved_bytes'] ?? 0;

        Image_Optimizer_DB::set_optimization_meta($attachment_id, $meta);

        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'status' => 'skipped',
        ]);
    }

    /**
     * Mark an image as failed.
     */
    public function mark_failed(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $attachment_id = absint($_POST['attachment_id'] ?? 0);
        $reason = sanitize_text_field($_POST['reason'] ?? '');

        if (!$attachment_id || !wp_attachment_is_image($attachment_id)) {
            wp_send_json_error(['message' => __('Invalid attachment ID.', 'king-addons')]);
            return;
        }

        $meta = Image_Optimizer_DB::get_optimization_meta($attachment_id) ?: [];
        $meta['status'] = 'failed';
        $meta['failed_reason'] = $reason ?: 'failed';
        $meta['failed_at'] = current_time('mysql');
        $meta['total_original_bytes'] = $meta['total_original_bytes'] ?? 0;
        $meta['total_optimized_bytes'] = $meta['total_optimized_bytes'] ?? 0;
        $meta['total_saved_bytes'] = $meta['total_saved_bytes'] ?? 0;

        Image_Optimizer_DB::set_optimization_meta($attachment_id, $meta);

        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'status' => 'failed',
        ]);
    }

    /**
     * Get IDs that are optimized and eligible for Media Library sync.
     */
    public function get_sync_ids(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        global $wpdb;

        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT pm.post_id FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                 WHERE pm.meta_key = %s
                   AND p.post_type = 'attachment'
                   AND pm.meta_value LIKE %s",
                '_king_img_optimized',
                '%"status";s:9:"optimized"%'
            )
        );

        $ids = array_values(array_unique(array_map('absint', $ids)));

        wp_send_json_success([
            'ids' => $ids,
            'total' => count($ids),
        ]);
    }

    /**
     * Sync a batch of attachment IDs to their optimized files.
     */
    public function sync_media_library_batch(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $ids_raw = $_POST['ids'] ?? '[]';
        if (!is_string($ids_raw)) {
            $ids_raw = wp_json_encode($ids_raw);
        }

        $ids = json_decode(stripslashes($ids_raw), true);
        if (!is_array($ids)) {
            wp_send_json_error(['message' => __('Invalid IDs payload.', 'king-addons')]);
            return;
        }

        $synced = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($ids as $attachment_id) {
            $attachment_id = absint($attachment_id);
            if (!$attachment_id) {
                $skipped++;
                continue;
            }

            $meta = Image_Optimizer_DB::get_optimization_meta($attachment_id);
            if (empty($meta) || ($meta['status'] ?? '') !== 'optimized') {
                $skipped++;
                continue;
            }

            $opt_full = $meta['sizes']['full']['optimized_path'] ?? $meta['sizes']['full']['webp_path'] ?? '';
            if (empty($opt_full) || !is_string($opt_full) || !file_exists($opt_full)) {
                $skipped++;
                continue;
            }

            try {
                Image_Optimizer_DB::sync_attachment_to_optimized($attachment_id, $meta);
                $synced++;
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        wp_send_json_success([
            'synced' => $synced,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }

    /**
     * Apply WebP URLs in database.
     */
    public function apply_webp_urls(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $attachment_id = absint($_POST['attachment_id'] ?? 0);

        if (!$attachment_id) {
            wp_send_json_error(['message' => __('Invalid attachment ID.', 'king-addons')]);
            return;
        }

        $meta = Image_Optimizer_DB::get_optimization_meta($attachment_id);
        
        if (empty($meta) || $meta['status'] !== 'optimized') {
            wp_send_json_error(['message' => __('Image not optimized yet.', 'king-addons')]);
            return;
        }

        $updated = Image_Optimizer_DB::apply_webp_urls($attachment_id, $meta);

        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'updated_count' => $updated,
            'message' => sprintf(__('Updated %d URL references.', 'king-addons'), $updated),
        ]);
    }

    /**
     * Restore original URLs.
     */
    public function restore_original(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $attachment_id = absint($_POST['attachment_id'] ?? 0);

        if (!$attachment_id) {
            wp_send_json_error(['message' => __('Invalid attachment ID.', 'king-addons')]);
            return;
        }

        $updated = Image_Optimizer_DB::revert_to_original_urls($attachment_id);

        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'updated_count' => $updated,
            'message' => sprintf(__('Reverted %d URL references.', 'king-addons'), $updated),
        ]);
    }

    /**
     * Get images for bulk optimization.
     */
    public function get_bulk_images(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $page = absint($_POST['page'] ?? 1);
        $per_page = absint($_POST['per_page'] ?? 50);
        $filter = sanitize_text_field($_POST['filter'] ?? 'all'); // all, pending, optimized
        $format_filter = isset($_POST['format_filter']) ? array_map('sanitize_text_field', (array) $_POST['format_filter']) : [];

        global $wpdb;

        // Build query
        $args = [
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'inherit',
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        // Format filter
        if (!empty($format_filter)) {
            $mime_types = [];
            foreach ($format_filter as $fmt) {
                if ($fmt === 'jpeg') {
                    $mime_types[] = 'image/jpeg';
                } elseif ($fmt === 'png') {
                    $mime_types[] = 'image/png';
                } elseif ($fmt === 'webp') {
                    $mime_types[] = 'image/webp';
                } elseif ($fmt === 'gif') {
                    $mime_types[] = 'image/gif';
                }
            }
            if (!empty($mime_types)) {
                $args['post_mime_type'] = $mime_types;
            }
        }

        // Status filter
        if ($filter === 'pending') {
            $args['meta_query'] = [
                'relation' => 'OR',
                [
                    'key' => '_king_img_optimized',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key' => '_king_img_optimized',
                    'value' => '"status";s:7:"pending"',
                    'compare' => 'LIKE',
                ],
            ];
        } elseif ($filter === 'optimized') {
            $args['meta_query'] = [
                [
                    'key' => '_king_img_optimized',
                    'value' => '"status";s:9:"optimized"',
                    'compare' => 'LIKE',
                ],
            ];
        }

        $query = new \WP_Query($args);
        $images = [];

        foreach ($query->posts as $post) {
            $file = get_attached_file($post->ID);
            $metadata = wp_get_attachment_metadata($post->ID);
            $opt_meta = Image_Optimizer_DB::get_optimization_meta($post->ID);

            $sizes = [];
            
            // Full size
            if ($file && file_exists($file)) {
                $sizes['full'] = [
                    'width' => $metadata['width'] ?? 0,
                    'height' => $metadata['height'] ?? 0,
                    'filesize' => filesize($file),
                ];
            }

            // Other sizes
            if (!empty($metadata['sizes'])) {
                $base_dir = trailingslashit(dirname($file));
                foreach ($metadata['sizes'] as $size_name => $size_data) {
                    $size_file = $base_dir . $size_data['file'];
                    if (file_exists($size_file)) {
                        $sizes[$size_name] = [
                            'width' => $size_data['width'],
                            'height' => $size_data['height'],
                            'filesize' => filesize($size_file),
                        ];
                    }
                }
            }

            $images[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'filename' => basename($file),
                'url' => wp_get_attachment_url($post->ID),
                'thumb_url' => wp_get_attachment_image_url($post->ID, 'thumbnail'),
                'mime_type' => $post->post_mime_type,
                'status' => $opt_meta['status'] ?? 'pending',
                'sizes' => $sizes,
                'total_size' => array_sum(array_column($sizes, 'filesize')),
                'optimization' => $opt_meta,
            ];
        }

        wp_send_json_success([
            'images' => $images,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'page' => $page,
        ]);
    }

    /**
     * Save settings via AJAX.
     */
    public function save_settings(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'king-addons')]);
            return;
        }

        $settings = [
            'quality' => absint($_POST['quality'] ?? 82),
            'auto_replace_urls' => !empty($_POST['auto_replace_urls']),
            'auto_optimize_uploads' => !empty($_POST['auto_optimize_uploads']),
            'skip_small' => !empty($_POST['skip_small']),
            'min_size' => absint($_POST['min_size'] ?? 10240),
            'resize_enabled' => !empty($_POST['resize_enabled']),
            'max_width' => absint($_POST['max_width'] ?? 2048),
            'create_backups' => !empty($_POST['create_backups']),
        ];
        $optimizer = Image_Optimizer::instance();
        $saved = $optimizer->save_settings($settings);

        if ($saved) {
            wp_send_json_success(['message' => __('Settings saved successfully.', 'king-addons')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save settings.', 'king-addons')]);
        }
    }

    /**
     * Get global stats.
     */
    public function get_stats(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $optimizer = Image_Optimizer::instance();
        $stats = $optimizer->get_global_stats();

        wp_send_json_success($stats);
    }

    /**
     * Get image format breakdown.
     */
    public function get_breakdown(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $optimizer = Image_Optimizer::instance();
        $stats = $optimizer->get_global_stats();
        $format_counts = Image_Optimizer_DB::count_images_by_format();

        wp_send_json_success([
            'total_images' => (int) ($stats['total_images'] ?? 0),
            'formats' => $format_counts,
        ]);
    }

    /**
     * Full restore - delete optimized files.
     */
    public function full_restore(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $attachment_id = absint($_POST['attachment_id'] ?? 0);

        if (!$attachment_id) {
            wp_send_json_error(['message' => __('Invalid attachment ID.', 'king-addons')]);
            return;
        }

        // First revert URLs
        Image_Optimizer_DB::revert_to_original_urls($attachment_id);

        // Get meta and delete optimized files
        $meta = Image_Optimizer_DB::get_optimization_meta($attachment_id);
        $deleted_files = 0;

        if (!empty($meta['sizes'])) {
            foreach ($meta['sizes'] as $size => $data) {
                // Try optimized_path first, then webp_path for backward compatibility
                $file_to_delete = $data['optimized_path'] ?? $data['webp_path'] ?? '';
                if (!empty($file_to_delete) && file_exists($file_to_delete)) {
                    if (@unlink($file_to_delete)) {
                        $deleted_files++;
                    }
                }
            }
        }

        // Delete optimization metadata
        Image_Optimizer_DB::delete_optimization_meta($attachment_id);

        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'deleted_files' => $deleted_files,
            'message' => sprintf(__('Restored original. Deleted %d optimized files.', 'king-addons'), $deleted_files),
        ]);
    }

    /**
     * Get optimization state for resume.
     */
    public function get_optimization_state(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $user_id = get_current_user_id();
        $state = get_user_meta($user_id, '_king_img_bulk_state', true);

        if (empty($state)) {
            wp_send_json_success(['has_state' => false]);
            return;
        }

        wp_send_json_success([
            'has_state' => true,
            'state' => $state,
        ]);
    }

    /**
     * Save optimization state.
     */
    public function save_optimization_state(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $user_id = get_current_user_id();
        $state = [
            'currentIndex' => absint($_POST['currentIndex'] ?? 0),
            'totalImages' => absint($_POST['totalImages'] ?? 0),
            'successCount' => absint($_POST['successCount'] ?? 0),
            'errorCount' => absint($_POST['errorCount'] ?? 0),
            'totalSavedBytes' => absint($_POST['totalSavedBytes'] ?? 0),
            'imageQueue' => isset($_POST['imageQueue']) ? json_decode(stripslashes($_POST['imageQueue']), true) : [],
            'settings' => isset($_POST['settings']) ? json_decode(stripslashes($_POST['settings']), true) : [],
            'saved_at' => current_time('mysql'),
        ];

        update_user_meta($user_id, '_king_img_bulk_state', $state);

        wp_send_json_success(['message' => __('State saved.', 'king-addons')]);
    }

    /**
     * Clear optimization state.
     */
    public function clear_optimization_state(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $user_id = get_current_user_id();
        delete_user_meta($user_id, '_king_img_bulk_state');

        wp_send_json_success(['message' => __('State cleared.', 'king-addons')]);
    }

    /**
     * Get all optimized image IDs for bulk restore.
     */
    public function get_optimized_ids(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        global $wpdb;

        // Get all attachment IDs that have optimization meta
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
                '_king_img_optimized'
            )
        );

        wp_send_json_success([
            'ids' => array_map('absint', $ids),
            'total' => count($ids),
        ]);
    }

    /**
     * Bulk restore single image (used in batch processing).
     */
    public function bulk_restore_single(): void
    {
        if (!$this->verify_request()) {
            return;
        }

        $attachment_id = absint($_POST['attachment_id'] ?? 0);

        if (!$attachment_id) {
            wp_send_json_error(['message' => __('Invalid attachment ID.', 'king-addons')]);
            return;
        }

        // Revert URLs in database
        Image_Optimizer_DB::revert_to_original_urls($attachment_id);

        // Get meta and delete optimized files
        $meta = Image_Optimizer_DB::get_optimization_meta($attachment_id);
        $deleted_files = 0;

        if (!empty($meta['sizes'])) {
            foreach ($meta['sizes'] as $size => $data) {
                $file_to_delete = $data['optimized_path'] ?? $data['webp_path'] ?? '';
                if (!empty($file_to_delete) && file_exists($file_to_delete)) {
                    if (@unlink($file_to_delete)) {
                        $deleted_files++;
                    }
                }
            }
        }

        // Delete optimization metadata
        Image_Optimizer_DB::delete_optimization_meta($attachment_id);

        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'deleted_files' => $deleted_files,
        ]);
    }
}
