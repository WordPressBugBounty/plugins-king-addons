/**
 * Dynamic Posts Grid Widget JavaScript
 * King Addons for Elementor
 */

(function ($) {
    'use strict';

    $(window).on("elementor/frontend/init", () => {
        elementorFrontend.hooks.addAction("frontend/element_ready/king-addons-dynamic-posts-grid.default", ($scope) => {
            
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
                        cardClickable: this.wrapper.data('card-clickable')
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
                        show_excerpt: this.settings.showExcerpt
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
                        show_excerpt: this.settings.showExcerpt
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

                    } else {
                        this.showError(response.data?.message || 'Failed to load more posts');
                        this.currentPage--; // Revert page increment
                    }
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
    });

})(jQuery); 