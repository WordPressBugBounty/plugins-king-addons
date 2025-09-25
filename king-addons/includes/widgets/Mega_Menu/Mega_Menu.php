<?php

namespace King_Addons;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}



/**
 * Class Mega_Menu
 *
 * Elementor Mega Menu widget for King Addons plugin.
 *
 * Provides a flexible, customizable mega menu for Elementor, supporting WordPress menus and (in Pro) custom menu items, advanced dropdowns, and mobile layouts.
 *
 * @package King_Addons
 */
class Mega_Menu extends Widget_Base
{
    

    /**
     * Get widget unique name.
     *
     * @return string Widget name.
     */
    public function get_name(): string
    {
        return 'king-addons-mega-menu';
    }

    /**
     * Get widget display title.
     *
     * @return string Widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Mega Menu', 'king-addons');
    }

    /**
     * Get widget icon for Elementor panel.
     *
     * @return string Icon class.
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-mega-menu';
    }

    /**
     * Get style dependencies for the widget.
     *
     * @return array List of style handles.
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-mega-menu-style'];
    }

    /**
     * Get script dependencies for the widget.
     *
     * @return array List of script handles.
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-mega-menu-script'];
    }

    /**
     * Get widget categories for Elementor panel.
     *
     * @return array List of categories.
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Get widget keywords for Elementor search.
     *
     * @return array List of keywords.
     */
    public function get_keywords(): array
    {
        return ['menu', 'nav', 'navigation', 'header', 'navbar', 'mega', 'dropdown', 'king', 'addons', 'kingaddons', 'king-addons'];
    }

    /**
     * Get custom help URL for the widget.
     *
     * @return string Help URL.
     */
    public function get_custom_help_url()
    {
        return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
    }

    /**
     * Get available WordPress navigation menus.
     *
     * @return array Associative array of menu slugs => names.
     */
    public function get_wp_nav_menus(): array
    {
        $menus = wp_get_nav_menus();
        $options = [];

        foreach ($menus as $menu) {
            $options[$menu->slug] = $menu->name;
        }

        return $options;
    }

    /**
     * Placeholder for Pro: Add controls for custom menu items.
     *
     * In Free, shows Pro upgrade notice if 'custom' menu source is selected.
     *
     * @return void
     */
    public function add_custom_menu_items_controls()
    {

        $this->add_control(
            'menu_source_pro_notice',
            [
                'raw' => 'Custom Menu Items and Elementor Templates as dropdown content are available in the <strong><a href="https://kingaddons.com/pricing/?utm_source=kng-module-mega-menu-settings-upgrade-pro&utm_medium=plugin&utm_campaign=kng" target="_blank">Pro version</a></strong>',
                'type' => Controls_Manager::RAW_HTML,
                'content_classes' => 'king-addons-pro-notice',
                'condition' => [
                    'menu_source' => 'custom',
                ]
            ]
        );
    }

    /**
     * Placeholder for Pro: Add controls for mobile menu layout.
     *
     * @return void
     */
    public function add_mobile_menu_layout_controls() {}

    /**
     * Placeholder for Pro: Add advanced dropdown controls.
     *
     * @return void
     */
    public function add_advanced_dropdown_controls() {}

