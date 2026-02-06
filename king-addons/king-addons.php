<?php

/**
 * Plugin Name: King Addons for Elementor
 * Description: Elementor addons, 4,000+ Templates & Sections, 80+ Widgets, AI Tools, Mega Menu, Popup Builder, WooCommerce, Templates & Sections. Lightweight & fast Elementor toolkit.
 * Author URI: https://kingaddons.com/
 * Author: KingAddons.com
 * Version: 51.1.58
 * Text Domain: king-addons
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

/** @noinspection SpellCheckingInspection */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Load plugin textdomain immediately to prevent early translation loading notices.
load_plugin_textdomain('king-addons');

/** PLUGIN VERSION */
const KING_ADDONS_VERSION = '51.1.58';

/** DEFINES */
define('KING_ADDONS_PATH', plugin_dir_path(__FILE__));
define('KING_ADDONS_URL', plugins_url('/', __FILE__));

/** ASSETS KEY - It's using to have the unique wp_register (style, script) handle */
const KING_ADDONS_ASSETS_UNIQUE_KEY = 'king-addons';

require_once(KING_ADDONS_PATH . 'includes/helpers/Global/global-constants.php');

if (!function_exists('king_addons_freemius')) {
    // Create a helper function for easy SDK access.
    function king_addons_freemius()
    {
        global $king_addons_freemius;

        if (!isset($king_addons_freemius)) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            /** @noinspection PhpUnhandledExceptionInspection */
            $king_addons_freemius = fs_dynamic_init(array(
                'id' => '16154',
                'slug' => 'king-addons',
                'premium_slug' => 'king-addons-pro',
                'type' => 'plugin',
                'public_key' => 'pk_eac3624cbc14c1846cf1ab9abbd68',
                'is_premium' => false,
                'premium_suffix' => 'pro',
                // If your plugin is a serviceware, set this option to false.
                'has_premium_version' => true,
                'has_addons' => false,
                'has_paid_plans' => true,
                'has_affiliation' => 'selected',
                'menu' => array(
                    'slug' => 'king-addons',
                    'first-path' => 'plugins.php',
                    'pricing' => false,
                    'contact' => true,
                    'support' => false,
                    'affiliation' => false,
                ),
            ));
        }

        return $king_addons_freemius;
    }

    // Init Freemius.
    king_addons_freemius();
    // Signal that SDK was initiated.
    do_action('king_addons_freemius_loaded');
    king_addons_freemius()->add_filter('show_deactivation_subscription_cancellation', '__return_false');
    king_addons_freemius()->add_filter('deactivate_on_activation', '__return_false');
}

/**
 * Safe check for Pro/Premium availability.
 *
 * This helper function safely checks if the premium code can be used,
 * preventing fatal errors if Freemius is not properly initialized.
 *
 * @since 51.1.40
 * @return bool True if premium code can be used, false otherwise.
 */
if (!function_exists('king_addons_can_use_pro')) {
    function king_addons_can_use_pro(): bool
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
}

if (!function_exists('king_addons_doActivation')) {
    function king_addons_doActivation()
    {
        add_option('king_addons_plugin_activated', true);
        if (false === get_option('king_addons_optionActivationTime')) {
            add_option('king_addons_optionActivationTime', absint(intval(strtotime('now'))));
        }

        // Ensure wishlist tables are created on activation.
        require_once plugin_dir_path(__FILE__) . 'includes/wishlist/Wishlist_DB.php';
        \King_Addons\Wishlist\Wishlist_DB::maybe_create_tables();

        // Ensure Smart Links tables are created on activation.
        require_once plugin_dir_path(__FILE__) . 'includes/extensions/Smart_Links/Smart_Links_DB.php';
        \King_Addons\Smart_Links\Smart_Links_DB::maybe_create_tables();
        update_option('king_addons_smart_links_flush_rewrite', 1);
    }

    register_activation_hook(__FILE__, 'king_addons_doActivation');
}

if (!function_exists('king_addons_doDectivation')) {
    function king_addons_doDectivation()
    {
        delete_option('king_addons_HFB_flushed_rewrite_rules');
        delete_option('king_addons_optionActivationTime');
    }

    register_deactivation_hook(__FILE__, 'king_addons_doDectivation');
}

