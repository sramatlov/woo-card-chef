# Development Guide — Woo Card Chef

Deze handleiding beschrijft hoe een wijziging veilig van idee naar testbare pluginversie gaat. Technische detailinformatie staat in [`TECHNICAL_SPEC.md`](TECHNICAL_SPEC.md); bindende codeafspraken staan in [`CONVENTIONS.md`](CONVENTIONS.md).

## Lokale benodigdheden

- Een lokale of staging WordPress-installatie.
- WordPress 6.0 of hoger.
- PHP 7.4 of hoger; test bij voorkeur ook op een ondersteunde 8.x-versie.
- WooCommerce 6.0 of hoger.
- Elementor 3.5.0 of hoger.
- ACF Free en ACF Pro voor tests van alle contentpaden.
- Optioneel: Lipscore en WBW Product Filter PRO voor integratietests.
- PowerShell en PHP CLI voor lokale validatie.

Er hoeft geen Composer- of npm-installatie te worden uitgevoerd. De repository bevat geen gegenereerde frontendbundels.

## Werkmap aansluiten op WordPress

Gebruik `wc-product-card-elementor/` als pluginmap. Plaats of koppel deze map onder:

```text
wp-content/plugins/wc-product-card-elementor/
```

Activeer daarna Woo Card Chef in WordPress. Voor volledige dekking zijn minimaal twee Elementor Theme Builder-templates nodig:

1. Een Product Archive-template met de Product Card Grid-widget.
2. Een Single Product-template met de zeven PDP-widgets of een representatieve selectie daarvan.

Gebruik testproducten met eenvoudige en variabele prijzen, sale- en niet-saleprijzen, verschillende voorraadstatussen, productafbeeldingen, video, USPs, FAQ, handleiding, upsells en cross-sells.

## Projectstructuur

```text
wc-product-card-elementor/
├── wc-product-card-elementor.php   Pluginheader, versie, constants en bootstrap
├── uninstall.php                   Opschonen van bijgehouden querytransients
├── readme.txt                      WordPress-metadata en changelog
├── includes/
│   ├── class-plugin.php            Registratie van helpers, assets, ACF en widgets
│   ├── class-assets.php            Centrale registratie van CSS en JavaScript
│   ├── class-acf-fields.php        Zes lokale ACF-veldgroepen
│   ├── Helpers/                    Gedeelde stateless logica en card renderer
│   └── Widgets/                    Acht Elementor-widgetklassen
├── assets/css/                     Een stylesheet per widget
├── assets/js/                      Gallery- en Accordion-interactie
└── templates/card.php              Gedeelde productkaartpartial
```

## Uitvoeringsmodel

1. `wc-product-card-elementor.php` valideert de omgeving, definieert constants en start `WC_Product_Card_Elementor_Plugin`.
2. `class-plugin.php` laadt helpers, registreert assets en ACF-velden en registreert acht widgets bij Elementor.
3. Een widget haalt de huidige WooCommerce-context en gevalideerde Elementor-instellingen op.
4. Belangrijke commerce-inhoud wordt server-side opgebouwd.
5. Elementor laadt alleen de CSS-/JS-handles die de widget via `get_style_depends()` en `get_script_depends()` declareert.
6. Productlijstwidgets delegeren kaartberekening en template-output aan `WCPCE_Card_Renderer`.

## Wijzigingsworkflow

1. Bepaal welke widget, helper, template en assets geraakt worden.
2. Lees de relevante secties in `TECHNICAL_SPEC.md`, `CONVENTIONS.md`, `DECISIONS_LOG.md` en `KNOWN_ISSUES.md`.
3. Houd de wijziging klein en behoud bestaande Elementor-controlnamen; opgeslagen templates verwijzen naar die keys.
4. Valideer alle instellingen op de PHP rendergrens. Escape op het laatst mogelijke uitvoerpunt.
5. Voeg JavaScript alleen toe voor echte interactie en behoud server-rendered inhoud als no-JS-basis.
6. Voer de tests in [`TESTING.md`](TESTING.md) uit.
7. Werk relevante documentatie en het changelog in `readme.txt` bij.
8. Verhoog pas bij een release de drie verplichte versiewaarden: pluginheader, `WCPCE_VERSION` en `Stable tag`.
9. Bouw uitsluitend via het meegeleverde buildscript en volg de releasechecklist.

## Versiecontract

Deze waarden moeten identiek zijn:

- `Version:` in `wc-product-card-elementor/wc-product-card-elementor.php`;
- `WCPCE_VERSION` in hetzelfde bestand;
- `Stable tag:` in `wc-product-card-elementor/readme.txt`.

Werk bij iedere gedragswijziging ook bij:

- het changelog in `readme.txt`;
- de actuele/recent-release-sectie in `ROADMAP.md`;
- `TECHNICAL_SPEC.md` wanneer structuur, API of gedrag verandert;
- `GLOSSARY.md` voor nieuwe termen, velden, helpers of releases;
- `KNOWN_ISSUES.md` wanneer een beperking ontstaat of wordt opgelost;
- `DECISIONS_LOG.md` wanneer een architectuurkeuze wordt gemaakt of vervangen.

## ACF en contentmodellen

De plugin registreert zes veldgroepen. De twee door de plugin geregistreerde repeaters (`pdp_usps` en `pdp_gallery_videos`) vereisen ACF Pro. `product_faq` is een derde, extern beheerde repeater: Woo Card Chef registreert hem bewust niet en leest hem alleen. Verander bestaande veldnamen of keys niet zonder een migratieplan; productdata is eraan gekoppeld.

De Accordion gebruikt voor handleidingen deze prioriteit:

1. ACF-bestand `product_manual`.
2. Automatische PDF-match in de geconfigureerde WordPress-root-relatieve manualsmap.

De automatische match gebruikt SKU en filterbare MPN-meta-keys. Houd bestandsnamen stabiel en test producten met en zonder trailing nullen in het artikelnummer.

## Compatibiliteit en regressierisico's

- Verander productcontextresolutie niet zonder tests met andere productloops op dezelfde PDP.
- Maak `get_script_depends()` nooit afhankelijk van `get_settings_for_display()`; Elementor kan deze methode te vroeg aanroepen.
- Behoud WooCommerce-prijs- en zichtbaarheids-API's in plaats van directe metaqueries waar het project dat al doet.
- De card overlay-link, Lipscore en action button hebben een bewust opgebouwde focus-/linkstructuur.
- De Gallery en Accordion hebben progressieve-enhancement- en focusregels die samen met hun JavaScript moeten worden getest.
- Manual-mode pagination gebruikt bewust `wcpce_paged` en een path-only basis-URL.

## Definitie van klaar

Een wijziging is klaar wanneer:

- alle PHP-bestanden zonder syntaxfouten linten;
- de relevante smoke- en regressietests zijn uitgevoerd;
- desktop, tablet en mobiel geen onverwachte layoutwijzigingen tonen;
- keyboard-, focus- en screenreader-attributen intact zijn;
- zowel normale frontend als Elementor-editor/preview werken;
- lege, ontbrekende en optionele databronnen veilig afhandelen;
- changelog en relevante projectdocumentatie overeenkomen met de code;
- een install- en update-test met de gevalideerde zip is geslaagd.

## Geen onderdeel van Woo Card Chef

- Product-, Offer-, Breadcrumb- en reviewschema-eigenaarschap.
- Een custom Add to Cart-flow.
- Site-specifieke WBW/Elementor sticky-headercorrecties.
- Een eigen analyticsdashboard.
- Exacte verzendkosten, kalendergestuurde leverdatums of countdown-urgentie.
