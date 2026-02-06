# WooCommerce Builder Guide

## What it does
Replaces WooCommerce templates (Single Product, Product Archive, Cart, Checkout, My Account) with Elementor templates stored in `elementor_library` and tagged by `ka_woo_template_type`.

## How to use
1) Create a new Elementor template from **WP Admin → WooCommerce Builder → Add {Type} template**.  
2) In the meta box **Woo Builder Conditions**, pick the template type and set rules.  
   - Rule types: `all_products`, `product_in`, `product_cat_in`, `product_tag_in`, `product_type_in`, `is_shop`, `product_cat_archive_in`, `cart`, `checkout`, `my_account`, `always`.  
   - Values are picked with AJAX select (products, categories, tags, product types).  
3) Set **Priority** (lower runs first). Enable the template.  
4) Build the layout with Woo widgets (`Woo_Products_Grid`, Archive Title, etc.).
5) New widgets (builder-ready):
   - Cart: `Woo_Cart_Coupon_Form`, `Woo_Cart_Empty`.  
   - Checkout: `Woo_Checkout_Steps` (Pro), `Woo_Checkout_Sticky_Sidebar` (Pro), `Woo_Checkout_ACF_Fields` (Pro).  
   - My Account: `Woo_My_Account_Dashboard`, `Woo_My_Account_Order_Details`, `Woo_My_Account_Logout`, `Woo_My_Account_ACF_Fields` (Pro).

## Pro / Free
- Free applies templates only for Single Product and Product Archive.  
- Cart / Checkout / My Account and advanced pagination/filters in grids require Pro. Pro-only items are locked with a padlock indicator.

## Faceted filters
- Set a `Query ID` in `Woo_Products_Grid`.  
- Frontend can dispatch `kingaddons:filters:apply` with `{ queryId, filters }` to reload the grid via AJAX.  
- URL params `kng_filter[QUERY_ID][tax|attrs|price]` and Woo defaults (`min_price`, `filter_{taxonomy}`) are also honored (Pro).

## Notes
- Context = `ka_woo_template_type`; no extra context field needed.  
- If used outside Woo pages in the editor, widgets show an info notice instead of rendering data.






