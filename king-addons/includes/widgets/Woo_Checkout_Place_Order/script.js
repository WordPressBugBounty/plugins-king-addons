/**
 * Woo Checkout Place Order widget behaviour.
 *
 * The button carries form="checkout", but WooCommerce's checkout form has no
 * id - only name="checkout" and class="checkout". An unresolvable form
 * attribute makes a submit button inert, so a button placed outside the form
 * did nothing at all. Give the form an id and point the buttons at it.
 */
(function () {
    'use strict';

    const FORM_ID = 'ka-woo-checkout-form';

    const attach = () => {
        const buttons = document.querySelectorAll('.ka-woo-checkout-place-order__btn');
        if (!buttons.length) {
            return;
        }

        const form = document.querySelector('form.woocommerce-checkout, form.checkout[name="checkout"]');
        if (!form) {
            return;
        }

        if (!form.id) {
            form.id = FORM_ID;
        }

        buttons.forEach((button) => {
            if (button.form === form) {
                return;
            }
            button.setAttribute('form', form.id);
        });
    };

    document.addEventListener('DOMContentLoaded', attach);

    // The checkout markup is re-rendered on every update_order_review call.
    if (window.jQuery) {
        window.jQuery(document.body).on('updated_checkout', attach);
    }

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/woo_checkout_place_order.default', attach);
    }
})();
