<?php
/**
 * Struktur- und Sicherheitsregressionstest für die zentrale Medienverwaltung.
 *
 * Der Test bleibt ohne WordPress-Installation ausführbar. Er prüft damit die
 * feste Tab-Whitelist und die getrennten, für Menschen wartbaren View-Dateien.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'MGD_AI_IMAGE_LABELS_DIR', dirname( __DIR__ ) . '/' );

$required_views = array(
	'views/admin/settings.php',
	'views/admin/css-classes.php',
	'views/admin/ai-philosophy.php',
	'views/admin/imprint.php',
);

foreach ( $required_views as $view ) {
	if ( ! is_file( dirname( __DIR__ ) . '/' . $view ) ) {
		throw new RuntimeException( 'Die getrennte Verwaltungsansicht fehlt: ' . $view );
	}
}

if ( ! is_file( dirname( __DIR__ ) . '/includes/class-admin-page.php' ) ) {
	throw new RuntimeException( 'Der zentrale Controller für die Medienverwaltung fehlt.' );
}

/** @var array<string, mixed> */
$GLOBALS['mgd_ail_admin_page_test'] = array(
	'capability' => true,
	'actions'    => array(),
	'page'       => array(),
	'options'    => array(
		'font_size' => '6',
		'offset'    => '12',
		'padding_y' => '5',
		'padding_x' => '9',
		'radius'    => '999',
		'blur'      => '10',
		'theme'     => 'auto',
		'position'  => 'bottom-right',
	),
);

function add_action( string $hook, callable $callback ): void {
	$GLOBALS['mgd_ail_admin_page_test']['actions'][ $hook ] = $callback;
}

function add_media_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback ): string {
	$GLOBALS['mgd_ail_admin_page_test']['page'] = compact( 'page_title', 'menu_title', 'capability', 'menu_slug', 'callback' );

	return 'media_page_' . $menu_slug;
}

/** @return array<string, string> */
function get_option( string $option, $default = false ) {
	if ( 'mgd_ail_display_options' !== $option ) {
		return is_array( $default ) ? $default : array();
	}

	return $GLOBALS['mgd_ail_admin_page_test']['options'];
}

function current_user_can( string $capability ): bool {
	return 'manage_options' === $capability && true === $GLOBALS['mgd_ail_admin_page_test']['capability'];
}

function wp_die( string $message ): void {
	throw new RuntimeException( $message );
}

function sanitize_key( string $key ): string {
	return strtolower( preg_replace( '/[^a-z0-9_-]/', '', $key ) ?? '' );
}

function esc_html( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

function esc_url( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

function admin_url( string $path = '' ): string {
	return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
}

function add_query_arg( array $arguments, string $url ): string {
	return $url . '?' . http_build_query( $arguments );
}

function settings_fields( string $group ): void {
	echo '<input name="settings-group" value="' . esc_attr( $group ) . '">';
}

function do_settings_sections( string $page ): void {
	echo '<div data-settings-page="' . esc_attr( $page ) . '"></div>';
}

function submit_button(): void {
	echo '<button type="submit">Speichern</button>';
}

/** Minimaler Editor- und Nonce-Ersatz für die isolierte AI-Philosophie-View. */
function wp_nonce_field( string $action, string $name ): void {
	echo '<input name="' . esc_attr( $name ) . '" value="test">';
}

function wp_editor( string $content, string $editor_id, array $settings = array() ): void {
	echo '<textarea id="' . esc_attr( $editor_id ) . '">' . esc_html( $content ) . '</textarea>';
}

function wp_kses( string $content, array $allowed_html ): string {
	return strip_tags( $content, '<p><h2><h3><h4><ul><ol><li><strong><em><a><br>' );
}

function wpautop( string $content ): string {
	return '<p>' . $content . '</p>';
}

function wp_unslash( string $value ): string {
	return $value;
}

function selected( string $selected, string $current, bool $display = true ): string {
	return $selected === $current ? ' selected="selected"' : '';
}

function checked( string $checked, string $current, bool $display = true ): string {
	return $checked === $current ? ' checked="checked"' : '';
}

require_once dirname( __DIR__ ) . '/includes/class-plugin-options.php';
require_once dirname( __DIR__ ) . '/includes/class-ai-philosophy.php';
require_once dirname( __DIR__ ) . '/includes/class-admin-page.php';

function mgd_ail_admin_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		throw new RuntimeException( $message . ' Erwartet: ' . var_export( $expected, true ) . '; erhalten: ' . var_export( $actual, true ) );
	}
}

