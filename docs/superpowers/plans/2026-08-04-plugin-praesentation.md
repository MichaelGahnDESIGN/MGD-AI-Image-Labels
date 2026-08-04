# Plugin-Präsentation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Version 0.5.3 liefert ein professionelles MGD-Branding, eine native WordPress-Detailansicht und sichere Service-Links für MGD KI-Bildkennzeichnung.

**Architecture:** Lokale Branding-Dateien liegen getrennt vom Laufzeitcode unter `assets/branding/`. Eine neue Präsentationsklasse kümmert sich ausschließlich um Links in der Pluginliste; die bestehende Updater-Klasse liefert dieselben lokalen Icon- und Banner-URLs an das WordPress-Detail-Modal. Alle externen Links werden zentral als sichere, neue Tabs erzeugt.

**Tech Stack:** WordPress 6+, PHP 8.1+, PHP-Regressionstests, SVG als editierbare Icon-Quelle, lokal erzeugte PNG/GIF-Dateien, GitHub Releases.

---

### Task 1: Lokale Branding-Dateien und Paketprüfung

**Files:**
- Create: `assets/branding/icon.svg`
- Create: `assets/branding/icon-128x128.png`
- Create: `assets/branding/icon-256x256.png`
- Create: `assets/branding/banner-772x250.png`
- Create: `assets/branding/icon-motion.gif`
- Create: `tests/test-branding-assets.php`

- [ ] **Step 1: Fehlschlagenden Asset-Test schreiben**

```php
$assets = array(
	'assets/branding/icon.svg',
	'assets/branding/icon-128x128.png',
	'assets/branding/icon-256x256.png',
	'assets/branding/banner-772x250.png',
	'assets/branding/icon-motion.gif',
);

foreach ( $assets as $asset ) {
	if ( ! is_file( dirname( __DIR__ ) . '/' . $asset ) || 0 === filesize( dirname( __DIR__ ) . '/' . $asset ) ) {
		throw new RuntimeException( 'Branding-Datei fehlt oder ist leer: ' . $asset );
	}
}
```

- [ ] **Step 2: Test ausführen**

Run: `php tests/test-branding-assets.php`

Expected: FAIL, weil der Branding-Ordner noch nicht existiert.

- [ ] **Step 3: Editierbares SVG und daraus abgeleitete lokale Dateien erstellen**

Das SVG nutzt ausschließlich eine dunkle abgerundete Fläche, einen roten Bildrahmen, eine weiße AI-Kapsel und keinen Text außerhalb der Bildmarke. Die PNG-Dateien werden aus dem SVG in 128 × 128 und 256 × 256 gerastert. Das Banner übernimmt dieselbe Palette mit viel ruhiger Negativfläche. Die GIF-Datei zeigt nur ein langsames Aufleuchten des roten Rahmens und enthält weder Script noch externe Quelle.

- [ ] **Step 4: Asset-Test und Dateitypen prüfen**

Run: `php tests/test-branding-assets.php && file assets/branding/icon-128x128.png assets/branding/icon-256x256.png assets/branding/banner-772x250.png assets/branding/icon-motion.gif`

Expected: Test-PASS sowie PNG- und GIF-MIME-Ausgaben.

- [ ] **Step 5: Commit erstellen**

Run: `git add assets/branding tests/test-branding-assets.php && git commit -m "feat: lokales Plugin-Branding ergänzen"`

### Task 2: Detaildaten und Update-Branding bereitstellen

**Files:**
- Modify: `includes/class-github-updater.php`
- Modify: `tests/test-github-updater.php`

- [ ] **Step 1: Fehlschlagenden Test für Icons und Banner ergänzen**

```php
mgd_ail_updater_assert_same(
	'https://example.test/wp-content/plugins/mgd-ai-image-labels/assets/branding/icon-128x128.png',
	$update->icons['1x'],
	'Das WordPress-Update-Objekt liefert das lokale reguläre Icon.'
);
mgd_ail_updater_assert_same(
	'https://example.test/wp-content/plugins/mgd-ai-image-labels/assets/branding/banner-772x250.png',
	$update->banners['low'],
	'Das WordPress-Update-Objekt liefert das lokale Banner für die Detailansicht.'
);
```

- [ ] **Step 2: Test ausführen**

Run: `php tests/test-github-updater.php`

Expected: FAIL, weil das Update-Objekt noch keine `icons` und `banners` enthält.

- [ ] **Step 3: Zentrale Asset-URLs ergänzen**

```php
private static function get_branding_urls(): array {
	$base_url = trailingslashit( MGD_AI_IMAGE_LABELS_URL ) . 'assets/branding/';

	return array(
		'icons'   => array( '1x' => $base_url . 'icon-128x128.png', '2x' => $base_url . 'icon-256x256.png' ),
		'banners' => array( 'low' => $base_url . 'banner-772x250.png', 'high' => $base_url . 'banner-772x250.png' ),
	);
}
```

`build_update()` und `provide_plugin_information()` übernehmen die Arrays direkt in ihre WordPress-Objekte. Die Funktion gibt einen leeren Array-Wert zurück, wenn die Plugin-URL-Konstante außerhalb von WordPress nicht definiert ist.

- [ ] **Step 4: Test, Syntax und Commit**

Run: `php tests/test-github-updater.php && php -l includes/class-github-updater.php && git add includes/class-github-updater.php tests/test-github-updater.php && git commit -m "feat: Detailansicht mit Branding versorgen"`

Expected: PASS ohne Netzwerkzugriff.

### Task 3: Sichere Links und native Detailansicht

