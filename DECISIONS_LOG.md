# Decisions Log — Woo Card Chef

## v2.6.0 Product Cross-sells / Related Decisions

### Decision: Cross-sells first, Related Products fallback
**Chose:** `WCPCE_Product_Related_Widget` reads WooCommerce Cross-sells from the current product first. If no visible cross-sells are available, it falls back to WooCommerce's native `wc_get_related_products()` result.
**Why:** Bourgini currently uses Related Products in the bottom PDP block but has very few Cross-sells configured. Cross-sells are the better hand-curated commerce source when available; Related Products keep the block useful while product-level cross-sell data is incomplete.
**Rejected:** Showing generic related products regardless of cross-sells. That would ignore deliberate editor curation when Cross-sells are present.

### Decision: Reuse Product Upsells card controls and shared renderer
**Chose:** The Product Cross-sells / Related widget uses the same `WCPCE_Card_Renderer`, `templates/card.php` shape, mobile-scroll behavior and card element controls as Product Upsells.
**Why:** The block is another PDP product grid. Users should not have to relearn a different control surface, and card behavior must stay consistent across Product Card Grid, Product Upsells and Product Cross-sells / Related.
**Rejected:** A simplified related-products-only template. That would create a third card implementation and drift from pricing, badge, stock and accessibility behavior already covered by the shared renderer.

## v2.5.8 Hardening Decisions

### Decision: `get_current_product()` ordering standardised across all PDP widgets
**Chose:** All six PDP widgets (Gallery, Price, USP, Delivery, Accordion, Upsells) now check `get_queried_object()` first, then fall back to `global $product`.
**Why:** The v2.5.1 patch fixed this ordering in the Upsells widget specifically because a product loop rendered earlier on the PDP can leave `global $product` pointing at a card item instead of the PDP product. That same risk exists for all other PDP widgets. A code review of v2.5.7 identified the inconsistency. Standardising on `get_queried_object()` first makes the entire PDP widget layer robust against template ordering changes.
**Rejected:** Leaving the five older widgets with `global $product` first. Even though the Bourgini template renders the Upsells widget last (making the bug unlikely to manifest), relying on template order for correctness is fragile.

### Decision: `get_reviews_content()` simplified — single set/restore of `global $product`
**Chose:** `get_reviews_content()` in the Product Accordion widget now sets `global $product` once, just before the Lipscore tab callback, and restores it via `finally`. The previous double set/restore pattern (also around `apply_filters('woocommerce_product_tabs')`) is removed.
**Why:** The `woocommerce_product_tabs` filter builds the tab registry — it does not render tab content and does not require the global product context. Setting and restoring the global around that filter was unnecessary and made the method harder to follow. The single set/restore around the actual callback is both sufficient and easier to reason about.
**Rejected:** Keeping the double pattern. It was technically correct but introduced two points where a future developer might add code between the set and restore, compounding the confusion.

### Decision: `tag_escape()` over `esc_attr()` for HTML tag names in Accordion widget
**Chose:** `render_accordion_item()` in the Product Accordion widget now uses `tag_escape($heading_tag)` for both the opening and closing tag, matching the existing pattern in the USP and Upsells widgets.
**Why:** `tag_escape()` is the WordPress-canonical function for HTML tag names. It strips characters that are invalid in a tag name. `esc_attr()` is designed for attribute values and is broader than necessary here. Both are safe in practice because `validate_accordion_settings()` whitelists the heading tag to `h2/h3/h4`, but using the semantically correct function makes intent clear and keeps the codebase consistent.
**Rejected:** Leaving `esc_attr()` in place. The validate whitelist makes it safe, but inconsistency between widgets adds cognitive overhead during future reviews.

## v2.5.0 Product Upsells Decisions

### Decision: Product Upsells uses WooCommerce upsells as the first Phase 7 source
**Chose:** `WCPCE_Product_Upsells_Widget` reads `$product->get_upsell_ids()` and preserves the manual WooCommerce upsell order.
**Why:** Bourgini already uses Elementor upsells for accessories and spare parts on the PDP. Reusing the existing WooCommerce linkage keeps the editor workflow intact and avoids adding an ACF relationship model before the source taxonomy/design brief is settled.
**Rejected:** A new ACF relationship field or category/SKU compatibility mapping in the MVP. Those can still be added later as explicit source modes if the merchandising model needs them.

