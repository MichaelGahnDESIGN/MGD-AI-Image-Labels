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

function mgd_ail_updater_assert_contains( string $needle, string $haystack, string $message ): void {
	if ( false === strpos( $haystack, $needle ) ) {
		throw new RuntimeException( $message . ' Fehlend: ' . $needle );
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

$update = MGD_AI_Image_Labels_GitHub_Updater::build_update(
	$release,
	'0.1.3',
	'mgd-ai-image-labels/mgd-ai-image-labels.php'
);
mgd_ail_updater_assert_same( '0.5.1', $update->new_version, 'Ein neuer GitHub-Release erzeugt einen WordPress-Update-Hinweis.' );
mgd_ail_updater_assert_same( 'mgd-ai-image-labels/mgd-ai-image-labels.php', $update->plugin, 'Der Update-Hinweis verweist auf die exakte Plugin-Datei.' );
mgd_ail_updater_assert_same( null, MGD_AI_Image_Labels_GitHub_Updater::build_update( $release, '0.5.1', 'mgd-ai-image-labels/mgd-ai-image-labels.php' ), 'Die bereits installierte Version erzeugt keinen Update-Hinweis.' );

$plugin_source = file_get_contents( dirname( __DIR__ ) . '/mgd-ai-image-labels.php' );
if ( false === $plugin_source ) {
	throw new RuntimeException( 'Die Hauptdatei des Plugins konnte nicht gelesen werden.' );
}
mgd_ail_updater_assert_contains( 'Version:            0.5.2', $plugin_source, 'Die Plugin-Metadaten enthalten die veröffentlichte Fehlerbehebungs-Version 0.5.2.' );
mgd_ail_updater_assert_contains( 'Update URI:         https://github.com/MichaelGahnDESIGN/MGD-AI-Image-Labels', $plugin_source, 'Die Plugin-Metadaten benennen die eindeutige öffentliche Update-Quelle.' );
mgd_ail_updater_assert_contains( "define( 'MGD_AI_IMAGE_LABELS_VERSION', '0.5.2' );", $plugin_source, 'Die Laufzeit-Konstante entspricht der Plugin-Version.' );

echo "PASS: Öffentliche GitHub-Releases werden sicher normalisiert.\n";