if (!function_exists('king_addons_doRedirect_after_activation')) {
    function king_addons_doRedirect_after_activation()
    {
        if (did_action('elementor/loaded')) {
            if (get_option('king_addons_plugin_activated', false)) {
                delete_option('king_addons_plugin_activated');
                wp_redirect(admin_url('admin.php?page=king-addons'));
                exit;
            }
        }
    }

    add_action('admin_init', 'king_addons_doRedirect_after_activation');
}

/**
 * Main function
 *
 * @return void
 * @since 1.0.0
 * @access public
 */
if (!function_exists('king_addons_doPlugin')) {
    /** @noinspection PhpMissingReturnTypeInspection */
    function king_addons_doPlugin()
    {
        require_once(KING_ADDONS_PATH . 'includes/Core.php');
    }
    // Using after_setup_theme to fix: PHP Notice:  Function _load_textdomain_just_in_time was called incorrectly.
    add_action('after_setup_theme', 'king_addons_doPlugin');
}

/**
 * Register Assets
 *
 * @return void
 * @since 1.0.0
 * @access public
 */
if (!function_exists('king_addons_registerAssets')) {
    /** @noinspection PhpMissingReturnTypeInspection */
    function king_addons_registerAssets()
    {
        require_once(KING_ADDONS_PATH . 'includes/RegisterAssets.php');
    }

    add_action('wp_loaded', 'king_addons_registerAssets');
}

/**
 * Enqueue small frontend-only CSS fixes.
 *
 * @return void
 * @since 51.1.42
 */
if (!function_exists('king_addons_enqueue_frontend_fixes_css')) {
    function king_addons_enqueue_frontend_fixes_css(): void
    {
        if (is_admin()) {
            return;
        }

        wp_enqueue_style(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-fixes-for-elementor',
            KING_ADDONS_URL . 'includes/assets/css/fixes-for-elementor.css',
            [],
            KING_ADDONS_VERSION
        );
    }

    add_action('wp_enqueue_scripts', 'king_addons_enqueue_frontend_fixes_css', 99);
    // Ensure the stylesheet is also loaded in Elementor's preview iframe inside the editor.
    add_action('elementor/frontend/after_enqueue_styles', 'king_addons_enqueue_frontend_fixes_css', 99);
    add_action('elementor/preview/enqueue_styles', 'king_addons_enqueue_frontend_fixes_css', 99);
}

/**
 * Hides spaming notices from another plugins on the plugin settings page
 *
 * @return void
 * @since 1.0.0
 * @access public
 */
if (!function_exists('king_addons_hideAnotherNotices')) {
    /** @noinspection PhpMissingReturnTypeInspection */
    function king_addons_hideAnotherNotices()
    {
        // Exclude the account page from notice hiding.
        // URL: /wp-admin/admin.php?page=king-addons-account
        if (isset($_GET['page']) && $_GET['page'] === 'king-addons-account') {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || empty($screen->id)) {
            return;
        }

        $current_screen = (string) $screen->id;

        // Legacy explicit screens that do not follow the generic prefixes.
        $legacy_screens = [
            'edit-king-addons-el-hf',
            'edit-king-addons-fb-sub',
            'header-footer_page_king-addons-el-hf-settings',
        ];

        $is_king_addons_screen = (strpos($current_screen, 'king-addons_') === 0)
            || (strpos($current_screen, 'toplevel_page_king-addons') === 0)
            || in_array($current_screen, $legacy_screens, true);

        if ($is_king_addons_screen) {
            // Remove all notices
            remove_all_actions('user_admin_notices');
            remove_all_actions('admin_notices');
        }
    }

    add_action('in_admin_header', 'king_addons_hideAnotherNotices', 99);
}

/**
 * Custom admin footer text on King Addons pages
 * Shows rating request similar to WooCommerce
 *
 * @param string $footer_text Default footer text.
 * @return string Modified footer text.
 * @since 51.2.0
 */
