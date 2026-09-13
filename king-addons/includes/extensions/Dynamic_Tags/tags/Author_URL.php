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
 * A link belonging to the post author.
 */
class Author_URL extends Url_Tag
{
    public function get_name()
    {
        return 'king-addons-author-url';
    }

    public function get_title()
    {
        return esc_html__('Author URL', 'king-addons');
    }

    protected function register_controls()
    {
        $this->add_control(
            'url_type',
            [
                'label' => esc_html__('URL', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'archive' => esc_html__('Author archive', 'king-addons'),
                    'website' => esc_html__('Author website', 'king-addons'),
                ],
                'default' => 'archive',
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
            return '';
        }

        if ('website' === $this->get_settings('url_type')) {
            return (string) get_the_author_meta('url', $author_id);
        }

        return (string) get_author_posts_url($author_id);
    }
}
