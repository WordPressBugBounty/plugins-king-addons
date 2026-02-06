(function (factory) {
    if (typeof window.jQuery === 'undefined') {
        return;
    }
    factory(window.jQuery);
})(function ($) {
    'use strict';

    const $rulesContainer = $('#ka-woo-rules');
    if (!$rulesContainer.length || typeof $.fn.select2 === 'undefined') {
        return;
    }

    const restRoot = (window.wpApiSettings && window.wpApiSettings.root) ? window.wpApiSettings.root : '';
    const restNonce = (window.wpApiSettings && window.wpApiSettings.nonce) ? window.wpApiSettings.nonce : '';
    const noValueTypes = ['always', 'all_products', 'cart', 'checkout', 'my_account', 'is_shop'];

    const productTypeOptions = [
        {id: 'simple', text: 'Simple'},
        {id: 'variable', text: 'Variable'},
        {id: 'grouped', text: 'Grouped'},
        {id: 'external', text: 'External/Affiliate'}
    ];

    const endpoints = {
        product_in: {url: restRoot + 'kingaddons/v1/ajaxselect2/getPostsByPostType/', params: {query_slug: 'product'}},
        product_cat_in: {url: restRoot + 'kingaddons/v1/ajaxselect2/getTaxonomies/', params: {query_slug: 'product_cat'}},
        product_cat_archive_in: {url: restRoot + 'kingaddons/v1/ajaxselect2/getTaxonomies/', params: {query_slug: 'product_cat'}},
        product_tag_archive_in: {url: restRoot + 'kingaddons/v1/ajaxselect2/getTaxonomies/', params: {query_slug: 'product_tag'}},
        product_tag_in: {url: restRoot + 'kingaddons/v1/ajaxselect2/getTaxonomies/', params: {query_slug: 'product_tag'}},
        products: {url: restRoot + 'kingaddons/v1/ajaxselect2/getPostsByPostType/', params: {query_slug: 'product'}},
        product_categories: {url: restRoot + 'kingaddons/v1/ajaxselect2/getTaxonomies/', params: {query_slug: 'product_cat'}},
        product_cat_archives: {url: restRoot + 'kingaddons/v1/ajaxselect2/getTaxonomies/', params: {query_slug: 'product_cat'}},
        product_tags: {url: restRoot + 'kingaddons/v1/ajaxselect2/getTaxonomies/', params: {query_slug: 'product_tag'}},
    };

    const getPlaceholder = () => {
        if (window.KingAddonsWooBuilder && window.KingAddonsWooBuilder.labels && window.KingAddonsWooBuilder.labels.valuePlaceholder) {
            return window.KingAddonsWooBuilder.labels.valuePlaceholder;
        }
        return '';
    };

    const initRule = ($rule, idx) => {
        const $type = $rule.find('.ka-woo-rule-type');
        const $values = $rule.find('.ka-woo-rule-values');

        $type.attr('name', 'ka_woo_rule_type[' + idx + ']');
        $values.attr('name', 'ka_woo_rule_values[' + idx + '][]');

        const typeVal = $type.val() || '';

        if (noValueTypes.includes(typeVal)) {
            if ($values.hasClass('select2-hidden-accessible')) {
                $values.select2('destroy');
            }
            $values.prop('disabled', true).val(null);
            return;
        }

        $values.prop('disabled', false);

        if ($values.hasClass('select2-hidden-accessible')) {
            $values.select2('destroy');
        }

        const ajaxConfig = endpoints[typeVal] || null;
        const select2Options = {
            width: '100%',
            allowClear: true,
            placeholder: getPlaceholder(),
        };

        if (ajaxConfig && restRoot) {
            select2Options.ajax = {
                url: ajaxConfig.url,
                dataType: 'json',
                delay: 120,
                headers: restNonce ? {'X-WP-Nonce': restNonce} : {},
                data: function (params) {
                    const query = Object.assign({}, ajaxConfig.params || {});
                    if (params && params.term) {
                        query.s = params.term;
                    }
                    if ($values.data('selected-ids')) {
                        query.ids = $values.data('selected-ids');
                    }
                    return query;
                },
                processResults: function (data) {
                    return data;
                }
            };
        } else if (typeVal === 'product_type_in' || typeVal === 'product_types') {
            select2Options.data = productTypeOptions;
        }

        $values.select2(select2Options);

        // Clear pre-set data-selected-ids after init to avoid duplicate queries.
        $values.removeData('selected-ids');
    };

    const rebuildIndexes = () => {
        $rulesContainer.find('.ka-woo-rule').each((idx, el) => initRule($(el), idx));
    };

    $rulesContainer.on('click', '.ka-woo-remove-rule', function () {
        if ($rulesContainer.find('.ka-woo-rule').length <= 1) {
            return;
        }
        $(this).closest('.ka-woo-rule').remove();
        rebuildIndexes();
    });

    $('#ka-woo-add-rule').on('click', function () {
        const $first = $rulesContainer.find('.ka-woo-rule').first();
        if (!$first.length) {
            return;
        }
        const $clone = $first.clone();
        $clone.find('option').prop('selected', false);
        $clone.find('.ka-woo-rule-values').empty();
        $clone.appendTo($rulesContainer);
        rebuildIndexes();
    });

    $rulesContainer.on('change', '.ka-woo-rule-type', function () {
        const $rule = $(this).closest('.ka-woo-rule');
        initRule($rule, $rulesContainer.find('.ka-woo-rule').index($rule));
    });

    $rulesContainer.find('.ka-woo-rule-values').each(function () {
        const existingValues = $(this).val();
        if (existingValues && existingValues.length) {
            $(this).data('selected-ids', Array.isArray(existingValues) ? existingValues.join(',') : existingValues);
        }
    });

    rebuildIndexes();
});

