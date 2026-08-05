# My Brehl Core

WordPress-Plugin mit modularen Intranet-Funktionen und flexiblen
Elementor-Widgets für das Mitarbeiterportal von Brehl.

## Aktueller Stand

- Plugin-Version: `3.26.0`
- News auf Basis von WordPress-Beiträgen
- Module für Urlaub, Fahrzeugschäden und Benachrichtigungen
- Modul für allgemeine Unternehmensdokumente
- Zentrale Elementor-Widget-Verwaltung
- Gemeinsame Widget-Basisklasse und KPI-Datenservice
- Globale Design-Tokens

Die für WordPress.org gedachte Kurzbeschreibung befindet sich weiterhin in
[`README.txt`](README.txt). Änderungen zwischen den Versionen stehen im
[`CHANGELOG.md`](CHANGELOG.md).

## Voraussetzungen

- WordPress-Installation
- Elementor für die Elementor-Widgets
- Eine mit der eingesetzten WordPress-Version kompatible PHP-Version

Die genauen Mindestversionen sind im aktuellen Quellstand noch nicht verbindlich
dokumentiert und sollten vor einer öffentlichen Veröffentlichung festgelegt
werden.

## Lokale Installation

1. Diesen Ordner nach `wp-content/plugins/brehl-intranet-core` kopieren.
2. Das Plugin **My Brehl Core** im WordPress-Backend aktivieren.
3. Die gewünschten My-Brehl-Widgets in Elementor einsetzen.

Alternativ kann aus dem Ordner ein ZIP-Archiv gebaut und über
**Plugins → Installieren → Plugin hochladen** installiert werden.

## Projektstruktur

```text
brehl-intranet-core.php   Plugin-Einstiegspunkt
includes/core/            Gemeinsame Dienste und Grundlagen
includes/elementor/       Elementor-Widgets und Widget-Verwaltung
includes/modules/         Fachmodule
includes/system/          Übergreifende Systemfunktionen
assets/css/               Stylesheets
assets/js/                JavaScript
uninstall.php             Deinstallationslogik
```

## Git-Workflow

Der stabile Hauptzweig heißt `main`. Neue Arbeiten entstehen in einem kurzen
Feature-Zweig, zum Beispiel:

```text
feature/news-widget
fix/login-validation
chore/release-3.2.0
```

Empfohlener Ablauf:

1. `main` aktualisieren.
2. Feature- oder Fix-Zweig erstellen.
3. Kleine, nachvollziehbare Commits anlegen.
4. Änderung in einer WordPress-Testinstallation prüfen.
5. Pull Request beziehungsweise Merge Request nach `main` erstellen.
6. Freigegebene Version im Changelog ergänzen und mit `vX.Y.Z` markieren.

Zugangsdaten, lokale Konfiguration, Abhängigkeiten und erzeugte ZIP-Dateien
werden nicht versioniert.

## Qualitätsprüfung

Vor jedem Merge sollten mindestens folgende Prüfungen erfolgen:

- PHP-Syntaxprüfung aller PHP-Dateien
- Aktivierung in einer WordPress-Testinstallation
- Elementor-Editor und betroffene Widgets testen
- Rollen, Berechtigungen und Formulare prüfen
- Responsive Darstellung kontrollieren
- Deaktivierung und erneute Aktivierung testen

## Dokumentenschutz

Das Modul „Unternehmensdokumente“ ist ausschließlich für allgemeine, nicht
vertrauliche Unterlagen vorgesehen. Dateien aus der normalen WordPress-Mediathek
können über ihre direkte URL erreichbar sein. Persönliche Dokumente,
Lohnabrechnungen und andere vertrauliche Dateien dürfen dort nicht abgelegt
werden; dafür ist ein späteres Modul mit geschützter Dateiauslieferung
vorgesehen.

## Rollen und Zugriffe

- **Administrator:** vollständiger Zugriff auf WordPress und My Brehl.
- **Personalverwaltung:** Zugriff ausschließlich auf freigegebene
  My-Brehl-Verwaltungsfunktionen im Frontend; kein Zugriff auf das reguläre
  WordPress-Backend.
- **Mitarbeiter:** Zugriff auf das Mitarbeiterportal und eigene Einreichungen;
  keine Verwaltungs- oder WordPress-Backend-Rechte.

Die Personalverwaltung besitzt einzelne My-Brehl-Berechtigungen. Sie darf keine
Administratoren erstellen, Rollen hochstufen, Plugins installieren oder
WordPress-Einstellungen verändern.

## Versionsverwaltung

Das Projekt verwendet semantische Versionsnummern (`MAJOR.MINOR.PATCH`).
Veröffentlichungen erhalten ein Git-Tag wie `v3.1.0`.

## Lizenz und Nutzung

Interne Software der Brehl GmbH. Eine Weitergabe oder öffentliche
Veröffentlichung ist nur nach vorheriger Freigabe vorgesehen.
