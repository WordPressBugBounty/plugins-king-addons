"use strict";

(function ($) {
    const ajaxUrl = (endpoint) => {
        if (typeof wc_add_to_cart_params !== "undefined") {
            return wc_add_to_cart_params.wc_ajax_url.replace("%%endpoint%%", endpoint);
        }
        return "";
    };

    const sendAddToCart = (productId, quantity) => {
        const url = ajaxUrl("add_to_cart");
        if (!url) {
            return Promise.reject(new Error("Missing WooCommerce AJAX URL"));
        }
        return $.post(url, {
            product_id: productId,
            quantity: quantity,
        });
    };

    const initAjaxAtc = ($scope) => {
        const wrapper = $scope[0]?.querySelector(".king-addons-ajax-atc");
        if (!wrapper) {
            return;
        }

        const button = wrapper.querySelector(".king-addons-ajax-atc__button");
        const notice = wrapper.querySelector(".king-addons-ajax-atc__notice");
        if (!button) {
            return;
        }

        const productId = parseInt(wrapper.dataset.productId || "0", 10);
        const successText = wrapper.dataset.successText || "Added to cart";
        const redirectCheckout = wrapper.dataset.redirectCheckout === "yes";
        const refreshFragments = wrapper.dataset.refreshFragments !== "no";
        const quantityField = wrapper.querySelector(".king-addons-ajax-atc__quantity");

        const setLoading = (isLoading) => {
            button.classList.toggle("loading", isLoading);
        };

        const setSuccess = (isSuccess) => {
            button.classList.toggle("added", isSuccess);
        };

        const setNotice = (message, isError = false) => {
            if (!notice) {
                return;
            }
            notice.textContent = message || "";
            notice.style.color = isError ? "#dc2626" : "";
        };

        button.addEventListener("click", (event) => {
            event.preventDefault();
            if (!productId) {
                setNotice("Product not found", true);
                return;
            }

            // Reset success state on each new attempt (Woo behavior).
            setSuccess(false);

            setNotice("");
            setLoading(true);

            const qty = quantityField
                ? Math.max(1, parseInt(quantityField.value || "1", 10))
                : parseInt(wrapper.dataset.quantity || "1", 10) || 1;

            sendAddToCart(productId, qty)
                .done((response) => {
                    setLoading(false);
                    if (response && response.error) {
                        setNotice(response.product_url ? "" : (response.message || "Error"), true);
                        if (response.product_url) {
                            window.location.href = response.product_url;
                        }
                        return;
                    }

                    // No text on success: show checkmark on the button instead.
                    setNotice("", false);
                    setSuccess(true);

                    if (refreshFragments && response && response.fragments) {
                        $.each(response.fragments, function (key, value) {
                            $(key).replaceWith(value);
                        });
                    }

                    if (redirectCheckout && response && response.cart_hash) {
                        window.location.href = wc_add_to_cart_params?.cart_url || "/";
                    }
                })
                .fail(() => {
                    setLoading(false);
                    setNotice("Error adding to cart", true);
                });
        });
    };

    $(window).on("elementor/frontend/init", () => {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-ajax-add-to-cart.default",
            ($scope) => {
                initAjaxAtc($scope);
            }
        );
    });
})(jQuery);







