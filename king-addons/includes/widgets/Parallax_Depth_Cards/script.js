"use strict";

(function ($) {
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const toBool = (value, fallback = false) => {
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

    const toNumber = (value, fallback = 0) => {
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    };

    const getPointer = (event, card) => {
        const rect = card.getBoundingClientRect();
        if (!rect.width || !rect.height) {
            return { x: 0, y: 0 };
        }

        const x = (event.clientX - rect.left) / rect.width;
        const y = (event.clientY - rect.top) / rect.height;
        return {
            x: clamp(x * 2 - 1, -1, 1),
            y: clamp(y * 2 - 1, -1, 1),
        };
    };

    const normalizeOptions = (dataset) => {
        const intensity = clamp(toNumber(dataset.intensity, 60) / 100, 0, 1);
        return {
            tilt: toBool(dataset.tilt, true),
            parallax: toBool(dataset.parallax, true),
            trigger: dataset.trigger || "hover",
            reduceMotion: toBool(dataset.reduceMotion, true),
            disableTouch: toBool(dataset.disableTouch, true),
            intensity,
            maxTilt: clamp(toNumber(dataset.maxTilt, 12), 0, 30),
            depthStrength: Math.max(toNumber(dataset.depthStrength, 18), 0),
            smoothing: clamp(toNumber(dataset.smoothing, 0.12), 0.02, 0.4),
            resetDuration: Math.max(toNumber(dataset.resetDuration, 350), 0),
        };
    };

    const initParallaxDepthCards = (root) => {
        const scope = root instanceof HTMLElement ? root : document;
        const wrappers = scope.querySelectorAll(".king-addons-parallax-depth-cards");

        wrappers.forEach((wrapper) => {
            if (wrapper.dataset.pdcInit === "yes") {
                return;
            }
            wrapper.dataset.pdcInit = "yes";

            const options = normalizeOptions(wrapper.dataset || {});
            if (!options.tilt && !options.parallax) {
                return;
            }

            const prefersReduced =
                window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
            if (prefersReduced && options.reduceMotion) {
                wrapper.classList.add("is-motion-disabled");
                return;
            }

            const isCoarsePointer =
                window.matchMedia && window.matchMedia("(hover: none), (pointer: coarse)").matches;
            if (options.disableTouch && isCoarsePointer) {
                wrapper.classList.add("is-motion-disabled");
                return;
            }

            const cards = Array.from(wrapper.querySelectorAll(".king-addons-parallax-depth-cards__card"));
            if (!cards.length) {
                return;
            }

            const instances = new Map();
            let activeInstance = null;

            const resetActive = () => {
                if (activeInstance) {
                    activeInstance.reset();
                    activeInstance = null;
                }
            };

            const setActiveInstance = (instance) => {
                if (activeInstance && activeInstance !== instance) {
                    activeInstance.reset();
                }
                activeInstance = instance;
            };

            const createInstance = (card) => {
                const inner = card.querySelector(".king-addons-parallax-depth-cards__card-inner");
                if (!inner) {
                    return null;
                }

                const layers = Array.from(
                    card.querySelectorAll(".king-addons-parallax-depth-cards__layer[data-depth]")
                );

                let targetX = 0;
                let targetY = 0;
                let currentX = 0;
                let currentY = 0;
                let rafId = 0;
                let active = false;
                let running = false;

                const resetSmoothing =
                    options.resetDuration > 0
                        ? clamp(16 / options.resetDuration, 0.02, 0.25)
                        : options.smoothing;

                const setWillChange = (enabled) => {
                    if (enabled) {
                        card.classList.add("is-active");
                        inner.style.willChange = "transform";
                        layers.forEach((layer) => {
                            layer.style.willChange = "transform";
                        });
                        return;
                    }

                    card.classList.remove("is-active");
                    inner.style.willChange = "";
                    layers.forEach((layer) => {
                        layer.style.willChange = "";
                    });
                };

                const applyTransforms = (x, y) => {
                    if (options.tilt) {
                        const tiltX = -y * options.maxTilt * options.intensity;
                        const tiltY = x * options.maxTilt * options.intensity;
                        inner.style.transform = `rotateX(${tiltX}deg) rotateY(${tiltY}deg)`;
                    } else {
                        inner.style.transform = "";
                    }

                    if (options.parallax) {
                        layers.forEach((layer) => {
                            const depth = toNumber(layer.dataset.depth, 0);
                            const offsetX = x * options.depthStrength * depth * options.intensity;
                            const offsetY = y * options.depthStrength * depth * options.intensity;
                            layer.style.transform = `translate3d(${offsetX}px, ${offsetY}px, 0)`;
                        });
                    } else {
                        layers.forEach((layer) => {
                            layer.style.transform = "";
                        });
                    }
                };

                const tick = () => {
                    const smoothing = active ? options.smoothing : resetSmoothing;
                    currentX += (targetX - currentX) * smoothing;
                    currentY += (targetY - currentY) * smoothing;

                    applyTransforms(currentX, currentY);

                    const isResting =
                        Math.abs(targetX - currentX) < 0.001 &&
                        Math.abs(targetY - currentY) < 0.001 &&
                        !active;

                    if (isResting) {
                        setWillChange(false);
                        running = false;
                        return;
                    }

                    rafId = window.requestAnimationFrame(tick);
                };

                const start = () => {
                    if (running) {
                        return;
                    }
                    running = true;
                    rafId = window.requestAnimationFrame(tick);
                };

                const update = (event) => {
                    const pointer = getPointer(event, card);
                    targetX = pointer.x;
                    targetY = pointer.y;
                    if (!active) {
                        active = true;
                        setWillChange(true);
                    }
                    start();
                };

                const reset = () => {
                    active = false;
                    targetX = 0;
                    targetY = 0;
                    start();
                };

                const destroy = () => {
                    window.cancelAnimationFrame(rafId);
                    setWillChange(false);
                    targetX = 0;
                    targetY = 0;
                    currentX = 0;
                    currentY = 0;
                    applyTransforms(0, 0);
                };

                return { update, reset, destroy };
            };

            cards.forEach((card) => {
                const instance = createInstance(card);
                if (!instance) {
                    return;
                }

                instances.set(card, instance);

                if (options.trigger === "always") {
                    return;
                }

                card.addEventListener("pointerenter", (event) => {
                    setActiveInstance(instance);
                    instance.update(event);
                });

                card.addEventListener("pointermove", (event) => {
                    instance.update(event);
                });

                card.addEventListener("pointerleave", () => {
                    instance.reset();
                });
            });

            if (options.trigger === "always") {
                wrapper.addEventListener("pointermove", (event) => {
                    const card = event.target.closest(".king-addons-parallax-depth-cards__card");
                    if (!card || !wrapper.contains(card)) {
                        resetActive();
                        return;
                    }

                    const instance = instances.get(card);
                    if (!instance) {
                        return;
                    }

                    setActiveInstance(instance);
                    instance.update(event);
                });

                wrapper.addEventListener("pointerleave", () => {
                    resetActive();
                });
            }
        });
    };

    if (window.elementorFrontend && elementorFrontend.hooks) {
        $(window).on("elementor/frontend/init", () => {
            elementorFrontend.hooks.addAction(
                "frontend/element_ready/king-addons-parallax-depth-cards.default",
                ($scope) => {
                    initParallaxDepthCards($scope[0]);
                }
            );
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => initParallaxDepthCards(document));
    } else {
        initParallaxDepthCards(document);
    }
})(jQuery);
