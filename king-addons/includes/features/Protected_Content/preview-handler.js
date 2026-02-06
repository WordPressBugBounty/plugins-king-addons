/**
 * Protected Content Preview Handler.
 *
 * Handles visual indication of protected content in Elementor editor.
 * Shows badge with protection type and highlights protected elements.
 *
 * @package King_Addons
 */
(function ($) {
    'use strict';

    /**
     * Mark protected elements in editor.
     */
    const markProtected = () => {
        $('[data-king-protected="yes"]').each(function () {
            const $el = $(this);

            // Skip if already marked.
            if ($el.data('king-protected-marked')) {
                return;
            }

            $el.addClass('king-addons-protected-preview');
            $el.data('king-protected-marked', true);

            // Create badge with protection info.
            let $badge = $('<div class="king-addons-protected-badge"></div>');
            
            const protectionType = $el.attr('data-king-protected-type') || 'Protected';
            
            let badgeHtml = '<span class="king-addons-protected-badge-icon">🔒</span>';
            badgeHtml += '<span class="king-addons-protected-badge-label">Protected</span>';
            
            // Tooltip with details.
            let tooltipHtml = '<div class="king-addons-protected-tooltip">';
            tooltipHtml += '<strong>Protection Info:</strong>';
            tooltipHtml += '<div class="king-addons-protected-tooltip-content">' + protectionType + '</div>';
            tooltipHtml += '</div>';

            badgeHtml += tooltipHtml;
            $badge.html(badgeHtml);
            $el.append($badge);
        });
    };

    /**
     * Cleanup marks when protection is disabled.
     */
    const cleanupMarks = () => {
        $('.king-addons-protected-preview').each(function () {
            const $el = $(this);
            if (!$el.is('[data-king-protected="yes"]')) {
                $el.removeClass('king-addons-protected-preview');
                $el.removeData('king-protected-marked');
                $el.find('.king-addons-protected-badge').remove();
            }
        });
    };

    /**
     * Handler for panel open events.
     */
    const onPanelOpen = () => {
        setTimeout(() => {
            markProtected();
            cleanupMarks();
        }, 100);
    };

    /**
     * Handler for settings change.
     */
    const onSettingsChange = (model) => {
        if (!model || !model.changed) {
            return;
        }

        const pcKeys = Object.keys(model.changed).filter(key => key.startsWith('protected_content'));
        if (pcKeys.length > 0) {
            setTimeout(() => {
                // Refresh marks.
                $('.king-addons-protected-preview').each(function () {
                    $(this).removeData('king-protected-marked');
                    $(this).find('.king-addons-protected-badge').remove();
                });
                markProtected();
                cleanupMarks();
            }, 200);
        }
    };

    /**
     * Initialize when Elementor frontend is ready.
     */
    $(window).on('elementor/frontend/init', function () {
        // Mark already-present elements.
        markProtected();

        // Element ready hook.
        if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction('frontend/element_ready/global', markProtected);
        }

        // Panel open events.
        if (typeof elementor !== 'undefined' && elementor.hooks) {
            elementor.hooks.addAction('panel/open_editor/widget', onPanelOpen);
            elementor.hooks.addAction('panel/open_editor/section', onPanelOpen);
            elementor.hooks.addAction('panel/open_editor/container', onPanelOpen);
            elementor.hooks.addAction('panel/open_editor/column', onPanelOpen);
        }

        // Settings change listener.
        if (typeof elementor !== 'undefined' && elementor.channels && elementor.channels.editor) {
            elementor.channels.editor.on('change', onSettingsChange);
        }

        // Periodic check for dynamically added elements.
        setInterval(() => {
            markProtected();
            cleanupMarks();
        }, 2000);
    });

}(jQuery));







