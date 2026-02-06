<?php
/**
 * Scroll Story Sections Widget.
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
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Scroll_Story_Sections extends Widget_Base
{
    public function get_name(): string
    {
        return 'king-addons-scroll-story-sections';
    }

    public function get_title(): string
    {
        return esc_html__('Scroll Story Sections', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-scroll';
    }

    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-scroll-story-sections-style',
            'elementor-icons-fa-solid',
            'elementor-icons-fa-regular',
            'elementor-icons-fa-brands',
        ];
    }

    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-scroll-story-sections-script',
        ];
    }

    public function get_categories(): array
    {
        return ['king-addons'];
    }

    public function get_keywords(): array
    {
        return ['scroll', 'story', 'steps', 'timeline', 'narrative', 'sections', 'king-addons'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        protected function register_controls(): void
    {
        $this->register_layout_controls();
        $this->register_steps_controls();
        $this->register_behavior_controls();
        $this->register_pro_controls();
        $this->register_style_container_controls();
        $this->register_style_steps_controls();
        $this->register_style_content_controls();
        $this->register_style_media_controls();
        $this->register_pro_notice_controls();
    }

    protected function register_layout_controls(): void
    {
        $this->start_controls_section(
            'kng_scroll_story_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_scroll_story_layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'normal',
                'options' => [
                    'normal' => esc_html__('Two Columns', 'king-addons'),
                    'reversed' => esc_html__('Two Columns Reversed', 'king-addons'),
                ],
                'selectors_dictionary' => [
                    'normal' => 'flex-direction: row;',
                    'reversed' => 'flex-direction: row-reverse;',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__grid' => '{{VALUE}}',
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_scroll_story_columns_ratio',
            [
                'label' => esc_html__('Columns Ratio', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => '40-60',
                'options' => [
                    '40-60' => esc_html__('40 / 60', 'king-addons'),
                    '50-50' => esc_html__('50 / 50', 'king-addons'),
                    '60-40' => esc_html__('60 / 40', 'king-addons'),
                ],
                'selectors_dictionary' => [
                    '40-60' => '--kng-scroll-story-left: 40%; --kng-scroll-story-right: 60%;',
                    '50-50' => '--kng-scroll-story-left: 50%; --kng-scroll-story-right: 50%;',
                    '60-40' => '--kng-scroll-story-left: 60%; --kng-scroll-story-right: 40%;',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story' => '{{VALUE}}',
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_scroll_story_vertical_align',
            [
                'label' => esc_html__('Vertical Align', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'top',
                'options' => [
                    'top' => esc_html__('Top', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                ],
                'selectors_dictionary' => [
                    'top' => 'align-items: flex-start;',
                    'center' => 'align-items: center;',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__grid' => '{{VALUE}}',
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_min_height',
            [
                'label' => esc_html__('Min Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 200,
                        'max' => 1200,
                        'step' => 10,
                    ],
                    'vh' => [
                        'min' => 20,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story' => 'min-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_steps_controls(): void
    {
        $this->start_controls_section(
            'kng_scroll_story_steps_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Steps', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'step_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Step Title', 'king-addons'),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'step_subtitle',
            [
                'label' => esc_html__('Subtitle', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'step_description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::WYSIWYG,
                'default' => esc_html__('Add a short description for this step.', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'step_icon',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'skin' => 'inline',
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'step_media_type',
            [
                'label' => esc_html__('Media Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'image',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'image' => esc_html__('Image', 'king-addons'),
                    'video' => esc_html__('Video (Pro)', 'king-addons'),
                    'lottie' => esc_html__('Lottie (Pro)', 'king-addons'),
                    'template' => esc_html__('Template (Pro)', 'king-addons'),
                ],
            ]
        );

        Core::renderUpgradeProNotice($repeater, Controls_Manager::RAW_HTML, 'scroll-story-sections', 'step_media_type', ['video', 'lottie', 'template']);

        $repeater->add_control(
            'step_media_image',
            [
                'label' => esc_html__('Image', 'king-addons'),
                'type' => Controls_Manager::MEDIA,
                'condition' => [
                    'step_media_type' => 'image',
                ],
            ]
        );

        $repeater->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name' => 'step_media_image_size',
                'default' => 'large',
                'condition' => [
                    'step_media_type' => 'image',
                ],
            ]
        );

        $repeater->add_control(
            'step_cta_text',
            [
                'label' => esc_html__('CTA Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'separator' => 'before',
            ]
        );

        $repeater->add_control(
            'step_cta_link',
            [
                'label' => esc_html__('CTA Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://your-link.com', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'step_anchor_id',
            [
                'label' => esc_html__('Anchor ID (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'classes' => $this->get_pro_control_class(),
                'description' => esc_html__('Lowercase letters, numbers, dashes, or underscores only.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_scroll_story_steps',
            [
                'label' => esc_html__('Steps', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => $this->get_default_steps(),
                'title_field' => '{{{ step_title }}}',
            ]
        );

        $this->end_controls_section();
    }

    protected function register_behavior_controls(): void
    {
        $this->start_controls_section(
            'kng_scroll_story_behavior_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Behavior', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_scroll_story_steps_style',
            [
                'label' => esc_html__('Active Step Style', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'minimal',
                'options' => [
                    'minimal' => esc_html__('Minimal', 'king-addons'),
                    'highlighted' => esc_html__('Highlighted', 'king-addons'),
                    'card' => esc_html__('Card List', 'king-addons'),
                ],
                'prefix_class' => 'king-addons-scroll-story--preset-',
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_scroll_story_transition',
            [
                'label' => esc_html__('Content Transition', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'fade',
                'options' => [
                    'fade' => esc_html__('Fade', 'king-addons'),
                    'slide' => esc_html__('Slide', 'king-addons'),
                    'none' => esc_html__('None', 'king-addons'),
                ],
                'prefix_class' => 'king-addons-scroll-story--transition-',
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_scroll_story_transition_duration',
            [
                'label' => esc_html__('Transition Duration (ms)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'step' => 10,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story' => '--kng-scroll-story-transition-duration: {{VALUE}}ms; --kng-scroll-story-transition-duration-slide: {{VALUE}}ms;',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_activation_offset',
            [
                'label' => esc_html__('Activation Offset (%)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 10,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 40,
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_smooth_scroll',
            [
                'label' => esc_html__('Smooth Scroll on Click', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_scroll_story_scroll_duration',
            [
                'label' => esc_html__('Scroll Duration (ms)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 600,
                'min' => 0,
                'step' => 50,
                'condition' => [
                    'kng_scroll_story_smooth_scroll' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_pro_controls(): void
    {
        $this->start_controls_section(
            'kng_scroll_story_sticky_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON_PRO . esc_html__('Sticky Media (Pro)', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_scroll_story_sticky_enable',
            [
                'label' => esc_html__('Enable Sticky Media', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'scroll-story-sections', 'kng_scroll_story_sticky_enable', ['yes']);

        $this->add_control(
            'kng_scroll_story_sticky_offset',
            [
                'label' => esc_html__('Sticky Top Offset (px)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 24,
                'min' => 0,
                'step' => 1,
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_scroll_story_sticky_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_scroll_story_progress_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON_PRO . esc_html__('Progress (Pro)', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_scroll_story_progress_enable',
            [
                'label' => esc_html__('Enable Progress', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'scroll-story-sections', 'kng_scroll_story_progress_enable', ['yes']);

        $this->add_control(
            'kng_scroll_story_progress_style',
            [
                'label' => esc_html__('Progress Style', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'line-dots',
                'options' => [
                    'line-dots' => esc_html__('Line + Dots', 'king-addons'),
                    'bar' => esc_html__('Bar', 'king-addons'),
                    'numeric' => esc_html__('Numeric', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_scroll_story_progress_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_progress_show_count',
            [
                'label' => esc_html__('Show Step Count', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_scroll_story_progress_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_scroll_story_templates_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON_PRO . esc_html__('Templates (Pro)', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_scroll_story_template_content',
            [
                'label' => esc_html__('Content Template ID', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'classes' => $this->get_pro_control_class(),
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'scroll-story-sections', 'kng_scroll_story_template_content', ['']);

        $this->add_control(
            'kng_scroll_story_template_media',
            [
                'label' => esc_html__('Media Template ID', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_scroll_story_template_per_step',
            [
                'label' => esc_html__('Template Per Step', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_scroll_story_lottie_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON_PRO . esc_html__('Lottie (Pro)', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_scroll_story_lottie_source',
            [
                'label' => esc_html__('Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'url' => esc_html__('URL', 'king-addons'),
                    'media' => esc_html__('Media Library', 'king-addons'),
                    'json' => esc_html__('JSON String', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'scroll-story-sections', 'kng_scroll_story_lottie_source', ['url', 'media', 'json']);

        $this->add_control(
            'kng_scroll_story_lottie_loop',
            [
                'label' => esc_html__('Loop', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_scroll_story_lottie_source!' => 'none',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_lottie_speed',
            [
                'label' => esc_html__('Playback Speed', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1,
                'min' => 0.1,
                'step' => 0.1,
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_scroll_story_lottie_source!' => 'none',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_lottie_segment_start',
            [
                'label' => esc_html__('Segment Start Frame', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_scroll_story_lottie_source!' => 'none',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_lottie_segment_end',
            [
                'label' => esc_html__('Segment End Frame', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'classes' => $this->get_pro_control_class(),
                'condition' => [
                    'kng_scroll_story_lottie_source!' => 'none',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_container_controls(): void
    {
        $this->start_controls_section(
            'kng_scroll_story_container_style',
            [
                'label' => esc_html__('Container', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_scroll_story_container_background',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story',
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_container_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_column_gap',
            [
                'label' => esc_html__('Column Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 120,
                    ],
                    'rem' => [
                        'min' => 0,
                        'max' => 6,
                        'step' => 0.25,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_max_width',
            [
                'label' => esc_html__('Max Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 600,
                        'max' => 1800,
                        'step' => 10,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__inner' => 'max-width: {{SIZE}}{{UNIT}}; margin-left: auto; margin-right: auto;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_scroll_story_container_border',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story',
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_container_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_steps_controls(): void
    {
        $this->start_controls_section(
            'kng_scroll_story_steps_style',
            [
                'label' => esc_html__('Steps List', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_steps_gap',
            [
                'label' => esc_html__('Item Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                    ],
                    'rem' => [
                        'min' => 0,
                        'max' => 4,
                        'step' => 0.25,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__steps' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_steps_icon_size',
            [
                'label' => esc_html__('Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 8,
                        'max' => 48,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 3,
                        'step' => 0.1,
                    ],
                    'rem' => [
                        'min' => 0.5,
                        'max' => 3,
                        'step' => 0.1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story' => '--kng-scroll-story-icon-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_steps_icon_gap',
            [
                'label' => esc_html__('Icon Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 3,
                        'step' => 0.1,
                    ],
                    'rem' => [
                        'min' => 0,
                        'max' => 3,
                        'step' => 0.1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story' => '--kng-scroll-story-icon-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_steps_padding',
            [
                'label' => esc_html__('Item Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__step' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_steps_accent_width',
            [
                'label' => esc_html__('Accent Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 12,
                    ],
                    'em' => [
                        'min' => 0.05,
                        'max' => 1,
                        'step' => 0.05,
                    ],
                    'rem' => [
                        'min' => 0.05,
                        'max' => 1,
                        'step' => 0.05,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story' => '--kng-scroll-story-accent-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_steps_accent_offset',
            [
                'label' => esc_html__('Accent Offset', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 2,
                        'step' => 0.1,
                    ],
                    'rem' => [
                        'min' => 0,
                        'max' => 2,
                        'step' => 0.1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story' => '--kng-scroll-story-accent-offset: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_scroll_story_steps_border',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story__step',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_scroll_story_steps_shadow',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story__step',
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_steps_radius',
            [
                'label' => esc_html__('Item Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__step' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_steps_completed_opacity',
            [
                'label' => esc_html__('Completed Opacity', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.05,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story' => '--kng-scroll-story-completed-opacity: {{SIZE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_steps_background',
            [
                'label' => esc_html__('Item Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__step' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_steps_background_hover',
            [
                'label' => esc_html__('Item Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__step:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_steps_background_active',
            [
                'label' => esc_html__('Active Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__step.is-active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_steps_border_active',
            [
                'label' => esc_html__('Active Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__step.is-active' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_steps_accent_color',
            [
                'label' => esc_html__('Accent Line', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__step-accent' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_steps_accent_color_active',
            [
                'label' => esc_html__('Active Accent Line', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__step.is-active .king-addons-scroll-story__step-accent' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_steps_icon_color',
            [
                'label' => esc_html__('Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__step-icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_steps_icon_color_active',
            [
                'label' => esc_html__('Active Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__step.is-active .king-addons-scroll-story__step-icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_steps_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__step-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_steps_subtitle_color',
            [
                'label' => esc_html__('Subtitle Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__step-subtitle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_scroll_story_steps_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story__step-title',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_scroll_story_steps_subtitle_typography',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story__step-subtitle',
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_content_controls(): void
    {
        $this->start_controls_section(
            'kng_scroll_story_panel_style',
            [
                'label' => esc_html__('Content Panel', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_scroll_story_panel_background',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story__panel',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_scroll_story_panel_border',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story__panel',
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_panel_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__panel' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_scroll_story_panel_shadow',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story__panel',
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_panel_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__panel-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_panel_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__panel-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_panel_subtitle_color',
            [
                'label' => esc_html__('Subtitle Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__panel-subtitle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_panel_description_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__panel-description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_scroll_story_panel_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story__panel-title',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_scroll_story_panel_subtitle_typography',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story__panel-subtitle',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_scroll_story_panel_description_typography',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story__panel-description',
            ]
        );

        $this->add_control(
            'kng_scroll_story_panel_cta_heading',
            [
                'label' => esc_html__('CTA', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_scroll_story_panel_cta_typography',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story__panel-cta',
            ]
        );

        $this->add_control(
            'kng_scroll_story_panel_cta_color',
            [
                'label' => esc_html__('CTA Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__panel-cta' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_panel_cta_hover_color',
            [
                'label' => esc_html__('CTA Hover Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__panel-cta:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_panel_cta_background',
            [
                'label' => esc_html__('CTA Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__panel-cta' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_panel_cta_hover_background',
            [
                'label' => esc_html__('CTA Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__panel-cta:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_scroll_story_panel_cta_border',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story__panel-cta',
            ]
        );

        $this->add_control(
            'kng_scroll_story_panel_cta_hover_border_color',
            [
                'label' => esc_html__('CTA Hover Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__panel-cta:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_panel_cta_radius',
            [
                'label' => esc_html__('CTA Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__panel-cta' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_panel_cta_padding',
            [
                'label' => esc_html__('CTA Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__panel-cta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_media_controls(): void
    {
        $this->start_controls_section(
            'kng_scroll_story_media_style',
            [
                'label' => esc_html__('Media', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_media_padding',
            [
                'label' => esc_html__('Media Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__panel-media' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_scroll_story_media_ratio',
            [
                'label' => esc_html__('Aspect Ratio', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto' => esc_html__('Auto', 'king-addons'),
                    '1-1' => esc_html__('1:1', 'king-addons'),
                    '4-3' => esc_html__('4:3', 'king-addons'),
                    '3-4' => esc_html__('3:4', 'king-addons'),
                    '16-9' => esc_html__('16:9', 'king-addons'),
                    '21-9' => esc_html__('21:9', 'king-addons'),
                    'custom' => esc_html__('Custom', 'king-addons'),
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_scroll_story_media_ratio_custom',
            [
                'label' => esc_html__('Custom Ratio', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('16/9', 'king-addons'),
                'description' => esc_html__('Use format like 16/9 or 4:3.', 'king-addons'),
                'condition' => [
                    'kng_scroll_story_media_ratio' => 'custom',
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_scroll_story_media_fit',
            [
                'label' => esc_html__('Object Fit', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'cover',
                'options' => [
                    'cover' => esc_html__('Cover', 'king-addons'),
                    'contain' => esc_html__('Contain', 'king-addons'),
                    'fill' => esc_html__('Fill', 'king-addons'),
                    'scale-down' => esc_html__('Scale Down', 'king-addons'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story' => '--kng-scroll-story-media-fit: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_media_max_height',
            [
                'label' => esc_html__('Max Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 1200,
                        'step' => 10,
                    ],
                    'vh' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story' => '--kng-scroll-story-media-max-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_story_media_radius',
            [
                'label' => esc_html__('Media Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-scroll-story__panel-media img, {{WRAPPER}} .king-addons-scroll-story__panel-media video' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_scroll_story_media_shadow',
                'selector' => '{{WRAPPER}} .king-addons-scroll-story__panel-media img, {{WRAPPER}} .king-addons-scroll-story__panel-media video',
            ]
        );

        $this->end_controls_section();
    }

    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $steps = $settings['kng_scroll_story_steps'] ?? [];

        if (empty($steps)) {
            return;
        }

        $offset = (int) ($settings['kng_scroll_story_activation_offset']['size'] ?? 40);
        $offset = max(10, min(80, $offset));

        $options = [
            'activationOffset' => $offset,
            'smoothScroll' => (($settings['kng_scroll_story_smooth_scroll'] ?? 'yes') === 'yes'),
            'scrollDuration' => (int) ($settings['kng_scroll_story_scroll_duration'] ?? 600),
        ];

        $ratio_map = [
            '1-1' => '1 / 1',
            '4-3' => '4 / 3',
            '3-4' => '3 / 4',
            '16-9' => '16 / 9',
            '21-9' => '21 / 9',
        ];
        $ratio_setting = $settings['kng_scroll_story_media_ratio'] ?? 'auto';
        $ratio_value = '';
        if ('custom' === $ratio_setting) {
            $ratio_value = $this->sanitize_ratio($settings['kng_scroll_story_media_ratio_custom'] ?? '');
        } elseif (isset($ratio_map[$ratio_setting])) {
            $ratio_value = $ratio_map[$ratio_setting];
        }

        $this->add_render_attribute(
            'wrapper',
            [
                'class' => 'king-addons-scroll-story',
                'data-settings' => wp_json_encode($options),
            ]
        );

        if (!empty($ratio_value)) {
            $this->add_render_attribute('wrapper', 'style', '--kng-scroll-story-media-ratio: ' . esc_attr($ratio_value) . ';');
        }

        $widget_id = $this->get_id();

        echo '<div ' . $this->get_render_attribute_string('wrapper') . '>';
        echo '<div class="king-addons-scroll-story__inner">';
        echo '<div class="king-addons-scroll-story__grid">';

        echo '<div class="king-addons-scroll-story__steps" role="list">';
        foreach ($steps as $index => $step) {
            $title = $step['step_title'] ?? '';
            $subtitle = $step['step_subtitle'] ?? '';
            $icon = $step['step_icon'] ?? [];
            $step_id = 'kng-scroll-story-step-' . $widget_id . '-' . $index;
            $panel_id = 'kng-scroll-story-panel-' . $widget_id . '-' . $index;

            $classes = ['king-addons-scroll-story__step'];
            if (0 === $index) {
                $classes[] = 'is-active';
            } else {
                $classes[] = 'is-upcoming';
            }

            echo '<div class="' . esc_attr(implode(' ', $classes)) . '" data-step-index="' . esc_attr((string) $index) . '" role="listitem">';
            echo '<span class="king-addons-scroll-story__step-accent" aria-hidden="true"></span>';
            echo '<button id="' . esc_attr($step_id) . '" class="king-addons-scroll-story__step-button" type="button" aria-controls="' . esc_attr($panel_id) . '"' . (0 === $index ? ' aria-current="step"' : '') . '>';

            if (!empty($icon['value'])) {
                echo '<span class="king-addons-scroll-story__step-icon" aria-hidden="true">';
                Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']);
                echo '</span>';
            }

            echo '<span class="king-addons-scroll-story__step-text">';
            if (!empty($title)) {
                echo '<span class="king-addons-scroll-story__step-title">' . esc_html($title) . '</span>';
            }
            if (!empty($subtitle)) {
                echo '<span class="king-addons-scroll-story__step-subtitle">' . esc_html($subtitle) . '</span>';
            }
            echo '</span>';

            echo '</button>';
            echo '</div>';
        }
        echo '</div>';

        echo '<div class="king-addons-scroll-story__content">';
        foreach ($steps as $index => $step) {
            $title = $step['step_title'] ?? '';
            $subtitle = $step['step_subtitle'] ?? '';
            $description = $step['step_description'] ?? '';
            $media_type = $step['step_media_type'] ?? 'none';
            $media_image = $step['step_media_image'] ?? [];
            $cta_text = $step['step_cta_text'] ?? '';
            $cta_link = $step['step_cta_link'] ?? [];
            $step_id = 'kng-scroll-story-step-' . $widget_id . '-' . $index;
            $panel_id = 'kng-scroll-story-panel-' . $widget_id . '-' . $index;

            $panel_classes = ['king-addons-scroll-story__panel'];
            if (0 === $index) {
                $panel_classes[] = 'is-active';
                $panel_classes[] = 'is-animated';
            }

            $panel_hidden = 0 === $index ? '' : ' hidden';
            echo '<div id="' . esc_attr($panel_id) . '" class="' . esc_attr(implode(' ', $panel_classes)) . '" data-step-index="' . esc_attr((string) $index) . '" role="region" aria-labelledby="' . esc_attr($step_id) . '" aria-hidden="' . (0 === $index ? 'false' : 'true') . '"' . $panel_hidden . '>';
            echo '<div class="king-addons-scroll-story__panel-inner">';

            if (!empty($title)) {
                echo '<div class="king-addons-scroll-story__panel-title">' . esc_html($title) . '</div>';
            }
            if (!empty($subtitle)) {
                echo '<div class="king-addons-scroll-story__panel-subtitle">' . esc_html($subtitle) . '</div>';
            }
            if (!empty($description)) {
                echo '<div class="king-addons-scroll-story__panel-description">' . wp_kses_post($description) . '</div>';
            }

            if (!empty($cta_text) && !empty($cta_link['url'])) {
                $cta_attrs = $this->get_link_attributes($cta_link);
                echo '<a class="king-addons-scroll-story__panel-cta" href="' . esc_url($cta_link['url']) . '"' . $cta_attrs . '>' . esc_html($cta_text) . '</a>';
            }

            echo '</div>';

            if ('image' === $media_type && (!empty($media_image['id']) || !empty($media_image['url']))) {
                echo '<div class="king-addons-scroll-story__panel-media">';
                if (!empty($media_image['id'])) {
                    echo Group_Control_Image_Size::get_attachment_image_html($step, 'step_media_image_size', 'step_media_image'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                } else {
                    $alt = $media_image['alt'] ?? '';
                    echo '<img src="' . esc_url($media_image['url']) . '" alt="' . esc_attr($alt) . '" loading="lazy">';
                }
                echo '</div>';
            }

            echo '</div>';
        }
        echo '</div>';

        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    public function register_pro_notice_controls(): void
    {
        if (!king_addons_can_use_pro()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'scroll-story-sections', [
                'Sticky media panel with offsets',
                'Progress line, dots, and step counters',
                'Lottie per step with playback control',
                'Elementor templates for content and media',
                'Deep linking and anchor sync',
            ]);
        }
    }

    protected function get_default_steps(): array
    {
        return [
            [
                'step_title' => esc_html__('Discover the story', 'king-addons'),
                'step_subtitle' => esc_html__('Step One', 'king-addons'),
                'step_description' => esc_html__('Introduce the core idea with a short, clear description that sets the stage.', 'king-addons'),
                'step_media_type' => 'image',
            ],
            [
                'step_title' => esc_html__('Highlight the shift', 'king-addons'),
                'step_subtitle' => esc_html__('Step Two', 'king-addons'),
                'step_description' => esc_html__('Explain the transformation and the impact you want visitors to remember.', 'king-addons'),
                'step_media_type' => 'image',
            ],
            [
                'step_title' => esc_html__('Drive to action', 'king-addons'),
                'step_subtitle' => esc_html__('Step Three', 'king-addons'),
                'step_description' => esc_html__('Close with a decisive benefit and guide readers to the next step.', 'king-addons'),
                'step_media_type' => 'image',
            ],
        ];
    }

    protected function is_pro_enabled(): bool
    {
        return function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();
    }

    protected function get_pro_control_class(string $extra = ''): string
    {
        if ($this->is_pro_enabled()) {
            return $extra;
        }

        return trim('king-addons-pro-control ' . $extra);
    }

    protected function get_link_attributes(array $link): string
    {
        $attrs = '';
        $rels = [];

        if (!empty($link['is_external'])) {
            $attrs .= ' target="_blank"';
            $rels[] = 'noopener';
            $rels[] = 'noreferrer';
        }

        if (!empty($link['nofollow'])) {
            $rels[] = 'nofollow';
        }

        if (!empty($rels)) {
            $attrs .= ' rel="' . esc_attr(implode(' ', array_unique($rels))) . '"';
        }

        return $attrs;
    }

    protected function sanitize_ratio(string $ratio): string
    {
        $ratio = trim($ratio);
        if ($ratio === '') {
            return '';
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\s*[:\/]\s*(\d+(?:\.\d+)?)$/', $ratio, $matches)) {
            $width = (float) $matches[1];
            $height = (float) $matches[2];
            if ($width > 0 && $height > 0) {
                return $width . ' / ' . $height;
            }
        }

        return '';
    }
}
