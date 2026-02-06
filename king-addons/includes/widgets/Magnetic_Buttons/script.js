"use strict";

(function ($) {
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

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

    const getCurve = (preset) => {
        switch (preset) {
            case "linear":
                return (t) => t;
            case "ease-out":
                return (t) => 1 - Math.pow(1 - t, 3);
            case "gaussian":
                return (t) => {
                    const raw = Math.exp(-Math.pow(1 - t, 2) * 4);
                    const min = Math.exp(-4);
                    return (raw - min) / (1 - min);
                };
            case "snappy":
                return (t) => 1 - Math.pow(1 - t, 3);
            case "calm":
                return (t) => t * t;
            case "smooth":
            default:
                return (t) => 1 - Math.pow(1 - t, 2);
        }
    };

    const isEnabled = (value, fallback = false) => {
        if (value === undefined || value === null || value === "") {
            return fallback;
        }
        if (value === true || value === "yes" || value === "1" || value === 1) {
            return true;
        }
        if (value === false || value === "no" || value === "0" || value === 0) {
            return false;
        }
        return fallback;
    };

    const normalizeOptions = (raw = {}) => {
        return {
            enabled: isEnabled(raw.enabled, true),
            strength: clamp(Number(raw.strength) || 0, 0, 100),
            radius: Math.max(Number(raw.radius) || 0, 0),
            maxOffset: Math.max(Number(raw.maxOffset) || 0, 0),
            returnSpeed: clamp(Number(raw.returnSpeed) || 0, 0, 100),
            curve: raw.curve || raw.easing || "smooth",
            axisX: isEnabled(raw.axisX, true),
            axisY: isEnabled(raw.axisY, true),
            damping: clamp(Number(raw.damping) || 0, 0, 1),
            triggerMode: raw.triggerMode || "bounds",
            triggerPadding: Math.max(Number(raw.triggerPadding) || 0, 0),
            boundary: raw.boundary || "none",
            boundaryWidth: Math.max(Number(raw.boundaryWidth) || 0, 0),
            boundaryHeight: Math.max(Number(raw.boundaryHeight) || 0, 0),
            edgeResistance: clamp(Number(raw.edgeResistance) || 0, 0, 100),
            overshoot: isEnabled(raw.overshoot, false),
            innerEnabled: isEnabled(raw.innerEnabled, false),
            innerStrength: clamp(Number(raw.innerStrength) || 0, 0, 100),
            innerMaxOffset: Math.max(Number(raw.innerMaxOffset) || 0, 0),
            reducedMotionMode: raw.reducedMotionMode || "respect",
            touchBehavior: raw.touchBehavior || "off",
            editorPreview: isEnabled(raw.editorPreview, true),
            pauseOffscreen: isEnabled(raw.pauseOffscreen, false),
            selector: typeof raw.selector === "string" ? raw.selector.trim() : "",
            innerSelector: typeof raw.innerSelector === "string" ? raw.innerSelector.trim() : "",
        };
    };

    const resolveTargets = (root, options) => {
        const targetType = root.dataset.target || "button";
        const selectorMap = {
            button: ".king-addons-magnetic-buttons__button",
            card: ".king-addons-magnetic-buttons__card",
            icon: ".king-addons-magnetic-buttons__icon-target",
        };

        if (targetType === "selector") {
            const selector = options.selector || root.dataset.selector || "";
            if (!selector) {
                return [];
            }

            let targets = Array.from(root.querySelectorAll(selector));
            if (!targets.length) {
                targets = Array.from(document.querySelectorAll(selector));
            }
            return targets;
        }

        const selector = selectorMap[targetType] || selectorMap.button;
        return Array.from(root.querySelectorAll(selector));
    };

    const initMagneticTarget = (root, target, targetType, options) => {
        if (!target) {
            return;
        }

        if (target.__kaMagneticInstance && typeof target.__kaMagneticInstance.destroy === "function") {
            target.__kaMagneticInstance.destroy();
        }

        if (!options.enabled) {
            return;
        }

        const isEditor =
            window.elementorFrontend &&
            elementorFrontend.isEditMode &&
            elementorFrontend.isEditMode();
        if (isEditor && !options.editorPreview) {
            return;
        }

        const prefersReduced =
            window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        if (options.reducedMotionMode === "disable") {
            return;
        }

        let motionFactor = 1;
        if (prefersReduced) {
            if (options.reducedMotionMode === "respect") {
                return;
            }
            if (options.reducedMotionMode === "minimal") {
                motionFactor *= 0.4;
            }
        }

        const isCoarsePointer =
            window.matchMedia && window.matchMedia("(hover: none), (pointer: coarse)").matches;
        const allowTouch = options.touchBehavior !== "off";
        const isTouchReduced = options.touchBehavior === "reduced";
        if (isCoarsePointer && !allowTouch) {
            return;
        }
        if (isCoarsePointer && isTouchReduced) {
            motionFactor *= 0.6;
        }

        const strength = clamp((options.strength / 100) * motionFactor, 0, 1);
        const radius = Math.max(options.radius, 0);
        const maxOffset = Math.max(options.maxOffset * motionFactor, 0);
        const returnSpeed = clamp(options.returnSpeed / 100, 0, 1);

        if (!strength || !radius || !maxOffset) {
            return;
        }

        target.dataset.magneticInit = "yes";

        const curve = getCurve(options.curve);
        const edgeResistance = clamp(options.edgeResistance / 100, 0, 1);
        const damping = clamp(options.damping, 0, 1);
        const speed = clamp(0.06 + returnSpeed * 0.24, 0.05, 0.35);
        const lerpSpeed = clamp(speed * (1 - damping * 0.6) + 0.02, 0.02, 0.4);
        const springStiffness = clamp(0.04 + returnSpeed * 0.2, 0.04, 0.3);
        const springDamping = clamp(0.12 + damping * 0.5, 0.12, 0.8);

        const innerSelectors = {
            button: ".king-addons-magnetic-buttons__content",
            card: ".king-addons-magnetic-buttons__card-inner",
            icon: ".king-addons-magnetic-buttons__icon-inner",
        };

        const innerSelector = options.innerSelector || innerSelectors[targetType] || "";
        const innerTarget = options.innerEnabled && innerSelector ? target.querySelector(innerSelector) : null;
        const innerStrength = clamp(options.innerStrength / 100, 0, 1);
        const innerMaxOffset = Math.max(options.innerMaxOffset, 0);

        const frameInterval = isEditor ? 1000 / 30 : 0;

        let currentX = 0;
        let currentY = 0;
        let targetX = 0;
        let targetY = 0;
        let innerX = 0;
        let innerY = 0;
        let targetInnerX = 0;
        let targetInnerY = 0;
        let velocityX = 0;
        let velocityY = 0;
        let innerVelocityX = 0;
        let innerVelocityY = 0;
        let rafId = 0;
        let lastFrame = 0;
        let isHovering = false;
        let isPressed = false;
        let isVisible = true;

        const applyTransform = () => {
            target.style.transform = `translate3d(${currentX}px, ${currentY}px, 0)`;
            if (innerTarget) {
                innerTarget.style.transform = `translate3d(${innerX}px, ${innerY}px, 0)`;
            }
        };

        const resetTargets = () => {
            targetX = 0;
            targetY = 0;
            targetInnerX = 0;
            targetInnerY = 0;
        };

        const getBoundaryRect = (rect) => {
            if (options.boundary === "parent") {
                const parent = target.parentElement || root;
                return parent ? parent.getBoundingClientRect() : null;
            }

            if (options.boundary === "custom") {
                const width = options.boundaryWidth;
                const height = options.boundaryHeight;
                if (!width || !height) {
                    return null;
                }

                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                return {
                    left: centerX - width / 2,
                    right: centerX + width / 2,
                    top: centerY - height / 2,
                    bottom: centerY + height / 2,
                };
            }

            return null;
        };

        const applyBoundaryClamp = (rect) => {
            if (options.boundary === "none") {
                return;
            }

            const boundary = getBoundaryRect(rect);
            if (!boundary) {
                return;
            }

            const minX = boundary.left - rect.left;
            const maxX = boundary.right - rect.right;
            const minY = boundary.top - rect.top;
            const maxY = boundary.bottom - rect.bottom;

            targetX = clamp(targetX, Math.min(minX, maxX), Math.max(minX, maxX));
            targetY = clamp(targetY, Math.min(minY, maxY), Math.max(minY, maxY));
        };

        const updateTarget = (clientX, clientY) => {
            const rect = target.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            let dx = clientX - centerX;
            let dy = clientY - centerY;

            if (!options.axisX) {
                dx = 0;
            }
            if (!options.axisY) {
                dy = 0;
            }

            const distance = Math.sqrt(dx * dx + dy * dy);
            if (!radius || distance > radius) {
                resetTargets();
                return;
            }

            const closeness = 1 - clamp(distance / radius, 0, 1);
            const edgeFactor = 1 - edgeResistance * (1 - closeness);
            const pull = curve(closeness) * strength * clamp(edgeFactor, 0, 1);
            const limitedOffset = Math.min(maxOffset, radius);

            targetX = clamp(dx * pull, -limitedOffset, limitedOffset);
            targetY = clamp(dy * pull, -limitedOffset, limitedOffset);

            applyBoundaryClamp(rect);

            if (innerTarget && innerStrength && innerMaxOffset) {
                const innerPull = curve(closeness) * innerStrength;
                const innerLimited = Math.min(innerMaxOffset, radius);
                targetInnerX = clamp(dx * innerPull, -innerLimited, innerLimited);
                targetInnerY = clamp(dy * innerPull, -innerLimited, innerLimited);
            }
        };

        const isWithinTriggerArea = (clientX, clientY) => {
            const rect = target.getBoundingClientRect();

            if (options.triggerMode === "radius") {
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                const dx = clientX - centerX;
                const dy = clientY - centerY;
                return Math.sqrt(dx * dx + dy * dy) <= radius;
            }

            if (options.triggerMode === "padding") {
                const padding = options.triggerPadding;
                return (
                    clientX >= rect.left - padding &&
                    clientX <= rect.right + padding &&
                    clientY >= rect.top - padding &&
                    clientY <= rect.bottom + padding
                );
            }

            return (
                clientX >= rect.left &&
                clientX <= rect.right &&
                clientY >= rect.top &&
                clientY <= rect.bottom
            );
        };

        const animate = (timestamp) => {
            if (frameInterval && timestamp - lastFrame < frameInterval) {
                rafId = window.requestAnimationFrame(animate);
                return;
            }

            lastFrame = timestamp;

            if (options.overshoot) {
                velocityX += (targetX - currentX) * springStiffness;
                velocityY += (targetY - currentY) * springStiffness;
                velocityX *= 1 - springDamping;
                velocityY *= 1 - springDamping;
                currentX += velocityX;
                currentY += velocityY;
            } else {
                currentX += (targetX - currentX) * lerpSpeed;
                currentY += (targetY - currentY) * lerpSpeed;
            }

            if (innerTarget) {
                if (options.overshoot) {
                    innerVelocityX += (targetInnerX - innerX) * springStiffness;
                    innerVelocityY += (targetInnerY - innerY) * springStiffness;
                    innerVelocityX *= 1 - springDamping;
                    innerVelocityY *= 1 - springDamping;
                    innerX += innerVelocityX;
                    innerY += innerVelocityY;
                } else {
                    innerX += (targetInnerX - innerX) * lerpSpeed;
                    innerY += (targetInnerY - innerY) * lerpSpeed;
                }
            }

            if (Math.abs(targetX - currentX) < 0.05) {
                currentX = targetX;
            }
            if (Math.abs(targetY - currentY) < 0.05) {
                currentY = targetY;
            }

            if (innerTarget) {
                if (Math.abs(targetInnerX - innerX) < 0.05) {
                    innerX = targetInnerX;
                }
                if (Math.abs(targetInnerY - innerY) < 0.05) {
                    innerY = targetInnerY;
                }
            }

            applyTransform();

            const isSettled =
                !isHovering &&
                !isPressed &&
                currentX === 0 &&
                currentY === 0 &&
                (!innerTarget || (innerX === 0 && innerY === 0));

            if (isSettled) {
                rafId = 0;
                return;
            }

            rafId = window.requestAnimationFrame(animate);
        };

        const start = () => {
            if (!rafId) {
                rafId = window.requestAnimationFrame(animate);
            }
        };

        const onPointerEnter = (event) => {
            if (!isVisible) {
                return;
            }
            if (event.pointerType === "touch" && !allowTouch) {
                return;
            }
            isHovering = true;
            updateTarget(event.clientX, event.clientY);
            start();
        };

        const onPointerMove = (event) => {
            if (!isVisible || isPressed) {
                return;
            }
            if (event.pointerType === "touch" && !allowTouch) {
                return;
            }
            updateTarget(event.clientX, event.clientY);
            start();
        };

        const onPointerLeave = () => {
            isHovering = false;
            resetTargets();
            start();
        };

        const onPointerMoveWindow = (event) => {
            if (!isVisible || isPressed) {
                return;
            }
            if (event.pointerType === "touch" && !allowTouch) {
                return;
            }

            const inside = isWithinTriggerArea(event.clientX, event.clientY);
            if (inside) {
                isHovering = true;
                updateTarget(event.clientX, event.clientY);
                start();
                return;
            }

            if (isHovering) {
                isHovering = false;
                resetTargets();
                start();
            }
        };

        const onPointerDown = (event) => {
            if (event.pointerType === "touch") {
                return;
            }
            isPressed = true;
            resetTargets();
            start();
        };

        const onPointerUp = () => {
            if (!isPressed) {
                return;
            }
            isPressed = false;
            resetTargets();
            start();
        };

        const onFocus = () => {
            isHovering = false;
            isPressed = false;
            currentX = 0;
            currentY = 0;
            innerX = 0;
            innerY = 0;
            resetTargets();
            applyTransform();
        };

        if (options.triggerMode === "bounds") {
            target.addEventListener("pointerenter", onPointerEnter);
            target.addEventListener("pointermove", onPointerMove);
            target.addEventListener("pointerleave", onPointerLeave);
        } else {
            window.addEventListener("pointermove", onPointerMoveWindow);
        }

        target.addEventListener("pointerdown", onPointerDown);
        window.addEventListener("pointerup", onPointerUp);
        target.addEventListener("focus", onFocus);
        target.addEventListener("blur", onPointerLeave);

        let observer = null;
        if (options.pauseOffscreen && "IntersectionObserver" in window) {
            observer = new IntersectionObserver((entries) => {
                const entry = entries[0];
                isVisible = !!(entry && entry.isIntersecting);

                if (!isVisible) {
                    isHovering = false;
                    resetTargets();
                    start();
                }
            });

            observer.observe(target);
        }

        const destroy = () => {
            if (options.triggerMode === "bounds") {
                target.removeEventListener("pointerenter", onPointerEnter);
                target.removeEventListener("pointermove", onPointerMove);
                target.removeEventListener("pointerleave", onPointerLeave);
            } else {
                window.removeEventListener("pointermove", onPointerMoveWindow);
            }

            target.removeEventListener("pointerdown", onPointerDown);
            window.removeEventListener("pointerup", onPointerUp);
            target.removeEventListener("focus", onFocus);
            target.removeEventListener("blur", onPointerLeave);

            if (observer) {
                observer.disconnect();
                observer = null;
            }

            if (rafId) {
                window.cancelAnimationFrame(rafId);
                rafId = 0;
            }

            target.style.transform = "";
            if (innerTarget) {
                innerTarget.style.transform = "";
            }

            delete target.dataset.magneticInit;
            target.removeAttribute("data-magnetic-init");
            delete target.__kaMagneticInstance;
        };

        target.__kaMagneticInstance = { destroy };
    };

    const initMagneticButtons = ($scope) => {
        $scope.find(".king-addons-magnetic-buttons").each(function () {
            const root = this;
            const rawOptions = parseOptions(root);
            const options = normalizeOptions(rawOptions);
            const targetType = root.dataset.target || "button";
            const targets = resolveTargets(root, options);

            targets.forEach((target) => {
                initMagneticTarget(root, target, targetType, options);
            });
        });
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-magnetic-buttons.default",
            function ($scope) {
                initMagneticButtons($scope);
            }
        );
    });
})(jQuery);
