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
 * The avatar of the post author, or of the author being viewed.
 */
class Author_Avatar extends Image_Tag
{
    public function get_name()
    {
        return 'king-addons-author-avatar';
    }

    public function get_title()
    {
        return esc_html__('Author Avatar', 'king-addons');
    }

    protected function register_controls()
    {
        $this->add_control(
            'size',
            [
                'label' => esc_html__('Size (px)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 16,
                'max' => 1024,
                'default' => 300,
            ]
        );
    }

    public function get_value(array $options = [])
    {
        $author_id = 0;

        $queried = get_queried_object();
        if ($queried instanceof \WP_User) {
            $author_id = (int) $queried->ID;
        } else {
            $post = $this->ka_get_post();
            if ($post) {
                $author_id = (int) $post->post_author;
            }
        }

        if (!$author_id) {
            return ['id' => 0, 'url' => ''];
        }

        $size = (int) $this->get_settings('size');
        if ($size < 16) {
            $size = 300;
        }

        $url = get_avatar_url($author_id, ['size' => $size]);

        // Avatars are usually remote (Gravatar), so there is no attachment id
        // to hand back.
        return ['id' => 0, 'url' => $url ? (string) $url : ''];
    }
}
