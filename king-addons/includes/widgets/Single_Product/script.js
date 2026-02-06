"use strict";

(function ($) {
    const initSingleProduct = ($scope) => {
        // Placeholder for future interactions (e.g., gallery, sticky CTA).
        // Currently no JS required for free version.
    };

    $(window).on("elementor/frontend/init", () => {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-single-product.default",
            ($scope) => {
                initSingleProduct($scope);
            }
        );
    });
})(jQuery);







