<?php
/**
 * Interactive Gradient Mesh Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Plugin;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders an interactive gradient mesh background with optional overlay content.
 */
class Interactive_Gradient_Mesh extends Widget_Base
{
    public function get_name(): string
    {
        return 'king-addons-interactive-gradient-mesh';
    }

    public function get_title(): string
    {
        return esc_html__('Interactive Gradient Mesh', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-interactive-gradient-mesh';
    }

    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-interactive-gradient-mesh-style',
        ];
    }

    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-interactive-gradient-mesh-script',
        ];
    }

    public function get_categories(): array
    {
        return ['king-addons'];
    }

    public function get_keywords(): array
    {
        return ['gradient', 'mesh', 'background', 'interactive', 'hero'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        public function register_controls(): void
    {
        $this->register_general_controls();
        $this->register_overlay_controls();
        $this->register_animation_controls();
        $this->register_baseline_controls();
        $this->register_style_container_controls();
        $this->register_style_overlay_controls();
        $this->register_pro_notice_controls();
    }

    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $this->render_output($settings);
    }

    protected function register_general_controls(): void
    {
        $this->start_controls_section(
            'kng_mesh_general_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('General', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_mesh_preset',
            [
                'label' => esc_html__('Preset', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'aurora',
                'options' => $this->get_preset_options(),
            ]
        );

        $this->add_control(
            'kng_mesh_base_color',
            [
                'label' => esc_html__('Base Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'description' => esc_html__('Overrides the preset base color.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_mesh_engine',
            [
                'label' => esc_html__('Engine', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto' => esc_html__('Auto', 'king-addons'),
                    'css' => esc_html__('CSS Gradients', 'king-addons'),
                    'canvas' => esc_html__('Canvas 2D', 'king-addons'),
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_mesh_height',
            [
                'label' => esc_html__('Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => ['min' => 120, 'max' => 1200],
                    'vh' => ['min' => 20, 'max' => 100],
                ],
                'default' => [
                    'size' => 420,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_mesh_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_mesh_alignment',
            [
                'label' => esc_html__('Overlay Alignment', 'king-addons'),
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
                'toggle' => false,
                'prefix_class' => 'king-addons-gradient-mesh--align-',
            ]
        );

        $this->add_control(
            'kng_mesh_vertical_align',
            [
                'label' => esc_html__('Overlay Vertical Align', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'top' => [
                        'title' => esc_html__('Top', 'king-addons'),
                        'icon' => 'eicon-v-align-top',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-v-align-middle',
                    ],
                    'bottom' => [
                        'title' => esc_html__('Bottom', 'king-addons'),
                        'icon' => 'eicon-v-align-bottom',
                    ],
                ],
                'default' => 'center',
                'toggle' => false,
                'prefix_class' => 'king-addons-gradient-mesh--valign-',
            ]
        );

        $this->add_responsive_control(
            'kng_mesh_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__overlay' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_overlay_controls(): void
    {
        $this->start_controls_section(
            'kng_mesh_overlay_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Overlay Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_mesh_show_overlay',
            [
                'label' => esc_html__('Show Overlay', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        if ($this->is_pro()) {
            $this->add_control(
                'kng_mesh_overlay_source',
                [
                    'label' => esc_html__('Overlay Source', 'king-addons'),
                    'type' => Controls_Manager::SELECT,
                    'default' => 'basic',
                    'options' => [
                        'basic' => esc_html__('Basic Content', 'king-addons'),
                        'template' => esc_html__('Elementor Template', 'king-addons'),
                    ],
                    'condition' => [
                        'kng_mesh_show_overlay' => 'yes',
                    ],
                ]
            );
        } else {
            $this->add_control(
                'kng_mesh_overlay_source',
                [
                    'type' => Controls_Manager::HIDDEN,
                    'default' => 'basic',
                ]
            );
        }

        if ($this->is_pro()) {
            $this->add_control(
                'kng_mesh_overlay_template',
                [
                    'label' => esc_html__('Choose Template', 'king-addons'),
                    'type' => Controls_Manager::SELECT2,
                    'options' => $this->get_elementor_templates_options(),
                    'label_block' => true,
                    'condition' => [
                        'kng_mesh_show_overlay' => 'yes',
                        'kng_mesh_overlay_source' => 'template',
                    ],
                ]
            );
        }

        $this->add_control(
            'kng_mesh_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Interactive Gradient Mesh', 'king-addons'),
                'label_block' => true,
                'condition' => [
                    'kng_mesh_show_overlay' => 'yes',
                    'kng_mesh_overlay_source' => 'basic',
                ],
            ]
        );

        $this->add_control(
            'kng_mesh_subtitle',
            [
                'label' => esc_html__('Subtitle', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Soft motion, premium atmosphere.', 'king-addons'),
                'label_block' => true,
                'condition' => [
                    'kng_mesh_show_overlay' => 'yes',
                    'kng_mesh_overlay_source' => 'basic',
                ],
            ]
        );

        $this->add_control(
            'kng_mesh_text',
            [
                'label' => esc_html__('Text', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 3,
                'default' => esc_html__('Use gradient meshes to add depth to heroes, highlights, and feature blocks.', 'king-addons'),
                'condition' => [
                    'kng_mesh_show_overlay' => 'yes',
                    'kng_mesh_overlay_source' => 'basic',
                ],
            ]
        );

        $this->add_control(
            'kng_mesh_button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Learn More', 'king-addons'),
                'condition' => [
                    'kng_mesh_show_overlay' => 'yes',
                    'kng_mesh_overlay_source' => 'basic',
                ],
            ]
        );

        $this->add_control(
            'kng_mesh_button_url',
            [
                'label' => esc_html__('Button Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://', 'king-addons'),
                'condition' => [
                    'kng_mesh_show_overlay' => 'yes',
                    'kng_mesh_overlay_source' => 'basic',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_animation_controls(): void
    {
        $this->start_controls_section(
            'kng_mesh_animation_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Animation', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_mesh_enable_animation',
            [
                'label' => esc_html__('Enable Animation', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_responsive_control(
            'kng_mesh_speed',
            [
                'label' => esc_html__('Speed', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['custom'],
                'range' => [
                    'custom' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => 80,
                    'unit' => 'custom',
                ],
                'condition' => [
                    'kng_mesh_enable_animation' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh' => '--kng-mesh-speed: {{SIZE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_mesh_motion',
            [
                'label' => esc_html__('Motion Style', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'drift',
                'options' => [
                    'drift' => esc_html__('Smooth Drift', 'king-addons'),
                    'pulse' => esc_html__('Slow Pulse', 'king-addons'),
                    'wave' => esc_html__('Calm Wave', 'king-addons'),
                ],
                'condition' => [
                    'kng_mesh_enable_animation' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_baseline_controls(): void
    {
        $this->start_controls_section(
            'kng_mesh_baseline_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Baseline', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'kng_mesh_saturation',
            [
                'label' => esc_html__('Saturation', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => ['min' => 50, 'max' => 160],
                ],
                'default' => [
                    'size' => 110,
                    'unit' => '%',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh' => '--kng-mesh-saturate: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_mesh_contrast',
            [
                'label' => esc_html__('Contrast', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => ['min' => 70, 'max' => 160],
                ],
                'default' => [
                    'size' => 105,
                    'unit' => '%',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh' => '--kng-mesh-contrast: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_mesh_blur',
            [
                'label' => esc_html__('Blur', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'default' => [
                    'size' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh' => '--kng-mesh-blur: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_mesh_noise',
            [
                'label' => esc_html__('Noise Layer', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_mesh_noise_opacity',
            [
                'label' => esc_html__('Noise Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['custom'],
                'range' => [
                    'custom' => ['min' => 0, 'max' => 0.4, 'step' => 0.02],
                ],
                'default' => [
                    'size' => 0.12,
                    'unit' => 'custom',
                ],
                'condition' => [
                    'kng_mesh_noise' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh' => '--kng-mesh-noise-opacity: {{SIZE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_overlay_controls(): void
    {
        $this->start_controls_section(
            'kng_mesh_style_overlay',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Overlay Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_mesh_show_overlay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_mesh_content_box_heading',
            [
                'label' => esc_html__('Content Box', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'condition' => [
                    'kng_mesh_overlay_source' => 'basic',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_mesh_content_background',
                'selector' => '{{WRAPPER}} .king-addons-gradient-mesh__content',
                'types' => ['classic', 'gradient'],
                'condition' => [
                    'kng_mesh_overlay_source' => 'basic',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_mesh_content_border',
                'selector' => '{{WRAPPER}} .king-addons-gradient-mesh__content',
                'condition' => [
                    'kng_mesh_overlay_source' => 'basic',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_mesh_content_shadow',
                'selector' => '{{WRAPPER}} .king-addons-gradient-mesh__content',
                'condition' => [
                    'kng_mesh_overlay_source' => 'basic',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_mesh_content_radius',
            [
                'label' => esc_html__('Content Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_mesh_overlay_source' => 'basic',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_mesh_content_padding',
            [
                'label' => esc_html__('Content Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_mesh_overlay_source' => 'basic',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_mesh_content_width',
            [
                'label' => esc_html__('Content Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 240, 'max' => 960],
                    '%' => ['min' => 50, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__content' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_mesh_overlay_source' => 'basic',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_mesh_content_gap',
            [
                'label' => esc_html__('Content Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__content' => 'gap: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_mesh_overlay_source' => 'basic',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_mesh_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-gradient-mesh__title',
            ]
        );

        $this->add_control(
            'kng_mesh_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_mesh_subtitle_typography',
                'selector' => '{{WRAPPER}} .king-addons-gradient-mesh__subtitle',
            ]
        );

        $this->add_control(
            'kng_mesh_subtitle_color',
            [
                'label' => esc_html__('Subtitle Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__subtitle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_mesh_text_typography',
                'selector' => '{{WRAPPER}} .king-addons-gradient-mesh__text',
            ]
        );

        $this->add_control(
            'kng_mesh_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_mesh_button_heading',
            [
                'label' => esc_html__('Button', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'kng_mesh_button_padding',
            [
                'label' => esc_html__('Button Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_mesh_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-gradient-mesh__button',
            ]
        );

        $this->add_control(
            'kng_mesh_button_color',
            [
                'label' => esc_html__('Button Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_mesh_button_background',
            [
                'label' => esc_html__('Button Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_mesh_button_border',
                'selector' => '{{WRAPPER}} .king-addons-gradient-mesh__button',
            ]
        );

        $this->add_control(
            'kng_mesh_button_hover_heading',
            [
                'label' => esc_html__('Button Hover', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_mesh_button_hover_color',
            [
                'label' => esc_html__('Hover Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_mesh_button_hover_background',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_mesh_button_hover_border_color',
            [
                'label' => esc_html__('Hover Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_mesh_button_radius',
            [
                'label' => esc_html__('Button Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 50],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-gradient-mesh__button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_mesh_button_shadow',
                'selector' => '{{WRAPPER}} .king-addons-gradient-mesh__button',
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_container_controls(): void
    {
        $this->start_controls_section(
            'kng_mesh_style_container',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Container', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_mesh_container_border',
                'selector' => '{{WRAPPER}} .king-addons-gradient-mesh',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_mesh_container_shadow',
                'selector' => '{{WRAPPER}} .king-addons-gradient-mesh',
            ]
        );

        $this->end_controls_section();
    }

    protected function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'interactive-gradient-mesh', [
                'Per-point controls (positions, colors, blend modes, motion)',
                'Scroll-driven animation with easing',
                'Export CSS (static or keyframes) with copy button',
                'Fallback image options for legacy browsers',
                'Performance modes and adaptive limits',
            ]);
        }
    }

    protected function render_output(array $settings): void
    {
        $preset = $this->get_preset($settings);
        $points = $this->get_points($settings, $preset);
        if (empty($points)) {
            return;
        }

        $mesh_payload = $this->build_mesh_payload($settings, $preset, $points);
        $mesh_data = esc_attr(wp_json_encode($mesh_payload));

        $background_css = $this->build_gradient_css($points);
        $blend_modes = $this->build_blend_modes($points);
        $base_color = $this->get_base_color($settings, $preset);

        $wrapper_classes = [
            'king-addons-gradient-mesh',
            ($settings['kng_mesh_enable_animation'] ?? 'yes') === 'yes' ? 'is-animated' : 'is-static',
            ($settings['kng_mesh_noise'] ?? '') === 'yes' ? 'has-noise' : '',
        ];

        $style_vars = $this->build_point_variables($points);

        $motion = $settings['kng_mesh_motion'] ?? 'drift';
        $engine = $settings['kng_mesh_engine'] ?? 'auto';
        $animate = ($settings['kng_mesh_enable_animation'] ?? 'yes') === 'yes' ? 'yes' : 'no';

        $wrapper_attr = sprintf(
            'class="%1$s" data-mesh="%2$s" data-motion="%3$s" data-engine="%4$s" data-animate="%5$s" style="%6$s"',
            esc_attr(trim(implode(' ', array_filter($wrapper_classes)))),
            $mesh_data,
            esc_attr($motion),
            esc_attr($engine),
            esc_attr($animate),
            esc_attr($style_vars)
        );

        $bg_attr = sprintf(
            'style="background-color:%1$s;background-image:%2$s;background-blend-mode:%3$s;"',
            esc_attr($base_color),
            esc_attr($background_css),
            esc_attr($blend_modes)
        );

        $overlay_html = $this->get_overlay_content($settings);

        ?>
        <div <?php echo $wrapper_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr__('Interactive gradient mesh', 'king-addons'); ?>">
            <div class="king-addons-gradient-mesh__bg" <?php echo $bg_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>></div>
            <canvas class="king-addons-gradient-mesh__canvas" aria-hidden="true"></canvas>
            <?php if (!empty($overlay_html)) : ?>
                <div class="king-addons-gradient-mesh__overlay">
                    <?php echo $overlay_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endif; ?>
            <?php $this->render_export_panel($settings, $preset, $points); ?>
        </div>
        <?php
    }

    protected function get_overlay_content(array $settings): string
    {
        if (($settings['kng_mesh_show_overlay'] ?? 'yes') !== 'yes') {
            return '';
        }

        if (($settings['kng_mesh_overlay_source'] ?? 'basic') === 'template' && $this->is_pro()) {
            $template_id = absint($settings['kng_mesh_overlay_template'] ?? 0);
            if ($template_id) {
                $template_content = $this->get_template_content($template_id);
                if ($template_content !== '') {
                    return '<div class="king-addons-gradient-mesh__template">' . $template_content . '</div>';
                }
            }
        }

        $title = trim((string) ($settings['kng_mesh_title'] ?? ''));
        $subtitle = trim((string) ($settings['kng_mesh_subtitle'] ?? ''));
        $text = trim((string) ($settings['kng_mesh_text'] ?? ''));
        $button_text = trim((string) ($settings['kng_mesh_button_text'] ?? ''));
        $button_url = $settings['kng_mesh_button_url'] ?? [];

        if ($title === '' && $subtitle === '' && $text === '' && $button_text === '') {
            return '';
        }

        $button_html = '';
        if ($button_text !== '') {
            $url = !empty($button_url['url']) ? esc_url($button_url['url']) : '#';
            $target = !empty($button_url['is_external']) ? ' target="_blank"' : '';
            $nofollow = !empty($button_url['nofollow']) ? ' rel="nofollow"' : '';
            $button_html = sprintf(
                '<a class="king-addons-gradient-mesh__button" href="%1$s"%2$s%3$s>%4$s</a>',
                $url,
                $target,
                $nofollow,
                esc_html($button_text)
            );
        }

        ob_start();
        ?>
        <div class="king-addons-gradient-mesh__content">
            <?php if ($subtitle !== '') : ?>
                <div class="king-addons-gradient-mesh__subtitle"><?php echo esc_html($subtitle); ?></div>
            <?php endif; ?>
            <?php if ($title !== '') : ?>
                <h3 class="king-addons-gradient-mesh__title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            <?php if ($text !== '') : ?>
                <div class="king-addons-gradient-mesh__text"><?php echo esc_html($text); ?></div>
            <?php endif; ?>
            <?php if ($button_html !== '') : ?>
                <div class="king-addons-gradient-mesh__actions">
                    <?php echo $button_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    protected function render_export_panel(array $settings, array $preset, array $points): void
    {
        if (!$this->is_pro()) {
            return;
        }

        if (!class_exists(Plugin::class) || !Plugin::$instance->editor->is_edit_mode()) {
            return;
        }

        $css = $this->build_export_css($settings, $preset, $points);
        if ($css === '') {
            return;
        }
        ?>
        <div class="king-addons-gradient-mesh__export" data-export-css="<?php echo esc_attr($css); ?>">
            <button type="button" class="king-addons-gradient-mesh__export-copy">
                <?php echo esc_html__('Copy CSS', 'king-addons'); ?>
            </button>
            <textarea class="king-addons-gradient-mesh__export-text" rows="6" readonly><?php echo esc_textarea($css); ?></textarea>
        </div>
        <?php
    }

    protected function build_mesh_payload(array $settings, array $preset, array $points): array
    {
        $speed = (int) ($settings['kng_mesh_speed']['size'] ?? 80);
        $payload_points = [];

        foreach ($points as $point) {
            $payload_points[] = [
                'x' => (float) $point['x'],
                'y' => (float) $point['y'],
                'radius' => (float) $point['radius'],
                'intensity' => (float) $point['intensity'],
                'feather' => (float) $point['feather'],
                'color' => $point['color'],
                'blend' => $point['blend'] ?? 'screen',
                'motion' => $point['motion'] ?? 'drift',
                'amplitude' => (float) ($point['amplitude'] ?? 18),
                'frequency' => (float) ($point['frequency'] ?? 0.25),
                'phase' => (float) ($point['phase'] ?? 0),
                'direction' => (float) ($point['direction'] ?? 0),
                'speed' => (float) ($point['speed'] ?? 1),
            ];
        }

        return [
            'engine' => $settings['kng_mesh_engine'] ?? 'auto',
            'animate' => ($settings['kng_mesh_enable_animation'] ?? 'yes') === 'yes',
            'motion' => $settings['kng_mesh_motion'] ?? 'drift',
            'speed' => $speed,
            'preset' => $settings['kng_mesh_preset'] ?? 'aurora',
            'points' => $payload_points,
            'fallback' => [
                'color' => $this->get_base_color($settings, $preset),
            ],
        ];
    }

    protected function build_export_css(array $settings, array $preset, array $points): string
    {
        return '';
    }

    protected function get_points(array $settings, array $preset): array
    {
        return $this->normalize_points($preset['points'] ?? []);
    }

    protected function get_base_color(array $settings, array $preset): string
    {
        $custom = trim((string) ($settings['kng_mesh_base_color'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }
        return $preset['base'] ?? '#0f172a';
    }

    protected function get_preset(array $settings): array
    {
        $presets = $this->get_presets();
        $preset_key = $settings['kng_mesh_preset'] ?? 'aurora';

        return $presets[$preset_key] ?? reset($presets);
    }

    protected function get_preset_options(): array
    {
        $options = [];
        foreach ($this->get_presets() as $key => $preset) {
            $options[$key] = $preset['label'];
        }

        return $options;
    }

    protected function get_presets(): array
    {
        return [
            'aurora' => [
                'label' => esc_html__('Aurora Mist', 'king-addons'),
                'base' => '#0b1020',
                'points' => [
                    ['color' => '#7fffd4', 'x' => 18, 'y' => 22, 'radius' => 260, 'intensity' => 0.85, 'feather' => 55, 'blend' => 'screen'],
                    ['color' => '#60a5fa', 'x' => 72, 'y' => 18, 'radius' => 240, 'intensity' => 0.8, 'feather' => 60, 'blend' => 'screen'],
                    ['color' => '#a78bfa', 'x' => 35, 'y' => 70, 'radius' => 300, 'intensity' => 0.75, 'feather' => 70, 'blend' => 'screen'],
                    ['color' => '#22d3ee', 'x' => 80, 'y' => 72, 'radius' => 220, 'intensity' => 0.7, 'feather' => 65, 'blend' => 'screen'],
                ],
            ],
            'sunset' => [
                'label' => esc_html__('Sunset Bloom', 'king-addons'),
                'base' => '#190d1c',
                'points' => [
                    ['color' => '#fb7185', 'x' => 20, 'y' => 30, 'radius' => 280, 'intensity' => 0.9, 'feather' => 60, 'blend' => 'screen'],
                    ['color' => '#fbbf24', 'x' => 70, 'y' => 24, 'radius' => 230, 'intensity' => 0.7, 'feather' => 55, 'blend' => 'screen'],
                    ['color' => '#f472b6', 'x' => 36, 'y' => 72, 'radius' => 300, 'intensity' => 0.8, 'feather' => 70, 'blend' => 'screen'],
                    ['color' => '#f97316', 'x' => 78, 'y' => 68, 'radius' => 210, 'intensity' => 0.65, 'feather' => 65, 'blend' => 'screen'],
                ],
            ],
            'mint' => [
                'label' => esc_html__('Mint Fog', 'king-addons'),
                'base' => '#061318',
                'points' => [
                    ['color' => '#5eead4', 'x' => 22, 'y' => 26, 'radius' => 260, 'intensity' => 0.8, 'feather' => 60, 'blend' => 'screen'],
                    ['color' => '#22d3ee', 'x' => 68, 'y' => 22, 'radius' => 240, 'intensity' => 0.75, 'feather' => 60, 'blend' => 'screen'],
                    ['color' => '#34d399', 'x' => 36, 'y' => 70, 'radius' => 310, 'intensity' => 0.7, 'feather' => 72, 'blend' => 'screen'],
                    ['color' => '#38bdf8', 'x' => 76, 'y' => 74, 'radius' => 220, 'intensity' => 0.65, 'feather' => 68, 'blend' => 'screen'],
                ],
            ],
            'twilight' => [
                'label' => esc_html__('Twilight Drift', 'king-addons'),
                'base' => '#0f0b1f',
                'points' => [
                    ['color' => '#818cf8', 'x' => 24, 'y' => 24, 'radius' => 250, 'intensity' => 0.75, 'feather' => 62, 'blend' => 'screen'],
                    ['color' => '#f472b6', 'x' => 70, 'y' => 28, 'radius' => 260, 'intensity' => 0.7, 'feather' => 60, 'blend' => 'screen'],
                    ['color' => '#22d3ee', 'x' => 32, 'y' => 74, 'radius' => 310, 'intensity' => 0.68, 'feather' => 72, 'blend' => 'screen'],
                    ['color' => '#f97316', 'x' => 78, 'y' => 70, 'radius' => 220, 'intensity' => 0.6, 'feather' => 68, 'blend' => 'screen'],
                ],
            ],
            'dusk' => [
                'label' => esc_html__('Deep Dusk', 'king-addons'),
                'base' => '#0b1120',
                'points' => [
                    ['color' => '#38bdf8', 'x' => 22, 'y' => 18, 'radius' => 240, 'intensity' => 0.7, 'feather' => 60, 'blend' => 'screen'],
                    ['color' => '#f472b6', 'x' => 70, 'y' => 20, 'radius' => 250, 'intensity' => 0.65, 'feather' => 58, 'blend' => 'screen'],
                    ['color' => '#34d399', 'x' => 30, 'y' => 74, 'radius' => 300, 'intensity' => 0.6, 'feather' => 72, 'blend' => 'screen'],
                    ['color' => '#fbbf24', 'x' => 78, 'y' => 72, 'radius' => 210, 'intensity' => 0.55, 'feather' => 68, 'blend' => 'screen'],
                ],
            ],
            'monochrome' => [
                'label' => esc_html__('Monochrome Glow', 'king-addons'),
                'base' => '#0a0a0f',
                'points' => [
                    ['color' => '#f9fafb', 'x' => 30, 'y' => 24, 'radius' => 260, 'intensity' => 0.4, 'feather' => 60, 'blend' => 'screen'],
                    ['color' => '#94a3b8', 'x' => 72, 'y' => 28, 'radius' => 240, 'intensity' => 0.35, 'feather' => 58, 'blend' => 'screen'],
                    ['color' => '#e2e8f0', 'x' => 40, 'y' => 72, 'radius' => 300, 'intensity' => 0.32, 'feather' => 70, 'blend' => 'screen'],
                    ['color' => '#cbd5f5', 'x' => 78, 'y' => 72, 'radius' => 220, 'intensity' => 0.3, 'feather' => 66, 'blend' => 'screen'],
                ],
            ],
        ];
    }

    protected function build_point_variables(array $points): string
    {
        $vars = [];
        $index = 1;

        foreach ($points as $point) {
            $color = $this->to_rgba($point['color'], $point['intensity']);
            $fade = $this->to_rgba($point['color'], 0);
            $solid_radius = max(0, $point['radius'] - ($point['radius'] * ($point['feather'] / 100)));

            $vars[] = sprintf('--kng-mesh-p%1$d-x:%2$s%%', $index, $point['x']);
            $vars[] = sprintf('--kng-mesh-p%1$d-y:%2$s%%', $index, $point['y']);
            $vars[] = sprintf('--kng-mesh-p%1$d-color:%2$s', $index, $color);
            $vars[] = sprintf('--kng-mesh-p%1$d-fade:%2$s', $index, $fade);
            $vars[] = sprintf('--kng-mesh-p%1$d-solid:%2$spx', $index, round($solid_radius, 2));
            $vars[] = sprintf('--kng-mesh-p%1$d-radius:%2$spx', $index, $point['radius']);
            $index++;
        }

        return implode(';', $vars) . ';';
    }

    protected function build_gradient_css(array $points): string
    {
        $layers = [];
        $index = 1;

        foreach ($points as $point) {
            $layers[] = sprintf(
                'radial-gradient(circle at var(--kng-mesh-p%1$d-x) var(--kng-mesh-p%1$d-y), var(--kng-mesh-p%1$d-color) 0px, var(--kng-mesh-p%1$d-color) var(--kng-mesh-p%1$d-solid), var(--kng-mesh-p%1$d-fade) var(--kng-mesh-p%1$d-radius))',
                $index
            );
            $index++;
        }

        return implode(', ', $layers);
    }

    protected function build_blend_modes(array $points): string
    {
        $modes = [];
        foreach ($points as $point) {
            $modes[] = $point['blend'] ?? 'screen';
        }

        return implode(', ', $modes);
    }

    protected function to_rgba(string $color, float $alpha): string
    {
        $alpha = max(0, min(1, $alpha));
        $color = trim($color);

        if (stripos($color, 'rgba(') === 0) {
            if (preg_match('/rgba\\(\\s*([\\d.]+)\\s*,\\s*([\\d.]+)\\s*,\\s*([\\d.]+)\\s*,\\s*([\\d.]+)\\s*\\)/i', $color, $matches)) {
                return sprintf('rgba(%d, %d, %d, %.3f)', (int) $matches[1], (int) $matches[2], (int) $matches[3], $alpha);
            }
            return $color;
        }

        if (stripos($color, 'rgb(') === 0) {
            if (preg_match('/rgb\\(\\s*([\\d.]+)\\s*,\\s*([\\d.]+)\\s*,\\s*([\\d.]+)\\s*\\)/i', $color, $matches)) {
                return sprintf('rgba(%d, %d, %d, %.3f)', (int) $matches[1], (int) $matches[2], (int) $matches[3], $alpha);
            }
            return $color;
        }

        $hex = ltrim($color, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6) {
            return $color;
        }

        $rgb = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];

        return sprintf('rgba(%d, %d, %d, %.3f)', $rgb[0], $rgb[1], $rgb[2], $alpha);
    }

    /**
     * Normalize points input to safe defaults.
     *
     * @param array $points
     * @return array
     */
    protected function normalize_points(array $points): array
    {
        $normalized = [];
        $limit = 10;

        foreach ($points as $point) {
            if (count($normalized) >= $limit) {
                break;
            }
            if (!is_array($point)) {
                continue;
            }
            $normalized[] = $this->normalize_point($point);
        }

        return $normalized;
    }

    /**
     * Normalize a single point definition.
     *
     * @param array $point
     * @return array
     */
    protected function normalize_point(array $point): array
    {
        $defaults = [
            'color' => '#ffffff',
            'x' => 50,
            'y' => 50,
            'radius' => 240,
            'intensity' => 0.8,
            'feather' => 60,
            'blend' => 'screen',
            'motion' => 'drift',
            'amplitude' => 18,
            'frequency' => 0.25,
            'phase' => 0,
            'direction' => 0,
            'speed' => 1,
        ];

        $color = $this->normalize_color((string) ($point['color'] ?? $defaults['color']), $defaults['color']);
        $blend = strtolower((string) ($point['blend'] ?? $defaults['blend']));
        $blend = in_array($blend, ['normal', 'screen', 'overlay', 'soft-light'], true) ? $blend : $defaults['blend'];
        $motion = strtolower((string) ($point['motion'] ?? $defaults['motion']));
        $motion = in_array($motion, ['static', 'drift', 'orbit', 'noise'], true) ? $motion : $defaults['motion'];

        return [
            'color' => $color,
            'x' => $this->clamp_value($point['x'] ?? $defaults['x'], 0, 100, $defaults['x']),
            'y' => $this->clamp_value($point['y'] ?? $defaults['y'], 0, 100, $defaults['y']),
            'radius' => $this->clamp_value($point['radius'] ?? $defaults['radius'], 80, 600, $defaults['radius']),
            'intensity' => $this->clamp_value($point['intensity'] ?? $defaults['intensity'], 0, 1, $defaults['intensity']),
            'feather' => $this->clamp_value($point['feather'] ?? $defaults['feather'], 0, 100, $defaults['feather']),
            'blend' => $blend,
            'motion' => $motion,
            'amplitude' => $this->clamp_value($point['amplitude'] ?? $defaults['amplitude'], 0, 80, $defaults['amplitude']),
            'frequency' => $this->clamp_value($point['frequency'] ?? $defaults['frequency'], 0, 2, $defaults['frequency']),
            'phase' => $this->clamp_value($point['phase'] ?? $defaults['phase'], 0, 360, $defaults['phase']),
            'direction' => $this->clamp_value($point['direction'] ?? $defaults['direction'], -180, 180, $defaults['direction']),
            'speed' => $this->clamp_value($point['speed'] ?? $defaults['speed'], 0.1, 3, $defaults['speed']),
        ];
    }

    /**
     * Normalize color input.
     *
     * @param string $color
     * @param string $fallback
     * @return string
     */
    protected function normalize_color(string $color, string $fallback): string
    {
        $color = trim($color);
        if ($color === '') {
            return $fallback;
        }
        if (stripos($color, 'rgba(') === 0 || stripos($color, 'rgb(') === 0) {
            return $color;
        }
        $hex = sanitize_hex_color($color);
        if (!empty($hex)) {
            return $hex;
        }
        return $fallback;
    }

    /**
     * Clamp numeric values safely.
     *
     * @param mixed $value
     * @param float $min
     * @param float $max
     * @param float $fallback
     * @return float
     */
    protected function clamp_value($value, float $min, float $max, float $fallback): float
    {
        if (!is_numeric($value)) {
            return $fallback;
        }
        $value = (float) $value;
        if ($value < $min) {
            return $min;
        }
        if ($value > $max) {
            return $max;
        }
        return $value;
    }

    /**
     * Get Elementor template content.
     *
     * @param int $template_id
     * @return string
     */
    protected function get_template_content(int $template_id): string
    {
        if ($template_id <= 0 || !class_exists(Plugin::class)) {
            return '';
        }

        $has_css = 'internal' === get_option('elementor_css_print_method');
        return Plugin::instance()->frontend->get_builder_content_for_display($template_id, $has_css);
    }

    /**
     * Get Elementor templates options.
     *
     * @return array<string, string>
     */
    protected function get_elementor_templates_options(): array
    {
        if (!class_exists(Plugin::class)) {
            return [];
        }

        $options = [];
        $templates = Plugin::$instance->templates_manager->get_source('local')->get_items();
        if (!empty($templates)) {
            foreach ($templates as $template) {
                $options[$template['template_id']] = $template['title'];
            }
        }

        return $options;
    }

    protected function is_pro(): bool
    {
        return false;
    }
}
