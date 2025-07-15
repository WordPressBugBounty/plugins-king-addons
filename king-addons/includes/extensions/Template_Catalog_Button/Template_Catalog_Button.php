<?php

/**
 * Template Catalog Button Extension
 * 
 * Adds a button to Elementor editor panel that opens King Addons template catalog in new tab
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Template_Catalog_Button
{
    /**
     * Instance
     *
     * @var Template_Catalog_Button|null The single instance of the class.
     */
    private static ?Template_Catalog_Button $_instance = null;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @return Template_Catalog_Button An instance of the class.
     */
    public static function instance(): Template_Catalog_Button
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        // Only load if templates catalog is enabled
        if (!KING_ADDONS_EXT_TEMPLATES_CATALOG) {
            return;
        }

        // Hook into Elementor editor
        add_action('elementor/editor/before_enqueue_scripts', [$this, 'enqueue_editor_scripts'], 10);
    }

    /**
     * Enqueue scripts for Elementor editor
     */
    public function enqueue_editor_scripts(): void
    {
        wp_enqueue_script(
            'king-addons-template-catalog-button',
            KING_ADDONS_URL . 'includes/extensions/Template_Catalog_Button/assets/template-catalog-button.js',
            ['jquery', 'elementor-editor'],
            KING_ADDONS_VERSION,
            true
        );

        // Localize script with template catalog data
        wp_localize_script(
            'king-addons-template-catalog-button',
            'kingAddonsTemplateCatalog',
            [
                'templateCatalogUrl' => admin_url('admin.php?page=king-addons-templates'),
                'templatesEnabled' => KING_ADDONS_EXT_TEMPLATES_CATALOG,
                'buttonText' => $this->get_button_text(),
                'nonce' => wp_create_nonce('king_addons_template_catalog'),
            ]
        );
    }

    /**
     * Get button text based on user's subscription level
     */
    private function get_button_text(): string
    {
        if (function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code()) {
            return esc_html__('Templates Pro', 'king-addons');
        }
        
        return esc_html__('Free Templates', 'king-addons');
    }
}
