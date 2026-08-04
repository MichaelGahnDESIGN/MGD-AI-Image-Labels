# Divi-Speichern und Plugin-Verwaltung Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Das Plugin speichert Kennzeichnungen im Divi-5-Medienmodal zuverlässig und bietet eine sichere zentrale Verwaltung unter Medien.

**Architecture:** Der bestehende AJAX-Endpunkt bleibt die einzige Schreibstelle für Bildwerte. Neue, getrennte Klassen kapseln globale Optionen, Shortcodes, die Verwaltungsseite und die AI-Philosophie-Seitenerstellung. Statische Views enthalten nur Darstellung; Validierung und Berechtigungen bleiben in PHP-Klassen.

**Tech Stack:** WordPress 6+, PHP 8.1, Settings API, Shortcode API, WordPress-Menü-API, jQuery im Admin, lokale CSS-Variablen, eigenständige PHP- und JavaScript-Regressionstests.

---

### Task 1: Divi-Medienmodal sicher erkennen und speichern

**Files:**
- Modify: `assets/js/media-save.js`
- Modify: `tests/test-media-save-selector.php`

- [ ] **Step 1: Den fehlschlagenden Regressionstest ergänzen**

Ergänze in `tests/test-media-save-selector.php` diese Prüfpunkte:

```php
if ( false === strpos( $script, ".media-modal:visible .attachment-details:visible" ) ) {
	throw new RuntimeException( 'Der Divi-Medienmodal wird noch nicht auf sichtbare Anhang-Details begrenzt.' );
}

if ( false === strpos( $script, ".media-frame-content:visible .attachment-details:visible" ) ) {
	throw new RuntimeException( 'Der WordPress-Medienframe wird noch nicht auf sichtbare Anhang-Details begrenzt.' );
}
```

- [ ] **Step 2: Test als Fehler reproduzieren**

Run: `php tests/test-media-save-selector.php`  
Expected: FAIL mit einem Hinweis auf den fehlenden sichtbaren Divi- oder WordPress-Medienbereich.

- [ ] **Step 3: Minimalen Scope-Resolver implementieren**

Ersetze `getDialogScope()` in `assets/js/media-save.js` durch diese feldlokale Suche:

```js
function getDialogScope( $button ) {
	var $scope = $button.closest( '.attachment-details:visible' );

	if ( ! $scope.length ) {
		$scope = $button.closest( '.media-modal:visible' )
			.find( '.attachment-details:visible' )
			.last();
	}

	if ( ! $scope.length ) {
		$scope = $button.closest( '.media-frame-content:visible' )
			.find( '.attachment-details:visible' )
			.last();
	}

	return $scope;
}
```

Die bestehende Suche `readField( $scope, … )` bleibt unverändert: Sie darf weiterhin nur im zurückgegebenen sichtbaren Scope suchen.

- [ ] **Step 4: Test und Syntaxprüfung ausführen**

Run: `php tests/test-media-save-selector.php && node --check assets/js/media-save.js`  
Expected: PASS; keine Node-Syntaxfehler.

- [ ] **Step 5: Commit erstellen**

```bash
git add assets/js/media-save.js tests/test-media-save-selector.php
git commit -m "fix: Divi-Medienmodal sicher speichern"
```

### Task 2: Globale, sichere Label-Optionen bereitstellen

**Files:**
- Create: `includes/class-plugin-options.php`
- Modify: `includes/class-image-renderer.php`
- Modify: `assets/css/frontend.css`
- Create: `tests/test-plugin-options.php`

- [ ] **Step 1: Fehlschlagenden Test für die Werte-Whitelist schreiben**

```php
$options = MGD_AI_Image_Labels_Plugin_Options::sanitize_options(
	array( 'font_size' => '9999px', 'offset' => '-2', 'radius' => '<script>' )
);

mgd_ail_options_assert_same( '6', $options['font_size'], 'Ungültige Schriftgrößen fallen auf den sicheren Standard zurück.' );
mgd_ail_options_assert_same( '12', $options['offset'], 'Ungültige Abstände fallen auf den sicheren Standard zurück.' );
mgd_ail_options_assert_same( '999', $options['radius'], 'Ungültige Radien fallen auf den sicheren Standard zurück.' );
```

- [ ] **Step 2: Fehler reproduzieren**

Run: `php tests/test-plugin-options.php`  
Expected: FAIL, weil die Optionsklasse noch nicht existiert.

- [ ] **Step 3: Optionsklasse implementieren**

Erstelle `includes/class-plugin-options.php` mit der Option `mgd_ail_display_options`, den sicheren Defaults und diesen öffentlichen Methoden:

