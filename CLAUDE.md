# Projekt kbs – Website Kotthaus Business Service GmbH

Neue Firmenwebsite (ersetzt die alte Seite auf kotthaus-bs.de) auf Basis von WordPress + Etch. Lokal: Local-Seite „kbs“ (https://kbs.local), MCP-Server `wp-kbs`.

Gemeinsame Standards (Stack, Konventionen, Betrieb): @etch-nodes/CLAUDE.md

Projektspezifisch (Prefix, Aufbau, Ablauf): @docs/development-environment.md

Design dieser Website (nur lokal, nie in etch-nodes): @docs/design.md
Farben und Farbregeln: @docs/farben.md

Seiten und Inhalte: [docs/seitenstruktur.md](docs/seitenstruktur.md). Handbuch für die Redaktion (u. a. KI-Kennzeichnung): [docs/handbuch.md](docs/handbuch.md). Technik-Doku für Administratoren (im Backend unter „Technik“, bei Änderungen an Plugins, Snippets, CSS oder Skripten mitpflegen): [docs/technik.md](docs/technik.md). Ablauf Build → WordPress: [wordpress/README.md](wordpress/README.md). Umzug nach kotthaus-bs.de und Übertragen von Änderungen nach dem Livegang: [docs/umzug-live.md](docs/umzug-live.md) – live gepflegte Inhalte nie ohne Rückfrage überschreiben. Vorlage für Website-Angebote an Kunden (Fragebogen, Angebotstext, Kalkulation): [docs/angebot-vorlage.md](docs/angebot-vorlage.md).

## Arbeitsweise
- Firmenangaben (Name, Adresse, Telefon, Rechtliches, PC-Visit-Links, Angebot) nie ins Markup schreiben: immer `firma('<feld>')` bzw. `{options.kbs.firma.…}` / `{options.kbs.pcvisit.…}`. Gepflegt wird im Backend unter „Firmendaten“.
- `etch-nodes/` ist ein git subtree. Dort nur allgemeine, generische Standards ändern; Projektspezifisches gehört in `docs/`.

## Medien (ki-lokal)
Werkzeuge und Regeln fuer Bilder, Videos und 3D auf der lokalen RTX 5070 Ti:
@D:/Projekte-KI/ki-lokal/CLAUDE.md
@D:/Projekte-KI/ki-lokal/CLAUDE.local.md

- Originale fuer dieses Projekt: D:/Projekte-KI/medien/raw/KBS/
  (neu erzeugte KI-Bilder nach dem Erzeugen dorthin verschieben)
- Bilder erzeugen: python D:/Projekte-KI/ki-lokal/scripts/comfy/generate.py
- Bilder optimieren: node D:/Projekte-KI/ki-lokal/scripts/optimize-images.mjs --src D:/Projekte-KI/medien/raw/KBS --out <Bildordner des Projekts>
- Bildordner dieses Projekts: `wordpress/medien/bilder` (Build kopiert ihn nach `/wp-content/kbs/medien/bilder/`).
- Originale in medien/raw nie veraendern oder loeschen.