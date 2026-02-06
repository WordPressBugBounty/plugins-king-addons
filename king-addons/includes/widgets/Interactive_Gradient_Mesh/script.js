"use strict";

(function ($) {
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

    const parseColor = (value) => {
        if (!value) {
            return null;
        }
        const input = String(value).trim();
        const rgbMatch = input.match(/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*([\d.]+))?\s*\)$/i);
        if (rgbMatch) {
            return {
                r: parseFloat(rgbMatch[1]),
                g: parseFloat(rgbMatch[2]),
                b: parseFloat(rgbMatch[3]),
                a: rgbMatch[4] !== undefined ? parseFloat(rgbMatch[4]) : 1,
            };
        }
        const hexMatch = input.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
        if (hexMatch) {
            let hex = hexMatch[1];
            if (hex.length === 3) {
                hex = hex.split("").map((char) => char + char).join("");
            }
            return {
                r: parseInt(hex.slice(0, 2), 16),
                g: parseInt(hex.slice(2, 4), 16),
                b: parseInt(hex.slice(4, 6), 16),
                a: 1,
            };
        }
        return null;
    };

    const toRgba = (rgb, alpha) => {
        const r = Math.round(clamp(rgb.r ?? 255, 0, 255));
        const g = Math.round(clamp(rgb.g ?? 255, 0, 255));
        const b = Math.round(clamp(rgb.b ?? 255, 0, 255));
        const a = clamp(alpha, 0, 1);
        return `rgba(${r}, ${g}, ${b}, ${a})`;
    };

    const buildRgba = (value, intensity) => {
        const parsed = parseColor(value);
        if (!parsed) {
            return {
                color: value || "#ffffff",
                fade: "rgba(255, 255, 255, 0)",
            };
        }
        const baseAlpha = Number.isFinite(parsed.a) ? parsed.a : 1;
        const power = Number.isFinite(intensity) ? intensity : 1;
        const alpha = clamp(baseAlpha * power, 0, 1);
        return {
            color: toRgba(parsed, alpha),
            fade: toRgba(parsed, 0),
        };
    };

    const supportsCssVars = () => {
        return typeof CSS !== "undefined" && CSS.supports("color", "var(--kng-test)");
    };

    const supportsGradients = () => {
        return (
            typeof CSS !== "undefined" &&
            CSS.supports("background-image", "radial-gradient(circle at 10% 10%, #fff 0%, transparent 60%)")
        );
    };

    const supportsCanvas = () => {
        try {
            const canvas = document.createElement("canvas");
            return !!canvas.getContext && !!canvas.getContext("2d");
        } catch (error) {
            return false;
        }
    };

    const parsePayload = (wrapper) => {
        const raw = wrapper.dataset.mesh || "";
        if (!raw) {
            return null;
        }
        try {
            return JSON.parse(raw);
        } catch (error) {
            return null;
        }
    };

    const getSpeed = (wrapper, payload) => {
        const cssValue = getComputedStyle(wrapper).getPropertyValue("--kng-mesh-speed");
        const parsed = parseFloat(cssValue);
        if (Number.isFinite(parsed)) {
            return parsed;
        }
        return Number.isFinite(payload.speed) ? payload.speed : 40;
    };

    const getPerformanceFps = (payload, isMobile) => {
        const mode = payload.performance?.mode || "auto";
        if (mode === "performance") {
            return 20;
        }
        if (mode === "balanced") {
            return 30;
        }
        if (mode === "quality") {
            return 60;
        }
        return isMobile ? 24 : 36;
    };

    const applyFallback = (wrapper, payload) => {
        wrapper.classList.add("is-fallback");
        if (payload.fallback?.color) {
            wrapper.style.backgroundColor = payload.fallback.color;
        }
        if (payload.fallback?.image) {
            wrapper.style.backgroundImage = `url(${payload.fallback.image})`;
            wrapper.style.backgroundSize = "cover";
            wrapper.style.backgroundPosition = "center";
        }
    };

    const resolveEngine = (payload) => {
        const requested = payload.engine || "auto";
        if (requested === "canvas") {
            return supportsCanvas() ? "canvas" : "css";
        }
        if (requested === "auto") {
            return supportsCanvas() ? "canvas" : "css";
        }
        return "css";
    };

    const buildPointState = (points) => {
        return points.map((point, index) => {
            const intensity = point.intensity ?? 0.8;
            const colors = buildRgba(point.color || "#ffffff", intensity);
            return {
                index,
                baseX: point.x ?? 50,
                baseY: point.y ?? 50,
                radius: point.radius ?? 220,
                intensity,
                feather: point.feather ?? 60,
                color: colors.color,
                fade: colors.fade,
                blend: point.blend || "screen",
                motion: point.motion || "drift",
                amplitude: point.amplitude ?? 18,
                frequency: point.frequency ?? 0.25,
                phase: (point.phase ?? 0) * (Math.PI / 180),
                direction: (point.direction ?? 0) * (Math.PI / 180),
                speed: point.speed ?? 1,
            };
        });
    };

    const rotateVector = (x, y, angle) => {
        if (!angle) {
            return { x, y };
        }
        const cos = Math.cos(angle);
        const sin = Math.sin(angle);
        return {
            x: x * cos - y * sin,
            y: x * sin + y * cos,
        };
    };

    const getScrollProgress = (wrapper, payload, state) => {
        const mode = payload.scroll?.mode || "off";
        if (mode === "off") {
            return 0;
        }

        if (payload.scroll?.reduceMobile && state.isMobile) {
            return 0;
        }

        const direction = payload.scroll?.direction === "reverse" ? -1 : 1;
        const startPct = payload.scroll?.start ?? 0;
        const endPct = payload.scroll?.end ?? 100;
        const offset = payload.scroll?.offset ?? 0;
        let progress = 0;

        if (mode === "page") {
            const scrollTop = window.scrollY || window.pageYOffset || 0;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            progress = docHeight > 0 ? scrollTop / docHeight : 0;
        } else {
            const rect = wrapper.getBoundingClientRect();
            const scrollTop = window.scrollY || window.pageYOffset || 0;
            const elementTop = scrollTop + rect.top;
            const start = elementTop - window.innerHeight * (startPct / 100) - offset;
            const end = elementTop + rect.height - window.innerHeight * (endPct / 100) - offset;
            progress = end > start ? (scrollTop - start) / (end - start) : 0;
        }

        progress = clamp(progress, 0, 1);
        if (direction < 0) {
            progress = 1 - progress;
        }

        const easing = payload.scroll?.easing || "linear";
        if (easing === "ease-out") {
            progress = 1 - Math.pow(1 - progress, 2);
        } else if (easing === "ease-in") {
            progress = Math.pow(progress, 2);
        } else if (easing === "ease-in-out") {
            progress = progress < 0.5 ? 2 * progress * progress : 1 - Math.pow(-2 * progress + 2, 2) / 2;
        }

        return progress;
    };

    const setPointVars = (wrapper, points, dims, t) => {
        points.forEach((point) => {
            let dx = 0;
            let dy = 0;
            const motion = point.motion;
            const localT = t * point.speed;

            if (motion !== "static") {
                const angle = localT * point.frequency * Math.PI * 2 + point.phase;
                if (motion === "orbit") {
                    dx = Math.cos(angle) * point.amplitude;
                    dy = Math.sin(angle) * point.amplitude;
                } else if (motion === "noise") {
                    dx = Math.sin(angle) * point.amplitude;
                    dy = Math.sin(angle * 1.3 + point.phase * 0.4) * point.amplitude;
                } else {
                    dx = Math.sin(angle) * point.amplitude;
                    dy = Math.cos(angle) * point.amplitude;
                }

                const rotated = rotateVector(dx, dy, point.direction);
                dx = rotated.x;
                dy = rotated.y;
            }

            const xPct = clamp(point.baseX + (dx / dims.width) * 100, 0, 100);
            const yPct = clamp(point.baseY + (dy / dims.height) * 100, 0, 100);
            wrapper.style.setProperty(`--kng-mesh-p${point.index + 1}-x`, `${xPct}%`);
            wrapper.style.setProperty(`--kng-mesh-p${point.index + 1}-y`, `${yPct}%`);
        });
    };

    const drawCanvas = (ctx, points, dims, t) => {
        ctx.clearRect(0, 0, dims.width, dims.height);
        points.forEach((point) => {
            let dx = 0;
            let dy = 0;
            const motion = point.motion;
            const localT = t * point.speed;

            if (motion !== "static") {
                const angle = localT * point.frequency * Math.PI * 2 + point.phase;
                if (motion === "orbit") {
                    dx = Math.cos(angle) * point.amplitude;
                    dy = Math.sin(angle) * point.amplitude;
                } else if (motion === "noise") {
                    dx = Math.sin(angle) * point.amplitude;
                    dy = Math.sin(angle * 1.3 + point.phase * 0.4) * point.amplitude;
                } else {
                    dx = Math.sin(angle) * point.amplitude;
                    dy = Math.cos(angle) * point.amplitude;
                }

                const rotated = rotateVector(dx, dy, point.direction);
                dx = rotated.x;
                dy = rotated.y;
            }

            const x = (point.baseX / 100) * dims.width + dx;
            const y = (point.baseY / 100) * dims.height + dy;
            const radius = Math.max(point.radius, 1);
            const feather = clamp(point.feather / 100, 0, 1);
            const solidRadius = radius - radius * feather;
            const stop = clamp(solidRadius / radius, 0, 1);

            const gradient = ctx.createRadialGradient(x, y, 0, x, y, radius);
            gradient.addColorStop(0, point.color);
            gradient.addColorStop(stop, point.color);
            gradient.addColorStop(1, point.fade);

            ctx.globalCompositeOperation = point.blend === "normal" ? "source-over" : point.blend;
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, dims.width, dims.height);
        });
    };

    const initExportCopy = (wrapper) => {
        const exportPanel = wrapper.querySelector(".king-addons-gradient-mesh__export");
        if (!exportPanel) {
            return;
        }
        const button = exportPanel.querySelector(".king-addons-gradient-mesh__export-copy");
        const textarea = exportPanel.querySelector(".king-addons-gradient-mesh__export-text");
        if (!button || !textarea) {
            return;
        }
        button.addEventListener("click", () => {
            textarea.select();
            try {
                navigator.clipboard?.writeText(textarea.value);
            } catch (error) {
                document.execCommand("copy");
            }
            button.textContent = "Copied";
            setTimeout(() => {
                button.textContent = "Copy CSS";
            }, 1200);
        });
    };

    const initMesh = ($scope) => {
        const wrapper = $scope[0]?.querySelector(".king-addons-gradient-mesh");
        if (!wrapper) {
            return;
        }

        const payload = parsePayload(wrapper);
        if (!payload) {
            return;
        }

        initExportCopy(wrapper);

        const isEditMode = window.elementorFrontend?.isEditMode && elementorFrontend.isEditMode();
        if (isEditMode) {
            wrapper.classList.add("is-editor");
        }

        wrapper.dataset.motion = payload.motion || "drift";

        const engine = resolveEngine(payload);
        if (engine === "canvas") {
            wrapper.classList.add("is-canvas");
        } else {
            wrapper.classList.remove("is-canvas");
        }

        if (engine === "css" && (!supportsCssVars() || !supportsGradients())) {
            applyFallback(wrapper, payload);
            return;
        }

        const points = buildPointState(payload.points || []);

        const prefersReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        const animate = payload.animate && !prefersReduced;
        if (!animate) {
            wrapper.classList.remove("is-animated");
            if (engine === "canvas" && points.length) {
                const canvas = wrapper.querySelector(".king-addons-gradient-mesh__canvas");
                const ctx = canvas ? canvas.getContext("2d") : null;
                if (ctx) {
                    const drawStatic = () => {
                        const dims = { width: wrapper.clientWidth, height: wrapper.clientHeight };
                        canvas.width = dims.width;
                        canvas.height = dims.height;
                        drawCanvas(ctx, points, dims, 0);
                    };
                    drawStatic();
                    window.addEventListener("resize", drawStatic);
                }
            }
            return;
        }

        const hasMotion = points.some((point) => point.motion !== "static");
        const scrollMode = payload.scroll?.mode || "off";
        const needsLoop = scrollMode !== "off" || hasMotion;

        if (!needsLoop) {
            if (engine === "canvas" && points.length) {
                const canvas = wrapper.querySelector(".king-addons-gradient-mesh__canvas");
                const ctx = canvas ? canvas.getContext("2d") : null;
                if (ctx) {
                    const drawStatic = () => {
                        const dims = { width: wrapper.clientWidth, height: wrapper.clientHeight };
                        canvas.width = dims.width;
                        canvas.height = dims.height;
                        drawCanvas(ctx, points, dims, 0);
                    };
                    drawStatic();
                    window.addEventListener("resize", drawStatic);
                }
            }
            return;
        }

        const canvas = wrapper.querySelector(".king-addons-gradient-mesh__canvas");
        const ctx = engine === "canvas" && canvas ? canvas.getContext("2d") : null;
        const dims = { width: wrapper.clientWidth, height: wrapper.clientHeight };

        const updateCanvasSize = () => {
            dims.width = wrapper.clientWidth;
            dims.height = wrapper.clientHeight;
            if (canvas) {
                canvas.width = dims.width;
                canvas.height = dims.height;
            }
        };

        updateCanvasSize();

        const isMobile = window.matchMedia("(max-width: 767px)").matches;
        const fps = getPerformanceFps(payload, isMobile);
        const frameInterval = 1000 / fps;
        let lastTime = 0;
        let rafId = 0;
        let inView = true;
        let scrollProgress = 0;

        const updateScroll = () => {
            scrollProgress = getScrollProgress(wrapper, payload, { isMobile });
        };

        if (scrollMode !== "off") {
            window.addEventListener("scroll", () => {
                window.requestAnimationFrame(updateScroll);
            });
            updateScroll();
        }

        const observer = "IntersectionObserver" in window
            ? new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    inView = entry.isIntersecting;
                });
            })
            : null;

        if (observer) {
            observer.observe(wrapper);
        }

        const animateFrame = (time) => {
            if (!inView) {
                rafId = window.requestAnimationFrame(animateFrame);
                return;
            }

            if (time - lastTime < frameInterval) {
                rafId = window.requestAnimationFrame(animateFrame);
                return;
            }

            lastTime = time;

            const speed = getSpeed(wrapper, payload);
            const speedScale = 0.15 + (speed / 100) * 0.85;
            let t = (time / 1000) * speedScale;

            if (scrollMode !== "off") {
                if (scrollMode === "hybrid") {
                    t = t + scrollProgress * 6;
                } else {
                    t = scrollProgress * 8;
                }
            }

            if (engine === "canvas" && ctx) {
                drawCanvas(ctx, points, dims, t);
            } else {
                setPointVars(wrapper, points, dims, t);
            }

            rafId = window.requestAnimationFrame(animateFrame);
        };

        rafId = window.requestAnimationFrame(animateFrame);
        window.addEventListener("resize", () => {
            updateCanvasSize();
        });

        if (isEditMode) {
            wrapper.addEventListener("mouseleave", () => {
                if (rafId) {
                    cancelAnimationFrame(rafId);
                    rafId = window.requestAnimationFrame(animateFrame);
                }
            });
        }
    };

    $(window).on("elementor/frontend/init", function () {
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-interactive-gradient-mesh.default",
            function ($scope) {
                initMesh($scope);
            }
        );
    });
})(jQuery);
