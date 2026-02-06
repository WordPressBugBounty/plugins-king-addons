"use strict";

(function ($) {
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const parseSettings = (root) => {
        try {
            return JSON.parse(root.dataset.settings || "{}");
        } catch (e) {
            return null;
        }
    };

    const applyCssVars = (root, settings) => {
        const columns = settings?.layout?.columns || 3;
        const gap = settings?.layout?.gap || 0;
        const duration = settings?.animation?.transitionDuration || 800;
        const easing = settings?.animation?.easing || "ease-in-out";
        const imageFit = settings?.visual?.imageFit || "cover";
        const scaleHover = settings?.visual?.imageScaleHover || 1.05;
        const imageOpacity = settings?.visual?.imageOpacity ?? 1;
        const imageOpacityHover =
            settings?.visual?.imageOpacityHover ?? settings?.visual?.imageOpacity ?? 1;

        root.style.setProperty("--rit-columns", columns);
        root.style.setProperty("--rit-gap", `${gap}px`);
        root.style.setProperty("--rit-transition-duration", `${duration}ms`);
        root.style.setProperty("--rit-easing", easing);
        root.style.setProperty("--rit-image-fit", imageFit);
        root.style.setProperty("--rit-image-scale-hover", scaleHover);
        root.style.setProperty("--rit-image-opacity", imageOpacity);
        root.style.setProperty("--rit-image-opacity-hover", imageOpacityHover);
    };

    const getAlternateNext = (current, length) => {
        const sequence = [];
        for (let i = 0; i < length; i++) {
            const left = i;
            const right = length - 1 - i;
            if (left < length) {
                sequence.push(left);
            }
            if (right !== left && right >= 0) {
                sequence.push(right);
            }
            if (sequence.length >= length) {
                break;
            }
        }
        const currentIndex = sequence.indexOf(current);
        if (currentIndex === -1) {
            return (current + 1) % length;
        }
        return sequence[(currentIndex + 1) % sequence.length];
    };

    const getNextIndex = (current, mode, length) => {
        if (length <= 1) {
            return current;
        }

        switch (mode) {
            case "reverse":
                return (current - 1 + length) % length;
            case "alternate":
                return getAlternateNext(current, length);
            case "random": {
                let next = current;
                const safety = 6;
                for (let i = 0; i < safety && next === current; i++) {
                    next = Math.floor(Math.random() * length);
                }
                return next;
            }
            case "sequential":
            default:
                return (current + 1) % length;
        }
    };

    const setLayerImage = (layer, image, mask) => {
        if (!layer) {
            return;
        }
        const img = layer.querySelector("img");
        if (img) {
            img.src = image.url;
            img.alt = image.alt || "";
        }
        if (mask) {
            layer.style.clipPath = mask;
            layer.style.webkitClipPath = mask;
        }
    };

    const updateCaption = (captionEl, image, showDescription) => {
        if (!captionEl) {
            return;
        }
        const titleEl = captionEl.querySelector(
            ".king-addons-rotating-image-tiles__caption-title"
        );
        const descriptionEl = captionEl.querySelector(
            ".king-addons-rotating-image-tiles__caption-description"
        );

        if (titleEl) {
            titleEl.textContent = image.title || "";
        }
        if (descriptionEl) {
            descriptionEl.textContent = showDescription ? image.description || "" : "";
            descriptionEl.style.display =
                showDescription && image.description ? "block" : "none";
        }
    };

    const swapLayers = (state, nextIndex, settings, images, mask) => {
        const nextImage = images[nextIndex];
        if (!nextImage) {
            return;
        }

        setLayerImage(state.layers.next, nextImage, mask);
        state.tile.classList.add("is-animating");
        state.layers.next.classList.add("is-entering");
        state.layers.current.classList.add("is-leaving");

        const duration = settings?.animation?.transitionDuration || 800;

        window.setTimeout(() => {
            state.layers.next.classList.remove("is-entering");
            state.layers.current.classList.remove("is-leaving");

            state.layers.current.classList.remove("is-current");
            state.layers.next.classList.remove("is-next");

            state.layers.current.classList.add("is-next");
            state.layers.next.classList.add("is-current");

            const temp = state.layers.current;
            state.layers.current = state.layers.next;
            state.layers.next = temp;

            state.currentIndex = nextIndex;
            state.transitionsDone += 1;

            state.tile.classList.remove("is-animating");

            if (state.captionEl) {
                const showDescription =
                    settings?.interaction?.captionSource === "title_description";
                updateCaption(state.captionEl, nextImage, showDescription);
            }
        }, duration);
    };

    const openLightbox = (image) => {
        if (!image?.url) {
            return;
        }

        if (window.elementorFrontend?.utils?.lightbox) {
            window.elementorFrontend.utils.lightbox.showImages([
                {
                    url: image.url,
                    title: image.title || "",
                    description: image.description || "",
                },
            ]);
            return;
        }

        window.open(image.url, "_blank", "noopener");
    };

    const handleClickAction = (action, image) => {
        if (action === "open_link" && image?.link?.url) {
            const target = image.link.is_external ? "_blank" : "_self";
            const relParts = [];
            if (image.link.nofollow) {
                relParts.push("nofollow");
            }
            if (image.link.is_external) {
                relParts.push("noopener", "noreferrer");
            }
            const rel = relParts.join(" ");
            const anchor = document.createElement("a");
            anchor.href = image.link.url;
            anchor.target = target;
            if (rel) {
                anchor.rel = rel;
            }
            anchor.style.display = "none";
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
            return;
        }

        if (action === "lightbox") {
            openLightbox(image);
        }
    };

    const buildMask = (tileSettings, settings) => {
        const useGlobal = settings?.circle?.useGlobal === "yes";
        const radius = useGlobal
            ? clamp(settings?.circle?.radius ?? 40, 5, 100)
            : clamp(tileSettings.radius ?? 40, 5, 100);
        const centerX = clamp(tileSettings.centerX ?? 50, 0, 100);
        const centerY = clamp(tileSettings.centerY ?? 50, 0, 100);
        return `circle(${radius}% at ${centerX}% ${centerY}%)`;
    };

    const buildTileState = (tile, index, settings, images) => {
        const layers = {
            current: tile.querySelector(".king-addons-rotating-image-tiles__image-layer.is-current"),
            next: tile.querySelector(".king-addons-rotating-image-tiles__image-layer.is-next"),
        };

        const captionEl = tile.querySelector(".king-addons-rotating-image-tiles__caption");
        const initialIndex = clamp(
            parseInt(tile.dataset.initialIndex, 10) || 0,
            0,
            Math.max(images.length - 1, 0)
        );
        const tileData = settings.tiles?.[index] || {};
        const hoverScale = parseFloat(tile.dataset.hoverScale || tileData.hoverScale || 1.05);
        const mask = buildMask(
            {
                centerX: parseFloat(tile.dataset.centerX || tileData.centerX || 50),
                centerY: parseFloat(tile.dataset.centerY || tileData.centerY || 50),
                radius: parseFloat(tile.dataset.radius || tileData.radius || 40),
            },
            settings
        );

        setLayerImage(layers.current, images[initialIndex], mask);
        setLayerImage(layers.next, images[initialIndex], mask);
        if (captionEl) {
            const showDescription = settings?.interaction?.captionSource === "title_description";
            updateCaption(captionEl, images[initialIndex], showDescription);
        }

        tile.style.setProperty("--rit-hover-scale", hoverScale);

        return {
            tile,
            layers,
            captionEl,
            currentIndex: initialIndex,
            transitionsDone: 0,
            delay: parseInt(tile.dataset.delay || "0", 10) || 0,
            paused: false,
            hoverScale,
            timers: {
                interval: null,
                delay: null,
            },
        };
    };

    const clearTimers = (state) => {
        if (state.timers.interval) {
            window.clearInterval(state.timers.interval);
            state.timers.interval = null;
        }
        if (state.timers.delay) {
            window.clearTimeout(state.timers.delay);
            state.timers.delay = null;
        }
    };

    const initBehavior = (root, states, settings, images) => {
        const mode = settings?.animation?.mode || "sequential";
        const behavior = settings?.animation?.behavior || "autoplay";
        const interval = settings?.animation?.interval || 2500;
        const loop = settings?.animation?.loop !== "no";
        const pauseOnHover = settings?.animation?.pauseOnHover === "yes";
        const tileEffect = settings?.hover?.tileEffect || "none";

        if (tileEffect && tileEffect !== "none") {
            states.forEach((state) => {
                state.tile.classList.add(`is-hover-${tileEffect}`);
            });
        }

        const stepTile = (state) => {
            if (state.paused) {
                return;
            }

            if (!loop && state.transitionsDone >= Math.max(images.length - 1, 0)) {
                return;
            }

            const nextIndex = getNextIndex(state.currentIndex, mode, images.length);
            if (nextIndex === state.currentIndex) {
                return;
            }

            const tileIndex = parseInt(state.tile.dataset.tileIndex || "0", 10) || 0;
            const mask = buildMask(settings.tiles?.[tileIndex] || {}, settings);
            swapLayers(state, nextIndex, settings, images, mask);
        };

        const startAutoplay = () => {
            states.forEach((state) => {
                clearTimers(state);
                const tick = () => stepTile(state);
                if (state.delay > 0) {
                    state.timers.delay = window.setTimeout(() => {
                        tick();
                        state.timers.interval = window.setInterval(tick, interval);
                    }, state.delay);
                } else {
                    tick();
                    state.timers.interval = window.setInterval(tick, interval);
                }
            });
        };

        const stopAutoplay = () => {
            states.forEach((state) => clearTimers(state));
        };

        if (behavior === "autoplay") {
            startAutoplay();

            if (pauseOnHover) {
                root.addEventListener("mouseenter", () => {
                    states.forEach((state) => {
                        state.paused = true;
                    });
                });
                root.addEventListener("mouseleave", () => {
                    states.forEach((state) => {
                        state.paused = false;
                    });
                });
            }
        } else if (behavior === "on_hover") {
            const onEnter = () => {
                startAutoplay();
            };
            const onLeave = () => {
                stopAutoplay();
            };
            root.addEventListener("mouseenter", onEnter);
            root.addEventListener("mouseleave", onLeave);
        } else if (behavior === "on_click") {
            stopAutoplay();
            states.forEach((state) => {
                state.tile.addEventListener("click", () => {
                    stepTile(state);
                });
            });
        }

        return () => {
            stopAutoplay();
        };
    };

    const initInteractions = (states, settings, images) => {
        const clickAction = settings?.interaction?.clickAction || "none";
        if (clickAction === "none") {
            return () => {};
        }

        const listeners = [];
        states.forEach((state) => {
            const handler = () => {
                const image = images[state.currentIndex] || images[0];
                handleClickAction(clickAction, image);
            };
            state.tile.addEventListener("click", handler);
            listeners.push({ el: state.tile, handler });
        });

        return () => {
            listeners.forEach(({ el, handler }) => {
                el.removeEventListener("click", handler);
            });
        };
    };

    const initWidget = ($scope) => {
        const root = $scope[0]?.querySelector(".king-addons-rotating-image-tiles");
        if (!root) {
            return;
        }

        const settings = parseSettings(root);
        if (!settings || !Array.isArray(settings.images) || !settings.images.length) {
            return;
        }

        applyCssVars(root, settings);

        const tiles = Array.from(
            root.querySelectorAll(".king-addons-rotating-image-tiles__tile")
        );
        if (!tiles.length) {
            return;
        }

        const states = tiles.map((tile, index) =>
            buildTileState(tile, index, settings, settings.images)
        );

        const disableAnimationOnMobile =
            settings?.animation?.disableOnMobile === "yes" &&
            (window.innerWidth || 0) < 768;

        const destroyBehavior = disableAnimationOnMobile
            ? () => {}
            : initBehavior(root, states, settings, settings.images);
        const destroyInteractions = initInteractions(states, settings, settings.images);

        $scope.on("destroy", () => {
            destroyBehavior();
            destroyInteractions();
            states.forEach((state) => clearTimers(state));
        });
    };

    $(window).on("elementor/frontend/init", () => {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-rotating-image-tiles.default",
            ($scope) => {
                initWidget($scope);
            }
        );
    });
})(jQuery);






