<?php
/**
 * Woo My Account Address widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders address management form.
 */
class Woo_My_Account_Address extends Abstract_My_Account_Widget
{
    public function get_name(): string
    {
        return 'woo_my_account_address';
    }

    public function get_title(): string
    {
        return esc_html__('My Account Address', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-address-book';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-my-account-address-style'];
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
                'selector' => '{{WRAPPER}} .ka-woo-my-account-address',
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

        echo '<div class="ka-woo-my-account-address">';
        wc_get_template('myaccount/my-address.php', ['customer_id' => get_current_user_id()]);
        echo '</div>';
    }
}






