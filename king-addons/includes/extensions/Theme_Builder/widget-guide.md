# Theme Builder (Posts & CPT)

## Overview
Create Elementor templates for single posts/pages/CPTs, archives (blog/categories/tags/taxonomies), author archives, search results, and 404 pages. Templates are stored as `elementor_library` posts with Theme Builder meta.

## How to create
1. Go to `King Addons → Theme Builder` and click **Add New**.
2. Pick the template type and location (e.g., All Posts, Blog Archive, 404).
3. The template opens in Elementor; design as usual.
4. Use the Theme Builder settings panel in Elementor to set preview IDs (post/term/author) or search query for accurate previews.

## Display conditions
- Free: All posts, all pages, blog archive, all categories, all tags, search results, 404.
- Pro: Specific posts/pages, custom post types, specific terms/taxonomies, authors, front page, blog page, include/exclude mixes.

## Priority and conflicts
- Lower priority wins; ties fall back to the more specific rule count, then lowest ID.
- Free allows one active template per primary type (single, archive, search, 404); Pro removes this limit.

## Pro features
- Advanced conditions (IDs, taxonomies, authors, front/blog page).
- Multiple templates per type with priority.
- Duplicate templates from the list table.

## Testing checklist
- Confirm template renders on matching context and not on excluded pages.
- Toggle enable/disable and verify cache updates.
- Validate Woo contexts (product/cart/checkout/account) are left to Woo Builder.
- Check Elementor preview uses selected preview IDs/query.




