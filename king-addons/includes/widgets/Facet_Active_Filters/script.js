/**
 * Facet Active Filters widget behavior.
 *
 * This widget is rendered by the faceted filters system. The UI behavior is
 * handled by the global faceted filters script; we keep an Elementor hook here
 * to ensure correct initialization in the editor.
 */
(function ($) {
    "use strict";

    /**
     * Initialize widget instance.
     *
     * @param {Object} $scope Elementor scope.
     * @returns {void}
     */
    const initFacetActiveFilters = ($scope) => {
        // Intentionally empty: behavior is handled globally.
        void $scope;
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-facet-active-filters.default",
            function ($scope) {
                initFacetActiveFilters($scope);
            }
        );
    });
})(jQuery);






