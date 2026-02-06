<?php
/**
 * Woo Product Rating widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays product rating and count.
 */
class Woo_Product_Rating extends Abstract_Single_Widget
{
    public function get_name(): string
    {
        return 'woo_product_rating';
    }

    public function get_title(): string
    {
        return esc_html__('Product Rating', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-star';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-rating-style'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Content', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'layout',
            [
                'label' => esc_html__('Layout', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'stars' => esc_html__('Stars', 'king-addons'),
                    'stars_count' => esc_html__('Stars + count', 'king-addons'),
                    'text' => sprintf(__('Text only %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                ],
                'default' => 'stars_count',
            ]
        );

        $this->add_control(
            'count_text',
            [
                'label' => esc_html__('Count text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '({count} reviews)',
            ]
        );

        $this->add_control(
            'text_template',
            [
                'label' => sprintf(__('Text template %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'default' => '{rating} / {max} ({count})',
                'description' => esc_html__('Placeholders: {rating}, {max}, {count}', 'king-addons'),
                'condition' => [
                    'layout' => 'text',
                ],
            ]
        );

        $this->add_control(
            'link_to_reviews',
            [
                'label' => sprintf(__('Link to reviews anchor %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'reviews_anchor',
            [
                'label' => esc_html__('Reviews anchor (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => '#reviews',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'star_size',
            [
                'label' => esc_html__('Star Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 10, 'max' => 48],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-rating .star-rating' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'star_color',
            [
                'label' => esc_html__('Filled Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-rating .star-rating span:before' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'star_empty_color',
            [
                'label' => esc_html__('Empty Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-rating .star-rating:before' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'count_typography',
                'selector' => '{{WRAPPER}} .ka-woo-product-rating__count',
                'label' => esc_html__('Count Typography', 'king-addons'),
            ]
        );

        $this->add_control(
            'count_color',
            [
                'label' => esc_html__('Count Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-rating__count' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'gap',
            [
                'label' => esc_html__('Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 30],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-rating' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'margin',
            [
                'label' => esc_html__('Margin', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-rating' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $product = $this->get_product();
        if (!$product) {
            $this->render_missing_product_notice();
            return;
        }

        if (!wc_review_ratings_enabled()) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $layout = $settings['layout'] ?? 'stars_count';
        $can_pro = king_addons_can_use_pro();

        if ('text' === $layout && !$can_pro) {
            $layout = 'stars_count';
        }

        $rating = (float) $product->get_average_rating();
        $count = (int) $product->get_rating_count();

        if ($count === 0) {
            return;
        }

        $this->add_render_attribute('wrapper', 'class', 'ka-woo-product-rating');

        $link_open = '';
        $link_close = '';
        if (!empty($settings['link_to_reviews']) && $can_pro) {
            $anchor = $settings['reviews_anchor'] ?: '#reviews';
            $link_open = '<a class="ka-woo-product-rating__link" href="' . esc_url($anchor) . '">';
            $link_close = '</a>';
        }

        echo '<div ' . $this->get_render_attribute_string('wrapper') . '>';

        if ('text' === $layout) {
            $tpl = $settings['text_template'] ?? '{rating} / {max} ({count})';
            $text = str_replace(
                ['{rating}', '{max}', '{count}'],
                [number_format($rating, 1), '5', (string) $count],
                $tpl
            );
            echo $link_open . '<span class="ka-woo-product-rating__text">' . esc_html($text) . '</span>' . $link_close;
        } else {
            // stars
            $html = wc_get_rating_html($rating, $count);
            echo $link_open . $html . $link_close; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            if ('stars_count' === $layout) {
                $count_text = $settings['count_text'] ?: '({count} reviews)';
                $count_text = str_replace('{count}', (string) $count, $count_text);
                echo '<span class="ka-woo-product-rating__count">' . esc_html($count_text) . '</span>';
            }
        }

        echo '</div>';
    }
}







