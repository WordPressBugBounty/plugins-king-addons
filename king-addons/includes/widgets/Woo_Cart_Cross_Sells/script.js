/**
 * Woo Cart Cross Sells widget behavior.
 *
 * Initializes a Slick carousel for cross-sell products when enabled.
 */
(function ($) {
  "use strict";

  window.KACartCrossSells = window.KACartCrossSells || (() => {
  const initSlider = (container) => {
    const products = container.querySelector('.products');
    if (!products) return;

    const cols = parseInt(container.dataset.cols || '4', 10);
    const colsTablet = parseInt(container.dataset.colsTablet || Math.max(2, Math.min(cols, 3)), 10);
    const colsMobile = parseInt(container.dataset.colsMobile || '1', 10);
    const showArrows = container.dataset.arrows === '1';
    const showDots = container.dataset.dots === '1';
    const loop = container.dataset.loop === '1';
    const autoplay = container.dataset.autoplay === '1';
    const autoplaySpeed = parseInt(container.dataset.autoplaySpeed || '4000', 10);

    if (typeof $.fn.slick === "function") {
      const $el = $(products);
      if ($el.hasClass('slick-initialized')) {
        $el.slick('unslick');
      }
      $el.slick({
        slidesToShow: cols,
        slidesToScroll: 1,
        infinite: loop,
        dots: showDots,
        arrows: showArrows,
        autoplay,
        autoplaySpeed,
        responsive: [
          { breakpoint: 1024, settings: { slidesToShow: colsTablet } },
          { breakpoint: 768, settings: { slidesToShow: colsMobile } },
        ],
      });
    }
  };

  const init = () => {
    document.querySelectorAll('.ka-woo-cart-cross-sells').forEach((wrap) => {
      initSlider(wrap);
    });
  };

  return { init };
})();

  const initInScope = ($scope) => {
    const root = $scope && $scope[0] ? $scope[0] : document;
    root.querySelectorAll(".ka-woo-cart-cross-sells").forEach((wrap) => {
      initSlider(wrap);
    });
  };

  document.addEventListener("DOMContentLoaded", () => window.KACartCrossSells.init());

  $(window).on("elementor/frontend/init", function () {
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/woo_cart_cross_sells.default",
      function ($scope) {
        initInScope($scope);
      }
    );
  });
})(jQuery);




