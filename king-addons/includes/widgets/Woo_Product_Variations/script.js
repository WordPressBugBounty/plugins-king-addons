/**
 * Woo Product Variations widget behavior.
 *
 * Enhances variation selects with swatches.
 */
(function ($) {
    "use strict";

    const INIT_FLAG = "kaWooVariationsInit";

    const initVariations = (wrap) => {
        if (!wrap || !wrap.dataset) return;
        if (wrap.dataset[INIT_FLAG] === "1") return;
        wrap.dataset[INIT_FLAG] = "1";

        const form = wrap.querySelector('form.variations_form');
        if (!form) return;

        if (wrap.dataset.swatches !== 'yes') return;

        let map = {};
        try {
            map = wrap.dataset.swatchesMap ? JSON.parse(wrap.dataset.swatchesMap) : {};
        } catch (e) {
            map = {};
        }

        const unavailableBehavior = map.unavailable || 'disable';
        const showSelected = map.show_selected === 'yes';
        const shape = map.shape || 'square';
        const attrMap = (map.map) || {};
        const source = map.source || 'auto';

        const selects = form.querySelectorAll('select');
        const updateCallbacks = [];
        const runAll = () => updateCallbacks.forEach((cb) => cb());

        selects.forEach((select) => {
            const attrName = select.name;
            const swatchesInfo = attrMap[attrName];
            if (!swatchesInfo) {
                return;
            }

            const container = document.createElement('div');
            container.className = 'ka-woo-variations__swatches';

            const selectedText = document.createElement('div');
            selectedText.className = 'ka-woo-variations__selected';

            const options = Array.from(select.options).filter((opt) => opt.value !== '');
            options.forEach((opt) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ka-woo-variations__swatch';
                if (shape === 'round') {
                    btn.classList.add('ka-woo-variations__swatch--round');
                }
                btn.dataset.value = opt.value;

                const info = swatchesInfo[opt.value] || {};

                if (source === 'text') {
                    const span = document.createElement('span');
                    span.className = 'ka-woo-variations__swatch-text';
                    span.textContent = info.label || opt.text;
                    btn.appendChild(span);
                } else if (info.image) {
                    const img = document.createElement('img');
                    img.src = info.image;
                    img.alt = info.label || opt.text;
                    img.className = 'ka-woo-variations__swatch-img';
                    btn.appendChild(img);
                } else if (info.color) {
                    const span = document.createElement('span');
                    span.className = 'ka-woo-variations__swatch-color';
                    span.style.background = info.color;
                    btn.appendChild(span);
                } else {
                    const span = document.createElement('span');
                    span.className = 'ka-woo-variations__swatch-text';
                    span.textContent = info.label || opt.text;
                    btn.appendChild(span);
                }

                btn.addEventListener('click', () => {
                    select.value = opt.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    if (window.jQuery) {
                        window.jQuery(select).trigger('change');
                    }
                    runAll();
                });

                container.appendChild(btn);
            });

            select.style.display = 'none';
            select.insertAdjacentElement('afterend', container);
            if (showSelected) {
                container.insertAdjacentElement('afterend', selectedText);
            }

            const updateStates = () => {
                const current = select.value;
                Array.from(container.children).forEach((btn) => {
                    const val = btn.dataset.value;
                    const opt = select.querySelector(`option[value="${val}"]`);
                    const disabled = opt && opt.disabled;
                    btn.classList.toggle('is-active', current === val);
                    btn.classList.toggle('is-disabled', !!disabled);
                    if (disabled && unavailableBehavior === 'hide') {
                        btn.style.display = 'none';
                    } else {
                        btn.style.display = '';
                    }
                });
                if (showSelected) {
                    const label = (swatchesInfo[current] && swatchesInfo[current].label) || current || '';
                    const prefix = select.getAttribute('aria-label') || '';
                    selectedText.textContent = label ? `${prefix}: ${label}` : '';
                }
            };

            select.addEventListener('change', runAll);
            updateCallbacks.push(updateStates);
        });

        runAll();

        // Sync with WooCommerce variation script events.
        if (window.jQuery) {
            const $form = window.jQuery(form);
            $form.on('woocommerce_update_variation_values woocommerce_variation_has_changed reset_data found_variation hide_variation', runAll);
            const resetBtn = form.querySelector('.reset_variations');
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    setTimeout(runAll, 50);
                });
            }
        }
    };

    const initAll = (root) => {
        const ctx = root && root.querySelectorAll ? root : document;
        ctx.querySelectorAll(".ka-woo-variations").forEach(initVariations);
    };

    document.addEventListener("DOMContentLoaded", () => initAll(document));

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/woo_product_variations.default",
            function ($scope) {
                initAll($scope && $scope[0] ? $scope[0] : document);
            }
        );
    });
})(jQuery);






