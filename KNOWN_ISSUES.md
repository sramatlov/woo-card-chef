# Known Issues — Woo Card Chef

## Solved bugs — Product Card widget

See the v1.0.84 KNOWN_ISSUES for full pre-v1.0.84 history. Summary of key resolutions:

- v1.0.54: AJAX pagination removed entirely; server-rendered pagination only
- v1.0.69–v1.0.71: Pagination URL saga resolved; path-only base URL for manual mode, `get_pagenum_link()` for auto mode
- v1.0.72: `loop_shop_per_page` override removed (was corrupting WBW facet counts)
- v1.0.75: `validate_manual_settings()` added
- v1.0.76–v1.0.78: Phase 5 accessibility and performance items (H6, H8, H9, H10, H11)
- v1.0.79: H13 pagination current-page aria-label
- v1.0.79.1–v1.0.79.2: Lipscore star colour — hardcoded filter and opacity removed; opt-in CSS-filter control
- v1.0.79.3–v1.0.79.13: WBW sticky header saga — resolved outside the plugin via CSS `position: sticky` on header template
- v1.0.79.4: Limit control max aligned, debug comment removed, `esc_url()` on pagination hrefs, type hints, nine `esc_html__()` → `__()`, product meta cache priming

---

## Solved bugs — PDP Gallery widget (v1.0.85–v2.0.0)

### Gallery aspect ratio forced video preview to 16:9 (fixed v1.0.85)
**Symptom:** Video slide preview thumbnail showed as a 16:9 strip inside the 1:1 gallery frame with empty space below.
**Resolution (v1.0.85):** 1:1 aspect ratio applied to the outer `.wcpce-gallery__slides` viewport; inner wrappers use `height: 100%`.

### Video thumbnail source inconsistent (fixed v1.0.86)
**Symptom:** Main video preview used `maxresdefault.jpg` which is sometimes unavailable (YouTube returns a 120×90 placeholder). Thumbnail strip already used `mqdefault.jpg`, creating inconsistency.
**Resolution (v1.0.86):** Both main preview and thumbnail strip use `mqdefault.jpg`.

### Elementor global `img { height: auto }` overriding video preview (fixed v1.0.87)
**Symptom:** CSS `object-fit: cover` on `<img>` was overridden by Elementor's global stylesheet, causing video thumbnails to render at intrinsic 16:9 ratio.
**Resolution (v1.0.87):** Video slide preview switched from `<img>` to `<span>` with `background-image` + `background-size: cover`. CSS background layers are not affected by Elementor's global image rules.

### Video play button did nothing when image lightbox was disabled (fixed v1.0.88)
**Symptom:** When the "Enable lightbox" Elementor control was off, play buttons on video slides were visible but clicking them had no effect.
**Resolution (v1.0.88):** Image lightbox and video lightbox separated. The lightbox HTML is always rendered when video slides are present (`$render_lightbox = $enable_lb || $has_video_slides`). `data-lightbox` controls images; `data-video-lightbox` controls video.

### Double initialisation possible in Elementor context (fixed v1.0.88)
**Symptom:** Both `DOMContentLoaded` and Elementor's `frontend/element_ready` hook could fire on the same page, initialising the same gallery twice and stacking event listeners.
**Resolution (v1.0.88):** Central `initGallery(galleryEl)` helper sets and checks the init flag on the `.wcpce-gallery` element in both code paths.

### Document-level zoom listeners stacking on repeated lightbox navigation (fixed v1.0.88)
**Symptom:** Each time the lightbox showed a new image, `bindZoom()` added fresh `mousemove` and `mouseup` listeners to `document`. After multiple navigations, many listener sets fired per mouse move.
**Resolution (v1.0.88):** `ensureZoomDocumentEvents()` guards with `zoomDocumentEventsBound` boolean; document listeners are bound exactly once per gallery instance. Active image reference tracked via `this.zoomImg`.

