# Glossary — Woo Card Chef

## Plugin identity

| Term | Meaning |
|---|---|
| **Woo Card Chef** | Plugin name (renamed from "WooCommerce Product Card for Elementor" in v1.0.31) |
| **S15 Webdesign** | Developer/author brand. Author URI: vaneekerenindustries.nl |
| **WCPCE** | Original plugin prefix. Still used in all PHP constants, CSS class prefixes, ACF field/group keys. |
| **Text domain** | `woo-card-chef` |
| **Plugin slug / folder name** | `wc-product-card-elementor` |

## Brand / business context

| Term | Meaning |
|---|---|
| **Bourgini** | Primary brand. Kitchen appliances. bourgini.com. Active deployment. |
| **Princess Traveller** | Second brand. Luggage. princesstraveller.com. Plugin designed for reuse. |
| **BourginiFitness** | Third brand. Fitness equipment. bourginifitness.com. Plugin designed for reuse. |
| **The Bourgini Company** | Company that owns all three brands. |
| **Robby van Eekeren** | Developer and primary user. |
| **Content editor** | Non-developer colleague who fills ACF fields per product. |

## Technical terms — Product Card widget

| Term | Meaning |
|---|---|
| **Auto mode** | Rides the main WP archive query. WBW, WC sorting, and pagination work natively. |
| **Manual mode** | Runs its own `WP_Query`. Used for landing pages and curated sections. |
| **Card data array** | `$card` array returned by `compute_card_data()` and passed to `card.php`. |
| **Sprite (card)** | Inline SVG block by `render_svg_sprite()`. Symbols: check, truck, leaf. Output once per page. |
| **WCPCE_Card_Renderer** | Shared renderer introduced in v2.5.0. Outputs the card sprite, computes card data, and includes `templates/card.php` for card-based widgets. |
| **OOS** | Out of stock. |
| **Hover swap** | Primary image crossfades to secondary on hover. Off by default. |
| **Badge priority (linksboven)** | Korting wins over Nieuw at top-left on the product card. Only one shows at a time. |
| **wcpce_paged** | Custom WP query var for manual-mode pagination. Avoids conflict with main WC archive `paged`. |
| **Path-only base URL** | Manual-mode pagination strips query args deliberately. See DECISIONS_LOG. |
| **wcpce-grid-section** | Stable CSS class wrapping the card grid output. WBW Product Container Selector target. |

## Technical terms — PDP Gallery widget (v2.0.0)

