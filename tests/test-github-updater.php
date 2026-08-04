<?php
/**
 * Unabhängiger Test für die sichere Auswertung öffentlicher GitHub-Releases.
 *
 * Der Test nutzt absichtlich keine Netzwerkverbindung und keine WordPress-
 * Installation. Er prüft nur die reine Validierungslogik: Ein Update darf
 * ausschließlich von dem erwarteten öffentlichen Repository stammen.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

require_once dirname( __DIR__ ) . '/includes/class-github-updater.php';

/** @param mixed $expected @param mixed $actual */
function mgd_ail_updater_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		throw new RuntimeException(
			$message . ' Erwartet: ' . var_export( $expected, true ) . '; erhalten: ' . var_export( $actual, true )
		);
	}
}

$valid_release = array(
	'tag_name' => 'v0.5.1',
	'assets'   => array(
		array(
			'name'                 => 'mgd-ai-image-labels-0.5.1.zip',
			'browser_download_url' => 'https://github.com/MichaelGahnDESIGN/MGD-AI-Image-Labels/releases/download/v0.5.1/mgd-ai-image-labels-0.5.1.zip',
		),
	),
);

$release = MGD_AI_Image_Labels_GitHub_Updater::normalize_release( $valid_release );
mgd_ail_updater_assert_same( '0.5.1', $release['version'], 'Der Release-Tag wird als WordPress-Version normalisiert.' );
mgd_ail_updater_assert_same( $valid_release['assets'][0]['browser_download_url'], $release['package'], 'Die gültige GitHub-ZIP wird als Update-Paket übernommen.' );

mgd_ail_updater_assert_same(
	array(),
	MGD_AI_Image_Labels_GitHub_Updater::normalize_release( array( 'tag_name' => 'v0.5.1', 'assets' => array() ) ),
	'Releases ohne ZIP-Asset werden verworfen.'
);
mgd_ail_updater_assert_same(
	array(),
	MGD_AI_Image_Labels_GitHub_Updater::normalize_release(
		array(
			'tag_name' => 'v0.5.1',
			'assets'   => array(
				array(
					'name'                 => 'mgd-ai-image-labels-0.5.1.zip',
					'browser_download_url' => 'https://example.invalid/mgd-ai-image-labels-0.5.1.zip',
				),
			),
		)
	),
	'Fremde Download-Quellen werden verworfen.'
);

echo "PASS: Öffentliche GitHub-Releases werden sicher normalisiert.\n";
