"use strict";

(function ($) {
    const initCptGrid = ($scope) => {
        const isEditMode = elementorFrontend.isEditMode();
        if (isEditMode) {
            return;
        }

        const grid = $scope[0]?.querySelector(".king-addons-cpt-grid__grid");
        if (!grid) {
            return;
        }

        grid.addEventListener("click", (event) => {
            const card = event.target.closest(".king-addons-cpt-grid__card[data-card-link]");
            if (!card) {
                return;
            }
            if (event.target.closest("a")) {
                return;
            }
            const url = card.dataset.cardLink;
            if (!url) {
                return;
            }
            window.open(url, "_self");
        });
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-custom-post-types-grid.default",
            function ($scope) {
                initCptGrid($scope);
            }
        );
    });
})(jQuery);






