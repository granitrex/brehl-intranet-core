# Changelog

Alle wichtigen Änderungen an My Brehl Core werden in dieser Datei dokumentiert.

## [Unveröffentlicht]

## [3.9.2] - 2026-08-04

- Urlaubsanträge ausdrücklich für Mitarbeiter, Personalverwaltung und Administratoren freigegeben.
- Andere WordPress-Rollen können keine Urlaubsanträge über den Portal-Endpunkt einreichen.

## [3.9.1] - 2026-08-04

- Personalverwaltung und Administratoren können neben Mitarbeitern eigene Krankmeldungen einreichen.
- Berechtigungsprüfung des Krankmeldungsformulars an den bereits funktionierenden Urlaubsablauf angeglichen.

## [3.9.0] - 2026-08-04

- Neues eigenständiges Krankmeldungsmodul mit geschützter Datenhaltung ergänzt.
- Drei Elementor-Widgets für Übersicht, Krankmeldung und persönlichen Status hinzugefügt.
- Bescheinigungen werden geschützt in der Datenbank gespeichert und nur berechtigten Personen ausgeliefert.
- Personalverwaltung kann Krankmeldungen im Portal einsehen und als zur Kenntnis genommen markieren.
- Mitarbeiter erhalten Status und Rückmeldungen ohne Zugriff auf das WordPress-Backend.
- Neue Krankmeldungen erscheinen als Kennzahl und Benachrichtigung in der Personalverwaltung.

## [3.8.2] - 2026-08-04

- Glocke und Profilbild als gleich große abgerundete Quadrate vereinheitlicht.
- Klaren Abstand zwischen Benachrichtigungen und Benutzerprofil ergänzt.

## [3.8.1] - 2026-08-04

- Rauten-Platzhalter im Benachrichtigungs-Widget durch eine klare Glocke ersetzt.
- Benachrichtigungszähler sauber am neuen Icon ausgerichtet.

## [3.8.0] - 2026-08-04

- Urlaub in drei eigenständige Elementor-Widgets für Übersicht, Antrag und Status aufgeteilt.
- Antragsformular optisch überarbeitet und gegen Theme- sowie Elementor-Stile stabilisiert.
- Einheitlichen, responsiven My-Brehl-Kalender anstelle des Browser-Standardkalenders ergänzt.
- Personalverwaltung kann Jahresanspruch und Übertrag je Mitarbeiter direkt im Portal pflegen.
- Bestehendes kombiniertes Urlaubs-Widget bleibt für vorhandene Elementor-Seiten kompatibel.

## [3.7.0] - 2026-08-04

- Urlaubsanträge erscheinen direkt in der Frontend-Personalverwaltung.
- Personalverwalter können Anträge im Portal genehmigen, ablehnen und mit einer Rückmeldung versehen.
- Berechtigungsprüfung auf die eigene Urlaubsverwaltungs-Capability umgestellt.
- Nach einer Entscheidung erfolgt die sichere Rückleitung in das Portal statt ins WordPress-Backend.

## [3.6.3] - 2026-07-31

- Personalnummer wird bei neuen Mitarbeitern ohne Präfix als WordPress-Benutzername verwendet.
- Personalverwaltung kann in der Mitarbeiterbearbeitung einen sicheren Passwort-Link erneut versenden.
- Passwort-E-Mail wird bereits beim Anlegen über den offiziellen WordPress-Ablauf ausgelöst.
- Fehler beim technischen Mailversand werden in der Frontend-Verwaltung angezeigt.

## [3.6.2] - 2026-07-31

- Starke Passwörter auch im offiziellen WordPress-Link zur eigenen Passwortvergabe serverseitig erzwungen.
- Einheitliche Passwortprüfung für Mitarbeiter und Personalverwaltung zentralisiert.
- Anfangspasswort der Personalverwaltung bleibt parallel gültig, bis der Mitarbeiter es über den sicheren Link ändert.

## [3.6.1] - 2026-07-31

