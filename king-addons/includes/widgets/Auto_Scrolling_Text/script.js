/**
 * Auto Scrolling Text — keeps the marquee running without a gap.
 *
 * The track scrolls left by exactly the width of one copy of the content. For
 * that to look continuous, the track has to hold enough copies that the ones
 * still to the right cover the visible strip at the moment the animation
 * resets. One copy alone leaves a growing empty space and then jumps back,
 * which is what the widget used to do.
 *
 * Rebuilding the copies forces layout and paint on a wide element, so it is
 * done only when the number of copies actually has to change — otherwise a
 * resize (which fires constantly on mobile as the address bar hides) would
 * show up as a stutter in the animation.
 */
(function () {
    'use strict';

    var GROUP = 'king-addons-auto-scrolling-text-group';

    function requiredCopies(groupWidth, visibleWidth) {
        // One copy scrolls out of view; the rest have to cover the strip.
        return Math.max(2, Math.ceil(visibleWidth / groupWidth) + 1);
    }

    function fill(track) {
        var strip = track.parentElement;
        if (!strip) {
            return;
        }

        var original = track.querySelector('.' + GROUP);
        if (!original) {
            return;
        }

        // Each copy is its own flex item, so the first one can be measured
        // without disturbing the copies already in place.
        var groupWidth = original.getBoundingClientRect().width;
        var visibleWidth = strip.getBoundingClientRect().width;
        if (groupWidth <= 0 || visibleWidth <= 0) {
            return;
        }

        var wanted = requiredCopies(groupWidth, visibleWidth);
        var current = track.querySelectorAll('.' + GROUP).length;
        if (current === wanted) {
            return;                      // nothing to do: leave the animation alone
        }

        if (current > wanted) {
            var extra = track.querySelectorAll('.' + GROUP);
            for (var i = extra.length - 1; i >= wanted; i--) {
                extra[i].remove();
            }
        } else {
            var fragment = document.createDocumentFragment();
            for (var c = current; c < wanted; c++) {
                var clone = original.cloneNode(true);
                // A screen reader should hear the message once, not once per copy.
                clone.setAttribute('aria-hidden', 'true');
                fragment.appendChild(clone);
            }
            track.appendChild(fragment);
        }

        // Shifting by one copy lands the next copy exactly where this one began.
        track.style.setProperty('--king-addons-marquee-shift', (-100 / wanted) + '%');
    }

    function observe(track) {
        if (track.kingAddonsMarqueeWatched) {
            return;
        }
        track.kingAddonsMarqueeWatched = true;

        var strip = track.parentElement;
        if (strip && window.ResizeObserver) {
            // Reacts to the strip actually changing width, unlike window resize,
            // which also fires on mobile scroll and on every orientation nudge.
            new ResizeObserver(function () { fill(track); }).observe(strip);
        }
    }

    function watchAll(root) {
        var scope = root && root.querySelectorAll ? root : document;
        Array.prototype.forEach.call(
            scope.querySelectorAll('.king-addons-auto-scrolling-text-wrapper'),
            function (track) {
                fill(track);        // cheap: returns straight away when nothing changed
                observe(track);     // one-time
            }
        );
    }

    function onReady() {
        watchAll(document);

        // Safety net for anything ResizeObserver does not catch (and for browsers
        // without it). fill() measures and returns when nothing has to change,
        // so this costs a measurement and never touches the DOM on its own.
        var timer = null;
        window.addEventListener('resize', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { watchAll(document); }, 200);
        });

        // Web fonts land after first paint and change how wide a copy is.
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(function () { watchAll(document); });
        }
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        onReady();
    } else {
        document.addEventListener('DOMContentLoaded', onReady);
    }

    /**
     * Re-measure when Elementor re-renders the widget, which it does on every
     * edit in the editor. Elementor fires this through jQuery, so it has to be
     * bound with jQuery — a native listener never hears it.
     */
    function bindElementor() {
        if (!window.elementorFrontend || !elementorFrontend.hooks) {
            return false;
        }
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/king-addons-auto-scrolling-text.default',
            function ($scope) { watchAll($scope && $scope[0] ? $scope[0] : document); }
        );
        return true;
    }

    if (window.jQuery) {
        // Covers both orders: Elementor still to start, or already started.
        if (!bindElementor()) {
            jQuery(window).on('elementor/frontend/init', bindElementor);
        }
    }
}());
