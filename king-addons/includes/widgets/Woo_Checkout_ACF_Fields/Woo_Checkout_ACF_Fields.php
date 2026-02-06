<?php
/**
 * Woo Checkout ACF Extra Fields widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use King_Addons\Core;
use King_Addons\Woo_Builder\Context as Woo_Context;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Placeholder ACF fields for checkout (Pro-only rendering).
 */
class Woo_Checkout_ACF_Fields extends Widget_Base
{
    /**
     * Register Pro-only controls (placeholder).
     *
     * Pro overrides this method to add premium controls without using parent::register_controls().
     *
     * @return void
     */
    public function register_pro_controls(): void
    {
        // Intentionally empty in Free.
    }

    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_checkout_acf_fields';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Checkout ACF Extra Fields', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-field-text';
    }

    /**
     * Styles.
     *
     * @return array<int,string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-checkout-acf-fields-style'];
    }

    /**
     * Widget categories.
     *
     * @return array<int,string>
     */
    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    /**
     * Register controls.
     *
     * @return void
     */
    public function get_custom_help_url()
    {
        return 'mailto:bug@kingaddons.com?subject=Bug Report - King Addons&body=Please describe the issue';
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
            'heading',
            [
                'label' => esc_html__('Heading', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Extra Information', 'king-addons'),
            ]
        );

        $this->add_control(
            'field_keys',
            [
                'label' => esc_html__('ACF Field Keys (Pro)', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'field_123abc, my_field',
            ]
        );

        $this->add_control(
            'placement',
            [
                'label' => esc_html__('Placement', 'king-addons'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'before_billing' => esc_html__('Before billing', 'king-addons'),
                    'after_billing' => esc_html__('After billing', 'king-addons'),
                    'before_shipping' => esc_html__('Before shipping', 'king-addons'),
                    'after_shipping' => esc_html__('After shipping', 'king-addons'),
                    'before_order' => esc_html__('Before order notes', 'king-addons'),
                    'after_order' => esc_html__('After order notes', 'king-addons'),
                ],
                'default' => 'after_order',
            ]
        );

        $this->add_control(
            'required_notice',
            [
                'label' => sprintf(__('Show required mark %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
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
            'gap',
            [
                'label' => esc_html__('Fields gap', 'king-addons'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => ['min' => 0, 'max' => 40],
                ],
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-checkout-acf-fields' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'label_color',
            [
                'label' => esc_html__('Label color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-checkout-acf-fields label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'required_color',
            [
                'label' => esc_html__('Required mark color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-checkout-acf-fields .required' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Allow Pro to add additional controls without using parent::register_controls().
        $this->register_pro_controls();
    }

    /**
     * Render widget output (free notice).
     *
     * @return void
     */
    protected function render(): void
    {
        if (!Woo_Context::maybe_render_context_notice('checkout')) {
            return;
        }

        $in_builder = class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('checkout');
        if (!function_exists('is_checkout') || (!is_checkout() && !$in_builder) || (function_exists('is_order_received_page') && is_order_received_page())) {
            return;
        }

        if (!king_addons_can_use_pro()) {
            if (Woo_Context::is_editor()) {
                echo '<div class="king-addons-woo-builder-notice">';
                echo esc_html__('ACF extra fields for checkout are available in Pro.', 'king-addons');
                echo '</div>';
            }
            return;
        }

        $settings = $this->get_settings_for_display();
        $placement = $settings['placement'] ?? 'after_order';
        $required = !empty($settings['required_notice']);
        $fields_raw = $settings['field_keys'] ?? '';
        $fields = [];
        if (!empty($fields_raw)) {
            $parts = explode(',', $fields_raw);
            $fields = array_filter(array_map('trim', $parts));
        }

        $wrap = '<div class="ka-woo-checkout-acf-fields" data-ka-acf="1" data-placement="' . esc_attr($placement) . '" data-required="' . ($required ? '1' : '0') . '">';
        if (!empty($settings['heading'])) {
            $wrap .= '<h4 class="ka-woo-checkout-acf-fields__heading">' . esc_html($settings['heading']) . '</h4>';
        }
        /**
         * Render ACF fields at checkout.
         *
         * Developers can hook into this action to output ACF forms/fields.
         */
        ob_start();
        do_action('king_addons_checkout_acf_fields', $fields, $required, $placement);
        $wrap .= ob_get_clean();
        $wrap .= '</div>';

        if ('before_billing' === $placement) {
            add_action('woocommerce_before_checkout_billing_form', static function () use ($wrap): void {
                echo $wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            });
            return;
        }
        if ('after_billing' === $placement) {
            add_action('woocommerce_after_checkout_billing_form', static function () use ($wrap): void {
                echo $wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            });
            return;
        }
        if ('before_shipping' === $placement) {
            add_action('woocommerce_before_checkout_shipping_form', static function () use ($wrap): void {
                echo $wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            });
            return;
        }
        if ('after_shipping' === $placement) {
            add_action('woocommerce_after_checkout_shipping_form', static function () use ($wrap): void {
                echo $wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            });
            return;
        }
        if ('before_order' === $placement) {
            add_action('woocommerce_before_order_notes', static function () use ($wrap): void {
                echo $wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            });
            return;
        }
        // default after_order
        add_action('woocommerce_after_order_notes', static function () use ($wrap): void {
            echo $wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        });
    }
}






