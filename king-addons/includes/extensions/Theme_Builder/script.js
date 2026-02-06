document.addEventListener('DOMContentLoaded', () => {
    const addNewButton = document.getElementById('ka-theme-builder-add-new');
    const addNewEmptyButton = document.getElementById('ka-theme-builder-add-new-empty');
    const addNewModal = document.getElementById('ka-theme-builder-modal');
    const quickModal = document.getElementById('ka-theme-builder-quick-modal');
    const closeButtons = document.querySelectorAll('.ka-tb-modal-close');

    const isOpenClass = 'is-open';

    const openModal = (modal) => {
        if (!modal) return;
        modal.classList.add(isOpenClass);
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.classList.remove(isOpenClass);
        modal.setAttribute('aria-hidden', 'true');
    };

    if (addNewButton) {
        addNewButton.addEventListener('click', (event) => {
            event.preventDefault();
            openModal(addNewModal);
        });
    }

    if (addNewEmptyButton) {
        addNewEmptyButton.addEventListener('click', (event) => {
            event.preventDefault();
            openModal(addNewModal);
        });
    }

    // Type cards: preselect type/location and open create modal
    const typeButtons = document.querySelectorAll('.ka-tb-type');
    const typeSelect = document.getElementById('ka-tb-type');
    const locationSelect = document.getElementById('ka-tb-location');
    typeButtons.forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            const type = btn.getAttribute('data-ka-tb-type');
            const location = btn.getAttribute('data-ka-tb-location');

            if (typeSelect && type) {
                typeSelect.value = type;
            }
            if (locationSelect && location) {
                locationSelect.value = location;
            }
            openModal(addNewModal);
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            closeModal(addNewModal);
            closeModal(quickModal);
        });
    });

    const quickLinks = document.querySelectorAll('.ka-tb-quick-edit');
    const quickPriority = document.getElementById('ka-tb-quick-priority');
    const quickTemplateId = document.getElementById('ka-tb-quick-template-id');
    const quickEnabled = document.getElementById('ka-tb-quick-enabled');

    quickLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const templateId = link.getAttribute('data-template-id');
            const priority = link.getAttribute('data-priority');
            const enabled = link.getAttribute('data-enabled') === '1';

            if (quickTemplateId) {
                quickTemplateId.value = templateId || '';
            }
            if (quickPriority) {
                quickPriority.value = priority || '10';
            }
            if (quickEnabled) {
                quickEnabled.value = enabled ? '1' : '0';
            }

            openModal(quickModal);
        });
    });

    // Backdrop click closes (clicking inside modal does not)
    [addNewModal, quickModal].forEach((modal) => {
        if (!modal) return;
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    // ESC closes any open modal
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        closeModal(addNewModal);
        closeModal(quickModal);
    });

    // Dropdown actions
    const closeAllDropdowns = () => {
        document.querySelectorAll('[data-ka-dropdown].is-open').forEach((dd) => dd.classList.remove('is-open'));
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.ka-tb-dropdown-trigger');
        if (trigger) {
            const dropdown = trigger.closest('[data-ka-dropdown]');
            if (!dropdown) return;

            const isOpen = dropdown.classList.contains('is-open');
            closeAllDropdowns();
            dropdown.classList.toggle('is-open', !isOpen);
            return;
        }

        // Click inside menu should not close immediately (links will navigate)
        if (event.target.closest('.ka-tb-dropdown-menu')) {
            return;
        }

        closeAllDropdowns();
    });
});