### Decision: Upsells reuse the product-card template through `WCPCE_Card_Renderer`
**Chose:** Extract card sprite output, card data computation and `templates/card.php` inclusion into `WCPCE_Card_Renderer`; the existing Product Card Grid and the new Product Upsells widget both call it.
**Why:** The PDP upsells block must show the same product-card semantics: price, stock labels, badges, image handling, optional add-to-cart and accessibility labels. A shared renderer keeps that behavior consistent and prevents a second card implementation from drifting.
**Rejected:** Copying a simplified card partial into the upsells widget.

## v2.4.1 Hardening Decisions

### Decision: Accordion panels render open before JS
**Chose:** The Product Accordion renders all panels and FAQ answers open in the initial server HTML. `product-accordion.js` then reads `data-default-open` and applies the configured open/closed state with `aria-expanded` and the HTML `hidden` attribute after initialization.
**Why:** The accordion is an interactive enhancement on top of commerce content. Rendering content hidden in initial HTML is acceptable for screen readers once JS has run, but it creates unnecessary risk for no-JS, crawler, SEO and GEO contexts. Server-rendered open content is the safest fallback; JS still restores the intended accessible closed state for normal visitors.
**Rejected:** Server-rendering closed panels with `hidden` and relying on crawlers to treat hidden accordion content as equivalent.

### Decision: Price helper data cache is per request only
**Chose:** `WCPCE_Price_Helper::get_product_price_data()` stores results in a static local cache keyed by product ID or object hash.
**Why:** Multiple PDP widgets can request the same product price data in one render. A per-request cache avoids repeated variation-price calculations without introducing persistent cache invalidation concerns.
**Rejected:** Transient or object-cache persistence for price helper results. Price display depends on live WooCommerce product state and shop context; persistent caching would need careful invalidation that is not justified here.

## Architecture

### Decision: Single Elementor widget → two widgets (v2.0.0)
**Chose:** Two separate Elementor widgets — `WC_Product_Card_Elementor_Widget` (product grid) and `WCPCE_Product_Gallery_Widget` (PDP gallery).
**Why:** The gallery is a fundamentally different UI from the product card grid. It requires its own JS, its own CSS, its own slide/lightbox/zoom logic, and its own Elementor controls. Combining them in one widget class would make both unmanageable. Two widgets in the same plugin share the helper layer (`includes/Helpers/`) and ACF fields without duplicating business logic.

### Decision: Widget files moved to `includes/Widgets/` (v2.0.0, R6)
**Chose:** `includes/Widgets/class-product-card-widget.php` and `includes/Widgets/class-product-gallery-widget.php`.
**Previously:** `includes/class-product-card-widget.php`.
**Why:** Grouping widget classes in a dedicated subdirectory is the natural extension of the `includes/Helpers/` pattern established in Phase 6. Makes the directory structure self-documenting as more widgets are added.

### Decision: `class-assets.php` (R7) for centralised asset registration
**Chose:** All `wp_register_style()` and `wp_register_script()` calls in a dedicated `WCPCE_Assets` class, hooked to `wp_enqueue_scripts`.
**Rejected:** Each widget registering its own assets.
**Why:** With two widgets, centralised registration gives one authoritative place to update version strings, dependencies, and file paths. Elementor's `get_style_depends()` / `get_script_depends()` on each widget drive the actual enqueueing — the assets class just ensures the handles exist.

### Decision: Gallery JS registered with `strategy: 'defer'`
**Chose:** `array('in_footer' => true, 'strategy' => 'defer')` in `wp_register_script()`.
**Why:** The gallery initialises on `DOMContentLoaded` and Elementor's `frontend/element_ready` hook — both fire after parsing. Deferring keeps the script off the critical render path. Falls back to in-footer loading on WP < 6.3.

