<?php

namespace King_Addons;

/** @noinspection SpellCheckingInspection */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
                wp_register_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . $widget_id . '-' . $js, KING_ADDONS_URL . 'includes/widgets/' . $widget_array['php-class'] . '/' . $js . '.js', array('jquery'), KING_ADDONS_VERSION);
            }
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
        foreach (ModulesMap::getModulesMapArray()['libraries'] as $library_id => $library_array) {
            foreach ($library_array['css'] as $css) {
                wp_register_style(KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . $library_id . '-' . $css, KING_ADDONS_URL . 'includes/assets/libraries/' . $library_id . '/' . $css . '.css', null, KING_ADDONS_VERSION);
            }
            foreach ($library_array['js'] as $js) {
                wp_register_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-' . $library_id . '-' . $js, KING_ADDONS_URL . 'includes/assets/libraries/' . $library_id . '/' . $js . '.js', null, KING_ADDONS_VERSION);
            }
        }
    }
}

RegisterAssets::instance();