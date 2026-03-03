/**
 * Docs & Knowledge Base v2 – Apple Liquid-Glass Frontend Scripts
 *
 * Modules:
 *   1. DocsSearch       — AJAX live search with debounce & keyboard nav
 *   2. TableOfContents  — auto-generated, hierarchical, sticky, scroll-spy
 *   3. ScrollReveal      — IntersectionObserver fade-in animations
 *   4. Reactions         — emoji reactions (happy / neutral / sad)
 *   5. DarkMode          — light / dark / auto from cookie / system
 *   6. SocialShare       — copy link, Twitter, Facebook, LinkedIn
 *   7. CopyAnchor        — heading anchor copy-to-clipboard
 *
 * @package King_Addons
 */

(function () {
    'use strict';

    /* ========================================
       HELPERS
       ======================================== */
    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

    function debounce(fn, ms = 300) {
        let t;
        return (...a) => {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, a), ms);
        };
    }

    function setCookie(n, v, days = 365) {
        const d = new Date();
        d.setTime(d.getTime() + days * 864e5);
        document.cookie = `${n}=${v};expires=${d.toUTCString()};path=/;SameSite=Lax`;
    }

    function getCookie(n) {
        return (document.cookie.match(`(^|;)\\s*${n}=([^;]*)`) || [])[2] || '';
    }

    /* ========================================
       1. DocsSearch
       ======================================== */
    class DocsSearch {
        constructor(container) {
            this.container = container;
            this.input = $('.kng-docs-search-input', container);
            this.results = $('.kng-docs-search-results', container);
            this.loader = $('.kng-docs-search-loader', container);
            this.shortcut = $('.kng-docs-search-shortcut', container);
            if (!this.input || !this.results) return;

            this.activeIdx = -1;
            this._bind();
        }

        _bind() {
            this.input.addEventListener('input', debounce(() => this._search(), 250));
            this.input.addEventListener('keydown', (e) => this._keydown(e));
            this.input.addEventListener('focus', () => {
                if (this.input.value.trim().length >= 2 && this.results.childElementCount) {
                    this.results.style.display = 'block';
                }
            });

            document.addEventListener('click', (e) => {
                if (!this.container.contains(e.target)) this._close();
            });

            /* ⌘K / Ctrl+K shortcut */
            document.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    this.input.focus();
                }
                if (e.key === 'Escape') this._close();
            });
        }

        async _search() {
            const q = this.input.value.trim();
            if (q.length < 2) {
                this._close();
                return;
            }

            this._showLoader(true);

            try {
                const r = await fetch(
                    `${kingDocsKB.restUrl}search?q=${encodeURIComponent(q)}`,
                    { headers: { 'X-WP-Nonce': kingDocsKB.nonce } }
                );
                const data = await r.json();
                this._render(data.results || [], q);
            } catch (err) {
                console.error('[KNG Docs] search error', err);
            } finally {
                this._showLoader(false);
            }
        }

        _render(items, query) {
            this.activeIdx = -1;
            if (!items.length) {
                this.results.innerHTML = `<div class="kng-docs-search-empty">${kingDocsKB.i18n?.noResults || 'No articles found.'}</div>`;
                this.results.style.display = 'block';
                return;
            }

            const re = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            this.results.innerHTML = items.map(i => {
                const title = i.title.replace(re, '<mark>$1</mark>');
                return `<a class="kng-docs-search-result" href="${i.url}">
                    <span class="kng-docs-search-result-title">${title}</span>
                    <span class="kng-docs-search-result-meta">
                        ${i.category ? `<span>${i.category}</span>` : ''}
                        ${i.category && i.reading_time ? `<span class="kng-docs-dot"></span>` : ''}
                        ${i.reading_time ? `<span>${i.reading_time}</span>` : ''}
                    </span>
                </a>`;
            }).join('');

            this.results.style.display = 'block';
        }

        _keydown(e) {
            const items = $$('.kng-docs-search-result', this.results);
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.activeIdx = Math.min(this.activeIdx + 1, items.length - 1);
                this._highlight(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.activeIdx = Math.max(this.activeIdx - 1, 0);
                this._highlight(items);
            } else if (e.key === 'Enter' && this.activeIdx >= 0) {
                e.preventDefault();
                items[this.activeIdx].click();
            }
        }

        _highlight(items) {
            items.forEach((el, i) => el.classList.toggle('is-focused', i === this.activeIdx));
            if (this.activeIdx >= 0) items[this.activeIdx].scrollIntoView({ block: 'nearest' });
        }

        _showLoader(show) {
            if (this.loader) this.loader.style.display = show ? 'block' : 'none';
            if (this.shortcut) this.shortcut.style.display = show ? 'none' : '';
        }

        _close() {
            this.results.style.display = 'none';
            this.activeIdx = -1;
        }
    }

    /* ========================================
       2. TableOfContents — auto-generated
       ======================================== */
    class TableOfContents {
        constructor(contentEl, tocListEl, floatingListEl) {
            this.content = contentEl;
            this.tocList = tocListEl;
            this.floatingList = floatingListEl;
            if (!this.content) return;

            this.headings = $$('h2, h3, h4', this.content);
            if (!this.headings.length) return;

            this._build();
            this._spy();
        }

        _build() {
            const html = this.headings.map((h, i) => {
                if (!h.id) h.id = `kng-heading-${i}`;
                const tag = h.tagName.toLowerCase();
                return `<li class="kng-docs-toc-item kng-docs-toc-${tag}" data-id="${h.id}">
                    <a href="#${h.id}">${h.textContent}</a>
                </li>`;
            }).join('');

            if (this.tocList) this.tocList.innerHTML = html;
            if (this.floatingList) this.floatingList.innerHTML = html;
        }

        _spy() {
            if (!('IntersectionObserver' in window)) return;

            const items = [
                ...(this.tocList ? $$('.kng-docs-toc-item', this.tocList) : []),
                ...(this.floatingList ? $$('.kng-docs-toc-item', this.floatingList) : []),
            ];

            const io = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        const id = e.target.id;
                        items.forEach(li => li.classList.toggle('is-active', li.dataset.id === id));
                    }
                });
            }, { rootMargin: '-80px 0px -70% 0px', threshold: 0 });

            this.headings.forEach(h => io.observe(h));
        }
    }

    /* ========================================
       3. ScrollReveal
       ======================================== */
    class ScrollReveal {
        constructor() {
            if (!('IntersectionObserver' in window)) {
                $$('.kng-docs-reveal, .kng-docs-reveal-stagger').forEach(el => el.classList.add('is-visible'));
                return;
            }

            const io = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        e.target.classList.add('is-visible');
                        io.unobserve(e.target);
                    }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

            $$('.kng-docs-reveal, .kng-docs-reveal-stagger').forEach(el => io.observe(el));
        }
    }

    /* ========================================
       4. Reactions
       ======================================== */
    class Reactions {
        constructor(container) {
            this.el = container;
            if (!this.el) return;

            this.postId = this.el.dataset.postId;
            this.buttons = $$('.kng-docs-reaction-btn', this.el);
            this.thanks = $('.kng-docs-reactions-thanks', this.el);

            const stored = getCookie(`kng_docs_reaction_${this.postId}`);
            if (stored) {
                this._showThanks();
                return;
            }

            this.buttons.forEach(btn => btn.addEventListener('click', () => this._react(btn)));
        }

        async _react(btn) {
            const type = btn.dataset.reaction;
            btn.classList.add('is-selected');

            try {
                await fetch(kingDocsKB.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'king_docs_reaction',
                        post_id: this.postId,
                        reaction: type,
                        nonce: kingDocsKB.reactionNonce,
                    }),
                });
            } catch (err) {
                console.error('[KNG Docs] reaction error', err);
            }

            setCookie(`kng_docs_reaction_${this.postId}`, type, 30);
            this._showThanks();
        }

        _showThanks() {
            this.buttons.forEach(btn => btn.style.display = 'none');
            if (this.thanks) this.thanks.classList.add('is-visible');
        }
    }

    /* ========================================
       5. DarkMode
       ======================================== */
    class DarkMode {
        constructor() {
            const page = $('.kng-docs-page');
            if (!page) return;

            this.page = page;
            this.mode = page.dataset.kngThemeMode || 'auto'; /* light | dark | auto */
            this.toggle = $('.kng-docs-dark-toggle');

            this._apply();

            if (this.toggle) {
                this.toggle.addEventListener('click', () => this._cycle());
            }

            if (this.mode === 'auto') {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => this._apply());
            }
        }

        _apply() {
            if (this.mode === 'auto') {
                const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                this.page.dataset.kngTheme = systemDark ? 'dark' : 'light';
            } else {
                this.page.dataset.kngTheme = this.mode;
            }
        }

        _cycle() {
            const order = ['light', 'dark', 'auto'];
            const idx = order.indexOf(this.mode);
            this.mode = order[(idx + 1) % order.length];
            this.page.dataset.kngThemeMode = this.mode;
            this._apply();
            setCookie('kng_docs_theme', this.mode, 365);
        }
    }

    /* ========================================
       6. SocialShare
       ======================================== */
    class SocialShare {
        constructor() {
            $$('.kng-docs-share-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const type = btn.dataset.share;
                    const url = encodeURIComponent(window.location.href);
                    const title = encodeURIComponent(document.title);

                    switch (type) {
                        case 'copy':
                            navigator.clipboard.writeText(window.location.href).then(() => {
                                this._tooltip(btn, 'Copied!');
                            });
                            break;
                        case 'twitter':
                            window.open(`https://twitter.com/intent/tweet?url=${url}&text=${title}`, '_blank', 'width=600,height=400');
                            break;
                        case 'facebook':
                            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400');
                            break;
                        case 'linkedin':
                            window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank', 'width=600,height=400');
                            break;
                    }
                });
            });
        }

        _tooltip(el, text) {
            let tip = document.querySelector('.kng-docs-tooltip');
            if (!tip) {
                tip = document.createElement('div');
                tip.className = 'kng-docs-tooltip';
                document.body.appendChild(tip);
            }
            tip.textContent = text;
            const rect = el.getBoundingClientRect();
            tip.style.left = `${rect.left + rect.width / 2 - 30}px`;
            tip.style.top = `${rect.top - 36}px`;
            tip.classList.add('is-visible');
            setTimeout(() => tip.classList.remove('is-visible'), 1200);
        }
    }

    /* ========================================
       7. CopyAnchor
       ======================================== */
    class CopyAnchor {
        constructor() {
            const content = $('.kng-docs-article-content');
            if (!content) return;

            $$('h2, h3, h4', content).forEach(h => {
                if (!h.id) return;
                const btn = document.createElement('button');
                btn.className = 'kng-docs-heading-anchor';
                btn.setAttribute('aria-label', 'Copy link');
                btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';
                btn.addEventListener('click', () => {
                    const url = `${window.location.origin}${window.location.pathname}#${h.id}`;
                    navigator.clipboard.writeText(url);
                });

                h.style.position = 'relative';
                h.appendChild(btn);
            });
        }
    }

    /* ========================================
       INIT
       ======================================== */
    document.addEventListener('DOMContentLoaded', () => {
        /* Search */
        const searchEl = $('.kng-docs-search');
        if (searchEl) new DocsSearch(searchEl);

        /* TOC */
        const articleContent = $('.kng-docs-article-content');
        const tocList = $('.kng-docs-toc-list');
        const floatingList = $('.kng-docs-toc-floating-list');
        if (articleContent) new TableOfContents(articleContent, tocList, floatingList);

        /* Scroll reveal */
        new ScrollReveal();

        /* Reactions */
        const reactionsEl = $('.kng-docs-reactions');
        if (reactionsEl) new Reactions(reactionsEl);

        /* Dark mode */
        new DarkMode();

        /* Social share */
        new SocialShare();

        /* Copy anchor */
        new CopyAnchor();
    });
})();
