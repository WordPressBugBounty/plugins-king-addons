"use strict";

(function ($) {
    const parseTime = (time) => {
        if (!time || typeof time !== "string") {
            return null;
        }
        const parts = time.split(":");
        if (parts.length < 2) {
            return null;
        }
        const hours = parseInt(parts[0], 10);
        const minutes = parseInt(parts[1], 10);
        if (Number.isNaN(hours) || Number.isNaN(minutes)) {
            return null;
        }
        return hours * 60 + minutes;
    };

    const isWithinSchedule = (start, end, offsetMinutes) => {
        const startMinutes = parseTime(start);
        const endMinutes = parseTime(end);
        if (startMinutes === null || endMinutes === null) {
            return true;
        }

        const now = new Date();
        let minutes = now.getUTCHours() * 60 + now.getUTCMinutes();
        minutes += offsetMinutes;

        while (minutes < 0) {
            minutes += 1440;
        }
        minutes = minutes % 1440;

        if (startMinutes === endMinutes) {
            return true;
        }

        if (startMinutes < endMinutes) {
            return minutes >= startMinutes && minutes <= endMinutes;
        }

        return minutes >= startMinutes || minutes <= endMinutes;
    };

    const matchUtm = (key, value) => {
        if (!key || !value) {
            return false;
        }
        const params = new URLSearchParams(window.location.search);
        return params.get(key) === value;
    };

    const applyState = ($bar) => {
        const data = $bar.data();
        let state = data.statusDefault === "offline" ? "offline" : "online";

        if (matchUtm(data.utmKey, data.utmValue) && data.utmState) {
            state = data.utmState;
        }

        if (data.scheduleActive === "yes" && data.scheduleStart && data.scheduleEnd) {
            const offset = Number(data.timezoneOffset || 0) * 60;
            if (!isWithinSchedule(data.scheduleStart, data.scheduleEnd, offset)) {
                state = "offline";
            }
        }

        const onlineLabel = data.onlineLabel || "Online";
        const offlineLabel = data.offlineLabel || "Offline";

        $bar.attr("data-state", state);
        $bar.toggleClass("is-offline", state === "offline");

        const $status = $bar.find(".king-addons-sticky-contact-bar__status-text");
        if ($status.length) {
            $status.text(state === "offline" ? offlineLabel : onlineLabel);
        }

        const $note = $bar.find(".king-addons-sticky-contact-bar__note");
        if ($note.length) {
            const offlineNote = $bar.data("offlineNote");
            if (offlineNote) {
                $note.text(offlineNote);
            }
            const showNote = state === "offline" && ($note.text().trim() !== "");
            $note.toggleClass("is-visible", showNote);
            $note.attr("aria-hidden", showNote ? "false" : "true");
        }

        const isMobile = window.matchMedia("(max-width: 767px)").matches;

        $bar.find(".king-addons-sticky-contact-bar__item").each(function () {
            const availability = $(this).data("availability") || "always";
            const device = $(this).data("device") || "all";
            let visible = true;

            if (availability === "online" && state !== "online") {
                visible = false;
            }

            if (availability === "offline" && state !== "offline") {
                visible = false;
            }

            if (device === "desktop" && isMobile) {
                visible = false;
            }

            if (device === "mobile" && !isMobile) {
                visible = false;
            }

            $(this).toggleClass("is-hidden", !visible);
        });
    };

    const applyPlacement = ($bar) => {
        const offset = parseInt($bar.data("offset"), 10);
        const align = $bar.data("align") || "middle";
        const position = $bar.data("position") === "left" ? "left" : "right";
        const safeOffset = Number.isNaN(offset) ? 120 : offset;

        const css = {
            left: "auto",
            right: "auto",
            top: "",
            bottom: "",
            transform: "",
        };

        css[position] = `${safeOffset}px`;
        if (position === "left") {
            css.right = "auto";
        } else {
            css.left = "auto";
        }

        if (align === "top") {
            css.top = `${safeOffset}px`;
            css.transform = "translateY(0)";
        } else if (align === "bottom") {
            css.bottom = `${safeOffset}px`;
            css.transform = "translateY(0)";
        } else {
            css.top = "50%";
            css.transform = "translateY(-50%)";
        }

        $bar.css(css);

        if ($bar.data("showLabels") === "no") {
            $bar.addClass("no-labels");
        }
    };

    const initStickyBar = ($scope) => {
        const $bar = $scope.find(".king-addons-sticky-contact-bar");
        if (!$bar.length) {
            return;
        }

        applyPlacement($bar);
        applyState($bar);

        window.setInterval(() => {
            applyState($bar);
        }, 60000);
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-sticky-contact-bar.default",
            function ($scope) {
                initStickyBar($scope);
            }
        );
    });
})(jQuery);




