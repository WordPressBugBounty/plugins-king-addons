<?php
/**
 * Elementor document type for Loop Builder item templates.
 *
 * @package King_Addons
 */

namespace King_Addons\Loop_Builder;

use Elementor\Core\DocumentTypes\Post;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * One repeated card, designed in Elementor and rendered per post by Loop Grid.
 */
class Document extends Post
{
    /**
     * Meta key holding the post type a template was designed for.
     */
    public const META_SOURCE = 'ka_loop_source';

    /**
     * Meta key holding the post used for the editor preview.
     */
    public const META_PREVIEW_ID = 'ka_loop_preview_id';

    /**
     * Document type name.
     *
     * @return string
     */
    public static function get_type(): string
    {
        return 'king-addons-loop-item';
    }

    /**
     * Document type title.
     *
     * @return string
     */
    public static function get_title(): string
    {
        return esc_html__('Loop Item', 'king-addons');
    }

    /**
     * Document properties.
     *
     * @return array<string,mixed>
     */
    public static function get_properties(): array
    {
        return array_merge(parent::get_properties(), [
            'location' => 'king-addons-loop',
            'support_wp_page_templates' => false,
            'support_site_editor' => false,
            'has_elements' => true,
            'support_kit' => true,
            'cpt' => ['elementor_library'],
        ]);
    }

    /**
     * Open the template straight in the editor from the library list.
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
