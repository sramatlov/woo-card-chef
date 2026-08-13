# Technical Spec — Woo Card Chef

## Version targets

| Dependency | Minimum | Tested up to |
|---|---|---|
| WordPress | 6.0 | 7.0.4 |
| PHP | 7.4 | 8.3 |
| WooCommerce | 6.0 | 11.0.1 |
| Elementor (free) | 3.5.0 | 4.2.2 |
| Elementor Pro | — | 4.2.1 |
| ACF / ACF Pro | Any (optional) | Current |

## Hard dependencies
- Elementor (free) — widget registration, controls, rendering lifecycle
- WooCommerce — product data, pricing, stock, visibility, catalog ordering

## Soft dependencies
- ACF or ACF Pro — card_title, card_hover_image, Product Card USPs, and all three badge fields (free ACF). `pdp_gallery_videos` and `pdp_usps` are repeaters and require ACF Pro. Plugin works without ACF but those fields are empty; gallery shows WC images only and the PDP USP widget falls back to short description / Product Card USPs when available.
- Lipscore — rating widget is a placeholder div; Lipscore JS fills it client-side. No API calls from our plugin.

## Plugin folder structure

```
wc-product-card-elementor/
├── wc-product-card-elementor.php   Main file: headers, constants, bootstrap, HPOS declaration, admin notices
├── uninstall.php                   Minimal uninstall handler (clears transient cache tracking option)
├── readme.txt                      WordPress.org format changelog and description
├── includes/
│   ├── class-plugin.php            Singleton bootstrap: loads helpers, registers all widgets, class-assets, ACF fields, query var, cache flush
│   ├── class-acf-fields.php        Programmatic ACF field group registration (6 groups)
│   ├── class-assets.php            Centralised CSS/JS registration for all widgets (R7, v2.0.0)
│   ├── Widgets/
│   │   ├── class-product-card-widget.php   Product Card grid widget (moved from includes/ root in v2.0.0)
│   │   ├── class-product-gallery-widget.php  PDP Gallery widget (new in v2.0.0)
│   │   ├── class-product-price-widget.php    PDP Price & Promo Block widget (new in v2.1.0)
│   │   ├── class-product-usps-widget.php     PDP Product USP / Benefits widget (new in v2.2.0)
│   │   ├── class-product-delivery-widget.php PDP Delivery & Availability widget (new in v2.3.0)
│   │   ├── class-product-accordion-widget.php PDP Product Accordion widget (new in v2.4.0)
│   │   ├── class-product-upsells-widget.php PDP Product Upsells widget (new in v2.5.0)
│   │   └── class-product-related-widget.php PDP Cross-sells / Related widget (new in v2.6.0)
│   └── Helpers/
│       ├── class-badge-helper.php  WCPCE_Badge_Helper (Phase 6 R1, v1.0.80)
│       ├── class-stock-helper.php  WCPCE_Stock_Helper (Phase 6 R3, v1.0.81)
│       ├── class-price-helper.php  WCPCE_Price_Helper (Phase 6 R2, v1.0.82)
│       ├── class-image-helper.php  WCPCE_Image_Helper (Phase 6 R4, v1.0.83)
│       ├── class-acf-helper.php    WCPCE_ACF_Helper (Phase 6 R5, v1.0.84)
│       └── class-card-renderer.php Shared card rendering for product-list widgets (v2.5.0)
├── assets/
│   ├── css/
│   │   ├── product-card.css        Product Card widget styles (unminified)
│   │   ├── product-gallery.css     PDP Gallery widget styles (unminified, new in v2.0.0)
│   │   ├── product-price.css       PDP Price & Promo Block styles (unminified, new in v2.1.0)
│   │   ├── product-usps.css        PDP Product USP / Benefits styles (unminified, new in v2.2.0)
│   │   ├── product-delivery.css    PDP Delivery & Availability styles (unminified, new in v2.3.0)
│   │   ├── product-accordion.css   PDP Product Accordion styles (unminified, new in v2.4.0)
│   │   ├── product-upsells.css     PDP Product Upsells styles (unminified, new in v2.5.0)
│   │   └── product-related.css     PDP Cross-sells / Related styles (unminified, new in v2.6.0)
│   └── js/
│       ├── product-gallery.js      PDP Gallery widget JS (new in v2.0.0; deferred)
│       └── product-accordion.js    PDP Accordion widget JS (new in v2.4.0; deferred)
└── templates/
    └── card.php                    Card partial included per product in the render loop
```

