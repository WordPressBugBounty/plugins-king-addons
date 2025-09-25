<?php

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Repeater;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}



class Pricing_Slider extends Widget_Base
{
    

    public function get_name(): string
    {
        return 'king-addons-pricing-slider';
    }

    public function get_title(): string
    {
        return esc_html__('Pricing Slider', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-pricing-slider';
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-pricing-slider-style'];
    }

    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-pricing-slider-script'];
    }

    public function get_categories(): array
    {
        return ['king-addons'];
    }

    public function get_keywords(): array
    {
        return [
            'price',
            'pricing',
            'slider',
            'pricing slider',
            'features',
            'range slider',
            'slider pricing',
            'king addons',
            'king',
            'addons',
            'kingaddons',
            'king-addons'
        ];
    }

    public function get_custom_help_url()
    {
        return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'section_pricing_slider',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Pricing Slider', 'king-addons'),
            ]
        );

        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            $this->add_control(
                'pricing_slider_pro_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => 'Advanced Formulas, Multiple Sliders, and WooCommerce Integration options are available in the <strong><a href="https://kingaddons.com/pricing/?utm_source=kng-module-pricing-slider-settings-upgrade-pro&utm_medium=plugin&utm_campaign=kng" target="_blank">Pro version</a></strong>',
                    'content_classes' => 'king-addons-pro-notice',
                ]
            );
        }

        $this->add_control(
            'title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Pricing Plans', 'king-addons'),
                'placeholder' => esc_html__('Enter title', 'king-addons'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Choose the perfect plan for your needs', 'king-addons'),
                'placeholder' => esc_html__('Enter description', 'king-addons'),
                'rows' => 5,
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_pricing_slider_settings',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Slider Settings', 'king-addons'),
            ]
        );

        $this->add_control(
            'min_value',
            [
                'label' => esc_html__('Minimum Value', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1,
                'min' => 0,
                'step' => 1,
            ]
        );

        $this->add_control(
            'max_value',
            [
                'label' => esc_html__('Maximum Value', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 100,
                'min' => 1,
                'step' => 1,
            ]
        );

        $this->add_control(
            'default_value',
            [
                'label' => esc_html__('Default Value', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 50,
                'min' => 1,
                'step' => 1,
            ]
        );

        $this->add_control(
            'step',
            [
                'label' => esc_html__('Step', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1,
                'min' => 1,
                'step' => 1,
            ]
        );

        $this->add_control(
            'min_label',
            [
                'label' => esc_html__('Minimum Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Basic', 'king-addons'),
                'placeholder' => esc_html__('Enter label', 'king-addons'),
            ]
        );

        $this->add_control(
            'max_label',
            [
                'label' => esc_html__('Maximum Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Premium', 'king-addons'),
                'placeholder' => esc_html__('Enter label', 'king-addons'),
            ]
        );

        $this->add_control(
            'price_prefix',
            [
                'label' => esc_html__('Price Prefix', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => esc_html__('Before price', 'king-addons'),
            ]
        );

        $this->add_control(
            'price_suffix',
            [
                'label' => esc_html__('Price Suffix', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => esc_html__('After price', 'king-addons'),
            ]
        );

        $this->add_control(
            'currency_symbol',
            [
                'label' => esc_html__('Currency Symbol', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '' => esc_html__('None', 'king-addons'),
                    'dollar' => '&#36; ' . esc_html__('Dollar', 'king-addons'),
                    'euro' => '&#128; ' . esc_html__('Euro', 'king-addons'),
                    'pound' => '&#163; ' . esc_html__('Pound Sterling', 'king-addons'),
                    'ruble' => '&#8381; ' . esc_html__('Ruble', 'king-addons'),
                    'shekel' => '&#8362; ' . esc_html__('Shekel', 'king-addons'),
                    'baht' => '&#3647; ' . esc_html__('Baht', 'king-addons'),
                    'yen' => '&#165; ' . esc_html__('Yen/Yuan', 'king-addons'),
                    'won' => '&#8361; ' . esc_html__('Won', 'king-addons'),
                    'rupee' => '&#8360; ' . esc_html__('Rupee', 'king-addons'),
                    'lira' => '&#8356; ' . esc_html__('Lira', 'king-addons'),
                    'peseta' => '&#8359; ' . esc_html__('Peseta', 'king-addons'),
                    'custom' => esc_html__('Custom', 'king-addons'),
                ],
                'default' => 'dollar',
            ]
        );

        $this->add_control(
            'currency_symbol_custom',
            [
                'label' => esc_html__('Custom Symbol', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'currency_symbol' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'currency_position',
            [
                'label' => esc_html__('Currency Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'before',
                'options' => [
                    'before' => esc_html__('Before', 'king-addons'),
                    'after' => esc_html__('After', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'period',
            [
                'label' => esc_html__('Period', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('/month', 'king-addons'),
                'placeholder' => esc_html__('e.g. /month', 'king-addons'),
            ]
        );

        $this->add_control(
            'price_formula',
            [
                'label' => esc_html__('Price Formula', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'linear',
                'options' => [
                    'linear' => esc_html__('Linear (ax + b)', 'king-addons'),
                    'custom' => esc_html__('Custom Prices', 'king-addons'),
                ],
                'condition' => [
                    'advanced_formula_types' => 'linear',
                ],
            ]
        );

        $this->add_control(
            'formula_a',
            [
                'label' => esc_html__('Coefficient (a)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1,
                'step' => 0.1,
                'condition' => [
                    'price_formula' => 'linear',
                ],
            ]
        );

        $this->add_control(
            'formula_b',
            [
                'label' => esc_html__('Constant (b)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'step' => 1,
                'condition' => [
                    'price_formula' => 'linear',
                ],
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'value',
            [
                'label' => esc_html__('Value', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1,
                'min' => 0,
                'step' => 1,
            ]
        );

        $repeater->add_control(
            'price',
            [
                'label' => esc_html__('Price', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 10,
                'min' => 0,
                'step' => 0.01,
            ]
        );

        $this->add_control(
            'custom_prices',
            [
                'label' => esc_html__('Custom Prices', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'value' => 1,
                        'price' => 9.99,
                    ],
                    [
                        'value' => 50,
                        'price' => 49.99,
                    ],
                    [
                        'value' => 100,
                        'price' => 99.99,
                    ],
                ],
                'title_field' => '{{{ value }}} - {{{ price }}}',
                'condition' => [
                    'price_formula' => 'custom',
                ],
            ]
        );

        $this->end_controls_section();

        // Features Section
        $this->start_controls_section(
            'section_features',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Features', 'king-addons'),
            ]
        );

        $this->add_control(
            'show_features',
            [
                'label' => esc_html__('Show Features', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'label_on' => esc_html__('Show', 'king-addons'),
                'label_off' => esc_html__('Hide', 'king-addons'),
            ]
        );

        $feature_repeater = new Repeater();

        $feature_repeater->add_control(
            'feature_text',
            [
                'label' => esc_html__('Feature Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Feature Item', 'king-addons'),
                'label_block' => true,
            ]
        );

        $feature_repeater->add_control(
            'min_value_feature',
            [
                'label' => esc_html__('Minimum Value', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1,
                'min' => 0,
                'step' => 1,
                'description' => esc_html__('Feature will be included starting from this value', 'king-addons'),
            ]
        );

        $feature_repeater->add_control(
            'feature_icon_included',
            [
                'label' => esc_html__('Included Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-check',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $feature_repeater->add_control(
            'feature_icon_excluded',
            [
                'label' => esc_html__('Excluded Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-times',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'feature_list',
            [
                'label' => esc_html__('Features', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $feature_repeater->get_controls(),
                'default' => [
                    [
                        'feature_text' => esc_html__('Basic Feature', 'king-addons'),
                        'min_value_feature' => 1,
                    ],
                    [
                        'feature_text' => esc_html__('Advanced Feature', 'king-addons'),
                        'min_value_feature' => 30,
                    ],
                    [
                        'feature_text' => esc_html__('Premium Feature', 'king-addons'),
                        'min_value_feature' => 60,
                    ],
                ],
                'title_field' => '{{{ feature_text }}}',
                'condition' => [
                    'show_features' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Button Section
        $this->start_controls_section(
            'section_button',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Button', 'king-addons'),
            ]
        );

        $this->add_control(
            'show_button',
            [
                'label' => esc_html__('Show Button', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'label_on' => esc_html__('Show', 'king-addons'),
                'label_off' => esc_html__('Hide', 'king-addons'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Choose Plan', 'king-addons'),
                'placeholder' => esc_html__('Enter button text', 'king-addons'),
                'condition' => [
                    'show_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'button_url',
            [
                'label' => esc_html__('Button URL', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'king-addons'),
                'default' => [
                    'url' => '#',
                ],
                'condition' => [
                    'show_button' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Tab
        $this->start_controls_section(
            'section_header_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Header', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333333', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__title',
                // Example: Set a default size if desired
                // 'fields_options' => [
                //     'font_size' => ['default' => ['unit' => 'px', 'size' => 28]],
                // ],
            ]
        );

        $this->add_control(
            'description_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#777777', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__description' => 'color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'description_typography',
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__description',
                // Example: Set a default size if desired
                // 'fields_options' => [
                //     'font_size' => ['default' => ['unit' => 'px', 'size' => 16]],
                // ],
            ]
        );

        $this->add_responsive_control(
            'header_spacing',
            [
                'label' => esc_html__('Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [ // Added default
                    'unit' => 'px',
                    'size' => 30,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'header_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__header' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Slider Style Section
        $this->start_controls_section(
            'section_slider_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Slider', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // Track Styles
        $this->add_control(
            'slider_track_heading',
            [
                'label' => esc_html__('Track (Line)', 'king-addons'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'slider_track_color',
            [
                'label' => esc_html__('Track Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#e0e0e0', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__track' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'slider_track_height',
            [
                'label' => esc_html__('Track Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 6,
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--slider-track-height: {{SIZE}}{{UNIT}};',
                ],
                'render_type' => 'template',
            ]
        );

        // Progress Styles
        $this->add_control(
            'slider_progress_heading',
            [
                'label' => esc_html__('Progress (Active Track)', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'slider_progress_color',
            [
                'label' => esc_html__('Progress Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#0073e6', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__progress' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        // Thumb Styles
        $this->add_control(
            'slider_thumb_heading',
            [
                'label' => esc_html__('Thumb (Handle)', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'slider_thumb_color',
            [
                'label' => esc_html__('Thumb Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#0073e6', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__custom-thumb' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'slider_thumb_size',
            [
                'label' => esc_html__('Thumb Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 24,
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--slider-thumb-size: {{SIZE}}{{UNIT}};',
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'slider_thumb_border_heading',
            [
                'label' => esc_html__('Thumb Border', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'slider_thumb_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__custom-thumb' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'slider_thumb_border_width',
            [
                'label' => esc_html__('Border Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 20,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 2,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__custom-thumb' => 'border-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'slider_thumb_border_style',
            [
                'label' => esc_html__('Border Style', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '' => esc_html__('Default', 'king-addons'),
                    'none' => esc_html__('None', 'king-addons'),
                    'solid' => esc_html__('Solid', 'king-addons'),
                    'dashed' => esc_html__('Dashed', 'king-addons'),
                    'dotted' => esc_html__('Dotted', 'king-addons'),
                    'double' => esc_html__('Double', 'king-addons'),
                ],
                'default' => 'solid',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__custom-thumb' => 'border-style: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'slider_thumb_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => '50',
                    'right' => '50',
                    'bottom' => '50',
                    'left' => '50',
                    'unit' => '%',
                    'isLinked' => true,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__custom-thumb' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Shadow
        $this->add_control(
            'slider_thumb_shadow_heading',
            [
                'label' => esc_html__('Thumb Shadow', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'slider_thumb_shadow',
            [
                'label' => esc_html__('Enable Shadow', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
                'prefix_class' => 'king-addons-slider-thumb-shadow-',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'slider_thumb_box_shadow',
                'label' => esc_html__('Thumb Shadow Settings', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__custom-thumb',
                'condition' => [
                    'slider_thumb_shadow' => 'yes',
                ],
            ]
        );

        // Current Value Indicator Styles
        $this->add_control(
            'current_value_indicator_heading',
            [
                'label' => esc_html__('Current Value Indicator', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'description' => esc_html__('Style the numeric value displayed below the thumb.', 'king-addons'),
            ]
        );

        $this->add_control(
            'show_current_value_indicator',
            [
                'label' => esc_html__('Show Current Value', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'king-addons'),
                'label_off' => esc_html__('Hide', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
                // We can use prefix_class later if needed, for now control via render()
            ]
        );

        $this->add_control(
            'current_value_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'transparent', // Updated default
                'condition' => ['show_current_value_indicator' => 'yes'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__current-value' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'current_value_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333333', // Updated default
                'condition' => ['show_current_value_indicator' => 'yes'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__current-value' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'current_value_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default' => [ // Added default
                    'top' => '2',
                    'right' => '5',
                    'bottom' => '2',
                    'left' => '5',
                    'unit' => 'px',
                    'isLinked' => false,
                ],
                'condition' => ['show_current_value_indicator' => 'yes'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__current-value' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'current_value_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'default' => [ // Added default
                    'unit' => 'px',
                    'size' => 3,
                ],
                'condition' => ['show_current_value_indicator' => 'yes'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 50],
                    '%' => ['min' => 0, 'max' => 50],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__current-value' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'current_value_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__current-value',
                'condition' => ['show_current_value_indicator' => 'yes'],
                'fields_options' => [ // Added default
                    'font_weight' => ['default' => '600'],
                ],
            ]
        );

        // Range Values Styles
        $this->add_control(
            'range_values_indicator_heading',
            [
                'label' => esc_html__('Range Min/Max Values', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'range_values_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#555555', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__range-min-value, {{WRAPPER}} .king-addons-pricing-slider__range-max-value' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'range_values_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__range-min-value, {{WRAPPER}} .king-addons-pricing-slider__range-max-value',
            ]
        );

        $this->add_control(
            'range_values_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__range-min-value, {{WRAPPER}} .king-addons-pricing-slider__range-max-value' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'range_values_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__range-min-value, {{WRAPPER}} .king-addons-pricing-slider__range-max-value' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'range_values_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 50],
                    '%' => ['min' => 0, 'max' => 50],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__range-min-value, {{WRAPPER}} .king-addons-pricing-slider__range-max-value' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'range_values_margin',
            [
                'label' => esc_html__('Margin (Around Container)', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 5, // Keep top margin
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__range-values' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();

        // Style Tab
        $this->start_controls_section(
            'section_control_group_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Control Groups', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'control_group_background_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#0000000A', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__control-group' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'control_group_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 6,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__control-group' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'control_group_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 10,
                    'right' => 10,
                    'bottom' => 10,
                    'left' => 10,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__control-group' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'control_group_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 25, // Updated default
                    'left' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__control-group' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Price Style
        $this->start_controls_section(
            'section_price_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Price', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'price_color',
            [
                'label' => esc_html__('Price Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#0073e6', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'price_typography',
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__price',
                'fields_options' => [ // Added default
                    'font_size' => ['default' => ['unit' => 'px', 'size' => 48]],
                    'font_weight' => ['default' => '700'],
                ],
            ]
        );

        $this->add_control(
            'currency_color',
            [
                'label' => esc_html__('Currency Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#0073e6', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__currency' => 'color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'currency_typography',
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__currency',
            ]
        );

        $this->add_control(
            'period_color',
            [
                'label' => esc_html__('Period Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#777777', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__period' => 'color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'period_typography',
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__period',
                'fields_options' => [ // Added default
                    'font_size' => ['default' => ['unit' => 'px', 'size' => 16]],
                    'font_weight' => ['default' => '400'],
                ],
            ]
        );

        $this->add_responsive_control(
            'price_spacing',
            [
                'label' => esc_html__('Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [ // Added default
                    'unit' => 'px',
                    'size' => 30,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__display' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'price_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__display' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Features Style
        $this->start_controls_section(
            'section_features_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Features', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_features' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'feature_item_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333333', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__feature-item' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'feature_typography',
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__feature-item',
            ]
        );

        $this->add_control(
            'feature_included_color',
            [
                'label' => esc_html__('Included Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#28a745', // Updated default (Green)
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__feature-item.king-addons-feature-included .king-addons-pricing-slider__feature-included i' => 'color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'feature_excluded_color',
            [
                'label' => esc_html__('Excluded Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#aaaaaa', // Updated default (Grey)
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__feature-item.king-addons-feature-excluded .king-addons-pricing-slider__feature-excluded i' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'feature_item_bg_color',
            [
                'label' => esc_html__('Default Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'transparent', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__feature-item' => 'background-color: {{VALUE}};',
                ],
                'separator' => 'before',
                'description' => esc_html__('Default background for all feature items. Specific backgrounds can be set below.', 'king-addons'),
            ]
        );

        // Included Feature Styles Heading
        $this->add_control(
            'feature_included_style_heading',
            [
                'label' => esc_html__('Included Feature Styles', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'feature_item_bg_color_included',
            [
                'label' => esc_html__('Background Color (Included)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'transparent', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__feature-item.king-addons-feature-included' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'feature_text_color_included',
            [
                'label' => esc_html__('Text Color (Included)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333333', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__feature-item.king-addons-feature-included .king-addons-pricing-slider__feature-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'feature_typography_included',
                'label' => esc_html__('Typography (Included)', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__feature-item.king-addons-feature-included .king-addons-pricing-slider__feature-text',
            ]
        );

        // Excluded Feature Styles Heading
        $this->add_control(
            'feature_excluded_style_heading',
            [
                'label' => esc_html__('Excluded Feature Styles', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'feature_item_bg_color_excluded',
            [
                'label' => esc_html__('Background Color (Excluded)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'transparent', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__feature-item.king-addons-feature-excluded' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'feature_text_color_excluded',
            [
                'label' => esc_html__('Text Color (Excluded)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#999999', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__feature-item.king-addons-feature-excluded .king-addons-pricing-slider__feature-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'feature_typography_excluded',
                'label' => esc_html__('Typography (Excluded)', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__feature-item.king-addons-feature-excluded .king-addons-pricing-slider__feature-text',
            ]
        );

        // Feature Item Border & Shadow Heading
        $this->add_control(
            'feature_border_shadow_heading',
            [
                'label' => esc_html__('Feature Item Border & Shadow', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'feature_border',
                'label' => esc_html__('Border (Default)', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__feature-item',
            ]
        );

        $this->add_control(
            'feature_border_color_included',
            [
                'label' => esc_html__('Border Color (Included)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__feature-item.king-addons-feature-included' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'feature_border_border!' => '', // Only show if a border type is selected
                ],
            ]
        );

        $this->add_control(
            'feature_border_color_excluded',
            [
                'label' => esc_html__('Border Color (Excluded)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__feature-item.king-addons-feature-excluded' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'feature_border_border!' => '', // Only show if a border type is selected
                ],
            ]
        );

        $this->add_responsive_control(
            'feature_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__feature-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'after', // Add separator after border controls
            ]
        );

        $this->add_responsive_control(
            'feature_spacing',
            [
                'label' => esc_html__('Spacing Between', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [ // Added default
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__feature-item:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'feature_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [ // Added default
                    'top' => '5',
                    'right' => '0',
                    'bottom' => '5',
                    'left' => '0',
                    'unit' => 'px',
                    'isLinked' => false,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__feature-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'feature_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__feature-item',
            ]
        );

        $this->end_controls_section();

        // Button Style
        $this->start_controls_section(
            'section_button_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_button' => 'yes',
                ],
            ]
        );

        $this->start_controls_tabs('button_style_tabs');

        $this->start_controls_tab(
            'button_normal_tab',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__button, {{WRAPPER}} .king-addons-pricing-slider__add-to-cart' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#0073e6', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__button, {{WRAPPER}} .king-addons-pricing-slider__add-to-cart' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'button_hover_tab',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'button_hover_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__button:hover, {{WRAPPER}} .king-addons-pricing-slider__add-to-cart:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_background_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#005bb5', // Updated default (darker blue)
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__button:hover, {{WRAPPER}} .king-addons-pricing-slider__add-to-cart:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__button:hover, {{WRAPPER}} .king-addons-pricing-slider__add-to-cart:hover' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'button_border_border!' => '',
                ],
            ]
        );

        $this->add_control(
            'button_hover_animation',
            [
                'label' => esc_html__('Hover Animation', 'king-addons'),
                'type' => Controls_Manager::HOVER_ANIMATION,
                // Apply animation class to both buttons? Might need JS intervention if using Add to Cart
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__button, {{WRAPPER}} .king-addons-pricing-slider__add-to-cart',
                'separator' => 'before',
                'fields_options' => [ // Added default
                    'font_weight' => ['default' => '600'],
                ],
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [ // Added default
                    'top' => '12',
                    'right' => '24',
                    'bottom' => '12',
                    'left' => '24',
                    'unit' => 'px',
                    'isLinked' => false,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__button, {{WRAPPER}} .king-addons-pricing-slider__add-to-cart' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__button, {{WRAPPER}} .king-addons-pricing-slider__add-to-cart',
            ]
        );

        $this->add_control(
            'button_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__actions' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Range Labels Style Section
        $this->start_controls_section(
            'section_range_labels_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Range Labels (Min/Max)', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // Minimum Label Styles
        $this->add_control(
            'min_label_style_heading',
            [
                'label' => esc_html__('Minimum Label Style', 'king-addons'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'min_label_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#555555', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__range-min' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'min_label_typography',
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__range-min',
            ]
        );

        $this->add_control(
            'min_label_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__range-min' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'min_label_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__range-min' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'min_label_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__range-min',
            ]
        );

        $this->add_responsive_control(
            'min_label_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__range-min' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Maximum Label Styles
        $this->add_control(
            'max_label_style_heading',
            [
                'label' => esc_html__('Maximum Label Style', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'max_label_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#555555', // Updated default
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__range-max' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'max_label_typography',
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__range-max',
            ]
        );

        $this->add_control(
            'max_label_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__range-max' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'max_label_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__range-max' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'max_label_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__range-max',
            ]
        );

        $this->add_responsive_control(
            'max_label_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__range-max' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section(); // End Section Range Labels Style

        // Call placeholder methods for Pro features
        $this->add_control_slider_labels_styling();
        $this->add_control_advanced_formula_types();
        $this->add_control_multiple_sliders();
        $this->add_control_woocommerce_integration();
        $this->add_control_notifications_styling();

        // Add Pro Features section
        Core::renderProFeaturesSection(
            $this,
            '',
            Controls_Manager::RAW_HTML,
            'pricing-slider',
            [
                esc_html__('Advanced Formula Types (Exponential, Logarithmic, etc.)', 'king-addons'),
                esc_html__('Multiple Interactive Sliders', 'king-addons'),
                esc_html__('WooCommerce Integration', 'king-addons'),
                esc_html__('Multi-Currency Support', 'king-addons'),
                esc_html__('Advanced Animation Effects', 'king-addons'),
                esc_html__('Premium Templates Library', 'king-addons'),
            ]
        );
    }

    public function get_currency_symbol($symbol_name)
    {
        $symbols = [
            'dollar' => '&#36;',
            'euro' => '&#128;',
            'pound' => '&#163;',
            'ruble' => '&#8381;',
            'shekel' => '&#8362;',
            'baht' => '&#3647;',
            'yen' => '&#165;',
            'won' => '&#8361;',
            'rupee' => '&#8360;',
            'lira' => '&#8356;',
            'peseta' => '&#8359;',
        ];

        if (isset($symbols[$symbol_name])) {
            return $symbols[$symbol_name];
        }

        return '';
    }

    public function calculate_price($value, $settings)
    {
        if ($settings['price_formula'] === 'linear') {
            // Linear formula: price = a * value + b
            return $settings['formula_a'] * $value + $settings['formula_b'];
        } else {
            // Custom prices - find closest value or interpolate
            $custom_prices = isset($settings['custom_prices']) && is_array($settings['custom_prices']) ? $settings['custom_prices'] : [];

            // Prevent usort() on null or non-array
            if (empty($custom_prices)) {
                return 0;
            }

            // Sort by value
            usort($custom_prices, function ($a, $b) {
                return $a['value'] <=> $b['value'];
            });

            // Find exact match or closest values for interpolation
            foreach ($custom_prices as $index => $price_point) {
                if ($price_point['value'] == $value) {
                    // Exact match
                    return $price_point['price'];
                }

                if ($index < count($custom_prices) - 1) {
                    $next_price_point = $custom_prices[$index + 1];

                    // If value is between this point and next point, interpolate
                    if ($value > $price_point['value'] && $value < $next_price_point['value']) {
                        // Linear interpolation
                        $ratio = ($value - $price_point['value']) / ($next_price_point['value'] - $price_point['value']);
                        return $price_point['price'] + $ratio * ($next_price_point['price'] - $price_point['price']);
                    }
                }
            }

            // If no match or interpolation, use first or last point
            if ($value <= $custom_prices[0]['value']) {
                return $custom_prices[0]['price'];
            } else {
                return end($custom_prices)['price'];
            }
        }
    }

    public function is_feature_included($feature_min_value, $current_value)
    {
        return $current_value >= $feature_min_value;
    }

    public function render()
    {
        $settings = $this->get_settings_for_display();
        $default_value = isset($settings['default_value']) ? $settings['default_value'] : 50;

        // Ensure default value is within range
        $default_value = max($settings['min_value'], min($settings['max_value'], $default_value));

        // Calculate initial price
        $initial_price = $this->calculate_price($default_value, $settings);

        // Get currency symbol
        $currency_symbol = '';
        if (!empty($settings['currency_symbol'])) {
            if ($settings['currency_symbol'] === 'custom') {
                $currency_symbol = $settings['currency_symbol_custom'];
            } else {
                $currency_symbol = $this->get_currency_symbol($settings['currency_symbol']);
            }
        }

        // Format price (2 decimal places for cents)
        $formatted_price = number_format($initial_price, 2);

        // Build price HTML
        $price_html = '';
        if ($settings['currency_position'] === 'before') {
            $price_html .= '<span class="king-addons-pricing-slider__currency">' . $currency_symbol . '</span>';
        }

        if (!empty($settings['price_prefix'])) {
            $price_html .= '<span class="king-addons-pricing-slider__prefix">' . esc_html($settings['price_prefix']) . '</span>';
        }

        $price_html .= '<span class="king-addons-pricing-slider__price-value">' . $formatted_price . '</span>';

        if (!empty($settings['price_suffix'])) {
            $price_html .= '<span class="king-addons-pricing-slider__suffix">' . esc_html($settings['price_suffix']) . '</span>';
        }

        if ($settings['currency_position'] === 'after') {
            $price_html .= '<span class="king-addons-pricing-slider__currency">' . $currency_symbol . '</span>';
        }

        if (!empty($settings['period'])) {
            $price_html .= '<span class="king-addons-pricing-slider__period">' . esc_html($settings['period']) . '</span>';
        }

        // Generate unique ID for the slider
        $slider_id = 'king-addons-pricing-slider-' . $this->get_id();

        // Prepare JSON data for features
        $features_data = [];
        if ($settings['show_features'] === 'yes' && !empty($settings['feature_list'])) {
            foreach ($settings['feature_list'] as $feature) {
                $features_data[] = [
                    'text' => $feature['feature_text'],
                    'min_value' => $feature['min_value_feature'],
                    'icon_included' => !empty($feature['feature_icon_included']['value']) ? $feature['feature_icon_included']['value'] : 'fas fa-check',
                    'icon_excluded' => !empty($feature['feature_icon_excluded']['value']) ? $feature['feature_icon_excluded']['value'] : 'fas fa-times',
                ];
            }
        }

        // Prepare price data for JS
        $price_data = [
            'formula' => $settings['price_formula'],
            'a' => isset($settings['formula_a']) ? (float)$settings['formula_a'] : 1,
            'b' => isset($settings['formula_b']) ? (float)$settings['formula_b'] : 0,
            'custom_prices' => isset($settings['custom_prices']) ? $settings['custom_prices'] : [],
            'currency_position' => $settings['currency_position'],
            'currency_symbol' => $currency_symbol,
            'price_prefix' => esc_html($settings['price_prefix']),
            'price_suffix' => esc_html($settings['price_suffix']),
            'period' => esc_html($settings['period']),
        ];

        // Add Pro-version formula parameters if they exist
        if (isset($settings['advanced_formula_types'])) {
            $price_data['advanced_formula_types'] = $settings['advanced_formula_types'];

            // Add parameters for exponential formula
            if (isset($settings['formula_exp_base'])) {
                $price_data['formula_exp_base'] = (float)$settings['formula_exp_base'];
            }
            if (isset($settings['formula_exp_multiplier'])) {
                $price_data['formula_exp_multiplier'] = (float)$settings['formula_exp_multiplier'];
            }

            // Add parameters for logarithmic formula
            if (isset($settings['formula_log_base'])) {
                $price_data['formula_log_base'] = (float)$settings['formula_log_base'];
            }
            if (isset($settings['formula_log_multiplier'])) {
                $price_data['formula_log_multiplier'] = (float)$settings['formula_log_multiplier'];
            }

            // Add parameters for power formula
            if (isset($settings['formula_power_exponent'])) {
                $price_data['formula_power_exponent'] = (float)$settings['formula_power_exponent'];
            }
            if (isset($settings['formula_power_multiplier'])) {
                $price_data['formula_power_multiplier'] = (float)$settings['formula_power_multiplier'];
            }
        }

        // Pass ONLY size settings as CSS variables
        $this->add_render_attribute('_wrapper', 'class', 'king-addons-pricing-slider');
        $this->add_render_attribute(
            '_wrapper',
            'style',
            sprintf(
                '--slider-track-height: %1$spx; --slider-thumb-size: %2$spx;',
                esc_attr($settings['slider_track_height']['size'] ?? '6'),
                esc_attr($settings['slider_thumb_size']['size'] ?? '24')
            )
        );

        // Add class for shadow based on setting
        if ($settings['slider_thumb_shadow'] === 'yes') {
            $this->add_render_attribute('_wrapper', 'class', 'king-addons-slider-thumb-shadow-yes');
        }

        $this->add_render_attribute('_wrapper', 'data-features', json_encode($features_data));
        $this->add_render_attribute('_wrapper', 'data-price-data', json_encode($price_data));
?>
        <div <?php echo $this->get_render_attribute_string('_wrapper'); ?>>
            <?php if (!empty($settings['title']) || !empty($settings['description'])) : ?>
                <div class="king-addons-pricing-slider__header">
                    <?php if (!empty($settings['title'])) : ?>
                        <h3 class="king-addons-pricing-slider__title"><?php echo esc_html($settings['title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($settings['description'])) : ?>
                        <div class="king-addons-pricing-slider__description"><?php echo wp_kses_post($settings['description']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="king-addons-pricing-slider__controls">
                <div class="king-addons-pricing-slider__control-group">
                    <?php if ($settings['min_label'] || $settings['max_label']) : ?>
                        <div class="king-addons-pricing-slider__range-labels">
                            <span class="king-addons-pricing-slider__range-min"><?php echo esc_html($settings['min_label']); ?></span>
                            <span class="king-addons-pricing-slider__range-max"><?php echo esc_html($settings['max_label']); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="king-addons-pricing-slider__range-container">
                        <div class="king-addons-pricing-slider__track"></div>
                        <div class="king-addons-pricing-slider__progress"></div>
                        <div class="king-addons-pricing-slider__custom-thumb"></div>
                        <?php if ('yes' === $settings['show_current_value_indicator']) : ?>
                            <span class="king-addons-pricing-slider__current-value"><?php echo esc_html($default_value); ?></span>
                        <?php endif; ?>
                        <input type="range" class="king-addons-pricing-slider__range"
                            min="<?php echo esc_attr($settings['min_value']); ?>"
                            max="<?php echo esc_attr($settings['max_value']); ?>"
                            step="<?php echo esc_attr($settings['step']); ?>"
                            value="<?php echo esc_attr($default_value); ?>"
                            aria-labelledby="king-addons-pricing-slider-label">
                    </div>

                    <div class="king-addons-pricing-slider__range-values">
                        <span class="king-addons-pricing-slider__range-min-value"><?php echo esc_html($settings['min_value']); ?></span>
                        <span class="king-addons-pricing-slider__range-max-value"><?php echo esc_html($settings['max_value']); ?></span>
                    </div>
                </div>
            </div>

            <div class="king-addons-pricing-slider__display">
                <div class="king-addons-pricing-slider__price"><?php echo $price_html; ?></div>
            </div>

            <?php if ($settings['show_features'] === 'yes' && !empty($settings['feature_list'])) : ?>
                <div class="king-addons-pricing-slider__features">
                    <ul class="king-addons-pricing-slider__feature-list">
                        <?php foreach ($settings['feature_list'] as $feature) :
                            $is_included = $this->is_feature_included($feature['min_value_feature'], $default_value);
                            $icon_class = $is_included ? 'king-addons-pricing-slider__feature-included' : 'king-addons-pricing-slider__feature-excluded';
                            $icon = $is_included ? $feature['feature_icon_included'] : $feature['feature_icon_excluded'];
                            $li_class = $is_included ? 'king-addons-feature-included' : 'king-addons-feature-excluded';
                        ?>
                            <li class="king-addons-pricing-slider__feature-item <?php echo esc_attr($li_class); ?>" data-min-value="<?php echo esc_attr($feature['min_value_feature']); ?>">
                                <span class="<?php echo esc_attr($icon_class); ?>">
                                    <?php \Elementor\Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']); ?>
                                </span>
                                <span class="king-addons-pricing-slider__feature-text"><?php echo esc_html($feature['feature_text']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($settings['show_button'] === 'yes') : ?>
                <div class="king-addons-pricing-slider__actions">
                    <a href="<?php echo esc_url($settings['button_url']['url']); ?>" class="king-addons-pricing-slider__button elementor-animation-<?php echo esc_attr($settings['button_hover_animation']); ?>"
                        <?php echo !empty($settings['button_url']['is_external']) ? ' target="_blank"' : ''; ?>
                        <?php echo !empty($settings['button_url']['nofollow']) ? ' rel="nofollow"' : ''; ?>>
                        <?php echo esc_html($settings['button_text']); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
<?php
    }

    public function add_control_advanced_formula_types()
    {
        // Empty placeholder for Pro feature
        $this->start_controls_section(
            'advanced_formula_types_placeholder',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Advanced Formula Types', 'king-addons'),
            ]
        );

        $this->add_control(
            'advanced_formula_types_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => 'Advanced Formula Types are available in the ' .
                    '<strong><a href="https://kingaddons.com/pricing/?utm_source=kng-module-pricing-slider-settings-upgrade-pro&utm_medium=plugin&utm_campaign=kng" target="_blank">Pro version</a></strong>',
                'content_classes' => 'king-addons-pro-notice',
            ]
        );
        
        $this->end_controls_section();
    }

    /**
     * Pro feature - Multiple sliders
     */
    public function add_control_multiple_sliders()
    {

        $this->start_controls_section(
            'section_multiple_sliders_placeholder',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Multiple Sliders', 'king-addons'),
            ]
        );

        $this->add_control(
            'multiple_sliders_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => 'Multiple interactive sliders with combined price calculation are available in the ' .
                    '<strong><a href="https://kingaddons.com/pricing/?utm_source=kng-module-pricing-slider-settings-upgrade-pro&utm_medium=plugin&utm_campaign=kng" target="_blank">Pro version</a></strong>',
                'content_classes' => 'king-addons-pro-notice',
            ]
        );

        
        

$this->end_controls_section();
    
        
    }

    public function add_control_woocommerce_integration()
    {
        // Section for WooCommerce Placeholder in Free version
        $this->start_controls_section(
            'section_woocommerce_placeholder',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('WooCommerce', 'king-addons'),
            ]
        );

        $this->add_control(
            'woocommerce_integration_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => 'WooCommerce integration with dynamic pricing and automatic cart functionality is available in the ' .
                    '<strong><a href="https://kingaddons.com/pricing/?utm_source=kng-module-pricing-slider-settings-upgrade-pro&utm_medium=plugin&utm_campaign=kng" target="_blank">Pro version</a></strong>',
                'content_classes' => 'king-addons-pro-notice',
            ]
        );

        $this->end_controls_section(); // End of section_woocommerce_placeholder
    }

    /**
     * Adds controls for slider label styling
     */
    public function add_control_slider_labels_styling()
    {
        $this->start_controls_section(
            'section_slider_labels_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Slider Labels', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'slider_label_style_heading',
            [
                'label' => esc_html__('Slider Label Style', 'king-addons'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'slider_label_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__control-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'slider_label_typography',
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__control-label',
                'fields_options' => [
                    'font_weight' => ['default' => '600'],
                    'font_size' => ['default' => ['unit' => 'px', 'size' => 16]],
                ],
            ]
        );

        $this->add_control(
            'slider_label_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__control-label' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'slider_label_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '5',
                    'left' => '0',
                    'unit' => 'px',
                    'isLinked' => false,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__control-label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'slider_label_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '5',
                    'left' => '0',
                    'unit' => 'px',
                    'isLinked' => false,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__control-label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'slider_label_border',
                'label' => esc_html__('Border', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__control-label',
            ]
        );

        $this->add_responsive_control(
            'slider_label_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__control-label' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'slider_label_text_align',
            [
                'label' => esc_html__('Text Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-pricing-slider__control-label' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Text_Shadow::get_type(),
            [
                'name' => 'slider_label_text_shadow',
                'selector' => '{{WRAPPER}} .king-addons-pricing-slider__control-label',
            ]
        );

        $this->end_controls_section();
    }

    public function add_control_notifications_styling()
    {
        // Empty placeholder for Pro feature
    }
}