### "Tot " prefix never appeared on variable product discount badge (fixed v1.0.89)
**Symptom:** Variable products with mixed discounts always showed e.g. "−20%" instead of "Tot −20%". The gallery widget was setting `$price_data['is_variable']` to `$prices['mixed_discounts']` (overwriting the real product type flag) and not passing `$mixed_discounts` as the third argument to `compute_badge_data()`.
**Resolution (v1.0.89):** `is_variable` now stays the real product type from `$product->is_type('variable')`. A separate `$mixed_discounts` variable is passed as the third argument to `compute_badge_data()` and to `render_badgebar()`.

### Hidden video-slide play buttons were keyboard-focusable (fixed v1.0.89)
**Symptom:** `aria-hidden="true"` on inactive slides hides them visually but play buttons remained in the tab order, violating WCAG 2.5.3.
**Resolution (v1.0.89):** PHP sets `tabindex="-1"` on play buttons of inactive slides at render time. `goTo()` in JS updates all play button tabindex values on every slide change.
**Hardening (v2.6.7):** Inactive slides now also render with `inert` alongside `aria-hidden`, and gallery JS toggles `inert` with the active slide. Live production check confirmed inactive slides without `inert`: 0.

### Video custom thumbnails not cache-primed (fixed v1.0.89)
**Symptom:** `prime_attachment_caches()` only collected image slide IDs. Custom `thumb_id` values on video slides were loaded individually via separate DB queries during the render loop.
**Resolution (v1.0.89):** Cache prime batch now collects both image `attachment_id` and video `thumb_id` values in a single loop.

### Gallery render settings not validated before use (fixed v1.0.89)
**Symptom:** `object_fit` was used directly in an inline style; `thumbnail_count` was not clamped. Corrupted postmeta could produce invalid CSS values or out-of-range counts.
**Resolution (v1.0.89):** `validate_gallery_settings()` added as the first call in `render()`. Whitelists `object_fit` (contain/cover), `badgebar_position` (above/below), `badge_format` (smart/percent/amount); clamps `thumbnail_count` (2–10) and `badge_threshold` (0–100).

---

## Solved bugs — Product USP / Benefits widget (v2.2.1)

### Short-description fallback collapsed HTML lists into one USP item (fixed v2.2.1)
**Symptom:** When the WooCommerce short description fallback contained an HTML list, the Product USP / Benefits widget rendered the full list as one combined USP item instead of one item per `<li>`.
**Cause:** The parser converted `</li>` to line breaks, but then called `wp_strip_all_tags( ..., true )`, which removed those line breaks again.
**Resolution (v2.2.1):** The parser now converts list/paragraph/block boundaries to line breaks and preserves those line breaks when stripping tags, so list items remain separate USP rows.

---

## Solved bugs — Product USP / Benefits widget (v2.3.1)

### Auto-mode fetched all three content sources on every render (fixed v2.3.1)
**Symptom:** In `source_mode = auto`, the widget always executed all three source methods (`get_pdp_usps`, `get_short_description_usps`, `get_card_usps`) per render, even when the first source already returned content. Wasted one `get_field()` DB call and one short-description regex parse on every page load for products with PDP USP content.
**Cause:** The fallback chain used `foreach( array( func1(), func2(), func3() ) as $items )`. PHP evaluates all array elements before the loop begins, so the early-return inside the loop was unreachable until all three fetches had already run.
**Resolution (v2.3.1):** Replaced with a sequential `$items = …; if ( empty($items) ) { … }` chain. Each source is now only read when the previous one returned nothing.

### `sanitise_usp_text()` used British spelling (fixed v2.3.1)
**Symptom:** Method name inconsistent with WordPress core and the rest of the codebase, which use American English (`sanitize_`).
**Resolution (v2.3.1):** Renamed to `sanitize_usp_text()` everywhere (definition, call sites, docblock).

---

## Solved bugs - Product Accordion and PDP helpers (v2.4.1)