**JavaScript.** The Product Card widget ships no JavaScript (zero-JS since v1.0.54). The PDP Gallery widget ships `assets/js/product-gallery.js`, registered with `strategy: 'defer'` and only enqueued on pages where the widget is present (via `get_script_depends()`). Purely presentational PDP widgets (Price & Promo Block, Product USP / Benefits, Product Delivery & Availability) return an empty `get_script_depends()` array and ship CSS only. The Product Accordion widget ships `assets/js/product-accordion.js` (deferred) for toggle behaviour, FAQ inner accordion, Lipscore count sync, and hash-jump navigation. The Product Upsells and Product Cross-sells / Related widgets have no widget-specific JS, but return `wc-add-to-cart` statically because the shared card template can render AJAX add-to-cart buttons.

**Helpers (Phase 6, v1.0.80-v1.0.84; v2.5.0).** Shared stateless utility classes live in `includes/Helpers/`. Required unconditionally at bootstrap. No constructors, no object state. Since v2.4.1, `WCPCE_Price_Helper::get_product_price_data()` has a static per-request cache because multiple PDP widgets can reuse the same product price data. Since v2.5.0, `WCPCE_Card_Renderer` owns card sprite output, `templates/card.php` inclusion, and card data computation so PDP product-list widgets can reuse the Product Card Grid card logic.

**class-assets.php (R7, v2.0.0).** Centralises `wp_register_style()` and `wp_register_script()` for all widgets. Hooked to `wp_enqueue_scripts`. Registration only — Elementor's `get_style_depends()` / `get_script_depends()` on each widget drive actual enqueueing.

## Constants

```php
WCPCE_VERSION       // e.g. '2.6.10'
WCPCE_PLUGIN_FILE   // __FILE__ of main plugin file
WCPCE_PLUGIN_DIR    // plugin_dir_path()
WCPCE_PLUGIN_URL    // plugin_dir_url()
WCPCE_MIN_ELEMENTOR_VERSION  // '3.5.0'
WCPCE_MIN_PHP_VERSION        // '7.4'
```

## ACF field groups (6 total)

| Group key | Title | Location | Notes |
|---|---|---|---|
| `group_wcpce_card_title` | Product Card Title | product post type | `card_title` + `card_hover_image` |
| `group_wcpce_product_usps` | Product Card USPs | product post type | `usp_1/2/3` |
| `group_wcpce_product_badges` | Product Card Badges | product post type | `badge_nieuw`, `badge_pfas_vrij`, `badge_niet_leverbaar` |
| `group_wcpce_pdp_usps` | PDP USP's | product post type | `pdp_usps` repeater — **ACF Pro required**. Content only: one `usp_text` row per USP. |
| `group_wcpce_pdp_gallery_media` | PDP Gallery: extra media | product post type | `pdp_gallery_videos` repeater — **ACF Pro required**. Only registered when `acf_get_field_types()` returns `repeater`. |
| `group_wcpce_pdp_accordion` | PDP Accordion: handleiding | product post type | `product_manual` file field. Works with free ACF (no repeater). |

### PDP USP repeater sub-fields

| Field name | Key | Type | Purpose |
|---|---|---|---|
| `usp_text` | `field_wcpce_pdp_usp_text` | Text | One short USP line for the product detail page. |

### PDP Gallery video repeater sub-fields

| Field name | Key | Type | Purpose |
|---|---|---|---|
| `youtube_url` | `field_wcpce_youtube_url` | URL | YouTube link (watch, youtu.be, embed, Shorts) |
| `video_title` | `field_wcpce_video_title` | Text | Accessibility label + admin clarity |
| `video_thumbnail` | `field_wcpce_video_thumbnail` | Image (id) | Custom thumbnail; overrides YouTube mqdefault |

`video_position` and `display_mode` fields are intentionally not registered in v2.0.0 — reserved for v2.1 (interleaving and in-slide playback).

## PDP Gallery widget: `WCPCE_Product_Gallery_Widget`

Extends `\Elementor\Widget_Base`. Renders inside a Theme Builder single-product template; product context is obtained via `wc_get_product()` / `global $product` automatically. No product-ID control needed.

### Key private methods

