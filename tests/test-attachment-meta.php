<?php
/**
 * Minimaler, bewusst unabhängiger PHP-Test für die erlaubten Medienwerte.
 *
 * Dieses Projekt nutzt kein PHPUnit. Der Test ist deshalb absichtlich klein,
 * reproduzierbar und prüft nur die reine Whitelist-Logik ohne WordPress oder
 * eine Datenbank zu starten.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

require_once dirname( __DIR__ ) . '/includes/class-attachment-meta.php';

/*
 * Die folgenden kleinen WordPress-Doubles erlauben einen isolierten Test der
 * Berechtigungsgrenze. Sie ersetzen keine WordPress-Integrationstests,
 * machen aber den kritischen Fall ohne Datenbank reproduzierbar.
 */
$GLOBALS['mgd_ail_test_capabilities'] = array();
$GLOBALS['mgd_ail_test_meta_writes']  = array();
$GLOBALS['mgd_ail_test_mime_type']    = 'image/jpeg';
$GLOBALS['mgd_ail_test_meta_values']  = array();
$GLOBALS['mgd_ail_test_display_options'] = array(
	'font_size' => '6',
	'offset'    => '12',
	'padding_y' => '5',
	'padding_x' => '9',
	'radius'    => '999',
	'blur'      => '10',
	'theme'     => 'dark',
	'position'  => 'top-left',
);

function current_user_can( string $capability, ...$arguments ): bool {
	$attachment_id = isset( $arguments[0] ) ? (int) $arguments[0] : 0;

	return (bool) ( $GLOBALS['mgd_ail_test_capabilities'][ $capability . ':' . $attachment_id ]
		?? $GLOBALS['mgd_ail_test_capabilities'][ $capability ]
		?? false );
}

function get_post_mime_type( int $attachment_id ): string {
	return (string) $GLOBALS['mgd_ail_test_mime_type'];
}

function update_post_meta( int $attachment_id, string $key, string $value ): void {
	$GLOBALS['mgd_ail_test_meta_writes'][] = array( $attachment_id, $key, $value );
}

function get_post_meta( int $attachment_id, string $key, bool $single ): string {
	return (string) ( $GLOBALS['mgd_ail_test_meta_values'][ $attachment_id ][ $key ] ?? '' );
}

/** @return array<string, string> */
function get_option( string $option, $default = false ): array {
	return 'mgd_ail_display_options' === $option ? $GLOBALS['mgd_ail_test_display_options'] : ( is_array( $default ) ? $default : array() );
}

require_once dirname( __DIR__ ) . '/includes/class-plugin-options.php';
require_once dirname( __DIR__ ) . '/includes/class-media-fields.php';

/** @param mixed $actual @param mixed $expected */
function mgd_ail_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		throw new RuntimeException(
			$message . ' Erwartet: ' . var_export( $expected, true ) . '; erhalten: ' . var_export( $actual, true )
		);
	}
}

/** @param array<int, string> $values */
function mgd_ail_assert_allowed_values( array $values, callable $sanitizer, string $group ): void {
	foreach ( $values as $value ) {
		mgd_ail_assert_same( $value, $sanitizer( $value ), $group . ' akzeptiert erlaubten Wert nicht: ' . $value );
	}
}

mgd_ail_assert_allowed_values(
	array( 'none', 'generated', 'partially-generated', 'modified', 'deepfake' ),
	array( MGD_AI_Image_Labels_Attachment_Meta::class, 'sanitize_status' ),
	'Status'
);
mgd_ail_assert_allowed_values(
	array( 'top-left', 'top-right', 'bottom-left', 'bottom-right' ),
	array( MGD_AI_Image_Labels_Attachment_Meta::class, 'sanitize_position' ),
	'Position'
);
mgd_ail_assert_allowed_values(
	array( 'auto', 'light', 'dark' ),
	array( MGD_AI_Image_Labels_Attachment_Meta::class, 'sanitize_theme' ),
	'Glas-Variante'
);

mgd_ail_assert_same( 'none', MGD_AI_Image_Labels_Attachment_Meta::sanitize_status( 'unbekannt' ), 'Ungültiger Status erhält keinen sicheren Rückfallwert.' );
mgd_ail_assert_same( 'bottom-right', MGD_AI_Image_Labels_Attachment_Meta::sanitize_position( '<script>top-left</script>' ), 'Ungültige Position erhält keinen sicheren Rückfallwert.' );
mgd_ail_assert_same( 'auto', MGD_AI_Image_Labels_Attachment_Meta::sanitize_theme( array( 'dark' ) ), 'Ungültige Glas-Variante erhält keinen sicheren Rückfallwert.' );

// Fehlen bei einem älteren Bild individuelle Darstellungswerte, müssen die
// zentral konfigurierten Standards greifen. So wirkt sich die Verwaltungsseite
// nicht nur in ihrer Vorschau, sondern auch in der echten Frontend-Ausgabe aus.
$GLOBALS['mgd_ail_test_meta_values'][77] = array(
	MGD_AI_Image_Labels_Attachment_Meta::STATUS_KEY => 'generated',
);
$inherited_values = MGD_AI_Image_Labels_Attachment_Meta::get_values( 77 );
mgd_ail_assert_same( 'top-left', $inherited_values['position'], 'Fehlende Einzelposition übernimmt den global validierten Standard.' );
mgd_ail_assert_same( 'dark', $inherited_values['theme'], 'Fehlende Einzel-Glasvariante übernimmt den global validierten Standard.' );

// Ein ausdrücklich gespeicherter Bildwert bleibt immer vorrangig, damit
// Redaktionen jedes Motiv unabhängig von der globalen Ausgangsgestaltung
// positionieren und kontraststark darstellen können.
$GLOBALS['mgd_ail_test_meta_values'][78] = array(
	MGD_AI_Image_Labels_Attachment_Meta::STATUS_KEY   => 'generated',
	MGD_AI_Image_Labels_Attachment_Meta::POSITION_KEY => 'bottom-left',
	MGD_AI_Image_Labels_Attachment_Meta::THEME_KEY    => 'light',
);
$individual_values = MGD_AI_Image_Labels_Attachment_Meta::get_values( 78 );
mgd_ail_assert_same( 'bottom-left', $individual_values['position'], 'Eine gespeicherte Einzelposition hat Vorrang vor dem globalen Standard.' );
mgd_ail_assert_same( 'light', $individual_values['theme'], 'Eine gespeicherte Einzel-Glasvariante hat Vorrang vor dem globalen Standard.' );

// Sicherheitsfall: Eine allgemeine Upload-Berechtigung reicht nicht aus. Die
// Person muss das konkrete Bild auch mit edit_post bearbeiten dürfen.
$GLOBALS['mgd_ail_test_capabilities'] = array(
	'upload_files' => true,
	'edit_post:55' => false,
);
$GLOBALS['mgd_ail_test_meta_writes'] = array();
MGD_AI_Image_Labels_Media_Fields::save_fields(
	array( 'ID' => 55 ),
	array( 'mgd_ail_status' => 'generated', 'mgd_ail_position' => 'top-right', 'mgd_ail_theme' => 'dark' )
);
mgd_ail_assert_same(
	array(),
	$GLOBALS['mgd_ail_test_meta_writes'],
	'Ohne edit_post für das konkrete Bild dürfen keine Kennzeichnungs-Metadaten gespeichert werden.'
);

echo "PASS: Whitelist und sichere Rückfallwerte für Attachment-Metadaten.\n";
