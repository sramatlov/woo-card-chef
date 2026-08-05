# Woo Card Chef

Woo Card Chef is een custom WordPress-plugin met acht Elementor-widgets voor WooCommerce-productkaarten, productarchieven en productdetailpagina's. De plugin is primair gebouwd voor Bourgini.com en houdt productinhoud in WooCommerce/ACF en presentatie in Elementor.

**Huidige versie:** 2.6.9

**Pluginmap:** `wc-product-card-elementor/`

**GitHub:** [sramatlov/woo-card-chef](https://github.com/sramatlov/woo-card-chef)

## Widgets

| Widget | Gebruik |
|---|---|
| Product Card Grid | Productkaarten op categorie-, archief- en landingspagina's |
| Product Gallery | Afbeeldingen, YouTube-video's, thumbnails, badges en lightbox op de PDP |
| Product Price & Promo | Reguliere/saleprijs, kortingschip, besparing en variabele prijzen |
| Product USP / Benefits | Korte productvoordelen uit ACF of WooCommerce-fallbacks |
| Product Delivery & Availability | Voorraadstatus, leverbelofte en gratis-verzenddrempel |
| Product Accordion | Beschrijving, specificaties, reviews, FAQ en handleiding |
| Product Upsells | Handmatig gekoppelde WooCommerce-upsells als productkaarten |
| Product Cross-sells / Related | Cross-sells met Related Products als fallback |

## Vereisten

| Component | Minimum | Getest tot en met |
|---|---:|---:|
| WordPress | 6.0 | 6.7 |
| PHP | 7.4 | 8.2 |
| WooCommerce | 6.0 | 10.7 |
| Elementor | 3.5.0 | 4.1.0 |
| Elementor Pro | Niet vereist door de plugin | 4.0.4 |
| ACF / ACF Pro | Optioneel | Zie hieronder |

Elementor en WooCommerce zijn harde afhankelijkheden. ACF is optioneel, maar zonder ACF blijven de productkaartvelden en de expliciete `product_manual`-bron leeg; de automatische PDF-handleidingfallback kan wel blijven werken. ACF Pro is nodig voor de repeaters `pdp_usps` en `pdp_gallery_videos`. Lipscore is optioneel en vult alleen de aanwezige rating-/reviews placeholders.

## Installeren

1. Installeer een gevalideerde `woo-card-chef-vX.Y.Z-install.zip` via **Plugins > Nieuwe plugin > Plugin uploaden**, of plaats `wc-product-card-elementor/` in `wp-content/plugins/`.
2. Activeer WooCommerce en Elementor.
3. Activeer Woo Card Chef.
4. Voeg de gewenste widgets uit de Elementor-categorie **Woo Card Chef** toe aan een Product Archive- of Single Product-template.
5. Activeer ACF of ACF Pro wanneer de bijbehorende contentvelden nodig zijn.

De volledige gebruikersinstallatie en WBW Product Filter PRO-configuratie staan in [`wc-product-card-elementor/readme.txt`](wc-product-card-elementor/readme.txt).

## Ontwikkelen en testen

De plugin heeft geen Composer- of npm-buildstap. PHP, CSS en JavaScript worden rechtstreeks uit `wc-product-card-elementor/` geladen.

- Lees [`DEVELOPMENT.md`](DEVELOPMENT.md) voor de lokale workflow, architectuur en wijzigingsregels.
- Gebruik [`TESTING.md`](TESTING.md) voor syntaxcontrole en de handmatige regressiematrix.
- Volg [`WORDPRESS_PLUGIN_RELEASE_CHECKLIST.md`](WORDPRESS_PLUGIN_RELEASE_CHECKLIST.md) voor iedere release.
- Houd [`CONVENTIONS.md`](CONVENTIONS.md) aan voor code, escaping, Elementor, ACF en frontendpatronen.

Snelle PHP-syntaxcontrole vanuit PowerShell:

```powershell
Get-ChildItem .\wc-product-card-elementor -Recurse -Filter *.php | ForEach-Object {
    php -l $_.FullName
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}
```

## Documentatie-index

| Document | Doel |
|---|---|
| [`PROJECT_OVERVIEW.md`](PROJECT_OVERVIEW.md) | Productdoel, doelgroep en functionele widgetbeschrijvingen |
| [`TECHNICAL_SPEC.md`](TECHNICAL_SPEC.md) | Afhankelijkheden, architectuur, klassen, methodes en controls |
| [`DEVELOPMENT.md`](DEVELOPMENT.md) | Lokale ontwikkelworkflow en definitie van klaar |
| [`TESTING.md`](TESTING.md) | Syntax-, smoke-, regressie- en toegankelijkheidstests |
| [`CONVENTIONS.md`](CONVENTIONS.md) | Code- en implementatieafspraken |
| [`DECISIONS_LOG.md`](DECISIONS_LOG.md) | Architectuurbesluiten en afgewezen alternatieven |
| [`KNOWN_ISSUES.md`](KNOWN_ISSUES.md) | Bekende beperkingen, bewuste compromissen en open punten |
| [`ROADMAP.md`](ROADMAP.md) | Afgeronde releases en geplande verbeteringen |
| [`GLOSSARY.md`](GLOSSARY.md) | Projecttermen, velden, helpers en versiehistorie |
| [`WORDPRESS_PLUGIN_RELEASE_CHECKLIST.md`](WORDPRESS_PLUGIN_RELEASE_CHECKLIST.md) | Versiebeheer, build, validatie, staging en rollback |
| [`wc-product-card-elementor/readme.txt`](wc-product-card-elementor/readme.txt) | WordPress-pluginmetadata, installatie, FAQ en changelog |

## Belangrijke architectuurregels

- Commerce-inhoud wordt server-side gerenderd; JavaScript is alleen voor noodzakelijke interactie.
- Widgets halen de huidige productcontext eerst uit `get_queried_object()` en daarna pas uit `global $product`.
- Assets worden centraal geregistreerd en alleen geladen via de dependencies van een aanwezige widget.
- De gedeelde productkaart wordt gerenderd via `WCPCE_Card_Renderer` en `templates/card.php`.
- Presentatiewidgets voegen geen eigen Product/Offer-schema toe.
- `get_script_depends()` blijft statisch en leest geen widgetinstellingen.

## Status en beperkingen

Er is nog geen geautomatiseerde functionele test-suite. PHP-syntaxcontrole en de WordPress-zipvalidator zijn automatiseerbaar; frontend-, Elementor-, WooCommerce- en toegankelijkheidscontrole blijven handmatig. Zie [`KNOWN_ISSUES.md`](KNOWN_ISSUES.md) voor geaccepteerde beperkingen, waaronder YouTube-thumbnailprivacy en enkele merkgebonden standaardkleuren.

## Licentie

GPL v2 or later. Zie de pluginheader in `wc-product-card-elementor/wc-product-card-elementor.php`.
