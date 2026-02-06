"use strict";

(() => {
    const easingMap = {
        linear: (t) => t,
        ease: (t) => 0.5 * (1 - Math.cos(Math.PI * t)),
        "ease-in": (t) => t * t,
        "ease-out": (t) => t * (2 - t),
        "ease-in-out": (t) => {
            return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
        }
    };

    const cssEasing = (name) => {
        if (["linear", "ease", "ease-in", "ease-out", "ease-in-out"].includes(name)) {
            return name;
        }
        return "ease";
    };

    const getBreakpoints = () => {
        const defaults = { md: 1025, lg: 1440 };
        const config = (window.elementorFrontend && window.elementorFrontend.config && window.elementorFrontend.config.breakpoints) || {};
        return { ...defaults, ...config };
    };

    const getCurrentDevice = () => {
        const width = window.innerWidth;
        const breakpoints = getBreakpoints();

        if (width <= (breakpoints.md || 1025)) {
            return "mobile";
        }
        if (width <= (breakpoints.lg || 1440)) {
            return "tablet";
        }
        return "desktop";
    };

    const measureFullHeight = (content) => {
        const prevHeight = content.style.height;
        const prevMaxHeight = content.style.maxHeight;
        content.style.height = "auto";
        content.style.maxHeight = "none";
        const height = content.scrollHeight;
        content.style.height = prevHeight;
        content.style.maxHeight = prevMaxHeight;
        return height;
    };

    const getFoldHeight = (content, unit) => {
        const device = getCurrentDevice();
        const key = device === "mobile" ? "foldHeightMobile" : device === "tablet" ? "foldHeightTablet" : "foldHeightDesktop";
        const value = parseFloat(content.dataset[key] || content.dataset.foldHeightDesktop || "0");
        const fullHeight = measureFullHeight(content);

        if (unit === "percent") {
            return Math.max(0, Math.min(fullHeight, (fullHeight * value) / 100));
        }
        return Math.max(0, Math.min(fullHeight, value));
    };

    const setFadeState = (fadeEl, fadeOnlyFolded, expanded, fadeHeight) => {
        if (!fadeEl) {
            return;
        }
        if (typeof fadeHeight === "number" && !Number.isNaN(fadeHeight)) {
            fadeEl.style.setProperty("--ka-unfold-fade-height", `${fadeHeight}px`);
            fadeEl.style.height = `${fadeHeight}px`;
        }
        if (fadeOnlyFolded && expanded) {
            fadeEl.classList.add("king-addons-unfold__fade--hidden");
        } else {
            fadeEl.classList.remove("king-addons-unfold__fade--hidden");
        }
    };

    const animateHeight = (content, from, to, duration, easing, type, onComplete) => {
        const resolvedDuration = Math.max(0, duration);
        const easeFn = easingMap[easing] || easingMap.ease;

        if (resolvedDuration === 0) {
            content.style.height = "";
            content.style.maxHeight = `${to}px`;
            if (onComplete) {
                onComplete();
            }
            return;
        }

        if (type === "max-height") {
            content.style.transition = `max-height ${resolvedDuration}s ${cssEasing(easing)}`;
            requestAnimationFrame(() => {
                content.style.maxHeight = `${to}px`;
            });
            setTimeout(() => {
                content.style.transition = "";
                if (onComplete) {
                    onComplete();
                }
            }, resolvedDuration * 1000);
            return;
        }

        let start = null;
        const step = (timestamp) => {
            if (!start) {
                start = timestamp;
            }
            const progress = Math.min((timestamp - start) / (resolvedDuration * 1000), 1);
            const eased = easeFn(progress);
            const current = from + (to - from) * eased;
            content.style.height = `${current}px`;
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                content.style.height = "";
                content.style.maxHeight = `${to}px`;
                if (onComplete) {
                    onComplete();
                }
            }
        };
        requestAnimationFrame(step);
    };

    const updateButtonState = (button, expanded) => {
        if (!button) {
            return;
        }
        const textEl = button.querySelector(".king-addons-unfold__text");
        const iconUnfold = button.querySelector(".king-addons-unfold__icon--unfold");
        const iconFold = button.querySelector(".king-addons-unfold__icon--fold");

        if (textEl) {
            textEl.textContent = expanded ? button.dataset.foldText : button.dataset.unfoldText;
        }

        if (iconUnfold && iconFold) {
            if (expanded) {
                iconUnfold.setAttribute("aria-hidden", "true");
                iconUnfold.classList.add("king-addons-unfold__icon--hidden");
                iconFold.setAttribute("aria-hidden", "false");
                iconFold.classList.remove("king-addons-unfold__icon--hidden");
            } else {
                iconFold.setAttribute("aria-hidden", "true");
                iconFold.classList.add("king-addons-unfold__icon--hidden");
                iconUnfold.setAttribute("aria-hidden", "false");
                iconUnfold.classList.remove("king-addons-unfold__icon--hidden");
            }
        }

        button.setAttribute("aria-expanded", expanded ? "true" : "false");
    };

    const applyInitialState = (wrapper, content, fadeEl, button) => {
        const unit = wrapper.dataset.foldUnit || "percent";
        const initialState = wrapper.dataset.initialState === "unfolded" ? "unfolded" : "folded";
        const fadeOnlyFolded = wrapper.dataset.fadeOnlyFolded === "true";
        const fadeHeight = parseFloat(wrapper.dataset.fadeHeight || "30");
        const fullHeight = measureFullHeight(content);
        const foldHeight = getFoldHeight(content, unit);
        const expanded = initialState === "unfolded";

        content.style.overflow = "hidden";
        if (initialState === "folded") {
            content.style.height = "";
            content.style.maxHeight = `${foldHeight}px`;
        } else {
            content.style.height = "";
            content.style.maxHeight = `${fullHeight}px`;
        }

        wrapper.classList.toggle("king-addons-unfold--folded", !expanded);
        wrapper.classList.toggle("king-addons-unfold--unfolded", expanded);
        content.setAttribute("aria-hidden", expanded ? "false" : "true");

        if (button) {
            updateButtonState(button, expanded);
        }
        setFadeState(fadeEl, fadeOnlyFolded, expanded, fadeHeight);
    };

    const scrollIntoView = (wrapper, offset) => {
        const top = wrapper.getBoundingClientRect().top + window.scrollY - offset;
        window.scrollTo({
            top,
            behavior: "smooth"
        });
    };

    const initInstance = (wrapper) => {
        const content = wrapper.querySelector(".king-addons-unfold__content");
        if (!content) {
            return;
        }
        const fadeEl = wrapper.querySelector(".king-addons-unfold__fade");
        const button = wrapper.querySelector(".king-addons-unfold__button");

        const unit = wrapper.dataset.foldUnit || "percent";
        const fadeOnlyFolded = wrapper.dataset.fadeOnlyFolded === "true";
        const fadeHeight = parseFloat(wrapper.dataset.fadeHeight || "30");
        const animateType = wrapper.dataset.animateType || "auto-to-fixed";
        const scrollAfter = wrapper.dataset.scrollAfter === "true";
        const scrollOffset = parseInt(wrapper.dataset.scrollOffset || "0", 10) || 0;
        const foldDuration = parseFloat(wrapper.dataset.foldDuration || "0.5");
        const unfoldDuration = parseFloat(wrapper.dataset.unfoldDuration || "0.5");
        const foldEasing = wrapper.dataset.foldEasing || "ease";
        const unfoldEasing = wrapper.dataset.unfoldEasing || "ease";

        applyInitialState(wrapper, content, fadeEl, button);

        const toggle = (expand) => {
            const fullHeight = measureFullHeight(content);
            const foldHeight = getFoldHeight(content, unit);
            const targetHeight = expand ? fullHeight : foldHeight;
            const currentHeight = content.getBoundingClientRect().height;
            const duration = expand ? unfoldDuration : foldDuration;
            const easing = expand ? unfoldEasing : foldEasing;

            content.style.overflow = "hidden";

            animateHeight(
                content,
                currentHeight,
                targetHeight,
                duration,
                easing,
                animateType,
                () => {
                    wrapper.classList.toggle("king-addons-unfold--folded", !expand);
                    wrapper.classList.toggle("king-addons-unfold--unfolded", expand);
                    content.setAttribute("aria-hidden", expand ? "false" : "true");
                    setFadeState(fadeEl, fadeOnlyFolded, expand, fadeHeight);
                    updateButtonState(button, expand);
                    if (expand && scrollAfter) {
                        scrollIntoView(wrapper, scrollOffset);
                    }
                }
            );
        };

        if (button) {
            button.addEventListener("click", () => {
                const isExpanded = wrapper.classList.contains("king-addons-unfold--unfolded");
                toggle(!isExpanded);
            });
        }

        let resizeTimeout = null;
        const handleResize = () => {
            const isExpanded = wrapper.classList.contains("king-addons-unfold--unfolded");
            const fullHeight = measureFullHeight(content);
            const foldHeight = getFoldHeight(content, unit);
            const target = isExpanded ? fullHeight : foldHeight;
            content.style.transition = "";
            content.style.maxHeight = `${target}px`;
            setFadeState(fadeEl, fadeOnlyFolded, isExpanded, fadeHeight);
        };

        window.addEventListener("resize", () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(handleResize, 150);
        });
    };

    const initScope = (scope) => {
        const root = scope || document;
        root.querySelectorAll(".king-addons-unfold").forEach((wrapper) => {
            initInstance(wrapper);
        });
    };

    document.addEventListener("DOMContentLoaded", () => {
        initScope(document);
    });

    if (window.elementorFrontend) {
        window.elementorFrontend.hooks.addAction("frontend/element_ready/king-addons-unfold.default", ($scope) => {
            const scopeEl = $scope && $scope[0] ? $scope[0] : $scope;
            initScope(scopeEl);
        });
    }
})();






