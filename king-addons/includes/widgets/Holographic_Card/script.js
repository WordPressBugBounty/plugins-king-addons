/**
 * King Addons - Holographic Card Widget Script
 * 3D tilt effect with pointer tracking and lerp animation
 */
(function () {
    'use strict';

    function initHolographicCard(stage) {
        if (!stage) return;
        
        const card = stage.querySelector('.king-addons-holo-card');
        if (!card) return;

        // Read data attributes
        const maxTilt = parseFloat(stage.dataset.maxTilt) || 12;
        const kIn = parseFloat(stage.dataset.smoothnessIn) || 0.16;
        const kHover = parseFloat(stage.dataset.smoothnessHover) || 0.08;
        const kOut = parseFloat(stage.dataset.smoothnessOut) || 0.12;
        const parallaxStrength = parseFloat(stage.dataset.parallaxStrength) || 0.18;
        const disableMotion = stage.dataset.disableMotion === 'true';
        const respectReducedMotion = stage.dataset.respectReducedMotion === 'true';

        // Check for reduced motion preference
        if (respectReducedMotion && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        // Skip if motion disabled
        if (disableMotion) {
            return;
        }

        // Utility functions
        const clamp = (v, a, b) => Math.min(b, Math.max(a, v));

        // Current values (animated)
        const cur = { rx: 0, ry: 0, mx: 50, my: 50, p: 0 };
        // Target values (from pointer)
        const tgt = { rx: 0, ry: 0, mx: 50, my: 50, p: 0 };

        let rect = null;
        let isInside = false;
        let isEntering = false; // true only during initial hover transition
        let rafId = 0;

        function readCssVarsIntoCur() {
            const s = getComputedStyle(stage);

            const rx = parseFloat(s.getPropertyValue('--rx')) || 0;
            const ry = parseFloat(s.getPropertyValue('--ry')) || 0;
            const mx = parseFloat(s.getPropertyValue('--mx')) || 50;
            const my = parseFloat(s.getPropertyValue('--my')) || 50;
            const p = parseFloat(s.getPropertyValue('--p')) || 0;

            cur.rx = rx;
            cur.ry = ry;
            cur.mx = mx;
            cur.my = my;
            cur.p = p;
        }

        function updateRect() {
            rect = stage.getBoundingClientRect();
        }

        function setTargetFromEvent(ev) {
            if (!rect) updateRect();

            const x = (ev.clientX - rect.left) / rect.width;
            const y = (ev.clientY - rect.top) / rect.height;

            const px = clamp(x, 0, 1);
            const py = clamp(y, 0, 1);

            tgt.ry = (px - 0.5) * (maxTilt * 2);
            tgt.rx = (0.5 - py) * (maxTilt * 2);
            tgt.mx = px * 100;
            tgt.my = py * 100;

            const dx = px - 0.5;
            const dy = py - 0.5;
            tgt.p = clamp(Math.sqrt(dx * dx + dy * dy) * 1.6, 0, 1);
        }

        function setTargetNeutral() {
            tgt.rx = 0;
            tgt.ry = 0;
            tgt.mx = 50;
            tgt.my = 50;
            tgt.p = 0;
        }

        function applyCur() {
            stage.style.setProperty('--rx', cur.rx.toFixed(2) + 'deg');
            stage.style.setProperty('--ry', cur.ry.toFixed(2) + 'deg');
            stage.style.setProperty('--mx', cur.mx.toFixed(2) + '%');
            stage.style.setProperty('--my', cur.my.toFixed(2) + '%');
            stage.style.setProperty('--p', cur.p.toFixed(3));
        }

        function nearly(a, b, eps) {
            return Math.abs(a - b) <= eps;
        }

        function tick() {
            // Smoothing factor: kIn = when entering card, kOut = when leaving card
            // When inside but not entering, use a base smooth value (0.08) for fluid motion
            let k;
            if (isEntering) {
                k = kIn;
                // Check if we've reached close to target - then switch off entering mode
                const enteredEnough =
                    nearly(cur.rx, tgt.rx, 1) &&
                    nearly(cur.ry, tgt.ry, 1) &&
                    nearly(cur.mx, tgt.mx, 5) &&
                    nearly(cur.my, tgt.my, 5);
                if (enteredEnough) {
                    isEntering = false;
                }
            } else if (isInside) {
                k = kHover; // smooth movement while hovering inside
            } else {
                k = kOut;
            }

            cur.rx += (tgt.rx - cur.rx) * k;
            cur.ry += (tgt.ry - cur.ry) * k;
            cur.mx += (tgt.mx - cur.mx) * k;
            cur.my += (tgt.my - cur.my) * k;
            cur.p += (tgt.p - cur.p) * k;

            applyCur();

            const done =
                nearly(cur.rx, tgt.rx, 0.05) &&
                nearly(cur.ry, tgt.ry, 0.05) &&
                nearly(cur.mx, tgt.mx, 0.08) &&
                nearly(cur.my, tgt.my, 0.08) &&
                nearly(cur.p, tgt.p, 0.004);

            if (!isInside && done) {
                rafId = 0;
                card.classList.remove('is-active');
                return;
            }

            rafId = requestAnimationFrame(tick);
        }

        function ensureRaf() {
            if (rafId) return;
            rafId = requestAnimationFrame(tick);
        }

        function onEnter(ev) {
            isInside = true;
            isEntering = true; // activate kIn smoothness only during entry
            card.classList.add('is-active');

            updateRect();
            // Start from current state to avoid jump
            readCssVarsIntoCur();

            setTargetFromEvent(ev);
            ensureRaf();
        }

        function onMove(ev) {
            if (!isInside) return;
            setTargetFromEvent(ev);
        }

        function onLeave() {
            isInside = false;
            isEntering = false;
            // If Auto-Sway is active, don't reset to neutral - let Pro script handle it
            if (stage.dataset.autoSway === 'true') {
                // Just stop the base animation, Pro will take over
                if (rafId) {
                    cancelAnimationFrame(rafId);
                    rafId = 0;
                }
                card.classList.remove('is-active');
                return;
            }
            setTargetNeutral();
            ensureRaf();
        }

        // Event listeners
        stage.addEventListener('pointerenter', onEnter);
        stage.addEventListener('pointermove', onMove);
        stage.addEventListener('pointerleave', onLeave);

        window.addEventListener('resize', updateRect, { passive: true });
        window.addEventListener('scroll', updateRect, { passive: true });

        // Initial state
        setTargetNeutral();
        applyCur();

        // Mark as initialized
        stage.dataset.initialized = 'true';
    }

    function initAllCards() {
        const stages = document.querySelectorAll('.king-addons-holo-card-stage:not([data-initialized="true"])');
        stages.forEach(initHolographicCard);
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllCards);
    } else {
        initAllCards();
    }

    // Elementor editor support
    if (typeof window.elementorFrontend !== 'undefined') {
        window.addEventListener('elementor/frontend/init', function () {
            if (window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/king-addons-holographic-card.default', function ($scope) {
                    const stage = $scope[0].querySelector('.king-addons-holo-card-stage');
                    if (stage) {
                        stage.removeAttribute('data-initialized');
                        initHolographicCard(stage);
                    }
                });
            }
        });
    }

    // MutationObserver for dynamic content
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) {
                        if (node.classList && node.classList.contains('king-addons-holo-card-stage')) {
                            initHolographicCard(node);
                        }
                        const stages = node.querySelectorAll && node.querySelectorAll('.king-addons-holo-card-stage:not([data-initialized="true"])');
                        if (stages) {
                            stages.forEach(initHolographicCard);
                        }
                    }
                });
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
})();
