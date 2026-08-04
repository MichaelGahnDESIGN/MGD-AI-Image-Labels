<?php
/**
 * Regressionstest für die sichtbaren, aber nicht invasiven Admin-Hilfen.
 *
 * Die Pluginliste soll nachvollziehbare Links und eine lokale Detailansicht
 * anbieten. Dieser schlanke Test sichert die öffentlichen Verträge, ohne eine
 * vollständige WordPress-Installation zu starten.
 */

declare(strict_types=1);

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-plugin-presentation.php' );

if ( false === $source ) {
	throw new RuntimeException( 'Die Präsentationsklasse konnte nicht gelesen werden.' );
}

/** Prüft einen unverzichtbaren Baustein der Plugin-Präsentation. */
function mgd_ail_presentation_assert_contains( string $needle, string $haystack, string $message ): void {
	if ( false === strpos( $haystack, $needle ) ) {
		throw new RuntimeException( $message . ' Fehlend: ' . $needle );
	}
}

mgd_ail_presentation_assert_contains( "'Michael Gahn DESIGN'", $source, 'Die Präsentationsklasse enthält den Website-Link.' );
mgd_ail_presentation_assert_contains( "'Support'", $source, 'Die Präsentationsklasse enthält einen Support-Link.' );
mgd_ail_presentation_assert_contains( 'noopener noreferrer', $source, 'Externe Links sichern den neuen Tab ab.' );
mgd_ail_presentation_assert_contains( 'plugin_row_meta', $source, 'Die Präsentationsklasse erweitert die Pluginliste nativ.' );
mgd_ail_presentation_assert_contains( 'open-plugin-details-modal', $source, 'Details anzeigen verwendet das WordPress-Modal.' );

echo "PASS: Die Plugin-Präsentation enthält sichere Service-Links und eine Detailansicht.\n";
