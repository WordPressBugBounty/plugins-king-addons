<?php
/**
 * FAQ Schema Widget (Free).
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders FAQ accordion with JSON-LD schema.
 */
class FAQ_Schema extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-faq-schema';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('FAQ Schema', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-faq-schema';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-faq-schema-style',
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-faq-schema-script',
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
        return ['faq', 'schema', 'accordion', 'seo'];
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
        $this->register_layout_controls();
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
        $faqs = $this->get_faq_items($settings);

        if (empty($faqs)) {
            return;
        }

        $schema = $this->build_schema($faqs);
        $is_first_open = ($settings['kng_first_open'] ?? 'yes') === 'yes';
        $animate = 'slide';
        $base_id = 'kng-faq-' . $this->get_id();
        ?>
        <div class="king-addons-faq" data-single="no" data-animate="<?php echo esc_attr($animate); ?>">
            <div class="king-addons-faq__list">
                <?php foreach ($faqs as $index => $item) : ?>
                    <?php
                    $is_open = $is_first_open && $index === 0;
                    $item_id = $base_id . '-' . $index;
                    $question_id = $item_id . '-question';
                    $answer_id = $item_id . '-answer';
                    $item_classes = ['king-addons-faq__item', 'is-anim-' . $animate];
                    $answer_style = $is_open ? ' style="display:block;"' : '';
                    $answer_hidden = $is_open ? 'false' : 'true';
                    ?>
                    <div class="<?php echo esc_attr(implode(' ', $item_classes)); ?>">
                        <button id="<?php echo esc_attr($question_id); ?>" class="king-addons-faq__question" type="button" aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr($answer_id); ?>">
                            <span class="king-addons-faq__question-text"><?php echo esc_html($item['question']); ?></span>
                            <span class="king-addons-faq__icon" aria-hidden="true">+</span>
                            <span class="king-addons-faq__icon king-addons-faq__icon--close" aria-hidden="true">-</span>
                        </button>
                        <div id="<?php echo esc_attr($answer_id); ?>" class="king-addons-faq__answer" aria-hidden="<?php echo esc_attr($answer_hidden); ?>" aria-labelledby="<?php echo esc_attr($question_id); ?>"<?php echo $answer_style; ?>>
                            <?php echo wp_kses_post($item['answer']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
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
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('FAQ Items', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_question',
            [
                'label' => esc_html__('Question', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('What is your return policy?', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_answer',
            [
                'label' => esc_html__('Answer', 'king-addons'),
                'type' => Controls_Manager::WYSIWYG,
                'default' => esc_html__('We offer a 30-day return policy on all items.', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_items',
            [
                'label' => esc_html__('Items', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
            ]
        );

        $this->add_control(
            'kng_first_open',
            [
                'label' => esc_html__('Open First Item', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Layout controls.
     *
     * @return void
     */
    protected function register_layout_controls(): void
    {
        $this->start_controls_section(
            'kng_layout_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Layout', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'kng_item_spacing',
            [
                'label' => esc_html__('Item Spacing', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-faq__list' => '--kng-faq-gap: {{SIZE}}{{UNIT}};',
                ],
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
                'name' => 'kng_question_typo',
                'selector' => '{{WRAPPER}} .king-addons-faq__question',
            ]
        );

        $this->add_control(
            'kng_question_color',
            [
                'label' => esc_html__('Question Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-faq__question' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_answer_typo',
                'selector' => '{{WRAPPER}} .king-addons-faq__answer',
            ]
        );

        $this->add_control(
            'kng_answer_color',
            [
                'label' => esc_html__('Answer Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-faq__answer' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_heading',
            [
                'label' => esc_html__('Icon', 'king-addons'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'kng_icon_color',
            [
                'label' => esc_html__('Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-faq__icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_background',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-faq__icon' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_icon_border',
                'selector' => '{{WRAPPER}} .king-addons-faq__icon',
            ]
        );

        $this->add_responsive_control(
            'kng_icon_box_size',
            [
                'label' => esc_html__('Box Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 12, 'max' => 60],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-faq__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'kng_icon_size',
            [
                'label' => esc_html__('Icon Size', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => ['min' => 8, 'max' => 32],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-faq__icon' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_icon_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-faq__icon' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'kng_card_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-faq__item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_card_border',
                'selector' => '{{WRAPPER}} .king-addons-faq__item',
            ]
        );

        $this->add_control(
            'kng_card_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 30],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-faq__item' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-faq__item',
            ]
        );

        $this->add_responsive_control(
            'kng_card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-faq__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'faq-schema', [
                'Single-open mode and expand/collapse all',
                'Custom open/close icons and animations',
                'Search/filter bar and numbering',
                'Disable schema or add custom schema type',
            ]);
        }
    }

    /**
     * Prepare FAQ data.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<int, array<string, string>>
     */
    protected function get_faq_items(array $settings): array
    {
        $items = [];
        if (empty($settings['kng_items']) || !is_array($settings['kng_items'])) {
            return $items;
        }

        foreach ($settings['kng_items'] as $item) {
            $question = trim((string) ($item['kng_question'] ?? ''));
            $answer = $item['kng_answer'] ?? '';
            if ($question === '' || $answer === '') {
                continue;
            }
            $items[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $items;
    }

    /**
     * Build schema.org FAQPage data.
     *
     * @param array<int, array<string, string>> $faqs FAQs.
     *
     * @return array<string, mixed>
     */
    protected function build_schema(array $faqs): array
    {
        $main_entity = [];
        foreach ($faqs as $item) {
            $main_entity[] = [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => wp_strip_all_tags($item['answer']),
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $main_entity,
        ];
    }
}




