# Testing Guide — Woo Card Chef

Woo Card Chef heeft nog geen PHPUnit-, browser- of visual-regression-suite. De repository automatiseert wel PHP-syntax, securitysniffs, PHP-compatibiliteit, dependency-audit, WordPress Plugin Check, metadata en ZIP-validatie. Iedere gedragswijziging vereist daarnaast gerichte widgettests en een staging smoke test.

## 1. PHP-syntaxcontrole

Voer vanuit de projectroot uit:

```powershell
Get-ChildItem .\wc-product-card-elementor -Recurse -Filter *.php | ForEach-Object {
    php -l $_.FullName
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}
```

Verwacht resultaat: ieder bestand meldt `No syntax errors detected` en het commando eindigt met exitcode 0.

## Geautomatiseerde repositorycontroles

Voer lokaal uit:

```powershell
composer install
composer validate --strict --no-check-publish
composer audit --locked
composer check
python tools/validate_plugin_metadata.py --plugin-dir wc-product-card-elementor --main-file wc-product-card-elementor.php
python tools/build_wordpress_plugin_zip.py --source-dir wc-product-card-elementor --destination-zip dist/woo-card-chef-test.zip --plugin-slug wc-product-card-elementor --main-file wc-product-card-elementor.php
```

GitHub Actions herhaalt deze controles op PHP 7.4 en 8.3, voert WordPress Plugin Check in een geïsoleerde WordPress-omgeving uit en publiceert de gevalideerde installatiezip pas wanneer alle voorgaande jobs slagen.

Gerichte standalone regressiecontrole voor de Product Card Grid-categorie-uitsluiting:

```powershell
php tools/test_product_card_category_exclusion.php
```

## 2. Minimale testdata

Gebruik minimaal de volgende WooCommerce-producten:

| Product | Benodigde data |
|---|---|
| Eenvoudig regulier | Prijs, voorraad, afbeelding en beschrijving |
| Eenvoudig in sale | Reguliere prijs, saleprijs en optioneel geplande sale |
| Variabel gemengd | Variaties met verschillende prijzen en kortingspercentages |
| Tijdelijk uitverkocht | `outofstock`, maar niet permanent niet-leverbaar |
| Niet meer leverbaar | ACF `badge_niet_leverbaar` actief |
| Rijk PDP-product | Gallery, YouTube-video, PDP-USPs, FAQ, manual, upsells en cross-sells |
| Fallback-product | Ontbrekende optionele ACF-data om alle fallbacks te testen |

Test waar mogelijk ook een verborgen product en een upsell/cross-sell die niet zichtbaar of niet gepubliceerd is.

## 3. Algemene smoke tests

- Plugin activeert zonder fatals of admin notices bij geldige afhankelijkheden.
- Ontbrekende WooCommerce of Elementor levert een beheerwaarschuwing en geen frontendfatal.
- Elementor toont de categorie **Woo Card Chef** en alle tien widgets.
- Frontend en Elementor preview gebruiken het juiste huidige product.
- Pagina's zonder Woo Card Chef-widget laden geen onnodige widgetassets.
- Browserconsole bevat geen nieuwe JavaScriptfouten.
- PHP-/WordPress-debuglog bevat geen nieuwe warnings, notices of deprecations.
- Alle shopperteksten zijn correct vertaald of hebben een Nederlandse standaardwaarde.

## 4. Widgetregressiematrix

### Product Card Grid

