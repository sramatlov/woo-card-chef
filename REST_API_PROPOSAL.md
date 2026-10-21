# Voorstel: besloten REST-beheer voor Woo Card Chef

Datum: 21 oktober 2026. Status: ontwerpvastlegging; geïmplementeerd als 2.10.0-rc.2, stagingacceptatie volgt.
Onderzocht: repositorybron op commit `f116d8a`, pluginversie 2.9.1.
Er is geen site benaderd of gewijzigd. De door de gebruiker gemelde staging-404
is verklaard vanuit de bron, niet opnieuw op staging getest.

## Bevindingen

- `includes/class-product-labels.php` registreert `wcpce_product_label` met
  `public => false` en `show_in_rest => false`. Daarom registreert WordPress
  geen `/wp/v2/wcpce_product_label`-route.
- Labelinstellingen worden opgeslagen door nonce-afhankelijke adminhandlers.
  Een standaard REST-controller zou die handlers niet correct kunnen gebruiken.
- Centraal labelbeheer vereist nu `manage_woocommerce`; koppelen vereist
  productbewerkrechten. Winkelmanagers kunnen dus in wp-admin labels beheren.
- De zes lokale ACF-groepen staan in `includes/class-acf-fields.php`. Twee
  repeaters worden alleen geregistreerd wanneer ACF Pro beschikbaar is.
- De plugin leegt zijn querytransients via `save_post_product`. Labelopslag
  wist de eigen cache voor zichtbare labels binnen hetzelfde PHP-request.
  De plugin heeft geen eigen WP Rocket- of Kinsta-purge-integratie.
- `product_faq` met `vraag` en `antwoord` wordt door de Accordion gelezen,
  maar buiten deze plugin geregistreerd. De definitie en validatiefilters
  zijn niet uit deze repository vast te stellen.
- Overige plugininstellingen zijn hoofdzakelijk Elementor-widgetinstellingen.
  Er is geen zelfstandig algemeen instellingenscherm of options-API te ontsluiten.
- Enkele docs noemen nog 2.9.0; de drie verplichte pluginversiewaarden zijn 2.9.1.

## Voorgestelde route

Een eigen WordPress REST API onder `/wp-json/wcpce/v1`, met controllers en
expliciete rechtencontrole op iedere lees- en schrijfoperatie. De private
taxonomie en ACF-groepen blijven buiten de standaard openbare REST-uitvoer.
De bestaande `/wp/v2/wcpce_product_label`-URL blijft bij deze keuze afwezig;
de integratie gebruikt voortaan `/wcpce/v1/labels`.

| Methode | Route binnen `wcpce/v1` | Functie |
|---|---|---|
| GET | `/labels` | Gepagineerd lezen en zoeken, inclusief inactieve/geplande labels |
| POST | `/labels` | Herbruikbaar label aanmaken |
| GET | `/labels/{id}` | Definitie met alle instellingen lezen |
| PATCH | `/labels/{id}` | Alleen opgegeven instellingen wijzigen |
| GET | `/products/{id}` | Pluginvelden en gekoppelde label-ID's lezen |
| PATCH | `/products/{id}` | Pluginvelden wijzigen en labels koppelen/ontkoppelen |
| GET | `/schema` | Veldschema, limieten, posities, sitezone en beschikbare ACF-onderdelen |

Labels krijgen `name`, `color`, `position`, `priority`, `active`,
`visible_from`, `visible_until` en `pdp_details`. Planning gebruikt dezelfde
WordPress-sitezone en inclusieve eindminuut als het beheerscherm. Het schema
beschrijft de vijf bestaande posities. De optionele PDP-toelichting blijft
veilige HTML via `wp_kses_post()`.

Producten krijgen een `fields`-object met bestaande veldnamen en een
`label_ids`-array. Geen nieuwe metavelden of migratie. Afwezige eigenschappen
blijven ongewijzigd; een aangeboden repeater of `label_ids` vervangt de hele
lijst, `[]` leegt deze. Media worden geschreven als attachment-ID of `null`
om te wissen. GET geeft dezelfde schrijfbare vorm terug, met eventuele
media-informatie apart als alleen-lezen gegevens.

