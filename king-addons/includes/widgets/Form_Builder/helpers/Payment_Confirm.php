<?php
/**
 * Confirms Form Builder payments after the visitor returns from Stripe or PayPal.
 *
 * Checkout URLs are not trusted on their own. Status is taken from the
 * provider API or a signed Stripe webhook.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pending submissions, webhooks, PayPal capture, Stripe session retrieve.
 */
class Form_Payment_Confirm
{
    public const META_STATUS = 'king_addons_payment_status';
    public const META_PROVIDER = 'king_addons_payment_provider';
    public const META_TXN = 'king_addons_payment_txn_id';
    public const META_AMOUNT = 'king_addons_payment_amount';
    public const META_CURRENCY = 'king_addons_payment_currency';
    public const META_TIME = 'king_addons_payment_time';
    public const META_EVENTS = 'king_addons_payment_events';
    public const META_PAYPAL_ORDER = 'king_addons_paypal_order_id';
    public const META_STRIPE_SESSION = 'king_addons_stripe_session_id';
    public const META_EXPECTED_AMOUNT = 'king_addons_payment_expected_amount';
    public const META_EXPECTED_CURRENCY = 'king_addons_payment_expected_currency';

    /**
     * Register REST and return-URL handlers.
     */
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('template_redirect', [$this, 'handle_return']);
    }

    /**
     * REST routes.
     *
     * @return void
     */
    public function register_routes(): void
    {
        register_rest_route('king-addons/v1', '/stripe-webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_stripe_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Public webhook URL shown on the settings screen.
     *
     * @return string
     */
    public static function stripe_webhook_url(): string
    {
        return rest_url('king-addons/v1/stripe-webhook');
    }

    /**
     * Create or reuse a submission and mark the payment pending.
     *
     * @param array<string,mixed> $config  Payment settings.
     * @param float               $amount  Amount to charge.
     * @param string              $provider stripe|paypal.
     * @param array<string,mixed> $request Posted form bits.
     *
     * @return int Submission ID.
     */
    public static function ensure_pending_submission(array $config, float $amount, string $provider, array $request): int
    {
        $submission_id = absint($request['submission_id'] ?? 0);
        if ($submission_id && 'king-addons-fb-sub' === get_post_type($submission_id)) {
            self::stamp_pending($submission_id, $config, $amount, $provider);
            return $submission_id;
        }

        $page_id = absint($request['form_page_id'] ?? 0);
        if ($page_id && class_exists('King_Addons\\Form_Builder_Security') && !Form_Builder_Security::is_valid_submission_page($page_id)) {
            $page_id = 0;
        }

        $created = 0;
        if (class_exists('King_Addons\\Create_Submission')) {
            $created = Create_Submission::insert_submission([
                'form_name' => (string) ($request['form_name'] ?? ''),
                'form_id' => (string) ($request['form_id'] ?? ''),
                'form_page' => (string) ($request['form_page'] ?? ''),
                'form_page_id' => $page_id,
                'form_content' => isset($request['form_content']) && is_array($request['form_content'])
                    ? $request['form_content']
                    : [],
            ]);
        }

        if ($created) {
            self::stamp_pending($created, $config, $amount, $provider);
        }

        return $created;
    }

    /**
     * Write pending payment meta.
     *
     * @param int                 $submission_id Submission.
     * @param array<string,mixed> $config        Settings.
     * @param float               $amount        Amount.
     * @param string              $provider      Provider.
     *
     * @return void
     */
    public static function stamp_pending(int $submission_id, array $config, float $amount, string $provider): void
    {
        $currency = strtoupper(substr((string) ($config['currency'] ?? 'USD'), 0, 3));
        update_post_meta($submission_id, self::META_STATUS, 'pending');
        update_post_meta($submission_id, self::META_PROVIDER, sanitize_key($provider));
        update_post_meta($submission_id, self::META_AMOUNT, $amount);
        update_post_meta($submission_id, self::META_CURRENCY, $currency);
        update_post_meta($submission_id, self::META_EXPECTED_AMOUNT, $amount);
        update_post_meta($submission_id, self::META_EXPECTED_CURRENCY, $currency);
        update_post_meta($submission_id, self::META_TIME, time());
    }

    /**
     * Apply a confirmed status. Never trust the browser for this.
     *
     * @param int    $submission_id Submission.
     * @param string $status        pending|paid|failed|cancelled|refunded.
     * @param string $txn_id        Provider transaction id.
     * @param string $event_id      Idempotency key (event or capture id).
     *
     * @return bool False when this event was already applied.
     */
    public static function apply_status(int $submission_id, string $status, string $txn_id = '', string $event_id = ''): bool
    {
        $allowed = ['pending', 'paid', 'failed', 'cancelled', 'refunded'];
        if (!in_array($status, $allowed, true) || !$submission_id) {
            return false;
        }

        if ('' !== $event_id) {
            $events = get_post_meta($submission_id, self::META_EVENTS, true);
            $events = is_array($events) ? $events : [];
            if (in_array($event_id, $events, true)) {
                return false;
            }
            $events[] = $event_id;
            update_post_meta($submission_id, self::META_EVENTS, $events);
        }

        update_post_meta($submission_id, self::META_STATUS, $status);
        if ('' !== $txn_id) {
            update_post_meta($submission_id, self::META_TXN, sanitize_text_field($txn_id));
        }
        update_post_meta($submission_id, self::META_TIME, time());

        return true;
    }

    /**
     * Find a submission by stored provider reference.
     *
     * @param string $meta_key Meta key.
     * @param string $value    Value.
     *
     * @return int
     */
    public static function find_by_meta(string $meta_key, string $value): int
    {
        $value = trim($value);
        if ('' === $value) {
            return 0;
        }

        $found = get_posts([
            'post_type' => 'king-addons-fb-sub',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => $meta_key,
            'meta_value' => $value,
        ]);

        return $found ? (int) $found[0] : 0;
    }

    /**
     * Stripe webhook.
     *
     * @param \WP_REST_Request $request Request.
     *
     * @return \WP_REST_Response
     */
    public function handle_stripe_webhook(\WP_REST_Request $request): \WP_REST_Response
    {
        $payload = $request->get_body();
        $signature = (string) $request->get_header('stripe-signature');
        $secret = (string) get_option('king_addons_stripe_webhook_secret', '');

        if ('' === $secret || !self::verify_stripe_signature($payload, $signature, $secret)) {
            return new \WP_REST_Response(['error' => 'invalid-signature'], 400);
        }

        $event = json_decode($payload, true);
        if (!is_array($event) || empty($event['type']) || empty($event['id'])) {
            return new \WP_REST_Response(['error' => 'invalid-payload'], 400);
        }

        $type = (string) $event['type'];
        $event_id = (string) $event['id'];
        $session = $event['data']['object'] ?? [];
        if (!is_array($session)) {
            return new \WP_REST_Response(['ok' => true], 200);
        }

        $submission_id = self::submission_from_stripe_session($session);
        if (!$submission_id) {
            return new \WP_REST_Response(['ok' => true], 200);
        }

        $txn = (string) ($session['payment_intent'] ?? $session['id'] ?? '');

        switch ($type) {
            case 'checkout.session.completed':
                if ('paid' === ($session['payment_status'] ?? '')) {
                    self::apply_status($submission_id, 'paid', $txn, $event_id);
                }
                break;
            case 'checkout.session.async_payment_succeeded':
                self::apply_status($submission_id, 'paid', $txn, $event_id);
                break;
            case 'checkout.session.async_payment_failed':
                self::apply_status($submission_id, 'failed', $txn, $event_id);
                break;
            case 'checkout.session.expired':
                self::apply_status($submission_id, 'cancelled', $txn, $event_id);
                break;
            default:
                break;
        }

        return new \WP_REST_Response(['ok' => true], 200);
    }

    /**
     * HMAC check for Stripe-Signature. Five minute skew.
     *
     * @param string $payload   Raw body.
     * @param string $header    Stripe-Signature header.
     * @param string $secret    Signing secret.
     *
     * @return bool
     */
    public static function verify_stripe_signature(string $payload, string $header, string $secret): bool
    {
        $parts = [];
        foreach (explode(',', $header) as $piece) {
            $piece = trim($piece);
            if (false === strpos($piece, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $piece, 2);
            $parts[$name][] = $value;
        }

        $timestamp = isset($parts['t'][0]) ? (int) $parts['t'][0] : 0;
        $signatures = $parts['v1'] ?? [];
        if ($timestamp < 1 || empty($signatures)) {
            return false;
        }

        if (abs(time() - $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Visitor came back from Stripe or PayPal.
     *
     * @return void
     */
    public function handle_return(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- provider return URL.
        $flag = isset($_GET['ka-payment']) ? sanitize_key(wp_unslash($_GET['ka-payment'])) : '';
        if ('' === $flag) {
            return;
        }

        if ('cancelled' === $flag) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
            if ('' !== $token) {
                $submission_id = self::find_by_meta(self::META_PAYPAL_ORDER, $token);
                if ($submission_id) {
                    self::apply_status($submission_id, 'cancelled', $token, 'cancel-' . $token);
                }
            }
            self::queue_return_notice(
                'warning',
                __('The payment was cancelled. No charge was made.', 'king-addons')
            );
            return;
        }

        if ('success' !== $flag) {
            return;
        }

        $confirmed = false;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $session_id = isset($_GET['session_id']) ? sanitize_text_field(wp_unslash($_GET['session_id'])) : '';
        if ('' !== $session_id) {
            $confirmed = self::confirm_stripe_session($session_id);
        } else {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
            if ('' !== $token) {
                $result = Form_Payments::capture_paypal_order($token);
                $confirmed = is_array($result);
            }
        }

        if ($confirmed) {
            self::queue_return_notice(
                'success',
                __('Payment received. Thank you.', 'king-addons')
            );
            return;
        }

        self::queue_return_notice(
            'info',
            __('We could not confirm this payment yet. If you were charged, your submission will update shortly.', 'king-addons')
        );
    }

    /**
     * Print a banner after Stripe/PayPal send the visitor back.
     *
     * @param string $tone    success|warning|info.
     * @param string $message Visitor-facing copy.
     *
     * @return void
     */
    private static function queue_return_notice(string $tone, string $message): void
    {
        $tone = in_array($tone, ['success', 'warning', 'info'], true) ? $tone : 'info';
        $message = (string) $message;
        if ('' === $message) {
            return;
        }

        add_action('wp_enqueue_scripts', static function () use ($tone): void {
            $css = '.king-addons-payment-return{max-width:720px;margin:16px auto;padding:12px 16px;font:15px/1.4 sans-serif;border:1px solid}'
                . '.king-addons-payment-return--success{background:#DCFCE7;border-color:#15803D;color:#14532D}'
                . '.king-addons-payment-return--warning{background:#FDE68A;border-color:#B45309;color:#92400E}'
                . '.king-addons-payment-return--info{background:#E5E7EB;border-color:#6B7280;color:#1F2937}';
            wp_register_style('king-addons-payment-return', false, [], defined('KING_ADDONS_VERSION') ? KING_ADDONS_VERSION : '1');
            wp_enqueue_style('king-addons-payment-return');
            wp_add_inline_style('king-addons-payment-return', $css);
        }, 5);

        $print = static function () use ($tone, $message): void {
            static $done = false;
            if ($done) {
                return;
            }
            $done = true;
            echo '<div class="king-addons-payment-return king-addons-payment-return--' . esc_attr($tone) . '" role="status">';
            echo esc_html($message);
            echo '</div>';
        };

        add_action('wp_body_open', $print, 5);
        add_action('wp_footer', $print, 5);
    }

    /**
     * Ask Stripe for the session so the thank-you page does not wait for the webhook.
     *
     * @param string $session_id Checkout session id.
     *
     * @return bool True when Stripe reported the session paid.
     */
    private static function confirm_stripe_session(string $session_id): bool
    {
        $secret = (string) get_option('king_addons_stripe_secret_key', '');
        if ('' === $secret) {
            return false;
        }

        $url = Form_Payments::public_endpoint('stripe_session', 'https://api.stripe.com/v1/checkout/sessions/' . rawurlencode($session_id));
        $response = wp_remote_get($url, [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $secret,
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $session = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($session)) {
            return false;
        }

        $submission_id = self::submission_from_stripe_session($session);
        if (!$submission_id) {
            return false;
        }

        $txn = (string) ($session['payment_intent'] ?? $session['id'] ?? '');
        $event_id = 'session-' . (string) ($session['id'] ?? $session_id);

        if ('paid' === ($session['payment_status'] ?? '')) {
            self::apply_status($submission_id, 'paid', $txn, $event_id);
            return true;
        }

        if ('unpaid' === ($session['payment_status'] ?? '')) {
            self::apply_status($submission_id, 'pending', $txn, $event_id . '-unpaid');
        }

        return false;
    }

    /**
     * Submission id from a Stripe session object.
     *
     * @param array<string,mixed> $session Session.
     *
     * @return int
     */
    private static function submission_from_stripe_session(array $session): int
    {
        $from_ref = absint($session['client_reference_id'] ?? 0);
        if ($from_ref && 'king-addons-fb-sub' === get_post_type($from_ref)) {
            return $from_ref;
        }

        $from_meta = absint($session['metadata']['ka_submission'] ?? 0);
        if ($from_meta && 'king-addons-fb-sub' === get_post_type($from_meta)) {
            return $from_meta;
        }

        $session_id = (string) ($session['id'] ?? '');

        return self::find_by_meta(self::META_STRIPE_SESSION, $session_id);
    }
}
