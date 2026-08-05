<?php
/**
 * Sichere Shortcodes für KI-Labels auf CSS-Hintergrundbildern.
 *
 * Der Shortcode verändert weder Bilder noch Divi-Container. Er gibt nur das
 * bereits zentral gerenderte Label einer ausdrücklich gekennzeichneten
 * WordPress-Bild-ID aus.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MGD_AI_Image_Labels_Shortcodes {

	/** @var array<string, string> Ausschließlich unterstützte Shortcode-Attribute. */
	private const DEFAULT_ATTRIBUTES = array(
		'image_id' => '',
		'class'    => '',
		'offset_x' => '0',
		'offset_y' => '0',
	);

	/** Registriert ausschließlich den fest benannten Hintergrund-Shortcode. */
	public static function register(): void {
		add_shortcode( 'mgd_ai_label', array( self::class, 'render_label' ) );
	}

	/**
	 * Gibt nur das Label einer sicher validierten Bild-ID aus.
	 *
	 * Unbekannte Attribute werden bereits durch `shortcode_atts()` verworfen.
	 * Jeder bekannte Wert wird anschließend streng geprüft. Sobald ein Wert
	 * ungültig ist, bleibt die Ausgabe vollständig leer; Attribut-HTML wird nie
	 * repariert oder teilweise übernommen.
	 *
	 * @param array<string, mixed> $attributes Ungeprüfte Shortcode-Attribute.
	 */
	public static function render_label( array $attributes ): string {
		/* shortcode_atts() ignoriert fremde Schlüssel regulär. Für diesen sicher
		 * begrenzten Shortcode ist das absichtlich nicht ausreichend: Ein Tippfehler
		 * oder Manipulationsversuch darf nicht mit einer scheinbar gültigen Ausgabe
		 * weiterlaufen. */
		if ( array() !== array_diff_key( $attributes, self::DEFAULT_ATTRIBUTES ) ) {
			return '';
		}

		$attributes = shortcode_atts( self::DEFAULT_ATTRIBUTES, $attributes );

		$image_id = self::validate_positive_integer( $attributes['image_id'] );
		$offset_x = self::validate_offset( $attributes['offset_x'] );
		$offset_y = self::validate_offset( $attributes['offset_y'] );
		$classes  = self::validate_classes( $attributes['class'] );

		if ( null === $image_id || null === $offset_x || null === $offset_y || null === $classes ) {
			return '';
		}

		$badge = MGD_AI_Image_Labels_Image_Renderer::render_label_only( $image_id, array() );

		if ( '' === $badge ) {
			return '';
		}

		$class_attribute = 'mgd-ail-background-label';
		if ( array() !== $classes ) {
			$class_attribute .= ' ' . implode( ' ', $classes );
		}

		/* Stilwerte bestehen hier ausschließlich aus validierten Ganzzahlen und
		 * fest bekannten Eigenschaftsnamen. Freies CSS wird nicht akzeptiert. */
		$style_attribute = sprintf(
			'--mgd-ail-offset-x:%1$dpx;--mgd-ail-offset-y:%2$dpx',
			$offset_x,
			$offset_y
		);

		return sprintf(
			'<span class="%1$s" style="%2$s">%3$s</span>',
			esc_attr( $class_attribute ),
			esc_attr( $style_attribute ),
			$badge
		);
	}

	/**
	 * Akzeptiert nur positive Ganzzahlen in ihrer eindeutigen Dezimalschreibweise.
	 *
	 * @param mixed $value Ungeprüfte Bild-ID.
	 */
	private static function validate_positive_integer( $value ): ?int {
		if ( is_int( $value ) ) {
			return $value > 0 ? $value : null;
		}

		if ( ! is_string( $value ) || 1 !== preg_match( '/^[1-9][0-9]*$/', $value ) ) {
			return null;
		}

		$integer = (int) $value;

		return $integer > 0 && (string) $integer === $value ? $integer : null;
	}

	/**
	 * Erlaubt ausschließlich ganzzahlige Pixelwerte von 0 bis 192.
	 *
	 * @param mixed $value Ungeprüfter Offset.
	 */
	private static function validate_offset( $value ): ?int {
		if ( is_int( $value ) ) {
			return $value >= 0 && $value <= 192 ? $value : null;
		}

		if ( ! is_string( $value ) || 1 !== preg_match( '/^(?:0|[1-9][0-9]{0,2})$/', $value ) ) {
			return null;
		}

		$integer = (int) $value;

		return $integer <= 192 ? $integer : null;
	}

	/**
	 * Prüft höchstens drei unabhängige CSS-Klassentokens mit WordPress-Mitteln.
	 *
	 * `sanitize_html_class()` darf einen manipulierten Wert nicht stillschweigend
	 * verändern: Nur ein bereits vollständig sicherer Token wird übernommen.
	 *
	 * @param mixed $value Ungeprüfte zusätzliche CSS-Klassen.
	 * @return array<int, string>|null
	 */
	private static function validate_classes( $value ): ?array {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = trim( $value );
		if ( '' === $value ) {
			return array();
		}

		$classes = preg_split( '/\s+/', $value );
		if ( false === $classes || count( $classes ) > 3 ) {
			return null;
		}

		foreach ( $classes as $class ) {
			if ( '' === $class || sanitize_html_class( $class ) !== $class ) {
				return null;
			}
		}

		return $classes;
	}
}
