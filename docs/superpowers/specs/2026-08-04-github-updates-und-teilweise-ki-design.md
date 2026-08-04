# GitHub-Updates und „Teilweise KI generiert“

## Ziel

Das Plugin soll auf allen WordPress-Installationen über normale WordPress-Plugin-Updates aktualisierbar sein. Zusätzlich wird ein klarer Status für Bilder ergänzt, deren wesentliche Bestandteile mit KI generiert wurden, die aber nicht vollständig KI-generiert sind.

## GitHub-Update-Kanal

Das öffentliche Repository `MichaelGahnDESIGN/MGD-AI-Image-Labels` bleibt die einzige Release-Quelle. Bei einem neuen GitHub-Release prüft WordPress im normalen Update-Zyklus, ob dessen Tag höher als die installierte Plugin-Version ist. Ist dies der Fall, liefert das Plugin WordPress eine Download-URL zur Release-ZIP. WordPress verwendet danach seinen eigenen geprüften Update-Ablauf.

- Kein Token, Passwort oder privater Schlüssel im Plugin oder auf den Websites.
- Kein eigener Server und keine Datenbank für Updates.
- Zwischenspeicherung der GitHub-Antwort über einen WordPress-Transienten für zwölf Stunden.
- Bei Netzwerkfehlern, ungültigen Daten oder fehlender Release-ZIP bleibt WordPress unverändert und zeigt keinen fehlerhaften Update-Hinweis.
- Die Update-Prüfung läuft nur im WordPress-Backend und nur für berechtigte Update-Abfragen.

## Neue Kennzeichnungsart

Der technische Status lautet `partially-generated`, die Auswahl in der Mediathek **„Teilweise KI generiert“** und das sichtbare Label `AI PARTIALLY GENERATED`.

Er beschreibt Bilder, bei denen bedeutende Bildbestandteile KI-generiert sind, aber auch eigene, vorhandene oder menschlich erstellte Elemente enthalten bleiben. Die bisherige Auswahl „Mit KI bearbeitet“ bleibt für gezielte KI-Bearbeitungen eines vorhandenen Bildes bestehen.

## Architektur und Dateien

- `includes/class-github-updater.php`: ausschließlich Abruf, Validierung, Caching und Übergabe von Release-Metadaten an WordPress.
- `includes/class-attachment-meta.php`: Whitelist um `partially-generated` ergänzen.
- `includes/class-media-fields.php`: verständlicher Auswahltext.
- `includes/class-image-renderer.php`: sichtbares Label und unveränderte Barrierefreiheitsregeln.
- `tests/test-github-updater.php`: Release-Validierung, Versionsvergleich und sichere Fehlerfälle ohne Netzwerkzugriff.
- vorhandene Metadaten- und Renderer-Tests: neuer Status in Speichern, Sanitizing und Ausgabe.

## Nicht im Umfang

Keine Änderung an vorhandenen Bilddateien, Beiträgen, Divi-Layouts oder Metadaten bestehender Statuswerte. Es wird kein Zugang zu privaten GitHub-Repositories eingerichtet.
