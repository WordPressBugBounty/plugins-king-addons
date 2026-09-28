/**
 * Checkout Coupon widget: open the coupon form when the notice banner is tapped.
 *
 * WooCommerce only binds a.showcoupon. On narrow viewports the link wraps, so
 * most of the banner's hit area is the parent div and the form stays closed.
 */
(function ($) {
    'use strict';

    $(document).on('click', '.ka-woo-checkout-coupon .woocommerce-form-coupon-toggle', function (event) {
        if (event.target.closest('a.showcoupon')) {
            return;
        }
        const link = this.querySelector('a.showcoupon');
        if (!link) {
            return;
        }
        event.preventDefault();
        link.click();
    });
})(jQuery);