### Accordion content was hidden in initial HTML (fixed v2.4.1)
**Symptom:** Closed accordion panels were rendered server-side with `hidden`. This was accessible after JS, but it made no-JS and crawler/GEO contexts rely on hidden content.
**Resolution (v2.4.1):** Outer panels and FAQ answers render open in the server HTML. `product-accordion.js` applies the configured default-open/closed state after initialization using `data-default-open`, `aria-expanded`, and `hidden`.

### Specs section missed dimensions/weight-only products (fixed v2.4.1)
**Symptom:** The pre-render check only looked at custom visible attributes. Products that only had WooCommerce dimensions or weight could skip the Specifications section even though WooCommerce's native attributes table would have content.
**Resolution (v2.4.1):** `get_specs_content()` now captures `wc_display_product_attributes( $product )` directly and checks the resulting table output.

### Accordion CSS hid the first h2 in every panel (fixed v2.4.1)
**Symptom:** `.wcpce-accordion__content > h2:first-child` suppressed headings outside the Specs panel, including editor-authored Description, FAQ, Review, or Manual headings.
**Resolution (v2.4.1):** The rule is scoped to `.wcpce-accordion__item--specs`.

### Direct `mb_substr()` calls could fatal without mbstring (fixed v2.4.1)
**Symptom:** Product USP / Benefits and Product Price & Promo called `mb_substr()` directly during settings validation. Hosts without the PHP mbstring extension could fatal.
**Resolution (v2.4.1):** Both widgets now use a clamp helper with `function_exists( 'mb_substr' )` fallback to `substr()`.

### Repeated PDP price helper work (optimized v2.4.1)
**Symptom:** Gallery, Price, and Delivery widgets can ask `WCPCE_Price_Helper::get_product_price_data()` for the same product during one PDP render, repeating variation price calculations.
**Resolution (v2.4.1):** The helper now caches results per request by product ID/object hash.

---

## Intentional quirks — Product Card widget

### `position: absolute` on `.wc-card__badge` with no `position: relative` on its container
Correct. Badge is positioned relative to `.wc-card__media`. Do not add `position: relative` to `.wc-card__badge`.

### `content_template()` is intentionally empty
The Product Card widget declares `content_template(): void` with an empty body. This explicitly keeps the widget server-rendered in Elementor; do not add a second client-side template that can drift from `render()`.

### `get_script_depends()` returns a static array
Never conditional on settings. This applies across the widget suite; presentational widgets return an empty array. See DECISIONS_LOG.

### Manual mode pagination strips query args
Deliberate trade-off from v1.0.69. Documented in DECISIONS_LOG.

### Nieuw badge does NOT inherit badge style controls
Excluded from all four Discount Badge Style Elementor controls since v1.0.45.

### `.wcpce-sr-live` CSS class defined but not referenced in PHP
Reserved for a future aria-live status region (roadmap item).

---

## Intentional quirks — PDP Gallery widget

### No `isolation: isolate` on `.wcpce-gallery` wrapper
Intentional — the lightbox is `position: fixed` and must escape to the viewport. Any ancestor stacking context would trap it. The product card widget uses `isolation: isolate` for the opposite reason (badges must not escape above the sticky header).

### Gallery nav buttons use `!important` on hover/active/focus colour states
Intentional. The Bourgini site-wide `button:hover` style uses a brand colour that bleeds into our buttons. `!important` on all interactive states prevents this.

### `mobile_thumbnail_count` control exists but does not affect video positioning
The control is registered and its value is read, but `position_video_slides_before_thumbnail_overflow()` uses only `thumbnail_count` (the desktop count). Mobile positioning logic is deferred to v2.1 because it requires careful coordination with the video-positioning rule.

### Video slide thumbnail is a `<span>` with `background-image`, not an `<img>`
Intentional. See DECISIONS_LOG. Avoids Elementor's global `img { height: auto }` override.

### `display_mode` and `video_position` ACF fields are reserved but not registered
Intentional. Reserved for a future gallery iteration (in-slide playback and video interleaving). They are not registered because fields with no frontend effect would invite editors to enter data that is ignored.

