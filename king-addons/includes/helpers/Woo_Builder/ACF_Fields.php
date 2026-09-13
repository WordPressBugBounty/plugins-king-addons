<?php
/**
 * ACF fields collected by the Woo Builder checkout and My Account widgets.
 *
 * @package King_Addons
 */

namespace King_Addons\Woo_Builder;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders, validates and stores the fields of the Checkout ACF Extra Fields and
 * My Account ACF Extra Fields widgets.
 *
 * Checkout values are stored on the order under the ACF post id
 * "woo_order_{id}" - the address ACF PRO uses for WooCommerce orders, so
 * get_field('name', 'woo_order_123') reads them back. My Account values go to
 * the customer's profile, "user_{id}".
 */
final class ACF_Fields
{
    /**
     * Hidden input carrying the signed field list of one checkout block.
     */
    private const CHECKOUT_INPUT = 'ka_acf_checkout';

    /**
     * Hidden input carrying the signed field list of one account form.
     */
    private const ACCOUNT_INPUT = 'ka_acf_account';

    /**
     * Order meta recording which fields the checkout stored on the order.
     */
    private const ORDER_META = '_ka_acf_checkout_fields';

    /**
     * Field types a visitor cannot fill in here: uploads need an uploader and a
     * multipart request, which the AJAX checkout does not send; tabs and
     * accordions only make sense inside a full ACF form.
     */
    private const UNSUPPORTED = ['image', 'file', 'gallery', 'tab', 'accordion'];

    /**
     * Checkout placements and the WooCommerce hook each one prints in.
     */
    private const PLACEMENTS = [
        'before_billing' => 'woocommerce_before_checkout_billing_form',
        'after_billing' => 'woocommerce_after_checkout_billing_form',
        'before_shipping' => 'woocommerce_before_checkout_shipping_form',
        'after_shipping' => 'woocommerce_after_checkout_shipping_form',
        'before_order' => 'woocommerce_before_order_notes',
        'after_order' => 'woocommerce_after_order_notes',
    ];

    /**
     * Order being written by save_checkout(), saved once at the end.
     *
     * @var \WC_Order|null
     */
    private static $active_order = null;

    /**
     * True while an account form writes to the customer's profile.
     *
     * @var bool
     */
    private static $saving_user = false;

    /**
     * Id of the account form whose submission failed validation.
     *
     * @var string
     */
    private static $account_failed = '';

    /**
     * Messages of that failed submission.
     *
     * @var array<int,string>
     */
    private static $account_errors = [];