| Productveld | Vorm en bestaande grens |
|---|---|
| `card_title` | Tekst, maximaal 80 tekens |
| `card_hover_image` | Bestaand afbeeldingsattachment-ID of `null` |
| `usp_1`, `usp_2`, `usp_3` | Tekst, ieder maximaal 60 tekens |
| `badge_nieuw`, `badge_pfas_vrij`, `badge_niet_leverbaar` | JSON-booleans |
| `pdp_usps` | Maximaal 8 objecten met `usp_text`, maximaal 140 tekens per tekst |
| `pdp_gallery_videos` | Maximaal 10 objecten met `youtube_url`, `video_title` (maximaal 120 tekens), `video_thumbnail` |
| `product_manual` | Bestaand PDF-attachment-ID of `null` |

Extra galerijmedia betekent in dit ACF-model YouTube-video's en hun optionele
thumbnails. Gewone product- en galerijafbeeldingen blijven WooCommerce-data.

## Rechten, validatie en opslaan

- De plugin gebruikt de door WordPress vastgestelde huidige gebruiker en
  controleert diens rechten. Het applicatiewachtwoord is uitsluitend een
  inlogmethode voor de externe client; de plugin bouwt hiervoor geen beheer,
  opslag of eigen authenticatie.
- Alle nieuwe gegevensroutes vereisen `manage_options` én toepasselijke
  WooCommerce-rechten. Productroutes controleren bovendien `edit_post` voor
  het specifieke product, ook bij lezen. Daarmee krijgen gewone editors en
  winkelmanagers standaard geen toegang tot deze REST-laag.
- Dit verandert de bestaande wp-admin-rechten niet. Beveiliging geldt ook
  voor detailroutes, zoekresultaten, schema en eventuele alternatieve REST-contexten.
- Geen nieuwe velden in publieke WooCommerce-, WordPress- of Store API-responses.
  Adminresponses krijgen passende headers tegen gedeelde caching.
- De REST-invoer wordt volledig gevalideerd voordat de eerste wijziging wordt
  uitgevoerd. Onbekende velden, verkeerde typen, verkeerde taxonomy-ID's en
  ongeschikte media geven een concrete fout met het betreffende veldpad.
- Labelnormalisatie en opslag worden uit de adminhandlers gedeeld met REST.
  Adminauthenticatie blijft bij de adminhandler; REST-authenticatie bij de controller.
- ACF-velden worden aan de hand van hun geregistreerde definities gevalideerd,
  inclusief repeater-subvelden en ACF-validatiefilters, en opgeslagen via
  ACF met field keys. Alleen `update_field()` aanroepen is onvoldoende als
  vervanging van de voorafgaande validatie.
- Ontbrekende ACF- of repeaterondersteuning wordt expliciet gemeld. Labels en
  labelkoppelingen blijven zelfstandig werken. Alleen writes die een niet
  beschikbaar ACF-veld aanbieden worden geweigerd. De API schrijft geen ruwe
  repeatermeta als noodoplossing.
- De bestaande cache-invalidatie wordt gedeeld en uitgevoerd nadat de
  wijzigingen zijn opgeslagen. WordPress-termrelaties en WooCommerce-data
  worden via hun eigen opslag-API's gewijzigd; admin-POST's worden niet nagebootst.
- Host- en volledige paginacaches zijn een aparte laag. De bestaande beperking
  rond geplande labelgrenzen blijft gelden: gecachte HTML kan tot de volgende
  purge/vervaldatum de oude toestand tonen. Automatisch campagnemomenten purgen
  vraagt een afzonderlijke uitbreiding en stagingcontrole.
- Frontendtemplates, CSS, JavaScript, fallbackregels en Elementor-controls
  behouden hun huidige gedrag.

## Aanvullende mogelijkheden om te kiezen

| Onderdeel | Advies |
|---|---|
| Besloten schema-/mogelijkhedenendpoint | Direct meenemen; bruikbaar voor Claude zonder hardcoded aannames |
| FAQ-repeater `product_faq` | Logische vervolgstap; eerst de werkelijke externe velddefinitie en filters inspecteren, geen nieuwe groep registreren |
| Validatie zonder opslaan | Optioneel vervolg voor grotere imports; geen vereiste voor de eerste versie |
| Elementor-categorievolgorde, naam-/afbeeldingoverrides, widgetstijl | Apart traject; dit wijzigt templates en gegenereerde CSS, vraagt een ander opslag- en testcontract |
| Geplande volledige paginacache-purges | Apart traject voor tijdkritische campagnes; hosting/cache-integratie eerst vaststellen |
| Prijzen, voorraad, categorieën, upsells, cross-sells, gewone galerij | Bestaande WooCommerce-API blijven gebruiken |
| Media uploaden | Bestaande WordPress `/wp/v2/media` gebruiken, daarna attachment-ID koppelen |
| Automatische handleidingenmap en MPN-filters | Bestaande template-/codeconfiguratie behouden; PDF-koppeling per product komt wel in deze REST-laag |