### Decision: Gallery JS breaks the zero-JS architecture (v2.0.0)
**Context:** The product card widget has been zero-JS since v1.0.54 (AJAX pagination refactor). TECHNICAL_SPEC previously stated "No JavaScript file ships with the plugin."
**Chose:** Introduce `product-gallery.js` for the gallery widget.
**Why:** Swipe, lightbox, zoom, pinch-zoom, and lazy YouTube embeds are impossible without JS. The zero-JS rule was specific to AJAX pagination/load-more on the product grid — it was never a goal for interactive PDP widgets. The gallery JS is scoped, deferred, and only enqueued on pages where the widget is present.
**Updated rule:** The product card widget remains zero-JS. Gallery widget JS is explicit and controlled.

---

## PDP Gallery architecture

### Decision: Theme Builder single-product context, no product-ID control
**Chose:** Widget resolves product from `get_queried_object()` / `global $product` automatically via WC's query context.
**Rejected:** A product-ID Elementor control for manual product selection.
**Why:** All Bourgini PDP pages use Elementor Theme Builder single-product templates. In that context the queried object is the product — exactly as all other WooCommerce product widgets work. A product-ID control would be redundant and would invite misconfiguration. Editor preview uses `get_editor_fallback_product()` (most recent published product). Since v2.5.8, `get_queried_object()` is checked first, before `global $product`, across all PDP widgets.

### Decision: Lightbox rendered outside `.wcpce-gallery` (v2.0.0)
**Chose:** Lightbox `<div>` rendered as a sibling after the `.wcpce-gallery` wrapper, not inside it. JS finds it via `document.getElementById('wcpce-gallery-lb-{widget_id}')`.
**Rejected:** Inside the `.wcpce-gallery` wrapper.
**Why:** `position:fixed` only escapes to the viewport when no ancestor creates a stacking context (transform, isolation, will-change, etc.). The gallery wrapper had no stacking context, but we do not want to depend on that remaining true as CSS evolves. Rendering the lightbox outside the widget ensures it is always at the top of the stacking order, regardless of any future styling on ancestor elements.

### Decision: Video preview as CSS background-image, not `<img>` (v1.0.87)
**Chose:** `<span class="wcpce-gallery__video-thumb" style="background-image:url(...)">` with `background-size: cover`.
**Rejected:** `<img>` element with object-fit CSS.
**Why:** Elementor's global stylesheet sets `img { height: auto }` which overrides `object-fit: cover` and makes image elements respect their intrinsic aspect ratio. This caused YouTube thumbnails (intrinsically 16:9) to render as 16:9 strips inside the 1:1 gallery frame. A CSS background layer is not affected by Elementor's global image rules and fills the configured aspect-ratio frame correctly.

### Decision: YouTube thumbnail source — `mqdefault.jpg`, not `maxresdefault.jpg` (v1.0.86)
**Chose:** `https://i.ytimg.com/vi/{id}/mqdefault.jpg`.
**Rejected:** `maxresdefault.jpg`.
**Why:** `maxresdefault.jpg` is inconsistently available (not all videos have it; YouTube may return a 120×90 placeholder instead). `mqdefault.jpg` (320×180) is always available and crops consistently in the 1:1 gallery frame. Thumbnail strip already used mqdefault; main preview aligned to the same source.

### Decision: Video slides always positioned at slot (thumbnail_count − 1)
**Chose:** `position_video_slides_before_thumbnail_overflow()` inserts video slides immediately before the last visible thumbnail slot.
**Rejected:** Videos always at the end of all slides (after all WC images).
**Why:** When the total slide count exceeds `thumbnail_count`, the last visible thumbnail carries the +N overflow indicator. If videos were appended after all WC images, they could easily end up hidden behind the overflow. Positioning them at slot thumbnail_count − 1 ensures they are always visible in the strip regardless of how many WC gallery images the product has.

### Decision: Video-lightbox always rendered when video slides are present (v1.0.88)
**Chose:** `$render_lightbox = $enable_lb || $has_video_slides`. Lightbox HTML is output whenever video slides exist, even if the image lightbox Elementor control is off.
**Rejected:** Only render lightbox when `enable_lightbox = yes`.
**Why:** A visible play button must always work. If the play button opens nothing, it is a broken UI. The image-lightbox setting controls whether clicking a product image opens the lightbox — it should not control whether video play buttons work. Both concepts are separate; the original implementation conflated them.