---

---

## Intentional quirks — Product Accordion widget (v2.4.0)

### `product_faq` is not registered by the plugin
Intentional. The `product_faq` repeater already exists on the site, registered outside the plugin. Re-registering it would either conflict or create a duplicate group. The plugin reads via `get_field()` only.

### Lipscore tab count in accordion trigger is async
Intentional. Lipscore updates the review count in the WC tab label via JavaScript after page load. The accordion JS syncs this count into the accordion trigger text via a MutationObserver plus a polling fallback. There is a brief moment on initial load where the count has not yet appeared. This matches the existing behaviour in the JS snippet it replaces.

### Specifications use `wc_display_product_attributes()`, not `wc_display_product_data()`
The attributes table is rendered by `wc_display_product_attributes( $product )` — the WooCommerce function that outputs the attributes/dimensions table used by the native "Additional information" tab. There is no `wc_display_product_data()` function in WooCommerce; calling it causes a fatal `Call to undefined function`. The call is guarded with `function_exists()` so the section is silently skipped if the function is ever unavailable.

### WooCommerce `<h2>` inside attributes panel is suppressed via CSS
Intentional. WooCommerce can output a `<h2>Additional information</h2>` inside additional-information markup. Inside the accordion this heading is redundant because the accordion trigger already labels the section. Since v2.4.1 the CSS is scoped to `.wcpce-accordion__item--specs`, so it cannot hide editor-authored headings in other accordion panels.

### `global $product` is temporarily overridden during Lipscore tab capture
Intentional. `get_reviews_content()` sets `global $product` to the current product just before calling the Lipscore tab callback, and restores the previous value in `finally`. This is the same context pattern WooCommerce uses internally when rendering its native tabs, with extra cleanup if a third-party callback throws. Since v2.5.8 the global is set once only around the callback itself; the earlier redundant set/restore around `apply_filters('woocommerce_product_tabs')` has been removed.

---

## Solved bugs — code review hardening (v2.5.8)

### Inconsistent `get_current_product()` ordering across PDP widgets (fixed v2.5.8)
**Symptom:** Five PDP widgets (Gallery, Price, USP, Delivery, Accordion) checked `global $product` before `get_queried_object()`. If a product loop ran earlier in the template and left `global $product` pointing at a card item, those widgets could resolve the wrong product.
**Cause:** The correct ordering (`get_queried_object()` first) was established in v2.5.1 for the Upsells widget but was never backported to the other five PDP widgets.
**Resolution (v2.5.8):** All six PDP widgets now use `get_queried_object()` first, `global $product` as fallback, matching the Upsells widget pattern.

### Redundant double set/restore of `global $product` in `get_reviews_content()` (fixed v2.5.8)
**Symptom:** `get_reviews_content()` in the Accordion widget set `global $product` before calling `apply_filters('woocommerce_product_tabs')`, then immediately restored it, then set it again before the callback. The first set/restore was unnecessary and made the method harder to follow.
**Cause:** Defensive code added before the tabs filter to ensure context, but that filter does not render content and does not need the global to be set.
**Resolution (v2.5.8):** Removed the first set/restore. `global $product` is now set once, just before `call_user_func()`, and restored via `finally`.

### `esc_attr()` used for HTML tag names in Accordion widget (fixed v2.5.8)
**Symptom:** `render_accordion_item()` used `esc_attr($heading_tag)` when writing heading opening and closing tags to HTML output. The WordPress-canonical function for HTML tag names is `tag_escape()`.
**Cause:** Inconsistency introduced when the Accordion widget was written; USP and Upsells widgets already used `tag_escape()` correctly.
**Resolution (v2.5.8):** Replaced both `esc_attr($heading_tag)` calls with `tag_escape($heading_tag)`. Both functions are safe here given the validate whitelist; the change aligns with WordPress conventions and cross-widget consistency.

