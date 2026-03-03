<?php
/**
 * Fomo Notifications Extension
 *
 * Social proof and FOMO notifications system for WordPress.
 * Creates notification bars, sales popups, review alerts and more.
 *
 * @package King_Addons
 * @since 1.0.0
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Fomo_Notifications
 *
 * Main class for the Fomo Notifications extension.
 * Handles CPT registration, admin pages, frontend rendering and analytics.
 *
 * @since 1.0.0
 */
final class Fomo_Notifications
{
    /**
     * Extension version
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Custom post type name
     *
     * @var string
     */
    const POST_TYPE = 'kng_fomo_notif';

    /**
     * Stats table name (without prefix)
     *
     * @var string
     */
    const STATS_TABLE = 'kng_fomo_stats';

    /**
     * Free version notification limit
     *
     * @var int
     */
    const FREE_LIMIT = 3;

    /**
     * Singleton instance
     *
     * @var Fomo_Notifications|null
     */
    private static ?Fomo_Notifications $instance = null;

    /**
     * Extension directory path
     *
     * @var string
     */
    private string $dir;

    /**
     * Extension directory URL
     *
     * @var string
     */
    private string $url;

    /**
     * Get singleton instance
     *
     * @return Fomo_Notifications
     */
    public static function instance(): Fomo_Notifications
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->dir = plugin_dir_path(__FILE__);
        $this->url = plugin_dir_url(__FILE__);

        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     *
     * @return void
     */
    private function init_hooks(): void
    {
        // Core initialization
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_post_meta']);

        // Admin
        add_action('admin_menu', [$this, 'register_admin_menu'], 99);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX handlers
        add_action('wp_ajax_kng_fomo_save_notification', [$this, 'ajax_save_notification']);
        add_action('wp_ajax_kng_fomo_get_notification', [$this, 'ajax_get_notification']);
        add_action('wp_ajax_kng_fomo_delete_notification', [$this, 'ajax_delete_notification']);
        add_action('wp_ajax_kng_fomo_toggle_status', [$this, 'ajax_toggle_status']);
        add_action('wp_ajax_kng_fomo_duplicate_notification', [$this, 'ajax_duplicate_notification']);
        add_action('wp_ajax_kng_fomo_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_kng_fomo_get_analytics', [$this, 'ajax_get_analytics']);
        add_action('wp_ajax_kng_fomo_export_notification', [$this, 'ajax_export_notification']);
        add_action('wp_ajax_kng_fomo_import_notification', [$this, 'ajax_import_notification']);
        add_action('wp_ajax_kng_fomo_fetch_wporg_data', [$this, 'ajax_fetch_wporg_data']);
        add_action('wp_ajax_kng_fomo_purge_cache', [$this, 'ajax_purge_cache']);

        // Frontend
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('wp_footer', [$this, 'render_notifications']);

        // Tracking endpoints (public)
        add_action('wp_ajax_kng_fomo_track_event', [$this, 'ajax_track_event']);
        add_action('wp_ajax_nopriv_kng_fomo_track_event', [$this, 'ajax_track_event']);

        // Database setup on init (activation hook won't work here)
        add_action('admin_init', [$this, 'maybe_create_stats_table']);
    }

    /**
     * Register custom post type for notifications
     *
     * @return void
     */
    public function register_post_type(): void
    {
        $labels = [
            'name' => __('Notifications', 'king-addons'),
            'singular_name' => __('Notification', 'king-addons'),
            'add_new' => __('Add New', 'king-addons'),
            'add_new_item' => __('Add New Notification', 'king-addons'),
            'edit_item' => __('Edit Notification', 'king-addons'),
            'new_item' => __('New Notification', 'king-addons'),
            'view_item' => __('View Notification', 'king-addons'),
            'search_items' => __('Search Notifications', 'king-addons'),
            'not_found' => __('No notifications found', 'king-addons'),
            'not_found_in_trash' => __('No notifications found in Trash', 'king-addons'),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'supports' => ['title'],
            'show_in_rest' => false,
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Register post meta fields
     *
     * @return void
     */
    public function register_post_meta(): void
    {
        // Simple string fields
        $string_fields = [
            '_kng_fomo_status',
            '_kng_fomo_type',
            '_kng_fomo_source',
        ];

        foreach ($string_fields as $meta_key) {
            register_post_meta(self::POST_TYPE, $meta_key, [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ]);
        }

        // JSON fields — sanitize_text_field would break JSON, so use a custom callback
        $json_fields = [
            '_kng_fomo_source_config',
            '_kng_fomo_design',
            '_kng_fomo_content',
            '_kng_fomo_display',
            '_kng_fomo_customize',
        ];

        foreach ($json_fields as $meta_key) {
            register_post_meta(self::POST_TYPE, $meta_key, [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => [$this, 'sanitize_json_meta'],
            ]);
        }

        // Numeric fields
        $numeric_fields = [
            '_kng_fomo_views',
            '_kng_fomo_clicks',
        ];

        foreach ($numeric_fields as $meta_key) {
            register_post_meta(self::POST_TYPE, $meta_key, [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => 'absint',
            ]);
        }
    }

    /**
     * Sanitize JSON meta value
     *
     * @param mixed $value The value to sanitize
     * @return string
     */
    public function sanitize_json_meta($value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return wp_json_encode($decoded);
            }
        }
        if (is_array($value)) {
            return wp_json_encode($value);
        }
        return '{}';
    }

    /**
     * Maybe create stats table (runs once)
     *
     * @return void
     */
    public function maybe_create_stats_table(): void
    {
        $version_key = 'kng_fomo_db_version';
        $current_version = '1.0';
        
        if (get_option($version_key) !== $current_version) {
            $this->create_stats_table();
            update_option($version_key, $current_version);
        }
    }

