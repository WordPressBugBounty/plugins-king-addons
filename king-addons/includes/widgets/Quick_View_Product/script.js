"use strict";

(function ($) {
    const ajaxUrl = (endpoint) => {
        if (typeof wc_add_to_cart_params !== "undefined") {
            return wc_add_to_cart_params.wc_ajax_url.replace("%%endpoint%%", endpoint);
        }
        return "";
    };

    const fetchProductFragment = (productId, settings, nonce) => {
        const url = ajaxUrl("king_addons_qv");
        if (!url) {
            return Promise.reject(new Error("Missing AJAX URL"));
        }
        return $.post(url, { product_id: productId, qv_settings: settings, nonce: nonce || "" });
    };

    const closeModal = (modal) => {
        modal.classList.remove("is-open");
        const productContainer = modal.querySelector(".king-addons-quick-view__product");
        if (productContainer) {
            productContainer.innerHTML = "";
        }
    };

    const initQuickView = ($scope) => {
        const root = $scope[0]?.querySelector(".king-addons-quick-view");
        if (!root) {
            return;
        }

        const trigger = root.querySelector(".king-addons-quick-view__trigger");
        const modal = root.querySelector(".king-addons-quick-view__modal");
        const overlay = root.querySelector(".king-addons-quick-view__overlay");
        const closeBtn = root.querySelector(".king-addons-quick-view__close");
        const loader = root.querySelector(".king-addons-quick-view__loader");
        const productContainer = root.querySelector(".king-addons-quick-view__product");

        if (!trigger || !modal || !overlay || !closeBtn || !loader || !productContainer) {
            return;
        }

        const productId = parseInt(root.dataset.productId || "0", 10);
        const settings = root.dataset.qvSettings || "";
        const nonce = root.dataset.nonce || "";
        if (!productId) {
            return;
        }

        const openModal = () => {
            modal.classList.add("is-open");
            loader.classList.add("is-active");
            productContainer.innerHTML = "";

            fetchProductFragment(productId, settings, nonce)
                .done((response) => {
                    loader.classList.remove("is-active");
                    if (response && response.html) {
                        productContainer.innerHTML = response.html;
                        const $body = $(document.body);
                        $body.trigger("wc_fragments_refreshed");
                        const $forms = $(productContainer).find("form.variations_form");
                        if ($forms.length) {
                            $forms.each(function () {
                                const $form = $(this);
                                $form.wc_variation_form();
                                $form.find(".variations select").trigger("change");
                            });
                        }
                    } else {
                        productContainer.innerHTML = "<p>Product not found.</p>";
                    }
                })
                .fail(() => {
                    loader.classList.remove("is-active");
                    productContainer.innerHTML = "<p>Error loading product.</p>";
                });
        };

        const handleClose = () => {
            closeModal(modal);
        };

        trigger.addEventListener("click", (event) => {
            event.preventDefault();
            openModal();
        });

        overlay.addEventListener("click", handleClose);
        closeBtn.addEventListener("click", handleClose);

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && modal.classList.contains("is-open")) {
                handleClose();
            }
        });
    };

    $(window).on("elementor/frontend/init", () => {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-quick-view-product.default",
            ($scope) => {
                initQuickView($scope);
            }
        );
    });
})(jQuery);







