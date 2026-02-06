/**
 * Woo Archive Description widget behavior.
 *
 * This widget is static; behavior is handled by WooCommerce/theme templates.
 * We keep an Elementor hook to ensure correct initialization in the editor.
 */
(function ($) {
  "use strict";

  /**
   * Initialize widget instance.
   *
   * @param {Object} $scope Elementor scope.
   * @returns {void}
   */
  const initWooArchiveDescription = ($scope) => {
    void $scope;
  };

  $(window).on("elementor/frontend/init", function () {
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/woo_archive_description.default",
      function ($scope) {
        initWooArchiveDescription($scope);
      }
    );
  });
})(jQuery);






