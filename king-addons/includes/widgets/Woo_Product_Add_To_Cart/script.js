/**
 * Woo Product Add To Cart widget behavior.
 *
 * Adds products to cart via WooCommerce AJAX and refreshes fragments.
 */
(function ($) {
    "use strict";

    const INIT_FLAG = "kaWooProductAtcInit";

    const ajaxUrl = (window?.king_addons_grid_data && window.king_addons_grid_data.ajaxUrl) || window.ajaxurl || '';

    const postAddToCart = async (payload) => {
        const formData = new FormData();
        formData.append('action', 'woocommerce_ajax_add_to_cart');
        Object.entries(payload).forEach(([k, v]) => formData.append(k, v));

        const response = await fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        });

        if (!response.ok) {
            throw new Error('Request failed');
        }
        return response.json();
    };

    const updateFragments = (data) => {
        if (!window.jQuery || !data || !data.fragments) return;
        window.jQuery(document.body).trigger('wc_fragment_refresh');
        window.jQuery.each(data.fragments, (key, value) => {
            window.jQuery(key).replaceWith(value);
        });
        window.jQuery(document.body).trigger('added_to_cart', [data.fragments, data.cart_hash, data.cart_totals]);
    };

    const showState = (wrapper, type, text) => {
        const stateEl = wrapper.querySelector('.ka-woo-product-atc__state');
        if (!stateEl) return;
        stateEl.textContent = text || '';
        stateEl.dataset.state = type || '';
        stateEl.classList.toggle('is-visible', !!text);
    };

    const collectVariation = () => {
        const variationForm = document.querySelector('form.variations_form');
        if (!variationForm) return { valid: true, payload: {} };

        const payload = {};
        let hasEmpty = false;

        const variationIdField = variationForm.querySelector('input[name="variation_id"]');
        const variationId = variationIdField ? parseInt(variationIdField.value || '0', 10) : 0;
        const attrs = variationForm.querySelectorAll('select[name^="attribute_"]');
        attrs.forEach((sel) => {
            const name = sel.name;
            if (!name) return;
            const val = sel.value;
            if (!val) {
                hasEmpty = true;
                return;
            }
            payload[name] = val;
        });

        if (variationId) {
            payload.variation_id = variationId;
        }

        const valid = variationId || !hasEmpty;
        return { valid, payload };
    };

    const handleATC = (wrapper) => {
        if (!wrapper || !wrapper.dataset) return;
        if (wrapper.dataset[INIT_FLAG] === "1") return;
        wrapper.dataset[INIT_FLAG] = "1";

        const btns = wrapper.querySelectorAll('.ka-woo-product-atc__button');
        if (!btns.length) return;
        const qtyInput = wrapper.querySelector('.ka-woo-product-atc__qty input[type="number"]');
        const productId = parseInt(wrapper.dataset.productId || '0', 10);
        const ajax = wrapper.dataset.ajax === 'yes';
        const defaultRedirect = wrapper.dataset.redirect || 'stay';
        const successText = wrapper.dataset.success || '';
        const errorText = wrapper.dataset.error || '';
        const hasVariations = wrapper.dataset.hasVariations === 'yes';

        const setLoading = (isLoading) => {
            btns.forEach((b) => {
                b.disabled = isLoading;
                b.classList.toggle('is-loading', isLoading);
            });
            if (qtyInput) {
                qtyInput.disabled = isLoading;
            }
        };

        btns.forEach((btn) => {
            btn.addEventListener('click', async (e) => {
                if (!ajax) return;
                e.preventDefault();
                const redirect = btn.dataset.redirect || defaultRedirect;
                const qty = qtyInput ? parseInt(qtyInput.value || '1', 10) || 1 : 1;

                if (!productId) return;

                const variation = collectVariation();
                if (hasVariations && !variation.valid) {
                    showState(wrapper, 'error', errorText || 'Please choose product options.');
                    return;
                }

                setLoading(true);
                showState(wrapper, '', '');

                try {
                    const payload = {
                        product_id: productId,
                        quantity: qty,
                        ...variation.payload,
                    };

                    const result = await postAddToCart(payload);

                    if (redirect === 'cart' && window.wc_add_to_cart_params?.cart_url) {
                        window.location.href = window.wc_add_to_cart_params.cart_url;
                        return;
                    }
                    if (redirect === 'checkout' && window.wc_add_to_cart_params?.checkout_url) {
                        window.location.href = window.wc_add_to_cart_params.checkout_url;
                        return;
                    }

                    updateFragments(result);
                    showState(wrapper, 'success', successText || 'Added to cart');
                } catch (err) {
                    showState(wrapper, 'error', errorText || 'Could not add to cart.');
                } finally {
                    setLoading(false);
                }
            });
        });
    };

    const initAll = (root) => {
        const ctx = root && root.querySelectorAll ? root : document;
        ctx.querySelectorAll('.ka-woo-product-atc[data-ajax="yes"]').forEach(handleATC);
    };

    document.addEventListener("DOMContentLoaded", () => initAll(document));

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/woo_product_add_to_cart.default",
            function ($scope) {
                initAll($scope && $scope[0] ? $scope[0] : document);
            }
        );
    });
})(jQuery);