## Solved bugs — code review hardening (v2.6.1)

### Alt text double-encoded via `wp_get_attachment_image()` (fixed v2.6.1)
**Symptom:** The card template's primary image and the gallery's custom video thumbnail passed `esc_attr()`-escaped alt strings to `wp_get_attachment_image()`. That function escapes its own attributes, so special characters in product or video titles (e.g. `&`) rendered double-encoded in the alt attribute (`&amp;amp;`).
**Cause:** Convention violation — CONVENTIONS documents that `wp_get_attachment_image()` receives raw strings, but two call sites predated or missed that rule.
**Resolution (v2.6.1):** Both call sites now pass the raw string. The manual placeholder `<img>` in card.php correctly keeps `esc_attr()` (hand-built markup).

### Duplicate reviews modifier class in Accordion render (fixed v2.6.1)
**Symptom:** The reviews section rendered with `wcpce-accordion__item--reviews` twice in its class attribute — once from the generic per-section modifier, once from a redundant append that claimed to add a data attribute but did not.
**Resolution (v2.6.1):** Redundant append removed. JS targets the reviews section via the `data-section` attribute set in `render_accordion_item()`.

### Local `$product` loop variable in Related widget (hardened v2.6.1)
**Symptom:** Not a bug — `get_visible_products_from_ids()` used a local variable named `$product`, which could never pollute the global, but invited future confusion in a codebase where `global $product` pollution is the documented v2.5.1/v2.5.8 failure mode.
**Resolution (v2.6.1):** Renamed to `$candidate`, matching the Upsells widget's use of a distinct name (`$upsell`).

### `build_reviews_html()` docblock mismatch (fixed v2.6.1)
**Symptom:** The docblock claimed a "permissive allowlist via wp_kses_post with an extra script pass"; no script pass exists in the code.
**Resolution (v2.6.1):** Docblock now describes the actual behaviour: `wp_kses_post()` strips inline scripts, `data-*` attributes survive kses, and Lipscore renders client-side via its site-wide JS on the placeholder markup.

---

## Solved bugs - live release hardening (v2.6.2-v2.6.7)

### Product Upsells lacked a popularity sort option (added v2.6.2)
**Resolution (v2.6.2):** Added `upsell_orderby` so linked upsells can keep WooCommerce's stored order or sort visible linked upsells by total sales.

### Security and escaping review follow-up (fixed v2.6.3)
**Resolution (v2.6.3):** Gallery slide aria labels and fallback YouTube thumbnail URLs are escaped explicitly, heading tags in Upsells and Related use `tag_escape()`, and local variables that could be confused with globals or reserved words were renamed.

### YouTube fallback thumbnails broke for mixed-case IDs (fixed v2.6.4)
**Symptom:** `sanitize_key()` lowercased YouTube IDs before building `mqdefault.jpg`, but YouTube IDs are case-sensitive.
**Resolution (v2.6.4):** Added a case-preserving YouTube ID allowlist sanitizer.

### Thumbnail hover colour was not controllable (fixed v2.6.5)
**Resolution (v2.6.5):** Active thumbnail styling now targets the real `.wcpce-gallery__thumb-btn` border, a thumbnail hover border colour control was added, and theme button hover backgrounds are locked out of the thumbnail strip.

### Elementor frontend hook API not always available on normal frontend (fixed v2.6.6)
**Symptom:** Production/staging pages could expose `window.elementorFrontend` before `elementorFrontend.hooks.addAction` existed, causing gallery and accordion console errors.
**Resolution (v2.6.6):** Gallery and Accordion only register Elementor `frontend/element_ready` hooks when `elementorFrontend.hooks.addAction` is available; DOMContentLoaded initialisation remains the normal frontend path.

### Empty gallery image alt text when media library alt was blank (fixed v2.6.7)
**Symptom:** PDP gallery images rendered empty `alt` attributes when the WooCommerce image attachment had no media-library alt text.
**Resolution (v2.6.7):** Gallery image slides and image thumbnails now use attachment alt text first and the product name as fallback. Live production check confirmed empty visible gallery alts: 0.

