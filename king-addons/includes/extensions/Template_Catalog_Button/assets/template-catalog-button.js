/**
 * King Addons Template Catalog Button for Elementor Editor
 * 
 * This script adds a template catalog button to the Elementor editor panel
 * that opens the King Addons template catalog in a popup for import into current page
 */

(function($) {
    'use strict';

    /**
     * Template Catalog Button Handler
     */
    class TemplateCatalogButton {
        
        constructor() {
            this.currentPage = 1;
            this.currentFilters = {};
            this.isLoading = false;
            this.catalogData = null;
            this.buttonCreated = false; // Flag to prevent duplicate button creation
            
            // Sections properties
            this.currentSectionsPage = 1;
            this.currentSectionsFilters = {};
            this.isSectionsLoading = false;
            this.sectionsData = null;
            this.sectionsLoaded = false;
            
            this.init();
        }

        /**
         * Initialize the button functionality
         */
        init() {
            // Wait for Elementor to be fully loaded
            $(window).on('elementor:init', () => {
                this.onElementorInit();
            });

            // Fallback if elementor:init doesn't fire
            setTimeout(() => {
                this.addButton();
            }, 2000);
        }

        /**
         * Handle Elementor initialization
         */
        onElementorInit() {
            // Add button immediately
            this.addButton();

            // Also add button when panel opens
            if (typeof elementor !== 'undefined' && elementor.hooks) {
                elementor.hooks.addAction('panel/open_editor/widget', () => {
                    setTimeout(() => this.addButton(), 100);
                });

                // Add button when navigator opens
                elementor.hooks.addAction('navigator/init', () => {
                    setTimeout(() => this.addButton(), 100);
                });
            }

            // Monitor for panel changes
            this.observePanelChanges();
        }

        /**
         * Reset button creation flag to allow recreating button when needed
         */
        resetButtonFlag() {
            this.buttonCreated = false;
        }

        /**
         * Add the template catalog button to the editor
         */
        addButton() {
            // Check if we have the required data
            if (!window.kingAddonsTemplateCatalog || !window.kingAddonsTemplateCatalog.templatesEnabled) {
                return;
            }

            // Check if button is enabled (premium setting)
            if (window.kingAddonsTemplateCatalog.buttonEnabled === false) {
                return;
            }

            // Check if button was already created in this session
            if (this.buttonCreated) {
                return;
            }

            // Check if button already exists in DOM
            if (document.querySelector('.king-addons-template-catalog-btn')) {
                this.buttonCreated = true;
                return;
            }

            // Try to add button to the content area where "Drag widget here" is shown
            const buttonAdded = this.tryAddToContentArea();
            
            // Mark as created if successfully added
            if (buttonAdded) {
                this.buttonCreated = true;
            }
        }

        /**
         * Try to add button to content area with "Drag widget here"
         */
        tryAddToContentArea() {
            // Look for empty section or container in the preview area
            const preview = document.querySelector('#elementor-preview-iframe');
            if (!preview) return false;

            const previewDoc = preview.contentDocument || preview.contentWindow.document;
            if (!previewDoc) return false;

            // Check if template catalog button already exists to prevent duplicates
            const existingButton = previewDoc.querySelector('.king-addons-template-catalog-content-area');
            if (existingButton) {
                return false;
            }

            // Only try to find the "Add New Section" element - no fallback methods
            const addNewSection = previewDoc.querySelector('#elementor-add-new-section');
            if (addNewSection && addNewSection.parentNode) {
                const buttonContainer = this.createContentAreaButton();
                
                // Insert after the "Add New Section" element
                addNewSection.parentNode.insertBefore(buttonContainer, addNewSection.nextSibling);
                return true;
            }

            // If no "Add New Section" element found, don't add button
            return false;
        }



        /**
         * Create button for content area placement
         */
        createContentAreaButton() {
            const container = document.createElement('div');
            container.className = 'king-addons-template-catalog-content-area';
            container.id = 'king-addons-template-catalog-' + Date.now(); // Unique ID
            container.style.cssText = `
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 40px 30px;
                text-align: center;
                min-height: 200px;
                width: 100%;
                max-width: 600px;
                margin: 40px auto;
                border: 3px solid #93c5fd;
                border-radius: 16px;
                background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f8fafc 100%);
                position: relative;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(59, 130, 246, 0.08);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                cursor: pointer;
                box-sizing: border-box;
            `;

            // Add hover effects
            container.addEventListener('mouseenter', () => {
                container.style.transform = 'translateY(-3px)';
                container.style.boxShadow = '0 8px 30px rgba(59, 130, 246, 0.12)';
                container.style.borderColor = '#60a5fa';
            });

            container.addEventListener('mouseleave', () => {
                container.style.transform = 'translateY(0)';
                container.style.boxShadow = '0 4px 20px rgba(59, 130, 246, 0.08)';
                container.style.borderColor = '#93c5fd';
            });

            // Add decorative background pattern
            const pattern = document.createElement('div');
            pattern.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-image: radial-gradient(circle at 25% 25%, rgba(59, 130, 246, 0.04) 0%, transparent 50%),
                                  radial-gradient(circle at 75% 75%, rgba(147, 197, 253, 0.04) 0%, transparent 50%);
                pointer-events: none;
                z-index: 1;
            `;
            container.appendChild(pattern);

            // Icon wrapper
            const iconWrapper = document.createElement('div');
            iconWrapper.style.cssText = `
                width: 64px;
                height: 64px;
                background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 0 20px 0;
                position: relative;
                z-index: 2;
                box-shadow: 0 6px 20px rgba(59, 130, 246, 0.25);
                animation: pulse 3s infinite;
            `;

            const icon = document.createElement('i');
            icon.className = 'eicon-library-open';
            icon.style.cssText = `
                font-size: 24px;
                color: white;
            `;
            iconWrapper.appendChild(icon);

            const title = document.createElement('h3');
            title.textContent = 'Start with a Template';
            title.style.cssText = `
                margin: 0 0 8px 0;
                font-size: 22px;
                font-weight: 700;
                color: #1e293b;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                position: relative;
                z-index: 2;
                letter-spacing: -0.025em;
            `;

            const subtitle = document.createElement('p');
            subtitle.textContent = 'Choose from hundreds of professional templates to get started quickly';
            subtitle.style.cssText = `
                margin: 0 0 24px 0;
                font-size: 14px;
                color: #64748b;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                position: relative;
                z-index: 2;
                line-height: 1.5;
                max-width: 400px;
            `;

            const button = document.createElement('button');
            button.className = 'king-addons-template-catalog-btn king-addons-content-area-btn';
            button.type = 'button';
            button.style.cssText = `
                background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 10px;
                font-size: 14px;
                font-weight: 600;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s ease;
                box-shadow: 0 3px 12px rgba(59, 130, 246, 0.3);
                position: relative;
                z-index: 2;
                text-decoration: none;
                outline: none;
                letter-spacing: 0.025em;
                min-width: 160px;
                justify-content: center;
            `;

            button.innerHTML = `
                <i class="eicon-library-open" style="font-size: 16px;" aria-hidden="true"></i>
                <span>${window.kingAddonsTemplateCatalog.buttonText}</span>
            `;

            // Add button hover effects
            button.addEventListener('mouseenter', () => {
                button.style.transform = 'translateY(-1px)';
                button.style.boxShadow = '0 6px 20px rgba(59, 130, 246, 0.4)';
                button.style.background = 'linear-gradient(135deg, #2563eb 0%, #1e40af 100%)';
            });

            button.addEventListener('mouseleave', () => {
                button.style.transform = 'translateY(0)';
                button.style.boxShadow = '0 4px 16px rgba(59, 130, 246, 0.3)';
                button.style.background = 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)';
            });

            button.addEventListener('mousedown', () => {
                button.style.transform = 'translateY(0px) scale(0.98)';
            });

            button.addEventListener('mouseup', () => {
                button.style.transform = 'translateY(-1px) scale(1)';
            });

            // Add click handler
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.openTemplatePopup();
            });

            // Add pulse animation styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes pulse {
                    0%, 100% { 
                        transform: scale(1); 
                        opacity: 1; 
                    }
                    50% { 
                        transform: scale(1.08); 
                        opacity: 0.9; 
                    }
                }
            `;
            document.head.appendChild(style);

            // Add responsive styles
            if (window.innerWidth <= 768) {
                container.style.padding = '30px 20px';
                container.style.margin = '20px auto';
                container.style.minHeight = '160px';
                container.style.maxWidth = '90%';
                title.style.fontSize = '18px';
                subtitle.style.fontSize = '13px';
                subtitle.style.maxWidth = '280px';
                button.style.padding = '10px 20px';
                button.style.minWidth = '140px';
                button.style.fontSize = '13px';
                iconWrapper.style.width = '48px';
                iconWrapper.style.height = '48px';
                icon.style.fontSize = '18px';
            }

            // Add click handler to entire container for better UX
            container.addEventListener('click', (e) => {
                if (e.target === container || e.target === pattern) {
                    this.openTemplatePopup();
                }
            });

            container.appendChild(iconWrapper);
            container.appendChild(title);
            container.appendChild(subtitle);
            container.appendChild(button);

            return container;
        }

        /**
         * Open template catalog popup
         */
        openTemplatePopup() {
            // Create popup if it doesn't exist
            if (!document.querySelector('.king-addons-template-popup')) {
                this.createTemplatePopup();
            }

            // Show popup
            const popup = document.querySelector('.king-addons-template-popup');
            popup.classList.add('show');

            // Load templates on first open
            if (!this.catalogData) {
                this.loadTemplateCatalog();
            } else {
                this.renderTemplateGrid();
            }

            // Load sections on first open
            if (!this.sectionsData) {
                this.loadSectionsCatalog();
            } else {
                this.renderSectionsGrid();
            }
        }

        /**
         * Create template popup HTML structure
         */
        createTemplatePopup() {
            const popup = document.createElement('div');
            popup.className = 'king-addons-template-popup';
            
            const isProVersion = window.kingAddonsTemplateCatalog.isPremium;
            const catalogTitle = isProVersion ? 'Templates Pro' : 'Free Templates';
            
            popup.innerHTML = `
                <div class="king-addons-template-popup-content">
                    <div class="king-addons-template-popup-header">
                        <div class="king-addons-template-popup-header-content">
                            <h2 class="king-addons-template-popup-title">King Addons ${catalogTitle}</h2>
                            <p class="king-addons-template-popup-subtitle">Choose from hundreds of professional templates and sections</p>
                        </div>
                        <button class="king-addons-template-popup-close" type="button">&times;</button>
                    </div>
                    
                    <!-- Popup Tabs -->
                    <div class="king-addons-popup-tabs">
                        <button class="king-addons-popup-tab-button active" data-tab="templates">
                            <i class="eicon-document-file"></i>
                            Templates
                            <span class="popup-tab-count" id="templates-tab-count">0</span>
                        </button>
                        <button class="king-addons-popup-tab-button" data-tab="sections">
                            <i class="eicon-section"></i>
                            Sections
                            <span class="popup-tab-count" id="sections-tab-count">0</span>
                        </button>
                    </div>

                    <!-- Templates Tab Content -->
                    <div class="king-addons-popup-tab-content active" id="templates-tab">
                        <div class="king-addons-template-popup-filters">
                            <input type="text" class="king-addons-template-popup-search" placeholder="Search templates..." />
                            <select class="king-addons-template-popup-select" id="category-filter">
                                <option value="">All Categories</option>
                            </select>
                            <select class="king-addons-template-popup-select" id="collection-filter">
                                <option value="">All Collections</option>
                            </select>
                            <button class="king-addons-reset-filters-btn" id="reset-templates-filters">
                                <i class="eicon-undo" aria-hidden="true"></i> Reset
                            </button>
                        </div>
                        <div class="king-addons-template-popup-body">
                            <div class="king-addons-template-popup-loading">
                                <div class="king-addons-template-spinner"></div>
                                Loading templates...
                            </div>
                        </div>
                    </div>

                    <!-- Sections Tab Content -->
                    <div class="king-addons-popup-tab-content" id="sections-tab">
                        <div class="king-addons-template-popup-filters">
                            <input type="text" class="king-addons-sections-popup-search" placeholder="Search sections..." />
                            <select class="king-addons-template-popup-select" id="sections-category-filter">
                                <option value="">All Categories</option>
                            </select>
                            <select class="king-addons-template-popup-select" id="sections-type-filter">
                                <option value="">All Types</option>
                            </select>
                            <select class="king-addons-template-popup-select" id="sections-plan-filter">
                                <option value="">All Plans</option>
                                <option value="free">Free</option>
                                ${isProVersion ? '<option value="premium">Premium</option>' : ''}
                            </select>
                            <button class="king-addons-reset-filters-btn" id="reset-sections-filters">
                                <i class="eicon-undo" aria-hidden="true"></i> Reset
                            </button>
                        </div>
                        <div class="king-addons-sections-popup-body">
                            <div class="king-addons-template-popup-loading">
                                <div class="king-addons-template-spinner"></div>
                                Loading sections...
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(popup);

            // Create and append premium promo popup
            this.createPremiumPromoPopup();

            // Add event listeners
            this.attachPopupEventListeners();
        }

        /**
         * Attach event listeners to popup elements
         */
        attachPopupEventListeners() {
            const popup = document.querySelector('.king-addons-template-popup');
            
            // Close popup
            const closeBtn = popup.querySelector('.king-addons-template-popup-close');
            closeBtn.addEventListener('click', () => this.closeTemplatePopup());
            
            // Close on backdrop click
            popup.addEventListener('click', (e) => {
                if (e.target === popup) {
                    this.closeTemplatePopup();
                }
            });

            // Tab switching functionality
            const tabButtons = popup.querySelectorAll('.king-addons-popup-tab-button');
            tabButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    const tabId = e.currentTarget.dataset.tab;
                    this.switchPopupTab(tabId);
                });
            });

            // Templates tab - Search functionality
            const searchInput = popup.querySelector('.king-addons-template-popup-search');
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    // Reset other filters when searching (same as main catalog)
                    this.currentFilters = { search: e.target.value };
                    categoryFilter.value = '';
                    collectionFilter.value = '';
                    this.currentPage = 1;
                    this.loadTemplateCatalog();
                }, 300);
            });

            // Templates tab - Category filter
            const categoryFilter = popup.querySelector('#category-filter');
            categoryFilter.addEventListener('change', (e) => {
                // Reset search and other filters (same as main catalog)
                this.currentFilters = { category: e.target.value };
                searchInput.value = '';
                collectionFilter.value = '';
                this.currentPage = 1;
                this.loadTemplateCatalog();
            });

            // Templates tab - Collection filter
            const collectionFilter = popup.querySelector('#collection-filter');
            collectionFilter.addEventListener('change', (e) => {
                // Reset search and other filters (same as main catalog)
                this.currentFilters = { collection: e.target.value };
                searchInput.value = '';
                categoryFilter.value = '';
                this.currentPage = 1;
                this.loadTemplateCatalog();
            });

            // Sections tab - Search functionality
            const sectionsSearchInput = popup.querySelector('.king-addons-sections-popup-search');
            let sectionsSearchTimeout;
            sectionsSearchInput.addEventListener('input', (e) => {
                clearTimeout(sectionsSearchTimeout);
                sectionsSearchTimeout = setTimeout(() => {
                    // Reset other filters when searching (same as main catalog)
                    this.currentSectionsFilters = { search: e.target.value };
                    sectionsCategoryFilter.value = '';
                    sectionsTypeFilter.value = '';
                    sectionsPlanFilter.value = '';
                    this.currentSectionsPage = 1;
                    this.loadSectionsCatalog();
                }, 300);
            });

            // Sections tab - Category filter
            const sectionsCategoryFilter = popup.querySelector('#sections-category-filter');
            sectionsCategoryFilter.addEventListener('change', (e) => {
                // Reset search and other filters (same as main catalog)
                this.currentSectionsFilters = { category: e.target.value };
                sectionsSearchInput.value = '';
                sectionsTypeFilter.value = '';
                sectionsPlanFilter.value = '';
                this.currentSectionsPage = 1;
                this.loadSectionsCatalog();
            });

            // Sections tab - Type filter
            const sectionsTypeFilter = popup.querySelector('#sections-type-filter');
            sectionsTypeFilter.addEventListener('change', (e) => {
                // Reset search and other filters (same as main catalog)
                this.currentSectionsFilters = { section_type: e.target.value };
                sectionsSearchInput.value = '';
                sectionsCategoryFilter.value = '';
                sectionsPlanFilter.value = '';
                this.currentSectionsPage = 1;
                this.loadSectionsCatalog();
            });

            // Sections tab - Plan filter
            const sectionsPlanFilter = popup.querySelector('#sections-plan-filter');
            sectionsPlanFilter.addEventListener('change', (e) => {
                // Reset search and other filters (same as main catalog)
                this.currentSectionsFilters = { plan: e.target.value };
                sectionsSearchInput.value = '';
                sectionsCategoryFilter.value = '';
                sectionsTypeFilter.value = '';
                this.currentSectionsPage = 1;
                this.loadSectionsCatalog();
            });

            // Templates reset button
            const resetTemplatesBtn = popup.querySelector('#reset-templates-filters');
            resetTemplatesBtn.addEventListener('click', () => {
                // Reset all templates filters and search
                this.currentFilters = {};
                this.currentPage = 1;
                searchInput.value = '';
                categoryFilter.value = '';
                collectionFilter.value = '';
                this.loadTemplateCatalog();
            });

            // Sections reset button  
            const resetSectionsBtn = popup.querySelector('#reset-sections-filters');
            resetSectionsBtn.addEventListener('click', () => {
                // Reset all sections filters and search
                this.currentSectionsFilters = {};
                this.currentSectionsPage = 1;
                sectionsSearchInput.value = '';
                sectionsCategoryFilter.value = '';
                sectionsTypeFilter.value = '';
                sectionsPlanFilter.value = '';
                this.loadSectionsCatalog();
            });

            // ESC key to close
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && popup.classList.contains('show')) {
                    this.closeTemplatePopup();
                }
            });
        }

        /**
         * Close template popup
         */
        closeTemplatePopup() {
            const popup = document.querySelector('.king-addons-template-popup');
            if (popup) {
                popup.classList.remove('show');
            }
        }

        /**
         * Create premium promo popup
         */
        createPremiumPromoPopup() {
            const promoPopup = document.createElement('div');
            promoPopup.className = 'king-addons-premium-promo-popup';
            
            promoPopup.innerHTML = `
                <div class="king-addons-premium-promo-popup-content">
                    <div class="king-addons-premium-promo-popup-wrapper">
                        <div class="king-addons-premium-promo-popup-txt">
                            <span class="king-addons-pr-popup-title">Want This Premium Template?</span>
                            <br><span class="king-addons-pr-popup-desc">
                                Get <span class="king-addons-pr-popup-desc-b">unlimited downloads</span> for just
                                <span class="king-addons-pr-popup-desc-b">$4.99/month</span> — keep them 
                                <span class="king-addons-pr-popup-desc-b">even after</span> your subscription ends!
                            </span>
                            <span class="king-addons-pr-popup-desc" style="font-size: 16px;opacity: 0.6;">
                                Trusted by 20,000+ users
                            </span>
                        </div>
                        <a class="purchase-btn" href="https://kingaddons.com/pricing/?utm_source=kng-elementor-popup-pro&utm_medium=plugin&utm_campaign=kng" target="_blank">
                            <button class="king-addons-premium-promo-popup-purchase-btn purchase-btn">
                                <img src="${window.kingAddonsTemplateCatalog.pluginUrl}includes/admin/img/icon-for-admin.svg" 
                                     style="margin-right: 7px;width: 16px;height: 16px;" 
                                     alt="Unlock All Templates">
                                Unlock All Templates
                            </button>
                        </a>
                        <button class="king-addons-close-premium-promo-popup">
                            Cancel
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(promoPopup);
            
            // Add event listener for close button
            const closeBtn = promoPopup.querySelector('.king-addons-close-premium-promo-popup');
            closeBtn.addEventListener('click', () => {
                this.closePremiumPromoPopup();
            });
            
            // Close on outside click
            promoPopup.addEventListener('click', (e) => {
                if (e.target === promoPopup) {
                    this.closePremiumPromoPopup();
                }
            });
        }

        /**
         * Show premium promo popup
         */
        showPremiumPromoPopup() {
            const promoPopup = document.querySelector('.king-addons-premium-promo-popup');
            if (promoPopup) {
                promoPopup.classList.add('show');
            }
        }

        /**
         * Close premium promo popup
         */
        closePremiumPromoPopup() {
            const promoPopup = document.querySelector('.king-addons-premium-promo-popup');
            if (promoPopup) {
                promoPopup.classList.remove('show');
            }
        }

        /**
         * Load template catalog data via AJAX
         */
        loadTemplateCatalog() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            
            const popup = document.querySelector('.king-addons-template-popup');
            const bodyElement = popup.querySelector('.king-addons-template-popup-body');
            
            bodyElement.innerHTML = `
                <div class="king-addons-template-popup-loading">
                    <div class="king-addons-template-spinner"></div>
                    Loading templates...
                </div>
            `;

            const formData = new FormData();
            formData.append('action', 'king_addons_get_template_catalog');
            formData.append('nonce', window.kingAddonsTemplateCatalog.nonce);
            formData.append('search', this.currentFilters.search || '');
            formData.append('category', this.currentFilters.category || '');
            formData.append('collection', this.currentFilters.collection || '');
            formData.append('page', this.currentPage);

            fetch(window.kingAddonsTemplateCatalog.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                this.isLoading = false;
                
                if (data.success) {
                    this.catalogData = data.data;
                    this.updateFilters();
                    this.renderTemplateGrid();
                } else {
                    bodyElement.innerHTML = `
                        <div class="king-addons-template-popup-error">
                            Error loading templates: ${data.data || 'Unknown error'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                this.isLoading = false;
                console.error('Error loading templates:', error);
                bodyElement.innerHTML = `
                    <div class="king-addons-template-popup-error">
                        Failed to load templates. Please try again.
                    </div>
                `;
            });
        }

        /**
         * Update filter dropdowns with available options
         */
        updateFilters() {
            if (!this.catalogData) return;

            const popup = document.querySelector('.king-addons-template-popup');
            const categoryFilter = popup.querySelector('#category-filter');
            const collectionFilter = popup.querySelector('#collection-filter');

            // Update categories
            categoryFilter.innerHTML = '<option value="">All Categories</option>';
            this.catalogData.categories.forEach(category => {
                const count = this.catalogData.category_counts[category] || 0;
                const option = document.createElement('option');
                option.value = category;
                option.textContent = `${category} (${count})`;
                if (category === this.currentFilters.category) {
                    option.selected = true;
                }
                categoryFilter.appendChild(option);
            });

            // Update collections
            collectionFilter.innerHTML = '<option value="">All Collections</option>';
            Object.entries(this.catalogData.collections).forEach(([id, name]) => {
                const option = document.createElement('option');
                option.value = id;
                option.textContent = name;
                if (id === this.currentFilters.collection) {
                    option.selected = true;
                }
                collectionFilter.appendChild(option);
            });
        }

        /**
         * Render template grid
         */
        renderTemplateGrid() {
            if (!this.catalogData) return;

            const popup = document.querySelector('.king-addons-template-popup');
            const bodyElement = popup.querySelector('.king-addons-template-popup-body');

            if (!this.catalogData.templates || this.catalogData.templates.length === 0) {
                bodyElement.innerHTML = `
                    <div class="king-addons-template-popup-empty">
                        No templates found. Try adjusting your search or filters.
                    </div>
                `;
                // Update templates count even when empty
                this.updateTemplatesCount();
                return;
            }

            const grid = document.createElement('div');
            grid.className = 'king-addons-template-grid';

            this.catalogData.templates.forEach(template => {
                const item = document.createElement('div');
                item.className = 'king-addons-template-item';
                item.dataset.templateKey = template.template_key;
                item.dataset.templatePlan = template.plan;

                const thumbnailUrl = `https://thumbnails.kingaddons.com/${template.template_key}.png?v=4`;
                
                item.innerHTML = `
                    <img class="king-addons-template-item-image" 
                         src="${thumbnailUrl}" 
                         alt="${template.title}" 
                         loading="lazy" />
                    <div class="king-addons-template-item-content">
                        <h3 class="king-addons-template-item-title">${template.title}</h3>
                        <span class="king-addons-template-item-plan ${template.plan}">${template.plan}</span>
                    </div>
                    <div class="king-addons-template-item-overlay">
                        <div class="king-addons-template-item-actions">
                            <button class="king-addons-template-import-btn" data-template-key="${template.template_key}" data-template-plan="${template.plan}">
                                Import Template
                            </button>
                            <a href="https://demo.kingaddons.com/${template.template_key}" class="king-addons-template-preview-btn" target="_blank">
                                Live Preview
                            </a>
                        </div>
                    </div>
                `;

                grid.appendChild(item);
            });

            // Add event listeners for template actions
            grid.querySelectorAll('.king-addons-template-import-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const templateKey = e.target.dataset.templateKey;
                    const templatePlan = e.target.dataset.templatePlan;
                    this.importTemplate(templateKey, templatePlan);
                });
            });

            // Create pagination
            const pagination = this.createPagination();

            bodyElement.innerHTML = '';
            bodyElement.appendChild(grid);
            bodyElement.appendChild(pagination);
            
            // Update templates count in tab
            this.updateTemplatesCount();
        }

        /**
         * Create pagination controls
         */
        createPagination() {
            const paginationContainer = document.createElement('div');
            paginationContainer.className = 'king-addons-template-pagination';

            const { current_page, total_pages, total_templates, items_per_page } = this.catalogData.pagination;
            
            // Previous button
            const prevBtn = document.createElement('button');
            prevBtn.textContent = '‹ Previous';
            prevBtn.disabled = current_page <= 1;
            prevBtn.addEventListener('click', () => {
                if (current_page > 1) {
                    this.currentPage = current_page - 1;
                    this.loadTemplateCatalog();
                }
            });

            // Page numbers
            const pageNumbers = [];
            const startPage = Math.max(1, current_page - 2);
            const endPage = Math.min(total_pages, current_page + 2);

            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                if (i === current_page) {
                    pageBtn.classList.add('active');
                }
                pageBtn.addEventListener('click', () => {
                    this.currentPage = i;
                    this.loadTemplateCatalog();
                });
                pageNumbers.push(pageBtn);
            }

            // Next button
            const nextBtn = document.createElement('button');
            nextBtn.textContent = 'Next ›';
            nextBtn.disabled = current_page >= total_pages;
            nextBtn.addEventListener('click', () => {
                if (current_page < total_pages) {
                    this.currentPage = current_page + 1;
                    this.loadTemplateCatalog();
                }
            });

            // Info text
            const infoText = document.createElement('div');
            infoText.className = 'king-addons-template-pagination-info';
            const startItem = (current_page - 1) * items_per_page + 1;
            const endItem = Math.min(current_page * items_per_page, total_templates);
            infoText.textContent = `Showing ${startItem}-${endItem} of ${total_templates} templates`;

            paginationContainer.appendChild(prevBtn);
            pageNumbers.forEach(btn => paginationContainer.appendChild(btn));
            paginationContainer.appendChild(nextBtn);
            paginationContainer.appendChild(infoText);

            return paginationContainer;
        }

        /**
         * Import selected template into current page
         */
        importTemplate(templateKey, templatePlan) {
            // Check permissions for premium templates
            if (templatePlan === 'premium' && !window.kingAddonsTemplateCatalog.isPremium) {
                this.showPremiumPromoPopup();
                return;
            }

            // Show import progress popup
            this.showImportProgress();

            // Close template catalog popup
            this.closeTemplatePopup();

            // Get template data first
            const formData = new FormData();
            formData.append('action', 'king_addons_import_template_to_page');
            formData.append('nonce', window.kingAddonsTemplateCatalog.nonce);
            formData.append('template_key', templateKey);
            formData.append('template_plan', templatePlan);

            this.updateImportProgress(5, `Fetching template data from King Addons API...`);

            fetch(window.kingAddonsTemplateCatalog.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                this.updateImportProgress(15, 'Received template data, validating...');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const templateData = data.data.template_data;
                    const imageCount = templateData.images ? templateData.images.length : 0;
                    
                    this.updateImportProgress(25, `Template validated! Found ${imageCount} images to process...`);
                    
                    console.log('Template data received:', {
                        title: templateData.title,
                        images: imageCount,
                        hasContent: !!templateData.content
                    });
                    
                    this.processTemplateImport(templateData);
                } else {
                    this.showImportError(data.data || 'Failed to fetch template data from API');
                }
            })
            .catch(error => {
                console.error('Error fetching template:', error);
                this.showImportError('Network error: ' + error.message);
            });
        }

        /**
         * Process template import into current Elementor page
         */
        processTemplateImport(templateData) {
            if (!templateData || !templateData.content) {
                this.showImportError('Invalid template data received');
                return;
            }

            const imageCount = templateData.images ? templateData.images.length : 0;
            this.updateImportProgress(35, `Starting import process... Preparing ${imageCount} images for download...`);

            // Get current page ID from Elementor - try multiple methods
            let pageId = null;
            
            // Method 1: elementor.config.post_id
            if (elementor && elementor.config && elementor.config.post_id) {
                pageId = elementor.config.post_id;
            }
            // Method 2: elementor.config.document.id
            else if (elementor && elementor.config && elementor.config.document && elementor.config.document.id) {
                pageId = elementor.config.document.id;
            }
            // Method 3: elementor.config.initial_document.id
            else if (elementor && elementor.config && elementor.config.initial_document && elementor.config.initial_document.id) {
                pageId = elementor.config.initial_document.id;
            }
            // Method 4: from URL parameters
            else {
                const urlParams = new URLSearchParams(window.location.search);
                const postParam = urlParams.get('post');
                if (postParam) {
                    pageId = parseInt(postParam);
                }
            }
            // Method 5: from WordPress admin globals
            if (!pageId && typeof window.pagenow !== 'undefined' && window.pagenow === 'toplevel_page_elementor') {
                const urlParams = new URLSearchParams(window.location.search);
                const postParam = urlParams.get('post');
                if (postParam) {
                    pageId = parseInt(postParam);
                }
            }
            // Method 6: from elementor globals
            if (!pageId && typeof elementorAdminConfig !== 'undefined' && elementorAdminConfig.post_id) {
                pageId = elementorAdminConfig.post_id;
            }
            // Method 7: from PHP localized data
            if (!pageId && window.kingAddonsTemplateCatalog && window.kingAddonsTemplateCatalog.currentPostId) {
                pageId = parseInt(window.kingAddonsTemplateCatalog.currentPostId);
            }

            if (!pageId) {
                // Debug information
                console.log('Debug: Could not determine page ID. Available data:', {
                    elementor: typeof elementor !== 'undefined' ? {
                        config: elementor.config,
                        configKeys: elementor.config ? Object.keys(elementor.config) : null
                    } : 'undefined',
                    windowLocation: window.location.href,
                    urlParams: new URLSearchParams(window.location.search).toString(),
                    elementorAdminConfig: typeof elementorAdminConfig !== 'undefined' ? elementorAdminConfig : 'undefined',
                    kingAddonsConfig: window.kingAddonsTemplateCatalog || 'undefined'
                });

                // Last resort: ask user to save the page first
                const userWantsToCreateNew = confirm(
                    'Could not determine current page ID. This might happen with unsaved pages.\n\n' +
                    'Do you want to:\n' +
                    '• Click "OK" to create a new page with this template\n' +
                    '• Click "Cancel" to save this page first and try again'
                );

                if (!userWantsToCreateNew) {
                    this.showImportError('Please save your page first, then try importing the template again.');
                    return;
                }

                // Create new page with template
                this.createNewPageWithTemplate(templateData);
                return;
            }

            // Use the proven original import system, adapted for current page
            this.updateImportProgress(55, `Initializing import using proven system...`);
            
            // Step 1: Initialize import with original system
            this.startOriginalStyleImport(templateData, pageId);
        }

        /**
         * Show import progress popup
         */
        showImportProgress() {
            // Remove existing progress popup if any
            const existingPopup = document.querySelector('.king-addons-import-progress-popup');
            if (existingPopup) {
                existingPopup.remove();
            }

            const popup = document.createElement('div');
            popup.className = 'king-addons-import-progress-popup show';
            
            popup.innerHTML = `
                <div class="king-addons-import-progress-content">
                    <h3 class="king-addons-import-progress-title">Importing Template</h3>
                    <div class="king-addons-import-progress-bar">
                        <div class="king-addons-import-progress-fill"></div>
                    </div>
                    <div class="king-addons-import-progress-text">Initializing...</div>
                        </div>
                    `;

            document.body.appendChild(popup);
        }

        /**
         * Update import progress
         */
        updateImportProgress(percentage, message, isSuccess = false) {
            const popup = document.querySelector('.king-addons-import-progress-popup');
            if (!popup) return;

            const progressFill = popup.querySelector('.king-addons-import-progress-fill');
            const progressText = popup.querySelector('.king-addons-import-progress-text');

            progressFill.style.width = percentage + '%';
            progressText.textContent = message;

            if (isSuccess) {
                progressText.classList.add('king-addons-import-success');
            }
        }

        /**
         * Show import error
         */
        showImportError(message) {
            const popup = document.querySelector('.king-addons-import-progress-popup');
            if (!popup) return;

            const progressText = popup.querySelector('.king-addons-import-progress-text');
            progressText.textContent = message;
            progressText.classList.add('king-addons-import-error');

            // Auto-close after 3 seconds
                    setTimeout(() => {
                this.closeImportProgress();
            }, 3000);
        }

        /**
         * Close import progress popup
         */
        closeImportProgress() {
            const popup = document.querySelector('.king-addons-import-progress-popup');
            if (popup) {
                popup.classList.remove('show');
                setTimeout(() => popup.remove(), 300);
            }
        }

        /**
         * Start import using original system adapted for current page
         */
        startOriginalStyleImport(templateData, pageId) {
            const imageCount = templateData.images ? templateData.images.length : 0;
            this.totalImages = imageCount;
            this.currentImageProgress = 0;
            this.pageId = pageId;
            
            this.updateImportProgress(60, `Setting up import session for ${imageCount} images...`);

            // Use original import_elementor_page_with_images but modify data for current page
            const modifiedData = {
                ...templateData,
                existing_page_id: pageId, // Signal that we want to add to existing page
                create_new_page: false
            };

            const formData = new URLSearchParams();
            formData.append('action', 'import_elementor_page_with_images');
            formData.append('data', JSON.stringify(modifiedData));

            fetch(window.kingAddonsTemplateCatalog.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(html => {
                        console.error('Server error:\n' + html);
                        throw new Error('Server error (not JSON).');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.updateImportProgress(70, `Import initialized! Processing ${imageCount} images...`);
                    
                    if (imageCount > 0) {
                        this.processOriginalStyleImages();
                    } else {
                        // No images, proceed directly to finalization
                        this.finalizeOriginalStyleImport();
                    }
                } else {
                    this.showImportError(data.data || 'Failed to initialize import');
                }
            })
            .catch(error => {
                console.error('Error starting import:', error);
                this.showImportError('Failed to start import: ' + error.message);
            });
        }

        /**
         * Process images using original system
         */
        processOriginalStyleImages() {
            const formData = new URLSearchParams();
            formData.append('action', 'process_import_images');

            fetch(window.kingAddonsTemplateCatalog.ajaxUrl, {
                method: 'POST', 
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(html => {
                        console.error('Server error:\n' + html);
                        throw new Error('Server error:\n' + html);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (data.data.progress !== undefined) {
                        // Continue processing images
                        const progress = data.data.progress;
                        const message = data.data.message || 'Processing images...';
                        
                        // Track image progress
                        if (data.data.image_url) {
                            this.currentImageProgress++;
                            console.log(`📷 Processed image ${this.currentImageProgress}/${this.totalImages}: ${data.data.image_url}`);
                        }
                        
                        // Update progress (70% to 85% for image processing)
                        const imageProgress = Math.round(70 + (progress / 100) * 15);
                        this.updateImportProgress(
                            imageProgress, 
                            `Processing images: ${this.currentImageProgress}/${this.totalImages} (${Math.round(progress)}%)`
                        );

                        // Continue processing
                        setTimeout(() => this.processOriginalStyleImages(), 300);
                    } else {
                        // Images completed, check if it's for existing page
                        if (data.data.processing_complete) {
                            console.log(`📷 Image processing complete: ${this.currentImageProgress}/${this.totalImages} for existing page`);
                            this.finalizeOriginalStyleImport();
                        } else {
                            // Original behavior - page created
                            console.log(`📷 Image processing complete: ${this.currentImageProgress}/${this.totalImages} - new page created`);
                            this.handleNewPageCreated(data.data);
                        }
                    }
                } else {
                    // Handle retry logic
                    if (data.data && data.data.retry) {
                        console.log('⚠️ Retrying image processing...');
                        setTimeout(() => this.processOriginalStyleImages(), 1000);
                    } else {
                        this.showImportError(data.data || 'Image processing failed');
                    }
                }
            })
            .catch(error => {
                console.error('Error processing images:', error);
                this.showImportError('Image processing error: ' + error.message);
            });
        }

        /**
         * Finalize import by merging with current page
         */
        finalizeOriginalStyleImport() {
            this.updateImportProgress(85, 'Merging template with current page...');

            // Use custom endpoint to merge with existing page instead of creating new one
            const formData = new URLSearchParams();
            formData.append('action', 'king_addons_merge_with_existing_page');
            formData.append('nonce', window.kingAddonsTemplateCatalog.nonce);
            formData.append('page_id', this.pageId);

            fetch(window.kingAddonsTemplateCatalog.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const result = data.data;
                    const importedCount = result.imported_elements || 0;
                    const imagesProcessed = this.currentImageProgress;
                    
                    this.updateImportProgress(90, 'Content merged! Refreshing editor preview...');
                    
                    console.log('📊 Final Import Statistics:', {
                        'Elements imported': importedCount,
                        'Images processed': imagesProcessed,
                        'Page ID': this.pageId
                    });
                    
                        let successMessage = `🎉 Template imported successfully! Added ${importedCount} elements`;
                        if (imagesProcessed > 0) {
                            successMessage += ` and ${imagesProcessed} images`;
                        }
                        successMessage += ' to your page.';
                        
                        this.updateImportProgress(100, successMessage, true);
                        
                        setTimeout(() => {
                            this.closeImportProgress();
                        
                        // Full page reload to properly show imported content
                        console.log('Template imported successfully! Reloading page to show content...');
                        window.location.reload();
                    }, 3000);
                } else {
                    this.showImportError(data.data || 'Failed to merge template with page');
                }
            })
            .catch(error => {
                console.error('Error finalizing import:', error);
                this.showImportError('Finalization error: ' + error.message);
            });
        }

        /**
         * Handle new page creation (fallback scenario)
         */
        handleNewPageCreated(data) {
            this.updateImportProgress(100, 'New page created successfully!', true);
            
            setTimeout(() => {
                this.closeImportProgress();
                this.closeTemplatePopup();
                
                // Ask user if they want to open the new page
                if (data.page_url) {
                    const openPage = confirm('New page created successfully! Do you want to open it in Elementor?');
                    if (openPage) {
                        const editUrl = data.page_url.replace(/\/$/, '') + '/?elementor';
                        window.open(editUrl, '_blank');
                    }
                }
            }, 2000);
        }



        /**
         * Safely reload Elementor preview
         */
        reloadElementorPreview(callback) {
            try {
                // Method 1: Try to use Elementor's built-in refresh
                if (elementor && elementor.getPreviewView && typeof elementor.getPreviewView === 'function') {
                    const previewView = elementor.getPreviewView();
                    
                    if (previewView && previewView.$el && previewView.$el.length > 0) {
                        const iframe = previewView.$el[0];
                        
                        if (iframe && iframe.contentWindow && iframe.contentWindow.location) {
                            console.log('Reloading preview via iframe.contentWindow.location.reload()');
                            iframe.contentWindow.location.reload();
                            
                            // Wait for reload and execute callback
                            if (callback) {
                                setTimeout(callback, 1500);
                            }
                            return;
                        }
                    }
                }

                // Method 2: Try to find preview iframe by selector
                const previewFrame = document.querySelector('#elementor-preview-iframe');
                if (previewFrame && previewFrame.contentWindow && previewFrame.contentWindow.location) {
                    console.log('Reloading preview via querySelector iframe');
                    previewFrame.contentWindow.location.reload();
                    
                    if (callback) {
                        setTimeout(callback, 1500);
                    }
                    return;
                }

                // Method 3: Try to use Elementor's saver to refresh content
                if (elementor && elementor.saver && typeof elementor.saver.reload === 'function') {
                    console.log('Reloading preview via elementor.saver.reload()');
                    elementor.saver.reload();
                    
                    if (callback) {
                        setTimeout(callback, 1000);
                    }
                    return;
                }

                // Method 4: Try to trigger Elementor's preview refresh event
                if (elementor && elementor.channels && elementor.channels.editor) {
                    console.log('Triggering preview refresh via Elementor channels');
                    elementor.channels.editor.trigger('preview:reload');
                    
                    if (callback) {
                        setTimeout(callback, 1000);
                    }
                    return;
                }

                // Method 5: Fallback - just execute callback without reload
                console.log('No preview reload method available, proceeding without reload');
                if (callback) {
                    callback();
                }

            } catch (error) {
                console.error('Error reloading preview:', error);
                if (callback) {
                    callback();
                }
            }
        }

        /**
         * Create new page with template (fallback method)
         */
        createNewPageWithTemplate(templateData) {
            this.updateImportProgress(30, 'Creating new page...');

            // Use the existing template import system (like the original catalog)
            const formData = new FormData();
            formData.append('action', 'import_elementor_page_with_images');
            formData.append('data', JSON.stringify(templateData));

            fetch(window.kingAddonsTemplateCatalog.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateImportProgress(50, 'Processing images...');
                    this.processImageImport();
                } else {
                    this.showImportError(data.data || 'Failed to create new page');
                }
            })
            .catch(error => {
                console.error('Error creating new page:', error);
                this.showImportError('Network error occurred while creating new page');
            });
        }

        /**
         * Process image import for new page creation
         */
        processImageImport() {
            const formData = new FormData();
            formData.append('action', 'process_import_images');

            fetch(window.kingAddonsTemplateCatalog.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.data.page_url) {
                        // Final success - page created
                        this.updateImportProgress(100, 'Page created successfully!', true);
                        
                        setTimeout(() => {
                            this.closeImportProgress();
                            this.closeTemplatePopup();
                            
                            // Ask user if they want to open the new page
                            const openPage = confirm('New page created successfully! Do you want to open it in Elementor?');
                            if (openPage) {
                                const editUrl = data.data.page_url.replace(/\/$/, '') + '/?elementor';
                                window.open(editUrl, '_blank');
                            }
                        }, 2000);
                    } else {
                        // Continue processing images
                        const progress = Math.min(90, 50 + (data.data.images_processed / data.data.total_images * 40));
                        this.updateImportProgress(progress, `Processing images... (${data.data.images_processed}/${data.data.total_images})`);
                        
                        // Continue processing
                        setTimeout(() => this.processImageImport(), 500);
                    }
                } else {
                    this.showImportError(data.data || 'Failed to process images');
                }
            })
            .catch(error => {
                console.error('Error processing images:', error);
                this.showImportError('Network error occurred during image processing');
            });
        }

        /**
         * Observe panel changes to re-add button if needed
         */
        observePanelChanges() {
            // Observe changes in the main editor
            const panel = document.querySelector('#elementor-panel');
            if (panel) {
                const observer = new MutationObserver((mutations) => {
                    let shouldCheck = false;
                    
                    mutations.forEach((mutation) => {
                        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                            shouldCheck = true;
                        }
                    });

                    if (shouldCheck) {
                        // Reset flag when content changes significantly
                        this.resetButtonFlag();
                        setTimeout(() => this.addButton(), 300);
                    }
                });

                observer.observe(panel, {
                    childList: true,
                    subtree: true
                });
            }

            // Also observe the preview iframe for content changes
            this.observePreviewChanges();
        }

        /**
         * Observe preview iframe changes
         */
        observePreviewChanges() {
            const preview = document.querySelector('#elementor-preview-iframe');
            if (!preview) {
                // Retry later if iframe not ready
                setTimeout(() => this.observePreviewChanges(), 1000);
                return;
            }

            // Wait for iframe to load
            preview.addEventListener('load', () => {
                const previewDoc = preview.contentDocument || preview.contentWindow.document;
                if (!previewDoc) return;

                // Reset button flag when iframe loads (new page/content)
                this.resetButtonFlag();

                // Add button immediately when iframe loads
                setTimeout(() => this.addButton(), 500);

                const observer = new MutationObserver((mutations) => {
                    let shouldReposition = false;
                    
                    mutations.forEach((mutation) => {
                        // Check if "Add New Section" was added/removed
                        if (mutation.type === 'childList') {
                            mutation.addedNodes.forEach(node => {
                                if (node.nodeType === 1 && 
                                    (node.id === 'elementor-add-new-section' || 
                                     node.querySelector && node.querySelector('#elementor-add-new-section'))) {
                                    shouldReposition = true;
                                }
                            });
                            
                            mutation.removedNodes.forEach(node => {
                                if (node.nodeType === 1 && 
                                    (node.id === 'elementor-add-new-section' || 
                                     node.querySelector && node.querySelector('#elementor-add-new-section'))) {
                                    shouldReposition = true;
                                }
                            });
                        }
                    });
                    
                    if (shouldReposition) {
                        // Remove existing button first
                        const existingButton = previewDoc.querySelector('.king-addons-template-catalog-content-area');
                        if (existingButton) {
                            existingButton.remove();
                        }
                        
                        // Re-add in correct position
                        setTimeout(() => this.addButton(), 200);
                    }
                });

                observer.observe(previewDoc.body, {
                    childList: true,
                    subtree: true
                });
            });

            // Also check periodically to ensure button is in correct position
            setInterval(() => {
                const previewDoc = preview.contentDocument || preview.contentWindow.document;
                if (previewDoc) {
                    const addNewSection = previewDoc.querySelector('#elementor-add-new-section');
                    const existingButton = previewDoc.querySelector('.king-addons-template-catalog-content-area');
                    
                    // If "Add New Section" exists but button is not positioned after it
                    if (addNewSection && existingButton) {
                        const nextSibling = addNewSection.nextSibling;
                        if (nextSibling !== existingButton) {
                            existingButton.remove();
                            this.resetButtonFlag();
                            this.addButton();
                        }
                    }
                    // If "Add New Section" exists but no button exists, add it
                    else if (addNewSection && !existingButton) {
                        this.resetButtonFlag();
                        this.addButton();
                    }
                    // If no "Add New Section" exists but button exists, remove button
                    else if (!addNewSection && existingButton) {
                        existingButton.remove();
                        this.resetButtonFlag();
                    }
                }
            }, 3000);
        }

        /**
         * Switch popup tab
         */
        switchPopupTab(tabId) {
            const popup = document.querySelector('.king-addons-template-popup');
            
            // Remove active class from all tabs and content
            popup.querySelectorAll('.king-addons-popup-tab-button').forEach(btn => btn.classList.remove('active'));
            popup.querySelectorAll('.king-addons-popup-tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to selected tab and content
            popup.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
            popup.querySelector(`#${tabId}-tab`).classList.add('active');
            
            // Load data for the tab if needed
            if (tabId === 'sections' && !this.sectionsLoaded) {
                this.loadSectionsCatalog();
            }
        }

        /**
         * Load sections catalog data via AJAX
         */
        loadSectionsCatalog() {
            if (this.isSectionsLoading) return;
            
            this.isSectionsLoading = true;
            
            const popup = document.querySelector('.king-addons-template-popup');
            const bodyElement = popup.querySelector('.king-addons-sections-popup-body');
            
            bodyElement.innerHTML = `
                <div class="king-addons-sections-popup-loading">
                    <div class="king-addons-template-spinner"></div>
                    Loading sections...
                </div>
            `;

            const formData = new FormData();
            formData.append('action', 'king_addons_get_sections_catalog');
            formData.append('nonce', window.kingAddonsTemplateCatalog.nonce);
            formData.append('search', this.currentSectionsFilters.search || '');
            formData.append('category', this.currentSectionsFilters.category || '');
            formData.append('section_type', this.currentSectionsFilters.section_type || '');
            formData.append('plan', this.currentSectionsFilters.plan || '');
            formData.append('page', this.currentSectionsPage);

            fetch(window.kingAddonsTemplateCatalog.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                this.isSectionsLoading = false;
                
                if (data.success) {
                    this.sectionsData = data.data;
                    this.sectionsLoaded = true;
                    this.renderSectionsGrid();
                    this.updateSectionsFilters();
                    this.updateSectionsCount();
                } else {
                    bodyElement.innerHTML = `
                        <div class="king-addons-sections-popup-empty">
                            Error loading sections: ${data.data || 'Unknown error'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                this.isSectionsLoading = false;
                console.error('Error loading sections:', error);
                bodyElement.innerHTML = `
                    <div class="king-addons-sections-popup-empty">
                        Failed to load sections. Please try again.
                    </div>
                `;
            });
        }

        /**
         * Render sections grid
         */
        renderSectionsGrid() {
            if (!this.sectionsData) return;

            const popup = document.querySelector('.king-addons-template-popup');
            const bodyElement = popup.querySelector('.king-addons-sections-popup-body');

            if (!this.sectionsData.sections || this.sectionsData.sections.length === 0) {
                bodyElement.innerHTML = `
                    <div class="king-addons-sections-popup-empty">
                        No sections found. Try adjusting your search or filters.
                    </div>
                `;
                // Update sections count even when empty
                this.updateSectionsCount();
                return;
            }

            const grid = document.createElement('div');
            grid.className = 'king-addons-sections-grid';

            this.sectionsData.sections.forEach(section => {
                const item = document.createElement('div');
                item.className = 'king-addons-section-item';
                item.dataset.sectionKey = section.section_key;
                item.dataset.sectionPlan = section.plan;

                // Use the correct screenshot URL pattern with plan-based paths  
                const screenshotUrl = `https://thumbnails.kingaddons.com/sections/${section.plan}/${section.section_key}.png?v=4`;
                
                item.innerHTML = `
                    <img class="king-addons-section-item-image" 
                         src="${screenshotUrl}" 
                         alt="${section.title}" 
                         loading="lazy"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImciIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjEwMCUiPjxzdG9wIG9mZnNldD0iMCUiIHN0b3AtY29sb3I9IiNmOGY5ZmEiLz48c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNlNWU3ZWIiLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2cpIi8+PGNpcmNsZSBjeD0iMTUwIiBjeT0iNzAiIHI9IjE2IiBmaWxsPSIjOWNhM2FmIiBvcGFjaXR5PSIwLjQiLz48cmVjdCB4PSIxMzQiIHk9Ijg2IiB3aWR0aD0iMzIiIGhlaWdodD0iNCIgZmlsbD0iIzljYTNhZiIgb3BhY2l0eT0iMC40IiByeD0iMiIvPjxyZWN0IHg9IjEyNiIgeT0iOTQiIHdpZHRoPSI0OCIgaGVpZ2h0PSI0IiBmaWxsPSIjOWNhM2FmIiBvcGFjaXR5PSIwLjMiIHJ4PSIyIi8+PHRleHQgeD0iNTAlIiB5PSIxMjAiIGZvbnQtZmFtaWx5PSItYXBwbGUtc3lzdGVtLCBCbGlua01hY1N5c3RlbUZvbnQsIHNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM2Yjc1ODQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIG9wYWNpdHk9IjAuNyI+U2VjdGlvbiBQcmV2aWV3PC90ZXh0Pjwvc3ZnPg=='" />
                    <div class="king-addons-section-item-content">
                        <h3 class="king-addons-section-item-title">${section.title}</h3>
                    </div>
                    <div class="king-addons-section-item-plan ${section.plan}">${section.plan}</div>
                    <div class="king-addons-section-item-overlay">
                        <div class="king-addons-section-item-actions">
                            <button class="king-addons-section-import-btn" data-section-key="${section.section_key}" data-section-plan="${section.plan}">
                                Import Section
                            </button>
                            <a href="https://sections.kingaddons.com/${section.section_key}" class="king-addons-section-preview-btn" target="_blank">
                                Live Preview
                            </a>
                        </div>
                    </div>
                `;

                grid.appendChild(item);
            });

            // Add event listeners for section actions
            grid.querySelectorAll('.king-addons-section-import-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const sectionKey = e.target.dataset.sectionKey;
                    const sectionPlan = e.target.dataset.sectionPlan;
                    this.importSection(sectionKey, sectionPlan);
                });
            });

            // Create pagination
            const pagination = this.createSectionsPagination();

            bodyElement.innerHTML = '';
            bodyElement.appendChild(grid);
            if (pagination) {
                bodyElement.appendChild(pagination);
            }
            
            // Update sections count in tab
            this.updateSectionsCount();
        }

        /**
         * Create sections pagination - Smart pagination like main catalog
         */
        createSectionsPagination() {
            if (!this.sectionsData || !this.sectionsData.pagination) return null;

            const pagination = this.sectionsData.pagination;
            if (pagination.total_pages <= 1) return null;

            const paginationDiv = document.createElement('div');
            paginationDiv.className = 'king-addons-sections-pagination';

            let paginationHtml = '<div class="pagination-inner">';
            
            const current = pagination.current_page;
            const total = pagination.total_pages;
            const endSize = 3;  // Show 3 pages at beginning and end
            const midSize = 2;  // Show 2 pages around current
            
            // Previous page
            if (current > 1) {
                paginationHtml += `<a href="#" data-page="${current - 1}">&larr; Previous</a>`;
            }
            
            // Smart pagination logic with proper ellipsis
            const pages = [];
            
            // Always show first pages
            for (let i = 1; i <= Math.min(endSize, total); i++) {
                pages.push(i);
            }
            
            // Calculate middle range around current page
            const start = Math.max(current - midSize, 1);
            const end = Math.min(current + midSize, total);
            
            // Add first ellipsis if there's a gap
            if (start > endSize + 1) {
                pages.push('...');
            }
            
            // Add middle pages around current (avoid duplicates with start/end)
            for (let i = Math.max(start, endSize + 1); i <= Math.min(end, total - endSize); i++) {
                if (pages.indexOf(i) === -1) {
                    pages.push(i);
                }
            }
            
            // Add second ellipsis if there's a gap
            if (end < total - endSize) {
                pages.push('...');
            }
            
            // Always show last pages (avoid duplicates)
            for (let i = Math.max(total - endSize + 1, endSize + 1); i <= total; i++) {
                if (pages.indexOf(i) === -1) {
                    pages.push(i);
                }
            }
            
            const uniquePages = pages;
            
            // Render pages
            uniquePages.forEach(page => {
                if (page === '...') {
                    paginationHtml += `<span class="dots">…</span>`;
                } else if (page === current) {
                    paginationHtml += `<span class="current">${page}</span>`;
                } else {
                    paginationHtml += `<a href="#" data-page="${page}">${page}</a>`;
                }
            });
            
            // Next page
            if (current < total) {
                paginationHtml += `<a href="#" data-page="${current + 1}">Next &rarr;</a>`;
            }
            
            paginationHtml += '</div>';
            paginationDiv.innerHTML = paginationHtml;

            // Add pagination event listeners
            paginationDiv.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const page = parseInt(e.target.dataset.page);
                    if (page) {
                        this.currentSectionsPage = page;
                        this.loadSectionsCatalog();
                    }
                });
            });

            return paginationDiv;
        }

        /**
         * Update sections filters dropdowns
         */
        updateSectionsFilters() {
            if (!this.sectionsData) return;

            const popup = document.querySelector('.king-addons-template-popup');

            // Update categories dropdown
            const categoriesSelect = popup.querySelector('#sections-category-filter');
            if (categoriesSelect && this.sectionsData.categories) {
                let categoriesHtml = '<option value="">All Categories</option>';
                this.sectionsData.categories.forEach(category => {
                    const displayName = category.charAt(0).toUpperCase() + category.slice(1).replace(/-/g, ' ');
                    categoriesHtml += `<option value="${category}">${displayName}</option>`;
                });
                categoriesSelect.innerHTML = categoriesHtml;
            }

            // Update types dropdown
            const typesSelect = popup.querySelector('#sections-type-filter');
            if (typesSelect && this.sectionsData.section_types) {
                let typesHtml = '<option value="">All Types</option>';
                this.sectionsData.section_types.forEach(type => {
                    const displayName = type.charAt(0).toUpperCase() + type.slice(1).replace(/-/g, ' ');
                    typesHtml += `<option value="${type}">${displayName}</option>`;
                });
                typesSelect.innerHTML = typesHtml;
            }
        }

        /**
         * Update sections count in tab
         */
        updateSectionsCount() {
            if (this.sectionsData && this.sectionsData.pagination) {
                const countElement = document.querySelector('#sections-tab-count');
                if (countElement) {
                    countElement.textContent = this.sectionsData.pagination.total_sections;
                }
            }
        }

        /**
         * Update templates count in tab
         */
        updateTemplatesCount() {
            if (this.catalogData && this.catalogData.pagination) {
                const countElement = document.querySelector('#templates-tab-count');
                if (countElement) {
                    countElement.textContent = this.catalogData.pagination.total_templates;
                }
            }
        }

        /**
         * Show import success message
         */
        showImportSuccess(message) {
            // Update the import progress popup with success message
            const progressPopup = document.querySelector('.king-addons-import-progress-popup');
            if (progressPopup) {
                const messageElement = progressPopup.querySelector('.king-addons-import-progress-text');
                if (messageElement) {
                    messageElement.innerHTML = `<div class="king-addons-import-success">${message}</div>`;
                }
                
                // Auto-hide after 3 seconds
                setTimeout(() => {
                    this.closeImportProgress();
                }, 3000);
            }
        }

        /**
         * Import selected section into current page
         */
        importSection(sectionKey, sectionPlan) {
            // Check permissions for premium sections
            if (sectionPlan === 'premium' && !window.kingAddonsTemplateCatalog.isPremium) {
                this.showPremiumPromoPopup();
                return;
            }

            // Show import progress popup
            this.showImportProgress();

            // Close template catalog popup
            this.closeTemplatePopup();

            // Get section data
            const formData = new FormData();
            formData.append('action', 'king_addons_import_section_to_page');
            formData.append('nonce', window.kingAddonsTemplateCatalog.nonce);
            formData.append('section_key', sectionKey);
            formData.append('section_plan', sectionPlan);

            this.updateImportProgress(5, `Loading section data...`);

            fetch(window.kingAddonsTemplateCatalog.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                this.updateImportProgress(15, 'Received section data, validating...');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const sectionData = data.data.section_data;
                    const imageCount = sectionData.images ? sectionData.images.length : 0;
                    
                    this.updateImportProgress(25, `Section validated! Found ${imageCount} images to process...`);
                    
                    console.log('Section data received:', {
                        title: sectionData.title,
                        images: imageCount,
                        hasContent: !!sectionData.content
                    });
                    
                    this.processSectionImport(sectionData);
                } else {
                    this.showImportError(data.data || 'Failed to load section data');
                }
            })
            .catch(error => {
                console.error('Error fetching section:', error);
                this.showImportError('Network error: ' + error.message);
            });
        }

        /**
         * Process section import into current Elementor page
         */
        processSectionImport(sectionData) {
            if (!sectionData || !sectionData.content) {
                this.showImportError('Invalid section data received');
                return;
            }

            const imageCount = sectionData.images ? sectionData.images.length : 0;
            this.updateImportProgress(35, `Starting import process... Preparing ${imageCount} images for download...`);

            // Get current page ID from Elementor - try multiple methods
            let pageId = null;
            
            // Method 1: elementor.config.post_id
            if (elementor && elementor.config && elementor.config.post_id) {
                pageId = elementor.config.post_id;
            }
            // Method 2: elementor.config.document.id
            else if (elementor && elementor.config && elementor.config.document && elementor.config.document.id) {
                pageId = elementor.config.document.id;
            }
            // Method 3: Check URL parameters
            else {
                const urlParams = new URLSearchParams(window.location.search);
                const postParam = urlParams.get('post');
                if (postParam) {
                    pageId = parseInt(postParam);
                }
            }

            if (!pageId) {
                this.showImportError('Could not determine current page ID for import');
                return;
            }

            this.updateImportProgress(45, `Page ID determined: ${pageId}. Starting section import...`);

            // Use existing Templates import system for sections
            const importData = {
                content: sectionData.content,
                images: sectionData.images || [],
                title: sectionData.title || 'Imported Section',
                elementor_version: sectionData.elementor_version || '3.0.0',
                existing_page_id: pageId,
                create_new_page: false  // Always merge with existing page for sections
            };

            // Call Templates import system
            const importFormData = new FormData();
            importFormData.append('action', 'import_elementor_page_with_images');
            importFormData.append('data', JSON.stringify(importData));

            this.updateImportProgress(55, 'Initializing section import with Templates system...');

            fetch(window.kingAddonsTemplateCatalog.ajaxUrl, {
                method: 'POST',
                body: importFormData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    this.updateImportProgress(65, 'Section import initialized! Processing images...');
                    // Start processing images using existing system
                    this.processImportImages();
                } else {
                    this.showImportError('Failed to initialize section import: ' + (result.data || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error initializing section import:', error);
                this.showImportError('Failed to initialize section import: ' + error.message);
            });
        }

        /**
         * Finalize section import by merging with current page (same as templates)
         */
        finalizeSectionImport() {
            this.updateImportProgress(85, 'Merging section with current page...');

            // Get current page ID - use same method as in processSectionImport
            let pageId = null;
            
            if (elementor && elementor.config && elementor.config.post_id) {
                pageId = elementor.config.post_id;
            } else if (elementor && elementor.config && elementor.config.document && elementor.config.document.id) {
                pageId = elementor.config.document.id;
            } else {
                const urlParams = new URLSearchParams(window.location.search);
                const postParam = urlParams.get('post');
                if (postParam) {
                    pageId = parseInt(postParam);
                }
            }

            if (!pageId) {
                this.showImportError('Could not determine page ID for final merge');
                return;
            }

            // Use same merge endpoint as templates
            const formData = new URLSearchParams();
            formData.append('action', 'king_addons_merge_with_existing_page');
            formData.append('nonce', window.kingAddonsTemplateCatalog.nonce);
            formData.append('page_id', pageId);

            fetch(window.kingAddonsTemplateCatalog.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const result = data.data;
                    const importedCount = result.imported_elements || 0;
                    
                    this.updateImportProgress(90, 'Section merged! Refreshing editor preview...');
                    
                    console.log('📊 Section Import Statistics:', {
                        'Elements imported': importedCount,
                        'Page ID': pageId
                    });
                    
                    let successMessage = `🎉 Section imported successfully! Added ${importedCount} elements to your page.`;
                    this.updateImportProgress(100, successMessage, true);
                    
                    setTimeout(() => {
                        this.closeImportProgress();
                        
                        // Full page reload to properly show imported content
                        // elementor.reloadPreview() doesn't refresh editor data from database
                        console.log('Section imported successfully! Reloading page to show content...');
                        window.location.reload();
                    }, 2000);
                } else {
                    this.showImportError('Failed to merge section with page: ' + (data.data || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error finalizing section import:', error);
                this.showImportError('Finalization error: ' + error.message);
            });
        }

        /**
         * Process import images for sections (reuse existing logic)
         */
        processImportImages() {
            const processNextImage = () => {
                const formData = new FormData();
                formData.append('action', 'process_import_images');

                fetch(window.kingAddonsTemplateCatalog.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.data.progress !== undefined) {
                            // Update progress
                            const progress = Math.min(65 + (data.data.progress * 0.35), 100); // Scale progress from 65% to 100%
                            this.updateImportProgress(progress, data.data.message || 'Processing images...');
                            
                            // Continue processing
                            setTimeout(processNextImage, 500);
                        } else {
                            // Images processed, now finalize section import (merge with page)
                            this.finalizeSectionImport();
                        }
                    } else {
                        // Continue on error (skip failed images)
                        if (data.data && data.data.retry) {
                            setTimeout(processNextImage, 1000);
                        } else {
                            this.showImportError('Image processing failed: ' + (data.data || 'Unknown error'));
                        }
                    }
                })
                .catch(error => {
                    console.error('Error processing images:', error);
                    // Continue processing other images
                    setTimeout(processNextImage, 1000);
                });
            };

            processNextImage();
        }
    }

    // Initialize when DOM is ready
    $(document).ready(() => {
        new TemplateCatalogButton();
    });

})(jQuery);
