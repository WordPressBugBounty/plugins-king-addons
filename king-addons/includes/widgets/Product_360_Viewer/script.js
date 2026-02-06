"use strict";

(() => {
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const parseJSON = (value, fallback) => {
        try {
            return JSON.parse(value);
        } catch (e) {
            return fallback;
        }
    };

    class KingAddonsProduct360Viewer {
        constructor(root) {
            this.root = root;
            this.canvas = root.querySelector(".king-addons-product-360-viewer__canvas");
            this.image = root.querySelector(".king-addons-product-360-viewer__frame");
            this.loader = root.querySelector(".king-addons-product-360-viewer__loader");
            this.progress = root.querySelector(".king-addons-product-360-viewer__progress");
            this.progressValue = root.querySelector(".king-addons-product-360-viewer__progress-value");
            this.zoomButton = root.querySelector(".king-addons-product-360-viewer__zoom");
            this.hotspotsLayer = root.querySelector(".king-addons-product-360-viewer__hotspots");

            this.frames = parseJSON(root.dataset.frames || "[]", []);
            this.hotspots = parseJSON(root.dataset.hotspots || "[]", []);

            this.settings = {
                isPro: root.dataset.isPro === "true",
                autoplay: (root.dataset.autoplay || "no") === "yes",
                autoplaySpeed: parseInt(root.dataset.autoplaySpeed || "90", 10),
                autoplayMode: root.dataset.autoplayMode || "loop",
                dragSensitivity: parseFloat(root.dataset.dragSensitivity || "8"),
                invertDrag: (root.dataset.invertDrag || "no") === "yes",
                dragAxis: root.dataset.dragAxis || "horizontal",
                enableScroll: (root.dataset.enableScroll || "no") === "yes",
                inertia: parseFloat(root.dataset.inertia || "0"),
                showProgress: (root.dataset.showProgress || "yes") === "yes",
                showZoom: (root.dataset.showZoom || "no") === "yes",
                zoomScale: parseFloat(root.dataset.zoomScale || "1.35"),
                startFrame: clamp(parseInt(root.dataset.startFrame || "1", 10) - 1, 0, this.frames.length - 1),
            };

            this.state = {
                currentFrame: this.settings.startFrame,
                autoplayTimer: null,
                pointerDown: false,
                lastPointer: { x: 0, y: 0 },
                inertiaFrame: null,
                inertiaAccumulator: 0,
                velocity: 0,
                autoplayWasRunning: false,
            };
        }

        init() {
            if (!this.frames.length || !this.canvas || !this.image) {
                return;
            }

            this.setFrame(this.state.currentFrame, false);
            this.buildHotspots();
            this.bindEvents();
            this.preloadFrames();

            if (this.settings.autoplay) {
                this.startAutoplay();
            }
        }

        bindEvents() {
            this.canvas.addEventListener("pointerdown", (event) => this.handlePointerDown(event));
            if (this.settings.enableScroll) {
                this.canvas.addEventListener(
                    "wheel",
                    (event) => this.handleWheel(event),
                    { passive: false }
                );
            }

            if (this.zoomButton) {
                this.zoomButton.addEventListener("click", () => this.toggleZoom());
            }
        }

        handlePointerDown(event) {
            event.preventDefault();
            this.state.pointerDown = true;
            this.state.lastPointer = { x: event.clientX, y: event.clientY };
            this.state.velocity = 0;
            this.state.inertiaAccumulator = 0;

            if (this.settings.autoplay) {
                this.state.autoplayWasRunning = true;
                this.stopAutoplay();
            } else {
                this.state.autoplayWasRunning = false;
            }

            const moveHandler = (moveEvent) => this.handlePointerMove(moveEvent);
            const upHandler = (upEvent) => this.handlePointerUp(upEvent, moveHandler, upHandler);

            document.addEventListener("pointermove", moveHandler);
            document.addEventListener("pointerup", upHandler);
            document.addEventListener("pointercancel", upHandler);
        }

        handlePointerMove(event) {
            if (!this.state.pointerDown) {
                return;
            }

            const current = { x: event.clientX, y: event.clientY };
            const deltaX = current.x - this.state.lastPointer.x;
            const deltaY = current.y - this.state.lastPointer.y;

            let delta = deltaX;
            if (this.settings.dragAxis === "vertical") {
                delta = deltaY * -1;
            } else if (this.settings.dragAxis === "both") {
                delta = Math.abs(deltaY) > Math.abs(deltaX) ? deltaY * -1 : deltaX;
            }

            const sensitivity = Math.max(1, this.settings.dragSensitivity);
            const steps = Math.trunc(delta / sensitivity);

            if (steps !== 0) {
                const direction = this.settings.invertDrag ? -steps : steps;
                this.rotateBy(direction);
                this.state.velocity = direction;
                this.state.lastPointer = current;
            }
        }

        handlePointerUp(event, moveHandler, upHandler) {
            event.preventDefault();
            this.state.pointerDown = false;

            document.removeEventListener("pointermove", moveHandler);
            document.removeEventListener("pointerup", upHandler);
            document.removeEventListener("pointercancel", upHandler);

            if (this.settings.inertia > 0 && Math.abs(this.state.velocity) > 0) {
                this.startInertia(this.state.velocity);
            }

            if (this.state.autoplayWasRunning) {
                this.startAutoplay();
            }
        }

        handleWheel(event) {
            event.preventDefault();
            const direction = event.deltaY > 0 ? 1 : -1;
            this.rotateBy(direction);
        }

        rotateBy(step) {
            if (!Number.isFinite(step) || this.frames.length === 0) {
                return;
            }

            this.setFrame(this.state.currentFrame + step, true);
        }

        setFrame(targetIndex, updateProgress = true) {
            const length = this.frames.length;
            const nextIndex = ((targetIndex % length) + length) % length;
            const nextFrame = this.frames[nextIndex];

            if (!nextFrame) {
                return;
            }

            this.state.currentFrame = nextIndex;
            this.image.src = nextFrame.url;
            this.image.alt = nextFrame.alt || nextFrame.label || "";

            if (updateProgress) {
                this.updateProgress();
            }

            this.updateHotspots();
        }

        updateProgress() {
            if (!this.progressValue || !this.settings.showProgress) {
                return;
            }
            const humanIndex = this.state.currentFrame + 1;
            this.progressValue.textContent = `${humanIndex} / ${this.frames.length}`;
        }

        startAutoplay() {
            this.stopAutoplay();
            if (this.frames.length <= 1) {
                return;
            }

            const speed = clamp(this.settings.autoplaySpeed, 10, 2000);
            let direction = 1;

            this.state.autoplayTimer = window.setInterval(() => {
                if (this.settings.autoplayMode === "pingpong") {
                    const nextIndex = this.state.currentFrame + direction;
                    if (nextIndex >= this.frames.length || nextIndex < 0) {
                        direction *= -1;
                    }
                    this.setFrame(this.state.currentFrame + direction);
                    return;
                }

                this.setFrame(this.state.currentFrame + 1);
            }, speed);
        }

        stopAutoplay() {
            if (this.state.autoplayTimer) {
                window.clearInterval(this.state.autoplayTimer);
                this.state.autoplayTimer = null;
            }
        }

        startInertia(initialVelocity) {
            if (this.state.inertiaFrame) {
                window.cancelAnimationFrame(this.state.inertiaFrame);
            }

            const friction = clamp(this.settings.inertia, 0, 1);
            let velocity = initialVelocity;
            this.state.inertiaAccumulator = 0;

            const tick = () => {
                velocity *= 1 - friction;
                this.state.inertiaAccumulator += velocity;

                const step = Math.trunc(this.state.inertiaAccumulator);
                if (step !== 0) {
                    this.state.inertiaAccumulator -= step;
                    this.rotateBy(step);
                }

                if (Math.abs(velocity) > 0.05 || Math.abs(this.state.inertiaAccumulator) > 0.05) {
                    this.state.inertiaFrame = window.requestAnimationFrame(tick);
                }
            };

            this.state.inertiaFrame = window.requestAnimationFrame(tick);
        }

        preloadFrames() {
            if (!this.loader) {
                return;
            }

            let loaded = 0;
            const total = this.frames.length;

            const handleLoaded = () => {
                loaded += 1;
                if (loaded >= Math.min(total, 2)) {
                    this.root.classList.remove("king-addons-product-360-viewer--loading");
                }
            };

            this.frames.forEach((frame) => {
                const img = new Image();
                img.onload = handleLoaded;
                img.onerror = handleLoaded;
                img.src = frame.url;
            });
        }

        toggleZoom() {
            if (!this.settings.showZoom) {
                return;
            }

            const isActive = this.root.classList.toggle("king-addons-product-360-viewer--zoomed");
            if (this.zoomButton) {
                this.zoomButton.setAttribute("aria-pressed", isActive ? "true" : "false");
            }
            this.image.style.setProperty("--ka-360-zoom-scale", this.settings.zoomScale || 1.35);
        }

        buildHotspots() {
            if (!this.hotspotsLayer || !this.hotspots.length) {
                return;
            }

            this.hotspotsLayer.querySelectorAll(".king-addons-product-360-viewer__hotspot").forEach((node) => {
                node.addEventListener("click", (event) => this.handleHotspotClick(event.currentTarget));
            });

            this.updateHotspots();
        }

        handleHotspotClick(target) {
            const url = target.dataset.link;
            if (!url) {
                return;
            }

            const anchor = document.createElement("a");
            anchor.href = url;
            anchor.target = target.dataset.newTab === "true" ? "_blank" : "_self";

            const rel = [];
            if (target.dataset.nofollow === "true") {
                rel.push("nofollow");
            }
            if (anchor.target === "_blank") {
                rel.push("noopener", "noreferrer");
            }
            anchor.rel = rel.join(" ");

            anchor.style.display = "none";
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
        }

        updateHotspots() {
            if (!this.hotspotsLayer) {
                return;
            }

            const activeFrame = this.state.currentFrame + 1;
            this.hotspotsLayer.querySelectorAll(".king-addons-product-360-viewer__hotspot").forEach((hotspot) => {
                const frameMatch = Number(hotspot.dataset.frame) === activeFrame;
                hotspot.classList.toggle("is-active", frameMatch);
                hotspot.setAttribute("aria-hidden", frameMatch ? "false" : "true");
            });
        }
    }

    const initScope = (scope) => {
        const roots = scope.querySelectorAll(".king-addons-product-360-viewer");
        roots.forEach((root) => {
            if (root.dataset.ka360Init === "true") {
                return;
            }
            root.dataset.ka360Init = "true";

            const instance = new KingAddonsProduct360Viewer(root);
            instance.init();
        });
    };

    const onDocumentReady = () => initScope(document);

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-product-360-viewer.default",
            (scope) => {
                const target = Array.isArray(scope) ? scope[0] : scope;
                if (target) {
                    initScope(target);
                }
            }
        );
    }

    if (document.readyState === "complete" || document.readyState === "interactive") {
        onDocumentReady();
    } else {
        document.addEventListener("DOMContentLoaded", onDocumentReady);
    }
})();




