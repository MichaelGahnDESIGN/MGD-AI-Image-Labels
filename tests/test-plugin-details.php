<?php
/**
 * Regressionstest für die eigenständige WordPress-Detailansicht.
 *
 * Die Detailansicht soll auch dann hilfreiche lokale Informationen liefern,
 * wenn GitHub vorübergehend nicht erreichbar ist. So bleibt die Hilfe im
 * Backend unabhängig von einer externen Netzwerkverbindung verfügbar.
 */

declare(strict_types=1);

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-github-updater.php' );

if ( false === $source ) {
	throw new RuntimeException( 'Die Updater-Klasse konnte nicht gelesen werden.' );
}

/** Prüft einen unverzichtbaren Inhalt der Detailansicht. */
function mgd_ail_details_assert_contains( string $needle, string $haystack, string $message ): void {
	if ( false === strpos( $haystack, $needle ) ) {
		throw new RuntimeException( $message . ' Fehlend: ' . $needle );
	}
}

mgd_ail_details_assert_contains( "'installation'", $source, 'Die Detailansicht erklärt die Installation.' );
mgd_ail_details_assert_contains( "'faq'", $source, 'Die Detailansicht enthält häufige Fragen.' );
mgd_ail_details_assert_contains( "'changelog'", $source, 'Die Detailansicht enthält ein Änderungsprotokoll.' );
mgd_ail_details_assert_contains( "MGD_AI_IMAGE_LABELS_VERSION", $source, 'Die Detailansicht besitzt einen lokalen Versions-Fallback.' );
mgd_ail_details_assert_contains( 'michael-gahn.de/support/', $source, 'Die Detailansicht verweist auf den Support.' );

echo "PASS: Die WordPress-Detailansicht bleibt lokal hilfreich und vollständig.\n";
