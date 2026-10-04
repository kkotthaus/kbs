# Entwicklungsumgebung – projektspezifisch

Stack, allgemeine Konventionen und Betrieb: [etch-nodes](../etch-nodes/CLAUDE.md). Projekt-Prefix (`<prefix>` in etch-nodes): `kbs`.

## Plugins (Stand 2026-10-04)

WordPress 7.1.2 auf beiden Seiten. Spalten: **lokal** = kbs.local, **live** = kotthaus-bs.de (gelesen per `kbs-live`, `wp plugin list`). Automatische Updates sind überall aus.

| Baustein | Slug | Version | lokal | live | Anmerkung |
| --- | --- | --- | --- | --- | --- |
| Etch (+ Etch-Theme 0.0.7) | `etch` | 1.6.8 | aktiv | aktiv | |
| Automatic.css | `automatic-css` | 4.0.1 | aktiv | aktiv | Farbschema „light dark“ (folgt dem Betrachter) |
| OhMyEtch | `oh-my-etch` | 1.6.0 | aktiv | aktiv | Accordion, Breadcrumbs, Table of Contents |
| Meta Box AIO | `meta-box-aio` | 3.12.0 | aktiv | aktiv | Einstellungsseite „Firmendaten“ (im Code registriert) |
| WPCodeBox 2 | `wpcodebox2` | 1.4.1 | aktiv | aktiv | Snippets (Ordner „KBS“); MCP lokal mit Schreibwerkzeugen, live nur lesend |
| MCP Adapter | `mcp-adapter` | 0.6.1 | aktiv | aktiv | **nicht auf 0.7.x aktualisieren** (inkompatibel mit mcp-wordpress-remote) – live wird ein Update angeboten, nicht einspielen. Live Zugang für Claude (`wp-kbs-live`) |
| SEOPress + SEOPress Pro | `wp-seopress`, `wp-seopress-pro` | 10.3 | aktiv | aktiv | Meta-Ausgabe, Open Graph, Sitemap (`/sitemaps.xml`), Indexierung, Auslieferung von `/llms.txt`; Titel und Inhalte der `llms.txt` kommen aus `kbs-seo.php` |
| Duplicator Pro | `duplicator-pro` | 5.0.5 | aktiv | aktiv | Umzug und Sicherung; nie mehr von lokal nach live |
| Etch Font Manager | `etch-font-manager` | 1.0.16 | aktiv | aktiv | hostet **Sora** (Überschriften) selbst: `wp-content/fonts/`, `efm-fonts.css`, vorgeladen, Fallback Systemschriften, **keine Rolle** gesetzt (Zuordnung nur in `acss-schrift.mjs`). Einrichten je Umgebung: Google Fonts › Sora, Subset latin, variabel; Preload an. Manrope kommt weiter aus `main.css` |
| Uplink Media Bridge for Etch | `media-bridge-for-etch` | 2.2.7 | aktiv | aktiv | |
| Uplink Editorial Title | `uplink-editorial-title` | 1.1.3 | aktiv | aktiv | |
| Uplink Unified Ops Center | `uplink-unified-ops-center` | 1.6.0 | aktiv | aktiv | |
| **LiteSpeed Cache** | `litespeed-cache` | 7.9.1 | – | aktiv | Seitencache (Server ist LiteSpeed) und Object-Cache-Drop-in `wp-content/object-cache.php`. Seiten werden 7 Tage gecacht. Den ganzen Cache leeren automatisch: Speichern der Firmendaten (`kbs-firma.php`) und der KI-Darstellung (`kbs-ki.php`) sowie jeder Sync (`kbs-mcp.php`), jeweils über `do_action( 'litespeed_purge_all' )`; Seiten leert LiteSpeed beim Speichern selbst |
| **MainWP Child** | `mainwp-child` | 6.2.1 | – | aktiv | Fernverwaltung über MainWP |
| **Matomo Analytics** | `matomo` | 5.13.1 | – | aktiv | Stand 2026-10-04 **ohne Tracking** (kein Code in den Seiten). Vor dem Einschalten die Datenschutzerklärung anpassen (Abschnitt 5 sagt: keine Analyse-Werkzeuge) |
| **Security Ninja (Premium)** | `security-ninja-premium` | 5.303 | – | aktiv | Sicherheit; darf `/wp-json/mcp/` (Zugang `wp-kbs-live`) nicht sperren |
| Temporary Login Without Password | `temporary-login-without-password` | 1.9.10 | – | inaktiv | nur bei Bedarf einschalten |

