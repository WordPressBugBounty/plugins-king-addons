<?php
/**
 * Base class for Checkout builder widgets.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Widget_Base;
use King_Addons\Woo_Builder\Context as Woo_Context;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provides checkout context utilities.
 */
abstract class Abstract_Checkout_Widget extends Widget_Base
{
    /**
     * Check if we should render the widget.
     * Returns true if in editor editing checkout template or on actual checkout page.
     *
     * @return bool
     */
    protected function should_render(): bool
    {
        // In editor mode editing checkout template
        if (class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('checkout')) {
            return true;
        }

        // On actual checkout page
        if (function_exists('is_checkout') && is_checkout() && !is_order_received_page()) {
            return true;
        }

        return false;
    }

    /**
     * Render placeholder when checkout context is missing.
     *
     * @return void
     */
    protected function render_missing_checkout_notice(): void
    {
        if (!class_exists('\Elementor\Plugin') || !\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            return;
        }

        // Check if we're editing a Checkout template
        if (class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('checkout')) {
            // Don't show notice - we're in the right template type
            return;
        }

        echo '<div class="king-addons-woo-builder-notice">' . esc_html__('This widget works only on the WooCommerce checkout page.', 'king-addons') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}







