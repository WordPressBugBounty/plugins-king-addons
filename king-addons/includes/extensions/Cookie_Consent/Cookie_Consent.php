<?php
/**
 * Cookie / Consent Bar extension.
 *
 * @package King_Addons
 */

namespace King_Addons;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles admin UI, frontend output, consent storage and script blocking.
 */
final class Cookie_Consent
{
    private const OPTION_NAME = 'king_addons_cookie_consent_options';
    private const CONSENT_COOKIE = 'ka_cookie_consent';
    public const LOG_TABLE = 'king_addons_cookie_consent_log';
    private const GEO_TRANSIENT = 'king_addons_cookie_geo';
    private const GEO_TTL = DAY_IN_SECONDS;
    private const FREE_SCRIPT_LIMIT = 3;

    private static ?Cookie_Consent $instance = null;

    /**
     * Cached options array.
     *
     * @var array<string, mixed>
     */
    private array $options = [];

    /**
     * Bootstraps the extension.
     *
     * @return Cookie_Consent
     */
    public static function instance(): Cookie_Consent
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor. Loads options and registers hooks.
     */
    public function __construct()
    {
        $this->options = $this->get_options();

        register_activation_hook(KING_ADDONS_PATH . 'king-addons.php', [$this, 'handle_activation']);

        add_action('admin_post_king_addons_cookie_consent_save', [$this, 'handle_save_settings']);
        add_action('admin_post_king_addons_cookie_consent_export', [$this, 'handle_export_settings']);
        add_action('admin_post_king_addons_cookie_consent_import', [$this, 'handle_import_settings']);
        add_action('admin_post_king_addons_cookie_consent_clear_logs', [$this, 'handle_clear_logs']);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_front_assets']);
        add_action('wp_footer', [$this, 'render_frontend_markup']);
        add_action('wp_body_open', [$this, 'render_portal_container']);
        add_filter('script_loader_tag', [$this, 'filter_script_loader_tag'], 10, 3);

        add_action('wp_ajax_king_addons_cookie_consent_log', [$this, 'ajax_log_consent']);
        add_action('wp_ajax_nopriv_king_addons_cookie_consent_log', [$this, 'ajax_log_consent']);

        add_shortcode('king_addons_cookie_settings', [$this, 'render_manage_button_shortcode']);
    }

    /**
     * Creates defaults and database table on activation.
     *
     * @return void
     */
    public function handle_activation(): void
    {
        if (!get_option(self::OPTION_NAME)) {
            add_option(self::OPTION_NAME, $this->get_default_options());
        }

        if ($this->is_premium()) {
            $this->maybe_create_log_table();
        }
    }

    /**
     * Renders the admin settings page.
     *
     * @return void
     */
    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->options = $this->get_options();
        $is_premium = $this->is_premium();
        $options = $this->options;

