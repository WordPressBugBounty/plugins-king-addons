/**
 * Conditional Display Preview Handler.
 *
 * Handles visual indication of Conditional Display settings in Elementor editor.
 * Shows badge with active conditions and highlights elements with CD enabled.
 *
 * @package King_Addons
 */
(function ($) {
    'use strict';

    /**
     * Mark elements with Conditional Display enabled.
     */
    const markElements = () => {
        $('[data-king-cd-enabled]').each(function () {
            const $el = $(this);

            // Skip if already processed.
            if ($el.data('kingCdMarked')) {
                return;
            }

            $el.addClass('king-addons-cd-preview');
            $el.data('kingCdMarked', true);

            // Create badge container.
            let $badge = $('<div class="king-addons-cd-badge"></div>');

            // Get conditions info.
            const conditions = $el.attr('data-king-cd-conditions');
            let badgeHtml = '<span class="king-addons-cd-badge-icon">🔒</span>';
            badgeHtml += '<span class="king-addons-cd-badge-label">CD</span>';

            if (conditions && conditions.length > 0) {
                const conditionsArray = conditions.split(', ');
                const conditionsCount = conditionsArray.length;

                badgeHtml += '<span class="king-addons-cd-badge-count">' + conditionsCount + '</span>';

                // Tooltip with conditions list.
                let tooltipHtml = '<div class="king-addons-cd-tooltip">';
                tooltipHtml += '<strong>Active Conditions:</strong><ul>';
                conditionsArray.forEach(function (cond) {
                    tooltipHtml += '<li>' + cond + '</li>';
                });
                tooltipHtml += '</ul></div>';

                badgeHtml += tooltipHtml;
            }

            $badge.html(badgeHtml);
            $el.append($badge);
        });
    };

    /**
     * Remove marks when CD is disabled.
     */
    const cleanupMarks = () => {
        $('.king-addons-cd-preview').each(function () {
            const $el = $(this);
            if (!$el.is('[data-king-cd-enabled]')) {
                $el.removeClass('king-addons-cd-preview');
                $el.removeData('kingCdMarked');
                $el.find('.king-addons-cd-badge').remove();
            }
        });
    };

    /**
     * Handler for panel open events.
     */
    const onPanelOpen = () => {
        setTimeout(() => {
            markElements();
            cleanupMarks();
        }, 100);
    };

    /**
     * Handler for element settings change.
     *
     * @param {Object} model Element model.
     */
    const onSettingsChange = (model) => {
        if (!model || !model.changed) {
            return;
        }

        // Check if CD-related setting changed.
        const cdKeys = Object.keys(model.changed).filter(key => key.startsWith('cd_'));
        if (cdKeys.length > 0) {
            setTimeout(() => {
                // Refresh all marks.
                $('.king-addons-cd-preview').each(function () {
                    $(this).removeData('kingCdMarked');
                    $(this).find('.king-addons-cd-badge').remove();
                });
                markElements();
                cleanupMarks();
            }, 200);
        }
    };

    /**
     * Initialize when Elementor frontend is ready.
     */
    $(window).on('elementor/frontend/init', () => {
        // Mark already-present elements.
        markElements();

        // When a new element is loaded in preview.
        if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction('frontend/element_ready/global', markElements);
        }

        // Panel open events.
        if (typeof elementor !== 'undefined' && elementor.hooks) {
            elementor.hooks.addAction('panel/open_editor/section', onPanelOpen);
            elementor.hooks.addAction('panel/open_editor/widget', onPanelOpen);
            elementor.hooks.addAction('panel/open_editor/column', onPanelOpen);
            elementor.hooks.addAction('panel/open_editor/container', onPanelOpen);
        }

        // Listen to settings changes.
        if (typeof elementor !== 'undefined' && elementor.channels && elementor.channels.editor) {
            elementor.channels.editor.on('change', onSettingsChange);
        }

        // Periodic check for dynamically added elements.
        setInterval(() => {
            markElements();
            cleanupMarks();
        }, 2000);
    });

}(jQuery));






