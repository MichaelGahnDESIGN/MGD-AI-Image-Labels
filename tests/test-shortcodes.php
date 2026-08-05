<?php
/**
 * Eigenständiger Sicherheitstest für den Hintergrundbild-Shortcode.
 *
 * Der Test benötigt keine WordPress-Installation. Die kleinen Stubs bilden nur
 * die tatsächlich verwendeten WordPress-Helfer nach und halten den Vertrag der
 * Shortcode-Ausgabe dadurch nachvollziehbar und reproduzierbar.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

/** @var array<string, callable> Registrierte Shortcodes des Test-Harnesses. */
$GLOBALS['mgd_ail_test_shortcodes'] = array();

/**
 * Übernimmt ausschließlich die im Shortcode definierten Attribute.
 *
 * @param array<string, mixed> $defaults Erlaubte Attribute mit Standardwerten.
 * @param array<string, mixed> $attributes Ungeprüfte Shortcode-Eingaben.
 * @return array<string, mixed>
 */
function shortcode_atts( array $defaults, array $attributes ): array {
	return array_merge( $defaults, array_intersect_key( $attributes, $defaults ) );
}

/** Registriert einen Shortcode ausschließlich im lokalen Test-Harness. */
function add_shortcode( string $tag, callable $callback ): void {
	$GLOBALS['mgd_ail_test_shortcodes'][ $tag ] = $callback;
}

/**
 * Bildet die für diesen Test maßgebliche WordPress-Klassensanitisierung nach.
 */
function sanitize_html_class( string $class, string $fallback = '' ): string {
	$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', $class );

	return is_string( $sanitized ) && '' !== $sanitized ? $sanitized : $fallback;
}

/** Escaping-Stub für HTML-Attribute. */
function esc_attr( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

/** Escaping-Stub für sichtbaren Text. */
function esc_html( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

/**
 * Liefert ausschließlich kontrollierte Metadaten für die Test-Anhänge.
 *
 * @return string Sicherer Testwert oder leerer Standardwert.
 */
function get_post_meta( int $attachment_id, string $key, bool $single = true ): string {
	$values = $GLOBALS['mgd_ail_test_shortcode_meta'][ $attachment_id ] ?? array();

	return is_string( $values[ $key ] ?? null ) ? $values[ $key ] : '';
}

$GLOBALS['mgd_ail_test_shortcode_meta'] = array(
	55 => array(
		'_mgd_ail_status'   => 'generated',
		'_mgd_ail_position' => 'bottom-right',
		'_mgd_ail_theme'    => 'auto',
	),
	56 => array(
		'_mgd_ail_status'   => 'none',
		'_mgd_ail_position' => 'bottom-right',
		'_mgd_ail_theme'    => 'auto',
	),
	57 => array(
		'_mgd_ail_status'   => 'deepfake',
		'_mgd_ail_position' => 'top-left',
		'_mgd_ail_theme'    => 'dark',
	),
);

$shortcode_file = dirname( __DIR__ ) . '/includes/class-shortcodes.php';

if ( ! is_file( $shortcode_file ) ) {
	throw new RuntimeException( 'Die noch fehlende Shortcode-Klasse muss den Test zunächst rot werden lassen.' );
}

require_once dirname( __DIR__ ) . '/includes/class-attachment-meta.php';
require_once dirname( __DIR__ ) . '/includes/class-image-renderer.php';
require_once $shortcode_file;

/** @param mixed $expected @param mixed $actual */
function mgd_ail_shortcode_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		throw new RuntimeException(
			$message . ' Erwartet: ' . var_export( $expected, true ) . '; erhalten: ' . var_export( $actual, true )
		);
	}
}

function mgd_ail_shortcode_assert_contains( string $needle, string $haystack, string $message ): void {
	if ( false === strpos( $haystack, $needle ) ) {
		throw new RuntimeException( $message . ' Fehlend: ' . $needle );
	}
}

function mgd_ail_shortcode_assert_not_contains( string $needle, string $haystack, string $message ): void {
	if ( false !== strpos( $haystack, $needle ) ) {
		throw new RuntimeException( $message . ' Unerlaubt enthalten: ' . $needle );
	}
}

MGD_AI_Image_Labels_Shortcodes::register();
mgd_ail_shortcode_assert_same( true, isset( $GLOBALS['mgd_ail_test_shortcodes']['mgd_ai_label'] ), 'Der öffentliche Shortcode wird unter dem festen Namen registriert.' );

$label = MGD_AI_Image_Labels_Shortcodes::render_label(
	array(
		'image_id' => '55',
		'class'    => 'hero-bild',
		'offset_x' => '24',
		'offset_y' => '12',
	)
);
mgd_ail_shortcode_assert_contains( 'mgd-ail-background-label', $label, 'Die Ausgabe erhält ihre eigene Hintergrundbild-Klasse.' );
mgd_ail_shortcode_assert_contains( 'hero-bild', $label, 'Eine sichere individuelle Klasse wird übernommen.' );
mgd_ail_shortcode_assert_contains( '--mgd-ail-offset-x:24px', $label, 'Der sichere horizontale Offset wird als lokale CSS-Variable ausgegeben.' );
mgd_ail_shortcode_assert_contains( '--mgd-ail-offset-y:12px', $label, 'Der sichere vertikale Offset wird als lokale CSS-Variable ausgegeben.' );
mgd_ail_shortcode_assert_contains( 'AI GENERATED', $label, 'Das vorhandene sichtbare Label wird wiederverwendet.' );
mgd_ail_shortcode_assert_not_contains( '<img', $label, 'Der Shortcode gibt niemals ein Bild oder Bild-Wrapping aus.' );

