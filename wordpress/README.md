# WordPress-Aufbau per MCP

Seiten, Templates, Komponenten und CSS werden im Repo als Code beschrieben, gebaut und über den MCP-Server `wp-kbs` nach kbs.local übertragen.

| Pfad | Zweck |
| --- | --- |
| `etch/lib.mjs` | Erzeugt Etch-Block-Markup, Icons, `firma('<feld>')` |
| `etch/daten.mjs` | Startwerte der Firmendaten, Navigation, Leistungen, Weiterleitungen |
| `etch/komponenten.mjs` | Etch-Komponenten |
| `etch/seiten.mjs` | Seiten und Templates mit allen Texten |
| `etch/css/main.css` | Globales Etch-Stylesheet „KBS“ |
| `etch/acss-farben.mjs`, `etch/acss-buttons.mjs`, `etch/acss-schrift.mjs`, `etch/kontrast.mjs` | Palette, Button-Aussehen und Schrift für ACSS, Kontrastprüfung |
| `etch/build.mjs` | Schreibt `etch/dist/` |
| `snippets/kbs-mcp.php` | MCP-Funktionen `kbs/*` (nur Entwicklung) |
| `snippets/kbs-firma.php` | Einstellungsseite „Firmendaten“, Daten `{options.kbs.firma|pcvisit}` – **live** |
| `snippets/kbs-kontakt.php` | Kontaktformular – **live** |
| `snippets/kbs-navigation.php` | Skript der Hauptnavigation – **live** |
| `snippets/kbs-ki.php` | KI-Kennzeichnung (Standard aus etch-nodes): Mediathek-Felder `ki_art` (ai, generated, modified; leer = keine KI), `ki_werkzeug`, `ki_position`; Einstellungsseite Medien › KI-Kennzeichnung (Option `kbs_ki`); eigenes Symbol `firmendaten.ki_logo`; Editor-Bilder per `render_block`, Etch über `kbs_ki_daten()` und `kiPlakette()` (lib.mjs); Mediathek: Spalte „KI“ und Filter `ki_filter` (Liste), Plakette auf den Kacheln (Raster, Medien-Fenster) – **live** |
| `snippets/kbs-seo.php` | SEO-Titel aus `daten/seo.json`, schema.org (ProfessionalService, Service, FAQPage), Inhalt von `/llms.txt`, 301 von alten Adressen; Meta-Beschreibung/Open Graph nur ohne SEOPress – **live** |
| `medien/` | Firmenlogo (`kbs-logo.svg`, helle Variante `kbs-logo-hell.svg` für dunkle Flächen, Quelle: alte Website kotthaus-bs.de) und PC-Visit-Signet; ausgeliefert unter `/wp-content/kbs/medien/`, Rückfall, wenn unter Firmendaten kein eigenes Bild hochgeladen ist. Website-Icon `kbs-favicon-32/180/192/512.png` (Quelle `kbs-favicon.svg`), Rückfall über den Filter `get_site_icon_url` in `kbs-firma.php`, solange unter Einstellungen › Allgemein kein Website-Icon gesetzt ist |
| `medien/bilder/` | Optimierte Bilder, die das Markup fest einbindet (aus `D:/Projekte-KI/medien/raw/KBS` mit `optimize-images.mjs --out wordpress/medien/bilder`); ausgeliefert unter `/wp-content/kbs/medien/bilder/`. Derzeit leer. Bilder, die die Redaktion austauschen soll (z. B. das Hero-Bild der Startseite, Firmendaten › Startseite), gehören in die **Mediathek** – dort mit `ki_art` kennzeichnen. Nur für fest eingebundene KI-Dateien: Eintrag in `KBS_KI_DATEIEN` (`snippets/kbs-ki.php`), Daten unter `{options.kbs.ki_bilder.<schlüssel>.…}`. |

## Ablauf

```bash
node wordpress/etch/kontrast.mjs
node wordpress/etch/build.mjs
cp -r wordpress/etch/dist/. "$HOME/Local Sites/kbs/app/public/wp-content/kbs/"
```

Dann per MCP `wp-kbs`:

1. Nur bei Änderungen an `snippets/`: `kbs/snippets-sync` (neue Snippets mit `aktivieren: true`), danach `wpcodebox/list-errored-snippets` – muss leer sein. Snippets liegen immer in WPCodeBox, nie als mu-plugin.
2. `kbs/sync-from-files` (`all`) – Komponenten, Templates, Seiten (inkl. Startseite und Auszug), Stylesheet.
3. Nur bei Änderungen an Farben, Buttons oder Schrift: `kbs/acss-colors` mit `aus_datei: true`.
4. Nur bei der Ersteinrichtung: `kbs/import-settings` (`firmendaten`, überschreibt standardmäßig nur leere Felder), `kbs/site-title`, `kbs/flush-permalinks`.

Der Sync überschreibt Änderungen, die im Etch-Editor an diesen Seiten gemacht wurden. Texte deshalb im Repo ändern.
