<?php
/**
 * Magnetic Buttons Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Magnetic button widget.
 */
class Magnetic_Buttons extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-magnetic-buttons';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Magnetic Buttons', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-magnetic-buttons';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-magnetic-buttons-style',
            'elementor-icons-fa-solid',
            'elementor-icons-fa-regular',
            'elementor-icons-fa-brands',
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-magnetic-buttons-script',
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
        return ['magnetic', 'button', 'cta', 'hover', 'interactive'];
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
        $this->register_magnet_controls();
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

        $button_text = trim((string) ($settings['kng_button_text'] ?? ''));
        $link = $settings['kng_button_link'] ?? [];
        $href = is_array($link) ? ($link['url'] ?? '') : '';
        $is_external = is_array($link) && !empty($link['is_external']);
        $nofollow = is_array($link) && !empty($link['nofollow']);

        $rels = [];
        if ($is_external) {
            $rels[] = 'noopener';
            $rels[] = 'noreferrer';
        }
        if ($nofollow) {
            $rels[] = 'nofollow';
        }
        $rel_attr = !empty($rels) ? ' rel="' . esc_attr(implode(' ', array_unique($rels))) . '"' : '';
        $target_attr = $is_external ? ' target="_blank"' : '';

        $size = $this->sanitize_size($settings['kng_button_size'] ?? 'medium');
        $icon_enabled = ($settings['kng_button_icon_enable'] ?? '') === 'yes';
        $icon_position = $this->sanitize_icon_position($settings['kng_button_icon_position'] ?? 'left');

        $options = $this->get_magnet_options($settings);

        $wrapper_classes = [
            'king-addons-magnetic-buttons',
            'king-addons-magnetic-buttons--size-' . $size,
        ];

        $this->add_render_attribute('wrapper', [
            'class' => $wrapper_classes,
            'data-options' => wp_json_encode($options),
            'data-target' => 'button',
        ]);

        $button_classes = ['king-addons-magnetic-buttons__button'];
        if ($icon_enabled && 'right' === $icon_position) {
            $button_classes[] = 'is-icon-right';
        }

        $this->add_render_attribute('button', 'class', $button_classes);
        if ('' === $button_text) {
            $aria_label = sanitize_text_field($settings['kng_button_aria_label'] ?? '');
            if ('' === $aria_label) {
                $aria_label = esc_html__('Magnetic Button', 'king-addons');
            }
            $this->add_render_attribute('button', 'aria-label', $aria_label);
        }

        echo '<div ' . $this->get_render_attribute_string('wrapper') . '>';
        echo '<div class="king-addons-magnetic-buttons__wrap">';

        if (!empty($href)) {
            echo '<a ' . $this->get_render_attribute_string('button') . ' href="' . esc_url($href) . '"' . $target_attr . $rel_attr . '>';
        } else {
            echo '<button ' . $this->get_render_attribute_string('button') . ' type="button">';
        }

        echo '<span class="king-addons-magnetic-buttons__content">';

        if ($icon_enabled && !empty($settings['kng_button_icon']['value'])) {
            echo '<span class="king-addons-magnetic-buttons__icon" aria-hidden="true">';
            Icons_Manager::render_icon($settings['kng_button_icon'], ['aria-hidden' => 'true']);
            echo '</span>';
        }

        if (!empty($button_text)) {
            echo '<span class="king-addons-magnetic-buttons__text">' . esc_html($button_text) . '</span>';
        }

        echo '</span>';

        if (!empty($href)) {
            echo '</a>';
        } else {
            echo '</button>';
        }

        echo '</div>';
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
            'kng_button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => true,
                ],
                'default' => esc_html__('Magnetic Button', 'king-addons'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'kng_button_aria_label',
            [
                'label' => esc_html__('Aria Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => [
                    'active' => true,
                ],
                'label_block' => true,
                'description' => esc_html__('Used when the button text is empty.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_button_link',
            [
                'label' => esc_html__('Button Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'dynamic' => [
                    'active' => true,
                ],
                'placeholder' => esc_html__('https://', 'king-addons'),
            ]
        );

        $this->add_responsive_control(
            'kng_button_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'toggle' => true,
                'default' => 'left',
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
                    '{{WRAPPER}} .king-addons-magnetic-buttons' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'medium',
                'options' => [
                    'small' => esc_html__('Small', 'king-addons'),
                    'medium' => esc_html__('Medium', 'king-addons'),
                    'large' => esc_html__('Large', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_button_icon_enable',
            [
                'label' => esc_html__('Enable Icon', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_button_icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-arrow-right',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'kng_button_icon_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_button_icon_position',
            [
                'label' => esc_html__('Icon Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'right' => esc_html__('Right', 'king-addons'),
                ],
                'condition' => [
                    'kng_button_icon_enable' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_button_icon_spacing',
            [
                'label' => esc_html__('Icon Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 4,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-magnetic-buttons' => '--ka-magnetic-icon-gap: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_button_icon_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Magnetic controls.
     *
     * @return void
     */
    protected function register_magnet_controls(): void
    {
        $this->start_controls_section(
            'kng_magnet_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Magnetic', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_magnet_enable',
            [
                'label' => esc_html__('Enable Magnet', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_magnet_strength',
            [
                'label' => esc_html__('Strength', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 25,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_magnet_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_magnet_radius',
            [
                'label' => esc_html__('Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 400,
                    ],
                ],
                'default' => [
                    'size' => 120,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_magnet_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_magnet_max_offset',
            [
                'label' => esc_html__('Max Offset', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                    ],
                ],
                'default' => [
                    'size' => 12,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_magnet_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_magnet_return_speed',
            [
                'label' => esc_html__('Return Speed', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 40,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_magnet_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_magnet_easing',
            [
                'label' => esc_html__('Easing', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'smooth',
                'options' => [
                    'smooth' => esc_html__('Smooth', 'king-addons'),
                    'snappy' => esc_html__('Snappy', 'king-addons'),
                    'calm' => esc_html__('Calm', 'king-addons'),
                ],
                'condition' => [
                    'kng_magnet_enable' => 'yes',
                ],
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
            'kng_style_button_section',
            [
                'label' => esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-magnetic-buttons__button',
            ]
        );

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-magnetic-buttons__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_button_radius',
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
                    '{{WRAPPER}} .king-addons-magnetic-buttons__button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_button_border_width',
            [
                'label' => esc_html__('Border Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 8,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-magnetic-buttons' => '--ka-magnetic-button-border-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_border_style',
            [
                'label' => esc_html__('Border Style', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'solid',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'solid' => esc_html__('Solid', 'king-addons'),
                    'dashed' => esc_html__('Dashed', 'king-addons'),
                    'dotted' => esc_html__('Dotted', 'king-addons'),
                    'double' => esc_html__('Double', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-magnetic-buttons' => '--ka-magnetic-button-border-style: {{VALUE}};',
                ],
            ]
        );

        $this->start_controls_tabs('kng_button_style_tabs');

        $this->start_controls_tab(
            'kng_button_style_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_button_background',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-magnetic-buttons' => '--ka-magnetic-button-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-magnetic-buttons' => '--ka-magnetic-button-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-magnetic-buttons' => '--ka-magnetic-button-border: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow',
                'selector' => '{{WRAPPER}} .king-addons-magnetic-buttons__button',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_button_style_tab_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_button_background_hover',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-magnetic-buttons' => '--ka-magnetic-button-bg-hover: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_text_color_hover',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-magnetic-buttons' => '--ka-magnetic-button-color-hover: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_border_color_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-magnetic-buttons' => '--ka-magnetic-button-border-hover: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-magnetic-buttons__button:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'kng_button_transition',
            [
                'label' => esc_html__('Transition Duration (ms)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 220,
                'min' => 0,
                'step' => 10,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-magnetic-buttons' => '--ka-magnetic-button-transition: {{VALUE}}ms;',
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
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'magnetic-buttons', [
                'Card and icon magnet modes',
                'Boundary constraints and edge resistance',
                'Reduced motion controls and touch behavior',
                'Advanced curves, damping, and inner magnet',
                'Custom selector targeting',
            ]);
        }
    }

    /**
     * Build magnetic options array.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<string, mixed>
     */
    protected function get_magnet_options(array $settings): array
    {
        return [
            'enabled' => (($settings['kng_magnet_enable'] ?? 'yes') === 'yes'),
            'strength' => (int) ($settings['kng_magnet_strength']['size'] ?? 25),
            'radius' => (int) ($settings['kng_magnet_radius']['size'] ?? 120),
            'maxOffset' => (int) ($settings['kng_magnet_max_offset']['size'] ?? 12),
            'returnSpeed' => (int) ($settings['kng_magnet_return_speed']['size'] ?? 40),
            'easing' => $settings['kng_magnet_easing'] ?? 'smooth',
        ];
    }

    /**
     * Sanitize size.
     *
     * @param string $size Size.
     *
     * @return string
     */
    protected function sanitize_size(string $size): string
    {
        $allowed = ['small', 'medium', 'large'];
        return in_array($size, $allowed, true) ? $size : 'medium';
    }

    /**
     * Sanitize icon position.
     *
     * @param string $position Position.
     *
     * @return string
     */
    protected function sanitize_icon_position(string $position): string
    {
        $allowed = ['left', 'right'];
        return in_array($position, $allowed, true) ? $position : 'left';
    }
}
