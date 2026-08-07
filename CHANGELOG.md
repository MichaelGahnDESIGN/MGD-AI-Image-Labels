# Änderungsprotokoll

Alle wesentlichen Änderungen werden in diesem Dokument festgehalten. Versionen folgen dem Format `MAJOR.MINOR.PATCH`.

## 0.6.6 – 8. August 2026

### Verbessert

- Die installierte Plugin-Liste im WordPress-Backend zeigt nun auch für die manuell oder über GitHub installierte Erweiterung das lokale MGD-Icon.
- Die Mediathek-Vorschau richtet sich exakt an der sichtbaren Bildfläche statt am häufig deutlich höheren Mediencontainer aus. Alle vier Ecken funktionieren damit gleich.
- Das Vorschau-Label liegt mit einem dezenten, einheitlichen Innenabstand von 18 Pixeln auf dem Bild.

### Sicherheit und Datenschutz

- Das Listen-Icon wird ausschließlich auf `plugins.php` und nur für die eigene Plugin-Zeile aus einer lokalen Paketdatei geladen.

## 0.6.5 – 7. August 2026

### Neu

- Die WordPress-Mediathek zeigt für ein geöffnetes Bild eine rein lokale Live-Vorschau des Labels. Sie reagiert sofort auf Status, Ecke und Glasvariante, speichert aber niemals selbstständig.

### Behoben

- Der Frontend-Fallback erkennt auch nachgeladene Divi-Blogkarten, Lazy-Loading und responsive Bildquellen aus `srcset`.
- Beitragsbilder und Blog-Thumbnails werden zusätzlich über in den WordPress-Metadaten hinterlegte WebP- und AVIF-Quellen eindeutig erkannt.

### Sicherheit und Datenschutz

- Die Vorschau sendet keine Anfrage, verändert keine Bilddatei und nutzt nur feste, lokale Labeltexte.
- Die Frontend-Zuordnung akzeptiert weiterhin ausschließlich exakte lokale Upload-Pfade eines ausdrücklich gekennzeichneten Anhangs.

## 0.6.4 – 5. August 2026

### Behoben

- KI-Labels werden nun auch bei Beitragsbildern und Blog-Archivkarten ausgegeben, wenn ein Theme die übliche WordPress-Klasse `wp-image-{ID}` nicht ausgibt.
- Der lokale Frontend-Fallback erkennt neben dem Original auch alle von WordPress erzeugten Größenvarianten eines ausdrücklich gekennzeichneten Bildes, etwa `-400x284` im Archiv oder `-980x551` im Einzelbeitrag.

### Sicherheit und Datenschutz

- Die Zuordnung erfolgt ausschließlich über exakte Pfade bekannter lokaler WordPress-Uploadvarianten. Externe Bilder, beliebige Dateinamen und unmarkierte Medien bleiben unverändert.

## 0.6.3 – 5. August 2026

### Behoben

- Ein nachgelagerter WordPress-Medien-Speichervorgang ohne KI-Felder kann gespeicherte Kennzeichnungswerte nicht mehr mit Standardwerten überschreiben.
- Teilaktualisierungen eines Anhangs ändern nur die tatsächlich übermittelten Kennzeichnungswerte; Status, Position und Glas-Variante bleiben ansonsten erhalten.

## 0.6.2 – 5. August 2026

### Behoben

- Der GitHub-Updater verwirft nun einen eigenen Release-Cache, sobald dieser nur die installierte oder eine ältere Version enthält. Dadurch blockiert ein kurz vor einer Veröffentlichung gespeicherter Cache keinen neuen Release mehr.
- Bereits als neuer erkannte Releases bleiben weiterhin kurz zwischengespeichert; GitHub wird nicht bei jedem Seitenaufruf angefragt.

## 0.6.1 – 5. August 2026

### Behoben

- Die Speichern-Funktion wird nun auch im separaten Laufzeitfenster des Divi-5-Builders geladen. Zuvor war der Button sichtbar, aber der zugehörige Browser-Handler stand in diesem Fenster nicht zur Verfügung.
- Die Medien-Speicherung arbeitet ohne jQuery-Abhängigkeit und ermittelt Status, Position und Glas-Variante ausschließlich innerhalb des aktuell sichtbaren Anhang-Dialogs.
- Der Speichern-Klick wird im Divi-Dialog früh abgefangen, damit ihn keine Builder- oder Medienbibliotheks-Interaktion überlagert.

### Prüfbarkeit

- Ein eigener Regressionstest stellt sicher, dass der Speichern-Handler bei `?et_fb=1` sowohl im Divi-Editor als auch im zugehörigen App-Fenster ausgeliefert wird.

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
