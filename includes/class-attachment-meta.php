<?php
/**
 * Sichere Verwaltung der drei Metadaten einer KI-Bildkennzeichnung.
 *
 * Es werden absichtlich weder Bilddateien noch Alt-Texte verändert. Diese
 * Klasse kennt ausschließlich die freigegebenen Auswahlwerte und speichert
 * sie pro WordPress-Anhang.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MGD_AI_Image_Labels_Attachment_Meta {

	public const STATUS_KEY   = '_mgd_ail_status';
	public const POSITION_KEY = '_mgd_ail_position';
	public const THEME_KEY    = '_mgd_ail_theme';

	/** @var array<int, string> */
	private const STATUSES = array( 'none', 'generated', 'partially-generated', 'modified', 'deepfake' );

	/** @var array<int, string> */
	private const POSITIONS = array( 'top-left', 'top-right', 'bottom-left', 'bottom-right' );

	/** @var array<int, string> */
	private const THEMES = array( 'auto', 'light', 'dark' );

	/**
	 * Gibt den zulässigen Status zurück. Unbekannte oder manipulierte Werte
	 * werden sicher als "none" behandelt und zeigen damit kein Badge an.
	 *
	 * @param mixed $value Ungeprüfte Eingabe aus WordPress.
	 */
	public static function sanitize_status( $value ): string {
		return self::sanitize_from_list( $value, self::STATUSES, 'none' );
	}

	/**
	 * Gibt eine von vier sicheren Ecken für die spätere Darstellung zurück.
	 *
	 * @param mixed $value Ungeprüfte Eingabe aus WordPress.
	 */
	public static function sanitize_position( $value ): string {
		return self::sanitize_from_list( $value, self::POSITIONS, 'bottom-right' );
	}

	/**
	 * Gibt die gewünschte Glas-Variante oder den sicheren Auto-Modus zurück.
	 *
	 * @param mixed $value Ungeprüfte Eingabe aus WordPress.
	 */
	public static function sanitize_theme( $value ): string {
		return self::sanitize_from_list( $value, self::THEMES, 'auto' );
	}

	/**
	 * Liest die drei Werte stets mit sicheren Standardwerten.
	 *
	 * Position und Glas-Variante eines bereits redaktionell bearbeiteten Bildes
	 * haben immer Vorrang. Bei älteren oder noch nicht vollständig gepflegten
	 * Anhängen fehlen diese Metadaten jedoch häufig. Dann werden die zentralen,
	 * ebenfalls validierten Standards aus der Plugin-Verwaltung verwendet.
	 *
	 * @return array{status: string, position: string, theme: string}
	 */
	public static function get_values( int $attachment_id ): array {
		$display_options = self::get_display_options();

		return array(
			'status'   => self::sanitize_status( get_post_meta( $attachment_id, self::STATUS_KEY, true ) ),
			'position' => self::sanitize_from_list(
				get_post_meta( $attachment_id, self::POSITION_KEY, true ),
				self::POSITIONS,
				$display_options['position']
			),
			'theme'    => self::sanitize_from_list(
				get_post_meta( $attachment_id, self::THEME_KEY, true ),
				self::THEMES,
				$display_options['theme']
			),
		);
	}

	/**
	 * Liest die globalen Vorgaben, ohne isolierte Integrations- oder
	 * Migrationstests von der später geladenen Optionsklasse abhängig zu machen.
	 *
	 * Im regulären Plugin-Boot wird die Optionsklasse immer vor einem Aufruf von
	 * `get_values()` geladen. Der konservative Fallback schützt dennoch gegen
	 * ungewöhnliche Aufrufreihenfolgen und entspricht den Standardwerten der
	 * Optionsklasse.
	 *
	 * @return array{position: string, theme: string}
	 */
	private static function get_display_options(): array {
		if ( class_exists( 'MGD_AI_Image_Labels_Plugin_Options' ) ) {
			$options = MGD_AI_Image_Labels_Plugin_Options::get_options();

			return array(
				'position' => self::sanitize_position( $options['position'] ?? 'bottom-right' ),
				'theme'    => self::sanitize_theme( $options['theme'] ?? 'auto' ),
			);
		}

		return array(
			'position' => 'bottom-right',
			'theme'    => 'auto',
		);
	}

	/**
	 * Speichert nur die drei ausdrücklich erlaubten Metaschlüssel.
	 *
	 * Der Status "none" wird bewusst ebenfalls gespeichert. So ist intern
	 * nachvollziehbar, dass ein Bild geprüft wurde, ohne etwas sichtbar zu
	 * kennzeichnen.
	 *
	 * @param array<string, mixed> $values Werte aus dem Mediathekformular.
	 */
	public static function save_values( int $attachment_id, array $values ): void {
		update_post_meta( $attachment_id, self::STATUS_KEY, self::sanitize_status( $values['mgd_ail_status'] ?? 'none' ) );
		update_post_meta( $attachment_id, self::POSITION_KEY, self::sanitize_position( $values['mgd_ail_position'] ?? 'bottom-right' ) );
		update_post_meta( $attachment_id, self::THEME_KEY, self::sanitize_theme( $values['mgd_ail_theme'] ?? 'auto' ) );
	}

	/**
	 * @param mixed             $value Ungeprüfte Eingabe.
	 * @param array<int, string> $allowed Freigegebene Werte.
	 */
	private static function sanitize_from_list( $value, array $allowed, string $fallback ): string {
		if ( ! is_string( $value ) ) {
			return $fallback;
		}

		$value = strtolower( trim( $value ) );

		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}
}
