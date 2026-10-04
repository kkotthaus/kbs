# Projekt kbs – Website Kotthaus Business Service GmbH

Neue Firmenwebsite (ersetzt die alte Seite auf kotthaus-bs.de) auf Basis von WordPress + Etch. Lokal: Local-Seite „kbs“ (https://kbs.local), MCP-Server `wp-kbs`.

Gemeinsame Standards (Stack, Konventionen, Betrieb): @etch-nodes/CLAUDE.md

Projektspezifisch (Prefix, Aufbau, Ablauf): @docs/development-environment.md

Design dieser Website (nur lokal, nie in etch-nodes): @docs/design.md
Farben und Farbregeln: @docs/farben.md

Seiten und Inhalte: [docs/seitenstruktur.md](docs/seitenstruktur.md). Handbuch für die Redaktion (u. a. KI-Kennzeichnung): [docs/handbuch.md](docs/handbuch.md). Ablauf Build → WordPress: [wordpress/README.md](wordpress/README.md).

## Arbeitsweise
- Firmenangaben (Name, Adresse, Telefon, Rechtliches, PC-Visit-Links, Angebot) nie ins Markup schreiben: immer `firma('<feld>')` bzw. `{options.kbs.firma.…}` / `{options.kbs.pcvisit.…}`. Gepflegt wird im Backend unter „Firmendaten“.
- `etch-nodes/` ist ein git subtree. Dort nur allgemeine, generische Standards ändern; Projektspezifisches gehört in `docs/`.
