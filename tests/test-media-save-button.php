<?php
/**
 * Reproduzierbarer Test für den expliziten Speichern-Button im Medien-Dialog.
 *
 * Der Medien-Modal von WordPress speichert eigene Auswahlfelder nicht immer
 * automatisch. Dieser Test sichert deshalb die zwei zwingenden Bestandteile:
 * eine eindeutig an den Anhang gebundene Schaltfläche und einen zugänglichen
 * Statusbereich für die Rückmeldung nach dem Speichern.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

function esc_attr( string $value ): string {
	return $value;
}

function esc_html( string $value ): string {
	return $value;
}

require_once dirname( __DIR__ ) . '/includes/class-media-fields.php';

/** @param mixed $actual @param mixed $expected */
function mgd_ail_save_button_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		throw new RuntimeException( $message );
	}
}

$html = MGD_AI_Image_Labels_Media_Fields::get_save_button_html( 55 );

mgd_ail_save_button_assert_same(
	true,
	false !== strpos( $html, 'data-mgd-ail-save="55"' ),
	'Der Speichern-Button muss eindeutig an den aktuellen Anhang gebunden sein.'
);
mgd_ail_save_button_assert_same(
	true,
	false !== strpos( $html, 'aria-live="polite"' ),
	'Der Medien-Dialog braucht einen zugänglichen Bereich für die Speicherrückmeldung.'
);

echo "PASS: Der Medien-Dialog enthält einen zugänglichen Speichern-Button.\n";
