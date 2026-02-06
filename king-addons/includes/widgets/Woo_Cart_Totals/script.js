/**
 * Woo Cart Totals widget behavior.
 *
 * Handles applying/removing coupons via AJAX and refreshing cart fragments.
 * Exposes a global initializer for other cart-related widgets.
 */
(function ($) {
  "use strict";

  const FLAG = "kaCartTotalsInit";

  const KACartTotals = window.KACartTotals || (() => {
  const toggleBody = (wrap) => {
    const body = wrap.querySelector('.ka-cart-coupon__body');
    if (!body) return;
    const isHidden = body.hasAttribute('hidden');
    if (isHidden) {
      body.removeAttribute('hidden');
    } else {
      body.setAttribute('hidden', '');
    }
  };

  const setNotice = (wrap, text) => {
    const notice = wrap.querySelector('.ka-cart-coupon__notice');
    if (notice) {
      notice.textContent = text || '';
    }
  };

  const applyFragments = (data) => {
    if (data.totals_html !== undefined) {
      document.querySelectorAll('.ka-woo-cart-totals').forEach((totals) => {
        totals.innerHTML = data.totals_html || '';
      });
    }
    if (data.cart_html && document.querySelector('.ka-cart-table')) {
      document.querySelector('.ka-cart-table').innerHTML = data.cart_html;
      if (window.KACartTable && typeof window.KACartTable.init === "function") {
        window.KACartTable.init(document);
      }
    }
    if (data.cross_sells_html !== undefined) {
      document.querySelectorAll('.ka-woo-cart-cross-sells').forEach((cross) => {
        cross.innerHTML = data.cross_sells_html || '';
      });
      if (window.KACartCrossSells && typeof window.KACartCrossSells.init === "function") {
        window.KACartCrossSells.init();
      }
    }
  };

  const sendCoupon = (wrap, mode, code) => {
    const ajaxUrl = wrap.dataset.ajaxUrl;
    const nonce = wrap.dataset.nonce;
    if (!ajaxUrl || !nonce) return;

    const body = new URLSearchParams();
    body.append('action', 'ka_cart_coupon');
    body.append('nonce', nonce);
    body.append('mode', mode);
    body.append('coupon', code);

    wrap.classList.add('loading');

    fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString(),
      credentials: 'same-origin',
    })
      .then((res) => res.json())
      .then((res) => {
        if (res && res.success) {
          applyFragments(res.data || {});
        }
        setNotice(wrap, (res && res.data && res.data.notices) || '');
      })
      .catch(() => setNotice(wrap, ''))
      .finally(() => wrap.classList.remove('loading'));
  };

  const bind = (wrap) => {
    if (!wrap || !wrap.dataset) return;
    if (wrap.dataset[FLAG] === "1") return;
    wrap.dataset[FLAG] = "1";

    const toggle = wrap.querySelector('[data-ka-coupon-toggle]');
    const input = wrap.querySelector('.ka-cart-coupon__input');
    const applyBtn = wrap.querySelector('.ka-cart-coupon__apply');
    const removeBtn = wrap.querySelector('.ka-cart-coupon__remove');

    if (toggle) {
      toggle.addEventListener('click', () => toggleBody(wrap));
    }
    if (applyBtn && input) {
      applyBtn.addEventListener('click', () => sendCoupon(wrap, 'apply', input.value));
    }
    if (removeBtn && input) {
      removeBtn.addEventListener('click', () => sendCoupon(wrap, 'remove', input.value));
    }
  };

  const init = (root) => {
    const ctx = root && root.querySelectorAll ? root : document;
    ctx.querySelectorAll('.ka-woo-cart-totals').forEach((wrap) => bind(wrap));
  };

  return { init };
})();

  window.KACartTotals = KACartTotals;

  const initInScope = ($scope) => {
    const root = $scope && $scope[0] ? $scope[0] : document;
    KACartTotals.init(root);
  };

  document.addEventListener("DOMContentLoaded", () => KACartTotals.init(document));

  $(window).on("elementor/frontend/init", function () {
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/woo_cart_totals.default",
      function ($scope) {
        initInScope($scope);
      }
    );
  });
})(jQuery);





