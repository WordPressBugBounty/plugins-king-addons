(function () {
    'use strict';

    function start() {
        // The bar is printed on wp_footer, which can run after this script:
        // wait for the document before looking for it.
        var bar = document.querySelector('.king-addons-satc');
        if (!bar) {
            return;
        }

        var cfg = window.kingAddonsStickyAddToCart || {};
        var offset = parseInt(cfg.offset, 10);
        if (isNaN(offset)) {
            offset = 400;
        }

        // Prefer the real add-to-cart form: the bar earns its place exactly when
        // the buy button has scrolled out of reach. Without a form on the page,
        // fall back to a plain scroll offset.
        var form = document.querySelector('form.cart, .ka-woo-product-atc, #ka-satc-form-anchor');

        // Variable products link the bar to #ka-satc-form-anchor. That id is
        // printed by the Woo Add To Cart widget; a variations form alone never
        // had it, so the button jumped nowhere.
        if (form && !document.getElementById('ka-satc-form-anchor')) {
            form.id = 'ka-satc-form-anchor';
        }

        var button = bar.querySelector('.king-addons-satc__button');
        if (button && (button.getAttribute('href') || '').indexOf('#ka-satc-form-anchor') === 0) {
            button.addEventListener('click', function (event) {
                var target = document.getElementById('ka-satc-form-anchor') || form;
                if (!target) {
                    return;
                }
                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        }

        bar.hidden = false;

        function update() {
            var visible;

            if (form) {
                visible = form.getBoundingClientRect().bottom < 0;
            } else {
                visible = window.pageYOffset > offset;
            }

            bar.classList.toggle('is-visible', visible);
        }

        var ticking = false;
        function onScroll() {
            if (ticking) {
                return;
            }
            ticking = true;
            window.requestAnimationFrame(function () {
                update();
                ticking = false;
            });
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        update();
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
