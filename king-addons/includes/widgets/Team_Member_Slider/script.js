;(function($){
    $(window).on('elementor/frontend/init', function(){
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/king-addons-team-member-slider.default',
            function($scope) {
                var container = $scope.find('.swiper-container').get(0);
                if (!container) {
                    return;
                }
                // Read settings from data attributes
                var data = container.dataset;
                var slidesPerView = parseInt(data.slidesPerView, 10) || 1;
                var tabletSlides = parseInt(data.slidesPerViewTablet, 10) || slidesPerView;
                var mobileSlides = parseInt(data.slidesPerViewMobile, 10) || tabletSlides;
                var spaceBetween  = parseInt(data.spaceBetween, 10) || 0;
                var loop           = data.loop === 'yes';
                var speed          = parseInt(data.speed, 10) || 600;
                // Autoplay
                var autoplay = false;
                if (data.autoplay === 'yes') {
                    autoplay = {
                        delay: parseInt(data.autoplayDelay, 10) || 2000,
                        disableOnInteraction: false
                    };
                    // Reverse autoplay direction
                    if (data.autoplayReverse === 'yes') {
                        autoplay.reverseDirection = true;
                    }
                }
                // Pagination and Navigation
                var config = {
                    slidesPerView: slidesPerView,
                    spaceBetween: spaceBetween,
                    loop: loop,
                    speed: speed
                };
                // Responsive breakpoints for slides per view
                config.breakpoints = {
                    0: { slidesPerView: mobileSlides },
                    768: { slidesPerView: tabletSlides },
                    1024: { slidesPerView: slidesPerView }
                };
                if (data.pagination === 'yes') {
                    config.pagination = {
                        el: $scope.find('.swiper-pagination').get(0),
                        clickable: true
                    };
                }
                if (data.navigation === 'yes') {
                    config.navigation = {
                        nextEl: $scope.find('.swiper-button-next').get(0),
                        prevEl: $scope.find('.swiper-button-prev').get(0)
                    };
                }
                if (autoplay) {
                    config.autoplay = autoplay;
                }
                new Swiper(container, config);
            }
        );
    });
})(jQuery);