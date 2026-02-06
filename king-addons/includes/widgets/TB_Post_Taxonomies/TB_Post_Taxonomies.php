<?php
/**
 * Theme Builder Post Taxonomies widget (Free).
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
 * Lists taxonomy terms for the current post.
 */
class TB_Post_Taxonomies extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-tb-post-taxonomies';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('TB - Post Taxonomies', 'king-addons');
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
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-post-taxonomies-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-tb-post-taxonomies-script'];
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
        return ['taxonomy', 'categories', 'tags', 'terms', 'king-addons'];
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
            'kng_taxonomy',
            [
                'label' => esc_html__('Taxonomy', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'category',
                'options' => [
                    'category' => esc_html__('Categories', 'king-addons'),
                    'post_tag' => esc_html__('Tags', 'king-addons'),
                    'custom' => $is_pro ?
                        esc_html__('Custom Taxonomy', 'king-addons') :
                        sprintf(__('Custom Taxonomy %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
            ]
        );

        $this->add_control(
            'kng_custom_taxonomy',
            [
                'label' => esc_html__('Custom Taxonomy Slug', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('product_cat', 'king-addons'),
                'condition' => [
                    'kng_taxonomy' => 'custom',
                ],
                'classes' => $is_pro ? '' : 'king-addons-pro-control',
            ]
        );

        $this->add_control(
            'kng_show_label',
            [
                'label' => esc_html__('Show Label', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_label_text',
            [
                'label' => esc_html__('Label Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Categories:', 'king-addons'),
                'condition' => [
                    'kng_show_label' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'kng_link_terms',
            [
                'label' => esc_html__('Link Terms', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_separator',
            [
                'label' => esc_html__('Separator', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => ', ',
            ]
        );

        $this->add_control(
            'kng_empty_behavior',
            [
                'label' => esc_html__('Empty Behavior', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'default' => 'hide',
                'options' => [
                    'hide' => esc_html__('Hide Widget', 'king-addons'),
                    'placeholder' => $is_pro ?
                        esc_html__('Show Placeholder', 'king-addons') :
                        sprintf(__('Show Placeholder %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
            ]
        );

        $this->add_control(
            'kng_placeholder_text',
            [
                'label' => esc_html__('Placeholder Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('No terms found.', 'king-addons'),
                'condition' => [
                    'kng_empty_behavior' => 'placeholder',
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
                'name' => 'kng_label_typography',
                'label' => esc_html__('Label Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tb-post-taxonomies__label',
            ]
        );

        $this->add_control(
            'kng_label_color',
            [
                'label' => esc_html__('Label Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-taxonomies__label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_terms_typography',
                'label' => esc_html__('Terms Typography', 'king-addons'),
                'selector' => '{{WRAPPER}} .king-addons-tb-post-taxonomies__terms, {{WRAPPER}} .king-addons-tb-post-taxonomies__terms a',
            ]
        );

        $this->add_control(
            'kng_terms_color',
            [
                'label' => esc_html__('Terms Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-taxonomies__terms' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .king-addons-tb-post-taxonomies__terms a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_terms_color_hover',
            [
                'label' => esc_html__('Terms Hover Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-taxonomies__terms a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_spacing',
            [
                'label' => esc_html__('Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 50],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-tb-post-taxonomies' => 'gap: {{SIZE}}{{UNIT}};',
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
            'tb-post-taxonomies',
            [
                'Custom taxonomy selection',
                'Placeholder when terms are missing',
                'Badge/chip presentation for terms',
            ]
        );
    }

    /**
     * Render output helper.
     *
     * @param array<string, mixed> $settings Widget settings.
     * @param bool                 $is_pro   Whether Pro mode is enabled.
     *
     * @return void
     */
    protected function render_output(array $settings, bool $is_pro): void
    {
        $post = get_post();
        if (!$post instanceof \WP_Post) {
            return;
        }

        $taxonomy = $settings['kng_taxonomy'] ?? 'category';
        if ('custom' === $taxonomy && $is_pro && !empty($settings['kng_custom_taxonomy'])) {
            $taxonomy = sanitize_key($settings['kng_custom_taxonomy']);
        } elseif ('custom' === $taxonomy) {
            $taxonomy = 'category';
        }

        $terms = get_the_terms($post->ID, $taxonomy);

        if (empty($terms) || is_wp_error($terms)) {
            if ('placeholder' === ($settings['kng_empty_behavior'] ?? 'hide') && $is_pro) {
                $placeholder = $settings['kng_placeholder_text'] ?? '';
                if ($placeholder) {
                    echo '<div class="king-addons-tb-post-taxonomies"><div class="king-addons-tb-post-taxonomies__placeholder">' . esc_html($placeholder) . '</div></div>';
                }
            }
            return;
        }

        $label = '';
        if ('yes' === ($settings['kng_show_label'] ?? 'yes')) {
            $label = '<span class="king-addons-tb-post-taxonomies__label">' . esc_html($settings['kng_label_text'] ?? '') . '</span>';
        }

        $separator = (string) ($settings['kng_separator'] ?? ', ');
        $link_terms = 'yes' === ($settings['kng_link_terms'] ?? 'yes');

        $term_parts = [];
        foreach ($terms as $term) {
            $name = esc_html($term->name);
            if ($link_terms) {
                $url = get_term_link($term);
                if (!is_wp_error($url)) {
                    $term_parts[] = '<a href="' . esc_url($url) . '">' . $name . '</a>';
                    continue;
                }
            }
            $term_parts[] = $name;
        }

        $terms_html = implode($separator, $term_parts);

        echo '<div class="king-addons-tb-post-taxonomies">';
        echo $label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<span class="king-addons-tb-post-taxonomies__terms">' . wp_kses_post($terms_html) . '</span>';
        echo '</div>';
    }
}
