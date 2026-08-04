<?php
/**
 * Sichere Auswertung öffentlicher GitHub-Releases für WordPress-Updates.
 *
 * Die Klasse enthält zunächst bewusst nur reine, testbare Validierungslogik.
 * Netzwerk- und WordPress-Hooks werden erst in einem getrennten Schritt ergänzt.
 * Dadurch kann eine manipulierte oder unvollständige API-Antwort nie als Update
 * an WordPress weitergereicht werden.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prüft Release-Daten des eindeutig festgelegten öffentlichen GitHub-Repositories.
 */
final class MGD_AI_Image_Labels_GitHub_Updater {

	/**
	 * Erwarteter Name des Release-Pakets ohne Versionsnummer.
	 */
	private const PACKAGE_PREFIX = 'mgd-ai-image-labels-';

	/**
	 * Erwartete öffentliche Download-Domain.
	 */
	private const DOWNLOAD_HOST = 'github.com';

	/**
	 * Normalisiert und validiert eine Antwort der GitHub-Releases-API.
	 *
	 * @param array<string, mixed> $release Unverarbeitete API-Antwort.
	 * @return array{version: string, package: string}|array{} Validierte Daten oder einen sicheren Leerwert.
	 */
	public static function normalize_release( array $release ): array {
		$version = self::normalize_version( $release['tag_name'] ?? '' );

		if ( '' === $version || ! is_array( $release['assets'] ?? null ) ) {
			return array();
		}

		$package = self::find_plugin_asset( $release['assets'], $version );

		if ( '' === $package ) {
			return array();
		}

		return array(
			'version' => $version,
			'package' => $package,
		);
	}

	/**
	 * Erlaubt ausschließlich dreiteilige, für WordPress vergleichbare Versionen.
	 *
	 * @param mixed $tag_name Wert aus der GitHub-Antwort.
	 */
	private static function normalize_version( $tag_name ): string {
		if ( ! is_string( $tag_name ) ) {
			return '';
		}

		$version = ltrim( trim( $tag_name ), 'vV' );

		if ( 1 !== preg_match( '/^(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?$/', $version ) ) {
			return '';
		}

		return $version;
	}

	/**
	 * Sucht das exakt zur Version passende ZIP-Paket auf GitHub.
	 *
	 * @param array<int, mixed> $assets  Unverarbeitete Asset-Liste der API.
	 * @param string            $version Bereits geprüfte Version.
	 */
	private static function find_plugin_asset( array $assets, string $version ): string {
		$expected_name = self::PACKAGE_PREFIX . $version . '.zip';

		foreach ( $assets as $asset ) {
			if ( ! is_array( $asset ) || $expected_name !== ( $asset['name'] ?? null ) ) {
				continue;
			}

			$url = $asset['browser_download_url'] ?? '';

			if ( is_string( $url ) && self::is_expected_download_url( $url ) ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * Begrenzt Update-Pakete auf HTTPS-Downloads von GitHub.
	 */
	private static function is_expected_download_url( string $url ): bool {
		$parts = parse_url( $url );

		if ( ! is_array( $parts ) || ! isset( $parts['scheme'], $parts['host'] ) ) {
			return false;
		}

		return 'https' === strtolower( (string) $parts['scheme'] )
			&& 0 === strcasecmp( self::DOWNLOAD_HOST, (string) $parts['host'] );
	}
}
