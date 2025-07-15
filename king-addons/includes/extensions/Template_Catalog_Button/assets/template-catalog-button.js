/**
 * King Addons Template Catalog Button for Elementor Editor
 * 
 * This script adds a template catalog button to the Elementor editor panel
 * that opens the King Addons template catalog in a new tab
 */

(function($) {
    'use strict';

    /**
     * Template Catalog Button Handler
     */
    class TemplateCatalogButton {
        
        constructor() {
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
         * Add the template catalog button to the editor
         */
        addButton() {
            // Check if we have the required data
            if (!window.kingAddonsTemplateCatalog || !window.kingAddonsTemplateCatalog.templatesEnabled) {
                return;
            }

            // Check if button already exists
            if (document.querySelector('.king-addons-template-catalog-btn')) {
                return;
            }

            // Try to add button to the content area where "Drag widget here" is shown
            this.tryAddToContentArea() || this.tryAddToEmptyContainer();
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

            // Find the main content area or empty sections
            const targetAreas = [
                // Main content wrapper
                previewDoc.querySelector('.elementor'),
                previewDoc.querySelector('#elementor'),
                previewDoc.querySelector('.elementor-inner'),
                // Empty sections
                ...previewDoc.querySelectorAll('.elementor-section-wrap'),
                // Empty containers
                ...previewDoc.querySelectorAll('.elementor-container'),
                // Body as fallback
                previewDoc.querySelector('body')
            ];

            for (const area of targetAreas) {
                if (area && this.isAreaSuitableForButton(area)) {
                    const buttonContainer = this.createContentAreaButton();
                    
                    // Add button at the bottom of the area
                    area.appendChild(buttonContainer);
                    return true;
                }
            }

            return false;
        }

        /**
         * Try to add button to empty container
         */
        tryAddToEmptyContainer() {
            // As fallback, add to the main Elementor editing area
            const editingArea = document.querySelector('#elementor-preview-iframe');
            if (!editingArea) return false;

            const previewDoc = editingArea.contentDocument || editingArea.contentWindow.document;
            if (!previewDoc) return false;

            const body = previewDoc.querySelector('body');
            if (!body) return false;

            const buttonContainer = this.createOverlayButton();
            body.appendChild(buttonContainer);
            return true;
        }

        /**
         * Check if area is visible and suitable for button placement
         */
        isAreaVisible(element) {
            const rect = element.getBoundingClientRect();
            return rect.width > 200 && rect.height > 100 && 
                   getComputedStyle(element).display !== 'none';
        }

        /**
         * Check if area is suitable for bottom button placement
         */
        isAreaSuitableForButton(element) {
            if (!element) return false;
            
            // Check if button already exists in this area
            if (element.querySelector('.king-addons-template-catalog-content-area')) {
                return false;
            }
            
            const rect = element.getBoundingClientRect();
            const style = getComputedStyle(element);
            
            // Area should be visible and have reasonable dimensions
            return rect.width > 300 && 
                   style.display !== 'none' && 
                   style.visibility !== 'hidden';
        }

        /**
         * Create button for content area placement
         */
        createContentAreaButton() {
            const container = document.createElement('div');
            container.className = 'king-addons-template-catalog-content-area';
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
                border: 3px dashed #93c5fd;
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
                this.openTemplateCatalog();
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
                    this.openTemplateCatalog();
                }
            });

            container.appendChild(iconWrapper);
            container.appendChild(title);
            container.appendChild(subtitle);
            container.appendChild(button);

            return container;
        }

        /**
         * Create overlay button as fallback
         */
        createOverlayButton() {
            const container = document.createElement('div');
            container.className = 'king-addons-template-catalog-overlay';
            container.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                z-index: 999999;
                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(59, 130, 246, 0.1);
                text-align: center;
                max-width: 420px;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
            `;

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
                margin: 0 auto 20px auto;
                box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
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
                margin: 0 0 16px 0;
                font-size: 24px;
                font-weight: 700;
                color: #1e293b;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                letter-spacing: -0.025em;
            `;

            const subtitle = document.createElement('p');
            subtitle.textContent = 'Choose from professional templates';
            subtitle.style.cssText = `
                margin: 0 0 24px 0;
                font-size: 16px;
                color: #64748b;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                line-height: 1.5;
            `;

            const button = document.createElement('button');
            button.className = 'king-addons-template-catalog-btn';
            button.style.cssText = `
                background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                color: white;
                border: none;
                padding: 14px 28px;
                border-radius: 12px;
                font-size: 16px;
                font-weight: 600;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s ease;
                box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
                text-decoration: none;
                outline: none;
                letter-spacing: 0.025em;
                min-width: 180px;
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

            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.openTemplateCatalog();
            });

            // Add close button
            const closeButton = document.createElement('button');
            closeButton.innerHTML = '×';
            closeButton.style.cssText = `
                position: absolute;
                top: 15px;
                right: 15px;
                background: none;
                border: none;
                font-size: 24px;
                color: #94a3b8;
                cursor: pointer;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: all 0.2s ease;
            `;

            closeButton.addEventListener('mouseenter', () => {
                closeButton.style.background = '#f1f5f9';
                closeButton.style.color = '#64748b';
            });

            closeButton.addEventListener('mouseleave', () => {
                closeButton.style.background = 'none';
                closeButton.style.color = '#94a3b8';
            });

            closeButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                container.remove();
            });

            // Add responsive styles for overlay
            if (window.innerWidth <= 768) {
                container.style.maxWidth = '90vw';
                container.style.padding = '30px 20px';
                title.style.fontSize = '20px';
                subtitle.style.fontSize = '14px';
                button.style.padding = '12px 20px';
                button.style.minWidth = '140px';
                iconWrapper.style.width = '56px';
                iconWrapper.style.height = '56px';
                icon.style.fontSize = '20px';
            }

            container.appendChild(closeButton);
            container.appendChild(iconWrapper);
            container.appendChild(title);
            container.appendChild(subtitle);
            container.appendChild(button);

            return container;
        }

        /**
         * Open template catalog in new tab
         */
        openTemplateCatalog() {
            const url = window.kingAddonsTemplateCatalog.templateCatalogUrl;
            if (url) {
                // Add loading state to buttons
                const buttons = document.querySelectorAll('.king-addons-template-catalog-btn');
                buttons.forEach(btn => {
                    const originalText = btn.innerHTML;
                    btn.innerHTML = `
                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                            <div style="width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top: 2px solid white; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                            <span>Opening...</span>
                        </div>
                    `;
                    btn.style.pointerEvents = 'none';
                    
                    // Add spin animation
                    if (!document.querySelector('#king-addons-spin-animation')) {
                        const spinStyle = document.createElement('style');
                        spinStyle.id = 'king-addons-spin-animation';
                        spinStyle.textContent = `
                            @keyframes spin {
                                0% { transform: rotate(0deg); }
                                100% { transform: rotate(360deg); }
                            }
                        `;
                        document.head.appendChild(spinStyle);
                    }
                    
                    // Reset after a delay
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.style.pointerEvents = 'auto';
                    }, 1500);
                });
                
                window.open(url, '_blank', 'noopener,noreferrer');
            }
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

                // Add button immediately when iframe loads
                setTimeout(() => this.addButton(), 500);

                const observer = new MutationObserver(() => {
                    // Check if button is still present, if not re-add
                    const existingButton = previewDoc.querySelector('.king-addons-template-catalog-content-area');
                    if (!existingButton) {
                        setTimeout(() => this.addButton(), 200);
                    }
                });

                observer.observe(previewDoc.body, {
                    childList: true,
                    subtree: true
                });
            });

            // Also check periodically to ensure button is present
            setInterval(() => {
                const previewDoc = preview.contentDocument || preview.contentWindow.document;
                if (previewDoc) {
                    const existingButton = previewDoc.querySelector('.king-addons-template-catalog-content-area, .king-addons-template-catalog-overlay');
                    if (!existingButton) {
                        this.addButton();
                    }
                }
            }, 3000);
        }
    }

    // Initialize when DOM is ready
    $(document).ready(() => {
        new TemplateCatalogButton();
    });

})(jQuery);
