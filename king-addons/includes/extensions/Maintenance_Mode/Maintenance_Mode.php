<?php
/**
 * Maintenance Mode extension.
 *
 * @package King_Addons
 */

namespace King_Addons\Maintenance_Mode;

if (!defined('ABSPATH')) {
    exit;
}

class Maintenance_Mode
{
    private const OPTION_NAME = 'kng_maintenance_settings';
    private const ANALYTICS_OPTION = 'kng_maintenance_analytics';
    private const ANALYTICS_TRANSIENT_24H = 'kng_maintenance_analytics_24h';

    private const PRIVATE_ACCESS_COOKIE = 'kng_maintenance_private_access';
    private const PRIVATE_ACCESS_QUERY_PARAM = 'kng_maintenance_token';
    private const PRIVATE_ACCESS_POST_FLAG = 'kng_maintenance_private_submit';

    private const DEFAULT_TIMEZONE = 'site';

    private static ?Maintenance_Mode $instance = null;

    /**
     * Cached settings.
     *
     * @var array<string, mixed>
     */
    private array $settings = [];

    private string $private_access_error = '';
    private string $private_access_redirect_to = '';

    public static function instance(): Maintenance_Mode
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
        add_action('template_redirect', [$this, 'maybe_render_maintenance'], 1);

        add_action('admin_post_kng_maintenance_export', [$this, 'handle_export']);
        add_action('admin_post_kng_maintenance_import', [$this, 'handle_import']);
        add_action('admin_post_kng_maintenance_reset_analytics', [$this, 'handle_reset_analytics']);

        add_action('admin_post_kng_maintenance_generate_token', [$this, 'handle_generate_private_token']);
        add_action('admin_post_kng_maintenance_revoke_token', [$this, 'handle_revoke_private_token']);
        add_action('admin_post_kng_maintenance_revoke_password', [$this, 'handle_revoke_private_password']);

