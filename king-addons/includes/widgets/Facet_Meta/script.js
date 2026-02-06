/**
 * Facet Meta/ACF widget behavior.
 *
 * Dispatches filter changes through a custom event consumed by the faceted
 * filters system.
 */
(function ($) {
    "use strict";

    const FLAG = "kingAddonsFacetMetaBound";

    /**
     * Dispatch filter updates.
     *
     * @param {string} queryId Query identifier.
     * @param {Object} payload Filters payload.
     * @returns {void}
     */
    const dispatch = (queryId, payload) => {
        const event = new CustomEvent("kingaddons:filters:apply", {
            detail: {
                queryId,
                filters: payload,
            },
        });
        document.dispatchEvent(event);
        window.dispatchEvent(event);
    };

    /**
     * Bind delegated listeners once per page.
     *
     * @returns {void}
     */
    const bindOnce = () => {
        if (window[FLAG]) {
            return;
        }
        window[FLAG] = true;

        document.addEventListener("input", (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const wrapper = target.closest(".ka-facet-meta");
            if (!wrapper) {
                return;
            }

            const queryId = wrapper.dataset.kaFiltersQueryId || "";
            const key = wrapper.dataset.kaMetaKey || "";
            if (!queryId || !key) {
                return;
            }

            if (
                target.classList.contains("ka-facet-meta__input") ||
                target.classList.contains("ka-facet-meta__select")
            ) {
                dispatch(queryId, {
                    meta: { [key]: target.value ? [target.value] : [] },
                });
                return;
            }

            if (
                target.classList.contains("ka-facet-meta__range-min") ||
                target.classList.contains("ka-facet-meta__range-max")
            ) {
                const minEl = wrapper.querySelector(".ka-facet-meta__range-min");
                const maxEl = wrapper.querySelector(".ka-facet-meta__range-max");
                dispatch(queryId, {
                    meta: {
                        [key]: {
                            min: minEl ? minEl.value : "",
                            max: maxEl ? maxEl.value : "",
                        },
                    },
                });
            }
        });
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/facet_meta.default",
            function () {
                bindOnce();
            }
        );
    });

    document.addEventListener("DOMContentLoaded", bindOnce);
})(jQuery);





