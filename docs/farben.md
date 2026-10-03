# Farben und Barrierefreiheit

Palette zentral in `wordpress/etch/acss-farben.mjs`, übernehmen per MCP `kbs/acss-colors` (`aus_datei: true`). Nach jeder Änderung `node wordpress/etch/kontrast.mjs` – muss fehlerfrei durchlaufen.

- **Primary** `#D22430` (Rot aus dem Logo): Flächen mit weißer Schrift, Icons. Rote Schrift und Links immer `--primary-dark`.
- **Secondary** `#2F3A45` (Graphit): dunkle Markenflächen über `--secondary-ultra-dark`, Seitenhintergrund `--secondary-ultra-light`.
- **Base**: Text `--base`, Nebentext `--base-semi-dark` (in `main.css` als Mischung aus Text- und Seitenfarbe überschrieben, damit es in beiden Schemata reicht), Rahmen `--base-light`, getönte Abschnitte `--base-ultra-light`, Karten `--white`.
- **Danger** bewusst orange (`#A6400B`), damit Fehlermeldungen nicht wie Markenflächen wirken.
- Nur ACSS-Farbvariablen, keine Hex-Werte im CSS (Ausnahme: Schatten mit festem Dunkelton).

## Hell/Dunkel

- ACSS `website-color-scheme: light dark`: Die Seite folgt automatisch der Einstellung des Betrachters (kein Umschalter). ACSS rechnet alle Farben mit `light-dark()` und tauscht im dunklen Schema die Abstufungen; `--white` wird schwarz.
- Immer gleich (Force light selectors, Liste `immerHell`): `.home-hero`, `.page-hero`, `.cta-band`, `.site-footer`.
- Kontrastpaare (Rollen: Seite, Karte, Fläche) stehen in `kontrast.mjs`.
