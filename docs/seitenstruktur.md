# Seitenstruktur

| Pfad | Inhalt | Quelle |
| --- | --- | --- |
| `/` | Hero mit Telefon-Karte, Leistungen, Websites (Webdesign und Wartung), drei Schritte, Fernwartung (PC-Visit), Zielgruppen, FAQ, Aufruf Erstgespräch | `seiten.mjs` › startseite |
| `/leistungen/` | Leistungskarten, Vorteile | |
| `/leistungen/<slug>/` | IT-Betreuung, IT-Beratung, Webdesign (`wordpress-websites`), Website-Wartung (`website-wartung`): Text, Ansprechpartner-Kasten, Ablauf in Schritten (optional, `ablauf`), FAQ, weitere Leistungen, Aufruf mit eigenem Titel (`cta`) | `daten.mjs` › leistungen, `seiten.mjs` › leistungInhalte |
| `/fernwartung/` | PC-Visit Quick Support und Host, Ablauf, Sicherheit, häufige Fragen (auch FAQ-Schema) | Links aus Firmendaten › PC-Visit |
| `/ueber-uns/` | Wer wir sind, Geschäftsführung, Werte | |
| `/kontakt/` | Kontaktformular, Kontaktdaten | `kbs-kontakt.php` |
| `/impressum/`, `/datenschutz/` | Rechtstexte mit Angaben aus den Firmendaten | |

Templates: `index` (Header, Inhalt, Footer für alle Seiten), `404`.

Komponenten: SiteHeader, SiteFooter, Leistungskarten, PcVisit, Kontaktdaten, Kontaktformular, CtaBand (`komponenten.mjs`).

Alte Adressen von kotthaus-bs.de (`/leistung/…`) leiten per 301 auf die neuen Seiten (`daten.mjs` › weiterleitungen).

Hauptnavigation (`daten.mjs` › navigation): Leistungen (IT-Betreuung, IT-Beratung), Websites (Webdesign, Website-Wartung), Fernwartung, Über uns, Kontakt. Websites sind ein eigener Hauptpunkt, weil Webdesign und Wartung ausgebaut werden sollen (Stand 2026-10-10).

Neukundengewinnung: Telefon und „Erstgespräch vereinbaren“ im Header, Telefon-Karte im Hero, Aufruf-Band am Ende jeder Seite, Ansprechpartner-Kasten auf Leistungsseiten, kurzes Formular mit Anliegen-Auswahl. Angebotstitel und -text sind unter Firmendaten › Angebot pflegbar.
