<?php
/**
 * Interactive Image Sheen Widget (Free).
 *
 * Creates a premium "silk" sheen effect overlay on images/cards
 * that activates on hover for a high-end product presentation.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Plugin;
use Elementor\Utils;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Interactive_Image_Sheen
 */
class Interactive_Image_Sheen extends Widget_Base
{
    /**
     * Get widget name.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-interactive-image-sheen';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Interactive Image Sheen', 'king-addons');
    }

    /**
     * Get widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-interactive-image-sheen';
    }

    /**
     * Get widget categories.
     *
     * @return array
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Get widget keywords.
     *
     * @return array
     */
    public function get_keywords(): array
    {
        return [
            'sheen',
            'shine',
            'gloss',
            'hover',
            'image',
            'card',
            'premium',
            'silk',
            'reflection',
            'highlight',
            'king addons',
            'kingaddons',
            'king-addons',
        ];
    }

    /**
     * Get style dependencies.
     *
     * @return array
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-interactive-image-sheen-style'];
    }

    /**
     * Get script dependencies.
     *
     * @return array
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-interactive-image-sheen-script'];
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
        // Content Tab
        $this->register_content_source_controls();
        $this->register_content_card_controls();
        $this->register_content_sheen_controls();

        // Pro notice
        $this->register_pro_notice_controls();

        // Style Tab
        $this->register_style_container_controls();
        $this->register_style_sheen_controls();
        $this->register_style_card_controls();
        $this->register_style_hover_controls();
    }

    /**
     * Register source content controls.
     *
     * @return void
     */
    protected function register_content_source_controls(): void
    {
        $this->start_controls_section(
            'kng_iis_source_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Source', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_iis_mode',
            [
                'label' => esc_html__('Mode', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'image',
                'options' => [
                    'image' => esc_html__('Image Only', 'king-addons'),
                    'card' => esc_html__('Card (Image + Content)', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_iis_image',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
                'dynamic' => ['active' => true],
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'kng_iis_image_size',
                'default' => 'large',
            ]
        );

        $this->add_control(
            'kng_iis_image_fit',
            [
                'label' => esc_html__('Image Fit', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'cover',
                'options' => [
                    'cover' => esc_html__('Cover', 'king-addons'),
                    'contain' => esc_html__('Contain', 'king-addons'),
                    'fill' => esc_html__('Fill', 'king-addons'),
                    'none' => esc_html__('None (Original)', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__image' => 'object-fit: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_link',
            [
                'label' => esc_html__('Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'king-addons'),
                'dynamic' => ['active' => true],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register card content controls.
     *
     * @return void
     */
    protected function register_content_card_controls(): void
    {
        $this->start_controls_section(
            'kng_iis_card_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'kng_iis_mode' => 'card',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_card_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Card Title', 'king-addons'),
                'label_block' => true,
                'dynamic' => ['active' => true],
            ]
        );

        $this->add_control(
            'kng_iis_card_title_tag',
            [
                'label' => esc_html__('Title HTML Tag', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'h3',
                'options' => [
                    'h1' => 'H1',
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                    'h6' => 'H6',
                    'p' => 'p',
                    'div' => 'div',
                    'span' => 'span',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_card_description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('A short description for the card.', 'king-addons'),
                'rows' => 3,
                'dynamic' => ['active' => true],
            ]
        );

        $this->add_control(
            'kng_iis_card_button_heading',
            [
                'label' => esc_html__('Button', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_iis_card_button_show',
            [
                'label' => esc_html__('Show Button', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_iis_card_button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Learn More', 'king-addons'),
                'condition' => [
                    'kng_iis_card_button_show' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_card_button_link',
            [
                'label' => esc_html__('Button Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'king-addons'),
                'dynamic' => ['active' => true],
                'condition' => [
                    'kng_iis_card_button_show' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_card_layout',
            [
                'label' => esc_html__('Content Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'below',
                'separator' => 'before',
                'options' => [
                    'below' => esc_html__('Below Image', 'king-addons'),
                    'overlay' => esc_html__('Overlay on Image', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_iis_card_overlay_position',
            [
                'label' => esc_html__('Overlay Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'default' => 'bottom',
                'options' => [
                    'top' => [
                        'title' => esc_html__('Top', 'king-addons'),
                        'icon' => 'eicon-v-align-top',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-v-align-middle',
                    ],
                    'bottom' => [
                        'title' => esc_html__('Bottom', 'king-addons'),
                        'icon' => 'eicon-v-align-bottom',
                    ],
                ],
                'condition' => [
                    'kng_iis_card_layout' => 'overlay',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register sheen content controls.
     *
     * @return void
     */
    protected function register_content_sheen_controls(): void
    {
        $this->start_controls_section(
            'kng_iis_sheen_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Sheen Settings', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_iis_sheen_preset',
            [
                'label' => esc_html__('Preset', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'silk-soft',
                'options' => [
                    'silk-soft' => esc_html__('Silk Soft (Wide & Subtle)', 'king-addons'),
                    'gloss-sharp' => esc_html__('Gloss Sharp (Narrow & Bright)', 'king-addons'),
                    'dual-streak' => esc_html__('Dual Streak (Two Lines)', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_iis_sheen_angle',
            [
                'label' => esc_html__('Angle', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => -90, 'max' => 90, 'step' => 1],
                ],
                'default' => ['size' => 25, 'unit' => 'px'],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis' => '--kng-iis-angle: {{SIZE}}deg;',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_sheen_intensity',
            [
                'label' => esc_html__('Intensity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 100, 'step' => 1],
                ],
                'default' => ['size' => 60, 'unit' => 'px'],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis' => '--kng-iis-intensity: {{SIZE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_sheen_duration',
            [
                'label' => esc_html__('Animation Duration (ms)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 150, 'max' => 2000, 'step' => 50],
                ],
                'default' => ['size' => 600, 'unit' => 'px'],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis' => '--kng-iis-duration: {{SIZE}}ms;',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_sheen_easing',
            [
                'label' => esc_html__('Animation Easing', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'ease-out',
                'options' => [
                    'linear' => esc_html__('Linear', 'king-addons'),
                    'ease' => esc_html__('Ease', 'king-addons'),
                    'ease-in' => esc_html__('Ease In', 'king-addons'),
                    'ease-out' => esc_html__('Ease Out', 'king-addons'),
                    'ease-in-out' => esc_html__('Ease In-Out', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__sheen' => 'animation-timing-function: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_sheen_direction',
            [
                'label' => esc_html__('Animation Direction', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left-right',
                'options' => [
                    'left-right' => esc_html__('Left to Right', 'king-addons'),
                    'right-left' => esc_html__('Right to Left', 'king-addons'),
                    'top-bottom' => esc_html__('Top to Bottom', 'king-addons'),
                    'bottom-top' => esc_html__('Bottom to Top', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_iis_sheen_repeat',
            [
                'label' => esc_html__('Animation Mode', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'once',
                'options' => [
                    'once' => esc_html__('Play Once on Hover', 'king-addons'),
                    'loop' => esc_html__('Loop While Hovering', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_iis_sheen_trigger',
            [
                'label' => esc_html__('Trigger', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'hover',
                'options' => [
                    'hover' => esc_html__('On Hover', 'king-addons'),
                    'always' => esc_html__('Always Playing', 'king-addons'),
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register Pro features notice.
     *
     * @return void
     */
    protected function register_pro_notice_controls(): void
    {
        if (!$this->is_pro_enabled()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'interactive-image-sheen', [
                'Follow cursor (sheen follows mouse)',
                'Layered sheen (2-3 layers)',
                'Mask by shape (circle, rounded rect)',
                'Mobile fallback controls',
                'Per-layer opacity & size',
            ]);
        }
    }

    /**
     * Register container style controls.
     *
     * @return void
     */
    protected function register_style_container_controls(): void
    {
        $this->start_controls_section(
            'kng_iis_style_container_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Container', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_iis_container_width',
            [
                'label' => esc_html__('Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vw'],
                'range' => [
                    'px' => ['min' => 100, 'max' => 1200],
                    '%' => ['min' => 10, 'max' => 100],
                    'vw' => ['min' => 10, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_iis_container_height',
            [
                'label' => esc_html__('Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => ['min' => 100, 'max' => 800],
                    'vh' => ['min' => 10, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__media' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_iis_container_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .kng-iis__media' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_iis_container_border',
                'selector' => '{{WRAPPER}} .kng-iis',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_iis_container_shadow',
                'selector' => '{{WRAPPER}} .kng-iis',
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_iis_container_bg',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .kng-iis',
                'condition' => [
                    'kng_iis_mode' => 'card',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register sheen style controls.
     *
     * @return void
     */
    protected function register_style_sheen_controls(): void
    {
        $this->start_controls_section(
            'kng_iis_style_sheen_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Sheen', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_iis_sheen_color',
            [
                'label' => esc_html__('Sheen Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .kng-iis' => '--kng-iis-sheen-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_sheen_opacity',
            [
                'label' => esc_html__('Base Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 1, 'step' => 0.05],
                ],
                'default' => ['size' => 0.6],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis' => '--kng-iis-sheen-opacity: {{SIZE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_sheen_softness',
            [
                'label' => esc_html__('Gradient Softness', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 100, 'step' => 1],
                ],
                'default' => ['size' => 30],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis' => '--kng-iis-softness: {{SIZE}}%;',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_sheen_width',
            [
                'label' => esc_html__('Sheen Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 10, 'max' => 100, 'step' => 1],
                ],
                'default' => ['size' => 50],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis' => '--kng-iis-sheen-width: {{SIZE}}%;',
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
            'kng_iis_style_card_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_iis_mode' => 'card',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_iis_card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_card_text_align',
            [
                'label' => esc_html__('Text Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'default' => 'left',
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
                    '{{WRAPPER}} .kng-iis__content' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        // Title
        $this->add_control(
            'kng_iis_card_title_heading',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_iis_card_title_typography',
                'selector' => '{{WRAPPER}} .kng-iis__title',
            ]
        );

        $this->add_control(
            'kng_iis_card_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_iis_card_title_spacing',
            [
                'label' => esc_html__('Title Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Description
        $this->add_control(
            'kng_iis_card_desc_heading',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_iis_card_desc_typography',
                'selector' => '{{WRAPPER}} .kng-iis__description',
            ]
        );

        $this->add_control(
            'kng_iis_card_desc_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_iis_card_desc_spacing',
            [
                'label' => esc_html__('Description Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__description' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Button
        $this->add_control(
            'kng_iis_card_button_heading_style',
            [
                'label' => esc_html__('Button', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'kng_iis_card_button_show' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_iis_card_button_typography',
                'selector' => '{{WRAPPER}} .kng-iis__button',
                'condition' => [
                    'kng_iis_card_button_show' => 'yes',
                ],
            ]
        );

        $this->start_controls_tabs(
            'kng_iis_card_button_tabs',
            [
                'condition' => [
                    'kng_iis_card_button_show' => 'yes',
                ],
            ]
        );

        $this->start_controls_tab(
            'kng_iis_card_button_normal',
            ['label' => esc_html__('Normal', 'king-addons')]
        );

        $this->add_control(
            'kng_iis_card_button_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_card_button_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'kng_iis_card_button_hover',
            ['label' => esc_html__('Hover', 'king-addons')]
        );

        $this->add_control(
            'kng_iis_card_button_hover_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_card_button_hover_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'kng_iis_card_button_padding',
            [
                'label' => esc_html__('Button Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'separator' => 'before',
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_iis_card_button_show' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_iis_card_button_radius',
            [
                'label' => esc_html__('Button Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px'],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_iis_card_button_show' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_iis_card_button_border',
                'selector' => '{{WRAPPER}} .kng-iis__button',
                'condition' => [
                    'kng_iis_card_button_show' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // =====================================================================
        // Style: Overlay Background (for Card mode)
        // =====================================================================
        $this->start_controls_section(
            'kng_iis_style_overlay_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Overlay Background', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_iis_mode' => 'card',
                    'kng_iis_card_layout' => 'overlay',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_iis_overlay_bg',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .kng-iis__content--overlay',
            ]
        );

        $this->add_responsive_control(
            'kng_iis_overlay_opacity',
            [
                'label' => esc_html__('Background Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 1, 'step' => 0.05],
                ],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis__content--overlay' => 'background-color: rgba(0, 0, 0, {{SIZE}});',
                ],
                'condition' => [
                    'kng_iis_overlay_bg_background' => '',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register hover state style controls.
     *
     * @return void
     */
    protected function register_style_hover_controls(): void
    {
        $this->start_controls_section(
            'kng_iis_style_hover_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Hover Effects', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_iis_hover_scale',
            [
                'label' => esc_html__('Scale on Hover', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_iis_hover_scale_value',
            [
                'label' => esc_html__('Scale Amount', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 1, 'max' => 1.2, 'step' => 0.01],
                ],
                'default' => ['size' => 1.03],
                'selectors' => [
                    '{{WRAPPER}} .kng-iis:hover .kng-iis__media' => 'transform: scale({{SIZE}});',
                ],
                'condition' => [
                    'kng_iis_hover_scale' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_iis_hover_shadow',
            [
                'label' => esc_html__('Increase Shadow on Hover', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_iis_hover_shadow_value',
                'selector' => '{{WRAPPER}} .kng-iis:hover',
                'condition' => [
                    'kng_iis_hover_shadow' => 'yes',
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
    protected function render(): void
    {
        $settings = $this->get_settings_for_display();

        $mode = $settings['kng_iis_mode'] ?? 'image';
        $preset = $settings['kng_iis_sheen_preset'] ?? 'silk-soft';
        $repeat = $settings['kng_iis_sheen_repeat'] ?? 'once';
        $trigger = $settings['kng_iis_sheen_trigger'] ?? 'hover';

        $wrapper_classes = $this->get_wrapper_classes($settings);
        $data_settings = $this->get_data_settings($settings);

        $this->add_render_attribute('wrapper', [
            'class' => implode(' ', $wrapper_classes),
            'data-settings' => wp_json_encode($data_settings),
        ]);

        $link = $settings['kng_iis_link'] ?? [];
        $has_wrapper_link = !empty($link['url']) && 'image' === $mode;

        echo '<div ' . $this->get_render_attribute_string('wrapper') . '>';

        // Sheen overlay
        echo '<div class="kng-iis__sheen" aria-hidden="true"></div>';

        // Media container
        $this->render_media($settings, $has_wrapper_link);

        // Card content
        if ('card' === $mode) {
            $this->render_card_content($settings);
        }

        echo '</div>';
    }

    /**
     * Render media (image).
     *
     * @param array $settings Widget settings.
     * @param bool $has_link Whether image has a link.
     * @return void
     */
    protected function render_media(array $settings, bool $has_link): void
    {
        $image = $settings['kng_iis_image'] ?? [];
        $image_url = Group_Control_Image_Size::get_attachment_image_src(
            $image['id'] ?? 0,
            'kng_iis_image_size',
            $settings
        );

        if (empty($image_url)) {
            $image_url = $image['url'] ?? Utils::get_placeholder_image_src();
        }

        $link = $settings['kng_iis_link'] ?? [];

        echo '<div class="kng-iis__media">';

        if ($has_link && !empty($link['url'])) {
            $link_attrs = $this->get_link_attributes($link);
            echo '<a ' . $link_attrs . ' class="kng-iis__link">';
        }

        echo '<img src="' . esc_url($image_url) . '" alt="" class="kng-iis__image" loading="lazy">';

        if ($has_link && !empty($link['url'])) {
            echo '</a>';
        }

        echo '</div>';
    }

    /**
     * Render card content.
     *
     * @param array $settings Widget settings.
     * @return void
     */
    protected function render_card_content(array $settings): void
    {
        $title = $settings['kng_iis_card_title'] ?? '';
        $title_tag = $settings['kng_iis_card_title_tag'] ?? 'h3';
        $description = $settings['kng_iis_card_description'] ?? '';
        $layout = $settings['kng_iis_card_layout'] ?? 'below';
        $position = $settings['kng_iis_card_overlay_position'] ?? 'bottom';
        $show_button = 'yes' === ($settings['kng_iis_card_button_show'] ?? '');

        $content_classes = ['kng-iis__content'];
        if ('overlay' === $layout) {
            $content_classes[] = 'kng-iis__content--overlay';
            $content_classes[] = 'kng-iis__content--' . $position;
        }

        echo '<div class="' . esc_attr(implode(' ', $content_classes)) . '">';

        // Title
        if (!empty($title)) {
            $allowed_tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span'];
            $title_tag = in_array($title_tag, $allowed_tags, true) ? $title_tag : 'h3';
            echo '<' . esc_attr($title_tag) . ' class="kng-iis__title">' . esc_html($title) . '</' . esc_attr($title_tag) . '>';
        }

        // Description
        if (!empty($description)) {
            echo '<p class="kng-iis__description">' . esc_html($description) . '</p>';
        }

        // Button
        if ($show_button) {
            $this->render_button($settings);
        }

        echo '</div>';
    }

    /**
     * Render button.
     *
     * @param array $settings Widget settings.
     * @return void
     */
    protected function render_button(array $settings): void
    {
        $button_text = $settings['kng_iis_card_button_text'] ?? '';
        $button_link = $settings['kng_iis_card_button_link'] ?? [];

        if (empty($button_text)) {
            return;
        }

        $tag = 'span';
        $attrs = '';

        if (!empty($button_link['url'])) {
            $tag = 'a';
            $attrs = $this->get_link_attributes($button_link);
        }

        echo '<' . esc_attr($tag) . ' ' . $attrs . ' class="kng-iis__button">' . esc_html($button_text) . '</' . esc_attr($tag) . '>';
    }

    /**
     * Get wrapper CSS classes.
     *
     * @param array $settings Widget settings.
     * @return array
     */
    protected function get_wrapper_classes(array $settings): array
    {
        $classes = ['kng-iis'];

        $mode = $settings['kng_iis_mode'] ?? 'image';
        $classes[] = 'kng-iis--mode-' . $mode;

        $preset = $settings['kng_iis_sheen_preset'] ?? 'silk-soft';
        $classes[] = 'kng-iis--preset-' . $preset;

        $repeat = $settings['kng_iis_sheen_repeat'] ?? 'once';
        $classes[] = 'kng-iis--repeat-' . $repeat;

        $trigger = $settings['kng_iis_sheen_trigger'] ?? 'hover';
        $classes[] = 'kng-iis--trigger-' . $trigger;

        $direction = $settings['kng_iis_sheen_direction'] ?? 'left-right';
        $classes[] = 'kng-iis--direction-' . $direction;

        if ($this->is_pro_enabled()) {
            $classes[] = 'kng-iis--pro';
        }

        return $classes;
    }

    /**
     * Get data settings for JS.
     *
     * @param array $settings Widget settings.
     * @return array
     */
    protected function get_data_settings(array $settings): array
    {
        return [
            'preset' => $settings['kng_iis_sheen_preset'] ?? 'silk-soft',
            'trigger' => $settings['kng_iis_sheen_trigger'] ?? 'hover',
            'repeat' => $settings['kng_iis_sheen_repeat'] ?? 'once',
        ];
    }

    /**
     * Get link attributes string.
     *
     * @param array $link Link data.
     * @return string
     */
    protected function get_link_attributes(array $link): string
    {
        if (empty($link['url'])) {
            return '';
        }

        $attributes = [
            'href' => esc_url($link['url']),
        ];

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
     * Check if Pro features are enabled.
     *
     * @return bool
     */
    protected function is_pro_enabled(): bool
    {
        return function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();
    }

    /**
     * Check if in editor mode.
     *
     * @return bool
     */
    protected function is_editor(): bool
    {
        return class_exists(Plugin::class) && Plugin::$instance->editor->is_edit_mode();
    }
}
