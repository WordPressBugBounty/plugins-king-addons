"use strict";

(function ($) {
    const initPostGrid = ($scope) => {
        const isEditMode = elementorFrontend.isEditMode();
        if (isEditMode) {
            return;
        }

        const grid = $scope[0]?.querySelector(".king-addons-post-grid__grid");
        if (!grid) {
            return;
        }

        const openCardLink = (card) => {
            const url = card.dataset.cardLink;
            if (!url) {
                return;
            }

            const target = card.dataset.cardLinkTarget || "_self";
            if (target === "_blank") {
                const newWindow = window.open(url, "_blank", "noopener");
                if (newWindow) {
                    newWindow.opener = null;
                }
                return;
            }

            window.location.href = url;
        };

        grid.addEventListener("click", (event) => {
            const card = event.target.closest(".king-addons-post-grid__card[data-card-link]");
            if (!card) {
                return;
            }
            if (event.target.closest("a")) {
                return;
            }
            openCardLink(card);
        });

        grid.addEventListener("keydown", (event) => {
            if (event.key !== "Enter" && event.key !== " " && event.key !== "Spacebar" && event.key !== "Space") {
                return;
            }

            const card = event.target.closest(".king-addons-post-grid__card[data-card-link]");
            if (!card || event.target !== card) {
                return;
            }

            event.preventDefault();
            openCardLink(card);
        });
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-quick-post-grid.default",
            function ($scope) {
                initPostGrid($scope);
            }
        );
    });
})(jQuery);





