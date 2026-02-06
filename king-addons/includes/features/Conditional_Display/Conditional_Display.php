<?php
/**
 * Conditional Display feature.
 *
 * Dynamically show or hide Elementor elements based on various conditions
 * including user roles, dates, devices, URL parameters, WooCommerce conditions, and more.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Conditional Display class.
 *
 * Provides controls and runtime logic to conditionally show/hide Elementor elements.
 */
class Conditional_Display
{
    /**
     * Cached Elementor templates list.
     *
     * @var array<int|string,string>|null
     */
    private static ?array $templates_cache = null;

    /**
     * Cached WP roles list.
     *
     * @var array<string,string>|null
     */
    private static ?array $roles_cache = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Editor assets.
        add_action('elementor/preview/enqueue_scripts', [$this, 'enqueue_preview_script'], 1);
        add_action('elementor/preview/enqueue_styles', [$this, 'enqueue_styles'], 1);
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'enqueue_styles'], 1);

        // Controls registration.
        add_action('elementor/element/common/_section_style/after_section_end', [$this, 'register_controls'], 10, 2);
        add_action('elementor/element/section/section_advanced/after_section_end', [$this, 'register_controls'], 10, 2);
        add_action('elementor/element/container/section_layout/after_section_end', [$this, 'register_controls'], 10, 2);
        add_action('elementor/element/column/section_advanced/after_section_end', [$this, 'register_controls'], 10, 2);

        // Runtime filters for proper element hiding.
        add_filter('elementor/frontend/widget/should_render', [$this, 'should_render_element'], 10, 2);
        add_filter('elementor/frontend/section/should_render', [$this, 'should_render_element'], 10, 2);
        add_filter('elementor/frontend/container/should_render', [$this, 'should_render_element'], 10, 2);
        add_filter('elementor/frontend/column/should_render', [$this, 'should_render_element'], 10, 2);

        // Before render for editor marking and fallback content.
        add_action('elementor/frontend/widget/before_render', [$this, 'before_render_element']);
        add_action('elementor/frontend/section/before_render', [$this, 'before_render_element']);
        add_action('elementor/frontend/container/before_render', [$this, 'before_render_element']);
        add_action('elementor/frontend/column/before_render', [$this, 'before_render_element']);
    }

    /**
     * Enqueue preview script for Elementor editor.
     *
     * @return void
     */
    public function enqueue_preview_script(): void
    {
        wp_enqueue_script(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-conditional-display-preview-handler',
            KING_ADDONS_URL . 'includes/features/Conditional_Display/preview-handler.js',
            ['jquery'],
            KING_ADDONS_VERSION,
            true
        );
    }

    /**
     * Enqueue styles for editor preview and frontend.
     *
     * @return void
     */
    public function enqueue_styles(): void
    {
        wp_enqueue_style(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-conditional-display-style',
            KING_ADDONS_URL . 'includes/features/Conditional_Display/style.css',
            [],
            KING_ADDONS_VERSION
        );
    }

    /**
     * Register Conditional Display controls.
     *
     * @param Element_Base $element Elementor element.
     * @param array<mixed> $args    Hook arguments.
     *
     * @return void
     */
    public function register_controls(Element_Base $element, $args): void
    {
        $element->start_controls_section(
            'king_addons_cd_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Conditional Display', 'king-addons'),
                'tab' => Controls_Manager::TAB_ADVANCED,
            ]
        );

        // Main enable switch.
        $element->add_control(
            'cd_enable',
            [
                'label' => esc_html__('Enable Conditional Display', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        // Conditions relation (AND/OR).
        $element->add_control(
            'cd_relation',
            [
                'label' => esc_html__('Conditions Relation', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'all' => esc_html__('Match all conditions (AND)', 'king-addons'),
                    'any' => esc_html__('Match any condition (OR)', 'king-addons'),
                ],
                'default' => 'all',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        // Invert result switch.
        $element->add_control(
            'cd_invert',
            [
                'label' => esc_html__('Invert Result', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'description' => esc_html__('If enabled, element will show when conditions are NOT met.', 'king-addons'),
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        // Fallback type selection.
        $element->add_control(
            'cd_fallback_type',
            [
                'label' => esc_html__('When conditions are not met', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'hide' => esc_html__('Hide element', 'king-addons'),
                    'message' => esc_html__('Show message', 'king-addons'),
                    'template' => sprintf(__('Render template %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'default' => 'hide',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        // Fallback message textarea.
        $element->add_control(
            'cd_fallback_msg',
            [
                'label' => esc_html__('Fallback Message', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 3,
                'default' => '',
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_fallback_type' => 'message',
                ],
            ]
        );

        // Fallback template selector (Pro).
        $element->add_control(
            'cd_fallback_template',
            [
                'label' => esc_html__('Template', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'options' => $this->get_elementor_templates_options(),
                'label_block' => true,
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_fallback_type' => 'template',
                ],
            ]
        );

        // ========== USER CONDITIONS ==========
        $element->add_control(
            'cd_heading_user',
            [
                'label' => esc_html__('User Conditions', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        // Login status.
        $element->add_control(
            'cd_cond_login_mode',
            [
                'label' => esc_html__('Login Status', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'any' => esc_html__('Any (ignore)', 'king-addons'),
                    'logged_in' => esc_html__('Logged in only', 'king-addons'),
                    'logged_out' => esc_html__('Logged out only', 'king-addons'),
                ],
                'default' => 'any',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        // User roles multi-select.
        $element->add_control(
            'cd_cond_roles',
            [
                'label' => esc_html__('Allowed Roles', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_wp_roles_for_control(),
                'label_block' => true,
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_login_mode' => 'logged_in',
                ],
            ]
        );

        // ========== DEVICE CONDITIONS ==========
        $element->add_control(
            'cd_heading_device',
            [
                'label' => esc_html__('Device Conditions', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_device_enable',
            [
                'label' => esc_html__('Device Condition', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_device_types',
            [
                'label' => esc_html__('Show on Devices', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => [
                    'desktop' => esc_html__('Desktop', 'king-addons'),
                    'tablet' => esc_html__('Tablet', 'king-addons'),
                    'mobile' => esc_html__('Mobile', 'king-addons'),
                ],
                'label_block' => true,
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_device_enable' => 'yes',
                ],
            ]
        );

        // Browser condition (Pro).
        $element->add_control(
            'cd_cond_browser_enable',
            [
                'label' => sprintf(__('Browser Condition %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_browser_types',
            [
                'label' => esc_html__('Show on Browsers', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => [
                    'chrome' => esc_html__('Chrome', 'king-addons'),
                    'firefox' => esc_html__('Firefox', 'king-addons'),
                    'safari' => esc_html__('Safari', 'king-addons'),
                    'edge' => esc_html__('Edge', 'king-addons'),
                    'opera' => esc_html__('Opera', 'king-addons'),
                    'ie' => esc_html__('Internet Explorer', 'king-addons'),
                ],
                'label_block' => true,
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_browser_enable' => 'yes',
                ],
            ]
        );

        // ========== DATE / TIME CONDITIONS ==========
        $element->add_control(
            'cd_heading_datetime',
            [
                'label' => esc_html__('Date / Time Conditions', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_date_enable',
            [
                'label' => esc_html__('Date Range Condition', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_date_from',
            [
                'label' => esc_html__('Date From', 'king-addons'),
                'type' => Controls_Manager::DATE_TIME,
                'label_block' => true,
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_date_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_date_to',
            [
                'label' => esc_html__('Date To', 'king-addons'),
                'type' => Controls_Manager::DATE_TIME,
                'label_block' => true,
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_date_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_days_of_week',
            [
                'label' => esc_html__('Days of Week', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => [
                    'mon' => esc_html__('Monday', 'king-addons'),
                    'tue' => esc_html__('Tuesday', 'king-addons'),
                    'wed' => esc_html__('Wednesday', 'king-addons'),
                    'thu' => esc_html__('Thursday', 'king-addons'),
                    'fri' => esc_html__('Friday', 'king-addons'),
                    'sat' => esc_html__('Saturday', 'king-addons'),
                    'sun' => esc_html__('Sunday', 'king-addons'),
                ],
                'label_block' => true,
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_date_enable' => 'yes',
                ],
            ]
        );

        // Time of day condition.
        $element->add_control(
            'cd_cond_time_enable',
            [
                'label' => esc_html__('Time of Day Condition', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_time_from',
            [
                'label' => esc_html__('Time From (HH:MM)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => '09:00',
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_time_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_time_to',
            [
                'label' => esc_html__('Time To (HH:MM)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => '18:00',
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_time_enable' => 'yes',
                ],
            ]
        );

        // ========== URL / QUERY CONDITIONS ==========
        $element->add_control(
            'cd_heading_url',
            [
                'label' => esc_html__('URL / Query Conditions', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_url_enable',
            [
                'label' => esc_html__('URL Condition', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_url_contains',
            [
                'label' => esc_html__('URL Contains', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_url_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_get_key',
            [
                'label' => esc_html__('GET Parameter Name', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_url_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_get_value',
            [
                'label' => esc_html__('GET Parameter Value (optional)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Leave empty to check only parameter existence.', 'king-addons'),
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_url_enable' => 'yes',
                ],
            ]
        );

        // ========== PRO CONDITIONS ==========
        $element->add_control(
            'cd_heading_pro',
            [
                'label' => esc_html__('Pro Conditions', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        // Referrer condition (Pro).
        $element->add_control(
            'cd_cond_ref_enable',
            [
                'label' => sprintf(__('Referrer Condition %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_ref_contains',
            [
                'label' => esc_html__('Referrer Contains', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_ref_enable' => 'yes',
                ],
            ]
        );

        // WooCommerce conditions (Pro).
        $element->add_control(
            'cd_cond_woo_enable',
            [
                'label' => sprintf(__('WooCommerce Conditions %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_woo_min_cart_total',
            [
                'label' => esc_html__('Minimum Cart Total', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'step' => 0.01,
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_woo_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_woo_product_in_cart',
            [
                'label' => esc_html__('Products in Cart (IDs)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Comma-separated product IDs. Element shows if ANY is in cart.', 'king-addons'),
                'label_block' => true,
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_woo_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_woo_bought_products',
            [
                'label' => esc_html__('Previously Bought Products (IDs)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Comma-separated product IDs. Element shows if user bought ANY.', 'king-addons'),
                'label_block' => true,
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_woo_enable' => 'yes',
                ],
            ]
        );

        // Cookie condition (Pro).
        $element->add_control(
            'cd_cond_cookie_enable',
            [
                'label' => sprintf(__('Cookie Condition %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_cookie_name',
            [
                'label' => esc_html__('Cookie Name', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_cookie_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_cookie_value',
            [
                'label' => esc_html__('Cookie Value (optional)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Leave empty to check only cookie existence.', 'king-addons'),
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_cookie_enable' => 'yes',
                ],
            ]
        );

        // Language / Locale condition (Pro).
        $element->add_control(
            'cd_cond_lang_enable',
            [
                'label' => sprintf(__('Language Condition %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'cd_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'cd_cond_lang_codes',
            [
                'label' => esc_html__('Languages', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_language_options(),
                'label_block' => true,
                'description' => esc_html__('Works with WPML, Polylang, and WordPress locale.', 'king-addons'),
                'condition' => [
                    'cd_enable' => 'yes',
                    'cd_cond_lang_enable' => 'yes',
                ],
            ]
        );

        $element->end_controls_section();
    }

    /**
     * Get cached Elementor templates list.
     *
     * @return array<int|string,string>
     */
    private function get_elementor_templates_options(): array
    {
        if (null !== self::$templates_cache) {
            return self::$templates_cache;
        }

        $templates = get_posts([
            'post_type' => 'elementor_library',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
        ]);

        self::$templates_cache = [];

        if (!empty($templates)) {
            foreach ($templates as $template_id) {
                self::$templates_cache[$template_id] = get_the_title($template_id);
            }
        }

        return self::$templates_cache;
    }

    /**
     * Get cached WP roles list.
     *
     * @return array<string,string>
     */
    private function get_wp_roles_for_control(): array
    {
        if (null !== self::$roles_cache) {
            return self::$roles_cache;
        }

        global $wp_roles;
        self::$roles_cache = [];

        if (!isset($wp_roles)) {
            $wp_roles = wp_roles();
        }

        foreach ($wp_roles->roles as $key => $role) {
            self::$roles_cache[$key] = $role['name'];
        }

        return self::$roles_cache;
    }

    /**
     * Get language options for SELECT2.
     *
     * @return array<string,string>
     */
    private function get_language_options(): array
    {
        return [
            'en' => 'English',
            'es' => 'Spanish (Español)',
            'fr' => 'French (Français)',
            'de' => 'German (Deutsch)',
            'it' => 'Italian (Italiano)',
            'pt' => 'Portuguese (Português)',
            'ru' => 'Russian (Русский)',
            'zh' => 'Chinese (中文)',
            'ja' => 'Japanese (日本語)',
            'ko' => 'Korean (한국어)',
            'ar' => 'Arabic (العربية)',
            'hi' => 'Hindi (हिन्दी)',
            'nl' => 'Dutch (Nederlands)',
            'pl' => 'Polish (Polski)',
            'tr' => 'Turkish (Türkçe)',
            'sv' => 'Swedish (Svenska)',
            'da' => 'Danish (Dansk)',
            'fi' => 'Finnish (Suomi)',
            'no' => 'Norwegian (Norsk)',
            'cs' => 'Czech (Čeština)',
            'el' => 'Greek (Ελληνικά)',
            'he' => 'Hebrew (עברית)',
            'th' => 'Thai (ไทย)',
            'vi' => 'Vietnamese (Tiếng Việt)',
            'id' => 'Indonesian (Bahasa Indonesia)',
            'uk' => 'Ukrainian (Українська)',
            'ro' => 'Romanian (Română)',
            'hu' => 'Hungarian (Magyar)',
            'bg' => 'Bulgarian (Български)',
            'sk' => 'Slovak (Slovenčina)',
        ];
    }

    /**
     * Filter: Should render element.
     *
     * This method is used to completely prevent element rendering.
     *
     * @param bool         $should_render Default render decision.
     * @param Element_Base $element       Elementor element.
     *
     * @return bool
     */
    public function should_render_element(bool $should_render, Element_Base $element): bool
    {
        if (!$should_render) {
            return false;
        }

        $settings = $element->get_settings_for_display();

        if (empty($settings['cd_enable']) || 'yes' !== $settings['cd_enable']) {
            return true;
        }

        // Always render in editor/preview mode.
        if ($this->is_editor_or_preview()) {
            return true;
        }

        $result = $this->evaluate_conditions($settings);

        // Handle inversion.
        if (!empty($settings['cd_invert']) && 'yes' === $settings['cd_invert']) {
            $result = !$result;
        }

        // If conditions pass, render normally.
        if ($result) {
            return true;
        }

        // Check if we need to show fallback content.
        $fallback = $settings['cd_fallback_type'] ?? 'hide';
        if ('hide' === $fallback) {
            return false; // Don't render at all.
        }

        // For message/template fallback, we still need to render.
        return true;
    }

    /**
     * Before render: mark element in editor or output fallback.
     *
     * @param Element_Base $element Elementor element.
     *
     * @return void
     */
    public function before_render_element(Element_Base $element): void
    {
        $settings = $element->get_settings_for_display();

        if (empty($settings['cd_enable']) || 'yes' !== $settings['cd_enable']) {
            return;
        }

        // Mark element in editor/preview for visual indication.
        if ($this->is_editor_or_preview()) {
            $element->add_render_attribute('_wrapper', 'data-king-cd-enabled', 'yes');
            $element->add_render_attribute('_wrapper', 'class', 'king-addons-cd-preview');
            
            // Add data attributes for condition info.
            $active_conditions = $this->get_active_conditions_list($settings);
            if (!empty($active_conditions)) {
                $element->add_render_attribute('_wrapper', 'data-king-cd-conditions', esc_attr(implode(', ', $active_conditions)));
            }
            return;
        }

        $result = $this->evaluate_conditions($settings);

        if (!empty($settings['cd_invert']) && 'yes' === $settings['cd_invert']) {
            $result = !$result;
        }

        // If conditions pass, do nothing.
        if ($result) {
            return;
        }

        // Apply fallback.
        $this->apply_fallback($element, $settings);
    }

    /**
     * Get list of active conditions for editor display.
     *
     * @param array<string,mixed> $settings Element settings.
     *
     * @return array<string>
     */
    private function get_active_conditions_list(array $settings): array
    {
        $conditions = [];

        $mode = $settings['cd_cond_login_mode'] ?? 'any';
        if ('logged_in' === $mode) {
            $conditions[] = 'User: Logged In';
        } elseif ('logged_out' === $mode) {
            $conditions[] = 'User: Logged Out';
        }

        if (!empty($settings['cd_cond_device_enable']) && 'yes' === $settings['cd_cond_device_enable']) {
            $conditions[] = 'Device';
        }

        if (!empty($settings['cd_cond_browser_enable']) && 'yes' === $settings['cd_cond_browser_enable']) {
            $conditions[] = 'Browser';
        }

        if (!empty($settings['cd_cond_date_enable']) && 'yes' === $settings['cd_cond_date_enable']) {
            $conditions[] = 'Date';
        }

        if (!empty($settings['cd_cond_time_enable']) && 'yes' === $settings['cd_cond_time_enable']) {
            $conditions[] = 'Time';
        }

        if (!empty($settings['cd_cond_url_enable']) && 'yes' === $settings['cd_cond_url_enable']) {
            $conditions[] = 'URL';
        }

        if (!empty($settings['cd_cond_ref_enable']) && 'yes' === $settings['cd_cond_ref_enable']) {
            $conditions[] = 'Referrer (Pro)';
        }

        if (!empty($settings['cd_cond_woo_enable']) && 'yes' === $settings['cd_cond_woo_enable']) {
            $conditions[] = 'WooCommerce (Pro)';
        }

        if (!empty($settings['cd_cond_cookie_enable']) && 'yes' === $settings['cd_cond_cookie_enable']) {
            $conditions[] = 'Cookie (Pro)';
        }

        if (!empty($settings['cd_cond_lang_enable']) && 'yes' === $settings['cd_cond_lang_enable']) {
            $conditions[] = 'Language (Pro)';
        }

        return $conditions;
    }

    /**
     * Evaluate all conditions.
     *
     * @param array<string,mixed> $settings Element settings.
     *
     * @return bool True if conditions pass (element should be shown).
     */
    private function evaluate_conditions(array $settings): bool
    {
        $relation = $settings['cd_relation'] ?? 'all';

        $results = [];

        $results[] = $this->check_login_role($settings);
        $results[] = $this->check_device($settings);
        $results[] = $this->check_browser($settings);
        $results[] = $this->check_date_time($settings);
        $results[] = $this->check_time_of_day($settings);
        $results[] = $this->check_url($settings);
        $results[] = $this->check_referrer($settings);
        $results[] = $this->check_woo($settings);
        $results[] = $this->check_cookie($settings);
        $results[] = $this->check_language($settings);

        // Filter out null values (disabled conditions).
        $results = array_filter($results, static function ($value) {
            return null !== $value;
        });

        // If no active conditions, show element by default.
        if (empty($results)) {
            return true;
        }

        if ('any' === $relation) {
            // OR: at least one must be true.
            return in_array(true, $results, true);
        }

        // AND: all must be true.
        return !in_array(false, $results, true);
    }

    /**
     * Apply fallback when conditions are not met.
     *
     * @param Element_Base        $element  Elementor element.
     * @param array<string,mixed> $settings Element settings.
     *
     * @return void
     */
    private function apply_fallback(Element_Base $element, array $settings): void
    {
        $fallback = $settings['cd_fallback_type'] ?? 'hide';

        switch ($fallback) {
            case 'message':
                $message = $settings['cd_fallback_msg'] ?? '';
                echo '<div class="king-addons-cd-fallback-message">' . wp_kses_post($message) . '</div>';
                $this->suppress_element_render($element);
                break;

            case 'template':
                if ($this->can_use_pro() && !empty($settings['cd_fallback_template'])) {
                    $this->render_template((int) $settings['cd_fallback_template']);
                }
                $this->suppress_element_render($element);
                break;

            case 'hide':
            default:
                $this->suppress_element_render($element);
                break;
        }
    }

    /**
     * Check login/role condition.
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return bool|null Null if condition is not active.
     */
    private function check_login_role(array $settings): ?bool
    {
        $mode = $settings['cd_cond_login_mode'] ?? 'any';

        if ('any' === $mode) {
            return null;
        }

        $is_logged_in = is_user_logged_in();

        if ('logged_in' === $mode && !$is_logged_in) {
            return false;
        }

        if ('logged_out' === $mode && $is_logged_in) {
            return false;
        }

        // Check specific roles if logged in.
        if ('logged_in' === $mode && $is_logged_in && !empty($settings['cd_cond_roles']) && is_array($settings['cd_cond_roles'])) {
            $user = wp_get_current_user();
            foreach ($user->roles as $role) {
                if (in_array($role, $settings['cd_cond_roles'], true)) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    /**
     * Check device condition.
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return bool|null
     */
    private function check_device(array $settings): ?bool
    {
        if (empty($settings['cd_cond_device_enable']) || 'yes' !== $settings['cd_cond_device_enable']) {
            return null;
        }

        if (empty($settings['cd_cond_device_types']) || !is_array($settings['cd_cond_device_types'])) {
            return null;
        }

        // Simple detection: mobile includes tablet.
        $is_mobile = wp_is_mobile();
        $current = $is_mobile ? 'mobile' : 'desktop';

        // Also check tablet separately using UA.
        if ($is_mobile && $this->is_tablet()) {
            $current = 'tablet';
        }

        return in_array($current, $settings['cd_cond_device_types'], true);
    }

    /**
     * Check if current device is tablet.
     *
     * @return bool
     */
    private function is_tablet(): bool
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        return (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) &&
               strpos($ua, 'mobile') === false;
    }

    /**
     * Check browser condition (Pro).
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return bool|null
     */
    private function check_browser(array $settings): ?bool
    {
        if (empty($settings['cd_cond_browser_enable']) || 'yes' !== $settings['cd_cond_browser_enable']) {
            return null;
        }

        if (!$this->can_use_pro()) {
            return true; // Pro feature not available, skip condition.
        }

        if (empty($settings['cd_cond_browser_types']) || !is_array($settings['cd_cond_browser_types'])) {
            return null;
        }

        $browser = $this->detect_browser();
        return in_array($browser, $settings['cd_cond_browser_types'], true);
    }

    /**
     * Detect current browser.
     *
     * @return string Browser identifier.
     */
    private function detect_browser(): string
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return 'unknown';
        }

        $ua = $_SERVER['HTTP_USER_AGENT'];

        // Order matters - check more specific first.
        if (preg_match('/Edg(e|A|iOS)?/i', $ua)) {
            return 'edge';
        }
        if (preg_match('/OPR|Opera/i', $ua)) {
            return 'opera';
        }
        if (preg_match('/Chrome/i', $ua) && !preg_match('/Chromium/i', $ua)) {
            return 'chrome';
        }
        if (preg_match('/Safari/i', $ua) && !preg_match('/Chrome/i', $ua)) {
            return 'safari';
        }
        if (preg_match('/Firefox/i', $ua)) {
            return 'firefox';
        }
        if (preg_match('/MSIE|Trident/i', $ua)) {
            return 'ie';
        }

        return 'unknown';
    }

    /**
     * Check date/time condition.
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return bool|null
     */
    private function check_date_time(array $settings): ?bool
    {
        if (empty($settings['cd_cond_date_enable']) || 'yes' !== $settings['cd_cond_date_enable']) {
            return null;
        }

        $now = current_time('timestamp');

        // Check date from.
        if (!empty($settings['cd_cond_date_from'])) {
            $from = strtotime((string) $settings['cd_cond_date_from']);
            if ($from && $now < $from) {
                return false;
            }
        }

        // Check date to.
        if (!empty($settings['cd_cond_date_to'])) {
            $to = strtotime((string) $settings['cd_cond_date_to']);
            if ($to && $now > $to) {
                return false;
            }
        }

        // Check days of week.
        if (!empty($settings['cd_cond_days_of_week']) && is_array($settings['cd_cond_days_of_week'])) {
            $current_day = strtolower(wp_date('D', $now));
            if (!in_array($current_day, $settings['cd_cond_days_of_week'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check time of day condition.
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return bool|null
     */
    private function check_time_of_day(array $settings): ?bool
    {
        if (empty($settings['cd_cond_time_enable']) || 'yes' !== $settings['cd_cond_time_enable']) {
            return null;
        }

        $current_time = wp_date('H:i');

        $time_from = $settings['cd_cond_time_from'] ?? '';
        $time_to = $settings['cd_cond_time_to'] ?? '';

        if (empty($time_from) && empty($time_to)) {
            return null;
        }

        if (!empty($time_from) && $current_time < $time_from) {
            return false;
        }

        if (!empty($time_to) && $current_time > $time_to) {
            return false;
        }

        return true;
    }

    /**
     * Check URL/query condition.
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return bool|null
     */
    private function check_url(array $settings): ?bool
    {
        if (empty($settings['cd_cond_url_enable']) || 'yes' !== $settings['cd_cond_url_enable']) {
            return null;
        }

        $url = (is_ssl() ? 'https://' : 'http://') . 
               ($_SERVER['HTTP_HOST'] ?? '') . 
               ($_SERVER['REQUEST_URI'] ?? '');

        // Check URL contains.
        if (!empty($settings['cd_cond_url_contains'])) {
            if (false === strpos($url, $settings['cd_cond_url_contains'])) {
                return false;
            }
        }

        // Check GET parameter.
        if (!empty($settings['cd_cond_get_key'])) {
            $key = sanitize_text_field($settings['cd_cond_get_key']);
            $expected_value = $settings['cd_cond_get_value'] ?? '';

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (!isset($_GET[$key])) {
                return false;
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ('' !== $expected_value && sanitize_text_field($_GET[$key]) !== $expected_value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check referrer condition (Pro).
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return bool|null
     */
    private function check_referrer(array $settings): ?bool
    {
        if (empty($settings['cd_cond_ref_enable']) || 'yes' !== $settings['cd_cond_ref_enable']) {
            return null;
        }

        if (!$this->can_use_pro()) {
            return true;
        }

        if (empty($_SERVER['HTTP_REFERER'])) {
            return false;
        }

        $ref = (string) $_SERVER['HTTP_REFERER'];

        if (!empty($settings['cd_cond_ref_contains'])) {
            return false !== strpos($ref, $settings['cd_cond_ref_contains']);
        }

        return true;
    }

    /**
     * Check WooCommerce conditions (Pro).
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return bool|null
     */
    private function check_woo(array $settings): ?bool
    {
        if (empty($settings['cd_cond_woo_enable']) || 'yes' !== $settings['cd_cond_woo_enable']) {
            return null;
        }

        if (!$this->can_use_pro()) {
            return true;
        }

        if (!class_exists('WooCommerce') || !function_exists('WC')) {
            return false;
        }

        $cart = WC()->cart;
        if (!$cart) {
            return false;
        }

        // Check minimum cart total.
        if (isset($settings['cd_cond_woo_min_cart_total']) && '' !== $settings['cd_cond_woo_min_cart_total']) {
            $min_total = (float) $settings['cd_cond_woo_min_cart_total'];
            $total = (float) $cart->get_total('edit');
            if ($total < $min_total) {
                return false;
            }
        }

        // Check products in cart.
        if (!empty($settings['cd_cond_woo_product_in_cart'])) {
            $ids = $this->sanitize_ids($settings['cd_cond_woo_product_in_cart']);
            if (!empty($ids)) {
                $in_cart = false;
                foreach ($cart->get_cart() as $item) {
                    $product_id = (int) ($item['product_id'] ?? 0);
                    $variation_id = (int) ($item['variation_id'] ?? 0);
                    if (in_array($product_id, $ids, true) || in_array($variation_id, $ids, true)) {
                        $in_cart = true;
                        break;
                    }
                }
                if (!$in_cart) {
                    return false;
                }
            }
        }

        // Check previously bought products.
        if (!empty($settings['cd_cond_woo_bought_products']) && is_user_logged_in()) {
            $ids = $this->sanitize_ids($settings['cd_cond_woo_bought_products']);
            if (!empty($ids)) {
                $user = wp_get_current_user();
                $bought = false;
                foreach ($ids as $pid) {
                    if (function_exists('wc_customer_bought_product') && 
                        wc_customer_bought_product($user->user_email, $user->ID, $pid)) {
                        $bought = true;
                        break;
                    }
                }
                if (!$bought) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check cookie condition (Pro).
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return bool|null
     */
    private function check_cookie(array $settings): ?bool
    {
        if (empty($settings['cd_cond_cookie_enable']) || 'yes' !== $settings['cd_cond_cookie_enable']) {
            return null;
        }

        if (!$this->can_use_pro()) {
            return true;
        }

        if (empty($settings['cd_cond_cookie_name'])) {
            return null;
        }

        $name = sanitize_text_field($settings['cd_cond_cookie_name']);
        $expected_value = $settings['cd_cond_cookie_value'] ?? '';

        if (!isset($_COOKIE[$name])) {
            return false;
        }

        if ('' !== $expected_value && (string) $_COOKIE[$name] !== (string) $expected_value) {
            return false;
        }

        return true;
    }

    /**
     * Check language condition (Pro).
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return bool|null
     */
    private function check_language(array $settings): ?bool
    {
        if (empty($settings['cd_cond_lang_enable']) || 'yes' !== $settings['cd_cond_lang_enable']) {
            return null;
        }

        if (!$this->can_use_pro()) {
            return true;
        }

        if (empty($settings['cd_cond_lang_codes']) || !is_array($settings['cd_cond_lang_codes'])) {
            return null;
        }

        $current_lang = $this->get_current_language();

        foreach ($settings['cd_cond_lang_codes'] as $lang) {
            if (strpos($current_lang, $lang) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current site language.
     *
     * Supports WPML, Polylang, and falls back to WordPress locale.
     *
     * @return string Language code.
     */
    private function get_current_language(): string
    {
        // WPML support.
        if (defined('ICL_LANGUAGE_CODE') && ICL_LANGUAGE_CODE) {
            return ICL_LANGUAGE_CODE;
        }

        // Polylang support.
        if (function_exists('pll_current_language')) {
            return pll_current_language('slug') ?: get_locale();
        }

        // WordPress locale fallback.
        $locale = get_locale();
        return substr($locale, 0, 2); // Return first 2 chars (e.g., 'en' from 'en_US').
    }

    /**
     * Render Elementor template.
     *
     * @param int $template_id Template ID.
     *
     * @return void
     */
    private function render_template(int $template_id): void
    {
        if (!did_action('elementor/loaded')) {
            return;
        }

        echo '<div class="king-addons-cd-fallback-template">';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo Plugin::$instance->frontend->get_builder_content_for_display($template_id);
        echo '</div>';
    }

    /**
     * Suppress element rendering.
     *
     * @param Element_Base $element Elementor element.
     *
     * @return void
     */
    private function suppress_element_render(Element_Base $element): void
    {
        // Add hidden class for CSS hiding as fallback.
        $element->add_render_attribute('_wrapper', 'class', 'king-addons-cd-hidden');
        $element->add_render_attribute('_wrapper', 'style', 'display:none !important;');
    }

    /**
     * Check if in editor or preview mode.
     *
     * @return bool
     */
    private function is_editor_or_preview(): bool
    {
        if (!did_action('elementor/loaded')) {
            return false;
        }

        return Plugin::$instance->editor->is_edit_mode() || Plugin::$instance->preview->is_preview_mode();
    }

    /**
     * Sanitize comma-separated IDs.
     *
     * @param string $raw Raw string.
     *
     * @return array<int>
     */
    private function sanitize_ids(string $raw): array
    {
        return array_values(array_filter(array_map('absint', array_map('trim', explode(',', $raw)))));
    }

    /**
     * Check Pro license availability.
     *
     * @return bool
     */
    private function can_use_pro(): bool
    {
        if (!function_exists('king_addons_freemius')) {
            return false;
        }

        return king_addons_freemius()->can_use_premium_code__premium_only();
    }
}







