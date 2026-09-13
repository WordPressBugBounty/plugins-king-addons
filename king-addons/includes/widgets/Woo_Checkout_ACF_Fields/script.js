/**
 * Checkout ACF Extra Fields widget behaviour.
 *
 * The fields have to be inside WooCommerce's checkout form to be sent with
 * the order. When this widget renders before the Checkout Form widget, PHP
 * prints them into the form directly. When it renders after it, the form is
 * already on the page, so PHP hands the block over in a hidden carrier and
 * this script moves it to the spot the Placement control names.
 */
(function () {
    'use strict';

    // Mirrors the WooCommerce hooks the PHP side prints from.
    const TARGETS = {
        before_billing: ['.woocommerce-billing-fields__field-wrapper', 'before'],
        after_billing: ['.woocommerce-billing-fields__field-wrapper', 'after'],
        // Woo hides .shipping_address until “ship to a different address” is
        // checked. Placing inside that box made Before/After shipping a no-op.
        before_shipping: ['.shipping_address', 'before'],
        after_shipping: ['.shipping_address', 'after'],
        before_order: ['.woocommerce-additional-fields', 'prepend'],
        after_order: ['.woocommerce-additional-fields', 'append'],
    };

    const insert = (block, anchor, mode) => {
        if ('before' === mode) {
            anchor.parentNode.insertBefore(block, anchor);
        } else if ('after' === mode) {
            anchor.parentNode.insertBefore(block, anchor.nextSibling);
        } else if ('prepend' === mode) {
            anchor.insertBefore(block, anchor.firstChild);
        } else {
            anchor.appendChild(block);
        }
    };

    const place = () => {
        const carriers = document.querySelectorAll('.ka-woo-checkout-acf-fields-carrier');
        if (!carriers.length) {
            return;
        }

        const form = document.querySelector('form.woocommerce-checkout, form.checkout[name="checkout"]');
        if (!form) {
            return;
        }

        carriers.forEach((carrier) => {
            const block = carrier.firstElementChild;
            if (!block) {
                carrier.remove();
                return;
            }

            const [selector, mode] = TARGETS[carrier.getAttribute('data-ka-acf-placement')] || TARGETS.after_order;
            const anchor = form.querySelector(selector);

            if (anchor) {
                insert(block, anchor, mode);
            } else {
                // A layout without that section: keep the fields in the form.
                insert(block, form.querySelector('.woocommerce-additional-fields') || form.querySelector('#customer_details') || form, 'append');
            }

            carrier.remove();
        });

        // PHP can print the same block on woocommerce_before/after_checkout_shipping_form,
        // which WooCommerce fires inside the hidden .shipping_address. Lift it
        // to a sibling so Before/After shipping stays visible.
        document.querySelectorAll('.shipping_address .ka-woo-checkout-acf-fields').forEach((block) => {
            const addr = block.closest('.shipping_address');
            if (!addr || !addr.parentNode) {
                return;
            }
            if ('after_shipping' === block.getAttribute('data-ka-acf-placement')) {
                addr.parentNode.insertBefore(block, addr.nextSibling);
            } else {
                addr.parentNode.insertBefore(block, addr);
            }
        });
    };

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', place);
    } else {
        place();
    }
})();
