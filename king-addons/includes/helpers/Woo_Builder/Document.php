<?php
/**
 * Elementor document type for Woo Builder templates.
 *
 * @package King_Addons
 */

namespace King_Addons\Woo_Builder;

use Elementor\Core\DocumentTypes\Post;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers a custom document type for Woo Builder templates.
 */
class Document extends Post
{
    /**
     * Document type name.
     *
     * @return string
     */
    public static function get_type(): string
    {
        return 'king-addons-woo-builder';
    }

    /**
     * Document type title.
     *
     * @return string
     */
    public static function get_title(): string
    {
        return esc_html__('Woo Builder Template', 'king-addons');
    }

    /**
     * Document properties.
     *
     * @return array<string,mixed>
     */
    public static function get_properties(): array
    {
        $properties = parent::get_properties();
        
        return array_merge($properties, [
            'location' => 'woo-builder',
            'support_wp_page_templates' => true,
            'support_site_editor' => false,
            'has_elements' => true,
            'support_kit' => true,
            'cpt' => ['elementor_library'],
        ]);
    }

    /**
     * Get the document edit URL.
     *
     * @return string
     */
    public function get_edit_url(): string
    {
        $url = parent::get_edit_url();

        if ($url) {
            $url = add_query_arg('action', 'elementor', $url);
        }

        return $url;
    }
}

