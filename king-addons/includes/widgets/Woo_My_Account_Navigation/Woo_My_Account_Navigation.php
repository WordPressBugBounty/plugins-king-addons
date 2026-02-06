<?php
/**
 * Woo My Account Navigation widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use King_Addons\Woo_Builder\Context as Woo_Context;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders my account navigation.
 */
class Woo_My_Account_Navigation extends Abstract_My_Account_Widget
{
    public function get_name(): string
    {
        return 'woo_my_account_navigation';
    }

    public function get_title(): string
    {
        return esc_html__('My Account Navigation', 'king-addons');
    }

    public function get_icon(): string
    {
        return 'eicon-menu-bar';
    }

    public function get_categories(): array
    {
        return ['king-addons-woo-builder'];
    }

    public function get_style_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-my-account-navigation-style'];
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
            'sticky',
            [
                'label' => sprintf(__('Sticky (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'sticky_offset',
            [
                'label' => sprintf(__('Sticky Offset (px) (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::NUMBER,
                'default' => 20,
                'condition' => [
                    'sticky' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_icons',
            [
                'label' => esc_html__('Show Icons', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
            ]
        );

        $this->add_control(
            'show_counts',
            [
                'label' => esc_html__('Show Counts (orders/downloads)', 'king-addons'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
            ]
        );

        $this->add_control(
            'menu_items',
            [
                'label' => esc_html__('Menu Items (reorder/hide/label)', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'fields' => [
                    [
                        'name' => 'endpoint',
                        'label' => esc_html__('Endpoint / slug', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => 'orders',
                    ],
                    [
                        'name' => 'label',
                        'label' => esc_html__('Custom label', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                    ],
                    [
                        'name' => 'position',
                        'label' => esc_html__('Order', 'king-addons'),
                        'type' => Controls_Manager::NUMBER,
                        'default' => 20,
                    ],
                    [
                        'name' => 'hide',
                        'label' => esc_html__('Hide', 'king-addons'),
                        'type' => Controls_Manager::SWITCHER,
                        'return_value' => 'yes',
                    ],
                    [
                        'name' => 'type',
                        'label' => esc_html__('Type', 'king-addons'),
                        'type' => Controls_Manager::SELECT,
                        'options' => [
                            'endpoint' => esc_html__('Endpoint', 'king-addons'),
                            'custom' => esc_html__('Custom URL', 'king-addons'),
                        ],
                        'default' => 'endpoint',
                    ],
                    [
                        'name' => 'custom_url',
                        'label' => esc_html__('Custom URL', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => 'https://example.com',
                        'condition' => [
                            'type' => 'custom',
                        ],
                    ],
                ],
                'title_field' => '{{{ endpoint }}}',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Links', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'links_typography',
                'selector' => '{{WRAPPER}} .woocommerce-MyAccount-navigation',
            ]
        );

        $this->add_control(
            'link_color',
            [
                'label' => esc_html__('Link Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-MyAccount-navigation a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'link_color_active',
            [
                'label' => esc_html__('Active Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-MyAccount-navigation .is-active a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'nav_border',
                'selector' => '{{WRAPPER}} .woocommerce-MyAccount-navigation',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'nav_shadow',
                'selector' => '{{WRAPPER}} .woocommerce-MyAccount-navigation',
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        if (!Woo_Context::maybe_render_context_notice('my_account')) {
            return;
        }

        $in_builder = class_exists('King_Addons\\Woo_Builder\\Context') && Woo_Context::is_editing_template_type('my_account');
        if (!function_exists('is_account_page') || (!is_account_page() && !$in_builder)) {
            return;
        }

        if (!function_exists('wc_get_account_menu_items')) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();
        $show_icons = ($settings['show_icons'] ?? '') === 'yes';
        $show_counts = ($settings['show_counts'] ?? '') === 'yes';

        $this->add_render_attribute('nav', 'class', 'woocommerce-MyAccount-navigation');
        if (!empty($settings['sticky']) && $can_pro) {
            $offset = isset($settings['sticky_offset']) ? (int) $settings['sticky_offset'] : 20;
            $this->add_render_attribute('nav', 'style', 'position: sticky; top: ' . $offset . 'px;');
        }

        $items = wc_get_account_menu_items();
        $custom = $settings['menu_items'] ?? [];
        $custom_links = [];
        if (!empty($custom) && is_array($custom)) {
            $ordered = [];
            foreach ($custom as $item) {
                $endpoint = sanitize_title($item['endpoint'] ?? '');
                if ('' === $endpoint) {
                    continue;
                }
                if (!empty($item['hide']) && 'yes' === $item['hide']) {
                    unset($items[$endpoint]);
                    continue;
                }
                if (isset($items[$endpoint]) && isset($item['label']) && $item['label'] !== '') {
                    $items[$endpoint] = $item['label'];
                }
                $position = isset($item['position']) ? (int) $item['position'] : 20;
                if (($item['type'] ?? 'endpoint') === 'custom') {
                    $custom_links[$position . ':' . $endpoint] = [
                        'label' => $item['label'] ?: $endpoint,
                        'url' => $item['custom_url'] ?? '#',
                    ];
                    continue;
                }
                $ordered[$position . ':' . $endpoint] = [$endpoint, $items[$endpoint] ?? ($item['label'] ?? $endpoint)];
            }
            if (!empty($ordered)) {
                ksort($ordered, SORT_NATURAL);
                $new_items = [];
                foreach ($ordered as $pack) {
                    [$endpoint, $label] = $pack;
                    $new_items[$endpoint] = $label;
                }
                // append any not mentioned endpoints at the end
                foreach ($items as $endpoint => $label) {
                    if (!isset($new_items[$endpoint])) {
                        $new_items[$endpoint] = $label;
                    }
                }
                $items = $new_items;
            }
        }
        $counts = [
            'orders' => function_exists('wc_get_customer_order_count') ? wc_get_customer_order_count(get_current_user_id()) : 0,
            'downloads' => function_exists('wc_get_customer_available_downloads') ? count(wc_get_customer_available_downloads(get_current_user_id())) : 0,
        ];

        echo '<nav ' . $this->get_render_attribute_string('nav') . ' data-ka-myaccount-nav>';
        echo '<ul>';
        foreach ($items as $endpoint => $label) {
            $classes = wc_get_account_menu_item_classes($endpoint);
            $icon = $show_icons ? '<span class="ka-myaccount-nav__icon" aria-hidden="true">•</span>' : '';
            $count = '';
            if ($show_counts && in_array($endpoint, ['orders', 'downloads'], true)) {
                $val = $counts[$endpoint] ?? 0;
                $count = '<span class="ka-myaccount-nav__count">' . esc_html((string) $val) . '</span>';
            }
            printf(
                '<li class="%s"><a href="%s">%s<span class="ka-myaccount-nav__label">%s</span>%s</a></li>',
                esc_attr($classes),
                esc_url(wc_get_account_endpoint_url($endpoint)),
                $icon,
                esc_html($label),
                $count
            ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        if (!empty($custom_links)) {
            ksort($custom_links, SORT_NATURAL);
            foreach ($custom_links as $data) {
                printf(
                    '<li class="ka-myaccount-nav__custom"><a href="%s"><span class="ka-myaccount-nav__label">%s</span></a></li>',
                    esc_url($data['url']),
                    esc_html($data['label'])
                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        }
        echo '</ul>';
        echo '</nav>';
    }
}