if (!function_exists('king_addons_admin_footer_text')) {
    function king_addons_admin_footer_text($footer_text)
    {
        if (!current_user_can('manage_options')) {
            return $footer_text;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || empty($screen->id)) {
            return $footer_text;
        }

        $current_screen = (string) $screen->id;

        // Check if we're on a King Addons admin page
        $legacy_screens = [
            'edit-king-addons-el-hf',
            'edit-king-addons-fb-sub',
            'header-footer_page_king-addons-el-hf-settings',
        ];

        $is_king_addons_screen = (strpos($current_screen, 'king-addons_') === 0)
            || (strpos($current_screen, 'toplevel_page_king-addons') === 0)
            || in_array($current_screen, $legacy_screens, true);

        if (!$is_king_addons_screen) {
            return $footer_text;
        }

        // Check if user has already rated
        if (!get_option('king_addons_admin_footer_text_rated')) {
            $footer_text = sprintf(
                /* translators: 1: King Addons 2: five stars */
                __('If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'king-addons'),
                sprintf('<strong>%s</strong>', esc_html__('King Addons', 'king-addons')),
                '<a href="https://wordpress.org/support/plugin/king-addons/reviews?rate=5#new-post" target="_blank" class="king-addons-rating-link" aria-label="' . esc_attr__('five star', 'king-addons') . '" data-rated="' . esc_attr__('Thanks :)', 'king-addons') . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
            );
        } else {
            $footer_text = __('Thank you for using King Addons!', 'king-addons');
        }

        return '<span id="footer-thankyou">' . $footer_text . '</span>';
    }

    add_filter('admin_footer_text', 'king_addons_admin_footer_text', 1);
}

/**
 * Enqueue script for rating link click handler
 *
 * @return void
 * @since 51.2.0
 */
if (!function_exists('king_addons_rating_script')) {
    function king_addons_rating_script()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Only on King Addons pages and only if not already rated
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || empty($screen->id)) {
            return;
        }

        $current_screen = (string) $screen->id;

        $legacy_screens = [
            'edit-king-addons-el-hf',
            'edit-king-addons-fb-sub',
            'header-footer_page_king-addons-el-hf-settings',
        ];

        $is_king_addons_screen = (strpos($current_screen, 'king-addons_') === 0)
            || (strpos($current_screen, 'toplevel_page_king-addons') === 0)
            || in_array($current_screen, $legacy_screens, true);

        if (!$is_king_addons_screen || get_option('king_addons_admin_footer_text_rated')) {
            return;
        }

        $script = "
            (function() {
                'use strict';
                var ratingLink = document.querySelector('a.king-addons-rating-link');
                if (ratingLink) {
                    ratingLink.addEventListener('click', function(e) {
                        var link = e.currentTarget;
                        var formData = new FormData();
                        formData.append('action', 'king_addons_rated');
                        formData.append('nonce', '" . esc_js(wp_create_nonce('king_addons_rated')) . "');
                        
                        fetch('" . esc_js(admin_url('admin-ajax.php')) . "', {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        });
                        
                        var parent = link.parentElement;
                        if (parent) {
                            parent.textContent = link.getAttribute('data-rated');
                        }
                    });
                }
            })();
        ";

        $handle = 'king-addons-admin-footer-rating';
        wp_register_script($handle, '', [], KING_ADDONS_VERSION, true);
        wp_enqueue_script($handle);
        wp_add_inline_script($handle, $script);
    }

    add_action('admin_enqueue_scripts', 'king_addons_rating_script');
}

/**
 * AJAX handler for saving rated status
 *
 * @return void
 * @since 51.2.0
 */
if (!function_exists('king_addons_rated_callback')) {
    function king_addons_rated_callback()
    {
        if (!current_user_can('manage_options')) {
            wp_die(-1, 403);
        }

        check_ajax_referer('king_addons_rated', 'nonce');

        update_option('king_addons_admin_footer_text_rated', 1);
        wp_die();
    }

    add_action('wp_ajax_king_addons_rated', 'king_addons_rated_callback');
}

/**
 * Apply theme detection script to Account page
 * Reads user's theme preference and applies ka-v3-dark class if needed
 *
 * @return void
 * @since 51.2.0
 */
