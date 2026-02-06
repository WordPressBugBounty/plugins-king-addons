<?php
/**
 * Theme Builder Post Content widget (Free).
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
 * Outputs the current post content inside Theme Builder templates.
 */
class TB_Post_Content extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-post-content';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Post Content', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-editor-paragraph';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-post-content-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-post-content-script'];
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
        return ['content', 'post', 'body', 'theme builder', 'king-addons'];
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
     * Render widget output.
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
     * @param bool $is_pro Whether pro controls are enabled.
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
            'kng_read_more_anchor',
            [
                'label' => esc_html__('Read More Anchor ID', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('example-anchor', 'king-addons'),
                'description' => esc_html__('Adds an id attribute to scroll to this widget.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_exclude_blocks',
            [
                'label' => $is_pro ?
                    esc_html__('Exclude First N Blocks', 'king-addons') :
                    sprintf(__('Exclude First N Blocks %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'default' => 0,
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style controls.
     *
     * @param bool $is_pro Whether pro controls are enabled.
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
                'selector' => '{{WRAPPER}} .king-addons-tb-post-content',
            ]
        );

        $this->add_control(
            'kng_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-content' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_link_color',
            [
                'label' => esc_html__('Link Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-content a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_link_color_hover',
            [
                'label' => esc_html__('Link Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-content a:hover' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .king-addons-tb-post-content' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        if ($is_pro) {
            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'kng_headings_typography',
                    'label' => esc_html__('Headings Typography', 'king-addons'),
                    'selector' => '{{WRAPPER}} .king-addons-tb-post-content h1, {{WRAPPER}} .king-addons-tb-post-content h2, {{WRAPPER}} .king-addons-tb-post-content h3, {{WRAPPER}} .king-addons-tb-post-content h4, {{WRAPPER}} .king-addons-tb-post-content h5, {{WRAPPER}} .king-addons-tb-post-content h6',
                ]
            );
        }

        $this->end_controls_section();
    }

    /**
     * Pro upsell section.
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
            'tb-post-content',
            [
                'Exclude first blocks for advanced layouts',
                'Dedicated heading and list typography',
                'Enhanced blockquote and list styling',
            ]
        );
    }

    /**
     * Render output helper.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param bool                 $is_pro   Whether Pro is enabled.
     *
     * @return void
     */
    protected function render_output(array $settings, bool $is_pro): void
    {
        $post = get_post();
        if (!$post instanceof \WP_Post) {
            return;
        }

        $anchor = sanitize_title($settings['kng_read_more_anchor'] ?? '');
        $content = '';

        if ($is_pro && !empty($settings['kng_exclude_blocks'])) {
            $exclude = (int) $settings['kng_exclude_blocks'];
            $blocks = parse_blocks((string) $post->post_content);
            if (!empty($blocks)) {
                $blocks = array_slice($blocks, $exclude);
                foreach ($blocks as $block) {
                    $content .= render_block($block);
                }
            } else {
                $content = (string) $post->post_content;
            }
        } else {
            $content = get_the_content(null, false, $post);
        }

        $content = apply_filters('the_content', $content);

        printf(
            '<div class="king-addons-tb-post-content"%s>%s</div>',
            $anchor ? ' id="' . esc_attr($anchor) . '"' : '',
            $content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }
}
