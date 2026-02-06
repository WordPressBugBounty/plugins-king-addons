"use strict";

(function ($) {
    const isDeviceAllowed = (device) => {
        if (device === "desktop") {
            return window.matchMedia("(min-width: 768px)").matches;
        }
        if (device === "mobile") {
            return window.matchMedia("(max-width: 767px)").matches;
        }
        return true;
    };

    const getStoredState = (key) => {
        if (!key) {
            return null;
        }

        try {
            const raw = window.localStorage.getItem(key);
            if (!raw) {
                return null;
            }
            const parsed = JSON.parse(raw);
            if (parsed.expires && parsed.expires < Date.now()) {
                window.localStorage.removeItem(key);
                return null;
            }
            return parsed.state || null;
        } catch (e) {
            return null;
        }
    };

    const setStoredState = (key, state, hours) => {
        if (!key) {
            return;
        }

        try {
            const expires =
                hours && hours > 0
                    ? Date.now() + hours * 60 * 60 * 1000
                    : null;
            const payload = {
                state,
                expires,
            };
            window.localStorage.setItem(key, JSON.stringify(payload));
        } catch (e) {
            // Ignore storage errors (private mode).
        }
    };

    const toggleVisibility = ($widget, visible) => {
        if (visible) {
            $widget.addClass("is-visible").attr("aria-hidden", "false");
        } else {
            $widget.removeClass("is-visible").attr("aria-hidden", "true");
        }
    };

    const bindClose = ($widget, data, state, evaluate) => {
        const $close = $widget.find(".king-addons-sticky-video__close");
        if (!$close.length || data.showClose !== "yes") {
            return;
        }

        $close.on("click", (event) => {
            event.preventDefault();
            state.dismissed = true;
            $widget.addClass("is-dismissed");
            toggleVisibility($widget, false);
            if (data.persistClose === "yes" && data.persistKey) {
                setStoredState(data.persistKey, "closed", data.persistHours);
            }
            evaluate();
        });
    };

    const initStickyVideo = ($scope) => {
        const $widget = $scope.find(".king-addons-sticky-video");
        if (!$widget.length) {
            return;
        }

        const data = $widget.data();
        data.triggerScroll = Number(data.triggerScroll || 0);
        data.delayMs = Number(data.delayMs || 0);
        data.persistHours = Number(data.persistHours || 0);
        data.persistKey = data.unique
            ? `kingAddonsStickyVideo_${data.unique}`
            : "";
        data.showClose = data.showClose || "yes";
        data.persistClose = data.persistClose || "no";
        data.device = data.device || "all";

        if (!isDeviceAllowed(data.device)) {
            $widget.addClass("is-dismissed");
            return;
        }

        const savedState = getStoredState(data.persistKey);
        const state = {
            dismissed: savedState === "closed",
            delayPassed: data.delayMs <= 0,
        };

        if (state.dismissed) {
            $widget.addClass("is-dismissed");
            toggleVisibility($widget, false);
            return;
        }

        const evaluateVisibility = () => {
            if (state.dismissed) {
                toggleVisibility($widget, false);
                return;
            }

            const meetsScroll =
                data.triggerScroll <= 0 ||
                window.scrollY >= data.triggerScroll ||
                elementorFrontend.isEditMode();
            const meetsDelay = state.delayPassed || elementorFrontend.isEditMode();

            const shouldShow = meetsScroll && meetsDelay;
            toggleVisibility($widget, shouldShow);
        };

        if (data.delayMs > 0) {
            window.setTimeout(() => {
                state.delayPassed = true;
                evaluateVisibility();
            }, data.delayMs);
        }

        if (data.triggerScroll > 0 && !elementorFrontend.isEditMode()) {
            const namespace = `.kingAddonsStickyVideo.${data.unique || "default"}`;
            $(window).on(
                `scroll${namespace}`,
                () => window.requestAnimationFrame(evaluateVisibility)
            );
        }

        bindClose($widget, data, state, evaluateVisibility);
        evaluateVisibility();
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-sticky-video.default",
            function ($scope) {
                initStickyVideo($scope);
            }
        );
    });
})(jQuery);



