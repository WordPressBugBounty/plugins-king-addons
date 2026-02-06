<?php
/**
 * Interactive Steps with Progress Widget.
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

class Interactive_Steps_Progress extends Widget_Base
{
    public function get_name(): string
    {
        return 'king-addons-interactive-steps-progress';
    }

    public function get_title(): string
    {
        return esc_html__('Interactive Steps with Progress', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-steps';
    }

    public function get_categories(): array
    {
        return ['king-addons'];
    }

    public function get_keywords(): array
    {
        return ['steps', 'progress', 'how it works', 'process', 'accordion', 'king-addons'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-interactive-steps-progress-style'];
    }

    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-interactive-steps-progress-script'];
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
        $this->register_progress_controls();
        $this->register_style_container_controls();
        $this->register_style_step_controls();
        $this->register_style_marker_controls();
        $this->register_style_typography_controls();
        $this->register_style_cta_controls();
        $this->register_pro_notice_controls();
    }

    protected function register_layout_controls(): void
    {
        $this->start_controls_section(
            'kng_isp_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_steps_orientation',
            [
                'label' => esc_html__('Orientation', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'vertical',
                'options' => [
                    'vertical' => esc_html__('Vertical', 'king-addons'),
                    'horizontal' => esc_html__('Horizontal (Pro)', 'king-addons'),
                ],
            ]
        );

        Core::renderUpgradeProNotice($this, Controls_Manager::RAW_HTML, 'interactive-steps-progress', 'kng_steps_orientation', ['horizontal']);

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
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_steps_gap',
            [
                'label' => esc_html__('Step Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 80],
                    'em' => ['min' => 0, 'max' => 6],
                    'rem' => ['min' => 0, 'max' => 6],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_steps_content_padding',
            [
                'label' => esc_html__('Content Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-content-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_connector',
            [
                'label' => esc_html__('Connector Line', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'show',
                'options' => [
                    'show' => esc_html__('Show', 'king-addons'),
                    'hide' => esc_html__('Hide', 'king-addons'),
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
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-line-thickness: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_line_offset',
            [
                'label' => esc_html__('Line Offset', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-line-offset: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_steps_controls(): void
    {
        $this->start_controls_section(
            'kng_isp_steps_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Steps', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

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
                'label' => esc_html__('Short Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 3,
                'default' => esc_html__('Explain the step in a single, clear sentence.', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'step_details',
            [
                'label' => esc_html__('Details', 'king-addons'),
                'type' => Controls_Manager::WYSIWYG,
                'default' => esc_html__('Add the expanded details for this step here.', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'step_points',
            [
                'label' => esc_html__('Details List (Optional)', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 4,
                'default' => '',
                'description' => esc_html__('Add one list item per line.', 'king-addons'),
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
            'step_icon_position',
            [
                'label' => esc_html__('Icon Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'top' => esc_html__('Top', 'king-addons'),
                ],
            ]
        );

        $repeater->add_control(
            'step_number',
            [
                'label' => esc_html__('Custom Number', 'king-addons'),
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
                'description' => esc_html__('Use lowercase letters, numbers, dashes, or underscores only.', 'king-addons'),
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

    protected function register_behavior_controls(): void
    {
        $this->start_controls_section(
            'kng_isp_behavior_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Behavior', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_steps_expand_behavior',
            [
                'label' => esc_html__('Expand Behavior', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'click',
                'options' => [
                    'none' => esc_html__('Always Open', 'king-addons'),
                    'click' => esc_html__('Click to Expand', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_steps_default_open',
            [
                'label' => esc_html__('Default Open', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'first',
                'options' => [
                    'first' => esc_html__('First Step', 'king-addons'),
                    'closed' => esc_html__('All Closed', 'king-addons'),
                ],
                'condition' => [
                    'kng_steps_expand_behavior' => 'click',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_single_open',
            [
                'label' => esc_html__('Single Open', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_steps_expand_behavior' => 'click',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_scroll_to_step',
            [
                'label' => esc_html__('Scroll to Step on Click', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->end_controls_section();
    }

    protected function register_progress_controls(): void
    {
        $this->start_controls_section(
            'kng_isp_progress_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Progress', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_steps_progress_style',
            [
                'label' => esc_html__('Progress Style', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'dots',
                'options' => [
                    'dots' => esc_html__('Dots + Line', 'king-addons'),
                    'numbers' => esc_html__('Numbered Badges', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_steps_show_completed',
            [
                'label' => esc_html__('Show Completed State', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'yes',
                'options' => [
                    'yes' => esc_html__('Yes', 'king-addons'),
                    'no' => esc_html__('No', 'king-addons'),
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_container_controls(): void
    {
        $this->start_controls_section(
            'kng_isp_style_container',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Container', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_isp_container_bg',
                'selector' => '{{WRAPPER}} .king-addons-interactive-steps',
            ]
        );

        $this->add_responsive_control(
            'kng_isp_container_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_isp_container_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_isp_container_shadow',
                'selector' => '{{WRAPPER}} .king-addons-interactive-steps',
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_step_controls(): void
    {
        $this->start_controls_section(
            'kng_isp_style_steps',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Step Item', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_steps_card',
            [
                'label' => esc_html__('Card Mode', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_steps_item_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step__body' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_steps_item_border',
                'selector' => '{{WRAPPER}} .king-addons-isp-step__body',
            ]
        );

        $this->add_responsive_control(
            'kng_steps_item_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step__body' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_steps_item_shadow',
                'selector' => '{{WRAPPER}} .king-addons-isp-step__body',
            ]
        );

        $this->add_control(
            'kng_steps_item_bg_hover',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step:hover .king-addons-isp-step__body' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_item_border_hover',
            [
                'label' => esc_html__('Hover Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step:hover .king-addons-isp-step__body' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_steps_item_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-isp-step:hover .king-addons-isp-step__body',
            ]
        );

        $this->add_control(
            'kng_steps_item_bg_active',
            [
                'label' => esc_html__('Active Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step.is-active .king-addons-isp-step__body' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_item_border_active',
            [
                'label' => esc_html__('Active Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step.is-active .king-addons-isp-step__body' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_steps_item_shadow_active',
                'selector' => '{{WRAPPER}} .king-addons-isp-step.is-active .king-addons-isp-step__body',
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_marker_controls(): void
    {
        $this->start_controls_section(
            'kng_isp_style_marker',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Marker & Progress', 'king-addons'),
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
                    'px' => ['min' => 16, 'max' => 80],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-marker-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_steps_dot_size',
            [
                'label' => esc_html__('Dot Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 6, 'max' => 30],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-dot-size: {{SIZE}}{{UNIT}};',
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
                    'px' => ['min' => 10, 'max' => 48],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-icon-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_steps_icon_spacing',
            [
                'label' => esc_html__('Icon Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-icon-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_steps_number_typography',
                'selector' => '{{WRAPPER}} .king-addons-isp-step__number',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_steps_label_typography',
                'selector' => '{{WRAPPER}} .king-addons-isp-step__label',
            ]
        );

        $this->add_control(
            'kng_steps_label_color',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-label-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_marker_color',
            [
                'label' => esc_html__('Marker Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-marker-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_marker_bg',
            [
                'label' => esc_html__('Marker Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-marker-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_marker_border',
            [
                'label' => esc_html__('Marker Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-marker-border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_marker_color_active',
            [
                'label' => esc_html__('Active Marker Text', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-marker-color-active: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_marker_bg_active',
            [
                'label' => esc_html__('Active Marker Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-marker-bg-active: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_marker_border_active',
            [
                'label' => esc_html__('Active Marker Border', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-marker-border-active: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_marker_color_completed',
            [
                'label' => esc_html__('Completed Marker Text', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-marker-color-completed: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_marker_bg_completed',
            [
                'label' => esc_html__('Completed Marker Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-marker-bg-completed: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_marker_border_completed',
            [
                'label' => esc_html__('Completed Marker Border', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-marker-border-completed: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_icon_color',
            [
                'label' => esc_html__('Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-icon-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_icon_color_active',
            [
                'label' => esc_html__('Active Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-icon-color-active: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_icon_color_completed',
            [
                'label' => esc_html__('Completed Icon Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-icon-color-completed: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_line_color',
            [
                'label' => esc_html__('Line Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-line-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_line_color_active',
            [
                'label' => esc_html__('Active Line Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-interactive-steps' => '--kng-isp-line-active-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_typography_controls(): void
    {
        $this->start_controls_section(
            'kng_isp_style_typography',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Typography', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_steps_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-isp-step__title',
            ]
        );

        $this->add_control(
            'kng_steps_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_steps_description_typography',
                'selector' => '{{WRAPPER}} .king-addons-isp-step__description',
            ]
        );

        $this->add_control(
            'kng_steps_description_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_steps_details_typography',
                'selector' => '{{WRAPPER}} .king-addons-isp-step__details',
            ]
        );

        $this->add_control(
            'kng_steps_details_color',
            [
                'label' => esc_html__('Details Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step__details' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_title_color_active',
            [
                'label' => esc_html__('Active Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step.is-active .king-addons-isp-step__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_description_color_active',
            [
                'label' => esc_html__('Active Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step.is-active .king-addons-isp-step__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_style_cta_controls(): void
    {
        $this->start_controls_section(
            'kng_isp_style_cta',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('CTA', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_steps_cta_typography',
                'selector' => '{{WRAPPER}} .king-addons-isp-step__cta',
            ]
        );

        $this->add_control(
            'kng_steps_cta_color',
            [
                'label' => esc_html__('CTA Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step__cta' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_cta_hover_color',
            [
                'label' => esc_html__('CTA Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step__cta:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_cta_bg',
            [
                'label' => esc_html__('CTA Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step__cta' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_steps_cta_hover_bg',
            [
                'label' => esc_html__('CTA Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step__cta:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_steps_cta_border',
                'selector' => '{{WRAPPER}} .king-addons-isp-step__cta',
            ]
        );

        $this->add_control(
            'kng_steps_cta_hover_border_color',
            [
                'label' => esc_html__('CTA Hover Border Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step__cta:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_steps_cta_radius',
            [
                'label' => esc_html__('CTA Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-isp-step__cta' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-isp-step__cta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'interactive-steps-progress', [
                'Horizontal layout with wrap and sticky progress',
                'Deep links with hash sync and anchor navigation',
                'Scroll reveal and active-on-scroll interactions',
                'Advanced animations with reduced motion options',
            ]);
        }
    }

    protected function should_render_progress_header(array $settings): bool
    {
        if (!$this->is_pro_enabled()) {
            return false;
        }

        $show_count = ('yes' === ($settings['kng_steps_show_step_count'] ?? ''));
        $show_percent = ('yes' === ($settings['kng_steps_show_percent'] ?? ''));
        $sticky = ('yes' === ($settings['kng_steps_sticky_progress'] ?? ''));

        return $show_count || $show_percent || $sticky;
    }

    protected function render_progress_header(array $settings, int $total_steps): void
    {
        $show_count = ('yes' === ($settings['kng_steps_show_step_count'] ?? ''));
        $show_percent = ('yes' === ($settings['kng_steps_show_percent'] ?? ''));

        $sticky_enabled = ('yes' === ($settings['kng_steps_sticky_progress'] ?? ''));
        $sticky_type = $this->sanitize_sticky_type((string) ($settings['kng_steps_sticky_type'] ?? 'bar'));
        $markers_clickable = ('yes' === ($settings['kng_steps_progress_clickable'] ?? ''));
        $sticky_steps_interactive = $sticky_enabled && ('header' === $sticky_type) && $markers_clickable;

        $count_text = sprintf(
            esc_html__('Step %1$s of %2$s', 'king-addons'),
            1,
            max($total_steps, 1)
        );

        echo '<div class="king-addons-isp__sticky">';
        echo '<div class="king-addons-isp__progress">';
        echo '<div class="king-addons-isp-progress__bar" aria-hidden="true">';
        echo '<span class="king-addons-isp-progress__fill" style="width: 0%;"></span>';
        echo '</div>';
        if ($sticky_steps_interactive) {
            echo '<div class="king-addons-isp-progress__steps" role="navigation" aria-label="' . esc_attr__('Steps', 'king-addons') . '"></div>';
        } else {
            echo '<div class="king-addons-isp-progress__steps" aria-hidden="true"></div>';
        }

        if ($show_count || $show_percent) {
            echo '<div class="king-addons-isp-progress__meta">';

            if ($show_count) {
                echo '<span class="king-addons-isp-progress__count" aria-live="polite">' . esc_html($count_text) . '</span>';
            }

            if ($show_percent) {
                echo '<span class="king-addons-isp-progress__percent">0%</span>';
            }

            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $steps = $settings['kng_steps_list'] ?? [];

        if (empty($steps)) {
            if ($this->is_editor()) {
                echo '<div class="king-addons-isp-empty">' . esc_html__('Add steps to get started.', 'king-addons') . '</div>';
            }
            return;
        }

        $wrapper_classes = $this->get_wrapper_classes($settings);
        $data_settings = $this->get_data_settings($settings);
        $total_steps = count($steps);
        $unique_anchors = $this->build_unique_step_anchors($settings, $steps);

        $this->add_render_attribute('kng-isp-wrapper', [
            'class' => implode(' ', $wrapper_classes),
            'data-settings' => wp_json_encode($data_settings),
        ]);

        echo '<div ' . $this->get_render_attribute_string('kng-isp-wrapper') . '>';

        if ($this->should_render_progress_header($settings)) {
            $this->render_progress_header($settings, $total_steps);
        }

        echo '<ul class="king-addons-isp__list">';

        foreach ($steps as $index => $step) {
            if (!empty($unique_anchors)) {
                $step['_kng_isp_anchor'] = $unique_anchors[$index] ?? '';
            }
            $this->render_step($settings, $step, $index);
        }

        echo '</ul>';
        echo '</div>';
    }

    protected function render_step(array $settings, array $step, int $index): void
    {
        $title = trim((string) ($step['step_title'] ?? ''));
        $description = trim((string) ($step['step_description'] ?? ''));
        $details = (string) ($step['step_details'] ?? '');
        $points_raw = (string) ($step['step_points'] ?? '');
        $cta_label = trim((string) ($step['step_cta_text'] ?? ''));
        $cta_link = $step['step_cta_link'] ?? [];

        $points = $this->parse_step_points($points_raw);

        $number_text = $this->get_step_number($settings, $step, $index);
        $label_text = '' !== $number_text
            ? sprintf(esc_html__('Step %s', 'king-addons'), $number_text)
            : '';

        $expand_behavior = $this->sanitize_expand_behavior($settings['kng_steps_expand_behavior'] ?? 'click');
        $default_open = $this->sanitize_default_open($settings['kng_steps_default_open'] ?? 'first');
        $has_panel = ('' !== trim($details)) || !empty($points) || (!empty($cta_link['url']) && '' !== $cta_label);
        $is_open = ('none' === $expand_behavior) || ('click' === $expand_behavior && 'first' === $default_open && 0 === $index && $has_panel);
        $anchor_id = (string) ($step['_kng_isp_anchor'] ?? '');
        if ('' === $anchor_id) {
            $anchor_id = $this->get_step_anchor($settings, $step, $index);
        }

        $step_classes = ['king-addons-isp-step'];
        if (0 === $index) {
            $step_classes[] = 'is-active';
        } else {
            $step_classes[] = 'is-upcoming';
        }

        if ($is_open) {
            $step_classes[] = 'is-open';
        }

        $attributes = [
            'class' => implode(' ', $step_classes),
            'data-step-index' => (string) $index,
        ];

        if ('' !== $anchor_id) {
            $attributes['id'] = $anchor_id;
            $attributes['data-anchor'] = $anchor_id;
        }

        if (0 === $index) {
            $attributes['aria-current'] = 'step';
        }

        $attr_output = [];
        foreach ($attributes as $key => $value) {
            $attr_output[] = esc_attr($key) . '="' . esc_attr((string) $value) . '"';
        }

        $panel_id = '';
        $button_id = $this->get_button_id($index);
        if ($has_panel) {
            $panel_id = $this->get_panel_id($index);
        }

        $icon_position = $this->sanitize_icon_position((string) ($step['step_icon_position'] ?? 'left'));
        $has_icon = !empty($step['step_icon']['value']);

        echo '<li ' . implode(' ', $attr_output) . '>';
        echo '<span class="king-addons-isp-step__marker" aria-hidden="true">';

        if ('' !== $number_text) {
            echo '<span class="king-addons-isp-step__number">' . esc_html($number_text) . '</span>';
        }

        echo '</span>';
        echo '<div class="king-addons-isp-step__body">';
        echo '<button class="king-addons-isp-step__button" type="button" id="' . esc_attr($button_id) . '"';

        if ($has_panel) {
            echo ' aria-expanded="' . ($is_open ? 'true' : 'false') . '" aria-controls="' . esc_attr($panel_id) . '"';
        }

        echo '>';
        echo '<span class="king-addons-isp-step__heading king-addons-isp-icon-' . esc_attr($icon_position) . '">';

        if ($has_icon) {
            echo '<span class="king-addons-isp-step__icon">';
            Icons_Manager::render_icon($step['step_icon'], ['aria-hidden' => 'true']);
            echo '</span>';
        }

        echo '<span class="king-addons-isp-step__text">';

        if ('' !== $label_text) {
            echo '<span class="king-addons-isp-step__label">' . esc_html($label_text) . '</span>';
        }

        if ('' !== $title) {
            echo '<span class="king-addons-isp-step__title">' . esc_html($title) . '</span>';
        }

        if ('' !== $description) {
            echo '<span class="king-addons-isp-step__description">' . wp_kses_post($description) . '</span>';
        }

        echo '</span>';
        echo '</span>';
        echo '</button>';

        if ($has_panel) {
            $panel_hidden = $is_open ? '' : ' hidden="hidden"';
            echo '<div id="' . esc_attr($panel_id) . '" class="king-addons-isp-step__panel" role="region" aria-labelledby="' . esc_attr($button_id) . '" aria-hidden="' . ($is_open ? 'false' : 'true') . '"' . $panel_hidden . '>';

            if ('' !== trim($details)) {
                echo '<div class="king-addons-isp-step__details">' . wp_kses_post($details) . '</div>';
            }

            if (!empty($points)) {
                echo '<ul class="king-addons-isp-step__points">';
                foreach ($points as $point) {
                    echo '<li>' . esc_html($point) . '</li>';
                }
                echo '</ul>';
            }

            if (!empty($cta_link['url']) && '' !== $cta_label) {
                $link_attrs = $this->get_link_attributes($cta_link, 'king-addons-isp-step__cta');
                if ('' !== $link_attrs) {
                    echo '<a ' . $link_attrs . '>' . esc_html($cta_label) . '</a>';
                }
            }

            echo '</div>';
        }

        echo '</div>';
        echo '</li>';
    }

    /**
     * @param string $raw
     * @return string[]
     */
    protected function parse_step_points(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $lines = array_filter(array_map('trim', explode("\n", $raw)), static function ($value) {
            return '' !== $value;
        });

        // Normalize and cap to a reasonable number for performance.
        $lines = array_slice(array_values($lines), 0, 50);

        return array_map('sanitize_text_field', $lines);
    }

    protected function get_step_number(array $settings, array $step, int $index): string
    {
        $mode = $settings['kng_steps_numbering_mode'] ?? 'auto';
        $custom = trim((string) ($step['step_number'] ?? ''));

        if ('manual' === $mode && '' !== $custom) {
            return sanitize_text_field($custom);
        }

        return (string) ($index + 1);
    }

    protected function get_wrapper_classes(array $settings): array
    {
        $alignment = $this->sanitize_alignment($settings['kng_steps_alignment'] ?? 'left');
        $progress_style = $this->sanitize_progress_style($settings['kng_steps_progress_style'] ?? 'dots');
        $line_display = $this->sanitize_line_display($settings['kng_steps_connector'] ?? 'show');
        $show_completed = $this->sanitize_show_completed($settings['kng_steps_show_completed'] ?? 'yes');
        $expand_behavior = $this->sanitize_expand_behavior($settings['kng_steps_expand_behavior'] ?? 'click');
        $card_mode = ('yes' === ($settings['kng_steps_card'] ?? 'yes')) ? 'yes' : 'no';
        $orientation = $this->get_orientation($settings);
        $classes = [
            'king-addons-interactive-steps',
            'king-addons-isp-layout-' . $orientation,
            'king-addons-isp-align-' . $alignment,
            'king-addons-isp-progress-' . $progress_style,
            'king-addons-isp-line-' . $line_display,
            'king-addons-isp-completed-' . $show_completed,
            'king-addons-isp-expand-' . $expand_behavior,
            'king-addons-isp-card-' . $card_mode,
        ];

        if ($this->is_pro_enabled()) {
            $wrap_mode = $this->sanitize_wrap_mode($settings['kng_steps_horizontal_wrap'] ?? 'wrap');
            $wrap_class = ('scroll' === $wrap_mode) ? 'nowrap' : $wrap_mode;
            $scroll_class = ('scroll' === $wrap_mode) ? 'yes' : 'no';

            $classes[] = 'king-addons-isp--pro';
            $classes[] = 'king-addons-isp-wrap-' . $wrap_class;
            $classes[] = 'king-addons-isp-scroll-' . $scroll_class;
            $classes[] = 'king-addons-isp-equal-' . $this->sanitize_toggle($settings['kng_steps_equal_width'] ?? '');
            $classes[] = 'king-addons-isp-mobile-' . $this->sanitize_mobile_fallback($settings['kng_steps_mobile_fallback'] ?? 'vertical');

            if ('yes' === ($settings['kng_steps_sticky_progress'] ?? '')) {
                $classes[] = 'king-addons-isp--sticky';
                $classes[] = 'king-addons-isp-sticky-' . $this->sanitize_sticky_type($settings['kng_steps_sticky_type'] ?? 'bar');

                if ('yes' === ($settings['kng_steps_sticky_compact_mobile'] ?? '')) {
                    $classes[] = 'king-addons-isp-sticky-compact';
                }
            }

            if ('yes' === ($settings['kng_steps_progress_animate'] ?? '')) {
                $classes[] = 'king-addons-isp-progress-animated';
            }

            if ('yes' === ($settings['kng_steps_active_animation'] ?? '')) {
                $classes[] = 'king-addons-isp-animate-active';
            }

            if ('yes' === ($settings['kng_steps_reveal'] ?? '')) {
                $classes[] = 'king-addons-isp-reveal';
                $classes[] = 'king-addons-isp-reveal-' . $this->sanitize_reveal_type($settings['kng_steps_reveal_type'] ?? 'fade');
            }

            $reduced_motion = $this->sanitize_reduced_motion($settings['kng_steps_reduced_motion'] ?? 'auto');
            if ('full' === $reduced_motion) {
                $classes[] = 'king-addons-isp-reduced-full';
            } elseif ('minimal' === $reduced_motion) {
                $classes[] = 'king-addons-isp-reduced-minimal';
            } elseif ('off' === $reduced_motion) {
                $classes[] = 'king-addons-isp-reduced-off';
            }

            if ('yes' === ($settings['kng_steps_progress_clickable'] ?? '')) {
                $classes[] = 'king-addons-isp-markers-clickable';
            }

            if ($this->should_render_progress_header($settings)) {
                $classes[] = 'king-addons-isp-has-progress';
            }
        } else {
            $classes[] = 'king-addons-isp-markers-clickable';
        }

        return $classes;
    }

    protected function get_data_settings(array $settings): array
    {
        $data = [
            'expandBehavior' => $this->sanitize_expand_behavior($settings['kng_steps_expand_behavior'] ?? 'click'),
            'defaultOpen' => $this->sanitize_default_open($settings['kng_steps_default_open'] ?? 'first'),
            'singleOpen' => ('yes' === ($settings['kng_steps_single_open'] ?? 'yes')) ? 'yes' : 'no',
            'scrollToStep' => ('yes' === ($settings['kng_steps_scroll_to_step'] ?? '')) ? 'yes' : 'no',
        ];

        if ($this->is_pro_enabled()) {
            $data['orientation'] = $this->get_orientation($settings);
            $data['activateOnScroll'] = ('yes' === ($settings['kng_steps_activate_on_scroll'] ?? '')) ? 'yes' : 'no';
            $data['activationOffset'] = $this->sanitize_activation_offset($settings['kng_steps_activation_offset'] ?? 40);
            $data['anchorLinking'] = ('yes' === ($settings['kng_steps_enable_anchors'] ?? '')) ? 'yes' : 'no';
            $data['updateHash'] = ('yes' === ($settings['kng_steps_update_hash'] ?? '')) ? 'yes' : 'no';
            $data['scrollToHash'] = ('yes' === ($settings['kng_steps_scroll_to_hash'] ?? '')) ? 'yes' : 'no';
            $data['keyboardNav'] = ('yes' === ($settings['kng_steps_keyboard_nav'] ?? '')) ? 'yes' : 'no';
            $data['reveal'] = ('yes' === ($settings['kng_steps_reveal'] ?? '')) ? 'yes' : 'no';
            $data['revealType'] = $this->sanitize_reveal_type($settings['kng_steps_reveal_type'] ?? 'fade');
            $data['revealStagger'] = absint($settings['kng_steps_reveal_stagger'] ?? 0);
            $data['activeAnimation'] = ('yes' === ($settings['kng_steps_active_animation'] ?? '')) ? 'yes' : 'no';
            $data['activeDuration'] = absint($settings['kng_steps_active_duration'] ?? 250);
            $data['activeEasing'] = $this->sanitize_easing($settings['kng_steps_active_easing'] ?? 'ease');
            $data['progressAnimate'] = ('yes' === ($settings['kng_steps_progress_animate'] ?? '')) ? 'yes' : 'no';
            $data['progressDuration'] = absint($settings['kng_steps_progress_duration'] ?? 300);
            $data['progressEasing'] = $this->sanitize_easing($settings['kng_steps_progress_easing'] ?? 'ease');
            $data['showStepCount'] = ('yes' === ($settings['kng_steps_show_step_count'] ?? '')) ? 'yes' : 'no';
            $data['showPercent'] = ('yes' === ($settings['kng_steps_show_percent'] ?? '')) ? 'yes' : 'no';
            $data['markersClickable'] = ('yes' === ($settings['kng_steps_progress_clickable'] ?? '')) ? 'yes' : 'no';
            $data['reducedMotion'] = $this->sanitize_reduced_motion($settings['kng_steps_reduced_motion'] ?? 'auto');
            $data['stepLabel'] = esc_html__('Step %1$s of %2$s', 'king-addons');
        }

        return $data;
    }

    protected function get_default_steps(): array
    {
        return [
            [
                'step_title' => esc_html__('Share your goals', 'king-addons'),
                'step_description' => esc_html__('Tell us what success looks like so we can tailor the plan.', 'king-addons'),
                'step_details' => esc_html__('Include your timeline, target audience, and success metrics to align the team.', 'king-addons'),
            ],
            [
                'step_title' => esc_html__('We build the roadmap', 'king-addons'),
                'step_description' => esc_html__('A clear sequence of tasks keeps everyone on track.', 'king-addons'),
                'step_details' => esc_html__('We break the project into milestones with owners and deadlines.', 'king-addons'),
            ],
            [
                'step_title' => esc_html__('Launch and improve', 'king-addons'),
                'step_description' => esc_html__('Ship fast and iterate with real data.', 'king-addons'),
                'step_details' => esc_html__('Measure results, collect feedback, and keep refining.', 'king-addons'),
            ],
        ];
    }

    protected function get_panel_id(int $index): string
    {
        $raw = 'kng-isp-panel-' . $this->get_id() . '-' . ($index + 1);
        return sanitize_key($raw);
    }

    protected function get_button_id(int $index): string
    {
        $raw = 'kng-isp-button-' . $this->get_id() . '-' . ($index + 1);
        return sanitize_key($raw);
    }

    /**
     * Ensure step anchors are unique within a single widget instance.
     *
     * @param array $settings
     * @param array $steps
     * @return array<int, string>
     */
    protected function build_unique_step_anchors(array $settings, array $steps): array
    {
        if (!$this->is_pro_enabled()) {
            return [];
        }

        if ('yes' !== ($settings['kng_steps_enable_anchors'] ?? '')) {
            return [];
        }

        $used = [];
        $anchors = [];

        foreach ($steps as $index => $step) {
            $raw = trim((string) ($step['step_anchor_id'] ?? ''));
            if ('' === $raw) {
                $raw = trim((string) ($step['step_title'] ?? ''));
            }

            $slug = $this->sanitize_anchor_id($raw);
            if ('' === $slug) {
                $slug = 'kng-isp-' . $this->get_id() . '-' . ($index + 1);
            }

            $base = $slug;
            $suffix = 2;
            while (isset($used[$slug])) {
                $slug = $base . '-' . $suffix;
                $suffix++;
            }

            $used[$slug] = true;
            $anchors[(int) $index] = $slug;
        }

        return $anchors;
    }

    protected function get_step_anchor(array $settings, array $step, int $index): string
    {
        if (!$this->is_pro_enabled()) {
            return '';
        }

        if ('yes' !== ($settings['kng_steps_enable_anchors'] ?? '')) {
            return '';
        }

        $raw = trim((string) ($step['step_anchor_id'] ?? ''));
        if ('' === $raw) {
            $raw = trim((string) ($step['step_title'] ?? ''));
        }

        $slug = $this->sanitize_anchor_id($raw);
        if ('' === $slug) {
            $slug = 'kng-isp-' . $this->get_id() . '-' . ($index + 1);
        }

        return $slug;
    }

    protected function sanitize_anchor_id(string $raw): string
    {
        $slug = strtolower($raw);
        $slug = preg_replace('/[^a-z0-9\\-_]+/', '-', $slug);
        return trim((string) $slug, '-_');
    }

    protected function get_orientation(array $settings): string
    {
        $orientation = $settings['kng_steps_orientation'] ?? 'vertical';
        if (!$this->is_pro_enabled()) {
            return 'vertical';
        }

        return in_array($orientation, ['vertical', 'horizontal'], true) ? $orientation : 'vertical';
    }

    protected function sanitize_alignment(string $alignment): string
    {
        return in_array($alignment, ['left', 'center'], true) ? $alignment : 'left';
    }

    protected function sanitize_progress_style(string $style): string
    {
        return in_array($style, ['dots', 'numbers'], true) ? $style : 'dots';
    }

    protected function sanitize_line_display(string $display): string
    {
        return in_array($display, ['show', 'hide'], true) ? $display : 'show';
    }

    protected function sanitize_show_completed(string $value): string
    {
        return in_array($value, ['yes', 'no'], true) ? $value : 'yes';
    }

    protected function sanitize_expand_behavior(string $value): string
    {
        return in_array($value, ['none', 'click'], true) ? $value : 'click';
    }

    protected function sanitize_default_open(string $value): string
    {
        return in_array($value, ['first', 'closed'], true) ? $value : 'first';
    }

    protected function sanitize_icon_position(string $value): string
    {
        return in_array($value, ['left', 'top'], true) ? $value : 'left';
    }

    protected function sanitize_toggle(string $value): string
    {
        return 'yes' === $value ? 'yes' : 'no';
    }

    protected function sanitize_wrap_mode(string $value): string
    {
        return in_array($value, ['wrap', 'nowrap', 'scroll'], true) ? $value : 'wrap';
    }

    protected function sanitize_mobile_fallback(string $value): string
    {
        return in_array($value, ['vertical', 'scroll'], true) ? $value : 'vertical';
    }

    protected function sanitize_sticky_type(string $value): string
    {
        return in_array($value, ['bar', 'header'], true) ? $value : 'bar';
    }

    protected function sanitize_reveal_type(string $value): string
    {
        return in_array($value, ['fade', 'slide', 'scale'], true) ? $value : 'fade';
    }

    protected function sanitize_reduced_motion(string $value): string
    {
        return in_array($value, ['auto', 'off', 'minimal', 'full'], true) ? $value : 'auto';
    }

    protected function sanitize_easing(string $value): string
    {
        $allowed = ['linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out'];
        return in_array($value, $allowed, true) ? $value : 'ease';
    }

    protected function sanitize_activation_offset($value): int
    {
        $offset = is_numeric($value) ? (int) $value : 40;
        $offset = max(10, min(80, $offset));
        return $offset;
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

    protected function is_editor(): bool
    {
        return class_exists(Plugin::class) && Plugin::$instance->editor->is_edit_mode();
    }
}