| Method | Returns | Purpose |
|---|---|---|
| `validate_gallery_settings(array)` | `array` | Whitelist/clamp settings before render. Same threat model as `validate_manual_settings()` on the card widget. |
| `get_current_product()` | `?\WC_Product` | Resolves product from queried object first, then global WC product (v2.5.8+). |
| `get_editor_fallback_product()` | `?\WC_Product` | Recent published product for editor preview when no loop context. |
| `render_editor_notices(array, ?\WC_Product)` | `void` | Editor-only notices: no product context, ACF Pro missing for videos. |
| `acf_pro_active()` | `bool` | Static. Checks `acf_get_field_types()` for repeater support. |
| `get_gallery_slides(\WC_Product, array)` | `array` | Builds ordered slide list: WC images → positioned video slides. |
| `get_visible_thumbnail_count(array)` | `int` | Returns thumbnail_count clamped 2–10. |
| `position_video_slides_before_thumbnail_overflow(array, array, array)` | `array` | Places video slides at slot (thumbnail_count − 1) so they are always visible in the strip. |
| `extract_youtube_id(string)` | `string` | Static. Strict host validation (youtube.com, youtu.be, youtube-nocookie.com only) then ID regex. Returns empty string on failure. |
| `get_attachment_alt_text(int, string)` | `string` | Returns attachment alt text with product-name fallback for gallery images (v2.6.7). |
| `render_gallery_sprite()` | `void` | Outputs inline SVG sprite once per page (static flag). Symbols: play, prev, next, close, leaf. |
| `render_badgebar(array, array, array, string, bool)` | `void` | Renders Korting / Nieuw / PFAS-vrij badges. PFAS is not suppressed by niet-leverbaar (it is an attribute badge). mixed_discounts passed as fifth arg for correct "Tot " prefix. |
| `render_image_slide(array, int, array, string)` | `void` | Single image slide with LCP optimisation on index 0 and alt fallback from product name. |
| `render_video_slide(array, array, bool)` | `void` | Video slide: CSS background-image thumbnail (bypasses Elementor global img styles) + play button. Inactive slides get tabindex="-1" on play button. |

### Slide data structure

Each slide is an associative array:
```php
[
  'type'          => 'image' | 'video',
  'attachment_id' => int,      // 0 for video slides
  'youtube_url'   => string,   // empty for image slides
  'youtube_id'    => string,   // video slides only
  'video_title'   => string,   // video slides only
  'thumb_id'      => int,      // video slides only; 0 = use YouTube mqdefault
]
```

### Gallery accessibility implementation

- Image slides pass explicit alt text to `wp_get_attachment_image()`: attachment alt text first, product name as fallback when media alt is empty (v2.6.7)
- Inactive slides render with `aria-hidden="true"` and `inert`; JS removes/adds `inert` when the active slide changes (v2.6.7)
- Hidden video slide play buttons render with `tabindex="-1"` and are updated on slide change

### Lightbox architecture

The lightbox `<div>` is rendered **outside** `.wcpce-gallery` (directly after it) so `position:fixed` escapes to the viewport. Any ancestor stacking context (`isolation`, `transform`, `will-change`) would trap a `position:fixed` child — rendering it outside avoids this. JS finds the lightbox via `document.getElementById('wcpce-gallery-lb-{widget_id}')`.

Image lightbox and video lightbox are separated: `data-lightbox` controls images, `data-video-lightbox` controls video. The lightbox is always rendered when video slides are present, regardless of the image lightbox setting.

### JS architecture

Single `WCPCEGallery` instance per `.wcpce-gallery`. Central `initGallery(galleryEl)` helper guards on the element itself (not the Elementor wrapper), preventing double-init from both `DOMContentLoaded` and `elementorFrontend` hooks. Document-level zoom handlers are bound exactly once per instance via `ensureZoomDocumentEvents()` using a `zoomDocumentEventsBound` flag; `zoomImg` tracks the active image reference.

### Gallery Elementor controls (v2.0.0)

**Content:** show featured image, show gallery images, show ACF videos, show badgebar + position, show/format each badge, show status overlays, enable lightbox, enable zoom, thumbnail count, image fit (contain/cover), aspect ratio (responsive), mobile thumbnail count (control exists; positional logic for mobile not yet active — v2.1).

**Style:** gallery border radius, gallery spacing, thumbnail border radius, active thumbnail border colour, badgebar gap + spacing, play button size/colour/background.

