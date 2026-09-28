/**
 * Woo Product Variations widget behavior.
 *
 * Enhances variation selects with swatches and keeps WooCommerce's
 * variation resolver in sync when the form lives in a split builder widget.
 */
(function ($) {
    "use strict";

    const INIT_FLAG = "kaWooVariationsInit";

    const initVariations = (wrap) => {
        if (!wrap || !wrap.dataset) return;
        if (wrap.dataset[INIT_FLAG] === "1") return;

        const form = wrap.querySelector('form.variations_form');
        if (!form) return;
        wrap.dataset[INIT_FLAG] = "1";

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

        // Woo leaves out-of-stock options enabled in the native select when
        // "hide out of stock" is off. Hide/disable would then be a no-op,
        // so also read the variation JSON Woo already prints on the form.
        let productVariations = [];
        try {
            const raw = form.getAttribute('data-product_variations');
            productVariations = raw && raw !== 'false' ? JSON.parse(raw) : [];
        } catch (e) {
            productVariations = [];
        }

        const priceRoots = () => document.querySelectorAll('.ka-woo-product-price');
        priceRoots().forEach((el) => {
            if (!el.dataset.kaOriginalPrice) {
                el.dataset.kaOriginalPrice = el.innerHTML;
            }
        });

        const setAtcNotice = (hide) => {
            document.querySelectorAll('.ka-woo-product-atc__notice').forEach((el) => {
                el.hidden = !!hide;
            });
        };

        const applyVariationPrice = (variation) => {
            priceRoots().forEach((el) => {
                if (!variation || !variation.price_html) {
                    if (el.dataset.kaOriginalPrice) {
                        el.innerHTML = el.dataset.kaOriginalPrice;
                    }
                    return;
                }
                const discount = el.querySelector('.ka-woo-product-price__discount');
                const discountHtml = discount ? discount.outerHTML : '';
                el.innerHTML = variation.price_html + discountHtml;
            });
        };

        const ensureWcVariationForm = () => {
            if (!window.jQuery || typeof window.jQuery.fn.wc_variation_form !== 'function') {
                return false;
            }
            const $form = window.jQuery(form);
            if (!$form.data('wc_variation_form')) {
                $form.wc_variation_form();
            }
            return true;
        };

        const findMatchedVariation = () => {
            if (!Array.isArray(productVariations) || !productVariations.length) {
                return null;
            }
            const selected = {};
            let hasEmpty = false;
            form.querySelectorAll('select[name^="attribute_"]').forEach((sel) => {
                selected[sel.name] = sel.value;
                if (!sel.value) {
                    hasEmpty = true;
                }
            });
            if (hasEmpty) {
                return null;
            }
            return productVariations.find((variation) => {
                const attrs = variation.attributes || {};
                return Object.keys(attrs).every((key) => {
                    const want = attrs[key];
                    return !want || want === selected[key];
                });
            }) || null;
        };

        const applyFallbackVariation = () => {
            if (window.jQuery && window.jQuery(form).data('wc_variation_form')) {
                return;
            }
            const match = findMatchedVariation();
            const varInput = form.querySelector('input.variation_id, input[name="variation_id"]');
            const single = form.querySelector('.single_variation');
            if (match) {
                if (varInput) {
                    varInput.value = String(match.variation_id || '');
                }
                if (single) {
                    single.innerHTML = (match.price_html ? '<div class="woocommerce-variation-price">' + match.price_html + '</div>' : '')
                        + (match.availability_html ? '<div class="woocommerce-variation-availability">' + match.availability_html + '</div>' : '');
                }
                applyVariationPrice(match);
                setAtcNotice(true);
            } else {
                if (varInput) {
                    varInput.value = '0';
                }
                if (single) {
                    single.innerHTML = '';
                }
                applyVariationPrice(null);
                setAtcNotice(false);
            }
        };

        const wcReady = ensureWcVariationForm();
        if (window.jQuery) {
            const $form = window.jQuery(form);
            $form.on('found_variation', function (event, variation) {
                applyVariationPrice(variation);
                setAtcNotice(!!(variation && variation.variation_id));
            });
            $form.on('reset_data hide_variation', function () {
                applyVariationPrice(null);
                setAtcNotice(false);
            });
            $form.on('woocommerce_update_variation_values woocommerce_variation_has_changed reset_data found_variation hide_variation', runAll);
        }
        if (!wcReady) {
            form.addEventListener('change', applyFallbackVariation);
            applyFallbackVariation();
            if (window.jQuery) {
                window.jQuery(function () {
                    if (ensureWcVariationForm()) {
                        const selected = form.querySelector('select[name^="attribute_"]');
                        if (selected && selected.value) {
                            window.jQuery(selected).trigger('change');
                        }
                    } else {
                        applyFallbackVariation();
                    }
                });
            }
        }

        if (wrap.dataset.swatches !== 'yes') {
            return;
        }

        const attributeMatches = (variationAttr, selected) => {
            if (!variationAttr) {
                return true;
            }
            return variationAttr === selected;
        };

        const valueIsAvailable = (attrName, val) => {
            if (!Array.isArray(productVariations) || !productVariations.length) {
                const opt = form.querySelector(`select[name="${attrName}"] option[value="${val}"]`);
                return !(opt && opt.disabled);
            }
            return productVariations.some((variation) => {
                if (!variation || variation.is_in_stock === false) {
                    return false;
                }
                const attrs = variation.attributes || {};
                if (!attributeMatches(attrs[attrName], val)) {
                    return false;
                }
                return Array.from(selects).every((other) => {
                    if (other.name === attrName || !other.value) {
                        return true;
                    }
                    return attributeMatches(attrs[other.name], other.value);
                });
            });
        };

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
                    if (!valueIsAvailable(attrName, opt.value)) {
                        return;
                    }
                    select.value = opt.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    if (window.jQuery) {
                        window.jQuery(select).trigger('check_variations');
                        window.jQuery(select).trigger('change');
                    }
                    applyFallbackVariation();
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
                    const unavailable = !valueIsAvailable(attrName, val) || !!(opt && opt.disabled);
                    btn.classList.toggle('is-active', current === val);
                    btn.classList.toggle('is-disabled', unavailable);
                    if (unavailable && unavailableBehavior === 'hide') {
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

        const resetBtn = form.querySelector('.reset_variations');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                setTimeout(() => {
                    runAll();
                    applyFallbackVariation();
                }, 50);
            });
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
