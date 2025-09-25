/**
 * Dynamic Posts Grid Widget JavaScript
 * King Addons for Elementor
 */

(function ($) {
    'use strict';

    $(window).on("elementor/frontend/init", () => {
        elementorFrontend.hooks.addAction("frontend/element_ready/king-addons-dynamic-posts-grid.default", ($scope) => {
            // Check if this is PRO mode by looking at widget-mode data attribute
            const $wrapper = $scope.find('.king-addons-dpg-wrapper');
            
            // Prevent double initialization
            if ($wrapper.data('king-addons-initialized')) {
                return;
            }
            $wrapper.data('king-addons-initialized', true);
            
            const widgetMode = $wrapper.data('widget-mode');
            const isPro = widgetMode === 'custom_cpt';
            
            const gridHandler = {
                init() {
                    this.wrapper = $scope.find('.king-addons-dpg-wrapper');
                    this.grid = $scope.find('.king-addons-dpg-grid');
                    this.filterBar = $scope.find('.king-addons-dpg-filter-bar');
                    this.filterSelect = $scope.find('.king-addons-dpg-posts-filter');
                    this.searchInput = $scope.find('.king-addons-dpg-posts-search');
                    this.searchBtn = $scope.find('.king-addons-dpg-search-btn');
                    this.loadMoreBtn = $scope.find('.king-addons-dpg-load-more-btn');
                    this.pagination = $scope.find('.king-addons-dpg-pagination');
                    this.loadingDiv = $scope.find('.king-addons-dpg-pagination-loading');
                    this.finishDiv = $scope.find('.king-addons-dpg-pagination-finish');

                    this.settings = this.getSettings();
                    this.currentPage = 1;
                    this.isLoading = false;
                    this.currentFilter = '*';
                    this.currentSearch = '';

                    this.bindEvents();
                    this.initIsotope();

                    // Prepare CPT icon map for client-side application after AJAX
                    this.cptIcons = {};
                    try {
                        const raw = this.settings.cptIconsRaw || '';
                        this.cptIcons = raw ? JSON.parse(raw) : {};
                    } catch (e) {
                        this.cptIcons = {};
                    }
                },

                getSettings() {
                    return {
                        widgetId: this.wrapper.data('widget-id'),
                        postsPerPage: this.wrapper.data('posts-per-page'),
                        postTypes: this.wrapper.data('post-types'),
                        orderby: this.wrapper.data('orderby'),
                        order: this.wrapper.data('order'),
                        filterTaxonomy: this.wrapper.data('filter-taxonomy'),
                        showExcerpt: this.wrapper.data('show-excerpt'),
                        cardClickable: this.wrapper.data('card-clickable'),
                        cptActionsRaw: this.wrapper.attr('data-cpt-actions') || '',
                        cptIconsRaw: this.wrapper.attr('data-cpt-icons') || ''
                    };
                },

                bindEvents() {
                    // Filter dropdown change
                    this.filterSelect.on('change', (e) => {
                        this.currentFilter = $(e.target).val();
                        this.currentPage = 1;
                        this.filterAndSearch();
                    });

                    // Search input events
                    this.searchInput.on('keyup', this.debounce((e) => {
                        this.currentSearch = $(e.target).val();
                        this.currentPage = 1;
                        this.filterAndSearch();
                    }, 500));

                    this.searchBtn.on('click', () => {
                        this.currentSearch = this.searchInput.val();
                        this.currentPage = 1;
                        this.filterAndSearch();
                    });

                    // Search on Enter key
                    this.searchInput.on('keypress', (e) => {
                        if (e.which === 13) {
                            this.currentSearch = this.searchInput.val();
                            this.currentPage = 1;
                            this.filterAndSearch();
                        }
                    });

                    // Load More button
                    this.loadMoreBtn.on('click', () => {
                        this.loadMore();
                    });

                    // Card click functionality
                    this.bindCardClickEvents();

                    // Action button events
                    this.bindActionButtonEvents();
                },

                bindCardClickEvents() {
                    // Only enable card clicking if setting is enabled and not in Elementor editor
                    if (this.settings.cardClickable === 1 && !elementorFrontend.isEditMode()) {
                        // Use event delegation for dynamically loaded content
                        this.wrapper.on('click', '.king-addons-dpg-card', (e) => {
                            // Don't trigger if clicking on a link or button inside the card
                            if ($(e.target).closest('a, button, .king-addons-dpg-button').length > 0) {
                                return;
                            }

                            // Find the post link within the card
                            const postLink = $(e.currentTarget).find('.king-addons-dpg-title a');
                            if (postLink.length > 0) {
                                const postUrl = postLink.attr('href');
                                if (postUrl) {
                                    // Navigate to post
                                    window.location.href = postUrl;
                                }
                            }
                        });

                        // Add cursor pointer style to cards when clickable
                        this.wrapper.addClass('king-addons-dpg-cards-clickable');
                    }
                },

                bindActionButtonEvents() {
                    // Prevent multiple event bindings
                    this.wrapper.off('click', '.king-addons-dpg-action-btn');
                    
                    // Use event delegation for dynamically loaded content
                    this.wrapper.on('click', '.king-addons-dpg-action-btn', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const $button = $(e.target).closest('.king-addons-dpg-action-btn');
                        
                        // Prevent double clicks and global lightbox blocking
                        if ($button.data('clicking') || $('body').data('lightbox-opening')) {
                            return;
                        }
                        
                        $button.data('clicking', true);
                        $('body').data('lightbox-opening', true);
                        
                        setTimeout(() => {
                            $button.removeData('clicking');
                            $('body').removeData('lightbox-opening');
                        }, 1500);
                        
                        const action = $button.data('action');
                        const url = $button.data('url');
                        const title = $button.data('title') || '';
                        
                        if (!url) {
                            console.warn('No URL provided for action button');
                            return;
                        }
                        
                        switch (action) {
                            case 'lightbox_image':
                                this.openImageLightbox(url, title);
                                break;
                            case 'lightbox_video':
                                this.openVideoLightbox(url, title);
                                break;
                            case 'new_tab':
                            default:
                                window.open(url, '_blank');
                                break;
                        }
                    });
                },

                openImageLightbox(url, title) {
                    // Prevent multiple lightboxes from opening simultaneously
                    if ($('.lg-backdrop, .lg-outer, .king-addons-dpg-lightbox-temp, [data-lg-uid]').length > 0) {
                        return;
                    }
                    
                    // Check if lightGallery is already running
                    if (window.lgCurrentInstance) {
                        return;
                    }
                    
                    // Remove any orphaned containers first
                    $('.king-addons-dpg-lightbox-temp').remove();
                    
                    // Create array with single image item for LightGallery
                    const galleryItems = [{
                        src: url,
                        subHtml: title || ''
                    }];
                    
                    // Initialize LightGallery directly with dynamic gallery
                    if (typeof $.fn.lightGallery !== 'undefined') {
                        // Create a temporary div just for gallery initialization
                        const $tempDiv = $('<div style="display:none;"></div>');
                        $('body').append($tempDiv);
                        
                        $tempDiv.lightGallery({
                            dynamic: true,
                            dynamicEl: galleryItems,
                            download: false,
                            counter: false,
                            zoom: true,
                            fullScreen: true,
                            controls: true,
                            thumbnail: false,
                            closable: true,
                            escKey: true,
                            keyPress: true
                        });
                        
                        // Clean up on close
                        $tempDiv.on('onCloseAfter.lg', function() {
                            window.lgCurrentInstance = false;
                            setTimeout(() => {
                                $tempDiv.remove();
                            }, 100);
                        });
                    } else {
                        console.error('LightGallery not available');
                        window.open(url, '_blank');
                    }
                },

                openVideoLightbox(url, title) {
                    // Check if LightGallery is available
                    if (typeof $.fn.lightGallery === 'undefined') {
                        console.warn('LightGallery not loaded, opening video in new tab');
                        window.open(url, '_blank');
                        return;
                    }

                    // For old LightGallery v1.6.12, let's create a manual video popup
                    this.createYouTubePopup(url, title);
                },

                createYouTubePopup(url, title) {
                    // Process YouTube URL to get video ID
                    const videoId = this.getYouTubeVideoId(url);
                    if (!videoId) {
                        console.warn('Invalid YouTube URL');
                        window.open(url, '_blank');
                        return;
                    }

                    // Create manual video popup similar to LightGallery structure
                    const popupHtml = `
                        <div class="king-addons-video-popup" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                            <div class="king-addons-video-container" style="position: relative; width: 90%; max-width: 1200px; max-height: 90%;">
                                <button class="king-addons-video-close" style="position: absolute; top: -40px; right: 0; background: none; border: none; color: white; font-size: 30px; cursor: pointer; z-index: 10000;">&times;</button>
                                <div class="king-addons-video-wrapper" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                                    <iframe 
                                        src="https://www.youtube.com/embed/${videoId}?autoplay=1&modestbranding=1&rel=0&showinfo=0&controls=1"
                                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
                                        allowfullscreen
                                        allow="autoplay; encrypted-media">
                                    </iframe>
                                </div>
                                ${title ? `<div style="color: white; text-align: center; margin-top: 10px;">${title}</div>` : ''}
                            </div>
                        </div>
                    `;

                    const $popup = $(popupHtml);
                    $('body').append($popup);

                    // Handle close events
                    $popup.find('.king-addons-video-close').on('click', () => {
                        this.closeVideoPopup($popup);
                    });

                    $popup.on('click', (e) => {
                        if (e.target === $popup[0]) {
                            this.closeVideoPopup($popup);
                        }
                    });

                    // Handle ESC key
                    $(document).on('keydown.video-popup', (e) => {
                        if (e.keyCode === 27) {
                            this.closeVideoPopup($popup);
                        }
                    });

                    // Animate in
                    $popup.css('opacity', 0).animate({opacity: 1}, 300);
                },

                getYouTubeVideoId(url) {
                    // Extract video ID from various YouTube URL formats
                    const regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/;
                    const match = url.match(regExp);
                    return (match && match[7].length === 11) ? match[7] : null;
                },

                closeVideoPopup($popup) {
                    // Stop video by removing iframe
                    $popup.find('iframe').attr('src', '');
                    
                    // Remove event listeners
                    $(document).off('keydown.video-popup');
                    
                    // Animate out and remove
                    $popup.animate({opacity: 0}, 300, function() {
                        $popup.remove();
                    });
                },

                processYouTubeUrl(url) {
                    // Handle different YouTube URL formats
                    if (url.includes('youtube.com/watch?v=')) {
                        // Already in correct format
                        return url;
                    } else if (url.includes('youtu.be/')) {
                        // Convert youtu.be/VIDEO_ID to youtube.com/watch?v=VIDEO_ID
                        const videoId = url.split('youtu.be/')[1].split('?')[0].split('&')[0];
                        return `https://www.youtube.com/watch?v=${videoId}`;
                    } else if (url.includes('youtube.com/embed/')) {
                        // Convert youtube.com/embed/VIDEO_ID to youtube.com/watch?v=VIDEO_ID  
                        const videoId = url.split('/embed/')[1].split('?')[0].split('&')[0];
                        return `https://www.youtube.com/watch?v=${videoId}`;
                    }
                    
                    // Return original URL if not YouTube or unknown format
                    return url;
                },

                initIsotope() {
                    // Skip Isotope initialization in Elementor editor
                    if (elementorFrontend.isEditMode()) {
                        return;
                    }
                    
                    // Initialize isotope if available
                    if (typeof $.fn.isotopekng !== 'undefined') {
                        this.grid.isotopekng({
                            itemSelector: '.king-addons-dpg-card',
                            layoutMode: 'masonry',
                            masonry: {
                                columnWidth: '.king-addons-dpg-card'
                            },
                            transitionDuration: '0.3s'
                        });
                    }

                    // Images loaded callback (skip in editor)
                    if (typeof $.fn.imagesLoaded !== 'undefined' && !elementorFrontend.isEditMode()) {
                        this.grid.imagesLoaded(() => {
                            this.relayoutGrid();
                        });
                    }
                },

                relayoutGrid() {
                    // Skip in Elementor editor
                    if (elementorFrontend.isEditMode()) {
                        return;
                    }
                    
                    if (typeof $.fn.isotopekng !== 'undefined') {
                        this.grid.isotopekng('layout');
                    }
                },

                filterAndSearch() {
                    // PRO: Use client-side filtering for CPT mode
                    if (widgetMode === 'custom_cpt') {
                        this.filterByPostType();
                        return;
                    }
                    
                    // Skip AJAX calls in Elementor editor
                    if (elementorFrontend.isEditMode()) {
                        return;
                    }
                    
                    if (this.isLoading) return;

                    this.isLoading = true;
                    this.showLoading();

                    const ajaxData = {
                        action: 'king_addons_dynamic_posts_grid_filter',
                        nonce: window.KingAddonsDynamicPostsGrid?.nonce || '',
                        widget_id: this.settings.widgetId,
                        posts_per_page: this.settings.postsPerPage,
                        post_types: this.settings.postTypes,
                        orderby: this.settings.orderby,
                        order: this.settings.order,
                        filter_taxonomy: this.settings.filterTaxonomy,
                        filter_term: this.currentFilter,
                        search_query: this.currentSearch,
                        page: this.currentPage,
                        show_excerpt: this.settings.showExcerpt,
                        cpt_actions: this.settings.cptActionsRaw
                    };

                    $.ajax({
                        url: window.KingAddonsDynamicPostsGrid?.ajaxUrl || '/wp-admin/admin-ajax.php',
                        type: 'POST',
                        data: ajaxData,
                        success: (response) => {
                            this.handleFilterResponse(response);
                        },
                        error: (xhr, status, error) => {
                            console.error('Dynamic Posts Grid AJAX Error:', error);
                            this.hideLoading();
                            this.isLoading = false;
                        }
                    });
                },

                loadMore() {
                    // Skip AJAX calls in Elementor editor
                    if (elementorFrontend.isEditMode()) {
                        return;
                    }
                    
                    if (this.isLoading) return;

                    this.currentPage++;
                    this.isLoading = true;
                    this.showLoading();

                    const ajaxData = {
                        action: 'king_addons_dynamic_posts_grid_load_more',
                        nonce: window.KingAddonsDynamicPostsGrid?.nonce || '',
                        widget_id: this.settings.widgetId,
                        posts_per_page: this.settings.postsPerPage,
                        post_types: this.settings.postTypes,
                        orderby: this.settings.orderby,
                        order: this.settings.order,
                        filter_taxonomy: this.settings.filterTaxonomy,
                        filter_term: this.currentFilter,
                        search_query: this.currentSearch,
                        page: this.currentPage,
                        show_excerpt: this.settings.showExcerpt,
                        cpt_actions: this.settings.cptActionsRaw
                    };

                    $.ajax({
                        url: window.KingAddonsDynamicPostsGrid?.ajaxUrl || '/wp-admin/admin-ajax.php',
                        type: 'POST',
                        data: ajaxData,
                        success: (response) => {
                            this.handleLoadMoreResponse(response);
                        },
                        error: (xhr, status, error) => {
                            console.error('Dynamic Posts Grid Load More Error:', error);
                            this.hideLoading();
                            this.isLoading = false;
                            this.currentPage--; // Revert page increment on error
                        }
                    });
                },

                handleFilterResponse(response) {
                    this.hideLoading();
                    this.isLoading = false;

                    if (response.success && response.data) {
                        // Fade out current content
                        this.grid.addClass('king-addons-dpg-zero-opacity');

                        setTimeout(() => {
                            // Replace grid content
                            if (typeof $.fn.isotopekng !== 'undefined' && !elementorFrontend.isEditMode()) {
                                this.grid.isotopekng('destroy');
                            }

                            this.grid.html(response.data.posts_html);

                            // Re-initialize isotope
                            this.initIsotope();

                            // Update pagination
                            this.updatePagination(response.data);

                            // Fade in new content
                            setTimeout(() => {
                                this.grid.removeClass('king-addons-dpg-zero-opacity');
                                this.animateNewItems();
                            }, 100);

                        }, 300);
                    } else {
                        this.showError(response.data?.message || 'Failed to load posts');
                    }
                },

                handleLoadMoreResponse(response) {
                    this.hideLoading();
                    this.isLoading = false;

                    if (response.success && response.data) {
                        const newItems = $(response.data.posts_html);
                        
                        // Add new items to grid
                        this.grid.append(newItems);

                        // Animate new items
                        newItems.addClass('king-addons-dpg-fade-in');

                        // Re-layout isotope
                        if (typeof $.fn.isotopekng !== 'undefined' && !elementorFrontend.isEditMode()) {
                            this.grid.isotopekng('appended', newItems);
                            
                            // Re-layout after images load
                            if (typeof $.fn.imagesLoaded !== 'undefined') {
                                newItems.imagesLoaded(() => {
                                    this.relayoutGrid();
                                });
                            }
                        }

                        // Update pagination
                        this.updatePagination(response.data);

                        // Ensure clicks on links/buttons do not bubble to card-click handler
                        this.wrapper.off('click', '.king-addons-dpg-card a, .king-addons-dpg-card button')
                            .on('click', '.king-addons-dpg-card a, .king-addons-dpg-card button', (e) => {
                                e.stopPropagation();
                            });

                        // If CPT mode, re-apply client-side filter (post type + search)
                        if (widgetMode === 'custom_cpt') {
                            // Apply CPT icons to newly added items
                            this.applyCptIcons(newItems);
                            this.filterByPostType();
                        }

                    } else {
                        this.showError(response.data?.message || 'Failed to load more posts');
                        this.currentPage--; // Revert page increment
                    }
                },

                applyCptIcons($scopeItems) {
                    if (!this.cptIcons || typeof this.cptIcons !== 'object') return;
                    $scopeItems.each((_, el) => {
                        const $card = $(el);
                        const postType = $card.data('post-type');
                        if (!postType) return;
                        const conf = this.cptIcons[postType];
                        if (!conf) return;

                        const $iconWrap = $card.find('.king-addons-dpg-icon');
                        if (conf.icon_type === 'image' && conf.image_url) {
                            $iconWrap.html('<img src="' + conf.image_url + '" alt="' + postType + '" />');
                        } else if (conf.icon_class) {
                            $iconWrap.html('<i class="' + conf.icon_class + '"></i>');
                        }
                    });
                },

                updatePagination(data) {
                    if (data.current_page >= data.max_pages) {
                        this.loadMoreBtn.hide();
                        this.showFinished(data.total_posts, data.current_count);
                    } else {
                        this.loadMoreBtn.attr('data-page', data.current_page);
                        this.loadMoreBtn.attr('data-max-pages', data.max_pages);
                        this.loadMoreBtn.show();
                        this.finishDiv.hide();
                    }

                    // Update counts
                    $scope.find('.king-addons-dpg-current-count').text(data.current_count);
                    $scope.find('.king-addons-dpg-total-count').text(data.total_posts);
                },

                showLoading() {
                    this.loadingDiv.show();
                    this.loadMoreBtn.prop('disabled', true);
                    
                    // Add loading spinner to button
                    if (!this.loadMoreBtn.find('.king-addons-dpg-loading-spinner').length) {
                        this.loadMoreBtn.prepend('<span class="king-addons-dpg-loading-spinner"></span>');
                    }
                },

                hideLoading() {
                    this.loadingDiv.hide();
                    this.loadMoreBtn.prop('disabled', false);
                    this.loadMoreBtn.find('.king-addons-dpg-loading-spinner').remove();
                },

                showFinished(total, current) {
                    this.finishDiv.find('.king-addons-dpg-current-count').text(current);
                    this.finishDiv.find('.king-addons-dpg-total-count').text(total);
                    this.finishDiv.fadeIn(1000);
                    
                    setTimeout(() => {
                        this.finishDiv.fadeOut(1000);
                    }, 3000);
                },

                showError(message) {
                    // Create and show error message
                    const errorDiv = $('<div class="king-addons-dpg-error-message">' + message + '</div>');
                    this.wrapper.prepend(errorDiv);
                    
                    setTimeout(() => {
                        errorDiv.fadeOut(() => {
                            errorDiv.remove();
                        });
                    }, 5000);
                },

                animateNewItems() {
                    this.grid.find('.king-addons-dpg-card').each((index, element) => {
                        setTimeout(() => {
                            $(element).addClass('king-addons-dpg-fade-in');
                        }, index * 100);
                    });
                },

                debounce(func, wait, immediate) {
                    let timeout;
                    return function() {
                        const context = this;
                        const args = arguments;
                        const later = function() {
                            timeout = null;
                            if (!immediate) func.apply(context, args);
                        };
                        const callNow = immediate && !timeout;
                        clearTimeout(timeout);
                        timeout = setTimeout(later, wait);
                        if (callNow) func.apply(context, args);
                    };
                },

                // PRO Methods
                filterByPostType() {
                    // Filter posts by post type (for CPT mode)
                    if (widgetMode === 'custom_cpt') {
                        const $cards = this.grid.find('.king-addons-dpg-item');
                        let $candidate = $cards;

                        // Filter by selected post type first
                        if (this.currentFilter !== '*') {
                            $candidate = $candidate.filter('[data-post-type="' + this.currentFilter + '"]');
                        }

                        // Then apply keyword search (title text)
                        const query = (this.currentSearch || '').toString().trim().toLowerCase();
                        if (query.length > 0) {
                            $candidate = $candidate.filter((_, el) => {
                                const $el = $(el);
                                const titleText = $el.find('.king-addons-dpg-title').text().toLowerCase();
                                const excerptText = $el.find('.king-addons-dpg-excerpt').text().toLowerCase();
                                return (titleText.indexOf(query) !== -1) || (excerptText.indexOf(query) !== -1);
                            });
                        }

                        // Show only matching cards
                        $cards.hide();
                        $candidate.show();

                        // Re-layout if using Isotope
                        if (this.grid.data('isotopekng')) {
                            this.grid.isotopekng('layout');
                        }
                    }
                }
            };

            // Initialize the grid handler
            gridHandler.init();

            // Handle responsive behavior
            $(window).on('resize', gridHandler.debounce(() => {
                gridHandler.relayoutGrid();
            }, 250));

            // Handle Elementor editor mode
            if (window.elementorFrontend?.isEditMode()) {
                // Re-initialize when settings change in editor
                elementorFrontend.hooks.addAction('panel/open_editor/widget/king-addons-dynamic-posts-grid', () => {
                    setTimeout(() => {
                        gridHandler.init();
                    }, 100);
                });
            }
        });

        // PRO version uses the same hook name as Free version, no separate hook needed
    });

})(jQuery);
