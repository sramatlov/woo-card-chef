# Project Overview — Woo Card Chef

## What it is

Woo Card Chef is a custom Elementor widget plugin for WooCommerce. The current release is **v2.6.9** and it ships eight widgets:

1. **Product Card widget** — renders product grids as richly designed product cards on category and archive pages. Replaces the default WooCommerce product loop and the EAEL product grid widget. Since v2.6.9, empty search/archive results always show the configured customer-facing message on the frontend while technical guidance remains editor-only.
2. **PDP Gallery widget** *(new in v2.0.0)* — replaces the default WooCommerce product image and gallery on the product detail page (PDP) with a slide-based gallery supporting images, YouTube video slides, lightbox, zoom, and a badgebar.
3. **Price & Promo Block widget** *(new in v2.1.0)* — replaces the default WooCommerce price output on the PDP with a status-aware price block: regular/sale price, struck reference, discount-percentage chip, savings amount, and "Tot -X%" / "Vanaf €X" handling for variable products.
4. **Product USP / Benefits widget** *(new in v2.2.0)* — renders short PDP-specific benefit lines near the buying area. Content comes from a simple ACF Pro repeater (`pdp_usps > usp_text`) with fallback to short description and product-card USPs; layout and styling live in Elementor.
5. **Product Delivery & Availability widget** *(new in v2.3.0)* - renders PDP stock status, delivery promise and free-shipping threshold near the buy section. Uses WooCommerce stock, existing `badge_niet_leverbaar`, and price helper data; text and styling live in Elementor.
6. **Product Accordion widget** *(new in v2.4.0, hardened in v2.4.1, v2.5.8 and v2.6.8)* — replaces the default WooCommerce product tabs on the PDP with a fully accessible, server-rendered accordion. Five sections: Description, Specifications, Reviews (Lipscore), FAQ (ACF repeater `product_faq`), and Manual (ACF file field `product_manual`). Each section is hidden when its content source is empty. In v2.4.1 the accordion became progressive-enhancement safe: content renders open in server HTML for no-JS/crawler contexts, then JS applies the configured collapsed state. In v2.5.8 `get_current_product()` was aligned to prefer `get_queried_object()` before `global $product`, and `get_reviews_content()` was simplified to a single set/restore of `global $product`. Since v2.6.8, an empty `product_manual` field falls back to automatic PDF matching in a configurable WordPress-root-relative manuals directory using SKU/MPN tokens.

7. **Product Upsells widget** *(new in v2.5.0)* - renders WooCommerce upsells on the PDP using the same product-card template as the Product Card Grid. Bourgini uses this for accessories, spare parts and extensions; layout and card element visibility live in Elementor.
8. **Product Cross-sells / Related widget** *(new in v2.6.0)* - renders WooCommerce cross-sells on the PDP using the same product-card template and controls as Product Upsells. When no visible cross-sells are available, it falls back to WooCommerce related products.

## Who it's for

**Primary developer:** Robby van Eekeren, e-commerce specialist at The Bourgini Company, Netherlands.
**Plugin author/brand:** S15 Webdesign (vaneekerenindustries.nl).
**End users:** Robby + one content editor who fills in ACF fields per product.

## Which webshops

Built primarily for **Bourgini.com** — a Dutch kitchen appliance brand. Designed generically so it can be reused on:
- **PrincessTraveller.com** — luggage brand
- **BourginiFitness.com** — fitness equipment brand

All three run WooCommerce on Kinsta hosting with Elementor + Hello theme.

## Core feature list

### Product Card widget (v1.x)
ACF-driven card title, hover image and USPs; smart discount badge; Nieuw / PFAS-vrij / Niet meer leverbaar badges; free shipping pill; Lipscore rating placeholder; savings line; LCP-optimised images; server-rendered pagination; WBW Product Filter PRO auto-mode compatibility; and a customer-facing empty state for frontend archives/searches.

### PDP Gallery widget (v2.0.0)

**Sources:**
- WooCommerce featured image
- WooCommerce gallery images
- YouTube video slides via ACF repeater (`pdp_gallery_videos`) — requires ACF Pro; positioned so video remains visible before the thumbnail overflow indicator