- Auto mode werkt op categorie-, shop-, zoek- en leeg archief.
- Exclude categories verwijdert geselecteerde categorieën en alle onderliggende categorieën in Auto en Manual mode; Auto-sortering, actieve filters en paginering blijven kloppen.
- Een product dat zowel aan een uitgesloten als een niet-uitgesloten categorie is gekoppeld blijft uitgesloten.
- Een leeg frontendarchief toont de configureerbare klanttekst en nooit technische editorhulp.
- Manual mode respecteert categorie, include/exclude, sale, featured en voorraadfilters.
- Native auto-pagination en `wcpce_paged` manual-pagination linken naar de juiste pagina.
- Eenvoudige en variabele prijzen tonen intern consistente korting, referentie en besparing.
- Nieuw, PFAS-vrij, niet-leverbaar en out-of-stock volgen hun prioriteitsregels.
- Overlay-link, Lipscore-placeholder en optionele action button veroorzaken geen geneste links.
- Een bestaand productlabel kan aan meerdere producten worden gekoppeld en gebruikt overal dezelfde tekst, kleur, positie en prioriteit.
- Een nieuw label vanuit het productscherm wordt opgeslagen, direct gekoppeld en verschijnt daarna als herbruikbare keuze.
- Een gebruiker met `manage_woocommerce` ziet het inline formulier en kan een label aanmaken; een aangepaste rol met alleen productbewerkingsrechten kan bestaande labels koppelen maar ziet het formulier niet en kan creatie niet via een gemanipuleerde POST afdwingen.
- Linksboven/rechtsboven stapelen correct; Korting/Nieuw veroorzaakt alleen in dezelfde hoek een verticale offset.
- De widgetlimiet toont de labels met de hoogste prioriteit (laagste getal) en inactieve labels blijven verborgen.
- Een label zonder planning blijft altijd zichtbaar; alleen-start en alleen-eind werken als open tijdvensters.
- Een label verschijnt exact vanaf de gekozen startminuut en blijft zichtbaar tot en met de gekozen eindminuut volgens de WordPress-sitezone.
- Toekomstige, verlopen, omgekeerde en beschadigde periodes renderen niet in Card Grid, Upsells, Related of Product Gallery.
- De centrale Productlabels-lijst toont een begrijpelijke status voor altijd/gepland/nu zichtbaar/verlopen/ongeldig.
- Herhaal start-/eindtests na het legen van eventuele full-page cache; controleer bij tijdkritische campagnes ook de ingestelde cacheduur.
- Niet meer leverbaar onderdrukt herbruikbare commerciële labels; PFAS en de bestaande beschikbaarheidsregels blijven ongewijzigd.
- Custom-labeltypografie, responsive padding, radius, schaduw en gap wijzigen alle custom labels binnen de widget gelijkmatig.
- Custom-labelstijlcontrols veranderen Korting, Nieuw, PFAS-vrij, prijs, Gratis verzending en voorraadlabels niet.
- Een per-label kleurwijziging blijft behouden wanneer de gedeelde widgettypografie of vormgeving wijzigt.
- Controleer met Query Monitor op een koude cache dat een productlijst labelrelaties in bulk ophaalt en niet één `wp_get_object_terms()`-query per kaart uitvoert; Gallery en Product Label Details op dezelfde PDP moeten dezelfde requestdata hergebruiken.

### Product Gallery

- Featured image, gallerybeelden en ACF-video's verschijnen in de juiste volgorde.
- Eerste afbeelding gebruikt eager/high-priority gedrag; overige beelden zijn lazy.
- Thumbnail overflow, active state en video-slot werken bij verschillende aantallen.
- Afbeeldinglightbox, zoom, pan, pinch-zoom, swipe en toetsenbordnavigatie werken.
- Video opent via YouTube-nocookie en wordt pas bij interactie als iframe gemaakt.
- Inactieve slides hebben `aria-hidden` en `inert`; verborgen play buttons zijn niet focusbaar.
- Lege media-alt gebruikt de productnaam als fallback.
- Herbruikbare labels verschijnen na Korting/Nieuw/PFAS in prioriteitsvolgorde en respecteren de Gallery-limiet.
- De boven/onder-instelling van de badgebar verplaatst ook custom labels; links-/rechtsboven van de kaartdefinitie wordt op PDP bewust genegeerd.
- Meerdere custom labels wrappen zonder horizontale overflow op tablet en mobiel.
- Gallery-typografie, padding, radius, schaduw en labelafstand wijzigen alleen custom labels en niet de systeemlabels.
- `Niet meer leverbaar` verbergt custom labels terwijl het bestaande PFAS-gedrag gelijk blijft.

### Product Label Details

- Een lege PDP-toelichting maakt geen detailblok; bestaande labels blijven verder ongewijzigd renderen.
- De WordPress Visueel/Tekst-editor bewaart alinea's, koppen, nadruk, lijsten en veilige links.
- Scripts, eventhandlers en niet-toegestane embeds worden bij opslag/uitvoer verwijderd; gewone en `target="_blank"`-links blijven zonder deprecated WordPress-calls werken.
- Alleen actieve labels binnen hun zichtbaarheidstijdvenster verschijnen, gesorteerd op prioriteit en begrensd door de widgetlimiet.
- `Niet meer leverbaar` onderdrukt ook de PDP-toelichtingsblokken.
- De labelnaam kan op widgetniveau worden verborgen; labelkleur blijft termgebonden en paneel/tekst/linkstyling blijft widgetgebonden.
- Meerdere toelichtingen stapelen zonder overflow op desktop, tablet en mobiel; de widget laadt geen JavaScript.
- Zonder productcontext of passende toelichting verschijnt alleen in Elementor een editorbericht en op de frontend geen leeg blok.

