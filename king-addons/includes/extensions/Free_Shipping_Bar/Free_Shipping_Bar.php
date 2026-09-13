<?php
/**
 * Free Shipping Progress Bar.
 *
 * Shows how much a customer still has to add to qualify for free shipping.
 * Reads the threshold from the store's own WooCommerce shipping zones, so it
 * keeps working when the merchant changes the rule.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Free shipping progress bar module.
 */
final class Free_Shipping_Bar
{
    /**
     * Settings option key.
     */
    public const OPTION_KEY = 'king_addons_free_shipping_bar_settings';

    /**
     * Singleton instance.
     *
     * @var Free_Shipping_Bar|null
     */
    private static ?Free_Shipping_Bar $instance = null;

    /**
     * Cached settings.
     *
     * @var array<string, mixed>
     */
    private array $settings = [];

    /**
     * Guards against printing the bar twice on one request.
     *
     * @var bool
     */
    private bool $printed = false;

    /**
     * Singleton accessor.
     *
     * @return Free_Shipping_Bar
     */
    public static function instance(): Free_Shipping_Bar
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Hooks.
     */
    public function __construct()
    {
        $this->settings = $this->get_settings();

        add_action('admin_menu', [$this, 'register_admin_menu'], 15);
        add_action('admin_post_king_addons_fsb_save', [$this, 'handle_save']);

        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);

        // Classic (shortcode) cart and checkout.
        add_action('woocommerce_before_cart', [$this, 'render_for_cart'], 5);
        add_action('woocommerce_before_checkout_form', [$this, 'render_for_checkout'], 5);

        // Block cart and checkout are the default for new stores. They fire
        // neither the classic hooks nor the_content, so hook the blocks directly.
        add_filter('render_block_woocommerce/cart', [$this, 'prepend_to_cart_block'], 10, 1);
        add_filter('render_block_woocommerce/checkout', [$this, 'prepend_to_checkout_block'], 10, 1);

        // Block-theme mini-cart never fires woocommerce_before_mini_cart.
        add_filter('render_block_woocommerce/filled-mini-cart-contents-block', [$this, 'prepend_to_mini_cart_block'], 10, 1);

        // Woo Builder cart/checkout templates skip the classic Woo hooks.
        add_action('king_addons/woo_builder/before_render', [$this, 'render_for_woo_builder'], 5, 2);

        // Mini-cart placement (also refreshed by WooCommerce fragments).
        add_action('woocommerce_before_mini_cart', [$this, 'render_for_mini_cart'], 5);

