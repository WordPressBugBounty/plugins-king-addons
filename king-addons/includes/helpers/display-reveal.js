(function ($) {
    'use strict';
    $(document).on('click.kingDisplayReveal', '.ka-display-reveal-trigger button', function () {
        const $trigger = $(this).closest('.ka-display-reveal-trigger');
        const $content = $trigger.nextAll('[data-ka-display-reveal]').first();
        if (!$content.length) { return; }
        $content.removeClass('ka-display-await-click');
        $trigger.remove();
        // These nodes already ran their Elementor handlers during page load.
        // Rebinding them would duplicate click handlers on accordions and tabs.
        $content.find('.swiper, .swiper-container').each(function () { if (this.swiper) { this.swiper.update(); } });
        window.dispatchEvent(new Event('resize'));
    });
}(jQuery));
