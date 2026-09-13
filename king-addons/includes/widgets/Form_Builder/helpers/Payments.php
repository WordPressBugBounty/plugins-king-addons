<?php
/**
 * Payments for Form Builder: Stripe Checkout and PayPal Orders.
 *
 * Both work the same way: the server creates the payment on the provider and
 * hands the browser a URL to send the visitor to. No card details ever reach
 * this site.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Creates a payment for a submitted form.
 */
class Form_Payments
{
    /**
     * Option prefix for the per-form configuration.
     */
    private const OPTION_PREFIX = 'king_addons_payment_';

    /**
     * Currencies with no minor unit, where Stripe expects whole numbers.
     *
     * @var array<int,string>
     */
    private const ZERO_DECIMAL = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

    /**
     * Providers offered in the panel.
     *
     * @return array<string,string>
     */
    public static function providers(): array
    {
        return [
            'none' => esc_html__('None', 'king-addons'),
            'stripe' => esc_html__('Stripe Checkout', 'king-addons'),
            'paypal' => esc_html__('PayPal', 'king-addons'),
        ];
    }

    /**
     * Register the endpoint.
     */
    public function __construct()
    {
        add_action('wp_ajax_king_addons_form_builder_payment', [self::class, 'handle']);
        add_action('wp_ajax_nopriv_king_addons_form_builder_payment', [self::class, 'handle']);
        new Form_Payment_Confirm();
    }

    /**
     * Store a form's payment settings.
     *
     * The amount is never taken from the request: either it is fixed here, or
     * it is worked out again on the server from the same formula the browser
     * used.
     *
     * @param string              $form_id  Form element id.
     * @param array<string,mixed> $settings Widget settings.
     * @param int                 $post_id  Page that owns the widget.
     *
     * @return void
     */
    public static function save_settings(string $form_id, array $settings, int $post_id = 0): void
    {
        $provider = (string) ($settings['payment_provider'] ?? 'none');
        if (!array_key_exists($provider, self::providers())) {
            $provider = 'none';
        }

        $formula = '';
        $amount_field = trim((string) ($settings['payment_amount_field'] ?? ''));

        // Find the calculation field the author nominated and keep its formula,
        // so the price can be recomputed rather than trusted.
        if ('' !== $amount_field && !empty($settings['form_fields']) && is_array($settings['form_fields'])) {
            foreach ($settings['form_fields'] as $field) {
                if (!is_array($field)) {
                    continue;
                }

                $field_key = class_exists('King_Addons\\Form_Builder')
                    ? Form_Builder::resolve_field_key($field)
                    : trim((string) ($field['field_id'] ?? ''));

                if ($field_key !== $amount_field) {
                    continue;
                }

                if ('calculation' === ($field['field_type'] ?? '')) {
                    $formula = (string) ($field['calc_formula'] ?? '');
                }

                break;
            }
        }

        update_option(self::option_key($form_id, $post_id), [
            'provider' => $provider,
            'currency' => strtoupper(substr((string) ($settings['payment_currency'] ?? 'USD'), 0, 3)),
            'fixed_amount' => (float) ($settings['payment_fixed_amount'] ?? 0),
            'amount_field' => $amount_field,
            'amount_formula' => $formula,
            'max_amount' => (float) ($settings['payment_max_amount'] ?? 0),
            'description' => (string) ($settings['payment_description'] ?? ''),
            'success_url' => (string) ($settings['payment_success_url'] ?? ''),
            'cancel_url' => (string) ($settings['payment_cancel_url'] ?? ''),
        ], false);
    }

    /**
     * Option name for a form's payment settings.
     *
     * Keyed by page and widget so two forms that happen to share an Elementor
     * id (common when pages are duplicated) do not overwrite each other.
     *
     * @param string $form_id Form element id.
     * @param int    $post_id Page that owns the widget.
     *
     * @return string
     */
    private static function option_key(string $form_id, int $post_id = 0): string
    {
        $form_id = sanitize_text_field($form_id);
        $post_id = $post_id > 0 ? $post_id : (int) get_the_ID();

        if ($post_id > 0) {
            return self::OPTION_PREFIX . $post_id . '_' . $form_id;
        }

        return self::OPTION_PREFIX . $form_id;
    }

    /**
     * A form's payment settings.
     *
     * @param string $form_id Form element id.
     * @param int    $post_id Page that owns the widget.
     *
     * @return array<string,mixed>
     */
    public static function get_settings(string $form_id, int $post_id = 0): array
    {
        $key = self::option_key($form_id, $post_id);
        $config = get_option($key, null);

        if (!is_array($config)) {
            $config = get_option(self::OPTION_PREFIX . $form_id, []);
        }

        return is_array($config) ? $config : [];
    }

