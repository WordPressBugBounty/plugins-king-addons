(function($) {
    'use strict';

    function parseConfig(raw) {
        if (!raw) {
            return {};
        }
        try {
            return JSON.parse(raw);
        } catch (e) {
            return {};
        }
    }

    function getCellValue($row, index, type) {
        var $cell = $row.find('td').eq(index);
        var value = $cell.text().trim();

        if (type === 'number' || type === 'currency' || type === 'percent') {
            var normalized = value.replace(/[^0-9.+-]/g, '');
            var number = parseFloat(normalized);
            return isNaN(number) ? 0 : number;
        }

        return value.toLowerCase();
    }

    function TableFrontend($container) {
        this.$container = $container;
        this.config = parseConfig($container.attr('data-config'));
        this.$table = $container.find('table');
        this.$tbody = this.$table.find('tbody');
        this.$rows = this.$tbody.find('tr');
        this.rows = this.$rows.toArray();
        this.filtered = this.rows.slice();
        this.currentPage = 1;
        this.rowsPerPage = parseInt(this.config.rowsPerPage || 10, 10);
        this.$pagination = $container.find('.kng-table-pagination');

        this.bindEvents();
        this.applyPagination();
    }

    TableFrontend.prototype.bindEvents = function() {
        var self = this;

        if (this.config.search) {
            this.$container.find('.kng-table-search input').on('input', function() {
                self.applySearch($(this).val());
            });
        }

        if (this.config.sorting) {
            this.$table.find('th').on('click', function() {
                var $th = $(this);
                var index = $th.index();
                var current = $th.attr('data-sort') || 'none';
                var next = current === 'asc' ? 'desc' : 'asc';

                self.$table.find('th').attr('data-sort', '');
                $th.attr('data-sort', next);

                self.sortByColumn(index, next);
            });
        }
    };

    TableFrontend.prototype.applySearch = function(query) {
        var self = this;
        var keyword = (query || '').toLowerCase();

        this.filtered = this.rows.filter(function(row) {
            var text = $(row).text().toLowerCase();
            return text.indexOf(keyword) !== -1;
        });

        this.currentPage = 1;
        this.renderRows();
        this.renderPagination();
    };

    TableFrontend.prototype.sortByColumn = function(index, direction) {
        var type = this.$table.find('th').eq(index).attr('data-type') || 'text';
        var self = this;

        this.filtered.sort(function(a, b) {
            var valueA = getCellValue($(a), index, type);
            var valueB = getCellValue($(b), index, type);

            if (valueA < valueB) {
                return direction === 'asc' ? -1 : 1;
            }
            if (valueA > valueB) {
                return direction === 'asc' ? 1 : -1;
            }
            return 0;
        });

        this.filtered.forEach(function(row) {
            self.$tbody.append(row);
        });

        this.currentPage = 1;
        this.renderRows();
        this.renderPagination();
    };

    TableFrontend.prototype.applyPagination = function() {
        if (!this.config.pagination) {
            this.$pagination.hide();
            this.filtered = this.rows.slice();
            this.renderRows();
            return;
        }

        this.renderPagination();
        this.renderRows();
    };

    TableFrontend.prototype.renderRows = function() {
        var self = this;
        var start = 0;
        var end = this.filtered.length;

        if (this.config.pagination) {
            start = (this.currentPage - 1) * this.rowsPerPage;
            end = start + this.rowsPerPage;
        }

        this.$rows.hide();

        this.filtered.slice(start, end).forEach(function(row) {
            $(row).show();
        });
    };

    TableFrontend.prototype.renderPagination = function() {
        var self = this;

        if (!this.config.pagination) {
            return;
        }

        var totalPages = Math.ceil(this.filtered.length / this.rowsPerPage) || 1;
        this.$pagination.empty();

        for (var i = 1; i <= totalPages; i++) {
            var $btn = $('<button type="button"></button>');
            $btn.text(i);
            if (i === this.currentPage) {
                $btn.addClass('active');
            }
            (function(page) {
                $btn.on('click', function() {
                    self.currentPage = page;
                    self.renderRows();
                    self.renderPagination();
                });
            })(i);
            this.$pagination.append($btn);
        }
    };

    $(document).ready(function() {
        $('.kng-table-builder').each(function() {
            new TableFrontend($(this));
        });
    });
})(jQuery);
