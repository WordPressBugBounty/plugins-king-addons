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
 * The logo set in the Customizer, or the site icon.
 */
class Site_Logo extends Image_Tag
{
    public function get_name()
    {
        return 'king-addons-site-logo';
    }

    public function get_title()
    {
        return esc_html__('Site Logo', 'king-addons');
    }

    protected function register_controls()
    {
        $this->add_control(
            'logo_type',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'logo' => esc_html__('Custom logo', 'king-addons'),
                    'icon' => esc_html__('Site icon', 'king-addons'),
                ],
                'default' => 'logo',
            ]
        );
    }

    public function get_value(array $options = [])
    {
        $attachment_id = 'icon' === $this->get_settings('logo_type')
            ? (int) get_option('site_icon')
            : (int) get_theme_mod('custom_logo');

        if ($attachment_id < 1) {
            return ['id' => 0, 'url' => ''];
        }

        return $this->ka_image_value($attachment_id);
    }
}
