/**
 * Woo Archive Description widget behaviour.
 *
 * Expands a trimmed description when the "Read more" control is used. The
 * markup ships both the trimmed and the full text; this only swaps which one
 * is visible.
 */
(function () {
    'use strict';

    const BOUND = 'kaArchiveDescriptionBound';

    const bind = (wrap) => {
        if (!wrap || !wrap.dataset || wrap.dataset[BOUND] === '1') {
            return;
        }

        const button = wrap.querySelector('.ka-woo-archive-description__readmore');
        const short = wrap.querySelector('.ka-woo-archive-description__short');
        const full = wrap.querySelector('.ka-woo-archive-description__full');
        if (!button || !short || !full) {
            return;
        }

        wrap.dataset[BOUND] = '1';
        const moreLabel = button.textContent;
        const lessLabel = button.dataset.lessText || '';

        button.addEventListener('click', () => {
            const expanded = button.getAttribute('aria-expanded') === 'true';
            short.hidden = !expanded;
            full.hidden = expanded;
            button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            if (lessLabel) {
                button.textContent = expanded ? moreLabel : lessLabel;
            }
        });
    };

    const init = (scope) => {
        const root = scope && scope.querySelectorAll ? scope : document;
        root.querySelectorAll('[data-ka-expandable="1"]').forEach(bind);
    };

    document.addEventListener('DOMContentLoaded', () => init(document));

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction(
            'frontend/element_ready/woo_archive_description.default',
            (scope) => init(scope && scope[0] ? scope[0] : document)
        );
    }
})();