```php
public static function get_defaults(): array;
public static function get_options(): array;
public static function sanitize_options( array $input ): array;
public static function register(): void;
public static function get_css_variables(): string;
```

Erlaubte Bereiche: `font_size` 6–24 (px), `offset` 0–96 (px), `padding_y` 2–24 (px), `padding_x` 4–40 (px), `radius` 0–999 (px), `blur` 0–24 (px); `theme` und `position` nur aus den bereits vorhandenen Whitelists. `get_css_variables()` gibt ausschließlich präfixierte Custom Properties wie `--mgd-ail-font-size: 6px;` zurück.

- [ ] **Step 4: CSS-Variablen ausgeben und verwenden**

In `class-image-renderer.php` registriert `enqueue_styles()` zusätzlich `wp_add_inline_style()` mit den bereits validierten CSS-Variablen. Ersetze in `assets/css/frontend.css` feste Werte beispielsweise so:

```css
.mgd-ail-badge {
	padding: var(--mgd-ail-padding-y, 5px) var(--mgd-ail-padding-x, 9px);
	border-radius: var(--mgd-ail-radius, 999px);
	font-size: var(--mgd-ail-font-size, 6px);
}
.mgd-ail-position-top-left { top: var(--mgd-ail-offset, 12px); left: var(--mgd-ail-offset, 12px); }
```

Die vorhandene pro-Bild-Position und Glas-Variante bleiben Klassen und haben gegenüber den globalen Defaults Vorrang.

- [ ] **Step 5: Tests und Commit**

Run: `php tests/test-plugin-options.php && php tests/test-image-renderer.php && php -l includes/class-plugin-options.php && git diff --check`  
Expected: PASS.

```bash
git add includes/class-plugin-options.php includes/class-image-renderer.php assets/css/frontend.css tests/test-plugin-options.php
git commit -m "feat: globale Label-Standards ergänzen"
```

### Task 3: Sichere Shortcodes für Bild- und Hintergrund-Labels

**Files:**
- Create: `includes/class-shortcodes.php`
- Modify: `includes/class-image-renderer.php`
- Modify: `assets/css/frontend.css`
- Create: `tests/test-shortcodes.php`

- [ ] **Step 1: Fehlschlagenden Shortcode-Test schreiben**

```php
$html = MGD_AI_Image_Labels_Shortcodes::render_label(
	array( 'image_id' => '55', 'class' => 'hero-bild', 'offset_x' => '24', 'offset_y' => '12' )
);
mgd_ail_shortcode_assert_contains( 'mgd-ail-background-label', $html, 'Der Hintergrund-Shortcode gibt einen isolierten Label-Wrapper aus.' );
mgd_ail_shortcode_assert_contains( 'hero-bild', $html, 'Eine erlaubte zusätzliche CSS-Klasse bleibt erhalten.' );
mgd_ail_shortcode_assert_contains( '--mgd-ail-offset-x:24px', $html, 'Der horizontale Abstand wird sicher als CSS-Variable ausgegeben.' );
```

- [ ] **Step 2: Fehler reproduzieren**

Run: `php tests/test-shortcodes.php`  
Expected: FAIL, weil die Klasse noch nicht existiert.

- [ ] **Step 3: Shortcode-Klasse implementieren**

Registriere `[mgd_ai_label]`. Attribute: `image_id` als positive Ganzzahl, `class` über `sanitize_html_class()` für maximal drei durch Leerzeichen getrennte Klassen, `offset_x` und `offset_y` als Ganzzahl 0–192. Bei Status `none`, ungültiger Bild-ID oder unbekannten Attributen bleibt die Ausgabe leer.

Nutze für den sichtbaren Badge eine neue öffentliche Methode `MGD_AI_Image_Labels_Image_Renderer::render_label_only( int $attachment_id, array $overrides ): string`, damit Label-Texte, Deepfake-Hinweise und Klassen nicht doppelt implementiert werden.

- [ ] **Step 4: Hintergrund-CSS ergänzen**

```css
.mgd-ail-background-label {
	position: relative;
	display: block;
}
.mgd-ail-background-label .mgd-ail-badge {
	transform: translate(var(--mgd-ail-offset-x, 0), var(--mgd-ail-offset-y, 0));
}
```

Die Klasse wird bewusst nicht automatisch auf ein Div angewendet: Der Shortcode muss innerhalb des gewünschten Divi-Containers platziert werden, damit kein fremdes Layout verändert wird.

- [ ] **Step 5: Tests und Commit**

