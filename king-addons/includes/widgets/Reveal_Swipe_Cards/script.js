"use strict";
/**
 * Reveal Swipe Cards Widget JavaScript.
 *
 * Handles reveal animations triggered by hover or scroll using IntersectionObserver.
 * Pro features (touch swipe, sequence mode) are handled in this file when Pro is enabled.
 *
 * @package King_Addons
 */
(($) => {
    /**
     * Clamp a value between min and max.
     *
     * @param {number} value - Value to clamp.
     * @param {number} min - Minimum value.
     * @param {number} max - Maximum value.
     * @returns {number} Clamped value.
     */
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

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
     * @returns {boolean} True if reduced motion preferred.
     */
    const prefersReducedMotion = () =>
        window.matchMedia &&
        window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    /**
     * Check if device supports touch.
     *
     * @returns {boolean} True if touch device.
     */
    const isTouchDevice = () =>
        "ontouchstart" in window || navigator.maxTouchPoints > 0;

    /**
     * Initialize widget instance.
     *
     * @param {jQuery} $scope - Widget scope.
     */
    const initWidget = ($scope) => {
        const root = $scope.find(".kng-rsc").first()[0];
        if (!root || root.dataset.kngRscInit === "yes") {
            return;
        }

        root.dataset.kngRscInit = "yes";
        const options = parseSettings(root);

        const cards = Array.from(root.querySelectorAll(".kng-rsc-card"));
        if (!cards.length) {
            return;
        }

        const isEditor =
            window.elementorFrontend &&
            elementorFrontend.isEditMode &&
            elementorFrontend.isEditMode();

        const reducedMotion = prefersReducedMotion();
        const touchEnabled = isTouchDevice();

        const trigger = options.trigger || "hover";
        const direction = options.direction || "left";
        const duration = options.duration || 500;
        const resetOnLeave = options.resetOnLeave !== false;
        const scrollThreshold = options.scrollThreshold || 0.3;
        const scrollOnce = options.scrollOnce !== false;
        const scrollReset = options.scrollReset === true;

        // Pro settings
        const isPro = root.classList.contains("kng-rsc--pro");
        const maskShape = options.maskShape || "rect";
        const blurEdge = options.blurEdge || 0;
        const touchSwipe = isPro && options.touchSwipe === true && touchEnabled;
        const sequenceMode = isPro ? options.sequenceMode || "off" : "off";
        const staggerDelay = options.staggerDelay || 100;

        // State tracking
        const cardStates = new Map();
        let activeCardIndex = -1;
        let sequenceRevealed = 0;

        /**
         * Set CSS custom properties for mask shape (Pro).
         *
         * @param {HTMLElement} card - Card element.
         */
        const setMaskProperties = (card) => {
            if (!isPro) return;

            card.style.setProperty("--kng-rsc-mask-shape", maskShape);

            if (blurEdge > 0) {
                card.style.setProperty("--kng-rsc-blur-edge", `${blurEdge}px`);
                card.classList.add("kng-rsc-card--blur-edge");
            }
        };

        /**
         * Reveal a card.
         *
         * @param {HTMLElement} card - Card element.
         * @param {boolean} instant - Skip animation.
         */
        const revealCard = (card, instant = false) => {
            if (cardStates.get(card) === "revealed") return;

            cardStates.set(card, "revealed");
            card.classList.add("is-revealed");

            if (instant || reducedMotion) {
                card.classList.add("is-instant");
            } else {
                card.classList.remove("is-instant");
            }
        };

        /**
         * Hide a card (reset reveal).
         *
         * @param {HTMLElement} card - Card element.
         * @param {boolean} instant - Skip animation.
         */
        const hideCard = (card, instant = false) => {
            if (cardStates.get(card) === "hidden") return;

            cardStates.set(card, "hidden");
            card.classList.remove("is-revealed");

            if (instant || reducedMotion) {
                card.classList.add("is-instant");
            } else {
                card.classList.remove("is-instant");
            }
        };

        /**
         * Toggle card reveal state.
         *
         * @param {HTMLElement} card - Card element.
         */
        const toggleCard = (card) => {
            if (cardStates.get(card) === "revealed") {
                hideCard(card);
            } else {
                revealCard(card);
            }
        };

        /**
         * Handle hover enter.
         *
         * @param {MouseEvent} event - Mouse event.
         */
        const handleMouseEnter = (event) => {
            const card = event.currentTarget;

            if (sequenceMode === "active-one") {
                // Close previous active card
                if (activeCardIndex >= 0 && cards[activeCardIndex] !== card) {
                    hideCard(cards[activeCardIndex]);
                }
                activeCardIndex = cards.indexOf(card);
            }

            revealCard(card);
        };

        /**
         * Handle hover leave.
         *
         * @param {MouseEvent} event - Mouse event.
         */
        const handleMouseLeave = (event) => {
            if (!resetOnLeave) return;
            if (sequenceMode === "active-one") return;

            const card = event.currentTarget;
            hideCard(card);
        };

        /**
         * Handle focus for keyboard accessibility.
         *
         * @param {FocusEvent} event - Focus event.
         */
        const handleFocus = (event) => {
            const card = event.currentTarget;
            revealCard(card);
        };

        /**
         * Handle blur for keyboard accessibility.
         *
         * @param {FocusEvent} event - Blur event.
         */
        const handleBlur = (event) => {
            if (!resetOnLeave) return;
            if (sequenceMode === "active-one") return;

            const card = event.currentTarget;
            hideCard(card);
        };

        /**
         * Setup hover triggers.
         */
        const setupHoverTriggers = () => {
            cards.forEach((card) => {
                card.addEventListener("mouseenter", handleMouseEnter);
                card.addEventListener("mouseleave", handleMouseLeave);
                card.addEventListener("focusin", handleFocus);
                card.addEventListener("focusout", handleBlur);
            });
        };

        /**
         * Setup scroll triggers using IntersectionObserver.
         */
        const setupScrollTriggers = () => {
            if (!("IntersectionObserver" in window)) {
                // Fallback: reveal all cards immediately
                cards.forEach((card) => revealCard(card, true));
                return;
            }

            const observerOptions = {
                root: null,
                rootMargin: "0px",
                threshold: scrollThreshold,
            };

            const handleIntersect = (entries, observer) => {
                entries.forEach((entry) => {
                    const card = entry.target;
                    const cardIndex = cards.indexOf(card);

                    if (entry.isIntersecting) {
                        // Sequence mode: stagger reveal
                        if (sequenceMode === "stagger" && !isEditor) {
                            const delay = cardIndex * staggerDelay;
                            setTimeout(() => {
                                revealCard(card);
                                sequenceRevealed++;
                            }, delay);
                        } else {
                            revealCard(card);
                        }

                        if (scrollOnce) {
                            observer.unobserve(card);
                        }
                    } else if (!scrollOnce && scrollReset) {
                        hideCard(card);
                    }
                });
            };

            const observer = new IntersectionObserver(handleIntersect, observerOptions);
            cards.forEach((card) => observer.observe(card));
        };

        /**
         * Setup touch swipe (Pro).
         */
        const setupTouchSwipe = () => {
            if (!touchSwipe) return;

            cards.forEach((card) => {
                let startX = 0;
                let startY = 0;
                let currentX = 0;
                let currentY = 0;
                let isDragging = false;
                const threshold = 0.4; // 40% swipe to trigger
                const velocityThreshold = 0.5;
                let startTime = 0;

                const isHorizontal = direction === "left" || direction === "right";
                const overlay = card.querySelector(".kng-rsc-card__overlay");

                const handleTouchStart = (e) => {
                    const touch = e.touches[0];
                    startX = touch.clientX;
                    startY = touch.clientY;
                    startTime = Date.now();
                    isDragging = true;
                    card.classList.add("is-dragging");
                };

                const handleTouchMove = (e) => {
                    if (!isDragging) return;

                    const touch = e.touches[0];
                    currentX = touch.clientX - startX;
                    currentY = touch.clientY - startY;

                    const delta = isHorizontal ? currentX : currentY;
                    const size = isHorizontal ? card.offsetWidth : card.offsetHeight;
                    const progress = clamp(Math.abs(delta) / size, 0, 1);

                    // Determine if swipe direction matches reveal direction
                    let isCorrectDirection = false;
                    if (direction === "left" && currentX > 0) isCorrectDirection = true;
                    if (direction === "right" && currentX < 0) isCorrectDirection = true;
                    if (direction === "top" && currentY > 0) isCorrectDirection = true;
                    if (direction === "bottom" && currentY < 0) isCorrectDirection = true;

                    // Prevent page scroll if swiping in correct direction
                    if (isCorrectDirection && Math.abs(delta) > 10) {
                        e.preventDefault();
                    }

                    // Update overlay position for visual feedback
                    if (overlay && isCorrectDirection) {
                        const transformValue = isHorizontal
                            ? `translateX(${progress * 100}%)`
                            : `translateY(${progress * 100}%)`;
                        overlay.style.transform = transformValue;
                    }
                };

                const handleTouchEnd = () => {
                    if (!isDragging) return;

                    isDragging = false;
                    card.classList.remove("is-dragging");

                    const delta = isHorizontal ? currentX : currentY;
                    const size = isHorizontal ? card.offsetWidth : card.offsetHeight;
                    const progress = Math.abs(delta) / size;
                    const elapsed = Date.now() - startTime;
                    const velocity = Math.abs(delta) / elapsed;

                    // Snap decision
                    if (progress > threshold || velocity > velocityThreshold) {
                        revealCard(card);
                    } else {
                        hideCard(card);
                    }

                    // Reset overlay transform
                    if (overlay) {
                        overlay.style.transform = "";
                    }

                    startX = 0;
                    startY = 0;
                    currentX = 0;
                    currentY = 0;
                };

                card.addEventListener("touchstart", handleTouchStart, { passive: true });
                card.addEventListener("touchmove", handleTouchMove, { passive: false });
                card.addEventListener("touchend", handleTouchEnd, { passive: true });
                card.addEventListener("touchcancel", handleTouchEnd, { passive: true });
            });
        };

        // Initialize cards
        cards.forEach((card, index) => {
            cardStates.set(card, "hidden");
            setMaskProperties(card);

            // Set direction-specific class
            card.classList.add(`kng-rsc-card--direction-${direction}`);

            // Pro: mask shape class
            if (isPro && maskShape !== "rect") {
                card.classList.add(`kng-rsc-card--mask-${maskShape}`);
            }
        });

        // Setup triggers based on configuration
        if (trigger === "hover" || trigger === "both") {
            setupHoverTriggers();
        }

        if ((trigger === "scroll" || trigger === "both") && !isEditor) {
            setupScrollTriggers();
        }

        // Setup touch swipe (Pro)
        if (touchSwipe && !isEditor) {
            setupTouchSwipe();
        }

        // Reduced motion: instant reveal or simplified
        if (reducedMotion) {
            root.classList.add("kng-rsc--reduced-motion");
        }
    };

    // Initialize on Elementor frontend ready
    $(window).on("elementor/frontend/init", () => {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-reveal-swipe-cards.default",
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
