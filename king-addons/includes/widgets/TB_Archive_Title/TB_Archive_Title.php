<?php
/**
 * Theme Builder Archive Title widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays the current archive title.
 */
class TB_Archive_Title extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-archive-title';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Archive Title', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-archive-title';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-archive-title-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-archive-title-script'];
    }

    /**
     * Categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons'];
    }

    /**
     * Keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['archive', 'title', 'taxonomy', 'search', 'king-addons'];
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
        $this->register_style_controls(false);
        $this->register_pro_notice_controls();
    }

    /**
     * Render output.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $this->render_output($settings, false);
    }

    /**
     * Content controls.
     *
     * @param bool $is_pro Whether Pro controls are enabled.
     *
     * @return void
     */
    protected function register_content_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_html_tag',
            [
                'label' => esc_html__('HTML Tag', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'h1',
                'options' => [
                    'h1' => 'H1',
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'div' => 'div',
                    'span' => 'span',
                ],
            ]
        );

        $this->add_control(
            'kng_prefix_mode',
            [
                'label' => esc_html__('Prefix', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'default',
                'options' => [
                    'default' => esc_html__('Use Default', 'king-addons'),
                    'remove' => esc_html__('Remove Default Prefix', 'king-addons'),
                    'custom' => $is_pro ?
                        esc_html__('Custom Prefix', 'king-addons') :
                        sprintf(__('Custom Prefix %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
            ]
        );

        $this->add_control(
            'kng_custom_prefix',
            [
                'label' => esc_html__('Custom Prefix Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Posts in', 'king-addons'),
                'condition' => [
                    'kng_prefix_mode' => 'custom',
                ],
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style controls.
     *
     * @param bool $is_pro Whether Pro controls are enabled.
     *
     * @return void
     */
    protected function register_style_controls(bool $is_pro): void
    {
        $this->start_controls_section(
            'kng_style_section',
            [
                'label' => esc_html__('Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_typography',
                'selector' => '{{WRAPPER}} .king-addons-tb-archive-title',
            ]
        );

        $this->add_control(
            'kng_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-archive-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_alignment',
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
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-archive-title' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-archive-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Pro upsell.
     *
     * @return void
     */
    protected function register_pro_notice_controls(): void
    {
        if (king_addons_freemius()->can_use_premium_code__premium_only()) {
            return;
        }

        Core::renderProFeaturesSection(
            $this,
            '',
            Controls_Manager::RAW_HTML,
            'tb-archive-title',
            [
                'Custom prefixes per archive type',
                'Advanced conditional formatting',
            ]
        );
    }

    /**
     * Render output helper.
     *
     * @param array<string, mixed> $settings Settings.
     * @param bool                 $is_pro   Pro flag.
     *
     * @return void
     */
    protected function render_output(array $settings, bool $is_pro): void
    {
        $tag = isset($settings['kng_html_tag']) ? strtolower((string) $settings['kng_html_tag']) : 'h1';
        $allowed_tags = ['h1', 'h2', 'h3', 'div', 'span'];
        if (!in_array($tag, $allowed_tags, true)) {
            $tag = 'h1';
        }

        $title = get_the_archive_title();
        if ('remove' === ($settings['kng_prefix_mode'] ?? 'default')) {
            $title = preg_replace('/^[^:]+:\\s*/', '', $title);
        }

        if ('custom' === ($settings['kng_prefix_mode'] ?? 'default') && $is_pro && !empty($settings['kng_custom_prefix'])) {
            $title = sprintf('%s %s', $settings['kng_custom_prefix'], $title);
        }

        $title = wp_strip_all_tags((string) $title);
        if ('' === $title) {
            return;
        }

        printf(
            '<%1$s class="king-addons-tb-archive-title">%2$s</%1$s>',
            esc_attr($tag),
            esc_html($title)
        );
    }
}
