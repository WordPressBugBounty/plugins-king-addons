# Age Gate Guide

## Overview
Age Gate blocks access with a full-screen overlay until visitors confirm they meet the minimum age. The feature stores the decision in a cookie to avoid repeat prompts.

## Free Capabilities
- Toggle Age Gate under `King Addons → Age Gate`.
- Modes: simple Yes/No or minimum age notice (text-only).
- Display scope: entire site, posts, or pages with exclusions.
- Design presets: centered, bottom, top, side panel (left/right), or fullscreen modal; overlay color/opacity, card colors, typography, and button labels.
- Deny handling: redirect to a chosen page or show a blocked message.

## Pro Capabilities
- Date of birth validation with server-side age calculation.
- Geo rules: country-based minimum ages using WooCommerce geolocation when available.
- Display rules: custom post types, archives, and WooCommerce targets (product, category, shop) with optional category filtering.
- Elementor template as the card body, entrance animations, hover styles, logo/background.
- Behaviour controls: repeat prompt cadence (session/days/once), consent checkbox, reset on rule change.

## Setup (Free)
1. Go to `King Addons → Age Gate` and enable the switch.
2. Choose verification mode and minimum age.
3. Pick display scope and optional exclusion pages.
4. Adjust design colors/template and button labels.
5. Select denial behaviour (redirect or block message) and save.

## Setup (Pro)
1. Select **Date of birth** mode and pick the date format.
2. Configure geo rules (`Default age` and country map like `US=21`).
3. Set display mode to **Custom** and target CPTs or WooCommerce contexts.
4. Choose Elementor template/animation/brand assets and hover states.
5. Configure repeat prompt mode, consent checkbox, and save.

## Frontend Behaviour
- On first view (no cookie) the overlay shows and locks scroll.
- Accept sets `allowed|{revision}` cookie; decline sets `denied|{revision}`.
- Redirect executes server-side when denial is set to redirect; block keeps the overlay visible with the blocked message.
- Revision changes (when “reset on rule change” is enabled) invalidate old cookies.

## Testing Checklist
- Confirm overlay shows when enabled and no cookie exists.
- Accept writes cookie and hides overlay; decline redirects or blocks based on settings.
- Exclusion pages bypass the overlay.
- Pro: DOB validation rejects invalid/underage inputs and accepts valid ones; geo overrides apply expected minimum age.
- Pro: Custom display rules trigger only on targeted CPT/Woo contexts; consent checkbox blocks submission until checked.
- Responsive: overlay and card remain usable on mobile widths.

## Frontend preview

To force-render the Age Gate for visual testing (even if you already accepted it), open any frontend URL as an admin with:

`?ka_age_gate_preview=1`



