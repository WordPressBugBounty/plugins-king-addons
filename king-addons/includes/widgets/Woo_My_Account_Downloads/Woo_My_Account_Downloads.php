<?php
/**
 * Woo My Account Downloads widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders downloadable products list.
 */
class Woo_My_Account_Downloads extends Abstract_My_Account_Widget
{
    public function get_name(): string
    {
        return 'woo_my_account_downloads';
    }

    public function get_title(): string
    {
        return esc_html__('My Account Downloads', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'king-addons-icon king-addons-woo-my-account-downloads';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-my-account-downloads-style'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Style', 'king-addons'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .ka-woo-my-account-downloads',
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        if (!$this->should_render()) {
            $this->render_missing_account_notice();
            return;
        }

        if (!$this->is_current_endpoint('downloads')) {
            return;
        }

        if ($this->maybe_render_login_form()) {
            return;
        }

        // downloads.php reaches into WC()->customer, which does not exist in
        // the Elementor editor - the call died with "Call to a member function
        // get_downloadable_products() on null" and took the whole editor
        // preview of the account template down with it.
        if (!function_exists('WC') || !WC()->customer) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="king-addons-woo-builder-notice">'
                    . esc_html__('Downloads are listed for the signed-in customer.', 'king-addons')
                    . '</div>';
            }
            return;
        }

        $downloads = wc_get_customer_available_downloads(get_current_user_id());

        echo '<div class="ka-woo-my-account-downloads">';
        wc_get_template(
            'myaccount/downloads.php',
            [
                'downloads' => $downloads,
                'has_downloads' => !empty($downloads),
                // The template also reads $wp_button_class; without it every
                // render warns once per download row.
                'wp_button_class' => function_exists('wc_wp_theme_get_element_class_name') && wc_wp_theme_get_element_class_name('button')
                    ? ' ' . wc_wp_theme_get_element_class_name('button')
                    : '',
            ]
        );
        echo '</div>';
    }
}






