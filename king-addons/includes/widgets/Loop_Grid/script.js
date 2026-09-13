(function () {
    'use strict';

    var INIT_FLAG = 'kaLoopGridReady';

    /**
     * Wire one grid: load more, filters, masonry, carousel.
     *
     * @param {HTMLElement} grid Grid root.
     */
    function initGrid(grid) {
        if (!grid || grid.dataset[INIT_FLAG] === '1') {
            return;
        }
        grid.dataset[INIT_FLAG] = '1';

        var filters = grid.querySelectorAll('.king-addons-loop-grid__filter');
        Array.prototype.forEach.call(filters, function (filter) {
            filter.addEventListener('click', function () {
                applyFilter(grid, filter);
            });
        });

        grid.addEventListener('click', function (event) {
            var more = event.target.closest('.king-addons-loop-grid__load-more');
            if (more && grid.contains(more)) {
                if (more.disabled || more.hasAttribute('disabled')) {
                    return;
                }
                loadPage(grid, parseInt(grid.dataset.page || '1', 10) + 1, true);
                return;
            }

            var link = event.target.closest('.king-addons-loop-grid__pagination a');
            if (!link || !grid.contains(link)) {
                return;
            }
            var href = link.getAttribute('href') || '';
            var page = 1;
            var match = href.match(/ka-loop-[^=]+=(\d+)/);
            if (match) {
                page = parseInt(match[1], 10);
            } else if (link.classList.contains('next')) {
                page = parseInt(grid.dataset.page || '1', 10) + 1;
            } else if (link.classList.contains('prev')) {
                page = Math.max(1, parseInt(grid.dataset.page || '1', 10) - 1);
            }
            event.preventDefault();
            loadPage(grid, page, false);
        });

        layoutGrid(grid);
        initCarousel(grid);
    }

    /**
     * Horizontal gutter from the widget's CSS variable (px). Other units fall
     * back to 24 so Isotope does not treat "1.5em" as 1.5px.
     *
     * @param {HTMLElement} grid Grid root.
     * @return {number}
     */
    function readGap(grid) {
        var raw = window.getComputedStyle(grid).getPropertyValue('--ka-loop-gap').trim();
        var px = parseFloat(raw);
        if (!raw || raw.indexOf('px') === -1 || isNaN(px)) {
            return 24;
        }
        return px;
    }

    /**
     * Masonry after images have loaded.
     *
     * @param {HTMLElement} grid Grid root.
     */
    function layoutGrid(grid) {
        if (grid.dataset.layout !== 'masonry') {
            return;
        }

        var items = grid.querySelector('.king-addons-loop-grid__items');
        if (!items || typeof window.IsotopeKng !== 'function') {
            return;
        }

        var run = function () {
            if (grid._kaIso) {
                grid._kaIso.reloadItems();
                grid._kaIso.layout();
                return;
            }
            grid._kaIso = new window.IsotopeKng(items, {
                itemSelector: '.king-addons-loop-grid__item',
                layoutMode: 'masonry',
                percentPosition: true,
                transitionDuration: '0.2s',
                masonry: {
                    columnWidth: '.king-addons-loop-grid__sizer',
                    gutter: readGap(grid)
                }
            });
        };

        if (typeof window.imagesLoaded === 'function') {
            window.imagesLoaded(items, run);
        } else {
            run();
        }
    }

    /**
     * Swiper for carousel layout.
     *
     * @param {HTMLElement} grid Grid root.
     */
    function initCarousel(grid) {
        if (grid.dataset.layout !== 'carousel' || typeof window.Swiper !== 'function') {
            return;
        }

        var el = grid.querySelector('.king-addons-loop-grid__swiper');
        if (!el || el.swiper) {
            return;
        }

        var config = {};
        try {
            config = JSON.parse(grid.dataset.swiper || '{}');
        } catch (error) {
            config = {};
        }

        var options = {
            slidesPerView: config.slidesMobile || 1,
            spaceBetween: config.gap || 24,
            speed: config.speed || 400,
            loop: !!config.loop,
            keyboard: { enabled: true },
            a11y: { enabled: true },
            breakpoints: {
                768: { slidesPerView: config.slidesTablet || 2 },
                1025: { slidesPerView: config.slides || 3 }
            }
        };

        if (config.autoplay) {
            options.autoplay = { delay: config.delay || 4000, disableOnInteraction: false };
        }
        if (config.arrows) {
            options.navigation = {
                nextEl: el.querySelector('.swiper-button-next'),
                prevEl: el.querySelector('.swiper-button-prev')
            };
        }
        if (config.dots) {
            options.pagination = {
                el: el.querySelector('.swiper-pagination'),
                clickable: true
            };
        }

        grid._kaSwiper = new window.Swiper(el, options);
    }

    /**
     * Switch the active filter and reload page 1.
     *
     * @param {HTMLElement} grid   Grid root.
     * @param {HTMLElement} button Filter button.
     */
    function applyFilter(grid, button) {
        var next = button.getAttribute('data-filter') || '';
        grid.dataset.filter = next;
        grid.dataset.page = '1';

        var buttons = grid.querySelectorAll('.king-addons-loop-grid__filter');
        Array.prototype.forEach.call(buttons, function (item) {
            var on = item === button;
            item.classList.toggle('is-active', on);
            item.setAttribute('aria-pressed', on ? 'true' : 'false');
        });

        var bar = grid.querySelector('.king-addons-loop-grid__filters');
        if (bar && bar.getAttribute('data-deeplink') === '1') {
            var key = 'ka-lf-' + (grid.dataset.elementId || '');
            var url = new URL(window.location.href);
            if (next) {
                url.searchParams.set(key, next);
            } else {
                url.searchParams.delete(key);
            }
            window.history.replaceState({}, '', url.toString());
        }

        loadPage(grid, 1, false);
    }

    /**
     * Fetch a page: append for load more, replace otherwise.
     *
     * @param {HTMLElement} grid   Grid root.
     * @param {number}      page   Page number.
     * @param {boolean}     append Load-more mode.
     */
    function loadPage(grid, page, append) {
        var ajaxUrl = grid.dataset.ajaxUrl;
        if (!ajaxUrl) {
            return;
        }

        var button = grid.querySelector('.king-addons-loop-grid__load-more');
        if (button) {
            button.classList.add('king-addons-loop-grid--busy');
            button.setAttribute('disabled', 'disabled');
        }

        var body = new URLSearchParams();
        body.append('action', 'ka_loop_grid');
        body.append('nonce', grid.dataset.nonce || '');
        body.append('post_id', grid.dataset.postId || '0');
        body.append('element_id', grid.dataset.elementId || '');
        body.append('page', String(page));
        body.append('context', grid.dataset.context || '{}');
        body.append('filter', grid.dataset.filter || '');

        fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload || !payload.success || !payload.data) {
                    release(button);
                    return;
                }

                var items = grid.querySelector('.king-addons-loop-grid__items');
                var empty = grid.querySelector('.king-addons-loop-grid__empty');

                if (payload.data.empty) {
                    if (items) {
                        items.querySelectorAll('.king-addons-loop-grid__item').forEach(function (node) {
                            node.remove();
                        });
                    }
                    if (!empty) {
                        empty = document.createElement('div');
                        empty.className = 'king-addons-loop-grid__empty';
                        grid.insertBefore(empty, items);
                    }
                    empty.textContent = payload.data.empty_text || '';
                    empty.hidden = false;
                } else if (items && payload.data.html) {
                    if (empty) {
                        empty.hidden = true;
                    }
                    if (append) {
                        items.insertAdjacentHTML('beforeend', payload.data.html);
                    } else {
                        var sizer = items.querySelector('.king-addons-loop-grid__sizer');
                        items.querySelectorAll('.king-addons-loop-grid__item').forEach(function (node) {
                            node.remove();
                        });
                        if (sizer) {
                            sizer.insertAdjacentHTML('afterend', payload.data.html);
                        } else {
                            items.insertAdjacentHTML('beforeend', payload.data.html);
                        }
                    }

                    if (window.elementorFrontend && window.elementorFrontend.elementsHandler) {
                        window.jQuery(items)
                            .find('.elementor-element')
                            .each(function () {
                                window.elementorFrontend.elementsHandler.runReadyTrigger(this);
                            });
                    }
                }

                var footer = grid.querySelector('.king-addons-loop-grid__footer');
                if (!append && footer) {
                    footer.innerHTML = payload.data.pagination || '';
                }

                grid.dataset.page = String(payload.data.page || page);
                grid.dataset.maxPages = String(payload.data.max_pages || grid.dataset.maxPages || '1');

                layoutGrid(grid);

                if (button) {
                    if (parseInt(grid.dataset.page, 10) >= parseInt(grid.dataset.maxPages, 10)) {
                        finish(grid, button);
                    } else {
                        release(button);
                    }
                }
            })
            .catch(function () {
                release(button);
            });
    }

    /**
     * Re-enable the button.
     *
     * @param {HTMLElement|null} button Button.
     */
    function release(button) {
        if (!button) {
            return;
        }
        button.classList.remove('king-addons-loop-grid--busy');
        button.removeAttribute('disabled');
    }

    /**
     * Nothing left to load.
     *
     * @param {HTMLElement} grid   Grid root.
     * @param {HTMLElement} button Load more button.
     */
    function finish(grid, button) {
        button.remove();
        var done = grid.querySelector('.king-addons-loop-grid__all-loaded');
        if (done) {
            done.hidden = false;
        }
    }

    /**
     * Find every grid on the page.
     *
     * @param {ParentNode} scope Root to search.
     */
    function initAll(scope) {
        var root = scope || document;
        var grids = root.querySelectorAll('[data-ka-loop-grid]');
        Array.prototype.forEach.call(grids, initGrid);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initAll(document);
        });
    } else {
        initAll(document);
    }

    window.addEventListener('elementor/frontend/init', function () {
        if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
            return;
        }

        window.elementorFrontend.hooks.addAction(
            'frontend/element_ready/king-addons-loop-grid.default',
            function ($scope) {
                initAll($scope && $scope[0] ? $scope[0] : document);
            }
        );
    });
})();
