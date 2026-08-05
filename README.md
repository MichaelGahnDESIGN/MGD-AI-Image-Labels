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
- Eigener Speichern-Button für eine nachvollziehbare Medienbearbeitung – auch im WordPress-Medienfenster des Divi-5-Builders
- Zentrale Verwaltung unter **Medien → KI-Bildkennzeichnung** mit vier klar getrennten Bereichen
- Globale, streng validierte Standards für Schriftgröße, Abstände, Radius, Glasunschärfe, Standard-Position und Glas-Variante
- Sicherer Hintergrundbild-Shortcode für Divi-Container: `[mgd_ai_label]`
- Redaktionell pflegbare AI-Philosophie mit Shortcode und vorsichtiger, optionaler Seitenerstellung
- Professionelles, lokal ausgeliefertes Icon und Banner im WordPress-Update- und Detaildialog
- Native Detailansicht in der Pluginliste mit Installation, FAQ und Änderungsprotokoll
- Website-, Dokumentations-, Support- und GitHub-Links; externe Links öffnen sicher in einem neuen Tab

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

### Sicher speichern – auch im Divi-5-Builder

Der Button **Kennzeichnung speichern** speichert Status, Position und Glas-Variante für genau den gerade geöffneten Medien-Anhang. Im Divi-5-Medienfenster sucht das Skript bewusst zuerst innerhalb des sichtbaren Anhang-Details-Dialogs und nicht global in der Seite. So werden die Werte nicht versehentlich aus einem verdeckten oder vorherigen Dialog gelesen. Nach erfolgreicher Speicherung kann der Anhang gewechselt oder das Medienfenster neu geöffnet werden; die drei Werte werden erneut aus den WordPress-Anhangsmetadaten geladen.

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

### Globale Standards und individuelle Bildwerte

Unter **Medien → KI-Bildkennzeichnung → Einstellungen** stehen globale Ausgangswerte für alle Labels zur Verfügung:

- Schriftgröße, Außen- und Innenabstände sowie Eckenradius
- Glasunschärfe
- bevorzugte Glas-Variante und Position

Alle Werte werden serverseitig auf enge, dokumentierte Grenzen geprüft. Das verhindert fehlerhafte oder unerwünschte CSS-Eingaben. Die globalen Werte gestalten das gemeinsame Erscheinungsbild; Status, Position und Glas-Variante können pro Bild weiterhin in der Mediathek gewählt werden. Die Mediathek-Wahl bleibt damit bewusst die konkrete Entscheidung für das einzelne Bild.

## Hintergrundbild mit Divi kennzeichnen

Für ein Divi-Modul mit CSS-Hintergrundbild gehört die CSS-Klasse
`mgd-ail-background-container` direkt auf den Container mit dem Hintergrundbild
(in Divi unter **Erweitert → CSS-ID & Klassen**). Die Klasse schafft den
notwendigen Bezugskontext für das Label; sie verändert weder das Hintergrundbild
noch das Layout des Containers.

Füge anschließend innerhalb dieses Containers ein Text- oder Code-Modul mit dem
Shortcode ein:

```text
[mgd_ai_label image_id="55"]
```

`55` ersetzt du durch die WordPress-Mediathek-ID des bereits gekennzeichneten
Bildes. Optional sind bis zu drei eigene Klassen sowie ganzzahlige Offsets von
`0` bis `192` Pixeln erlaubt, zum Beispiel:

```text
[mgd_ai_label image_id="55" class="hero-bild" offset_x="24" offset_y="12"]
```

Ohne gültige Bild-ID, bei einem Status **Keine KI** oder bei ungültigen
Attributen gibt der Shortcode bewusst nichts aus.

### CSS-Klassen und Abstände

Der Reiter **CSS-Klassen** listet die verwendeten Status-, Positions- und Glas-Klassen auf. Dort lässt sich der Beispiel-Shortcode direkt kopieren. Für Hintergrundbilder ist wichtig: Der Shortcode muss innerhalb desselben Containers liegen, der die Klasse `mgd-ail-background-container` trägt. Die Attribute `offset_x` und `offset_y` akzeptieren ausschließlich ganze Pixelwerte von `0` bis `192`; `class` akzeptiert höchstens drei sichere CSS-Klassennamen. Das Label blockiert keine Buttons, Links oder andere Interaktionen im Container.

Beispiel für ein individuelles, per CSS ansprechbares Hintergrund-Label:

```text
[mgd_ai_label image_id="55" class="hero-label mgd-eigenes-label" offset_x="24" offset_y="12"]
```

## Zentrale Plugin-Verwaltung

Die Verwaltung liegt unter **Medien → KI-Bildkennzeichnung**. Sie verwendet die WordPress-Standardoberfläche und ist auf Administratorinnen und Administratoren mit der Berechtigung `manage_options` begrenzt.

| Reiter | Zweck |
| --- | --- |
| **Einstellungen** | Globale Standards festlegen und mit einer lokalen Vorschau kontrollieren. |
| **CSS-Klassen** | Klassen und Hintergrund-Shortcode für Divi-Container kopieren. |
| **AI-Philosophie** | Transparenztext redaktionell pflegen und als `[mgd_ai_philosophy]` ausgeben. |
| **Impressum** | Lokale Projekt-, Support- und Quellcode-Links finden. |

