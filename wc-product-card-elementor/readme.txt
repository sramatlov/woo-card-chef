=== Woo Card Chef ===
Contributors: s15webdesign
Tags: woocommerce, elementor, product card, archive, category, lipscore, acf
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.6.10
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: woocommerce, elementor

A custom Elementor widget suite for WooCommerce product cards and focused product-detail-page blocks in Elementor.

== Description ==

This plugin started as a custom "Product Card Grid" widget for WooCommerce category and archive pages. It now also ships focused PDP widgets for Elementor Theme Builder: Product Gallery, Product Price & Promo, Product USP / Benefits, Product Delivery & Availability, Product Accordion, Product Upsells, and Product Cross-sells / Related. Each widget is modular, server-rendered where possible, and designed so content lives in WooCommerce/ACF while presentation stays in Elementor.

= Card features =

* Auto-detection of the current category when placed on a Theme Builder archive template
* Smart discount badge with three formats (percentage, amount, or smart Rule of 100), a separate show/hide toggle, and a configurable minimum-discount threshold to suppress badges on weak deals
* "Tot -X%" prefix for variable products with mixed discounts across variations
* Optional savings line below the price, disabled by default for a calmer product-card design
* Lipscore rating placeholder (renders client-side via Lipscore's own JS)
* Free shipping pill that auto-renders only when the product price clears a configurable threshold
* Up to three USPs per product, sourced from ACF text fields auto-registered on the product post type
* Optional hover image swap to the second gallery image
* Optional out-of-stock label
* Optional action button for view product or add to cart / choose options
* Mobile controls for columns, spacing, title clamp, USP visibility, and hover image swap
* Whole-card click area using an overlay link, avoiding nested-link conflicts with Lipscore

= Elementor controls =

The widget exposes 35+ controls across the Content and Style tabs, including:

* Source (auto archive query or manual category)
* Multi-category picker, product limit, and order-by (manual mode)
* Optional native pagination in auto archive mode; products per page remains controlled by WooCommerce, the theme, Customizer, or filter plugins
* Toggles for every card element (rating, savings line, shipping pill, USPs, hover swap, badge, out-of-stock label, and action button)
* Discount badge format and threshold
* Shipping threshold and label
* Responsive columns, gaps, and padding (desktop, tablet, mobile)
* Configurable title clamp (lines)
* Card background, border radius, and box shadow
* Image aspect ratio
* Typography groups for title, price, and USPs
* Color controls for every text element, badges, pills, and icons

= PDP widgets =

* Product Gallery (PDP): WooCommerce images, YouTube video slides, thumbnail strip, badgebar, lightbox and zoom.
* Product Price & Promo (PDP): current/sale price, reference price, discount chip, savings amount and variable-product handling.
* Product USP / Benefits (PDP): short product benefit lines from the PDP USP repeater, with fallback to short description and product-card USPs.
* Product Delivery & Availability (PDP): stock status, global delivery promise and free-shipping threshold line near the buy section.
* Product Accordion (PDP): description, specifications, reviews, FAQ and manual sections in an accessible accordion.
* Product Upsells (PDP): WooCommerce upsells rendered as the existing product cards for accessories, spare parts and extensions, with optional popularity sorting.
* Product Cross-sells / Related (PDP): WooCommerce cross-sells rendered as product cards, with WooCommerce related products as the fallback when no cross-sells are available.

= Designed for Bourgini, generic by build =

The plugin's defaults match the Bourgini brand palette but every visual is overridable in Elementor. The same plugin can be installed on any WooCommerce shop running Elementor and adapted via the widget's controls.

== Installation ==

1. Upload the `wc-product-card-elementor` folder to `/wp-content/plugins/`, or install the zip via Plugins > Add New > Upload Plugin.
2. Activate the plugin through the Plugins menu in WordPress.
3. Make sure Elementor and WooCommerce are active. Advanced Custom Fields or ACF Pro is optional, but needed for the ACF content fields; ACF Pro is required for repeater-based PDP video and USP fields.
4. Open Elementor Theme Builder and edit your Product Archive template.
5. Replace the existing product grid widget with the new "Product Card Grid" widget (under "Woo Card Chef" in the widget panel).
6. Configure the widget. Default source is "Current archive (auto)", which is what you want on archive templates.
7. Fill in the USP fields on individual products (Products > Edit Product > Product Card USPs metabox).

= USP fields =

Three Product Card text fields are auto-registered on the product post type: USP 1, USP 2, USP 3. Each USP renders only if filled in. Recommended: at least one USP should be product-specific (e.g. "Geschikt voor 4 personen", "1500W vermogen") rather than a site-wide promise that repeats on every card.

For the PDP Product USP / Benefits widget, ACF Pro registers a repeater field `pdp_usps` with one text field per row: `usp_text`. Editors only fill in the USP lines; layout, icons, columns, spacing, colours and typography are controlled in Elementor.

= Lipscore integration =

If your site already loads Lipscore site-wide, the rating block on each card will automatically populate with the product's stars and review count. No further configuration is needed. If a product has no reviews, the rating block stays empty (no awkward placeholder).

= WBW Product Filter PRO integration =

For AJAX filtering to work correctly with this widget, three settings must be configured in WBW Product Filter PRO:

1. Go to WBW Product Filter PRO settings > Main > Enable Ajax.
2. Set **Product List / Loader Selector** to: `ul.products.wc-card-grid`
3. Set **Product Container Selector** to: `.wcpce-grid-section`
4. Enable **Force Theme Templates**.

Without these settings, WBW cannot locate the product grid and falls back to a full page reload instead of AJAX filtering.

The plugin no longer ships a WBW/Elementor sticky-header workaround. If WBW filtering causes Elementor Pro sticky header rows to appear while the page is still at the top, handle that as a site-level snippet so the reusable plugin stays free of shop-specific header logic.

== Frequently Asked Questions ==

= Does this work without ACF? =

Yes, but ACF-owned content fields will be empty. The Product Card USP block needs ACF fields. The PDP USP / Benefits widget can fall back to the WooCommerce short description or Product Card USPs, but the dedicated `pdp_usps` repeater requires ACF Pro.

= Does this work without Lipscore? =

Yes. The rating block stays empty if Lipscore is not installed or has no review data for the product. You can also turn the rating off entirely via the widget's "Show Lipscore rating" toggle.

= Will pagination work? =

Yes. In Auto mode on an archive template, the widget uses the main archive query and native WooCommerce archive page URLs. In Manual mode, enable the widget's Pagination control to show server-rendered numbered links using the wcpce_paged query var.

= Why is the badge missing on a product I know is on sale? =

Check the "Minimum discount percentage" setting in the Discount Badge controls. The default is 0%, so every valid sale can show a badge. Increase the threshold when weak discounts should be suppressed.

= Can I use this on multiple WooCommerce shops? =

Yes. The plugin is generic and only its defaults are tuned to a specific brand palette. Install it on any shop and override colors via the Elementor controls.

== Changelog ==

= 2.6.10 =
Compatibility metadata release. Records the production-tested stack of WordPress 7.0, WooCommerce 11.0, Elementor 4.2.2, Elementor Pro 4.2.1, and PHP 8.3. No functional plugin behaviour changed.

= 2.6.9 =
Hotfix: Product Card Grid now shows the customer-facing Dutch empty-state message in Auto mode when a search or archive has no matching products, instead of exposing the technical editor guidance on the frontend.

= 2.6.8 =
Product Accordion manual section now falls back to an automatic PDF match in the configured `/manuals` directory when the product_manual ACF field is empty. Matching uses the product SKU plus common MPN meta fields, including variants with trailing zeroes stripped, so PDFs whose title contains the base MPN can appear automatically in the PDP accordion.

= 2.6.7 =
Accessibility hardening after live ARIA review. Product Gallery image slides now use the attachment alt text with a product-name fallback when media alt text is empty, and inactive gallery slides are marked inert alongside aria-hidden so hidden slide controls cannot be reached by assistive technology. Also includes the 2.6.6 Elementor frontend hook guard.

= 2.6.6 =
Hotfix: guard Product Gallery and Product Accordion Elementor hook registration when Elementor's frontend object exists before its hooks API. This removes live frontend console errors while preserving the standard DOMContentLoaded initialisation and Elementor editor preview support.

= 2.6.5 =
Refine Product Gallery thumbnail styling controls. Fix the active thumbnail border colour control so it targets the actual thumbnail button border, add a thumbnail hover border colour control with Bourgini red as the default, and explicitly lock thumbnail button hover/focus backgrounds so theme button hover styles cannot bleed into the strip.

= 2.6.4 =
Hotfix: restore fallback YouTube thumbnails in the Product Gallery. v2.6.3 used sanitize_key() on YouTube IDs in the thumbnail fallback, but YouTube IDs are case-sensitive. The gallery now sanitises IDs with a case-preserving allowlist before building mqdefault.jpg URLs.

= 2.6.3 =
Hardening patch for v2.6.2. PHPCS security review: escaped gallery slide aria labels and fallback YouTube thumbnail URLs more explicitly, escaped Product Upsells and Cross-sells / Related heading tags with tag_escape(), and renamed local variables that collided with WordPress globals or reserved keywords. No intended frontend behaviour changes.

= 2.6.2 =
Add a Product Upsells order control. The default keeps the stored WooCommerce linked-product order; the new Popularity option sorts the linked upsells by WooCommerce total sales, highest first, while keeping the linked order as the tie-breaker.

= 2.6.1 =
Hardening patch based on code review of v2.6.0. Fix: alt text passed to wp_get_attachment_image() in the card template and the gallery video thumbnail is no longer pre-escaped, which double-encoded special characters in product and video titles (the function escapes its own attributes, per CONVENTIONS). Code quality: renamed the local $product loop variable in the Cross-sells / Related widget's get_visible_products_from_ids() to $candidate so it can never be confused with the WooCommerce global; removed a duplicate reviews modifier class in the Product Accordion render; corrected the build_reviews_html() docblock to describe the actual wp_kses_post() implementation. Documentation/metadata: clarified that the WBW/Elementor sticky-header workaround is site-level, not part of the plugin, and updated the WooCommerce tested-up-to header to 10.7.

= 2.6.0 =
Added Product Cross-sells / Related (PDP) widget. The widget renders WooCommerce cross-sells first and falls back to WooCommerce related products when no visible cross-sells are configured. It reuses the shared product-card renderer and the same card element, badge, responsive layout, typography, colour and mobile-scroll controls as the Product Upsells widget.

= 2.5.8 =
Hardening release based on code review of v2.5.7. Fix: aligned get_current_product() ordering across all PDP widgets (Gallery, Price, USP, Delivery, Accordion) to prefer the queried single-product context before the WooCommerce global, matching the v2.5.1 Upsells widget fix. Refactor: simplified get_reviews_content() in the Product Accordion widget by removing the redundant set/restore of global $product around the woocommerce_product_tabs filter; the global is now set once just before the Lipscore tab callback and restored via finally. Code quality: replaced esc_attr() with tag_escape() for HTML tag names in the Product Accordion widget's render_accordion_item(), aligning with the existing pattern in the USP and Upsells widgets.

= 2.5.7 =
Add richer Product Upsells heading style controls for alignment, margin, padding, background, border and border radius.

= 2.5.6 =
Enforce Product Upsells title and price typography through Elementor typography defaults and scoped frontend CSS fallback.

= 2.5.5 =
Tune Product Upsells card typography: product titles are 12px/400 and current/sale prices are 15px/500 by default.

= 2.5.4 =
Refine Product Upsells card defaults: smaller scoped title typography, PFAS-vrij badge off by default, and a subtler temporarily out-of-stock label.

= 2.5.3 =
Remove extra non-core Product Upsells guard so the widget trusts the stored WooCommerce upsell IDs and only filters products for visibility.

= 2.5.2 =
Fix Product Upsells retrieval to mirror WooCommerce core: map the stored upsell IDs directly through `wc_get_product()` and filter visible products, instead of querying products with `wc_get_products()`.

= 2.5.1 =
Fix Product Upsells PDP context resolution so frontend single-product pages read upsells from the queried PDP product, not a polluted global product from another product loop.

= 2.5.0 =
Added Product Upsells (PDP) widget for WooCommerce upsells, with shared product-card rendering, section controls, responsive grid/mobile-scroll layout and editor empty-state notices.

= 2.4.2 =
Mobile CSS fix for the Product Accordion FAQ section.

Long FAQ question labels now wrap correctly on narrow screens. The accordion trigger, FAQ trigger, question text, and answer containers explicitly allow normal wrapping and safe long-word breaking, preventing Dutch compound words or inherited theme button styles from pushing FAQ rows outside the viewport.

= 2.4.1 =
Bug-fix and hardening release for the PDP Product Accordion and shared PDP helpers.

The Product Accordion now renders panel content open in the initial server HTML as a progressive-enhancement fallback, then JavaScript applies the configured default-open/closed state after initialization. This keeps accordion content available for no-JS contexts and crawlers while preserving accessible closed panels with the `hidden` attribute once JavaScript is active.

The Specifications section now relies on WooCommerce's native attributes renderer instead of pre-checking only visible custom attributes, so products with dimensions or weight but no custom attributes can still show the section. The CSS rule that suppresses WooCommerce's default "Additional information" heading is now scoped to the Specifications panel only, so headings in Description, FAQ, Reviews, or Manual content are no longer hidden by accident.

Fixed direct `mb_substr()` calls in the Product USP / Benefits and Product Price & Promo widgets by adding the same `function_exists( 'mb_substr' )` fallback pattern used elsewhere in the plugin. This prevents fatal errors on hosts where the mbstring PHP extension is unavailable.

Shared price data from `WCPCE_Price_Helper::get_product_price_data()` is now cached per request, avoiding repeated variation-price calculations when multiple PDP widgets render for the same product. Lipscore review callback rendering also restores the global product context safely if a third-party callback throws.

= 2.4.0 =
Introduces the Product Accordion widget (PDP Phase 6), the sixth Elementor widget in the plugin.

The widget replaces the default WooCommerce product tabs on the product detail page with a fully server-rendered, accessible accordion. All five sections — Description, Specifications, Reviews (Lipscore), FAQ, and Manual — are hidden automatically when their content source is empty, so editors never see empty headings.

The Description section renders the WooCommerce product description through the same processing pipeline WooCommerce uses natively (wc_format_long_description + wpautop). The Specifications section outputs the WooCommerce product attributes table with only public attributes; the default WooCommerce h2 heading inside the panel is suppressed via CSS. The Reviews section wraps the Lipscore tab by calling its registered WooCommerce tab callback via the woocommerce_product_tabs filter, exactly as WooCommerce does natively. The FAQ section reads the existing ACF repeater product_faq (sub-fields: vraag, antwoord) and renders each pair as an inner accordion. The Manual section reads a new ACF file field product_manual (registered in the new group_wcpce_pdp_accordion) and renders a download link using only the configurable label — the raw filename is never exposed to shoppers.

The accordion is WCAG 2.2 AA accessible: every trigger is a real button element wrapped in a configurable heading (h2/h3/h4) to maintain page hierarchy; aria-expanded and aria-controls are set on every trigger; panels use the HTML hidden attribute (not CSS-only display:none) so closed content is fully removed from the tab order and invisible to assistive technology; the + / − icon carries aria-hidden="true"; all focus states are visible. Multiple sections can be open simultaneously (NNG recommendation). A Elementor control lets editors set one section as default-open.

A scoped, deferred JavaScript file (product-accordion.js) handles toggle behaviour, the FAQ inner accordion, Lipscore review-count sync via MutationObserver, hash-jump navigation to the reviews section (#lipscore-review-list, #tab-lipscorereviews, #reviews), scroll-to-trigger on open, and a double-init guard for Elementor editor contexts.

Elementor style controls cover the divider colour and width, trigger padding and typography and colour (normal and open states), icon colour, and content padding and typography and colour.

Also removes a dependency on the Additional Custom Product Tabs for WooCommerce (ACPT) plugin: manual/PDF content is now handled by the plugin's own product_manual ACF field, which can be deleted from the database once the ACPT plugin is deactivated. The product_faq ACF field is registered outside the plugin and is read-only from the plugin's perspective; the schema snippet that also reads it is unaffected.

= 2.3.2 =
Visual refinement for the Product Delivery & Availability widget list layout.

The stock-status row ("Op voorraad") now renders with a larger icon (20 px), green text, and bolder weight so it reads immediately as the commerce-critical status signal rather than a plain list item at the same visual weight as the delivery and shipping rows. The delivery and shipping icon colour has been changed from the olive-green default to a neutral grey, creating a clear hierarchy: green for status, neutral for promise and benefit. The shipping line ("Gratis bezorging") is slightly smaller and muted because it is secondary information. A subtle separator line appears between the status row and the delivery/shipping rows in list mode, visually grouping "what the product is" and "what we promise" without any change to the HTML output. Row gap increased from 8 px to 10 px for better breathing room.

CSS-only change. No PHP changes. The Elementor style controls (icon colour, text colour, typography) continue to override all defaults.

= 2.3.1 =
Bug-fix release for the Product USP / Benefits widget.

Fixed a performance issue in auto source mode where all three content sources (PDP USP repeater, short description, product-card USPs) were always fetched on every render, even when an earlier source already returned results. The previous implementation passed all three method calls as elements of an array literal; PHP evaluates every element of an array before a foreach begins, so the short-circuit return inside the loop had no effect. The fix replaces the array/foreach pattern with a sequential series of assignments guarded by empty() checks, so each source is only read when the previous one produced no output. On products that have PDP USP repeater content this eliminates one ACF get_field() call and one short-description regex parse per widget render.

Renamed the internal sanitise_usp_text() helper to sanitize_usp_text() to match the American English spelling used throughout WordPress core, the plugin codebase, and all other Woo Card Chef conventions.

= 2.3.0 =
PDP Phase 4 - Product Delivery & Availability widget. The plugin now ships five Elementor widgets: Product Card Grid, Product Gallery (PDP), Product Price & Promo (PDP), Product USP / Benefits (PDP), and Product Delivery & Availability (PDP).

The new Delivery & Availability widget renders the key purchase reassurance lines near the PDP buying area: stock status, one delivery/cut-off promise, and one free-shipping threshold line. In-stock products show `Op voorraad`, the configurable delivery promise (`Voor 23:00 besteld, morgen in huis` by default), and either `Gratis bezorging` or `Gratis bezorging vanaf €25,-` depending on the configured threshold. Temporarily out-of-stock products show `Tijdelijk uitverkocht` with an optional `Niet direct leverbaar` note, and suppress the tomorrow/free-shipping lines. Products marked with the existing `badge_niet_leverbaar` flag show `Niet meer leverbaar` only.

The widget reuses existing data sources and helpers: WooCommerce stock status via `WCPCE_Stock_Helper`, permanent unavailable status via `WCPCE_ACF_Helper`, and price data via `WCPCE_Price_Helper`. Delivery text and free-shipping threshold are Elementor controls, not new ACF fields. Presentation controls include list/pill layout, icons on/off, spacing, typography, colours, item background, border, radius and shadow. The widget is server-rendered and ships no JavaScript.

Infrastructure: new widget class `includes/Widgets/class-product-delivery-widget.php`, new stylesheet `assets/css/product-delivery.css`, asset registration in `class-assets.php`, and widget registration in `class-plugin.php`.

= 2.2.1 =
Bugfix for the Product USP / Benefits widget. Short-description fallback now preserves list item boundaries when WooCommerce short description content is stored as an HTML list, so each `<li>` becomes its own USP item instead of the whole list rendering as one combined line.

= 2.2.0 =
PDP Phase 3 - Product USP / Benefits widget. The plugin now ships four Elementor widgets: Product Card Grid, Product Gallery (PDP), Product Price & Promo (PDP), and Product USP / Benefits (PDP).

The new Product USP / Benefits widget renders short product benefit lines near the PDP buying area. Its primary content source is a new ACF Pro repeater `pdp_usps`, with a single text field per row: `usp_text`. This keeps ACF focused on content only. Layout and presentation live in Elementor: source mode, max item count, optional heading, list/cards/inline layout, global icon on/off, icon style, responsive columns, spacing, padding, typography, colours, border, radius and shadow.

Source handling is defensive and editor-friendly. Default "Auto" mode uses PDP USP rows first, then falls back to the WooCommerce short description, then to the existing Product Card USP fields. Editors can also force any source explicitly. The widget is server-rendered and ships no JavaScript.

Infrastructure: new widget class `includes/Widgets/class-product-usps-widget.php`, new stylesheet `assets/css/product-usps.css`, new ACF field group `group_wcpce_pdp_usps`, asset registration in `class-assets.php`, and widget registration in `class-plugin.php`.

= 2.1.0 =
PDP Phase 2 — Price & Promo Block widget. The plugin now ships three Elementor widgets: the Product Card grid, the Product Gallery (PDP), and the new Product Price & Promo block for the product detail page.

The Price & Promo Block replaces the default WooCommerce price output on the PDP with a stronger, status-aware price display. It shows the regular price, and on sale it shows a struck-through reference price plus the sale price, an optional discount-percentage chip, and an optional savings amount line. Variable products show either "Vanaf €X" (the lowest current price) or the full WooCommerce range, with a "Tot -X%" chip when discounts are mixed across variations. The block reuses the existing price and badge helpers so its numbers, the "Tot" prefix and the accessible discount label stay consistent with the product card and gallery. Two layouts (Compact inline and Extended prominent) control prominence only; which elements appear is governed by independent toggles. When a product carries the "Niet meer leverbaar" flag the price is dimmed and all discount framing is dropped.

All displayed amounts run through wc_get_price_to_display() so they match WooCommerce's own get_price_html() under both tax-inclusive and tax-exclusive shop settings. The struck reference value passes through a new wcpce_price_reference_value filter, so a 30-day-lowest reference (NL Omnibus/Prijzenwet) can be injected later without changing the widget; the percentage and savings derive from the same reference. For variable products on sale the block intentionally avoids a single struck reference, because the lowest-priced variation never had that reference price and anchoring against it would be a misleading discount. The widget emits no Product/Offer structured data — WooCommerce core and the SEO plugin remain the single source for that. The block is server-rendered and ships no JavaScript.

Infrastructure: new widget class includes/Widgets/class-product-price-widget.php, new stylesheet assets/css/product-price.css registered in class-assets.php, and the widget registered in class-plugin.php. The project-wide JavaScript stance is clarified — progressive enhancement, not blanket zero-JS: the product card and purely presentational widgets ship no JS, while interactive widgets add scoped, deferred scripts only where interaction requires it.

= 2.0.0 =
Major release — PDP Gallery widget (PDP Phase 1). The plugin now ships two Elementor widgets: the existing Product Card grid widget and a new Product Gallery widget for the product detail page.

The Gallery widget replaces the default WooCommerce product image and gallery on the PDP. It renders WooCommerce featured image and gallery images as a slide-based gallery, optionally followed by YouTube video slides from an ACF repeater field (ACF Pro required). Thumbnails appear below the main image with a configurable visible count and a +N overflow indicator. Video slides are always positioned at slot (thumbnail_count − 1) so they are visible in the strip rather than hidden behind the overflow indicator. A badgebar (Korting / Nieuw / PFAS-vrij) shows above or below the gallery block; badge display matches the product card exactly including the PFAS leaf SVG icon and equal badge heights. Status overlays (Niet meer leverbaar / Tijdelijk uitverkocht) render on the main image. A full-featured lightbox handles both images and video: image slides support click-to-zoom, desktop drag-to-pan, and mobile pinch-zoom; video slides open a YouTube nocookie embed. Navigation uses prev/next chevron buttons (desktop, white circle, half over image edge) and swipe-only on mobile. The widget introduces JavaScript to the plugin for the first time since v1.0.54 — scoped, deferred, and only enqueued on pages where the widget is present.

Infrastructure changes: widget files moved to includes/Widgets/, class-assets.php added to centralise CSS/JS registration for both widgets (R7 milestone), ACF field group group_wcpce_pdp_gallery_media added with a YouTube video repeater (pdp_gallery_videos) requiring ACF Pro. The plugin now declares two Elementor widgets in class-plugin.php.

The 1.0.85–1.0.89 series covers the gallery development history: v1.0.85 gallery aspect-ratio/viewport, v1.0.86 video thumbnail source alignment, v1.0.87 video preview as CSS background layer to prevent Elementor image-style interference, v1.0.88 video-lightbox separation, single-guard init, zoom listener fix, v1.0.89 "Tot " prefix correctness, validate_gallery_settings(), YouTube host validation, video thumbnail cache priming, hidden-slide focusability fix, and defer script loading.

= 1.0.89 =
Gallery hardening and correctness release. Restored the "Tot " prefix on the discount badge for variable products with mixed discounts (the gallery was passing the wrong argument to the badge helper, so the prefix never showed). Added strict YouTube host validation so only youtube.com, youtu.be and youtube-nocookie.com URLs are treated as embeddable videos. Custom video thumbnails are now primed in the same bulk attachment-cache batch as image slides, avoiding per-thumbnail database calls. Play buttons in hidden (inactive) video slides are no longer keyboard-focusable; focusability is restored when their slide becomes active. Added a defensive validate_gallery_settings() pass that whitelists select values and clamps numeric ranges before render, matching the card widget's validation approach. The gallery script is now registered with the 'defer' loading strategy.

= 1.0.88 =
Gallery bugfix release. Video play buttons now still open the video player when image lightbox is disabled, without making product images clickable. Gallery initialisation now uses one shared guard on the gallery element to prevent duplicate Elementor/DOMContentLoaded event bindings. Lightbox zoom now binds document-level mouse handlers once per gallery instance and clears stale image references when the lightbox content changes.

= 1.0.87 =
Gallery video preview hardening. The main video preview now renders its thumbnail as a CSS background layer with `background-size: cover` instead of a normal image element, preventing Elementor's global image styles from forcing the YouTube preview back to its intrinsic 16:9 height.

= 1.0.86 =
Gallery video preview correction. The main video preview now uses the same YouTube `mqdefault.jpg` source as the thumbnail strip instead of `maxresdefault.jpg`, so the main preview and thumbnail crop consistently in the 1:1 gallery frame. Removed the no-longer-needed maxres fallback JavaScript.

= 1.0.85 =
Gallery refinement release. Video slides now fill the gallery's configured aspect-ratio viewport correctly, so YouTube preview images crop into the same 1:1 frame as product images instead of rendering as a 16:9 strip with empty space below. The gallery aspect-ratio control now targets the outer slide viewport. Video thumbnails use higher-quality YouTube preview images with fallback handling, and video play buttons are more prominent in both the main gallery and thumbnail strip.

= 1.0.84 =
Phase 6 R5: extracted ACF field reading into a new stateless helper class `WCPCE_ACF_Helper` (`includes/Helpers/class-acf-helper.php`). Two static methods: `get_field_keys()` returns the canonical list of eight field names used by the product card (`card_title`, `usp_1`–`usp_3`, `badge_nieuw`, `badge_pfas_vrij`, `badge_niet_leverbaar`, `card_hover_image`); `get_card_data( $product_id )` reads all eight fields via a single `get_post_meta()` call and returns them in a consistently-keyed array with empty-string defaults, matching the structure that `card.php` and all downstream helpers already expect. The read is a pure object-cache hit because `WCPCE_Image_Helper::prime_attachment_caches()` runs `update_meta_cache()` for all products before the render loop. The inline ACF block in `card.php` was replaced by a single `WCPCE_ACF_Helper::get_card_data()` call; no other code was changed. The helper is loaded unconditionally at plugin bootstrap in `class-plugin.php`. Card output is byte-identical; no controls, ACF fields, or CSS were changed. All five Phase 6 helpers are now in place; the plugin is ready for the v2.0.0 milestone and the PDP Gallery widget.

= 1.0.83 =
Phase 6 R4: extracted image logic into a new stateless helper class `WCPCE_Image_Helper` (`includes/Helpers/class-image-helper.php`). Four static methods cover the image responsibilities previously split between the widget class and `card.php`: `prime_attachment_caches()` bulk-primes product meta, attachment post, and attachment metadata caches before the render loop; `build_sizes_attribute()` computes the dynamic `sizes` hint from column settings per breakpoint; `is_above_fold()` decides whether a card receives `fetchpriority=high` and eager loading for LCP optimisation; and `get_image_ids()` resolves the primary and secondary image IDs including the hover swap guard (H8) and the ACF `card_hover_image` → gallery[0] fallback priority. The helper is loaded unconditionally at plugin bootstrap in `class-plugin.php`. The private `prime_attachment_caches()` method was removed from the widget class; all three `card.php` inline image blocks were replaced by helper calls. Card output is byte-identical; no controls, ACF fields, or CSS were changed. This helper is a direct prerequisite for the PDP Gallery widget (PDP Phase 1).

= 1.0.82 =
Phase 6 R2: extracted product price logic into a new stateless helper class `WCPCE_Price_Helper` (`includes/Helpers/class-price-helper.php`). The private widget method `get_product_price_data()` moved here as a public static method and is now called as `WCPCE_Price_Helper::get_product_price_data()` from `compute_card_data()`. The method handles simple, variable, and grouped product types: for variable products it finds the variation with the highest percentage discount and uses its prices consistently for badge text, strikethrough, sale price, and savings line — preventing misleading "Bespaar €X" claims that could arise from mixing max-regular with min-sale across variations. A static `format_money()` alias is also included, mirroring the one on `WCPCE_Badge_Helper` so both helpers are standalone-usable (documented design choice in ROADMAP). The helper is loaded unconditionally at plugin bootstrap in `class-plugin.php`. Card output is byte-identical; no controls, ACF fields, or CSS were changed. This helper is a prerequisite for the PDP Price & Promo Block widget (PDP Phase 2).

= 1.0.81 =
Phase 6 R3: extracted stock-status logic into a new stateless helper class `WCPCE_Stock_Helper` (`includes/Helpers/class-stock-helper.php`). Three static methods cover the three distinct responsibilities that were previously inline in `card.php`: `is_out_of_stock()` wraps `WC_Product::is_in_stock()` with a typed bool return; `should_show_oos_visual()` decides whether the `wc-card--out-of-stock` modifier class should be applied (requires both an OOS stock status and the widget's "Show out of stock label" toggle to be on); and `get_stock_label()` initialises the stock label slot — visibility flag plus customisable label text — in a single call before `WCPCE_Badge_Helper::apply_badge_priority()` can mutate the flag via its by-reference parameter. The helper is loaded unconditionally at plugin bootstrap in `class-plugin.php`. Card output is byte-identical; no controls, ACF fields, or CSS were changed. This helper is a prerequisite for the PDP Gallery widget (PDP Phase 1), which reuses the OOS overlay treatment.

= 1.0.80 =
* First step of the Phase 6 refactor (preparing the codebase for multiple widgets). Extracted all badge logic into a new stateless helper class, `WCPCE_Badge_Helper`, in `includes/Helpers/class-badge-helper.php`. This is pure internal restructuring — the rendered product card output is byte-for-byte identical to v1.0.79.13. No new Elementor controls, no new ACF fields, no CSS changes, no behaviour change for editors or shoppers.
* Moved `format_badge_text()` and `format_money()` out of the widget class and onto the helper as public static methods. The widget class no longer contains badge-text or money-formatting logic.
* The discount-badge decision (show/hide plus text) inside `compute_card_data()` now delegates to `WCPCE_Badge_Helper::compute_badge_data()`. The gate is unchanged: the show-badge setting is on, the product is genuinely on sale with a positive discount, and the discount meets the configured threshold.
* In the card template, the ACF custom-badge flags (Nieuw, PFAS-vrij, Niet meer leverbaar), the top-left badge priority rule (discount badge wins over Nieuw), the Niet-meer-leverbaar suppression of competing badges and the stock label, the badge label defaults, and the accessible badge aria-label all now delegate to `WCPCE_Badge_Helper`. The suppression still mutates the card classes and stock-label visibility exactly as before (now via reference parameters).
* The helper is loaded unconditionally at plugin bootstrap so both the widget render path and the card template can rely on it. Why a helper now: the upcoming PDP Gallery widget reuses badge overlays (discount, Nieuw, PFAS-vrij) and the out-of-stock overlay, so this logic needs to live in one shared place rather than being duplicated per widget.

= 1.0.79.13 =
* Removed the WBW/Elementor sticky-header workaround from the plugin. Deleted `assets/js/wbw-elementor-sticky.js`, its `wp_register_script()` registration, and its entry in `get_script_depends()` (which is back to `array( 'wc-add-to-cart' )`). The sticky-header conflict is between Elementor Pro and WBW Product Filter PRO — both outside this plugin's responsibility — and the workaround contained hardcoded Bourgini-specific element IDs that did not belong in a reusable product-card plugin. The fix is preserved separately as a site-level PHP snippet for environments that still need it. The plugin no longer ships any JavaScript, consistent with the v1.0.54 architecture. The `wcpce-grid-section` container class, the WooCommerce-shaped `ul.products`/`li.product` markup, and all WBW container-targeting support are unchanged — only the sticky-header JS is gone.

= 1.0.79.12 =
* Sticky guard hardening. Replaced staging-specific Elementor element IDs with the stable custom class `wcpce-wbw-sticky-guard`, so the WBW/Elementor sticky-header fix also works when live and staging have different Elementor-generated IDs.

= 1.0.79.11 =
* Cleanup pass after live WBW testing. The Bourgini sticky-header guard now stays dormant on the initial page load and only activates after a WBW filter AJAX/update event. This keeps the fix scoped to the WBW/Elementor sticky conflict instead of altering normal header behaviour before filtering.

= 1.0.79.10 =
* WBW / Elementor sticky compatibility. Replaced the generic sticky reflow with a top-of-page guard for the Bourgini desktop sticky header rows that Elementor Pro incorrectly marks active after WBW Force Theme Templates AJAX updates. The guard only applies while the visitor is at the top of the page and automatically releases when the visitor scrolls, so normal sticky behaviour is preserved.

= 1.0.79.9 =
* WBW / Elementor sticky compatibility. Added a tiny frontend helper that listens for WBW Product Filter AJAX completion events and triggers resize/scroll reflow events for Elementor Pro sticky headers. This was superseded in v1.0.79.10 by a top-of-page guard after testing showed the sticky rows were being incorrectly marked active at scroll position 0.

= 1.0.79.8 =
* WBW styling hardening. The WooCommerce-compatible outer product list still renders as `ul.products.wc-card-grid` with `li.product` items, but the inner card `<article>` no longer carries the generic `product` class. This prevents WooCommerce/theme `ul.products li.product` rules from leaking onto the card itself after WBW AJAX replacement. Added a stronger reset for the card inside the WBW-compatible list item.

= 1.0.79.7 =
* WBW compatibility fix. The product grid now renders as `ul.products.wc-card-grid` with `li.product` items, matching WooCommerce's default product-list shape while preserving the existing card markup and styling. WBW Product Filter PRO defaults to `ul.products` and reloads the page when it cannot find the product block; this markup gives WBW a standard product-list target while keeping `.wcpce-grid-section` as the scoped Product Container Selector.

= 1.0.79.6 =
* WBW integration fix. Moved the stable `wcpce-grid-section` class from Elementor's outer widget wrapper to an internal product-results container that wraps the product grid, empty state, and pagination. This better matches WBW Product Filter PRO's Product Container Selector expectation: the selected container contains the product list and related navigation, but not the broader Elementor widget shell or page header. Removed the custom `before_render()` override entirely.

= 1.0.79.5 =
* Hotfix. Fixed the `before_render()` override added in v1.0.79.3. Elementor declares this method as public and uses it to print the widget wrapper; the plugin override was protected and did not call the parent method, causing a fatal method-signature conflict on load. The stable `wcpce-grid-section` class is still added before the wrapper is printed.

= 1.0.79.4 =
* Bug check and hardening pass. Aligned the manual-mode "Number of products" Elementor control max value with the existing server-side validation clamp (was 100 in the UI, 48 in validation; both are now 48). Removed a leftover debug HTML comment that was being emitted on every page with pagination (`<!-- WCC pagination builder v1.0.73-... -->`) along with two unused variables (`$source`, `$mode`) in `render_pagination()`. Pagination link hrefs now use `esc_url()` instead of a local `htmlspecialchars` lambda, matching WordPress conventions and protecting against unusual URL protocols. The lambda and its three `phpcs:ignore` directives are gone.
* Type hints and convention fixes. `render_card()` now has explicit `\WC_Product $product` and `array $settings` type hints, matching every other private method in the widget class. `WC_Product_Card_Elementor_Plugin::instance()` and `WC_Product_Card_Elementor_ACF_Fields::register()` now declare return types. Nine Elementor control defaults that previously used `esc_html__()` were changed to `__()`, per the project convention that strings going through `esc_html()` at output must not be pre-escaped at the source: `empty_state_text`, `shipping_label`, `out_of_stock_label`, `action_label_view`, `action_label_add_to_cart`, `action_label_options`, `badge_nieuw_label`, `badge_pfas_label`, `badge_niet_leverbaar_label`.
* Performance. `prime_attachment_caches()` now also bulk-primes the product meta cache before iterating, so the `get_post_meta()` reads in both this method (for `card_hover_image`) and `card.php` (for all ACF fields) hit the cache instead of triggering individual DB queries per product. On a cold cache this collapses up to N+1 separate meta queries into a single bulk query for a grid of N products.
* Documentation. The widget class docblock previously stated the discount badge supports four formats; it actually supports three (percent, amount, smart). Corrected. The main plugin file now declares `Tested up to: 6.7` alongside `Requires at least: 6.0`, matching the value in readme.txt.

= 1.0.79.3 =
* Added a stable `wcpce-grid-section` class to the Elementor widget wrapper element. The Elementor-generated element IDs (`.elementor-element-XXXXXXX`) are unique per widget instance and differ across pages and shops, which made it impractical to use them as a target selector for third-party plugins like WBW Product Filter PRO. This implementation was superseded in v1.0.79.6, which moved the same class to an internal product-results container.

= 1.0.79.2 =
* Removed the hardcoded opacity (0.82) from the Lipscore rating slot. The opacity was originally added as a subtle visual treatment but it muted the star colours independently of the CSS filter control introduced in 1.0.79.1. Stars now render at full opacity by default, matching Lipscore's native colours. The hover opacity rule (1.0) has also been removed as it is no longer needed.

= 1.0.79.1 =
* Added a Rating style section in the Elementor Style tab (visible only when "Show Lipscore rating" is enabled). A new "Apply CSS filter to stars" switcher (default off) lets editors opt in to colour adjustment. When enabled, a "Filter intensity" slider (0–200, default 100) sets a combined saturate/brightness CSS filter on the Lipscore star element — 100 is neutral, below 100 mutes the stars, above 100 makes them more vivid. The previously hardcoded filter (saturate 80% / brightness 95%) has been removed from the stylesheet; native Lipscore colours now show by default.

= 1.0.79 =
* Accessibility (H13): Added aria-label to the current page pagination element. Previously the current page span only had aria-current="page" but no descriptive label, so screen readers announced only the page number without context. The element now includes aria-label="Huidige pagina, pagina N" so users hear the full context when navigating by landmarks or list items. This completes Phase 5 of the roadmap.

= 1.0.78 =
* Performance (H10): The image sizes attribute is now computed dynamically from the configured column counts per breakpoint rather than using a hardcoded approximation. Previously sizes was always "(max-width: 1024px) 50vw, 33vw", which assumed 2 tablet columns and 3 desktop columns regardless of what the editor had set. The hint is now built from the actual columns, columns_tablet, and columns_mobile settings (with fallback to the control defaults of 3, 2, 2). A 4-column desktop grid now correctly hints 25vw; a 6-column grid hints 17vw. Breakpoints match Elementor defaults (767px mobile, 1024px tablet). The above-the-fold fetchpriority=high calculation is also updated to use the actual desktop column count rather than the hardcoded value of 3, so LCP images are marked correctly when the column count differs.

= 1.0.77 =
* Performance (H9): get_variation_prices() is now only called when at least one discount-dependent feature is enabled (discount badge or savings line). Previously, compute_card_data() always called get_product_price_data() regardless of widget settings, which in turn called get_variation_prices() for every variable product — the most expensive WooCommerce pricing call, as it traverses all active variations. When both the badge and savings line are off, variable products now use get_price() instead (a single cached meta read), and display_price is populated from that. The shipping pill threshold and overlay link aria-label both fall back to display_price, so their behaviour is unchanged for non-sale products. No visible change when badge or savings line is enabled.

= 1.0.76 =
* Accessibility (H6): Stock status is now included in the overlay link accessible name. Screen reader users navigating by links now hear "Bekijk [product] - [price], Tijdelijk uitverkocht" or "Bekijk [product] - [price], Niet meer leverbaar" without having to enter the card. Niet meer leverbaar takes priority over Tijdelijk uitverkocht, consistent with the visual badge hierarchy.
* Performance (H8): Hover image resolution in card.php is now skipped entirely when hover swap is disabled in widget settings. Previously, get_gallery_image_ids(), the ACF card_hover_image lookup, and wp_attachment_is() all ran unconditionally on every card even when hover swap was off. These calls are now wrapped in a guard so they only execute when hover swap is actually enabled. Consistent with the existing conditional in prime_attachment_caches().
* Accessibility (H11): The hover-swap crossfade is now disabled under prefers-reduced-motion. Previously the reduced-motion block covered hover lift and button transforms but left the image crossfade active. The secondary image now stays hidden (opacity: 0) and the primary image stays fully visible when the user has requested reduced motion at the OS level.

= 1.0.75 =
* Hardening (H1): Added validate_manual_settings() — a new private method called at the top of run_manual_query() that sanitises all manual-mode widget settings before query execution. Settings are saved by authenticated editors and stored as Elementor JSON in wp_postmeta, so this is not a live user input attack surface. The validation guards against corrupted database values, direct postmeta edits, and import/export anomalies. Specifically: limit is clamped to 1–48; orderby is checked against the registered SELECT whitelist and falls back to menu_order; filter_stock is checked against its three allowed values and falls back to any; the three SWITCHER fields (filter_sale_only, filter_featured_only, enable_pagination) are coerced to yes or empty string; category is sanitised to an array of positive integers; include_ids and exclude_ids are sanitised token-by-token via absint(), zeros dropped, and capped at 200 entries each. No behavioural change when settings contain valid values.

= 1.0.74 =
* Cleanup: Replaced esc_html__() with __() on the "Tot " prefix in format_badge_text(). The badge text is escaped at output via esc_html() in the card template, so pre-escaping at the source would double-encode any special characters if the string were ever changed. No visible change at the current default value, but the codebase now matches the project convention that says strings passed through esc_html() at output must not be pre-escaped at the source.
* Stability: No functional changes. v1.0.73 has been confirmed stable in audit; this release exists to keep the codebase aligned with the documented conventions before further work proceeds.

= 1.0.73 =
* Cleanup: Updated the Auto mode editor notice and documentation after removing the products-per-page override. Auto mode now clearly states that product count, sorting and pagination are controlled by WooCommerce, the theme, Customizer settings or filter plugins.
* Safety: No functional query changes compared with 1.0.72. The loop_shop_per_page override remains removed and the namespaced pagination classes remain in place.

= 1.0.72 =
* Fix: Removed the global loop_shop_per_page override so WooCommerce archive product counts are no longer changed by Woo Card Chef. Shop, category, tag and filtered archive result counts now remain controlled by WooCommerce, the theme, Customizer settings or dedicated filter plugins.
* Fix: Removed the Auto mode products-per-page override control. Auto mode now follows the existing WooCommerce main query; Manual mode still uses its own Number of products setting for custom widget queries.
* Safety: Confirmed no pre_get_posts, woocommerce_product_query or other global posts_per_page hooks are registered by the plugin.

= 1.0.71 =
* Feature: Added pagination support for Auto source mode using the main WooCommerce archive query's native paged/max_num_pages values and archive page URLs.
* Safety: Kept Manual source pagination on the working namespaced wcpce_paged URL builder so the page 1/previous link fix remains isolated.

= 1.0.70 =
* Maintenance: Kept the working namespaced manual pagination fix, centralized the path-only pagination URL builder, and removed stale debug helper code that was no longer used by the renderer.

= 1.0.69 =
* Debug/Fix: Bypassed the existing pagination URL helper entirely and builds manual pagination hrefs inline from the current request path only.
Fix: Rebuilt pagination href output with a minimal path based URL builder so links no longer inherit the current wcpce_paged value.

= 1.0.62 =
* Fix: Switched manual pagination query string generation to native http_build_query and added target page markers to verify each rendered pagination link uses its own target page.

= 1.0.61 =
* Fix: Rebuilt manual pagination URLs from the current request path only, so page 1 and previous links cannot inherit stale wcpce_paged values from permalink, archive or filter-plugin URL helpers.

= 1.0.59 =
* Bug fix: Hardened manual pagination URL generation so page-1 and previous links cannot inherit the current wcpce_paged value. Pagination state is now stripped from both the base URL and the preserved query args before any target page link is built, including amp-prefixed query-key variants.

= 1.0.58 =
* Bug fix: Pagination page-1 and previous links no longer inherit the current wcpce_paged value. The pagination base URL is now built explicitly from the current product taxonomy, shop page, singular page, or product archive, with existing filter/sort query args preserved and wcpce_paged/paged always stripped before links are generated.

= 1.0.57 =
* Bug fix: Simplified pagination URL building. Replaced the multi-branch is_product_taxonomy/is_shop/is_singular logic and the $_GET preservation block with a single call to get_pagenum_link( 1, false ) — WordPress's own helper for getting the canonical URL of page 1 of the current query. This avoids any reliance on $_SERVER['REQUEST_URI'] (which produced wrong results on Kinsta) and avoids the complex query-arg juggling that previously failed silently. Page 1 links now correctly point to the canonical archive URL without wcpce_paged.


= 1.0.56 =
* Bug fix: Pagination base URL is now built explicitly via get_term_link() on category archives, get_permalink( wc_get_page_id( 'shop' ) ) on the shop page, and get_permalink() on singular pages, instead of relying on remove_query_arg() with $_SERVER['REQUEST_URI']. The previous implementation produced broken page-1 and prev links on Kinsta staging despite logically correct PHP, suggesting REQUEST_URI was unreliable behind the reverse proxy or in combination with some caching layer. The new approach uses WordPress's canonical URL helpers which do not depend on REQUEST_URI. Other query args from the current request (WBW filter params etc.) are preserved by re-appending them via add_query_arg().


= 1.0.55 =
* Bug fix: Pagination links pointed to the current page instead of the target page. The previous implementation used paginate_links() which has known issues with custom query vars: for page 1 (and the previous link when current=2), the function replaces the %_% placeholder with an empty string, leaving the link without our wcpce_paged param. The current URL's wcpce_paged value then leaks back into the link via paginate_links()' internal add_args handling, making "page 1" effectively point to "page 2" when viewed from page 2.
* Replaced paginate_links() with a custom render_pagination() implementation. URLs are now constructed explicitly via add_query_arg() / remove_query_arg() with no placeholder substitution: page 1 always strips wcpce_paged entirely (clean canonical URL), other pages get wcpce_paged=N. The same end_size=1, mid_size=2 visible-page logic is preserved, including ellipsis gaps. Prev/next chevron SVGs and aria-labels are unchanged.


= 1.0.54 =
* Major refactor: removed AJAX pagination and the Load More button entirely. Pagination in manual mode now uses standard server-rendered numbered links with native browser navigation — no more JS, no more AJAX endpoint, no more nonce management. This eliminates the entire class of AJAX/race-condition/state-sync bugs and clears the way for proper auto-mode support coming in 1.0.55.
* Removed: pagination_type Elementor control (no longer needed — always numbered).
* Removed: load_more_label and load_more_all_loaded_label Elementor controls.
* Removed: pagination_scroll_offset Elementor control (was only meaningful with AJAX scroll-to-top).
* Removed: assets/js/pagination.js entirely.
* Removed: ajax_load_page() handler in class-plugin.php.
* Removed: ajax_render_cards(), sanitize_ajax_settings(), and get_ajax_safe_settings() methods in the widget class.
* Removed: wcpce_paged_inject parameter handling in run_manual_query().
* Removed: data-wcpce-* attributes on the grid element (data-wcpce-pagination, data-wcpce-page, data-wcpce-max, data-wcpce-type, data-wcpce-settings).
* Removed: data-wcpce-for attribute on pagination elements.
* Removed: aria-live status region and aria-busy attribute handling (added in 1.0.50 and 1.0.53 for AJAX status announcements — no longer applicable).
* Removed: all .wcpce-load-more-*, .wcpce-grid--loading, .wcpce-card-fadein, .wcpce-spin keyframes from product-card.css.
* Kept: .wcpce-sr-live CSS class for future use.
* H4 fix preserved: duplicate title IDs across multiple widget instances on one page are still avoided. The title ID now uses the Elementor widget ID instead of the grid ID, with identical results: wcpce-title-{widget_id}-{product_id}.


= 1.0.54 =
* Historical: Added a "Products per page (override)" control for Auto mode. This global WooCommerce archive override was later removed in 1.0.72 because Woo Card Chef should not control WooCommerce archive product counts by default.


= 1.0.53 =
* Accessibility (H5): AJAX pagination now announces status updates to screen readers via the aria-live region introduced in 1.0.50. Numbered pagination announces "Pagina X van Y geladen." after each successful page change. Load more announces how many products were added ("N producten geladen.") after each successful fetch, and announces the all-loaded label or "Geen producten meer beschikbaar." when exhausted. Both modes now also set aria-busy="true" on the grid during the fetch and remove it on completion or error, so screen readers can signal that the region is updating.


= 1.0.52 =
* Bug fix (H4): Fixed duplicate HTML IDs when the same product appears in two separate widget instances on one page. The title ID used for aria-labelledby linkage between the article and h3 is now wcpce-title-{grid_id}-{product_id} instead of wcpce-title-{product_id}. Duplicate IDs are invalid HTML and caused the aria-labelledby on the second instance to reference the wrong heading. Falls back to product ID only in auto mode where grid_id is empty.


= 1.0.51 =
* Bug fix: sanitize_ajax_settings() now always returns category as an array, even when only one term ID is present. Previously a single valid category was returned as a scalar integer, which caused run_manual_query() to skip the category filter entirely (is_array() check failed), resulting in all products being shown on AJAX-loaded pages 2 and beyond.


= 1.0.50 =
* Bug fix (H2): Manual mode queries now respect the WooCommerce "Hide out of stock items" catalog setting (WooCommerce → Settings → Products). Previously, products marked as out of stock in the product_visibility taxonomy were always shown in manual-mode grids regardless of this setting. A NOT IN clause on the outofstock term is now added whenever the option is enabled. The same fix applies to the editor fallback query. Note: if the widget's own stock filter is set to "Out of stock only" while this WC setting is also on, the query will return zero results — the site-wide WC setting takes precedence.
* Bug fix (H3): Load more button now shows a visible error message ("Producten laden is niet gelukt. Probeer het opnieuw.") when the AJAX request fails, instead of silently resetting the button. The error is announced to screen readers via a new visually-hidden aria-live="assertive" region rendered next to each paginated grid. The error message is cleared automatically on the next successful load. The button remains enabled so the user can retry.

= 1.0.49 =
* Security hardening (H1): Added server-side validation of the AJAX settings payload in the pagination handler. A new sanitize_ajax_settings() method in the widget class rebuilds the settings array from scratch, discarding any unknown keys and validating every known key against the same constraints defined in the Elementor controls. Select fields are checked against explicit whitelists (orderby, filter_stock, pagination_type, badge_format, action_type, image_size), numeric fields are clamped to their allowed ranges (limit 1–48, badge_threshold 0–100, usp_count 1–3, shipping_threshold 0–1000), category term IDs are validated against the product_cat taxonomy via term_exists(), include/exclude ID lists are reduced to positive integers only, all boolean SWITCHER controls are restricted to yes/no, and all free-text label fields are passed through sanitize_text_field(). This prevents a tampered JS payload from injecting arbitrary values into the query or card rendering logic.


= 1.0.48 =
* Pre-launch hardening release. Code, design, and performance polish based on a full plugin audit.
* Bug fix (L4): Manual-query transient cache now stores product IDs and pagination metadata only, not the full WC_Product objects. Caching the objects was fragile — they can hold closures or runtime-only state that doesn't survive serialise/unserialise. On a cache hit the IDs are hydrated back through wc_prime_caches_for_products() and is_visible() so the result is identical to a cold query.
* Bug fix (L5): Replaced the inline Elementor editor check in run_manual_query() with the existing $this->is_elementor_editor_or_preview() helper. The previous check would have thrown a fatal if Elementor's Plugin::$instance was null in some lifecycle moments.
* Bug fix (L7): uninstall.php now flushes all wcpce_q_* transients and deletes the wcpce_query_cache_keys option. Previous note that "this plugin stores nothing in options" became inaccurate when the transient cache was added in 1.0.46.
* Design (D1): Discount badge box-shadow changed from green-tinted (rgba(62,194,109,0.25)) to neutral dark (rgba(0,0,0,0.18)). The shadow now matches the rest of the card's overlay indicators and stays correct when the badge color is changed via the Elementor controls.
* Design (D2): The wc-card-badge-pos-top-right CSS modifier no longer flips the "Tijdelijk uitverkocht" stock label from bottom-right to bottom-left. The discount badge and the stock label live in independent corners and shouldn't move together.
* Design (D9): Permanently unavailable products ("Niet meer leverbaar" ACF flag) now suppress both the discount badge and the Nieuw badge. Showing "20% off" on top of "Niet meer leverbaar" is contradictory and undermines the unavailability message.
* Performance (P-A + P-B): Bulk-prime the WP attachment cache before the card render loop. New private method prime_attachment_caches() collects every featured-image, gallery, and card_hover_image ID up front and primes both the post cache (_prime_post_caches) and the metadata cache (update_meta_cache) in two bulk queries. Without this, wp_get_attachment_image() and wp_attachment_is() hit the database per attachment per card — for a 12-card grid that's 24-48 individual queries on a cold cache. The visible win is most noticeable on Kinsta staging or first-page-load scenarios where object cache is cold.

= 1.0.47 =
* Feature (C3): Editors can now set a custom hover image per product via a new ACF field "Hover-afbeelding voor productkaart" (field_wcpce_card_hover_image) in the Product Card Title group. When set, this image takes precedence over the first WooCommerce gallery image for the hover swap. The field uses return_format=id so only an attachment ID is stored; the template validates it with wp_attachment_is() to guard against stale IDs from deleted media. Only active when "Hover image swap" is enabled in the Elementor widget.
* Code quality (B11): $card['index'] is now set inside compute_card_data() instead of being tacked on afterwards in render_card(). The method signature gains a third parameter int $index = 0. All card data is now consistently computed in one place.
* Code quality: ACF data is now loaded before the image logic in card.php so card_hover_image is available when determining the secondary image source.

= 1.0.46 =
* Accessibility (A1): Each product card <article> now carries aria-labelledby pointing to its <h3> title element. Screen readers that navigate by landmarks or list items now announce the product name without requiring the user to enter the card. The h3 has a matching id (wcpce-title-{product_id}) that is unique per card even with multiple widget instances on the same page.
* Accessibility (A2): The product grid now has role="list" and each card article has role="listitem". This is required for VoiceOver on Safari, which strips list semantics from elements with list-style: none in CSS. Screen reader users can now navigate the grid as a proper list and hear the item count.
* Accessibility: aria-hidden="true" removed from .wc-card__body. The body content (price, USPs, shipping pill) is now readable by screen readers. The overlay link retains its aria-label (product name + price) for users who navigate by links only — the two announcements serve different navigation modes and do not conflict.
* Feature (D2): New "Shadow on hover" group control in the Card Style section (Elementor Style tab). Allows independent control of the card's resting shadow and its hover/focus-visible shadow. The existing hardcoded hover shadow remains as a CSS default when the control is left blank.
* Performance (P1): get_price_html() is now called once per card in compute_card_data() and its result is cached in $card['price_html']. The template reads this cached value for both the price block and the overlay aria-label, eliminating a second invocation of WC's full filter chain per card — particularly significant for variable products where this internally calls get_variation_prices().
* Performance (PF1): Manual-mode queries are now cached as transients for 5 minutes. The cache key is an MD5 hash of the fully-resolved WP_Query args, so different widgets with different settings get separate cache entries. The cache is flushed immediately when any product is saved (save_post_product), so product edits are always reflected without waiting for the TTL. Random order queries and Elementor editor previews are excluded from caching.

= 1.0.45 =
* Bug fix: "Nieuw" badge inherited the Discount Badge Style color, border-radius, and typography controls from the Elementor panel when a discount badge was also active on the same card. The four badge style selectors now exclude .wc-card__badge--nieuw in addition to .wc-card__badge--pfas, so the Nieuw badge always uses its hardcoded brand red (#B4211C) regardless of the discount badge color setting.
* UX: Scroll offset control (pagination) is now responsive — desktop, tablet, and mobile values can be set independently in the Elementor editor. The value is passed via a --wcpce-scroll-offset CSS custom property on the grid so the JS always reads the breakpoint-correct value via getComputedStyle. The data-wcpce-scroll-offset attribute and its entry in get_ajax_safe_settings() are removed as they are no longer needed.

= 1.0.44 =
* Bug fix (critical): AJAX pagination links pointed to home_url() instead of the actual category/page URL. After clicking a numbered link, the server-rendered nav replacement contained links like "/?wcpce_paged=N" which navigated to the homepage on subsequent clicks. The JS now sends current_url with the AJAX request (window.location.href stripped of any wcpce_paged param), the server validates it against the home host, and uses it as the pagination base. Falls back to home_url('/') only if the URL is missing or off-host.
* Bug fix: Pagination nav returned by the AJAX endpoint had no data-wcpce-for attribute because ajax_render_cards() called render_pagination() with an empty grid_id. After the first AJAX page change the JS fell back to nextElementSibling lookup, which is fragile when Elementor injects wrappers. The JS now sends grid_id with the request, the server validates it matches the wcpce-grid- prefix, and the rendered nav carries the correct data-wcpce-for attribute.
* Bug fix: pagination_scroll_offset (added in 1.0.43) was missing from the get_ajax_safe_settings() whitelist. Not visibly broken but inconsistent with the convention that all render-relevant settings ride along in the AJAX payload.
* Bug fix: Defaults in card.php used esc_html__() while the same value was passed through esc_html() at output, double-encoding any apostrophes or HTML entities if a default was ever changed to include them. Affected: out_of_stock_label, shipping_label, and the three action button labels (In winkelwagen, Kies opties, Bekijk product). Now use __() consistently — escaping happens once at output.
* Bug fix: render_empty_state() called sanitize_text_field() and then esc_html() on the same value, double-processing the editor-supplied empty state text. Removed the redundant sanitize_text_field call.
* Bug fix: shipping_threshold in card.php read via isset() ternary instead of the project's null coalesce convention. Switched to floatval( $settings['shipping_threshold'] ?? 25 ).
* Security: AJAX endpoint now rejects settings payloads larger than 8KB and decodes JSON with an explicit depth limit of 16, preventing a malicious authenticated client from sending an oversized or deeply nested payload to waste server resources.
* Security: current_url is validated by parsing the host and comparing case-insensitively against the home_url() host. Off-host values are silently dropped to home_url('/'). Prevents a tampered client from injecting an arbitrary host into the rendered pagination nav.
* Performance: Replaced get_fields() in card.php with a single get_post_meta($product_id) call followed by direct lookups for the seven keys we actually use (card_title, usp_1/2/3, badge_nieuw, badge_pfas_vrij, badge_niet_leverbaar). On products with many ACF field groups this is a measurable saving per card render.
* Performance: Skipped get_price_html() for the overlay aria-label on simple non-sale products. Now uses wc_price($display_price) directly when possible. get_price_html() runs through WC's full filter chain and is the most expensive call on a typical card.
* JS: AbortController now cancels any in-flight pagination request when a newer one starts. Rapid clicks on different page numbers no longer cause stale responses to overwrite newer ones.
* Note: ACF field reads (card_title, USPs, badges) now use direct postmeta. If you ever attach ACF formatting filters to these specific fields, those filters will no longer run on this widget. The fields are owned by this plugin and not used elsewhere, so this is intentional.

= 1.0.43 =
* UX: Added prev/next arrow buttons (‹ ›) to numbered pagination. Buttons are hidden automatically when there is no previous or next page.
* UX: Added history.pushState after each AJAX page change so the browser URL updates to ?wcpce_paged=N and the page is shareable and bookmarkable.
* UX: Added popstate event handler so browser back/forward navigation correctly reloads the right page via AJAX without a full page refresh.
* UX: Added hover scale animation (transform: scale(1.05)) on pagination numbers and arrows. Dots are excluded from the scale animation.
* Code: Refactored numbered pagination click handler and popstate handler to share a single loadNumberedPage() function.

= 1.0.42 =
* Bug fix (critical): Wrong text domain in load_plugin_textdomain() — function was called with 'wc-product-card-elementor' but the plugin header declares 'woo-card-chef'. Translations were never loaded. Fixed to use 'woo-card-chef'.
* Bug fix (critical): render_pagination() used remove_query_arg() to build pagination URLs, which in an AJAX context resolves to admin-ajax.php instead of the actual page URL. Fixed by using home_url('/') as the base when DOING_AJAX is true (the JS replaces the nav with fresh server HTML anyway, so absolute correctness only matters for the initial page load).
* Bug fix: data-wcpce-grid-id / data-wcpce-for attributes were never output in PHP, making the JS findPaginationEl() grid-ID lookup dead code that always fell through to the fragile nextElementSibling fallback. Fixed: grid now gets an id="wcpce-grid-{widget-id}" attribute, pagination elements get data-wcpce-for="{grid-id}", and JS reads grid.id to locate pagination elements reliably.
* Bug fix: wp_json_encode() can return false if settings contain unencodable values. The data-wcpce-settings attribute would then be empty and the JS would silently fail. Fixed with an explicit false check, falling back to '{}' so the JS can detect the failure instead of crashing.
* Bug fix: load_more_label and load_more_all_loaded_label were missing from get_ajax_safe_settings() — custom button text was always ignored in AJAX responses, defaulting to hardcoded Dutch strings.

= 1.0.41 =
* Bug fix: duplicate require_once in ajax_load_page() — the widget class file was loaded twice per AJAX request. The redundant unconditional require_once after the class_exists check is removed.
* Bug fix: numbered pagination nav update after AJAX was fragile — the JS tried to surgically patch individual <a>/<span> elements including dots, which broke on edge cases. Now the server returns fresh pagination_html with every AJAX response and the JS replaces the entire nav element.
* Bug fix: pagination element lookup via nextElementSibling was fragile when Elementor injects wrapper elements. Now uses data-wcpce-for attribute with nextElementSibling as fallback.
* Bug fix: unused variable maxPages in initLoadMore() removed.
* Bug fix: numbered pagination event listener now uses delegation on the nav's parent so it keeps working after nav replacement.
* Code: added missing return types (void) to register_widget_category() and register_widget() in class-plugin.php.
* Code: restored missing docblock description on render() method.
* Code: ajax_render_cards() now always runs even when products is empty, returning empty html string with correct pagination state.

= 1.0.40 =
* Phase 3 (P2/P3): Added AJAX pagination and Load more button. New pagination_type control (numbered / load more). Load more appends products to the existing grid without a page reload. Numbered pagination now also works via AJAX — grid replaces in-place, page links update, grid scrolls into view. Numbered links remain as noscript/fallback when JS is disabled or AJAX fails. New assets/js/pagination.js handles all client-side logic. Nonce-secured wp_ajax_wcpce_load_page endpoint added. Fade-in animation on newly loaded cards. Loading spinner on Load more button during fetch.
* Phase 3 (P5): Added scroll-to-grid on numbered AJAX page change.

= 1.0.39 =
* Bug fix: pagination links always pointed to the current page instead of the clicked page number. Root cause: add_query_arg() URL-encodes the %#% placeholder that paginate_links() uses for page number substitution, turning it into %25%23%25 which paginate_links() never matches. Fixed by building the base URL manually using remove_query_arg() + direct string concatenation of the raw ?wcpce_paged=%#% placeholder.

= 1.0.38 =
* Bug fix: pagination page 1 link was not navigable from page 2+. Root cause was that paginate_links() generates a clean URL (no wcpce_paged param) for page 1 but the query reads wcpce_paged=1 as page 1 — WordPress then didn't recognise the two as the same page and wouldn't generate a clickable link. Fixed by consistently using wcpce_paged=1 in all page 1 links so the URL always matches what the query reads. Also explicitly set end_size=1 and mid_size=2 to prevent any ambiguity in the link range calculation.

= 1.0.37 =
* Bug fix: early returns in run_manual_query() (sale-only filter with no sale products, Q4 empty ID intersection) were returning a bare array() instead of the structured array the render() method expects after the v1.0.36 pagination refactor. This caused a PHP notice and broke the empty state rendering in those cases.
* Bug fix: render_pagination() was using get_pagenum_link(1) as the base URL, which is designed for archive-style /page/N/ URLs and produces incorrect results on regular Elementor pages. Now uses remove_query_arg() + add_query_arg() on the current request URL.
* Code: removed duplicate docblock on get_products() left over from the v1.0.36 refactor.
* Code: added int typehint to run_fallback_query() $limit parameter for consistency with the typed-params convention.
* Code: restored missing @description line in render_empty_state() docblock.

= 1.0.36 =
* Phase 3 (P1): Added numbered pagination for manual mode. Enable via the new "Enable pagination" toggle in the Query section (manual mode only). Uses wcpce_paged as the URL query var (?wcpce_paged=2) to avoid conflicts with the main WooCommerce archive loop. Number of products becomes the per-page limit when pagination is on. Style controls (active/inactive/hover colors and typography) available in the Style tab under Pagination.

= 1.0.35 =
* Mobile: PFAS-vrij badge is now hidden on mobile (max-width: 767px). On narrow 2-column cards the badge created visual overload without adding meaningful value at that viewport size.
* Mobile: sale and Nieuw badge font-size reduced from 13px to 11px and padding tightened to better fit narrow cards.

= 1.0.34 =
* Design consistency: shipping pill border-radius changed from pill (100px) to 6px to match badges and stock label. CSS fallback and Elementor control default both updated. Control description updated to reflect the new default.

= 1.0.33 =
* Design consistency: PFAS-vrij badge border-radius changed from pill (100px) to 6px to match the sale, Nieuw, and stock label badges. All overlay indicators now share the same corner radius.
* PFAS-vrij badge color updated from #2a6e46 to #57664d.

= 1.0.32 =
* Renamed plugin to Woo Card Chef by S15 Webdesign.
* Bug fix: PFAS-vrij badge was showing in the discount badge color instead of its own dark green (#2a6e46). All four badge style selectors (background, text color, border radius, typography) now use :not(.wc-card__badge--pfas) to exclude the PFAS badge from discount badge styling.

= 1.0.31 =
* Renamed Elementor widget category to Woo Card Chef.


= 1.0.30 =
* Bug fix (B1): "Niet meer leverbaar" badge onderdrukking van het tijdelijk uitverkocht label werkte niet. De $show_stock_label variabele werd na het niet_leverbaar blok opnieuw geïnitialiseerd waardoor de onderdrukking werd overschreven. Volgorde gecorrigeerd: stock label wordt nu eerder geïnitialiseerd zodat het niet_leverbaar blok hem daarna definitief kan uitzetten.
* Bug fix (B2): $out_of_stock_label is nu altijd gedeclareerd, ook als $show_oos_visual false is. Voorkomt een mogelijke PHP undefined variable notice.
* Bug fix (B3): onnodige aria-label verwijderd van de PFAS-vrij badge. De zichtbare tekst is al toegankelijk voor screenreaders — een aria-label op de container overschrijft de zichtbare tekst onnodig.
* Efficiëntie (E1/E2): badge label waarden worden nu één keer gecached in variabelen in plaats van bij elke render twee keer uit het settings array gelezen.
* Efficiëntie (E3): count() in de USP loop vervangen door een teller variabele. Voorkomt herhaalde array telbewerkingen per iteratie.
* Efficiëntie (E4): file_exists() check voor het template pad gebruikt nu een static variabele zodat de filesystem check slechts één keer per request wordt uitgevoerd in plaats van per kaart.
* Redundantie (R1): $card_title_acf tussenvariabele verwijderd, directe toewijzing aan $title.
* Redundantie (R2): $badge_nieuw_acf, $badge_pfas_acf en $badge_niet_leverbaar_acf tussenvariabelen verwijderd, inline checks.
* Redundantie (R3): editor notice voor ontbrekende ACF controleert nu get_fields() (meervoud) consistent met hoe ACF overal in het plugin gebruikt wordt in plaats van get_field().
* Redundantie (R4): width en height verwijderd uit $allowed_svg whitelist — sprite-reference SVGs gebruiken deze attributen niet.
* CSS (R5): comment toegevoegd bij top: auto op .wc-card__badge--pfas om te verklaren waarom dit nodig is.

= 1.0.29 =
* Title clamp default changed from 2 to 3 lines, both desktop and mobile. Bourgini product titles are typically long enough that 2 lines causes frequent truncation. 3 lines gives the full title a fair chance to show without the ellipsis. The setting remains configurable via the Elementor "Title clamp (lines)" control in the Layout section (1–5 lines, responsive). CSS fallback values updated to match.

= 1.0.28 =
* Phase 2: Added three new product badge types, each controlled per product via ACF toggle fields and per widget via Elementor toggles.
* "Nieuw" badge: red (#B4211C) pill, top-left position. Only shows when no discount badge is active — the discount badge always takes priority at that position. ACF field: badge_nieuw (true/false). Widget toggle: "Toon Nieuw badge".
* "PFAS-vrij" badge: dark green (#2a6e46) pill with uploaded leaf icon, bottom-left position. Never conflicts with other badges since it has its own dedicated corner. ACF field: badge_pfas_vrij (true/false). Widget toggle: "Toon PFAS-vrij badge".
* "Niet meer leverbaar" badge: black semi-transparent overlay centered over the full image area. Also applies the same dimmed + grayscale image treatment as tijdelijk uitverkocht. Suppresses the tijdelijk uitverkocht label when both are active since niet meer leverbaar is the stronger message. ACF field: badge_niet_leverbaar (true/false). Widget toggle: "Toon Niet meer leverbaar badge".
* ACF: added new field group "Product Card Badges" (group_wcpce_product_badges) with three true/false fields rendered as toggle switches on the product edit screen sidebar. Position: side panel, menu_order 5.
* SVG sprite: added wcpce-icon-leaf symbol using the uploaded leaf-icon-35699.svg path, cleaned of Inkscape metadata and converted to currentColor fill.
* Each badge type has a configurable label text in the Elementor widget controls.
* Badge priority matrix: (1) korting linksboven, (2) nieuw linksboven als geen korting, (3) PFAS-vrij altijd linksonder, (4) tijdelijk uitverkocht altijd rechtsonder, (5) niet meer leverbaar gecentreerd — verdringt tijdelijk uitverkocht label.

= 1.0.27 =
* Updated color defaults based on UX research and brand color hierarchy analysis.
* Sale price color changed from brand green (#3EC26D) to Bourgini red (#B4211C). Red is the universal convention for reduced prices and creates urgency. Using the same green as badges and buttons made the sale price compete with too many other elements. #B4211C on white passes WCAG AA at 6.2:1.
* Savings line color changed from brand green (#3EC26D) to dark neutral (#2a2a2a). The savings line is supporting information, not a primary CTA. Neutral color removes one of the seven competing green elements on a single card.
* Shipping pill text color changed from brand green (#3EC26D) to accessible dark green (#1e7a3a). The previous color on the light green background (#eef8f2) had a contrast ratio of ~3.7:1, failing WCAG AA for normal text. #1e7a3a on #eef8f2 passes at 5.1:1.
* USP text color changed from #6b6b6b to #5a5a5a. Previous value was borderline WCAG AA at 13px. #5a5a5a gives 5.7:1 contrast — clearly passes and still reads as secondary information.
* Strikethrough price color changed from #999999 to #888888. #999999 on white was 2.85:1, failing WCAG AA. #888888 gives 3.5:1 — de-emphasized but readable.
* CSS fallback values updated to match all new defaults.

= 1.0.26 =
* Changed image area max height default from empty (no constraint) to 240px. On a 3-column 1440px desktop grid the unconstrained square image area was ~440px tall, pushing prices and USPs below the fold on typical laptop viewports. 240px targets a total card height of ~520px which keeps the first product row fully visible without scrolling. Existing saved widgets where max height was explicitly set are unaffected — Elementor only applies the default to controls that have never been saved.
* Changed image padding default from 16px to 8px. At the new 240px image height, 16px padding consumed a disproportionate amount of the available image area. 8px gives products better visual room while remaining compact.
* Updated CSS fallback values to match the new defaults.

= 1.0.25 =
* Bug fix: discount badges and stock labels no longer float above the sticky site header when scrolling. The widget's absolutely positioned elements were escaping the widget's stacking context and rendering above site-level elements. Fixed by adding isolation: isolate to the Elementor widget wrapper (.elementor-widget-wc_product_card), which creates a self-contained stacking context without changing any internal z-index values.

= 1.0.24 =
* Added "Image area max height" slider control in the Card Style section, between image aspect ratio and image padding. Caps the image container height regardless of card width. On a 3-column 1440px desktop grid, the default square image area is ~440px tall, which pushes prices and USPs below the fold on typical laptop viewports. Setting this to 240px brings the total card height to ~520px, keeping the first product row fully visible without scrolling. Default is empty (no constraint) so existing widgets are not affected. Recommended value for Bourgini category pages: 240px.

= 1.0.23 =
* Bug fix: discount badge z-index and positioning conflict resolved. The badge had z-index: 2 in its own rule but was also included in a grouped rule that set z-index: 4 and position: relative. Since the grouped rule came later in the file it won specificity, overriding the badge's position: absolute with position: relative and pulling the badge out of its positioned layout. This caused incorrect badge placement and z-index shifts on hover. Fixed by removing the badge from the grouped rule, consolidating its z-index to 4 in its own rule alongside its position: absolute.
* CSS: split the grouped z-index rule into separate rules per element type. Actions and button receive position: relative + z-index: 4. Badge keeps position: absolute + z-index: 4 in its own rule. Stock label gets position: absolute + z-index: 4 in a single consolidated rule.

= 1.0.22 =
* Added five new shipping pill style controls in the Colors section: font size (default 11px), icon size (default 14px, up from hardcoded 11px), vertical padding, horizontal padding, and border radius (default 100px full pill). All controls apply via Elementor's inline style system and are visible immediately in the editor preview.
* Shipping pill icon default size bumped from 11px to 14px to better match the USP checkmark icon size and improve readability at small pill sizes.
* CSS: removed hardcoded padding and border-radius from .wc-card__shipping since these values are now controlled via Elementor. CSS fallback values remain in the stylesheet for cases where no Elementor value has been saved.

= 1.0.21 =
* Performance: added a "sizes" attribute to all product images that matches the plugin's actual CSS grid breakpoints ((max-width: 1024px) 50vw, 33vw). The browser can now pick the smallest srcset variant that still fits, saving roughly 30-50% of image bandwidth on mobile devices where each card is half the viewport width but a full-size 600px image was being downloaded by default.
* Performance: the first three cards in the grid (typical above-the-fold count for 3-column desktop) now render their primary image with loading="eager" and fetchpriority="high". This hints to the browser to prioritize fetching the LCP candidate image early. Cards from index 3 onward continue using loading="lazy". Expected LCP improvement of 100-300ms on slow connections.
* Performance: SVG icons (check mark for USPs, truck for shipping pill) are now defined once in an SVG sprite output at the top of each grid, and referenced by each card via <svg><use href="#wcpce-icon-..."/>. Previously each card inlined the full SVG markup multiple times (once per USP plus shipping). On a 12-card page with 3 USPs each, this saves around 10KB of duplicated HTML.
* Internal: the sprite output uses a static flag so it renders at most once per page, even when multiple widget instances appear on the same page. Avoids duplicate-ID HTML validation warnings.
* Internal: card index is now passed from render() into the template via the $card data array. Used to drive fetchpriority and eager loading for above-the-fold cards.
* Internal: $allowed_svg whitelist updated to allow the <use> element for the sprite reference markup. Removed <path> and <circle> from the whitelist since those tags now only appear inside the sprite block which is output directly by the widget rather than passed through wp_kses.

= 1.0.20 =
* Bug fix: turning off "Show out of stock label" now also disables the dimming and grayscale visual treatment on out-of-stock products. Previously the toggle only hid the dark "Tijdelijk uitverkocht" pill, but the product image stayed dimmed and grayscale, which didn't match what the toggle implies.
* Performance: is_in_stock() is now called once per card and cached, instead of three times (template was calling it for the OOS class, the OOS label, and the add-to-cart button condition).
* Performance: get_price_html() in the price block is now deferred into the not-on-sale branch instead of being called unconditionally before the conditional. For sale products, this skips a filter chain that was being computed but discarded.
* Performance: get_price_html() in the aria-label price string is now skipped for sale products since we already have the numeric sale_price.
* Refactor: sale_price in the data array stays at 0 for products that are not on sale, instead of mirroring regular_price. Cleaner semantics for downstream code.
* Refactor: template now trusts the is_on_sale flag computed by compute_card_data() instead of re-validating sale_price > 0 && sale_price < regular_price. compute_card_data already validates discount_percent > 0 before setting is_on_sale.
* Code cleanup: removed unnecessary $orderby_value intermediate variable in price ordering. Removed unused $out_of_stock_label initialization. Removed mixed_discounts from the data array returned to the template since it is only used internally by compute_card_data.
* CSS: removed the now-redundant .wc-card--out-of-stock .wc-card__image--secondary { opacity: 0 } rule. The inline style="opacity:0" attribute on the secondary image (added in v1.0.19) wins specificity over any class-based rule, making this dedicated rule obsolete.
* CSS: added explanatory comment to the !important on width/height of .wc-card__image, documenting that it overrides theme styles that often set img { width: auto }.
* Plugin headers: bumped Elementor tested up to 4.1.0 and Elementor Pro tested up to 4.0.4.

= 1.0.19 =
* Hover image swap is now off by default. The control is still available, but new widget instances start with hover swap disabled. Existing widgets where hover swap was already turned on stay on. The control description now explains the trade-off so users can make an informed choice.
* Bug fix: random products no longer briefly show the secondary image at full opacity on first page load when hover swap is enabled. Cause: when v1.0.17 changed secondary image loading from lazy to eager, a render-order race appeared where the secondary image could be downloaded and decoded before the external CSS arrived to hide it. Fix: secondary image now carries an inline style="opacity:0" attribute so it stays hidden during first paint regardless of CSS load timing. Inline styles win specificity over external CSS, so the hover rules now use !important to override the inline style during the swap interaction.
* Performance: secondary image loading reverted from eager to lazy. With hover swap off by default this only affects widgets where it has been deliberately turned on. Lazy loading defers the fetch until the card is near the viewport, saving bandwidth on long category pages where most cards are below the fold.
* Internal: the out-of-stock hover override on the secondary image now uses !important to win against the new generic hover !important rule, keeping the dimming behavior intact for out-of-stock products with hover swap enabled.

= 1.0.18 =
* Hotfix: out-of-stock cards with hover swap enabled showed the secondary image at 55% opacity bleeding through behind the primary image at rest, with no hover involved. Cause: the .wc-card--out-of-stock .wc-card__image rule (specificity 0,2,0) was overriding the .wc-card__image--secondary opacity: 0 default (specificity 0,1,0), so the secondary image was visible at the dimmed opacity instead of fully hidden. Fixed by adding a more specific rule for the out-of-stock secondary image resting state.

= 1.0.17 =
* Bug fix: out-of-stock products no longer flash at full opacity on hover when hover swap is enabled. Added explicit CSS rules that keep the primary image at dimmed opacity and the secondary image hidden throughout the hover interaction for out-of-stock cards.
* Bug fix: secondary (hover swap) image now uses loading="eager" instead of loading="lazy". Previously the browser deferred fetching the secondary image until the first hover, causing a visible delay. Eager loading fetches it during initial page load so hover swap is instant.
* Bug fix: run_manual_query() now uses a named callback with remove_filter() instead of remove_all_filters() when applying the price orderby. remove_all_filters() was removing all third-party hooks on woocommerce_default_catalog_orderby, which could silently break other plugins or themes.
* Bug fix: when no sale products exist and "Sale only" filter is enabled, the query now returns early instead of using the post__in = [0] hack which is undocumented WP behavior.
* Bug fix: when "Include specific products" and "Sale only" filter produce an empty intersection, the query now returns early instead of falling through to WP_Query which would return all products.
* Bug fix: editor fallback query (shown when no archive context exists) now excludes products marked "hidden" from the catalog, matching the behavior of the main query.
* Performance: added decoding="async" to both primary and secondary product images so image decoding happens off the main thread, reducing stutter on hover swap.
* Minor: secondary image ID check now uses strict > 0 comparison instead of truthiness.
* Minor: out-of-stock label and text are now only evaluated when the feature is enabled.
* Minor: shipping pill threshold logic rewritten to consistently use sale price when on sale, regular price otherwise, avoiding the edge case where the minimum variation price caused the pill to show when only the cheapest variant qualifies.
* Minor: template docblock no longer references the removed format_price() helper.
* Minor: isset() guard removed from display_price access since compute_card_data() always initializes it.
* CSS: "VERSION 1.0.3 INTERACTION UPDATES" comment replaced with descriptive "OVERLAY LINK AND INTERACTIVE ELEMENTS" section header.
* CSS: all mobile breakpoints changed from 600px to 767px to align with Elementor's default mobile breakpoint. Previously screens between 601-767px were treated as tablet by the plugin but mobile by Elementor, causing responsive column controls to not match the CSS.
* CSS: wc-card__rating removed from the z-index: 4 group. The rating block sits inside the aria-hidden card body and does not need z-index elevation above the overlay link.
* Code: all private methods now have PHP return type declarations and typed parameters for consistency with the public/protected methods added in v1.0.14.

= 1.0.16 =
* Phase 1: Query — added "Sale products only" toggle (Q1). When enabled in manual mode, only products currently on sale are shown. Useful for campaign and sale pages.
* Phase 1: Query — added "Featured products only" toggle (Q2). When enabled, only products marked as featured in WooCommerce are shown.
* Phase 1: Query — added "Stock status" select (Q3) with three options: Any (respects WooCommerce global settings), In stock only, Out of stock only.
* Phase 1: Query — added "Include specific products" text field (Q4). Enter comma-separated product IDs to show only those products. Combines correctly with category and sale/featured filters.
* Phase 1: Query — added "Exclude specific products" text field (Q5). Enter comma-separated product IDs to hide from the grid.
* Phase 1: Empty state — added "Show message when no products found" toggle (E2). Turn off to render nothing when the query returns empty instead of showing a message.
* Phase 1: Empty state — added "Empty state message" text field (E1). Configurable Dutch default "Geen producten gevonden." Replaces the hardcoded English fallback in manual mode.
* Phase 1: Editor notice — added warning when a manual query returns zero products (N1), explaining which settings to check (category, IDs, filters, stock status).
* Internal: editor notices now run after the product query so the N1 notice has access to the query result.
* All new query controls only appear when source is set to Manual, keeping the Auto mode panel clean.

= 1.0.15 =
* Hotfix: fatal TypeError in get_script_depends() caused by calling get_settings_for_display() before widget settings are initialized. Elementor calls get_script_depends() during early script enqueueing when settings are still null. Reverted to always declaring wc-add-to-cart, which is a small script already loaded on most WooCommerce pages.

= 1.0.14 =
* Code review fixes across all files. Full list below.

Bugs fixed:
* Badge aria-label regex now correctly strips the dash after "Tot " prefix for variable products (was reading "Tot -20% korting" instead of "Tot 20% korting").
* Badge aria-label no longer double-escapes the "korting" suffix (esc_html__ replaced with __ since esc_attr() handles escaping at output).
* USP mobile visibility default in the template now matches the widget control default (both default to hidden on mobile).
* Misaligned indentation in the USP/shipping block normalized.
* ACF fields are now fetched in a single get_fields() call per card instead of 4 separate get_field() calls, reducing DB queries per card from 4 to 1.

Elementor standards:
* All widget method overrides now have PHP return type declarations (get_name(): string, get_title(): string, get_categories(): array, get_keywords(): array, get_style_depends(): array, get_script_depends(): array, register_controls(): void, render(): void) to match Elementor's Widget_Base signatures.
* Added empty content_template(): void method to signal server-side rendering to Elementor instead of leaving it undefined.
* Added get_custom_help_url(): string method.
* Removed woocommerce-elements category from get_categories() since this category requires Elementor Pro and silently no-ops on free installs.
* wc-add-to-cart script now only declared in get_script_depends() when action_type is add_to_cart, not on every page that contains the widget.

WooCommerce standards:
* Added WC requires at least and WC tested up to headers to main plugin file.
* Added HPOS (High Performance Order Storage / Custom Order Tables) compatibility declaration via FeaturesUtil.
* Price, popularity, and rating orderby in run_manual_query() now use WC()->query->get_catalog_ordering_args() instead of direct _price, total_sales, and _wc_average_rating meta keys. This future-proofs against WooCommerce storage changes.
* Added wc_prime_caches_for_products() call before the product loop in posts_to_products() to reduce DB queries from N to 1.
* Removed cache-bypassing true parameter from get_variation_prices().

Code quality:
* Added Author URI and fixed placeholder Plugin URI in plugin header.
* Admin notice output changed from printf() to echo concatenation to avoid printf misinterpreting % characters in translated strings.
* Removed dead format_price() public method that was never called.
* Removed development artefact "Point 3" comment in template.
* Removed double blank lines between widget controls.
* ACF class docblock updated to describe both registered field groups.
* hide_on_screen key added to card_title field group for consistency with USP group.
* Widget class docblock updated to reflect current badge threshold default (0, not 10).
* Vague "Variables used inside the template partial" comment removed.

= 1.0.13 =
* Accessibility: discount badge now has an aria-label with human-readable Dutch text ("12% korting", "€45 korting") so screen readers no longer announce "-12%" as "negative 12 percent".
* Accessibility: card body is now hidden from screen readers (aria-hidden="true") to prevent the product name being announced twice. The overlay link aria-label now includes the product name and price ("Bekijk Kitchen Chef Plus - €88,00") so screen reader users get all essential information in one clean announcement.
* Accessibility: visually hidden "Van" and "Voor" labels added to the price block so screen readers announce "Van €129,99 Voor €85,00" instead of two numbers back to back.
* Accessibility: added .sr-only utility CSS class (visually hidden, screen-reader readable) used by the price labels.
* Accessibility: removed dead CSS rule .wc-card:focus-visible which never fired because the article element has no tabindex. Focus ring lives on the overlay link.
* Accessibility: overlay link focus ring border-radius changed from hardcoded 14px to inherit, so it always matches the card border-radius Elementor control.
* Accessibility: added @media (prefers-reduced-motion: reduce) block that disables all card transitions, lift transforms, image zoom, and button transforms for users who have enabled reduced motion in their OS settings.

= 1.0.12 =
* New control: Badge border radius (Style > Discount Badge Style). Default 6px. Set to 100px for a pill shape, 2-4px for a rectangular badge.
* New control: Gap between card elements (Style > Layout). Responsive. Controls vertical spacing between title, rating, price, USPs, and shipping inside the card body. Default 10px desktop, 8px mobile.
* New control: Image padding (Style > Card). Controls white space around the product photo inside the image area. Default 16px. Decrease for edge-to-edge images, increase for more breathing room.
* New controls: Card border color + border width (Style > Card). Default border width is 0 (no border). Set a color and a width to add a border as an alternative or addition to the box shadow.
* New controls: Out-of-stock label background color and text color (Style > Colors). Both only visible when "Show out of stock label" is enabled. Default background rgba(0,0,0,0.62), text white.

= 1.0.11 =
* Card order: "Gratis verzending" pill moved to below the USP list per updated information hierarchy (badge, image, title, rating, price, USPs, shipping).
* ACF: new optional "card_title" field (Korte titel voor productkaart) added to the product post type. When filled, it replaces the WooCommerce product title on the card. When empty, the normal product title is used as fallback. Allows shorter, cleaner titles on category pages without touching the SEO product title.
* Title clamp: default changed from 3 lines to 2 lines on both desktop and mobile, matching the new card_title workflow where short ACF titles keep cards compact. The Elementor title clamp control can still be changed per widget instance.
* Accessibility: overlay link now has a clear focus-visible ring (2px solid #3EC26D, 4px offset) so keyboard users can see which card is focused. Uses :focus-visible so mouse users are not affected.
* Dutch frontend strings: all visitor-facing default texts confirmed Dutch (Bekijk product, In winkelwagen, Kies opties, Tijdelijk uitverkocht, Gratis verzending). aria-label on overlay link updated from "View %s" to "Bekijk %s".

= 1.0.10 =
* Changed discount badge threshold default from 10% to 0%, so all sale products show a badge regardless of discount size. The threshold control is still available in the widget settings if you want to re-enable filtering later.

= 1.0.9 =
* Layout consistency: rating slot now reserves vertical space even when Lipscore has no data for a product. Removed the :has() collapse rule so cards in the same row align horizontally regardless of which products have reviews. Cards without reviews show a small empty 14px area where the stars would be, which is preferable to jagged row alignment per category-grid scanning research.
* Layout consistency: USP block now reserves vertical space based on the configured Maximum USPs to show value (1, 2, or 3 slots), so cards align horizontally regardless of how many USPs an individual product has filled in. Empty space below filled USPs is intentional and preserves comparison scanning across cards in the same row. The mobile USP-hide setting still removes the entire USP block from layout when enabled.

= 1.0.8 =
* Design refinement: Lipscore rating made subtler with 10px sizing, lower opacity, and a mild saturation/brightness filter while preserving Lipscore's filled and empty star styling.

= 1.0.6 =
* Design refinement: savings line disabled by default for calmer product cards.
* Design refinement: default title clamp changed to three lines.
* Design refinement: Lipscore rating slot reduced to 14px stars with tighter spacing.
* Design refinement: free-shipping pill made slightly smaller and subtler.
* Design refinement: USP list is now hidden on mobile by default.

= 1.0.4 =
* Hardened frontend safety checks for Elementor editor/preview detection.
* Removed a potentially fragile WooCommerce add-to-cart feature check.

= 1.0.3 =
* Reworked the card from a full anchor tag to an article with an overlay link. This keeps the whole card clickable while reducing nested-link conflicts with Lipscore or button links.
* Added optional out-of-stock label with editable text.
* Added optional action button: none, view product, or add to cart / choose options.
* Added mobile controls for hiding USPs and disabling hover image swap on mobile.
* Added selectable image size: WooCommerce thumbnail, medium, large, or full.
* Improved editor preview notices for Auto mode, ACF/ACF Pro, and Lipscore behavior.
* Improved price display fallback for grouped, external, and products with empty numeric prices by using WooCommerce native price HTML where needed.

= 1.0.2 =
* Fixed Elementor load-order issue where the widget could fail to register and not appear in the Elementor editor.
* Removed Advanced Custom Fields from the WordPress Requires Plugins header so ACF Pro is supported correctly. ACF is now detected at runtime through the shared ACF function.
* Updated the ACF admin notice to mention both ACF Free and ACF Pro.
* Updated the free-shipping threshold description to match the actual displayed-price logic.
* Improved the free-shipping threshold check for variable products without sale prices by using the lowest displayed variation price.

= 1.0.1 =
* Fixed variable products with mixed sale variations rendering an incorrect or empty sale price. The card now tracks the specific variation that produced the maximum percentage discount and uses ITS regular and sale prices for the strikethrough, sale price, savings line, and badge, keeping all four values internally consistent.
* Fixed Smart badge format ("Rule of 100") using the catalog max regular price instead of the discounted variation's actual regular price, which could mistakenly choose the amount format when percentage was psychologically stronger.
* Fixed editor stylesheet being enqueued on the wrong hook, which leaked card CSS into the Elementor editor panel UI rather than the preview iframe. Removed the redundant enqueue; Elementor now handles preview iframe styles via get_style_depends() automatically.
* Fixed get_terms() running on every frontend page load to populate a control that's only visible in the editor. Category options now load lazily in admin/editor contexts.
* Fixed ACF field group registration being load-order-dependent. The hook now attaches unconditionally and self-gates on acf/init firing.
* Fixed currency symbol hardcoded to "EUR" in the discount badge, which broke the badge on shops using other currencies. Now uses get_woocommerce_currency_symbol().
* Variable products that aren't on sale now display the WooCommerce price range (e.g. "Vanaf EUR 99,00") instead of a single max price, matching native WC behavior.
* ACF field labels now use plain __() rather than esc_html__(), since ACF escapes labels itself when rendering admin forms.
* Added is_dynamic_content() method to explicitly opt out of Elementor's element output cache, future-proofing against stale product data.
* Defensive: ELEMENTOR_VERSION constant existence check before version_compare.
* Defensive: editor context detection wrapped in class_exists guard.
* Defensive: is_on_sale flag now reflects computed reality rather than just WC's flag, guarding against products marked on-sale but with no valid sale prices.
* Improved placeholder image markup with explicit width/height to prevent layout shift.
* CSS: removed dead duplicate rule for the secondary image opacity.
* CSS: added :has() based collapse for the rating slot in modern browsers, with min-height fallback for older browsers.

= 1.0.0 =
* Initial release.
