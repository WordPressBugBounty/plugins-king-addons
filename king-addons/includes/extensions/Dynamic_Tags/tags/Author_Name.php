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
 * Display name of the author of the post being displayed.
 */
class Author_Name extends Text_Tag
{
    public function get_name()
    {
        return 'king-addons-author-name';
    }

    public function get_title()
    {
        return esc_html__('Author Name', 'king-addons');
    }

    protected function register_controls()
    {
        // Elementor's own Advanced section already offers Before, After and
        // Fallback for every text tag, and applies them in Tag::get_content().
    }

    public function render()
    {
        $post = $this->ka_get_post();
        $author_id = $post ? (int) $post->post_author : 0;

        if (!$author_id) {
            // On an author archive there is no post to read from.
            $queried = get_queried_object();
            if ($queried instanceof \WP_User) {
                $author_id = (int) $queried->ID;
            }
        }

        $this->ka_echo($author_id ? (string) get_the_author_meta('display_name', $author_id) : '');
    }
}
