(function ($) {
    'use strict';

    const openLocker = async function (node) {
        const $locker = $(node).closest('.king-addons-protected-locker');
        try { await window.kingDisplayInline.refresh($locker, $locker.attr('data-ka-document'), $locker.attr('data-ka-element')); }
        catch (error) { $('<p>').text(error.message).appendTo($locker); }
    };
    $(document).on('king-addons/form/submitted.kingAddonsLocker', '.king-addons-protected-locker form', function () {
        if ($(this).closest('[data-king-locker-context]').length) {
            openLocker(this);
        }
    });

    document.addEventListener('wpcf7mailsent', function (event) {
        if (event.target instanceof Element && event.target.closest('.king-addons-protected-locker')) {
            openLocker(event.target);
        }
    });
}(jQuery));
