(function ($) {
    'use strict';

    const debounce = (fn, wait) => {
        let timer;
        return (...args) => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => fn.apply(null, args), wait);
        };
    };

    const initTestimonialsWall = (root) => {
        const scope = root instanceof HTMLElement ? root : document;
        const wrappers = scope.querySelectorAll('.king-addons-testimonials-wall');

        wrappers.forEach((wrapper) => {
            if (wrapper.dataset.twInit === 'yes') {
                return;
            }
            wrapper.dataset.twInit = 'yes';

            const grid = wrapper.querySelector('.king-addons-testimonials-wall__grid');
            if (!grid) {
                return;
            }

            const items = Array.from(grid.querySelectorAll('.king-addons-testimonials-wall__item'));
            const loadMoreBtn = wrapper.querySelector('.king-addons-testimonials-wall__load-more-btn');
            const searchInput = wrapper.querySelector('.king-addons-testimonials-wall__search-input');
            const ratingSelect = wrapper.querySelector('.king-addons-testimonials-wall__rating-select');
            const chipButtons = Array.from(wrapper.querySelectorAll('.king-addons-testimonials-wall__filter-chip'));
            const categoryButtons = chipButtons.filter((btn) => btn.dataset.filter !== undefined);
            const ratingButtons = chipButtons.filter((btn) => btn.dataset.rating !== undefined);

            const isEditor = window.elementorFrontend && typeof elementorFrontend.isEditMode === 'function'
                ? elementorFrontend.isEditMode()
                : false;
            const allowMasonry = wrapper.dataset.layout === 'masonry'
                && (!isEditor || wrapper.dataset.editorMasonry === 'yes');
            const readMoreEnabled = wrapper.dataset.readMore === 'yes';
            const skeletonEnabled = wrapper.dataset.skeleton === 'yes';
            const readMoreText = wrapper.dataset.readMoreText || 'Read more';
            const readLessText = wrapper.dataset.readLessText || 'Read less';

            wrapper.classList.toggle('is-masonry', allowMasonry);

            const state = {
                category: '*',
                rating: 'all',
                search: '',
                visibleCount: Math.min(parseInt(wrapper.dataset.initial || items.length, 10), items.length),
                perLoad: Math.max(parseInt(wrapper.dataset.perLoad || 1, 10), 1),
                loading: false,
            };

            const updateReadMoreLineHeight = () => {
                if (!readMoreEnabled) {
                    return;
                }
                const sampleText = wrapper.querySelector('.king-addons-testimonials-wall__text');
                if (!sampleText) {
                    return;
                }
                const styles = window.getComputedStyle(sampleText);
                let lineHeight = parseFloat(styles.lineHeight);
                if (Number.isNaN(lineHeight)) {
                    const fontSize = parseFloat(styles.fontSize) || 16;
                    lineHeight = fontSize * 1.6;
                }
                wrapper.style.setProperty('--ka-tw-text-line-height', `${lineHeight}px`);
            };

            const scheduleReadMoreLineHeight = debounce(updateReadMoreLineHeight, 120);

            items.forEach((item, index) => {
                item.dataset.index = String(index);
                if (!item.dataset.search) {
                    item.dataset.search = (item.textContent || '').trim();
                }
            });

            if (readMoreEnabled) {
                const lineClamp = parseInt(wrapper.dataset.readMoreLines || '4', 10);
                if (!Number.isNaN(lineClamp) && lineClamp > 0) {
                    wrapper.style.setProperty('--ka-tw-read-more-lines', String(lineClamp));
                }
                updateReadMoreLineHeight();
                window.addEventListener('resize', scheduleReadMoreLineHeight);
            }

            const setActiveButton = (buttons, active) => {
                buttons.forEach((button) => {
                    const isActive = button === active;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
            };

            const matchesCategory = (item) => {
                if (!state.category || state.category === '*') {
                    return true;
                }
                const categories = (item.dataset.categories || '').split(' ').filter(Boolean);
                return categories.includes(state.category);
            };

            const matchesRating = (item) => {
                if (!state.rating || state.rating === 'all') {
                    return true;
                }
                const ratingValue = parseInt(item.dataset.rating || '0', 10);
                const threshold = parseInt(state.rating, 10);
                return ratingValue >= threshold;
            };

            const matchesSearch = (item) => {
                if (!state.search) {
                    return true;
                }
                const haystack = (item.dataset.search || '').toLowerCase();
                return haystack.includes(state.search);
            };

            const clearHideTimer = (item) => {
                if (item._twHideTimer) {
                    window.clearTimeout(item._twHideTimer);
                    item._twHideTimer = null;
                }
            };

            const showItem = (item) => {
                clearHideTimer(item);
                if (!item.classList.contains('is-hidden')) {
                    item.setAttribute('aria-hidden', 'false');
                    return;
                }
                item.classList.remove('is-hidden');
                item.classList.remove('is-fading-out');
                item.classList.add('is-revealing');
                item.setAttribute('aria-hidden', 'false');
                window.requestAnimationFrame(() => {
                    item.classList.remove('is-revealing');
                });
            };

            const hideItem = (item, immediate = false) => {
                clearHideTimer(item);
                if (item.classList.contains('is-hidden')) {
                    item.setAttribute('aria-hidden', 'true');
                    return;
                }
                if (immediate) {
                    item.classList.add('is-hidden');
                    item.classList.remove('is-fading-out');
                    item.setAttribute('aria-hidden', 'true');
                    return;
                }
                item.classList.add('is-fading-out');
                item.setAttribute('aria-hidden', 'true');
                item._twHideTimer = window.setTimeout(() => {
                    item.classList.add('is-hidden');
                    item.classList.remove('is-fading-out');
                    item._twHideTimer = null;
                }, 200);
            };

            const updateLoadMore = (totalMatches) => {
                const total = (typeof totalMatches === 'number') ? totalMatches : items.length;
                const hasMore = total > 0 && state.visibleCount < total;

                wrapper.classList.toggle('is-complete', !hasMore);
                if (loadMoreBtn) {
                    loadMoreBtn.disabled = !hasMore;
                }
            };

            const reflowMasonry = () => {
                if (!allowMasonry) {
                    items.forEach((item) => {
                        item.style.gridRowEnd = 'auto';
                    });
                    return;
                }
                const rowHeight = parseFloat(getComputedStyle(grid).getPropertyValue('--ka-tw-row')) || 8;
                items.forEach((item) => {
                    if (item.classList.contains('is-hidden')) {
                        item.style.gridRowEnd = 'auto';
                        return;
                    }
                    const height = item.getBoundingClientRect().height;
                    const span = Math.ceil(height / rowHeight);
                    item.style.gridRowEnd = `span ${span}`;
                });
            };

            const updateReadMoreVisibility = () => {
                if (!readMoreEnabled) {
                    return;
                }
                items.forEach((item) => {
                    if (item.classList.contains('is-hidden')) {
                        return;
                    }
                    const text = item.querySelector('.king-addons-testimonials-wall__text');
                    const button = item.querySelector('.king-addons-testimonials-wall__read-more');
                    if (!text || !button) {
                        return;
                    }
                    const isExpanded = item.classList.contains('is-expanded');
                    if (isExpanded) {
                        button.style.display = '';
                        button.textContent = readLessText;
                        button.setAttribute('aria-expanded', 'true');
                        return;
                    }

                    const isOverflowing = text.scrollHeight > text.clientHeight + 2;
                    button.style.display = isOverflowing ? '' : 'none';
                    button.textContent = readMoreText;
                    button.setAttribute('aria-expanded', 'false');
                });
            };

            const applyFilters = () => {
                let totalMatches = 0;

                items.forEach((item) => {
                    const matches = matchesCategory(item) && matchesRating(item) && matchesSearch(item);
                    if (matches) {
                        totalMatches += 1;
                        if (totalMatches <= state.visibleCount) {
                            showItem(item);
                        } else {
                            hideItem(item, true);
                        }
                    } else {
                        hideItem(item, false);
                    }
                });

                wrapper.classList.toggle('is-empty', totalMatches === 0);
                updateLoadMore(totalMatches);
                updateReadMoreVisibility();
                window.requestAnimationFrame(reflowMasonry);
            };

            if (categoryButtons.length) {
                wrapper.addEventListener('click', (event) => {
                    const button = event.target.closest('.king-addons-testimonials-wall__filter-chip');
                    if (!button || button.dataset.filter === undefined) {
                        return;
                    }
                    state.category = button.dataset.filter || '*';
                    setActiveButton(categoryButtons, button);
                    applyFilters();
                });
            }

            if (ratingButtons.length) {
                wrapper.addEventListener('click', (event) => {
                    const button = event.target.closest('.king-addons-testimonials-wall__filter-chip');
                    if (!button || button.dataset.rating === undefined) {
                        return;
                    }
                    state.rating = button.dataset.rating || 'all';
                    setActiveButton(ratingButtons, button);
                    applyFilters();
                });
            }

            if (ratingSelect) {
                ratingSelect.addEventListener('change', () => {
                    state.rating = ratingSelect.value || 'all';
                    applyFilters();
                });
            }

            if (searchInput) {
                const onSearch = debounce(() => {
                    state.search = (searchInput.value || '').trim().toLowerCase();
                    applyFilters();
                }, 250);
                searchInput.addEventListener('input', onSearch);
            }

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', () => {
                    if (state.loading) {
                        return;
                    }
                    state.loading = true;
                    const delay = skeletonEnabled ? 350 : 0;
                    const loadText = loadMoreBtn.dataset.loadText || loadMoreBtn.textContent;
                    const loadingText = loadMoreBtn.dataset.loadingText || loadText;

                    if (skeletonEnabled) {
                        wrapper.classList.add('is-loading');
                    }
                    loadMoreBtn.textContent = loadingText;
                    loadMoreBtn.disabled = true;

                    window.setTimeout(() => {
                        state.visibleCount = Math.min(state.visibleCount + state.perLoad, items.length);
                        applyFilters();
                        wrapper.classList.remove('is-loading');
                        loadMoreBtn.textContent = loadText;
                        state.loading = false;
                    }, delay);
                });
            }

            if (readMoreEnabled) {
                wrapper.addEventListener('click', (event) => {
                    const button = event.target.closest('.king-addons-testimonials-wall__read-more');
                    if (!button) {
                        return;
                    }
                    const item = button.closest('.king-addons-testimonials-wall__item');
                    if (!item) {
                        return;
                    }
                    const expanded = item.classList.toggle('is-expanded');
                    button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                    button.textContent = expanded ? readLessText : readMoreText;
                    window.requestAnimationFrame(reflowMasonry);
                });
            }

            if (allowMasonry) {
                if ('ResizeObserver' in window) {
                    const resizeObserver = new ResizeObserver(() => reflowMasonry());
                    resizeObserver.observe(grid);
                    items.forEach((item) => resizeObserver.observe(item));
                } else {
                    window.addEventListener('resize', () => reflowMasonry());
                }

                const images = grid.querySelectorAll('img');
                images.forEach((img) => {
                    if (img.complete) {
                        return;
                    }
                    img.addEventListener('load', () => reflowMasonry(), { once: true });
                });
            }

            applyFilters();
        });
    };

    if (window.elementorFrontend && elementorFrontend.hooks) {
        $(window).on('elementor/frontend/init', () => {
            elementorFrontend.hooks.addAction(
                'frontend/element_ready/king-addons-testimonials-wall.default',
                ($scope) => {
                    initTestimonialsWall($scope[0]);
                }
            );
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initTestimonialsWall(document));
    } else {
        initTestimonialsWall(document);
    }
})(jQuery);
