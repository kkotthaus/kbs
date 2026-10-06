# Design

Gilt nur für diese Website (nicht in etch-nodes). Farben und Kontraste: [farben.md](farben.md).

## Leitidee

Modern, hell und vertrauenswürdig: IT-Service und Websites für kleine Unternehmen, persönlich und aus der Region. Viel Weißraum, ruhige Flächen, das Markenrot nur als Akzent. Ein feines **Raster** (wie Millimeterpapier/Netzwerk) steht für Technik und Ordnung.

## Bausteine

- **Schriften:** Fließtext **Manrope** Variable, Überschriften **Sora** Variable (technisch-geometrisch, passend zum IT-Thema). Beide aus Google Fonts, selbst gehostet über den Etch Font Manager (`wp-content/fonts/manrope-variable-latin.woff2`, `sora-variable-latin.woff2`; Sora vorgeladen), Lizenzen `wordpress/lizenzen/manrope-OFL.txt` und `sora-OFL.txt`. Welche Schrift wofür, Überschriften-Stärke (700) und Laufweite (-0.03em) stehen in den ACSS-Einstellungen: `wordpress/etch/acss-schrift.mjs`, übertragen mit `kbs/acss-colors` (`aus_datei: true`). Große Titel (Hero, Seitenkopf) mit 700 und Laufweite -0.035em bzw. -0.03em in `main.css`.
- **Website-Icon (Favicon):** Initialen „KBS“ aus dem Logo (K und S Grau `#5E5E5E`, B Rot `#D22430`, Arial fett) auf weißem, abgerundetem Quadrat; Apple-Touch-Icon eckig. Quelle `wordpress/medien/kbs-favicon.svg`.
- **Header:** schwebende, abgerundete Leiste (Pille) mit Unschärfe-Hintergrund; mobil klappt das Menü als Panel darunter auf.
- **Hero und Seitenkopf:** hell, mit Raster und weichem Rot-Glühen oben rechts. Sie folgen Hell/Dunkel. Seitenkopf mit Brotkrumen als Pille und Logo-Wasserzeichen (im dunklen Schema die helle Logo-Variante).
- **Dunkle „Ink“-Flächen** (`--kbs-ink`: Graphit mit Rot-Glühen) nur als Akzent: Notfall-Karte im Hero, Erstgespräch-Karte bei den Leistungen, Aufruf-Panel (CtaBand) und Footer. Sie bleiben in beiden Schemata gleich (`immerHell` in `acss-farben.mjs`).
- **3D-Optik (kräftig):** Tiefe ohne Bilder, nur per CSS. Schatten in Schichten (`--kbs-shadow-s/m/l`: Kontakt, Nähe, Ferne), Lichtkante oben an allen erhabenen Flächen (`--kbs-kante` auf hellen, `--kbs-kante-dunkel` auf Ink- und Markenflächen), plastische Symbole (Verlauf, Licht oben, Schatten unten), Punkte als Kugeln. Alles hat Tiefe:
  - **Erhaben** (Lichtkante + Schichtschatten): Karten, Panels, Header-Leiste und -Schaltflächen, Pillen (Eyebrow, Brotkrumen, Zielgruppen), Akkordeon, Menü-Panels, Meldungen, KI-Plakette.
  - **Buttons** (ACSS hat keine Schatten-Einstellung, deshalb in `main.css`): gefüllt mit Licht oben, Kante unten und farbigem Schlagschatten, umrandet als erhabene Pille; heben sich beim Überfahren, drücken sich beim Klicken ein.
  - **Vertieft:** Formularfelder, aktiver Menüpunkt, Adresszeile der Website-Illustration, getönte Abschnitte (Innenschatten oben/unten).
  - **Karten als Tasten** (Leistungs-, Notfall-, Fernwartungs-, Vorteils- und Schritt-Karten, Seitenkästen, Aufruf-Panel, Akkordeon): fester Sockel unten (`--kbs-hub` 6px, Akkordeon 4px; hell `--base-light` mit Text gemischt, auf Ink-Flächen fester Dunkelton). Beim Überfahren sinkt die Karte um 60 % des Sockels ein und der Schatten wird kleiner, beim Klicken ganz mit Schatten innen – wie ein gedrückter Knopf. Keine Drehung, die Karte bleibt plan. Übergang 0.15s. Die Website-Illustration steht statisch schräg im Raum (8° / -16°).
  - **Große Titel** (Hero, Seitenkopf) mit weichem Textschatten; Footer mit Schatten nach oben als Stufe.
- **Karten:** weiß, Rundung `--kbs-radius-xl` (2rem), Schichtschatten mit Lichtkante. Leistungskarten mit rotem Symbol-Quadrat und Glühen beim Überfahren; Seitenkästen mit auslaufender roter Linie oben.
- **Abschnittsköpfe:** Eyebrow in Versalien mit kurzer roter Linie (zentriert beidseitig).
- **Buttons:** ACSS, Pillenform (`acss-buttons.mjs`). Auf hellen Flächen `btn--primary` / `btn--primary btn--outline`, auf dunklen Flächen `btn--primary` / `btn--primary-light btn--outline`.
- **KI-Kennzeichnung** (Standard aus etch-nodes, `snippets/kbs-ki.php`): Pille mit Funken-Symbol und „KI“ am Bild, aufgeklappt „generiert · Vollständig mit KI erzeugt (Werkzeug)“. Stile dunkel (Ink, Standard), hell, Markenrot; Größe normal/klein; Position je Einstellung oder je Bild. Deckend und in beiden Farbschemata gleich. Eigenes Symbol (z. B. EU-Symbol) unter Firmendaten › Firma & Kontakt. Texte in `KBS_KI_ARTEN`: KI-unterstützt, KI-generiert, KI-bearbeitet.
- **Bewegung:** dezent (Karten drücken sich ein wie Tasten, Pfeile rücken, pulsierender Punkt im Hero-Eyebrow); bei „Bewegung reduzieren“ abgeschaltet.

## Werte

| Token | Wert | Zweck |
| --- | --- | --- |
| `--kbs-radius-l` | 1.25rem | Akkordeon, Inhaltsverzeichnis, Menü-Panels |
| `--kbs-radius-xl` | 2rem | Karten, Panels, Aufruf-Panel |
| `--kbs-grid-line` | Text 9 % | Rasterlinien (auf dunklen Flächen Weiß 6–7 %) |
| `--kbs-header-h` | 5rem | Sprungziel-Abstand unter dem Header |
| `--kbs-kante` | Weiß 100 % (dunkel 10 %) innen oben, Schattenlinie innen unten | Lichtkante heller Flächen |
| `--kbs-kante-dunkel` | Weiß 14 % innen oben, Schwarz 30 % innen unten | Lichtkante auf Ink- und Markenflächen |
| `--kbs-hub`, `--kbs-druck` | 6px (Akkordeon 4px) bzw. 0 → 60 % → 100 % | Sockelhöhe der Karten, Eindrücken beim Überfahren/Klicken |
