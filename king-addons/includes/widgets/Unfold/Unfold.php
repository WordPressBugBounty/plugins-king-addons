<?php
/**
 * Unfold Widget (Free).
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
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Collapsible content box with fade overlay.
 */
class Unfold extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-unfold';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Unfold', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-unfold';
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
     * Search keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['unfold', 'toggle', 'collapse', 'read more', 'expand'];
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-unfold-style',
        ];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-unfold-script',
        ];
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
        $this->register_content_controls(false);
        $this->register_button_controls(false);
        $this->register_fade_controls(false);
        $this->register_advanced_controls(false);
        $this->register_style_box_controls();
        $this->register_style_title_controls();
        $this->register_style_content_controls();
        $this->register_style_button_controls();
        $this->register_style_fade_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Render widget.
     *
     * @return void
     */
    public function render(): void
    {
        $this->render_output(false);
    }

    /**
     * Register content controls.
     *
     * @param bool $is_pro Whether pro controls are available.
     *
     * @return void
     */
    public function register_content_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_content_source',
            [
                'label' => esc_html__('Content Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'editor',
                'options' => [
                    'editor' => esc_html__('Editor', 'king-addons'),
                    'template' => $is_pro ?
                        esc_html__('Elementor Template', 'king-addons') :
                        sprintf('%s %s', esc_html__('Elementor Template', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'description' => $is_pro ? '' : esc_html__('Use Elementor Template with the Pro version.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_title_enable',
            [
                'label' => esc_html__('Title Enable', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Expandable block', 'king-addons'),
                'condition' => [
                    'kng_title_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_title_tag',
            [
                'label' => esc_html__('Title HTML Tag', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'h1' => 'H1',
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                    'h6' => 'H6',
                    'div' => 'DIV',
                ],
                'default' => 'h3',
                'condition' => [
                    'kng_title_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_editor_content',
            [
                'label' => esc_html__('Content', 'king-addons'),
                'type' => Controls_Manager::WYSIWYG,
                'default' => esc_html__('Add your description here. The block will unfold to reveal the full content.', 'king-addons'),
                'condition' => [
                    'kng_content_source' => 'editor',
                ],
            ]
        );

        $this->add_control(
            'kng_template_id',
            [
                'label' => sprintf('%s %s', esc_html__('Template', 'king-addons'), $is_pro ? '' : '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => $is_pro ? $this->get_elementor_templates_options() : [],
                'condition' => [
                    'kng_content_source' => 'template',
                ],
                'description' => $is_pro ? '' : esc_html__('Select an Elementor template (Pro).', 'king-addons'),
            ]
        );

        $this->add_responsive_control(
            'kng_content_alignment',
            [
                'label' => esc_html__('Alignment', 'king-addons'),
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
                    'justify' => [
                        'title' => esc_html__('Justify', 'king-addons'),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__box' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register button controls.
     *
     * @param bool $is_pro Whether pro controls are available.
     *
     * @return void
     */
    public function register_button_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_button_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_button_enable',
            [
                'label' => esc_html__('Button Enable', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_unfold_text',
            [
                'label' => esc_html__('Unfold Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Read more', 'king-addons'),
                'condition' => [
                    'kng_button_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_fold_text',
            [
                'label' => esc_html__('Fold Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Read less', 'king-addons'),
                'condition' => [
                    'kng_button_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_enable',
            [
                'label' => esc_html__('Icon Enable', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_button_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_unfold_icon',
            [
                'label' => esc_html__('Unfold Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-chevron-down',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'kng_button_enable' => 'yes',
                    'kng_icon_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_fold_icon',
            [
                'label' => esc_html__('Fold Icon', 'king-addons'),
                'type' => Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-chevron-up',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'kng_button_enable' => 'yes',
                    'kng_icon_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_position',
            [
                'label' => esc_html__('Icon Position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'before',
                'options' => [
                    'before' => esc_html__('Before', 'king-addons'),
                    'after' => esc_html__('After', 'king-addons'),
                ],
                'condition' => [
                    'kng_button_enable' => 'yes',
                    'kng_icon_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_button_size',
            [
                'label' => esc_html__('Button Size', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'md',
                'options' => [
                    'xs' => esc_html__('XS', 'king-addons'),
                    'sm' => esc_html__('SM', 'king-addons'),
                    'md' => esc_html__('MD', 'king-addons'),
                    'lg' => esc_html__('LG', 'king-addons'),
                    'block' => esc_html__('Block', 'king-addons'),
                ],
                'condition' => [
                    'kng_button_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_button_position',
            [
                'label' => $is_pro ?
                    esc_html__('Button Position', 'king-addons') :
                    sprintf(__('Button Position %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'default' => 'outside',
                'options' => [
                    'inside' => esc_html__('Inside', 'king-addons'),
                    'outside' => esc_html__('Outside', 'king-addons'),
                ],
                'condition' => [
                    'kng_button_enable' => 'yes',
                ],
                'description' => $is_pro ? '' : esc_html__('Inside placement is available in Pro.', 'king-addons'),
            ]
        );

        $this->add_responsive_control(
            'kng_button_alignment',
            [
                'label' => esc_html__('Button Alignment', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'king-addons'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'king-addons'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'king-addons'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'condition' => [
                    'kng_button_enable' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__button-wrapper' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register fade controls.
     *
     * @param bool $is_pro Whether pro controls are available.
     *
     * @return void
     */
    public function register_fade_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_fade_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Fade Effect', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_fade_enable',
            [
                'label' => esc_html__('Fade Enable', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_fade_height',
            [
                'label' => $is_pro ? esc_html__('Fade Height', 'king-addons') : sprintf(__('Fade Height %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 400,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 30,
                ],
                'condition' => [
                    'kng_fade_enable' => 'yes',
                ],
                'description' => $is_pro ? '' : esc_html__('Adjustable fade height is available in Pro.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_fade_only_folded',
            [
                'label' => esc_html__('Fade Only When Folded', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'kng_fade_enable' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register advanced controls.
     *
     * @param bool $is_pro Whether pro controls are available.
     *
     * @return void
     */
    public function register_advanced_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_advanced_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Advanced Settings', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_initial_state',
            [
                'label' => esc_html__('Initial State', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'folded',
                'options' => [
                    'folded' => esc_html__('Folded', 'king-addons'),
                    'unfolded' => esc_html__('Unfolded', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_fold_height_unit',
            [
                'label' => esc_html__('Fold Height Unit', 'king-addons'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'percent' => [
                        'title' => esc_html__('Percent', 'king-addons'),
                        'icon' => 'eicon-percentage',
                    ],
                    'px' => [
                        'title' => esc_html__('PX', 'king-addons'),
                        'icon' => 'eicon-editor-list-ol',
                    ],
                ],
                'default' => 'percent',
            ]
        );

        $this->add_responsive_control(
            'kng_fold_height_percent',
            [
                'label' => esc_html__('Fold Height', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 60,
                ],
                'condition' => [
                    'kng_fold_height_unit' => 'percent',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_fold_height_px',
            [
                'label' => $is_pro ? esc_html__('Fold Height', 'king-addons') : sprintf(__('Fold Height %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 2000,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 320,
                ],
                'condition' => [
                    'kng_fold_height_unit' => 'px',
                ],
                'description' => $is_pro ? '' : esc_html__('Fixed height is available in Pro.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_shared_duration',
            [
                'label' => esc_html__('Animation Duration', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'normal',
                'options' => [
                    'fast' => esc_html__('Fast', 'king-addons'),
                    'normal' => esc_html__('Normal', 'king-addons'),
                    'slow' => esc_html__('Slow', 'king-addons'),
                    'custom' => esc_html__('Custom', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_shared_duration_custom',
            [
                'label' => esc_html__('Custom Duration (s)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0.1,
                'step' => 0.1,
                'default' => 0.5,
                'condition' => [
                    'kng_shared_duration' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'kng_shared_easing',
            [
                'label' => esc_html__('Easing', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'ease',
                'options' => [
                    'linear' => esc_html__('Linear', 'king-addons'),
                    'ease' => esc_html__('Ease', 'king-addons'),
                    'ease-in' => esc_html__('Ease In', 'king-addons'),
                    'ease-out' => esc_html__('Ease Out', 'king-addons'),
                    'ease-in-out' => esc_html__('Ease In Out', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_fold_duration_mode',
            [
                'label' => $is_pro ? esc_html__('Fold Duration', 'king-addons') : sprintf(__('Fold Duration %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'default' => 'shared',
                'options' => [
                    'shared' => esc_html__('Use Shared', 'king-addons'),
                    'fast' => esc_html__('Fast', 'king-addons'),
                    'normal' => esc_html__('Normal', 'king-addons'),
                    'slow' => esc_html__('Slow', 'king-addons'),
                    'custom' => esc_html__('Custom', 'king-addons'),
                ],
                'description' => $is_pro ? '' : esc_html__('Separate duration is available in Pro.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_fold_duration_custom',
            [
                'label' => esc_html__('Fold Custom (s)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0.1,
                'step' => 0.1,
                'default' => 0.5,
                'condition' => [
                    'kng_fold_duration_mode' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'kng_fold_easing',
            [
                'label' => $is_pro ? esc_html__('Fold Easing', 'king-addons') : sprintf(__('Fold Easing %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'default' => 'shared',
                'options' => [
                    'shared' => esc_html__('Use Shared', 'king-addons'),
                    'linear' => esc_html__('Linear', 'king-addons'),
                    'ease' => esc_html__('Ease', 'king-addons'),
                    'ease-in' => esc_html__('Ease In', 'king-addons'),
                    'ease-out' => esc_html__('Ease Out', 'king-addons'),
                    'ease-in-out' => esc_html__('Ease In Out', 'king-addons'),
                ],
                'description' => $is_pro ? '' : esc_html__('Separate easing is available in Pro.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_unfold_duration_mode',
            [
                'label' => $is_pro ? esc_html__('Unfold Duration', 'king-addons') : sprintf(__('Unfold Duration %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'default' => 'shared',
                'options' => [
                    'shared' => esc_html__('Use Shared', 'king-addons'),
                    'fast' => esc_html__('Fast', 'king-addons'),
                    'normal' => esc_html__('Normal', 'king-addons'),
                    'slow' => esc_html__('Slow', 'king-addons'),
                    'custom' => esc_html__('Custom', 'king-addons'),
                ],
                'description' => $is_pro ? '' : esc_html__('Separate duration is available in Pro.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_unfold_duration_custom',
            [
                'label' => esc_html__('Unfold Custom (s)', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0.1,
                'step' => 0.1,
                'default' => 0.5,
                'condition' => [
                    'kng_unfold_duration_mode' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'kng_unfold_easing',
            [
                'label' => $is_pro ? esc_html__('Unfold Easing', 'king-addons') : sprintf(__('Unfold Easing %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'default' => 'shared',
                'options' => [
                    'shared' => esc_html__('Use Shared', 'king-addons'),
                    'linear' => esc_html__('Linear', 'king-addons'),
                    'ease' => esc_html__('Ease', 'king-addons'),
                    'ease-in' => esc_html__('Ease In', 'king-addons'),
                    'ease-out' => esc_html__('Ease Out', 'king-addons'),
                    'ease-in-out' => esc_html__('Ease In Out', 'king-addons'),
                ],
                'description' => $is_pro ? '' : esc_html__('Separate easing is available in Pro.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_animate_height_type',
            [
                'label' => $is_pro ? esc_html__('Animate Height Type', 'king-addons') : sprintf(__('Animate Height Type %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'default' => 'auto-to-fixed',
                'options' => [
                    'auto-to-fixed' => esc_html__('Auto to fixed', 'king-addons'),
                    'max-height' => esc_html__('Max height', 'king-addons'),
                ],
                'description' => $is_pro ? '' : esc_html__('Max height animation is available in Pro.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_scroll_after_unfold',
            [
                'label' => $is_pro ? esc_html__('Scroll After Unfold', 'king-addons') : sprintf(__('Scroll After Unfold %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'description' => $is_pro ? '' : esc_html__('Auto scroll is available in Pro.', 'king-addons'),
            ]
        );

        $this->add_responsive_control(
            'kng_scroll_offset',
            [
                'label' => esc_html__('Scroll Offset', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 400,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 40,
                ],
                'condition' => [
                    'kng_scroll_after_unfold' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register box style controls.
     *
     * @return void
     */
    public function register_style_box_controls(): void
    {
        $this->start_controls_section(
            'kng_box_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Box', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'kng_box_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .king-addons-unfold__box',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_box_border',
                'selector' => '{{WRAPPER}} .king-addons-unfold__box',
            ]
        );

        $this->add_control(
            'kng_box_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_box_shadow',
                'selector' => '{{WRAPPER}} .king-addons-unfold__box',
            ]
        );

        $this->add_responsive_control(
            'kng_box_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_box_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register title style controls.
     *
     * @return void
     */
    public function register_style_title_controls(): void
    {
        $this->start_controls_section(
            'kng_title_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Title', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_title_enable' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-unfold__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_title_margin_bottom',
            [
                'label' => esc_html__('Margin Bottom', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 200,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register content style controls.
     *
     * @return void
     */
    public function register_style_content_controls(): void
    {
        $this->start_controls_section(
            'kng_content_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_content_typography',
                'selector' => '{{WRAPPER}} .king-addons-unfold__content',
            ]
        );

        $this->add_control(
            'kng_content_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__content' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_content_link_color',
            [
                'label' => esc_html__('Link Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__content a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_content_link_hover_color',
            [
                'label' => esc_html__('Link Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__content a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_paragraph_spacing',
            [
                'label' => esc_html__('Paragraph Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__content p:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}};',
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
            'kng_button_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Button', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_button_enable' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-unfold__button',
            ]
        );

        $this->start_controls_tabs('kng_button_style_tabs');

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
                    '{{WRAPPER}} .king-addons-unfold__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_background_color',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border',
                'selector' => '{{WRAPPER}} .king-addons-unfold__button',
            ]
        );

        $this->add_control(
            'kng_button_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow',
                'selector' => '{{WRAPPER}} .king-addons-unfold__button',
            ]
        );

        $this->add_responsive_control(
            'kng_button_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_spacing',
            [
                'label' => esc_html__('Icon Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__button--icon-before .king-addons-unfold__icon' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .king-addons-unfold__button--icon-after .king-addons-unfold__icon' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'kng_icon_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_button_hover_animation',
            [
                'label' => esc_html__('Hover Animation', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'lift' => esc_html__('Lift', 'king-addons'),
                    'scale' => esc_html__('Scale', 'king-addons'),
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
                    '{{WRAPPER}} .king-addons-unfold__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_background_color_hover',
            [
                'label' => esc_html__('Background Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_button_border_hover',
                'selector' => '{{WRAPPER}} .king-addons-unfold__button:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_button_shadow_hover',
                'selector' => '{{WRAPPER}} .king-addons-unfold__button:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /**
     * Register fade style controls.
     *
     * @return void
     */
    public function register_style_fade_controls(): void
    {
        $this->start_controls_section(
            'kng_fade_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Fade Overlay', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'kng_fade_enable' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_fade_color',
            [
                'label' => esc_html__('Fade Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(0, 0, 0, 0.2)',
                'selectors' => [
                    '{{WRAPPER}} .king-addons-unfold__fade' => '--ka-unfold-fade-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register pro promo controls.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection(
                $this,
                '',
                Controls_Manager::RAW_HTML,
                'unfold',
                [
                    'Use Elementor templates as content source',
                    'Fixed fold height and inside button placement',
                    'Separate fold/unfold durations and easing',
                    'Max-height animation mode and scroll after unfold',
                    'Custom fade overlay height and behavior',
                ]
            );
        }
    }

    /**
     * Render widget output.
     *
     * @param bool $is_pro Whether pro features are allowed.
     *
     * @return void
     */
    public function render_output(bool $is_pro): void
    {
        $settings = $this->get_settings_for_display();
        $content_id = 'king-addons-unfold-content-' . $this->get_id();
        $title_id = 'king-addons-unfold-title-' . $this->get_id();
        $initial_state = $settings['kng_initial_state'] ?? 'folded';
        $has_button = ($settings['kng_button_enable'] ?? 'yes') === 'yes';
        $has_title = ($settings['kng_title_enable'] ?? '') === 'yes' && !empty($settings['kng_title']);

        $fold_unit = $settings['kng_fold_height_unit'] ?? 'percent';
        if (!$is_pro && 'px' === $fold_unit) {
            $fold_unit = 'percent';
        }

        $fold_height = $this->get_fold_height_values($settings, $fold_unit);
        if (!$is_pro && 'px' === $fold_unit) {
            $fold_height = $this->get_fold_height_values($settings, 'percent');
        }

        $fade_height = $is_pro ? (float) ($settings['kng_fade_height']['size'] ?? 30) : 30;
        $fade_only_folded = ($settings['kng_fade_only_folded'] ?? 'yes') === 'yes';
        $fade_enabled = ($settings['kng_fade_enable'] ?? 'yes') === 'yes';

        $shared_duration = $this->resolve_duration(
            $settings['kng_shared_duration'] ?? 'normal',
            (float) ($settings['kng_shared_duration_custom'] ?? 0.5),
            0.5
        );

        $fold_duration = $shared_duration;
        $unfold_duration = $shared_duration;
        if ($is_pro) {
            $fold_duration = $this->resolve_duration(
                $settings['kng_fold_duration_mode'] ?? 'shared',
                (float) ($settings['kng_fold_duration_custom'] ?? 0.5),
                $shared_duration
            );
            $unfold_duration = $this->resolve_duration(
                $settings['kng_unfold_duration_mode'] ?? 'shared',
                (float) ($settings['kng_unfold_duration_custom'] ?? 0.5),
                $shared_duration
            );
        }

        $shared_easing = $settings['kng_shared_easing'] ?? 'ease';
        $fold_easing = $is_pro ? ($settings['kng_fold_easing'] ?? 'shared') : 'shared';
        $unfold_easing = $is_pro ? ($settings['kng_unfold_easing'] ?? 'shared') : 'shared';

        if ('shared' === $fold_easing) {
            $fold_easing = $shared_easing;
        }
        if ('shared' === $unfold_easing) {
            $unfold_easing = $shared_easing;
        }

        $animate_type = $is_pro ? ($settings['kng_animate_height_type'] ?? 'auto-to-fixed') : 'auto-to-fixed';
        $button_position = $is_pro ? ($settings['kng_button_position'] ?? 'outside') : 'outside';
        $scroll_after_unfold = $is_pro && ($settings['kng_scroll_after_unfold'] ?? '') === 'yes';
        $scroll_offset = (int) ($settings['kng_scroll_offset']['size'] ?? 0);

        $button_alignment_class = $this->get_button_alignment_class($settings);

        $wrapper_classes = [
            'king-addons-unfold',
            'folded' === $initial_state ? 'king-addons-unfold--folded' : 'king-addons-unfold--unfolded',
            $fade_enabled ? 'king-addons-unfold--fade' : 'king-addons-unfold--no-fade',
            $has_button ? 'king-addons-unfold--has-button' : 'king-addons-unfold--no-button',
            'inside' === $button_position ? 'king-addons-unfold--button-inside' : 'king-addons-unfold--button-outside',
            $button_alignment_class,
        ];

        $data_attributes = $this->build_wrapper_data_attributes(
            $fold_unit,
            $fold_height,
            $initial_state,
            $fade_height,
            $fade_only_folded,
            $fold_duration,
            $unfold_duration,
            $fold_easing,
            $unfold_easing,
            $animate_type,
            $scroll_after_unfold,
            $scroll_offset
        );

        $button_data = $this->build_button_data_attributes($settings, $is_pro);

        ?>
        <div class="<?php echo esc_attr(implode(' ', array_filter($wrapper_classes))); ?>" <?php echo $data_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
            <div class="king-addons-unfold__box">
                <?php $this->render_title($settings, $title_id); ?>
                <div
                    class="king-addons-unfold__content"
                    id="<?php echo esc_attr($content_id); ?>"
                    role="region"
                    <?php if ($has_title) : ?>
                        aria-labelledby="<?php echo esc_attr($title_id); ?>"
                    <?php endif; ?>
                    aria-hidden="<?php echo 'folded' === $initial_state ? 'true' : 'false'; ?>"
                    data-fold-height-desktop="<?php echo esc_attr($fold_height['desktop']); ?>"
                    data-fold-height-tablet="<?php echo esc_attr($fold_height['tablet']); ?>"
                    data-fold-height-mobile="<?php echo esc_attr($fold_height['mobile']); ?>"
                >
                    <?php $this->render_inner_content($settings, $is_pro); ?>
                </div>

                <?php if ($fade_enabled) : ?>
                    <div class="king-addons-unfold__fade" aria-hidden="true"></div>
                <?php endif; ?>

                <?php if ($has_button && 'inside' === $button_position) : ?>
                    <?php $this->render_button($settings, $content_id, $button_data, 'inside'); ?>
                <?php endif; ?>
            </div>

            <?php if ($has_button && 'outside' === $button_position) : ?>
                <?php $this->render_button($settings, $content_id, $button_data, 'outside'); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render title if enabled.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param string               $title_id Title element id.
     *
     * @return void
     */
    public function render_title(array $settings, string $title_id): void
    {
        if (($settings['kng_title_enable'] ?? '') !== 'yes' || empty($settings['kng_title'])) {
            return;
        }

        $tag = $settings['kng_title_tag'] ?? 'h3';
        $title = $settings['kng_title'];

        printf(
            '<%1$s class="king-addons-unfold__title" id="%2$s">%3$s</%1$s>',
            esc_attr($tag),
            esc_attr($title_id),
            esc_html($title)
        );
    }

    /**
     * Render inner content.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param bool                 $is_pro   Whether pro features are allowed.
     *
     * @return void
     */
    public function render_inner_content(array $settings, bool $is_pro): void
    {
        $content_source = $settings['kng_content_source'] ?? 'editor';
        if (!$is_pro) {
            $content_source = 'editor';
        }

        if ('template' === $content_source && !empty($settings['kng_template_id'])) {
            echo $this->get_template_content((int) $settings['kng_template_id']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }

        if (!empty($settings['kng_editor_content'])) {
            echo wp_kses_post($settings['kng_editor_content']);
        }
    }

    /**
     * Render toggle button.
     *
     * @param array<string, mixed> $settings        Widget settings.
     * @param string               $content_id      Target content id.
     * @param array<string, mixed> $button_data     Button data attributes.
     * @param string               $position        Button position.
     *
     * @return void
     */
    public function render_button(array $settings, string $content_id, array $button_data, string $position): void
    {
        $icon_enable = ($settings['kng_icon_enable'] ?? '') === 'yes';
        $button_size = $settings['kng_button_size'] ?? 'md';
        $icon_position = $settings['kng_icon_position'] ?? 'before';
        $is_unfolded_initial = ($settings['kng_initial_state'] ?? 'folded') === 'unfolded';
        $hover_animation = $settings['kng_button_hover_animation'] ?? 'none';

        $wrapper_classes = [
            'king-addons-unfold__button-wrapper',
            'king-addons-unfold__button-wrapper--' . $position,
        ];

        $button_classes = [
            'king-addons-unfold__button',
            'king-addons-unfold__button--' . $button_size,
            'king-addons-unfold__button--icon-' . $icon_position,
            'king-addons-unfold__button--hover-' . $hover_animation,
        ];

        $icon_before = 'before' === $icon_position;

        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>">
            <button
                type="button"
                class="<?php echo esc_attr(implode(' ', $button_classes)); ?>"
                aria-expanded="<?php echo $is_unfolded_initial ? 'true' : 'false'; ?>"
                aria-controls="<?php echo esc_attr($content_id); ?>"
                <?php echo $this->format_data_attributes($button_data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            >
                <?php if ($icon_enable && $icon_before) : ?>
                    <?php $this->render_icons($settings, $is_unfolded_initial); ?>
                <?php endif; ?>

                <span class="king-addons-unfold__text">
                    <?php echo esc_html($is_unfolded_initial ? ($settings['kng_fold_text'] ?? '') : ($settings['kng_unfold_text'] ?? '')); ?>
                </span>

                <?php if ($icon_enable && !$icon_before) : ?>
                    <?php $this->render_icons($settings, $is_unfolded_initial); ?>
                <?php endif; ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render icon markup for both states.
     *
     * @param array<string, mixed> $settings          Widget settings.
     * @param bool                 $is_unfolded_state Whether initial state is unfolded.
     *
     * @return void
     */
    public function render_icons(array $settings, bool $is_unfolded_state): void
    {
        $unfold_icon = $settings['kng_unfold_icon'] ?? null;
        $fold_icon = $settings['kng_fold_icon'] ?? null;

        echo '<span class="king-addons-unfold__icon king-addons-unfold__icon--unfold" aria-hidden="' . ($is_unfolded_state ? 'true' : 'false') . '">';
        if (!empty($unfold_icon['value'])) {
            Icons_Manager::render_icon($unfold_icon);
        }
        echo '</span>';

        echo '<span class="king-addons-unfold__icon king-addons-unfold__icon--fold" aria-hidden="' . ($is_unfolded_state ? 'false' : 'true') . '">';
        if (!empty($fold_icon['value'])) {
            Icons_Manager::render_icon($fold_icon);
        }
        echo '</span>';
    }

    /**
     * Build wrapper data attributes string.
     *
     * @param string               $fold_unit          Fold unit.
     * @param array<string, float> $fold_height        Fold height values.
     * @param string               $initial_state      Initial state.
     * @param float                $fade_height        Fade height.
     * @param bool                 $fade_only_folded   Fade only when folded.
     * @param float                $fold_duration      Fold duration.
     * @param float                $unfold_duration    Unfold duration.
     * @param string               $fold_easing        Fold easing.
     * @param string               $unfold_easing      Unfold easing.
     * @param string               $animate_type       Animation type.
     * @param bool                 $scroll_after       Scroll after unfold.
     * @param int                  $scroll_offset      Scroll offset.
     *
     * @return string
     */
    public function build_wrapper_data_attributes(
        string $fold_unit,
        array $fold_height,
        string $initial_state,
        float $fade_height,
        bool $fade_only_folded,
        float $fold_duration,
        float $unfold_duration,
        string $fold_easing,
        string $unfold_easing,
        string $animate_type,
        bool $scroll_after,
        int $scroll_offset
    ): string {
        $attributes = [
            'data-fold-unit' => $fold_unit,
            'data-initial-state' => $initial_state,
            'data-fade-height' => $fade_height,
            'data-fade-only-folded' => $fade_only_folded ? 'true' : 'false',
            'data-fold-duration' => $fold_duration,
            'data-unfold-duration' => $unfold_duration,
            'data-fold-easing' => $fold_easing,
            'data-unfold-easing' => $unfold_easing,
            'data-animate-type' => $animate_type,
            'data-scroll-after' => $scroll_after ? 'true' : 'false',
            'data-scroll-offset' => $scroll_offset,
        ];

        foreach ($attributes as $key => $value) {
            $attributes[$key] = $key . '="' . esc_attr((string) $value) . '"';
        }

        $heights = [
            'data-fold-height-desktop' => $fold_height['desktop'],
            'data-fold-height-tablet' => $fold_height['tablet'],
            'data-fold-height-mobile' => $fold_height['mobile'],
        ];

        foreach ($heights as $key => $value) {
            $heights[$key] = $key . '="' . esc_attr((string) $value) . '"';
        }

        return implode(' ', array_merge($attributes, $heights));
    }

    /**
     * Build button data attributes.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param bool                 $is_pro   Whether pro features are available.
     *
     * @return array<string, string>
     */
    public function build_button_data_attributes(array $settings, bool $is_pro): array
    {
        $button_position = $is_pro ? ($settings['kng_button_position'] ?? 'outside') : 'outside';
        $data = [
            'data-unfold-text' => $settings['kng_unfold_text'] ?? '',
            'data-fold-text' => $settings['kng_fold_text'] ?? '',
            'data-icon-position' => $settings['kng_icon_position'] ?? 'before',
            'data-button-position' => $button_position,
        ];

        return $data;
    }

    /**
     * Format data attributes for HTML output.
     *
     * @param array<string, mixed> $attributes Attributes.
     *
     * @return string
     */
    public function format_data_attributes(array $attributes): string
    {
        $formatted = [];
        foreach ($attributes as $key => $value) {
            $formatted[] = $key . '="' . esc_attr((string) $value) . '"';
        }
        return implode(' ', $formatted);
    }

    /**
     * Get fold height values for devices.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param string               $unit     Unit type.
     *
     * @return array<string, float>
     */
    public function get_fold_height_values(array $settings, string $unit): array
    {
        $key = 'percent' === $unit ? 'kng_fold_height_percent' : 'kng_fold_height_px';

        $desktop = isset($settings[$key]['size']) ? (float) $settings[$key]['size'] : ('percent' === $unit ? 60.0 : 320.0);
        $tablet = isset($settings[$key . '_tablet']['size']) ? (float) $settings[$key . '_tablet']['size'] : $desktop;
        $mobile = isset($settings[$key . '_mobile']['size']) ? (float) $settings[$key . '_mobile']['size'] : $tablet;

        return [
            'desktop' => $desktop,
            'tablet' => $tablet,
            'mobile' => $mobile,
        ];
    }

    /**
     * Resolve duration based on preset.
     *
     * @param string $preset        Preset.
     * @param float  $custom_value  Custom value.
     * @param float  $shared_fallback Fallback value.
     *
     * @return float
     */
    public function resolve_duration(string $preset, float $custom_value, float $shared_fallback): float
    {
        switch ($preset) {
            case 'fast':
                return 0.3;
            case 'normal':
                return 0.5;
            case 'slow':
                return 0.8;
            case 'custom':
                return max(0.05, $custom_value);
            case 'shared':
            default:
                return $shared_fallback;
        }
    }

    /**
     * Get Elementor template content.
     *
     * @param int $template_id Template id.
     *
     * @return string
     */
    public function get_template_content(int $template_id): string
    {
        if (empty($template_id)) {
            return '';
        }

        $has_css = 'internal' === get_option('elementor_css_print_method');

        return Plugin::instance()->frontend->get_builder_content_for_display($template_id, $has_css);
    }

    /**
     * Get Elementor templates options.
     *
     * @return array<string, string>
     */
    public function get_elementor_templates_options(): array
    {
        $options = [];
        $templates = Plugin::$instance->templates_manager->get_source('local')->get_items();

        if (!empty($templates)) {
            foreach ($templates as $template) {
                $options[$template['template_id']] = $template['title'];
            }
        }

        return $options;
    }

    /**
     * Get button alignment helper class.
     *
     * @param array<string, mixed> $settings Widget settings.
     *
     * @return string
     */
    public function get_button_alignment_class(array $settings): string
    {
        $alignment = $settings['kng_button_alignment'] ?? 'center';
        return 'king-addons-unfold--align-' . $alignment;
    }
}





