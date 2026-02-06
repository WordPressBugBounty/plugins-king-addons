<?php
/**
 * Quick Post Slider Widget (Free)
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Utils;
use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the Quick Post Slider widget.
 */
class Quick_Post_Slider extends Widget_Base
{
    /**
    * Widget slug.
    */
    public function get_name(): string
    {
        return 'king-addons-quick-post-slider';
    }

    /**
     * Widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Quick Post Slider', 'king-addons');
    }

    /**
     * Widget icon.
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-simple-post-slider';
    }

    /**
     * Scripts used.
     *
     * @return array<string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-swiper-swiper',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-quick-post-slider-script',
        ];
    }

    /**
     * Styles used.
     *
     * @return array<string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-swiper-swiper',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-quick-post-slider-style',
        ];
    }

    /**
     * Widget categories.
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Widget keywords.
     */
    public function get_keywords(): array
    {
        return ['posts', 'slider', 'carousel', 'blog', 'king-addons'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        /**
     * Register controls.
     */
    public function register_controls(): void
    {
        $this->register_query_controls();
        $this->register_slider_controls();
        $this->register_interaction_controls();
        $this->register_navigation_controls();
        $this->register_pagination_controls();
        $this->register_style_card_controls();
        $this->register_style_text_controls();
        $this->register_style_meta_controls();
        $this->register_style_button_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Register Pro-only interaction controls placeholder.
     *
     * The Pro version overrides this method to add additional interaction
     * controls without overriding the full `register_controls()` flow.
     *
     * @return void
     */
    public function register_interaction_controls(): void
    {
        // Intentionally empty in Free.
    }

    /**
     * Render widget on frontend.
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $this->render_output($settings);
    }

    /**
     * Render output with settings.
     *
     * @param array<string, mixed> $settings settings.
     */
    public function render_output(array $settings): void
    {
        $query = $this->build_query($settings);

        if (!$query->have_posts()) {
            wp_reset_postdata();
            return;
        }

        $wrapper_classes = $this->get_wrapper_classes($settings);
        $wrapper_styles = $this->get_wrapper_style_attributes($settings);
        $data_attributes = $this->get_slider_data_attributes($settings);

        $show_navigation = ($settings['kng_show_navigation'] ?? '') === 'yes';
        $show_pagination = ($settings['kng_show_pagination'] ?? '') === 'yes';

        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"<?php echo $wrapper_styles; ?>>
            <div class="king-addons-post-slider__track swiper" <?php echo $data_attributes; ?>>
                <div class="king-addons-post-slider__wrapper swiper-wrapper">
                    <?php
                    while ($query->have_posts()) :
                        $query->the_post();
                        $this->render_slide($settings);
                    endwhile;
                    ?>
                </div>
                <?php if ($show_pagination) : ?>
                    <div class="king-addons-post-slider__pagination swiper-pagination" aria-label="<?php echo esc_attr__('Slider pagination', 'king-addons'); ?>"></div>
                <?php endif; ?>
            </div>
            <?php if ($show_navigation) : ?>
                <div class="king-addons-post-slider__navigation" aria-label="<?php echo esc_attr__('Slider navigation', 'king-addons'); ?>">
                    <button type="button" class="king-addons-post-slider__arrow king-addons-post-slider__arrow--prev swiper-button-prev" aria-label="<?php echo esc_attr__('Previous slide', 'king-addons'); ?>">
                        <span class="king-addons-post-slider__arrow-icon" aria-hidden="true"></span>
                    </button>
                    <button type="button" class="king-addons-post-slider__arrow king-addons-post-slider__arrow--next swiper-button-next" aria-label="<?php echo esc_attr__('Next slide', 'king-addons'); ?>">
                        <span class="king-addons-post-slider__arrow-icon" aria-hidden="true"></span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
    }

    public function add_pro_posts_number_controls(): void
    {
        $this->add_control(
            'kng_posts_per_page',
            [
                'label' => esc_html__('Posts Number', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 6,
                'step' => 1,
                'default' => 6,
                'description' => esc_html__('Free version is limited to 6 posts.', 'king-addons'),
            ]
        );
    }

    /**
     * Query controls.
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

        $this->add_pro_posts_number_controls();

        $this->add_control(
            'kng_orderby',
            [
                'label' => esc_html__('Order By', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date' => esc_html__('Date', 'king-addons'),
                    'title' => esc_html__('Title', 'king-addons'),
                    'rand' => esc_html__('Random', 'king-addons'),
                    'menu_order' => esc_html__('Menu Order', 'king-addons'),
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
                    'DESC' => esc_html__('Descending', 'king-addons'),
                    'ASC' => esc_html__('Ascending', 'king-addons'),
                ],
            ]
        );

        $this->add_control_categories();

        $this->add_control(
            'kng_exclude_sticky',
            [
                'label' => esc_html__('Ignore Sticky Posts', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

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

        $this->add_control(
            'kng_image_ratio',
            [
                'label' => esc_html__('Image Ratio', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'original',
                'options' => [
                    'original' => esc_html__('Original', 'king-addons'),
                    'square' => esc_html__('1:1', 'king-addons'),
                    '4-3' => esc_html__('4:3', 'king-addons'),
                    '3-2' => esc_html__('3:2', 'king-addons'),
                    '16-9' => esc_html__('16:9', 'king-addons'),
                ],
                'condition' => [
                    'kng_show_image' => 'yes',
                ],
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
                ],
                'condition' => [
                    'kng_show_image' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider' => '--kng-post-slider-image-fit: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_image_radius',
            [
                'label' => esc_html__('Image Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'condition' => [
                    'kng_show_image' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__media' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_image_spacing',
            [
                'label' => esc_html__('Image Bottom Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'condition' => [
                    'kng_show_image' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__media' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_show_meta',
            [
                'label' => esc_html__('Show Meta', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_author',
            [
                'label' => esc_html__('Show Author', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_show_meta' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_show_date',
            [
                'label' => esc_html__('Show Date', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_show_meta' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_meta_separator',
            [
                'label' => esc_html__('Meta Separator', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '•',
                'condition' => [
                    'kng_show_meta' => 'yes',
                ],
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
                'label' => esc_html__('Make Card Clickable', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'description' => esc_html__('Card click is disabled inside the Elementor editor to avoid interfering with editing.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_card_link_new_tab',
            [
                'label' => esc_html__('Open Card in New Tab', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_card_linkable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Categories control (Pro-only entry point).
     *
     * @return void
     */
    protected function add_control_categories(): void
    {
        $this->add_control(
            'kng_categories',
            [
                'label' => sprintf(__('Categories %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Enter category slugs separated by commas.', 'king-addons'),
                'placeholder' => esc_html__('news,features', 'king-addons'),
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );
    }

    /**
     * Slider controls.
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
                'label' => esc_html__('Space Between (px)', 'king-addons'),
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
            ]
        );

        $this->add_control(
            'kng_loop',
            [
                'label' => esc_html__('Loop', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_autoplay',
            [
                'label' => esc_html__('Autoplay', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_autoplay_delay',
            [
                'label' => esc_html__('Autoplay Delay (ms)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 100,
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
                'step' => 50,
                'default' => 600,
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Navigation controls.
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
                    '{{WRAPPER}} .king-addons-post-slider' => '--kng-post-slider-nav-offset: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-post-slider' => '--kng-post-slider-nav-gap: {{SIZE}}{{UNIT}};',
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

        $this->add_pro_navigation_controls();

        $this->end_controls_section();
    }

    /**
     * Pagination controls.
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
                    '{{WRAPPER}} .king-addons-post-slider' => '--kng-post-slider-pagination-offset: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-post-slider' => '--kng-post-slider-pagination-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_pro_pagination_controls();

        $this->end_controls_section();
    }

    /**
     * Card style controls.
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

        $this->add_responsive_control(
            'kng_track_padding',
            [
                'label' => esc_html__('Track Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__track' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_card_padding',
            [
                'label' => esc_html__('Card Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_card_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_card_background_hover',
            [
                'label' => esc_html__('Background Hover', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__card:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_card_border',
                'selector' => '{{WRAPPER}} .king-addons-post-slider__card',
            ]
        );

        $this->add_control(
            'kng_card_border_color_hover',
            [
                'label' => esc_html__('Border Color Hover', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__card:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_card_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-post-slider__card',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-post-slider__card:hover',
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
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__card' => 'box-shadow: none !important;',
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
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__card:hover' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Text style controls.
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
                'selector' => '{{WRAPPER}} .king-addons-post-slider__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_title_spacing',
            [
                'label' => esc_html__('Title Bottom Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_excerpt_typography',
                'selector' => '{{WRAPPER}} .king-addons-post-slider__excerpt',
            ]
        );

        $this->add_control(
            'kng_excerpt_color',
            [
                'label' => esc_html__('Excerpt Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__excerpt' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_excerpt_spacing',
            [
                'label' => esc_html__('Excerpt Bottom Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__excerpt' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_text_alignment',
            [
                'label' => esc_html__('Text Alignment', 'king-addons'),
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
                'prefix_class' => 'king-addons-post-slider--text-align-',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Meta style controls.
     */
    protected function register_style_meta_controls(): void
    {
        $this->start_controls_section(
            'kng_style_meta_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Meta', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_show_meta' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_meta_typography',
                'selector' => '{{WRAPPER}} .king-addons-post-slider__meta',
            ]
        );

        $this->add_control(
            'kng_meta_color',
            [
                'label' => esc_html__('Meta Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__meta' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_meta_spacing',
            [
                'label' => esc_html__('Meta Bottom Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__meta' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Button style controls.
     */
    protected function register_style_button_controls(): void
    {
        $this->start_controls_section(
            'kng_style_button_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_show_read_more' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_button_alignment',
            [
                'label' => esc_html__('Button Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
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
                'toggle' => false,
                'default' => 'left',
                'prefix_class' => 'king-addons-post-slider--button-align-',
            ]
        );

        $this->add_control(
            'kng_button_text_alignment',
            [
                'label' => esc_html__('Button Text Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
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
                'toggle' => false,
                'default' => 'center',
                'prefix_class' => 'king-addons-post-slider--button-text-align-',
            ]
        );

        $this->add_control(
            'kng_button_full_width',
            [
                'label' => esc_html__('Full Width', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__button' => 'width: 100%;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-post-slider__button',
            ]
        );

        $this->start_controls_tabs('kng_button_tabs');

        $this->start_controls_tab(
            'kng_button_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_button_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border',
                'selector' => '{{WRAPPER}} .king-addons-post-slider__button',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow',
                'selector' => '{{WRAPPER}} .king-addons-post-slider__button',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_button_tab_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_button_text_color_hover',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_background_hover',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_border_color_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-post-slider__button:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'kng_button_spacing',
            [
                'label' => esc_html__('Button Top Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__button' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_button_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_shadow_disable',
            [
                'label' => esc_html__('Disable Box Shadow', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__button' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->add_control(
            'kng_button_shadow_hover_disable',
            [
                'label' => esc_html__('Disable Box Shadow on Hover', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-post-slider__button:hover' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Build WP query.
     *
     * @param array<string, mixed> $settings settings.
     */
    protected function build_query(array $settings): WP_Query
    {
        $per_page = !empty($settings['kng_posts_per_page']) ? (int) $settings['kng_posts_per_page'] : 6;
        $order = strtoupper((string) ($settings['kng_order'] ?? 'DESC'));
        $order = in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC';

        $allowed_orderby = ['date', 'title', 'rand', 'menu_order'];
        $orderby = (string) ($settings['kng_orderby'] ?? 'date');
        $orderby = in_array($orderby, $allowed_orderby, true) ? $orderby : 'date';

        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            $per_page = min($per_page, 6);
        }

        $args = [
            'post_type' => 'post',
            'posts_per_page' => $per_page,
            'order' => $order,
            'orderby' => $orderby,
        ];

        if (!empty($settings['kng_categories']) && king_addons_freemius()->can_use_premium_code__premium_only()) {
            $slugs = array_filter(array_map('sanitize_title', array_map('trim', explode(',', (string) $settings['kng_categories']))));
            if (!empty($slugs)) {
                $args['category_name'] = implode(',', $slugs);
            }
        }

        if (!empty($settings['kng_exclude_sticky'])) {
            $args['ignore_sticky_posts'] = true;
        }

        return new WP_Query($args);
    }

    /**
     * Render single slide.
     *
     * @param array<string, mixed> $settings settings.
     */
    protected function render_slide(array $settings): void
    {
        $show_image = ($settings['kng_show_image'] ?? '') === 'yes';
        $show_meta = ($settings['kng_show_meta'] ?? '') === 'yes';
        $show_author = ($settings['kng_show_author'] ?? 'yes') === 'yes';
        $show_date = ($settings['kng_show_date'] ?? 'yes') === 'yes';
        $show_excerpt = ($settings['kng_show_excerpt'] ?? '') === 'yes';
        $show_read_more = ($settings['kng_show_read_more'] ?? '') === 'yes';
        $card_linkable = ($settings['kng_card_linkable'] ?? '') === 'yes';

        $title = get_the_title();
        $permalink = get_permalink();
        $image_html = '';

        if ($show_image) {
            if (has_post_thumbnail()) {
                $image_html = wp_get_attachment_image(
                    get_post_thumbnail_id(),
                    $settings['kng_image_size_size'] ?? 'medium',
                    false,
                    [
                        'alt' => esc_attr($title),
                    ]
                );
            }

            if (empty($image_html)) {
                $image_html = '<img src="' . esc_url(Utils::get_placeholder_image_src()) . '" alt="' . esc_attr($title) . '"/>';
            }
        }

        $excerpt_length = !empty($settings['kng_excerpt_length']) ? (int) $settings['kng_excerpt_length'] : 18;
        $excerpt_text = $show_excerpt ? wp_trim_words(get_the_excerpt(), $excerpt_length) : '';

        $meta_parts = [];
        if ($show_meta) {
            if ($show_date) {
                $meta_parts[] = get_the_date();
            }
            if ($show_author) {
                $meta_parts[] = get_the_author();
            }
        }

        $meta_separator = isset($settings['kng_meta_separator']) ? trim((string) $settings['kng_meta_separator']) : '•';
        $meta_glue = $meta_separator === '' ? ' ' : ' ' . $meta_separator . ' ';

        $card_attributes = [];
        if ($card_linkable && !empty($permalink)) {
            $card_attributes[] = 'data-card-link="' . esc_url($permalink) . '"';
            if (($settings['kng_card_link_new_tab'] ?? '') === 'yes') {
                $card_attributes[] = 'data-card-link-target="_blank"';
            }
            $card_attributes[] = 'tabindex="0"';
            $card_attributes[] = 'role="link"';
            $card_attributes[] = 'aria-label="' . esc_attr(sprintf(__('Open %s', 'king-addons'), $title)) . '"';
        }

        ?>
        <div class="king-addons-post-slider__slide swiper-slide"<?php echo !empty($card_attributes) ? ' ' . implode(' ', $card_attributes) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <article class="king-addons-post-slider__card">
                <?php if (!empty($image_html)) : ?>
                    <div class="king-addons-post-slider__media">
                        <a href="<?php the_permalink(); ?>" class="king-addons-post-slider__media-link">
                            <?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="king-addons-post-slider__body">
                    <h3 class="king-addons-post-slider__title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>

                    <?php if (!empty($meta_parts)) : ?>
                        <div class="king-addons-post-slider__meta">
                            <?php echo esc_html(implode($meta_glue, $meta_parts)); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($excerpt_text)) : ?>
                        <div class="king-addons-post-slider__excerpt"><?php echo esc_html($excerpt_text); ?></div>
                    <?php endif; ?>

                    <?php if ($show_read_more) : ?>
                        <a class="king-addons-post-slider__button" href="<?php the_permalink(); ?>">
                            <?php echo esc_html($settings['kng_read_more_text'] ?? esc_html__('Read more', 'king-addons')); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </article>
        </div>
        <?php
    }

    /**
     * Data attributes for JS.
     *
     * @param array<string, mixed> $settings settings.
     */
    protected function get_slider_data_attributes(array $settings): string
    {
        $slides = !empty($settings['kng_slides_per_view']) ? (int) $settings['kng_slides_per_view'] : 1;
        $slides_tablet = !empty($settings['kng_slides_per_view_tablet']) ? (int) $settings['kng_slides_per_view_tablet'] : $slides;
        $slides_mobile = !empty($settings['kng_slides_per_view_mobile']) ? (int) $settings['kng_slides_per_view_mobile'] : $slides_tablet;
        $space_between = isset($settings['kng_space_between']['size']) ? (int) $settings['kng_space_between']['size'] : 20;
        $autoplay = ($settings['kng_autoplay'] ?? '') === 'yes' ? 'yes' : 'no';
        $autoplay_delay = !empty($settings['kng_autoplay_delay']) ? (int) $settings['kng_autoplay_delay'] : 3200;
        $autoplay_pause_on_hover = ($settings['kng_autoplay_pause_on_hover'] ?? '') === 'yes' ? 'yes' : 'no';
        $autoplay_stop_on_interaction = ($settings['kng_autoplay_stop_on_interaction'] ?? '') === 'yes' ? 'yes' : 'no';
        $loop = ($settings['kng_loop'] ?? '') === 'yes' ? 'yes' : 'no';
        $speed = !empty($settings['kng_speed']) ? (int) $settings['kng_speed'] : 600;

        $show_navigation = ($settings['kng_show_navigation'] ?? '') === 'yes' ? 'yes' : 'no';
        $show_pagination = ($settings['kng_show_pagination'] ?? '') === 'yes' ? 'yes' : 'no';

        $attributes = [
            'data-slides-per-view' => $slides,
            'data-slides-per-view-tablet' => $slides_tablet,
            'data-slides-per-view-mobile' => $slides_mobile,
            'data-space-between' => $space_between,
            'data-autoplay' => $autoplay,
            'data-autoplay-delay' => $autoplay_delay,
            'data-autoplay-pause-on-hover' => $autoplay_pause_on_hover,
            'data-autoplay-stop-on-interaction' => $autoplay_stop_on_interaction,
            'data-loop' => $loop,
            'data-speed' => $speed,
            'data-navigation' => $show_navigation,
            'data-pagination' => $show_pagination,
        ];

        $output = [];

        foreach ($attributes as $key => $value) {
            $output[] = esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        return implode(' ', $output);
    }

    /**
     * Wrapper classes.
     *
     * @param array<string, mixed> $settings settings.
     */
    protected function get_wrapper_classes(array $settings): array
    {
        return $this->get_wrapper_classes_base($settings);
    }

    /**
     * Base wrapper classes builder.
     *
     * This method exists to allow the Pro version to extend wrapper classes
     * without calling `parent::` methods.
     *
     * @param array<string, mixed> $settings settings.
     *
     * @return array<int, string>
     */
    protected function get_wrapper_classes_base(array $settings): array
    {
        $classes = ['king-addons-post-slider'];

        $image_ratio = $settings['kng_image_ratio'] ?? 'original';
        if ($image_ratio !== 'original') {
            $classes[] = 'king-addons-post-slider--image-ratio-' . sanitize_html_class((string) $image_ratio);
        }

        $nav_position = $settings['kng_navigation_position'] ?? 'outside';
        $classes[] = 'king-addons-post-slider--nav-' . sanitize_html_class((string) $nav_position);

        $nav_alignment = $settings['kng_navigation_alignment'] ?? 'center';
        $classes[] = 'king-addons-post-slider--nav-align-' . sanitize_html_class((string) $nav_alignment);

        $pagination_position = $settings['kng_pagination_position'] ?? 'outside';
        $classes[] = 'king-addons-post-slider--pagination-' . sanitize_html_class((string) $pagination_position);

        $pagination_alignment = $settings['kng_pagination_alignment'] ?? 'center';
        $classes[] = 'king-addons-post-slider--pagination-align-' . sanitize_html_class((string) $pagination_alignment);

        if (!empty($settings['kng_navigation_hide_tablet'])) {
            $classes[] = 'king-addons-post-slider--nav-hide-tablet';
        }

        if (!empty($settings['kng_navigation_hide_mobile'])) {
            $classes[] = 'king-addons-post-slider--nav-hide-mobile';
        }

        return array_filter($classes);
    }

    /**
     * Wrapper inline styles.
     *
     * @param array<string, mixed> $settings settings.
     */
    protected function get_wrapper_style_attributes(array $settings): string
    {
        $styles = [];

        if (isset($settings['kng_pagination_offset']['size'])) {
            $styles[] = '--kng-post-slider-pagination-offset: ' . (float) $settings['kng_pagination_offset']['size'] . $settings['kng_pagination_offset']['unit'] . ';';
        }

        if (isset($settings['kng_pagination_gap']['size'])) {
            $styles[] = '--kng-post-slider-pagination-gap: ' . (float) $settings['kng_pagination_gap']['size'] . $settings['kng_pagination_gap']['unit'] . ';';
        }

        if (isset($settings['kng_navigation_gap']['size'])) {
            $styles[] = '--kng-post-slider-nav-gap: ' . (float) $settings['kng_navigation_gap']['size'] . $settings['kng_navigation_gap']['unit'] . ';';
        }

        $style_string = '';

        if (!empty($styles)) {
            $style_string = ' style="' . esc_attr(implode(' ', $styles)) . '"';
        }

        return $style_string;
    }

    /**
     * Pro nav placeholder.
     */
    public function add_pro_navigation_controls(): void
    {
        $this->add_control(
            'kng_navigation_skin',
            [
                'label' => sprintf(__('Arrow Style %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'default' => 'elevated',
                'options' => [
                    'elevated' => esc_html__('Elevated', 'king-addons'),
                    'pro-solid' => esc_html__('Solid (Pro)', 'king-addons'),
                    'pro-ghost' => esc_html__('Ghost (Pro)', 'king-addons'),
                    'pro-minimal' => esc_html__('Minimal (Pro)', 'king-addons'),
                ],
                'condition' => [
                    'kng_show_navigation' => 'yes',
                ],
            ]
        );
    }

    /**
     * Pro pagination placeholder.
     */
    public function add_pro_pagination_controls(): void
    {
        $this->add_control(
            'kng_pagination_skin',
            [
                'label' => sprintf(__('Dots Style %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'default' => 'dot',
                'options' => [
                    'dot' => esc_html__('Dots', 'king-addons'),
                    'pro-pill' => esc_html__('Pill (Pro)', 'king-addons'),
                    'pro-outlined' => esc_html__('Outlined (Pro)', 'king-addons'),
                ],
                'condition' => [
                    'kng_show_pagination' => 'yes',
                ],
            ]
        );
    }

    /**
     * Pro notice section.
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_can_use_pro()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'quick-post-slider', [
                'Navigation and pagination style presets',
                'Extended placement and offset controls',
                'Advanced hover animations',
                'Unlimited posts per page',
                'Category filtering',
            ]);
        }
    }
}
