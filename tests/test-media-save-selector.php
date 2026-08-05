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

if ( false === strpos( $script, 'document.addEventListener( \'click\'' ) ) {
	throw new RuntimeException( 'Der Medien-Speicherbutton muss ohne jQuery per Ereignisdelegation arbeiten.' );
}

if ( false === strpos( $script, 'data-mgd-ail-ajax-url' ) || false === strpos( $script, 'data-mgd-ail-nonce' ) ) {
	throw new RuntimeException( 'Der Medien-Speicherbutton muss seine AJAX-Daten direkt am Button lesen können.' );
}

if ( false === strpos( $script, 'XMLHttpRequest' ) ) {
	throw new RuntimeException( 'Der Medien-Speicherbutton braucht einen unabhängigen Browser-Transport ohne jQuery-Abhängigkeit.' );
}

echo "PASS: Der Medien-Speicherbutton liest ausschließlich Felder des aktuellen Anhangs.\n";
