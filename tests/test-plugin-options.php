<?php
/**
 * Eigenständiger Test für die globalen Darstellungsoptionen.
 *
 * Der Test benötigt keine WordPress-Installation. Die kleine get_option()-
 * Ersatzfunktion simuliert ausschließlich einen gespeicherten Optionswert und
 * erlaubt dadurch eine reproduzierbare Prüfung der erzeugten CSS-Variablen.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

/** @var mixed Simulierter, von WordPress gelesener Optionswert. */
$GLOBALS['mgd_ail_test_display_options'] = array();

/** @var array<string, mixed> Aufgezeichnete Argumente von register_setting(). */
$GLOBALS['mgd_ail_test_registered_setting'] = array();

/**
 * Simuliert den lesenden Teil der WordPress Options API.
 *
 * @param string $option  Name der angefragten Option.
 * @param mixed  $default Rückgabewert, falls keine Testoption vorliegt.
 * @return mixed Gespeicherter Testwert oder der übergebene Standardwert.
 */
function get_option( string $option, $default = false ) {
	if ( 'mgd_ail_display_options' !== $option ) {
		return $default;
	}

	return $GLOBALS['mgd_ail_test_display_options'];
}

/**
 * Zeichnet die sichere Registrierung auf, ohne WordPress zu laden.
 *
 * @param array<string, mixed> $args Registrierungsargumente.
 */
function register_setting( string $group, string $name, array $args = array() ): void {
	$GLOBALS['mgd_ail_test_registered_setting'] = compact( 'group', 'name', 'args' );
}

require_once dirname( __DIR__ ) . '/includes/class-plugin-options.php';

/**
 * Vergleicht zwei Werte strikt und liefert bei Abweichungen lesbare Details.
 *
 * @param mixed $expected Erwarteter Wert.
 * @param mixed $actual   Tatsächlicher Wert.
 */
function mgd_ail_options_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		throw new RuntimeException(
			$message . ' Erwartet: ' . var_export( $expected, true ) . '; erhalten: ' . var_export( $actual, true )
		);
	}
}

function mgd_ail_options_assert_contains( string $needle, string $haystack, string $message ): void {
	if ( false === strpos( $haystack, $needle ) ) {
		throw new RuntimeException( $message . ' Fehlend: ' . $needle );
	}
}

function mgd_ail_options_assert_not_contains( string $needle, string $haystack, string $message ): void {
	if ( false !== strpos( $haystack, $needle ) ) {
		throw new RuntimeException( $message . ' Unerwartet enthalten: ' . $needle );
	}
}

MGD_AI_Image_Labels_Plugin_Options::register();
mgd_ail_options_assert_same( 'mgd_ail_display_options', $GLOBALS['mgd_ail_test_registered_setting']['group'], 'Die spätere Verwaltungsseite teilt dieselbe Settings Group.' );
mgd_ail_options_assert_same( 'mgd_ail_display_options', $GLOBALS['mgd_ail_test_registered_setting']['name'], 'Nur der fest definierte Optionsname wird registriert.' );
mgd_ail_options_assert_same( array( MGD_AI_Image_Labels_Plugin_Options::class, 'sanitize_options' ), $GLOBALS['mgd_ail_test_registered_setting']['args']['sanitize_callback'], 'WordPress verwendet die strikt geprüfte Sanitization als Callback.' );

$unsafe = MGD_AI_Image_Labels_Plugin_Options::sanitize_options(
	array(
		'font_size' => '9999px',
		'offset'    => '-2',
		'radius'    => '<script>',
	)
);

mgd_ail_options_assert_same( '6', $unsafe['font_size'], 'Eine ungültige Schriftgröße fällt auf den sicheren Standard zurück.' );
mgd_ail_options_assert_same( '12', $unsafe['offset'], 'Ein negativer Abstand fällt auf den sicheren Standard zurück.' );
mgd_ail_options_assert_same( '999', $unsafe['radius'], 'Ein nicht numerischer Radius fällt auf den sicheren Standard zurück.' );

$valid = MGD_AI_Image_Labels_Plugin_Options::sanitize_options(
	array(
		'font_size' => 24,
		'offset'    => '0',
		'padding_y' => '2',
		'padding_x' => 40,
		'radius'    => '0',
		'blur'      => 24,
		'theme'     => 'dark',
		'position'  => 'top-left',
	)
);

mgd_ail_options_assert_same( '24', $valid['font_size'], 'Eine zulässige Ganzzahl wird als normalisierte Zeichenfolge gespeichert.' );
mgd_ail_options_assert_same( '0', $valid['offset'], 'Die zulässige Untergrenze bleibt erhalten.' );
mgd_ail_options_assert_same( 'dark', $valid['theme'], 'Eine zulässige Glasvariante bleibt erhalten.' );
mgd_ail_options_assert_same( 'top-left', $valid['position'], 'Eine zulässige Position bleibt erhalten.' );

$GLOBALS['mgd_ail_test_display_options'] = array(
	'font_size' => '12; color: red',
	'offset'    => '24',
	'padding_y' => '7',
	'padding_x' => '11',
	'radius'    => '18',
	'blur'      => '8',
	'theme'     => '</style><script>alert(1)</script>',
	'position'  => 'top:0;left:0',
);

