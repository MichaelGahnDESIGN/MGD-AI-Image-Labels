<?php
/**
 * Sichere globale Darstellungsoptionen für KI-Bildkennzeichnungen.
 *
 * Die Klasse hält ausschließlich freigegebene Ganzzahlen und Auswahlwerte.
 * Aus freien Eingaben wird niemals direkt CSS erzeugt. Dadurch bleiben die
 * lokalen Frontend-Styles nachvollziehbar und gegen CSS-Injektionen geschützt.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kapselt Registrierung, Validierung und lokale CSS-Variablen der Darstellung.
 */
final class MGD_AI_Image_Labels_Plugin_Options {

	/** Gemeinsamer Optionsname und Settings Group für die spätere Verwaltung. */
	public const OPTION_NAME = 'mgd_ail_display_options';

	/** @var array<int, string> */
	private const THEMES = array( 'auto', 'light', 'dark' );

	/** @var array<int, string> */
	private const POSITIONS = array( 'top-left', 'top-right', 'bottom-left', 'bottom-right' );

	/**
	 * Registriert genau eine Array-Option mit der zentralen Sanitization.
	 */
	public static function register(): void {
		register_setting(
			self::OPTION_NAME,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'default'           => self::get_defaults(),
				'sanitize_callback' => array( self::class, 'sanitize_options' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Liefert die vollständigen, realistischen Ausgangswerte.
	 *
	 * Alle Zahlen bleiben als Strings gespeichert, damit WordPress-Optionen und
	 * spätere Formularfelder dieselbe eindeutige Repräsentation verwenden.
	 *
	 * @return array<string, string>
	 */
	public static function get_defaults(): array {
		return array(
			'font_size' => '6',
			'offset'    => '12',
			'padding_y' => '5',
			'padding_x' => '9',
			'radius'    => '999',
			'blur'      => '10',
			'theme'     => 'auto',
			'position'  => 'bottom-right',
		);
	}

	/**
	 * Liest gespeicherte Werte nur als Array und validiert sie erneut.
	 *
	 * Diese Prüfung beim Lesen schützt auch vor alten, manuell veränderten oder
	 * anderweitig fehlerhaften Datenbankwerten.
	 *
	 * @return array<string, string>
	 */
	public static function get_options(): array {
		$stored = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( self::get_defaults(), self::sanitize_options( $stored ) );
	}

	/**
	 * Normalisiert ausschließlich freigegebene Ganzzahlen und Auswahlwerte.
	 *
	 * Zahlen mit Einheiten, Vorzeichen, Dezimalstellen oder zusätzlichem Text
	 * gelten bewusst als ungültig und fallen auf den jeweiligen Standard zurück.
	 *
	 * @param mixed $input Ungeprüfte Eingabe aus WordPress.
	 * @return array<string, string> Vollständige, sicher normalisierte Optionen.
	 */
	public static function sanitize_options( $input ): array {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$defaults = self::get_defaults();

		return array(
			'font_size' => self::normalize_integer( $input['font_size'] ?? null, 6, 24, $defaults['font_size'] ),
			'offset'    => self::normalize_integer( $input['offset'] ?? null, 0, 96, $defaults['offset'] ),
			'padding_y' => self::normalize_integer( $input['padding_y'] ?? null, 2, 24, $defaults['padding_y'] ),
			'padding_x' => self::normalize_integer( $input['padding_x'] ?? null, 4, 40, $defaults['padding_x'] ),
			'radius'    => self::normalize_integer( $input['radius'] ?? null, 0, 999, $defaults['radius'] ),
			'blur'      => self::normalize_integer( $input['blur'] ?? null, 0, 24, $defaults['blur'] ),
			'theme'     => self::normalize_choice( $input['theme'] ?? null, self::THEMES, $defaults['theme'] ),
			'position'  => self::normalize_choice( $input['position'] ?? null, self::POSITIONS, $defaults['position'] ),
		);
	}

	/**
	 * Erzeugt einen lokal begrenzten Block aus fest benannten CSS-Variablen.
	 *
	 * Die Zahlen werden nach der Sanitization erneut in Integer umgewandelt.
	 * Damit kann keine gespeicherte Zeichenfolge unverändert in CSS gelangen.
	 */
	public static function get_css_variables(): string {
		return self::get_css_variables_for_selector( '.mgd-ail-image-wrapper' );
	}

	/**
	 * Liefert dieselben geprüften Darstellungswerte für die rein lokale
	 * Medienvorschau. Der Selektor bleibt bewusst auf den temporären Canvas
	 * begrenzt, damit die WordPress-Administration keine anderen Elemente
	 * gestaltet und die Vorschau dem späteren Frontend dennoch exakt entspricht.
	 */
	public static function get_media_preview_css_variables(): string {
		return self::get_css_variables_for_selector( '.mgd-ail-media-preview-canvas' );
	}

	/**
	 * Erzeugt aus den bereits bereinigten Optionen einen engen CSS-Variablenblock.
	 * Der Selektor ist ausschließlich aus dem eigenen Quellcode übergeben und
	 * niemals eine redaktionelle oder externe Eingabe.
	 */
	private static function get_css_variables_for_selector( string $selector ): string {
		$options = self::get_options();

		return sprintf(
			'%1$s {%2$s'
			. '  --mgd-ail-font-size: %3$dpx;%2$s'
			. '  --mgd-ail-offset: %4$dpx;%2$s'
			. '  --mgd-ail-padding-y: %5$dpx;%2$s'
			. '  --mgd-ail-padding-x: %6$dpx;%2$s'
			. '  --mgd-ail-radius: %7$dpx;%2$s'
			. '  --mgd-ail-blur: %8$dpx;%2$s'
			. '}',
			$selector,
			"\n",
			(int) $options['font_size'],
			(int) $options['offset'],
			(int) $options['padding_y'],
			(int) $options['padding_x'],
			(int) $options['radius'],
			(int) $options['blur']
		);
	}

	/**
	 * Akzeptiert nur echte Ganzzahlen oder reine, kanonische Dezimalstrings.
	 *
	 * @param mixed $value Ungeprüfter Einzelwert.
	 */
	private static function normalize_integer( $value, int $minimum, int $maximum, string $fallback ): string {
		if ( is_int( $value ) ) {
			$number = $value;
		} elseif ( is_string( $value ) && 1 === preg_match( '/^(?:0|[1-9][0-9]*)$/D', $value ) ) {
			$number = (int) $value;
		} else {
			return $fallback;
		}

		if ( $number < $minimum || $number > $maximum ) {
			return $fallback;
		}

		return (string) $number;
	}

	/**
	 * Akzeptiert einen Auswahlwert nur bytegenau aus der freigegebenen Liste.
	 *
	 * @param mixed              $value   Ungeprüfter Einzelwert.
	 * @param array<int, string> $allowed Erlaubte Werte.
	 */
	private static function normalize_choice( $value, array $allowed, string $fallback ): string {
		return is_string( $value ) && in_array( $value, $allowed, true ) ? $value : $fallback;
	}
}
