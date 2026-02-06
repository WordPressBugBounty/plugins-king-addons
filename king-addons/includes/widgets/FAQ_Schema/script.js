"use strict";

(function ($) {
    const ANIMATION_DURATION = 180;
    const ZOOM_DURATION = 200;

    const normalizeAnimation = (value) => {
        const animation = (value || "slide").toString();
        const allowed = ["slide", "fade", "zoom", "none"];
        return allowed.includes(animation) ? animation : "slide";
    };

    const openItem = ($item, animation) => {
        const $answer = $item.find(".king-addons-faq__answer");
        const $button = $item.find(".king-addons-faq__question");

        clearTimeout($item.data("kngFaqTimer"));
        $item.removeData("kngFaqTimer");

        $button.attr("aria-expanded", "true");
        $answer.attr("aria-hidden", "false");

        if (animation === "none") {
            $answer.show();
            return;
        }

        if (animation === "fade") {
            $answer.stop(true, true).fadeIn(ANIMATION_DURATION);
            return;
        }

        if (animation === "zoom") {
            $answer.stop(true, true).show();
            requestAnimationFrame(() => {
                $item.addClass("is-open");
            });
            return;
        }

        $answer.stop(true, true).slideDown(ANIMATION_DURATION);
    };

    const closeItem = ($item, animation) => {
        const $answer = $item.find(".king-addons-faq__answer");
        const $button = $item.find(".king-addons-faq__question");

        clearTimeout($item.data("kngFaqTimer"));
        $item.removeData("kngFaqTimer");

        $button.attr("aria-expanded", "false");
        $answer.attr("aria-hidden", "true");

        if (animation === "none") {
            $answer.hide();
            return;
        }

        if (animation === "fade") {
            $answer.stop(true, true).fadeOut(ANIMATION_DURATION);
            return;
        }

        if (animation === "zoom") {
            $item.removeClass("is-open");
            const timer = setTimeout(() => {
                if ($button.attr("aria-expanded") === "false") {
                    $answer.hide();
                }
                $item.removeData("kngFaqTimer");
            }, ZOOM_DURATION);
            $item.data("kngFaqTimer", timer);
            return;
        }

        $answer.stop(true, true).slideUp(ANIMATION_DURATION);
    };

    const toggleItem = ($item, single, animation) => {
        const $button = $item.find(".king-addons-faq__question");
        const isOpen = $button.attr("aria-expanded") === "true";

        if (single && !isOpen) {
            $item.siblings(".king-addons-faq__item").each(function () {
                closeItem($(this), animation);
            });
        }

        if (isOpen) {
            closeItem($item, animation);
        } else {
            openItem($item, animation);
        }
    };

    const expandAll = ($list, animation) => {
        $list.find(".king-addons-faq__item").each(function () {
            openItem($(this), animation);
        });
    };

    const collapseAll = ($list, animation) => {
        $list.find(".king-addons-faq__item").each(function () {
            closeItem($(this), animation);
        });
    };

    const initSearch = ($wrapper) => {
        const $search = $wrapper.find(".king-addons-faq__search");
        if (!$search.length) return;

        $search.on("input", function () {
            const query = $(this).val().toString().toLowerCase();
            $wrapper.find(".king-addons-faq__item").each(function () {
                const $item = $(this);
                const text = $item.text().toLowerCase();
                if (text.includes(query)) {
                    $item.show();
                } else {
                    $item.hide();
                }
            });
        });
    };

    const initToggleAll = ($wrapper, $list, animation) => {
        const $toggle = $wrapper.find(".king-addons-faq__toggle-all");
        if (!$toggle.length) return;

        $toggle.on("click", function () {
            const state = $(this).data("state");
            if (state === "collapse") {
                expandAll($list, animation);
                $(this).data("state", "expand").text($(this).data("label-collapse") || window.kngFaqCollapse || "Collapse all");
            } else {
                collapseAll($list, animation);
                $(this).data("state", "collapse").text($(this).data("label-expand") || window.kngFaqExpand || "Expand all");
            }
        });
    };

    const initFaq = ($scope) => {
        const $wrapper = $scope.find(".king-addons-faq");
        if (!$wrapper.length) return;

        const single = $wrapper.data("single") === "yes";
        const animation = normalizeAnimation($wrapper.data("animate"));
        const $list = $wrapper.find(".king-addons-faq__list");

        $wrapper.on("click", ".king-addons-faq__question", function () {
            const $item = $(this).closest(".king-addons-faq__item");
            toggleItem($item, single, animation);
        });

        initSearch($wrapper);
        initToggleAll($wrapper, $list, animation);
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-faq-schema.default",
            function ($scope) {
                initFaq($scope);
            }
        );
    });
})(jQuery);






