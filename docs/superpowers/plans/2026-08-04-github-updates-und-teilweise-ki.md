# GitHub-Updates und „Teilweise KI generiert“ Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Version 0.5.1 ergänzt sichere GitHub-basierte WordPress-Updates und den Kennzeichnungsstatus „Teilweise KI generiert“.

**Architecture:** Eine neue, isolierte Updater-Klasse liest nur die öffentliche GitHub-Release-API, validiert Version und ZIP-Asset und übergibt anschließend Metadaten an den WordPress-Update-Transienten. Der neue Bildstatus wird konsistent in der Metadaten-Whitelist, Mediathek und Renderer ergänzt.

**Tech Stack:** WordPress 6+, PHP 8.1+, GitHub Releases API, lokale PHP-Tests, GitHub CLI für den Release.

---

### Task 1: GitHub-Release-Daten sicher normalisieren

**Files:**
- Create: `includes/class-github-updater.php`
- Create: `tests/test-github-updater.php`

- [ ] **Step 1: Den fehlschlagenden Test für eine gültige Release-Antwort schreiben**

```php
$release = MGD_AI_Image_Labels_GitHub_Updater::normalize_release(
	array(
		'tag_name' => 'v0.5.1',
		'assets'   => array(
			array(
				'name'                 => 'mgd-ai-image-labels-0.5.1.zip',
				'browser_download_url' => 'https://github.com/MichaelGahnDESIGN/MGD-AI-Image-Labels/releases/download/v0.5.1/mgd-ai-image-labels-0.5.1.zip',
			),
		),
	)
);
mgd_ail_updater_assert_same( '0.5.1', $release['version'], 'Der Release-Tag wird als WordPress-Version normalisiert.' );
```

- [ ] **Step 2: Test ausführen**

Run: `php tests/test-github-updater.php`

Expected: FAIL, weil die Updater-Klasse noch nicht existiert.

- [ ] **Step 3: Minimale Updater-Klasse implementieren**

```php
final class MGD_AI_Image_Labels_GitHub_Updater {
	private const RELEASE_ENDPOINT = 'https://api.github.com/repos/MichaelGahnDESIGN/MGD-AI-Image-Labels/releases/latest';

	public static function normalize_release( array $release ): array {
		$version = ltrim( (string) ( $release['tag_name'] ?? '' ), 'vV' );
		$asset   = self::find_plugin_asset( $release['assets'] ?? array(), $version );
		return '' === $version || '' === $asset ? array() : array( 'version' => $version, 'package' => $asset );
	}
}
```

`find_plugin_asset()` akzeptiert nur eine HTTPS-URL von `github.com` und einen Dateinamen im Format `mgd-ai-image-labels-{Version}.zip`.

- [ ] **Step 4: Sicherheits- und Fehlerfälle ergänzen**

```php
mgd_ail_updater_assert_same( array(), MGD_AI_Image_Labels_GitHub_Updater::normalize_release( array( 'tag_name' => 'v0.5.1', 'assets' => array() ) ), 'Releases ohne ZIP-Asset werden verworfen.' );
mgd_ail_updater_assert_same( array(), MGD_AI_Image_Labels_GitHub_Updater::normalize_release( array( 'tag_name' => 'v0.5.1', 'assets' => array( array( 'name' => 'fremd.zip', 'browser_download_url' => 'https://example.invalid/fremd.zip' ) ) ) ), 'Fremde Download-Quellen werden verworfen.' );
```

- [ ] **Step 5: Test ausführen und committen**

Run: `php tests/test-github-updater.php && git add includes/class-github-updater.php tests/test-github-updater.php && git commit -m "feat: GitHub-Release-Daten sicher prüfen"`

Expected: `PASS` und ein Commit mit ausschließlich Updater-Quelltext und Test.

### Task 2: WordPress-Update-Hinweis einbinden

**Files:**
- Modify: `mgd-ai-image-labels.php:3-27`
- Modify: `includes/class-plugin.php:28-43`
- Modify: `includes/class-github-updater.php`
- Test: `tests/test-github-updater.php`

- [ ] **Step 1: Fehlschlagenden Test für einen neueren Release schreiben**

```php
$update = MGD_AI_Image_Labels_GitHub_Updater::build_update( array( 'version' => '0.5.1', 'package' => 'https://github.com/MichaelGahnDESIGN/MGD-AI-Image-Labels/releases/download/v0.5.1/mgd-ai-image-labels-0.5.1.zip' ), '0.1.3', 'mgd-ai-image-labels/mgd-ai-image-labels.php' );
mgd_ail_updater_assert_same( '0.5.1', $update->new_version, 'Ein neuer GitHub-Release erzeugt einen WordPress-Update-Hinweis.' );
```

- [ ] **Step 2: Test ausführen**

Run: `php tests/test-github-updater.php`

Expected: FAIL, weil `build_update()` noch nicht existiert.

- [ ] **Step 3: WordPress-Integration implementieren**

```php
add_filter( 'pre_set_site_transient_update_plugins', array( self::class, 'inject_update' ) );
add_filter( 'plugins_api', array( self::class, 'provide_plugin_information' ), 20, 3 );
```

`inject_update()` nutzt einen zwölfstündigen Transient, ruft ausschließlich `RELEASE_ENDPOINT` über `wp_safe_remote_get()` ab und ergänzt nur dann `$transient->response[ plugin_basename( MGD_AI_IMAGE_LABELS_FILE ) ]`, wenn `version_compare( $release['version'], MGD_AI_IMAGE_LABELS_VERSION, '>' )` wahr ist.

- [ ] **Step 4: Header, Initialisierung und Version 0.5.1 ergänzen**

