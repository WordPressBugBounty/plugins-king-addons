/**
 * Age Gate widget behavior.
 *
 * This script renders a global overlay. It must initialize once per page load,
 * and should also work inside Elementor preview without duplicating DOM nodes.
 */
(function () {
    "use strict";

    const FLAG = "kingAddonsAgeGateInitialized";

    // Module-scoped references used by helpers like setStatus()/closeOverlay().
    // These are assigned during init() and remain available for button handlers.
    let payload = null;
    let root = null;
    let state = null;

    /**
     * Initialize Age Gate once.
     *
     * @returns {void}
     */
    const init = () => {
        payload = window.kingAddonsAgeGate;
        if (!payload || !payload.enabled) {
            return;
        }

        if (window[FLAG]) {
            return;
        }

        if (!document.body) {
            // Defer until body is available.
            setTimeout(init, 0);
            return;
        }

        window[FLAG] = true;

        root = ensureRoot();
        const card = root.querySelector('.king-addons-age-gate__card');
        const content = root.querySelector('.king-addons-age-gate__content');
        state = {
            status: payload.status || '',
            cookieDays: resolveCookieDays(payload),
        };

        if (!card || !content) {
            return;
        }

        applyDesign(root, card, payload);

        const needsBlock =
            payload.behaviour &&
            payload.behaviour.denyAction === 'block' &&
            state.status === 'denied';

        if (!payload.shouldRender && !needsBlock) {
            return;
        }

        lockScroll();
        showOverlay(root, payload);

        if (payload.elementorTemplate) {
            const templateWrapper = document.createElement('div');
            templateWrapper.className = 'king-addons-age-gate__template';
            templateWrapper.innerHTML = payload.elementorTemplate;
            content.appendChild(templateWrapper);
        }

        if (needsBlock) {
            renderBlocked(content, payload);
            return;
        }

        if (payload.mode === 'dob' && payload.isPremium) {
            renderDob(content, payload, state);
        } else {
            renderYesNo(content, payload, state);
        }
    };

    const initWhenReady = () => {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", init);
            return;
        }
        init();
    };

    initWhenReady();

    // Elementor preview support: run init after Elementor frontend bootstrap.
    if (typeof jQuery !== "undefined") {
        jQuery(window).on("elementor/frontend/init", function () {
            init();
        });
    }

    /**
     * Creates root container if not present.
     */
    function ensureRoot() {
        let rootEl = document.getElementById('king-addons-age-gate');
        if (!rootEl) {
            rootEl = document.createElement('div');
            rootEl.id = 'king-addons-age-gate';
            rootEl.className = 'king-addons-age-gate';
            rootEl.innerHTML = '<div class="king-addons-age-gate__overlay"></div><div class="king-addons-age-gate__card"><div class="king-addons-age-gate__content"></div></div>';
            document.body.appendChild(rootEl);
        }
        return rootEl;
    }

    /**
     * Applies design variables to the overlay.
     */
    function applyDesign(rootEl, cardEl, data) {
        const design = data.design || {};

        // Reset template + animation modifiers (important for Elementor preview re-init).
        const templateClasses = [
            'king-addons-age-gate--template-center-card',
            'king-addons-age-gate--template-bottom-card',
            'king-addons-age-gate--template-top-card',
            'king-addons-age-gate--template-side-left',
            'king-addons-age-gate--template-side-right',
            'king-addons-age-gate--template-fullscreen',
        ];
        templateClasses.forEach((cls) => rootEl.classList.remove(cls));
        Array.from(rootEl.classList)
            .filter((cls) => cls.indexOf('king-addons-age-gate--animate-') === 0)
            .forEach((cls) => rootEl.classList.remove(cls));

        cardEl.classList.remove('king-addons-age-gate__card--bottom');
        cardEl.classList.remove('king-addons-age-gate__card--top');

        const existingBg = cardEl.querySelector('.king-addons-age-gate__background');
        if (existingBg) {
            existingBg.remove();
        }

        const overlayColor = (design.overlayColor || '#0d0d0d').toString();
        const overlayOpacity = clamp01(design.overlayOpacity ?? 0.7);
        rootEl.style.setProperty('--ka-age-overlay', overlayColor);
        rootEl.style.setProperty('--ka-age-overlay-opacity', overlayOpacity);
        rootEl.style.setProperty('--ka-age-overlay-rgba', toRgba(overlayColor, overlayOpacity) || ('rgba(13, 13, 13, ' + overlayOpacity + ')'));
        rootEl.style.setProperty('--ka-age-card-bg', design.cardBackground || '#ffffff');
        rootEl.style.setProperty('--ka-age-card-width', (design.cardWidth || 520) + 'px');
        rootEl.style.setProperty('--ka-age-text', design.textColor || '#111827');
        rootEl.style.setProperty('--ka-age-title-size', (design.titleSize || 24) + 'px');
        rootEl.style.setProperty('--ka-age-body-size', (design.bodySize || 16) + 'px');
        rootEl.style.setProperty('--ka-age-title-weight', design.titleWeight || 700);
        rootEl.style.setProperty('--ka-age-body-weight', design.bodyWeight || 400);
        rootEl.style.setProperty('--ka-age-btn-yes-color', design.buttonYesColor || '#ffffff');
        rootEl.style.setProperty('--ka-age-btn-yes-bg', design.buttonYesBg || '#10b981');
        rootEl.style.setProperty('--ka-age-btn-no-color', design.buttonNoColor || '#ffffff');
        rootEl.style.setProperty('--ka-age-btn-no-bg', design.buttonNoBg || '#ef4444');
        rootEl.style.setProperty('--ka-age-btn-yes-hover-bg', design.buttonYesHoverBg || design.buttonYesBg || '#0f9c75');
        rootEl.style.setProperty('--ka-age-btn-no-hover-bg', design.buttonNoHoverBg || design.buttonNoBg || '#d92d20');
        rootEl.style.setProperty('--ka-age-btn-yes-hover-color', design.buttonYesHoverColor || design.buttonYesColor || '#ffffff');
        rootEl.style.setProperty('--ka-age-btn-no-hover-color', design.buttonNoHoverColor || design.buttonNoColor || '#ffffff');

        const template = (design.template || 'center-card').toString();
        rootEl.classList.add('king-addons-age-gate--template-' + template);

        // Back-compat: old settings may still use cardAlign.
        if (design.cardAlign === 'bottom' || template === 'bottom-card') {
            cardEl.classList.add('king-addons-age-gate__card--bottom');
        }

        if (template === 'top-card' || design.cardAlign === 'top') {
            cardEl.classList.add('king-addons-age-gate__card--top');
        }

        if (design.backgroundImage) {
            const bg = document.createElement('div');
            bg.className = 'king-addons-age-gate__background';
            bg.style.backgroundImage = 'url(' + design.backgroundImage + ')';
            cardEl.prepend(bg);
        }

        if (design.animation && design.animation !== 'none') {
            rootEl.classList.add('king-addons-age-gate--animate-' + design.animation);
        }
    }

    function clamp01(value) {
        const numberValue = typeof value === 'number' ? value : parseFloat(value);
        if (!Number.isFinite(numberValue)) {
            return 0.7;
        }
        return Math.min(1, Math.max(0, numberValue));
    }

    function toRgba(color, alpha) {
        const c = (color || '').toString().trim();
        if (!c) {
            return null;
        }

        // #RGB / #RRGGBB
        if (c[0] === '#') {
            let hex = c.slice(1);
            if (hex.length === 3) {
                hex = hex.split('').map((ch) => ch + ch).join('');
            }
            if (hex.length !== 6) {
                return null;
            }
            const r = parseInt(hex.slice(0, 2), 16);
            const g = parseInt(hex.slice(2, 4), 16);
            const b = parseInt(hex.slice(4, 6), 16);
            if ([r, g, b].some((n) => Number.isNaN(n))) {
                return null;
            }
            return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
        }

        // rgb(r, g, b)
        const rgbMatch = c.match(/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i);
        if (rgbMatch) {
            return 'rgba(' + rgbMatch[1] + ', ' + rgbMatch[2] + ', ' + rgbMatch[3] + ', ' + alpha + ')';
        }

        // rgba(r, g, b, a) -> replace alpha
        const rgbaMatch = c.match(/^rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*([0-9.]+)\s*\)$/i);
        if (rgbaMatch) {
            return 'rgba(' + rgbaMatch[1] + ', ' + rgbaMatch[2] + ', ' + rgbaMatch[3] + ', ' + alpha + ')';
        }

        return null;
    }

    /**
     * Shows the overlay.
     */
    function showOverlay(rootEl, data) {
        rootEl.classList.add('king-addons-age-gate--visible');
        rootEl.setAttribute('aria-hidden', 'false');
        const overlay = rootEl.querySelector('.king-addons-age-gate__overlay');
        if (overlay) {
            overlay.setAttribute('aria-hidden', 'true');
        }
    }

    /**
     * Renders simple Yes/No mode.
     */
    function renderYesNo(wrapper, data, localState) {
        wrapper.innerHTML = '';
        const title = document.createElement('h2');
        title.className = 'king-addons-age-gate__title';
        title.textContent = data.texts.title;

        const subtitle = document.createElement('p');
        subtitle.className = 'king-addons-age-gate__subtitle';
        subtitle.textContent = data.mode === 'minimum'
            ? data.texts.subtitle + ' (' + data.minAge + '+)'
            : data.texts.subtitle;

        const buttons = document.createElement('div');
        buttons.className = 'king-addons-age-gate__buttons';

        const yesBtn = buildButton('king-addons-age-gate__button king-addons-age-gate__button--allow', data.texts.yes);
        const noBtn = buildButton('king-addons-age-gate__button king-addons-age-gate__button--deny', data.texts.no);

        const consent = buildConsent(data);

        yesBtn.addEventListener('click', () => {
            if (consent && !consent.checkbox.checked) {
                showMessage(wrapper, data.behaviour.blockMessage || 'Please confirm consent.');
                return;
            }
            setStatus('allowed', localState.cookieDays);
            closeOverlay();
        });

        noBtn.addEventListener('click', () => {
            setStatus('denied', localState.cookieDays);
            if (data.behaviour.denyAction === 'redirect' && data.behaviour.denyRedirectUrl) {
                window.location.href = data.behaviour.denyRedirectUrl;
            } else {
                renderBlocked(wrapper, data);
            }
        });

        buttons.appendChild(yesBtn);
        buttons.appendChild(noBtn);

        appendLogo(wrapper, data.design);
        wrapper.appendChild(title);
        wrapper.appendChild(subtitle);
        if (consent) {
            wrapper.appendChild(consent.container);
        }
        wrapper.appendChild(buttons);
    }

    /**
     * Renders DOB form.
     */
    function renderDob(wrapper, data, localState) {
        wrapper.innerHTML = '';
        const dobConfig = data.dob || { format: 'dmy', errors: { invalid: 'Invalid date.', denied: 'Access denied.' } };
        
        const title = document.createElement('h2');
        title.className = 'king-addons-age-gate__title';
        title.textContent = data.texts.title;

        const subtitle = document.createElement('p');
        subtitle.className = 'king-addons-age-gate__subtitle';
        subtitle.textContent = data.texts.subtitle + ' (' + data.minAge + '+)';

        const grid = document.createElement('div');
        grid.className = 'king-addons-age-gate__field-grid';

        const inputs = buildDobInputs(dobConfig.format || 'dmy');
        inputs.forEach((input) => grid.appendChild(input));

        const consent = buildConsent(data);

        const buttons = document.createElement('div');
        buttons.className = 'king-addons-age-gate__buttons';
        const submitBtn = buildButton('king-addons-age-gate__button king-addons-age-gate__button--allow', data.texts.yes);
        const declineBtn = buildButton('king-addons-age-gate__button king-addons-age-gate__button--deny', data.texts.no);

        const message = document.createElement('div');
        message.className = 'king-addons-age-gate__message';
        message.style.display = 'none';

        submitBtn.addEventListener('click', () => {
            if (consent && !consent.checkbox.checked) {
                showMessage(wrapper, data.behaviour.blockMessage || dobConfig.errors.invalid);
                return;
            }
            message.style.display = 'none';
            const params = new URLSearchParams();
            params.append('action', data.ajax.action);
            params.append('nonce', data.ajax.nonce);
            params.append('day', inputs.day.value || '');
            params.append('month', inputs.month.value || '');
            params.append('year', inputs.year.value || '');

            submitBtn.disabled = true;
            submitBtn.textContent = data.texts.yes + '...';

            fetch(data.ajax.url, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: params.toString(),
            }).then((response) => response.json())
                .then((resp) => {
                    if (resp && resp.success) {
                        setStatus('allowed', localState.cookieDays);
                        closeOverlay();
                    } else {
                        const msg = resp && resp.data && resp.data.message ? resp.data.message : dobConfig.errors.denied;
                        showInlineMessage(msg);
                        setStatus('denied', localState.cookieDays);
                    }
                })
                .catch(() => {
                    showInlineMessage(dobConfig.errors.invalid);
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = data.texts.yes;
                });
        });

        declineBtn.addEventListener('click', () => {
            setStatus('denied', localState.cookieDays);
            if (data.behaviour.denyAction === 'redirect' && data.behaviour.denyRedirectUrl) {
                window.location.href = data.behaviour.denyRedirectUrl;
            } else {
                renderBlocked(wrapper, data);
            }
        });

        buttons.appendChild(submitBtn);
        buttons.appendChild(declineBtn);

        appendLogo(wrapper, data.design);
        wrapper.appendChild(title);
        wrapper.appendChild(subtitle);
        wrapper.appendChild(grid);
        if (consent) {
            wrapper.appendChild(consent.container);
        }
        wrapper.appendChild(buttons);
        wrapper.appendChild(message);

        function showInlineMessage(text) {
            message.textContent = text;
            message.style.display = 'block';
        }
    }

    /**
     * Builds DOB inputs based on format.
     */
    function buildDobInputs(format) {
        const day = document.createElement('input');
        day.type = 'number';
        day.min = '1';
        day.max = '31';
        day.placeholder = 'DD';
        day.className = 'king-addons-age-gate__input';

        const month = document.createElement('input');
        month.type = 'number';
        month.min = '1';
        month.max = '12';
        month.placeholder = 'MM';
        month.className = 'king-addons-age-gate__input';

        const year = document.createElement('input');
        year.type = 'number';
        year.min = '1900';
        year.placeholder = 'YYYY';
        year.className = 'king-addons-age-gate__input';

        const parts = [];
        if (format === 'mdy') {
            parts.push(month, day, year);
        } else if (format === 'ymd') {
            parts.push(year, month, day);
        } else {
            parts.push(day, month, year);
        }

        parts.day = day;
        parts.month = month;
        parts.year = year;
        return parts;
    }

    /**
     * Renders blocked message.
     */
    function renderBlocked(wrapper, data) {
        wrapper.innerHTML = '';
        const title = document.createElement('h2');
        title.className = 'king-addons-age-gate__title';
        title.textContent = data.texts.title;

        const message = document.createElement('p');
        message.className = 'king-addons-age-gate__message';
        message.style.display = 'block';
        message.textContent = data.behaviour.blockMessage || data.texts.block;

        appendLogo(wrapper, data.design);
        wrapper.appendChild(title);
        wrapper.appendChild(message);

        if (data.behaviour.denyRedirectUrl) {
            const buttons = document.createElement('div');
            buttons.className = 'king-addons-age-gate__buttons';
            const goBtn = buildButton('king-addons-age-gate__button king-addons-age-gate__button--allow', data.texts.no);
            goBtn.addEventListener('click', () => {
                window.location.href = data.behaviour.denyRedirectUrl;
            });
            buttons.appendChild(goBtn);
            wrapper.appendChild(buttons);
        }
    }

    /**
     * Builds consent checkbox if required.
     */
    function buildConsent(data) {
        if (!data.behaviour || !data.behaviour.consentCheckbox) {
            return null;
        }
        const container = document.createElement('label');
        container.className = 'king-addons-age-gate__subtitle';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.style.marginRight = '8px';
        container.appendChild(checkbox);
        const labelText = data.behaviour.consentLabel || 'I agree to the policy.';
        container.appendChild(document.createTextNode(labelText));
        return {container, checkbox};
    }

    /**
     * Utility to create button.
     */
    function buildButton(classes, text) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = classes;
        button.textContent = text;
        return button;
    }

    /**
     * Appends logo if provided.
     */
    function appendLogo(wrapper, design) {
        if (!design || !design.logo) {
            return;
        }
        const logo = document.createElement('img');
        logo.src = design.logo;
        logo.alt = '';
        logo.className = 'king-addons-age-gate__logo';
        wrapper.appendChild(logo);
    }

    /**
     * Shows a toast-like message within wrapper.
     */
    function showMessage(wrapper, text) {
        let message = wrapper.querySelector('.king-addons-age-gate__message');
        if (!message) {
            message = document.createElement('div');
            message.className = 'king-addons-age-gate__message';
            wrapper.appendChild(message);
        }
        message.textContent = text;
        message.style.display = 'block';
    }

    /**
     * Sets cookie and updates state.
     */
    function setStatus(status, days) {
        if (payload && payload.cookie && payload.cookie.name) {
            const revision = payload.cookie.revision ? payload.cookie.revision : '';
            const value = encodeURIComponent(status + '|' + revision);
            let cookie = payload.cookie.name + '=' + value + '; path=/; SameSite=Lax';
            if (payload.cookie.domain) {
                cookie += '; domain=' + payload.cookie.domain;
            }
            if (days > 0) {
                const date = new Date();
                date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
                cookie += '; expires=' + date.toUTCString();
            }
            document.cookie = cookie;
        }

        if (state) {
            state.status = status;
        }
    }

    /**
     * Locks document scroll.
     */
    function lockScroll() {
        document.documentElement.dataset.kaAgeGateScroll = document.documentElement.style.overflow || '';
        document.documentElement.style.overflow = 'hidden';
    }

    /**
     * Restores scroll and hides overlay.
     */
    function closeOverlay() {
        const previous = document.documentElement.dataset.kaAgeGateScroll || '';
        document.documentElement.style.overflow = previous;
        if (root) {
            root.classList.remove('king-addons-age-gate--visible');
        }
    }

    /**
     * Cookie days resolver based on behaviour.
     */
    function resolveCookieDays(data) {
        if (data.behaviour && data.behaviour.repeatMode) {
            if (data.behaviour.repeatMode === 'session') {
                return 0;
            }
            if (data.behaviour.repeatMode === 'once') {
                return 3650;
            }
            if (data.behaviour.repeatMode === 'days') {
                return data.behaviour.repeatDays || data.cookie.days || 30;
            }
        }
        return data.cookie && data.cookie.days ? data.cookie.days : 30;
    }
})();



