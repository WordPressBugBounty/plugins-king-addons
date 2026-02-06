/**
 * Woo Cart Table widget behavior.
 *
 * Handles cart quantity updates and item removal via AJAX.
 * Exposes a global initializer for other cart-related widgets.
 */
(function ($) {
  "use strict";

  const FLAG = "kaCartTableInit";

  const KACartTable = window.KACartTable || (() => {
  const getKeyFromInput = (input) => {
    const name = input.getAttribute('name') || '';
    const match = name.match(/cart\[(.+?)\]\[qty\]/);
    return match ? match[1] : '';
  };

  const getKeyFromRemove = (link) => {
    const href = link.getAttribute('href') || '';
    try {
      const url = new URL(href, window.location.href);
      return url.searchParams.get('remove_item') || '';
    } catch (e) {
      return '';
    }
  };

  const setNotices = (wrapper, notices) => {
    let container = document.querySelector('.woocommerce-notices-wrapper');
    if (!container) {
      container = document.createElement('div');
      container.className = 'woocommerce-notices-wrapper';
      wrapper.prepend(container);
    }
    container.innerHTML = notices || '';
  };

  const applyFragments = (wrapper, data) => {
    if (data.cart_html) {
      wrapper.innerHTML = data.cart_html;
    }

    // Allow re-binding after HTML replacement.
    if (wrapper && wrapper.dataset) {
      delete wrapper.dataset[FLAG];
    }

    if (data.totals_html !== undefined) {
      document.querySelectorAll('.ka-woo-cart-totals').forEach((totals) => {
        totals.innerHTML = data.totals_html || '';
      });
    }

    if (data.cross_sells_html !== undefined) {
      document.querySelectorAll('.ka-woo-cart-cross-sells').forEach((cross) => {
        cross.innerHTML = data.cross_sells_html || '';
      });
      if (window.KACartCrossSells && typeof window.KACartCrossSells.init === "function") {
        window.KACartCrossSells.init();
      }
    }

    setNotices(wrapper, data.notices || '');
    initWrapper(wrapper);
  };

  const request = (wrapper, payload) => {
    const ajaxUrl = wrapper.dataset.ajaxUrl;
    const nonce = wrapper.dataset.nonce;
    if (!ajaxUrl || !nonce) {
      return Promise.resolve();
    }

    const body = new URLSearchParams();
    body.append('action', 'ka_cart_update');
    body.append('nonce', nonce);
    Object.entries(payload).forEach(([k, v]) => body.append(k, v));

    wrapper.classList.add('loading');

    return fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString(),
      credentials: 'same-origin',
    })
      .then((res) => res.json())
      .then((res) => {
        if (res && res.success) {
          applyFragments(wrapper, res.data || {});
        }
      })
      .catch(() => {})
      .finally(() => wrapper.classList.remove('loading'));
  };

  const bindEvents = (wrapper) => {
    if (!wrapper || !wrapper.dataset) return;
    if (wrapper.dataset[FLAG] === "1") return;
    wrapper.dataset[FLAG] = "1";

    const form = wrapper.querySelector('.woocommerce-cart-form');
    if (!form) return;

    form.addEventListener('submit', (e) => {
      e.preventDefault();
    });

    form.addEventListener('change', (e) => {
      const target = e.target;
      if (!(target instanceof HTMLInputElement)) return;
      if (target.name && target.name.startsWith('cart[')) {
        const key = getKeyFromInput(target);
        if (key) {
          request(wrapper, { op: 'qty', cart_item_key: key, qty: target.value });
        }
      }
    });

    form.addEventListener('click', (e) => {
      const target = e.target;
      if (!(target instanceof HTMLElement)) return;
      if (target.closest('a.remove')) {
        e.preventDefault();
        const link = target.closest('a.remove');
        const key = getKeyFromRemove(link);
        if (key) {
          request(wrapper, { op: 'remove', cart_item_key: key });
        }
      }
    });
  };

  const initWrapper = (wrapper) => {
    bindEvents(wrapper);
  };

  const init = (root) => {
    const ctx = root && root.querySelectorAll ? root : document;
    ctx.querySelectorAll('.ka-cart-table').forEach((wrapper) => {
      initWrapper(wrapper);
    });
  };

  return { init };
})();

  window.KACartTable = KACartTable;

  const initInScope = ($scope) => {
    const root = $scope && $scope[0] ? $scope[0] : document;
    KACartTable.init(root);
  };

  document.addEventListener("DOMContentLoaded", () => KACartTable.init(document));

  $(window).on("elementor/frontend/init", function () {
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/woo_cart_table.default",
      function ($scope) {
        initInScope($scope);
      }
    );
  });
})(jQuery);





