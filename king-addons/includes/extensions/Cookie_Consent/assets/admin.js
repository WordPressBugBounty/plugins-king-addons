document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.king-addons-cookie-tabs__btn');
    const panels = document.querySelectorAll('.king-addons-cookie-panel');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.getAttribute('data-tab');
            tabs.forEach((btn) => btn.classList.remove('is-active'));
            panels.forEach((panel) => panel.classList.remove('is-active'));

            tab.classList.add('is-active');
            const targetPanel = document.querySelector(`.king-addons-cookie-panel[data-tab="${target}"]`);
            if (targetPanel) {
                targetPanel.classList.add('is-active');
                targetPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    if (typeof jQuery !== 'undefined' && jQuery.fn.wpColorPicker) {
        jQuery('.king-addons-color').wpColorPicker();
    }
});



