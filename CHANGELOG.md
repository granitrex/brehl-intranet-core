# Changelog

Alle wichtigen Änderungen an My Brehl Core werden in dieser Datei dokumentiert.

## [Unveröffentlicht]

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
