<?php
/**
 * Image Optimizer extension.
 *
 * Unlimited Image Optimizer - Browser-based image conversion to WebP (Canvas API).
 *
 * @package King_Addons
 */

namespace King_Addons\Image_Optimizer;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/Image_Optimizer_DB.php';
require_once __DIR__ . '/Image_Optimizer_Ajax.php';

class Image_Optimizer
{
    private const OPTION_NAME = 'king_addons_image_optimizer_settings';
    private const META_KEY = '_king_img_optimized';

    private const FREE_MONTHLY_OPTIMIZATION_LIMIT = 200;
    private const FREE_QUOTA_OPTION = 'king_addons_img_opt_free_quota';

    private static ?Image_Optimizer $instance = null;

    /**
     * Cached settings.
     *
     * @var array<string, mixed>
     */
    private array $settings = [];

    public static function instance(): Image_Optimizer
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->settings = $this->get_settings();

        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Initialize AJAX handlers
        Image_Optimizer_Ajax::instance();

        // Add media library column
        add_filter('manage_media_columns', [$this, 'add_media_column']);
        add_action('manage_media_custom_column', [$this, 'render_media_column'], 10, 2);

        // Add attachment meta box
        add_action('add_meta_boxes_attachment', [$this, 'add_attachment_metabox']);

        // Attachment Details panel (upload.php?item=ID) + Media modal
        add_filter('attachment_fields_to_edit', [$this, 'add_attachment_details_fields'], 10, 2);

