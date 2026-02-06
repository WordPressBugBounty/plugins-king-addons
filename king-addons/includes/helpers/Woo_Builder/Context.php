<?php
/**
 * Woo Builder context helpers.
 *
 * @package King_Addons
 */

namespace King_Addons\Woo_Builder;

use WC_Product;
use Elementor\Plugin as Elementor_Plugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Utility helpers for Woo Builder.
 */
class Context
{
    /**
     * Cached template type for current editor session.
     *
     * @var string|null
     */
    private static ?string $cached_editor_template_type = null;

    /**
     * Cached preview product.
     *
     * @var WC_Product|null|false
     */
    private static $cached_preview_product = false;

    /**
     * Check if we are in Elementor editor UI context (including Elementor AJAX render).
     *
     * Important: This should never return true on normal frontend requests.
     *
     * @return bool
     */
    public static function is_editor(): bool
    {
        // Check for Elementor AJAX render request first (when widget is added/updated)
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (wp_doing_ajax() && !empty($_REQUEST['action']) && strpos($_REQUEST['action'], 'elementor') !== false) {
            return true;
        }

        if (!class_exists('\Elementor\Plugin')) {
            return false;
        }

        if (!Elementor_Plugin::$instance) {
            return false;
        }

        // Check if editor is active
        if (isset(Elementor_Plugin::$instance->editor) && Elementor_Plugin::$instance->editor->is_edit_mode()) {
            return true;
        }

        // In the Elementor editor iframe, requests are served in preview mode.
        // Treat it as editor context only when Elementor's preview query var is present.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset(Elementor_Plugin::$instance->preview) && Elementor_Plugin::$instance->preview->is_preview_mode() && !empty($_GET['elementor-preview'])) {
            return true;
        }

