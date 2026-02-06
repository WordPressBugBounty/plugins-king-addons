 (function () {
    'use strict';

    const initTabs = (root) => {
        const wrapper = root.closest('.ka-woo-custom-tabs--tabs');
        if (!wrapper) {
            return;
        }

        const tabs = wrapper.querySelectorAll('.ka-woo-custom-tabs__tab');
        const panels = wrapper.querySelectorAll('.ka-woo-custom-tabs__panel');

        const setActive = (key) => {
            tabs.forEach((btn) => btn.classList.toggle('is-active', btn.dataset.tab === key));
            panels.forEach((panel) => {
                const active = panel.dataset.tab === key;
                panel.classList.toggle('is-active', active);
            });
        };

        tabs.forEach((btn) => {
            btn.addEventListener('click', () => setActive(btn.dataset.tab));
        });
    };

    const initAccordion = (wrapper) => {
        const toggles = wrapper.querySelectorAll('.ka-woo-custom-tabs__accordion-toggle');

        toggles.forEach((btn) => {
            btn.addEventListener('click', () => {
                const body = btn.nextElementSibling;
                const isOpen = btn.classList.contains('is-active');
                if (isOpen) {
                    btn.classList.remove('is-active');
                    if (body) {
                        body.style.display = 'none';
                    }
                } else {
                    btn.classList.add('is-active');
                    if (body) {
                        body.style.display = 'block';
                    }
                }
            });
        });
    };

    const bootstrap = (scope) => {
        const root = scope || document;
        root.querySelectorAll('.ka-woo-custom-tabs--tabs').forEach((wrap) => initTabs(wrap));
        root.querySelectorAll('.ka-woo-custom-tabs--accordion').forEach(initAccordion);
    };

    document.addEventListener('DOMContentLoaded', () => bootstrap(document));

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/woo_product_custom_tabs.default', (scope) => {
            bootstrap(scope[0] || scope);
        });
    }
})();





