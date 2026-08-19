# Conventions — Woo Card Chef

## Language policy

| Context | Language |
|---|---|
| Code, comments, docblocks | English |
| User-facing strings in Elementor controls (labels, descriptions) | English |
| User-facing strings shown to shop editors (ACF field labels, instructions) | Dutch |
| User-facing strings shown to shoppers (badge labels, empty state text, out-of-stock label) | Dutch (with `__()` for translatability) |
| Changelog entries | English |
| Internal variable names | English |

**Rationale:** Elementor panel is used by Robby (developer-level). ACF product edit screen is used by the content editor (Dutch speaker). Shopper-facing text defaults to Dutch since the shops are Dutch.

---

## PHP conventions

### No opening `<?php` tag in snippets
When writing code intended for WordPress snippet plugins or quick-insert contexts, never include `<?php` at the top. For actual plugin files, the opening tag is present as normal.

### Return types on all public, protected, and private methods
Every method override and every private helper must declare a return type.

```php
// Correct
public function get_name(): string { ... }
private function run_manual_query( array $settings ): array { ... }
```

### Typed parameters on private methods
Private methods use typed parameters where possible.

### Null coalescing for settings access
Settings are always accessed with a null coalesce fallback:
```php
$settings['show_badge'] ?? 'yes'
$settings['thumbnail_count'] ?? 5
```

