<?php
/**
 * Wishlist Button Widget.
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
 * Renders the wishlist button widget.
 */
class Wishlist_Button extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string Widget name.
     */
    public function get_name(): string
    {
        return 'king-addons-wishlist-button';
    }

    /**
     * Widget title.
     *
     * @return string Widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Wishlist Button', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string Icon class.
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-wishlist-button';
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
     * Register widget controls.
     *
     * @return void
     */
    protected function register_controls(): void
    {
        $this->start_controls_section(
            'kng_button_content',
            [
                'label' => esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_mode',
            [
                'label' => esc_html__('Product Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'auto' => esc_html__('Current product (single page)', 'king-addons'),
                    'manual' => esc_html__('Custom product ID', 'king-addons'),
                ],
                'default' => 'auto',
            ]
        );

        $this->add_control(
            'kng_product_id',
            [
                'label' => esc_html__('Product ID', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'condition' => [
                    'kng_mode' => 'manual',
                ],
            ]
        );

        $this->add_control(
            'kng_label_default',
            [
                'label' => esc_html__('Default text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => Wishlist_Settings::get('button_add_text'),
            ]
        );

        $this->add_control(
            'kng_label_added',
            [
                'label' => esc_html__('Added text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => Wishlist_Settings::get('button_added_text'),
            ]
        );

        $this->add_control(
            'kng_display_mode',
            [
                'label' => esc_html__('Display', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'icon_text' => esc_html__('Icon with text', 'king-addons'),
                    'icon' => esc_html__('Icon only', 'king-addons'),
                ],
                'default' => Wishlist_Settings::get('button_display_mode', 'icon_text'),
            ]
        );

        $this->add_control(
            'kng_icon_class',
            [
                'label' => esc_html__('Icon class', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => Wishlist_Settings::get('icon_choice', 'eicon-heart'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_button_style',
            [
                'label' => esc_html__('Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_text_color',
            [
                'label' => esc_html__('Text color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-wishlist-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_background_color',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-wishlist-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_typography',
                'selector' => '{{WRAPPER}} .king-addons-wishlist-button__label',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    protected function render(): void
    {
        $settings = $this->get_settings_for_display();

        $product_id = 'manual' === ($settings['kng_mode'] ?? 'auto')
            ? absint($settings['kng_product_id'])
            : get_the_ID();

        if (!$product_id) {
            echo esc_html__('Select a product to show wishlist button.', 'king-addons');
            return;
        }

        $service = new Wishlist_Service();
        $renderer = new Wishlist_Renderer($service);

        echo $renderer->render_button([
            'product_id' => $product_id,
            'label_default' => $settings['kng_label_default'] ?? Wishlist_Settings::get('button_add_text'),
            'label_added' => $settings['kng_label_added'] ?? Wishlist_Settings::get('button_added_text'),
            'display_mode' => $settings['kng_display_mode'] ?? Wishlist_Settings::get('button_display_mode', 'icon_text'),
            'icon_class' => $settings['kng_icon_class'] ?? Wishlist_Settings::get('icon_choice', 'eicon-heart'),
        ]);
    }
}



