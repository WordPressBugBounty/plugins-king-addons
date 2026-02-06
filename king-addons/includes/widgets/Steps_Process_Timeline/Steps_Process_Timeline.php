<?php
/**
 * Steps Process Timeline Widget.
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
    exit; // Exit if accessed directly.
}

class Steps_Process_Timeline extends Widget_Base
{
    public function get_name(): string
    {
        return 'king-addons-steps-process-timeline';
    }

    public function get_title(): string
    {
        return esc_html__('Steps Process Timeline', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-icon-steps-process-timeline';
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-steps-process-timeline-style'];
    }

    public function get_script_depends(): array
    {
        if (!$this->is_pro_enabled()) {
            return [];
        }

        // Elementor instantiates widget types without settings when enqueueing scripts.
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-steps-process-timeline-script'];
    }

    public function get_categories(): array
    {
        return ['king-addons'];
    }

    public function get_keywords(): array
    {
        return ['steps', 'process', 'timeline', 'how it works', 'workflow', 'milestones', 'king-addons'];
    }

        public function get_custom_help_url()
        {
            return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
        }

        protected function register_controls(): void
    {
        $this->register_content_controls();
        $this->register_steps_controls();
        $this->register_interaction_controls();
        $this->register_style_container_controls();
        $this->register_style_heading_controls();
        $this->register_style_steps_controls();
        $this->register_style_marker_controls();
        $this->register_style_line_controls();
        $this->register_style_card_controls();
        $this->register_style_text_controls();
        $this->register_pro_notice_controls();
    }

    protected function register_content_controls(): void
    {
        $this->start_controls_section(
            'kng_steps_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_steps_title',
            [
                'label' => esc_html__('Widget Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('How It Works', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'kng_steps_subtitle',
            [
                'label' => esc_html__('Subtitle', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 3,
                'default' => esc_html__('Clear, simple steps that guide customers from start to finish.', 'king-addons'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'kng_steps_layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'vertical',
                'options' => [
                    'vertical' => esc_html__('Vertical', 'king-addons'),
                    'vertical-compact' => esc_html__('Vertical Compact', 'king-addons'),
                    'horizontal' => esc_html__('Horizontal (Pro)', 'king-addons'),
                ],
                'prefix_class' => 'king-addons-steps-layout-',
                'render_type' => 'template',
                'separator' => 'before',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'steps-process-timeline', 'kng_steps_layout', ['horizontal']);

        $this->add_control(
            'kng_steps_numbering_mode',
            [
                'label' => esc_html__('Step Numbering', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto' => esc_html__('Auto', 'king-addons'),
                    'manual' => esc_html__('Manual', 'king-addons'),
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_steps_number_format',
            [
                'label' => esc_html__('Number Format', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'default',
                'options' => [
                    'default' => esc_html__('1, 2, 3', 'king-addons'),
                    'leading' => esc_html__('01, 02, 03 (Pro)', 'king-addons'),
                ],
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'steps-process-timeline', 'kng_steps_number_format', ['leading']);

        $this->add_control(
            'kng_steps_number_prefix',
            [
                'label' => esc_html__('Number Prefix (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_steps_number_suffix',
            [
                'label' => esc_html__('Number Suffix (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_steps_marker_type',
            [
                'label' => esc_html__('Marker Type', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'number',
                'options' => [
                    'number' => esc_html__('Number', 'king-addons'),
                    'icon' => esc_html__('Icon', 'king-addons'),
                    'number-icon' => esc_html__('Number + Icon (Pro)', 'king-addons'),
                ],
                'prefix_class' => 'king-addons-steps-marker-',
                'render_type' => 'template',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'steps-process-timeline', 'kng_steps_marker_type', ['number-icon']);

        $this->add_control(
            'kng_steps_line_position',
            [
                'label' => esc_html__('Line Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'center' => esc_html__('Center', 'king-addons'),
                ],
                'prefix_class' => 'king-addons-steps-line-',
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_steps_alignment',
            [
                'label' => esc_html__('Content Alignment', 'king-addons'),
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
                'prefix_class' => 'king-addons-steps-align-',
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_steps_card',
            [
                'label' => esc_html__('Step Cards', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'prefix_class' => 'king-addons-steps-card-',
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'kng_steps_breakpoint',
            [
                'label' => esc_html__('Stacking on Tablet', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'tablet',
                'options' => [
                    'tablet' => esc_html__('Switch to vertical on tablet', 'king-addons'),
                    'mobile' => esc_html__('Switch to vertical only on mobile', 'king-addons'),
                ],
                'prefix_class' => 'king-addons-steps-stack-',
                'render_type' => 'template',
            ]
        );

        $this->end_controls_section();
    }

    protected function register_steps_controls(): void
    {
        $this->start_controls_section(
            'kng_steps_list_section',
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
            'step_description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 4,
                'default' => esc_html__('Explain what happens during this step in a clear, concise way.', 'king-addons'),
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
            'step_number',
            [
                'label' => esc_html__('Manual Number', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'condition' => [
                    'kng_steps_numbering_mode' => 'manual',
                ],
            ]
        );

        $repeater->add_control(
            'step_cta_text',
            [
                'label' => esc_html__('CTA Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'separator' => 'before',
            ]
        );

        $repeater->add_control(
            'step_link',
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
                'description' => esc_html__('Use lowercase letters, numbers, dashes, or underscores only.', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'step_state',
            [
                'label' => esc_html__('State (Pro)', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'default',
                'options' => [
                    'default' => esc_html__('Default', 'king-addons'),
                    'highlighted' => esc_html__('Highlighted', 'king-addons'),
                    'disabled' => esc_html__('Disabled', 'king-addons'),
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_steps_list',
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

    protected function register_interaction_controls(): void
    {
        $this->start_controls_section(
            'kng_steps_interaction_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON_PRO . esc_html__('Interactions (Pro)', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_steps_active_on_scroll',
            [
                'label' => esc_html__('Active Step on Scroll (Pro)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'steps-process-timeline', 'kng_steps_active_on_scroll', ['yes']);

        $this->add_control(
            'kng_steps_anchor_linking',
            [
                'label' => esc_html__('Enable Anchor Linking (Pro)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'separator' => 'before',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'steps-process-timeline', 'kng_steps_anchor_linking', ['yes']);

        $this->add_control(
            'kng_steps_anchor_sync',
            [
                'label' => esc_html__('Sync Active Step with Anchors (Pro)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'kng_steps_anchor_linking' => 'yes',
                ],
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'steps-process-timeline', 'kng_steps_anchor_sync', ['yes']);

        $this->add_control(
            'kng_steps_anchor_offset',
            [
                'label' => esc_html__('Anchor Offset (px) (Pro)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'condition' => [
                    'kng_steps_anchor_linking' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_deep_link',
            [
                'label' => esc_html__('Enable Deep Linking (Pro)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'kng_steps_anchor_linking' => 'yes',
                ],
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'steps-process-timeline', 'kng_steps_deep_link', ['yes']);

        $this->add_control(
            'kng_steps_sticky_progress',
            [
                'label' => esc_html__('Enable Sticky Progress (Pro)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'separator' => 'before',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'steps-process-timeline', 'kng_steps_sticky_progress', ['yes']);

        $this->add_control(
            'kng_steps_sticky_offset',
            [
                'label' => esc_html__('Sticky Offset (px) (Pro)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'condition' => [
                    'kng_steps_sticky_progress' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_progress_mode',
            [
                'label' => esc_html__('Progress Calculation (Pro)', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'steps',
                'options' => [
                    'steps' => esc_html__('First to Last Step', 'king-addons'),
                    'widget' => esc_html__('Widget Top to Bottom', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_steps_reveal',
            [
                'label' => esc_html__('Enable Reveal Animations (Pro)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'separator' => 'before',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'steps-process-timeline', 'kng_steps_reveal', ['yes']);

        $this->add_control(
            'kng_steps_reveal_type',
            [
                'label' => esc_html__('Reveal Type (Pro)', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'fade-up',
                'options' => [
                    'fade-up' => esc_html__('Fade Up', 'king-addons'),
                    'fade-in' => esc_html__('Fade In', 'king-addons'),
                    'slide-up' => esc_html__('Slide Up', 'king-addons'),
                    'scale-in' => esc_html__('Scale In', 'king-addons'),
                ],
                'condition' => [
                    'kng_steps_reveal' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_reveal_stagger',
            [
                'label' => esc_html__('Stagger Delay (ms) (Pro)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 120,
                'condition' => [
                    'kng_steps_reveal' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_reveal_trigger',
            [
                'label' => esc_html__('Animation Trigger (Pro)', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'viewport',
                'options' => [
                    'viewport' => esc_html__('On Enter Viewport', 'king-addons'),
                    'active' => esc_html__('On Active Step', 'king-addons'),
                ],
                'condition' => [
                    'kng_steps_reveal' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_horizontal_wrap',
            [
                'label' => esc_html__('Wrap Steps into Rows (Pro)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'condition' => [
                    'kng_steps_layout' => 'horizontal',
                ],
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'steps-process-timeline', 'kng_steps_horizontal_wrap', ['yes']);

        $this->end_controls_section();
    }

    protected function register_style_container_controls(): void
    {
        $this->start_controls_section(
            'kng_steps_style_container',
            [
                'label' => esc_html__('Container', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_steps_container_bg',
                'selector' => '{{WRAPPER}} .king-addons-steps-timeline',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_steps_container_border',
                'selector' => '{{WRAPPER}} .king-addons-steps-timeline',
            ]
        );

        $this->add_control(
            'kng_steps_container_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_steps_container_shadow',
                'selector' => '{{WRAPPER}} .king-addons-steps-timeline',
            ]
        );

        $this->add_responsive_control(
            'kng_steps_container_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_heading_controls(): void
    {
        $this->start_controls_section(
            'kng_steps_style_heading',
            [
                'label' => esc_html__('Heading', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_steps_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-steps__title',
            ]
        );

        $this->add_control(
            'kng_steps_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_steps_subtitle_typography',
                'selector' => '{{WRAPPER}} .king-addons-steps__subtitle',
            ]
        );

        $this->add_control(
            'kng_steps_subtitle_color',
            [
                'label' => esc_html__('Subtitle Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps__subtitle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_steps_heading_gap',
            [
                'label' => esc_html__('Heading Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps__heading' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_steps_controls(): void
    {
        $this->start_controls_section(
            'kng_steps_style_steps',
            [
                'label' => esc_html__('Steps', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_steps_gap',
            [
                'label' => esc_html__('Gap Between Steps', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_steps_marker_gap',
            [
                'label' => esc_html__('Marker to Content Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-marker-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_marker_controls(): void
    {
        $this->start_controls_section(
            'kng_steps_style_marker',
            [
                'label' => esc_html__('Marker', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'kng_steps_marker_size',
            [
                'label' => esc_html__('Marker Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 24, 'max' => 120],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-marker-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_steps_icon_size',
            [
                'label' => esc_html__('Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 10, 'max' => 60],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-icon-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_marker_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-marker-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_marker_color',
            [
                'label' => esc_html__('Text/Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-marker-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_marker_border_color',
            [
                'label' => esc_html__('Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-marker-border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_marker_border_width',
            [
                'label' => esc_html__('Border Width', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 6],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-marker-border-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_marker_active_bg',
            [
                'label' => esc_html__('Active Background (Pro)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-marker-bg-active: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_steps_marker_active_color',
            [
                'label' => esc_html__('Active Text/Icon Color (Pro)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-marker-color-active: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_steps_marker_active_border_color',
            [
                'label' => esc_html__('Active Border Color (Pro)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-marker-border-color-active: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_steps_marker_complete_bg',
            [
                'label' => esc_html__('Completed Background (Pro)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-marker-bg-complete: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_steps_marker_complete_color',
            [
                'label' => esc_html__('Completed Text/Icon Color (Pro)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-marker-color-complete: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_line_controls(): void
    {
        $this->start_controls_section(
            'kng_steps_style_line',
            [
                'label' => esc_html__('Line & Progress', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_steps_line_color',
            [
                'label' => esc_html__('Line Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-line-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_line_thickness',
            [
                'label' => esc_html__('Line Thickness', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 1, 'max' => 10],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-line-thickness: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_line_style',
            [
                'label' => esc_html__('Line Style', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'solid',
                'options' => [
                    'solid' => esc_html__('Solid', 'king-addons'),
                    'dashed' => esc_html__('Dashed', 'king-addons'),
                    'gradient' => esc_html__('Gradient (Pro)', 'king-addons'),
                ],
                'prefix_class' => 'king-addons-steps-line-style-',
                'render_type' => 'template',
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'steps-process-timeline', 'kng_steps_line_style', ['gradient']);

        $this->add_control(
            'kng_steps_progress_color',
            [
                'label' => esc_html__('Progress Fill Color (Pro)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-progress-color: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_steps_progress_radius',
            [
                'label' => esc_html__('Rounded Edges (Pro)', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 20],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-progress-radius: {{SIZE}}{{UNIT}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_card_controls(): void
    {
        $this->start_controls_section(
            'kng_steps_style_card',
            [
                'label' => esc_html__('Card', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_steps_card' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_steps_card_bg',
                'selector' => '{{WRAPPER}} .king-addons-step__content',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_steps_card_border',
                'selector' => '{{WRAPPER}} .king-addons-step__content',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_steps_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-step__content',
            ]
        );

        $this->add_responsive_control(
            'kng_steps_card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-step__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_card_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-step__content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_card_active_border_color',
            [
                'label' => esc_html__('Active Border Color (Pro)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-card-active-border-color: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->add_control(
            'kng_steps_card_active_shadow_color',
            [
                'label' => esc_html__('Active Shadow Color (Pro)', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-steps-timeline' => '--kng-steps-card-active-shadow-color: {{VALUE}};',
                ],
                'classes' => $this->get_pro_control_class(),
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_text_controls(): void
    {
        $this->start_controls_section(
            'kng_steps_style_text',
            [
                'label' => esc_html__('Text', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_steps_step_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-step__title',
            ]
        );

        $this->add_control(
            'kng_steps_step_title_color',
            [
                'label' => esc_html__('Step Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-step__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_steps_step_desc_typography',
                'selector' => '{{WRAPPER}} .king-addons-step__description',
            ]
        );

        $this->add_control(
            'kng_steps_step_desc_color',
            [
                'label' => esc_html__('Step Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-step__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_steps_step_number_typography',
                'selector' => '{{WRAPPER}} .king-addons-step__number',
            ]
        );

        $this->add_control(
            'kng_steps_step_number_color',
            [
                'label' => esc_html__('Step Number Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-step__number' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_steps_cta_typography',
                'selector' => '{{WRAPPER}} .king-addons-step__cta',
            ]
        );

        $this->add_control(
            'kng_steps_cta_color',
            [
                'label' => esc_html__('CTA Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-step__cta' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_cta_hover_color',
            [
                'label' => esc_html__('CTA Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-step__cta:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_cta_bg',
            [
                'label' => esc_html__('CTA Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-step__cta' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_cta_hover_bg',
            [
                'label' => esc_html__('CTA Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-step__cta:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_steps_cta_border',
                'selector' => '{{WRAPPER}} .king-addons-step__cta',
            ]
        );

        $this->add_control(
            'kng_steps_cta_hover_border_color',
            [
                'label' => esc_html__('CTA Hover Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-step__cta:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_cta_radius',
            [
                'label' => esc_html__('CTA Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-step__cta' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_steps_cta_padding',
            [
                'label' => esc_html__('CTA Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-step__cta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_pro_notice_controls(): void
    {
        Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'steps-process-timeline', [
            'Horizontal timeline layout with progress line',
            'Active step sync with scroll and anchors',
            'Sticky progress bar with offset control',
            'Reveal animations with stagger and triggers',
            'Advanced numbering formats and prefixes',
        ]);
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $steps = $settings['kng_steps_list'] ?? [];

        if (empty($steps)) {
            return;
        }

        $is_pro = $this->is_pro_enabled();
        $layout = $settings['kng_steps_layout'] ?? 'vertical';
        if (!$is_pro && 'horizontal' === $layout) {
            $layout = 'vertical';
        }

        $marker_type = $settings['kng_steps_marker_type'] ?? 'number';
        if (!$is_pro && 'number-icon' === $marker_type) {
            $marker_type = 'number';
        }

        $use_templates = false;

        $wrapper_classes = [
            'king-addons-steps-timeline',
            'king-addons-steps-layout-' . $layout,
            'king-addons-steps-marker-' . $marker_type,
        ];

        if ($is_pro) {
            $wrapper_classes[] = 'king-addons-steps--pro';
            $this->add_render_attribute('_wrapper', 'class', 'king-addons-steps--pro');
        }

        if (!empty($settings['kng_steps_line_position'])) {
            $wrapper_classes[] = 'king-addons-steps-line-' . $settings['kng_steps_line_position'];
        }

        if (!empty($settings['kng_steps_card']) && 'yes' === $settings['kng_steps_card']) {
            $wrapper_classes[] = 'king-addons-steps--card';
        }

        if ($is_pro && !empty($settings['kng_steps_sticky_progress']) && 'yes' === $settings['kng_steps_sticky_progress']) {
            $wrapper_classes[] = 'king-addons-steps--sticky';
        }

        if ($is_pro && 'horizontal' === $layout && !empty($settings['kng_steps_horizontal_wrap']) && 'yes' === $settings['kng_steps_horizontal_wrap']) {
            $wrapper_classes[] = 'king-addons-steps--wrap';
        }

        $anchor_linking_enabled = $is_pro && !empty($settings['kng_steps_anchor_linking']) && 'yes' === $settings['kng_steps_anchor_linking'];

        $options = [
            'layout' => $layout,
            'activeOnScroll' => $is_pro && !empty($settings['kng_steps_active_on_scroll']) && 'yes' === $settings['kng_steps_active_on_scroll'],
            'anchorLinking' => $anchor_linking_enabled,
            'anchorSync' => $is_pro && !empty($settings['kng_steps_anchor_sync']) && 'yes' === $settings['kng_steps_anchor_sync'],
            'anchorOffset' => $is_pro ? (int) ($settings['kng_steps_anchor_offset'] ?? 0) : 0,
            'deepLink' => $is_pro && !empty($settings['kng_steps_deep_link']) && 'yes' === $settings['kng_steps_deep_link'],
            'sticky' => $is_pro && !empty($settings['kng_steps_sticky_progress']) && 'yes' === $settings['kng_steps_sticky_progress'],
            'stickyOffset' => $is_pro ? (int) ($settings['kng_steps_sticky_offset'] ?? 0) : 0,
            'progressMode' => $is_pro ? ($settings['kng_steps_progress_mode'] ?? 'steps') : 'steps',
            'reveal' => $is_pro && !empty($settings['kng_steps_reveal']) && 'yes' === $settings['kng_steps_reveal'],
            'revealType' => $settings['kng_steps_reveal_type'] ?? 'fade-up',
            'revealStagger' => $is_pro ? (int) ($settings['kng_steps_reveal_stagger'] ?? 0) : 0,
            'revealTrigger' => $settings['kng_steps_reveal_trigger'] ?? 'viewport',
            'wrap' => $is_pro && 'horizontal' === $layout && !empty($settings['kng_steps_horizontal_wrap']) && 'yes' === $settings['kng_steps_horizontal_wrap'],
        ];

        $style_vars = [];
        if ($is_pro && !empty($settings['kng_steps_sticky_progress']) && 'yes' === $settings['kng_steps_sticky_progress']) {
            $style_vars[] = '--kng-steps-sticky-offset:' . (int) ($settings['kng_steps_sticky_offset'] ?? 0) . 'px';
        }
        if ('horizontal' === $layout) {
            $style_vars[] = '--kng-steps-wrap:' . (($is_pro && !empty($settings['kng_steps_horizontal_wrap']) && 'yes' === $settings['kng_steps_horizontal_wrap']) ? 'wrap' : 'nowrap');
        }

        $this->add_render_attribute('steps_wrapper', [
            'class' => $wrapper_classes,
            'data-options' => wp_json_encode($options),
        ]);

        if (!empty($style_vars)) {
            $this->add_render_attribute('steps_wrapper', 'style', implode(';', $style_vars) . ';');
        }

        if ($is_pro && $this->should_enqueue_script($settings)) {
            wp_enqueue_script(KING_ADDONS_ASSETS_UNIQUE_KEY . '-steps-process-timeline-script');
        }

        echo '<div ' . $this->get_render_attribute_string('steps_wrapper') . '>';

        $this->render_heading($settings);

        echo '<div class="king-addons-steps__track">';
        echo '<div class="king-addons-steps__progress-wrap">';
        echo '<span class="king-addons-steps__line"><span class="king-addons-steps__progress"></span></span>';
        echo '</div>';
        echo '<ol class="king-addons-steps__list" role="list">';

        $active_index = $this->get_initial_active_index($steps, $is_pro);

        foreach ($steps as $index => $step) {
            $this->render_step($settings, $step, $index, $active_index, $is_pro, $marker_type, $use_templates, $anchor_linking_enabled);
        }

        echo '</ol>';
        echo '</div>';
        echo '</div>';
    }

    protected function render_heading(array $settings): void
    {
        $title = trim((string) ($settings['kng_steps_title'] ?? ''));
        $subtitle = trim((string) ($settings['kng_steps_subtitle'] ?? ''));

        if ('' === $title && '' === $subtitle) {
            return;
        }

        echo '<div class="king-addons-steps__heading">';

        if ('' !== $title) {
            echo '<h3 class="king-addons-steps__title">' . esc_html($title) . '</h3>';
        }

        if ('' !== $subtitle) {
            echo '<div class="king-addons-steps__subtitle">' . esc_html($subtitle) . '</div>';
        }

        echo '</div>';
    }

    protected function render_step(array $settings, array $step, int $index, int $active_index, bool $is_pro, string $marker_type, bool $use_templates, bool $anchor_linking_enabled): void
    {
        $title = trim((string) ($step['step_title'] ?? ''));
        $description = trim((string) ($step['step_description'] ?? ''));
        $cta_label = trim((string) ($step['step_cta_text'] ?? ''));
        $link = $step['step_link'] ?? [];

        $step_state = $is_pro ? ($step['step_state'] ?? 'default') : 'default';
        $anchor_id = '';
        if ($is_pro && !empty($step['step_anchor_id'])) {
            $anchor_id = $this->sanitize_anchor_id((string) $step['step_anchor_id']);
        }

        $number_value = $index + 1;
        if (($settings['kng_steps_numbering_mode'] ?? 'auto') === 'manual' && !empty($step['step_number'])) {
            $number_value = sanitize_text_field((string) $step['step_number']);
        }

        $number_text = $this->format_step_number($number_value, $settings, $is_pro);
        $step_classes = ['king-addons-step'];

        if ($index === $active_index) {
            $step_classes[] = 'is-active';
        }

        if ('highlighted' === $step_state) {
            $step_classes[] = 'is-highlighted';
        }

        if ('disabled' === $step_state) {
            $step_classes[] = 'is-disabled';
        }

        $attributes = [
            'class' => implode(' ', $step_classes),
            'data-step-index' => (string) $index,
        ];

        if ('' !== $anchor_id) {
            $attributes['data-anchor'] = $anchor_id;
        }

        if ('disabled' === $step_state) {
            $attributes['aria-disabled'] = 'true';
        }

        if ($anchor_linking_enabled && '' !== $anchor_id && 'disabled' !== $step_state) {
            $attributes['tabindex'] = '0';
            $attributes['role'] = 'button';
        }

        $attr_output = [];
        foreach ($attributes as $key => $value) {
            $attr_output[] = esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        echo '<li ' . implode(' ', $attr_output) . '>';
        $has_icon = !empty($step['step_icon']['value']);
        $show_number = 'icon' !== $marker_type || !$has_icon;

        echo '<div class="king-addons-step__marker">';

        if ($show_number && '' !== (string) $number_text) {
            echo '<span class="king-addons-step__number">' . esc_html($number_text) . '</span>';
        }

        if ('number' !== $marker_type && $has_icon) {
            echo '<span class="king-addons-step__icon">';
            Icons_Manager::render_icon($step['step_icon'], ['aria-hidden' => 'true']);
            echo '</span>';
        }

        echo '</div>';
        echo '<div class="king-addons-step__content">';

        if ('' !== $title) {
            echo '<h4 class="king-addons-step__title">' . esc_html($title) . '</h4>';
        }

        if ('' !== $description) {
            echo '<div class="king-addons-step__description">' . wp_kses_post($description) . '</div>';
        }

        if ($use_templates && !empty($step['step_template_id'])) {
            $template_id = absint($step['step_template_id']);
            if ($template_id) {
                echo '<div class="king-addons-step__template">' . Plugin::instance()->frontend->get_builder_content_for_display($template_id) . '</div>';
            }
        }

        if (!empty($link['url']) && '' !== $cta_label && 'disabled' !== $step_state) {
            $link_attrs = $this->get_link_attributes($link, 'king-addons-step__cta');
            if ('' !== $link_attrs) {
                echo '<a ' . $link_attrs . '>' . esc_html($cta_label) . '</a>';
            }
        }

        echo '</div>';
        echo '</li>';
    }

    protected function format_step_number($number_value, array $settings, bool $is_pro): string
    {
        if ('' === (string) $number_value) {
            return '';
        }

        $number_text = (string) $number_value;

        if ($is_pro && 'leading' === ($settings['kng_steps_number_format'] ?? 'default') && is_numeric($number_value)) {
            $number_text = str_pad((string) $number_value, 2, '0', STR_PAD_LEFT);
        }

        if ($is_pro) {
            $prefix = (string) ($settings['kng_steps_number_prefix'] ?? '');
            $suffix = (string) ($settings['kng_steps_number_suffix'] ?? '');
            $number_text = $prefix . $number_text . $suffix;
        }

        return $number_text;
    }

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

    protected function sanitize_anchor_id(string $raw): string
    {
        $slug = strtolower($raw);
        $slug = preg_replace('/[^a-z0-9\-_]+/', '-', $slug);
        $slug = trim((string) $slug, '-_');
        return $slug;
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
            $settings['kng_steps_active_on_scroll'] ?? '',
            $settings['kng_steps_anchor_linking'] ?? '',
            $settings['kng_steps_anchor_sync'] ?? '',
            $settings['kng_steps_sticky_progress'] ?? '',
            $settings['kng_steps_reveal'] ?? '',
        ];

        foreach ($flags as $flag) {
            if ('yes' === $flag) {
                return true;
            }
        }

        return false;
    }

    protected function get_initial_active_index(array $steps, bool $is_pro): int
    {
        if (!$is_pro) {
            return 0;
        }

        foreach ($steps as $index => $step) {
            if (($step['step_state'] ?? 'default') !== 'disabled') {
                return $index;
            }
        }

        return 0;
    }

    protected function get_default_steps(): array
    {
        return [
            [
                'step_title' => esc_html__('Share your goals', 'king-addons'),
                'step_description' => esc_html__('Tell us what you want to achieve and the timeline you have in mind.', 'king-addons'),
            ],
            [
                'step_title' => esc_html__('We map the process', 'king-addons'),
                'step_description' => esc_html__('Our team creates a clear plan with milestones and deliverables.', 'king-addons'),
            ],
            [
                'step_title' => esc_html__('Launch and iterate', 'king-addons'),
                'step_description' => esc_html__('We ship, measure results, and improve with every iteration.', 'king-addons'),
            ],
        ];
    }

    protected function get_pro_control_class(string $extra = ''): string
    {
        if ($this->is_pro_enabled()) {
            return $extra;
        }

        return trim('king-addons-pro-control ' . $extra);
    }
}