---

## Intentional quirks - Product Upsells & Cross-sells / Related widgets

### Title/price typography `!important` fallback overrides Elementor typography controls
Both `product-upsells.css` (since v2.5.6) and `product-related.css` (v2.6.0) ship a scoped fallback with `font-size`/`font-weight` marked `!important` on the card title (12px/400) and current/sale price (15px/500). Consequence: the Elementor typography controls for those two properties cannot override the stylesheet fallback, because Elementor-generated CSS does not use `!important`. Accepted trade-off from the v2.5.6 decision (enforce typography on existing templates without re-saving them); the Related widget inherits it deliberately for consistency. Other typography properties (line-height, family, spacing) remain controllable.

### Mixed CRLF/LF line endings in several PHP files
Cosmetic only. Most widget/helper files contain a small number of CRLF lines within otherwise LF files. Deliberately not normalised in v2.6.1 to keep the patch diff minimal; a separate whitespace-only clean-up can be done later if desired.

---

## Open / unresolved

### PDP Gallery ignores the custom label's card-corner position
Intentional. `wcpce_label_position` controls only Product Card Grid, Upsells and Related. The Gallery has one horizontal badgebar above or below the media and therefore renders custom labels after its system badges in priority order. This prevents one reusable label from needing a second, conflicting PDP position.

### Product-label limit applies across both card corners
Intentional. The Elementor limit (default 3, range 1-10) selects the highest-priority labels across the product first; the selected labels are then grouped into left/right stacks. This prevents assigning labels to both corners from silently doubling the configured maximum.

### Inactive product labels remain assigned
Intentional. Turning a reusable label inactive hides it everywhere without destroying product relationships. Reactivating it restores the same assignments.

### Product-label planning applies to every assignment
Intentional. Visible-from/visible-until values belong to the reusable label definition, so the same window is used on every linked product and in Card Grid, Upsells, Related and Product Gallery. Create a separate label when the same text needs a different campaign period for a subset of products.

### PDP label explanations apply to every assignment
Intentional. The optional PDP rich text belongs to the reusable label and is therefore identical on every linked product. Use separate labels when the same short badge text needs different PDP terms or gifts. Product-specific overrides are deliberately not supported because they would weaken central reuse and scheduling.

### Full-page caching can delay a scheduled label boundary
Visibility is evaluated whenever WordPress generates the widget HTML. A previously cached product or archive page can therefore retain its earlier label state until that page cache expires or is purged. For minute-critical campaigns, align the host/cache-plugin lifetime with the schedule or purge the affected pages at the start and end boundaries.

### `posts_per_page` in manual mode may yield fewer cards than expected
If some products fail `is_visible()` after the query, the grid shows fewer cards than the configured limit. Consistent with WooCommerce's own behaviour. Accepted.

### Lipscore stars colour (real recolouring not done)
True hue change requires Lipscore dashboard configuration or fragile shadow DOM manipulation. Optional CSS-filter control (saturate/brightness) available since v1.0.79.1 for light tint adjustment.

### Brand-specific hardcoded colours (action button, overlay link focus ring, pagination)
`.wc-card__button`, `.wc-card__overlay-link` focus ring, and pagination defaults use hardcoded Bourgini green `#3EC26D`. Will require CSS overrides or Phase 7 Elementor controls when deploying to PrincessTraveller or BourginiFitness.

### `.sr-only` CSS class may clash with theme styles
`.sr-only` is generic. Future rename to `.wcpce-sr-only` considered; deferred because Hello theme does not style it and risk is minimal on current deployment.

### YouTube privacy — video thumbnails load from `i.ytimg.com` without consent
`mqdefault.jpg` thumbnails are requested directly from YouTube's CDN on page load. This establishes a connection to a third-party domain before user interaction. In strict EU/AVG setups this may require a consent gate or self-hosted proxy. Deferred from v2.0.0 scope. Accepted for now given Bourgini's existing YouTube embed usage on the site.