Themes: nur das Etch-Theme (die Bricks-Themes von live sind seit 2026-10-04 gelöscht).

Plugins, die nur live laufen, kommen nicht automatisch nach kbs.local; bei „Live → lokal“ (Duplicator von live) kommen sie mit – lokal dann LiteSpeed Cache, MainWP Child und Matomo deaktivieren, Security Ninja prüfen (darf den lokalen MCP-Zugang nicht sperren).

Header und Navigation sind eigene Komponenten (`SiteHeader`, Skript `kbs-navigation.php`). Das globale Etch-Stylesheet `dwc-mega-menu` von EtchMegaMenuPro bleibt trotzdem **immer** erhalten – es ist Voraussetzung dafür, dass das Mega-Menü funktioniert; nicht löschen und nicht als „ungenutztes CSS“ entfernen. SEO: SEOPress Pro gibt Titel, Meta-Beschreibung und Open Graph aus und liefert Sitemap und `/llms.txt`. Die Inhalte kommen aus dem Repo: In `seiten.mjs` hat jede Seite `seo` (Titel mit `firma()`, `faq`, `leistung`, `llms`-Abschnitt), der Build schreibt `daten/seo.json`, `kbs-seo.php` setzt daraus den Titel (ein im Seiteneditor gesetzter SEOPress-Titel geht vor) und die Beschreibung aus dem Auszug, gibt strukturierte Daten als @graph aus (ProfessionalService mit Öffnungszeiten aus „Erreichbarkeit“, Geschäftsführer Einsatzgebiet `areaServed` aus „Einsatzorte“ (Städte) und „Region“ (Gebiete) und `sameAs` aus „Profil-Links“, Service auf Leistungsseiten, FAQPage) und füllt `/llms.txt` (Firma, Kontakt, Profile, Seiten, Fragen je Seite). Vorschaubild für geteilte Links (`og:image`, `twitter:image`, große Twitter-Karte) aus Firmendaten › Vorschaubild, nur wenn die Seite kein eigenes Bild hat und in SEOPress kein Standardbild gesetzt ist. Dazu die 301-Weiterleitungen von den alten Adressen. Ohne SEO-Plugin gibt `kbs-seo.php` Titel, Beschreibung und Open Graph selbst aus.

## Projektspezifische Konventionen

- **Dynamische Daten:** `{options.kbs.firma.…}`, `{options.kbs.pcvisit.…}`, `{options.kbs.kontakt.…}`.
- **Einstellungsseite „Firmendaten“** (Option `firmendaten`) und ihre Felder sind in `kbs-firma.php` registriert, nicht im Meta-Box-Builder: Das Repo bleibt die einzige Quelle. Recht `edit_pages`.
- **Datenordner:** `wp-content/kbs` über die Konstante `KBS_DATEN`.
- **Snippets:** liegen immer in WPCodeBox (Standard aus etch-nodes, seit 2026-10-04 auch lokal): Ordner „KBS“, Schlagwörter `kbs` + Dateiname, Einfügepunkt Root. Angelegt und aktualisiert werden sie nur mit `kbs/snippets-sync` aus `wp-content/kbs/snippets/` (neue Snippets zunächst ausgeschaltet, `aktivieren: true` schaltet ein). Kein Lader, kein mu-plugin. Nach einer PHP-Änderung: bauen, `dist` kopieren, `kbs/snippets-sync`, dann `wpcodebox/list-errored-snippets` muss leer sein. Lokal freigegebene WPCodeBox-MCP-Werkzeuge: List/Get, Create Folder, Create/Update/Enable/Disable Snippet (nicht Delete und Run).
- **OhMyEtch:** Häufige Fragen (Accordion), Brotkrumen (Breadcrumbs, automatisch aus der Seitenhierarchie, mit Schema) und das Inhaltsverzeichnis der Datenschutzerklärung (Table of Contents) kommen von OhMyEtch, eingebunden per Key (`ome()` in `lib.mjs`, Sync löst `__REF_Ome…__` auf). Die Navigation bleibt eine eigene Komponente (keine OhMyEtch-Navigation).
- **Buttons:** nur die ACSS-Klassen (`btn--primary`, `btn--primary btn--outline`, `btn--primary-light` und `btn--primary-light btn--outline` auf dunklen Flächen, Größe `btn--s`). Aussehen (Rundung, Schriftstärke, Innenabstand, Rahmen) in `wordpress/etch/acss-buttons.mjs`, übertragen mit `kbs/acss-colors` (`aus_datei: true`) in die ACSS-Einstellungen. Im eigenen CSS nur der Icon-Abstand.
