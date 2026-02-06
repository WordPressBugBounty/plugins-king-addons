<?php
/**
 * Theme Builder Archive Posts widget (Free).
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
 * Renders the current archive/search main query.
 */
class TB_Archive_Posts extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-archive-posts';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Archive Posts', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-post-list';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-archive-posts-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-archive-posts-script'];
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
        return ['archive', 'loop', 'posts', 'search', 'king-addons'];
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
            'kng_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => esc_html__('Grid', 'king-addons'),
                    'list' => esc_html__('List', 'king-addons'),
                    'cards' => $is_pro ? esc_html__('Cards (Pro)', 'king-addons') : sprintf(__('Cards %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 1, 'max' => 4, 'step' => 1],
                ],
                'default' => [
                    'size' => 3,
                    'unit' => 'px',
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
                    'size' => 16,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-archive-posts__grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_show_image',
            [
                'label' => esc_html__('Show Featured Image', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_title',
            [
                'label' => esc_html__('Show Title', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_meta',
            [
                'label' => esc_html__('Show Meta', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_excerpt',
            [
                'label' => esc_html__('Show Excerpt', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'kng_show_button',
            [
                'label' => esc_html__('Show Read More', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
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
                'name' => 'kng_title_typography',
                'label' => esc_html__('Title Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tb-archive-posts__title',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_meta_typography',
                'label' => esc_html__('Meta Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tb-archive-posts__meta',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-archive-posts__title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_meta_color',
            [
                'label' => esc_html__('Meta Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-archive-posts__meta' => 'color: {{VALUE}};',
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
            'tb-archive-posts',
            [
                'Card presets and animations',
                'Built-in pagination and skins',
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
        global $wp_query;
        if (!$wp_query instanceof \WP_Query || !$wp_query->have_posts()) {
            return;
        }

        $wp_query->rewind_posts();

        $columns = isset($settings['kng_columns']['size']) ? max(1, (int) $settings['kng_columns']['size']) : 3;
        $layout = $settings['kng_layout'] ?? 'grid';

        $classes = [
            'king-addons-tb-archive-posts',
            'king-addons-tb-archive-posts--' . $layout,
        ];

        echo '<div class="' . esc_attr(implode(' ', $classes)) . '" style="--kng-archive-columns:' . esc_attr((string) $columns) . '">';
        echo '<div class="king-addons-tb-archive-posts__grid">';

        while ($wp_query->have_posts()) {
            $wp_query->the_post();
            $this->render_card($settings);
        }

        echo '</div></div>';

        wp_reset_postdata();
    }

    /**
     * Render single card.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return void
     */
    protected function render_card(array $settings): void
    {
        echo '<article class="king-addons-tb-archive-posts__item">';

        if ('yes' === ($settings['kng_show_image'] ?? 'yes') && has_post_thumbnail()) {
            echo '<a class="king-addons-tb-archive-posts__thumb" href="' . esc_url(get_permalink()) . '">';
            the_post_thumbnail('medium');
            echo '</a>';
        }

        if ('yes' === ($settings['kng_show_title'] ?? 'yes')) {
            echo '<h3 class="king-addons-tb-archive-posts__title"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';
        }

        if ('yes' === ($settings['kng_show_meta'] ?? 'yes')) {
            echo '<div class="king-addons-tb-archive-posts__meta">' . esc_html(get_the_date()) . '</div>';
        }

        if ('yes' === ($settings['kng_show_excerpt'] ?? '')) {
            echo '<div class="king-addons-tb-archive-posts__excerpt">' . esc_html(wp_trim_words(get_the_excerpt(), 18, '…')) . '</div>';
        }

        if ('yes' === ($settings['kng_show_button'] ?? '')) {
            echo '<a class="king-addons-tb-archive-posts__button" href="' . esc_url(get_permalink()) . '">' . esc_html__('Read more', 'king-addons') . '</a>';
        }

        echo '</article>';
    }
}
