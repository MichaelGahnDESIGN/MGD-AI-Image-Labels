# Divi-Speichern und Plugin-Verwaltung – Design

**Datum:** 2026-08-05  
**Projekt:** MGD KI-Bildkennzeichnung  
**Zielversion:** wird im Implementierungsplan festgelegt

## Ziel

Die KI-Kennzeichnung muss im Divi-5-Medienmodal genauso zuverlässig gespeichert werden wie in der WordPress-Mediathek. Zusätzlich erhält das Plugin unter **Medien → KI-Bildkennzeichnung** eine zentrale, verständliche Verwaltung mit den Reitern **Einstellungen**, **CSS-Klassen**, **AI-Philosophie** und **Impressum**.

## Abgrenzung und Reihenfolge

Die Arbeit wird in zwei aufeinander aufbauende Lieferungen geteilt:

1. **Stabilität:** Divi-Medienmodal erkennen, korrekte Felder lesen und über den bestehenden geschützten AJAX-Endpunkt speichern.
2. **Verwaltung:** zentrale Optionen, Shortcodes, CSS-Helfer, AI-Philosophie und Informationsseite.

Der bestehende Medienstatus, die Position und die Glas-Variante bleiben pro Bild erhalten. Das Plugin verändert weder Originalbilder noch gespeicherte Divi-Layouts.

## Architektur

### Divi-kompatibles Speichern

Die JavaScript-Datei erhält einen klar abgegrenzten Resolver für den sichtbaren Medien-Dialog. Er verwendet zuerst das aktive WordPress-Details-Panel und sucht bei einem Divi-Medienframe nur innerhalb des sichtbaren Frames. Niemals dürfen Werte aus ausgeblendeten oder zuvor geöffneten Dialogen gelesen werden.

Der bestehende AJAX-Endpunkt bleibt zuständig für Nonce, konkrete Anhangsberechtigung, Bild-MIME-Prüfung, Whitelist und Speicherung. Es gibt keine neue öffentliche API und keine Speicherung von Nutzerdaten.

### Zentrale Verwaltung

Eine neue Verwaltungs-Klasse registriert genau eine Unterseite unter **Medien**. Die vier Reiter werden durch getrennte View-Dateien gerendert:

- **Einstellungen:** globale Designstandards und Shortcode-Übersicht.
- **CSS-Klassen:** Vorschauen, kopierbare Klassen und Hintergrundbild-Beispiele.
- **AI-Philosophie:** editorfähiger, sanitierter Erklärungstext und Seitenerstellung.
- **Impressum:** Projekt-, Lizenz-, Sicherheits- und Service-Informationen.

Die Optionsdaten werden in einer einzigen, klar benannten WordPress-Option gespeichert. Nur ein Administrator mit `manage_options` darf sie ändern. Die Seitenerstellung verlangt zusätzlich `publish_pages` und einen eigenen Nonce.

## Design-Standards und Priorität

Zentrale Optionen liefern die Standardwerte für:

- Schriftgröße,
- Innen- und Außenabstand,
- Eckenradius,
- Glas-Stärke,
- helle oder dunkle Standardvariante,
- Standardposition.

Diese Werte werden ausschließlich als CSS-Variablen ausgegeben und serverseitig auf sichere Bereiche begrenzt. Ein Bild kann weiterhin seinen eigenen Status, seine Position und seine Glas-Variante besitzen; diese Einzelbildwerte haben Vorrang.

## Shortcodes und CSS-Klassen

`[mgd_ai_label]` gibt ein Label für eine Bild-ID oder eine Hintergrundfläche aus. Unterstützte Attribute sind `image_id`, `class`, `offset_x` und `offset_y`. Die Werte werden streng normalisiert; unbekannte Attribute und HTML werden nicht übernommen.

Im Reiter **CSS-Klassen** werden die fünf Statusklassen und ein Einbindebeispiel angezeigt. Die Klasse kann beispielsweise an einem Divi-Abschnitt oder einer Divi-Zeile hinterlegt werden. Abstände für Hintergrundbilder werden über die sicheren Shortcode-Attribute oder CSS-Variablen gesetzt.

## AI-Philosophie

Ein vorbereiteter, anpassbarer Standardtext erklärt den verantwortungsvollen KI-Einsatz. Die Ausgabe erfolgt über `[mgd_ai_philosophy]`. Ein Administrator kann einmalig eine Seite „AI-Philosophie“ mit diesem Shortcode anlegen.

Das Plugin versucht nur dann, die neue Seite ins Footer-Menü aufzunehmen, wenn genau ein Menüstandort eine eindeutige Footer-Bezeichnung trägt und diesem Standort ein Menü zugeordnet ist. Andernfalls wird die Seite angelegt und eine verständliche Meldung erklärt den manuellen letzten Schritt. Bestehende Menüs werden nie überschrieben.

## Impressum

Der Reiter zeigt Pluginname, Version, Lizenz, Links zu Website, Dokumentation, Repository und Support sowie die wesentlichen Datenschutz- und Sicherheitsgrundsätze. Es lädt keine Drittanbieter-Ressourcen.

## Tests und Verifikation

- JavaScript-Regressionstest für sichtbare WordPress- und Divi-Medienbereiche.
- PHP-Tests für Options-Whitelist, Shortcode-Normalisierung und Berechtigungsgrenzen.
- Test für die sichere Seitenerstellung ohne Menüüberschreibung.
- Bestehende Tests, PHP-Syntaxprüfung und Paketprüfung.
- Manueller Browser-Smoke in WordPress und im Divi-5-Medienmodal nach einem Backup.

## Nicht im Umfang

- Keine eigene Divi-5-Module oder Bearbeitung des Divi-Cores.
- Keine automatische rechtliche Bewertung eines Bildes.
- Keine externen Schriften, Tracker, Analysewerkzeuge oder KI-Dienste.
- Kein Überschreiben oder Löschen vorhandener Seiten, Menüs, Bilder oder Einstellungen.