**Files:**
- Create: `includes/class-plugin-presentation.php`
- Create: `tests/test-plugin-presentation.php`
- Modify: `includes/class-plugin.php`
- Modify: `includes/class-github-updater.php`
- Modify: `mgd-ai-image-labels.php`

- [ ] **Step 1: Fehlschlagenden Test für die Präsentationsinhalte schreiben**

```php
$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-plugin-presentation.php' );
mgd_ail_presentation_assert_contains( "'Support'", $source, 'Die Präsentationsklasse enthält einen Support-Link.' );
mgd_ail_presentation_assert_contains( 'noopener noreferrer', $source, 'Externe Links sichern den neuen Tab ab.' );
mgd_ail_presentation_assert_contains( 'plugin_row_meta', $source, 'Die Präsentationsklasse erweitert die Pluginliste nativ.' );
mgd_ail_presentation_assert_contains( 'open-plugin-details-modal', $source, 'Details anzeigen verwendet das WordPress-Modal.' );
```

- [ ] **Step 2: Test ausführen**

Run: `php tests/test-plugin-presentation.php`

Expected: FAIL, weil die Klasse noch nicht existiert.

- [ ] **Step 3: Präsentationsklasse implementieren**

Die Klasse registriert `plugin_row_meta` und ergänzt nur für `plugin_basename( MGD_AI_IMAGE_LABELS_FILE )` diese Links: `Michael Gahn DESIGN`, `Details anzeigen`, `Dokumentation`, `Support`, `GitHub`. Externe Ziel-URLs sind `https://michael-gahn.de/`, `https://michael-gahn.de/support/` und das öffentliche GitHub-Repository. `Details anzeigen` führt mit Thickbox-Klassen zur lokalen `plugins_api`-Antwort.

Die vorhandene Methode `provide_plugin_information()` liefert vollständige WordPress-Abschnitte `description`, `installation`, `faq` und `changelog`; sie enthält keine erfundenen Ratings, Downloadzahlen oder WordPress.org-Verweise.

- [ ] **Step 4: Header und Initialisierung verbinden**

```php
 * Plugin URI:         https://github.com/MichaelGahnDESIGN/MGD-AI-Image-Labels
 * Author URI:         https://michael-gahn.de/
 * Version:            0.5.3
```

`class-plugin.php` lädt `class-plugin-presentation.php` und registriert sie. Die Detailansicht wird weiter ausschließlich von der vorhandenen Updater-Klasse über den `plugins_api`-Filter geliefert.

- [ ] **Step 5: Tests, Syntax und Commit**

Run: `for test in tests/test-*.php; do php "$test" || exit 1; done && php -l includes/class-plugin-presentation.php && php -l includes/class-github-updater.php && php -l mgd-ai-image-labels.php && git diff --check && git add includes/class-plugin-presentation.php includes/class-plugin.php includes/class-github-updater.php mgd-ai-image-labels.php tests/test-plugin-presentation.php && git commit -m "feat: Plugin-Details und Service-Links ergänzen"`

Expected: Alle Tests geben `PASS` aus.

### Task 4: Dokumentation, ZIP und Release 0.5.3

**Files:**
- Modify: `README.md`
- Modify: `SECURITY.md`

- [ ] **Step 1: README und Security-Hinweise ergänzen**

README dokumentiert die lokale Branding-Dateien, die Detailansicht und die vier Service-Links. SECURITY dokumentiert, dass die Darstellung ohne Tracking und ohne externe Asset-Hosts arbeitet.

- [ ] **Step 2: Release-Paket prüfen**

Run: `PACKAGE_DIR=$(mktemp -d) && PACKAGE_PATH="$PACKAGE_DIR/mgd-ai-image-labels-0.5.3.zip" && git archive --format=zip --prefix='mgd-ai-image-labels/' HEAD -- assets includes mgd-ai-image-labels.php README.md LICENSE SECURITY.md > "$PACKAGE_PATH" && unzip -t "$PACKAGE_PATH" && unzip -l "$PACKAGE_PATH" | rg 'assets/branding/(icon-128x128\.png|banner-772x250\.png|icon-motion\.gif)'`

Expected: Die ZIP ist fehlerfrei und enthält alle Branding-Dateien.

- [ ] **Step 3: Commit, Push, Tag und GitHub-Release erstellen**

Run: `PACKAGE_DIR=$(mktemp -d) && PACKAGE_PATH="$PACKAGE_DIR/mgd-ai-image-labels-0.5.3.zip" && git archive --format=zip --prefix='mgd-ai-image-labels/' HEAD -- assets includes mgd-ai-image-labels.php README.md LICENSE SECURITY.md > "$PACKAGE_PATH" && git add README.md SECURITY.md && git commit -m "docs: Plugin-Präsentation erklären" && git push origin main && git tag -a v0.5.3 -m "MGD KI-Bildkennzeichnung 0.5.3" && git push origin v0.5.3 && gh release create v0.5.3 "$PACKAGE_PATH#MGD KI-Bildkennzeichnung 0.5.3" --title "MGD KI-Bildkennzeichnung 0.5.3" --notes "Professionelles Branding, native Detailansicht und sichere Service-Links."`

Expected: GitHub enthält den Tag und eine prüfbare Installations-ZIP für 0.5.3.

## Selbstprüfung

- Task 1 erfüllt das statische WordPress-Icon und die getrennte Motion-Variante.
- Task 2 versorgt Update- und Detailansicht ausschließlich mit lokalen Asset-URLs.
- Task 3 deckt Website-Link, Support, native Detailansicht und sichere externe Links ab.
- Task 4 stellt sicher, dass Repository und Release-ZIP dieselben Branding-Dateien enthalten.