### Product Price & Promo

- Regulier, sale, variabel en gemengd-kortingsgedrag komt overeen met de matrix in `TECHNICAL_SPEC.md`.
- Bedragen volgen de WooCommerce-instelling voor inclusief/exclusief belasting.
- Niet-leverbaar onderdrukt kortingsframing en dimt alleen wanneer geconfigureerd.
- Er wordt geen dubbel Product/Offer-schema toegevoegd.

### Product USP / Benefits

- Auto source volgt: PDP-USPs, korte beschrijving, productkaart-USPs.
- Een HTML-lijst in de korte beschrijving wordt als losse USP-regels verwerkt.
- Expliciete source modes vallen niet onbedoeld door naar een andere bron.
- Lege inhoud levert geen lege wrapper op.

### Product Delivery & Availability

- In stock onder/boven verzenddrempel toont de juiste bezorgtekst.
- Out of stock en niet-leverbaar onderdrukken morgen-/gratis-verzendclaims.
- Variabele producten gebruiken de conservatieve prijsvergelijking.
- Lijst- en pill-layout blijven leesbaar op mobiel.

### Product Accordion

- Lege secties worden niet gerenderd; vaste volgorde blijft behouden.
- Zonder JavaScript is alle gerenderde inhoud zichtbaar.
- Met JavaScript worden alleen de bedoelde panelen gesloten en bijgewerkt via `hidden`/`aria-expanded`.
- FAQ-inner accordion, hash-jump en Lipscore-countsync werken.
- `global $product` is na reviewoutput hersteld.
- ACF `product_manual` wint van automatische matching.
- Automatische matching vindt een PDF op SKU/MPN, inclusief de varianten zonder trailing nullen.
- Een ongeldige of ontbrekende manualsmap veroorzaakt geen warning en geen lege sectie.

### Product Upsells

- Handmatige WooCommerce-volgorde blijft standaard behouden.
- Popularity-sortering sorteert alleen de zichtbare gekoppelde producten.
- Maximum, empty state, mobile horizontal scroll en card toggles werken.
- Optionele AJAX add-to-cart gebruikt WooCommerce `wc-add-to-cart`.
- Herbruikbare productlabels en de ingestelde limiet komen overeen met de gedeelde Product Card-output.

### Product Cross-sells / Related

- Zichtbare cross-sells hebben prioriteit.
- Related Products worden alleen gebruikt wanneer geen zichtbare cross-sells overblijven.
- Het huidige product en onzichtbare/onpubliceerde producten verschijnen niet.
- Cardweergave blijft gelijk aan Product Card Grid en Upsells.
- Herbruikbare productlabels en de ingestelde limiet komen overeen met de gedeelde Product Card-output.

## 5. Responsive en visueel

Controleer minimaal rond de Elementor-standaardbreakpoints:

- mobiel: tot en met 767 px;
- tablet: 768–1024 px;
- desktop: vanaf 1025 px.

Controleer kaarten met korte en lange titels, ontbrekende ratings, één tot drie USPs, verschillende badgecombinaties en afbeeldingverhoudingen. Let op layoutverschuiving, focusringen, overflow en horizontale scroll buiten widgets die dit expliciet ondersteunen.

## 6. Toegankelijkheid

- Bedien alle interactieve onderdelen alleen met toetsenbord.
- Controleer zichtbare `:focus-visible`-stijlen.
- Controleer dat `aria-expanded`, `aria-controls`, `aria-labelledby`, `aria-hidden`, `inert` en `hidden` met de zichtbare staat overeenkomen.
- Sluit een Gallery-lightbox met Escape en controleer focus return.
- Controleer de focus trap in de lightbox.
- Activeer `prefers-reduced-motion` en controleer dat kaart- en galleryanimaties rustig blijven.
- Inspecteer prijslabels met een screenreader of accessibility tree zodat Van/Voor-relaties begrijpelijk zijn.

## 7. Integraties

- **ACF Free:** kaartvelden, badges en `product_manual` werken.
- **ACF Pro:** video- en PDP-USP-repeaters verschijnen en renderen.
- **Lipscore:** rating en reviewcount vullen zonder de layout of globale productcontext te breken.
- **WBW Product Filter PRO:** AJAX, selectors en Force Theme Templates werken volgens `readme.txt`.
- **Elementor editor:** widgets renderen met editor fallback zonder het frontendproduct te beïnvloeden.

