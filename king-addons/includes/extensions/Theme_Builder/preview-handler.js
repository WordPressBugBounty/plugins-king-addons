/**
 * Theme Builder preview context handler.
 *
 * Adjusts the Elementor preview to properly display dynamic widgets
 * in the context of Theme Builder templates.
 *
 * @package King_Addons
 */
(function ($) {
    'use strict';

    /**
     * Apply preview context adjustments for Theme Builder widgets.
     *
     * @param {jQuery} $scope The widget element scope.
     */
    const applyPreviewContext = ($scope) => {
        // Theme Builder widgets that need special handling in preview
        const tbWidgets = [
            'king-addons-tb-post-title',
            'king-addons-tb-post-content',
            'king-addons-tb-post-excerpt',
            'king-addons-tb-featured-image',
            'king-addons-tb-post-meta',
            'king-addons-tb-author-box',
            'king-addons-tb-post-comments',
            'king-addons-tb-post-navigation',
            'king-addons-tb-post-taxonomies',
            'king-addons-tb-related-posts',
            'king-addons-tb-archive-title',
            'king-addons-tb-archive-description',
            'king-addons-tb-archive-posts',
            'king-addons-tb-archive-pagination',
            'king-addons-tb-archive-result-count',
            'king-addons-tb-404-title',
            'king-addons-tb-404-description',
            'king-addons-tb-404-search-form',
            'king-addons-tb-back-to-home'
        ];

        // Check if this is a TB widget
        const widgetType = $scope.data('widget_type');
        if (!widgetType) {
            return;
        }

        const widgetName = widgetType.split('.')[0];
        if (!tbWidgets.includes(widgetName)) {
            return;
        }

        // Add a visual indicator for TB widgets in editor
        if (!$scope.find('.ka-tb-preview-notice').length) {
            const $notice = $('<div class="ka-tb-preview-notice" style="font-size: 11px; color: #666; padding: 4px 8px; background: #f0f0f0; border-radius: 3px; margin-bottom: 8px;">');
            $notice.text('Preview: Using sample data');
            // Only show notice in editor mode, not on frontend
            if (window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode()) {
                $scope.prepend($notice);
            }
        }
    };

    /**
     * Initialize preview handlers.
     */
    const init = () => {
        if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
            return;
        }

        // Apply context when any element becomes ready
        elementorFrontend.hooks.addAction('frontend/element_ready/global', applyPreviewContext);

        // Handle dynamic content refresh
        $(document).on('elementor/editor/after_save', () => {
            // Trigger refresh of dynamic widgets after save
            $(document).trigger('ka-theme-builder-refresh');
        });
    };

    // Initialize when Elementor frontend is ready
    $(window).on('elementor/frontend/init', init);

    // Fallback initialization
    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        init();
    }
})(jQuery);
