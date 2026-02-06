/**
 * Woo Archive Title widget behavior.
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
  const initWooArchiveTitle = ($scope) => {
    void $scope;
  };

  $(window).on("elementor/frontend/init", function () {
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/woo_archive_title.default",
      function ($scope) {
        initWooArchiveTitle($scope);
      }
    );
  });
})(jQuery);






