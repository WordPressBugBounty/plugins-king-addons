<?php
/**
 * Theme Builder Post Title widget (Free).
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
 * Renders the current post title in Theme Builder templates.
 */
class TB_Post_Title extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-post-title';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Post Title', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-t-letter';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-post-title-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-post-title-script'];
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
        return ['title', 'post', 'heading', 'theme builder', 'king-addons'];
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
            'kng_link_to',
            [
                'label' => esc_html__('Link', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'post' => esc_html__('Post Permalink', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_truncate_mode',
            [
                'label' => $is_pro ?
                    esc_html__('Truncate Mode', 'king-addons') :
                    sprintf(__('Truncate Mode %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'none' => esc_html__('None', 'king-addons'),
                    'words' => esc_html__('Words', 'king-addons'),
                    'chars' => esc_html__('Characters', 'king-addons'),
                ],
                'default' => 'none',
                'classes' => $is_pro ? '' : 'king-addons-pro-control no-distance',
            ]
        );

        $this->add_control(
            'kng_truncate_length',
            [
                'label' => $is_pro ?
                    esc_html__('Truncate Length', 'king-addons') :
                    sprintf(__('Truncate Length %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'default' => 10,
                'condition' => [
                    'kng_truncate_mode!' => 'none',
                ],
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        $this->add_control(
            'kng_hover_underline',
            [
                'label' => $is_pro ?
                    esc_html__('Hover Underline', 'king-addons') :
                    sprintf(__('Hover Underline %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
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
                'selector' => '{{WRAPPER}} .king-addons-tb-post-title',
            ]
        );

        $this->add_control(
            'kng_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_color_hover',
            [
                'label' => esc_html__('Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-title:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-tb-post-title a:hover' => 'color: {{VALUE}};',
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
                    'justify' => [
                        'title' => esc_html__('Justify', 'king-addons'),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-title' => 'text-align: {{VALUE}};',
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
                    '{{WRAPPER}} .king-addons-tb-post-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-tb-post-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        if ($is_pro) {
            $this->add_control(
                'kng_underline_color',
                [
                    'label' => esc_html__('Underline Color', 'king-addons'),
                    'type' => Controls_Manager::COLOR,
                    'condition' => [
                        'kng_hover_underline' => 'yes',
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .king-addons-tb-post-title--underline::after' => 'background-color: {{VALUE}};',
                    ],
                ]
            );
        }

        $this->end_controls_section();
    }

    /**
     * Pro upsell notice.
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
            'tb-post-title',
            [
                'Title truncation by words or characters',
                'Hover underline and advanced hover effects',
            ]
        );
    }

    /**
     * Render output helper.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param bool                 $is_pro   Whether pro mode is enabled.
     *
     * @return void
     */
    protected function render_output(array $settings, bool $is_pro): void
    {
        $post = get_post();
        if (!$post instanceof \WP_Post) {
            return;
        }

        $title = get_the_title($post);
        if ('' === (string) $title) {
            return;
        }

        $tag = isset($settings['kng_html_tag']) ? strtolower((string) $settings['kng_html_tag']) : 'h1';
        $allowed_tags = ['h1', 'h2', 'h3', 'div', 'span'];
        if (!in_array($tag, $allowed_tags, true)) {
            $tag = 'h1';
        }

        $truncate_mode = $settings['kng_truncate_mode'] ?? 'none';
        $truncate_length = (int) ($settings['kng_truncate_length'] ?? 0);
        if ($is_pro && $truncate_mode !== 'none' && $truncate_length > 0) {
            if ('words' === $truncate_mode) {
                $title = wp_trim_words($title, $truncate_length, '…');
            } elseif ('chars' === $truncate_mode) {
                $title = mb_substr($title, 0, $truncate_length) . '…';
            }
        }

        $link_to = $settings['kng_link_to'] ?? 'none';
        $title_html = esc_html($title);

        if ('post' === $link_to) {
            $permalink = get_permalink($post);
            if ($permalink) {
                $title_html = sprintf(
                    '<a href="%1$s" class="king-addons-tb-post-title__link">%2$s</a>',
                    esc_url($permalink),
                    $title_html
                );
            }
        }

        $classes = ['king-addons-tb-post-title'];
        if ($is_pro && ('yes' === ($settings['kng_hover_underline'] ?? ''))) {
            $classes[] = 'king-addons-tb-post-title--underline';
        }

        printf(
            '<%1$s class="%2$s">%3$s</%1$s>',
            esc_attr($tag),
            esc_attr(implode(' ', $classes)),
            $title_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }
}
