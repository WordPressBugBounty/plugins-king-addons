/**
 * King Addons Cookie / Consent Bar
 * GDPR/CCPA compliant cookie consent management.
 */
(function () {
    'use strict';

    const config = window.kingAddonsCookieConsent;
    if (!config || !config.options) {
        return;
    }

    const options = config.options;
    const container = document.getElementById('king-addons-cookie-consent');
    if (!container) {
        return;
    }

    const consentName = options.advanced.cookie_name || 'ka_cookie_consent';
    const consentLifetime = parseInt(options.consentLifetime, 10) || 365;
    const isPremium = !!options.isPremium;
    const logsEnabled = !!options.logsEnabled;
    const region = config.region || 'all';

    const categories = options.categories || [];
    const necessaryKeys = categories.filter((cat) => cat.state === 'required').map((cat) => cat.key);

    /**
     * Utility functions
     */
    const encode = (value) => JSON.stringify(value);
    const decode = (value) => {
        try {
            return JSON.parse(value);
        } catch (e) {
            return null;
        }
    };

    /**
     * Storage handler for consent data
     */
    const storage = {
        save(consent) {
            if (options.advanced.storage === 'local' && isPremium && window.localStorage) {
                window.localStorage.setItem(consentName, encode(consent));
                return;
            }

            const expires = new Date();
            expires.setTime(expires.getTime() + consentLifetime * 24 * 60 * 60 * 1000);

            const sameSite = options.advanced.same_site || 'Lax';
            const secureFlag = !!options.advanced.secure || sameSite === 'None';
            const parts = [
                `${consentName}=${encodeURIComponent(encode(consent))}`,
                `expires=${expires.toUTCString()}`,
                `path=${options.advanced.cookie_path || '/'}`,
                options.advanced.cookie_domain ? `domain=${options.advanced.cookie_domain}` : '',
                `SameSite=${sameSite}`,
                secureFlag ? 'Secure' : '',
            ].filter(Boolean);
            document.cookie = parts.join('; ');
        },
        read() {
            if (options.advanced.storage === 'local' && isPremium && window.localStorage) {
                const stored = window.localStorage.getItem(consentName);
                return stored ? decode(stored) : null;
            }

            const cookies = document.cookie ? document.cookie.split('; ') : [];
            for (const entry of cookies) {
                if (entry.startsWith(`${consentName}=`)) {
                    return decode(decodeURIComponent(entry.substring(consentName.length + 1)));
                }
            }
            return null;
        },
    };

    /**
     * Apply CSS variables from design options
     */
    const setCSSVariables = () => {
        const colors = options.design.colors;
        container.style.setProperty('--ka-bg', colors.background || '#111827');
        container.style.setProperty('--ka-text', colors.text || '#f9fafb');
        container.style.setProperty('--ka-link', colors.link || '#60a5fa');
        container.style.setProperty('--ka-primary-bg', colors.primary_bg || '#2563eb');
        container.style.setProperty('--ka-primary-text', colors.primary_text || '#ffffff');
        container.style.setProperty('--ka-secondary-bg', colors.secondary_bg || '#374151');
        container.style.setProperty('--ka-secondary-text', colors.secondary_text || '#ffffff');
        container.style.setProperty('--ka-border', colors.border || '#374151');
        container.style.setProperty('--ka-radius', `${options.design.border_radius || 12}px`);
        container.style.setProperty('--ka-shadow', options.design.shadow ? '0 20px 50px rgba(0,0,0,0.3)' : 'none');
    };

    /**
     * Apply layout class based on design options
     */
    const applyLayoutClass = () => {
        const layoutMap = {
            'top-bar': 'king-addons-cookie-consent--top-bar',
            'bottom-left': 'king-addons-cookie-consent--bottom-left',
            'bottom-right': 'king-addons-cookie-consent--bottom-right',
            'modal': 'king-addons-cookie-consent--modal',
        };
        const layoutClass = layoutMap[options.design.layout] || 'king-addons-cookie-consent--bottom-bar';
        container.classList.add(layoutClass);

        // Apply preset class
        const preset = options.design.preset || 'dark';
        container.classList.add(`king-addons-cookie-consent--preset-${preset}`);

        // Apply animation class
        const animation = options.design.animation || 'fade';
        container.classList.add(`king-addons-cookie-consent--anim-${animation}`);
    };

    const bannerEl = container.querySelector('.king-addons-cookie-consent__banner');
    const modalEl = container.querySelector('.king-addons-cookie-consent__modal');

    /**
     * Build the consent banner
     */
    const buildBanner = () => {
        const body = document.createElement('div');
        body.className = 'king-addons-cookie-consent__body';

        // Title
        const title = document.createElement('h3');
        title.className = 'king-addons-cookie-consent__title';
        title.textContent = options.content.title;

        // Message text
        const text = document.createElement('p');
        text.className = 'king-addons-cookie-consent__text';
        text.textContent = options.content.message;

        // Policy links
        const links = document.createElement('div');
        links.className = 'king-addons-cookie-consent__links';
        
        if (options.content.privacy_url) {
            const privacy = document.createElement('a');
            privacy.href = options.content.privacy_url;
            privacy.target = '_blank';
            privacy.rel = 'noopener noreferrer';
            privacy.textContent = options.content.privacy_label || 'Privacy Policy';
            links.appendChild(privacy);
        }
        
        const cookieLink = options.content.cookie_url_custom || options.content.cookie_url;
        if (cookieLink) {
            const cookie = document.createElement('a');
            cookie.href = cookieLink;
            cookie.target = '_blank';
            cookie.rel = 'noopener noreferrer';
            cookie.textContent = options.content.cookie_label || 'Cookie Policy';
            links.appendChild(cookie);
        }

        // Action buttons
        const actions = document.createElement('div');
        actions.className = 'king-addons-cookie-consent__actions';

        const acceptBtn = document.createElement('button');
        acceptBtn.type = 'button';
        acceptBtn.className = 'king-addons-cookie-consent__btn king-addons-cookie-consent__btn--primary';
        acceptBtn.textContent = options.buttons.accept;
        acceptBtn.addEventListener('click', () => handleAcceptAll());

        const rejectBtn = document.createElement('button');
        rejectBtn.type = 'button';
        rejectBtn.className = 'king-addons-cookie-consent__btn king-addons-cookie-consent__btn--secondary';
        rejectBtn.textContent = options.buttons.reject;
        rejectBtn.addEventListener('click', () => handleRejectAll());

        const settingsBtn = document.createElement('button');
        settingsBtn.type = 'button';
        settingsBtn.className = 'king-addons-cookie-consent__btn king-addons-cookie-consent__btn--secondary';
        settingsBtn.textContent = options.buttons.settings;
        settingsBtn.addEventListener('click', () => openModal());

        actions.append(acceptBtn, rejectBtn, settingsBtn);
        body.append(title, text, links, actions);
        bannerEl.appendChild(body);
    };

    /**
     * Build the preferences modal
     */
    const buildModal = () => {
        const inner = document.createElement('div');
        inner.className = 'king-addons-cookie-consent__modal-inner';

        // Header
        const header = document.createElement('div');
        header.className = 'king-addons-cookie-consent__modal-header';

        const title = document.createElement('h3');
        title.className = 'king-addons-cookie-consent__modal-title';
        title.textContent = options.content.title;

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'king-addons-cookie-consent__close';
        closeBtn.setAttribute('aria-label', 'Close');
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', () => closeModal());

        header.append(title, closeBtn);

        // Body with description and links
        const body = document.createElement('div');
        body.className = 'king-addons-cookie-consent__modal-body';

        const description = document.createElement('p');
        description.className = 'king-addons-cookie-consent__modal-text';
        description.textContent = options.content.message;

        // Policy links in modal
        const links = document.createElement('div');
        links.className = 'king-addons-cookie-consent__modal-links';
        
        if (options.content.privacy_url) {
            const privacy = document.createElement('a');
            privacy.href = options.content.privacy_url;
            privacy.target = '_blank';
            privacy.rel = 'noopener noreferrer';
            privacy.textContent = options.content.privacy_label || 'Privacy Policy';
            links.appendChild(privacy);
        }
        
        const cookieLink = options.content.cookie_url_custom || options.content.cookie_url;
        if (cookieLink) {
            const cookie = document.createElement('a');
            cookie.href = cookieLink;
            cookie.target = '_blank';
            cookie.rel = 'noopener noreferrer';
            cookie.textContent = options.content.cookie_label || 'Cookie Policy';
            links.appendChild(cookie);
        }

        // Categories list
        const categoriesWrap = document.createElement('div');
        categoriesWrap.className = 'king-addons-cookie-consent__categories';

        const storedConsent = storage.read();
        const storedCategories = storedConsent?.categories || [];

        categories.forEach((category) => {
            if (category.display === false) {
                return;
            }

            const item = document.createElement('div');
            item.className = 'king-addons-cookie-consent__category';

            const headerRow = document.createElement('div');
            headerRow.className = 'king-addons-cookie-consent__category-header';

            const label = document.createElement('h4');
            label.textContent = category.label;

            headerRow.appendChild(label);

            // Toggle or required badge
            if (category.state === 'required') {
                const badge = document.createElement('span');
                badge.className = 'king-addons-cookie-consent__required-badge';
                badge.textContent = 'Required';
                headerRow.appendChild(badge);
            } else {
                const toggleWrap = document.createElement('label');
                toggleWrap.className = 'king-addons-cookie-consent__toggle';

                const toggle = document.createElement('input');
                toggle.type = 'checkbox';
                toggle.value = category.key;
                toggle.dataset.categoryKey = category.key;
                
                // Check based on stored consent or default state
                if (storedCategories.includes(category.key)) {
                    toggle.checked = true;
                } else if (category.state === 'on') {
                    toggle.checked = true;
                } else {
                    toggle.checked = false;
                }

                const slider = document.createElement('span');
                slider.className = 'king-addons-cookie-consent__toggle-slider';

                toggleWrap.append(toggle, slider);
                headerRow.appendChild(toggleWrap);
            }

            const desc = document.createElement('p');
            desc.textContent = category.description || '';

            item.append(headerRow, desc);
            categoriesWrap.appendChild(item);
        });

        body.append(description, links, categoriesWrap);

        // Footer with actions
        const footer = document.createElement('div');
        footer.className = 'king-addons-cookie-consent__footer';

        const footerActions = document.createElement('div');
        footerActions.className = 'king-addons-cookie-consent__footer-actions';

        const rejectBtn = document.createElement('button');
        rejectBtn.type = 'button';
        rejectBtn.className = 'king-addons-cookie-consent__btn king-addons-cookie-consent__btn--secondary';
        rejectBtn.textContent = options.buttons.reject;
        rejectBtn.addEventListener('click', () => handleRejectAll());

        const acceptAllBtn = document.createElement('button');
        acceptAllBtn.type = 'button';
        acceptAllBtn.className = 'king-addons-cookie-consent__btn king-addons-cookie-consent__btn--secondary';
        acceptAllBtn.textContent = options.buttons.accept;
        acceptAllBtn.addEventListener('click', () => handleAcceptAll());

        const saveBtn = document.createElement('button');
        saveBtn.type = 'button';
        saveBtn.className = 'king-addons-cookie-consent__btn king-addons-cookie-consent__btn--primary';
        saveBtn.textContent = options.buttons.save;
        saveBtn.addEventListener('click', () => {
            const selected = [...categoriesWrap.querySelectorAll('input[type="checkbox"]')]
                .filter((input) => input.checked)
                .map((input) => input.value);
            handleSave(selected, 'custom');
        });

        footerActions.append(rejectBtn, acceptAllBtn, saveBtn);
        footer.appendChild(footerActions);

        inner.append(header, body, footer);
        modalEl.appendChild(inner);
    };

    /**
     * Consent handlers
     */
    const applyConsent = (categoriesAllowed, actionType) => {
        const consent = {
            categories: categoriesAllowed,
            version: options.policyVersion,
            timestamp: Date.now(),
        };
        storage.save(consent);
        activateScripts(categoriesAllowed);
        activateDataAttributes(categoriesAllowed);
        runManualBlocks(categoriesAllowed);

        // Push to dataLayer for GTM integration
        if (window.dataLayer && isPremium) {
            window.dataLayer.push({
                event: 'consent_update',
                consentCategories: categoriesAllowed,
                consentAction: actionType,
            });
        }

        if (logsEnabled) {
            logEvent(actionType, categoriesAllowed);
        }
    };

    const hideAll = () => {
        bannerEl.classList.remove('is-visible');
        modalEl.classList.remove('is-visible');
        document.body.style.overflow = '';
    };

    const handleAcceptAll = () => {
        const allowed = categories.map((cat) => cat.key);
        applyConsent(allowed, 'accept');
        hideAll();
    };

    const handleRejectAll = () => {
        const allowed = [...necessaryKeys];
        applyConsent(allowed, 'reject');
        hideAll();
    };

    const handleSave = (selected, actionType = 'custom') => {
        const allowed = new Set(necessaryKeys);
        selected.forEach((cat) => allowed.add(cat));
        applyConsent(Array.from(allowed), actionType);
        hideAll();
    };

    /**
     * Check if we should re-show the banner
     */
    const shouldReshow = (stored) => {
        if (!stored) {
            return true;
        }

        // If stored timestamp is older than lifetime (mainly for localStorage), re-show.
        if (stored.timestamp) {
            const ts = parseInt(stored.timestamp, 10);
            if (!Number.isNaN(ts)) {
                const maxAgeMs = consentLifetime * 24 * 60 * 60 * 1000;
                if (Date.now() - ts > maxAgeMs) {
                    return true;
                }
            }
        }

        // Check policy version, if resurface is enabled.
        const resurface = options?.behavior?.resurface || 'version';
        if (resurface !== 'never') {
            if (stored.version !== options.policyVersion) {
                return true;
            }
        }

        return false;
    };

    /**
     * Activate blocked scripts for allowed categories
     */
    const activateScripts = (allowed) => {
        const scripts = document.querySelectorAll('script[type="text/plain"][data-ka-cookie-category]');
        scripts.forEach((script) => {
            const category = script.getAttribute('data-ka-cookie-category') || 'analytics';
            if (!allowed.includes(category)) {
                return;
            }

            const clone = document.createElement('script');

            const originalType = script.getAttribute('data-ka-cookie-original-type');
            clone.type = originalType || 'text/javascript';
            for (const attr of script.attributes) {
                if (attr.name === 'type' || attr.name === 'data-ka-cookie-category') {
                    continue;
                }
                if (attr.name === 'data-ka-cookie-original-type') {
                    continue;
                }
                if (attr.name === 'data-ka-cookie-src') {
                    continue;
                }
                clone.setAttribute(attr.name, attr.value);
            }

            const dataSrc = script.getAttribute('data-ka-cookie-src');
            if (dataSrc) {
                clone.src = dataSrc;
            } else if (script.src) {
                clone.src = script.src;
            }
            if (script.textContent) {
                clone.textContent = script.textContent;
            }
            script.parentNode.replaceChild(clone, script);
        });
    };

    /**
     * Activate elements that opt-in via data attributes (Pro).
     * Expected usage: set real src/href/etc in data-ka-cookie-* attributes and add data-ka-cookie-category.
     */
    const activateDataAttributes = (allowed) => {
        if (!isPremium || !options.dataAttributes) {
            return;
        }

        const elements = document.querySelectorAll('[data-ka-cookie-category]');
        elements.forEach((el) => {
            if (!el || el.tagName.toLowerCase() === 'script') {
                return;
            }

            const category = (el.getAttribute('data-ka-cookie-category') || 'analytics').toString();
            if (!allowed.includes(category) && category !== 'necessary') {
                return;
            }

            const swaps = [
                ['data-ka-cookie-src', 'src'],
                ['data-ka-cookie-srcset', 'srcset'],
                ['data-ka-cookie-href', 'href'],
                ['data-ka-cookie-poster', 'poster'],
            ];

            swaps.forEach(([from, to]) => {
                const val = el.getAttribute(from);
                if (val) {
                    el.setAttribute(to, val);
                    el.removeAttribute(from);
                }
            });

            if (el.hasAttribute('data-ka-cookie-show')) {
                el.removeAttribute('hidden');
                el.style.display = '';
                el.removeAttribute('data-ka-cookie-show');
            }
        });
    };

    /**
     * Execute manual code blocks (Pro)
     */
    const runManualBlocks = (allowed) => {
        if (!isPremium || !options.manualBlocks || !options.manualBlocks.length) {
            return;
        }
        options.manualBlocks.forEach((block) => {
            if (!allowed.includes(block.category)) {
                return;
            }
            if (block.type === 'html') {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = block.code;
                document.body.appendChild(wrapper);
            } else {
                const script = document.createElement('script');
                script.type = 'text/javascript';
                script.textContent = block.code;
                document.body.appendChild(script);
            }
        });
    };

    /**
     * Log consent event to server (Pro)
     */
    const logEvent = (actionType, allowed) => {
        if (!config.ajax || !config.ajax.url || !logsEnabled) {
            return;
        }
        const payload = new URLSearchParams();
        payload.set('action', 'king_addons_cookie_consent_log');
        payload.set('actionType', actionType);
        allowed.forEach((category) => payload.append('categories[]', category));
        payload.set('region', region);
        payload.set('device', window.innerWidth < 768 ? 'mobile' : 'desktop');
        payload.set('_ajax_nonce', config.ajax.nonce);

        fetch(config.ajax.url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: payload.toString(),
        }).catch(() => {
            // Silently ignore logging errors
        });
    };

    /**
     * Modal controls
     */
    const openModal = () => {
        modalEl.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        modalEl.classList.remove('is-visible');
        document.body.style.overflow = '';
    };

    // Global function to open consent modal
    window.kingAddonsOpenConsent = () => {
        openModal();
    };

    // Custom event listener
    document.addEventListener('king-addons-open-cookie-settings', () => {
        openModal();
    });

    /**
     * Set up manage buttons
     */
    const setupManageButtons = () => {
        document.querySelectorAll('[data-ka-cookie-manage]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                openModal();
            });
        });
    };

    /**
     * Bind Pro behavior triggers (scroll/click consent)
     */
    const bindBehaviorTriggers = () => {
        if (!isPremium) {
            return;
        }
        
        if (options.behavior.scroll_consent) {
            const scrollHandler = () => {
                window.removeEventListener('scroll', scrollHandler);
                handleAcceptAll();
            };
            window.addEventListener('scroll', scrollHandler, { once: true, passive: true });
        }

        if (options.behavior.click_consent) {
            const clickHandler = (event) => {
                if (container.contains(event.target)) {
                    return;
                }
                document.removeEventListener('click', clickHandler);
                handleAcceptAll();
            };
            setTimeout(() => {
                document.addEventListener('click', clickHandler);
            }, 1000);
        }
    };

    /**
     * Initialize the consent system
     */
    const init = () => {
        setCSSVariables();
        applyLayoutClass();
        buildBanner();
        buildModal();
        setupManageButtons();

        const stored = storage.read();
        if (!shouldReshow(stored)) {
            // Already consented, activate scripts
            const allowed = stored.categories || [];
            activateScripts(allowed);
            activateDataAttributes(allowed);
            runManualBlocks(allowed);
            return;
        }

        // Show the banner
        bindBehaviorTriggers();
        bannerEl.classList.add('is-visible');
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