    /**
     * Hook storage, checkout processing, display and the account form.
     *
     * Registered at plugin load: the order is created by ?wc-ajax=checkout,
     * a request in which no Elementor widget renders.
     *
     * @return void
     */
    public static function register(): void
    {
        add_filter('acf/pre_load_metadata', [self::class, 'load_meta'], 5, 4);
        add_filter('acf/pre_update_metadata', [self::class, 'update_meta'], 5, 5);
        add_filter('acf/pre_delete_metadata', [self::class, 'delete_meta'], 5, 4);

        add_action('woocommerce_after_checkout_validation', [self::class, 'validate_checkout'], 20, 2);
        add_action('woocommerce_checkout_update_order_meta', [self::class, 'save_checkout'], 20, 1);

        add_action('woocommerce_admin_order_data_after_order_details', [self::class, 'print_admin_values']);
        add_action('woocommerce_order_details_after_customer_details', [self::class, 'print_customer_values']);
        // Block themes show the thank-you page with WooCommerce's Order
        // Confirmation blocks, which never run the classic template hook above.
        add_filter('render_block_woocommerce/order-confirmation-totals-wrapper', [self::class, 'append_to_confirmation']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_order_view_styles'], 20);
        // The action, not the woocommerce_email_order_meta_fields filter: the
        // filter hands one value to both HTML and plain-text emails and
        // WooCommerce prints it raw in both, so it cannot be escaped for each.
        add_action('woocommerce_email_order_meta', [self::class, 'print_email_values'], 20, 3);

        add_action('template_redirect', [self::class, 'handle_account_form']);

        add_action('elementor/preview/enqueue_styles', [self::class, 'enqueue_preview_styles']);
    }

    /**
     * ACF's field styles in the Elementor preview of checkout and account
     * templates. The editor renders the widgets in its own request and loads
     * the assets there, not in the preview frame, so the preview showed bare
     * browser inputs.
     *
     * @return void
     */
    public static function enqueue_preview_styles(): void
    {
        if (in_array(Context::get_editor_template_type(), ['checkout', 'my_account'], true)) {
            wp_enqueue_style('acf-input');
        }
    }

    /* ---------------------------------------------------------------------
     * Rendering
     * ------------------------------------------------------------------ */

    /**
     * Output of the Checkout ACF Extra Fields widget.
     *
     * The inputs have to be inside WooCommerce's checkout form to travel with
     * the order, so they are printed from the hook the Placement control names
     * rather than where the widget sits. When the form has already been printed
     * above the widget, the block goes out in a hidden carrier and the widget
     * script moves it into the form.
     *
     * @param array<string,mixed> $settings  Widget settings for display.
     * @param string              $widget_id Elementor element id.
     *
     * @return void
     */
    public static function render_checkout(array $settings, string $widget_id): void
    {
        $editor = Context::is_editor();

        if (!self::acf_ready()) {
            if ($editor) {
                self::print_notice(esc_html__('Install and activate Advanced Custom Fields to use this widget.', 'king-addons'));
            }
            return;
        }

        $placement = (string) ($settings['placement'] ?? 'after_order');
        if (!isset(self::PLACEMENTS[$placement])) {
            $placement = 'after_order';
        }

        $resolved = self::resolve(self::keys_from_settings($settings), 'checkout');
        $heading = trim((string) ($settings['heading'] ?? ''));
        $classes = self::block_classes('ka-woo-checkout-acf-fields', $widget_id, $settings);

        if ($editor) {
            self::print_preview('ka-woo-checkout-acf-fields', $classes, $heading, $resolved, sprintf(
                /* translators: %s: where the fields go, e.g. "after order notes". */
                esc_html__('Shown inside the checkout form, %s. Values are saved to the order.', 'king-addons'),
                self::placement_label($placement)
            ));
            return;
        }

        if (!$resolved['fields']) {
            return;
        }

        // WooCommerce prints the shipping form only for carts that need a
        // shipping address; its hooks never fire otherwise.
        if (in_array($placement, ['before_shipping', 'after_shipping'], true)
            && function_exists('WC') && WC()->cart && !WC()->cart->needs_shipping_address()) {
            $placement = 'after_order';
        }

        $token = self::sign([
            'c' => 'checkout',
            'k' => array_keys($resolved['fields']),
            'p' => $placement,
            'h' => $heading,
        ]);

        $block = self::checkout_block($classes, $heading, $resolved['fields'], $token, $placement);
        $hook = self::PLACEMENTS[$placement];

        // The checkout form widget often sits above this one. Once that form
        // has printed, adding the placement hook is too late - the hook already
        // ran, or (order notes disabled) it never will. Hand the block to JS
        // in a carrier instead.
        $form_already_printed = did_action('woocommerce_before_checkout_form') > 0
            || did_action('woocommerce_after_checkout_form') > 0
            || did_action('woocommerce_checkout_order_review') > 0;

        if (!$form_already_printed && !did_action($hook)) {
            $printed = false;
            add_action($hook, static function () use ($block, &$printed): void {
                // A page can hold a second checkout form; the fields belong to one.
                if ($printed) {
                    return;
                }
                $printed = true;
                echo $block; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts and ACF's own renderer.
            }, 20);
            return;
        }

        echo '<div class="ka-woo-checkout-acf-fields-carrier" data-ka-acf-placement="' . esc_attr($placement) . '" hidden>';
        echo $block; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts and ACF's own renderer.
        echo '</div>';
    }

    /**
     * Output of the My Account ACF Extra Fields widget: a form of its own that
     * saves the fields to the customer's profile.
     *
     * @param array<string,mixed> $settings  Widget settings for display.
     * @param string              $widget_id Elementor element id.
     *
     * @return void
     */
    public static function render_account(array $settings, string $widget_id): void
    {
        $editor = Context::is_editor();

        if (!self::acf_ready()) {
            if ($editor) {
                self::print_notice(esc_html__('Install and activate Advanced Custom Fields to use this widget.', 'king-addons'));
            }
            return;
        }

        $resolved = self::resolve(self::keys_from_settings($settings), 'account');
        $heading = trim((string) ($settings['heading'] ?? ''));
        $classes = self::block_classes('ka-woo-account-acf-fields', $widget_id, $settings);

        if ($editor) {
            self::print_preview('ka-woo-account-acf-fields', $classes, $heading, $resolved, esc_html__('Customers fill these in and save them to their profile with the button below the fields.', 'king-addons'));
            return;
        }

        if (!is_user_logged_in() || !$resolved['fields']) {
            return;
        }

        $keys = array_keys($resolved['fields']);
        $form_id = substr(md5($widget_id . '|' . implode(',', $keys)), 0, 12);
        $failed = self::$account_failed === $form_id;
        $user_id = 'user_' . get_current_user_id();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only compared, never stored.
        $saved = isset($_GET['ka-acf-saved']) && $form_id === sanitize_key(wp_unslash($_GET['ka-acf-saved']));

        self::enqueue_acf();

        echo '<div class="' . esc_attr($classes) . '">';
        if ('' !== $heading) {
            echo '<h3 class="ka-woo-account-acf-fields__heading">' . esc_html($heading) . '</h3>';
        }

        if ($failed && self::$account_errors) {
            echo '<ul class="woocommerce-error" role="alert">';
            foreach (self::$account_errors as $message) {
                echo '<li>' . wp_kses_post($message) . '</li>';
            }
            echo '</ul>';
        } elseif ($saved) {
            echo '<div class="woocommerce-message" role="status">' . esc_html__('Your details have been saved.', 'king-addons') . '</div>';
        }

        echo '<form method="post" class="ka-woo-account-acf-fields__form" action="' . esc_url(remove_query_arg('ka-acf-saved')) . '">';
        echo '<div class="acf-fields ka-acf-fields">';
        foreach ($resolved['fields'] as $field) {
            // After a failed submission show what the customer typed, not the stored value.
            $value = $failed ? self::posted_value($field) : acf_get_value($user_id, $field);
            self::render_field($field, $value);
        }
        echo '</div>';

        /**
         * Print extra markup inside the My Account fields form. Inputs added
         * here are submitted with it but not stored by King Addons.
         */
        do_action('king_addons_my_account_acf_fields');

        wp_nonce_field('ka_acf_account_' . $form_id, '_ka_acf_nonce');
        echo '<input type="hidden" name="' . esc_attr(self::ACCOUNT_INPUT) . '" value="' . esc_attr(self::sign(['c' => 'account', 'k' => $keys, 'f' => $form_id])) . '" />';

        $button_class = function_exists('wc_wp_theme_get_element_class_name') ? wc_wp_theme_get_element_class_name('button') : '';
        echo '<p class="ka-woo-account-acf-fields__actions">';
        echo '<button type="submit" class="woocommerce-Button button' . ($button_class ? ' ' . esc_attr($button_class) : '') . '" name="ka_acf_account_save" value="1">' . esc_html__('Save changes', 'king-addons') . '</button>';
        echo '</p>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Field keys chosen in a widget: the Pro repeater plus the free text control.
     *
     * @param array<string,mixed> $settings Widget settings.
     *
     * @return array<int,string>
     */
    public static function keys_from_settings(array $settings): array
    {
        $keys = [];

        foreach ((array) ($settings['acf_fields'] ?? []) as $row) {
            $key = is_array($row) ? trim((string) ($row['field_key'] ?? '')) : '';
            if ('' !== $key) {
                $keys[] = $key;
            }
        }

        foreach (explode(',', (string) ($settings['field_keys'] ?? '')) as $key) {
            $key = trim($key);
            if ('' !== $key) {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Classes of a field block. The per-widget class lets the style controls
     * reach the block after it has been printed inside the checkout form,
     * outside the widget's own wrapper.
     *
     * @param string              $base      Base class.
     * @param string              $widget_id Elementor element id.
     * @param array<string,mixed> $settings  Widget settings.
     *
     * @return string
     */
    private static function block_classes(string $base, string $widget_id, array $settings): string
    {
        $classes = [$base, 'ka-acf-block', 'ka-acf-block-' . sanitize_html_class($widget_id)];

        if ('yes' !== ($settings['required_notice'] ?? 'yes')) {
            $classes[] = 'ka-acf-no-required-mark';
        }

        return implode(' ', $classes);
    }

    /**
     * The checkout block as HTML.
     *
     * @param string                           $classes   Block classes.
     * @param string                           $heading   Heading text.
     * @param array<string,array<string,mixed>> $fields    Resolved fields.
     * @param string                           $token     Signed field list.
     * @param string                           $placement Placement key.
     *
     * @return string
     */
    private static function checkout_block(string $classes, string $heading, array $fields, string $token, string $placement): string
    {
        self::enqueue_acf();

        ob_start();
        echo '<div class="' . esc_attr($classes) . '" data-ka-acf-placement="' . esc_attr($placement) . '">';
        if ('' !== $heading) {
            echo '<h3 class="ka-woo-checkout-acf-fields__heading">' . esc_html($heading) . '</h3>';
        }
        echo '<div class="acf-fields ka-acf-fields">';
        foreach ($fields as $field) {
            self::render_field($field, $field['default_value'] ?? '');
        }
        echo '</div>';

        /**
         * Print extra markup inside the checkout block, which sits in the
         * checkout form. Inputs added here are submitted with the order but not
         * stored by King Addons.
         *
         * @param array<int,string> $keys      ACF field keys shown.
         * @param bool              $required  Kept for compatibility; always true.
         * @param string            $placement Placement key.
         */
        do_action('king_addons_checkout_acf_fields', array_keys($fields), true, $placement);
        echo '<input type="hidden" name="' . esc_attr(self::CHECKOUT_INPUT) . '[]" value="' . esc_attr($token) . '" />';
        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * Print one field with ACF's renderer under the acf[field_key] input name.
     *
     * @param array<string,mixed> $field ACF field.
     * @param mixed               $value Value to show.
     *
     * @return void
     */
    private static function render_field(array $field, $value): void
    {
        $field = self::with_woo_classes($field);
        $field['value'] = $value;
        $field['prefix'] = 'acf';

        ob_start();
        acf_render_field_wrap($field);
        $html = (string) ob_get_clean();

        // Date and time pickers ignore the field's class setting and print
        // their visible input with a fixed class="input".
        echo str_replace('<input type="text" class="input"', '<input type="text" class="input input-text"', $html); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ACF's own escaped markup.
    }

    /**
     * Give a field WooCommerce's row and input classes, so the theme styles it
     * like the checkout and account fields around it instead of ACF's admin look.
     *
     * @param array<string,mixed> $field ACF field.
     *
     * @return array<string,mixed>
     */
    private static function with_woo_classes(array $field): array
    {
        $wrapper = is_array($field['wrapper'] ?? null) ? $field['wrapper'] : [];
        $wrapper['class'] = trim((string) ($wrapper['class'] ?? '') . ' form-row form-row-wide');
        $field['wrapper'] = $wrapper;

        if (in_array($field['type'] ?? '', ['text', 'email', 'url', 'number', 'password', 'textarea'], true)) {
            $field['class'] = trim((string) ($field['class'] ?? '') . ' input-text');
        }

        if (!empty($field['sub_fields']) && is_array($field['sub_fields'])) {
            $field['sub_fields'] = array_map([self::class, 'with_woo_classes'], $field['sub_fields']);
        }

        return $field;
    }

    /**
     * Editor preview: the fields where the widget sits, plus where they go.
     *
     * @param string                                                     $base     Base class of the block.
     * @param string                                                     $classes  Block classes.
     * @param string                                                     $heading  Heading text.
     * @param array{fields:array<string,array<string,mixed>>,skipped:array<int,string>} $resolved Resolved fields.
     * @param string                                                     $note     Escaped explanation.
     *
     * @return void
     */
    private static function print_preview(string $base, string $classes, string $heading, array $resolved, string $note): void
    {
        self::enqueue_acf();

        echo '<div class="' . esc_attr($classes) . ' ka-acf-preview">';
        if ('' !== $heading) {
            echo '<h3 class="' . esc_attr($base . '__heading') . '">' . esc_html($heading) . '</h3>';
        }

        if (!$resolved['fields'] && !$resolved['skipped']) {
            self::print_notice(esc_html__('Choose ACF fields in the ACF Fields section.', 'king-addons'));
        }

        if ($resolved['fields']) {
            echo '<div class="acf-fields ka-acf-fields">';
            foreach ($resolved['fields'] as $field) {
                self::render_field($field, $field['default_value'] ?? '');
            }
            echo '</div>';
            echo '<p class="ka-acf-preview__note">' . $note . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the caller.
        }

        foreach ($resolved['skipped'] as $message) {
            self::print_notice(esc_html($message));
        }

        echo '</div>';
    }

    /**
     * Print an editor notice.
     *
     * @param string $message Escaped message.
     *
     * @return void
     */
    private static function print_notice(string $message): void
    {
        echo '<div class="king-addons-woo-builder-notice">' . $message . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the caller.
    }

    /**
     * Human wording of a placement.
     *
     * @param string $placement Placement key.
     *
     * @return string Escaped text.
     */
    private static function placement_label(string $placement): string
    {
        $labels = [
            'before_billing' => esc_html__('before the billing fields', 'king-addons'),
            'after_billing' => esc_html__('after the billing fields', 'king-addons'),
            'before_shipping' => esc_html__('before the shipping fields', 'king-addons'),
            'after_shipping' => esc_html__('after the shipping fields', 'king-addons'),
            'before_order' => esc_html__('before the order notes', 'king-addons'),
            'after_order' => esc_html__('after the order notes', 'king-addons'),
        ];

        return $labels[$placement] ?? $labels['after_order'];
    }

    /**
     * ACF's scripts and styles are admin-only by default; without them a select,
     * a date picker or conditional logic is dead markup on the storefront.
     *
     * @return void
     */
    private static function enqueue_acf(): void
    {
        static $done = false;

        if ($done || !function_exists('acf_enqueue_scripts')) {
            return;
        }
        $done = true;

        acf_enqueue_scripts();

        // ACF asks "Leave site?" when a page with changed fields is left without
        // it seeing the form submit. WooCommerce submits the checkout over AJAX
        // and stops that event, so the dialog met the customer on the way to
        // the thank-you page. It is an admin safeguard; a shop has no use for it.
        wp_add_inline_script('acf-input', 'if(window.acf&&acf.unload){acf.unload.disable();}');
    }

    /* ---------------------------------------------------------------------
     * Fields
     * ------------------------------------------------------------------ */

    /**
     * Whether the ACF functions this class relies on are loaded.
     *
     * @return bool
     */
    private static function acf_ready(): bool
    {
        return function_exists('acf_get_field')
            && function_exists('acf_render_field_wrap')
            && function_exists('acf_validate_value')
            && function_exists('acf_update_value')
            && function_exists('acf_get_value');
    }

    /**
     * Turn configured keys or names into ACF fields that can be used here.
     *
     * @param array<int,string> $keys    Field keys or names.
     * @param string            $context "checkout" or "account".
     *
     * @return array{fields:array<string,array<string,mixed>>,skipped:array<int,string>}
     */
    private static function resolve(array $keys, string $context): array
    {
        $fields = [];
        $skipped = [];

        foreach ($keys as $key) {
            $field = acf_get_field($key);
            if (!$field || empty($field['key'])) {
                /* translators: %s: ACF field key or name. */
                $skipped[] = sprintf(__('%s: there is no ACF field with this key or name.', 'king-addons'), $key);
                continue;
            }

            $label = '' !== (string) ($field['label'] ?? '') ? (string) $field['label'] : (string) $field['name'];

            if (in_array($field['type'], self::UNSUPPORTED, true)) {
                /* translators: 1: field label, 2: ACF field type. */
                $skipped[] = sprintf(__('%1$s: "%2$s" fields cannot be filled in here and are left out.', 'king-addons'), $label, $field['type']);
                continue;
            }

            if (!self::storable($field, $context)) {
                /* translators: %s: field label. */
                $skipped[] = sprintf(__('%s: the field name is already used by WooCommerce or WordPress for this record, so it is left out. Rename the field in ACF.', 'king-addons'), $label);
                continue;
            }

            $fields[$field['key']] = $field;
        }

        return ['fields' => $fields, 'skipped' => $skipped];
    }

    /**
     * Fields named in a verified token, re-checked against the live ACF setup.
     *
     * @param array<int,mixed> $keys    Field keys from the token.
     * @param string           $context "checkout" or "account".
     *
     * @return array<string,array<string,mixed>>
     */
    private static function token_fields(array $keys, string $context): array
    {
        return self::resolve(array_map('strval', $keys), $context)['fields'];
    }

    /**
     * Whether a field can be stored without touching data the record keeps for
     * itself. ACF writes the value under the field name and a reference under
     * "_name"; on an order WooCommerce turns its own keys into setter calls, so a
     * field called "order_total" would rewrite the order total.
     *
     * @param array<string,mixed> $field   ACF field.
     * @param string              $context "checkout" or "account".
     * @param string              $prefix  Name prefix of a parent group.
     *
     * @return bool
     */
    private static function storable(array $field, string $context, string $prefix = ''): bool
    {
        $name = $prefix . (string) ($field['name'] ?? '');
        if ('' === $name || '_' === $name[0]) {
            return false;
        }

        $reserved = 'checkout' === $context
            ? self::is_reserved_order_key($name) || self::is_reserved_order_key('_' . $name)
            : self::is_reserved_user_key($name);

        if ($reserved) {
            return false;
        }

        if ('group' === ($field['type'] ?? '') && !empty($field['sub_fields'])) {
            foreach ((array) $field['sub_fields'] as $sub_field) {
                if (is_array($sub_field) && !self::storable($sub_field, $context, $name . '_')) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Order meta keys WooCommerce keeps for itself.
     *
     * @param string $key Meta key.
     *
     * @return bool
     */
    private static function is_reserved_order_key(string $key): bool
    {
        static $internal = null;

        if (null === $internal) {
            $internal = [];
            if (class_exists('WC_Order')) {
                try {
                    $internal = (array) (new \WC_Order())->get_data_store()->get_internal_meta_keys();
                } catch (\Throwable $e) {
                    $internal = [];
                }
            }
        }

        return self::ORDER_META === $key || in_array($key, $internal, true);
    }

    /**
     * User meta keys a customer must not be able to write: roles and
     * capabilities live under the table prefix, sessions under session_tokens.
     *
     * @param string $key Meta key.
     *
     * @return bool
     */
    private static function is_reserved_user_key(string $key): bool
    {
        global $wpdb;

        if ('' === $key || '_' === $key[0] || 'session_tokens' === $key) {
            return true;
        }

        return 0 === strpos($key, $wpdb->base_prefix);
    }

    /* ---------------------------------------------------------------------
     * Signed field lists
     * ------------------------------------------------------------------ */

    /**
     * Sign a field list so a submission can only write the fields the page
     * actually showed, whatever else is posted under acf[...].
     *
     * @param array<string,mixed> $payload Data to sign.
     *
     * @return string
     */
    private static function sign(array $payload): string
    {
        $data = rtrim(strtr(base64_encode((string) wp_json_encode($payload)), '+/', '-_'), '=');

        return $data . '.' . hash_hmac('sha256', $data, wp_salt('nonce'));
    }

    /**
     * Verify a signed field list.
     *
     * @param mixed  $token   Posted token.
     * @param string $context Expected context.
     *
     * @return array<string,mixed>|null
     */
    private static function verify($token, string $context): ?array
    {
        if (!is_string($token) || false === strpos($token, '.')) {
            return null;
        }

        [$data, $mac] = explode('.', $token, 2);
        if (!hash_equals(hash_hmac('sha256', $data, wp_salt('nonce')), $mac)) {
            return null;
        }

        $payload = json_decode((string) base64_decode(strtr($data, '-_', '+/')), true);
        if (!is_array($payload) || ($payload['c'] ?? '') !== $context || empty($payload['k']) || !is_array($payload['k'])) {
            return null;
        }

        return $payload;
    }

    /**
     * Verified checkout blocks of the current submission.
     *
     * @return array<int,array<string,mixed>>
     */
    private static function posted_checkout_blocks(): array
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verified the checkout nonce before these hooks run.
        $tokens = isset($_POST[self::CHECKOUT_INPUT]) ? (array) wp_unslash($_POST[self::CHECKOUT_INPUT]) : [];
        $blocks = [];

        foreach ($tokens as $token) {
            $payload = self::verify($token, 'checkout');
            if (null === $payload) {
                continue;
            }

            // Fields placed in the shipping form are hidden with it when the
            // order ships to the billing address; WooCommerce ignores its own
            // shipping fields then, and so do we.
            $placement = (string) ($payload['p'] ?? '');
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if (in_array($placement, ['before_shipping', 'after_shipping'], true) && empty($_POST['ship_to_different_address'])) {
                continue;
            }

            $blocks[] = $payload;
        }

        return $blocks;
    }

    /**
     * The submitted value of a field, or null when the browser sent nothing -
     * ACF disables the inputs of a field its conditional logic hides.
     *
     * @param array<string,mixed> $field       ACF field.
     * @param bool                $for_storage Prepare for acf_update_value(): strip
     *                                         HTML the visitor may not post, as
     *                                         ACF's own front-end forms do, and
     *                                         slash it the way WordPress meta
     *                                         functions expect.
     *
     * @return mixed
     */
    private static function posted_value(array $field, bool $for_storage = false)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the callers verify the request.
        if (!isset($_POST['acf']) || !is_array($_POST['acf']) || !array_key_exists($field['key'], $_POST['acf'])) {
            return null;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised below per field type by ACF.
        $value = wp_unslash($_POST['acf'][$field['key']]);

        if (!$for_storage) {
            return $value;
        }

        if (!current_user_can('unfiltered_html')) {
            $value = wp_kses_post_deep($value);
        }

        return wp_slash($value);
    }

    /**
     * Validate the given fields with ACF's own rules.
     *
     * @param array<string,array<string,mixed>> $fields Fields to validate.
     *
     * @return array<int,array{input:string,message:string}>
     */
    private static function validate(array $fields): array
    {
        acf_reset_validation_errors();

        foreach ($fields as $field) {
            $value = self::posted_value($field);
            if (null === $value && !empty($field['conditional_logic'])) {
                continue;
            }

            $input = 'acf[' . $field['key'] . ']';
            if (acf_validate_value($value ?? '', $field, $input) && !self::is_offered_choice($field, $value)) {
                /* translators: %s: field label. */
                acf_add_validation_error($input, sprintf(__('%s: choose one of the offered options.', 'king-addons'), $field['label']));
            }
        }

        // ACF returns false, not an empty array, when there is nothing to report.
        $errors = acf_get_validation_errors();
        acf_reset_validation_errors();

        return is_array($errors) ? array_values(array_filter($errors, 'is_array')) : [];
    }

    /**
     * Whether a choice field got only choices it offers. ACF itself stores
     * whatever a crafted request sends, and the shop would see it on the order.
     *
     * @param array<string,mixed> $field ACF field.
     * @param mixed               $value Submitted value.
     *
     * @return bool
     */
    private static function is_offered_choice(array $field, $value): bool
    {
        if (!in_array($field['type'] ?? '', ['select', 'radio', 'button_group', 'checkbox'], true)
            || !empty($field['allow_custom']) || !empty($field['other_choice'])) {
            return true;
        }

        $choices = array_map('strval', array_keys((array) ($field['choices'] ?? [])));

        foreach ((array) $value as $item) {
            if (!is_scalar($item) || ('' !== (string) $item && !in_array((string) $item, $choices, true))) {
                return false;
            }
        }

        return true;
    }

    /* ---------------------------------------------------------------------
     * Checkout
     * ------------------------------------------------------------------ */

    /**
     * Report invalid fields the way WooCommerce reports its own.
     *
     * @param array<string,mixed> $data   Posted checkout data.
     * @param \WP_Error           $errors Checkout errors.
     *
     * @return void
     */
    public static function validate_checkout($data, $errors): void
    {
        if (!($errors instanceof \WP_Error) || !self::acf_ready()) {
            return;
        }

        $fields = [];
        foreach (self::posted_checkout_blocks() as $block) {
            $fields += self::token_fields($block['k'], 'checkout');
        }

        if (!$fields) {
            return;
        }

        foreach (self::validate($fields) as $error) {
            $input = (string) ($error['input'] ?? '');
            $id = function_exists('acf_idify') ? acf_idify($input) : sanitize_key($input);

            // One code per field: WP_Error keeps one data array per code, and
            // WooCommerce links each notice to its field through that data.
            $errors->add(
                'ka_acf_' . $id,
                wp_kses_post((string) ($error['message'] ?? '')),
                ['id' => $id]
            );
        }
    }

    /**
     * Store the fields on the order WooCommerce just created.
     *
     * @param int $order_id Order id.
     *
     * @return void
     */
    public static function save_checkout($order_id): void
    {
        if (!self::acf_ready() || !function_exists('wc_get_order')) {
            return;
        }

        $blocks = self::posted_checkout_blocks();
        if (!$blocks) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $record = [];
        self::$active_order = $order;

        try {
            foreach ($blocks as $block) {
                $stored = [];
                foreach (self::token_fields($block['k'], 'checkout') as $field) {
                    $value = self::posted_value($field, true);
                    if (null === $value) {
                        if (!empty($field['conditional_logic'])) {
                            continue;
                        }
                        $value = '';
                    }

                    acf_update_value($value, 'woo_order_' . $order->get_id(), $field);
                    $stored[] = ['key' => $field['key'], 'name' => $field['name'], 'label' => $field['label']];
                }

                if ($stored) {
                    $record[] = ['heading' => (string) ($block['h'] ?? ''), 'fields' => $stored];
                }
            }
        } finally {
            self::$active_order = null;
        }

        if ($record) {
            $order->update_meta_data(self::ORDER_META, $record);
        }
        $order->save();
    }

    /* ---------------------------------------------------------------------
     * My Account
     * ------------------------------------------------------------------ */

    /**
     * Save a submitted account form, then redirect so a reload does not post
     * it again. A failed submission renders the form again with the errors.
     *
     * @return void
     */
    public static function handle_account_form(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified below, once the form is known.
        if (empty($_POST['ka_acf_account_save']) || empty($_POST[self::ACCOUNT_INPUT]) || !self::acf_ready()) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- a signed token, verified here.
        $payload = self::verify(wp_unslash($_POST[self::ACCOUNT_INPUT]), 'account');
        if (null === $payload || !is_user_logged_in()) {
            return;
        }

        $form_id = sanitize_key((string) ($payload['f'] ?? ''));
        $nonce = isset($_POST['_ka_acf_nonce']) ? sanitize_text_field(wp_unslash($_POST['_ka_acf_nonce'])) : '';

        if (!wp_verify_nonce($nonce, 'ka_acf_account_' . $form_id)) {
            self::$account_failed = $form_id;
            self::$account_errors = [esc_html__('Your session has expired. Please try again.', 'king-addons')];
            return;
        }

        $fields = self::token_fields($payload['k'], 'account');
        $errors = self::validate($fields);

        if ($errors) {
            self::$account_failed = $form_id;
            self::$account_errors = array_map(static function ($error): string {
                return (string) ($error['message'] ?? '');
            }, $errors);
            return;
        }

        $user_id = 'user_' . get_current_user_id();
        self::$saving_user = true;

        try {
            foreach ($fields as $field) {
                $value = self::posted_value($field, true);
                if (null === $value) {
                    if (!empty($field['conditional_logic'])) {
                        continue;
                    }
                    $value = '';
                }
                acf_update_value($value, $user_id, $field);
            }
        } finally {
            self::$saving_user = false;
        }

        $back = wp_validate_redirect(wp_get_raw_referer(), wc_get_page_permalink('myaccount'));
        wp_safe_redirect(add_query_arg('ka-acf-saved', $form_id, remove_query_arg('ka-acf-saved', $back)));
        exit;
    }

    /* ---------------------------------------------------------------------
     * Order storage for ACF
     * ------------------------------------------------------------------ */

    /**
     * Order id from an ACF post id of the form "woo_order_123".
     *
     * @param mixed $post_id ACF post id.
     *
     * @return int 0 when it is not an order.
     */
    private static function order_id($post_id): int
    {
        if (is_string($post_id) && preg_match('/^woo_order_(\d+)$/', $post_id, $match)) {
            return (int) $match[1];
        }

        return 0;
    }

    /**
     * Whether ACF itself can store order meta (ACF PRO registers a location).
     *
     * @return bool
     */
    private static function acf_has_order_storage(): bool
    {
        static $native = null;

        if (null === $native) {
            $native = function_exists('acf_get_meta_instance') && null !== acf_get_meta_instance('woo_order');
        }

        return $native;
    }

    /**
     * The order an ACF call refers to.
     *
     * @param int $order_id Order id.
     *
     * @return \WC_Order|null
     */
    private static function order(int $order_id): ?\WC_Order
    {
        if (self::$active_order instanceof \WC_Order && self::$active_order->get_id() === $order_id) {
            return self::$active_order;
        }

        $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;

        return $order instanceof \WC_Order ? $order : null;
    }

    /**
     * Read ACF values of an order through WooCommerce, which knows whether
     * orders live in their own tables (HPOS) or as posts.
     *
     * @param mixed  $value   Short-circuit value.
     * @param mixed  $post_id ACF post id.
     * @param string $name    Meta name.
     * @param bool   $hidden  True for the "_name" reference.
     *
     * @return mixed
     */
    public static function load_meta($value, $post_id, $name, $hidden)
    {
        $order_id = self::order_id($post_id);
        if (null !== $value || !$order_id || self::acf_has_order_storage()) {
            return $value;
        }

        $key = ($hidden ? '_' : '') . $name;
        $order = self::order($order_id);

        if (!$order || self::is_reserved_order_key($key) || !$order->meta_exists($key)) {
            return '__return_null';
        }

        return $order->get_meta($key, true);
    }

    /**
     * Write ACF values of an order through WooCommerce; refuse keys the order
     * or the user record keeps for itself.
     *
     * @param mixed  $result  Short-circuit result.
     * @param mixed  $post_id ACF post id.
     * @param string $name    Meta name.
     * @param mixed  $value   Value, slashed.
     * @param bool   $hidden  True for the "_name" reference.
     *
     * @return mixed
     */
    public static function update_meta($result, $post_id, $name, $value, $hidden)
    {
        if (null !== $result) {
            return $result;
        }

        $key = ($hidden ? '_' : '') . $name;

        if (self::$saving_user && is_string($post_id) && 0 === strpos($post_id, 'user_')) {
            // References are "_name" by design. Values are checked here as well
            // as in storable(): a group composes its sub field names, so a
            // group "session" with a sub field "tokens" writes "session_tokens".
            return !$hidden && self::is_reserved_user_key($key) ? false : null;
        }

        $order_id = self::order_id($post_id);
        if (!$order_id) {
            return null;
        }

        if (self::is_reserved_order_key($key) || (!$hidden && '_' === substr($key, 0, 1))) {
            return false;
        }

        if (self::acf_has_order_storage()) {
            return null;
        }

        $order = self::order($order_id);
        if (!$order) {
            return false;
        }

        $order->update_meta_data($key, wp_unslash($value));

        // During checkout the order is saved once, after every field.
        if ($order !== self::$active_order) {
            $order->save_meta_data();
        }

        return true;
    }

    /**
     * Delete ACF values of an order through WooCommerce.
     *
     * @param mixed  $result  Short-circuit result.
     * @param mixed  $post_id ACF post id.
     * @param string $name    Meta name.
     * @param bool   $hidden  True for the "_name" reference.
     *
     * @return mixed
     */
    public static function delete_meta($result, $post_id, $name, $hidden)
    {
        $order_id = self::order_id($post_id);
        if (null !== $result || !$order_id || self::acf_has_order_storage()) {
            return $result;
        }

        $key = ($hidden ? '_' : '') . $name;
        $order = self::order($order_id);

        if (!$order || self::is_reserved_order_key($key)) {
            return false;
        }

        $order->delete_meta_data($key);
        if ($order !== self::$active_order) {
            $order->save_meta_data();
        }

        return true;
    }

    /* ---------------------------------------------------------------------
     * Showing stored values
     * ------------------------------------------------------------------ */

    /**
     * Stored checkout fields of an order as printable rows, grouped by block.
     *
     * @param mixed $order Order.
     *
     * @return array<int,array{heading:string,rows:array<int,array{key:string,label:string,value:string}>}>
     */
    private static function stored_rows($order): array
    {
        if (!$order instanceof \WC_Order) {
            return [];
        }

        $record = $order->get_meta(self::ORDER_META, true);
        if (!is_array($record) || !$record) {
            return [];
        }

        $post_id = 'woo_order_' . $order->get_id();
        $groups = [];
        $previous = self::$active_order;
        self::$active_order = $order;

        try {
            foreach ($record as $block) {
                $rows = [];
                foreach ((array) ($block['fields'] ?? []) as $stored) {
                    $key = (string) ($stored['key'] ?? '');
                    $field = self::acf_ready() && '' !== $key ? acf_get_field($key) : false;

                    if ($field) {
                        $value = self::display_value($field, acf_get_value($post_id, $field));
                        $label = (string) ($field['label'] ?: $field['name']);
                    } else {
                        // The field was deleted from ACF after the order: show what is stored.
                        $name = (string) ($stored['name'] ?? '');
                        $value = '' !== $name && !self::is_reserved_order_key($name) ? self::flatten($order->get_meta($name, true)) : '';
                        $label = (string) ($stored['label'] ?? $name);
                    }

                    // Browsers submit textarea line breaks as \r\n.
                    $value = str_replace(["\r\n", "\r"], "\n", $value);
                    if ('' === $value) {
                        continue;
                    }

                    $rows[] = ['key' => $key, 'label' => $label, 'value' => $value];
                }

                if ($rows) {
                    $groups[] = ['heading' => (string) ($block['heading'] ?? ''), 'rows' => $rows];
                }
            }
        } finally {
            self::$active_order = $previous;
        }

        return $groups;
    }

    /**
     * Order edit screen: below the customer, in the General column.
     *
     * @param mixed $order Order.
     *
     * @return void
     */
    public static function print_admin_values($order): void
    {
        $groups = self::stored_rows($order);
        if (!$groups) {
            return;
        }

        echo '<div class="ka-order-acf-fields" style="clear:both;padding-top:4px;">';
        foreach ($groups as $group) {
            echo '<h3 style="margin:1em 0 0.5em;">' . esc_html('' !== $group['heading'] ? $group['heading'] : __('Checkout fields', 'king-addons')) . '</h3>';
            foreach ($group['rows'] as $row) {
                echo '<p class="form-field form-field-wide"><strong>' . esc_html($row['label']) . ':</strong><br />' . nl2br(esc_html($row['value'])) . '</p>';
            }
        }
        echo '</div>';
    }

    /**
     * Thank-you page and My Account order view.
     *
     * @param mixed  $order   Order.
     * @param string $context "block" inside WooCommerce's Order Confirmation
     *                        blocks, anything else for the classic templates.
     *
     * @return void
     */
    public static function print_customer_values($order, $context = ''): void
    {
        $groups = self::stored_rows($order);
        if (!$groups) {
            return;
        }

        $block = 'block' === $context;

        foreach ($groups as $group) {
            $heading = '' !== $group['heading'] ? $group['heading'] : __('Additional information', 'king-addons');

            if ($block) {
                // Borrow the totals block's table classes so the section looks
                // like the order table right above it in any block theme.
                echo '<div class="ka-order-acf-fields ka-order-acf-fields--block alignwide">';
                echo '<h2 class="wp-block-heading ka-order-acf-fields__heading">' . esc_html($heading) . '</h2>';
                echo '<div class="wc-block-order-confirmation-totals">';
                echo '<table cellspacing="0" class="wc-block-order-confirmation-totals__table ka-order-acf-fields__table"><tbody>';
            } else {
                echo '<div class="ka-order-acf-fields">';
                echo '<h2 class="woocommerce-column__title">' . esc_html($heading) . '</h2>';
                echo '<table class="woocommerce-table shop_table ka-order-acf-fields__table"><tbody>';
            }

            foreach ($group['rows'] as $row) {
                echo '<tr><th scope="row">' . esc_html($row['label']) . '</th><td>' . nl2br(esc_html($row['value'])) . '</td></tr>';
            }

            echo '</tbody></table>';
            echo $block ? '</div></div>' : '</div>';
        }
    }

    /**
     * Order Confirmation block template: add the fields after the totals.
     *
     * WooCommerce renders the totals wrapper only for a viewer allowed to see
     * the order's details and returns an empty string otherwise, so the fields
     * follow the same rule as the rest of the page.
     *
     * @param string $content Rendered block.
     *
     * @return string
     */
    public static function append_to_confirmation($content): string
    {
        $content = (string) $content;
        if ('' === trim($content) || !function_exists('wc_get_order')) {
            return $content;
        }

        $order = wc_get_order(absint(get_query_var('order-received')));
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the order key is the credential here, as in WooCommerce.
        $key = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';

        if (!$order instanceof \WC_Order || !$order->key_is_valid($key)) {
            return $content;
        }

        ob_start();
        self::print_customer_values($order, 'block');

        return $content . (string) ob_get_clean();
    }

    /**
     * Styles for the order views, which have no Elementor widget to pull them in.
     *
     * @return void
     */
    public static function enqueue_order_view_styles(): void
    {
        if (function_exists('is_wc_endpoint_url') && (is_wc_endpoint_url('order-received') || is_wc_endpoint_url('view-order'))) {
            wp_enqueue_style(KING_ADDONS_ASSETS_UNIQUE_KEY . '-woo-acf-fields-style');
        }
    }

    /**
     * Order emails, to the store and to the customer.
     *
     * @param mixed $order         Order.
     * @param bool  $sent_to_admin Whether the email goes to the store.
     * @param bool  $plain_text    Whether this is the plain-text version.
     *
     * @return void
     */
    public static function print_email_values($order, $sent_to_admin = false, $plain_text = false): void
    {
        foreach (self::stored_rows($order) as $group) {
            $heading = '' !== $group['heading'] ? $group['heading'] : __('Additional information', 'king-addons');

            if ($plain_text) {
                // Plain text is not HTML: escaping would print &quot; and friends.
                echo "\n" . (function_exists('wc_strtoupper') ? wc_strtoupper($heading) : strtoupper($heading)) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                foreach ($group['rows'] as $row) {
                    echo wp_strip_all_tags($row['label']) . ': ' . str_replace("\n", "\n    ", wp_strip_all_tags($row['value'])) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
                continue;
            }

            echo '<h2>' . esc_html($heading) . '</h2>';
            foreach ($group['rows'] as $row) {
                echo '<p><strong>' . esc_html($row['label']) . ':</strong> ' . nl2br(esc_html($row['value'])) . '</p>';
            }
        }
    }

    /**
     * A stored value as text: choice labels instead of choice values, titles
     * instead of ids, dates in the field's display format.
     *
     * @param array<string,mixed> $field ACF field.
     * @param mixed               $value Unformatted value.
     *
     * @return string
     */
    private static function display_value(array $field, $value): string
    {
        $type = (string) ($field['type'] ?? '');

        if ('true_false' === $type) {
            if (null === $value || '' === $value) {
                return '';
            }

            return !empty($value) ? __('Yes', 'king-addons') : __('No', 'king-addons');
        }

        if (null === $value || '' === $value || [] === $value || false === $value) {
            return '';
        }

        switch ($type) {
            case 'select':
            case 'checkbox':
            case 'radio':
            case 'button_group':
                $choices = (array) ($field['choices'] ?? []);
                $labels = array_map(static function ($item) use ($choices): string {
                    return (string) ($choices[$item] ?? $item);
                }, array_filter((array) $value, static function ($item): bool {
                    return is_scalar($item) && '' !== (string) $item;
                }));
                return implode(', ', $labels);

            case 'date_picker':
            case 'date_time_picker':
            case 'time_picker':
                $format = (string) ($field['display_format'] ?? '');
                return '' !== $format && function_exists('acf_format_date') ? (string) acf_format_date($value, $format) : (string) $value;

            case 'link':
                if (is_array($value)) {
                    $url = (string) ($value['url'] ?? '');
                    $title = (string) ($value['title'] ?? '');
                    return '' !== $title && '' !== $url ? $title . ' (' . $url . ')' : ($title . $url);
                }
                return (string) $value;

            case 'post_object':
            case 'page_link':
            case 'relationship':
                return implode(', ', array_map(static function ($item): string {
                    return is_numeric($item) ? get_the_title((int) $item) : (string) $item;
                }, (array) $value));

            case 'taxonomy':
                return implode(', ', array_filter(array_map(static function ($item): string {
                    $term = is_numeric($item) ? get_term((int) $item) : null;
                    return $term instanceof \WP_Term ? $term->name : '';
                }, (array) $value)));

            case 'user':
                return implode(', ', array_filter(array_map(static function ($item): string {
                    $user = is_numeric($item) ? get_userdata((int) $item) : false;
                    return $user ? $user->display_name : '';
                }, (array) $value)));

            case 'google_map':
                return is_array($value) ? (string) ($value['address'] ?? '') : (string) $value;

            case 'group':
                $parts = [];
                foreach ((array) ($field['sub_fields'] ?? []) as $sub_field) {
                    if (!is_array($sub_field) || empty($sub_field['key'])) {
                        continue;
                    }
                    $text = self::display_value($sub_field, is_array($value) ? ($value[$sub_field['key']] ?? null) : null);
                    if ('' !== $text) {
                        $parts[] = ($sub_field['label'] ?: $sub_field['name']) . ': ' . $text;
                    }
                }
                return implode("\n", $parts);

            case 'wysiwyg':
                return trim(wp_strip_all_tags((string) $value));
        }

        return self::flatten($value);
    }

    /**
     * Any value as a line of text.
     *
     * @param mixed $value Value.
     *
     * @return string
     */
    private static function flatten($value): string
    {
        if (is_scalar($value)) {
            return trim((string) $value);
        }

        if (is_array($value)) {
            $parts = array_filter(array_map([self::class, 'flatten'], $value), static function (string $part): bool {
                return '' !== $part;
            });
            return implode(', ', $parts);
        }

        return '';
    }
}
