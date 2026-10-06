# Farben und Barrierefreiheit

Palette zentral in `wordpress/etch/acss-farben.mjs`, übernehmen per MCP `kbs/acss-colors` (`aus_datei: true`). Nach jeder Änderung `node wordpress/etch/kontrast.mjs` – muss fehlerfrei durchlaufen.

- **Primary** `#D22430` (Rot aus dem Logo): Flächen mit weißer Schrift, Icons. Rote Schrift und Links immer `--primary-dark`.
- **Secondary** `#2F3A45` (Graphit): dunkle Akzentflächen über `--secondary-ultra-dark` (`--kbs-ink` in main.css), Seitenhintergrund `--secondary-ultra-light`.
- **Base**: Text `--base`, Nebentext `--base-semi-dark` (in `main.css` als Mischung aus Text- und Seitenfarbe überschrieben, damit es in beiden Schemata reicht), Rahmen `--base-light`, getönte Abschnitte `--base-ultra-light`, Karten `--white`.
- **Danger** bewusst orange (`#A6400B`), damit Fehlermeldungen nicht wie Markenflächen wirken.
- Nur ACSS-Farbvariablen, keine Hex-Werte im CSS (Ausnahme: Schatten mit festem Dunkelton und Lichtkanten mit festem Weißton, `--kbs-kante` per `light-dark()`).

## Hell/Dunkel

- **Umschalter** im Header (`.scheme-toggle`, Skript in `kbs-navigation.php`): setzt die ACSS-Klasse `scheme--light` bzw. `scheme--dark` am `<html>` und speichert die Wahl im localStorage (`kbs-farbschema`, Hinweis in der Datenschutzerklärung). Entspricht die Wahl dem Gerät, wird sie gelöscht. Bilder je Schema über `scheme-bild--hell` / `scheme-bild--dunkel` (keine `<picture>`-Media-Query, die würde den Umschalter ignorieren).
- ACSS `website-color-scheme: light dark`: Ohne Wahl folgt die Seite der Einstellung des Geräts. ACSS rechnet alle Farben mit `light-dark()` und tauscht im dunklen Schema die Abstufungen; `--white` wird schwarz.
- Immer gleich (Force light selectors, Liste `immerHell`): die dunklen Akzentflächen `.service-card--cta`, `.cta-band`, `.site-footer` und die KI-Plakette `.ki-plakette` (auf Bildern immer deckend und gleich). Hero, Seitenkopf und die Notfall-Karte im Hero sind hell und folgen dem Schema.
- Kontrastpaare (Rollen: Seite, Karte, Fläche) stehen in `kontrast.mjs`.

## Fokus und Links

- Fokusring: `--primary` (Links), bei ACSS-Buttons über `--focus-color` auf hellem Grund `--primary-dark`, auf dunklen Flächen `--primary-light` (main.css). ACSS-Standard für `btn--primary` (`--primary-light`) reicht auf Weiß nicht.
- Links im Fließtext (`p`, `li`, `label`, `dd` ohne Klasse) sind unterstrichen, nicht nur farbig (WCAG 1.4.1).
- Prüfung: `node wordpress/etch/kontrast.mjs` und Lighthouse (Kategorie Barrierefreiheit) auf allen Seiten.
