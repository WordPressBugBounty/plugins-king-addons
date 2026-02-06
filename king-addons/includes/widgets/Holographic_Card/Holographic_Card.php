<?php

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Holographic_Card extends Widget_Base
{
    protected function sanitize_inline_svg(string $svg): string
    {
        $allowed = [
            'svg' => [
                'class' => true,
                'xmlns' => true,
                'width' => true,
                'height' => true,
                'viewbox' => true,
                'viewBox' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'stroke-linecap' => true,
                'stroke-linejoin' => true,
                'aria-hidden' => true,
                'role' => true,
            ],
            'path' => [
                'd' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'stroke-linecap' => true,
                'stroke-linejoin' => true,
                'opacity' => true,
                'transform' => true,
            ],
            'g' => [
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'opacity' => true,
                'transform' => true,
            ],
            'circle' => [
                'cx' => true,
                'cy' => true,
                'r' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'opacity' => true,
            ],
            'rect' => [
                'x' => true,
                'y' => true,
                'width' => true,
                'height' => true,
                'rx' => true,
                'ry' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'opacity' => true,
            ],
            'polygon' => [
                'points' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'opacity' => true,
            ],
            'polyline' => [
                'points' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'opacity' => true,
            ],
            'line' => [
                'x1' => true,
                'y1' => true,
                'x2' => true,
                'y2' => true,
                'stroke' => true,
                'stroke-width' => true,
                'opacity' => true,
            ],
            'ellipse' => [
                'cx' => true,
                'cy' => true,
                'rx' => true,
                'ry' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'opacity' => true,
            ],
        ];

        return (string) wp_kses($svg, $allowed);
    }
    public function get_name(): string
    {
        return 'king-addons-holographic-card';
    }

    public function get_title(): string
    {
        return esc_html__('3D Holographic Card', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-holographic-card';
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-holographic-card-style'];
    }

    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-holographic-card-script'];
    }

    public function get_categories(): array
    {
        return ['king-addons'];
    }

    public function get_keywords(): array
    {
        return ['holographic', 'card', '3d', 'tilt', 'parallax', 'hover', 'interactive', 'animated', 'king', 'addons', 'kingaddons', 'king-addons'];
    }

    public function get_custom_help_url(): string
    {
        return 'https://kingaddons.com/elementor/holographic-card/';
    }

    protected function register_controls(): void
    {
        // =====================================================
        // CONTENT TAB
        // =====================================================

        // === CONTENT SECTION ===
        $this->start_controls_section(
            'king_addons_holo_card_section_content',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'king_addons_holo_card_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('The Engineer', 'king-addons'),
                'placeholder' => esc_html__('Enter your title', 'king-addons'),
                'label_block' => true,
                'dynamic' => ['active' => true],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_meta',
            [
                'label' => esc_html__('Meta Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('19.1% of users share this archetype', 'king-addons'),
                'placeholder' => esc_html__('Enter meta description', 'king-addons'),
                'label_block' => true,
                'dynamic' => ['active' => true],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_badge_text',
            [
                'label' => esc_html__('Badge Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Your Archetype', 'king-addons'),
                'placeholder' => esc_html__('Enter badge text', 'king-addons'),
                'label_block' => true,
                'dynamic' => ['active' => true],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_topline_right',
            [
                'label' => esc_html__('Topline Right', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('2025', 'king-addons'),
                'placeholder' => esc_html__('Enter topline right text', 'king-addons'),
                'label_block' => true,
                'dynamic' => ['active' => true],
            ]
        );

        $this->end_controls_section();

        // === MEDIA SECTION ===
        $this->start_controls_section(
            'king_addons_holo_card_section_media',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Media', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'king_addons_holo_card_media_type',
            [
                'label' => esc_html__('Parallax Layer Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'svg',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'svg' => esc_html__('Default SVG', 'king-addons'),
                    'image' => esc_html__('Image', 'king-addons'),
                    'custom_svg' => esc_html__('Custom SVG', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_image',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => '',
                ],
                'condition' => [
                    'king_addons_holo_card_media_type' => 'image',
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_custom_svg',
            [
                'label' => esc_html__('Custom SVG Code', 'king-addons'),
                'type' => Controls_Manager::CODE,
                'language' => 'html',
                'rows' => 10,
                'default' => '',
                'placeholder' => esc_html__('Paste your SVG code here', 'king-addons'),
                'condition' => [
                    'king_addons_holo_card_media_type' => 'custom_svg',
                ],
            ]
        );

        $this->end_controls_section();

        // === LINK SECTION ===
        $this->start_controls_section(
            'king_addons_holo_card_section_link',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Link', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'king_addons_holo_card_link_enable',
            [
                'label' => esc_html__('Enable Link', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
            ]
        );

        $this->add_control(
            'king_addons_holo_card_link',
            [
                'label' => esc_html__('Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'king-addons'),
                'default' => [
                    'url' => '',
                ],
                'dynamic' => ['active' => true],
                'condition' => [
                    'king_addons_holo_card_link_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // === MOTION SECTION ===
        $this->start_controls_section(
            'king_addons_holo_card_section_motion',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Motion', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'king_addons_holo_card_max_tilt',
            [
                'label' => esc_html__('Max Tilt Angle', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['deg'],
                'range' => [
                    'deg' => [
                        'min' => 0,
                        'max' => 30,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'deg',
                    'size' => 12,
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_smoothness_in',
            [
                'label' => esc_html__('Smoothness In', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [''],
                'range' => [
                    '' => [
                        'min' => 0.01,
                        'max' => 0.5,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'unit' => '',
                    'size' => 0.08,
                ],
                'description' => esc_html__('Higher value = snappier animation', 'king-addons'),
            ]
        );

        $this->add_control(
            'king_addons_holo_card_smoothness_hover',
            [
                'label' => esc_html__('Smoothness Hover', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [''],
                'range' => [
                    '' => [
                        'min' => 0.01,
                        'max' => 0.5,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'unit' => '',
                    'size' => 0.08,
                ],
                'description' => esc_html__('Smoothness while moving mouse inside the card', 'king-addons'),
            ]
        );

        $this->add_control(
            'king_addons_holo_card_smoothness_out',
            [
                'label' => esc_html__('Smoothness Out', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [''],
                'range' => [
                    '' => [
                        'min' => 0.01,
                        'max' => 0.5,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'unit' => '',
                    'size' => 0.12,
                ],
                'description' => esc_html__('Higher value = snappier animation', 'king-addons'),
            ]
        );

        $this->add_control(
            'king_addons_holo_card_parallax_strength',
            [
                'label' => esc_html__('Parallax Strength', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [''],
                'range' => [
                    '' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.05,
                    ],
                ],
                'default' => [
                    'unit' => '',
                    'size' => 0.18,
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_disable_motion',
            [
                'label' => esc_html__('Disable Motion', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'description' => esc_html__('Completely disable 3D tilt and parallax effects', 'king-addons'),
            ]
        );

        $this->add_control(
            'king_addons_holo_card_respect_reduced_motion',
            [
                'label' => esc_html__('Respect Reduced Motion', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => esc_html__('Disable effects for users who prefer reduced motion', 'king-addons'),
            ]
        );

        $this->end_controls_section();

        // =====================================================
        // STYLE TAB
        // =====================================================

        // === CARD BOX STYLE ===
        $this->start_controls_section(
            'king_addons_holo_card_section_style_card',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card Box', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}}' => 'display: flex; justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_width',
            [
                'label' => esc_html__('Card Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => [
                        'min' => 200,
                        'max' => 800,
                        'step' => 10,
                    ],
                    '%' => [
                        'min' => 20,
                        'max' => 100,
                    ],
                    'vw' => [
                        'min' => 20,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 420,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card-stage' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_perspective',
            [
                'label' => esc_html__('3D Perspective', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 500,
                        'max' => 2000,
                        'step' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 1000,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card-stage' => 'perspective: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => '18',
                    'right' => '18',
                    'bottom' => '16',
                    'left' => '18',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_content_gap',
            [
                'label' => esc_html__('Content Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 3,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 14,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__content' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => '26',
                    'right' => '26',
                    'bottom' => '26',
                    'left' => '26',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-holo-card::before' => 'border-radius: calc({{TOP}}{{UNIT}} + 2px) calc({{RIGHT}}{{UNIT}} + 2px) calc({{BOTTOM}}{{UNIT}} + 2px) calc({{LEFT}}{{UNIT}} + 2px);',
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_bg_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'king_addons_holo_card_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-holo-card',
            ]
        );

        $this->add_control(
            'king_addons_holo_card_hover_shadow_heading',
            [
                'label' => esc_html__('Hover Shadow', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'king_addons_holo_card_box_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-holo-card.is-active',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'king_addons_holo_card_border',
                'selector' => '{{WRAPPER}} .king-addons-holo-card',
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();

        // === POSTER STYLE ===
        $this->start_controls_section(
            'king_addons_holo_card_section_style_poster',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Poster', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_poster_height',
            [
                'label' => esc_html__('Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 600,
                        'step' => 10,
                    ],
                    '%' => [
                        'min' => 20,
                        'max' => 150,
                    ],
                    'vh' => [
                        'min' => 10,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 260,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__poster' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_poster_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => '18',
                    'right' => '18',
                    'bottom' => '18',
                    'left' => '18',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__poster' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'king_addons_holo_card_poster_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .king-addons-holo-card__poster',
                'fields_options' => [
                    'background' => [
                        'default' => 'gradient',
                    ],
                    'color' => [
                        'default' => '#7cc7ff',
                    ],
                    'color_b' => [
                        'default' => '#c59bff',
                    ],
                    'gradient_type' => [
                        'default' => 'linear',
                    ],
                    'gradient_angle' => [
                        'default' => [
                            'unit' => 'deg',
                            'size' => 135,
                        ],
                    ],
                ],
            ]
        );

        $this->end_controls_section();

        // === PARALLAX LAYER STYLE ===
        $this->start_controls_section(
            'king_addons_holo_card_section_style_parallax',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Parallax Layer', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'king_addons_holo_card_media_type!' => 'none',
                ],
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_layer_width',
            [
                'label' => esc_html__('Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%', 'px'],
                'range' => [
                    '%' => [
                        'min' => 30,
                        'max' => 100,
                    ],
                    'px' => [
                        'min' => 100,
                        'max' => 500,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 78,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__layer' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_layer_max_width',
            [
                'label' => esc_html__('Max Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 600,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 320,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__layer' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_layer_scale',
            [
                'label' => esc_html__('Scale', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [''],
                'range' => [
                    '' => [
                        'min' => 0.5,
                        'max' => 2,
                        'step' => 0.05,
                    ],
                ],
                'default' => [
                    'unit' => '',
                    'size' => 1.03,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__layer' => '--layer-scale: {{SIZE}};',
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_layer_shadow_color',
            [
                'label' => esc_html__('Shadow Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(0, 0, 0, 0.18)',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__layer' => 'filter: drop-shadow(0 22px 34px {{VALUE}});',
                ],
            ]
        );

        $this->end_controls_section();

        // === TYPOGRAPHY STYLE ===
        $this->start_controls_section(
            'king_addons_holo_card_section_style_typography',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Typography', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'king_addons_holo_card_title_heading',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'king_addons_holo_card_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-holo-card__title span',
            ]
        );

        $this->add_control(
            'king_addons_holo_card_title_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#0c0f14',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__title span' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_meta_heading',
            [
                'label' => esc_html__('Meta', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'king_addons_holo_card_meta_typography',
                'selector' => '{{WRAPPER}} .king-addons-holo-card__meta',
            ]
        );

        $this->add_control(
            'king_addons_holo_card_meta_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#4b5563',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__meta' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_badge_heading',
            [
                'label' => esc_html__('Badge', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'king_addons_holo_card_badge_typography',
                'selector' => '{{WRAPPER}} .king-addons-holo-card__topline',
            ]
        );

        $this->add_control(
            'king_addons_holo_card_badge_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(255,255,255,0.92)',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__topline' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_badge_text_shadow',
            [
                'label' => esc_html__('Text Shadow', 'king-addons'),
                'type' => Controls_Manager::TEXT_SHADOW,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__topline' => 'text-shadow: {{HORIZONTAL}}px {{VERTICAL}}px {{BLUR}}px {{COLOR}};',
                ],
                'default' => [
                    'horizontal' => 0,
                    'vertical' => 2,
                    'blur' => 10,
                    'color' => 'rgba(0,0,0,0.15)',
                ],
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_topline_padding',
            [
                'label' => esc_html__('Topline Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default' => [
                    'top' => '12',
                    'right' => '14',
                    'bottom' => '0',
                    'left' => '14',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__topline' => 'top: {{TOP}}{{UNIT}}; right: {{RIGHT}}{{UNIT}}; left: {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // === HOLOGRAPHIC EFFECTS STYLE ===
        $this->start_controls_section(
            'king_addons_holo_card_section_style_effects',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Holographic Effects', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'king_addons_holo_card_film_enable',
            [
                'label' => esc_html__('Enable Holographic Film', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'king_addons_holo_card_film_intensity',
            [
                'label' => esc_html__('Film Intensity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [''],
                'range' => [
                    '' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.05,
                    ],
                ],
                'default' => [
                    'unit' => '',
                    'size' => 0.55,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__film' => '--film-intensity: {{SIZE}};',
                ],
                'condition' => [
                    'king_addons_holo_card_film_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_rim_enable',
            [
                'label' => esc_html__('Enable Rim Glow', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'king_addons_holo_card_rim_intensity',
            [
                'label' => esc_html__('Rim Intensity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [''],
                'range' => [
                    '' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.05,
                    ],
                ],
                'default' => [
                    'unit' => '',
                    'size' => 0.35,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card' => '--rim-intensity: {{SIZE}};',
                ],
                'condition' => [
                    'king_addons_holo_card_rim_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_poster_shine_enable',
            [
                'label' => esc_html__('Enable Poster Shine', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'king_addons_holo_card_pattern_enable',
            [
                'label' => esc_html__('Enable Pattern Overlay', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'king_addons_holo_card_pattern_opacity',
            [
                'label' => esc_html__('Pattern Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [''],
                'range' => [
                    '' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.05,
                    ],
                ],
                'default' => [
                    'unit' => '',
                    'size' => 0.22,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__pattern' => 'opacity: {{SIZE}};',
                ],
                'condition' => [
                    'king_addons_holo_card_pattern_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_title_mark_enable',
            [
                'label' => esc_html__('Enable Title Mark', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'king_addons_holo_card_badge_logo_enable',
            [
                'label' => esc_html__('Enable Badge Logo', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();

        // === TITLE MARK STYLE ===
        $this->start_controls_section(
            'king_addons_holo_card_section_style_mark',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Title Mark', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'king_addons_holo_card_title_mark_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_mark_type',
            [
                'label' => esc_html__('Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'default',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'default' => esc_html__('Default', 'king-addons'),
                    'icon' => esc_html__('Icon Library', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_mark_icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'condition' => [
                    'king_addons_holo_card_mark_type' => 'icon',
                ],
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_mark_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 8,
                        'max' => 50,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 3,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 18,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__mark' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_mark_icon_color',
            [
                'label' => esc_html__('Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'condition' => [
                    'king_addons_holo_card_mark_type' => 'icon',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__mark--icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // === BADGE LOGO STYLE ===
        $this->start_controls_section(
            'king_addons_holo_card_section_style_logo',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Badge Logo', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'king_addons_holo_card_badge_logo_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_logo_type',
            [
                'label' => esc_html__('Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'default',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'default' => esc_html__('Default', 'king-addons'),
                    'icon' => esc_html__('Icon Library', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_logo_icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'condition' => [
                    'king_addons_holo_card_logo_type' => 'icon',
                ],
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_logo_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 8,
                        'max' => 50,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 3,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 18,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__logo' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'king_addons_holo_card_logo_icon_color',
            [
                'label' => esc_html__('Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'condition' => [
                    'king_addons_holo_card_logo_type' => 'icon',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__logo--icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // === DIVIDER STYLE ===
        $this->start_controls_section(
            'king_addons_holo_card_section_style_divider',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Divider', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'king_addons_holo_card_divider_enable',
            [
                'label' => esc_html__('Show Divider', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'king_addons_holo_card_divider_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(0,0,0,0.06)',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__divider' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'king_addons_holo_card_divider_enable' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_divider_height',
            [
                'label' => esc_html__('Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 10,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 1,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__divider' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'king_addons_holo_card_divider_enable' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'king_addons_holo_card_divider_margin',
            [
                'label' => esc_html__('Margin Top', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 2,
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-holo-card__divider' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'king_addons_holo_card_divider_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Register Pro features promo section
        $this->register_pro_notice_controls();
    }

    /**
     * Register Pro notice controls with premium features promo.
     *
     * @return void
     */
    protected function register_pro_notice_controls(): void
    {
        if (function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only()) {
            return; // Pro version - skip promo
        }

        // === PRO ANIMATIONS PROMO SECTION ===
        $this->start_controls_section(
            'king_addons_holo_card_section_pro_animations',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Pro Animations', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'king_addons_holo_card_auto_sway_promo',
            [
                'label' => sprintf(__('Auto-Sway Animation %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'classes' => 'king-addons-pro-control no-distance',
                'description' => esc_html__('Card will gently sway/float automatically, simulating mouse hover movement', 'king-addons'),
            ]
        );

        $this->end_controls_section();

        // === PRO EFFECTS PROMO SECTION ===
        $this->start_controls_section(
            'king_addons_holo_card_section_pro_effects',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Pro Effects', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'king_addons_holo_card_sparkle_promo',
            [
                'label' => sprintf(__('Sparkle Effect %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'classes' => 'king-addons-pro-control no-distance',
                'description' => esc_html__('Add animated sparkle/glitter particles overlay', 'king-addons'),
            ]
        );

        $this->add_control(
            'king_addons_holo_card_rainbow_promo',
            [
                'label' => sprintf(__('Rainbow Shift %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'classes' => 'king-addons-pro-control no-distance',
                'description' => esc_html__('Animate the holographic film colors in a continuous rainbow cycle', 'king-addons'),
            ]
        );

        $this->end_controls_section();

        // === UPGRADE TO PRO SECTION ===
        Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'holographic-card', [
            'Auto-Sway Animation (5 animation types: Orbit, Figure-8, Wave, Random, Pendulum)',
            'Sparkle/Glitter Effect with customizable colors and density',
            'Rainbow Color Shift for holographic film',
        ]);
    }

    /**
     * Generate gradient/background style for mark and logo elements
     */
    protected function get_gradient_style(string $type, array $settings): string
    {
        $prefix = 'king_addons_holo_card_' . $type . '_';
        $gradient_type = $settings[$prefix . 'gradient_type'] ?? 'conic';
        $color1 = $settings[$prefix . 'color_1'] ?? '#00d4ff';
        $color2 = $settings[$prefix . 'color_2'] ?? '#ff2bd6';
        $color3 = $settings[$prefix . 'color_3'] ?? '#ffd600';
        $color4 = $settings[$prefix . 'color_4'] ?? '#00ffa8';
        $angle = isset($settings[$prefix . 'gradient_angle']['size']) ? $settings[$prefix . 'gradient_angle']['size'] : 90;
        $highlight_enable = $settings[$prefix . 'highlight_enable'] ?? 'yes';
        $highlight_color = $settings[$prefix . 'highlight_color'] ?? 'rgba(255, 255, 255, 0.85)';

        $style = '';
        $highlight = '';

        if ($highlight_enable === 'yes') {
            $highlight = "radial-gradient(circle at 30% 30%, {$highlight_color}, rgba(255, 255, 255, 0) 60%), ";
        }

        switch ($gradient_type) {
            case 'solid':
                $style = "background: {$highlight}{$color1};";
                break;
            case 'linear':
                $style = "background: {$highlight}linear-gradient({$angle}deg, {$color1}, {$color2});";
                break;
            case 'radial':
                $style = "background: {$highlight}radial-gradient(circle, {$color1}, {$color2});";
                break;
            case 'conic':
            default:
                $style = "background: {$highlight}conic-gradient(from {$angle}deg, {$color1}, {$color2}, {$color3}, {$color4}, {$color1});";
                break;
        }

        return $style;
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $id = $this->get_id();

        // Build data attributes for JS
        $data_attrs = [
            'max-tilt' => $settings['king_addons_holo_card_max_tilt']['size'] ?? 12,
            'smoothness-in' => $settings['king_addons_holo_card_smoothness_in']['size'] ?? 0.16,
            'smoothness-hover' => $settings['king_addons_holo_card_smoothness_hover']['size'] ?? 0.08,
            'smoothness-out' => $settings['king_addons_holo_card_smoothness_out']['size'] ?? 0.12,
            'parallax-strength' => $settings['king_addons_holo_card_parallax_strength']['size'] ?? 0.18,
            'disable-motion' => $settings['king_addons_holo_card_disable_motion'] === 'yes' ? 'true' : 'false',
            'respect-reduced-motion' => $settings['king_addons_holo_card_respect_reduced_motion'] === 'yes' ? 'true' : 'false',
        ];

        $data_string = '';
        foreach ($data_attrs as $key => $value) {
            $data_string .= ' data-' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        // Classes for conditional features
        $card_classes = ['king-addons-holo-card'];
        if ($settings['king_addons_holo_card_film_enable'] !== 'yes') {
            $card_classes[] = 'king-addons-holo-card--no-film';
        }
        if ($settings['king_addons_holo_card_rim_enable'] !== 'yes') {
            $card_classes[] = 'king-addons-holo-card--no-rim';
        }
        if ($settings['king_addons_holo_card_poster_shine_enable'] !== 'yes') {
            $card_classes[] = 'king-addons-holo-card--no-shine';
        }

        // Link wrapper
        $link_tag = 'article';
        $link_attrs = '';
        if ($settings['king_addons_holo_card_link_enable'] === 'yes' && !empty($settings['king_addons_holo_card_link']['url'])) {
            $link_tag = 'a';
            $link_attrs = ' href="' . esc_url($settings['king_addons_holo_card_link']['url']) . '"';
            if (!empty($settings['king_addons_holo_card_link']['is_external'])) {
                $link_attrs .= ' target="_blank"';
            }
            if (!empty($settings['king_addons_holo_card_link']['nofollow'])) {
                $link_attrs .= ' rel="nofollow"';
            }
        }

        echo '<div class="king-addons-holo-card-stage king-addons-holo-card-' . esc_attr($id) . '"' . $data_string . '>';
        echo '<' . esc_html($link_tag) . ' class="' . esc_attr(implode(' ', $card_classes)) . '"' . $link_attrs . ' aria-label="' . esc_attr__('Holographic 3D card', 'king-addons') . '">';

        // Holographic film overlay
        if ($settings['king_addons_holo_card_film_enable'] === 'yes') {
            echo '<div class="king-addons-holo-card__film" aria-hidden="true"></div>';
        }

        echo '<div class="king-addons-holo-card__content">';

        // Poster section
        echo '<div class="king-addons-holo-card__poster">';
        
        // Pattern overlay
        if ($settings['king_addons_holo_card_pattern_enable'] === 'yes') {
            echo '<div class="king-addons-holo-card__pattern" aria-hidden="true"></div>';
        }

        // Topline
        echo '<div class="king-addons-holo-card__topline">';
        echo '<span class="king-addons-holo-card__badge">';
        if ($settings['king_addons_holo_card_badge_logo_enable'] === 'yes') {
            $logo_type = $settings['king_addons_holo_card_logo_type'] ?? 'default';
            if ($logo_type === 'none') {
                // Intentionally hidden
            } else {
            $logo_classes = ['king-addons-holo-card__logo'];
            $logo_inner = '';

            if ($logo_type === 'icon') {
                $logo_classes[] = 'king-addons-holo-card__logo--icon';
                $icon = $settings['king_addons_holo_card_logo_icon'] ?? [];
                if (!empty($icon['value'])) {
                    ob_start();
                    Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']);
                    $logo_inner = (string) ob_get_clean();
                }
            }

            echo '<span class="' . esc_attr(implode(' ', $logo_classes)) . '" aria-hidden="true">' . $logo_inner . '</span>';
            }
        }
        if (!empty($settings['king_addons_holo_card_badge_text'])) {
            echo '<span>' . esc_html($settings['king_addons_holo_card_badge_text']) . '</span>';
        }
        echo '</span>';
        if (!empty($settings['king_addons_holo_card_topline_right'])) {
            echo '<span>' . esc_html($settings['king_addons_holo_card_topline_right']) . '</span>';
        }
        echo '</div>';

        // Art layer (parallax)
        if ($settings['king_addons_holo_card_media_type'] !== 'none') {
            echo '<div class="king-addons-holo-card__art" aria-hidden="true">';
            
            if ($settings['king_addons_holo_card_media_type'] === 'svg') {
                // Default SVG
                echo '<svg class="king-addons-holo-card__layer" viewBox="0 0 420 300" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="">';
                echo '<defs>';
                echo '<linearGradient id="holo-g1-' . esc_attr($id) . '" x1="0" y1="0" x2="1" y2="1">';
                echo '<stop offset="0" stop-color="#ffffff" stop-opacity=".92"/>';
                echo '<stop offset=".55" stop-color="#d7e7ff" stop-opacity=".92"/>';
                echo '<stop offset="1" stop-color="#ffe0f2" stop-opacity=".9"/>';
                echo '</linearGradient>';
                echo '<linearGradient id="holo-g2-' . esc_attr($id) . '" x1="0" y1="1" x2="1" y2="0">';
                echo '<stop offset="0" stop-color="#7dd3ff" stop-opacity=".95"/>';
                echo '<stop offset="1" stop-color="#c4b5ff" stop-opacity=".95"/>';
                echo '</linearGradient>';
                echo '<filter id="holo-s-' . esc_attr($id) . '" x="-20%" y="-20%" width="140%" height="140%">';
                echo '<feDropShadow dx="0" dy="10" stdDeviation="10" flood-color="#000" flood-opacity=".18"/>';
                echo '</filter>';
                echo '</defs>';
                echo '<g filter="url(#holo-s-' . esc_attr($id) . ')" opacity=".95">';
                echo '<path d="M84 98c8-14 28-18 40-8 10-12 30-12 40 0 18-6 34 10 28 28H70c-18 0-26-14-18-20 8-6 18-2 32 0z" fill="url(#holo-g1-' . esc_attr($id) . ')"/>';
                echo '</g>';
                echo '<g filter="url(#holo-s-' . esc_attr($id) . ')">';
                echo '<path d="M132 206c0-10 8-18 18-18h114c10 0 18 8 18 18v46H132v-46z" fill="url(#holo-g2-' . esc_attr($id) . ')"/>';
                echo '<path d="M114 252h206c6 0 10 4 10 10v8H104v-8c0-6 4-10 10-10z" fill="#ffffff" opacity=".9"/>';
                echo '<rect x="152" y="200" width="110" height="40" rx="10" fill="#ffffff" opacity=".18"/>';
                echo '</g>';
                echo '<g filter="url(#holo-s-' . esc_attr($id) . ')">';
                echo '<ellipse cx="236" cy="186" rx="64" ry="54" fill="url(#holo-g1-' . esc_attr($id) . ')" opacity=".92"/>';
                echo '<circle cx="214" cy="158" r="26" fill="url(#holo-g1-' . esc_attr($id) . ')" opacity=".95"/>';
                echo '<circle cx="266" cy="158" r="26" fill="url(#holo-g1-' . esc_attr($id) . ')" opacity=".95"/>';
                echo '<ellipse cx="240" cy="152" rx="72" ry="64" fill="url(#holo-g1-' . esc_attr($id) . ')" opacity=".95"/>';
                echo '<circle cx="214" cy="154" r="10" fill="#0c0f14" opacity=".75"/>';
                echo '<circle cx="266" cy="154" r="10" fill="#0c0f14" opacity=".75"/>';
                echo '<circle cx="210" cy="150" r="4" fill="#fff" opacity=".8"/>';
                echo '<circle cx="262" cy="150" r="4" fill="#fff" opacity=".8"/>';
                echo '<path d="M232 170c6 6 12 6 18 0" stroke="#0c0f14" stroke-opacity=".45" stroke-width="5" stroke-linecap="round"/>';
                echo '<path d="M212 120c-8-18 10-34 28-22 8-14 30-10 30 8" fill="none" stroke="#ffffff" stroke-opacity=".55" stroke-width="7" stroke-linecap="round"/>';
                echo '</g>';
                echo '<g filter="url(#holo-s-' . esc_attr($id) . ')" opacity=".85">';
                echo '<path d="M310 218l10 6-6 10-12-4-6 10-12-4 2-12-10-6 6-10 12 4 6-10 12 4-2 12z" fill="#ffffff" opacity=".85"/>';
                echo '<path d="M164 220l10 6-6 10-12-4-6 10-12-4 2-12-10-6 6-10 12 4 6-10 12 4-2 12z" fill="#ffffff" opacity=".75"/>';
                echo '</g>';
                echo '</svg>';
            } elseif ($settings['king_addons_holo_card_media_type'] === 'image' && !empty($settings['king_addons_holo_card_image']['url'])) {
                echo '<img class="king-addons-holo-card__layer" src="' . esc_url($settings['king_addons_holo_card_image']['url']) . '" alt="" />';
            } elseif ($settings['king_addons_holo_card_media_type'] === 'custom_svg' && !empty($settings['king_addons_holo_card_custom_svg'])) {
                // Sanitize SVG and add class
                $svg = $settings['king_addons_holo_card_custom_svg'];
                $svg = preg_replace('/<svg\s/', '<svg class="king-addons-holo-card__layer" ', $svg, 1);
                echo wp_kses($svg, [
                    'svg' => ['class' => true, 'viewBox' => true, 'xmlns' => true, 'role' => true, 'aria-label' => true, 'width' => true, 'height' => true, 'fill' => true],
                    'defs' => [],
                    'linearGradient' => ['id' => true, 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'gradientUnits' => true, 'gradientTransform' => true],
                    'radialGradient' => ['id' => true, 'cx' => true, 'cy' => true, 'r' => true, 'fx' => true, 'fy' => true, 'gradientUnits' => true],
                    'stop' => ['offset' => true, 'stop-color' => true, 'stop-opacity' => true, 'style' => true],
                    'filter' => ['id' => true, 'x' => true, 'y' => true, 'width' => true, 'height' => true],
                    'feDropShadow' => ['dx' => true, 'dy' => true, 'stdDeviation' => true, 'flood-color' => true, 'flood-opacity' => true],
                    'feGaussianBlur' => ['in' => true, 'stdDeviation' => true],
                    'feOffset' => ['dx' => true, 'dy' => true, 'result' => true],
                    'feBlend' => ['in' => true, 'in2' => true, 'mode' => true],
                    'g' => ['filter' => true, 'opacity' => true, 'fill' => true, 'transform' => true],
                    'path' => ['d' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'stroke-opacity' => true, 'opacity' => true, 'transform' => true],
                    'rect' => ['x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'opacity' => true, 'transform' => true],
                    'circle' => ['cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'opacity' => true, 'transform' => true],
                    'ellipse' => ['cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'opacity' => true, 'transform' => true],
                    'polygon' => ['points' => true, 'fill' => true, 'opacity' => true, 'transform' => true],
                    'polyline' => ['points' => true, 'fill' => true, 'stroke' => true, 'opacity' => true],
                    'line' => ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true],
                    'text' => ['x' => true, 'y' => true, 'fill' => true, 'font-size' => true, 'font-family' => true, 'text-anchor' => true],
                    'tspan' => ['x' => true, 'y' => true, 'dx' => true, 'dy' => true],
                    'use' => ['href' => true, 'xlink:href' => true, 'x' => true, 'y' => true, 'width' => true, 'height' => true],
                    'clipPath' => ['id' => true],
                    'mask' => ['id' => true],
                ]);
            }
            
            echo '</div>';
        }

        echo '</div>'; // End poster

        // Divider
        if ($settings['king_addons_holo_card_divider_enable'] === 'yes') {
            echo '<div class="king-addons-holo-card__divider" aria-hidden="true"></div>';
        }

        // Title
        if (!empty($settings['king_addons_holo_card_title'])) {
            echo '<div class="king-addons-holo-card__title">';
            if ($settings['king_addons_holo_card_title_mark_enable'] === 'yes') {
                $mark_type = $settings['king_addons_holo_card_mark_type'] ?? 'default';
                if ($mark_type === 'none') {
                    // Intentionally hidden
                } else {
                $mark_classes = ['king-addons-holo-card__mark'];
                $mark_inner = '';

                if ($mark_type === 'icon') {
                    $mark_classes[] = 'king-addons-holo-card__mark--icon';
                    $icon = $settings['king_addons_holo_card_mark_icon'] ?? [];
                    if (!empty($icon['value'])) {
                        ob_start();
                        Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']);
                        $mark_inner = (string) ob_get_clean();
                    }
                }

                echo '<span class="' . esc_attr(implode(' ', $mark_classes)) . '" aria-hidden="true">' . $mark_inner . '</span>';
                }
            }
            echo '<span>' . esc_html($settings['king_addons_holo_card_title']) . '</span>';
            echo '</div>';
        }

        // Meta
        if (!empty($settings['king_addons_holo_card_meta'])) {
            echo '<div class="king-addons-holo-card__meta">' . esc_html($settings['king_addons_holo_card_meta']) . '</div>';
        }

        echo '</div>'; // End content
        echo '</' . esc_html($link_tag) . '>';
        echo '</div>';
    }
}
