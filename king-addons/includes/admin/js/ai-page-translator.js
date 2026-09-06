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
        currentRequests: [], // Store active AJAX requests to cancel them
        doneElementIds: [],  // Elements finished in this run, for resuming later
        failedElementIds: [],
        consecutiveFailures: 0,
        resumedCount: 0,
        lastErrorMessage: ''
    };

    // Saved progress lets a run continue after the editor is reloaded.
    var PROGRESS_STORAGE_PREFIX = 'king_addons_ai_translator_progress_';
    var PROGRESS_MAX_AGE_MS = 7 * 24 * 60 * 60 * 1000; // A week-old run is stale.

    /**
     * Id of the document currently open in the editor, or 0 when unknown.
     */
    function getCurrentDocumentId() {
        try {
            var doc = elementor.documents.getCurrent();
            return doc && doc.id ? parseInt(doc.id, 10) : 0;
        } catch (e) {
            return 0;
        }
    }

    function getProgressStorageKey(documentId) {
        return PROGRESS_STORAGE_PREFIX + (documentId || getCurrentDocumentId());
    }

    /**
     * Persist where the run got to. Storage can be unavailable (private mode,
     * blocked site data), and losing resume support must never break a run.
     */
    function saveTranslationProgress() {
        var documentId = getCurrentDocumentId();
        if (!documentId || !translationState.totalElements) {
            return;
        }

        try {
            window.localStorage.setItem(getProgressStorageKey(documentId), JSON.stringify({
                v: 1,
                documentId: documentId,
                fromLang: translationState.fromLang,
                toLang: translationState.toLang,
                total: translationState.totalElements,
                done: translationState.doneElementIds,
                failed: translationState.failedElementIds,
                updatedAt: Date.now()
            }));
        } catch (e) {
            // Ignore - resuming is a convenience, not a requirement.
        }
    }

    function clearTranslationProgress() {
        try {
            window.localStorage.removeItem(getProgressStorageKey());
        } catch (e) {
            // Ignore.
        }
    }

    /**
     * Saved progress for the open document, or null when there is nothing
     * usable to resume.
     */
    function loadTranslationProgress() {
        var documentId = getCurrentDocumentId();
        if (!documentId) {
            return null;
        }

        var raw;
        try {
            raw = window.localStorage.getItem(getProgressStorageKey(documentId));
        } catch (e) {
            return null;
        }

        if (!raw) {
            return null;
        }

        var saved;
        try {
            saved = JSON.parse(raw);
        } catch (e) {
            clearTranslationProgress();
            return null;
        }

        var valid = saved
            && saved.v === 1
            && saved.documentId === documentId
            && saved.toLang
            && Array.isArray(saved.done)
            && typeof saved.total === 'number';

        if (!valid) {
            clearTranslationProgress();
            return null;
        }

        // Drop stale entries, and finished ones that were never cleaned up.
        if ((Date.now() - (saved.updatedAt || 0)) > PROGRESS_MAX_AGE_MS || saved.done.length >= saved.total) {
            clearTranslationProgress();
            return null;
        }

        return saved;
    }

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
                    /* Design tokens - flat surfaces, one accent, no gradients. */
                    :root {
                        --ka-tr-accent: #5B03FF;
                        --ka-tr-accent-hover: #4A02D6;
                        --ka-tr-accent-soft: rgba(91, 3, 255, 0.08);
                        --ka-tr-ink: #16161a;
                        --ka-tr-ink-muted: #6b7280;
                        --ka-tr-surface: #ffffff;
                        --ka-tr-surface-sunken: #f6f7f9;
                        --ka-tr-border: #e4e6ea;
                        --ka-tr-border-strong: #d3d6db;
                        --ka-tr-success: #10794a;
                        --ka-tr-success-soft: #eefaf3;
                        --ka-tr-success-border: #c2e9d4;
                        --ka-tr-warning: #8a5a00;
                        --ka-tr-warning-soft: #fff8ec;
                        --ka-tr-warning-border: #f3ddb4;
                        --ka-tr-danger: #b3261e;
                        --ka-tr-radius: 12px;
                        --ka-tr-radius-sm: 8px;
                        --ka-tr-font: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    }

                    /* Translator Button Styles */
                    /* Desaturated violet at the toolbar's own 4px radius: the
                       saturated fill shimmered against the near-black bar and
                       its 8px corners did not match any neighbouring control. */
                    .king-addons-ai-translator-btn {
                        background: #6C5CE7 !important;
                        border: none !important;
                        color: #fff !important;
                        padding: 8px 14px !important;
                        border-radius: 4px !important;
                        font-size: 12px !important;
                        font-weight: 600 !important;
                        cursor: pointer !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        gap: 6px !important;
                        transition: background-color 0.15s ease !important;
                        margin: 8px !important;
                        position: relative !important;
                        z-index: 10 !important;
                        text-decoration: none !important;
                        outline: none !important;
                        box-shadow: none !important;
                    }
                    .king-addons-ai-translator-btn:hover {
                        background: #5B4BD6 !important;
                        box-shadow: none !important;
                    }
                    .king-addons-ai-translator-btn:focus-visible {
                        outline: 2px solid #8C7DFF !important;
                        outline-offset: 2px !important;
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
                        background: rgba(16, 16, 20, 0.55);
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
                        --ka-tr-pad: 28px;
                        background: var(--ka-tr-surface);
                        padding: var(--ka-tr-pad);
                        border-radius: var(--ka-tr-radius);
                        box-shadow: 0 1px 2px rgba(16,16,20,0.06), 0 12px 32px rgba(16,16,20,0.16);
                        width: 90%;
                        max-width: 480px;
                        max-height: 82vh;
                        overflow-y: auto;
                        transition: all 0.3s ease;
                        transform: scale(1);
                        font-family: var(--ka-tr-font);
                        color: var(--ka-tr-ink);
                        line-height: 1.5;
                    }
                    
                    /* Compact popup for top-right positioning */
                    .king-addons-translator-popup.compact {
                        --ka-tr-pad: 16px;
                        position: fixed;
                        top: 80px;
                        right: 20px;
                        width: 350px;
                        max-width: 350px;
                        padding: var(--ka-tr-pad);
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
                        margin: 0 0 6px 0;
                        font-size: 18px;
                        font-weight: 650;
                        letter-spacing: -0.01em;
                        color: var(--ka-tr-ink);
                        display: flex;
                        align-items: center;
                        gap: 10px;
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
                        font-weight: 600;
                        color: var(--ka-tr-ink);
                        font-size: 13px;
                    }

                    .king-addons-translator-field select {
                        padding: 10px 12px;
                        border: 1px solid var(--ka-tr-border-strong);
                        border-radius: var(--ka-tr-radius-sm);
                        font-size: 14px;
                        height: auto;
                        background: var(--ka-tr-surface);
                        color: var(--ka-tr-ink);
                    }

                    .king-addons-translator-field select:focus {
                        border-color: #5B03FF;
                        box-shadow: 0 0 0 1px rgba(91,3,255,0.3);
                        outline: none;
                    }

                    .king-addons-translator-field input[type="text"] {
                        padding: 10px 12px;
                        border: 1px solid var(--ka-tr-border-strong);
                        border-radius: var(--ka-tr-radius-sm);
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
                        background: #f5b301;
                        color: #3a2c00;
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
                        font-size: 12px;
                        color: var(--ka-tr-ink-muted);
                        margin-top: 4px;
                        line-height: 1.5;
                        background: var(--ka-tr-surface-sunken);
                        border: 1px solid var(--ka-tr-border);
                        border-radius: var(--ka-tr-radius-sm);
                        padding: 12px 14px;
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
                        padding: 10px 12px;
                        background: var(--ka-tr-surface-sunken);
                        border: 1px solid var(--ka-tr-border);
                        border-radius: var(--ka-tr-radius-sm);
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
                        position: sticky;
                        bottom: calc(var(--ka-tr-pad) * -1);
                        margin: 8px calc(var(--ka-tr-pad) * -1) calc(var(--ka-tr-pad) * -1);
                        padding: 14px var(--ka-tr-pad) var(--ka-tr-pad);
                        background: var(--ka-tr-surface);
                        border-top: 1px solid var(--ka-tr-border);
                    }

                    .king-addons-translator-btn-primary,
                    .king-addons-translator-btn-secondary {
                        padding: 11px 20px;
                        border-radius: var(--ka-tr-radius-sm);
                        font-size: 14px;
                        font-weight: 600;
                        font-family: inherit;
                        line-height: 1.2;
                        cursor: pointer;
                        flex: 1;
                        transition: background-color 0.15s ease, border-color 0.15s ease;
                    }

                    .king-addons-translator-btn-primary {
                        background: var(--ka-tr-accent);
                        border: 1px solid var(--ka-tr-accent);
                        color: #fff;
                    }

                    .king-addons-translator-btn-primary:hover {
                        background: var(--ka-tr-accent-hover);
                        border-color: var(--ka-tr-accent-hover);
                        color: #fff;
                    }

                    .king-addons-translator-btn-primary:disabled {
                        background: var(--ka-tr-border-strong);
                        border-color: var(--ka-tr-border-strong);
                        color: #fff;
                        cursor: not-allowed;
                    }

                    .king-addons-translator-btn-secondary {
                        background: var(--ka-tr-surface);
                        border: 1px solid var(--ka-tr-border-strong);
                        color: var(--ka-tr-ink);
                    }

                    .king-addons-translator-btn-secondary:hover {
                        background: var(--ka-tr-surface-sunken);
                    }

                    .king-addons-translator-btn-primary:focus-visible,
                    .king-addons-translator-btn-secondary:focus-visible {
                        outline: 2px solid var(--ka-tr-accent);
                        outline-offset: 2px;
                    }

                    /* Progress Styles */
                    .king-addons-translator-progress {
                        margin-top: 16px;
                        padding: 16px;
                        background: var(--ka-tr-surface-sunken);
                        border: 1px solid var(--ka-tr-border);
                        border-radius: var(--ka-tr-radius-sm);
                    }

                    .king-addons-translator-progress-text {
                        font-size: 14px;
                        color: #555;
                        margin-bottom: 8px;
                    }

                    .king-addons-translator-progress-bar {
                        width: 100%;
                        height: 6px;
                        background: var(--ka-tr-border);
                        border-radius: 999px;
                        overflow: hidden;
                        margin-bottom: 8px;
                    }

                    .king-addons-translator-progress-fill {
                        height: 100%;
                        background: var(--ka-tr-accent);
                        width: 0%;
                        transition: width 0.3s ease;
                    }

                    .king-addons-translator-current-element {
                        font-size: 12px;
                        color: var(--ka-tr-ink-muted);
                    }

                    .ka-tr-activity {
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        min-height: 18px;
                    }

                    .ka-tr-spinner {
                        flex: 0 0 13px;
                        width: 13px;
                        height: 13px;
                        border: 2px solid var(--ka-tr-border);
                        border-top-color: var(--ka-tr-accent);
                        border-radius: 50%;
                        animation: rotate 0.7s linear infinite;
                    }

                    /* Respect a reduced-motion preference rather than spinning regardless. */
                    @media (prefers-reduced-motion: reduce) {
                        .ka-tr-spinner {
                            animation-duration: 2.4s;
                        }
                    }

                    .ka-tr-snippet {
                        margin-top: 8px;
                        padding: 8px 10px;
                        background: var(--ka-tr-surface);
                        border: 1px solid var(--ka-tr-border);
                        border-radius: var(--ka-tr-radius-sm);
                        font-size: 12px;
                        line-height: 1.45;
                        color: var(--ka-tr-ink-muted);
                        display: -webkit-box;
                        -webkit-line-clamp: 2;
                        -webkit-box-orient: vertical;
                        overflow: hidden;
                    }

                    .king-addons-translator-progress-note {
                        display: none;
                        margin-top: 10px;
                        padding: 10px 12px;
                        background: var(--ka-tr-warning-soft);
                        border: 1px solid var(--ka-tr-warning-border);
                        border-radius: var(--ka-tr-radius-sm);
                        color: var(--ka-tr-warning);
                        font-size: 12px;
                        line-height: 1.5;
                    }

                    /* Stats Styles */
                    .king-addons-translator-stats {
                        margin-top: 16px;
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                        gap: 12px;
                    }

                    /* Shared dialog building blocks */
                    .ka-tr-dialog-head {
                        margin-bottom: 20px;
                    }

                    .ka-tr-dialog-head h3 {
                        margin: 0 0 6px 0;
                    }

                    .ka-tr-dialog-sub {
                        margin: 0;
                        font-size: 13px;
                        color: var(--ka-tr-ink-muted);
                    }

                    /* Says whose feature this is - inside Elementor's editor the
                       dialog otherwise reads as one of Elementor's own. */
                    .ka-tr-byline {
                        margin: -2px 0 12px;
                        font-size: 11px;
                        font-weight: 700;
                        letter-spacing: .08em;
                        text-transform: uppercase;
                        color: var(--ka-tr-accent);
                    }

                    .ka-tr-panel {
                        background: var(--ka-tr-surface-sunken);
                        border: 1px solid var(--ka-tr-border);
                        border-radius: var(--ka-tr-radius-sm);
                        padding: 16px;
                        margin-bottom: 12px;
                    }

                    .ka-tr-panel--accent {
                        background: var(--ka-tr-accent-soft);
                        border-color: rgba(91, 3, 255, 0.18);
                    }

                    .ka-tr-panel--warning {
                        background: var(--ka-tr-warning-soft);
                        border-color: var(--ka-tr-warning-border);
                        color: var(--ka-tr-warning);
                    }

                    .ka-tr-panel h4 {
                        margin: 0 0 10px 0;
                        font-size: 13px;
                        font-weight: 650;
                        color: var(--ka-tr-ink);
                        text-transform: uppercase;
                        letter-spacing: 0.04em;
                    }

                    .ka-tr-panel p {
                        margin: 0 0 12px 0;
                        font-size: 13px;
                        color: var(--ka-tr-ink-muted);
                    }

                    .ka-tr-panel p:last-child {
                        margin-bottom: 0;
                    }

                    .ka-tr-steps {
                        list-style: none;
                        counter-reset: ka-tr-step;
                        margin: 0;
                        padding: 0;
                    }

                    .ka-tr-steps li {
                        counter-increment: ka-tr-step;
                        position: relative;
                        padding-left: 28px;
                        margin: 0 0 10px 0;
                        font-size: 13px;
                        color: var(--ka-tr-ink);
                        line-height: 1.5;
                    }

                    .ka-tr-steps li:last-child {
                        margin-bottom: 0;
                    }

                    .ka-tr-steps li::before {
                        content: counter(ka-tr-step);
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 20px;
                        height: 20px;
                        border-radius: 50%;
                        background: var(--ka-tr-accent);
                        color: #fff;
                        font-size: 11px;
                        font-weight: 650;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }

                    .ka-tr-steps a,
                    .ka-tr-panel a {
                        color: var(--ka-tr-accent);
                        font-weight: 600;
                        text-decoration: none;
                    }

                    .ka-tr-steps a:hover,
                    .ka-tr-panel a:hover {
                        text-decoration: underline;
                    }

                    .ka-tr-rows {
                        display: grid;
                        gap: 8px;
                    }

                    .ka-tr-row {
                        display: flex;
                        justify-content: space-between;
                        gap: 12px;
                        font-size: 13px;
                    }

                    .ka-tr-row span {
                        color: var(--ka-tr-ink-muted);
                    }

                    .ka-tr-row strong {
                        color: var(--ka-tr-ink);
                        font-weight: 600;
                    }

                    .ka-tr-detail {
                        font-size: 12px;
                        color: var(--ka-tr-ink-muted);
                        line-height: 1.5;
                        word-break: break-word;
                        max-height: 120px;
                        overflow-y: auto;
                        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                    }

                    .king-addons-translator-stat {
                        text-align: center;
                        padding: 14px 12px;
                        background: var(--ka-tr-surface-sunken);
                        border: 1px solid var(--ka-tr-border);
                        border-radius: var(--ka-tr-radius-sm);
                    }

                    .king-addons-translator-stat-number {
                        font-size: 26px;
                        font-weight: 650;
                        line-height: 1.1;
                        letter-spacing: -0.02em;
                        color: var(--ka-tr-ink);
                    }

                    .king-addons-translator-stat-label {
                        margin-top: 4px;
                        font-size: 11px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        color: var(--ka-tr-ink-muted);
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
            return;
        }
        
        // Strategy 1: Try to find the left button group in the top toolbar (after initial buttons)
        var $leftButtonGroup = $('#elementor-editor-wrapper-v2 .MuiStack-root.eui-1g5sxhh:first');
        if ($leftButtonGroup.length) {
            return addButtonToElement($leftButtonGroup, 'left-group');
        }
        
        // Strategy 2: Try to find the first stack group in the toolbar
        var $toolbarStack = $('#elementor-editor-wrapper-v2 .MuiStack-root');
        if ($toolbarStack.length) {
            return addButtonToElement($toolbarStack.first(), 'toolbar-stack');
        }
        
        // Strategy 2.5: Try to find grid container with stacks
        var $gridContainer = $('#elementor-editor-wrapper-v2 .MuiGrid-container:first');
        if ($gridContainer.length) {
            var $firstStack = $gridContainer.find('.MuiStack-root:first');
            if ($firstStack.length) {
                return addButtonToElement($firstStack, 'grid-stack');
            }
        }
        
        // Strategy 3: Try to find the toolbar itself
        var $toolbar = $('#elementor-editor-wrapper-v2 .MuiToolbar-root');
        if ($toolbar.length) {
            return addButtonToElement($toolbar, 'toolbar');
        }
        
        // Strategy 4: Try to find the Elementor panel header (fallback)
        var $panelHeader = $('#elementor-panel-header');
        if ($panelHeader.length) {
            return addButtonToElement($panelHeader, 'panel-header');
        }
        
        // Strategy 5: Try to find the main panel (further fallback)
        var $panel = $('#elementor-panel');
        if ($panel.length) {
            return addButtonToElement($panel, 'panel-fallback');
        }
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
                'tabindex="0" type="button" aria-label="AI Page Translate & Transform" title="AI Page Translate & Transform">' +
                '<span class="MuiButton-startIcon MuiButton-iconSizeSmall" style="margin-right: 4px;">' +
                '<img src="' + iconUrl + '" alt="AI" onerror="this.src=\'' + fallbackIconUrl + '\'" style="width: 20px; height: 20px;" />' +
                '</span>' +
                '<span class="MuiStack-root" style="color: white;">AI Page Translate &amp; Transform</span>' +
                '</button>' +
                '</span>');
        } else {
            // Original button style for panel locations
            $translatorBtn = $('<button class="king-addons-ai-translator-btn" title="AI Page Translate & Transform">' +
                '<img src="' + iconUrl + '" alt="" onerror="this.src=\'' + fallbackIconUrl + '\'"/>' +
                '<span>AI Page Translate &amp; Transform</span>' +
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
            
            var saved = loadTranslationProgress();
            if (saved) {
                showResumePopup(saved);
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
        var cfg = window.KingAddonsAiField || {};
        var settingsUrl = cfg.settings_url || '/wp-admin/admin.php?page=king-addons-ai-settings';

        // The setup steps name whichever AI provider is configured.
        var keysUrl = cfg.api_keys_url || 'https://platform.openai.com/api-keys';
        var keysLabel = cfg.api_keys_label || 'OpenAI Platform';
        var billingNote = cfg.setup_billing_note || 'and top up your OpenAI account balance by at least $5';
        var costNote = cfg.setup_cost_note || 'Processing a page costs pennies (about $0.01 per full page).';

        function esc(value) {
            return $('<div></div>').text(String(value == null ? '' : value)).html();
        }
        
        var $overlay = $('<div class="king-addons-translator-overlay"></div>');
        var $popup = $('<div class="king-addons-translator-popup"></div>');
        
        var errorHtml = `
            <div class="ka-tr-dialog-head">
                <h3>${esc(title || 'AI Page Translate & Transform')}</h3>
                <div class="ka-tr-byline">by King Addons</div>
                <p class="ka-tr-dialog-sub">Connect an AI provider once and the feature is ready to use.</p>
            </div>

            <div class="ka-tr-panel">
                <h4>What you need to do</h4>
                <ol class="ka-tr-steps">
                    <li>Get an API key from <a href="${esc(keysUrl)}" target="_blank" rel="noopener noreferrer">${esc(keysLabel)}</a> ${esc(billingNote)}</li>
                    <li>Paste it into AI Settings</li>
                    <li>Come back here and translate the page</li>
                </ol>
            </div>

            ${message ? `<div class="ka-tr-panel"><h4>Details</h4><div class="ka-tr-detail">${esc(message)}</div></div>` : ''}

            <div class="ka-tr-panel">
                <h4>What it costs</h4>
                <p>${esc(costNote)}</p>
            </div>

            <div class="king-addons-translator-actions">
                <button class="king-addons-translator-btn-secondary" id="king-addons-error-close">Not now</button>
                <a href="${esc(settingsUrl)}" class="king-addons-translator-btn-primary" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">Go to AI Settings</a>
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
    function showTokenLimitError(message, errorCode) {
        // Remove any existing popups first
        $('.king-addons-translator-overlay').remove();
        $('.king-addons-translator-popup').remove();
        
        // Wait a bit to ensure cleanup is complete
        setTimeout(function() {
            showTokenLimitErrorDelayed(message, errorCode);
        }, 100);
    }
    
    /**
     * A run can be stopped by four different limits, and they need four
     * different answers: the plugin's own token cap, the provider's short-term
     * throttling, a per-model daily cap, and an empty account balance. Showing
     * "increase your Daily Token Limit" for all of them sends people to a
     * setting that has nothing to do with the failure.
     */
    function showTokenLimitErrorDelayed(message, errorCode) {
        var cfg = window.KingAddonsAiField || {};
        var settingsUrl = cfg.settings_url || '/wp-admin/admin.php?page=king-addons-ai-settings';
        var providerLabel = cfg.provider_label || 'the AI provider';
        var isOpenRouter = cfg.provider === 'openrouter';

        function esc(value) {
            return $('<div></div>').text(String(value == null ? '' : value)).html();
        }

        var variants = {
            local_limit: {
                title: 'Daily token limit reached',
                subtitle: 'Your own safety limit stopped the translation.',
                heading: 'What happened',
                body: 'King Addons has a <strong>"Daily Token Limit"</strong> setting that prevents accidental '
                    + 'overspending, and this page hit it. Nothing is wrong with your ' + esc(providerLabel) + ' account.',
                steps: [
                    '<strong>Increase the "Daily Token Limit"</strong> in AI Settings (recommended)',
                    'Or wait until tomorrow &mdash; the limit resets automatically'
                ],
                tip: 'Go to <strong>AI Settings → Daily Token Limit</strong> and set a higher number. '
                    + 'For regular use, try <strong>50,000 or 100,000 tokens</strong>.'
            },
            rate_limit: {
                title: 'Model rate limit reached',
                subtitle: esc(providerLabel) + ' is throttling requests for the selected model.',
                heading: 'What happened',
                body: 'The model was asked for translations faster than the provider allows, and it kept '
                    + 'refusing after several retries. This is a temporary limit, not a problem with your account.',
                steps: [
                    'Wait a minute and resume &mdash; the limit clears on its own',
                    'Or pick a less busy model in AI Settings'
                ].concat(isOpenRouter ? ['Free models share a pool with other users; a paid model has far higher limits'] : []),
                tip: 'Your progress was saved. Reopen the AI Translator and choose <strong>Resume</strong> '
                    + 'to continue from where it stopped.'
            },
            daily_limit: {
                title: 'Daily model limit reached',
                subtitle: esc(providerLabel) + ' has capped this model for today.',
                heading: 'What happened',
                body: 'The selected model has a daily request cap and it has been used up. Waiting a few '
                    + 'seconds will not help &mdash; the cap resets on the provider\'s schedule.',
                steps: [
                    'Switch to a different model in AI Settings',
                    'Or come back after the cap resets'
                ].concat(isOpenRouter ? ['Free models have daily caps that credits do not lift; a paid model avoids them'] : []),
                tip: 'Your progress was saved. Reopen the AI Translator and choose <strong>Resume</strong> '
                    + 'to continue from where it stopped.'
            },
            credits: {
                title: 'Out of credits',
                subtitle: 'Your ' + esc(providerLabel) + ' account has no balance left.',
                heading: 'What happened',
                body: esc(providerLabel) + ' rejected the request because the account balance is empty. '
                    + 'The plugin and your API key are fine.',
                steps: isOpenRouter
                    ? ['Add credit at <a href="https://openrouter.ai/settings/credits" target="_blank" rel="noopener noreferrer" style="color:#5B03FF;">openrouter.ai/settings/credits</a>',
                       'Or switch to a free model in AI Settings']
                    : ['Top up your account balance in the provider dashboard',
                       'Then run the translation again'],
                tip: 'Your progress was saved. Reopen the AI Translator and choose <strong>Resume</strong> '
                    + 'to continue from where it stopped.'
            }
        };

        var variant = variants[errorCode] || variants.local_limit;

        var $overlay = $('<div class="king-addons-translator-overlay"></div>');
        var $popup = $('<div class="king-addons-translator-popup"></div>');

        var stepsHtml = variant.steps.map(function(step) {
            return '<li>' + step + '</li>';
        }).join('');

        // The provider's own wording is the most precise explanation there is,
        // so it is shown verbatim rather than paraphrased away.
        var detailHtml = message ? `
            <div class="ka-tr-panel">
                <h4>Provider response</h4>
                <div class="ka-tr-detail">${esc(message)}</div>
            </div>` : '';

        $popup.html(`
            <div class="ka-tr-dialog-head">
                <h3>${variant.title}</h3>
                <p class="ka-tr-dialog-sub">${variant.subtitle}</p>
            </div>

            <div class="ka-tr-panel">
                <h4>${variant.heading}</h4>
                <p>${variant.body}</p>
            </div>

            <div class="ka-tr-panel ka-tr-panel--accent">
                <h4>What to do</h4>
                <ol class="ka-tr-steps">${stepsHtml}</ol>
            </div>

            ${detailHtml}

            <div class="ka-tr-panel ka-tr-panel--warning">
                <p>${variant.tip}</p>
            </div>

            <div class="king-addons-translator-actions">
                <button class="king-addons-translator-btn-secondary" id="king-addons-limit-close">I understand</button>
                <a href="${esc(settingsUrl)}" class="king-addons-translator-btn-primary" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">Go to AI Settings</a>
            </div>
        `);

        $overlay.append($popup);
        $('body').append($overlay);

        $('#king-addons-limit-close').on('click', function() {
            $overlay.remove();
        });

        $overlay.on('click', function(e) {
            if (e.target === $overlay[0]) {
                $overlay.remove();
            }
        });
    }

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
            return;
        }

        translationState.isCancelled = true;
        translationState.isTranslating = false;
        
        // Cancel all active AJAX requests
        if (translationState.currentRequests.length > 0) {
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
    /**
     * Human readable name for a language code or a custom prompt.
     */
    function describeLanguage(code) {
        return languages[code] || code || 'the target language';
    }

    /**
     * Offer to continue an interrupted run instead of starting over.
     *
     * Elementor keeps translated content as unsaved changes, so a reload only
     * preserves it once the document has been saved or autosaved - the prompt
     * says so rather than pretending otherwise.
     */
    function showResumePopup(saved) {
        $('.king-addons-translator-overlay').remove();

        var $overlay = $('<div class="king-addons-translator-overlay"></div>');
        var $popup = $('<div class="king-addons-translator-popup"></div>');

        var remaining = Math.max(0, saved.total - saved.done.length);

        function esc(value) {
            return $('<div></div>').text(String(value == null ? '' : value)).html();
        }

        // Mirrors the main popup's skeleton (h3, a subtitle sibling, and a
        // .king-addons-translator-form body) so showProgressInPopup() can take
        // it over once the run starts.
        $popup.html(`
            <h3>
                <img src="${KingAddonsAiField.plugin_url}includes/admin/img/ai.svg" style="width:20px;height:20px;filter: invert(1);" alt=""/>
                Resume this run?
            </h3>
            <div class="ka-tr-byline">by King Addons</div>
            <div class="ka-tr-dialog-sub" style="margin-bottom: 16px;">
                A run on this page was interrupted.
            </div>
            <div class="king-addons-translator-form">
                <div class="ka-tr-panel">
                    <div class="ka-tr-rows">
                        <div class="ka-tr-row"><span>Progress</span><strong>${esc(saved.done.length)} / ${esc(saved.total)} elements</strong></div>
                        <div class="ka-tr-row"><span>Remaining</span><strong>${esc(remaining)} elements</strong></div>
                        <div class="ka-tr-row"><span>Translating into</span><strong>${esc(describeLanguage(saved.toLang))}</strong></div>
                    </div>
                </div>

                <div class="ka-tr-panel ka-tr-panel--warning">
                    <p>
                        Resuming skips the elements that were already done. If the page was reloaded
                        without saving, those elements kept their original text &mdash; choose
                        <strong>Start over</strong> to translate the whole page again.
                    </p>
                </div>

                <div class="king-addons-translator-actions">
                    <button class="king-addons-translator-btn-secondary" id="king-addons-resume-discard">Start over</button>
                    <button class="king-addons-translator-btn-primary" id="king-addons-resume-continue">Resume</button>
                </div>
            </div>
        `);

        $overlay.append($popup);
        $('body').append($overlay);

        // Hand the same popup to the normal flow, which swaps its body for the
        // progress UI and animates it into the corner.
        $('#king-addons-resume-continue').on('click', function() {
            startTranslation(saved.fromLang || 'auto', saved.toLang, $popup, $overlay, saved);
        });

        $('#king-addons-resume-discard').on('click', function() {
            clearTranslationProgress();
            $overlay.remove();
            createAndShowPopup();
        });

        $overlay.on('click', function(e) {
            if (e.target === $overlay[0]) {
                $overlay.remove();
            }
        });
    }

    /**
     * Small banner shown after the editor loads when a run can be continued.
     */
    function offerResumeOnLoad() {
        if (translationState.isTranslating || $('#king-addons-translator-resume-banner').length) {
            return;
        }

        var saved = loadTranslationProgress();
        if (!saved) {
            return;
        }

        var $banner = $(`
            <div id="king-addons-translator-resume-banner" style="position: fixed; bottom: 20px; right: 20px; z-index: 999998; max-width: 320px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); padding: 16px; font-size: 13px; color: #2d3748;">
                <div style="font-weight: 600; margin-bottom: 6px;">Unfinished run</div>
                <div style="color: #718096; line-height: 1.5; margin-bottom: 12px;">
                    ${saved.done.length} of ${saved.total} elements were processed into ${$('<div></div>').text(describeLanguage(saved.toLang)).html()}.
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" id="king-addons-resume-banner-dismiss" style="flex: 1; border: 1px solid #e2e8f0; background: #f7fafc; color: #4a5568; border-radius: 6px; padding: 7px 10px; cursor: pointer;">Later</button>
                    <button type="button" id="king-addons-resume-banner-open" style="flex: 1; border: none; background: #5B03FF; color: #fff; border-radius: 6px; padding: 7px 10px; cursor: pointer;">Resume</button>
                </div>
            </div>
        `);

        $('body').append($banner);

        $('#king-addons-resume-banner-open').on('click', function() {
            $banner.remove();
            var current = loadTranslationProgress();
            if (current) {
                showResumePopup(current);
            }
        });

        // "Later" only hides the banner; the saved progress stays available
        // from the AI Translator button.
        $('#king-addons-resume-banner-dismiss').on('click', function() {
            $banner.remove();
        });
    }

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
            '<div class="king-addons-pro-info">💎 <a href="' + upgradeUrl + '" target="_blank" style="color: #5B03FF; text-decoration: none;">Upgrade to King Addons PRO</a> to use custom languages, regional dialects and custom translation prompts (formal tone, technical style, etc.)!</div>';
        
        var popupContent = `
            <h3>
                <img src="${KingAddonsAiField.plugin_url}includes/admin/img/ai.svg" style="width:20px;height:20px;filter: invert(1);" alt=""/>
                AI Page Translate &amp; Transform
            </h3>
            <div class="ka-tr-byline">by King Addons</div>
            <div class="ka-tr-dialog-sub" style="margin-bottom: 20px;">
                Translate to any language, or transform the text style (formal, casual, technical).
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
    function startTranslation(fromLang, toLang, $popup, $overlay, resumeFrom) {
        translationState.isTranslating = true;
        translationState.isCancelled = false; // Reset cancellation flag
        translationState.currentRequests = []; // Clear any previous requests
        translationState.fromLang = fromLang;
        translationState.toLang = toLang;
        translationState.translatedElements = 0;
        translationState.failedElements = 0;
        translationState.doneElementIds = [];
        translationState.failedElementIds = [];
        translationState.lastErrorMessage = '';
        translationState.consecutiveFailures = 0;
        
        // Inject animation styles into preview iframe immediately
        injectPreviewStyles();
        
        // Disable the AI Translator button
        toggleTranslatorButton(true);
        
        // Get all translatable elements
        var elements = getTranslatableElements();
        
        if (elements.length === 0) {
            alert('No translatable text elements found on this page.');
            translationState.isTranslating = false;
            toggleTranslatorButton(false);
            clearTranslationProgress();
            return;
        }
        
        // Resuming: keep the elements already handled out of this run, but keep
        // counting them so the progress bar reflects the whole page.
        if (resumeFrom && Array.isArray(resumeFrom.done) && resumeFrom.done.length) {
            var alreadyDone = resumeFrom.done;
            var remaining = elements.filter(function(element) {
                return alreadyDone.indexOf(element.elementId) === -1;
            });

            // Every element accounted for means there is nothing left to do.
            if (!remaining.length) {
                translationState.isTranslating = false;
                toggleTranslatorButton(false);
                clearTranslationProgress();
                alert('This page has already been translated.');
                return;
            }

            translationState.doneElementIds = alreadyDone.slice();
            translationState.failedElementIds = Array.isArray(resumeFrom.failed) ? resumeFrom.failed.slice() : [];
            translationState.translatedElements = alreadyDone.length;
            translationState.failedElements = translationState.failedElementIds.length;
            translationState.resumedCount = alreadyDone.length;
            elements = remaining;
        } else {
            translationState.resumedCount = 0;
        }

        translationState.totalElements = elements.length + translationState.doneElementIds.length;
        saveTranslationProgress();
        
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
            // console.warn('🚨 Unable to find container children using any known API');
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
                    return; // Exit early for blacklisted widgets
                }
                
                var settings = model.get('settings').attributes;
                
                // Now check for text fields in remaining widgets
                var textFields = getTextFieldsForWidget(widgetType, settings, container);
                
                // If we found text fields, process the widget
                if (textFields.length > 0) {
                    elements.push({
                        container: container,
                        widgetType: widgetType,
                        textFields: textFields,
                        elementId: model.get('id')
                    });
                    return;
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
        var textFields = [];
        
        // Try to get widget controls schema from Elementor
        var controls = getWidgetControls(widgetType, container);
        
        if (controls && Object.keys(controls).length > 0) {
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
                            textFields.push({
                                field: controlName,
                                value: settingValue,
                                type: controlType === 'wysiwyg' ? 'wysiwyg' : 'text'
                            });
                        }
                    }
                }
                
                // Also check for repeater controls
                if (controlType === 'repeater' && settingValue) {
                    checkRepeaterFieldsByType(controlName, control, settingValue, textFields);
                }
            });
        } else {
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
                    return model.config.controls;
                }
                
                // Try to get controls from the container settings
                if (container.settings && container.settings.controls) {
                    return container.settings.controls;
                }
            }
            
            // Method 2: Try to get controls from Elementor widgets registry
            if (window.elementor && elementor.widgets) {
                var widgetConfig = elementor.widgets.getWidgetType(widgetType);
                if (widgetConfig && widgetConfig.controls) {
                    return widgetConfig.controls;
                }
            }
            
            // Method 3: Try to get controls from elements manager
            if (window.elementor && elementor.elementsManager) {
                var elementView = elementor.elementsManager.getElementView(container.model.get('id'));
                if (elementView && elementView.model && elementView.model.controls) {
                    return elementView.model.controls;
                }
            }

            return null;
            
        } catch (error) {
            // console.warn('⚠️ Error getting widget controls:', error);
            return null;
        }
    }
    
    /**
     * Check repeater fields using control type information
     */
    function checkRepeaterFieldsByType(repeaterName, repeaterControl, repeaterData, textFields) {
        try {
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
            
            if (textFieldNames.length === 0) {
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
            // console.warn('⚠️ Error processing repeater by type:', error);
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

        for (const [repeaterKey, fieldNames] of Object.entries(repeaterConfigs)) {
            if (settings[repeaterKey]) {
                let repeaterData = settings[repeaterKey];
                
                // Handle Backbone Collections (common in King Addons and some Elementor widgets)
                if (repeaterData && typeof repeaterData === 'object' && repeaterData.models) {
                    // Extract models from Backbone collection
                    const models = repeaterData.models || [];
                    for (let i = 0; i < models.length; i++) {
                        const model = models[i];
                        const modelData = model.attributes || model.toJSON();

                        for (const fieldName of fieldNames) {
                            if (modelData[fieldName] && typeof modelData[fieldName] === 'string' && modelData[fieldName].trim()) {
                                const fieldKey = `${repeaterKey}[${i}][${fieldName}]`;
                                const fieldValue = modelData[fieldName];

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
                    for (let i = 0; i < repeaterData.length; i++) {
                        const item = repeaterData[i];
                        for (const fieldName of fieldNames) {
                            if (item[fieldName] && typeof item[fieldName] === 'string' && item[fieldName].trim()) {
                                const fieldKey = `${repeaterKey}[${i}][${fieldName}]`;
                                const fieldValue = item[fieldName];

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
                    const keys = Object.keys(repeaterData).filter(key => /^\d+$/.test(key));

                    for (const key of keys) {
                        const item = repeaterData[key];
                        for (const fieldName of fieldNames) {
                            if (item[fieldName] && typeof item[fieldName] === 'string' && item[fieldName].trim()) {
                                const fieldKey = `${repeaterKey}[${key}][${fieldName}]`;
                                const fieldValue = item[fieldName];

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
            <img src="${KingAddonsAiField.plugin_url}includes/admin/img/ai.svg" style="width:20px;height:20px;filter: invert(1);"/>
            AI is working on your page
            <button class="king-addons-translator-close-btn" title="Close">×</button>
        `;
        
        var progressHtml = `
            <div class="king-addons-translator-progress">
                <div class="king-addons-translator-progress-text">
                    Processing page elements&hellip; <span id="king-addons-progress-count">0 / ${translationState.totalElements}</span>
                </div>
                <div class="king-addons-translator-progress-bar">
                    <div class="king-addons-translator-progress-fill" id="king-addons-progress-fill"></div>
                </div>
                <div class="ka-tr-activity">
                    <span class="ka-tr-spinner" aria-hidden="true"></span>
                    <span class="king-addons-translator-current-element" id="king-addons-current-element">Preparing…</span>
                </div>
                <div class="ka-tr-snippet" id="king-addons-progress-snippet" hidden></div>
                <div class="king-addons-translator-progress-note" id="king-addons-progress-note"></div>
            </div>
        `;
        
        // Update header 
        $popup.find('h3').html(headerHtml);
        
        // Hide the description and the byline while the run is in progress.
        $popup.find('.ka-tr-dialog-sub, .ka-tr-byline').hide();
        
        $popup.find('.king-addons-translator-form').html(progressHtml);
        
        // Note: Close button events are handled by global document handler to prevent duplicates
    }

    /**
     * Translate elements sequentially
     */
    function translateElementsSequentially(elements, index, $popup) {
        // Check if translation was cancelled
        if (translationState.isCancelled) {
            return;
        }
        
        if (index >= elements.length) {
            showTranslationComplete($popup);
            return;
        }
        
        var element = elements[index];
        translationState.currentElement = element;
        
        // Update progress UI
        updateProgressUI(translationState.doneElementIds.length + 1, element);
        
        // Highlight current element in preview
        highlightElementInPreview(element.elementId, true);
        
        // Translate all text fields for this element
        translateElementFields(element, function(success) {
            // Remove highlight
            highlightElementInPreview(element.elementId, false);
            
            // A fatal error stops the run from inside the request handler; do
            // not record the element or schedule the next one.
            if (translationState.isCancelled) {
                return;
            }
            
            if (success) {
                translationState.translatedElements++;
                translationState.consecutiveFailures = 0;
                showElementSuccess(element.elementId);
            } else {
                translationState.failedElements++;
                translationState.consecutiveFailures++;
                translationState.failedElementIds.push(element.elementId);
            }
            
            // Either way the element is behind us, so a resume skips it.
            translationState.doneElementIds.push(element.elementId);
            saveTranslationProgress();
            
            // A long run of failures means the provider or model is unusable;
            // stop rather than working through the rest of the page for nothing.
            if (translationState.consecutiveFailures >= MAX_CONSECUTIVE_ELEMENT_FAILURES) {
                handleFatalTranslationError({
                    code: 'unknown',
                    message: (translationState.lastErrorMessage || 'Several elements failed in a row.')
                        + '\n\nTranslation stopped after '
                        + MAX_CONSECUTIVE_ELEMENT_FAILURES
                        + ' consecutive failures. You can resume it later from where it stopped.'
                });
                return;
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
            setActivity(element, completedFields + 1, fieldsToTranslate.length, field.value);
            translateSingleField(field.value, function(translatedText, success) {
                // Check if translation was cancelled while waiting for response
                if (translationState.isCancelled) {
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
    // How many times a temporary failure is retried before giving up on a field,
    // and how long to wait before each retry.
    var RETRY_DELAYS = [2000, 5000, 12000];

    // A model that is throttled or down fails every field, so the run stops
    // rather than grinding through the whole page collecting failures.
    var MAX_CONSECUTIVE_ELEMENT_FAILURES = 5;

    /**
     * Normalise a failed translation request into { code, message, retryable }.
     *
     * The server classifies provider failures and answers with a meaningful
     * status, but requests can also fail before reaching it (offline, proxy,
     * PHP fatal), so the status code is used as a fallback.
     */
    function parseTranslationError(response, xhr) {
        var data = null;

        if (response && typeof response === 'object' && response.data) {
            data = response.data;
        } else if (xhr && xhr.responseJSON && xhr.responseJSON.data) {
            data = xhr.responseJSON.data;
        } else if (xhr && xhr.responseText) {
            try {
                var parsed = JSON.parse(xhr.responseText);
                data = parsed && parsed.data;
            } catch (e) {
                // Not JSON - fall back to the status code below.
            }
        }

        // No xhr means the HTTP call itself succeeded and the body carried the
        // failure, so it must not be mistaken for a lost connection.
        var status = xhr && typeof xhr.status === 'number' ? xhr.status : (response ? 200 : 0);
        var message = '';
        var code = '';
        var retryable = null;

        if (data && typeof data === 'object') {
            message = data.message || '';
            code = data.code || '';
            if (typeof data.retryable === 'boolean') {
                retryable = data.retryable;
            }
        } else if (typeof data === 'string') {
            message = data;
        }

        if (!code) {
            if (status === 200) {
                code = 'unknown';
            } else if (status === 0) {
                code = 'network';
            } else if (status === 401 || status === 403) {
                code = 'auth';
            } else if (status === 402) {
                code = 'credits';
            } else if (status === 400 || status === 404) {
                code = 'model';
            } else if (status === 429) {
                code = 'rate_limit';
            } else if (status >= 500) {
                code = 'upstream';
            } else {
                code = 'unknown';
            }
        }

        if (retryable === null) {
            retryable = (code === 'rate_limit' || code === 'upstream' || code === 'network');
        }

        if (!message) {
            var fallbacks = {
                network: 'Network connection failed. Please check your internet connection.',
                auth: 'The API key is invalid or expired. Please check it in AI Settings.',
                credits: 'The AI provider reports insufficient credits.',
                model: 'The selected model was rejected by the provider. Pick another model in AI Settings.',
                rate_limit: 'Rate limit reached. Please wait a moment and try again.',
                daily_limit: 'The daily limit for this model has been reached.',
                upstream: 'The AI provider is temporarily unavailable. Please try again shortly.'
            };
            message = fallbacks[code] || 'Translation failed.';
        }

        return { code: code, message: message, retryable: retryable, status: status };
    }

    /**
     * Stop the run and explain why, choosing the popup that fits the cause.
     */
    function handleFatalTranslationError(error) {
        // Keep whatever has been translated so far resumable.
        saveTranslationProgress();
        stopTranslationProcess();

        var providerLabel = (window.KingAddonsAiField && KingAddonsAiField.provider_label) || 'AI provider';

        setTimeout(function() {
            if (error.code === 'auth') {
                showApiKeyError('Setup Required', error.message + '\n\nPlease check your API key in AI Settings and try again.');
            } else if (error.code === 'credits' || error.code === 'daily_limit'
                || error.code === 'rate_limit' || error.code === 'local_limit') {
                showTokenLimitError(error.message, error.code);
            } else if (error.code === 'model') {
                showApiKeyError('Model Not Available', error.message);
            } else {
                showApiKeyError('Run Stopped', error.message);
            }
        }, 500);
    }

    /**
     * Show a short-lived note in the progress popup (retry countdown, warnings).
     */
    function setProgressNote(text) {
        var $note = $('#king-addons-progress-note');
        if (!$note.length) {
            return;
        }
        if (text) {
            $note.text(text).show();
        } else {
            $note.text('').hide();
        }
    }

    /**
     * Translate a single text field, retrying temporary provider failures.
     */
    function translateSingleField(text, callback, attempt) {
        attempt = attempt || 0;

        // Check if translation was cancelled before making request
        if (translationState.isCancelled) {
            callback(text, false);
            return;
        }

        var request = $.post(KingAddonsAiField.ajax_url, {
            action: 'king_addons_ai_translate_text',
            nonce: KingAddonsAiField.generate_nonce,
            text: text,
            from_lang: translationState.fromLang,
            to_lang: translationState.toLang
        });

        // Store the request so we can cancel it if needed
        translationState.currentRequests.push(request);

        function releaseRequest() {
            var index = translationState.currentRequests.indexOf(request);
            if (index > -1) {
                translationState.currentRequests.splice(index, 1);
            }
        }

        function onFailure(error) {
            if (translationState.isCancelled) {
                callback(text, false);
                return;
            }

            // Temporary problem: wait and try the same field again.
            if (error.retryable && attempt < RETRY_DELAYS.length) {
                var delay = RETRY_DELAYS[attempt];
                setProgressNote('⏳ ' + error.message + ' Retrying in ' + Math.round(delay / 1000) + 's…');

                setTimeout(function() {
                    if (translationState.isCancelled) {
                        callback(text, false);
                        return;
                    }
                    setProgressNote('');
                    translateSingleField(text, callback, attempt + 1);
                }, delay);
                return;
            }

            setProgressNote('');

            // A dead end (bad key, no credit, unusable model, daily cap) will
            // fail every remaining field, so stop instead of burning the page.
            var fatalCodes = ['auth', 'credits', 'model', 'daily_limit', 'rate_limit', 'local_limit'];
            if (fatalCodes.indexOf(error.code) > -1) {
                handleFatalTranslationError(error);
                return;
            }

            // Anything else: give up on this field and let the run continue.
            translationState.lastErrorMessage = error.message;
            callback(text, false);
        }

        request.done(function(response) {
            releaseRequest();

            if (translationState.isCancelled) {
                callback(text, false);
                return;
            }

            if (response && response.success && response.data && response.data.translated_text) {
                setProgressNote('');
                callback(response.data.translated_text, true);
                return;
            }

            onFailure(parseTranslationError(response, null));
        }).fail(function(xhr, textStatus) {
            releaseRequest();

            // An aborted request is a cancellation, not a provider failure.
            if (translationState.isCancelled || textStatus === 'abort') {
                return;
            }

            onFailure(parseTranslationError(null, xhr));
        });
    }

    /**
     * Update element settings with translated text
     */
    function updateElementSettings(container, translatedFields) {
        try {
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
                    
                    if (!repeaterUpdates[repeaterKey]) {
                        repeaterUpdates[repeaterKey] = {};
                    }
                    if (!repeaterUpdates[repeaterKey][itemIndex]) {
                        repeaterUpdates[repeaterKey][itemIndex] = {};
                    }
                    repeaterUpdates[repeaterKey][itemIndex][itemField] = translatedValue;
                } else {
                    // Regular field
                    regularFields[fieldKey] = translatedValue;
                }
            });
            
            // Apply regular field updates
            if (Object.keys(regularFields).length > 0) {
                $e.run('document/elements/settings', {
                    container: container,
                    settings: regularFields
                });
            }
            
            // Apply repeater field updates
            Object.keys(repeaterUpdates).forEach(function(repeaterKey) {
                var currentSettings = container.settings.get(repeaterKey);
                
                // Handle Backbone Collections (King Addons and some Elementor widgets)
                if (currentSettings && typeof currentSettings.models !== 'undefined') {
                    // Work with Backbone collection
                    Object.keys(repeaterUpdates[repeaterKey]).forEach(function(itemIndex) {
                        var index = parseInt(itemIndex);
                        if (currentSettings.models[index]) {
                            var model = currentSettings.models[index];
                            
                            // Update the specific fields in this repeater item
                            Object.keys(repeaterUpdates[repeaterKey][itemIndex]).forEach(function(fieldName) {
                                var oldValue = model.get(fieldName);
                                var newValue = repeaterUpdates[repeaterKey][itemIndex][fieldName];

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

                        $e.run('document/elements/settings', {
                            container: container,
                            settings: repeaterSettings
                        });
                    } catch (e) {
                        // console.warn('⚠️ Error updating via Elementor API, trying alternative method:', e);
                        
                        // Fallback: Try to manually trigger save without change events
                        try {
                            if (typeof container.saveSettings === 'function') {
                                container.saveSettings();
                            }
                        } catch (e2) {
                            // console.warn('⚠️ Fallback method also failed:', e2);
                        }
                    }
                }
                // Handle regular arrays (standard Elementor repeaters)
                else if (Array.isArray(currentSettings)) {
                    var updatedRepeater = currentSettings.slice(); // Clone array
                    
                    Object.keys(repeaterUpdates[repeaterKey]).forEach(function(itemIndex) {
                        var index = parseInt(itemIndex);
                        if (updatedRepeater[index]) {
                            // Update the specific fields in this repeater item
                            Object.keys(repeaterUpdates[repeaterKey][itemIndex]).forEach(function(fieldName) {
                                var oldValue = updatedRepeater[index][fieldName];
                                var newValue = repeaterUpdates[repeaterKey][itemIndex][fieldName];
                                updatedRepeater[index][fieldName] = newValue;
                            });
                        }
                    });
                    
                    // Update the entire repeater field
                    var repeaterSettings = {};
                    repeaterSettings[repeaterKey] = updatedRepeater;

                    $e.run('document/elements/settings', {
                        container: container,
                        settings: repeaterSettings
                    });
                }
                // Handle objects with numbered keys
                else if (currentSettings && typeof currentSettings === 'object') {
                    var updatedObject = Object.assign({}, currentSettings); // Clone object
                    
                    Object.keys(repeaterUpdates[repeaterKey]).forEach(function(itemIndex) {
                        if (updatedObject[itemIndex]) {
                            // Update the specific fields in this repeater item
                            Object.keys(repeaterUpdates[repeaterKey][itemIndex]).forEach(function(fieldName) {
                                var oldValue = updatedObject[itemIndex][fieldName];
                                var newValue = repeaterUpdates[repeaterKey][itemIndex][fieldName];
                                updatedObject[itemIndex][fieldName] = newValue;
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
                } else {
                }
            });
            
        } catch (error) {
            // console.error('❌ Error updating element settings:', error);
            // console.error('Error details:', {
                // message: error.message,
                // stack: error.stack,
                // translatedFields: translatedFields,
                // widgetType: container.model.get('widgetType')
            // });
        }
    }

    /**
     * Update progress UI
     */
    function updateProgressUI(current, element) {
        var percentage = (current / translationState.totalElements) * 100;
        
        $('#king-addons-progress-count').text(current + ' / ' + translationState.totalElements);
        $('#king-addons-progress-fill').css('width', percentage + '%');
        setActivity(element, 0, element.textFields.length, '');
    }

    /**
     * Describe what the translator is working on right now.
     *
     * @param {Object} element    Element being processed.
     * @param {number} fieldIndex 1-based field position, 0 while starting out.
     * @param {number} fieldTotal Number of fields on the element.
     * @param {string} text       Source text of the current field.
     */
    function setActivity(element, fieldIndex, fieldTotal, text) {
        var label = element ? element.widgetType : '';
        if (fieldTotal > 1 && fieldIndex > 0) {
            label += ' — field ' + fieldIndex + ' of ' + fieldTotal;
        }
        if (translationState.toLang) {
            label += ' → ' + describeLanguage(translationState.toLang);
        }

        $('#king-addons-current-element').text(label);

        // Showing the actual string makes a long run legible: you can see it
        // move rather than watching a counter that only ticks per element.
        var $snippet = $('#king-addons-progress-snippet');
        var plain = $('<div></div>').html(String(text || '')).text().replace(/\s+/g, ' ').trim();
        if (plain) {
            $snippet.text(plain.length > 160 ? plain.slice(0, 160) + '…' : plain).prop('hidden', false);
        } else {
            $snippet.text('').prop('hidden', true);
        }
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
            return;
        }
        
        var $previewDoc = elementor.$preview.contents();
        var $previewElement = $previewDoc.find('[data-id="' + elementId + '"]');
        
        if ($previewElement.length === 0) {
            return;
        }
        
        if (highlight) {
            // Remove any existing classes first
            $previewElement.removeClass('king-addons-translated-element');
            $previewElement.addClass('king-addons-translating-element');
            scrollPreviewToElement($previewElement);
        } else {
            $previewElement.removeClass('king-addons-translating-element');
        }
    }

    /**
     * Bring the element being translated into view inside the preview.
     *
     * A long page otherwise translates itself off screen, so the highlight and
     * the success animation are never actually seen.
     *
     * The preview iframe is not scrolled by plain window.scrollTo - Elementor
     * drives it itself - so its own helper is used, the same one the Navigator
     * uses to jump to a widget. It already skips elements that are in view and
     * animates the rest.
     *
     * @param {jQuery} $element Element inside the preview document.
     */
    function scrollPreviewToElement($element) {
        if (!$element || !$element.length) {
            return;
        }

        try {
            if (elementor.helpers && typeof elementor.helpers.scrollToView === 'function') {
                // Second argument is the delay before scrolling; the default
                // half second would lag behind a fast run.
                elementor.helpers.scrollToView($element, 0);
                return;
            }
        } catch (e) {
            // Fall through to the native path below.
        }

        try {
            $element[0].scrollIntoView({
                behavior: prefersReducedMotion() ? 'auto' : 'smooth',
                block: 'center'
            });
        } catch (e) {
            // A torn-down preview must not break the run.
        }
    }

    /**
     * Whether the viewer asked for less animation.
     */
    function prefersReducedMotion() {
        try {
            return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        } catch (e) {
            return false;
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
            return;
        }
        
        var $previewDoc = elementor.$preview.contents();
        var $previewElement = $previewDoc.find('[data-id="' + elementId + '"]');
        
        if ($previewElement.length === 0) {
            return;
        }
        
        // Remove translating class and add translated class
        $previewElement.removeClass('king-addons-translating-element');
        $previewElement.addClass('king-addons-translated-element');
        
        // Remove the success animation after it completes
        setTimeout(function() {
            $previewElement.removeClass('king-addons-translated-element');
        }, 1200);
    }

    /**
     * Show translation complete with stats (stays open until manually closed)
     */
    function showTranslationComplete($popup) {
        translationState.isTranslating = false;

        var failedIds = translationState.failedElementIds.slice();

        // Elements that failed are worth another attempt - a model can refuse
        // one string and handle it fine on a retry. Keeping a progress entry
        // that marks everything except the failures as done turns the normal
        // Resume path into "retry just the ones that failed".
        if (failedIds.length) {
            translationState.doneElementIds = translationState.doneElementIds.filter(function(id) {
                return failedIds.indexOf(id) === -1;
            });
            saveTranslationProgress();
        } else {
            // The page is done, so there is nothing left to resume.
            clearTranslationProgress();
        }

        if (!$popup || $popup.length === 0) {
            // console.error('❌ Cannot show translation results: popup not found');
            return;
        }
        
        var statsHtml = `
            <div class="king-addons-translator-progress">
                <div class="king-addons-translator-stats">
                    <div class="king-addons-translator-stat">
                        <div class="king-addons-translator-stat-number">${translationState.totalElements}</div>
                        <div class="king-addons-translator-stat-label">Elements</div>
                    </div>
                    <div class="king-addons-translator-stat">
                        <div class="king-addons-translator-stat-number" style="color: var(--ka-tr-success);">${translationState.translatedElements}</div>
                        <div class="king-addons-translator-stat-label">Translated</div>
                    </div>
                    <div class="king-addons-translator-stat">
                        <div class="king-addons-translator-stat-number" style="color: ${translationState.failedElements > 0 ? 'var(--ka-tr-danger)' : 'var(--ka-tr-ink-muted)'};">${translationState.failedElements}</div>
                        <div class="king-addons-translator-stat-label">Failed</div>
                    </div>
                </div>
                ${failedIds.length ? `
                <div class="ka-tr-panel ka-tr-panel--warning" style="margin-top: 16px;">
                    <p>${failedIds.length} element${failedIds.length === 1 ? '' : 's'} could not be translated${
                        translationState.lastErrorMessage
                            ? ': ' + $('<div></div>').text(translationState.lastErrorMessage).html()
                            : '.'
                    }</p>
                </div>` : ''}
                <div class="king-addons-translator-actions" style="margin-top: 20px;">
                    <button class="king-addons-translator-btn-secondary" id="king-addons-close-stats">Close</button>
                    ${failedIds.length ? '<button class="king-addons-translator-btn-primary" id="king-addons-retry-failed">Retry failed</button>' : ''}
                </div>
            </div>
        `;
        
        var $form = $popup.find('.king-addons-translator-form');
        
        $form.html(statsHtml);

        if (!failedIds.length) {
            $form.find('#king-addons-close-stats')
                .removeClass('king-addons-translator-btn-secondary')
                .addClass('king-addons-translator-btn-primary');
        }

        $form.find('#king-addons-retry-failed').on('click', function() {
            var saved = loadTranslationProgress();
            $popup.closest('.king-addons-translator-overlay').remove();
            $popup.remove();
            if (saved) {
                showResumePopup(saved);
            }
        });
        
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
        }
        
        // Show temporary notification to attract attention
        var $notificationBanner = $('<div style="position: fixed; top: 0; left: 0; right: 0; background: #10794a; color: #fff; padding: 12px; text-align: center; font-size: 14px; font-weight: 600; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; z-index: 1000000; animation: slideDown 0.4s ease;">Your page is ready &mdash; see the results panel.</div>');
        $('body').append($notificationBanner);
        
        // Remove notification after 5 seconds
        setTimeout(function() {
            $notificationBanner.fadeOut(300, function() {
                $notificationBanner.remove();
            });
        }, 5000);
        
        // Update header to show completion
        var completionHeaderHtml = `
            <img src="${KingAddonsAiField.plugin_url}includes/admin/img/ai.svg" style="width:20px;height:20px;filter: invert(1);"/>
            Your page is ready
        `;
        $popup.find('h3').html(completionHeaderHtml);
        
        // Restore the byline, and rewrite the description for the result - the
        // popup may have started life as the resume prompt, whose subtitle no
        // longer applies.
        $popup.find('.ka-tr-byline').show();
        $popup.find('.ka-tr-dialog-sub')
            .text('Into ' + describeLanguage(translationState.toLang) + '.')
            .show();
        
        // If popup is in compact mode, move it back to center for better visibility
        if ($popup.hasClass('compact')) {
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
                injectPreviewStyles();
            });
        }

        // Monitor for panel changes
        observePanelChanges();

        // Surface an interrupted run once the document is available.
        setTimeout(offerResumeOnLoad, 1500);
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
        }
        
        // Also observe the main editor wrapper for structural changes
        var editorWrapper = document.querySelector('#elementor-editor-wrapper-v2');
        if (editorWrapper) {
            var wrapperObserver = createObserver();
            wrapperObserver.observe(editorWrapper, {
                childList: true,
                subtree: false
            });
        }

        // Observe changes in the main panel (fallback)
        var panel = document.querySelector('#elementor-panel');
        if (panel) {
            var panelObserver = createObserver();
            panelObserver.observe(panel, {
                childList: true,
                subtree: true
            });
        }

        // Observe the main Elementor editor area
        var editorArea = document.querySelector('#elementor-editor-wrapper, .elementor-editor-wrapper');
        if (editorArea) {
            var editorObserver = createObserver();
            editorObserver.observe(editorArea, {
                childList: true,
                subtree: true
            });
        }
    }

    /**
     * Initialize the translator
     */
    function initTranslator() {
        // Wait for Elementor to be fully loaded
        $(window).on('elementor:init', function() {
            // Add small delay to ensure Material UI is rendered
            setTimeout(onElementorInit, 500);
        });

        // Fallback if elementor:init doesn't fire
        setTimeout(function() {
            onElementorInit();
        }, 3000);
        
        // Additional fallback for when Material UI components are ready
        setTimeout(function() {
            if (!document.querySelector('.king-addons-ai-translator-btn')) {
                onElementorInit();
            }
        }, 5000);
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        initTranslator();
        
        // Global event handler for close buttons (backup protection)
        $(document).off('click.aiTranslatorGlobal').on('click.aiTranslatorGlobal', '.king-addons-translator-close-btn', function(e) {
            if (translationState.isTranslating) {
                stopTranslationProcess();
                
                // Show cancellation notice
                var $notice = $('<div style="position: fixed; top: 120px; right: 20px; background: #ff9800; color: white; padding: 8px 12px; border-radius: 4px; font-size: 14px; z-index: 1000000;">Run cancelled</div>');
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
            
            // Reset state and re-enable button. Saved progress is deliberately
            // kept so a cancelled run can be resumed from the same place.
            translationState.isTranslating = false;
            translationState.isCancelled = false; 
            translationState.currentRequests = [];
            toggleTranslatorButton(false);
            
            e.preventDefault();
            e.stopPropagation();
        });
    });

})(jQuery, window.elementor); 