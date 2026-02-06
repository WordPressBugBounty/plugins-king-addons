<?php
/**
 * Custom Post Types Slider Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders a slider for selected post types with basic display options.
 */
class Custom_Post_Types_Slider extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-custom-post-types-slider';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Custom Post Types Slider', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-simple-post-slider';
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-swiper-swiper',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-custom-post-types-slider-script',
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-swiper-swiper',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-custom-post-types-slider-style',
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
        return ['custom post type', 'cpt', 'slider', 'carousel', 'king-addons'];
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
        $this->register_query_controls(false);
        $this->register_slider_controls();
        $this->register_interaction_controls();
        $this->register_navigation_controls();
        $this->register_pagination_controls();
        $this->register_display_controls();
        $this->register_style_card_controls();
        $this->register_style_text_controls();
        $this->register_style_meta_controls();
        $this->register_style_button_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Interaction controls.
     *
     * @return void
     */
    protected function register_interaction_controls(): void
    {
        $this->start_controls_section(
            'kng_interaction_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Animations', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_hover_animation',
            [
                'label' => esc_html__('Hover Animation', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'hover-lift' => esc_html__('Lift', 'king-addons'),
                    'hover-zoom' => esc_html__('Image Zoom', 'king-addons'),
                    'hover-tilt' => esc_html__('Tilt', 'king-addons'),
                    'hover-float' => esc_html__('Float', 'king-addons'),
                    'hover-scale-fade' => esc_html__('Scale & Fade', 'king-addons'),
                ],
            ]
        );

        $this->end_controls_section();
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
     * Render slider markup.
     *
     * @param array<string, mixed> $settings Settings.
     * @param bool                 $is_pro   Whether pro mode is enabled.
     *
     * @return void
     */
    public function render_output(array $settings, bool $is_pro = false): void
    {
        $query = $this->build_query($settings);
        if (!$query->have_posts()) {
            wp_reset_postdata();
            return;
        }

        $wrapper_classes = $this->get_wrapper_classes($settings);
        $wrapper_styles = $this->get_wrapper_styles($settings);
        $data_attributes = $this->get_slider_data_attributes($settings);

        $show_navigation = ($settings['kng_show_navigation'] ?? 'yes') === 'yes';
        $show_pagination = ($settings['kng_show_pagination'] ?? 'yes') === 'yes';

        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"<?php echo $wrapper_styles; ?>>
            <div class="king-addons-cpt-slider__track swiper" <?php echo $data_attributes; ?>>
                <div class="king-addons-cpt-slider__wrapper swiper-wrapper">
                    <?php while ($query->have_posts()) : $query->the_post(); ?>
                        <?php $this->render_slide($settings, $is_pro); ?>
                    <?php endwhile; ?>
                </div>
                <?php if ($show_pagination) : ?>
                    <div class="king-addons-cpt-slider__pagination swiper-pagination" aria-label="<?php echo esc_attr__('Slider pagination', 'king-addons'); ?>"></div>
                <?php endif; ?>
            </div>
            <?php if ($show_navigation) : ?>
                <div class="king-addons-cpt-slider__navigation" aria-label="<?php echo esc_attr__('Slider navigation', 'king-addons'); ?>">
                    <button type="button" class="king-addons-cpt-slider__arrow king-addons-cpt-slider__arrow--prev swiper-button-prev" aria-label="<?php echo esc_attr__('Previous slide', 'king-addons'); ?>">
                        <span class="king-addons-cpt-slider__arrow-icon" aria-hidden="true"></span>
                    </button>
                    <button type="button" class="king-addons-cpt-slider__arrow king-addons-cpt-slider__arrow--next swiper-button-next" aria-label="<?php echo esc_attr__('Next slide', 'king-addons'); ?>">
                        <span class="king-addons-cpt-slider__arrow-icon" aria-hidden="true"></span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php

        wp_reset_postdata();
    }

    /**
     * Show pro feature promo.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'custom-post-types-slider', [
                'Multiple post types and taxonomy filters',
                'Higher limits with include/exclude rules',
                'Custom field output inside slides',
                'Meta-based ordering and offsets',
            ]);
        }
    }

    /**
     * Register query controls.
     *
     * @param bool $include_custom_types Whether to show custom post types.
     *
     * @return void
     */
    protected function register_query_controls(bool $include_custom_types): void
    {
        $post_type_options = $this->get_post_type_options($include_custom_types);

        $this->start_controls_section(
            'kng_query_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Query', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_post_type',
            [
                'label' => $include_custom_types ? esc_html__('Post Types', 'king-addons') : esc_html__('Post Type', 'king-addons'),
                'type' => $include_custom_types ? Controls_Manager::SELECT2 : Controls_Manager::SELECT,
                'multiple' => $include_custom_types,
                'options' => $post_type_options,
                'default' => $include_custom_types ? ['post'] : 'post',
                'label_block' => true,
                'description' => $include_custom_types ?
                    esc_html__('Select one or multiple public post types.', 'king-addons') :
                    esc_html__('Upgrade to Pro to unlock all custom post types.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_posts_per_page',
            [
                'label' => esc_html__('Posts Number', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => $include_custom_types ? 20 : 6,
                'step' => 1,
                'default' => $include_custom_types ? 9 : 6,
                'description' => $include_custom_types ?
                    esc_html__('Limit per slider (capped at 20 for performance).', 'king-addons') :
                    esc_html__('Free version is limited to 6 posts.', 'king-addons'),
            ]
        );

        $orderby_options = [
            'date' => esc_html__('Date', 'king-addons'),
            'title' => esc_html__('Title', 'king-addons'),
            'menu_order' => esc_html__('Menu Order', 'king-addons'),
            'rand' => esc_html__('Random', 'king-addons'),
        ];

        if ($include_custom_types) {
            $orderby_options['meta_value'] = esc_html__('Custom Field (Text)', 'king-addons');
            $orderby_options['meta_value_num'] = esc_html__('Custom Field (Number)', 'king-addons');
        }

        $this->add_control(
            'kng_orderby',
            [
                'label' => esc_html__('Order By', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => $orderby_options,
            ]
        );

        if ($include_custom_types) {
            $this->add_control(
                'kng_meta_key',
                [
                    'label' => esc_html__('Custom Field Key', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'placeholder' => esc_html__('meta_key', 'king-addons'),
                    'condition' => [
                        'kng_orderby' => ['meta_value', 'meta_value_num'],
                    ],
                ]
            );
        }

        $this->add_control(
            'kng_order',
            [
                'label' => esc_html__('Order', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'DESC' => esc_html__('Descending', 'king-addons'),
                    'ASC' => esc_html__('Ascending', 'king-addons'),
                ],
            ]
        );

        if ($include_custom_types) {
            $this->add_control(
                'kng_offset',
                [
                    'label' => esc_html__('Offset', 'king-addons'),
                    'type' => Controls_Manager::NUMBER,
                    'min' => 0,
                    'step' => 1,
                    'default' => 0,
                ]
            );

            $this->add_control(
                'kng_include_ids',
                [
                    'label' => esc_html__('Include IDs', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'description' => esc_html__('Comma-separated list of post IDs to include.', 'king-addons'),
                    'placeholder' => esc_html__('12,34,56', 'king-addons'),
                ]
            );

            $this->add_control(
                'kng_exclude_ids',
                [
                    'label' => esc_html__('Exclude IDs', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'description' => esc_html__('Comma-separated list of post IDs to exclude.', 'king-addons'),
                    'placeholder' => esc_html__('78,90', 'king-addons'),
                ]
            );

            $this->add_control(
                'kng_taxonomy_filter',
                [
                    'label' => esc_html__('Taxonomy Filters', 'king-addons'),
                    'type' => Controls_Manager::TEXT,
                    'placeholder' => esc_html__('category:news,updates; portfolio_cat:web', 'king-addons'),
                    'description' => esc_html__('Use taxonomy:slug-one,slug-two; separate multiple taxonomies with semicolons.', 'king-addons'),
                    'label_block' => true,
                ]
            );
        } else {
            $this->add_control(
                'kng_taxonomy_filter',
                [
                    'label' => sprintf(__('Taxonomy Filters %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                    'type' => Controls_Manager::TEXT,
                    'description' => esc_html__('Available in Pro. Filter by taxonomy slugs.', 'king-addons'),
                    'classes' => 'king-addons-pro-control no-distance',
                ]
            );
        }

        $this->add_control(
            'kng_show_image',
            [
                'label' => esc_html__('Show Featured Image', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'kng_image_size',
                'default' => 'medium',
                'condition' => [
                    'kng_show_image' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Slider behavior controls.
     *
     * @return void
     */
    protected function register_slider_controls(): void
    {
        $this->start_controls_section(
            'kng_slider_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Slider', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'kng_slides_per_view',
            [
                'label' => esc_html__('Slides Per View', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 4,
                'step' => 1,
                'default' => 1,
            ]
        );

        $this->add_responsive_control(
            'kng_space_between',
            [
                'label' => esc_html__('Space Between', 'king-addons'),
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
                    '{{WRAPPER}} .king-addons-cpt-slider' => '--kng-cpt-slider-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_speed',
            [
                'label' => esc_html__('Transition Speed (ms)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 100,
                'max' => 5000,
                'step' => 50,
                'default' => 600,
            ]
        );

        $this->add_control(
            'kng_autoplay',
            [
                'label' => esc_html__('Autoplay', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_autoplay_delay',
            [
                'label' => esc_html__('Autoplay Delay (ms)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1000,
                'max' => 10000,
                'step' => 100,
                'default' => 3200,
                'condition' => [
                    'kng_autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_loop',
            [
                'label' => esc_html__('Loop Slides', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Navigation controls.
     *
     * @return void
     */
    protected function register_navigation_controls(): void
    {
        $this->start_controls_section(
            'kng_navigation_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Navigation', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_show_navigation',
            [
                'label' => esc_html__('Show Arrows', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_navigation_position',
            [
                'label' => esc_html__('Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'outside',
                'options' => [
                    'outside' => esc_html__('Outside', 'king-addons'),
                    'inside' => esc_html__('Inside', 'king-addons'),
                ],
                'condition' => [
                    'kng_show_navigation' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_navigation_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Top', 'king-addons'),
                        'icon' => 'eicon-v-align-top',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-v-align-middle',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Bottom', 'king-addons'),
                        'icon' => 'eicon-v-align-bottom',
                    ],
                ],
                'toggle' => false,
                'default' => 'center',
                'condition' => [
                    'kng_show_navigation' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_navigation_offset',
            [
                'label' => esc_html__('Offset', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'size' => 25,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_show_navigation' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cpt-slider' => '--kng-cpt-slider-nav-offset: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_navigation_gap',
            [
                'label' => esc_html__('Arrows Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 4,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'size' => 12,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_show_navigation' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cpt-slider' => '--kng-cpt-slider-nav-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_navigation_hide_tablet',
            [
                'label' => esc_html__('Hide on Tablet', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'condition' => [
                    'kng_show_navigation' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_navigation_hide_mobile',
            [
                'label' => esc_html__('Hide on Mobile', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'condition' => [
                    'kng_show_navigation' => 'yes',
                ],
            ]
        );

        if (method_exists($this, 'add_pro_navigation_controls')) {
            $this->add_pro_navigation_controls();
        }

        $this->end_controls_section();
    }

    /**
     * Pagination controls.
     *
     * @return void
     */
    protected function register_pagination_controls(): void
    {
        $this->start_controls_section(
            'kng_pagination_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Pagination', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_show_pagination',
            [
                'label' => esc_html__('Show Pagination', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_pagination_position',
            [
                'label' => esc_html__('Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'outside',
                'options' => [
                    'outside' => esc_html__('Outside', 'king-addons'),
                    'inside' => esc_html__('Inside', 'king-addons'),
                ],
                'condition' => [
                    'kng_show_pagination' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_pagination_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Start', 'king-addons'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('End', 'king-addons'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'toggle' => false,
                'default' => 'center',
                'condition' => [
                    'kng_show_pagination' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pagination_offset',
            [
                'label' => esc_html__('Offset', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'size' => 14,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_show_pagination' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cpt-slider' => '--kng-cpt-slider-pagination-offset: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_pagination_gap',
            [
                'label' => esc_html__('Dots Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 2,
                        'max' => 36,
                    ],
                ],
                'default' => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_show_pagination' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cpt-slider' => '--kng-cpt-slider-pagination-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        if (method_exists($this, 'add_pro_pagination_controls')) {
            $this->add_pro_pagination_controls();
        }

        $this->end_controls_section();
    }

    /**
     * Content display controls.
     *
     * @return void
     */
    protected function register_display_controls(): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_show_meta',
            [
                'label' => esc_html__('Show Meta (Author & Date)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_excerpt',
            [
                'label' => esc_html__('Show Excerpt', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_excerpt_length',
            [
                'label' => esc_html__('Excerpt Length (words)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 5,
                'max' => 80,
                'step' => 1,
                'default' => 18,
                'condition' => [
                    'kng_show_excerpt' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_show_read_more',
            [
                'label' => esc_html__('Show Read More', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_read_more_text',
            [
                'label' => esc_html__('Read More Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Read more', 'king-addons'),
                'condition' => [
                    'kng_show_read_more' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_card_linkable',
            [
                'label' => esc_html__('Make Slide Clickable', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'description' => esc_html__('Slide click is disabled inside the Elementor editor to avoid interfering with editing.', 'king-addons'),
            ]
        );

        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            $this->add_control(
                'kng_custom_fields_notice',
                [
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => sprintf(__('Custom field output is available in the <strong><a href="%s" target="_blank">Pro version</a></strong>.', 'king-addons'), 'https://kingaddons.com/pricing/?utm_source=kng-module-cpt-slider-content-upgrade-pro&utm_medium=plugin&utm_campaign=kng'),
                    'content_classes' => 'king-addons-pro-notice',
                ]
            );
        }

        $this->end_controls_section();
    }

    /**
     * Card style controls.
     *
     * @return void
     */
    protected function register_style_card_controls(): void
    {
        $this->start_controls_section(
            'kng_style_card_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_card_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cpt-slider__card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cpt-slider__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_card_border',
                'selector' => '{{WRAPPER}} .king-addons-cpt-slider__card',
            ]
        );

        $this->add_control(
            'kng_card_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cpt-slider__card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-cpt-slider__card',
            ]
        );

        $this->add_control(
            'kng_card_shadow_disable',
            [
                'label' => esc_html__('Disable Box Shadow', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cpt-slider__card' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->add_control(
            'kng_card_shadow_hover_disable',
            [
                'label' => esc_html__('Disable Box Shadow on Hover', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cpt-slider__card:hover' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Text style controls.
     *
     * @return void
     */
    protected function register_style_text_controls(): void
    {
        $this->start_controls_section(
            'kng_style_text_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Text', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cpt-slider__title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-cpt-slider__title',
            ]
        );

        $this->add_control(
            'kng_excerpt_color',
            [
                'label' => esc_html__('Excerpt Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cpt-slider__excerpt' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_excerpt_typography',
                'selector' => '{{WRAPPER}} .king-addons-cpt-slider__excerpt',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Meta style controls.
     *
     * @return void
     */
    protected function register_style_meta_controls(): void
    {
        $this->start_controls_section(
            'kng_style_meta_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Meta', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_meta_color',
            [
                'label' => esc_html__('Meta Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cpt-slider__meta' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_meta_typography',
                'selector' => '{{WRAPPER}} .king-addons-cpt-slider__meta',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Button style controls.
     *
     * @return void
     */
    protected function register_style_button_controls(): void
    {
        $this->start_controls_section(
            'kng_style_button_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Read More', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_button_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-cpt-slider__read-more' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-cpt-slider__read-more',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Build WP_Query for free version.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return WP_Query
     */
    protected function build_query(array $settings): WP_Query
    {
        $post_type = $settings['kng_post_type'] ?? 'post';
        if (is_array($post_type)) {
            $post_type = $post_type[0] ?? 'post';
        }

        $allowed_types = $this->get_post_type_options(false);
        if (!array_key_exists($post_type, $allowed_types)) {
            $post_type = 'post';
        }

        $posts_per_page = isset($settings['kng_posts_per_page']) ? (int) $settings['kng_posts_per_page'] : 6;
        $posts_per_page = max(1, min(6, $posts_per_page));

        $order = $settings['kng_order'] ?? 'DESC';
        $order = in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC';

        $orderby = $this->map_orderby($settings['kng_orderby'] ?? 'date');
        if ($orderby === 'meta_value' || $orderby === 'meta_value_num') {
            $orderby = 'date';
        }

        $args = [
            'post_type' => $post_type,
            'posts_per_page' => $posts_per_page,
            'order' => $order,
            'orderby' => $orderby,
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
        ];

        return new WP_Query($args);
    }

    /**
     * Map control orderby to WP_Query value.
     *
     * @param string $orderby Orderby value.
     *
     * @return string
     */
    protected function map_orderby(string $orderby): string
    {
        $allowed = ['date', 'title', 'menu_order', 'rand', 'meta_value', 'meta_value_num'];
        return in_array($orderby, $allowed, true) ? $orderby : 'date';
    }

    /**
     * Get available post type options.
     *
     * @param bool $include_custom Whether to include custom post types.
     *
     * @return array<string, string>
     */
    protected function get_post_type_options(bool $include_custom): array
    {
        $options = [
            'post' => esc_html__('Posts', 'king-addons'),
            'page' => esc_html__('Pages', 'king-addons'),
        ];

        if (!$include_custom) {
            return $options;
        }

        $public_types = get_post_types(
            [
                'public' => true,
                '_builtin' => false,
            ],
            'objects'
        );

        foreach ($public_types as $slug => $type_object) {
            $options[$slug] = esc_html($type_object->labels->name ?? $slug);
        }

        return $options;
    }

    /**
     * Wrapper classes.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<int, string>
     */
    protected function get_wrapper_classes(array $settings): array
    {
        $classes = [
            'king-addons-cpt-slider',
        ];

        $show_navigation = ($settings['kng_show_navigation'] ?? 'yes') === 'yes';
        $show_pagination = ($settings['kng_show_pagination'] ?? 'yes') === 'yes';

        if ($show_navigation) {
            $nav_position = $settings['kng_navigation_position'] ?? 'outside';
            if (!in_array($nav_position, ['inside', 'outside'], true)) {
                $nav_position = 'outside';
            }
            $classes[] = 'king-addons-cpt-slider--nav-' . $nav_position;

            $nav_alignment = $settings['kng_navigation_alignment'] ?? 'center';
            if (!in_array($nav_alignment, ['flex-start', 'center', 'flex-end'], true)) {
                $nav_alignment = 'center';
            }
            $classes[] = 'king-addons-cpt-slider--nav-align-' . $nav_alignment;

            if (($settings['kng_navigation_hide_tablet'] ?? '') === 'yes') {
                $classes[] = 'king-addons-cpt-slider--nav-hide-tablet';
            }

            if (($settings['kng_navigation_hide_mobile'] ?? '') === 'yes') {
                $classes[] = 'king-addons-cpt-slider--nav-hide-mobile';
            }
        }

        if ($show_pagination) {
            $pagination_position = $settings['kng_pagination_position'] ?? 'outside';
            if (!in_array($pagination_position, ['inside', 'outside'], true)) {
                $pagination_position = 'outside';
            }
            $classes[] = 'king-addons-cpt-slider--pagination-' . $pagination_position;

            $pagination_alignment = $settings['kng_pagination_alignment'] ?? 'center';
            if (!in_array($pagination_alignment, ['flex-start', 'center', 'flex-end'], true)) {
                $pagination_alignment = 'center';
            }
            $classes[] = 'king-addons-cpt-slider--pagination-align-' . $pagination_alignment;
        }

        if (!empty($settings['kng_navigation_skin'])) {
            $classes[] = 'king-addons-cpt-slider--nav-style-' . sanitize_html_class((string) $settings['kng_navigation_skin']);
        }

        if (!empty($settings['kng_pagination_skin'])) {
            $classes[] = 'king-addons-cpt-slider--pagination-style-' . sanitize_html_class((string) $settings['kng_pagination_skin']);
        }

        return array_filter($classes);
    }

    /**
     * Wrapper inline styles.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return string
     */
    protected function get_wrapper_styles(array $settings): string
    {
        $parts = [];
        if (isset($settings['kng_space_between']['size'])) {
            $parts[] = '--kng-cpt-slider-gap:' . (float) $settings['kng_space_between']['size'] . ($settings['kng_space_between']['unit'] ?? 'px') . ';';
        }

        if (empty($parts)) {
            return '';
        }

        return ' style="' . esc_attr(implode(' ', $parts)) . '"';
    }

    /**
     * Slider data attributes.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return string
     */
    protected function get_slider_data_attributes(array $settings): string
    {
        $desktop = $settings['kng_slides_per_view'] ?? 1;
        if (is_array($desktop)) {
            $desktop = $desktop['size'] ?? 1;
        }
        $tablet = $settings['kng_slides_per_view_tablet'] ?? $desktop;
        if (is_array($tablet)) {
            $tablet = $tablet['size'] ?? $desktop;
        }
        $mobile = $settings['kng_slides_per_view_mobile'] ?? $tablet;
        if (is_array($mobile)) {
            $mobile = $mobile['size'] ?? $tablet;
        }

        $space = $settings['kng_space_between']['size'] ?? 20;
        $speed = $settings['kng_speed'] ?? 600;

        $attributes = [
            'data-slides-per-view' => (int) $desktop,
            'data-slides-per-view-tablet' => (int) $tablet,
            'data-slides-per-view-mobile' => (int) $mobile,
            'data-space-between' => (int) $space,
            'data-speed' => (int) $speed,
            'data-loop' => ($settings['kng_loop'] ?? '') === 'yes' ? 'yes' : 'no',
            'data-autoplay' => ($settings['kng_autoplay'] ?? '') === 'yes' ? 'yes' : 'no',
            'data-autoplay-delay' => isset($settings['kng_autoplay_delay']) ? (int) $settings['kng_autoplay_delay'] : 3200,
            'data-pagination' => ($settings['kng_show_pagination'] ?? 'yes') === 'yes' ? 'yes' : 'no',
            'data-navigation' => ($settings['kng_show_navigation'] ?? 'yes') === 'yes' ? 'yes' : 'no',
        ];

        $parts = [];
        foreach ($attributes as $key => $value) {
            $parts[] = esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        return implode(' ', $parts);
    }

    /**
     * Render slide.
     *
     * @param array<string, mixed> $settings Settings.
     * @param bool                 $is_pro   Whether pro mode is enabled.
     *
     * @return void
     */
    protected function render_slide(array $settings, bool $is_pro): void
    {
        $card_classes = ['king-addons-cpt-slider__card'];
        $animation = $settings['kng_hover_animation'] ?? 'none';
        if ($animation !== 'none') {
            $card_classes[] = 'is-anim-' . sanitize_html_class((string) $animation);
        }

        $show_image = ($settings['kng_show_image'] ?? 'yes') === 'yes';
        $show_meta = ($settings['kng_show_meta'] ?? 'yes') === 'yes';
        $show_excerpt = ($settings['kng_show_excerpt'] ?? 'yes') === 'yes';
        $show_button = ($settings['kng_show_read_more'] ?? 'yes') === 'yes';
        $card_linkable = ($settings['kng_card_linkable'] ?? '') === 'yes';

        $excerpt_length = isset($settings['kng_excerpt_length']) ? (int) $settings['kng_excerpt_length'] : 18;
        $excerpt_length = max(5, $excerpt_length);

        $card_attributes = '';
        if ($card_linkable) {
            $card_attributes = ' data-card-link="' . esc_url(get_permalink()) . '"';
        }

        ?>
        <div class="king-addons-cpt-slider__slide swiper-slide"<?php echo $card_attributes; ?>>
            <article class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
                <?php if ($show_image && has_post_thumbnail()) : ?>
                    <div class="king-addons-cpt-slider__media">
                        <a class="king-addons-cpt-slider__thumb-link" href="<?php the_permalink(); ?>">
                            <?php echo Group_Control_Image_Size::get_attachment_image_html($settings, 'kng_image_size'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="king-addons-cpt-slider__body">
                    <h3 class="king-addons-cpt-slider__title">
                        <a href="<?php the_permalink(); ?>"><?php echo esc_html(get_the_title()); ?></a>
                    </h3>

                    <?php if ($show_meta) : ?>
                        <?php $this->render_meta(); ?>
                    <?php endif; ?>

                    <?php if ($is_pro && !empty($settings['kng_custom_fields'])) : ?>
                        <?php $this->render_custom_fields($settings); ?>
                    <?php endif; ?>

                    <?php if ($show_excerpt) : ?>
                        <div class="king-addons-cpt-slider__excerpt">
                            <?php echo esc_html(wp_trim_words(get_the_excerpt(), $excerpt_length)); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_button) : ?>
                        <div class="king-addons-cpt-slider__cta">
                            <a class="king-addons-cpt-slider__read-more" href="<?php the_permalink(); ?>">
                                <?php echo esc_html($settings['kng_read_more_text'] ?? esc_html__('Read more', 'king-addons')); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        </div>
        <?php
    }

    /**
     * Render post meta.
     *
     * @return void
     */
    protected function render_meta(): void
    {
        $author = get_the_author();
        $date = get_the_date();
        ?>
        <div class="king-addons-cpt-slider__meta">
            <span class="king-addons-cpt-slider__meta-item"><?php echo esc_html($author); ?></span>
            <span class="king-addons-cpt-slider__meta-sep">•</span>
            <span class="king-addons-cpt-slider__meta-item"><?php echo esc_html($date); ?></span>
        </div>
        <?php
    }

    /**
     * Render custom fields (Pro only).
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return void
     */
    protected function render_custom_fields(array $settings): void
    {
        if (empty($settings['kng_custom_fields']) || !is_array($settings['kng_custom_fields'])) {
            return;
        }

        $post_id = get_the_ID();

        echo '<div class="king-addons-cpt-slider__fields">';

        foreach ($settings['kng_custom_fields'] as $field) {
            $meta_key = isset($field['kng_custom_field_key']) ? sanitize_key((string) $field['kng_custom_field_key']) : '';
            if ($meta_key === '') {
                continue;
            }

            $raw_value = get_post_meta($post_id, $meta_key, true);
            if (is_array($raw_value)) {
                $raw_value = implode(', ', array_map('wp_strip_all_tags', $raw_value));
            }

            $value = $raw_value !== '' ? $raw_value : ($field['kng_custom_field_fallback'] ?? '');
            if ($value === '' && $value !== '0') {
                continue;
            }

            $label = $field['kng_custom_field_label'] ?? $meta_key;
            $prefix = $field['kng_custom_field_prefix'] ?? '';
            $suffix = $field['kng_custom_field_suffix'] ?? '';

            echo '<div class="king-addons-cpt-slider__field">';
            if ($label !== '') {
                echo '<span class="king-addons-cpt-slider__field-label">' . esc_html($label) . '</span>';
            }
            echo '<span class="king-addons-cpt-slider__field-value">' . esc_html($prefix . $value . $suffix) . '</span>';
            echo '</div>';
        }

        echo '</div>';
    }
}






