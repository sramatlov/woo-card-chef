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
- Elementor toont de categorie **Woo Card Chef** en alle negen widgets.
- Frontend en Elementor preview gebruiken het juiste huidige product.
- Pagina's zonder Woo Card Chef-widget laden geen onnodige widgetassets.
- Browserconsole bevat geen nieuwe JavaScriptfouten.
- PHP-/WordPress-debuglog bevat geen nieuwe warnings, notices of deprecations.
- Alle shopperteksten zijn correct vertaald of hebben een Nederlandse standaardwaarde.

## 4. Widgetregressiematrix

### Product Card Grid

- Auto mode werkt op categorie-, shop-, zoek- en leeg archief.
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
2. Activeer de plugin en controleer alle negen widgetregistraties.
3. Installeer dezelfde zip als update over de vorige productieversie.
4. Open minimaal één productarchief en één rijk productdetail.
5. Controleer browserconsole en WordPress-debuglog.
6. Bevestig dat opgeslagen Elementor-templates en controlwaarden behouden zijn.
7. Bewaar de vorige werkende zip als rollbackartefact.

Noteer bij een release welke scenario's zijn getest, in welke browser(s), met welke pluginversies en op welke omgeving.
