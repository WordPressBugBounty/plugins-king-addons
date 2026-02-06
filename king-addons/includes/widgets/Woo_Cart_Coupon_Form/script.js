/**
 * Woo Cart Coupon Form widget behavior.
 *
 * Applies/removes coupons via AJAX and refreshes cart fragments.
 */
(function ($) {
  "use strict";

  const INIT_FLAG = "kaWooCartCouponInit";

  const setNotice = (wrap, html) => {
    const notice = wrap.querySelector('.ka-woo-cart-coupon__notice');
    if (notice) {
      notice.innerHTML = html || '';
    }
  };

  const updateFragments = (data) => {
    if (data.cart_html && document.querySelector('.ka-cart-table')) {
      document.querySelectorAll('.ka-cart-table').forEach((table) => {
        table.innerHTML = data.cart_html;
        if (window.KACartTable && typeof window.KACartTable.init === "function") {
          window.KACartTable.init(document);
        }
      });
    }

    if (data.totals_html !== undefined) {
      document.querySelectorAll('.ka-woo-cart-totals').forEach((totals) => {
        totals.innerHTML = data.totals_html || '';
        if (window.KACartTotals && typeof window.KACartTotals.init === "function") {
          window.KACartTotals.init(document);
        }
      });
    }

    if (data.cross_sells_html !== undefined) {
      document.querySelectorAll('.ka-woo-cart-cross-sells').forEach((cross) => {
        cross.innerHTML = data.cross_sells_html || '';
        if (window.KACartCrossSells && typeof window.KACartCrossSells.init === "function") {
          window.KACartCrossSells.init();
        }
      });
    }

    if (data.notices) {
      let container = document.querySelector('.woocommerce-notices-wrapper');
      if (!container) {
        container = document.createElement('div');
        container.className = 'woocommerce-notices-wrapper';
        document.body.prepend(container);
      }
      container.innerHTML = data.notices;
    }
  };

  const sendCoupon = (wrap, mode, code) => {
    const ajaxUrl = wrap.dataset.ajaxUrl;
    const nonce = wrap.dataset.nonce;
    if (!ajaxUrl || !nonce || !mode) return;

    const applyBtn = wrap.querySelector('.ka-woo-cart-coupon__apply');
    const removeBtn = wrap.querySelector('.ka-woo-cart-coupon__remove');
    [applyBtn, removeBtn].forEach((btn) => {
      if (btn) {
        btn.disabled = true;
        btn.classList.add('is-loading');
      }
    });

    const body = new URLSearchParams();
    body.append('action', 'ka_cart_coupon');
    body.append('nonce', nonce);
    body.append('mode', mode);
    body.append('coupon', code || '');

    fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString(),
      credentials: 'same-origin',
    })
      .then((res) => res.json())
      .then((res) => {
        if (res && res.success && res.data) {
          updateFragments(res.data);
          setNotice(wrap, res.data.notices || '');
        } else {
          setNotice(wrap, '');
        }
      })
      .catch(() => setNotice(wrap, ''))
      .finally(() => {
        [applyBtn, removeBtn].forEach((btn) => {
          if (btn) {
            btn.disabled = false;
            btn.classList.remove('is-loading');
          }
        });
      });
  };

  const bindWidget = (wrap) => {
    if (!wrap || !wrap.dataset) return;
    if (wrap.dataset[INIT_FLAG] === "1") return;
    wrap.dataset[INIT_FLAG] = "1";

    const toggle = wrap.querySelector('[data-ka-coupon-toggle]');
    const body = wrap.querySelector('.ka-woo-cart-coupon__body');
    const input = wrap.querySelector('.ka-woo-cart-coupon__input');
    const applyBtn = wrap.querySelector('.ka-woo-cart-coupon__apply');
    const removeBtn = wrap.querySelector('.ka-woo-cart-coupon__remove');

    if (toggle && body) {
      toggle.addEventListener('click', () => {
        const isHidden = body.hasAttribute('hidden');
        if (isHidden) {
          body.removeAttribute('hidden');
        } else {
          body.setAttribute('hidden', '');
        }
      });
    }

    if (applyBtn && input) {
      applyBtn.addEventListener('click', () => sendCoupon(wrap, 'apply', input.value));
    }

    if (removeBtn && input) {
      removeBtn.addEventListener('click', () => sendCoupon(wrap, 'remove', input.value));
    }

    wrap.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' && input && document.activeElement === input) {
        event.preventDefault();
        sendCoupon(wrap, 'apply', input.value);
      }
    });
  };

  const init = (root) => {
    const ctx = root && root.querySelectorAll ? root : document;
    ctx.querySelectorAll('.ka-woo-cart-coupon-widget').forEach(bindWidget);
  };

  document.addEventListener("DOMContentLoaded", () => init(document));

  $(window).on("elementor/frontend/init", function () {
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/woo_cart_coupon_form.default",
      function ($scope) {
        init($scope && $scope[0] ? $scope[0] : document);
      }
    );
  });
})(jQuery);






