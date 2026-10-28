# Sicherheitsprüfungen kotthaus-bs.de

Intern. Prüfung nach etch-nodes (`etch-nodes/docs/sicherheit.md`) mit `node tools/sicherheit-pruefen.mjs <Adresse> [--innen=kbs-live] [--bericht]`: von außen wie ein fremder Besucher, mit `--innen` zusätzlich nur lesend per WP-CLI. Ziel 0 Lücken. Pflicht nach jedem Umzug auf live, danach mindestens monatlich und nach Updates.

Die Prüfung der Anmeldung sperrt die eigene IP-Adresse 15 Minuten für die Anmeldung (nicht für die Website). Mit `--innen=kbs-live` hebt das Werkzeug diese frische Sperre am Ende auf. Security Ninja stuft die Adresse des Büros evtl. als vertrauenswürdig ein – die Firewall dann von einer anderen Adresse prüfen.

## Berichte

| Datum | Adresse | Ergebnis | Bericht |
| --- | --- | --- | --- |
| 2026-10-28 | kbs.local (Probelauf mit kbs-schutz 1.0.0) | 39 ok, 7 Hinweise, 0 Lücken – Hinweise nur lokal (nginx-Version, `.htaccess` gilt lokal nicht, noindex) bzw. `?ver=` von OhMyEtch | – |

## Verlauf

| Datum | Befund | Maßnahme |
| --- | --- | --- |
| 2026-10-28 | Kontaktformular wurde von Robotern missbraucht (Namen aus Zufallsbuchstaben) | Spamschutz in kbs-kontakt (Zeitfeld, Zufallsnamen, höchstens 20 Mails je Stunde), live übertragen |
| 2026-10-28 | Härtung nach etch-nodes fehlte | Snippet kbs-schutz angelegt, lokal geprüft: 0 Lücken |

## Offen

- `?ver=1.6.0` an Skripten von OhMyEtch wird nicht ersetzt (lädt nicht über die üblichen Filter) – Hinweis, keine Lücke.