### Decision: Strict YouTube host validation in `extract_youtube_id()` (v1.0.89)
**Chose:** Validate `wp_parse_url($url, PHP_URL_HOST)` against an explicit allowlist before running ID regex.
**Rejected:** Regex-only approach (ID-pattern match without host check).
**Why:** A URL like `https://malicious.example.com/?v=dQw4w9WgXcW` would pass an ID-only regex but should never be treated as a YouTube embed. The host check ensures only `youtube.com`, `youtu.be`, and `youtube-nocookie.com` URLs are embedded.

### Decision: Document-level zoom listeners bound once per instance (v1.0.88/v1.0.89)
**Chose:** `ensureZoomDocumentEvents()` with a `zoomDocumentEventsBound` boolean. Called once from `bindZoom()`. Document handlers act on `this.zoomImg` (updated per lightbox open).
**Rejected:** Adding document listeners each time `bindZoom()` is called (once per lightbox image render).
**Why:** `bindZoom()` runs every time the lightbox shows a new image. Without the guard, each navigation adds a new `mousemove` + `mouseup` listener to `document`. After 10 navigations there are 10 listener sets firing per mouse move — measurable performance degradation and a memory leak.

### Decision: `initGallery(galleryEl)` central helper (v1.0.88)
**Chose:** A shared `initGallery(galleryEl)` function that guards on the `.wcpce-gallery` element itself. Both `DOMContentLoaded` (via `initAllGalleries`) and the `elementorFrontend` hook call it.
**Rejected:** Separate init logic in each hook with different guard targets.
**Why:** When both hooks fire (possible on Theme Builder pages), the earlier fix set the flag on different DOM elements in each path, making the guard ineffective. Using the same element as the flag target in both paths guarantees exactly-once initialisation.

---

## Product Card widget — existing decisions unchanged

### Decision: AJAX pagination removed (v1.0.54)
See previous DECISIONS_LOG entries. Server-rendered pagination is stable since v1.0.69. Not returning to AJAX.

### Decision: Manual mode pagination strips query args
Path-only base URL since v1.0.69. Deliberate trade-off. UTM preservation remains a future option via a targeted whitelist — not a wholesale re-introduction.

### Decision: No global WooCommerce archive overrides (v1.0.72)
`loop_shop_per_page` and all `pre_get_posts` hooks are forbidden on the product card widget.

### Decision: Validation at the query/render boundary
`validate_manual_settings()` (card widget, v1.0.75) and `validate_gallery_settings()` (gallery widget, v1.0.89) are defensive against data corruption, not against attacker input. Elementor settings are only writable by authenticated editors.

### Decision: `get_script_depends()` must always return a static array
Calling `get_settings_for_display()` inside `get_script_depends()` causes a fatal TypeError — settings are null at that Elementor lifecycle stage. The card widget returns `['wc-add-to-cart']` unconditionally. The gallery widget returns `['wcpce-product-gallery']` unconditionally. This rule applies to all present and future widgets.

### Decision: WBW sticky header fix outside the plugin (v1.0.79.13)
The WBW / Elementor Pro sticky header conflict is resolved with native CSS `position: sticky` on the Bourgini header template. The plugin ships no sticky-related code. The standalone `bourgini-wbw-elementor-sticky-guard.php` snippet is obsolete.

---

## PDP Price & Promo Block architecture (v2.1.0)

