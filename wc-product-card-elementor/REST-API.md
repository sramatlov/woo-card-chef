# Woo Card Chef REST API — 2.10.0-rc.2

Deze kandidaat voegt besloten beheer toe onder `/wp-json/wcpce/v1`.
Installeer de ZIP zelf op staging; deze versie is nog geen stagingacceptatie.
De taxonomie blijft privé. `/wp/v2/wcpce_product_label` blijft bewust afwezig.

## Toegang

WordPress verzorgt de authenticatie. Gebruik voor een externe client HTTPS,
je WordPress-gebruikersnaam en een WordPress-applicatiewachtwoord via Basic
Auth. De plugin beheert geen wachtwoorden. Alle gegevensroutes vereisen
`manage_options` én `manage_woocommerce`. Productroutes vereisen bovendien
`edit_post` voor het betreffende product. Een standaard winkelmanager heeft
dus geen toegang. De bestaande wp-admin-rechten blijven gelijk.

De nieuwe velden worden niet toegevoegd aan openbare WordPress-, WooCommerce-
of Store API-responses. De bestaande ruwe `meta_data`-mogelijkheden van de
beveiligde WooCommerce API worden niet vervangen of aangepast.

## Routes

| Methode | Pad na `/wp-json/wcpce/v1` | Gebruik |
|---|---|---|
| GET | `/labels?page=1&per_page=20&search=Campagne` | Labels, ook ongebruikte, inactieve en geplande; maximaal 100 per pagina |
| POST | `/labels` | Nieuw label; `name` verplicht |
| GET | `/labels/{id}` | Eén labeldefinitie |
| PATCH | `/labels/{id}` | Alleen aangeboden instellingen wijzigen |
| GET | `/products/{id}` | Opgeslagen pluginvelden en alle gekoppelde label-ID's |
| PATCH | `/products/{id}` | Pluginvelden en/of labelkoppelingen wijzigen |
| GET | `/schema` | Veldtypen, limieten, posities, sitezone en niet-beschikbare velden |

`GET /labels` geeft `X-WP-Total` en `X-WP-TotalPages` terug. GET en PATCH
geven de actuele beheerdata terug, onafhankelijk van frontendzichtbaarheid
of het maximum aantal zichtbare labels. Een POST geeft HTTP 201 en Location.
Privégegevensresponses hebben `Cache-Control: private, no-store`.

## Schrijfcontract

Verstuur een JSON-object met `Content-Type: application/json`.

- Niet aangeboden eigenschappen blijven ongewijzigd. Stuur geen `id` of
  `available_fields` uit een GET-response terug als schrijfveld.
- Tekst wissen: `""`. Media wissen: `null`. Een lijst wissen: `[]`.
- Repeaters en `label_ids` vervangen steeds de volledige betreffende lijst.
  Lees eerst de actuele toestand; er is geen locking- of versieprotocol.
- JSON-booleans zijn `true`/`false`, geen strings of `0`/`1`.
- Afbeeldingen en PDF's zijn bestaande media-ID's, geen URL's of ACF-arrays.
  Upload nieuwe bestanden eerst via de bestaande `/wp/v2/media`-API.
- Alle invoer wordt vóór de eerste write gecontroleerd. Een validatiefout
  (HTTP 400) noemt de velden onder `data.params` en schrijft niets.
- Dubbele labelnamen geven HTTP 409. Lees/zoek het bestaande label; overschrijf
  de instellingen daarvan niet automatisch bij een herhaalde create.
- Een opslagfout geeft HTTP 500, geen vals succes. Een write met meerdere
  velden is geen database-transactie: lees na een opslagfout de toestand
  opnieuw voordat je een correctie of retry uitvoert.
- Definitief labels verwijderen, producten aanmaken, variaties, bulkbewerkingen,
  externe FAQ-velden en Elementor-templategegevens vallen buiten deze API.
  Een label verbergen kan via `active: false`, loskoppelen via `label_ids`.

Een label bevat `id`, `name`, `color`, `position`, `priority`, `active`,
`visible_from`, `visible_until` en `pdp_details`. Prioriteit loopt van 0–999.
Posities: `top-left`, `top-right`, `bottom-left`, `bottom-right`, `below-image`.
Planning gebruikt `YYYY-MM-DDTHH:mm` in de WordPress-sitezone, of `""` voor een
open grens. De eindminuut telt volledig mee. `pdp_details` wordt met
`wp_kses_post()` opgeschoond, net als in wp-admin.

Een product bevat `id`, `label_ids`, `fields` en `available_fields`.

