<?php
/**
 * Theme Builder Post Excerpt widget (Free).
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
 * Shows the current post excerpt with trimming options.
 */
class TB_Post_Excerpt extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-post-excerpt';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Post Excerpt', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-editor-quote';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-post-excerpt-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-post-excerpt-script'];
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
        return ['excerpt', 'summary', 'post', 'theme builder', 'king-addons'];
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
            'kng_trim_mode',
            [
                'label' => esc_html__('Trim By', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'words' => esc_html__('Words', 'king-addons'),
                    'chars' => esc_html__('Characters', 'king-addons'),
                ],
                'default' => 'words',
            ]
        );

        $this->add_control(
            'kng_max_length',
            [
                'label' => esc_html__('Max Length', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'default' => 25,
            ]
        );

        $this->add_control(
            'kng_suffix',
            [
                'label' => esc_html__('Suffix', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '…',
            ]
        );

        $this->add_control(
            'kng_fallback_content',
            [
                'label' => esc_html__('Fallback to Content if Empty', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_strip_html',
            [
                'label' => $is_pro ?
                    esc_html__('Strip HTML', 'king-addons') :
                    sprintf(__('Strip HTML %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
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
                'selector' => '{{WRAPPER}} .king-addons-tb-post-excerpt',
            ]
        );

        $this->add_control(
            'kng_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-excerpt' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .king-addons-tb-post-excerpt' => 'text-align: {{VALUE}};',
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
                    '{{WRAPPER}} .king-addons-tb-post-excerpt' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .king-addons-tb-post-excerpt' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

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
            'tb-post-excerpt',
            [
                'Character-based trimming and HTML stripping',
                'Advanced sanitization options for excerpts',
            ]
        );
    }

    /**
     * Render output helper.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param bool                 $is_pro   Whether Pro options are enabled.
     *
     * @return void
     */
    protected function render_output(array $settings, bool $is_pro): void
    {
        $post = get_post();
        if (!$post instanceof \WP_Post) {
            return;
        }

        $trim_mode = $settings['kng_trim_mode'] ?? 'words';
        $max_length = (int) ($settings['kng_max_length'] ?? 25);
        $suffix = (string) ($settings['kng_suffix'] ?? '…');

        $excerpt = get_the_excerpt($post);
        if (empty($excerpt) && ('yes' === ($settings['kng_fallback_content'] ?? ''))) {
            $excerpt = wp_strip_all_tags(get_the_content(null, false, $post));
        }

        if ('chars' === $trim_mode) {
            $text = $is_pro && ('yes' === ($settings['kng_strip_html'] ?? '')) ? wp_strip_all_tags($excerpt) : $excerpt;
            $excerpt = mb_strlen($text) > $max_length ? mb_substr($text, 0, $max_length) . $suffix : $text;
        } else {
            $excerpt = wp_trim_words($excerpt, $max_length, $suffix);
        }

        if ('' === trim((string) $excerpt)) {
            return;
        }

        printf(
            '<div class="king-addons-tb-post-excerpt">%s</div>',
            wp_kses_post($excerpt)
        );
    }
}