(function () {
    'use strict';

    const defaultPanels = ['#customer_details', '.woocommerce-checkout-payment', '.woocommerce-checkout-review-order'];

    const resolvePanels = (container) => {
        const customSelectors = Array.from(container.querySelectorAll('.ka-woo-checkout-step')).map((step) => step.dataset.target || '');
        const mapped = customSelectors.some(Boolean) ? customSelectors : defaultPanels;
        return mapped.map((selector, idx) => ({ selector, idx }));
    };

    const togglePanels = (stepsMap, activeIndex, scroll) => {
        stepsMap.forEach((item, idx) => {
            if (!item.selector) {
                return;
            }
            document.querySelectorAll(item.selector).forEach((el) => {
                if (idx === activeIndex) {
                    el.classList.remove('ka-woo-hidden');
                } else {
                    el.classList.add('ka-woo-hidden');
                }
            });
        });

        if (scroll && scroll === 'yes') {
            const targetSelector = stepsMap[activeIndex] ? stepsMap[activeIndex].selector : null;
            const target = targetSelector ? document.querySelector(targetSelector) : null;
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    };

    const initSteps = () => {
        document.querySelectorAll('.ka-woo-checkout-steps--pro').forEach((container) => {
            const steps = Array.from(container.querySelectorAll('.ka-woo-checkout-step'));
            if (!steps.length) {
                return;
            }
            let current = 0;
            const scrollTop = container.dataset.scrollTop || 'no';
            const stepsMap = resolvePanels(container);
            const wrapper = container.parentElement;
            const enableNav = wrapper?.dataset.enableNav === 'true';
            const navPrev = wrapper?.querySelector('.ka-woo-checkout-steps__prev');
            const navNext = wrapper?.querySelector('.ka-woo-checkout-steps__next');
            const form = document.querySelector('form.checkout');
            const submitButton = form ? form.querySelector('button[type="submit"]') : null;
            const errorClass = 'ka-woo-checkout-step--error';
            const serverErrors = document.querySelector('.woocommerce-NoticeGroup-checkout, .woocommerce-error');

            const validateCurrent = () => {
                const panelSelector = stepsMap[current] ? stepsMap[current].selector : null;
                if (!panelSelector || !form) {
                    return true;
                }
                const panel = document.querySelector(panelSelector);
                if (!panel) {
                    return true;
                }
                const fields = panel.querySelectorAll('input, select, textarea');
                let valid = true;
                fields.forEach((field) => {
                    if (typeof field.reportValidity === 'function') {
                        if (!field.reportValidity()) {
                            valid = false;
                        }
                    }
                });
                return valid;
            };

            const setActive = (idx) => {
                current = idx;
                steps.forEach((step, i) => step.classList.toggle('is-active', i === current));
                togglePanels(stepsMap, current, scrollTop);
                if (enableNav && navPrev && navNext) {
                    navPrev.disabled = current === 0;
                    const isLast = current >= steps.length - 1;
                    navNext.dataset.isLast = isLast ? 'true' : 'false';
                    const defaultNext = container.dataset.nextText || navNext.textContent;
                    const submitText = container.dataset.submitText || navNext.textContent;
                    navNext.textContent = isLast ? submitText : defaultNext;
                    navNext.classList.toggle('is-submit', isLast);
                    if (submitButton) {
                        submitButton.disabled = !isLast;
                    }
                }
                steps.forEach((step) => step.classList.remove(errorClass));
            };

            steps.forEach((step, idx) => {
                step.addEventListener('click', () => setActive(idx));
            });

            if (enableNav && navPrev && navNext) {
                navPrev.addEventListener('click', () => {
                    setActive(Math.max(0, current - 1));
                });
                navNext.addEventListener('click', (e) => {
                    if (!validateCurrent()) {
                        e.preventDefault();
                        return;
                    }
                    if (navNext.dataset.isLast === 'true') {
                        if (submitButton) {
                            submitButton.click();
                        } else if (form) {
                            form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                        }
                        return;
                    }
                    setActive(Math.min(steps.length - 1, current + 1));
                });
            }

            setActive(0);

            // React to Woo checkout errors: highlight step containing error.
            document.body.addEventListener('checkout_error', () => {
                const errorField = document.querySelector('.woocommerce-error, .woocommerce-invalid');
                if (!errorField) {
                    return;
                }
                const errorStepIdx = stepsMap.findIndex((map) => map.selector && errorField.closest(map.selector));
                if (errorStepIdx >= 0) {
                    steps[errorStepIdx]?.classList.add(errorClass);
                    setActive(errorStepIdx);
                    errorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            // React to Woo AJAX errors (server-side validation)
            if (serverErrors) {
                const observer = new MutationObserver(() => {
                    const err = document.querySelector('.woocommerce-error, .woocommerce-invalid');
                    if (!err) {
                        return;
                    }
                    const idx = stepsMap.findIndex((map) => map.selector && err.closest(map.selector));
                    if (idx >= 0) {
                        steps[idx]?.classList.add(errorClass);
                        setActive(idx);
                        err.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
                observer.observe(serverErrors.parentElement || document.body, { childList: true, subtree: true });
            }
        });
    };

    const initSticky = () => {
        const items = document.querySelectorAll('[data-ka-sticky="true"]');
        items.forEach((el) => {
            const offset = parseInt(el.dataset.kaStickyOffset || '20', 10);
            const breakpoint = parseInt(el.dataset.kaStickyBreakpoint || '768', 10);
            const footer = document.querySelector('.site-footer, footer');

            const applySticky = () => {
                if (window.innerWidth < breakpoint) {
                    el.classList.remove('ka-woo-sticky-ready');
                    el.style.top = '';
                    el.style.maxHeight = '';
                    el.style.overflow = '';
                    return;
                }
                let available = window.innerHeight - offset - 20;
                if (footer) {
                    const rect = footer.getBoundingClientRect();
                    if (rect.top > 0) {
                        available = Math.min(available, rect.top - offset - 10);
                    }
                }
                el.classList.add('ka-woo-sticky-ready');
                el.style.top = offset + 'px';
                if (available > 100) {
                    el.style.maxHeight = available + 'px';
                    el.style.overflow = 'auto';
                }
            };

            const ro = new ResizeObserver(() => applySticky());
            ro.observe(document.body);
            if (footer) {
                const fo = new ResizeObserver(() => applySticky());
                fo.observe(footer);
            }
            // Adjust on content changes inside sticky container
            const contentObserver = new MutationObserver(() => applySticky());
            contentObserver.observe(el, { childList: true, subtree: true });
            applySticky();
            window.addEventListener('resize', applySticky, { passive: true });
        });
    };

    const initPayments = () => {
        document.querySelectorAll('.ka-woo-checkout-payment').forEach((wrapper) => {
            const icons = wrapper.dataset.kaIcons ? JSON.parse(wrapper.dataset.kaIcons) : {};
            const placeorder = wrapper.dataset.kaPlaceorder ? JSON.parse(wrapper.dataset.kaPlaceorder) : {};
            const accordion = wrapper.dataset.kaAccordion === 'true';
            const methods = wrapper.querySelectorAll('.wc_payment_methods .wc_payment_method');
            const orderButton = wrapper.querySelector('.place-order button[type="submit"]') || document.querySelector('.place-order button[type="submit"]');

            const applyIcons = () => {
                methods.forEach((method) => {
                    const input = method.querySelector('input[name="payment_method"]');
                    const label = input ? wrapper.querySelector('label[for="' + input.id + '"]') : null;
                    if (!input || !label) {
                        return;
                    }
                    const gid = input.value;
                    if (icons && icons[gid] && !label.querySelector('.ka-woo-payment-icon')) {
                        const img = document.createElement('img');
                        img.src = icons[gid];
                        img.alt = gid;
                        img.className = 'ka-woo-payment-icon';
                        label.appendChild(img);
                    }
                });
            };

            const updatePlaceOrder = () => {
                if (!orderButton) return;
                const selected = wrapper.querySelector('input[name="payment_method"]:checked');
                if (!selected) return;
                const gid = selected.value;
                if (placeorder && placeorder[gid]) {
                    orderButton.textContent = placeorder[gid];
                }
            };

            const toggleAccordion = () => {
                if (!accordion) return;
                methods.forEach((method) => {
                    const input = method.querySelector('input[name="payment_method"]');
                    const box = method.querySelector('.payment_box');
                    const active = input && input.checked;
                    method.classList.toggle('is-active', !!active);
                    if (box) {
                        box.style.display = active ? '' : 'none';
                    }
                });
            };

            methods.forEach((method) => {
                const input = method.querySelector('input[name="payment_method"]');
                if (input) {
                    input.addEventListener('change', () => {
                        updatePlaceOrder();
                        toggleAccordion();
                    });
                }
            });

            applyIcons();
            updatePlaceOrder();
            toggleAccordion();
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        initSteps();
        initSticky();
        initPayments();
    });
})();





