# Roadmap - Woo Card Chef

## Current version: 2.6.10

---

## Plugin positionering

Woo Card Chef is een private Bourgini Elementor WooCommerce widget plugin. De plugin is gestart als productkaart en productgrid oplossing voor categoriepagina's en landingspagina's. Vanaf v2.0.0 groeit de plugin door naar een bredere set Bourgini specifieke Elementor WooCommerce widgets.

De huidige werkstroom is PDP optimalisatie. Dat betekent niet dat de plugin een volledige PDP builder wordt. De richting blijft: losse, herbruikbare widgets die samen sterke WooCommerce templates mogelijk maken binnen Elementor.

Belangrijk voor alle nieuwe widgets:
- werken vanuit de huidige WooCommerce productcontext;
- passen visueel bij de bestaande Bourgini productkaartstijl;
- gebruiken bestaande helpers waar dat logisch is;
- laden assets alleen wanneer de widget rendert;
- leveren server-side HTML voor belangrijke commerce content;
- krijgen defensieve validatie van instellingen;
- blijven bruikbaar in Elementor Theme Builder templates met editor fallback;
- bevatten geen onnodige alles-in-een logica.

---

## Done (v1.0.0-v1.0.84)

### Foundation & card features
Core Elementor widget with auto/manual query modes, WooCommerce product grid, discount badge (3 formats plus a separate show/hide toggle), out-of-stock label, action button, ACF-driven USPs (3 slots), free shipping pill, Lipscore rating placeholder, savings line, hover image swap, and responsive style controls for all card elements.

### Query & filtering
Sale-only, featured-only, stock, include/exclude by ID filters for manual mode. Configurable empty state. Editor notice when manual query returns zero products. Manual mode and editor fallback queries both respect the WooCommerce "Hide out of stock items" site setting (was Phase 5 H2 - completed in v1.0.50).

### Code quality & performance
PHP return types on all methods, HPOS compatibility, WC query API for ordering, SVG sprite for icons, LCP-optimised image loading (`fetchpriority`, `loading`, `sizes`, `decoding`), `isolation: isolate` on widget wrapper, `wc_prime_caches_for_products()`, bulk attachment cache priming, `get_price_html()` cached per card, ACF fields read via direct `get_post_meta()`, transient caching for manual queries (stores IDs not objects), null-safe editor detection.

### Custom badges
Nieuw badge (red, top-left, ACF), PFAS-vrij badge (muted green, bottom-left, ACF, hidden on mobile), Niet meer leverbaar overlay (centred, suppresses discount and Nieuw badges). All badge/pill elements unified to 6px border-radius. Badge style controls exclude PFAS and Nieuw via `:not()` selectors.

### Pagination (final stable architecture, v1.0.54 onward)
Server-rendered numbered pagination with prev/next SVG chevrons. No JavaScript. Auto mode uses native `paged` via `get_pagenum_link()` (preserves query args for WBW and tracking). Manual mode uses custom `wcpce_paged` query var with path-only base URL (strips query args - documented trade-off, see DECISIONS_LOG). The v1.0.36-v1.0.53 AJAX/load-more architecture was removed in the v1.0.54 refactor; the v1.0.55-v1.0.71 URL builder iteration ended at v1.0.69's stable path-only approach.

### Accessibility
`aria-labelledby` on article pointing to a unique title ID (`wcpce-title-{widget_id}-{product_id}` - v1.0.52). `role="list"` on grid, `role="listitem"` on cards. `aria-hidden` removed from card body. Screen-reader-only Van/Voor price labels.

### Phase 5 hardening & accessibility (v1.0.75-v1.0.79)
See KNOWN_ISSUES for full detail. H1 validate_manual_settings, H6 overlay aria-label, H8 conditional hover image resolution, H9 conditional get_variation_prices, H10 dynamic sizes attribute, H11 prefers-reduced-motion crossfade, H13 pagination current-page aria-label.

### Post-1.0.79 patch series (v1.0.79.1-v1.0.79.13)
Lipscore star colour control, WBW sticky header resolution via CSS position:sticky on header template, wcpce-grid-section container class, bug-check pass. See KNOWN_ISSUES for full detail.

### Phase 6 - R1-R5 helpers (v1.0.80-v1.0.84) ✓ Voltooid
All five helper classes extracted. Card output byte-identical. `includes/Helpers/` is the foundation for the multi-widget architecture.

- **v1.0.80 R1** - `WCPCE_Badge_Helper`
- **v1.0.81 R3** - `WCPCE_Stock_Helper`
- **v1.0.82 R2** - `WCPCE_Price_Helper`
- **v1.0.83 R4** - `WCPCE_Image_Helper`
- **v1.0.84 R5** - `WCPCE_ACF_Helper`

### v2.0.0 - PDP Gallery widget (PDP Phase 1) ✓ Voltooid

Infrastructure: widget files moved to `includes/Widgets/`, `class-assets.php` added (R7), `group_wcpce_pdp_gallery_media` ACF group added. Plugin now registers two widgets.

Gallery widget features: WC featured image + gallery as slides, YouTube video slides via ACF repeater (ACF Pro required, always after WC images, positioned at slot thumbnail_count − 1 in the strip), thumbnail strip with +N overflow indicator, badgebar (Korting / Nieuw / PFAS-vrij) above or below with equal badge heights and PFAS leaf icon, status overlays (Niet meer leverbaar / Tijdelijk uitverkocht), full lightbox with image zoom + pinch-zoom + video embed, deferred JS, LCP-optimised first image, validate_gallery_settings() defensive validation.

