/**
 * Woo Product Short/Full Description widgets behavior.
 *
 * Toggles trimmed/full description visibility.
 */
(function ($) {
    "use strict";

    const INIT_FLAG = "kaWooDescToggleInit";

    const toggleSection = (wrapper) => {
        if (!wrapper || !wrapper.dataset) return;
        if (wrapper.dataset[INIT_FLAG] === "1") return;
        wrapper.dataset[INIT_FLAG] = "1";

        const trimmed = wrapper.querySelector('.ka-woo-product-short-description__content.is-trimmed');
        const full = wrapper.querySelector('.ka-woo-product-short-description__content.is-full');
        const btn = wrapper.querySelector('.ka-woo-product-short-description__toggle');
        if (!trimmed || !full || !btn) return;

        let expanded = false;
        const moreLabel = btn.dataset.more || 'Read more';
        const lessLabel = btn.dataset.less || 'Show less';

        const apply = () => {
            if (expanded) {
                trimmed.setAttribute('hidden', 'hidden');
                full.removeAttribute('hidden');
                btn.textContent = lessLabel;
                wrapper.classList.add('is-expanded');
            } else {
                full.setAttribute('hidden', 'hidden');
                trimmed.removeAttribute('hidden');
                btn.textContent = moreLabel;
                wrapper.classList.remove('is-expanded');
            }
        };

        btn.addEventListener('click', () => {
            expanded = !expanded;
            apply();
        });

        apply();
    };

    const init = () => {
        document.querySelectorAll('.ka-woo-product-short-description').forEach(toggleSection);
        document.querySelectorAll('.ka-woo-product-full-description').forEach(toggleSection);
    };

    const initAll = (root) => {
        const ctx = root && root.querySelectorAll ? root : document;
        ctx.querySelectorAll('.ka-woo-product-short-description').forEach(toggleSection);
        ctx.querySelectorAll('.ka-woo-product-full-description').forEach(toggleSection);
    };

    if (document.readyState === "complete" || document.readyState === "interactive") {
        initAll(document);
    } else {
        document.addEventListener("DOMContentLoaded", () => initAll(document));
    }

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/woo_product_short_description.default",
            function ($scope) {
                initAll($scope && $scope[0] ? $scope[0] : document);
            }
        );
    });
})(jQuery);




