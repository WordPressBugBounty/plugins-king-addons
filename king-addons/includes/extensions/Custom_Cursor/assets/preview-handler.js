/**
 * King Addons Custom Cursor Preview Handler
 * Handles custom cursor initialization in Elementor preview/editor.
 */
(() => {
  'use strict';

  /**
   * Initialize or reinitialize the cursor for preview mode.
   */
  const initCursor = () => {
    const api = window.KingAddonsCustomCursor;
    const data = window.KingAddonsCustomCursorData;
    
    if (!api || !data) {
      return;
    }

    // Force reinitialize in preview mode
    if (data.isPreview) {
      api.reinit(data);
    } else {
      api.init(data);
    }
  };

  /**
   * Boot function to initialize on Elementor events.
   */
  const boot = () => {
    // Small delay to ensure Elementor has fully loaded
    setTimeout(initCursor, 100);
  };

  // Listen for Elementor frontend events
  if (window.elementorFrontend && typeof window.elementorFrontend.on === 'function') {
    window.elementorFrontend.on('components:init', boot);
    window.elementorFrontend.on('frontend:initialized', boot);
  }

  // Also listen for DOMContentLoaded as fallback
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }

  // Listen for Elementor preview reload
  if (window.elementor) {
    window.elementor.on('preview:loaded', boot);
  }
})();