        // Clean up WebP files when attachment is deleted
        add_action('delete_attachment', [$this, 'cleanup_on_delete']);
    }

    /**
     * Add Image Optimizer details to the Attachment Details screen and media modal.
     *
     * @param array $form_fields
     * @param \WP_Post $post
     * @return array
     */
    public function add_attachment_details_fields(array $form_fields, \WP_Post $post): array
    {
        if (!wp_attachment_is_image($post->ID)) {
            return $form_fields;
        }

        $form_fields['king_img_optimizer'] = [
            'label' => esc_html__('Image Optimizer', 'king-addons'),
            'input' => 'html',
            'html'  => $this->get_attachment_optimizer_card_html((int) $post->ID),
        ];

        return $form_fields;
    }

    /**
     * Build the "King Image Optimizer" card HTML for Attachment Details / media modal.
     */
    public function get_attachment_optimizer_card_html(int $attachment_id): string
    {
        if (!wp_attachment_is_image($attachment_id)) {
            return '';
        }

        $file = get_attached_file($attachment_id);
        $original_bytes_current = (!empty($file) && is_string($file) && file_exists($file)) ? (int) filesize($file) : 0;

        $meta = Image_Optimizer_DB::get_optimization_meta($attachment_id) ?: [];
        $is_optimized = (($meta['status'] ?? '') === 'optimized');

        $original_bytes = $original_bytes_current;
        $optimized_bytes = 0;
        $saved_bytes = 0;
        $saved_percent = 0;

        if ($is_optimized) {
            $full = is_array($meta['sizes']['full'] ?? null) ? $meta['sizes']['full'] : [];
            $original_path = $full['original_path'] ?? '';
            $optimized_path = $full['optimized_path'] ?? ($full['webp_path'] ?? '');

            if (!empty($original_path) && is_string($original_path) && file_exists($original_path)) {
                $original_bytes = (int) filesize($original_path);
            } else {
                $original_bytes = (int) ($full['original_size'] ?? ($meta['total_original_bytes'] ?? $original_bytes));
            }

            if (!empty($optimized_path) && is_string($optimized_path) && file_exists($optimized_path)) {
                $optimized_bytes = (int) filesize($optimized_path);
            } else {
                $optimized_bytes = (int) ($full['optimized_size'] ?? ($meta['total_optimized_bytes'] ?? 0));
            }

            $saved_bytes = max(0, $original_bytes - $optimized_bytes);
            $saved_percent = $original_bytes > 0 ? round(($saved_bytes / $original_bytes) * 100, 1) : 0;
        }

        $btn_label = $is_optimized
            ? esc_html__('Restore Original', 'king-addons')
            : esc_html__('Optimize Image', 'king-addons');

        $btn_class = $is_optimized ? 'button' : 'button button-primary';
        $btn_action = $is_optimized ? 'restore' : 'convert';

        $status_badge = $is_optimized
            ? '<span class="king-img-attach-badge king-img-attach-badge--optimized">' . esc_html__('Optimized', 'king-addons') . '</span>'
            : '<span class="king-img-attach-badge king-img-attach-badge--pending">' . esc_html__('Not optimized', 'king-addons') . '</span>';

        $rows_html = '';
        $rows_html .= '<div class="king-img-attach-row"><span class="king-img-attach-k">' . esc_html__('Original', 'king-addons') . '</span><span class="king-img-attach-v">' . esc_html(self::format_bytes($original_bytes)) . '</span></div>';

        if ($is_optimized) {
            $rows_html .= '<div class="king-img-attach-row"><span class="king-img-attach-k">' . esc_html__('Optimized', 'king-addons') . '</span><span class="king-img-attach-v">' . esc_html(self::format_bytes($optimized_bytes)) . '</span></div>';
            $rows_html .= '<div class="king-img-attach-row"><span class="king-img-attach-k">' . esc_html__('Saved', 'king-addons') . '</span><span class="king-img-attach-v king-img-attach-v--good">' . esc_html(self::format_bytes($saved_bytes)) . ' (' . esc_html($saved_percent) . '%)</span></div>';
        }

        $html = '';
        $html .= '<div class="king-img-attach-card" data-king-img-attachment-id="' . esc_attr((string) $attachment_id) . '" data-king-img-status="' . esc_attr($is_optimized ? 'optimized' : 'pending') . '">';
        $html .= '<div class="king-img-attach-head">';
        $html .= '<div class="king-img-attach-title">' . esc_html__('King Image Optimizer', 'king-addons') . '</div>';
        $html .= '<div class="king-img-attach-badges">' . $status_badge . '</div>';
        $html .= '</div>';
        $html .= '<div class="king-img-attach-body">' . $rows_html . '</div>';
        $html .= '<div class="king-img-attach-actions">';
        $html .= '<button type="button" class="' . esc_attr($btn_class) . ' king-img-attachment-action" data-king-img-action="' . esc_attr($btn_action) . '">' . esc_html($btn_label) . '</button>';
        $html .= '<span class="spinner king-img-attach-spinner" style="float:none;"></span>';
        $html .= '<span class="king-img-attach-msg" aria-live="polite"></span>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Check if Pro version is available.
     */
    public function is_pro(): bool
    {
        // If the PRO plugin is active, it defines these constants.
        if (defined('KING_ADDONS_PRO_PATH') || defined('KING_ADDONS_PRO_VERSION')) {
            return true;
        }

        // License-based check (Freemius).
        if (function_exists('king_addons_freemius')) {
            $fs = king_addons_freemius();
            if (is_object($fs) && method_exists($fs, 'can_use_premium_code__premium_only')) {
                return (bool) $fs->can_use_premium_code__premium_only();
            }
        }

        // Backward-compatible helper.
        return function_exists('king_addons_can_use_pro') && king_addons_can_use_pro();
    }

    /**
     * Get monthly free quota state.
     *
     * @return array{limit:int, used:int, remaining:int, month_key:string}
     */
    public function get_free_quota_state(): array
    {
        $limit = (int) self::FREE_MONTHLY_OPTIMIZATION_LIMIT;
        $month_key = wp_date('Y-m', (int) current_time('timestamp'));

        $stored = get_option(self::FREE_QUOTA_OPTION, []);
        $stored = is_array($stored) ? $stored : [];
        $stored_month = (string) ($stored['month_key'] ?? '');
        $used = (int) ($stored['used'] ?? 0);

        if ($stored_month !== $month_key || $used < 0) {
            $used = 0;
            update_option(self::FREE_QUOTA_OPTION, [
                'month_key' => $month_key,
                'used' => 0,
            ]);
        }

        $remaining = max(0, $limit - $used);

        return [
            'limit' => $limit,
            'used' => $used,
            'remaining' => $remaining,
            'month_key' => $month_key,
        ];
    }

    /**
     * Consume free quota.
     *
     * @return array{limit:int, used:int, remaining:int, month_key:string}
     */
    public function consume_free_quota(int $amount = 1): array
    {
        $amount = max(0, (int) $amount);
        if ($amount === 0) {
            return $this->get_free_quota_state();
        }

        $state = $this->get_free_quota_state();
        $new_used = (int) $state['used'] + $amount;

        update_option(self::FREE_QUOTA_OPTION, [
            'month_key' => (string) $state['month_key'],
            'used' => $new_used,
        ]);

        return $this->get_free_quota_state();
    }

    /**
     * Upgrade URL used across the plugin.
     */
    public function get_upgrade_url(string $source = 'kng-img-optimizer'): string
    {
        $source = sanitize_key($source);
        return 'https://kingaddons.com/pricing/?utm_source=' . rawurlencode($source) . '&utm_medium=wp-admin&utm_campaign=kng';
    }

    /**
     * Get default settings.
     */
    public function get_default_settings(): array
    {
        return [
            'quality' => 82,
            'auto_replace_urls' => true,
            'auto_optimize_uploads' => false,
            'skip_small' => false,
            'min_size' => 10240, // 10KB
            'resize_enabled' => true,
            'max_width' => 2048,
            'create_backups' => true,
        ];
    }

    /**
     * Get current settings.
     */
    public function get_settings(): array
    {
        $saved = get_option(self::OPTION_NAME, []);
        return wp_parse_args($saved, $this->get_default_settings());
    }

    /**
     * Save settings.
     */
    public function save_settings(array $settings): bool
    {
        $sanitized = [
            'quality' => max(1, min(100, absint($settings['quality'] ?? 82))),
            'auto_replace_urls' => !empty($settings['auto_replace_urls']),
            'auto_optimize_uploads' => $this->is_pro() && !empty($settings['auto_optimize_uploads']),
            'skip_small' => !empty($settings['skip_small']),
            'min_size' => absint($settings['min_size'] ?? 10240),
            'resize_enabled' => !empty($settings['resize_enabled']),
            'max_width' => max(100, min(5000, absint($settings['max_width'] ?? 2048))),
            'create_backups' => !empty($settings['create_backups']),
        ];

        $this->settings = $sanitized;
        return update_option(self::OPTION_NAME, $sanitized);
    }

    /**
     * Register admin menu.
     */
    public function register_admin_menu(): void
    {
        add_submenu_page(
            'king-addons',
            esc_html__('Image Optimizer', 'king-addons'),
            esc_html__('Image Optimizer', 'king-addons'),
            'manage_options',
            'king-addons-image-optimizer',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Register settings.
     */
    public function register_settings(): void
    {
        register_setting('king_addons_image_optimizer', self::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);
    }

    /**
     * Sanitize settings callback.
     */
    public function sanitize_settings($input): array
    {
        return [
            'quality' => max(1, min(100, absint($input['quality'] ?? 82))),
            'auto_replace_urls' => !empty($input['auto_replace_urls']),
            'auto_optimize_uploads' => !empty($input['auto_optimize_uploads']),
            'skip_small' => !empty($input['skip_small']),
            'min_size' => absint($input['min_size'] ?? 10240),
            'resize_enabled' => !empty($input['resize_enabled']),
            'max_width' => max(100, min(5000, absint($input['max_width'] ?? 2048))),
            'create_backups' => !empty($input['create_backups']),
        ];
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets(string $hook): void
    {
        $assets_url = KING_ADDONS_URL . 'includes/extensions/Image_Optimizer/assets/';
        $assets_path = KING_ADDONS_PATH . 'includes/extensions/Image_Optimizer/assets/';

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_optimizer_page = ($hook === 'king-addons_page_king-addons-image-optimizer');
        $is_media_library = ($hook === 'upload.php');
        $is_attachment_edit = (
            ($hook === 'post.php' || $hook === 'post-new.php')
            && $screen
            && ($screen->post_type ?? '') === 'attachment'
        );

        $can_use_media = current_user_can('upload_files');

        // Attachment Details / media modal additions
        if ($can_use_media) {
            wp_enqueue_style(
                'king-addons-image-optimizer-attachment-details',
                $assets_url . 'attachment-details.css',
                [],
                file_exists($assets_path . 'attachment-details.css') ? filemtime($assets_path . 'attachment-details.css') : KING_ADDONS_VERSION
            );

            wp_enqueue_script(
                'king-addons-image-optimizer-attachment-details',
                $assets_url . 'attachment-details.js',
                ['jquery'],
                file_exists($assets_path . 'attachment-details.js') ? filemtime($assets_path . 'attachment-details.js') : KING_ADDONS_VERSION,
                true
            );

            wp_localize_script('king-addons-image-optimizer-attachment-details', 'kingImageOptimizerAttachment', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('king_img_optimizer_nonce'),
                'settings' => $this->settings,
                'isPro' => $this->is_pro(),
                'quota' => $this->get_free_quota_state(),
                'upgradeUrl' => $this->get_upgrade_url('kng-img-optimizer'),
                'strings' => [
                    'fetching' => esc_html__('Fetching image data...', 'king-addons'),
                    'converting' => esc_html__('Converting to WebP...', 'king-addons'),
                    'restoring' => esc_html__('Restoring original...', 'king-addons'),
                    'optimized' => esc_html__('Optimized', 'king-addons'),
                    'restored' => esc_html__('Restored', 'king-addons'),
                    'skipped' => esc_html__('Skipped (too small)', 'king-addons'),
                    'doneReload' => esc_html__('Done. Reloading...', 'king-addons'),
                    'confirmRestore' => esc_html__('Restore original and delete optimized files?', 'king-addons'),
                    'errorPrefix' => esc_html__('Error: ', 'king-addons'),
                    'quotaExceeded' => esc_html__('Free plan limit reached (200 optimizations/month). Upgrade to Unlimited to continue.', 'king-addons'),
                ],
            ]);
        }

        // Only load the full optimizer UI assets on our admin page
        if (!$is_optimizer_page) {
            return;
        }

        // Admin styles
        wp_enqueue_style(
            'king-addons-image-optimizer',
            $assets_url . 'admin.css',
            [],
            file_exists($assets_path . 'admin.css') ? filemtime($assets_path . 'admin.css') : KING_ADDONS_VERSION
        );

        // Shared V3 styles
        wp_enqueue_style(
            'king-addons-admin-v3',
            KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css',
            [],
            KING_ADDONS_VERSION
        );

        // Optimizer script
        wp_enqueue_script(
            'king-addons-image-optimizer',
            $assets_url . 'optimizer.js',
            ['jquery'],
            file_exists($assets_path . 'optimizer.js') ? filemtime($assets_path . 'optimizer.js') : KING_ADDONS_VERSION,
            true
        );

        // Bulk optimizer script
        wp_enqueue_script(
            'king-addons-bulk-optimizer',
            $assets_url . 'bulk-optimizer.js',
            ['jquery', 'king-addons-image-optimizer'],
            file_exists($assets_path . 'bulk-optimizer.js') ? filemtime($assets_path . 'bulk-optimizer.js') : KING_ADDONS_VERSION,
            true
        );

        // Localize script
        wp_localize_script('king-addons-image-optimizer', 'kingImageOptimizer', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('king_img_optimizer_nonce'),
            'settings' => $this->settings,
            'isPro' => $this->is_pro(),
            'quota' => $this->get_free_quota_state(),
            'upgradeUrl' => $this->get_upgrade_url('kng-img-optimizer'),
            'strings' => [
                'optimizing' => esc_html__('Optimizing...', 'king-addons'),
                'optimized' => esc_html__('Optimized', 'king-addons'),
                'error' => esc_html__('Error', 'king-addons'),
                'pending' => esc_html__('Pending', 'king-addons'),
                'skipped' => esc_html__('Skipped', 'king-addons'),
                'confirmRestore' => esc_html__('Are you sure you want to restore the original image?', 'king-addons'),
                'confirmBulkRestore' => esc_html__('Are you sure you want to restore all images to their original versions?', 'king-addons'),
                'leaveWarning' => esc_html__('A process is running. If you leave this page, it will be interrupted. Do you want to leave?', 'king-addons'),
                'processingImage' => esc_html__('Processing image %d of %d...', 'king-addons'),
                'completed' => esc_html__('Optimization completed!', 'king-addons'),
                'savedBytes' => esc_html__('Saved %s', 'king-addons'),
                'quotaExceeded' => esc_html__('Free plan limit reached (200 optimizations/month). Upgrade to Unlimited to continue.', 'king-addons'),
            ],
        ]);
    }

    /**
     * Render admin page.
     */
    public function render_admin_page(): void
    {
        include __DIR__ . '/templates/admin-page.php';
    }

    /**
     * Add media library column.
     */
    public function add_media_column(array $columns): array
    {
        $columns['king_optimization'] = esc_html__('Optimization', 'king-addons');
        return $columns;
    }

    /**
     * Render media library column.
     */
    public function render_media_column(string $column_name, int $attachment_id): void
    {
        if ($column_name !== 'king_optimization') {
            return;
        }

        if (!wp_attachment_is_image($attachment_id)) {
            echo '<span class="king-opt-na">—</span>';
            return;
        }

        $meta = Image_Optimizer_DB::get_optimization_meta($attachment_id);

        if (empty($meta) || $meta['status'] === 'pending') {
            echo '<span class="king-opt-pending"><span class="dashicons dashicons-clock" aria-hidden="true"></span> ' . esc_html__('Pending', 'king-addons') . '</span>';
        } elseif ($meta['status'] === 'optimized') {
            $savings = $meta['total_saved_bytes'] ?? 0;
            $percent = $meta['savings_percent'] ?? 0;
            echo '<span class="king-opt-done"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> ' . esc_html(sprintf(__('-%d%%', 'king-addons'), $percent)) . '</span>';
        } elseif ($meta['status'] === 'failed') {
            echo '<span class="king-opt-failed"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span> ' . esc_html__('Failed', 'king-addons') . '</span>';
        }
    }

    /**
     * Add attachment metabox.
     */
    public function add_attachment_metabox(): void
    {
        add_meta_box(
            'king_image_optimization',
            esc_html__('Image Optimization', 'king-addons'),
            [$this, 'render_attachment_metabox'],
            'attachment',
            'side',
            'default'
        );
    }

    /**
     * Render attachment metabox.
     */
    public function render_attachment_metabox(\WP_Post $post): void
    {
        if (!wp_attachment_is_image($post->ID)) {
            echo '<p>' . esc_html__('This file type is not supported for optimization.', 'king-addons') . '</p>';
            return;
        }

        $meta = Image_Optimizer_DB::get_optimization_meta($post->ID);
        $file = get_attached_file($post->ID);
        $file_size = $file ? filesize($file) : 0;
        
        include __DIR__ . '/templates/attachment-metabox.php';
    }

    /**
     * Cleanup WebP files when attachment is deleted.
     */
    public function cleanup_on_delete(int $attachment_id): void
    {
        $meta = Image_Optimizer_DB::get_optimization_meta($attachment_id);
        
        if (empty($meta) || empty($meta['sizes'])) {
            return;
        }

        foreach ($meta['sizes'] as $size => $data) {
            if (!empty($data['webp_path']) && file_exists($data['webp_path'])) {
                wp_delete_file($data['webp_path']);
            }
        }

        Image_Optimizer_DB::delete_optimization_meta($attachment_id);
    }

    /**
     * Get global statistics.
     */
    public function get_global_stats(): array
    {
        global $wpdb;

        $stats = [
            'total_images' => 0,
            'optimized_images' => 0,
            'skipped_images' => 0,
            'failed_images' => 0,
            'pending_images' => 0,
            'total_original_bytes' => 0,
            'total_optimized_bytes' => 0,
            'total_saved_bytes' => 0,
        ];

        // Count total images
        $stats['total_images'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'"
        );

        // Get optimization stats from postmeta
        $optimized_data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = %s AND p.post_type = 'attachment'",
                self::META_KEY
            )
        );

        // Opportunistic migration: sync a small batch to Media Library per request
        $sync_limit = 200;
        $synced = 0;

        foreach ($optimized_data as $row) {
            $attachment_id = (int) ($row->post_id ?? 0);
            $meta = maybe_unserialize($row->meta_value);
            if (!is_array($meta)) {
                continue;
            }

            if (($meta['status'] ?? '') === 'optimized') {
                $stats['optimized_images']++;
                $stats['total_original_bytes'] += $meta['total_original_bytes'] ?? 0;
                $stats['total_optimized_bytes'] += $meta['total_optimized_bytes'] ?? 0;
                $stats['total_saved_bytes'] += $meta['total_saved_bytes'] ?? 0;

                // If Media Library still points to original, sync it (limited per call)
                if ($synced < $sync_limit && $attachment_id > 0) {
                    $opt_full = $meta['sizes']['full']['optimized_path'] ?? $meta['sizes']['full']['webp_path'] ?? '';
                    if (!empty($opt_full) && is_string($opt_full) && file_exists($opt_full)) {
                        $current = get_attached_file($attachment_id);
                        if (!empty($current) && $current !== $opt_full) {
                            Image_Optimizer_DB::sync_attachment_to_optimized($attachment_id, $meta);
                            $synced++;
                        }
                    }
                }
            } elseif (($meta['status'] ?? '') === 'skipped') {
                $stats['skipped_images']++;
            } elseif (($meta['status'] ?? '') === 'failed') {
                $stats['failed_images']++;
            }
        }

        if ($synced > 0) {
            $stats['synced_media_library'] = $synced;
        }

        // Pending = Total - Optimized - Skipped - Failed
        $stats['pending_images'] = max(0, $stats['total_images'] - $stats['optimized_images'] - $stats['skipped_images'] - $stats['failed_images']);

        return $stats;
    }

    /**
     * Get supported image types.
     */
    public function get_supported_types(): array
    {
        $types = [
            'image/jpeg' => ['ext' => 'jpg', 'label' => 'JPEG'],
            'image/png' => ['ext' => 'png', 'label' => 'PNG'],
            'image/webp' => ['ext' => 'webp', 'label' => 'WebP'],
        ];
        return $types;
    }

    /**
     * Check if image type is supported.
     */
    public function is_supported_type(string $mime_type): bool
    {
        return array_key_exists($mime_type, $this->get_supported_types());
    }

    /**
     * Format bytes to human readable.
     */
    public static function format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

// Initialize the extension
add_action('init', function() {
    Image_Optimizer::instance();
});