    public function add_advanced_dropdown_animation() {
        // Dropdown Animation Control
        $this->add_control(
            'dropdown_animation',
            [
                'label' => esc_html__('Dropdown Animation', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'fade',
                'options' => [
                    'fade' => esc_html__('Fade', 'king-addons'),
                    'slide' => esc_html__('Slide', 'king-addons'),
                    'none' => esc_html__('None', 'king-addons'),
                    'pro-fade-up' => esc_html__('Fade Up (Pro)', 'king-addons'),
                    'pro-fade-down' => esc_html__('Fade Down (Pro)', 'king-addons'),
                    'pro-fade-left' => esc_html__('Fade Left (Pro)', 'king-addons'),
                    'pro-fade-right' => esc_html__('Fade Right (Pro)', 'king-addons'),
                    'pro-zoom-in' => esc_html__('Zoom In (Pro)', 'king-addons'),
                    'pro-zoom-out' => esc_html__('Zoom Out (Pro)', 'king-addons'),
                ],
            ]
        );
    }

    /**
     * Register Elementor controls for the widget.
     *
     * Defines all settings and style controls for the Mega Menu widget, including menu source, layout, logo, menu items, dropdown, and style options.
     *
     * @return void
     */
    protected function register_controls()
    {
        $this->start_controls_section(
            'section_mega_menu_general',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('General', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'menu_source',
            [
                'label' => esc_html__('Menu Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'wordpress',
                'options' => [
                    'wordpress' => esc_html__('WordPress Menu', 'king-addons'),
                    'custom' => esc_html__('Custom Elementor Menu', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'wp_menu',
            [
                'label' => esc_html__('Select Menu', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => array_keys($this->get_wp_nav_menus())[0] ?? '',
                'options' => $this->get_wp_nav_menus(),
                'condition' => [
                    'menu_source' => 'wordpress',
                ],
            ]
        );

        $this->add_control(
            'show_logo',
            [
                'label' => esc_html__('Show Logo', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
            ]
        );

        $this->add_control(
            'logo_position',
            [
                'label' => esc_html__('Logo Position (Desktop)', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'center' => esc_html__('Center Between', 'king-addons'),
                    'right' => esc_html__('Right', 'king-addons'),
                ],
                'condition' => [
                    'show_logo' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'logo_position_tablet',
            [
                'label' => esc_html__('Logo Position (Tablet)', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'right' => esc_html__('Right', 'king-addons'),
                ],
                'condition' => [
                    'show_logo' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'logo_position_mobile',
            [
                'label' => esc_html__('Logo Position (Mobile)', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'right' => esc_html__('Right', 'king-addons'),
                ],
                'condition' => [
                    'show_logo' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'custom_logo',
            [
                'label' => esc_html__('Custom Logo', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => '',
                ],
                'condition' => [
                    'show_logo' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'center_logo_split_at',
            [
                'label' => esc_html__('Split Menu After Item', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 2,
                'min' => 1,
                'max' => 10,
                'description' => esc_html__('Logo will be placed after this menu item number (e.g., 2 = after 2nd menu item)', 'king-addons'),
                'condition' => [
                    'show_logo' => 'yes',
                    'logo_position' => 'center',
                ],
            ]
        );

        $this->add_control(
            'center_logo_menu_alignment',
            [
                'label' => esc_html__('Menu Items Vertical Alignment', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'center',
                'options' => [
                    'start' => esc_html__('Top', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'end' => esc_html__('Bottom', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-logo-position-center.king-addons-mega-menu-horizontal .king-addons-menu-items' => 'align-items: {{VALUE}};',
                ],
                'condition' => [
                    'show_logo' => 'yes',
                    'logo_position' => 'center',
                ],
            ]
        );

        $this->add_responsive_control(
            'logo_size',
            [
                'label' => esc_html__('Logo Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 500,
                    ],
                    '%' => [
                        'min' => 1,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 150,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mega-menu-logo img' => 'max-width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-mega-menu-logo .king-addons-lottie-animations' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-mega-menu-logo .king-addons-lottie-animations svg' => 'width: 100% !important; height: 100% !important;',
                    '{{WRAPPER}} .king-addons-mega-menu-logo .king-addons-lottie-animations canvas' => 'width: 100% !important; height: 100% !important;',
                ],
                'condition' => [
                    'show_logo' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'menu_layout',
            [
                'label' => esc_html__('Menu Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'horizontal',
                'options' => [
                    'horizontal' => esc_html__('Horizontal', 'king-addons'),
                    'vertical' => esc_html__('Vertical', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'horizontal_alignment',
            [
                'label' => esc_html__('Menu Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'space-between' => [
                        'title' => esc_html__('Justify', 'king-addons'),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'default' => 'flex-end',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mega-menu.king-addons-mega-menu-horizontal' => 'justify-content: {{VALUE}};',
                ],
                'condition' => [
                    'menu_layout' => 'horizontal',
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'vertical_alignment',
            [
                'label' => esc_html__('Menu Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'flex-start',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mega-menu.king-addons-mega-menu-vertical .king-addons-menu-items' => 'align-items: {{VALUE}};',
                ],
                'condition' => [
                    'menu_layout' => 'vertical',
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_mobile_menu_layout_controls();

        $this->end_controls_section();

        // Add Custom Menu Items Section - Only in Pro
        $this->start_controls_section(
            'section_mega_menu_custom_items',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Custom Elementor Menu', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'menu_source' => 'custom',
                ],
            ]
        );

        $this->add_custom_menu_items_controls();

        $this->end_controls_section();

        // TODO: Add dropdown settings
        // Advanced Dropdown Settings - Only in Pro
        $this->start_controls_section(
            'section_mega_menu_dropdown',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Dropdown Settings', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // TODO: Add dropdown width control
        // $this->add_control(
        //     'dropdown_width',
        //     [
        //         'label' => sprintf(__('Dropdown Width %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
        //         'type' => Controls_Manager::SELECT,
        //         'default' => 'default',
        //         'options' => [
        //             'default' => esc_html__('Default', 'king-addons'),
        //             'custom' => esc_html__('Custom', 'king-addons'),
        //             'full-width' => esc_html__('Full Width', 'king-addons'),
        //         ],
        //         'classes' => 'king-addons-pro-control',
        //     ]
        // );

        $this->add_advanced_dropdown_animation();
        $this->add_control(
            'dropdown_animation_duration',
            [
                'label' => esc_html__('Animation Duration (ms)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 300,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mega-menu .king-addons-menu-items ul.sub-menu' => 'transition-duration: {{VALUE}}ms;',
                ],
                'condition' => [
                    'dropdown_animation!' => 'none',
                ],
            ]
        );

        $this->add_advanced_dropdown_controls();

        $this->end_controls_section();

        Core::renderProFeaturesSection(
            $this,
            Controls_Manager::TAB_CONTENT,
            Controls_Manager::RAW_HTML,
            'mega-menu',
            [
                esc_html__('Add Elementor templates as dropdown content', 'king-addons'),
                esc_html__('Create custom menu items with icons', 'king-addons'),
                esc_html__('Build nested dropdown menus with unlimited levels', 'king-addons'),
                esc_html__('Advanced mobile menu layouts', 'king-addons'),
                esc_html__('Set megamenu width and position', 'king-addons'),
                esc_html__('Advanced transitions and animations', 'king-addons'),
            ]
        );

        // Style Sections
        $this->start_controls_section(
            'section_mega_menu_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Menu Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'menu_background',
            [
                'label' => esc_html__('Background (Desktop)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mega-menu' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_background_tablet',
            [
                'label' => esc_html__('Background (Tablet)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '(tablet){{WRAPPER}} .king-addons-mega-menu' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_background_mobile',
            [
                'label' => esc_html__('Background (Mobile)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '(mobile){{WRAPPER}} .king-addons-mega-menu' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'menu_border',
                'selector' => '{{WRAPPER}} .king-addons-mega-menu',
            ]
        );

        $this->add_responsive_control(
            'menu_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mega-menu' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'menu_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mega-menu' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'menu_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-mega-menu',
            ]
        );

        
        

$this->end_controls_section();

        
        

        // Menu Items Style
        $this->start_controls_section(
            'section_mega_menu_items_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Menu Items', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'menu_item_typography',
                'selector' => '{{WRAPPER}} .king-addons-menu-item > a, {{WRAPPER}} .king-addons-menu-items > li > a',
            ]
        );

        $this->add_responsive_control(
            'menu_item_spacing',
            [
                'label' => esc_html__('Items Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mega-menu-horizontal .king-addons-menu-item' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-mega-menu-vertical .king-addons-menu-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-mega-menu-horizontal .king-addons-menu-items > li' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-mega-menu-vertical .king-addons-menu-items > li' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'menu_item_padding',
            [
                'label' => esc_html__('Item Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-item > a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-menu-items > li > a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('menu_item_style_tabs');

        // Normal State
        $this->start_controls_tab(
            'menu_item_style_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'menu_item_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-item > a' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-items > li > a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_item_background_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-item > a' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-items > li > a' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'menu_item_border',
                'selector' => '{{WRAPPER}} .king-addons-menu-item > a, {{WRAPPER}} .king-addons-menu-items > li > a',
            ]
        );

        $this->add_responsive_control(
            'menu_item_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-item > a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-menu-items > li > a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_tab();

        // Hover State
        $this->start_controls_tab(
            'menu_item_style_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'menu_item_text_color_hover',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-item > a:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-item:hover > a' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-items > li > a:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-items > li:hover > a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_item_background_color_hover',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-item > a:hover' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-item:hover > a' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-items > li > a:hover' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-items > li:hover > a' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_item_border_color_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-item > a:hover' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-item:hover > a' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-items > li > a:hover' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-items > li:hover > a' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'menu_item_border_border!' => '',
                ],
            ]
        );

        $this->end_controls_tab();

        // Active State
        $this->start_controls_tab(
            'menu_item_style_active',
            [
                'label' => esc_html__('Active', 'king-addons'),
            ]
        );

        $this->add_control(
            'menu_item_text_color_active',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-item.current-menu-item > a' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-item.current-menu-ancestor > a' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-items > li.current-menu-item > a' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-items > li.current-menu-ancestor > a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_item_background_color_active',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-item.current-menu-item > a' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-item.current-menu-ancestor > a' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-items > li.current-menu-item > a' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-items > li.current-menu-ancestor > a' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'menu_item_border_color_active',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-item.current-menu-item > a' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-item.current-menu-ancestor > a' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-items > li.current-menu-item > a' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-menu-items > li.current-menu-ancestor > a' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'menu_item_border_border!' => '',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // Dropdown Style
        $this->start_controls_section(
            'section_mega_menu_dropdown_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Dropdown Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'dropdown_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#fff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-items ul.sub-menu' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-submenu' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'dropdown_typography',
                'selector' => '{{WRAPPER}} .king-addons-menu-items ul.sub-menu li a, {{WRAPPER}} .king-addons-submenu li a',
            ]
        );

        $this->add_control(
            'dropdown_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-items ul.sub-menu li a' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-submenu li a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dropdown_text_hover_color',
            [
                'label' => esc_html__('Text Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-items ul.sub-menu li a:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-submenu li a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dropdown_item_hover_background',
            [
                'label' => esc_html__('Item Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-items ul.sub-menu li a:hover' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-submenu li a:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'dropdown_border',
                'selector' => '{{WRAPPER}} .king-addons-menu-items ul.sub-menu, {{WRAPPER}} .king-addons-submenu',
            ]
        );

        $this->add_responsive_control(
            'dropdown_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-items ul.sub-menu' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-submenu' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'dropdown_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-menu-items ul.sub-menu' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-submenu' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'dropdown_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-menu-items ul.sub-menu, {{WRAPPER}} .king-addons-submenu',
            ]
        );

        $this->end_controls_section();

        // Mobile Menu Style
        $this->start_controls_section(
            'section_mega_menu_mobile_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Mobile Menu', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'mobile_toggle_color',
            [
                'label' => esc_html__('Toggle Button Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mobile-menu-toggle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'mobile_toggle_background',
            [
                'label' => esc_html__('Toggle Button Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mobile-menu-toggle' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'mobile_toggle_hover_color',
            [
                'label' => esc_html__('Toggle Button Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mobile-menu-toggle:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'mobile_toggle_hover_background',
            [
                'label' => esc_html__('Toggle Button Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mobile-menu-toggle:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'mobile_toggle_active_color',
            [
                'label' => esc_html__('Toggle Button Active Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mobile-menu-toggle.active' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'mobile_toggle_active_background',
            [
                'label' => esc_html__('Toggle Button Active Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mobile-menu-toggle.active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'mobile_menu_background',
            [
                'label' => esc_html__('Menu Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mobile-menu' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'mobile_menu_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mobile-menu .king-addons-menu-items a' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .king-addons-mobile-menu .king-addons-menu-items li a' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'mobile_menu_text_hover_color',
            [
                'label' => esc_html__('Text Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mobile-menu .king-addons-menu-items a:hover' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .king-addons-mobile-menu .king-addons-menu-items li a:hover' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'mobile_menu_typography',
                'selector' => '{{WRAPPER}} .king-addons-mobile-menu a',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'mobile_menu_border',
                'selector' => '{{WRAPPER}} .king-addons-mobile-menu',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'mobile_menu_shadow',
                'selector' => '{{WRAPPER}} .king-addons-mobile-menu',
            ]
        );

        $this->end_controls_section();

        // Logo Style
        $this->start_controls_section(
            'section_mega_menu_logo_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Logo Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_logo' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'logo_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mega-menu-logo' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'logo_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mega-menu-logo' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'logo_border',
                'selector' => '{{WRAPPER}} .king-addons-mega-menu-logo',
            ]
        );

        $this->add_responsive_control(
            'logo_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-mega-menu-logo' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->add_menu_items_hover_effects_controls();
    }

    /**
     * Render the widget output on the frontend.
     *
     * Outputs the Mega Menu HTML structure, including logo, menu, and mobile menu toggle. Handles both horizontal and vertical layouts, logo position, and menu source.
     *
     * @return void
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $menu_layout = $settings['menu_layout'];
        $logo_position = $settings['logo_position'];
        $logo_position_tablet = !empty($settings['logo_position_tablet']) ? $settings['logo_position_tablet'] : $logo_position;
        $logo_position_mobile = !empty($settings['logo_position_mobile']) ? $settings['logo_position_mobile'] : $logo_position;
        $logo_html = '';

        if ($settings['show_logo'] === 'yes') {
            $site_url = get_home_url();
            $logo_content = '';

            // Generate logo content
            if (!empty($settings['custom_logo']['url'])) {
                // Custom logo uploaded via Elementor
                $logo_content = '<img src="' . esc_url($settings['custom_logo']['url']) . '" alt="' . esc_attr(get_bloginfo('name')) . '">';
            } else {
                // Use WordPress theme logo
                $custom_logo_id = get_theme_mod('custom_logo');
                if ($custom_logo_id) {
                    $logo_content = wp_get_attachment_image($custom_logo_id, 'full');
                } else {
                    $logo_content = '<span class="site-title">' . esc_html(get_bloginfo('name')) . '</span>';
                }
            }

            if (!empty($logo_content)) {
                $logo_classes = 'king-addons-mega-menu-logo';
                $logo_classes .= ' king-addons-logo-position-' . esc_attr($logo_position);
                $logo_classes .= ' king-addons-logo-position-tablet-' . esc_attr($logo_position_tablet);
                $logo_classes .= ' king-addons-logo-position-mobile-' . esc_attr($logo_position_mobile);
                
                $logo_html = '<div class="' . esc_attr($logo_classes) . '">';
                $logo_html .= '<a href="' . esc_url($site_url) . '">';
                $logo_html .= $logo_content;
                $logo_html .= '</a>';
                $logo_html .= '</div>';
            }
        }

        $menu_source = $settings['menu_source'];
        $menu_html = '';
        $menu_html_left = '';
        $menu_html_right = '';

        if ($menu_source === 'wordpress' && !empty($settings['wp_menu'])) {
            $args = [
                'menu' => $settings['wp_menu'],
                'container' => false,
                'menu_class' => 'king-addons-menu-items',
                'echo' => false,
                'fallback_cb' => '__return_empty_string',
                'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                'depth' => 3,
                'walker' => new King_Addons_Mega_Menu_Walker_Free(),
            ];

            $menu_html = wp_nav_menu($args);

            // Store split position for center logo (will be handled by JavaScript)
            if ($logo_position === 'center' && !empty($logo_html)) {
                $split_at = isset($settings['center_logo_split_at']) ? (int)$settings['center_logo_split_at'] : 2;
                // We'll let JavaScript handle the splitting for more reliable results
            }
        }

        // Mobile menu toggle button
        $mobile_toggle = '<button class="king-addons-mobile-menu-toggle"><i class="fas fa-bars"></i></button>';

        // Applying styles for vertical alignment
        $vertical_alignment_style = '';
        $vertical_alignment_attr = '';
        if ($menu_layout === 'vertical' && !empty($settings['vertical_alignment'])) {
            $vertical_alignment_style = ' style="align-items: ' . esc_attr($settings['vertical_alignment']) . ';"';
            $vertical_alignment_attr = ' data-vertical-align="' . esc_attr($settings['vertical_alignment']) . '"';
        }

        // Applying styles for horizontal alignment
        $horizontal_alignment_attr = '';
        if ($menu_layout === 'horizontal' && !empty($settings['horizontal_alignment'])) {
            $horizontal_alignment_attr = ' data-horizontal-align="' . esc_attr($settings['horizontal_alignment']) . '"';
        }

        // Add data attributes to main menu wrapper
        $dropdown_animation = !empty($settings['dropdown_animation']) ? $settings['dropdown_animation'] : 'fade';
        $dropdown_animation_attr = ' data-dropdown-animation="' . esc_attr($dropdown_animation) . '"';
        
        // Add center logo data attributes for responsive positions
        $center_logo_attrs = '';
        $has_center_logo = ($logo_position === 'center' || $logo_position_tablet === 'center' || $logo_position_mobile === 'center');
        
        if ($has_center_logo && !empty($logo_html)) {
            $split_at = isset($settings['center_logo_split_at']) ? (int)$settings['center_logo_split_at'] : 2;
            $center_logo_attrs = ' data-center-logo="true" data-split-at="' . esc_attr($split_at) . '"';
            $center_logo_attrs .= ' data-logo-position-desktop="' . esc_attr($logo_position) . '"';
            $center_logo_attrs .= ' data-logo-position-tablet="' . esc_attr($logo_position_tablet) . '"';
            $center_logo_attrs .= ' data-logo-position-mobile="' . esc_attr($logo_position_mobile) . '"';
        }

?>
        <div class="king-addons-mega-menu king-addons-mega-menu-<?php echo esc_attr($menu_layout); ?> king-addons-logo-position-<?php echo esc_attr($logo_position); ?> king-addons-logo-position-tablet-<?php echo esc_attr($logo_position_tablet); ?> king-addons-logo-position-mobile-<?php echo esc_attr($logo_position_mobile); ?>" <?php echo $vertical_alignment_attr . $horizontal_alignment_attr . $dropdown_animation_attr . $center_logo_attrs; ?>>
            
            <?php if ($logo_position === 'left' && !empty($logo_html)) : ?>
                <?php echo $logo_html; ?>
            <?php endif; ?>

            <?php if (!empty($logo_html) && ($logo_position === 'center' || $logo_position_tablet === 'center' || $logo_position_mobile === 'center')) : ?>
                <!-- Center logo will be positioned by JavaScript -->
                <div class="king-addons-center-logo-placeholder">
                    <?php echo $logo_html; ?>
                </div>
            <?php endif; ?>

            <nav class="king-addons-menu-container" <?php echo $vertical_alignment_style; ?>>
                <?php echo $menu_html; ?>
            </nav>

            <?php if ($logo_position === 'right' && !empty($logo_html)) : ?>
                <?php echo $logo_html; ?>
            <?php endif; ?>

            <?php echo $mobile_toggle; ?>
            <div class="king-addons-mobile-menu">
                <?php echo $menu_html; ?>
            </div>
        </div>
<?php
    }

    public function add_menu_items_hover_effects_controls()
    {
        $this->start_controls_section(
            'section_menu_items_hover_effects',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Menu Items Hover Effects', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_control(
            'dropdown_icon_heading',
            [
                'label' => esc_html__('Dropdown Icon', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        $this->add_control(
            'dropdown_icon_pro_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => 'Dropdown icon customization is available in the <strong><a href="https://kingaddons.com/pricing/?utm_source=kng-module-mega-menu-settings-upgrade-pro&utm_medium=plugin&utm_campaign=kng" target="_blank">Pro version</a></strong>',
                'content_classes' => 'king-addons-pro-notice',
            ]
        );
        $this->end_controls_section();
    }
}

class King_Addons_Mega_Menu_Walker_Free extends \Walker_Nav_Menu {
    public function start_el( &$output, $item, $depth = 0, $args = [], $id = 0 ) {
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $is_dropdown = in_array('menu-item-has-children', $classes);
        $output .= '<li class="' . esc_attr(implode(' ', $classes)) . '">';
        $atts = [];
        $atts['href'] = !empty($item->url) ? $item->url : '';
        $atts['title'] = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target) ? $item->target : '';
        $atts['rel'] = !empty($item->xfn) ? $item->xfn : '';
        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $attributes .= ' ' . $attr . '="' . esc_attr($value) . '"';
            }
        }
        $output .= '<a' . $attributes . '>';
        $output .= '<span class="menu-item-text">' . apply_filters('the_title', $item->title, $item->ID) . '</span>';
        if ($is_dropdown) {
            $output .= '<span class="king-addons-dropdown-icon" style="margin-left:8px;"><i class="fas fa-angle-down"></i></span>';
        }
        $output .= '</a>';
    }
    public function end_el( &$output, $item, $depth = 0, $args = [] ) {
        $output .= '</li>';
    }
}