- Bearbeiten von Mitarbeitern direkt auf derselben Frontend-Seite stabilisiert.
- Anfangspasswort wird durch die Personalverwaltung vergeben.
- Serverseitige Passwortregeln: mindestens 12 Zeichen sowie Großbuchstabe, Kleinbuchstabe, Zahl und Sonderzeichen.
- Passwortbestätigung ergänzt; Passwörter werden weder angezeigt noch im Klartext per E-Mail versendet.
- Neue Mitarbeiter erhalten zusätzlich die offizielle E-Mail mit einem sicheren Link zur Vergabe eines eigenen Passworts.
- Bestehende Mitarbeiter können optional ein neues Passwort erhalten.

## [3.6.0] - 2026-07-31

- Geschützten Frontend-Bereich für die Personalverwaltung ergänzt.
- Neues Elementor-Widget „My Brehl – Personalverwaltung“ eingeführt.
- Mitarbeiterkonten können im Frontend angelegt und bearbeitet werden.
- Personalnummer, Abteilung, Position, Telefon, Standort und Verzeichnissichtbarkeit pflegbar.
- Mitarbeiteranmeldung kann deaktiviert und wieder aktiviert werden.
- Personalverwaltung kann ausschließlich Mitarbeiterkonten bearbeiten, keine Administratoren oder Rollen mit höheren Rechten.
- Übersicht für Mitarbeiterzahl sowie offene Urlaubs- und Fahrzeugvorgänge ergänzt.

## [3.5.1] - 2026-07-31

- Doppelte Personalverwaltungs- und Mitarbeiterrollen aus älteren Plugin-Versionen werden zusammengeführt.
- Bestehende Benutzer werden vor dem Entfernen einer alten Rolle in die zentrale Rolle übernommen.
- Andere WordPress- und Erweiterungsrollen bleiben unverändert.

## [3.5.0] - 2026-07-31

- Rollen Administrator, Personalverwaltung und Mitarbeiter zentral definiert.
- Personalverwaltung mit getrennten My-Brehl-Berechtigungen für Personal, News, Urlaub, Krankmeldungen, Fahrzeugschäden, Dokumente und Benachrichtigungen vorbereitet.
- Kritische WordPress-Rechte bleiben ausschließlich Administratoren vorbehalten.
- Personalverwaltung und Mitarbeiter werden nach der Anmeldung in das Intranet statt in das WordPress-Backend geleitet.
- Grundlage für einen geschützten Frontend-Verwaltungsbereich geschaffen.

## [3.4.0] - 2026-07-31

- Unternehmensnews und Systemmeldungen in einer gemeinsamen Benachrichtigungsanzeige zusammengeführt.
- Urlaubs-, Fahrzeug- und manuelle Meldungen erscheinen nun auch in der Benutzerleiste.
- Globale Meldungen erhalten einen persönlichen Lesestatus je Mitarbeiter.
- Zielverknüpfungen werden nach dem Markieren als gelesen geöffnet.
- Benachrichtigungs-KPI an die gemeinsame Datenquelle angebunden.
- Abweichenden News-Lesestatus-Schlüssel im bisherigen Widget korrigiert.

## [3.3.2] - 2026-07-31

- Dokumentkarten um eine deutlich sichtbare Download-Aktion ergänzt.
- Vorschau über „Öffnen“ bleibt parallel verfügbar.
- Aktionen für schmale Karten und Smartphones responsiv angeordnet.

## [3.3.1] - 2026-07-31

- Dateien können direkt in der Dokumentmaske ausgewählt oder hochgeladen werden.
- Manuelles Kopieren einer Mediathek-URL ist nicht mehr erforderlich.
- Die offizielle WordPress-Medienauswahl wird verwendet.

## [3.3.0] - 2026-07-31

- Eigenes Modul für allgemeine Unternehmensdokumente ergänzt.
- Dokumentkategorien, Datei-URL, Versionsangabe und zeitlich begrenzte Neu-Kennzeichnung eingeführt.
- Neues Elementor-Widget mit Suche, Kategorienfiltern und responsivem Kartenraster ergänzt.
- Kartenlayout, Spalten, Abstände, Farben, Typografie, Radien und Schatten in Elementor konfigurierbar.
- Persönliche Mitarbeiterdokumente bewusst nicht aufgenommen; sie bleiben einem getrennten, geschützten Modul vorbehalten.

## [3.2.1] - 2026-07-31

- Globale Elementor- und Theme-Hoverfarben vom News-Karten-Button isoliert.
- Sichtbaren Tastaturfokus für die weiterhin vollständig klickbare Karte beibehalten.

