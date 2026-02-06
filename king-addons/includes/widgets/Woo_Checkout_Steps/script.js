/**
 * Woo Checkout Steps widget behavior.
 *
 * Initializes step navigation and hides/shows checkout sections.
 */
(function ($) {
  "use strict";

  const INIT_FLAG = "kaCheckoutStepsInit";

  const scrollToEl = (el) => {
    if (!el) return;
    const rect = el.getBoundingClientRect();
    const offset = window.pageYOffset + rect.top - 80;
    window.scrollTo({ top: offset, behavior: 'smooth' });
  };

  const hideTargets = (allTargets, activeTarget) => {
    allTargets.forEach((el) => {
      if (!el) return;
      if (el === activeTarget) {
        el.classList.remove('ka-woo-checkout-step-hidden');
      } else {
        el.classList.add('ka-woo-checkout-step-hidden');
      }
    });
  };

  const validateStep = (target) => {
    if (!target) return true;
    const fields = target.querySelectorAll('input[required], select[required], textarea[required]');
    let valid = true;
    fields.forEach((field) => {
      const value = field.value?.trim();
      if (!value) {
        valid = false;
        field.classList.add('ka-woo-checkout-step-error');
      } else {
        field.classList.remove('ka-woo-checkout-step-error');
      }
    });
    return valid;
  };

  const initSteps = (wrapper) => {
    if (!wrapper || !wrapper.dataset) return;
    if (wrapper.dataset[INIT_FLAG] === "1") return;
    wrapper.dataset[INIT_FLAG] = "1";

    const stepsList = wrapper.querySelector('.ka-woo-checkout-steps');
    const steps = [...wrapper.querySelectorAll('.ka-woo-checkout-step')];
    if (!steps.length || !stepsList) return;

    const enableNav = wrapper.dataset.enableNav === 'true';
    const autoScroll = wrapper.dataset.autoScroll === 'yes';
    const scrollTop = stepsList.dataset.scrollTop === 'yes';
    const prevText = stepsList.dataset.prevText || 'Previous';
    const nextText = stepsList.dataset.nextText || 'Next';

    const navPrev = wrapper.querySelector('.ka-woo-checkout-steps__prev');
    const navNext = wrapper.querySelector('.ka-woo-checkout-steps__next');

    const targets = steps.map((step) => {
      const sel = step.dataset.target;
      if (!sel) return null;
      return document.querySelector(sel);
    });

    let current = 0;

    const setActive = (index, fromNav = false) => {
      if (index < 0 || index >= steps.length) return;
      if (fromNav) {
        // validate current before moving forward
        if (index > current && !validateStep(targets[current])) {
          return;
        }
      }
      current = index;
      steps.forEach((step, i) => {
        step.classList.toggle('is-active', i === current);
        step.classList.toggle('is-complete', i < current);
      });
      hideTargets(targets.filter(Boolean), targets[current] || null);
      if (scrollTop && autoScroll && targets[current]) {
        scrollToEl(targets[current]);
      } else if (autoScroll) {
        scrollToEl(wrapper);
      }
      if (enableNav) {
        if (navPrev) navPrev.disabled = current === 0;
        if (navNext) navNext.textContent = current === steps.length - 1 ? nextText : nextText;
      }
    };

    steps.forEach((step, i) => {
      step.addEventListener('click', () => setActive(i, true));
    });

    if (enableNav && navPrev && navNext) {
      navPrev.addEventListener('click', () => setActive(current - 1, true));
      navNext.addEventListener('click', () => setActive(current + 1, true));
    }

    // Init
    hideTargets(targets.filter(Boolean), targets[0] || null);
    setActive(0);
  };

  const initAll = () => {
    document.querySelectorAll('.ka-woo-checkout-steps-wrapper').forEach(initSteps);
  };

  const initInScope = ($scope) => {
    const root = $scope && $scope[0] ? $scope[0] : document;
    root.querySelectorAll('.ka-woo-checkout-steps-wrapper').forEach(initSteps);
  };

  document.addEventListener("DOMContentLoaded", initAll);

  // WooCommerce checkout fragments updates can re-render parts of checkout.
  $(document.body).on("updated_checkout", function () {
    // Allow re-init after WooCommerce updates.
    document.querySelectorAll(".ka-woo-checkout-steps-wrapper").forEach((wrap) => {
      if (wrap && wrap.dataset) {
        delete wrap.dataset[INIT_FLAG];
      }
    });
    initAll();
  });

  $(window).on("elementor/frontend/init", function () {
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/woo_checkout_steps.default",
      function ($scope) {
        initInScope($scope);
      }
    );
  });
})(jQuery);





