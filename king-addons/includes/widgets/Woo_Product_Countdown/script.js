/**
 * Woo Product Countdown widget behavior.
 *
 * Updates countdown timers on the frontend.
 */
(function ($) {
    "use strict";

    const INIT_FLAG = "kaWooCountdownInit";

    const initCountdown = (el) => {
        if (!el || !el.dataset) return;
        if (el.dataset[INIT_FLAG] === "1") return;
        el.dataset[INIT_FLAG] = "1";

        const endTs = parseInt(el.dataset.end || '0', 10);
        if (!endTs) return;
        const showSeconds = el.dataset.seconds === 'yes';
        const expireMode = el.dataset.expire || 'hide';
        const expireText = el.dataset.expireText || '';
        const format = el.classList.contains('ka-woo-countdown--inline') ? 'inline' : 'blocks';

        const numbers = {
            days: el.querySelector('[data-unit="days"]'),
            hours: el.querySelector('[data-unit="hours"]'),
            minutes: el.querySelector('[data-unit="minutes"]'),
            seconds: el.querySelector('[data-unit="seconds"]'),
        };

        const tick = () => {
            const now = Math.floor(Date.now() / 1000);
            let diff = endTs - now;
            if (diff <= 0) {
                if (expireMode === 'text' && expireText) {
                    const msg = document.createElement("div");
                    msg.className = "ka-woo-countdown__expired-text";
                    msg.textContent = expireText;
                    el.innerHTML = "";
                    el.appendChild(msg);
                    return;
                }
                if (expireMode === 'zero') {
                    if (numbers.days) numbers.days.textContent = '0';
                    if (numbers.hours) numbers.hours.textContent = '00';
                    if (numbers.minutes) numbers.minutes.textContent = '00';
                    if (showSeconds && numbers.seconds) numbers.seconds.textContent = '00';
                    return;
                }
                el.remove();
                return;
            }
            const days = Math.floor(diff / 86400);
            diff -= days * 86400;
            const hours = Math.floor(diff / 3600);
            diff -= hours * 3600;
            const minutes = Math.floor(diff / 60);
            diff -= minutes * 60;
            const seconds = diff;

            if (numbers.days) numbers.days.textContent = days;
            const hoursText = hours.toString().padStart(2, '0');
            const minutesText = minutes.toString().padStart(2, '0');
            const secondsText = seconds.toString().padStart(2, '0');

            if (numbers.hours) numbers.hours.textContent = hoursText;
            if (numbers.minutes) numbers.minutes.textContent = minutesText;
            if (showSeconds && numbers.seconds) {
                numbers.seconds.textContent = secondsText;
            } else if (!showSeconds && numbers.seconds && 'inline' === format) {
                numbers.seconds.parentElement?.remove();
            }
        };

        tick();
        setInterval(tick, 1000);
    };

    const initAll = (root) => {
        const ctx = root && root.querySelectorAll ? root : document;
        ctx.querySelectorAll(".ka-woo-countdown").forEach(initCountdown);
    };

    document.addEventListener("DOMContentLoaded", () => initAll(document));

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/woo_product_countdown.default",
            function ($scope) {
                initAll($scope && $scope[0] ? $scope[0] : document);
            }
        );
    });
})(jQuery);






