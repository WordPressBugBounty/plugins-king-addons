(function($) {
    'use strict';

    function parseLogPayload(raw) {
        if (!raw) {
            return {};
        }
        try {
            return JSON.parse(raw);
        } catch (e) {
            return {};
        }
    }

    function setText($drawer, field, value) {
        var text = value || '-';
        $drawer.find('[data-field="' + field + '"]').text(text);
    }

    function setObject($drawer, label, url) {
        var $field = $drawer.find('[data-field="object"]');
        $field.empty();

        if (url) {
            var $link = $('<a></a>');
            $link.attr('href', url);
            $link.attr('target', '_blank');
            $link.attr('rel', 'noopener');
            $link.text(label || '-');
            $field.append($link);
        } else {
            $field.text(label || '-');
        }
    }

    function setSeverity($drawer, severity) {
        var $field = $drawer.find('[data-field="severity"]');
        var value = severity || '-';
        $field.text(value);
        $field.removeClass('kng-severity-info kng-severity-notice kng-severity-warning kng-severity-critical');
        if (severity) {
            $field.addClass('kng-severity-' + severity);
        }
    }

    function openDrawer(payload) {
        var $drawer = $('.kng-activity-drawer');

        setText($drawer, 'event', payload.event);
        setText($drawer, 'time', payload.time);
        setSeverity($drawer, payload.severity);
        setText($drawer, 'user', payload.user);
        setText($drawer, 'role', payload.role);
        setText($drawer, 'ip', payload.ip);
        setText($drawer, 'source', payload.source);
        setText($drawer, 'context', payload.context);
        setText($drawer, 'message', payload.message);
        setText($drawer, 'user_agent', payload.user_agent);
        setObject($drawer, payload.object_label, payload.object_url);

        var dataText = '';
        if (payload.data && typeof payload.data === 'object') {
            dataText = JSON.stringify(payload.data, null, 2);
        }
        $drawer.find('[data-field="data"]').text(dataText || '-');

        $drawer.addClass('is-open').attr('aria-hidden', 'false');
    }

    function closeDrawer() {
        $('.kng-activity-drawer').removeClass('is-open').attr('aria-hidden', 'true');
    }

    $(document).on('click', '.kng-log-view', function() {
        var raw = $(this).attr('data-log');
        var payload = parseLogPayload(raw);
        openDrawer(payload);
    });

    $(document).on('click', '.kng-activity-drawer-close, .kng-activity-drawer-overlay', function() {
        closeDrawer();
    });

    var $segment = $('#ka-v3-theme-segment');
    var $buttons = $segment.find('.ka-v3-segmented-btn');
    var mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var mode = ($segment.attr('data-active') || 'dark').toString();
    var mqlHandler = null;

    function saveUISetting(value) {
        if (!window.KNGActivityLog) {
            return;
        }
        $.post(KNGActivityLog.ajaxUrl, {
            action: 'king_addons_save_dashboard_ui',
            nonce: KNGActivityLog.themeNonce,
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
})(jQuery);