        include __DIR__ . '/templates/admin-page.php';
    }

    /**
     * Enqueues admin assets for the settings page.
     *
     * @param string $hook Current admin hook.
     * @return void
     */
    public function enqueue_admin_assets(string $hook): void
    {
        if ($hook !== 'king-addons_page_king-addons-cookie-consent') {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_enqueue_style(
            'king-addons-cookie-consent-admin',
            KING_ADDONS_URL . 'includes/extensions/Cookie_Consent/assets/admin.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-cookie-consent-admin',
            KING_ADDONS_URL . 'includes/extensions/Cookie_Consent/assets/admin.js',
            ['jquery', 'wp-color-picker'],
            KING_ADDONS_VERSION,
            true
        );

        wp_localize_script('king-addons-cookie-consent-admin', 'kingAddonsCookieAdmin', [
            'saveText' => esc_html__('Save Settings', 'king-addons'),
        ]);
    }

    /**
     * Handles settings save.
     *
     * @return void
     */
    public function handle_save_settings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Access denied', 'king-addons'));
        }

        check_admin_referer('king_addons_cookie_consent_save');

        $sanitized = $this->sanitize_options($_POST);

        update_option(self::OPTION_NAME, $sanitized);
        $this->options = $sanitized;

        wp_safe_redirect(
            add_query_arg(
                ['page' => 'king-addons-cookie-consent', 'updated' => 'true'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    /**
     * Exports settings as JSON.
     *
     * @return void
     */
    public function handle_export_settings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Access denied', 'king-addons'));
        }

        if (!$this->is_premium()) {
            wp_die(esc_html__('Upgrade to export settings.', 'king-addons'));
        }

        check_admin_referer('king_addons_cookie_consent_export');

        $options = $this->options;
        $json = wp_json_encode($options, JSON_PRETTY_PRINT);

        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=cookie-consent-settings.json');
        echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Imports settings from JSON upload.
     *
     * @return void
     */
    public function handle_import_settings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Access denied', 'king-addons'));
        }

        if (!$this->is_premium()) {
            wp_die(esc_html__('Upgrade to import settings.', 'king-addons'));
        }

        check_admin_referer('king_addons_cookie_consent_import');

        if (!isset($_FILES['king_addons_cookie_import']) || !is_uploaded_file($_FILES['king_addons_cookie_import']['tmp_name'])) {
            wp_die(esc_html__('No file uploaded.', 'king-addons'));
        }

        $raw = file_get_contents($_FILES['king_addons_cookie_import']['tmp_name']);
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            wp_die(esc_html__('Invalid JSON file.', 'king-addons'));
        }

        $sanitized = $this->sanitize_options($decoded);
        update_option(self::OPTION_NAME, $sanitized);
        $this->options = $sanitized;

        wp_safe_redirect(
            add_query_arg(
                ['page' => 'king-addons-cookie-consent', 'imported' => 'true'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    /**
     * Clears logs older than retention.
     *
     * @return void
     */
    public function handle_clear_logs(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Access denied', 'king-addons'));
        }

        if (!$this->is_premium()) {
            wp_die(esc_html__('Upgrade to manage logs.', 'king-addons'));
        }

        check_admin_referer('king_addons_cookie_consent_clear_logs');

        global $wpdb;
        $table = $wpdb->prefix . self::LOG_TABLE;
        $retention = isset($_POST['retention']) ? sanitize_text_field(wp_unslash($_POST['retention'])) : 'all';

        if ($retention === 'all') {
            $wpdb->query("TRUNCATE TABLE {$table}");
        } else {
            $days = absint($retention);
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$table} WHERE logged_at < (NOW() - INTERVAL %d DAY)",
                    $days
                )
            );
        }

        wp_safe_redirect(
            add_query_arg(
                ['page' => 'king-addons-cookie-consent', 'logs_cleared' => 'true'],
                admin_url('admin.php')
            )
        );
        exit;
    }

    /**
     * Enqueues frontend assets and localizes settings.
     *
     * @return void
     */
    public function enqueue_front_assets(): void
    {
        if (is_admin()) {
            return;
        }

        if (!$this->should_render()) {
            return;
        }

        wp_register_style(
            'king-addons-cookie-consent',
            KING_ADDONS_URL . 'includes/extensions/Cookie_Consent/assets/style.css',
            [],
            KING_ADDONS_VERSION
        );
        wp_register_script(
            'king-addons-cookie-consent',
            KING_ADDONS_URL . 'includes/extensions/Cookie_Consent/assets/script.js',
            [],
            KING_ADDONS_VERSION,
            true
        );

        $payload = $this->get_frontend_payload();
        wp_localize_script('king-addons-cookie-consent', 'kingAddonsCookieConsent', $payload);

        wp_enqueue_style('king-addons-cookie-consent');
        wp_enqueue_script('king-addons-cookie-consent');
    }

    /**
     * Outputs the frontend container when enabled and targeted.
     *
     * @return void
     */
    public function render_frontend_markup(): void
    {
        if (!$this->should_render()) {
            return;
        }

        ?>
        <div class="king-addons-cookie-consent" id="king-addons-cookie-consent" data-cookie-name="<?php echo esc_attr($this->options['advanced']['cookie_name']); ?>">
            <div class="king-addons-cookie-consent__banner" aria-live="polite"></div>
            <div class="king-addons-cookie-consent__modal" role="dialog" aria-modal="true"></div>
        </div>
        <?php
    }

    /**
     * Adds a portal container early in the markup for overlays.
     *
     * @return void
     */
    public function render_portal_container(): void
    {
        if (!$this->should_render()) {
            return;
        }

        echo '<div id="king-addons-cookie-portal" class="king-addons-cookie-consent__portal" aria-hidden="true"></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Filters script tags and blocks them until consent is granted.
     *
     * @param string $tag    Original script tag.
     * @param string $handle Script handle.
     * @param string $src    Script source.
     * @return string
     */
    public function filter_script_loader_tag(string $tag, string $handle, string $src): string
    {
        if (is_admin()) {
            return $tag;
        }

        $rules = $this->get_script_rules();
        if (!isset($rules[$handle])) {
            return $tag;
        }

        $category = $rules[$handle]['category'];
        $mode = isset($rules[$handle]['mode']) ? (string) $rules[$handle]['mode'] : 'block';
        if ($this->has_consent_for_category($category)) {
            return $tag;
        }

        // Preserve original type (e.g. module) so JS can restore it after consent.
        $original_type = '';
        if (preg_match('/\s+type=(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $tag, $m)) {
            $original_type = (string) ($m[1] ?? ($m[2] ?? ($m[3] ?? '')));
        }

        // If the rule is "allow", avoid downloading before consent by stripping src
        // and storing it for later activation.
        $src_attr = '';
        if ($mode === 'allow') {
            $tag = preg_replace('/\s+src=(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $tag);
            $tag = preg_replace('/\s+data-ka-cookie-src=(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $tag);
            if ($src !== '') {
                $src_attr = ' data-ka-cookie-src="' . esc_url($src) . '"';
            }
        }

        // Normalize: remove any existing type and category marker then force text/plain with category marker.
        // This avoids invalid markup like duplicate type attributes (e.g. when original tag is type="module").
        $tag = preg_replace('/\s+type=(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $tag);
        $tag = preg_replace('/\s+data-ka-cookie-category=(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $tag);

        // Avoid duplicates if another filter already added our attribute.
        $tag = preg_replace('/\s+data-ka-cookie-original-type=(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $tag);

        $original_type_attr = '';
        if ($original_type !== '') {
            $original_type_attr = ' data-ka-cookie-original-type="' . esc_attr($original_type) . '"';
        }
        $blocked_tag = preg_replace(
            '/<script\b/i',
            '<script type="text/plain" data-ka-cookie-category="' . esc_attr($category) . '"' . $original_type_attr . $src_attr,
            $tag,
            1
        );

        if (!is_string($blocked_tag) || $blocked_tag === '') {
            return $tag;
        }

        return $blocked_tag;
    }

    /**
     * Logs consent events via AJAX (Pro).
     *
     * @return void
     */
    public function ajax_log_consent(): void
    {
        if (!$this->is_premium()) {
            wp_send_json_success(['message' => 'logging-disabled']);
        }

        check_ajax_referer('king_addons_cookie_log');

        $action = isset($_POST['actionType']) ? sanitize_text_field(wp_unslash($_POST['actionType'])) : '';
        $categories = isset($_POST['categories']) && is_array($_POST['categories']) ? array_map('sanitize_text_field', wp_unslash($_POST['categories'])) : [];
        $region = isset($_POST['region']) ? sanitize_text_field(wp_unslash($_POST['region'])) : 'unknown';
        $device = isset($_POST['device']) ? sanitize_text_field(wp_unslash($_POST['device'])) : 'unknown';

        $this->maybe_create_log_table();
        $this->insert_log_row($action, $categories, $region, $device);

        wp_send_json_success(['message' => 'logged']);
    }

    /**
     * Renders a shortcode manage button.
     *
     * @param array<string, mixed> $atts Shortcode attributes.
     * @return string
     */
    public function render_manage_button_shortcode(array $atts = []): string
    {
        $atts = shortcode_atts(
            [
                'label' => esc_html__('Cookie settings', 'king-addons'),
                'class' => '',
            ],
            $atts
        );

        $class = trim('king-addons-cookie-manage ' . sanitize_html_class($atts['class']));

        return '<button type="button" class="' . esc_attr($class) . '" data-ka-cookie-manage="true">' . esc_html($atts['label']) . '</button>';
    }

    /**
     * Retrieves options with defaults.
     *
     * @return array<string, mixed>
     */
    private function get_options(): array
    {
        $stored = get_option(self::OPTION_NAME, []);
        $defaults = $this->get_default_options();

        if (!is_array($stored)) {
            $stored = [];
        }

        return array_replace_recursive($defaults, $stored);
    }

    /**
     * Returns default option set.
     *
     * @return array<string, mixed>
     */
    private function get_default_options(): array
    {
        return [
            'enabled' => false,
            'mode' => 'gdpr',
            'region_targeting' => 'all',
            'custom_regions' => '',
            'consent_lifetime' => '365',
            'policy_version' => '1',
            'template' => 'gdpr_minimal',
            'content' => [
                'title' => esc_html__('We value your privacy', 'king-addons'),
                'message' => esc_html__('We use cookies to enhance your browsing experience, serve personalized ads or content, and analyze our traffic. By clicking "Accept all", you consent to our use of cookies.', 'king-addons'),
                'privacy_label' => esc_html__('Privacy Policy', 'king-addons'),
                'privacy_url' => '',
                'cookie_label' => esc_html__('Cookie Policy', 'king-addons'),
                'cookie_url' => '',
                'cookie_url_custom' => '',
            ],
            'buttons' => [
                'accept' => esc_html__('Accept all', 'king-addons'),
                'reject' => esc_html__('Reject all', 'king-addons'),
                'settings' => esc_html__('Cookie settings', 'king-addons'),
                'save' => esc_html__('Save preferences', 'king-addons'),
            ],
            'categories' => [
                [
                    'key' => 'necessary',
                    'label' => esc_html__('Strictly necessary', 'king-addons'),
                    'description' => esc_html__('Required for the site to function correctly. These cookies cannot be disabled.', 'king-addons'),
                    'state' => 'required',
                    'display' => true,
                ],
                [
                    'key' => 'analytics',
                    'label' => esc_html__('Analytics', 'king-addons'),
                    'description' => esc_html__('Helps us improve the site by collecting anonymous usage data about how you interact with it.', 'king-addons'),
                    'state' => 'off',
                    'display' => true,
                ],
                [
                    'key' => 'marketing',
                    'label' => esc_html__('Marketing', 'king-addons'),
                    'description' => esc_html__('Used to deliver personalized advertisements and measure their performance.', 'king-addons'),
                    'state' => 'off',
                    'display' => true,
                ],
                [
                    'key' => 'other',
                    'label' => esc_html__('Other', 'king-addons'),
                    'description' => esc_html__('Additional cookies that do not fit into other categories.', 'king-addons'),
                    'state' => 'off',
                    'display' => true,
                ],
            ],
            'design' => [
                'layout' => 'bottom-bar',
                'preset' => 'light',
                'position' => 'center',
                'width' => 'full',
                'animation' => 'fade',
                'shadow' => true,
                'border_radius' => 10,
                'colors' => [
                    'background' => '#111827',
                    'text' => '#f9fafb',
                    'link' => '#93c5fd',
                    'primary_bg' => '#2563eb',
                    'primary_text' => '#ffffff',
                    'secondary_bg' => '#1f2937',
                    'secondary_text' => '#ffffff',
                    'border' => '#1f2937',
                ],
            ],
            'behavior' => [
                'show_on' => 'all',
                'include_pages' => '',
                'exclude_pages' => '',
                'resurface' => 'version',
                'scroll_consent' => false,
                'click_consent' => false,
            ],
            'scripts' => [],
            'manual_blocks' => [],
            'data_attribute_support' => false,
            'advanced' => [
                'cookie_name' => self::CONSENT_COOKIE,
                'cookie_path' => '/',
                'cookie_domain' => '',
                'same_site' => 'Lax',
                'secure' => false,
                'storage' => 'cookie',
            ],
            'logs' => [
                'enabled' => false,
                'retention' => '365',
            ],
        ];
    }

    /**
     * Sanitizes incoming options.
     *
     * @param array<string, mixed> $raw Raw request data.
     * @return array<string, mixed>
     */
    private function sanitize_options(array $raw): array
    {
        $defaults = $this->get_default_options();

        $enabled = isset($raw['enabled']) ? (bool) $raw['enabled'] : false;
        $mode = isset($raw['mode']) ? sanitize_text_field(wp_unslash($raw['mode'])) : $defaults['mode'];
        $region = isset($raw['region_targeting']) ? sanitize_text_field(wp_unslash($raw['region_targeting'])) : $defaults['region_targeting'];
        $custom_regions = isset($raw['custom_regions']) ? sanitize_textarea_field(wp_unslash($raw['custom_regions'])) : '';
        $consent_lifetime = isset($raw['consent_lifetime']) ? sanitize_text_field(wp_unslash($raw['consent_lifetime'])) : $defaults['consent_lifetime'];
        $policy_version = isset($raw['policy_version']) ? sanitize_text_field(wp_unslash($raw['policy_version'])) : $defaults['policy_version'];
        $template = isset($raw['template']) ? sanitize_text_field(wp_unslash($raw['template'])) : $defaults['template'];

        $content = $defaults['content'];
        if (isset($raw['content']) && is_array($raw['content'])) {
            $content = [
                'title' => isset($raw['content']['title']) ? sanitize_text_field(wp_unslash($raw['content']['title'])) : $content['title'],
                'message' => isset($raw['content']['message']) ? sanitize_textarea_field(wp_unslash($raw['content']['message'])) : $content['message'],
                'privacy_label' => isset($raw['content']['privacy_label']) ? sanitize_text_field(wp_unslash($raw['content']['privacy_label'])) : $content['privacy_label'],
                'privacy_url' => isset($raw['content']['privacy_url']) ? esc_url_raw(wp_unslash($raw['content']['privacy_url'])) : '',
                'cookie_label' => isset($raw['content']['cookie_label']) ? sanitize_text_field(wp_unslash($raw['content']['cookie_label'])) : $content['cookie_label'],
                'cookie_url' => isset($raw['content']['cookie_url']) ? esc_url_raw(wp_unslash($raw['content']['cookie_url'])) : '',
                'cookie_url_custom' => isset($raw['content']['cookie_url_custom']) ? esc_url_raw(wp_unslash($raw['content']['cookie_url_custom'])) : '',
            ];
        }

        $buttons = $defaults['buttons'];
        if (isset($raw['buttons']) && is_array($raw['buttons'])) {
            $buttons = [
                'accept' => isset($raw['buttons']['accept']) ? sanitize_text_field(wp_unslash($raw['buttons']['accept'])) : $buttons['accept'],
                'reject' => isset($raw['buttons']['reject']) ? sanitize_text_field(wp_unslash($raw['buttons']['reject'])) : $buttons['reject'],
                'settings' => isset($raw['buttons']['settings']) ? sanitize_text_field(wp_unslash($raw['buttons']['settings'])) : $buttons['settings'],
                'save' => isset($raw['buttons']['save']) ? sanitize_text_field(wp_unslash($raw['buttons']['save'])) : $buttons['save'],
            ];
        }

        $categories = $this->sanitize_categories($raw, $defaults['categories']);

        $design = $defaults['design'];
        if (isset($raw['design']) && is_array($raw['design'])) {
            $design['layout'] = isset($raw['design']['layout']) ? sanitize_text_field(wp_unslash($raw['design']['layout'])) : $design['layout'];
            $design['preset'] = isset($raw['design']['preset']) ? sanitize_text_field(wp_unslash($raw['design']['preset'])) : $design['preset'];
            $design['position'] = isset($raw['design']['position']) ? sanitize_text_field(wp_unslash($raw['design']['position'])) : $design['position'];
            $design['width'] = isset($raw['design']['width']) ? sanitize_text_field(wp_unslash($raw['design']['width'])) : $design['width'];
            $design['animation'] = isset($raw['design']['animation']) ? sanitize_text_field(wp_unslash($raw['design']['animation'])) : $design['animation'];
            $design['shadow'] = isset($raw['design']['shadow']) ? (bool) $raw['design']['shadow'] : false;
            $design['border_radius'] = isset($raw['design']['border_radius']) ? absint($raw['design']['border_radius']) : $design['border_radius'];

            if (isset($raw['design']['colors']) && is_array($raw['design']['colors'])) {
                foreach ($design['colors'] as $key => $color_default) {
                    if (isset($raw['design']['colors'][$key])) {
                        $design['colors'][$key] = sanitize_hex_color(wp_unslash($raw['design']['colors'][$key]));
                    }
                }
            }
        }

        $behavior = $defaults['behavior'];
        if (isset($raw['behavior']) && is_array($raw['behavior'])) {
            $behavior['show_on'] = isset($raw['behavior']['show_on']) ? sanitize_text_field(wp_unslash($raw['behavior']['show_on'])) : $behavior['show_on'];
            $behavior['include_pages'] = isset($raw['behavior']['include_pages']) ? sanitize_textarea_field(wp_unslash($raw['behavior']['include_pages'])) : '';
            $behavior['exclude_pages'] = isset($raw['behavior']['exclude_pages']) ? sanitize_textarea_field(wp_unslash($raw['behavior']['exclude_pages'])) : '';
            $behavior['resurface'] = isset($raw['behavior']['resurface']) ? sanitize_text_field(wp_unslash($raw['behavior']['resurface'])) : $behavior['resurface'];
            $behavior['scroll_consent'] = isset($raw['behavior']['scroll_consent']) ? (bool) $raw['behavior']['scroll_consent'] : false;
            $behavior['click_consent'] = isset($raw['behavior']['click_consent']) ? (bool) $raw['behavior']['click_consent'] : false;
        }

        $scripts = $this->sanitize_script_rules($raw);

        $manual_blocks = [];
        if ($this->is_premium() && isset($raw['manual_blocks']) && is_array($raw['manual_blocks'])) {
            foreach ($raw['manual_blocks'] as $block) {
                if (!isset($block['code']) || trim($block['code']) === '') {
                    continue;
                }
                $manual_blocks[] = [
                    'name' => isset($block['name']) ? sanitize_text_field(wp_unslash($block['name'])) : '',
                    'category' => isset($block['category']) ? sanitize_text_field(wp_unslash($block['category'])) : 'analytics',
                    'type' => isset($block['type']) ? sanitize_text_field(wp_unslash($block['type'])) : 'inline-js',
                    'code' => wp_kses_post(wp_unslash($block['code'])),
                ];
            }
        }

        $advanced = $defaults['advanced'];
        if (isset($raw['advanced']) && is_array($raw['advanced'])) {
            $advanced['cookie_name'] = isset($raw['advanced']['cookie_name']) ? sanitize_key(wp_unslash($raw['advanced']['cookie_name'])) : $advanced['cookie_name'];
            $advanced['cookie_path'] = isset($raw['advanced']['cookie_path']) ? sanitize_text_field(wp_unslash($raw['advanced']['cookie_path'])) : $advanced['cookie_path'];
            $advanced['cookie_domain'] = isset($raw['advanced']['cookie_domain']) ? sanitize_text_field(wp_unslash($raw['advanced']['cookie_domain'])) : '';

            $same_site = isset($raw['advanced']['same_site']) ? sanitize_text_field(wp_unslash($raw['advanced']['same_site'])) : $advanced['same_site'];
            $same_site = ucfirst(strtolower($same_site));
            if (!in_array($same_site, ['Lax', 'Strict', 'None'], true)) {
                $same_site = $advanced['same_site'];
            }
            $advanced['same_site'] = $same_site;

            $secure = isset($raw['advanced']['secure']) ? (bool) $raw['advanced']['secure'] : false;
            if ($advanced['same_site'] === 'None') {
                $secure = true;
            }
            $advanced['secure'] = $secure;

            $storage = isset($raw['advanced']['storage']) ? sanitize_text_field(wp_unslash($raw['advanced']['storage'])) : $advanced['storage'];
            $storage = strtolower($storage);
            if (!in_array($storage, ['cookie', 'local'], true)) {
                $storage = $advanced['storage'];
            }
            $advanced['storage'] = $storage;
        }

        $logs = $defaults['logs'];
        if (isset($raw['logs']) && is_array($raw['logs'])) {
            $logs['enabled'] = isset($raw['logs']['enabled']) ? (bool) $raw['logs']['enabled'] : false;
            $logs['retention'] = isset($raw['logs']['retention']) ? sanitize_text_field(wp_unslash($raw['logs']['retention'])) : $logs['retention'];
        }

        $data_attribute_support = $this->is_premium() && isset($raw['data_attribute_support']) ? (bool) $raw['data_attribute_support'] : false;

        return [
            'enabled' => $enabled,
            'mode' => $mode,
            'region_targeting' => $region,
            'custom_regions' => $custom_regions,
            'consent_lifetime' => $consent_lifetime,
            'policy_version' => $policy_version,
            'template' => $template,
            'content' => $content,
            'buttons' => $buttons,
            'categories' => $categories,
            'design' => $design,
            'behavior' => $behavior,
            'scripts' => $scripts,
            'manual_blocks' => $manual_blocks,
            'data_attribute_support' => $data_attribute_support,
            'advanced' => $advanced,
            'logs' => $logs,
        ];
    }

    /**
     * Sanitizes categories including Pro-only additions.
     *
     * @param array<string, mixed> $raw      Raw input.
     * @param array<int, array<string, mixed>> $defaults Defaults.
     * @return array<int, array<string, mixed>>
     */
    private function sanitize_categories(array $raw, array $defaults): array
    {
        $categories = $defaults;

        if (isset($raw['categories']) && is_array($raw['categories'])) {
            $categories = [];
            foreach ($raw['categories'] as $category) {
                if (!isset($category['key'])) {
                    continue;
                }
                $key = sanitize_key($category['key']);
                $label = isset($category['label']) ? sanitize_text_field(wp_unslash($category['label'])) : '';
                $description = isset($category['description']) ? sanitize_textarea_field(wp_unslash($category['description'])) : '';
                $state = isset($category['state']) ? sanitize_text_field(wp_unslash($category['state'])) : 'off';
                $display = isset($category['display']) ? (bool) $category['display'] : true;

                if ($key === 'necessary') {
                    $state = 'required';
                    $display = true;
                }

                $categories[] = [
                    'key' => $key,
                    'label' => $label ?: ucfirst($key),
                    'description' => $description,
                    'state' => $state,
                    'display' => $display,
                ];
            }
        }

        if (!$this->is_premium()) {
            $categories = array_slice($categories, 0, 4);
        }

        return $categories;
    }

    /**
     * Sanitizes script blocking rules.
     *
     * @param array<string, mixed> $raw Raw input.
     * @return array<string, array<string, string>>
     */
    private function sanitize_script_rules(array $raw): array
    {
        $rules = [];
        if (isset($raw['scripts']) && is_array($raw['scripts'])) {
            foreach ($raw['scripts'] as $rule) {
                if (empty($rule['handle'])) {
                    continue;
                }

                $handle = sanitize_key(wp_unslash($rule['handle']));
                $category = isset($rule['category']) ? sanitize_key(wp_unslash($rule['category'])) : 'analytics';
                $mode = isset($rule['mode']) ? sanitize_text_field(wp_unslash($rule['mode'])) : 'block';

                if (!in_array($mode, ['block', 'allow'], true)) {
                    $mode = 'block';
                }

                $rules[$handle] = [
                    'handle' => $handle,
                    'category' => $category,
                    'mode' => $mode,
                ];
            }
        }

        if (!$this->is_premium() && count($rules) > self::FREE_SCRIPT_LIMIT) {
            $rules = array_slice($rules, 0, self::FREE_SCRIPT_LIMIT, true);
        }

        return $rules;
    }

    /**
     * Determines if frontend should render.
     *
     * @return bool
     */
    private function should_render(): bool
    {
        if (is_admin()) {
            return false;
        }

        if (!$this->options['enabled']) {
            return false;
        }

        if (!$this->passes_page_rules()) {
            return false;
        }

        if (!$this->passes_region_rules()) {
            return false;
        }

        return true;
    }

    /**
     * Checks page targeting rules.
     *
     * @return bool
     */
    private function passes_page_rules(): bool
    {
        $behavior = $this->options['behavior'];

        if (!$this->is_premium()) {
            return true;
        }

        if ($behavior['show_on'] === 'all') {
            return true;
        }

        if ($behavior['show_on'] === 'include') {
            $ids = array_filter(array_map('absint', explode(',', $behavior['include_pages'])));
            return is_page($ids);
        }

        if ($behavior['show_on'] === 'exclude') {
            $ids = array_filter(array_map('absint', explode(',', $behavior['exclude_pages'])));
            return !is_page($ids);
        }

        return true;
    }

    /**
     * Checks region targeting rules.
     *
     * @return bool
     */
    private function passes_region_rules(): bool
    {
        $target = $this->options['region_targeting'];

        if ($target === 'all') {
            return true;
        }

        $region = $this->resolve_region();

        // Fail-open for privacy compliance: if we cannot resolve region reliably, show the banner.
        if ($region === 'unknown') {
            return true;
        }

        if ($target === 'eu') {
            return $region === 'eu';
        }

        if ($target === 'us') {
            return $region === 'us';
        }

        if ($target === 'custom' && $this->is_premium()) {
            $raw = array_filter(array_map('trim', explode(',', (string) $this->options['custom_regions'])));
            $countries = [];
            foreach ($raw as $code) {
                $code = (string) $code;
                $code_lower = strtolower($code);
                if ($code_lower === 'eu' || $code_lower === 'us') {
                    $countries[] = $code_lower;
                    continue;
                }
                $countries[] = strtoupper($code);
            }

            $region_norm = $region;
            if ($region !== 'eu' && $region !== 'us') {
                $region_norm = strtoupper($region);
            }

            return in_array($region_norm, $countries, true);
        }

        return true;
    }

    /**
     * Gets consent data from cookie.
     *
     * @return array<string, mixed>
     */
    private function get_consent_cookie(): array
    {
        $name = $this->options['advanced']['cookie_name'];
        if (!isset($_COOKIE[$name])) {
            return [];
        }

        $decoded = json_decode(stripslashes($_COOKIE[$name]), true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * Determines if a category has consent.
     *
     * @param string $category Category key.
     * @return bool
     */
    private function has_consent_for_category(string $category): bool
    {
        if ($category === 'necessary') {
            return true;
        }

        $consent = $this->get_consent_cookie();
        if (!isset($consent['categories']) || !is_array($consent['categories'])) {
            return false;
        }

        return in_array($category, $consent['categories'], true);
    }

    /**
     * Builds frontend payload for JS.
     *
     * @return array<string, mixed>
     */
    private function get_frontend_payload(): array
    {
        $categories = $this->options['categories'];
        if (!$this->is_premium()) {
            $categories = array_slice($categories, 0, 4);
        }

        $region = 'all';
        if (($this->options['region_targeting'] ?? 'all') !== 'all') {
            $region = $this->resolve_region();
        }

        return [
            'options' => [
                'mode' => $this->options['mode'],
                'template' => $this->options['template'],
                'content' => $this->options['content'],
                'buttons' => $this->options['buttons'],
                'categories' => $categories,
                'design' => $this->options['design'],
                'behavior' => $this->options['behavior'],
                'manualBlocks' => $this->is_premium() ? $this->options['manual_blocks'] : [],
                'scripts' => array_values($this->get_script_rules()),
                'dataAttributes' => $this->options['data_attribute_support'],
                'advanced' => $this->options['advanced'],
                'policyVersion' => $this->options['policy_version'],
                'consentLifetime' => $this->options['consent_lifetime'],
                'isPremium' => $this->is_premium(),
                'logsEnabled' => $this->is_premium() && $this->options['logs']['enabled'],
            ],
            'ajax' => [
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('king_addons_cookie_log'),
            ],
            'region' => $region,
        ];
    }

    /**
     * Returns script blocking rules keyed by handle.
     *
     * @return array<string, array<string, string>>
     */
    private function get_script_rules(): array
    {
        return $this->options['scripts'];
    }

    /**
     * Determines visitor region with caching and filter hook.
     *
     * @return string
     */
    private function resolve_region(): string
    {
        $cache_key = $this->get_geo_cache_key();
        $cached = get_transient($cache_key);
        if ($cached) {
            return (string) $cached;
        }

        $region = 'unknown';
        $response = wp_remote_get(
            'https://ipapi.co/json/',
            [
                'timeout' => 2,
                'redirection' => 2,
                'user-agent' => 'KingAddonsCookieConsent/' . (defined('KING_ADDONS_VERSION') ? KING_ADDONS_VERSION : 'unknown'),
            ]
        );
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($data['country_code'])) {
                $country = strtoupper(sanitize_text_field($data['country_code']));
                $eu_countries = $this->get_eu_countries();
                if (in_array($country, $eu_countries, true)) {
                    $region = 'eu';
                } elseif ($country === 'US') {
                    $region = 'us';
                } else {
                    $region = $country;
                }
            }
        }

        $region = apply_filters('king_addons_cookie_consent_region', $region);
        set_transient($cache_key, $region, self::GEO_TTL);

        return $region;
    }

    /**
     * Inserts a log row.
     *
     * @param string              $action     Action name.
     * @param array<int, string>  $categories Consent categories.
     * @param string              $region     Region.
     * @param string              $device     Device descriptor.
     * @return void
     */
    private function insert_log_row(string $action, array $categories, string $region, string $device): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::LOG_TABLE;

        $wpdb->insert(
            $table,
            [
                'action' => $action,
                'categories' => wp_json_encode($categories),
                'region' => $region,
                'device' => $device,
                'logged_at' => current_time('mysql', true),
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Creates the log table if missing.
     *
     * @return void
     */
    private function maybe_create_log_table(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . self::LOG_TABLE;

        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            action varchar(50) NOT NULL,
            categories text NULL,
            region varchar(20) NULL,
            device varchar(50) NULL,
            logged_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Returns a list of EU country codes.
     *
     * @return array<int, string>
     */
    private function get_eu_countries(): array
    {
        return [
            'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
        ];
    }

    /**
     * Builds a geo cache key scoped per IP.
     *
     * @return string
     */
    private function get_geo_cache_key(): string
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        return self::GEO_TRANSIENT . '_' . md5($ip);
    }

    /**
     * Indicates whether Pro is active.
     *
     * @return bool
     */
    private function is_premium(): bool
    {
        if (!function_exists('king_addons_freemius')) {
            return false;
        }

        $fs = king_addons_freemius();
        if (!is_object($fs) || !method_exists($fs, 'can_use_premium_code__premium_only')) {
            return false;
        }

        return $fs->can_use_premium_code__premium_only();
    }
}



