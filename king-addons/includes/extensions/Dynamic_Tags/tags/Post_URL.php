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
 * Permalink of the current post, or of a chosen one.
 */
class Post_URL extends Url_Tag
{
    public function get_name()
    {
        return 'king-addons-post-url';
    }

    public function get_title()
    {
        return esc_html__('Post URL', 'king-addons');
    }

    protected function register_controls()
    {
        $this->add_control(
            'target',
            [
                'label' => esc_html__('Link to', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'current' => esc_html__('Current post', 'king-addons'),
                    'parent' => esc_html__('Parent post', 'king-addons'),
                    'custom' => esc_html__('Specific post ID', 'king-addons'),
                ],
                'default' => 'current',
            ]
        );

        $this->add_control(
            'post_id',
            [
                'label' => esc_html__('Post ID', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'condition' => ['target' => 'custom'],
            ]
        );
    }

    public function get_value(array $options = [])
    {
        $target = (string) $this->get_settings('target');

        if ('custom' === $target) {
            $id = (int) $this->get_settings('post_id');
            return $id > 0 ? (string) get_permalink($id) : '';
        }

        $post = $this->ka_get_post();
        if (!$post) {
            return '';
        }

        if ('parent' === $target) {
            return $post->post_parent ? (string) get_permalink((int) $post->post_parent) : '';
        }

        return (string) get_permalink($post);
    }
}
