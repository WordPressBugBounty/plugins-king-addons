<?php
/**
 * Theme Builder Search Results widget (Free).
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
 * Renders the results of the current search query, with an empty state.
 */
class TB_Search_Results extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-search-results';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Search Results', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-tb-search-results';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-search-results-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-search-results-script'];
    }

    /**
     * Categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons-theme-builder'];
    }

    /**
     * Keywords.
     *
     * @return array<int, string>
     */
    public function get_keywords(): array
    {
        return ['search', 'results', 'query', 'theme builder', 'king-addons'];
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

        $this->add_responsive_control(
            'kng_columns',
            [
                'label' => esc_html__('Columns', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => '1',
                'tablet_default' => '1',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-search-results__list' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
                ],
            ]
        );

        $this->add_control(
            'kng_show_thumbnail',
            [
                'label' => esc_html__('Featured Image', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_excerpt',
            [
                'label' => esc_html__('Excerpt', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_excerpt_length',
            [
                'label' => esc_html__('Excerpt Length', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'default' => 25,
                'min' => 5,
                'max' => 100,
                'condition' => ['kng_show_excerpt' => 'yes'],
            ]
        );

        $this->add_control(
            'kng_show_meta',
            [
                'label' => esc_html__('Date', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_read_more_text',
            [
                'label' => esc_html__('Read More Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => ['active' => true],
                'default' => esc_html__('Read more', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_nothing_found_text',
            [
                'label' => esc_html__('Nothing Found Text', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'dynamic' => ['active' => true],
                'default' => esc_html__('Nothing matched your search. Try a different phrase.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_custom_query',
            [
                'label' => $is_pro ?
                    esc_html__('Custom Query', 'king-addons') :
                    sprintf(__('Custom Query %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('post_type=post&posts_per_page=6', 'king-addons'),
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

        $this->add_responsive_control(
            'kng_gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => ['px' => ['min' => 0, 'max' => 120]],
                'default' => ['unit' => 'px', 'size' => 32],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-search-results__list' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-tb-search-results__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-search-results__title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_excerpt_color',
            [
                'label' => esc_html__('Excerpt Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-search-results__excerpt' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_meta_color',
            [
                'label' => esc_html__('Date Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-search-results__meta' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_empty_color',
            [
                'label' => esc_html__('Nothing Found Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-search-results__empty' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .king-addons-tb-search-results' => 'text-align: {{VALUE}};',
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
            'tb-search-results',
            [
                'Custom query arguments',
                'Filter results by post type',
                'Card and list skins',
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

        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();
        $results_query = $wp_query instanceof \WP_Query ? $wp_query : null;

        $raw_custom = $is_pro ? trim((string) ($settings['kng_custom_query'] ?? '')) : '';
        if ($raw_custom && $results_query instanceof \WP_Query) {
            $extra = [];
            wp_parse_str(str_replace('&amp;', '&', $raw_custom), $extra);
            if (is_array($extra)) {
                unset($extra['post_status'], $extra['suppress_filters']);
                $results_query = new \WP_Query(array_merge($results_query->query_vars, $extra));
            }
        }

        if (!$results_query instanceof \WP_Query || !$results_query->have_posts()) {
            if ($is_editor) {
                Core::renderEditorHint(
                    esc_html__('Search results appear on the search page. Preview this template from a search URL to see them.', 'king-addons')
                );
            }

            $empty = (string) ($settings['kng_nothing_found_text'] ?? '');
            if ('' !== $empty) {
                echo '<div class="king-addons-tb-search-results">'
                    . '<p class="king-addons-tb-search-results__empty">' . esc_html($empty) . '</p>'
                    . '</div>';
            }

            return;
        }

        $show_thumb = 'yes' === ($settings['kng_show_thumbnail'] ?? 'yes');
        $show_excerpt = 'yes' === ($settings['kng_show_excerpt'] ?? 'yes');
        $show_meta = 'yes' === ($settings['kng_show_meta'] ?? 'yes');
        $excerpt_length = max(5, (int) ($settings['kng_excerpt_length'] ?? 25));
        $read_more = (string) ($settings['kng_read_more_text'] ?? '');

        echo '<div class="king-addons-tb-search-results">';
        echo '<div class="king-addons-tb-search-results__list">';

        while ($results_query->have_posts()) {
            $results_query->the_post();

            echo '<article class="king-addons-tb-search-results__item">';

            if ($show_thumb && has_post_thumbnail()) {
                echo '<a class="king-addons-tb-search-results__thumb" href="' . esc_url((string) get_permalink()) . '">';
                the_post_thumbnail('medium');
                echo '</a>';
            }

            echo '<h3 class="king-addons-tb-search-results__title">'
                . '<a href="' . esc_url((string) get_permalink()) . '">' . esc_html((string) get_the_title()) . '</a>'
                . '</h3>';

            if ($show_meta) {
                echo '<div class="king-addons-tb-search-results__meta">' . esc_html((string) get_the_date()) . '</div>';
            }

            if ($show_excerpt) {
                $excerpt = wp_trim_words((string) get_the_excerpt(), $excerpt_length, '…');
                if ('' !== $excerpt) {
                    echo '<div class="king-addons-tb-search-results__excerpt">' . esc_html($excerpt) . '</div>';
                }
            }

            if ('' !== $read_more) {
                echo '<a class="king-addons-tb-search-results__more" href="' . esc_url((string) get_permalink()) . '">'
                    . esc_html($read_more) . '</a>';
            }

            echo '</article>';
        }

        echo '</div>';
        echo '</div>';

        wp_reset_postdata();
    }
}