## PDP Price & Promo Block widget: `WCPCE_Product_Price_Widget` (v2.1.0)

Extends `\Elementor\Widget_Base`. Renders inside a Theme Builder single-product template; product context via `wc_get_product()` / `global $product`, editor fallback via the most recent published product. Server-side, zero JS (`get_script_depends()` returns an empty array). Style handle: `wcpce-product-price`.

### Key private methods

| Method | Returns | Purpose |
|---|---|---|
| `validate_price_settings(array)` | `array` | Whitelist `price_mode` (compact/extended) and `variable_price_display` (from/range); sanitise + length-clamp the label fields. |
| `get_current_product()` | `?\WC_Product` | Resolve product from queried object first, then global WC product (v2.5.8+). |
| `get_editor_fallback_product()` | `?\WC_Product` | Recent published product for editor preview. |
| `render_editor_notices(array, ?\WC_Product)` | `void` | Editor-only "no product context" notice. |
| `to_display(\WC_Product, float)` | `float` | `wc_get_price_to_display()` wrapper — tax-correct display amount. |
| `get_reference_display(\WC_Product, float)` | `float` | Applies the `wcpce_price_reference_value` filter then `to_display()`. |
| `render_discount_chip(int, float, bool)` | `void` | "-X%" / "Tot -X%" chip (visual, aria-hidden) + sr-only "X% korting". |
| `render_plain_price(\WC_Product, array, bool, array)` | `void` | Not-on-sale / discontinued price (current, Vanaf, or WC range / `get_price_html()`). |
| `render_price_block(\WC_Product, array, array, bool, bool, bool, bool)` | `void` | Builds the block per mode and state. |

### Behaviour matrix

- Simple on sale: struck `<del>` reference (visible label) + sale price + optional chip + optional savings line. Percentage and savings derive from the (filtered) display reference vs display sale.
- Simple not on sale: single current price; `get_price_html()` fallback for grouped/external.
- Variable on sale: "Vanaf EUR X" (lowest current) + "Tot -X%" chip; no struck reference / no literal savings (ghost-anchor avoidance).
- Variable not on sale: "Vanaf EUR X" or full WC range (`variable_price_display`).
- Niet meer leverbaar (`badge_niet_leverbaar`): plain current price, dimmed via `.wcpce-price--unavailable`, all discount framing dropped.

### Filters & data

- `wcpce_price_reference_value` (float `$raw_regular`, `\WC_Product $product`) — override the struck reference (e.g. 30-day Omnibus lowest). Default: WC regular price.
- All displayed amounts via `wc_get_price_to_display()`; the percentage is tax-neutral.
- No Product/Offer structured data emitted (WooCommerce core / SEO plugin own it).

### Elementor controls (v2.1.0)

**Content:** layout (compact/extended), variable price display (from/range), from-price label, show reference + reference label, sale price label (screen-reader), show discount percentage, show savings amount, "Tot" prefix toggle, dim-when-unavailable toggle.

**Style (via `selectors` / group controls):** element spacing, current/sale/reference colour, price + reference typography (`Group_Control_Typography`), chip background/text/radius, savings colour, discontinued opacity.

## PDP Product USP / Benefits widget: `WCPCE_Product_USPs_Widget` (v2.2.0)

Extends `\Elementor\Widget_Base`. Renders short, scan-friendly benefit lines inside a Theme Builder single-product template. Server-side, zero JS (`get_script_depends()` returns an empty array). Style handle: `wcpce-product-usps`.

### Content source priority

In `source_mode = auto`, the widget reads sources in this order:
1. PDP-specific ACF Pro repeater `pdp_usps` (`usp_text` per row)
2. WooCommerce short description, split into clean lines
3. Existing Product Card USP fields (`usp_1`, `usp_2`, `usp_3`)

Manual source modes can force any one of those sources. The repeater is intentionally content-only; no per-row icon, title, colour, or layout data is stored in ACF.

### Key private methods