$css = MGD_AI_Image_Labels_Plugin_Options::get_css_variables();

mgd_ail_options_assert_contains( '.mgd-ail-image-wrapper {', $css, 'Die Variablen gelten ausschließlich im lokalen Bild-Wrapper.' );
mgd_ail_options_assert_contains( '--mgd-ail-font-size: 6px;', $css, 'Eine manipulierte Schriftgröße wird nicht in CSS übernommen.' );
mgd_ail_options_assert_contains( '--mgd-ail-offset: 24px;', $css, 'Ein zulässiger globaler Abstand wird lokal ausgegeben.' );
mgd_ail_options_assert_not_contains( 'color: red', $css, 'Freie CSS-Deklarationen dürfen nicht in den Block gelangen.' );
mgd_ail_options_assert_not_contains( '<script>', $css, 'HTML- oder Skriptfragmente dürfen nicht in den Block gelangen.' );

if ( 6 !== preg_match_all( '/(^|\n)\s*(--[^:]+):/', $css, $properties ) ) {
	throw new RuntimeException( 'Der CSS-Block muss genau sechs klar erkennbare Custom Properties enthalten.' );
}

foreach ( $properties[2] as $property ) {
	if ( 0 !== strpos( $property, '--mgd-ail-' ) ) {
		throw new RuntimeException( 'Nicht präfixierte CSS-Property gefunden: ' . $property );
	}
}

$renderer_source = file_get_contents( dirname( __DIR__ ) . '/includes/class-image-renderer.php' );
if ( false === $renderer_source ) {
	throw new RuntimeException( 'Die Frontend-Ausgabeklasse konnte nicht gelesen werden.' );
}
mgd_ail_options_assert_contains( "wp_add_inline_style( 'mgd-ai-image-labels', MGD_AI_Image_Labels_Plugin_Options::get_css_variables() );", $renderer_source, 'Die lokalen Variablen werden an das bereits registrierte Frontend-Stylesheet gebunden.' );

$plugin_source = file_get_contents( dirname( __DIR__ ) . '/includes/class-plugin.php' );
if ( false === $plugin_source ) {
	throw new RuntimeException( 'Die zentrale Plugin-Klasse konnte nicht gelesen werden.' );
}

$options_require_position  = strpos( $plugin_source, "require_once MGD_AI_IMAGE_LABELS_DIR . 'includes/class-plugin-options.php';" );
$renderer_require_position = strpos( $plugin_source, "require_once MGD_AI_IMAGE_LABELS_DIR . 'includes/class-image-renderer.php';" );
$options_register_position = strpos( $plugin_source, 'MGD_AI_Image_Labels_Plugin_Options::register();' );
$renderer_register_position = strpos( $plugin_source, 'MGD_AI_Image_Labels_Image_Renderer::register();' );

if ( false === $options_require_position || false === $renderer_require_position || $options_require_position >= $renderer_require_position ) {
	throw new RuntimeException( 'Die Optionsklasse muss vor der Frontend-Ausgabeklasse geladen werden.' );
}
if ( false === $options_register_position || false === $renderer_register_position || $options_register_position >= $renderer_register_position ) {
	throw new RuntimeException( 'Die globalen Optionen müssen vor der Frontend-Ausgabe registriert werden.' );
}

$frontend_css = file_get_contents( dirname( __DIR__ ) . '/assets/css/frontend.css' );
if ( false === $frontend_css ) {
	throw new RuntimeException( 'Die lokale Frontend-CSS-Datei konnte nicht gelesen werden.' );
}

mgd_ail_options_assert_contains( 'padding: var(--mgd-ail-padding-y, 5px) var(--mgd-ail-padding-x, 9px);', $frontend_css, 'Das Badge verwendet globale Innenabstände mit sicheren Fallbacks.' );
mgd_ail_options_assert_contains( 'border-radius: var(--mgd-ail-radius, 999px);', $frontend_css, 'Das Badge verwendet den globalen Radius mit sicherem Fallback.' );
mgd_ail_options_assert_contains( 'font-size: var(--mgd-ail-font-size, 6px);', $frontend_css, 'Das Badge verwendet die globale Schriftgröße mit sicherem Fallback.' );
mgd_ail_options_assert_contains( '.mgd-ail-position-top-left { top: var(--mgd-ail-offset, 12px); left: var(--mgd-ail-offset, 12px); }', $frontend_css, 'Die obere linke Pro-Bild-Position verwendet den globalen Abstand.' );
mgd_ail_options_assert_contains( '.mgd-ail-position-bottom-right { right: var(--mgd-ail-offset, 12px); bottom: var(--mgd-ail-offset, 12px); }', $frontend_css, 'Die untere rechte Pro-Bild-Position verwendet den globalen Abstand.' );
mgd_ail_options_assert_contains( 'blur(var(--mgd-ail-blur, 10px))', $frontend_css, 'Die progressive Glasdarstellung verwendet die lokale Blur-Variable.' );

echo "PASS: Globale Label-Standards werden strikt validiert und lokal als CSS-Variablen ausgegeben.\n";
