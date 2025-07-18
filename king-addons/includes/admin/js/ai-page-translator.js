(function($, elementor) {
    'use strict';

    // Check if Elementor and our AI settings exist
    if (!elementor || !window.KingAddonsAiField) {
        return;
    }

    // Translation progress tracking
    var translationState = {
        isTranslating: false,
        totalElements: 0,
        translatedElements: 0,
        failedElements: 0,
        currentElement: null,
        fromLang: '',
        toLang: '',
        isCancelled: false,
        currentRequests: [] // Store active AJAX requests to cancel them
    };

    // Language options
    var languages = {
        'en': 'English',
        'es': 'Spanish (Español)',
        'fr': 'French (Français)', 
        'de': 'German (Deutsch)',
        'it': 'Italian (Italiano)',
        'pt': 'Portuguese (Português)',
        'ru': 'Russian (Русский)',
        'ja': 'Japanese (日本語)',
        'ko': 'Korean (한국어)',
        'zh': 'Chinese (中文)',
        'ar': 'Arabic (العربية)',
        'hi': 'Hindi (हिन्दी)',
        'nl': 'Dutch (Nederlands)',
        'pl': 'Polish (Polski)',
        'tr': 'Turkish (Türkçe)',
        'uk': 'Ukrainian (Українська)',
        'cs': 'Czech (Čeština)',
        'sv': 'Swedish (Svenska)',
        'no': 'Norwegian (Norsk)',
        'da': 'Danish (Dansk)',
        'fi': 'Finnish (Suomi)'
    };

    /**
     * Check if premium version is active
     */
    function isPremiumActive() {
        // Check for premium indicators
        return !!(
            window.KingAddonsPro ||
            window.kingAddonsPro ||
            (window.KingAddonsAiField && window.KingAddonsAiField.is_pro) ||
            (window.KingAddonsAiField && window.KingAddonsAiField.premium_active) ||
            document.querySelector('body.king-addons-pro') ||
            (typeof jQuery !== 'undefined' && jQuery('body').hasClass('king-addons-pro'))
        );
    }

    /**
     * Inject CSS styles for the translator
     */
    function injectTranslatorStyles() {
        if ($('#king-addons-ai-translator-styles').length === 0) {
            const styles = `
                <style id="king-addons-ai-translator-styles">
                    /* Translator Button Styles */
                    .king-addons-ai-translator-btn {
                        background: linear-gradient(135deg, #E1CBFF, #5B03FF) !important;
                        border: none !important;
                        color: white !important;
                        padding: 8px 12px !important;
                        border-radius: 6px !important;
                        font-size: 12px !important;
                        font-weight: 500 !important;
                        cursor: pointer !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        gap: 6px !important;
                        transition: all 0.3s ease !important;
                        margin: 8px !important;
                        position: relative !important;
                        z-index: 10 !important;
                        text-decoration: none !important;
                        outline: none !important;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
                    }
                    .king-addons-ai-translator-btn:hover {
                        background: linear-gradient(135deg, #d4a3ff, #4f00e6) !important;
                        box-shadow: 0 4px 12px rgba(91,3,255,0.3) !important;
                        transform: translateY(-1px) !important;
                    }
                    .king-addons-ai-translator-btn:active {
                        transform: translateY(0) !important;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
                    }
                    .king-addons-ai-translator-btn img {
                        width: 16px !important;
                        height: 16px !important;
                        flex-shrink: 0 !important;
                    }
                    .king-addons-ai-translator-btn span {
                        white-space: nowrap !important;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                    }
                    
                    /* Location-specific styles */
                    
                    /* In panel header */
                    .king-addons-translator-location-panel-header {
                        position: absolute !important;
                        top: 50% !important;
                        right: 16px !important;
                        transform: translateY(-50%) !important;
                        margin: 0 !important;
                        z-index: 1000 !important;
                    }
                    .king-addons-translator-location-panel-header:hover {
                        transform: translateY(-50%) translateY(-1px) !important;
                    }
                    
                    /* In header within panel */
                    .king-addons-translator-location-header-in-panel {
                        margin-left: auto !important;
                        margin-right: 8px !important;
                    }
                    
                    /* At top of panel */
                    .king-addons-translator-location-panel-top {
                        width: calc(100% - 16px) !important;
                        margin: 8px !important;
                        justify-content: center !important;
                    }
                    
                    /* In general elementor panel */
                    .king-addons-translator-location-elementor-panel {
                        margin: 8px !important;
                        align-self: flex-end !important;
                    }
                    
                    /* Toolbar button group integration styles */
                    .king-addons-translator-location-left-group,
                    .king-addons-translator-location-toolbar-stack,
                    .king-addons-translator-location-toolbar,
                    .king-addons-translator-location-grid-stack {
                        /* Material UI button styling is handled in the HTML structure */
                        display: inline-flex !important;
                    }
                    
                    /* Additional spacing for toolbar button */
                    .king-addons-translator-location-left-group .king-addons-ai-translator-btn,
                    .king-addons-translator-location-toolbar-stack .king-addons-ai-translator-btn,
                    .king-addons-translator-location-grid-stack .king-addons-ai-translator-btn {
                        margin-left: 8px !important;
                    }
                    
                    /* Compact popup styles */
                    .king-addons-translator-popup.compact .king-addons-translator-progress-text {
                        font-size: 14px;
                        margin-bottom: 8px;
                    }
                    
                    .king-addons-translator-popup.compact .king-addons-translator-current-element {
                        font-size: 12px;
                        margin-top: 8px;
                        color: #666;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }
                    
                    .king-addons-translator-popup.compact .king-addons-translator-stats {
                        margin: 12px 0;
                    }
                    
                    .king-addons-translator-popup.compact .king-addons-translator-stat {
                        margin: 0 8px;
                    }
                    
                    .king-addons-translator-popup.compact .king-addons-translator-stat-number {
                        font-size: 18px;
                    }
                    
                    .king-addons-translator-popup.compact .king-addons-translator-stat-label {
                        font-size: 11px;
                    }

                    /* Compact mode adjustments for custom fields */
                    .king-addons-translator-popup.compact .king-addons-prompt-examples {
                        padding: 6px;
                        margin-top: 4px;
                    }
                    
                    .king-addons-translator-popup.compact .king-addons-prompt-examples small {
                        font-size: 10px;
                    }
                    
                    .king-addons-translator-popup.compact .king-addons-pro-info {
                        font-size: 11px;
                        margin-top: 8px;
                        padding: 6px 8px;
                        background: #f8f9fa;
                        border-radius: 4px;
                        border-left: 3px solid #5B03FF;
                    }

                    /* Element highlighting styles moved to preview iframe */

                    /* Ensure button appears properly in all panel locations */
                    #elementor-panel .king-addons-ai-translator-btn,
                    .elementor-panel .king-addons-ai-translator-btn {
                        max-width: 200px !important;
                        overflow: hidden !important;
                    }
                    
                    /* Responsive behavior */
                    @media (max-width: 600px) {
                        /* Hide text in panel buttons on small screens */
                        .king-addons-ai-translator-btn span {
                            display: none !important;
                        }
                        .king-addons-ai-translator-btn {
                            padding: 8px !important;
                            min-width: 32px !important;
                        }
                    }

                    /* Popup Overlay */
                    .king-addons-translator-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(0,0,0,0.5);
                        z-index: 999999;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: opacity 0.3s ease;
                    }
                    
                    .king-addons-translator-overlay.hiding {
                        opacity: 0;
                        pointer-events: none;
                    }

                    /* Popup Container */
                    .king-addons-translator-popup {
                        background: white;
                        padding: 24px;
                        border-radius: 8px;
                        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                        width: 90%;
                        max-width: 500px;
                        max-height: 80vh;
                        overflow-y: auto;
                        transition: all 0.3s ease;
                        transform: scale(1);
                    }
                    
                    /* Compact popup for top-right positioning */
                    .king-addons-translator-popup.compact {
                        position: fixed;
                        top: 80px;
                        right: 20px;
                        width: 350px;
                        max-width: 350px;
                        padding: 16px;
                        z-index: 999999;
                        max-height: 400px;
                        transform: scale(1);
                        box-shadow: 0 8px 32px rgba(0,0,0,0.4);
                    }
                    
                    /* Compact popup header */
                    .king-addons-translator-popup.compact h3 {
                        font-size: 16px;
                        margin: 0 0 12px 0;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }
                    
                    /* Close button for compact popup */
                    .king-addons-translator-close-btn {
                        background: none;
                        border: none;
                        font-size: 18px;
                        cursor: pointer;
                        color: #999;
                        width: 24px;
                        height: 24px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 3px;
                    }
                    
                    .king-addons-translator-close-btn:hover {
                        background: #f0f0f0;
                        color: #333;
                    }
                    
                    /* Animation states */
                    .king-addons-translator-popup.moving {
                        transition: all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                    }
                    
                    /* Notification banner animation */
                    @keyframes slideDown {
                        0% {
                            transform: translateY(-100%);
                            opacity: 0;
                        }
                        100% {
                            transform: translateY(0);
                            opacity: 1;
                        }
                    }
                    
                    /* Pulse animation for success numbers */
                    @keyframes pulse {
                        0% {
                            transform: scale(1);
                            opacity: 1;
                        }
                        50% {
                            transform: scale(1.1);
                            opacity: 0.8;
                        }
                        100% {
                            transform: scale(1);
                            opacity: 1;
                        }
                    }

                    .king-addons-translator-popup h3 {
                        margin: 0 0 20px 0;
                        font-size: 18px;
                        color: #23282d;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                    }

                    .king-addons-translator-form {
                        display: flex;
                        flex-direction: column;
                        gap: 16px;
                    }

                    .king-addons-translator-field {
                        display: flex;
                        flex-direction: column;
                        gap: 6px;
                    }

                    .king-addons-translator-field label {
                        font-weight: 500;
                        color: #555;
                        font-size: 14px;
                    }

                    .king-addons-translator-field select {
                        padding: 8px 12px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        font-size: 14px;
                        height: auto;
                    }

                    .king-addons-translator-field select:focus {
                        border-color: #5B03FF;
                        box-shadow: 0 0 0 1px rgba(91,3,255,0.3);
                        outline: none;
                    }

                    .king-addons-translator-field input[type="text"] {
                        padding: 8px 12px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        font-size: 14px;
                        margin-top: 6px;
                        transition: border-color 0.3s ease, box-shadow 0.3s ease;
                    }

                    .king-addons-translator-field input[type="text"]:focus {
                        border-color: #5B03FF;
                        box-shadow: 0 0 0 1px rgba(91,3,255,0.3);
                        outline: none;
                    }

                    .king-addons-custom-language-field {
                        margin-top: 8px;
                        display: none;
                        animation: slideDown 0.3s ease-out;
                    }

                    .king-addons-custom-language-field.show {
                        display: block;
                    }

                    .king-addons-custom-language-field input {
                        width: 100%;
                        box-sizing: border-box;
                    }

                    .king-addons-custom-language-field label {
                        font-size: 13px;
                        color: #666;
                        margin-bottom: 4px;
                        display: block;
                    }

                    .king-addons-pro-badge {
                        background: linear-gradient(135deg, #FFD700, #FFA500);
                        color: #333;
                        font-size: 10px;
                        font-weight: bold;
                        padding: 2px 6px;
                        border-radius: 3px;
                        margin-left: 6px;
                        vertical-align: middle;
                    }
                    
                    /* Style for disabled custom option when not premium */
                    .king-addons-translator-field select option[value="custom"]:disabled {
                        color: #999;
                        background-color: #f5f5f5;
                    }
                    
                    /* Enhanced styling for custom language fields */
                    .king-addons-custom-language-field.show input:focus {
                        border-color: #5B03FF;
                        box-shadow: 0 0 0 2px rgba(91,3,255,0.1);
                    }
                    
                    /* Info text for premium features */
                    .king-addons-pro-info {
                        font-size: 13px;
                        color: #666;
                        margin-top: 4px;
                        font-style: italic;
                        line-height: 1.4;
                    }

                    .king-addons-pro-info a {
                        color: #5B03FF;
                        text-decoration: none;
                        font-weight: 500;
                    }

                    .king-addons-pro-info a:hover {
                        color: #4f00e6;
                        text-decoration: underline;
                    }

                    /* Prompt examples styling */
                    .king-addons-prompt-examples {
                        margin-top: 6px;
                        padding: 8px;
                        background: #f8f9fa;
                        border-left: 3px solid #5B03FF;
                        border-radius: 0 4px 4px 0;
                    }

                    .king-addons-prompt-examples small {
                        color: #666;
                        font-size: 11px;
                        line-height: 1.4;
                        display: block;
                    }

                    @keyframes slideDown {
                        from {
                            opacity: 0;
                            max-height: 0;
                            transform: translateY(-10px);
                        }
                        to {
                            opacity: 1;
                            max-height: 100px;
                            transform: translateY(0);
                        }
                    }

                    /* Loading spinner animation */
                    @keyframes rotate {
                        from {
                            transform: rotate(0deg);
                        }
                        to {
                            transform: rotate(360deg);
                        }
                    }

                    /* Error popup specific styles */
                    .king-addons-translator-popup .king-addons-error-icon {
                        width: 60px;
                        height: 60px;
                        background: #f44336;
                        border-radius: 50%;
                        margin: 0 auto 16px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        animation: errorPulse 2s ease-in-out infinite;
                    }

                    @keyframes errorPulse {
                        0%, 100% {
                            transform: scale(1);
                            box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.4);
                        }
                        50% {
                            transform: scale(1.05);
                            box-shadow: 0 0 0 8px rgba(244, 67, 54, 0.1);
                        }
                    }

                    .king-addons-translator-actions {
                        display: flex;
                        gap: 12px;
                        margin-top: 8px;
                    }

                    .king-addons-translator-btn-primary {
                        background: linear-gradient(135deg, #E1CBFF, #5B03FF);
                        border: none;
                        color: white;
                        padding: 10px 20px;
                        border-radius: 4px;
                        font-size: 14px;
                        font-weight: 500;
                        cursor: pointer;
                        flex: 1;
                        transition: all 0.3s ease;
                    }

                    .king-addons-translator-btn-primary:hover {
                        box-shadow: 0 0 12px rgba(91,3,255,0.5);
                        color: #fff;
                    }

                    .king-addons-translator-btn-primary:disabled {
                        background: #ccc;
                        cursor: not-allowed;
                        box-shadow: none;
                    }

                    .king-addons-translator-btn-secondary {
                        background: #f1f1f1;
                        border: 1px solid #ddd;
                        color: #555;
                        padding: 10px 20px;
                        border-radius: 4px;
                        font-size: 14px;
                        cursor: pointer;
                        flex: 1;
                        transition: all 0.3s ease;
                    }

                    .king-addons-translator-btn-secondary:hover {
                        background: #e8e8e8;
                    }

                    /* Progress Styles */
                    .king-addons-translator-progress {
                        margin-top: 16px;
                        padding: 16px;
                        background: #f8f9fa;
                        border-radius: 6px;
                        border-left: 4px solid #5B03FF;
                    }

                    .king-addons-translator-progress-text {
                        font-size: 14px;
                        color: #555;
                        margin-bottom: 8px;
                    }

                    .king-addons-translator-progress-bar {
                        width: 100%;
                        height: 8px;
                        background: #e0e0e0;
                        border-radius: 4px;
                        overflow: hidden;
                        margin-bottom: 8px;
                    }

                    .king-addons-translator-progress-fill {
                        height: 100%;
                        background: linear-gradient(90deg, #5B03FF, #E1CBFF);
                        width: 0%;
                        transition: width 0.3s ease;
                    }

                    .king-addons-translator-current-element {
                        font-size: 12px;
                        color: #777;
                        font-style: italic;
                    }

                    /* Stats Styles */
                    .king-addons-translator-stats {
                        margin-top: 16px;
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                        gap: 12px;
                    }

                    .king-addons-translator-stat {
                        text-align: center;
                        padding: 12px;
                        background: #f8f9fa;
                        border-radius: 6px;
                    }

                    .king-addons-translator-stat-number {
                        font-size: 20px;
                        font-weight: bold;
                        color: #5B03FF;
                    }

                    .king-addons-translator-stat-label {
                        font-size: 12px;
                        color: #777;
                        margin-top: 4px;
                    }

                    /* Element animations handled in preview iframe */
                </style>
            `;
            $('head').append(styles);
        }
    }

    /**
     * Add translator button to Elementor panel
     */
    function addTranslatorButton() {
        // Check if button already exists
        if (document.querySelector('.king-addons-ai-translator-btn')) {
            console.log('AI Translator button already exists');
            return;
        }

        console.log('Attempting to add AI Translator button...');
        
        // Strategy 1: Try to find the left button group in the top toolbar (after initial buttons)
        var $leftButtonGroup = $('#elementor-editor-wrapper-v2 .MuiStack-root.eui-1g5sxhh:first');
        if ($leftButtonGroup.length) {
            console.log('Found left button group in top toolbar, adding button');
            return addButtonToElement($leftButtonGroup, 'left-group');
        }
        
        // Strategy 2: Try to find the first stack group in the toolbar
        var $toolbarStack = $('#elementor-editor-wrapper-v2 .MuiStack-root');
        if ($toolbarStack.length) {
            console.log('Found toolbar stack, adding button to first stack');
            return addButtonToElement($toolbarStack.first(), 'toolbar-stack');
        }
        
        // Strategy 2.5: Try to find grid container with stacks
        var $gridContainer = $('#elementor-editor-wrapper-v2 .MuiGrid-container:first');
        if ($gridContainer.length) {
            var $firstStack = $gridContainer.find('.MuiStack-root:first');
            if ($firstStack.length) {
                console.log('Found first stack in grid container, adding button');
                return addButtonToElement($firstStack, 'grid-stack');
            }
        }
        
        // Strategy 3: Try to find the toolbar itself
        var $toolbar = $('#elementor-editor-wrapper-v2 .MuiToolbar-root');
        if ($toolbar.length) {
            console.log('Found toolbar, adding button');
            return addButtonToElement($toolbar, 'toolbar');
        }
        
        // Strategy 4: Try to find the Elementor panel header (fallback)
        var $panelHeader = $('#elementor-panel-header');
        if ($panelHeader.length) {
            console.log('Fallback: Found #elementor-panel-header, adding button');
            return addButtonToElement($panelHeader, 'panel-header');
        }
        
        // Strategy 5: Try to find the main panel (further fallback)
        var $panel = $('#elementor-panel');
        if ($panel.length) {
            console.log('Fallback: Adding button to panel');
            return addButtonToElement($panel, 'panel-fallback');
        }
        
        console.log('Could not find suitable location for AI Translator button');
        return false;
    }

    /**
     * Helper function to add button to specific element
     */
    function addButtonToElement($target, location) {
        // Try using the custom icon, fallback to the standard AI icon
        var iconUrl = KingAddonsAiField.plugin_url + 'includes/admin/img/ai.svg';
        var fallbackIconUrl = KingAddonsAiField.plugin_url + 'includes/admin/img/ai.svg';
        
        var $translatorBtn;
        
        // Create button with appropriate styling based on location
        if (location === 'left-group' || location === 'toolbar-stack' || location === 'toolbar' || location === 'grid-stack') {
            // Material UI style button for toolbar with text and custom icon
            $translatorBtn = $('<span class="MuiBox-root eui-0">' +
                '<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-textInherit MuiButton-sizeSmall MuiButton-textSizeSmall MuiButton-colorInherit king-addons-ai-translator-btn eui-17yw4pm" ' +
                'tabindex="0" type="button" aria-label="AI Page Translator" title="AI Page Translator">' +
                '<span class="MuiButton-startIcon MuiButton-iconSizeSmall" style="margin-right: 4px;">' +
                '<img src="' + iconUrl + '" alt="AI" onerror="this.src=\'' + fallbackIconUrl + '\'" style="width: 20px; height: 20px;" />' +
                '</span>' +
                '<span class="MuiStack-root" style="color: white;">AI Page Translator</span>' +
                '</button>' +
                '</span>');
        } else {
            // Original button style for panel locations
            $translatorBtn = $('<button class="king-addons-ai-translator-btn" title="AI Page Translator">' +
                '<img src="' + iconUrl + '" alt="AI Page Translator" onerror="this.src=\'' + fallbackIconUrl + '\'"/>' +
                '<span>AI Page Translator</span>' +
                '</button>');
        }
        
        // Add location-specific class for different styling if needed
        $translatorBtn.addClass('king-addons-translator-location-' + location);
        
        // Add to the target element based on location
        if (location === 'panel-top' || location === 'panel-fallback') {
            $target.prepend($translatorBtn);
        } else if (location === 'left-group' || location === 'toolbar-stack' || location === 'grid-stack') {
            // Add after existing buttons in the left group
            $target.append($translatorBtn);
        } else {
            $target.append($translatorBtn);
        }
        
        // Bind click event (works for both button structures)
        $translatorBtn.find('button').length ? 
            $translatorBtn.find('button').on('click', handleButtonClick) :
            $translatorBtn.on('click', handleButtonClick);
        
        function handleButtonClick(e) {
            e.preventDefault();
            // Check if button is disabled or translation is in progress
            if (translationState.isTranslating || $(e.currentTarget).prop('disabled')) {
                return;
            }
            showTranslatorPopup();
        }
        
        // Add debug info to button
        var $btn = $translatorBtn.find('button').length ? $translatorBtn.find('button') : $translatorBtn;
        $btn.attr('data-location', location);
        $btn.attr('data-target', $target.prop('tagName') + ($target.attr('id') ? '#' + $target.attr('id') : '') + ($target.attr('class') ? '.' + $target.attr('class').split(' ').join('.') : ''));
        
        console.log('AI Translator button added to:', location, 'Target:', $target);
        return true;
    }

    /**
     * Show the translator popup
     */
    function showTranslatorPopup() {
        // Check API key first
        checkApiKeyAndShowPopup();
    }

    /**
     * Check API key before showing popup
     */
    function checkApiKeyAndShowPopup() {
        // Show loading state briefly
        var $loadingOverlay = showLoadingOverlay();
        
        $.post(KingAddonsAiField.ajax_url, {
            action: 'king_addons_ai_check_tokens',
            nonce: KingAddonsAiField.generate_nonce
        }, function(response) {
            $loadingOverlay.remove();
            
            if (!response.success) {
                if (response.data && response.data.message) {
                    var errorMessage = response.data.message;
                    
                    // Check for token limit errors first
                    if (errorMessage.toLowerCase().includes('token limit') || 
                        errorMessage.toLowerCase().includes('daily limit') ||
                        errorMessage.toLowerCase().includes('limit reached') ||
                        errorMessage.toLowerCase().includes('quota exceeded') ||
                        errorMessage.toLowerCase().includes('rate limit')) {
                        
                        showTokenLimitError(errorMessage);
                        return;
                    }
                    
                    showApiKeyError('API Error', errorMessage);
                } else {
                    showApiKeyError('Connection Issue', 'Unable to connect right now. Please check your internet connection and try again.');
                }
                return;
            }
            
            if (!response.data.api_key_valid) {
                var errorMessage = response.data.error_message || 'API key is missing or invalid';
                
                // Check for token limit errors first  
                if (errorMessage.toLowerCase().includes('token limit') || 
                    errorMessage.toLowerCase().includes('daily limit') ||
                    errorMessage.toLowerCase().includes('limit reached') ||
                    errorMessage.toLowerCase().includes('quota exceeded') ||
                    errorMessage.toLowerCase().includes('rate limit') ||
                    errorMessage.toLowerCase().includes('too many requests')) {
                    
                    showTokenLimitError(errorMessage);
                    return;
                }
                
                showApiKeyError('API Key Required', errorMessage);
                return;
            }
            
            createAndShowPopup();
        }).fail(function(xhr) {
            $loadingOverlay.remove();
            
            if (xhr.status === 0) {
                showApiKeyError('Connection Issue', 'Network connection failed. Please check your internet connection and try again.');
            } else {
                showApiKeyError('Temporary Issue', 'Server is temporarily unavailable. Please try again in a few minutes.');
            }
        });
    }

    /**
     * Show loading overlay
     */
    function showLoadingOverlay() {
        var $overlay = $('<div class="king-addons-translator-overlay"></div>');
        var $popup = $('<div class="king-addons-translator-popup" style="text-align: center; padding: 40px;"></div>');
        
        var loadingHtml = `
            <div style="margin-bottom: 16px;">
                <img src="${KingAddonsAiField.plugin_url}includes/admin/img/ai.svg" style="width:40px;height:40px;filter: invert(1); animation: rotate 1s linear infinite;"/>
            </div>
            <div style="font-size: 16px; color: #333; margin-bottom: 8px;">🔍 Verifying Setup...</div>
            <div style="font-size: 12px; color: #666;">Just checking that everything is ready for translation</div>
        `;
        
        $popup.html(loadingHtml);
        $overlay.append($popup);
        $('body').append($overlay);
        
        return $overlay;
    }

    /**
     * Show API key error with detailed information
     */
    function showApiKeyError(title, message) {
        // Aggressively remove any existing popups/overlays
        $('.king-addons-translator-overlay').remove();
        $('.king-addons-translator-popup').remove();
        
        // Wait a bit to ensure cleanup is complete
        setTimeout(function() {
            showApiKeyErrorDelayed(title, message);
        }, 100);
    }
    
    function showApiKeyErrorDelayed(title, message) {
        var settingsUrl = window.KingAddonsAiField && window.KingAddonsAiField.settings_url
            ? window.KingAddonsAiField.settings_url
            : '/wp-admin/admin.php?page=king-addons-ai-settings';
        
        var $overlay = $('<div class="king-addons-translator-overlay"></div>');
        var $popup = $('<div class="king-addons-translator-popup"></div>');
        
        var errorHtml = `
            <div style="text-align: center; margin-bottom: 32px;">
                <h3 style="margin: 0 0 16px 0; color: #2d3748; font-size: 20px; text-align: center;justify-content: center;">AI Translator Setup Required</h3>
                <p style="color: #718096; margin: 0; font-size: 14px;">This is completely safe and takes only 2 minutes!</p>
            </div>
            
            <div style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <h4 style="color: #2d3748; margin: 0 0 12px 0; font-size: 16px;">📋 What you need to do:</h4>
                
                <div style="margin-bottom: 12px; display: flex; align-items: center;">
                    <span style="background: #48bb78; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 12px; font-weight: bold;max-width: 24px;flex-basis: 46px;">1</span>
                    <span style="color: #4a5568;">Get an API key from <a href="https://platform.openai.com/api-keys" target="_blank" style="color: #5B03FF; text-decoration: none;line-height: 1.4;">OpenAI Platform</a> and top up your OpenAI account balance by at least $5</span>
                </div>
                
                <div style="margin-bottom: 12px; display: flex; align-items: center;">
                    <span style="background: #48bb78; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 12px; font-weight: bold;">2</span>
                    <span style="color: #4a5568;">Paste it in AI Settings</span>
                </div>
                
                <div style="margin-bottom: 12px; display: flex; align-items: center;">
                    <span style="background: #48bb78; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 12px; font-weight: bold;">3</span>
                    <span style="color: #4a5568;">Done! Translate as much as you want 🎉</span>
                </div>
            </div>
            
            <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; margin-bottom: 8px;">
                    <span style="color: #495057; margin-right: 8px; font-size: 16px;">💰</span>
                    <strong style="color: #495057;">How much does it cost?</strong>
                </div>
                <p style="color: #6c757d; margin: 0; font-size: 14px; line-height: 1.4;">
                    Translation costs pennies (about $0.01 per full page).
                </p>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button class="king-addons-translator-btn-secondary" id="king-addons-error-close" style="flex: 1;">
                    Not now
                </button>
                <a href="${settingsUrl}" class="king-addons-translator-btn-primary" style="flex: 1; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                    Set up now
                </a>
            </div>
        `;
        
        $popup.html(errorHtml);
        $overlay.append($popup);
        $('body').append($overlay);
        
        // Bind close event
        $('#king-addons-error-close').on('click', function() {
            $overlay.remove();
        });
        
        // Close on overlay click
        $overlay.on('click', function(e) {
            if (e.target === $overlay[0]) {
                $overlay.remove();
            }
        });
    }

    /**
     * Show token limit error popup
     */
    function showTokenLimitError(message) {
        // Remove any existing popups first
        $('.king-addons-translator-overlay').remove();
        $('.king-addons-translator-popup').remove();
        
        // Wait a bit to ensure cleanup is complete
        setTimeout(function() {
            showTokenLimitErrorDelayed(message);
        }, 100);
    }
    
    function showTokenLimitErrorDelayed(message) {
        var settingsUrl = window.KingAddonsAiField && window.KingAddonsAiField.settings_url
            ? window.KingAddonsAiField.settings_url
            : '/wp-admin/admin.php?page=king-addons-ai-settings';
        
        var $overlay = $('<div class="king-addons-translator-overlay"></div>');
        var $popup = $('<div class="king-addons-translator-popup"></div>');
        
        var errorHtml = `
            <div style="text-align: center; margin-bottom: 32px;">
                <h3 style="margin: 0 0 16px 0; color: #2d3748; font-size: 20px; text-align: center;justify-content: center;">🛡️ Daily Limit Reached</h3>
                <p style="color: #718096; margin: 0; font-size: 14px;">Your safety limit is protecting your account!</p>
            </div>
            
            <div style="background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%); border: 1px solid #c3e6cb; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <h4 style="color: #155724; margin: 0 0 12px 0; font-size: 16px;">🎯 What's happening?</h4>
                <p style="color: #155724; margin: 0 0 16px 0; font-size: 14px; line-height: 1.5;">
                    You have a <strong>"Daily Token Limit"</strong> setting that prevents accidental overspending. 
                    This is a <em>good thing</em> - it's working as intended to protect your account!
                </p>
                
                <h4 style="color: #155724; margin: 0 0 12px 0; font-size: 16px;">⚙️ Easy solutions:</h4>
                
                <div style="margin-bottom: 12px; display: flex; align-items: center;">
                    <span style="background: #28a745; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 12px; font-weight: bold;">1</span>
                    <span style="color: #155724;"><strong>Increase the "Daily Token Limit"</strong> in AI Settings (recommended)</span>
                </div>
                
                <div style="margin-bottom: 12px; display: flex; align-items: center;">
                    <span style="background: #28a745; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 12px; font-weight: bold;">2</span>
                    <span style="color: #155724;">Or wait until tomorrow (limit automatically resets)</span>
                </div>
            </div>
            
            <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; margin-bottom: 8px;">
                    <span style="color: #495057; margin-right: 8px; font-size: 16px;">💡</span>
                    <strong style="color: #495057;">Quick tip</strong>
                </div>
                <p style="color: #6c757d; margin: 0; font-size: 14px; line-height: 1.4;">
                    Just go to <strong>AI Settings → Daily Token Limit</strong> and set a higher number. 
                    For regular use, try setting it to <strong>50,000 or 100,000 tokens</strong>.
                </p>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button class="king-addons-translator-btn-secondary" id="king-addons-limit-close" style="flex: 1;">
                    I understand
                </button>
                <a href="${settingsUrl}" class="king-addons-translator-btn-primary" style="flex: 1; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                    Open AI Settings
                </a>
            </div>
        `;
        
        $popup.html(errorHtml);
        $overlay.append($popup);
        $('body').append($overlay);
        
        // Bind close event
        $('#king-addons-limit-close').on('click', function() {
            $overlay.remove();
        });
        
        // Close on overlay click
        $overlay.on('click', function(e) {
            if (e.target === $overlay[0]) {
                $overlay.remove();
            }
        });
    }

    /**
     * Disable/enable the AI Translator button
     */
    function toggleTranslatorButton(disabled) {
        var $button = $('.king-addons-ai-translator-btn');
        
        if (disabled) {
            $button.prop('disabled', true);
            $button.css('opacity', '0.5');
            $button.css('cursor', 'not-allowed');
        } else {
            $button.prop('disabled', false);
            $button.css('opacity', '1');
            $button.css('cursor', 'pointer');
        }
    }

    /**
     * Stop the translation process
     */
    function stopTranslationProcess() {
        // Prevent multiple calls
        if (translationState.isCancelled) {
            console.log('⚠️ Translation already cancelled, ignoring duplicate stop request');
            return;
        }
        
        console.log('🛑 Stopping translation process...');
        translationState.isCancelled = true;
        translationState.isTranslating = false;
        
        // Cancel all active AJAX requests
        if (translationState.currentRequests.length > 0) {
            console.log('Cancelling ' + translationState.currentRequests.length + ' active AJAX requests');
            translationState.currentRequests.forEach(function(request) {
                if (request && request.abort) {
                    request.abort();
                }
            });
            translationState.currentRequests = [];
        }
        
        // Remove any highlighting from current element
        if (translationState.currentElement) {
            highlightElementInPreview(translationState.currentElement.elementId, false);
        }
        
        // Remove any existing popups/overlays
        $('.king-addons-translator-overlay').remove();
        
        // Show cancellation message
        console.log('Translation process cancelled by user');
        
        // Re-enable the button
        toggleTranslatorButton(false);
    }

    /**
     * Animate popup to top-right corner
     */
    function movePopupToCorner($popup, $overlay) {
        return new Promise(function(resolve) {
            // Add moving class for smooth animation
            $popup.addClass('moving');
            
            // Hide overlay with fade
            $overlay.addClass('hiding');
            
            // Calculate current position and target position
            var currentRect = $popup[0].getBoundingClientRect();
            var targetTop = 80;
            var targetRight = 20;
            var targetLeft = window.innerWidth - 350 - 20;
            
            // Move popup from overlay to body with current position
            $popup.css({
                'position': 'fixed',
                'top': currentRect.top + 'px',
                'left': currentRect.left + 'px',
                'width': currentRect.width + 'px',
                'margin': '0',
                'transform': 'none',
                'z-index': 999999
            });
            
            // Append popup to body (remove from overlay)
            $('body').append($popup);
            
            // Wait for overlay to fade, then animate popup
            setTimeout(function() {
                // Force reflow
                $popup[0].offsetHeight;
                
                // Animate to final position
                $popup.css({
                    'top': targetTop + 'px',
                    'left': targetLeft + 'px',
                    'width': '350px',
                    'padding': '16px'
                });
                
                // Add compact class after animation and remove overlay
                setTimeout(function() {
                    $popup.removeClass('moving').addClass('compact');
                    $overlay.remove(); // Remove overlay completely
                    resolve();
                }, 500);
                
            }, 300);
        });
    }

    /**
     * Create and show the main popup
     */
    function createAndShowPopup() {
        var $overlay = $('<div class="king-addons-translator-overlay"></div>');
        var $popup = $('<div class="king-addons-translator-popup"></div>');
        
        var isPro = isPremiumActive();
        var customOptionHtml = isPro ? 
            '<option value="custom">Custom language or prompt (PRO)</option>' : 
            '<option value="custom" disabled>Custom language or prompt (PRO)</option>';
        
        var upgradeUrl = 'https://kingaddons.com/pricing/?utm_source=ai-translator&utm_medium=plugin&utm_campaign=custom-prompts';
        var proInfoHtml = isPro ? 
            '<div class="king-addons-pro-info" style="color: #4CAF50; border-left-color: #4CAF50;">✅ PRO Active: Use custom languages and translation prompts!</div>' :
            '<div class="king-addons-pro-info">💎 <a href="' + upgradeUrl + '" target="_blank" style="color: #5B03FF; text-decoration: none;">Upgrade to PRO</a> to use custom languages, regional dialects and custom translation prompts (formal tone, technical style, etc.)!</div>';
        
        var popupContent = `
            <h3>
                <img src="${KingAddonsAiField.plugin_url}includes/admin/img/ai.svg" style="width:20px;height:20px;filter: invert(1);"/>
                AI Page Translator
            </h3>
            <div style="font-size: 12px; color: #666; margin-bottom: 16px; line-height: 1.4;">
                Translate to any language or transform text style (formal, casual, technical, etc.)
            </div>
            <div class="king-addons-translator-form">
                <div class="king-addons-translator-field">
                    <label>From Language</label>
                    <select id="king-addons-from-lang">
                        <option value="auto">Auto-detect</option>
                        ${Object.keys(languages).map(code => 
                            `<option value="${code}">${languages[code]}</option>`
                        ).join('')}
                        ${customOptionHtml}
                    </select>
                    <div class="king-addons-custom-language-field" id="king-addons-custom-from-field">
                        <label>Custom language or translation prompt</label>
                        <input type="text" id="king-addons-custom-from-lang" placeholder="e.g., Klingon, Old English, formal business tone, medical terminology..." />
                        <div class="king-addons-prompt-examples">
                            <small>Examples: "Klingon", "Shakespeare English", "formal business style", "casual conversational tone"</small>
                        </div>
                    </div>
                </div>
                <div class="king-addons-translator-field">
                    <label>To Language</label>
                    <select id="king-addons-to-lang">
                        ${Object.keys(languages).map(code => 
                            `<option value="${code}" ${code === 'en' ? 'selected' : ''}>${languages[code]}</option>`
                        ).join('')}
                        ${customOptionHtml}
                    </select>
                    <div class="king-addons-custom-language-field" id="king-addons-custom-to-field">
                        <label>Custom language or translation style</label>
                        <input type="text" id="king-addons-custom-to-lang" placeholder="e.g., Dothraki, Academic writing, pirate speak, baby talk..." />
                        <div class="king-addons-prompt-examples">
                            <small>Examples: "Dothraki", "academic paper style", "pirate language", "simplified for children"</small>
                        </div>
                    </div>
                </div>
                ${proInfoHtml}
                <div class="king-addons-translator-actions">
                    <button class="king-addons-translator-btn-secondary" id="king-addons-cancel-translation">
                        Cancel
                    </button>
                    <button class="king-addons-translator-btn-primary" id="king-addons-start-translation">
                        Start Translation
                    </button>
                </div>
            </div>
        `;
        
        $popup.html(popupContent);
        $overlay.append($popup);
        $('body').append($overlay);
        
        // Store references globally
        window.currentTranslatorPopup = $popup;
        window.currentTranslatorOverlay = $overlay;
        
        // Bind events for language selection
        bindLanguageSelectionEvents();
        
        // Bind events
        $('#king-addons-cancel-translation').on('click', function() {
            if (!translationState.isTranslating) {
                $overlay.remove();
            }
        });
        
        $('#king-addons-start-translation').on('click', function() {
            var result = getSelectedLanguages();
            
            if (!result.valid) {
                alert(result.error);
                return;
            }
            
            // Double-check API key before starting translation
            var $button = $(this);
            $button.prop('disabled', true).text('🔍 Verifying...');
            
            $.post(KingAddonsAiField.ajax_url, {
                action: 'king_addons_ai_check_tokens',
                nonce: KingAddonsAiField.generate_nonce
            }, function(response) {
                $button.prop('disabled', false).text('Start Translation');
                
                if (!response.success || !response.data.api_key_valid) {
                    var errorMessage = 'API key verification failed. Please check your API key in settings.';
                    if (response.data && response.data.error_message) {
                        errorMessage = response.data.error_message;
                    }
                    
                    // Check for token limit errors first
                    if (errorMessage.toLowerCase().includes('token limit') || 
                        errorMessage.toLowerCase().includes('daily limit') ||
                        errorMessage.toLowerCase().includes('limit reached') ||
                        errorMessage.toLowerCase().includes('quota exceeded') ||
                        errorMessage.toLowerCase().includes('rate limit') ||
                        errorMessage.toLowerCase().includes('too many requests')) {
                        
                        // Show token limit error popup
                        showTokenLimitError(errorMessage);
                        return;
                    }
                    
                    // Show error popup (will handle cleanup automatically)
                    showApiKeyError('Setup Required', errorMessage);
                    return;
                }
                
                // API key is valid, proceed with translation
                startTranslation(result.fromLang, result.toLang, $popup, $overlay);
                
            }).fail(function() {
                $button.prop('disabled', false).text('Start Translation');
                
                // Show error popup (will handle cleanup automatically)
                showApiKeyError('Connection Issue', 'Failed to connect right now. Please check your connection and try again.');
            });
        });
        
        // Close on overlay click only if not translating
        $overlay.on('click', function(e) {
            if (e.target === $overlay[0] && !translationState.isTranslating) {
                $overlay.remove();
            }
        });
    }

    /**
     * Bind events for language selection dropdowns
     */
    function bindLanguageSelectionEvents() {
        // Handle From Language selection
        $('#king-addons-from-lang').on('change', function() {
            var selectedValue = $(this).val();
            var $customField = $('#king-addons-custom-from-field');
            
            if (selectedValue === 'custom') {
                if (!isPremiumActive()) {
                    // Reset to previous value and show upgrade message
                    $(this).val('auto');
                    alert('Custom languages and translation prompts are a PRO feature. Please upgrade to King Addons PRO to use custom languages or translation styles.');
                    return;
                }
                $customField.addClass('show');
                $('#king-addons-custom-from-lang').focus();
            } else {
                $customField.removeClass('show');
            }
        });
        
        // Handle To Language selection
        $('#king-addons-to-lang').on('change', function() {
            var selectedValue = $(this).val();
            var $customField = $('#king-addons-custom-to-field');
            
            if (selectedValue === 'custom') {
                if (!isPremiumActive()) {
                    // Reset to previous value and show upgrade message
                    $(this).val('en');
                    alert('Custom languages and translation prompts are a PRO feature. Please upgrade to King Addons PRO to use custom languages or translation styles.');
                    return;
                }
                $customField.addClass('show');
                $('#king-addons-custom-to-lang').focus();
            } else {
                $customField.removeClass('show');
            }
        });
    }

    /**
     * Get selected languages with validation
     */
    function getSelectedLanguages() {
        var fromLang = $('#king-addons-from-lang').val();
        var toLang = $('#king-addons-to-lang').val();
        var customFromLang = $('#king-addons-custom-from-lang').val().trim();
        var customToLang = $('#king-addons-custom-to-lang').val().trim();
        
        // Handle custom from language
        if (fromLang === 'custom') {
            if (!customFromLang) {
                return {
                    valid: false,
                    error: 'Please enter a custom source language or translation prompt.'
                };
            }
            fromLang = customFromLang;
        }
        
        // Handle custom to language  
        if (toLang === 'custom') {
            if (!customToLang) {
                return {
                    valid: false,
                    error: 'Please enter a custom target language or translation style.'
                };
            }
            toLang = customToLang;
        }
        
        // Validate languages are different (except auto-detect)
        if (fromLang === toLang && fromLang !== 'auto') {
            return {
                valid: false,
                error: 'Source and target languages cannot be the same.'
            };
        }
        
        return {
            valid: true,
            fromLang: fromLang,
            toLang: toLang
        };
    }

    /**
     * Start the translation process
     */
    function startTranslation(fromLang, toLang, $popup, $overlay) {
        translationState.isTranslating = true;
        translationState.isCancelled = false; // Reset cancellation flag
        translationState.currentRequests = []; // Clear any previous requests
        translationState.fromLang = fromLang;
        translationState.toLang = toLang;
        translationState.translatedElements = 0;
        translationState.failedElements = 0;
        
        // Inject animation styles into preview iframe immediately
        injectPreviewStyles();
        
        // Disable the AI Translator button
        toggleTranslatorButton(true);
        
        // Get all translatable elements
        var elements = getTranslatableElements();
        translationState.totalElements = elements.length;
        
        if (elements.length === 0) {
            alert('No translatable text elements found on this page.');
            translationState.isTranslating = false;
            toggleTranslatorButton(false);
            return;
        }
        
        console.log('Starting translation of ' + elements.length + ' elements from ' + fromLang + ' to ' + toLang);
        
        // Update popup to show progress
        showProgressInPopup($popup);
        
        // Animate popup to corner and start translation
        movePopupToCorner($popup, $overlay).then(function() {
            // Start translating elements one by one
            translateElementsSequentially(elements, 0, $popup);
        });
    }

    /**
     * Get all translatable text elements
     */
    function getTranslatableElements() {
        var elements = [];
        // Get the main document container using Elementor 3.0+ API
        var documentContainer = elementor.documents.getCurrent().container;
        var elementorElements = [];
        
        // Use the new API to get children - for Elementor 3.0+
        if (documentContainer.children && typeof documentContainer.children.models !== 'undefined') {
            // Backbone collection - extract models
            elementorElements = documentContainer.children.models || [];
        } else if (documentContainer.elements && typeof documentContainer.elements.models !== 'undefined') {
            // Alternative property name in some Elementor versions
            elementorElements = documentContainer.elements.models || [];
        } else if (Array.isArray(documentContainer.children)) {
            // Fallback for older API
            elementorElements = documentContainer.children;
        } else {
            console.warn('🚨 Unable to find container children using any known API');
            elementorElements = [];
        }
        
        function processContainer(container) {
            var model = container.model;
            var elementType = model.get('elType');
            var widgetType = model.get('widgetType');
            
            // Process text-based widgets
            if (widgetType) {
                // First, check if this widget type should be skipped entirely
                var nonTextWidgets = [
                    'spacer', 'divider', 'html', 'shortcode', 'sidebar',
                    'menu-anchor', 'read-more', 'google_maps', 'paypal_button',
                    'stripe_button', 'facebook_button', 'facebook_page',
                    'video', 'audio', 'iframe', 'code', 'wp-widget',
                    'map', 'rating', 'progress', 'counter', 'countdown',
                    'social-icons', 'share-buttons', 'login', 'lottie',
                    'image'  // Image widget should be skipped
                ];
                
                if (nonTextWidgets.indexOf(widgetType) !== -1) {
                    console.log('⚪ Skipping non-text widget:', widgetType, '(blacklisted widget type)');
                    return; // Exit early for blacklisted widgets
                }
                
                var settings = model.get('settings').attributes;
                
                // Now check for text fields in remaining widgets
                var textFields = getTextFieldsForWidget(widgetType, settings, container);
                
                // If we found text fields, process the widget
                if (textFields.length > 0) {
                    console.log('✅ Processing widget:', widgetType, 'with', textFields.length, 'text fields');
                    elements.push({
                        container: container,
                        widgetType: widgetType,
                        textFields: textFields,
                        elementId: model.get('id')
                    });
                    return;
                } else {
                    console.log('⚪ No text fields found in widget:', widgetType);
                }
            }
            
            // Process child containers recursively using Elementor 3.0+ API
            if (container.children && container.children.length > 0) {
                // Check if children is a Backbone collection
                if (typeof container.children.models !== 'undefined') {
                    container.children.models.forEach(processContainer);
                } else if (Array.isArray(container.children)) {
                    container.children.forEach(processContainer);
                }
            }
        }
        
        elementorElements.forEach(processContainer);
        return elements;
    }

    /**
     * Get text fields for a specific widget type using Elementor control types
     */
    function getTextFieldsForWidget(widgetType, settings, container) {
        console.log('🔍 Analyzing widget:', widgetType);
        console.log('🔍 Widget settings keys:', Object.keys(settings));
        
        var textFields = [];
        
        // Try to get widget controls schema from Elementor
        var controls = getWidgetControls(widgetType, container);
        
        if (controls && Object.keys(controls).length > 0) {
            console.log('📋 Found widget controls schema with', Object.keys(controls).length, 'controls');
            
            // Look for text-based controls
            Object.keys(controls).forEach(function(controlName) {
                var control = controls[controlName];
                var controlType = control.type;
                var settingValue = settings[controlName];
                
                // Check if this is a text-based control type
                var textControlTypes = [
                    'text', 'textarea', 'wysiwyg', 'url', 'email', 
                    'password', 'search', 'tel', 'date', 'time', 
                    'datetime-local', 'month', 'week'
                ];
                
                if (textControlTypes.includes(controlType)) {
                    // Check if the field has a non-empty string value
                    if (settingValue && typeof settingValue === 'string' && settingValue.trim()) {
                        // Skip obviously non-translatable fields
                        var skipFields = [
                            '_element_id', '_css_classes', 'link', 'url', 'href', 
                            'custom_css', 'css_id', 'anchor', 'html_tag'
                        ];
                        
                        if (!skipFields.includes(controlName)) {
                            console.log('✅ Found text control:', controlName, 'Type:', controlType, 'Value:', settingValue.substring(0, 50) + '...');
                            
                            textFields.push({
                                field: controlName,
                                value: settingValue,
                                type: controlType === 'wysiwyg' ? 'wysiwyg' : 'text'
                            });
                        } else {
                            console.log('⚪ Skipping non-translatable field:', controlName, 'Type:', controlType);
                        }
                    }
                }
                
                // Also check for repeater controls
                if (controlType === 'repeater' && settingValue) {
                    console.log('🔍 Found repeater control:', controlName);
                    checkRepeaterFieldsByType(controlName, control, settingValue, textFields);
                }
            });
        } else {
            console.log('⚠️ No widget controls schema found, falling back to manual detection');
            
            // Fallback: Use the original method for widgets without accessible controls
            var commonTextFields = [
                'title', 'text', 'content', 'description', 'subtitle', 'button_text',
                'heading_title', 'heading_subtitle', 'testimonial_content', 'testimonial_name',
                'title_text', 'description_text', 'content_text', 'editor'
            ];
            
            commonTextFields.forEach(function(field) {
                if (settings[field] && typeof settings[field] === 'string' && settings[field].trim()) {
                    textFields.push({
                        field: field,
                        value: settings[field],
                        type: field === 'editor' ? 'wysiwyg' : 'text'
                    });
                }
            });
            
            // Check for repeater fields using the old method
            checkRepeaterFields(settings, textFields);
        }
        
        console.log('🔍 Final result for widget', widgetType + ':', textFields.length, 'text fields found');
        if (textFields.length > 0) {
            console.log('📝 All text fields:', textFields.map(f => f.field + ' = ' + f.value.substring(0, 30) + '...'));
        }
        
        return textFields;
    }
    
    /**
     * Get widget controls schema from Elementor
     */
    function getWidgetControls(widgetType, container) {
        try {
            // Method 1: Try to get controls from container model
            if (container && container.model && container.model.get) {
                var model = container.model;
                
                // Try to get controls from the model's widget config
                if (model.config && model.config.controls) {
                    console.log('📋 Found controls from model.config');
                    return model.config.controls;
                }
                
                // Try to get controls from the container settings
                if (container.settings && container.settings.controls) {
                    console.log('📋 Found controls from container.settings');
                    return container.settings.controls;
                }
            }
            
            // Method 2: Try to get controls from Elementor widgets registry
            if (window.elementor && elementor.widgets) {
                var widgetConfig = elementor.widgets.getWidgetType(widgetType);
                if (widgetConfig && widgetConfig.controls) {
                    console.log('📋 Found controls from widgets registry');
                    return widgetConfig.controls;
                }
            }
            
            // Method 3: Try to get controls from elements manager
            if (window.elementor && elementor.elementsManager) {
                var elementView = elementor.elementsManager.getElementView(container.model.get('id'));
                if (elementView && elementView.model && elementView.model.controls) {
                    console.log('📋 Found controls from element view');
                    return elementView.model.controls;
                }
            }
            
            console.log('⚠️ Could not find controls schema for widget:', widgetType);
            return null;
            
        } catch (error) {
            console.warn('⚠️ Error getting widget controls:', error);
            return null;
        }
    }
    
    /**
     * Check repeater fields using control type information
     */
    function checkRepeaterFieldsByType(repeaterName, repeaterControl, repeaterData, textFields) {
        try {
            console.log('🔍 Analyzing repeater:', repeaterName, 'with control:', repeaterControl);
            
            // Get the fields schema for this repeater
            var repeaterFields = repeaterControl.fields || repeaterControl.controls || {};
            
            // Find text-based fields in the repeater schema
            var textFieldNames = [];
            Object.keys(repeaterFields).forEach(function(fieldName) {
                var fieldControl = repeaterFields[fieldName];
                var textControlTypes = ['text', 'textarea', 'wysiwyg', 'url', 'email'];
                
                if (textControlTypes.includes(fieldControl.type)) {
                    textFieldNames.push(fieldName);
                }
            });
            
            console.log('🔍 Found text fields in repeater schema:', textFieldNames);
            
            if (textFieldNames.length === 0) {
                console.log('⚠️ No text fields found in repeater schema');
                return;
            }
            
            // Process repeater data (same as before)
            if (repeaterData && typeof repeaterData === 'object' && repeaterData.models) {
                // Backbone collection
                const models = repeaterData.models || [];
                for (let i = 0; i < models.length; i++) {
                    const model = models[i];
                    const modelData = model.attributes || model.toJSON();
                    
                    for (const fieldName of textFieldNames) {
                        if (modelData[fieldName] && typeof modelData[fieldName] === 'string' && modelData[fieldName].trim()) {
                            const fieldKey = `${repeaterName}[${i}][${fieldName}]`;
                            const fieldValue = modelData[fieldName];
                            
                            console.log(`✅ Found repeater text field: ${fieldKey} = ${fieldValue}`);
                            textFields.push({
                                field: fieldKey,
                                value: fieldValue,
                                type: 'text',
                                isRepeater: true,
                                repeaterKey: repeaterName,
                                repeaterIndex: i,
                                repeaterField: fieldName
                            });
                        }
                    }
                }
            } else if (Array.isArray(repeaterData)) {
                // Regular array
                for (let i = 0; i < repeaterData.length; i++) {
                    const item = repeaterData[i];
                    for (const fieldName of textFieldNames) {
                        if (item[fieldName] && typeof item[fieldName] === 'string' && item[fieldName].trim()) {
                            const fieldKey = `${repeaterName}[${i}][${fieldName}]`;
                            const fieldValue = item[fieldName];
                            
                            console.log(`✅ Found repeater text field: ${fieldKey} = ${fieldValue}`);
                            textFields.push({
                                field: fieldKey,
                                value: fieldValue,
                                type: 'text',
                                isRepeater: true,
                                repeaterKey: repeaterName,
                                repeaterIndex: i,
                                repeaterField: fieldName
                            });
                        }
                    }
                }
            }
            
        } catch (error) {
            console.warn('⚠️ Error processing repeater by type:', error);
            // Fallback to old method
            var repeaterConfig = {};
            repeaterConfig[repeaterName] = ['content', 'text', 'title', 'description'];
            checkRepeaterFields({[repeaterName]: repeaterData}, textFields);
        }
    }
    
    /**
     * Check for repeater fields in settings (fallback method)
     */
    function checkRepeaterFields(settings, textFields) {
        // King Addons specific repeater configurations
        const repeaterConfigs = {
            'kng_styled_txt_content_items': ['kng_styled_txt_content'],
            'kng_tabs_items': ['kng_tabs_title', 'kng_tabs_content'],
            'kng_accordion_items': ['kng_accordion_title', 'kng_accordion_content'],
            'kng_testimonials_items': ['kng_testimonials_content', 'kng_testimonials_name'],
            'kng_team_members': ['kng_team_name', 'kng_team_position', 'kng_team_description'],
            'kng_price_list_items': ['kng_price_title', 'kng_price_description'],
            'kng_business_hours_items': ['kng_business_day', 'kng_business_hours'],
            // Standard Elementor repeaters
            'tabs': ['tab_title', 'tab_content'],
            'icon_list': ['text'],
            'slides': ['heading', 'description', 'button_text'],
            'list_items': ['text'],
            'testimonials': ['testimonial_content', 'testimonial_name'],
            'items': ['item_title', 'item_description', 'item_content'],
            'price_list': ['price_title', 'price_description']
        };

        console.log('🔍 Starting fallback repeater field check for settings:', Object.keys(settings));

        for (const [repeaterKey, fieldNames] of Object.entries(repeaterConfigs)) {
            if (settings[repeaterKey]) {
                console.log(`🔍 Found repeater key: ${repeaterKey}`);
                
                let repeaterData = settings[repeaterKey];
                
                // Handle Backbone Collections (common in King Addons and some Elementor widgets)
                if (repeaterData && typeof repeaterData === 'object' && repeaterData.models) {
                    console.log(`🔍 BACKBONE COLLECTION detected for ${repeaterKey}:`, repeaterData);
                    console.log(`🔍 Collection length:`, repeaterData.length || repeaterData.models.length);
                    
                    // Extract models from Backbone collection
                    const models = repeaterData.models || [];
                    for (let i = 0; i < models.length; i++) {
                        const model = models[i];
                        const modelData = model.attributes || model.toJSON();
                        console.log(`🔍 Processing Backbone model ${i}:`, modelData);
                        
                        for (const fieldName of fieldNames) {
                            if (modelData[fieldName] && typeof modelData[fieldName] === 'string' && modelData[fieldName].trim()) {
                                const fieldKey = `${repeaterKey}[${i}][${fieldName}]`;
                                const fieldValue = modelData[fieldName];
                                
                                console.log(`✅ Found repeater text field: ${fieldKey} = ${fieldValue}`);
                                textFields.push({
                                    field: fieldKey,
                                    value: fieldValue,
                                    type: 'text',
                                    isRepeater: true,
                                    repeaterKey: repeaterKey,
                                    repeaterIndex: i,
                                    repeaterField: fieldName
                                });
                            }
                        }
                    }
                }
                // Handle regular arrays
                else if (Array.isArray(repeaterData)) {
                    console.log(`🔍 ARRAY detected for ${repeaterKey}, length:`, repeaterData.length);
                    
                    for (let i = 0; i < repeaterData.length; i++) {
                        const item = repeaterData[i];
                        for (const fieldName of fieldNames) {
                            if (item[fieldName] && typeof item[fieldName] === 'string' && item[fieldName].trim()) {
                                const fieldKey = `${repeaterKey}[${i}][${fieldName}]`;
                                const fieldValue = item[fieldName];
                                
                                console.log(`✅ Found repeater text field: ${fieldKey} = ${fieldValue}`);
                                textFields.push({
                                    field: fieldKey,
                                    value: fieldValue,
                                    type: 'text',
                                    isRepeater: true,
                                    repeaterKey: repeaterKey,
                                    repeaterIndex: i,
                                    repeaterField: fieldName
                                });
                            }
                        }
                    }
                }
                // Handle objects with numbered keys (alternative format)
                else if (repeaterData && typeof repeaterData === 'object') {
                    console.log(`🔍 OBJECT detected for ${repeaterKey}:`, repeaterData);
                    const keys = Object.keys(repeaterData).filter(key => /^\d+$/.test(key));
                    console.log(`🔍 Found numeric keys:`, keys);
                    
                    for (const key of keys) {
                        const item = repeaterData[key];
                        for (const fieldName of fieldNames) {
                            if (item[fieldName] && typeof item[fieldName] === 'string' && item[fieldName].trim()) {
                                const fieldKey = `${repeaterKey}[${key}][${fieldName}]`;
                                const fieldValue = item[fieldName];
                                
                                console.log(`✅ Found repeater text field: ${fieldKey} = ${fieldValue}`);
                                textFields.push({
                                    field: fieldKey,
                                    value: fieldValue,
                                    type: 'text',
                                    isRepeater: true,
                                    repeaterKey: repeaterKey,
                                    repeaterIndex: key,
                                    repeaterField: fieldName
                                });
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Show progress UI in popup
     */
    function showProgressInPopup($popup) {
        // Update header for compact mode with close button
        var headerHtml = `
            <img src="${KingAddonsAiField.plugin_url}includes/admin/img/ai.svg" style="width:20px;height:20px;"/>
            AI Translation in Progress
            <button class="king-addons-translator-close-btn" title="Close">×</button>
        `;
        
        var progressHtml = `
            <div class="king-addons-translator-progress">
                <div class="king-addons-translator-progress-text">
                    Translating page elements... <span id="king-addons-progress-count">0 / ${translationState.totalElements}</span>
                </div>
                <div class="king-addons-translator-progress-bar">
                    <div class="king-addons-translator-progress-fill" id="king-addons-progress-fill"></div>
                </div>
                <div class="king-addons-translator-current-element" id="king-addons-current-element">
                    Starting translation...
                </div>
            </div>
        `;
        
        // Update header 
        $popup.find('h3').html(headerHtml);
        
        // Hide the subtitle/description text during translation
        $popup.find('h3').next('div').hide();
        
        $popup.find('.king-addons-translator-form').html(progressHtml);
        
        // Note: Close button events are handled by global document handler to prevent duplicates
    }

    /**
     * Translate elements sequentially
     */
    function translateElementsSequentially(elements, index, $popup) {
        // Check if translation was cancelled
        if (translationState.isCancelled) {
            console.log('Translation cancelled, stopping process');
            return;
        }
        
        if (index >= elements.length) {
            console.log('🏁 Translation sequence complete! Index:', index, 'Total elements:', elements.length);
            console.log('🎯 Calling showTranslationComplete with popup:', $popup && $popup.length > 0 ? 'exists' : 'missing');
            showTranslationComplete($popup);
            return;
        }
        
        var element = elements[index];
        translationState.currentElement = element;
        
        // Update progress UI
        updateProgressUI(index + 1, element);
        
        // Highlight current element in preview
        highlightElementInPreview(element.elementId, true);
        
        // Translate all text fields for this element
        translateElementFields(element, function(success) {
            // Remove highlight
            highlightElementInPreview(element.elementId, false);
            
            if (success) {
                translationState.translatedElements++;
                showElementSuccess(element.elementId);
            } else {
                translationState.failedElements++;
            }
            
            // Continue with next element after a short delay
            setTimeout(function() {
                // Check if translation was cancelled before proceeding
                if (!translationState.isCancelled) {
                    translateElementsSequentially(elements, index + 1, $popup);
                }
            }, 500);
        });
    }

    /**
     * Translate all text fields for an element
     */
    function translateElementFields(element, callback) {
        // Check if translation was cancelled before starting
        if (translationState.isCancelled) {
            console.log('Translation cancelled, skipping element fields');
            callback(false);
            return;
        }
        
        var fieldsToTranslate = element.textFields.slice();
        var translatedFields = {};
        var completedFields = 0;
        var hasErrors = false;
        
        if (fieldsToTranslate.length === 0) {
            callback(true);
            return;
        }
        
        function translateNextField() {
            // Check if translation was cancelled before processing next field
            if (translationState.isCancelled) {
                console.log('Translation cancelled, stopping field translation');
                callback(false);
                return;
            }
            
            if (completedFields >= fieldsToTranslate.length) {
                // All fields translated, update the element
                if (Object.keys(translatedFields).length > 0 && !translationState.isCancelled) {
                    updateElementSettings(element.container, translatedFields);
                }
                callback(!hasErrors);
                return;
            }
            
            var field = fieldsToTranslate[completedFields];
            translateSingleField(field.value, function(translatedText, success) {
                // Check if translation was cancelled while waiting for response
                if (translationState.isCancelled) {
                    console.log('Translation cancelled, ignoring field response');
                    callback(false);
                    return;
                }
                
                if (success && translatedText) {
                    translatedFields[field.field] = translatedText;
                } else {
                    hasErrors = true;
                }
                
                completedFields++;
                setTimeout(translateNextField, 200); // Small delay between field translations
            });
        }
        
        translateNextField();
    }

    /**
     * Translate a single text field
     */
    function translateSingleField(text, callback) {
        // Check if translation was cancelled before making request
        if (translationState.isCancelled) {
            console.log('Translation cancelled, skipping AJAX request');
            callback(text, false);
            return;
        }
        
        var request = $.post(KingAddonsAiField.ajax_url, {
            action: 'king_addons_ai_translate_text',
            nonce: KingAddonsAiField.generate_nonce,
            text: text,
            from_lang: translationState.fromLang,
            to_lang: translationState.toLang
        }, function(response) {
            // Remove request from active requests list
            var index = translationState.currentRequests.indexOf(request);
            if (index > -1) {
                translationState.currentRequests.splice(index, 1);
            }
            
            // Check if translation was cancelled while request was in progress
            if (translationState.isCancelled) {
                console.log('Translation cancelled, ignoring AJAX response');
                callback(text, false);
                return;
            }
            
            if (response.success && response.data.translated_text) {
                callback(response.data.translated_text, true);
            } else {
                // Handle API errors with more detail
                var errorMessage = 'Translation failed';
                if (response.data && response.data.message) {
                    errorMessage = response.data.message;
                } else if (!response.success && response.data) {
                    errorMessage = typeof response.data === 'string' ? response.data : 'API Error';
                }
                
                console.error('Translation API Error:', errorMessage);
                
                // Check for token limit errors first
                if (errorMessage.toLowerCase().includes('token limit') || 
                    errorMessage.toLowerCase().includes('daily limit') ||
                    errorMessage.toLowerCase().includes('limit reached') ||
                    errorMessage.toLowerCase().includes('quota exceeded') ||
                    errorMessage.toLowerCase().includes('rate limit') ||
                    errorMessage.toLowerCase().includes('too many requests')) {
                    
                    // Stop translation process for limit errors
                    stopTranslationProcess();
                    
                    // Show token limit error popup
                    setTimeout(function() {
                        showTokenLimitError(errorMessage);
                    }, 500);
                    return;
                }
                
                // Show error notification for API key issues
                if (errorMessage.toLowerCase().includes('api key') || 
                    errorMessage.toLowerCase().includes('invalid') ||
                    errorMessage.toLowerCase().includes('unauthorized')) {
                    
                    // Stop translation process for critical errors
                    stopTranslationProcess();
                    
                    // Show API key error popup
                    setTimeout(function() {
                        showApiKeyError('Setup Required', errorMessage + '\n\nPlease check your API key in settings and try again.');
                    }, 500);
                    return;
                }
                
                callback(text, false);
            }
        }).fail(function(xhr, textStatus, errorThrown) {
            // Remove request from active requests list
            var index = translationState.currentRequests.indexOf(request);
            if (index > -1) {
                translationState.currentRequests.splice(index, 1);
            }
            
            // Don't process failed requests if cancelled
            if (translationState.isCancelled) {
                return;
            }
            
            // Handle different types of network errors
            var errorMessage = 'Network error occurred';
            var shouldShowError = false;
            
            if (xhr.status === 0) {
                errorMessage = 'Network connection failed. Please check your internet connection.';
                shouldShowError = true;
            } else if (xhr.status === 401) {
                errorMessage = 'API key is invalid or expired. Please check your API key in settings.';
                shouldShowError = true;
            } else if (xhr.status === 403) {
                errorMessage = 'Access forbidden. Please check your API key permissions.';
                shouldShowError = true;
            } else if (xhr.status === 429) {
                // Try to get detailed error message from response
                var detailedMessage = 'Rate limit exceeded. Please wait a moment and try again.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    detailedMessage = xhr.responseJSON.data.message;
                } else if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response && response.data && response.data.message) {
                            detailedMessage = response.data.message;
                        }
                    } catch (e) {
                        // Keep default message if parsing fails
                    }
                }
                errorMessage = detailedMessage;
                shouldShowError = true;
            } else if (xhr.status >= 500) {
                errorMessage = 'Server error. Please try again later.';
                shouldShowError = true;
            }
            
            console.error('Translation Network Error:', {
                status: xhr.status,
                statusText: textStatus,
                error: errorThrown,
                message: errorMessage,
                responseText: xhr.responseText,
                responseJSON: xhr.responseJSON
            });
            
            // Log specifically for token limit debugging
            if (xhr.status === 429 || errorMessage.toLowerCase().includes('limit')) {
                console.log('🔍 Potential token limit error detected:', {
                    status: xhr.status,
                    message: errorMessage,
                    fullResponse: xhr.responseText
                });
            }
            
            // Show error popup for critical network issues
            if (shouldShowError) {
                stopTranslationProcess();
                
                setTimeout(function() {
                    // Check for token limit errors first (including 429 status)
                    if (errorMessage.toLowerCase().includes('token limit') || 
                        errorMessage.toLowerCase().includes('daily limit') ||
                        errorMessage.toLowerCase().includes('limit reached') ||
                        errorMessage.toLowerCase().includes('quota exceeded') ||
                        errorMessage.toLowerCase().includes('rate limit exceeded') ||
                        errorMessage.toLowerCase().includes('too many requests') ||
                        (xhr.status === 429 && (
                            errorMessage.toLowerCase().includes('limit') ||
                            errorMessage.toLowerCase().includes('exceeded') ||
                            errorMessage.toLowerCase().includes('quota')
                        ))) {
                        
                        showTokenLimitError(errorMessage);
                        return;
                    }
                    
                    if (xhr.status === 401 || xhr.status === 403) {
                        showApiKeyError('Authentication Error', errorMessage);
                    } else {
                        showApiKeyError('Network Error', errorMessage);
                    }
                }, 500);
                return;
            }
            
            callback(text, false);
        });
        
        // Store the request so we can cancel it if needed
        translationState.currentRequests.push(request);
    }

    /**
     * Update element settings with translated text
     */
    function updateElementSettings(container, translatedFields) {
        try {
            console.log('🔄 Updating element settings for widget:', container.model.get('widgetType'));
            console.log('🔄 All translated fields:', translatedFields);
            
            // Separate regular fields from repeater fields
            var regularFields = {};
            var repeaterUpdates = {};
            
            Object.keys(translatedFields).forEach(function(fieldKey) {
                var translatedValue = translatedFields[fieldKey];
                
                // Check if this is a repeater field
                var repeaterMatch = fieldKey.match(/^(.+)\[(\d+)\]\[(.+)\]$/);
                if (repeaterMatch) {
                    // This is a repeater field: repeaterKey[index][fieldName]
                    var repeaterKey = repeaterMatch[1];
                    var itemIndex = parseInt(repeaterMatch[2]);
                    var itemField = repeaterMatch[3];
                    
                    console.log('📝 Found repeater field:', {
                        fullKey: fieldKey,
                        repeaterKey: repeaterKey,
                        itemIndex: itemIndex,
                        itemField: itemField,
                        value: translatedValue
                    });
                    
                    if (!repeaterUpdates[repeaterKey]) {
                        repeaterUpdates[repeaterKey] = {};
                    }
                    if (!repeaterUpdates[repeaterKey][itemIndex]) {
                        repeaterUpdates[repeaterKey][itemIndex] = {};
                    }
                    repeaterUpdates[repeaterKey][itemIndex][itemField] = translatedValue;
                } else {
                    // Regular field
                    console.log('📝 Found regular field:', fieldKey, '=', translatedValue);
                    regularFields[fieldKey] = translatedValue;
                }
            });
            
            // Apply regular field updates
            if (Object.keys(regularFields).length > 0) {
                console.log('🔧 Applying regular field updates:', regularFields);
                $e.run('document/elements/settings', {
                    container: container,
                    settings: regularFields
                });
                console.log('✅ Regular field updates applied successfully');
            }
            
            // Apply repeater field updates
            Object.keys(repeaterUpdates).forEach(function(repeaterKey) {
                console.log('🔧 Updating repeater field:', repeaterKey, repeaterUpdates[repeaterKey]);
                var currentSettings = container.settings.get(repeaterKey);
                
                // Handle Backbone Collections (King Addons and some Elementor widgets)
                if (currentSettings && typeof currentSettings.models !== 'undefined') {
                    console.log('📦 Found Backbone collection for:', repeaterKey, 'Length:', currentSettings.length);
                    
                    // Work with Backbone collection
                    Object.keys(repeaterUpdates[repeaterKey]).forEach(function(itemIndex) {
                        var index = parseInt(itemIndex);
                        if (currentSettings.models[index]) {
                            var model = currentSettings.models[index];
                            
                            // Update the specific fields in this repeater item
                            Object.keys(repeaterUpdates[repeaterKey][itemIndex]).forEach(function(fieldName) {
                                var oldValue = model.get(fieldName);
                                var newValue = repeaterUpdates[repeaterKey][itemIndex][fieldName];
                                
                                console.log('📝 Updating Backbone model [' + index + '][' + fieldName + ']:', oldValue, '->', newValue);
                                
                                // Update the model attribute
                                model.set(fieldName, newValue);
                            });
                        }
                    });
                    
                    // Use Elementor's proper API to notify of changes instead of direct trigger
                    try {
                        // Method 1: Use Elementor's run command to update the entire repeater
                        var backboneData = currentSettings.toJSON ? currentSettings.toJSON() : 
                                          currentSettings.models.map(function(model) { 
                                              return model.toJSON ? model.toJSON() : model.attributes; 
                                          });
                        
                        var repeaterSettings = {};
                        repeaterSettings[repeaterKey] = backboneData;
                        
                        console.log('🔧 Applying Backbone collection update via Elementor API:', {
                            repeaterKey: repeaterKey,
                            dataLength: backboneData.length,
                            settingsObject: repeaterSettings
                        });
                        
                        $e.run('document/elements/settings', {
                            container: container,
                            settings: repeaterSettings
                        });
                        
                        console.log('✅ Backbone collection updates applied via Elementor API for:', repeaterKey);
                    } catch (e) {
                        console.warn('⚠️ Error updating via Elementor API, trying alternative method:', e);
                        
                        // Fallback: Try to manually trigger save without change events
                        try {
                            if (typeof container.saveSettings === 'function') {
                                container.saveSettings();
                                console.log('📡 Triggered saveSettings as fallback for:', repeaterKey);
                            }
                        } catch (e2) {
                            console.warn('⚠️ Fallback method also failed:', e2);
                        }
                    }
                    
                    console.log('✅ Backbone collection updates applied successfully for:', repeaterKey);
                }
                // Handle regular arrays (standard Elementor repeaters)
                else if (Array.isArray(currentSettings)) {
                    console.log('📦 Found array for:', repeaterKey, 'Length:', currentSettings.length);
                    
                    var updatedRepeater = currentSettings.slice(); // Clone array
                    
                    Object.keys(repeaterUpdates[repeaterKey]).forEach(function(itemIndex) {
                        var index = parseInt(itemIndex);
                        if (updatedRepeater[index]) {
                            // Update the specific fields in this repeater item
                            Object.keys(repeaterUpdates[repeaterKey][itemIndex]).forEach(function(fieldName) {
                                var oldValue = updatedRepeater[index][fieldName];
                                var newValue = repeaterUpdates[repeaterKey][itemIndex][fieldName];
                                updatedRepeater[index][fieldName] = newValue;
                                console.log('📝 Updated array item [' + index + '][' + fieldName + ']:', oldValue, '->', newValue);
                            });
                        }
                    });
                    
                    // Update the entire repeater field
                    var repeaterSettings = {};
                    repeaterSettings[repeaterKey] = updatedRepeater;
                    
                    console.log('🔧 Applying array repeater settings update:', {
                        repeaterKey: repeaterKey,
                        originalLength: currentSettings.length,
                        updatedLength: updatedRepeater.length,
                        settingsObject: repeaterSettings
                    });
                    
                    $e.run('document/elements/settings', {
                        container: container,
                        settings: repeaterSettings
                    });
                    console.log('✅ Array repeater updates applied successfully for:', repeaterKey);
                }
                // Handle objects with numbered keys
                else if (currentSettings && typeof currentSettings === 'object') {
                    console.log('📦 Found object for:', repeaterKey, 'Keys:', Object.keys(currentSettings));
                    
                    var updatedObject = Object.assign({}, currentSettings); // Clone object
                    
                    Object.keys(repeaterUpdates[repeaterKey]).forEach(function(itemIndex) {
                        if (updatedObject[itemIndex]) {
                            // Update the specific fields in this repeater item
                            Object.keys(repeaterUpdates[repeaterKey][itemIndex]).forEach(function(fieldName) {
                                var oldValue = updatedObject[itemIndex][fieldName];
                                var newValue = repeaterUpdates[repeaterKey][itemIndex][fieldName];
                                updatedObject[itemIndex][fieldName] = newValue;
                                console.log('📝 Updated object item [' + itemIndex + '][' + fieldName + ']:', oldValue, '->', newValue);
                            });
                        }
                    });
                    
                    // Update the entire repeater field
                    var repeaterSettings = {};
                    repeaterSettings[repeaterKey] = updatedObject;
                    
                    $e.run('document/elements/settings', {
                        container: container,
                        settings: repeaterSettings
                    });
                    console.log('✅ Object repeater updates applied successfully for:', repeaterKey);
                } else {
                    console.log('⚠️ Repeater field not found or unsupported type:', repeaterKey, typeof currentSettings, currentSettings);
                }
            });
            
        } catch (error) {
            console.error('❌ Error updating element settings:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                translatedFields: translatedFields,
                widgetType: container.model.get('widgetType')
            });
        }
    }

    /**
     * Update progress UI
     */
    function updateProgressUI(current, element) {
        var percentage = (current / translationState.totalElements) * 100;
        
        $('#king-addons-progress-count').text(current + ' / ' + translationState.totalElements);
        $('#king-addons-progress-fill').css('width', percentage + '%');
        $('#king-addons-current-element').text('Translating: ' + element.widgetType + ' (' + element.textFields.length + ' fields)');
    }

    /**
     * Inject animation styles into preview iframe
     */
    function injectPreviewStyles() {
        if (!elementor || !elementor.$preview) return;
        
        var $previewDoc = elementor.$preview.contents();
        var $previewHead = $previewDoc.find('head');
        
        if ($previewHead.length && !$previewDoc.find('#king-addons-preview-translator-styles').length) {
            var previewStyles = `
                <style id="king-addons-preview-translator-styles">
                    /* Element highlighting animation for translation */
                    .king-addons-translating-element {
                        position: relative !important;
                        border: 3px solid #2196F3 !important;
                        box-shadow: 0 0 20px rgba(33, 150, 243, 0.4) !important;
                        border-radius: 4px !important;
                        animation: king-addons-translate-pulse 1.5s infinite ease-in-out !important;
                        z-index: 999 !important;
                    }
                    
                    .king-addons-translating-element::before {
                        content: "🔄 Translating..." !important;
                        position: absolute !important;
                        top: -35px !important;
                        left: 50% !important;
                        transform: translateX(-50%) !important;
                        background: #2196F3 !important;
                        color: white !important;
                        padding: 6px 12px !important;
                        border-radius: 20px !important;
                        font-size: 12px !important;
                        font-weight: 600 !important;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                        z-index: 10000 !important;
                        animation: king-addons-translate-bounce 0.8s ease-out !important;
                        box-shadow: 0 3px 10px rgba(33, 150, 243, 0.3) !important;
                        white-space: nowrap !important;
                    }
                    
                    .king-addons-translated-element {
                        position: relative !important;
                        border: 3px solid #4CAF50 !important;
                        box-shadow: 0 0 20px rgba(76, 175, 80, 0.4) !important;
                        border-radius: 4px !important;
                        animation: king-addons-translate-success 1.2s ease-out !important;
                        z-index: 999 !important;
                    }
                    
                    .king-addons-translated-element::before {
                        content: "✅ Translated!" !important;
                        position: absolute !important;
                        top: -35px !important;
                        left: 50% !important;
                        transform: translateX(-50%) !important;
                        background: #4CAF50 !important;
                        color: white !important;
                        padding: 6px 12px !important;
                        border-radius: 20px !important;
                        font-size: 12px !important;
                        font-weight: 600 !important;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
                        z-index: 10000 !important;
                        animation: king-addons-translate-bounce 0.8s ease-out !important;
                        box-shadow: 0 3px 10px rgba(76, 175, 80, 0.3) !important;
                        white-space: nowrap !important;
                    }
                    
                    /* Pulsing animation for translating elements */
                    @keyframes king-addons-translate-pulse {
                        0% { 
                            box-shadow: 0 0 0 0 rgba(33, 150, 243, 0.7), 
                                        0 0 20px rgba(33, 150, 243, 0.4);
                            transform: scale(1);
                        }
                        50% { 
                            box-shadow: 0 0 0 8px rgba(33, 150, 243, 0.2), 
                                        0 0 30px rgba(33, 150, 243, 0.6);
                            transform: scale(1.02);
                        }
                        100% { 
                            box-shadow: 0 0 0 0 rgba(33, 150, 243, 0), 
                                        0 0 20px rgba(33, 150, 243, 0.4);
                            transform: scale(1);
                        }
                    }
                    
                    /* Success animation for completed elements */
                    @keyframes king-addons-translate-success {
                        0% { 
                            box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7), 
                                        0 0 20px rgba(76, 175, 80, 0.4);
                            transform: scale(1);
                        }
                        20% { 
                            box-shadow: 0 0 0 12px rgba(76, 175, 80, 0.3), 
                                        0 0 40px rgba(76, 175, 80, 0.6);
                            transform: scale(1.05);
                        }
                        40% { 
                            transform: scale(0.98);
                        }
                        60% { 
                            transform: scale(1.02);
                        }
                        80% { 
                            transform: scale(0.99);
                        }
                        100% { 
                            box-shadow: 0 0 0 0 rgba(76, 175, 80, 0), 
                                        0 0 20px rgba(76, 175, 80, 0.2);
                            transform: scale(1);
                        }
                    }
                    
                    /* Bounce animation for labels */
                    @keyframes king-addons-translate-bounce {
                        0% { 
                            transform: translateX(-50%) translateY(-10px) scale(0.8);
                            opacity: 0;
                        }
                        50% { 
                            transform: translateX(-50%) translateY(-2px) scale(1.1);
                            opacity: 1;
                        }
                        70% { 
                            transform: translateX(-50%) translateY(-1px) scale(0.95);
                        }
                        100% { 
                            transform: translateX(-50%) translateY(0) scale(1);
                            opacity: 1;
                        }
                    }
                </style>
            `;
            $previewHead.append(previewStyles);
            console.log('Preview animation styles injected into iframe');
        }
    }

    /**
     * Highlight element in preview
     */
    function highlightElementInPreview(elementId, highlight) {
        // Ensure preview styles are injected
        injectPreviewStyles();
        
        // Find element in preview iframe
        if (!elementor || !elementor.$preview) {
            console.log('Preview not available');
            return;
        }
        
        var $previewDoc = elementor.$preview.contents();
        var $previewElement = $previewDoc.find('[data-id="' + elementId + '"]');
        
        if ($previewElement.length === 0) {
            console.log('Element not found in preview:', elementId);
            return;
        }
        
        if (highlight) {
            // Remove any existing classes first
            $previewElement.removeClass('king-addons-translated-element');
            $previewElement.addClass('king-addons-translating-element');
            console.log('Added translating highlight to element:', elementId);
        } else {
            $previewElement.removeClass('king-addons-translating-element');
            console.log('Removed translating highlight from element:', elementId);
        }
    }

    /**
     * Show element success animation
     */
    function showElementSuccess(elementId) {
        // Ensure preview styles are injected
        injectPreviewStyles();
        
        // Find element in preview iframe
        if (!elementor || !elementor.$preview) {
            console.log('Preview not available for success animation');
            return;
        }
        
        var $previewDoc = elementor.$preview.contents();
        var $previewElement = $previewDoc.find('[data-id="' + elementId + '"]');
        
        if ($previewElement.length === 0) {
            console.log('Element not found in preview for success animation:', elementId);
            return;
        }
        
        // Remove translating class and add translated class
        $previewElement.removeClass('king-addons-translating-element');
        $previewElement.addClass('king-addons-translated-element');
        console.log('Added success animation to element:', elementId);
        
        // Remove the success animation after it completes
        setTimeout(function() {
            $previewElement.removeClass('king-addons-translated-element');
            console.log('Removed success animation from element:', elementId);
        }, 1200);
    }

    /**
     * Show translation complete with stats (stays open until manually closed)
     */
    function showTranslationComplete($popup) {
        console.log('🎉 showTranslationComplete called');
        console.log('🔍 Popup exists:', $popup && $popup.length > 0);
        console.log('🔍 Popup classes:', $popup && $popup.length > 0 ? $popup.attr('class') : 'N/A');
        console.log('📊 Final stats:', {
            total: translationState.totalElements,
            translated: translationState.translatedElements,
            failed: translationState.failedElements
        });
        
        if (!$popup || $popup.length === 0) {
            console.error('❌ Cannot show translation results: popup not found');
            return;
        }
        
        var statsHtml = `
            <div class="king-addons-translator-progress">
                <div class="king-addons-translator-progress-text">
                    ✅ Translation Complete!
                </div>
                <div class="king-addons-translator-stats">
                    <div class="king-addons-translator-stat">
                        <div class="king-addons-translator-stat-number" style="font-size: 32px; font-weight: bold; color: #2196F3;">${translationState.totalElements}</div>
                        <div class="king-addons-translator-stat-label">Total Elements</div>
                    </div>
                    <div class="king-addons-translator-stat">
                        <div class="king-addons-translator-stat-number" style="font-size: 32px; font-weight: bold; color: #4CAF50; animation: pulse 2s infinite;">${translationState.translatedElements}</div>
                        <div class="king-addons-translator-stat-label">✅ Translated</div>
                    </div>
                    <div class="king-addons-translator-stat">
                        <div class="king-addons-translator-stat-number" style="font-size: 32px; font-weight: bold; color: ${translationState.failedElements > 0 ? '#F44336' : '#999'};">${translationState.failedElements}</div>
                        <div class="king-addons-translator-stat-label">${translationState.failedElements > 0 ? '❌' : '⚪'} Failed</div>
                    </div>
                </div>
                <div class="king-addons-translator-actions" style="margin-top: 20px; text-align: center;">
                    <button class="king-addons-translator-btn-primary" id="king-addons-close-stats" style="background: #4CAF50; color: white; border: none; padding: 12px 30px; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.2); min-width: 120px;">
                        Close Results
                    </button>
                </div>
            </div>
        `;
        
        console.log('📝 Setting stats HTML into popup form');
        var $form = $popup.find('.king-addons-translator-form');
        console.log('🔍 Form element found:', $form.length > 0);
        
        $form.html(statsHtml);
        
        console.log('✅ Stats HTML set, popup should now show results');
        
        // Play success sound (Web Audio API)
        try {
            var audioContext = new (window.AudioContext || window.webkitAudioContext)();
            var oscillator = audioContext.createOscillator();
            var gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.1);
            oscillator.frequency.setValueAtTime(1200, audioContext.currentTime + 0.2);
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (e) {
            console.log('Audio notification not available');
        }
        
        // Show temporary notification to attract attention
        var $notificationBanner = $('<div style="position: fixed; top: 0; left: 0; right: 0; background: linear-gradient(135deg, #4CAF50, #2E7D32); color: white; padding: 12px; text-align: center; font-size: 16px; font-weight: bold; z-index: 1000000; box-shadow: 0 2px 10px rgba(0,0,0,0.3); animation: slideDown 0.5s ease;">🎉 Translation Complete! Check the results popup below.</div>');
        $('body').append($notificationBanner);
        
        // Remove notification after 5 seconds
        setTimeout(function() {
            $notificationBanner.fadeOut(300, function() {
                $notificationBanner.remove();
            });
        }, 5000);
        
        // Log popup state for debugging
        console.log('🔍 Final popup state check:');
        console.log('- Popup visible:', $popup.is(':visible'));
        console.log('- Popup display:', $popup.css('display'));
        console.log('- Popup opacity:', $popup.css('opacity'));
        console.log('- Popup z-index:', $popup.css('z-index'));
        console.log('- Popup position:', $popup.css('position'));
        console.log('- Overlay exists:', $popup.closest('.king-addons-translator-overlay').length > 0);
        console.log('- Browser dimensions:', window.innerWidth + 'x' + window.innerHeight);
        
        // Update header to show completion
        var completionHeaderHtml = `
            <img src="${KingAddonsAiField.plugin_url}includes/admin/img/ai.svg" style="width:20px;height:20px;filter: invert(1);"/>
            AI Translation Complete
        `;
        $popup.find('h3').html(completionHeaderHtml);
        
        // Show the subtitle/description text again  
        $popup.find('h3').next('div').show();
        
        // If popup is in compact mode, move it back to center for better visibility
        if ($popup.hasClass('compact')) {
            console.log('📐 Moving popup from compact mode to center for results display');
            
            // Remove compact class and positioning
            $popup.removeClass('compact moving');
            $popup.css({
                'position': 'fixed',
                'top': '50%',
                'left': '50%',
                'transform': 'translate(-50%, -50%)',
                'right': 'auto',
                'bottom': 'auto',
                'width': '500px',
                'max-width': '90vw',
                'z-index': '999999',
                'background': 'white',
                'border-radius': '8px',
                'box-shadow': '0 10px 25px rgba(0,0,0,0.2)',
                'opacity': '1',
                'visibility': 'visible'
            });
            
            // Re-add overlay if it doesn't exist
            if (!$popup.closest('.king-addons-translator-overlay').length) {
                var $overlay = $('<div class="king-addons-translator-overlay"></div>').css({
                    'position': 'fixed',
                    'top': '0',
                    'left': '0',
                    'width': '100%',
                    'height': '100%',
                    'background': 'rgba(0, 0, 0, 0.5)',
                    'z-index': '999998',
                    'display': 'flex',
                    'align-items': 'center',
                    'justify-content': 'center'
                });
                $popup.wrap($overlay);
            } else {
                // Make sure existing overlay is visible
                $popup.closest('.king-addons-translator-overlay').css({
                    'z-index': '999998',
                    'display': 'flex'
                });
            }
            
            // Add entrance animation
            $popup.css('opacity', '0').animate({'opacity': '1'}, 300);
        } else {
            // For non-compact popups, ensure they're also properly visible
            console.log('📐 Ensuring non-compact popup is visible');
            $popup.css({
                'z-index': '999999',
                'opacity': '1',
                'visibility': 'visible',
                'position': 'fixed'
            });
            
            // Make sure overlay is visible
            var $overlay = $popup.closest('.king-addons-translator-overlay');
            if ($overlay.length) {
                $overlay.css({
                    'z-index': '999998',
                    'display': 'block',
                    'opacity': '1',
                    'visibility': 'visible'
                });
            }
            
            // Add entrance animation
            $popup.css('opacity', '0').animate({'opacity': '1'}, 300);
        }
        
        $('#king-addons-close-stats').on('click', function() {
            // For compact popup, just remove it directly since overlay is already gone
            if ($popup.hasClass('compact')) {
                $popup.remove();
            } else {
                $popup.closest('.king-addons-translator-overlay').remove();
            }
        });
        
        // Reset translation state and re-enable button
        translationState.isTranslating = false;
        translationState.isCancelled = false;
        translationState.currentRequests = []; // Clear any remaining requests
        toggleTranslatorButton(false);
        
        // Note: Auto-close removed by user request - popup stays open until manually closed
    }

    /**
     * Handle Elementor initialization
     */
    function onElementorInit() {
        // Check if AI Page Translator is enabled
        if (typeof KingAddonsAiField !== 'undefined' && KingAddonsAiField.translator_enabled === false) {
            console.log('AI Page Translator is disabled in settings');
            return;
        }
        
        // Inject styles first
        injectTranslatorStyles();
        
        // Try to inject preview styles (will work when preview is available)
        setTimeout(function() {
            injectPreviewStyles();
        }, 1000);
        
        // Add button immediately
        addTranslatorButton();

        // Also add button when panel opens
        if (typeof elementor !== 'undefined' && elementor.hooks) {
            elementor.hooks.addAction('panel/open_editor/widget', function() {
                setTimeout(addTranslatorButton, 100);
            });

            // Add button when navigator opens
            elementor.hooks.addAction('navigator/init', function() {
                setTimeout(addTranslatorButton, 100);
            });
            
            // Inject preview styles when preview loads
            elementor.hooks.addAction('preview/loaded', function() {
                console.log('Preview loaded, injecting animation styles');
                injectPreviewStyles();
            });
        }

        // Monitor for panel changes
        observePanelChanges();
    }

    /**
     * Observe panel changes to re-add button if needed
     */
    function observePanelChanges() {
        function createObserver() {
            return new MutationObserver(function(mutations) {
                var shouldCheck = false;
                
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        shouldCheck = true;
                    }
                });

                if (shouldCheck) {
                    setTimeout(addTranslatorButton, 300);
                }
            });
        }

        // Observe changes in the top toolbar (priority)
        var topToolbar = document.querySelector('#elementor-editor-wrapper-v2 .MuiToolbar-root');
        if (topToolbar) {
            var topObserver = createObserver();
            topObserver.observe(topToolbar, {
                childList: true,
                subtree: true
            });
            console.log('Observing changes in top toolbar');
        }
        
        // Also observe the main editor wrapper for structural changes
        var editorWrapper = document.querySelector('#elementor-editor-wrapper-v2');
        if (editorWrapper) {
            var wrapperObserver = createObserver();
            wrapperObserver.observe(editorWrapper, {
                childList: true,
                subtree: false
            });
            console.log('Observing changes in editor wrapper');
        }

        // Observe changes in the main panel (fallback)
        var panel = document.querySelector('#elementor-panel');
        if (panel) {
            var panelObserver = createObserver();
            panelObserver.observe(panel, {
                childList: true,
                subtree: true
            });
            console.log('Observing changes in elementor panel');
        }

        // Observe the main Elementor editor area
        var editorArea = document.querySelector('#elementor-editor-wrapper, .elementor-editor-wrapper');
        if (editorArea) {
            var editorObserver = createObserver();
            editorObserver.observe(editorArea, {
                childList: true,
                subtree: true
            });
            console.log('Observing changes in editor area');
        }
    }

    /**
     * Initialize the translator
     */
    function initTranslator() {
        // Wait for Elementor to be fully loaded
        $(window).on('elementor:init', function() {
            console.log('Elementor initialized, setting up AI Translator');
            // Add small delay to ensure Material UI is rendered
            setTimeout(onElementorInit, 500);
        });

        // Fallback if elementor:init doesn't fire
        setTimeout(function() {
            console.log('Fallback initialization for AI Translator');
            onElementorInit();
        }, 3000);
        
        // Additional fallback for when Material UI components are ready
        setTimeout(function() {
            if (!document.querySelector('.king-addons-ai-translator-btn')) {
                console.log('Material UI fallback initialization for AI Translator');
                onElementorInit();
            }
        }, 5000);
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('DOM ready, initializing AI Translator');
        initTranslator();
        
        // Global event handler for close buttons (backup protection)
        $(document).off('click.aiTranslatorGlobal').on('click.aiTranslatorGlobal', '.king-addons-translator-close-btn', function(e) {
            console.log('🚫 Global close button clicked');
            if (translationState.isTranslating) {
                console.log('🛑 Stopping translation via global handler');
                stopTranslationProcess();
                
                // Show cancellation notice
                var $notice = $('<div style="position: fixed; top: 120px; right: 20px; background: #ff9800; color: white; padding: 8px 12px; border-radius: 4px; font-size: 14px; z-index: 1000000;">Translation cancelled</div>');
                $('body').append($notice);
                setTimeout(function() {
                    $notice.fadeOut(300, function() {
                        $notice.remove();
                    });
                }, 2000);
            }
            
            // Close popup/overlay
            var $popup = $(this).closest('.king-addons-translator-popup');
            if ($popup.hasClass('compact')) {
                $popup.remove();
            } else {
                $popup.closest('.king-addons-translator-overlay').remove();
            }
            
            // Reset state and re-enable button
            translationState.isTranslating = false;
            translationState.isCancelled = false; 
            translationState.currentRequests = [];
            toggleTranslatorButton(false);
            
            e.preventDefault();
            e.stopPropagation();
        });
    });

})(jQuery, window.elementor); 