Development history for the gallery (all incorporated in v2.0.0):
- **v1.0.85** - Gallery aspect-ratio fix: 1:1 viewport on the outer slide container.
- **v1.0.86** - Video thumbnail source aligned to mqdefault.jpg consistently.
- **v1.0.87** - Video preview rendered as CSS background-cover layer, bypassing Elementor's global img { height: auto }.
- **v1.0.88** - Video-lightbox and image-lightbox separated (video always opens player even when image lightbox is off). Single initGallery() guard prevents double-init. Zoom document listeners bound once per instance.
- **v1.0.89** - "Tot " prefix restored for variable products with mixed discounts. validate_gallery_settings() added. YouTube host strict validation. Video custom thumbnails cache-primed. Hidden video-slide play buttons set to tabindex="-1". Gallery script registered with defer strategy.

R6 (slim widget class) happened implicitly during gallery build - no separate release needed.
R7 (class-assets.php) delivered in v2.0.0.

---

## Current status and next work

Geen actieve fase. PDP Phase 8 (Product Cross-sells / Related) is voltooid in v2.6.0.

De resterende roadmap is bewust smal gehouden. Er worden geen nieuwe PDP widgets of multi-shop uitbreidingen meer gepland totdat daar een concrete businesscase voor is.

### v2.7.0 - Accessibility cleanup - Gepland

Doel: de bestaande toegankelijkheidsbasis opschonen en naamconflicten met themes voorkomen, zonder visuele of functionele herbouw.

Scope:
- generieke `.sr-only` usage in de Product Card template vervangen door een namespaced `.wcpce-sr-only` utility;
- tijdelijke backward-compatible CSS behouden waar nodig om bestaande markup veilig te laten renderen;
- screen-reader-only patronen tussen Card, Price en andere widgets waar logisch gelijk trekken;
- keyboard/focus states nalopen op de bestaande widgets na de class-rename;
- geen herontwerp van kleuren, contrast of merkkeuzes.

Acceptatiecriteria:
- screen-reader labels voor prijsrelaties blijven beschikbaar;
- geen theme clash meer via een generieke `.sr-only` class;
- bestaande frontend output blijft visueel gelijk;
- gallery, accordion en product cards blijven toetsenbordbedienbaar.

### v2.8.0 - Analytics foundation - Gepland

Doel: een lichte, opt-in meetlaag toevoegen voor bestaande widgets, zodat interacties meetbaar worden zonder harde afhankelijkheid van GA4, GTM of een specifieke datalayer.

Scope:
- centrale helper of frontend event conventie voor Woo Card Chef interacties;
- events voor product-card clicks, gallery interacties, accordion opens, upsell clicks en cross-sell/related clicks;
- widget/product context als eventdata waar beschikbaar;
- filter of instelling om tracking output uit te zetten of aan te passen;
- geen eigen analytics dashboard en geen directe vendor lock-in.

Acceptatiecriteria:
- events zijn consistent benoemd en gedocumenteerd;
- integratie met GTM/GA4 is mogelijk via data attributes, CustomEvent of filterbare output;
- analytics code verandert geen winkelgedrag;
- geen tracking wordt geladen vanaf externe domeinen door de plugin zelf.

### Future - Robust product labels architecture - Gepland

Doel: nieuwe productlabels kunnen beheren zonder voor ieder label nieuwe ACF-velden, Elementor-controls, helperlogica, template-output en CSS-special cases toe te voegen.

Aanleiding: `Nieuw` en `PFAS-vrij` zijn nu als losse ACF true/false badges ingebouwd. Dat is beheersbaar voor twee vaste badges, maar niet schaalbaar wanneer er later labels zoals Bestseller, Actie, Limited edition of andere commerciële/producteigenschap-labels bijkomen.

Voorkeursrichting:
- generiek productlabelsysteem, bij voorkeur via een WooCommerce product taxonomy of centraal configureerbare labeldefinities;
- per label beheerbare tekst, kleur, positie, prioriteit, mobiel gedrag en eventueel icoon;
- producten koppelen aan labels zonder codewijziging;
- bestaande `badge_nieuw` en `badge_pfas_vrij` blijven tijdelijk backwards-compatible of worden gecontroleerd gemigreerd;
- `Niet meer leverbaar` blijft een aparte beschikbaarheidsstatus, omdat die speciale suppressie- en overlaylogica heeft;
- Card, Gallery, Upsells en Related gebruiken dezelfde label-helper en renderregels;
- Elementor krijgt algemene label-toggles/styling waar nodig, niet per nieuw label een hardcoded control.

Acceptatiecriteria:
- een nieuw label kan door beheer worden aangemaakt en aan producten gekoppeld zonder plugin-code aan te passen;
- bestaande producten met Nieuw/PFAS-vrij blijven zichtbaar na migratie;
- labelprioriteit voorkomt visuele conflicten met korting, voorraadlabels en niet-leverbaar overlays;
- frontend output blijft server-side en toegankelijk.

### v2.6.10 - Compatibility metadata sync - Voltooid

Metadata-release na staging- en productievalidatie op de actuele stack.

- WordPress getest tot en met 7.0.4 (`Tested up to: 7.0`).
- WooCommerce getest tot en met 11.0.1 (`WC tested up to: 11.0`).
- Elementor 4.2.2 en Elementor Pro 4.2.1 vastgelegd.
- PHP 8.3 vastgelegd als geteste runtime; minimum PHP 7.4 blijft ongewijzigd.
- Geen functionele wijzigingen aan widgets of frontend-output.

