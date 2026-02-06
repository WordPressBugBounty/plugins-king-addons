<?php
/**
 * Activity Log extension.
 *
 * @package King_Addons
 */

namespace King_Addons\Activity_Log;

use WP_Post;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/Activity_Log_DB.php';

class Activity_Log
{
    private const OPTION_NAME = 'king_addons_activity_log_settings';

    private const CORE_SETTINGS_OPTIONS_ALLOWLIST = [
        'blogname',
        'blogdescription',
        'siteurl',
        'home',
        'admin_email',
        'users_can_register',
        'default_role',
        'timezone_string',
        'gmt_offset',
        'date_format',
        'time_format',
        'start_of_week',
        'permalink_structure',
        'category_base',
        'tag_base',
        'posts_per_page',
        'show_on_front',
        'page_on_front',
        'page_for_posts',
        'thumbnail_size_w',
        'thumbnail_size_h',
        'thumbnail_crop',
        'medium_size_w',
        'medium_size_h',
        'large_size_w',
        'large_size_h',
        'uploads_use_yearmonth_folders',
    ];

    private static ?Activity_Log $instance = null;

    /**
     * Cached settings.
     *
     * @var array<string, mixed>
     */
    private array $settings = [];

    public static function instance(): Activity_Log
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->settings = $this->get_settings();

        add_action('init', [$this, 'maybe_create_table']);
        add_action('init', [$this, 'schedule_purge']);
        add_action('kng_activity_log_purge', [$this, 'purge_old_logs']);

        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        add_action('admin_post_kng_activity_log_export', [$this, 'handle_export']);
        add_action('admin_post_kng_activity_log_purge', [$this, 'handle_manual_purge']);
        add_action('admin_post_kng_activity_log_save_alerts', [$this, 'handle_save_alerts']);

        // Settings changes + King Addons option changes (Pro modules).
        add_action('updated_option', [$this, 'log_option_updated'], 10, 3);
        add_action('added_option', [$this, 'log_option_added'], 10, 2);
        add_action('deleted_option', [$this, 'log_option_deleted'], 10, 1);

        // WooCommerce events (Pro module).
        add_action('plugins_loaded', [$this, 'register_woocommerce_hooks'], 20);

        add_action('wp_login', [$this, 'log_login'], 10, 2);
        add_action('wp_login_failed', [$this, 'log_failed_login']);
        add_action('wp_logout', [$this, 'log_logout']);
        add_action('user_register', [$this, 'log_user_created']);
        add_action('profile_update', [$this, 'log_user_updated'], 10, 2);
        add_action('delete_user', [$this, 'log_user_deleted']);
        add_action('set_user_role', [$this, 'log_user_role_changed'], 10, 3);

        add_action('save_post', [$this, 'log_post_saved'], 10, 3);
        add_action('wp_trash_post', [$this, 'log_post_trashed']);
        add_action('untrash_post', [$this, 'log_post_restored']);
        add_action('before_delete_post', [$this, 'log_post_deleted']);

        add_action('activated_plugin', [$this, 'log_plugin_activated'], 10, 2);
        add_action('deactivated_plugin', [$this, 'log_plugin_deactivated'], 10, 2);
        add_action('upgrader_process_complete', [$this, 'log_plugin_updated'], 10, 2);
        add_action('switch_theme', [$this, 'log_theme_switched'], 10, 3);

