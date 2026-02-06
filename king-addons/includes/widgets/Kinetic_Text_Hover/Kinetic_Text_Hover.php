<?php
/**
 * Kinetic Text Hover Widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

class Kinetic_Text_Hover extends Widget_Base
{
    public function get_name(): string
    {
        return 'king-addons-kinetic-text-hover';
    }

    public function get_title(): string
    {
        return esc_html__('Kinetic Text Hover', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-kinetic-text-hover';
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-kinetic-text-hover-style'];
    }

    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-kinetic-text-hover-script'];
    }

    public function get_categories(): array
    {
        return ['king-addons'];
    }

    public function get_keywords(): array
    {
        return ['kinetic', 'text', 'hover', 'chroma', 'glow', 'underline', 'reveal', 'typography'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        public function register_controls(): void
    {
        $this->register_content_controls();
        $this->register_variable_font_controls();
        $this->register_magnetic_controls();
        $this->register_reveal_controls();
        $this->register_motion_controls();
        $this->register_style_text_controls();
        $this->register_style_effect_controls();
        $this->register_style_preset_controls();
        $this->register_pro_notice_controls();
    }

    public function render(): void
    {
        $settings = $this->get_settings_for_display();

        $text = sanitize_textarea_field((string) ($settings['kng_text'] ?? ''));
        if ($text === '') {
            $text = esc_html__('Kinetic Text Hover', 'king-addons');
        }

        $tag = $this->sanitize_html_tag($settings['kng_html_tag'] ?? 'h2');
        $preset = $settings['kng_preset'] ?? 'split-underline';
        $trigger = $settings['kng_trigger'] ?? 'hover';
        $mobile_behavior = $settings['kng_mobile_behavior'] ?? 'disable';
        $gradient_enabled = ($settings['kng_gradient_text_enable'] ?? '') === 'yes';

        if (!$this->can_use_pro() && $trigger === 'idle') {
            $trigger = 'hover';
        }

        $classes = [
            'king-addons-kinetic-text-hover',
            'is-preset-' . $preset,
        ];

        if ($gradient_enabled) {
            $classes[] = 'is-gradient-text';
        }

        $mask_direction = $settings['kng_mask_direction'] ?? 'left';
        if ($preset === 'mask-reveal') {
            $classes[] = 'is-mask-' . $mask_direction;
        }

        $link = is_array($settings['kng_link'] ?? null) ? $settings['kng_link'] : [];
        $has_link = !empty($link['url']);
        if ($has_link) {
            $classes[] = 'is-link';
        }

        $can_pro = $this->can_use_pro();
        $options = [
            'preset' => $preset,
            'trigger' => $trigger,
            'mobileBehavior' => $mobile_behavior,
            'intensity' => (int) ($this->get_slider_size($settings, 'kng_effect_intensity', 35)),
            'letterDrift' => [
                'max' => (float) $this->get_slider_size($settings, 'kng_letter_drift_max', 10),
                'randomness' => (float) $this->get_slider_size($settings, 'kng_letter_drift_randomness', 40),
            ],
            'magnetic' => [
                'enabled' => $can_pro && (($settings['kng_magnetic_enable'] ?? '') === 'yes'),
                'strength' => (float) ($settings['kng_magnetic_strength']['size'] ?? 35),
                'radius' => (float) ($settings['kng_magnetic_radius']['size'] ?? 140),
                'maxOffset' => (float) ($settings['kng_magnetic_max_offset']['size'] ?? 14),
                'smoothing' => (float) ($settings['kng_magnetic_smoothing'] ?? 0.35),
                'clamp' => $settings['kng_magnetic_clamp'] ?? 'soft',
                'disableMobile' => ($settings['kng_magnetic_disable_mobile'] ?? '') === 'yes',
            ],
            'variableFont' => [
                'enabled' => $can_pro && (($settings['kng_varfont_enable'] ?? '') === 'yes'),
                'wghtMin' => (float) ($settings['kng_varfont_wght_min'] ?? 400),
                'wghtMax' => (float) ($settings['kng_varfont_wght_max'] ?? 700),
                'wdthMin' => (float) ($settings['kng_varfont_wdth_min'] ?? 100),
                'wdthMax' => (float) ($settings['kng_varfont_wdth_max'] ?? 110),
                'widthSafe' => ($settings['kng_varfont_width_safe'] ?? '') === 'yes',
            ],
            'reveal' => [
                'enabled' => $can_pro && (($settings['kng_reveal_enable'] ?? '') === 'yes'),
                'type' => $settings['kng_reveal_type'] ?? 'fade',
                'threshold' => (float) ($settings['kng_reveal_threshold'] ?? 0.2),
                'once' => ($settings['kng_reveal_once'] ?? '') === 'yes',
            ],
            'reducedMotion' => [
                'respect' => $can_pro && (($settings['kng_motion_respect'] ?? '') === 'yes'),
                'mode' => $settings['kng_motion_mode'] ?? 'simplify',
            ],
            'editorPreview' => $this->is_editor_mode(),
        ];

        if ($options['reveal']['enabled']) {
            $classes[] = 'has-reveal';
            $classes[] = 'reveal-' . $options['reveal']['type'];
        }

        $this->add_render_attribute('wrapper', [
            'class' => $classes,
            'data-options' => wp_json_encode($options),
            'data-trigger' => $trigger,
            'data-mobile' => $mobile_behavior,
        ]);

        echo '<' . esc_attr($tag) . ' ' . $this->get_render_attribute_string('wrapper') . '>';

        if ($has_link) {
            $this->add_render_attribute('link', 'class', 'king-addons-kinetic-text-hover__link');
            $this->add_render_attribute('link', 'href', esc_url($link['url']));
            if (!empty($link['is_external'])) {
                $this->add_render_attribute('link', 'target', '_blank');
            }
            $rel = [];
            if (!empty($link['nofollow'])) {
                $rel[] = 'nofollow';
            }
            if (!empty($link['is_external'])) {
                $rel[] = 'noopener';
                $rel[] = 'noreferrer';
            }
            if ($rel) {
                $this->add_render_attribute('link', 'rel', implode(' ', array_unique($rel)));
            }
            echo '<a ' . $this->get_render_attribute_string('link') . '>';
        }

        echo '<span class="king-addons-kinetic-text-hover__inner">';
        echo '<span class="king-addons-kinetic-text-hover__sr">' . esc_html($text) . '</span>';
        echo '<span class="king-addons-kinetic-text-hover__visual" aria-hidden="true" data-text="' . esc_attr($text) . '">' . esc_html($text) . '</span>';
        echo '</span>';

        if ($has_link) {
            echo '</a>';
        }

        echo '</' . esc_attr($tag) . '>';
    }

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
            'kng_text',
            [
                'label' => esc_html__('Text', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Kinetic Text Hover', 'king-addons'),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'kng_html_tag',
            [
                'label' => esc_html__('HTML Tag', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'h2',
                'options' => [
                    'h1' => 'H1',
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                    'h6' => 'H6',
                    'div' => 'DIV',
                    'span' => 'SPAN',
                    'p' => 'P',
                ],
            ]
        );

        $this->add_control(
            'kng_link',
            [
                'label' => esc_html__('Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'king-addons'),
                'default' => [
                    'url' => '',
                ],
            ]
        );

        $this->add_control(
            'kng_preset',
            [
                'label' => esc_html__('Preset', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'split-underline',
                'options' => [
                    'split-underline' => esc_html__('Split Underline', 'king-addons'),
                    'glow-stroke' => esc_html__('Glow Stroke', 'king-addons'),
                    'chroma-shift' => esc_html__('Chroma Shift', 'king-addons'),
                    'letter-drift' => esc_html__('Letter Drift', 'king-addons'),
                    'sheen-sweep' => esc_html__('Sheen Sweep', 'king-addons'),
                    'mask-reveal' => esc_html__('Mask Reveal', 'king-addons'),
                ],
            ]
        );

        $trigger_options = [
            'hover' => esc_html__('Hover', 'king-addons'),
            'hover-move' => esc_html__('Hover + Cursor Move', 'king-addons'),
        ];

        if ($this->can_use_pro()) {
            $trigger_options['idle'] = esc_html__('Always On (Idle)', 'king-addons');
        } else {
            $trigger_options['idle'] = $this->get_pro_label(esc_html__('Always On (Idle)', 'king-addons'));
        }

        $this->add_control(
            'kng_trigger',
            [
                'label' => esc_html__('Trigger', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'hover',
                'options' => $trigger_options,
            ]
        );

        $this->add_control(
            'kng_mobile_behavior',
            [
                'label' => esc_html__('Mobile Behavior', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'disable',
                'options' => [
                    'disable' => esc_html__('Disable Effects', 'king-addons'),
                    'tap' => esc_html__('Tap to Activate', 'king-addons'),
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_variable_font_controls(): void
    {
        $this->start_controls_section(
            'kng_varfont_section',
            [
                'label' => $this->get_pro_label(esc_html__('Variable Font Axes', 'king-addons')),
                'tab' => Controls_Manager::TAB_CONTENT,
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_varfont_enable',
            [
                'label' => $this->get_pro_label(esc_html__('Enable Variable Font', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_varfont_notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => esc_html__('Requires a variable font that supports wght/wdth axes.', 'king-addons'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'condition' => [
                    'kng_varfont_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_varfont_wght_min',
            [
                'label' => $this->get_pro_label(esc_html__('Weight Min (wght)', 'king-addons')),
                'type' => Controls_Manager::NUMBER,
                'default' => 400,
                'min' => 100,
                'max' => 900,
                'step' => 1,
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_varfont_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_varfont_wght_max',
            [
                'label' => $this->get_pro_label(esc_html__('Weight Max (wght)', 'king-addons')),
                'type' => Controls_Manager::NUMBER,
                'default' => 700,
                'min' => 100,
                'max' => 900,
                'step' => 1,
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_varfont_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_varfont_wdth_min',
            [
                'label' => $this->get_pro_label(esc_html__('Width Min (wdth)', 'king-addons')),
                'type' => Controls_Manager::NUMBER,
                'default' => 100,
                'min' => 75,
                'max' => 125,
                'step' => 1,
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_varfont_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_varfont_wdth_max',
            [
                'label' => $this->get_pro_label(esc_html__('Width Max (wdth)', 'king-addons')),
                'type' => Controls_Manager::NUMBER,
                'default' => 110,
                'min' => 75,
                'max' => 125,
                'step' => 1,
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_varfont_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_varfont_width_safe',
            [
                'label' => $this->get_pro_label(esc_html__('Use Width-Safe Mode', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_varfont_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_magnetic_controls(): void
    {
        $this->start_controls_section(
            'kng_magnetic_section',
            [
                'label' => $this->get_pro_label(esc_html__('Magnetic Letters', 'king-addons')),
                'tab' => Controls_Manager::TAB_CONTENT,
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_magnetic_enable',
            [
                'label' => $this->get_pro_label(esc_html__('Enable Magnetic Letters', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_magnetic_strength',
            [
                'label' => $this->get_pro_label(esc_html__('Strength', 'king-addons')),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 35,
                    'unit' => '%',
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_magnetic_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_magnetic_radius',
            [
                'label' => $this->get_pro_label(esc_html__('Radius', 'king-addons')),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 40,
                        'max' => 320,
                    ],
                ],
                'default' => [
                    'size' => 140,
                    'unit' => 'px',
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_magnetic_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_magnetic_max_offset',
            [
                'label' => $this->get_pro_label(esc_html__('Max Offset', 'king-addons')),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                ],
                'default' => [
                    'size' => 14,
                    'unit' => 'px',
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_magnetic_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_magnetic_smoothing',
            [
                'label' => $this->get_pro_label(esc_html__('Smoothing', 'king-addons')),
                'type' => Controls_Manager::NUMBER,
                'default' => 0.35,
                'min' => 0,
                'max' => 1,
                'step' => 0.05,
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_magnetic_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_magnetic_clamp',
            [
                'label' => $this->get_pro_label(esc_html__('Clamp Mode', 'king-addons')),
                'type' => Controls_Manager::SELECT,
                'default' => 'soft',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'soft' => esc_html__('Soft', 'king-addons'),
                    'hard' => esc_html__('Hard', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_magnetic_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_magnetic_disable_mobile',
            [
                'label' => $this->get_pro_label(esc_html__('Disable on Mobile', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_magnetic_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_reveal_controls(): void
    {
        $this->start_controls_section(
            'kng_reveal_section',
            [
                'label' => $this->get_pro_label(esc_html__('Reveal on Scroll', 'king-addons')),
                'tab' => Controls_Manager::TAB_CONTENT,
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_reveal_enable',
            [
                'label' => $this->get_pro_label(esc_html__('Enable Reveal', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_reveal_type',
            [
                'label' => $this->get_pro_label(esc_html__('Reveal Type', 'king-addons')),
                'type' => Controls_Manager::SELECT,
                'default' => 'fade',
                'options' => [
                    'fade' => esc_html__('Fade', 'king-addons'),
                    'slide' => esc_html__('Slide Up', 'king-addons'),
                    'clip' => esc_html__('Clip Reveal', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_reveal_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_reveal_duration',
            [
                'label' => $this->get_pro_label(esc_html__('Duration', 'king-addons')),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['ms'],
                'range' => [
                    'ms' => [
                        'min' => 100,
                        'max' => 2000,
                    ],
                ],
                'default' => [
                    'size' => 600,
                    'unit' => 'ms',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-reveal-duration: {{SIZE}}ms;',
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_reveal_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_reveal_delay',
            [
                'label' => $this->get_pro_label(esc_html__('Delay', 'king-addons')),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['ms'],
                'range' => [
                    'ms' => [
                        'min' => 0,
                        'max' => 1000,
                    ],
                ],
                'default' => [
                    'size' => 0,
                    'unit' => 'ms',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-reveal-delay: {{SIZE}}ms;',
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_reveal_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_reveal_threshold',
            [
                'label' => $this->get_pro_label(esc_html__('Threshold', 'king-addons')),
                'type' => Controls_Manager::NUMBER,
                'default' => 0.2,
                'min' => 0,
                'max' => 1,
                'step' => 0.05,
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_reveal_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_reveal_once',
            [
                'label' => $this->get_pro_label(esc_html__('Reveal Once', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_reveal_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_motion_controls(): void
    {
        $this->start_controls_section(
            'kng_motion_section',
            [
                'label' => $this->get_pro_label(esc_html__('Reduced Motion', 'king-addons')),
                'tab' => Controls_Manager::TAB_CONTENT,
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_motion_respect',
            [
                'label' => $this->get_pro_label(esc_html__('Respect Reduced Motion', 'king-addons')),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_motion_mode',
            [
                'label' => $this->get_pro_label(esc_html__('Mode', 'king-addons')),
                'type' => Controls_Manager::SELECT,
                'default' => 'simplify',
                'options' => [
                    'disable' => esc_html__('Disable Animations', 'king-addons'),
                    'simplify' => esc_html__('Simplify Effects', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_motion_respect' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_text_controls(): void
    {
        $this->start_controls_section(
            'kng_style_text_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Typography', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_text_typography',
                'selector' => '{{WRAPPER}} .king-addons-kinetic-text-hover__visual',
            ]
        );

        $this->add_responsive_control(
            'kng_text_align',
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
                'selectors' => [
                    '{{WRAPPER}}' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_text_hover_color',
            [
                'label' => esc_html__('Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-hover-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_text_stroke_color',
            [
                'label' => esc_html__('Stroke Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-stroke-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_gradient_text_enable',
            [
                'label' => esc_html__('Gradient Text', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_text_gradient',
                'types' => ['gradient'],
                'selector' => '{{WRAPPER}} .king-addons-kinetic-text-hover__visual',
                'condition' => [
                    'kng_gradient_text_enable' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_text_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .king-addons-kinetic-text-hover__inner',
            ]
        );

        $this->add_responsive_control(
            'kng_container_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover__inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_letter_spacing_hover',
            [
                'label' => esc_html__('Letter Spacing on Hover', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => -5,
                        'max' => 20,
                    ],
                    'em' => [
                        'min' => -0.2,
                        'max' => 1,
                        'step' => 0.01,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-letter-spacing-hover: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_effect_controls(): void
    {
        $this->start_controls_section(
            'kng_style_effects_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Effects', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_effect_intensity',
            [
                'label' => esc_html__('Intensity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 35,
                    'unit' => '%',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-intensity: calc({{SIZE}} / 100);',
                ],
            ]
        );

        $this->add_control(
            'kng_effect_speed',
            [
                'label' => esc_html__('Speed', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['ms'],
                'range' => [
                    'ms' => [
                        'min' => 100,
                        'max' => 2000,
                    ],
                ],
                'default' => [
                    'size' => 420,
                    'unit' => 'ms',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-speed: {{SIZE}}ms;',
                ],
            ]
        );

        $this->add_control(
            'kng_effect_easing',
            [
                'label' => esc_html__('Easing', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'ease',
                'options' => [
                    'ease' => esc_html__('Ease', 'king-addons'),
                    'linear' => esc_html__('Linear', 'king-addons'),
                    'ease-in' => esc_html__('Ease In', 'king-addons'),
                    'ease-out' => esc_html__('Ease Out', 'king-addons'),
                    'ease-in-out' => esc_html__('Ease In Out', 'king-addons'),
                    'cubic-bezier(0.4, 0, 0.2, 1)' => esc_html__('Cubic', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-ease: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_effect_out_duration',
            [
                'label' => esc_html__('Hover Out Duration', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['ms'],
                'range' => [
                    'ms' => [
                        'min' => 0,
                        'max' => 2000,
                    ],
                ],
                'default' => [
                    'size' => 320,
                    'unit' => 'ms',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-out-speed: {{SIZE}}ms;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_preset_controls(): void
    {
        $this->start_controls_section(
            'kng_style_underline_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Split Underline', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_preset' => 'split-underline',
                ],
            ]
        );

        $this->add_control(
            'kng_underline_thickness',
            [
                'label' => esc_html__('Thickness', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 8,
                    ],
                ],
                'default' => [
                    'size' => 2,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-underline-thickness: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_underline_offset',
            [
                'label' => esc_html__('Offset', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 20,
                    ],
                ],
                'default' => [
                    'size' => 6,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-underline-offset: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_underline_radius',
            [
                'label' => esc_html__('Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 20,
                    ],
                ],
                'default' => [
                    'size' => 4,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-underline-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_underline_color',
            [
                'label' => esc_html__('Underline Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-underline-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_glow_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Glow Stroke', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_preset' => 'glow-stroke',
                ],
            ]
        );

        $this->add_control(
            'kng_stroke_width',
            [
                'label' => esc_html__('Stroke Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 3,
                    ],
                ],
                'default' => [
                    'size' => 1,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-stroke-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_glow_blur',
            [
                'label' => esc_html__('Glow Blur', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                ],
                'default' => [
                    'size' => 12,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-glow-blur: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_glow_intensity',
            [
                'label' => esc_html__('Glow Intensity', 'king-addons'),
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
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-glow-intensity: calc({{SIZE}} / 100);',
                ],
            ]
        );

        $this->add_control(
            'kng_glow_color',
            [
                'label' => esc_html__('Glow Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-glow-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_chroma_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Chroma Shift', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_preset' => 'chroma-shift',
                ],
            ]
        );

        $this->add_control(
            'kng_chroma_distance',
            [
                'label' => esc_html__('Chroma Distance', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 12,
                    ],
                ],
                'default' => [
                    'size' => 5,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-chroma-distance: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_chroma_opacity',
            [
                'label' => esc_html__('Chroma Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 40,
                    'unit' => '%',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-chroma-opacity: calc({{SIZE}} / 100);',
                ],
            ]
        );

        $this->add_control(
            'kng_chroma_color_1',
            [
                'label' => esc_html__('Left Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(255, 0, 96, 0.4)',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-chroma-color-1: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_chroma_color_2',
            [
                'label' => esc_html__('Right Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(0, 209, 255, 0.4)',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-chroma-color-2: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_chroma_color_3',
            [
                'label' => esc_html__('Bottom Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(140, 0, 255, 0.4)',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-chroma-color-3: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_drift_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Letter Drift', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_preset' => 'letter-drift',
                ],
            ]
        );

        $this->add_control(
            'kng_letter_drift_max',
            [
                'label' => esc_html__('Max Translate', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                ],
                'default' => [
                    'size' => 10,
                    'unit' => 'px',
                ],
            ]
        );

        $this->add_control(
            'kng_letter_drift_randomness',
            [
                'label' => esc_html__('Randomness', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 40,
                    'unit' => '%',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_sheen_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Sheen Sweep', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_preset' => 'sheen-sweep',
                ],
            ]
        );

        $this->add_control(
            'kng_sheen_angle',
            [
                'label' => esc_html__('Angle', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['deg'],
                'range' => [
                    'deg' => [
                        'min' => 0,
                        'max' => 180,
                    ],
                ],
                'default' => [
                    'size' => 120,
                    'unit' => 'deg',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-sheen-angle: {{SIZE}}deg;',
                ],
            ]
        );

        $this->add_control(
            'kng_sheen_duration',
            [
                'label' => esc_html__('Sweep Duration', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['ms'],
                'range' => [
                    'ms' => [
                        'min' => 200,
                        'max' => 3000,
                    ],
                ],
                'default' => [
                    'size' => 1400,
                    'unit' => 'ms',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-sheen-duration: {{SIZE}}ms;',
                ],
            ]
        );

        $this->add_control(
            'kng_sheen_brightness',
            [
                'label' => esc_html__('Brightness', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 65,
                    'unit' => '%',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-sheen-brightness: calc({{SIZE}} / 100);',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_style_mask_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Mask Reveal', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_preset' => 'mask-reveal',
                ],
            ]
        );

        $this->add_control(
            'kng_mask_direction',
            [
                'label' => esc_html__('Reveal Direction', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Left to Right', 'king-addons'),
                    'right' => esc_html__('Right to Left', 'king-addons'),
                    'up' => esc_html__('Bottom to Top', 'king-addons'),
                    'down' => esc_html__('Top to Bottom', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_mask_softness',
            [
                'label' => esc_html__('Edge Softness', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'default' => [
                    'size' => 12,
                    'unit' => '%',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kinetic-text-hover' => '--kng-kt-mask-softness: {{SIZE}}%;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    public function register_pro_notice_controls(): void
    {
        if (!$this->can_use_pro()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'kinetic-text-hover', [
                'Variable font axes animation (wght/wdth)',
                'Magnetic letters on cursor movement',
                'Reveal on scroll effects',
                'Reduced motion modes and overrides',
                'Always-on idle animation trigger',
            ]);
        }
    }

    protected function sanitize_html_tag(string $tag): string
    {
        $allowed = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p'];
        $tag = strtolower(trim($tag));
        return in_array($tag, $allowed, true) ? $tag : 'div';
    }

    protected function get_slider_size(array $settings, string $key, float $default = 0): float
    {
        if (isset($settings[$key]['size'])) {
            return (float) $settings[$key]['size'];
        }
        return $default;
    }

    protected function is_editor_mode(): bool
    {
        return class_exists(\Elementor\Plugin::class) && \Elementor\Plugin::$instance->editor->is_edit_mode();
    }

    protected function can_use_pro(): bool
    {
        return function_exists('king_addons_can_use_pro') && king_addons_can_use_pro();
    }

    protected function get_pro_label(string $label): string
    {
        if ($this->can_use_pro()) {
            return $label;
        }

        return $label . ' <i class="eicon-pro-icon"></i>';
    }

    protected function get_pro_control_class(string $extra = ''): string
    {
        if ($this->can_use_pro()) {
            return $extra;
        }

        return trim('king-addons-pro-control ' . $extra);
    }
}
