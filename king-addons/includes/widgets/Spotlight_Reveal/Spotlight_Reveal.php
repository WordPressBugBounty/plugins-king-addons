<?php
/**
 * Spotlight Reveal Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Spotlight Reveal widget.
 */
class Spotlight_Reveal extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-spotlight-reveal';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Spotlight Reveal', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-spotlight-reveal';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-spotlight-reveal-style',
        ];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-spotlight-reveal-script',
        ];
    }

    /**
     * Categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['spotlight', 'reveal', 'mask', 'hover', 'scroll', 'interactive'];
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
    public function register_controls(): void
    {
        $this->register_content_controls();
        $this->register_mask_controls();
        $this->register_trigger_controls();
        $this->register_style_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();

        $trigger = $this->sanitize_trigger($settings['kng_trigger'] ?? 'cursor');
        $fallback = $this->sanitize_fallback($settings['kng_fallback_mode'] ?? 'static');
        $interaction_layer = $this->sanitize_interaction_layer($settings['kng_interaction_layer'] ?? 'base');
        $dim_base = ($settings['kng_dim_base'] ?? '') === 'yes';
        $show_reveal_in_editor = ($settings['kng_editor_show_reveal'] ?? 'yes') === 'yes';

        $start_position = $settings['kng_start_position'] ?? 'center';
        $start_x = (float) ($settings['kng_start_x']['size'] ?? 50);
        $start_y = (float) ($settings['kng_start_y']['size'] ?? 50);

        if ('custom' !== $start_position) {
            $start_x = 50;
            $start_y = 50;
        }

        $options = [
            'trigger' => $trigger,
            'smoothing' => (float) ($settings['kng_cursor_smoothing'] ?? 0.2),
            'hoverOnly' => ($settings['kng_cursor_hover_only'] ?? 'yes') === 'yes',
            'constrain' => ($settings['kng_mask_constrain'] ?? 'yes') === 'yes',
            'startX' => $start_x,
            'startY' => $start_y,
            'scrollStart' => (float) ($settings['kng_scroll_start'] ?? 0),
            'scrollEnd' => (float) ($settings['kng_scroll_end'] ?? 0),
        ];

        $wrapper_classes = [
            'king-addons-spotlight-reveal',
            'king-addons-spotlight-reveal--trigger-' . $trigger,
            'king-addons-spotlight-reveal--fallback-' . $fallback,
            'king-addons-spotlight-reveal--interaction-' . $interaction_layer,
        ];
        if ($dim_base) {
            $wrapper_classes[] = 'king-addons-spotlight-reveal--dim-base';
        }
        if (!$show_reveal_in_editor) {
            $wrapper_classes[] = 'king-addons-spotlight-reveal--editor-hide';
        }

        $this->add_render_attribute('wrapper', [
            'class' => $wrapper_classes,
            'data-options' => wp_json_encode($options),
            'style' => sprintf('--ka-spotlight-x: %s%%; --ka-spotlight-y: %s%%;', $start_x, $start_y),
        ]);

        $base_title = (string) ($settings['kng_base_title'] ?? '');
        $base_description = (string) ($settings['kng_base_description'] ?? '');
        $reveal_title = (string) ($settings['kng_reveal_title'] ?? '');
        $reveal_description = (string) ($settings['kng_reveal_description'] ?? '');

        $has_base_text = ('' !== trim($base_title)) || ('' !== trim($base_description));
        $has_reveal_text = ('' !== trim($reveal_title)) || ('' !== trim($reveal_description));

        $reveal_hidden = ($settings['kng_reveal_aria_hidden'] ?? 'yes') === 'yes' && 'base' === $interaction_layer;
        $reveal_attrs = $reveal_hidden ? ' aria-hidden="true" inert' : '';

        echo '<div ' . $this->get_render_attribute_string('wrapper') . '>';
        echo '<div class="king-addons-spotlight-reveal__layer king-addons-spotlight-reveal__layer--base">';
        if ($has_base_text) {
            echo '<div class="king-addons-spotlight-reveal__content">';
            echo '<div class="king-addons-spotlight-reveal__text">';
            if ('' !== trim($base_title)) {
                echo '<h3 class="king-addons-spotlight-reveal__title">' . esc_html($base_title) . '</h3>';
            }
            if ('' !== trim($base_description)) {
                echo '<div class="king-addons-spotlight-reveal__description">' . wp_kses_post(wpautop($base_description)) . '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '<div class="king-addons-spotlight-reveal__layer king-addons-spotlight-reveal__layer--reveal"' . $reveal_attrs . '>';
        if ($has_reveal_text) {
            echo '<div class="king-addons-spotlight-reveal__content">';
            echo '<div class="king-addons-spotlight-reveal__text">';
            if ('' !== trim($reveal_title)) {
                echo '<h3 class="king-addons-spotlight-reveal__title">' . esc_html($reveal_title) . '</h3>';
            }
            if ('' !== trim($reveal_description)) {
                echo '<div class="king-addons-spotlight-reveal__description">' . wp_kses_post(wpautop($reveal_description)) . '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '<div class="king-addons-spotlight-reveal__overlay" aria-hidden="true"></div>';
        echo '</div>';
    }

    /**
     * Content controls.
     *
     * @return void
     */
    protected function register_content_controls(): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_base_title',
            [
                'label' => esc_html__('Base Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Base Layer', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'kng_base_description',
            [
                'label' => esc_html__('Base Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Use this content as the always-visible layer.', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'kng_reveal_title',
            [
                'label' => esc_html__('Reveal Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Reveal Layer', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'kng_reveal_description',
            [
                'label' => esc_html__('Reveal Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('The spotlight reveals this content.', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'kng_interaction_layer',
            [
                'label' => esc_html__('Interactive Layer', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'base',
                'options' => [
                    'base' => esc_html__('Base Layer', 'king-addons'),
                    'reveal' => esc_html__('Reveal Layer', 'king-addons'),
                ],
                'description' => esc_html__('Choose which layer receives pointer interactions.', 'king-addons'),
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_reveal_aria_hidden',
            [
                'label' => esc_html__('Hide Reveal Layer From Screen Readers', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_interaction_layer' => 'base',
                ],
            ]
        );

        $this->add_control(
            'kng_base_background_heading',
            [
                'label' => esc_html__('Base Background', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_base_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .king-addons-spotlight-reveal__layer--base',
            ]
        );

        $this->add_control(
            'kng_reveal_background_heading',
            [
                'label' => esc_html__('Reveal Background', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_reveal_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .king-addons-spotlight-reveal__layer--reveal',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Mask controls.
     *
     * @return void
     */
    protected function register_mask_controls(): void
    {
        $this->start_controls_section(
            'kng_mask_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Mask', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'kng_mask_size',
            [
                'label' => esc_html__('Mask Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vw'],
                'range' => [
                    'px' => [
                        'min' => 60,
                        'max' => 600,
                    ],
                    'vw' => [
                        'min' => 10,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'size' => 260,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_mask_softness',
            [
                'label' => esc_html__('Edge Softness', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 200,
                    ],
                ],
                'default' => [
                    'size' => 32,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-softness: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_start_position',
            [
                'label' => esc_html__('Start Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'center',
                'options' => [
                    'center' => esc_html__('Center', 'king-addons'),
                    'custom' => esc_html__('Custom', 'king-addons'),
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_start_x',
            [
                'label' => esc_html__('Start X (%)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 50,
                    'unit' => '%',
                ],
                'condition' => [
                    'kng_start_position' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'kng_start_y',
            [
                'label' => esc_html__('Start Y (%)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 50,
                    'unit' => '%',
                ],
                'condition' => [
                    'kng_start_position' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'kng_mask_constrain',
            [
                'label' => esc_html__('Constrain Within Container', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Trigger controls.
     *
     * @return void
     */
    protected function register_trigger_controls(): void
    {
        $this->start_controls_section(
            'kng_trigger_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Trigger', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_trigger',
            [
                'label' => esc_html__('Reveal Trigger', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'cursor',
                'options' => [
                    'cursor' => esc_html__('Follow Cursor', 'king-addons'),
                    'scroll' => esc_html__('Scroll Progress', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_cursor_heading',
            [
                'label' => esc_html__('Cursor Follow', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'kng_trigger' => 'cursor',
                ],
            ]
        );

        $this->add_control(
            'kng_cursor_smoothing',
            [
                'label' => esc_html__('Smoothing', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 1,
                'step' => 0.05,
                'default' => 0.2,
                'condition' => [
                    'kng_trigger' => 'cursor',
                ],
            ]
        );

        $this->add_control(
            'kng_cursor_hover_only',
            [
                'label' => esc_html__('Activate on Hover Only', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_trigger' => 'cursor',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_heading',
            [
                'label' => esc_html__('Scroll Progress', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'kng_trigger' => 'scroll',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_start',
            [
                'label' => esc_html__('Start Offset (%)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 100,
                'step' => 1,
                'default' => 0,
                'condition' => [
                    'kng_trigger' => 'scroll',
                ],
                'description' => esc_html__('Viewport offset before the reveal starts.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_scroll_end',
            [
                'label' => esc_html__('End Offset (%)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 100,
                'step' => 1,
                'default' => 0,
                'condition' => [
                    'kng_trigger' => 'scroll',
                ],
                'description' => esc_html__('Viewport offset after the reveal ends.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_editor_show_reveal',
            [
                'label' => esc_html__('Show Reveal Layer in Editor', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_fallback_mode',
            [
                'label' => esc_html__('Fallback When JS Is Disabled', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'static',
                'options' => [
                    'static' => esc_html__('Static Spotlight', 'king-addons'),
                    'visible' => esc_html__('Reveal Fully Visible', 'king-addons'),
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style controls.
     *
     * @return void
     */
    protected function register_style_controls(): void
    {
        $this->start_controls_section(
            'kng_style_container_section',
            [
                'label' => esc_html__('Container', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_container_min_height',
            [
                'label' => esc_html__('Min Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 120,
                        'max' => 1000,
                    ],
                    'vh' => [
                        'min' => 20,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 320,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => 'min-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_container_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_container_border',
                'selector' => '{{WRAPPER}} .king-addons-spotlight-reveal',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_container_shadow',
                'selector' => '{{WRAPPER}} .king-addons-spotlight-reveal',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_base_section',
            [
                'label' => esc_html__('Base Layer', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_base_opacity',
            [
                'label' => esc_html__('Opacity', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 1,
                'step' => 0.05,
                'default' => 1,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-base-opacity: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_base_title_heading',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_base_title_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal__layer--base .king-addons-spotlight-reveal__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_base_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-spotlight-reveal__layer--base .king-addons-spotlight-reveal__title',
            ]
        );

        $this->add_responsive_control(
            'kng_base_content_width',
            [
                'label' => esc_html__('Content Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => [
                        'min' => 120,
                        'max' => 1200,
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
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-base-content-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_base_description_heading',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_base_description_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal__layer--base .king-addons-spotlight-reveal__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_base_description_typography',
                'selector' => '{{WRAPPER}} .king-addons-spotlight-reveal__layer--base .king-addons-spotlight-reveal__description',
            ]
        );

        $this->add_responsive_control(
            'kng_base_title_description_gap',
            [
                'label' => esc_html__('Title/Description Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 120,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                    'rem' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-base-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_base_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-base-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_base_text_align',
            [
                'label' => esc_html__('Text Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'toggle' => true,
                'default' => 'center',
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
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-base-text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_base_horizontal_align',
            [
                'label' => esc_html__('Horizontal Position', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'toggle' => true,
                'default' => 'center',
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'selectors_dictionary' => [
                    'left' => 'flex-start',
                    'center' => 'center',
                    'right' => 'flex-end',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-base-justify: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_base_vertical_align',
            [
                'label' => esc_html__('Vertical Position', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'toggle' => true,
                'default' => 'middle',
                'options' => [
                    'top' => [
                        'title' => esc_html__('Top', 'king-addons'),
                        'icon' => 'eicon-v-align-top',
                    ],
                    'middle' => [
                        'title' => esc_html__('Middle', 'king-addons'),
                        'icon' => 'eicon-v-align-middle',
                    ],
                    'bottom' => [
                        'title' => esc_html__('Bottom', 'king-addons'),
                        'icon' => 'eicon-v-align-bottom',
                    ],
                ],
                'selectors_dictionary' => [
                    'top' => 'flex-start',
                    'middle' => 'center',
                    'bottom' => 'flex-end',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-base-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_dim_base',
            [
                'label' => esc_html__('Dim Base On Reveal', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_dim_base_opacity',
            [
                'label' => esc_html__('Dim Opacity', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 1,
                'step' => 0.05,
                'default' => 0.6,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-base-dim-opacity: {{VALUE}};',
                ],
                'condition' => [
                    'kng_dim_base' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_reveal_section',
            [
                'label' => esc_html__('Reveal Layer', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_reveal_opacity',
            [
                'label' => esc_html__('Opacity', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 1,
                'step' => 0.05,
                'default' => 1,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-reveal-opacity: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_reveal_title_heading',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_reveal_title_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal__layer--reveal .king-addons-spotlight-reveal__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_reveal_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-spotlight-reveal__layer--reveal .king-addons-spotlight-reveal__title',
            ]
        );

        $this->add_responsive_control(
            'kng_reveal_content_width',
            [
                'label' => esc_html__('Content Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => [
                        'min' => 120,
                        'max' => 1200,
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
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-reveal-content-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_reveal_description_heading',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_reveal_description_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal__layer--reveal .king-addons-spotlight-reveal__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_reveal_description_typography',
                'selector' => '{{WRAPPER}} .king-addons-spotlight-reveal__layer--reveal .king-addons-spotlight-reveal__description',
            ]
        );

        $this->add_responsive_control(
            'kng_reveal_title_description_gap',
            [
                'label' => esc_html__('Title/Description Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 120,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                    'rem' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-reveal-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Css_Filter::get_type(),
            [
                'name' => 'kng_reveal_filters',
                'selector' => '{{WRAPPER}} .king-addons-spotlight-reveal__layer--reveal',
            ]
        );

        $this->add_responsive_control(
            'kng_reveal_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-reveal-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_reveal_text_align',
            [
                'label' => esc_html__('Text Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'toggle' => true,
                'default' => 'center',
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
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-reveal-text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_reveal_horizontal_align',
            [
                'label' => esc_html__('Horizontal Position', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'toggle' => true,
                'default' => 'center',
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'selectors_dictionary' => [
                    'left' => 'flex-start',
                    'center' => 'center',
                    'right' => 'flex-end',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-reveal-justify: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_reveal_vertical_align',
            [
                'label' => esc_html__('Vertical Position', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'toggle' => true,
                'default' => 'middle',
                'options' => [
                    'top' => [
                        'title' => esc_html__('Top', 'king-addons'),
                        'icon' => 'eicon-v-align-top',
                    ],
                    'middle' => [
                        'title' => esc_html__('Middle', 'king-addons'),
                        'icon' => 'eicon-v-align-middle',
                    ],
                    'bottom' => [
                        'title' => esc_html__('Bottom', 'king-addons'),
                        'icon' => 'eicon-v-align-bottom',
                    ],
                ],
                'selectors_dictionary' => [
                    'top' => 'flex-start',
                    'middle' => 'center',
                    'bottom' => 'flex-end',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-reveal-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_overlay_section',
            [
                'label' => esc_html__('Overlay', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_overlay_color',
            [
                'label' => esc_html__('Tint Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-overlay-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_overlay_opacity',
            [
                'label' => esc_html__('Opacity', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 1,
                'step' => 0.05,
                'default' => 0.65,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-spotlight-reveal' => '--ka-spotlight-overlay-opacity: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register Pro notice controls.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_can_use_pro()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'spotlight-reveal', [
                'Ellipse, rectangle, beam, and SVG mask shapes',
                'Blur inside/outside mask and edge blur controls',
                'Blend modes, reveal intensity, and noise overlays',
                'Touch drag spotlight and swipe-to-reveal modes',
                'Advanced scroll paths, snaps, and template reveals',
            ]);
        }
    }

    /**
     * Sanitize trigger value.
     *
     * @param string $value Trigger value.
     *
     * @return string
     */
    protected function sanitize_trigger(string $value): string
    {
        $allowed = ['cursor', 'scroll'];
        return in_array($value, $allowed, true) ? $value : 'cursor';
    }

    /**
     * Sanitize fallback value.
     *
     * @param string $value Fallback value.
     *
     * @return string
     */
    protected function sanitize_fallback(string $value): string
    {
        $allowed = ['static', 'visible'];
        return in_array($value, $allowed, true) ? $value : 'static';
    }

    /**
     * Sanitize interaction layer.
     *
     * @param string $value Interaction layer.
     *
     * @return string
     */
    protected function sanitize_interaction_layer(string $value): string
    {
        $allowed = ['base', 'reveal'];
        return in_array($value, $allowed, true) ? $value : 'base';
    }
}