### Sanitisation and escaping
- All output escaped: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`, `wp_kses()`
- `wp_kses()` with explicit `$allowed_svg` array for SVG icon output
- All input sanitised: `absint()`, `sanitize_key()`, `sanitize_text_field()`
- Do not call `mb_substr()` directly in widget validation or sanitization. Use a local clamp helper that checks `function_exists( 'mb_substr' )` and falls back to `substr()`.
- **`__()` (not `esc_html__()`) for shopper-facing defaults that will be passed through `esc_html()` or `esc_attr()` at output** — pre-escaping causes double-encoding.
- `esc_html__()` is correct in Elementor control labels (they go directly to the panel, not through a second escape)
- **`wp_get_attachment_image()` escapes its own attributes** — pass raw strings (not pre-escaped via `esc_attr()`) for alt, class etc. when using this function.

### Static variables for per-request caches
```php
static $rendered = false;    // SVG sprite: output once per page
static $template_exists = null;  // file_exists: check once per request
```

### Early returns over nested conditions
Prefer returning early rather than deeply nesting.

### Transient cache — store IDs not objects
Cache product IDs and pagination metadata only, never `WC_Product` objects.

### Editor detection — use `$this->is_elementor_editor_or_preview()`
Never write inline Elementor editor checks.

### PDP widget product context — `get_queried_object()` before `global $product`
All PDP widgets resolve the current product via `get_queried_object()` first. `global $product` is the fallback only when the queried object is not a product post type. This ordering prevents a product loop rendered earlier in the template (e.g. the Upsells card grid) from leaving `global $product` pointing at a card item instead of the PDP product. Established as the canonical pattern in v2.5.1 (Upsells widget) and standardised across all PDP widgets in v2.5.8.

### No global WooCommerce archive overrides
Do not register `pre_get_posts`, `woocommerce_product_query`, or `loop_shop_per_page` hooks.

### Helper classes are stateless and static (Phase 6+)
`includes/Helpers/` classes: public static methods only, no constructor, no state. Prefix-style naming (`WCPCE_Badge_Helper`), not namespaces. Required unconditionally at bootstrap.

All five Phase 6 helpers present as of v1.0.84 / v2.0.0: `WCPCE_Badge_Helper`, `WCPCE_Stock_Helper`, `WCPCE_Price_Helper`, `WCPCE_Image_Helper`, `WCPCE_ACF_Helper`.

### Widget files live in `includes/Widgets/` (v2.0.0+)
All Elementor widget classes live in `includes/Widgets/`. Required via `class-plugin.php` → `register_widgets()`.

### Validation at the query/render boundary
Defensive validation (`validate_manual_settings()` on the card widget, `validate_gallery_settings()` on the gallery widget, `validate_price_settings()` on the price widget, `validate_usp_settings()` on the USP widget, `validate_delivery_settings()` on the Delivery & Availability widget, `validate_accordion_settings()` on the Accordion widget) is called at the top of the relevant method, not as a general filter. Threat model: guard against data corruption and import/export anomalies, not unauthenticated attacker input.

---

## CSS conventions

### BEM naming — Product Card
```
.wc-card           Block
.wc-card__image    Element
.wc-card--out-of-stock  Modifier
.wcpce-pagination  Pagination elements (wcpce- prefix)
```

### BEM naming — PDP Gallery
```
.wcpce-gallery            Block
.wcpce-gallery__slides    Element
.wcpce-gallery__badge--discount  Element with modifier
```

### BEM naming — PDP Product USP / Benefits
```
.wcpce-usps           Block
.wcpce-usps__list     Element
.wcpce-usps__item     Element
.wcpce-usps--cards    Modifier
```

### BEM naming - PDP Delivery & Availability
```
.wcpce-delivery              Block
.wcpce-delivery__list        Element
.wcpce-delivery__item        Element
.wcpce-delivery__item--stock Element modifier
.wcpce-delivery--pills       Modifier
```

### BEM naming — PDP Product Accordion
```
.wcpce-accordion              Block
.wcpce-accordion__item        Element
.wcpce-accordion__heading     Element
.wcpce-accordion__trigger     Element
.wcpce-accordion__content     Element
.wcpce-accordion__faq         Element (inner FAQ container)
.wcpce-accordion__faq-item    Element
.is-open                       State class (added/removed by JS)
```

### BEM naming — PDP Product Cross-sells / Related
```
.wcpce-related                 Block
.wcpce-related__heading        Element
.wcpce-related__grid           Element
.wcpce-related__item           Element
.wcpce-related__empty          Element
.wcpce-related--mobile-scroll  Modifier
```

### Section headers in CSS
```css
/* ===== SECTION NAME ===== */
```

### z-index hierarchy
- Card widget: image (no z-index) → overlay link (3) → badge/stock-label/actions/button (4)
- Gallery widget: nav/overlay-icons (4) → lightbox (100000)
- No `isolation: isolate` on the gallery wrapper (lightbox must escape to viewport). `isolation: isolate` is correct on the card widget wrapper (prevents badge bleed above sticky header).

### Breakpoints
Always `767px` for mobile, `1024px` for tablet. Matches Elementor defaults.

### Border radius consistency
All badge and overlay elements use `6px` border-radius. Never `100px` (pill shape) for these elements.

### Theme style overrides
Gallery buttons use `!important` on colour/background/border states to prevent Bourgini site-wide `button:hover` colour bleed.

### Video preview as CSS background-image
Gallery video slide previews use `<span>` with `background-image` + `background-size: cover`, not `<img>`. Reason: Elementor's global `img { height: auto }` overrides the aspect ratio on image elements. Background layers are not affected by this rule.

---

## JavaScript conventions (PDP Gallery widget)

### Static `get_script_depends()` — never conditional
`get_script_depends()` is called by Elementor before widget settings are initialised. Always return a static array. Never call `get_settings_for_display()` here — it is null at this lifecycle stage and causes a fatal TypeError. (Same rule as the card widget — see DECISIONS_LOG, v1.0.15.)

### Single init guard on the gallery element
Use the central `initGallery(galleryEl)` helper. The init flag (`data-wcpce-gallery-init="1"`) is set on the `.wcpce-gallery` element itself — not on any Elementor wrapper. Both `DOMContentLoaded` and `elementorFrontend` hooks call `initGallery()`, which returns early if already initialised.

### Document-level event listeners: once per instance
Bind document-level handlers (zoom mousemove/mouseup) exactly once per `WCPCEGallery` instance using a boolean flag (`zoomDocumentEventsBound`). Never re-bind per lightbox open — this causes listener-stacking memory leaks.

### Lightbox outside `.wcpce-gallery`
The lightbox `<div>` is rendered after the `.wcpce-gallery` wrapper, not inside it. JS finds it via `document.getElementById()`, not `querySelector()` on the root. `position:fixed` must not be trapped inside a stacking context ancestor.

---

## JavaScript conventions (PDP Product Accordion widget)

### Server-render open, then apply JS state
Accordion content must render available in the initial server HTML. PHP renders outer panels and FAQ answers open as the no-JS/crawler fallback. `product-accordion.js` then reads `data-default-open` and applies the configured closed/open state with `aria-expanded` and `hidden`.

### Closed panels use `hidden` after initialization
Do not hide closed accordion panels with CSS-only `display:none`. After JS initialization, closed panels use the HTML `hidden` attribute so content leaves the tab order and screen-reader traversal.

### Keep accordion JS scoped and guarded
Use `initAccordion(accordionEl)` with the `data-wcpce-accordion-init` guard on the `.wcpce-accordion` element. Both `DOMContentLoaded` and Elementor's `frontend/element_ready` hook may fire.

---

## Elementor control conventions

### Selectors exclude PFAS badge and Nieuw badge (Product Card)
All Discount Badge Style controls use `:not(.wc-card__badge--pfas):not(.wc-card__badge--nieuw)`.

### `add_responsive_control` for layout values
Columns, gap, title clamp, aspect ratio — all use `add_responsive_control`.

### `condition` to show/hide controls
```php
'condition' => array( 'source' => 'manual' ),      // card widget
'condition' => array( 'enable_lightbox' => 'yes' ), // gallery widget
```

### No products-per-page control in auto mode
Registering such a control requires `loop_shop_per_page`, which is explicitly forbidden.

---

## ACF field conventions

### Registered programmatically in `class-acf-fields.php`
Never rely on JSON sync or admin-saved groups.

### Field key naming
```
field_wcpce_{field_name}    e.g. field_wcpce_badge_nieuw
```

### Group key naming
```
group_wcpce_{group_name}    e.g. group_wcpce_product_badges
```

### ACF Pro check before repeater registration
Repeater groups (`group_wcpce_pdp_gallery_media`, `group_wcpce_pdp_usps`) are only registered when ACF Pro is active (checked via `acf_get_field_types()` returning `repeater`). Free ACF silently drops repeater sub-fields.

### ACF repeaters store content only unless explicitly decided otherwise
The PDP USP repeater stores only `usp_text`. Presentation choices (layout, icons, columns, spacing, colours, typography, border, radius, shadow) belong in Elementor controls, not ACF fields.

### Image fields use `return_format = 'id'`
`card_hover_image` and `video_thumbnail` store attachment IDs only.

### ACF fields are read via direct `get_post_meta()`, not `get_fields()`
Since v1.0.44 for the card widget. For repeater-based PDP widgets, `pdp_gallery_videos` and `pdp_usps` are read via `get_field()` (ACF Pro repeater — no equivalent direct meta path).

---

## Template conventions

### `card.php` is a PHP include, not a class method
Variables flow in via the enclosing scope from `render_card()`.

### Execution order in card.php (matters for badge priority)
Required order: ACF data loads before image logic; badge priority is computed before `niet_leverbaar` suppression; `niet_leverbaar` suppression happens before HTML output.

### Changelog format
Free-form English prose per entry. Required release metadata lives in the plugin header `Version:`, the `WCPCE_VERSION` constant, and the `Stable tag` in readme.txt. Also update readme changelog, ROADMAP current/recent release notes, TECHNICAL_SPEC version examples or changed APIs, and GLOSSARY release history when behaviour changes.

---

## JavaScript: progressive enhancement, scoped per widget (v2.1.0)

Core commerce content is always server-rendered. JavaScript is added only where interaction requires it — scoped to the widget, registered in `class-assets.php`, deferred, and enqueued only where the widget renders. The product card widget and purely presentational widgets (Price & Promo Block, USP, Delivery & Availability, etc.) ship no JS; prefer native `<details>`/`<summary>` over scripting for light interactions. `get_script_depends()` always returns a static array (purely presentational widgets return an empty array). See DECISIONS_LOG.

## Price display: tax-correct amounts (v2.1.0)

Widgets that render prices outside the product card pass every displayed amount through `wc_get_price_to_display( $product, array( 'price' => $raw ) )` so the output respects the shop's tax display setting and matches WooCommerce's own `get_price_html()` and Product schema. The discount percentage is tax-neutral and is not converted. The struck reference value runs through the `wcpce_price_reference_value` filter; percentage and savings derive from that reference. (The card widget predates this and uses `wc_price()` on raw helper values — no visible difference on Bourgini's incl-VAT setup; see DECISIONS_LOG.)

## Reusable product-label conventions (v2.7.1)

### Labels use a taxonomy, not new ACF flags

Reusable commercial labels belong to `wcpce_product_label`. Do not add a new ACF true/false field, Elementor toggle and CSS special case for every new label. Existing Nieuw/PFAS/niet-leverbaar fields remain only for backwards compatibility and their special status rules.

### Term meta owns presentation

The term name owns visible text. `wcpce_label_color`, `wcpce_label_position`, `wcpce_label_priority`, `wcpce_label_active`, `wcpce_label_visible_from`, `wcpce_label_visible_until` and optional `wcpce_label_pdp_details` own reusable definition/state. Validate these values both on save and when preparing frontend output. Colour is the deliberate per-label style exception because it communicates the label identity; text contrast remains automatic.

### Scheduling is label-wide and site-local

Optional visibility boundaries apply to every assignment of the term and every frontend context. Parse `datetime-local` input in `wp_timezone()`, compare with `current_datetime()`, include the selected start and end minutes, and leave a missing boundary open-ended. Invalid stored dates or an end before the start must fail closed. Do not introduce per-product schedule overrides without a separate data-model decision.

### PDP explanation HTML is safe, term-level content

PDP explanation content is optional and global to the reusable label. Use the WordPress Visual/Text editor on the term edit screen and sanitise with `wp_kses_post()` on both save and render. Do not execute shortcodes, scripts, event attributes or arbitrary embeds, and do not call the deprecated `wp_targeted_link_rel()` helper. The Product Label Details widget owns placement, limit and shared presentation; it must not duplicate or override the label schedule.

### Shared format is widget-level only

Font family, responsive font size, weight, line height, letter spacing, text transform, responsive padding, border radius, shadow and stack gap belong to the shared `WCPCE_Custom_Label_Controls` Elementor surface. All custom labels inside one widget use that same format. Never generate a separate Elementor control per taxonomy term.

### Custom-label selectors must not touch system elements

Shared controls may target only `.wc-card__custom-label` and `.wc-card__labels` in card contexts, or `.wcpce-gallery__badge--custom` and `.wcpce-gallery__custom-labels` in the PDP Gallery. Discount/Korting, Nieuw, PFAS-vrij, price, Gratis verzending, stock and Niet meer leverbaar retain their existing independent controls and styles.

### Position and collision rules are deterministic

Only top-left and top-right are valid reusable-label positions on cards. Labels stack vertically by priority (lower first), then alphabetically. Korting/Nieuw keeps its existing system slot; a custom stack in that same corner is offset. PFAS remains bottom-left, stock remains bottom-right and niet-leverbaar suppresses reusable commercial labels. The Gallery deliberately ignores card position: it renders labels horizontally after system badges, uses the same priority order and follows the Gallery's above/below badgebar setting.
