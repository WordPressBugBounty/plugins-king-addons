<?php /** @noinspection PhpUnused, SpellCheckingInspection */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Repeater;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Animated_Gradient_Mesh_Background
{
    private static ?Animated_Gradient_Mesh_Background $_instance = null;

    public static function instance(): Animated_Gradient_Mesh_Background
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        add_action('elementor/element/section/section_background/after_section_end', [$this, 'addControls'], 1);
        add_action('elementor/element/container/section_background/after_section_end', [$this, 'addControls'], 1);
        add_action('elementor/element/column/section_style/after_section_end', [$this, 'addControls'], 1);
        add_action('elementor/frontend/before_render', [$this, 'before_render'], 1);
    }

    public function addControls(Element_Base $element): void
    {
        $element->start_controls_section(
            'kng_agm_background_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Animated Gradient / Mesh', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $element->add_control(
            'kng_agm_enable',
            [
                'label' => esc_html__('Enable Background', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'prefix_class' => 'kng-agm-bg-',
            ]
        );

        $element->add_control(
            'kng_agm_type',
            [
                'label' => esc_html__('Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'gradient' => esc_html__('Animated Gradient', 'king-addons'),
                    'mesh' => esc_html__('Mesh', 'king-addons'),
                ],
                'default' => 'gradient',
                'prefix_class' => 'kng-agm-type-',
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_preset',
            [
                'label' => esc_html__('Preset', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_preset_options(),
                'default' => 'aurora',
                'prefix_class' => 'kng-agm-preset-',
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_enable_animation',
            [
                'label' => esc_html__('Enable Animation', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
                'prefix_class' => 'kng-agm-animate-',
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_speed',
            [
                'label' => esc_html__('Speed', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => 40,
                ],
                'selectors' => [
                    '{{WRAPPER}}.kng-agm-bg-yes' => '--kng-agm-speed: {{SIZE}};',
                ],
                'condition' => [
                    'kng_agm_enable' => 'yes',
                    'kng_agm_enable_animation' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_direction',
            [
                'label' => esc_html__('Direction', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'left-right' => esc_html__('Left to Right', 'king-addons'),
                    'right-left' => esc_html__('Right to Left', 'king-addons'),
                    'top-bottom' => esc_html__('Top to Bottom', 'king-addons'),
                    'bottom-top' => esc_html__('Bottom to Top', 'king-addons'),
                    'diag-lr' => esc_html__('Diagonal (Left)', 'king-addons'),
                    'diag-rl' => esc_html__('Diagonal (Right)', 'king-addons'),
                ],
                'default' => 'left-right',
                'prefix_class' => 'kng-agm-direction-',
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_opacity',
            [
                'label' => esc_html__('Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0.1,
                        'max' => 1,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 1,
                ],
                'selectors' => [
                    '{{WRAPPER}}.kng-agm-bg-yes' => '--kng-agm-opacity: {{SIZE}};',
                ],
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_blur',
            [
                'label' => esc_html__('Blur', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 20,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => 0,
                ],
                'selectors' => [
                    '{{WRAPPER}}.kng-agm-bg-yes' => '--kng-agm-blur: {{SIZE}}px;',
                ],
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_easing',
            [
                'label' => esc_html__('Animation Easing', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'linear' => esc_html__('Linear', 'king-addons'),
                    'ease' => esc_html__('Ease', 'king-addons'),
                    'ease-in-out' => esc_html__('Ease In Out', 'king-addons'),
                    'ease-in' => esc_html__('Ease In', 'king-addons'),
                    'ease-out' => esc_html__('Ease Out', 'king-addons'),
                ],
                'default' => 'ease-in-out',
                'selectors' => [
                    '{{WRAPPER}}.kng-agm-bg-yes' => '--kng-agm-easing: {{VALUE}};',
                ],
                'condition' => [
                    'kng_agm_enable' => 'yes',
                    'kng_agm_enable_animation' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_angle',
            [
                'label' => esc_html__('Gradient Angle', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['deg'],
                'range' => [
                    'deg' => [
                        'min' => 0,
                        'max' => 360,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'size' => 135,
                ],
                'selectors' => [
                    '{{WRAPPER}}.kng-agm-bg-yes' => '--kng-agm-angle: {{SIZE}}deg;',
                ],
                'condition' => [
                    'kng_agm_enable' => 'yes',
                    'kng_agm_type' => 'gradient',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_bg_size',
            [
                'label' => esc_html__('Background Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 100,
                        'max' => 400,
                        'step' => 10,
                    ],
                ],
                'default' => [
                    'size' => 200,
                ],
                'selectors' => [
                    '{{WRAPPER}}.kng-agm-bg-yes' => '--kng-agm-bg-size: {{SIZE}}%;',
                ],
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_z_index',
            [
                'label' => esc_html__('Z-Index', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => -1,
                'max' => 10,
                'step' => 1,
                'default' => 0,
                'selectors' => [
                    '{{WRAPPER}}.kng-agm-bg-yes::before, {{WRAPPER}}.kng-agm-bg-yes::after' => 'z-index: {{VALUE}};',
                ],
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_noise',
            [
                'label' => esc_html__('Noise Overlay', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
                'prefix_class' => 'kng-agm-noise-',
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_noise_amount',
            [
                'label' => esc_html__('Noise Amount', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 0.6,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 0.18,
                ],
                'selectors' => [
                    '{{WRAPPER}}.kng-agm-bg-yes' => '--kng-agm-noise-opacity: {{SIZE}};',
                ],
                'condition' => [
                    'kng_agm_enable' => 'yes',
                    'kng_agm_noise' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_noise_type',
            [
                'label' => esc_html__('Noise Pattern', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'grain' => esc_html__('Grain', 'king-addons'),
                    'lines' => esc_html__('Lines', 'king-addons'),
                    'dots' => esc_html__('Dots', 'king-addons'),
                    'crosshatch' => esc_html__('Crosshatch', 'king-addons'),
                ],
                'default' => 'grain',
                'prefix_class' => 'kng-agm-noise-type-',
                'condition' => [
                    'kng_agm_enable' => 'yes',
                    'kng_agm_noise' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_custom_colors_heading',
            [
                'label' => esc_html__('Custom Colors', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_use_custom_colors',
            [
                'label' => esc_html__('Use Custom Colors', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'prefix_class' => 'kng-agm-custom-',
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_color_1',
            [
                'label' => esc_html__('Color 1', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#0b1020',
                'selectors' => [
                    '{{WRAPPER}}.kng-agm-bg-yes' => '--kng-agm-color-1: {{VALUE}};',
                ],
                'condition' => [
                    'kng_agm_enable' => 'yes',
                    'kng_agm_use_custom_colors' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_color_2',
            [
                'label' => esc_html__('Color 2', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#3dd6d0',
                'selectors' => [
                    '{{WRAPPER}}.kng-agm-bg-yes' => '--kng-agm-color-2: {{VALUE}};',
                ],
                'condition' => [
                    'kng_agm_enable' => 'yes',
                    'kng_agm_use_custom_colors' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_color_3',
            [
                'label' => esc_html__('Color 3', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#5878ff',
                'selectors' => [
                    '{{WRAPPER}}.kng-agm-bg-yes' => '--kng-agm-color-3: {{VALUE}};',
                ],
                'condition' => [
                    'kng_agm_enable' => 'yes',
                    'kng_agm_use_custom_colors' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_adaptive',
            [
                'label' => esc_html__('Adaptive Performance', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
                'prefix_class' => 'kng-agm-adaptive-',
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_disable_tablet',
            [
                'label' => esc_html__('Disable on Tablet', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'prefix_class' => 'kng-agm-disable-tablet-',
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_disable_mobile',
            [
                'label' => esc_html__('Disable on Mobile', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'prefix_class' => 'kng-agm-disable-mobile-',
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->end_controls_section();

        if ($this->can_use_pro()) {
            $this->add_pro_controls($element);
        } else {
            Core::renderProFeaturesSection(
                $element,
                Controls_Manager::TAB_STYLE,
                Controls_Manager::RAW_HTML,
                'animated-gradient-mesh-background',
                [
                    esc_html__('Scroll driven animation', 'king-addons'),
                    esc_html__('Blend modes', 'king-addons'),
                    esc_html__('Multi-layer presets', 'king-addons'),
                    esc_html__('Export CSS + fallback', 'king-addons'),
                ]
            );
        }
    }

    private function add_pro_controls(Element_Base $element): void
    {
        $element->start_controls_section(
            'kng_agm_background_pro_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON_PRO . esc_html__('Animated Gradient / Mesh (Pro)', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_agm_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_blend_mode',
            [
                'label' => esc_html__('Blend Mode', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'normal' => esc_html__('Normal', 'king-addons'),
                    'multiply' => esc_html__('Multiply', 'king-addons'),
                    'screen' => esc_html__('Screen', 'king-addons'),
                    'overlay' => esc_html__('Overlay', 'king-addons'),
                    'soft-light' => esc_html__('Soft Light', 'king-addons'),
                ],
                'default' => 'normal',
                'selectors' => [
                    '{{WRAPPER}}.kng-agm-bg-yes' => '--kng-agm-blend-mode: {{VALUE}};',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_scroll_heading',
            [
                'label' => esc_html__('Scroll Driven Animation', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $element->add_control(
            'kng_agm_scroll_mode',
            [
                'label' => esc_html__('Scroll Sync', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'off' => esc_html__('Off', 'king-addons'),
                    'section' => esc_html__('Section Scroll', 'king-addons'),
                    'page' => esc_html__('Page Scroll', 'king-addons'),
                    'hybrid' => esc_html__('Hybrid', 'king-addons'),
                ],
                'default' => 'off',
            ]
        );

        $element->add_control(
            'kng_agm_scroll_easing',
            [
                'label' => esc_html__('Scroll Easing', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'linear' => esc_html__('Linear', 'king-addons'),
                    'ease' => esc_html__('Ease', 'king-addons'),
                    'parallax' => esc_html__('Parallax', 'king-addons'),
                ],
                'default' => 'linear',
                'condition' => [
                    'kng_agm_scroll_mode!' => 'off',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_scroll_start',
            [
                'label' => esc_html__('Scroll Start', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => 0,
                ],
                'condition' => [
                    'kng_agm_scroll_mode!' => 'off',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_scroll_end',
            [
                'label' => esc_html__('Scroll End', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => 100,
                ],
                'condition' => [
                    'kng_agm_scroll_mode!' => 'off',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_scroll_reduce_mobile',
            [
                'label' => esc_html__('Reduce on Mobile', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_agm_scroll_mode!' => 'off',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_layers_heading',
            [
                'label' => esc_html__('Layers', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'enabled',
            [
                'label' => esc_html__('Enable Layer', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $repeater->add_control(
            'type',
            [
                'label' => esc_html__('Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'gradient' => esc_html__('Gradient', 'king-addons'),
                    'mesh' => esc_html__('Mesh', 'king-addons'),
                ],
                'default' => 'mesh',
            ]
        );

        $repeater->add_control(
            'preset',
            [
                'label' => esc_html__('Preset', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_preset_options(),
                'default' => 'aurora',
            ]
        );

        $repeater->add_control(
            'use_custom_colors',
            [
                'label' => esc_html__('Custom Colors', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
            ]
        );

        $repeater->add_control(
            'color_1',
            [
                'label' => esc_html__('Color 1', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#3dd6d0',
                'condition' => [
                    'use_custom_colors' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'color_2',
            [
                'label' => esc_html__('Color 2', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#5878ff',
                'condition' => [
                    'use_custom_colors' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'color_3',
            [
                'label' => esc_html__('Color 3', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#0b1020',
                'condition' => [
                    'use_custom_colors' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'opacity',
            [
                'label' => esc_html__('Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0.05,
                        'max' => 1,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 0.7,
                ],
            ]
        );

        $repeater->add_control(
            'blur',
            [
                'label' => esc_html__('Blur', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => 0,
                ],
            ]
        );

        $repeater->add_control(
            'speed',
            [
                'label' => esc_html__('Speed', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => 35,
                ],
            ]
        );

        $repeater->add_control(
            'direction',
            [
                'label' => esc_html__('Direction', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'left-right' => esc_html__('Left to Right', 'king-addons'),
                    'right-left' => esc_html__('Right to Left', 'king-addons'),
                    'top-bottom' => esc_html__('Top to Bottom', 'king-addons'),
                    'bottom-top' => esc_html__('Bottom to Top', 'king-addons'),
                    'diag-lr' => esc_html__('Diagonal (Left)', 'king-addons'),
                    'diag-rl' => esc_html__('Diagonal (Right)', 'king-addons'),
                ],
                'default' => 'left-right',
            ]
        );

        $repeater->add_control(
            'blend_mode',
            [
                'label' => esc_html__('Blend Mode', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'normal' => esc_html__('Normal', 'king-addons'),
                    'multiply' => esc_html__('Multiply', 'king-addons'),
                    'screen' => esc_html__('Screen', 'king-addons'),
                    'overlay' => esc_html__('Overlay', 'king-addons'),
                    'soft-light' => esc_html__('Soft Light', 'king-addons'),
                ],
                'default' => 'normal',
            ]
        );

        $repeater->add_control(
            'size',
            [
                'label' => esc_html__('Scale', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 120,
                        'max' => 260,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'size' => 200,
                ],
            ]
        );

        $element->add_control(
            'kng_agm_layers',
            [
                'label' => esc_html__('Layer Presets', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'title_field' => '{{{ preset }}}',
            ]
        );

        $element->add_control(
            'kng_agm_export_heading',
            [
                'label' => esc_html__('Export', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $element->add_control(
            'kng_agm_export_enable',
            [
                'label' => esc_html__('Enable Export Panel', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $element->add_control(
            'kng_agm_export_fallback',
            [
                'label' => esc_html__('Include Fallback', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_agm_export_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_export_reduced_motion',
            [
                'label' => esc_html__('Include Reduced Motion', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_agm_export_enable' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'kng_agm_export_note',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<div class="king-addons-editor-note">' . esc_html__('The export panel appears in the editor preview when enabled.', 'king-addons') . '</div>',
                'condition' => [
                    'kng_agm_export_enable' => 'yes',
                ],
            ]
        );

        $element->end_controls_section();
    }

    public function before_render(Element_Base $element): void
    {
        $settings = $element->get_settings_for_display();
        if (($settings['kng_agm_enable'] ?? '') !== 'yes') {
            return;
        }

        $payload = $this->build_payload($settings);
        if (!$payload) {
            return;
        }

        $element->add_render_attribute('_wrapper', 'data-kng-agm', wp_json_encode($payload));

        $this->enqueue_styles();

        if ($this->should_enqueue_script($settings)) {
            $this->enqueue_script();
        }
    }

    private function build_payload(array $settings): array
    {
        $payload = [
            'enabled' => true,
            'type' => $settings['kng_agm_type'] ?? 'gradient',
            'preset' => $settings['kng_agm_preset'] ?? 'aurora',
            'speed' => (int) ($settings['kng_agm_speed']['size'] ?? 40),
            'direction' => $settings['kng_agm_direction'] ?? 'left-right',
            'opacity' => (float) ($settings['kng_agm_opacity']['size'] ?? 1),
            'blur' => (float) ($settings['kng_agm_blur']['size'] ?? 0),
            'easing' => $settings['kng_agm_easing'] ?? 'ease-in-out',
            'angle' => (int) ($settings['kng_agm_angle']['size'] ?? 135),
            'bgSize' => (int) ($settings['kng_agm_bg_size']['size'] ?? 200),
            'zIndex' => (int) ($settings['kng_agm_z_index'] ?? 0),
            'animate' => ($settings['kng_agm_enable_animation'] ?? 'yes') === 'yes',
            'noise' => [
                'enabled' => ($settings['kng_agm_noise'] ?? '') === 'yes',
                'amount' => (float) ($settings['kng_agm_noise_amount']['size'] ?? 0.18),
                'type' => $settings['kng_agm_noise_type'] ?? 'grain',
            ],
            'customColors' => [
                'enabled' => ($settings['kng_agm_use_custom_colors'] ?? '') === 'yes',
                'color1' => $settings['kng_agm_color_1'] ?? '#0b1020',
                'color2' => $settings['kng_agm_color_2'] ?? '#3dd6d0',
                'color3' => $settings['kng_agm_color_3'] ?? '#5878ff',
            ],
            'adaptive' => ($settings['kng_agm_adaptive'] ?? 'yes') === 'yes',
            'disable' => [
                'tablet' => ($settings['kng_agm_disable_tablet'] ?? '') === 'yes',
                'mobile' => ($settings['kng_agm_disable_mobile'] ?? '') === 'yes',
            ],
        ];

        if ($this->can_use_pro()) {
            $payload['blendMode'] = $settings['kng_agm_blend_mode'] ?? 'normal';
            $payload['scroll'] = [
                'mode' => $settings['kng_agm_scroll_mode'] ?? 'off',
                'easing' => $settings['kng_agm_scroll_easing'] ?? 'linear',
                'start' => (float) ($settings['kng_agm_scroll_start']['size'] ?? 0),
                'end' => (float) ($settings['kng_agm_scroll_end']['size'] ?? 100),
                'reduceMobile' => ($settings['kng_agm_scroll_reduce_mobile'] ?? 'yes') === 'yes',
            ];
            $payload['layers'] = $this->normalize_layers($settings['kng_agm_layers'] ?? []);
            $payload['export'] = [
                'enabled' => ($settings['kng_agm_export_enable'] ?? '') === 'yes',
                'includeFallback' => ($settings['kng_agm_export_fallback'] ?? 'yes') === 'yes',
                'includeReducedMotion' => ($settings['kng_agm_export_reduced_motion'] ?? 'yes') === 'yes',
            ];
        }

        return $payload;
    }

    private function normalize_layers(array $layers): array
    {
        $normalized = [];

        foreach ($layers as $layer) {
            if (($layer['enabled'] ?? '') !== 'yes') {
                continue;
            }

            $entry = [
                'type' => $layer['type'] ?? 'mesh',
                'preset' => $layer['preset'] ?? 'aurora',
                'opacity' => (float) ($layer['opacity']['size'] ?? 0.7),
                'blur' => (float) ($layer['blur']['size'] ?? 0),
                'speed' => (int) ($layer['speed']['size'] ?? 35),
                'direction' => $layer['direction'] ?? 'left-right',
                'blendMode' => $layer['blend_mode'] ?? 'normal',
                'size' => (int) ($layer['size']['size'] ?? 200),
            ];

            if (($layer['use_custom_colors'] ?? '') === 'yes') {
                $entry['colors'] = [
                    $layer['color_1'] ?? '#3dd6d0',
                    $layer['color_2'] ?? '#5878ff',
                    $layer['color_3'] ?? '#0b1020',
                ];
            }

            $normalized[] = $entry;
        }

        return array_slice($normalized, 0, 5);
    }

    private function should_enqueue_script(array $settings): bool
    {
        if (!$this->can_use_pro()) {
            return false;
        }

        if (($settings['kng_agm_scroll_mode'] ?? 'off') !== 'off') {
            return true;
        }

        if (!empty($settings['kng_agm_layers'])) {
            return true;
        }

        return ($settings['kng_agm_export_enable'] ?? '') === 'yes';
    }

    private function enqueue_styles(): void
    {
        $handle = KING_ADDONS_ASSETS_UNIQUE_KEY . '-animated-gradient-mesh-background-style';

        if (!wp_style_is($handle)) {
            wp_enqueue_style(
                $handle,
                KING_ADDONS_URL . 'includes/features/Animated_Gradient_Mesh_Background/style.css',
                [],
                KING_ADDONS_VERSION
            );
        }
    }

    private function enqueue_script(): void
    {
        $handle = KING_ADDONS_ASSETS_UNIQUE_KEY . '-animated-gradient-mesh-background-script';

        if (!wp_script_is($handle)) {
            wp_enqueue_script($handle);
        }
    }

    private function can_use_pro(): bool
    {
        if (!function_exists('king_addons_freemius')) {
            return false;
        }

        return king_addons_freemius()->can_use_premium_code__premium_only();
    }

    private function get_preset_options(): array
    {
        return [
            'aurora' => esc_html__('Aurora', 'king-addons'),
            'sunset' => esc_html__('Sunset', 'king-addons'),
            'ocean' => esc_html__('Ocean', 'king-addons'),
            'violet-mist' => esc_html__('Violet Mist', 'king-addons'),
            'lime-neon' => esc_html__('Lime Neon', 'king-addons'),
            'mono-glass' => esc_html__('Mono Glass', 'king-addons'),
            'forest-dew' => esc_html__('Forest Dew', 'king-addons'),
            'candy-pop' => esc_html__('Candy Pop', 'king-addons'),
            'midnight-blue' => esc_html__('Midnight Blue', 'king-addons'),
            'rose-gold' => esc_html__('Rose Gold', 'king-addons'),
        ];
    }
}
