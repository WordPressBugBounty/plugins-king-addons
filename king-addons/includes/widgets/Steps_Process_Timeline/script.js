"use strict";
(function ($) {
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const initStepsTimeline = ($scope) => {
        const root = $scope.find('.king-addons-steps-timeline')[0];
        if (!root) {
            return;
        }

        let options = {};
        try {
            options = JSON.parse(root.dataset.options || '{}');
        } catch (e) {
            options = {};
        }

        const hasFeatures = !!(
            options.activeOnScroll ||
            options.anchorLinking ||
            options.anchorSync ||
            options.sticky ||
            options.reveal
        );

        if (!hasFeatures) {
            return;
        }

        const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const isEditor = window.elementorFrontend && elementorFrontend.isEditMode && elementorFrontend.isEditMode();

        if (isEditor) {
            root.classList.remove('king-addons-steps--sticky');
            options.sticky = false;
        }

        const steps = Array.from(root.querySelectorAll('.king-addons-step'));
        const list = root.querySelector('.king-addons-steps__list');
        const track = root.querySelector('.king-addons-steps__track');
        const line = root.querySelector('.king-addons-steps__line');
        const progress = root.querySelector('.king-addons-steps__progress');

        if (!steps.length || !list || !track || !line || !progress) {
            return;
        }

        const layout = options.layout || 'vertical';
        let activeIndex = -1;
        let ticking = false;

        const isHorizontalLayout = () => window.getComputedStyle(list).display === 'flex';

        const updateLineVisibility = () => {
            const hideLine = options.wrap && isHorizontalLayout();
            const display = hideLine ? 'none' : '';

            line.style.display = display;
            progress.style.display = display;

            return !hideLine;
        };

        const anchorSteps = steps
            .map((step, index) => {
                const anchorId = step.dataset.anchor || '';
                const target = anchorId ? document.getElementById(anchorId) : null;
                return { step, anchorId, target, index };
            })
            .filter((item) => item.anchorId);

        const getScrollTop = () => window.pageYOffset || document.documentElement.scrollTop || 0;

        const getOffsetTop = (element) => {
            const rect = element.getBoundingClientRect();
            return rect.top + getScrollTop();
        };

        const updateLineBounds = () => {
            if (!line || steps.length < 2) {
                return;
            }

            if (!updateLineVisibility()) {
                return;
            }

            const firstMarker = steps[0].querySelector('.king-addons-step__marker');
            const lastMarker = steps[steps.length - 1].querySelector('.king-addons-step__marker');

            if (!firstMarker || !lastMarker) {
                return;
            }

            const trackRect = track.getBoundingClientRect();
            const firstRect = firstMarker.getBoundingClientRect();
            const lastRect = lastMarker.getBoundingClientRect();

            if (isHorizontalLayout()) {
                const top = firstRect.top - trackRect.top + firstRect.height / 2;
                const left = firstRect.left - trackRect.left + firstRect.width / 2;
                const width = Math.max(lastRect.left - firstRect.left, 0);

                line.style.top = `${top}px`;
                line.style.left = `${left}px`;
                line.style.width = `${width}px`;
                line.style.height = '';
            } else {
                const left = firstRect.left - trackRect.left + firstRect.width / 2;
                const top = firstRect.top - trackRect.top + firstRect.height / 2;
                const height = Math.max(lastRect.top - firstRect.top, 0);

                line.style.left = `${left}px`;
                line.style.top = `${top}px`;
                line.style.height = `${height}px`;
                line.style.width = '';
            }
        };

        const getProgressRatio = () => {
            if (!steps.length) {
                return 0;
            }

            const scrollTop = getScrollTop();
            const viewportCenter = scrollTop + window.innerHeight * 0.5;
            let start = getOffsetTop(steps[0]);
            let end = getOffsetTop(steps[steps.length - 1]);

            if (options.progressMode === 'widget') {
                start = getOffsetTop(root);
                end = getOffsetTop(root) + root.offsetHeight;
            }

            if (end <= start) {
                return 0;
            }

            return clamp((viewportCenter - start) / (end - start), 0, 1);
        };

        const updateProgress = () => {
            if (!progress || !line) {
                return;
            }

            if (!updateLineVisibility()) {
                return;
            }

            const ratio = getProgressRatio();
            const lineRect = line.getBoundingClientRect();

            if (isHorizontalLayout()) {
                progress.style.width = `${lineRect.width * ratio}px`;
                progress.style.height = '100%';
            } else {
                progress.style.height = `${lineRect.height * ratio}px`;
                progress.style.width = '100%';
            }
        };

        const getClosestEnabledIndex = (index) => {
            if (!steps.length) {
                return 0;
            }

            for (let i = index; i < steps.length; i += 1) {
                if (!steps[i].classList.contains('is-disabled')) {
                    return i;
                }
            }

            for (let i = index - 1; i >= 0; i -= 1) {
                if (!steps[i].classList.contains('is-disabled')) {
                    return i;
                }
            }

            return index;
        };

        const setActive = (index) => {
            const safeIndex = clamp(index, 0, steps.length - 1);
            const nextIndex = getClosestEnabledIndex(safeIndex);

            if (nextIndex === activeIndex) {
                return;
            }

            activeIndex = nextIndex;

            steps.forEach((step, idx) => {
                step.classList.toggle('is-active', idx === activeIndex);
                step.classList.toggle('is-complete', idx < activeIndex);

                if (idx === activeIndex) {
                    step.setAttribute('aria-current', 'step');
                } else {
                    step.removeAttribute('aria-current');
                }

                if (options.reveal && options.revealTrigger === 'active') {
                    if (idx <= activeIndex) {
                        step.classList.add('is-revealed');
                    }
                }
            });
        };

        const getActiveIndex = () => {
            const targetList = options.anchorSync && anchorSteps.length
                ? anchorSteps.filter((item) => item.target)
                : steps.map((step, index) => ({ target: step, index }));

            const scrollTop = getScrollTop();
            const threshold = scrollTop + window.innerHeight * 0.35 + (options.anchorOffset || 0);
            let active = 0;

            targetList.forEach((item, idx) => {
                const target = item.target || item;
                const stepIndex = item.index !== undefined ? item.index : idx;

                if (!target) {
                    return;
                }

                const top = getOffsetTop(target);
                if (top <= threshold) {
                    active = stepIndex;
                }
            });

            return active;
        };

        const handleScroll = () => {
            if (ticking) {
                return;
            }

            ticking = true;

            window.requestAnimationFrame(() => {
                if (options.activeOnScroll || options.anchorSync) {
                    setActive(getActiveIndex());
                }

                updateProgress();
                ticking = false;
            });
        };

        const scrollToTarget = (target, anchorId) => {
            if (!target) {
                return;
            }

            if (isEditor) {
                return;
            }

            const offset = options.anchorOffset || 0;
            const targetTop = getOffsetTop(target) - offset;
            const behavior = prefersReduced ? 'auto' : 'smooth';

            window.scrollTo({
                top: targetTop,
                behavior,
            });

            if (anchorId) {
                try {
                    window.history.pushState(null, '', `#${anchorId}`);
                } catch (e) {
                    window.location.hash = anchorId;
                }
            }
        };

        if (options.anchorLinking && anchorSteps.length) {
            anchorSteps.forEach((item) => {
                item.step.addEventListener('click', (event) => {
                    if (item.step.classList.contains('is-disabled')) {
                        return;
                    }

                    if (event.target.closest('a')) {
                        return;
                    }

                    if (!item.target) {
                        return;
                    }

                    event.preventDefault();
                    setActive(item.index);
                    scrollToTarget(item.target, item.anchorId);
                });

                item.step.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    if (item.step.classList.contains('is-disabled')) {
                        return;
                    }

                    if (!item.target) {
                        return;
                    }

                    event.preventDefault();
                    setActive(item.index);
                    scrollToTarget(item.target, item.anchorId);
                });
            });
        }

        if (options.reveal) {
            const canRevealOnActive = options.revealTrigger === 'active' && (options.activeOnScroll || options.anchorSync);

            root.classList.add(`king-addons-steps-reveal-${options.revealType || 'fade-up'}`);
            steps.forEach((step, idx) => {
                step.classList.add('is-reveal');
                if (options.revealStagger && !prefersReduced) {
                    step.style.transitionDelay = `${idx * options.revealStagger}ms`;
                }
            });

            if (prefersReduced) {
                steps.forEach((step) => step.classList.add('is-revealed'));
            } else if (!canRevealOnActive && options.revealTrigger === 'viewport' && 'IntersectionObserver' in window) {
                const revealObserver = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-revealed');
                            revealObserver.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.2 });

                steps.forEach((step) => revealObserver.observe(step));
            } else if (!canRevealOnActive && options.revealTrigger === 'viewport') {
                steps.forEach((step) => step.classList.add('is-revealed'));
            } else if (!canRevealOnActive) {
                steps.forEach((step) => step.classList.add('is-revealed'));
            }
        }

        if (options.deepLink && options.anchorLinking && window.location.hash) {
            const hash = window.location.hash.replace('#', '');
            const match = anchorSteps.find((item) => item.anchorId === hash);
            if (match && match.target) {
                setTimeout(() => {
                    setActive(match.index);
                    scrollToTarget(match.target, match.anchorId);
                }, 150);
            }
        }

        updateLineBounds();
        updateProgress();

        if (options.activeOnScroll || options.anchorSync) {
            setActive(getActiveIndex());
        } else {
            setActive(0);
        }

        if (options.activeOnScroll || options.anchorSync || options.sticky) {
            window.addEventListener('scroll', handleScroll, { passive: true });
        }

        window.addEventListener('resize', () => {
            updateLineBounds();
            updateProgress();
        });
    };

    $(window).on('elementor/frontend/init', () => {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/king-addons-steps-process-timeline.default',
            initStepsTimeline
        );
    });
})(jQuery);