        add_action('kng_activity_log/event', [$this, 'handle_custom_event']);
    }

    public function register_woocommerce_hooks(): void
    {
        if (!function_exists('wc_get_order')) {
            return;
        }

        add_action('woocommerce_new_order', [$this, 'log_wc_order_created'], 10, 1);
        add_action('woocommerce_order_status_changed', [$this, 'log_wc_order_status_changed'], 10, 4);
        add_action('woocommerce_product_set_stock', [$this, 'log_wc_product_stock_changed'], 10, 1);
    }

    public function log_wc_order_created(int $order_id): void
    {
        if (!function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $user_id = (int) $order->get_user_id();
        $user_login = '';
        if ($user_id > 0) {
            $user = get_userdata($user_id);
            $user_login = $user ? (string) $user->user_login : '';
        }

        $this->log_event([
            'event_key' => 'woocommerce.order.created',
            'severity' => 'notice',
            'user_id' => $user_id ?: null,
            'user_login' => $user_login,
            'object_type' => 'shop_order',
            'object_id' => (string) $order_id,
            'object_title' => 'Order #' . $order->get_order_number(),
            'source' => 'woocommerce',
            'message' => __('Order created.', 'king-addons'),
            'data' => [
                'status' => $order->get_status(),
                'total' => $order->get_total(),
                'currency' => $order->get_currency(),
                'payment_method' => $order->get_payment_method_title(),
            ],
        ]);
    }

    public function log_wc_order_status_changed(int $order_id, string $old_status, string $new_status, $order): void
    {
        if (!function_exists('wc_get_order')) {
            return;
        }

        if (!$order) {
            $order = wc_get_order($order_id);
        }

        if (!$order) {
            return;
        }

        $user_id = (int) $order->get_user_id();
        $user_login = '';
        if ($user_id > 0) {
            $user = get_userdata($user_id);
            $user_login = $user ? (string) $user->user_login : '';
        }

        $this->log_event([
            'event_key' => 'woocommerce.order.status_changed',
            'severity' => 'notice',
            'user_id' => $user_id ?: null,
            'user_login' => $user_login,
            'object_type' => 'shop_order',
            'object_id' => (string) $order_id,
            'object_title' => 'Order #' . $order->get_order_number(),
            'source' => 'woocommerce',
            'message' => __('Order status changed.', 'king-addons'),
            'data' => [
                'from' => $old_status,
                'to' => $new_status,
            ],
        ]);
    }

    public function log_wc_product_stock_changed($product): void
    {
        if (!is_object($product) || !method_exists($product, 'get_id')) {
            return;
        }

        $product_id = (int) $product->get_id();
        if ($product_id <= 0) {
            return;
        }

        $this->log_event([
            'event_key' => 'woocommerce.product.stock_changed',
            'severity' => 'notice',
            'object_type' => 'product',
            'object_id' => (string) $product_id,
            'object_title' => method_exists($product, 'get_name') ? (string) $product->get_name() : ('#' . $product_id),
            'source' => 'woocommerce',
            'message' => __('Product stock updated.', 'king-addons'),
            'data' => [
                'stock_status' => method_exists($product, 'get_stock_status') ? (string) $product->get_stock_status() : '',
                'stock_quantity' => method_exists($product, 'get_stock_quantity') ? $product->get_stock_quantity() : null,
            ],
        ]);
    }

    public function log_option_updated(string $option, $old_value, $value): void
    {
        if (!$this->should_log_option_change($option)) {
            return;
        }

        if ($old_value === $value) {
            return;
        }

        $event_key = $this->is_king_addons_option($option) ? 'kng.option.updated' : 'settings.option.updated';

        $data = [
            'option' => $option,
            'old' => $this->sanitize_option_value_for_log($option, $old_value),
            'new' => $this->sanitize_option_value_for_log($option, $value),
        ];

        if (is_array($old_value) && is_array($value)) {
            $changed_keys = [];
            foreach (array_unique(array_merge(array_keys($old_value), array_keys($value))) as $key) {
                $old = $old_value[$key] ?? null;
                $new = $value[$key] ?? null;
                if ($old !== $new) {
                    $changed_keys[] = (string) $key;
                }
            }

            $data['changed_count'] = count($changed_keys);
            $data['changed_keys'] = array_slice($changed_keys, 0, 20);
        }

        $this->log_event([
            'event_key' => $event_key,
            'severity' => 'notice',
            'object_type' => 'option',
            'object_id' => $option,
            'object_title' => $option,
            'source' => 'core',
            'message' => __('Option updated.', 'king-addons'),
            'data' => $data,
        ]);
    }

    public function log_option_added(string $option, $value): void
    {
        if (!$this->should_log_option_change($option)) {
            return;
        }

        $event_key = $this->is_king_addons_option($option) ? 'kng.option.added' : 'settings.option.added';

        $this->log_event([
            'event_key' => $event_key,
            'severity' => 'notice',
            'object_type' => 'option',
            'object_id' => $option,
            'object_title' => $option,
            'source' => 'core',
            'message' => __('Option added.', 'king-addons'),
            'data' => [
                'option' => $option,
                'new' => $this->sanitize_option_value_for_log($option, $value),
            ],
        ]);
    }

    public function log_option_deleted(string $option): void
    {
        if (!$this->should_log_option_change($option)) {
            return;
        }

        $event_key = $this->is_king_addons_option($option) ? 'kng.option.deleted' : 'settings.option.deleted';

        $this->log_event([
            'event_key' => $event_key,
            'severity' => 'warning',
            'object_type' => 'option',
            'object_id' => $option,
            'object_title' => $option,
            'source' => 'core',
            'message' => __('Option deleted.', 'king-addons'),
            'data' => [
                'option' => $option,
            ],
        ]);
    }

    private function should_log_option_change(string $option): bool
    {
        if ($option === '' || $option === self::OPTION_NAME) {
            return false;
        }

        if (strpos($option, '_transient_') === 0 || strpos($option, '_site_transient_') === 0) {
            return false;
        }

        // Avoid recursion & internal runtime counters.
        if (strpos($option, 'king_addons_activity_log_') === 0) {
            return false;
        }

        if ($this->is_king_addons_option($option)) {
            if ($option === 'king_addons_options') {
                return true;
            }

            if (substr($option, -9) === '_settings') {
                return true;
            }

            return false;
        }

        return in_array($option, self::CORE_SETTINGS_OPTIONS_ALLOWLIST, true);
    }

    private function is_king_addons_option(string $option): bool
    {
        return strpos($option, 'king_addons_') === 0;
    }

    private function sanitize_option_value_for_log(string $option, $value): string
    {
        $lower = strtolower($option);
        if (preg_match('/(pass|password|secret|token|key|salt|nonce|license)/', $lower)) {
            return '[redacted]';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return '';
            }

            if (strlen($trimmed) > 120) {
                return substr($trimmed, 0, 120) . '…';
            }

            return $trimmed;
        }

        if (is_array($value)) {
            return '[array:' . count($value) . ']';
        }

        if (is_object($value)) {
            return '[object:' . get_class($value) . ']';
        }

        return '[unknown]';
    }

    public function register_admin_menu(): void
    {
        $view_logs_cap = $this->get_view_logs_capability();

        // Optional logs-only entry for non-admin roles (Pro).
        // Admins can still access logs from the main Activity Log page.
        if ($view_logs_cap !== 'manage_options') {
            add_submenu_page(
                'king-addons',
                __('Activity Logs', 'king-addons'),
                __('Activity Logs', 'king-addons'),
                $view_logs_cap,
                'king-addons-activity-log-logs',
                [$this, 'render_admin_logs_page']
            );
        }

        add_submenu_page(
            'king-addons',
            __('Activity Log', 'king-addons'),
            __('Activity Log', 'king-addons'),
            'manage_options',
            'king-addons-activity-log',
            [$this, 'render_admin_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'king_addons_activity_log',
            self::OPTION_NAME,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_default_settings(),
            ]
        );
    }

    public function enqueue_admin_assets(string $hook): void
    {
        if (!in_array($hook, ['king-addons_page_king-addons-activity-log', 'king-addons_page_king-addons-activity-log-logs'], true)) {
            return;
        }

        $shared_css = KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css';
        $shared_path = KING_ADDONS_PATH . 'includes/admin/layouts/shared/admin-v3-styles.css';
        $shared_version = file_exists($shared_path) ? filemtime($shared_path) : KING_ADDONS_VERSION;
        wp_enqueue_style('king-addons-admin-v3', $shared_css, [], $shared_version);

        $admin_css = KING_ADDONS_URL . 'includes/extensions/Activity_Log/assets/admin.css';
        $admin_path = KING_ADDONS_PATH . 'includes/extensions/Activity_Log/assets/admin.css';
        $admin_version = file_exists($admin_path) ? filemtime($admin_path) : KING_ADDONS_VERSION;
        wp_enqueue_style('king-addons-activity-log-admin', $admin_css, ['king-addons-admin-v3'], $admin_version);

        $admin_js = KING_ADDONS_URL . 'includes/extensions/Activity_Log/assets/admin.js';
        $admin_path_js = KING_ADDONS_PATH . 'includes/extensions/Activity_Log/assets/admin.js';
        $admin_js_version = file_exists($admin_path_js) ? filemtime($admin_path_js) : KING_ADDONS_VERSION;
        wp_enqueue_script('king-addons-activity-log-admin', $admin_js, ['jquery'], $admin_js_version, true);

        wp_localize_script('king-addons-activity-log-admin', 'KNGActivityLog', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'themeNonce' => wp_create_nonce('king_addons_dashboard_ui'),
        ]);
    }

    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'dashboard';
        $is_pro = $this->is_pro();
        $settings = $this->get_settings();

        include __DIR__ . '/templates/admin-page.php';
    }

    public function render_admin_logs_page(): void
    {
        $capability = $this->get_view_logs_capability();
        if (!current_user_can($capability)) {
            return;
        }

        $view = 'logs';
        $is_pro = $this->is_pro();
        $settings = $this->get_settings();

        include __DIR__ . '/templates/admin-page.php';
    }

    public function maybe_create_table(): void
    {
        Activity_Log_DB::maybe_create_table();
    }

    public function schedule_purge(): void
    {
        if (!wp_next_scheduled('kng_activity_log_purge')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'kng_activity_log_purge');
        }
    }

    public function purge_old_logs(): void
    {
        global $wpdb;

        $days = $this->get_retention_days();
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));

        $table = Activity_Log_DB::get_table();
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE created_at < %s", $cutoff));
    }

    public function handle_manual_purge(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_activity_log_purge');
        $this->purge_old_logs();

        $this->redirect_with_message('tools', 'purged');
    }

    public function handle_export(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_activity_log_export');

        $filters = $this->get_filters_from_request();
        $filters = $this->apply_retention_limit($filters);
        $logs = $this->get_logs($filters, 1, 0);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=activity-log-' . gmdate('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Time',
            'Event',
            'Severity',
            'User',
            'Role',
            'IP',
            'Object',
            'Source',
            'Message',
        ]);

        foreach ($logs as $log) {
            $object = trim($log->object_type . ' ' . $log->object_title);
            $row = [
                $this->format_time($log->created_at),
                $log->event_key,
                $log->severity,
                $log->user_login ?: 'Guest',
                $log->user_role ?: '',
                $log->ip ?: '',
                trim($object),
                $log->source,
                $log->message,
            ];
            $row = array_map([$this, 'escape_csv_value'], $row);
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    public function handle_save_alerts(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_activity_log_alerts');

        $settings = $this->get_settings();
        $alerts = [
            'failed_login_enabled' => !empty($_POST['failed_login_enabled']),
            'failed_login_threshold' => absint($_POST['failed_login_threshold'] ?? 5),
            'failed_login_window' => absint($_POST['failed_login_window'] ?? 10),
            'failed_login_emails' => sanitize_text_field(wp_unslash($_POST['failed_login_emails'] ?? '')),
        ];

        $settings['alerts'] = $this->sanitize_alerts($alerts);
        update_option(self::OPTION_NAME, $settings);

        $this->redirect_with_message('alerts', 'alerts_saved');
    }

    public function log_login(string $user_login, \WP_User $user): void
    {
        $this->log_event([
            'event_key' => 'auth.login.success',
            'severity' => 'info',
            'user_id' => $user->ID,
            'user_login' => $user_login,
            'user_role' => $this->get_user_role($user),
            'object_type' => 'user',
            'object_id' => (string) $user->ID,
            'object_title' => $user_login,
            'source' => 'core',
            'message' => __('User logged in.', 'king-addons'),
        ]);
    }

    public function log_failed_login(string $username): void
    {
        $user = get_user_by('login', $username);
        $user_id = $user ? $user->ID : 0;
        $user_role = $user ? $this->get_user_role($user) : '';

        $this->log_event([
            'event_key' => 'auth.login.failed',
            'severity' => 'warning',
            'user_id' => $user_id,
            'user_login' => $username,
            'user_role' => $user_role,
            'object_type' => 'user',
            'object_id' => $user_id ? (string) $user_id : '',
            'object_title' => $username,
            'source' => 'core',
            'message' => __('Failed login attempt.', 'king-addons'),
            'data' => [
                'login' => $username,
            ],
        ]);

        $this->maybe_send_failed_login_alert();
    }

    public function log_logout(): void
    {
        $user = wp_get_current_user();
        if (!$user || !$user->ID) {
            return;
        }

        $this->log_event([
            'event_key' => 'auth.logout',
            'severity' => 'info',
            'user_id' => $user->ID,
            'user_login' => $user->user_login,
            'user_role' => $this->get_user_role($user),
            'object_type' => 'user',
            'object_id' => (string) $user->ID,
            'object_title' => $user->user_login,
            'source' => 'core',
            'message' => __('User logged out.', 'king-addons'),
        ]);
    }

    public function log_user_created(int $user_id): void
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $this->log_event([
            'event_key' => 'user.created',
            'severity' => 'notice',
            'user_id' => $user_id,
            'user_login' => $user->user_login,
            'user_role' => $this->get_user_role($user),
            'object_type' => 'user',
            'object_id' => (string) $user_id,
            'object_title' => $user->user_login,
            'source' => 'core',
            'message' => __('User account created.', 'king-addons'),
        ]);
    }

    public function log_user_updated(int $user_id, $old_user_data): void
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $this->log_event([
            'event_key' => 'user.updated',
            'severity' => 'notice',
            'user_id' => $user_id,
            'user_login' => $user->user_login,
            'user_role' => $this->get_user_role($user),
            'object_type' => 'user',
            'object_id' => (string) $user_id,
            'object_title' => $user->user_login,
            'source' => 'core',
            'message' => __('User profile updated.', 'king-addons'),
        ]);
    }

    public function log_user_deleted(int $user_id): void
    {
        $user = get_userdata($user_id);
        $user_login = $user ? $user->user_login : '';

        $this->log_event([
            'event_key' => 'user.deleted',
            'severity' => 'warning',
            'user_id' => $user_id,
            'user_login' => $user_login,
            'user_role' => $user ? $this->get_user_role($user) : '',
            'object_type' => 'user',
            'object_id' => (string) $user_id,
            'object_title' => $user_login,
            'source' => 'core',
            'message' => __('User account deleted.', 'king-addons'),
        ]);
    }

    public function log_user_role_changed(int $user_id, string $role, array $old_roles): void
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $severity = 'notice';
        if ($role === 'administrator' && !in_array('administrator', $old_roles, true)) {
            $severity = 'critical';
        }

        $this->log_event([
            'event_key' => 'user.role_changed',
            'severity' => $severity,
            'user_id' => $user_id,
            'user_login' => $user->user_login,
            'user_role' => $this->get_user_role($user),
            'object_type' => 'user',
            'object_id' => (string) $user_id,
            'object_title' => $user->user_login,
            'source' => 'core',
            'message' => __('User role changed.', 'king-addons'),
            'data' => [
                'old_roles' => $old_roles,
                'new_role' => $role,
            ],
        ]);
    }

    public function log_post_saved(int $post_id, WP_Post $post, bool $update): void
    {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if ($post->post_status === 'auto-draft') {
            return;
        }

        $event_key = $update ? 'content.updated' : 'content.created';
        $severity = $update ? 'notice' : 'info';

        $this->log_event([
            'event_key' => $event_key,
            'severity' => $severity,
            'object_type' => $post->post_type,
            'object_id' => (string) $post_id,
            'object_title' => $post->post_title ?: ('#' . $post_id),
            'source' => 'core',
            'message' => $update ? __('Content updated.', 'king-addons') : __('Content created.', 'king-addons'),
            'data' => [
                'status' => $post->post_status,
            ],
        ]);
    }

    public function log_post_trashed(int $post_id): void
    {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        $this->log_event([
            'event_key' => 'content.trashed',
            'severity' => 'notice',
            'object_type' => $post->post_type,
            'object_id' => (string) $post_id,
            'object_title' => $post->post_title ?: ('#' . $post_id),
            'source' => 'core',
            'message' => __('Content moved to trash.', 'king-addons'),
        ]);
    }

    public function log_post_restored(int $post_id): void
    {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        $this->log_event([
            'event_key' => 'content.restored',
            'severity' => 'notice',
            'object_type' => $post->post_type,
            'object_id' => (string) $post_id,
            'object_title' => $post->post_title ?: ('#' . $post_id),
            'source' => 'core',
            'message' => __('Content restored from trash.', 'king-addons'),
        ]);
    }

    public function log_post_deleted(int $post_id): void
    {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        $this->log_event([
            'event_key' => 'content.deleted',
            'severity' => 'warning',
            'object_type' => $post->post_type,
            'object_id' => (string) $post_id,
            'object_title' => $post->post_title ?: ('#' . $post_id),
            'source' => 'core',
            'message' => __('Content deleted permanently.', 'king-addons'),
        ]);
    }

    public function log_plugin_activated(string $plugin, bool $network_wide): void
    {
        $plugin_name = $this->get_plugin_name($plugin);

        $this->log_event([
            'event_key' => 'plugin.activated',
            'severity' => 'notice',
            'object_type' => 'plugin',
            'object_id' => $plugin,
            'object_title' => $plugin_name,
            'source' => 'core',
            'message' => __('Plugin activated.', 'king-addons'),
        ]);
    }

    public function log_plugin_deactivated(string $plugin, bool $network_wide): void
    {
        $plugin_name = $this->get_plugin_name($plugin);

        $this->log_event([
            'event_key' => 'plugin.deactivated',
            'severity' => 'notice',
            'object_type' => 'plugin',
            'object_id' => $plugin,
            'object_title' => $plugin_name,
            'source' => 'core',
            'message' => __('Plugin deactivated.', 'king-addons'),
        ]);
    }

    public function log_plugin_updated($upgrader, array $hook_extra): void
    {
        if (($hook_extra['action'] ?? '') !== 'update' || ($hook_extra['type'] ?? '') !== 'plugin') {
            return;
        }

        $plugins = $hook_extra['plugins'] ?? [];
        if (!is_array($plugins)) {
            return;
        }

        foreach ($plugins as $plugin) {
            $plugin_name = $this->get_plugin_name($plugin);
            $this->log_event([
                'event_key' => 'plugin.updated',
                'severity' => 'notice',
                'object_type' => 'plugin',
                'object_id' => (string) $plugin,
                'object_title' => $plugin_name,
                'source' => 'core',
                'message' => __('Plugin updated.', 'king-addons'),
            ]);
        }
    }

    public function log_theme_switched(string $new_name, \WP_Theme $new_theme, \WP_Theme $old_theme): void
    {
        $this->log_event([
            'event_key' => 'theme.switched',
            'severity' => 'notice',
            'object_type' => 'theme',
            'object_id' => (string) $new_theme->get_stylesheet(),
            'object_title' => $new_name,
            'source' => 'core',
            'message' => __('Theme switched.', 'king-addons'),
            'data' => [
                'previous' => $old_theme->get_stylesheet(),
            ],
        ]);
    }

    public function handle_custom_event(array $event): void
    {
        if (empty($event['event_key'])) {
            return;
        }

        $this->log_event($event);
    }

    private function log_event(array $event): void
    {
        if (empty($this->settings['enabled'])) {
            return;
        }

        $event_key = $event['event_key'] ?? '';
        if ($event_key === '' || !$this->is_event_allowed($event_key, (int) ($event['user_id'] ?? 0))) {
            return;
        }

        $user = null;
        if (!empty($event['user_id'])) {
            $user = get_userdata((int) $event['user_id']);
        }

        if (!$user) {
            $user = wp_get_current_user();
        }

        $user_id = $event['user_id'] ?? ($user && $user->ID ? $user->ID : null);
        $user_login = $event['user_login'] ?? ($user && $user->ID ? $user->user_login : '');
        $user_role = $event['user_role'] ?? ($user && $user->ID ? $this->get_user_role($user) : '');

        $data = $event['data'] ?? [];
        if (!is_array($data)) {
            $data = [];
        }

        $context = $event['context'] ?? $this->get_context();
        $source = $event['source'] ?? 'core';

        $log = [
            'created_at' => current_time('mysql', true),
            'event_key' => sanitize_text_field($event_key),
            'severity' => $this->sanitize_severity($event['severity'] ?? 'info'),
            'user_id' => $user_id ? (int) $user_id : null,
            'user_login' => $user_login ? sanitize_text_field($user_login) : null,
            'user_role' => $user_role ? sanitize_text_field($user_role) : null,
            'ip' => $this->get_ip_for_storage(),
            'user_agent' => $this->settings['store_user_agent'] ? $this->get_user_agent() : null,
            'object_type' => sanitize_key($event['object_type'] ?? ''),
            'object_id' => isset($event['object_id']) ? sanitize_text_field((string) $event['object_id']) : null,
            'object_title' => isset($event['object_title']) ? sanitize_text_field((string) $event['object_title']) : null,
            'source' => sanitize_text_field($source),
            'context' => sanitize_key($context),
            'message' => isset($event['message']) ? sanitize_text_field((string) $event['message']) : '',
            'data' => wp_json_encode($data),
            'checksum' => null,
            'chain_prev_checksum' => null,
        ];

        global $wpdb;

        $table = Activity_Log_DB::get_table();
        $wpdb->insert($table, $log, [
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        ]);
    }

    private function is_event_allowed(string $event_key, int $user_id): bool
    {
        $settings = $this->settings;

        if ($user_id > 0 && !empty($settings['exclude_user_ids']) && in_array($user_id, $settings['exclude_user_ids'], true)) {
            return false;
        }

        if (!empty($settings['exclude_roles']) && $user_id > 0) {
            $user = get_userdata($user_id);
            $role = $user ? $this->get_user_role($user) : '';
            if ($role !== '' && in_array($role, $settings['exclude_roles'], true)) {
                return false;
            }
        }

        if (!empty($settings['exclude_event_keys']) && in_array($event_key, $settings['exclude_event_keys'], true)) {
            return false;
        }

        $modules = $settings['modules'] ?? [];
        $prefix = strstr($event_key, '.', true);

        $module_map = [
            'auth' => 'auth',
            'content' => 'content',
            'user' => 'users',
            'plugin' => 'plugins_themes',
            'theme' => 'plugins_themes',
            'settings' => 'settings',
            'woocommerce' => 'woocommerce',
            'kng' => 'king_addons',
        ];

        if ($prefix && isset($module_map[$prefix])) {
            $module_key = $module_map[$prefix];
            if (isset($modules[$module_key]) && !$modules[$module_key]) {
                return false;
            }
        }

        return true;
    }

    private function maybe_send_failed_login_alert(): void
    {
        $alerts = $this->settings['alerts'] ?? [];
        if (empty($alerts['failed_login_enabled'])) {
            return;
        }

        $threshold = max(1, (int) ($alerts['failed_login_threshold'] ?? 5));
        $window = max(1, (int) ($alerts['failed_login_window'] ?? 10));
        $emails = trim((string) ($alerts['failed_login_emails'] ?? ''));
        if ($emails === '') {
            $emails = get_option('admin_email');
        }

        $last_sent = (int) get_option('king_addons_activity_log_failed_login_last', 0);
        if ($last_sent > 0 && (time() - $last_sent) < ($window * 60)) {
            return;
        }

        global $wpdb;
        $table = Activity_Log_DB::get_table();
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($window * 60));
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE event_key = %s AND created_at >= %s",
            'auth.login.failed',
            $cutoff
        ));

        if ($count < $threshold) {
            return;
        }

        $subject = __('Failed login alert', 'king-addons');
        $message = sprintf(
            __('There have been %d failed login attempts in the last %d minutes.', 'king-addons'),
            $count,
            $window
        );

        wp_mail($emails, $subject, $message);
        update_option('king_addons_activity_log_failed_login_last', time());
    }

    public function get_logs(array $filters, int $page, int $per_page): array
    {
        global $wpdb;

        $table = Activity_Log_DB::get_table();
        [$where_sql, $params] = $this->build_where_clause($filters);

        $sql = "SELECT * FROM {$table} {$where_sql} ORDER BY created_at DESC";

        if ($per_page > 0) {
            $offset = max(0, ($page - 1) * $per_page);
            $sql .= $wpdb->prepare(' LIMIT %d OFFSET %d', $per_page, $offset);
        }

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql);
    }

    public function get_logs_count(array $filters): int
    {
        global $wpdb;

        $table = Activity_Log_DB::get_table();
        [$where_sql, $params] = $this->build_where_clause($filters);

        $sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return (int) $wpdb->get_var($sql);
    }

    public function get_dashboard_stats(): array
    {
        global $wpdb;

        $table = Activity_Log_DB::get_table();
        $now = time();

        $last_24 = gmdate('Y-m-d H:i:s', $now - DAY_IN_SECONDS);
        $last_7 = gmdate('Y-m-d H:i:s', $now - (7 * DAY_IN_SECONDS));

        $total_24h = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
            $last_24
        ));

        $failed_24h = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE event_key = %s AND created_at >= %s",
            'auth.login.failed',
            $last_24
        ));

        $critical_7d = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE severity = %s AND created_at >= %s",
            'critical',
            $last_7
        ));

        $unique_users_7d = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$table} WHERE created_at >= %s AND user_id IS NOT NULL",
            $last_7
        ));

        return [
            'total_24h' => $total_24h,
            'failed_24h' => $failed_24h,
            'critical_7d' => $critical_7d,
            'unique_users_7d' => $unique_users_7d,
        ];
    }

    public function get_events_over_time(int $days = 14): array
    {
        global $wpdb;

        $table = Activity_Log_DB::get_table();
        $end = strtotime('today', time());
        $start = $end - (($days - 1) * DAY_IN_SECONDS);

        $start_sql = gmdate('Y-m-d H:i:s', $start);
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) AS day, COUNT(*) AS total
             FROM {$table}
             WHERE created_at >= %s
             GROUP BY day
             ORDER BY day ASC",
            $start_sql
        ));

        $map = [];
        foreach ($rows as $row) {
            $map[$row->day] = (int) $row->total;
        }

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = gmdate('Y-m-d', $start + ($i * DAY_IN_SECONDS));
            $series[] = [
                'date' => $day,
                'count' => $map[$day] ?? 0,
            ];
        }

        return $series;
    }

    public function get_top_events(int $limit = 6): array
    {
        global $wpdb;

        $table = Activity_Log_DB::get_table();
        $last_7 = gmdate('Y-m-d H:i:s', time() - (7 * DAY_IN_SECONDS));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT event_key, COUNT(*) AS total
             FROM {$table}
             WHERE created_at >= %s
             GROUP BY event_key
             ORDER BY total DESC
             LIMIT %d",
            $last_7,
            $limit
        ));
    }

    public function get_top_users(int $limit = 6): array
    {
        global $wpdb;

        $table = Activity_Log_DB::get_table();
        $last_7 = gmdate('Y-m-d H:i:s', time() - (7 * DAY_IN_SECONDS));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT user_login, COUNT(*) AS total
             FROM {$table}
             WHERE created_at >= %s AND user_login IS NOT NULL AND user_login != ''
             GROUP BY user_login
             ORDER BY total DESC
             LIMIT %d",
            $last_7,
            $limit
        ));
    }

    public function get_recent_users_for_filter(): array
    {
        global $wpdb;

        $table = Activity_Log_DB::get_table();
        $rows = $wpdb->get_results(
            "SELECT DISTINCT user_id, user_login
             FROM {$table}
             WHERE user_id IS NOT NULL AND user_login IS NOT NULL
             ORDER BY created_at DESC
             LIMIT 50"
        );

        $users = [];
        foreach ($rows as $row) {
            $users[$row->user_id] = $row->user_login;
        }

        return $users;
    }

    private function build_where_clause(array $filters): array
    {
        global $wpdb;

        $where = 'WHERE 1=1';
        $params = [];

        if (!empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where .= " AND (event_key LIKE %s OR message LIKE %s OR object_title LIKE %s OR user_login LIKE %s OR ip LIKE %s)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($filters['event_key'])) {
            $where .= ' AND event_key = %s';
            $params[] = $filters['event_key'];
        }

        if (!empty($filters['severity'])) {
            $where .= ' AND severity = %s';
            $params[] = $filters['severity'];
        }

        if (!empty($filters['user_id'])) {
            $where .= ' AND user_id = %d';
            $params[] = (int) $filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $where .= ' AND created_at >= %s';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where .= ' AND created_at <= %s';
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['ip']) && $this->is_pro()) {
            $where .= ' AND ip = %s';
            $params[] = $filters['ip'];
        }

        return [$where, $params];
    }

    private function get_filters_from_request(): array
    {
        $filters = [
            'search' => isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '',
            'event_key' => isset($_GET['event_key']) ? sanitize_text_field(wp_unslash($_GET['event_key'])) : '',
            'severity' => isset($_GET['severity']) ? sanitize_text_field(wp_unslash($_GET['severity'])) : '',
            'user_id' => isset($_GET['user_id']) ? absint($_GET['user_id']) : 0,
            'date_from' => isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '',
            'date_to' => isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '',
            'ip' => isset($_GET['ip']) ? sanitize_text_field(wp_unslash($_GET['ip'])) : '',
        ];

        if ($filters['date_from'] !== '') {
            $filters['date_from'] = $this->normalize_date($filters['date_from'], false);
        }

        if ($filters['date_to'] !== '') {
            $filters['date_to'] = $this->normalize_date($filters['date_to'], true);
        }

        return $filters;
    }

    private function apply_retention_limit(array $filters): array
    {
        $days = $this->get_retention_days();
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));

        if (empty($filters['date_from']) || strtotime($filters['date_from']) < strtotime($cutoff)) {
            $filters['date_from'] = $cutoff;
        }

        return $filters;
    }

    private function normalize_date(string $date, bool $end_of_day): string
    {
        $date = preg_replace('/[^0-9\\-]/', '', $date);
        $time = $end_of_day ? '23:59:59' : '00:00:00';
        $local = $date . ' ' . $time;
        return get_gmt_from_date($local, 'Y-m-d H:i:s');
    }

    private function get_context(): string
    {
        if (defined('WP_CLI') && WP_CLI) {
            return 'wp_cli';
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return 'rest';
        }

        if (wp_doing_cron()) {
            return 'cron';
        }

        return is_admin() ? 'admin' : 'frontend';
    }

    private function get_user_role($user): string
    {
        $roles = $user->roles ?? [];
        return $roles ? (string) $roles[0] : '';
    }

    private function get_user_agent(): ?string
    {
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        return $agent !== '' ? $agent : null;
    }

    private function get_ip_for_storage(): ?string
    {
        $ip = $this->get_client_ip();
        if ($ip === '') {
            return null;
        }

        $storage = $this->settings['ip_storage'] ?? 'full';
        if ($storage === 'masked') {
            return $this->mask_ip($ip);
        }

        if ($storage === 'hashed') {
            return hash('sha256', $ip);
        }

        return $ip;
    }

    private function get_client_ip(): string
    {
        $ip = '';

        if (!empty($this->settings['trust_proxy_headers']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = explode(',', (string) wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
            foreach ($forwarded as $candidate) {
                $candidate = trim($candidate);
                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    $ip = $candidate;
                    break;
                }
            }
        }

        if ($ip === '' && !empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            $ip = (string) $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    private function mask_ip(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $parts = array_pad($parts, 8, '0000');
            $parts[7] = '0000';
            $parts[6] = '0000';
            return implode(':', $parts);
        }

        return $ip;
    }

    private function sanitize_severity(string $severity): string
    {
        $allowed = ['info', 'notice', 'warning', 'critical'];
        return in_array($severity, $allowed, true) ? $severity : 'info';
    }

    public function sanitize_settings($settings): array
    {
        $defaults = $this->get_default_settings();

        if (!is_array($settings)) {
            $settings = [];
        }

        $modules = $settings['modules'] ?? [];
        $clean_modules = [];
        foreach ($defaults['modules'] as $key => $value) {
            $clean_modules[$key] = !empty($modules[$key]);
        }

        if (!$this->is_pro()) {
            $clean_modules['settings'] = false;
            $clean_modules['woocommerce'] = false;
            $clean_modules['king_addons'] = false;
        }

        $clean = [
            'enabled' => !empty($settings['enabled']),
            'timezone' => in_array($settings['timezone'] ?? 'site', ['site', 'utc'], true) ? $settings['timezone'] : 'site',
            'rows_per_page' => max(10, min(200, absint($settings['rows_per_page'] ?? $defaults['rows_per_page']))),
            'retention_days' => max(1, absint($settings['retention_days'] ?? $defaults['retention_days'])),
            'ip_storage' => in_array($settings['ip_storage'] ?? 'full', ['full', 'masked', 'hashed'], true) ? $settings['ip_storage'] : 'full',
            'store_user_agent' => !empty($settings['store_user_agent']),
            'trust_proxy_headers' => !empty($settings['trust_proxy_headers']),
            'view_logs_capability' => $this->sanitize_view_logs_capability($settings['view_logs_capability'] ?? $defaults['view_logs_capability']),
            'modules' => $clean_modules,
            'exclude_roles' => $this->sanitize_list($settings['exclude_roles'] ?? ''),
            'exclude_user_ids' => $this->sanitize_id_list($settings['exclude_user_ids'] ?? ''),
            'exclude_event_keys' => $this->sanitize_event_keys_list($settings['exclude_event_keys'] ?? ''),
            'alerts' => $this->sanitize_alerts($settings['alerts'] ?? []),
        ];

        if (!$this->is_pro() && $clean['retention_days'] > 14) {
            $clean['retention_days'] = 14;
        }

        if (!$this->is_pro()) {
            $clean['view_logs_capability'] = 'manage_options';
        }

        return $clean;
    }

    private function sanitize_view_logs_capability($capability): string
    {
        $capability = sanitize_key((string) $capability);

        $allowed = [
            'manage_options',
            'edit_pages',
            'edit_posts',
            'read',
        ];

        return in_array($capability, $allowed, true) ? $capability : 'manage_options';
    }

    private function sanitize_alerts(array $alerts): array
    {
        return [
            'failed_login_enabled' => !empty($alerts['failed_login_enabled']),
            'failed_login_threshold' => max(1, min(50, absint($alerts['failed_login_threshold'] ?? 5))),
            'failed_login_window' => max(1, min(60, absint($alerts['failed_login_window'] ?? 10))),
            'failed_login_emails' => sanitize_text_field((string) ($alerts['failed_login_emails'] ?? '')),
        ];
    }

    private function sanitize_list($value): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = explode(',', (string) $value);
        }

        $items = array_map('trim', $items);
        $items = array_filter($items);
        $items = array_map('sanitize_key', $items);
        $items = array_filter($items);

        return array_values(array_unique($items));
    }

    private function sanitize_id_list($value): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = explode(',', (string) $value);
        }

        $ids = array_map('absint', $items);
        $ids = array_filter($ids);

        return array_values(array_unique($ids));
    }

    private function sanitize_event_keys_list($value): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = explode(',', (string) $value);
        }

        $items = array_map('trim', $items);
        $items = array_filter($items);
        $items = array_map(function ($item) {
            $item = strtolower($item);
            return preg_replace('/[^a-z0-9._-]/', '', $item);
        }, $items);
        $items = array_filter($items);

        return array_values(array_unique($items));
    }

    public function get_default_settings(): array
    {
        return [
            'enabled' => false,
            'timezone' => 'site',
            'rows_per_page' => 20,
            'retention_days' => 14,
            'ip_storage' => 'full',
            'store_user_agent' => true,
            'trust_proxy_headers' => false,
            'view_logs_capability' => 'manage_options',
            'modules' => [
                'auth' => true,
                'content' => true,
                'users' => true,
                'plugins_themes' => true,
                'settings' => false,
                'woocommerce' => false,
                'king_addons' => false,
            ],
            'exclude_roles' => [],
            'exclude_user_ids' => [],
            'exclude_event_keys' => [],
            'alerts' => [
                'failed_login_enabled' => false,
                'failed_login_threshold' => 5,
                'failed_login_window' => 10,
                'failed_login_emails' => '',
            ],
        ];
    }

    private function get_view_logs_capability(): string
    {
        $capability = $this->settings['view_logs_capability'] ?? 'manage_options';
        return $this->sanitize_view_logs_capability($capability);
    }

    public function get_settings(): array
    {
        $defaults = $this->get_default_settings();
        $saved = get_option(self::OPTION_NAME, []);

        $settings = wp_parse_args($saved, $defaults);
        $settings['modules'] = wp_parse_args($settings['modules'] ?? [], $defaults['modules']);

        if (!isset($settings['exclude_roles']) || !is_array($settings['exclude_roles'])) {
            $settings['exclude_roles'] = $defaults['exclude_roles'];
        }
        if (!isset($settings['exclude_user_ids']) || !is_array($settings['exclude_user_ids'])) {
            $settings['exclude_user_ids'] = $defaults['exclude_user_ids'];
        }
        if (!isset($settings['exclude_event_keys']) || !is_array($settings['exclude_event_keys'])) {
            $settings['exclude_event_keys'] = $defaults['exclude_event_keys'];
        }
        if (!isset($settings['alerts']) || !is_array($settings['alerts'])) {
            $settings['alerts'] = $defaults['alerts'];
        }

        return $settings;
    }

    public function format_time(string $gmt): string
    {
        $timestamp = strtotime($gmt . ' UTC');
        if (($this->settings['timezone'] ?? 'site') === 'utc') {
            return gmdate('Y-m-d H:i:s', $timestamp);
        }

        return wp_date('Y-m-d H:i:s', $timestamp);
    }

    public function get_event_labels(): array
    {
        return [
            'auth.login.success' => __('Login success', 'king-addons'),
            'auth.login.failed' => __('Login failed', 'king-addons'),
            'auth.logout' => __('Logout', 'king-addons'),
            'user.created' => __('User created', 'king-addons'),
            'user.updated' => __('User updated', 'king-addons'),
            'user.deleted' => __('User deleted', 'king-addons'),
            'user.role_changed' => __('Role changed', 'king-addons'),
            'content.created' => __('Content created', 'king-addons'),
            'content.updated' => __('Content updated', 'king-addons'),
            'content.trashed' => __('Content trashed', 'king-addons'),
            'content.restored' => __('Content restored', 'king-addons'),
            'content.deleted' => __('Content deleted', 'king-addons'),
            'plugin.activated' => __('Plugin activated', 'king-addons'),
            'plugin.deactivated' => __('Plugin deactivated', 'king-addons'),
            'plugin.updated' => __('Plugin updated', 'king-addons'),
            'theme.switched' => __('Theme switched', 'king-addons'),
            'settings.option.updated' => __('Setting updated', 'king-addons'),
            'settings.option.added' => __('Setting added', 'king-addons'),
            'settings.option.deleted' => __('Setting deleted', 'king-addons'),
            'woocommerce.order.created' => __('Order created', 'king-addons'),
            'woocommerce.order.status_changed' => __('Order status changed', 'king-addons'),
            'woocommerce.product.stock_changed' => __('Product stock updated', 'king-addons'),
            'kng.option.updated' => __('King Addons setting updated', 'king-addons'),
            'kng.option.added' => __('King Addons setting added', 'king-addons'),
            'kng.option.deleted' => __('King Addons setting deleted', 'king-addons'),
        ];
    }

    private function get_plugin_name(string $plugin): string
    {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $file = WP_PLUGIN_DIR . '/' . $plugin;
        if (file_exists($file)) {
            $data = get_plugin_data($file, false, false);
            if (!empty($data['Name'])) {
                return $data['Name'];
            }
        }

        return $plugin;
    }

    private function get_retention_days(): int
    {
        $days = (int) ($this->settings['retention_days'] ?? 14);
        if (!$this->is_pro()) {
            $days = min($days, 14);
        }

        return max(1, $days);
    }

    private function escape_csv_value(string $value): string
    {
        if (preg_match('/^[=+\\-@]/', $value)) {
            return "'" . $value;
        }
        return $value;
    }

    private function is_pro(): bool
    {
        if (function_exists('king_addons_can_use_pro')) {
            return king_addons_can_use_pro();
        }

        if (!function_exists('king_addons_freemius')) {
            return false;
        }

        $fs = king_addons_freemius();
        if (!is_object($fs)) {
            return false;
        }

        if (method_exists($fs, 'can_use_premium_code__premium_only')) {
            return (bool) $fs->can_use_premium_code__premium_only();
        }

        if (method_exists($fs, 'can_use_premium_code')) {
            return (bool) $fs->can_use_premium_code();
        }

        return false;
    }

    private function redirect_with_message(string $view, string $message): void
    {
        $args = [
            'page' => 'king-addons-activity-log',
            'view' => $view,
            'message' => $message,
        ];

        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }
}
