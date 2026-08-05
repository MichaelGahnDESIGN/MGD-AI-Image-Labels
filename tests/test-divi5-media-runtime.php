<?php
/**
 * Regressionstest für die Divi-5-Laufzeit.
 *
 * Der Visual Builder rendert die bearbeitete Seite in einem eigenen
 * app_window-Iframe. Der Speichern-Handler muss daher nicht nur in klassischen
 * WordPress-Adminseiten, sondern zusätzlich im Divi-Builder-Request geladen
 * werden. Dieser Test schützt genau diese Einbindung vor einem Rückfall.
 */

declare(strict_types=1);

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-media-fields.php' );

if ( false === $source ) {
	throw new RuntimeException( 'Die Medienfeld-Klasse konnte nicht gelesen werden.' );
}

if ( false === strpos( $source, "add_action( 'wp_enqueue_scripts'" ) ) {
	throw new RuntimeException( 'Der Speichern-Handler wird im Divi-5-Frontend-Frame noch nicht eingehängt.' );
}

if ( false === strpos( $source, 'is_divi_visual_builder_request' ) ) {
	throw new RuntimeException( 'Die Divi-5-Builder-Erkennung fehlt.' );
}

if ( false === strpos( $source, 'data-mgd-ail-ajax-url' ) || false === strpos( $source, 'data-mgd-ail-nonce' ) ) {
	throw new RuntimeException( 'Der Speichern-Button enthält noch keine frame-unabhängigen AJAX-Daten.' );
}

echo "PASS: Der Speichern-Handler wird im Divi-5-Frame sicher geladen.\n";
