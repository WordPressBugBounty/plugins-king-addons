"use strict";

(function ($) {
    const STORAGE_KEY = "king_addons_promo_bar_closed";

    const matchConditions = (conditions) => {
        if (!conditions) {
            return true;
        }
        try {
            const parsed = JSON.parse(conditions);
            if (parsed.utms && Array.isArray(parsed.utms) && parsed.utms.length > 0) {
                const urlParams = new URLSearchParams(window.location.search);
                const utmMatch = parsed.utms.some((utm) => urlParams.get(utm.key) === utm.value);
                if (!utmMatch) {
                    return false;
                }
            }
            if (parsed.paths && Array.isArray(parsed.paths) && parsed.paths.length > 0) {
                const currentPath = window.location.pathname.replace(/\/+$/, "");
                const normalized = currentPath === "" ? "/" : currentPath;
                const allowed = parsed.paths.includes(normalized);
                if (!allowed) {
                    return false;
                }
            }
            if (parsed.schedule) {
                const now = Date.now();
                const start = parsed.schedule.start ? Date.parse(parsed.schedule.start) : null;
                const end = parsed.schedule.end ? Date.parse(parsed.schedule.end) : null;
                if ((start && now < start) || (end && now > end)) {
                    return false;
                }
            }
        } catch (e) {
            // Fail-open for malformed data.
            return true;
        }
        return true;
    };

    const initPromoBar = ($scope) => {
        const $bar = $scope.find(".king-addons-promo-bar");
        if (!$bar.length) {
            return;
        }

        if (localStorage.getItem(STORAGE_KEY) === "1") {
            $bar.addClass("is-hidden");
            return;
        }

        const conditions = $bar.data("conditions");
        if (!matchConditions(conditions)) {
            $bar.addClass("is-hidden");
            return;
        }

        const $close = $bar.find(".king-addons-promo-bar__close");
        $close.on("click", function () {
            localStorage.setItem(STORAGE_KEY, "1");
            $bar.addClass("is-hidden");
        });
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-promo-bar.default",
            function ($scope) {
                initPromoBar($scope);
            }
        );
    });
})(jQuery);







