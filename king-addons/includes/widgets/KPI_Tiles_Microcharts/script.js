"use strict";

(function ($) {
    const openCardLink = (card) => {
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
    };

    const initKpiTiles = ($scope) => {
        if (typeof elementorFrontend !== "undefined" && elementorFrontend.isEditMode()) {
            return;
        }

        const cards = $scope[0]?.querySelectorAll(
            ".king-addons-kpi-tiles__card[data-card-link]"
        );
        if (!cards || !cards.length) {
            return;
        }

        cards.forEach((card) => {
            card.addEventListener("click", (event) => {
                if (event.target.closest("a")) {
                    return;
                }
                openCardLink(card);
            });

            card.addEventListener("keydown", (event) => {
                if (event.key !== "Enter" && event.key !== " " && event.key !== "Spacebar") {
                    return;
                }
                event.preventDefault();
                openCardLink(card);
            });
        });
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-kpi-tiles-microcharts.default",
            function ($scope) {
                initKpiTiles($scope);
            }
        );
    });
})(jQuery);
