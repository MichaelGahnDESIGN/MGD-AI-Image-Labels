# MGD KI-Bildkennzeichnung

Ein schlankes WordPress-Plugin für die transparente, barrierefreie Kennzeichnung von Bildern, bei deren Erstellung oder Bearbeitung KI beteiligt war.

Es ergänzt die WordPress-Mediathek um eine Auswahl pro Bild und gibt ein dezentes Label direkt auf dem Bild aus. Das Plugin lädt keine externen Schriften, Skripte, Analysewerkzeuge oder Tracking-Dienste.

> **Hinweis:** Die Entscheidung, ob und wie Inhalte gekennzeichnet werden müssen, hängt vom konkreten Inhalt, Verwendungszweck und geltendem Recht ab. Dieses Plugin ist ein technisches Hilfsmittel und keine Rechtsberatung.

## Funktionen

- Auswahl direkt in den Anhang-Details der WordPress-Mediathek
- Fünf Statuswerte: Keine KI, Mit KI erstellt, Teilweise KI generiert, Mit KI bearbeitet und Deepfake / täuschend echt
- Position des Labels in allen vier Bildecken
- Helle, dunkle oder automatische Glas-Variante
- Lokale, datensparsame Ausgabe ohne Drittanbieter
- Barrierefreie Semantik; Deepfake-Hinweise enthalten einen erweiterten Screenreader-Text
- Unterstützung für WordPress-Bilder, Beitragsbilder sowie Divi-5- und klassische Divi-Bildmodule
- Eigener Speichern-Button für eine nachvollziehbare Medienbearbeitung

## Voraussetzungen

- WordPress 6.0 oder neuer
- PHP 8.1 oder neuer
- Schreibrechte für die normale WordPress-Plugin-Installation

## Installation

1. Lade unter **Plugins → Installieren → Plugin hochladen** eine Release-ZIP hoch.
2. Aktiviere **MGD KI-Bildkennzeichnung**.
3. Öffne unter **Medien → Mediathek** ein Bild in den Anhang-Details.
4. Wähle bei **KI-Kennzeichnung** den passenden Status, die Position und die Glas-Variante.
5. Klicke auf **Kennzeichnung speichern**.
6. Leere bei Bedarf den Seiten- oder CDN-Cache und kontrolliere die Ausgabe im Frontend.

Die Einstellungen werden als drei WordPress-Anhangsmetadaten gespeichert. Beim Plugin-Update bleiben sie erhalten.

## Kennzeichnungsarten

| Auswahl | Sichtbares Label | Zweck |
| --- | --- | --- |
| Keine KI | Kein Label | Keine Kennzeichnung ausgeben |
| Mit KI erstellt | `AI GENERATED` | Bild wurde vollständig oder überwiegend mit KI erzeugt |
| Teilweise KI generiert | `AI PARTIALLY GENERATED` | Bild enthält erkennbare, KI-generierte Bestandteile |
| Mit KI bearbeitet | `AI MODIFIED` | Bestehendes Bild wurde mit KI wesentlich verändert |
| Deepfake / täuschend echt | `AI DEEPFAKE` | Bild kann einen authentischen Eindruck erwecken |

## Gestaltung und Barrierefreiheit

Das Badge ist bewusst klein und dezent. Position, helle/dunkle Glasoptik und ein kontrastreicher Fallback sorgen dafür, dass es auf unterschiedlichen Motiven erkennbar bleibt.

- Die sichtbare Kennzeichnung nutzt `role="note"`.
- Bei Deepfakes ergänzt ein nicht sichtbarer, aber vorlesbarer Erklärungstext die Bedeutung.
- Alt-Texte, Bilddateien und vorhandene Inhalte werden nicht überschrieben.
- Bei deaktivierter Hintergrundunschärfe bleibt eine kontrastreiche Fläche sichtbar.

Die Styles liegen vollständig lokal in `assets/css/frontend.css` und können in einem Child Theme gezielt überschrieben werden.

## Technische Arbeitsweise

Das Plugin speichert nur die Auswahl pro Anhang:

```text
_mgd_ail_status     generated | partially-generated | modified | deepfake | none
_mgd_ail_position   top-left | top-right | bottom-left | bottom-right
_mgd_ail_theme      auto | light | dark
```