### Decision: Project-wide JavaScript stance — progressive enhancement, not zero-JS
**Context:** "Zero-JS" was sometimes treated as a whole-project goal. It never was. The rule originated with the v1.0.54 removal of AJAX pagination/load-more from the product card grid (SEO, back-button UX, WBW facet integrity). The Gallery widget (v2.0.0) deliberately introduced `product-gallery.js` because swipe/lightbox/zoom/lazy-embed are impossible without JS.
**Chose:** Progressive enhancement as the project-wide principle. Core commerce content is always server-rendered; JavaScript is added only where interaction genuinely requires it — scoped per widget, deferred, registered in `class-assets.php`, and enqueued only on pages where the widget renders (via `get_script_depends()`).
**Kept absolute:** The product card widget remains zero-JS. Purely presentational widgets (Price & Promo Block, USP, and any non-interactive block) ship no JS; prefer native HTML/CSS (e.g. `<details>`/`<summary>`) over scripting for light interactions where it suffices.
**JS is expected for:** Add to Cart (quantity, add-to-cart feedback, states, sticky mobile CTA), Accordion (unless built with `<details>`/`<summary>`), and any carousel.
**Rejected:** (a) Reintroducing JS to the product card grid; (b) treating "zero-JS" as a blanket constraint that would block legitimately interactive PDP widgets.
**To revisit:** Once a second JS-bearing widget ships (Add to Cart), decide whether each widget keeps its own scoped script or a small shared base emerges (init guard, a11y/live-region helpers). The Gallery's `initGallery()` pattern and the reserved `.wcpce-sr-live` class are the anchor points.
**Unchanged:** `get_script_depends()` always returns a static array (lifecycle rule). The Price & Promo Block returns an empty array.

### Decision: All displayed amounts via `wc_get_price_to_display()`
**Chose:** Every amount the Price & Promo Block renders (reference, sale, current, savings) is passed through `wc_get_price_to_display( $product, array( 'price' => $raw ) )`.
**Why:** The helper returns base prices (`get_regular_price()`, `get_sale_price()`, `get_variation_prices()`), which are not adjusted for the `woocommerce_tax_display_shop` setting. `wc_get_price_to_display()` respects it, so the block matches WooCommerce's own `get_price_html()` and the Product schema under both tax-inclusive and tax-exclusive shops. The discount percentage is tax-neutral and is not converted.
**Divergence from card:** the card widget uses `wc_price()` on raw helper values. On Bourgini (prices entered and displayed incl. VAT) base == display, so there is no visible difference, which is why it never surfaced. Aligning the card is a separate optional task, not done here.

### Decision: Discount reference via the `wcpce_price_reference_value` filter
**Chose:** The struck-through reference uses `apply_filters( 'wcpce_price_reference_value', $raw_regular, $product )` before tax-display conversion, and the percentage and savings derive from that same (filtered) reference.
**Why:** Keeps the displayed "van" price, the percentage and the savings internally consistent from one source, and provides a single integration point to inject a 30-day-lowest reference (NL Omnibus/Prijzenwet) later without changing the widget. With no filter attached the reference equals the WooCommerce regular price (not necessarily Omnibus-compliant — accepted, documented in KNOWN_ISSUES).

### Decision: No struck reference for variable products on sale
**Chose:** Variable products on sale show "Vanaf €X" (lowest current price) + a "Tot -X%" chip, but no single struck reference price and no literal savings amount.
**Rejected:** Showing the best-discount variation's regular price struck through next to the lowest "Vanaf" price.
**Why:** The lowest-priced variation never had that reference price. Anchoring "Vanaf €X" against a reference belonging to a different (more expensive) variation is a misleading discount — the ghost-anchor pattern the ACM/Prijzenwet treats as a fake discount. The "Tot -X%" chip communicates the promotion honestly without implying a €X→€Y reduction on one item. This intentionally narrows the design brief's earlier §3 wording in favour of the ethical guardrail (brief §16).

### Decision: No own structured data in PDP presentational widgets
**Chose:** The Price & Promo Block emits no Product/Offer JSON-LD.
**Why:** WooCommerce core (and the active SEO plugin) already output Product schema with offers. Duplicate Product schema causes Google to ignore the markup. The widget's responsibility is only that the visible price matches what the schema reports — another reason for `wc_get_price_to_display()`.

### Decision: Discount chip reuses the card's Korting colour (green), sale price red
**Chose:** The percentage chip uses the existing Korting-badge styling (Bourgini green `#3EC26D` on white); the sale price uses the card's sale colour (`#B4211C`), the reference uses the card's grey (`#888888`).
**Why:** Cross-widget visual consistency — the discount/Korting colour in this plugin is green, red is reserved for the Nieuw badge. Matches "badge styling matches the product card exactly".