        // Keep the bar current after AJAX cart updates.
        add_filter('woocommerce_add_to_cart_fragments', [$this, 'add_cart_fragment']);
        add_action('wp_ajax_king_addons_fsb_markup', [$this, 'ajax_markup']);
        add_action('wp_ajax_nopriv_king_addons_fsb_markup', [$this, 'ajax_markup']);
    }

    /**
     * Default settings.
     *
     * @return array<string, mixed>
     */
    public function get_default_settings(): array
    {
        return [
            'enabled' => false,
            'threshold_source' => 'auto',
            'manual_amount' => '',
            'show_on_cart' => 'yes',
            'show_on_checkout' => 'yes',
            'show_in_mini_cart' => 'yes',
            'text_progress' => __('Add {remaining} more and your shipping is on us.', 'king-addons'),
            'text_success' => __('Free shipping unlocked.', 'king-addons'),
            'bar_height' => 8,
            'bar_color' => '#2f9e5f',
            'bar_track_color' => '#e6e6e6',
            'text_color' => '#1f2933',
            'hide_when_reached' => '',
        ];
    }

    /**
     * Settings with defaults applied.
     *
     * @return array<string, mixed>
     */
    public function get_settings(): array
    {
        $saved = get_option(self::OPTION_KEY, []);
        if (!is_array($saved)) {
            $saved = [];
        }

        return array_merge($this->get_default_settings(), $saved);
    }

    /**
     * Whether the module should render at all.
     *
     * @return bool
     */
    private function is_active(): bool
    {
        if (empty($this->settings['enabled'])) {
            return false;
        }

        // admin-ajax.php reports is_admin() === true, so treating every admin
        // request as "off" dropped the bar from cart-table AJAX fragments and
        // from woocommerce_add_to_cart_fragments.
        $front_or_ajax = !is_admin() || wp_doing_ajax();

        return class_exists('WooCommerce') && $front_or_ajax;
    }

    /**
     * Free shipping threshold for the current customer, or null when the store
     * has no amount-based free shipping rule.
     *
     * @return float|null
     */
    public function get_threshold(): ?float
    {
        if ('manual' === ($this->settings['threshold_source'] ?? 'auto')) {
            $amount = (float) ($this->settings['manual_amount'] ?? 0);

            return $amount > 0 ? $amount : null;
        }

        if (!function_exists('WC') || !class_exists('WC_Shipping_Zones')) {
            return null;
        }

        $package = ['destination' => $this->get_package_destination()];
        if (WC()->cart && method_exists(WC()->cart, 'get_shipping_packages')) {
            $packages = WC()->cart->get_shipping_packages();
            if (!empty($packages)) {
                $package = reset($packages);
            }
        }

        $zone = \WC_Shipping_Zones::get_zone_matching_package($package);
        if (!$zone) {
            return null;
        }

        $amounts = [];
        foreach ($zone->get_shipping_methods(true) as $method) {
            if ('free_shipping' !== $method->id) {
                continue;
            }

            $requires = $method->get_option('requires');
            if (!in_array($requires, ['min_amount', 'either', 'both'], true)) {
                continue;
            }

            $amount = (float) $method->get_option('min_amount');
            if ($amount > 0) {
                $amounts[] = $amount;
            }
        }

        if (empty($amounts)) {
            return null;
        }

        // A zone can carry several free shipping methods; the cheapest one is
        // the one the customer will actually hit first.
        return (float) min($amounts);
    }

    /**
     * A destination WooCommerce can match a shipping zone against.
     *
     * WC_Shipping_Zones::get_zone_matching_package() reads country, state and
     * postcode straight out of the array with no checks of its own, so handing
     * it an empty destination - which is what happens in wp-admin, where there
     * is no cart - printed six "Undefined array key" warnings over the settings
     * screen.
     *
     * @return array<string,string>
     */
    private function get_package_destination(): array
    {
        $destination = [
            'country' => '',
            'state' => '',
            'postcode' => '',
            'city' => '',
            'address' => '',
            'address_2' => '',
        ];

        $customer = function_exists('WC') ? WC()->customer : null;

        if ($customer) {
            $destination['country'] = (string) $customer->get_shipping_country();
            $destination['state'] = (string) $customer->get_shipping_state();
            $destination['postcode'] = (string) $customer->get_shipping_postcode();
            $destination['city'] = (string) $customer->get_shipping_city();
        }

        // No customer session, which is the normal case in wp-admin: fall back
        // to where the store itself is, so the detected threshold is the one
        // most customers will see.
        if ('' === $destination['country'] && function_exists('wc_get_base_location')) {
            $base = wc_get_base_location();
            $destination['country'] = (string) ($base['country'] ?? '');
            $destination['state'] = (string) ($base['state'] ?? '');
        }

        return $destination;
    }

    /**
     * Cart total the threshold is measured against.
     *
     * @return float
     */
    private function get_cart_total(): float
    {
        if (!function_exists('WC') || !WC()->cart) {
            return 0.0;
        }

        // Subtotal after discounts, excluding shipping - this is what
        // WooCommerce's own free shipping method compares.
        $total = (float) WC()->cart->get_displayed_subtotal();

        if (WC()->cart->display_prices_including_tax()) {
            $total -= (float) WC()->cart->get_discount_tax();
        }

        $total -= (float) WC()->cart->get_discount_total();

        return max(0.0, $total);
    }

    /**
     * Build the bar markup, or an empty string when there is nothing to show.
     *
     * @param bool $mark_printed Whether this render should block a second cart/checkout copy.
     *
     * @return string
     */
    public function get_markup(bool $mark_printed = true): string
    {
        if (!$this->is_active()) {
            return '';
        }

        $threshold = $this->get_threshold();
        if (null === $threshold || $threshold <= 0) {
            return '';
        }

        $total = $this->get_cart_total();
        $reached = $total >= $threshold;

        if ($reached && !empty($this->settings['hide_when_reached'])) {
            return '';
        }

        $remaining = max(0.0, $threshold - $total);
        $percent = $threshold > 0 ? min(100, ($total / $threshold) * 100) : 0;

        $text = $reached
            ? (string) $this->settings['text_success']
            : str_replace(
                ['{remaining}', '{total}', '{threshold}'],
                [wc_price($remaining), wc_price($total), wc_price($threshold)],
                (string) $this->settings['text_progress']
            );

        $style = sprintf(
            '--ka-fsb-height:%dpx;--ka-fsb-color:%s;--ka-fsb-track:%s;--ka-fsb-text:%s;',
            (int) $this->settings['bar_height'],
            sanitize_hex_color((string) $this->settings['bar_color']) ?: '#2f9e5f',
            sanitize_hex_color((string) $this->settings['bar_track_color']) ?: '#e6e6e6',
            sanitize_hex_color((string) $this->settings['text_color']) ?: '#1f2933'
        );

        ob_start();
        ?>
        <div class="king-addons-fsb<?php echo $reached ? ' is-reached' : ''; ?>" style="<?php echo esc_attr($style); ?>">
            <p class="king-addons-fsb__text">
                <?php
                // wc_price() returns markup; the surrounding text is translator-supplied.
                echo wp_kses_post($text);
                ?>
            </p>
            <div class="king-addons-fsb__track" role="progressbar"
                aria-valuemin="0" aria-valuemax="100"
                aria-valuenow="<?php echo esc_attr((string) round($percent)); ?>">
                <span class="king-addons-fsb__fill" style="width:<?php echo esc_attr((string) round($percent, 2)); ?>%"></span>
            </div>
        </div>
        <?php

        if ($mark_printed) {
            $this->printed = true;
        }

        return (string) ob_get_clean();
    }

    /**
     * Prepend the bar to the Cart block.
     *
     * @param string $block_content Rendered block markup.
     *
     * @return string
     */
    public function prepend_to_cart_block(string $block_content): string
    {
        if (!$this->is_active() || $this->printed || empty($this->settings['show_on_cart'])) {
            return $block_content;
        }

        return $this->get_markup() . $block_content;
    }

    /**
     * Prepend the bar to the Checkout block.
     *
     * @param string $block_content Rendered block markup.
     *
     * @return string
     */
    public function prepend_to_checkout_block(string $block_content): string
    {
        if (!$this->is_active() || $this->printed || empty($this->settings['show_on_checkout'])) {
            return $block_content;
        }

        return $this->get_markup() . $block_content;
    }

    /**
     * Prepend the bar to the filled Mini-Cart block (block themes).
     *
     * @param string $block_content Rendered block markup.
     *
     * @return string
     */
    public function prepend_to_mini_cart_block(string $block_content): string
    {
        if (!$this->is_active() || empty($this->settings['show_in_mini_cart'])) {
            return $block_content;
        }

        $markup = $this->get_markup(false);
        if ('' === $markup) {
            return $block_content;
        }

        $injected = preg_replace('/(<div\b[^>]*>)/', '$1' . $markup, $block_content, 1);

        return is_string($injected) ? $injected : $markup . $block_content;
    }

    /**
     * Woo Builder cart/checkout placement.
     *
     * @param string $context     Template context.
     * @param mixed  $template_id Template id.
     *
     * @return void
     */
    public function render_for_woo_builder(string $context, $template_id): void
    {
        unset($template_id);

        if ('cart' === $context) {
            $this->render_for_cart();
            return;
        }

        if ('checkout' === $context) {
            $this->render_for_checkout();
        }
    }

    /**
     * Fresh markup for JS refreshers (block mini-cart quantity changes).
     *
     * @return void
     */
    public function ajax_markup(): void
    {
        check_ajax_referer('king_addons_fsb', 'nonce');

        $this->printed = false;

        wp_send_json_success(['html' => $this->get_markup(false)]);
    }

    /**
     * Cart page placement.
     *
     * @return void
     */
    public function render_for_cart(): void
    {
        // Cart Table / coupon AJAX re-renders cart/cart.php. That template
        // fires woocommerce_before_cart, which would nest a second bar inside
        // the table fragment and leave the page-level bar stale.
        if (wp_doing_ajax() || $this->printed || empty($this->settings['show_on_cart'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->get_markup();
    }

    /**
     * Checkout placement.
     *
     * @return void
     */
    public function render_for_checkout(): void
    {
        if (wp_doing_ajax() || $this->printed || empty($this->settings['show_on_checkout'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->get_markup();
    }

    /**
     * Mini-cart placement.
     *
     * @return void
     */
    public function render_for_mini_cart(): void
    {
        if (empty($this->settings['show_in_mini_cart'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->get_markup();
    }

    /**
     * Refresh the bar through WooCommerce's own fragment mechanism.
     *
     * @param array<string, string> $fragments Cart fragments.
     *
     * @return array<string, string>
     */
    public function add_cart_fragment(array $fragments): array
    {
        if (!$this->is_active() || empty($this->settings['show_in_mini_cart'])) {
            return $fragments;
        }

        $markup = $this->get_markup();
        if ('' !== $markup) {
            $fragments['div.king-addons-fsb'] = $markup;
        }

        return $fragments;
    }

    /**
     * Front-end assets.
     *
     * @return void
     */
    public function maybe_enqueue_assets(): void
    {
        if (!$this->is_active()) {
            return;
        }

        $dir = KING_ADDONS_PATH . 'includes/extensions/Free_Shipping_Bar/assets/';
        $css_ver = file_exists($dir . 'style.css') ? (string) filemtime($dir . 'style.css') : KING_ADDONS_VERSION;
        $js_ver = file_exists($dir . 'script.js') ? (string) filemtime($dir . 'script.js') : KING_ADDONS_VERSION;

        wp_enqueue_style(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-free-shipping-bar',
            KING_ADDONS_URL . 'includes/extensions/Free_Shipping_Bar/assets/style.css',
            [],
            $css_ver
        );

        wp_enqueue_script(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-free-shipping-bar',
            KING_ADDONS_URL . 'includes/extensions/Free_Shipping_Bar/assets/script.js',
            [],
            $js_ver,
            true
        );

        wp_localize_script(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-free-shipping-bar',
            'kingAddonsFsb',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('king_addons_fsb'),
            ]
        );
    }

    /**
     * Admin submenu.
     *
     * @return void
     */
    public function register_admin_menu(): void
    {
        add_submenu_page(
            king_addons_woo_admin_parent_slug(),
            esc_html__('Free Shipping Bar', 'king-addons'),
            esc_html__('Free Shipping Bar', 'king-addons'),
            'manage_options',
            'king-addons-free-shipping-bar',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Save handler.
     *
     * @return void
     */
    public function handle_save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to do this.', 'king-addons'));
        }

        check_admin_referer('king_addons_fsb_save');

        $raw = isset($_POST['ka_fsb']) && is_array($_POST['ka_fsb'])
            ? wp_unslash($_POST['ka_fsb']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : [];

        $defaults = $this->get_default_settings();

        $clean = [
            'enabled' => !empty($raw['enabled']),
            'threshold_source' => in_array(($raw['threshold_source'] ?? 'auto'), ['auto', 'manual'], true)
                ? $raw['threshold_source'] : 'auto',
            'manual_amount' => is_numeric($raw['manual_amount'] ?? '') ? (float) $raw['manual_amount'] : '',
            'show_on_cart' => !empty($raw['show_on_cart']) ? 'yes' : '',
            'show_on_checkout' => !empty($raw['show_on_checkout']) ? 'yes' : '',
            'show_in_mini_cart' => !empty($raw['show_in_mini_cart']) ? 'yes' : '',
            'text_progress' => sanitize_text_field((string) ($raw['text_progress'] ?? $defaults['text_progress'])),
            'text_success' => sanitize_text_field((string) ($raw['text_success'] ?? $defaults['text_success'])),
            'bar_height' => max(2, min(40, (int) ($raw['bar_height'] ?? $defaults['bar_height']))),
            'bar_color' => sanitize_hex_color((string) ($raw['bar_color'] ?? '')) ?: $defaults['bar_color'],
            'bar_track_color' => sanitize_hex_color((string) ($raw['bar_track_color'] ?? '')) ?: $defaults['bar_track_color'],
            'text_color' => sanitize_hex_color((string) ($raw['text_color'] ?? '')) ?: $defaults['text_color'],
            'hide_when_reached' => !empty($raw['hide_when_reached']) ? 'yes' : '',
        ];

        update_option(self::OPTION_KEY, $clean);

        wp_safe_redirect(add_query_arg(
            ['page' => 'king-addons-free-shipping-bar', 'ka-saved' => '1'],
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Settings screen.
     *
     * @return void
     */
    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $s = $this->get_settings();
        $detected = $this->get_threshold();

        require __DIR__ . '/templates/admin-page.php';
    }
}
