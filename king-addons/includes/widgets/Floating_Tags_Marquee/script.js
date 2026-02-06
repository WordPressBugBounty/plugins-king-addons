"use strict";
/**
 * Floating Tags Marquee Widget JavaScript.
 *
 * Handles infinite marquee animation with seamless loop.
 * Pro features: drag, pause on hover, multi-rows, reduced motion.
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
     * Debounce function execution.
     *
     * @param {Function} func - Function to debounce.
     * @param {number} wait - Wait time in ms.
     * @returns {Function}
     */
    const debounce = (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    /**
     * Initialize widget instance.
     *
     * @param {jQuery} $scope - Widget scope.
     */
    const initWidget = ($scope) => {
        const root = $scope.find(".kng-ftm").first()[0];
        if (!root || root.dataset.kngFtmInit === "yes") {
            return;
        }

        root.dataset.kngFtmInit = "yes";
        const options = parseSettings(root);

        const isEditor =
            window.elementorFrontend &&
            elementorFrontend.isEditMode &&
            elementorFrontend.isEditMode();

        const isPro = root.classList.contains("kng-ftm--pro");
        const reducedMotion = prefersReducedMotion();

        const direction = options.direction || "left";
        const speed = options.speed || 30;
        const duplicateCount = options.duplicateCount || "auto";

        // Pro settings
        const pauseOnHover = isPro && options.pauseOnHover === true;
        const dragEnabled = isPro && options.drag === true;
        const fadeEdges = isPro && options.fadeEdges === true;
        const respectReducedMotion =
            isPro && options.respectReducedMotion !== false;
        const rows = isPro ? options.rows || 1 : 1;

        // State
        let isDragging = false;
        let startX = 0;
        let scrollLeft = 0;

        /**
         * Setup track duplication for seamless loop.
         *
         * @param {HTMLElement} row - Row element.
         */
        const setupTrackDuplication = (row) => {
            const track = row.querySelector(".kng-ftm__track");
            const content = track.querySelector(".kng-ftm__content");

            if (!content) return;

            const containerWidth = root.offsetWidth;
            const contentWidth = content.scrollWidth;

            // Calculate how many copies needed
            let copies;
            if (duplicateCount === "auto") {
                copies = Math.max(2, Math.ceil((containerWidth * 2) / contentWidth) + 1);
            } else {
                copies = parseInt(duplicateCount, 10) || 2;
            }

            // Remove existing clones
            track.querySelectorAll(".kng-ftm__content--clone").forEach((el) => el.remove());

            // Create clones
            for (let i = 1; i < copies; i++) {
                const clone = content.cloneNode(true);
                clone.classList.add("kng-ftm__content--clone");
                clone.setAttribute("aria-hidden", "true");
                track.appendChild(clone);
            }

            // Set animation duration based on speed and content width
            const totalWidth = contentWidth * copies;
            const duration = totalWidth / (speed * 2); // speed factor
            track.style.setProperty("--kng-ftm-duration", `${duration}s`);
            track.style.setProperty("--kng-ftm-content-width", `${contentWidth}px`);
        };

        /**
         * Setup animation for a track.
         *
         * @param {HTMLElement} row - Row element.
         * @param {number} rowIndex - Row index.
         */
        const setupAnimation = (row, rowIndex) => {
            const track = row.querySelector(".kng-ftm__track");

            // Handle reduced motion
            if (reducedMotion && respectReducedMotion) {
                track.classList.add("kng-ftm__track--paused");
                root.classList.add("kng-ftm--reduced-motion");
                return;
            }

            // Alternate direction for multi-rows
            if (isPro && rows > 1) {
                const alternateDir = options.alternateDirection !== false;
                const rowDirection = direction;

                if (alternateDir && rowIndex % 2 === 1) {
                    track.classList.remove("kng-ftm__track--left", "kng-ftm__track--right");
                    track.classList.add(
                        `kng-ftm__track--${rowDirection === "left" ? "right" : "left"}`
                    );
                }

                // Speed offset for rows
                const speedOffset = options.rowSpeedOffset || 0;
                if (speedOffset !== 0) {
                    const offsetFactor = 1 + (speedOffset / 100) * (rowIndex % 2 === 0 ? 1 : -1);
                    const currentDuration = parseFloat(
                        getComputedStyle(track).getPropertyValue("--kng-ftm-duration")
                    );
                    track.style.setProperty(
                        "--kng-ftm-duration",
                        `${currentDuration * offsetFactor}s`
                    );
                }
            }

            // Start animation
            track.classList.add("kng-ftm__track--animating");
        };

        /**
         * Setup pause on hover (Pro).
         */
        const setupPauseOnHover = () => {
            if (!pauseOnHover) return;

            root.addEventListener("mouseenter", () => {
                root.querySelectorAll(".kng-ftm__track").forEach((track) => {
                    track.classList.add("kng-ftm__track--paused");
                });
            });

            root.addEventListener("mouseleave", () => {
                if (isDragging) return;
                root.querySelectorAll(".kng-ftm__track").forEach((track) => {
                    track.classList.remove("kng-ftm__track--paused");
                });
            });
        };

        /**
         * Setup drag interaction (Pro).
         */
        const setupDrag = () => {
            if (!dragEnabled) return;

            const tracks = root.querySelectorAll(".kng-ftm__track");

            tracks.forEach((track) => {
                // Mouse events
                track.addEventListener("mousedown", (e) => {
                    isDragging = true;
                    track.classList.add("kng-ftm__track--dragging");
                    startX = e.pageX - track.offsetLeft;
                    scrollLeft = track.scrollLeft;
                    track.classList.add("kng-ftm__track--paused");
                });

                track.addEventListener("mouseleave", () => {
                    if (!isDragging) return;
                    isDragging = false;
                    track.classList.remove("kng-ftm__track--dragging");
                    if (!pauseOnHover) {
                        track.classList.remove("kng-ftm__track--paused");
                    }
                });

                track.addEventListener("mouseup", () => {
                    isDragging = false;
                    track.classList.remove("kng-ftm__track--dragging");
                    if (!pauseOnHover) {
                        track.classList.remove("kng-ftm__track--paused");
                    }
                });

                track.addEventListener("mousemove", (e) => {
                    if (!isDragging) return;
                    e.preventDefault();
                    const x = e.pageX - track.offsetLeft;
                    const walk = (x - startX) * 2;
                    track.scrollLeft = scrollLeft - walk;
                });

                // Touch events
                let touchStartX = 0;
                let touchStartY = 0;
                let isHorizontalSwipe = false;

                track.addEventListener(
                    "touchstart",
                    (e) => {
                        const touch = e.touches[0];
                        touchStartX = touch.clientX;
                        touchStartY = touch.clientY;
                        isHorizontalSwipe = false;
                        startX = touch.clientX - track.offsetLeft;
                        scrollLeft = track.scrollLeft;
                    },
                    { passive: true }
                );

                track.addEventListener(
                    "touchmove",
                    (e) => {
                        const touch = e.touches[0];
                        const deltaX = Math.abs(touch.clientX - touchStartX);
                        const deltaY = Math.abs(touch.clientY - touchStartY);

                        // Determine swipe direction on first significant move
                        if (!isHorizontalSwipe && (deltaX > 10 || deltaY > 10)) {
                            isHorizontalSwipe = deltaX > deltaY;
                            if (isHorizontalSwipe) {
                                isDragging = true;
                                track.classList.add("kng-ftm__track--dragging");
                                track.classList.add("kng-ftm__track--paused");
                            }
                        }

                        if (isHorizontalSwipe) {
                            e.preventDefault();
                            const x = touch.clientX - track.offsetLeft;
                            const walk = (x - startX) * 2;
                            track.scrollLeft = scrollLeft - walk;
                        }
                    },
                    { passive: false }
                );

                track.addEventListener(
                    "touchend",
                    () => {
                        isDragging = false;
                        isHorizontalSwipe = false;
                        track.classList.remove("kng-ftm__track--dragging");
                        if (!pauseOnHover) {
                            track.classList.remove("kng-ftm__track--paused");
                        }
                    },
                    { passive: true }
                );
            });
        };

        /**
         * Setup fade edges (Pro).
         */
        const setupFadeEdges = () => {
            if (!fadeEdges) return;
            root.classList.add("kng-ftm--fade-edges");
        };

        /**
         * Initialize all rows.
         */
        const initRows = () => {
            const rowElements = root.querySelectorAll(".kng-ftm__row");
            rowElements.forEach((row, index) => {
                setupTrackDuplication(row);
                setupAnimation(row, index);
            });
        };

        /**
         * Handle resize with debounce.
         */
        const handleResize = debounce(() => {
            const rowElements = root.querySelectorAll(".kng-ftm__row");
            rowElements.forEach((row) => {
                setupTrackDuplication(row);
            });
        }, 200);

        // Initialize
        initRows();

        // Pro features
        if (isPro) {
            setupPauseOnHover();
            setupDrag();
            setupFadeEdges();
        }

        // Resize handler
        window.addEventListener("resize", handleResize);

        // Cleanup on widget destroy (if needed)
        root.kngFtmCleanup = () => {
            window.removeEventListener("resize", handleResize);
        };
    };

    // Initialize on Elementor frontend ready
    $(window).on("elementor/frontend/init", () => {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-floating-tags-marquee.default",
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