### Decision: Style controls via Elementor `selectors`/group controls
**Chose:** Pure style controls (colour, typography, spacing, chip radius, unavailable opacity) use Elementor's `selectors` / `add_group_control()`, so Elementor generates the CSS; values are not read back in PHP.
**Why:** Idiomatic Elementor styling, less PHP, and keeps the render method focused on content/logic. PHP only reads settings that drive logic (mode, toggles, labels, variable display).

---

## PDP Product USP / Benefits architecture (v2.2.0)

### Decision: ACF stores only USP text; Elementor owns presentation
**Chose:** The dedicated PDP USP content model is a single ACF Pro repeater `pdp_usps` with one text sub-field per row: `usp_text`.
**Rejected:** A richer ACF model with per-USP title, body, icon, colour, layout, or styling fields.
**Why:** The content editor should only fill the product-specific benefit text. Layout choices (list/cards/inline), icon style, columns, spacing, typography, colours, border, radius and shadow belong in Elementor because they are presentation decisions for the PDP template, not per-product content. This keeps the product edit screen simple and prevents design drift between products.

### Decision: Source fallback chain for migration safety
**Chose:** In `source_mode = auto`, the widget reads PDP USPs first, then WooCommerce short description, then the existing Product Card USP fields (`usp_1`, `usp_2`, `usp_3`).
**Why:** The current PDP already uses the short description for this area, and the site already has short Product Card USPs. The fallback chain lets the new widget be placed in the template before every product has dedicated `pdp_usps` rows, while still allowing cleaner PDP-specific content over time.

### Decision: Product USP / Benefits widget ships no JavaScript
**Chose:** Server-rendered HTML plus CSS only. `get_script_depends()` returns an empty array.
**Why:** The block is static, scan-friendly content. There is no interaction that requires JavaScript, so adding script would create cost without benefit. This follows the project-wide progressive-enhancement stance: JS only where interaction genuinely needs it.

---

## PDP Delivery & Availability architecture (v2.3.0 / PDP Phase 4)

### Decision: Keep the block narrow: availability, delivery promise, free-shipping threshold
**Chose:** The MVP contains only three commercial reassurance lines: stock status, one delivery/cut-off text line, and one free-shipping threshold line.
**Rejected:** A broader trust widget containing returns, warranty, payment methods, customer service, reviews, or generic service promises.
**Why:** The block belongs near the buy action and must answer the immediate purchase questions without competing with price and add-to-cart. Returns and warranty can be handled later as a separate trust/accordion pattern if needed.

### Decision: Delivery and cut-off are one Elementor text line
**Chose:** Use one configurable Elementor text control, defaulting to `Voor 23:00 besteld, morgen in huis`.
**Rejected:** Automatic delivery-date calculation, separate cut-off-time logic, weekday/holiday rules, or carrier-specific logic in the MVP.
**Why:** The current business rule is globally the same for products and already exists as a hardcoded text promise. Keeping it as one editor-controlled line avoids false precision and keeps responsibility for the promise with the template/editor, not hidden date logic.

### Decision: Show free-shipping threshold, not exact shipping cost
**Chose:** If product price is at or above the configured threshold, show `Gratis bezorging`; otherwise show `Gratis bezorging vanaf €25,-`.
**Rejected:** Showing exact shipping cost such as `Bezorging €4,95` in the MVP.
**Why:** Exact shipping cost depends on cart context, destination, package size/weight, shipping zone, carrier and possible exceptions. Showing a wrong exact cost is worse than showing a clear threshold. The threshold is already the commercial promise used on product cards and is enough for the PDP MVP.

### Decision: Stock status comes from WooCommerce; permanent unavailability from existing ACF badge
**Chose:** Use WooCommerce stock status for `Op voorraad` / `Tijdelijk uitverkocht`, and existing `badge_niet_leverbaar` for `Niet meer leverbaar`.
**Rejected:** New ACF fields for delivery availability in the MVP.
**Why:** Product availability should match the source that also controls purchasability. The project already has a stronger permanent-unavailable flag; duplicating that in a new field would create drift.

### Decision: Free-shipping comparison is conservative for variable products
**Chose:** Use the current/lowest display price from `WCPCE_Price_Helper::get_product_price_data()` first, then sale/regular fallbacks.
**Why:** For variable products, the shopper first sees a "vanaf" price. If the cheapest purchasable variant is below the free-shipping threshold, the PDP should not claim `Gratis bezorging` for the whole product family. Showing `Gratis bezorging vanaf €25,-` is safer and more honest until a variation-specific delivery/add-to-cart layer exists.

