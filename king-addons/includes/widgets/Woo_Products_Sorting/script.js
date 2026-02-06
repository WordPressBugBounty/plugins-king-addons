(() => {
    'use strict';

    const mapOrder = (key) => {
        switch (key) {
            case 'price':
                return { orderby: 'price', order: 'ASC' };
            case 'price-desc':
                return { orderby: 'price', order: 'DESC' };
            case 'rating':
                return { orderby: 'rating', order: 'DESC' };
            case 'popularity':
                return { orderby: 'popularity', order: 'DESC' };
            case 'rand':
                return { orderby: 'rand', order: 'DESC' };
            case 'date':
                return { orderby: 'date', order: 'DESC' };
            case 'menu_order':
            default:
                return { orderby: 'menu_order', order: 'ASC' };
        }
    };

    const dispatchSorting = (el, key) => {
        const { orderby, order } = mapOrder(key);
        const detail = {
            queryId: el.closest('.ka-woo-sorting')?.dataset.queryId || '',
            orderby,
            order,
            raw: key,
        };
        document.dispatchEvent(new CustomEvent('kingaddons:sorting:apply', { detail }));
    };

    const initSorting = (wrapper) => {
        const select = wrapper.querySelector('.ka-woo-sorting__select');
        const buttons = wrapper.querySelectorAll('.ka-woo-sorting__btn');

        if (select) {
            select.addEventListener('change', () => {
                dispatchSorting(select, select.value);
            });
        }

        if (buttons.length) {
            buttons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    buttons.forEach((b) => b.classList.remove('is-active'));
                    btn.classList.add('is-active');
                    dispatchSorting(btn, btn.dataset.sort);
                });
            });
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.ka-woo-sorting').forEach(initSorting);
    });

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/woo_products_sorting.default', (scope) => {
            const root = scope[0] || scope;
            root.querySelectorAll('.ka-woo-sorting').forEach(initSorting);
        });
    }
})();





