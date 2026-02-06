"use strict";

(function ($) {
    const initFloatingCart = ($scope) => {
        const $widget = $scope.find(".king-addons-floating-cart");
        if (!$widget.length) return;

        const $button = $widget.find(".king-addons-floating-cart__button");
        const $panel = $widget.find(".king-addons-floating-cart__panel");
        if (!$button.length || !$panel.length) return;

        const trigger = $button.data("trigger") || "click";
        const autoOpen = ($button.data("auto-open") || "no") === "yes";

        const openPanel = () => {
            $widget.addClass("is-open");
            $panel.removeAttr("hidden").attr("aria-hidden", "false");
            $button.attr("aria-expanded", "true");
        };

        const closePanel = () => {
            $widget.removeClass("is-open");
            $panel.attr("hidden", "hidden").attr("aria-hidden", "true");
            $button.attr("aria-expanded", "false");
        };

        const togglePanel = () => {
            if ($widget.hasClass("is-open")) {
                closePanel();
            } else {
                openPanel();
            }
        };

        if (trigger === "click") {
            $button.on("click", function (event) {
                event.preventDefault();
                togglePanel();
            });
        } else if (trigger === "hover") {
            let hoverTimer;
            $widget.on("mouseenter", () => {
                clearTimeout(hoverTimer);
                openPanel();
            });
            $widget.on("mouseleave", () => {
                hoverTimer = setTimeout(closePanel, 150);
            });
            $button.on("click", (event) => event.preventDefault());
        }

        $(document).on("click", function (event) {
            if (!$widget.is(event.target) && $widget.has(event.target).length === 0) {
                closePanel();
            }
        });

        if (autoOpen) {
            $(document.body).on("added_to_cart", () => {
                openPanel();
            });
        }
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-woocommerce-floating-cart-icon.default",
            function ($scope) {
                initFloatingCart($scope);
            }
        );
    });
})(jQuery);