    /**
     * Create the payment and return where to send the visitor.
     *
     * @return void
     */
    public static function handle(): void
    {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'king-addons-js')) {
            wp_send_json_error(['message' => 'invalid-nonce', 'status' => 'error']);
        }

        if (class_exists('King_Addons\\Form_Builder_Security')) {
            Form_Builder_Security::guard_spam();
        }

        $form_id = isset($_POST['king_addons_form_id'])
            ? sanitize_text_field(wp_unslash($_POST['king_addons_form_id']))
            : '';
        if ('' === $form_id && isset($_POST['form_id'])) {
            $form_id = sanitize_text_field(wp_unslash($_POST['form_id']));
        }

        $post_id = absint($_POST['form_page_id'] ?? 0);
        $config = '' === $form_id ? [] : self::get_settings($form_id, $post_id);
        $provider = (string) ($config['provider'] ?? 'none');

        if ('none' === $provider) {
            wp_send_json_success([
                'action' => 'king_addons_form_builder_payment',
                'status' => 'success',
                'message' => esc_html__('No payment is configured for this form.', 'king-addons'),
            ]);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked above.
        $raw = isset($_POST['form_content']) && is_array($_POST['form_content']) ? wp_unslash($_POST['form_content']) : [];
        $amount = self::resolve_amount($config, $raw);

        // Amount first: an over-max total should be refused even when Stripe
        // or PayPal keys are empty, otherwise leftover QA (and a mis-set
        // ceiling) only ever sees "not configured".
        if (null === $amount || $amount <= 0) {
            $max = (float) ($config['max_amount'] ?? 0);
            $computed = self::compute_amount($config, $raw);
            $over_max = $max > 0 && null !== $computed && $computed > $max;
            wp_send_json_error([
                'action' => 'king_addons_form_builder_payment',
                'status' => 'error',
                'message' => $over_max
                    ? esc_html__('This amount is over the maximum allowed for this form.', 'king-addons')
                    : esc_html__('The amount to charge could not be worked out.', 'king-addons'),
            ]);
        }

        if ('stripe' === $provider && '' === (string) get_option('king_addons_stripe_secret_key', '')) {
            wp_send_json_error([
                'action' => 'king_addons_form_builder_payment',
                'status' => 'error',
                'message' => esc_html__('Stripe is not configured. Add a secret key in King Addons → Settings.', 'king-addons'),
            ]);
        }

        if ('paypal' === $provider && ('' === (string) get_option('king_addons_paypal_client_id', '') || '' === (string) get_option('king_addons_paypal_secret', ''))) {
            wp_send_json_error([
                'action' => 'king_addons_form_builder_payment',
                'status' => 'error',
                'message' => esc_html__('PayPal is not configured. Add a client ID and secret in King Addons → Settings.', 'king-addons'),
            ]);
        }

        $submission_id = 0;
        if (class_exists('King_Addons\\Form_Payment_Confirm')) {
            $submission_id = Form_Payment_Confirm::ensure_pending_submission($config, $amount, $provider, [
                'submission_id' => absint($_POST['submission_id'] ?? 0),
                'form_id' => $form_id !== '' ? $form_id : sanitize_text_field(wp_unslash($_POST['form_id'] ?? '')),
                'form_name' => sanitize_text_field(wp_unslash($_POST['form_name'] ?? '')),
                'form_page' => sanitize_text_field(wp_unslash($_POST['form_page'] ?? '')),
                'form_page_id' => absint($_POST['form_page_id'] ?? 0),
                'form_content' => $raw,
            ]);
        }

        $result = 'stripe' === $provider
            ? self::create_stripe_session($config, $amount, $submission_id)
            : self::create_paypal_order($config, $amount, $submission_id);

        // Both providers answer with ['url' => …] when they accepted the
        // payment, and a plain string naming the problem when they did not.
        if (!is_array($result) || empty($result['url'])) {
            wp_send_json_error([
                'action' => 'king_addons_form_builder_payment',
                'status' => 'error',
                'message' => esc_html__('The payment could not be started.', 'king-addons'),
                'reason' => is_string($result) ? $result : 'unknown',
            ]);
        }

        wp_send_json_success([
            'action' => 'king_addons_form_builder_payment',
            'status' => 'success',
            'message' => esc_html__('Redirecting to payment.', 'king-addons'),
            'redirect' => (string) $result['url'],
            'amount' => $amount,
        ]);
    }

    /**
     * Work out what to charge.
     *
     * @param array<string,mixed> $config Stored settings.
     * @param array<mixed>        $raw    Submitted form content.
     *
     * @return float|null
     */
    private static function resolve_amount(array $config, array $raw): ?float
    {
        $amount = self::compute_amount($config, $raw);
        if (null === $amount) {
            return null;
        }

        $max = (float) ($config['max_amount'] ?? 0);
        if ($max > 0 && $amount > $max) {
            // A ceiling the author set: better to refuse than to charge a
            // number that came out of a formula fed by the visitor.
            return null;
        }

        return round((float) $amount, 2);
    }

    /**
     * Amount before the max-amount ceiling is applied.
     *
     * @param array<string,mixed> $config Stored settings.
     * @param array<mixed>        $raw    Submitted form content.
     *
     * @return float|null
     */
    private static function compute_amount(array $config, array $raw): ?float
    {
        $fixed = (float) ($config['fixed_amount'] ?? 0);
        $formula = (string) ($config['amount_formula'] ?? '');

        if ('' === $formula) {
            return $fixed > 0 ? $fixed : null;
        }

        $values = [];

        foreach ($raw as $key => $entry) {
            if (!is_array($entry) || !isset($entry[1]) || is_array($entry[1])) {
                continue;
            }

            $id = str_replace('form_field-', '', sanitize_text_field((string) $key));
            $values[$id] = sanitize_text_field((string) $entry[1]);
        }

        $amount = class_exists('King_Addons\\Form_Formula')
            ? Form_Formula::evaluate($formula, $values)
            : null;

        return null === $amount ? null : (float) $amount;
    }

    /**
     * Turn an amount into the units the provider expects.
     *
     * @param float  $amount   Amount.
     * @param string $currency Currency code.
     *
     * @return int
     */
    private static function to_minor_units(float $amount, string $currency): int
    {
        if (in_array(strtoupper($currency), self::ZERO_DECIMAL, true)) {
            return (int) round($amount);
        }

        return (int) round($amount * 100);
    }

    /**
     * A URL the visitor is sent to after paying, always on this site.
     *
     * @param string $url      Configured URL.
     * @param string $fallback Fallback path.
     *
     * @return string
     */
    private static function return_url(string $url, string $fallback): string
    {
        $url = trim($url);

        if ('' === $url) {
            return home_url($fallback);
        }

        // Keep the round trip on this site: an off-site return URL would let a
        // form send people anywhere after payment.
        $host = wp_parse_url($url, PHP_URL_HOST);
        $home = wp_parse_url(home_url(), PHP_URL_HOST);

        return ($host && $host === $home) ? $url : home_url($fallback);
    }

    /**
     * Public wrapper so return-URL handlers can reuse the filterable endpoints.
     *
     * @param string $provider Provider key.
     * @param string $url      Default URL.
     *
     * @return string
     */
    public static function public_endpoint(string $provider, string $url): string
    {
        return self::endpoint($provider, $url);
    }

    /**
     * The endpoint a provider is called on. Filterable for proxies and tests.
     *
     * @param string $provider Provider key.
     * @param string $url      Default URL.
     *
     * @return string
     */
    private static function endpoint(string $provider, string $url): string
    {
        /**
         * Filters the endpoint a Form Builder payment provider is called on.
         *
         * @param string $url      Endpoint URL.
         * @param string $provider Provider key.
         */
        return (string) apply_filters('king_addons/form_builder/payment_endpoint', $url, $provider);
    }

    /**
     * Create a Stripe Checkout session.
     *
     * @param array<string,mixed> $config         Settings.
     * @param float               $amount         Amount.
     * @param int                 $submission_id  Linked submission.
     *
     * @return array{url:string}|string The URL to send the visitor to, or a reason.
     */
    private static function create_stripe_session(array $config, float $amount, int $submission_id = 0)
    {
        $secret = (string) get_option('king_addons_stripe_secret_key', '');
        if ('' === $secret) {
            return 'no-stripe-key';
        }

        $currency = strtolower((string) ($config['currency'] ?? 'usd'));
        $description = (string) ($config['description'] ?? '');
        if ('' === $description) {
            $description = esc_html__('Form submission', 'king-addons');
        }

        $success = self::return_url((string) ($config['success_url'] ?? ''), '/?ka-payment=success');
        $success .= (false === strpos($success, '?') ? '?' : '&') . 'session_id={CHECKOUT_SESSION_ID}';

        $body = [
            'mode' => 'payment',
            'success_url' => $success,
            'cancel_url' => self::return_url((string) ($config['cancel_url'] ?? ''), '/?ka-payment=cancelled'),
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][unit_amount]' => self::to_minor_units($amount, $currency),
            'line_items[0][price_data][product_data][name]' => $description,
        ];

        if ($submission_id) {
            $body['client_reference_id'] = (string) $submission_id;
            $body['metadata[ka_submission]'] = (string) $submission_id;
        }

        $response = wp_remote_post(self::endpoint('stripe', 'https://api.stripe.com/v1/checkout/sessions'), [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $secret,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            return 'request-failed';
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);

        if (!is_array($data) || empty($data['url'])) {
            return 'http-' . (int) wp_remote_retrieve_response_code($response);
        }

        if ($submission_id && !empty($data['id'])) {
            update_post_meta($submission_id, Form_Payment_Confirm::META_STRIPE_SESSION, sanitize_text_field((string) $data['id']));
        }

        return ['url' => (string) $data['url']];
    }

    /**
     * Create a PayPal order and return its approval link.
     *
     * @param array<string,mixed> $config        Settings.
     * @param float               $amount        Amount.
     * @param int                 $submission_id Linked submission.
     *
     * @return array{url:string}|string The URL to send the visitor to, or a reason.
     */
    private static function create_paypal_order(array $config, float $amount, int $submission_id = 0)
    {
        $client_id = (string) get_option('king_addons_paypal_client_id', '');
        $secret = (string) get_option('king_addons_paypal_secret', '');

        if ('' === $client_id || '' === $secret) {
            return 'no-paypal-keys';
        }

        $base = 'live' === get_option('king_addons_paypal_environment', 'sandbox')
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $token = self::paypal_token($base, $client_id, $secret);
        if (!is_string($token) || '' === $token) {
            return 'paypal-auth-failed';
        }

        $currency = strtoupper((string) ($config['currency'] ?? 'USD'));
        $unit = [
            'amount' => [
                'currency_code' => $currency,
                'value' => number_format($amount, 2, '.', ''),
            ],
            'description' => (string) ($config['description'] ?? ''),
        ];
        if ($submission_id) {
            $unit['custom_id'] = (string) $submission_id;
            $unit['invoice_id'] = 'ka-' . $submission_id . '-' . time();
        }

        $response = wp_remote_post(self::endpoint('paypal_order', $base . '/v2/checkout/orders'), [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'intent' => 'CAPTURE',
                'purchase_units' => [$unit],
                'application_context' => [
                    'return_url' => self::return_url((string) ($config['success_url'] ?? ''), '/?ka-payment=success'),
                    'cancel_url' => self::return_url((string) ($config['cancel_url'] ?? ''), '/?ka-payment=cancelled'),
                ],
            ]),
        ]);

        if (is_wp_error($response)) {
            return 'request-failed';
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);

        if (!is_array($data) || empty($data['links'])) {
            return 'http-' . (int) wp_remote_retrieve_response_code($response);
        }

        if ($submission_id && !empty($data['id'])) {
            update_post_meta($submission_id, Form_Payment_Confirm::META_PAYPAL_ORDER, sanitize_text_field((string) $data['id']));
        }

        foreach ($data['links'] as $link) {
            if (is_array($link) && 'approve' === ($link['rel'] ?? '') && !empty($link['href'])) {
                return ['url' => (string) $link['href']];
            }
        }

        return 'no-approval-link';
    }

    /**
     * Capture a PayPal order after the buyer returns. Safe to call twice.
     *
     * @param string $order_id PayPal order id from the return URL token.
     *
     * @return array<string,mixed>|string
     */
    public static function capture_paypal_order(string $order_id)
    {
        $order_id = sanitize_text_field($order_id);
        if ('' === $order_id) {
            return 'missing-order';
        }

        $submission_id = class_exists('King_Addons\\Form_Payment_Confirm')
            ? Form_Payment_Confirm::find_by_meta(Form_Payment_Confirm::META_PAYPAL_ORDER, $order_id)
            : 0;

        $client_id = (string) get_option('king_addons_paypal_client_id', '');
        $secret = (string) get_option('king_addons_paypal_secret', '');
        if ('' === $client_id || '' === $secret) {
            return 'no-paypal-keys';
        }

        $base = 'live' === get_option('king_addons_paypal_environment', 'sandbox')
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $token = self::paypal_token($base, $client_id, $secret);
        if (!is_string($token) || '' === $token) {
            return 'paypal-auth-failed';
        }

        $existing = self::paypal_get_order($base, $token, $order_id);
        if (is_array($existing) && 'COMPLETED' === ($existing['status'] ?? '')) {
            $txn = self::paypal_capture_id($existing) ?: $order_id;
            if ($submission_id) {
                Form_Payment_Confirm::apply_status($submission_id, 'paid', $txn, 'paypal-completed-' . $order_id);
            }
            return $existing;
        }

        $response = wp_remote_post(self::endpoint('paypal_capture', $base . '/v2/checkout/orders/' . rawurlencode($order_id) . '/capture'), [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
            ],
            'body' => '{}',
        ]);

        if (is_wp_error($response)) {
            return 'request-failed';
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        $code = (int) wp_remote_retrieve_response_code($response);

        if (!is_array($data)) {
            return 'http-' . $code;
        }

        $name = (string) ($data['name'] ?? '');
        if ('INSTRUMENT_DECLINED' === $name) {
            if ($submission_id) {
                Form_Payment_Confirm::apply_status($submission_id, 'failed', $order_id, 'paypal-declined-' . $order_id);
            }
            $approve = '';
            foreach (($data['links'] ?? []) as $link) {
                if (is_array($link) && 'approve' === ($link['rel'] ?? '') && !empty($link['href'])) {
                    $approve = (string) $link['href'];
                    break;
                }
            }
            if ('' !== $approve && !headers_sent()) {
                wp_safe_redirect($approve);
                exit;
            }
            return $data;
        }

        if ('COMPLETED' !== ($data['status'] ?? '')) {
            if ($submission_id && in_array($data['status'] ?? '', ['VOIDED', 'DECLINED'], true)) {
                Form_Payment_Confirm::apply_status($submission_id, 'failed', $order_id, 'paypal-' . strtolower((string) $data['status']) . '-' . $order_id);
            }
            return $data;
        }

        if ($submission_id && !self::paypal_amount_matches($submission_id, $data)) {
            Form_Payment_Confirm::apply_status($submission_id, 'failed', $order_id, 'paypal-mismatch-' . $order_id);
            return 'amount-mismatch';
        }

        $txn = self::paypal_capture_id($data) ?: $order_id;
        if ($submission_id) {
            Form_Payment_Confirm::apply_status($submission_id, 'paid', $txn, 'paypal-capture-' . $order_id);
        }

        return $data;
    }

    /**
     * GET a PayPal order.
     *
     * @param string $base     API base.
     * @param string $token    Access token.
     * @param string $order_id Order id.
     *
     * @return array<string,mixed>|null
     */
    private static function paypal_get_order(string $base, string $token, string $order_id): ?array
    {
        $response = wp_remote_get(self::endpoint('paypal_order_get', $base . '/v2/checkout/orders/' . rawurlencode($order_id)), [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);

        return is_array($data) ? $data : null;
    }

    /**
     * Capture id from a PayPal order/capture payload.
     *
     * @param array<string,mixed> $data Payload.
     *
     * @return string
     */
    private static function paypal_capture_id(array $data): string
    {
        $captures = $data['purchase_units'][0]['payments']['captures'][0]['id'] ?? '';

        return is_string($captures) ? $captures : '';
    }

    /**
     * Captured amount and currency must match what the form stored.
     *
     * @param int                 $submission_id Submission.
     * @param array<string,mixed> $data          PayPal payload.
     *
     * @return bool
     */
    private static function paypal_amount_matches(int $submission_id, array $data): bool
    {
        $expected_amount = (float) get_post_meta($submission_id, Form_Payment_Confirm::META_EXPECTED_AMOUNT, true);
        $expected_currency = strtoupper((string) get_post_meta($submission_id, Form_Payment_Confirm::META_EXPECTED_CURRENCY, true));
        $amount = $data['purchase_units'][0]['payments']['captures'][0]['amount']['value']
            ?? $data['purchase_units'][0]['amount']['value']
            ?? '';
        $currency = $data['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code']
            ?? $data['purchase_units'][0]['amount']['currency_code']
            ?? '';

        if ('' === $amount || '' === $currency) {
            return false;
        }

        return abs((float) $amount - $expected_amount) < 0.009 && strtoupper((string) $currency) === $expected_currency;
    }

    /**
     * Exchange the PayPal credentials for an access token.
     *
     * @param string $base      API base URL.
     * @param string $client_id Client id.
     * @param string $secret    Secret.
     *
     * @return string|null
     */
    private static function paypal_token(string $base, string $client_id, string $secret): ?string
    {
        $response = wp_remote_post(self::endpoint('paypal_token', $base . '/v1/oauth2/token'), [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $secret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => ['grant_type' => 'client_credentials'],
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);

        return (is_array($data) && !empty($data['access_token'])) ? (string) $data['access_token'] : null;
    }
}
