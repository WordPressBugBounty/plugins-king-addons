<?php
/**
 * Wishlist Page Widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use King_Addons\Wishlist\Wishlist_Frontend;
use King_Addons\Wishlist\Wishlist_Renderer;
use King_Addons\Wishlist\Wishlist_Service;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders a wishlist page/table inside Elementor.
 */
class Wishlist_Page extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string Widget name.
     */
    public function get_name(): string
    {
        return 'king-addons-wishlist-page';
    }

    /**
     * Widget title.
     *
     * @return string Widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Wishlist Page', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string Icon class.
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-wishlist-page';
    }

    /**
     * Widget categories.
     *
     * @return array<int, string> Categories.
     */
    public function get_categories(): array
    {
        return ['king-addons', 'king-addons-woo-builder'];
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string> Style handles.
     */
    public function get_style_depends(): array
    {
        return [
            'king-addons-wishlist',
        ];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string> Script handles.
     */
    public function get_script_depends(): array
    {
        return [
            'king-addons-wishlist',
        ];
    }

    /**
     * Register controls.
     *
     * @return void
     */
    public function get_custom_help_url()
    {
        return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
    }

    protected function register_controls(): void
    {
        $this->start_controls_section(
            'kng_page_content',
            [
                'label' => esc_html__('Wishlist', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_wishlist_id',
            [
                'label' => esc_html__('Wishlist ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Leave empty to use the active wishlist.', 'king-addons'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render wishlist page markup.
     *
     * @return void
     */
    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $service = new Wishlist_Service();
        $renderer = new Wishlist_Renderer($service);
        $frontend = new Wishlist_Frontend($service, $renderer);

        echo $frontend->shortcode_page([
            'wishlist_id' => $settings['kng_wishlist_id'] ?? '',
        ]);
    }
}



