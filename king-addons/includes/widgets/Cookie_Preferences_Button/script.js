/**
 * Cookie Preferences Button behavior.
 *
 * Binds a delegated click handler to elements with [data-ka-cookie-manage].
 * Uses Elementor frontend init hook when available.
 */
(function () {
    const FLAG = "kingAddonsCookiePreferencesBound";

    /**
     * Bind click handler once per page.
     *
     * @return void
     */
    const bindOnce = () => {
        if (window[FLAG]) {
            return;
        }
        window[FLAG] = true;

        document.addEventListener("click", (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const trigger = target.closest("[data-ka-cookie-manage]");
            if (!trigger) {
                return;
            }

            event.preventDefault();

            if (typeof window.kingAddonsOpenConsent === "function") {
                window.kingAddonsOpenConsent();
                return;
            }

            document.dispatchEvent(
                new CustomEvent("king-addons-open-cookie-settings")
            );
        });
    };

    document.addEventListener("DOMContentLoaded", bindOnce);

    // Elementor editor/frontend support.
    if (typeof jQuery !== "undefined") {
        jQuery(window).on("elementor/frontend/init", function () {
            if (
                typeof elementorFrontend !== "undefined" &&
                elementorFrontend.hooks &&
                typeof elementorFrontend.hooks.addAction === "function"
            ) {
                elementorFrontend.hooks.addAction(
                    "frontend/element_ready/king-addons-cookie-preferences-button.default",
                    function () {
                        bindOnce();
                    }
                );
            } else {
                bindOnce();
            }
        });
    }
})();



