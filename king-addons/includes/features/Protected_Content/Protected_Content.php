<?php
/**
 * Protected Content feature.
 *
 * Adds protection controls and runtime logic for Elementor elements.
 * Supports role-based access, password protection, conditions, and content locker.
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
 * Protected Content class.
 */
class Protected_Content
{
    /**
     * Templates cache.
     *
     * @var array<int,string>|null
     */
    private static ?array $templates_cache = null;

    /**
     * Roles cache.
     *
     * @var array<string,string>|null
     */
    private static ?array $roles_cache = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Styles and scripts.
        add_action('elementor/preview/enqueue_styles', [$this, 'enqueue_styles'], 1);
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'enqueue_styles'], 1);
        add_action('elementor/preview/enqueue_scripts', [$this, 'enqueue_preview_script'], 1);

        // Controls for widgets / columns / sections / containers.
        add_action('elementor/element/common/_section_style/after_section_end', [$this, 'register_controls'], 10, 2);
        add_action('elementor/element/section/section_advanced/after_section_end', [$this, 'register_controls'], 10, 2);
        add_action('elementor/element/container/section_layout/after_section_end', [$this, 'register_controls'], 10, 2);
        add_action('elementor/element/column/section_advanced/after_section_end', [$this, 'register_controls'], 10, 2);

        // Runtime check.
        add_action('elementor/frontend/widget/before_render', [$this, 'before_render_element']);
        add_action('elementor/frontend/section/before_render', [$this, 'before_render_element']);
        add_action('elementor/frontend/container/before_render', [$this, 'before_render_element']);
        add_action('elementor/frontend/column/before_render', [$this, 'before_render_element']);

        // Locker helper.
        add_action('king_addons_locker_unlock', [$this, 'set_locker_cookie']);

        // Password AJAX handler.
        add_action('wp_ajax_king_addons_verify_password', [$this, 'ajax_verify_password']);
        add_action('wp_ajax_nopriv_king_addons_verify_password', [$this, 'ajax_verify_password']);

        // Enqueue password form script.
        add_action('wp_enqueue_scripts', [$this, 'enqueue_password_form_script']);
    }

    /**
     * Enqueue styles.
     *
     * @return void
     */
    public function enqueue_styles(): void
    {
        wp_enqueue_style(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-protected-content-style',
            KING_ADDONS_URL . 'includes/features/Protected_Content/style.css',
            [],
            KING_ADDONS_VERSION
        );
    }

    /**
     * Enqueue preview script in editor.
     *
     * @return void
     */
    public function enqueue_preview_script(): void
    {
        wp_enqueue_script(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-protected-content-preview-handler',
            KING_ADDONS_URL . 'includes/features/Protected_Content/preview-handler.js',
            ['jquery'],
            KING_ADDONS_VERSION,
            true
        );
    }

    /**
     * Enqueue password form script on frontend.
     *
     * @return void
     */
    public function enqueue_password_form_script(): void
    {
        if ($this->is_editor_or_preview()) {
            return;
        }

        wp_enqueue_script(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-protected-content-password',
            KING_ADDONS_URL . 'includes/features/Protected_Content/password-form.js',
            ['jquery'],
            KING_ADDONS_VERSION,
            true
        );

        wp_localize_script(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-protected-content-password',
            'kingAddonsProtectedContent',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('king_addons_protected_content'),
                'strings' => [
                    'enterPassword' => esc_html__('Please enter a password', 'king-addons'),
                    'incorrectPassword' => esc_html__('Incorrect password', 'king-addons'),
                    'errorOccurred' => esc_html__('An error occurred. Please try again.', 'king-addons'),
                ],
            ]
        );
    }

    /**
     * Register protection controls on supported elements.
     *
     * @param Element_Base $element Element.
     * @param array<mixed> $args    Args.
     *
     * @return void
     */
    public function register_controls(Element_Base $element, $args): void
    {
        $element->start_controls_section(
            'king_addons_protected_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Protection', 'king-addons'),
                'tab' => Controls_Manager::TAB_ADVANCED,
            ]
        );

        // =============================================
        // MAIN SETTINGS
        // =============================================

        $element->add_control(
            'protected_content_enable',
            [
                'label' => esc_html__('Enable Protection', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $element->add_control(
            'protected_content_mode',
            [
                'label' => esc_html__('Protection Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'role' => esc_html__('By Role / Login', 'king-addons'),
                    'password' => $this->get_pro_label(__('By Password', 'king-addons')),
                    'conditions' => esc_html__('By Conditions', 'king-addons'),
                    'locker' => $this->get_pro_label(__('Content Locker', 'king-addons')),
                ],
                'default' => 'role',
                'condition' => [
                    'protected_content_enable' => 'yes',
                ],
            ]
        );

        // =============================================
        // FALLBACK SETTINGS
        // =============================================

        $element->add_control(
            'protected_content_fallback_heading',
            [
                'label' => esc_html__('Fallback Options', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'protected_content_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'protected_content_fallback_type',
            [
                'label' => esc_html__('If access denied', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'none' => esc_html__('Hide element', 'king-addons'),
                    'message' => esc_html__('Show message', 'king-addons'),
                    'template' => $this->get_pro_label(__('Show template', 'king-addons')),
                    'form' => $this->get_pro_label(__('Show form', 'king-addons')),
                ],
                'default' => 'none',
                'condition' => [
                    'protected_content_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'protected_content_fallback_msg',
            [
                'label' => esc_html__('Message', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 3,
                'default' => esc_html__('This content is protected.', 'king-addons'),
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_fallback_type' => 'message',
                ],
            ]
        );

        $element->add_control(
            'protected_content_fallback_template',
            [
                'label' => esc_html__('Template', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'options' => $this->get_elementor_templates(),
                'label_block' => true,
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_fallback_type' => 'template',
                ],
            ]
        );

        $element->add_control(
            'protected_content_fallback_form',
            [
                'label' => esc_html__('Form Shortcode', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => '[contact-form-7 id="123"]',
                'description' => esc_html__('Enter a form shortcode (CF7, WPForms, etc.)', 'king-addons'),
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_fallback_type' => 'form',
                ],
            ]
        );

        $element->add_control(
            'protected_content_invert',
            [
                'label' => esc_html__('Invert Logic', 'king-addons'),
                'description' => esc_html__('Show content only when condition is NOT met', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'protected_content_enable' => 'yes',
                ],
            ]
        );

        // =============================================
        // ROLE MODE (Free)
        // =============================================

        $element->add_control(
            'protected_content_role_heading',
            [
                'label' => esc_html__('Role / Login Settings', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'role',
                ],
            ]
        );

        $element->add_control(
            'protected_content_require_login',
            [
                'label' => esc_html__('Require Logged In User', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'role',
                ],
            ]
        );

        $element->add_control(
            'protected_content_roles',
            [
                'label' => esc_html__('Allowed Roles', 'king-addons'),
                'description' => esc_html__('Leave empty to allow all logged-in users', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_wp_roles_for_control(),
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'role',
                ],
            ]
        );

        // =============================================
        // PASSWORD MODE (Pro)
        // =============================================

        $element->add_control(
            'protected_content_password_heading',
            [
                'label' => $this->get_pro_label(__('Password Settings', 'king-addons')),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'password',
                ],
            ]
        );

        $element->add_control(
            'protected_content_password_type',
            [
                'label' => esc_html__('Password Scope', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'element' => esc_html__('Per Element', 'king-addons'),
                    'global' => esc_html__('Global Key', 'king-addons'),
                ],
                'default' => 'element',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'password',
                ],
            ]
        );

        $element->add_control(
            'protected_content_password',
            [
                'label' => esc_html__('Password', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'input_type' => 'password',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'password',
                    'protected_content_password_type' => 'element',
                ],
            ]
        );

        $element->add_control(
            'protected_content_password_key',
            [
                'label' => esc_html__('Global Key', 'king-addons'),
                'description' => esc_html__('Shared key for multiple elements', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'password',
                    'protected_content_password_type' => 'global',
                ],
            ]
        );

        $element->add_control(
            'protected_content_password_global_password',
            [
                'label' => esc_html__('Global Password', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'input_type' => 'password',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'password',
                    'protected_content_password_type' => 'global',
                ],
            ]
        );

        $element->add_control(
            'protected_content_password_cookie_days',
            [
                'label' => esc_html__('Remember for (days)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 7,
                'min' => 1,
                'max' => 365,
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'password',
                ],
            ]
        );

        // =============================================
        // CONDITIONS MODE
        // =============================================

        $element->add_control(
            'protected_content_conditions_heading',
            [
                'label' => esc_html__('Conditions Settings', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'conditions',
                ],
            ]
        );

        // Login Status (Free)
        $element->add_control(
            'protected_content_cond_login',
            [
                'label' => esc_html__('Login Status', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'any' => esc_html__('Any', 'king-addons'),
                    'logged_in' => esc_html__('Logged In', 'king-addons'),
                    'logged_out' => esc_html__('Logged Out', 'king-addons'),
                ],
                'default' => 'any',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'conditions',
                ],
            ]
        );

        // Device (Free)
        $element->add_control(
            'protected_content_cond_device',
            [
                'label' => esc_html__('Allowed Devices', 'king-addons'),
                'description' => esc_html__('Leave empty to allow all devices', 'king-addons'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => [
                    'desktop' => esc_html__('Desktop', 'king-addons'),
                    'tablet' => esc_html__('Tablet', 'king-addons'),
                    'mobile' => esc_html__('Mobile', 'king-addons'),
                ],
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'conditions',
                ],
            ]
        );

        // Browser (Pro)
        $element->add_control(
            'protected_content_cond_browser',
            [
                'label' => $this->get_pro_label(__('Browser', 'king-addons')),
                'description' => esc_html__('Leave empty to allow all browsers', 'king-addons'),
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
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'conditions',
                ],
            ]
        );

        // Date Range (Pro)
        $element->add_control(
            'protected_content_date_heading',
            [
                'label' => $this->get_pro_label(__('Date Range', 'king-addons')),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'conditions',
                ],
            ]
        );

        $element->add_control(
            'protected_content_cond_date_from',
            [
                'label' => esc_html__('From Date', 'king-addons'),
                'type' => Controls_Manager::DATE_TIME,
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'conditions',
                ],
            ]
        );

        $element->add_control(
            'protected_content_cond_date_to',
            [
                'label' => esc_html__('To Date', 'king-addons'),
                'type' => Controls_Manager::DATE_TIME,
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'conditions',
                ],
            ]
        );

        // URL Parameters (Pro)
        $element->add_control(
            'protected_content_url_heading',
            [
                'label' => $this->get_pro_label(__('URL Parameters', 'king-addons')),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'conditions',
                ],
            ]
        );

        $element->add_control(
            'protected_content_cond_url_param',
            [
                'label' => esc_html__('URL Parameter Name', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'ref',
                'description' => esc_html__('e.g., "ref" for ?ref=value', 'king-addons'),
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'conditions',
                ],
            ]
        );

        $element->add_control(
            'protected_content_cond_url_value',
            [
                'label' => esc_html__('URL Parameter Value', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'campaign',
                'description' => esc_html__('Leave empty to check only parameter existence', 'king-addons'),
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'conditions',
                ],
            ]
        );

        // WooCommerce Conditions (Pro)
        $element->add_control(
            'protected_content_woo_heading',
            [
                'label' => $this->get_pro_label(__('WooCommerce Conditions', 'king-addons')),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'conditions',
                ],
            ]
        );

        $element->add_control(
            'protected_content_cond_woo_in_cart_product_ids',
            [
                'label' => esc_html__('Products in Cart', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => '123, 456, 789',
                'description' => esc_html__('Comma-separated product IDs. Show if ANY is in cart.', 'king-addons'),
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'conditions',
                ],
            ]
        );

        $element->add_control(
            'protected_content_cond_woo_min_cart_total',
            [
                'label' => esc_html__('Minimum Cart Total', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'placeholder' => '50.00',
                'description' => esc_html__('Show content if cart total is at least this amount', 'king-addons'),
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'conditions',
                ],
            ]
        );

        $element->add_control(
            'protected_content_cond_woo_customer_bought_ids',
            [
                'label' => esc_html__('Customer Bought Products', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => '123, 456',
                'description' => esc_html__('Comma-separated product IDs. Show if user purchased ALL.', 'king-addons'),
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'conditions',
                ],
            ]
        );

        // =============================================
        // LOCKER MODE (Pro)
        // =============================================

        $element->add_control(
            'protected_content_locker_heading',
            [
                'label' => $this->get_pro_label(__('Content Locker Settings', 'king-addons')),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'locker',
                ],
            ]
        );

        $element->add_control(
            'protected_content_locker_method',
            [
                'label' => esc_html__('Unlock Method', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'form_submit' => esc_html__('After Form Submit', 'king-addons'),
                    'manual_token' => esc_html__('By Token', 'king-addons'),
                ],
                'default' => 'form_submit',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'locker',
                ],
            ]
        );

        $element->add_control(
            'protected_content_locker_token',
            [
                'label' => esc_html__('Locker Token', 'king-addons'),
                'description' => esc_html__('Unique identifier for this locked content', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'newsletter-unlock',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'locker',
                ],
            ]
        );

        $element->add_control(
            'protected_content_locker_form',
            [
                'label' => esc_html__('Form Shortcode', 'king-addons'),
                'description' => esc_html__('Form to display for unlocking content', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => '[contact-form-7 id="123"]',
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'locker',
                    'protected_content_locker_method' => 'form_submit',
                ],
            ]
        );

        $element->add_control(
            'protected_content_locker_cookie_days',
            [
                'label' => esc_html__('Remember for (days)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 7,
                'min' => 1,
                'max' => 365,
                'condition' => [
                    'protected_content_enable' => 'yes',
                    'protected_content_mode' => 'locker',
                ],
            ]
        );

        $element->end_controls_section();
    }

    /**
     * Before render hook.
     *
     * @param Element_Base $element Element.
     *
     * @return void
     */
    public function before_render_element(Element_Base $element): void
    {
        $settings = $element->get_settings_for_display();

        if (empty($settings['protected_content_enable']) || 'yes' !== $settings['protected_content_enable']) {
            return;
        }

        // In editor or preview: show content, mark protected.
        if ($this->is_editor_or_preview()) {
            $element->add_render_attribute('_wrapper', 'data-king-protected', 'yes');
            $element->add_render_attribute('_wrapper', 'class', 'king-addons-protected-preview');
            
            // Add protection type info for editor badge.
            $protection_info = $this->get_protection_info($settings);
            $element->add_render_attribute('_wrapper', 'data-king-protected-type', esc_attr($protection_info));
            return;
        }

        $has_access = $this->evaluate_access($settings, $element);

        if (!empty($settings['protected_content_invert']) && 'yes' === $settings['protected_content_invert']) {
            $has_access = !$has_access;
        }

        if ($has_access) {
            return;
        }

        // Special handling for password mode - show password form.
        $mode = $settings['protected_content_mode'] ?? 'role';
        if ('password' === $mode && $this->can_use_pro()) {
            $this->render_password_form($settings, $element);
            $this->suppress_element_render($element);
            return;
        }

        $fallback = $settings['protected_content_fallback_type'] ?? 'none';

        switch ($fallback) {
            case 'message':
                $this->render_fallback_message($settings);
                $this->suppress_element_render($element);
                return;

            case 'template':
                if ($this->can_use_pro() && !empty($settings['protected_content_fallback_template'])) {
                    $this->render_fallback_template((int) $settings['protected_content_fallback_template']);
                    $this->suppress_element_render($element);
                    return;
                }
                $this->render_fallback_message($settings);
                $this->suppress_element_render($element);
                return;

            case 'form':
                if ($this->can_use_pro() && !empty($settings['protected_content_fallback_form'])) {
                    echo '<div class="king-addons-protected-fallback king-addons-protected-form-fallback">';
                    echo do_shortcode($settings['protected_content_fallback_form']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo '</div>';
                    $this->suppress_element_render($element);
                    return;
                }
                $this->render_fallback_message($settings);
                $this->suppress_element_render($element);
                return;

            case 'none':
            default:
                $this->suppress_element_render($element);
                return;
        }
    }

    /**
     * Evaluate access.
     *
     * @param array<string,mixed> $settings Settings.
     * @param Element_Base        $element  Element.
     *
     * @return bool
     */
    private function evaluate_access(array $settings, Element_Base $element): bool
    {
        $mode = $settings['protected_content_mode'] ?? 'role';

        switch ($mode) {
            case 'role':
                return $this->check_role_access($settings);

            case 'password':
                return $this->check_password_access($settings, $element);

            case 'conditions':
                return $this->check_conditions_access($settings);

            case 'locker':
                return $this->check_locker_access($settings);

            default:
                return true;
        }
    }

    /**
     * Role-based access.
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return bool
     */
    private function check_role_access(array $settings): bool
    {
        $require_login = !empty($settings['protected_content_require_login']) && 'yes' === $settings['protected_content_require_login'];

        if (!is_user_logged_in()) {
            return !$require_login;
        }

        // User is logged in.
        if (empty($settings['protected_content_roles']) || !is_array($settings['protected_content_roles'])) {
            // No specific roles required - all logged in users have access.
            return true;
        }

        $user = wp_get_current_user();
        foreach ($user->roles as $role) {
            if (in_array($role, $settings['protected_content_roles'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Password access.
     *
     * @param array<string,mixed> $settings Settings.
     * @param Element_Base        $element  Element.
     *
     * @return bool
     */
    private function check_password_access(array $settings, Element_Base $element): bool
    {
        if (!$this->can_use_pro()) {
            return true;
        }

        $scope = $settings['protected_content_password_type'] ?? 'element';

        if ('element' === $scope) {
            $cookie_name = 'king_protect_' . $element->get_id();
            return !empty($_COOKIE[$cookie_name]) && '1' === $_COOKIE[$cookie_name];
        }

        // Global scope.
        $key = $settings['protected_content_password_key'] ?? '';
        if (empty($key)) {
            return false;
        }

        $cookie_name = 'king_protect_global_' . sanitize_key($key);
        return !empty($_COOKIE[$cookie_name]) && '1' === $_COOKIE[$cookie_name];
    }

    /**
     * Conditions access.
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return bool
     */
    private function check_conditions_access(array $settings): bool
    {
        // Login condition (Free).
        $login_cond = $settings['protected_content_cond_login'] ?? 'any';
        if ('logged_in' === $login_cond && !is_user_logged_in()) {
            return false;
        }
        if ('logged_out' === $login_cond && is_user_logged_in()) {
            return false;
        }

        // Device condition (Free).
        if (!empty($settings['protected_content_cond_device']) && is_array($settings['protected_content_cond_device'])) {
            $device = $this->detect_device();
            if (!in_array($device, $settings['protected_content_cond_device'], true)) {
                return false;
            }
        }

        // Pro conditions.
        if ($this->can_use_pro()) {
            // Browser condition.
            if (!empty($settings['protected_content_cond_browser']) && is_array($settings['protected_content_cond_browser'])) {
                $browser = $this->detect_browser();
                if (!in_array($browser, $settings['protected_content_cond_browser'], true)) {
                    return false;
                }
            }

            // Date range.
            $now = wp_date('U'); // Use WordPress timezone.
            if (!empty($settings['protected_content_cond_date_from'])) {
                $from = strtotime((string) $settings['protected_content_cond_date_from']);
                if ($from && $now < $from) {
                    return false;
                }
            }
            if (!empty($settings['protected_content_cond_date_to'])) {
                $to = strtotime((string) $settings['protected_content_cond_date_to']);
                if ($to && $now > $to) {
                    return false;
                }
            }

            // URL parameter.
            if (!empty($settings['protected_content_cond_url_param'])) {
                $param = sanitize_text_field($settings['protected_content_cond_url_param']);
                $value = $settings['protected_content_cond_url_value'] ?? '';

                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                if (!isset($_GET[$param])) {
                    return false;
                }

                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                if ('' !== $value && sanitize_text_field($_GET[$param]) !== $value) {
                    return false;
                }
            }

            // WooCommerce conditions.
            if (class_exists('WooCommerce')) {
                // Products in cart.
                if (!empty($settings['protected_content_cond_woo_in_cart_product_ids'])) {
                    $ids = $this->sanitize_ids($settings['protected_content_cond_woo_in_cart_product_ids']);
                    if (!empty($ids) && !$this->woo_cart_has_products($ids)) {
                        return false;
                    }
                }

                // Minimum cart total.
                if (!empty($settings['protected_content_cond_woo_min_cart_total'])) {
                    $min_total = (float) $settings['protected_content_cond_woo_min_cart_total'];
                    $total = (float) (WC()->cart ? WC()->cart->get_total('edit') : 0);
                    if ($total < $min_total) {
                        return false;
                    }
                }

                // Customer bought products.
                if (!empty($settings['protected_content_cond_woo_customer_bought_ids']) && is_user_logged_in()) {
                    $ids = $this->sanitize_ids($settings['protected_content_cond_woo_customer_bought_ids']);
                    if (!empty($ids)) {
                        $user = wp_get_current_user();
                        foreach ($ids as $pid) {
                            if (!wc_customer_bought_product($user->user_email, $user->ID, $pid)) {
                                return false;
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Locker access.
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return bool
     */
    private function check_locker_access(array $settings): bool
    {
        if (!$this->can_use_pro()) {
            return true;
        }

        $token = $settings['protected_content_locker_token'] ?? '';
        if (empty($token)) {
            return false;
        }

        $cookie_name = 'king_locker_' . sanitize_key($token);
        return !empty($_COOKIE[$cookie_name]) && '1' === $_COOKIE[$cookie_name];
    }

    /**
     * Set locker cookie after unlock event.
     *
     * @param string $token Token.
     * @param int    $days  Days to remember (default 7).
     *
     * @return void
     */
    public function set_locker_cookie(string $token, int $days = 7): void
    {
        $cookie_name = 'king_locker_' . sanitize_key($token);
        $expiry = time() + ($days * DAY_IN_SECONDS);
        setcookie($cookie_name, '1', $expiry, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }

    /**
     * AJAX handler for password verification.
     *
     * @return void
     */
    public function ajax_verify_password(): void
    {
        check_ajax_referer('king_addons_protected_content', 'nonce');

        if (!$this->can_use_pro()) {
            wp_send_json_error(['message' => esc_html__('Pro feature', 'king-addons')]);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $password = isset($_POST['password']) ? sanitize_text_field(wp_unslash($_POST['password'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $element_id = isset($_POST['element_id']) ? sanitize_text_field(wp_unslash($_POST['element_id'])) : '';
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $scope = isset($_POST['scope']) ? sanitize_text_field(wp_unslash($_POST['scope'])) : 'element';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $global_key = isset($_POST['global_key']) ? sanitize_text_field(wp_unslash($_POST['global_key'])) : '';
        $days = isset($_POST['days']) ? absint($_POST['days']) : 7;

        $days = max(1, min(365, $days));

        if (empty($password) || empty($post_id) || empty($element_id)) {
            wp_send_json_error(['message' => esc_html__('Invalid request', 'king-addons')]);
        }

        if (!in_array($scope, ['element', 'global'], true)) {
            wp_send_json_error(['message' => esc_html__('Invalid request', 'king-addons')]);
        }

        // Get element settings from post meta.
        $elementor_data = get_post_meta($post_id, '_elementor_data', true);
        if (empty($elementor_data)) {
            wp_send_json_error(['message' => esc_html__('Element not found', 'king-addons')]);
        }

        if (is_array($elementor_data)) {
            $data = $elementor_data;
        } else {
            $data = json_decode((string) $elementor_data, true);
        }
        if (!is_array($data)) {
            wp_send_json_error(['message' => esc_html__('Invalid data', 'king-addons')]);
        }

        // Find element settings.
        $settings = $this->find_element_settings($data, $element_id);
        if (null === $settings) {
            wp_send_json_error(['message' => esc_html__('Element not found', 'king-addons')]);
        }

        // Validate password mode configuration.
        $mode = $settings['protected_content_mode'] ?? '';
        $enabled = $settings['protected_content_enable'] ?? '';
        if ('yes' !== $enabled || 'password' !== $mode) {
            wp_send_json_error(['message' => esc_html__('Configuration error', 'king-addons')]);
        }

        $stored_scope = $settings['protected_content_password_type'] ?? 'element';
        if (!in_array($stored_scope, ['element', 'global'], true)) {
            $stored_scope = 'element';
        }

        if ($stored_scope !== $scope) {
            wp_send_json_error(['message' => esc_html__('Configuration error', 'king-addons')]);
        }

        if ('global' === $scope) {
            $stored_key = isset($settings['protected_content_password_key']) ? sanitize_text_field((string) $settings['protected_content_password_key']) : '';
            if (empty($stored_key) || empty($global_key) || sanitize_key($stored_key) !== sanitize_key($global_key)) {
                wp_send_json_error(['message' => esc_html__('Configuration error', 'king-addons')]);
            }
            $stored_password = $settings['protected_content_password_global_password'] ?? null;
        } else {
            $stored_password = $settings['protected_content_password'] ?? null;
        }

        if (empty($stored_password) || !is_string($stored_password)) {
            wp_send_json_error(['message' => esc_html__('Configuration error', 'king-addons')]);
        }

        if (!hash_equals((string) $stored_password, (string) $password)) {
            wp_send_json_error(['message' => esc_html__('Incorrect password', 'king-addons')]);
        }

        // Set cookie.
        $expiry = time() + ($days * DAY_IN_SECONDS);
        if ('global' === $scope && !empty($global_key)) {
            $cookie_name = 'king_protect_global_' . sanitize_key($global_key);
        } else {
            $cookie_name = 'king_protect_' . $element_id;
        }

        setcookie($cookie_name, '1', $expiry, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

        wp_send_json_success(['message' => esc_html__('Access granted', 'king-addons')]);
    }

    /**
     * Find element settings in Elementor data.
     *
     * @param array<mixed> $data       Elementor data.
     * @param string       $element_id Element ID.
     *
     * @return array<string,mixed>|null
     */
    private function find_element_settings(array $data, string $element_id): ?array
    {
        foreach ($data as $element) {
            if (!is_array($element)) {
                continue;
            }

            $id = $element['id'] ?? '';
            $settings = $element['settings'] ?? [];

            if ($id === $element_id) {
                return is_array($settings) ? $settings : [];
            }

            // Recursively search in children.
            if (!empty($element['elements']) && is_array($element['elements'])) {
                $found = $this->find_element_settings($element['elements'], $element_id);
                if (null !== $found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Render password form.
     *
     * @param array<string,mixed> $settings Settings.
     * @param Element_Base        $element  Element.
     *
     * @return void
     */
    private function render_password_form(array $settings, Element_Base $element): void
    {
        $scope = $settings['protected_content_password_type'] ?? 'element';
        $global_key = $settings['protected_content_password_key'] ?? '';
        $days = (int) ($settings['protected_content_password_cookie_days'] ?? 7);
        $message = $settings['protected_content_fallback_msg'] ?? esc_html__('This content is password protected.', 'king-addons');

        $document_post_id = $this->get_current_elementor_document_id();
        if ($document_post_id <= 0) {
            $document_post_id = (int) get_queried_object_id();
        }

        $days = max(1, min(365, $days));

        ?>
        <div class="king-addons-protected-fallback king-addons-protected-password-form">
            <div class="king-addons-protected-password-message"><?php echo esc_html($message); ?></div>
            <form class="king-addons-password-form" 
                  data-element-id="<?php echo esc_attr($element->get_id()); ?>"
                data-post-id="<?php echo esc_attr($document_post_id); ?>"
                  data-scope="<?php echo esc_attr($scope); ?>"
                  data-global-key="<?php echo esc_attr($global_key); ?>"
                  data-days="<?php echo esc_attr($days); ?>">
                <div class="king-addons-password-input-wrap">
                    <input type="password" 
                           class="king-addons-password-input" 
                           placeholder="<?php esc_attr_e('Enter password', 'king-addons'); ?>" 
                           required />
                    <button type="submit" class="king-addons-password-submit">
                        <span class="king-addons-password-submit-text"><?php esc_html_e('Unlock', 'king-addons'); ?></span>
                        <span class="king-addons-password-submit-loading" style="display:none;">⏳</span>
                    </button>
                </div>
                <div class="king-addons-password-error" style="display:none;"></div>
            </form>
        </div>
        <?php
    }

    /**
     * Render fallback message.
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return void
     */
    private function render_fallback_message(array $settings): void
    {
        $msg = $settings['protected_content_fallback_msg'] ?? esc_html__('This content is protected.', 'king-addons');
        echo '<div class="king-addons-protected-fallback">' . esc_html($msg) . '</div>';
    }

    /**
     * Render Elementor template.
     *
     * @param int $template_id Template ID.
     *
     * @return void
     */
    private function render_fallback_template(int $template_id): void
    {
        $frontend = Plugin::$instance->frontend;
        echo '<div class="king-addons-protected-fallback king-addons-protected-template-fallback">';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $frontend->get_builder_content_for_display($template_id);
        echo '</div>';
    }

    /**
     * Suppress element rendering.
     *
     * @param Element_Base $element Element.
     *
     * @return void
     */
    private function suppress_element_render(Element_Base $element): void
    {
        // Try modern Elementor method first.
        if (method_exists($element, 'set_should_render')) {
            $element->set_should_render(false);
            return;
        }

        // Fallback: hide with CSS.
        $element->add_render_attribute('_wrapper', 'class', 'king-addons-protected-hidden');
    }

    /**
     * Check if in editor or preview mode.
     *
     * @return bool
     */
    private function is_editor_or_preview(): bool
    {
        if (!class_exists('\Elementor\Plugin')) {
            return false;
        }

        $editor = Plugin::$instance->editor;
        $preview = Plugin::$instance->preview;

        if ($editor && $editor->is_edit_mode()) {
            return true;
        }

        if ($preview && $preview->is_preview_mode()) {
            return true;
        }

        return false;
    }

    /**
     * Get current Elementor document post ID (important for Theme Builder templates).
     *
     * @return int
     */
    private function get_current_elementor_document_id(): int
    {
        if (!class_exists('\Elementor\Plugin') || !Plugin::$instance || !isset(Plugin::$instance->documents)) {
            return 0;
        }

        $document = Plugin::$instance->documents->get_current();
        if ($document && method_exists($document, 'get_main_id')) {
            return (int) $document->get_main_id();
        }

        return 0;
    }

    /**
     * Get protection info for editor badge.
     *
     * @param array<string,mixed> $settings Settings.
     *
     * @return string
     */
    private function get_protection_info(array $settings): string
    {
        $mode = $settings['protected_content_mode'] ?? 'role';
        $info = [];

        switch ($mode) {
            case 'role':
                $info[] = 'Role/Login';
                if (!empty($settings['protected_content_roles'])) {
                    $info[] = implode(', ', (array) $settings['protected_content_roles']);
                }
                break;

            case 'password':
                $scope = $settings['protected_content_password_type'] ?? 'element';
                $info[] = 'Password (' . $scope . ')';
                break;

            case 'conditions':
                $info[] = 'Conditions';
                if (!empty($settings['protected_content_cond_login']) && 'any' !== $settings['protected_content_cond_login']) {
                    $info[] = $settings['protected_content_cond_login'];
                }
                if (!empty($settings['protected_content_cond_device'])) {
                    $info[] = 'Device: ' . implode(', ', (array) $settings['protected_content_cond_device']);
                }
                break;

            case 'locker':
                $info[] = 'Content Locker';
                if (!empty($settings['protected_content_locker_token'])) {
                    $info[] = 'Token: ' . $settings['protected_content_locker_token'];
                }
                break;
        }

        return implode(' | ', $info);
    }

    /**
     * Detect device type.
     *
     * @return string desktop|tablet|mobile
     */
    private function detect_device(): string
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return 'desktop';
        }

        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

        // Tablet detection (before mobile since tablets often have "mobile" in UA).
        $tablets = ['ipad', 'tablet', 'kindle', 'silk', 'playbook'];
        foreach ($tablets as $tablet) {
            if (strpos($ua, $tablet) !== false) {
                return 'tablet';
            }
        }

        // Android tablet (no "mobile" in UA).
        if (strpos($ua, 'android') !== false && strpos($ua, 'mobile') === false) {
            return 'tablet';
        }

        // Mobile detection.
        if (wp_is_mobile()) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Detect browser.
     *
     * @return string
     */
    private function detect_browser(): string
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return 'unknown';
        }

        $ua = $_SERVER['HTTP_USER_AGENT'];

        // Order matters: Edge contains "Chrome", Chrome contains "Safari".
        if (preg_match('/Edg/i', $ua)) {
            return 'edge';
        }
        if (preg_match('/OPR|Opera/i', $ua)) {
            return 'opera';
        }
        if (preg_match('/Chrome/i', $ua)) {
            return 'chrome';
        }
        if (preg_match('/Safari/i', $ua)) {
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
     * Get WP roles as options with caching.
     *
     * @return array<string,string>
     */
    private function get_wp_roles_for_control(): array
    {
        if (null !== self::$roles_cache) {
            return self::$roles_cache;
        }

        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = wp_roles();
        }

        self::$roles_cache = [];
        foreach ($wp_roles->roles as $key => $role) {
            self::$roles_cache[$key] = $role['name'];
        }

        return self::$roles_cache;
    }

    /**
     * Get Elementor templates with caching.
     *
     * @return array<int,string>
     */
    private function get_elementor_templates(): array
    {
        if (null !== self::$templates_cache) {
            return self::$templates_cache;
        }

        self::$templates_cache = [];

        $templates = get_posts([
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        foreach ($templates as $template) {
            self::$templates_cache[$template->ID] = $template->post_title;
        }

        return self::$templates_cache;
    }

    /**
     * Check Woo cart for products.
     *
     * @param array<int> $ids Product IDs.
     *
     * @return bool
     */
    private function woo_cart_has_products(array $ids): bool
    {
        if (!function_exists('WC') || !WC()->cart) {
            return false;
        }

        foreach (WC()->cart->get_cart() as $cart_item) {
            $pid = (int) ($cart_item['product_id'] ?? 0);
            $vid = (int) ($cart_item['variation_id'] ?? 0);

            if (in_array($pid, $ids, true) || ($vid > 0 && in_array($vid, $ids, true))) {
                return true;
            }
        }

        return false;
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
        $ids = array_filter(array_map('absint', array_map('trim', explode(',', $raw))));
        return array_values($ids);
    }

    /**
     * Is pro available.
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

    /**
     * Get label with Pro icon.
     *
     * @param string $label Label text.
     *
     * @return string
     */
    private function get_pro_label(string $label): string
    {
        if ($this->can_use_pro()) {
            return $label;
        }

        return sprintf('%s <i class="eicon-pro-icon"></i>', $label);
    }
}