| Method | Returns | Purpose |
|---|---|---|
| `validate_usp_settings(array)` | `array` | Whitelist source/layout/icon options; clamp max item count to 1-8. |
| `get_current_product()` | `?\WC_Product` | Resolve product from queried object first, then global WC product (v2.5.8+). |
| `get_editor_fallback_product()` | `?\WC_Product` | Recent published product for editor preview. |
| `get_pdp_usps(int, int)` | `array` | Reads `pdp_usps` via ACF Pro `get_field()` and returns sanitised `usp_text` values. |
| `get_short_description_usps(\WC_Product, int)` | `array` | Converts short description HTML into short text lines; preserves list item boundaries so each `<li>` becomes its own USP row. |
| `get_card_usps(\WC_Product, int)` | `array` | Reuses existing Product Card USP data from `WCPCE_ACF_Helper::get_card_data()`. |
| `get_usps(\WC_Product, array)` | `array` | Applies source mode and fallback chain. |
| `render_editor_notices(array, ?\WC_Product)` | `void` | Editor-only notices for missing product context or missing ACF Pro when forcing `pdp_usps`. |
| `render_icon(string)` | `void` | Global icon only: checkmark SVG or CSS dot. |

### Elementor controls (v2.2.0)

**Content:** source mode, max items, optional heading + heading tag, layout mode (list/cards/inline), show icon, icon style (check/dot).

**Style (via `selectors` / group controls):** responsive columns, row/column gap, item padding, text alignment, heading typography/colour/spacing, item typography/colour, icon colour/size, item background, border, radius and shadow.

## PDP Delivery & Availability widget: `WCPCE_Product_Delivery_Widget` (v2.3.0)

Extends `\Elementor\Widget_Base`. Renders stock, delivery and free-shipping reassurance lines inside a Theme Builder single-product template. Server-side, zero JS (`get_script_depends()` returns an empty array). Style handle: `wcpce-product-delivery`.

### Behaviour matrix

- In stock below threshold: `Op voorraad`, configured delivery text, `Gratis bezorging vanaf €25,-`
- In stock at/above threshold: `Op voorraad`, configured delivery text, `Gratis bezorging`
- Out of stock: `Tijdelijk uitverkocht` plus optional `Niet direct leverbaar`; no tomorrow/free-shipping lines
- Discontinued (`badge_niet_leverbaar`): `Niet meer leverbaar`; no tomorrow/free-shipping lines

### Data sources

- WooCommerce stock status via `WCPCE_Stock_Helper::is_out_of_stock()`
- Permanent unavailable flag via `WCPCE_ACF_Helper::get_card_data()` and `badge_niet_leverbaar`
- Price comparison via `WCPCE_Price_Helper::get_product_price_data()`
- Delivery text and free-shipping threshold via Elementor controls

### Key private methods

| Method | Returns | Purpose |
|---|---|---|
| `validate_delivery_settings(array)` | `array` | Whitelist layout, normalise switchers, clamp threshold, sanitise + length-clamp labels. |
| `get_current_product()` | `?\WC_Product` | Resolve product from queried object first, then global WC product (v2.5.8+). |
| `get_editor_fallback_product()` | `?\WC_Product` | Recent published product for editor preview. |
| `get_availability_state(\WC_Product)` | `string` | Returns `in_stock`, `out_of_stock`, or `discontinued`. |
| `get_compare_price(\WC_Product)` | `float` | Conservative threshold price. Uses the current/lowest display price first, then sale/regular fallbacks. |
| `format_threshold_amount(float)` | `string` | Formats the threshold as compact shopper copy, e.g. `€25,-`. |
| `get_delivery_items(\WC_Product, array)` | `array` | Builds the render items for the current product state. |
| `render_editor_notices(?\WC_Product, array)` | `void` | Editor-only notices for missing product context or no enabled lines. |
| `render_icon(string, array)` | `void` | Inline status/delivery/shipping SVG icons. |

### Elementor controls (v2.3.0)

**Content:** stock status toggle + labels, delivery line toggle + global delivery text, out-of-stock delivery note, shipping line toggle, free-shipping threshold, free/below-threshold labels, list/pill layout, icons on/off.

**Style (via `selectors` / group controls):** item gap, item padding, alignment, typography, text colour, icon size/colours, item background, border, radius and shadow.

## PDP Product Accordion widget: `WCPCE_Product_Accordion_Widget` (v2.4.0, hardened v2.4.1, v2.5.8 and v2.6.8)

Extends `\Elementor\Widget_Base`. Renders inside a Theme Builder single-product template; product context via `wc_get_product()` / `global $product`, editor fallback via the most recent published product. Ships `assets/js/product-accordion.js` (deferred) and `assets/css/product-accordion.css`. Style handle: `wcpce-product-accordion`.

### Five sections (fixed order)

