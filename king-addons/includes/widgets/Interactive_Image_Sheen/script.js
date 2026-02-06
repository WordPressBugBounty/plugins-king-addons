"use strict";
/**
 * Interactive Image Sheen Widget JavaScript.
 *
 * Handles follow cursor mode, mobile fallback, and accessibility.
 * Pro features: cursor tracking with lerp smoothing, reveal on scroll.
 *
 * @package King_Addons
 */
(($) => {
    /**
     * Parse JSON settings from data attribute.
     *
     * @param {HTMLElement} root - Root element.
     * @returns {Object} Parsed settings.
     */
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

    /**
     * Check if user prefers reduced motion.
     *
     * @returns {boolean}
     */
    const prefersReducedMotion = () =>
        window.matchMedia &&
        window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    /**
     * Check if device is touch-only.
     *
     * @returns {boolean}
     */
    const isTouchDevice = () =>
        window.matchMedia &&
        window.matchMedia("(hover: none) and (pointer: coarse)").matches;

    /**
     * Linear interpolation.
     *
     * @param {number} start - Start value.
     * @param {number} end - End value.
     * @param {number} factor - Interpolation factor (0-1).
     * @returns {number}
     */
    const lerp = (start, end, factor) => start + (end - start) * factor;

    /**
     * Clamp value between min and max.
     *
     * @param {number} value - Value to clamp.
     * @param {number} min - Minimum.
     * @param {number} max - Maximum.
     * @returns {number}
     */
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    /**
     * Initialize widget instance.
     *
     * @param {jQuery} $scope - Widget scope.
     */
    const initWidget = ($scope) => {
        const root = $scope.find(".kng-iis").first()[0];
        if (!root || root.dataset.kngIisInit === "yes") {
            return;
        }

        root.dataset.kngIisInit = "yes";
        const options = parseSettings(root);

        const isPro = root.classList.contains("kng-iis--pro");
        const reducedMotion = prefersReducedMotion();
        const isTouch = isTouchDevice();

        // Pro settings
        const followEnabled = isPro && options.followEnabled === true;
        const followStrength = options.followStrength || 50;
        const followSmoothing = options.followSmoothing || 0.1;
        const followAngleShift = options.followAngleShift === true;
        const mobileMode = options.mobileMode || "disable";
        const focusTrigger = options.focusTrigger !== false;

        // State for follow cursor
        let targetX = 0.5;
        let targetY = 0.5;
        let currentX = 0.5;
        let currentY = 0.5;
        let isHovering = false;
        let animationId = null;

        const sheens = root.querySelectorAll(".kng-iis__sheen");

        /**
         * Update sheen position based on cursor.
         */
        const updateSheenPosition = () => {
            if (!isHovering && Math.abs(currentX - 0.5) < 0.01 && Math.abs(currentY - 0.5) < 0.01) {
                cancelAnimationFrame(animationId);
                animationId = null;
                return;
            }

            // Lerp towards target
            currentX = lerp(currentX, isHovering ? targetX : 0.5, followSmoothing);
            currentY = lerp(currentY, isHovering ? targetY : 0.5, followSmoothing);

            // Calculate transform
            const strength = followStrength / 100;
            const translateX = (currentX - 0.5) * 200 * strength;
            const translateY = (currentY - 0.5) * 50 * strength;

            // Update sheen position
            sheens.forEach((sheen, index) => {
                const layerFactor = 1 - index * 0.15; // Slightly different for each layer
                let transform = `translateX(${translateX * layerFactor}%) translateY(${translateY * layerFactor}%)`;

                if (followAngleShift) {
                    const angle = (currentX - 0.5) * 30;
                    transform += ` rotate(${angle}deg)`;
                }

                sheen.style.transform = transform;
            });

            animationId = requestAnimationFrame(updateSheenPosition);
        };

        /**
         * Setup follow cursor interaction.
         */
        const setupFollowCursor = () => {
            if (!followEnabled || reducedMotion || isTouch) {
                return;
            }

            root.addEventListener("mouseenter", () => {
                isHovering = true;
                root.classList.add("kng-iis--following");
                if (!animationId) {
                    animationId = requestAnimationFrame(updateSheenPosition);
                }
            });

            root.addEventListener("mouseleave", () => {
                isHovering = false;
                root.classList.remove("kng-iis--following");
                // Animation continues to lerp back to center
                if (!animationId) {
                    animationId = requestAnimationFrame(updateSheenPosition);
                }
            });

            root.addEventListener("mousemove", (e) => {
                const rect = root.getBoundingClientRect();
                targetX = clamp((e.clientX - rect.left) / rect.width, 0, 1);
                targetY = clamp((e.clientY - rect.top) / rect.height, 0, 1);
            });
        };

        /**
         * Setup mobile reveal on scroll (Pro).
         */
        const setupMobileReveal = () => {
            if (mobileMode !== "reveal" || !isTouch) {
                return;
            }

            if (!("IntersectionObserver" in window)) {
                return;
            }

            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            root.classList.add("kng-iis--in-view");
                            observer.unobserve(root);
                        }
                    });
                },
                {
                    threshold: 0.5,
                }
            );

            observer.observe(root);
        };

        /**
         * Setup focus trigger for accessibility.
         */
        const setupFocusTrigger = () => {
            if (!focusTrigger) {
                return;
            }

            const focusable = root.querySelectorAll("a, button, [tabindex]");
            focusable.forEach((el) => {
                el.addEventListener("focus", () => {
                    root.classList.add("kng-iis--focused");
                });

                el.addEventListener("blur", () => {
                    root.classList.remove("kng-iis--focused");
                });
            });
        };

        /**
         * Handle reduced motion preference.
         */
        const setupReducedMotion = () => {
            if (!reducedMotion) {
                return;
            }

            // CSS handles the visual changes via media query
            // We just ensure no JS animations run
            if (animationId) {
                cancelAnimationFrame(animationId);
                animationId = null;
            }
        };

        /**
         * Parse sheen color to RGB for CSS calc.
         * (Helper for advanced gradient calculations)
         */
        const setupSheenColor = () => {
            const computedStyle = getComputedStyle(root);
            const sheenColor = computedStyle.getPropertyValue("--kng-iis-sheen-color").trim();

            // Convert hex to RGB for CSS calc
            if (sheenColor.startsWith("#")) {
                let r, g, b;
                if (sheenColor.length === 4) {
                    r = parseInt(sheenColor[1] + sheenColor[1], 16);
                    g = parseInt(sheenColor[2] + sheenColor[2], 16);
                    b = parseInt(sheenColor[3] + sheenColor[3], 16);
                } else {
                    r = parseInt(sheenColor.slice(1, 3), 16);
                    g = parseInt(sheenColor.slice(3, 5), 16);
                    b = parseInt(sheenColor.slice(5, 7), 16);
                }
                root.style.setProperty("--kng-iis-sheen-color-rgb", `${r}, ${g}, ${b}`);
            }
        };

        // Initialize
        setupSheenColor();
        setupReducedMotion();

        if (isPro) {
            setupFollowCursor();
            setupMobileReveal();
        }

        setupFocusTrigger();

        // Cleanup function
        root.kngIisCleanup = () => {
            if (animationId) {
                cancelAnimationFrame(animationId);
            }
        };
    };

    // Initialize on Elementor frontend ready
    $(window).on("elementor/frontend/init", () => {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-interactive-image-sheen.default",
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

        // Pro version
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-interactive-image-sheen-pro.default",
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
