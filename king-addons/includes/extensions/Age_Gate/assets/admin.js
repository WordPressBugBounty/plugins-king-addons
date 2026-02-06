(function ($) {
    $(function () {
        const tabs = $('.king-addons-age-gate-admin__tabs .nav-tab');
        const panels = $('.king-addons-age-gate-admin__panel');

        tabs.on('click', function (event) {
            event.preventDefault();
            const tab = $(this).data('tab');
            tabs.removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            panels.hide();
            panels.filter('[data-tab="' + tab + '"]').show();
        });

        const denySelect = $('select[name="king_addons_age_gate_options[behaviour][deny_action]"]');
        const redirectRow = $('select[name="king_addons_age_gate_options[behaviour][deny_redirect_page]"]').closest('tr');

        const toggleRedirectRow = () => {
            if (denySelect.val() === 'redirect') {
                redirectRow.show();
            } else {
                redirectRow.hide();
            }
        };

        denySelect.on('change', toggleRedirectRow);
        toggleRedirectRow();
    });
})(jQuery);