## 8. Release smoke test

Voer na het bouwen van de install-zip uit:

1. Installeer de zip als schone installatie op staging.
2. Activeer de plugin en controleer alle tien widgetregistraties.
3. Installeer dezelfde zip als update over de vorige productieversie.
4. Open minimaal één productarchief en één rijk productdetail.
5. Controleer browserconsole en WordPress-debuglog.
6. Bevestig dat opgeslagen Elementor-templates en controlwaarden behouden zijn.
7. Bewaar de vorige werkende zip als rollbackartefact.

Noteer bij een release welke scenario's zijn getest, in welke browser(s), met welke pluginversies en op welke omgeving.

## 9. v2.7.2 stagingacceptatie

Op 3 september 2026 is `2.7.2-rc.2` in Chrome gecontroleerd op de Bourgini Kinsta-stagingomgeving en daarna geaccepteerd als `2.7.2`:

- pluginassetversie `2.7.2-rc.2` bevestigd;
- alle acht Product Card Grid-pagina's gescand: 69 unieke producten, verdeeld als 9/9/9/9/9/9/9/6;
- geen product uit de uitgesloten reserveonderdelencategorie aangetroffen;
- prijs oplopend, prijs aflopend en populariteit behouden de uitsluiting en juiste volgorde;
- WBW-categoriefilter Waterkokers ververst de grid via AJAX naar acht passende producten zonder vastgelopen loader;
- desktopgrid toont drie gelijke kolommen zonder horizontale overflow;
- geen Woo Card Chef-consolefouten; een losse Trusted Shops `CustomEvent`-melding komt uit het externe trustbadge-script;
- de gridpaginering eindigt correct op pagina 8;
- bekende widget-scopingbeperking bevestigd: de onderliggende WordPress-hoofdquery en documenttitel blijven het ongefilterde totaal van 28 pagina's kennen, waardoor een handmatig bezochte pagina 9 leeg kan renderen. Zie `KNOWN_ISSUES.md`.

### Heracceptatie na platformupdates

Op 9 september 2026 is de definitieve `2.7.2` opnieuw in Chrome gecontroleerd nadat de stagingstack was bijgewerkt naar WordPress 7.1, WooCommerce 11.1.0, Elementor 4.2.4 en Elementor Pro 4.2.3 op PHP 8.3.30:

- een handmatige Kinsta-back-up van staging is om 09:43 CEST aangemaakt;
- Woo Card Chef 2.7.2 is actief en alle negen widgets staan geregistreerd in Elementor;
- het Product Card Grid bevat opnieuw 69 unieke producten over acht pagina's (9/9/9/9/9/9/9/6), zonder producten uit de uitgesloten onderdelenbranche en zonder horizontale overflow;
- prijs oplopend en aflopend sorteren correct; populariteit behoudt de uitsluiting;
- WBW-filter Waterkokers levert via de bestaande filteractie acht passende kaarten op zonder vastgelopen loader;
- de rijke PDP toont Gallery, Price, USP, Delivery, Accordion, Upsells en Related; Product Label Details blijft terecht leeg zonder toepasselijke labeltoelichting;
- Gallery-volgende activeert exact één volgende slide en houdt alle inactieve slides inert; Accordion opent het bijbehorende paneel met correcte `aria-expanded`-status;
- toevoegen aan winkelwagen verhoogt de stagingwinkelmand van zes naar zeven items en de checkout laadt volledig met orderoverzicht en zeven betaalmethoden; er is geen bestelling geplaatst;
- Kinsta `error.log` bevat geen meldingen; de browserconsole bevat alleen de bekende externe Trusted Shops `CustomEvent`-melding en geen Woo Card Chef-fout.

## 10. v2.8.0 Product Category Navigation stagingacceptatie

De definitieve 2.8.0-build is op 10 september 2026 gecontroleerd op de homepage van de Bourgini Kinsta-stagingomgeving. De oude losse categorienavigatie stond tijdens de test bewust boven de nieuwe widget ter visuele vergelijking.

