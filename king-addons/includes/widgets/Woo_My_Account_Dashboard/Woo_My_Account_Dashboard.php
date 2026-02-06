<?php
/**
 * Woo My Account Dashboard widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use King_Addons\Woo_Builder\Context as Woo_Context;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders Woo My Account dashboard area.
 */
class Woo_My_Account_Dashboard extends Widget_Base
{
    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_my_account_dashboard';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('My Account Dashboard', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-dashboard';
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
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-my-account-dashboard-style'];
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
            'section_cards',
            [
                'label' => esc_html__('Cards', 'king-addons'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'custom_cards',
            [
                'label' => esc_html__('Custom quick links (Pro)', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => [
                    [
                        'name' => 'title',
                        'label' => esc_html__('Title', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'default' => esc_html__('Quick link', 'king-addons'),
                    ],
                    [
                        'name' => 'value',
                        'label' => esc_html__('Value', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => '5',
                    ],
                    [
                        'name' => 'url',
                        'label' => esc_html__('URL', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => 'https://',
                    ],
                ],
                'title_field' => '{{{ title }}}',
            ]
        );

        $this->add_control(
            'show_orders',
            [
                'label' => esc_html__('Show Orders Count', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_downloads',
            [
                'label' => esc_html__('Show Downloads Count', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_addresses',
            [
                'label' => esc_html__('Show Addresses', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
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
        $orders_count = wc_get_customer_order_count(get_current_user_id());
        $can_pro = king_addons_can_use_pro();

        echo '<div class="ka-woo-my-account-dashboard">';
        echo '<div class="ka-woo-my-account-dashboard__cards">';

        if (($settings['show_orders'] ?? 'yes') === 'yes') {
            echo '<div class="ka-woo-account-card">';
            echo '<div class="ka-woo-account-card__label">' . esc_html__('Orders', 'king-addons') . '</div>';
            echo '<div class="ka-woo-account-card__value">' . esc_html((string) $orders_count) . '</div>';
            echo '</div>';
        }

        if (($settings['show_downloads'] ?? 'yes') === 'yes' && function_exists('wc_get_customer_available_downloads')) {
            $downloads = wc_get_customer_available_downloads(get_current_user_id());
            echo '<div class="ka-woo-account-card">';
            echo '<div class="ka-woo-account-card__label">' . esc_html__('Downloads', 'king-addons') . '</div>';
            echo '<div class="ka-woo-account-card__value">' . esc_html((string) count($downloads)) . '</div>';
            echo '</div>';
        }

        if (($settings['show_addresses'] ?? 'yes') === 'yes' && function_exists('wc_get_account_menu_items')) {
            $address_url = wc_get_endpoint_url('edit-address');
            echo '<div class="ka-woo-account-card">';
            echo '<div class="ka-woo-account-card__label">' . esc_html__('Addresses', 'king-addons') . '</div>';
            echo '<div class="ka-woo-account-card__value">' . esc_html__('Manage', 'king-addons') . '</div>';
            if ($address_url) {
                echo '<a class="ka-woo-account-card__link" href="' . esc_url($address_url) . '">' . esc_html__('Edit', 'king-addons') . '</a>';
            }
            echo '</div>';
        }

        if ($can_pro && !empty($settings['custom_cards']) && is_array($settings['custom_cards'])) {
            foreach ($settings['custom_cards'] as $card) {
                $title = $card['title'] ?? '';
                $value = $card['value'] ?? '';
                $url = $card['url'] ?? '';
                echo '<div class="ka-woo-account-card">';
                if ($title) {
                    echo '<div class="ka-woo-account-card__label">' . esc_html($title) . '</div>';
                }
                if ($value) {
                    echo '<div class="ka-woo-account-card__value">' . esc_html($value) . '</div>';
                }
                if ($url) {
                    echo '<a class="ka-woo-account-card__link" href="' . esc_url($url) . '">' . esc_html__('Open', 'king-addons') . '</a>';
                }
                echo '</div>';
            }
        }

        echo '</div>';
        echo '<div class="ka-woo-my-account-dashboard__content">';
        do_action('woocommerce_account_dashboard');
        echo '</div>';
        echo '</div>';
    }
}






