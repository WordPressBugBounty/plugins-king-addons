(function ($) {
    'use strict';
    const loaded = new Map();
    const appendInline = (node) => {
        if (!node || (node.id && document.getElementById(node.id))) { return; }
        const script = document.createElement('script');
        if (node.id) { script.id = node.id; }
        script.textContent = node.textContent;
        document.body.appendChild(script);
    };
    const assets = async (source) => {
        source.querySelectorAll('link[rel="stylesheet"]').forEach(function (node) {
            if (!Array.from(document.querySelectorAll('link[rel="stylesheet"]')).some(existing => existing.href === node.href)) { document.head.appendChild(node.cloneNode(true)); }
        });
        for (const node of source.querySelectorAll('script[src]')) {
            const src = node.src;
            if (Array.from(document.scripts).some(existing => existing.src === src) || loaded.has(src)) { if (loaded.has(src)) { await loaded.get(src); } continue; }
            const base = node.id.replace(/-js$/, '');
            if (base) { appendInline(source.getElementById(base + '-js-extra')); appendInline(source.getElementById(base + '-js-before')); }
            const promise = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src; script.async = false;
                if (node.id) { script.id = node.id; }
                script.onload = resolve;
                script.onerror = () => { loaded.delete(src); script.remove(); reject(new Error('Unable to load content assets.')); };
                document.body.appendChild(script);
            });
            loaded.set(src, promise); await promise;
            if (base) { appendInline(source.getElementById(base + '-js-after')); }
        }
    };
    window.kingDisplayInline = {
        refresh: async function ($placeholder, postId, elementId) {
            const position = {x: window.scrollX, y: window.scrollY};
            const html = await $.ajax({url: window.location.href.split('#')[0], type: 'GET', cache: false});
            const source = new DOMParser().parseFromString(html, 'text/html');
            const target = Array.from(source.querySelectorAll('[data-elementor-id] .elementor-element[data-id]')).find(node => node.dataset.id === String(elementId) && node.closest('[data-elementor-id]').dataset.elementorId === String(postId));
            if (!target || target.classList.contains('king-addons-protected-hidden')) { throw new Error('Content is not available yet. Please reload the page.'); }
            await assets(source);
            const $content = $(document.importNode(target, true));
            const $old = $placeholder.next('.king-addons-protected-hidden');
            if ($old.attr('data-id') === String(elementId)) { $old.remove(); }
            $placeholder.replaceWith($content);
            if (window.elementorFrontend && elementorFrontend.elementsHandler) {
                $content.find('.elementor-element').addBack('.elementor-element').each(function () { elementorFrontend.elementsHandler.runReadyTrigger($(this)); });
            }
            window.dispatchEvent(new Event('resize'));
            window.requestAnimationFrame(() => window.scrollTo(position.x, position.y));
        }
    };
}(jQuery));
