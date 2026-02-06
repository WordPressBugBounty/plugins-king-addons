(function($) {
    'use strict';

    function parseJSON(value, fallback) {
        try {
            var parsed = JSON.parse(value);
            return parsed && typeof parsed === 'object' ? parsed : fallback;
        } catch (e) {
            return fallback;
        }
    }

    function csvToArray(text) {
        var rows = [];
        var row = [];
        var current = '';
        var inQuotes = false;
        var i = 0;

        text = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n');

        for (i = 0; i < text.length; i++) {
            var char = text[i];
            var next = text[i + 1];

            if (char === '"') {
                if (inQuotes && next === '"') {
                    current += '"';
                    i++;
                } else {
                    inQuotes = !inQuotes;
                }
                continue;
            }

            if (char === ',' && !inQuotes) {
                row.push(current);
                current = '';
                continue;
            }

            if (char === '\n' && !inQuotes) {
                row.push(current);
                rows.push(row);
                row = [];
                current = '';
                continue;
            }

            current += char;
        }

        if (current !== '' || row.length) {
            row.push(current);
            rows.push(row);
        }

        return rows;
    }

    function TableBuilder($editor) {
        this.$editor = $editor;
        this.state = parseJSON($editor.attr('data-state') || '{}', {});
        this.state.columns = this.state.columns || [];
        this.state.rows = this.state.rows || [];
        this.state.config = this.state.config || {};
        this.state.style = this.state.style || {};
        this.state.filters = this.state.filters || {};
        this.state.responsive = this.state.responsive || {};
        this.selected = { row: 0, col: 0 };

        this.$grid = $editor.find('#kng-table-grid');
        this.$columnList = $editor.find('.kng-column-list');

        this.normalizeState();
        this.bindEvents();
        this.render();
    }

    TableBuilder.prototype.normalizeState = function() {
        if (!this.state.columns.length) {
            this.state.columns = [{ label: 'Column 1', type: 'text', sortable: true, hide_mobile: false, align: 'left' }];
        }

        var colCount = this.state.columns.length;
        this.state.rows = this.state.rows.map(function(row) {
            row = Array.isArray(row) ? row : [];
            while (row.length < colCount) {
                row.push({ value: '', tooltip: '', rowspan: 1, colspan: 1 });
            }
            return row;
        });

        if (!this.state.rows.length) {
            this.state.rows = [[{ value: '', tooltip: '', rowspan: 1, colspan: 1 }]];
        }
    };

    TableBuilder.prototype.bindEvents = function() {
        var self = this;

        var $segment = $('#ka-v3-theme-segment');
        var $buttons = $segment.find('.ka-v3-segmented-btn');
        var mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
        var mode = ($segment.attr('data-active') || 'dark').toString();
        var mqlHandler = null;

        function saveUISetting(value) {
            if (!window.KNGTableBuilder) {
                return;
            }
            $.post(KNGTableBuilder.ajaxUrl, {
                action: 'king_addons_save_dashboard_ui',
                nonce: KNGTableBuilder.themeNonce,
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

        $('.kng-add-row').on('click', function() {
            self.addRow();
        });

        $('.kng-remove-row').on('click', function() {
            self.removeRow();
        });

        $('.kng-add-column').on('click', function() {
            self.addColumn();
        });

        $('.kng-remove-column').on('click', function() {
            self.removeColumn();
        });

        $('.kng-apply-span').on('click', function() {
            self.applySpan();
        });

        $('.kng-apply-import').on('click', function() {
            var text = $('.kng-table-import-text').val() || '';
            self.applyImport(text);
        });

        $('.kng-paste-clipboard').on('click', function() {
            if (navigator.clipboard && navigator.clipboard.readText) {
                navigator.clipboard.readText().then(function(text) {
                    $('.kng-table-import-text').val(text);
                });
            }
        });

        $('.kng-table-form').on('submit', function() {
            self.syncHiddenFields();
        });
    };

    TableBuilder.prototype.render = function() {
        this.renderTable();
        this.renderColumns();
        this.bindDynamicInputs();
        this.updateInspector();
    };

    TableBuilder.prototype.renderTable = function() {
        var self = this;
        var columns = this.state.columns;
        var rows = this.state.rows;
        var $thead = $('<tr></tr>');

        columns.forEach(function(column, index) {
            var $th = $('<th contenteditable="true"></th>');
            $th.text(column.label || ('Column ' + (index + 1)));
            $th.attr('data-col', index);
            $thead.append($th);
        });

        this.$grid.find('thead').html($thead);

        var $tbody = $('<tbody></tbody>');
        var skipMap = [];

        rows.forEach(function(row, rowIndex) {
            var $tr = $('<tr></tr>');
            var colIndex = 0;

            while (colIndex < columns.length) {
                if (skipMap[colIndex] && skipMap[colIndex] > 0) {
                    skipMap[colIndex]--;
                    colIndex++;
                    continue;
                }

                var cell = row[colIndex] || { value: '', tooltip: '', rowspan: 1, colspan: 1 };
                var colspan = parseInt(cell.colspan || 1, 10);
                var rowspan = parseInt(cell.rowspan || 1, 10);

                var $td = $('<td contenteditable="true"></td>');
                $td.text(cell.value || '');
                $td.attr('data-row', rowIndex);
                $td.attr('data-col', colIndex);
                $td.attr('data-tooltip', cell.tooltip || '');
                $td.attr('data-rowspan', rowspan);
                $td.attr('data-colspan', colspan);

                if (rowspan > 1) {
                    $td.attr('rowspan', rowspan);
                    for (var spanCol = 0; spanCol < colspan; spanCol++) {
                        skipMap[colIndex + spanCol] = rowspan - 1;
                    }
                }

                if (colspan > 1) {
                    $td.attr('colspan', colspan);
                }

                $tr.append($td);
                colIndex += colspan;
            }

            $tbody.append($tr);
        });

        this.$grid.find('tbody').replaceWith($tbody);

        this.$grid.find('td, th').on('click', function() {
            var $cell = $(this);
            self.selectCell($cell);
        });

        this.$grid.find('td').on('input', function() {
            var $cell = $(this);
            self.updateCellValue($cell);
        });

        this.$grid.find('th').on('input', function() {
            var $cell = $(this);
            self.updateColumnLabel($cell);
        });
    };

    TableBuilder.prototype.renderColumns = function() {
        var self = this;
        this.$columnList.empty();

        this.state.columns.forEach(function(column, index) {
            var $row = $('<div class="kng-column-item"></div>');
            var $label = $('<input type="text" class="kng-column-label" />');
            $label.val(column.label || '');
            $label.attr('data-col', index);

            var $type = $('<select class="kng-column-type"></select>');
            $type.append('<option value="text">Text</option>');
            $type.append('<option value="number">Number</option>');
            $type.append('<option value="link">Link</option>');
            $type.val(column.type || 'text');
            $type.attr('data-col', index);

            var $hide = $('<label><input type="checkbox" class="kng-column-hide" /> Hide on mobile</label>');
            $hide.find('input').prop('checked', !!column.hide_mobile).attr('data-col', index);

            var $wrap = $('<div></div>');
            $wrap.append($label).append($type).append($hide);

            $row.append($wrap);
            self.$columnList.append($row);
        });
    };

    TableBuilder.prototype.bindDynamicInputs = function() {
        var self = this;

        this.$columnList.find('.kng-column-label').off('input').on('input', function() {
            var col = parseInt($(this).attr('data-col'), 10);
            self.state.columns[col].label = $(this).val();
            self.$grid.find('th[data-col="' + col + '"]').text($(this).val());
        });

        this.$columnList.find('.kng-column-type').off('change').on('change', function() {
            var col = parseInt($(this).attr('data-col'), 10);
            self.state.columns[col].type = $(this).val();
        });

        this.$columnList.find('.kng-column-hide').off('change').on('change', function() {
            var col = parseInt($(this).attr('data-col'), 10);
            self.state.columns[col].hide_mobile = $(this).is(':checked');
        });

        this.$editor.find('[data-config]').off('change input').on('change input', function() {
            var key = $(this).attr('data-config');
            if ($(this).is(':checkbox')) {
                self.state.config[key] = $(this).is(':checked');
            } else {
                self.state.config[key] = $(this).val();
            }
        });

        this.$editor.find('[data-style]').off('change').on('change', function() {
            var key = $(this).attr('data-style');
            if ($(this).is(':checkbox')) {
                self.state.style[key] = $(this).is(':checked');
            } else {
                self.state.style[key] = $(this).val();
            }
        });

        this.$editor.find('[data-responsive]').off('change').on('change', function() {
            var key = $(this).attr('data-responsive');
            if ($(this).is(':checkbox')) {
                self.state.responsive[key] = $(this).is(':checked');
            } else {
                self.state.responsive[key] = $(this).val();
            }
        });

        this.$editor.find('.kng-cell-tooltip').off('input').on('input', function() {
            var $cell = self.getSelectedCell();
            if (!$cell) {
                return;
            }
            var tooltip = $(this).val();
            $cell.attr('data-tooltip', tooltip);
            self.updateCellMeta($cell);
        });
    };

    TableBuilder.prototype.selectCell = function($cell) {
        this.$grid.find('.kng-selected').removeClass('kng-selected');
        $cell.addClass('kng-selected');

        if ($cell.is('th')) {
            this.selected = { row: 0, col: parseInt($cell.attr('data-col'), 10) };
        } else {
            this.selected = {
                row: parseInt($cell.attr('data-row'), 10),
                col: parseInt($cell.attr('data-col'), 10)
            };
        }

        this.updateInspector();
    };

    TableBuilder.prototype.getSelectedCell = function() {
        var row = this.selected.row;
        var col = this.selected.col;
        return this.$grid.find('td[data-row="' + row + '"][data-col="' + col + '"]');
    };

    TableBuilder.prototype.updateInspector = function() {
        var $cell = this.getSelectedCell();
        if (!$cell || !$cell.length) {
            return;
        }

        var tooltip = $cell.attr('data-tooltip') || '';
        var rowspan = $cell.attr('data-rowspan') || 1;
        var colspan = $cell.attr('data-colspan') || 1;

        this.$editor.find('.kng-cell-tooltip').val(tooltip);
        this.$editor.find('.kng-cell-rowspan').val(rowspan);
        this.$editor.find('.kng-cell-colspan').val(colspan);
    };

    TableBuilder.prototype.applySpan = function() {
        var $cell = this.getSelectedCell();
        if (!$cell || !$cell.length) {
            return;
        }

        var rowspan = parseInt(this.$editor.find('.kng-cell-rowspan').val(), 10) || 1;
        var colspan = parseInt(this.$editor.find('.kng-cell-colspan').val(), 10) || 1;

        $cell.attr('data-rowspan', rowspan).attr('data-colspan', colspan);
        $cell.attr('rowspan', rowspan).attr('colspan', colspan);

        this.updateCellMeta($cell);
        this.render();
    };

    TableBuilder.prototype.updateCellValue = function($cell) {
        var row = parseInt($cell.attr('data-row'), 10);
        var col = parseInt($cell.attr('data-col'), 10);

        this.state.rows[row][col].value = $cell.text();
    };

    TableBuilder.prototype.updateCellMeta = function($cell) {
        var row = parseInt($cell.attr('data-row'), 10);
        var col = parseInt($cell.attr('data-col'), 10);

        var cell = this.state.rows[row][col];
        cell.tooltip = $cell.attr('data-tooltip') || '';
        cell.rowspan = parseInt($cell.attr('data-rowspan'), 10) || 1;
        cell.colspan = parseInt($cell.attr('data-colspan'), 10) || 1;
    };

    TableBuilder.prototype.updateColumnLabel = function($cell) {
        var col = parseInt($cell.attr('data-col'), 10);
        this.state.columns[col].label = $cell.text();
        this.$columnList.find('.kng-column-label[data-col="' + col + '"]').val($cell.text());
    };

    TableBuilder.prototype.addRow = function() {
        if (window.KNGTableBuilder && !KNGTableBuilder.isPro && this.state.rows.length >= KNGTableBuilder.maxRows) {
            alert('Free limit reached. Upgrade to Pro for more rows.');
            return;
        }

        var row = [];
        for (var i = 0; i < this.state.columns.length; i++) {
            row.push({ value: '', tooltip: '', rowspan: 1, colspan: 1 });
        }
        this.state.rows.push(row);
        this.render();
    };

    TableBuilder.prototype.removeRow = function() {
        if (this.state.rows.length <= 1) {
            return;
        }
        this.state.rows.pop();
        this.render();
    };

    TableBuilder.prototype.addColumn = function() {
        if (window.KNGTableBuilder && !KNGTableBuilder.isPro && this.state.columns.length >= KNGTableBuilder.maxCols) {
            alert('Free limit reached. Upgrade to Pro for more columns.');
            return;
        }

        var index = this.state.columns.length + 1;
        this.state.columns.push({
            label: 'Column ' + index,
            type: 'text',
            sortable: true,
            hide_mobile: false,
            align: 'left'
        });

        this.state.rows.forEach(function(row) {
            row.push({ value: '', tooltip: '', rowspan: 1, colspan: 1 });
        });

        this.render();
    };

    TableBuilder.prototype.removeColumn = function() {
        if (this.state.columns.length <= 1) {
            return;
        }

        this.state.columns.pop();
        this.state.rows.forEach(function(row) {
            row.pop();
        });

        this.render();
    };

    TableBuilder.prototype.applyImport = function(text) {
        if (!text.trim()) {
            return;
        }

        var raw = text.trim();
        if (raw.indexOf('\t') !== -1 && raw.indexOf(',') === -1) {
            raw = raw.replace(/\t/g, ',');
        }

        var rows = csvToArray(raw);
        if (!rows.length) {
            return;
        }

        var header = rows.shift();
        var columns = header.map(function(label, index) {
            return {
                label: label || ('Column ' + (index + 1)),
                type: 'text',
                sortable: true,
                hide_mobile: false,
                align: 'left'
            };
        });

        if (window.KNGTableBuilder && !KNGTableBuilder.isPro && columns.length > KNGTableBuilder.maxCols) {
            alert('CSV exceeds free column limit.');
            return;
        }

        var dataRows = rows.map(function(row) {
            return columns.map(function(_, colIndex) {
                return {
                    value: row[colIndex] || '',
                    tooltip: '',
                    rowspan: 1,
                    colspan: 1
                };
            });
        });

        if (window.KNGTableBuilder && !KNGTableBuilder.isPro && dataRows.length > KNGTableBuilder.maxRows) {
            alert('CSV exceeds free row limit.');
            return;
        }

        this.state.columns = columns;
        this.state.rows = dataRows;
        this.render();
    };

    TableBuilder.prototype.syncHiddenFields = function() {
        var responsive = $.extend({}, this.state.responsive);
        responsive.hide_columns = [];

        this.state.columns.forEach(function(column, index) {
            if (column.hide_mobile) {
                responsive.hide_columns.push(index);
            }
        });

        $('#kng-table-data').val(JSON.stringify({
            columns: this.state.columns,
            rows: this.state.rows,
            schema_version: 1
        }));
        $('#kng-table-config').val(JSON.stringify(this.state.config));
        $('#kng-table-style').val(JSON.stringify(this.state.style));
        $('#kng-table-responsive').val(JSON.stringify(responsive));
    };

    $(document).ready(function() {
        var $editor = $('.kng-table-editor');
        if ($editor.length) {
            new TableBuilder($editor);
        }
    });
})(jQuery);
