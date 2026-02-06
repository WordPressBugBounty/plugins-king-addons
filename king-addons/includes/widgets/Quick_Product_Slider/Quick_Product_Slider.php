<?php
/**
 * Quick Product Slider Widget (Free).
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
 * Renders the Quick Product Slider widget.
 */
class Quick_Product_Slider extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-quick-product-slider';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Quick Product Slider', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-quick-product-slider';
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        $deps = [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-swiper-swiper',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-quick-product-slider-script',
        ];

        if (class_exists('\WooCommerce') && function_exists('wp_script_is') && wp_script_is('wc-add-to-cart', 'registered')) {
            $deps[] = 'wc-add-to-cart';
        }

        return $deps;
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-quick-product-slider-style',
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
        return ['woocommerce', 'product', 'quick', 'slider', 'shop'];
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
        $this->register_query_controls();
        $this->register_slider_controls();
        $this->register_interaction_controls();
        $this->register_navigation_controls();
        $this->register_pagination_controls();
        $this->register_display_controls();
        $this->register_style_card_controls();
        $this->register_style_text_controls();
        $this->register_style_rating_controls();
        $this->register_style_badge_controls();
        $this->register_style_button_controls();
        $this->register_style_view_cart_controls();
        $this->register_style_navigation_controls();
        $this->register_style_pagination_controls();
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
                'default' => 'hover-small-lift',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'hover-small-lift' => esc_html__('Small Lift', 'king-addons'),
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
        if (!class_exists('\WooCommerce')) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $query = $this->build_query($settings);

        if (!$query->have_posts()) {
            wp_reset_postdata();
            return;
        }

        $data_attributes = $this->get_slider_data_attributes($settings);
        $wrapper_classes = $this->get_wrapper_classes($settings);

        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>">
            <div class="king-addons-quick-product-slider__track swiper" <?php echo $data_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <div class="king-addons-quick-product-slider__wrapper swiper-wrapper">
                    <?php
                    while ($query->have_posts()) :
                        $query->the_post();
                        $product = wc_get_product(get_the_ID());
                        if ($product) {
                            $this->render_slide($settings, $product);
                        }
                    endwhile;
                    ?>
                </div>
                <?php if (($settings['kng_show_pagination'] ?? 'yes') === 'yes') : ?>
                    <div class="king-addons-quick-product-slider__pagination swiper-pagination" aria-label="<?php echo esc_attr__('Slider pagination', 'king-addons'); ?>"></div>
                <?php endif; ?>
            </div>
            <?php if (($settings['kng_show_navigation'] ?? 'yes') === 'yes') : ?>
                <div class="king-addons-quick-product-slider__navigation" aria-label="<?php echo esc_attr__('Slider navigation', 'king-addons'); ?>">
                    <button type="button" class="king-addons-quick-product-slider__arrow king-addons-quick-product-slider__arrow--prev swiper-button-prev" aria-label="<?php echo esc_attr__('Previous', 'king-addons'); ?>">
                        <span class="king-addons-quick-product-slider__arrow-icon" aria-hidden="true"></span>
                    </button>
                    <button type="button" class="king-addons-quick-product-slider__arrow king-addons-quick-product-slider__arrow--next swiper-button-next" aria-label="<?php echo esc_attr__('Next', 'king-addons'); ?>">
                        <span class="king-addons-quick-product-slider__arrow-icon" aria-hidden="true"></span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php

        wp_reset_postdata();
    }

    /**
     * Query controls.
     *
     * @return void
     */
    protected function register_query_controls(): void
    {
        $this->start_controls_section(
            'kng_query_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Query', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_products_per_page',
            [
                'label' => esc_html__('Products Number', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 12,
                'step' => 1,
                'default' => 6,
                'description' => esc_html__('Free version limited to 12 products.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_orderby',
            [
                'label' => esc_html__('Order By', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date' => esc_html__('Date', 'king-addons'),
                    'title' => esc_html__('Title', 'king-addons'),
                    'price' => esc_html__('Price', 'king-addons'),
                    'popularity' => esc_html__('Popularity', 'king-addons'),
                    'rating' => esc_html__('Rating', 'king-addons'),
                    'rand' => esc_html__('Random', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_order',
            [
                'label' => esc_html__('Order', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'DESC' => esc_html__('DESC', 'king-addons'),
                    'ASC' => esc_html__('ASC', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_categories',
            [
                'label' => sprintf(__('Categories %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Comma-separated slugs.', 'king-addons'),
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Slider controls.
     *
     * @return void
     */
    protected function register_slider_controls(): void
    {
        $this->start_controls_section(
            'kng_slider_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Slider Settings', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_slides_per_view',
            [
                'label' => esc_html__('Slides Per View', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'step' => 1,
                'default' => 3,
            ]
        );

        $this->add_control(
            'kng_slides_per_view_tablet',
            [
                'label' => esc_html__('Slides Per View (Tablet)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'step' => 1,
                'default' => 2,
            ]
        );

        $this->add_control(
            'kng_slides_per_view_mobile',
            [
                'label' => esc_html__('Slides Per View (Mobile)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'step' => 1,
                'default' => 1,
            ]
        );

        $this->add_control(
            'kng_space_between',
            [
                'label' => esc_html__('Space Between', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 80,
                'step' => 1,
                'default' => 20,
            ]
        );

        $this->add_control(
            'kng_slides_per_group',
            [
                'label' => esc_html__('Slides Per Group', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'step' => 1,
                'default' => 1,
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
            'kng_autoplay_pause_on_hover',
            [
                'label' => esc_html__('Pause on Hover', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_autoplay_stop_on_interaction',
            [
                'label' => esc_html__('Stop on Interaction', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_autoplay' => 'yes',
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

        $this->end_controls_section();
    }

    /**
     * Navigation controls (layout and placement).
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
                    '{{WRAPPER}} .king-addons-quick-product-slider' => '--kng-quick-product-slider-nav-offset: {{SIZE}}{{UNIT}};',
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

        $this->add_control(
            'kng_navigation_inside_padding',
            [
                'label' => esc_html__('Inside Padding', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'size' => 12,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_show_navigation' => 'yes',
                    'kng_navigation_position' => 'inside',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider' => '--kng-quick-product-slider-nav-padding: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-quick-product-slider' => '--kng-quick-product-slider-nav-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Pagination controls (layout and placement).
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
                    '{{WRAPPER}} .king-addons-quick-product-slider' => '--kng-quick-product-slider-pagination-offset: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-quick-product-slider' => '--kng-quick-product-slider-pagination-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Navigation style controls.
     *
     * @return void
     */
    protected function register_style_navigation_controls(): void
    {
        $this->start_controls_section(
            'kng_style_navigation_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Navigation Arrows', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_show_navigation' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_nav_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 24, 'max' => 96],
                ],
                'default' => [
                    'size' => 44,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider' => '--kng-quick-product-slider-nav-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_nav_icon_size',
            [
                'label' => esc_html__('Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 6, 'max' => 28],
                ],
                'default' => [
                    'size' => 12,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider' => '--kng-quick-product-slider-nav-icon-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('kng_nav_tabs');

        $this->start_controls_tab(
            'kng_nav_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_nav_color',
            [
                'label' => esc_html__('Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__arrow' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_nav_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__arrow' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_nav_border',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__arrow',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_nav_shadow',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__arrow',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_nav_tab_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_nav_color_hover',
            [
                'label' => esc_html__('Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__arrow:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_nav_background_hover',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__arrow:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_nav_border_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__arrow:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_nav_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__arrow:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'kng_nav_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__arrow' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Pagination style controls.
     *
     * @return void
     */
    protected function register_style_pagination_controls(): void
    {
        $this->start_controls_section(
            'kng_style_pagination_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Pagination Dots', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_show_pagination' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_pagination_size',
            [
                'label' => esc_html__('Dot Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 4, 'max' => 24],
                ],
                'default' => [
                    'size' => 8,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider' => '--kng-quick-product-slider-pagination-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('kng_pagination_tabs');

        $this->start_controls_tab(
            'kng_pagination_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_pagination_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__pagination .swiper-pagination-bullet' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_pagination_tab_active',
            [
                'label' => esc_html__('Active', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_pagination_color_active',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__pagination .swiper-pagination-bullet-active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_pagination_active_width',
            [
                'label' => esc_html__('Active Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 4, 'max' => 32],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider' => '--kng-quick-product-slider-pagination-active-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
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
        $classes = ['king-addons-quick-product-slider'];

        $nav_position = $settings['kng_navigation_position'] ?? 'outside';
        $classes[] = 'king-addons-quick-product-slider--nav-' . sanitize_html_class((string) $nav_position);

        $nav_alignment = $settings['kng_navigation_alignment'] ?? 'center';
        $classes[] = 'king-addons-quick-product-slider--nav-align-' . sanitize_html_class((string) $nav_alignment);

        if (!empty($settings['kng_navigation_hide_tablet'])) {
            $classes[] = 'king-addons-quick-product-slider--nav-hide-tablet';
        }

        if (!empty($settings['kng_navigation_hide_mobile'])) {
            $classes[] = 'king-addons-quick-product-slider--nav-hide-mobile';
        }

        if (!empty($settings['kng_navigation_skin']) && $settings['kng_navigation_skin'] !== 'default') {
            $classes[] = 'king-addons-quick-product-slider--nav-style-' . sanitize_html_class((string) $settings['kng_navigation_skin']);
        }

        $pagination_position = $settings['kng_pagination_position'] ?? 'outside';
        $classes[] = 'king-addons-quick-product-slider--pagination-' . sanitize_html_class((string) $pagination_position);

        $pagination_alignment = $settings['kng_pagination_alignment'] ?? 'center';
        $classes[] = 'king-addons-quick-product-slider--pagination-align-' . sanitize_html_class((string) $pagination_alignment);

        if (!empty($settings['kng_pagination_skin'])) {
            $skin = (string) $settings['kng_pagination_skin'];
            if ($skin === 'pill') {
                $classes[] = 'king-addons-quick-product-slider--pagination-style-pill';
            } elseif ($skin === 'outlined') {
                $classes[] = 'king-addons-quick-product-slider--pagination-style-outlined';
            }
        }

        return array_filter($classes);
    }

    /**
     * Display controls.
     *
     * @return void
     */
    protected function register_display_controls(): void
    {
        $this->start_controls_section(
            'kng_display_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Display', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_show_rating',
            [
                'label' => esc_html__('Show Rating', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_price',
            [
                'label' => esc_html__('Show Price', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_excerpt',
            [
                'label' => esc_html__('Show Short Description', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_add_to_cart',
            [
                'label' => esc_html__('Show Add to Cart', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_badge',
            [
                'label' => esc_html__('Show Sale Badge', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_badge_text',
            [
                'label' => esc_html__('Sale Badge Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Sale', 'king-addons'),
                'condition' => [
                    'kng_show_badge' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_excerpt_length',
            [
                'label' => esc_html__('Excerpt Length (words)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 5,
                'max' => 60,
                'step' => 1,
                'default' => 20,
                'condition' => [
                    'kng_show_excerpt' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'kng_image_size',
                'default' => 'medium',
            ]
        );

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
                    '{{WRAPPER}} .king-addons-quick-product-slider__card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_card_border',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__card',
            ]
        );

        $this->add_control(
            'kng_card_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 50],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__card',
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
                    '{{WRAPPER}} .king-addons-quick-product-slider__card' => 'box-shadow: none !important;',
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
                    '{{WRAPPER}} .king-addons-quick-product-slider__card:hover' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->add_control(
            'kng_card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_price_typography',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__price',
            ]
        );

        $this->add_control(
            'kng_price_color',
            [
                'label' => esc_html__('Price Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_excerpt_typography',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__excerpt',
            ]
        );

        $this->add_control(
            'kng_excerpt_color',
            [
                'label' => esc_html__('Excerpt Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__excerpt' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Rating styles.
     *
     * @return void
     */
    protected function register_style_rating_controls(): void
    {
        $this->start_controls_section(
            'kng_style_rating_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Rating', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_show_rating' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_rating_color_empty',
            [
                'label' => esc_html__('Empty Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__rating .star-rating' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rating_color_filled',
            [
                'label' => esc_html__('Filled Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__rating .star-rating span' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rating_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 10, 'max' => 32],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__rating .star-rating' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Sale badge styles.
     *
     * @return void
     */
    protected function register_style_badge_controls(): void
    {
        $this->start_controls_section(
            'kng_style_badge_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Sale Badge', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_show_badge' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_badge_typography',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__badge',
            ]
        );

        $this->add_control(
            'kng_badge_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__badge' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_badge_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__badge' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_badge_border',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__badge',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_badge_shadow',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__badge',
            ]
        );

        $this->add_control(
            'kng_badge_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 50],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__badge' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_badge_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_badge_offset_top',
            [
                'label' => esc_html__('Offset Top', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__badge' => 'top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_badge_offset_left',
            [
                'label' => esc_html__('Offset Left', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__badge' => 'left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Button styles.
     *
     * @return void
     */
    protected function register_style_button_controls(): void
    {
        $this->start_controls_section(
            'kng_style_button_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Add to Cart', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__cta .button',
            ]
        );

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__cta .button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_button_indicator_size',
            [
                'label' => esc_html__('Indicator Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 8, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__cta .king-addons-quick-product-slider__add-to-cart' => '--king-addons-atc-indicator-size: {{SIZE}}{{UNIT}};',
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
            'kng_button_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__cta .button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__cta .button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__cta .button',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__cta .button',
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
            'kng_button_color_hover',
            [
                'label' => esc_html__('Hover Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__cta .button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg_hover',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__cta .button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border_hover',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__cta .button:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__cta .button:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'kng_button_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__cta .button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * View cart link styles (WooCommerce injects `.added_to_cart` link after AJAX add to cart).
     *
     * @return void
     */
    protected function register_style_view_cart_controls(): void
    {
        $this->start_controls_section(
            'kng_style_view_cart_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('View Cart Link', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_view_cart_gap',
            [
                'label' => esc_html__('Spacing From Button', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__cta .king-addons-quick-product-slider__add-to-cart + .added_to_cart' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_view_cart_typography',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__cta .added_to_cart',
            ]
        );

        $this->add_responsive_control(
            'kng_view_cart_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__cta .added_to_cart' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('kng_view_cart_style_tabs');

        $this->start_controls_tab(
            'kng_view_cart_style_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_view_cart_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__cta .added_to_cart' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_view_cart_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__cta .added_to_cart' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_view_cart_border',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__cta .added_to_cart',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_view_cart_shadow',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__cta .added_to_cart',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_view_cart_style_tab_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_view_cart_color_hover',
            [
                'label' => esc_html__('Hover Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__cta .added_to_cart:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_view_cart_bg_hover',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__cta .added_to_cart:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_view_cart_border_hover',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__cta .added_to_cart:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_view_cart_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-quick-product-slider__cta .added_to_cart:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'kng_view_cart_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-quick-product-slider__cta .added_to_cart' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Build query for products.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return WP_Query
     */
    protected function build_query(array $settings): WP_Query
    {
        $per_page = !empty($settings['kng_products_per_page']) ? (int) $settings['kng_products_per_page'] : 6;
        $per_page = min($per_page, 12);

        $orderby_setting = $settings['kng_orderby'] ?? 'date';
        $meta_key = $this->get_orderby_meta_key($orderby_setting);

        $args = [
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'order' => $settings['kng_order'] ?? 'DESC',
            'orderby' => $this->map_orderby($orderby_setting),
            'post_status' => 'publish',
        ];

        if ($meta_key) {
            $args['meta_key'] = $meta_key;
        }

        // Categories: gated for pro; ignored in free.

        return new WP_Query($args);
    }

    /**
     * Map orderby to Woo fields.
     *
     * @param string $orderby Orderby value.
     *
     * @return string
     */
    protected function map_orderby(string $orderby): string
    {
        $map = [
            'price' => 'meta_value_num',
            'popularity' => 'meta_value_num',
            'rating' => 'meta_value_num',
            'rand' => 'rand',
            'title' => 'title',
            'date' => 'date',
        ];

        return $map[$orderby] ?? 'date';
    }

    /**
     * Return meta key for meta-based orderby.
     *
     * @param string $orderby Orderby value.
     *
     * @return string|null
     */
    protected function get_orderby_meta_key(string $orderby): ?string
    {
        $map = [
            'price' => '_price',
            'popularity' => 'total_sales',
            'rating' => '_wc_average_rating',
        ];

        return $map[$orderby] ?? null;
    }

    /**
     * Render single slide.
     *
     * @param array<string, mixed> $settings Settings.
     * @param \WC_Product          $product  Product.
     *
     * @return void
     */
    protected function render_slide(array $settings, \WC_Product $product): void
    {
        $card_classes = ['king-addons-quick-product-slider__card'];
        $animation = $settings['kng_hover_animation'] ?? 'none';
        if ($animation !== 'none') {
            $card_classes[] = 'is-anim-' . sanitize_html_class((string) $animation);
        }

        $show_rating = ($settings['kng_show_rating'] ?? 'yes') === 'yes';
        $show_price = ($settings['kng_show_price'] ?? 'yes') === 'yes';
        $show_excerpt = ($settings['kng_show_excerpt'] ?? 'yes') === 'yes';
        $show_button = ($settings['kng_show_add_to_cart'] ?? 'yes') === 'yes';
        $show_badge = ($settings['kng_show_badge'] ?? 'yes') === 'yes';
        $badge_text = !empty($settings['kng_badge_text']) ? (string) $settings['kng_badge_text'] : esc_html__('Sale', 'king-addons');
        $excerpt_length = !empty($settings['kng_excerpt_length']) ? max(1, (int) $settings['kng_excerpt_length']) : 20;

        ?>
        <div class="king-addons-quick-product-slider__slide swiper-slide">
            <article class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
                <div class="king-addons-quick-product-slider__media">
                    <a href="<?php the_permalink(); ?>" class="king-addons-quick-product-slider__link">
                        <?php echo $this->get_product_image_html($product, $settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </a>
                    <?php if ($show_badge && $product->is_on_sale()) : ?>
                        <span class="king-addons-quick-product-slider__badge"><?php echo esc_html($badge_text); ?></span>
                    <?php endif; ?>
                </div>

                <div class="king-addons-quick-product-slider__body">
                    <h3 class="king-addons-quick-product-slider__title">
                        <a href="<?php the_permalink(); ?>"><?php echo esc_html($product->get_name()); ?></a>
                    </h3>

                    <?php if ($show_rating && wc_review_ratings_enabled()) : ?>
                        <div class="king-addons-quick-product-slider__rating">
                            <?php echo wc_get_rating_html($product->get_average_rating()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_price) : ?>
                        <div class="king-addons-quick-product-slider__price">
                            <?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_excerpt) : ?>
                        <div class="king-addons-quick-product-slider__excerpt">
                            <?php echo wp_kses_post(wp_trim_words($product->get_short_description(), $excerpt_length)); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_button) : ?>
                        <div class="king-addons-quick-product-slider__cta">
                            <?php echo $this->render_add_to_cart_button($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        </div>
        <?php
    }

    /**
     * Get product image HTML with Elementor image size control.
     *
     * @param \WC_Product          $product  Product instance.
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return string
     */
    protected function get_product_image_html(\WC_Product $product, array $settings): string
    {
        $image_id = $product->get_image_id();

        if ($image_id) {
            $image_html = Group_Control_Image_Size::get_attachment_image_html($settings, 'kng_image_size', $image_id);
            if (!empty($image_html)) {
                return $image_html;
            }
        }

        $fallback_size = $settings['kng_image_size_size'] ?? 'medium';

        return $product->get_image($fallback_size);
    }

    /**
     * Render a controlled Add to Cart button without relying on Woo shortcode.
     *
     * @param \WC_Product $product Product instance.
     *
     * @return string
     */
    protected function render_add_to_cart_button(\WC_Product $product): string
    {
        if (!$product->is_purchasable() || !$product->is_in_stock()) {
            return '<span class="king-addons-quick-product-slider__add-to-cart is-disabled">' . esc_html__('Unavailable', 'king-addons') . '</span>';
        }

        $classes = [
            'king-addons-quick-product-slider__add-to-cart',
            'button',
            'product_type_' . $product->get_type(),
        ];

        if ($product->supports('ajax_add_to_cart')) {
            $classes[] = 'ajax_add_to_cart';
            $classes[] = 'add_to_cart_button';
        }

        $attributes = [
            'href' => $product->add_to_cart_url(),
            'data-quantity' => 1,
            'data-product_id' => $product->get_id(),
            'data-product_sku' => $product->get_sku(),
            'rel' => 'nofollow',
            'class' => implode(' ', array_filter($classes)),
            'aria-label' => wp_strip_all_tags($product->add_to_cart_description()),
        ];

        return '<a ' . wc_implode_html_attributes($attributes) . '><span class="king-addons-quick-product-slider__add-to-cart-label">' . esc_html($product->add_to_cart_text()) . '</span></a>';
    }

    /**
     * Build slider data attributes.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return string
     */
    protected function get_slider_data_attributes(array $settings): string
    {
        $slides = !empty($settings['kng_slides_per_view']) ? (int) $settings['kng_slides_per_view'] : 1;
        $slides_tablet = !empty($settings['kng_slides_per_view_tablet']) ? (int) $settings['kng_slides_per_view_tablet'] : $slides;
        $slides_mobile = !empty($settings['kng_slides_per_view_mobile']) ? (int) $settings['kng_slides_per_view_mobile'] : $slides_tablet;
        $space = !empty($settings['kng_space_between']) ? (int) $settings['kng_space_between'] : 20;
        $slides_per_group = !empty($settings['kng_slides_per_group']) ? (int) $settings['kng_slides_per_group'] : 1;
        $speed = !empty($settings['kng_speed']) ? (int) $settings['kng_speed'] : 600;
        $autoplay = $settings['kng_autoplay'] ?? '';
        $autoplay_delay = !empty($settings['kng_autoplay_delay']) ? (int) $settings['kng_autoplay_delay'] : 3200;
        $autoplay_pause_on_hover = $settings['kng_autoplay_pause_on_hover'] ?? '';
        $autoplay_stop_on_interaction = $settings['kng_autoplay_stop_on_interaction'] ?? '';
        $loop = $settings['kng_loop'] ?? 'yes';
        $show_nav = $settings['kng_show_navigation'] ?? 'yes';
        $show_pagination = $settings['kng_show_pagination'] ?? 'yes';

        $attrs = [
            'data-slides-per-view' => $slides,
            'data-slides-per-view-tablet' => $slides_tablet,
            'data-slides-per-view-mobile' => $slides_mobile,
            'data-space-between' => $space,
            'data-slides-per-group' => $slides_per_group,
            'data-speed' => $speed,
            'data-autoplay' => $autoplay,
            'data-autoplay-delay' => $autoplay_delay,
            'data-autoplay-pause-on-hover' => $autoplay_pause_on_hover,
            'data-autoplay-stop-on-interaction' => $autoplay_stop_on_interaction,
            'data-loop' => $loop,
            'data-navigation' => $show_nav,
            'data-pagination' => $show_pagination,
        ];

        $out = [];
        foreach ($attrs as $key => $value) {
            $out[] = esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        return implode(' ', $out);
    }

    /**
     * Pro notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'quick-product-slider', [
                'Gallery thumbs and zoom in modal',
                'Advanced hover animations',
                'Navigation/pagination skins',
                'Category filter and higher limits',
            ]);
        }
    }
}


