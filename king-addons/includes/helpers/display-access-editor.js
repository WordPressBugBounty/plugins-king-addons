(function ($) {
    'use strict';
    let selected = null;
    let rulesRevision = 0;
    let formRevision = 0;
    const current = () => selected || (window.elementor && elementor.getPanelView().getCurrentPageView().getOption('editedElementView'));
    const openSection = (id) => {
        const $section = $('.elementor-control-' + id);
        if (!$section.hasClass('elementor-open')) { $section.find('.elementor-panel-heading').trigger('click'); }
        window.setTimeout(syncDisclosure, 0);
    };
    const proReport = ($report, data) => {
        $report.attr('data-state', 'warning').empty();
        $('<p>').text(data.message).appendTo($report);
        $('<a target="_blank" rel="noopener">').attr('href', data.upgrade_url || kingDisplayEditor.upgradeUrl).text(kingDisplayEditor.upgradeLabel).appendTo($report);
    };
    const syncDisclosure = () => {
        const view = current();
        if (!view) { return; }
        const settings = view.model.get('settings');
        document.body.classList.toggle('ka-access-more-open', settings.get('ka_access_more') === 'yes');
        document.body.classList.toggle('ka-access-password', settings.get('protected_content_mode') === 'password');
        document.body.classList.toggle('ka-access-locker', settings.get('protected_content_mode') === 'locker');
        if (!kingDisplayEditor.hasPro) {
            $('.ka-display-pro-only').find('input, select, textarea, button').not('.elementor-repeater-row-item-title').prop('disabled', true);
            const options = {cd_rules_mode: ['rules'], protected_content_mode: ['password', 'locker'], cd_fallback_type: ['template'], protected_content_fallback_type: ['template', 'form']};
            Object.entries(options).forEach(([control, values]) => values.forEach(value => $('.elementor-control-' + control + ' select option[value="' + value + '"]').prop('disabled', true)));
        }
    };
    const patch = (values) => {
        const view = current();
        if (!view) { return; }
        if (window.$e && view.getContainer) {
            $e.run('document/elements/settings', {container: view.getContainer(), settings: values});
        } else { view.model.get('settings').set(values); }
        window.setTimeout(syncDisclosure, 0);
    };
    $(document).on('change', '.elementor-control-ka_access_more input, .elementor-control-protected_content_mode select, .elementor-control-protected_content_locker_template select, .elementor-control-cd_enable input, .elementor-control-cd_rules_mode select, .elementor-control-protected_content_enable input', () => window.setTimeout(syncDisclosure, 0));
    $(document).on('input change', '.elementor-control-cd_rules input, .elementor-control-cd_rules select, .elementor-control-cd_rules_mode select, .elementor-control-cd_invert input, .elementor-control-ka_display_visitor select', function () {
        rulesRevision++;
        $('.ka-display-report').attr('data-state', 'idle').text('Settings changed. Check rules again.');
    });
    $(document).on('click', '.elementor-control-cd_rules .elementor-repeater-add, .elementor-control-cd_rules .elementor-repeater-tool-duplicate, .elementor-control-cd_rules .elementor-repeater-tool-remove', function () {
        rulesRevision++;
        $('.ka-display-report').attr('data-state', 'idle').text('Settings changed. Check rules again.');
    });
    $(document).on('input change', '.elementor-control-protected_content_locker_template select, .elementor-control-protected_content_locker_form input, .elementor-control-protected_content_locker_method select', function () {
        formRevision++;
        $('.ka-access-form-report').attr('data-state', 'idle').text('Form changed. Check its setup again.');
    });
    $(document).on('click', '.elementor-control-king_addons_protected_content_section .elementor-panel-heading, .elementor-control-king_addons_cd_section .elementor-panel-heading, .elementor-control-cd_rules .elementor-repeater-row-item-title', () => window.setTimeout(syncDisclosure, 50));
    $(document).on('click', '.elementor-control-ka_access_check button', function () {
        const view = current(); if (!view) { return; }
        const revision = ++formRevision;
        const $report = $('.ka-access-form-report').attr('data-state', 'idle').text('Checking form…');
        $.post(kingDisplayEditor.ajax, {action: 'king_addons_display_preview', nonce: kingDisplayEditor.nonce, post_id: elementor.config.document.id, kind: 'form', settings: JSON.stringify(view.model.get('settings').toJSON())}).done(function (response) {
            if (revision !== formRevision) { return; }
            if (response.data.requires_pro) { proReport($report, response.data); return; }
            $report.attr('data-state', response.data.ready ? 'pass' : 'warning').text(response.data.message);
            if (response.data.template_id) { $('<a class="ka-access-edit-template" target="_blank" rel="noopener">').attr('href', kingDisplayEditor.ajax.replace('admin-ajax.php', 'post.php') + '?post=' + response.data.template_id + '&action=elementor').text('Edit this form template').appendTo($report); }
        }).fail(function () { if (revision === formRevision) { $report.attr('data-state', 'warning').text('Unable to check. Save the page and try again.'); } });
    });
    $(document).on('click', '.elementor-control-ka_display_preset_rules button', function () {
        patch({cd_enable: 'yes', cd_rules_mode: kingDisplayEditor.hasPro ? 'rules' : 'legacy'});
        openSection('king_addons_cd_section');
    });
    $(document).on('click', '.elementor-control-ka_display_preset_password button, .elementor-control-ka_display_preset_locker button', function () {
        const locker = $(this).closest('.elementor-control-ka_display_preset_locker').length > 0;
        if (!kingDisplayEditor.hasPro) {
            openSection('king_addons_protected_content_section');
            window.setTimeout(() => { const card = document.querySelector('.elementor-control-ka_display_upgrade_pc'); if (card) { card.scrollIntoView({block: 'nearest'}); } }, 0);
            return;
        }
        patch({protected_content_enable: 'yes', protected_content_mode: locker ? 'locker' : 'password'});
        openSection('king_addons_protected_content_section');
    });
    $(document).on('click', '.elementor-control-ka_display_check_button button', function () {
        const view = current();
        if (!view) { return; }
        const revision = ++rulesRevision;
        const $report = $('.ka-display-report').attr('data-state', 'idle').empty().text('Checking…');
        const settings = view.model.get('settings').toJSON();
        const id = elementor.config.document.id;
        let query = '';
        const frame = document.getElementById('elementor-preview-iframe');
        if (frame && frame.contentWindow) { query = frame.contentWindow.location.search; }
        $.post(kingDisplayEditor.ajax, {action: 'king_addons_display_preview', nonce: kingDisplayEditor.nonce, post_id: id, settings: JSON.stringify(settings), visitor: settings.ka_display_visitor || 'current', query: query.replace(/^\?/, '')}).done(function (response) {
            if (revision !== rulesRevision) { return; }
            $report.empty();
            if (response.data.requires_pro) { proReport($report, response.data); return; }
            if (!response.success) { $report.attr('data-state', 'warning').text(response.data.message || 'Unable to check these rules.'); return; }
            const result = response.data;
            if (result.legacy) { $report.text(result.message); return; }
            $report.attr('data-state', result.pass ? 'pass' : 'warning');
            $('<strong>').text(result.pass ? 'Rules match' : 'Rules do not match').appendTo($report);
            if (result.empty) { $('<p>').text('No rules yet — the block is always shown. Add a rule to narrow the audience.').appendTo($report); }
            const $list = $('<ul>').appendTo($report);
            result.checks.forEach(function (item) { $('<li>').text((item.pass ? 'Matches: ' : 'Does not match: ') + item.label + ' — ' + item.detail).appendTo($list); });
            $('<p>').text('Site time: ' + result.clock).appendTo($report);
            $('<small>').text('Access settings and click-to-reveal may add another step.').appendTo($report);
            if ((settings.cd_rules || []).some(rule => rule.type === 'meta' || rule.type === 'acf')) { $('<p>').text('Loop preview uses this page. Check actual items on the frontend.').appendTo($report); }
        }).fail(function () { if (revision === rulesRevision) { $report.attr('data-state', 'warning').text('Unable to check. Save the page and try again.'); } });
    });
    $(window).on('elementor:init', function () {
        ['widget', 'section', 'container', 'column'].forEach(function (kind) {
            elementor.hooks.addAction('panel/open_editor/' + kind, function (panel, model, view) { selected = view; syncDisclosure(); window.setTimeout(syncDisclosure, 100); });
        });
    });
    $(function () {
        if (kingDisplayEditor.hasPro) { return; }
        const panel = document.getElementById('elementor-panel');
        if (!panel) { return; }
        let pending = false;
        const observer = new MutationObserver(function () {
            if (pending) { return; }
            pending = true;
            window.requestAnimationFrame(function () { pending = false; syncDisclosure(); });
        });
        observer.observe(panel, {childList: true, subtree: true});
    });
}(jQuery));