## Oplevering na akkoord over de scope

- Een unieke nieuwe minor-releasecandidate, met gesynchroniseerde metadata,
  changelog en relevante projectdocs volgens de repo-werkwijze.
- Gerichte REST-regressies voor admin, anoniem, klant, editor en winkelmanager;
  ook verkeerde IDs, overschrijding van limieten, veilige HTML, planning,
  repeater herschrijven/leegmaken, ontbrekende ACF en cache-invalidatie.
- Bestaande label- en categorie-exclusieregressies, PHP-lint, Composer/security/
  compatibiliteit, metadata- en ZIP-validatie; CI-controles volgens de repo.
- Een installeerbare ZIP uitsluitend via `tools/build_wordpress_plugin_zip.py`,
  met SHA-256 en een aparte handleiding met curl- en PowerShell-voorbeelden.
- Stagingvoorbeelden voor lezen, label aanmaken/wijzigen, koppelen/ontkoppelen,
  productvelden/repeaters/media wijzigen en geweigerde anonieme toegang.
- Geen installatie op staging of productie. Niet uitgevoerde installatie-,
  editor-, frontend- en cache-integratietests worden expliciet vermeld.

## Online verificatie van de aanpak (21 oktober 2026)

De officiële documentatie en openbare broncode bevestigen de hoofdrichting.
De namespacekeuze is een architectuurafweging; WordPress ondersteunt zowel
een aangepaste standaardcontroller als eigen controllers en namespaces.

| Alternatief | Afweging voor deze plugin |
|---|---|
| Alleen `show_in_rest` aanzetten | Onvoldoende: de standaard termcontroller controleert bij normale lijstreads niet of de gebruiker labels mag beheren |
| Eigen beveiligde termcontroller op `/wp/v2/wcpce_product_label` | Technisch geldig; productvelden vereisen daarnaast hun eigen integratie en validatie |
| Native ACF REST inschakelen en afschermen | Ondersteunt repeaters en media, maar vereist aanvullende rechtenfilters en hergebruik van formulier-validatie op alle relevante routes |
| Velden toevoegen aan `/wc/v3/products` | Mogelijk, maar vereist nog steeds eigen ACF-validatie en strikte rechten op de uitbreiding; standaard productbeheer is al een apart contract |
| Eigen `/wcpce/v1`-controllers | Voorkeur: één expliciet, klein beheercontract voor labels, koppelingen en pluginvelden, met ACF als opslaglaag |

### Authenticatie en private reads

