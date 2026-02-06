<?php
/**
 * AJAX handler for Quick View Product.
 *
 * @package King_Addons
 */

use King_Addons\Quick_View_Product;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render a controlled Add to Cart button without relying on Woo shortcode.
 *
 * @param \WC_Product $product Product instance.
 *
 * @return string
 */
function king_addons_qv_render_add_to_cart_button(\WC_Product $product): string
{
    if (!$product->is_purchasable() || !$product->is_in_stock()) {
        return '<span class="king-addons-qv__add-to-cart button is-disabled">' . esc_html__('Unavailable', 'king-addons') . '</span>';
    }

    $classes = [
        'king-addons-qv__add-to-cart',
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

    return '<a ' . wc_implode_html_attributes($attributes) . '><span class="king-addons-qv__add-to-cart-label">' . esc_html($product->add_to_cart_text()) . '</span></a>';
}

/**
 * Render quick view HTML.
 *
 * @return void
 */
function king_addons_handle_quick_view_ajax(): void
{
    if (!class_exists('\WooCommerce')) {
        wp_send_json_error(['message' => esc_html__('WooCommerce not active.', 'king-addons')], 400);
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'king_addons_qv')) {
        wp_send_json_error(['message' => esc_html__('Invalid nonce.', 'king-addons')], 400);
    }

    $product_id = isset($_POST['product_id']) ? (int) wp_unslash($_POST['product_id']) : 0;
    if (!$product_id) {
        wp_send_json_error(['message' => esc_html__('Invalid product.', 'king-addons')], 400);
    }

    $settings_json = isset($_POST['qv_settings']) ? (string) wp_unslash($_POST['qv_settings']) : '';
    $settings = json_decode($settings_json, true);
    if (!is_array($settings)) {
        $settings = [];
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(['message' => esc_html__('Product not found.', 'king-addons')], 404);
    }

    $show_image = ($settings['show_image'] ?? 'yes') === 'yes';
    $show_price = ($settings['show_price'] ?? 'yes') === 'yes';
    $show_rating = ($settings['show_rating'] ?? 'yes') === 'yes';
    $show_excerpt = ($settings['show_excerpt'] ?? 'yes') === 'yes';
    $show_button = ($settings['show_button'] ?? 'yes') === 'yes';
    $pro_gallery = ($settings['pro_gallery'] ?? 'no') === 'yes';
    $pro_variations = ($settings['pro_variations'] ?? 'no') === 'yes';
    $pro_sticky_cta = ($settings['pro_sticky_cta'] ?? 'no') === 'yes';

    $html = '';

    ob_start();
    global $post;
    $post = get_post($product_id);
    if (!$post) {
        wp_send_json_error(['message' => esc_html__('Product not found.', 'king-addons')], 404);
    }
    setup_postdata($post);

    ?>
    <div class="king-addons-qv <?php echo $pro_sticky_cta ? 'king-addons-qv--sticky' : ''; ?>">
        <?php if ($show_image) : ?>
            <div class="king-addons-qv__media">
                <?php echo $product->get_image('large'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php
                if ($pro_gallery) {
                    $gallery_ids = $product->get_gallery_image_ids();
                    if (!empty($gallery_ids)) {
                        echo '<div class="king-addons-qv__thumbs">';
                        foreach ($gallery_ids as $gid) {
                            $thumb = wp_get_attachment_image($gid, 'thumbnail', false, ['class' => 'king-addons-qv__thumb']);
                            if ($thumb) {
                                echo '<div class="king-addons-qv__thumb-item">' . $thumb . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            }
                        }
                        echo '</div>';
                    }
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="king-addons-qv__content">
            <h3 class="king-addons-qv__title"><?php echo esc_html($product->get_name()); ?></h3>

            <?php if ($show_rating && wc_review_ratings_enabled()) : ?>
                <div class="king-addons-qv__rating">
                    <?php echo wc_get_rating_html($product->get_average_rating()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endif; ?>

            <?php if ($show_price) : ?>
                <div class="king-addons-qv__price">
                    <?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endif; ?>

            <?php if ($show_excerpt) : ?>
                <div class="king-addons-qv__excerpt">
                    <?php echo wp_kses_post(wpautop($product->get_short_description())); ?>
                </div>
            <?php endif; ?>

            <?php if ($show_button) : ?>
                <div class="king-addons-qv__cta">
                    <?php
                    if ($product->is_type('variable')) {
                        if ($pro_variations) {
                            wc_get_template('single-product/add-to-cart/variable.php', ['product' => $product]);
                        } else {
                            echo '<p class="king-addons-qv__notice">' . esc_html__('Select options on the product page.', 'king-addons') . '</p>';
                        }
                    } elseif ($product->is_type('simple')) {
                        wc_get_template('single-product/add-to-cart/simple.php', ['product' => $product]);
                    } else {
                        echo king_addons_qv_render_add_to_cart_button($product); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php

    wp_reset_postdata();
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}

add_action('wc_ajax_king_addons_qv', 'king_addons_handle_quick_view_ajax');
add_action('wc_ajax_nopriv_king_addons_qv', 'king_addons_handle_quick_view_ajax');







