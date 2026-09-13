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
use King_Addons\Woo_Builder\Context as Woo_Context;
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
        return 'king-addons-icon king-addons-woo-checkout-form';
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
     * Script dependencies.
     *
     * @return array<int, string>
     */
    public function get_script_depends(): array
    {
        return [KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-checkout-form-script'];
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
                'title_field' => '{{ field_key }}',
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
                'title_field' => '{{ field_key }}',
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
        self::$is_rendering = true;

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

        // Dedicated widgets already print these blocks; keep one copy.
        if (Woo_Context::template_has_widget('woo_checkout_login', 'checkout')) {
            $show_login = false;
        }
        if (Woo_Context::template_has_widget('woo_checkout_coupon', 'checkout')) {
            $show_coupon = false;
        }

        if (!$show_login) {
            remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10);
            $removed[] = 'login';
        }

        if (!$show_coupon) {
            remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
            $removed[] = 'coupon';
        }

        $hide_native_payment = Woo_Context::template_has_widget('woo_checkout_payment', 'checkout');
        if ($hide_native_payment) {
            remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
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
            $this->add_render_attribute(
                '_wrapper',
                'data-ka-fields-config',
                wp_json_encode(self::$cached_config)
            );
            add_filter('woocommerce_checkout_fields', [self::class, 'filter_checkout_fields'], 9999);
            add_action('woocommerce_checkout_update_order_meta', [self::class, 'save_extra_fields'], 20, 2);
            // The order is created by a separate ?wc-ajax=checkout request in
            // which no Elementor widget renders, so hooks added here are gone
            // by then - extra fields were shown, filled in and silently
            // dropped. Stash the configuration for that request to pick up.
            self::remember_config(self::$cached_config, self::$cached_extra);
            self::$remembered_this_request = true;
        } elseif (!self::$remembered_this_request) {
            // Only drop a stored configuration when nothing on this page set
            // one: a second, plainer checkout widget must not wipe what the
            // customised one just saved for the submit request.
            self::forget_config();
        }

        // WooCommerce has no woocommerce_checkout() function - the guard was
        // always false and the widget printed nothing at all. The checkout form
        // comes from the shortcode handler, which also covers the pay-for-order
        // and order-received states.
        // Style selectors target {{WRAPPER}} .woocommerce form.checkout.
        // WC_Shortcode_Checkout::output() prints the form without that wrap
        // on the checkout page, so padding/color never applied.
        echo '<div class="woocommerce">';
        if (class_exists('WC_Shortcode_Checkout')) {
            \WC_Shortcode_Checkout::output([]);
        } elseif (function_exists('woocommerce_checkout')) {
            call_user_func('woocommerce_checkout');
        }
        echo '</div>';

        if (in_array('login', $removed, true)) {
            add_action('woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10);
        }
        if (in_array('coupon', $removed, true)) {
            add_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
        }
        if ($hide_native_payment) {
            add_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
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

        self::$is_rendering = false;
    }

    /**
     * Session key holding the field configuration for the submit request.
     */
    private const SESSION_KEY = 'king_addons_checkout_fields';

    /**
     * True while a checkout form widget is rendering.
     *
     * @var bool
     */
    private static $is_rendering = false;

    /**
     * True once a widget in this request stored a field configuration.
     *
     * @var bool
     */
    private static $remembered_this_request = false;

    /**
     * Store the configuration for the request that actually creates the order.
     *
     * @param array<int,array<string,mixed>> $config Existing-field settings.
     * @param array<int,array<string,mixed>> $extra  Extra field definitions.
     *
     * @return void
     */
    private static function remember_config(array $config, array $extra): void
    {
        if (!function_exists('WC') || !WC()->session) {
            return;
        }

        WC()->session->set(self::SESSION_KEY, ['config' => $config, 'extra' => $extra]);
    }

    /**
     * Drop a previously stored configuration.
     *
     * @return void
     */
    private static function forget_config(): void
    {
        if (!function_exists('WC') || !WC()->session) {
            return;
        }

        if (WC()->session->get(self::SESSION_KEY)) {
            WC()->session->set(self::SESSION_KEY, null);
        }
    }

    /**
     * Read the stored configuration.
     *
     * @return array{config: array<int,array<string,mixed>>, extra: array<int,array<string,mixed>>}|null
     */
    private static function stored_config(): ?array
    {
        if (!function_exists('WC') || !WC()->session) {
            return null;
        }

        $stored = WC()->session->get(self::SESSION_KEY);
        if (!is_array($stored)) {
            return null;
        }

        return [
            'config' => isset($stored['config']) && is_array($stored['config']) ? $stored['config'] : [],
            'extra' => isset($stored['extra']) && is_array($stored['extra']) ? $stored['extra'] : [],
        ];
    }

    /**
     * Hook the checkout-field handling for requests that never render a widget.
     *
     * Registered once at plugin load; both callbacks do nothing unless the
     * checkout form widget stored a configuration for this visitor.
     *
     * @return void
     */
    public static function register_persistent_hooks(): void
    {
        add_filter('woocommerce_checkout_fields', [self::class, 'filter_stored_checkout_fields'], 9998);
        add_action('woocommerce_checkout_update_order_meta', [self::class, 'save_stored_extra_fields'], 20, 2);
        add_action('woocommerce_admin_order_data_after_billing_address', [self::class, 'render_extra_fields_admin']);
        add_action('woocommerce_order_details_after_order_table', [self::class, 'render_extra_fields_front']);
        add_filter('woocommerce_email_order_meta_fields', [self::class, 'email_extra_fields'], 10, 3);
    }

    /**
     * Apply the stored field configuration.
     *
     * @param array<string,array<string,array<string,mixed>>> $fields WC checkout fields.
     *
     * @return array<string,array<string,array<string,mixed>>>
     */
    public static function filter_stored_checkout_fields(array $fields): array
    {
        // A widget on the page owns its own settings - including the choice not
        // to customise anything. Applying the stored copy during any render
        // would leak one widget's field setup into its neighbours.
        if (self::$is_rendering) {
            return $fields;
        }

        $stored = self::stored_config();
        if (null === $stored) {
            return $fields;
        }

        return self::tune_checkout_fields($fields, $stored['config'], $stored['extra']);
    }

    /**
     * Save extra fields using the stored configuration.
     *
     * @param int   $order_id Order ID.
     * @param array $data     Posted data.
     *
     * @return void
     */
    public static function save_stored_extra_fields(int $order_id, array $data): void
    {
        if (self::$is_rendering || !empty(self::$cached_extra)) {
            return;
        }

        $stored = self::stored_config();
        if (null === $stored || empty($stored['extra'])) {
            return;
        }

        self::$cached_extra = $stored['extra'];
        self::save_extra_fields($order_id, $data);
        self::$cached_extra = [];
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
        $labels = [];
        foreach (self::$cached_extra as $item) {
            if (!empty($item['field_key'])) {
                $types[$item['field_key']] = $item['type'] ?? 'text';
                $labels[$item['field_key']] = (string) ($item['label'] ?? $item['field_key']);
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
        if (!empty($labels)) {
            $order->update_meta_data('_ka_extra_labels', $labels);
        }
        $order->save();
    }

    /**
     * Extra checkout fields saved on the order, as key => [label, value].
     *
     * @param \WC_Order $order Order.
     *
     * @return array<string,array{label:string,value:string}>
     */
    public static function extra_fields_from_order($order): array
    {
        if (!$order instanceof \WC_Order) {
            return [];
        }

        $labels = $order->get_meta('_ka_extra_labels');
        if (!is_array($labels) || empty($labels)) {
            return [];
        }

        $out = [];
        foreach ($labels as $key => $label) {
            $value = $order->get_meta('_ka_' . sanitize_key((string) $key));
            if ('' === (string) $value) {
                continue;
            }
            $out[(string) $key] = [
                'label' => sanitize_text_field((string) $label),
                'value' => sanitize_text_field((string) $value),
            ];
        }

        return $out;
    }

    /**
     * Show extra fields in wp-admin order screen.
     *
     * @param \WC_Order $order Order.
     *
     * @return void
     */
    public static function render_extra_fields_admin($order): void
    {
        $fields = self::extra_fields_from_order($order);
        if (empty($fields)) {
            return;
        }

        echo '<div class="ka-order-extra-fields">';
        echo '<h3>' . esc_html__('Extra fields', 'king-addons') . '</h3>';
        foreach ($fields as $field) {
            echo '<p><strong>' . esc_html($field['label']) . ':</strong> ' . esc_html($field['value']) . '</p>';
        }
        echo '</div>';
    }

    /**
     * Show extra fields on the thank-you / view-order screens.
     *
     * @param \WC_Order $order Order.
     *
     * @return void
     */
    public static function render_extra_fields_front($order): void
    {
        $fields = self::extra_fields_from_order($order);
        if (empty($fields)) {
            return;
        }

        echo '<section class="ka-order-extra-fields">';
        echo '<h2>' . esc_html__('Extra fields', 'king-addons') . '</h2>';
        echo '<table class="woocommerce-table shop_table"><tbody>';
        foreach ($fields as $field) {
            echo '<tr><th>' . esc_html($field['label']) . '</th><td>' . esc_html($field['value']) . '</td></tr>';
        }
        echo '</tbody></table></section>';
    }

    /**
     * Extra fields in WooCommerce emails. Values are plain text on purpose:
     * this filter is printed raw in both HTML and plain-text mail.
     *
     * @param array<string,array<string,string>> $fields  Existing meta fields.
     * @param bool                               $sent_to_admin Unused.
     * @param \WC_Order                          $order   Order.
     *
     * @return array<string,array<string,string>>
     */
    public static function email_extra_fields($fields, $sent_to_admin, $order): array
    {
        unset($sent_to_admin);
        if (!is_array($fields)) {
            $fields = [];
        }

        foreach (self::extra_fields_from_order($order) as $key => $field) {
            $fields['ka_' . sanitize_key((string) $key)] = [
                'label' => $field['label'],
                'value' => $field['value'],
            ];
        }

        return $fields;
    }
}






