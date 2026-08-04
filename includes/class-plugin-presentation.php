<?php
/**
 * Sichtbare, aber zurückhaltende Service-Links in der WordPress-Pluginliste.
 *
 * Diese Klasse speichert keine Einstellungen und lädt keine Fremdskripte. Sie
 * ergänzt ausschließlich die Metazeile des eigenen Plugins um sichere Links
 * und den Einstieg in die native WordPress-Detailansicht.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kapselt die Darstellung des Plugins im WordPress-Backend.
 */
final class MGD_AI_Image_Labels_Plugin_Presentation {

	private const WEBSITE_URL       = 'https://michael-gahn.de/';
	private const SUPPORT_URL       = 'https://michael-gahn.de/support/';
	private const DOCUMENTATION_URL = 'https://github.com/MichaelGahnDESIGN/MGD-AI-Image-Labels/wiki';
	private const REPOSITORY_URL    = 'https://github.com/MichaelGahnDESIGN/MGD-AI-Image-Labels';
	private const PLUGIN_SLUG       = 'mgd-ai-image-labels';

	/** Registriert ausschließlich die WordPress-Pluginlisten-Erweiterung. */
	public static function register(): void {
		add_filter( 'plugin_row_meta', array( self::class, 'add_row_meta' ), 10, 4 );
	}

	/**
	 * Fügt nachvollziehbare Projekt- und Hilfe-Links nur zum eigenen Plugin hinzu.
	 *
	 * @param array<int, string>    $plugin_meta Bereits vorhandene Plugin-Metadaten.
	 * @param string                $plugin_file Plugin-Datei relativ zum Plugin-Ordner.
	 * @param array<string, mixed>  $plugin_data WordPress-Metadaten des Plugins.
	 * @param string                $status      Aktueller WordPress-Listenfilter.
	 * @return array<int, string>
	 */
	public static function add_row_meta( array $plugin_meta, string $plugin_file, array $plugin_data, string $status ): array {
		unset( $plugin_data, $status );

		if ( plugin_basename( MGD_AI_IMAGE_LABELS_FILE ) !== $plugin_file ) {
			return $plugin_meta;
		}

		$plugin_meta[] = self::external_link( self::WEBSITE_URL, 'Michael Gahn DESIGN' );
		$plugin_meta[] = self::details_link();
		$plugin_meta[] = self::external_link( self::DOCUMENTATION_URL, 'Dokumentation' );
		$plugin_meta[] = self::external_link( self::SUPPORT_URL, 'Support' );
		$plugin_meta[] = self::external_link( self::REPOSITORY_URL, 'GitHub' );

		return $plugin_meta;
	}

	/** Baut einen sicheren Link, der bewusst in einem neuen Tab geöffnet wird. */
	private static function external_link( string $url, string $label ): string {
		return sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $url ),
			esc_html( $label )
		);
	}

	/** Baut die WordPress-eigene Thickbox-URL für „Details anzeigen“. */
	private static function details_link(): string {
		$url = add_query_arg(
			array(
				'tab'       => 'plugin-information',
				'plugin'    => self::PLUGIN_SLUG,
				'TB_iframe' => 'true',
				'width'     => '772',
				'height'    => '820',
			),
			self_admin_url( 'plugin-install.php' )
		);

		return sprintf(
			'<a href="%1$s" class="thickbox open-plugin-details-modal" aria-label="%2$s">%3$s</a>',
			esc_url( $url ),
			esc_attr( 'Details zu MGD KI-Bildkennzeichnung anzeigen' ),
			esc_html( 'Details anzeigen' )
		);
	}
}
