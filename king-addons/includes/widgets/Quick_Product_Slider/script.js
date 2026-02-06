"use strict";

(function ($) {
    const parseNumber = (value, fallback) => {
        const number = parseInt(value, 10);
        return Number.isFinite(number) ? number : fallback;
    };

    const initQuickProductSlider = ($scope) => {
        const container = $scope[0]?.querySelector(".king-addons-quick-product-slider__track");
        if (!container || typeof Swiper === "undefined") {
            return;
        }

        const dataset = container.dataset;
        const slidesPerView = parseNumber(dataset.slidesPerView, 1);
        const slidesPerViewTablet = parseNumber(dataset.slidesPerViewTablet, slidesPerView);
        const slidesPerViewMobile = parseNumber(dataset.slidesPerViewMobile, slidesPerViewTablet);
        const spaceBetween = parseNumber(dataset.spaceBetween, 20);
        const slidesPerGroup = parseNumber(dataset.slidesPerGroup, 1);
        const speed = parseNumber(dataset.speed, 600);
        const autoplay = dataset.autoplay === "yes";
        const autoplayDelay = parseNumber(dataset.autoplayDelay, 3200);
        const autoplayPauseOnHover = dataset.autoplayPauseOnHover === "yes";
        const autoplayStopOnInteraction = dataset.autoplayStopOnInteraction === "yes";
        const loop = dataset.loop === "yes";
        const showNav = dataset.navigation === "yes";
        const showPagination = dataset.pagination === "yes";

        const config = {
            slidesPerView: slidesPerView,
            spaceBetween: spaceBetween,
            slidesPerGroup: slidesPerGroup,
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
                el: container.querySelector(".king-addons-quick-product-slider__pagination"),
                clickable: true,
            };
        }

        if (showNav) {
            config.navigation = {
                nextEl: $scope[0].querySelector(".king-addons-quick-product-slider__arrow--next"),
                prevEl: $scope[0].querySelector(".king-addons-quick-product-slider__arrow--prev"),
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
                const nextButton = $scope[0].querySelector(".king-addons-quick-product-slider__arrow--next");
                const prevButton = $scope[0].querySelector(".king-addons-quick-product-slider__arrow--prev");

                nextButton?.addEventListener("click", stopAutoplay);
                prevButton?.addEventListener("click", stopAutoplay);
            }
        }
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-quick-product-slider.default",
            function ($scope) {
                initQuickProductSlider($scope);
            }
        );
    });
})(jQuery);



