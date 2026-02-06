"use strict";

(function ($) {
    const rowsAreEqual = ($row) => {
        const $cells = $row.find("td");
        if ($cells.length < 2) {
            return true;
        }
        const first = $($cells.get(0)).text().trim();
        for (let i = 1; i < $cells.length; i += 1) {
            if ($($cells.get(i)).text().trim() !== first) {
                return false;
            }
        }
        return true;
    };

    const applyDifferenceFilter = ($wrapper) => {
        const hideEqual = $wrapper.data("hide-equal") === "yes";
        if (!hideEqual) {
            return;
        }
        const $rows = $wrapper.find(".king-addons-compare-table__body .king-addons-compare-table__row");
        $rows.each(function () {
            const $row = $(this);
            if (rowsAreEqual($row)) {
                $row.addClass("is-equal-hidden");
            } else {
                $row.addClass("is-different");
            }
        });
    };

    const initCompareTable = ($scope) => {
        const $wrapper = $scope.find(".king-addons-compare-table");
        if (!$wrapper.length) {
            return;
        }
        applyDifferenceFilter($wrapper);
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-compare-table.default",
            function ($scope) {
                initCompareTable($scope);
            }
        );
    });
})(jQuery);







