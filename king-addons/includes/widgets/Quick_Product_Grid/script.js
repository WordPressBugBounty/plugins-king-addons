"use strict";

(function ($) {
    const initQuickProductGrid = ($scope) => {
        // No JS needed for base grid; reserved for Pro (filters, etc.).
        return;
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-quick-product-grid.default",
            function ($scope) {
                initQuickProductGrid($scope);
            }
        );
    });
})(jQuery);