```php
 * Version:            0.5.1
 * Update URI:          https://github.com/MichaelGahnDESIGN/MGD-AI-Image-Labels
define( 'MGD_AI_IMAGE_LABELS_VERSION', '0.5.1' );
```

`class-plugin.php` lädt `class-github-updater.php` und ruft `MGD_AI_Image_Labels_GitHub_Updater::register()` auf.

- [ ] **Step 5: Tests, Syntax und Commit**

Run: `php tests/test-github-updater.php && php -l mgd-ai-image-labels.php && php -l includes/class-github-updater.php && git diff --check && git add mgd-ai-image-labels.php includes/class-plugin.php includes/class-github-updater.php tests/test-github-updater.php && git commit -m "feat: WordPress-Updates aus GitHub-Releases anbieten"`

Expected: Alle Prüfungen bestehen; kein Zugriffstoken ist im Diff enthalten.

### Task 3: „Teilweise KI generiert“ vollständig integrieren

**Files:**
- Modify: `includes/class-attachment-meta.php:25`
- Modify: `includes/class-media-fields.php:44-51`
- Modify: `includes/class-image-renderer.php:21-25`
- Modify: `tests/test-attachment-meta.php`
- Modify: `tests/test-image-renderer.php`

- [ ] **Step 1: Fehlschlagende Tests für Speicherung und Ausgabe ergänzen**

```php
mgd_ail_attachment_assert_same( 'partially-generated', MGD_AI_Image_Labels_Attachment_Meta::sanitize_status( 'partially-generated' ), 'Teilweise KI generiert ist ein zulässiger Status.' );
$partial = MGD_AI_Image_Labels_Image_Renderer::render_badge( $image_html, array( 'status' => 'partially-generated', 'position' => 'bottom-right', 'theme' => 'auto' ) );
mgd_ail_renderer_assert_contains( 'AI PARTIALLY GENERATED', $partial, 'Teilweise KI-generierte Bilder erhalten das passende Label.' );
```

- [ ] **Step 2: Tests ausführen**

Run: `php tests/test-attachment-meta.php && php tests/test-image-renderer.php`

Expected: FAIL, weil der neue Wert bisher von der Whitelist verworfen wird.

- [ ] **Step 3: Whitelist, Medienfeld und Renderer erweitern**

```php
private const STATUSES = array( 'none', 'generated', 'partially-generated', 'modified', 'deepfake' );
```

```php
'partially-generated' => 'Teilweise KI generiert',
```

```php
'partially-generated' => 'AI PARTIALLY GENERATED',
```

- [ ] **Step 4: Gesamte Test-Suite und Diff prüfen**

Run: `for test in tests/test-*.php; do php "$test" || exit 1; done && git diff --check`

Expected: Alle Tests geben `PASS` aus.

- [ ] **Step 5: Commit erstellen**

Run: `git add includes/class-attachment-meta.php includes/class-media-fields.php includes/class-image-renderer.php tests/test-attachment-meta.php tests/test-image-renderer.php && git commit -m "feat: teilweise KI-generierte Bilder kennzeichnen"`

### Task 4: Dokumentation, Release und Veröffentlichung

**Files:**
- Modify: `README.md`
- Modify: `SECURITY.md`

- [ ] **Step 1: README um Update- und Statusinformationen ergänzen**

```markdown
Neue öffentliche GitHub-Releases werden im üblichen WordPress-Update-Zyklus erkannt. Die Update-Prüfung benötigt keine Zugangsdaten und überträgt keine Medien- oder Nutzerdaten.
```

- [ ] **Step 2: Release-Paket erzeugen und den Inhalt prüfen**

Run: `PACKAGE_DIR=$(mktemp -d) && mkdir -p "$PACKAGE_DIR/mgd-ai-image-labels" && rsync -a --exclude .git --exclude tests --exclude '*.zip' ./ "$PACKAGE_DIR/mgd-ai-image-labels/" && ( cd "$PACKAGE_DIR" && zip -qr mgd-ai-image-labels-0.5.1.zip mgd-ai-image-labels ) && unzip -p "$PACKAGE_DIR/mgd-ai-image-labels-0.5.1.zip" mgd-ai-image-labels/mgd-ai-image-labels.php | rg -q 'Version:\s+0.5.1'`

- [ ] **Step 3: Commit, Push, Tag und GitHub-Release erstellen**

Run: `PACKAGE_DIR=$(mktemp -d) && mkdir -p "$PACKAGE_DIR/mgd-ai-image-labels" && rsync -a --exclude .git --exclude tests --exclude '*.zip' ./ "$PACKAGE_DIR/mgd-ai-image-labels/" && ( cd "$PACKAGE_DIR" && zip -qr mgd-ai-image-labels-0.5.1.zip mgd-ai-image-labels ) && git add README.md SECURITY.md && git commit -m "docs: Version 0.5.1 dokumentieren" && git push && git tag v0.5.1 && git push origin v0.5.1 && gh release create v0.5.1 "$PACKAGE_DIR/mgd-ai-image-labels-0.5.1.zip#MGD KI-Bildkennzeichnung 0.5.1" --title "MGD KI-Bildkennzeichnung 0.5.1" --notes "GitHub-basierte WordPress-Updates und Status Teilweise KI generiert."`

Expected: GitHub enthält den Tag, die Release-ZIP und die vollständige Dokumentation.

## Selbstprüfung

- Task 1 und 2 liefern den sicheren Update-Kanal ohne Geheimnisse oder privaten Server.
- Task 3 deckt Whitelist, Redaktion und Frontend-Ausgabe für den neuen Status ab.
- Task 4 publiziert die explizit gewünschte Version 0.5.1 als überprüfbaren GitHub-Release.