        add_shortcode('kng_maintenance_page', [$this, 'render_shortcode']);
    }

    public function register_admin_menu(): void
    {
        add_submenu_page(
            'king-addons',
            __('Maintenance Mode', 'king-addons'),
            __('Maintenance Mode', 'king-addons'),
            'manage_options',
            'king-addons-maintenance-mode',
            [$this, 'render_admin_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'kng_maintenance_settings_group',
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
        if ($hook !== 'king-addons_page_king-addons-maintenance-mode') {
            return;
        }

        $shared_css = KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css';
        $shared_path = KING_ADDONS_PATH . 'includes/admin/layouts/shared/admin-v3-styles.css';
        $shared_version = file_exists($shared_path) ? filemtime($shared_path) : KING_ADDONS_VERSION;
        wp_enqueue_style('king-addons-admin-v3', $shared_css, [], $shared_version);

        $admin_css = KING_ADDONS_URL . 'includes/extensions/Maintenance_Mode/assets/admin.css';
        $admin_path = KING_ADDONS_PATH . 'includes/extensions/Maintenance_Mode/assets/admin.css';
        $admin_version = file_exists($admin_path) ? filemtime($admin_path) : KING_ADDONS_VERSION;
        wp_enqueue_style('king-addons-maintenance-admin', $admin_css, ['king-addons-admin-v3'], $admin_version);

        $admin_js = KING_ADDONS_URL . 'includes/extensions/Maintenance_Mode/assets/admin.js';
        $admin_path_js = KING_ADDONS_PATH . 'includes/extensions/Maintenance_Mode/assets/admin.js';
        $admin_js_version = file_exists($admin_path_js) ? filemtime($admin_path_js) : KING_ADDONS_VERSION;
        wp_enqueue_script('king-addons-maintenance-admin', $admin_js, ['jquery'], $admin_js_version, true);

        wp_localize_script('king-addons-maintenance-admin', 'KNGMaintenance', [
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
        $templates = $this->get_builtin_templates();

        include __DIR__ . '/templates/admin-page.php';
    }

    public function maybe_render_maintenance(): void
    {
        if ($this->is_preview_request()) {
            $this->render_maintenance_response(true);
            exit;
        }

        if (!$this->is_mode_active()) {
            return;
        }

        $bypass_reason = $this->get_bypass_reason();
        if ($bypass_reason !== '') {
            $this->track_bypass($bypass_reason);

            if ($this->private_access_redirect_to !== '') {
                wp_safe_redirect($this->private_access_redirect_to);
                exit;
            }
            return;
        }

        $this->track_blocked_visit();
        $this->render_maintenance_response(false);
        exit;
    }

    public function handle_reset_analytics(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_maintenance_reset_analytics');

        delete_option(self::ANALYTICS_OPTION);
        delete_transient(self::ANALYTICS_TRANSIENT_24H);

        $this->redirect_with_message('analytics', 'analytics_reset');
    }

    private function is_mode_active(): bool
    {
        if (empty($this->settings['enabled'])) {
            return false;
        }

        if (empty($this->settings['schedule_enabled'])) {
            return true;
        }

        return $this->is_any_schedule_window_active() || $this->is_any_recurring_rule_active();
    }

    private function is_any_schedule_window_active(): bool
    {
        $now = current_time('timestamp', true);

        $windows = $this->settings['schedule_windows'] ?? [];
        if (!is_array($windows)) {
            $windows = [];
        }

        foreach ($windows as $window) {
            if (!is_array($window)) {
                continue;
            }

            $start = $this->parse_schedule_time((string) ($window['start'] ?? ''));
            $end = $this->parse_schedule_time((string) ($window['end'] ?? ''));

            if ($this->is_time_range_active($now, $start, $end)) {
                return true;
            }
        }

        $start = $this->parse_schedule_time($this->settings['schedule_start'] ?? '');
        $end = $this->parse_schedule_time($this->settings['schedule_end'] ?? '');

        return $this->is_time_range_active($now, $start, $end);
    }

    private function is_time_range_active(int $now, int $start, int $end): bool
    {
        if ($start && $end) {
            return $now >= $start && $now <= $end;
        }

        if ($start && !$end) {
            return $now >= $start;
        }

        if (!$start && $end) {
            return $now <= $end;
        }

        return false;
    }

    private function is_any_recurring_rule_active(): bool
    {
        if (empty($this->settings['recurring_enabled'])) {
            return false;
        }

        $rules = $this->settings['recurring_rules'] ?? [];
        if (!is_array($rules) || $rules === []) {
            return false;
        }

        $nowTs = current_time('timestamp', true);
        $nowUtc = (new \DateTimeImmutable('@' . $nowTs))->setTimezone(new \DateTimeZone('UTC'));

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            if ($this->is_recurring_rule_active($rule, $nowUtc)) {
                return true;
            }
        }

        return false;
    }

    private function is_recurring_rule_active(array $rule, \DateTimeImmutable $nowUtc): bool
    {
        $freq = isset($rule['frequency']) ? sanitize_key((string) $rule['frequency']) : '';
        if (!in_array($freq, ['daily', 'weekly', 'monthly'], true)) {
            return false;
        }

        $tzString = isset($rule['timezone']) ? sanitize_text_field((string) $rule['timezone']) : self::DEFAULT_TIMEZONE;
        $tz = $this->resolve_timezone($tzString);
        $local = $nowUtc->setTimezone($tz);

        $startMinutes = $this->parse_time_minutes((string) ($rule['start_time'] ?? ''));
        $endMinutes = $this->parse_time_minutes((string) ($rule['end_time'] ?? ''));
        if ($startMinutes < 0 || $endMinutes < 0) {
            return false;
        }

        $nowMinutes = ((int) $local->format('G')) * 60 + (int) $local->format('i');
        $inTime = $this->is_minutes_in_range($nowMinutes, $startMinutes, $endMinutes);
        if (!$inTime) {
            return false;
        }

        if ($freq === 'daily') {
            return true;
        }

        if ($freq === 'weekly') {
            $days = $rule['days_of_week'] ?? [];
            if (!is_array($days) || $days === []) {
                return false;
            }

            $dow = (int) $local->format('N');
            return in_array($dow, array_map('intval', $days), true);
        }

        $days = $rule['days_of_month'] ?? [];
        if (!is_array($days) || $days === []) {
            return false;
        }

        $dom = (int) $local->format('j');
        return in_array($dom, array_map('intval', $days), true);
    }

    private function is_minutes_in_range(int $value, int $start, int $end): bool
    {
        if ($start === $end) {
            return false;
        }

        if ($start < $end) {
            return $value >= $start && $value <= $end;
        }

        return $value >= $start || $value <= $end;
    }

    private function parse_time_minutes(string $value): int
    {
        $value = trim($value);
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
            return -1;
        }

        $h = (int) $m[1];
        $i = (int) $m[2];
        if ($h < 0 || $h > 23 || $i < 0 || $i > 59) {
            return -1;
        }

        return $h * 60 + $i;
    }

    private function resolve_timezone(string $timezone): \DateTimeZone
    {
        $timezone = $timezone !== '' ? $timezone : self::DEFAULT_TIMEZONE;
        if ($timezone === self::DEFAULT_TIMEZONE) {
            return function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone('UTC');
        }

        try {
            return new \DateTimeZone($timezone);
        } catch (\Exception $e) {
            return function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone('UTC');
        }
    }

    private function should_bypass_request(): bool
    {
        return $this->get_bypass_reason() !== '';
    }

    private function get_bypass_reason(): string
    {
        if (defined('WP_CLI') && WP_CLI) {
            return 'wp_cli';
        }

        if (wp_doing_cron()) {
            return 'cron';
        }

        if (is_admin()) {
            return 'wp_admin';
        }

        if (wp_doing_ajax() && !empty($this->settings['allow_admin_ajax'])) {
            return 'admin_ajax_allowed';
        }

        if ($this->is_login_request()) {
            return 'login_page';
        }

        if (!empty($this->settings['disable_elementor_editor']) && $this->is_elementor_editor()) {
            return 'elementor_editor';
        }

        $private_reason = $this->get_private_access_bypass_reason();
        if ($private_reason !== '') {
            return $private_reason;
        }

        if ($this->is_rest_request()) {
            if (is_user_logged_in()) {
                return 'rest_logged_in';
            }
            return !empty($this->settings['allow_rest']) ? 'rest_allowed' : '';
        }

        if ($this->is_user_allowed()) {
            return 'user_allowed';
        }

        if ($this->is_ip_whitelisted()) {
            return 'ip_whitelist';
        }

        if ($this->is_path_whitelisted()) {
            return 'path_whitelist';
        }

        return '';
    }

    private function is_private_access_enabled(): bool
    {
        if (!$this->is_pro()) {
            return false;
        }

        return !empty($this->settings['private_password_hash']) || !empty($this->settings['private_token']);
    }

    private function get_private_access_bypass_reason(): string
    {
        if (!$this->is_private_access_enabled()) {
            return '';
        }

        if ($this->has_private_access_cookie()) {
            return 'private_cookie';
        }

        $token = isset($_GET[self::PRIVATE_ACCESS_QUERY_PARAM])
            ? sanitize_text_field(wp_unslash($_GET[self::PRIVATE_ACCESS_QUERY_PARAM]))
            : '';

        if ($token !== '' && $this->is_private_token_valid($token)) {
            $this->set_private_access_cookie();
            $this->private_access_redirect_to = $this->get_current_url_without_private_params();
            return 'private_token';
        }

        if ($this->maybe_accept_private_password()) {
            $this->private_access_redirect_to = $this->get_current_url_without_private_params();
            return 'private_password';
        }

        return '';
    }

    private function maybe_accept_private_password(): bool
    {
        if (empty($this->settings['private_password_hash'])) {
            return false;
        }

        if (!isset($_POST[self::PRIVATE_ACCESS_POST_FLAG])) {
            return false;
        }

        $nonce = isset($_POST['_kng_private_nonce']) ? sanitize_text_field(wp_unslash($_POST['_kng_private_nonce'])) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'kng_maintenance_private_access')) {
            $this->private_access_error = 'invalid_nonce';
            return false;
        }

        $password = isset($_POST['kng_maintenance_private_password'])
            ? (string) wp_unslash($_POST['kng_maintenance_private_password'])
            : '';

        $password = trim($password);
        if ($password === '') {
            $this->private_access_error = 'invalid_password';
            return false;
        }

        $hash = (string) ($this->settings['private_password_hash'] ?? '');
        if ($hash === '' || !function_exists('wp_check_password')) {
            $this->private_access_error = 'invalid_password';
            return false;
        }

        if (!wp_check_password($password, $hash)) {
            $this->private_access_error = 'invalid_password';
            return false;
        }

        $this->set_private_access_cookie();
        return true;
    }

    private function is_private_token_valid(string $token): bool
    {
        $saved = (string) ($this->settings['private_token'] ?? '');
        if ($saved === '' || $token === '') {
            return false;
        }

        return hash_equals($saved, $token);
    }

    private function get_private_access_cookie_expected(): string
    {
        $key = defined('AUTH_SALT') && AUTH_SALT ? AUTH_SALT : (defined('NONCE_SALT') && NONCE_SALT ? NONCE_SALT : 'kng');
        $hash = (string) ($this->settings['private_password_hash'] ?? '');
        $token = (string) ($this->settings['private_token'] ?? '');
        $material = $hash . '|' . $token;
        return hash_hmac('sha256', 'private_access|' . $material, $key);
    }

    private function has_private_access_cookie(): bool
    {
        $expected = $this->get_private_access_cookie_expected();
        if ($expected === '') {
            return false;
        }

        $cookie = isset($_COOKIE[self::PRIVATE_ACCESS_COOKIE]) ? (string) wp_unslash($_COOKIE[self::PRIVATE_ACCESS_COOKIE]) : '';
        if ($cookie === '') {
            return false;
        }

        return hash_equals($expected, $cookie);
    }

    private function set_private_access_cookie(): void
    {
        $value = $this->get_private_access_cookie_expected();
        if ($value === '') {
            return;
        }

        $expires = time() + (int) apply_filters('kng_maintenance_private_cookie_ttl', 7 * DAY_IN_SECONDS);

        $path = defined('COOKIEPATH') ? (string) COOKIEPATH : '/';
        if ($path === '') {
            $path = '/';
        }

        $args = [
            'expires' => $expires,
            'path' => $path,
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        $domain = defined('COOKIE_DOMAIN') ? (string) COOKIE_DOMAIN : '';
        if ($domain !== '') {
            $args['domain'] = $domain;
        }

        setcookie(self::PRIVATE_ACCESS_COOKIE, $value, $args);
        $_COOKIE[self::PRIVATE_ACCESS_COOKIE] = $value;
    }

    private function get_current_url_without_private_params(): string
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/';
        $url = home_url($request_uri);
        $url = remove_query_arg([self::PRIVATE_ACCESS_QUERY_PARAM], $url);
        return $url;
    }

    private function track_blocked_visit(): void
    {
        if ($this->is_preview_request()) {
            return;
        }

        if (is_admin() || wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) {
            return;
        }

        $analytics = $this->get_analytics();
        $analytics['blocked_total'] = (int) ($analytics['blocked_total'] ?? 0) + 1;
        $analytics['updated_at'] = time();
        update_option(self::ANALYTICS_OPTION, $analytics, false);

        $this->update_24h_analytics('blocked', '', $this->get_masked_request_path());

        $ip = $this->get_client_ip();
        if ($ip !== '') {
            $this->update_24h_unique_ip($ip);
        }
    }

    private function track_bypass(string $reason = ''): void
    {
        if ($this->is_preview_request()) {
            return;
        }

        if (is_admin() || wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) {
            return;
        }

        $analytics = $this->get_analytics();
        $analytics['bypass_total'] = (int) ($analytics['bypass_total'] ?? 0) + 1;

        if ($reason !== '') {
            if (!isset($analytics['bypass_by_reason']) || !is_array($analytics['bypass_by_reason'])) {
                $analytics['bypass_by_reason'] = [];
            }
            $analytics['bypass_by_reason'][$reason] = (int) ($analytics['bypass_by_reason'][$reason] ?? 0) + 1;
        }

        $analytics['updated_at'] = time();
        update_option(self::ANALYTICS_OPTION, $analytics, false);

        $this->update_24h_analytics('bypass', $reason, $this->get_masked_request_path());
    }

    private function get_analytics(): array
    {
        $saved = get_option(self::ANALYTICS_OPTION, []);
        if (!is_array($saved)) {
            $saved = [];
        }

        $defaults = [
            'blocked_total' => 0,
            'bypass_total' => 0,
            'bypass_by_reason' => [],
            'created_at' => time(),
            'updated_at' => time(),
        ];

        $data = wp_parse_args($saved, $defaults);
        if (!is_array($data['bypass_by_reason'])) {
            $data['bypass_by_reason'] = [];
        }

        return $data;
    }

    private function get_analytics_24h(): array
    {
        $saved = get_transient(self::ANALYTICS_TRANSIENT_24H);
        if (!is_array($saved)) {
            $saved = [];
        }

        $defaults = [
            'blocked' => 0,
            'bypass' => 0,
            'bypass_by_reason' => [],
            'unique' => [],
            'paths' => [
                'blocked' => [],
                'bypass' => [],
            ],
        ];

        $data = wp_parse_args($saved, $defaults);
        if (!is_array($data['unique'])) {
            $data['unique'] = [];
        }

        if (!is_array($data['bypass_by_reason'])) {
            $data['bypass_by_reason'] = [];
        }

        if (!isset($data['paths']) || !is_array($data['paths'])) {
            $data['paths'] = ['blocked' => [], 'bypass' => []];
        }
        if (!isset($data['paths']['blocked']) || !is_array($data['paths']['blocked'])) {
            $data['paths']['blocked'] = [];
        }
        if (!isset($data['paths']['bypass']) || !is_array($data['paths']['bypass'])) {
            $data['paths']['bypass'] = [];
        }

        $this->prune_24h_unique($data);
        $this->prune_24h_paths($data);

        return $data;
    }

    private function save_analytics_24h(array $data): void
    {
        $this->prune_24h_unique($data);
        $this->prune_24h_paths($data);
        set_transient(self::ANALYTICS_TRANSIENT_24H, $data, DAY_IN_SECONDS + HOUR_IN_SECONDS);
    }

    private function prune_24h_unique(array &$data): void
    {
        $cutoff = time() - DAY_IN_SECONDS;
        if (!isset($data['unique']) || !is_array($data['unique'])) {
            $data['unique'] = [];
            return;
        }

        foreach ($data['unique'] as $hash => $ts) {
            if ((int) $ts < $cutoff) {
                unset($data['unique'][$hash]);
            }
        }

        $max = 10000;
        if (count($data['unique']) > $max) {
            $data['unique'] = array_slice($data['unique'], -$max, null, true);
        }
    }

    private function prune_24h_paths(array &$data): void
    {
        $cutoff = time() - DAY_IN_SECONDS;
        if (!isset($data['paths']) || !is_array($data['paths'])) {
            $data['paths'] = ['blocked' => [], 'bypass' => []];
            return;
        }

        foreach (['blocked', 'bypass'] as $bucket) {
            if (!isset($data['paths'][$bucket]) || !is_array($data['paths'][$bucket])) {
                $data['paths'][$bucket] = [];
                continue;
            }

            foreach ($data['paths'][$bucket] as $hash => $row) {
                $ts = is_array($row) ? (int) ($row['t'] ?? 0) : 0;
                if ($ts < $cutoff) {
                    unset($data['paths'][$bucket][$hash]);
                }
            }

            $max = 400;
            if (count($data['paths'][$bucket]) > $max) {
                uasort($data['paths'][$bucket], static function ($a, $b) {
                    $ta = is_array($a) ? (int) ($a['t'] ?? 0) : 0;
                    $tb = is_array($b) ? (int) ($b['t'] ?? 0) : 0;
                    return $ta <=> $tb;
                });
                $data['paths'][$bucket] = array_slice($data['paths'][$bucket], -$max, null, true);
            }
        }
    }

    private function update_24h_analytics(string $type, string $reason = '', string $masked_path = ''): void
    {
        $data = $this->get_analytics_24h();
        if ($type === 'blocked') {
            $data['blocked'] = (int) ($data['blocked'] ?? 0) + 1;
        } elseif ($type === 'bypass') {
            $data['bypass'] = (int) ($data['bypass'] ?? 0) + 1;
            if ($reason !== '') {
                if (!isset($data['bypass_by_reason'][$reason])) {
                    $data['bypass_by_reason'][$reason] = 0;
                }
                $data['bypass_by_reason'][$reason] = (int) $data['bypass_by_reason'][$reason] + 1;
            }
        }

        if ($masked_path !== '' && in_array($type, ['blocked', 'bypass'], true)) {
            if (!isset($data['paths']) || !is_array($data['paths'])) {
                $data['paths'] = ['blocked' => [], 'bypass' => []];
            }
            if (!isset($data['paths'][$type]) || !is_array($data['paths'][$type])) {
                $data['paths'][$type] = [];
            }

            $hash = $this->hash_string($masked_path);
            if ($hash !== '') {
                if (!isset($data['paths'][$type][$hash]) || !is_array($data['paths'][$type][$hash])) {
                    $data['paths'][$type][$hash] = ['c' => 0, 'm' => $masked_path, 't' => time()];
                }
                $data['paths'][$type][$hash]['c'] = (int) ($data['paths'][$type][$hash]['c'] ?? 0) + 1;
                $data['paths'][$type][$hash]['m'] = $masked_path;
                $data['paths'][$type][$hash]['t'] = time();
            }
        }

        $this->save_analytics_24h($data);
    }

    private function hash_string(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $key = defined('AUTH_SALT') && AUTH_SALT ? AUTH_SALT : (defined('NONCE_SALT') && NONCE_SALT ? NONCE_SALT : 'kng');
        return hash_hmac('sha256', $value, $key);
    }

    private function get_request_path(): string
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        $path = $path ? '/' . ltrim((string) $path, '/') : '/';
        return $path;
    }

    private function get_masked_request_path(): string
    {
        return $this->mask_path($this->get_request_path());
    }

    private function mask_path(string $path): string
    {
        $path = $path !== '' ? '/' . ltrim($path, '/') : '/';
        $trim = trim($path, '/');
        if ($trim === '') {
            return '/';
        }

        $segments = array_values(array_filter(explode('/', $trim), static fn($s) => $s !== ''));
        $maxSegments = 2;
        $shown = array_slice($segments, 0, $maxSegments);

        $out = [];
        foreach ($shown as $seg) {
            $seg = rawurldecode((string) $seg);
            $seg = preg_replace('/\s+/', '-', $seg);

            if ($seg === null || $seg === '') {
                continue;
            }

            if (preg_match('/^[0-9]+$/', $seg)) {
                $out[] = '{n}';
                continue;
            }

            if (preg_match('/^[a-f0-9]{16,}$/i', $seg)) {
                $out[] = '{hash}';
                continue;
            }

            if (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $seg)) {
                $out[] = '{uuid}';
                continue;
            }

            $seg = preg_replace('/\d+/', '{n}', $seg);
            $seg = preg_replace('/[^a-zA-Z0-9._\-{}]+/', '', (string) $seg);
            $seg = substr((string) $seg, 0, 24);
            if ($seg === '') {
                $seg = '{seg}';
            }
            $out[] = $seg;
        }

        $masked = '/' . implode('/', $out);
        if (count($segments) > $maxSegments) {
            $masked .= '/…';
        }

        return substr($masked, 0, 80);
    }

    private function update_24h_unique_ip(string $ip): void
    {
        $hash = $this->hash_ip($ip);
        if ($hash === '') {
            return;
        }

        $data = $this->get_analytics_24h();
        $data['unique'][$hash] = time();
        $this->save_analytics_24h($data);
    }

    private function hash_ip(string $ip): string
    {
        $ip = trim($ip);
        if ($ip === '') {
            return '';
        }

        $key = defined('AUTH_SALT') && AUTH_SALT ? AUTH_SALT : (defined('NONCE_SALT') && NONCE_SALT ? NONCE_SALT : 'kng');
        return hash_hmac('sha256', $ip, $key);
    }

    private function get_analytics_overview(): array
    {
        $all = $this->get_analytics();
        $h24 = $this->get_analytics_24h();

        $bypassAll = $all['bypass_by_reason'] ?? [];
        if (!is_array($bypassAll)) {
            $bypassAll = [];
        }

        $bypass24 = $h24['bypass_by_reason'] ?? [];
        if (!is_array($bypass24)) {
            $bypass24 = [];
        }

        arsort($bypassAll);
        arsort($bypass24);

        $topBlocked = $this->get_top_paths_24h($h24, 'blocked');
        $topBypass = $this->get_top_paths_24h($h24, 'bypass');

        return [
            'blocked_total' => (int) ($all['blocked_total'] ?? 0),
            'bypass_total' => (int) ($all['bypass_total'] ?? 0),
            'blocked_24h' => (int) ($h24['blocked'] ?? 0),
            'bypass_24h' => (int) ($h24['bypass'] ?? 0),
            'unique_24h' => is_array($h24['unique'] ?? null) ? count($h24['unique']) : 0,
            'bypass_by_reason_total' => $bypassAll,
            'bypass_by_reason_24h' => $bypass24,
            'top_paths_24h_blocked' => $topBlocked,
            'top_paths_24h_bypass' => $topBypass,
        ];
    }

    private function get_top_paths_24h(array $h24, string $bucket, int $limit = 10): array
    {
        if (!isset($h24['paths']) || !is_array($h24['paths'])) {
            return [];
        }
        if (!isset($h24['paths'][$bucket]) || !is_array($h24['paths'][$bucket])) {
            return [];
        }

        $rows = [];
        foreach ($h24['paths'][$bucket] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $count = (int) ($row['c'] ?? 0);
            $mask = isset($row['m']) ? (string) $row['m'] : '';
            if ($count <= 0 || $mask === '') {
                continue;
            }
            $rows[] = ['mask' => $mask, 'count' => $count];
        }

        usort($rows, static function ($a, $b) {
            $ca = (int) ($a['count'] ?? 0);
            $cb = (int) ($b['count'] ?? 0);
            if ($ca === $cb) {
                return strcmp((string) ($a['mask'] ?? ''), (string) ($b['mask'] ?? ''));
            }
            return $cb <=> $ca;
        });

        return array_slice($rows, 0, $limit);
    }

    private function is_user_allowed(): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();
        if (!$user || !$user->ID) {
            return false;
        }

        if (!empty($this->settings['exclude_admin']) && user_can($user, 'manage_options')) {
            return true;
        }

        if ($this->is_pro()) {
            $allowed_roles = $this->settings['allowed_roles'] ?? [];
            if (!empty($allowed_roles) && array_intersect((array) $user->roles, $allowed_roles)) {
                return true;
            }
        }

        return false;
    }

    private function is_ip_whitelisted(): bool
    {
        $whitelist = $this->settings['whitelist_ips'] ?? [];
        if (empty($whitelist)) {
            return false;
        }

        $ip = $this->get_client_ip();
        if ($ip === '') {
            return false;
        }

        return in_array($ip, $whitelist, true);
    }

    private function is_path_whitelisted(): bool
    {
        $paths = $this->settings['whitelist_paths'] ?? [];
        if (empty($paths)) {
            return false;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        $path = $path ? '/' . ltrim($path, '/') : '/';

        foreach ($paths as $allowed) {
            if ($allowed !== '/' && strpos($path, $allowed) === 0) {
                return true;
            }
            if ($allowed === $path) {
                return true;
            }
        }

        return false;
    }

    private function is_login_request(): bool
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        return strpos($request_uri, 'wp-login.php') !== false || strpos($request_uri, 'wp-register.php') !== false;
    }

    private function is_rest_request(): bool
    {
        return defined('REST_REQUEST') && REST_REQUEST;
    }

    private function is_elementor_editor(): bool
    {
        if (isset($_GET['elementor-preview'])) {
            return true;
        }

        if (class_exists('\\Elementor\\Plugin')) {
            $plugin = \Elementor\Plugin::instance();
            if ($plugin->editor && $plugin->editor->is_edit_mode()) {
                return true;
            }
        }

        return false;
    }

    private function render_maintenance_response(bool $is_preview): void
    {
        if (!defined('KING_ADDONS_IS_MAINTENANCE_PAGE')) {
            define('KING_ADDONS_IS_MAINTENANCE_PAGE', true);
        }

        $mode = $this->settings['mode'] ?? 'coming_soon';
        $status = $mode === 'maintenance' ? 503 : 200;

        if ($is_preview) {
            $status = 200;
        }

        status_header($status);
        nocache_headers();
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $retry_after = (int) ($this->settings['retry_after'] ?? 0);
        if ($status === 503 && $retry_after > 0) {
            header('Retry-After: ' . $retry_after);
        }

        if (!empty($this->settings['noindex'])) {
            header('X-Robots-Tag: noindex, nofollow', true);
        }

        $title = $mode === 'maintenance' ? __('Maintenance Mode', 'king-addons') : __('Coming Soon', 'king-addons');
        $content = $this->get_rendered_page_content();

        $this->enqueue_frontend_assets();

        echo '<!doctype html>';
        echo '<html ' . get_language_attributes() . '>';
        echo '<head>';
        echo '<meta charset="' . esc_attr(get_bloginfo('charset')) . '">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . esc_html($title) . '</title>';
        if (!empty($this->settings['noindex'])) {
            echo '<meta name="robots" content="noindex, nofollow">';
        }
        wp_head();
        echo '</head>';
        echo '<body class="kng-maintenance-body">';
        echo $content;
        wp_footer();
        echo '</body></html>';
    }

    private function enqueue_frontend_assets(): void
    {
        wp_enqueue_style(
            'king-addons-maintenance-frontend',
            KING_ADDONS_URL . 'includes/extensions/Maintenance_Mode/assets/frontend.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-maintenance-frontend',
            KING_ADDONS_URL . 'includes/extensions/Maintenance_Mode/assets/frontend.js',
            [],
            KING_ADDONS_VERSION,
            true
        );
    }

    private function get_rendered_page_content(): string
    {
        $theme = $this->settings['theme'] ?? 'dark';
        $mode = $this->settings['mode'] ?? 'coming_soon';
        $template_source = $this->settings['template_source'] ?? 'built_in';

        $wrapper_classes = [
            'kng-maintenance',
            $theme === 'light' ? 'kng-maintenance-theme-light' : 'kng-maintenance-theme-dark',
            $mode === 'maintenance' ? 'kng-maintenance-mode-maintenance' : 'kng-maintenance-mode-coming-soon',
        ];

        $content = '';
        if ($template_source === 'page' && !empty($this->settings['page_id'])) {
            $content = $this->get_page_content((int) $this->settings['page_id']);
        } elseif ($template_source === 'elementor' && !empty($this->settings['elementor_id'])) {
            $content = $this->get_elementor_content((int) $this->settings['elementor_id']);
        } else {
            $content = $this->render_builtin_template((string) $this->settings['template_id']);
        }

        $private_panel = $this->render_private_access_panel();

        return '<main class="' . esc_attr(implode(' ', $wrapper_classes)) . '">' . $content . $private_panel . '</main>';
    }

    private function render_private_access_panel(): string
    {
        if (!$this->is_private_access_enabled()) {
            return '';
        }

        if (empty($this->settings['private_password_hash'])) {
            return '';
        }

        $error = $this->private_access_error;

        ob_start();
        ?>
        <details class="kng-maintenance-private-access" <?php echo $error !== '' ? 'open' : ''; ?>>
            <summary><?php esc_html_e('Private access', 'king-addons'); ?></summary>
            <div class="kng-maintenance-private-access-body">
                <?php if ($error === 'invalid_password') : ?>
                    <div class="kng-maintenance-private-access-error"><?php esc_html_e('Incorrect password. Please try again.', 'king-addons'); ?></div>
                <?php elseif ($error === 'invalid_nonce') : ?>
                    <div class="kng-maintenance-private-access-error"><?php esc_html_e('Session expired. Please try again.', 'king-addons'); ?></div>
                <?php endif; ?>

                <form method="post" class="kng-maintenance-private-access-form">
                    <?php wp_nonce_field('kng_maintenance_private_access', '_kng_private_nonce'); ?>
                    <input type="password" name="kng_maintenance_private_password" placeholder="<?php echo esc_attr__('Password', 'king-addons'); ?>" autocomplete="current-password">
                    <button type="submit" name="<?php echo esc_attr(self::PRIVATE_ACCESS_POST_FLAG); ?>" value="1"><?php esc_html_e('Enter', 'king-addons'); ?></button>
                </form>
            </div>
        </details>
        <?php
        return (string) ob_get_clean();
    }

    public function handle_generate_private_token(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_maintenance_private_token');

        $settings = $this->get_settings();
        try {
            $settings['private_token'] = bin2hex(random_bytes(16));
        } catch (\Exception $e) {
            $settings['private_token'] = wp_generate_password(32, false, false);
        }

        update_option(self::OPTION_NAME, $settings, false);
        $this->redirect_with_message('mode', 'token_generated');
    }

    public function handle_revoke_private_token(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_maintenance_private_token');

        $settings = $this->get_settings();
        $settings['private_token'] = '';
        update_option(self::OPTION_NAME, $settings, false);

        $this->redirect_with_message('mode', 'token_revoked');
    }

    public function handle_revoke_private_password(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_maintenance_private_password');

        $settings = $this->get_settings();

        // update_option() will call the registered sanitize callback.
        // Ensure we provide the same intent flag used by sanitize_settings().
        $settings['private_password_remove'] = 1;
        $settings['private_password'] = '';
        update_option(self::OPTION_NAME, $settings, false);

        $this->redirect_with_message('mode', 'password_revoked');
    }

    public function render_shortcode(array $atts): string
    {
        $atts = shortcode_atts([
            'id' => '',
            'type' => '',
            'theme' => '',
            'full_height' => 'false',
        ], $atts, 'kng_maintenance_page');

        $theme = $atts['theme'] !== '' ? sanitize_key($atts['theme']) : ($this->settings['theme'] ?? 'dark');
        $theme = $theme === 'light' ? 'light' : 'dark';

        $template_source = $this->settings['template_source'] ?? 'built_in';
        $template_id = $this->settings['template_id'] ?? 'minimal';
        $page_id = (int) ($this->settings['page_id'] ?? 0);
        $elementor_id = (int) ($this->settings['elementor_id'] ?? 0);

        if ($atts['id'] !== '') {
            if (is_numeric($atts['id'])) {
                $template_source = 'page';
                $page_id = absint($atts['id']);
            } else {
                $template_source = 'built_in';
                $template_id = sanitize_key($atts['id']);
            }
        }

        if ($atts['type'] !== '') {
            $template_source = sanitize_key($atts['type']);
            if ($template_source === 'elementor' && is_numeric($atts['id'])) {
                $elementor_id = absint($atts['id']);
            }
        }

        $content = '';
        if ($template_source === 'page' && $page_id) {
            $content = $this->get_page_content($page_id);
        } elseif ($template_source === 'elementor' && $elementor_id) {
            $content = $this->get_elementor_content($elementor_id);
        } else {
            $content = $this->render_builtin_template($this->ensure_template_allowed($template_id));
        }

        $this->enqueue_frontend_assets();

        $classes = [
            'kng-maintenance',
            $theme === 'light' ? 'kng-maintenance-theme-light' : 'kng-maintenance-theme-dark',
        ];

        if (filter_var($atts['full_height'], FILTER_VALIDATE_BOOLEAN)) {
            $classes[] = 'kng-maintenance-full';
        }

        return '<div class="' . esc_attr(implode(' ', $classes)) . '">' . $content . '</div>';
    }

    private function get_page_content(int $page_id): string
    {
        $page = get_post($page_id);
        if (!$page) {
            return $this->render_builtin_template('minimal');
        }

        $old_post = $GLOBALS['post'] ?? null;
        $GLOBALS['post'] = $page;
        setup_postdata($page);

        $content = apply_filters('the_content', $page->post_content);

        wp_reset_postdata();
        $GLOBALS['post'] = $old_post;

        return $content;
    }

    private function get_elementor_content(int $template_id): string
    {
        if (!class_exists('\\Elementor\\Plugin')) {
            return $this->render_builtin_template('minimal');
        }

        $plugin = \Elementor\Plugin::instance();
        if (!$plugin->frontend) {
            return $this->render_builtin_template('minimal');
        }

        $plugin->frontend->enqueue_styles();
        $plugin->frontend->enqueue_scripts();

        return $plugin->frontend->get_builder_content_for_display($template_id, true);
    }

    private function render_builtin_template(string $template_id): string
    {
        $templates = $this->get_builtin_templates();
        $template_id = isset($templates[$template_id]) ? $template_id : 'minimal';
        $template_id = $this->ensure_template_allowed($template_id);

        $mode = $this->settings['mode'] ?? 'coming_soon';
        $site_name = get_bloginfo('name');
        $tagline = get_bloginfo('description');
        $schedule_end = $this->settings['schedule_end'] ?? '';
        $content = $this->get_template_content($template_id);

        $headline = $mode === 'maintenance'
            ? __('We are upgrading the experience.', 'king-addons')
            : __('A new experience is on the horizon.', 'king-addons');

        $subhead = $tagline !== '' ? $tagline : __('Our team is preparing something beautiful. Stay tuned.', 'king-addons');

        $mode_label = $mode === 'maintenance' ? __('Maintenance Mode', 'king-addons') : __('Coming Soon', 'king-addons');

        $badge = $content['badge'] !== '' ? $content['badge'] : $mode_label;
        $headline = $content['headline'] !== '' ? $content['headline'] : $headline;
        $subhead = $content['subhead'] !== '' ? $content['subhead'] : $subhead;

        $launch_label = '';
        if ($content['launch_label'] !== '') {
            $launch_label = $content['launch_label'];
        }
        if ($schedule_end !== '') {
            $local_end = get_date_from_gmt($schedule_end, 'Y-m-d H:i');
            if ($launch_label === '') {
                $launch_label = sprintf(__('Estimated return: %s', 'king-addons'), esc_html($local_end));
            }
        }

        $footer_left = $content['footer_left'] !== '' ? $content['footer_left'] : $site_name;
        $footer_right = $content['footer_right'] !== '' ? $content['footer_right'] : __('Powered by King Addons', 'king-addons');

        $countdown_days = str_pad((string) (int) ($content['countdown_days'] ?? 0), 2, '0', STR_PAD_LEFT);
        $countdown_hours = str_pad((string) (int) ($content['countdown_hours'] ?? 0), 2, '0', STR_PAD_LEFT);
        $countdown_minutes = str_pad((string) (int) ($content['countdown_minutes'] ?? 0), 2, '0', STR_PAD_LEFT);
        $progress_percent = isset($content['progress_percent']) ? min(100, max(0, (int) $content['progress_percent'])) : 0;
        $progress_label = $content['progress_label'] ?? '';
        if ($progress_label === '') {
            $progress_label = sprintf(__('%d%% complete', 'king-addons'), $progress_percent);
        }
        $form_placeholder = $content['form_placeholder'] ?? '';
        if ($form_placeholder === '') {
            $form_placeholder = __('Email address', 'king-addons');
        }
        $form_button = $content['form_button'] ?? '';
        if ($form_button === '') {
            $form_button = __('Notify me', 'king-addons');
        }

        ob_start();
        ?>
        <section class="kng-maintenance-shell kng-maintenance-template-<?php echo esc_attr($template_id); ?>">
            <div class="kng-maintenance-card">
                <div class="kng-maintenance-badge"><?php echo esc_html($badge); ?></div>
                <h1><?php echo esc_html($headline); ?></h1>
                <p class="kng-maintenance-subhead"><?php echo esc_html($subhead); ?></p>

                <?php if ($launch_label !== '') : ?>
                    <p class="kng-maintenance-launch"><?php echo esc_html($launch_label); ?></p>
                <?php endif; ?>

                <?php if ($template_id === 'countdown') : ?>
                    <div class="kng-maintenance-countdown">
                        <div><strong><?php echo esc_html($countdown_days); ?></strong><span><?php esc_html_e('Days', 'king-addons'); ?></span></div>
                        <div><strong><?php echo esc_html($countdown_hours); ?></strong><span><?php esc_html_e('Hours', 'king-addons'); ?></span></div>
                        <div><strong><?php echo esc_html($countdown_minutes); ?></strong><span><?php esc_html_e('Minutes', 'king-addons'); ?></span></div>
                    </div>
                <?php endif; ?>

                <?php if ($template_id === 'progress') : ?>
                    <div class="kng-maintenance-progress">
                        <div class="kng-maintenance-progress-bar">
                            <span style="width: <?php echo esc_attr($progress_percent); ?>%;"></span>
                        </div>
                        <div class="kng-maintenance-progress-label"><?php echo esc_html($progress_label); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (in_array($template_id, ['subscribe', 'product-launch'], true)) : ?>
                    <form class="kng-maintenance-form">
                        <input type="email" placeholder="<?php echo esc_attr($form_placeholder); ?>" aria-label="<?php echo esc_attr($form_placeholder); ?>">
                        <button type="button"><?php echo esc_html($form_button); ?></button>
                    </form>
                <?php endif; ?>

                <?php if ($template_id === 'split') : ?>
                    <div class="kng-maintenance-split">
                        <div>
                            <h3><?php echo esc_html($content['split_title_a']); ?></h3>
                            <p><?php echo esc_html($content['split_text_a']); ?></p>
                        </div>
                        <div>
                            <h3><?php echo esc_html($content['split_title_b']); ?></h3>
                            <p><?php echo esc_html($content['split_text_b']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="kng-maintenance-footer">
                    <span><?php echo esc_html($footer_left); ?></span>
                    <span class="kng-maintenance-dot"></span>
                    <span><?php echo esc_html($footer_right); ?></span>
                </div>
            </div>
            <div class="kng-maintenance-orb"></div>
            <div class="kng-maintenance-orb secondary"></div>
        </section>
        <?php
        return ob_get_clean();
    }

    private function get_builtin_templates(): array
    {
        return [
            'minimal' => __('Minimal', 'king-addons'),
            'dark' => __('Dark', 'king-addons'),
            'gradient' => __('Gradient', 'king-addons'),
            'aurora' => __('Aurora Glow', 'king-addons'),
            'neon' => __('Neon Tech', 'king-addons'),
            'paper' => __('Paper Light', 'king-addons'),
            'grid' => __('Tech Grid', 'king-addons'),
            'mono' => __('Mono Minimal', 'king-addons'),
            'spotlight' => __('Spotlight', 'king-addons'),
            'poster' => __('Poster', 'king-addons'),
            'ribbon' => __('Ribbon', 'king-addons'),
            'countdown' => __('Coming Soon Countdown', 'king-addons'),
            'progress' => __('Maintenance Progress', 'king-addons'),
            'subscribe' => __('Simple Subscribe', 'king-addons'),
            'product-launch' => __('Product Launch', 'king-addons'),
            'construction' => __('Under Construction', 'king-addons'),
            'split' => __('Split Layout', 'king-addons'),
            'logo' => __('Centered Logo', 'king-addons'),
        ];
    }

    public function get_pro_templates(): array
    {
        return [
            'countdown',
            'progress',
            'product-launch',
            'split',
        ];
    }

    public function handle_export(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_maintenance_export');

        $settings = $this->get_settings();

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=maintenance-mode-settings.json');
        echo wp_json_encode($settings);
        exit;
    }

    public function handle_import(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'king-addons'));
        }

        check_admin_referer('kng_maintenance_import');

        if (empty($_FILES['import_file']) || !isset($_FILES['import_file']['tmp_name'])) {
            $this->redirect_with_message('import-export', 'error_import');
        }

        $file = $_FILES['import_file'];
        if (!empty($file['error'])) {
            $this->redirect_with_message('import-export', 'error_import');
        }

        $contents = file_get_contents($file['tmp_name']);
        if ($contents === false) {
            $this->redirect_with_message('import-export', 'error_import');
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            $this->redirect_with_message('import-export', 'error_import');
        }

        $sanitized = $this->sanitize_settings($decoded);
        update_option(self::OPTION_NAME, $sanitized);

        $this->redirect_with_message('import-export', 'imported');
    }

    private function get_default_settings(): array
    {
        return [
            'enabled' => false,
            'mode' => 'coming_soon',
            'template_source' => 'built_in',
            'template_id' => 'minimal',
            'template_content' => [],
            'page_id' => 0,
            'elementor_id' => 0,
            'theme' => 'dark',
            'noindex' => true,
            'retry_after' => 3600,
            'whitelist_ips' => [],
            'whitelist_paths' => [],
            'exclude_admin' => true,
            'allowed_roles' => [],
            'schedule_enabled' => false,
            'schedule_start' => '',
            'schedule_end' => '',
            'schedule_windows' => [],
            'recurring_enabled' => false,
            'recurring_rules' => [],
            'allow_rest' => true,
            'allow_admin_ajax' => true,
            'disable_elementor_editor' => true,
            'private_password_hash' => '',
            'private_token' => '',
            'custom_css' => '',
            'custom_js' => '',
        ];
    }

    public function get_settings(): array
    {
        $defaults = $this->get_default_settings();
        $saved = get_option(self::OPTION_NAME, []);

        $settings = wp_parse_args($saved, $defaults);
        if (!is_array($settings['template_content'])) {
            $settings['template_content'] = $defaults['template_content'];
        }
        if (!is_array($settings['whitelist_ips'])) {
            $settings['whitelist_ips'] = $defaults['whitelist_ips'];
        }
        if (!is_array($settings['whitelist_paths'])) {
            $settings['whitelist_paths'] = $defaults['whitelist_paths'];
        }
        if (!is_array($settings['allowed_roles'])) {
            $settings['allowed_roles'] = $defaults['allowed_roles'];
        }

        if (!is_array($settings['schedule_windows'])) {
            $settings['schedule_windows'] = $defaults['schedule_windows'];
        }

        if (!is_array($settings['recurring_rules'])) {
            $settings['recurring_rules'] = $defaults['recurring_rules'];
        }

        return $settings;
    }

    public function sanitize_settings(array $settings): array
    {
        $defaults = $this->get_default_settings();
        $existing = $this->get_settings();
        $settings = wp_parse_args($settings, $existing);
        $templates = array_keys($this->get_builtin_templates());
        $pro_templates = $this->get_pro_templates();

        $source = isset($settings['template_source']) ? sanitize_key($settings['template_source']) : $defaults['template_source'];
        if (!in_array($source, ['built_in', 'page', 'elementor'], true)) {
            $source = 'built_in';
        }

        $template_id = isset($settings['template_id']) ? sanitize_key($settings['template_id']) : $defaults['template_id'];
        if (!in_array($template_id, $templates, true)) {
            $template_id = $defaults['template_id'];
        }
        if (!$this->is_pro() && in_array($template_id, $pro_templates, true)) {
            $template_id = 'minimal';
        }

        $mode = isset($settings['mode']) ? sanitize_key($settings['mode']) : $defaults['mode'];
        if (!in_array($mode, ['coming_soon', 'maintenance'], true)) {
            $mode = $defaults['mode'];
        }

        $theme = isset($settings['theme']) ? sanitize_key($settings['theme']) : $defaults['theme'];
        $theme = $theme === 'light' ? 'light' : 'dark';

        $whitelist_ips = $this->sanitize_list($settings['whitelist_ips'] ?? []);
        $whitelist_paths = $this->sanitize_list($settings['whitelist_paths'] ?? []);

        if (!$this->is_pro()) {
            $whitelist_ips = array_slice($whitelist_ips, 0, 10);
            $whitelist_paths = array_slice($whitelist_paths, 0, 10);
        }

        $legacyScheduleStart = $this->sanitize_datetime($settings['schedule_start'] ?? '');
        $legacyScheduleEnd = $this->sanitize_datetime($settings['schedule_end'] ?? '');

        $scheduleWindows = $this->sanitize_schedule_windows($settings['schedule_windows'] ?? []);
        if ($scheduleWindows === [] && ($legacyScheduleStart !== '' || $legacyScheduleEnd !== '')) {
            $scheduleWindows[] = [
                'start' => $legacyScheduleStart,
                'end' => $legacyScheduleEnd,
                'timezone' => self::DEFAULT_TIMEZONE,
            ];
        }

        if ($scheduleWindows !== []) {
            $legacyScheduleStart = (string) ($scheduleWindows[0]['start'] ?? $legacyScheduleStart);
            $legacyScheduleEnd = (string) ($scheduleWindows[0]['end'] ?? $legacyScheduleEnd);
        }

        $recurringRules = $this->sanitize_recurring_rules($settings['recurring_rules'] ?? []);

        $clean = [
            'enabled' => !empty($settings['enabled']),
            'mode' => $mode,
            'template_source' => $source,
            'template_id' => $template_id,
            'template_content' => $this->sanitize_template_content($settings['template_content'] ?? []),
            'page_id' => absint($settings['page_id'] ?? 0),
            'elementor_id' => absint($settings['elementor_id'] ?? 0),
            'theme' => $theme,
            'noindex' => !empty($settings['noindex']),
            'retry_after' => max(0, absint($settings['retry_after'] ?? $defaults['retry_after'])),
            'whitelist_ips' => $whitelist_ips,
            'whitelist_paths' => $this->normalize_paths($whitelist_paths),
            'exclude_admin' => !empty($settings['exclude_admin']),
            'allowed_roles' => $this->sanitize_list($settings['allowed_roles'] ?? []),
            'schedule_enabled' => !empty($settings['schedule_enabled']),
            'schedule_start' => $legacyScheduleStart,
            'schedule_end' => $legacyScheduleEnd,
            'schedule_windows' => $scheduleWindows,
            'recurring_enabled' => !empty($settings['recurring_enabled']),
            'recurring_rules' => $recurringRules,
            'allow_rest' => !empty($settings['allow_rest']),
            'allow_admin_ajax' => !empty($settings['allow_admin_ajax']),
            'disable_elementor_editor' => !empty($settings['disable_elementor_editor']),
            'private_password_hash' => (string) ($existing['private_password_hash'] ?? ''),
            'private_token' => (string) ($existing['private_token'] ?? ''),
            'custom_css' => '',
            'custom_js' => '',
        ];

        if ($this->is_pro()) {
            $remove_password = !empty($settings['private_password_remove']);
            $plain = isset($settings['private_password']) ? (string) $settings['private_password'] : '';
            $plain = trim($plain);

            if ($remove_password) {
                $clean['private_password_hash'] = '';
            } elseif ($plain !== '') {
                $clean['private_password_hash'] = function_exists('wp_hash_password') ? wp_hash_password($plain) : $clean['private_password_hash'];
            }

            $token = isset($settings['private_token']) ? sanitize_text_field((string) $settings['private_token']) : (string) ($existing['private_token'] ?? '');
            $clean['private_token'] = $token;
        }

        if (!$this->is_pro()) {
            $clean['allowed_roles'] = [];
        }

        return $clean;
    }

    private function sanitize_schedule_windows($windows): array
    {
        if (!is_array($windows)) {
            return [];
        }

        $clean = [];
        foreach ($windows as $window) {
            if (!is_array($window)) {
                continue;
            }

            $tz = isset($window['timezone']) ? sanitize_text_field((string) $window['timezone']) : self::DEFAULT_TIMEZONE;
            if ($tz === '') {
                $tz = self::DEFAULT_TIMEZONE;
            }

            $start = $this->sanitize_datetime_with_timezone((string) ($window['start'] ?? ''), $tz);
            $end = $this->sanitize_datetime_with_timezone((string) ($window['end'] ?? ''), $tz);

            if ($start === '' && $end === '') {
                continue;
            }

            $clean[] = [
                'start' => $start,
                'end' => $end,
                'timezone' => $tz,
            ];
        }

        return $clean;
    }

    private function sanitize_recurring_rules($rules): array
    {
        if (!is_array($rules)) {
            return [];
        }

        $clean = [];
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $freq = isset($rule['frequency']) ? sanitize_key((string) $rule['frequency']) : '';
            if (!in_array($freq, ['daily', 'weekly', 'monthly'], true)) {
                continue;
            }

            $tz = isset($rule['timezone']) ? sanitize_text_field((string) $rule['timezone']) : self::DEFAULT_TIMEZONE;
            if ($tz === '') {
                $tz = self::DEFAULT_TIMEZONE;
            }

            $startTime = sanitize_text_field((string) ($rule['start_time'] ?? ''));
            $endTime = sanitize_text_field((string) ($rule['end_time'] ?? ''));
            if ($this->parse_time_minutes($startTime) < 0 || $this->parse_time_minutes($endTime) < 0) {
                continue;
            }

            $daysOfWeek = [];
            if ($freq === 'weekly') {
                $raw = $rule['days_of_week'] ?? [];
                if (!is_array($raw)) {
                    $raw = preg_split('/\s*,\s*/', (string) $raw);
                }

                $daysOfWeek = array_values(array_unique(array_filter(array_map(static function ($v) {
                    $n = absint($v);
                    return ($n >= 1 && $n <= 7) ? $n : 0;
                }, (array) $raw))));
            }

            $daysOfMonth = [];
            if ($freq === 'monthly') {
                $raw = $rule['days_of_month'] ?? [];
                if (!is_array($raw)) {
                    $raw = preg_split('/\s*,\s*/', (string) $raw);
                }

                $daysOfMonth = array_values(array_unique(array_filter(array_map(static function ($v) {
                    $n = absint($v);
                    return ($n >= 1 && $n <= 31) ? $n : 0;
                }, (array) $raw))));
            }

            $item = [
                'frequency' => $freq,
                'timezone' => $tz,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'days_of_week' => $daysOfWeek,
                'days_of_month' => $daysOfMonth,
            ];

            $clean[] = $item;
        }

        return $clean;
    }

    public function get_template_content(string $template_id): array
    {
        $templates = $this->get_builtin_templates();
        $template_id = isset($templates[$template_id]) ? $template_id : 'minimal';
        $template_id = $this->ensure_template_allowed($template_id);

        $defaults = $this->get_template_content_defaults($template_id);
        $saved = $this->settings['template_content'][$template_id] ?? [];
        if (!is_array($saved)) {
            $saved = [];
        }

        return wp_parse_args($saved, $defaults);
    }

    private function get_template_content_defaults(string $template_id): array
    {
        $mode = $this->settings['mode'] ?? 'coming_soon';
        $site_name = get_bloginfo('name');
        $tagline = get_bloginfo('description');

        $headline = $mode === 'maintenance'
            ? __('We are upgrading the experience.', 'king-addons')
            : __('A new experience is on the horizon.', 'king-addons');

        $subhead = $tagline !== '' ? $tagline : __('Our team is preparing something beautiful. Stay tuned.', 'king-addons');

        $base = [
            'badge' => '',
            'headline' => $headline,
            'subhead' => $subhead,
            'launch_label' => '',
            'footer_left' => $site_name,
            'footer_right' => __('Powered by King Addons', 'king-addons'),
        ];

        if ($template_id === 'countdown') {
            $base['countdown_days'] = '14';
            $base['countdown_hours'] = '06';
            $base['countdown_minutes'] = '42';
        }

        if ($template_id === 'progress') {
            $base['progress_percent'] = 68;
            $base['progress_label'] = __('68% complete', 'king-addons');
        }

        if (in_array($template_id, ['subscribe', 'product-launch'], true)) {
            $base['form_placeholder'] = __('Email address', 'king-addons');
            $base['form_button'] = __('Notify me', 'king-addons');
        }

        if ($template_id === 'split') {
            $base['split_title_a'] = __('What is happening', 'king-addons');
            $base['split_text_a'] = __('We are refining the experience with faster performance and new visuals.', 'king-addons');
            $base['split_title_b'] = __('Stay connected', 'king-addons');
            $base['split_text_b'] = __('Follow our updates while we prepare the launch.', 'king-addons');
        }

        return $base;
    }

    private function sanitize_template_content(array $content): array
    {
        $fields = $this->get_template_content_fields();
        $clean = [];

        foreach ($fields as $template_id => $template_fields) {
            $values = isset($content[$template_id]) && is_array($content[$template_id]) ? $content[$template_id] : [];
            $template_clean = [];

            foreach ($template_fields as $field => $type) {
                $value = $values[$field] ?? '';
                if ($type === 'int') {
                    $int = absint($value);
                    if ($field === 'progress_percent') {
                        $int = min(100, max(0, $int));
                    }
                    if ($field === 'countdown_hours') {
                        $int = min(23, max(0, $int));
                    }
                    if ($field === 'countdown_minutes') {
                        $int = min(59, max(0, $int));
                    }
                    $template_clean[$field] = $int;
                } else {
                    $template_clean[$field] = sanitize_text_field((string) $value);
                }
            }

            $clean[$template_id] = $template_clean;
        }

        return $clean;
    }

    private function get_template_content_fields(): array
    {
        $base = [
            'badge' => 'text',
            'headline' => 'text',
            'subhead' => 'text',
            'launch_label' => 'text',
            'footer_left' => 'text',
            'footer_right' => 'text',
        ];

        return [
            'minimal' => $base,
            'dark' => $base,
            'gradient' => $base,
            'aurora' => $base,
            'neon' => $base,
            'paper' => $base,
            'grid' => $base,
            'mono' => $base,
            'spotlight' => $base,
            'poster' => $base,
            'ribbon' => $base,
            'construction' => $base,
            'logo' => $base,
            'countdown' => $base + [
                'countdown_days' => 'int',
                'countdown_hours' => 'int',
                'countdown_minutes' => 'int',
            ],
            'progress' => $base + [
                'progress_percent' => 'int',
                'progress_label' => 'text',
            ],
            'subscribe' => $base + [
                'form_placeholder' => 'text',
                'form_button' => 'text',
            ],
            'product-launch' => $base + [
                'form_placeholder' => 'text',
                'form_button' => 'text',
            ],
            'split' => $base + [
                'split_title_a' => 'text',
                'split_text_a' => 'text',
                'split_title_b' => 'text',
                'split_text_b' => 'text',
            ],
        ];
    }

    private function ensure_template_allowed(string $template_id): string
    {
        if (!$this->is_pro() && in_array($template_id, $this->get_pro_templates(), true)) {
            return 'minimal';
        }

        return $template_id;
    }

    private function sanitize_list($value): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = preg_split('/\\r\\n|\\r|\\n|,/', (string) $value);
        }

        $items = array_map('trim', $items);
        $items = array_filter($items, static function ($item) {
            return $item !== '';
        });
        $items = array_map('sanitize_text_field', $items);

        return array_values(array_unique($items));
    }

    private function normalize_paths(array $paths): array
    {
        $normalized = [];
        foreach ($paths as $path) {
            $path = wp_parse_url($path, PHP_URL_PATH) ?: $path;
            $path = '/' . ltrim((string) $path, '/');
            $normalized[] = $path === '' ? '/' : $path;
        }

        return array_values(array_unique($normalized));
    }

    private function sanitize_datetime(string $value): string
    {
        $value = sanitize_text_field($value);
        if ($value === '') {
            return '';
        }

        $value = str_replace('T', ' ', $value);
        if (strlen($value) === 16) {
            $value .= ':00';
        }

        $timestamp = strtotime($value);
        if (!$timestamp) {
            return '';
        }

        return get_gmt_from_date($value, 'Y-m-d H:i:s');
    }

    private function sanitize_datetime_with_timezone(string $value, string $timezone): string
    {
        $value = sanitize_text_field($value);
        if ($value === '') {
            return '';
        }

        $value = str_replace('T', ' ', $value);
        if (strlen($value) === 16) {
            $value .= ':00';
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return '';
        }

        $tz = $this->resolve_timezone($timezone);
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, $tz);
        if (!$dt) {
            return '';
        }

        return $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    private function parse_schedule_time(string $value): int
    {
        if ($value === '') {
            return 0;
        }

        return (int) strtotime($value . ' UTC');
    }

    private function get_client_ip(): string
    {
        if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            return (string) $_SERVER['REMOTE_ADDR'];
        }

        return '';
    }

    private function is_preview_request(): bool
    {
        if (empty($_GET['kng_maintenance_preview'])) {
            return false;
        }

        if (!current_user_can('manage_options')) {
            return false;
        }

        $nonce = isset($_GET['_kng_preview_nonce']) ? sanitize_text_field(wp_unslash($_GET['_kng_preview_nonce'])) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'kng_maintenance_preview')) {
            return false;
        }

        return true;
    }

    private function is_pro(): bool
    {
        return function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();
    }

    private function redirect_with_message(string $view, string $message): void
    {
        $args = [
            'page' => 'king-addons-maintenance-mode',
            'view' => $view,
            'message' => $message,
        ];

        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }
}