### Gallery zoom on desktop — edge cases in pan-clamping
The `clampPan()` method uses `getBoundingClientRect()` which returns the rendered size. On very fast zoom gestures the clamp may be applied against a slightly stale size. No visible issue reported; noted for awareness.

---

## Intentional quirks — Price & Promo Block widget (v2.1.0)

### Reference price is the WC regular price, not the Omnibus 30-day lowest
Intentional and accepted. The struck "van" price defaults to `get_regular_price()` (via the `wcpce_price_reference_value` filter). For genuine sales this is not necessarily the lowest price of the last 30 days that the NL Omnibus/Prijzenwet requires. The filter is the integration point to inject a compliant value later. No 30-day source is wired in v2.1.0. See DECISIONS_LOG.

### Variable products on sale show no single struck reference or literal savings
Intentional. Showing the best-discount variation's regular price struck through next to a "Vanaf €X" lowest price would anchor against a reference the lowest variation never had — a ghost anchor (a fake discount under the Prijzenwet). The "Tot -X%" chip communicates the promotion honestly instead. This narrows the design brief's earlier §3 wording in favour of the ethical guardrail. See DECISIONS_LOG.

### Displayed amounts use `wc_get_price_to_display()`; the card widget does not
Intentional divergence. The Price & Promo Block converts every amount for the shop's tax display setting; the older card widget uses `wc_price()` on raw helper values. On Bourgini (prices entered and shown incl. VAT) the two are identical, so there is no visible difference. Aligning the card is a separate optional task. See DECISIONS_LOG.

### Discount-percentage threshold not implemented
The card and gallery have a `badge_threshold` to suppress weak discounts. The Price & Promo Block has no threshold in v2.1.0 — the chip shows whenever the product is on sale and the percentage toggle is on (and the rounded percentage is > 0). A threshold can be added later if low-percentage noise becomes an issue.

### `.wcpce-price__sr-only` is a scoped copy of the sr-only pattern
Intentional. The widget defines its own prefixed screen-reader-only class rather than depending on the generic `.sr-only` (which KNOWN_ISSUES notes may clash with theme styles). Self-contained with the widget's own stylesheet.

---

## Intentional quirks — Product USP / Benefits widget (v2.2.0)

### Dedicated `pdp_usps` content requires ACF Pro
Intentional. The dedicated PDP content model is a repeater (`pdp_usps > usp_text`), so it is only registered when ACF Pro's repeater field type is available. The widget remains usable without ACF Pro by falling back to the WooCommerce short description and then Product Card USPs in auto mode.

### No per-row icon or styling fields in ACF
Intentional. ACF stores only content. Icons, layout, columns, typography, colours, spacing, border, radius and shadow are controlled in Elementor at widget/template level.

---

## Intentional quirks - Product Delivery & Availability widget (v2.3.0)

### No exact shipping cost in the PDP widget
Intentional. The widget shows the free-shipping threshold (`Gratis bezorging vanaf €25,-`) rather than an exact shipping price. Exact shipping cost depends on cart contents, destination, shipping zone, package data and carrier rules, so showing a wrong amount would be worse than showing the threshold.

### No countdown or automatic delivery date calculation
Intentional. Delivery/cut-off is one Elementor text line (`Voor 23:00 besteld, morgen in huis` by default). Weekends, holidays and carrier exceptions are not modeled in v2.3.0, so the plugin avoids false precision.

### No product-specific delivery ACF fields
Intentional. The widget uses WooCommerce stock status, existing `badge_niet_leverbaar`, and global Elementor text/threshold controls. Product-specific delivery overrides can be considered later if the business process needs them.

### Variable-product free-shipping check is conservative
Intentional. For variable products, the widget compares the threshold against the current/lowest display price first. If the product starts below the threshold, the widget shows the threshold line instead of claiming free shipping for the whole product family.
