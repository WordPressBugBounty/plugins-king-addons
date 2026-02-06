<?php

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

final class RegisterAssets
{
    private static ?RegisterAssets $_instance = null;

    public static function instance(): RegisterAssets
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        // Register styles and scripts for Elementor widgets and features
        self::registerElementorStyles();
        self::registerElementorScripts();

        // Register Pro assets if available
        self::registerProAssets();

        // Register general files
        self::registerLibrariesFiles();
    }

    /**
     * Register CSS files
     */
    function registerElementorStyles(): void
    {
        foreach (ModulesMap::getModulesMapArray()['widgets'] as $widget_id => $widget_array) {
            foreach ($widget_array['css'] as $css) {
                wp_register_style(KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . $widget_id . '-' . $css, KING_ADDONS_URL . 'includes/widgets/' . $widget_array['php-class'] . '/' . $css . '.css', null, KING_ADDONS_VERSION);
            }
        }
    }

    /**
     * Register JS files
     */
    function registerElementorScripts(): void
    {
        foreach (ModulesMap::getModulesMapArray()['widgets'] as $widget_id => $widget_array) {
            foreach ($widget_array['js'] as $js) {
                $script_handle = KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . $widget_id . '-' . $js;
                wp_register_script($script_handle, KING_ADDONS_URL . 'includes/widgets/' . $widget_array['php-class'] . '/' . $js . '.js', array('jquery'), KING_ADDONS_VERSION, true);
                
                // Localize script specifically for pricing-slider
                if ($widget_id === 'pricing-slider' && $js === 'script') {
                     $localized_data = [
                         'ajax_url' => admin_url('admin-ajax.php'),
                         'view_cart_text' => esc_html__('View Cart', 'king-addons'),
                         // Note: Nonce should ideally be generated closer to where the button is rendered,
                         // but passing ajax_url here is the main fix for the AJAX call.
                         // The nonce is already passed via data attribute on the button in Pricing_Slider_Pro.php.
                         // 'nonce' => wp_create_nonce('king_addons_add_to_cart_nonce') 
                     ];
                     wp_localize_script($script_handle, 'king_addons_slider_vars', $localized_data);
                }
                
                // Localize script for pricing-calculator
                if ($widget_id === 'pricing-calculator' && $js === 'script') {
                    $localized_data = [
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'add_to_cart_nonce' => wp_create_nonce('king_addons_add_to_cart_nonce'),
                        'send_email_quote_nonce' => wp_create_nonce('king_addons_send_email_quote_nonce'),
                        'view_cart_text' => esc_html__('View Cart', 'king-addons')
                    ];
                    wp_localize_script($script_handle, 'king_addons_calculator_vars', $localized_data);
                }

                // Localize script for login-register-form
                if ($widget_id === 'login-register-form' && $js === 'script') {
                    wp_localize_script($script_handle, 'king_addons_login_register_vars', [
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'login_nonce' => wp_create_nonce('king_addons_login_action'),
                        'register_nonce' => wp_create_nonce('king_addons_register_action'),
                        'lostpassword_nonce' => wp_create_nonce('king_addons_lostpassword_action'),
                        'social_login_nonce' => wp_create_nonce('king_addons_social_login_action'),
                        'strings' => [
                            'loading' => esc_html__('Please wait...', 'king-addons'),
                            'login_success' => esc_html__('Login successful! Redirecting...', 'king-addons'),
                            'register_success' => esc_html__('Registration successful! Welcome!', 'king-addons'),
                            'required_fields' => esc_html__('Please fill in all required fields.', 'king-addons'),
                            'invalid_email' => esc_html__('Please enter a valid email address.', 'king-addons'),
                            'password_mismatch' => esc_html__('Passwords do not match.', 'king-addons'),
                            'network_error' => esc_html__('Network error. Please try again.', 'king-addons'),
                        ]
                    ]);
                }
            }
        }

        wp_localize_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-search-script', 'KingAddonsSearchData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('king_addons_search_nonce'),
        ]);

        wp_localize_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-mailchimp-script', 'KingAddonsMailChimpData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('king_addons_mailchimp_nonce'),
        ]);

        if(KING_ADDONS_WGT_FORM_BUILDER) {
            wp_localize_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-form-builder-script', 'KingAddonsFormBuilderData', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                // Security fix: Remove public nonce exposure - generate dynamically in AJAX handlers
                'input_empty' => esc_html__('Please fill out this field', 'king-addons'),
                'select_empty' => esc_html__('Nothing selected', 'king-addons'),
                'file_empty' => esc_html__('Please upload a file', 'king-addons'),
                'recaptcha_v3_site_key' => get_option('king_addons_recaptcha_v3_site_key'),
                'recaptcha_error' => esc_html__('Recaptcha Error', 'king-addons'),
            ]);
        }

        // Localize form builder script - Security fix: Remove public nonce exposure
        if (KING_ADDONS_WGT_FORM_BUILDER) {
            wp_localize_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-form-builder-script', 'king_addons_form_builder_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                // Security fix: Remove public nonce exposure - generate dynamically in AJAX handlers
                'required_text' => esc_html__('This field is required', 'king-addons'),
                'email_text' => esc_html__('Enter a valid email', 'king-addons'),
                'select_empty' => esc_html__('Nothing selected', 'king-addons'),
                'file_empty' => esc_html__('Please upload a file', 'king-addons'),
                'recaptcha_v3_site_key' => get_option('king_addons_recaptcha_v3_site_key'),
                'recaptcha_error' => esc_html__('Recaptcha Error', 'king-addons'),
            ]);
        }

        foreach (ModulesMap::getModulesMapArray()['features'] as $feature_id => $feature_array) {
            foreach ($feature_array['js'] as $js) {
                wp_register_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . $feature_id . '-' . $js, KING_ADDONS_URL . 'includes/features/' . $feature_array['php-class'] . '/' . $js . '.js', null, KING_ADDONS_VERSION);
            }
        }
    }

    /**
     * Register libraries files
     */
    function registerLibrariesFiles(): void
    {
        foreach (LibrariesMap::getLibrariesMapArray()['libraries'] as $library_id => $library_array) {
            foreach ($library_array['css'] as $css) {
                wp_register_style(KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . $library_id . '-' . $css, KING_ADDONS_URL . 'includes/assets/libraries/' . $library_id . '/' . $css . '.css', null, KING_ADDONS_VERSION);
            }
            foreach ($library_array['js'] as $js) {
                wp_register_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . $library_id . '-' . $js, KING_ADDONS_URL . 'includes/assets/libraries/' . $library_id . '/' . $js . '.js', null, KING_ADDONS_VERSION);
            }
        }

        wp_localize_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-grid-grid', 'KingAddonsGridData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('king_addons_grid_nonce'),
            'viewCart' => esc_html__('View Cart', 'king-addons'),
            'addedToCartText' => esc_html__('was added to cart', 'king-addons'),
            'comparePageURL' => get_permalink(get_option('king_addons_compare_page')),
            'wishlistPageURL' => get_permalink(get_option('king_addons_wishlist_page')),
        ]);

    }
