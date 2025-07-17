"use strict";

(function($) {
    function createProTooltip() {
        const tooltip = document.createElement('div');
        tooltip.className = 'king-addons-pro-tooltip';
        tooltip.innerHTML = `
            <p>This is available in <strong><a href="https://kingaddons.com/pricing/?utm_source=kng-module-upgrade-pro&utm_medium=plugin&utm_campaign=kng" target="_blank">King Addons PRO</a></strong> version</p>
        `;
        return tooltip;
    }

    function appendProTooltip(controlElement) {
        // Only append if a tooltip doesn't already exist
        if (!controlElement.querySelector('.king-addons-pro-tooltip')) {
            controlElement.appendChild(createProTooltip());
        }
    }

    // Function for handling custom menu and submenu
    function setupMegaMenuItemsHandling() {
        // Carefully apply styles to submenu elements
        function markSubmenuItems() {
            $('.elementor-control-submenu_items .elementor-repeater-fields').addClass('king-addons-submenu-item');
            
            $('.elementor-control-item_type select').each(function() {
                if ($(this).val() === 'submenu') {
                    $(this).closest('.elementor-repeater-fields').addClass('king-addons-parent-submenu-item');
                }
            });
        }
        
        // Process menu item type change events
        $(document).on('change', '.elementor-control-item_type select', function() {
            var $select = $(this);
            var selectedValue = $select.val();
            
            // If submenu type is selected
            if (selectedValue === 'submenu') {
                // Find item container
                var $itemContainer = $select.closest('.elementor-repeater-fields');
                
                // Mark element as parent for submenu
                $itemContainer.addClass('king-addons-parent-submenu-item');
            } else {
                $select.closest('.elementor-repeater-fields').removeClass('king-addons-parent-submenu-item');
            }
            
            // Apply styles to all submenu items after change
            markSubmenuItems();
        });
        
        // Immediately style submenu items on page load
        markSubmenuItems();
        
        // Apply styles when loading panel
        if (window.elementor) {
            elementor.hooks.addAction('panel/open_editor/widget', function(panel, model, view) {
                if (model.attributes.widgetType === 'king-mega-menu' || model.attributes.widgetType === 'king-mega-menu-pro') {
                    // Apply styles and classes on first opening
                    markSubmenuItems();
                    
                    // Check for submenu items every 500ms for the first few seconds
                    // to catch items that might be added dynamically
                    for (let i = 1; i <= 10; i++) {
                        setTimeout(markSubmenuItems, i * 500);
                    }
                    
                    // Track element addition via MutationObserver more safely
                    try {
                        const observer = new MutationObserver(function(mutations) {
                            mutations.forEach(function(mutation) {
                                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                                    // If new nodes are added, apply styles
                                    if ($(mutation.target).closest('.elementor-control-submenu_items').length > 0) {
                                        // If change is in submenu
                                        markSubmenuItems();
                                    }
                                }
                            });
                        });
                        
                        // Start observing the entire editor, but with a narrower focus on changes
                        observer.observe(panel.$el[0], {
                            childList: true,
                            subtree: true,
                            attributes: false
                        });
                        
                        // Stop observing when panel is closed
                        elementor.hooks.addAction('panel/close_editor', function() {
                            observer.disconnect();
                        });
                    } catch (error) {
                        console.error('Error when setting up observer:', error);
                    }
                }
            });
            
            // Hook after adding a new element to Elementor repeater
            $(document).on('DOMNodeInserted', '.elementor-repeater-row-item-title', function(e) {
                // Apply styles after any item is inserted
                setTimeout(markSubmenuItems, 150);
            });
        }
        
        // Add styles for visual distinction of submenu elements
        try {
            const style = document.createElement('style');
            style.textContent = `
                .king-addons-parent-submenu-item {
                    border-left: 3px solid #4054b2 !important;
                    background-color: rgba(64, 84, 178, 0.05) !important;
                }
                .king-addons-submenu-item {
                    border-left: 3px solid #6d7882 !important;
                    margin-left: 15px !important;
                    width: calc(100% - 15px) !important;
                }
                
                /* Custom styles for bug report section */
                .king-addons-help-wrapper {
                    width: 100%;
                    max-width: 100%;
                    box-sizing: border-box;
                }
                
                .king-addons-bug-report {
                    background: #ffffff;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    padding: 15px;
                    margin-top: 8px;
                    line-height: 1.4;
                    width: 100%;
                    box-sizing: border-box;
                }
                
                .king-addons-bug-report-title {
                    font-weight: 600;
                    font-size: 14px;
                    color: #333;
                    margin-bottom: 8px;
                }
                
                .king-addons-bug-report-text {
                    font-size: 12px;
                    color: #666;
                    margin-bottom: 10px;
                }
                
                .king-addons-bug-report-email-section {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .king-addons-bug-report-email-field {
                    flex: 1;
                    background: #f8f9fa;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    padding: 6px 10px;
                    font-family: monospace;
                    font-size: 11px;
                    color: #333;
                    user-select: all;
                }
                
                .king-addons-bug-report-copy-btn {
                    padding: 6px 12px;
                    background: #666666;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 11px;
                    font-weight: 500;
                    transition: background 0.2s;
                }
                
                .king-addons-bug-report-copy-btn:hover {
                    background: #2d3a80;
                }
                
                .king-addons-bug-report-copy-btn.copied {
                    background: #28a745;
                }

                /* Promo block styles */
                .king-addons-promo-block {
                    background: linear-gradient(135deg, #93117e 0%, #d946ef 100%);
                    border-radius: 6px;
                    padding: 16px;
                    margin-top: 12px;
                    margin-bottom: 0;
                    color: white;
                    position: relative;
                    overflow: hidden;
                    width: 100%;
                    box-sizing: border-box;
                }
                
                .king-addons-promo-block::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    right: 0;
                    bottom: 0;
                    left: 0;
                    background: rgba(255, 255, 255, 0.05);
                    border-radius: 6px;
                    pointer-events: none;
                }
                
                .king-addons-promo-title {
                    font-weight: 600;
                    font-size: 13px;
                    margin-bottom: 6px;
                    line-height: 1.3;
                    position: relative;
                    z-index: 1;
                }
                
                .king-addons-promo-text {
                    font-size: 11px;
                    line-height: 1.4;
                    margin-bottom: 8px;
                    opacity: 0.9;
                    position: relative;
                    z-index: 1;
                }
                
                .king-addons-promo-highlight {
                    font-weight: 600;
                    color: #fbbf24;
                }
                
                .king-addons-promo-small {
                    font-size: 10px;
                    opacity: 0.8;
                    margin-bottom: 10px;
                    position: relative;
                    z-index: 1;
                }
                
                .king-addons-promo-buttons {
                    display: flex;
                    gap: 8px;
                    position: relative;
                    z-index: 1;
                    flex-wrap: wrap;
                }
                
                .king-addons-promo-btn {
                    padding: 6px 12px;
                    border: none;
                    border-radius: 4px;
                    font-size: 11px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s;
                    text-decoration: none;
                    display: inline-block;
                    white-space: nowrap;
                }
                
                .king-addons-promo-btn-primary {
                    background: #ffffff;
                    color: #93117e;
                }
                
                .king-addons-promo-btn-primary:hover {
                    background: #f3f4f6;
                    transform: translateY(-1px);
                }
                
                .king-addons-promo-btn-secondary {
                    background: rgba(255, 255, 255, 0.2);
                    color: white;
                }
                
                .king-addons-promo-btn-secondary:hover {
                    background: rgba(255, 255, 255, 0.3);
                    color: rgb(255 255 255 / 80%);
                }

                /* Dark mode styles using system preference */
                @media (prefers-color-scheme: dark) {
                    .king-addons-bug-report {
                        background: #2c2c2c;
                        border-color: #444;
                    }
                    
                    .king-addons-bug-report-title {
                        color: #ffffff;
                    }
                    
                    .king-addons-bug-report-text {
                        color: #b0b0b0;
                    }
                    
                    .king-addons-bug-report-email-field {
                        background: #383838;
                        border-color: #555;
                        color: #ffffff;
                    }
                }
            `;
            document.head.appendChild(style);
        } catch (styleError) {
            console.error('Error when adding styles:', styleError);
        }
    }

    $(window).on('elementor:init', function() {
        console.log('elementor:init event fired for mega menu editor');
        const panelEl = document.getElementById('elementor-panel');

        // Watch for newly-added .king-addons-pro-control elements
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            // Find .king-addons-pro-control .elementor-control-content in newly added nodes
                            const proControls = node.querySelectorAll('.king-addons-pro-control .elementor-control-content');
                            proControls.forEach(appendProTooltip);
                        }
                    });
                }
            });
        });

        // Start observing if the elementor panel is found
        if (panelEl) {
            observer.observe(panelEl, {
                childList: true,
                subtree: true
            });
        }

        // Initial pass for any controls already in the DOM
        const initialProControls = document.querySelectorAll('.king-addons-pro-control .elementor-control-content');
        initialProControls.forEach(appendProTooltip);
        
        // Initialize custom menu handling
        setupMegaMenuItemsHandling();

        // Replace default "Need Help" link with King Addons bug report section
        (function replaceHelpLink() {
            const EMAIL = 'contact@kingaddons.com';

            // Function to copy text to clipboard
            function copyToClipboard(text) {
                return new Promise((resolve, reject) => {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text)
                            .then(() => resolve(true))
                            .catch(err => reject(err));
                    } else {
                        // Fallback for older browsers
                        const textArea = document.createElement('textarea');
                        textArea.value = text;
                        textArea.style.position = 'fixed';
                        textArea.style.left = '-999999px';
                        textArea.style.top = '-999999px';
                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
                        
                        try {
                            document.execCommand('copy');
                            document.body.removeChild(textArea);
                            resolve(true);
                        } catch (err) {
                            document.body.removeChild(textArea);
                            reject(err);
                        }
                    }
                });
            }

            function createBugReportSection() {
                // Create new bug report section
                const bugReportSection = document.createElement('div');
                bugReportSection.className = 'king-addons-bug-report';
                bugReportSection.innerHTML = `
                    <div class="king-addons-bug-report-title">Questions, bugs, feedback, or ideas?</div>
                    <div class="king-addons-bug-report-text">Reach out to us at ${EMAIL} we love hearing from our users!</div>
                    <div class="king-addons-bug-report-email-section">
                        <span class="king-addons-bug-report-email-field">${EMAIL}</span>
                        <button class="king-addons-bug-report-copy-btn">Copy</button>
                    </div>
                `;

                // Add copy functionality
                const copyBtn = bugReportSection.querySelector('.king-addons-bug-report-copy-btn');
                
                // Copy email address function
                copyBtn.addEventListener('click', () => {
                    copyToClipboard(EMAIL).then(() => {
                        copyBtn.textContent = 'Copied!';
                        copyBtn.classList.add('copied');
                        setTimeout(() => {
                            copyBtn.textContent = 'Copy';
                            copyBtn.classList.remove('copied');
                        }, 2000);
                    }).catch(err => {
                        console.error('Error copying email:', err);
                        alert('Failed to copy email address.');
                    });
                });

                return bugReportSection;
            }

            function createPromoBlock() {
                // Check if PRO is active
                if (typeof kingAddonsEditor !== 'undefined' && kingAddonsEditor.isPro) {
                    return null; // Don't show promo for PRO users
                }
                
                const promoBlock = document.createElement('div');
                promoBlock.className = 'king-addons-promo-block';
                promoBlock.innerHTML = `
                    <div class="king-addons-promo-title">Unlock <span class="king-addons-promo-highlight">650+</span> premium templates and <span class="king-addons-promo-highlight">200+</span> advanced features for only <span class="king-addons-promo-highlight">$4.99</span>/month.</div>
                    <div class="king-addons-promo-text">Upgrade now and boost your website's performance!</div>
                    <div class="king-addons-promo-small">Trusted by 20,000+ users</div>
                    <div class="king-addons-promo-buttons">
                        <a href="https://kingaddons.com/pricing/?utm_source=kng-module-elementor-panel-upgrade&utm_medium=plugin&utm_campaign=kng" target="_blank" class="king-addons-promo-btn king-addons-promo-btn-primary">Upgrade Now</a>
                        <a href="https://kingaddons.com/pricing/?utm_source=kng-module-elementor-panel-lm&utm_medium=plugin&utm_campaign=kng" target="_blank" class="king-addons-promo-btn king-addons-promo-btn-secondary">Learn More</a>
                    </div>
                `;
                return promoBlock;
            }

            function replaceBugReportInPanel() {
                const helpSection = document.querySelector('#elementor-panel__editor__help');
                if (helpSection) {
                    // Clear existing content and create wrapper
                    helpSection.innerHTML = '';
                    
                    // Create wrapper div
                    const wrapper = document.createElement('div');
                    wrapper.className = 'king-addons-help-wrapper';
                    
                    // Add promo block only if not PRO user
                    const promoBlock = createPromoBlock();
                    if (promoBlock) {
                        wrapper.appendChild(promoBlock);
                    }
                    
                    // Always add bug report section
                    wrapper.appendChild(createBugReportSection());
                    
                    // Add wrapper to help section
                    helpSection.appendChild(wrapper);
                }
            }

            // Hook into Elementor panel events to replace help section for King Addons widgets
            if (window.elementor) {
                elementor.hooks.addAction('panel/open_editor/widget', function(panel, model, view) {
                    const widgetType = model.attributes.widgetType;
                    
                    // Check if it's a King Addons widget
                    if (widgetType && widgetType.startsWith('king-addons-')) {
                        // Wait a bit for the panel to fully load, then replace the help section
                        setTimeout(() => {
                            replaceBugReportInPanel();
                        }, 100);
                    }
                });
            }

            // Initial replacement if we're already on a King Addons widget
            setTimeout(() => {
                if (window.elementor && elementor.panel && elementor.panel.currentView) {
                    const currentModel = elementor.panel.currentView.model;
                    if (currentModel && currentModel.attributes.widgetType && currentModel.attributes.widgetType.startsWith('king-addons-')) {
                        replaceBugReportInPanel();
                    }
                }
            }, 500);
        })();
    });

}(jQuery));