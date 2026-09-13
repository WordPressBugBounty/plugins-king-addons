/**
 * Keep the Free Shipping Bar in sync after block-theme mini-cart and AJAX cart updates.
 */
(function () {
    "use strict";

    const cfg = window.kingAddonsFsb || {};
    let timer = 0;

    const miniCartHost = () =>
        document.querySelector(
            ".wp-block-woocommerce-filled-mini-cart-contents-block, .wc-block-mini-cart__items"
        );

    const applyHtml = (html) => {
        const existing = document.querySelectorAll(".king-addons-fsb");

        if (!html) {
            existing.forEach((el) => el.remove());
            return;
        }

        const tmp = document.createElement("div");
        tmp.innerHTML = html;
        const next = tmp.firstElementChild;
        if (!(next instanceof HTMLElement)) {
            return;
        }

        if (existing.length) {
            existing.forEach((el, i) => {
                el.replaceWith(i === 0 ? next : next.cloneNode(true));
            });
            return;
        }

        const host = miniCartHost();
        if (host) {
            host.insertAdjacentElement("afterbegin", next);
        }
    };

    const refresh = () => {
        if (!cfg.ajaxUrl) {
            return;
        }

        const body = new URLSearchParams();
        body.append("action", "king_addons_fsb_markup");
        if (cfg.nonce) {
            body.append("nonce", cfg.nonce);
        }

        fetch(cfg.ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
            body: body.toString(),
            credentials: "same-origin",
        })
            .then((res) => res.json())
            .then((res) => {
                if (!res || !res.success) {
                    return;
                }
                applyHtml((res.data && res.data.html) || "");
            })
            .catch(() => {});
    };

    const schedule = () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(refresh, 250);
    };

    document.addEventListener("added_to_cart", schedule);
    document.addEventListener("removed_from_cart", schedule);
    document.body.addEventListener("wc_fragments_refreshed", schedule);
    document.body.addEventListener("wc-blocks_added_to_cart", schedule);
    document.body.addEventListener("wc-blocks_removed_from_cart", schedule);

    document.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }
        if (
            target.closest(".wc-block-components-quantity-selector") ||
            target.closest(".wc-block-cart-item__remove-link") ||
            target.closest(".wc-block-mini-cart__button")
        ) {
            schedule();
        }
    });
})();
