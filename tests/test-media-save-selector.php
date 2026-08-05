<?php
/**
 * Regressionstest für die Feldsuche im WordPress-Medien-Dialog.
 *
 * WordPress behält in den per JavaScript gerenderten Anhangsfeldern den
 * Platzhalter {{ID}} im name-Attribut. Der Speichern-Button muss diesen
 * dokumentierten Dialogfall zusätzlich zur konkreten Anhangs-ID auslesen.
 */

declare(strict_types=1);

$script = file_get_contents( dirname( __DIR__ ) . '/assets/js/media-save.js' );

if ( false === $script ) {
	throw new RuntimeException( 'Die JavaScript-Datei für den Medien-Speicherbutton konnte nicht gelesen werden.' );
}

if ( false === strpos( $script, 'attachments[{{ID}}]' ) ) {
	throw new RuntimeException( 'Der Medien-Speicherbutton unterstützt den WordPress-Platzhalter {{ID}} noch nicht.' );
}

if ( false === strpos( $script, '$button.closest( \'.attachment-details:visible\' )' ) ) {
	throw new RuntimeException( 'Der Medien-Speicherbutton begrenzt die Feldsuche noch nicht auf den aktuell geöffneten Anhang.' );
}

if ( false === strpos( $script, '.media-modal:visible .attachment-details:visible' ) ) {
	throw new RuntimeException( 'Die sichtbaren Anhang-Details im Divi-Medienmodal fehlen für den Medien-Speicherbutton.' );
}

if ( false === strpos( $script, '.media-frame-content:visible .attachment-details:visible' ) ) {
	throw new RuntimeException( 'Die sichtbaren Anhang-Details im WordPress-Medienframe fehlen für den Medien-Speicherbutton.' );
}

if ( false === strpos( $script, '$scope.find( selector ).first()' ) ) {
	throw new RuntimeException( 'Der Medien-Speicherbutton liest noch nicht das erste passende Feld innerhalb des aktuellen Anhangs.' );
}

echo "PASS: Der Medien-Speicherbutton liest ausschließlich Felder des aktuellen Anhangs.\n";
