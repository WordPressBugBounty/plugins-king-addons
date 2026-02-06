/**
 * Fomo Notifications - Frontend JavaScript
 * Lightweight notification display engine
 *
 * @package King_Addons
 */

(function() {
    'use strict';

    // Bail if already initialized
    if (window.KngFomo) return;

    const KngFomo = {
        queue: [],
        current: null,
        container: null,
        settings: {},
        sessionViews: 0,
        init: function() {
            if (!window.kngFomoData || !window.kngFomoData.notifications) return;

            this.settings = window.kngFomoData.settings || {};
            this.sessionViews = this.getSessionViews();

            // Check session limit
            if (this.settings.sessionLimit && this.sessionViews >= this.settings.sessionLimit) {
                return;
            }

            // Create container
            this.createContainer();

            // Filter and prepare notifications
            this.prepareQueue();

            // Start display cycle
            if (this.queue.length > 0) {
                this.startCycle();
            }
        },

        createContainer: function() {
            const position = this.settings.position || 'bottom-left';
            
            // Check for bar notifications
            const barNotifs = window.kngFomoData.notifications.filter(n => n.type === 'notification_bar');
            const popupNotifs = window.kngFomoData.notifications.filter(n => n.type !== 'notification_bar');
            
            // Create popup container
            if (popupNotifs.length > 0) {
                this.container = document.createElement('div');
                this.container.className = 'kng-fomo-notification-wrap kng-fomo-notification-wrap--' + position;
                document.body.appendChild(this.container);
            }
        },

        prepareQueue: function() {
            const notifications = window.kngFomoData.notifications;
            const now = Date.now();

            notifications.forEach(notif => {
                // Check if already dismissed
                if (this.isDismissed(notif.id)) return;

                // Check device
                if (!this.checkDevice(notif.device)) return;

                // Check page rules
                if (!this.checkPageRules(notif.page_rules)) return;

                // Check date range
                if (notif.date_start && new Date(notif.date_start) > now) return;
                if (notif.date_end && new Date(notif.date_end) < now) return;

                // Add to queue
                if (notif.type === 'notification_bar') {
                    // Show bars immediately
                    this.showBar(notif);
                } else {
                    this.queue.push(notif);
                }
            });

            // Shuffle or sort queue based on settings
            if (this.settings.randomOrder) {
                this.shuffleQueue();
            }
        },

        checkDevice: function(device) {
            if (!device || device === 'all') return true;

            const width = window.innerWidth;
            const isMobile = width < 768;
            const isTablet = width >= 768 && width < 1024;
            const isDesktop = width >= 1024;

            switch (device) {
                case 'desktop': return isDesktop;
                case 'tablet': return isTablet;
                case 'mobile': return isMobile;
                case 'desktop_tablet': return isDesktop || isTablet;
                case 'mobile_tablet': return isMobile || isTablet;
                default: return true;
            }
        },

        checkPageRules: function(rules) {
            if (!rules || !rules.type || rules.type === 'all') return true;

            const currentUrl = window.location.href;
            const currentPath = window.location.pathname;
            const pages = rules.pages || [];

            switch (rules.type) {
                case 'include':
                    return pages.some(page => this.matchPage(page, currentUrl, currentPath));
                case 'exclude':
                    return !pages.some(page => this.matchPage(page, currentUrl, currentPath));
                default:
                    return true;
            }
        },

        matchPage: function(page, url, path) {
            // Simple matching - can be enhanced
            if (page.indexOf('*') > -1) {
                const regex = new RegExp('^' + page.replace(/\*/g, '.*') + '$');
                return regex.test(path);
            }
            return url.indexOf(page) > -1 || path === page;
        },

        shuffleQueue: function() {
            for (let i = this.queue.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [this.queue[i], this.queue[j]] = [this.queue[j], this.queue[i]];
            }
        },

        startCycle: function() {
            const initialDelay = parseInt(this.settings.initialDelay) || 3000;
            
            setTimeout(() => {
                this.showNext();
            }, initialDelay);
        },

        showNext: function() {
            if (this.queue.length === 0) {
                // Check if loop
                if (this.settings.loop) {
                    this.prepareQueue();
                }
                return;
            }

            // Check session limit
            if (this.settings.sessionLimit && this.sessionViews >= this.settings.sessionLimit) {
                return;
            }

            const notif = this.queue.shift();
            this.showNotification(notif);
        },

        showNotification: function(notif) {
            this.current = notif;

            // Build HTML
            const el = this.buildNotificationHTML(notif);
            this.container.appendChild(el);

            // Track view
            this.trackEvent(notif.id, 'view');
            this.incrementSessionViews();

            // Play sound if enabled
            if (notif.sound) {
                this.playSound(notif.sound);
            }

            // Show with animation
            requestAnimationFrame(() => {
                el.classList.add('is-visible');
            });

            // Auto-hide
            const displayTime = parseInt(notif.display_time) || 5000;
            setTimeout(() => {
                this.hideNotification(el, notif);
            }, displayTime);
        },

        buildNotificationHTML: function(notif) {
            const el = document.createElement('div');
            const animation = notif.animation || 'slide';
            const hasClick = notif.click_url || notif.cta_url;

            el.className = 'kng-fomo-notification kng-fomo-notification--shadow kng-fomo-notification--' + animation;
            if (hasClick) {
                el.classList.add('kng-fomo-notification--clickable');
            }

            // Apply custom colors
            if (notif.bg_color) el.style.setProperty('--kng-fomo-bg', notif.bg_color);
            if (notif.text_color) el.style.setProperty('--kng-fomo-text', notif.text_color);
            if (notif.accent_color) el.style.setProperty('--kng-fomo-accent', notif.accent_color);

            // Build inner HTML
            let html = '';

            // Image
            if (notif.image) {
                const imgClass = notif.image_style === 'avatar' ? 'kng-fomo-notification__image--avatar' : '';
                html += '<div class="kng-fomo-notification__image ' + imgClass + '"><img src="' + this.escapeHtml(notif.image) + '" alt="" loading="lazy"></div>';
            }

            // Content
            html += '<div class="kng-fomo-notification__content">';
            
            if (notif.title) {
                html += '<p class="kng-fomo-notification__title">' + this.escapeHtml(notif.title) + '</p>';
            }
            
            if (notif.message) {
                html += '<p class="kng-fomo-notification__message">' + this.escapeHtml(notif.message) + '</p>';
            }
            
            if (notif.time_text) {
                html += '<div class="kng-fomo-notification__time">';
                html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';
                html += '<span>' + this.escapeHtml(notif.time_text) + '</span>';
                html += '</div>';
            }

            if (notif.cta_text && notif.cta_url) {
                html += '<a href="' + this.escapeHtml(notif.cta_url) + '" class="kng-fomo-notification__cta" data-notif-id="' + notif.id + '">';
                html += this.escapeHtml(notif.cta_text);
                html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
                html += '</a>';
            }

            html += '</div>';

            // Close button
            html += '<button type="button" class="kng-fomo-notification__close" aria-label="Close">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>';
            html += '</button>';

            el.innerHTML = html;

            // Event listeners
            const closeBtn = el.querySelector('.kng-fomo-notification__close');
            closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.dismiss(notif.id);
                this.hideNotification(el, notif);
            });

            // Click handler for whole notification or CTA
            if (notif.click_url) {
                el.addEventListener('click', (e) => {
                    if (e.target.closest('.kng-fomo-notification__close')) return;
                    this.trackEvent(notif.id, 'click');
                    window.open(notif.click_url, notif.click_target || '_self');
                });
            }

            // CTA click
            const ctaBtn = el.querySelector('.kng-fomo-notification__cta');
            if (ctaBtn) {
                ctaBtn.addEventListener('click', (e) => {
                    this.trackEvent(notif.id, 'click');
                });
            }

            return el;
        },

        hideNotification: function(el, notif) {
            el.classList.remove('is-visible');
            
            setTimeout(() => {
                el.remove();
                this.current = null;

                // Show next
                const betweenDelay = parseInt(this.settings.betweenDelay) || 3000;
                setTimeout(() => {
                    this.showNext();
                }, betweenDelay);
            }, 400);
        },

        showBar: function(notif) {
            // Check if already dismissed
            if (this.isDismissed(notif.id)) return;

            const position = notif.bar_position || 'top';
            
            const el = document.createElement('div');
            el.className = 'kng-fomo-bar kng-fomo-bar--' + position;
            el.id = 'kng-fomo-bar-' + notif.id;

            // Apply custom colors
            if (notif.bg_color) el.style.setProperty('--kng-fomo-bg', notif.bg_color);
            if (notif.text_color) el.style.setProperty('--kng-fomo-text', notif.text_color);

            let html = '';
            
            if (notif.message) {
                html += '<p class="kng-fomo-bar__message">' + this.escapeHtml(notif.message) + '</p>';
            }
            
            if (notif.cta_text && notif.cta_url) {
                html += '<a href="' + this.escapeHtml(notif.cta_url) + '" class="kng-fomo-bar__cta">' + this.escapeHtml(notif.cta_text) + '</a>';
            }

            html += '<button type="button" class="kng-fomo-bar__close" aria-label="Close">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>';
            html += '</button>';

            el.innerHTML = html;

            document.body.appendChild(el);

            // Track view
            this.trackEvent(notif.id, 'view');

            // Show with animation
            requestAnimationFrame(() => {
                el.classList.add('is-visible');
            });

            // Close button
            const closeBtn = el.querySelector('.kng-fomo-bar__close');
            closeBtn.addEventListener('click', () => {
                this.dismiss(notif.id);
                el.classList.remove('is-visible');
                setTimeout(() => el.remove(), 400);
            });

            // CTA click tracking
            const ctaBtn = el.querySelector('.kng-fomo-bar__cta');
            if (ctaBtn) {
                ctaBtn.addEventListener('click', () => {
                    this.trackEvent(notif.id, 'click');
                });
            }
        },

        trackEvent: function(notifId, eventType) {
            if (!window.kngFomoData.ajaxUrl) return;

            const formData = new FormData();
            formData.append('action', 'kng_fomo_track_event');
            formData.append('notification_id', notifId);
            formData.append('event_type', eventType);
            formData.append('nonce', window.kngFomoData.nonce);

            fetch(window.kngFomoData.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            }).catch(() => {});
        },

        playSound: function(soundType) {
            const volume = parseFloat(this.settings.soundVolume) || 0.5;
            
            // Create audio context
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = ctx.createOscillator();
                const gainNode = ctx.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(ctx.destination);

                gainNode.gain.value = volume * 0.3;
                
                switch (soundType) {
                    case 'pop':
                        oscillator.frequency.value = 600;
                        oscillator.type = 'sine';
                        break;
                    case 'ding':
                        oscillator.frequency.value = 800;
                        oscillator.type = 'triangle';
                        break;
                    case 'chime':
                        oscillator.frequency.value = 523;
                        oscillator.type = 'sine';
                        break;
                    default:
                        oscillator.frequency.value = 600;
                        oscillator.type = 'sine';
                }

                gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);

                oscillator.start();
                oscillator.stop(ctx.currentTime + 0.2);
            } catch (e) {
                // Audio not supported
            }
        },

        dismiss: function(notifId) {
            const dismissed = this.getDismissed();
            dismissed.push(notifId);
            
            try {
                sessionStorage.setItem('kng_fomo_dismissed', JSON.stringify(dismissed));
            } catch (e) {}
        },

        isDismissed: function(notifId) {
            const dismissed = this.getDismissed();
            return dismissed.indexOf(notifId) > -1;
        },

        getDismissed: function() {
            try {
                return JSON.parse(sessionStorage.getItem('kng_fomo_dismissed') || '[]');
            } catch (e) {
                return [];
            }
        },

        getSessionViews: function() {
            try {
                return parseInt(sessionStorage.getItem('kng_fomo_views') || '0');
            } catch (e) {
                return 0;
            }
        },

        incrementSessionViews: function() {
            this.sessionViews++;
            try {
                sessionStorage.setItem('kng_fomo_views', this.sessionViews.toString());
            } catch (e) {}
        },

        escapeHtml: function(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    };

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => KngFomo.init());
    } else {
        KngFomo.init();
    }

    // Export
    window.KngFomo = KngFomo;

})();
