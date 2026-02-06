"use strict";

(function ($) {
    const debounce = (callback, wait) => {
        let timeout;
        return (...args) => {
            window.clearTimeout(timeout);
            timeout = window.setTimeout(() => callback(...args), wait);
        };
    };

    const normalizeText = (value) => {
        if (value === undefined || value === null) {
            return "";
        }
        return value.toString().trim().toLowerCase();
    };

    const getValueKey = (row, compareMode) => {
        const type = row.dataset.valueType || "";
        const text = row.dataset.valueText || "";
        if (type === "text") {
            return compareMode === "normalized" ? normalizeText(text) : text;
        }
        return compareMode === "normalized" ? normalizeText(type) : type;
    };

    const computeDifferences = (widget) => {
        const rows = Array.from(
            widget.querySelectorAll(".king-addons-comparison-matrix-cards__feature")
        );
        if (!rows.length) {
            return;
        }

        const compareMode = widget.dataset.highlightCompare || "normalized";
        const groups = new Map();

        rows.forEach((row) => {
            const id = row.dataset.featureId || "";
            if (!id) {
                return;
            }
            if (!groups.has(id)) {
                groups.set(id, []);
            }
            groups.get(id).push(row);
        });

        groups.forEach((groupRows) => {
            const values = groupRows.map((row) => getValueKey(row, compareMode));
            const uniqueValues = Array.from(new Set(values));
            const identical = uniqueValues.length <= 1;

            groupRows.forEach((row) => {
                row.classList.toggle("is-identical", identical);
                row.classList.toggle("is-different", !identical);
            });

            if (!identical) {
                const counts = values.reduce((acc, value) => {
                    acc[value] = (acc[value] || 0) + 1;
                    return acc;
                }, {});

                groupRows.forEach((row, index) => {
                    const key = values[index];
                    row.classList.toggle("is-unique", counts[key] === 1);
                });
            } else {
                groupRows.forEach((row) => row.classList.remove("is-unique"));
            }
        });
    };

    const applyHighlightState = (widget, active) => {
        widget.classList.toggle("is-highlight-active", active);

        const mode = widget.dataset.highlightMode || "dim";
        const rows = widget.querySelectorAll(".king-addons-comparison-matrix-cards__feature");
        const hide = mode === "hide" && active;

        rows.forEach((row) => {
            const shouldHide = hide && row.classList.contains("is-identical");
            row.classList.toggle("is-highlight-hidden", shouldHide);
        });
    };

    const updateGroupVisibility = (widget) => {
        const cards = widget.querySelectorAll(".king-addons-comparison-matrix-cards__card");

        cards.forEach((card) => {
            const rows = Array.from(
                card.querySelectorAll(".king-addons-comparison-matrix-cards__feature")
            );
            const visibility = new Map();

            rows.forEach((row) => {
                const group = row.dataset.featureGroup || "";
                if (!group) {
                    return;
                }
                const hidden =
                    row.classList.contains("is-filter-hidden") ||
                    row.classList.contains("is-highlight-hidden");
                if (!hidden) {
                    visibility.set(group, true);
                }
            });

            const groupHeaders = card.querySelectorAll(
                ".king-addons-comparison-matrix-cards__feature-group"
            );
            groupHeaders.forEach((header) => {
                const group = header.dataset.featureGroup || "";
                const isVisible = visibility.get(group) === true;
                header.classList.toggle("is-hidden", !isVisible);
            });
        });
    };

    const applyFilter = (widget, value) => {
        const query = normalizeText(value);
        const includeTooltip = widget.dataset.searchTooltip === "yes";
        const rows = Array.from(
            widget.querySelectorAll(".king-addons-comparison-matrix-cards__feature")
        );
        const featureMap = new Map();

        rows.forEach((row) => {
            const id = row.dataset.featureId || "";
            if (!id || featureMap.has(id)) {
                return;
            }
            featureMap.set(id, {
                label: row.dataset.featureLabel || "",
                tooltip: row.dataset.featureTooltip || "",
            });
        });

        const visibleIds = new Set();
        featureMap.forEach((data, id) => {
            if (!query) {
                visibleIds.add(id);
                return;
            }
            const label = normalizeText(data.label);
            const tooltip = normalizeText(data.tooltip);
            if (label.includes(query) || (includeTooltip && tooltip.includes(query))) {
                visibleIds.add(id);
            }
        });

        rows.forEach((row) => {
            const id = row.dataset.featureId || "";
            if (!id) {
                row.classList.remove("is-filter-hidden");
                return;
            }
            row.classList.toggle("is-filter-hidden", !visibleIds.has(id));
        });

        updateGroupVisibility(widget);
    };

    const initScroll = (widget) => {
        const layout = widget.dataset.mobileLayout || "stack";
        if (layout !== "scroll") {
            return;
        }

        const grid = widget.querySelector(".king-addons-comparison-matrix-cards__grid");
        if (!grid || grid.hasAttribute("tabindex")) {
            return;
        }

        grid.setAttribute("tabindex", "0");
    };

    const initHighlight = (widget) => {
        if (widget.dataset.highlightEnable !== "yes") {
            return;
        }

        computeDifferences(widget);

        const toggle = widget.querySelector(
            ".king-addons-comparison-matrix-cards__highlight-input"
        );
        const defaultOn = widget.dataset.highlightDefault === "yes";

        if (toggle) {
            toggle.checked = defaultOn;
            if (!toggle.dataset.kngBound) {
                toggle.dataset.kngBound = "yes";
                toggle.addEventListener("change", () => {
                    applyHighlightState(widget, toggle.checked);
                    updateGroupVisibility(widget);
                });
            }
        }

        applyHighlightState(widget, defaultOn);
        updateGroupVisibility(widget);

        if (!widget.dataset.kngResizeBound) {
            widget.dataset.kngResizeBound = "yes";
            const handleResize = debounce(() => {
                computeDifferences(widget);
                updateGroupVisibility(widget);
            }, 150);
            window.addEventListener("resize", handleResize);
        }
    };

    const initSearch = (widget) => {
        const input = widget.querySelector(
            ".king-addons-comparison-matrix-cards__search-input"
        );
        if (!input) {
            return;
        }

        if (!input.dataset.kngBound) {
            input.dataset.kngBound = "yes";
            input.addEventListener("input", () => {
                applyFilter(widget, input.value);
            });
        }

        if (input.value) {
            applyFilter(widget, input.value);
        }
    };

    const initComparisonMatrixCards = ($scope) => {
        const widget = $scope[0]?.querySelector(".king-addons-comparison-matrix-cards");
        if (!widget) {
            return;
        }

        initScroll(widget);
        initHighlight(widget);
        initSearch(widget);
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-comparison-matrix-cards.default",
            function ($scope) {
                initComparisonMatrixCards($scope);
            }
        );
    });
})(jQuery);
