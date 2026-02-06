/**
 * King Addons Custom Cursor
 * Provides global custom cursor functionality with hover states,
 * magnetic effects, and Elementor integration.
 */
(() => {
  'use strict';

  const globalScope = window;
  const DEFAULT_BODY_CLASS = 'ka-custom-cursor-enabled';

  /**
   * Check if device supports pointer (mouse).
   * Falls back to checking for touch capability.
   * 
   * @returns {boolean} True if device likely has a mouse pointer.
   */
  const hasPointerDevice = () => {
    // Check for pointer:fine media query support
    if (globalScope.matchMedia) {
      const pointerFine = globalScope.matchMedia('(pointer:fine)');
      if (pointerFine.matches) {
        return true;
      }
      // Also check hover capability
      const canHover = globalScope.matchMedia('(hover:hover)');
      if (canHover.matches) {
        return true;
      }
    }
    // Fallback: assume pointer exists if not a pure touch device
    // Check if maxTouchPoints is 0 (no touch) or device has mouse events
    if ('ontouchstart' in globalScope && navigator.maxTouchPoints > 0) {
      // Touch device - check if it also has mouse (like laptops with touchscreen)
      if (globalScope.matchMedia && globalScope.matchMedia('(pointer:coarse)').matches) {
        // Coarse pointer only = likely touch-only device
        return false;
      }
    }
    return true;
  };

  /**
   * Custom Cursor API
   */
  const api = {
    state: null,
    config: null,
    listeners: [],
    raf: null,
    cursor: null,
    inner: null,
    outer: null,
    label: null,
    tailContainer: null,
    initialized: false,

    isLivePreviewMatchedType(type) {
      return ['dot', 'ring', 'dot-ring', 'outline', 'blend'].includes((type || '').toString());
    },

    /**
     * Render core preset visuals to match the Live Preview logic.
     * This ensures Dot/Ring/Dot+Ring/Outline look identical on frontend.
     *
     * @param {Object} options Render overrides.
     * @param {string} [options.fill] Fill color.
     * @param {string} [options.border] Border color.
     * @param {number} [options.scale] Inner scale.
     */
    renderLikePreview(options = {}) {
      if (!this.cursor || !this.inner || !this.outer || !this.config) {
        return;
      }

      const { preset, states } = this.config;
      const type = (preset.type || 'dot').toString();

      if (!this.isLivePreviewMatchedType(type)) {
        // For non-core types, keep the existing CSS-based rendering.
        this.state.lastFill = (options.fill || preset.fill_color || '#111111').toString();
        return;
      }

      const size = parseFloat(this.state.runtimeSize || preset.size || 14) || 14;
      const borderWidth = parseFloat(preset.border_width || 2) || 2;
      const fill = (options.fill || preset.fill_color || '#111111').toString();
      const border = (options.border || preset.border_color || '#111111').toString();
      const opacity = parseFloat(states?.normal?.opacity ?? 1) || 1;
      const scale = parseFloat(options.scale ?? states?.normal?.scale ?? 1) || 1;
      const blur = parseFloat(preset.blur || 0) || 0;

      // Track last fill for ripple.
      this.state.lastFill = fill;

      // Reset styles
      this.inner.style.display = 'none';
      this.outer.style.display = 'none';

      this.inner.style.boxShadow = 'none';
      this.inner.style.background = fill;
      this.inner.style.filter = 'none';
      this.inner.style.borderRadius = '50%';
      this.inner.style.border = '';

      this.outer.style.background = 'transparent';
      this.cursor.style.mixBlendMode = 'normal';

      // IMPORTANT: base CSS uses `inset: 0` for both inner/outer.
      // For preview-matched types we must opt out, otherwise width/height/top/left won't behave.
      this.inner.style.inset = 'auto';
      this.outer.style.inset = 'auto';
      this.inner.style.right = 'auto';
      this.inner.style.bottom = 'auto';
      this.outer.style.right = 'auto';
      this.outer.style.bottom = 'auto';
      this.inner.style.top = '50%';
      this.inner.style.left = '50%';
      this.outer.style.top = '50%';
      this.outer.style.left = '50%';

      // Base inner
      this.inner.style.width = `${size}px`;
      this.inner.style.height = `${size}px`;
      this.inner.style.opacity = `${opacity}`;
      this.inner.style.transform = `translate(-50%, -50%) scale(${scale})`;

      // Base outer (ring)
      const outerSize = size + (borderWidth * 2) + 8;
      this.outer.style.width = `${outerSize}px`;
      this.outer.style.height = `${outerSize}px`;
      this.outer.style.border = `${borderWidth}px solid ${border}`;
      this.outer.style.marginTop = '0';
      this.outer.style.marginLeft = '0';
      this.outer.style.opacity = '1';
      this.outer.style.transform = 'translate(-50%, -50%)';

      // Type-specific
      switch (type) {
        case 'dot':
          this.inner.style.display = 'block';
          break;
        case 'ring':
          this.outer.style.display = 'block';
          break;
        case 'dot-ring':
          this.inner.style.display = 'block';
          this.outer.style.display = 'block';
          break;
        case 'outline':
          this.inner.style.display = 'block';
          this.inner.style.background = 'transparent';
          this.inner.style.border = `${borderWidth}px solid ${fill}`;
          this.inner.style.width = `${Math.max(0, size - borderWidth * 2)}px`;
          this.inner.style.height = `${Math.max(0, size - borderWidth * 2)}px`;
          break;
        case 'blend':
          // Keep existing CSS preset for advanced types; match blend-mode.
          this.cursor.style.mixBlendMode = 'difference';
          this.inner.style.display = 'block';
          this.inner.style.background = '#ffffff';
          this.inner.style.width = `${size * 1.5}px`;
          this.inner.style.height = `${size * 1.5}px`;
          break;
        default:
          // For all other types, fall back to existing CSS preset styles.
          // But still apply blur if configured.
          this.inner.style.display = 'block';
      }

      // Apply blur if set (mimic preview rule)
      if (blur > 0 && !['soft-glow', 'blend'].includes(type)) {
        this.inner.style.filter = `blur(${blur}px)`;
      }
    },

    /**
     * Initialize the custom cursor.
     *
     * @param {Object} config Configuration from PHP.
     */
    init(config = globalScope.KingAddonsCustomCursorData) {
      // Prevent double initialization
      if (this.initialized) {
        return;
      }

      if (!config) {
        console.warn('[KingAddons CustomCursor] No configuration found.');
        return;
      }

      if (!config.enabled) {
        return;
      }

      // Wait for document body
      if (!document.body) {
        document.addEventListener('DOMContentLoaded', () => this.init(config), { once: true });
        return;
      }

      // Check for pointer device (mouse)
      if (!hasPointerDevice()) {
        return;
      }

      // Clean up any previous instance
      this.destroy();
      this.config = config;
      this.initialized = true;

      // Find or create cursor element
      this.cursor = document.getElementById('ka-custom-cursor');
      if (!this.cursor) {
        this.cursor = document.createElement('div');
        this.cursor.id = 'ka-custom-cursor';
        this.cursor.className = 'ka-custom-cursor';
        this.cursor.setAttribute('aria-hidden', 'true');
        this.cursor.innerHTML = `
          <div class="ka-custom-cursor__outer"></div>
          <div class="ka-custom-cursor__inner"></div>
          <div class="ka-custom-cursor__label" data-ka-cursor-label></div>
          <div class="ka-custom-cursor__tail" data-ka-cursor-tail></div>
        `;
        document.body.appendChild(this.cursor);
      }

      // Cache DOM references
      this.inner = this.cursor.querySelector('.ka-custom-cursor__inner');
      this.outer = this.cursor.querySelector('.ka-custom-cursor__outer');
      this.label = this.cursor.querySelector('[data-ka-cursor-label]');
      this.tailContainer = this.cursor.querySelector('[data-ka-cursor-tail]');

      // Initialize state
      this.state = {
        x: globalScope.innerWidth / 2,
        y: globalScope.innerHeight / 2,
        targetX: globalScope.innerWidth / 2,
        targetY: globalScope.innerHeight / 2,
        visible: false,
        multiplier: 1,
        colorOverride: null,
        borderOverride: null,
        activeMagnet: null,
        tailDots: [],
        tailPositions: [],
        currentState: 'normal',
        runtimeSize: null,
      };

      // Apply configuration
      this.applyPreset();
      this.buildTail();
      this.bindEvents();

      // Add body class
      document.body.classList.add(config.bodyClass || DEFAULT_BODY_CLASS);

      // Sync hide-original cursor class (must also remove when disabled)
      if (config.hideOriginalCursor) {
        document.body.classList.add('ka-cursor-hide-original');
      } else {
        document.body.classList.remove('ka-cursor-hide-original');
      }

      // Add preset type class
      this.cursor.classList.add(`ka-custom-cursor--type-${config.preset.type}`);

      // Add blend mode class if needed
      if (config.preset.blend_mode && config.preset.blend_mode !== 'normal') {
        this.cursor.classList.add('ka-custom-cursor--blend');
      }

      // Set initial state and show cursor
      this.setState('normal');
      this.show();
      this.renderLoop();
    },

    /**
     * Destroy current instance and clean up.
     */
    destroy() {
      // Remove event listeners
      if (this.listeners.length) {
        this.listeners.forEach(({ target, type, handler, options }) => {
          target.removeEventListener(type, handler, options);
        });
      }
      this.listeners = [];

      // Cancel animation frame
      if (this.raf) {
        cancelAnimationFrame(this.raf);
        this.raf = null;
      }

      // Reset cursor element
      if (this.cursor) {
        this.cursor.removeAttribute('data-ka-state');
        this.cursor.classList.remove('ka-custom-cursor--hidden');
      }

      this.initialized = false;
    },

    /**
     * Bind all event listeners.
     */
    bindEvents() {
      const add = (target, type, handler, options) => {
        target.addEventListener(type, handler, options);
        this.listeners.push({ target, type, handler, options });
      };

      add(document, 'mousemove', this.handleMove.bind(this), { passive: true });
      add(document, 'mouseenter', this.show.bind(this), { passive: true });
      add(document, 'mouseleave', this.hide.bind(this), { passive: true });
      add(document, 'mousedown', this.handleDown.bind(this), { passive: true });
      add(document, 'mouseup', this.handleUp.bind(this), { passive: true });
      add(document, 'mouseover', this.handleOver.bind(this), { passive: true });
      add(document, 'mouseout', this.handleOut.bind(this), { passive: true });
      add(document, 'scroll', this.handleScroll.bind(this), { passive: true });
    },

    /**
     * Apply preset configuration to cursor element via CSS variables.
     */
    applyPreset() {
      const { preset, states, image } = this.config;
      const setVar = (key, value) => {
        this.cursor.style.setProperty(key, value);
      };

      // Determine cursor size based on preset type
      const hasImageSize = image && image.size;
      const sizeValue = preset.type === 'image' && hasImageSize ? image.size : preset.size;
      this.state.runtimeSize = sizeValue;

      // Apply CSS variables
      setVar('--ka-cursor-size', `${sizeValue}px`);
      setVar('--ka-cursor-border-width', `${preset.border_width}px`);
      setVar('--ka-cursor-fill', preset.fill_color);
      setVar('--ka-cursor-border-color', preset.border_color);
      if (this.isLivePreviewMatchedType(preset.type)) {
        // Live Preview applies opacity/scale to inner only, not the whole cursor.
        setVar('--ka-cursor-opacity', '1');
        setVar('--ka-cursor-scale', '1');
      } else {
        setVar('--ka-cursor-opacity', states.normal.opacity);
        setVar('--ka-cursor-scale', states.normal.scale);
      }
      setVar('--ka-cursor-blur', `${preset.blur}px`);
      setVar('--ka-cursor-mix-blend', preset.blend_mode || 'normal');

      // Apply image settings for image cursor type
      if (preset.type === 'image' && image && image.url) {
        setVar('--ka-cursor-image', `url(${image.url})`);
        setVar('--ka-cursor-image-offset-x', `${image.hotspot_x || 0}px`);
        setVar('--ka-cursor-image-offset-y', `${image.hotspot_y || 0}px`);
      }

      // Render visuals to match admin Live Preview.
      this.renderLikePreview();
    },

    /**
     * Build tail dots for trail effect.
     */
    buildTail() {
      if (!this.tailContainer) {
        return;
      }
      this.tailContainer.innerHTML = '';
      this.state.tailDots = [];
      this.state.tailPositions = [];

      const points = this.config.movement && this.config.movement.tail 
        ? this.config.movement.tail.points || 0 
        : 0;
      
      for (let i = 0; i < points; i++) {
        const dot = document.createElement('span');
        dot.className = 'ka-custom-cursor__tail-dot';
        this.tailContainer.appendChild(dot);
        this.state.tailDots.push(dot);
        this.state.tailPositions.push({ x: this.state.x, y: this.state.y });
      }
    },

    /**
     * Main render loop using requestAnimationFrame.
     */
    renderLoop() {
      // Calculate follow speed based on rendering mode
      const followSpeed = this.config.mode === 'enhanced'
        ? (this.config.movement?.follow_speed || 0.2)
        : 1;

      // Smooth interpolation to target position
      const dx = this.state.targetX - this.state.x;
      const dy = this.state.targetY - this.state.y;
      this.state.x += dx * followSpeed;
      this.state.y += dy * followSpeed;

      // Update cursor position via CSS variables
      const size = this.state.runtimeSize || this.config.preset.size;
      this.cursor.style.setProperty('--ka-cursor-x', `${this.state.x - size / 2}px`);
      this.cursor.style.setProperty('--ka-cursor-y', `${this.state.y - size / 2}px`);

      // Update tail and magnetic effects
      this.updateTail();
      this.updateMagnetic();

      // Continue loop
      this.raf = requestAnimationFrame(this.renderLoop.bind(this));
    },

    /**
     * Update tail dots positions.
     */
    updateTail() {
      if (!this.state.tailDots.length) {
        return;
      }

      // Add current position to the front
      this.state.tailPositions.unshift({ x: this.state.x, y: this.state.y });
      
      // Trim to max length
      const maxPoints = this.state.tailDots.length;
      this.state.tailPositions = this.state.tailPositions.slice(0, maxPoints);

      // Update each tail dot
      this.state.tailDots.forEach((dot, index) => {
        const point = this.state.tailPositions[index] || { x: this.state.x, y: this.state.y };
        const scale = Math.max(0.2, 1 - index / (maxPoints + 2));
        const opacity = Math.max(0.15, 1 - index / (maxPoints + 1));
        dot.style.transform = `translate3d(${point.x}px, ${point.y}px, 0) scale(${scale})`;
        dot.style.opacity = `${opacity}`;
      });
    },

    /**
     * Update magnetic pull effect on active element.
     */
    updateMagnetic() {
      if (!this.state.activeMagnet || !this.config.magnetic?.enabled) {
        return;
      }

      const rect = this.state.activeMagnet.getBoundingClientRect();
      const centerX = rect.left + rect.width / 2;
      const centerY = rect.top + rect.height / 2;
      const deltaX = this.state.x - centerX;
      const deltaY = this.state.y - centerY;
      const distance = Math.hypot(deltaX, deltaY);
      const radius = this.config.magnetic.radius || 140;

      // Reset transform if outside radius
      if (distance > radius) {
        this.state.activeMagnet.style.transform = '';
        return;
      }

      // Get magnetic strength based on behavior attribute
      const behavior = this.state.activeMagnet.getAttribute('data-ka-magnetic');
      let strength = this.config.magnetic.strength || 0.2;
      
      switch (behavior) {
        case 'light':
          strength = 0.2;
          break;
        case 'strong':
          strength = 0.55;
          break;
        case 'follow':
          strength = 0.75;
          break;
      }

      // Calculate pull based on distance
      const pull = (1 - Math.min(distance / radius, 1)) * strength;
      const translateX = deltaX * pull;
      const translateY = deltaY * pull;

      this.state.activeMagnet.style.transform = `translate3d(${translateX}px, ${translateY}px, 0)`;
    },

    /**
     * Handle mouse move event.
     *
     * @param {MouseEvent} event Mouse event.
     */
    handleMove(event) {
      this.state.targetX = event.clientX;
      this.state.targetY = event.clientY;
      if (!this.state.visible) {
        this.show();
      }
    },

    /**
     * Handle mouse down event.
     */
    handleDown(event) {
      this.setState('click');
      if (this.config.states.click.ripple) {
        this.spawnClickRipple(event);
      }
    },

    /**
     * Spawn a click ripple that expands beyond cursor bounds.
     */
    spawnClickRipple(event) {
      try {
        const ripple = document.createElement('div');
        ripple.className = 'ka-custom-cursor__click-ripple';
        const x = event && typeof event.clientX === 'number' ? event.clientX : (parseFloat(this.state.targetX) || 0);
        const y = event && typeof event.clientY === 'number' ? event.clientY : (parseFloat(this.state.targetY) || 0);
        ripple.style.left = `${x - 30}px`;
        ripple.style.top = `${y - 30}px`;
        ripple.style.background = (this.state.lastFill || this.config?.preset?.fill_color || '#111111').toString();
        document.body.appendChild(ripple);
        globalScope.setTimeout(() => ripple.remove(), 650);
      } catch (e) {
        // noop
      }
    },

    /**
     * Handle mouse up event.
     */
    handleUp() {
      this.setState('normal');
    },

    /**
     * Handle mouse over event.
     *
     * @param {MouseEvent} event Mouse event.
     */
    handleOver(event) {
      const target = event.target;
      if (!target) {
        return;
      }

      // Check if target should be excluded
      if (this.isExcluded(target)) {
        this.hide();
        return;
      }

      this.show();

      // Find cursor attribute on target or parent
      const cursorAttr = target.closest(this.config.selectors.attribute);
      const hoverAttr = cursorAttr ? cursorAttr.getAttribute('data-ka-cursor') : null;
      const magneticAttr = target.closest(this.config.selectors.magnetic);

      // Handle magnetic elements
      if (magneticAttr && this.config.magnetic?.enabled) {
        this.state.activeMagnet = magneticAttr;
      }

      // Get overrides from attributes
      const colorOverride = cursorAttr?.getAttribute('data-ka-cursor-color') || target.getAttribute?.('data-ka-cursor-color');
      const sizeOverride = cursorAttr?.getAttribute('data-ka-cursor-size') || target.getAttribute?.('data-ka-cursor-size');
      const labelOverride = cursorAttr?.getAttribute('data-ka-cursor-label');

      if (colorOverride) {
        this.state.colorOverride = colorOverride;
      }
      if (sizeOverride) {
        const parsed = parseFloat(sizeOverride);
        if (!Number.isNaN(parsed) && parsed > 0) {
          this.state.multiplier = parsed;
        }
      }

      // Handle hide cursor state
      if (hoverAttr === 'hide') {
        this.hide();
        return;
      }

      // Handle special hover states
      if (['drag', 'zoom', 'hover'].includes(hoverAttr)) {
        this.setState('hover', { state: hoverAttr, label: labelOverride });
        return;
      }

      // Handle hover on links/buttons
      if (target.closest(this.config.selectors.hover)) {
        this.setState('hover', { label: labelOverride });
        return;
      }

      this.setState('normal');
    },

    /**
     * Handle mouse out event.
     *
     * @param {MouseEvent} event Mouse event.
     */
    handleOut(event) {
      const related = event.relatedTarget;
      
      // Reset magnetic element if mouse left it
      if (this.state.activeMagnet && event.target === this.state.activeMagnet) {
        if (!related || !event.target.contains(related)) {
          event.target.style.transform = '';
          this.state.activeMagnet = null;
        }
      }

      // Reset state if mouse left the document or moved to excluded area
      if (!related || this.isExcluded(related)) {
        this.setState('normal');
        this.state.multiplier = 1;
        this.state.colorOverride = null;
        this.state.borderOverride = null;
        this.state.activeMagnet = null;
      }
    },

    /**
     * Handle scroll event.
     */
    handleScroll() {
      if (!this.state.visible) {
        return;
      }
      // Keep cursor position stable during scroll
      this.state.targetX = this.state.x;
      this.state.targetY = this.state.y;
    },

    /**
     * Set cursor state (normal, hover, click).
     *
     * @param {string} name State name.
     * @param {Object} options Optional options.
     */
    setState(name, options = {}) {
      this.state.currentState = name;
      this.cursor.dataset.kaState = name;

      const baseScale = this.config.states.normal.scale || 1;
      const hoverScale = this.config.states.hover_link.scale || 1.25;
      const clickScale = this.config.states.click.scale || 0.9;
      const multiplier = options.sizeMultiplier || this.state.multiplier || 1;

      const isMatched = this.isLivePreviewMatchedType(this.config?.preset?.type);
      const setVar = (key, value) => this.cursor.style.setProperty(key, value);

      if (['hover', 'drag', 'zoom'].includes(name)) {
        const nextFill = this.state.colorOverride || this.config.states.hover_link.color || this.config.preset.fill_color;
        const nextBorder = this.state.borderOverride || this.config.states.hover_link.border_color || this.config.preset.border_color;
        if (isMatched) {
          this.renderLikePreview({ fill: nextFill, border: nextBorder, scale: hoverScale * multiplier });
        } else {
          setVar('--ka-cursor-scale', hoverScale * multiplier);
          setVar('--ka-cursor-fill', nextFill);
          setVar('--ka-cursor-border-color', nextBorder);
        }
        this.setLabel(options.label || this.config.states.hover_link.label);
        return;
      }

      if (name === 'click') {
        const nextFill = this.state.colorOverride || this.config.preset.fill_color;
        const nextBorder = this.state.borderOverride || this.config.preset.border_color;
        if (isMatched) {
          this.renderLikePreview({ fill: nextFill, border: nextBorder, scale: clickScale * multiplier });
        } else {
          setVar('--ka-cursor-scale', clickScale * multiplier);
          setVar('--ka-cursor-fill', nextFill);
          setVar('--ka-cursor-border-color', nextBorder);
        }
        return;
      }

      // Normal state
      if (isMatched) {
        this.renderLikePreview({
          fill: this.config.preset.fill_color,
          border: this.config.preset.border_color,
          scale: baseScale * multiplier,
        });
      } else {
        setVar('--ka-cursor-scale', baseScale * multiplier);
        setVar('--ka-cursor-fill', this.config.preset.fill_color);
        setVar('--ka-cursor-border-color', this.config.preset.border_color);
      }
      this.state.multiplier = 1;
      this.state.colorOverride = null;
      this.state.borderOverride = null;
      this.setLabel('');
    },

    /**
     * Set cursor label text.
     *
     * @param {string} text Label text.
     */
    setLabel(text) {
      if (!this.label) {
        return;
      }
      if (text) {
        this.label.textContent = text;
        this.label.classList.add('is-visible');
      } else {
        this.label.textContent = '';
        this.label.classList.remove('is-visible');
      }
    },

    /**
     * Check if target element is excluded.
     *
     * @param {Element} target Target element.
     * @returns {boolean} True if excluded.
     */
    isExcluded(target) {
      if (!target || !this.config.targeting?.excludeSelectors) {
        return false;
      }
      const selectors = this.config.targeting.excludeSelectors
        .split(',')
        .map(item => item.trim())
        .filter(Boolean);
      return selectors.some(selector => target.closest(selector));
    },

    /**
     * Show the cursor.
     */
    show() {
      this.state.visible = true;
      this.cursor.classList.remove('ka-custom-cursor--hidden');
    },

    /**
     * Hide the cursor.
     */
    hide() {
      this.state.visible = false;
      this.cursor.classList.add('ka-custom-cursor--hidden');
    },

    /**
     * Reinitialize cursor (useful for Elementor preview).
     *
     * @param {Object} config New configuration.
     */
    reinit(config) {
      this.destroy();
      this.initialized = false;
      this.init(config);
    },
  };

  // Expose API globally
  globalScope.KingAddonsCustomCursor = api;

  /**
   * Initialize cursor when DOM is ready.
   */
  const initWhenReady = () => {
    if (globalScope.KingAddonsCustomCursorData && globalScope.KingAddonsCustomCursorData.enabled) {
      api.init(globalScope.KingAddonsCustomCursorData);
    }
  };

  // Initialize on DOMContentLoaded or immediately if already loaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWhenReady, { once: true });
  } else {
    initWhenReady();
  }
})();
