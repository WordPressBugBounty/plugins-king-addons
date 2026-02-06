"use strict";

(function ($) {
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const parseOptions = (root) => {
        if (!root || !root.dataset) {
            return {};
        }

        try {
            return JSON.parse(root.dataset.options || "{}");
        } catch (e) {
            return {};
        }
    };

    const parseJSON = (value) => {
        if (!value) {
            return {};
        }

        try {
            return JSON.parse(value);
        } catch (e) {
            return {};
        }
    };

    const getClosestIndex = (slides, offsetRatio) => {
        const targetY = window.innerHeight * offsetRatio;
        let closestIndex = 0;
        let closestDistance = Number.POSITIVE_INFINITY;

        slides.forEach((slide, index) => {
            const rect = slide.getBoundingClientRect();
            const distance = Math.abs(rect.top - targetY);
            if (distance < closestDistance) {
                closestDistance = distance;
                closestIndex = index;
            }
        });

        return closestIndex;
    };

    const getLottieLibrary = () => {
        if (typeof lottie !== "undefined") {
            return lottie;
        }

        if (typeof window.lottie !== "undefined") {
            return window.lottie;
        }

        if (typeof window.Lottie !== "undefined") {
            return window.Lottie;
        }

        return null;
    };

    const getScrollMarginPx = (root) => {
        if (!root) {
            return 0;
        }

        const rawValue = window
            .getComputedStyle(root)
            .getPropertyValue("--kng-sly-scroll-margin")
            .trim();

        if (!rawValue) {
            return 0;
        }

        const match = rawValue.match(/^(-?[\d.]+)(px|vh|vw|rem|em)?$/);
        if (!match) {
            return 0;
        }

        const value = Number(match[1]);
        if (!Number.isFinite(value)) {
            return 0;
        }

        const unit = match[2] || "px";
        if (unit === "vh") {
            return (window.innerHeight * value) / 100;
        }

        if (unit === "vw") {
            return (window.innerWidth * value) / 100;
        }

        if (unit === "rem") {
            const base = Number(
                window.getComputedStyle(document.documentElement).fontSize || 16
            );
            return base * value;
        }

        if (unit === "em") {
            const base = Number(window.getComputedStyle(root).fontSize || 16);
            return base * value;
        }

        return value;
    };

    const initScrollytelling = (root) => {
        if (!root || root.dataset.scrollyInit === "yes") {
            return;
        }

        const slides = Array.from(root.querySelectorAll(".king-addons-scrollytelling__slide"));
        if (!slides.length) {
            return;
        }

        root.dataset.scrollyInit = "yes";

        const dots = slides.map((slide) => slide.querySelector(".king-addons-scrollytelling__dot"));
        const options = parseOptions(root);
        const offset = clamp(Number(options.offset) || 40, 10, 80);
        const offsetRatio = offset / 100;
        const clickableDots = !!options.clickableDots;
        const updateHash = !!options.updateHash;
        const readHash = !!options.readHash;
        const sticky = !!options.sticky;
        const snapMode = options.snap || "off";
        const snapDuration = clamp(Number(options.snapDuration) || 420, 150, 2000);
        const lottieActiveOnly = options.lottieActiveOnly !== false;
        const prefersReduced =
            window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        const isEditor =
            window.elementorFrontend &&
            elementorFrontend.isEditMode &&
            elementorFrontend.isEditMode();
        const mediaPanel = root.querySelector(".king-addons-scrollytelling__media-sticky");
        const shouldLazy = !isEditor;

        let activeIndex = -1;
        let ticking = false;
        let scrollMarginPx = getScrollMarginPx(root);
        let snapTimeout = null;
        let snapFrame = null;
        let lottieLib = getLottieLibrary();

        const lottieInstances = new Map();
        let allLottiesInitialized = false;

        const parseNumber = (value) => {
            if (value === "" || value === null || value === undefined) {
                return null;
            }

            const numberValue = Number(value);
            return Number.isFinite(numberValue) ? numberValue : null;
        };

        const isWidgetInView = () => {
            const rect = root.getBoundingClientRect();
            return rect.bottom > 0 && rect.top < window.innerHeight;
        };

        const updateScrollMargin = () => {
            scrollMarginPx = getScrollMarginPx(root);
        };

        const getSlideScrollY = (slide) => {
            const rect = slide.getBoundingClientRect();
            return window.pageYOffset + rect.top - scrollMarginPx;
        };

        const scrollToPosition = (targetY, duration) => {
            const maxScroll = Math.max(
                0,
                document.documentElement.scrollHeight - window.innerHeight
            );
            const target = clamp(targetY, 0, maxScroll);

            if (snapFrame) {
                window.cancelAnimationFrame(snapFrame);
                snapFrame = null;
            }

            if (duration <= 0 || prefersReduced) {
                window.scrollTo(0, target);
                return;
            }

            const startY = window.pageYOffset;
            const distance = target - startY;
            const startTime = window.performance.now();

            const animate = (now) => {
                const progress = clamp((now - startTime) / duration, 0, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                window.scrollTo(0, startY + distance * eased);

                if (progress < 1) {
                    snapFrame = window.requestAnimationFrame(animate);
                }
            };

            snapFrame = window.requestAnimationFrame(animate);
        };

        const scrollToSlide = (slide, duration) => {
            if (!slide) {
                return;
            }

            scrollToPosition(getSlideScrollY(slide), duration);
        };

        const loadIframesInElement = (element) => {
            if (!element) {
                return;
            }

            element.querySelectorAll("iframe[data-src]").forEach((iframe) => {
                if (iframe.dataset.loaded === "yes") {
                    return;
                }

                const src = iframe.getAttribute("data-src");
                if (!src) {
                    return;
                }

                iframe.setAttribute("src", src);
                iframe.dataset.loaded = "yes";
            });
        };

        const preloadIframesAround = (index) => {
            if (!shouldLazy) {
                slides.forEach((slide) => loadIframesInElement(slide));
                loadIframesInElement(mediaPanel);
                return;
            }

            const start = Math.max(0, index - 1);
            const end = Math.min(slides.length - 1, index + 1);
            for (let i = start; i <= end; i += 1) {
                loadIframesInElement(slides[i]);
            }

            loadIframesInElement(mediaPanel);
        };

        const playLottieInstance = (entry) => {
            const start = parseNumber(entry.settings.segmentStart);
            const end = parseNumber(entry.settings.segmentEnd);

            if (start !== null && end !== null && end > start) {
                entry.animation.playSegments([start, end], true);
            } else {
                entry.animation.play();
            }
        };

        const initLottieContainer = (container, index) => {
            if (!container || container.dataset.lottieInit === "yes" || !lottieLib) {
                return;
            }

            const jsonUrl = container.dataset.jsonUrl || "";
            if (!jsonUrl) {
                return;
            }

            const settings = parseJSON(container.dataset.settings || "{}");
            const loop = settings.loop === true || settings.loop === "yes";
            const speed = Number(settings.speed) > 0 ? Number(settings.speed) : 1;

            const animation = lottieLib.loadAnimation({
                container,
                path: jsonUrl,
                renderer: settings.renderer || "svg",
                loop,
                autoplay: !lottieActiveOnly && !prefersReduced,
            });

            animation.setSpeed(speed);
            container.dataset.lottieInit = "yes";
            lottieInstances.set(container, {
                animation,
                settings,
                index,
                isActive: false,
            });

            if (prefersReduced || lottieActiveOnly) {
                animation.pause();
            }
        };

        const initLottieInElement = (element, index) => {
            if (!element) {
                return;
            }

            const container = element.querySelector(".king-addons-scrollytelling__lottie");
            if (!container) {
                return;
            }

            initLottieContainer(container, index);
        };

        const initNearbyLotties = (index) => {
            if (!lottieLib) {
                return;
            }

            if (!lottieActiveOnly) {
                if (!allLottiesInitialized) {
                    slides.forEach((slide, slideIndex) => initLottieInElement(slide, slideIndex));
                    allLottiesInitialized = true;
                }
                initLottieInElement(mediaPanel, index);
                return;
            }

            const start = Math.max(0, index - 1);
            const end = Math.min(slides.length - 1, index + 1);
            for (let i = start; i <= end; i += 1) {
                initLottieInElement(slides[i], i);
            }

            initLottieInElement(mediaPanel, index);
        };

        const updateLottiePlayback = (index) => {
            if (!lottieLib) {
                return;
            }

            lottieInstances.forEach((entry) => {
                const shouldBeActive = entry.index === index;

                if (prefersReduced) {
                    entry.animation.pause();
                    entry.isActive = shouldBeActive;
                    return;
                }

                if (lottieActiveOnly) {
                    if (entry.isActive === shouldBeActive) {
                        return;
                    }

                    entry.isActive = shouldBeActive;
                    if (shouldBeActive) {
                        playLottieInstance(entry);
                    } else {
                        entry.animation.pause();
                    }
                    return;
                }

                if (!entry.isActive) {
                    entry.isActive = true;
                }

                if (entry.settings.autoplay !== "no") {
                    entry.animation.play();
                }
            });
        };

        const handleMediaUpdate = (index) => {
            preloadIframesAround(index);
            initNearbyLotties(index);
            updateLottiePlayback(index);
        };

        const getMediaMarkup = (index) => {
            if (!slides[index]) {
                return "";
            }

            const media = slides[index].querySelector(".king-addons-scrollytelling__media");
            if (!media) {
                return "";
            }

            return media.innerHTML.trim();
        };

        const updateStickyMedia = (index) => {
            if (!sticky || !mediaPanel) {
                return;
            }

            const markup = getMediaMarkup(index);
            if (!markup || mediaPanel.dataset.index === String(index)) {
                return;
            }

            mediaPanel.innerHTML = markup;
            mediaPanel.dataset.index = String(index);
            loadIframesInElement(mediaPanel);
            initLottieInElement(mediaPanel, index);
        };

        const setActive = (nextIndex, shouldUpdateHash = true) => {
            const safeIndex = clamp(nextIndex, 0, slides.length - 1);
            if (safeIndex === activeIndex) {
                return;
            }

            activeIndex = safeIndex;

            slides.forEach((slide, index) => {
                slide.classList.toggle("is-active", index === activeIndex);
                slide.classList.toggle("is-completed", index < activeIndex);
                slide.classList.toggle("is-upcoming", index > activeIndex);

                const dot = dots[index];
                if (!dot) {
                    return;
                }

                if (index === activeIndex) {
                    dot.setAttribute("aria-current", "step");
                } else {
                    dot.removeAttribute("aria-current");
                }
            });

            updateStickyMedia(activeIndex);
            handleMediaUpdate(activeIndex);

            if (!updateHash || isEditor || !shouldUpdateHash || !isWidgetInView()) {
                return;
            }

            const anchor = slides[activeIndex].dataset.anchor || "";
            if (!anchor) {
                return;
            }

            const newHash = `#${encodeURIComponent(anchor)}`;
            if (window.location.hash !== newHash) {
                window.history.replaceState(null, "", newHash);
            }
        };

        const updateFromScroll = () => {
            const nextIndex = getClosestIndex(slides, offsetRatio);
            setActive(nextIndex);
        };

        const scheduleUpdate = () => {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(() => {
                updateFromScroll();
                ticking = false;
            });
        };

        const scheduleSnap = () => {
            if (snapMode === "off" || prefersReduced || isEditor) {
                return;
            }

            if (!isWidgetInView()) {
                return;
            }

            if (snapTimeout) {
                window.clearTimeout(snapTimeout);
            }

            snapTimeout = window.setTimeout(() => {
                if (!isWidgetInView()) {
                    return;
                }

                const nextIndex = getClosestIndex(slides, offsetRatio);
                const target = slides[nextIndex];
                if (!target) {
                    return;
                }

                const targetY = getSlideScrollY(target);
                const distance = Math.abs(window.pageYOffset - targetY);
                if (snapMode === "soft" && distance > window.innerHeight * 0.35) {
                    return;
                }

                scrollToPosition(targetY, snapDuration);
            }, 120);
        };

        const onScroll = () => {
            scheduleUpdate();
            scheduleSnap();
        };

        window.addEventListener("scroll", onScroll, { passive: true });
        window.addEventListener("resize", () => {
            updateScrollMargin();
            scheduleUpdate();
        });

        updateScrollMargin();
        scheduleUpdate();

        if ("IntersectionObserver" in window) {
            const observer = new IntersectionObserver(scheduleUpdate, {
                root: null,
                rootMargin: "0px",
                threshold: 0,
            });

            slides.forEach((slide) => observer.observe(slide));
        }

        if (clickableDots) {
            dots.forEach((dot, index) => {
                if (!dot) {
                    return;
                }

                dot.addEventListener("click", (event) => {
                    event.preventDefault();
                    const target = slides[index];
                    if (!target) {
                        return;
                    }

                    scrollToSlide(target, snapDuration);
                });
            });
        }

        if (readHash && !isEditor && window.location.hash) {
            const hash = decodeURIComponent(window.location.hash.replace("#", ""));
            const targetIndex = slides.findIndex(
                (slide) => (slide.dataset.anchor || "") === hash
            );

            if (targetIndex >= 0) {
                window.setTimeout(() => {
                    scrollToSlide(slides[targetIndex], snapDuration);
                    setActive(targetIndex, false);
                }, 100);
            }
        }

        if (root.querySelector(".king-addons-scrollytelling__lottie") && !lottieLib) {
            let attempts = 0;
            const timer = window.setInterval(() => {
                attempts += 1;
                lottieLib = getLottieLibrary();
                if (lottieLib || attempts >= 20) {
                    window.clearInterval(timer);
                    if (lottieLib) {
                        initNearbyLotties(activeIndex >= 0 ? activeIndex : 0);
                        updateLottiePlayback(activeIndex >= 0 ? activeIndex : 0);
                    }
                }
            }, 200);
        }
    };

    const initScrollytellingWidgets = ($scope) => {
        $scope.find(".king-addons-scrollytelling").each(function () {
            initScrollytelling(this);
        });
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-scrollytelling-slides.default",
            function ($scope) {
                initScrollytellingWidgets($scope);
            }
        );
    });
})(jQuery);
