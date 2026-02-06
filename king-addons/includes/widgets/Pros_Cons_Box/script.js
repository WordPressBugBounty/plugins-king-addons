"use strict";

(function ($) {
    const parseNumber = (value, fallback) => {
        const number = parseInt(value, 10);
        return Number.isFinite(number) ? number : fallback;
    };

    const getStorage = () => {
        try {
            return window.localStorage;
        } catch (error) {
            return null;
        }
    };

    const initCollapse = (wrapper, dataset, isEditMode) => {
        if (dataset.collapse !== "yes") {
            return;
        }

        const collapseMode = dataset.collapseMode || "mobile";
        const remember = dataset.collapseRemember === "yes";
        const autoScroll = dataset.collapseScroll === "yes";
        const widgetId = dataset.widgetId || "";
        const storage = remember ? getStorage() : null;
        const mq = window.matchMedia("(max-width: 767px)");

        const shouldCollapse = () => {
            if (collapseMode === "always") {
                return true;
            }
            if (collapseMode === "mobile") {
                return mq.matches;
            }
            return false;
        };

        const sections = wrapper.querySelectorAll(".king-addons-pros-cons__section[data-collapsible='yes']");

        const getStorageKey = (sectionName) => {
            if (!widgetId || !sectionName) {
                return "";
            }
            return `kngProsCons:${widgetId}:${sectionName}`;
        };

        const applyState = (section, collapsed, animate) => {
            const list = section.querySelector(".king-addons-pros-cons__list");
            const toggle = section.querySelector(".king-addons-pros-cons__toggle");

            section.classList.toggle("is-collapsed", collapsed);
            toggle?.setAttribute("aria-expanded", collapsed ? "false" : "true");

            if (!list) {
                return;
            }
            list.setAttribute("aria-hidden", collapsed ? "true" : "false");

            if (!animate) {
                list.style.display = collapsed ? "none" : "";
                return;
            }

            if (collapsed) {
                $(list).stop(true, true).slideUp(180);
            } else {
                $(list)
                    .stop(true, true)
                    .slideDown(180, () => {
                        list.style.display = "";
                    });
            }
        };

        const setInitialState = () => {
            const collapse = shouldCollapse();
            sections.forEach((section) => {
                const name = section.dataset.section || "";
                let collapsed = collapse;

                if (storage) {
                    const key = getStorageKey(name);
                    if (key) {
                        const stored = storage.getItem(key);
                        if (stored === "collapsed") {
                            collapsed = true;
                        } else if (stored === "expanded") {
                            collapsed = false;
                        }
                    }
                }

                applyState(section, collapse ? collapsed : false, false);
            });
        };

        setInitialState();

        if (collapseMode === "mobile") {
            if (mq.addEventListener) {
                mq.addEventListener("change", setInitialState);
            } else if (mq.addListener) {
                mq.addListener(setInitialState);
            }
        }

        wrapper.addEventListener("click", (event) => {
            const toggle = event.target.closest(".king-addons-pros-cons__toggle");
            if (!toggle) {
                return;
            }

            const section = toggle.closest(".king-addons-pros-cons__section");
            if (!section) {
                return;
            }

            const isCollapsed = section.classList.contains("is-collapsed");
            applyState(section, !isCollapsed, true);

            if (storage) {
                const name = section.dataset.section || "";
                const key = getStorageKey(name);
                if (key) {
                    storage.setItem(key, isCollapsed ? "expanded" : "collapsed");
                }
            }

            if (isCollapsed && autoScroll && !isEditMode) {
                section.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        });
    };

    const initTooltips = (wrapper, dataset) => {
        if (dataset.tooltip !== "yes") {
            return;
        }

        const delay = parseNumber(dataset.tooltipDelay, 150);
        const timers = new WeakMap();

        const showTooltip = (item) => {
            item.classList.add("is-tooltip-visible");
        };

        const hideTooltip = (item) => {
            item.classList.remove("is-tooltip-visible");
        };

        const scheduleShow = (item) => {
            const timer = setTimeout(() => showTooltip(item), delay);
            timers.set(item, timer);
        };

        const clearTimer = (item) => {
            const timer = timers.get(item);
            if (timer) {
                clearTimeout(timer);
                timers.delete(item);
            }
        };

        wrapper.querySelectorAll(".king-addons-pros-cons__item.has-tooltip").forEach((item) => {
            item.addEventListener("mouseenter", () => scheduleShow(item));
            item.addEventListener("mouseleave", () => {
                clearTimer(item);
                hideTooltip(item);
            });
        });

        wrapper.querySelectorAll(".king-addons-pros-cons__tooltip-trigger").forEach((trigger) => {
            const item = trigger.closest(".king-addons-pros-cons__item");
            if (!item) {
                return;
            }

            trigger.addEventListener("focus", () => scheduleShow(item));
            trigger.addEventListener("blur", () => {
                clearTimer(item);
                hideTooltip(item);
            });
        });

        wrapper.addEventListener("keydown", (event) => {
            if (event.key !== "Escape") {
                return;
            }
            wrapper.querySelectorAll(".king-addons-pros-cons__item.is-tooltip-visible").forEach((item) => {
                hideTooltip(item);
            });
        });
    };

    const initProsCons = ($scope) => {
        const wrapper = $scope[0]?.querySelector(".king-addons-pros-cons");
        if (!wrapper) {
            return;
        }

        const dataset = wrapper.dataset;
        const isEditMode = elementorFrontend?.isEditMode ? elementorFrontend.isEditMode() : false;

        initCollapse(wrapper, dataset, isEditMode);
        initTooltips(wrapper, dataset);
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-pros-cons-box.default",
            function ($scope) {
                initProsCons($scope);
            }
        );
    });
})(jQuery);
