<?php
/**
 * Woo My Account ACF Extra Fields widget.
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
 * Placeholder ACF fields for My Account (Pro-only rendering).
 */
class Woo_My_Account_ACF_Fields extends Widget_Base
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
        return 'woo_my_account_acf_fields';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('My Account ACF Extra Fields', 'king-addons');
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
                'default' => esc_html__('Account Details', 'king-addons'),
            ]
        );

        $this->end_controls_section();

        // Allow Pro to add additional controls without using parent::register_controls().
        $this->register_pro_controls();
    }

    /**
     * Render widget output (Free notice).
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

        if (!king_addons_can_use_pro()) {
            if (Woo_Context::is_editor()) {
                echo '<div class="king-addons-woo-builder-notice">';
                echo esc_html__('ACF extra fields for My Account are available in Pro.', 'king-addons');
                echo '</div>';
            }
            return;
        }

        $settings = $this->get_settings_for_display();
        echo '<div class="ka-woo-account-acf-fields">';
        if (!empty($settings['heading'])) {
            echo '<h4 class="ka-woo-account-acf-fields__heading">' . esc_html($settings['heading']) . '</h4>';
        }
        /**
         * Render ACF fields for My Account.
         *
         * Developers can hook into this action to output ACF forms/fields.
         */
        do_action('king_addons_my_account_acf_fields');
        echo '</div>';
    }
}






