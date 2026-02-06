"use strict";

(function ($) {
    const parseNumber = (value, fallback) => {
        const number = parseInt(value, 10);
        return Number.isFinite(number) ? number : fallback;
    };

    const initCptSlider = ($scope) => {
        const container = $scope[0]?.querySelector(".king-addons-cpt-slider__track");

        if (!container || typeof Swiper === "undefined") {
            return;
        }

        const settings = container.dataset;

        const slidesPerView = parseNumber(settings.slidesPerView, 1);
        const slidesPerViewTablet = parseNumber(settings.slidesPerViewTablet, slidesPerView);
        const slidesPerViewMobile = parseNumber(settings.slidesPerViewMobile, slidesPerViewTablet);
        const spaceBetween = parseNumber(settings.spaceBetween, 20);
        const speed = parseNumber(settings.speed, 600);

        const config = {
            slidesPerView: slidesPerView,
            spaceBetween: spaceBetween,
            speed: speed,
            loop: settings.loop === "yes",
            breakpoints: {
                0: { slidesPerView: slidesPerViewMobile },
                768: { slidesPerView: slidesPerViewTablet },
                1024: { slidesPerView: slidesPerView },
            },
        };

        if (settings.autoplay === "yes") {
            config.autoplay = {
                delay: parseNumber(settings.autoplayDelay, 3200),
                disableOnInteraction: false,
            };
        }

        if (settings.pagination === "yes") {
            config.pagination = {
                el: container.querySelector(".king-addons-cpt-slider__pagination"),
                clickable: true,
            };
        }

        if (settings.navigation === "yes") {
            config.navigation = {
                nextEl: $scope[0].querySelector(".king-addons-cpt-slider__arrow--next"),
                prevEl: $scope[0].querySelector(".king-addons-cpt-slider__arrow--prev"),
            };
        }

        // eslint-disable-next-line no-new
        new Swiper(container, config);

        const isEditMode = elementorFrontend.isEditMode();
        if (!isEditMode) {
            container.addEventListener("click", (event) => {
                const slide = event.target.closest(".king-addons-cpt-slider__slide[data-card-link]");
                if (!slide) {
                    return;
                }

                if (event.target.closest("a")) {
                    return;
                }

                const url = slide.dataset.cardLink;
                if (!url) {
                    return;
                }

                window.open(url, "_self");
            });
        }
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-custom-post-types-slider.default",
            function ($scope) {
                initCptSlider($scope);
            }
        );
    });
})(jQuery);






