# Entwicklungsumgebung – projektspezifisch

Stack, allgemeine Konventionen und Betrieb: [etch-nodes](../etch-nodes/CLAUDE.md). Projekt-Prefix (`<prefix>` in etch-nodes): `kbs`.

## Plugins (kbs.local, Stand 2026-10-03)

| Baustein | Version | Anmerkung |
| --- | --- | --- |
| Etch (+ Etch-Theme) | 1.6.8 | |
| Automatic.css | 4.0.1 | Farbschema „light dark“ (folgt dem Betrachter) |
| OhMyEtch | 1.6.0 | Accordion, Breadcrumbs, Table of Contents |
| Meta Box AIO | 3.12.0 | Einstellungsseite „Firmendaten“ (im Code registriert) |
| WPCodeBox 2 | 1.4.1 | Ziel für die Snippets (Ordner „KBS“), siehe unten |
| MCP Adapter | 0.6.1 | **nicht auf 0.7.x aktualisieren** (inkompatibel mit mcp-wordpress-remote) |
| SEOPress Pro | 10.3 | Meta-Ausgabe, Open Graph, Sitemap (`/sitemaps.xml`), Indexierung, Auslieferung von `/llms.txt`; Titel und Inhalte der `llms.txt` kommen aus `kbs-seo.php` |
| Duplicator Pro, Etch Font Manager, Media Bridge for Etch | – | |

Kein EtchMegaMenuPro: Header und Navigation sind eigene Komponenten (`SiteHeader`, Skript `kbs-navigation.php`). SEO: SEOPress Pro gibt Titel, Meta-Beschreibung und Open Graph aus und liefert Sitemap und `/llms.txt`. Die Inhalte kommen aus dem Repo: In `seiten.mjs` hat jede Seite `seo` (Titel mit `firma()`, `faq`, `leistung`, `llms`-Abschnitt), der Build schreibt `daten/seo.json`, `kbs-seo.php` setzt daraus den Titel (ein im Seiteneditor gesetzter SEOPress-Titel geht vor) und die Beschreibung aus dem Auszug, gibt strukturierte Daten als @graph aus (ProfessionalService mit Öffnungszeiten aus „Erreichbarkeit“ und Geschäftsführer, Service auf Leistungsseiten, FAQPage) und füllt `/llms.txt` (Firma, Kontakt, Seiten, Fragen je Seite). Dazu die 301-Weiterleitungen von den alten Adressen. Ohne SEO-Plugin gibt `kbs-seo.php` Titel, Beschreibung und Open Graph selbst aus.

## Projektspezifische Konventionen

- **Dynamische Daten:** `{options.kbs.firma.…}`, `{options.kbs.pcvisit.…}`, `{options.kbs.kontakt.…}`.
- **Einstellungsseite „Firmendaten“** (Option `firmendaten`) und ihre Felder sind in `kbs-firma.php` registriert, nicht im Meta-Box-Builder: Das Repo bleibt die einzige Quelle. Recht `edit_pages`.
- **Datenordner:** `wp-content/kbs` über die Konstante `KBS_DATEN`.
- **Snippets, Übergang:** Solange die WPCodeBox-MCP-Schreibfunktionen nicht freigegeben sind, lädt `wp-content/mu-plugins/kbs-loader.php` die Dateien aus `wp-content/kbs/snippets/`. Umstellung auf WPCodeBox:
  1. In WPCodeBox › Einstellungen › MCP die Werkzeuge Create Folder, Create/Update/Enable Snippet freigeben.
  2. `kbs/snippets-sync` (legt die Snippets deaktiviert an).
  3. `wp-content/mu-plugins/kbs-loader.php` löschen.
  4. `kbs/snippets-sync` mit `aktivieren: true`.
- **OhMyEtch:** Häufige Fragen (Accordion), Brotkrumen (Breadcrumbs, automatisch aus der Seitenhierarchie, mit Schema) und das Inhaltsverzeichnis der Datenschutzerklärung (Table of Contents) kommen von OhMyEtch, eingebunden per Key (`ome()` in `lib.mjs`, Sync löst `__REF_Ome…__` auf). Die Navigation bleibt eine eigene Komponente (keine OhMyEtch-Navigation).
- **Buttons:** nur die ACSS-Klassen (`btn--primary`, `btn--primary btn--outline`, `btn--primary-light` und `btn--primary-light btn--outline` auf dunklen Flächen, Größe `btn--s`). Aussehen (Rundung, Schriftstärke, Innenabstand, Rahmen) in `wordpress/etch/acss-buttons.mjs`, übertragen mit `kbs/acss-colors` (`aus_datei: true`) in die ACSS-Einstellungen. Im eigenen CSS nur der Icon-Abstand.
