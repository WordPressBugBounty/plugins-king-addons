(function () {
    'use strict';

    const setGridVars = (grid) => {
        grid.style.setProperty('--ka-grid-desktop', grid.dataset.colsDesktop || 4);
        grid.style.setProperty('--ka-grid-tablet', grid.dataset.colsTablet || 3);
        grid.style.setProperty('--ka-grid-mobile', grid.dataset.colsMobile || 2);
        grid.style.setProperty('--ka-masonry-row', '8px');
    };

    const appendItems = (grid, html, replace = false) => {
        const track = grid.querySelector('.ka-woo-products-grid__track') || grid;
        const temp = document.createElement('div');
        temp.innerHTML = html;
        const items = temp.querySelectorAll('.ka-woo-products-grid__item');
        if (replace) {
            track.innerHTML = '';
        }
        items.forEach((item) => track.appendChild(item));
        return items.length;
    };

    const reflowMasonry = (grid) => {
        if (grid.dataset.layoutType !== 'masonry') return;
        const rowHeight = parseFloat(getComputedStyle(grid).getPropertyValue('--ka-masonry-row')) || 8;
        const items = grid.querySelectorAll('.ka-woo-products-grid__item');
        items.forEach((item) => {
            const span = Math.ceil(item.getBoundingClientRect().height / rowHeight);
            item.style.gridRowEnd = `span ${span}`;
        });
    };

    const setupMasonry = (grid) => {
        if (grid.dataset.layoutType !== 'masonry') return;
        if (typeof ResizeObserver === 'undefined') return;
        const resizeObserver = new ResizeObserver(() => reflowMasonry(grid));
        resizeObserver.observe(grid);
        (grid.querySelector('.ka-woo-products-grid__track') || grid).querySelectorAll('img').forEach((img) => {
            if (img.complete) {
                reflowMasonry(grid);
            } else {
                img.addEventListener('load', () => reflowMasonry(grid));
            }
        });
        reflowMasonry(grid);
    };

    const setupSlider = (grid) => {
        if (grid.dataset.layoutType !== 'slider') return;
        const parent = grid.parentElement;
        if (!parent) return;
        const track = grid.querySelector('.ka-woo-products-grid__track') || grid;
        const existingNav = parent.querySelector('.ka-woo-products-grid__slider-nav');
        if (existingNav) {
            existingNav.remove();
        }
        const slides = Array.from(track.children);
        if (!slides.length) return;

        grid.classList.add('ka-woo-products-grid--slider');
        slides.forEach((slide) => {
            slide.classList.add('ka-woo-products-grid__slide');
        });

        let index = 0;
        const loop = grid.dataset.sliderLoop === 'true';
        const autoplay = grid.dataset.sliderAutoplay === 'true';
        const speed = parseInt(grid.dataset.sliderAutoplaySpeed || '5000', 10);
        const skin = grid.dataset.sliderSkin || 'arrows';
        let timer;
        let isDragging = false;
        let startX = 0;
        let currentX = 0;

        const dots = [];

        const update = () => {
            const offset = index * 100;
            track.style.transform = `translateX(-${offset}%)`;
            dots.forEach((dot, idx) => {
                dot.classList.toggle('is-active', idx === index);
            });
        };

        const go = (dir) => {
            const last = slides.length - 1;
            let next = index + dir;
            if (next > last) {
                next = loop ? 0 : last;
            } else if (next < 0) {
                next = loop ? last : 0;
            }
            if (next === index && !loop) return;
            index = next;
            update();
        };

        const nav = document.createElement('div');
        nav.className = 'ka-woo-products-grid__slider-nav';
        const dotsWrap = document.createElement('div');
        dotsWrap.className = 'ka-woo-products-grid__slider-dots';
        slides.forEach((_, idx) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'ka-woo-products-grid__slider-dot';
            dot.addEventListener('click', () => {
                index = idx;
                update();
                resetAutoplay();
            });
            dotsWrap.appendChild(dot);
            dots.push(dot);
        });

        const controls = document.createElement('div');
        controls.className = 'ka-woo-products-grid__slider-controls';
        const prev = document.createElement('button');
        prev.type = 'button';
        prev.className = 'ka-woo-products-grid__slider-btn ka-woo-products-grid__slider-btn--prev';
        prev.textContent = '‹';
        prev.addEventListener('click', () => {
            go(-1);
            resetAutoplay();
        });
        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'ka-woo-products-grid__slider-btn ka-woo-products-grid__slider-btn--next';
        next.textContent = '›';
        next.addEventListener('click', () => {
            go(1);
            resetAutoplay();
        });

        if (skin === 'arrows' || skin === 'both') {
            controls.appendChild(prev);
            controls.appendChild(next);
        }
        if (skin === 'dots' || skin === 'both') {
            nav.appendChild(dotsWrap);
        }
        if (controls.children.length) {
            nav.appendChild(controls);
        }
        parent.appendChild(nav);

        const stopAutoplay = () => {
            if (timer) {
                window.clearInterval(timer);
                timer = undefined;
            }
        };
        const startAutoplay = () => {
            if (!autoplay) return;
            stopAutoplay();
            timer = window.setInterval(() => go(1), speed);
        };
        const resetAutoplay = () => {
            stopAutoplay();
            startAutoplay();
        };

        grid.addEventListener('mouseenter', stopAutoplay);
        grid.addEventListener('mouseleave', startAutoplay);

        const getPointerX = (event) => (event.touches && event.touches.length ? event.touches[0].clientX : event.clientX);

        const onPointerDown = (event) => {
            isDragging = true;
            startX = getPointerX(event);
            currentX = startX;
            track.style.transition = 'none';
            event.preventDefault();
        };

        const onPointerMove = (event) => {
            if (!isDragging) return;
            currentX = getPointerX(event);
        };

        const onPointerUp = () => {
            if (!isDragging) return;
            const delta = currentX - startX;
            track.style.transition = '';
            if (Math.abs(delta) > 40) {
                if (delta < 0) {
                    go(1);
                } else {
                    go(-1);
                }
                resetAutoplay();
            } else {
                update();
            }
            isDragging = false;
        };

        track.addEventListener('mousedown', onPointerDown);
        track.addEventListener('mousemove', onPointerMove);
        track.addEventListener('mouseup', onPointerUp);
        track.addEventListener('mouseleave', () => {
            if (isDragging) {
                onPointerUp();
            }
        });
        track.addEventListener('touchstart', onPointerDown, { passive: true });
        track.addEventListener('touchmove', onPointerMove, { passive: true });
        track.addEventListener('touchend', onPointerUp);
        track.addEventListener('touchcancel', onPointerUp);

        update();
        startAutoplay();
    };

    const buildFormData = (grid, page) => {
        const boolVal = (value) => (value === 'true' || value === '1' ? '1' : '');
        const form = new FormData();
        form.append('action', 'ka_products_grid');
        form.append('nonce', grid.dataset.nonce || '');
        form.append('page', page);
        form.append('per_page', grid.dataset.perPage || '8');
        form.append('order', grid.dataset.order || 'DESC');
        form.append('orderby', grid.dataset.orderby || 'date');
        form.append('show_rating', boolVal(grid.dataset.showRating));
        form.append('show_excerpt', boolVal(grid.dataset.showExcerpt));
        form.append('excerpt_length', grid.dataset.excerptLength || '15');
        form.append('show_badge', boolVal(grid.dataset.showBadge));
        form.append('show_best_badge', boolVal(grid.dataset.showBestBadge));
        form.append('show_custom_badge', boolVal(grid.dataset.showCustomBadge));
        form.append('custom_badge_text', grid.dataset.customBadgeText || '');
        form.append('show_brand', boolVal(grid.dataset.showBrand));
        form.append('show_sku', boolVal(grid.dataset.showSku));
        form.append('card_layout', grid.dataset.cardLayout || 'classic');

        const filters = grid.dataset.filters ? grid.dataset.filters : '';
        if (filters) {
            form.append('filters', filters);
        }
        return form;
    };

    const fetchPage = (grid, page, replace = false) =>
        fetch(grid.dataset.ajaxUrl, {
            method: 'POST',
            body: buildFormData(grid, page),
        }).then((res) => res.json());

    const bindLightbox = (grid, rebind = false) => {
        if (!rebind && grid.dataset.lightboxBound === 'true') return;
        grid.dataset.lightboxBound = 'true';
        const overlayId = 'ka-woo-products-grid-lightbox';
        let overlay = document.getElementById(overlayId);
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = overlayId;
            overlay.className = 'ka-woo-products-grid__lightbox';
            overlay.innerHTML = `
                <button class="ka-woo-products-grid__lightbox-close" type="button" aria-label="Close">×</button>
                <figure class="ka-woo-products-grid__lightbox-figure">
                    <img src="" alt="" />
                    <figcaption></figcaption>
                </figure>
            `;
            document.body.appendChild(overlay);
        }
        const imgEl = overlay.querySelector('img');
        const captionEl = overlay.querySelector('figcaption');
        const closeBtn = overlay.querySelector('.ka-woo-products-grid__lightbox-close');

        const close = () => {
            overlay.classList.remove('is-open');
            document.body.classList.remove('ka-woo-products-grid--lock');
        };

        closeBtn.addEventListener('click', close);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                close();
            }
        });
        document.addEventListener('keyup', (e) => {
            if (e.key === 'Escape') {
                close();
            }
        });

        grid.addEventListener('click', (e) => {
            const link = e.target.closest('.ka-woo-products-grid__thumb');
            if (!link || !grid.contains(link)) return;
            const full = link.dataset.full || link.href;
            if (!full) return;
            e.preventDefault();
            if (imgEl) {
                imgEl.src = full;
            }
            if (captionEl) {
                captionEl.textContent = link.dataset.caption || '';
            }
            overlay.classList.add('is-open');
            document.body.classList.add('ka-woo-products-grid--lock');
        });
    };

    const handleAjax = (grid) => {
        const paginationType = grid.dataset.paginationType;
        if (!paginationType || paginationType === 'none' || paginationType === 'numbers') return;

        const parent = grid.parentElement;
        if (!parent) return;
        const btn = parent.querySelector('.ka-woo-products-grid__load-more');
        let page = parseInt(grid.dataset.page || '1', 10);
        const max = () => parseInt(grid.dataset.maxPages || '1', 10);
        let sentinelObserver;
        let sentinelEl;
        let isProcessing = false;

        const setButtonState = (state) => {
            if (!btn) return;
            const labelEl = btn.querySelector('.ka-woo-products-grid__load-more-label');
            const spinner = btn.querySelector('.ka-woo-products-grid__spinner');
            const defaultText = btn.dataset.defaultText || btn.textContent || '';
            const loadingText = btn.dataset.loadingText || 'Loading…';
            if (state === 'loading') {
                btn.disabled = true;
                if (labelEl) labelEl.textContent = loadingText;
                spinner?.classList.add('is-visible');
                btn.classList.add('is-loading');
            } else if (state === 'ready') {
                btn.disabled = false;
                if (labelEl) labelEl.textContent = defaultText;
                spinner?.classList.remove('is-visible');
                btn.classList.remove('is-loading');
            } else if (state === 'done') {
                spinner?.classList.remove('is-visible');
                btn.classList.remove('is-loading');
                btn.remove();
            }
        };

        const cleanupSentinel = () => {
            if (sentinelObserver && sentinelEl) {
                sentinelObserver.unobserve(sentinelEl);
            }
            if (sentinelEl) {
                sentinelEl.remove();
            }
        };

        const process = (replace = false) => {
            if (isProcessing) return;
            if (page >= max() && !replace) {
                setButtonState('done');
                cleanupSentinel();
                return;
            }
            isProcessing = true;
            setButtonState('loading');
            fetchPage(grid, replace ? 1 : page + 1, replace)
                .then((res) => {
                    if (!res || !res.success || !res.data) {
                        setButtonState('done');
                        cleanupSentinel();
                        return;
                    }
                    const added = appendItems(grid, res.data.html || '', replace);
                    const maxPages = res.data.max_pages ? parseInt(res.data.max_pages, 10) : max();
                    grid.dataset.maxPages = maxPages;
                    if (replace) {
                        page = 1;
                        grid.dataset.page = '1';
                    } else {
                        page += 1;
                        grid.dataset.page = page;
                    }
                    if (page >= maxPages || added === 0) {
                        setButtonState('done');
                        cleanupSentinel();
                    } else {
                        setButtonState('ready');
                    }
                    reflowMasonry(grid);
                    setupSlider(grid);
                    bindLightbox(grid, true);
                })
                .catch(() => {
                    setButtonState('ready');
                })
                .finally(() => {
                    isProcessing = false;
                });
        };

        if (btn) {
            btn.addEventListener('click', () => process(false));
        }

        if (paginationType === 'infinite') {
            if (typeof IntersectionObserver === 'undefined') {
                return;
            }
            sentinelEl = document.createElement('div');
            sentinelEl.className = 'ka-woo-products-grid__sentinel';
            parent.appendChild(sentinelEl);
            sentinelObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            process(false);
                        }
                    });
                },
                { rootMargin: '200px' }
            );
            sentinelObserver.observe(sentinelEl);
        }

        const applyFilters = (filters) => {
            if (filters && typeof filters === 'object') {
                grid.dataset.filters = JSON.stringify(filters);
            } else {
                grid.dataset.filters = '';
            }
            grid.dataset.page = '1';
            process(true);
        };

        const applySorting = (orderby, order) => {
            if (orderby) {
                grid.dataset.orderby = orderby;
            }
            if (order) {
                grid.dataset.order = order;
            }
            grid.dataset.page = '1';
            process(true);
        };

        window.addEventListener('kaWooGridApplyFilters', (event) => {
            const detail = event.detail || {};
            if (detail.queryId && detail.queryId !== grid.dataset.queryId) {
                return;
            }
            applyFilters(detail.filters || {});
        });

        document.addEventListener('kingaddons:filters:apply', (event) => {
            const detail = event.detail || {};
            if (!detail.queryId || detail.queryId !== grid.dataset.queryId) {
                return;
            }
            applyFilters(detail.filters || {});
        });

        document.addEventListener('kingaddons:filters:reset', (event) => {
            const detail = event.detail || {};
            if (!detail.queryId || detail.queryId !== grid.dataset.queryId) {
                return;
            }
            applyFilters({});
        });

        document.addEventListener('kingaddons:sorting:apply', (event) => {
            const detail = event.detail || {};
            if (detail.queryId && detail.queryId !== grid.dataset.queryId) {
                return;
            }
            applySorting(detail.orderby, detail.order);
        });
    };

    const initGrid = (grid) => {
        if (grid.dataset.kngBound === 'true') return;
        grid.dataset.kngBound = 'true';
        setGridVars(grid);
        setupMasonry(grid);
        setupSlider(grid);
        bindLightbox(grid);
        handleAjax(grid);
    };

    const initAll = (context) => {
        const root = context && context.querySelectorAll ? context : document;
        root.querySelectorAll('.ka-woo-products-grid').forEach((grid) => initGrid(grid));
    };

    const observeNewGrids = () => {
        if (!('MutationObserver' in window)) return;
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (!(node instanceof HTMLElement)) return;
                    if (node.classList.contains('ka-woo-products-grid')) {
                        initGrid(node);
                    }
                    node.querySelectorAll?.('.ka-woo-products-grid').forEach((grid) => initGrid(grid));
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    };

    document.addEventListener('DOMContentLoaded', () => {
        initAll(document);
        observeNewGrids();
    });

    const onElementorReady = () => {
        if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
            return;
        }
        window.elementorFrontend.hooks.addAction('frontend/element_ready/woo_products_grid.default', (scope) => {
            const root = scope && scope[0] ? scope[0] : scope;
            initAll(root || document);
        });
    };

    if (document.readyState === 'complete') {
        onElementorReady();
    } else {
        window.addEventListener('DOMContentLoaded', onElementorReady);
    }
})();



