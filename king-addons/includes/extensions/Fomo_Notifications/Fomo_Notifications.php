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
        $meta_fields = [
            '_kng_fomo_status',
            '_kng_fomo_type',
            '_kng_fomo_source',
            '_kng_fomo_source_config',
            '_kng_fomo_design',
            '_kng_fomo_content',
            '_kng_fomo_display',
            '_kng_fomo_customize',
            '_kng_fomo_views',
            '_kng_fomo_clicks',
        ];

        foreach ($meta_fields as $meta_key) {
            register_post_meta(self::POST_TYPE, $meta_key, [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ]);
        }
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
        if (!$this->has_active_notifications()) {
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
            ['jquery'],
            self::VERSION,
            true
        );

        wp_localize_script('kng-fomo-frontend', 'kngFomoFrontend', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kng_fomo_frontend'),
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
            $is_edit = $view === 'edit';

            if ($is_edit && isset($_GET['id'])) {
                $notification = $this->get_notification((int)$_GET['id']);
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
            'type' => get_post_meta($post->ID, '_kng_fomo_type', true) ?: 'notification-bar',
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
            'cache_ttl' => 3600,
            'modules' => [
                'notification-bar' => true,
                'woocommerce-sales' => true,
                'wordpress-comments' => true,
                'wporg-downloads' => true,
                'wporg-reviews' => false,
                'google-reviews' => false,
                'email-subscription' => false,
                'elearning' => false,
                'donations' => false,
                'discount-alert' => false,
                'flashing-tab' => false,
                'custom-csv' => false,
                'page-analytics' => false,
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
     * Check if there are active notifications for current page
     *
     * @return bool
     */
    private function has_active_notifications(): bool
    {
        $settings = $this->get_settings();
        if (empty($settings['enabled'])) {
            return false;
        }

        $notifications = $this->get_active_notifications_for_page();
        return !empty($notifications);
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

        // Check show_on rules
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

        // Check display_for (audience)
        $display_for = $display['display_for'] ?? 'everyone';
        if ($display_for === 'guests' && is_user_logged_in()) {
            return false;
        }
        if ($display_for === 'logged_in' && !is_user_logged_in()) {
            return false;
        }

        return true;
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
        $notifications = $this->get_active_notifications_for_page();
        if (empty($notifications)) {
            return;
        }

        // Prepare notifications data for JS
        $notifications_data = [];
        foreach ($notifications as $notification) {
            $notifications_data[] = $this->prepare_notification_for_frontend($notification);
        }

        echo '<div id="kng-fomo-container" data-notifications="' . esc_attr(wp_json_encode($notifications_data)) . '"></div>';
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

        return [
            'id' => $notification['id'],
            'type' => $notification['type'],
            'design' => $notification['design'],
            'content' => $content,
            'customize' => $notification['customize'],
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
        $source_config = $notification['source_config'];

        // For dynamic sources, fetch data
        if ($source === 'woocommerce') {
            $content['items'] = $this->get_woocommerce_data($source_config);
        } elseif ($source === 'comments') {
            $content['items'] = $this->get_comments_data($source_config);
        } elseif ($source === 'wporg') {
            $content['items'] = $this->get_wporg_data($source_config);
        }

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

        $limit = $config['limit'] ?? 10;
        $days = $config['days'] ?? 7;

        $args = [
            'limit' => $limit,
            'status' => ['wc-completed', 'wc-processing'],
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
        $limit = $config['limit'] ?? 10;
        $post_scope = $config['post_scope'] ?? 'all';

        $args = [
            'number' => $limit,
            'status' => 'approve',
            'orderby' => 'comment_date',
            'order' => 'DESC',
        ];

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
        $slug = $config['slug'] ?? '';
        $type = $config['product_type'] ?? 'plugin';
        $data_type = $config['data_type'] ?? 'downloads';

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
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'disabled';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'notification-bar';
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

        // Ensure content.title has the post title
        if (empty($data['content']['title'])) {
            $data['content']['title'] = $post->post_title;
        }

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

        $settings = isset($_POST['settings']) ? $_POST['settings'] : [];

        // Sanitize
        $sanitized = [
            'enabled' => !empty($settings['enabled']),
            'tracking_enabled' => !empty($settings['tracking_enabled']),
            'track_for' => sanitize_text_field($settings['track_for'] ?? 'everyone'),
            'exclude_bots' => !empty($settings['exclude_bots']),
            'cache_ttl' => (int)($settings['cache_ttl'] ?? 3600),
            'modules' => [],
        ];

        if (!empty($settings['modules']) && is_array($settings['modules'])) {
            foreach ($settings['modules'] as $module => $enabled) {
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
        $notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

        // Calculate date range
        $end_date = current_time('Y-m-d');
        switch ($period) {
            case '30days':
                $start_date = date('Y-m-d', strtotime('-30 days'));
                break;
            case '90days':
                $start_date = date('Y-m-d', strtotime('-90 days'));
                break;
            case '7days':
            default:
                $start_date = date('Y-m-d', strtotime('-7 days'));
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
            $chart_data['labels'][] = date('M j', strtotime($row->stat_date));
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
        update_post_meta($post_id, '_kng_fomo_type', $notification['type'] ?? 'notification-bar');
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

// Initialize the extension
Fomo_Notifications::instance();