- pluginmetadata meldt 2.8.0; PHP 7.4-compatibiliteit, WordPress-securitysniffs, Composer-audit, JavaScript-syntax, bestaande categorie-uitsluitingsregressietest en installatiezip zijn lokaal groen;
- de widget rendert de bewust ingestelde volgorde van tien unieke categorieën, inclusief de handmatige korte namen en Koffiezetapparaten als extra categorie;
- alle tien kaartlinks wijzen naar de verwachte WooCommerce-productcategorieën en de algemene link wijst naar `/shop/`;
- op 1280px viewport is de widget 1140px breed, zijn zeven kaarten zichtbaar en werkt navigatie over twee pagina's; de volgende/vorige-knoppen verplaatsen de lijst van 0 naar 540px en terug, wisselen hun disabled-status correct en houden exact één dot op `aria-current="true"`;
- op 390x844px is de widget 335px breed, zijn kaarten 100px breed, zijn de pijlen volgens instelling verborgen en zijn vier paginadots beschikbaar voor touchscrolling;
- zonder uitgevoerd widgetscript blijft de server-rendered horizontale lijst bruikbaar; na de eerste interactie activeert WP Rocket het uitgestelde script en verschijnen alleen bij overflow de werkende controls;
- WP Rocket lazy-loadt de afbeeldingen en Imagify levert 100/150px WebP-bronnen met `srcset` en de ingestelde responsive `sizes`; de versiegebonden CSS en JS worden eenmaal geladen met een cacheduur van één jaar;
- de browserconsole bevat geen Woo Card Chef-fout; alleen de al bekende externe Trusted Shops `CustomEvent`-melding is waargenomen.

De WordPress-debuglog is in deze browserronde niet zelfstandig geopend. De eerdere platformheracceptatie van 9 september 2026 dekt de overige negen widgets en de rijke PDP; deze ronde was gericht op de nieuwe categorienavigatie.

## 11. v2.8.1 bronverificatie en desktop-hercontrole (1 oktober 2026)

De gebruiker heeft `wc-product-card-elementor.zip` aangewezen als de ZIP die live is geïnstalleerd en bevestigd dat staging en productie al 2.8.1 draaien. Pluginheader, `WCPCE_VERSION` en `Stable tag` in de ZIP vermelden alle drie 2.8.1. Alle 38 pluginbestanden waren byte voor byte gelijk aan de lokale pluginmap vóór de metadata- en translator-commentaarcorrectie voor PR #9. De serverbestanden op productie zijn niet zelfstandig uitgelezen.

- SHA-256 aangeleverde ZIP: `BEAB80D43CA7150F5F93D5184D142771C8586A5511FE9FAA2B42E229A8B97E41`.
- Staging: `https://env-bourginicom-premium.kinsta.cloud/`.
- WordPress 7.1.2 en Elementor 4.3.3 bevestigd via generator-metadata; WooCommerce 11.1.2 en Elementor Pro 4.3.1 via hun versiegebonden CSS-assets. Woo Card Chef-assets melden 2.8.1; WP Rocket meldt 3.23.4.
- De eerste normale homepage had een onvolledig `wpr-usedcss`-blok zonder de specifieke Elementor-stijlen van homepage 88, header 171 en footer 97. `?nowprocket` herstelde de opmaak. Na de gebruikersmelding om opnieuw te testen, werkte ook de normale URL correct; het onvolledige Used CSS-blok was afwezig en de reguliere/minified stylesheets waren aanwezig.
- Header en hero renderen correct; de homepagegrid toont vier kolommen van 270px binnen 1140px.
- Alle tien Imagify WebP-categorieafbeeldingen laden. De volgende-pijl verplaatst de lijst; de tweede paginadot brengt de lijst naar 540px, activeert exact de tweede `aria-current`-dot en schakelt de volgende-pijl uit.
- Op de Chef's Dinner Party Glazed Grey-PDP activeert de volgende Gallery-knop afbeelding 2 van 17. De geneste WebP-afbeelding laadt met een natuurlijke breedte van 1421px; de lightbox toont de afbeelding en sluit correct.
- De knop Beschrijving opent het Accordion-paneel met `aria-expanded="true"`. Prijs, voorraad/levering en accessoire-/relatedkaarten zijn zichtbaar; er is geen winkelwagen- of checkoutactie uitgevoerd.
- De geïnspecteerde pagina's tonen geen PHP fatal/warning/notice-tekst. Browsermeldingen kwamen van het externe Trusted Shops-script en een Lipscore-productwaarschuwing; die zijn geen bewijs van een Woo Card Chef-fout.