/**
     * Register Pro widget assets when premium is available
     */
    function registerProAssets(): void
    {
        // Only register Pro assets if Freemius premium is available
        if (
            !function_exists('king_addons_freemius') ||
            !king_addons_freemius()->can_use_premium_code__premium_only() ||
            !defined('KING_ADDONS_PRO_PATH') ||
            !defined('KING_ADDONS_PRO_URL')
        ) {
            return;
        }

        // In Elementor editor/preview we want instant reflection of changes,
        // even when WP_DEBUG/SCRIPT_DEBUG are off (browser cache is aggressive).
        $is_elementor_edit_or_preview = false;
        if (defined('ELEMENTOR_VERSION') && class_exists('\\Elementor\\Plugin')) {
            try {
                $elementor = \Elementor\Plugin::$instance;
                $is_elementor_edit_or_preview = (
                    (isset($elementor->editor) && method_exists($elementor->editor, 'is_edit_mode') && $elementor->editor->is_edit_mode())
                    || (isset($elementor->preview) && method_exists($elementor->preview, 'is_preview_mode') && $elementor->preview->is_preview_mode())
                );
            } catch (\Throwable $e) {
                $is_elementor_edit_or_preview = false;
            }
        }

        // Register Pro assets for widgets that have Pro versions
        foreach (ModulesMap::getModulesMapArray()['widgets'] as $widget_id => $widget_array) {
            if (empty($widget_array['has-pro'])) {
                continue;
            }

            $widget_class = $widget_array['php-class'];
            $pro_widget_dir = KING_ADDONS_PRO_PATH . 'includes/widgets/' . $widget_class . '_Pro/';

            // Register Pro CSS if exists
            $pro_css_path = $pro_widget_dir . 'style.css';
            if (file_exists($pro_css_path)) {
                $use_filemtime = $is_elementor_edit_or_preview || (defined('WP_DEBUG') && WP_DEBUG) || (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG);
                $pro_css_ver = ($use_filemtime && function_exists('filemtime'))
                    ? (string) filemtime($pro_css_path)
                    : (defined('KING_ADDONS_PRO_VERSION') ? KING_ADDONS_PRO_VERSION : KING_ADDONS_VERSION);
                wp_register_style(
                    KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . $widget_id . '-pro-style',
                    KING_ADDONS_PRO_URL . 'includes/widgets/' . $widget_class . '_Pro/style.css',
                    null,
                    $pro_css_ver
                );
            }

            // Register Pro JS if exists
            $pro_js_path = $pro_widget_dir . 'script.js';
            if (file_exists($pro_js_path)) {
                $use_filemtime = $is_elementor_edit_or_preview || (defined('WP_DEBUG') && WP_DEBUG) || (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG);
                $pro_js_ver = ($use_filemtime && function_exists('filemtime'))
                    ? (string) filemtime($pro_js_path)
                    : (defined('KING_ADDONS_PRO_VERSION') ? KING_ADDONS_PRO_VERSION : KING_ADDONS_VERSION);
                wp_register_script(
                    KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . $widget_id . '-pro-script',
                    KING_ADDONS_PRO_URL . 'includes/widgets/' . $widget_class . '_Pro/script.js',
                    array('jquery'),
                    $pro_js_ver,
                    true
                );
            }
        }
    }
}

RegisterAssets::instance();