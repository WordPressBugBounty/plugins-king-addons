"use strict";

(function ($) {
    const ANIMATION_DURATION = 220;

    const parseSelectors = (value) => {
        if (!value) {
            return [];
        }

        return value
            .split(",")
            .map((item) => item.trim())
            .filter((item) => item.length > 0);
    };

    const applyVisibility = (selectors, activeClass, hiddenClass, makeVisible) => {
        if (!selectors.length) {
            return;
        }

        selectors.forEach((selector) => {
            document.querySelectorAll(selector).forEach((node) => {
                if (hiddenClass) {
                    if (makeVisible) {
                        node.classList.remove(hiddenClass);
                    } else {
                        node.classList.add(hiddenClass);
                    }
                }

                if (activeClass) {
                    if (makeVisible) {
                        node.classList.add(activeClass);
                    } else {
                        node.classList.remove(activeClass);
                    }
                }
            });
        });
    };

    const initContentToggle = ($scope) => {
        const root = $scope[0]?.querySelector(".king-addons-content-toggle");

        if (!root) {
            return;
        }

        const panes = {
            primary: root.querySelector(".king-addons-content-toggle__pane--primary"),
            secondary: root.querySelector(".king-addons-content-toggle__pane--secondary"),
        };

        const labels = {
            primary: root.querySelector(".king-addons-content-toggle__label--primary"),
            secondary: root.querySelector(".king-addons-content-toggle__label--secondary"),
        };

        const switcher = root.querySelector(".king-addons-content-toggle__switch");

        const dataset = root.dataset;
        const activeClass = dataset.activeClass || "";
        const hiddenClass = dataset.hiddenClass || "";
        const primaryTargets = parseSelectors(dataset.primaryTargets);
        const secondaryTargets = parseSelectors(dataset.secondaryTargets);
        const primaryHide = parseSelectors(dataset.primaryHide);
        const secondaryHide = parseSelectors(dataset.secondaryHide);

        let currentState = dataset.defaultState === "secondary" ? "secondary" : "primary";

        const updateExternal = (state) => {
            if (
                !activeClass &&
                !hiddenClass &&
                !primaryTargets.length &&
                !secondaryTargets.length &&
                !primaryHide.length &&
                !secondaryHide.length
            ) {
                return;
            }

            if (state === "primary") {
                applyVisibility(primaryTargets, activeClass, hiddenClass, true);
                applyVisibility(secondaryTargets, activeClass, hiddenClass, false);
                applyVisibility(primaryHide, activeClass, hiddenClass, false);
                applyVisibility(secondaryHide, activeClass, hiddenClass, true);
                return;
            }

            applyVisibility(secondaryTargets, activeClass, hiddenClass, true);
            applyVisibility(primaryTargets, activeClass, hiddenClass, false);
            applyVisibility(secondaryHide, activeClass, hiddenClass, false);
            applyVisibility(primaryHide, activeClass, hiddenClass, true);
        };

        const hidePane = (pane) => {
            if (!pane) {
                return;
            }
            pane.classList.remove("is-active");
            window.setTimeout(() => {
                pane.setAttribute("hidden", "hidden");
            }, ANIMATION_DURATION);
        };

        const showPane = (pane) => {
            if (!pane) {
                return;
            }
            pane.removeAttribute("hidden");
            window.requestAnimationFrame(() => {
                pane.classList.add("is-active");
            });
        };

        const applyState = (nextState) => {
            if (!panes[nextState]) {
                return;
            }

            if (currentState !== nextState) {
                hidePane(panes[currentState]);
                showPane(panes[nextState]);
            }

            root.classList.toggle("king-addons-content-toggle--secondary-active", nextState === "secondary");
            root.classList.toggle("king-addons-content-toggle--primary-active", nextState === "primary");

            if (switcher) {
                switcher.setAttribute("aria-checked", nextState === "secondary" ? "true" : "false");
                switcher.dataset.target = nextState === "primary" ? "secondary" : "primary";
            }

            if (labels.primary) {
                labels.primary.classList.toggle("is-active", nextState === "primary");
                labels.primary.setAttribute("aria-pressed", nextState === "primary" ? "true" : "false");
            }

            if (labels.secondary) {
                labels.secondary.classList.toggle("is-active", nextState === "secondary");
                labels.secondary.setAttribute("aria-pressed", nextState === "secondary" ? "true" : "false");
            }

            currentState = nextState;
            updateExternal(currentState);
        };

        if (switcher) {
            switcher.addEventListener("click", () => {
                applyState(currentState === "primary" ? "secondary" : "primary");
            });
        }

        if (labels.primary) {
            labels.primary.addEventListener("click", () => applyState("primary"));
        }

        if (labels.secondary) {
            labels.secondary.addEventListener("click", () => applyState("secondary"));
        }

        // Ensure external selectors are synced on initial render.
        updateExternal(currentState);
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction("frontend/element_ready/king-addons-content-toggle.default", function ($scope) {
            initContentToggle($scope);
        });
    });
})(jQuery);




