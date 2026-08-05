# Änderungsprotokoll

Alle wesentlichen Änderungen werden in diesem Dokument festgehalten. Versionen folgen dem Format `MAJOR.MINOR.PATCH`.

## 0.6.0 – 5. August 2026

### Neu

- Zentrale Verwaltung unter **Medien → KI-Bildkennzeichnung** mit den Bereichen Einstellungen, CSS-Klassen, AI-Philosophie und Impressum.
- Globale, serverseitig geprüfte Label-Standards für Schriftgröße, Abstände, Radius, Glasunschärfe, Standard-Position und Glas-Variante.
- Shortcode `[mgd_ai_label]` für gekennzeichnete Hintergrundbilder in Divi-Containern, inklusive enger Attributvalidierung und interaktionsdurchlässiger Ausgabe.
- Shortcode `[mgd_ai_philosophy]` sowie ein redaktioneller Editor für den lokalen Transparenztext.
- Vorsichtige, idempotente Erstellung einer AI-Philosophie-Seite. Ein Footer-Menü wird nur bei genau einer eindeutig erkannten Footer-Position ergänzt.

### Verbessert

- Der Speichern-Button der Medien-Anhangdetails arbeitet nun auch im Divi-5-Medienfenster mit dem sichtbaren, aktuell ausgewählten Anhang.
- Lokale Plugin-Präsentation: Icon, Banner, Detailansicht und sichere Service-Links in der WordPress-Pluginverwaltung.
- Dokumentation zu Datenschutz, Grenzen der Kennzeichnung, Update-Paketen und Divi-Hintergrundbildern.

### Sicherheit

- Neue Einstellungswerte, Shortcode-Attribute, Textinhalte und Verwaltungsaktionen werden serverseitig validiert, berechtigungsgeprüft und mit WordPress-Nonces abgesichert.
- Keine Änderung an Footer-Menüs bei mehrdeutiger oder fehlender Footer-Position.

## 0.5.3 – 4. August 2026

- Lokale Plugin-Präsentation mit Detailansicht und Service-Links vorbereitet.

## 0.5.2 – 4. August 2026

- GitHub-basierte Update-Prüfung und Status „Teilweise KI generiert“ ergänzt.
