(function () {
    'use strict';

    var SVG_NS = 'http://www.w3.org/2000/svg';

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function quadAt(a, b, c, t) {
        var omt = 1 - t;
        return (omt * omt * a) + (2 * omt * t * b) + (t * t * c);
    }

    function pointToViewBox(point, rect, viewBoxWidth, viewBoxHeight) {
        return {
            x: (point.x / rect.width) * viewBoxWidth,
            y: (point.y / rect.height) * viewBoxHeight,
        };
    }

    function getLocalRect(el, containerRect) {
        var r = el.getBoundingClientRect();
        return {
            left: r.left - containerRect.left,
            right: r.right - containerRect.left,
            top: r.top - containerRect.top,
            bottom: r.bottom - containerRect.top,
            width: r.width,
            height: r.height,
            cx: (r.left - containerRect.left) + (r.width / 2),
            cy: (r.top - containerRect.top) + (r.height / 2),
        };
    }

    function createCircle(cx, cy, r) {
        var circle = document.createElementNS(SVG_NS, 'circle');
        circle.setAttribute('class', 'ka-values-circle-dot');
        circle.setAttribute('cx', cx.toFixed(2));
        circle.setAttribute('cy', cy.toFixed(2));
        circle.setAttribute('r', r.toFixed(2));
        return circle;
    }

    function drawDottedCurve(targetGroup, rect, viewBox, startPoint, controlPoint, endPoint, baseStep, dotRSmall, dotRLarge) {
        var start = pointToViewBox(startPoint, rect, viewBox.width, viewBox.height);
        var ctrl = pointToViewBox(controlPoint, rect, viewBox.width, viewBox.height);
        var end = pointToViewBox(endPoint, rect, viewBox.width, viewBox.height);

        var estimateLength = Math.hypot(end.x - start.x, end.y - start.y) * 1.08;
        var step = clamp(baseStep, 20, 34);
        var dotCount = clamp(Math.round(estimateLength / step), 5, 13);

        for (var i = 0; i < dotCount; i++) {
            var t = dotCount === 1 ? 0.5 : i / (dotCount - 1);
            var x = quadAt(start.x, ctrl.x, end.x, t);
            var y = quadAt(start.y, ctrl.y, end.y, t);
            // Pattern: small, small, BIG — repeating every 3 dots
            var radius = (i % 3 === 2) ? dotRLarge : dotRSmall;
            targetGroup.appendChild(createCircle(x, y, radius));
        }
    }

    function renderConnectors(widgetRoot) {
        var svgLayer = widgetRoot.querySelector('.king-addons-values-circle-svg-layer');
        var svg = svgLayer ? svgLayer.querySelector('svg') : null;
        if (!svg) return;

        var connectorsGroup = svg.querySelector('.ka-values-circle-connectors-dynamic');
        if (!connectorsGroup) return;

        var topLeft = widgetRoot.querySelector('.king-addons-values-circle-item--top-left');
        var topRight = widgetRoot.querySelector('.king-addons-values-circle-item--top-right');
        var middleLeft = widgetRoot.querySelector('.king-addons-values-circle-item--middle-left');
        var middleRight = widgetRoot.querySelector('.king-addons-values-circle-item--middle-right');
        var bottomLeft = widgetRoot.querySelector('.king-addons-values-circle-item--bottom-left');
        var bottomRight = widgetRoot.querySelector('.king-addons-values-circle-item--bottom-right');

        if (!topLeft || !topRight || !middleLeft || !middleRight || !bottomLeft || !bottomRight) {
            return;
        }

        var wrapRect = widgetRoot.getBoundingClientRect();
        if (!wrapRect.width || !wrapRect.height) {
            return;
        }

        var tl = getLocalRect(topLeft, wrapRect);
        var tr = getLocalRect(topRight, wrapRect);
        var ml = getLocalRect(middleLeft, wrapRect);
        var mr = getLocalRect(middleRight, wrapRect);
        var bl = getLocalRect(bottomLeft, wrapRect);
        var br = getLocalRect(bottomRight, wrapRect);

        var viewBox = svg.viewBox && svg.viewBox.baseVal
            ? { width: svg.viewBox.baseVal.width, height: svg.viewBox.baseVal.height }
            : { width: 1000, height: 860 };

        connectorsGroup.innerHTML = '';

        // Global gap from block edges (px)
        var gap     = parseFloat(widgetRoot.dataset.connGap)   || 20;
        var dotRS   = parseFloat(widgetRoot.dataset.dotRSmall) || 5;
        var dotRL   = parseFloat(widgetRoot.dataset.dotRLarge) || 9.5;

        // Read per-connector settings from wrapper data attributes
        function connData(name) {
            var d = widgetRoot.dataset;
            return {
                bend:   parseFloat(d['conn' + name + 'Bend'])   || 0,
                startX: parseFloat(d['conn' + name + 'StartX']) || 0,
                startY: parseFloat(d['conn' + name + 'StartY']) || 0,
                endX:   parseFloat(d['conn' + name + 'EndX'])   || 0,
                endY:   parseFloat(d['conn' + name + 'EndY'])   || 0,
            };
        }

        // camelCase keys match data-conn-{id}-{prop} HTML attributes
        var cTop         = connData('Top');
        var cBottom      = connData('Bottom');
        var cLeftTop     = connData('LeftTop');
        var cLeftBottom  = connData('LeftBottom');
        var cRightTop    = connData('RightTop');
        var cRightBottom = connData('RightBottom');

        // ── Top arc: tl right-edge → tr left-edge ────────────────────
        var topStart  = { x: tl.right + gap + cTop.startX, y: tl.cy + cTop.startY };
        var topEnd    = { x: tr.left  - gap + cTop.endX,   y: tr.cy + cTop.endY   };
        var topCtrlY  = Math.min(topStart.y, topEnd.y) - cTop.bend;

        // ── Bottom arc: bl right-edge → br left-edge ─────────────────
        var botStart  = { x: bl.right + gap + cBottom.startX, y: bl.cy + cBottom.startY };
        var botEnd    = { x: br.left  - gap + cBottom.endX,   y: br.cy + cBottom.endY   };
        var botCtrlY  = Math.max(botStart.y, botEnd.y) + cBottom.bend;

        // ── Left-Top arc: tl bottom → ml top ─────────────────────────
        var ltStart  = { x: tl.cx + cLeftTop.startX, y: tl.bottom + gap + cLeftTop.startY };
        var ltEnd    = { x: ml.cx + cLeftTop.endX,   y: ml.top    - gap + cLeftTop.endY   };
        var ltCtrlX  = Math.min(ltStart.x, ltEnd.x) - cLeftTop.bend;

        // ── Left-Bottom arc: ml bottom → bl top ──────────────────────
        var lbStart  = { x: ml.cx + cLeftBottom.startX, y: ml.bottom + gap + cLeftBottom.startY };
        var lbEnd    = { x: bl.cx + cLeftBottom.endX,   y: bl.top    - gap + cLeftBottom.endY   };
        var lbCtrlX  = Math.min(lbStart.x, lbEnd.x) - cLeftBottom.bend;

        // ── Right-Top arc: tr bottom → mr top ────────────────────────
        var rtStart  = { x: tr.cx + cRightTop.startX, y: tr.bottom + gap + cRightTop.startY };
        var rtEnd    = { x: mr.cx + cRightTop.endX,   y: mr.top    - gap + cRightTop.endY   };
        var rtCtrlX  = Math.max(rtStart.x, rtEnd.x) + cRightTop.bend;

        // ── Right-Bottom arc: mr bottom → br top ─────────────────────
        var rbStart  = { x: mr.cx + cRightBottom.startX, y: mr.bottom + gap + cRightBottom.startY };
        var rbEnd    = { x: br.cx + cRightBottom.endX,   y: br.top    - gap + cRightBottom.endY   };
        var rbCtrlX  = Math.max(rbStart.x, rbEnd.x) + cRightBottom.bend;

        drawDottedCurve(connectorsGroup, wrapRect, viewBox, topStart,
            { x: (topStart.x + topEnd.x) / 2, y: topCtrlY }, topEnd, 27, dotRS, dotRL);

        drawDottedCurve(connectorsGroup, wrapRect, viewBox, botStart,
            { x: (botStart.x + botEnd.x) / 2, y: botCtrlY }, botEnd, 27, dotRS, dotRL);

        drawDottedCurve(connectorsGroup, wrapRect, viewBox, ltStart,
            { x: ltCtrlX, y: (ltStart.y + ltEnd.y) / 2 }, ltEnd, 25, dotRS, dotRL);

        drawDottedCurve(connectorsGroup, wrapRect, viewBox, lbStart,
            { x: lbCtrlX, y: (lbStart.y + lbEnd.y) / 2 }, lbEnd, 25, dotRS, dotRL);

        drawDottedCurve(connectorsGroup, wrapRect, viewBox, rtStart,
            { x: rtCtrlX, y: (rtStart.y + rtEnd.y) / 2 }, rtEnd, 25, dotRS, dotRL);

        drawDottedCurve(connectorsGroup, wrapRect, viewBox, rbStart,
            { x: rbCtrlX, y: (rbStart.y + rbEnd.y) / 2 }, rbEnd, 25, dotRS, dotRL);
    }

    function isMobileLayout() {
        return window.innerWidth <= 767;
    }

    function renderMobileConnectors(widgetRoot) {
        var groups = widgetRoot.querySelectorAll('.ka-vcm-dots');
        if (!groups.length) return;

        var dotRS   = parseFloat(widgetRoot.dataset.mobileDotRSmall) || 5.5;
        var dotRL   = parseFloat(widgetRoot.dataset.mobileDotRLarge) || 8;
        var edgeGap = parseFloat(widgetRoot.dataset.mobileConnGap)   || 6;
        var stepPx  = parseFloat(widgetRoot.dataset.mobileDotStep)   || 28;

        groups.forEach(function (dotsGroup) {
            dotsGroup.innerHTML = '';
            var svg = dotsGroup.closest('svg');
            if (!svg) return;

            var rect = svg.getBoundingClientRect();
            var renderedWidth = rect.width || 50;
            var renderedHeight = rect.height || 50;

            // Keep SVG coordinate space equal to rendered size,
            // so connector length truly grows when CSS height grows.
            svg.setAttribute('viewBox', '0 0 ' + renderedWidth + ' ' + renderedHeight);

            var vb = { width: renderedWidth, height: renderedHeight };

            var cx      = vb.width / 2;
            var startY  = dotRL + edgeGap;
            var endY    = vb.height - dotRL - edgeGap;
            var length  = endY - startY;
            if (length <= 0) return; // no room for dots
            var dotCount = clamp(Math.round(length / stepPx), 1, 18);

            for (var i = 0; i < dotCount; i++) {
                var t = (dotCount === 1) ? 0.5 : i / (dotCount - 1);
                var y = startY + length * t;
                var r = (i % 3 === 2) ? dotRL : dotRS;
                dotsGroup.appendChild(createCircle(cx, y, r));
            }
        });
    }

    function scheduleRender(widgetRoot) {
        if (widgetRoot.__kaValuesCircleRaf) {
            cancelAnimationFrame(widgetRoot.__kaValuesCircleRaf);
        }

        widgetRoot.__kaValuesCircleRaf = requestAnimationFrame(function () {
            if (isMobileLayout()) {
                renderMobileConnectors(widgetRoot);
            } else {
                renderConnectors(widgetRoot);
            }
        });
    }

    function initCenterLottie(widgetRoot) {
        // Init ALL lottie divs (desktop centre + mobile view duplicate)
        var els = widgetRoot.querySelectorAll('.king-addons-values-circle-lottie');
        if (!els.length) return;

        var lottieLib = (typeof lottie !== 'undefined') ? lottie
            : (typeof window.lottie !== 'undefined') ? window.lottie
            : (typeof window.Lottie !== 'undefined') ? window.Lottie
            : null;
        if (!lottieLib) return;

        els.forEach(function (el) {
            if (el.__kaLottieInited) return;

            var cfg = {};
            try { cfg = JSON.parse(el.getAttribute('data-settings') || '{}'); } catch (e) {}

            var anim = lottieLib.loadAnimation({
                container: el,
                path:      el.getAttribute('data-json-url'),
                renderer:  cfg.renderer  || 'svg',
                loop:      cfg.loop      !== 'no',
                autoplay:  cfg.autoplay  !== 'no',
            });
            anim.setSpeed(parseFloat(cfg.speed) || 1);
            if (cfg.reverse === 'yes') anim.setDirection(-1);
            el.__kaLottieInited = true;
        });
    }

    function initWidget(widgetRoot) {
        if (!widgetRoot) return;
        initCenterLottie(widgetRoot);
        scheduleRender(widgetRoot);

        if (!widgetRoot.__kaValuesCircleResizeBound) {
            widgetRoot.__kaValuesCircleResizeBound = true;
            window.addEventListener('resize', function () {
                scheduleRender(widgetRoot);
            }, { passive: true });
        }

        if (!widgetRoot.__kaValuesCircleObserver) {
            var observer = new MutationObserver(function () {
                scheduleRender(widgetRoot);
            });

            observer.observe(widgetRoot, {
                attributes: true,
                attributeFilter: ['style', 'class'],
                childList: true,
                subtree: true,
            });

            widgetRoot.__kaValuesCircleObserver = observer;
        }
    }

    function initAll() {
        var widgets = document.querySelectorAll('.king-addons-values-circle-wrap');
        widgets.forEach(initWidget);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    window.addEventListener('load', initAll);

    if (typeof window.elementorFrontend !== 'undefined') {
        window.addEventListener('elementor/frontend/init', function () {
            if (!window.elementorFrontend.hooks) return;

            window.elementorFrontend.hooks.addAction(
                'frontend/element_ready/king-addons-values-circle-infographic.default',
                function ($scope) {
                    var widgetRoot = $scope && $scope[0]
                        ? $scope[0].querySelector('.king-addons-values-circle-wrap')
                        : null;

                    if (widgetRoot) {
                        initWidget(widgetRoot);
                    }
                }
            );
        });
    }
})();
