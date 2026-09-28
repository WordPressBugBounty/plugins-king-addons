/**
 * Keep header/block mini-cart in sync after classic [woocommerce_cart] updates.
 */
(function ($) {
    'use strict';

    let refreshing = false;

    const refreshMiniCart = () => {
        if (refreshing) {
            return;
        }
        refreshing = true;

        document.dispatchEvent(new CustomEvent('wc-blocks_added_to_cart', { bubbles: true }));
        document.dispatchEvent(new CustomEvent('wc-blocks_removed_from_cart', { bubbles: true }));

        if (window.wp && wp.data && wp.data.dispatch) {
            try {
                const cartStore = wp.data.dispatch('wc/store/cart');
                if (cartStore && typeof cartStore.invalidateResolution === 'function') {
                    cartStore.invalidateResolution('getCartData');
                }
            } catch (e) {
                // Store may be unavailable on pages without WooCommerce blocks.
            }
        }

        if ($ && $.fn) {
            $(document.body).trigger('wc_fragment_refresh');
        }

        window.setTimeout(() => {
            refreshing = false;
        }, 250);
    };

    if ($ && $.fn) {
        $(document.body).on('updated_wc_div updated_cart_totals', refreshMiniCart);
    } else {
        document.body.addEventListener('updated_wc_div', refreshMiniCart);
        document.body.addEventListener('updated_cart_totals', refreshMiniCart);
    }
})(window.jQuery);
