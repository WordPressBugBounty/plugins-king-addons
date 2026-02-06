<?php
/**
 * Woo My Account Details widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders account details form.
 */
class Woo_My_Account_Details extends Abstract_My_Account_Widget
{
    public function get_name(): string
    {
        return 'woo_my_account_details';
    }

    public function get_title(): string
    {
        return esc_html__('My Account Details', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-user-circle-o';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-my-account-details-style'];
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
                'selector' => '{{WRAPPER}} .ka-woo-my-account-details',
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

        if ($this->maybe_render_login_form()) {
            return;
        }

        $user = wp_get_current_user();

        echo '<div class="ka-woo-my-account-details">';
        wc_get_template('myaccount/form-edit-account.php', ['user' => $user]);
        echo '</div>';
    }
}






