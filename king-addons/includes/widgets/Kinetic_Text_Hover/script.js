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

    const normalizeOptions = (raw = {}) => {
        const letter = raw.letterDrift || {};
        const magnetic = raw.magnetic || {};
        const variableFont = raw.variableFont || {};
        const reveal = raw.reveal || {};
        const reducedMotion = raw.reducedMotion || {};

        return {
            preset: raw.preset || "split-underline",
            trigger: raw.trigger || "hover",
            mobileBehavior: raw.mobileBehavior || "disable",
            intensity: clamp(Number(raw.intensity) || 35, 0, 100),
            letterDrift: {
                max: Math.max(Number(letter.max) || 0, 0),
                randomness: clamp(Number(letter.randomness) || 0, 0, 100),
            },
            magnetic: {
                enabled: magnetic.enabled === true || magnetic.enabled === "yes",
                strength: clamp(Number(magnetic.strength) || 0, 0, 100),
                radius: Math.max(Number(magnetic.radius) || 0, 0),
                maxOffset: Math.max(Number(magnetic.maxOffset) || 0, 0),
                smoothing: clamp(Number(magnetic.smoothing) || 0, 0, 1),
                clamp: magnetic.clamp || "soft",
                disableMobile: magnetic.disableMobile === true || magnetic.disableMobile === "yes",
            },
            variableFont: {
                enabled: variableFont.enabled === true || variableFont.enabled === "yes",
                wghtMin: Number(variableFont.wghtMin) || 400,
                wghtMax: Number(variableFont.wghtMax) || 700,
                wdthMin: Number(variableFont.wdthMin) || 100,
                wdthMax: Number(variableFont.wdthMax) || 110,
                widthSafe: variableFont.widthSafe === true || variableFont.widthSafe === "yes",
            },
            reveal: {
                enabled: reveal.enabled === true || reveal.enabled === "yes",
                type: reveal.type || "fade",
                threshold: clamp(Number(reveal.threshold) || 0.2, 0, 1),
                once: reveal.once === true || reveal.once === "yes",
            },
            reducedMotion: {
                respect: reducedMotion.respect === true || reducedMotion.respect === "yes",
                mode: reducedMotion.mode || "simplify",
            },
            editorPreview: raw.editorPreview === true || raw.editorPreview === "yes",
        };
    };

    const splitText = (visual, text) => {
        if (!visual) {
            return [];
        }

        while (visual.firstChild) {
            visual.removeChild(visual.firstChild);
        }

        const fragment = document.createDocumentFragment();
        const chars = [];

        for (let i = 0; i < text.length; i += 1) {
            const char = text[i];

            if (char === "\n") {
                fragment.appendChild(document.createElement("br"));
                continue;
            }

            const span = document.createElement("span");
            span.className = "kng-kt-char";
            span.setAttribute("aria-hidden", "true");

            if (char === " ") {
                span.classList.add("kng-kt-space");
                span.innerHTML = "&nbsp;";
            } else {
                span.textContent = char;
            }

            fragment.appendChild(span);
            chars.push(span);
        }

        visual.appendChild(fragment);
        visual.dataset.kineticSplit = "yes";
        return chars;
    };

    const getPointerSupport = () => {
        if (!window.matchMedia) {
            return { coarse: false };
        }
        const coarse = window.matchMedia("(hover: none), (pointer: coarse)").matches;
        return { coarse };
    };

    const initInstance = (root) => {
        if (!root || root.dataset.kineticInit === "yes") {
            return;
        }

        root.dataset.kineticInit = "yes";

        const options = normalizeOptions(parseOptions(root));
        const visual = root.querySelector(".king-addons-kinetic-text-hover__visual");
        if (!visual) {
            return;
        }

        const originalText = visual.dataset.originalText || visual.textContent || "";
        visual.dataset.originalText = originalText;
        visual.dataset.text = originalText;

        const charElements = splitText(visual, originalText);
        const chars = charElements.map((el, index) => ({
            el,
            index,
            randX: Math.random() * 2 - 1,
            randY: Math.random() * 2 - 1,
            driftX: 0,
            driftY: 0,
            magX: 0,
            magY: 0,
            centerX: 0,
            centerY: 0,
        }));

        const intensity = clamp(options.intensity / 100, 0, 1);
        const letterMax = options.letterDrift.max * intensity;
        const letterRandomness = clamp(options.letterDrift.randomness / 100, 0, 1);

        const pointerSupport = getPointerSupport();
        const isCoarsePointer = pointerSupport.coarse;
        const disableHover = isCoarsePointer && options.mobileBehavior === "disable";
        const tapMode = isCoarsePointer && options.mobileBehavior === "tap";

        if (disableHover) {
            root.classList.add("is-hover-disabled");
        }

        const prefersReduced =
            window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

        let motionMode = "full";
        if (options.reducedMotion.respect && prefersReduced) {
            motionMode = options.reducedMotion.mode === "disable" ? "disable" : "simplify";
            if (motionMode === "disable") {
                root.classList.add("is-motion-disabled");
            }
        }

        const canAnimate = motionMode !== "disable" && !disableHover;
        const allowLetterMotion = motionMode === "full" && options.preset === "letter-drift";
        const allowMagnetic =
            motionMode === "full" &&
            options.magnetic.enabled &&
            !(options.magnetic.disableMobile && isCoarsePointer);

        const setActive = (active) => {
            if (disableHover) {
                return;
            }
            if (!canAnimate && active) {
                return;
            }
            root.classList.toggle("is-active", active);
            if (options.variableFont.enabled) {
                const useWidth = !options.variableFont.widthSafe;
                const wght = active ? options.variableFont.wghtMax : options.variableFont.wghtMin;
                const wdth = active && useWidth ? options.variableFont.wdthMax : options.variableFont.wdthMin;
                visual.style.setProperty("--kng-kt-var-wght", `${wght}`);
                visual.style.setProperty("--kng-kt-var-wdth", `${wdth}`);
            }
        };

        if (options.variableFont.enabled) {
            root.classList.add("is-varfont-enabled");
            visual.style.setProperty("--kng-kt-var-wght", `${options.variableFont.wghtMin}`);
            visual.style.setProperty("--kng-kt-var-wdth", `${options.variableFont.wdthMin}`);
        }

        if (options.trigger === "idle" && canAnimate) {
            setActive(true);
        }

        if (options.reveal.enabled) {
            if (options.editorPreview || !("IntersectionObserver" in window)) {
                root.classList.add("is-visible");
            } else {
                const observer = new IntersectionObserver(
                    (entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting) {
                                root.classList.add("is-visible");
                                if (options.reveal.once && observer) {
                                    observer.unobserve(entry.target);
                                }
                            } else if (!options.reveal.once) {
                                root.classList.remove("is-visible");
                            }
                        });
                    },
                    { threshold: options.reveal.threshold }
                );

                observer.observe(root);
            }
        }

        if (!canAnimate) {
            return;
        }

        let active = options.trigger === "idle";
        let pointerX = 0;
        let pointerY = 0;
        let driftRaf = 0;
        let magneticRaf = 0;
        let magneticActive = false;
        let bounds = root.getBoundingClientRect();

        const updateBounds = () => {
            bounds = root.getBoundingClientRect();
            chars.forEach((char) => {
                const rect = char.el.getBoundingClientRect();
                char.centerX = rect.left - bounds.left + rect.width / 2;
                char.centerY = rect.top - bounds.top + rect.height / 2;
            });
        };

        updateBounds();

        const applyCharOffsets = (char) => {
            const x = char.driftX + char.magX;
            const y = char.driftY + char.magY;
            char.el.style.setProperty("--kng-kt-char-x", `${x.toFixed(2)}px`);
            char.el.style.setProperty("--kng-kt-char-y", `${y.toFixed(2)}px`);
        };

        const resetDrift = () => {
            chars.forEach((char) => {
                char.driftX = 0;
                char.driftY = 0;
                applyCharOffsets(char);
            });
        };

        const updateDrift = (usePointer) => {
            if (!allowLetterMotion) {
                return;
            }

            const centerX = bounds.width / 2;
            const centerY = bounds.height / 2;
            const max = letterMax;

            let baseX = 0;
            let baseY = 0;
            if (usePointer) {
                const dx = (pointerX - centerX) / (bounds.width / 2 || 1);
                const dy = (pointerY - centerY) / (bounds.height / 2 || 1);
                baseX = clamp(dx, -1, 1) * max;
                baseY = clamp(dy, -1, 1) * max;
            }

            chars.forEach((char) => {
                const randomX = char.randX * max * letterRandomness;
                const randomY = char.randY * max * letterRandomness;
                char.driftX = clamp(baseX + randomX, -max, max);
                char.driftY = clamp(baseY + randomY, -max, max);
                applyCharOffsets(char);
            });
        };

        const scheduleDrift = (usePointer) => {
            if (driftRaf) {
                return;
            }
            driftRaf = window.requestAnimationFrame(() => {
                driftRaf = 0;
                updateDrift(usePointer);
            });
        };

        const animateMagnetic = () => {
            if (!allowMagnetic) {
                magneticRaf = 0;
                return;
            }

            const strength = clamp(options.magnetic.strength / 100, 0, 1) * intensity;
            const radius = options.magnetic.radius;
            const maxOffset = options.magnetic.maxOffset * intensity;
            const smoothing = clamp(options.magnetic.smoothing, 0, 1);
            const lerp = clamp(1 - smoothing * 0.8, 0.05, 0.5);

            let stillActive = false;

            chars.forEach((char) => {
                let targetX = 0;
                let targetY = 0;

                if (magneticActive && radius > 0 && strength > 0 && maxOffset > 0) {
                    const dx = pointerX - char.centerX;
                    const dy = pointerY - char.centerY;
                    const distance = Math.hypot(dx, dy) || 0;

                    if (distance < radius) {
                        const ratio = 1 - distance / radius;
                        let influence = ratio;
                        if (options.magnetic.clamp === "soft") {
                            influence = ratio * ratio;
                        } else if (options.magnetic.clamp === "hard") {
                            influence = ratio > 0 ? 1 : 0;
                        }

                        const normalizedX = distance > 0 ? dx / distance : 0;
                        const normalizedY = distance > 0 ? dy / distance : 0;
                        targetX = normalizedX * maxOffset * strength * influence;
                        targetY = normalizedY * maxOffset * strength * influence;
                    }
                }

                char.magX += (targetX - char.magX) * lerp;
                char.magY += (targetY - char.magY) * lerp;
                applyCharOffsets(char);

                if (Math.abs(char.magX) > 0.05 || Math.abs(char.magY) > 0.05) {
                    stillActive = true;
                }
            });

            if (magneticActive || stillActive) {
                magneticRaf = window.requestAnimationFrame(animateMagnetic);
            } else {
                magneticRaf = 0;
            }
        };

        const handlePointerMove = (event) => {
            if (!active) {
                return;
            }
            const rect = root.getBoundingClientRect();
            pointerX = event.clientX - rect.left;
            pointerY = event.clientY - rect.top;

            root.style.setProperty("--kng-kt-pointer-x", `${(pointerX / rect.width) * 100}%`);
            root.style.setProperty("--kng-kt-pointer-y", `${(pointerY / rect.height) * 100}%`);

            if (allowLetterMotion && options.trigger === "hover-move") {
                scheduleDrift(true);
            }

            if (allowMagnetic && !magneticRaf) {
                magneticRaf = window.requestAnimationFrame(animateMagnetic);
            }
        };

        const handlePointerEnter = (event) => {
            if (tapMode) {
                return;
            }
            active = true;
            setActive(true);
            if (allowLetterMotion) {
                scheduleDrift(options.trigger === "hover-move");
            }
            if (allowMagnetic) {
                magneticActive = true;
                handlePointerMove(event);
            }
        };

        const handlePointerLeave = () => {
            if (options.trigger === "idle") {
                return;
            }
            if (tapMode) {
                return;
            }
            active = false;
            setActive(false);
            resetDrift();
            magneticActive = false;
            if (allowMagnetic && !magneticRaf) {
                magneticRaf = window.requestAnimationFrame(animateMagnetic);
            }
        };

        const handleFocusIn = () => {
            if (disableHover) {
                return;
            }
            active = true;
            setActive(true);
            if (allowLetterMotion) {
                scheduleDrift(false);
            }
        };

        const handleFocusOut = () => {
            if (options.trigger === "idle") {
                return;
            }
            if (tapMode) {
                return;
            }
            active = false;
            setActive(false);
            resetDrift();
            magneticActive = false;
            if (allowMagnetic && !magneticRaf) {
                magneticRaf = window.requestAnimationFrame(animateMagnetic);
            }
        };

        const handleTap = (event) => {
            if (!tapMode) {
                return;
            }
            if (event.pointerType === "mouse") {
                return;
            }
            active = !active;
            setActive(active);
            if (allowLetterMotion) {
                scheduleDrift(false);
            }
            if (allowMagnetic) {
                magneticActive = active;
                if (active) {
                    handlePointerMove(event);
                }
            }
        };

        root.addEventListener("pointerenter", handlePointerEnter);
        root.addEventListener("pointerleave", handlePointerLeave);
        root.addEventListener("pointermove", handlePointerMove);
        root.addEventListener("focusin", handleFocusIn);
        root.addEventListener("focusout", handleFocusOut);
        root.addEventListener("pointerdown", handleTap);

        let resizeTimer = 0;
        window.addEventListener("resize", () => {
            window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(() => {
                updateBounds();
            }, 150);
        });

        if (!allowMagnetic) {
            magneticActive = false;
        }
    };

    const initWidget = ($scope) => {
        $scope.find(".king-addons-kinetic-text-hover").each(function () {
            initInstance(this);
        });
    };

    $(window).on("elementor/frontend/init", function () {
        if (!window.elementorFrontend) {
            return;
        }
        elementorFrontend.hooks.addAction(
            "frontend/element_ready/king-addons-kinetic-text-hover.default",
            initWidget
        );
    });
})(jQuery);
