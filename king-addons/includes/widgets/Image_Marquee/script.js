"use strict";

(function ($) {
    const initTrack = (track, rootDataset) => {
        const mergedData = Object.assign({}, rootDataset, track.dataset || {});
        const orientation = mergedData.orientation || "horizontal";
        const direction = mergedData.direction || "right_to_left";
        const pauseHover = mergedData.pauseHover === "yes";
        const pauseTouch = mergedData.pauseTouch === "yes";
        const reverseOnHover = mergedData.reverseHover === "yes";
        const speedRaw = parseFloat(mergedData.speed || "50");
        const speed = Number.isFinite(speedRaw) ? speedRaw : 50;

        const firstList = track.querySelector(".king-addons-image-marquee__list");
        if (!firstList) {
            return null;
        }

        const baseSign =
            orientation === "vertical"
                ? direction === "top_to_bottom"
                    ? 1
                    : -1
                : direction === "left_to_right"
                  ? 1
                  : -1;
        let currentSign = baseSign;

        const getBaseSize = () =>
            orientation === "vertical" ? firstList.scrollHeight : firstList.scrollWidth;

        let baseSize = getBaseSize();
        if (!baseSize) {
            return null;
        }

        let offset = currentSign > 0 ? -baseSize : 0;
        let paused = false;
        let rafId = null;

        const applyTransform = () => {
            if (orientation === "vertical") {
                track.style.transform = `translate3d(0, ${offset}px, 0)`;
            } else {
                track.style.transform = `translate3d(${offset}px, 0, 0)`;
            }
        };

        const step = (timestamp) => {
            const now = timestamp || performance.now();
            if (!step.last) {
                step.last = now;
            }
            const delta = (now - step.last) / 1000;
            step.last = now;

            if (!paused) {
                const pxPerSecond = Math.max(5, speed);
                offset += currentSign * pxPerSecond * delta;

                if (currentSign < 0 && Math.abs(offset) >= baseSize) {
                    offset += baseSize;
                } else if (currentSign > 0 && offset >= 0) {
                    offset -= baseSize;
                }

                applyTransform();
            }

            rafId = requestAnimationFrame(step);
        };

        const handleResize = () => {
            baseSize = getBaseSize();
            if (baseSize <= 0) {
                return;
            }
            offset = currentSign > 0 ? Math.max(offset, -baseSize) : Math.min(offset, 0);
            applyTransform();
        };

        if (pauseHover) {
            track.addEventListener("mouseenter", () => {
                paused = true;
            });
            track.addEventListener("mouseleave", () => {
                paused = false;
            });
        }

        if (reverseOnHover) {
            track.addEventListener("mouseenter", () => {
                currentSign = baseSign * -1;
            });
            track.addEventListener("mouseleave", () => {
                currentSign = baseSign;
            });
        }

        if (pauseTouch) {
            track.addEventListener(
                "touchstart",
                () => {
                    paused = true;
                },
                { passive: true }
            );
            track.addEventListener(
                "touchend",
                () => {
                    paused = false;
                },
                { passive: true }
            );
        }

        applyTransform();
        rafId = requestAnimationFrame(step);

        const resizeObserver = new ResizeObserver(handleResize);
        resizeObserver.observe(firstList);
        resizeObserver.observe(track);

        return () => {
            if (rafId) {
                cancelAnimationFrame(rafId);
            }
            resizeObserver.disconnect();
        };
    };

    const initMarquee = ($scope) => {
        const root = $scope[0]?.querySelector(".king-addons-image-marquee");
        if (!root) {
            return;
        }

        const tracks = root.querySelectorAll(".king-addons-image-marquee__track");
        if (!tracks.length) {
            return;
        }

        const cleanupFns = [];
        tracks.forEach((track) => {
            const cleanup = initTrack(track, root.dataset || {});
            if (cleanup) {
                cleanupFns.push(cleanup);
            }
        });

        $scope.on("destroy", () => {
            cleanupFns.forEach((fn) => fn());
        });
    };

    $(window).on("elementor/frontend/init", () => {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-image-marquee.default",
            ($scope) => {
                initMarquee($scope);
            }
        );
    });
})(jQuery);







