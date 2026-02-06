(function () {
    'use strict';

    const loadTabContent = async (wrapper, key) => {
        const ajax = wrapper.dataset.ajax === 'yes';
        if (!ajax) {
            return true;
        }

        const panel = wrapper.querySelector('.ka-woo-tabs__panel[data-tab="' + key + '"]');
        if (!panel || !panel.querySelector('.ka-woo-tabs__placeholder')) {
            return true;
        }

        const ajaxUrl = wrapper.dataset.ajaxUrl;
        const nonce = wrapper.dataset.nonce;
        const productId = wrapper.dataset.productId;

        if (!ajaxUrl || !nonce || !productId) {
            return true;
        }

        panel.classList.add('is-loading');

        const formData = new FormData();
        formData.append('action', 'king_addons_render_product_tab');
        formData.append('nonce', nonce);
        formData.append('product_id', productId);
        formData.append('tab_key', key);

        try {
            const response = await fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData,
            });

            const result = await response.json();
            if (result && result.success && result.data && typeof result.data.html === 'string') {
                panel.innerHTML = result.data.html;
                panel.dataset.loaded = 'yes';
            } else {
                panel.innerHTML = '<div class="ka-woo-tabs__error">Could not load content.</div>';
            }
        } catch (e) {
            panel.innerHTML = '<div class="ka-woo-tabs__error">Could not load content.</div>';
        }

        panel.classList.remove('is-loading');
        return true;
    };

    const initTabs = (wrapper) => {
        const tabs = wrapper.querySelectorAll('.ka-woo-tabs__tab');
        const panels = wrapper.querySelectorAll('.ka-woo-tabs__panel');
        const accordions = wrapper.querySelectorAll('.ka-woo-tabs__accordion-toggle');

        const setActive = (key) => {
            tabs.forEach((btn) => btn.classList.toggle('is-active', btn.dataset.tab === key));
            panels.forEach((panel) => {
                const active = panel.dataset.tab === key;
                panel.classList.toggle('is-active', active);
                const body = panel.querySelector('.ka-woo-tabs__accordion-body');
                if (body) {
                    body.style.display = active ? 'block' : 'none';
                }
            });
        };

        const handleSwitch = async (key) => {
            const panel = wrapper.querySelector('.ka-woo-tabs__panel[data-tab="' + key + '"]');
            const needsLoad = panel && panel.querySelector('.ka-woo-tabs__placeholder');
            if (needsLoad) {
                await loadTabContent(wrapper, key);
            }
            setActive(key);
        };

        tabs.forEach((btn) => {
            btn.addEventListener('click', () => handleSwitch(btn.dataset.tab));
        });

        accordions.forEach((btn) => {
            btn.addEventListener('click', () => handleSwitch(btn.dataset.tab));
        });
    };

    const bootstrap = (scope) => {
        const root = scope || document;
        root.querySelectorAll('.ka-woo-tabs').forEach(initTabs);
    };

    document.addEventListener('DOMContentLoaded', () => bootstrap(document));

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/woo_product_tabs.default', (scope) => {
            bootstrap(scope[0] || scope);
        });
    }
})();






