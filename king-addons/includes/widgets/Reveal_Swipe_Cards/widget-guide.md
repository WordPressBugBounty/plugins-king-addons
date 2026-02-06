# Reveal Swipe Cards Widget – Developer Guide

## Overview

The **Reveal Swipe Cards** widget creates a responsive card grid where each card's content is initially hidden behind a colored overlay. When the user hovers over (or scrolls into view), the overlay "wipes" away in a specified direction to reveal the card's content.

**Free version** supports hover/scroll triggers, 4-direction wipe, and full style customization.  
**Pro version** adds mask shapes (circle, diagonal, wave), blur edge effect, touch swipe reveal, click trigger, and sequence modes (stagger, active-one).

---

## File Structure

```
king-addons/
└── includes/
    └── widgets/
        └── Reveal_Swipe_Cards/
            ├── Reveal_Swipe_Cards.php   # Free widget class
            ├── script.js                 # Frontend JavaScript
            └── style.css                 # Base + Pro styles

king-addons-pro/
└── includes/
    └── widgets/
        └── Reveal_Swipe_Cards_Pro/
            └── Reveal_Swipe_Cards_Pro.php  # Pro widget class (extends Free)
```

---

## CSS Custom Properties (Design Tokens)

All major values are exposed via CSS custom properties for easy theming:

| Property                    | Default       | Description                          |
|-----------------------------|---------------|--------------------------------------|
| `--kng-rsc-columns`         | `3`           | Grid column count                    |
| `--kng-rsc-gap`             | `24px`        | Gap between cards                    |
| `--kng-rsc-min-height`      | `320px`       | Minimum card height                  |
| `--kng-rsc-content-align`   | `center`      | Vertical content alignment           |
| `--kng-rsc-text-align`      | `center`      | Horizontal text alignment            |
| `--kng-rsc-duration`        | `500ms`       | Reveal animation duration            |
| `--kng-rsc-easing`          | `ease-out`    | CSS easing function                  |
| `--kng-rsc-overlay-color`   | `#2563eb`     | Overlay background color             |
| `--kng-rsc-overlay-opacity` | `1`           | Overlay opacity                      |
| `--kng-rsc-blur-edge`       | `0px`         | Blur edge softness (Pro)             |
| `--kng-rsc-mask-shape`      | `rect`        | Mask shape identifier                |

---

## HTML Structure

```html
<div class="kng-rsc" data-settings="{...}">
  <div class="kng-rsc__grid" role="list">
    
    <div class="kng-rsc-card kng-rsc-card--direction-left" data-index="0">
      <!-- Optional badge -->
      <span class="kng-rsc-card__badge kng-rsc-card__badge--top-right">New</span>
      
      <div class="kng-rsc-card__content">
        <div class="kng-rsc-card__inner">
          <div class="kng-rsc-card__media">
            <span class="kng-rsc-card__icon">...</span>
          </div>
          <h3 class="kng-rsc-card__title">Card Title</h3>
          <p class="kng-rsc-card__description">Card description text.</p>
          <a href="#" class="kng-rsc-card__button">Learn More</a>
        </div>
      </div>
      
      <div class="kng-rsc-card__overlay" aria-hidden="true"></div>
    </div>
    
    <!-- More cards... -->
  </div>
</div>
```

---

## JavaScript API

### Data Settings (passed via `data-settings` attribute)

```js
{
  trigger: "hover" | "scroll" | "both" | "click",
  direction: "left" | "right" | "top" | "bottom",
  duration: 500,           // ms
  easing: "ease-out",
  resetOnLeave: true,
  scroll: {
    threshold: 0.3,        // 0-1, viewport intersection ratio
    once: false,           // reveal only once
    resetOnExit: false     // reset when leaving viewport
  },
  // Pro options:
  maskShape: "rect" | "circle" | "rounded" | "diagonal" | "wave",
  blurEdge: {
    enable: false,
    softness: 20
  },
  touch: {
    enable: false,
    threshold: 50,         // px
    velocity: 0.5,         // px/ms
    directionLock: true
  },
  sequence: {
    mode: "off" | "stagger" | "active-one",
    delay: 100             // ms between cards in stagger mode
  }
}
```

### Events

The widget dispatches custom events on the wrapper element:

