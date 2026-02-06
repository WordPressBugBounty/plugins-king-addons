"use strict";
(($) => {
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const parseSettings = (root) => {
        if (!root || !root.dataset) {
            return {};
        }

        try {
            return JSON.parse(root.dataset.settings || "{}");
        } catch (e) {
            return {};
        }
    };

    const initWidget = ($scope) => {
        const root = $scope.find(".king-addons-interactive-steps").first()[0];
        if (!root || root.dataset.kngIspInit === "yes") {
            return;
        }

        root.dataset.kngIspInit = "yes";
        const options = parseSettings(root);

        const steps = Array.from(root.querySelectorAll(".king-addons-isp-step"));
        if (!steps.length) {
            return;
        }

        const progressCount = root.querySelector(".king-addons-isp-progress__count");
        const progressPercent = root.querySelector(".king-addons-isp-progress__percent");
        const progressFill = root.querySelector(".king-addons-isp-progress__fill");
        const stickySteps = root.querySelector(".king-addons-isp-progress__steps");
        const isStickyHeader = root.classList.contains("king-addons-isp-sticky-header");
        let stickyStepItems = [];
        const prefersReduced =
            window.matchMedia &&
            window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        const isEditor =
            window.elementorFrontend &&
            elementorFrontend.isEditMode &&
            elementorFrontend.isEditMode();

        const reducedMode = options.reducedMotion || "auto";
        const reducedFull =
            reducedMode === "full" ||
            (prefersReduced && reducedMode !== "off" && reducedMode !== "minimal");
        const reducedMinimal =
            reducedMode === "minimal" ||
            (prefersReduced && reducedMode === "auto");

        if (reducedFull) {
            root.classList.add("king-addons-isp-reduced-full");
        } else if (reducedMinimal) {
            root.classList.add("king-addons-isp-reduced-minimal");
        }

        const expandBehavior = options.expandBehavior || "click";
        const defaultOpen = options.defaultOpen || "first";
        const singleOpen = options.singleOpen !== "no";
        const scrollToStep = options.scrollToStep === "yes";
        const markersClickable = options.markersClickable !== "no";
        const showStepCount = options.showStepCount === "yes";
        const showPercent = options.showPercent === "yes";
        const stepLabelTemplate = options.stepLabel || "Step %1$s of %2$s";
        const updateHash = options.updateHash === "yes";
        const scrollToHash = options.scrollToHash === "yes";
        const anchorLinking = options.anchorLinking === "yes";
        const keyboardNav = options.keyboardNav === "yes";
        const activateOnScroll = !isEditor && options.activateOnScroll === "yes";
        const reveal = options.reveal === "yes" && !isEditor;

        const getPanel = (step) => step.querySelector(".king-addons-isp-step__panel");
        const getButton = (step) => step.querySelector(".king-addons-isp-step__button");
        const getAnchor = (step) => step.dataset.anchor || "";

        const setExpanded = (step, isOpen) => {
            const panel = getPanel(step);
            const button = getButton(step);

            if (!panel || !button) {
                return;
            }

            step.classList.toggle("is-open", isOpen);
            button.setAttribute("aria-expanded", isOpen ? "true" : "false");
            panel.setAttribute("aria-hidden", isOpen ? "false" : "true");
            if (isOpen) {
                panel.removeAttribute("hidden");
            } else {
                panel.setAttribute("hidden", "hidden");
            }
        };

        const closeAll = () => {
            steps.forEach((step) => setExpanded(step, false));
        };

        const openAll = () => {
            steps.forEach((step) => setExpanded(step, true));
        };

        const applyDefaults = () => {
            if (expandBehavior === "none") {
                openAll();
                return;
            }

            closeAll();
            if (defaultOpen === "first") {
                const firstStep = steps[0];
                if (firstStep && getPanel(firstStep)) {
                    setExpanded(firstStep, true);
                }
            }
        };

        const formatStepLabel = (current, total) =>
            stepLabelTemplate
                .replace("%1$s", current)
                .replace("%2$s", total);

        const updateProgress = (activeIndex) => {
            const total = steps.length;
            const ratio = total > 1 ? activeIndex / (total - 1) : 1;
            const percentValue = Math.round(ratio * 100);

            root.style.setProperty("--kng-isp-progress-ratio", ratio);
            root.style.setProperty("--kng-isp-progress-percent", `${percentValue}%`);

            if (progressFill) {
                progressFill.style.width = `${percentValue}%`;
            }

            if (progressCount && showStepCount) {
                progressCount.textContent = formatStepLabel(
                    activeIndex + 1,
                    total
                );
            }

            if (progressPercent && showPercent) {
                progressPercent.textContent = `${percentValue}%`;
            }
        };

        const setActive = (activeIndex, shouldUpdateHash) => {
            steps.forEach((step, index) => {
                step.classList.toggle("is-active", index === activeIndex);
                step.classList.toggle("is-completed", index < activeIndex);
                step.classList.toggle("is-upcoming", index > activeIndex);

                if (index === activeIndex) {
                    step.setAttribute("aria-current", "step");
                } else {
                    step.removeAttribute("aria-current");
                }
            });

            updateProgress(activeIndex);

            if (stickyStepItems.length) {
                stickyStepItems.forEach((item, index) => {
                    item.classList.toggle("is-active", index === activeIndex);
                    item.classList.toggle("is-completed", index < activeIndex);

                    if (index === activeIndex) {
                        item.setAttribute("aria-current", "step");
                    } else {
                        item.removeAttribute("aria-current");
                    }
                });
            }

            if (shouldUpdateHash && updateHash && anchorLinking && !isEditor) {
                const anchor = getAnchor(steps[activeIndex]);
                if (anchor) {
                    try {
                        window.history.replaceState(null, "", `#${anchor}`);
                    } catch (e) {
                        window.location.hash = anchor;
                    }
                }
            }
        };

        const activateStep = (index, openPanel = true, shouldScroll = true) => {
            const step = steps[index];
            if (!step) {
                return;
            }

            const hasPanel = !!getPanel(step);
            if (expandBehavior === "click" && hasPanel && openPanel) {
                if (singleOpen) {
                    closeAll();
                    setExpanded(step, true);
                } else {
                    setExpanded(step, !step.classList.contains("is-open"));
                }
            }

            setActive(index, true);

            if (scrollToStep && shouldScroll && !isEditor) {
                step.scrollIntoView({
                    behavior: reducedFull ? "auto" : "smooth",
                    block: "start",
                });
            }
        };

        applyDefaults();

        if (stickySteps && isStickyHeader) {
            stickySteps.innerHTML = "";
            stickyStepItems = steps.map((_, index) => {
                const item = document.createElement("button");
                item.type = "button";
                item.className = "king-addons-isp-progress-step";
                item.dataset.stepIndex = String(index);
                item.setAttribute(
                    "aria-label",
                    formatStepLabel(index + 1, steps.length)
                );
                stickySteps.appendChild(item);
                return item;
            });
        }

        const initialActive = Math.max(
            0,
            steps.findIndex((step) => step.classList.contains("is-active"))
        );
        setActive(initialActive, false);

        root.addEventListener("click", (event) => {
            const button = event.target.closest(".king-addons-isp-step__button");
            if (button) {
                const step = button.closest(".king-addons-isp-step");
                const index = steps.indexOf(step);
                if (index > -1) {
                    activateStep(index, true, true);
                }
                return;
            }

            if (markersClickable) {
                const stickyDot = event.target.closest(".king-addons-isp-progress-step");
                if (stickyDot && stickyDot.dataset && stickyDot.dataset.stepIndex) {
                    const index = Number(stickyDot.dataset.stepIndex);
                    if (!Number.isNaN(index)) {
                        activateStep(clamp(index, 0, steps.length - 1), true, true);
                    }
                    return;
                }
            }

            if (markersClickable) {
                const marker = event.target.closest(".king-addons-isp-step__marker");
                if (marker) {
                    const step = marker.closest(".king-addons-isp-step");
                    const index = steps.indexOf(step);
                    if (index > -1) {
                        activateStep(index, true, true);
                    }
                }
            }
        });

        const isHorizontalLayout = () => {
            if (!root.classList.contains("king-addons-isp-layout-horizontal")) {
                return false;
            }

            if (
                root.classList.contains("king-addons-isp-mobile-vertical") &&
                window.innerWidth <= 767
            ) {
                return false;
            }

            return true;
        };

        if (keyboardNav) {
            root.addEventListener("keydown", (event) => {
                if (!event.target.closest(".king-addons-isp-step__button")) {
                    return;
                }

                if (!["ArrowDown", "ArrowUp", "ArrowLeft", "ArrowRight"].includes(event.key)) {
                    return;
                }

                const step = event.target.closest(".king-addons-isp-step");
                const index = steps.indexOf(step);
                if (index < 0) {
                    return;
                }

                const isHorizontal = isHorizontalLayout();
                const stepDelta =
                    event.key === "ArrowDown" || event.key === "ArrowRight" ? 1 : -1;

                if (
                    (isHorizontal && (event.key === "ArrowLeft" || event.key === "ArrowRight")) ||
                    (!isHorizontal && (event.key === "ArrowDown" || event.key === "ArrowUp"))
                ) {
                    event.preventDefault();
                    const nextIndex = clamp(index + stepDelta, 0, steps.length - 1);
                    const nextButton = getButton(steps[nextIndex]);
                    if (nextButton) {
                        nextButton.focus();
                    }
                    activateStep(nextIndex, true, true);
                }
            });
        }

        if (anchorLinking && scrollToHash && window.location.hash && !isEditor) {
            const hash = window.location.hash.replace("#", "");
            const targetIndex = steps.findIndex(
                (step) => getAnchor(step) === hash
            );
            if (targetIndex > -1) {
                setTimeout(() => {
                    activateStep(targetIndex, true, false);
                    steps[targetIndex].scrollIntoView({
                        behavior: reducedFull ? "auto" : "smooth",
                        block: "start",
                    });
                }, 150);
            }
        }

        if (reveal) {
            const revealType = options.revealType || "fade";
            root.classList.add(`king-addons-isp-reveal-${revealType}`);

            steps.forEach((step, idx) => {
                step.classList.add("is-reveal");
                if (options.revealStagger && !reducedFull) {
                    step.style.transitionDelay = `${idx * options.revealStagger}ms`;
                }
            });

            if (reducedFull) {
                steps.forEach((step) => step.classList.add("is-revealed"));
            } else if ("IntersectionObserver" in window) {
                const revealObserver = new IntersectionObserver(
                    (entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add("is-revealed");
                                revealObserver.unobserve(entry.target);
                            }
                        });
                    },
                    { threshold: 0.2 }
                );

                steps.forEach((step) => revealObserver.observe(step));
            } else {
                steps.forEach((step) => step.classList.add("is-revealed"));
            }
        }

        if (activateOnScroll && "IntersectionObserver" in window) {
            const offset = clamp(Number(options.activationOffset) || 40, 10, 80);
            const rootMargin = `-${offset}% 0px -${100 - offset}% 0px`;

            const observer = new IntersectionObserver(
                (entries) => {
                    const visible = entries
                        .filter((entry) => entry.isIntersecting)
                        .sort(
                            (a, b) =>
                                b.intersectionRatio - a.intersectionRatio
                        );

                    if (!visible.length) {
                        return;
                    }

                    const index = steps.indexOf(visible[0].target);
                    if (index > -1) {
                        activateStep(index, true, false);
                    }
                },
                {
                    root: null,
                    rootMargin,
                    threshold: [0, 0.25, 0.5, 0.75, 1],
                }
            );

            steps.forEach((step) => observer.observe(step));
        }
    };

    $(window).on("elementor/frontend/init", () => {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-interactive-steps-progress.default",
            ($scope) => {
                elementorFrontend.elementsHandler.addHandler(
                    elementorModules.frontend.handlers.Base.extend({
                        onInit: function () {
                            initWidget(this.$element);
                        },
                    }),
                    { $element: $scope }
                );
            }
        );
    });
})(jQuery);
