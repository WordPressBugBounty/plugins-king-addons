<?php
/**
 * Reveal Swipe Cards Widget.
 *
 * Creative card widget with wipe-reveal effect triggered by hover or scroll.
 * Pro version adds custom mask shapes, blur edge, touch swipe, and sequence mode.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Reveal_Swipe_Cards
 *
 * Elementor widget for animated reveal cards with wipe effect.
 */
class Reveal_Swipe_Cards extends Widget_Base
{
    /**
     * Get widget name.
     *
     * @return string Widget name.
     */
    public function get_name(): string
    {
        return 'king-addons-reveal-swipe-cards';
    }

    /**
     * Get widget title.
     *
     * @return string Widget title.
     */
    public function get_title(): string
    {
        return esc_html__('Reveal Swipe Cards', 'king-addons');
    }

    /**
     * Get widget icon.
     *
     * @return string Widget icon.
     */
    public function get_icon(): string
    {
        return 'eicon-slides';
    }

    /**
     * Get widget categories.
     *
     * @return array Widget categories.
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Get widget keywords.
     *
     * @return array Widget keywords.
     */
    public function get_keywords(): array
    {
        return ['reveal', 'swipe', 'cards', 'wipe', 'hover', 'animation', 'creative', 'king-addons'];
    }

    /**
     * Get style dependencies.
     *
     * @return array Style handles.
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-reveal-swipe-cards-style'];
    }

    /**
     * Get script dependencies.
     *
     * @return array Script handles.
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-reveal-swipe-cards-script'];
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
    protected function register_controls(): void
    {
        $this->register_cards_controls();
        $this->register_layout_controls();
        $this->register_reveal_controls();
        $this->register_style_card_controls();
        $this->register_style_content_controls();
        $this->register_style_button_controls();
        $this->register_style_badge_controls();
        $this->register_style_overlay_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Register cards repeater controls.
     *
     * @return void
     */
    protected function register_cards_controls(): void
    {
        $this->start_controls_section(
            'kng_rsc_cards_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Cards', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'media_type',
            [
                'label' => esc_html__('Media Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'image',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'image' => esc_html__('Image', 'king-addons'),
                    'icon' => esc_html__('Icon', 'king-addons'),
                ],
            ]
        );

        $repeater->add_control(
            'image',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
                'condition' => [
                    'media_type' => 'image',
                ],
            ]
        );

