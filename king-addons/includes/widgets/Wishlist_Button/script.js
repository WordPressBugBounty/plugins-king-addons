/**
 * Wishlist Button widget behavior.
 *
 * The actual interactions are handled by the Wishlist module's global script.
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
    const initWishlistButton = ($scope) => {
        // Intentionally empty: behavior is handled globally.
        void $scope;
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-wishlist-button.default",
            function ($scope) {
                initWishlistButton($scope);
            }
        );
    });
})(jQuery);



