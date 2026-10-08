# Technik der Website

> Für Administratoren. Erklärt, wie die Website aufgebaut ist und wie Plugins, Snippets, CSS und Skripte zusammenarbeiten. Quelle: Repository kbs, `docs/technik.md` – Änderungen dort, nicht hier (die Seite wird beim Build erzeugt).

## Überblick

Die Website besteht aus Schichten. Jede Schicht hat genau eine Aufgabe; was eine Schicht liefert, nutzt die nächste.

| Schicht | Baustein | Aufgabe |
| --- | --- | --- |
| 1. Grundlage | WordPress, Theme „Etch Theme“ | Inhalte, Benutzer, Mediathek; das Theme ist nur ein leerer Rahmen |
| 2. Aufbau | Etch | Seiten, Templates (Rahmen für alle Seiten) und Komponenten (wiederverwendbare Bausteine) als echtes HTML |
| 3. Gestaltungssystem | Automatic.css (ACSS) 4 | Farben, Abstände, Schriftgrößen, Buttons, Hell/Dunkel – als CSS-Variablen und Klassen |
| 4. Eigenes Aussehen | Etch-Stylesheet „KBS“ | alles, was ACSS nicht abdeckt (Header, Hero, Karten, Footer …) – nur mit ACSS-Variablen |
| 5. Bausteine mit Verhalten | OhMyEtch | Häufige Fragen (Accordion), Brotkrumen, Inhaltsverzeichnis – mit Tastaturbedienung und ARIA |
| 6. Daten und Logik | Snippets in WPCodeBox | Firmendaten, Kontaktformular, SEO, KI-Kennzeichnung, Navigation – liefern fertige Werte an Etch |
| 7. Betrieb | SEOPress, LiteSpeed Cache, Security Ninja, MainWP, Duplicator | Suchmaschinen, Geschwindigkeit, Sicherheit, Fernverwaltung, Sicherung |

**Grundsatz:** PHP rechnet, Etch gestaltet. Die Snippets erzeugen kein Markup und keine Shortcodes; sie stellen Werte als „dynamische Daten“ bereit (z. B. `{options.kbs.firma.telefon}`), und die Etch-Komponenten zeigen sie an. Texte und Telefonnummern stehen deshalb nie fest im Markup.

## Woher kommt was?

Seiten, Templates, Komponenten, das Stylesheet „KBS“ und die Snippets entstehen im **Repository** (GitHub: kkotthaus/kbs) und werden übertragen. Einige Inhalte werden dagegen **hier im Backend** gepflegt.

| Bereich | Gepflegt | Hinweis |
| --- | --- | --- |
| Seiten und Texte, Templates, Komponenten | Repository | Änderungen im Etch-Editor überschreibt die nächste Übertragung. Texte deshalb im Repository ändern. |
| Stylesheet „KBS“, ACSS-Einstellungen (Farben, Buttons, Schrift) | Repository | ACSS-Einstellungen nicht im Backend ändern, sonst gehen sie bei der nächsten Übertragung verloren |
| Snippets (WPCodeBox, Ordner „KBS“) | Repository | im WPCodeBox-Editor nur im Notfall ändern und die Änderung ins Repository übernehmen |
| Dateien in `wp-content/kbs/` (Logos, Daten, Build) | Repository | werden mit dem Build hochgeladen |
| **Firmendaten** (Menü „Firmendaten“) | **Backend** | Name, Adresse, Telefon, Erreichbarkeit, Rechtliches, PC-Visit, Angebot, Vorschaubild |
| **Mediathek** samt KI-Kennzeichnung | **Backend** | Bilder und ihre Felder `ki_art`, `ki_werkzeug`, `ki_position` |
| SEO-Felder einzelner Seiten, SEOPress-Einstellungen | **Backend** | ein im Seiteneditor gesetzter SEOPress-Titel geht dem Titel aus dem Repository vor |
| Benutzer | **Backend** | |

Die Entwicklung läuft auf einer lokalen Kopie (kbs.local). Ablauf einer Änderung: im Repository ändern → bauen → lokal prüfen → hierher übertragen (Dateien hochladen, gezielt synchronisieren). Beschrieben in `docs/umzug-live.md` im Repository.

## Plugins

