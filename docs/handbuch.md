# Handbuch für die Redaktion

## Firmendaten

Name, Adresse, Telefon, Erreichbarkeit, Rechtliches, PC-Visit-Links und das Angebot („Kostenloses Erstgespräch“) pflegen Sie im Backend unter **Firmendaten**. Die Website liest alle Angaben von dort; im Text der Seiten steht nichts davon fest. Nach dem Speichern wird der Seitencache automatisch geleert.

**Region und Einsatzorte** (Firmendaten › Firma & Kontakt):

- **Region** – kurzer Name, z. B. „Bergisches Land und Rheinland“. Erscheint in den Texten als „in der Region …“ (Startseite, Über uns).
- **Einsatzorte** – alle Orte mit Kunden, durch Komma getrennt. Sie stehen einmal sichtbar auf „Über uns“ („Zu unseren Kunden gehören Unternehmen in …“), dazu für Google und KI-Suchdienste in den strukturierten Daten und in `/llms.txt`. So wird die Website für diese Orte gefunden, ohne dass jede Seite alle Orte nennt. Keine eigenen Seiten je Ort anlegen – Google wertet fast gleiche Ortsseiten ab.
- Dieselben Orte im **Google-Unternehmensprofil** als Einzugsgebiet eintragen; dort wirken sie für die Suche in der Nähe am stärksten.
- **Kontaktformular** – anzeigen oder ausblenden. Ausgeblendet steht auf der Kontaktseite ein Hinweis mit Telefon und E-Mail.

**Vorschaubild für Links** (Firmendaten › Firma & Kontakt): erscheint, wenn jemand einen Link zur Website teilt (WhatsApp, LinkedIn, Teams …), und bei Suchmaschinen und KI-Suche. Format 1200 × 630 px, JPEG oder PNG; wichtige Inhalte in die Mitte, manche Dienste schneiden quadratisch zu. Die Vorlage liegt unter `D:/Projekte-KI/medien/raw/KBS/kbs-vorschaubild.html` (im Browser öffnen, Text anpassen, als Bild speichern). Seiten mit eigenem Beitragsbild nutzen dieses.

**Profil-Links** (Firmendaten › Firma & Kontakt): eine Adresse je Zeile – Google-Unternehmensprofil, LinkedIn, XING, Facebook. Nicht sichtbar; Google und KI-Dienste verbinden daran die Profile mit der Firma. Nur vollständige Adressen mit `https://` werden übernommen.

**Hero-Bild der Startseite** (Firmendaten › Startseite): Bild aus der Mediathek wählen (Querformat, mindestens 1344 px breit; das Motiv gehört nach rechts, links steht der Text), optional ein zweites für das dunkle Farbschema. Den Alternativtext und – bei KI-Bildern – die KI-Kennzeichnung (`KI-Nutzung`, Werkzeug, Position des Symbols, z. B. „unten rechts“) tragen Sie beim Bild in der Mediathek ein. Leer = kein Bild im Hero.

## KI-generierte Bilder und Videos

Seit dem 2. August 2026 verlangt die EU-KI-Verordnung (Art. 50): Bilder und Videos, die mit KI erzeugt oder so verändert wurden, dass sie echt wirken können, müssen als künstlich erkennbar sein. Dazu kommt das Wettbewerbsrecht: Bilder dürfen nicht über Tatsachen täuschen (z. B. Büro, Team, Referenzen).

**So kennzeichnen Sie ein Bild:** in der **Mediathek** das Bild öffnen (auch direkt beim Hochladen im Medien-Fenster) und im Kasten **KI-Kennzeichnung** bei **KI-Nutzung** wählen:

| Auswahl | Wann |
| --- | --- |
| **KI-unterstützt** | KI wurde für einzelne Details eingesetzt |
| **KI-generiert** | Das Bild ist vollständig mit KI erzeugt |
| **KI-bearbeitet** | Ein echtes Foto wurde mit KI verändert (z. B. Hintergrund ersetzt, Personen entfernt oder ergänzt) |

Leer lassen, wenn keine KI im Spiel war. Optional tragen Sie das **Werkzeug** ein (z. B. „Firefly“). Auf der Website erscheint am Bild eine kleine Plakette **„KI“**; fährt man mit der Maus darüber (am Handy: antippen), klappt sie auf und zeigt die Art der KI-Nutzung. Screenreader lesen den Hinweis im Alternativtext vor. Verdeckt die Plakette etwas Wichtiges, wählen Sie am Bild eine andere **Position**.

**In der Mediathek** sehen Sie gekennzeichnete Bilder sofort: In der Rasteransicht und im Medien-Fenster (beim Einfügen eines Bildes) trägt das Vorschaubild oben rechts eine dunkle Plakette mit der Kennung. In der Listenansicht zeigt die Spalte **KI** Kennung und Werkzeug; über die Auswahl **KI: alle Medien** oben in der Liste filtern Sie nach Bildern mit oder ohne KI bzw. nach der Art.

**Aussehen einstellen:** Menü **Medien › KI-Kennzeichnung** – Position, Stil (dunkel, hell, Markenrot), Größe und ob das Werkzeug genannt wird. Ein eigenes Symbol (z. B. das offizielle EU-Symbol, sobald es veröffentlicht ist) hinterlegen Sie unter **Firmendaten › Firma & Kontakt › KI-Symbol**.

> **Wichtig:** Fotos von Kunden, Mitarbeitern oder anderen Personen nicht ohne deren Einwilligung in KI-Dienste hochladen, auch nicht zum Bearbeiten – das ist eine Datenverarbeitung bei einem fremden Anbieter. Prüfen Sie außerdem, ob die Nutzungsbedingungen des Werkzeugs die Veröffentlichung erlauben.
