# Umzug auf kotthaus-bs.de und Betrieb danach

Anleitung für den einmaligen Umzug der neuen Website von **kbs.local** auf **kotthaus-bs.de** (ersetzt die alte Seite) und für alle Änderungen danach. Allgemeine Regeln: [etch-nodes/docs/betrieb.md](../etch-nodes/docs/betrieb.md#veröffentlichen-mit-duplicator-pro). Ablauf Build → WordPress: [wordpress/README.md](../wordpress/README.md).

**Grundsatz nach dem Livegang:** kbs.local ist die Entwicklung, kotthaus-bs.de bekommt nur noch gezielte Änderungen. Inhalte, die live gepflegt werden, ändert niemand ohne Nachfrage – auch kein Sync und kein Claude.

---

## Teil 1: Umzug (einmalig)

### 1. Vorbereiten auf kbs.local

1. **Stand bauen und übertragen**
   ```bash
   node wordpress/etch/kontrast.mjs
   node wordpress/etch/build.mjs
   cp -r wordpress/etch/dist/. "$HOME/Local Sites/kbs/app/public/wp-content/kbs/"
   ```
   Dann per MCP `wp-kbs`: `kbs/sync-from-files` (`all`) und `kbs/acss-colors` (`aus_datei: true`).
2. **Snippets prüfen:** Sie laufen seit 2026-10-04 aus WPCodeBox (kein Lader mehr). Nach dem letzten Build `kbs/snippets-sync` ausführen; in WPCodeBox stehen alle Snippets im Ordner „KBS“ auf `enabled`, `wpcodebox/list-errored-snippets` ist leer.
3. **Firmendaten vervollständigen** (Backend › Firmendaten):
   - *Hoster der Website* (`recht_hoster`) – wird in der Datenschutzerklärung (Abschnitt Hosting) gebraucht, ist noch leer.
   - *Empfänger Kontaktformular* prüfen (leer = Admin-E-Mail).
4. **Aufräumen:** Beispiel- und Testinhalte (Beiträge, Seiten, Medien) in den Papierkorb und den Papierkorb leeren. Nur die Seiten aus `seiten.mjs` bleiben veröffentlicht.
5. **SEOPress:** Sitemap nur mit Seiten (Beiträge, Kategorien abwählen), Autoren-, Datums-, Kategorie- und Schlagwort-Archive auf noindex bzw. Autorenarchive deaktivieren. Optional Standardbild für Social (1200 × 630) setzen.
6. **KI-Kennzeichnung:** In der Mediathek bei allen KI-Bildern `ki_art` gesetzt (Filter „nur mit KI“ / „ohne KI“ über der Liste).
7. **Alte Seite erfassen** (auf kotthaus-bs.de, noch alt): Liste aller Adressen sichern, z. B. aus `https://kotthaus-bs.de/sitemap.xml` oder `/wp-sitemap.xml`. Jede Adresse, die nicht 1:1 weiterlebt, braucht einen Eintrag in `weiterleitungen` (`wordpress/etch/daten.mjs`) → bauen, kopieren.
8. **Stand im Repo festhalten:** committen und pushen; Commit-Kennung notieren (= Stand, der live geht).

### 2. Paket erstellen (Duplicator Pro auf kbs.local)

1. Duplicator Pro › Pakete › Neu.
2. Nichts ausschließen, insbesondere **nicht** `wp-content/kbs/` und die Tabellen `wp_wpcb_*` (WPCodeBox).
3. Paket bauen, **Archiv** und **installer.php** herunterladen.

### 3. Alte Seite sichern (kotthaus-bs.de)

1. Mit dem dort installierten Duplicator ein Paket der **alten** Seite erstellen und Archiv + Installer herunterladen. Zusätzlich beim Hoster eine Sicherung von Dateien und Datenbank anlegen.
2. Klären, welche Inhalte der alten Seite noch gebraucht werden (Texte, Bilder, Downloads) – sie werden vorher lokal übernommen, nicht nach dem Umzug.
3. E-Mail ist nicht betroffen, solange DNS und MX-Einträge unverändert bleiben (nur die Website wird ersetzt).

### 4. Probelauf (empfohlen)

Erst auf einer Test-Subdomain installieren (z. B. `neu.kotthaus-bs.de`, mit SSL), dort Teil 1 Schritt 6 („Nachher“) prüfen, dann erst die Hauptdomain. Die Test-Subdomain danach löschen oder per Passwort schützen und auf noindex lassen.

### 5. Installieren auf kotthaus-bs.de

Je nach Duplicator auf der alten Seite:

- **Duplicator Pro mit „Import“:** Duplicator › Import › Archiv hochladen → der Installer startet. Die alte Seite wird dabei komplett ersetzt.
- **Klassisch (geht immer):** Inhalt des Webroots der alten Seite sichern und entfernen, `installer.php` und das Archiv in den Webroot laden, `https://kotthaus-bs.de/installer.php` aufrufen.

Im Installer:

- Datenbank: die bisherige Datenbank der Domain (wird überschrieben) oder eine neue, leere.
- **Neue Adresse mit `https://`**: `https://kotthaus-bs.de` (ohne `www`, wenn die alte Seite ohne lief – sonst gleich wie bisher, damit Google-Einträge passen).
- Ersetzen der Adresse in der ganzen Datenbank aktiviert lassen (Duplicator ersetzt `https://kbs.local` überall, auch serialisiert).
- Admin-Zugang: vorhandene Benutzer aus kbs.local kommen mit – Passwörter danach prüfen.

### 6. Nachher (live) – Prüfliste

**Sicherheit zuerst:**

1. **`wp-config.php`: `define( 'WP_ENVIRONMENT_TYPE', 'local' );` entfernen** (oder `'production'`). Die Zeile kommt aus kbs.local mit. Steht sie dort, sind die MCP-Baufunktionen (`kbs/*`) live aktiv!
2. `KBS_MCP_LIVE` darf **nicht** in der `wp-config.php` stehen.
3. MCP Adapter (0.6.1, kommt mit) bleibt aktiv – er ist der Zugang für Claude (`kbs-live`, siehe [Zugang für Claude](#zugang-für-claude-kbs-live)). WPCodeBox › Einstellungen › MCP: nur die **lesenden** Werkzeuge frei, Create/Update/Enable/Disable/Delete/Run aus (Soll „live“ der Prüfliste in etch-nodes/docs/betrieb.md).
4. Alle Anwendungspasswörter aus kbs.local widerrufen (sie kommen mit). Danach live ein neues Anwendungspasswort „Claude kbs-live“ anlegen (siehe unten). Benutzer und Admin-Passwörter prüfen.
5. Installer-Dateien von Duplicator löschen (Duplicator weist darauf hin).

**Betrieb:**

6. Einstellungen › Lesen: „Suchmaschinen davon abhalten …“ **ausschalten**.
7. SMTP einrichten und testen (Kontaktformular einmal absenden), Admin-E-Mail auf eine echte Adresse.
8. Lizenzen auf der neuen Domain aktivieren: Etch, Automatic.css, OhMyEtch, Meta Box AIO, WPCodeBox, SEOPress Pro, Duplicator Pro.
9. Automatic.css einmal speichern (CSS neu erzeugen), Einstellungen › Permalinks einmal speichern.
10. Cron: `define( 'DISABLE_WP_CRON', true );` in die `wp-config.php` und beim Hoster einen Cronjob alle 5–15 Minuten auf `https://kotthaus-bs.de/wp-cron.php`.
11. WPCodeBox: alle KBS-Snippets `enabled`.

**Prüfen im Browser:**

12. Alle Seiten, Hell/Dunkel, mobil, Kontaktformular, Fernwartungs-Links, KI-Plakette im Hero.
13. Alte Adressen aus Schritt 1.7 aufrufen → 301 auf die neue Seite.
14. `https://kotthaus-bs.de/sitemaps.xml`, `/robots.txt` (mit Sitemap-Zeile), `/llms.txt`.
15. Startseite und eine Leistungsseite im [Rich Results Test](https://search.google.com/test/rich-results) (FAQ, Organisation).
16. Google Search Console: Domain bestätigen (falls noch nicht), Sitemap einreichen; in den ersten Wochen SEOPress › 404-Protokoll ansehen und fehlende Weiterleitungen ergänzen.

**Ab jetzt nie wieder per Duplicator von kbs.local nach live** – das würde alles überschreiben, was live gepflegt wurde.

---

## Teil 2: Danach – kbs.local entwickelt, kotthaus-bs.de bekommt Änderungen

### Wem gehört was?

| Bereich | Quelle | Übertragung nach live |
| --- | --- | --- |
| PHP-Snippets (`wordpress/snippets/`) | Repo | ja, nach Prüfung lokal |
| Stylesheet `main.css`, ACSS-Einstellungen (Farben, Buttons, Schrift) | Repo | ja |
| Dateien unter `wp-content/kbs/` (Bilder, Logos, Schrift, `daten/*.json`) | Repo | ja |
| Etch-Komponenten, Templates, Seiten aus `seiten.mjs` | Repo | **nur mit Prüfung** (s. u.) – live im Editor geänderte Inhalte werden nicht überschrieben |
| Seitenauszüge (Meta-Beschreibungen) | Repo, live änderbar | wie Seiten |
| Firmendaten, Mediathek (inkl. KI-Felder), KI-Darstellung, SEOPress-Einstellungen und SEO-Felder einzelner Seiten, Benutzer | **live** | **nie** automatisch; Firmendaten-Import nur nach Rückfrage und nur mit `nur_leere` oder ausdrücklicher Feldliste |
| Neue Seiten/Beiträge, die live angelegt werden | **live** | nie |

### Voraussetzung: Prüfmodus für den Sync (noch umzusetzen)

Heute überschreibt `kbs/sync-from-files` Seiten, Templates, Komponenten und Auszüge ohne Prüfung. Vor der ersten Übertragung nach live wird der Sync erweitert:

- Beim Speichern merkt er sich je Inhalt eine Prüfsumme dessen, was er geschrieben hat (Post-Meta, z. B. `_kbs_sync_hash`).
- Neuer Modus `pruefen: true` (nur lesend) meldet je Seite/Template/Komponente: *unverändert*, *neu im Repo*, *im Repo geändert* oder **live geändert** (aktueller Inhalt ≠ zuletzt geschriebener).
- Beim Schreiben werden „live geändert“-Inhalte **übersprungen** und gemeldet. Überschreiben nur mit ausdrücklicher Liste (`ueberschreiben: ['<slug>', …]`) nach Rückfrage.
- Auszüge nur setzen, wenn sie live noch dem zuletzt geschriebenen Stand entsprechen.

Bis das umgesetzt ist: vor jeder Übertragung den Live-Inhalt der betroffenen Seiten mit `kbs/get-content` bzw. im Editor vergleichen und nur einzelne, geprüfte Teile übertragen.

### Zugang für Claude: kbs-live

Nach dem Umzug ist kotthaus-bs.de für Claude unter dem Namen **kbs-live** erreichbar – wie `gcdb-staging`, aber für die echte Live-Seite: per **SSH** (Alias `kbs-live`, WP-CLI) und per **MCP** (Server `wp-kbs-live`).

**Regeln für kbs-live:**

- Standard ist **lesen** (Seiten, Einstellungen, Snippets, Logs ansehen, vergleichen).
- Schreiben, Hochladen, Sync, Snippet-Änderungen nur auf **ausdrückliche Anweisung** und nach dem Ablauf unten; live gepflegte Inhalte nie ohne Rückfrage überschreiben.
- Die Baufunktionen `kbs/*` sind live per MCP nicht verfügbar (kein `KBS_MCP_LIVE` in der `wp-config.php`). Gebraucht werden sie nur für eine Übertragung – dann per WP-CLI und nur für diesen einen Aufruf:
  ```bash
  ssh kbs-live "cd ~/public_html && wp --user=kkotthaus --exec=\"define('KBS_MCP_LIVE', true);\" eval '…'"
  ```
- Was MCP live sonst anbietet (WordPress-Grundfunktionen, Meta Box, lesende WPCodeBox-Werkzeuge), wird ebenfalls nur lesend genutzt, außer auf ausdrückliche Anweisung.

**Einrichten (nach dem Umzug):**

1. **SSH-Schlüssel:** liegt schon bereit (`~/.ssh/kbs_live`, öffentlicher Teil `~/.ssh/kbs_live.pub`, Kommentar `claude-kbs-live`). Den öffentlichen Schlüssel beim Hoster für den SSH-Benutzer der Domain kotthaus-bs.de hinterlegen (z. B. CyberPanel › SSH-Zugang bzw. `~/.ssh/authorized_keys`).
2. **SSH-Alias** in `~/.ssh/config` (Server von kotthaus-bs.de: 152.53.13.207 – nicht derselbe wie gcdb-staging; Benutzer beim Hoster nachsehen):
   ```
   Host kbs-live
       HostName 152.53.13.207
       User <ssh-benutzer>
       Port 22
       IdentityFile ~/.ssh/kbs_live
       IdentitiesOnly yes
   ```
   Test: `ssh kbs-live "cd ~/public_html && wp core version && wp option get home"` → `https://kotthaus-bs.de`.
3. **Anwendungspasswort:** live unter Benutzer › Profil › Anwendungspasswörter „Claude kbs-live“ anlegen.
4. **MCP-Server** in `D:\Projekte\.mcp.json` ergänzen (wie `wp-gcdb-staging`, ohne `NODE_EXTRA_CA_CERTS`, die Live-Seite hat ein echtes Zertifikat):
   ```json
   "wp-kbs-live": {
     "command": "cmd",
     "args": ["/c", "npx", "-y", "@automattic/mcp-wordpress-remote@latest"],
     "env": {
       "WP_API_URL": "https://kotthaus-bs.de/wp-json/mcp/mcp-adapter-default-server",
       "WP_API_USERNAME": "kkotthaus",
       "WP_API_PASSWORD": "<Anwendungspasswort>"
     }
   }
   ```
   Claude Code neu starten, dann `mcp-adapter-discover-abilities` auf `wp-kbs-live`: es dürfen **keine** `kbs/*`-Funktionen und keine schreibenden `wpcodebox/*`-Werkzeuge auftauchen.
5. **Bot-Schutz des Servers:** kotthaus-bs.de antwortet heute (2026-10-04) auf Anfragen ohne Browser-Kennung mit **403** (z. B. `curl`), auch auf `/wp-json/`. Prüfen, ob das nach dem Umzug noch gilt; falls ja, beim Hoster bzw. in der LiteSpeed-/Firewall-Einstellung freigeben:
   - `/wp-json/mcp/` für den MCP-Zugang (authentifiziert per Anwendungspasswort),
   - die Crawler der Suchmaschinen und KI-Suchdienste (Googlebot, Bingbot, OAI-SearchBot, ChatGPT-User, PerplexityBot, Claude-SearchBot …), sonst sehen sie Seite, Sitemap und `/llms.txt` nicht.
6. **Eintrag in `D:\Projekte\CLAUDE.md`** (Abschnitt kbs) von „geplant“ auf „eingerichtet“ setzen.

**Abschalten**, falls der Zugang nicht mehr gebraucht wird: Anwendungspasswort „Claude kbs-live“ widerrufen, Eintrag aus `.mcp.json` entfernen, öffentlichen Schlüssel beim Hoster löschen.

### Ablauf einer Änderung

1. **Lokal entwickeln und prüfen** auf kbs.local (Build → kopieren → Sync, Browser, `kontrast.mjs`).
2. **Committen und pushen.**
3. **Live sichern:** Duplicator-Paket auf kotthaus-bs.de (oder Sicherung beim Hoster) – vor jeder größeren Übertragung.
4. **Dateien hochladen:** `wordpress/etch/dist/` nach `~/public_html/wp-content/kbs/` (per `tar` über SSH). Dateien, die nicht mehr gebaut werden, nur melden, nicht löschen.
5. **Snippets** (nur wenn PHP geändert), einer von zwei Wegen:
   - **Von Hand:** in WPCodeBox das betroffene Snippet öffnen, Code aus `wordpress/snippets/<datei>.php` einfügen, speichern. Für einzelne Dateien am einfachsten.
   - **Per Snippet-Sync:** in WPCodeBox › Einstellungen › MCP kurzzeitig „Update Snippet“ (und „List Snippets/Folders“) freigeben, `kbs_mcp_snippets_sync( [ 'nur' => [ '<datei ohne .php>' ] ] )` per WP-CLI, danach die Freigaben wieder entziehen.

   Danach im Backend prüfen, dass alle KBS-Snippets `enabled` sind (WPCodeBox schaltet fehlerhafte Snippets selbst ab).
6. **Prüfen, dann übertragen** (Etch-Inhalte):
   1. Sync mit `pruefen: true` → Liste ansehen.
   2. Bei **live geändert**: nachfragen – Änderung erst ins Repo übernehmen (Live-Inhalt lesen, in `seiten.mjs`/`komponenten.mjs` einarbeiten, neu bauen) oder diesen Inhalt auslassen.
   3. Sync ausführen; nur die gewünschten Bereiche (`what`: `stylesheet`, `components`, `templates`, `pages`).
7. **ACSS** (nur wenn Farben/Buttons/Schrift geändert): `kbs_mcp_acss_colors( [ 'aus_datei' => true ] )` per WP-CLI.
8. **Live im Browser prüfen.**

Kleine reine CSS-Änderung: Schritte 1–4, dann nur `what: stylesheet` – betrifft keine Inhalte.

### Live → lokal (Stand holen)

Damit kbs.local die echten Inhalte (Firmendaten, Mediathek, live angelegte Seiten) kennt, gelegentlich in **diese** Richtung kopieren:

1. Duplicator-Paket auf kotthaus-bs.de erstellen, auf kbs.local installieren (Adresse `https://kbs.local`).
2. Danach auf kbs.local: `WP_ENVIRONMENT_TYPE` wieder `local`, MCP Adapter 0.6.1 aktiv (nicht 0.7.x), Anwendungspasswort für MCP neu anlegen, WPCodeBox-MCP-Werkzeuge wieder freigeben, „Suchmaschinen abhalten“ an, SMTP aus bzw. auf Testpostfach.
3. Build kopieren und syncen, damit lokal wieder der Repo-Stand gilt.

Diese Richtung überschreibt nur kbs.local und ist unkritisch.

### Wichtig

- Kein Duplicator-Paket von kbs.local mehr nach live.
- Keine Inhalte live per Sync oder MCP überschreiben, die live geändert wurden, ohne Rückfrage.
- Firmendaten, Mediathek und SEO-Felder werden **live** gepflegt; Änderungen dort bei Bedarf über „Live → lokal“ zurückholen.
- Nach Updates von Etch, OhMyEtch oder ACSS: erst lokal testen, dann live einspielen und Header, Akkordeon, Brotkrumen prüfen.
