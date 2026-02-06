/**
 * Site Preloader Frontend Script.
 *
 * Handles preloader initialization, hide strategies, and AJAX navigation support.
 * Premium style inspired smooth interactions.
 *
 * @package King_Addons
 * @since 1.0.0
 */

(function () {
    'use strict';

    /**
     * Main Preloader Controller
     */
    const KngPreloader = {

        /**
         * Configuration from backend
         */
        config: {
            hideStrategy: 'window_load',
            minDisplayTime: 500,
            maxDisplayTime: 10000,
            hideAnimation: 'fade',
            animationDuration: 400,
            triggerType: 'always',
            cookieName: 'kng_preloader_shown',
            enableAjax: false,
            allowSkip: false,
            skipMethod: 'click',
            lockScroll: true,
            cookieDays: 30
        },

        /**
         * State management
         */
        state: {
            isShowing: true,
            startTime: 0,
            hideTimer: null,
            maxTimer: null
        },

        /**
         * DOM elements
         */
        elements: {
            preloader: null,
            overlay: null,
            content: null
        },

        /**
         * Initialize the preloader
         */
        init: function () {
            // Get preloader element
            this.elements.preloader = document.querySelector('.kng-site-preloader');
            
            if (!this.elements.preloader) {
                return;
            }

            // Get child elements
            this.elements.overlay = this.elements.preloader.querySelector('.kng-site-preloader__overlay');
            this.elements.content = this.elements.preloader.querySelector('.kng-site-preloader__content');

            // Parse configuration from data attributes
            this.parseConfig();

            // Record start time
            this.state.startTime = performance.now();

            // Lock scroll if needed
            if (this.config.lockScroll) {
                document.body.classList.add('kng-preloader-no-scroll');
            }

            // Set up skip key listener
            this.setupSkipKey();

            // Set up hide strategy
            this.setupHideStrategy();

            // Set up maximum display time failsafe
            this.setupMaxTimeout();

            // Set up AJAX navigation support (Pro feature)
            if (this.config.enableAjax) {
                this.setupAjaxNavigation();
            }

            // Expose API
            window.KngPreloader = this;
        },

        /**
         * Parse configuration from data attributes
         */
        parseConfig: function () {
            const dataset = this.elements.preloader.dataset;

            if (dataset.hideStrategy) {
                this.config.hideStrategy = dataset.hideStrategy;
            }
            if (dataset.minDisplayTime) {
                this.config.minDisplayTime = parseInt(dataset.minDisplayTime, 10);
            }
            if (dataset.maxDisplayTime) {
                this.config.maxDisplayTime = parseInt(dataset.maxDisplayTime, 10);
            }
            if (dataset.hideAnimation) {
                this.config.hideAnimation = dataset.hideAnimation;
            }
            if (dataset.animationDuration) {
                this.config.animationDuration = parseInt(dataset.animationDuration, 10);
            }
            if (dataset.triggerType) {
                this.config.triggerType = dataset.triggerType;
            }
            if (dataset.cookieName) {
                this.config.cookieName = dataset.cookieName;
            }
            if (dataset.enableAjax) {
                this.config.enableAjax = dataset.enableAjax === 'true';
            }
            if (dataset.allowSkip) {
                this.config.allowSkip = dataset.allowSkip === 'true';
            }
            if (dataset.skipMethod) {
                this.config.skipMethod = dataset.skipMethod;
            }
            if (dataset.lockScroll !== undefined) {
                this.config.lockScroll = dataset.lockScroll === 'true';
            }
        },

        /**
         * Set a cookie with optional expiry days.
         */
        setCookie: function (name, value, days) {
            let expires = '';
            if (typeof days === 'number') {
                const date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }

            const secure = (window.location && window.location.protocol === 'https:') ? '; Secure' : '';
            document.cookie = name + '=' + encodeURIComponent(String(value)) + expires + '; path=/; SameSite=Lax' + secure;
        },

        /**
         * Set up hide strategy based on configuration
         */
        setupHideStrategy: function () {
            switch (this.config.hideStrategy) {
                case 'dom_ready':
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', () => this.requestHide());
                    } else {
                        this.requestHide();
                    }
                    break;

                case 'window_load':
                    if (document.readyState === 'complete') {
                        this.requestHide();
                    } else {
                        window.addEventListener('load', () => this.requestHide());
                    }
                    break;

                case 'timeout':
                    this.state.hideTimer = setTimeout(() => {
                        this.requestHide();
                    }, this.config.minDisplayTime);
                    break;

                case 'custom':
                    // Wait for external call to hide()
                    break;

                default:
                    window.addEventListener('load', () => this.requestHide());
            }
        },

        /**
         * Set up maximum display time failsafe
         */
        setupMaxTimeout: function () {
            this.state.maxTimer = setTimeout(() => {
                if (this.state.isShowing) {
                    console.warn('[KngPreloader] Max display time reached, forcing hide');
                    this.hide();
                }
            }, this.config.maxDisplayTime);
        },

        /**
         * Set up skip functionality based on config
         */
        setupSkipKey: function () {
            if (!this.config.allowSkip) {
                return;
            }

            const skipMethod = this.config.skipMethod || 'click';

            if (skipMethod === 'click') {
                // Skip on click anywhere
                const handleClick = () => {
                    if (this.state.isShowing) {
                        this.hide();
                        document.removeEventListener('click', handleClick);
                    }
                };
                document.addEventListener('click', handleClick);
            } else if (skipMethod === 'escape') {
                // Skip on Escape key
                const handleKeyDown = (e) => {
                    if (e.key === 'Escape' && this.state.isShowing) {
                        this.hide();
                        document.removeEventListener('keydown', handleKeyDown);
                    }
                };
                document.addEventListener('keydown', handleKeyDown);
            }
        },

        /**
         * Request hide with minimum display time check
         */
        requestHide: function () {
            const elapsed = performance.now() - this.state.startTime;
            const remaining = this.config.minDisplayTime - elapsed;

            if (remaining > 0) {
                this.state.hideTimer = setTimeout(() => this.hide(), remaining);
            } else {
                this.hide();
            }
        },

        /**
         * Hide the preloader
         */
        hide: function () {
            if (!this.state.isShowing || !this.elements.preloader) {
                return;
            }

            this.state.isShowing = false;

            // Clear timers
            if (this.state.hideTimer) {
                clearTimeout(this.state.hideTimer);
            }
            if (this.state.maxTimer) {
                clearTimeout(this.state.maxTimer);
            }

            // Set CSS animation duration variable
            this.elements.preloader.style.setProperty('--kng-preloader-transition', this.config.animationDuration + 'ms');

            // Apply hide animation
            const animationClass = this.getHideAnimationClass();
            this.elements.preloader.classList.add('kng-site-preloader--hidden', animationClass);

            // Unlock scroll after animation completes
            setTimeout(() => {
                document.body.classList.remove('kng-preloader-no-scroll');
                this.elements.preloader.style.display = 'none';
                
                // Set cookie based on trigger type
                this.setTriggerCookie();

                // Trigger custom event
                document.dispatchEvent(new CustomEvent('kngPreloaderHidden', {
                    detail: {
                        displayTime: performance.now() - this.state.startTime
                    }
                }));
            }, this.config.animationDuration);
        },

        /**
         * Get hide animation CSS class
         */
        getHideAnimationClass: function () {
            const animations = {
                'fade': 'kng-site-preloader--fade-out',
                'slide_up': 'kng-site-preloader--slide-up',
                'blur': 'kng-site-preloader--blur-out',
                'scale': 'kng-site-preloader--scale-out'
            };

            return animations[this.config.hideAnimation] || animations['fade'];
        },

        /**
         * Show the preloader (for AJAX navigation)
         */
        show: function () {
            if (this.state.isShowing || !this.elements.preloader) {
                return;
            }

            this.state.isShowing = true;
            this.state.startTime = performance.now();

            // Remove hide classes
            this.elements.preloader.classList.remove(
                'kng-site-preloader--hidden',
                'kng-site-preloader--fade-out',
                'kng-site-preloader--slide-up',
                'kng-site-preloader--blur-out',
                'kng-site-preloader--scale-out'
            );

            // Show preloader
            this.elements.preloader.style.display = 'flex';
            this.elements.preloader.classList.add('kng-site-preloader--fade-in');

            // Lock scroll
            document.body.classList.add('kng-preloader-no-scroll');

            // Set up max timeout again
            this.setupMaxTimeout();

            // Trigger custom event
            document.dispatchEvent(new CustomEvent('kngPreloaderShown'));
        },

        /**
         * Set cookie based on trigger type
         */
        setTriggerCookie: function () {
            const triggerType = this.config.triggerType || 'always';
            if (triggerType === 'always') {
                return;
            }

            const cookieName = this.config.cookieName || 'kng_preloader_shown';
            const nowSeconds = Math.floor(Date.now() / 1000);

            switch (triggerType) {
                case 'first_visit':
                    // Persist "forever" (10 years) to represent first visit.
                    this.setCookie(cookieName, nowSeconds, 3650);
                    break;

                case 'once_per_session':
                    // Session cookie (no expiry).
                    this.setCookie(cookieName, '1');
                    break;

                case 'once_per_day':
                    // Store a unix timestamp (seconds). Expiry slightly > 1 day.
                    this.setCookie(cookieName, nowSeconds, 2);
                    break;

                default:
                    // Unknown trigger type: do nothing.
                    break;
            }
        },

        /**
         * Check if preloader should be shown based on cookie
         */
        shouldShow: function () {
            if (this.config.triggerType === 'always') {
                return true;
            }

            const cookieName = (this.config.cookieName || 'kng_preloader_shown') + '=';
            const cookies = document.cookie.split(';');
            for (let i = 0; i < cookies.length; i++) {
                const cookie = cookies[i].trim();
                if (cookie.startsWith(cookieName)) {
                    return false;
                }
            }
            return true;
        },

        /**
         * Set up AJAX navigation support (Pro feature)
         */
        setupAjaxNavigation: function () {
            // Intercept link clicks
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (!link || !this.isInternalLink(link.href)) {
                    return;
                }

                // Check for external link indicators
                if (link.target === '_blank' || link.hasAttribute('download')) {
                    return;
                }

                // Exclude admin links and WP-specific URLs
                const href = link.href;
                if (href.includes('/wp-admin') || 
                    href.includes('/wp-login') || 
                    href.includes('#') ||
                    href.includes('?') && href.includes('action=')) {
                    return;
                }

                // Show preloader for internal navigation
                e.preventDefault();
                this.show();

                // Navigate after short delay
                setTimeout(() => {
                    window.location.href = href;
                }, 200);
            });

            // Handle browser back/forward
            window.addEventListener('popstate', () => {
                this.show();
            });

            // Handle page show event (for bfcache)
            window.addEventListener('pageshow', (e) => {
                if (e.persisted) {
                    this.hide();
                }
            });
        },

        /**
         * Check if URL is internal
         */
        isInternalLink: function (url) {
            try {
                const linkUrl = new URL(url);
                return linkUrl.hostname === window.location.hostname;
            } catch (e) {
                return false;
            }
        },

        /**
         * Update progress (for custom progress bars)
         */
        setProgress: function (percent) {
            const progressBar = this.elements.preloader.querySelector('[data-progress-bar]');
            if (progressBar) {
                progressBar.style.width = Math.min(100, Math.max(0, percent)) + '%';
            }
        },

        /**
         * Update loading text
         */
        setText: function (text) {
            const textElement = this.elements.preloader.querySelector('.kng-site-preloader__text');
            if (textElement) {
                textElement.textContent = text;
            }
        },

        /**
         * Destroy preloader
         */
        destroy: function () {
            if (this.state.hideTimer) {
                clearTimeout(this.state.hideTimer);
            }
            if (this.state.maxTimer) {
                clearTimeout(this.state.maxTimer);
            }
            
            if (this.elements.preloader) {
                this.elements.preloader.remove();
            }

            document.body.classList.remove('kng-preloader-no-scroll');
            this.elements.preloader = null;
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => KngPreloader.init());
    } else {
        KngPreloader.init();
    }

    // Expose globally for external control
    window.KngPreloader = KngPreloader;

})();
