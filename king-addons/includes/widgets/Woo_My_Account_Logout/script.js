/**
 * Woo My Account Logout widget behavior.
 *
 * Shows a confirmation modal before logging out.
 */
(function ($) {
  "use strict";

  const MODAL_ID = 'ka-logout-modal';
  const FLAG = "kaWooLogoutBound";

  const createModal = () => {
    const modal = document.createElement('div');
    modal.className = 'ka-logout-modal';
    modal.id = MODAL_ID;
    modal.innerHTML = `
      <div class="ka-logout-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="${MODAL_ID}-title">
        <h3 id="${MODAL_ID}-title" class="ka-logout-modal__title">Confirm logout</h3>
        <p class="ka-logout-modal__desc">Are you sure you want to log out?</p>
        <div class="ka-logout-modal__actions">
          <a class="ka-logout-modal__btn ka-logout-modal__btn--primary" data-action="confirm" href="#">Yes, log out</a>
          <button class="ka-logout-modal__btn ka-logout-modal__btn--ghost" type="button" data-action="cancel">Cancel</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
    return modal;
  };

  const getModal = () => document.getElementById(MODAL_ID) || createModal();

  const openModal = (logoutUrl) => {
    const modal = getModal();
    const confirmBtn = modal.querySelector('[data-action="confirm"]');
    confirmBtn.setAttribute('href', logoutUrl);
    modal.classList.add('is-visible');
    confirmBtn.focus();
  };

  const closeModal = () => {
    const modal = document.getElementById(MODAL_ID);
    if (modal) {
      modal.classList.remove('is-visible');
    }
  };

  /**
   * Bind delegated listeners once per page.
   *
   * @returns {void}
   */
  const bindOnce = () => {
    if (window[FLAG]) {
      return;
    }
    window[FLAG] = true;

    document.addEventListener('click', (e) => {
      const target = e.target;
      if (!(target instanceof Element)) {
        return;
      }

      const trigger = target.closest('.ka-woo-account-logout[data-ka-logout-confirm="1"]');
      if (!trigger) {
        const cancelBtn = target.closest(`#${MODAL_ID} [data-action="cancel"]`);
        if (cancelBtn) {
          e.preventDefault();
          closeModal();
        }
        return;
      }
      e.preventDefault();
      const url = trigger.getAttribute('href');
      if (!url) {
        return;
      }
      openModal(url);
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeModal();
      }
    });
  };

  document.addEventListener("DOMContentLoaded", bindOnce);

  $(window).on("elementor/frontend/init", function () {
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/woo_my_account_logout.default",
      function () {
        bindOnce();
      }
    );
  });
})(jQuery);





