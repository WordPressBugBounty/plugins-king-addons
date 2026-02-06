<?php
/**
 * Smart Links extension.
 *
 * @package King_Addons
 */

namespace King_Addons\Smart_Links;

use WP_Post;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/Smart_Links_DB.php';
require_once __DIR__ . '/Smart_Links_Settings.php';
require_once __DIR__ . '/Smart_Links_Service.php';

class Smart_Links
{
    public const POST_TYPE = 'kng_short_link';
    public const META_SLUG = '_kng_slug';
    public const META_DESTINATION = '_kng_destination_url';
    public const META_STATUS = '_kng_status';
    public const META_TAGS = '_kng_tags';
    public const META_NOTES = '_kng_notes';
    public const META_UTM = '_kng_utm_defaults';
    public const META_REDIRECT = '_kng_redirect_type';
    public const META_ALLOW_QUERY = '_kng_allow_query';
    public const META_CLICKS = '_kng_clicks_total';
    public const META_UNIQUE_CLICKS = '_kng_unique_clicks_total';

    private static ?Smart_Links $instance = null;

    public static function instance(): Smart_Links
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('template_redirect', [$this, 'handle_redirect']);
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_flush_rewrite_rules']);

        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        add_action('admin_post_kng_smart_links_save', [$this, 'handle_save_link']);
        add_action('admin_post_kng_smart_links_delete', [$this, 'handle_delete_link']);
        add_action('admin_post_kng_smart_links_duplicate', [$this, 'handle_duplicate_link']);
        add_action('admin_post_kng_smart_links_bulk', [$this, 'handle_bulk_action']);
        add_action('admin_post_kng_smart_links_export', [$this, 'handle_export_csv']);
        add_action('admin_post_kng_smart_links_import', [$this, 'handle_import_csv']);
        add_action('admin_post_kng_smart_links_toggle', [$this, 'handle_toggle_status']);
    }

    public function register_post_type(): void
    {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Smart Links', 'king-addons'),
                'singular_name' => __('Smart Link', 'king-addons'),
            ],
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'supports' => ['title'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
    }

    public function register_rewrite_rules(): void
    {
        $base_path = trim(Smart_Links_Settings::get_base_path(), '/');
        if ($base_path === '') {
            return;
        }

        add_rewrite_tag('%kng_go%', '([0-1])');
        add_rewrite_tag('%kng_slug%', '([^&]+)');

        $pattern = '^' . preg_quote($base_path, '/') . '/([^/]+)/?$';
        add_rewrite_rule($pattern, 'index.php?kng_go=1&kng_slug=$matches[1]', 'top');
    }

    public function register_query_vars(array $vars): array
    {
        $vars[] = 'kng_go';
        $vars[] = 'kng_slug';
        return $vars;
    }

    public function maybe_create_tables(): void
    {
        Smart_Links_DB::maybe_create_tables();
    }

    public function maybe_flush_rewrite_rules(): void
    {
        if (!get_option('king_addons_smart_links_rewrite_version')) {
            update_option('king_addons_smart_links_rewrite_version', '1');
            update_option('king_addons_smart_links_flush_rewrite', 1);
        }

        if (!get_option('king_addons_smart_links_flush_rewrite')) {
            return;
        }

        delete_option('king_addons_smart_links_flush_rewrite');
        flush_rewrite_rules();
    }

    public function handle_redirect(): void
    {
        $go = get_query_var('kng_go');
        if (empty($go)) {
            return;
        }

        $slug = (string) get_query_var('kng_slug');
        $service = new Smart_Links_Service();
        $link = $service->get_link_by_slug($slug);

        if (!$link) {
            $this->render_not_found();
        }

        $status = get_post_meta($link->ID, self::META_STATUS, true);
        if ($status === 'disabled') {
            $this->render_not_found();
        }

        $destination = (string) get_post_meta($link->ID, self::META_DESTINATION, true);
        $destination = $service->sanitize_destination_url($destination);
        if ($destination === '') {
            $this->render_not_found();
        }

        $settings = Smart_Links_Settings::get_settings();
        $utm = get_post_meta($link->ID, self::META_UTM, true);
        if (!is_array($utm)) {
            $utm = [];
        }

        $destination = $service->apply_utm($destination, $utm);

        $allow_query = get_post_meta($link->ID, self::META_ALLOW_QUERY, true);
        $allow_query = $allow_query === '' ? !empty($settings['pass_query_params']) : (bool) $allow_query;
        if ($allow_query) {
            $destination = $service->merge_query_params($destination, $_GET, $settings);
        }

        $redirect_type = (string) get_post_meta($link->ID, self::META_REDIRECT, true);
        if (!in_array($redirect_type, ['301', '302'], true)) {
            $redirect_type = (string) $settings['default_redirect_type'];
        }

        $this->log_click($link->ID, $destination);

        wp_redirect($destination, (int) $redirect_type);
        exit;
    }

    public function register_admin_menu(): void
    {
        add_submenu_page(
            'king-addons',
            __('Smart Links', 'king-addons'),
            __('Smart Links', 'king-addons'),
            'manage_options',
            'king-addons-smart-links',
            [$this, 'render_admin_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'king_addons_smart_links',
            'king_addons_smart_links_settings',
            [
                'type' => 'array',
                'sanitize_callback' => [Smart_Links_Settings::class, 'sanitize'],
                'default' => Smart_Links_Settings::defaults(),
            ]
        );
    }

    public function enqueue_admin_assets(string $hook): void
    {
        if ($hook !== 'king-addons_page_king-addons-smart-links') {
            return;
        }

        $shared_css = KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css';
        $shared_path = KING_ADDONS_PATH . 'includes/admin/layouts/shared/admin-v3-styles.css';
        $shared_version = file_exists($shared_path) ? filemtime($shared_path) : KING_ADDONS_VERSION;

        wp_enqueue_style('king-addons-admin-v3', $shared_css, [], $shared_version);

        $admin_css = KING_ADDONS_URL . 'includes/extensions/Smart_Links/assets/admin.css';
        $admin_path = KING_ADDONS_PATH . 'includes/extensions/Smart_Links/assets/admin.css';
        $admin_version = file_exists($admin_path) ? filemtime($admin_path) : KING_ADDONS_VERSION;
        wp_enqueue_style('king-addons-smart-links', $admin_css, ['king-addons-admin-v3'], $admin_version);

        $admin_js = KING_ADDONS_URL . 'includes/extensions/Smart_Links/assets/admin.js';
        $admin_js_path = KING_ADDONS_PATH . 'includes/extensions/Smart_Links/assets/admin.js';
        $admin_js_version = file_exists($admin_js_path) ? filemtime($admin_js_path) : KING_ADDONS_VERSION;

        wp_enqueue_script('king-addons-smart-links', $admin_js, ['jquery'], $admin_js_version, true);
        wp_localize_script('king-addons-smart-links', 'KNGSmartLinks', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'themeNonce' => wp_create_nonce('king_addons_dashboard_ui'),
            'homeUrl' => home_url('/'),
            'basePath' => Smart_Links_Settings::get_base_path(),
        ]);
    }

    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'dashboard';
        $settings = Smart_Links_Settings::get_settings();
        $service = new Smart_Links_Service();
        $is_pro = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();

        include __DIR__ . '/templates/admin-page.php';
    }

    public function handle_save_link(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_smart_links_save');

        $service = new Smart_Links_Service();
        $settings = Smart_Links_Settings::get_settings();

        $link_id = isset($_POST['link_id']) ? absint($_POST['link_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $destination = isset($_POST['destination_url']) ? wp_unslash($_POST['destination_url']) : '';
        $destination = $service->sanitize_destination_url($destination);

        if ($destination === '') {
            $this->redirect_with_message('add', 'error_invalid_url', $link_id);
        }

        $slug_mode = isset($_POST['slug_mode']) ? sanitize_key($_POST['slug_mode']) : 'auto';
        $manual_slug = isset($_POST['slug']) ? sanitize_text_field(wp_unslash($_POST['slug'])) : '';
        $slug = '';

        if ($slug_mode === 'manual' && !empty($settings['allow_manual_slug'])) {
            $slug = $service->sanitize_slug($manual_slug);
        }

        if ($slug === '' && $link_id > 0) {
            $existing_slug = (string) get_post_meta($link_id, self::META_SLUG, true);
            $slug = $service->sanitize_slug($existing_slug);
        }

        if ($slug === '') {
            $slug = $service->generate_slug((int) $settings['default_slug_length']);
        }

        $slug = $service->ensure_unique_slug($slug, $link_id);

        $status = !empty($_POST['status']) && $_POST['status'] === 'disabled' ? 'disabled' : 'active';
        $tags_input = isset($_POST['tags']) ? sanitize_text_field(wp_unslash($_POST['tags'])) : '';
        $tags = $service->parse_tags($tags_input);
        $notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';

        $utm_enabled = !empty($_POST['utm_enabled']);
        $utm_defaults = [
            'enabled' => $utm_enabled,
            'utm_source' => isset($_POST['utm_source']) ? sanitize_text_field(wp_unslash($_POST['utm_source'])) : '',
            'utm_medium' => isset($_POST['utm_medium']) ? sanitize_text_field(wp_unslash($_POST['utm_medium'])) : '',
            'utm_campaign' => isset($_POST['utm_campaign']) ? sanitize_text_field(wp_unslash($_POST['utm_campaign'])) : '',
            'utm_term' => isset($_POST['utm_term']) ? sanitize_text_field(wp_unslash($_POST['utm_term'])) : '',
            'utm_content' => isset($_POST['utm_content']) ? sanitize_text_field(wp_unslash($_POST['utm_content'])) : '',
        ];

        $redirect_type = isset($_POST['redirect_type']) ? sanitize_text_field(wp_unslash($_POST['redirect_type'])) : '';
        $redirect_type = in_array($redirect_type, ['301', '302'], true) ? $redirect_type : (string) $settings['default_redirect_type'];
        $allow_query = !empty($_POST['allow_query']);

        $post_args = [
            'post_title' => $title !== '' ? $title : $slug,
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
        ];

        if ($link_id > 0) {
            $post_args['ID'] = $link_id;
        }

        $result = wp_insert_post($post_args, true);
        if (is_wp_error($result)) {
            $this->redirect_with_message('add', 'error_save', $link_id);
        }

        $link_id = (int) $result;

        update_post_meta($link_id, self::META_SLUG, $slug);
        update_post_meta($link_id, self::META_DESTINATION, $destination);
        update_post_meta($link_id, self::META_STATUS, $status);
        update_post_meta($link_id, self::META_TAGS, $tags);
        update_post_meta($link_id, self::META_NOTES, $notes);
        update_post_meta($link_id, self::META_UTM, $utm_defaults);
        update_post_meta($link_id, self::META_REDIRECT, $redirect_type);
        update_post_meta($link_id, self::META_ALLOW_QUERY, $allow_query ? '1' : '0');

        $message = $link_id > 0 && isset($_POST['link_id']) && absint($_POST['link_id']) > 0 ? 'updated' : 'created';
        $this->redirect_with_message('add', $message, $link_id);
    }

    public function handle_delete_link(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        $link_id = isset($_GET['link_id']) ? absint($_GET['link_id']) : 0;
        check_admin_referer('kng_smart_links_delete_' . $link_id);

        if ($link_id > 0) {
            wp_delete_post($link_id, true);
        }

        $this->redirect_with_message('links', 'deleted');
    }

    public function handle_duplicate_link(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        $link_id = isset($_GET['link_id']) ? absint($_GET['link_id']) : 0;
        check_admin_referer('kng_smart_links_duplicate_' . $link_id);

        $service = new Smart_Links_Service();
        $link = $service->get_link($link_id);
        if (!$link) {
            $this->redirect_with_message('links', 'error_not_found');
        }

        $slug = get_post_meta($link_id, self::META_SLUG, true);
        $slug = $service->ensure_unique_slug((string) $slug, 0);
        $destination = get_post_meta($link_id, self::META_DESTINATION, true);

        $new_id = wp_insert_post([
            'post_title' => $link->post_title . ' (Copy)',
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
        ]);

        if (is_wp_error($new_id)) {
            $this->redirect_with_message('links', 'error_save');
        }

        update_post_meta($new_id, self::META_SLUG, $slug);
        update_post_meta($new_id, self::META_DESTINATION, $destination);
        update_post_meta($new_id, self::META_STATUS, get_post_meta($link_id, self::META_STATUS, true));
        update_post_meta($new_id, self::META_TAGS, get_post_meta($link_id, self::META_TAGS, true));
        update_post_meta($new_id, self::META_NOTES, get_post_meta($link_id, self::META_NOTES, true));
        update_post_meta($new_id, self::META_UTM, get_post_meta($link_id, self::META_UTM, true));
        update_post_meta($new_id, self::META_REDIRECT, get_post_meta($link_id, self::META_REDIRECT, true));
        update_post_meta($new_id, self::META_ALLOW_QUERY, get_post_meta($link_id, self::META_ALLOW_QUERY, true));

        $this->redirect_with_message('links', 'duplicated');
    }

    public function handle_toggle_status(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        $link_id = isset($_GET['link_id']) ? absint($_GET['link_id']) : 0;
        $status = isset($_GET['status']) ? sanitize_key($_GET['status']) : 'active';
        check_admin_referer('kng_smart_links_toggle_' . $link_id);

        if ($link_id > 0 && in_array($status, ['active', 'disabled'], true)) {
            update_post_meta($link_id, self::META_STATUS, $status);
        }

        $this->redirect_with_message('links', 'updated');
    }

    public function handle_bulk_action(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_smart_links_bulk');

        $action = isset($_POST['bulk_action']) ? sanitize_key($_POST['bulk_action']) : '';
        $link_ids = isset($_POST['link_ids']) ? array_map('absint', (array) $_POST['link_ids']) : [];
        $link_ids = array_filter($link_ids);

        if (empty($action) || empty($link_ids)) {
            $this->redirect_with_message('links', 'error_bulk');
        }

        if ($action === 'export') {
            $this->output_csv($link_ids);
        }

        if (in_array($action, ['enable', 'disable'], true)) {
            $status = $action === 'enable' ? 'active' : 'disabled';
            foreach ($link_ids as $link_id) {
                update_post_meta($link_id, self::META_STATUS, $status);
            }
            $this->redirect_with_message('links', 'updated');
        }

        if ($action === 'delete') {
            foreach ($link_ids as $link_id) {
                wp_delete_post($link_id, true);
            }
            $this->redirect_with_message('links', 'deleted');
        }

        if ($action === 'add_tag') {
            $tag = isset($_POST['bulk_tag']) ? sanitize_text_field(wp_unslash($_POST['bulk_tag'])) : '';
            if ($tag !== '') {
                foreach ($link_ids as $link_id) {
                    $tags = (array) get_post_meta($link_id, self::META_TAGS, true);
                    $tags[] = $tag;
                    $tags = array_values(array_unique(array_filter(array_map('sanitize_text_field', $tags))));
                    update_post_meta($link_id, self::META_TAGS, $tags);
                }
                $this->redirect_with_message('links', 'updated');
            }
        }

        $this->redirect_with_message('links', 'error_bulk');
    }

    public function handle_export_csv(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_smart_links_export');

        $link_id = isset($_GET['link_id']) ? absint($_GET['link_id']) : 0;
        $link_ids = $link_id > 0 ? [$link_id] : [];
        $this->output_csv($link_ids);
    }

    public function handle_import_csv(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_smart_links_import');

        if (empty($_FILES['import_file']) || !isset($_FILES['import_file']['tmp_name'])) {
            $this->redirect_with_message('import-export', 'error_import');
        }

        $file = $_FILES['import_file'];
        if (!empty($file['error'])) {
            $this->redirect_with_message('import-export', 'error_import');
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            $this->redirect_with_message('import-export', 'error_import');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $this->redirect_with_message('import-export', 'error_import');
        }

        $map = array_flip(array_map('trim', $header));
        $service = new Smart_Links_Service();
        $settings = Smart_Links_Settings::get_settings();
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $destination = $row[$map['destination_url'] ?? -1] ?? '';
            $destination = $service->sanitize_destination_url((string) $destination);
            if ($destination === '') {
                continue;
            }

            $slug = $row[$map['slug'] ?? -1] ?? '';
            $slug = $service->sanitize_slug((string) $slug);
            if ($slug === '') {
                $slug = $service->generate_slug((int) $settings['default_slug_length']);
            }
            $slug = $service->ensure_unique_slug($slug);

            $title = $row[$map['title'] ?? -1] ?? '';
            $title = sanitize_text_field((string) $title);

            $tags = $row[$map['tags'] ?? -1] ?? '';
            $tags = $service->parse_tags((string) $tags);

            $new_id = wp_insert_post([
                'post_title' => $title !== '' ? $title : $slug,
                'post_type' => self::POST_TYPE,
                'post_status' => 'publish',
            ]);

            if (is_wp_error($new_id)) {
                continue;
            }

            update_post_meta($new_id, self::META_SLUG, $slug);
            update_post_meta($new_id, self::META_DESTINATION, $destination);
            update_post_meta($new_id, self::META_STATUS, 'active');
            update_post_meta($new_id, self::META_TAGS, $tags);

            $imported++;
        }

        fclose($handle);

        $this->redirect_with_message('import-export', $imported > 0 ? 'imported' : 'error_import');
    }

    private function output_csv(array $link_ids = []): void
    {
        $args = [
            'post_type' => self::POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => -1,
        ];

        if (!empty($link_ids)) {
            $args['post__in'] = $link_ids;
            $args['orderby'] = 'post__in';
        }

        $query = new WP_Query($args);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=smart-links-' . gmdate('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'id',
            'title',
            'slug',
            'short_url',
            'destination_url',
            'status',
            'tags',
            'created',
            'clicks',
            'unique_clicks',
        ]);

        $service = new Smart_Links_Service();

        foreach ($query->posts as $post) {
            $slug = (string) get_post_meta($post->ID, self::META_SLUG, true);
            $short_url = $service->build_short_url($slug);
            $tags = (array) get_post_meta($post->ID, self::META_TAGS, true);

            fputcsv($output, [
                $post->ID,
                $post->post_title,
                $slug,
                $short_url,
                (string) get_post_meta($post->ID, self::META_DESTINATION, true),
                (string) get_post_meta($post->ID, self::META_STATUS, true),
                implode(', ', $tags),
                $post->post_date,
                (int) get_post_meta($post->ID, self::META_CLICKS, true),
                (int) get_post_meta($post->ID, self::META_UNIQUE_CLICKS, true),
            ]);
        }

        fclose($output);
        exit;
    }

    private function log_click(int $link_id, string $landing_url): void
    {
        $settings = Smart_Links_Settings::get_settings();
        if (empty($settings['tracking_enabled'])) {
            return;
        }

        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
        if (!empty($settings['exclude_bots']) && $this->is_bot($user_agent)) {
            return;
        }

        $ip = $this->get_client_ip();
        $ip_hash = hash('sha256', $ip . '|' . wp_salt('kng_link'));
        $ua_hash = hash('sha256', $user_agent . '|' . wp_salt('kng_link'));

        $date = current_time('Y-m-d');
        $unique_hash = hash('sha256', $ip_hash . '|' . $ua_hash . '|' . $date);

        global $wpdb;
        $clicks_table = Smart_Links_DB::get_clicks_table();
        $daily_table = Smart_Links_DB::get_daily_table();

        $is_unique = false;

        if (($settings['unique_click_window'] ?? 'daily') === 'rolling') {
            $window_start = wp_date('Y-m-d H:i:s', current_time('timestamp') - DAY_IN_SECONDS);
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$clicks_table} WHERE link_id = %d AND ip_hash = %s AND user_agent_hash = %s AND created_at >= %s LIMIT 1",
                    $link_id,
                    $ip_hash,
                    $ua_hash,
                    $window_start
                )
            );
            $is_unique = empty($exists);
        } else {
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$clicks_table} WHERE link_id = %d AND unique_hash = %s LIMIT 1",
                    $link_id,
                    $unique_hash
                )
            );
            $is_unique = empty($exists);
        }

        $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
        $referrer_host = $referrer ? (string) wp_parse_url($referrer, PHP_URL_HOST) : '';

        $wpdb->insert(
            $clicks_table,
            [
                'link_id' => $link_id,
                'created_at' => current_time('mysql'),
                'ip_hash' => $ip_hash,
                'user_agent_hash' => $ua_hash,
                'unique_hash' => $unique_hash,
                'referrer' => $referrer_host,
                'landing_url' => esc_url_raw($landing_url),
                'device_type' => $this->detect_device($user_agent),
                'browser' => $this->detect_browser($user_agent),
                'os' => $this->detect_os($user_agent),
                'country' => '',
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        $daily_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT clicks, unique_clicks FROM {$daily_table} WHERE link_id = %d AND date = %s",
                $link_id,
                $date
            ),
            ARRAY_A
        );

        if ($daily_row) {
            $wpdb->update(
                $daily_table,
                [
                    'clicks' => (int) $daily_row['clicks'] + 1,
                    'unique_clicks' => (int) $daily_row['unique_clicks'] + ($is_unique ? 1 : 0),
                ],
                [
                    'link_id' => $link_id,
                    'date' => $date,
                ],
                ['%d', '%d'],
                ['%d', '%s']
            );
        } else {
            $wpdb->insert(
                $daily_table,
                [
                    'link_id' => $link_id,
                    'date' => $date,
                    'clicks' => 1,
                    'unique_clicks' => $is_unique ? 1 : 0,
                ],
                ['%d', '%s', '%d', '%d']
            );
        }

        $total_clicks = (int) get_post_meta($link_id, self::META_CLICKS, true);
        update_post_meta($link_id, self::META_CLICKS, $total_clicks + 1);

        if ($is_unique) {
            $unique_clicks = (int) get_post_meta($link_id, self::META_UNIQUE_CLICKS, true);
            update_post_meta($link_id, self::META_UNIQUE_CLICKS, $unique_clicks + 1);
        }
    }

    private function get_client_ip(): string
    {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = (string) $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($parts[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = (string) $_SERVER['REMOTE_ADDR'];
        }

        return preg_replace('/[^0-9a-fA-F:.,]/', '', (string) $ip);
    }

    private function is_bot(string $user_agent): bool
    {
        if ($user_agent === '') {
            return false;
        }

        return (bool) preg_match('/bot|crawl|spider|slurp|bingpreview|yandex|baiduspider|duckduckbot|facebookexternalhit|facebot|ia_archiver/i', $user_agent);
    }

    private function detect_device(string $user_agent): string
    {
        $ua = strtolower($user_agent);
        if (strpos($ua, 'ipad') !== false || strpos($ua, 'tablet') !== false) {
            return 'tablet';
        }
        if (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false) {
            return 'mobile';
        }
        return 'desktop';
    }

    private function detect_browser(string $user_agent): string
    {
        $ua = strtolower($user_agent);
        if (strpos($ua, 'edge') !== false || strpos($ua, 'edg') !== false) {
            return 'edge';
        }
        if (strpos($ua, 'chrome') !== false && strpos($ua, 'safari') !== false) {
            return 'chrome';
        }
        if (strpos($ua, 'safari') !== false && strpos($ua, 'chrome') === false) {
            return 'safari';
        }
        if (strpos($ua, 'firefox') !== false) {
            return 'firefox';
        }
        if (strpos($ua, 'opera') !== false || strpos($ua, 'opr') !== false) {
            return 'opera';
        }
        return 'other';
    }

    private function detect_os(string $user_agent): string
    {
        $ua = strtolower($user_agent);
        if (strpos($ua, 'windows') !== false) {
            return 'windows';
        }
        if (strpos($ua, 'mac os') !== false || strpos($ua, 'macintosh') !== false) {
            return 'macos';
        }
        if (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false) {
            return 'ios';
        }
        if (strpos($ua, 'android') !== false) {
            return 'android';
        }
        if (strpos($ua, 'linux') !== false) {
            return 'linux';
        }
        return 'other';
    }

    private function render_not_found(): void
    {
        wp_die(esc_html__('Link not found.', 'king-addons'), '', ['response' => 404]);
    }

    private function redirect_with_message(string $view, string $message, int $link_id = 0): void
    {
        $args = [
            'page' => 'king-addons-smart-links',
            'view' => $view,
            'message' => $message,
        ];

        if ($link_id > 0) {
            $args['link_id'] = $link_id;
        }

        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }
}