### v2.6.9 - Product Card empty-state frontend fix - Voltooid

Hotfix voor lege zoekresultaten en productarchieven in Auto mode.

- De frontend toont de configureerbare Nederlandse klanttekst wanneer geen producten matchen.
- Technische editoruitleg blijft beperkt tot Elementor editor/preview.
- Er zijn geen wijzigingen aan query-, filter- of paginagelogica.

### v2.6.8 - Product Accordion automatic manual fallback - Voltooid

Patch voor Bourgini handleidingen in `/manuals`.

- Product Accordion blijft eerst het ACF veld `product_manual` gebruiken wanneer dat gevuld is.
- Als ACF leeg is, zoekt de manual-sectie automatisch naar PDF's in de configureerbare manuals-map.
- Matching gebruikt SKU en bekende MPN meta-velden, inclusief varianten waarbij trailing nullen zijn gestript.

### v2.6.7 - Gallery accessibility hardening - Voltooid

Hotfix na live ARIA/WCAG review van categoriepagina en PDP.

- Product Gallery image slides gebruiken nu attachment alt text met productnaam als fallback wanneer de mediabibliotheek-alt leeg is.
- Inactieve gallery slides krijgen `inert` naast `aria-hidden`, en de gallery JS toggelt dit mee bij slidewissels.
- Bevat ook de v2.6.6 Elementor frontend hook guard.

### v2.6.6 - Elementor frontend hook guard - Voltooid

Hotfix na live staging controle van 2.6.5.

- Product Gallery en Product Accordion registreren Elementor `frontend/element_ready` hooks alleen wanneer `elementorFrontend.hooks.addAction` beschikbaar is.
- Normale frontend initialisatie via DOMContentLoaded blijft intact.
- Verwijdert live console errors op staging waar `elementorFrontend` al bestaat voordat de hooks API beschikbaar is.

### v2.6.5 — Product Gallery thumbnail style controls ✓ Voltooid

Kleine styling/control patch voor de Product Gallery.

- Active thumbnail border colour control target nu de echte `.wcpce-gallery__thumb-btn` border.
- Nieuwe Elementor-control `Thumbnail hover border colour`.
- Default hoverkleur is Bourgini rood `#B4211C`.
- Thumbnail button hover/focus background expliciet vastgezet zodat theme button-hover styling niet in de thumbnailstrip lekt.

### v2.6.4 — Product Gallery YouTube thumbnail hotfix ✓ Voltooid

Hotfix voor v2.6.3.

- YouTube fallback thumbnails in de Product Gallery werkten niet meer wanneer de video-ID hoofdletters bevatte.
- Oorzaak: `sanitize_key()` lowercaset waarden, terwijl YouTube IDs hoofdlettergevoelig zijn.
- Oplossing: case-preserving YouTube ID allowlist toegevoegd en gebruikt voor `mqdefault.jpg` fallback URLs.

### v2.6.3 — PHPCS security hardening patch ✓ Voltooid

Kleine hardening release na installatie van Composer/PHPCS en WordPress Coding Standards.

- PHP syntax lint: alle pluginbestanden schoon.
- PHPCS `WordPress.Security.EscapeOutput`: schoon na explicietere escaping in Gallery, Upsells en Cross-sells / Related.
- Gallery aria-labels en fallback YouTube thumbnail URLs explicieter geescaped.
- Upsells en Cross-sells / Related heading tags via `tag_escape()` geoutput.
- Lokale variabelen hernoemd die PHPCS als WordPress global override of reserved keyword zag.
- Geen bedoelde frontend-gedragswijzigingen.

### v2.6.2 — Product Upsells order control ✓ Voltooid

Kleine functionele uitbreiding voor de Product Upsells widget.

- Nieuwe Elementor-control `Product order`.
- Default blijft `WooCommerce linked order`, zodat bestaande templates en handmatig ingestelde upsell-volgorde hetzelfde blijven werken.
- Nieuwe optie `Popularity` sorteert gekoppelde upsells op WooCommerce total sales, hoogste eerst.
- Producten met hetzelfde aantal verkopen behouden de gekoppelde WooCommerce-volgorde als tie-breaker.

### v2.6.1 — Code review hardening patch ✓ Voltooid

Hardening release op basis van een grondige code review van v2.6.0 (bugs, snelheid, veiligheid).

- Alt-tekst die aan `wp_get_attachment_image()` wordt meegegeven (card-template primaire afbeelding, gallery video-thumbnail) wordt niet meer vooraf ge-escaped. De functie escapet zijn eigen attributen (CONVENTIONS); pre-escaping dubbel-encodeerde `&` en quotes in product- en videotitels.
- Lokale loopvariabele `$product` in `get_visible_products_from_ids()` van de Cross-sells / Related widget hernoemd naar `$candidate`, zodat die nooit verward kan worden met de WooCommerce global — precies de vervuiling waar v2.5.1/v2.5.8 tegen beschermen.
- Dubbele `wcpce-accordion__item--reviews` class in de Accordion-render verwijderd (de generieke per-sectie modifier dekt dit al; JS target de sectie via `data-section`).
- Docblock van `build_reviews_html()` gecorrigeerd: beschrijft nu de werkelijke `wp_kses_post()`-implementatie (geen "extra script pass"; Lipscore rendert client-side op de placeholder, `data-*` attributen overleven kses).

