<?php
/**
 * Age Gate extension - Free base class.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provides admin settings, frontend payload and cookie handling for Age Gate.
 */
class Age_Gate
{
    protected const OPTION_NAME = 'king_addons_age_gate_options';
    protected const COOKIE_NAME = 'ka_age_gate_status';
    protected const NONCE_ACTION = 'king_addons_age_gate_nonce';
    protected const AJAX_ACTION_DOB = 'ka_age_gate_validate_dob';

    protected static ?Age_Gate $instance = null;

    /**
     * Cached options.
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * Singleton accessor that swaps in the Pro implementation when available.
     *
     * @return Age_Gate
     */
    public static function instance(): Age_Gate
    {
        if (is_null(self::$instance)) {
            if (class_exists('\King_Addons\Age_Gate_Pro') && king_addons_freemius()->can_use_premium_code()) {
                self::$instance = new Age_Gate_Pro();
            } else {
                self::$instance = new self();
            }
        }

        return self::$instance;
    }

    /**
     * Bootstraps the extension.
     */
    public function __construct()
    {
        $this->options = $this->get_options();

        register_activation_hook(KING_ADDONS_PATH . 'king-addons.php', [$this, 'handle_activation']);

        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_front_assets']);
        add_action('wp_footer', [$this, 'render_frontend_markup']);
        add_action('wp_body_open', [$this, 'render_portal_container']);
        add_action('template_redirect', [$this, 'maybe_handle_denied_redirect']);

        // AJAX handler for DOB validation (Pro)
        add_action('wp_ajax_' . self::AJAX_ACTION_DOB, [$this, 'ajax_validate_dob']);
        add_action('wp_ajax_nopriv_' . self::AJAX_ACTION_DOB, [$this, 'ajax_validate_dob']);
    }

    /**
     * AJAX handler for date of birth validation.
     *
     * @return void
     */
    public function ajax_validate_dob(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        if (!$this->is_premium()) {
            wp_send_json_error(['message' => esc_html__('Pro feature required.', 'king-addons')]);
            return;
        }

        $day = isset($_POST['day']) ? absint($_POST['day']) : 0;
        $month = isset($_POST['month']) ? absint($_POST['month']) : 0;
        $year = isset($_POST['year']) ? absint($_POST['year']) : 0;

        $dob_options = $this->options['dob'];
        $min_age = (int) $this->options['general']['min_age'];
        $max_age = (int) $dob_options['max_age'];

        // Validate date components
        if ($day < 1 || $day > 31 || $month < 1 || $month > 12 || $year < 1900) {
            wp_send_json_error(['message' => $dob_options['error_invalid']]);
            return;
        }

        // Check if date is valid
        if (!checkdate($month, $day, $year)) {
            wp_send_json_error(['message' => $dob_options['error_invalid']]);
            return;
        }

        // Calculate age
        $dob = new \DateTime(sprintf('%04d-%02d-%02d', $year, $month, $day));
        $now = new \DateTime();
        $age = $now->diff($dob)->y;

        // Validate age range
        if ($age < 0 || $age > $max_age) {
            wp_send_json_error(['message' => $dob_options['error_invalid']]);
            return;
        }

        // Check minimum age requirement
        $required_age = $this->resolve_minimum_age();
        if ($age < $required_age) {
            wp_send_json_error(['message' => $dob_options['error_denied']]);
            return;
        }

        // Set cookie server-side for extra security
        $cookie_days = (int) $this->options['general']['cookie_days'];
        $this->set_status_cookie('allowed', $cookie_days);

        wp_send_json_success(['message' => esc_html__('Age verified.', 'king-addons')]);
    }

    /**
     * Resolves minimum age considering geo rules.
     *
     * @return int
     */
    protected function resolve_minimum_age(): int
    {
        $base_age = (int) $this->options['general']['min_age'];

        if (!$this->is_premium() || empty($this->options['geo']['enabled'])) {
            return $base_age;
        }

        $geo_map = $this->options['geo']['map'];
        $default_age = (int) $this->options['geo']['default_age'];
        $country = $this->detect_country();

        if ($country && isset($geo_map[$country])) {
            return (int) $geo_map[$country];
        }

        return $default_age ?: $base_age;
    }

