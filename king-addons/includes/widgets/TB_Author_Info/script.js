(function($){
    const init = () => {};
    $(window).on('elementor/frontend/init', () => {
        elementorFrontend.hooks.addAction('frontend/element_ready/global', init);
    });
})(jQuery);
