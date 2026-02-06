<?php
/**
 * Base class for My Account builder widgets.
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
 * Provides my-account context utilities.
 */
abstract class Abstract_My_Account_Widget extends Widget_Base
{
    /**
     * Check if we should render the widget.
     * Returns true if in editor editing my_account template or on actual my account page.
     *
     * @return bool
     */
    protected function should_render(): bool
    {
        // In editor mode editing my_account template
        if (class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('my_account')) {
            return true;
        }

        // On actual my account page
        if (function_exists('is_account_page') && is_account_page()) {
            return true;
        }

        return false;
    }

    /**
     * Render placeholder when my account context is missing.
     *
     * @return void
     */
    protected function render_missing_account_notice(): void
    {
        if (!class_exists('\Elementor\Plugin') || !\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            return;
        }

        // Check if we're editing a My Account template
        if (class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('my_account')) {
            // Don't show notice - we're in the right template type
            return;
        }

        echo '<div class="king-addons-woo-builder-notice">' . esc_html__('This widget works only on the WooCommerce My Account page.', 'king-addons') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Render login form if user is not logged in.
     * In editor mode, shows a placeholder instead.
     *
     * @return bool True when login form rendered.
     */
    protected function maybe_render_login_form(): bool
    {
        // In editor mode, don't show login form - show preview content
        if (class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editor()) {
            return false;
        }

        if (is_user_logged_in()) {
            return false;
        }

        echo '<div class="king-addons-my-account-login">';
        woocommerce_login_form(['redirect' => wc_get_page_permalink('myaccount')]);
        echo '</div>';
        return true;
    }
}