| Plugin | Aufgabe | Zusammenspiel |
| --- | --- | --- |
| **Etch** | Builder: Seiten, Templates, Komponenten, globale Stylesheets, dynamische Daten | Kern der Website. Liest die Daten der Snippets über die Filter `etch/dynamic_data/option` und `etch/dynamic_data/post` |
| **Etch Theme** | leerer Theme-Rahmen für Etch | keine eigene Gestaltung |
| **Automatic.css** | Gestaltungssystem (Variablen, Klassen, Hell/Dunkel) | lädt **nach** dem Stylesheet „KBS“. Farbschema „light dark“: folgt der Einstellung des Geräts, der Umschalter im Header überstimmt sie |
| **OhMyEtch** | Accordion (Häufige Fragen), Breadcrumbs, Table of Contents (Datenschutz) | bringt Verhalten und ARIA mit; das Aussehen kommt aus dem Stylesheet „KBS“ |
| **Etch Font Manager** | Schriften Manrope (Text) und Sora (Überschriften), selbst gehostet in `wp-content/fonts/` | keine Schrift von fremden Servern. Welche Schrift wofür gilt, steht in den ACSS-Einstellungen, nicht im Font Manager |
| **Meta Box AIO** | Felder der Einstellungsseiten „Firmendaten“ und „KI-Kennzeichnung“ und der Mediathek | die Felder sind im Code der Snippets registriert, nicht im Meta-Box-Builder |
| **WPCodeBox 2** | führt die eigenen PHP-Snippets aus (Ordner „KBS“) | Snippets mit Fehler schaltet WPCodeBox selbst ab |
| **SEOPress + SEOPress Pro** | Titel, Meta-Beschreibung, Open Graph, Sitemap `/sitemaps.xml`, `/llms.txt` | Titel, strukturierte Daten und Inhalt der `llms.txt` liefert das Snippet „KBS – Suchmaschinen“ |
| **LiteSpeed Cache** | Seitencache (7 Tage) und Object-Cache | wird beim Speichern der Firmendaten und der KI-Darstellung automatisch komplett geleert, Seiten beim Speichern einzeln |
| **Security Ninja** | Sicherheit (Firewall, Prüfungen) | darf den Zugang `/wp-json/mcp/` nicht sperren |
| **MainWP Child** | Fernverwaltung über MainWP | |
| **Matomo** | Statistik – derzeit **ohne Tracking** | vor dem Einschalten die Datenschutzerklärung anpassen (sie sagt: keine Analyse-Werkzeuge) |
| **Duplicator Pro** | Sicherung | nie ein Paket der lokalen Kopie hier einspielen – es überschriebe alles, was hier gepflegt wurde |
| **MCP Adapter** | Zugang für KI-Werkzeuge (nur lesend) | **nicht auf 0.7.x aktualisieren** (inkompatibel) |
| Uplink Media Bridge, Uplink Editorial Title, Uplink Unified Ops Center | Hilfen für Etch im Backend (Mediathek-Ordner, Titel, Übersicht) | nicht für die Darstellung der Website nötig |
| Temporary Login Without Password | befristeter Zugang für Support | inaktiv, nur bei Bedarf einschalten |

## Snippets (WPCodeBox, Ordner „KBS“)

| Snippet | Aufgabe | Liefert / greift ein |
| --- | --- | --- |
| **KBS – Firmendaten** | Einstellungsseite „Firmendaten“ (Option `firmendaten`) | dynamische Daten `{options.kbs.firma.…}`, `{options.kbs.pcvisit.…}`; Website-Icon als Rückfall; leert nach dem Speichern den Cache |
| **KBS – Navigation** | Skripte für den Header | im `<head>`: Klasse `js` und gespeichertes Farbschema vor dem ersten Zeichnen; im Footer: mobiles Menü, Untermenüs, Escape, aktueller Menüpunkt (`aria-current`), Umschalter Hell/Dunkel (Wahl im Browser unter `kbs-farbschema`) |
| **KBS – Kontaktformular** | nimmt das Formular an (`admin-post.php`) | prüft Pflichtfelder und Zustimmung, Spam-Schutz ohne Cookies (verstecktes Feld, Mindestzeit, Grenze je Stunde), schickt per E-Mail an den Empfänger aus den Firmendaten; speichert nichts. Daten `{options.kbs.kontakt.…}` |
| **KBS – Suchmaschinen** | SEO und KI-Suche | Seitentitel aus `daten/seo.json`, strukturierte Daten (Firma mit Öffnungszeiten, Leistungen, FAQ), Vorschaubild für geteilte Links, Inhalt von `/llms.txt`, 301-Weiterleitungen von alten Adressen |
| **KBS – KI-Kennzeichnung** | Kennzeichnung KI-erzeugter oder -veränderter Bilder (EU-KI-Verordnung) | Felder in der Mediathek, Plakette „KI“ am Bild, Hinweis im Alternativtext; Einstellungen unter Medien › KI-Kennzeichnung; Spalte und Filter in der Mediathek |
| **KBS – Backend** | blendet den Block „Individuelle Felder“ aus | verhindert, dass rohe Metadaten ungeprüft geändert werden |
| **KBS – MCP-Erweiterung** | Funktionen zum Übertragen aus dem Repository | hier ohne Wirkung: aktiv nur in der Entwicklung oder für einen einzelnen Aufruf per WP-CLI |

