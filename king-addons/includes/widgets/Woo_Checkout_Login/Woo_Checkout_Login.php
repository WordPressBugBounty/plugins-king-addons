<?php
/**
 * Woo Checkout Login widget.
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
 * Displays checkout login prompt/form.
 */
class Woo_Checkout_Login extends Abstract_Checkout_Widget
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_checkout_login';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Checkout Login', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-woo-checkout-login';
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
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-checkout-login-style'];
    }

    /**
     * Register controls.
     *
     * @return void
     */
    protected function register_controls(): void
    {
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
                'selector' => '{{WRAPPER}} .ka-woo-checkout-login',
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ka-woo-checkout-login' => 'color: {{VALUE}};',
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

        // woocommerce_checkout_login_form() prints nothing for a signed-in
            // visitor, or when the login reminder is switched off in WooCommerce.
        // An empty wrapper on the page - and a blank box in the editor -
        // reads as a broken widget rather than a context that has nothing
        // to show.
        ob_start();
        woocommerce_checkout_login_form();
        $output = trim((string) ob_get_clean());

        if ('' === $output) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="king-addons-woo-builder-notice">'
                    . esc_html__('The returning-customer form only appears for a signed-out visitor when "allow customers to log in during checkout" is on.', 'king-addons')
                    . '</div>';
            }
            return;
        }

        // WooCommerce's own markup, not user input.
        echo '<div class="ka-woo-checkout-login">' . $output . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}






