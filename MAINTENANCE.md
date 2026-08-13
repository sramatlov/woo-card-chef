# Onderhouds- en releasebeleid

Dit document bepaalt hoe Woo Card Chef actueel, testbaar en herstelbaar blijft. De uitvoerbare stappen staan in [`WORDPRESS_PLUGIN_RELEASE_CHECKLIST.md`](WORDPRESS_PLUGIN_RELEASE_CHECKLIST.md).

## Onderhoudsritme

| Moment | Actie |
|---|---|
| Iedere pull request | Verplichte syntax-, security-, compatibiliteits-, Plugin Check-, metadata- en ZIP-controles |
| Wekelijks | Dependabot beoordeelt GitHub Actions en Composer-tooling; security-PR's krijgen voorrang |
| Maandelijks | Controleer actuele stabiele WordPress-, WooCommerce-, Elementor- en ondersteunde PHP-versies en test relevante updates op staging |
| Bij securityadvies | Beoordeel direct de impact op Woo Card Chef en de staging-/productieomgeving |
| Voor iedere release | Doorloop de releasechecklist en bewaar een rollbackartefact |

Automatische updates mogen nooit rechtstreeks buiten een pull request naar `main`. Een Dependabot-PR wordt pas samengevoegd nadat de verplichte controles groen zijn.

## Prioriteiten

| Risico | Streeftermijn |
|---|---|
| Kritiek of actief misbruikt | Dezelfde werkdag triëren; zo snel mogelijk op staging testen en na akkoord uitrollen |
| Hoog | Binnen zeven dagen beoordelen en oplossen of gemotiveerd uitstellen |
| Middel/laag | Bundelen in de maandelijkse onderhoudsronde |
| Reguliere compatibiliteitsupdate | Plannen op basis van impact en eerst op staging testen |

Een uitstel wordt in de betreffende PR of issue vastgelegd met risico, tijdelijke maatregel en nieuwe beoordelingsdatum.

## Compatibiliteitsbeleid

- De minimumversies in de pluginheader veranderen alleen bewust en worden als mogelijke breaking change behandeld.
- `Tested up to`-velden worden pas verhoogd nadat de betreffende combinatie daadwerkelijk op staging is getest.
- WordPress, WooCommerce en Elementor worden afzonderlijk bijgewerkt wanneer een gecombineerde update de foutoorzaak moeilijk herleidbaar maakt.
- PHP-compatibiliteit blijft minimaal 7.4 zolang de pluginheader dat verklaart; CI controleert PHP 7.4 en een actuele PHP 8.x-versie.

## Wijzigings- en releasepad

1. Maak een afgebakende branch en pull request.
2. Werk bij een release de pluginheader, `WCPCE_VERSION`, `Stable tag` en het changelog synchroon bij.
3. Los alle verplichte CI-fouten en open reviewgesprekken op.
4. Merge via squash naar `main`; directe pushes, force-pushes en verwijderen van `main` zijn geblokkeerd.
5. Gebruik uitsluitend de installatiezip uit de geslaagde `main`-workflow of bouw exact dezelfde bron met het Python-buildscript.
6. Installeer hetzelfde artefact eerst op staging en voer de relevante regressietests uit.
7. Plaats pas na stagingakkoord exact hetzelfde artefact op productie.
8. Noteer versie, commit, CI-run, stagingresultaat en productiedatum bij de release.

## Minimale stagingcontrole

- Plugin activeert zonder fatal, nieuwe PHP-warning of adminfout.
- Alle acht Elementor-widgets blijven beschikbaar.
- Een representatief productarchief en rijk productdetail renderen op desktop en mobiel.
- Product Gallery, Accordion, prijzen, voorraad/levering, upsells en related/cross-sells werken.
- Elementor-editor en preview blijven bruikbaar.
- Browserconsole en WordPress-debuglog bevatten geen nieuwe Woo Card Chef-fouten.
- Een update over de vorige productieversie behoudt templates, instellingen en ACF-data.

De volledige matrix staat in [`TESTING.md`](TESTING.md).

## Rollback

- Maak vóór productieplaatsing een herstelpunt van bestanden en database.
- Bewaar de vorige bekende goede pluginzip naast de nieuwe release.
- Herstel bij regressie de vorige pluginzip of het hostingherstelpunt.
- Voer na rollback opnieuw de minimale smoke test uit.
- Documenteer oorzaak, impact en vervolgactie voordat een nieuwe release wordt geprobeerd.

Woo Card Chef voert momenteel geen eigen databasemigraties uit. Verwijder of herschrijf tijdens rollback geen WooCommerce-, Elementor- of ACF-data.
