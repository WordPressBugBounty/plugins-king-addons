<?php
/**
 * Woo Checkout Progress widget.
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
 * Displays checkout progress steps.
 */
class Woo_Checkout_Progress extends Abstract_Checkout_Widget
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_checkout_progress';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Checkout Progress', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-progress-tracker';
    }

    /**
     * Categories.
     *
     * @return array<int, string>
     */
    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    /**
     * Style dependencies.
     *
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-checkout-progress-style'];
    }

    /**
     * Register controls.
     *
     * @return void
     */
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
                'label' => sprintf(__('Layout (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'bar' => esc_html__('Bar', 'king-addons'),
                    'steps' => esc_html__('Steps (Pro)', 'king-addons'),
                ],
                'default' => 'bar',
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
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .ka-woo-checkout-progress',
            ]
        );

        $this->add_control(
            'color_base',
            [
                'label' => esc_html__('Base Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-checkout-progress' => '--ka-checkout-progress-base: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'color_active',
            [
                'label' => esc_html__('Active Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-checkout-progress' => '--ka-checkout-progress-active: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'color_complete',
            [
                'label' => sprintf(__('Completed Color (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-checkout-progress' => '--ka-checkout-progress-complete: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output.
     *
     * @return void
     */
    protected function render(): void
    {
        $order_received = function_exists('is_order_received_page') && is_order_received_page();
        $in_checkout = $this->should_render() || $order_received;
        $in_cart = function_exists('is_cart') && is_cart();
        if (!$in_checkout && !$in_cart) {
            $this->render_missing_checkout_notice();
            return;
        }

        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        $layout = $settings['layout'] ?? 'bar';
        if ('steps' === $layout && !$can_pro) {
            $layout = 'bar';
        }

        $current = 'details';
        if ($in_cart) {
            $current = 'cart';
        }
        if ($order_received) {
            $current = 'complete';
        } elseif (function_exists('is_checkout_pay_page') && is_checkout_pay_page()) {
            $current = 'payment';
        }

        $steps = [
            ['key' => 'cart', 'label' => esc_html__('Cart', 'king-addons')],
            ['key' => 'details', 'label' => esc_html__('Details', 'king-addons')],
            ['key' => 'payment', 'label' => esc_html__('Payment', 'king-addons')],
            ['key' => 'complete', 'label' => esc_html__('Complete', 'king-addons')],
        ];

        echo '<div class="ka-woo-checkout-progress ka-woo-checkout-progress--' . esc_attr($layout) . '">';

        if ('bar' === $layout) {
            $percent = 0;
            switch ($current) {
                case 'cart':
                    $percent = 25;
                    break;
                case 'details':
                    $percent = 50;
                    break;
                case 'payment':
                    $percent = 75;
                    break;
                case 'complete':
                    $percent = 100;
                    break;
            }
            echo '<div class="ka-woo-checkout-progress__bar"><span style="width:' . (int) $percent . '%"></span></div>';
            echo '<div class="ka-woo-checkout-progress__labels">';
            foreach ($steps as $step) {
                echo '<span class="ka-woo-checkout-progress__label">' . esc_html($step['label']) . '</span>';
            }
            echo '</div>';
        } else {
            echo '<div class="ka-woo-checkout-progress__steps">';
            foreach ($steps as $step) {
                $status = 'upcoming';
                if ($step['key'] === $current) {
                    $status = 'active';
                } elseif (array_search($step['key'], array_column($steps, 'key'), true) <= array_search($current, array_column($steps, 'key'), true)) {
                    $status = 'complete';
                }
                echo '<div class="ka-woo-checkout-progress__step is-' . esc_attr($status) . '">';
                echo '<span class="ka-woo-checkout-progress__dot"></span>';
                echo '<span class="ka-woo-checkout-progress__text">' . esc_html($step['label']) . '</span>';
                echo '</div>';
            }
            echo '</div>';
        }

        echo '</div>';
    }
}