Run: `php tests/test-shortcodes.php && php tests/test-image-renderer.php && php -l includes/class-shortcodes.php`  
Expected: PASS.

```bash
git add includes/class-shortcodes.php includes/class-image-renderer.php assets/css/frontend.css tests/test-shortcodes.php
git commit -m "feat: Hintergrund-Labels per Shortcode ergänzen"
```

### Task 4: Zentrale Verwaltungsseite und getrennte Views

**Files:**
- Create: `includes/class-admin-page.php`
- Create: `views/admin/settings.php`
- Create: `views/admin/css-classes.php`
- Create: `views/admin/ai-philosophy.php`
- Create: `views/admin/imprint.php`
- Modify: `includes/class-plugin.php`
- Create: `tests/test-admin-page.php`

- [ ] **Step 1: Fehlschlagenden Strukturtest schreiben**

```php
$required = array(
	'views/admin/settings.php',
	'views/admin/css-classes.php',
	'views/admin/ai-philosophy.php',
	'views/admin/imprint.php',
);
foreach ( $required as $file ) {
	if ( ! is_file( dirname( __DIR__ ) . '/' . $file ) ) {
		throw new RuntimeException( 'Die getrennte Verwaltungsansicht fehlt: ' . $file );
	}
}
```

- [ ] **Step 2: Fehler reproduzieren**

Run: `php tests/test-admin-page.php`  
Expected: FAIL mit der ersten fehlenden View-Datei.

- [ ] **Step 3: Admin-Controller implementieren**

`class-admin-page.php` registriert via `add_media_page()` genau eine Seite **KI-Bildkennzeichnung**. Die Methode `render_page()` verlangt `current_user_can( 'manage_options' )`, liest den Reiter nur aus der festen Whitelist `settings`, `css-classes`, `ai-philosophy`, `imprint` und lädt die passende View.

Die Einstellungs-View nutzt `settings_fields( 'mgd_ail_display_options' )`, `do_settings_sections()` und `submit_button()`. Die CSS-View zeigt die fünf Statusklassen, ein kopierbares Code-Beispiel und `[mgd_ai_label image_id="123" class="mein-div" offset_x="24" offset_y="24"]` nur als Text. Kein Administrator-Code führt Eingaben als JavaScript aus.

- [ ] **Step 4: Plugin-Boot ergänzen**

In `includes/class-plugin.php` die neuen Abhängigkeiten laden und registrieren:

```php
require_once MGD_AI_IMAGE_LABELS_DIR . 'includes/class-plugin-options.php';
require_once MGD_AI_IMAGE_LABELS_DIR . 'includes/class-shortcodes.php';
require_once MGD_AI_IMAGE_LABELS_DIR . 'includes/class-admin-page.php';

MGD_AI_Image_Labels_Plugin_Options::register();
MGD_AI_Image_Labels_Shortcodes::register();
MGD_AI_Image_Labels_Admin_Page::register();
```

- [ ] **Step 5: Tests und Commit**

Run: `php tests/test-admin-page.php && php -l includes/class-admin-page.php && php -l views/admin/settings.php && git diff --check`  
Expected: PASS.

```bash
git add includes/class-admin-page.php includes/class-plugin.php views/admin tests/test-admin-page.php
git commit -m "feat: zentrale Plugin-Verwaltung ergänzen"
```

### Task 5: AI-Philosophie, sichere Seitenerstellung und Footer-Zuordnung

**Files:**
- Create: `includes/class-ai-philosophy.php`
- Modify: `views/admin/ai-philosophy.php`
- Create: `tests/test-ai-philosophy.php`

- [ ] **Step 1: Fehlschlagenden Test für den Standard-Shortcode schreiben**

```php
$content = MGD_AI_Image_Labels_AI_Philosophy::render_shortcode();
mgd_ail_philosophy_assert_contains( 'verantwortungsvoll', $content, 'Der Standardtext erklärt einen verantwortungsvollen KI-Einsatz.' );
mgd_ail_philosophy_assert_same( '', MGD_AI_Image_Labels_AI_Philosophy::sanitize_content( '<script>alert(1)</script>' ), 'Unsicheres Script-Markup wird nicht gespeichert.' );
```

- [ ] **Step 2: Fehler reproduzieren**

Run: `php tests/test-ai-philosophy.php`  
Expected: FAIL, weil die Klasse noch nicht existiert.

- [ ] **Step 3: AI-Philosophie-Klasse implementieren**

