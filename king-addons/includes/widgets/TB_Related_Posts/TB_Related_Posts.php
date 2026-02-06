<?php
/**
 * Theme Builder Related Posts widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays related posts grid.
 */
class TB_Related_Posts extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-related-posts';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Related Posts', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-posts-grid';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-related-posts-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-related-posts-script'];
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
        return ['related', 'posts', 'grid', 'theme builder', 'king-addons'];
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
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Query', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_source',
            [
                'label' => esc_html__('Source', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'categories',
                'options' => [
                    'categories' => esc_html__('Same Categories', 'king-addons'),
                    'tags' => $is_pro ? esc_html__('Same Tags', 'king-addons') : sprintf(__('Same Tags %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                    'taxonomy' => $is_pro ? esc_html__('Same Taxonomy', 'king-addons') : sprintf(__('Same Taxonomy %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
            ]
        );

        $this->add_control(
            'kng_taxonomy_slug',
            [
                'label' => esc_html__('Taxonomy Slug', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('portfolio_cat', 'king-addons'),
                'condition' => [
                    'kng_source' => 'taxonomy',
                ],
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        $this->add_control(
            'kng_posts_per_page',
            [
                'label' => esc_html__('Posts Per Page', 'king-addons'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 12,
                'default' => 3,
            ]
        );

        $this->add_control(
            'kng_order',
            [
                'label' => esc_html__('Order', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date' => esc_html__('Date', 'king-addons'),
                    'rand' => esc_html__('Random', 'king-addons'),
                    'comment_count' => $is_pro ? esc_html__('Comment Count', 'king-addons') : sprintf(__('Comment Count %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
            ]
        );

        $this->add_control(
            'kng_exclude_current',
            [
                'label' => esc_html__('Exclude Current Post', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_fallback',
            [
                'label' => $is_pro ?
                    esc_html__('Fallback', 'king-addons') :
                    sprintf(__('Fallback %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'default' => 'hide',
                'options' => [
                    'hide' => esc_html__('Hide Widget', 'king-addons'),
                    'latest' => esc_html__('Show Latest Posts', 'king-addons'),
                ],
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'kng_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
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
                'selector' => '{{WRAPPER}} .king-addons-tb-related-posts__title',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_meta_typography',
                'label' => esc_html__('Meta Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tb-related-posts__meta',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-related-posts__title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_meta_color',
            [
                'label' => esc_html__('Meta Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-related-posts__meta' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_gap',
            [
                'label' => esc_html__('Grid Gap', 'king-addons'),
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
                    '{{WRAPPER}} .king-addons-tb-related-posts__grid' => 'gap: {{SIZE}}{{UNIT}};',
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
            'tb-related-posts',
            [
                'Tag or custom taxonomy sources',
                'Comment count ordering and fallbacks',
                'Advanced hover and overlay styles',
            ]
        );
    }

    /**
     * Render output helper.
     *
     * @param array<string, mixed> $settings Settings.
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

        $query = $this->build_query($post, $settings, $is_pro);
        if (!$query->have_posts()) {
            if ($is_pro && 'latest' === ($settings['kng_fallback'] ?? 'hide')) {
                wp_reset_postdata();
                $fallback_args = [
                    'post_type' => get_post_type($post),
                    'posts_per_page' => (int) ($settings['kng_posts_per_page'] ?? 3),
                    'post__not_in' => ('yes' === ($settings['kng_exclude_current'] ?? 'yes')) ? [$post->ID] : [],
                ];
                $query = new WP_Query($fallback_args);
            }
        }

        if (!$query->have_posts()) {
            wp_reset_postdata();
            return;
        }

        $columns = isset($settings['kng_columns']['size']) ? max(1, (int) $settings['kng_columns']['size']) : 3;

        echo '<div class="king-addons-tb-related-posts" style="--kng-related-columns:' . esc_attr((string) $columns) . '">';
        echo '<div class="king-addons-tb-related-posts__grid">';
        while ($query->have_posts()) {
            $query->the_post();
            $this->render_card($settings);
        }
        echo '</div></div>';

        wp_reset_postdata();
    }

    /**
     * Build query.
     *
     * @param \WP_Post $post     Current post.
     * @param array    $settings Settings.
     * @param bool     $is_pro   Pro flag.
     *
     * @return WP_Query
     */
    protected function build_query(\WP_Post $post, array $settings, bool $is_pro): WP_Query
    {
        $args = [
            'post_type' => get_post_type($post),
            'posts_per_page' => (int) ($settings['kng_posts_per_page'] ?? 3),
            'ignore_sticky_posts' => true,
        ];

        if ('yes' === ($settings['kng_exclude_current'] ?? 'yes')) {
            $args['post__not_in'] = [$post->ID];
        }

        $orderby = $settings['kng_order'] ?? 'date';
        if ('comment_count' === $orderby && !$is_pro) {
            $orderby = 'date';
        }
        $args['orderby'] = $orderby;

        $source = $settings['kng_source'] ?? 'categories';
        $tax_query = [];
        if ('categories' === $source) {
            $terms = wp_get_post_categories($post->ID);
            if (!empty($terms)) {
                $tax_query[] = [
                    'taxonomy' => 'category',
                    'field' => 'term_id',
                    'terms' => $terms,
                ];
            }
        } elseif ('tags' === $source && $is_pro) {
            $terms = wp_get_post_tags($post->ID, ['fields' => 'ids']);
            if (!empty($terms)) {
                $tax_query[] = [
                    'taxonomy' => 'post_tag',
                    'field' => 'term_id',
                    'terms' => $terms,
                ];
            }
        } elseif ('taxonomy' === $source && $is_pro && !empty($settings['kng_taxonomy_slug'])) {
            $slug = sanitize_key($settings['kng_taxonomy_slug']);
            $terms = wp_get_object_terms($post->ID, $slug, ['fields' => 'ids']);
            if (!empty($terms) && !is_wp_error($terms)) {
                $tax_query[] = [
                    'taxonomy' => $slug,
                    'field' => 'term_id',
                    'terms' => $terms,
                ];
            }
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        return new WP_Query($args);
    }

    /**
     * Render card.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return void
     */
    protected function render_card(array $settings): void
    {
        echo '<article class="king-addons-tb-related-posts__item">';

        if ('yes' === ($settings['kng_show_image'] ?? 'yes') && has_post_thumbnail()) {
            echo '<a class="king-addons-tb-related-posts__thumb" href="' . esc_url(get_permalink()) . '">';
            the_post_thumbnail('medium');
            echo '</a>';
        }

        if ('yes' === ($settings['kng_show_title'] ?? 'yes')) {
            echo '<h3 class="king-addons-tb-related-posts__title"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';
        }

        if ('yes' === ($settings['kng_show_meta'] ?? 'yes')) {
            echo '<div class="king-addons-tb-related-posts__meta">' . esc_html(get_the_date()) . ' · ' . esc_html(get_the_author()) . '</div>';
        }

        if ('yes' === ($settings['kng_show_excerpt'] ?? '')) {
            echo '<div class="king-addons-tb-related-posts__excerpt">' . esc_html(wp_trim_words(get_the_excerpt(), 18, '…')) . '</div>';
        }

        if ('yes' === ($settings['kng_show_button'] ?? '')) {
            echo '<a class="king-addons-tb-related-posts__button" href="' . esc_url(get_permalink()) . '">' . esc_html__('Read more', 'king-addons') . '</a>';
        }

        echo '</article>';
    }
}