Geen functionele wijzigingen voor shoppers behalve correcte alt-teksten bij titels met speciale tekens. Gemengde CRLF/LF-regeleindes bewust niet genormaliseerd (cosmetisch, aparte opschoonactie indien gewenst).

### v2.6.0 — Product Cross-sells / Related widget ✓ Voltooid

Nieuwe PDP productgrid-widget voor het onderste aanbevelingenblok op productpagina's.

- Primaire bron: WooCommerce Cross-sells van het huidige product.
- Fallback: WooCommerce Related Products wanneer er geen zichtbare Cross-sells zijn.
- Hergebruikt `WCPCE_Card_Renderer` en `templates/card.php`, zodat afbeelding, prijs, badges, voorraadlabel, hover swap en optionele add-to-cart gelijk blijven aan Product Card Grid en Product Upsells.
- Zelfde Elementor-controloppervlak als Product Upsells: heading, maximum producten, hide-empty, card element toggles, badge controls, responsive grid/mobile scroll, typography, colours en card styling.
- Geen nieuwe ACF-velden en geen widget-specifieke JavaScript.

### v2.5.8 — Code review hardening patch ✓ Voltooid

Hardening release op basis van een grondige code review van v2.5.7.

- `get_current_product()` volgorde uitgelijn over alle vijf resterende PDP widgets (Gallery, Price, USP, Delivery, Accordion): `get_queried_object()` wordt nu overal eerst gecontroleerd, vóór `global $product`. Matcht de v2.5.1 fix van de Upsells widget. Voorkomt dat een eerder gelopen product loop de verkeerde context nalaat voor PDP widgets.
- `get_reviews_content()` in de Accordion widget vereenvoudigd: de overbodige set/restore van `global $product` rond `apply_filters('woocommerce_product_tabs')` is verwijderd. De global wordt nu eenmalig ingesteld vlak vóór de Lipscore callback en hersteld via `finally`.
- `esc_attr()` vervangen door `tag_escape()` voor HTML-tagnamen in `render_accordion_item()` van de Accordion widget. Sluit aan bij het bestaande patroon in de USP- en Upsells-widgets en is de WordPress-canonieke functie voor HTML-tagnamen.

### v2.4.1 - Product Accordion hardening patch (Voltooid)

Bugfix- en hardening release voor de Product Accordion en gedeelde PDP helpers.

- Accordion panels renderen server-side open als no-JS/crawler fallback. `product-accordion.js` leest daarna `data-default-open` en zet de geconfigureerde gesloten/open staat met `aria-expanded` en `hidden`.
- De Specs-sectie gebruikt direct `wc_display_product_attributes( $product )`, zodat ook dimensies en gewicht zichtbaar kunnen zijn wanneer er geen custom zichtbare attributen bestaan.
- De CSS die de overbodige WooCommerce "Additional information" heading verbergt is gescoped naar de Specs-sectie, zodat headings in Beschrijving, FAQ, Reviews of Manual niet meer per ongeluk verdwijnen.
- Directe `mb_substr()` calls in Product USP / Benefits en Product Price & Promo zijn vervangen door helpers met `function_exists( 'mb_substr' )` fallback.
- `WCPCE_Price_Helper::get_product_price_data()` heeft nu een per-request cache, omdat meerdere PDP widgets dezelfde productprijsdata kunnen opvragen.
- Lipscore tab callback capture herstelt `global $product` veilig via `finally`.

### v2.4.0 — Product Accordion widget (PDP Phase 6) ✓ Voltooid

Nieuwe Elementor-widget die de standaard WooCommerce productdetailpagina-tabs vervangt. De plugin registreert nu zes widgets (Product Card, Product Gallery, Price & Promo Block, Product USP / Benefits, Product Delivery & Availability, Product Accordion).

Vijf secties in vaste volgorde: Beschrijving (WooCommerce product description), Extra informatie (WooCommerce attributen-tabel, alleen public attributes), Beoordelingen (Lipscore WC tab, via tab callback), Veelgestelde vragen (ACF repeater `product_faq`, sub-fields `vraag`/`antwoord` — buiten plugin geregistreerd), Handleiding (nieuw ACF `file` veld `product_manual`, geregistreerd in `group_wcpce_pdp_accordion`). Lege secties worden automatisch verborgen.

