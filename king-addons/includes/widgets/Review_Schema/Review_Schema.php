<?php
/**
 * Review Schema Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders a review/rating block with JSON-LD schema.
 */
class Review_Schema extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-review-schema';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Review Schema', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-review-schema';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-review-schema-style',
        ];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-review-schema-script',
        ];
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
        return ['review', 'rating', 'schema', 'seo'];
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
        $this->register_content_controls();
        $this->register_style_controls();
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
        $rating = (float) ($settings['kng_rating']['size'] ?? 0);
        if ($rating <= 0) {
            return;
        }

        $item_name = $settings['kng_item_name'] ?? '';
        $author = $settings['kng_author'] ?? '';
        $title = $settings['kng_title'] ?? '';
        $body = $settings['kng_body'] ?? '';
        $date = $settings['kng_date'] ?? '';
        $stars_html = $this->render_stars($rating);

        $schema = $this->build_schema($settings);
        ?>
        <div class="king-addons-review">
            <div class="king-addons-review__header">
                <div class="king-addons-review__item"><?php echo esc_html($item_name); ?></div>
                <div class="king-addons-review__rating" aria-label="<?php echo esc_attr($rating); ?>">
                    <?php echo $stars_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <span class="king-addons-review__rating-value"><?php echo esc_html(number_format($rating, 1)); ?>/5</span>
                </div>
            </div>
            <?php if (!empty($title)) : ?>
                <div class="king-addons-review__title"><?php echo esc_html($title); ?></div>
            <?php endif; ?>
            <?php if (!empty($body)) : ?>
                <div class="king-addons-review__body"><?php echo wp_kses_post($body); ?></div>
            <?php endif; ?>
            <div class="king-addons-review__meta">
                <?php if (!empty($author)) : ?>
                    <span class="king-addons-review__author"><?php echo esc_html($author); ?></span>
                <?php endif; ?>
                <?php if (!empty($date)) : ?>
                    <span class="king-addons-review__date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime((string) $date))); ?></span>
                <?php endif; ?>
            </div>
            <script type="application/ld+json">
                <?php echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </script>
        </div>
        <?php
    }

    /**
     * Content controls.
     *
     * @return void
     */
    protected function register_content_controls(): void
    {
        $this->start_controls_section(
            'kng_content_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Review', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'kng_item_name',
            [
                'label' => esc_html__('Item Name', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Product or Service', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_author',
            [
                'label' => esc_html__('Author', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('John Doe', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_rating',
            [
                'label' => esc_html__('Rating', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['custom'],
                'range' => [
                    'custom' => [
                        'min' => 0,
                        'max' => 5,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'size' => 4.5,
                    'unit' => 'custom',
                ],
            ]
        );

        $this->add_control(
            'kng_title',
            [
                'label' => esc_html__('Review Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Great quality and support', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_body',
            [
                'label' => esc_html__('Review Body', 'king-addons'),
                'type' => Controls_Manager::WYSIWYG,
                'default' => esc_html__('The product exceeded expectations and the support team was responsive.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_date',
            [
                'label' => esc_html__('Review Date', 'king-addons'),
                'type' => Controls_Manager::DATE_TIME,
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style controls.
     *
     * @return void
     */
    protected function register_style_controls(): void
    {
        $this->start_controls_section(
            'kng_style_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_item_typo',
                'selector' => '{{WRAPPER}} .king-addons-review__item',
            ]
        );

        $this->add_control(
            'kng_item_color',
            [
                'label' => esc_html__('Item Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-review__item' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typo',
                'selector' => '{{WRAPPER}} .king-addons-review__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-review__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_body_typo',
                'selector' => '{{WRAPPER}} .king-addons-review__body',
            ]
        );

        $this->add_control(
            'kng_body_color',
            [
                'label' => esc_html__('Body Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-review__body' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_meta_typo',
                'selector' => '{{WRAPPER}} .king-addons-review__meta',
            ]
        );

        $this->add_control(
            'kng_meta_color',
            [
                'label' => esc_html__('Meta Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-review__meta' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_card_border',
                'selector' => '{{WRAPPER}} .king-addons-review',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-review',
            ]
        );

        $this->add_control(
            'kng_card_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-review' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-review' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Pro notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_freemius()->can_use_premium_code__premium_only()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'review-schema', [
                'Aggregate rating with count',
                'Multiple reviews and layout options',
                'Custom star icons and animations',
                'Schema type selection (Product/LocalBusiness)',
            ]);
        }
    }

    /**
     * Render stars.
     *
     * @param float $rating Rating.
     *
     * @return string
     */
    protected function render_stars(float $rating): string
    {
        $full = floor($rating);
        $half = ($rating - $full) >= 0.5;
        $html = '<span class="king-addons-review__stars" aria-hidden="true">';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $full) {
                $html .= '<span class="king-addons-review__star is-full">★</span>';
            } elseif ($half && $i === ($full + 1)) {
                $html .= '<span class="king-addons-review__star is-half">★</span>';
            } else {
                $html .= '<span class="king-addons-review__star is-empty">☆</span>';
            }
        }
        $html .= '</span>';
        return $html;
    }

    /**
     * Build schema for single review.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<string, mixed>
     */
    protected function build_schema(array $settings): array
    {
        $rating = (float) ($settings['kng_rating']['size'] ?? 0);
        $author = $settings['kng_author'] ?? '';
        $item_name = $settings['kng_item_name'] ?? '';
        $title = $settings['kng_title'] ?? '';
        $body = $settings['kng_body'] ?? '';
        $date = !empty($settings['kng_date']) ? date_i18n('c', strtotime((string) $settings['kng_date'])) : '';

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'itemReviewed' => [
                '@type' => 'Thing',
                'name' => $item_name,
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $author,
            ],
            'datePublished' => $date,
            'name' => $title,
            'reviewBody' => wp_strip_all_tags($body),
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => number_format($rating, 1, '.', ''),
                'bestRating' => '5',
                'worstRating' => '1',
            ],
        ];
    }
}







