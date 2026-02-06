<?php
/**
 * Theme Builder Post Navigation widget (Free).
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
 * Shows previous/next post navigation.
 */
class TB_Post_Navigation extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-post-navigation';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Post Navigation', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-arrow-right';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-post-navigation-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-post-navigation-script'];
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
        return ['navigation', 'previous', 'next', 'post', 'king-addons'];
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
            'kng_show',
            [
                'label' => esc_html__('Show', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'both',
                'options' => [
                    'previous' => esc_html__('Previous Only', 'king-addons'),
                    'next' => esc_html__('Next Only', 'king-addons'),
                    'both' => esc_html__('Both', 'king-addons'),
                ],
            ]
        );

        $this->add_control(
            'kng_prev_label',
            [
                'label' => esc_html__('Previous Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Previous', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_next_label',
            [
                'label' => esc_html__('Next Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Next', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_show_titles',
            [
                'label' => esc_html__('Show Post Titles', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_same_tax',
            [
                'label' => $is_pro ?
                    esc_html__('Restrict to Same Category', 'king-addons') :
                    sprintf(__('Restrict to Same Category %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        $this->add_control(
            'kng_layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'inline',
                'options' => [
                    'inline' => esc_html__('Inline', 'king-addons'),
                    'columns' => esc_html__('Two Columns', 'king-addons'),
                    'stacked' => esc_html__('Stacked', 'king-addons'),
                ],
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
                'name' => 'kng_label_typography',
                'label' => esc_html__('Label Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tb-post-navigation__label',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'label' => esc_html__('Title Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tb-post-navigation__title',
            ]
        );

        $this->add_control(
            'kng_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-navigation' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-tb-post-navigation a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_text_color_hover',
            [
                'label' => esc_html__('Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-navigation a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'default' => [
                    'size' => 12,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-navigation' => 'gap: {{SIZE}}{{UNIT}};',
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
            'tb-post-navigation',
            [
                'Restrict navigation to same category',
                'Arrow icons and advanced layout skins',
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

        $same = $is_pro && ('yes' === ($settings['kng_same_tax'] ?? '')) ? true : false;
        $previous = get_previous_post($same);
        $next = get_next_post($same);

        $show = $settings['kng_show'] ?? 'both';
        $items = [];

        if (('previous' === $show || 'both' === $show) && $previous instanceof \WP_Post) {
            $items[] = $this->build_item(
                $settings['kng_prev_label'] ?? '',
                $previous,
                'prev',
                $settings
            );
        }

        if (('next' === $show || 'both' === $show) && $next instanceof \WP_Post) {
            $items[] = $this->build_item(
                $settings['kng_next_label'] ?? '',
                $next,
                'next',
                $settings
            );
        }

        if (empty($items)) {
            return;
        }

        $layout = $settings['kng_layout'] ?? 'inline';
        $classes = ['king-addons-tb-post-navigation', 'king-addons-tb-post-navigation--' . $layout];

        echo '<div class="' . esc_attr(implode(' ', $classes)) . '">';
        foreach ($items as $item) {
            echo $item; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</div>';
    }

    /**
     * Build item markup.
     *
     * @param string   $label    Label text.
     * @param \WP_Post $post     Target post.
     * @param string   $type     Item type prev/next.
     * @param array    $settings Settings.
     *
     * @return string
     */
    protected function build_item(string $label, \WP_Post $post, string $type, array $settings): string
    {
        $title = ('yes' === ($settings['kng_show_titles'] ?? 'yes')) ? get_the_title($post) : '';

        $html  = '<div class="king-addons-tb-post-navigation__item king-addons-tb-post-navigation__item--' . esc_attr($type) . '">';
        $html .= '<a href="' . esc_url(get_permalink($post)) . '">';
        if ($label) {
            $html .= '<span class="king-addons-tb-post-navigation__label">' . esc_html($label) . '</span>';
        }
        if ($title) {
            $html .= '<span class="king-addons-tb-post-navigation__title">' . esc_html($title) . '</span>';
        }
        $html .= '</a></div>';

        return $html;
    }
}
