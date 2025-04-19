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

                // Responsive logo alignment
                const applyLogoAlignment = () => {
                    $mainMenu.each(function () {
                        const $menu = $(this);
                        const breakpoint = parseInt($menu.data('mobile-breakpoint') || '1024', 10);
                        if (window.innerWidth > breakpoint) {
                            $menu.find('.king-addons-mega-menu-logo').css('justify-content', '');
                        } else {
                            const $logo = $menu.find('.king-addons-mega-menu-logo');
                            if ($logo.hasClass('king-addons-logo-mobile-alignment-left')) {
                                $logo.css('justify-content', 'flex-start');
                            } else if ($logo.hasClass('king-addons-logo-mobile-alignment-center')) {
                                $logo.css('justify-content', 'center');
                            } else if ($logo.hasClass('king-addons-logo-mobile-alignment-right')) {
                                $logo.css('justify-content', 'flex-end');
                            }
                        }
                    });
                };
                $(window).on('resize', applyLogoAlignment);
                applyLogoAlignment();

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
})(jQuery);