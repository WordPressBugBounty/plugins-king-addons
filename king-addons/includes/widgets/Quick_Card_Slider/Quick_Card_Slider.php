<?php
/**
 * Quick Card Slider Widget (Free)
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the Quick Card Slider widget for the free version.
 */
class Quick_Card_Slider extends Widget_Base
{
    /**
     * Get widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-quick-card-slider';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Quick Card Slider', 'king-addons');
    }

    /**
     * Get widget icon CSS classes.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-card-slider';
    }

    /**
     * Get script dependencies.
     *
     * @return array<string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-swiper-swiper',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-quick-card-slider-script',
        ];
    }

    /**
     * Get style dependencies.
     *
     * @return array<string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-swiper-swiper',
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-quick-card-slider-style',
        ];
    }

    /**
     * Get widget categories.
     *
     * @return array<string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Get widget keywords.
     *
     * @return array<string>
     */
    public function get_keywords(): array
    {
        return ['slider', 'card', 'carousel', 'content', 'king-addons'];
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
        $this->register_slider_controls();
        $this->register_navigation_controls();
        $this->register_pagination_controls();
        $this->register_style_card_controls();
        $this->register_style_typography_controls();
        $this->register_style_button_controls();
        $this->register_style_navigation_controls();
        $this->register_style_pagination_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Render widget output on the frontend.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $this->render_output($settings);
    }

