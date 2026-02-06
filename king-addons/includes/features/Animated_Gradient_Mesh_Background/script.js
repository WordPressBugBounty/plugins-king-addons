(() => {
    const PRESETS = {
        aurora: {
            base: "#0b1020",
            colors: ["#0b1020", "#1c3c68", "#3dd6d0"],
            gradient: "linear-gradient(135deg, #0b1020 0%, #1c3c68 45%, #3dd6d0 100%)",
            mesh: "radial-gradient(circle at 15% 20%, rgba(61, 214, 208, 0.65) 0%, rgba(61, 214, 208, 0) 55%), radial-gradient(circle at 80% 25%, rgba(88, 120, 255, 0.6) 0%, rgba(88, 120, 255, 0) 55%), radial-gradient(circle at 45% 85%, rgba(8, 14, 30, 0.9) 0%, rgba(8, 14, 30, 0) 60%)",
        },
        sunset: {
            base: "#1d0c08",
            colors: ["#2c0f09", "#ff7a59", "#ffd082"],
            gradient: "linear-gradient(120deg, #2c0f09 0%, #ff7a59 45%, #ffd082 100%)",
            mesh: "radial-gradient(circle at 20% 25%, rgba(255, 122, 89, 0.65) 0%, rgba(255, 122, 89, 0) 60%), radial-gradient(circle at 80% 30%, rgba(255, 208, 130, 0.6) 0%, rgba(255, 208, 130, 0) 60%), radial-gradient(circle at 50% 80%, rgba(55, 15, 8, 0.85) 0%, rgba(55, 15, 8, 0) 60%)",
        },
        ocean: {
            base: "#061a2e",
            colors: ["#061a2e", "#0d5c7f", "#39d2c0"],
            gradient: "linear-gradient(140deg, #061a2e 0%, #0d5c7f 45%, #39d2c0 100%)",
            mesh: "radial-gradient(circle at 18% 30%, rgba(57, 210, 192, 0.6) 0%, rgba(57, 210, 192, 0) 60%), radial-gradient(circle at 80% 20%, rgba(13, 92, 127, 0.7) 0%, rgba(13, 92, 127, 0) 60%), radial-gradient(circle at 50% 85%, rgba(5, 16, 34, 0.9) 0%, rgba(5, 16, 34, 0) 60%)",
        },
        "violet-mist": {
            base: "#190c2d",
            colors: ["#1c0d33", "#7a3bff", "#f4b9ff"],
            gradient: "linear-gradient(135deg, #1c0d33 0%, #7a3bff 45%, #f4b9ff 100%)",
            mesh: "radial-gradient(circle at 20% 25%, rgba(244, 185, 255, 0.6) 0%, rgba(244, 185, 255, 0) 60%), radial-gradient(circle at 80% 30%, rgba(122, 59, 255, 0.6) 0%, rgba(122, 59, 255, 0) 60%), radial-gradient(circle at 45% 80%, rgba(20, 8, 40, 0.9) 0%, rgba(20, 8, 40, 0) 60%)",
        },
        "lime-neon": {
            base: "#0a1506",
            colors: ["#0a1506", "#59ff6f", "#d7ff5c"],
            gradient: "linear-gradient(135deg, #0a1506 0%, #59ff6f 45%, #d7ff5c 100%)",
            mesh: "radial-gradient(circle at 25% 25%, rgba(89, 255, 111, 0.6) 0%, rgba(89, 255, 111, 0) 60%), radial-gradient(circle at 75% 30%, rgba(215, 255, 92, 0.6) 0%, rgba(215, 255, 92, 0) 60%), radial-gradient(circle at 50% 85%, rgba(10, 21, 6, 0.85) 0%, rgba(10, 21, 6, 0) 60%)",
        },
        "mono-glass": {
            base: "#0f1115",
            colors: ["#0f1115", "#3d3f44", "#a8aab0"],
            gradient: "linear-gradient(135deg, #0f1115 0%, #3d3f44 45%, #a8aab0 100%)",
            mesh: "radial-gradient(circle at 20% 30%, rgba(168, 170, 176, 0.55) 0%, rgba(168, 170, 176, 0) 60%), radial-gradient(circle at 80% 25%, rgba(61, 63, 68, 0.6) 0%, rgba(61, 63, 68, 0) 60%), radial-gradient(circle at 50% 80%, rgba(15, 17, 22, 0.9) 0%, rgba(15, 17, 22, 0) 60%)",
        },
        "forest-dew": {
            base: "#0a1a12",
            colors: ["#0a1a12", "#1e5a3a", "#7ee8a5"],
            gradient: "linear-gradient(135deg, #0a1a12 0%, #1e5a3a 45%, #7ee8a5 100%)",
            mesh: "radial-gradient(circle at 20% 25%, rgba(126, 232, 165, 0.6) 0%, rgba(126, 232, 165, 0) 60%), radial-gradient(circle at 75% 35%, rgba(30, 90, 58, 0.7) 0%, rgba(30, 90, 58, 0) 60%), radial-gradient(circle at 50% 80%, rgba(10, 26, 18, 0.9) 0%, rgba(10, 26, 18, 0) 60%)",
        },
        "candy-pop": {
            base: "#2d0a2a",
            colors: ["#2d0a2a", "#ff6b9d", "#ffc857"],
            gradient: "linear-gradient(135deg, #2d0a2a 0%, #ff6b9d 45%, #ffc857 100%)",
            mesh: "radial-gradient(circle at 25% 20%, rgba(255, 107, 157, 0.65) 0%, rgba(255, 107, 157, 0) 60%), radial-gradient(circle at 80% 30%, rgba(255, 200, 87, 0.6) 0%, rgba(255, 200, 87, 0) 60%), radial-gradient(circle at 50% 85%, rgba(45, 10, 42, 0.9) 0%, rgba(45, 10, 42, 0) 60%)",
        },
        "midnight-blue": {
            base: "#050a1a",
            colors: ["#050a1a", "#0a2463", "#247ba0"],
            gradient: "linear-gradient(135deg, #050a1a 0%, #0a2463 45%, #247ba0 100%)",
            mesh: "radial-gradient(circle at 20% 30%, rgba(36, 123, 160, 0.6) 0%, rgba(36, 123, 160, 0) 60%), radial-gradient(circle at 80% 20%, rgba(10, 36, 99, 0.7) 0%, rgba(10, 36, 99, 0) 60%), radial-gradient(circle at 50% 85%, rgba(5, 10, 26, 0.9) 0%, rgba(5, 10, 26, 0) 60%)",
        },
        "rose-gold": {
            base: "#1a0f0f",
            colors: ["#1a0f0f", "#b76e79", "#f7d1ba"],
            gradient: "linear-gradient(135deg, #1a0f0f 0%, #b76e79 45%, #f7d1ba 100%)",
            mesh: "radial-gradient(circle at 25% 25%, rgba(247, 209, 186, 0.6) 0%, rgba(247, 209, 186, 0) 60%), radial-gradient(circle at 75% 30%, rgba(183, 110, 121, 0.65) 0%, rgba(183, 110, 121, 0) 60%), radial-gradient(circle at 50% 80%, rgba(26, 15, 15, 0.9) 0%, rgba(26, 15, 15, 0) 60%)",
        },
    };

    const DIRECTION_MAP = {
        "left-right": { from: [0, 50], to: [100, 50] },
        "right-left": { from: [100, 50], to: [0, 50] },
        "top-bottom": { from: [50, 0], to: [50, 100] },
        "bottom-top": { from: [50, 100], to: [50, 0] },
        "diag-lr": { from: [0, 0], to: [100, 100] },
        "diag-rl": { from: [100, 0], to: [0, 100] },
    };

    const supportsCssVars = () =>
        window.CSS && CSS.supports && CSS.supports("--kng-agm-test: 0");
    const supportsGradients = () =>
        window.CSS && CSS.supports && CSS.supports("background-image", "linear-gradient(#000, #fff)");

    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

    const parsePayload = (element) => {
        const raw = element.getAttribute("data-kng-agm");
        if (!raw) {
            return null;
        }
        try {
            return JSON.parse(raw);
        } catch (error) {
            return null;
        }
    };

    const isEditor = () =>
        Boolean(window.elementorFrontend && elementorFrontend.isEditMode && elementorFrontend.isEditMode());

    const getPreset = (key) => PRESETS[key] || PRESETS.aurora;

    const parseColor = (value) => {
        if (!value || typeof value !== "string") {
            return null;
        }
        const hex = value.trim();
        if (hex.startsWith("#")) {
            const clean = hex.replace("#", "");
            if (clean.length === 3) {
                const r = parseInt(clean[0] + clean[0], 16);
                const g = parseInt(clean[1] + clean[1], 16);
                const b = parseInt(clean[2] + clean[2], 16);
                return { r, g, b };
            }
            if (clean.length === 6) {
                const r = parseInt(clean.slice(0, 2), 16);
                const g = parseInt(clean.slice(2, 4), 16);
                const b = parseInt(clean.slice(4, 6), 16);
                return { r, g, b };
            }
        }

        const rgbMatch = value.match(/rgba?\(([^)]+)\)/i);
        if (rgbMatch) {
            const parts = rgbMatch[1].split(",").map((part) => parseFloat(part.trim()));
            if (parts.length >= 3) {
                return { r: parts[0], g: parts[1], b: parts[2] };
            }
        }
        return null;
    };

    const toRgba = (value, alpha) => {
        const parsed = parseColor(value);
        if (!parsed) {
            return value;
        }
        return `rgba(${parsed.r}, ${parsed.g}, ${parsed.b}, ${alpha})`;
    };

    const buildGradient = (colors) =>
        `linear-gradient(135deg, ${colors[0]} 0%, ${colors[1]} 45%, ${colors[2]} 100%)`;

    const buildMesh = (colors) => {
        const primary = toRgba(colors[0], 0.6);
        const secondary = toRgba(colors[1], 0.6);
        const deep = toRgba(colors[2], 0.9);
        const primaryClear = toRgba(colors[0], 0);
        const secondaryClear = toRgba(colors[1], 0);
        const deepClear = toRgba(colors[2], 0);
        return `radial-gradient(circle at 20% 25%, ${primary} 0%, ${primaryClear} 60%), radial-gradient(circle at 80% 30%, ${secondary} 0%, ${secondaryClear} 60%), radial-gradient(circle at 50% 85%, ${deep} 0%, ${deepClear} 60%)`;
    };

    const resolveColors = (layer, preset) => {
        if (layer && Array.isArray(layer.colors) && layer.colors.length >= 3) {
            return layer.colors;
        }
        return preset.colors;
    };

    const buildLayerBackground = (layer, preset) => {
        const colors = resolveColors(layer, preset);
        return layer.type === "mesh" ? buildMesh(colors) : buildGradient(colors);
    };

    const speedToDuration = (speed) => {
        const safe = Math.max(1, Number(speed) || 40);
        return `${Math.max(4, Math.round(400 / safe))}s`;
    };

    const getTarget = (element) => {
        if (element.classList.contains("elementor-column")) {
            return element.querySelector(".elementor-widget-wrap") || element;
        }
        return element;
    };

    const clearLayers = (target) => {
        target.querySelectorAll(".kng-agm-layer").forEach((layer) => layer.remove());
        const exportPanel = target.querySelector(".kng-agm-export");
        if (exportPanel) {
            exportPanel.remove();
        }
    };

    const isMobile = () => window.matchMedia("(max-width: 767px)").matches;
    const isTablet = () => window.matchMedia("(max-width: 1024px)").matches;

    const shouldDisableForDevice = (payload) => {
        if (payload.disable?.mobile && isMobile()) {
            return true;
        }
        if (payload.disable?.tablet && !isMobile() && isTablet()) {
            return true;
        }
        return false;
    };

    const applyFallback = (target, preset) => {
        target.style.backgroundColor = preset.base;
        target.style.backgroundImage = "none";
    };

    const buildLayerElement = (layer, payload) => {
        const preset = getPreset(layer.preset || payload.preset);
        const element = document.createElement("span");
        element.className = "kng-agm-layer";
        element.style.backgroundImage = buildLayerBackground(layer, preset);
        element.style.opacity = layer.opacity ?? 0.7;
        element.style.filter = `blur(${layer.blur || 0}px)`;
        element.style.backgroundSize = `${layer.size || 200}% ${layer.size || 200}%`;
        element.style.backgroundPosition = "0% 50%";
        element.style.mixBlendMode = layer.blendMode || payload.blendMode || "normal";
        element.style.animationDuration = speedToDuration(layer.speed || payload.speed || 40);
        element.style.animationTimingFunction = "ease-in-out";
        element.style.animationIterationCount = "infinite";
        element.dataset.direction = layer.direction || payload.direction || "left-right";
        if (!payload.animate || (payload.scroll && payload.scroll.mode !== "off")) {
            element.classList.add("is-static");
            element.style.animation = "none";
        }
        return element;
    };

    const buildExportCss = (payload) => {
        const preset = getPreset(payload.preset || "aurora");
        const type = payload.type === "mesh" ? "mesh" : "gradient";
        const baseGradient = type === "mesh" ? preset.mesh : preset.gradient;
        const direction = payload.direction || "left-right";
        const range = DIRECTION_MAP[direction] || DIRECTION_MAP["left-right"];
        const duration = speedToDuration(payload.speed || 40);
        const opacity = payload.opacity ?? 1;
        const blur = payload.blur ?? 0;
        const blendMode = payload.blendMode || "normal";
        const animate = payload.animate && (!payload.scroll || payload.scroll.mode === "off");
        const noise = payload.noise || {};

        let css = "";

        if (payload.export?.includeFallback) {
            css += `.kng-animated-bg{background:${preset.base};}\n`;
        }

        css += `.kng-animated-bg{position:relative;overflow:hidden;}\n`;
        css += `.kng-animated-bg::before{content:\"\";position:absolute;inset:0;background-color:${preset.base};background-image:${baseGradient};background-size:200% 200%;background-position:${range.from[0]}% ${range.from[1]}%;opacity:${opacity};filter:blur(${blur}px);mix-blend-mode:${blendMode};}\n`;

        if (animate) {
            css += `.kng-animated-bg::before{animation:kng-agm-shift ${duration} ease-in-out infinite;}\n`;
        }

        if (noise.enabled) {
            css += `.kng-animated-bg::after{content:\"\";position:absolute;inset:0;pointer-events:none;opacity:${noise.amount ?? 0.18};background-image:repeating-linear-gradient(0deg, rgba(255, 255, 255, 0.14), rgba(255, 255, 255, 0.14) 1px, transparent 1px, transparent 3px),repeating-linear-gradient(90deg, rgba(0, 0, 0, 0.08), rgba(0, 0, 0, 0.08) 1px, transparent 1px, transparent 4px);mix-blend-mode:soft-light;}\n`;
        }

        css += `@keyframes kng-agm-shift{0%{background-position:${range.from[0]}% ${range.from[1]}%;}100%{background-position:${range.to[0]}% ${range.to[1]}%;}}\n`;

        if (payload.export?.includeReducedMotion) {
            css += `@media (prefers-reduced-motion: reduce){.kng-animated-bg::before{animation:none;}}\n`;
        }

        return css.trim();
    };

    const initExportPanel = (target, payload) => {
        if (!payload.export?.enabled || !isEditor()) {
            return;
        }
        const css = buildExportCss(payload);
        if (!css) {
            return;
        }

        const panel = document.createElement("div");
        panel.className = "kng-agm-export";
        panel.innerHTML = `<button type=\"button\">Copy CSS</button><textarea readonly>${css}</textarea>`;

        target.insertBefore(panel, target.firstChild);

        const button = panel.querySelector("button");
        const textarea = panel.querySelector("textarea");
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

    const scrollRegistry = new Map();
    let scrollBound = false;
    let rafPending = false;

    const getScrollProgress = (element, mode, start, end) => {
        const rangeStart = clamp((start || 0) / 100, 0, 1);
        const rangeEnd = clamp((end || 100) / 100, 0, 1);
        let raw = 0;

        if (mode === "page") {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
            const maxScroll = Math.max(document.documentElement.scrollHeight - window.innerHeight, 1);
            raw = scrollTop / maxScroll;
        } else {
            const rect = element.getBoundingClientRect();
            const viewHeight = window.innerHeight || 1;
            const total = rect.height + viewHeight;
            raw = total > 0 ? (viewHeight - rect.top) / total : 0;
        }

        if (rangeEnd <= rangeStart) {
            return clamp(raw, 0, 1);
        }

        return clamp((raw - rangeStart) / (rangeEnd - rangeStart), 0, 1);
    };

    const applyEasing = (value, easing) => {
        if (easing === "ease") {
            return value * value * (3 - 2 * value);
        }
        if (easing === "parallax") {
            return clamp(value * 1.15, 0, 1);
        }
        return value;
    };

    const resolvePosition = (direction, progress) => {
        const map = DIRECTION_MAP[direction] || DIRECTION_MAP["left-right"];
        const x = map.from[0] + (map.to[0] - map.from[0]) * progress;
        const y = map.from[1] + (map.to[1] - map.from[1]) * progress;
        return { x, y };
    };

    const updateScrollEntry = (entry) => {
        const { element, payload, layers } = entry;
        if (!element.isConnected) {
            scrollRegistry.delete(element);
            return;
        }

        const scroll = payload.scroll || {};
        let progress = getScrollProgress(element, scroll.mode, scroll.start, scroll.end);
        progress = applyEasing(progress, scroll.easing || "linear");

        if (scroll.mode === "hybrid") {
            progress = clamp(progress * 0.35, 0, 1);
        }

        const baseDirection = payload.direction || "left-right";
        const basePos = resolvePosition(baseDirection, progress);
        element.style.setProperty("--kng-agm-scroll-x", `${basePos.x}%`);
        element.style.setProperty("--kng-agm-scroll-y", `${basePos.y}%`);

        layers.forEach((layer) => {
            const dir = layer.dataset.direction || baseDirection;
            const pos = resolvePosition(dir, progress);
            layer.style.backgroundPosition = `${pos.x}% ${pos.y}%`;
        });
    };

    const updateScrollAll = () => {
        rafPending = false;
        scrollRegistry.forEach(updateScrollEntry);
    };

    const scheduleScrollUpdate = () => {
        if (rafPending) {
            return;
        }
        rafPending = true;
        window.requestAnimationFrame(updateScrollAll);
    };

    const registerScroll = (element, payload, layers) => {
        scrollRegistry.set(element, { element, payload, layers });
        if (!scrollBound) {
            window.addEventListener("scroll", scheduleScrollUpdate, { passive: true });
            window.addEventListener("resize", scheduleScrollUpdate);
            scrollBound = true;
        }
        scheduleScrollUpdate();
    };

    const unregisterScroll = (element) => {
        scrollRegistry.delete(element);
    };

    const initElement = (element) => {
        const payload = parsePayload(element);
        const target = getTarget(element);

        if (!payload || !payload.enabled) {
            element.classList.remove("kng-agm-scroll-sync");
            unregisterScroll(element);
            clearLayers(target);
            return;
        }

        if (shouldDisableForDevice(payload)) {
            element.classList.remove("kng-agm-scroll-sync");
            unregisterScroll(element);
            clearLayers(target);
            return;
        }

        const preset = getPreset(payload.preset || "aurora");
        if (!supportsCssVars() || !supportsGradients()) {
            element.classList.remove("kng-agm-scroll-sync");
            unregisterScroll(element);
            applyFallback(target, preset);
            return;
        }

        clearLayers(target);

        let layersData = Array.isArray(payload.layers) ? payload.layers.slice() : [];
        if (payload.adaptive && isMobile()) {
            layersData = layersData.slice(0, 2);
            target.style.setProperty("--kng-agm-noise-opacity", Math.min(payload.noise?.amount ?? 0.18, 0.1));
            target.style.setProperty("--kng-agm-blur", "0px");
        }

        const layers = [];
        if (layersData.length) {
            const fragment = document.createDocumentFragment();
            layersData.forEach((layer) => {
                const elementLayer = buildLayerElement(layer, payload);
                layers.push(elementLayer);
                fragment.appendChild(elementLayer);
            });
            target.insertBefore(fragment, target.firstChild);
        }

        const scrollMode = payload.scroll?.mode || "off";
        const allowScroll = scrollMode !== "off" && !(payload.scroll?.reduceMobile && isMobile());
        if (allowScroll) {
            element.classList.add("kng-agm-scroll-sync");
            registerScroll(element, payload, layers);
        } else {
            element.classList.remove("kng-agm-scroll-sync");
            unregisterScroll(element);
        }

        initExportPanel(target, payload);
    };

    const initAll = (root) => {
        if (!root) {
            return;
        }
        const elements = root.querySelectorAll("[data-kng-agm]");
        elements.forEach(initElement);
    };

    const initGlobal = () => {
        initAll(document);

        if (window.elementorFrontend && elementorFrontend.hooks) {
            const handler = ($scope) => {
                const scopeElement = $scope[0] || $scope;
                if (!scopeElement) {
                    return;
                }
                if (scopeElement.hasAttribute && scopeElement.hasAttribute("data-kng-agm")) {
                    initElement(scopeElement);
                } else {
                    initAll(scopeElement);
                }
            };

            elementorFrontend.hooks.addAction("frontend/element_ready/section", handler);
            elementorFrontend.hooks.addAction("frontend/element_ready/container", handler);
            elementorFrontend.hooks.addAction("frontend/element_ready/column", handler);
        }
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initGlobal);
    } else {
        initGlobal();
    }
})();
