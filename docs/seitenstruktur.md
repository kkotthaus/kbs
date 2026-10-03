# Seitenstruktur

| Pfad | Inhalt | Quelle |
| --- | --- | --- |
| `/` | Hero mit Telefon-Karte, Leistungen, drei Schritte, Fernwartung (PC-Visit), Websites, Zielgruppen, FAQ, Aufruf Erstgespräch | `seiten.mjs` › startseite |
| `/leistungen/` | Leistungskarten, Vorteile | |
| `/leistungen/<slug>/` | IT-Betreuung, IT-Beratung, WordPress-Websites: Text, Ansprechpartner-Kasten, FAQ, weitere Leistungen | `daten.mjs` › leistungen, `seiten.mjs` › leistungInhalte |
| `/fernwartung/` | PC-Visit Quick Support und Host, Ablauf, Sicherheit | Links aus Firmendaten › PC-Visit |
| `/ueber-uns/` | Wer wir sind, Geschäftsführung, Werte | |
| `/kontakt/` | Kontaktformular, Kontaktdaten | `kbs-kontakt.php` |
| `/impressum/`, `/datenschutz/` | Rechtstexte mit Angaben aus den Firmendaten | |

Templates: `index` (Header, Inhalt, Footer für alle Seiten), `404`.

Komponenten: SiteHeader, SiteFooter, Leistungskarten, PcVisit, Kontaktdaten, Kontaktformular, CtaBand (`komponenten.mjs`).

Alte Adressen von kotthaus-bs.de (`/leistung/…`) leiten per 301 auf die neuen Seiten (`daten.mjs` › weiterleitungen).

Neukundengewinnung: Telefon und „Erstgespräch vereinbaren“ im Header, Telefon-Karte im Hero, Aufruf-Band am Ende jeder Seite, Ansprechpartner-Kasten auf Leistungsseiten, kurzes Formular mit Anliegen-Auswahl. Angebotstitel und -text sind unter Firmendaten › Angebot pflegbar.
