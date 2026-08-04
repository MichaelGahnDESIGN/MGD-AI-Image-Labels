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

echo "PASS: Der Medien-Speicherbutton unterstützt konkrete und dynamische WordPress-Anhangsfelder.\n";
