(function($) {
    'use strict';

    function updateShortPreview() {
        var $preview = $('#kng-short-preview');
        if (!$preview.length || !window.KNGSmartLinks) {
            return;
        }

        var basePath = (KNGSmartLinks.basePath || 'go').replace(/^\/+|\/+$/g, '');
        var slug = $('#kng-slug-input').val() || '';
        slug = slug.trim();
        var displaySlug = slug !== '' ? slug : 'your-slug';
        var shortUrl = KNGSmartLinks.homeUrl + basePath + '/' + displaySlug;

        $preview.text(shortUrl);
        $preview.attr('data-copy', shortUrl);
        $('#kng-copy-short').attr('data-copy', shortUrl);
    }

    function updateUtmPreview() {
        var $preview = $('#kng-utm-preview');
        if (!$preview.length) {
            return;
        }

        var destination = $('#kng-destination').val() || '';
        destination = destination.trim();
        if (destination === '') {
            $preview.text('');
            return;
        }

        var url;
        try {
            url = new URL(destination);
        } catch (e) {
            $preview.text(destination);
            return;
        }

        if ($('#kng-utm-enabled').is(':checked')) {
            var fields = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
            fields.forEach(function(key) {
                var value = $('#kng-' + key).val();
                if (value) {
                    url.searchParams.set(key, value);
                }
            });
        }

        $preview.text(url.toString());
    }

    function toggleSlugMode() {
        var mode = $('input[name="slug_mode"]:checked').val();
        if (mode === 'manual') {
            $('.kng-slug-manual').show();
        } else {
            $('.kng-slug-manual').hide();
        }
        updateShortPreview();
    }

    $(document).ready(function() {
        // Theme toggle
        var $segment = $('#ka-v3-theme-segment');
        var $buttons = $segment.find('.ka-v3-segmented-btn');
        var mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
        var mode = ($segment.attr('data-active') || 'dark').toString();
        var mqlHandler = null;

        function saveUISetting(value) {
            if (!window.KNGSmartLinks) {
                return;
            }
            $.post(KNGSmartLinks.ajaxUrl, {
                action: 'king_addons_save_dashboard_ui',
                nonce: KNGSmartLinks.themeNonce,
                key: 'theme_mode',
                value: value
            });
        }

        function updateSegment(activeMode) {
            $segment.attr('data-active', activeMode);
            $buttons.each(function() {
                var theme = ($(this).data('theme') || 'dark').toString();
                $(this).attr('aria-pressed', theme === activeMode ? 'true' : 'false');
            });
        }

        function applyTheme(isDark) {
            $('body').toggleClass('ka-v3-dark', isDark);
            document.documentElement.classList.toggle('ka-v3-dark', isDark);
        }

        function setThemeMode(nextMode, save) {
            mode = (nextMode || 'dark').toString();
            updateSegment(mode);

            if (mqlHandler && mql) {
                if (mql.removeEventListener) {
                    mql.removeEventListener('change', mqlHandler);
                } else if (mql.removeListener) {
                    mql.removeListener(mqlHandler);
                }
                mqlHandler = null;
            }

            if (mode === 'auto') {
                applyTheme(!!(mql && mql.matches));
                mqlHandler = function(e) {
                    if (mode !== 'auto') {
                        return;
                    }
                    applyTheme(!!e.matches);
                };
                if (mql) {
                    if (mql.addEventListener) {
                        mql.addEventListener('change', mqlHandler);
                    } else if (mql.addListener) {
                        mql.addListener(mqlHandler);
                    }
                }
            } else {
                applyTheme(mode === 'dark');
            }

            if (save) {
                saveUISetting(mode);
            }
        }

        $segment.on('click', '.ka-v3-segmented-btn', function(e) {
            e.preventDefault();
            setThemeMode(($(this).data('theme') || 'dark').toString(), true);
        });

        window.kaV3ToggleDark = function() {
            var isDark = $('body').hasClass('ka-v3-dark');
            setThemeMode(isDark ? 'light' : 'dark', true);
        };

        setThemeMode(mode, false);

        // Copy buttons
        $(document).on('click', '.ka-copy-btn, #kng-short-preview', function(e) {
            var text = $(this).data('copy') || $(this).attr('data-copy') || $(this).text();
            if (!text) {
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text);
            } else {
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(text).select();
                document.execCommand('copy');
                $temp.remove();
            }

            var $btn = $(this).hasClass('ka-copy-btn') ? $(this) : null;
            if ($btn && $btn.length) {
                $btn.addClass('is-copied');
                $btn.find('span').last().text('Copied');
                setTimeout(function() {
                    $btn.removeClass('is-copied');
                    $btn.find('span').last().text('Copy');
                }, 1200);
            }
        });

        // Short URL preview
        $(document).on('input', '#kng-slug-input', updateShortPreview);
        $(document).on('change', 'input[name="slug_mode"]', toggleSlugMode);
        toggleSlugMode();

        // UTM preview
        $(document).on('input change', '#kng-destination, #kng-utm-enabled, #kng-utm_source, #kng-utm_medium, #kng-utm_campaign, #kng-utm_term, #kng-utm_content', updateUtmPreview);
        updateUtmPreview();

        // Select all in lists
        $('#kng-select-all').on('change', function() {
            var checked = $(this).is(':checked');
            $('.kng-link-checkbox').prop('checked', checked);
        });

        // Settings tab navigation
        $('.kng-settings-tab').on('click', function() {
            var target = $(this).data('tab');
            $('.kng-settings-tab').removeClass('active');
            $(this).addClass('active');
            $('.kng-settings-content').removeClass('active');
            $('.kng-settings-content[data-tab=\"' + target + '\"]').addClass('active');
        });
    });
})(jQuery);
