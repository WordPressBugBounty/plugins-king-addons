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
        if (!$this->can_use_builder_widgets()) {
            return false;
        }

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
     * Whether this request is the given My Account endpoint.
     *
     * An empty endpoint means the account dashboard (no WooCommerce endpoint
     * query var). The Elementor editor always returns true so every widget
     * on the template stays visible while designing.
     *
     * @param string $endpoint WooCommerce endpoint slug, or '' for dashboard.
     *
     * @return bool
     */
    protected function is_current_endpoint(string $endpoint = ''): bool
    {
        if (class_exists('King_Addons\\Woo_Builder\\Context') && (Woo_Context::is_editing_template_type('my_account') || Woo_Context::is_editor())) {
            return true;
        }

        if (!function_exists('is_wc_endpoint_url')) {
            return '' === $endpoint;
        }

        if ('' === $endpoint) {
            return !is_wc_endpoint_url();
        }

        return is_wc_endpoint_url($endpoint);
    }

    /**
     * Cart / checkout / account builder widgets are a Pro feature.
     *
     * @return bool
     */
    protected function can_use_builder_widgets(): bool
    {
        return function_exists('king_addons_can_use_pro') && king_addons_can_use_pro();
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

        if (!$this->can_use_builder_widgets()) {
            $this->render_pro_required_notice();
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
     * Editor-only upsell when the site cannot use Pro.
     *
     * @return void
     */
    protected function render_pro_required_notice(): void
    {
        if (class_exists('King_Addons\\Core')) {
            Core::renderEditorHint(esc_html__('Available in King Addons Pro.', 'king-addons'));
            return;
        }

        echo '<div class="king-addons-woo-builder-notice">' . esc_html__('Available in King Addons Pro.', 'king-addons') . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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







