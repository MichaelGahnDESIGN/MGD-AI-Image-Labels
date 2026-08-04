# Plugin-Präsentation: Icon, Details und Admin-Links

## Ziel

MGD KI-Bildkennzeichnung erhält eine professionelle, wiedererkennbare Darstellung in der WordPress-Pluginverwaltung. Sie soll vertrauenswürdig wirken, die wichtigsten Hilfen unmittelbar zugänglich machen und ohne Tracking, externe Schriften oder eingebettete Fremdinhalte auskommen.

## Gestaltung

Das statische WordPress-Icon zeigt eine dunkle, leicht glasartige Kachel mit einer roten Bildrahmen-Markierung und einem klaren `AI`-Akzent. Es bleibt bei 128 × 128 Pixeln lesbar und verwendet die MGD-Farben Anthrazit, Weiß und ein zurückhaltendes Rot.

Zusätzlich entsteht eine kurze, reduzierte Motion-Variante für GitHub und die README. Sie wird nicht als Admin-Icon eingesetzt: WordPress-Listenansichten und Einstellungen für reduzierte Bewegung sollen nicht von Animationen beeinflusst werden.

## Technische Bausteine

### Lokale Bilddateien

- `assets/branding/icon-128x128.png` für reguläre WordPress-Ansichten.
- `assets/branding/icon-256x256.png` für hochauflösende Ansichten.
- `assets/branding/banner-772x250.png` für die Detailansicht im WordPress-Modal.
- `assets/branding/icon-motion.gif` ausschließlich für GitHub/README; keine automatische Wiedergabe im WordPress-Backend.

Alle Dateien gehören zum Plugin-Paket. Es gibt keine CDN-URL und keine Anfrage an Drittanbieter.

### WordPress-Integration

- Der Plugin-Header erhält `Plugin URI` und `Author URI` für die kanonische Projekt- und Autorzuordnung.
- Eine neue, klar getrennte Klasse registriert `plugin_row_meta` und ergänzt Links zur Projektseite, Dokumentation, Support, GitHub und zur lokalen Detailansicht.
- Externe Links öffnen sich in einem neuen Tab und erhalten `rel="noopener noreferrer"`.
- Die vorhandene GitHub-Updater-Klasse ergänzt WordPress-Update-Daten um die lokalen Icon- und Banner-URLs.
- Die `plugins_api`-Antwort enthält Icons, Banner, eine Beschreibung, Installationsschritte, FAQ, Änderungsprotokoll, Support- und Projekt-Links. Damit funktioniert „Details anzeigen“ auch ohne Veröffentlichung im WordPress.org-Verzeichnis.

### Details-Modal

Das WordPress-Modal nutzt die native `plugin_information`-Schnittstelle. Es zeigt:

1. eine kurze Erklärung und die fünf Kennzeichnungsarten,
2. Installation und sicheren Speichervorgang,
3. FAQ zur Anzeige, Caches und Updates,
4. ein kurzes Änderungsprotokoll für 0.5.3, 0.5.2 und 0.5.1,
5. Links zu Michael Gahn DESIGN, Support, GitHub und der ausführlichen Wiki.

Das Modal setzt keine Bewertung, Installationszahlen oder WordPress.org-Daten vor, weil diese für ein selbst veröffentlichtes GitHub-Plugin nicht belastbar wären.

## Sicherheit und Datenschutz

- Die neue Ausgabe beschränkt sich auf den WordPress-Adminbereich und öffentliche statische Dateien aus dem Plugin-Verzeichnis.
- HTML in der Update-Detailantwort wird vollständig vom Plugin erzeugt; URLs werden über WordPress-Funktionen validiert und escaped.
- Es werden keine Administrator-, Medien- oder Besucherinformationen an GitHub oder andere Dritte übertragen.
- Die Animation bleibt eine lokale Dekorationsdatei und wird weder durch JavaScript noch durch Tracking ausgelöst.

## Prüfkriterien

- Das Plugin zeigt in der WordPress-Pluginliste ein eigenes, hochauflösendes Icon.
- „Details anzeigen“ öffnet die native WordPress-Ansicht und zeigt die beschriebenen Bereiche.
- Website-, Support-, Dokumentations- und GitHub-Links haben die richtige URL sowie sichere Attribute für neue Tabs.
- Das Update-Objekt enthält nur lokale, HTTPS-basierte Icon- und Banner-URLs.
- Die Paketprüfung bestätigt, dass alle Branding-Dateien in der Release-ZIP liegen.