if (!function_exists('king_addons_account_page_theme_script')) {
    function king_addons_account_page_theme_script()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'king-addons_page_king-addons-account') {
            return;
        }

        // Get user's theme preference
        $theme_mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);
        $allowed_theme_modes = ['dark', 'light', 'auto'];
        if (!in_array($theme_mode, $allowed_theme_modes, true)) {
            $theme_mode = 'dark'; // Default to dark theme
        }
        ?>
        <script>
        (function() {
            var themeMode = '<?php echo esc_js($theme_mode); ?>';
            var root = document.documentElement;
            var mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

            // Prevent flash by disabling transitions early (body isn't available in admin_head).
            root.classList.add('ka-no-transition');

            function isDarkForMode(mode) {
                if (mode === 'auto') {
                    return !!(mql && mql.matches);
                }

                return mode === 'dark';
            }

            function applyTheme(mode) {
                var isDark = isDarkForMode(mode);

                root.classList.toggle('ka-v3-dark', isDark);

                // body may not exist yet (this script runs in <head>).
                if (document.body) {
                    document.body.classList.toggle('ka-v3-dark', isDark);
                }
            }

            applyTheme(themeMode);

            // Once body exists, ensure it mirrors the html class.
            document.addEventListener('DOMContentLoaded', function() {
                applyTheme(themeMode);
                if (document.body) {
                    document.body.classList.remove('ka-no-transition');
                }
            });

            // Listen for system theme changes if in auto mode.
            if (themeMode === 'auto' && mql) {
                var onMqlChange = function() {
                    applyTheme('auto');
                };

                if (typeof mql.addEventListener === 'function') {
                    mql.addEventListener('change', onMqlChange);
                } else if (typeof mql.addListener === 'function') {
                    mql.addListener(onMqlChange);
                }
            }

            // Re-enable transitions.
            setTimeout(function() {
                root.classList.remove('ka-no-transition');
            }, 50);
        })();
        </script>
        <?php
    }

    add_action('admin_head', 'king_addons_account_page_theme_script', 1);
}

/**
 * Apply styles to the plugin menu icon because some plugins broke the menu icon styles
 *
 * @return void
 * @since 24.8.25
 * @access public
 */
if (!function_exists('king_addons_styleMenuIcon')) {
    /** @noinspection PhpMissingReturnTypeInspection */
    function king_addons_styleMenuIcon()
    {
        wp_enqueue_style('king-addons-plugin-style-menu-icon', plugin_dir_url(__FILE__) . 'includes/admin/css/menu-icon.css', '', KING_ADDONS_VERSION);
        if (get_current_screen()->id == 'king-addons_page_king-addons-pricing') {
            wp_enqueue_style('king-addons-plugin-style-pricing', plugin_dir_url(__FILE__) . 'includes/admin/css/pricing.css', '', KING_ADDONS_VERSION);
        }
        // Load Account page styles
        if (get_current_screen()->id == 'king-addons_page_king-addons-account') {
            wp_enqueue_style('king-addons-plugin-style-account', plugin_dir_url(__FILE__) . 'includes/admin/css/account.css', '', KING_ADDONS_VERSION);
        }
    }

    add_action('admin_enqueue_scripts', 'king_addons_styleMenuIcon');
}

/**
 * Add "Upgrade to Pro" link to the Plugins list table.
 *
 * @param array $links Existing plugin action links.
 *
 * @return array Modified action links with Upgrade link.
 * @since 24.12.78
 */
if (! function_exists('king_addons_add_action_links')) {
    function king_addons_add_action_links(array $links): array
    {
        // Pricing page with UTM parameters for tracking.
        $pro_url = 'https://kingaddons.com/pricing/?utm_source=kng-plugin-list&utm_medium=wp-plugins-page&utm_campaign=kng';

        // Prepend the Upgrade link.
        $links['go_pro'] = sprintf(
            '<a href="%1$s" target="_blank" class="king-addons-plugins-gopro">%2$s</a>',
            esc_url($pro_url),
            esc_html__('Upgrade to Pro', 'king-addons')
        );

        return $links;
    }

    if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'king_addons_add_action_links');
        add_filter('network_admin_plugin_action_links_' . plugin_basename(__FILE__), 'king_addons_add_action_links');
    }
}
