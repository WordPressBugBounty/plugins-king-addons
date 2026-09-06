/**
 * Wrapper Link — makes a whole container clickable.
 *
 * The wrapper only acts when the click was not meant for something inside it.
 * A link, a button or a form control inside the container has to keep working
 * on its own; letting both fire sends the visitor to two places at once.
 */
(function () {
    'use strict';

    var WRAPPER_SELECTOR = '[data-kng-wrapper-link]';

    /**
     * Things a visitor can click on purpose. A click that starts on any of
     * these belongs to that element, not to the wrapper around it.
     */
    var INTERACTIVE_SELECTOR = [
        'a[href]',
        'button',
        'input',
        'select',
        'textarea',
        'label',
        'summary',
        'video',
        'audio',
        'iframe',
        '[role="button"]',
        '[role="link"]',
        '[role="tab"]',
        '[contenteditable="true"]',
        '[data-kng-wrapper-link]'
    ].join(',');

    function closest(node, selector) {
        if (!node) {
            return null;
        }
        if (typeof node.closest === 'function') {
            return node.closest(selector);
        }
        // Very old browsers: walk up by hand.
        var el = node.nodeType === 1 ? node : node.parentElement;
        while (el) {
            if (el.matches && el.matches(selector)) {
                return el;
            }
            el = el.parentElement;
        }
        return null;
    }

    function hasTextSelection() {
        try {
            var selection = window.getSelection();
            return !!selection && String(selection).trim().length > 0;
        } catch (e) {
            return false;
        }
    }

    function onClick(event) {
        // Only plain left clicks navigate; the rest is the browser's business.
        if (event.defaultPrevented || (typeof event.button === 'number' && event.button !== 0)) {
            return;
        }

        var wrapper = closest(event.target, WRAPPER_SELECTOR);
        if (!wrapper) {
            return;
        }

        // Editing the page should not navigate away from it.
        if (document.body && document.body.classList.contains('elementor-editor-active')) {
            return;
        }

        // The click landed on something clickable in its own right — or on a
        // nested wrapper, which owns the click instead of this one.
        var inner = closest(event.target, INTERACTIVE_SELECTOR);
        if (inner && inner !== wrapper) {
            return;
        }

        // Selecting text inside the container is not a request to leave it.
        if (hasTextSelection()) {
            return;
        }

        var url = wrapper.getAttribute('data-kng-wrapper-link');
        if (!url) {
            return;
        }

        var target = wrapper.getAttribute('data-kng-wrapper-link-target') || '_self';

        // Behave like a real link under the modifier keys people expect.
        if (target === '_blank' || event.metaKey || event.ctrlKey || event.shiftKey) {
            window.open(url, '_blank', 'noopener');
            return;
        }

        window.location.href = url;
    }

    document.addEventListener('click', onClick, false);
}());
