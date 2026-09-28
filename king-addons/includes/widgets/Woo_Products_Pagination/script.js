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

    const hrefFor = (pageArg, page) => {
        const url = new URL(window.location.href);
        if (!pageArg) {
            return url.pathname + url.search;
        }
        if (page <= 1) {
            url.searchParams.delete(pageArg);
        } else {
            url.searchParams.set(pageArg, String(page));
        }
        return url.pathname + url.search;
    };

    const buildLinks = (page, maxPages, pageArg) => {
        const parts = [];
        if (page > 1) {
            parts.push('<a class="prev page-numbers" href="' + hrefFor(pageArg, page - 1) + '">&laquo;</a>');
        }
        for (let i = 1; i <= maxPages; i += 1) {
            if (i === page) {
                parts.push('<span aria-current="page" class="page-numbers current">' + i + '</span>');
            } else {
                parts.push('<a class="page-numbers" href="' + hrefFor(pageArg, i) + '">' + i + '</a>');
            }
        }
        if (page < maxPages) {
            parts.push('<a class="next page-numbers" href="' + hrefFor(pageArg, page + 1) + '">&raquo;</a>');
        }
        return parts.join('');
    };

    const applyPage = (detail) => {
        const page = Math.max(1, parseInt(detail.page, 10) || 1);
        const maxPages = Math.max(1, parseInt(detail.maxPages, 10) || 1);

        document.querySelectorAll('.ka-woo-pagination').forEach((el) => {
            if (!matchesTarget(el, detail)) {
                return;
            }
            const pageArg = el.dataset.pageArg || detail.pageArg || '';
            el.innerHTML = buildLinks(page, maxPages, pageArg);
        });
    };

    document.addEventListener('kingaddons:grid:page', (event) => {
        applyPage(event.detail || {});
    });
})();