| # | Key | Content source | Hidden when empty |
|---|---|---|---|
| 1 | `description` | `$product->get_description()` via `wc_format_long_description()` + `wpautop()` | Yes |
| 2 | `specs` | WooCommerce attributes/dimensions/weight table via `wc_display_product_attributes( $product )` | Yes |
| 3 | `reviews` | Lipscore WC tab callback via `woocommerce_product_tabs` filter | Yes (no tab = no section) |
| 4 | `faq` | ACF repeater `product_faq` (`vraag` / `antwoord`) — registered outside plugin, read-only | Yes |
| 5 | `manual` | ACF file field `product_manual` first; if empty, automatic PDF match in the configured WordPress-root-relative manuals directory | Yes |

### Key private methods

| Method | Returns | Purpose |
|---|---|---|
| `validate_accordion_settings(array)` | `array` | Whitelist heading tag and default-open section; sanitise + clamp labels and manuals directory. |
| `get_current_product()` | `?\WC_Product` | Resolve product from queried object first, then global WC product (v2.5.8+). |
| `get_editor_fallback_product()` | `?\WC_Product` | Recent published product for editor preview. |
| `get_description_content(\WC_Product)` | `string` | Description HTML via `wc_format_long_description()` + `wpautop()`. |
| `get_specs_content(\WC_Product)` | `string` | Captures `wc_display_product_attributes( $product )`; empty string if WooCommerce outputs no attributes table. |
| `get_reviews_content(\WC_Product)` | `string` | Lipscore tab output via WC tab callback; sets/restores `global $product` with `finally`. |
| `get_faq_items(int)` | `array` | Reads `product_faq` via `get_field()`; filters empty rows. |
| `get_manual_file(\WC_Product, string)` | `array\|null` | Reads `product_manual` via ACF first, then falls back to automatic SKU/MPN PDF matching in the configured manuals directory. |
| `get_manual_search_tokens(\WC_Product)` | `array` | Builds SKU/MPN match terms, including variants with trailing zeroes stripped. |
| `get_manual_pdf_files(string)` | `array` | Scans PDF filenames in the manuals directory and caches the list per request. |
| `render_accordion_item(...)` | `void` | Renders one section with button, heading wrapper and aria attributes. Panels are visible server-side; JS applies `hidden` to closed panels after init. |
| `build_faq_html(array, string)` | `string` | Builds FAQ inner accordion HTML with nested aria-expanded/aria-controls/hidden pattern. |
| `build_manual_html(array, string)` | `string` | Download link with PDF icon; label from Elementor control. |

### Accessibility implementation

- Every trigger is a `<button>` inside a `<h2/h3/h4>` (configurable heading level)
- `aria-expanded` toggled by JS on open/close
- `aria-controls` points to panel ID; panel has `role="region"` + `aria-labelledby`
- Panels render visible in initial server HTML as a no-JS/crawler fallback; after JS init, closed panels use the HTML `hidden` attribute (not CSS `display:none`) and are removed from the tab order
- + / − icon carries `aria-hidden="true"`
- Focus ring: `outline: 2px solid #d9d9d9` on `:focus-visible`
- Minimum tap target height: 44 px (set via trigger padding)

### JS architecture

Single `initAccordion(accordionEl)` helper guards on the `.wcpce-accordion` element. Both `DOMContentLoaded` and the `elementorFrontend` hook call it; the init flag prevents double-init. PHP renders all panels open for progressive enhancement. JS first runs `applyInitialOuterState(accordionEl)`, reads `data-default-open`, and closes non-default panels with `hidden`/`aria-expanded="false"`. FAQ answers also render open server-side and are closed during FAQ init. Lipscore count sync uses a MutationObserver on `#js-lipscore-reviews-tab-count` plus a 10-second polling interval. Hash-jump listens on `window.load` and document click (capture phase). `global $product` is restored after Lipscore tab callback capture even if a third-party callback throws.

### Elementor controls (v2.4.0)

**Content:** heading level (h2/h3/h4), default-open section, per-section show/hide toggle + label, manual download label, and a WordPress-root-relative automatic manuals directory (default `manuals`) used only when `product_manual` is empty.

**Style:** divider colour + width, trigger padding (responsive), trigger typography + colour (normal / open), icon colour, content padding (responsive), content typography + colour.

## Product Card widget

