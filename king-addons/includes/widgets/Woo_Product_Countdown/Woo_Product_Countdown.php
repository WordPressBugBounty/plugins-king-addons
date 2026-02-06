<?php
/**
 * Woo Product Countdown widget.
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
 * Displays countdown until sale ends.
 */
class Woo_Product_Countdown extends Abstract_Single_Widget
{
    public function get_name(): string
    {
        return 'woo_product_countdown';
    }

    public function get_title(): string
    {
        return esc_html__('Product Sale Countdown', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-countdown';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-countdown-style'];
    }

    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-countdown-script'];
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
            'custom_date',
            [
                'label' => sprintf(__('Custom end date %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::DATE_TIME,
            ]
        );

        $this->add_control(
            'display_format',
            [
                'label' => sprintf(__('Display format %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'blocks' => esc_html__('Blocks', 'king-addons'),
                    'inline' => esc_html__('Inline (Pro)', 'king-addons'),
                ],
                'default' => 'blocks',
            ]
        );

        $this->add_control(
            'show_seconds',
            [
                'label' => sprintf(__('Show seconds %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'expired_behavior',
            [
                'label' => sprintf(__('After expire %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'hide' => esc_html__('Hide', 'king-addons'),
                    'text' => esc_html__('Show text', 'king-addons'),
                    'zero' => esc_html__('Freeze at 0', 'king-addons'),
                ],
                'default' => 'hide',
            ]
        );

        $this->add_control(
            'expired_text',
            [
                'label' => esc_html__('Expired text (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Offer ended', 'king-addons'),
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

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'numbers_typo',
                'selector' => '{{WRAPPER}} .ka-woo-countdown__number',
                'label' => esc_html__('Numbers Typography', 'king-addons'),
            ]
        );

        $this->add_control(
            'numbers_color',
            [
                'label' => esc_html__('Numbers Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-countdown__number' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'labels_typo',
                'selector' => '{{WRAPPER}} .ka-woo-countdown__label',
                'label' => esc_html__('Labels Typography', 'king-addons'),
            ]
        );

        $this->add_control(
            'labels_color',
            [
                'label' => esc_html__('Labels Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-countdown__label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'gap',
            [
                'label' => esc_html__('Items Gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-countdown' => 'gap: {{SIZE}}{{UNIT}};',
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

        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        $format = $settings['display_format'] ?? 'blocks';
        if ('inline' === $format && !$can_pro) {
            $format = 'blocks';
        }

        $end_timestamp = 0;
        $custom_date = $settings['custom_date'] ?? '';
        if (!empty($custom_date) && $can_pro) {
            $end_timestamp = strtotime($custom_date);
        }

        if (!$end_timestamp) {
            $end_timestamp = (int) get_post_meta($product->get_id(), '_sale_price_dates_to', true);
        }

        if (!$end_timestamp || $end_timestamp <= time()) {
            $behavior = $settings['expired_behavior'] ?? 'hide';
            if (!empty($settings['expired_text']) && 'text' === $behavior && $can_pro) {
                echo '<div class="ka-woo-countdown ka-woo-countdown--expired">' . esc_html($settings['expired_text']) . '</div>';
            } elseif ('zero' === $behavior) {
                echo '<div class="ka-woo-countdown ka-woo-countdown--expired"><div class="ka-woo-countdown__item"><span class="ka-woo-countdown__number">0</span><span class="ka-woo-countdown__label">' . esc_html__('Time', 'king-addons') . '</span></div></div>';
            }
            return;
        }

        $show_seconds = !empty($settings['show_seconds']) && $can_pro;

        $behavior = $settings['expired_behavior'] ?? 'hide';
        $expired_text = (!empty($settings['expired_text']) && $can_pro) ? $settings['expired_text'] : '';

        $classes = ['ka-woo-countdown', 'ka-woo-countdown--' . $format];

        echo '<div class="' . esc_attr(implode(' ', $classes)) . '" data-end="' . esc_attr((string) $end_timestamp) . '" data-seconds="' . ($show_seconds ? 'yes' : 'no') . '" data-expire="' . esc_attr($behavior) . '" data-expire-text="' . esc_attr($expired_text) . '">';

        if ('inline' === $format) {
            echo '<div class="ka-woo-countdown__inline">';
            echo '<span class="ka-woo-countdown__number" data-unit="days">0</span><span class="ka-woo-countdown__label">d</span>';
            echo '<span class="ka-woo-countdown__sep">:</span>';
            echo '<span class="ka-woo-countdown__number" data-unit="hours">00</span><span class="ka-woo-countdown__label">h</span>';
            echo '<span class="ka-woo-countdown__sep">:</span>';
            echo '<span class="ka-woo-countdown__number" data-unit="minutes">00</span><span class="ka-woo-countdown__label">m</span>';
            if ($show_seconds) {
                echo '<span class="ka-woo-countdown__sep">:</span>';
                echo '<span class="ka-woo-countdown__number" data-unit="seconds">00</span><span class="ka-woo-countdown__label">s</span>';
            }
            echo '</div>';
        } else {
            echo '<div class="ka-woo-countdown__item"><span class="ka-woo-countdown__number" data-unit="days">0</span><span class="ka-woo-countdown__label">' . esc_html__('Days', 'king-addons') . '</span></div>';
            echo '<div class="ka-woo-countdown__item"><span class="ka-woo-countdown__number" data-unit="hours">0</span><span class="ka-woo-countdown__label">' . esc_html__('Hours', 'king-addons') . '</span></div>';
            echo '<div class="ka-woo-countdown__item"><span class="ka-woo-countdown__number" data-unit="minutes">0</span><span class="ka-woo-countdown__label">' . esc_html__('Minutes', 'king-addons') . '</span></div>';
            if ($show_seconds) {
                echo '<div class="ka-woo-countdown__item"><span class="ka-woo-countdown__number" data-unit="seconds">0</span><span class="ka-woo-countdown__label">' . esc_html__('Seconds', 'king-addons') . '</span></div>';
            }
        }
        echo '</div>';
    }
}







