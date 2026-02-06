/**
 * Breadcrumbs widget behavior.
 *
 * This widget is primarily server-rendered. The script keeps an Elementor
 * lifecycle hook for editor compatibility.
 */
(function ($) {
    "use strict";

    /**
     * Initialize widget instance.
     *
     * @param {Object} $scope Elementor scope.
     * @returns {void}
     */
    const initBreadcrumbs = ($scope) => {
        void $scope;
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-breadcrumbs.default",
            function ($scope) {
                initBreadcrumbs($scope);
            }
        );
    });
})(jQuery);

