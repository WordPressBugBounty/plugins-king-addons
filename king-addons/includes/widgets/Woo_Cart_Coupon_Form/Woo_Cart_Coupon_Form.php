<?php
/**
 * Woo Cart Coupon Form widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use King_Addons\Woo_Builder\Context as Woo_Context;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders WooCommerce cart coupon form.
 */
class Woo_Cart_Coupon_Form extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_cart_coupon_form';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Cart Coupon Form', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-cart';
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
     * Style dependencies.
     *
     * @return array<int,string>
     */
    public function get_style_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-cart-coupon-form-style',
        ];
    }

    /**
     * Script dependencies.
     *
     * @return array<int,string>
     */
    public function get_script_depends(): array
    {
        return [
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-cart-coupon-form-script',
        ];
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
            'show_heading',
            [
                'label' => esc_html__('Show Heading', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'heading_text',
            [
                'label' => esc_html__('Heading Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Have a coupon?', 'king-addons'),
                'condition' => [
                    'show_heading' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'placeholder',
            [
                'label' => esc_html__('Input Placeholder', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Coupon code', 'king-addons'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Apply coupon', 'king-addons'),
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
        if (!Woo_Context::maybe_render_context_notice('cart')) {
            return;
        }

        $in_builder = class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('cart');
        if (!function_exists('is_cart') || (!is_cart() && !$in_builder)) {
            return;
        }

        if (!function_exists('WC') || !WC()->cart || (WC()->cart->is_empty() && !$in_builder)) {
            return;
        }

        if (!wc_coupons_enabled()) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $ajax_nonce = wp_create_nonce('ka_cart');
        $heading_text = !empty($settings['heading_text']) ? $settings['heading_text'] : esc_html__('Have a coupon?', 'king-addons');
        $button_text = !empty($settings['button_text']) ? $settings['button_text'] : esc_html__('Apply coupon', 'king-addons');
        ?>
        <div
            class="ka-woo-cart-coupon-widget"
            data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
            data-nonce="<?php echo esc_attr($ajax_nonce); ?>"
        >
            <div class="ka-woo-cart-coupon__header">
                <?php if (!empty($settings['show_heading'])) : ?>
                    <button type="button" class="ka-woo-cart-coupon__toggle" data-ka-coupon-toggle="1">
                        <?php echo esc_html($heading_text); ?>
                    </button>
                <?php endif; ?>
            </div>
            <div class="ka-woo-cart-coupon__body" <?php echo !empty($settings['show_heading']) ? 'hidden' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                <div class="ka-woo-cart-coupon__row">
                    <label class="screen-reader-text" for="ka_coupon_code"><?php esc_html_e('Coupon code', 'king-addons'); ?></label>
                    <input
                        type="text"
                        id="ka_coupon_code"
                        class="ka-woo-cart-coupon__input"
                        name="coupon_code"
                        placeholder="<?php echo esc_attr($settings['placeholder']); ?>"
                        autocomplete="off"
                    />
                    <button type="button" class="ka-woo-cart-coupon__apply button" data-mode="apply">
                        <?php echo esc_html($button_text); ?>
                    </button>
                    <button type="button" class="ka-woo-cart-coupon__remove button button-secondary" data-mode="remove">
                        <?php esc_html_e('Remove', 'king-addons'); ?>
                    </button>
                </div>
                <div class="ka-woo-cart-coupon__notice" aria-live="polite"></div>
            </div>
        </div>
        <?php
    }
}