    /**
     * Create stats table on activation
     *
     * @return void
     */
    public function create_stats_table(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::STATS_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            notification_id bigint(20) NOT NULL,
            stat_date date NOT NULL,
            views int(11) NOT NULL DEFAULT 0,
            clicks int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY notification_date (notification_id, stat_date),
            KEY notification_id (notification_id),
            KEY stat_date (stat_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Register admin menu pages
     *
     * @return void
     */
    public function register_admin_menu(): void
    {
        // Main page (Dashboard)
        add_submenu_page(
            'king-addons',
            __('Fomo Notifications', 'king-addons'),
            __('Fomo Notifications', 'king-addons'),
            'manage_options',
            'king-addons-fomo',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_assets(string $hook): void
    {
        // Check if we're on the Fomo Notifications admin page
        if (strpos($hook, 'king-addons-fomo') === false && 
            (!isset($_GET['page']) || $_GET['page'] !== 'king-addons-fomo')) {
            return;
        }

        // Chart.js for analytics
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            [],
            '4.4.1',
            true
        );

        // Admin CSS
        wp_enqueue_style(
            'kng-fomo-admin',
            $this->url . 'assets/admin.css',
            [],
            self::VERSION
        );

        // Admin JS
        wp_enqueue_script(
            'kng-fomo-admin',
            $this->url . 'assets/admin.js',
            ['jquery', 'wp-util'],
            self::VERSION,
            true
        );

        wp_localize_script('kng-fomo-admin', 'kngFomoAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kng_fomo_admin'),
            'restUrl' => rest_url('kng-fomo/v1/'),
            'listUrl' => admin_url('admin.php?page=king-addons-fomo&view=list'),
            'hasPro' => self::hasPro(),
            'freeLimit' => self::FREE_LIMIT,
            'upgradeUrl' => 'https://kingaddons.com/pricing/?utm_source=kng-fomo-notifications&utm_medium=plugin',
            'typeDefaults' => [
                'notification_bar' => self::get_type_defaults('notification_bar'),
                'woocommerce_sales' => self::get_type_defaults('woocommerce_sales'),
                'wordpress_comments' => self::get_type_defaults('wordpress_comments'),
                'wporg_downloads' => self::get_type_defaults('wporg_downloads'),
                'reviews' => self::get_type_defaults('reviews'),
                'email_subscription' => self::get_type_defaults('email_subscription'),
                'donations' => self::get_type_defaults('donations'),
                'flashing_tab' => self::get_type_defaults('flashing_tab'),
                'custom_csv' => self::get_type_defaults('custom_csv'),
            ],
            'i18n' => [
                'confirmDelete' => __('Are you sure you want to delete this notification?', 'king-addons'),
                'saved' => __('Notification saved successfully!', 'king-addons'),
                'deleted' => __('Notification deleted.', 'king-addons'),
                'duplicated' => __('Notification duplicated.', 'king-addons'),
                'error' => __('An error occurred. Please try again.', 'king-addons'),
                'limitReached' => __('Free version limit reached. Upgrade to Pro for unlimited notifications.', 'king-addons'),
                'proFeature' => __('This feature is available in Pro version.', 'king-addons'),
                'select_type' => __('Please select a notification type.', 'king-addons'),
                'select_template' => __('Please select a template.', 'king-addons'),
                'add_content' => __('Please add a title or message.', 'king-addons'),
                'settings_saved' => __('Settings saved successfully!', 'king-addons'),
                'exported' => __('Notification exported successfully!', 'king-addons'),
            ],
        ]);
    }

    /**
     * Enqueue frontend assets
     *
     * @return void
     */
    public function enqueue_frontend_assets(): void
    {
        // Check if there are active notifications for current page
        $notifications = $this->get_active_notifications_for_page();
        if (empty($notifications)) {
            return;
        }

        wp_enqueue_style(
            'kng-fomo-frontend',
            $this->url . 'assets/frontend.css',
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'kng-fomo-frontend',
            $this->url . 'assets/frontend.js',
            [],
            self::VERSION,
            true
        );

        // Prepare notifications data for JS
        $notifications_data = [];
        $settings = $this->get_settings();
        foreach ($notifications as $notification) {
            $notifications_data[] = $this->prepare_notification_for_frontend($notification);
        }

        wp_localize_script('kng-fomo-frontend', 'kngFomoData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kng_fomo_frontend'),
            'notifications' => $notifications_data,
            'settings' => [
                'position' => 'bottom-left',
                'delayBefore' => 5,
                'displayFor' => 5,
                'delayBetween' => 5,
                'sessionLimit' => 0,
                'soundVolume' => (int)($settings['sound_volume'] ?? 50),
                'loop' => true,
            ],
        ]);
    }

    /**
     * Render admin page based on view parameter
     *
     * @return void
     */
    public function render_admin_page(): void
    {
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'dashboard';
        $template_map = [
            'dashboard' => 'admin-dashboard.php',
            'list' => 'admin-list.php',
            'new' => 'admin-wizard.php',
            'wizard' => 'admin-wizard.php',
            'edit' => 'admin-wizard.php',
            'settings' => 'admin-settings.php',
            'analytics' => 'admin-analytics.php',
        ];

        $template = $template_map[$view] ?? 'admin-dashboard.php';
        $template_path = $this->dir . 'templates/' . $template;

        if (file_exists($template_path)) {
            // Prepare variables for templates
            $has_pro = self::hasPro();
            $notifications = $this->get_all_notifications();
            $notification_count = count($notifications);
            $at_limit = !$has_pro && $notification_count >= self::FREE_LIMIT;
            $settings = $this->get_settings();
            $notification = null;
            $is_edit = ($view === 'edit' || ($view === 'wizard' && isset($_GET['edit'])));

            if ($is_edit && isset($_GET['edit'])) {
                $notification = $this->get_notification((int)$_GET['edit']);
            }

            include $template_path;
        }
    }

    /**
     * Check if user has Pro version
     *
     * @return bool
     */
    public static function hasPro(): bool
    {
        return function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code();
    }

    /**
     * Get all notifications
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_all_notifications(array $args = []): array
    {
        $defaults = [
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'any',
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);
        $posts = get_posts($args);

        $notifications = [];
        foreach ($posts as $post) {
            $notifications[] = $this->format_notification($post);
        }

        return $notifications;
    }

    /**
     * Get single notification by ID
     *
     * @param int $id Notification ID
     * @return array|null
     */
    public function get_notification(int $id): ?array
    {
        $post = get_post($id);
        if (!$post || $post->post_type !== self::POST_TYPE) {
            return null;
        }

        return $this->format_notification($post);
    }

    /**
     * Format notification post to array
     *
     * @param \WP_Post $post Post object
     * @return array
     */
    private function format_notification(\WP_Post $post): array
    {
        $views = (int)get_post_meta($post->ID, '_kng_fomo_views', true);
        $clicks = (int)get_post_meta($post->ID, '_kng_fomo_clicks', true);
        $ctr = $views > 0 ? round(($clicks / $views) * 100, 2) : 0;

        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'status' => get_post_meta($post->ID, '_kng_fomo_status', true) ?: 'disabled',
            'type' => get_post_meta($post->ID, '_kng_fomo_type', true) ?: 'notification_bar',
            'source' => get_post_meta($post->ID, '_kng_fomo_source', true) ?: 'manual',
            'source_config' => json_decode(get_post_meta($post->ID, '_kng_fomo_source_config', true) ?: '{}', true),
            'design' => json_decode(get_post_meta($post->ID, '_kng_fomo_design', true) ?: '{}', true),
            'content' => json_decode(get_post_meta($post->ID, '_kng_fomo_content', true) ?: '{}', true),
            'display' => json_decode(get_post_meta($post->ID, '_kng_fomo_display', true) ?: '{}', true),
            'customize' => json_decode(get_post_meta($post->ID, '_kng_fomo_customize', true) ?: '{}', true),
            'views' => $views,
            'clicks' => $clicks,
            'ctr' => $ctr,
            'date' => $post->post_date,
            'date_formatted' => get_the_date('M j, Y', $post),
        ];
    }

    /**
     * Get extension settings
     *
     * @return array
     */
    public function get_settings(): array
    {
        $defaults = [
            'enabled' => true,
            'tracking_enabled' => true,
            'track_for' => 'everyone',
            'exclude_bots' => true,
            'cache_ttl' => 300,
            'anonymize_names' => false,
            'sound_volume' => 50,
            'modules' => [
                'notification_bar' => true,
                'woocommerce_sales' => true,
                'wordpress_comments' => true,
                'wporg_downloads' => true,
                'reviews' => false,
                'email_subscription' => false,
                'donations' => false,
                'flashing_tab' => false,
                'custom_csv' => false,
            ],
        ];

        $saved = get_option('kng_fomo_settings', []);
        return wp_parse_args($saved, $defaults);
    }

    /**
     * Save extension settings
     *
     * @param array $settings Settings to save
     * @return bool
     */
    public function save_settings(array $settings): bool
    {
        return update_option('kng_fomo_settings', $settings);
    }

    /**
     * Get active notifications that should display on current page
     *
     * @return array
     */
    public function get_active_notifications_for_page(): array
    {
        $all = $this->get_all_notifications([
            'meta_key' => '_kng_fomo_status',
            'meta_value' => 'enabled',
        ]);

        $active = [];
        foreach ($all as $notification) {
            if ($this->should_display_notification($notification)) {
                $active[] = $notification;
            }
        }

        return $active;
    }

    /**
     * Check if notification should display on current page
     *
     * @param array $notification Notification data
     * @return bool
     */
    private function should_display_notification(array $notification): bool
    {
        $display = $notification['display'];

        // Check device visibility
        $customize = $notification['customize'];
        $visibility = $customize['visibility'] ?? ['desktop' => true, 'tablet' => true, 'mobile' => true];
        if (!$this->check_device_visibility($visibility)) {
            return false;
        }

        // Legacy show_on rules
        $show_on = $display['show_on'] ?? 'everywhere';
        if ($show_on === 'include' && !empty($display['include_pages'])) {
            if (!$this->is_current_page_in_list($display['include_pages'])) {
                return false;
            }
        } elseif ($show_on === 'exclude' && !empty($display['exclude_pages'])) {
            if ($this->is_current_page_in_list($display['exclude_pages'])) {
                return false;
            }
        }

        // Wizard page rules: pages + page_rules
        $pages_mode = $display['pages'] ?? 'all';
        if ($pages_mode === 'specific') {
            $page_rules = $display['page_rules'] ?? [];
            if (is_array($page_rules) && !empty($page_rules)) {
                $has_include = false;
                $include_matched = false;

                foreach ($page_rules as $rule) {
                    if (!is_array($rule)) {
                        continue;
                    }

                    $type = sanitize_text_field((string)($rule['type'] ?? 'include'));
                    $matched = $this->match_page_rule($rule);

                    if ($type === 'exclude' && $matched) {
                        return false;
                    }

                    if ($type === 'include') {
                        $has_include = true;
                        if ($matched) {
                            $include_matched = true;
                        }
                    }
                }

                if ($has_include && !$include_matched) {
                    return false;
                }
            }
        }

        // Check display_for / audience (supports both legacy and wizard keys)
        $display_for = $display['display_for'] ?? ($display['audience'] ?? 'everyone');

        if (($display_for === 'guests' || $display_for === 'logged_out') && is_user_logged_in()) {
            return false;
        }

        if ($display_for === 'logged_in' && !is_user_logged_in()) {
            return false;
        }

        return true;
    }

    /**
     * Match a single wizard page rule against current request
     *
     * @param array $rule Page rule
     * @return bool
     */
    private function match_page_rule(array $rule): bool
    {
        $condition = sanitize_text_field((string)($rule['condition'] ?? 'url_contains'));
        $value = trim((string)($rule['value'] ?? ''));

        if ($value === '') {
            return false;
        }

        if ($condition === 'page' || $condition === 'post') {
            if (is_numeric($value)) {
                return ((int)get_queried_object_id()) === (int)$value;
            }
            return false;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string)$_SERVER['REQUEST_URI']) : '';
        $current_url = strtolower(home_url($request_uri));
        $needle = strtolower($value);

        if ($condition === 'url_is') {
            // Exact full URL
            if ($current_url === $needle) {
                return true;
            }

            // Exact path (with or without leading slash)
            $current_path = strtolower((string)wp_parse_url($current_url, PHP_URL_PATH));
            $needle_path = strtolower((string)wp_parse_url($needle, PHP_URL_PATH));
            if ($needle_path === '' && strpos($needle, '/') === false) {
                $needle_path = '/' . ltrim($needle, '/');
            }
            return $needle_path !== '' && $current_path === $needle_path;
        }

        // Default: url_contains
        return strpos($current_url, $needle) !== false;
    }

    /**
     * Check device visibility based on screen width
     *
     * @param array $visibility Visibility settings
     * @return bool
     */
    private function check_device_visibility(array $visibility): bool
    {
        // Server-side we can't detect device, so return true and let JS handle it
        return true;
    }

    /**
     * Check if current page is in the given list
     *
     * @param array $page_list List of page IDs
     * @return bool
     */
    private function is_current_page_in_list(array $page_list): bool
    {
        $current_id = get_queried_object_id();
        return in_array($current_id, $page_list, true);
    }

    /**
     * Render notifications on frontend
     *
     * @return void
     */
    public function render_notifications(): void
    {
        // Notifications data is passed via wp_localize_script in enqueue_frontend_assets().
        // This method only outputs the container div for popup notifications.
        $notifications = $this->get_active_notifications_for_page();
        if (empty($notifications)) {
            return;
        }

        echo '<div id="kng-fomo-container"></div>';
    }

    /**
     * Prepare notification data for frontend rendering
     *
     * @param array $notification Notification data
     * @return array
     */
    private function prepare_notification_for_frontend(array $notification): array
    {
        $content = $this->get_notification_content($notification);
        $design = $notification['design'];
        $customize = $notification['customize'];
        $display = $notification['display'];

        // Ensure loop is always set (default true for cycling)
        if (!isset($display['loop'])) {
            $display['loop'] = true;
        }

        return [
            'id' => $notification['id'],
            'type' => $notification['type'],
            'design' => $design,
            'content' => $content,
            'customize' => $customize,
            'display' => $display,
            // Flat fields for frontend.js convenience
            'title' => $content['title'] ?? '',
            'message' => $content['message'] ?? '',
            'image' => $content['image'] ?? '',
            'image_style' => $content['image_type'] ?? 'product',
            'time_text' => $content['time_text'] ?? '',
            'cta_text' => $content['cta_text'] ?? '',
            'cta_url' => $content['cta_url'] ?? '',
            'click_url' => ($customize['click_action'] ?? 'link') === 'link' ? ($content['cta_url'] ?? '') : '',
            'click_target' => '_self',
            'bg_color' => $design['bg_color'] ?? '#ffffff',
            'text_color' => $design['text_color'] ?? '#1d1d1f',
            'accent_color' => $design['accent_color'] ?? '#0071e3',
            'animation' => $design['animation'] ?? 'slide',
            'display_time' => (($display['duration'] ?? 5) * 1000),
            'sound' => !empty($customize['sound']) ? 'pop' : false,
            'device' => 'all',
            'bar_position' => $design['position'] ?? 'top',
            'page_rules' => $display['page_rules'] ?? null,
            // Items for dynamic notifications (WooCommerce, comments, etc.)
            'items' => $content['items'] ?? [],
        ];
    }

    /**
     * Get default content templates and source config for a notification type.
     *
     * These are used when the user hasn't provided custom templates, and as
     * fallback data when real sources return empty.
     *
     * @param string $type Notification type
     * @return array { content_defaults: array, source_config_defaults: array, fallback_items: array }
     */
    public static function get_type_defaults(string $type): array
    {
        $defaults = [
            'notification_bar' => [
                'content_defaults' => [
                    'title' => '',
                    'message' => '',
                ],
                'source_config_defaults' => [],
                'fallback_items' => [],
            ],
            'woocommerce_sales' => [
                'content_defaults' => [
                    'title' => '{{name}}',
                    'message' => 'just purchased {{product}}',
                ],
                'source_config_defaults' => [
                    'order_status' => 'any',
                    'time_range' => '7d',
                    'limit' => 10,
                ],
                'fallback_items' => [
                    [
                        'username' => 'Sarah',
                        'product' => 'Premium Bundle',
                        'product_url' => '#',
                        'product_image' => '',
                        'location' => 'New York, US',
                        'time' => time() - 180,
                        'time_ago' => '3 mins ago',
                    ],
                    [
                        'username' => 'Michael',
                        'product' => 'Starter Pack',
                        'product_url' => '#',
                        'product_image' => '',
                        'location' => 'London, UK',
                        'time' => time() - 720,
                        'time_ago' => '12 mins ago',
                    ],
                    [
                        'username' => 'Emma',
                        'product' => 'Annual Plan',
                        'product_url' => '#',
                        'product_image' => '',
                        'location' => 'Toronto, CA',
                        'time' => time() - 1800,
                        'time_ago' => '30 mins ago',
                    ],
                    [
                        'username' => 'James',
                        'product' => 'Pro License',
                        'product_url' => '#',
                        'product_image' => '',
                        'location' => 'Sydney, AU',
                        'time' => time() - 3600,
                        'time_ago' => '1 hour ago',
                    ],
                    [
                        'username' => 'Lisa',
                        'product' => 'Business Suite',
                        'product_url' => '#',
                        'product_image' => '',
                        'location' => 'Berlin, DE',
                        'time' => time() - 7200,
                        'time_ago' => '2 hours ago',
                    ],
                ],
            ],
            'wordpress_comments' => [
                'content_defaults' => [
                    'title' => '{{name}}',
                    'message' => 'commented on {{product}}',
                ],
                'source_config_defaults' => [
                    'post_types' => ['post'],
                    'comments_count' => 10,
                ],
                'fallback_items' => [
                    [
                        'username' => 'Alex',
                        'content' => 'Great article! Very helpful.',
                        'post_title' => 'Getting Started Guide',
                        'post_url' => '#',
                        'avatar' => '',
                        'time' => time() - 300,
                        'time_ago' => '5 mins ago',
                    ],
                    [
                        'username' => 'Maria',
                        'content' => 'Thanks for sharing this!',
                        'post_title' => 'Tips & Tricks',
                        'post_url' => '#',
                        'avatar' => '',
                        'time' => time() - 900,
                        'time_ago' => '15 mins ago',
                    ],
                    [
                        'username' => 'David',
                        'content' => 'Exactly what I was looking for.',
                        'post_title' => 'Complete Tutorial',
                        'post_url' => '#',
                        'avatar' => '',
                        'time' => time() - 2700,
                        'time_ago' => '45 mins ago',
                    ],
                ],
            ],
            'wporg_downloads' => [
                'content_defaults' => [
                    'title' => '{{name}}',
                    'message' => '{{active_installs}} active installs',
                ],
                'source_config_defaults' => [
                    'wporg_slug' => '',
                    'wporg_type' => 'plugin',
                    'data_type' => 'downloads',
                ],
                'fallback_items' => [],
            ],
            'reviews' => [
                'content_defaults' => [
                    'title' => '{{name}}',
                    'message' => 'left a review on {{product}}',
                ],
                'source_config_defaults' => [],
                'fallback_items' => [
                    [
                        'username' => 'John',
                        'product' => 'Premium Plugin',
                        'post_title' => 'Premium Plugin',
                        'product_url' => '#',
                        'avatar' => '',
                        'time' => time() - 600,
                        'time_ago' => '10 mins ago',
                    ],
                ],
            ],
            'email_subscription' => [
                'content_defaults' => [
                    'title' => '{{name}}',
                    'message' => 'just subscribed to the newsletter',
                ],
                'source_config_defaults' => [],
                'fallback_items' => [
                    [
                        'username' => 'Subscriber',
                        'email' => 'user@example.com',
                        'avatar' => '',
                        'time' => time() - 120,
                        'time_ago' => '2 mins ago',
                    ],
                ],
            ],
            'donations' => [
                'content_defaults' => [
                    'title' => '{{name}}',
                    'message' => 'just donated',
                ],
                'source_config_defaults' => [],
                'fallback_items' => [
                    [
                        'username' => 'Donor',
                        'product' => '$25',
                        'time' => time() - 240,
                        'time_ago' => '4 mins ago',
                    ],
                ],
            ],
            'custom_csv' => [
                'content_defaults' => [
                    'title' => '{{name}}',
                    'message' => '{{content}}',
                ],
                'source_config_defaults' => [],
                'fallback_items' => [],
            ],
        ];

        return $defaults[$type] ?? [
            'content_defaults' => ['title' => '', 'message' => ''],
            'source_config_defaults' => [],
            'fallback_items' => [],
        ];
    }

    /**
     * Get notification content with dynamic data
     *
     * @param array $notification Notification data
     * @return array
     */
    private function get_notification_content(array $notification): array
    {
        $content = $notification['content'];
        $source = $notification['source'];
        $type = $notification['type'];
        $source_config = $notification['source_config'];

        // Overlay type-specific content defaults when user hasn't provided templates.
        $type_defaults = self::get_type_defaults($type);
        $cd = $type_defaults['content_defaults'];

        // If title doesn't contain a {{placeholder}}, overlay the type default
        if (!empty($cd['title']) && (empty($content['title']) || strpos($content['title'], '{{') === false)) {
            $content['title'] = $cd['title'];
        }
        if (!empty($cd['message']) && (empty($content['message']) || strpos($content['message'], '{{') === false)) {
            $content['message'] = $cd['message'];
        }

        // For dynamic sources, fetch real data.
        // The wizard sets source = type, so we match both legacy and current values.
        $items = [];
        if ($source === 'woocommerce' || $source === 'woocommerce_sales' || $type === 'woocommerce_sales') {
            $items = $this->get_woocommerce_data($source_config);
        } elseif ($source === 'comments' || $source === 'wordpress_comments' || $type === 'wordpress_comments') {
            $items = $this->get_comments_data($source_config);
        } elseif ($source === 'wporg' || $source === 'wporg_downloads' || $type === 'wporg_downloads') {
            $items = $this->get_wporg_data($source_config);
        }

        // Fallback: use demo/sample items when real source returned nothing
        if (empty($items) && !empty($type_defaults['fallback_items'])) {
            $items = $type_defaults['fallback_items'];
            // Mark as demo data so frontend can optionally indicate it
            foreach ($items as &$item) {
                $item['_demo'] = true;
            }
            unset($item);
        }

        $content['items'] = $items;

        return $content;
    }

    /**
     * Get WooCommerce recent orders data
     *
     * @param array $config Source configuration
     * @return array
     */
    private function get_woocommerce_data(array $config): array
    {
        if (!class_exists('WooCommerce')) {
            return [];
        }

        $limit = (int)($config['limit'] ?? 10);

        $days = isset($config['days']) ? (int)$config['days'] : 0;
        if ($days <= 0 && !empty($config['time_range'])) {
            $time_range = sanitize_text_field((string)$config['time_range']);
            $days_map = [
                '24h' => 1,
                '7d' => 7,
                '30d' => 30,
            ];
            $days = $days_map[$time_range] ?? 7;
        }
        if ($days <= 0) {
            $days = 7;
        }

        $order_status = sanitize_text_field((string)($config['order_status'] ?? 'any'));
        $status = ['wc-completed', 'wc-processing'];
        if ($order_status === 'completed') {
            $status = ['wc-completed'];
        } elseif ($order_status === 'processing') {
            $status = ['wc-processing'];
        }

        $args = [
            'limit' => $limit,
            'status' => $status,
            'date_created' => '>' . (time() - ($days * DAY_IN_SECONDS)),
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $orders = wc_get_orders($args);
        $items = [];

        foreach ($orders as $order) {
            $customer_name = $order->get_billing_first_name();
            if (empty($customer_name)) {
                $customer_name = __('Someone', 'king-addons');
            }

            $products = $order->get_items();
            $product = reset($products);
            if ($product) {
                $product_obj = $product->get_product();
                $items[] = [
                    'username' => $customer_name,
                    'product' => $product->get_name(),
                    'product_url' => $product_obj ? get_permalink($product_obj->get_id()) : '',
                    'product_image' => $product_obj ? wp_get_attachment_url($product_obj->get_image_id()) : '',
                    'location' => $order->get_billing_city() . ', ' . $order->get_billing_country(),
                    'time' => $order->get_date_created()->getTimestamp(),
                    'time_ago' => human_time_diff($order->get_date_created()->getTimestamp(), time()),
                ];
            }
        }

        return $items;
    }

    /**
     * Get WordPress comments data
     *
     * @param array $config Source configuration
     * @return array
     */
    private function get_comments_data(array $config): array
    {
        $limit = (int)($config['limit'] ?? ($config['comments_count'] ?? 10));
        if ($limit <= 0) {
            $limit = 10;
        }
        $post_scope = $config['post_scope'] ?? 'all';
        $post_types = $config['post_types'] ?? [];
        if (!is_array($post_types)) {
            $post_types = [$post_types];
        }
        $post_types = array_values(array_filter(array_map('sanitize_text_field', $post_types)));

        $args = [
            'number' => $limit,
            'status' => 'approve',
            'orderby' => 'comment_date',
            'order' => 'DESC',
        ];

        if (!empty($post_types)) {
            $args['post_type'] = $post_types;
        }

        if ($post_scope !== 'all' && is_numeric($post_scope)) {
            $args['post_id'] = (int)$post_scope;
        }

        $comments = get_comments($args);
        $items = [];

        foreach ($comments as $comment) {
            $items[] = [
                'username' => $comment->comment_author,
                'content' => wp_trim_words($comment->comment_content, 15),
                'post_title' => get_the_title($comment->comment_post_ID),
                'post_url' => get_permalink($comment->comment_post_ID),
                'avatar' => get_avatar_url($comment->comment_author_email, ['size' => 64]),
                'time' => strtotime($comment->comment_date),
                'time_ago' => human_time_diff(strtotime($comment->comment_date), time()),
            ];
        }

        return $items;
    }

    /**
     * Get WordPress.org plugin/theme data
     *
     * @param array $config Source configuration
     * @return array
     */
    private function get_wporg_data(array $config): array
    {
        $slug = sanitize_text_field((string)($config['slug'] ?? ($config['wporg_slug'] ?? '')));
        $type = sanitize_text_field((string)($config['product_type'] ?? ($config['wporg_type'] ?? 'plugin')));
        $data_type = sanitize_text_field((string)($config['data_type'] ?? 'downloads'));

        if (empty($slug)) {
            return [];
        }

        $cache_key = 'kng_fomo_wporg_' . md5($slug . $type . $data_type);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $api_url = $type === 'plugin'
            ? 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=' . urlencode($slug)
            : 'https://api.wordpress.org/themes/info/1.2/?action=theme_information&slug=' . urlencode($slug);

        $response = wp_remote_get($api_url, ['timeout' => 10]);
        if (is_wp_error($response)) {
            return [];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data)) {
            return [];
        }

        $items = [];
        if ($data_type === 'downloads') {
            $items[] = [
                'name' => $data['name'] ?? $slug,
                'downloads' => number_format($data['downloaded'] ?? 0),
                'active_installs' => number_format($data['active_installs'] ?? 0),
                'rating' => $data['rating'] ?? 0,
                'num_ratings' => $data['num_ratings'] ?? 0,
            ];
        }

        $cache_ttl = $config['cache_ttl'] ?? 3600;
        set_transient($cache_key, $items, $cache_ttl);

        return $items;
    }

    /**
     * AJAX: Save notification
     *
     * @return void
     */
    public function ajax_save_notification(): void
    {
        check_ajax_referer('kng_fomo_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'king-addons')]);
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        // Use separate 'name' field for post_title if provided, otherwise fall back to 'title'
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        if (!empty($name)) {
            $title = $name;
        }
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'disabled';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'notification_bar';
        $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'manual';
        $source_config = isset($_POST['source_config']) ? $_POST['source_config'] : '{}';
        $design = isset($_POST['design']) ? $_POST['design'] : '{}';
        $content = isset($_POST['content']) ? $_POST['content'] : '{}';
        $display = isset($_POST['display']) ? $_POST['display'] : '{}';
        $customize = isset($_POST['customize']) ? $_POST['customize'] : '{}';

        // Check free limit
        if (!$id && !self::hasPro()) {
            $count = count($this->get_all_notifications());
            if ($count >= self::FREE_LIMIT) {
                wp_send_json_error(['message' => __('Free version limit reached.', 'king-addons')]);
            }
        }

        $post_data = [
            'post_title' => $title ?: __('Untitled Notification', 'king-addons'),
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
        ];

        if ($id) {
            $post_data['ID'] = $id;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }

        if (is_wp_error($post_id)) {
            wp_send_json_error(['message' => $post_id->get_error_message()]);
        }

        // Save meta
        update_post_meta($post_id, '_kng_fomo_status', $status);
        update_post_meta($post_id, '_kng_fomo_type', $type);
        update_post_meta($post_id, '_kng_fomo_source', $source);
        update_post_meta($post_id, '_kng_fomo_source_config', $source_config);
        update_post_meta($post_id, '_kng_fomo_design', $design);
        update_post_meta($post_id, '_kng_fomo_content', $content);
        update_post_meta($post_id, '_kng_fomo_display', $display);
        update_post_meta($post_id, '_kng_fomo_customize', $customize);

        wp_send_json_success([
            'id' => $post_id,
            'message' => __('Notification saved successfully!', 'king-addons'),
        ]);
    }

    /**
     * AJAX: Get notification for editing
     *
     * @return void
     */
    public function ajax_get_notification(): void
    {
        check_ajax_referer('kng_fomo_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'king-addons')]);
        }

        $id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
        if (!$id) {
            wp_send_json_error(['message' => __('Invalid notification ID.', 'king-addons')]);
        }

        $post = get_post($id);
        if (!$post || $post->post_type !== self::POST_TYPE) {
            wp_send_json_error(['message' => __('Notification not found.', 'king-addons')]);
        }

        $data = [
            'notification_id' => $id,
            'type' => get_post_meta($id, '_kng_fomo_type', true) ?: 'notification_bar',
            'source' => get_post_meta($id, '_kng_fomo_source', true) ?: 'manual',
            'source_config' => json_decode(get_post_meta($id, '_kng_fomo_source_config', true) ?: '{}', true),
            'design' => json_decode(get_post_meta($id, '_kng_fomo_design', true) ?: '{}', true),
            'content' => json_decode(get_post_meta($id, '_kng_fomo_content', true) ?: '{}', true),
            'display' => json_decode(get_post_meta($id, '_kng_fomo_display', true) ?: '{}', true),
            'customize' => json_decode(get_post_meta($id, '_kng_fomo_customize', true) ?: '{}', true),
        ];

        // Provide the notification name separately from content.title
        $data['name'] = $post->post_title;

        wp_send_json_success($data);
    }

    /**
     * AJAX: Delete notification
     *
     * @return void
     */
    public function ajax_delete_notification(): void
    {
        check_ajax_referer('kng_fomo_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'king-addons')]);
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!$id) {
            wp_send_json_error(['message' => __('Invalid notification ID.', 'king-addons')]);
        }

        $result = wp_delete_post($id, true);
        if (!$result) {
            wp_send_json_error(['message' => __('Failed to delete notification.', 'king-addons')]);
        }

        wp_send_json_success(['message' => __('Notification deleted.', 'king-addons')]);
    }

    /**
     * AJAX: Toggle notification status
     *
     * @return void
     */
    public function ajax_toggle_status(): void
    {
        check_ajax_referer('kng_fomo_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'king-addons')]);
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'disabled';

        if (!$id) {
            wp_send_json_error(['message' => __('Invalid notification ID.', 'king-addons')]);
        }

        update_post_meta($id, '_kng_fomo_status', $status);

        wp_send_json_success(['message' => __('Status updated.', 'king-addons')]);
    }

    /**
     * AJAX: Duplicate notification
     *
     * @return void
     */
    public function ajax_duplicate_notification(): void
    {
        check_ajax_referer('kng_fomo_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'king-addons')]);
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $original = $this->get_notification($id);

        if (!$original) {
            wp_send_json_error(['message' => __('Notification not found.', 'king-addons')]);
        }

        // Check free limit
        if (!self::hasPro()) {
            $count = count($this->get_all_notifications());
            if ($count >= self::FREE_LIMIT) {
                wp_send_json_error(['message' => __('Free version limit reached.', 'king-addons')]);
            }
        }

        $new_id = wp_insert_post([
            'post_title' => $original['title'] . ' ' . __('(Copy)', 'king-addons'),
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
        ]);

        if (is_wp_error($new_id)) {
            wp_send_json_error(['message' => $new_id->get_error_message()]);
        }

        // Copy meta
        update_post_meta($new_id, '_kng_fomo_status', 'disabled');
        update_post_meta($new_id, '_kng_fomo_type', $original['type']);
        update_post_meta($new_id, '_kng_fomo_source', $original['source']);
        update_post_meta($new_id, '_kng_fomo_source_config', wp_json_encode($original['source_config']));
        update_post_meta($new_id, '_kng_fomo_design', wp_json_encode($original['design']));
        update_post_meta($new_id, '_kng_fomo_content', wp_json_encode($original['content']));
        update_post_meta($new_id, '_kng_fomo_display', wp_json_encode($original['display']));
        update_post_meta($new_id, '_kng_fomo_customize', wp_json_encode($original['customize']));

        wp_send_json_success([
            'id' => $new_id,
            'message' => __('Notification duplicated.', 'king-addons'),
        ]);
    }

    /**
     * AJAX: Save settings
     *
     * @return void
     */
    public function ajax_save_settings(): void
    {
        check_ajax_referer('kng_fomo_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'king-addons')]);
        }

        $raw_settings = isset($_POST['settings']) ? $_POST['settings'] : [];

        // If settings were sent as JSON string, decode them
        if (is_string($raw_settings)) {
            $raw_settings = json_decode(stripslashes($raw_settings), true);
            if (!is_array($raw_settings)) {
                $raw_settings = [];
            }
        }

        // Sanitize
        $sanitized = [
            'enabled' => !empty($raw_settings['enabled']),
            'tracking_enabled' => !empty($raw_settings['tracking_enabled']),
            'track_for' => sanitize_text_field($raw_settings['track_for'] ?? 'everyone'),
            'exclude_bots' => !empty($raw_settings['exclude_bots']),
            'cache_ttl' => (int)($raw_settings['cache_ttl'] ?? 300),
            'anonymize_names' => !empty($raw_settings['anonymize_names']),
            'sound_volume' => (int)($raw_settings['sound_volume'] ?? 50),
            'modules' => [],
        ];

        if (!empty($raw_settings['modules']) && is_array($raw_settings['modules'])) {
            foreach ($raw_settings['modules'] as $module => $enabled) {
                $sanitized['modules'][sanitize_key($module)] = !empty($enabled);
            }
        }

        $this->save_settings($sanitized);

        wp_send_json_success(['message' => __('Settings saved.', 'king-addons')]);
    }

    /**
     * AJAX: Track event (view or click)
     *
     * @return void
     */
    public function ajax_track_event(): void
    {
        // Verify nonce
        check_ajax_referer('kng_fomo_frontend', 'nonce');

        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $rate_key = 'kng_fomo_rate_' . md5($ip);
        $rate = get_transient($rate_key);
        if ($rate && $rate > 100) {
            wp_send_json_error(['message' => 'Rate limit exceeded']);
        }
        set_transient($rate_key, ($rate ?: 0) + 1, MINUTE_IN_SECONDS);

        $notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
        $event_type = isset($_POST['event_type']) ? sanitize_text_field($_POST['event_type']) : '';

        if (!$notification_id || !in_array($event_type, ['view', 'click'], true)) {
            wp_send_json_error(['message' => 'Invalid request']);
        }

        $settings = $this->get_settings();
        if (empty($settings['tracking_enabled'])) {
            wp_send_json_success();
        }

        // Check audience
        $track_for = $settings['track_for'] ?? 'everyone';
        if ($track_for === 'guests' && is_user_logged_in()) {
            wp_send_json_success();
        }
        if ($track_for === 'logged_in' && !is_user_logged_in()) {
            wp_send_json_success();
        }

        // Update total counts
        $meta_key = $event_type === 'view' ? '_kng_fomo_views' : '_kng_fomo_clicks';
        $current = (int)get_post_meta($notification_id, $meta_key, true);
        update_post_meta($notification_id, $meta_key, $current + 1);

        // Update daily stats
        global $wpdb;
        $table = $wpdb->prefix . self::STATS_TABLE;
        $today = current_time('Y-m-d');
        $column = $event_type === 'view' ? 'views' : 'clicks';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (notification_id, stat_date, $column) VALUES (%d, %s, 1)
             ON DUPLICATE KEY UPDATE $column = $column + 1",
            $notification_id,
            $today
        ));

        wp_send_json_success();
    }

    /**
     * Get total stats for all notifications
     *
     * @return array
     */
    public function get_total_stats(): array
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::STATS_TABLE;

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return [
                'views' => 0,
                'clicks' => 0,
                'ctr' => 0,
            ];
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $stats = $wpdb->get_row("SELECT SUM(views) as views, SUM(clicks) as clicks FROM $table_name", ARRAY_A);

        $views = isset($stats['views']) ? (int)$stats['views'] : 0;
        $clicks = isset($stats['clicks']) ? (int)$stats['clicks'] : 0;
        $ctr = $views > 0 ? round(($clicks / $views) * 100, 2) : 0;

        return [
            'views' => $views,
            'clicks' => $clicks,
            'ctr' => $ctr,
            'views_change' => 0,
            'clicks_change' => 0,
        ];
    }

    /**
     * AJAX: Get analytics data
     *
     * @return void
     */
    public function ajax_get_analytics(): void
    {
        check_ajax_referer('kng_fomo_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'king-addons')]);
        }

        global $wpdb;
        $table = $wpdb->prefix . self::STATS_TABLE;

        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '7days';
        if (empty($period) && isset($_POST['range'])) {
            $period = sanitize_text_field($_POST['range']);
        }
        $notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

        // Calculate date range
        $end_date = current_time('Y-m-d');
        switch ($period) {
            case '30days':
                $start_date = wp_date('Y-m-d', strtotime('-30 days'));
                break;
            case '90days':
                $start_date = wp_date('Y-m-d', strtotime('-90 days'));
                break;
            case 'year':
                $start_date = wp_date('Y-01-01');
                break;
            case '7days':
            default:
                $start_date = wp_date('Y-m-d', strtotime('-7 days'));
                break;
        }

        // Build query
        $where = $wpdb->prepare("WHERE stat_date BETWEEN %s AND %s", $start_date, $end_date);
        if ($notification_id) {
            $where .= $wpdb->prepare(" AND notification_id = %d", $notification_id);
        }

        // Get totals
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $totals = $wpdb->get_row(
            "SELECT SUM(views) as views, SUM(clicks) as clicks FROM $table $where"
        );

        $total_views = (int)($totals->views ?? 0);
        $total_clicks = (int)($totals->clicks ?? 0);
        $total_ctr = $total_views > 0 ? round(($total_clicks / $total_views) * 100, 2) : 0;

        // Get daily data for chart
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $daily = $wpdb->get_results(
            "SELECT stat_date, SUM(views) as views, SUM(clicks) as clicks 
             FROM $table $where 
             GROUP BY stat_date 
             ORDER BY stat_date ASC"
        );

        $chart_data = [
            'labels' => [],
            'views' => [],
            'clicks' => [],
        ];

        foreach ($daily as $row) {
            $chart_data['labels'][] = wp_date('M j', strtotime($row->stat_date));
            $chart_data['views'][] = (int)$row->views;
            $chart_data['clicks'][] = (int)$row->clicks;
        }

        // Get top notifications
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $top_raw = $wpdb->get_results(
            "SELECT notification_id, SUM(views) as views, SUM(clicks) as clicks 
             FROM $table $where 
             GROUP BY notification_id 
             ORDER BY clicks DESC 
             LIMIT 5"
        );

        $top_notifications = [];
        foreach ($top_raw as $row) {
            $post = get_post($row->notification_id);
            if ($post) {
                $ctr = $row->views > 0 ? round(($row->clicks / $row->views) * 100, 2) : 0;
                $top_notifications[] = [
                    'id' => $row->notification_id,
                    'title' => $post->post_title,
                    'views' => (int)$row->views,
                    'clicks' => (int)$row->clicks,
                    'ctr' => $ctr,
                ];
            }
        }

        wp_send_json_success([
            'totals' => [
                'views' => $total_views,
                'clicks' => $total_clicks,
                'ctr' => $total_ctr,
            ],
            'chart' => $chart_data,
            'top' => $top_notifications,
        ]);
    }

    /**
     * AJAX: Export notification
     *
     * @return void
     */
    public function ajax_export_notification(): void
    {
        check_ajax_referer('kng_fomo_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'king-addons')]);
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        // Export single notification
        if ($id) {
            $notification = $this->get_notification($id);

            if (!$notification) {
                wp_send_json_error(['message' => __('Notification not found.', 'king-addons')]);
            }

            // Remove stats and IDs for export
            unset($notification['id'], $notification['views'], $notification['clicks'], $notification['ctr']);

            wp_send_json_success([
                'data' => $notification,
                'filename' => 'fomo-notification-' . sanitize_title($notification['title']) . '.json',
            ]);
            return;
        }

        // Export all notifications
        $all = $this->get_all_notifications();
        $export = [];
        foreach ($all as $notification) {
            unset($notification['id'], $notification['views'], $notification['clicks'], $notification['ctr']);
            $export[] = $notification;
        }

        wp_send_json_success([
            'data' => $export,
            'filename' => 'fomo-notifications-export.json',
        ]);
    }

    /**
     * AJAX: Import notification
     *
     * @return void
     */
    public function ajax_import_notification(): void
    {
        check_ajax_referer('kng_fomo_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'king-addons')]);
        }

        $data = isset($_POST['data']) ? $_POST['data'] : '';
        if (empty($data)) {
            wp_send_json_error(['message' => __('No data to import.', 'king-addons')]);
        }

        $notification = json_decode(stripslashes($data), true);
        if (!$notification || !is_array($notification)) {
            wp_send_json_error(['message' => __('Invalid import data.', 'king-addons')]);
        }

        // Check free limit
        if (!self::hasPro()) {
            $count = count($this->get_all_notifications());
            if ($count >= self::FREE_LIMIT) {
                wp_send_json_error(['message' => __('Free version limit reached.', 'king-addons')]);
            }
        }

        $post_id = wp_insert_post([
            'post_title' => $notification['title'] ?? __('Imported Notification', 'king-addons'),
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
        ]);

        if (is_wp_error($post_id)) {
            wp_send_json_error(['message' => $post_id->get_error_message()]);
        }

        // Save meta
        update_post_meta($post_id, '_kng_fomo_status', 'disabled');
        update_post_meta($post_id, '_kng_fomo_type', $notification['type'] ?? 'notification_bar');
        update_post_meta($post_id, '_kng_fomo_source', $notification['source'] ?? 'manual');
        update_post_meta($post_id, '_kng_fomo_source_config', wp_json_encode($notification['source_config'] ?? []));
        update_post_meta($post_id, '_kng_fomo_design', wp_json_encode($notification['design'] ?? []));
        update_post_meta($post_id, '_kng_fomo_content', wp_json_encode($notification['content'] ?? []));
        update_post_meta($post_id, '_kng_fomo_display', wp_json_encode($notification['display'] ?? []));
        update_post_meta($post_id, '_kng_fomo_customize', wp_json_encode($notification['customize'] ?? []));

        wp_send_json_success([
            'id' => $post_id,
            'message' => __('Notification imported successfully!', 'king-addons'),
        ]);
    }

    /**
     * AJAX: Fetch WordPress.org data
     *
     * @return void
     */
    public function ajax_fetch_wporg_data(): void
    {
        check_ajax_referer('kng_fomo_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'king-addons')]);
        }

        $slug = isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'plugin';

        if (empty($slug)) {
            wp_send_json_error(['message' => __('Please enter a slug.', 'king-addons')]);
        }

        $data = $this->get_wporg_data([
            'slug' => $slug,
            'product_type' => $type,
            'data_type' => 'downloads',
        ]);

        if (empty($data)) {
            wp_send_json_error(['message' => __('Could not fetch data. Please check the slug.', 'king-addons')]);
        }

        wp_send_json_success(['data' => $data]);
    }

    /**
     * AJAX: Purge cache
     *
     * @return void
     */
    public function ajax_purge_cache(): void
    {
        check_ajax_referer('kng_fomo_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'king-addons')]);
        }

        global $wpdb;

        // Delete all kng_fomo transients
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query(
            "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_kng_fomo_%' OR option_name LIKE '_transient_timeout_kng_fomo_%'"
        );

        wp_send_json_success(['message' => __('Cache cleared successfully.', 'king-addons')]);
    }
}