## [3.2.0] - 2026-07-31

- Unternehmensnews-Widget vollständig über Elementor konfigurierbar gemacht.
- Kategorieauswahl, responsive Spalten, Kartenabstände und Bildhöhe ergänzt.
- Beitragsbild, Kurztext, Datum, Autor, Lesedauer, Kommentare, Badges und Weiterlesen-Hinweis einzeln schaltbar.
- Kartenfarben, Schatten, Radien, Innenabstände und Typografie als Stiloptionen ergänzt.
- Bestehende Unternehmensnews, Lesestatus, Reaktionen und Kommentare bleiben kompatibel.

## [3.1.0] - 2026-07-31
- Zentrale Elementor-Widget-Verwaltung eingeführt.
- Gemeinsame Basisklasse für neue My-Brehl-Widgets ergänzt.
- KPI-Datenlogik in einen wiederverwendbaren Service ausgelagert.
- Globale Design-Tokens stehen nun als CSS-Variablen bereit.
- Bestehende Widgets und Elementor-Seiten bleiben vollständig kompatibel.

## [3.0.3]
- Universelles natives Elementor-Widget „My Brehl – KPI-Karte“.
- Dynamische Datenquellen für Urlaub, Benachrichtigungen, Aufgaben, Fahrzeugschäden, News und Mitarbeiterzahl.
- Frei einstellbare Titel, Einheit, Untertitel, Icon, Badge und Verlinkung.
- Umfangreiche Elementor-Stile für Karte, Hover, Icon und Typografie.
- Responsive Darstellung und Live-Vorschau im Elementor-Editor.

## [3.0.2]
- Begrüßungs-Widget als frei konfigurierbarer Elementor-Baustein erweitert.
- Tageszeitabhängige Ansprache, Benutzername, Datum und Unterzeile separat steuerbar.
- Neue Elementor-Designoptionen für Typografie, Farben, Hintergrund, Rahmen, Schatten, Abstände und Ausrichtung.

## [3.0.1]
- Dashboard bleibt vollständig in Elementor aufgebaut.
- Neue native Elementor-Widgets: Urlaub, Fahrzeugschaden und Meine Aufgaben.
- Urlaubs-Widget kann wahlweise nur die Resturlaub-Kachel oder den vollständigen Urlaubsbereich anzeigen.
- Der bestehende Komplett-Dashboard-Shortcode bleibt nur aus Kompatibilitätsgründen erhalten und ist nicht mehr der empfohlene Aufbau.

## [3.0.0]
- Neues responsives Mitarbeiter-Dashboard mit persönlicher Begrüßung.
- KPI-Karten für Resturlaub, Fahrzeugmeldungen, Aufgaben und nächsten Urlaub.
- Einheitlicher Schnellzugriff für zentrale Intranet-Bereiche.
- Neuer Shortcode `[my_brehl_dashboard]`; bestehender Dashboard-Shortcode bleibt kompatibel.
- Neues zentrales Dashboard-Designsystem für Desktop und Smartphone.

## [2.7.0]
- Neue Urlaubsverwaltung mit Antragsformular im Mitarbeiterportal.
- Resturlaub, genehmigte und offene Urlaubstage als Kennzahlen.
- Halbe Urlaubstage, Sonderurlaub und unbezahlter Urlaub.
- Genehmigungsprozess mit Benachrichtigungen und Verwaltungsnotizen.
- Urlaubskonten je Mitarbeiter und Kalenderjahr im Backend.
- Neue Shortcodes: [my_brehl_urlaub] und [my_brehl_urlaub_kpi].

## [2.6.0]
- Neues Modul „Fahrzeugschäden“
- Frontend-Formular mit Foto-Upload
- Persönliche Übersicht der eigenen Schadenmeldungen
- Statusverwaltung im WordPress-Backend
- Benachrichtigungen bei neuer Meldung und Statusänderung

## [2.5.0]
- Neues My-Brehl-Grundsystem
- Zentrale Aufgabenverwaltung
- Benachrichtigungszentrale
- Aktivitätsprotokoll
- Personal-Dashboard
- Globale Suche
- Neue Shortcodes für Elementor

## [2.4.0]
- Vorherige Version
