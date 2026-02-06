## Unfold Widget Guide

### What it does
Creates a collapsible content area with a fade overlay and toggle button. Initial state can be folded or unfolded, and content can come from the editor (free) or Elementor templates (pro).

### Free vs Pro
- **Free:** Editor content, title toggle, fade overlay, shared animation timing/easing, outside button placement, responsive fold height (percent).
- **Pro:** Elementor template source, fixed (px) fold height, inside button placement, separate fold/unfold duration and easing, max-height animation mode, adjustable fade height, scroll after unfold with offset.

### Controls (Content tab)
- Content Source: Editor (free) or Elementor Template (pro).
- Title Enable, Title text, Title HTML Tag.
- Content (Editor) or Template selector (pro).
- Alignment for title/content.
- Button: Enable, Unfold/Fold texts, icons, icon position, size, position (pro for inside), alignment.
- Fade: Enable, Fade Height (pro), Fade Only When Folded.
- Advanced: Initial State, Fold Height Unit (percent free / px pro), Fold Height, Animation Duration & Easing (shared), Fold/Unfold duration & easing (pro), Animate Height Type (pro), Scroll After Unfold + Offset (pro).

### Controls (Style tab)
- Box: background, border, radius, shadow, padding, margin.
- Title: typography, color, margin bottom.
- Content: typography, text color, link/hover color, paragraph spacing.
- Button: typography, normal/hover colors, background, border, radius, shadow, padding, icon spacing, hover animation.
- Fade Overlay: color (gradient uses transparent to color).

### Usage
1) Add the Unfold widget.  
2) Enter content in the editor (or choose a template in Pro).  
3) Set fold height (percent in free, px in pro) and initial state.  
4) Toggle fade and adjust overlay behavior.  
5) Style box, text, button, and fade.  
6) In Pro, optionally place the button inside, tune per-direction timing/easing, pick animation mode, and enable scroll-after-unfold.

### Notes
- The button is optional; if disabled you can control height externally by custom JS.  
- Fade overlay hides in the unfolded state when “Fade Only When Folded” is on.  
- Responsive fold height is read from the current breakpoint (desktop/tablet/mobile).





