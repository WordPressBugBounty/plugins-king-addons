// noinspection DuplicatedCode,JSUnresolvedReference,JSValidateTypes

jQuery(document).ready(function ($) {
    $(document).on('click', '.template-item', function () {
        let templateKey = $(this).data('template-key');
        let templatePlan = $(this).data('template-plan');
        let templateBtn = $('#install-template');
        templateBtn.attr('data-template-key', templateKey);
        templateBtn.attr('data-template-plan', templatePlan);
        let planActive = templateBtn.attr('data-plan-active');

        if (templatePlan === 'premium') {
            if (planActive === 'premium') {
                templateBtn.text('Import Premium Template');
            } else {
                templateBtn.text('Import Premium Template');
            }
        } else {
            templateBtn.text('Import Free Template');
        }

        $('#template-preview-iframe').attr('src', 'https://demo.kingaddons.com/' + templateKey);
        $('#template-preview-link').attr('href', 'https://demo.kingaddons.com/' + templateKey);
        $('#template-preview-popup').fadeIn();
    });

    // Listen for clicks on any button inside .preview-mode-switcher
    $('.preview-mode-switcher button').on('click', function () {
        // Remove the .active class from all buttons
        $('.preview-mode-switcher button').removeClass('active');
        // Add .active to the clicked button
        $(this).addClass('active');

        // Determine which mode was clicked: desktop, tablet, or mobile
        let mode = $(this).data('mode');

        // Select the preview iframe
        let $iframe = $('#template-preview-iframe');

        // Remove any previous mode classes
        $iframe.removeClass('preview-tablet preview-mobile');

        // If tablet, add .preview-tablet class
        if (mode === 'tablet') {
            $iframe.addClass('preview-tablet');
        }
        // If mobile, add .preview-mobile class
        else if (mode === 'mobile') {
            $iframe.addClass('preview-mobile');
        }
        // If desktop, no extra class (width:100% by default).
    });

    $(document).on('click', '#close-popup', function () {
        $('#template-preview-popup').fadeOut();
    });

    $(document).on('click', '#activate-license-btn', function () {
        $('#templates-catalog').addClass('kng-whole-overlay');
        $('#license-activating-popup').fadeIn();
    });

    $(document).on('click', '#close-license-activating-popup', function () {
        $('#templates-catalog').removeClass('kng-whole-overlay');
        $('#license-activating-popup').fadeOut();
    });

    $(document).on('click', '#close-premium-promo-popup', function () {
        $('#templates-catalog').removeClass('kng-whole-overlay');
        $('#premium-promo-popup').fadeOut();
    });

    $(document).on('click', '#close-installing-popup', function () {
        $('#templates-catalog').removeClass('kng-whole-overlay');
        $('#template-installing-popup').fadeOut();
        $('#go-to-imported-page').fadeOut();
        $('#close-installing-popup').fadeOut();
        document.getElementById('final_response').innerText = '';
        document.getElementById('progress').innerText = '';
        document.getElementById('progress-bar').style.width = '0%';
        document.getElementById('progress-bar').innerText = '0%';
    });

    $(document).on('click', '#install-template', function () {

        $('#templates-catalog').addClass('kng-whole-overlay');
        $('#template-preview-popup').fadeOut();

        let templateBtn = $('#install-template');
        let templateKey = templateBtn.attr('data-template-key');
        let templatePlan = templateBtn.attr('data-template-plan');
        let planActive = templateBtn.attr('data-plan-active');

        let api_request_url;
        let installId;

        if (planActive === 'premium') {
            if (templatePlan === 'premium') {
                api_request_url = 'https://api.kingaddons.com/get-template.php';
                installId = window.kingAddons.installId;
            } else {
                api_request_url = 'https://api.kingaddons.com/get-template-free.php';
                installId = 0;
            }
        } else {
            if (templatePlan === 'free') {
                api_request_url = 'https://api.kingaddons.com/get-template-free.php';
                installId = 0;
            } else {
                $('#premium-promo-popup').fadeIn();
                return;
            }
        }

        fetch(api_request_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                key: templateKey,
                install: installId,
            }),
        })
            .then(response => {
                if (!response.ok) {
                    // The server returned a 4xx or 5xx, read the text and throw an error
                    return response.text().then(html => {
                        console.error('Server error:\n' + html);
                        throw new Error('Server error (not JSON).');
                    });
                }
                // If ok, parse JSON
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    console.error('API error:', data);
                }
                if (data.success) {
                    $('#template-installing-popup').fadeIn();
                    doImport(data);
                } else {
                    $('#premium-promo-popup').fadeIn();
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Connection error: ' + error);
            });
    });

    function doImport(data) {
        document.getElementById('progress').innerText = 'Starting import...';
        document.getElementById('image-list').innerHTML = '';
        document.getElementById('progress').innerText = 'Import initialized.';
        document.getElementById('progress-bar').style.width = '10%';
        document.getElementById('progress-bar').innerText = '10%';

        let images = data.landing.images;

        if (images && images.length > 0) {
            let imageList = images.map(function (img) {
                return '<li data-id="' + img.id + '" data-url="' + img.url + '">' + img.url + ' (ID: ' + img.id + ')</li>';
            }).join('');

            document.getElementById('image-list').innerHTML = '<ul>' + imageList + '</ul>';

            startImport(data.landing);
        } else {
            document.getElementById('final_response').innerText = 'No images to import.';
        }
    }

    function startImport(initial_data) {
        fetch(kingAddonsData.ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'import_elementor_page_with_images',
                data: JSON.stringify(initial_data),
            })
        })
            .then(response => {
                if (!response.ok) {
                    // The server returned a 4xx or 5xx, read the text and throw an error
                    return response.text().then(html => {
                        console.error('Server error:\n' + html);
                        throw new Error('Server error:\n' + html);
                    });
                }
                // If ok, parse JSON
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    processNextImage();
                } else {
                    alert('Error getting template: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
    }

    function processNextImage() {
        const maxRetries = 3;
        let currentRetry = 0;
        let retryTimeout = 1000;

        function attemptProcessImage() {
            fetch(kingAddonsData.ajaxUrl, {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'process_import_images',
                })
            })
                .then(response => {
                    if (!response.ok) {
                        // The server returned a 4xx or 5xx, read the text and throw an error
                        return response.text().then(html => {
                            console.error('Server error:\n' + html);
                            throw new Error('Server error:\n' + html);
                        });
                    }
                    // If ok, parse JSON
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        if (data.data.progress !== undefined) {

                            let progressBar = document.getElementById('progress-bar');
                            let progress = data.data.progress;
                            progressBar.style.width = progress + '%';
                            progressBar.innerText = progress + '%';

                            document.getElementById('progress').innerText = data.data.message;

                            let imageUrlElement = document.querySelector('li[data-url="' + data.data.image_url + '"]');
                            if (imageUrlElement) {
                                imageUrlElement.innerHTML += ' - done';
                            }
                            currentRetry = 0;
                            retryTimeout = 1000;
                            processNextImage();
                        } else {
                            // Both templates and sections from main admin catalog create new pages
                            document.getElementById('final_response').innerText = data.data.message;
                            document.getElementById('progress').innerText = 'Import completed.';
                            document.getElementById('progress-bar').style.width = '100%';
                            document.getElementById('progress-bar').innerText = '100%';
                            let goToPage = $('#go-to-imported-page');
                            goToPage.attr('href', data.data.page_url);
                            goToPage.fadeIn();
                            $('#close-installing-popup').fadeIn();
                        }
                    } else {
                        console.error('Process image issue:', data);
                        // Skip image
                        if (data.data && data.data.retry) {
                            processNextImage();
                        }
                    }
                })
                .catch(error => {
                    console.error('Catch Error:', error);
                    if (currentRetry < maxRetries) {
                        currentRetry++;
                        retryTimeout *= 2;
                        setTimeout(attemptProcessImage, retryTimeout);
                    } else {
                        let imageUrlElement = document.querySelector('li[data-url="unknown"]');
                        if (imageUrlElement) {
                            imageUrlElement.innerHTML += ' - SKIPPED';
                        }
                        console.error('Error:', error);
                        document.getElementById('final_response').innerText = 'Error: ' + error.message;
                        currentRetry = 0;
                        retryTimeout = 1000;
                        processNextImage();
                    }
                });
        }

        attemptProcessImage();
    }

    $(document).on('click', '.pagination a', function (e) {
        e.preventDefault();
        let page = $(this).attr('href').split('paged=')[1];
        filterTemplates(page);
    });

    let templateSearch = $('#template-search');
    let templateCategory = $('#template-category');
    let templateCollection = $('#template-collection');

    function filterTemplates(page = 1) {
        let searchQuery = templateSearch.val().toLowerCase();
        let selectedCategory = templateCategory.val();
        let selectedCollection = templateCollection.val();
        let selectedTags = [];

        $('#template-tags input:checked').each(function () {
            selectedTags.push($(this).val());
        });

        $.ajax({
            url: kingAddonsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'filter_templates',
                s: searchQuery,
                category: selectedCategory,
                collection: selectedCollection,
                tags: selectedTags.join(','),
                paged: page
            },
            success: function (response) {
                if (response.success) {

                    // Scroll to top after the new content is loaded with admin bar offset
                    const scrollOffset = $('#king-addons-templates-top').offset().top - 32; // Subtract 32px for admin bar
                    $('html, body').animate({scrollTop: scrollOffset}, 0);

                    $('.templates-grid').html(response.data.grid_html);
                    $('.pagination').html(response.data.pagination_html);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error: ', status, error);
            }
        });
    }

    templateSearch.on('keyup', function () {
        templateCategory.val('');
        templateCollection.val('');
        $('#template-tags input:checked').prop('checked', false);
        filterTemplates();
    });

    templateCategory.on('change', function () {
        templateSearch.val('');
        templateCollection.val('');
        $('#template-tags input:checked').prop('checked', false);
        filterTemplates();
    });

    templateCollection.on('change', function () {
        templateSearch.val('');
        templateCategory.val('');
        $('#template-tags input:checked').prop('checked', false);
        filterTemplates();
    });

    $('#template-tags input').on('change', function () {
        templateSearch.val('');
        templateCategory.val('');
        templateCollection.val('');
        if ($(this).is(':checked')) {
            $('#template-tags input').not(this).prop('checked', false);
        }
        filterTemplates();
    });

    $('#reset-filters').on('click', function () {
        templateSearch.val('');
        templateCategory.val('');
        templateCollection.val('');
        $('#template-tags input:checked').prop('checked', false);
        filterTemplates();
    });

    // Load sections count on page load
    if ($('#sections-count').length) {
        loadSectionsCount();
    }

    // ===== TABS FUNCTIONALITY =====
    
    // Tab switching
    $('.king-addons-tab-button').on('click', function() {
        var tabId = $(this).data('tab');
        
        // Remove active class from all tabs and content
        $('.king-addons-tab-button').removeClass('active');
        $('.king-addons-tab-content').removeClass('active');
        
        // Add active class to clicked tab and corresponding content
        $(this).addClass('active');
        $('#' + tabId + '-catalog').addClass('active');
        
        // Load sections on first visit to sections tab
        if (tabId === 'sections' && !window.sectionsLoaded) {
            loadSections();
        }
    });

    // ===== SECTIONS FUNCTIONALITY =====
    
    window.sectionsLoaded = false;
    window.sectionsData = null;
    window.sectionsPage = 1;
    window.sectionsFilters = {};

    function loadSections(page = 1) {
        if (!window.kingAddonsData || !window.kingAddonsData.ajaxUrl) {
            console.error('King Addons data not available');
            return;
        }

        var searchQuery = $('#sections-search').val() || '';
        var selectedCategory = $('#sections-category').val() || '';
        var selectedType = $('#sections-type').val() || '';
        var selectedPlan = $('#sections-plan').val() || '';

        $('.sections-grid').html(`
            <div class="sections-loading">
                Loading sections...
            </div>
        `);

        $.ajax({
            url: window.kingAddonsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'king_addons_get_sections_catalog',
                nonce: window.kingAddonsData.nonce || '',
                search: searchQuery,
                category: selectedCategory,
                section_type: selectedType,
                plan: selectedPlan,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    // Scroll to top after new content is loaded with admin bar offset (same as templates)
                    const scrollOffset = $('#king-addons-templates-top').offset().top - 32; // Subtract 32px for admin bar
                    $('html, body').animate({scrollTop: scrollOffset}, 0);

                    window.sectionsData = response.data;
                    window.sectionsLoaded = true;
                    renderSections();
                    updateSectionsFilters();
                    updateSectionsCount();
                } else {
                    $('.sections-grid').html(`
                        <div class="sections-loading">
                            Error loading sections: ${response.data || 'Unknown error'}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error loading sections:', status, error);
                $('.sections-grid').html(`
                    <div class="sections-loading">
                        Failed to load sections. Please try again.
                    </div>
                `);
            }
        });
    }

    function renderSections() {
        if (!window.sectionsData || !window.sectionsData.sections) {
            $('.sections-grid').html(`
                <div class="sections-loading">
                    <p>No sections found. Try adjusting your search or filters to find more sections.</p>
                </div>
            `);
            return;
        }

        var sectionsHtml = '';
        var sections = window.sectionsData.sections;

        sections.forEach(function(section) {
            // Use the correct screenshot URL pattern with plan-based paths
            var screenshotUrl = `https://thumbnails.kingaddons.com/sections/${section.plan}/${section.section_key}.png?v=4`;
            
            sectionsHtml += `
                <div class="section-item" 
                     data-section-key="${section.section_key}" 
                     data-section-plan="${section.plan}"
                     data-category="${section.category || ''}"
                     data-tags="${(section.tags || []).join(',')}"
                     data-section-type="${section.section_type || ''}">
                    <img class="kng-addons-section-thumbnail" loading="lazy"
                         src="${screenshotUrl}" 
                         alt="${section.title}"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImciIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjEwMCUiPjxzdG9wIG9mZnNldD0iMCUiIHN0b3AtY29sb3I9IiNmOGY5ZmEiLz48c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNlNWU3ZWIiLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2cpIi8+PGNpcmNsZSBjeD0iMTUwIiBjeT0iNzAiIHI9IjE2IiBmaWxsPSIjOWNhM2FmIiBvcGFjaXR5PSIwLjQiLz48cmVjdCB4PSIxMzQiIHk9Ijg2IiB3aWR0aD0iMzIiIGhlaWdodD0iNCIgZmlsbD0iIzljYTNhZiIgb3BhY2l0eT0iMC40IiByeD0iMiIvPjxyZWN0IHg9IjEyNiIgeT0iOTQiIHdpZHRoPSI0OCIgaGVpZ2h0PSI0IiBmaWxsPSIjOWNhM2FmIiBvcGFjaXR5PSIwLjMiIHJ4PSIyIi8+PHRleHQgeD0iNTAlIiB5PSIxMjAiIGZvbnQtZmFtaWx5PSItYXBwbGUtc3lzdGVtLCBCbGlua01hY1N5c3RlbUZvbnQsIHNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM2Yjc1ODQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIG9wYWNpdHk9IjAuNyI+U2VjdGlvbiBQcmV2aWV3PC90ZXh0Pjwvc3ZnPg=='">
                    <div class="section-content">
                        <h3>${section.title}</h3>
                        <div class="section-plan section-plan-${section.plan}">${section.plan}</div>
                    </div>
                    <div class="section-overlay">
                        <div class="section-actions">
                            <button class="section-import-btn" data-section-key="${section.section_key}" data-section-plan="${section.plan}">
                                Import Section
                            </button>
                            <a href="https://sections.kingaddons.com/${section.section_key}" class="section-preview-btn" target="_blank">
                                Live Preview
                            </a>
                        </div>
                    </div>
                </div>
            `;
        });

        $('.sections-grid').html(sectionsHtml);
        
        // Render pagination if available
        if (window.sectionsData.pagination) {
            renderSectionsPagination();
        }
    }



    function renderSectionsPagination() {
        var pagination = window.sectionsData.pagination;
        var paginationHtml = '';

        if (pagination.total_pages > 1) {
            paginationHtml += '<div class="pagination-inner">';
            
            var current = pagination.current_page;
            var total = pagination.total_pages;
            var endSize = 3;  // Show 3 pages at beginning and end
            var midSize = 2;  // Show 2 pages around current
            
            // Previous page
            if (current > 1) {
                paginationHtml += `<a class="page-numbers" href="#" data-page="${current - 1}">&larr; Previous</a>`;
            }
            
            // Smart pagination logic with proper ellipsis
            var pages = [];
            
            // Always show first pages
            for (var i = 1; i <= Math.min(endSize, total); i++) {
                pages.push(i);
            }
            
            // Calculate middle range around current page
            var start = Math.max(current - midSize, 1);
            var end = Math.min(current + midSize, total);
            
            // Add first ellipsis if there's a gap
            if (start > endSize + 1) {
                pages.push('...');
            }
            
            // Add middle pages around current (avoid duplicates with start/end)
            for (var i = Math.max(start, endSize + 1); i <= Math.min(end, total - endSize); i++) {
                if (pages.indexOf(i) === -1) {
                    pages.push(i);
                }
            }
            
            // Add second ellipsis if there's a gap
            if (end < total - endSize) {
                pages.push('...');
            }
            
            // Always show last pages (avoid duplicates)
            for (var i = Math.max(total - endSize + 1, endSize + 1); i <= total; i++) {
                if (pages.indexOf(i) === -1) {
                    pages.push(i);
                }
            }
            
            // Render pages
            pages.forEach(function(page) {
                if (page === '...') {
                    paginationHtml += `<span class="page-numbers dots">…</span>`;
                } else if (page === current) {
                    paginationHtml += `<span class="page-numbers current">${page}</span>`;
                } else {
                    paginationHtml += `<a class="page-numbers" href="#" data-page="${page}">${page}</a>`;
                }
            });
            
            // Next page
            if (current < total) {
                paginationHtml += `<a class="page-numbers" href="#" data-page="${current + 1}">Next &rarr;</a>`;
            }
            
            paginationHtml += '</div>';
        }

        $('.sections-pagination').html(paginationHtml);
    }

    function updateSectionsFilters() {
        if (!window.sectionsData) return;

        // Update categories dropdown
        var categoriesHtml = '<option value="">All Categories</option>';
        if (window.sectionsData.categories) {
            window.sectionsData.categories.forEach(function(category) {
                var displayName = category.charAt(0).toUpperCase() + category.slice(1).replace(/-/g, ' ');
                categoriesHtml += `<option value="${category}">${displayName}</option>`;
            });
        }
        $('#sections-category').html(categoriesHtml);

        // Update types dropdown
        var typesHtml = '<option value="">All Types</option>';
        if (window.sectionsData.section_types) {
            window.sectionsData.section_types.forEach(function(type) {
                var displayName = type.charAt(0).toUpperCase() + type.slice(1).replace(/-/g, ' ');
                typesHtml += `<option value="${type}">${displayName}</option>`;
            });
        }
        $('#sections-type').html(typesHtml);
    }

    function updateSectionsCount() {
        if (window.sectionsData && window.sectionsData.pagination) {
            $('#sections-count').text(window.sectionsData.pagination.total_sections);
        }
    }

    // Sections filters event handlers - Same logic as templates
    var sectionsSearch = $('#sections-search');
    var sectionsCategory = $('#sections-category');
    var sectionsType = $('#sections-type');
    var sectionsPlan = $('#sections-plan');

    sectionsSearch.on('keyup', function() {
        // Reset other filters when searching (same as templates)
        sectionsCategory.val('');
        sectionsType.val('');
        sectionsPlan.val('');
        
        clearTimeout(window.sectionsSearchTimeout);
        window.sectionsSearchTimeout = setTimeout(function() {
            loadSections(1);
        }, 500);
    });

    sectionsCategory.on('change', function() {
        // Reset search and other filters (same as templates)
        sectionsSearch.val('');
        sectionsType.val('');
        sectionsPlan.val('');
        loadSections(1);
    });

    sectionsType.on('change', function() {
        // Reset search and other filters (same as templates)
        sectionsSearch.val('');
        sectionsCategory.val('');
        sectionsPlan.val('');
        loadSections(1);
    });

    sectionsPlan.on('change', function() {
        // Reset search and other filters (same as templates)
        sectionsSearch.val('');
        sectionsCategory.val('');
        sectionsType.val('');
        loadSections(1);
    });

    $('#sections-reset-filters').on('click', function() {
        sectionsSearch.val('');
        sectionsCategory.val('');
        sectionsType.val('');
        sectionsPlan.val('');
        loadSections(1);
    });

    // Sections pagination handler
    $(document).on('click', '.sections-pagination .page-numbers', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        if (page) {
            loadSections(page);
        }
    });

    // Section import button handler
    $(document).on('click', '.section-import-btn', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Prevent triggering parent click
        
        var sectionKey = $(this).data('section-key');
        var sectionPlan = $(this).data('section-plan');
        
        // Check if this is a premium section and user doesn't have premium
        var planActive = $('#install-template').attr('data-plan-active');
        
        if (sectionPlan === 'premium' && planActive !== 'premium') {
            // Show premium promo popup
            $('#templates-catalog').addClass('kng-whole-overlay');
            $('#premium-promo-popup').fadeIn();
            return;
        }
        
        // For free sections or premium sections with premium license, trigger import
        if (confirm('Import this section?\n\nSection: ' + sectionKey + '\nPlan: ' + sectionPlan + '\n\nNote: This will be added to your current page in Elementor.')) {
            importSection(sectionKey, sectionPlan);
        }
    });

    // Section preview button handler (handled by href, but we can add tracking here)
    $(document).on('click', '.section-preview-btn', function(e) {
        e.stopPropagation(); // Prevent triggering parent click
        // The link will open in new tab automatically due to target="_blank"
    });

    // Section item click handler - now only for general clicks (not on buttons)
    $(document).on('click', '.section-item', function(e) {
        // Don't trigger if clicking on buttons or overlay
        if ($(e.target).closest('.section-overlay, .section-import-btn, .section-preview-btn').length) {
            return;
        }
        
        var sectionKey = $(this).data('section-key');
        var sectionPlan = $(this).data('section-plan');
        
        // Check if this is a premium section and user doesn't have premium
        var planActive = $('#install-template').attr('data-plan-active');
        
        if (sectionPlan === 'premium' && planActive !== 'premium') {
            // Show premium promo popup
            $('#templates-catalog').addClass('kng-whole-overlay');
            $('#premium-promo-popup').fadeIn();
            return;
        }
        
        // For free sections or premium sections with premium license, trigger import
        if (confirm('Import this section?\n\nSection: ' + sectionKey + '\nPlan: ' + sectionPlan + '\n\nNote: This will be added to your current page in Elementor.')) {
            importSection(sectionKey, sectionPlan);
        }
    });

    // Function to import section from main catalog
    function importSection(sectionKey, sectionPlan) {
        // Show importing popup
        $('#templates-catalog').addClass('kng-whole-overlay');
        $('#template-installing-popup').fadeIn();
        
        // Initialize progress
        document.getElementById('progress').innerText = 'Loading section data...';
        document.getElementById('progress-bar').style.width = '5%';
        document.getElementById('progress-bar').innerText = '5%';

        // Get section data
        $.ajax({
            url: window.kingAddonsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'king_addons_import_section_admin',
                nonce: window.kingAddonsData.nonce,
                section_key: sectionKey,
                section_plan: sectionPlan
            },
            success: function(response) {
                if (response.success) {
                    var sectionData = response.data.section_data;
                    var imageCount = sectionData.images ? sectionData.images.length : 0;
                    
                    document.getElementById('progress').innerText = 'Section loaded! Found ' + imageCount + ' images to process...';
                    document.getElementById('progress-bar').style.width = '25%';
                    document.getElementById('progress-bar').innerText = '25%';
                    
                    // Process section import (use existing system)
                    processSectionImport(sectionData);
                } else {
                    document.getElementById('progress').innerText = 'Error: ' + (response.data || 'Failed to load section');
                    $('#close-installing-popup').fadeIn();
                }
            },
            error: function(xhr, status, error) {
                document.getElementById('progress').innerText = 'Network error: ' + error;
                $('#close-installing-popup').fadeIn();
            }
        });
    }

    // Process section import using existing Templates system
    function processSectionImport(sectionData) {
        document.getElementById('progress').innerText = 'Preparing section import...';
        document.getElementById('progress-bar').style.width = '45%';
        document.getElementById('progress-bar').innerText = '45%';

        // Use existing Templates import system
        var importData = {
            content: sectionData.content,
            images: sectionData.images || [],
            title: sectionData.title || 'Imported Section',
            elementor_version: sectionData.elementor_version || '3.0.0',
            existing_page_id: 0, 
            create_new_page: true  // Create new page for sections from main admin catalog
        };

        // Call Templates import system
        $.ajax({
            url: window.kingAddonsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'import_elementor_page_with_images',
                data: JSON.stringify(importData)
            },
            success: function(result) {
                if (result.success) {
                    document.getElementById('progress').innerText = 'Section import initialized! Processing images...';
                    document.getElementById('progress-bar').style.width = '65%';
                    document.getElementById('progress-bar').innerText = '65%';
                    
                    // Start processing images using existing system
                    processNextImage();
                } else {
                    document.getElementById('progress').innerText = 'Error: ' + (result.data || 'Failed to initialize section import');
                    $('#close-installing-popup').fadeIn();
                }
            },
            error: function(xhr, status, error) {
                document.getElementById('progress').innerText = 'Import error: ' + error;
                $('#close-installing-popup').fadeIn();
            }
        });
    }

    // Load sections count for tab
    function loadSectionsCount() {
        $.ajax({
            url: window.kingAddonsData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'king_addons_get_sections_catalog',
                nonce: window.kingAddonsData.nonce || '',
                search: '',
                category: '',
                section_type: '',
                plan: '',
                page: 1
            },
            success: function(response) {
                if (response.success && response.data.pagination) {
                    $('#sections-count').text(response.data.pagination.total_sections);
                }
            },
            error: function(xhr, status, error) {
                console.log('Failed to load sections count:', error);
            }
        });
    }
});
