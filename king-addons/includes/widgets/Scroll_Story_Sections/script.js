"use strict";

(function ($) {
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

    const getScrollTop = () =>
        window.pageYOffset || document.documentElement.scrollTop || 0;

    const getOffsetTop = (element) => {
        const rect = element.getBoundingClientRect();
        return rect.top + getScrollTop();
    };

    const initScrollStory = ($scope) => {
        const root = $scope.find(".king-addons-scroll-story")[0];
        if (!root || root.dataset.scrollStoryInit === "yes") {
            return;
        }

        const steps = Array.from(
            root.querySelectorAll(".king-addons-scroll-story__step")
        );
        const panels = Array.from(
            root.querySelectorAll(".king-addons-scroll-story__panel")
        );
        const list = root.querySelector(".king-addons-scroll-story__steps");

        if (!steps.length || steps.length !== panels.length) {
            return;
        }

        root.dataset.scrollStoryInit = "yes";

        const settings = parseSettings(root);
        const activationOffset = clamp(
            Number(settings.activationOffset) || 40,
            10,
            80
        );
        const smoothScroll =
            settings.smoothScroll !== false &&
            settings.smoothScroll !== "no" &&
            settings.smoothScroll !== "0";
        const scrollDuration = Math.max(
            Number(settings.scrollDuration) || 600,
            0
        );

        const prefersReduced =
            window.matchMedia &&
            window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        const isEditor =
            window.elementorFrontend &&
            elementorFrontend.isEditMode &&
            elementorFrontend.isEditMode();

        let activeIndex = -1;

        const setActive = (index) => {
            const nextIndex = clamp(index, 0, steps.length - 1);
            if (nextIndex === activeIndex) {
                return;
            }

            activeIndex = nextIndex;
            root.setAttribute("data-active-index", String(activeIndex));

            steps.forEach((step, idx) => {
                const isActive = idx === activeIndex;
                step.classList.toggle("is-active", isActive);
                step.classList.toggle("is-completed", idx < activeIndex);
                step.classList.toggle("is-upcoming", idx > activeIndex);

                const button = step.querySelector(
                    ".king-addons-scroll-story__step-button"
                );
                if (button) {
                    if (isActive) {
                        button.setAttribute("aria-current", "step");
                    } else {
                        button.removeAttribute("aria-current");
                    }
                }
            });

            panels.forEach((panel, idx) => {
                const isActive = idx === activeIndex;
                panel.classList.toggle("is-active", isActive);
                panel.setAttribute("aria-hidden", isActive ? "false" : "true");
                if (isActive) {
                    panel.removeAttribute("hidden");
                } else {
                    panel.setAttribute("hidden", "hidden");
                }
            });
        };

        const getClosestIndex = () => {
            const threshold = window.innerHeight * (activationOffset / 100);
            let intersecting = null;
            let lastAbove = null;

            steps.forEach((step, idx) => {
                const rect = step.getBoundingClientRect();
                if (rect.top <= threshold && rect.bottom >= threshold) {
                    intersecting = idx;
                }
                if (rect.top <= threshold) {
                    lastAbove = idx;
                }
            });

            if (intersecting !== null) {
                return intersecting;
            }

            if (list) {
                const listRect = list.getBoundingClientRect();
                if (listRect.top >= 0 && listRect.bottom < threshold) {
                    return activeIndex >= 0 ? activeIndex : 0;
                }
            }

            if (lastAbove !== null) {
                return lastAbove;
            }

            return activeIndex >= 0 ? activeIndex : 0;
        };

        const smoothScrollTo = (targetY, duration) => {
            if (prefersReduced || duration <= 0) {
                window.scrollTo(0, targetY);
                return;
            }

            const startY = getScrollTop();
            const distance = targetY - startY;
            const startTime =
                (window.performance && performance.now()) || Date.now();

            const easeInOutQuad = (t) =>
                t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;

            const tick = (now) => {
                const currentTime =
                    (window.performance && performance.now()) || now;
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const eased = easeInOutQuad(progress);

                window.scrollTo(0, startY + distance * eased);

                if (progress < 1) {
                    window.requestAnimationFrame(tick);
                }
            };

            window.requestAnimationFrame(tick);
        };

        const scrollToStep = (index) => {
            const target = steps[index];
            if (!target) {
                return;
            }

            const offsetPx = window.innerHeight * (activationOffset / 100);
            const targetTop = Math.max(getOffsetTop(target) - offsetPx, 0);

            if (!smoothScroll || prefersReduced) {
                window.scrollTo(0, targetTop);
                return;
            }

            smoothScrollTo(targetTop, scrollDuration);
        };

        steps.forEach((step, index) => {
            const button = step.querySelector(
                ".king-addons-scroll-story__step-button"
            );
            if (!button) {
                return;
            }

            button.addEventListener("click", (event) => {
                event.preventDefault();
                setActive(index);
                if (!isEditor) {
                    scrollToStep(index);
                }
            });

            button.addEventListener("keydown", (event) => {
                if (event.key !== "Enter" && event.key !== " ") {
                    return;
                }
                event.preventDefault();
                setActive(index);
                if (!isEditor) {
                    scrollToStep(index);
                }
            });
        });

        if (isEditor) {
            setActive(0);
            return;
        }

        setActive(getClosestIndex());

        if ("IntersectionObserver" in window) {
            const observerMargin = `-${activationOffset}% 0px -${
                100 - activationOffset
            }% 0px`;
            const observer = new IntersectionObserver(
                (entries) => {
                    const shouldUpdate = entries.some(
                        (entry) => entry.isIntersecting
                    );
                    if (shouldUpdate) {
                        setActive(getClosestIndex());
                    }
                },
                {
                    rootMargin: observerMargin,
                    threshold: 0,
                }
            );

            steps.forEach((step) => observer.observe(step));
        } else {
            let ticking = false;
            const onScroll = () => {
                if (ticking) {
                    return;
                }
                ticking = true;
                window.requestAnimationFrame(() => {
                    setActive(getClosestIndex());
                    ticking = false;
                });
            };
            window.addEventListener("scroll", onScroll, { passive: true });
        }

        window.addEventListener("resize", () => {
            setActive(getClosestIndex());
        });
    };

    $(window).on("elementor/frontend/init", () => {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-scroll-story-sections.default",
            initScrollStory
        );
    });
})(jQuery);
