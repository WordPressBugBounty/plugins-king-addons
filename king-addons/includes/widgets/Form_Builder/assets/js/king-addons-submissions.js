jQuery(document).ready(function ($) {
    // Remove default "Add New" button on submissions list page
    $('.page-title-action').remove();

    /* -------------------- LIST TABLE (read/unread toggle) -------------------- */
    $('body').on('click', '.column-read_status', function () {
        const post_id = $(this).parent().attr('id').replace('post-', '');
        const read_status = $(this).text() === 'Read' ? '0' : '1';
        const nonce = KingAddonsSubmissions.nonce; // localized in View_Submissions_Pro

        $.post(KingAddonsSubmissions.ajaxurl, {
            action: 'king_addons_submissions_update_read_status',
            post_id,
            read_status,
            nonce,
        });
    });

    /* -------------------- SINGLE SUBMISSION SCREEN -------------------- */
    // Add hidden input to store changes
    $('<input>', {
        type: 'hidden',
        id: 'king_addons_submission_changes',
        name: 'king_addons_submission_changes',
    }).appendTo('#post');

    let changes = {};

    // Initially lock inputs
    $('.king-addons-submissions-wrap input, .king-addons-submissions-wrap textarea').each(function () {
        if ($(this).is('[type="checkbox"],[type="radio"]')) {
            $(this).prop('disabled', true);
        } else {
            $(this).prop('readonly', true);
        }
    });

    // Track edits
    $('input, textarea').on('change', function () {
        let key = $(this).attr('id');
        let value = [];

        if ($(this).is('[type="checkbox"],[type="radio"]')) {
            value[0] = $(this).attr('type');
            value[1] = [];
            value[2] = $(this).closest('.king-addons-submissions-wrap').find('label:first-of-type').text();
            key = $(this).closest('.king-addons-submissions-wrap').find('label:first-of-type').attr('for');

            $(this).closest('.king-addons-submissions-wrap').find('input').each(function () {
                value[1].push([$(this).val(), $(this).is(':checked'), $(this).attr('name'), $(this).attr('id')]);
            });
        } else {
            value[0] = $(this).attr('type');
            value[1] = $(this).val();
            value[2] = $(this).prev('label').text();
        }
        changes[key] = value;
        $('#king_addons_submission_changes').val(JSON.stringify(changes));
    });

    // Toggle edit mode
    $('.king-addons-edit-submissions').on('click', function (e) {
        e.preventDefault();
        const btn = $(this);
        $('#king_addons_submission_changes').val('');

        $('input, textarea').each(function () {
            if ($(this).prop('readonly') || $(this).prop('disabled')) {
                $(this).prop('readonly', false).prop('disabled', false);
                btn.text('Cancel');
            } else {
                if ($(this).is('[type="checkbox"],[type="radio"]')) {
                    $(this).prop('disabled', true);
                } else {
                    $(this).prop('readonly', true);
                }
                btn.text('Edit');
            }
        });
    });

    // Highlight unread rows on list screen
    $('.king-addons-submission-unread').closest('tr').addClass('king-addons-submission-unread-column');

    /* --------------- Sidebar meta on single submission --------------- */
    if ($('#postbox-container-1').find('#submitdiv').length) {
        const s = KingAddonsSubmissions; // alias
        $('#minor-publishing').remove();
        $('#submitdiv .postbox-header h2').text('Extra Info');

        const info = [
            ['Form', `<a href="${s.form_page_editor}" target="_blank">${s.form_name} (${s.form_id})</a>`],
            ['Page', `<a href="${s.form_page_url}" target="_blank">${s.form_page}</a>`],
            ['Created at', s.post_created],
            ['Updated at', s.post_updated],
            ['User IP', s.agent_ip],
            ['User Agent', s.form_agent],
        ];

        info.forEach(function (row) {
            $('<div>', {class: 'misc-pub-section', html: `${row[0]}: <span class="king-addons-submissions-meta">${row[1]}</span>`}).insertBefore('#major-publishing-actions');
        });

        // Reveal meta boxes
        $('#postbox-container-1, #postbox-container-2').css('opacity', 1);
    }
}); 