The widget file moved from `includes/class-product-card-widget.php` to `includes/Widgets/class-product-card-widget.php` in v2.0.0. Its widget name remains `wc_product_card`. Since v2.5.0 the render loop delegates sprite/card rendering to `WCPCE_Card_Renderer`. In v2.6.9 Auto mode's empty frontend state was corrected so shoppers see the configured customer-facing message while technical query guidance remains limited to Elementor editor/preview.

## PDP Product Upsells widget: `WCPCE_Product_Upsells_Widget` (v2.5.0)

Extends `\Elementor\Widget_Base`. Renders inside a Theme Builder single-product template; product context via `wc_get_product()` / `global $product`, editor fallback via the most recent published product. Style handles: `wc-product-card-elementor` and `wcpce-product-upsells`. Script handle: `wc-add-to-cart` only, declared statically for optional card action buttons.

### Data source

- WooCommerce upsell IDs from the current product (`$product->get_upsell_ids( 'view' )`).
- Default order preserves manual upsell order by mapping the stored IDs directly through `wc_get_product()` in order.
- Since v2.6.2, `upsell_orderby` can optionally sort visible linked upsells by WooCommerce total sales (`popularity`), highest first. Equal sales counts fall back to stored linked-product order.
- Products are filtered to published, visible `WC_Product` instances. Out-of-stock products may still render so the existing card stock label can communicate availability.
- No new ACF fields.

### Key private methods

| Method | Returns | Purpose |
|---|---|---|
| `validate_upsell_settings(array)` | `array` | Whitelist layout/card choices, normalise switchers and clamp text/number settings. |
| `get_current_product()` | `?\WC_Product` | Resolve product from queried object first, then global WC product (v2.5.8+). |
| `get_editor_fallback_product()` | `?\WC_Product` | Recent published product for editor preview. |
| `get_upsell_products(\WC_Product, int, string)` | `array` | Reads WooCommerce upsell IDs, primes post caches, maps IDs through `wc_get_product()`, filters invisible products, and returns linked-order or popularity-sorted products. |
| `build_card_settings(array)` | `array` | Maps widget controls into the settings shape expected by `templates/card.php`. |
| `render_editor_notices(?\WC_Product, array)` | `void` | Editor-only notices for no product context or no visible upsells. |
| `render_empty_state(array)` | `void` | Frontend empty message when `hide_empty` is disabled. |

### Shared card renderer

`WCPCE_Card_Renderer` renders `templates/card.php` for both the archive Product Card Grid and the Product Upsells widget. It provides:
- `render_svg_sprite()` for the card check/truck/leaf SVG symbols.
- `render_card(\WC_Product, array, int, string)` for template inclusion.
- `compute_card_data(\WC_Product, array, int)` for price, discount and badge data.

The existing Product Card Grid now calls `WCPCE_Card_Renderer::render_svg_sprite()` and `WCPCE_Card_Renderer::render_card()` in its render loop.

### Elementor controls (v2.5.0; v2.6.2)

**Content:** heading + heading tag, maximum products, product order (linked order or popularity), hide-empty behavior, empty-state text, card element toggles (Lipscore, savings, shipping, product-card USPs, stock label, hover image swap, action button), discount/custom badge controls and image size.

**Style:** mobile layout (horizontal scroll or compact grid), responsive columns/gaps, card body padding/gap, title clamp, heading typography/colour/alignment/spacing/background/border/radius, card background/border/radius/shadow, hover lift, image aspect/max-height/padding, title/price/badge/stock colours.

## PDP Product Cross-sells / Related widget: `WCPCE_Product_Related_Widget` (v2.6.0)

Extends `\Elementor\Widget_Base`. Renders inside a Theme Builder single-product template; product context resolves from the queried product first, then `global $product`; editor fallback uses the most recent published product. Style handles: `wc-product-card-elementor` and `wcpce-product-related`. Script handle: `wc-add-to-cart` only, declared statically for optional card action buttons.

### Data source

- WooCommerce cross-sell IDs from the current product (`$product->get_cross_sell_ids( 'view' )`) are the primary source.
- Cross-sell order is preserved by mapping stored IDs directly through `wc_get_product()`.
- If no visible cross-sells are available, the widget falls back to `wc_get_related_products( $product_id, $limit, $exclude_ids )`.
- Products are filtered to visible `WC_Product` instances and the current product ID is excluded.
- No new ACF fields.

### Key private methods

