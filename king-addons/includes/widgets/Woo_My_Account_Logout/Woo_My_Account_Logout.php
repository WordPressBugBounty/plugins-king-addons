<?php
/**
 * Woo My Account Logout widget.
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
 * Renders logout button for My Account.
 */
class Woo_My_Account_Logout extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_my_account_logout';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('My Account Logout', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-sign-out';
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
     * @return array<int, string>
     */
    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-my-account-logout-style'];
    }

    /**
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-my-account-logout-script'];
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
            'label',
            [
                'label' => esc_html__('Button Text', 'king-addons'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Log out', 'king-addons'),
            ]
        );

        $this->add_control(
            'confirm',
            [
                'label' => esc_html__('Confirm before logout', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => '',
                'description' => esc_html__('If enabled, user will see a confirmation step.', 'king-addons'),
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
        if (!Woo_Context::maybe_render_context_notice('my_account')) {
            return;
        }
        $in_builder = class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('my_account');
        if (!function_exists('is_account_page') || (!is_account_page() && !$in_builder)) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $label = $settings['label'] ?? esc_html__('Log out', 'king-addons');
        $logout_url = wc_logout_url();
        if (($settings['confirm'] ?? '') === 'yes') {
            $logout_url = add_query_arg('ka_logout_confirm', '1', $logout_url);
        }

        $confirm_attr = ($settings['confirm'] ?? '') === 'yes' ? ' data-ka-logout-confirm="1"' : '';

        echo '<a class="ka-woo-account-logout button" href="' . esc_url($logout_url) . '"' . $confirm_attr . '>';
        echo esc_html($label);
        echo '</a>';
    }
}






