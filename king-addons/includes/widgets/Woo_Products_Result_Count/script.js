(() => {
    'use strict';

    const matchesTarget = (el, detail) => {
        const ownArg = el.dataset.pageArg || '';
        if (ownArg) {
            return ownArg === (detail.pageArg || '');
        }
        const own = el.dataset.queryId || '';
        if (!detail.queryId) {
            return true;
        }
        return !own || own === detail.queryId;
    };

    const formatCount = (format, first, last, total) => {
        const template = format || 'Showing {first}–{last} of {total} results';
        return template
            .replace(/\{first\}/g, String(first))
            .replace(/\{from\}/g, String(first))
            .replace(/\{last\}/g, String(last))
            .replace(/\{to\}/g, String(last))
            .replace(/\{total\}/g, String(total));
    };

    const applyPage = (detail) => {
        const total = Math.max(0, parseInt(detail.foundPosts, 10) || 0);
        const perPage = Math.max(1, parseInt(detail.perPage, 10) || 1);
        const page = Math.max(1, parseInt(detail.page, 10) || 1);
        const first = total > 0 ? ((page - 1) * perPage) + 1 : 0;
        const last = total > 0 ? Math.min(total, page * perPage) : 0;

        document.querySelectorAll('.ka-woo-result-count').forEach((el) => {
            if (!matchesTarget(el, detail)) {
                return;
            }
            el.textContent = formatCount(el.dataset.format || '', first, last, total);
        });
    };

    document.addEventListener('kingaddons:grid:page', (event) => {
        applyPage(event.detail || {});
    });
})();
