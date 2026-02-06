/**
 * Docs & Knowledge Base - Frontend JavaScript
 * Live search, TOC generation, smooth scroll, analytics
 *
 * @package King_Addons
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        searchDebounce: 300,
        scrollOffset: 80,
        stickyOffset: 100
    };

    // API endpoints
    const API = {
        search: kngDocsKB?.restUrl + 'search' || '/wp-json/king-addons/v1/docs/search',
        nonce: kngDocsKB?.nonce || ''
    };

    /**
     * Debounce helper
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Live Search
     */
    class DocsSearch {
        constructor(container) {
            this.container = container;
            this.input = container.querySelector('.kng-docs-search-input');
            this.results = container.querySelector('.kng-docs-search-results');
            this.loader = container.querySelector('.kng-docs-search-loader');
            
            if (!this.input || !this.results) return;
            
            this.minChars = parseInt(this.input.dataset.minChars) || 2;
            this.categoryId = this.input.dataset.category || '';
            
            this.init();
        }

        init() {
            this.input.addEventListener('input', debounce(() => this.handleSearch(), CONFIG.searchDebounce));
            this.input.addEventListener('focus', () => this.showResults());
            
            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!this.container.contains(e.target)) {
                    this.hideResults();
                }
            });

            // Keyboard navigation
            this.input.addEventListener('keydown', (e) => this.handleKeyboard(e));
        }

        async handleSearch() {
            const query = this.input.value.trim();
            
            if (query.length < this.minChars) {
                this.hideResults();
                return;
            }

            this.showLoader();

            try {
                let url = `${API.search}?s=${encodeURIComponent(query)}&per_page=10`;
                if (this.categoryId) {
                    url += `&category=${this.categoryId}`;
                }

                const response = await fetch(url, {
                    headers: {
                        'X-WP-Nonce': API.nonce
                    }
                });

                const data = await response.json();
                this.renderResults(data);
            } catch (error) {
                console.error('Search error:', error);
                this.renderEmpty();
            }

            this.hideLoader();
        }

        renderResults(results) {
            if (!results || results.length === 0) {
                this.renderEmpty();
                return;
            }

            const html = results.map(item => `
                <a href="${item.url}" class="kng-docs-search-result">
                    <div class="kng-docs-search-result-title">${item.title}</div>
                    ${item.category ? `<div class="kng-docs-search-result-category">${item.category}</div>` : ''}
                </a>
            `).join('');

            this.results.innerHTML = html;
            this.showResults();
        }

        renderEmpty() {
            this.results.innerHTML = `
                <div class="kng-docs-search-empty">
                    ${kngDocsKB?.i18n?.noResults || 'No results found'}
                </div>
            `;
            this.showResults();
        }

        showResults() {
            if (this.input.value.trim().length >= this.minChars) {
                this.results.style.display = 'block';
            }
        }

        hideResults() {
            this.results.style.display = 'none';
        }

        showLoader() {
            if (this.loader) {
                this.loader.style.display = 'flex';
            }
        }

        hideLoader() {
            if (this.loader) {
                this.loader.style.display = 'none';
            }
        }

        handleKeyboard(e) {
            const items = this.results.querySelectorAll('.kng-docs-search-result');
            const activeItem = this.results.querySelector('.kng-docs-search-result:focus');
            const activeIndex = activeItem ? Array.from(items).indexOf(activeItem) : -1;

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    if (activeIndex < items.length - 1) {
                        items[activeIndex + 1].focus();
                    } else if (activeIndex === -1 && items.length > 0) {
                        items[0].focus();
                    }
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    if (activeIndex > 0) {
                        items[activeIndex - 1].focus();
                    } else {
                        this.input.focus();
                    }
                    break;

                case 'Escape':
                    this.hideResults();
                    this.input.blur();
                    break;
            }
        }
    }

    /**
     * Table of Contents Generator
     */
    class TableOfContents {
        constructor(contentEl, tocEl, floatingTocEl = null) {
            this.content = contentEl;
            this.toc = tocEl;
            this.floatingToc = floatingTocEl;
            
            if (!this.content) return;
            
            this.headings = this.content.dataset.tocHeadings || 'h2,h3';
            this.init();
        }

        init() {
            const elements = this.content.querySelectorAll(this.headings);
            
            if (elements.length === 0) {
                if (this.toc) this.toc.style.display = 'none';
                return;
            }

            const tocItems = [];

            elements.forEach((el, index) => {
                const id = `toc-${index}`;
                el.id = id;
                
                const level = el.tagName.toLowerCase();
                const text = el.textContent;

                tocItems.push({
                    id,
                    text,
                    level
                });
            });

            const html = this.generateHTML(tocItems);

            if (this.toc) {
                const list = this.toc.querySelector('.kng-docs-toc-list');
                if (list) {
                    list.innerHTML = html;
                    this.toc.style.display = '';
                }
            }

            if (this.floatingToc) {
                this.floatingToc.innerHTML = html;
            }

            // Initialize scroll spy
            this.initScrollSpy(tocItems);

            // Smooth scroll
            this.initSmoothScroll();
        }

        generateHTML(items) {
            return items.map(item => `
                <li class="kng-docs-toc-item kng-docs-toc-${item.level}">
                    <a href="#${item.id}">${item.text}</a>
                </li>
            `).join('');
        }

        initScrollSpy(items) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const link = document.querySelector(`.kng-docs-toc-item a[href="#${entry.target.id}"]`);
                    if (link) {
                        if (entry.isIntersecting) {
                            // Remove active from all
                            document.querySelectorAll('.kng-docs-toc-item').forEach(item => {
                                item.classList.remove('is-active');
                            });
                            link.parentElement.classList.add('is-active');
                        }
                    }
                });
            }, {
                rootMargin: '-100px 0px -66%',
                threshold: 0
            });

            items.forEach(item => {
                const el = document.getElementById(item.id);
                if (el) observer.observe(el);
            });
        }

        initSmoothScroll() {
            document.querySelectorAll('.kng-docs-toc-list a').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = link.getAttribute('href').slice(1);
                    const target = document.getElementById(targetId);
                    
                    if (target) {
                        const offsetTop = target.getBoundingClientRect().top + window.pageYOffset - CONFIG.scrollOffset;
                        window.scrollTo({
                            top: offsetTop,
                            behavior: 'smooth'
                        });

                        // Update URL without scrolling
                        history.pushState(null, '', link.getAttribute('href'));
                    }
                });
            });
        }
    }

    /**
     * Article Feedback
     */
    class ArticleFeedback {
        constructor(container) {
            this.container = container;
            this.docId = container.dataset.docId;
            this.buttons = container.querySelector('.kng-docs-feedback-buttons');
            this.thanks = container.querySelector('.kng-docs-feedback-thanks');
            this.question = container.querySelector('.kng-docs-feedback-question');
            
            if (!this.buttons) return;
            
            this.init();
        }

        init() {
            const btns = this.container.querySelectorAll('.kng-docs-feedback-btn');
            
            btns.forEach(btn => {
                btn.addEventListener('click', () => this.submitFeedback(btn.dataset.value));
            });
        }

        async submitFeedback(value) {
            // Check if already submitted
            if (localStorage.getItem(`kng_doc_feedback_${this.docId}`)) {
                this.showThanks();
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'king_addons_docs_feedback');
                formData.append('doc_id', this.docId);
                formData.append('feedback', value);
                formData.append('nonce', kngDocsKB?.feedbackNonce || '');

                await fetch(kngDocsKB?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                });

                // Store in localStorage to prevent duplicate submissions
                localStorage.setItem(`kng_doc_feedback_${this.docId}`, value);

                this.showThanks();
            } catch (error) {
                console.error('Feedback error:', error);
            }
        }

        showThanks() {
            if (this.buttons) this.buttons.style.display = 'none';
            if (this.question) this.question.style.display = 'none';
            if (this.thanks) this.thanks.style.display = 'flex';
        }
    }

    /**
     * Sticky Elements
     */
    class StickyElement {
        constructor(element) {
            this.element = element;
            this.isSticky = element.classList.contains('is-sticky');
            
            if (!this.isSticky) return;
            
            this.init();
        }

        init() {
            const inner = this.element.querySelector('.kng-docs-sidebar-inner, .kng-docs-toc-floating-inner');
            
            if (inner) {
                inner.style.position = 'sticky';
                inner.style.top = `${CONFIG.stickyOffset}px`;
            }
        }
    }

    /**
     * Copy Code Button
     */
    class CopyCode {
        constructor() {
            this.init();
        }

        init() {
            const codeBlocks = document.querySelectorAll('.kng-docs-article-content pre');
            
            codeBlocks.forEach(block => {
                const button = document.createElement('button');
                button.className = 'kng-docs-copy-btn';
                button.innerHTML = `
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                        <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                    </svg>
                `;
                button.title = kngDocsKB?.i18n?.copy || 'Copy';

                block.style.position = 'relative';
                block.appendChild(button);

                button.addEventListener('click', () => this.copyToClipboard(block, button));
            });
        }

        async copyToClipboard(block, button) {
            const code = block.querySelector('code') || block;
            const text = code.textContent;

            try {
                await navigator.clipboard.writeText(text);
                button.classList.add('copied');
                button.innerHTML = `
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                `;

                setTimeout(() => {
                    button.classList.remove('copied');
                    button.innerHTML = `
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                            <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                        </svg>
                    `;
                }, 2000);
            } catch (error) {
                console.error('Copy failed:', error);
            }
        }
    }

    /**
     * Reading Progress
     */
    class ReadingProgress {
        constructor(article) {
            this.article = article;
            
            if (!this.article) return;
            
            this.init();
        }

        init() {
            const progressBar = document.createElement('div');
            progressBar.className = 'kng-docs-reading-progress';
            progressBar.innerHTML = '<div class="kng-docs-reading-progress-bar"></div>';
            document.body.appendChild(progressBar);

            const bar = progressBar.querySelector('.kng-docs-reading-progress-bar');

            window.addEventListener('scroll', () => {
                const articleRect = this.article.getBoundingClientRect();
                const articleTop = articleRect.top + window.pageYOffset;
                const articleHeight = this.article.offsetHeight;
                const windowHeight = window.innerHeight;
                const scrollTop = window.pageYOffset;

                const start = articleTop - windowHeight;
                const end = articleTop + articleHeight;
                const progress = (scrollTop - start) / (end - start);

                bar.style.width = `${Math.min(Math.max(progress * 100, 0), 100)}%`;
            });
        }
    }

    /**
     * Initialize
     */
    function init() {
        // Search
        document.querySelectorAll('.kng-docs-search').forEach(el => {
            new DocsSearch(el);
        });

        // Table of Contents
        const content = document.querySelector('.kng-docs-article-content');
        const toc = document.getElementById('kng-docs-toc');
        const floatingToc = document.querySelector('.kng-docs-toc-floating-list');
        
        if (content) {
            new TableOfContents(content, toc, floatingToc);
        }

        // Feedback
        document.querySelectorAll('.kng-docs-feedback').forEach(el => {
            new ArticleFeedback(el);
        });

        // Sticky elements
        document.querySelectorAll('.kng-docs-sidebar, .kng-docs-toc-floating').forEach(el => {
            new StickyElement(el);
        });

        // Copy code buttons
        if (document.querySelector('.kng-docs-article-content pre')) {
            new CopyCode();
        }

        // Reading progress (optional)
        const article = document.querySelector('.kng-docs-article');
        if (article && kngDocsKB?.showReadingProgress) {
            new ReadingProgress(article);
        }
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