Die Klasse speichert ihren Text in `mgd_ail_ai_philosophy`, registriert `[mgd_ai_philosophy]` und sanitiziert mit einer engen `wp_kses()`-Whitelist für Absatz, Überschriften, Listen, starke Betonung und Links. In der Verwaltungs-View wird der WordPress-Editor über `wp_editor()` eingebunden.

`handle_create_page()` prüft `manage_options`, `publish_pages` und einen spezifischen Nonce. Die Methode erstellt nur dann eine Seite mit Titel `AI-Philosophie`, wenn keine Seite mit dem eigenen Meta-Schlüssel `_mgd_ail_ai_philosophy_page` existiert. Der Inhalt lautet exakt `[mgd_ai_philosophy]`.

- [ ] **Step 4: Footer-Zuordnung konservativ implementieren**

Die Methode durchsucht `get_nav_menu_locations()` nur nach Standort-Keys, die `footer` enthalten. Sie fügt die Seite nur dann mit `wp_update_nav_menu_item()` hinzu, wenn genau ein eindeutiger Standort ein Menü enthält und dort keine Menü-URL auf die Seite verweist. Bei mehreren oder keinen Kandidaten erstellt sie die Seite, fasst aber kein Menü an und zeigt eine Admin-Meldung mit dem manuellen Schritt.

- [ ] **Step 5: Tests und Commit**

Run: `php tests/test-ai-philosophy.php && php -l includes/class-ai-philosophy.php && git diff --check`  
Expected: PASS; der Test enthält keine Datenbank- oder Netzwerkverbindung.

```bash
git add includes/class-ai-philosophy.php views/admin/ai-philosophy.php tests/test-ai-philosophy.php
git commit -m "feat: AI-Philosophie und Seitenerstellung ergänzen"
```

### Task 6: Dokumentation, Paket, Browser-Smoke und /dev-Abschluss

**Files:**
- Modify: `README.md`
- Modify: `SECURITY.md`
- Create: `CHANGELOG.md`

- [ ] **Step 1: Dokumentation und Changelog aktualisieren**

README ergänzt die Divi-Speicherung, alle vier Reiter, die Shortcodes, die CSS-Klassen und die konservative Footer-Regel. SECURITY ergänzt Rechte, Nonces, keine externen Assets und die enge Text-Whitelist. `CHANGELOG.md` erhält unter `Unreleased` die Bereiche Neu, Behoben, Sicherheit und Dokumentation.

- [ ] **Step 2: Vollständige technische Prüfung ausführen**

Run:

```bash
for test in tests/test-*.php; do php "$test" || exit 1; done
for file in mgd-ai-image-labels.php includes/*.php views/admin/*.php; do php -l "$file" || exit 1; done
node --check assets/js/media-save.js
git diff --check
```

Expected: alle Tests `PASS`, keine Syntax- und keine Whitespace-Fehler.

- [ ] **Step 3: Release-ZIP ohne sensible Daten prüfen**

Run:

```bash
release_dir=$(mktemp -d)
release_zip="$release_dir/mgd-ai-image-labels-next.zip"
git archive --format=zip --prefix='mgd-ai-image-labels/' HEAD -- assets includes views mgd-ai-image-labels.php README.md LICENSE SECURITY.md CHANGELOG.md > "$release_zip"
unzip -t "$release_zip"
unzip -l "$release_zip" | rg '(^|/)(\.env|BACKUP|PLAYTEST|id_rsa|\.pem|\.key|\.sql)'
```

Expected: `unzip -t` ist erfolgreich und `rg` liefert keine Ausgabe.

- [ ] **Step 4: WordPress- und Divi-Browser-Smoke nach Backup**

Vor einer Live-Installation ein vollständiges UpdraftPlus-Backup erstellen. Danach im angemeldeten Browser prüfen: Bild im WordPress-Medienmodal speichern; Bild im Divi-5-Medienmodal speichern; Einstellungen speichern; Hintergrund-Shortcode in einem Divi-Container testen; AI-Philosophie-Seite erstellen; Footer nur bei eindeutiger Zuordnung prüfen. Keine Produktivseite, kein bestehendes Menü und kein Bild wird bei der Prüfung gelöscht.

- [ ] **Step 5: Dokumentation committen und /dev ausführen**

```bash
git add README.md SECURITY.md CHANGELOG.md
git commit -m "docs: Verwaltung und Divi-Kompatibilität dokumentieren"
```

Danach den `/dev`-Ablauf nutzen: Status, Branches, Remote, Release-Stand, Tests, ZIP-Inhalt und Wissensdokumentation vergleichen. Vor jedem Push und Release erneut auf Secrets, Backups und Playtest-Dateien prüfen.

