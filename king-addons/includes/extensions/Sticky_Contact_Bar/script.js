/**
 * Sticky Contact Bar - Frontend JavaScript
 *
 * Handles visibility triggers, scroll behavior, and action buttons.
 *
 * @package King_Addons
 */
(() => {
    'use strict';

    /**
     * Initialize a single sticky contact bar.
     *
     * @param {HTMLElement} bar The bar element.
     */
    const initBar = (bar) => {
        if (!bar) {
            return;
        }

        const trigger = bar.dataset.trigger || 'always';
        const triggerScroll = parseInt(bar.dataset.triggerScroll || '0', 10);
        const triggerDelay = parseInt(bar.dataset.triggerDelay || '0', 10);
        const hideOnScrollDown = (bar.dataset.hideOnScrollDown || 'no') === 'yes';
        const showOnScrollUp = (bar.dataset.showOnScrollUp || 'no') === 'yes';
        const analyticsEnabled = (bar.dataset.analyticsEnabled || 'no') === 'yes';

        let lastY = window.scrollY;
        let isVisible = false;
        let triggerMet = false;

        /**
         * Show the bar with animation.
         */
        const show = () => {
            if (!isVisible) {
                bar.classList.add('is-visible');
                bar.classList.remove('is-hidden');
                isVisible = true;
            }
        };

        /**
         * Hide the bar with animation.
         */
        const hide = () => {
            bar.classList.remove('is-visible');
            bar.classList.add('is-hidden');
            isVisible = false;
        };

        /**
         * Handle scroll-based trigger activation.
         */
        const handleTriggerScroll = () => {
            const currentY = window.scrollY;

            // Check if trigger threshold is met
            if (currentY >= triggerScroll) {
                triggerMet = true;
            } else {
                triggerMet = false;
            }

            // If trigger not met, always hide
            if (!triggerMet) {
                hide();
                return;
            }

            // Handle direction-based visibility (Pro feature)
            if (hideOnScrollDown || showOnScrollUp) {
                const goingDown = currentY > lastY;
                const delta = Math.abs(currentY - lastY);

                // Only react to significant scroll (debounce small movements)
                if (delta > 5) {
                    if (hideOnScrollDown && goingDown) {
                        hide();
                    } else if (showOnScrollUp && !goingDown) {
                        show();
                    }
                }
            } else {
                // No direction logic, just show if trigger is met
                show();
            }

            lastY = currentY;
        };

        /**
         * Handle direction-based visibility for always/delay triggers.
         */
        const handleDirectionOnly = () => {
            const currentY = window.scrollY;
            const goingDown = currentY > lastY;
            const delta = Math.abs(currentY - lastY);

            // Only react to significant scroll
            if (delta > 5) {
                if (hideOnScrollDown && goingDown && isVisible) {
                    hide();
                } else if (showOnScrollUp && !goingDown && !isVisible) {
                    show();
                }
            }

            lastY = currentY;
        };

        /**
         * Track click event for analytics.
         *
         * @param {string} type  Button type.
         * @param {string} label Button label.
         */
        const trackClick = (type, label) => {
            if (!analyticsEnabled) {
                return;
            }

            const eventData = {
                event: 'ka_sticky_contact_click',
                ka_button_type: type,
                ka_button_label: label,
            };

            // Push to dataLayer (GTM)
            if (typeof window.dataLayer !== 'undefined') {
                window.dataLayer.push(eventData);
            }

            // Send to gtag if available
            if (typeof window.gtag === 'function') {
                window.gtag('event', 'sticky_contact_click', {
                    button_type: type,
                    button_label: label,
                });
            }
        };

        // Initialize based on trigger type
        if (trigger === 'always') {
            show();
            triggerMet = true;

            // If direction features enabled, listen for scroll
            if (hideOnScrollDown || showOnScrollUp) {
                window.addEventListener('scroll', handleDirectionOnly, { passive: true });
            }
        } else if (trigger === 'delay') {
            setTimeout(() => {
                show();
                triggerMet = true;

                // If direction features enabled, listen for scroll after delay
                if (hideOnScrollDown || showOnScrollUp) {
                    window.addEventListener('scroll', handleDirectionOnly, { passive: true });
                }
            }, Math.max(triggerDelay, 0));
        } else if (trigger === 'scroll') {
            window.addEventListener('scroll', handleTriggerScroll, { passive: true });
            // Check initial position
            handleTriggerScroll();
        }

        // Handle button clicks
        bar.addEventListener('click', (event) => {
            const target = event.target.closest('.ka-sticky-contact-item');
            if (!target) {
                return;
            }

            const action = target.dataset.action;
            const type = target.classList.contains('ka-type-phone') ? 'phone' :
                         target.classList.contains('ka-type-email') ? 'email' :
                         target.classList.contains('ka-type-whatsapp') ? 'whatsapp' :
                         target.classList.contains('ka-type-telegram') ? 'telegram' :
                         target.classList.contains('ka-type-messenger') ? 'messenger' :
                         target.classList.contains('ka-type-viber') ? 'viber' :
                         action || 'link';
            const label = target.getAttribute('aria-label') || type;

            // Track the click
            trackClick(type, label);

            // Handle special actions
            if (!action) {
                return;
            }

            event.preventDefault();

            if (action === 'popup') {
                const popupId = target.dataset.popupId;
                if (popupId && window.KingAddonsPopup && typeof window.KingAddonsPopup.open === 'function') {
                    window.KingAddonsPopup.open(popupId);
                }
                return;
            }

            if (action === 'offcanvas') {
                const offcanvasId = target.dataset.offcanvasId;
                if (offcanvasId && window.KingAddonsOffcanvas && typeof window.KingAddonsOffcanvas.open === 'function') {
                    window.KingAddonsOffcanvas.open(offcanvasId);
                }
                return;
            }

            if (action === 'scroll') {
                const selector = target.dataset.scrollTarget;
                if (selector) {
                    const el = document.querySelector(selector);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            }
        });
    };

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.ka-sticky-contact-bar').forEach(initBar);
    });
})();






