"use strict";
(function ($) {
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/king-addons-image-accordion.default', function ($scope) {
            elementorFrontend.elementsHandler.addHandler(elementorModules.frontend.handlers.Base.extend({
                onInit: function onInit() {
                    let $scope = this.$element;
                    

                    var settings = JSON.parse($scope.find('.king-addons-img-accordion-media-hover').attr('data-settings'));

                    
                    var MediaWrap = $scope.find('.king-addons-image-accordion');
                    
                    var lightboxSettings = $scope.find('.king-addons-image-accordion').attr('lightbox') ? JSON.parse($scope.find('.king-addons-image-accordion').attr('lightbox')) : '';

                    var thisTargetHasClass = false;

                    if ($scope.find('.king-addons-image-accordion-wrap').hasClass('king-addons-acc-no-column')) {
                        if (!$scope.hasClass('king-addons-image-accordion-row')) ;
                        $scope.removeClass('king-addons-image-accordion-column').addClass('king-addons-image-accordion-row');
                        $scope.find('.king-addons-image-accordion').css('flex-direction', 'row');
                    }

                    if ('' !== lightboxSettings) {

                        
                        MediaWrap.lightGallery(lightboxSettings);

                        
                        MediaWrap.on('onAfterOpen.lg', function () {
                            if ($('.lg-outer').find('.lg-thumb-item').length) {
                                $('.lg-outer').find('.lg-thumb-item').each(function () {
                                    var imgSrc = $(this).find('img').attr('src'),
                                        newImgSrc = imgSrc,
                                        extIndex = imgSrc.lastIndexOf('.'),
                                        imgExt = imgSrc.slice(extIndex),
                                        cropIndex = imgSrc.lastIndexOf('-'),
                                        cropSize = /\d{3,}x\d{3,}/.test(imgSrc.substring(extIndex, cropIndex)) ? imgSrc.substring(extIndex, cropIndex) : false;

                                    if (42 <= imgSrc.substring(extIndex, cropIndex).length) {
                                        cropSize = '';
                                    }

                                    if (cropSize !== '') {
                                        if (false !== cropSize) {
                                            newImgSrc = imgSrc.replace(cropSize, '-150x150');
                                        } else {
                                            newImgSrc = [imgSrc.slice(0, extIndex), '-150x150', imgSrc.slice(extIndex)].join('');
                                        }
                                    }

                                    
                                    $(this).find('img').attr('src', newImgSrc);

                                    if (false == cropSize || '-450x450' === cropSize) {
                                        $(this).find('img').attr('src', imgSrc);
                                    }
                                });
                            }
                        });

                        
                        $scope.find('.king-addons-image-accordion').on('onAferAppendSlide.lg, onAfterSlide.lg', function (event, prevIndex, index) {
                            var lightboxControls = $('#lg-actual-size, #lg-zoom-in, #lg-zoom-out, #lg-download'),
                                lightboxDownload = $('#lg-download').attr('href');

                            if ($('#lg-download').length) {
                                if (-1 === lightboxDownload.indexOf('wp-content')) {
                                    lightboxControls.addClass('king-addons-hidden-element');
                                } else {
                                    lightboxControls.removeClass('king-addons-hidden-element');
                                }
                            }

                            
                            if ('' === lightboxSettings.autoplay) {
                                $('.lg-autoplay-button').css({
                                    'width': '0',
                                    'height': '0',
                                    'overflow': 'hidden'
                                });
                            }
                        });

                    }

                    MediaWrap.css('cursor', 'pointer');

                    

                    var accordionItem = $scope.find('.king-addons-image-accordion-item');

                    
                    function mediaHoverLink() {
                        if (!(!!$('body').hasClass('elementor-editor-active'))) {

                            $scope.find('.king-addons-img-accordion-media-hover').on('click', function (event) {
                                var thisSettings = event.target.className.includes('king-addons-img-accordion-media-hover') ? JSON.parse($(this).attr('data-settings')) : JSON.parse($(this).closest('.king-addons-img-accordion-media-hover').attr('data-settings'));

                                if (!$(event.target).hasClass('king-addons-img-accordion-item-lightbox') && 0 === $(event.target).closest('.king-addons-img-accordion-item-lightbox').length) {
                                    var itemUrl = thisSettings.activeItem.overlayLink;
                                    if (itemUrl != '') {

                                        if ('_blank' === thisSettings.activeItem.overlayLinkTarget) {
                                            window.open(itemUrl, '_blank').focus();
                                        } else {
                                            window.location.href = itemUrl;
                                        }

                                    }
                                }
                            });
                        }
                    }

                    if ('hover' === settings.activeItem.interaction) {

                        mediaHoverLink();

                        accordionItem.on('mouseenter', function () {
                            accordionItem.removeClass('king-addons-image-accordion-item-grow');
                            accordionItem.find('.king-addons-animation-wrap').removeClass('king-addons-animation-wrap-active');
                            $(this).addClass('king-addons-image-accordion-item-grow');
                            $(this).find('.king-addons-animation-wrap').addClass('king-addons-animation-wrap-active');
                        });

                        accordionItem.on('mouseleave', function () {
                            $(this).removeClass('king-addons-image-accordion-item-grow');
                            $(this).find('.king-addons-animation-wrap').removeClass('king-addons-animation-wrap-active');
                        });

                    } else if ('click' === settings.activeItem.interaction) {
                        $scope.find('.king-addons-img-accordion-media-hover').removeClass('king-addons-animation-wrap');
                        accordionItem.on('click', '.king-addons-img-accordion-media-hover', function (event) {
                            thisTargetHasClass = event.target.className.includes('king-addons-img-accordion-media-hover') ? event.target.className.includes('king-addons-animation-wrap-active') : $(this).closest('.king-addons-img-accordion-media-hover').hasClass('king-addons-animation-wrap-active');
                            if (thisTargetHasClass && !($( 'body' ).hasClass( 'elementor-editor-active' ) ? true : false)) {
                                var thisSettings = event.target.className.includes('king-addons-img-accordion-media-hover') ? JSON.parse($(this).attr('data-settings')) : JSON.parse($(this).closest('.king-addons-img-accordion-media-hover').attr('data-settings'));

                                if (!$(event.target).hasClass('king-addons-img-accordion-item-lightbox') && 0 === $(event.target).closest('.king-addons-img-accordion-item-lightbox').length) {
                                    var itemUrl = thisSettings.activeItem.overlayLink;
                                    if (itemUrl != '') {
                                        if ('_blank' === thisSettings.activeItem.overlayLinkTarget) {
                                            window.open(itemUrl, '_blank').focus();
                                        } else {
                                            window.location.href = itemUrl;
                                        }
                                    }
                                }
                            } else {
                                $scope.find('.king-addons-img-accordion-media-hover').removeClass('king-addons-animation-wrap').removeClass('king-addons-animation-wrap-active');
                                accordionItem.removeClass('king-addons-image-accordion-item-grow');
                                $(this).closest('.king-addons-image-accordion-item').addClass('king-addons-image-accordion-item-grow');
                                $(this).closest('.king-addons-img-accordion-media-hover').addClass('king-addons-animation-wrap-active');
                            }
                        });
                    } else {
                        $scope.find('.king-addons-img-accordion-media-hover').removeClass('king-addons-animation-wrap');
                    }

                    accordionItem.each(function () {
                        if ($(this).index() === settings.activeItem.defaultActive - 1) {
                            if ('click' === settings.activeItem.interaction) {
                                setTimeout(() => {
                                    $(this).find('.king-addons-img-accordion-media-hover').trigger('click');
                                }, 400);
                            } else {
                                setTimeout(() => {
                                    $(this).find('.king-addons-img-accordion-media-hover').trigger('mouseenter');
                                }, 400);
                            }
                        }
                    });

                    $scope.find('.king-addons-image-accordion-wrap').css('opacity', 1);


                    
                },
            }), {
                $element: $scope
            });
        });
    });
})(jQuery);