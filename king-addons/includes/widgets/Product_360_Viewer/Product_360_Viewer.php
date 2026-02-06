<?php
/**
 * 360 Product Viewer Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the 360 Product Viewer widget.
 */
class Product_360_Viewer extends Widget_Base
{
    private const FREE_MAX_FRAMES = 36;
    private const DEFAULT_ASPECT_RATIO = 1.2;
    private const DEFAULT_DRAG_SENSITIVITY = 8;
    private const DEFAULT_AUTOPLAY_SPEED = 90;
    private const DEFAULT_ZOOM_SCALE = 1.35;
    private const DEFAULT_INERTIA = 0.08;

    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-product-360-viewer';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('360° Product Viewer', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-product-360-viewer';
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-product-360-viewer-script',
        ];
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-product-360-viewer-style',
        ];
    }

    /**
     * Widget categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Widget keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['360', '3d', 'viewer', 'product', 'spin', 'rotate'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        /**
     * Register Elementor controls.
     *
     * @return void
     */
    public function register_controls(): void
    {
        $this->register_content_frames_controls(false);
        $this->register_behavior_controls(false);
        $this->register_ui_controls(false);
        $this->register_hotspot_controls(false);
        $this->register_style_viewer_controls();
        $this->register_style_loader_controls();
        $this->register_style_hotspot_controls(false);
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
        $frames = $this->prepare_frames($settings, false);

        if (empty($frames)) {
            return;
        }

        $hotspots = $this->prepare_hotspots($settings, false, count($frames));

        $this->render_output($settings, $frames, $hotspots, false);
    }

    /**
     * Register frames repeater controls.
     *
     * @param bool $is_pro Whether Pro controls should be unlocked.
     *
     * @return void
     */
    public function register_content_frames_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_frames_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Frames', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_frame_image',
            [
                'label' => esc_html__('Frame Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $repeater->add_control(
            'kng_frame_label',
            [
                'label' => esc_html__('Label (optional)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'default' => esc_html__('Frame', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_frame_alt',
            [
                'label' => esc_html__('Alt Text (optional)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
            ]
        );

        $this->add_control(
            'kng_frames',
            [
                'label' => esc_html__('Frames', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => $this->get_default_frames(),
                'title_field' => '{{{ kng_frame_label }}}',
            ]
        );

        if (!$is_pro) {
            $this->add_control(
                'kng_frames_limit_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => sprintf(
                        /* translators: %d: maximum frames in the free version. */
                        esc_html__('Free version renders up to %d frames. Add more with Pro.', 'king-addons'),
                        self::FREE_MAX_FRAMES
                    ),
                    'content_classes' => 'king-addons-pro-notice',
                ]
            );
        }

        $this->end_controls_section();
    }

    /**
     * Register behavior and motion controls.
     *
     * @param bool $is_pro Whether Pro controls should be unlocked.
     *
     * @return void
     */
    public function register_behavior_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_behavior_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Viewer Behavior', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'kng_aspect_ratio',
            [
                'label' => esc_html__('Aspect Ratio', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0.5,
                        'max' => 2.5,
                        'step' => 0.05,
                    ],
                ],
                'default' => [
                    'size' => self::DEFAULT_ASPECT_RATIO,
                ],
            ]
        );

        $this->add_control(
            'kng_start_frame',
            [
                'label' => esc_html__('Start From Frame', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 200,
                'step' => 1,
                'default' => 1,
            ]
        );

        $this->add_control(
            'kng_autoplay',
            [
                'label' => esc_html__('Autoplay', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_autoplay_speed',
            [
                'label' => esc_html__('Autoplay Speed (ms/frame)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 400,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'size' => self::DEFAULT_AUTOPLAY_SPEED,
                ],
                'condition' => [
                    'kng_autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_autoplay_mode',
            [
                'label' => sprintf(
                    /* translators: %s: pro icon. */
                    esc_html__('Autoplay Mode %s', 'king-addons'),
                    '<i class="eicon-pro-icon"></i>'
                ),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'loop' => esc_html__('Loop', 'king-addons'),
                    'pingpong' => esc_html__('Ping Pong (Pro)', 'king-addons'),
                ],
                'default' => 'loop',
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );

        $this->add_control(
            'kng_drag_sensitivity',
            [
                'label' => esc_html__('Drag Sensitivity (px/frame)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 2,
                        'max' => 40,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => self::DEFAULT_DRAG_SENSITIVITY,
                ],
            ]
        );

        $this->add_control(
            'kng_invert_drag',
            [
                'label' => esc_html__('Invert Drag Direction', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'kng_drag_axis',
            [
                'label' => sprintf(
                    /* translators: %s: pro icon. */
                    esc_html__('Drag Axis %s', 'king-addons'),
                    '<i class="eicon-pro-icon"></i>'
                ),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'horizontal' => esc_html__('Horizontal', 'king-addons'),
                    'vertical' => esc_html__('Vertical (Pro)', 'king-addons'),
                    'both' => esc_html__('Both (Pro)', 'king-addons'),
                ],
                'default' => 'horizontal',
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );

        $this->add_control(
            'kng_scroll_wheel',
            [
                'label' => sprintf(
                    /* translators: %s: pro icon. */
                    esc_html__('Scroll to Rotate %s', 'king-addons'),
                    '<i class="eicon-pro-icon"></i>'
                ),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'no',
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );

        $this->add_control(
            'kng_inertia',
            [
                'label' => sprintf(
                    /* translators: %s: pro icon. */
                    esc_html__('Inertia %s', 'king-addons'),
                    '<i class="eicon-pro-icon"></i>'
                ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 0.3,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => self::DEFAULT_INERTIA,
                ],
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register UI controls for overlays and toolbar.
     *
     * @param bool $is_pro Whether Pro controls should be unlocked.
     *
     * @return void
     */
    public function register_ui_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_ui_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Viewer UI', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_show_hint',
            [
                'label' => esc_html__('Show Drag Hint', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_hint_text',
            [
                'label' => esc_html__('Hint Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Drag or swipe to rotate', 'king-addons'),
                'label_block' => true,
                'condition' => [
                    'kng_show_hint' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_show_loader',
            [
                'label' => esc_html__('Show Loader', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_loader_text',
            [
                'label' => esc_html__('Loader Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Loading frames...', 'king-addons'),
                'label_block' => true,
                'condition' => [
                    'kng_show_loader' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_show_progress',
            [
                'label' => esc_html__('Show Progress', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_zoom',
            [
                'label' => sprintf(
                    /* translators: %s: pro icon. */
                    esc_html__('Zoom Toggle %s', 'king-addons'),
                    '<i class="eicon-pro-icon"></i>'
                ),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'no',
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );

        $this->add_control(
            'kng_zoom_scale',
            [
                'label' => sprintf(
                    /* translators: %s: pro icon. */
                    esc_html__('Zoom Scale %s', 'king-addons'),
                    '<i class="eicon-pro-icon"></i>'
                ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1.05,
                        'max' => 2.5,
                        'step' => 0.05,
                    ],
                ],
                'default' => [
                    'size' => self::DEFAULT_ZOOM_SCALE,
                ],
                'classes' => 'king-addons-pro-control no-distance',
                'condition' => [
                    'kng_show_zoom' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer' => '--ka-360-zoom-scale: {{SIZE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register hotspot controls.
     *
     * @param bool $is_pro Whether Pro controls should be unlocked.
     *
     * @return void
     */
    public function register_hotspot_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_hotspots_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Hotspots', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        if (!$is_pro) {
            $this->add_control(
                'kng_hotspots_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => esc_html__('Frame-based hotspots are available in the Pro version.', 'king-addons'),
                    'content_classes' => 'king-addons-pro-notice',
                ]
            );
            $this->end_controls_section();
            return;
        }

        $hotspot_repeater = new Repeater();

        $hotspot_repeater->add_control(
            'kng_hotspot_label',
            [
                'label' => esc_html__('Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Detail', 'king-addons'),
                'label_block' => true,
            ]
        );

        $hotspot_repeater->add_control(
            'kng_hotspot_frame',
            [
                'label' => esc_html__('Visible On Frame', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 500,
                'step' => 1,
                'default' => 1,
            ]
        );

        $hotspot_repeater->add_control(
            'kng_hotspot_horizontal',
            [
                'label' => esc_html__('Horizontal (%)', 'king-addons'),
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
                    'size' => 50,
                    'unit' => '%',
                ],
            ]
        );

        $hotspot_repeater->add_control(
            'kng_hotspot_vertical',
            [
                'label' => esc_html__('Vertical (%)', 'king-addons'),
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
                    'size' => 50,
                    'unit' => '%',
                ],
            ]
        );

        $hotspot_repeater->add_control(
            'kng_hotspot_link',
            [
                'label' => esc_html__('Link (optional)', 'king-addons'),
                'type' => Controls_Manager::URL,
                'label_block' => true,
                'placeholder' => esc_html__('https://example.com', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'kng_hotspots',
            [
                'label' => esc_html__('Hotspots', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $hotspot_repeater->get_controls(),
                'title_field' => '{{{ kng_hotspot_label }}}',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register viewer style controls.
     *
     * @return void
     */
    public function register_style_viewer_controls(): void
    {
        $this->start_controls_section(
            'kng_style_viewer_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Viewer', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_viewer_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_viewer_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_viewer_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_viewer_border',
                'selector' => '{{WRAPPER}} .king-addons-product-360-viewer',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_viewer_shadow',
                'selector' => '{{WRAPPER}} .king-addons-product-360-viewer',
            ]
        );

        $this->add_control(
            'kng_image_fit',
            [
                'label' => esc_html__('Image Fit', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'contain' => esc_html__('Contain', 'king-addons'),
                    'cover' => esc_html__('Cover', 'king-addons'),
                ],
                'default' => 'contain',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer__frame' => 'object-fit: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register style controls for overlays and text.
     *
     * @return void
     */
    public function register_style_loader_controls(): void
    {
        $this->start_controls_section(
            'kng_style_overlay_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Overlays & Toolbar', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_hint_typography',
                'selector' => '{{WRAPPER}} .king-addons-product-360-viewer__hint, {{WRAPPER}} .king-addons-product-360-viewer__progress',
            ]
        );

        $this->add_control(
            'kng_hint_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer__hint' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_hint_background',
            [
                'label' => esc_html__('Hint Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer__hint' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_loader_color',
            [
                'label' => esc_html__('Loader Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer__loader' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_loader_background',
            [
                'label' => esc_html__('Loader Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer__loader' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_loader_padding',
            [
                'label' => esc_html__('Loader Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer__loader' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_progress_background',
            [
                'label' => esc_html__('Progress Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer__progress' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_progress_color',
            [
                'label' => esc_html__('Progress Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer__progress' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_toolbar_radius',
            [
                'label' => esc_html__('Overlay Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                        'step' => 1,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer__hint' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-product-360-viewer__progress' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-product-360-viewer__loader' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register hotspot style controls.
     *
     * @param bool $is_pro Whether Pro controls should be unlocked.
     *
     * @return void
     */
    public function register_style_hotspot_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_style_hotspot_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Hotspot Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        if (!$is_pro) {
            $this->add_control(
                'kng_style_hotspot_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => esc_html__('Style controls unlock with Pro hotspots.', 'king-addons'),
                    'content_classes' => 'king-addons-pro-notice',
                ]
            );
            $this->end_controls_section();
            return;
        }

        $this->add_control(
            'kng_hotspot_color',
            [
                'label' => esc_html__('Dot Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer__hotspot-dot' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_hotspot_label_color',
            [
                'label' => esc_html__('Label Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer__hotspot-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_hotspot_label_background',
            [
                'label' => esc_html__('Label Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer__hotspot-label' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_hotspot_size',
            [
                'label' => esc_html__('Dot Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 8,
                        'max' => 28,
                        'step' => 1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-product-360-viewer__hotspot' => '--ka-360-hotspot-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_hotspot_typography',
                'selector' => '{{WRAPPER}} .king-addons-product-360-viewer__hotspot-label',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render the viewer markup.
     *
     * @param array<string, mixed> $settings  Widget settings.
     * @param array<int, array<string, mixed>> $frames  Prepared frames.
     * @param array<int, array<string, mixed>> $hotspots Prepared hotspots.
     * @param bool                 $is_pro   Whether Pro rendering is active.
     *
     * @return void
     */
    public function render_output(array $settings, array $frames, array $hotspots, bool $is_pro): void
    {
        $frame_count = count($frames);
        if ($frame_count === 0) {
            return;
        }

        $start_frame = $this->clamp_int((int) ($settings['kng_start_frame'] ?? 1), 1, $frame_count);
        $start_index = $start_frame - 1;
        $start_frame_data = $frames[$start_index] ?? $frames[0];

        $aspect_ratio = $this->clamp_float((float) ($settings['kng_aspect_ratio']['size'] ?? self::DEFAULT_ASPECT_RATIO), 0.4, 3.5);
        $autoplay_speed = $this->clamp_float((float) ($settings['kng_autoplay_speed']['size'] ?? self::DEFAULT_AUTOPLAY_SPEED), 10, 1000);
        $drag_sensitivity = $this->clamp_float((float) ($settings['kng_drag_sensitivity']['size'] ?? self::DEFAULT_DRAG_SENSITIVITY), 2, 80);
        $inertia = $is_pro ? $this->clamp_float((float) ($settings['kng_inertia']['size'] ?? self::DEFAULT_INERTIA), 0, 1) : 0;
        $zoom_scale = $is_pro ? $this->clamp_float((float) ($settings['kng_zoom_scale']['size'] ?? self::DEFAULT_ZOOM_SCALE), 1.05, 3) : self::DEFAULT_ZOOM_SCALE;

        $container_attributes = [
            'data-frames' => esc_attr(wp_json_encode($frames)),
            'data-start-frame' => esc_attr((string) $start_frame),
            'data-aspect-ratio' => esc_attr((string) $aspect_ratio),
            'data-autoplay' => esc_attr($settings['kng_autoplay'] ?? 'no'),
            'data-autoplay-speed' => esc_attr((string) $autoplay_speed),
            'data-autoplay-mode' => esc_attr($is_pro ? ($settings['kng_autoplay_mode'] ?? 'loop') : 'loop'),
            'data-drag-sensitivity' => esc_attr((string) $drag_sensitivity),
            'data-invert-drag' => esc_attr($settings['kng_invert_drag'] ?? 'no'),
            'data-drag-axis' => esc_attr($is_pro ? ($settings['kng_drag_axis'] ?? 'horizontal') : 'horizontal'),
            'data-enable-scroll' => esc_attr($is_pro ? ($settings['kng_scroll_wheel'] ?? 'no') : 'no'),
            'data-inertia' => esc_attr((string) $inertia),
            'data-show-progress' => esc_attr($settings['kng_show_progress'] ?? 'yes'),
            'data-show-zoom' => esc_attr($is_pro ? ($settings['kng_show_zoom'] ?? 'no') : 'no'),
            'data-zoom-scale' => esc_attr((string) $zoom_scale),
            'data-hotspots' => $is_pro ? esc_attr(wp_json_encode($hotspots)) : '[]',
            'data-is-pro' => $is_pro ? 'true' : 'false',
        ];

        $wrapper_classes = ['king-addons-product-360-viewer'];
        if (($settings['kng_show_loader'] ?? 'yes') === 'yes') {
            $wrapper_classes[] = 'king-addons-product-360-viewer--loading';
        }

        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>" <?php echo $this->render_attributes_string($container_attributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <div class="king-addons-product-360-viewer__canvas" role="presentation" style="--ka-360-aspect: <?php echo esc_attr((string) $aspect_ratio); ?>;">
                <img
                    class="king-addons-product-360-viewer__frame"
                    src="<?php echo esc_url($start_frame_data['url']); ?>"
                    alt="<?php echo esc_attr($start_frame_data['alt'] ?: $start_frame_data['label']); ?>"
                    loading="lazy"
                />
                <?php if (($settings['kng_show_loader'] ?? 'yes') === 'yes') : ?>
                    <div class="king-addons-product-360-viewer__loader" aria-live="polite">
                        <?php echo esc_html($settings['kng_loader_text'] ?? esc_html__('Loading frames...', 'king-addons')); ?>
                    </div>
                <?php endif; ?>
                <?php if (($settings['kng_show_hint'] ?? 'yes') === 'yes') : ?>
                    <div class="king-addons-product-360-viewer__hint">
                        <?php echo esc_html($settings['kng_hint_text'] ?? esc_html__('Drag or swipe to rotate', 'king-addons')); ?>
                    </div>
                <?php endif; ?>
                <?php if (($settings['kng_show_progress'] ?? 'yes') === 'yes') : ?>
                    <div class="king-addons-product-360-viewer__progress" aria-live="polite">
                        <span class="king-addons-product-360-viewer__progress-value"><?php echo esc_html('1 / ' . $frame_count); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($is_pro && ($settings['kng_show_zoom'] ?? 'no') === 'yes') : ?>
                    <button type="button" class="king-addons-product-360-viewer__zoom" aria-pressed="false">
                        <?php echo esc_html__('Zoom', 'king-addons'); ?>
                    </button>
                <?php endif; ?>
            </div>
            <?php if ($is_pro && !empty($hotspots)) : ?>
                <div class="king-addons-product-360-viewer__hotspots" aria-hidden="true">
                    <?php foreach ($hotspots as $hotspot) : ?>
                        <button
                            type="button"
                            class="king-addons-product-360-viewer__hotspot"
                            data-frame="<?php echo esc_attr((string) $hotspot['frame']); ?>"
                            data-link="<?php echo esc_url($hotspot['link']); ?>"
                            data-new-tab="<?php echo esc_attr($hotspot['is_external'] ? 'true' : 'false'); ?>"
                            data-nofollow="<?php echo esc_attr($hotspot['nofollow'] ? 'true' : 'false'); ?>"
                            style="left: <?php echo esc_attr((string) $hotspot['x']); ?>%; top: <?php echo esc_attr((string) $hotspot['y']); ?>%;"
                        >
                            <span class="king-addons-product-360-viewer__hotspot-dot" aria-hidden="true"></span>
                            <span class="king-addons-product-360-viewer__hotspot-label"><?php echo esc_html($hotspot['label']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Prepare frames respecting free limits and required keys.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param bool                 $is_pro   Whether Pro rendering is active.
     *
     * @return array<int, array<string, string>>
     */
    public function prepare_frames(array $settings, bool $is_pro): array
    {
        $frames = $settings['kng_frames'] ?? [];
        if (!$is_pro) {
            $frames = array_slice($frames, 0, self::FREE_MAX_FRAMES);
        }

        $prepared = [];
        foreach ($frames as $frame) {
            $url = $frame['kng_frame_image']['url'] ?? '';
            if (empty($url)) {
                continue;
            }

            $label = $frame['kng_frame_label'] ?? '';
            $alt = $frame['kng_frame_alt'] ?? '';

            $prepared[] = [
                'url' => esc_url($url),
                'label' => sanitize_text_field($label),
                'alt' => sanitize_text_field($alt),
            ];
        }

        return $prepared;
    }

    /**
     * Prepare hotspot data.
     *
     * @param array<string, mixed> $settings    Widget settings.
     * @param bool                 $is_pro      Whether Pro rendering is active.
     * @param int                  $frame_count Total frame count.
     *
     * @return array<int, array<string, mixed>>
     */
    public function prepare_hotspots(array $settings, bool $is_pro, int $frame_count): array
    {
        if (!$is_pro) {
            return [];
        }

        $hotspots = $settings['kng_hotspots'] ?? [];
        $prepared = [];

        foreach ($hotspots as $hotspot) {
            $frame = $this->clamp_int((int) ($hotspot['kng_hotspot_frame'] ?? 1), 1, $frame_count);
            $label = sanitize_text_field($hotspot['kng_hotspot_label'] ?? '');
            $x = $this->clamp_float((float) ($hotspot['kng_hotspot_horizontal']['size'] ?? 50), 0, 100);
            $y = $this->clamp_float((float) ($hotspot['kng_hotspot_vertical']['size'] ?? 50), 0, 100);

            $link = $hotspot['kng_hotspot_link']['url'] ?? '';
            $is_external = !empty($hotspot['kng_hotspot_link']['is_external']);
            $nofollow = !empty($hotspot['kng_hotspot_link']['nofollow']);

            $prepared[] = [
                'frame' => $frame,
                'label' => $label,
                'x' => $x,
                'y' => $y,
                'link' => esc_url_raw($link),
                'is_external' => $is_external,
                'nofollow' => $nofollow,
            ];
        }

        return $prepared;
    }

    /**
     * Pro upsell notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'product-360-viewer', [
                'Unlimited frames with ping-pong autoplay',
                'Scroll, inertia, and multi-axis drag controls',
                'Zoom toggle with adjustable scale',
                'Frame-specific hotspots with links and labels',
            ]);
        }
    }

    /**
     * Provide default frames.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_default_frames(): array
    {
        $placeholder = Utils::get_placeholder_image_src();
        $defaults = [];

        for ($i = 1; $i <= 8; $i++) {
            $defaults[] = [
                'kng_frame_image' => [
                    'url' => $placeholder,
                ],
                'kng_frame_label' => sprintf(
                    /* translators: %d: frame number. */
                    esc_html__('Frame %d', 'king-addons'),
                    $i
                ),
                'kng_frame_alt' => sprintf(
                    /* translators: %d: frame number. */
                    esc_html__('Frame %d', 'king-addons'),
                    $i
                ),
            ];
        }

        return $defaults;
    }

    /**
     * Clamp integer values.
     *
     * @param int $value Value to clamp.
     * @param int $min   Minimum.
     * @param int $max   Maximum.
     *
     * @return int
     */
    public function clamp_int(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    /**
     * Clamp float values.
     *
     * @param float $value Value to clamp.
     * @param float $min   Minimum.
     * @param float $max   Maximum.
     *
     * @return float
     */
    public function clamp_float(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    /**
     * Render attributes as HTML string.
     *
     * @param array<string, string> $attributes Attributes to render.
     *
     * @return string
     */
    public function render_attributes_string(array $attributes): string
    {
        $rendered = [];
        foreach ($attributes as $name => $value) {
            $rendered[] = sprintf('%s="%s"', esc_attr($name), $value);
        }

        return implode(' ', $rendered);
    }
}




