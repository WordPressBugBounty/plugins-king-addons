<?php

namespace King_Addons\Wishlist;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles front-end hooks, assets, AJAX, and shortcodes.
 */
class Wishlist_Frontend
{
    private Wishlist_Service $service;
    private Wishlist_Renderer $renderer;

    /**
     * @param Wishlist_Service $service Wishlist service instance.
     * @param Wishlist_Renderer $renderer Renderer instance.
     */
    public function __construct(Wishlist_Service $service, Wishlist_Renderer $renderer)
    {
        $this->service = $service;
        $this->renderer = $renderer;
    }

    /**
     * Register scripts, ajax handlers, and shortcodes.
     *
     * @return void
     */
    public function hooks(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        add_action('wp_ajax_nopriv_king_addons_wishlist_toggle', [$this, 'handle_toggle']);
        add_action('wp_ajax_king_addons_wishlist_toggle', [$this, 'handle_toggle']);

        add_action('wp_ajax_nopriv_king_addons_wishlist_count', [$this, 'handle_count']);
        add_action('wp_ajax_king_addons_wishlist_count', [$this, 'handle_count']);

        add_action('wp_ajax_king_addons_wishlist_update_note', [$this, 'handle_update_note']);
        add_action('wp_ajax_king_addons_wishlist_remove', [$this, 'handle_remove']);

        add_shortcode('ka_wishlist_button', [$this, 'shortcode_button']);
        add_shortcode('ka_wishlist_counter', [$this, 'shortcode_counter']);
        add_shortcode('ka_wishlist_page', [$this, 'shortcode_page']);
    }