### Decision: Default design is a quiet stacked list, with pills optional
**Chose:** Default layout is a stacked list with subtle icons/status markers. Compact pill layout is optional via Elementor.
**Rejected:** Pills/badges as the default presentation.
**Why:** Pills can read as promotional badges and compete with sale badges, price and add-to-cart. A calm list is easier to scan on mobile and better fits the role: reassurance, not promotion.

### Decision: Delivery & Availability widget ships no JavaScript
**Chose:** Server-rendered HTML plus CSS only. `get_script_depends()` returns an empty array.
**Why:** Availability, delivery promise and threshold text are static commerce content. They must be available without JavaScript, and there is no interaction in the MVP that justifies a script.

### Decision: No low-stock counts or countdown urgency in MVP
**Chose:** Do not show `Nog X op voorraad`, `bijna uitverkocht`, countdown timers, or similar scarcity messaging.
**Why:** Scarcity claims must be reliable and current. WooCommerce stock quantities are not yet confirmed as operationally trustworthy for this purpose, and countdown/cut-off urgency can become misleading if it does not account for weekends, holidays and carrier constraints.

---

## PDP Product Accordion architecture (v2.4.0)

### Decision: Lipscore reviews via WC tab callback, not direct output
**Chose:** Detect the `lipscorereviews` key in `woocommerce_product_tabs`, call its `callback` via `call_user_func()` inside an output buffer, set `global $product` just before the callback and restore it via `finally`.
**Rejected:** Rendering Lipscore's `<div>` placeholder directly or hard-coding Lipscore shortcodes.
**Why:** The WC tab callback is the only stable, Lipscore-version-agnostic way to get the full panel HTML. It is exactly what WooCommerce does natively. Hard-coding a placeholder or shortcode would break if Lipscore changes its API; calling the registered callback always produces the same output regardless of Lipscore version. Since v2.5.8 the global is set once, only around the callback itself (not around the preceding `apply_filters` call), which is where it is actually needed.

### Decision: HTML `hidden` attribute for closed panels, not CSS-only
**Chose:** Set `hidden` on closed panels; JS removes it on open and re-adds it on close.
**Rejected:** CSS `display:none` or `visibility:hidden` alone.
**Why:** `display:none` via CSS is not guaranteed to remove content from the tab order in all assistive technology configurations. The `hidden` attribute is semantically meaningful ("this content does not exist right now") and universally removes content from both visual rendering and AT traversal. This is the W3C APG recommended approach for accordion panels.

### Decision: Multiple sections open simultaneously
**Chose:** Each trigger toggles its own panel independently; closing one section does not close others.
**Rejected:** The "one open at a time" behaviour used by the previous JS snippet.
**Why:** NNG (Nielsen Norman Group) recommends allowing multiple sections open simultaneously so users can compare or combine information from different sections. The previous single-open behaviour was a snippet convention, not a deliberate UX decision. On a product detail page, a shopper may want to read the description while looking at the specs, or compare the FAQ answer against the attributes table.

### Decision: FAQ as inner accordion, not flat `<dl>`
**Chose:** Each `vraag`/`antwoord` pair rendered as its own expandable toggle using the same `button`/`aria-expanded`/`aria-controls`/`hidden` pattern as the outer accordion.
**Rejected:** A flat always-visible `<dl>` list.
**Why:** The existing site design and user behaviour already uses accordion-in-accordion for FAQ (visible in screenshots). It keeps long FAQ sections scannable without requiring the user to read every answer. The accessible implementation is identical in pattern to the outer accordion, so no new patterns are introduced.

