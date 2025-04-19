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
    });
}(jQuery));