> Ein Snippet, das WPCodeBox wegen eines Fehlers abgeschaltet hat, fehlt still – etwa das Formular oder die Navigation. Nach Änderungen in WPCodeBox prüfen, dass alle KBS-Snippets eingeschaltet sind.

## CSS: wer gestaltet was

Reihenfolge, in der die Gestaltung zusammenkommt:

1. **Schriften** – Etch Font Manager (`efm-fonts.css`, Schriften aus `wp-content/fonts/`).
2. **Etch-Stylesheets** (Etch › Stylesheets, global):
   - „KBS“ – das eigene Aussehen, Quelle `wordpress/etch/css/main.css` im Repository
   - „DWC Mega Menu“ – **immer behalten**, auch wenn es ungenutzt wirkt; nicht löschen
   - „Main“ und „Custom Media Definitions“ – Vorgaben von Etch
3. **Automatic.css** – lädt danach. Seine Einstellungen (Farben, Buttons, Schrift) kommen aus dem Repository (`acss-farben`, `acss-buttons`, `acss-schrift`).
4. **OhMyEtch** – Grundverhalten seiner Bausteine; das Aussehen setzt das Stylesheet „KBS“.

Regeln im Stylesheet „KBS“:

- **ACSS zuerst:** Buttons nur mit ACSS-Klassen (`btn--primary`, `btn--outline`, `btn--s` …), Abstände, Schriftgrößen und Rundungen aus ACSS-Variablen. Eigene Klassen nur, wo ACSS nichts bietet, nach BEM benannt (`block__element--modifier`).
- **Nur ACSS-Farbvariablen**, keine festen Farbwerte – sonst bricht das dunkle Schema. Flächen, die in beiden Schemata dunkel bleiben (Footer, Erstgespräch-Panel), legt die ACSS-Einstellung „Force light selectors“ fest.
- **Bilder je Schema** (Logo, Hintergründe): beide Fassungen im Markup, umgeschaltet über die Klassen `scheme-bild--hell` / `scheme-bild--dunkel`.
- **Skip-Link** „Zum Inhalt springen“: Klasse `skip-link` von ACSS, kein eigenes CSS.
- Abschnitte im Stylesheet: Grundlagen, Buttons, Abschnitte und Layout, Bilder je Farbschema, Header und Navigation, Köpfe mit Raster, Leistungskarten, Schritte, Vorteile, Zielgruppen, Häufige Fragen, Seitenkästen, Fernwartung, Website-Illustration, Kontakt, Aufruf Erstgespräch, 3D-Karten, Footer, KI-Kennzeichnung.

## JavaScript

| Skript | Woher | Aufgabe |
| --- | --- | --- |
| Header (Menü, Untermenüs, Hell/Dunkel) | Snippet „KBS – Navigation“ | ohne JavaScript bleibt das Menü sichtbar und bedienbar (Klasse `js` am `<html>` schaltet erst das Klappmenü ein) |
| Accordion, Breadcrumbs, Inhaltsverzeichnis | OhMyEtch | Tastatur, ARIA, Animation |
| Mediathek: Plakette „KI“ auf den Kacheln | Snippet „KBS – KI-Kennzeichnung“ | nur im Backend |
| Etch-Editor | Etch | nur für angemeldete Administratoren |

Keine Analyse- oder Werbeskripte, keine externen Schriften, keine Cookies außer denen von WordPress für angemeldete Benutzer.

