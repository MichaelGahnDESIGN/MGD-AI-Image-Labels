<?php
/**
 * Zentrale Plugin-Klasse für MGD KI-Bildkennzeichnung.
 *
 * Die Klasse lädt die klar getrennten Komponenten für Medienfelder, den
 * geschützten Speichern-Endpunkt und die lokale Frontend-Ausgabe.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kapselt die Initialisierung des Plugins.
 */
final class MGD_AI_Image_Labels_Plugin {

	/**
	 * Registriert den klar abgegrenzten Initialisierungspunkt im WordPress-Lebenszyklus.
	 */
	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
	}

	/**
	 * Lädt und registriert die einzelnen Plugin-Funktionen.
	 */
	public static function register(): void {
		require_once MGD_AI_IMAGE_LABELS_DIR . 'includes/class-attachment-meta.php';
		require_once MGD_AI_IMAGE_LABELS_DIR . 'includes/class-media-fields.php';
		require_once MGD_AI_IMAGE_LABELS_DIR . 'includes/class-media-ajax.php';
		require_once MGD_AI_IMAGE_LABELS_DIR . 'includes/class-image-renderer.php';

		// Die Komponenten ergänzen Felder in der Mediathek und eine rein lokale,
		// nicht destruktive Ausgabe bei WordPress-Bildern. Divi-Inhalte, Header,
		// Footer und Menü werden dabei nicht verändert.
		MGD_AI_Image_Labels_Media_Fields::register();
		MGD_AI_Image_Labels_Media_Ajax::register();
		MGD_AI_Image_Labels_Image_Renderer::register();
	}
}