WordPress ondersteunt applicatiewachtwoorden rechtstreeks voor REST via Basic
Auth over HTTPS. WooCommerce ondersteunt eveneens WordPress REST-authenticatie.
De login bepaalt wie de gebruiker is; capabilitychecks bepalen toegang.
Een plugincontroller kan daardoor dezelfde login gebruiken zonder extra
authenticatieplugin of browsernonce.
Bronnen: [WordPress authenticatie](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/),
[WooCommerce authenticatie](https://developer.woocommerce.com/docs/apis/rest-api/authentication).

De standaard termcontroller controleert `edit_terms` bij `context=edit`, maar
niet standaard bij iedere `context=view`-lijstread. Alleen de taxonomycapabilities
instellen is dus onvoldoende voor besloten REST-lezen. Iedere eigen leesroute
krijgt expliciet dezelfde beheerdercontrole als de schrijfroutes.
Bron: [WordPress term-leesrechten, inclusief broncode](https://developer.wordpress.org/reference/classes/wp_rest_terms_controller/get_items_permissions_check/).

### ACF kan de gegevensvorm al aan, maar de controles moeten aansluiten

ACF ondersteunt native REST vanaf 5.11, met repeaters als lijsten van objecten
en image/file-velden als attachment-ID's. Een extra repeater-REST-plugin is
daarvoor niet nodig. De native integratie is een reëel alternatief, geen
technische onmogelijkheid. Bron:
[ACF REST-integratie](https://www.advancedcustomfields.com/resources/wp-rest-api-integration/).

De onderzochte ACF-controller valideert via WordPress-schema's en
`acf/validate_rest_value/type=...`. De formulier-validator gebruikt
`acf_validate_value()` met de `acf/validate_value`-filters voor type, naam en
field key. De paden zijn verschillend; een native REST-opt-in waarborgt dus
niet vanzelf dezelfde maatwerk-formuliervalidatie.
Bronnen: [ACF REST-controller](https://raw.githubusercontent.com/AdvancedCustomFields/acf/master/includes/rest-api/class-acf-rest-api.php),
[ACF formulier-validator](https://raw.githubusercontent.com/AdvancedCustomFields/acf/master/includes/validation.php).

`update_field()` roept opslag aan zonder zelf die formulier-validator uit te
voeren. Voor deze API moeten alle aangeboden velden, inclusief repeaterrijen,
eerst met hun echte field keys/definities worden gevalideerd. Daarna opslaan
via field keys, ook bij de eerste waarde op een product, zodat ACF de correcte
veldreferenties onderhoudt. Geen ruwe metatellingen of handgemaakte subvelden.
Bronnen: [ACF update_field-bron](https://raw.githubusercontent.com/AdvancedCustomFields/acf/master/includes/api/api-template.php),
[ACF field-key-documentatie](https://www.advancedcustomfields.com/resources/update_field/).

Volledige formulierhooks zoals `acf/validate_save_post` en `acf/save_post`
hebben bovendien een ingediende-formuliercontext en kunnen `$_POST` lezen.
We beloven niet dat onbekende site-snippets automatisch identiek werken.
Eventuele zulke regels moeten worden geïnventariseerd en naar gedeelde logica
worden gebracht of een gerichte REST-adapter krijgen. De repo bevat die
externe snippets niet. Bronnen:
[ACF formulier-validatiehook](https://www.advancedcustomfields.com/resources/acf-validate_save_post/),
[ACF formulier-opslaghook](https://www.advancedcustomfields.com/resources/acf-save_post/).

### Cache-afhandeling is onderdeel van de implementatie

ACF's waardeopslag wist de eigen veldcache en voert update-valuefilters uit.
Dat is geen volledige product-savecyclus. Ook WooCommerce kan bij een save
zonder gewijzigde post-eigenschappen de wijzigingsdatum rechtstreeks bijwerken;
`save_post_product` is dus geen gegarandeerd gevolg van een metadatawijziging.
Daarom de plugin-cacheafhandeling expliciet delen en na de volledige write
uitvoeren. Bronnen:
[ACF waardeopslag](https://raw.githubusercontent.com/AdvancedCustomFields/acf/master/includes/acf-value-functions.php),
[WooCommerce productopslag](https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/includes/data-stores/class-wc-product-data-store-cpt.php).

WP Rocket biedt `rocket_clean_post()` om een product en gerelateerde archieven
te purgen. Een eventuele adapter moet na succesvolle opslag lopen en rekening
houden met andere pagina's waarop het product als kaart voorkomt. Het is
geen bewijs dat Kinsta/CDN en willekeurige Elementor-landingspagina's daarmee
ook allemaal worden ververst. Die dekking blijft een stagingcontrole;
geen onvoorwaardelijke belofte van een volledige sitepurge.
Bron: [WP Rocket gerichte purge](https://docs.wp-rocket.me/article/93-rocketcleanpost).

### Schema en alternatieve verzoekvormen

WordPress verwerkt `OPTIONS` buiten de normale routemapping en kan daar
schemas publiceren. De afgeschermde GET `/schema` levert beschikbare
pluginfunctionaliteit aan bevoegde clients. Algemene routebeschrijvingen
mogen uitsluitend statische schemainformatie bevatten, zonder opgeslagen
label-/productwaarden of dynamische site-instellingen. Er is geen reden de
algemene WordPress-route-index te verbergen of schemanamen als geheime data
te behandelen. Wel testen dat ontdekking en OPTIONS geen beheerdata lekken.
Bronnen: [WordPress OPTIONS-afhandeling](https://developer.wordpress.org/reference/functions/rest_handle_options_request/),
[WordPress route-discovery](https://developer.wordpress.org/reference/classes/wp_rest_server/get_data_for_route/).

Aanvullende regressies: anonieme `HEAD`/`OPTIONS`, REST-embeds, aanvragen binnen
een REST-batch en lees-na-schrijf binnen één proces; geen datalek of oude
requestcache. Dit zijn gerichte tests van de afscherming, geen uitbreiding van
de functionele scope.

Deze verificatie gebruikt online documentatie en openbare ontwikkelbranches.
De geïnstalleerde ACF/WooCommerce-versies en sitefilters zijn hiermee niet
vastgesteld. Bij de bouw worden beschikbare functies gecontroleerd en wordt
ontbrekende ondersteuning expliciet gerapporteerd.

## Definitief afgebakend bouwplan na herbeoordeling

Dit onderdeel legt de overeengekomen scope vast. Het actuele clientcontract
staat in `wc-product-card-elementor/REST-API.md`; de secties hieronder bewaren
de ontwerpafwegingen van vóór de bouw.

### 1. Klein en zelfstandig REST-contract

De routetabel hierboven blijft het contract. De `/products/{id}`-route beheert
uitsluitend pluginvelden en labelkoppelingen op een bestaand `product`.
Producten aanmaken, verwijderen, algemene WooCommerce-data en variaties
vallen onder de bestaande WooCommerce-routes. Een label kan worden
gedeactiveerd en van producten worden losgekoppeld; definitief verwijderen
van herbruikbare labels is geen onderdeel van de eerste versie.

Er komt een expliciete allowlist van de door deze plugin beheerde ACF-fields
en field keys. Een client kan geen willekeurige meta, ACF-groep, productsysteem-
eigenschap of Elementor-data aanbieden. Het schema wordt uit de bestaande
velddefinities afgeleid, zodat limieten niet op meerdere plekken uiteenlopen.

### 2. Lees beheerdata, behoud bestaande weergaveregels

Voor REST worden de opgeslagen velden en termrelaties gelezen, niet de
zichtbare frontendresultaten uit `get_product_labels()` of widgethelpers.
Inactieve, toekomstige en verlopen labels blijven leesbaar en toegewezen,
ook op een product met de badge Niet meer leverbaar. Frontendlimieten en
fallbacks mogen de beheergegevens niet verkorten of vervangen.

Afwezige ACF-ondersteuning wordt via beschikbare onderdelen gemeld; een
niet-beschikbaar veld wordt niet als een gewone lege repeater voorgesteld.
Bestaande opgeslagen data wordt niet gemigreerd of tijdens een GET gerepareerd.

### 3. Eenduidige updates en fouten

- Een PATCH wijzigt alleen expliciet aangeboden eigenschappen. Defaults zijn
  voor nieuwe labels; ze mogen bij een PATCH geen ontbrekende waarden wissen.
- Tekst wissen via `""`, media wissen via `null`, een lijst wissen via `[]`.
  Booleans gebruiken echte JSON-booleans; `false` is een aangeleverde waarde.
- Een repeater of `label_ids` is een volledige vervanging. Volgorde blijft
  bewaard waar die inhoudelijk betekenis heeft; dubbele label-ID's worden
  verwijderd. Een client leest de actuele toestand voordat hij lijsten wijzigt.
- De API controleert alle aangeboden velden en relaties vooraf. Verkeerde
  invoer geeft een fout met veldpad en veroorzaakt geen opslag of cachepurge.
- Dat is geen belofte van een database-transactie over alle WordPress- en
  ACF-hooks. Ook opslagfouten moeten worden herkend; nooit succes teruggeven
  terwijl slechts een deel is opgeslagen. Na een opslagfout leest de client
  de actuele toestand voordat hij een write herhaalt.
- Een ongewijzigde waarde opnieuw schrijven is wel succesvol gedrag. Een
  `false`-resultaat van een updatefunctie is daarom op zichzelf geen bewijs
  van een opslagfout; de afhandeling moet ongewijzigd en mislukt onderscheiden.
- Labels aanmaken en koppelen zijn afzonderlijke operaties. Een dubbel
  label levert een herkenbare conflictmelding op; een retry mag bestaande
  labelinstellingen niet stilzwijgend overschrijven.
- Geen batchwriter, versie-/lockingprotocol of validatie-zonder-opslaan in
  deze eerste versie. Daarvoor is een aparte keuze nodig; gelijktijdige
  beheerwijzigingen blijven dus een aandachtspunt bij volledige lijstvervanging.

### 4. Eén set bestaande inhoudsregels

Gedeelde labelopslag en normalisatie gaan achter de admin- en REST-adapters.
De externe vorm verschilt: adminformulieren leveren onder meer strings en
checkboxes, REST levert getypeerde JSON. Hetzelfde opgeslagen resultaat en
dezelfde inhoudsregels zijn het doel; identieke transportvalidatie niet.

De ACF-definities en hun veldvalidators zijn leidend. Render-fallbacks zijn
geen nieuwe schrijfregels. Voorbeeld: de galerij kan een onbruikbare
YouTube-link bij weergave overslaan; alleen daarom een nieuwe, afwijkende
REST-validator toevoegen zou geen gelijkheid met het productscherm opleveren.
Eventuele aanvullende inhoudsregels moeten expliciet gedeeld worden met admin.

### 5. Opslag afronden en caches correct behandelen

Na ACF-opslag worden de actuele productgegevens opnieuw betrokken bij het
afronden van de productwijziging via de normale WordPress/WooCommerce-
opslagcyclus. Daarbij wordt expliciet gecontroleerd welke savehooks werkelijk
lopen: een kale `update_field()` of ongewijzigde `$product->save()` volstaat
niet als bewijs dat alle verwachte handlers zijn uitgevoerd. Geen nagebootste
admin-POST en geen losse kunstmatige aanroep van core-savehooks.

De gedeelde plugin-cache-invalidatie volgt op de voltooide wijziging, ook
voor labelrelaties. Labeldefinitiewijzigingen wissen de eigen labelcache;
ze leiden niet tot het opnieuw opslaan van alle gekoppelde producten.
Een ACF-wijziging aan bijvoorbeeld Niet meer leverbaar moet eveneens de
zichtbare-labelcache in hetzelfde request ongeldig maken.

De eerste versie introduceert geen algemeen cachebeheerendpoint en geen
automatische planning van cachepurges. Bestaande integraties krijgen de echte
opslaggebeurtenissen waar van toepassing. Volledige host/CDN-purgedekking en
automatische tijdgrenzen blijven apart te testen en te beslissen onderdelen.

### 6. Toetsen aan de oorspronkelijke opdracht

De oplevering toetst minimaal: juiste gegevens heen en terug lezen, nieuw
product zonder eerdere ACF-veldreferenties, aanpassen/leegmaken/inkorten van
repeaters, afbeeldings-ID/PDF-controles, behoud van niet-aangeboden velden,
alle labelinstellingen en planning, geweigerde lees- en schrijfpogingen,
geen writes bij validatiefouten, geen vals succes bij opslagfouten en
cacheverversing na product-, relatie- en labelwijzigingen. De nieuwe
REST-regressies worden onderdeel van de bestaande CI in plaats van alleen
een los handmatig script.

Er komt een kandidaat-ZIP plus korte clientdocumentatie met eerst GET, dan
gerichte PATCH, label aanmaken/koppelen en negatieve toegangstests. Werkelijke
staginginstallatie en acceptatie blijven bij de gebruiker. Een lokaal groene
check wordt niet gepresenteerd als bewezen site-integratie.

De voorgestelde extra's blijven: schema direct meenemen; FAQ, Elementor-
templatebeheer, dry-run en geplande externe cachepurges buiten de eerste
bouw houden totdat ze expliciet zijn gekozen.

## Geraadpleegde primaire documentatie

- [WordPress custom endpoints en permission callbacks](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/)
- [WordPress standaard term-leesrechten](https://developer.wordpress.org/reference/classes/wp_rest_terms_controller/get_items_permissions_check/)
- [WordPress authenticatie en applicatiewachtwoorden](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/)
- [ACF REST API-opt-in](https://www.advancedcustomfields.com/resources/wp-rest-api-integration/)
- [ACF update_field en field keys](https://www.advancedcustomfields.com/resources/update_field/)
- [ACF validatiefilters](https://www.advancedcustomfields.com/resources/acf-validate_value/)
- [WordPress media-endpoints](https://developer.wordpress.org/rest-api/reference/media/)