$injected_class = MGD_AI_Image_Labels_Shortcodes::render_label(
	array( 'image_id' => '55', 'class' => 'hero-bild <script>', 'offset_x' => '24', 'offset_y' => '12' )
);
mgd_ail_shortcode_assert_same( '', $injected_class, 'Manipulierte Klassenattribute verwerfen die vollständige Ausgabe.' );
mgd_ail_shortcode_assert_not_contains( '<script>', $injected_class, 'HTML aus Attributen darf niemals ausgegeben werden.' );

mgd_ail_shortcode_assert_same( '', MGD_AI_Image_Labels_Shortcodes::render_label( array( 'image_id' => '0' ) ), 'Eine nicht positive Bild-ID erzeugt keine Ausgabe.' );
mgd_ail_shortcode_assert_same( '', MGD_AI_Image_Labels_Shortcodes::render_label( array( 'image_id' => '55<script>' ) ), 'Eine manipulierte Bild-ID erzeugt keine Ausgabe.' );
mgd_ail_shortcode_assert_same( '', MGD_AI_Image_Labels_Shortcodes::render_label( array( 'image_id' => '56' ) ), 'Der Status none erzeugt keine Ausgabe.' );
mgd_ail_shortcode_assert_same( '', MGD_AI_Image_Labels_Shortcodes::render_label( array( 'image_id' => '55', 'offset_x' => '193' ) ), 'Ein zu großer Offset erzeugt keine Ausgabe.' );
mgd_ail_shortcode_assert_same( '', MGD_AI_Image_Labels_Shortcodes::render_label( array( 'image_id' => '55', 'offset_y' => '12.5' ) ), 'Ein nicht ganzzahliger Offset erzeugt keine Ausgabe.' );
mgd_ail_shortcode_assert_same( '', MGD_AI_Image_Labels_Shortcodes::render_label( array( 'image_id' => '55', 'class' => 'eins zwei drei vier' ) ), 'Mehr als drei Zusatzklassen erzeugen keine Ausgabe.' );
mgd_ail_shortcode_assert_same( '', MGD_AI_Image_Labels_Shortcodes::render_label( array( 'image_id' => '55', 'fremdes_attribut' => '1' ) ), 'Unbekannte Attribute erzeugen keine Ausgabe.' );

$deepfake = MGD_AI_Image_Labels_Shortcodes::render_label( array( 'image_id' => '57' ) );
mgd_ail_shortcode_assert_contains( 'AI DEEPFAKE', $deepfake, 'Das vorhandene Deepfake-Label wird wiederverwendet.' );
mgd_ail_shortcode_assert_contains( 'künstlich erzeugt oder manipuliert', $deepfake, 'Der vorhandene ausführliche Screenreader-Hinweis bleibt enthalten.' );

$frontend_css = file_get_contents( dirname( __DIR__ ) . '/assets/css/frontend.css' );
if ( false === $frontend_css ) {
	throw new RuntimeException( 'Die lokale Frontend-CSS-Datei konnte nicht gelesen werden.' );
}
mgd_ail_shortcode_assert_contains( '.mgd-ail-background-label {', $frontend_css, 'Der Shortcode erhält eine eigene Hülle für die Hintergrundbild-Ausgabe.' );
mgd_ail_shortcode_assert_contains( 'position: absolute;', $frontend_css, 'Die Hülle wird als Overlay innerhalb des Hintergrund-Containers positioniert.' );
mgd_ail_shortcode_assert_contains( '.mgd-ail-background-label .mgd-ail-badge', $frontend_css, 'Offset-Transformationen bleiben auf Shortcode-Badges begrenzt.' );
mgd_ail_shortcode_assert_contains( 'inset: 0;', $frontend_css, 'Die leere Shortcode-Hülle überlagert die Fläche des Hintergrund-Containers vollständig.' );
mgd_ail_shortcode_assert_contains( ".mgd-ail-background-label {\n\tposition: absolute;\n\tinset: 0;\n\tdisplay: block;\n\tpointer-events: none;", $frontend_css, 'Das unsichtbare Overlay lässt Links, Buttons und Formulare des Containers durch.' );
mgd_ail_shortcode_assert_contains( '.mgd-ail-background-container {', $frontend_css, 'Der Hintergrund-Container erhält eine dokumentierte Helper-Klasse.' );
mgd_ail_shortcode_assert_contains( ".mgd-ail-background-container {\n\tposition: relative;", $frontend_css, 'Die Helper-Klasse erzeugt den sicheren Bezugskontext für das Overlay.' );

$readme = file_get_contents( dirname( __DIR__ ) . '/README.md' );
if ( false === $readme ) {
	throw new RuntimeException( 'Die Redaktionsanleitung konnte nicht gelesen werden.' );
}
mgd_ail_shortcode_assert_contains( 'mgd-ail-background-container', $readme, 'Die Anleitung benennt die erforderliche Klasse des Divi-Hintergrund-Containers.' );
mgd_ail_shortcode_assert_contains( '[mgd_ai_label image_id="55"]', $readme, 'Die Anleitung enthält ein vollständiges Shortcode-Beispiel.' );

echo "PASS: Hintergrundbild-Shortcodes geben ausschließlich sichere KI-Badges aus.\n";
