(function ($) {
    'use strict';

    const settings = window.kingAddonsWishlist || {};

    const getVariationId = ($button) => {
        const explicit = parseInt($button.data('variation-id'), 10);
        if (explicit) {
            return explicit;
        }

        const $form = $button.closest('form.variations_form');
        if ($form.length) {
            const picked = parseInt($form.find('input.variation_id').val(), 10);
            if (!isNaN(picked) && picked > 0) {
                return picked;
            }
        }

        return 0;
    };

    const updateButtonState = ($button, state) => {
        const labelDefault = settings.labels?.add || '';
        const labelAdded = settings.labels?.added || '';
        const isAdded = state === 'added';

        $button.attr('data-state', isAdded ? 'added' : 'default');
        $button.attr('aria-pressed', isAdded ? 'true' : 'false');
        $button.toggleClass('king-addons-wishlist-button--added', isAdded);
        $button.toggleClass('king-addons-wishlist-button--default', !isAdded);

        const $label = $button.find('.king-addons-wishlist-button__label');
        if ($label.length) {
            $label.text(isAdded ? labelAdded : labelDefault);
        }
    };

    const updateCounters = (count) => {
        $('.king-addons-wishlist-counter').each(function () {
            const $counter = $(this);
            $counter.attr('data-count', count);
            $counter.text(count);
        });
    };

    const handleToggle = ($button) => {
        const productId = parseInt($button.data('product-id'), 10);
        const wishlistId = $button.data('wishlist-id') || '';
        const variationId = getVariationId($button);

        if (!productId) {
            return;
        }

        $button.prop('disabled', true);

        $.post(settings.ajaxUrl, {
            action: 'king_addons_wishlist_toggle',
            nonce: settings.nonce,
            product_id: productId,
            variation_id: variationId,
            wishlist_id: wishlistId,
        })
            .done((response) => {
                if (response.success && response.data) {
                    updateButtonState($button, response.data.state);
                    if (response.data.count !== undefined) {
                        updateCounters(response.data.count);
                    }

                    if (response.data.state === 'default' && $button.hasClass('king-addons-wishlist-button--table-remove')) {
                        $button.closest('tr').remove();
                    }
                } else if (response?.data?.message) {
                    alert(response.data.message);
                } else {
                    alert(settings.labels?.error || 'Error');
                }
            })
            .fail((xhr) => {
                if (xhr?.responseJSON?.data?.message) {
                    alert(xhr.responseJSON.data.message);
                } else {
                    alert(settings.labels?.error || 'Error');
                }
            })
            .always(() => {
                $button.prop('disabled', false);
            });
    };

    $(document).on('click', '.king-addons-wishlist-button', function (event) {
        event.preventDefault();
        handleToggle($(this));
    });

    // Notes functionality (Pro)
    const handleNoteSave = ($container) => {
        const productId = parseInt($container.data('product-id'), 10);
        const variationId = parseInt($container.data('variation-id'), 10) || 0;
        const wishlistId = $container.data('wishlist-id') || '';
        const note = $container.find('.king-addons-wishlist-notes__input').val().trim();

        const $saveBtn = $container.find('.king-addons-wishlist-notes__save');
        $saveBtn.prop('disabled', true).text('...');

        $.post(settings.ajaxUrl, {
            action: 'king_addons_wishlist_update_note',
            nonce: settings.nonce,
            product_id: productId,
            variation_id: variationId,
            wishlist_id: wishlistId,
            note: note,
        })
            .done((response) => {
                if (response.success) {
                    const $display = $container.find('.king-addons-wishlist-notes__display');
                    const $form = $container.find('.king-addons-wishlist-notes__form');
                    const $text = $container.find('.king-addons-wishlist-notes__text');

                    $text.text(note);

                    if (note) {
                        $display.show();
                        $form.hide();
                    } else {
                        $display.hide();
                        $form.show();
                    }
                } else if (response?.data?.message) {
                    alert(response.data.message);
                }
            })
            .fail((xhr) => {
                if (xhr?.responseJSON?.data?.message) {
                    alert(xhr.responseJSON.data.message);
                } else {
                    alert(settings.labels?.error || 'Error');
                }
            })
            .always(() => {
                $saveBtn.prop('disabled', false).text('Save');
            });
    };

    // Edit note button click
    $(document).on('click', '.king-addons-wishlist-notes__edit-btn', function (event) {
        event.preventDefault();
        const $container = $(this).closest('.king-addons-wishlist-notes');
        $container.find('.king-addons-wishlist-notes__display').hide();
        $container.find('.king-addons-wishlist-notes__form').show();
        $container.find('.king-addons-wishlist-notes__input').focus();
    });

    // Save note button click
    $(document).on('click', '.king-addons-wishlist-notes__save', function (event) {
        event.preventDefault();
        const $container = $(this).closest('.king-addons-wishlist-notes');
        handleNoteSave($container);
    });

    // Cancel note edit
    $(document).on('click', '.king-addons-wishlist-notes__cancel', function (event) {
        event.preventDefault();
        const $container = $(this).closest('.king-addons-wishlist-notes');
        const $display = $container.find('.king-addons-wishlist-notes__display');
        const $form = $container.find('.king-addons-wishlist-notes__form');
        const originalNote = $display.find('.king-addons-wishlist-notes__text').text();

        $container.find('.king-addons-wishlist-notes__input').val(originalNote);
        $display.show();
        $form.hide();
    });

    // Submit on Enter key
    $(document).on('keydown', '.king-addons-wishlist-notes__input', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            const $container = $(this).closest('.king-addons-wishlist-notes');
            handleNoteSave($container);
        }
    });
})(jQuery);



