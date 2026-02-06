<?php
/**
 * Event Calendar Widget (Free).
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
 * Renders an event calendar list.
 */
class Event_Calendar extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'king-addons-event-calendar';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Event Calendar', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-event-calendar';
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-event-calendar-style',
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
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-event-calendar-script',
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
        return ['event', 'calendar', 'schedule', 'list'];
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
        $this->register_style_card_controls();
        $this->register_style_text_controls();
        $this->register_style_badge_controls();
        $this->register_pro_controls();
        $this->register_pro_notice_controls();
    }

    /**
     * Register Pro-only controls placeholder.
     *
     * The Pro version overrides this method to add premium controls without
     * overriding the full `register_controls()` flow.
     *
     * @return void
     */
    public function register_pro_controls(): void
    {
        // Intentionally empty in Free.
    }

    /**
     * Filter wrapper attributes for the widget output.
     *
     * @param array<string, string> $attributes Wrapper attributes.
     * @param array<string, mixed>  $settings   Widget settings.
     * @param array<int, array<string, mixed>> $events Events list.
     *
     * @return array<string, string>
     */
    public function filter_wrapper_attributes(array $attributes, array $settings, array $events): array
    {
        return $attributes;
    }

    /**
     * Render Pro-only header content placeholder.
     *
     * @param array<string, mixed>  $settings Widget settings.
     * @param array<int, array<string, mixed>> $events Events list.
     *
     * @return void
     */
    public function render_pro_header(array $settings, array $events): void
    {
        // Intentionally empty in Free.
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    public function render(): void
    {
        $settings = $this->get_settings_for_display();
        $events = $this->get_events($settings);

        if (empty($events)) {
            return;
        }

        $attributes = [
            'class' => 'king-addons-event-calendar',
            'data-has-filters' => 'no',
        ];
        $attributes = $this->filter_wrapper_attributes($attributes, $settings, $events);

        $attr_pairs = [];
        foreach ($attributes as $key => $value) {
            $attr_pairs[] = esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        ?>
        <div <?php echo implode(' ', $attr_pairs); ?>>
            <?php $this->render_pro_header($settings, $events); ?>
            <div class="king-addons-event-calendar__list">
                <?php foreach ($events as $event) : ?>
                    <?php $this->render_event($event, $settings); ?>
                <?php endforeach; ?>
            </div>
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
            'kng_events_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Events', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'kng_event_title',
            [
                'label' => esc_html__('Title', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Event title', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_event_date',
            [
                'label' => esc_html__('Start Date & Time', 'king-addons'),
                'type' => Controls_Manager::DATE_TIME,
                'picker_options' => ['enableTime' => true],
            ]
        );

        $repeater->add_control(
            'kng_event_end_date',
            [
                'label' => esc_html__('End Date & Time', 'king-addons'),
                'type' => Controls_Manager::DATE_TIME,
                'picker_options' => ['enableTime' => true],
            ]
        );

        $repeater->add_control(
            'kng_event_time_label',
            [
                'label' => esc_html__('Time Label', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('10:00 - 12:00', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_event_location',
            [
                'label' => esc_html__('Location', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Main Hall', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_event_category',
            [
                'label' => esc_html__('Category', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Conference', 'king-addons'),
            ]
        );

        $repeater->add_control(
            'kng_event_color',
            [
                'label' => esc_html__('Category Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
            ]
        );

        $repeater->add_control(
            'kng_event_description',
            [
                'label' => esc_html__('Description', 'king-addons'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 3,
            ]
        );

        $repeater->add_control(
            'kng_event_link',
            [
                'label' => esc_html__('Event Link', 'king-addons'),
                'type' => Controls_Manager::URL,
                'placeholder' => esc_html__('https://', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_events',
            [
                'label' => esc_html__('Events', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
            ]
        );

        $this->add_control(
            'kng_cta_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('View details', 'king-addons'),
            ]
        );

        $this->add_control(
            'kng_show_button',
            [
                'label' => esc_html__('Show Button', 'king-addons'),
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

        $this->add_control(
            'kng_show_description',
            [
                'label' => esc_html__('Show Description', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_location',
            [
                'label' => esc_html__('Show Location', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'kng_show_time',
            [
                'label' => esc_html__('Show Time', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Card style controls.
     *
     * @return void
     */
    protected function register_style_card_controls(): void
    {
        $this->start_controls_section(
            'kng_style_card_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Card', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'kng_card_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-event-calendar__card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'kng_card_border',
                'selector' => '{{WRAPPER}} .king-addons-event-calendar__card',
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
                    '{{WRAPPER}} .king-addons-event-calendar__card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'kng_card_shadow',
                'selector' => '{{WRAPPER}} .king-addons-event-calendar__card',
            ]
        );

        $this->add_responsive_control(
            'kng_card_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-event-calendar__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Text style controls.
     *
     * @return void
     */
    protected function register_style_text_controls(): void
    {
        $this->start_controls_section(
            'kng_style_text_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Text', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_title_typography',
                'selector' => '{{WRAPPER}} .king-addons-event-calendar__title',
            ]
        );

        $this->add_control(
            'kng_title_color',
            [
                'label' => esc_html__('Title Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-event-calendar__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_meta_typography',
                'selector' => '{{WRAPPER}} .king-addons-event-calendar__meta',
            ]
        );

        $this->add_control(
            'kng_meta_color',
            [
                'label' => esc_html__('Meta Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-event-calendar__meta' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_desc_typography',
                'selector' => '{{WRAPPER}} .king-addons-event-calendar__description',
            ]
        );

        $this->add_control(
            'kng_desc_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-event-calendar__description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_button_typography',
                'selector' => '{{WRAPPER}} .king-addons-event-calendar__button',
            ]
        );

        $this->add_control(
            'kng_button_color',
            [
                'label' => esc_html__('Button Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-event-calendar__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_bg',
            [
                'label' => esc_html__('Button Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-event-calendar__button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_button_radius',
            [
                'label' => esc_html__('Button Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-event-calendar__button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Badge style controls.
     *
     * @return void
     */
    protected function register_style_badge_controls(): void
    {
        $this->start_controls_section(
            'kng_style_badge_section',
            [
                'label' => KING_ADDONS_ELEMENTOR_ICON . esc_html__('Date Badge', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'kng_badge_typography',
                'selector' => '{{WRAPPER}} .king-addons-event-calendar__badge',
            ]
        );

        $this->add_control(
            'kng_badge_text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-event-calendar__badge' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_badge_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .king-addons-event-calendar__badge' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'kng_badge_radius',
            [
                'label' => esc_html__('Radius', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 30],
                    '%' => ['min' => 0, 'max' => 100],
                ],
                'selectors' => [
                    '{{WRAPPER}} .king-addons-event-calendar__badge' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render pro notice.
     *
     * @return void
     */
    public function register_pro_notice_controls(): void
    {
        if (!king_addons_can_use_pro()) {
            Core::renderProFeaturesSection($this, '', Controls_Manager::RAW_HTML, 'event-calendar', [
                'Filters (category, search, upcoming only)',
                'Date range filter and compact layout',
                'Hover animations and category chips',
                'Highlight ongoing events',
            ]);
        }
    }

    /**
     * Get events parsed and sorted.
     *
     * @param array<string, mixed> $settings Settings.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function get_events(array $settings): array
    {
        $events = [];
        if (empty($settings['kng_events']) || !is_array($settings['kng_events'])) {
            return $events;
        }

        foreach ($settings['kng_events'] as $item) {
            $start = isset($item['kng_event_date']) ? strtotime((string) $item['kng_event_date']) : null;
            $end = isset($item['kng_event_end_date']) ? strtotime((string) $item['kng_event_end_date']) : null;

            $events[] = [
                'title' => $item['kng_event_title'] ?? '',
                'start' => $start,
                'end' => $end,
                'time_label' => $item['kng_event_time_label'] ?? '',
                'location' => $item['kng_event_location'] ?? '',
                'category' => $item['kng_event_category'] ?? '',
                'color' => $item['kng_event_color'] ?? '',
                'description' => $item['kng_event_description'] ?? '',
                'link' => $item['kng_event_link'] ?? [],
            ];
        }

        usort(
            $events,
            static function ($a, $b) {
                return ($a['start'] ?? 0) <=> ($b['start'] ?? 0);
            }
        );

        return $events;
    }

    /**
     * Render single event card.
     *
     * @param array<string, mixed> $event    Event data.
     * @param array<string, mixed> $settings Settings.
     *
     * @return void
     */
    protected function render_event(array $event, array $settings): void
    {
        $this->render_event_base($event, $settings);
    }

    /**
     * Render single event card base implementation.
     *
     * This method exists to allow the Pro version to extend event rendering
     * without calling `parent::` methods.
     *
     * @param array<string, mixed> $event    Event data.
     * @param array<string, mixed> $settings Settings.
     *
     * @return void
     */
    protected function render_event_base(array $event, array $settings): void
    {
        $start_ts = $event['start'] ?? null;
        $end_ts = $event['end'] ?? null;
        $date_day = $start_ts ? date_i18n('d', $start_ts) : '';
        $date_month = $start_ts ? date_i18n('M', $start_ts) : '';
        $time_label = $event['time_label'] ?: ($start_ts ? date_i18n('H:i', $start_ts) : '');

        $link = $event['link']['url'] ?? '';
        $link_attrs = '';
        if (!empty($link)) {
            $is_external = !empty($event['link']['is_external']);
            $nofollow = !empty($event['link']['nofollow']);
            $rels = [];
            if ($is_external) {
                $rels[] = 'noopener';
                $rels[] = 'noreferrer';
            }
            if ($nofollow) {
                $rels[] = 'nofollow';
            }
            $rel_attr = !empty($rels) ? ' rel="' . esc_attr(implode(' ', array_unique($rels))) . '"' : '';
            $target_attr = $is_external ? ' target="_blank"' : '';
            $link_attrs = 'href="' . esc_url($link) . '"' . $target_attr . $rel_attr;
        }

        $item_classes = $event['item_classes'] ?? ['king-addons-event-calendar__item'];
        $card_classes = $event['card_classes'] ?? ['king-addons-event-calendar__card'];
        ?>
        <article class="<?php echo esc_attr(implode(' ', $item_classes)); ?>"
            data-date-start="<?php echo esc_attr($start_ts ?? ''); ?>"
            data-date-end="<?php echo esc_attr($end_ts ?? ''); ?>"
            data-category="<?php echo esc_attr($event['category']); ?>"
            data-title="<?php echo esc_attr($event['title']); ?>">
            <div class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
                <div class="king-addons-event-calendar__badge" style="<?php echo $event['color'] ? 'background-color:' . esc_attr($event['color']) . ';' : ''; ?>">
                    <span class="king-addons-event-calendar__badge-day"><?php echo esc_html($date_day); ?></span>
                    <span class="king-addons-event-calendar__badge-month"><?php echo esc_html($date_month); ?></span>
                </div>
                <div class="king-addons-event-calendar__body">
                    <div class="king-addons-event-calendar__header">
                        <h3 class="king-addons-event-calendar__title"><?php echo esc_html($event['title']); ?></h3>
                        <?php if (!empty($event['category'])) : ?>
                            <span class="king-addons-event-calendar__chip" style="<?php echo $event['color'] ? 'color:' . esc_attr($event['color']) . ';border-color:' . esc_attr($event['color']) . ';' : ''; ?>">
                                <?php echo esc_html($event['category']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="king-addons-event-calendar__meta">
                        <?php if (($settings['kng_show_time'] ?? 'yes') === 'yes' && ($time_label || $end_ts)) : ?>
                            <span class="king-addons-event-calendar__meta-item">
                                <?php echo esc_html($time_label); ?>
                                <?php if ($end_ts) : ?>
                                    <?php echo ' - ' . esc_html(date_i18n('H:i', $end_ts)); ?>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <?php if (($settings['kng_show_location'] ?? 'yes') === 'yes' && !empty($event['location'])) : ?>
                            <span class="king-addons-event-calendar__meta-item">
                                <?php echo esc_html($event['location']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (($settings['kng_show_description'] ?? 'yes') === 'yes' && !empty($event['description'])) : ?>
                        <div class="king-addons-event-calendar__description">
                            <?php echo esc_html($event['description']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (($settings['kng_show_button'] ?? 'yes') === 'yes' && !empty($link_attrs)) : ?>
                        <div class="king-addons-event-calendar__actions">
                            <a class="king-addons-event-calendar__button" <?php echo $link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                                <?php echo esc_html($settings['kng_cta_text'] ?? esc_html__('View details', 'king-addons')); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </article>
        <?php
    }
}