function mgd_ail_admin_assert_contains( string $needle, string $haystack, string $message ): void {
	if ( false === strpos( $haystack, $needle ) ) {
		throw new RuntimeException( $message . ' Fehlend: ' . $needle );
	}
}

MGD_AI_Image_Labels_Admin_Page::register();
call_user_func( $GLOBALS['mgd_ail_admin_page_test']['actions']['admin_menu'] );
mgd_ail_admin_assert_same( 'KI-Bildkennzeichnung', $GLOBALS['mgd_ail_admin_page_test']['page']['page_title'], 'Die zentrale Seite verwendet den vereinbarten Seitentitel.' );
mgd_ail_admin_assert_same( 'mgd-ai-image-labels', $GLOBALS['mgd_ail_admin_page_test']['page']['menu_slug'], 'Die Verwaltungsseite verwendet einen festen, konfliktarmen Medien-Slug.' );

$_GET['tab'] = '<script>unbekannt</script>';
ob_start();
MGD_AI_Image_Labels_Admin_Page::render_page();
$fallback_page = (string) ob_get_clean();
mgd_ail_admin_assert_contains( 'Globale Label-Standards', $fallback_page, 'Ein unbekannter Reiter fällt sicher auf Einstellungen zurück.' );
mgd_ail_admin_assert_contains( 'mgd_ail_display_options', $fallback_page, 'Die Einstellungen verwenden die zentrale WordPress Settings Group.' );

$_GET['tab'] = 'css-classes';
ob_start();
MGD_AI_Image_Labels_Admin_Page::render_page();
$classes_page = (string) ob_get_clean();
mgd_ail_admin_assert_contains( 'mgd-ail-background-container', $classes_page, 'Die CSS-Ansicht erklärt die Helper-Klasse für Hintergrundbilder.' );
mgd_ail_admin_assert_contains( 'AI PARTIALLY GENERATED', $classes_page, 'Die CSS-Ansicht führt auch teilweise KI-generierte Inhalte auf.' );
mgd_ail_admin_assert_contains( '[mgd_ai_label image_id=&quot;123&quot;', $classes_page, 'Die Shortcode-Anleitung bleibt als reiner, escapeter Text sichtbar.' );

$_GET['tab'] = 'ai-philosophy';
ob_start();
MGD_AI_Image_Labels_Admin_Page::render_page();
$philosophy_page = (string) ob_get_clean();
mgd_ail_admin_assert_contains( 'AI-Philosophie', $philosophy_page, 'Die sichere Platzhalteransicht für die spätere Philosophie ist erreichbar.' );

$_GET['tab'] = 'imprint';
ob_start();
MGD_AI_Image_Labels_Admin_Page::render_page();
$imprint_page = (string) ob_get_clean();
mgd_ail_admin_assert_contains( 'Michael Gahn DESIGN', $imprint_page, 'Das Impressum nennt den Plugin-Herausgeber.' );

$plugin_source = file_get_contents( dirname( __DIR__ ) . '/includes/class-plugin.php' );
if ( false === $plugin_source ) {
	throw new RuntimeException( 'Die zentrale Plugin-Klasse konnte nicht gelesen werden.' );
}
mgd_ail_admin_assert_contains( "includes/class-admin-page.php", $plugin_source, 'Der zentrale Controller wird beim Plugin-Start geladen.' );
mgd_ail_admin_assert_contains( 'MGD_AI_Image_Labels_Admin_Page::register();', $plugin_source, 'Der zentrale Controller wird beim Plugin-Start registriert.' );

$GLOBALS['mgd_ail_admin_page_test']['capability'] = false;
try {
	MGD_AI_Image_Labels_Admin_Page::render_page();
	throw new RuntimeException( 'Eine Verwaltungsseite ohne Rechte darf nicht gerendert werden.' );
} catch ( RuntimeException $exception ) {
	mgd_ail_admin_assert_contains( 'nicht berechtigt', $exception->getMessage(), 'Die Verwaltungsseite schützt sich mit manage_options.' );
}

echo "PASS: Die zentrale Medienverwaltung nutzt getrennte sichere Views und feste Reiter.\n";
