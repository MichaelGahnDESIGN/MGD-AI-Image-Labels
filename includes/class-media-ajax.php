<?php
/**
 * Sicherer AJAX-Endpunkt für den Speichern-Button im WordPress-Medien-Dialog.
 *
 * Der Endpunkt akzeptiert ausschließlich die drei whitelistenbasierten Werte
 * des Plugins. Berechtigung und Nonce werden bei jedem einzelnen Speichern
 * erneut geprüft. Dadurch genügt es nicht, eine Anhangs-ID im Browser zu ändern.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MGD_AI_Image_Labels_Media_Ajax {

	/** Registriert nur den authentifizierten WordPress-Admin-Endpunkt. */
	public static function register(): void {
		add_action( 'wp_ajax_mgd_ail_save_attachment', array( self::class, 'handle_request' ) );
	}

	/**
	 * Prüft Anfrage und speichert die drei erlaubten Medienwerte.
	 *
	 * wp_send_json_* beendet die Anfrage bewusst. Es wird weder eine Datei
	 * verändert noch ein Bild neu erzeugt; ausschließlich die drei Metadaten
	 * des berechtigten Bild-Anhangs werden aktualisiert.
	 */
	public static function handle_request(): void {
		check_ajax_referer( 'mgd_ail_save_attachment', 'nonce' );

		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

		if ( ! MGD_AI_Image_Labels_Media_Fields::can_edit_image( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => 'Keine Berechtigung für dieses Bild.' ), 403 );
		}

		$values = self::normalize_values( $_POST );
		MGD_AI_Image_Labels_Attachment_Meta::save_values( $attachment_id, $values );

		wp_send_json_success(
			array(
				'message' => 'Kennzeichnung gespeichert.',
				'values'  => $values,
			)
		);
	}

	/**
	 * Reduziert eine ungeprüfte AJAX-Anfrage auf die drei erlaubten Werte.
	 *
	 * @param array<string, mixed> $request Ungeprüfte WordPress-Request-Daten.
	 * @return array{mgd_ail_status: string, mgd_ail_position: string, mgd_ail_theme: string}
	 */
	public static function normalize_values( array $request ): array {
		return array(
			'mgd_ail_status'   => MGD_AI_Image_Labels_Attachment_Meta::sanitize_status( self::unslash_value( $request['status'] ?? 'none' ) ),
			'mgd_ail_position' => MGD_AI_Image_Labels_Attachment_Meta::sanitize_position( self::unslash_value( $request['position'] ?? 'bottom-right' ) ),
			'mgd_ail_theme'    => MGD_AI_Image_Labels_Attachment_Meta::sanitize_theme( self::unslash_value( $request['theme'] ?? 'auto' ) ),
		);
	}

	/** @param mixed $value @return mixed */
	private static function unslash_value( $value ) {
		return is_string( $value ) ? wp_unslash( $value ) : $value;
	}
}
