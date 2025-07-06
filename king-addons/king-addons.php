<?php

/**
 * Plugin Name: King Addons
 * Description: 600+ Elementor templates, 60+ FREE widgets, and features like Live Search, Popups, Carousels, Image Hotspots, and Parallax Backgrounds.
 * Author URI: https://kingaddons.com/
 * Author: KingAddons.com
 * Version: 24.12.90
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
const KING_ADDONS_VERSION = '24.12.90';

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
                'is_premium' => false, // temp
                'premium_suffix' => 'pro',
                // If your plugin is a serviceware, set this option to false.
                'has_premium_version' => true,
                'has_addons' => false,
                'has_paid_plans' => false, // temp
                'has_affiliation' => 'all',
                'menu' => array(
                    'slug' => 'king-addons',
                    'first-path' => 'plugins.php',
                    'pricing' => false,
                    'contact' => false,
                    'support' => false,
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

if (!function_exists('king_addons_doActivation')) {
    function king_addons_doActivation()
    {
        add_option('king_addons_plugin_activated', true);
        if (false === get_option('king_addons_optionActivationTime')) {
            add_option('king_addons_optionActivationTime', absint(intval(strtotime('now'))));
        }
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
        $current_screen = get_current_screen()->id;
            //    error_log($current_screen);
        if (
            $current_screen == 'toplevel_page_king-addons' ||
            $current_screen == 'toplevel_page_king-addons-templates' ||
            $current_screen == 'toplevel_page_king-addons-popup-builder' ||
            $current_screen == 'edit-king-addons-el-hf' ||
            $current_screen == 'edit-king-addons-fb-sub' ||
            $current_screen == 'header-footer_page_king-addons-el-hf-settings' ||
            $current_screen == 'king-addons_page_king-addons-ai-settings' ||
            $current_screen == 'king-addons_page_king-addons-pricing' ||
            $current_screen == 'king-addons_page_king-addons-settings'
        ) {
            // Remove all notices
            remove_all_actions('user_admin_notices');
            remove_all_actions('admin_notices');
        }
    }

    add_action('in_admin_header', 'king_addons_hideAnotherNotices', 99);
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