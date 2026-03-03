<?php

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

class Values_Circle_Infographic extends Widget_Base
{
    public function get_name(): string
    {
        return 'king-addons-values-circle-infographic';
    }

    public function get_title(): string
    {
        return esc_html__('Values Circle Infographic', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-shape-circle';
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-values-circle-infographic-style'];
    }

    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-values-circle-infographic-script',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-lottie-lottie',
        ];
    }

    public function get_categories(): array
    {
        return ['king-addons'];
    }

    public function get_keywords(): array
    {
        return ['values', 'circle', 'infographic', 'diagram', 'king-addons'];
    }

    public function get_custom_help_url()
    {
        return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
    }

    protected function register_controls(): void
    {
        // ── Content: Center Image ───────────────────────────────────────
        $this->start_controls_section(
            'ka_values_circle_center_image_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Center Image', 'king-addons'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'ka_values_circle_center_type',
            [
                'label'   => esc_html__('Type', 'king-addons'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'image',
                'options' => [
                    'image'  => esc_html__('Image', 'king-addons'),
                    'lottie' => esc_html__('Lottie Animation', 'king-addons'),
                ],
            ]
        );

        // ── Image ─────────────────────────────────────────────────────────
        $this->add_control(
            'ka_values_circle_image',
            [
                'label'     => esc_html__('Image', 'king-addons'),
                'type'      => Controls_Manager::MEDIA,
                'default'   => ['url' => ''],
                'condition' => ['ka_values_circle_center_type' => 'image'],
            ]
        );

        // ── Lottie ────────────────────────────────────────────────────────
        $this->add_control(
            'ka_values_circle_lottie_source',
            [
                'label'     => esc_html__('Lottie File Source', 'king-addons'),
                'type'      => Controls_Manager::SELECT,
                'options'   => [
                    'file' => esc_html__('Media File (JSON)', 'king-addons'),
                    'url'  => esc_html__('External URL', 'king-addons'),
                ],
                'default'   => 'file',
                'condition' => ['ka_values_circle_center_type' => 'lottie'],
            ]
        );

        $this->add_control(
            'ka_values_circle_lottie_file',
            [
                'label'      => esc_html__('Upload JSON File', 'king-addons'),
                'type'       => Controls_Manager::MEDIA,
                'media_type' => 'application/json',
                'condition'  => [
                    'ka_values_circle_center_type'   => 'lottie',
                    'ka_values_circle_lottie_source' => 'file',
                ],
            ]
        );

        $this->add_control(
            'ka_values_circle_lottie_url',
            [
                'label'       => esc_html__('Animation JSON URL', 'king-addons'),
                'type'        => Controls_Manager::TEXT,
                'label_block' => true,
                'dynamic'     => ['active' => true],
                'description' => esc_html__('Get free animations at lottiefiles.com', 'king-addons'),
                'condition'   => [
                    'ka_values_circle_center_type'   => 'lottie',
                    'ka_values_circle_lottie_source' => 'url',
                ],
            ]
        );

        $this->add_control(
            'ka_values_circle_lottie_autoplay',
            [
                'label'        => esc_html__('Autoplay', 'king-addons'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'separator'    => 'before',
                'condition'    => ['ka_values_circle_center_type' => 'lottie'],
            ]
        );

        $this->add_control(
            'ka_values_circle_lottie_loop',
            [
                'label'        => esc_html__('Loop', 'king-addons'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'condition'    => ['ka_values_circle_center_type' => 'lottie'],
            ]
        );

        $this->add_control(
            'ka_values_circle_lottie_speed',
            [
                'label'     => esc_html__('Speed', 'king-addons'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 1,
                'min'       => 0.1,
                'max'       => 3,
                'step'      => 0.1,
                'condition' => ['ka_values_circle_center_type' => 'lottie'],
            ]
        );

        $this->add_control(
            'ka_values_circle_lottie_reverse',
            [
                'label'        => esc_html__('Reverse', 'king-addons'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition'    => ['ka_values_circle_center_type' => 'lottie'],
            ]
        );

        $this->add_control(
            'ka_values_circle_lottie_renderer',
            [
                'label'     => esc_html__('Render As', 'king-addons'),
                'type'      => Controls_Manager::SELECT,
                'options'   => [
                    'svg'    => 'SVG',
                    'canvas' => 'Canvas',
                    'html'   => 'HTML',
                ],
                'default'   => 'svg',
                'condition' => ['ka_values_circle_center_type' => 'lottie'],
            ]
        );

        $this->add_responsive_control(
            'ka_values_circle_image_width',
            [
                'label'      => esc_html__('Width', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['%', 'px'],
                'range'      => [
                    '%'  => ['min' => 5,  'max' => 90,  'step' => 1],
                    'px' => ['min' => 50, 'max' => 800, 'step' => 1],
                ],
                'default'    => ['unit' => '%', 'size' => 40],
                'selectors'  => [
                    '{{WRAPPER}} .king-addons-values-circle-center-image-wrap' => 'width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-values-circle-mobile-image'          => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ── Style: Mobile Layout ───────────────────────────────────────
        $this->start_controls_section(
            'ka_values_circle_mobile_layout_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Mobile Layout', 'king-addons'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'ka_values_circle_mobile_gap_image_to_first',
            [
                'label'      => esc_html__('Image → First Block Gap', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 12, 'max' => 220, 'step' => 1]],
                'default'    => ['unit' => 'px', 'size' => 50],
                'selectors'  => [
                    '{{WRAPPER}} .king-addons-values-circle-mobile-view .ka-vcm-connector--first' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'ka_values_circle_mobile_gap_between_blocks',
            [
                'label'      => esc_html__('Gap Between Blocks', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 12, 'max' => 220, 'step' => 1]],
                'default'    => ['unit' => 'px', 'size' => 50],
                'selectors'  => [
                    '{{WRAPPER}} .king-addons-values-circle-mobile-view .ka-vcm-connector--between' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'ka_values_circle_mobile_conn_gap',
            [
                'label'      => esc_html__('Gap from block edge (px)', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 0, 'max' => 40, 'step' => 1]],
                'default'    => ['unit' => 'px', 'size' => 6],
                'separator'  => 'before',
            ]
        );

        $this->add_control(
            'ka_values_circle_mobile_dot_r_small',
            [
                'label'      => esc_html__('Small dot radius (px)', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 1, 'max' => 30, 'step' => 0.5]],
                'default'    => ['unit' => 'px', 'size' => 5.5],
                'separator'  => 'before',
            ]
        );

        $this->add_control(
            'ka_values_circle_mobile_dot_r_large',
            [
                'label'      => esc_html__('Large dot radius (px)', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 1, 'max' => 40, 'step' => 0.5]],
                'default'    => ['unit' => 'px', 'size' => 8],
            ]
        );

        $this->add_control(
            'ka_values_circle_mobile_dot_step',
            [
                'label'      => esc_html__('Spacing between dots (px)', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 2, 'max' => 80, 'step' => 1]],
                'default'    => ['unit' => 'px', 'size' => 28],
                'separator'  => 'before',
            ]
        );

        $this->end_controls_section();

        // ── Content: Items ─────────────────────────────────────────────
        $items_config = [
            ['top_left',     'top-left',     esc_html__('Top Left', 'king-addons'),     'left',  60,  'top',    10,  'Respect',      "Honouring culture,\npeople, and\npartnerships"],
            ['top_right',    'top-right',    esc_html__('Top Right', 'king-addons'),    'right', 60,  'top',    10,  'Sustainability',"Extending\nproduct life\ncycles"],
            ['middle_left',  'middle-left',  esc_html__('Middle Left', 'king-addons'),  'left',  -24, 'top',    286, 'Integrity',    "Acting with\nhonesty and\naccountability"],
            ['middle_right', 'middle-right', esc_html__('Middle Right', 'king-addons'), 'right', -24, 'top',    286, 'Excellence',   "Delivering quality\nand reliability\nevery time"],
            ['bottom_left',  'bottom-left',  esc_html__('Bottom Left', 'king-addons'),  'left',  60,  'bottom', 10,  'Safety',       "We put safety first\n- protecting our\npeople, partners,\nand environment"],
            ['bottom_right', 'bottom-right', esc_html__('Bottom Right', 'king-addons'), 'right', 60,  'bottom', 10,  'Inclusion',    "Empowering\nAboriginal\nparticipation\nand growth"],
        ];

        foreach ($items_config as [$prefix, $slot, $label, $h_prop, $h_default, $v_prop, $v_default, $default_title, $default_desc]) {
            $this->add_item_section($prefix, $slot, $label, $h_prop, $h_default, $v_prop, $v_default, $default_title, $default_desc);
        }

        // ── Style: Connectors ───────────────────────────────────────────
        $this->start_controls_section(
            'ka_values_circle_connectors_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Connectors', 'king-addons'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'ka_values_circle_dot_color',
            [
                'label'     => esc_html__('Dot Color', 'king-addons'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f2e3b8',
                'selectors' => [
                    '{{WRAPPER}} .ka-values-circle-dot' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'ka_values_circle_conn_gap',
            [
                'label'      => esc_html__('Gap from block edge (px)', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 0, 'max' => 80, 'step' => 1]],
                'default'    => ['unit' => 'px', 'size' => 20],
            ]
        );

        $this->add_control(
            'ka_values_circle_dot_r_small',
            [
                'label'      => esc_html__('Small dot radius (px)', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 1, 'max' => 30, 'step' => 0.5]],
                'default'    => ['unit' => 'px', 'size' => 8],
                'separator'  => 'before',
            ]
        );

        $this->add_control(
            'ka_values_circle_dot_r_large',
            [
                'label'      => esc_html__('Large dot radius (px)', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 1, 'max' => 40, 'step' => 0.5]],
                'default'    => ['unit' => 'px', 'size' => 9.5],
            ]
        );

        $this->end_controls_section();

        // ── Style: Per-connector ──────────────────────────────────────────
        $this->add_connector_section('top',          esc_html__('Top',          'king-addons'), 12);
        $this->add_connector_section('bottom',       esc_html__('Bottom',       'king-addons'), 12);
        $this->add_connector_section('left_top',     esc_html__('Left Top',     'king-addons'), 18);
        $this->add_connector_section('left_bottom',  esc_html__('Left Bottom',  'king-addons'), 18);
        $this->add_connector_section('right_top',    esc_html__('Right Top',    'king-addons'), 18);
        $this->add_connector_section('right_bottom', esc_html__('Right Bottom', 'king-addons'), 18);

        // ── Style: Text Blocks ───────────────────────────────────────────
        $this->start_controls_section(
            'ka_values_circle_blocks_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Text Blocks', 'king-addons'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'ka_values_circle_block_width',
            [
                'label'      => esc_html__('Width', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range'      => [
                    'px'  => ['min' => 80,  'max' => 500, 'step' => 1],
                    '%'   => ['min' => 5,   'max' => 60,  'step' => 1],
                    'vw'  => ['min' => 5,   'max' => 40,  'step' => 0.5],
                ],
                'default'    => ['unit' => 'px', 'size' => 200],
                'selectors'  => [
                    '{{WRAPPER}} .king-addons-values-circle-item' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ── Style: Title Typography ──────────────────────────────────────
        $this->start_controls_section(
            'ka_values_circle_title_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Title', 'king-addons'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'ka_values_circle_title_color',
            [
                'label'     => esc_html__('Color', 'king-addons'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-values-circle-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'ka_values_circle_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-values-circle-title',
            ]
        );

        $this->end_controls_section();

        // ── Style: Divider ─────────────────────────────────────────────
        $this->start_controls_section(
            'ka_values_circle_divider_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Divider', 'king-addons'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'ka_values_circle_divider_color',
            [
                'label'     => esc_html__('Color', 'king-addons'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#8f8f8f',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-values-circle-divider' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'ka_values_circle_divider_width',
            [
                'label'      => esc_html__('Width', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => ['min' => 10, 'max' => 300, 'step' => 1],
                    '%'  => ['min' => 5,  'max' => 100, 'step' => 1],
                ],
                'default'    => ['unit' => 'px', 'size' => 130],
                'selectors'  => [
                    '{{WRAPPER}} .king-addons-values-circle-divider' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'ka_values_circle_divider_thickness',
            [
                'label'      => esc_html__('Thickness (px)', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 1, 'max' => 10, 'step' => 0.5]],
                'default'    => ['unit' => 'px', 'size' => 1.5],
                'selectors'  => [
                    '{{WRAPPER}} .king-addons-values-circle-divider' => 'border-top-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'ka_values_circle_divider_spacing',
            [
                'label'      => esc_html__('Spacing (top / bottom)', 'king-addons'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px'],
                'default'    => [
                    'top'    => '8',
                    'bottom' => '10',
                    'unit'   => 'px',
                    'isLinked' => false,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .king-addons-values-circle-divider' => 'margin-top: {{TOP}}{{UNIT}}; margin-bottom: {{BOTTOM}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ── Style: Description Typography ─────────────────────────────────
        $this->start_controls_section(
            'ka_values_circle_description_style',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Description', 'king-addons'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'ka_values_circle_description_color',
            [
                'label'     => esc_html__('Color', 'king-addons'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-values-circle-description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'ka_values_circle_description_typography',
                'selector' => '{{WRAPPER}} .king-addons-values-circle-description',
            ]
        );

        $this->end_controls_section();
    }

    private function add_item_section(
        string $prefix,
        string $slot,
        string $label,
        string $h_prop,
        int    $h_default,
        string $v_prop,
        int    $v_default,
        string $default_title,
        string $default_desc
    ): void {
        $sid      = str_replace('-', '_', $slot);
        $selector = '{{WRAPPER}} .king-addons-values-circle-item--' . $slot;

        $this->start_controls_section(
            'ka_values_circle_section_' . $prefix,
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . $label,
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        // ── Text ───────────────────────────────────────────────────────
        $this->add_control(
            'ka_values_circle_' . $prefix . '_title',
            [
                'label'       => esc_html__('Title', 'king-addons'),
                'type'        => Controls_Manager::TEXT,
                'default'     => $default_title,
                'label_block' => true,
            ]
        );

        $this->add_control(
            'ka_values_circle_' . $prefix . '_description',
            [
                'label'   => esc_html__('Description', 'king-addons'),
                'type'    => Controls_Manager::TEXTAREA,
                'default' => $default_desc,
            ]
        );

        // ── Position ──────────────────────────────────────────────────
        $this->add_control(
            'ka_values_circle_' . $prefix . '_pos_heading',
            [
                'label'     => esc_html__('Position', 'king-addons'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'ka_values_circle_pos_' . $sid . '_x',
            [
                'label'       => esc_html__('Horizontal offset', 'king-addons'),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => ['px'],
                'range'       => ['px' => ['min' => -260, 'max' => 260, 'step' => 1]],
                'default'     => ['unit' => 'px', 'size' => $h_default],
                'selectors'   => [$selector => $h_prop . ': {{SIZE}}{{UNIT}};'],
            ]
        );

        $this->add_responsive_control(
            'ka_values_circle_pos_' . $sid . '_y',
            [
                'label'       => esc_html__('Vertical offset', 'king-addons'),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => ['px'],
                'range'       => ['px' => ['min' => -40, 'max' => 860, 'step' => 1]],
                'default'     => ['unit' => 'px', 'size' => $v_default],
                'selectors'   => [$selector => $v_prop . ': {{SIZE}}{{UNIT}};'],
            ]
        );

        $this->end_controls_section();
    }

    private function add_connector_section(string $id, string $label, int $default_bend): void
    {
        $key = str_replace('-', '_', $id);
        $hid = str_replace('_', '-', $id);

        $this->start_controls_section(
            'ka_values_circle_conn_style_' . $key,
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Connector: ', 'king-addons') . $label,
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'ka_conn_' . $key . '_bend',
            [
                'label'      => esc_html__('Bend (px)', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 0, 'max' => 160, 'step' => 1]],
                'default'    => ['unit' => 'px', 'size' => $default_bend],
            ]
        );

        $this->add_control(
            'ka_conn_' . $key . '_start_x',
            [
                'label'      => esc_html__('Start X offset (px)', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => -200, 'max' => 200, 'step' => 1]],
                'default'    => ['unit' => 'px', 'size' => 0],
            ]
        );

        $this->add_control(
            'ka_conn_' . $key . '_start_y',
            [
                'label'      => esc_html__('Start Y offset (px)', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => -200, 'max' => 200, 'step' => 1]],
                'default'    => ['unit' => 'px', 'size' => 0],
            ]
        );

        $this->add_control(
            'ka_conn_' . $key . '_end_x',
            [
                'label'      => esc_html__('End X offset (px)', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => -200, 'max' => 200, 'step' => 1]],
                'default'    => ['unit' => 'px', 'size' => 0],
            ]
        );

        $this->add_control(
            'ka_conn_' . $key . '_end_y',
            [
                'label'      => esc_html__('End Y offset (px)', 'king-addons'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => -200, 'max' => 200, 'step' => 1]],
                'default'    => ['unit' => 'px', 'size' => 0],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();

        $gap            = (float) ($settings['ka_values_circle_conn_gap']['size']           ?? 20);
        $r_small        = (float) ($settings['ka_values_circle_dot_r_small']['size']        ?? 5);
        $r_large        = (float) ($settings['ka_values_circle_dot_r_large']['size']        ?? 9.5);
        $mob_r_small    = (float) ($settings['ka_values_circle_mobile_dot_r_small']['size'] ?? 5.5);
        $mob_r_large    = (float) ($settings['ka_values_circle_mobile_dot_r_large']['size'] ?? 8);
        $mob_conn_gap   = (float) ($settings['ka_values_circle_mobile_conn_gap']['size']    ?? 6);
        $mob_dot_step   = (float) ($settings['ka_values_circle_mobile_dot_step']['size']    ?? 28);

        $conn_ids      = ['top', 'bottom', 'left_top', 'left_bottom', 'right_top', 'right_bottom'];
        $conn_defaults = ['top' => 12, 'bottom' => 12, 'left_top' => 18, 'left_bottom' => 18, 'right_top' => 18, 'right_bottom' => 18];
        $conn_attrs    = '';
        foreach ($conn_ids as $cid) {
            $hid           = str_replace('_', '-', $cid);
            $bend          = (float) ($settings['ka_conn_' . $cid . '_bend']['size']    ?? $conn_defaults[$cid]);
            $start_x       = (float) ($settings['ka_conn_' . $cid . '_start_x']['size'] ?? 0);
            $start_y       = (float) ($settings['ka_conn_' . $cid . '_start_y']['size'] ?? 0);
            $end_x         = (float) ($settings['ka_conn_' . $cid . '_end_x']['size']   ?? 0);
            $end_y         = (float) ($settings['ka_conn_' . $cid . '_end_y']['size']   ?? 0);
            $conn_attrs   .= sprintf(
                ' data-conn-%1$s-bend="%2$s" data-conn-%1$s-start-x="%3$s" data-conn-%1$s-start-y="%4$s" data-conn-%1$s-end-x="%5$s" data-conn-%1$s-end-y="%6$s"',
                esc_attr($hid),
                esc_attr($bend),
                esc_attr($start_x),
                esc_attr($start_y),
                esc_attr($end_x),
                esc_attr($end_y)
            );
        }

        $items = [
            ['slot' => 'top-left',     'prefix' => 'top_left'],
            ['slot' => 'top-right',    'prefix' => 'top_right'],
            ['slot' => 'middle-left',  'prefix' => 'middle_left'],
            ['slot' => 'middle-right', 'prefix' => 'middle_right'],
            ['slot' => 'bottom-left',  'prefix' => 'bottom_left'],
            ['slot' => 'bottom-right', 'prefix' => 'bottom_right'],
        ];

        foreach ($items as &$item) {
            $p               = $item['prefix'];
            $item['title']       = (string) ($settings['ka_values_circle_' . $p . '_title']       ?? '');
            $item['description'] = (string) ($settings['ka_values_circle_' . $p . '_description'] ?? '');
        }
        unset($item);

        // ── Centre-image vars (used in both desktop and mobile views) ──────────
        $center_type = $settings['ka_values_circle_center_type'] ?? 'image';
        $center_img  = $settings['ka_values_circle_image'] ?? [];
        $lottie_json = '';
        $lottie_cfg  = '';
        if ($center_type === 'lottie') {
            $lottie_source = $settings['ka_values_circle_lottie_source'] ?? 'file';
            $lottie_json   = ($lottie_source === 'url')
                ? esc_url($settings['ka_values_circle_lottie_url'] ?? '')
                : esc_url($settings['ka_values_circle_lottie_file']['url'] ?? '');
            $lottie_json   = $lottie_json ?: KING_ADDONS_URL . 'includes/assets/libraries/lottie/default.json';
            $lottie_cfg    = esc_attr(json_encode([
                'autoplay' => $settings['ka_values_circle_lottie_autoplay'] ?? 'yes',
                'loop'     => $settings['ka_values_circle_lottie_loop']     ?? 'yes',
                'speed'    => (float) ($settings['ka_values_circle_lottie_speed'] ?? 1),
                'renderer' => $settings['ka_values_circle_lottie_renderer'] ?? 'svg',
                'reverse'  => $settings['ka_values_circle_lottie_reverse']  ?? '',
            ]));
        }

        ?>
        <div class="king-addons-values-circle-wrap"
             data-conn-gap="<?php echo esc_attr($gap); ?>"
             data-dot-r-small="<?php echo esc_attr($r_small); ?>"
             data-dot-r-large="<?php echo esc_attr($r_large); ?>"
             data-mobile-dot-r-small="<?php echo esc_attr($mob_r_small); ?>"
             data-mobile-dot-r-large="<?php echo esc_attr($mob_r_large); ?>"
             data-mobile-conn-gap="<?php echo esc_attr($mob_conn_gap); ?>"
             data-mobile-dot-step="<?php echo esc_attr($mob_dot_step); ?>"
             <?php echo $conn_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped above ?>>
            <div class="king-addons-values-circle-center-image-wrap">
                <?php if ($center_type === 'lottie') : ?>
                    <div class="king-addons-values-circle-lottie"
                         data-settings="<?php echo $lottie_cfg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped ?>"
                         data-json-url="<?php echo esc_url($lottie_json); ?>"
                         aria-hidden="true"></div>
                <?php elseif ($center_type === 'image' && !empty($center_img['url'])) : ?>
                    <img src="<?php echo esc_url($center_img['url']); ?>"
                         alt=""
                         loading="lazy">
                <?php else : ?>
                    <div class="king-addons-values-circle-image-placeholder" aria-hidden="true"></div>
                <?php endif; ?>
            </div>
            <div class="king-addons-values-circle-svg-layer" aria-hidden="true">
                <svg viewBox="0 0 1000 860" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg">
                    <g class="ka-values-circle-connectors ka-values-circle-connectors-dynamic"></g>
                </svg>
            </div>

            <?php foreach ($items as $item) : ?>
                <div class="king-addons-values-circle-item king-addons-values-circle-item--<?php echo esc_attr($item['slot']); ?>">
                    <h3 class="king-addons-values-circle-title"><?php echo esc_html($item['title']); ?></h3>
                    <span class="king-addons-values-circle-divider" aria-hidden="true"></span>
                    <div class="king-addons-values-circle-description"><?php echo wp_kses_post(nl2br(esc_html($item['description']))); ?></div>
                </div>
            <?php endforeach; ?>

            <?php // ── Mobile-only vertical strip ────────────────────────────────── ?>
            <div class="king-addons-values-circle-mobile-view">

                <?php // Centre image at the top ?>
                <div class="king-addons-values-circle-mobile-image">
                    <?php if ($center_type === 'lottie') : ?>
                        <div class="king-addons-values-circle-lottie"
                             data-settings="<?php echo $lottie_cfg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped ?>"
                             data-json-url="<?php echo esc_url($lottie_json); ?>"
                             aria-hidden="true"></div>
                    <?php elseif ($center_type === 'image' && !empty($center_img['url'])) : ?>
                        <img src="<?php echo esc_url($center_img['url']); ?>" alt="" loading="lazy">
                    <?php else : ?>
                        <div class="king-addons-values-circle-image-placeholder" aria-hidden="true"></div>
                    <?php endif; ?>
                </div>

                <?php foreach ($items as $idx => $item) :
                    $conn_class = ($idx === 0) ? 'ka-vcm-connector--first' : 'ka-vcm-connector--between';
                ?>
                    <svg class="ka-vcm-connector <?php echo esc_attr($conn_class); ?>" viewBox="0 0 100 60"
                         xmlns="http://www.w3.org/2000/svg"
                         aria-hidden="true" focusable="false">
                        <g class="ka-vcm-dots ka-values-circle-connectors"></g>
                    </svg>
                    <div class="king-addons-values-circle-mobile-card">
                        <h3 class="king-addons-values-circle-title"><?php echo esc_html($item['title']); ?></h3>
                        <span class="king-addons-values-circle-divider" aria-hidden="true"></span>
                        <div class="king-addons-values-circle-description"><?php echo wp_kses_post(nl2br(esc_html($item['description']))); ?></div>
                    </div>
                <?php endforeach; ?>

            </div><!-- /.king-addons-values-circle-mobile-view -->

        </div>
        <?php
    }
}