| Event                    | Detail                          | Description                    |
|--------------------------|---------------------------------|--------------------------------|
| `kng-rsc:reveal`         | `{ index, card }`               | Card reveal started            |
| `kng-rsc:hide`           | `{ index, card }`               | Card hide started              |
| `kng-rsc:reveal-complete`| `{ index, card }`               | Card reveal animation complete |

### State Classes

| Class           | Applied To     | Description                       |
|-----------------|----------------|-----------------------------------|
| `.is-revealed`  | `.kng-rsc-card`| Card overlay is animated away     |
| `.is-dragging`  | `.kng-rsc-card`| Touch swipe in progress (Pro)     |
| `.is-instant`   | `.kng-rsc-card`| No animation (reduced motion)     |

---

## Reveal Directions

The overlay slides in the opposite direction of the setting name:

| Setting    | Overlay Slides | Visual Result               |
|------------|----------------|-----------------------------|
| `left`     | Left → Right   | Content revealed from left  |
| `right`    | Right → Left   | Content revealed from right |
| `top`      | Top → Bottom   | Content revealed from top   |
| `bottom`   | Bottom → Top   | Content revealed from bottom|

---

## Pro Features

### Mask Shapes

Instead of a linear wipe, Pro offers creative reveal shapes:

- **circle**: Circular shrink from center outward (uses `clip-path: circle()`)
- **rounded**: Rectangle with increasing border-radius
- **diagonal**: Diagonal wipe using `clip-path: polygon()`
- **wave**: Wavy edge wipe (CSS polygon)

### Blur Edge Effect

Adds a soft gradient edge to the overlay as it reveals, creating a smoother transition. Only works with `rect` mask shape.

### Touch Swipe

On touch devices, users can swipe to reveal cards:
- **Threshold**: Minimum swipe distance before trigger
- **Velocity**: Minimum swipe speed (pixels/ms)
- **Direction Lock**: Only allow swipe in the reveal direction

### Sequence Modes

- **Stagger**: When triggered (e.g., scroll into view), cards reveal one after another with a configurable delay
- **Active One**: Only one card can be revealed at a time; revealing a new card hides the previous one

### Click Trigger

Cards can be revealed/hidden by clicking/tapping instead of hovering.

---

## Accessibility

1. **Reduced Motion**: Widget respects `prefers-reduced-motion` and disables animations when set
2. **Keyboard Support**: Cards can be focused and triggered via keyboard (focus = reveal)
3. **ARIA**: Overlay has `aria-hidden="true"` since it's decorative
4. **Focus Visible**: Button and card have visible focus outlines

---

## Performance Considerations

1. **GPU-Accelerated**: Uses `transform` and `clip-path` for animations (no layout thrashing)
2. **will-change**: Applied sparingly to `.kng-rsc-card__overlay`
3. **Intersection Observer**: Used for scroll triggers (no scroll listeners)
4. **Event Delegation**: Uses delegated event listeners where possible

---

## Customization Examples

### Custom Overlay Gradient (CSS)

```css
.kng-rsc-card__overlay {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

### Slower Animation

```css
.kng-rsc {
  --kng-rsc-duration: 800ms;
  --kng-rsc-easing: cubic-bezier(0.4, 0, 0.2, 1);
}
```

### Hook into Reveal Event (JavaScript)

```js
document.querySelector('.kng-rsc').addEventListener('kng-rsc:reveal', (e) => {
  console.log('Card revealed:', e.detail.index);
});
```

---

## Troubleshooting

| Issue                          | Solution                                           |
|--------------------------------|----------------------------------------------------|
| Overlay not animating          | Check if reduced motion is enabled on device       |
| Cards not revealing on scroll  | Ensure `trigger` includes `scroll`                 |
| Touch swipe not working        | Enable touch in Pro settings, check threshold      |
| Overlay color not changing     | Use Elementor style controls or override CSS var   |
| Badge hidden behind overlay    | Badge has `z-index: 3`, should be above overlay    |

---

## Changelog

### 1.0.0
- Initial release with hover/scroll triggers
- 4-direction reveal animation
- Responsive grid layout
- Badge support
- Full style controls

### 1.0.0 Pro
- Mask shapes (circle, diagonal, wave)
- Blur edge effect
- Touch swipe reveal
- Click trigger
- Sequence modes (stagger, active-one)
- Gradient overlay support
- Box shadow controls
