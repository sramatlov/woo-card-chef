# Woo Card Chef

[![Validate and build plugin zip](https://github.com/sramatlov/woo-card-chef/actions/workflows/validate-and-build.yml/badge.svg?branch=main)](https://github.com/sramatlov/woo-card-chef/actions/workflows/validate-and-build.yml)

Woo Card Chef is een custom WordPress-plugin met acht Elementor-widgets voor WooCommerce-productkaarten, productarchieven en productdetailpagina's. De plugin is primair gebouwd voor Bourgini.com en houdt productinhoud in WooCommerce/ACF en presentatie in Elementor.

**Huidige versie:** 2.6.10

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

| Component | Minimum | Getest tot en met volgens pluginmetadata |
|---|---:|---:|
| WordPress | 6.0 | 7.0.4 |
| PHP | 7.4 | 8.3 via CI |
| WooCommerce | 6.0 | 11.0.1 |
| Elementor | 3.5.0 | 4.2.2 |
| Elementor Pro | Niet vereist | 4.2.1 |
| ACF / ACF Pro | Optioneel | Zie hieronder |

Elementor en WooCommerce zijn harde afhankelijkheden. ACF is optioneel, maar zonder ACF blijven de productkaartvelden en de expliciete `product_manual`-bron leeg; de automatische PDF-handleidingfallback kan wel blijven werken. ACF Pro is nodig voor de repeaters `pdp_usps` en `pdp_gallery_videos`. Lipscore is optioneel en vult alleen de aanwezige rating-/reviews placeholders.

## Installeren

1. Download het installatieartefact van een geslaagde `main`-workflow of bouw de zip lokaal.
2. Installeer de zip via **Plugins > Nieuwe plugin > Plugin uploaden**.
3. Activeer WooCommerce en Elementor en daarna Woo Card Chef.
4. Voeg de gewenste widgets uit de Elementor-categorie **Woo Card Chef** toe aan een Product Archive- of Single Product-template.
5. Activeer ACF of ACF Pro wanneer de bijbehorende contentvelden nodig zijn.

De volledige gebruikersinstallatie en WBW Product Filter PRO-configuratie staan in [`wc-product-card-elementor/readme.txt`](wc-product-card-elementor/readme.txt).

## Ontwikkelen en lokaal controleren

PHP, CSS en JavaScript worden rechtstreeks uit `wc-product-card-elementor/` geladen. Composer beheert uitsluitend de ontwikkeltools; er worden geen Composer-packages in de pluginzip opgenomen.

```powershell
composer install
composer validate --strict --no-check-publish
composer audit --locked
composer check
python tools/validate_plugin_metadata.py --plugin-dir wc-product-card-elementor --main-file wc-product-card-elementor.php
python tools/build_wordpress_plugin_zip.py --source-dir wc-product-card-elementor --destination-zip dist/woo-card-chef-v2.6.10-wordpress-install.zip --plugin-slug wc-product-card-elementor --main-file wc-product-card-elementor.php
```

Gebruik daarnaast [`TESTING.md`](TESTING.md) voor de handmatige regressiematrix en [`WORDPRESS_PLUGIN_RELEASE_CHECKLIST.md`](WORDPRESS_PLUGIN_RELEASE_CHECKLIST.md) voor iedere release.

## CI en repositorybeveiliging

Iedere pull request en push naar `main` wordt gecontroleerd op:

- PHP-syntax met PHP 7.4 en 8.3;
- bekende kwetsbaarheden in de gelockte Composer-tooling;
- WordPress-securitysniffs;
- PHP 7.4+-compatibiliteit;
- stabiele WordPress Plugin Check-regels voor algemene, security- en performanceproblemen;
- consistente pluginmetadata en een WordPress-veilige installatiezip.

`main` accepteert alleen pull requests met alle verplichte controles groen. GitHub Dependabot, vulnerability alerts, automatische security-updates, secret scanning en secret push protection zijn ingeschakeld.

## Documentatie-index

| Document | Doel |
|---|---|
| [`SECURITY.md`](SECURITY.md) | Ondersteunde versies en vertrouwelijk melden van kwetsbaarheden |
| [`MAINTENANCE.md`](MAINTENANCE.md) | Updatefrequentie, prioriteiten, releasebeleid en rollback |
| [`PROJECT_OVERVIEW.md`](PROJECT_OVERVIEW.md) | Productdoel, doelgroep en functionele widgetbeschrijvingen |
| [`TECHNICAL_SPEC.md`](TECHNICAL_SPEC.md) | Afhankelijkheden, architectuur, klassen, methodes en controls |
| [`DEVELOPMENT.md`](DEVELOPMENT.md) | Lokale ontwikkelworkflow en definitie van klaar |
| [`TESTING.md`](TESTING.md) | Automatische controles en handmatige regressietests |
| [`CONVENTIONS.md`](CONVENTIONS.md) | Code- en implementatieafspraken |
| [`DECISIONS_LOG.md`](DECISIONS_LOG.md) | Architectuurbesluiten en afgewezen alternatieven |
| [`KNOWN_ISSUES.md`](KNOWN_ISSUES.md) | Bekende beperkingen, bewuste compromissen en open punten |
| [`ROADMAP.md`](ROADMAP.md) | Afgeronde releases en geplande verbeteringen |
| [`GLOSSARY.md`](GLOSSARY.md) | Projecttermen, velden, helpers en versiehistorie |
| [`WORDPRESS_PLUGIN_RELEASE_CHECKLIST.md`](WORDPRESS_PLUGIN_RELEASE_CHECKLIST.md) | Uitvoerbare release-, staging- en rollbackchecklist |
| [`wc-product-card-elementor/readme.txt`](wc-product-card-elementor/readme.txt) | WordPress-pluginmetadata, installatie, FAQ en changelog |

## Belangrijke architectuurregels

- Commerce-inhoud wordt server-side gerenderd; JavaScript is alleen voor noodzakelijke interactie.
- Widgets halen de huidige productcontext eerst uit `get_queried_object()` en daarna pas uit `global $product`.
- Assets worden centraal geregistreerd en alleen geladen via de dependencies van een aanwezige widget.
- De gedeelde productkaart wordt gerenderd via `WCPCE_Card_Renderer` en `templates/card.php`.
- Presentatiewidgets voegen geen eigen Product/Offer-schema toe.
- `get_script_depends()` blijft statisch en leest geen widgetinstellingen.

## Status en beperkingen

Er is nog geen geautomatiseerde browser- of visual-regression-suite. Frontend-, Elementor-, WooCommerce- en toegankelijkheidscontrole blijven daarom onderdeel van de stagingtest. Zie [`KNOWN_ISSUES.md`](KNOWN_ISSUES.md) voor geaccepteerde beperkingen.

## Licentie

GPL v2 or later. Zie de pluginheader in `wc-product-card-elementor/wc-product-card-elementor.php`.
