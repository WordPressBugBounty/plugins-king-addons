"use strict";
(function ($) {
    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-content-ticker.default",
            function ($scope) {
                // Register a new handler extending Elementor’s Base handler
                elementorFrontend.elementsHandler.addHandler(
                    elementorModules.frontend.handlers.Base.extend({
                        onInit() {
                            const $element = this.$element;

                            const $contentTickerSlider = $element.find(".king-addons-ticker-slider"),
                                $contentTickerMarquee = $element.find(".king-addons-ticker-marquee"),
                                marqueeData = $contentTickerMarquee.data("options"),
                                sliderClass = $element.attr("class"),
                                dataSlideEffect = $contentTickerSlider.attr("data-slide-effect");

                            // Helper to capture a single digit at the end of a matched pattern
                            const getClassNumber = (regex, fallback) => {
                                const match = sliderClass.match(regex);
                                return match ? parseInt(match[1], 10) : fallback;
                            };

                            // Determine how many columns to use in different breakpoints
                            const sliderColumnsDesktop = getClassNumber(/king-addons-ticker-slider-columns-(\d)/, 2);
                            const sliderColumnsWideScreen = getClassNumber(/columns--widescreen(\d)/, sliderColumnsDesktop);
                            const sliderColumnsLaptop = getClassNumber(/columns--laptop(\d)/, sliderColumnsDesktop);
                            const sliderColumnsTablet = getClassNumber(/columns--tablet(\d)/, 2);
                            const sliderColumnsTabletExtra = getClassNumber(/columns--tablet_extra(\d)/, sliderColumnsTablet);
                            const sliderColumnsMobile = getClassNumber(/columns--mobile(\d)/, 1);
                            // Mobile extra should fall back to mobile, not tablet.
                            const sliderColumnsMobileExtra = getClassNumber(/columns--mobile_extra(\d)/, sliderColumnsMobile);

                            // Slides to scroll logic if the slider effect is "hr-slide"
                            const sliderSlidesToScroll =
                                dataSlideEffect === "hr-slide"
                                    ? getClassNumber(/king-addons-ticker-slides-to-scroll-(\d)/, 1)
                                    : 1;

                            // Check if slide effect is typing or fade
                            const isTypingOrFade = dataSlideEffect === "typing" || dataSlideEffect === "fade";

                            // Short helper to decide slidesToShow and slidesToScroll
                            const getSlidesToShow = columns => (isTypingOrFade ? 1 : columns);
                            const getSlidesToScroll = columns => (sliderSlidesToScroll > columns ? 1 : sliderSlidesToScroll);

                            const isMeasurable = ($el) => {
                                if (!$el || !$el.length) {
                                    return false;
                                }
                                // :visible can be true even with width 0 depending on CSS.
                                return $el.is(":visible") && $el.outerWidth() > 0;
                            };

                            // Slick settings with breakpoints
                            const normalizeBool = (value) => {
                                return value === true || value === 1 || value === "1" || value === "true" || value === "yes";
                            };

                            const slickOptionsFromAttr = (() => {
                                const raw = $contentTickerSlider.attr("data-slick-options");
                                if (!raw) {
                                    return {};
                                }

                                try {
                                    const parsed = JSON.parse(raw);
                                    if (parsed && typeof parsed === "object") {
                                        // Normalize Elementor switcher values.
                                        if ("pauseOnHover" in parsed) {
                                            parsed.pauseOnHover = normalizeBool(parsed.pauseOnHover);
                                        }
                                        if ("autoplay" in parsed) {
                                            parsed.autoplay = normalizeBool(parsed.autoplay);
                                        }
                                        if ("infinite" in parsed) {
                                            parsed.infinite = normalizeBool(parsed.infinite);
                                        }
                                        if ("rtl" in parsed) {
                                            parsed.rtl = normalizeBool(parsed.rtl);
                                        }
                                        if ("arrows" in parsed) {
                                            parsed.arrows = normalizeBool(parsed.arrows);
                                        }
                                        if ("vertical" in parsed) {
                                            parsed.vertical = normalizeBool(parsed.vertical);
                                        }

                                        if ("autoplaySpeed" in parsed) {
                                            parsed.autoplaySpeed = parseInt(parsed.autoplaySpeed, 10) || 0;
                                        }
                                        if ("speed" in parsed) {
                                            parsed.speed = parseInt(parsed.speed, 10) || 0;
                                        }

                                        return parsed;
                                    }
                                } catch (e) {
                                    // Ignore invalid JSON.
                                }
                                return {};
                            })();

                            const initOrRefreshSlick = () => {
                                if (!$contentTickerSlider.length) {
                                    return;
                                }

                                // If initialized while hidden, Slick can calculate wrong widths.
                                // Re-init only when element has a measurable width.
                                if (!isMeasurable($contentTickerSlider)) {
                                    return;
                                }

                                // Prevent duplicate initialization when Elementor re-renders.
                                if ($contentTickerSlider.hasClass("slick-initialized")) {
                                    try {
                                        $contentTickerSlider.slick("setPosition");
                                    } catch (e) {
                                        // ignore
                                    }
                                    return;
                                }

                                $contentTickerSlider.slick({
                                    ...slickOptionsFromAttr,
                                    appendArrows: $element.find(".king-addons-ticker-slider-controls"),
                                    // Base (>= 2400px) uses widescreen columns when provided.
                                    slidesToShow: getSlidesToShow(sliderColumnsWideScreen),
                                    slidesToScroll: getSlidesToScroll(sliderColumnsWideScreen),
                                    fade: isTypingOrFade,
                                    responsive: [
                                        {
                                            breakpoint: 2399,
                                            settings: {
                                                slidesToShow: getSlidesToShow(sliderColumnsDesktop),
                                                slidesToScroll: getSlidesToScroll(sliderColumnsDesktop),
                                                fade: isTypingOrFade,
                                            },
                                        },
                                        {
                                            breakpoint: 1221,
                                            settings: {
                                                slidesToShow: getSlidesToShow(sliderColumnsLaptop),
                                                slidesToScroll: getSlidesToScroll(sliderColumnsLaptop),
                                                fade: isTypingOrFade,
                                            },
                                        },
                                        {
                                            breakpoint: 1200,
                                            settings: {
                                                slidesToShow: getSlidesToShow(sliderColumnsTabletExtra),
                                                slidesToScroll: getSlidesToScroll(sliderColumnsTabletExtra),
                                                fade: isTypingOrFade,
                                            },
                                        },
                                        {
                                            breakpoint: 1024,
                                            settings: {
                                                slidesToShow: getSlidesToShow(sliderColumnsTablet),
                                                slidesToScroll: getSlidesToScroll(sliderColumnsTablet),
                                                fade: isTypingOrFade,
                                            },
                                        },
                                        {
                                            breakpoint: 880,
                                            settings: {
                                                slidesToShow: getSlidesToShow(sliderColumnsMobileExtra),
                                                slidesToScroll: getSlidesToScroll(sliderColumnsMobileExtra),
                                                fade: isTypingOrFade,
                                            },
                                        },
                                        {
                                            breakpoint: 768,
                                            settings: {
                                                slidesToShow: getSlidesToShow(sliderColumnsMobile),
                                                slidesToScroll: getSlidesToScroll(sliderColumnsMobile),
                                                fade: isTypingOrFade,
                                            },
                                        },
                                    ],
                                });

                                // Force a layout pass right after init.
                                try {
                                    $contentTickerSlider.slick("setPosition");
                                } catch (e) {
                                    // ignore
                                }
                            };

                            const retryInit = (attempt = 0) => {
                                if (attempt > 40) {
                                    return;
                                }
                                initOrRefreshSlick();
                                if (!$contentTickerSlider.hasClass("slick-initialized")) {
                                    setTimeout(() => retryInit(attempt + 1), 50);
                                }
                            };

                            // If slider is already initialized (e.g. rendered by cached HTML), ensure it positions correctly.
                            if ($contentTickerSlider.hasClass("slick-initialized")) {
                                try {
                                    $contentTickerSlider.slick("setPosition");
                                } catch (e) {
                                    // ignore
                                }
                            } else {
                                // Try immediately, then retry while the widget becomes visible.
                                retryInit(0);
                            }

                            // Refresh on image load (common cause of wrong widths on frontend).
                            $contentTickerSlider.find("img").one("load", function () {
                                if ($contentTickerSlider.hasClass("slick-initialized")) {
                                    try {
                                        $contentTickerSlider.slick("setPosition");
                                    } catch (e) {
                                        // ignore
                                    }
                                } else {
                                    retryInit(0);
                                }
                            }).each(function () {
                                if (this.complete) {
                                    $(this).trigger("load");
                                }
                            });

                            // Also refresh on global events.
                            $(window).on("load resize orientationchange", function () {
                                if ($contentTickerSlider.hasClass("slick-initialized")) {
                                    try {
                                        $contentTickerSlider.slick("setPosition");
                                    } catch (e) {
                                        // ignore
                                    }
                                } else {
                                    retryInit(0);
                                }
                            });

                            document.addEventListener("visibilitychange", function () {
                                if (!document.hidden) {
                                    if ($contentTickerSlider.hasClass("slick-initialized")) {
                                        try {
                                            $contentTickerSlider.slick("setPosition");
                                        } catch (e) {
                                            // ignore
                                        }
                                    } else {
                                        retryInit(0);
                                    }
                                }
                            });

                            // Initialize marquee
                            if ($contentTickerMarquee.length && marqueeData) {
                                $contentTickerMarquee.marquee(marqueeData);
                            }

                            // If marquee is hidden, remove the hidden class
                            if ($element.find(".king-addons-marquee-hidden").length) {
                                $element
                                    .find(".king-addons-ticker-marquee")
                                    .removeClass("king-addons-marquee-hidden");
                            }
                        },
                    }),
                    {
                        $element: $scope,
                    }
                );
            }
        );
    });
})(jQuery);