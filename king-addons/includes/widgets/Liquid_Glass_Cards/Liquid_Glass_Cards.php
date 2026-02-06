<?php
/**
 * Liquid Glass Cards Widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

class Liquid_Glass_Cards extends Widget_Base
{
    public function get_name(): string
    {
        return 'king-addons-liquid-glass-cards';
    }

    public function get_title(): string
    {
        return esc_html__('Liquid Glass Cards', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-info-box';
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-liquid-glass-cards-style'];
    }

    public function get_script_depends(): array
    {
        if (!$this->is_pro_enabled()) {
            return [];
        }

        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-liquid-glass-cards-script'];
    }

    public function get_categories(): array
    {
        return ['king-addons'];
    }

    public function get_keywords(): array
    {
        return ['glass', 'liquid', 'cards', 'frosted', 'blur', 'noise', 'refraction', 'king-addons'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        protected function register_controls(): void
    {
        $this->register_content_controls();
        $this->register_layout_controls();
        $this->register_style_preset_controls();
        $this->register_style_material_controls();
        $this->register_style_text_controls();
        $this->register_style_badge_controls();
        $this->register_style_icon_controls();
        $this->register_style_button_controls();
        $this->register_pro_controls();
        $this->register_pro_notice_controls();
    }

    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $this->render_output($settings);
    }

    protected function register_content_controls(): void
    {
        $this->start_controls_section(
            'kng_cards_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Cards', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_card_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Glass-ready headline', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_card_subtitle',
            [
                'label' => esc_html__('Subtitle', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Short supporting line', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_card_description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 4,
                'default' => esc_html__('Soft glass layers with blur, noise, and subtle highlights.', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'kng_card_badge',
            [
                'label' => esc_html__('Badge', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Featured', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'kng_card_icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-star',
                    'library' => 'solid',
                ],
                'skin' => 'inline',
            ]
        );

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
            'kng_card_image_display',
            [
                'label' => esc_html__('Image Placement', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'layer',
                'options' => [
                    'layer' => esc_html__('Background Layer', 'king-addons'),
                    'thumb' => esc_html__('Thumbnail', 'king-addons'),
                ],
            ]
        );

        $repeater->add_control(
            'kng_card_button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Learn more', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
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
            'kng_card_link_mode',
            [
                'label' => esc_html__('Card Link Mode', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'button',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'button' => esc_html__('Button Only', 'king-addons'),
                    'card' => esc_html__('Whole Card', 'king-addons'),
                ],
            ]
        );

        $repeater->add_control(
            'kng_card_show_badge',
            [
                'label' => esc_html__('Show Badge', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $repeater->add_control(
            'kng_card_show_icon',
            [
                'label' => esc_html__('Show Icon', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $repeater->add_control(
            'kng_card_show_button',
            [
                'label' => esc_html__('Show Button', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
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
                'default' => 3,
                'tablet_default' => 2,
                'mobile_default' => 1,
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
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
                    'size' => 24,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_equal_height',
            [
                'label' => esc_html__('Equal Height', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'render_type' => 'template',
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
                'default' => 'left',
                'toggle' => false,
                'render_type' => 'template',
            ]
        );

        $this->add_responsive_control(
            'kng_card_min_height',
            [
                'label' => esc_html__('Card Min Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 600,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__card' => 'min-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_card_padding',
            [
                'label' => esc_html__('Card Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_content_gap',
            [
                'label' => esc_html__('Content Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__content' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
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
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__card' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-liquid-glass__layer' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_shadow_style',
            [
                'label' => esc_html__('Shadow', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'soft',
                'options' => [
                    'off' => esc_html__('Off', 'king-addons'),
                    'soft' => esc_html__('Soft', 'king-addons'),
                    'medium' => esc_html__('Medium', 'king-addons'),
                    'strong' => esc_html__('Strong', 'king-addons'),
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_title_tag',
            [
                'label' => esc_html__('Title HTML Tag', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'h3',
                'options' => [
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'div' => 'DIV',
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_preset_controls(): void
    {
        $this->start_controls_section(
            'kng_preset_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Presets', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_preset',
            [
                'label' => esc_html__('Preset', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'clear',
                'options' => [
                    'clear' => esc_html__('Clear Glass', 'king-addons'),
                    'frosted' => esc_html__('Frosted Glass', 'king-addons'),
                    'dark' => esc_html__('Dark Glass', 'king-addons'),
                ],
                'render_type' => 'template',
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_material_controls(): void
    {
        $this->start_controls_section(
            'kng_material_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Glass Material', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_blur',
            [
                'label' => esc_html__('Blur', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_tint_mode',
            [
                'label' => esc_html__('Tint', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto' => esc_html__('Auto', 'king-addons'),
                    'light' => esc_html__('Light', 'king-addons'),
                    'dark' => esc_html__('Dark', 'king-addons'),
                    'custom' => esc_html__('Custom', 'king-addons'),
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_tint_color',
            [
                'label' => esc_html__('Custom Tint', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'condition' => [
                    'kng_tint_mode' => 'custom',
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_border_width',
            [
                'label' => esc_html__('Border Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 6,
                    ],
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_noise_mode',
            [
                'label' => esc_html__('Noise Overlay', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'on',
                'options' => [
                    'on' => esc_html__('On', 'king-addons'),
                    'off' => esc_html__('Off', 'king-addons'),
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_noise_opacity',
            [
                'label' => esc_html__('Noise Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['custom'],
                'range' => [
                    'custom' => [
                        'min' => 0,
                        'max' => 0.35,
                        'step' => 0.01,
                    ],
                ],
                'condition' => [
                    'kng_noise_mode' => 'on',
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_noise_scale',
            [
                'label' => esc_html__('Noise Scale', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 120,
                    ],
                ],
                'condition' => [
                    'kng_noise_mode' => 'on',
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_highlight_intensity',
            [
                'label' => esc_html__('Highlight Intensity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['custom'],
                'range' => [
                    'custom' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.01,
                    ],
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_highlight_position',
            [
                'label' => esc_html__('Highlight Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'top-left',
                'options' => [
                    'top-left' => esc_html__('Top Left', 'king-addons'),
                    'top-right' => esc_html__('Top Right', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                    'bottom' => esc_html__('Bottom', 'king-addons'),
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_highlight_color',
            [
                'label' => esc_html__('Highlight Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_image_layer_heading',
            [
                'label' => esc_html__('Image Layer', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_image_opacity',
            [
                'label' => esc_html__('Image Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['custom'],
                'range' => [
                    'custom' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.01,
                    ],
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_image_blend',
            [
                'label' => esc_html__('Image Blend Mode', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'screen',
                'options' => [
                    'normal' => esc_html__('Normal', 'king-addons'),
                    'screen' => esc_html__('Screen', 'king-addons'),
                    'overlay' => esc_html__('Overlay', 'king-addons'),
                    'soft-light' => esc_html__('Soft Light', 'king-addons'),
                    'multiply' => esc_html__('Multiply', 'king-addons'),
                ],
                'render_type' => 'template',
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_text_controls(): void
    {
        $this->start_controls_section(
            'kng_typography_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Typography', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'label' => esc_html__('Title Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-liquid-glass__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_subtitle_typography',
                'label' => esc_html__('Subtitle Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-liquid-glass__subtitle',
            ]
        );

        $this->add_control(
            'kng_subtitle_color',
            [
                'label' => esc_html__('Subtitle Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__subtitle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_description_typography',
                'label' => esc_html__('Description Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-liquid-glass__description',
            ]
        );

        $this->add_control(
            'kng_description_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_badge_controls(): void
    {
        $this->start_controls_section(
            'kng_badge_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Badge', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_badge_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-liquid-glass__badge',
            ]
        );

        $this->add_control(
            'kng_badge_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__badge' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_badge_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__badge' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_badge_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__badge' => 'border-radius: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-liquid-glass__badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_icon_controls(): void
    {
        $this->start_controls_section(
            'kng_icon_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Icon', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_icon_size',
            [
                'label' => esc_html__('Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 12,
                        'max' => 80,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__icon' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-liquid-glass__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-liquid-glass__icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_button_controls(): void
    {
        $this->start_controls_section(
            'kng_button_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'label' => esc_html__('Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-liquid-glass__button',
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
                    '{{WRAPPER}} .king-liquid-glass__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__button' => 'border-color: {{VALUE}};',
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
                    '{{WRAPPER}} .king-liquid-glass__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_background_hover',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_border_color_hover',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'kng_button_border_width',
            [
                'label' => esc_html__('Border Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 6,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__button' => 'border-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_radius',
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
                    '{{WRAPPER}} .king-liquid-glass__button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-liquid-glass__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_pro_controls(): void
    {
        $is_pro = $this->is_pro_enabled();
        $pro_class = $this->get_pro_control_class();

        $this->start_controls_section(
            'kng_tilt_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON_PRO . esc_html__('Interactive Tilt', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_enable_tilt',
            [
                'label' => $is_pro ? esc_html__('Enable Tilt', 'king-addons') : sprintf(__('Enable Tilt %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'classes' => $pro_class,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_tilt_input',
            [
                'label' => esc_html__('Input Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'pointer',
                'options' => [
                    'pointer' => esc_html__('Pointer', 'king-addons'),
                    'orientation' => esc_html__('Device Orientation', 'king-addons'),
                ],
                'condition' => [
                    'kng_enable_tilt' => 'yes',
                ],
                'classes' => $pro_class,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_tilt_max',
            [
                'label' => esc_html__('Max Tilt Angle', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['custom'],
                'range' => [
                    'custom' => [
                        'min' => 0,
                        'max' => 12,
                    ],
                ],
                'default' => [
                    'size' => 8,
                ],
                'condition' => [
                    'kng_enable_tilt' => 'yes',
                ],
                'classes' => $pro_class,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_tilt_smoothing',
            [
                'label' => esc_html__('Tilt Smoothing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['custom'],
                'range' => [
                    'custom' => [
                        'min' => 0.02,
                        'max' => 0.5,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 0.12,
                ],
                'condition' => [
                    'kng_enable_tilt' => 'yes',
                ],
                'classes' => $pro_class,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_glare_enable',
            [
                'label' => esc_html__('Glare Highlight', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_enable_tilt' => 'yes',
                ],
                'classes' => $pro_class,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_glare_intensity',
            [
                'label' => esc_html__('Glare Intensity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['custom'],
                'range' => [
                    'custom' => [
                        'min' => 0,
                        'max' => 0.8,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 0.35,
                ],
                'condition' => [
                    'kng_enable_tilt' => 'yes',
                    'kng_glare_enable' => 'yes',
                ],
                'classes' => $pro_class,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_glare_size',
            [
                'label' => esc_html__('Glare Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 20,
                        'max' => 120,
                    ],
                ],
                'default' => [
                    'size' => 65,
                    'unit' => '%',
                ],
                'condition' => [
                    'kng_enable_tilt' => 'yes',
                    'kng_glare_enable' => 'yes',
                ],
                'classes' => $pro_class,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_glare_blend',
            [
                'label' => esc_html__('Glare Blend Mode', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'screen',
                'options' => [
                    'normal' => esc_html__('Normal', 'king-addons'),
                    'screen' => esc_html__('Screen', 'king-addons'),
                    'overlay' => esc_html__('Overlay', 'king-addons'),
                ],
                'condition' => [
                    'kng_enable_tilt' => 'yes',
                    'kng_glare_enable' => 'yes',
                ],
                'classes' => $pro_class,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_hover_lift',
            [
                'label' => esc_html__('Hover Lift', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'condition' => [
                    'kng_enable_tilt' => 'yes',
                ],
                'classes' => $pro_class,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_hover_lift_distance',
            [
                'label' => esc_html__('Lift Distance', 'king-addons'),
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
                'condition' => [
                    'kng_enable_tilt' => 'yes',
                    'kng_hover_lift' => 'yes',
                ],
                'classes' => $pro_class,
                'render_type' => 'template',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'liquid-glass-cards', 'kng_enable_tilt', ['yes']);

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_parallax_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON_PRO . esc_html__('Parallax Layers', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_enable_parallax',
            [
                'label' => $is_pro ? esc_html__('Enable Parallax', 'king-addons') : sprintf(__('Enable Parallax %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'classes' => $pro_class,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_parallax_mode',
            [
                'label' => esc_html__('Parallax Mode', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'pointer',
                'options' => [
                    'pointer' => esc_html__('Pointer', 'king-addons'),
                    'scroll' => esc_html__('Scroll', 'king-addons'),
                ],
                'condition' => [
                    'kng_enable_parallax' => 'yes',
                ],
                'classes' => $pro_class,
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_parallax_image_depth',
            [
                'label' => esc_html__('Image Layer Depth', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => -30,
                        'max' => 30,
                    ],
                ],
                'default' => [
                    'size' => 12,
                    'unit' => 'px',
                ],
                'condition' => [
                    'kng_enable_parallax' => 'yes',
                ],
                'classes' => $pro_class,
                'render_type' => 'template',
            ]
        );

        $layer_repeater = new Repeater();

        $layer_repeater->add_control(
            'kng_layer_type',
            [
                'label' => esc_html__('Layer Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'gradient',
                'options' => [
                    'image' => esc_html__('Image', 'king-addons'),
                    'gradient' => esc_html__('Gradient', 'king-addons'),
                    'highlight' => esc_html__('Highlight', 'king-addons'),
                ],
            ]
        );

        $layer_repeater->add_control(
            'kng_layer_image',
            [
                'label' => esc_html__('Layer Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'condition' => [
                    'kng_layer_type' => 'image',
                ],
            ]
        );

        $layer_repeater->add_control(
            'kng_layer_color',
            [
                'label' => esc_html__('Layer Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'condition' => [
                    'kng_layer_type!' => 'image',
                ],
            ]
        );

        $layer_repeater->add_control(
            'kng_layer_color_end',
            [
                'label' => esc_html__('Secondary Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'condition' => [
                    'kng_layer_type' => 'gradient',
                ],
            ]
        );

        $layer_repeater->add_control(
            'kng_layer_depth',
            [
                'label' => esc_html__('Depth', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => -30,
                        'max' => 30,
                    ],
                ],
                'default' => [
                    'size' => 14,
                    'unit' => 'px',
                ],
            ]
        );

        $layer_repeater->add_control(
            'kng_layer_opacity',
            [
                'label' => esc_html__('Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['custom'],
                'range' => [
                    'custom' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 0.35,
                ],
            ]
        );

        $layer_repeater->add_control(
            'kng_layer_blur',
            [
                'label' => esc_html__('Blur', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                ],
            ]
        );

        $layer_repeater->add_control(
            'kng_layer_blend',
            [
                'label' => esc_html__('Blend Mode', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'normal',
                'options' => [
                    'normal' => esc_html__('Normal', 'king-addons'),
                    'screen' => esc_html__('Screen', 'king-addons'),
                    'overlay' => esc_html__('Overlay', 'king-addons'),
                    'soft-light' => esc_html__('Soft Light', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_parallax_layers',
            [
                'label' => esc_html__('Layers', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $layer_repeater->get_controls(),
                'title_field' => '{{{ kng_layer_type }}}',
                'default' => [
                    [
                        'kng_layer_type' => 'gradient',
                        'kng_layer_color' => 'rgba(255,255,255,0.6)',
                        'kng_layer_color_end' => 'rgba(255,255,255,0)',
                        'kng_layer_depth' => [
                            'size' => 10,
                            'unit' => 'px',
                        ],
                    ],
                ],
                'condition' => [
                    'kng_enable_parallax' => 'yes',
                ],
                'classes' => $pro_class,
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'liquid-glass-cards', 'kng_enable_parallax', ['yes']);

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_performance_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON_PRO . esc_html__('Adaptive Performance', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_performance_mode',
            [
                'label' => $is_pro ? esc_html__('Performance Mode', 'king-addons') : sprintf(__('Performance Mode %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto' => esc_html__('Auto', 'king-addons'),
                    'quality' => esc_html__('Quality', 'king-addons'),
                    'balanced' => esc_html__('Balanced', 'king-addons'),
                    'performance' => esc_html__('Performance', 'king-addons'),
                ],
                'classes' => $pro_class,
                'render_type' => 'template',
            ]
        );

        $this->end_controls_section();
    }

    protected function register_pro_notice_controls(): void
    {
        Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'liquid-glass-cards', [
            'Add interactive depth with pointer-based tilt and dynamic highlights.',
            'Layered parallax creates premium dimensionality without heavy libraries.',
            'Auto-adjust effects for smooth rendering on any device.',
        ]);
    }

    protected function render_output(array $settings): void
    {
        $cards = $settings['kng_cards'] ?? [];
        if (empty($cards)) {
            return;
        }

        $is_editor = class_exists(Plugin::class) && Plugin::$instance->editor->is_edit_mode();
        $is_pro = $this->is_pro_enabled();
        $wrapper_classes = $this->get_wrapper_classes($settings, $is_pro);
        $wrapper_style = $this->get_wrapper_style($settings);
        $data_attributes = $this->get_data_attributes($settings, $cards, $is_pro);

        if ($is_pro && $this->should_enqueue_script($settings)) {
            wp_enqueue_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-liquid-glass-cards-script');
        }

        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"<?php echo $wrapper_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $data_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <?php if ($is_editor && count($cards) > 12 && $is_pro && $this->should_enqueue_script($settings)) : ?>
                <div class="king-liquid-glass__notice elementor-alert elementor-alert-warning">
                    <?php echo esc_html__('Tip: For best performance, keep card count under 12 when effects are enabled.', 'king-addons'); ?>
                </div>
            <?php endif; ?>
            <div class="king-liquid-glass__grid">
                <?php foreach ($cards as $index => $card) : ?>
                    <?php $this->render_card($settings, $card, $index, $is_pro); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    protected function render_card(array $settings, array $card, int $index, bool $is_pro): void
    {
        $title = $card['kng_card_title'] ?? '';
        $subtitle = $card['kng_card_subtitle'] ?? '';
        $description = $card['kng_card_description'] ?? '';
        $badge = $card['kng_card_badge'] ?? '';
        $icon = $card['kng_card_icon'] ?? [];
        $button_text = $card['kng_card_button_text'] ?? '';
        $button_link = $card['kng_card_button_link'] ?? [];
        $link_mode = $card['kng_card_link_mode'] ?? 'button';

        $show_badge = ($card['kng_card_show_badge'] ?? 'yes') === 'yes';
        $show_icon = ($card['kng_card_show_icon'] ?? 'yes') === 'yes';
        $show_button = ($card['kng_card_show_button'] ?? 'yes') === 'yes';

        $image_display = $card['kng_card_image_display'] ?? 'layer';
        $image_html = $image_display === 'thumb' ? $this->get_card_image_html($card) : '';
        $image_src = $image_display === 'layer' ? $this->get_card_image_src($settings, $card) : '';

        $card_classes = ['king-liquid-glass__card'];
        if (!empty($card['_id'])) {
            $card_classes[] = 'elementor-repeater-item-' . sanitize_html_class((string) $card['_id']);
        }

        $title_tag = $this->get_card_title_tag($settings);
        $link_attributes = $this->get_link_attributes($button_link);
        $card_link_enabled = $link_mode === 'card' && !empty($button_link['url']);
        $button_link_enabled = $link_mode === 'button' && !empty($button_link['url']);

        $image_depth = $this->get_parallax_image_depth($settings, $is_pro);
        $parallax_layers = $this->get_parallax_layers($settings, $is_pro);

        ?>
        <article class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
            <div class="king-liquid-glass__layers">
                <div class="king-liquid-glass__layer king-liquid-glass__layer--bg"></div>
                <?php if ($image_src) : ?>
                    <div class="king-liquid-glass__layer king-liquid-glass__layer--image"<?php echo $image_depth !== null ? ' data-depth="' . esc_attr((string) $image_depth) . '"' : ''; ?> style="background-image: url('<?php echo esc_url($image_src); ?>');"></div>
                <?php endif; ?>
                <?php if (!empty($parallax_layers)) : ?>
                    <?php foreach ($parallax_layers as $layer) : ?>
                        <?php $this->render_parallax_layer($layer); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="king-liquid-glass__layer king-liquid-glass__layer--noise"></div>
                <div class="king-liquid-glass__layer king-liquid-glass__layer--highlight"></div>
            </div>
            <div class="king-liquid-glass__content">
                <?php if ($show_badge && !empty($badge)) : ?>
                    <div class="king-liquid-glass__badge"><?php echo esc_html($badge); ?></div>
                <?php endif; ?>
                <?php if ($show_icon && !empty($icon['value'])) : ?>
                    <div class="king-liquid-glass__icon">
                        <?php Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']); ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($image_html)) : ?>
                    <div class="king-liquid-glass__media">
                        <?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($title)) : ?>
                    <<?php echo tag_escape($title_tag); ?> class="king-liquid-glass__title"><?php echo esc_html($title); ?></<?php echo tag_escape($title_tag); ?>>
                <?php endif; ?>
                <?php if (!empty($subtitle)) : ?>
                    <div class="king-liquid-glass__subtitle"><?php echo esc_html($subtitle); ?></div>
                <?php endif; ?>
                <?php if (!empty($description)) : ?>
                    <div class="king-liquid-glass__description"><?php echo wp_kses_post($description); ?></div>
                <?php endif; ?>
                <?php if ($show_button && !empty($button_text)) : ?>
                    <?php $this->render_button($button_text, $link_attributes, $button_link_enabled); ?>
                <?php endif; ?>
            </div>
            <?php if ($card_link_enabled) : ?>
                <a class="king-liquid-glass__card-link"<?php echo $link_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr($title ?: $button_text ?: esc_html__('Open card', 'king-addons')); ?>"></a>
            <?php endif; ?>
        </article>
        <?php
    }

    protected function render_button(string $text, string $link_attributes, bool $is_link): void
    {
        if ($is_link) {
            ?>
            <a class="king-liquid-glass__button"<?php echo $link_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <?php echo esc_html($text); ?>
            </a>
            <?php
            return;
        }
        ?>
        <span class="king-liquid-glass__button is-static"><?php echo esc_html($text); ?></span>
        <?php
    }

    protected function render_parallax_layer(array $layer): void
    {
        $layer_type = $layer['kng_layer_type'] ?? 'gradient';
        $depth = $layer['kng_layer_depth']['size'] ?? 0;
        $opacity = $layer['kng_layer_opacity']['size'] ?? '';
        $blur = $layer['kng_layer_blur']['size'] ?? '';
        $blend = $layer['kng_layer_blend'] ?? 'normal';

        $style_parts = [];
        if ($opacity !== '') {
            $style_parts[] = 'opacity:' . (float) $opacity . ';';
        }
        if ($blur !== '') {
            $style_parts[] = 'filter: blur(' . (float) $blur . 'px);';
        }
        if (!empty($blend)) {
            $style_parts[] = 'mix-blend-mode:' . esc_attr((string) $blend) . ';';
        }

        if ($layer_type === 'image') {
            $image = $layer['kng_layer_image']['url'] ?? '';
            if ($image) {
                $style_parts[] = 'background-image:url(' . esc_url($image) . ');';
            }
        } else {
            $color = $layer['kng_layer_color'] ?? '';
            if ($color === '') {
                $color = 'rgba(255,255,255,0.4)';
            }
            $secondary = $layer['kng_layer_color_end'] ?? '';
            if ($secondary === '') {
                $secondary = 'rgba(255,255,255,0)';
            }
            if ($layer_type === 'highlight') {
                $style_parts[] = 'background-image: radial-gradient(circle at 20% 20%, ' . esc_attr($color) . ' 0%, rgba(255,255,255,0) 65%);';
            } else {
                $style_parts[] = 'background-image: radial-gradient(circle at 30% 20%, ' . esc_attr($color) . ' 0%, ' . esc_attr($secondary) . ' 70%);';
            }
        }

        $style_attribute = '';
        if (!empty($style_parts)) {
            $style_attribute = ' style="' . esc_attr(implode(' ', $style_parts)) . '"';
        }

        $depth_attribute = $depth !== '' ? ' data-depth="' . esc_attr((string) $depth) . '"' : '';

        echo '<div class="king-liquid-glass__layer king-liquid-glass__layer--parallax"' . $depth_attribute . $style_attribute . '></div>';
    }

    protected function get_wrapper_classes(array $settings, bool $is_pro): array
    {
        $preset = $settings['kng_preset'] ?? 'clear';
        $align = $settings['kng_content_alignment'] ?? 'left';
        $shadow = $settings['kng_shadow_style'] ?? 'soft';
        $tint = $settings['kng_tint_mode'] ?? 'auto';
        $highlight = $settings['kng_highlight_position'] ?? 'top-left';
        $noise = $settings['kng_noise_mode'] ?? 'on';

        $classes = [
            'king-liquid-glass',
            'king-liquid-glass--preset-' . sanitize_html_class((string) $preset),
            'king-liquid-glass--align-' . sanitize_html_class((string) $align),
            'king-liquid-glass--shadow-' . sanitize_html_class((string) $shadow),
            'king-liquid-glass--tint-' . sanitize_html_class((string) $tint),
            'king-liquid-glass--highlight-' . sanitize_html_class((string) $highlight),
            'king-liquid-glass--noise-' . sanitize_html_class((string) $noise),
        ];

        if (($settings['kng_equal_height'] ?? '') === 'yes') {
            $classes[] = 'king-liquid-glass--equal-height';
        }

        if ($is_pro) {
            $classes[] = 'king-liquid-glass--pro';
        }

        return array_filter($classes);
    }

    protected function get_wrapper_style(array $settings): string
    {
        $style_parts = [];

        if (isset($settings['kng_blur']['size']) && $settings['kng_blur']['size'] !== '') {
            $style_parts[] = '--kng-liquid-blur:' . (float) $settings['kng_blur']['size'] . ($settings['kng_blur']['unit'] ?? 'px') . ';';
        }

        if (($settings['kng_tint_mode'] ?? '') === 'custom' && !empty($settings['kng_tint_color'])) {
            $style_parts[] = '--kng-liquid-tint:' . $settings['kng_tint_color'] . ';';
        }

        if (isset($settings['kng_border_width']['size']) && $settings['kng_border_width']['size'] !== '') {
            $style_parts[] = '--kng-liquid-border-width:' . (float) $settings['kng_border_width']['size'] . ($settings['kng_border_width']['unit'] ?? 'px') . ';';
        }

        if (!empty($settings['kng_border_color'])) {
            $style_parts[] = '--kng-liquid-border-color:' . $settings['kng_border_color'] . ';';
        }

        if (isset($settings['kng_noise_opacity']['size']) && $settings['kng_noise_opacity']['size'] !== '') {
            $style_parts[] = '--kng-liquid-noise-opacity:' . (float) $settings['kng_noise_opacity']['size'] . ';';
        }

        if (isset($settings['kng_noise_scale']['size']) && $settings['kng_noise_scale']['size'] !== '') {
            $style_parts[] = '--kng-liquid-noise-scale:' . (float) $settings['kng_noise_scale']['size'] . ($settings['kng_noise_scale']['unit'] ?? 'px') . ';';
        }

        if (isset($settings['kng_highlight_intensity']['size']) && $settings['kng_highlight_intensity']['size'] !== '') {
            $style_parts[] = '--kng-liquid-highlight-opacity:' . (float) $settings['kng_highlight_intensity']['size'] . ';';
        }

        if (!empty($settings['kng_highlight_color'])) {
            $style_parts[] = '--kng-liquid-highlight-color:' . $settings['kng_highlight_color'] . ';';
        }

        if (isset($settings['kng_image_opacity']['size']) && $settings['kng_image_opacity']['size'] !== '') {
            $style_parts[] = '--kng-liquid-image-opacity:' . (float) $settings['kng_image_opacity']['size'] . ';';
        }

        if (!empty($settings['kng_image_blend'])) {
            $style_parts[] = '--kng-liquid-image-blend:' . $settings['kng_image_blend'] . ';';
        }

        if (!empty($style_parts)) {
            return ' style="' . esc_attr(implode(' ', $style_parts)) . '"';
        }

        return '';
    }

    protected function get_data_attributes(array $settings, array $cards, bool $is_pro): string
    {
        if (!$is_pro) {
            return '';
        }

        $tilt_enabled = ($settings['kng_enable_tilt'] ?? '') === 'yes';
        $parallax_enabled = ($settings['kng_enable_parallax'] ?? '') === 'yes';

        if (!$tilt_enabled && !$parallax_enabled) {
            return '';
        }

        $tilt = [
            'enabled' => $tilt_enabled,
            'input' => $settings['kng_tilt_input'] ?? 'pointer',
            'max' => $settings['kng_tilt_max']['size'] ?? 8,
            'smoothing' => $settings['kng_tilt_smoothing']['size'] ?? 0.12,
            'glare' => [
                'enabled' => ($settings['kng_glare_enable'] ?? '') === 'yes',
                'intensity' => $settings['kng_glare_intensity']['size'] ?? 0.35,
                'size' => $settings['kng_glare_size']['size'] ?? 65,
                'blend' => $settings['kng_glare_blend'] ?? 'screen',
            ],
            'lift' => [
                'enabled' => ($settings['kng_hover_lift'] ?? '') === 'yes',
                'distance' => $settings['kng_hover_lift_distance']['size'] ?? 10,
            ],
        ];

        $parallax = [
            'enabled' => $parallax_enabled,
            'mode' => $settings['kng_parallax_mode'] ?? 'pointer',
        ];

        $performance = [
            'mode' => $settings['kng_performance_mode'] ?? 'auto',
        ];

        $payload = [
            'pro' => true,
            'cardCount' => count($cards),
            'tilt' => $tilt,
            'parallax' => $parallax,
            'performance' => $performance,
        ];

        return ' data-settings="' . esc_attr(wp_json_encode($payload)) . '"';
    }

    protected function get_link_attributes(array $link): string
    {
        $url = $link['url'] ?? '';
        if (empty($url)) {
            return '';
        }

        $attributes = [];
        $attributes[] = 'href="' . esc_url($url) . '"';

        if (!empty($link['is_external'])) {
            $attributes[] = 'target="_blank"';
        }

        $rel = [];
        if (!empty($link['nofollow'])) {
            $rel[] = 'nofollow';
        }
        if (!empty($link['is_external'])) {
            $rel[] = 'noopener';
            $rel[] = 'noreferrer';
        }

        if (!empty($rel)) {
            $attributes[] = 'rel="' . esc_attr(implode(' ', array_unique($rel))) . '"';
        }

        return ' ' . implode(' ', $attributes);
    }

    protected function get_card_image_html(array $card): string
    {
        if (empty($card['kng_card_image']['id']) && empty($card['kng_card_image']['url'])) {
            return '';
        }

        $image_html = Group_Control_Image_Size::get_attachment_image_html($card, 'kng_card_image');

        if (!empty($image_html)) {
            return $image_html;
        }

        return '<img src="' . esc_url(Utils::get_placeholder_image_src()) . '" alt="' . esc_attr($card['kng_card_title'] ?? '') . '"/>';
    }

    protected function get_card_image_src(array $settings, array $card): string
    {
        $image = $card['kng_card_image'] ?? [];
        if (empty($image['id']) && empty($image['url'])) {
            return '';
        }

        if (!empty($image['id'])) {
            $src = Group_Control_Image_Size::get_attachment_image_src($image['id'], 'kng_card_image', $settings);
            if ($src) {
                return $src;
            }
        }

        return $image['url'] ?? '';
    }

    protected function get_parallax_layers(array $settings, bool $is_pro): array
    {
        if (!$is_pro) {
            return [];
        }

        if (($settings['kng_enable_parallax'] ?? '') !== 'yes') {
            return [];
        }

        $layers = $settings['kng_parallax_layers'] ?? [];
        if (!is_array($layers)) {
            return [];
        }

        return array_slice($layers, 0, 4);
    }

    protected function get_parallax_image_depth(array $settings, bool $is_pro): ?float
    {
        if (!$is_pro || ($settings['kng_enable_parallax'] ?? '') !== 'yes') {
            return null;
        }

        if (!isset($settings['kng_parallax_image_depth']['size'])) {
            return null;
        }

        return (float) $settings['kng_parallax_image_depth']['size'];
    }

    protected function get_card_title_tag(array $settings): string
    {
        $tag = $settings['kng_title_tag'] ?? 'h3';
        $allowed = ['h2', 'h3', 'h4', 'div'];

        if (!in_array($tag, $allowed, true)) {
            return 'h3';
        }

        return $tag;
    }

    protected function get_default_cards(): array
    {
        $placeholder = Utils::get_placeholder_image_src();

        return [
            [
                'kng_card_title' => esc_html__('Liquid clarity', 'king-addons'),
                'kng_card_subtitle' => esc_html__('Preset one', 'king-addons'),
                'kng_card_description' => esc_html__('A clean glass card ready for hero sections and feature grids.', 'king-addons'),
                'kng_card_badge' => esc_html__('Preset 1', 'king-addons'),
                'kng_card_button_text' => esc_html__('Explore', 'king-addons'),
                'kng_card_image' => ['url' => $placeholder],
            ],
            [
                'kng_card_title' => esc_html__('Frosted depth', 'king-addons'),
                'kng_card_subtitle' => esc_html__('Preset two', 'king-addons'),
                'kng_card_description' => esc_html__('Softer blur and noise for layered marketing content.', 'king-addons'),
                'kng_card_badge' => esc_html__('Preset 2', 'king-addons'),
                'kng_card_button_text' => esc_html__('Details', 'king-addons'),
                'kng_card_image' => ['url' => $placeholder],
            ],
            [
                'kng_card_title' => esc_html__('Dark glass', 'king-addons'),
                'kng_card_subtitle' => esc_html__('Preset three', 'king-addons'),
                'kng_card_description' => esc_html__('Rich dark glass tuned for bold hero backdrops.', 'king-addons'),
                'kng_card_badge' => esc_html__('Preset 3', 'king-addons'),
                'kng_card_button_text' => esc_html__('See more', 'king-addons'),
                'kng_card_image' => ['url' => $placeholder],
            ],
        ];
    }

    protected function is_pro_enabled(): bool
    {
        return function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();
    }

    protected function should_enqueue_script(array $settings): bool
    {
        if (!$this->is_pro_enabled()) {
            return false;
        }

        $flags = [
            $settings['kng_enable_tilt'] ?? '',
            $settings['kng_enable_parallax'] ?? '',
        ];

        foreach ($flags as $flag) {
            if ($flag === 'yes') {
                return true;
            }
        }

        return false;
    }

    protected function get_pro_control_class(string $extra = ''): string
    {
        if ($this->is_pro_enabled()) {
            return $extra;
        }

        return trim('king-addons-pro-control ' . $extra);
    }
}
