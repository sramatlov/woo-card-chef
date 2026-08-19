# Woo Card Chef release checklist

Gebruik deze checklist voor iedere patch-, minor- en majorrelease. Een release is pas klaar nadat versiegegevens, syntax, install-zip, updatepad en frontend-smoke-test zijn gecontroleerd.

## 1. Releaseomvang bepalen

- **Patch** (`2.6.9` → `2.6.10`): bugfix of kleine compatibiliteits-/documentatiecorrectie zonder nieuwe editorworkflow.
- **Minor** (`2.6.x` → `2.7.0`): nieuwe functionaliteit of controls die backwards-compatible zijn.
- **Release candidate** (`2.7.1-rc.1` → `2.7.1-rc.2`): iedere nieuwe gedeelde stagingbuild krijgt een uniek oplopend RC-nummer. Het basisnummer moet hoger zijn dan de laatst geïnstalleerde definitieve versie; de definitieve `2.7.1` volgt pas na acceptatie.
- **Major** (`2.x` → `3.0.0`): breaking change, datamigratie of bewust incompatibele architectuurwijziging.

Leg de gebruikersimpact en regressierisico's vast voordat de versie wordt verhoogd.

## 2. Versie en documentatie synchroniseren

De volgende drie waarden moeten exact gelijk zijn:

1. `Version:` in `wc-product-card-elementor/wc-product-card-elementor.php`.
2. `WCPCE_VERSION` in hetzelfde bestand.
3. `Stable tag:` in `wc-product-card-elementor/readme.txt`.

Controleer vanuit de projectroot:

```powershell
rg -n "Version:|WCPCE_VERSION|Stable tag" `
  .\wc-product-card-elementor\wc-product-card-elementor.php `
  .\wc-product-card-elementor\readme.txt
```

Werk daarnaast bij:

- het changelog in `wc-product-card-elementor/readme.txt`;
- `ROADMAP.md` (current version en recente release);
- `TECHNICAL_SPEC.md` bij structuur-, API- of gedragswijzigingen;
- `GLOSSARY.md` voor releasehistorie en nieuwe termen;
- `KNOWN_ISSUES.md` voor nieuwe of opgeloste beperkingen;
- `DECISIONS_LOG.md` wanneer een architectuurkeuze verandert.

## 3. Statische controle

Lint alle PHP-bestanden:

```powershell
Get-ChildItem .\wc-product-card-elementor -Recurse -Filter *.php | ForEach-Object {
    php -l $_.FullName
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}
```

Controleer vervolgens op achtergebleven debugcode en verouderde versieverwijzingen:

```powershell
rg -n "var_dump|print_r|console\.log|TODO|FIXME" .\wc-product-card-elementor
rg -n "2\.6\.[0-9]+" README.md PROJECT_OVERVIEW.md TECHNICAL_SPEC.md ROADMAP.md GLOSSARY.md
```

Beoordeel iedere match; changelog- en historie-verwijzingen mogen vanzelfsprekend ouder zijn.

Voer ook de gelockte security- en compatibiliteitscontroles uit:

```powershell
composer install
composer validate --strict --no-check-publish
composer audit --locked
composer check
python tools/validate_plugin_metadata.py --plugin-dir wc-product-card-elementor --main-file wc-product-card-elementor.php
```

## 4. Regressietest uitvoeren

Volg [`TESTING.md`](TESTING.md). Minimaal verplicht:

- pluginactivatie;
- één Product Archive met Auto mode en een leeg resultaat;
- één rijk productdetail met Gallery, prijs, USP, delivery en Accordion;
- Upsells en Cross-sells/Related;
- Elementor editor/preview;
- keyboardbediening van Gallery en Accordion;
- browserconsole en WordPress-debuglog.

## 5. WordPress-veilige install-zip bouwen

Gebruik bij voorkeur de installatiezip uit een geslaagde `main`-workflow. Voor een lokale controle voer je vanuit de projectroot uit en pas je alleen de versie in de doelnaam aan:

```powershell
python tools/build_wordpress_plugin_zip.py `
  --source-dir wc-product-card-elementor `
  --destination-zip dist/woo-card-chef-v2.7.1-wordpress-install.zip `
  --plugin-slug wc-product-card-elementor `
  --main-file wc-product-card-elementor.php
```

Gebruik nooit `Compress-Archive`. Dat kan Windows-backslashes in zipentries zetten, waardoor WordPress de plugin niet als normale update/vervanging behandelt.

De validator moet bevestigen:

- `PluginSlug`: `wc-product-card-elementor`;
- `MainFile`: `wc-product-card-elementor/wc-product-card-elementor.php`;
- `BackslashEntries`: `0`;
- exact één rootmap: `wc-product-card-elementor/`.

Fout patroon:

```text
wc-product-card-elementor\wc-product-card-elementor.php
```

Correct patroon:

```text
wc-product-card-elementor/wc-product-card-elementor.php
```

## 6. Installatie- en updatetest

Test op staging met een database- en bestandenbackup:

1. Installeer de zip op een schone WordPress-installatie.
2. Activeer de plugin en controleer de negen Elementor-widgets.
3. Installeer dezelfde zip als update over de vorige productieversie.
4. Controleer dat opgeslagen Elementor-templates, ACF-data en widgetcontrols behouden blijven.
5. Open een productarchief en een rijk productdetail op desktop en mobiel.
6. Controleer browserconsole en WordPress-debuglog.

## 7. Releaseartefacten en rollback

- Bewaar de nieuwe gevalideerde zip buiten de tijdelijke GitHub-retentieperiode.
- Bewaar de vorige productiezip als rollbackartefact.
- Noteer releaseversie, datum, geteste omgeving, browsers en uitgevoerde scenario's.
- Maak geen release wanneer een verplichte test niet is uitgevoerd of een nieuwe fatal, warning of console-error aanwezig is.

Rollback bestaat uit het terugplaatsen/installeren van de vorige werkende pluginzip en het opnieuw uitvoeren van de minimale smoke test. De plugin heeft geen eigen databasemigraties, maar verwijder of wijzig ACF-/Elementor-data niet tijdens rollback.

## 8. GitHub en productie

1. Publiceer de wijziging als pull request naar `main`.
2. Controleer dat PHP 7.4/8.3 syntax, security/compatibility, WordPress Plugin Check en metadata/ZIP-build groen zijn.
3. Los alle open reviewgesprekken op en squash-merge de pull request.
4. Wacht op de definitieve geslaagde `main`-workflow.
5. Download het installatieartefact uit die workflow en gebruik exact dat bestand voor staging en productie.
6. Leg versie, mergecommit, workflowlink, stagingresultaat en uitroldatum vast.

Directe pushes, force-pushes en verwijderen van `main` zijn geblokkeerd. Zie [`MAINTENANCE.md`](MAINTENANCE.md) voor prioriteiten en onderhoudsfrequentie en [`SECURITY.md`](SECURITY.md) voor kwetsbaarheidsmeldingen.
