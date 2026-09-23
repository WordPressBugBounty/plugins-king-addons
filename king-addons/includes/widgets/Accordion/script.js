"use strict";
(($) => {
    $(window).on("elementor/frontend/init", () => {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-accordion.default",
            ($scope) => {
                elementorFrontend.elementsHandler.addHandler(
                    elementorModules.frontend.handlers.Base.extend({
                        onInit: function () {
                            const $accordion  = $scope.find(".king-addons-advanced-accordion");
                            const $accButtons = $scope.find(".king-addons-acc-button");
                            const $accItems   = $scope.find(".king-addons-accordion-item-wrap");
                            const accordionType    = $accordion.data("accordion-type");
                            const accordionTrigger = $accordion.data("accordion-trigger");
                            const interactionSpeed = +$accordion.data("interaction-speed") * 1000;
                            let   activeIndex      = +$accordion.data("active-index") - 1;
                            $accordion.css("--king-acc-speed", (interactionSpeed || 400) + "ms");

                            // Padding and border on the panel cannot shrink with the grid
                            // row, so they jump off at the end. Move them inside the clip.
                            const preparePanel = ($panel) => {
                                if (!$panel.length || $panel.data("kaBoxReady")) {
                                    return;
                                }
                                let $clip = $panel.children(".king-addons-acc-panel-clip");
                                if (!$clip.length) {
                                    $panel.children().wrapAll('<div class="king-addons-acc-panel-clip"></div>');
                                    $clip = $panel.children(".king-addons-acc-panel-clip");
                                }
                                let $content = $clip.children(".king-addons-acc-panel-content");
                                if (!$content.length) {
                                    $clip.children().wrapAll('<div class="king-addons-acc-panel-content"></div>');
                                    $content = $clip.children(".king-addons-acc-panel-content");
                                }
                                const styles = window.getComputedStyle($panel[0]);
                                $content.css({
                                    paddingTop: styles.paddingTop,
                                    paddingRight: styles.paddingRight,
                                    paddingBottom: styles.paddingBottom,
                                    paddingLeft: styles.paddingLeft,
                                    borderTopWidth: styles.borderTopWidth,
                                    borderRightWidth: styles.borderRightWidth,
                                    borderBottomWidth: styles.borderBottomWidth,
                                    borderLeftWidth: styles.borderLeftWidth,
                                    borderTopStyle: styles.borderTopStyle,
                                    borderRightStyle: styles.borderRightStyle,
                                    borderBottomStyle: styles.borderBottomStyle,
                                    borderLeftStyle: styles.borderLeftStyle,
                                    borderTopColor: styles.borderTopColor,
                                    borderRightColor: styles.borderRightColor,
                                    borderBottomColor: styles.borderBottomColor,
                                    borderLeftColor: styles.borderLeftColor,
                                    borderRadius: styles.borderRadius,
                                    backgroundColor: styles.backgroundColor,
                                    boxShadow: styles.boxShadow,
                                });
                                $panel.css({
                                    padding: 0,
                                    borderWidth: 0,
                                    backgroundColor: "transparent",
                                    boxShadow: "none",
                                    height: "",
                                    overflow: "",
                                    display: "",
                                });
                                $panel.data("kaBoxReady", 1);
                            };

                            $scope.find(".king-addons-acc-panel").each((_, panel) => {
                                preparePanel($(panel));
                            });

                            const openPanel = ($btn) => {
                                const $panel = $btn.next(".king-addons-acc-panel");
                                if (!$panel.length) {
                                    return;
                                }
                                preparePanel($panel);
                                $btn.addClass("king-addons-acc-active");
                                $panel.addClass("king-addons-acc-panel-active");
                            };

                            const closePanel = ($btn) => {
                                const $panel = $btn.next(".king-addons-acc-panel");
                                $btn.removeClass("king-addons-acc-active");
                                $panel.removeClass("king-addons-acc-panel-active");
                            };

                            const togglePanel = ($btn) => {
                                const $panel = $btn.next(".king-addons-acc-panel");
                                if ($panel.hasClass("king-addons-acc-panel-active") && $panel.is(":visible")) {
                                    closePanel($btn);
                                } else {
                                    openPanel($btn);
                                }
                            };

                            // Check URL for "active_panel"
                            const activeTabParamPos = window.location.href.indexOf("active_panel=");
                            if (activeTabParamPos > -1) {
                                activeIndex = +window.location.href
                                    .substring(activeTabParamPos, window.location.href.lastIndexOf("#"))
                                    .replace("active_panel=", "") - 1;
                            }

                            // Helper: toggles a single accordion panel
                            // openPanel / closePanel are defined above.

                            // Accordion: click trigger
                            if (accordionTrigger === "click") {
                                if (accordionType === "accordion") {
                                    $accButtons.on("click", function () {
                                        const currentIndex = $accButtons.index(this);

                                        // Deactivate all except the current
                                        $accButtons.each((i, btn) => {
                                            if (i !== currentIndex) $(btn).removeClass("king-addons-acc-active");
                                        });
                                        $scope.find(".king-addons-acc-panel").each((i, panel) => {
                                            if (i !== currentIndex) {
                                                closePanel($(panel).prev(".king-addons-acc-button"));
                                            }
                                        });

                                        // Toggle the current
                                        togglePanel($(this));
                                    });
                                } else {
                                    // Accordion: toggle each panel independently
                                    $accButtons.each((_, btn) => {
                                        $(btn).on("click", function () {
                                            togglePanel($(this));
                                        });
                                    });
                                }
                                // Open active index if set
                                if (activeIndex > -1) $accButtons.eq(activeIndex).trigger("click");

                                // Accordion: hover trigger
                            } else if (accordionTrigger === "hover") {
                                $accItems.on("mouseenter", function () {
                                    const currentIndex = $accItems.index(this);
                                    const $btn   = $(this).find(".king-addons-acc-button");

                                    openPanel($btn);

                                    // Deactivate others
                                    $accItems.each((i, item) => {
                                        if (i !== currentIndex) {
                                            closePanel($(item).find(".king-addons-acc-button"));
                                        }
                                    });
                                });
                                // Open active index if set
                                if (activeIndex > -1) $accItems.eq(activeIndex).trigger("mouseenter");
                            }

                            // Search input events
                            const $searchInput = $scope.find(".king-addons-acc-search-input");
                            $searchInput.on({
                                focus: () => $scope.addClass("king-addons-acc-search-input-focus"),
                                blur:  () => $scope.removeClass("king-addons-search-form-input-focus"),
                            });

                            // Clear icon
                            const $clearIcon = $scope.find(".king-addons-acc-search-input-wrap i.fa-times");
                            $clearIcon.on("click", () => {
                                $searchInput.val("").trigger("keyup");
                            });

                            // Handle icon box border settings
                            const $iconBoxes = $scope.find(".king-addons-acc-icon-box");
                            const setIconBoxBorders = () => {
                                $iconBoxes.each((_, box) => {
                                    const $box = $(box);
                                    $box.find(".king-addons-acc-icon-box-after").css({
                                        "border-top":    $box.height() / 2 + "px solid transparent",
                                        "border-bottom": $box.height() / 2 + "px solid transparent",
                                    });
                                });
                            };
                            setIconBoxBorders();
                            $(window).on("resize", setIconBoxBorders);

                            // Search filtering
                            const $allInAccordion = $accordion.children();
                            $searchInput.on("keyup", function () {
                                setTimeout(() => {
                                    const query = $(this).val().trim();
                                    if (query.length) {
                                        $clearIcon.css("display", "inline-block");
                                        $allInAccordion.each((_, el) => {
                                            const $item = $(el);
                                            if (!$item.hasClass("king-addons-accordion-item-wrap")) return;

                                            // Match text?
                                            if ($item.text().toUpperCase().indexOf(query.toUpperCase()) === -1) {
                                                // Hide and deactivate
                                                $item.hide();
                                                if (
                                                    $item.find(".king-addons-acc-button").hasClass("king-addons-acc-active") &&
                                                    $item.find(".king-addons-acc-panel").hasClass("king-addons-acc-panel-active")
                                                ) {
                                                    $item
                                                        .find(".king-addons-acc-button")
                                                        .removeClass("king-addons-acc-active");
                                                    $item
                                                        .find(".king-addons-acc-panel")
                                                        .removeClass("king-addons-acc-panel-active");
                                                }
                                            } else {
                                                // Show and activate
                                                $item.show();
                                                const $btn   = $item.find(".king-addons-acc-button");
                                                if (!$btn.hasClass("king-addons-acc-active")) {
                                                    openPanel($btn);
                                                }
                                            }
                                        });
                                    } else {
                                        $clearIcon.css("display", "none");
                                        $allInAccordion.each((_, el) => {
                                            const $item = $(el);
                                            if ($item.hasClass("king-addons-accordion-item-wrap")) {
                                                $item.show();
                                                closePanel($item.find(".king-addons-acc-button"));
                                            }
                                        });
                                    }
                                }, 1000);
                            });
                        },
                    }),
                    { $element: $scope }
                );
            }
        );
    });
})(jQuery);