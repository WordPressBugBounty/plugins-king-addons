"use strict";

(function ($) {
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
    const lerp = (start, end, amount) => start + (end - start) * amount;
    const getNumber = (value, fallback) => {
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    };

    const resolvePerformanceMode = (mode, cardCount, reducedMotion) => {
        if (mode && mode !== "auto") {
            return mode;
        }

        if (reducedMotion) {
            return "performance";
        }

        const isMobile = window.matchMedia && window.matchMedia("(max-width: 767px)").matches;
        const lowMemory = navigator.deviceMemory && navigator.deviceMemory <= 4;
        const lowCores = navigator.hardwareConcurrency && navigator.hardwareConcurrency <= 4;

        if (isMobile && cardCount > 8) {
            return "performance";
        }

        if (lowMemory || lowCores) {
            return "balanced";
        }

        return "quality";
    };

    const capRootVar = (root, name, cap, unit) => {
        const value = getNumber(getComputedStyle(root).getPropertyValue(name), 0);
        if (value > cap) {
            root.style.setProperty(name, `${cap}${unit || ""}`);
        }
    };

    const applyPerformanceOverrides = (root, mode) => {
        if (mode === "balanced") {
            capRootVar(root, "--kng-liquid-blur", 16, "px");
            capRootVar(root, "--kng-liquid-noise-opacity", 0.12, "");
            capRootVar(root, "--kng-liquid-highlight-opacity", 0.35, "");
        }

        if (mode === "performance") {
            capRootVar(root, "--kng-liquid-blur", 10, "px");
            capRootVar(root, "--kng-liquid-noise-opacity", 0.08, "");
            capRootVar(root, "--kng-liquid-highlight-opacity", 0.25, "");
        }
    };

    const initLiquidGlass = ($scope) => {
        const root = $scope[0]?.querySelector(".king-liquid-glass");
        if (!root) {
            return;
        }

        let settings = {};
        try {
            settings = JSON.parse(root.dataset.settings || "{}");
        } catch (error) {
            settings = {};
        }

        const tilt = settings.tilt || {};
        const parallax = settings.parallax || {};
        const performance = settings.performance || {};

        const cards = Array.from(root.querySelectorAll(".king-liquid-glass__card"));
        if (!cards.length) {
            return;
        }

        const reducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        const reduceTransparency = window.matchMedia && window.matchMedia("(prefers-reduced-transparency: reduce)").matches;
        const highContrast = window.matchMedia && window.matchMedia("(prefers-contrast: more)").matches;

        if (reduceTransparency) {
            root.classList.add("king-liquid-glass--reduced-transparency");
        }

        if (highContrast) {
            root.classList.add("king-liquid-glass--high-contrast");
        }

        const perfMode = resolvePerformanceMode(performance.mode || "auto", settings.cardCount || cards.length, reducedMotion);
        applyPerformanceOverrides(root, perfMode);

        if (reducedMotion || perfMode === "performance") {
            tilt.enabled = false;
            parallax.enabled = false;
        }

        const hasTilt = !!tilt.enabled;
        const hasParallax = !!parallax.enabled;

        if (!hasTilt && !hasParallax) {
            return;
        }

        const isEditMode = window.elementorFrontend && elementorFrontend.isEditMode && elementorFrontend.isEditMode();
        let tiltInput = tilt.input || "pointer";

        if (tiltInput === "orientation") {
            if (isEditMode || !("DeviceOrientationEvent" in window)) {
                tiltInput = "pointer";
            } else if (typeof DeviceOrientationEvent.requestPermission === "function") {
                tiltInput = "pointer";
            }
        }

        const glareEnabled = !!(tilt.glare && tilt.glare.enabled);
        const glareStrength = glareEnabled ? getNumber(tilt.glare.intensity, 0.35) : 0;
        if (glareEnabled) {
            root.style.setProperty("--kng-liquid-glare-size", `${getNumber(tilt.glare.size, 70)}%`);
            root.style.setProperty("--kng-liquid-glare-blend", tilt.glare.blend || "screen");
        }

        const scale = perfMode === "balanced" ? 0.8 : 1;
        const maxTilt = clamp(getNumber(tilt.max, 8) * scale, 0, 20);
        const smoothing = clamp(getNumber(tilt.smoothing, 0.12), 0.02, 0.5);
        const liftDistance = tilt.lift && tilt.lift.enabled ? getNumber(tilt.lift.distance, 10) : 0;
        const parallaxScale = perfMode === "balanced" ? 0.7 : 1;
        const pointerParallax = hasParallax && (parallax.mode || "pointer") === "pointer";
        const scrollParallax = hasParallax && (parallax.mode || "pointer") === "scroll";

        if (isEditMode) {
            return;
        }

        const cardData = cards.map((card) => ({
            card,
            layers: Array.from(card.querySelectorAll("[data-depth]")),
        }));

        const state = {
            active: null,
            rect: null,
            targetX: 0,
            targetY: 0,
            currentX: 0,
            currentY: 0,
            rafPointer: null,
            rafScroll: null,
            inView: true,
        };

        const resetCard = (data) => {
            if (!data) {
                return;
            }
            data.card.classList.remove("is-tilting");
            data.card.style.transform = "";
            data.card.style.removeProperty("--kng-liquid-highlight-x");
            data.card.style.removeProperty("--kng-liquid-highlight-y");
            data.card.style.removeProperty("--kng-liquid-glare-strength");
            if (pointerParallax) {
                data.layers.forEach((layer) => {
                    layer.style.transform = "";
                });
            }
        };

        const setActiveCard = (data) => {
            if (state.active === data) {
                return;
            }
            resetCard(state.active);
            state.active = data;
            state.currentX = 0;
            state.currentY = 0;
            state.targetX = 0;
            state.targetY = 0;
            if (data) {
                state.rect = data.card.getBoundingClientRect();
                data.card.classList.add("is-tilting");
            } else {
                state.rect = null;
            }
        };

        const updateTargets = (clientX, clientY) => {
            if (!state.rect) {
                return;
            }
            const x = clamp((clientX - state.rect.left) / state.rect.width, 0, 1) * 2 - 1;
            const y = clamp((clientY - state.rect.top) / state.rect.height, 0, 1) * 2 - 1;
            state.targetX = x;
            state.targetY = y;
        };

        const applyCardTransforms = (data, normX, normY) => {
            if (!data) {
                return;
            }

            if (hasTilt) {
                const tiltX = clamp(-normY * maxTilt, -maxTilt, maxTilt);
                const tiltY = clamp(normX * maxTilt, -maxTilt, maxTilt);
                const translateY = liftDistance ? -liftDistance : 0;
                data.card.style.transform = `translate3d(0, ${translateY}px, 0) rotateX(${tiltX}deg) rotateY(${tiltY}deg)`;

                if (glareEnabled) {
                    const glareX = clamp((normX + 1) * 50, 0, 100);
                    const glareY = clamp((normY + 1) * 50, 0, 100);
                    data.card.style.setProperty("--kng-liquid-highlight-x", `${glareX}%`);
                    data.card.style.setProperty("--kng-liquid-highlight-y", `${glareY}%`);
                    data.card.style.setProperty("--kng-liquid-glare-strength", String(glareStrength));
                }
            }

            if (pointerParallax) {
                data.layers.forEach((layer) => {
                    const depth = getNumber(layer.dataset.depth, 0) * parallaxScale;
                    if (!depth) {
                        layer.style.transform = "";
                        return;
                    }
                    const translateX = normX * depth;
                    const translateY = normY * depth;
                    const scaleValue = 1 + Math.min(Math.abs(depth) / 120, 0.06);
                    layer.style.transform = `translate3d(${translateX}px, ${translateY}px, 0) scale(${scaleValue})`;
                });
            }
        };

        const animatePointer = () => {
            if (!state.active) {
                state.rafPointer = null;
                return;
            }

            state.currentX = lerp(state.currentX, state.targetX, smoothing);
            state.currentY = lerp(state.currentY, state.targetY, smoothing);

            applyCardTransforms(state.active, state.currentX, state.currentY);

            const deltaX = Math.abs(state.targetX - state.currentX);
            const deltaY = Math.abs(state.targetY - state.currentY);

            if (deltaX > 0.001 || deltaY > 0.001) {
                state.rafPointer = requestAnimationFrame(animatePointer);
            } else {
                state.rafPointer = null;
            }
        };

        const requestPointerTick = () => {
            if (state.rafPointer) {
                return;
            }
            state.rafPointer = requestAnimationFrame(animatePointer);
        };

        const onPointerMove = (event) => {
            if (!state.inView) {
                return;
            }
            const card = event.target.closest(".king-liquid-glass__card");
            if (!card || !root.contains(card)) {
                onPointerLeave();
                return;
            }
            const data = cardData.find((item) => item.card === card);
            if (!data) {
                return;
            }
            if (state.active !== data) {
                setActiveCard(data);
            }
            updateTargets(event.clientX, event.clientY);
            requestPointerTick();
        };

        const onPointerLeave = () => {
            if (!state.active) {
                return;
            }
            resetCard(state.active);
            state.active = null;
            state.rect = null;
        };

        const setupPointer = () => {
            if (!hasTilt && !pointerParallax) {
                return;
            }
            root.addEventListener("pointermove", onPointerMove);
            root.addEventListener("pointerleave", onPointerLeave);
        };

        const setupOrientation = () => {
            if (!hasTilt || tiltInput !== "orientation") {
                return;
            }

            const handleOrientation = (event) => {
                if (!state.inView) {
                    return;
                }
                const gamma = getNumber(event.gamma, 0);
                const beta = getNumber(event.beta, 0);
                state.targetX = clamp(gamma / 25, -1, 1);
                state.targetY = clamp(beta / 25, -1, 1);

                if (!state.rafPointer) {
                    state.rafPointer = requestAnimationFrame(() => {
                        state.currentX = lerp(state.currentX, state.targetX, smoothing);
                        state.currentY = lerp(state.currentY, state.targetY, smoothing);
                        cardData.forEach((data) => applyCardTransforms(data, state.currentX, state.currentY));
                        state.rafPointer = null;
                    });
                }
            };

            window.addEventListener("deviceorientation", handleOrientation, { passive: true });
        };

        const setupScrollParallax = () => {
            if (!scrollParallax) {
                return;
            }

            const updateScroll = () => {
                if (!state.inView) {
                    state.rafScroll = null;
                    return;
                }

                const viewportHeight = window.innerHeight || 0;

                cardData.forEach((data) => {
                    const rect = data.card.getBoundingClientRect();
                    const progress = clamp((viewportHeight - rect.top) / (viewportHeight + rect.height), 0, 1);
                    const offset = (progress - 0.5) * 2;

                    data.layers.forEach((layer) => {
                        const depth = getNumber(layer.dataset.depth, 0) * parallaxScale;
                        if (!depth) {
                            layer.style.transform = "";
                            return;
                        }
                        const translateY = -offset * depth;
                        const scaleValue = 1 + Math.min(Math.abs(depth) / 120, 0.06);
                        layer.style.transform = `translate3d(0, ${translateY}px, 0) scale(${scaleValue})`;
                    });
                });

                state.rafScroll = null;
            };

            const onScroll = () => {
                if (state.rafScroll) {
                    return;
                }
                state.rafScroll = requestAnimationFrame(updateScroll);
            };

            window.addEventListener("scroll", onScroll, { passive: true });
            window.addEventListener("resize", onScroll);
            updateScroll();
        };

        const setupObserver = () => {
            if (!("IntersectionObserver" in window)) {
                return;
            }
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        state.inView = entry.isIntersecting;
                        if (!state.inView) {
                            onPointerLeave();
                        }
                    });
                },
                {
                    threshold: 0.1,
                }
            );
            observer.observe(root);
        };

        setupObserver();
        if (tiltInput === "orientation") {
            setupOrientation();
        } else {
            setupPointer();
        }
        setupScrollParallax();
    };

    $(window).on("elementor/frontend/init", () => {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-liquid-glass-cards.default",
            initLiquidGlass
        );
    });
})(jQuery);
