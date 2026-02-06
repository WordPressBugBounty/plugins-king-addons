<?php
/**
 * Wishlist Counter Widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use King_Addons\Wishlist\Wishlist_Renderer;
use King_Addons\Wishlist\Wishlist_Service;
use King_Addons\Wishlist\Wishlist_Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders a wishlist counter widget.
 */
class Wishlist_Counter extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string Widget name.
     */
    public function get_name(): string
    {
        return 'king-addons-wishlist-counter';
    }

    /**
     * Widget title.
     *
     * @return string Widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Wishlist Counter', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string Icon class.
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-wishlist-counter';
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

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        /**
     * Register controls.
     *
     * @return void
     */
    protected function register_controls(): void
    {
        $this->start_controls_section(
            'kng_counter_content',
            [
                'label' => esc_html__('Counter', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_click_action',
            [
                'label' => esc_html__('Click action', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'wishlist_page' => esc_html__('Go to wishlist page', 'king-addons'),
                    'custom' => esc_html__('Custom URL', 'king-addons'),
                ],
                'default' => 'wishlist_page',
            ]
        );

        $this->add_control(
            'kng_custom_url',
            [
                'label' => esc_html__('Custom URL', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => 'https://',
                'condition' => [
                    'kng_click_action' => 'custom',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_counter_style',
            [
                'label' => esc_html__('Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_counter_color',
            [
                'label' => esc_html__('Text color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-wishlist-counter' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_counter_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-wishlist-counter' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_counter_typography',
                'selector' => '{{WRAPPER}} .king-addons-wishlist-counter',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render counter output.
     *
     * @return void
     */
    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $service = new Wishlist_Service();
        $renderer = new Wishlist_Renderer($service);

        $counter_markup = $renderer->render_counter([]);
        $action = $settings['kng_click_action'] ?? 'wishlist_page';

        if ('custom' === $action && !empty($settings['kng_custom_url']['url'])) {
            $this->add_link_attributes('kng_custom_url', $settings['kng_custom_url']);
            echo '<a ' . $this->get_render_attribute_string('kng_custom_url') . '>' . $counter_markup . '</a>';
            return;
        }

        if ('wishlist_page' === $action) {
            $page_id = Wishlist_Settings::wishlist_page_id();
            if ($page_id) {
                $url = get_permalink($page_id);
                echo '<a href="' . esc_url($url) . '">' . $counter_markup . '</a>';
                return;
            }
        }

        echo $counter_markup;
    }
}