| Term | Meaning |
|---|---|
| **Gallery widget** | `WCPCE_Product_Gallery_Widget`. PDP Gallery Elementor widget introduced in v2.0.0. |
| **Slide** | Single gallery item. Either `type: image` or `type: video`. |
| **Thumbnail strip** | Row of small clickable thumbnails below the main image. |
| **Overflow indicator (+N)** | "+N" badge on the last visible thumbnail showing hidden slide count. |
| **Video slide** | Gallery slide backed by a YouTube video. Shows thumbnail + play button. Click opens lightbox. |
| **Badgebar** | Horizontal row of commercial badges (Korting, Nieuw, PFAS-vrij) above or below the gallery block. Badges show side-by-side — no priority conflict (unlike the card widget's top-left priority rule). |
| **Lightbox** | Full-screen overlay. Image mode: zoom/pan/pinch. Video mode: YouTube nocookie iframe. Always rendered outside `.wcpce-gallery` to escape stacking contexts. |
| **youtube-nocookie** | `www.youtube-nocookie.com` embed domain. Default for all gallery video embeds. |
| **mqdefault.jpg** | YouTube medium-quality thumbnail (320×180). Always available; consistent cropping. Used for both main preview and thumbnail strip. |
| **Background-image thumbnail** | Video preview uses `<span>` with CSS `background-image` + `background-size: cover`, not `<img>`. Bypasses Elementor's global `img { height: auto }`. |
| **ensureZoomDocumentEvents()** | JS method binding document-level zoom handlers once per instance. Guards with `zoomDocumentEventsBound`. |
| **initGallery(galleryEl)** | Central JS bootstrap helper. Guards on `.wcpce-gallery` element; both init paths use it. |
| **zoomImg** | Instance property tracking the active lightbox zoom image. Updated by `bindZoom()`. |
| **validate_gallery_settings()** | PHP private method. Whitelist/clamp before render. Same threat model as `validate_manual_settings()`. |
| **position_video_slides_before_thumbnail_overflow()** | PHP method. Places video slides at slot (thumbnail_count − 1) so they are always visible in the strip. |
| **acf_pro_active()** | Static PHP method checking `acf_get_field_types()` for repeater support. Used before rendering video slides and before registering the repeater field group. |

## Technical terms — Product USP / Benefits widget (v2.2.0)

| Term | Meaning |
|---|---|
| **Product USP / Benefits** | `WCPCE_Product_USPs_Widget`. PDP widget introduced in v2.2.0. Slug `wcpce_product_usps`. |
| **PDP USP** | One short benefit line shown on the product detail page near the buying area. |
| **pdp_usps** | ACF Pro repeater for PDP-specific USP lines. Content only. |
| **usp_text** | Single text sub-field inside `pdp_usps`; one row equals one visible USP. |
| **source_mode = auto** | Reads `pdp_usps` first, then WooCommerce short description, then Product Card USPs (`usp_1/2/3`). |
| **Content vs presentation split** | ACF stores only the USP text; Elementor controls layout, icons, columns, spacing, colours, typography, border, radius and shadow. |

## Technical terms - Product Delivery & Availability widget (v2.3.0)

| Term | Meaning |
|---|---|
| **Product Delivery & Availability** | `WCPCE_Product_Delivery_Widget`. PDP delivery/status widget introduced in v2.3.0. Slug `wcpce_product_delivery`. |
| **Availability state** | Internal state derived from product data: `in_stock`, `out_of_stock`, or `discontinued`. |
| **Delivery promise** | One global Elementor text line, default `Voor 23:00 besteld, morgen in huis`. No automatic date/cut-off calculation in v2.3.0. |
| **Free-shipping threshold** | Elementor number control, default `25`. Below threshold the widget shows `Gratis bezorging vanaf €25,-`; at/above threshold it shows `Gratis bezorging`. |
| **Conservative threshold comparison** | For variable products, the widget compares against the current/lowest display price first, so it does not overclaim free shipping for product families starting below the threshold. |
| **Discontinued state** | Product has existing `badge_niet_leverbaar` flag. The widget shows `Niet meer leverbaar` and suppresses delivery/free-shipping lines. |
| **Temporarily out of stock state** | WooCommerce says the product is not in stock, but `badge_niet_leverbaar` is not set. The widget shows `Tijdelijk uitverkocht` and optional `Niet direct leverbaar`. |

## Technical terms - Product Upsells widget (v2.5.0)

| Term | Meaning |
|---|---|
| **Product Upsells** | `WCPCE_Product_Upsells_Widget`. PDP widget introduced in v2.5.0. Slug `wcpce_product_upsells`. |
| **WooCommerce upsells** | Product IDs configured in Product data > Linked Products > Upsells. Used as the source for PDP accessories, spare parts and extensions. |
| **Manual upsell order** | The order set in WooCommerce is preserved by iterating the stored upsell IDs and calling `wc_get_product()` for each ID. |
| **Mobile scroll layout** | Optional mobile presentation using `.wcpce-upsells--mobile-scroll` to turn the card grid into a horizontal scroll row. |
| **Shared card template** | Upsells render through `WCPCE_Card_Renderer` and `templates/card.php`, so badges, prices, stock labels, images and optional action buttons match the Product Card Grid. |

## Technical terms - Product Cross-sells / Related widget (v2.6.0)

| Term | Meaning |
|---|---|
| **Product Cross-sells / Related** | `WCPCE_Product_Related_Widget`. PDP widget introduced in v2.6.0. Slug `wcpce_product_related`. |
| **WooCommerce cross-sells** | Product IDs configured in Product data > Linked Products > Cross-sells. Used as the primary source for the widget. |
| **Related Products fallback** | When no visible cross-sells are available, the widget falls back to WooCommerce's native `wc_get_related_products()` result. |
| **Manual cross-sell order** | The order set in WooCommerce is preserved by iterating the stored cross-sell IDs and calling `wc_get_product()` for each ID. |
| **Mobile scroll layout** | Optional mobile presentation using `.wcpce-related--mobile-scroll` to turn the card grid into a horizontal scroll row. |
| **Shared card template** | Cross-sells and related products render through `WCPCE_Card_Renderer` and `templates/card.php`, so cards match the Product Card Grid and Product Upsells. |

## ACF fields

### Product Card

| Field | Group | Type | Purpose |
|---|---|---|---|
| `card_title` | `group_wcpce_card_title` | text | Short title override |
| `card_hover_image` | `group_wcpce_card_title` | image (id) | Hover image override |
| `usp_1/2/3` | `group_wcpce_product_usps` | text | USP bullets |
| `badge_nieuw` | `group_wcpce_product_badges` | true_false | Nieuw badge |
| `badge_pfas_vrij` | `group_wcpce_product_badges` | true_false | PFAS-vrij badge |
| `badge_niet_leverbaar` | `group_wcpce_product_badges` | true_false | Niet meer leverbaar overlay |

### PDP Gallery (ACF Pro required)

| Field | Group | Type | Purpose |
|---|---|---|---|
| `pdp_gallery_videos` | `group_wcpce_pdp_gallery_media` | repeater | Container for video slides |
| `youtube_url` | sub-field | url | YouTube link |
| `video_title` | sub-field | text | Accessibility label |
| `video_thumbnail` | sub-field | image (id) | Custom thumbnail; overrides mqdefault |

### PDP Product USP / Benefits (ACF Pro required)

| Field | Group | Type | Purpose |
|---|---|---|---|
| `pdp_usps` | `group_wcpce_pdp_usps` | repeater | Container for PDP USP lines |
| `usp_text` | sub-field | text | One short USP line |

## Badge system — Product Card

| Badge | Position | Color | CSS class | Source |
|---|---|---|---|---|
| Korting | Linksboven | #3EC26D | `.wc-card__badge` | WC sale price |
| Nieuw | Linksboven | #B4211C | `.wc-card__badge--nieuw` | ACF `badge_nieuw` |
| PFAS-vrij | Linksonder | #57664d | `.wc-card__badge--pfas` | ACF `badge_pfas_vrij` |
| Niet meer leverbaar | Gecentreerd | rgba(0,0,0,0.6) | `.wc-card__niet-leverbaar-overlay` | ACF `badge_niet_leverbaar` |
| Tijdelijk uitverkocht | Rechtsonder | rgba(0,0,0,0.62) | `.wc-card__stock-label` | WC stock status |

## Badge system — PDP Gallery badgebar

| Badge | CSS class | Notes |
|---|---|---|
| Korting | `.wcpce-gallery__badge--discount` | Suppressed by niet_leverbaar |
| Nieuw | `.wcpce-gallery__badge--nieuw` | Suppressed by niet_leverbaar |
| PFAS-vrij | `.wcpce-gallery__badge--pfas` | Not suppressed; includes leaf SVG |

## Third-party plugins

| Plugin | Role |
|---|---|
| **Elementor (free)** | Widget framework, controls, rendering lifecycle |
| **Elementor Pro** | Available on sites; our widgets require free only |
| **WooCommerce** | Product data, pricing, stock, ordering |
| **ACF / ACF Pro** | Custom fields. ACF Pro required for gallery video and PDP USP repeaters. |
| **WBW Product Filter PRO** | AJAX filtering, auto mode only. Three WBW settings required. |
| **Lipscore** | Rating platform. Plugin outputs `<div class="lipscore-rating-small">` placeholder only. |
| **Kinsta** | Hosting. Staging: stg-bourginicom.kinsta.cloud |
| **Hello theme** | Active theme. Notable: global `img { height: auto }` affects gallery (solved with background-image approach). |

## Version history shorthand

| Range | What happened |
|---|---|
| v1.0.0–v1.0.74 | Foundation, card features, pagination saga, badges, accessibility, cleanup |
| v1.0.75–v1.0.79.13 | Phase 5 complete. WBW sticky resolved outside plugin. |
| v1.0.80–v1.0.84 | Phase 6 complete. Five helpers extracted. |
| v1.0.85–v1.0.89 | PDP Gallery development series. |
| **v2.0.0** | **Official release. Gallery widget (PDP Phase 1). includes/Widgets/, class-assets.php, gallery ACF group.** |
| **v2.1.0** | **Price & Promo Block widget (PDP Phase 2). Third widget. wc_get_price_to_display(), wcpce_price_reference_value filter, project-wide progressive-enhancement JS stance.** |
| **v2.2.0** | **Product USP / Benefits widget (PDP Phase 3). Fourth widget. ACF content-only repeater `pdp_usps > usp_text`, Elementor owns presentation, source fallback chain.** |
| **v2.2.1** | **Product USP / Benefits fallback fix. Short-description HTML lists now split into separate USP lines.** |
| **v2.3.0** | **Product Delivery & Availability widget (PDP Phase 4). Fifth widget. WooCommerce stock, existing `badge_niet_leverbaar`, delivery promise and free-shipping threshold.** |
| **v2.3.1** | **Patch: USP auto-mode eager-evaluation fix (sequential fallback chain); `sanitise_usp_text` renamed to `sanitize_usp_text`.** |
| **v2.3.2** | **Patch: Delivery widget list visual refinement — stock status green/bold, separator line, shipping muted, neutral icon colours. CSS-only.** |
| **v2.4.0** | **Product Accordion widget (PDP Phase 6). Sixth widget. Five sections: Description, Specifications, Reviews (Lipscore), FAQ (`product_faq` ACF repeater), Manual (new `product_manual` ACF file field). WCAG 2.2 AA accessible. Replaces accordion JS/CSS snippet, FAQ shortcode snippet, and ACPT plugin dependency.** |
| **v2.4.1** | **Patch: Product Accordion progressive-enhancement hardening, specs dimensions/weight fix, scoped h2 CSS, mbstring fallbacks, price helper per-request cache.** |
| **v2.5.0** | **Product Upsells widget (PDP Phase 7). Seventh widget. WooCommerce upsells rendered with the shared product-card template for accessories, spare parts and extensions; adds `WCPCE_Card_Renderer`.** |
| **v2.5.1** | **Patch: Product Upsells frontend PDP context fix. Uses the queried single-product before `global $product` so other product loops cannot make the widget read the wrong upsell source.** |
| **v2.5.2** | **Patch: Product Upsells retrieval now mirrors WooCommerce core by mapping stored upsell IDs directly through `wc_get_product()` and filtering visible products, instead of using `wc_get_products()`.** |
| **v2.5.3** | **Patch: Product Upsells trusts the stored WooCommerce upsell IDs and removes the extra non-core guard, while still excluding the exact current product ID and filtering invisible products.** |
| **v2.5.4** | **Patch: Product Upsells visual defaults refined for narrow PDP cards: smaller title type, PFAS-vrij badge off by default, and subtler temporarily out-of-stock label.** |
| **v2.5.5** | **Patch: Product Upsells typography defaults tuned to 12px/400 product titles and 15px/500 current or sale prices.** |
| **v2.5.6** | **Patch: Product Upsells typography is enforced through Elementor typography defaults plus a scoped frontend CSS fallback for existing templates.** |
| **v2.5.7** | **Patch: Product Upsells heading style controls expanded with alignment, margin, padding, background, border and border radius.** |
| **v2.5.8** | **Patch: Hardening release. `get_current_product()` ordering aligned across all PDP widgets (`get_queried_object()` first). `get_reviews_content()` simplified (single set/restore of `global $product`). `esc_attr()` → `tag_escape()` for heading tag names in Accordion widget.** |
| **v2.6.0** | **Product Cross-sells / Related widget (PDP Phase 8). Eighth widget. WooCommerce cross-sells rendered with the shared product-card template; falls back to WooCommerce Related Products when no visible cross-sells are available.** |
| **v2.6.1** | **Patch: Hardening release from v2.6.0 code review. Raw (unescaped) alt strings passed to `wp_get_attachment_image()` in card template and gallery video thumbnail; `$product` loop variable in Related widget renamed to `$candidate`; duplicate reviews modifier class removed in Accordion; `build_reviews_html()` docblock corrected.** |
| **v2.6.2** | **Patch: Product Upsells order control. Default preserves WooCommerce linked-product order; optional popularity mode sorts visible linked upsells by total sales.** |
| **v2.6.3** | **Patch: PHPCS security hardening. Escaping tightened for gallery aria labels, YouTube fallback thumbnails, and PDP product-list heading tags.** |
| **v2.6.4** | **Patch: Product Gallery YouTube thumbnail hotfix. YouTube IDs are sanitised without lowercasing so mixed-case IDs keep working.** |
| **v2.6.5** | **Patch: Product Gallery thumbnail style controls. Active border selector fixed, hover border colour control added, and hover/focus backgrounds locked against theme button styles.** |
| **v2.6.6** | **Patch: Elementor frontend hook guard. Gallery and Accordion only register Elementor hooks when `elementorFrontend.hooks.addAction` exists.** |
| **v2.6.7** | **Live release: Gallery accessibility hardening. Image alt text falls back to product name, inactive slides use `inert`, and production smoke test passed on Bourgini.com.** |
| **v2.6.8** | **Patch: Product Accordion manual fallback. If `product_manual` is empty, the widget can auto-match a PDF from the configured manuals directory using SKU/MPN tokens with trailing zeroes stripped.** |
| **v2.6.9** | **Hotfix: Product Card Grid empty-state correction. Auto-mode frontend requests show the customer-facing Dutch empty message instead of Elementor editor guidance.** |
| **v2.6.10** | **Compatibility metadata release. Records validation on WordPress 7.0.4, WooCommerce 11.0.1, Elementor 4.2.2, Elementor Pro 4.2.1, and PHP 8.3 without changing plugin behaviour.** |

## Technical terms — Product Accordion widget (v2.4.0)

| Term | Meaning |
|---|---|
| **Product Accordion** | `WCPCE_Product_Accordion_Widget`. PDP accordion widget introduced in v2.4.0. Slug `wcpce_product_accordion`. |
| **product_faq** | Existing ACF repeater (registered outside the plugin) with sub-fields `vraag` (text) and `antwoord` (textarea). Read by the widget via `get_field()`. |
| **product_manual** | New ACF `file` field registered in `group_wcpce_pdp_accordion`. Stores a PDF; return format `array` providing `url`, `filename`, and `title`. |
| **manuals_dir** | Elementor setting for the Product Accordion manual section. Relative to the WordPress root; defaults to `manuals`. Used only when `product_manual` is empty. |
| **Automatic manual match** | Product Accordion fallback that scans PDF filenames in `manuals_dir` and matches against product SKU/MPN values, including variants with trailing zeroes stripped. |
| **group_wcpce_pdp_accordion** | ACF field group registered by the plugin for the Product Accordion widget. Contains `product_manual`. Works with free ACF (no repeater needed). |
| **Lipscore tab callback** | The WC tab callback registered by Lipscore under key `lipscorereviews`. Called via `call_user_func()` inside an output buffer to capture the reviews panel HTML. |
| **Progressive-enhancement accordion** | The Product Accordion renders content open in initial server HTML for no-JS/crawler contexts, then JS applies the configured closed/open state after initialization. |
| **hidden attribute** | HTML attribute set on closed accordion panels after JS initialization. More reliable than CSS `display:none` for accessibility because it removes content from tab order and screen reader traversal. |
| **FAQ inner accordion** | Nested accordion pattern within the FAQ section. Each `vraag`/`antwoord` pair is its own toggle, using the same `button`/`aria-expanded`/`aria-controls`/`hidden` pattern as the outer accordion. |

## Technical terms — Price & Promo Block widget (v2.1.0)

| Term | Meaning |
|---|---|
| **Price & Promo Block** | `WCPCE_Product_Price_Widget`. PDP price widget introduced in v2.1.0. Slug `wcpce_product_price`. |
| **Reference price** | The struck-through "van" price. Defaults to the WooCommerce regular price, run through the `wcpce_price_reference_value` filter. |
| **wcpce_price_reference_value** | Filter on the raw reference price. Integration point for a 30-day-lowest (NL Omnibus/Prijzenwet) source without changing the widget. |
| **wc_get_price_to_display()** | WooCommerce function returning the amount to show per the `woocommerce_tax_display_shop` setting. Used for every displayed amount in the Price & Promo Block so it matches `get_price_html()` and the Product schema. |
| **Vanaf €X** | Variable-product display: the lowest current variation price with a configurable prefix. Alternative to the full "€X – €Y" range. |
| **Discount chip** | `.wcpce-price__chip`. Green Korting-coloured "-X%" / "Tot -X%" pill. Visually aria-hidden; an `.wcpce-price__sr-only` span carries the readable "X% korting". |
| **Compact / Extended** | Two layout modes controlling prominence only (inline vs prominent). Which elements show is governed by independent content toggles. |
| **Ghost anchor** | A struck reference price the item never actually had. Avoided: variable products on sale show no single struck reference (the lowest variation never had the best-discount variation's regular price). |

**Note on "zero-JS":** the term is scoped, not project-wide. The product card widget and purely presentational widgets (Price & Promo Block) ship no JS. Interactive widgets (Gallery; future Add to Cart) add scoped, deferred JS. The project principle is progressive enhancement — see DECISIONS_LOG.