Die Frontend-Ausgabe ist nicht destruktiv. Das Plugin umschließt nur die jeweilige Bildausgabe zur Laufzeit und verändert weder die Datei in der Mediathek noch gespeicherte Divi-Layouts.

Einige Themes geben Beitragsbilder direkt als `<img>` aus. Dafür enthält das Plugin einen lokalen Fallback für klassisches Divi und WordPress-Beitragsausgaben. Die Zuordnung bleibt auf explizit gekennzeichnete Medien beschränkt.

## Entwicklung

### Struktur

```text
assets/
  css/frontend.css                 Lokale Frontend-Gestaltung
  js/media-save.js                 Speichern im Medien-Dialog
includes/
  class-attachment-meta.php        Validierung und Zugriff auf Anhangsmetadaten
  class-github-updater.php          Sichere Prüfung öffentlicher GitHub-Releases
  class-image-renderer.php          Frontend-Ausgabe und Divi-Kompatibilität
  class-media-ajax.php              Geschützter Speichern-Endpunkt
  class-media-fields.php            Felder in den Anhang-Details
  class-plugin.php                  Plugin-Initialisierung
tests/                              Eigenständige PHP-Tests ohne WordPress-Installation
mgd-ai-image-labels.php            Plugin-Header und Startpunkt
```

### Tests

Die Tests sind ohne WordPress-Installation ausführbar:

```bash
for test in tests/test-*.php; do
  php "$test" || exit 1
done
```

Zusätzlich sollte jede Änderung geprüft werden:

```bash
php -l mgd-ai-image-labels.php
php -l includes/class-image-renderer.php
git diff --check
```

## Releases und Updates

Jede Version erhält einen Git-Tag im Format `vX.Y.Z` und ein ZIP-Release, dessen oberster Ordner `mgd-ai-image-labels` heißt. WordPress erkennt neuere öffentliche GitHub-Releases im üblichen Plugin-Update-Zyklus und zeigt sie in **Dashboard → Aktualisierungen** beziehungsweise **Plugins** an.

Die Prüfung ruft höchstens alle zwölf Stunden ausschließlich die öffentliche GitHub-Release-API dieses Repositories auf. Sie benötigt keine Zugangsdaten und überträgt keine Bilder, Bildmetadaten, Besucher- oder Nutzerdaten. Ein Release wird nur angeboten, wenn die Version neuer ist und eine exakt passende ZIP-Datei über `https://github.com` bereitsteht. Bei Netzwerk- oder Validierungsfehlern bleibt WordPress beim bisherigen Stand und führt kein Update aus.

Automatische WordPress-Updates können Website-Administratoren wie bei anderen Plugins bewusst in der Plugin-Verwaltung aktivieren oder deaktivieren. Vor jedem Update empfiehlt sich ein getestetes Backup, zum Beispiel über UpdraftPlus.

## Sicherheit und Datenschutz

- Alle Eingaben werden serverseitig auf erlaubte Werte beschränkt.
- Änderungen erfordern die passende WordPress-Berechtigung und einen Nonce.
- Es werden keine personenbezogenen Daten, Bilder oder Analysedaten an externe Dienste übertragen.
- Das Plugin speichert keine API-Schlüssel, Passwörter oder Tokens.
- Die Update-Prüfung verwendet keine GitHub-Zugangsdaten und akzeptiert nur HTTPS-Pakete vom festgelegten öffentlichen GitHub-Repository.
- Bitte veröffentliche niemals `wp-config.php`, Backups, Logs mit personenbezogenen Daten oder Zugangsdaten im Repository.

## Mitwirken

Fehlerberichte und Verbesserungsvorschläge sind willkommen. Bitte beschreibe bei einem Fehler möglichst:

- WordPress-, PHP-, Theme- und Plugin-Version
- verwendeten Bildtyp (Mediathek, Beitragsbild, Divi-Bildmodul)
- erwartetes und tatsächliches Verhalten
- Schritte zur Reproduktion

Keine Zugangsdaten, privaten Bild-URLs oder personenbezogenen Daten in Issues veröffentlichen.

## Lizenz

Dieses Plugin steht unter der [GNU General Public License v2.0 oder neuer](LICENSE).
