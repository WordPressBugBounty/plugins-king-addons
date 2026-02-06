<?php
/**
 * KPI Tiles with Microcharts Widget (Free).
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
use Elementor\Repeater;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders KPI tiles with trend deltas and sparklines.
 */
class KPI_Tiles_Microcharts extends Widget_Base
{
    /**
     * Get widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-kpi-tiles-microcharts';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('KPI Tiles with Microcharts', 'king-addons');
    }

    /**
     * Get widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-kpi-tiles-microcharts';
    }

    /**
     * Enqueue script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-kpi-tiles-microcharts-script',
        ];
    }

    /**
     * Enqueue style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-kpi-tiles-microcharts-style',
        ];
    }

    /**
     * Get widget categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Get widget keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['kpi', 'tiles', 'microcharts', 'sparkline', 'metrics', 'dashboard', 'stats'];
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
    public function register_controls(): void
    {
        $this->register_content_controls();
        $this->register_layout_controls();
        $this->register_sparkline_controls();
        $this->register_style_layout_controls();
        $this->register_style_tile_controls();
        $this->register_style_title_controls();
        $this->register_style_value_controls();
        $this->register_style_delta_controls();
        $this->register_style_sparkline_controls();
        $this->register_style_footer_controls();
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
        $this->render_output($settings);
    }

    /**
     * Register content controls.
     *
     * @return void
     */
    protected function register_content_controls(): void
    {
        $this->start_controls_section(
            'kng_kpi_tiles_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('KPI Tiles', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_tile_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Revenue', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_tile_value',
            [
                'label' => esc_html__('Value', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'step' => 0.01,
                'default' => 128000,
            ]
        );

        $repeater->add_control(
            'kng_tile_prefix',
            [
                'label' => esc_html__('Value Prefix', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '$',
            ]
        );

        $repeater->add_control(
            'kng_tile_suffix',
            [
                'label' => esc_html__('Value Suffix', 'king-addons'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $repeater->add_control(
            'kng_tile_decimals',
            [
                'label' => esc_html__('Decimals', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 4,
                'step' => 1,
                'default' => 0,
            ]
        );

        $repeater->add_control(
            'kng_tile_separator',
            [
                'label' => esc_html__('Thousands Separator', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'auto' => esc_html__('Auto', 'king-addons'),
                    'comma' => esc_html__('Comma', 'king-addons'),
                    'dot' => esc_html__('Dot', 'king-addons'),
                    'space' => esc_html__('Space', 'king-addons'),
                ],
                'default' => 'auto',
            ]
        );

        $repeater->add_control(
            'kng_tile_show_delta',
            [
                'label' => esc_html__('Show Delta', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        $repeater->add_control(
            'kng_tile_trend',
            [
                'label' => esc_html__('Trend Direction', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'up' => esc_html__('Up', 'king-addons'),
                    'down' => esc_html__('Down', 'king-addons'),
                    'flat' => esc_html__('Flat', 'king-addons'),
                ],
                'default' => 'up',
                'condition' => [
                    'kng_tile_show_delta' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'kng_tile_delta_type',
            [
                'label' => esc_html__('Delta Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'percent' => esc_html__('Percent', 'king-addons'),
                    'absolute' => esc_html__('Absolute', 'king-addons'),
                ],
                'default' => 'percent',
                'condition' => [
                    'kng_tile_show_delta' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'kng_tile_delta_value',
            [
                'label' => esc_html__('Delta Value', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'step' => 0.01,
                'default' => 12,
                'condition' => [
                    'kng_tile_show_delta' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'kng_tile_delta_decimals',
            [
                'label' => esc_html__('Delta Decimals', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 4,
                'step' => 1,
                'default' => 0,
                'condition' => [
                    'kng_tile_show_delta' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'kng_tile_delta_separator',
            [
                'label' => esc_html__('Delta Thousands Separator', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'auto' => esc_html__('Auto', 'king-addons'),
                    'comma' => esc_html__('Comma', 'king-addons'),
                    'dot' => esc_html__('Dot', 'king-addons'),
                    'space' => esc_html__('Space', 'king-addons'),
                ],
                'default' => 'auto',
                'condition' => [
                    'kng_tile_show_delta' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'kng_tile_delta_prefix',
            [
                'label' => esc_html__('Delta Prefix', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'kng_tile_show_delta' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'kng_tile_delta_suffix',
            [
                'label' => esc_html__('Delta Suffix', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'kng_tile_show_delta' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'kng_tile_period',
            [
                'label' => esc_html__('Period Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('vs last month', 'king-addons'),
                'label_block' => true,
                'condition' => [
                    'kng_tile_show_delta' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'kng_tile_show_sparkline',
            [
                'label' => esc_html__('Show Sparkline', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        $repeater->add_control(
            'kng_tile_sparkline',
            [
                'label' => esc_html__('Sparkline Values', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 3,
                'default' => '12, 14, 13, 18, 21, 19, 26',
                'description' => esc_html__('Comma separated numbers. Min 2, max 50.', 'king-addons'),
                'condition' => [
                    'kng_tile_show_sparkline' => 'yes',
                ],
            ]
        );

        $repeater->add_control(
            'kng_tile_secondary',
            [
                'label' => esc_html__('Secondary Value', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('ARR 1.2M', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_tile_badge',
            [
                'label' => esc_html__('Badge', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('New', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_tile_link',
            [
                'label' => esc_html__('Tile Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_kpi_tiles',
            [
                'label' => esc_html__('Tiles', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'kng_tile_title' => esc_html__('Revenue', 'king-addons'),
                        'kng_tile_value' => 128000,
                        'kng_tile_prefix' => '$',
                        'kng_tile_suffix' => '',
                        'kng_tile_decimals' => 0,
                        'kng_tile_separator' => 'comma',
                        'kng_tile_show_delta' => 'yes',
                        'kng_tile_trend' => 'up',
                        'kng_tile_delta_type' => 'percent',
                        'kng_tile_delta_value' => 12,
                        'kng_tile_delta_decimals' => 0,
                        'kng_tile_delta_separator' => 'auto',
                        'kng_tile_delta_prefix' => '',
                        'kng_tile_delta_suffix' => '',
                        'kng_tile_period' => esc_html__('vs last month', 'king-addons'),
                        'kng_tile_show_sparkline' => 'yes',
                        'kng_tile_sparkline' => '12, 14, 13, 18, 21, 19, 26',
                        'kng_tile_secondary' => esc_html__('ARR 1.2M', 'king-addons'),
                        'kng_tile_badge' => esc_html__('New', 'king-addons'),
                    ],
                    [
                        'kng_tile_title' => esc_html__('Conversion', 'king-addons'),
                        'kng_tile_value' => 3.2,
                        'kng_tile_prefix' => '',
                        'kng_tile_suffix' => '%',
                        'kng_tile_decimals' => 1,
                        'kng_tile_separator' => 'auto',
                        'kng_tile_show_delta' => 'yes',
                        'kng_tile_trend' => 'up',
                        'kng_tile_delta_type' => 'percent',
                        'kng_tile_delta_value' => 0.4,
                        'kng_tile_delta_decimals' => 1,
                        'kng_tile_delta_separator' => 'auto',
                        'kng_tile_delta_prefix' => '',
                        'kng_tile_delta_suffix' => '',
                        'kng_tile_period' => esc_html__('vs last week', 'king-addons'),
                        'kng_tile_show_sparkline' => 'yes',
                        'kng_tile_sparkline' => '2.6, 2.9, 3.1, 2.8, 3.4, 3.2',
                        'kng_tile_secondary' => '',
                        'kng_tile_badge' => '',
                    ],
                    [
                        'kng_tile_title' => esc_html__('MRR', 'king-addons'),
                        'kng_tile_value' => 42000,
                        'kng_tile_prefix' => '$',
                        'kng_tile_suffix' => '',
                        'kng_tile_decimals' => 0,
                        'kng_tile_separator' => 'comma',
                        'kng_tile_show_delta' => 'yes',
                        'kng_tile_trend' => 'up',
                        'kng_tile_delta_type' => 'percent',
                        'kng_tile_delta_value' => 6,
                        'kng_tile_delta_decimals' => 0,
                        'kng_tile_delta_separator' => 'auto',
                        'kng_tile_delta_prefix' => '',
                        'kng_tile_delta_suffix' => '',
                        'kng_tile_period' => esc_html__('MoM', 'king-addons'),
                        'kng_tile_show_sparkline' => 'yes',
                        'kng_tile_sparkline' => '32, 34, 36, 38, 40, 42',
                        'kng_tile_secondary' => esc_html__('Goal 50k', 'king-addons'),
                        'kng_tile_badge' => '',
                    ],
                    [
                        'kng_tile_title' => esc_html__('NPS', 'king-addons'),
                        'kng_tile_value' => 58,
                        'kng_tile_prefix' => '',
                        'kng_tile_suffix' => '',
                        'kng_tile_decimals' => 0,
                        'kng_tile_separator' => 'auto',
                        'kng_tile_show_delta' => 'yes',
                        'kng_tile_trend' => 'flat',
                        'kng_tile_delta_type' => 'absolute',
                        'kng_tile_delta_value' => 0,
                        'kng_tile_delta_decimals' => 0,
                        'kng_tile_delta_separator' => 'auto',
                        'kng_tile_delta_prefix' => '',
                        'kng_tile_delta_suffix' => '',
                        'kng_tile_period' => esc_html__('QoQ', 'king-addons'),
                        'kng_tile_show_sparkline' => 'yes',
                        'kng_tile_sparkline' => '52, 54, 55, 56, 57, 58',
                        'kng_tile_secondary' => '',
                        'kng_tile_badge' => '',
                    ],
                ],
                'title_field' => '{{{ kng_tile_title }}}',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register layout controls.
     *
     * @return void
     */
    protected function register_layout_controls(): void
    {
        $this->start_controls_section(
            'kng_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'kng_columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'step' => 1,
                'default' => 4,
                'tablet_default' => 2,
                'mobile_default' => 1,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_grid_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'size' => 20,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_equal_height',
            [
                'label' => esc_html__('Equal Height', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_responsive_control(
            'kng_tile_min_height',
            [
                'label' => esc_html__('Tile Min Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 600,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__card' => 'min-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_content_alignment',
            [
                'label' => esc_html__('Content Alignment', 'king-addons'),
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
                'toggle' => false,
                'default' => 'left',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register sparkline controls.
     *
     * @return void
     */
    protected function register_sparkline_controls(): void
    {
        $this->start_controls_section(
            'kng_sparkline_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Sparkline', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_sparkline_smooth',
            [
                'label' => esc_html__('Smoothing', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_sparkline_last_dot',
            [
                'label' => esc_html__('Show Last Dot', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_sparkline_zero_baseline',
            [
                'label' => esc_html__('Zero Baseline', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register grid style controls.
     *
     * @return void
     */
    protected function register_style_layout_controls(): void
    {
        $this->start_controls_section(
            'kng_style_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Wall / Grid', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_grid_background',
                'selector' => '{{WRAPPER}} .king-addons-kpi-tiles',
            ]
        );

        $this->add_responsive_control(
            'kng_grid_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register tile style controls.
     *
     * @return void
     */
    protected function register_style_tile_controls(): void
    {
        $this->start_controls_section(
            'kng_style_tile_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Tile', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_tile_background',
                'selector' => '{{WRAPPER}} .king-addons-kpi-tiles__card',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_tile_border',
                'selector' => '{{WRAPPER}} .king-addons-kpi-tiles__card',
            ]
        );

        $this->add_control(
            'kng_tile_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_tile_shadow',
                'selector' => '{{WRAPPER}} .king-addons-kpi-tiles__card',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_tile_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-kpi-tiles__card:hover',
            ]
        );

        $this->add_control(
            'kng_tile_hover_lift',
            [
                'label' => esc_html__('Hover Lift', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                ],
                'default' => [
                    'size' => 4,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__card:hover' => 'transform: translateY(-{{SIZE}}{{UNIT}});',
                ],
            ]
        );

        $this->add_control(
            'kng_tile_content_gap',
            [
                'label' => esc_html__('Content Gap', 'king-addons'),
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
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__card' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tile_padding',
            [
                'label' => esc_html__('Inner Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register title style controls.
     *
     * @return void
     */
    protected function register_style_title_controls(): void
    {
        $this->start_controls_section(
            'kng_style_title_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Title', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-kpi-tiles__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register value style controls.
     *
     * @return void
     */
    protected function register_style_value_controls(): void
    {
        $this->start_controls_section(
            'kng_style_value_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Value', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_value_typography',
                'selector' => '{{WRAPPER}} .king-addons-kpi-tiles__value',
            ]
        );

        $this->add_control(
            'kng_value_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__value' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_value_affix_typography',
                'selector' => '{{WRAPPER}} .king-addons-kpi-tiles__value-prefix, {{WRAPPER}} .king-addons-kpi-tiles__value-suffix',
            ]
        );

        $this->add_control(
            'kng_value_affix_color',
            [
                'label' => esc_html__('Prefix/Suffix Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__value-prefix, {{WRAPPER}} .king-addons-kpi-tiles__value-suffix' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register delta style controls.
     *
     * @return void
     */
    protected function register_style_delta_controls(): void
    {
        $this->start_controls_section(
            'kng_style_delta_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Delta', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_delta_typography',
                'selector' => '{{WRAPPER}} .king-addons-kpi-tiles__delta',
            ]
        );

        $this->add_control(
            'kng_delta_gap',
            [
                'label' => esc_html__('Delta Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 24,
                    ],
                ],
                'default' => [
                    'size' => 6,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__delta' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_delta_icon_size',
            [
                'label' => esc_html__('Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 32,
                    ],
                ],
                'default' => [
                    'size' => 16,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__delta-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_delta_up_color',
            [
                'label' => esc_html__('Positive Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--kng-kpi-up-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_delta_down_color',
            [
                'label' => esc_html__('Negative Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--kng-kpi-down-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_delta_flat_color',
            [
                'label' => esc_html__('Neutral Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--kng-kpi-flat-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_period_typography',
                'selector' => '{{WRAPPER}} .king-addons-kpi-tiles__delta-period',
            ]
        );

        $this->add_control(
            'kng_period_color',
            [
                'label' => esc_html__('Period Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__delta-period' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register sparkline style controls.
     *
     * @return void
     */
    protected function register_style_sparkline_controls(): void
    {
        $this->start_controls_section(
            'kng_style_sparkline_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Sparkline', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_sparkline_color',
            [
                'label' => esc_html__('Stroke Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--kng-kpi-sparkline-color: {{VALUE}};',
                ],
                'description' => esc_html__('Leave empty to use trend colors.', 'king-addons'),
            ]
        );

        $this->add_responsive_control(
            'kng_sparkline_height',
            [
                'label' => esc_html__('Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 120,
                    ],
                ],
                'default' => [
                    'size' => 42,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__sparkline-svg' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_sparkline_stroke_width',
            [
                'label' => esc_html__('Stroke Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 6,
                    ],
                ],
                'default' => [
                    'size' => 2,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__sparkline-line' => 'stroke-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_sparkline_dot_size',
            [
                'label' => esc_html__('Dot Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 10,
                    ],
                ],
                'default' => [
                    'size' => 3,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__sparkline-dot' => 'r: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register footer meta style controls.
     *
     * @return void
     */
    protected function register_style_footer_controls(): void
    {
        $this->start_controls_section(
            'kng_style_footer_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Footer Meta', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_secondary_typography',
                'selector' => '{{WRAPPER}} .king-addons-kpi-tiles__secondary',
            ]
        );

        $this->add_control(
            'kng_secondary_color',
            [
                'label' => esc_html__('Secondary Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__secondary' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_badge_typography',
                'selector' => '{{WRAPPER}} .king-addons-kpi-tiles__badge',
            ]
        );

        $this->add_control(
            'kng_badge_color',
            [
                'label' => esc_html__('Badge Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__badge' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_badge_background',
            [
                'label' => esc_html__('Badge Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__badge' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_badge_radius',
            [
                'label' => esc_html__('Badge Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-kpi-tiles__badge' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render pro upgrade notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'kpi-tiles-microcharts', [
                'CSV and JSON data sources with refresh controls',
                'Advanced locale and currency formatting',
                'Animated value changes and sparkline morphs',
                'Enhanced microcharts with area fills and gradients',
            ]);
        }
    }

    /**
     * Render widget output.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return void
     */
    protected function render_output(array $settings): void
    {
        $tiles = $settings['kng_kpi_tiles'] ?? [];
        if (empty($tiles)) {
            if ($this->is_editor()) {
                echo '<div class="king-addons-kpi-tiles__empty">' . esc_html__('Add KPI tiles to get started.', 'king-addons') . '</div>';
            }
            return;
        }

        $wrapper_classes = $this->get_wrapper_classes($settings);
        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>">
            <div class="king-addons-kpi-tiles__grid">
                <?php foreach ($tiles as $tile) : ?>
                    <?php $this->render_tile($settings, $tile); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render a single tile.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param array<string, mixed> $tile     Tile data.
     * @return void
     */
    protected function render_tile(array $settings, array $tile): void
    {
        $title = $tile['kng_tile_title'] ?? '';
        $prefix = $tile['kng_tile_prefix'] ?? '';
        $suffix = $tile['kng_tile_suffix'] ?? '';
        $decimals = isset($tile['kng_tile_decimals']) ? (int) $tile['kng_tile_decimals'] : 0;
        $separator = $this->sanitize_separator((string) ($tile['kng_tile_separator'] ?? 'auto'));
        $show_delta = ($tile['kng_tile_show_delta'] ?? 'yes') === 'yes';
        $trend = $this->sanitize_trend((string) ($tile['kng_tile_trend'] ?? 'up'));
        $delta_type = $this->sanitize_delta_type((string) ($tile['kng_tile_delta_type'] ?? 'percent'));
        $delta_decimals = isset($tile['kng_tile_delta_decimals']) ? (int) $tile['kng_tile_delta_decimals'] : $decimals;
        $delta_separator = isset($tile['kng_tile_delta_separator'])
            ? $this->sanitize_separator((string) $tile['kng_tile_delta_separator'])
            : $separator;
        $delta_prefix = $tile['kng_tile_delta_prefix'] ?? '';
        $delta_suffix = $tile['kng_tile_delta_suffix'] ?? '';
        $period_label = $tile['kng_tile_period'] ?? '';
        $secondary = $tile['kng_tile_secondary'] ?? '';
        $badge = $tile['kng_tile_badge'] ?? '';
        $show_sparkline = ($tile['kng_tile_show_sparkline'] ?? 'yes') === 'yes';

        $value = $this->format_number($tile['kng_tile_value'] ?? '', $decimals, $separator);
        $delta_value = $this->format_number($tile['kng_tile_delta_value'] ?? '', $delta_decimals, $delta_separator);
        if ('' !== $delta_value && 'percent' === $delta_type && '' === $delta_suffix) {
            $delta_value .= '%';
        }

        $sparkline_raw = '';
        $sparkline_values = [];
        $sparkline_svg = '';
        $sparkline_error = false;

        if ($show_sparkline) {
            $sparkline_raw = (string) ($tile['kng_tile_sparkline'] ?? '');
            $sparkline_values = $this->parse_sparkline_values($sparkline_raw);
            $sparkline_svg = $this->build_sparkline_svg($sparkline_values, $settings);
            $sparkline_error = $this->is_editor() && '' !== trim($sparkline_raw) && count($sparkline_values) < 2;
        }

        $card_attributes = $this->get_card_attributes($tile, $trend, $title);
        ?>
        <div <?php echo $this->build_html_attributes($card_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <?php if ('' !== $title) : ?>
                <div class="king-addons-kpi-tiles__title"><?php echo esc_html($title); ?></div>
            <?php endif; ?>

            <?php if ('' !== $value) : ?>
                <div class="king-addons-kpi-tiles__value">
                    <?php if ('' !== $prefix) : ?>
                        <span class="king-addons-kpi-tiles__value-prefix"><?php echo esc_html($prefix); ?></span>
                    <?php endif; ?>
                    <span class="king-addons-kpi-tiles__value-number"><?php echo esc_html($value); ?></span>
                    <?php if ('' !== $suffix) : ?>
                        <span class="king-addons-kpi-tiles__value-suffix"><?php echo esc_html($suffix); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($show_delta && ('' !== $delta_value || '' !== $period_label)) : ?>
                <div class="king-addons-kpi-tiles__delta king-addons-kpi-tiles__delta--<?php echo esc_attr($trend); ?>">
                    <span class="king-addons-kpi-tiles__delta-icon" aria-hidden="true">
                        <?php echo $this->get_trend_icon($trend); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </span>
                    <?php if ('' !== $delta_value) : ?>
                        <?php if ('' !== $delta_prefix) : ?>
                            <span class="king-addons-kpi-tiles__delta-prefix"><?php echo esc_html($delta_prefix); ?></span>
                        <?php endif; ?>
                        <span class="king-addons-kpi-tiles__delta-value"><?php echo esc_html($delta_value); ?></span>
                        <?php if ('' !== $delta_suffix) : ?>
                            <span class="king-addons-kpi-tiles__delta-suffix"><?php echo esc_html($delta_suffix); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ('' !== $period_label) : ?>
                        <span class="king-addons-kpi-tiles__delta-period"><?php echo esc_html($period_label); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($show_sparkline && $sparkline_error) : ?>
                <div class="king-addons-kpi-tiles__sparkline king-addons-kpi-tiles__sparkline--invalid">
                    <?php echo esc_html__('Sparkline needs at least 2 numeric values.', 'king-addons'); ?>
                </div>
            <?php elseif ($show_sparkline && '' !== $sparkline_svg) : ?>
                <div class="king-addons-kpi-tiles__sparkline">
                    <?php echo $sparkline_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endif; ?>

            <?php if ('' !== $secondary || '' !== $badge) : ?>
                <div class="king-addons-kpi-tiles__footer">
                    <?php if ('' !== $secondary) : ?>
                        <span class="king-addons-kpi-tiles__secondary"><?php echo esc_html($secondary); ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $badge) : ?>
                        <span class="king-addons-kpi-tiles__badge"><?php echo esc_html($badge); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Build wrapper classes.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return array<int, string>
     */
    protected function get_wrapper_classes(array $settings): array
    {
        $classes = ['king-addons-kpi-tiles'];

        $alignment = $settings['kng_content_alignment'] ?? 'left';
        $classes[] = 'king-addons-kpi-tiles--align-' . sanitize_html_class((string) $alignment);

        if (!empty($settings['kng_equal_height'])) {
            $classes[] = 'king-addons-kpi-tiles--equal-height';
        }

        $classes = array_merge($classes, $this->get_additional_wrapper_classes($settings));

        return array_filter($classes);
    }

    /**
     * Placeholder for pro wrapper classes.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return array<int, string>
     */
    protected function get_additional_wrapper_classes(array $settings): array
    {
        unset($settings);
        return [];
    }

    /**
     * Build card attributes.
     *
     * @param array<string, mixed> $tile  Tile data.
     * @param string               $trend Trend direction.
     * @param string               $title Tile title.
     * @return array<string, string>
     */
    protected function get_card_attributes(array $tile, string $trend, string $title): array
    {
        $classes = [
            'king-addons-kpi-tiles__card',
            'king-addons-kpi-tiles__card--' . $trend,
        ];

        $attributes = [
            'class' => implode(' ', $classes),
        ];

        $link = $tile['kng_tile_link'] ?? [];
        if (!empty($link['url'])) {
            $attributes['data-card-link'] = esc_url_raw($link['url']);
            $rel_parts = [];
            if (!empty($link['is_external'])) {
                $attributes['data-card-link-target'] = '_blank';
                $rel_parts[] = 'noopener';
                $rel_parts[] = 'noreferrer';
            }
            if (!empty($link['nofollow'])) {
                $rel_parts[] = 'nofollow';
            }
            if (!empty($rel_parts)) {
                $attributes['data-card-link-rel'] = implode(' ', array_values(array_unique($rel_parts)));
            }
            $attributes['tabindex'] = '0';
            $attributes['role'] = 'link';
            if ('' !== $title) {
                $attributes['aria-label'] = $title;
            }
            $classes[] = 'king-addons-kpi-tiles__card--link';
            $attributes['class'] = implode(' ', $classes);
        }

        return $attributes;
    }

    /**
     * Build HTML attributes string.
     *
     * @param array<string, string> $attributes Attributes.
     *
     * @return string
     */
    protected function build_html_attributes(array $attributes): string
    {
        $output = [];

        foreach ($attributes as $key => $value) {
            if ('' === $value) {
                continue;
            }
            $output[] = esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        return implode(' ', $output);
    }

    /**
     * Build sparkline SVG markup.
     *
     * @param array<int, float>     $values   Sparkline values.
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return string
     */
    protected function build_sparkline_svg(array $values, array $settings): string
    {
        if (count($values) < 2) {
            return '';
        }

        $width = 120.0;
        $height = 36.0;
        $padding = 2.0;

        $min = min($values);
        $max = max($values);

        if (!empty($settings['kng_sparkline_zero_baseline'])) {
            $min = min($min, 0);
            $max = max($max, 0);
        }

        if ($min === $max) {
            $min -= 1;
            $max += 1;
        }

        $range = $max - $min;
        $count = count($values);
        $points = [];

        foreach ($values as $index => $value) {
            $x = $count > 1
                ? ($index / ($count - 1)) * ($width - ($padding * 2)) + $padding
                : $width / 2;
            $y = $height - $padding - ((($value - $min) / $range) * ($height - ($padding * 2)));

            $points[] = [
                'x' => $this->format_point($x),
                'y' => $this->format_point($y),
            ];
        }

        $smooth = !empty($settings['kng_sparkline_smooth']);
        $line_markup = $smooth ? $this->build_smooth_path_markup($points) : $this->build_polyline_markup($points);
        $dot_markup = '';

        if (!empty($settings['kng_sparkline_last_dot'])) {
            $last = $points[$count - 1];
            $dot_markup = '<circle class="king-addons-kpi-tiles__sparkline-dot" cx="' . esc_attr($last['x']) . '" cy="' . esc_attr($last['y']) . '" r="3" />';
        }

        return '<svg class="king-addons-kpi-tiles__sparkline-svg" viewBox="0 0 120 36" preserveAspectRatio="none" aria-hidden="true">' . $line_markup . $dot_markup . '</svg>';
    }

    /**
     * Build smooth path markup.
     *
     * @param array<int, array<string, string>> $points Sparkline points.
     *
     * @return string
     */
    protected function build_smooth_path_markup(array $points): string
    {
        $count = count($points);
        if ($count < 2) {
            return '';
        }

        $d = 'M ' . $points[0]['x'] . ' ' . $points[0]['y'];

        for ($i = 1; $i < $count; $i++) {
            $prev = $points[$i - 1];
            $current = $points[$i];
            $mid_x = $this->format_point(((float) $prev['x'] + (float) $current['x']) / 2);
            $mid_y = $this->format_point(((float) $prev['y'] + (float) $current['y']) / 2);
            $d .= ' Q ' . $prev['x'] . ' ' . $prev['y'] . ' ' . $mid_x . ' ' . $mid_y;
        }

        $last = $points[$count - 1];
        $d .= ' T ' . $last['x'] . ' ' . $last['y'];

        return '<path class="king-addons-kpi-tiles__sparkline-line" d="' . esc_attr($d) . '" />';
    }

    /**
     * Build polyline markup.
     *
     * @param array<int, array<string, string>> $points Sparkline points.
     *
     * @return string
     */
    protected function build_polyline_markup(array $points): string
    {
        $pairs = [];
        foreach ($points as $point) {
            $pairs[] = $point['x'] . ',' . $point['y'];
        }

        return '<polyline class="king-addons-kpi-tiles__sparkline-line" points="' . esc_attr(implode(' ', $pairs)) . '" />';
    }

    /**
     * Format point value.
     *
     * @param float $value Raw point value.
     *
     * @return string
     */
    protected function format_point(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    /**
     * Parse sparkline values from string.
     *
     * @param string $raw_values Raw values string.
     *
     * @return array<int, float>
     */
    protected function parse_sparkline_values(string $raw_values): array
    {
        $raw_values = trim($raw_values);
        if ('' === $raw_values) {
            return [];
        }

        $parts = preg_split('/\s*,\s*/', $raw_values, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts)) {
            return [];
        }

        $values = [];
        foreach ($parts as $part) {
            $clean = trim((string) $part);
            if ('' === $clean || !is_numeric($clean)) {
                continue;
            }
            $values[] = (float) $clean;
        }

        if (count($values) > 50) {
            $values = array_slice($values, 0, 50);
        }

        return $values;
    }

    /**
     * Format numeric value.
     *
     * @param mixed  $value     Raw value.
     * @param int    $decimals  Decimals count.
     * @param string $separator Thousands separator option.
     *
     * @return string
     */
    protected function format_number($value, int $decimals, string $separator): string
    {
        if (!is_numeric($value)) {
            return '';
        }

        $number = (float) $value;
        $decimals = max(0, min(4, $decimals));
        $separator = $this->sanitize_separator($separator);

        if ('auto' === $separator) {
            return number_format_i18n($number, $decimals);
        }

        $thousand_separator = $this->get_thousand_separator($separator);
        $decimal_separator = $this->get_decimal_separator($separator);

        return number_format($number, $decimals, $decimal_separator, $thousand_separator);
    }

    /**
     * Get thousands separator character.
     *
     * @param string $separator Separator key.
     *
     * @return string
     */
    protected function get_thousand_separator(string $separator): string
    {
        switch ($separator) {
            case 'comma':
                return ',';
            case 'dot':
                return '.';
            case 'space':
                return ' ';
            default:
                return '';
        }
    }

    /**
     * Get decimal separator character.
     *
     * @param string $separator Separator key.
     *
     * @return string
     */
    protected function get_decimal_separator(string $separator): string
    {
        return 'dot' === $separator ? ',' : '.';
    }

    /**
     * Sanitize trend direction.
     *
     * @param string $trend Raw trend value.
     *
     * @return string
     */
    protected function sanitize_trend(string $trend): string
    {
        $allowed = ['up', 'down', 'flat'];
        return in_array($trend, $allowed, true) ? $trend : 'up';
    }

    /**
     * Sanitize delta type.
     *
     * @param string $delta_type Raw delta type.
     *
     * @return string
     */
    protected function sanitize_delta_type(string $delta_type): string
    {
        return 'absolute' === $delta_type ? 'absolute' : 'percent';
    }

    /**
     * Sanitize separator option.
     *
     * @param string $separator Raw separator.
     *
     * @return string
     */
    protected function sanitize_separator(string $separator): string
    {
        $allowed = ['auto', 'comma', 'dot', 'space'];
        return in_array($separator, $allowed, true) ? $separator : 'auto';
    }

    /**
     * Get trend icon SVG.
     *
     * @param string $trend Trend direction.
     *
     * @return string
     */
    protected function get_trend_icon(string $trend): string
    {
        switch ($trend) {
            case 'down':
                return '<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 3v12M10 15l-5-5M10 15l5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>';
            case 'flat':
                return '<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M3 10h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>';
            case 'up':
            default:
                return '<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 17V5M10 5l-5 5M10 5l5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>';
        }
    }

    /**
     * Check if in Elementor editor.
     *
     * @return bool
     */
    protected function is_editor(): bool
    {
        return class_exists(Plugin::class) && Plugin::$instance->editor->is_edit_mode();
    }
}