**Layout:**
- Slide-based gallery with single main image and thumbnail strip below
- Configurable visible thumbnail count with +N overflow indicator
- Video slides always positioned at slot (thumbnail_count − 1) so they are never hidden behind the overflow indicator

**Badgebar:**
- Korting / Nieuw / PFAS-vrij badges in a horizontal bar above or below the gallery
- Badge styling matches the product card exactly (same colours, font, border-radius, PFAS leaf icon, equal badge heights)
- Niet meer leverbaar suppresses Korting and Nieuw (PFAS-vrij is an attribute badge and is not suppressed)
- Status overlays (Niet meer leverbaar / Tijdelijk uitverkocht) render on the main image

**Navigation & interaction:**
- Desktop: semi-transparent white prev/next chevron buttons, half over the image edge
- Mobile: swipe only (no visible nav buttons); thumbnails serve as active-slide indicator
- Lightbox for images (click to open) and video (play button to open); same lightbox instance for both
- Image lightbox: click-to-zoom, desktop drag-to-pan, mobile pinch-zoom
- Video lightbox: lazy YouTube nocookie embed (iframe only created on play-button click)
- Keyboard navigation: Arrow keys, Escape to close lightbox
- Accessible: focus trap in lightbox, focus return on close, attachment alt text with product-name fallback when media alt is empty, `aria-hidden` plus `inert` on inactive slides, and `tabindex="-1"` on hidden play buttons

**Performance:**
- First image: `fetchpriority="high"`, `loading="eager"` (LCP)
- Remaining images: `loading="lazy"`
- Thumbnails: WordPress `thumbnail` size
- Video thumbnails: YouTube `mqdefault.jpg` (or custom ACF attachment)
- Gallery JS: deferred, only enqueued where widget is present
- All attachment caches bulk-primed before render loop (images + video thumbnails)

**Elementor controls:** image fit, aspect ratio, thumbnail count, lightbox on/off, zoom on/off, badgebar position, per-badge toggles, badge format, style controls for gallery/thumbnails/badges/play button. Full list in TECHNICAL_SPEC.

**Out of scope for v2.0.0 (v2.1+):** variation image swap, in-slide video playback, video_position interleaving, thumbnails beside (not below) main image, functional mobile_thumbnail_count in positioning logic.

### Price & Promo Block widget (v2.1.0)

**Sources:** WooCommerce product price data via `WCPCE_Price_Helper` (regular, sale, display, discount %, savings, mixed-discount flag). No new ACF fields; reads only `badge_niet_leverbaar` for status.

**Display:**
- Simple on sale: struck reference price (with visible label) + sale price + optional discount-percentage chip + optional savings line.
- Simple not on sale: single current price.
- Variable: "Vanaf €X" (lowest current price) or full WooCommerce range; on sale adds a "Tot -X%" chip (mixed discounts). No single struck reference for variable on sale — avoids anchoring against a reference the lowest variation never had.
- Niet meer leverbaar: price dimmed, all discount framing dropped.
- Compact (inline) vs Extended (prominent) layout — prominence only; element visibility via independent toggles.

**Technique:** all displayed amounts via `wc_get_price_to_display()` (tax-correct, matches `get_price_html()` and Product schema); reference via the `wcpce_price_reference_value` filter (30-day Omnibus source injectable later); reuses `WCPCE_Badge_Helper` for the chip text, "Tot" prefix and accessible label; server-side, zero JS; no own structured data.

**Elementor controls:** layout mode, variable price display (From/Range), per-element toggles (reference, percentage, savings, "Tot" prefix), editable labels (reference, sale, from), status dim toggle, and style controls (colours, typography, chip, spacing, opacity) wired via Elementor `selectors`.

### Product USP / Benefits widget (v2.2.0)

- PDP-specific USP repeater with one text field per row: `usp_text`
- Default source chain: PDP USPs -> WooCommerce short description -> product-card USPs
- Explicit source modes for editors who want to force one source
- Optional heading controlled in Elementor
- List, compact-card, and inline-row layouts
- Optional global icon with checkmark/dot style
- Responsive columns, spacing, padding, typography, colours, borders, radius, and shadow via Elementor controls
- Server-rendered, zero-JS