| Method | Returns | Purpose |
|---|---|---|
| `validate_related_settings(array)` | `array` | Whitelist layout/card choices, normalise switchers and clamp text/number settings. |
| `get_current_product()` | `?\WC_Product` | Resolve product from queried object first, then global WC product. |
| `get_editor_fallback_product()` | `?\WC_Product` | Recent published product for editor preview. |
| `get_recommendation_products(\WC_Product, int)` | `array` | Reads visible cross-sells first, falls back to visible related products when none are available. |
| `get_visible_products_from_ids(array, int, int)` | `array` | Primes post caches, maps IDs through `wc_get_product()`, preserves order and filters invisible/current products. |
| `build_card_settings(array)` | `array` | Maps widget controls into the settings shape expected by `templates/card.php`. |
| `render_editor_notices(?\WC_Product, array)` | `void` | Editor-only notices for no product context or no visible cross-sells/related products. |
| `render_empty_state(array)` | `void` | Frontend empty message when `hide_empty` is disabled. |

### Elementor controls (v2.6.0)

Same control surface as Product Upsells: heading + heading tag, maximum products, hide-empty behavior, empty-state text, card element toggles, discount/custom badge controls, image size, mobile layout, responsive columns/gaps, section/card styling, typography and colours.

## CSS architecture

**Product Card:** `product-card.css`. BEM `.wc-card__*`. Unchanged.

**PDP Gallery:** `product-gallery.css`. BEM `.wcpce-gallery__*`. Key rules:
- No `isolation: isolate` on the gallery wrapper (lightbox must escape to viewport).
- Stage is `position: relative`; nav buttons are `position: absolute` at `left: -18px` / `right: -18px` (desktop: half over image edge; mobile: `display: none`).
- Video thumbnail uses `<span>` with `background-image: url(...)` + `background-size: cover` — not `<img>` — so Elementor's global `img { height: auto }` cannot override the aspect ratio.
- All button hover/active/focus states use `!important` on colour/background to prevent Bourgini site-wide `button:hover` colour bleed.
- Lightbox: `display: none` when `aria-hidden="true"`, `display: flex` when open.

**PDP Product USP / Benefits:** `product-usps.css`. BEM `.wcpce-usps__*`. Key rules:
- Base layout uses CSS variable `--wcpce-usp-columns`; Elementor responsive controls set the variable per widget instance.
- The widget stylesheet provides stable list/cards/inline structure and sensible defaults only; colours, spacing, typography, radius, border and shadow are primarily Elementor style controls.
- No JavaScript and no hidden interaction state.

**PDP Delivery & Availability:** `product-delivery.css`. BEM `.wcpce-delivery__*`. Key rules:
- Base layout is a quiet stacked list; optional `.wcpce-delivery--pills` switches to compact wrapping items.
- Status icon colours have sensible defaults (green in-stock, amber unavailable, grey discontinued), but Elementor can override icon/text/item styling.
- No JavaScript and no hidden interaction state.

**PDP Product Upsells:** `product-upsells.css` plus `product-card.css`. BEM `.wcpce-upsells__*` for the section wrapper and existing `.wc-card__*` for each card. Key rules:
- The widget wrapper uses `isolation: isolate` like the Product Card Grid so card badges stay below sticky site chrome.
- Section CSS covers only heading, editor notice, empty state and mobile scroll; card visuals stay in `product-card.css`.
- Mobile can switch from compact grid to horizontal scroll via `.wcpce-upsells--mobile-scroll`.

**PDP Product Cross-sells / Related:** `product-related.css` plus `product-card.css`. BEM `.wcpce-related__*` for the section wrapper and existing `.wc-card__*` for each card. Key rules:
- The widget wrapper uses `isolation: isolate` like the Product Card Grid so card badges stay below sticky site chrome.
- Section CSS covers only heading, editor notice, empty state and mobile scroll; card visuals stay in `product-card.css`.
- Mobile can switch from compact grid to horizontal scroll via `.wcpce-related--mobile-scroll`.

## WBW Product Filter PRO integration — unchanged

Auto mode works natively. Three manual WBW settings required:
1. Product List / Loader Selector → `.wc-card-grid`
2. Product Container Selector → `.wcpce-grid-section`
3. Force Theme Templates → on

The WBW / Elementor Pro sticky header conflict is resolved outside the plugin with `position: sticky` on the Bourgini header template. The plugin ships no sticky-related code.
