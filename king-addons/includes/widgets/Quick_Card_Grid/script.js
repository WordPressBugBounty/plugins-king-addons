"use strict";

(function ($) {
    const initCardGrid = ($scope) => {
        const isEditMode = elementorFrontend.isEditMode();
        if (isEditMode) {
            return;
        }

        const grid = $scope[0]?.querySelector(".king-addons-card-grid__grid");
        if (!grid) {
            return;
        }

        grid.addEventListener("click", (event) => {
            const card = event.target.closest(".king-addons-card-grid__card[data-card-link]");
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
            const target = card.dataset.cardLinkTarget || "_self";
            const rel = card.dataset.cardLinkRel || "";
            const anchor = document.createElement("a");
            anchor.href = url;
            anchor.target = target;
            if (rel) {
                anchor.rel = rel;
            }
            anchor.style.display = "none";
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
        });
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-quick-card-grid.default",
            function ($scope) {
                initCardGrid($scope);
            }
        );
    });
})(jQuery);