    /**
     * Render widget output using provided settings.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return void
     */
    public function render_output(array $settings): void
    {
        $cards = $settings['kng_cards'] ?? [];

        if (empty($cards)) {
            return;
        }

        $wrapper_classes = $this->get_wrapper_classes($settings);
        $wrapper_styles = $this->get_wrapper_style_attributes($settings);

        $data_attributes = $this->get_slider_data_attributes($settings);

        $show_navigation = $settings['kng_show_navigation'] === 'yes';
        $show_pagination = $settings['kng_show_pagination'] === 'yes';

        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"<?php echo $wrapper_styles; ?>>
            <div class="king-addons-card-slider__track swiper" <?php echo $data_attributes; ?>>
                <div class="king-addons-card-slider__wrapper swiper-wrapper">
                    <?php foreach ($cards as $card) : ?>
                        <?php $this->render_slide($card); ?>
                    <?php endforeach; ?>
                </div>
                <?php if ($show_pagination) : ?>
                    <div class="king-addons-card-slider__pagination swiper-pagination" aria-label="<?php echo esc_attr__('Slider pagination', 'king-addons'); ?>"></div>
                <?php endif; ?>
            </div>
            <?php if ($show_navigation) : ?>
                <div class="king-addons-card-slider__navigation" aria-label="<?php echo esc_attr__('Slider navigation', 'king-addons'); ?>">
                    <button type="button" class="king-addons-card-slider__arrow king-addons-card-slider__arrow--prev swiper-button-prev" aria-label="<?php echo esc_attr__('Previous slide', 'king-addons'); ?>">
                        <span class="king-addons-card-slider__arrow-icon" aria-hidden="true"></span>
                    </button>
                    <button type="button" class="king-addons-card-slider__arrow king-addons-card-slider__arrow--next swiper-button-next" aria-label="<?php echo esc_attr__('Next slide', 'king-addons'); ?>">
                        <span class="king-addons-card-slider__arrow-icon" aria-hidden="true"></span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Register content controls for cards.
     *
     * @return void
     */
    public function register_content_controls(): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Cards', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_card_image',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $repeater->add_control(
            'kng_card_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Feature highlight', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_card_description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 3,
                'default' => esc_html__('Share a concise value statement for this card.', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_card_button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Learn more', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_card_button_link',
            [
                'label' => esc_html__('Button Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_card_link_enable',
            [
                'label' => esc_html__('Make Whole Card Clickable', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'king-addons'),
                'label_off' => esc_html__('No', 'king-addons'),
                'return_value' => 'yes',
                'description' => esc_html__('Editor safety: the card link is disabled in the Elementor editor to prevent accidental navigation.', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_card_link',
            [
                'label' => esc_html__('Card Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'king-addons'),
                'condition' => [
                    'kng_card_link_enable' => 'yes',
                ],
            ]
        );

        // Pro-only badges: show a disabled switcher hint in free.
        $repeater->add_control(
            'kng_card_badge_pro_notice',
            [
                'label' => sprintf(__('Card Badge %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'classes' => 'king-addons-pro-control no-distance',
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'kng_card_image',
                'default' => 'medium',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_cards',
            [
                'label' => esc_html__('Cards', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => $this->get_default_cards(),
                'title_field' => '{{{ kng_card_title }}}',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register slider behavior controls.
     *
     * @return void
     */
    public function register_slider_controls(): void
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
     * Register navigation controls (layout and placement).
     *
     * @return void
     */
    public function register_navigation_controls(): void
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

        $this->add_pro_navigation_controls();

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
                    '{{WRAPPER}} .king-addons-card-slider' => '--kng-card-slider-nav-offset: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-card-slider' => '--kng-card-slider-nav-padding: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-card-slider' => '--kng-card-slider-nav-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register pagination controls (layout and placement).
     *
     * @return void
     */
    public function register_pagination_controls(): void
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

        $this->add_pro_pagination_controls();

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
                    '{{WRAPPER}} .king-addons-card-slider' => '--kng-card-slider-pagination-offset: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-card-slider' => '--kng-card-slider-pagination-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register card container style controls.
     *
     * @return void
     */
    public function register_style_card_controls(): void
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
                    '{{WRAPPER}} .king-addons-card-slider__track' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-card-slider__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_card_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-slider__card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_card_border',
                'selector' => '{{WRAPPER}} .king-addons-card-slider__card',
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
                    '{{WRAPPER}} .king-addons-card-slider__card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-card-slider__card',
                'render_type' => 'ui',
                'selector_value' => '{{WRAPPER}} .king-addons-card-slider__card',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow_hover',
                'label' => esc_html__('Hover Shadow', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-card-slider__card:hover, {{WRAPPER}} .king-addons-card-slider__slide-inner:hover .king-addons-card-slider__card',
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
                    '{{WRAPPER}} .king-addons-card-slider__card' => 'box-shadow: none !important;',
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
                    '{{WRAPPER}} .king-addons-card-slider__card:hover, {{WRAPPER}} .king-addons-card-slider__slide-inner:hover .king-addons-card-slider__card' => 'box-shadow: none !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_media_radius',
            [
                'label' => esc_html__('Image Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-slider__media img' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_media_spacing',
            [
                'label' => esc_html__('Image Bottom Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-slider__media' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register typography and text color style controls.
     *
     * @return void
     */
    public function register_style_typography_controls(): void
    {
        $this->start_controls_section(
            'kng_style_typography_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Text', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-card-slider__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-slider__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_description_typography',
                'selector' => '{{WRAPPER}} .king-addons-card-slider__description',
            ]
        );

        $this->add_control(
            'kng_description_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-slider__description' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .king-addons-card-slider__title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
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
                'prefix_class' => 'king-addons-card-slider--text-align-',
            ]
        );

        $this->add_responsive_control(
            'kng_description_spacing',
            [
                'label' => esc_html__('Description Bottom Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-slider__description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register button style controls.
     *
     * @return void
     */
    public function register_style_button_controls(): void
    {
        $this->start_controls_section(
            'kng_style_button_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
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
                'prefix_class' => 'king-addons-card-slider--button-align-',
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
                'prefix_class' => 'king-addons-card-slider--button-text-align-',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-card-slider__button',
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
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-slider__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-slider__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border',
                'selector' => '{{WRAPPER}} .king-addons-card-slider__button',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow',
                'selector' => '{{WRAPPER}} .king-addons-card-slider__button',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow_hover',
                'label' => esc_html__('Hover Shadow', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-card-slider__button:hover',
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
                    '{{WRAPPER}} .king-addons-card-slider__button' => 'box-shadow: none !important;',
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
                    '{{WRAPPER}} .king-addons-card-slider__button:hover' => 'box-shadow: none !important;',
                ],
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
                    '{{WRAPPER}} .king-addons-card-slider__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_background_hover',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-slider__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_border_color_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-slider__button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-card-slider__button:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-slider__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-card-slider__button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register navigation style controls.
     *
     * @return void
     */
    public function register_style_navigation_controls(): void
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
                    '{{WRAPPER}} .king-addons-card-slider' => '--kng-card-slider-nav-size: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-card-slider' => '--kng-card-slider-nav-icon-size: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-card-slider__arrow' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_nav_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-slider__arrow' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_nav_border',
                'selector' => '{{WRAPPER}} .king-addons-card-slider__arrow',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_nav_shadow',
                'selector' => '{{WRAPPER}} .king-addons-card-slider__arrow',
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
                    '{{WRAPPER}} .king-addons-card-slider__arrow:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_nav_background_hover',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-slider__arrow:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_nav_border_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-card-slider__arrow:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_nav_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-card-slider__arrow:hover',
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
                    '{{WRAPPER}} .king-addons-card-slider__arrow' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register pagination style controls.
     *
     * @return void
     */
    public function register_style_pagination_controls(): void
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
                    '{{WRAPPER}} .king-addons-card-slider' => '--kng-card-slider-pagination-size: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-card-slider__pagination .swiper-pagination-bullet' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .king-addons-card-slider__pagination .swiper-pagination-bullet-active' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .king-addons-card-slider' => '--kng-card-slider-pagination-active-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /**
     * Render a single slide.
     *
     * @param array<string, mixed> $card Card data.
     *
     * @return void
     */
    public function render_slide(array $card): void
    {
        $image_html = $this->get_card_image($card);

        $title = $card['kng_card_title'] ?? '';
        $description = $card['kng_card_description'] ?? '';
        $button_text = $card['kng_card_button_text'] ?? '';
        $button_link = $card['kng_card_button_link'] ?? [];
        $card_link_enabled = ($card['kng_card_link_enable'] ?? '') === 'yes';
        $card_link = $card['kng_card_link'] ?? [];

        $slide_classes = $this->get_slide_classes($card);

        $card_link_attributes = $this->get_card_link_attributes($card_link_enabled, $card_link);

        ?>
        <div class="king-addons-card-slider__slide swiper-slide <?php echo esc_attr($slide_classes); ?>" <?php echo $card_link_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <article class="king-addons-card-slider__card">
                <?php if (!empty($image_html)) : ?>
                    <div class="king-addons-card-slider__media">
                        <?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php $this->render_card_badge($card); ?>
                    </div>
                <?php endif; ?>

                <div class="king-addons-card-slider__body">
                    <?php if (!empty($title)) : ?>
                        <h3 class="king-addons-card-slider__title"><?php echo esc_html($title); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($description)) : ?>
                        <div class="king-addons-card-slider__description"><?php echo esc_html($description); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($button_text)) : ?>
                        <?php $this->render_button($button_text, $button_link); ?>
                    <?php endif; ?>
                </div>
            </article>
        </div>
        <?php
    }

    /**
     * Get default cards for the repeater.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_default_cards(): array
    {
        return [
            [
                'kng_card_title' => esc_html__('Feature-rich card', 'king-addons'),
                'kng_card_description' => esc_html__('Highlight what makes your offer compelling with concise supporting text.', 'king-addons'),
                'kng_card_button_text' => esc_html__('Explore', 'king-addons'),
                'kng_card_image' => ['url' => Utils::get_placeholder_image_src()],
            ],
            [
                'kng_card_title' => esc_html__('Built-in spacing', 'king-addons'),
                'kng_card_description' => esc_html__('Defaults ensure navigation and pagination never cover your content.', 'king-addons'),
                'kng_card_button_text' => esc_html__('See details', 'king-addons'),
                'kng_card_image' => ['url' => Utils::get_placeholder_image_src()],
            ],
            [
                'kng_card_title' => esc_html__('Modern styling', 'king-addons'),
                'kng_card_description' => esc_html__('Cards ship with clean typography, soft shadows, and rounded corners.', 'king-addons'),
                'kng_card_button_text' => esc_html__('Get started', 'king-addons'),
                'kng_card_image' => ['url' => Utils::get_placeholder_image_src()],
            ],
            [
                'kng_card_title' => esc_html__('Responsive design', 'king-addons'),
                'kng_card_description' => esc_html__('Looks great on all devices with optimized touch interactions.', 'king-addons'),
                'kng_card_button_text' => esc_html__('Learn more', 'king-addons'),
                'kng_card_image' => ['url' => Utils::get_placeholder_image_src()],
            ],
        ];
    }

    /**
     * Get slider data attributes string for frontend JS.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return string
     */
    public function get_slider_data_attributes(array $settings): string
    {
        $slides = !empty($settings['kng_slides_per_view']) ? (int) $settings['kng_slides_per_view'] : 1;
        $slides_tablet = !empty($settings['kng_slides_per_view_tablet']) ? (int) $settings['kng_slides_per_view_tablet'] : $slides;
        $slides_mobile = !empty($settings['kng_slides_per_view_mobile']) ? (int) $settings['kng_slides_per_view_mobile'] : $slides_tablet;
        $space_between = isset($settings['kng_space_between']['size']) ? (int) $settings['kng_space_between']['size'] : 20;
        $autoplay = $settings['kng_autoplay'] === 'yes' ? 'yes' : 'no';
        $autoplay_delay = !empty($settings['kng_autoplay_delay']) ? (int) $settings['kng_autoplay_delay'] : 3200;
        $loop = $settings['kng_loop'] === 'yes' ? 'yes' : 'no';
        $speed = !empty($settings['kng_speed']) ? (int) $settings['kng_speed'] : 600;

        $show_navigation = $settings['kng_show_navigation'] === 'yes' ? 'yes' : 'no';
        $show_pagination = $settings['kng_show_pagination'] === 'yes' ? 'yes' : 'no';

        $attributes = [
            'data-slides-per-view' => $slides,
            'data-slides-per-view-tablet' => $slides_tablet,
            'data-slides-per-view-mobile' => $slides_mobile,
            'data-space-between' => $space_between,
            'data-autoplay' => $autoplay,
            'data-autoplay-delay' => $autoplay_delay,
            'data-loop' => $loop,
            'data-speed' => $speed,
            'data-navigation' => $show_navigation,
            'data-pagination' => $show_pagination,
        ];

        return $this->format_data_attributes($attributes);
    }

    /**
     * Build wrapper classes based on settings.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return array<int, string>
     */
    public function get_wrapper_classes(array $settings): array
    {
        $classes = ['king-addons-card-slider'];

        $nav_position = $settings['kng_navigation_position'] ?? 'outside';
        $classes[] = 'king-addons-card-slider--nav-' . sanitize_html_class((string) $nav_position);

        $nav_alignment = $settings['kng_navigation_alignment'] ?? 'flex-end';
        $classes[] = 'king-addons-card-slider--nav-align-' . sanitize_html_class((string) $nav_alignment);

        if (!empty($settings['kng_navigation_hide_tablet'])) {
            $classes[] = 'king-addons-card-slider--nav-hide-tablet';
        }

        if (!empty($settings['kng_navigation_hide_mobile'])) {
            $classes[] = 'king-addons-card-slider--nav-hide-mobile';
        }

        $pagination_position = $settings['kng_pagination_position'] ?? 'outside';
        $classes[] = 'king-addons-card-slider--pagination-' . sanitize_html_class((string) $pagination_position);

        $pagination_alignment = $settings['kng_pagination_alignment'] ?? 'center';
        $classes[] = 'king-addons-card-slider--pagination-align-' . sanitize_html_class((string) $pagination_alignment);

        $classes = array_merge($classes, $this->get_additional_wrapper_classes($settings));

        return array_filter($classes);
    }

    /**
     * Get wrapper style attributes for CSS variables.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return string
     */
    public function get_wrapper_style_attributes(array $settings): string
    {
        // CSS variables for pagination offset, pagination gap, and navigation gap
        // are now handled via Elementor selectors in the control definitions.
        // This ensures proper responsive behavior and avoids conflicts with
        // Elementor's built-in style handling.
        return '';
    }

    /**
     * Render button element.
     *
     * @param string                 $text  Button text.
     * @param array<string, mixed>   $link  Button link array.
     *
     * @return void
     */
    public function render_button(string $text, array $link): void
    {
        $url = $link['url'] ?? '';
        $rel = [];

        if (!empty($link['nofollow'])) {
            $rel[] = 'nofollow';
        }

        if (!empty($link['is_external'])) {
            $rel[] = 'noopener';
            $rel[] = 'noreferrer';
        }

        $attributes = '';

        if (!empty($url)) {
            $attributes .= ' href="' . esc_url($url) . '"';
        }

        if (!empty($link['is_external'])) {
            $attributes .= ' target="_blank"';
        }

        if (!empty($rel)) {
            $attributes .= ' rel="' . esc_attr(implode(' ', array_unique($rel))) . '"';
        }

        ?>
        <a class="king-addons-card-slider__button"<?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <?php echo esc_html($text); ?>
        </a>
        <?php
    }

    /**
     * Get card image HTML.
     *
     * @param array<string, mixed> $card Card data.
     *
     * @return string
     */
    public function get_card_image(array $card): string
    {
        if (empty($card['kng_card_image']['id']) && empty($card['kng_card_image']['url'])) {
            return '';
        }

        return Group_Control_Image_Size::get_attachment_image_html($card, 'kng_card_image');
    }

    /**
     * Get slide classes.
     *
     * @param array<string, mixed> $card Card data.
     *
     * @return string
     */
    public function get_slide_classes(array $card): string
    {
        $classes = ['king-addons-card-slider__slide-inner'];

        $classes = array_merge($classes, $this->get_additional_slide_classes($card));

        return implode(' ', array_filter($classes));
    }

    /**
     * Build data attributes for card-level navigation.
     *
     * @param bool                   $enabled Whether link is enabled.
     * @param array<string, mixed>   $link    Link data.
     *
     * @return string
     */
    public function get_card_link_attributes(bool $enabled, array $link): string
    {
        if (!$enabled || empty($link['url'])) {
            return '';
        }

        $attrs = [
            'data-card-link' => esc_url_raw($link['url']),
        ];

        if (!empty($link['is_external'])) {
            $attrs['data-card-link-target'] = '_blank';
        }

        if (!empty($link['nofollow'])) {
            $attrs['data-card-link-rel'] = 'nofollow';
        }

        $attributes = [];
        foreach ($attrs as $key => $value) {
            $attributes[] = esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        return implode(' ', $attributes);
    }

    /**
     * Format data attributes array into HTML attributes string.
     *
     * @param array<string, string|int> $attributes Attributes to format.
     *
     * @return string
     */
    public function format_data_attributes(array $attributes): string
    {
        $output = [];

        foreach ($attributes as $key => $value) {
            $output[] = esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        return implode(' ', $output);
    }

    /**
     * Placeholder for additional wrapper classes (Pro overrides).
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return array<int, string>
     */
    public function get_additional_wrapper_classes(array $settings): array
    {
        unset($settings);
        return [];
    }

    /**
     * Placeholder for additional slide classes (Pro overrides).
     *
     * @param array<string, mixed> $card Card data.
     *
     * @return array<int, string>
     */
    public function get_additional_slide_classes(array $card): array
    {
        unset($card);
        return [];
    }

    /**
     * Placeholder for navigation pro controls.
     *
     * @return void
     */
    public function add_pro_navigation_controls(): void
    {
        // Premium navigation style controls are added in Pro.
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
     * Placeholder for pagination pro controls.
     *
     * @return void
     */
    public function add_pro_pagination_controls(): void
    {
        // Premium pagination style controls are added in Pro.
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
     * Placeholder for rendering badges (Pro overrides).
     *
     * @param array<string, mixed> $card Card data.
     *
     * @return void
     */
    public function render_card_badge(array $card): void
    {
        unset($card);
        // Premium badge rendering is handled in Pro.
    }

    /**
     * Register upgrade notice section for premium features.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'quick-card-slider', [
                'Per-card badges and ribbons',
                'Advanced hover and entrance animations',
                'Navigation and pagination style presets',
                'Extended placement and offset controls',
                'Badge typography and color styling',
            ]);
        }
    }
}