| Veld onder `fields` | Waarde |
|---|---|
| `card_title` | Tekst, max. 80 tekens |
| `card_hover_image` | Afbeeldings-ID of `null` |
| `usp_1`, `usp_2`, `usp_3` | Tekst, ieder max. 60 tekens |
| `badge_nieuw`, `badge_pfas_vrij`, `badge_niet_leverbaar` | Boolean |
| `pdp_usps` | Max. 8 objecten met `usp_text` (max. 140 tekens) |
| `pdp_gallery_videos` | Max. 10 objecten met verplichte `youtube_url`, optionele `video_title` (max. 120 tekens) en `video_thumbnail` (afbeeldings-ID of `null`) |
| `product_manual` | PDF-attachment-ID of `null` |

De grenzen en ACF-validatiefilters komen uit de geregistreerde velddefinities.
De API schrijft via ACF-field keys en ondersteunt ook de eerste veldwaarde op
een product. De ACF-updatefilters blijven actief; de response bevat de werkelijk
opgeslagen waarden. Het YouTube-veld gebruikt dezelfde ACF-URL-controle als het
productscherm. De bestaande Gallery bepaalt daarna of de URL afspeelbaar is.

Zonder ACF werken labels en labelkoppelingen nog steeds. Niet-beschikbare
productvelden ontbreken in `fields` en `available_fields`; `/schema` vermeldt
ze onder `fields.unavailable_fields`. ACF Free ondersteunt de eenvoudige
velden; `pdp_usps` en `pdp_gallery_videos` vereisen ACF Pro.

## Voorbeelden met curl

Gebruik een stagingtestproduct. Stel `BASE`, `WP_USER` en `WP_APP_PASSWORD`
in je eigen shell in; `BASE` eindigt op `/wp-json/wcpce/v1`. Hieronder staat
`PRODUCT_ID` voor het echte product-ID, `LABEL_ID` voor het ID uit de POST-
response, en staan 200/201 voor te vervangen afbeeldings-/PDF-ID's.

Lees schema, product en labels, en bewaar de oorspronkelijke productwaarden:

```bash
curl --fail-with-body --user "$WP_USER:$WP_APP_PASSWORD" "$BASE/schema"
curl --fail-with-body --user "$WP_USER:$WP_APP_PASSWORD" "$BASE/products/$PRODUCT_ID" > product-before.json
curl --fail-with-body --user "$WP_USER:$WP_APP_PASSWORD" "$BASE/labels?per_page=100"
```

Maak een label aan, noteer het teruggegeven ID en wijzig vervolgens alleen kleur:

```bash
curl --fail-with-body --user "$WP_USER:$WP_APP_PASSWORD" \
  -H 'Content-Type: application/json' -X POST "$BASE/labels" \
  --data '{"name":"REST stagingtest","color":"#B4211C","position":"below-image","priority":10,"active":true,"pdp_details":"<p>Voorwaarden van deze actie.</p>"}'

curl --fail-with-body --user "$WP_USER:$WP_APP_PASSWORD" \
  -H 'Content-Type: application/json' -X PATCH "$BASE/labels/$LABEL_ID" \
  --data '{"color":"#224466"}'
```

Koppel het label. Deze lijst vervangt de oude lijst: neem bestaande label-ID's
mee als je die wilt behouden.

```bash
curl --fail-with-body --user "$WP_USER:$WP_APP_PASSWORD" \
  -H 'Content-Type: application/json' -X PATCH "$BASE/products/$PRODUCT_ID" \
  --data "{\"label_ids\":[$LABEL_ID]}"
```

Schrijf velden en een repeater; vervang media-ID's door echte staging-ID's:

```bash
curl --fail-with-body --user "$WP_USER:$WP_APP_PASSWORD" \
  -H 'Content-Type: application/json' -X PATCH "$BASE/products/$PRODUCT_ID" \
  --data '{"fields":{"card_title":"REST testkaart","card_hover_image":200,"usp_1":"Eerste kaartvoordeel","badge_nieuw":false,"pdp_usps":[{"usp_text":"Eerste PDP-voordeel"},{"usp_text":"Tweede PDP-voordeel"}],"pdp_gallery_videos":[{"youtube_url":"https://www.youtube.com/watch?v=VIDEO_ID","video_title":"Demonstratie","video_thumbnail":200}],"product_manual":201}}'
```

Gebruik voor `VIDEO_ID` een bestaande video die je voor dit testproduct wilt
gebruiken. Een onbekende video kan een geldige URL hebben en toch niet afspelen.

Leeg een repeater en ontkoppel labels:

```bash
curl --fail-with-body --user "$WP_USER:$WP_APP_PASSWORD" \
  -H 'Content-Type: application/json' -X PATCH "$BASE/products/$PRODUCT_ID" \
  --data '{"label_ids":[],"fields":{"pdp_usps":[],"card_hover_image":null}}'
```

Controleer geweigerde toegang zonder credentials (verwacht standaard 401):

```bash
curl -i "$BASE/labels"
curl -i "$BASE/products/$PRODUCT_ID"
curl -i "$BASE/schema"
```

Herhaal met een editor-/winkelmanageraccount (verwacht 403). Controleer ook
dat een ingelogde beheerder met onvoldoende rechten voor het specifieke
product geen toegang krijgt. Globale securityplugins kunnen foutcodes wijzigen.
Een niet-bestaand object geeft een 404 of wordt al in de rechtencontrole geweigerd.

## PowerShell: lees, wijzig gericht en herstel

Werkt ook met Windows PowerShell 5.1. Vul de stagingbasis en een testproduct-ID
in; voer in het credentialvenster het applicatiewachtwoord in.

```powershell
$base = 'https://JOUW-STAGING/wp-json/wcpce/v1'
$productId = 123
$credential = Get-Credential -Message 'WordPress-gebruikersnaam en applicatiewachtwoord'
$pair = '{0}:{1}' -f $credential.UserName, $credential.GetNetworkCredential().Password
$headers = @{ Authorization = 'Basic ' + [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($pair)) }

$schema = Invoke-RestMethod "$base/schema" -Headers $headers
$before = Invoke-RestMethod "$base/products/$productId" -Headers $headers
$before | ConvertTo-Json -Depth 20 | Set-Content 'product-before.json' -Encoding UTF8

$label = Invoke-RestMethod "$base/labels" -Method Post -Headers $headers -ContentType 'application/json; charset=utf-8' -Body '{"name":"REST PowerShell test","active":true}'
$payload = @{
    label_ids = @($before.label_ids) + @($label.id)
    fields = @{ card_title = 'Korte REST testtitel'; badge_nieuw = $false }
} | ConvertTo-Json -Depth 10
Invoke-RestMethod "$base/products/$productId" -Method Patch -Headers $headers -ContentType 'application/json; charset=utf-8' -Body ([Text.Encoding]::UTF8.GetBytes($payload))

# Herstel alleen de velden die deze voorbeeldwrite wijzigde en de oude koppelingen.
$restore = @{
    label_ids = @($before.label_ids)
    fields = @{ card_title = $before.fields.card_title; badge_nieuw = $before.fields.badge_nieuw }
} | ConvertTo-Json -Depth 10
Invoke-RestMethod "$base/products/$productId" -Method Patch -Headers $headers -ContentType 'application/json; charset=utf-8' -Body ([Text.Encoding]::UTF8.GetBytes($restore))
Invoke-RestMethod "$base/labels/$($label.id)" -Method Patch -Headers $headers -ContentType 'application/json' -Body '{"active":false}'
```

## Stagingacceptatie en grenzen

Controleer na iedere succesvolle API-write dezelfde waarden in het product-
of labelscherm en andersom. Test nieuwe en bestaande producten, het inkorten
en leegmaken van repeaters, quotes/backslashes, planning in de sitezone en
een afbeelding die ten onrechte als PDF wordt aangeboden. Een request met
een geldig veld én een ongeldig veld moet niets wijzigen.

Controleer een productarchief en rijke PDP op desktop/mobiel en open de
Elementor-editor; de markup, styles, fallbacks en controls zijn niet gewijzigd.
CSS-regeneratie is voor deze wijziging op zichzelf niet vereist. Controleer
ook PHP-/WordPress-debuglog en de browserconsole.

De productwrite rondt een echte WooCommerce-save met gewijzigde datum af en
leegt de plugin-querycache na opslag. Labelsettings en koppelingen wissen de
labelcache binnen hetzelfde request. Labels wijzigen slaat niet alle
gekoppelde producten opnieuw op. Er is geen nieuwe volledige sitepurge.

Site-snippets die uitsluitend admin-POST/nonces of `acf/save_post` verwachten
worden niet nagebootst. Bekende regels van deze plugin en ACF-veldvalidators
zijn gedekt; aanvullende sitehooks vragen een eigen integratiecontrole.

WP Rocket, hosting/CDN en aparte landingspagina's kunnen gecachte HTML tonen.
Verifieer hun bestaande purgegedrag op staging. De bestaande beperking bij
het passeren van een label-planningsgrens blijft gelden: er is geen automatische
purge op die minuut. Test zowel een ongecachete pagina als een normale pagina.
