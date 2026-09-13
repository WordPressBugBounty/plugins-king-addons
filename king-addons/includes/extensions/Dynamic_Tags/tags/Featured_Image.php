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
 * The featured image of the current post, with fallbacks.
 */
class Featured_Image extends Image_Tag
{
    public function get_name()
    {
        return 'king-addons-featured-image';
    }

    public function get_title()
    {
        return esc_html__('Featured Image', 'king-addons');
    }

    protected function register_controls()
    {
        $this->add_control(
            'source',
            [
                'label' => esc_html__('Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'post' => esc_html__('Current post', 'king-addons'),
                    'term' => esc_html__('Current term', 'king-addons'),
                ],
                'default' => 'post',
                'description' => esc_html__('Term images are read from the "thumbnail_id" term meta, the key WooCommerce and most plugins use.', 'king-addons'),
            ]
        );

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__('Fallback', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'description' => esc_html__('Used when the post has no image.', 'king-addons'),
            ]
        );
    }

    public function get_value(array $options = [])
    {
        $attachment_id = 0;

        if ('term' === $this->get_settings('source')) {
            $term = get_queried_object();
            if ($term instanceof \WP_Term) {
                $attachment_id = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
            }
        } else {
            $post_id = $this->ka_get_post_id();
            if ($post_id) {
                $attachment_id = (int) get_post_thumbnail_id($post_id);
            }
        }

        if ($attachment_id > 0) {
            return $this->ka_image_value($attachment_id);
        }

        $fallback = $this->get_settings('fallback');
        if (is_array($fallback) && !empty($fallback['url'])) {
            return [
                'id' => isset($fallback['id']) ? (int) $fallback['id'] : 0,
                'url' => (string) $fallback['url'],
            ];
        }

        return ['id' => 0, 'url' => ''];
    }
}
