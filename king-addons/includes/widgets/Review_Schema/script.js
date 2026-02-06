"use strict";

(function ($) {
    const shouldShow = ($el) => {
        const device = $el.data("device") || "all";
        if (device === "all") return true;
        const isMobile = window.matchMedia("(max-width: 767px)").matches;
        if (device === "mobile") return isMobile;
        if (device === "desktop") return !isMobile;
        return true;
    };

    const initReviewSchema = ($scope) => {
        const $wrap = $scope.find(".king-addons-review");
        if (!$wrap.length) return;

        if (!shouldShow($wrap)) {
            $wrap.hide();
        }
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-review-schema.default",
            function ($scope) {
                initReviewSchema($scope);
            }
        );
    });
})(jQuery);







