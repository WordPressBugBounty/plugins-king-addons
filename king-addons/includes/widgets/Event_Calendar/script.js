"use strict";

(function ($) {
    const parseDate = (value) => {
        const ts = parseInt(value, 10);
        if (Number.isNaN(ts)) {
            return null;
        }
        return ts * 1000;
    };

    const matchSearch = (text, query) => {
        if (!query) return true;
        return text.toLowerCase().includes(query.toLowerCase());
    };

    const matchCategory = (category, selected) => {
        if (!selected || selected === "all") return true;
        return (category || "").toLowerCase() === selected.toLowerCase();
    };

    const matchUpcoming = (startTs) => {
        if (!startTs) return true;
        const now = Date.now();
        return startTs >= now;
    };

    const matchDateRange = (startTs, fromTs, toTs) => {
        if (!startTs) return true;
        if (fromTs && startTs < fromTs) return false;
        if (toTs && startTs > toTs) return false;
        return true;
    };

    const applyFilters = ($scope) => {
        const $wrapper = $scope.find(".king-addons-event-calendar");
        if (!$wrapper.length || $wrapper.data("has-filters") !== "yes") {
            return;
        }

        const $items = $wrapper.find(".king-addons-event-calendar__item");
        const searchVal = ($wrapper.find('[data-filter="search"]').val() || "").trim();
        const categoryVal = $wrapper.find('[data-filter="category"]').val() || "all";
        const upcomingOnly = $wrapper.find('[data-filter="upcoming"]').is(":checked");
        const fromVal = $wrapper.find('[data-filter="from"]').val();
        const toVal = $wrapper.find('[data-filter="to"]').val();

        const fromTs = fromVal ? Date.parse(fromVal) : null;
        const toTs = toVal ? Date.parse(toVal) : null;

        $items.each(function () {
            const $item = $(this);
            const start = parseDate($item.data("date-start"));
            const title = ($item.data("title") || "").toString();
            const category = ($item.data("category") || "").toString();

            const passesSearch = matchSearch(title, searchVal);
            const passesCategory = matchCategory(category, categoryVal);
            const passesUpcoming = !upcomingOnly || matchUpcoming(start);
            const passesRange = matchDateRange(start, fromTs, toTs);

            if (passesSearch && passesCategory && passesUpcoming && passesRange) {
                $item.show();
            } else {
                $item.hide();
            }
        });
    };

    const initEventCalendar = ($scope) => {
        const $wrapper = $scope.find(".king-addons-event-calendar");
        if (!$wrapper.length) {
            return;
        }

        if ($wrapper.data("has-filters") === "yes") {
            $wrapper.on("input change", "[data-filter]", function () {
                applyFilters($scope);
            });
            applyFilters($scope);
        }
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-event-calendar.default",
            function ($scope) {
                initEventCalendar($scope);
            }
        );
    });
})(jQuery);







