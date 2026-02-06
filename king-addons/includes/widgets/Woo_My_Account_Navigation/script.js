/**
 * Woo My Account Navigation widget behavior.
 *
 * Navigation behavior is handled by WooCommerce. This script provides an
 * Elementor hook to ensure correct initialization in the editor.
 */
(function ($) {
  "use strict";

  /**
   * Initialize widget instance.
   *
   * @param {Object} $scope Elementor scope.
   * @returns {void}
   */
  const initWooMyAccountNavigation = ($scope) => {
    void $scope;
  };

  $(window).on("elementor/frontend/init", function () {
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/woo_my_account_navigation.default",
      function ($scope) {
        initWooMyAccountNavigation($scope);
      }
    );
  });
})(jQuery);





