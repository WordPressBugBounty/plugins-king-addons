<?php
/**
 * Woo Product Add To Cart widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use WC_Product_Variable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders add to cart for simple/variable products.
 */
class Woo_Product_Add_To_Cart extends Abstract_Single_Widget
{
    public function get_name(): string
    {
        return 'woo_product_add_to_cart';
    }

    public function get_title(): string
    {
        return esc_html__('Add To Cart', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-cart';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-add-to-cart-style'];
    }

    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-product-add-to-cart-script'];
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
            'button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Add to cart', 'king-addons'),
            ]
        );

        $this->add_control(
            'button_text_buy',
            [
                'label' => sprintf(__('Buy now text %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Buy now', 'king-addons'),
            ]
        );

        $this->add_control(
            'show_buy_now',
            [
                'label' => sprintf(__('Show Buy Now button %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_quantity',
            [
                'label' => esc_html__('Show quantity', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'quantity_position',
            [
                'label' => esc_html__('Quantity position', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'left' => esc_html__('Left', 'king-addons'),
                    'right' => esc_html__('Right', 'king-addons'),
                ],
                'default' => 'left',
                'condition' => [
                    'show_quantity' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_icon',
            [
                'label' => sprintf(__('Show icon %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'redirect_after',
            [
                'label' => sprintf(__('Redirect after add %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'stay' => esc_html__('Stay on page', 'king-addons'),
                    'cart' => esc_html__('Go to cart', 'king-addons'),
                    'checkout' => esc_html__('Go to checkout', 'king-addons'),
                ],
                'default' => 'stay',
            ]
        );

        $this->add_control(
            'ajax_add_to_cart',
            [
                'label' => sprintf(__('AJAX add to cart %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'success_text',
            [
                'label' => sprintf(__('Success message %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Added to cart', 'king-addons'),
            ]
        );

        $this->add_control(
            'error_text',
            [
                'label' => sprintf(__('Error message %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Please choose product options.', 'king-addons'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Button Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'typography',
                'selector' => '{{WRAPPER}} .ka-woo-product-atc__button',
            ]
        );

        $this->add_control(
            'btn_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-atc__button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'btn_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-atc__button' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'btn_color_hover',
            [
                'label' => esc_html__('Hover Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-atc__button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'btn_bg_hover',
            [
                'label' => esc_html__('Hover Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-atc__button:hover' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'btn_padding',
            [
                'label' => esc_html__('Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-atc__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'btn_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-atc__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_qty',
            [
                'label' => esc_html__('Quantity Style', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_quantity' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'qty_typography',
                'selector' => '{{WRAPPER}} .ka-woo-product-atc__qty input[type="number"]',
            ]
        );

        $this->add_control(
            'qty_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-atc__qty input[type="number"]' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'qty_bg',
            [
                'label' => esc_html__('Background', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-atc__qty input[type="number"]' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'qty_border_radius',
            [
                'label' => esc_html__('Border Radius', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-product-atc__qty input[type="number"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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

        $is_variable = $product instanceof WC_Product_Variable;

        $btn_text = $settings['button_text'] ?: esc_html__('Add to cart', 'king-addons');
        $ajax_enabled = !empty($settings['ajax_add_to_cart']) && $can_pro;
        $redirect_after = $can_pro ? ($settings['redirect_after'] ?? 'stay') : 'stay';
        $success_text = $can_pro ? ($settings['success_text'] ?? esc_html__('Added to cart', 'king-addons')) : '';
        $error_text = $can_pro ? ($settings['error_text'] ?? esc_html__('Please choose product options.', 'king-addons')) : '';
        $show_buy_now = !empty($settings['show_buy_now']) && $can_pro;
        $buy_text = $settings['button_text_buy'] ?? esc_html__('Buy now', 'king-addons');

        $this->add_render_attribute('wrapper', 'class', 'ka-woo-product-atc');
        $this->add_render_attribute('wrapper', 'data-product-id', (string) $product->get_id());
        $this->add_render_attribute('wrapper', 'data-ajax', $ajax_enabled ? 'yes' : 'no');
        $this->add_render_attribute('wrapper', 'data-redirect', esc_attr($redirect_after));
        $this->add_render_attribute('wrapper', 'data-success', esc_attr($success_text));
        $this->add_render_attribute('wrapper', 'data-error', esc_attr($error_text));
        $this->add_render_attribute('wrapper', 'data-has-variations', $is_variable ? 'yes' : 'no');

        echo '<div ' . $this->get_render_attribute_string('wrapper') . '>';

        $qty_html = '';
        if (!empty($settings['show_quantity'])) {
            $qty_html = '<div class="ka-woo-product-atc__qty"><input type="number" min="1" step="1" value="1" /></div>';
        }

        $button_classes = ['ka-woo-product-atc__button', 'single_add_to_cart_button', 'button'];
        $icon_html = '';
        if (!empty($settings['show_icon']) && $can_pro) {
            $icon_html = '<span class="ka-woo-product-atc__icon" aria-hidden="true"></span>';
        }

        $button = '<button type="button" class="' . esc_attr(implode(' ', $button_classes)) . '" data-redirect="' . esc_attr($redirect_after) . '">' . $icon_html . '<span class="ka-woo-product-atc__text">' . esc_html($btn_text) . '</span></button>';
        $buy_button = '';
        if ($show_buy_now) {
            $buy_button = '<button type="button" class="' . esc_attr(implode(' ', $button_classes)) . ' ka-woo-product-atc__button--buy" data-redirect="checkout">' . $icon_html . '<span class="ka-woo-product-atc__text">' . esc_html($buy_text) . '</span></button>';
        }

        if ('left' === ($settings['quantity_position'] ?? 'left') && !empty($settings['show_quantity'])) {
            echo $qty_html . $button . $buy_button;
        } else {
            echo $button . $buy_button . $qty_html;
        }

        if ($is_variable) {
            echo '<div class="ka-woo-product-atc__notice">' . esc_html__('Please select product options to add to cart.', 'king-addons') . '</div>';
        }

        echo '<div class="ka-woo-product-atc__state" aria-live="polite"></div>';

        echo '</div>';
    }
}







