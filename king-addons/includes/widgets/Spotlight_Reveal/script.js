"use strict";

(function ($) {
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
    const globalPointerHandlers = new Set();
    let globalPointerActive = false;

    const onGlobalPointerMove = (event) => {
        globalPointerHandlers.forEach((handler) => handler(event));
    };

    const registerGlobalPointer = (handler) => {
        globalPointerHandlers.add(handler);
        if (!globalPointerActive) {
            window.addEventListener("pointermove", onGlobalPointerMove);
            globalPointerActive = true;
        }
    };

    const parseOptions = (root) => {
        if (!root || !root.dataset) {
            return {};
        }

        try {
            return JSON.parse(root.dataset.options || "{}");
        } catch (e) {
            return {};
        }
    };

    const getUnit = (value) => {
        if (!value) {
            return "";
        }
        const match = String(value).trim().match(/[a-z%]+$/i);
        return match ? match[0] : "";
    };

    const toPixels = (value, root, rect) => {
        if (!value) {
            return 0;
        }

        const number = parseFloat(value);
        if (Number.isNaN(number)) {
            return 0;
        }

        const unit = getUnit(value);
        if (unit === "vw") {
            return (window.innerWidth * number) / 100;
        }

        if (unit === "vh") {
            return (window.innerHeight * number) / 100;
        }

        if (unit === "%") {
            const basis = rect ? Math.min(rect.width, rect.height) : 0;
            return (basis * number) / 100;
        }

        if (unit === "rem") {
            const rootSize = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
            return number * rootSize;
        }

        if (unit === "em") {
            const rootSize = parseFloat(getComputedStyle(root).fontSize) || 16;
            return number * rootSize;
        }

        return number;
    };

    const getMaskRadius = (root, rect) => {
        const style = window.getComputedStyle(root);
        const sizeValue = style.getPropertyValue("--ka-spotlight-size").trim();
        const sizePx = toPixels(sizeValue, root, rect);
        return sizePx / 2;
    };

    const initSpotlightReveal = (root) => {
        if (!root || root.dataset.spotlightInit === "yes") {
            return;
        }

        root.dataset.spotlightInit = "yes";

        const options = parseOptions(root);
        const trigger = options.trigger === "scroll" ? "scroll" : "cursor";
        const hoverOnly = !(options.hoverOnly === false || options.hoverOnly === "no" || options.hoverOnly === 0);
        const constrain = !(options.constrain === false || options.constrain === "no" || options.constrain === 0);
        const smoothingInput = clamp(Number(options.smoothing) || 0, 0, 1);
        const speed = 1 - smoothingInput * 0.9;
        const startPercentX = clamp(Number(options.startX) || 50, 0, 100);
        const startPercentY = clamp(Number(options.startY) || 50, 0, 100);
        const scrollStart = clamp(Number(options.scrollStart) || 0, 0, 100);
        const scrollEnd = clamp(Number(options.scrollEnd) || 0, 0, 100);

        const isEditor =
            window.elementorFrontend &&
            elementorFrontend.isEditMode &&
            elementorFrontend.isEditMode();
        const activeTrigger = isEditor && trigger === "scroll" ? "cursor" : trigger;

        let bounds = root.getBoundingClientRect();
        let maskRadius = getMaskRadius(root, bounds);
        let startPosition = {
            x: (bounds.width * startPercentX) / 100,
            y: (bounds.height * startPercentY) / 100,
        };

        let currentX = startPosition.x;
        let currentY = startPosition.y;
        let targetX = startPosition.x;
        let targetY = startPosition.y;
        let rafId = 0;
        let lastFrame = 0;
        let isHovering = false;
        let inView = true;
        const frameInterval = isEditor ? 1000 / 30 : 0;

        const prefersReduced =
            window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

        const applyPosition = () => {
            root.style.setProperty("--ka-spotlight-x", `${currentX}px`);
            root.style.setProperty("--ka-spotlight-y", `${currentY}px`);
        };

        const refreshMetrics = () => {
            bounds = root.getBoundingClientRect();
            maskRadius = getMaskRadius(root, bounds);
            startPosition = {
                x: (bounds.width * startPercentX) / 100,
                y: (bounds.height * startPercentY) / 100,
            };
        };

        const clampPosition = (x, y) => {
            if (!constrain) {
                return { x, y };
            }

            let clampedX = x;
            let clampedY = y;
            const maxX = bounds.width - maskRadius;
            const maxY = bounds.height - maskRadius;

            if (maxX <= maskRadius) {
                clampedX = bounds.width / 2;
            } else {
                clampedX = clamp(x, maskRadius, maxX);
            }

            if (maxY <= maskRadius) {
                clampedY = bounds.height / 2;
            } else {
                clampedY = clamp(y, maskRadius, maxY);
            }

            return { x: clampedX, y: clampedY };
        };

        const updateTarget = (x, y) => {
            const clamped = clampPosition(x, y);
            targetX = clamped.x;
            targetY = clamped.y;
        };

        const animate = (timestamp) => {
            if (frameInterval && timestamp - lastFrame < frameInterval) {
                rafId = window.requestAnimationFrame(animate);
                return;
            }

            lastFrame = timestamp;

            currentX += (targetX - currentX) * speed;
            currentY += (targetY - currentY) * speed;

            if (Math.abs(targetX - currentX) < 0.2) {
                currentX = targetX;
            }

            if (Math.abs(targetY - currentY) < 0.2) {
                currentY = targetY;
            }

            applyPosition();

            if (currentX === targetX && currentY === targetY) {
                rafId = 0;
                return;
            }

            rafId = window.requestAnimationFrame(animate);
        };

        const startAnimation = () => {
            if (!rafId) {
                rafId = window.requestAnimationFrame(animate);
            }
        };

        const resetToStart = () => {
            updateTarget(startPosition.x, startPosition.y);
            startAnimation();
        };

        const updateTargetFromPointer = (clientX, clientY) => {
            const x = clientX - bounds.left;
            const y = clientY - bounds.top;
            updateTarget(x, y);
            startAnimation();
        };

        const updateTargetFromScroll = () => {
            if (!inView) {
                return;
            }

            const rect = root.getBoundingClientRect();
            bounds = rect;
            const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
            const startOffset = (viewportHeight * scrollStart) / 100;
            const endOffset = (viewportHeight * scrollEnd) / 100;
            const total = rect.height + viewportHeight - startOffset - endOffset;
            const progress = total > 0 ? (viewportHeight - rect.top - startOffset) / total : 0;
            const clamped = clamp(progress, 0, 1);

            updateTarget(rect.width * 0.5, rect.height * clamped);
            startAnimation();
        };

        const onResize = () => {
            refreshMetrics();
            if (activeTrigger === "scroll") {
                updateTargetFromScroll();
            } else if (!isHovering) {
                resetToStart();
            }
        };

        refreshMetrics();
        applyPosition();

        if (prefersReduced) {
            root.classList.add("king-addons-spotlight-reveal--active");
            return;
        }

        if (activeTrigger === "cursor") {
            const onPointerEnter = (event) => {
                if (event.pointerType === "touch") {
                    return;
                }
                isHovering = true;
                refreshMetrics();
                updateTargetFromPointer(event.clientX, event.clientY);
            };

            const onPointerMove = (event) => {
                if (event.pointerType === "touch") {
                    return;
                }
                if (hoverOnly && !isHovering) {
                    return;
                }
                updateTargetFromPointer(event.clientX, event.clientY);
            };

            const onPointerLeave = () => {
                isHovering = false;
                if (hoverOnly) {
                    resetToStart();
                }
            };

            root.addEventListener("pointerenter", onPointerEnter);
            if (hoverOnly) {
                root.addEventListener("pointermove", onPointerMove);
                root.addEventListener("pointerleave", onPointerLeave);
            } else {
                registerGlobalPointer(onPointerMove);
            }
        }

        if (activeTrigger === "scroll") {
            if (typeof IntersectionObserver !== "undefined") {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.target !== root) {
                            return;
                        }
                        inView = entry.isIntersecting;
                        if (inView) {
                            updateTargetFromScroll();
                        }
                    });
                });

                observer.observe(root);
            } else {
                inView = true;
            }

            window.addEventListener("scroll", updateTargetFromScroll, { passive: true });
            updateTargetFromScroll();
        }

        window.addEventListener("resize", onResize, { passive: true });
        root.classList.add("king-addons-spotlight-reveal--active");
    };

    const initSpotlightReveals = ($scope) => {
        $scope.find(".king-addons-spotlight-reveal").each(function () {
            initSpotlightReveal(this);
        });
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-spotlight-reveal.default",
            function ($scope) {
                initSpotlightReveals($scope);
            }
        );
    });
})(jQuery);
