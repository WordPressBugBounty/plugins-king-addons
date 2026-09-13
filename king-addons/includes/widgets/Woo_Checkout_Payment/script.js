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

  const relocateIntoCheckoutForm = (root) => {
    if (!root) return;
    if (window.elementorFrontend && elementorFrontend.isEditMode && elementorFrontend.isEditMode()) {
      return;
    }
    const form = document.querySelector("form.woocommerce-checkout, form.checkout");
    if (!form) return;
    const widget = root.closest(".elementor-widget-woo_checkout_payment") || root;
    if (form.contains(widget)) return;
    const review = form.querySelector("#order_review") || form;
    review.appendChild(widget);
  };

  const initAccordion = (root) => {
    if (!root || !root.dataset) return;
    relocateIntoCheckoutForm(root);
    if (root.dataset[INIT_FLAG] === "1") return;
    root.dataset[INIT_FLAG] = "1";

    const isAccordion = root.dataset.kaAccordion === "true";
    const iconMap = safeParseJson(root.dataset.kaIcons);
    const buttonMap = safeParseJson(root.dataset.kaPlaceorder);
    const descMap = safeParseJson(root.dataset.kaDescriptions);

    const methods = root.querySelectorAll('.wc_payment_method');
    // Scope to this widget first: the payment template renders its own
    // #place_order, so a document-wide lookup renamed the button belonging to
    // another payment widget on the page instead of this one.
    const placeOrder = root.querySelector('#place_order, button[name="woocommerce_checkout_place_order"]')
      || document.querySelector('#place_order');
    // Remember the stock label so switching to a gateway without a custom one
    // puts it back - otherwise the first custom text stuck for every gateway.
    const placeOrderDefault = placeOrder ? placeOrder.textContent : '';

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
        if (placeOrder) {
          placeOrder.textContent = (buttonMap && buttonMap[input.value])
            ? buttonMap[input.value]
            : placeOrderDefault;
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




