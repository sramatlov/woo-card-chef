# Security Policy

## Ondersteunde versies

Woo Card Chef is een maatwerkplugin. Alleen de laatste release en de actuele code op `main` krijgen security- en compatibiliteitsupdates.

| Versie | Ondersteund |
|---|---|
| Laatste release | Ja |
| `main` | Ja, als ontwikkelversie |
| Oudere releases | Nee; upgrade eerst naar de laatste release |

## Een kwetsbaarheid melden

Meld vermoedelijke kwetsbaarheden vertrouwelijk via [GitHub Private Vulnerability Reporting](https://github.com/sramatlov/woo-card-chef/security/advisories/new). Maak geen openbaar issue aan voordat een oplossing beschikbaar is.

Vermeld waar mogelijk:

- de getroffen pluginversie en WordPress/PHP-versies;
- benodigde rollen of rechten om het probleem te misbruiken;
- reproduceerbare stappen of een minimaal proof of concept;
- de mogelijke impact;
- relevante logs of screenshots zonder persoonsgegevens, wachtwoorden of tokens.

De maintainer streeft ernaar een melding binnen drie werkdagen te bevestigen en binnen zeven werkdagen een eerste beoordeling of vervolgplan te geven. Kritieke of actief misbruikte kwetsbaarheden krijgen voorrang volgens [`MAINTENANCE.md`](MAINTENANCE.md).

## Afhandeling en publicatie

- Details blijven privé totdat een oplossing beschikbaar is en gebruikers redelijkerwijs konden bijwerken.
- Een fix doorloopt dezelfde verplichte CI- en stagingcontroles als iedere andere release.
- De release notes beschrijven impact en updateadvies zonder onnodige exploitdetails.
- Publieke bekendmaking en eventuele CVE-coördinatie gebeuren in overleg met de melder.

## Buiten scope

Problemen in WordPress, WooCommerce, Elementor, ACF, hosting of andere externe diensten horen primair bij de betreffende leverancier. Meld een integratieprobleem wel wanneer Woo Card Chef het risico veroorzaakt of vergroot.
