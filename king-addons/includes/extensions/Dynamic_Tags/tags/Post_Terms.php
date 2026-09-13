<?php
/**
 * King Addons dynamic tag.
 *
 * @package King_Addons
 */

namespace King_Addons\Dynamic_Tags;

use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Terms of a taxonomy attached to the post being displayed.
 */
class Post_Terms extends Text_Tag
{
    public function get_name()
    {
        return 'king-addons-post-terms';
    }

    public function get_title()
    {
        return esc_html__('Post Terms', 'king-addons');
    }

    protected function register_controls()
    {
        $taxonomies = [];
        foreach (get_taxonomies(['show_ui' => true], 'objects') as $slug => $tax) {
            $taxonomies[$slug] = $tax->label;
        }

        $this->add_control(
            'taxonomy',
            [
                'label' => esc_html__('Taxonomy', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $taxonomies,
                'default' => 'category',
            ]
        );

        $this->add_control(
            'separator',
            [
                'label' => esc_html__('Separator', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => ', ',
            ]
        );

        $this->add_control(
            'link',
            [
                'label' => esc_html__('Link to term', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );
    }

    public function render()
    {
        $id = $this->ka_get_post_id();
        $taxonomy = sanitize_key((string) $this->get_settings('taxonomy'));

        if (!$id || '' === $taxonomy || !taxonomy_exists($taxonomy)) {
            $this->ka_echo('');
            return;
        }

        $terms = get_the_terms($id, $taxonomy);
        if (empty($terms) || is_wp_error($terms)) {
            $this->ka_echo('');
            return;
        }

        $link = 'yes' === $this->get_settings('link');
        $parts = [];
        foreach ($terms as $term) {
            if ($link) {
                $url = get_term_link($term);
                if (!is_wp_error($url)) {
                    $parts[] = '<a href="' . esc_url($url) . '">' . esc_html($term->name) . '</a>';
                    continue;
                }
            }
            $parts[] = esc_html($term->name);
        }

        // Parts are escaped individually above; the separator is author input.
        $this->ka_echo(implode(esc_html((string) $this->get_settings('separator')), $parts));
    }
}
