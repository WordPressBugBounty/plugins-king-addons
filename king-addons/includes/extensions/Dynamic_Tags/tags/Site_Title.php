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
 * The site title from Settings → General.
 */
class Site_Title extends Text_Tag
{
    public function get_name()
    {
        return 'king-addons-site-title';
    }

    public function get_title()
    {
        return esc_html__('Site Title', 'king-addons');
    }

    protected function register_controls()
    {
        // Elementor's own Advanced section already offers Before, After and
        // Fallback for every text tag, and applies them in Tag::get_content().
    }

    public function render()
    {
        $this->ka_echo((string) get_bloginfo('name'));
    }
}
