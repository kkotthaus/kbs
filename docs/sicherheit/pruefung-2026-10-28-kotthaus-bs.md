# Sicherheitsprüfung kotthaus-bs.de – 2026-10-28

Geprüft mit `node tools/sicherheit-pruefen.mjs https://kotthaus-bs.de --innen=kbs-live --bericht`: von außen wie ein fremder Besucher ohne Anmeldung, dazu von innen per WP-CLI (nur lesend). Verändert wurde nichts; für die Prüfung der Anmeldung gab es sechs Fehlversuche mit einem erfundenen Benutzernamen, die dadurch entstandene Sperre der eigenen IP-Adresse wurde danach aufgehoben.

**Ergebnis:** 48 in Ordnung, 9 Hinweise, 2 Lücken.

| Bereich | Prüfung | Ergebnis | Befund |
| --- | --- | --- | --- |
| Verbindung | Umleitung von http auf https | ✗ Lücke | http:// antwortet mit 302 → https://kotthaus-bs.de/ |
| Konfiguration | WPCodeBox-MCP | ✗ Lücke | an (live muss es aus sein) |
| Versionen | readme.html | ! Hinweis | öffentlich (nennt WordPress) |
| Technik verraten | Keine Versionsnummern von WordPress und Plugins (?ver=) | ! Hinweis | oh-my-etch 1.6.0 |
| Dateien | Daten des Builds /wp-content/kbs/daten/seo.json | ! Hinweis | öffentlich (9229 Bytes) – Inhalt prüfen |
| Dateien | Daten des Builds /wp-content/kbs/daten/weiterleitungen.json | ! Hinweis | öffentlich (551 Bytes) – Inhalt prüfen |
| Zugänge | Anwendungspasswörter | ! Hinweis | 1 vorhanden (z. B. für MCP – live widerrufen) |
| Konfiguration | Datei-Editor im Backend gesperrt | ! Hinweis | Themes und Plugins ließen sich im Backend bearbeiten (nur Administratoren) |
| Konfiguration | Rechte der wp-config.php | ! Hinweis | 644 |
| Aktualität | Inaktive Plugins | ! Hinweis | mainwp-child/mainwp-child.php, temporary-login-without-password/temporary-login-without-password.php |
| Konfiguration | Kommentare | ! Hinweis | open |
| Verbindung | HSTS (Browser immer verschlüsselt) | ✓ in Ordnung | max-age=31536000;, max-age=63072000; includeSubDomains |
| Kopfzeilen | X-Content-Type-Options | ✓ in Ordnung | nosniff |
| Kopfzeilen | Schutz gegen Einbetten in fremde Seiten | ✓ in Ordnung | SAMEORIGIN,SAMEORIGIN |
| Kopfzeilen | Referrer-Policy | ✓ in Ordnung | same-origin |
| Kopfzeilen | Permissions-Policy | ✓ in Ordnung | camera=(), microphone=(), geolocation=(), payment=(), usb=() |
| Kopfzeilen | Server verrät keine Versionen | ✓ in Ordnung | keine Versionsangaben |
| Versionen | WordPress-Version im Quelltext | ✓ in Ordnung | nicht genannt |
| Technik verraten | Server und PHP ohne Versionsnummer | ✓ in Ordnung | Server: LiteSpeed, kein X-Powered-By |
| Technik verraten | Keine Link-Kopfzeilen (Schnittstelle, Kurzlink) | ✓ in Ordnung | keine |
| Benutzernamen | Schnittstelle /wp-json/wp/v2/users | ✓ in Ordnung | keine Liste (401) |
| Benutzernamen | Autorenseite /?author=1 | ✓ in Ordnung | kein Benutzername (403) |
| Benutzernamen | oEmbed nennt den Autor | ✓ in Ordnung | kein Autor genannt |
| Benutzernamen | Sitemap /wp-sitemap-users-1.xml | ✓ in Ordnung | nicht vorhanden (404) |
| Benutzernamen | Sitemap /author.xml | ✓ in Ordnung | nicht vorhanden (404) |
| Anmeldung | XML-RPC (alter Zugang für Passwort-Angriffe) | ✓ in Ordnung | abgeschaltet bzw. gesperrt (403) |
| Anmeldung | Fehlermeldung verrät keine Benutzernamen | ✓ in Ordnung | einheitliche Meldung |
| Anmeldung | Fehlversuche werden gebremst | ✓ in Ordnung | nach wenigen Fehlversuchen gesperrt |
| Schnittstelle | Mediathek ohne Anmeldung | ✓ in Ordnung | nicht aufgelistet (401) |
| Schnittstelle | WordPress-Schnittstelle nur mit Anmeldung | ✓ in Ordnung | /wp-json/wp/v2/pages antwortet mit 401 |
| Dateien | Ordnerauflistung /wp-content/uploads/ | ✓ in Ordnung | keine Auflistung (403) |
| Dateien | Ordnerauflistung /wp-content/kbs/ | ✓ in Ordnung | keine Auflistung (404) |
| Dateien | Ordnerauflistung /wp-content/kbs/medien/ | ✓ in Ordnung | keine Auflistung (404) |
| Dateien | Ordnerauflistung /wp-content/ | ✓ in Ordnung | keine Auflistung (200) |
| Dateien | Fehlerprotokoll /wp-content/debug.log | ✓ in Ordnung | nicht erreichbar (404) |
| Dateien | Fehlerprotokoll /debug.log | ✓ in Ordnung | nicht erreichbar (404) |
| Dateien | Umgebungsdatei /.env | ✓ in Ordnung | nicht erreichbar (404) |
| Dateien | Sicherung der Konfiguration /wp-config.php.bak | ✓ in Ordnung | nicht erreichbar (404) |
| Dateien | Sicherung der Konfiguration /wp-config.php~ | ✓ in Ordnung | nicht erreichbar (404) |
| Dateien | Sicherung der Konfiguration /wp-config.old | ✓ in Ordnung | nicht erreichbar (404) |
| Dateien | Duplicator-Installer /installer.php | ✓ in Ordnung | nicht erreichbar (404) |
| Dateien | Duplicator-Installer /installer-backup.php | ✓ in Ordnung | nicht erreichbar (404) |
| Dateien | Duplicator-Sicherungen /wp-content/backups-dup-pro/ | ✓ in Ordnung | nicht erreichbar (404) |
| Dateien | Duplicator-Sicherungen /wp-content/backups-dup-lite/ | ✓ in Ordnung | nicht erreichbar (404) |
| Dateien | Git-Daten /.git/config | ✓ in Ordnung | nicht erreichbar (404) |
| Dateien | PHP-Datei des Builds /wp-content/kbs/technik.php | ✓ in Ordnung | gibt nichts aus (200) |
| Dateien | PHP-Datei des Builds /wp-content/kbs/snippets/kbs-mcp.php | ✓ in Ordnung | gibt nichts aus (200) |
| Dateien | PHP-Datei des Builds /wp-content/kbs/snippets/kbs-schutz.php | ✓ in Ordnung | gibt nichts aus (200) |
| Betrieb | robots.txt erreichbar | ✓ in Ordnung | Antwort 200 |
| Betrieb | Sitemap (SEOPress) | ✓ in Ordnung | /sitemaps.xml antwortet mit 200 |
| Betrieb | Suchmaschinen erlaubt (live) | ✓ in Ordnung | index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1 |
| Betrieb | Anhangseiten | ✓ in Ordnung | Antwort 404 |
| Zugänge | Administratoren | ✓ in Ordnung | 1 (Anmeldenamen stehen bewusst nicht im Bericht) |
| Zugänge | Rollen | ✓ in Ordnung | administrator 1 |
| Zugänge | Selbst registrieren abgeschaltet | ✓ in Ordnung | aus |
| Konfiguration | Fehlerausgabe auf der Website | ✓ in Ordnung | WP_DEBUG aus |
| Konfiguration | Umgebung | ✓ in Ordnung | production |
| Konfiguration | Schutz-Regeln in der .htaccess | ✓ in Ordnung | Block KBS-Schutz vorhanden |
| Aktualität | Plugins mit Updates | ✓ in Ordnung | alle aktuell |
