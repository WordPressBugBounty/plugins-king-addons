(function ($) {
    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/king-addons-mega-menu.default',
            function ($scope) {
                const $mainMenu = $scope.find('.king-addons-mega-menu');
                if (!$mainMenu.length) return;

                // Ensure data-dropdown-animation attribute is set
                let animation = $mainMenu.attr('data-dropdown-animation');
                if (!animation) {
                    animation = $mainMenu.data('dropdown-animation') || 'fade';
                    $mainMenu.attr('data-dropdown-animation', animation);
                }

                // Remove previous animation classes and add the current one
                $mainMenu.removeClass(function (i, c) {
                    return (c.match(/king-addons-animation-[^\s]+/g) || []).join(' ');
                });
                if ([
                    'fade-up',
                    'fade-down',
                    'fade-left',
                    'fade-right',
                    'zoom-in',
                    'zoom-out',
                    'fade',
                    'slide',
                    'none'
                ].includes(animation)) {
                    $mainMenu.addClass('king-addons-animation-' + animation);
                }

                // Dropdown open/close logic (do not set any left/right/transform styles)
                $mainMenu.find('.king-addons-menu-items > li').each(function () {
                    const $menuItem = $(this);
                    const $dropdowns = $menuItem.find('> ul.sub-menu, > .king-addons-template-content, > .king-addons-submenu');
                    if ($dropdowns.length) {
                        $menuItem.off('mouseenter mouseleave');
                        $menuItem.on('mouseenter', function () {
                            if (window.innerWidth > 1024) {
                                $dropdowns.each(function () {
                                    kingAddonsDropdownFitToScreen(this);
                                });
                            }
                        });
                        $menuItem.on('mouseleave', function () {
                            $dropdowns.removeClass('king-addons-dropdown-open');
                        });
                    }
                });

                // Full-width dropdown logic (only set width, do not set left/right/transform)
                $mainMenu.filter('.king-addons-dropdown-width-full').each(function () {
                    const $menu = $(this);
                    const $window = $(window);
                    const setDropdownWidth = () => {
                        const windowWidth = $window.width();
                        $menu.find('.king-addons-menu-items > li > ul.sub-menu, .king-addons-menu-items > li > .king-addons-template-content').css('width', windowWidth + 'px');
                    };
                    setDropdownWidth();
                    $window.on('resize', setDropdownWidth);
                });

                // Accessibility: keyboard navigation
                $mainMenu.find('.king-addons-menu-items > li > a').on('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        $(this).trigger('click');
                        const $firstLink = $(this).siblings('.sub-menu, .king-addons-template-content').find('a').first();
                        if ($firstLink.length) {
                            $firstLink.focus();
                        }
                    }
                });

                // Escape key closes dropdowns
                $(document).on('keydown.kingAddonsMegaMenu', function (e) {
                    if (e.key === 'Escape') {
                        $mainMenu.find('.king-addons-menu-items li').removeClass('focus');
                        $mainMenu.find('.sub-menu, .king-addons-template-content').css({ opacity: '', visibility: '' });
                    }
                });

                // ARIA attributes
                $mainMenu.find('.king-addons-menu-items li').each(function () {
                    const $li = $(this);
                    if ($li.has('.sub-menu, .king-addons-template-content').length) {
                        $li.attr('aria-haspopup', 'true');
                        $li.find('> a').attr('aria-expanded', 'false');
                        $li.on('mouseenter focus', function () {
                            $li.find('> a').attr('aria-expanded', 'true');
                        });
                        $li.on('mouseleave blur', function () {
                            $li.find('> a').attr('aria-expanded', 'false');
                        });
                    }
                });



                // Mobile menu toggle logic
                const $mobileToggle = $scope.find('.king-addons-mobile-menu-toggle');
                const $mobileMenu = $scope.find('.king-addons-mobile-menu');
                const $mobileClose = $scope.find('.king-addons-mobile-menu-close');

                $mobileToggle.on('click', function (e) {
                    e.preventDefault();
                    $mobileMenu.slideToggle(250);
                    $mobileToggle.toggleClass('active');
                });

                $mobileClose.on('click', function (e) {
                    e.preventDefault();
                    $mobileMenu.slideUp(250);
                    $mobileToggle.removeClass('active');
                });

                // Mobile menu dropdown logic
                $mobileMenu.find('.menu-item-has-children > a').on('click', function (e) {
                    // Only handle in mobile menu
                    if (window.innerWidth > 1024) return;
                    e.preventDefault();
                    const $parent = $(this).parent();
                    const $submenu = $parent.children('ul.sub-menu, ul.king-addons-submenu, .king-addons-template-content');
                    // Toggle current submenu
                    $submenu.slideToggle(250);
                    $parent.toggleClass('king-addons-mobile-menu__item--open');
                    // Optionally close other open submenus (accordion behavior)
                    $parent.siblings('.menu-item-has-children').removeClass('king-addons-mobile-menu__item--open')
                        .children('ul.sub-menu, ul.king-addons-submenu, .king-addons-template-content').slideUp(250);
                });

                // Initialize center logo positioning
                kingAddonsCenterLogoPositioning($mainMenu);

                // Initialize Lottie animations for logos (with library loading wait)
                waitForLottieAndInitialize($mainMenu);
            }
        );
    });

    /**
     * Force transform: none !important and left: 0 !important for all .sub-menu, .king-addons-submenu, and .king-addons-template-content in mega menu on mobile width
     */
    function kingAddonsMobileMenuTransformReset() {
        const isMobile = window.innerWidth <= 1024;
        const subMenus = document.querySelectorAll('.king-addons-mega-menu .sub-menu, .king-addons-mega-menu .king-addons-submenu, .king-addons-mega-menu .king-addons-template-content');
        subMenus.forEach(function(subMenu) {
            if (isMobile) {
                subMenu.style.setProperty('transform', 'none', 'important');
                subMenu.style.setProperty('left', '0', 'important');
            } else {
                subMenu.style.removeProperty('transform');
                subMenu.style.removeProperty('left');
            }
        });
    }

    function kingAddonsMobileMenuHideOnDesktop() {
        if (window.innerWidth > 1024) {
            document.querySelectorAll('.king-addons-mobile-menu').forEach(function(menu) {
                if (menu.style.display !== 'none') {
                    if (window.jQuery) {
                        jQuery(menu).slideUp(0);
                    } else {
                        menu.style.display = 'none';
                    }
                }
                // Close all open dropdowns inside mobile menu
                menu.querySelectorAll('.menu-item-has-children.king-addons-mobile-menu__item--open').forEach(function(item) {
                    item.classList.remove('king-addons-mobile-menu__item--open');
                });
                menu.querySelectorAll('ul.sub-menu, ul.king-addons-submenu, .king-addons-template-content').forEach(function(sub) {
                    if (window.jQuery) {
                        jQuery(sub).slideUp(0);
                    } else {
                        sub.style.display = 'none';
                    }
                });
            });
            document.querySelectorAll('.king-addons-mobile-menu-toggle.active').forEach(function(toggle) {
                toggle.classList.remove('active');
            });
        }
    }

    window.addEventListener('resize', kingAddonsMobileMenuTransformReset);
    document.addEventListener('DOMContentLoaded', kingAddonsMobileMenuTransformReset);
    window.addEventListener('resize', kingAddonsMobileMenuHideOnDesktop);
    document.addEventListener('DOMContentLoaded', kingAddonsMobileMenuHideOnDesktop);

    /**
     * Ensure dropdown (template, sub-menu, custom submenu) fits inside viewport on desktop
     * Observer is temporarily disconnected to avoid infinite loop
     */
    function kingAddonsDropdownFitToScreen(dropdown) {
        if (window.innerWidth <= 1024) return;
        // Temporarily disconnect observer to avoid loop
        if (dropdown._kingAddonsObserver) dropdown._kingAddonsObserver.disconnect();
        dropdown.style.left = '';
        dropdown.style.right = '';
        dropdown.style.transform = '';
        const rect = dropdown.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        let changed = false;
        if (rect.right > viewportWidth) {
            const overflowRight = rect.right - viewportWidth;
            dropdown.style.left = (dropdown.offsetLeft - overflowRight) + 'px';
            changed = true;
        }
        if (rect.left < 0) {
            dropdown.style.left = (dropdown.offsetLeft - rect.left) + 'px';
            changed = true;
        }
        // Reconnect observer after change
        if (dropdown._kingAddonsObserver) {
            dropdown._kingAddonsObserver.observe(dropdown, { attributes: true, attributeFilter: ['class', 'style'] });
        }
    }

    /**
     * Observe dropdowns and fix their position as soon as they become visible (desktop only)
     */
    function kingAddonsObserveDropdowns() {
        if (window.innerWidth <= 1024) return;
        const dropdowns = document.querySelectorAll('.king-addons-template-content, .sub-menu, .king-addons-submenu');
        dropdowns.forEach(function(dropdown) {
            // Avoid multiple observers
            if (dropdown._kingAddonsObserved) return;
            dropdown._kingAddonsObserved = true;
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (
                        mutation.attributeName === 'class' || mutation.attributeName === 'style'
                    ) {
                        // Check if dropdown is visible
                        const style = window.getComputedStyle(dropdown);
                        if (style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0') {
                            kingAddonsDropdownFitToScreen(dropdown);
                        }
                    }
                });
            });
            dropdown._kingAddonsObserver = observer;
            observer.observe(dropdown, { attributes: true, attributeFilter: ['class', 'style'] });
        });
    }
    window.addEventListener('DOMContentLoaded', kingAddonsObserveDropdowns);
    window.addEventListener('resize', kingAddonsObserveDropdowns);

    /**
     * Handle center logo positioning by inserting logo between menu items
     * Supports responsive positioning (desktop/tablet/mobile)
     */
    function kingAddonsCenterLogoPositioning($mainMenu) {
        const centerLogo = $mainMenu.attr('data-center-logo');
        if (centerLogo !== 'true') return;

        // Get responsive logo positions
        const logoPositionDesktop = $mainMenu.attr('data-logo-position-desktop') || 'left';
        const logoPositionTablet = $mainMenu.attr('data-logo-position-tablet') || logoPositionDesktop;
        const logoPositionMobile = $mainMenu.attr('data-logo-position-mobile') || logoPositionDesktop;

        // Determine current device and position
        let currentPosition = logoPositionDesktop;
        if (window.innerWidth <= 767) {
            currentPosition = logoPositionMobile;
        } else if (window.innerWidth <= 1024) {
            currentPosition = logoPositionTablet;
        }

        // Remove any existing center logo
        $mainMenu.find('.king-addons-center-logo-inserted').remove();
        $mainMenu.removeClass('king-addons-center-logo-active');

        // Only proceed if current position is center
        if (currentPosition !== 'center') return;
        if (window.innerWidth <= 1024) return;

        const splitAt = parseInt($mainMenu.attr('data-split-at')) || 2;
        const $menuItems = $mainMenu.find('.king-addons-menu-items > li');
        const $logoPlaceholder = $mainMenu.find('.king-addons-center-logo-placeholder');
        
        if ($menuItems.length > splitAt && $logoPlaceholder.length) {
            // Clone logo and insert it after the split position
            const $logoClone = $logoPlaceholder.children().first().clone(true, true);

            // Clean any previously injected Lottie DOM from the clone
            // (cloning after Lottie init copies <svg>/<canvas> which would produce duplicates)
            $logoClone.find('.king-addons-lottie-animations').each(function() {
                const $container = jQuery(this);
                // Remove any rendered content and runtime flags
                $container.empty();
                $container.removeData('lottie-initialized');
                $container.removeData('lottie-animation');
            });
            $logoClone.addClass('king-addons-center-logo-inserted');
            
            // Insert logo after the specified menu item
            $menuItems.eq(splitAt - 1).after($logoClone);
            
            // Add special class to menu for center layout
            $mainMenu.addClass('king-addons-center-logo-active');

            // Ensure Lottie animations are initialized for cloned logo, if any
            try {
                // Initialize Lottie only for not-yet-initialized elements to avoid duplicates
                $mainMenu.find('.king-addons-lottie-animations').each(function() {
                    const $el = jQuery(this);
                    if (!$el.data('lottie-initialized')) {
                        kingAddonsInitializeLottieLogos($mainMenu);
                        return false; // one pass is enough; internal init loops through all
                    }
                });
            } catch (e) {}
        }
    }

    /**
     * Check if Lottie library is available under different global names
     */
    function getLottieLibrary() {
        // Check common global names for lottie library
        if (typeof lottie !== 'undefined') {
            return lottie;
        }
        if (typeof window.lottie !== 'undefined') {
            return window.lottie;
        }
        if (typeof window.Lottie !== 'undefined') {
            return window.Lottie;
        }
        if (typeof window.bodymovin !== 'undefined') {
            return window.bodymovin;
        }
        return null;
    }

    /**
     * Wait for Lottie library to load and then initialize animations
     */
    function waitForLottieAndInitialize($mainMenu) {
        let attempts = 0;
        const maxAttempts = 50; // 5 seconds max wait
        
        function checkLottie() {
            const lottieLib = getLottieLibrary();
            if (lottieLib) {
                console.log('Lottie library found, initializing animations');
                kingAddonsInitializeLottieLogos($mainMenu);
            } else if (attempts < maxAttempts) {
                attempts++;
                setTimeout(checkLottie, 100);
            } else {
                console.log('Lottie library not loaded after 5 seconds');
                // Try one more time with alternative approach
                tryAlternativeLottieInit($mainMenu);
            }
        }
        
        checkLottie();
    }

    /**
     * Alternative initialization approach for edge cases
     */
    function tryAlternativeLottieInit($mainMenu) {
        // Wait for window load event
        if (document.readyState === 'complete') {
            setTimeout(function() {
                const lottieLib = getLottieLibrary();
                if (lottieLib) {
                    console.log('Lottie library found on window load, initializing');
                    kingAddonsInitializeLottieLogos($mainMenu);
                }
            }, 500);
        } else {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    const lottieLib = getLottieLibrary();
                    if (lottieLib) {
                        console.log('Lottie library found after window load, initializing');
                        kingAddonsInitializeLottieLogos($mainMenu);
                    }
                }, 500);
            });
        }
    }

    /**
     * Initialize Lottie animations for logos
     */
    function kingAddonsInitializeLottieLogos($mainMenu) {
        const $lottieElements = $mainMenu.find('.king-addons-lottie-animations');
        
        if ($lottieElements.length === 0) return;
        
        $lottieElements.each(function() {
            const $element = $(this);
            const settings = $element.data('settings');
            const jsonUrl = $element.data('json-url');
            
            if (!jsonUrl) return;
            
            // Skip if already initialized
            if ($element.data('lottie-initialized')) return;
            
            try {
                // Check if lottie is available
                const lottieLib = getLottieLibrary();
                if (lottieLib) {
                    const animationSettings = {
                        container: this,
                        renderer: settings.lottie_renderer || 'svg',
                        loop: settings.loop === 'yes',
                        autoplay: settings.autoplay === 'yes',
                        path: jsonUrl,
                        rendererSettings: {
                            preserveAspectRatio: 'xMidYMid slice'
                        }
                    };

                    const animation = lottieLib.loadAnimation(animationSettings);
                    
                    // Store animation instance
                    $element.data('lottie-animation', animation);
                    $element.data('lottie-initialized', true);
                    
                    // Set speed if specified
                    if (settings.speed) {
                        animation.setSpeed(parseFloat(settings.speed));
                    }
                    
                    // Set direction if reversed
                    if (settings.reverse === 'true') {
                        animation.setDirection(-1);
                    }
                    
                    // Handle triggers
                    if (settings.trigger === 'hover') {
                        const isInNavigation = $element.closest('.king-addons-mega-menu').length > 0;
                        $element.on('mouseenter', function() {
                            animation.play();
                        });
                        $element.on('mouseleave', function() {
                            // Do not pause on mouseleave while a King Addons popup is open
                            if (isInNavigation && document.querySelector('.king-addons-pb-template-popup.king-addons-pb-popup-open')) {
                                animation.play();
                                return;
                            }
                            animation.pause();
                        });
                    } else if (settings.trigger === 'viewport') {
                        // For navigation logos, viewport trigger should be more permissive
                        // Check if element is in navigation context
                        const isInNavigation = $element.closest('.king-addons-mega-menu').length > 0;
                        
                        if (isInNavigation) {
                            // For navigation logos, just start playing and keep playing
                            // Navigation is typically always visible
                            animation.play();
                        } else {
                            // For non-navigation elements, use standard viewport detection
                            const observer = new IntersectionObserver((entries) => {
                                entries.forEach(entry => {
                                    if (entry.isIntersecting) {
                                        animation.play();
                                    } else {
                                        animation.pause();
                                    }
                                });
                            });
                            observer.observe(this);
                        }
                    }
                    
                    console.log('Lottie animation initialized for:', jsonUrl);
                } else {
                    console.log('Lottie library not available');
                }
            } catch (error) {
                console.log('Lottie initialization failed:', error);
            }
        });
    }

    // Handle center logo repositioning on window resize
    $(window).on('resize', function() {
        $('.king-addons-mega-menu[data-center-logo="true"]').each(function() {
            const $menu = $(this);
            kingAddonsCenterLogoPositioning($menu);
            // Ensure cloned logo does not carry previous rendered SVG/canvas
            $menu.find('.king-addons-center-logo-inserted .king-addons-lottie-animations').each(function() {
                const $container = jQuery(this);
                if (!$container.data('lottie-initialized')) {
                    $container.empty();
                }
            });
            // Re-initialize Lottie only for missing instances
            try {
                $menu.find('.king-addons-lottie-animations').each(function() {
                    const $el = jQuery(this);
                    if (!$el.data('lottie-initialized')) {
                        kingAddonsInitializeLottieLogos($menu);
                        return false;
                    }
                });
            } catch (e) {}
        });
    });

    // Additional Lottie initialization fallback
    // This handles cases where Lottie loads after our initial check
    $(document).ready(function() {
        // Wait a bit for all scripts to load, then try Lottie initialization again
        setTimeout(function() {
            $('.king-addons-mega-menu').each(function() {
                const $menu = $(this);
                const $lottieElements = $menu.find('.king-addons-lottie-animations');
                
                // Only initialize elements that haven't been initialized yet
                $lottieElements.each(function() {
                    const $element = $(this);
                    const lottieLib = getLottieLibrary();
                    if (!$element.data('lottie-initialized') && lottieLib) {
                        kingAddonsInitializeLottieLogos($menu);
                    }
                });
            });
        }, 1000);
    });

    // Resume navigation logo Lottie animations when a King Addons popup opens
    function kingAddonsKeepNavLottieRunning() {
        const resumeAll = () => {
            document.querySelectorAll('.king-addons-mega-menu .king-addons-lottie-animations').forEach(function(el) {
                const $el = jQuery(el);
                const anim = $el.data('lottie-animation');
                if (anim) {
                    anim.play();
                }
            });
        };
        // Resume when class changes on popups (open/close)
        const popups = document.querySelectorAll('.king-addons-pb-template-popup');
        popups.forEach(function(popup) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        resumeAll();
                    }
                });
            });
            observer.observe(popup, { attributes: true, attributeFilter: ['class'] });
        });
        // Resume on resize (desktop breakpoint changes)
        window.addEventListener('resize', function() {
            resumeAll();
        });
        // Resume on scroll (in case of transient viewport observers)
        window.addEventListener('scroll', function() {
            resumeAll();
        }, { passive: true });
    }
    document.addEventListener('DOMContentLoaded', kingAddonsKeepNavLottieRunning);

})(jQuery);