### Decision: `product_manual` ACF file field replaces ACPT plugin
**Chose:** Register a new `file` field `product_manual` in `group_wcpce_pdp_accordion` (return format `array`). Show only the configurable download label as link text — never the raw filename.
**Rejected:** Keeping the Additional Custom Product Tabs for WooCommerce (ACPT) plugin for manual delivery.
**Why:** ACPT introduced a third-party plugin dependency for a single use case (one PDF per product). An ACF `file` field achieves the same result with less overhead, stays within the existing ACF data model, and removes a plugin the site no longer needs once the accordion widget is live. The filename is hidden from shoppers because raw filenames (e.g. `Bourgini_16.4041-16.4045_Chefs_Dinner_Party_Glazed_Grey.pdf`) are meaningless and unwelcoming; the Elementor download label control gives editors a clean, translatable label instead.

### Decision: Heading level is a configurable Elementor control
**Chose:** `SELECT` control offering h2, h3, h4; default h3.
**Rejected:** Hardcoding h3 or h2.
**Why:** WCAG and the W3C APG require accordion headers to be at the correct heading level for the page hierarchy. On different pages or templates the correct level varies. A Elementor control gives the template builder explicit control without requiring a code change.

### Decision: FAQPage schema remains an external site snippet
**Chose:** Keep FAQPage JSON-LD outside Woo Card Chef for now. The active snippet outputs only `FAQPage` from the existing `product_faq` ACF rows; Product, Offer, Breadcrumb, review and rating schema remain owned by Rank Math/WooCommerce or a dedicated schema integration.
**Rejected:** Emitting Product/Offer schema, reviews, ratings or FAQPage JSON-LD from the Product Accordion widget.
**Why:** Woo Card Chef is the presentation/widget layer. The live site already has Rank Math/WooCommerce Product schema, so adding another Product node from the plugin would create duplicate ownership. FAQPage schema is a site-level SEO concern that reads the same visible FAQ data as the widget, but it should stay in the schema layer until the project deliberately moves all schema ownership into a dedicated integration.

### Decision: Expired priceValidUntil is cleaned in the schema layer
**Chose:** Keep WooCommerce sale metadata untouched and remove only expired `priceValidUntil` values from Rank Math JSON-LD output via an external safety snippet.
**Rejected:** Manually clearing expired sale dates on products, changing WooCommerce product data on page load, or moving Rank Math Product schema into Woo Card Chef.
**Why:** WooCommerce can retain expired sale schedule data after a sale ends, and Rank Math may expose that stale date in Product schema. Google may suppress Product snippets/listings when `priceValidUntil` is in the past. The safest fix is output-level cleanup: future dates remain, active sale schema remains, historical admin data remains available, and Woo Card Chef stays out of Product schema ownership.

## PDP roadmap — scope decisions (v2.3.x)

### Decision: PDP Phase 5 (Add to Cart) descoped permanently
**Chose:** Do not build a custom Add to Cart widget for Woo Card Chef. Style and status-awareness work happens via CSS overrides on the existing EAEL WooCommerce Add to Cart widget.
**Rejected:** Building a `WCPCE_Product_AddToCart_Widget` wrapper around WooCommerce's cart form.
**Why:** WooCommerce's add-to-cart flow — variation JS, cart nonces, AJAX handlers, backorder logic, and hooks from payment and shipping plugins — is one of the most tested and integration-dense flows in the WooCommerce ecosystem. A custom wrapper adds no new functionality that WooCommerce or EAEL do not already provide. The risk of regressions (variable products, bundles, conditional payment methods, cart validation hooks) significantly outweighs the benefit of having a dedicated Elementor widget. Visual consistency and the `badge_niet_leverbaar` disabled state are both achievable with a focused CSS snippet. A separate briefing document was written for this CSS work.
**Consequence at the time:** Phase 5 moved to the Descoped table in ROADMAP. Development continued from Phase 4 to Phase 6; the Accordion was initially deferred and later shipped in v2.4.0.

### Historical decision: PDP Phase 6 (Accordion) deferred — superseded by v2.4.0
**Originally chose:** Defer Phase 6 until there was capacity and a clear content brief.
**Why at the time:** The Accordion widget covers product description, specs, FAQ, and manuals — content that required editorial decisions (what sections, what order, what content model) before the widget scope could be defined.
**Status:** Superseded. The content brief was completed and the Product Accordion shipped in v2.4.0, followed by progressive-enhancement hardening in v2.4.1 and manual fallback matching in v2.6.8. The historical decision remains here to explain the roadmap sequence; it is no longer an active constraint.
