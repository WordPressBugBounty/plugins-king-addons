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
 * The title of the post being displayed.
 */
class Post_Title extends Text_Tag
{
    public function get_name()
    {
        return 'king-addons-post-title';
    }

    public function get_title()
    {
        return esc_html__('Post Title', 'king-addons');
    }

    protected function register_controls()
    {
        // Elementor's own Advanced section already offers Before, After and
        // Fallback for every text tag, and applies them in Tag::get_content().
    }

    public function render()
    {
        $id = $this->ka_get_post_id();
        $this->ka_echo($id ? get_the_title($id) : '');
    }
}
