<?php
/**
 * Rotating Image Tiles Widget (Free).
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
 * Renders the Rotating Image Tiles widget.
 */
class Rotating_Image_Tiles extends Widget_Base
{
    private const FREE_MAX_IMAGES = 2;
    private const FREE_MAX_TILES = 8;
    private const FREE_MAX_COLUMNS = 3;
    private const DEFAULT_ROTATION_INTERVAL = 2500;
    private const DEFAULT_TRANSITION_DURATION = 800;

    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-rotating-image-tiles';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Rotating Image Tiles', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-rotating-image-tiles';
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-rotating-image-tiles-script',
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-rotating-image-tiles-style',
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
        return ['image', 'tiles', 'rotation', 'gallery', 'grid'];
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
        $this->register_content_images_controls(false);
        $this->register_layout_controls(false);
        $this->register_tiles_controls(false);
        $this->register_animation_controls(false);
        $this->register_interaction_controls(false);
        $this->register_style_holder_controls();
        $this->register_style_tiles_controls(false);
        $this->register_style_circle_controls(false);
        $this->register_style_images_controls(false);
        $this->register_style_overlay_controls(false);
        $this->register_style_caption_controls(false);
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
        $this->render_output($settings, false);
    }

    /**
     * Render markup shared between free and pro.
     *
     * @param array<string, mixed> $settings Settings.
     * @param bool                 $is_pro   Whether pro version is active.
     *
     * @return void
     */
    public function render_output(array $settings, bool $is_pro): void
    {
        $images = $this->prepare_images($settings, $is_pro);
        $tiles = $this->prepare_tiles($settings, $is_pro, count($images));

        if (empty($images) || empty($tiles)) {
            return;
        }

        $layout_columns = $this->get_int_setting($settings['kng_tiles_columns'] ?? ($is_pro ? 4 : self::FREE_MAX_COLUMNS), 1, $is_pro ? 8 : self::FREE_MAX_COLUMNS);
        $layout_gap = $this->get_int_setting($settings['kng_tiles_gap']['size'] ?? 12, 0, 120);

        $rotation_interval = $this->get_int_setting(
            $settings['kng_rotation_interval']['size'] ?? self::DEFAULT_ROTATION_INTERVAL,
            100,
            20000
        );
        $transition_duration = $this->get_int_setting(
            $settings['kng_transition_duration']['size'] ?? self::DEFAULT_TRANSITION_DURATION,
            100,
            4000
        );

        $rotation_behavior = $is_pro ? ($settings['kng_rotation_behavior'] ?? 'autoplay') : 'autoplay';
        if (!$is_pro && ($settings['kng_enable_rotation'] ?? 'yes') !== 'yes') {
            $rotation_behavior = 'none';
        }

        $data_settings = [
            'isPro' => $is_pro,
            'layout' => [
                'columns' => $layout_columns,
                'gap' => $layout_gap,
            ],
            'animation' => [
                'mode' => $is_pro ? ($settings['kng_rotation_mode'] ?? 'sequential') : 'sequential',
                'behavior' => $rotation_behavior,
                'interval' => $rotation_interval,
                'transitionDuration' => $transition_duration,
                'easing' => $is_pro ? ($settings['kng_easing'] ?? 'ease-in-out') : 'ease-in-out',
                'loop' => $is_pro ? ($settings['kng_loop'] ?? 'yes') : 'yes',
                'pauseOnHover' => $is_pro ? ($settings['kng_pause_on_hover'] ?? 'no') : 'no',
                'disableOnMobile' => $is_pro ? ($settings['kng_disable_animation_on_mobile'] ?? 'no') : 'no',
            ],
            'interaction' => [
                'clickAction' => $is_pro ? ($settings['kng_click_action'] ?? 'lightbox') : 'lightbox',
                'showCaptions' => $settings['kng_show_captions'] ?? 'no',
                'captionSource' => $is_pro ? ($settings['kng_caption_source'] ?? 'title') : 'title',
            ],
            'visual' => [
                'imageFit' => $settings['kng_image_fit'] ?? 'cover',
                'imageScaleHover' => $is_pro ? ($settings['kng_image_scale_hover']['size'] ?? 1.05) : 1.05,
                'imageOpacity' => $settings['kng_image_opacity']['size'] ?? 1,
                'imageOpacityHover' => $is_pro ? ($settings['kng_image_opacity_hover']['size'] ?? 1) : ($settings['kng_image_opacity']['size'] ?? 1),
            ],
            'circle' => [
                'useGlobal' => $is_pro ? ($settings['kng_use_global_radius'] ?? 'no') : 'yes',
                'radius' => $is_pro ? ($settings['kng_circle_global_radius']['size'] ?? 40) : 40,
            ],
            'overlay' => [
                'color' => $settings['kng_overlay_color'] ?? '',
                'opacity' => $settings['kng_overlay_opacity']['size'] ?? 0,
                'blendMode' => $settings['kng_overlay_blend_mode'] ?? 'normal',
            ],
            'hover' => [
                'tileEffect' => $is_pro ? ($settings['kng_tile_hover_effect'] ?? 'none') : 'none',
            ],
            'tiles' => $tiles,
            'images' => $images,
        ];

        $holder_style = $this->build_holder_style($settings);
        $grid_style = sprintf('--rit-columns:%d;--rit-gap:%dpx;', $layout_columns, $layout_gap);
        $grid_style .= sprintf('--rit-transition-duration:%dms;--rit-easing:%s;', $transition_duration, esc_attr($data_settings['animation']['easing']));
        $grid_style .= sprintf('--rit-image-fit:%s;', esc_attr($data_settings['visual']['imageFit']));

        ?>
        <div class="king-addons-rotating-image-tiles" data-settings="<?php echo esc_attr(wp_json_encode($data_settings)); ?>">
            <div class="king-addons-rotating-image-tiles__holder"<?php echo $holder_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <div class="king-addons-rotating-image-tiles__grid" style="<?php echo esc_attr($grid_style); ?>">
                    <?php foreach ($tiles as $index => $tile) : ?>
                        <?php
                        $current_image_index = $tile['initialIndex'] ?? 0;
                        $current_image = $images[$current_image_index] ?? $images[0];
                        $mask_radius = $this->get_float_setting($tile['radius'] ?? 40, 5, 100);
                        $mask_center_x = $this->get_float_setting($tile['centerX'] ?? 50, 0, 100);
                        $mask_center_y = $this->get_float_setting($tile['centerY'] ?? 50, 0, 100);
                        $mask_style = sprintf(
                            'clip-path:circle(%s%% at %s%% %s%%);',
                            esc_attr($mask_radius),
                            esc_attr($mask_center_x),
                            esc_attr($mask_center_y)
                        );
                        ?>
                        <div
                            class="king-addons-rotating-image-tiles__tile"
                            data-tile-index="<?php echo esc_attr($index); ?>"
                            data-initial-index="<?php echo esc_attr($current_image_index); ?>"
                            data-delay="<?php echo esc_attr((int) ($tile['delay'] ?? 0)); ?>"
                            data-center-x="<?php echo esc_attr($mask_center_x); ?>"
                            data-center-y="<?php echo esc_attr($mask_center_y); ?>"
                            data-radius="<?php echo esc_attr($mask_radius); ?>"
                            data-hover-scale="<?php echo esc_attr($tile['hoverScale'] ?? 1.05); ?>"
                        >
                            <div class="king-addons-rotating-image-tiles__tile-inner">
                                <div class="king-addons-rotating-image-tiles__image-wrapper">
                                    <div class="king-addons-rotating-image-tiles__image-layer is-current" style="<?php echo esc_attr($mask_style); ?>">
                                        <img src="<?php echo esc_url($current_image['url']); ?>" alt="<?php echo esc_attr($current_image['alt']); ?>" />
                                        <span class="king-addons-rotating-image-tiles__overlay"></span>
                                    </div>
                                    <div class="king-addons-rotating-image-tiles__image-layer is-next" style="<?php echo esc_attr($mask_style); ?>">
                                        <img src="<?php echo esc_url($current_image['url']); ?>" alt="<?php echo esc_attr($current_image['alt']); ?>" />
                                        <span class="king-addons-rotating-image-tiles__overlay"></span>
                                    </div>
                                </div>
                                <?php if (($settings['kng_show_captions'] ?? 'no') === 'yes') : ?>
                                    <div class="king-addons-rotating-image-tiles__caption">
                                        <?php if (!empty($current_image['title'])) : ?>
                                            <div class="king-addons-rotating-image-tiles__caption-title">
                                                <?php echo esc_html($current_image['title']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (($settings['kng_caption_source'] ?? 'title') === 'title_description' && !empty($current_image['description'])) : ?>
                                            <div class="king-addons-rotating-image-tiles__caption-description">
                                                <?php echo esc_html($current_image['description']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Register images repeater controls.
     *
     * @param bool $is_pro Whether pro controls should be displayed.
     *
     * @return void
     */
    public function register_content_images_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_images_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Images', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'image',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $repeater->add_control(
            'title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 3,
            ]
        );

        if ($is_pro) {
            $repeater->add_control(
                'link_type',
                [
                    'label' => esc_html__('Link Type', 'king-addons'),
                    'type' => Controls_Manager::SELECT,
                    'default' => 'none',
                    'options' => [
                        'none' => esc_html__('None', 'king-addons'),
                        'custom' => esc_html__('Custom URL', 'king-addons'),
                    ],
                ]
            );

            $repeater->add_control(
                'link_url',
                [
                    'label' => esc_html__('Link', 'king-addons'),
                    'type' => Controls_Manager::URL,
                    'condition' => [
                        'link_type' => 'custom',
                    ],
                ]
            );
        }

        $this->add_control(
            'kng_images',
            [
                'label' => esc_html__('Images', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'title_field' => '{{{ title || "' . esc_html__('Image', 'king-addons') . '" }}}',
                'default' => [
                    [
                        'image' => ['url' => Utils::get_placeholder_image_src()],
                        'title' => esc_html__('Primary', 'king-addons'),
                    ],
                    [
                        'image' => ['url' => Utils::get_placeholder_image_src()],
                        'title' => esc_html__('Secondary', 'king-addons'),
                    ],
                ],
                'description' => $is_pro
                    ? esc_html__('Add up to four images for rotation.', 'king-addons')
                    : esc_html__('Free version uses the first two images. Upgrade to Pro to unlock four images, captions, and links.', 'king-addons'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register layout controls.
     *
     * @param bool $is_pro Whether pro controls should be displayed.
     *
     * @return void
     */
    public function register_layout_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'kng_holder_width',
            [
                'label' => esc_html__('Max Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 2000,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 100,
                    'unit' => '%',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__holder' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_holder_height',
            [
                'label' => esc_html__('Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 120,
                        'max' => 1200,
                    ],
                    'vh' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__holder' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_tiles_count',
            [
                'label' => esc_html__('Tiles Count', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => $is_pro ? 16 : self::FREE_MAX_TILES,
                'step' => 1,
                'default' => $is_pro ? 9 : 6,
                'description' => $is_pro
                    ? esc_html__('Define how many tiles should render (up to 16).', 'king-addons')
                    : esc_html__('Free version renders up to 8 tiles.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_tiles_columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => $is_pro ? 8 : self::FREE_MAX_COLUMNS,
                'step' => 1,
                'default' => $is_pro ? 4 : 3,
                'description' => $is_pro
                    ? esc_html__('Columns across the grid (up to 8).', 'king-addons')
                    : esc_html__('Free version supports up to 3 columns.', 'king-addons'),
            ]
        );

        $this->add_responsive_control(
            'kng_tiles_gap',
            [
                'label' => esc_html__('Tiles Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 64,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 4,
                    ],
                ],
                'default' => [
                    'size' => 12,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register tiles controls.
     *
     * @param bool $is_pro Whether pro controls should be displayed.
     *
     * @return void
     */
    public function register_tiles_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_tiles_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Tiles', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        if (!$is_pro) {
            $this->add_control(
                'kng_tiles_locked_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => esc_html__('Per-tile radius, center, delays, and hover effects are available in Pro. Free version uses a uniform 40% circle centered in each tile.', 'king-addons'),
                    'content_classes' => 'king-addons-pro-notice',
                ]
            );
            $this->end_controls_section();
            return;
        }

        $repeater = new Repeater();

        $repeater->add_control(
            'admin_label',
            [
                'label' => esc_html__('Admin Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
                'description' => esc_html__('Internal name for the tile.', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'initial_image_index',
            [
                'label' => esc_html__('Initial Image', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => '0',
                'options' => [
                    '0' => esc_html__('Image 1', 'king-addons'),
                    '1' => esc_html__('Image 2', 'king-addons'),
                    '2' => esc_html__('Image 3', 'king-addons'),
                    '3' => esc_html__('Image 4', 'king-addons'),
                ],
            ]
        );

        $repeater->add_control(
            'tile_circle_center_x',
            [
                'label' => esc_html__('Circle Center X (%)', 'king-addons'),
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
            ]
        );

        $repeater->add_control(
            'tile_circle_center_y',
            [
                'label' => esc_html__('Circle Center Y (%)', 'king-addons'),
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
            ]
        );

        $repeater->add_control(
            'tile_circle_radius',
            [
                'label' => esc_html__('Circle Radius (%)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 5,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 40,
                    'unit' => '%',
                ],
            ]
        );

        $repeater->add_control(
            'delay_offset',
            [
                'label' => esc_html__('Delay Offset (ms)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['ms'],
                'range' => [
                    'ms' => [
                        'min' => 0,
                        'max' => 5000,
                    ],
                ],
                'default' => [
                    'size' => 0,
                    'unit' => 'ms',
                ],
            ]
        );

        $repeater->add_control(
            'hover_scale',
            [
                'label' => esc_html__('Hover Scale', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 1.3,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 1.05,
                    'unit' => 'px',
                ],
            ]
        );

        $this->add_control(
            'kng_tiles',
            [
                'label' => esc_html__('Tiles', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'title_field' => '{{{ admin_label || "' . esc_html__('Tile', 'king-addons') . '" }}}',
                'description' => esc_html__('Add one entry per tile. If fewer items are set than the count, defaults are used.', 'king-addons'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register animation controls.
     *
     * @param bool $is_pro Whether pro controls should be displayed.
     *
     * @return void
     */
    public function register_animation_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_animation_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Animation', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        if ($is_pro) {
            $this->add_control(
                'kng_rotation_mode',
                [
                    'label' => esc_html__('Rotation Mode', 'king-addons'),
                    'type' => Controls_Manager::SELECT,
                    'default' => 'sequential',
                    'options' => [
                        'sequential' => esc_html__('Sequential', 'king-addons'),
                        'reverse' => esc_html__('Reverse', 'king-addons'),
                        'alternate' => esc_html__('Alternate', 'king-addons'),
                        'random' => esc_html__('Random', 'king-addons'),
                    ],
                ]
            );

            $this->add_control(
                'kng_rotation_behavior',
                [
                    'label' => esc_html__('Rotation Trigger', 'king-addons'),
                    'type' => Controls_Manager::SELECT,
                    'default' => 'autoplay',
                    'options' => [
                        'autoplay' => esc_html__('Autoplay', 'king-addons'),
                        'on_hover' => esc_html__('On Hover', 'king-addons'),
                        'on_click' => esc_html__('On Click', 'king-addons'),
                    ],
                ]
            );

            $this->add_control(
                'kng_loop',
                [
                    'label' => esc_html__('Loop', 'king-addons'),
                    'type' => Controls_Manager::SWITCHER,
                    'return_value' => 'yes',
                    'default' => 'yes',
                ]
            );

            $this->add_control(
                'kng_pause_on_hover',
                [
                    'label' => esc_html__('Pause on Hover', 'king-addons'),
                    'type' => Controls_Manager::SWITCHER,
                    'return_value' => 'yes',
                    'default' => 'no',
                    'condition' => [
                        'kng_rotation_behavior' => 'autoplay',
                    ],
                ]
            );
        } else {
            $this->add_control(
                'kng_enable_rotation',
                [
                    'label' => esc_html__('Enable Rotation', 'king-addons'),
                    'type' => Controls_Manager::SWITCHER,
                    'return_value' => 'yes',
                    'default' => 'yes',
                    'description' => esc_html__('Free version uses autoplay with fixed easing and timing.', 'king-addons'),
                ]
            );
        }

        $this->add_control(
            'kng_rotation_interval',
            [
                'label' => $is_pro ? esc_html__('Rotation Interval (ms)', 'king-addons') : esc_html__('Rotation Interval (fixed)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['ms'],
                'range' => [
                    'ms' => [
                        'min' => 500,
                        'max' => 10000,
                    ],
                ],
                'default' => [
                    'size' => self::DEFAULT_ROTATION_INTERVAL,
                    'unit' => 'ms',
                ],
                'description' => $is_pro
                    ? esc_html__('Interval between rotations per tile.', 'king-addons')
                    : esc_html__('Fixed for free version; adjust with Pro.', 'king-addons'),
                'render_type' => 'none',
            ]
        );

        $this->add_control(
            'kng_transition_duration',
            [
                'label' => $is_pro ? esc_html__('Transition Duration (ms)', 'king-addons') : esc_html__('Transition Duration (fixed)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['ms'],
                'range' => [
                    'ms' => [
                        'min' => 100,
                        'max' => 3000,
                    ],
                ],
                'default' => [
                    'size' => self::DEFAULT_TRANSITION_DURATION,
                    'unit' => 'ms',
                ],
                'description' => $is_pro
                    ? esc_html__('Fade/transform duration.', 'king-addons')
                    : esc_html__('Fixed for free version; adjust with Pro.', 'king-addons'),
                'render_type' => 'none',
            ]
        );

        if ($is_pro) {
            $this->add_control(
                'kng_easing',
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
                    ],
                ]
            );

            $this->add_control(
                'kng_disable_animation_on_mobile',
                [
                    'label' => esc_html__('Disable Animation on Mobile', 'king-addons'),
                    'type' => Controls_Manager::SWITCHER,
                    'return_value' => 'yes',
                    'default' => 'no',
                ]
            );
        }

        $this->end_controls_section();
    }

    /**
     * Register interaction controls.
     *
     * @param bool $is_pro Whether pro controls should be displayed.
     *
     * @return void
     */
    public function register_interaction_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_interaction_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Interaction', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        if ($is_pro) {
            $this->add_control(
                'kng_click_action',
                [
                    'label' => esc_html__('Click Action', 'king-addons'),
                    'type' => Controls_Manager::SELECT,
                    'default' => 'lightbox',
                    'options' => [
                        'none' => esc_html__('None', 'king-addons'),
                        'lightbox' => esc_html__('Open Image in Lightbox', 'king-addons'),
                        'open_link' => esc_html__('Open Link', 'king-addons'),
                    ],
                ]
            );
        } else {
            $this->add_control(
                'kng_click_action_locked',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => esc_html__('Clicks open the image in a simple lightbox. Upgrade to Pro to unlock custom links and click behaviors.', 'king-addons'),
                    'content_classes' => 'king-addons-pro-notice',
                ]
            );
        }

        $this->add_control(
            'kng_show_captions',
            [
                'label' => esc_html__('Show Captions', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'kng_caption_source',
            [
                'label' => sprintf(esc_html__('Caption Source %s', 'king-addons'), $is_pro ? '' : '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'default' => 'title',
                'options' => [
                    'title' => esc_html__('Title Only', 'king-addons'),
                    'title_description' => esc_html__('Title and Description', 'king-addons'),
                ],
                'condition' => [
                    'kng_show_captions' => 'yes',
                ],
                'description' => $is_pro ? '' : esc_html__('Pro unlocks description captions.', 'king-addons'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register holder style controls.
     *
     * @return void
     */
    public function register_style_holder_controls(): void
    {
        $this->start_controls_section(
            'kng_style_holder_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Holder', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_holder_alignment',
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
                    '{{WRAPPER}} .king-addons-rotating-image-tiles' => 'display:flex;justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_holder_background_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__holder' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_holder_border',
                'selector' => '{{WRAPPER}} .king-addons-rotating-image-tiles__holder',
            ]
        );

        $this->add_responsive_control(
            'kng_holder_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__holder' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_holder_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-rotating-image-tiles__holder',
            ]
        );

        $this->add_responsive_control(
            'kng_holder_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__holder' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register tiles style controls.
     *
     * @param bool $is_pro Whether pro controls should be displayed.
     *
     * @return void
     */
    public function register_style_tiles_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_style_tiles_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Tiles', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_tile_background_color',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__tile' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_tile_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 120,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__tile' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_tile_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-rotating-image-tiles__tile',
            ]
        );

        if ($is_pro) {
            $this->add_control(
                'kng_tile_hover_effect',
                [
                    'label' => esc_html__('Hover Effect', 'king-addons'),
                    'type' => Controls_Manager::SELECT,
                    'default' => 'none',
                    'options' => [
                        'none' => esc_html__('None', 'king-addons'),
                        'lift' => esc_html__('Lift', 'king-addons'),
                        'glow' => esc_html__('Glow', 'king-addons'),
                        'shadow' => esc_html__('Shadow Increase', 'king-addons'),
                    ],
                ]
            );
        }

        $this->end_controls_section();
    }

    /**
     * Register circle style controls.
     *
     * @param bool $is_pro Whether pro controls should be displayed.
     *
     * @return void
     */
    public function register_style_circle_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_style_circle_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Circle Mask', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        if ($is_pro) {
            $this->add_control(
                'kng_use_global_radius',
                [
                    'label' => esc_html__('Use Global Radius', 'king-addons'),
                    'type' => Controls_Manager::SWITCHER,
                    'return_value' => 'yes',
                    'default' => 'no',
                ]
            );
        }

        $this->add_control(
            'kng_circle_global_radius',
            [
                'label' => $is_pro ? esc_html__('Global Radius (%)', 'king-addons') : esc_html__('Circle Radius (%)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 5,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 40,
                    'unit' => '%',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_circle_border',
                'selector' => '{{WRAPPER}} .king-addons-rotating-image-tiles__image-layer',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_circle_shadow',
                'selector' => '{{WRAPPER}} .king-addons-rotating-image-tiles__image-layer',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register image style controls.
     *
     * @param bool $is_pro Whether pro controls should be displayed.
     *
     * @return void
     */
    public function register_style_images_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_style_images_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Images', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_image_fit',
            [
                'label' => esc_html__('Image Fit', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'cover',
                'options' => [
                    'cover' => esc_html__('Cover', 'king-addons'),
                    'contain' => esc_html__('Contain', 'king-addons'),
                    'fill' => esc_html__('Fill', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__image-layer img' => 'object-fit: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_image_scale_hover',
            [
                'label' => $is_pro ? esc_html__('Hover Scale', 'king-addons') : esc_html__('Hover Scale (fixed)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 1.3,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 1.05,
                    'unit' => 'px',
                ],
                'render_type' => 'none',
            ]
        );

        $this->add_control(
            'kng_image_opacity',
            [
                'label' => esc_html__('Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 1,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__image-layer.is-current img' => 'opacity: {{SIZE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_image_opacity_hover',
            [
                'label' => $is_pro ? esc_html__('Opacity on Hover', 'king-addons') : esc_html__('Opacity on Hover (Pro)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 1,
                    'unit' => 'px',
                ],
                'condition' => $is_pro ? [] : ['kng_show_captions' => 'never-match'],
                'selectors' => $is_pro ? [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__tile:hover .king-addons-rotating-image-tiles__image-layer img' => 'opacity: {{SIZE}};',
                ] : [],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register overlay style controls.
     *
     * @param bool $is_pro Whether pro controls should be displayed.
     *
     * @return void
     */
    public function register_style_overlay_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_style_overlay_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Overlay', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_overlay_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__overlay' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_overlay_opacity',
            [
                'label' => esc_html__('Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 0,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__overlay' => 'opacity: {{SIZE}};',
                ],
            ]
        );

        if ($is_pro) {
            $this->add_control(
                'kng_overlay_blend_mode',
                [
                    'label' => esc_html__('Blend Mode', 'king-addons'),
                    'type' => Controls_Manager::SELECT,
                    'default' => 'normal',
                    'options' => [
                        'normal' => esc_html__('Normal', 'king-addons'),
                        'multiply' => esc_html__('Multiply', 'king-addons'),
                        'screen' => esc_html__('Screen', 'king-addons'),
                        'overlay' => esc_html__('Overlay', 'king-addons'),
                        'soft-light' => esc_html__('Soft Light', 'king-addons'),
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-rotating-image-tiles__overlay' => 'mix-blend-mode: {{VALUE}};',
                    ],
                ]
            );
        }

        $this->end_controls_section();
    }

    /**
     * Register caption style controls.
     *
     * @param bool $is_pro Whether pro controls should be displayed.
     *
     * @return void
     */
    public function register_style_caption_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_style_caption_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Captions', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_show_captions' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_caption_typography',
                'selector' => '{{WRAPPER}} .king-addons-rotating-image-tiles__caption',
            ]
        );

        $this->add_control(
            'kng_caption_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__caption' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_caption_background_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__caption' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_caption_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__caption' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_caption_alignment',
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
                    '{{WRAPPER}} .king-addons-rotating-image-tiles__caption' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Show pro upsell notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (king_addons_freemius()->can_use_premium_code__premium_only()) {
            return;
        }

        Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'rotating-image-tiles', [
            'Up to 4 images with per-image titles, descriptions, and links',
            'Advanced rotation modes: reverse, alternate, random',
            'Per-tile circle masks, delay offsets, hover scales',
            'Click actions (custom links or lightbox gallery) and hover effects',
            'Overlay blend modes, global radius override, and mobile controls',
        ]);
    }

    /**
     * Build holder inline style.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return string
     */
    public function build_holder_style(array $settings): string
    {
        $styles = [];

        if (!empty($settings['kng_holder_height']['size'])) {
            $styles[] = 'height:' . esc_attr($settings['kng_holder_height']['size'] . ($settings['kng_holder_height']['unit'] ?? 'px')) . ';';
        }

        return empty($styles) ? '' : ' style="' . esc_attr(implode('', $styles)) . '"';
    }

    /**
     * Prepare images payload.
     *
     * @param array<string, mixed> $settings Settings.
     * @param bool                 $is_pro   Whether pro version is active.
     *
     * @return array<int, array<string, mixed>>
     */
    public function prepare_images(array $settings, bool $is_pro): array
    {
        $max_images = $is_pro ? 4 : self::FREE_MAX_IMAGES;
        $items = array_slice($settings['kng_images'] ?? [], 0, $max_images);
        $prepared = [];

        foreach ($items as $item) {
            $url = $item['image']['url'] ?? Utils::get_placeholder_image_src();
            if (empty($url)) {
                $url = Utils::get_placeholder_image_src();
            }

            $title = $item['title'] ?? '';
            $description = $item['description'] ?? '';
            $alt = $title ?: $description;

            $link = [
                'url' => '',
                'is_external' => false,
                'nofollow' => false,
            ];

            if ($is_pro && ($item['link_type'] ?? 'none') === 'custom' && !empty($item['link_url']['url'])) {
                $link = [
                    'url' => $item['link_url']['url'],
                    'is_external' => (bool) ($item['link_url']['is_external'] ?? false),
                    'nofollow' => (bool) ($item['link_url']['nofollow'] ?? false),
                ];
            }

            $prepared[] = [
                'url' => $url,
                'title' => $title,
                'description' => $description,
                'alt' => $alt ?: esc_html__('Image', 'king-addons'),
                'link' => $link,
            ];
        }

        return $prepared;
    }

    /**
     * Prepare tiles payload.
     *
     * @param array<string, mixed> $settings      Settings.
     * @param bool                 $is_pro        Whether pro version is active.
     * @param int                  $images_length Number of images.
     *
     * @return array<int, array<string, mixed>>
     */
    public function prepare_tiles(array $settings, bool $is_pro, int $images_length): array
    {
        $count = $this->get_int_setting($settings['kng_tiles_count'] ?? 0, 1, $is_pro ? 16 : self::FREE_MAX_TILES);
        $tiles = [];

        if ($is_pro) {
            $raw_tiles = $settings['kng_tiles'] ?? [];
            for ($i = 0; $i < $count; $i++) {
                $tile = $raw_tiles[$i] ?? [];
                $tiles[] = [
                    'initialIndex' => $this->get_int_setting($tile['initial_image_index'] ?? 0, 0, max(0, $images_length - 1)),
                    'centerX' => $this->get_float_setting($tile['tile_circle_center_x']['size'] ?? 50, 0, 100),
                    'centerY' => $this->get_float_setting($tile['tile_circle_center_y']['size'] ?? 50, 0, 100),
                    'radius' => $this->get_float_setting($tile['tile_circle_radius']['size'] ?? 40, 5, 100),
                    'delay' => $this->get_int_setting($tile['delay_offset']['size'] ?? 0, 0, 5000),
                    'hoverScale' => $this->get_float_setting($tile['hover_scale']['size'] ?? 1.05, 1, 1.3),
                ];
            }
        } else {
            for ($i = 0; $i < $count; $i++) {
                $tiles[] = [
                    'initialIndex' => $i % max(1, $images_length),
                    'centerX' => 50,
                    'centerY' => 50,
                    'radius' => 40,
                    'delay' => 0,
                    'hoverScale' => 1.05,
                ];
            }
        }

        return $tiles;
    }

    /**
     * Get integer setting within bounds.
     *
     * @param mixed $value Raw value.
     * @param int   $min   Minimum allowed.
     * @param int   $max   Maximum allowed.
     *
     * @return int
     */
    public function get_int_setting($value, int $min, int $max): int
    {
        $int = (int) $value;
        return max($min, min($max, $int));
    }

    /**
     * Get float setting within bounds.
     *
     * @param mixed $value Raw value.
     * @param float $min   Minimum allowed.
     * @param float $max   Maximum allowed.
     *
     * @return float
     */
    public function get_float_setting($value, float $min, float $max): float
    {
        $float = (float) $value;
        return max($min, min($max, $float));
    }
}






