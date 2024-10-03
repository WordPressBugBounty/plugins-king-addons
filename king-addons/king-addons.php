<?php
/**
 * Plugin Name: King Addons
 * Description: King Addons has 200+ premium templates, 30+ FREE widgets like One Page Navigation, Off-Canvas, Image Hotspots, Particles Background.
 * Author URI: https://kingaddons.com/
 * Author: KingAddons.com
 * Version: 24.8.83
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

const KING_ADDONS_VERSION = '24.8.83';
const KING_ADDONS_MINIMUM_PHP_VERSION = '7.4';
const KING_ADDONS_MINIMUM_ELEMENTOR_VERSION = '3.19.0';
const KING_ADDONS__FILE__ = __FILE__;
define('KING_ADDONS_PATH', plugin_dir_path(KING_ADDONS__FILE__));
define('KING_ADDONS_URL', plugins_url('/', KING_ADDONS__FILE__));

// It's using to have the unique wp_register (style, script) handle
const KING_ADDONS_ASSETS_UNIQUE_KEY = 'king-addons';

// Icon for Elementor editor with inline styles included
const KING_ADDONS_ELEMENTOR_ICON = '<img src="' . KING_ADDONS_URL . 'includes/admin/img/icon-for-elementor.svg" alt="King Addons" style="width: 13px; margin-right: 5px; vertical-align: top;">';

if (!version_compare(PHP_VERSION, KING_ADDONS_MINIMUM_PHP_VERSION, '>=')) {

    /** Admin notification when the site doesn't have a minimum required PHP version. */
    $message = sprintf(
    /* translators: %1$s is shortcut that puts required PHP version to the text */
        esc_html__('King Addons plugin requires PHP version %1$s or greater.', 'king-addons'),
        KING_ADDONS_MINIMUM_PHP_VERSION
    );

    echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';

} else {

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

        add_action('plugins_loaded', 'king_addons_doPlugin');
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
        if ($current_screen == 'toplevel_page_king-addons' || $current_screen == 'toplevel_page_king-addons-templates') {
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
    }

    add_action('admin_enqueue_scripts', 'king_addons_styleMenuIcon');
}