        return false;
    }

    /**
     * Get the template type of the currently edited Woo Builder template.
     *
     * @return string|null Template type (single_product, product_archive, cart, checkout, my_account) or null.
     */
    public static function get_editor_template_type(): ?string
    {
        if (self::$cached_editor_template_type !== null) {
            return self::$cached_editor_template_type ?: null;
        }

        self::$cached_editor_template_type = '';

        if (!self::is_editor()) {
            return null;
        }

        // Get the post ID being edited
        $post_id = self::get_editor_post_id();
        if (!$post_id) {
            return null;
        }

        // Check if this is a Woo Builder template
        $template_type = get_post_meta($post_id, 'ka_woo_template_type', true);
        if (!empty($template_type)) {
            self::$cached_editor_template_type = $template_type;
            return $template_type;
        }

        // Also check Elementor document type
        $elementor_type = get_post_meta($post_id, '_elementor_template_type', true);
        if ('king-addons-woo-builder' === $elementor_type) {
            // Template type not set yet, but it's a Woo Builder template
            self::$cached_editor_template_type = 'single_product'; // Default
            return 'single_product';
        }

        return null;
    }

    /**
     * Get the post ID being edited in Elementor.
     *
     * @return int|null
     */
    public static function get_editor_post_id(): ?int
    {
        // First try $_GET parameters - most reliable and doesn't require Elementor to be fully loaded
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!empty($_GET['post'])) {
            return absint($_GET['post']);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!empty($_GET['elementor-preview'])) {
            return absint($_GET['elementor-preview']);
        }

        // For AJAX requests, check POST data for editor_post_id
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (wp_doing_ajax() && !empty($_POST['editor_post_id'])) {
            return absint($_POST['editor_post_id']);
        }

        // Try to get from current document if Elementor is available
        if (class_exists('\Elementor\Plugin') && Elementor_Plugin::$instance && isset(Elementor_Plugin::$instance->documents)) {
            $document = Elementor_Plugin::$instance->documents->get_current();
            if ($document) {
                return $document->get_main_id();
            }
        }

        // Fallback to global post
        global $post;
        if ($post && $post->ID) {
            return (int) $post->ID;
        }

        return null;
    }

    /**
     * Check if we're editing a specific Woo Builder template type.
     *
     * @param string $expected_type Expected template type.
     * @return bool
     */
    public static function is_editing_template_type(string $expected_type): bool
    {
        $current_type = self::get_editor_template_type();
        return $current_type === $expected_type;
    }

    /**
     * Get a preview product for editor mode.
     *
     * @return WC_Product|null
     */
    public static function get_preview_product(): ?WC_Product
    {
        if (self::$cached_preview_product !== false) {
            return self::$cached_preview_product;
        }

        self::$cached_preview_product = null;

        if (!function_exists('wc_get_products')) {
            return null;
        }

        // Try to get a published product for preview
        $products = wc_get_products([
            'status' => 'publish',
            'limit' => 6,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (!empty($products)) {
            foreach ($products as $candidate) {
                if (!$candidate instanceof WC_Product) {
                    continue;
                }

                if ($candidate->get_image_id() || $candidate->get_gallery_image_ids()) {
                    self::$cached_preview_product = $candidate;
                    return self::$cached_preview_product;
                }
            }

            if ($products[0] instanceof WC_Product) {
                self::$cached_preview_product = $products[0];
                return self::$cached_preview_product;
            }
        }

        return null;
    }

    /**
     * Setup preview product context for editor.
     * Call this at the start of widget render when in editor mode.
     *
     * @return WC_Product|null The preview product if set up, null otherwise.
     */
    public static function setup_preview_product(): ?WC_Product
    {
        global $product;

        // Only in editor mode and when editing a single_product template
        if (!self::is_editor()) {
            return null;
        }

        $template_type = self::get_editor_template_type();
        if ('single_product' !== $template_type) {
            return null;
        }

        // If product is already set, return it
        if ($product instanceof WC_Product) {
            return $product;
        }

        // Get preview product
        $preview = self::get_preview_product();
        if (!$preview) {
            return null;
        }

        // Set up global product context only - DO NOT change $post as it breaks Elementor
        $product = $preview;

        return $preview;
    }

    /**
     * Detect current WooCommerce context.
     *
     * Cart, Checkout, and My Account pages may not pass is_woocommerce()
     * so we check them separately.
     *
     * @return string|null
     */
    public static function detect_context(): ?string
    {
        // Note: We do NOT check is_editor() here because this method is also called
        // by override_wc_template() which has is_admin() check already.
        // Editor context detection is handled separately in maybe_render_context_notice().
        
        if (!function_exists('is_woocommerce')) {
            return null;
        }

        // Check cart page first (may not pass is_woocommerce())
        if (function_exists('is_cart') && is_cart()) {
            return 'cart';
        }

        // Check checkout page (may not pass is_woocommerce())
        if (function_exists('is_checkout') && is_checkout() && !is_order_received_page()) {
            return 'checkout';
        }

        // Check My Account page (may not pass is_woocommerce())
        if (function_exists('is_account_page') && is_account_page()) {
            return 'my_account';
        }

        // For product and archive pages, is_woocommerce() should be true
        if (!is_woocommerce()) {
            return null;
        }

        if (is_product()) {
            return 'single_product';
        }

        if (is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy()) {
            return 'product_archive';
        }

        return null;
    }

    /**
    * Get current product ID.
    *
    * @return int|null
    */
    public static function get_product_id(): ?int
    {
        global $product, $post;

        if ($product instanceof WC_Product) {
            return (int) $product->get_id();
        }

        if (!empty($post->ID) && 'product' === get_post_type($post->ID)) {
            return (int) $post->ID;
        }

        return null;
    }

    /**
    * Match conditions array against current page context.
    *
    * @param string               $context    Page context.
    * @param array<string,mixed>  $conditions Conditions array.
    *
    * @return bool
    */
    public static function match_conditions(string $context, array $conditions): bool
    {
        if (empty($conditions['rules']) || !is_array($conditions['rules'])) {
            return true;
        }

        $product_id = self::get_product_id();

        foreach ($conditions['rules'] as $rule) {
            $type = $rule['type'] ?? '';
            $values = is_array($rule['values'] ?? null) ? $rule['values'] : [];

            if (!self::match_rule($context, $type, $values, $product_id)) {
                return false;
            }
        }

        return true;
    }

    /**
    * Match a single rule.
    *
    * @param string    $context    Context.
    * @param string    $type       Rule type.
    * @param array     $values     Rule values.
    * @param int|null  $product_id Product ID.
    *
    * @return bool
    */
    private static function match_rule(string $context, string $type, array $values, ?int $product_id): bool
    {
        switch ($type) {
            // Universal "all" type - matches everything for the context
            case 'all':
                return true;
                
            // Single Product conditions
            case 'all_products':
                return 'single_product' === $context;
                
            case 'specific_product':
            case 'product_in':
            case 'products':
                $ids = array_filter(array_map('absint', $values));
                return ('single_product' === $context && $product_id && in_array($product_id, $ids, true));
                
            case 'product_cat':
            case 'product_cat_in':
            case 'product_categories':
                // For single product - check if product is in category
                if ('single_product' === $context && $product_id) {
                    $ids = array_filter(array_map('absint', $values));
                    if (empty($ids)) {
                        return true; // No specific categories = all categories
                    }
                    $terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
                    return !empty(array_intersect($ids, $terms));
                }
                // For archive - check if on category archive
                if ('product_archive' === $context && is_product_category()) {
                    $term = get_queried_object();
                    if (!empty($term->term_id)) {
                        $ids = array_filter(array_map('absint', $values));
                        if (empty($ids)) {
                            return true;
                        }
                        return in_array((int) $term->term_id, $ids, true);
                    }
                }
                return false;
                
            case 'product_tag':
            case 'product_tag_in':
            case 'product_tags':
                // For single product - check if product has tag
                if ('single_product' === $context && $product_id) {
                    $ids = array_filter(array_map('absint', $values));
                    if (empty($ids)) {
                        return true; // No specific tags = all tags
                    }
                    $terms = wp_get_post_terms($product_id, 'product_tag', ['fields' => 'ids']);
                    return !empty(array_intersect($ids, $terms));
                }
                // For archive - check if on tag archive
                if ('product_archive' === $context && is_product_tag()) {
                    $term = get_queried_object();
                    if (!empty($term->term_id)) {
                        $ids = array_filter(array_map('absint', $values));
                        if (empty($ids)) {
                            return true;
                        }
                        return in_array((int) $term->term_id, $ids, true);
                    }
                }
                return false;
                
            case 'product_type_in':
            case 'product_types':
                if ('single_product' !== $context || !$product_id) {
                    return false;
                }
                $types = wp_get_object_terms($product_id, 'product_type', ['fields' => 'slugs']);
                $wanted = array_values(array_filter(array_map('sanitize_text_field', $values)));
                return !empty(array_intersect($wanted, $types));
                
            // Archive conditions
            case 'shop':
            case 'is_shop':
                return 'product_archive' === $context && is_shop();
                
            case 'product_cat_archive_in':
            case 'product_cat_archives':
                if ('product_archive' !== $context) {
                    return false;
                }
                if (is_product_category()) {
                    $term = get_queried_object();
                    if (!empty($term->term_id)) {
                        $ids = array_filter(array_map('absint', $values));
                        if (empty($values)) {
                            return true;
                        }
                        return in_array((int) $term->term_id, $ids, true);
                    }
                }
                return false;
                
            case 'product_tag_archive_in':
            case 'product_tag_archives':
                if ('product_archive' !== $context) {
                    return false;
                }
                if (is_product_tag()) {
                    $term = get_queried_object();
                    if (!empty($term->term_id)) {
                        $ids = array_filter(array_map('absint', $values));
                        if (empty($values)) {
                            return true;
                        }
                        return in_array((int) $term->term_id, $ids, true);
                    }
                }
                return false;
            case 'cart':
                return 'cart' === $context;
            case 'checkout':
                return 'checkout' === $context;
            case 'my_account':
                return 'my_account' === $context;
            case 'always':
                return true;
            default:
                return true;
        }
    }

    /**
     * Render an editor-only notice if widget is not in the expected Woo context.
     *
     * @param string $expected_context Expected context slug.
     *
     * @return bool True when context matches or not in edit mode, false when notice rendered.
     */
    public static function maybe_render_context_notice(string $expected_context): bool
    {
        // Not in editor mode - allow render (frontend will show appropriate content)
        if (!class_exists('\Elementor\Plugin') || !Elementor_Plugin::$instance->editor->is_edit_mode()) {
            return true;
        }

        // Check if we're editing a Woo Builder template of the matching type
        $editor_template_type = self::get_editor_template_type();
        if ($editor_template_type === $expected_context) {
            // We're editing the correct template type - allow render with preview data
            return true;
        }

        // Fallback: check actual page context (for when editing on actual WooCommerce pages)
        $current = self::detect_context();
        if ($current === $expected_context) {
            return true;
        }

        // Show notice only if we're not editing any Woo Builder template
        // or editing a different type
        $labels = [
            'single_product' => esc_html__('This widget works on Single Product pages.', 'king-addons'),
            'product_archive' => esc_html__('This widget works on Product Archive pages.', 'king-addons'),
            'cart' => esc_html__('This widget works on the Cart page.', 'king-addons'),
            'checkout' => esc_html__('This widget works on the Checkout page.', 'king-addons'),
            'my_account' => esc_html__('This widget works on My Account pages.', 'king-addons'),
        ];

        // If editing a Woo Builder template but wrong type, show helpful message
        if ($editor_template_type) {
            $type_labels = [
                'single_product' => esc_html__('Single Product', 'king-addons'),
                'product_archive' => esc_html__('Product Archive', 'king-addons'),
                'cart' => esc_html__('Cart', 'king-addons'),
                'checkout' => esc_html__('Checkout', 'king-addons'),
                'my_account' => esc_html__('My Account', 'king-addons'),
            ];
            $expected_label = $type_labels[$expected_context] ?? $expected_context;
            $current_label = $type_labels[$editor_template_type] ?? $editor_template_type;
            
            $message = sprintf(
                /* translators: 1: expected template type, 2: current template type */
                esc_html__('This widget is for %1$s templates. You are editing a %2$s template.', 'king-addons'),
                $expected_label,
                $current_label
            );
        } else {
            $message = $labels[$expected_context] ?? esc_html__('This widget is only available on WooCommerce pages.', 'king-addons');
        }

        echo '<div class="king-addons-woo-builder-notice">' . esc_html($message) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return false;
    }
}



