# Design

Gilt nur für diese Website (nicht in etch-nodes). Farben und Kontraste: [farben.md](farben.md).

## Leitidee

Modern, hell und vertrauenswürdig: IT-Service und Websites für kleine Unternehmen, persönlich und aus der Region. Viel Weißraum, ruhige Flächen, das Markenrot nur als Akzent. Ein feines **Raster** (wie Millimeterpapier/Netzwerk) steht für Technik und Ordnung.

## Bausteine

- **Schriften:** Fließtext **Manrope** Variable, selbst gehostet (`wordpress/medien/manrope-latin-wght.woff2`, Lizenz `wordpress/lizenzen/manrope-OFL.txt`, `@font-face` in `main.css`). Überschriften **Sora** Variable (Google Fonts, technisch-geometrisch, passend zum IT-Thema; Lizenz `wordpress/lizenzen/sora-OFL.txt`), selbst gehostet über den Etch Font Manager (`wp-content/fonts/sora-variable-latin.woff2`, vorgeladen). Welche Schrift wofür, Überschriften-Stärke (700) und Laufweite (-0.03em) stehen in den ACSS-Einstellungen: `wordpress/etch/acss-schrift.mjs`, übertragen mit `kbs/acss-colors` (`aus_datei: true`). Große Titel (Hero, Seitenkopf) mit 700 und Laufweite -0.035em bzw. -0.03em in `main.css`.
- **Header:** schwebende, abgerundete Leiste (Pille) mit Unschärfe-Hintergrund; mobil klappt das Menü als Panel darunter auf.
- **Hero und Seitenkopf:** hell, mit Raster und weichem Rot-Glühen oben rechts. Sie folgen Hell/Dunkel. Seitenkopf mit Brotkrumen als Pille und Logo-Wasserzeichen (im dunklen Schema die helle Logo-Variante).
- **Dunkle „Ink“-Flächen** (`--kbs-ink`: Graphit mit Rot-Glühen) nur als Akzent: Notfall-Karte im Hero, Erstgespräch-Karte bei den Leistungen, Aufruf-Panel (CtaBand) und Footer. Sie bleiben in beiden Schemata gleich (`immerHell` in `acss-farben.mjs`).
- **Karten:** weiß, Rundung `--kbs-radius-xl` (2rem), weiche Schatten (`--kbs-shadow-s/m/l`). Leistungskarten mit rotem Symbol-Quadrat und Glühen beim Überfahren; Seitenkästen mit auslaufender roter Linie oben.
- **Abschnittsköpfe:** Eyebrow in Versalien mit kurzer roter Linie (zentriert beidseitig).
- **Buttons:** ACSS, Pillenform (`acss-buttons.mjs`). Auf hellen Flächen `btn--primary` / `btn--primary btn--outline`, auf dunklen Flächen `btn--primary` / `btn--primary-light btn--outline`.
- **KI-Kennzeichnung** (Standard aus etch-nodes, `snippets/kbs-ki.php`): Pille mit Funken-Symbol und „KI“ am Bild, aufgeklappt „generiert · Vollständig mit KI erzeugt (Werkzeug)“. Stile dunkel (Ink, Standard), hell, Markenrot; Größe normal/klein; Position je Einstellung oder je Bild. Deckend und in beiden Farbschemata gleich. Eigenes Symbol (z. B. EU-Symbol) unter Firmendaten › Firma & Kontakt. Texte in `KBS_KI_ARTEN`: KI-unterstützt, KI-generiert, KI-bearbeitet.
- **Bewegung:** dezent (Karten heben sich, Pfeile rücken, pulsierender Punkt im Hero-Eyebrow); bei „Bewegung reduzieren“ abgeschaltet.

## Werte

| Token | Wert | Zweck |
| --- | --- | --- |
| `--kbs-radius-l` | 1.25rem | Akkordeon, Inhaltsverzeichnis, Menü-Panels |
| `--kbs-radius-xl` | 2rem | Karten, Panels, Aufruf-Panel |
| `--kbs-grid-line` | Text 9 % | Rasterlinien (auf dunklen Flächen Weiß 6–7 %) |
| `--kbs-header-h` | 5rem | Sprungziel-Abstand unter dem Header |