### Product Delivery & Availability widget (v2.3.0)

**Sources:**
- WooCommerce product stock status via `WCPCE_Stock_Helper`
- Existing ACF/meta flag `badge_niet_leverbaar` via `WCPCE_ACF_Helper`
- Product price data via `WCPCE_Price_Helper`
- Delivery promise text and free-shipping threshold via Elementor controls

**Display:**
- In stock, below threshold: `Op voorraad`, configured delivery promise, `Gratis bezorging vanaf €25,-`
- In stock, at/above threshold: `Op voorraad`, configured delivery promise, `Gratis bezorging`
- Temporarily out of stock: `Tijdelijk uitverkocht` plus optional `Niet direct leverbaar`; no tomorrow/free-shipping lines
- Discontinued: `Niet meer leverbaar`; no tomorrow/free-shipping lines

**Elementor controls:** stock/delivery/shipping toggles, editable labels, threshold amount, list vs compact-pill layout, icons on/off, spacing, typography, colours, background, border, radius and shadow.

**Technique:** server-rendered, zero-JS, no new ACF fields. The block stays narrow: availability, delivery promise and free-shipping threshold only; returns/warranty/payment trust messaging belongs in a later separate pattern.

### Product Upsells widget (v2.5.0)

**Source:** WooCommerce upsells on the current product (`Linked Products > Upsells`). The widget preserves manual upsell order by default, can optionally sort linked upsells by WooCommerce popularity, filters to visible published products, and shows an editor notice when no product context or no visible upsells are available.

**Display:** optional heading (default `Accessoires en onderdelen`), empty-state handling, responsive product-card grid, and optional horizontal scroll on mobile. Cards reuse `templates/card.php` through `WCPCE_Card_Renderer`, including product-card image, price, badge, stock and optional add-to-cart logic.

**Elementor controls:** maximum products, product order, hide-empty behavior, heading/tag, heading styling, mobile layout, responsive columns/gaps, compact card spacing, image sizing, badge toggles, stock label, hover swap, Lipscore, USPs, shipping pill, savings line and optional action button.

**Technique:** no new ACF fields and no widget-specific JS. `get_script_depends()` declares WooCommerce `wc-add-to-cart` statically because the shared card template can render AJAX add-to-cart buttons when enabled.

### Product Cross-sells / Related widget (v2.6.0)

**Source:** WooCommerce cross-sells on the current product (`Linked Products > Cross-sells`) first. If no visible cross-sells are available, the widget falls back to WooCommerce related products from `wc_get_related_products()`.

**Display:** optional heading (default `Ook interessant`), empty-state handling, responsive product-card grid, and optional horizontal scroll on mobile. Cards reuse `templates/card.php` through `WCPCE_Card_Renderer`, so image, price, badge, stock and optional add-to-cart behavior match the Product Card Grid and Product Upsells.

**Elementor controls:** same card element, badge, responsive layout, section heading, card style, typography, colour and mobile layout controls as Product Upsells.

**Technique:** no new ACF fields and no widget-specific JS. `get_script_depends()` declares WooCommerce `wc-add-to-cart` statically because the shared card template can render AJAX add-to-cart buttons when enabled.

### ACF field groups (6 total)

| Group | Fields |
|---|---|
| `group_wcpce_card_title` | `card_title`, `card_hover_image` |
| `group_wcpce_product_usps` | `usp_1`, `usp_2`, `usp_3` |
| `group_wcpce_product_badges` | `badge_nieuw`, `badge_pfas_vrij`, `badge_niet_leverbaar` |
| `group_wcpce_pdp_gallery_media` | `pdp_gallery_videos` repeater (ACF Pro) |
| `group_wcpce_pdp_accordion` | `product_manual` file field |
| `group_wcpce_pdp_usps` | `pdp_usps` repeater (ACF Pro) |

### Compatibility
- **WBW Product Filter PRO** — product card auto mode; three WBW settings required (see TECHNICAL_SPEC). The WBW / Elementor Pro sticky header conflict is resolved outside the plugin via CSS `position: sticky` on the Bourgini header template.
- **PDP Theme Builder** — gallery widget renders inside a Theme Builder single-product template; no product-ID control needed.
