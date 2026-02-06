"use strict";

(function ($) {
    const shouldShowForDevice = (device) => {
        if (device === "all") return true;
        const isMobile = window.matchMedia("(max-width: 767px)").matches;
        if (device === "mobile") return isMobile;
        if (device === "desktop") return !isMobile;
        return true;
    };

    const initPhoneCallButton = ($scope) => {
        const $btn = $scope.find(".king-addons-phone-call__button");
        if (!$btn.length) return;

        const device = $btn.data("device") || "all";
        if (!shouldShowForDevice(device)) {
            $btn.hide();
            return;
        }
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-phone-call-button.default",
            function ($scope) {
                initPhoneCallButton($scope);
            }
        );
    });
})(jQuery);







