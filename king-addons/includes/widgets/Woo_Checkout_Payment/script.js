/**
 * Woo Checkout Payment widget behavior.
 *
 * Enhances payment methods UI and can optionally behave like an accordion.
 */
(function ($) {
  "use strict";

  const INIT_FLAG = "kaCheckoutPaymentInit";

  const safeParseJson = (value) => {
    if (!value) return {};
    try {
      const parsed = JSON.parse(value);
      return parsed && typeof parsed === "object" ? parsed : {};
    } catch (e) {
      return {};
    }
  };

  const initAccordion = (root) => {
    if (!root || !root.dataset) return;
    if (root.dataset[INIT_FLAG] === "1") return;
    root.dataset[INIT_FLAG] = "1";

    const isAccordion = root.dataset.kaAccordion === "true";
    const iconMap = safeParseJson(root.dataset.kaIcons);
    const buttonMap = safeParseJson(root.dataset.kaPlaceorder);
    const descMap = safeParseJson(root.dataset.kaDescriptions);

    const methods = root.querySelectorAll('.wc_payment_method');
    const placeOrder = document.querySelector('#place_order');

    methods.forEach((method) => {
      const input = method.querySelector('input.input-radio');
      const label = method.querySelector('label');
      const desc = method.querySelector('.payment_box');

      if (label && input) {
        const gid = input.value;
        if (iconMap && iconMap[gid]) {
          const icon = document.createElement('img');
          icon.src = iconMap[gid];
          icon.alt = '';
          icon.className = 'ka-wc-payment__icon';
          label.prepend(icon);
        }
        if (descMap && descMap[gid]) {
          let custom = method.querySelector('.ka-wc-payment__custom-desc');
          if (!custom) {
            custom = document.createElement('div');
            custom.className = 'ka-wc-payment__custom-desc';
            label.appendChild(custom);
          }
          custom.innerHTML = descMap[gid];
        }
      }

      if (!isAccordion || !input || !desc) return;

      const toggle = () => {
        methods.forEach((m) => {
          const box = m.querySelector('.payment_box');
          if (box) {
            box.style.display = 'none';
          }
          m.classList.remove('is-active');
        });
        desc.style.display = 'block';
        method.classList.add('is-active');
        if (placeOrder && buttonMap && buttonMap[input.value]) {
          placeOrder.textContent = buttonMap[input.value];
        }
      };

      input.addEventListener('change', toggle);
      if (input.checked) {
        toggle();
      } else {
        desc.style.display = 'none';
      }
    });
  };

  const init = () => {
    document.querySelectorAll('.ka-woo-checkout-payment').forEach(initAccordion);
  };

  const initInScope = ($scope) => {
    const root = $scope && $scope[0] ? $scope[0] : document;
    root.querySelectorAll(".ka-woo-checkout-payment").forEach(initAccordion);
  };

  document.addEventListener("DOMContentLoaded", init);

  $(document.body).on("updated_checkout", function () {
    // Allow re-init after WooCommerce updates.
    document.querySelectorAll(".ka-woo-checkout-payment").forEach((wrap) => {
      if (wrap && wrap.dataset) {
        delete wrap.dataset[INIT_FLAG];
      }
    });
    init();
  });

  $(window).on("elementor/frontend/init", function () {
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/woo_checkout_payment.default",
      function ($scope) {
        initInScope($scope);
      }
    );
  });
})(jQuery);