### AI-Philosophie veröffentlichen

Der Text zur AI-Philosophie bleibt als WordPress-Option auf der eigenen Website und wird ausschließlich mit erlaubtem, einfachem HTML gespeichert. Per `[mgd_ai_philosophy]` lässt er sich in einem WordPress-, Divi-Text- oder Code-Modul ausgeben.

Der Button **AI-Philosophie-Seite anlegen** ist absichtlich vorsichtig: Er legt höchstens eine vom Plugin markierte Seite an und überschreibt keine vorhandenen Seiten. Einen Footer-Link ergänzt das Plugin nur dann automatisch, wenn WordPress exakt eine aktive Menüposition erkennt, deren Kennung `footer` enthält. Bei keiner oder mehreren passenden Positionen bleibt jedes Menü unverändert; die Seite kann danach manuell dem gewünschten Footer-Menü hinzugefügt werden.

> **Rechtlicher Hinweis:** Die AI-Philosophie und die Bildlabels fördern Transparenz, ersetzen aber keine Prüfung von Rechtslage, Vertrag, Urheberrecht, Plattformregeln oder Einzelfall. Das Plugin gibt keine rechtliche Garantie und keine Rechtsberatung.

## Plugin-Verwaltung im Backend

In der WordPress-Pluginliste zeigt **Details anzeigen** eine eigene, native WordPress-Detailansicht. Sie bleibt auch verfügbar, wenn GitHub gerade nicht erreichbar ist. Die Ansicht erklärt Installation, Bedienung, häufige Fragen und die Änderungen der letzten Versionen.

Das Plugin liefert Icon, Banner und eine dezente animierte Variante vollständig lokal aus. Die Animation ist ausschließlich für die Projektdokumentation gedacht; im WordPress-Backend bleibt das Icon bewusst statisch, damit die Verwaltung ruhig und barrierearm bleibt.

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
  branding/                         Lokales Icon, Banner und Dokumentations-Animation
  css/frontend.css                 Lokale Frontend-Gestaltung
  js/media-save.js                 Speichern im Medien-Dialog
includes/
  class-admin-page.php              Controller der zentralen Medien-Verwaltung
  class-ai-philosophy.php           AI-Philosophie, Shortcode und sichere Seitenerstellung
  class-attachment-meta.php        Validierung und Zugriff auf Anhangsmetadaten
  class-github-updater.php          Sichere Prüfung öffentlicher GitHub-Releases
  class-image-renderer.php          Frontend-Ausgabe und Divi-Kompatibilität
  class-media-ajax.php              Geschützter Speichern-Endpunkt
  class-media-fields.php            Felder in den Anhang-Details
  class-plugin.php                  Plugin-Initialisierung
  class-plugin-presentation.php     Service-Links und native Detailansicht
  class-plugin-options.php          Streng validierte globale Label-Standards
  class-shortcodes.php               Hintergrund-Shortcode für Divi-Container
views/admin/                        Getrennte Ansichten für die vier Verwaltungsreiter
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

Jede Version erhält einen Git-Tag im Format `vX.Y.Z` und ein ZIP-Release, dessen oberster Ordner `mgd-ai-image-labels` heißt. WordPress erkennt neuere öffentliche GitHub-Releases im üblichen Plugin-Update-Zyklus und zeigt sie in **Dashboard → Aktualisierungen** beziehungsweise **Plugins** an. Das Release-Paket enthält nur die zur Laufzeit und Dokumentation erforderlichen Plugin-Dateien; Entwicklungs-Worktrees, Tests, lokale Visualisierungen, Archivdateien und Konfigurationsgeheimnisse gehören nicht hinein.

Die Prüfung ruft höchstens alle zwölf Stunden ausschließlich die öffentliche GitHub-Release-API dieses Repositories auf. Sie benötigt keine Zugangsdaten und überträgt keine Bilder, Bildmetadaten, Besucher- oder Nutzerdaten. Ein Release wird nur angeboten, wenn die Version neuer ist und eine exakt passende ZIP-Datei über `https://github.com` bereitsteht. Bei Netzwerk- oder Validierungsfehlern bleibt WordPress beim bisherigen Stand und führt kein Update aus.

Automatische WordPress-Updates können Website-Administratoren wie bei anderen Plugins bewusst in der Plugin-Verwaltung aktivieren oder deaktivieren. Vor jedem Update empfiehlt sich ein getestetes Backup, zum Beispiel über UpdraftPlus.

## Sicherheit und Datenschutz

- Alle Eingaben werden serverseitig auf erlaubte Werte beschränkt.
- Änderungen erfordern die passende WordPress-Berechtigung und einen Nonce.
- Es werden keine personenbezogenen Daten, Bilder oder Analysedaten an externe Dienste übertragen.
- Das Plugin speichert keine API-Schlüssel, Passwörter oder Tokens.
- Die AI-Philosophie wird ausschließlich in der lokalen WordPress-Datenbank gespeichert; externe Links öffnen mit `noopener noreferrer`.
- Automatische Footer-Änderungen erfolgen nur bei einer einzigen eindeutig erkannten Footer-Menüposition; sonst bleibt die Navigation unangetastet.
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
