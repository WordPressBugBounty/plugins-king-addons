<?php
/**
 * Woo Checkout Payment widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use King_Addons\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays checkout payment methods.
 */
class Woo_Checkout_Payment extends Abstract_Checkout_Widget
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_checkout_payment';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Checkout Payment', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-credit-card';
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
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-checkout-payment-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-checkout-payment-script'];
    }

    /**
     * Register controls.
     *
     * @return void
     */
    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_behavior',
            [
                'label' => esc_html__('Behavior', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'enable_accordion',
            [
                'label' => sprintf(__('Accordion payments (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'gateway_icons',
            [
                'label' => esc_html__('Payment icons (Pro)', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'condition' => [
                    'enable_accordion' => 'yes',
                ],
                'fields' => [
                    [
                        'name' => 'gateway_id',
                        'label' => esc_html__('Gateway ID', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => 'stripe',
                    ],
                    [
                        'name' => 'icon_url',
                        'label' => esc_html__('Icon URL', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => 'https://…/visa.svg',
                    ],
                ],
                'title_field' => '{{{ gateway_id }}}',
            ]
        );

        $this->add_control(
            'custom_descriptions',
            [
                'label' => esc_html__('Custom descriptions (Pro)', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => [
                    [
                        'name' => 'gateway_id',
                        'label' => esc_html__('Gateway ID', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => 'stripe',
                    ],
                    [
                        'name' => 'text',
                        'label' => esc_html__('Description', 'king-addons'),
                        'type' => Controls_Manager::TEXTAREA,
                        'rows' => 2,
                    ],
                ],
                'title_field' => '{{{ gateway_id }}}',
            ]
        );

        $this->add_control(
            'place_order_texts',
            [
                'label' => esc_html__('Place order text per gateway (Pro)', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'condition' => [
                    'enable_accordion' => 'yes',
                ],
                'fields' => [
                    [
                        'name' => 'gateway_id',
                        'label' => esc_html__('Gateway ID', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => 'stripe',
                    ],
                    [
                        'name' => 'button_text',
                        'label' => esc_html__('Button text', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => esc_html__('Pay now', 'king-addons'),
                    ],
                ],
                'title_field' => '{{{ gateway_id }}}',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Payment Box', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .ka-woo-checkout-payment',
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-checkout-payment' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'box_border',
                'selector' => '{{WRAPPER}} .ka-woo-checkout-payment',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'box_shadow',
                'selector' => '{{WRAPPER}} .ka-woo-checkout-payment',
            ]
        );

        $this->add_control(
            'box_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-checkout-payment' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'method_gap',
            [
                'label' => esc_html__('Methods gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-checkout-payment .wc_payment_methods' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'selector' => '{{WRAPPER}} .ka-woo-checkout-payment .wc_payment_method label',
                'label' => esc_html__('Label Typography', 'king-addons'),
            ]
        );

        $this->add_control(
            'desc_color',
            [
                'label' => esc_html__('Description Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-checkout-payment .ka-wc-payment__custom-desc' => 'color: {{VALUE}};',
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
        if (!$this->should_render()) {
            $this->render_missing_checkout_notice();
            return;
        }

        if (!function_exists('WC')) {
            return;
        }

        $checkout = WC()->checkout();
        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        $attrs = [
            'class' => 'ka-woo-checkout-payment',
        ];

        if ($can_pro) {
            $accordion = (!empty($settings['enable_accordion']) && 'yes' === $settings['enable_accordion']) ? 'true' : 'false';
            $icons = [];
            foreach ($settings['gateway_icons'] ?? [] as $item) {
                $gid = sanitize_key($item['gateway_id'] ?? '');
                $url = isset($item['icon_url']) ? esc_url_raw($item['icon_url']) : '';
                if ($gid && $url) {
                    $icons[$gid] = $url;
                }
            }
            $buttons = [];
            foreach ($settings['place_order_texts'] ?? [] as $item) {
                $gid = sanitize_key($item['gateway_id'] ?? '');
                $txt = isset($item['button_text']) ? sanitize_text_field($item['button_text']) : '';
                if ($gid && $txt) {
                    $buttons[$gid] = $txt;
                }
            }
            $descs = [];
            foreach ($settings['custom_descriptions'] ?? [] as $item) {
                $gid = sanitize_key($item['gateway_id'] ?? '');
                $txt = isset($item['text']) ? wp_kses_post($item['text']) : '';
                if ($gid && $txt) {
                    $descs[$gid] = $txt;
                }
            }

            $attrs['data-ka-accordion'] = $accordion;
            if (!empty($icons)) {
                $attrs['data-ka-icons'] = esc_attr(wp_json_encode($icons));
            }
            if (!empty($buttons)) {
                $attrs['data-ka-placeorder'] = esc_attr(wp_json_encode($buttons));
            }
            if (!empty($descs)) {
                $attrs['data-ka-descriptions'] = esc_attr(wp_json_encode($descs));
            }
        }

        $attr_str = '';
        foreach ($attrs as $key => $val) {
            $attr_str .= ' ' . $key . '="' . esc_attr($val) . '"';
        }

        echo '<div' . $attr_str . '>';
        wc_get_template('checkout/payment.php', ['checkout' => $checkout]);
        echo '</div>';
    }
}