WCAG 2.2 AA-toegankelijk: `<button>` triggers in configureerbaar heading-niveau (h2/h3/h4), `aria-expanded`, `aria-controls`, `hidden` attribuut op panels, `aria-hidden` op icoon, zichtbare focus states. Meerdere secties tegelijk open (NNG-aanbeveling). FAQ accordion-in-accordion. Lipscore count-sync via MutationObserver. Hash-jump naar reviews (#lipscore-review-list, #tab-lipscorereviews, #reviews).

Vervangt de bestaande accordion JS/CSS snippet, de FAQ shortcode snippet, en de afhankelijkheid van de Additional Custom Product Tabs for WooCommerce (ACPT) plugin via een nieuw ACF `product_manual` file-veld.

### v2.3.1 — USP widget bug-fix ✓ Voltooid

Bugfix: in auto-modus evalueerde de USP widget altijd alle drie bronnen (ACF repeater, short description, card USPs) tegelijk, ook als de eerste bron al resultaten had. De foreach(array(...)) constructie dwingt PHP tot eager evaluation vóór de loop begint. Vervangen door een sequentiële if/empty-keten zodat elke bron alleen geladen wordt als de vorige leeg was. Methode hernoemd van `sanitise_usp_text()` naar `sanitize_usp_text()` (WordPress-conventie, Amerikaans Engels).

### v2.3.2 — Delivery widget visuele verfijning ✓ Voltooid

CSS-only patch voor de list-layout van de Delivery & Availability widget. "Op voorraad" is nu groen + vet + groter icoon zodat het meteen als statusregel opvalt. Subtiele scheidingslijn tussen de statusregel en de delivery/shipping-regels. Shipping-tekst iets kleiner en muted (secundaire info). Delivery/shipping-iconen van olive-groen naar neutraal grijs zodat het groen volledig eigenaar is van de statushiërarchie.

### v2.1.0 - Price & Promo Block widget (PDP Phase 2) ✓ Voltooid

Nieuwe Elementor-widget die de standaard WooCommerce-prijsweergave op de PDP vervangt. De plugin registreert nu drie widgets (Product Card, Product Gallery, Product Price & Promo).

Features: reguliere prijs; bij actie doorgestreepte referentieprijs + actieprijs (simple), met los te togglen kortingspercentage-chip en besparingsbedrag; "Tot -X%" voor variabele producten met mixed discounts; variabele weergave "Vanaf €X" (laagste huidige prijs) of volledige range; compacte/uitgebreide layout (alleen prominentie, elementen via toggles); statusbewust (niet-leverbaar dimt de prijs en laat korting-framing weg). Hergebruikt `WCPCE_Price_Helper`, `WCPCE_Badge_Helper` en `WCPCE_ACF_Helper`. Server-side, zero-JS.

Onderbouwde keuzes (zie DECISIONS_LOG en de design brief):
- Alle bedragen via `wc_get_price_to_display()` — btw-correct, consistent met `get_price_html()` en de Product-schema.
- Referentiewaarde via de `wcpce_price_reference_value`-filter — 30-dagen Omnibus-bron later injecteerbaar zonder herontwerp; percentage en besparing leiden af van dezelfde referentie.
- Variabele producten in actie tonen géén losse doorgestreepte referentie/besparing — de laagste variant had die referentie nooit (ghost-anchor-vermijding, AVG/Prijzenwet).
- Geen eigen structured data — WooCommerce-core + SEO-plugin blijven eigenaar.
- Stijlcontrols via Elementor `selectors`/group-controls (CSS door Elementor gegenereerd).

### v2.2.0 - Product USP / Benefits widget (PDP Phase 3) ✓ Voltooid

Nieuwe Elementor-widget voor korte productvoordelen op de PDP. De plugin registreert nu vier widgets (Product Card, Product Gallery, Product Price & Promo, Product USP / Benefits).

Contentmodel: ACF Pro repeater `pdp_usps` met per rij één tekstveld `usp_text`. ACF bevat alleen inhoud; layout, iconen, kolommen, spacing, kleuren, typografie, borders, radius en shadow worden volledig via Elementor geregeld.

Bronlogica: standaard gebruikt de widget PDP USP's, daarna WooCommerce short description, daarna bestaande Product Card USP's. Editors kunnen ook één bron expliciet forceren. De widget is server-side en zero-JS.

### v2.2.1 - Short-description fallback list parsing ✓ Voltooid

Bugfix voor de Product USP / Benefits widget. Wanneer de WooCommerce short description als HTML-lijst is opgeslagen, blijft elk `<li>` nu een eigen USP item. In v2.2.0 werden de ingevoegde line breaks weer verwijderd door `wp_strip_all_tags( ..., true )`, waardoor de hele lijst als één gecombineerd item kon renderen.

---

### v2.3.0 - Product Delivery & Availability widget (PDP Phase 4) ✓ Voltooid

Nieuwe Elementor-widget voor beschikbaarheid, levertijd en verzendbelofte op de PDP. De plugin registreert nu vijf widgets (Product Card, Product Gallery, Product Price & Promo, Product USP / Benefits, Product Delivery & Availability).

Features: voorraadstatus via WooCommerce; `Niet meer leverbaar` via bestaande ACF/meta flag `badge_niet_leverbaar`; levertijd/cut-off als één Elementor tekstregel; gratis-bezorgingregel op basis van een Elementor drempel; tijdelijk-uitverkocht variant met optionele `Niet direct leverbaar` regel; niet-meer-leverbaar variant zonder leverbelofte; standaard stacked list; optionele compacte pill-layout; iconen aan/uit; styling via Elementor controls. Server-side, zero-JS, geen nieuwe ACF-velden.

## Vaste PDP ontwerpprincipes

Deze principes gelden voor alle PDP widgets die vanaf v2.0.0 worden uitgewerkt.

### Koopproces
Elke widget moet een duidelijke rol hebben in het koopproces. De belangrijkste vragen zijn:
- begrijp ik wat ik koop;
- zie ik snel wat het kost;
- weet ik of het beschikbaar is;
- weet ik wanneer het geleverd wordt;
- kan ik zonder frictie bestellen;
- krijg ik genoeg productbewijs om twijfel weg te nemen.

### Mobile-first
Mobiel is leidend. Belangrijke commerce informatie mag niet verdwijnen of pas na veel scrollen vindbaar zijn. Mobiele versies mogen compacter zijn, maar moeten dezelfde kerninformatie ondersteunen.

### Modulair
Geen grote alles-in-een widgets. De koopsectie wordt opgebouwd uit losse widgets, zodat Elementor templates flexibel blijven.

### Performance
Belangrijke content wordt server-side gerenderd. Scripts worden alleen toegevoegd wanneer interactie dat nodig maakt. Niet-kritische media en content mogen lazy laden.

### Toegankelijkheid
Nieuwe interacties moeten werken met toetsenbord, duidelijke focus states, correcte buttons en statusmeldingen waar nodig.

### Analytics
Elke interactieve PDP widget krijgt vooraf een meetdoel. Denk aan add-to-cart, media-interactie, delivery visibility, accordion open rate, accessory click rate en product recommendation clicks.

---

## Phase 5 - Stabiliseren & hardening ✓ Voltooid in v1.0.75-v1.0.79

All items resolved. See KNOWN_ISSUES for full detail per item.

---

## Phase 6 - Refactor: helpers uitknippen ✓ Voltooid in v1.0.80-v1.0.84

All five helpers extracted. R6 and R7 delivered implicitly in v2.0.0.

---

## Phase 7 - Reuse prep - Niet op roadmap

De plugin wordt voorlopig alleen ingezet op Bourgini.com. Multi-shop herbruikbaarheid, brand-neutral controls en shop-specifieke badge configuratie staan niet meer op de actieve roadmap.

Deze richting kan later opnieuw worden beoordeeld als de plugin daadwerkelijk buiten Bourgini.com wordt ingezet.

---

## PDP Phase 1 - Product Gallery widget - Voltooid in v2.0.0

See "Done" section above.

**Niet op roadmap: eerdere gallery-vervolgopties**
- Variatie-afbeelding wisseling bij variabele producten
- In-slide video afspelen via `display_mode`
- `video_position` gebruiken voor interleaving
- Thumbnails naast hoofdafbeelding als extra layoutoptie
- `mobile_thumbnail_count` functioneel verwerken in de mobiele thumbnaillogica

---

## PDP Phase 2 - Price & Promo Block widget ✓ Voltooid in v2.1.0

See "In progress" / Done summary above and the changelog. The editorial promo line ("actieregel") was parked for a later iteration; the 30-dagen Omnibus-referentieprijs as a data source is deferred (filter hook in place); variant-reactive live pricing remains v2.x.

Vervangt standaard WooCommerce prijsweergave op de PDP. Bouwt op `WCPCE_Price_Helper` en sluit visueel aan op de bestaande productkaartlogica.

### Doel
Een sterker prijsblok dat direct duidelijk maakt wat de klant betaalt en wat een eventuele actie oplevert.

### Scope
- reguliere prijs;
- actieprijs;
- doorgestreepte referentieprijs;
- kortingspercentage;
- besparingsbedrag;
- "Tot X% korting" voor variabele producten;
- compacte en uitgebreide weergave;
- statusbewuste styling bij producten die niet gekocht kunnen worden.

### Elementor controls
- prijsmodus: compact of uitgebreid;
- referentieprijs tonen;
- kortingspercentage tonen;
- besparingsbedrag tonen;
- labeltekst voor referentieprijs;
- spacing en typografie;
- kleurinstellingen voor reguliere prijs, actieprijs en referentieprijs.

### Techniek
- hergebruik `get_price_html()` waar dat juridisch en WooCommerce-technisch nodig is;
- gebruik helperlogica voor sale, variable sale en savings;
- geen client-side berekening van prijzen;
- server-side output met duidelijke screen-reader tekst voor prijsrelaties.

### Acceptatiecriteria
- normale producten, actieproducten en variabele producten tonen een correcte prijs;
- sale logica wijkt niet af van WooCommerce data;
- output is scanbaar op mobiel;
- prijsinformatie is beschikbaar zonder JavaScript;
- styling is consistent met Bourgini rood, tekstkleur en spacing.

---

## PDP Phase 3 - Product USP / Benefits widget - completed in v2.2.0

Deze fase is uitgevoerd als de `WCPCE_Product_USPs_Widget`.

### Gerealiseerd
- dedicated Elementor-widget voor korte USP regels op de PDP;
- ACF Pro repeater `pdp_usps` met één tekstveld per rij: `usp_text`;
- ACF bevat alleen content; styling en layout staan in Elementor;
- automatische fallback: PDP USPs → WooCommerce short description → Product Card USPs;
- layouts: list, compact cards en inline;
- optioneel globaal icoon: checkmark of dot;
- responsive columns, spacing, padding, typografie, kleuren, border, radius en shadow via Elementor;
- server-side output zonder JavaScript.

### Bewuste keuzes
- geen titel/body per USP;
- geen per-rij iconen of styling in ACF;
- geen categorie fallback in deze fase;
- geen aparte mobiele contentlimiet; het aantal items is globaal instelbaar en layout is responsive.

---

## PDP Phase 4 - Delivery & Availability widget - completed in v2.3.0

Widget voor beschikbaarheid, levertijd en verzendbelofte op productpagina's.

### Doel
Twijfel rond beschikbaarheid, levertijd en bezorgkosten wegnemen vlak bij de koopactie, zonder er een brede trust-widget van te maken.

### Onderzoeksbasis
- Baymard: verzendkosten en gratis-verzendinginformatie horen op de PDP en dicht bij de koopsectie; niet alleen in een headerbanner.
- Baymard: een concrete leverbelofte of bezorgdatum vermindert onzekerheid beter dan abstracte verzendsnelheid.
- Baymard: voorraadstatus moet duidelijk zijn in de koopsectie; tijdelijk uitverkocht en permanent niet leverbaar zijn verschillende toestanden.
- ACM/ConsuWijzer: levertijd en bijkomende kosten moeten juist, volledig, begrijpelijk en goed vindbaar zijn.

### Scope
- voorraadstatus;
- levertijd/cut-off als één tekstregel;
- gratis bezorging op basis van een drempel;
- statusvariant voor tijdelijk uitverkocht;
- statusvariant voor niet meer leverbaar;
- default stacked list;
- optionele compacte pill-layout.

### Data bronnen
- WooCommerce stock status;
- bestaande ACF `badge_niet_leverbaar` via `WCPCE_ACF_Helper`;
- productprijs via bestaande prijshelperlogica;
- leveringstekst en verzenddrempel via Elementor widget controls.

### MVP output states

**In stock, productprijs onder drempel**
- `Op voorraad`
- `Voor 23:00 besteld, morgen in huis`
- `Gratis bezorging vanaf €25,-`

**In stock, productprijs vanaf drempel**
- `Op voorraad`
- `Voor 23:00 besteld, morgen in huis`
- `Gratis bezorging`

**Tijdelijk uitverkocht**
- `Tijdelijk uitverkocht`
- optioneel: `Niet direct leverbaar`
- geen morgen-in-huis regel;
- geen gratis-bezorging regel.

**Niet meer leverbaar**
- `Niet meer leverbaar`
- geen morgen-in-huis regel;
- geen gratis-bezorging regel.

### Elementor controls
- voorraadregel tonen;
- leveringregel tonen;
- leveringtekst, default `Voor 23:00 besteld, morgen in huis`;
- gratis-bezorgingregel tonen;
- gratis-bezorging drempel, default `25`;
- label boven drempel, default `Gratis bezorging`;
- label onder drempel, default `Gratis bezorging vanaf €25,-`;
- tijdelijk-uitverkocht label;
- niet-meer-leverbaar label;
- layout: inline pills of stacked list;
- iconen aan of uit;
- kleur, typografie en spacing.

### Bewust niet in MVP
- geen exacte verzendkosten tonen;
- geen lage voorraad aantallen;
- geen countdown timer;
- geen automatische leverdagberekening;
- geen product-specifieke ACF overrides;
- geen retour/garantie/betaling trust-widget logica.

### Acceptatiecriteria
- status komt overeen met WooCommerce productdata;
- tijdelijk uitverkocht en niet meer leverbaar krijgen duidelijke tekst;
- gratis-bezorgingregel wisselt correct op basis van productprijs en drempel;
- tekst is kort genoeg voor mobiel;
- standaardlayout is een rustige stacked list;
- de widget werkt server-side zonder JavaScript;
- geen algemene trust widget logica in deze widget.

---

## PDP Phase 5 - Add to Cart widget ⏸ Descoped

De standaard WooCommerce add-to-cart flow is één van de meest geteste en complexe flows in het WC-ecosysteem. Een eigen widget wrapper voegt geen functionaliteit toe die niet al in WooCommerce of EAEL zit, terwijl het risico op regressies (variable products, bundels, backorders, payment plugin hooks) hoog is. Visuele consistentie en de niet-leverbaar disabled state zijn bereikbaar via CSS-overrides op de bestaande EAEL widget — zie de aparte EAEL briefing.

**Beslissing:** niet bouwen als eigen Woo Card Chef widget. Toegevoegd aan de Descoped-lijst.

Originele scope (ter referentie):

Eigen add-to-cart widget voor de PDP. De widget focust op duidelijke koopactie, aantalkeuze en feedback na interactie.

### Doel
De standaard WooCommerce koopactie visueel sterker, consistenter en statusbewuster maken binnen Bourgini templates.

### Scope
- aantal selector;
- primaire add-to-cart knop;
- loading state;
- succesfeedback;
- foutmelding bij ontbrekende of ongeldige keuze;
- disabled state wanneer kopen niet mogelijk is;
- statusbewuste buttontekst.

### Techniek
- WooCommerce add-to-cart gedrag blijft leidend;
- geen dubbele cart logica naast WooCommerce;
- AJAX alleen als dit stabiel en noodzakelijk blijkt;
- duidelijke fallback wanneer JavaScript niet actief is;
- statusmeldingen via toegankelijke live region.

### Elementor controls
- buttontekst;
- quantity selector tonen;
- button full-width op mobiel;
- succesmelding tonen;
- icon in knop aan of uit;
- spacing en knopstijl.

### Acceptatiecriteria
- toevoegen aan winkelwagen werkt met WooCommerce cart flow;
- knopstatus past bij productstatus;
- gebruiker krijgt duidelijke feedback na klik;
- interactie is toetsenbordbedienbaar;
- button is de visueel dominante actie in de koopsectie.

---

## PDP Phase 6 - Product Accordion widget ✓ Voltooid in v2.4.0

Widget voor semantische contentlagen onder de koopsectie. De widget vervangt losse snippets en standaard tabs waar dat zinvol is.

### Doel
Productinformatie scanbaar maken zonder dat belangrijke inhoud verdwijnt.

### Scope
- productbeschrijving;
- specificatiegroepen;
- FAQ onderdeel;
- handleiding of documentlink;
- onderhoudsinformatie;
- video onderdeel indien relevant;
- instelbare sectievolgorde.

### UX principes
- verticale secties of accordions;
- beschrijvende sectietitels;
- geen horizontale tabs als primaire structuur;
- belangrijke content blijft op mobiel beschikbaar;
- eerste sectie kan open staan als dat conversie helpt.

### Elementor controls
- secties aan of uit;
- standaard open sectie;
- sectievolgorde;
- iconen aan of uit;
- spacing;
- typografie;
- border en achtergrond.

### Toegankelijkheid
- iedere accordion header is een echte button;
- `aria-expanded` wordt correct gezet;
- focus blijft logisch bij openen en sluiten;
- inhoud is bereikbaar zonder hover;
- documentlinks hebben duidelijke linktekst.

### Structured data
FAQ markup is geen primaire SEO reden voor deze widget. Eventuele schema output wordt alleen toegevoegd wanneer de zichtbare inhoud en technische implementatie dit betrouwbaar ondersteunen.

### Acceptatiecriteria
- secties zijn scanbaar en logisch gegroepeerd;
- lege secties worden niet getoond;
- de widget werkt goed op mobiel;
- content is server-side aanwezig;
- FAQ en handleidingen zijn beheerbaar zonder codewijzigingen.

---

## PDP Phase 7 - Spare Parts & Accessories widget - Niet op roadmap

De eerste praktische invulling van deze richting bestaat al als `Product Upsells (PDP)` widget in v2.5.0. Verdere verdieping met ACF relationships, compatibiliteitsmapping, aparte onderdelenlabels of extra commerce-logica staat niet meer op de actieve roadmap.

Deze richting kan later opnieuw worden beoordeeld als er een concrete onderdelen/accessoires-briefing komt.

---

## PDP Phase 8 - Product Cross-sells / Related widget - Voltooid in v2.6.0

Widget voor aanbevolen producten onderaan PDP's. Deze widget gebruikt de bestaande productkaartbasis en houdt Cross-sells als handmatige, commerce-gerichte bron boven WooCommerce Related Products.

### Doel
Alternatieven en aanvullende producten duidelijk tonen zonder de primaire koopactie te verstoren.

### Scope
- WooCommerce Cross-sells als primaire bron;
- WooCommerce Related Products als fallback wanneer geen zichtbare Cross-sells bestaan;
- productcards met prijs, voorraad, badges en optionele add-to-cart;
- grid of horizontale mobile-scroll layout;
- hergebruik productcard styling via `WCPCE_Card_Renderer`.

### UX principes
- alternatieven en aanvullende producten krijgen een duidelijke context;
- aanbevelingen staan lager dan de primaire productinformatie;
- geen agressieve afleiding naast de primaire CTA;
- mobiel snel scanbaar.

### Status v2.6.0
- geïmplementeerd als `Product Cross-sells / Related (PDP)`;
- geen nieuwe ACF-velden;
- geen widget-specifieke JS;
- lege staat wordt verborgen of toont editor/front-end empty state afhankelijk van Elementor setting.

### Analytics
- recommendation click rate;
- add-to-cart na recommendation click;
- assisted revenue;
- positie en type aanbeveling.

---

## Remaining roadmap scope

De actieve roadmap bestaat vanaf v2.6.7 alleen nog uit:

1. `v2.7.0 - Accessibility cleanup`
2. `v2.8.0 - Analytics foundation`
3. `Future - Robust product labels architecture`

Andere richtingen in dit document zijn historie, afgeronde fases, bewuste descopes of later opnieuw te beoordelen ideeen. Ze vormen geen actieve roadmap.

---

## Descoped / explicitly not doing

| Feature | Reden |
|---------|-------|
| AJAX pagination of welke vorm dan ook | Verwijderd in v1.0.54. Stabiel server-rendered pagination sinds v1.0.69. |
| Load more knop | Verwijderd in v1.0.54. |
| Infinite scroll | SEO risico, back-button UX probleem. |
| Manual mode pagination met query-args behoud | Bewust gestript in v1.0.69. Zie DECISIONS_LOG. |
| `loop_shop_per_page` filter herintroduceren | Verwijderd in v1.0.72 (corrupte WBW facet counts). |
| Scripts conditioneel maken op widget settings in `get_script_depends()` | Lifecycle fatal (v1.0.15). Static array is bewuste keuze. |
| Meerdere paginated grids op één pagina | Geen bestaande use case. |
| WCAG AA contrast fix voor brand green op wit | Merkkleur heeft prioriteit. |
| Lipscore sterren kleur direct aanpassen | Fragiel (shadow DOM). CSS-filter control beschikbaar als alternatief. |
| WordPress.org submission | Private plugin voor intern gebruik. |
| Volledige PDP builder | Doel is losse widgets. |
| ACF veldnamen prefixen met migratie | Te veel risico op regressies. |
| YouTube privacy thumbnails via eigen proxy | Bewust niet in v2.0.0. Overweging voor later als AVG-compliance dit vereist. |
| display_mode in-slide video afspelen | Uitgesteld naar v2.1; ACF veld gereserveerd maar niet gelezen. |
| video_position interleaving | Uitgesteld naar v2.1; ACF veld gereserveerd maar niet gelezen. |
| PDP Phase 5 — Add to Cart widget | WooCommerce cart-flow is te complex en te riskant om te wrappen zonder toegevoegde waarde. Styling en niet-leverbaar state via CSS-overrides op EAEL widget. Zie aparte EAEL briefing. |