    /**
     * Enqueue wishlist assets and pass localized data.
     *
     * @return void
     */
    public function enqueue_assets(): void
    {
        if (!Wishlist_Settings::is_enabled()) {
            return;
        }

        wp_enqueue_style(
            'king-addons-wishlist',
            KING_ADDONS_URL . 'includes/wishlist/assets/style.css',
            [],
            KING_ADDONS_VERSION
        );

        wp_enqueue_script(
            'king-addons-wishlist',
            KING_ADDONS_URL . 'includes/wishlist/assets/script.js',
            ['jquery'],
            KING_ADDONS_VERSION,
            true
        );

        wp_localize_script(
            'king-addons-wishlist',
            'kingAddonsWishlist',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('king_addons_wishlist'),
                'labels' => [
                    'added' => Wishlist_Settings::get('button_added_text'),
                    'add' => Wishlist_Settings::get('button_add_text'),
                    'error' => esc_html__('Unable to update wishlist. Please try again.', 'king-addons'),
                ],
            ]
        );
    }

    /**
     * Handle AJAX toggle request.
     *
     * @return void
     */
    public function handle_toggle(): void
    {
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce(sanitize_text_field($nonce), 'king_addons_wishlist')) {
            wp_send_json_error(['message' => esc_html__('Invalid nonce.', 'king-addons')], 400);
        }

        $product_id = absint($_POST['product_id'] ?? 0);
        $variation_id = absint($_POST['variation_id'] ?? 0);
        $wishlist_id = isset($_POST['wishlist_id']) ? sanitize_title(wp_unslash($_POST['wishlist_id'])) : null;

        if (!$product_id) {
            wp_send_json_error(['message' => esc_html__('Product id is required.', 'king-addons')], 400);
        }

        if (!Wishlist_Settings::guests_allowed() && !is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Please sign in to use wishlist.', 'king-addons')], 403);
        }

        $result = $this->service->toggle_item($product_id, $variation_id, 1, $wishlist_id);
        if ($result instanceof WP_Error) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        }

        wp_send_json_success([
            'wishlist_id' => $result['wishlist_id'],
            'count' => $result['count'],
            'state' => !empty($result['success']) ? ($this->service->has_item($product_id, $variation_id, $wishlist_id) ? 'added' : 'default') : 'default',
        ]);
    }

    /**
     * Return wishlist count via AJAX.
     *
     * @return void
     */
    public function handle_count(): void
    {
        $nonce = $_GET['nonce'] ?? '';
        if (!wp_verify_nonce(sanitize_text_field($nonce), 'king_addons_wishlist')) {
            wp_send_json_error(['message' => esc_html__('Invalid nonce.', 'king-addons')], 400);
        }

        $wishlist_id = isset($_GET['wishlist_id']) ? sanitize_title(wp_unslash($_GET['wishlist_id'])) : null;
        wp_send_json_success([
            'count' => $this->service->get_count($wishlist_id),
        ]);
    }

    /**
     * Handle AJAX request to update product note (Pro feature).
     *
     * @return void
     */
    public function handle_update_note(): void
    {
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce(sanitize_text_field($nonce), 'king_addons_wishlist')) {
            wp_send_json_error(['message' => esc_html__('Invalid nonce.', 'king-addons')], 400);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => esc_html__('Please sign in to add notes.', 'king-addons')], 403);
        }

        // Pro check
        if (!function_exists('king_addons_freemius') || !king_addons_freemius()->can_use_premium_code__premium_only()) {
            wp_send_json_error(['message' => esc_html__('Notes require Pro version.', 'king-addons')], 403);
        }

        $product_id = absint($_POST['product_id'] ?? 0);
        $variation_id = absint($_POST['variation_id'] ?? 0);
        $note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));
        $wishlist_id = isset($_POST['wishlist_id']) ? sanitize_title(wp_unslash($_POST['wishlist_id'])) : null;

        if (!$product_id) {
            wp_send_json_error(['message' => esc_html__('Product ID required.', 'king-addons')], 400);
        }

        $result = $this->service->update_item_note($product_id, $variation_id, $note, $wishlist_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        }

        wp_send_json_success(['message' => esc_html__('Note saved.', 'king-addons')]);
    }

    /**
     * Handle AJAX request to remove item from wishlist.
     *
     * @return void
     */
    public function handle_remove(): void
    {
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce(sanitize_text_field($nonce), 'king_addons_wishlist')) {
            wp_send_json_error(['message' => esc_html__('Invalid nonce.', 'king-addons')], 400);
        }

        $product_id = absint($_POST['product_id'] ?? 0);
        $variation_id = absint($_POST['variation_id'] ?? 0);
        $wishlist_id = isset($_POST['wishlist_id']) ? sanitize_title(wp_unslash($_POST['wishlist_id'])) : null;

        if (!$product_id) {
            wp_send_json_error(['message' => esc_html__('Product ID required.', 'king-addons')], 400);
        }

        $result = $this->service->remove_item($product_id, $variation_id, $wishlist_id);

        wp_send_json_success([
            'count' => $result['count'],
            'message' => esc_html__('Removed from wishlist.', 'king-addons'),
        ]);
    }

    /**
     * Shortcode renderer for wishlist button.
     *
     * @param array<string, mixed> $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public function shortcode_button(array $atts): string
    {
        if (!Wishlist_Settings::is_enabled()) {
            return '';
        }

        $atts = shortcode_atts(
            [
                'product_id' => get_the_ID(),
                'variation_id' => 0,
                'wishlist_id' => '',
            ],
            $atts
        );

        return $this->renderer->render_button([
            'product_id' => absint($atts['product_id']),
            'variation_id' => absint($atts['variation_id']),
            'wishlist_id' => $atts['wishlist_id'],
        ]);
    }

    /**
     * Shortcode renderer for wishlist counter.
     *
     * @param array<string, mixed> $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public function shortcode_counter(array $atts): string
    {
        if (!Wishlist_Settings::is_enabled()) {
            return '';
        }

        $atts = shortcode_atts(
            [
                'wishlist_id' => '',
            ],
            $atts
        );

        return $this->renderer->render_counter([
            'wishlist_id' => $atts['wishlist_id'],
        ]);
    }

    /**
     * Render wishlist page/list shortcode.
     *
     * @param array<string, mixed> $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public function shortcode_page(array $atts): string
    {
        if (!Wishlist_Settings::is_enabled()) {
            return '';
        }

        if (!Wishlist_Settings::guests_allowed() && !is_user_logged_in()) {
            return '<div class="king-addons-wishlist__notice">' . esc_html(Wishlist_Settings::get('guest_block_text')) . '</div>';
        }

        $atts = shortcode_atts(
            [
                'wishlist_id' => '',
            ],
            $atts
        );

        $wishlist_id = $atts['wishlist_id'] ?: $this->service->get_active_wishlist_id();
        $items = $this->service->get_items($wishlist_id);
        $columns = Wishlist_Settings::get('wishlist_columns', Wishlist_Settings::defaults()['wishlist_columns']);
        $is_pro = function_exists('king_addons_freemius') && king_addons_freemius()->can_use_premium_code__premium_only();
        $column_labels = [
            'image' => esc_html__('Image', 'king-addons'),
            'title' => esc_html__('Title', 'king-addons'),
            'price' => esc_html__('Price', 'king-addons'),
            'stock' => esc_html__('Stock', 'king-addons'),
            'notes' => esc_html__('Notes', 'king-addons'),
            'add_to_cart' => esc_html__('Add to cart', 'king-addons'),
            'remove' => esc_html__('Remove', 'king-addons'),
        ];
        // Only show notes column if Pro and user is logged in
        if (!$is_pro || !is_user_logged_in()) {
            $columns = array_filter($columns, function ($col) {
                return $col !== 'notes';
            });
        }

        ob_start();
?>
        <div class="king-addons-wishlist-page" data-wishlist-id="<?php echo esc_attr($wishlist_id); ?>">
            <table class="king-addons-wishlist-table">
                <thead>
                    <tr>
                        <?php foreach ($columns as $column) : ?>
                            <th class="king-addons-wishlist-table__heading king-addons-wishlist-table__heading--<?php echo esc_attr($column); ?>">
                                <?php echo esc_html($column_labels[$column] ?? ucfirst(str_replace('_', ' ', $column))); ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)) : ?>
                        <tr>
                            <td colspan="<?php echo esc_attr(count($columns)); ?>" class="king-addons-wishlist-table__empty">
                                <?php esc_html_e('No products in wishlist yet.', 'king-addons'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($items as $item) : ?>
                            <?php
                            $product = function_exists('wc_get_product') ? wc_get_product($item->variation_id ?: $item->product_id) : null;
                            if (!$product) {
                                continue;
                            }
                            ?>
                            <tr class="king-addons-wishlist-table__row" data-product-id="<?php echo esc_attr($item->product_id); ?>">
                                <?php foreach ($columns as $column) : ?>
                                    <td class="king-addons-wishlist-table__cell king-addons-wishlist-table__cell--<?php echo esc_attr($column); ?>">
                                        <?php echo wp_kses_post($this->render_table_cell($column, $product, $item)); ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
<?php
        return ob_get_clean();
    }

    /**
     * Render a table cell content.
     *
     * @param string $column Column key.
     * @param \WC_Product $product Product instance.
     * @param object $item Wishlist item row.
     * @return string Rendered HTML.
     */
    private function render_table_cell(string $column, \WC_Product $product, object $item): string
    {
        switch ($column) {
            case 'image':
                return '<a href="' . esc_url($product->get_permalink()) . '" class="king-addons-wishlist-table__thumb">' . $product->get_image('thumbnail') . '</a>';
            case 'title':
                return '<a href="' . esc_url($product->get_permalink()) . '" class="king-addons-wishlist-table__title">' . esc_html($product->get_name()) . '</a>';
            case 'price':
                return '<span class="king-addons-wishlist-table__price">' . wp_kses_post($product->get_price_html()) . '</span>';
            case 'stock':
                return '<span class="king-addons-wishlist-table__stock">' . esc_html($product->is_in_stock() ? __('In stock', 'king-addons') : __('Out of stock', 'king-addons')) . '</span>';
            case 'notes':
                return $this->render_notes_cell($item);
            case 'add_to_cart':
                return $this->render_add_to_cart_button($product);
            case 'remove':
                return '<button type="button" class="king-addons-wishlist-button king-addons-wishlist-button--table-remove" data-product-id="' . esc_attr($item->product_id) . '" data-variation-id="' . esc_attr($item->variation_id) . '" data-wishlist-id="' . esc_attr($item->wishlist_id) . '"><span class="king-addons-wishlist-button__label">' . esc_html__('Remove', 'king-addons') . '</span></button>';
            default:
                return '';
        }
    }

    /**
     * Render Add to Cart button without relying on Woo shortcode.
     *
     * @param \WC_Product $product Product instance.
     * @return string
     */
    private function render_add_to_cart_button(\WC_Product $product): string
    {
        if (!$product->is_purchasable() || !$product->is_in_stock()) {
            return '<span class="king-addons-wishlist-table__add-to-cart is-disabled">' . esc_html__('Unavailable', 'king-addons') . '</span>';
        }

        $classes = [
            'king-addons-wishlist-table__add-to-cart',
            'button',
            'product_type_' . $product->get_type(),
        ];

        if ($product->supports('ajax_add_to_cart')) {
            $classes[] = 'ajax_add_to_cart';
            $classes[] = 'add_to_cart_button';
        }

        $attributes = [
            'href' => $product->add_to_cart_url(),
            'data-quantity' => 1,
            'data-product_id' => $product->get_id(),
            'data-product_sku' => $product->get_sku(),
            'rel' => 'nofollow',
            'class' => implode(' ', array_filter($classes)),
            'aria-label' => wp_strip_all_tags($product->add_to_cart_description()),
        ];

        return '<a ' . wc_implode_html_attributes($attributes) . '><span class="king-addons-wishlist-table__add-to-cart-label">' . esc_html($product->add_to_cart_text()) . '</span></a>';
    }

    /**
     * Render the notes cell with inline editing (Pro feature).
     *
     * @param object $item Wishlist item row.
     * @return string Rendered HTML.
     */
    private function render_notes_cell(object $item): string
    {
        $meta = [];
        if (!empty($item->meta)) {
            $decoded = json_decode($item->meta, true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }
        $note = $meta['note'] ?? '';

        ob_start();
        ?>
        <div class="king-addons-wishlist-notes" data-product-id="<?php echo esc_attr($item->product_id); ?>" data-variation-id="<?php echo esc_attr($item->variation_id); ?>" data-wishlist-id="<?php echo esc_attr($item->wishlist_id); ?>">
            <div class="king-addons-wishlist-notes__display" <?php echo empty($note) ? 'style="display:none;"' : ''; ?>>
                <span class="king-addons-wishlist-notes__text"><?php echo esc_html($note); ?></span>
                <button type="button" class="king-addons-wishlist-notes__edit-btn" title="<?php esc_attr_e('Edit note', 'king-addons'); ?>">
                    <span class="dashicons dashicons-edit"></span>
                </button>
            </div>
            <div class="king-addons-wishlist-notes__form" <?php echo !empty($note) ? 'style="display:none;"' : ''; ?>>
                <textarea class="king-addons-wishlist-notes__input" rows="2" maxlength="200" placeholder="<?php esc_attr_e('Add a note...', 'king-addons'); ?>"><?php echo esc_textarea($note); ?></textarea>
                <div class="king-addons-wishlist-notes__actions">
                    <button type="button" class="king-addons-wishlist-notes__save button button-small"><?php esc_html_e('Save', 'king-addons'); ?></button>
                    <?php if (!empty($note)) : ?>
                        <button type="button" class="king-addons-wishlist-notes__cancel button button-small"><?php esc_html_e('Cancel', 'king-addons'); ?></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}



