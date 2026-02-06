(function($) {
    'use strict';

    function toggleSections(source) {
        $('.kng-maintenance-section').removeClass('is-active');
        $('.kng-maintenance-section[data-section="' + source + '"]').addClass('is-active');
    }

    function toggleTemplateContent(templateId) {
        $('.kng-maintenance-content-block').removeClass('is-active');
        $('.kng-maintenance-content-block[data-template="' + templateId + '"]').addClass('is-active');
    }

    $(document).ready(function() {
        var $sourceInputs = $('input[name="kng_maintenance_settings[template_source]"]');
        var activeSource = $sourceInputs.filter(':checked').val() || 'built_in';
        toggleSections(activeSource);

        var $templateInputs = $('input[name="kng_maintenance_settings[template_id]"]');
        var activeTemplate = $templateInputs.filter(':checked').val() || 'minimal';
        toggleTemplateContent(activeTemplate);

        $sourceInputs.on('change', function() {
            toggleSections($(this).val());
        });

        $templateInputs.on('change', function() {
            toggleTemplateContent($(this).val());
        });

        var $segment = $('#ka-v3-theme-segment');
        var $buttons = $segment.find('.ka-v3-segmented-btn');
        var mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
        var mode = ($segment.attr('data-active') || 'dark').toString();
        var mqlHandler = null;

        function saveUISetting(value) {
            if (!window.KNGMaintenance) {
                return;
            }
            $.post(KNGMaintenance.ajaxUrl, {
                action: 'king_addons_save_dashboard_ui',
                nonce: KNGMaintenance.themeNonce,
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

        function nextIndex($container) {
            var max = -1;
            $container.find('.kng-repeat-row').each(function() {
                var idx = parseInt($(this).attr('data-index'), 10);
                if (!isNaN(idx)) {
                    max = Math.max(max, idx);
                }
            });
            return max + 1;
        }

        function addRowFromTemplate(templateId, $target) {
            var tmpl = document.getElementById(templateId);
            if (!tmpl || !tmpl.content) {
                return;
            }
            var idx = nextIndex($target);
            var html = tmpl.innerHTML.replace(/__INDEX__/g, String(idx));
            $target.append($(html));
        }

        $(document).on('click', '#kng-add-window', function(e) {
            e.preventDefault();
            addRowFromTemplate('kng-maintenance-window-template', $('#kng-maintenance-windows'));
        });

        $(document).on('click', '#kng-add-recurring', function(e) {
            e.preventDefault();
            addRowFromTemplate('kng-maintenance-recurring-template', $('#kng-maintenance-recurring'));
        });

        $(document).on('click', '.kng-remove-row', function(e) {
            e.preventDefault();
            var $row = $(this).closest('.kng-repeat-row');
            $row.remove();
        });

        function updateRecurringRow($row) {
            var freq = ($row.find('.kng-recurring-frequency').val() || 'daily').toString();
            $row.find('.kng-recurring-weekly').toggle(freq === 'weekly');
            $row.find('.kng-recurring-monthly').toggle(freq === 'monthly');
        }

        $(document).on('change', '.kng-recurring-frequency', function() {
            updateRecurringRow($(this).closest('.kng-repeat-row'));
        });

        $('.kng-repeat-row').each(function() {
            updateRecurringRow($(this));
        });

        var copyTimer = null;

        function showCopyStatus($status, text) {
            if (!$status || !$status.length) {
                return;
            }

            $status.text(text || 'Copied');
            $status.addClass('is-visible');

            if (copyTimer) {
                window.clearTimeout(copyTimer);
            }

            copyTimer = window.setTimeout(function() {
                $status.removeClass('is-visible');
            }, 1400);
        }

        async function copyToClipboard(text) {
            if (!text) {
                return false;
            }

            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(text);
                    return true;
                }
            } catch (e) {
                // fall back below
            }

            try {
                var $tmp = $('<textarea readonly></textarea>')
                    .css({ position: 'absolute', left: '-9999px', top: '0', opacity: '0' })
                    .val(text)
                    .appendTo('body');
                $tmp[0].select();
                $tmp[0].setSelectionRange(0, text.length);
                var ok = document.execCommand('copy');
                $tmp.remove();
                return !!ok;
            } catch (err) {
                return false;
            }
        }

        $(document).on('click', '.kng-maintenance-copy-token', function(e) {
            e.preventDefault();
            var $row = $(this).closest('.kng-maintenance-token-row');
            var $input = $row.find('.kng-maintenance-token-input').first();
            var $status = $row.find('.kng-maintenance-copy-status').first();
            var val = ($input.val() || '').toString();

            copyToClipboard(val).then(function(ok) {
                if (ok) {
                    showCopyStatus($status, 'Copied');
                } else {
                    showCopyStatus($status, 'Copy failed');
                }
            });
        });
    });
})(jQuery);
