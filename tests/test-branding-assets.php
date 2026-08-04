<?php
/**
 * Prüft, dass alle lokalen Dateien für die Plugin-Präsentation vorhanden sind.
 *
 * Das Branding wird bewusst mit der Plugin-ZIP ausgeliefert. Dieser Test
 * verhindert, dass WordPress später ein externes Bild laden müsste oder die
 * Detailansicht ohne Icon beziehungsweise Banner erscheint.
 */

declare(strict_types=1);

$assets = array(
	'assets/branding/icon.svg',
	'assets/branding/icon-128x128.png',
	'assets/branding/icon-256x256.png',
	'assets/branding/banner-772x250.png',
	'assets/branding/icon-motion.gif',
);

foreach ( $assets as $asset ) {
	$path = dirname( __DIR__ ) . '/' . $asset;

	if ( ! is_file( $path ) || 0 === filesize( $path ) ) {
		throw new RuntimeException( 'Branding-Datei fehlt oder ist leer: ' . $asset );
	}
}

echo "PASS: Alle lokalen Branding-Dateien sind vorhanden.\n";