    /**
     * Detects visitor country using WooCommerce geolocation if available.
     *
     * @return string Country code or empty string.
     */
    protected function detect_country(): string
    {
        // Try WooCommerce geolocation first
        if (class_exists('WC_Geolocation')) {
            $geo = \WC_Geolocation::geolocate_ip();
            if (!empty($geo['country'])) {
                return strtoupper($geo['country']);
            }
        }

        return '';
    }

    /**
     * Creates default options on activation.
     *
     * @return void
     */
    public function handle_activation(): void
    {
        if (!get_option(self::OPTION_NAME)) {
            add_option(self::OPTION_NAME, $this->get_default_options());
        }
    }

    /**
     * Registers the settings entry and sanitize callback.
     *
     * @return void
     */
    public function register_settings(): void
    {
        register_setting('king_addons_age_gate', self::OPTION_NAME, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_options'],
        ]);
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
        $options = $this->options;
        $is_premium = $this->is_premium();

        settings_errors('king_addons_age_gate');
        include __DIR__ . '/templates/admin-page.php';
    }

    /**
     * Enqueues admin assets for the Age Gate page.
     *
     * @param string $hook Current admin hook.
     * @return void
     */
    public function enqueue_admin_assets(string $hook): void
    {
        if ($hook !== 'king-addons_page_king-addons-age-gate') {
            return;
        }

        wp_enqueue_style(
            'king-addons-age-gate-admin',
            KING_ADDONS_URL . 'includes/extensions/Age_Gate/assets/admin.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-age-gate-admin',
            KING_ADDONS_URL . 'includes/extensions/Age_Gate/assets/admin.js',
            ['jquery'],
            KING_ADDONS_VERSION,
            true
        );
    }

    /**
     * Registers and enqueues frontend assets when needed.
     *
     * @return void
     */
    public function enqueue_front_assets(): void
    {
        if (defined('KING_ADDONS_IS_MAINTENANCE_PAGE') && KING_ADDONS_IS_MAINTENANCE_PAGE) {
            return;
        }

        if (is_admin()) {
            return;
        }

        $should_render = $this->should_render() || $this->should_render_block_state() || $this->is_preview_mode();

        if (!$should_render) {
            return;
        }

        wp_register_style(
            'king-addons-age-gate',
            KING_ADDONS_URL . 'includes/widgets/Age_Gate/style.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_register_script(
            'king-addons-age-gate',
            KING_ADDONS_URL . 'includes/widgets/Age_Gate/script.js',
            [],
            KING_ADDONS_VERSION,
            true
        );

        wp_localize_script('king-addons-age-gate', 'kingAddonsAgeGate', $this->get_frontend_payload());

        wp_enqueue_style('king-addons-age-gate');
        wp_enqueue_script('king-addons-age-gate');
    }

    /**
     * Outputs the overlay container markup.
     *
     * @return void
     */
    public function render_frontend_markup(): void
    {
        if (defined('KING_ADDONS_IS_MAINTENANCE_PAGE') && KING_ADDONS_IS_MAINTENANCE_PAGE) {
            return;
        }

        if (!$this->should_render() && !$this->should_render_block_state() && !$this->is_preview_mode()) {
            return;
        }
        ?>
        <div id="king-addons-age-gate" class="king-addons-age-gate" aria-hidden="true">
            <div class="king-addons-age-gate__overlay"></div>
            <div class="king-addons-age-gate__card" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr($this->options['design']['title']); ?>">
                <div class="king-addons-age-gate__content"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Adds an early portal container to the body for fixed overlays.
     *
     * @return void
     */
    public function render_portal_container(): void
    {
        if (defined('KING_ADDONS_IS_MAINTENANCE_PAGE') && KING_ADDONS_IS_MAINTENANCE_PAGE) {
            return;
        }

        if (!$this->should_render() && !$this->should_render_block_state() && !$this->is_preview_mode()) {
            return;
        }

        echo '<div id="king-addons-age-gate-portal" class="king-addons-age-gate__portal" aria-hidden="true"></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Performs redirect on denied state when configured.
     *
     * @return void
     */
    public function maybe_handle_denied_redirect(): void
    {
        if (defined('KING_ADDONS_IS_MAINTENANCE_PAGE') && KING_ADDONS_IS_MAINTENANCE_PAGE) {
            return;
        }

        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        if (!$this->is_enabled()) {
            return;
        }

        $status = $this->get_cookie_status();
        $behaviour = $this->options['behaviour'];

        if ($status !== 'denied' || !$this->is_deny_action_redirect($behaviour['deny_action'] ?? '')) {
            return;
        }

        $deny_action = (string) ($behaviour['deny_action'] ?? '');
        if ($deny_action === 'redirect_page' || $deny_action === 'redirect') {
            $redirect_id = isset($behaviour['deny_redirect_page']) ? (int) $behaviour['deny_redirect_page'] : 0;
            if ($redirect_id && is_page($redirect_id)) {
                return;
            }
        }

        $url = $this->resolve_deny_redirect_url();
        if (!$url) {
            return;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        if ($request_uri) {
            $current_url = home_url($request_uri);
            if (untrailingslashit($current_url) === untrailingslashit($url)) {
                return;
            }
        }

        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $target_host = wp_parse_url($url, PHP_URL_HOST);

        // Prefer safe redirects for internal destinations; allow external URLs when configured by admin.
        if ($site_host && $target_host && strtolower((string) $site_host) === strtolower((string) $target_host)) {
            wp_safe_redirect($url);
        } else {
            wp_redirect($url);
        }
        exit;
    }

    /**
     * Determines whether the overlay should render.
     *
     * @return bool
     */
    public function should_render(): bool
    {
        if (defined('KING_ADDONS_IS_MAINTENANCE_PAGE') && KING_ADDONS_IS_MAINTENANCE_PAGE) {
            return false;
        }

        if (is_admin() || wp_doing_ajax()) {
            return false;
        }

        if (!$this->is_enabled()) {
            return false;
        }

        if ($this->is_preview_mode()) {
            return true;
        }

        if (!$this->passes_display_rules()) {
            return false;
        }

        if ($this->is_excluded_page()) {
            return false;
        }

        $status = $this->get_cookie_status();

        if ($status === 'allowed') {
            return false;
        }

        if ($status === 'denied' && $this->is_deny_action_redirect($this->options['behaviour']['deny_action'] ?? '') && $this->has_deny_redirect_target()) {
            return false;
        }

        return true;
    }

    /**
     * Indicates that a blocked state still needs markup (deny blocking).
     *
     * @return bool
     */
    protected function should_render_block_state(): bool
    {
        if (defined('KING_ADDONS_IS_MAINTENANCE_PAGE') && KING_ADDONS_IS_MAINTENANCE_PAGE) {
            return false;
        }

        if (!$this->is_enabled()) {
            return false;
        }

        $status = $this->get_cookie_status();
        return $status === 'denied' && (($this->options['behaviour']['deny_action'] ?? '') === 'block');
    }

    /**
     * Checks if general display rules allow the gate.
     *
     * @return bool
     */
    protected function passes_display_rules(): bool
    {
        $general = $this->options['general'];
        $display = $this->options['display'];

        if ($general['audience'] === 'guests' && is_user_logged_in()) {
            return false;
        }

        $scope = $display['scope'];

        if ($scope === 'posts') {
            return is_singular('post');
        }

        if ($scope === 'pages') {
            return is_page();
        }

        return true;
    }

    /**
     * Checks if current page is excluded from gating.
     *
     * @return bool
     */
    protected function is_excluded_page(): bool
    {
        if (!is_singular()) {
            return false;
        }

        $exclude_ids = array_map('absint', $this->options['display']['exclude_ids']);
        $current_id = get_the_ID();

        if (in_array($current_id, $exclude_ids, true)) {
            return true;
        }

        $deny_redirect_id = isset($this->options['behaviour']['deny_redirect_page']) ? (int)$this->options['behaviour']['deny_redirect_page'] : 0;
        if ($deny_redirect_id && $deny_redirect_id === $current_id) {
            return true;
        }

        return false;
    }

    /**
     * Builds the frontend payload localized to JS.
     *
     * @return array<string, mixed>
     */
    public function get_frontend_payload(): array
    {
        $design = $this->options['design'];
        $behaviour = $this->options['behaviour'];
        $general = $this->options['general'];
        $dob = $this->options['dob'];

        $deny_redirect_id = isset($behaviour['deny_redirect_page']) ? (int) $behaviour['deny_redirect_page'] : 0;
        $deny_redirect_url = $this->resolve_deny_redirect_url();
        $deny_action_raw = (string) ($behaviour['deny_action'] ?? 'block');
        $deny_action = $this->is_deny_action_redirect($deny_action_raw) ? 'redirect' : 'block';
        if ($deny_action === 'redirect' && !$deny_redirect_url) {
            $deny_action = 'block';
        }

        return [
            'enabled' => $this->is_enabled(),
            'mode' => $general['mode'],
            'minAge' => $this->resolve_minimum_age(),
            'texts' => [
                'title' => $design['title'],
                'subtitle' => $design['subtitle'],
                'yes' => $design['button_yes'],
                'no' => $design['button_no'],
                'block' => $behaviour['block_message'],
            ],
            'design' => [
                'template' => $design['template'],
                'overlayColor' => $design['overlay_color'],
                'overlayOpacity' => $design['overlay_opacity'],
                'cardBackground' => $design['card_background'],
                'cardWidth' => $design['card_width'],
                'cardAlign' => $design['card_align'],
                'textColor' => $design['text_color'],
                'titleSize' => $design['title_size'],
                'bodySize' => $design['body_size'],
                'titleWeight' => $design['title_weight'],
                'bodyWeight' => $design['body_weight'],
                'buttonYesColor' => $design['button_yes_color'],
                'buttonYesBg' => $design['button_yes_bg'],
                'buttonNoColor' => $design['button_no_color'],
                'buttonNoBg' => $design['button_no_bg'],
                'buttonYesHoverBg' => $design['button_yes_hover_bg'] ?? $design['button_yes_bg'],
                'buttonYesHoverColor' => $design['button_yes_hover_color'] ?? $design['button_yes_color'],
                'buttonNoHoverBg' => $design['button_no_hover_bg'] ?? $design['button_no_bg'],
                'buttonNoHoverColor' => $design['button_no_hover_color'] ?? $design['button_no_color'],
                'animation' => $design['animation'],
                'logo' => $design['logo'],
                'backgroundImage' => $design['background_image'],
            ],
            'behaviour' => [
                'denyAction' => $deny_action,
                'denyRedirect' => $deny_redirect_id,
                'denyRedirectUrl' => $deny_redirect_url,
                'blockMessage' => $behaviour['block_message'],
                'consentCheckbox' => (bool) $behaviour['consent_checkbox'],
                'consentLabel' => esc_html__('I agree to the policy.', 'king-addons'),
                'repeatMode' => $behaviour['repeat_mode'] ?? 'days',
                'repeatDays' => (int) ($behaviour['repeat_days'] ?? 30),
            ],
            'cookie' => [
                'name' => $this->get_cookie_name(),
                'days' => (int) $general['cookie_days'],
                'revision' => $this->options['advanced']['revision'],
                'respectRevision' => (bool) $behaviour['reset_on_rule_change'],
                'domain' => $this->get_cookie_domain(),
            ],
            'dob' => [
                'format' => $dob['format'],
                'errors' => [
                    'invalid' => $dob['error_invalid'],
                    'denied' => $dob['error_denied'],
                ],
            ],
            'status' => $this->get_cookie_status(),
            'shouldRender' => $this->should_render() || $this->is_preview_mode(),
            'isPreview' => $this->is_preview_mode(),
            'ajax' => [
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(self::NONCE_ACTION),
                'action' => self::AJAX_ACTION_DOB,
            ],
            'isPremium' => $this->is_premium(),
            'elementorTemplate' => '',
        ];
    }

    /**
     * Preview mode: force render Age Gate for admins via query param.
     * Example: /?ka_age_gate_preview=1
     */
    protected function is_preview_mode(): bool
    {
        if (is_admin() || wp_doing_ajax()) {
            return false;
        }

        if (!$this->is_enabled()) {
            return false;
        }

        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            return false;
        }

        return isset($_GET['ka_age_gate_preview']) && (string) $_GET['ka_age_gate_preview'] === '1';
    }

    /**
     * Indicates whether premium code is available.
     *
     * @return bool
     */
    protected function is_premium(): bool
    {
        if (!function_exists('king_addons_freemius')) {
            return false;
        }

        $fs = king_addons_freemius();
        if (!is_object($fs) || !method_exists($fs, 'can_use_premium_code')) {
            return false;
        }

        return (bool) $fs->can_use_premium_code();
    }

    /**
     * Returns the sanitized options merged with defaults.
     *
     * @return array<string, mixed>
     */
    public function get_options(): array
    {
        $saved = get_option(self::OPTION_NAME, []);
        $defaults = $this->get_default_options();

        return wp_parse_args($saved, $defaults);
    }

    /**
     * Default options for the feature.
     *
     * @return array<string, mixed>
     */
    public function get_default_options(): array
    {
        return [
            'general' => [
                'enabled' => false,
                'audience' => 'guests',
                'mode' => 'confirm',
                'min_age' => 18,
                'cookie_days' => 30,
            ],
            'display' => [
                'scope' => 'site',
                'exclude_ids' => [],
                'mode' => 'global',
                'cpt_scope' => [],
                'archives' => false,
                'woo' => [
                    'enabled' => false,
                    'apply_to' => 'product',
                    'categories' => [],
                ],
            ],
            'design' => [
                'template' => 'center-card',
                'overlay_color' => '#0d0d0d',
                'overlay_opacity' => 0.7,
                'card_background' => '#ffffff',
                'card_width' => 520,
                'card_align' => 'center',
                'title' => esc_html__('Age verification required', 'king-addons'),
                'subtitle' => esc_html__('This content is restricted to visitors over the specified age.', 'king-addons'),
                'button_yes' => esc_html__('Yes, continue', 'king-addons'),
                'button_no' => esc_html__('No, leave', 'king-addons'),
                'text_color' => '#111827',
                'title_size' => 24,
                'body_size' => 16,
                'title_weight' => 700,
                'body_weight' => 400,
                'button_yes_color' => '#ffffff',
                'button_yes_bg' => '#10b981',
                'button_no_color' => '#ffffff',
                'button_no_bg' => '#ef4444',
                'animation' => 'none',
                'logo' => '',
                'background_image' => '',
                'button_yes_hover_bg' => '#0f9c75',
                'button_no_hover_bg' => '#d92d20',
                'button_yes_hover_color' => '#ffffff',
                'button_no_hover_color' => '#ffffff',
                'elementor_template' => 0,
            ],
            'behaviour' => [
                'deny_action' => 'redirect_url',
                'deny_redirect_page' => 0,
                'deny_redirect_url' => 'https://google.com',
                'block_message' => esc_html__('Access denied. You do not meet the minimum age requirement.', 'king-addons'),
                'consent_checkbox' => false,
                'reset_on_rule_change' => true,
                'repeat_mode' => 'days',
                'repeat_days' => 30,
            ],
            'advanced' => [
                'revision' => time(),
            ],
            'geo' => [
                'enabled' => false,
                'default_age' => 18,
                'map' => [],
            ],
            'dob' => [
                'format' => 'dmy',
                'max_age' => 120,
                'error_invalid' => esc_html__('Enter a valid date of birth.', 'king-addons'),
                'error_denied' => esc_html__('You do not meet the minimum age requirement.', 'king-addons'),
            ],
        ];
    }

    /**
     * Sanitizes and normalizes saved options.
     *
     * @param mixed $raw Raw submitted options.
     * @return array<string, mixed>
     */
    public function sanitize_options($raw): array
    {
        $raw = is_array($raw) ? $raw : [];
        $current = $this->get_options();
        $defaults = $this->get_default_options();

        $general = $raw['general'] ?? [];
        $display = $raw['display'] ?? [];
        $design = $raw['design'] ?? [];
        $behaviour = $raw['behaviour'] ?? [];

        $allowed_templates = [
            'center-card',
            'bottom-card',
            'top-card',
            'side-left',
            'side-right',
            'fullscreen',
        ];

        $template = in_array($design['template'] ?? 'center-card', $allowed_templates, true)
            ? $design['template']
            : 'center-card';

        // Back-compat: if card_align isn't explicitly set, infer it from template.
        $inferred_align = 'center';
        if ($template === 'bottom-card') {
            $inferred_align = 'bottom';
        } elseif ($template === 'top-card') {
            $inferred_align = 'top';
        }

        $deny_action_raw = (string) ($behaviour['deny_action'] ?? ($defaults['behaviour']['deny_action'] ?? 'redirect_url'));
        if ($deny_action_raw === 'redirect') {
            // Back-compat: older saved values.
            $deny_action_raw = 'redirect_page';
        }
        $allowed_deny_actions = ['redirect_page', 'redirect_url', 'block'];
        $deny_action = in_array($deny_action_raw, $allowed_deny_actions, true) ? $deny_action_raw : ($defaults['behaviour']['deny_action'] ?? 'redirect_url');

        $sanitized = [
            'general' => [
                'enabled' => !empty($general['enabled']),
                'audience' => in_array($general['audience'] ?? 'guests', ['guests', 'all'], true) ? $general['audience'] : $defaults['general']['audience'],
                'mode' => in_array($general['mode'] ?? 'confirm', ['confirm', 'minimum'], true) ? $general['mode'] : 'confirm',
                'min_age' => max(0, absint($general['min_age'] ?? $defaults['general']['min_age'])),
                'cookie_days' => max(0, absint($general['cookie_days'] ?? $defaults['general']['cookie_days'])),
            ],
            'display' => [
                'scope' => in_array($display['scope'] ?? 'site', ['site', 'posts', 'pages'], true) ? $display['scope'] : $defaults['display']['scope'],
                'exclude_ids' => array_values(array_filter(array_map('absint', $display['exclude_ids'] ?? []))),
            ],
            'design' => [
                'template' => $template,
                'overlay_color' => sanitize_hex_color($design['overlay_color'] ?? $defaults['design']['overlay_color']) ?: $defaults['design']['overlay_color'],
                'overlay_opacity' => min(1, max(0, floatval($design['overlay_opacity'] ?? $defaults['design']['overlay_opacity']))),
                'card_background' => sanitize_hex_color($design['card_background'] ?? $defaults['design']['card_background']) ?: $defaults['design']['card_background'],
                'card_width' => max(280, absint($design['card_width'] ?? $defaults['design']['card_width'])),
                'card_align' => in_array($design['card_align'] ?? $inferred_align, ['center', 'bottom', 'top'], true) ? ($design['card_align'] ?? $inferred_align) : 'center',
                'title' => sanitize_text_field($design['title'] ?? $defaults['design']['title']),
                'subtitle' => sanitize_text_field($design['subtitle'] ?? $defaults['design']['subtitle']),
                'button_yes' => sanitize_text_field($design['button_yes'] ?? $defaults['design']['button_yes']),
                'button_no' => sanitize_text_field($design['button_no'] ?? $defaults['design']['button_no']),
                'text_color' => sanitize_hex_color($design['text_color'] ?? $defaults['design']['text_color']) ?: $defaults['design']['text_color'],
                'title_size' => max(10, absint($design['title_size'] ?? $defaults['design']['title_size'])),
                'body_size' => max(10, absint($design['body_size'] ?? $defaults['design']['body_size'])),
                'title_weight' => max(100, absint($design['title_weight'] ?? $defaults['design']['title_weight'])),
                'body_weight' => max(100, absint($design['body_weight'] ?? $defaults['design']['body_weight'])),
                'button_yes_color' => sanitize_hex_color($design['button_yes_color'] ?? $defaults['design']['button_yes_color']) ?: $defaults['design']['button_yes_color'],
                'button_yes_bg' => sanitize_hex_color($design['button_yes_bg'] ?? $defaults['design']['button_yes_bg']) ?: $defaults['design']['button_yes_bg'],
                'button_no_color' => sanitize_hex_color($design['button_no_color'] ?? $defaults['design']['button_no_color']) ?: $defaults['design']['button_no_color'],
                'button_no_bg' => sanitize_hex_color($design['button_no_bg'] ?? $defaults['design']['button_no_bg']) ?: $defaults['design']['button_no_bg'],
                'animation' => in_array($design['animation'] ?? 'none', ['none', 'fade', 'slide-up', 'slide-down'], true) ? $design['animation'] : 'none',
                'logo' => esc_url_raw($design['logo'] ?? ''),
                'background_image' => esc_url_raw($design['background_image'] ?? ''),
            ],
            'behaviour' => [
                'deny_action' => $deny_action,
                'deny_redirect_page' => absint($behaviour['deny_redirect_page'] ?? 0),
                'deny_redirect_url' => $this->sanitize_redirect_url($behaviour['deny_redirect_url'] ?? ($defaults['behaviour']['deny_redirect_url'] ?? '')),
                'block_message' => sanitize_text_field($behaviour['block_message'] ?? $defaults['behaviour']['block_message']),
                'consent_checkbox' => !empty($behaviour['consent_checkbox']),
                'reset_on_rule_change' => isset($behaviour['reset_on_rule_change']) ? (bool) $behaviour['reset_on_rule_change'] : $defaults['behaviour']['reset_on_rule_change'],
                'repeat_mode' => in_array($behaviour['repeat_mode'] ?? 'days', ['days', 'session', 'once'], true) ? $behaviour['repeat_mode'] : 'days',
                'repeat_days' => max(0, absint($behaviour['repeat_days'] ?? $defaults['behaviour']['repeat_days'])),
            ],
            'advanced' => [
                'revision' => $current['advanced']['revision'] ?? time(),
            ],
            'geo' => $this->sanitize_geo_options($raw),
            'dob' => $this->sanitize_dob_options($raw),
        ];

        // Force revision bump when rule reset is enabled and settings are updated.
        if (!empty($sanitized['behaviour']['reset_on_rule_change'])) {
            $sanitized['advanced']['revision'] = time();
        }

        $this->options = wp_parse_args($sanitized, $this->get_default_options());

        return $this->options;
    }

    /**
     * Sanitizes geo options.
     *
     * @param array<string, mixed> $raw Raw input.
     * @return array<string, mixed>
     */
    protected function sanitize_geo_options(array $raw): array
    {
        $defaults = $this->get_default_options()['geo'];
        $geo = $raw['geo'] ?? [];

        $enabled = !empty($geo['enabled']) && $this->is_premium();
        $default_age = max(0, absint($geo['default_age'] ?? $defaults['default_age']));

        // Parse geo map from textarea (format: "US=21\nUK=18")
        $map = [];
        if (!empty($geo['map']) && is_string($geo['map'])) {
            $lines = explode("\n", $geo['map']);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '=') === false) {
                    continue;
                }
                [$code, $age] = explode('=', $line, 2);
                $code = strtoupper(trim(sanitize_text_field($code)));
                $age = absint(trim($age));
                if (strlen($code) === 2 && $age > 0) {
                    $map[$code] = $age;
                }
            }
        } elseif (is_array($geo['map'] ?? null)) {
            // Already an array (from existing options)
            foreach ($geo['map'] as $code => $age) {
                $code = strtoupper(sanitize_text_field($code));
                $map[$code] = absint($age);
            }
        }

        return [
            'enabled' => $enabled,
            'default_age' => $default_age,
            'map' => $map,
        ];
    }

    /**
     * Sanitizes DOB options.
     *
     * @param array<string, mixed> $raw Raw input.
     * @return array<string, mixed>
     */
    protected function sanitize_dob_options(array $raw): array
    {
        $defaults = $this->get_default_options()['dob'];
        $dob = $raw['dob'] ?? [];

        return [
            'format' => in_array($dob['format'] ?? 'dmy', ['dmy', 'mdy', 'ymd'], true) ? $dob['format'] : 'dmy',
            'max_age' => max(10, absint($dob['max_age'] ?? $defaults['max_age'])),
            'error_invalid' => sanitize_text_field($dob['error_invalid'] ?? $defaults['error_invalid']),
            'error_denied' => sanitize_text_field($dob['error_denied'] ?? $defaults['error_denied']),
        ];
    }

    /**
     * Sanitizes a custom redirect URL.
     *
     * @param mixed $raw_url Raw URL.
     * @return string Sanitized URL or empty string.
     */
    protected function sanitize_redirect_url($raw_url): string
    {
        $url = is_string($raw_url) ? trim($raw_url) : '';
        if ($url === '') {
            return '';
        }

        $url = esc_url_raw($url);
        if ($url && wp_http_validate_url($url)) {
            return $url;
        }

        return '';
    }

    /**
     * Returns the effective redirect destination when denial action is redirect.
     * Prefers an internal selected page, otherwise falls back to the custom URL.
     */
    protected function resolve_deny_redirect_url(): string
    {
        $behaviour = $this->options['behaviour'] ?? [];
        $deny_action = (string) ($behaviour['deny_action'] ?? '');

        // Legacy back-compat.
        if ($deny_action === 'redirect') {
            $deny_action = 'redirect_page';
        }

        $custom_url = isset($behaviour['deny_redirect_url']) ? $this->sanitize_redirect_url($behaviour['deny_redirect_url']) : '';

        if ($deny_action === 'redirect_url') {
            return $custom_url;
        }

        // redirect_page (default): prefer page ID, but fall back to URL if provided.
        $redirect_id = isset($behaviour['deny_redirect_page']) ? (int) $behaviour['deny_redirect_page'] : 0;
        if ($redirect_id) {
            return get_permalink($redirect_id) ?: '';
        }

        return $custom_url;
    }

    /**
     * True for redirect-based deny actions.
     */
    protected function is_deny_action_redirect(string $deny_action): bool
    {
        return in_array($deny_action, ['redirect', 'redirect_page', 'redirect_url'], true);
    }

    /**
     * Whether a denial redirect destination is configured.
     */
    protected function has_deny_redirect_target(): bool
    {
        return $this->resolve_deny_redirect_url() !== '';
    }

    /**
     * Determines whether the feature is active.
     *
     * @return bool
     */
    protected function is_enabled(): bool
    {
        return !empty($this->options['general']['enabled']);
    }

    /**
     * Returns the cookie name to use.
     *
     * @return string
     */
    protected function get_cookie_name(): string
    {
        return self::COOKIE_NAME;
    }

    /**
     * Parses the stored cookie status.
     *
     * @return string allowed|denied|dob:{date}|''
     */
    protected function get_cookie_status(): string
    {
        $cookie_name = $this->get_cookie_name();

        if (!isset($_COOKIE[$cookie_name])) {
            return '';
        }

        $raw = sanitize_text_field(wp_unslash($_COOKIE[$cookie_name]));
        $parts = explode('|', $raw);
        $status = $parts[0] ?? '';
        $revision = $parts[1] ?? '';

        if (!empty($this->options['behaviour']['reset_on_rule_change'])) {
            if ($revision && (string)$revision !== (string)$this->options['advanced']['revision']) {
                return '';
            }
        }

        if ($status === 'allowed' || $status === 'denied' || strpos($status, 'dob:') === 0) {
            return $status;
        }

        return '';
    }

    /**
     * Writes the status cookie with revision marker.
     *
     * @param string $status allowed|denied|dob:{date}
     * @param int    $days   Cookie lifetime in days. Zero for session cookie.
     * @return void
     */
    protected function set_status_cookie(string $status, int $days): void
    {
        $value = $status . '|' . $this->options['advanced']['revision'];
        $expire = $days > 0 ? time() + (DAY_IN_SECONDS * $days) : 0;
        setcookie(
            $this->get_cookie_name(),
            $value,
            [
                'expires' => $expire,
                'path' => '/',
                'domain' => $this->get_cookie_domain(),
                'secure' => is_ssl(),
                'httponly' => false,
                'samesite' => 'Lax',
            ]
        );
        $_COOKIE[$this->get_cookie_name()] = $value;
    }

    /**
     * Resolves cookie domain to current host.
     *
     * @return string
     */
    protected function get_cookie_domain(): string
    {
        $host = wp_parse_url(home_url(), PHP_URL_HOST);
        return $host ? $host : '';
    }
}