Deze ronde was een read-only desktop-smoketest van de al geïnstalleerde plugin. De actuele PHP-runtime, admin/editor, WordPress-debuglog, mobiel/tablet, volledige filter-/pagineringmatrix en een nieuwe installatie/update zijn niet opnieuw gecontroleerd. De viewportoverride veranderde de effectieve browserbreedte niet en is hersteld; er wordt daarom geen mobiele acceptatie geclaimd. Een opnieuw gegenereerd Used CSS-blok is nog niet getest. De latere repositorycorrectie voegt alleen een translator-commentaar en compatibiliteitsmetadata toe en is niet op staging geïnstalleerd. Er zijn geen instellingen, plugins of productiegegevens gewijzigd.

De bijgewerkte PR-bron is lokaal gecontroleerd met PHP 8.3.32: syntax van alle 24 PHP- en drie JavaScript-bestanden, Composer-configuratie en dependency-audit, WordPress-securitysniffs, PHP 7.4+-compatibiliteit, gerichte WordPress-i18n-controle van Category Navigation, de bestaande categorie-uitsluitingsregressietest en pluginmetadata slagen. Het Python-buildscript valideert de test-ZIP met één pluginroot, 47 entries (38 bestanden en negen mappen) en nul backslashpaden. PHP 7.4-syntax en de volledige WordPress Plugin Check worden afzonderlijk door de verplichte GitHub CI uitgevoerd.

## 2026-10-06 — Local label-layout candidate 2.9.0-rc.1

- PHP 8.3.35: all 25 plugin PHP files linted; WordPress security and PHP 7.4+ compatibility sniffs passed.
- Standalone regression checks passed for all five positions, legacy/invalid-position fallback, global priority/limit, visibility toggle, discontinued-product suppression and text escaping. Existing category-exclusion regression checks also passed. The label checks now run in the CI PHP 7.4/8.3 matrix.
- Chromium browser measurements of the actual card template with WordPress/WooCommerce stubs: 20 fixture cards at each viewport width 320, 390, 768 and 1280px (80 card renderings), zero label intersections, zero clipped labels and zero horizontal card overflow.
- Fixtures include same/opposite corners, PFAS and stock, multiple labels, long unbroken text, large typography/padding and a 16:9 image with 120px cap. At a 260px card width the latter media height measured exactly 120px; dense rows grow instead of clipping.
- Mobile comparison saved as dist/product-label-mobile.jpg. This local preview uses the shared template and the public product image; it is not a staging installation.
- Metadata and canonical ZIP builder passed. Candidate: dist/woo-card-chef-v2.9.0-rc.1-wordpress-install.zip.
- Still pending: WordPress installation/update, real Elementor editor/generated CSS, WP Rocket Used CSS regeneration and end-to-end Gallery/Upsells/Related acceptance on staging. Regenerate Elementor CSS & Data and clear caches after the update.


## 2026-10-06 — Staging frontend acceptance: Black Friday labels

The user reported the installed candidate working correctly. A subsequent read-only browser check of https://env-bourginicom-premium.kinsta.cloud/blackfriday/ confirmed the shared label-row markup and active grid sizing.

- All 16 product cards, including both featured and full-deal grids, were measured at viewport widths 320, 390, 768 and 1440px. Nine custom labels were present at each width.
- Across these 64 card renderings: zero badge/custom-label intersections, zero clipped badges or labels, zero horizontal card overflow, and zero page horizontal overflow.
- The campaign labels currently use bottom-left; discount labels remain top-left. Desktop, mobile and tablet screenshots showed readable text and separate placement.
- Evidence: dist/blackfriday-staging-mobile-2026-10-06.jpg and dist/blackfriday-staging-tablet-2026-10-06.jpg.
- Scope: the current Black Friday frontend layout is accepted. This check does not cover all other selectable label positions, Elementor editor controls, Gallery, Upsells or Related on staging. No site settings, product data or plugin files were changed. The temporary viewport override was reset.


## 2026-10-06 — Final release 2.9.0 promotion

The maintainer approved promotion after the Black Friday staging check. Every plugin file was compared against the preserved 2.9.0-rc.1 ZIP; after normalizing the version string all files match. No runtime, stylesheet, control or template change was introduced during promotion.

Local release checks passed: syntax for all 25 plugin PHP files with PHP 8.3.35, WordPress security sniffs, PHP 7.4+ compatibility sniffs, both standalone regression scripts, consistent 2.9.0 metadata and clean diff whitespace. CI separately checks PHP 7.4/8.3, Composer configuration/audit and WordPress Plugin Check.

This promotion relies on the accepted candidate's targeted staging evidence above. Unchecked editor, debug-log and other widget scenarios remain explicitly outside that check; the final version has not been installed on production by this task.