## Datenfluss an Beispielen

### Telefonnummer im Header

Firmendaten › Telefon (gespeichert in der Option `firmendaten`) → Snippet „KBS – Firmendaten“ stellt `{options.kbs.firma.telefon}` bereit (dazu die Form für `tel:`-Links) → Komponente „SiteHeader“ zeigt sie an → beim Speichern der Firmendaten leert das Snippet den Cache, die Seite zeigt sofort die neue Nummer.

### Kontaktformular

Komponente „Kontaktformular“ auf `/kontakt/` → Absenden an `admin-post.php` (Aktion `kbs_kontakt`) → Snippet „KBS – Kontaktformular“ prüft und sendet per `wp_mail` an den Empfänger aus den Firmendaten (leer = Admin-E-Mail) → zurück auf die Kontaktseite mit Erfolg oder Fehlermeldung. Abschalten unter Firmendaten › Kontaktformular.

### Seitentitel und Google

`seiten.mjs` im Repository (Titel, häufige Fragen, Leistung je Seite) → Build schreibt `wp-content/kbs/daten/seo.json` → Snippet „KBS – Suchmaschinen“ setzt Titel und strukturierte Daten über die Filter von SEOPress → SEOPress gibt Meta-Daten und Sitemap aus. Die Meta-Beschreibung ist der Auszug der Seite.

### KI-Kennzeichnung

Mediathek › Bild › „KI-Nutzung“ (`ki_art`) → Snippet „KBS – KI-Kennzeichnung“ ergänzt Bilder aus dem Editor (`render_block`) und liefert Etch die Daten je Bild → Plakette „KI“ am Bild und Hinweis im Alternativtext. Aussehen unter Medien › KI-Kennzeichnung, eigenes Symbol unter Firmendaten.

## Seiten und Komponenten

| Seite | Inhalt |
| --- | --- |
| `/` | Hero mit Telefon-Karte, Leistungen, Schritte, Fernwartung, Websites, Zielgruppen, Häufige Fragen, Erstgespräch |
| `/leistungen/` und drei Unterseiten | IT-Betreuung, IT-Beratung, WordPress-Websites |
| `/fernwartung/` | PC-Visit-Downloads aus den Firmendaten |
| `/ueber-uns/`, `/kontakt/` | |
| `/impressum/`, `/datenschutz/` | Angaben aus den Firmendaten |

Templates: „index“ (Header, Inhalt, Footer für alle Seiten) und „404“.

| Komponente | Daten |
| --- | --- |
| SiteHeader | Firmendaten (Logo, Telefon), Skript „KBS – Navigation“ |
| SiteFooter | Firmendaten |
| Leistungskarten | Repository (Leistungen), letzte Karte aus Firmendaten › Angebot |
| PcVisit | Firmendaten › PC-Visit (Karten ohne Link werden ausgeblendet) |
| Kontaktdaten | Firmendaten |
| Kontaktformular | Snippet „KBS – Kontaktformular“ |
| CtaBand | Firmendaten (Angebot, Telefon) |

## Was man nicht tun sollte

- Seiten, Templates oder Komponenten im Etch-Editor ändern – die nächste Übertragung aus dem Repository überschreibt das (Ausnahme nur nach Absprache, dann ins Repository übernehmen).
- Das Etch-Stylesheet „DWC Mega Menu“ löschen.
- Den MCP Adapter auf 0.7.x aktualisieren.
- Ein Duplicator-Paket der lokalen Kopie hier installieren.
- Matomo-Tracking einschalten, ohne die Datenschutzerklärung anzupassen.
- Felder im Block „Individuelle Felder“ oder per Datenbank ändern – Felder immer über ihre Seiten (Firmendaten, Mediathek).

## Nach Updates prüfen

Automatische Updates sind aus; Updates werden zuerst auf der lokalen Kopie getestet. Danach hier im Browser prüfen:

- Header: Menü, Untermenü „Leistungen“, mobiles Menü, Umschalter Hell/Dunkel, Skip-Link (einmal Tab auf einer Seite)
- Häufige Fragen auf- und zuklappen, Brotkrumen, Inhaltsverzeichnis der Datenschutzerklärung
- Kontaktformular absenden (Testnachricht)
- beide Farbschemata, Schriften (Manrope, Sora)
- WPCodeBox: alle KBS-Snippets eingeschaltet
