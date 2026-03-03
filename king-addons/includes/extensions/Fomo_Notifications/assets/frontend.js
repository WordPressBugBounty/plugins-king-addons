/**
 * Fomo Notifications - Frontend Engine
 *
 * Full notification display engine with entry cycling, notification bar,
 * FOMO popups, progress bars, and analytics tracking.
 *
 * Each notification post can have multiple entries (items).
 * Entries cycle through with configurable timing (like NotificationX).
 * Notification bars are persistent (no cycling).
 * Popup notifications cycle through entries with show/hide animations.
 *
 * @package King_Addons
 */

(function () {
    'use strict';

    if (window.KngFomo) return;

    /* =============================================
       Utility helpers
       ============================================= */

    function esc(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function timeAgo(timestamp) {
        if (!timestamp) return '';
        var seconds = Math.floor((Date.now() / 1000) - timestamp);
        if (seconds < 60) return 'just now';
        var minutes = Math.floor(seconds / 60);
        if (minutes < 60) return minutes + (minutes === 1 ? ' min' : ' mins') + ' ago';
        var hours = Math.floor(minutes / 60);
        if (hours < 24) return hours + (hours === 1 ? ' hour' : ' hours') + ' ago';
        var days = Math.floor(hours / 24);
        if (days < 7) return days + (days === 1 ? ' day' : ' days') + ' ago';
        var weeks = Math.floor(days / 7);
        return weeks + (weeks === 1 ? ' week' : ' weeks') + ' ago';
    }

    /**
     * Process template string, replacing {{placeholders}} with item data.
     * Returns empty string if template is empty/undefined.
     */
    function processTemplate(template, item) {
        if (!template) return '';
        return template.replace(/\{\{(\w+)\}\}/g, function (match, key) {
            if (key === 'name' || key === 'username') return item.username || item.name || '';
            if (key === 'product') return item.product || item.post_title || '';
            if (key === 'location') return item.location || '';
            if (key === 'time' || key === 'time_ago') return item.time_ago || (item.time ? timeAgo(item.time) : '');
            if (key === 'email') return item.email || '';
            if (key === 'content') return item.content || '';
            if (key === 'url' || key === 'link') return item.product_url || item.post_url || item.url || '';
            return item[key] || '';
        });
    }

    /* =============================================
       Main engine
       ============================================= */

    var KngFomo = {
        /** All notification configs keyed by id */
        notifications: {},
        /** Global settings */
        settings: {},
        /** Popup containers keyed by position */
        containers: {},
        /** Active cycling timers keyed by notif id */
        timers: {},
        /** Session view count */
        sessionViews: 0,
        /** Initialized flag */
        ready: false,

        /* ----- Bootstrap ----- */

        init: function () {
            if (this.ready) return;
            if (!window.kngFomoData || !window.kngFomoData.notifications) return;

            this.ready = true;
            this.settings = window.kngFomoData.settings || {};
            this.sessionViews = this.getSessionViews();

            var notifs = window.kngFomoData.notifications;
            if (!notifs.length) return;

            var bars = [];
            var popups = [];

            for (var i = 0; i < notifs.length; i++) {
                var n = notifs[i];
                if (this.isDismissed(n.id)) continue;
                if (!this.isDeviceAllowed(n)) continue;
                if (!this.isPageAllowed(n)) continue;
                this.notifications[n.id] = n;

                if (n.type === 'notification_bar') {
                    bars.push(n);
                } else {
                    popups.push(n);
                }
            }

            // Bars: render immediately (static, no cycling)
            for (var b = 0; b < bars.length; b++) {
                this.renderBar(bars[b]);
            }

            // Popups: start independent cycling for each
            for (var p = 0; p < popups.length; p++) {
                this.startPopupCycle(popups[p]);
            }
        },

        getCurrentDevice: function () {
            var width = window.innerWidth || document.documentElement.clientWidth || 1024;
            if (width < 768) return 'mobile';
            if (width < 1024) return 'tablet';
            return 'desktop';
        },

        isDeviceAllowed: function (notif) {
            var currentDevice = this.getCurrentDevice();

            // New format: display.devices = ['desktop', 'tablet', 'mobile']
            var devices = notif.display && Array.isArray(notif.display.devices) ? notif.display.devices : null;
            if (devices && devices.length) {
                return devices.indexOf(currentDevice) !== -1;
            }

            // Legacy format: customize.visibility = { desktop: true, tablet: true, mobile: true }
            var visibility = notif.customize && notif.customize.visibility ? notif.customize.visibility : null;
            if (visibility && typeof visibility === 'object') {
                return visibility[currentDevice] !== false;
            }

            return true;
        },

        isPageAllowed: function (notif) {
            var display = notif.display || {};

            // Legacy shape from old settings
            if (display.show_on === 'include' && Array.isArray(display.include_pages) && display.include_pages.length) {
                return this.matchesIdRule(display.include_pages);
            }
            if (display.show_on === 'exclude' && Array.isArray(display.exclude_pages) && display.exclude_pages.length) {
                return !this.matchesIdRule(display.exclude_pages);
            }

            // Wizard shape
            var pagesMode = display.pages || 'all';
            if (pagesMode === 'all') return true;

            var rules = Array.isArray(display.page_rules) ? display.page_rules : [];
            if (!rules.length) return true;

            var includeMatched = false;
            var hasIncludeRules = false;

            for (var i = 0; i < rules.length; i++) {
                var rule = rules[i] || {};
                var type = rule.type || 'include';
                var matched = this.matchesPageRule(rule);

                if (type === 'exclude' && matched) {
                    return false;
                }

                if (type === 'include') {
                    hasIncludeRules = true;
                    if (matched) includeMatched = true;
                }
            }

            return hasIncludeRules ? includeMatched : true;
        },

        matchesIdRule: function (ids) {
            if (!Array.isArray(ids) || !ids.length) return false;
            var bodyClass = document.body && document.body.className ? document.body.className : '';
            for (var i = 0; i < ids.length; i++) {
                var id = String(ids[i]);
                if (bodyClass.indexOf('page-id-' + id) !== -1 || bodyClass.indexOf('postid-' + id) !== -1) {
                    return true;
                }
            }
            return false;
        },

        matchesPageRule: function (rule) {
            var condition = rule.condition || 'url_contains';
            var rawValue = rule.value === undefined || rule.value === null ? '' : String(rule.value).trim();
            if (!rawValue) return false;

            var currentPath = (window.location.pathname || '').toLowerCase();
            var currentUrl = (window.location.href || '').toLowerCase();
            var value = rawValue.toLowerCase();

            if (condition === 'url_contains') {
                return currentUrl.indexOf(value) !== -1;
            }

            if (condition === 'url_is') {
                if (currentUrl === value) return true;
                // Also support path-only values in UI
                var normalized = value.charAt(0) === '/' ? value : ('/' + value);
                return currentPath === normalized;
            }

            if (condition === 'page' || condition === 'post') {
                var bodyClass = document.body && document.body.className ? document.body.className : '';
                return bodyClass.indexOf('page-id-' + rawValue) !== -1 || bodyClass.indexOf('postid-' + rawValue) !== -1;
            }

            return false;
        },

        /* =============================================
           NOTIFICATION BAR
           ============================================= */

        renderBar: function (notif) {
            var position = notif.bar_position || 'top';
            var el = document.createElement('div');
            el.className = 'kng-fomo-bar kng-fomo-bar--' + position;
            el.id = 'kng-fomo-bar-' + notif.id;
            el.setAttribute('data-notif-id', notif.id);

            if (notif.bg_color) el.style.setProperty('--kng-fomo-bg', notif.bg_color);
            if (notif.text_color) el.style.setProperty('--kng-fomo-text', notif.text_color);
            if (notif.accent_color) el.style.setProperty('--kng-fomo-accent', notif.accent_color);

            var html = '';

            if (notif.message) {
                html += '<p class="kng-fomo-bar__message">' + esc(notif.message) + '</p>';
            } else if (notif.title) {
                html += '<p class="kng-fomo-bar__message">' + esc(notif.title) + '</p>';
            }

            if (notif.cta_text && notif.cta_url) {
                html += '<a href="' + esc(notif.cta_url) + '" class="kng-fomo-bar__cta" target="_blank" rel="noopener">';
                html += esc(notif.cta_text);
                html += '</a>';
            }

            html += '<button type="button" class="kng-fomo-bar__close" aria-label="Close">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>';
            html += '</button>';

            el.innerHTML = html;

            if (position === 'top') {
                document.body.insertBefore(el, document.body.firstChild);
            } else {
                document.body.appendChild(el);
            }

            this.trackEvent(notif.id, 'view');

            var self = this;
            requestAnimationFrame(function () {
                el.classList.add('is-visible');
                self.adjustBodyPadding(position, el.offsetHeight);
            });

            el.querySelector('.kng-fomo-bar__close').addEventListener('click', function () {
                self.dismiss(notif.id);
                el.classList.remove('is-visible');
                self.adjustBodyPadding(position, 0);
                setTimeout(function () { el.remove(); }, 400);
            });

            var cta = el.querySelector('.kng-fomo-bar__cta');
            if (cta) {
                cta.addEventListener('click', function () {
                    self.trackEvent(notif.id, 'click');
                });
            }

            // Auto-hide support
            var cust = notif.customize || {};
            if (cust.auto_hide && cust.hide_after > 0) {
                setTimeout(function () {
                    el.classList.remove('is-visible');
                    self.adjustBodyPadding(position, 0);
                    setTimeout(function () { el.remove(); }, 400);
                }, cust.hide_after * 1000);
            }
        },

        adjustBodyPadding: function (position, height) {
            if (position === 'top') {
                document.body.style.paddingTop = height ? (height + 'px') : '';
                document.body.classList.toggle('has-kng-fomo-bar-top', height > 0);
            } else {
                document.body.style.paddingBottom = height ? (height + 'px') : '';
                document.body.classList.toggle('has-kng-fomo-bar-bottom', height > 0);
            }
        },

        /* =============================================
           POPUP NOTIFICATIONS — entry cycling engine
           ============================================= */

        startPopupCycle: function (notif) {
            var self = this;
            var disp = notif.display || {};

            // Build entries and optionally randomize
            var entries = this.buildEntries(notif);
            if (!entries.length) return;

            if (disp.random) {
                // Fisher-Yates shuffle
                for (var si = entries.length - 1; si > 0; si--) {
                    var ri = Math.floor(Math.random() * (si + 1));
                    var tmp = entries[si];
                    entries[si] = entries[ri];
                    entries[ri] = tmp;
                }
            }

            var delayBefore  = (parseInt(disp.delay, 10)    || this.settings.delayBefore  || 5) * 1000;
            var displayFor   = (parseInt(disp.duration, 10)  || this.settings.displayFor  || 5) * 1000;
            var delayBetween = (parseInt(disp.interval, 10)  || this.settings.delayBetween || 5) * 1000;
            var loop = disp.loop !== undefined ? !!disp.loop : (this.settings.loop !== undefined ? !!this.settings.loop : true);
            var maxPerSession = parseInt(disp.max_per_session, 10) || 0;

            var position = (notif.design && notif.design.position) || this.settings.position || 'bottom-left';
            var container = this.getContainer(position);

            var state = {
                entries: entries,
                index: 0,
                notif: notif,
                container: container,
                displayFor: displayFor,
                delayBetween: delayBetween,
                loop: loop,
                maxPerSession: maxPerSession,
                shown: 0,
                intervalId: null,
                timeoutId: null,
                active: true
            };

            this.timers[notif.id] = state;

            // Initial delay then show first entry
            state.timeoutId = setTimeout(function () {
                self.showEntry(state);

                // Cycling: use setTimeout chain instead of setInterval for reliability
                function scheduleNext() {
                    if (!state.active) return;
                    state.timeoutId = setTimeout(function () {
                        if (!state.active) return;

                        state.index++;
                        if (state.index >= state.entries.length) {
                            if (!state.loop) {
                                state.active = false;
                                return;
                            }
                            state.index = 0;
                        }
                        self.showEntry(state);
                        scheduleNext();
                    }, displayFor + delayBetween);
                }

                if (entries.length > 1 || loop) {
                    scheduleNext();
                }
            }, delayBefore);
        },

        /**
         * Build entries from notification data.
         * Dynamic sources (WooCommerce, comments) produce multiple entries.
         * Manual/static notifications produce a single entry.
         */
        buildEntries: function (notif) {
            var items = notif.items || [];
            var entries = [];

            if (items.length > 0) {
                for (var i = 0; i < items.length; i++) {
                    entries.push(this.buildEntryFromItem(notif, items[i]));
                }
            } else {
                // Manual: single entry from the notification content
                entries.push({
                    title: notif.title || '',
                    message: notif.message || '',
                    image: notif.image || '',
                    image_style: notif.image_style || 'product',
                    time_text: notif.time_text || '',
                    cta_text: notif.cta_text || '',
                    cta_url: notif.cta_url || '',
                    click_url: notif.click_url || '',
                    click_target: notif.click_target || '_self'
                });
            }

            return entries;
        },

        /**
         * Build one entry from a dynamic item.
         * Maps item data to display fields based on notification type.
         */
        buildEntryFromItem: function (notif, item) {
            var type = notif.type;
            var ct = notif.content || {};
            var clickAction = (notif.customize && notif.customize.click_action) || 'link';
            var entry = {
                image: '', image_style: 'product',
                title: '', message: '', time_text: '',
                cta_text: notif.cta_text || '', cta_url: '',
                click_url: '', click_target: '_self', click_action: clickAction
            };

            // If user provided templates with {{placeholders}}, use them
            var hasTemplateTitle = ct.title && ct.title.indexOf('{{') !== -1;
            var hasTemplateMessage = ct.message && ct.message.indexOf('{{') !== -1;

            if (type === 'sales_notification' || type === 'woocommerce_sales') {
                entry.title = hasTemplateTitle ? processTemplate(ct.title, item) : (item.username || 'Someone');
                entry.message = hasTemplateMessage ? processTemplate(ct.message, item) : ((ct.action_text || 'just purchased') + ' ' + (item.product || ''));
                entry.image = item.product_image || item.avatar || '';
                entry.image_style = 'product';
                entry.time_text = item.time_ago || (item.time ? timeAgo(item.time) : '');
                entry.click_url = item.product_url || '';
                entry.cta_url = item.product_url || '';
                if (item.location) {
                    entry.time_text += (entry.time_text ? ' \u00b7 ' : '') + item.location;
                }
            } else if (type === 'review_notification' || type === 'reviews') {
                entry.title = hasTemplateTitle ? processTemplate(ct.title, item) : (item.username || 'Someone');
                entry.message = hasTemplateMessage ? processTemplate(ct.message, item) : ((ct.action_text || 'left a review on') + ' ' + (item.product || item.post_title || ''));
                entry.image = item.avatar || item.product_image || '';
                entry.image_style = 'avatar';
                entry.time_text = item.time_ago || (item.time ? timeAgo(item.time) : '');
                entry.click_url = item.product_url || item.post_url || '';
                entry.cta_url = entry.click_url;
            } else if (type === 'comment_notification' || type === 'comments' || type === 'wordpress_comments') {
                entry.title = hasTemplateTitle ? processTemplate(ct.title, item) : (item.username || 'Someone');
                entry.message = hasTemplateMessage ? processTemplate(ct.message, item) : ((ct.action_text || 'commented on') + ' ' + (item.post_title || ''));
                entry.image = item.avatar || '';
                entry.image_style = 'avatar';
                entry.time_text = item.time_ago || (item.time ? timeAgo(item.time) : '');
                entry.click_url = item.post_url || '';
                entry.cta_url = entry.click_url;
            } else if (type === 'download_stats' || type === 'wporg_downloads') {
                entry.title = hasTemplateTitle ? processTemplate(ct.title, item) : (item.name || '');
                entry.message = hasTemplateMessage ? processTemplate(ct.message, item) : ((item.active_installs || '0') + ' active installs');
                entry.image = notif.image || '';
                entry.time_text = (item.downloads || '0') + ' total downloads';
            } else if (type === 'email_subscription') {
                entry.title = hasTemplateTitle ? processTemplate(ct.title, item) : (item.username || item.email || 'Someone');
                entry.message = hasTemplateMessage ? processTemplate(ct.message, item) : (ct.action_text || 'just subscribed');
                entry.image = item.avatar || '';
                entry.image_style = 'avatar';
                entry.time_text = item.time_ago || (item.time ? timeAgo(item.time) : '');
            } else if (type === 'custom_notification' || type === 'custom_csv') {
                entry.title = hasTemplateTitle ? processTemplate(ct.title, item) : (item.title || item.username || '');
                entry.message = hasTemplateMessage ? processTemplate(ct.message, item) : (item.message || item.content || '');
                entry.image = item.image || item.avatar || '';
                entry.time_text = item.time_text || item.time_ago || (item.time ? timeAgo(item.time) : '');
                entry.click_url = item.url || item.link || '';
                entry.cta_url = entry.click_url;
            } else {
                // Fallback generic
                entry.title = hasTemplateTitle ? processTemplate(ct.title, item) : (item.username || item.title || item.name || '');
                entry.message = hasTemplateMessage ? processTemplate(ct.message, item) : (item.message || item.content || item.product || item.post_title || '');
                entry.image = item.image || item.avatar || item.product_image || '';
                entry.time_text = item.time_ago || (item.time ? timeAgo(item.time) : '');
                entry.click_url = item.url || item.link || item.product_url || item.post_url || '';
                entry.cta_url = entry.click_url;
            }

            // Respect click action from wizard/customize settings.
            if (clickAction !== 'link') {
                entry.click_url = '';
            }

            return entry;
        },

        /**
         * Show a single entry (one step of the cycle)
         */
        showEntry: function (state) {
            var self = this;
            var notif = state.notif;
            var entry = state.entries[state.index];

            // Session limit checks
            if (state.maxPerSession && state.shown >= state.maxPerSession) {
                state.active = false;
                return;
            }
            if (this.settings.sessionLimit && this.sessionViews >= this.settings.sessionLimit) {
                state.active = false;
                return;
            }

            // Remove current visible notification in the container
            var existing = state.container.querySelector('.kng-fomo-notification');
            if (existing) {
                existing.classList.remove('is-visible');
                setTimeout(function () { existing.remove(); }, 300);
            }

            var showDelay = existing ? 350 : 0;

            setTimeout(function () {
                var el = self.buildPopupHTML(notif, entry, state);
                state.container.appendChild(el);

                if (notif.sound) self.playSound(notif.sound);

                self.trackEvent(notif.id, 'view');
                self.incrementSessionViews();
                state.shown++;

                // Double rAF for reliable CSS transition trigger
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        el.classList.add('is-visible');
                    });
                });

                self.startProgressBar(el, state.displayFor);

                // Auto-hide
                setTimeout(function () {
                    el.classList.remove('is-visible');
                    setTimeout(function () { el.remove(); }, 400);
                }, state.displayFor);
            }, showDelay);
        },

        /**
         * Build popup HTML element
         */
        buildPopupHTML: function (notif, entry, state) {
            var el = document.createElement('div');
            var animation = notif.animation || 'slide';
            var clickAction = entry.click_action || ((notif.customize && notif.customize.click_action) || 'link');
            var hasClick = (clickAction === 'link' && entry.click_url) || clickAction === 'dismiss';

            el.className = 'kng-fomo-notification kng-fomo-notification--shadow kng-fomo-notification--' + animation;
            el.setAttribute('data-notif-id', notif.id);
            if (hasClick) el.classList.add('kng-fomo-notification--clickable');
            el.classList.add('kng-fomo-notification--type-' + (notif.type || 'generic'));

            if (notif.bg_color) el.style.setProperty('--kng-fomo-bg', notif.bg_color);
            if (notif.text_color) el.style.setProperty('--kng-fomo-text', notif.text_color);
            if (notif.accent_color) el.style.setProperty('--kng-fomo-accent', notif.accent_color);

            var html = '';

            // Image
            if (entry.image) {
                var imgCls = (entry.image_style === 'avatar') ? ' kng-fomo-notification__image--avatar' : '';
                html += '<div class="kng-fomo-notification__image' + imgCls + '">';
                html += '<img src="' + esc(entry.image) + '" alt="" loading="lazy">';
                html += '</div>';
            }

            // Content
            html += '<div class="kng-fomo-notification__content">';

            if (entry.title) {
                html += '<p class="kng-fomo-notification__title">' + esc(entry.title) + '</p>';
            }
            if (entry.message) {
                html += '<p class="kng-fomo-notification__message">' + esc(entry.message) + '</p>';
            }
            if (entry.time_text) {
                html += '<div class="kng-fomo-notification__time">';
                html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';
                html += '<span>' + esc(entry.time_text) + '</span>';
                html += '</div>';
            }
            if (entry.cta_text && entry.cta_url) {
                html += '<a href="' + esc(entry.cta_url) + '" class="kng-fomo-notification__cta" target="_blank" rel="noopener">';
                html += esc(entry.cta_text);
                html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
                html += '</a>';
            }

            html += '</div>'; // .content

            // Close button
            html += '<button type="button" class="kng-fomo-notification__close" aria-label="Close">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>';
            html += '</button>';

            // Progress bar
            html += '<div class="kng-fomo-notification__progress"><div class="kng-fomo-notification__progress-bar"></div></div>';

            el.innerHTML = html;

            // --- Event listeners ---
            var self = this;

            // Close dismisses and stops cycling for this notification
            el.querySelector('.kng-fomo-notification__close').addEventListener('click', function (e) {
                e.stopPropagation();
                self.dismiss(notif.id);
                state.active = false;
                if (state.intervalId) clearInterval(state.intervalId);
                if (state.timeoutId) clearTimeout(state.timeoutId);
                el.classList.remove('is-visible');
                setTimeout(function () { el.remove(); }, 400);
            });

            // Click on body navigates
            if (entry.click_url) {
                el.addEventListener('click', function (e) {
                    if (e.target.closest('.kng-fomo-notification__close')) return;
                    if (e.target.closest('.kng-fomo-notification__cta')) return;

                    if (clickAction === 'dismiss') {
                        self.dismiss(notif.id);
                        state.active = false;
                        if (state.intervalId) clearInterval(state.intervalId);
                        if (state.timeoutId) clearTimeout(state.timeoutId);
                        el.classList.remove('is-visible');
                        setTimeout(function () { el.remove(); }, 400);
                        return;
                    }

                    if (clickAction === 'link' && entry.click_url) {
                        self.trackEvent(notif.id, 'click');
                        window.open(entry.click_url, entry.click_target || '_self');
                    }
                });
            } else if (clickAction === 'dismiss') {
                el.addEventListener('click', function (e) {
                    if (e.target.closest('.kng-fomo-notification__close')) return;
                    if (e.target.closest('.kng-fomo-notification__cta')) return;
                    self.dismiss(notif.id);
                    state.active = false;
                    if (state.intervalId) clearInterval(state.intervalId);
                    if (state.timeoutId) clearTimeout(state.timeoutId);
                    el.classList.remove('is-visible');
                    setTimeout(function () { el.remove(); }, 400);
                });
            }

            // CTA
            var ctaBtn = el.querySelector('.kng-fomo-notification__cta');
            if (ctaBtn) {
                ctaBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    self.trackEvent(notif.id, 'click');
                });
            }

            return el;
        },

        /**
         * Progress bar animation: 100% → 0% over duration
         */
        startProgressBar: function (el, duration) {
            var bar = el.querySelector('.kng-fomo-notification__progress-bar');
            if (!bar) return;

            bar.style.transition = 'none';
            bar.style.width = '100%';

            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    bar.style.transition = 'width ' + duration + 'ms linear';
                    bar.style.width = '0%';
                });
            });
        },

        /* =============================================
           Container management
           ============================================= */

        getContainer: function (position) {
            if (this.containers[position]) return this.containers[position];
            var el = document.createElement('div');
            el.className = 'kng-fomo-notification-wrap kng-fomo-notification-wrap--' + position;
            document.body.appendChild(el);
            this.containers[position] = el;
            return el;
        },

        /* =============================================
           Sound
           ============================================= */

        playSound: function (soundType) {
            // Try pre-loaded audio files
            var src = this.getSoundUrl(soundType);
            if (src) {
                try {
                    var audio = new Audio(src);
                    audio.volume = (this.settings.soundVolume || 50) / 100;
                    audio.play().catch(function () {});
                    return;
                } catch (e) {}
            }

            // Fallback: Web Audio API oscillator
            try {
                var ctx = new (window.AudioContext || window.webkitAudioContext)();
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                var vol = ((this.settings.soundVolume || 50) / 100) * 0.3;
                gain.gain.value = vol;
                switch (soundType) {
                    case 'pop':   osc.frequency.value = 600; osc.type = 'sine'; break;
                    case 'ding':  osc.frequency.value = 800; osc.type = 'triangle'; break;
                    case 'chime': osc.frequency.value = 523; osc.type = 'sine'; break;
                    default:      osc.frequency.value = 600; osc.type = 'sine';
                }
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
                osc.start();
                osc.stop(ctx.currentTime + 0.2);
            } catch (e) {}
        },

        getSoundUrl: function (soundType) {
            if (window.kngFomoData && window.kngFomoData.sounds && window.kngFomoData.sounds[soundType]) {
                return window.kngFomoData.sounds[soundType];
            }
            return null;
        },

        /* =============================================
           Analytics
           ============================================= */

        trackEvent: function (notifId, eventType) {
            if (!window.kngFomoData || !window.kngFomoData.ajaxUrl) return;
            var fd = new FormData();
            fd.append('action', 'kng_fomo_track_event');
            fd.append('notification_id', notifId);
            fd.append('event_type', eventType);
            fd.append('nonce', window.kngFomoData.nonce);
            fetch(window.kngFomoData.ajaxUrl, {
                method: 'POST', body: fd, credentials: 'same-origin'
            }).catch(function () {});
        },

        /* =============================================
           Dismissal & session
           ============================================= */

        dismiss: function (id) {
            var d = this.getDismissed();
            if (d.indexOf(id) === -1) d.push(id);
            try { sessionStorage.setItem('kng_fomo_dismissed', JSON.stringify(d)); } catch (e) {}
        },
        isDismissed: function (id) { return this.getDismissed().indexOf(id) > -1; },
        getDismissed: function () {
            try { return JSON.parse(sessionStorage.getItem('kng_fomo_dismissed') || '[]'); } catch (e) { return []; }
        },
        getSessionViews: function () {
            try { return parseInt(sessionStorage.getItem('kng_fomo_views') || '0', 10); } catch (e) { return 0; }
        },
        incrementSessionViews: function () {
            this.sessionViews++;
            try { sessionStorage.setItem('kng_fomo_views', this.sessionViews.toString()); } catch (e) {}
        },

        /* =============================================
           Public API
           ============================================= */

        stop: function (id) {
            var s = this.timers[id];
            if (s) { s.active = false; if (s.intervalId) clearInterval(s.intervalId); if (s.timeoutId) clearTimeout(s.timeoutId); }
        },
        stopAll: function () {
            var ids = Object.keys(this.timers);
            for (var i = 0; i < ids.length; i++) this.stop(ids[i]);
        }
    };

    // Init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { KngFomo.init(); });
    } else {
        KngFomo.init();
    }

    window.KngFomo = KngFomo;

})();