        $repeater->add_control(
            'icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-star',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'media_type' => 'icon',
                ],
            ]
        );

        $repeater->add_control(
            'title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Card Title', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('Card description goes here. Add your content.', 'king-addons'),
                'rows' => 4,
            ]
        );

        $repeater->add_control(
            'button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Learn More', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'button_link',
            [
                'label' => esc_html__('Button Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'badge_enabled',
            [
                'label' => esc_html__('Show Badge', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'separator' => 'before',
            ]
        );

        $repeater->add_control(
            'badge_text',
            [
                'label' => esc_html__('Badge Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('New', 'king-addons'),
                'condition' => [
                    'badge_enabled' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_cards',
            [
                'label' => esc_html__('Cards', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => $this->get_default_cards(),
                'title_field' => '{{{ title }}}',
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
            'kng_rsc_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc' => '--kng-rsc-columns: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 100],
                ],
                'default' => [
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc' => '--kng-rsc-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_min_height',
            [
                'label' => esc_html__('Card Min Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => ['min' => 150, 'max' => 800],
                    'vh' => ['min' => 20, 'max' => 100],
                ],
                'default' => [
                    'size' => 320,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc' => '--kng-rsc-min-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_content_align',
            [
                'label' => esc_html__('Content Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'default' => 'center',
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Top', 'king-addons'),
                        'icon' => 'eicon-v-align-top',
                    ],
                    'center' => [
                        'title' => esc_html__('Middle', 'king-addons'),
                        'icon' => 'eicon-v-align-middle',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Bottom', 'king-addons'),
                        'icon' => 'eicon-v-align-bottom',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc' => '--kng-rsc-content-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_text_align',
            [
                'label' => esc_html__('Text Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'default' => 'center',
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
                    '{{WRAPPER}} .kng-rsc' => '--kng-rsc-text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register reveal settings controls.
     *
     * @return void
     */
    protected function register_reveal_controls(): void
    {
        $this->start_controls_section(
            'kng_rsc_reveal_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Reveal Settings', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_rsc_trigger',
            [
                'label' => esc_html__('Trigger', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'hover',
                'options' => [
                    'hover' => esc_html__('Hover', 'king-addons'),
                    'scroll' => esc_html__('Scroll', 'king-addons'),
                    'both' => esc_html__('Hover + Scroll', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_direction',
            [
                'label' => esc_html__('Direction', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Left to Right', 'king-addons'),
                    'right' => esc_html__('Right to Left', 'king-addons'),
                    'top' => esc_html__('Top to Bottom', 'king-addons'),
                    'bottom' => esc_html__('Bottom to Top', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_duration',
            [
                'label' => esc_html__('Duration (ms)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 150, 'max' => 2000, 'step' => 50],
                ],
                'default' => [
                    'size' => 500,
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc' => '--kng-rsc-duration: {{SIZE}}ms;',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_easing',
            [
                'label' => esc_html__('Easing', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'ease-out',
                'options' => [
                    'linear' => esc_html__('Linear', 'king-addons'),
                    'ease' => esc_html__('Ease', 'king-addons'),
                    'ease-in' => esc_html__('Ease In', 'king-addons'),
                    'ease-out' => esc_html__('Ease Out', 'king-addons'),
                    'ease-in-out' => esc_html__('Ease In Out', 'king-addons'),
                    'cubic-bezier(0.4, 0, 0.2, 1)' => esc_html__('Smooth', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc' => '--kng-rsc-easing: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_reset_on_leave',
            [
                'label' => esc_html__('Reset on Hover Leave', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'condition' => [
                    'kng_rsc_trigger' => ['hover', 'both'],
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_scroll_heading',
            [
                'label' => esc_html__('Scroll Options', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'kng_rsc_trigger' => ['scroll', 'both'],
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_scroll_threshold',
            [
                'label' => esc_html__('Threshold (%)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 100],
                ],
                'default' => [
                    'size' => 30,
                ],
                'condition' => [
                    'kng_rsc_trigger' => ['scroll', 'both'],
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_scroll_once',
            [
                'label' => esc_html__('Reveal Once', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'condition' => [
                    'kng_rsc_trigger' => ['scroll', 'both'],
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_scroll_reset',
            [
                'label' => esc_html__('Reset When Out of View', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'condition' => [
                    'kng_rsc_trigger' => ['scroll', 'both'],
                    'kng_rsc_scroll_once!' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register card style controls.
     *
     * @return void
     */
    protected function register_style_card_controls(): void
    {
        $this->start_controls_section(
            'kng_rsc_style_card_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_rsc_card_bg',
                'selector' => '{{WRAPPER}} .kng-rsc-card__content',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_rsc_card_border',
                'selector' => '{{WRAPPER}} .kng-rsc-card',
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_card_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_rsc_card_shadow',
                'selector' => '{{WRAPPER}} .kng-rsc-card',
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_card_hover_heading',
            [
                'label' => esc_html__('Hover Effects', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_rsc_card_hover_transform',
            [
                'label' => esc_html__('Hover Transform', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'lift' => esc_html__('Lift Up', 'king-addons'),
                    'scale' => esc_html__('Scale Up', 'king-addons'),
                    'tilt' => esc_html__('Tilt', 'king-addons'),
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_rsc_card_hover_shadow',
                'label' => esc_html__('Hover Shadow', 'king-addons'),
                'selector' => '{{WRAPPER}} .kng-rsc-card:hover',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register content style controls (title, description, icon/image).
     *
     * @return void
     */
    protected function register_style_content_controls(): void
    {
        $this->start_controls_section(
            'kng_rsc_style_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // Media (icon/image)
        $this->add_control(
            'kng_rsc_media_heading',
            [
                'label' => esc_html__('Media', 'king-addons'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_icon_size',
            [
                'label' => esc_html__('Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 16, 'max' => 120],
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__icon' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .kng-rsc-card__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_icon_color',
            [
                'label' => esc_html__('Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .kng-rsc-card__icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_icon_bg_color',
            [
                'label' => esc_html__('Icon Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__icon' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_icon_padding',
            [
                'label' => esc_html__('Icon Padding', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 50],
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__icon' => 'padding: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_icon_radius',
            [
                'label' => esc_html__('Icon Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_image_width',
            [
                'label' => esc_html__('Image Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 40, 'max' => 400],
                    '%' => ['min' => 10, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__image' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_image_radius',
            [
                'label' => esc_html__('Image Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_media_spacing',
            [
                'label' => esc_html__('Media Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__media' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Title
        $this->add_control(
            'kng_rsc_title_heading',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_rsc_title_typography',
                'selector' => '{{WRAPPER}} .kng-rsc-card__title',
            ]
        );

        $this->add_control(
            'kng_rsc_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_title_spacing',
            [
                'label' => esc_html__('Title Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Description
        $this->add_control(
            'kng_rsc_desc_heading',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_rsc_desc_typography',
                'selector' => '{{WRAPPER}} .kng-rsc-card__description',
            ]
        );

        $this->add_control(
            'kng_rsc_desc_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_desc_spacing',
            [
                'label' => esc_html__('Description Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
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
    protected function register_style_button_controls(): void
    {
        $this->start_controls_section(
            'kng_rsc_style_button_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_rsc_btn_typography',
                'selector' => '{{WRAPPER}} .kng-rsc-card__button',
            ]
        );

        $this->start_controls_tabs('kng_rsc_btn_tabs');

        $this->start_controls_tab(
            'kng_rsc_btn_tab_normal',
            [
                'label' => esc_html__('Normal', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_rsc_btn_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_btn_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_rsc_btn_border',
                'selector' => '{{WRAPPER}} .kng-rsc-card__button',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_rsc_btn_tab_hover',
            [
                'label' => esc_html__('Hover', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_rsc_btn_hover_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_btn_hover_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_btn_hover_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'kng_rsc_btn_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'separator' => 'before',
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_btn_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register badge style controls.
     *
     * @return void
     */
    protected function register_style_badge_controls(): void
    {
        $this->start_controls_section(
            'kng_rsc_style_badge_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Badge', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_rsc_badge_typography',
                'selector' => '{{WRAPPER}} .kng-rsc-card__badge',
            ]
        );

        $this->add_control(
            'kng_rsc_badge_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__badge' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_badge_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__badge' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_badge_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_badge_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_badge_position',
            [
                'label' => esc_html__('Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'top-right',
                'options' => [
                    'top-left' => esc_html__('Top Left', 'king-addons'),
                    'top-right' => esc_html__('Top Right', 'king-addons'),
                    'bottom-left' => esc_html__('Bottom Left', 'king-addons'),
                    'bottom-right' => esc_html__('Bottom Right', 'king-addons'),
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register overlay style controls.
     *
     * @return void
     */
    protected function register_style_overlay_controls(): void
    {
        $this->start_controls_section(
            'kng_rsc_style_overlay_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Reveal Overlay', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_rsc_overlay_color',
            [
                'label' => esc_html__('Overlay Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2563eb',
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc' => '--kng-rsc-overlay-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_overlay_opacity',
            [
                'label' => esc_html__('Overlay Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 1, 'step' => 0.05],
                ],
                'default' => [
                    'size' => 1,
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc' => '--kng-rsc-overlay-opacity: {{SIZE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_overlay_content_heading',
            [
                'label' => esc_html__('Overlay Content', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_rsc_overlay_content_type',
            [
                'label' => esc_html__('Content Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'icon' => esc_html__('Icon', 'king-addons'),
                    'text' => esc_html__('Text', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_overlay_icon',
            [
                'label' => esc_html__('Overlay Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-eye',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'kng_rsc_overlay_content_type' => 'icon',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_overlay_text',
            [
                'label' => esc_html__('Overlay Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Hover to reveal', 'king-addons'),
                'condition' => [
                    'kng_rsc_overlay_content_type' => 'text',
                ],
            ]
        );

        $this->add_control(
            'kng_rsc_overlay_content_color',
            [
                'label' => esc_html__('Content Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__overlay-content' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .kng-rsc-card__overlay-content svg' => 'fill: {{VALUE}};',
                ],
                'condition' => [
                    'kng_rsc_overlay_content_type!' => 'none',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_rsc_overlay_content_size',
            [
                'label' => esc_html__('Content Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 12, 'max' => 100],
                ],
                'default' => [
                    'size' => 32,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-rsc-card__overlay-content' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .kng-rsc-card__overlay-content svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_rsc_overlay_content_type!' => 'none',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register Pro features notice (Free version).
     *
     * @return void
     */
    protected function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'reveal-swipe-cards', [
                'Custom mask shapes (circle, diagonal, wave)',
                'Blur edge effect for soft reveal',
                'Touch swipe on mobile devices',
                'Sequence mode (stagger reveal)',
                'Active one at a time mode',
            ]);
        }
    }

    /**
     * Get default cards data.
     *
     * @return array Default cards.
     */
    protected function get_default_cards(): array
    {
        return [
            [
                'media_type' => 'icon',
                'icon' => ['value' => 'fas fa-rocket', 'library' => 'fa-solid'],
                'title' => esc_html__('Fast Performance', 'king-addons'),
                'description' => esc_html__('Optimized for speed and efficiency to deliver the best experience.', 'king-addons'),
                'button_text' => esc_html__('Learn More', 'king-addons'),
            ],
            [
                'media_type' => 'icon',
                'icon' => ['value' => 'fas fa-shield-alt', 'library' => 'fa-solid'],
                'title' => esc_html__('Secure & Reliable', 'king-addons'),
                'description' => esc_html__('Built with security in mind to protect your data and privacy.', 'king-addons'),
                'button_text' => esc_html__('Discover', 'king-addons'),
            ],
            [
                'media_type' => 'icon',
                'icon' => ['value' => 'fas fa-paint-brush', 'library' => 'fa-solid'],
                'title' => esc_html__('Beautiful Design', 'king-addons'),
                'description' => esc_html__('Modern and clean aesthetics that captivate your audience.', 'king-addons'),
                'button_text' => esc_html__('Explore', 'king-addons'),
            ],
        ];
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $cards = $settings['kng_rsc_cards'] ?? [];

        if (empty($cards)) {
            if ($this->is_editor()) {
                echo '<div class="kng-rsc-empty">' . esc_html__('Add cards to get started.', 'king-addons') . '</div>';
            }
            return;
        }

        $wrapper_classes = $this->get_wrapper_classes($settings);
        $data_settings = $this->get_data_settings($settings);

        $this->add_render_attribute('wrapper', [
            'class' => implode(' ', $wrapper_classes),
            'data-settings' => wp_json_encode($data_settings),
        ]);

        echo '<div ' . $this->get_render_attribute_string('wrapper') . '>';
        echo '<div class="kng-rsc__grid">';

        foreach ($cards as $index => $card) {
            $this->render_card($settings, $card, $index);
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Render single card.
     *
     * @param array $settings Widget settings.
     * @param array $card Card data.
     * @param int $index Card index.
     * @return void
     */
    protected function render_card(array $settings, array $card, int $index): void
    {
        $title = esc_html(trim((string) ($card['title'] ?? '')));
        $description = wp_kses_post(trim((string) ($card['description'] ?? '')));
        $button_text = esc_html(trim((string) ($card['button_text'] ?? '')));
        $button_link = $card['button_link'] ?? [];
        $media_type = $card['media_type'] ?? 'none';
        $badge_enabled = 'yes' === ($card['badge_enabled'] ?? '');
        $badge_text = esc_html(trim((string) ($card['badge_text'] ?? '')));
        $badge_position = $settings['kng_rsc_badge_position'] ?? 'top-right';

        $card_classes = ['kng-rsc-card'];

        echo '<div class="' . esc_attr(implode(' ', $card_classes)) . '" data-card-index="' . esc_attr((string) $index) . '">';

        // Reveal overlay (mask layer)
        echo '<div class="kng-rsc-card__overlay" aria-hidden="true">';
        $this->render_overlay_content($settings);
        echo '</div>';

        // Content layer
        echo '<div class="kng-rsc-card__content">';
        echo '<div class="kng-rsc-card__inner">';

        // Badge
        if ($badge_enabled && '' !== $badge_text) {
            echo '<span class="kng-rsc-card__badge kng-rsc-card__badge--' . esc_attr($badge_position) . '">' . $badge_text . '</span>';
        }

        // Media
        if ('none' !== $media_type) {
            echo '<div class="kng-rsc-card__media">';

            if ('image' === $media_type && !empty($card['image']['url'])) {
                echo '<img class="kng-rsc-card__image" src="' . esc_url($card['image']['url']) . '" alt="' . esc_attr($title) . '" />';
            } elseif ('icon' === $media_type && !empty($card['icon']['value'])) {
                echo '<span class="kng-rsc-card__icon">';
                Icons_Manager::render_icon($card['icon'], ['aria-hidden' => 'true']);
                echo '</span>';
            }

            echo '</div>';
        }

        // Title
        if ('' !== $title) {
            echo '<h3 class="kng-rsc-card__title">' . $title . '</h3>';
        }

        // Description
        if ('' !== $description) {
            echo '<p class="kng-rsc-card__description">' . $description . '</p>';
        }

        // Button
        if ('' !== $button_text) {
            $link_attrs = $this->get_link_attributes($button_link, 'kng-rsc-card__button');
            if ('' !== $link_attrs) {
                echo '<a ' . $link_attrs . '>' . $button_text . '</a>';
            } else {
                echo '<span class="kng-rsc-card__button">' . $button_text . '</span>';
            }
        }

        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Get wrapper CSS classes.
     *
     * @param array $settings Widget settings.
     * @return array CSS classes.
     */
    protected function get_wrapper_classes(array $settings): array
    {
        $direction = $this->sanitize_direction($settings['kng_rsc_direction'] ?? 'left');
        $trigger = $this->sanitize_trigger($settings['kng_rsc_trigger'] ?? 'hover');
        $hover_transform = $settings['kng_rsc_card_hover_transform'] ?? 'none';

        $classes = [
            'kng-rsc',
            'kng-rsc--direction-' . $direction,
            'kng-rsc--trigger-' . $trigger,
        ];

        if ('none' !== $hover_transform) {
            $classes[] = 'kng-rsc--hover-' . $hover_transform;
        }

        if ($this->is_pro_enabled()) {
            $classes[] = 'kng-rsc--pro';
        }

        return $classes;
    }

    /**
     * Get data settings for JS.
     *
     * @param array $settings Widget settings.
     * @return array Data settings.
     */
    protected function get_data_settings(array $settings): array
    {
        $data = [
            'trigger' => $this->sanitize_trigger($settings['kng_rsc_trigger'] ?? 'hover'),
            'direction' => $this->sanitize_direction($settings['kng_rsc_direction'] ?? 'left'),
            'duration' => absint($settings['kng_rsc_duration']['size'] ?? 500),
            'resetOnLeave' => 'yes' === ($settings['kng_rsc_reset_on_leave'] ?? 'yes'),
            'scrollThreshold' => absint($settings['kng_rsc_scroll_threshold']['size'] ?? 30) / 100,
            'scrollOnce' => 'yes' === ($settings['kng_rsc_scroll_once'] ?? 'yes'),
            'scrollReset' => 'yes' === ($settings['kng_rsc_scroll_reset'] ?? ''),
        ];

        return $data;
    }

    /**
     * Get link attributes string.
     *
     * @param array $link Link data.
     * @param string $class CSS class.
     * @return string Attributes string.
     */
    protected function get_link_attributes(array $link, string $class = ''): string
    {
        if (empty($link['url'])) {
            return '';
        }

        $attributes = [
            'href' => esc_url($link['url']),
        ];

        if ('' !== $class) {
            $attributes['class'] = $class;
        }

        if (!empty($link['is_external'])) {
            $attributes['target'] = '_blank';
            $attributes['rel'] = 'noopener noreferrer';
        }

        if (!empty($link['nofollow'])) {
            $attributes['rel'] = isset($attributes['rel']) ? $attributes['rel'] . ' nofollow' : 'nofollow';
        }

        $output = [];
        foreach ($attributes as $key => $value) {
            $output[] = esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        return implode(' ', $output);
    }

    /**
     * Sanitize direction value.
     *
     * @param string $direction Direction value.
     * @return string Sanitized direction.
     */
    protected function sanitize_direction(string $direction): string
    {
        $allowed = ['left', 'right', 'top', 'bottom'];
        return in_array($direction, $allowed, true) ? $direction : 'left';
    }

    /**
     * Sanitize trigger value.
     *
     * @param string $trigger Trigger value.
     * @return string Sanitized trigger.
     */
    protected function sanitize_trigger(string $trigger): string
    {
        $allowed = ['hover', 'scroll', 'both'];
        return in_array($trigger, $allowed, true) ? $trigger : 'hover';
    }

    /**
     * Check if Pro features are enabled.
     *
     * @return bool True if Pro enabled.
     */
    protected function is_pro_enabled(): bool
    {
        return function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();
    }

    /**
     * Check if in editor mode.
     *
     * @return bool True if editor.
     */
    protected function is_editor(): bool
    {
        return class_exists(Plugin::class) && Plugin::$instance->editor->is_edit_mode();
    }

    /**
     * Render overlay content (icon or text).
     *
     * @param array $settings Widget settings.
     * @return void
     */
    protected function render_overlay_content(array $settings): void
    {
        $content_type = $settings['kng_rsc_overlay_content_type'] ?? 'none';

        if ('none' === $content_type) {
            return;
        }

        echo '<span class="kng-rsc-card__overlay-content">';

        if ('icon' === $content_type && !empty($settings['kng_rsc_overlay_icon']['value'])) {
            Icons_Manager::render_icon($settings['kng_rsc_overlay_icon'], ['aria-hidden' => 'true']);
        } elseif ('text' === $content_type && !empty($settings['kng_rsc_overlay_text'])) {
            echo esc_html($settings['kng_rsc_overlay_text']);
        }

        echo '</span>';
    }
}
