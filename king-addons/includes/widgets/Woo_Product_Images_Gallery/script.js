/**
 * Woo Product Images Gallery widget behavior.
 *
 * Provides gallery navigation, optional lightbox, and optional zoom.
 */
(function ($) {
    "use strict";

    const INIT_FLAG = "kaWooGalleryInit";
    const KEYUP_FLAG = "kaWooGalleryKeyupBound";

    // Global active lightbox controller (single active at a time).
    window.kaWooGalleryActive = window.kaWooGalleryActive || null;

    const lockScroll = () => {
        document.body.classList.add('ka-woo-gallery--lock');
    };

    const unlockScroll = () => {
        document.body.classList.remove('ka-woo-gallery--lock');
    };

    const enableZoom = (slide) => {
        if (!slide || slide.dataset.zoomBound === 'yes') return;
        if (window.matchMedia('(hover: none)').matches) return; // avoid on touch-only
        const full = slide.dataset.full;
        if (!full) return;
        const lens = document.createElement('div');
        lens.className = 'ka-woo-gallery__zoom-lens';
        lens.style.backgroundImage = `url(${full})`;
        slide.appendChild(lens);
        slide.dataset.zoomBound = 'yes';

        const move = (event) => {
            const rect = slide.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            const xPercent = Math.max(0, Math.min(100, (x / rect.width) * 100));
            const yPercent = Math.max(0, Math.min(100, (y / rect.height) * 100));
            lens.style.left = `${x - lens.offsetWidth / 2}px`;
            lens.style.top = `${y - lens.offsetHeight / 2}px`;
            lens.style.backgroundPosition = `${xPercent}% ${yPercent}%`;
            lens.classList.add('is-visible');
        };

        slide.addEventListener('mousemove', move);
        slide.addEventListener('mouseenter', () => lens.classList.add('is-visible'));
        slide.addEventListener('mouseleave', () => lens.classList.remove('is-visible'));
    };

    const initGallery = (wrapper) => {
        if (!wrapper || !wrapper.dataset) return;
        if (wrapper.dataset[INIT_FLAG] === "1") return;
        wrapper.dataset[INIT_FLAG] = "1";

        const slides = wrapper.querySelectorAll('.ka-woo-gallery__slide');
        const thumbs = wrapper.querySelectorAll('.ka-woo-gallery__thumb');
        if (!slides.length) return;

        const layout = wrapper.dataset.layout || 'slider';
        const mobileLayout = wrapper.dataset.mobileLayout || '';
        const loop = wrapper.dataset.loop === 'yes';
        const autoplay = wrapper.dataset.autoplay === 'yes';
        const speed = parseInt(wrapper.dataset.speed || '4000', 10);
        const captions = wrapper.dataset.captions === 'yes';
        const lightboxEnabled = wrapper.dataset.lightbox === 'yes';
        const zoomEnabled = wrapper.classList.contains('ka-woo-gallery--zoom');
        const lightboxSkin = wrapper.dataset.lightboxSkin || 'dark';
        const lightboxCounterEnabled = wrapper.dataset.lightboxCounter === 'yes';
        const lightboxThumbsEnabled = wrapper.dataset.lightboxThumbs === 'yes';
        let activeIndex = 0;
        let timer = null;

        const overlayId = `ka-woo-gallery-lightbox-${Math.random().toString(36).slice(2)}`;

        const lightbox = document.createElement('div');
        lightbox.className = 'ka-woo-gallery__lightbox';
        lightbox.id = overlayId;
        lightbox.innerHTML = `
            <button type="button" class="ka-woo-gallery__lightbox-close" aria-label="Close">&times;</button>
            <div class="ka-woo-gallery__lightbox-body">
                <div class="ka-woo-gallery__lightbox-top">
                    <div class="ka-woo-gallery__lightbox-counter"></div>
                    <div class="ka-woo-gallery__lightbox-nav">
                        <button class="ka-woo-gallery__lb-prev" aria-label="Previous">&#10094;</button>
                        <button class="ka-woo-gallery__lb-next" aria-label="Next">&#10095;</button>
                    </div>
                </div>
                <div class="ka-woo-gallery__lightbox-media">
                    <img alt="" />
                    <div class="ka-woo-gallery__lightbox-caption"></div>
                </div>
                <div class="ka-woo-gallery__lightbox-thumbs"></div>
            </div>
        `;
        if (lightboxEnabled) {
            document.body.appendChild(lightbox);
            lightbox.classList.add('ka-woo-gallery__lightbox--' + lightboxSkin);
        }

        const lightboxImg = lightbox.querySelector('img');
        const lightboxCaption = lightbox.querySelector('.ka-woo-gallery__lightbox-caption');
        const lightboxThumbs = lightbox.querySelector('.ka-woo-gallery__lightbox-thumbs');
        const lightboxCounter = lightbox.querySelector('.ka-woo-gallery__lightbox-counter');
        const lbPrev = lightbox.querySelector('.ka-woo-gallery__lb-prev');
        const lbNext = lightbox.querySelector('.ka-woo-gallery__lb-next');
        const lbClose = lightbox.querySelector('.ka-woo-gallery__lightbox-close');
        const lightboxThumbButtons = [];

        const updateCounter = () => {
            if (!lightboxCounterEnabled || !lightboxCounter) return;
            lightboxCounter.textContent = `${activeIndex + 1} / ${slides.length}`;
        };

        const syncLightboxThumbs = () => {
            if (!lightboxThumbsEnabled || !lightboxThumbButtons.length) return;
            lightboxThumbButtons.forEach((btn, i) => btn.classList.toggle('is-active', i === activeIndex));
        };

        const setActive = (index) => {
            activeIndex = index;
            slides.forEach((slide, i) => {
                const isGrid = layout === 'grid' || layout === 'masonry' || (mobileLayout === 'grid' && window.matchMedia('(max-width: 767px)').matches);
                slide.style.display = i === activeIndex || isGrid ? 'block' : 'none';
            });
            thumbs.forEach((thumb, i) => {
                thumb.classList.toggle('is-active', i === activeIndex);
            });
            dots.forEach((dot, i) => {
                dot.classList.toggle('is-active', i === activeIndex);
            });
            if (lightboxEnabled && captions && lightboxCaption) {
                const cap = slides[activeIndex]?.dataset.caption || '';
                lightboxCaption.textContent = cap;
                lightboxCaption.style.display = cap ? 'block' : 'none';
            }
            updateCounter();
            syncLightboxThumbs();
            if (zoomEnabled) {
                enableZoom(slides[activeIndex]);
            }
        };

        thumbs.forEach((thumb, index) => {
            thumb.addEventListener('click', () => setActive(index));
        });

        const prev = wrapper.querySelector('.ka-woo-gallery__arrow--prev');
        const next = wrapper.querySelector('.ka-woo-gallery__arrow--next');
        const dots = wrapper.querySelectorAll('.ka-woo-gallery__dot');

        const goTo = (delta) => {
            const total = slides.length;
            let target = activeIndex + delta;
            if (loop) {
                target = (target + total) % total;
            } else {
                target = Math.min(Math.max(target, 0), total - 1);
            }
            setActive(target);
        };

        if (prev) prev.addEventListener('click', () => goTo(-1));
        if (next) next.addEventListener('click', () => goTo(1));
        dots.forEach((dot, idx) => dot.addEventListener('click', () => setActive(idx)));

        // Lightbox
        const openLightbox = (index) => {
            if (!lightboxEnabled || !lightboxImg) return;
            const slide = slides[index];
            if (!slide) return;
            const src = slide.dataset.full || '';
            if (!src) return;
            activeIndex = index;
            lightboxImg.src = src;
            if (captions && lightboxCaption) {
                const cap = slide.dataset.caption || '';
                lightboxCaption.textContent = cap;
                lightboxCaption.style.display = cap ? 'block' : 'none';
            }
            updateCounter();
            syncLightboxThumbs();
            lightbox.classList.add('is-open');
            lockScroll();

            // Set global active controller
            window.kaWooGalleryActive = {
                isOpen: () => lightbox.classList.contains("is-open"),
                close: closeLightbox,
                prev: () => {
                    goTo(-1);
                    openLightbox(activeIndex);
                },
                next: () => {
                    goTo(1);
                    openLightbox(activeIndex);
                },
            };
        };

        const closeLightbox = () => {
            lightbox.classList.remove('is-open');
            unlockScroll();
            if (window.kaWooGalleryActive && window.kaWooGalleryActive.close === closeLightbox) {
                window.kaWooGalleryActive = null;
            }
        };

        if (lightboxEnabled) {
            lightbox.addEventListener('click', (event) => {
                const isBody = event.target.classList.contains('ka-woo-gallery__lightbox');
                const isClose = event.target === lbClose;
                if (isBody || isClose) {
                    closeLightbox();
                }
            });
            lightbox.querySelector('.ka-woo-gallery__lightbox-body')?.addEventListener('click', (e) => e.stopPropagation());

            if (lightboxThumbsEnabled && lightboxThumbs) {
                slides.forEach((slide, idx) => {
                    const imgEl = slide.querySelector('img');
                    const src = slide.dataset.full || imgEl?.src || '';
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'ka-woo-gallery__lightbox-thumb';
                    btn.innerHTML = `<span style="background-image:url('${src}')"></span>`;
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        setActive(idx);
                        openLightbox(idx);
                    });
                    lightboxThumbButtons.push(btn);
                    lightboxThumbs.appendChild(btn);
                });
            }

            if (!lightboxCounterEnabled && lightboxCounter) {
                lightboxCounter.style.display = 'none';
            }

            if (!lightboxThumbsEnabled && lightboxThumbs) {
                lightboxThumbs.style.display = 'none';
            }
        }

        slides.forEach((slide, idx) => {
            slide.addEventListener('click', () => {
                if (!lightboxEnabled) return;
                openLightbox(idx);
            });
            if (zoomEnabled) {
                enableZoom(slide);
            }
        });

        if (lbPrev) lbPrev.addEventListener('click', (e) => { e.stopPropagation(); goTo(-1); openLightbox(activeIndex); });
        if (lbNext) lbNext.addEventListener('click', (e) => { e.stopPropagation(); goTo(1); openLightbox(activeIndex); });

        // Global key handler is bound once for all instances below.

        // Autoplay
        const startAutoplay = () => {
            if (!autoplay || slides.length <= 1) return;
            stopAutoplay();
            timer = setInterval(() => {
                const nextIndex = loop ? (activeIndex + 1) % slides.length : Math.min(activeIndex + 1, slides.length - 1);
                setActive(nextIndex);
            }, speed > 500 ? speed : 4000);
        };
        const stopAutoplay = () => {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        };
        wrapper.addEventListener('mouseenter', stopAutoplay);
        wrapper.addEventListener('mouseleave', startAutoplay);

        setActive(0);
        startAutoplay();
    };

    const bindGlobalKeyupOnce = () => {
        if (window[KEYUP_FLAG]) {
            return;
        }
        window[KEYUP_FLAG] = true;

        document.addEventListener("keyup", (e) => {
            const active = window.kaWooGalleryActive;
            if (!active || typeof active.isOpen !== "function" || !active.isOpen()) {
                return;
            }
            if (e.key === "Escape") {
                active.close();
            }
            if (e.key === "ArrowLeft") {
                active.prev();
            }
            if (e.key === "ArrowRight") {
                active.next();
            }
        });
    };

    const initAll = (root) => {
        const ctx = root && root.querySelectorAll ? root : document;
        bindGlobalKeyupOnce();
        ctx.querySelectorAll(".ka-woo-gallery").forEach(initGallery);
    };

    document.addEventListener("DOMContentLoaded", () => initAll(document));

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/woo_product_images_gallery.default",
            function ($scope) {
                initAll($scope && $scope[0] ? $scope[0] : document);
            }
        );
    });
})(jQuery);






