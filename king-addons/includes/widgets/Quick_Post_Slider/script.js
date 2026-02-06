"use strict";

(function ($) {
    const parseNumber = (value, fallback) => {
        const number = parseInt(value, 10);
        return Number.isFinite(number) ? number : fallback;
    };

    const initPostSlider = ($scope) => {
        const container = $scope[0]?.querySelector(".king-addons-post-slider__track");

        if (!container || typeof Swiper === "undefined") {
            return;
        }

        const settings = container.dataset;
        const slidesPerView = parseNumber(settings.slidesPerView, 1);
        const slidesPerViewTablet = parseNumber(settings.slidesPerViewTablet, slidesPerView);
        const slidesPerViewMobile = parseNumber(settings.slidesPerViewMobile, slidesPerViewTablet);
        const spaceBetween = parseNumber(settings.spaceBetween, 20);
        const speed = parseNumber(settings.speed, 600);
        const autoplay = settings.autoplay === "yes";
        const autoplayDelay = parseNumber(settings.autoplayDelay, 3200);
        const autoplayPauseOnHover = settings.autoplayPauseOnHover === "yes";
        const autoplayStopOnInteraction = settings.autoplayStopOnInteraction === "yes";
        const loop = settings.loop === "yes";
        const showPagination = settings.pagination === "yes";
        const showNav = settings.navigation === "yes";

        const config = {
            slidesPerView: slidesPerView,
            spaceBetween: spaceBetween,
            speed: speed,
            loop: loop,
            breakpoints: {
                0: { slidesPerView: slidesPerViewMobile },
                768: { slidesPerView: slidesPerViewTablet },
                1024: { slidesPerView: slidesPerView },
            },
        };

        if (autoplay) {
            config.autoplay = {
                delay: autoplayDelay,
                disableOnInteraction: autoplayStopOnInteraction,
                pauseOnMouseEnter: autoplayPauseOnHover,
            };
        }

        if (showPagination) {
            config.pagination = {
                el: container.querySelector(".king-addons-post-slider__pagination"),
                clickable: true,
            };
        }

        if (showNav) {
            config.navigation = {
                nextEl: $scope[0].querySelector(".king-addons-post-slider__arrow--next"),
                prevEl: $scope[0].querySelector(".king-addons-post-slider__arrow--prev"),
            };
        }

        // eslint-disable-next-line no-new
        const swiper = new Swiper(container, config);

        if (autoplay && autoplayStopOnInteraction) {
            const stopAutoplay = () => {
                if (!swiper.autoplay || !swiper.autoplay.running) {
                    return;
                }

                swiper.autoplay.stop();
            };

            container.addEventListener("pointerdown", stopAutoplay, { passive: true });
            container.addEventListener("touchstart", stopAutoplay, { passive: true });
            container.addEventListener("mousedown", stopAutoplay);
            container.addEventListener("keydown", stopAutoplay);

            if (showNav) {
                const nextButton = $scope[0].querySelector(".king-addons-post-slider__arrow--next");
                const prevButton = $scope[0].querySelector(".king-addons-post-slider__arrow--prev");

                nextButton?.addEventListener("click", stopAutoplay);
                prevButton?.addEventListener("click", stopAutoplay);
            }

            if (showPagination) {
                const pagination = container.querySelector(".king-addons-post-slider__pagination");
                pagination?.addEventListener("click", stopAutoplay);
            }
        }

        // Card-level link handling: only bind on frontend to avoid blocking editor selection.
        const isEditMode = elementorFrontend.isEditMode();
        if (!isEditMode) {
            const openCardLink = (slide) => {
                const url = slide.dataset.cardLink;
                if (!url) {
                    return;
                }

                const target = slide.dataset.cardLinkTarget || "_self";
                if (target === "_blank") {
                    const newWindow = window.open(url, "_blank", "noopener");
                    if (newWindow) {
                        newWindow.opener = null;
                    }
                    return;
                }

                window.location.href = url;
            };

            container.addEventListener("click", (event) => {
                const slide = event.target.closest(".king-addons-post-slider__slide[data-card-link]");
                if (!slide) {
                    return;
                }

                if (event.target.closest("a")) {
                    return;
                }

                openCardLink(slide);
            });

            container.addEventListener("keydown", (event) => {
                if (event.key !== "Enter" && event.key !== " " && event.key !== "Spacebar" && event.key !== "Space") {
                    return;
                }

                const slide = event.target.closest(".king-addons-post-slider__slide[data-card-link]");
                if (!slide || event.target !== slide) {
                    return;
                }

                event.preventDefault();
                openCardLink(slide);
            });
        }
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-quick-post-slider.default",
            function ($scope) {
                initPostSlider($scope);
            }
        );
    });
})(jQuery);






