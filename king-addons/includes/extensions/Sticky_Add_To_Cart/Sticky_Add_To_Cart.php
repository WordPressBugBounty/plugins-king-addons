<?php
/**
 * Sticky Add To Cart bar.
 *
 * Keeps the product's price and buy button reachable once the visitor has
 * scrolled the real add-to-cart form out of view.
 *
 * @package King_Addons
 */

namespace King_Addons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sticky add-to-cart module (Pro).
 */
final class Sticky_Add_To_Cart
{
    /**
     * Settings option key.
     */
    public const OPTION_KEY = 'king_addons_sticky_add_to_cart_settings';

    /**
     * Singleton instance.
     *
     * @var Sticky_Add_To_Cart|null
     */
    private static ?Sticky_Add_To_Cart $instance = null;

    /**
     * Cached settings.
     *
     * @var array<string, mixed>
     */
    private array $settings = [];

    /**
     * Singleton accessor.
     *
     * @return Sticky_Add_To_Cart
     */
    public static function instance(): Sticky_Add_To_Cart
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
        add_action('admin_post_king_addons_satc_save', [$this, 'handle_save']);

        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);
        add_action('wp_footer', [$this, 'render_bar'], 20);
    }

    /**
     * Whether the paid tier is active.
     *
     * @return bool
     */
    private function can_use_pro(): bool
    {
        return function_exists('king_addons_can_use_pro') && king_addons_can_use_pro();
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
            'position' => 'bottom',
            'show_thumbnail' => 'yes',
            'show_price' => 'yes',
            'show_on_mobile' => 'yes',
            'button_text' => __('Add to cart', 'king-addons'),
            'trigger_offset' => 400,
            'bg_color' => '#ffffff',
            'text_color' => '#1f2933',
            'button_bg_color' => '#1f2933',
            'button_text_color' => '#ffffff',
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
     * Whether the bar should render on this request.
     *
     * @return bool
     */
    private function should_render(): bool
    {
        if (empty($this->settings['enabled']) || !$this->can_use_pro()) {
            return false;
        }

        if (!function_exists('is_product') || !is_product()) {
            return false;
        }

        if (empty($this->settings['show_on_mobile']) && wp_is_mobile()) {
            return false;
        }

        return true;
    }

    /**
     * Front-end assets.
     *
     * @return void
     */
    public function maybe_enqueue_assets(): void
    {
        if (!$this->should_render()) {
            return;
        }

        $dir = KING_ADDONS_PATH . 'includes/extensions/Sticky_Add_To_Cart/assets/';
        $css_ver = file_exists($dir . 'style.css') ? (string) filemtime($dir . 'style.css') : KING_ADDONS_VERSION;
        $js_ver = file_exists($dir . 'script.js') ? (string) filemtime($dir . 'script.js') : KING_ADDONS_VERSION;

        wp_enqueue_style(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-sticky-add-to-cart',
            KING_ADDONS_URL . 'includes/extensions/Sticky_Add_To_Cart/assets/style.css',
            [],
            $css_ver
        );

        wp_enqueue_script(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-sticky-add-to-cart',
            KING_ADDONS_URL . 'includes/extensions/Sticky_Add_To_Cart/assets/script.js',
            [],
            $js_ver,
            true
        );

        wp_localize_script(
            KING_ADDONS_ASSETS_UNIQUE_KEY . '-sticky-add-to-cart',
            'kingAddonsStickyAddToCart',
            ['offset' => (int) $this->settings['trigger_offset']]
        );
    }

    /**
     * Render the bar markup in the footer.
     *
     * @return void
     */
    public function render_bar(): void
    {
        if (!$this->should_render()) {
            return;
        }

        global $product;
        if (!$product instanceof \WC_Product) {
            $product = wc_get_product(get_the_ID());
        }

        if (!$product instanceof \WC_Product || !$product->is_purchasable()) {
            return;
        }

        $style = sprintf(
            '--ka-satc-bg:%s;--ka-satc-text:%s;--ka-satc-btn-bg:%s;--ka-satc-btn-text:%s;',
            sanitize_hex_color((string) $this->settings['bg_color']) ?: '#ffffff',
            sanitize_hex_color((string) $this->settings['text_color']) ?: '#1f2933',
            sanitize_hex_color((string) $this->settings['button_bg_color']) ?: '#1f2933',
            sanitize_hex_color((string) $this->settings['button_text_color']) ?: '#ffffff'
        );

        $position = 'top' === ($this->settings['position'] ?? 'bottom') ? 'top' : 'bottom';

        // A variable product cannot be added from a single button - send the
        // visitor back to the real form instead of guessing a variation.
        $is_simple = $product->is_type('simple');
        $url = $is_simple
            ? esc_url($product->add_to_cart_url())
            : '#ka-satc-form-anchor';
        $classes = 'king-addons-satc__button button';
        if ($is_simple) {
            $classes .= ' add_to_cart_button ajax_add_to_cart';
        }
        ?>
        <div class="king-addons-satc king-addons-satc--<?php echo esc_attr($position); ?>"
            style="<?php echo esc_attr($style); ?>" hidden>
            <div class="king-addons-satc__inner">
                <?php if (!empty($this->settings['show_thumbnail'])) : ?>
                    <div class="king-addons-satc__thumb"><?php echo wp_kses_post($product->get_image('thumbnail')); ?></div>
                <?php endif; ?>

                <div class="king-addons-satc__meta">
                    <span class="king-addons-satc__title"><?php echo esc_html($product->get_name()); ?></span>
                    <?php if (!empty($this->settings['show_price'])) : ?>
                        <span class="king-addons-satc__price"><?php echo wp_kses_post($product->get_price_html()); ?></span>
                    <?php endif; ?>
                </div>

                <a href="<?php echo $url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
                    class="<?php echo esc_attr($classes); ?>"
                    data-quantity="1"
                    data-product_id="<?php echo esc_attr((string) $product->get_id()); ?>"
                    rel="nofollow">
                    <?php echo esc_html((string) $this->settings['button_text']); ?>
                </a>
            </div>
        </div>
        <?php
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
            esc_html__('Sticky Add To Cart', 'king-addons'),
            esc_html__('Sticky Add To Cart', 'king-addons'),
            'manage_options',
            'king-addons-sticky-add-to-cart',
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

        check_admin_referer('king_addons_satc_save');

        $raw = isset($_POST['ka_satc']) && is_array($_POST['ka_satc'])
            ? wp_unslash($_POST['ka_satc']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : [];

        $defaults = $this->get_default_settings();

        update_option(self::OPTION_KEY, [
            'enabled' => !empty($raw['enabled']),
            'position' => in_array(($raw['position'] ?? 'bottom'), ['top', 'bottom'], true) ? $raw['position'] : 'bottom',
            'show_thumbnail' => !empty($raw['show_thumbnail']) ? 'yes' : '',
            'show_price' => !empty($raw['show_price']) ? 'yes' : '',
            'show_on_mobile' => !empty($raw['show_on_mobile']) ? 'yes' : '',
            'button_text' => sanitize_text_field((string) ($raw['button_text'] ?? $defaults['button_text'])),
            'trigger_offset' => max(0, min(5000, (int) ($raw['trigger_offset'] ?? $defaults['trigger_offset']))),
            'bg_color' => sanitize_hex_color((string) ($raw['bg_color'] ?? '')) ?: $defaults['bg_color'],
            'text_color' => sanitize_hex_color((string) ($raw['text_color'] ?? '')) ?: $defaults['text_color'],
            'button_bg_color' => sanitize_hex_color((string) ($raw['button_bg_color'] ?? '')) ?: $defaults['button_bg_color'],
            'button_text_color' => sanitize_hex_color((string) ($raw['button_text_color'] ?? '')) ?: $defaults['button_text_color'],
        ]);

        wp_safe_redirect(add_query_arg(
            ['page' => 'king-addons-sticky-add-to-cart', 'ka-saved' => '1'],
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
        $is_pro = $this->can_use_pro();

        require __DIR__ . '/templates/admin-page.php';
    }
}
