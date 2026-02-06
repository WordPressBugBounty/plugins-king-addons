<?php
/**
 * Woo Checkout Form widget.
 *
 * @package King_Addons
 */

namespace King_Addons;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the WooCommerce checkout form.
 */
class Woo_Checkout_Form extends Abstract_Checkout_Widget
{
    /**
     * Last config cache for checkout fields filter.
     *
     * @var array<int,array<string,mixed>>
     */
    private static array $cached_config = [];

    /**
     * Last extra fields cache for checkout fields filter.
     *
     * @var array<int,array<string,mixed>>
     */
    private static array $cached_extra = [];

    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'woo_checkout_form';
    }

    /**
     * Widget title.
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Checkout Form', 'king-addons');
    }

    /**
     * Widget icon.
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-checkout';
    }

    /**
     * Categories.
     *
     * @return array<int, string>
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
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-checkout-form-style'];
    }

    /**
     * Register controls.
     *
     * @return void
     */
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
            'show_login',
            [
                'label' => sprintf(__('Show Login Notice (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_coupon',
            [
                'label' => sprintf(__('Show Coupon Form (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_order_notes',
            [
                'label' => sprintf(__('Show Order Notes (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'enable_field_customization',
            [
                'label' => sprintf(__('Customize fields (Pro) %s', 'king-addons'), '<i class="eicon-pro-icon"></i>'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'fields_config',
            [
                'label' => esc_html__('Fields settings (label/placeholder/required/order/hide)', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'condition' => [
                    'enable_field_customization' => 'yes',
                ],
                'fields' => [
                    [
                        'name' => 'field_key',
                        'label' => esc_html__('Field key', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => 'billing_phone',
                    ],
                    [
                        'name' => 'section',
                        'label' => esc_html__('Section', 'king-addons'),
                        'type' => Controls_Manager::SELECT,
                        'options' => [
                            'billing' => esc_html__('Billing', 'king-addons'),
                            'shipping' => esc_html__('Shipping', 'king-addons'),
                            'order' => esc_html__('Order notes', 'king-addons'),
                        ],
                        'default' => 'billing',
                    ],
                    [
                        'name' => 'label',
                        'label' => esc_html__('Label', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                    ],
                    [
                        'name' => 'help',
                        'label' => esc_html__('Help text (hint)', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                    ],
                    [
                        'name' => 'placeholder',
                        'label' => esc_html__('Placeholder', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                    ],
                    [
                        'name' => 'required',
                        'label' => esc_html__('Required', 'king-addons'),
                        'type' => Controls_Manager::SWITCHER,
                        'return_value' => 'yes',
                    ],
                    [
                        'name' => 'hide',
                        'label' => esc_html__('Hide field', 'king-addons'),
                        'type' => Controls_Manager::SWITCHER,
                        'return_value' => 'yes',
                    ],
                    [
                        'name' => 'priority',
                        'label' => esc_html__('Priority (order)', 'king-addons'),
                        'type' => Controls_Manager::NUMBER,
                        'default' => 20,
                    ],
                    [
                        'name' => 'visibility_mode',
                        'label' => esc_html__('Visibility rule', 'king-addons'),
                        'type' => Controls_Manager::SELECT,
                        'options' => [
                            'all' => esc_html__('Show everywhere', 'king-addons'),
                            'show' => esc_html__('Show only for countries', 'king-addons'),
                            'hide' => esc_html__('Hide for countries', 'king-addons'),
                        ],
                        'default' => 'all',
                    ],
                    [
                        'name' => 'countries',
                        'label' => esc_html__('Countries (Pro)', 'king-addons'),
                        'type' => Controls_Manager::SELECT2,
                        'multiple' => true,
                        'options' => (function_exists('WC') && WC()->countries) ? WC()->countries->get_countries() : [],
                    ],
                ],
                'default' => [],
                'title_field' => '{{{ field_key }}}',
            ]
        );

        $this->add_control(
            'extra_fields',
            [
                'label' => esc_html__('Extra fields (Pro)', 'king-addons'),
                'type' => Controls_Manager::REPEATER,
                'condition' => [
                    'enable_field_customization' => 'yes',
                ],
                'fields' => [
                    [
                        'name' => 'field_key',
                        'label' => esc_html__('Field key', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => 'order_reference',
                    ],
                    [
                        'name' => 'section',
                        'label' => esc_html__('Section', 'king-addons'),
                        'type' => Controls_Manager::SELECT,
                        'options' => [
                            'billing' => esc_html__('Billing', 'king-addons'),
                            'shipping' => esc_html__('Shipping', 'king-addons'),
                            'order' => esc_html__('Order notes', 'king-addons'),
                        ],
                        'default' => 'order',
                    ],
                    [
                        'name' => 'label',
                        'label' => esc_html__('Label', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                        'default' => esc_html__('Extra field', 'king-addons'),
                    ],
                    [
                        'name' => 'placeholder',
                        'label' => esc_html__('Placeholder', 'king-addons'),
                        'type' => Controls_Manager::TEXT,
                    ],
                    [
                        'name' => 'type',
                        'label' => esc_html__('Type', 'king-addons'),
                        'type' => Controls_Manager::SELECT,
                        'options' => [
                            'text' => esc_html__('Text', 'king-addons'),
                            'textarea' => esc_html__('Textarea', 'king-addons'),
                        ],
                        'default' => 'text',
                    ],
                    [
                        'name' => 'required',
                        'label' => esc_html__('Required', 'king-addons'),
                        'type' => Controls_Manager::SWITCHER,
                        'return_value' => 'yes',
                    ],
                    [
                        'name' => 'priority',
                        'label' => esc_html__('Priority (order)', 'king-addons'),
                        'type' => Controls_Manager::NUMBER,
                        'default' => 80,
                    ],
                ],
                'default' => [],
                'title_field' => '{{{ field_key }}}',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Form', 'king-addons'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'form_typography',
                'selector' => '{{WRAPPER}} .woocommerce',
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'king-addons'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .woocommerce' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'form_border',
                'selector' => '{{WRAPPER}} .woocommerce form.checkout',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'form_shadow',
                'selector' => '{{WRAPPER}} .woocommerce form.checkout',
            ]
        );

        $this->add_control(
            'form_padding',
            [
                'label' => esc_html__('Form Padding', 'king-addons'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .woocommerce form.checkout' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
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
        if (!$this->should_render()) {
            $this->render_missing_checkout_notice();
            return;
        }

        if (!function_exists('WC')) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $can_pro = king_addons_can_use_pro();

        $show_login = !empty($settings['show_login']);
        $show_coupon = !empty($settings['show_coupon']);
        $show_notes = !empty($settings['show_order_notes']);
        $field_customization = $can_pro && !empty($settings['enable_field_customization']);

        $removed = [];

        if (!$can_pro) {
            $show_login = true;
            $show_coupon = true;
            $show_notes = true;
        }

        if (!$show_login) {
            remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10);
            $removed[] = 'login';
        }

        if (!$show_coupon) {
            remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
            $removed[] = 'coupon';
        }

        $notes_filter_added = false;
        if (!$show_notes) {
            add_filter('woocommerce_enable_order_notes_field', '__return_false', 9999);
            $notes_filter_added = true;
        }

        $fields_filter_added = false;
        if ($field_customization) {
            $fields_filter_added = true;
            self::$cached_config = $settings['fields_config'] ?? [];
            self::$cached_extra = $settings['extra_fields'] ?? [];
            add_filter('woocommerce_checkout_fields', [self::class, 'filter_checkout_fields'], 9999);
            add_action('woocommerce_checkout_update_order_meta', [self::class, 'save_extra_fields'], 20, 2);
        }

        if (function_exists('woocommerce_checkout')) {
            call_user_func('woocommerce_checkout');
        }

        if (in_array('login', $removed, true)) {
            add_action('woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10);
        }
        if (in_array('coupon', $removed, true)) {
            add_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
        }
        if ($notes_filter_added) {
            remove_filter('woocommerce_enable_order_notes_field', '__return_false', 9999);
        }
        if ($fields_filter_added) {
            remove_filter('woocommerce_checkout_fields', [self::class, 'filter_checkout_fields'], 9999);
            remove_action('woocommerce_checkout_update_order_meta', [self::class, 'save_extra_fields'], 20);
            self::$cached_config = [];
            self::$cached_extra = [];
        }
    }

    /**
     * Wrapper for WC checkout fields filter using cached config.
     *
     * @param array<string,array<string,array<string,mixed>>> $fields WC checkout fields.
     *
     * @return array<string,array<string,array<string,mixed>>>
     */
    public static function filter_checkout_fields(array $fields): array
    {
        return self::tune_checkout_fields($fields, self::$cached_config, self::$cached_extra);
    }

    /**
     * Adjust checkout fields based on widget settings (Pro).
     *
     * @param array<string,array<string,array<string,mixed>>> $fields WC checkout fields.
     * @param array<int,array<string,mixed>>                  $config Configured existing fields.
     * @param array<int,array<string,mixed>>                  $extra  Extra fields to inject.
     *
     * @return array<string,array<string,array<string,mixed>>>
     */
    public static function tune_checkout_fields(array $fields, array $config, array $extra): array
    {
        foreach ($config as $item) {
            $section = $item['section'] ?? '';
            $key = $item['field_key'] ?? '';
            if (empty($section) || empty($key) || empty($fields[$section][$key])) {
                continue;
            }
            // Country-based visibility.
            $mode = $item['visibility_mode'] ?? 'all';
            $countries = !empty($item['countries']) && is_array($item['countries']) ? array_filter(array_map('sanitize_text_field', $item['countries'])) : [];
            if ('all' !== $mode && !empty($countries) && function_exists('WC')) {
                $customer = WC()->customer;
                $current_country = $customer ? $customer->get_shipping_country() : '';
                if (empty($current_country) && $customer) {
                    $current_country = $customer->get_billing_country();
                }
                if ('show' === $mode && !in_array($current_country, $countries, true)) {
                    unset($fields[$section][$key]);
                    continue;
                }
                if ('hide' === $mode && in_array($current_country, $countries, true)) {
                    unset($fields[$section][$key]);
                    continue;
                }
            }
            if (!empty($item['hide']) && 'yes' === $item['hide']) {
                unset($fields[$section][$key]);
                continue;
            }
            if (isset($item['label']) && $item['label'] !== '') {
                $fields[$section][$key]['label'] = sanitize_text_field($item['label']);
            }
            if (isset($item['placeholder']) && $item['placeholder'] !== '') {
                $fields[$section][$key]['placeholder'] = sanitize_text_field($item['placeholder']);
            } elseif (empty($fields[$section][$key]['placeholder']) && !empty($fields[$section][$key]['label'])) {
                $fields[$section][$key]['placeholder'] = $fields[$section][$key]['label'];
            }
            if (isset($item['help']) && $item['help'] !== '') {
                $fields[$section][$key]['description'] = sanitize_text_field($item['help']);
            }
            if (isset($item['required'])) {
                $fields[$section][$key]['required'] = ('yes' === $item['required']);
            }
            if (isset($item['priority']) && '' !== $item['priority']) {
                $fields[$section][$key]['priority'] = (int) $item['priority'];
            }
        }

        foreach ($extra as $item) {
            $section = $item['section'] ?? 'order';
            $key = $item['field_key'] ?? '';
            if (empty($key)) {
                continue;
            }
            $type = ('textarea' === ($item['type'] ?? 'text')) ? 'textarea' : 'text';
            $label = isset($item['label']) ? sanitize_text_field($item['label']) : '';
            $placeholder = isset($item['placeholder']) ? sanitize_text_field($item['placeholder']) : '';
            $required = ('yes' === ($item['required'] ?? ''));
            $priority = isset($item['priority']) ? (int) $item['priority'] : 80;

            $fields[$section][$key] = [
                'type' => $type,
                'label' => $label,
                'placeholder' => $placeholder,
                'required' => $required,
                'priority' => $priority,
            ];
        }

        return $fields;
    }

    /**
     * Save extra checkout fields to order meta.
     *
     * @param int   $order_id Order ID.
     * @param array $data     Posted data.
     *
     * @return void
     */
    public static function save_extra_fields(int $order_id, array $data): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $posted = $_POST ?? []; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        $types = [];
        foreach (self::$cached_extra as $item) {
            if (!empty($item['field_key'])) {
                $types[$item['field_key']] = $item['type'] ?? 'text';
            }
        }

        foreach ($types as $extra_key => $type) {
            if (!isset($posted[$extra_key])) {
                continue;
            }
            $raw = $posted[$extra_key];
            if (is_array($raw)) {
                $clean = implode(', ', array_map('sanitize_text_field', $raw));
            } else {
                $clean = ('textarea' === $type) ? sanitize_textarea_field((string) $raw) : sanitize_text_field((string) $raw);
            }
            $order->update_meta_data('_ka_' . sanitize_key((string) $extra_key), $clean);
        }
        $order->save();
    }
}






