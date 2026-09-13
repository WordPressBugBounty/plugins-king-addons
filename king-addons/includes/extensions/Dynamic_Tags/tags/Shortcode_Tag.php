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
 * Output of a WordPress shortcode.
 */
class Shortcode_Tag extends Text_Tag
{
    public function get_name()
    {
        return 'king-addons-shortcode';
    }

    public function get_title()
    {
        return $this->ka_title_with_pro(esc_html__('Shortcode', 'king-addons'));
    }

    protected function register_controls()
    {
        $this->ka_register_pro_notice();

        $this->add_control(
            'shortcode',
            [
                'label' => esc_html__('Shortcode', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'placeholder' => '[my_shortcode]',
            ]
        );
    }

    public function render()
    {
        if ($this->ka_pro_locked()) {
            return;
        }

        $shortcode = (string) $this->get_settings('shortcode');
        if ('' === trim($shortcode)) {
            return;
        }

        $value = do_shortcode($shortcode);

        // Shortcode output is markup by nature and is author-controlled, so it
        // is printed as-is rather than through the text helper.
        echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
