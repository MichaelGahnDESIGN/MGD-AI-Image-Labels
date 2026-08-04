<?php
/**
 * Felder für die WordPress-Mediathek.
 *
 * Die Felder erscheinen ausschließlich bei Bildern und nur für Personen mit
 * der WordPress-Berechtigung "Dateien hochladen". WordPress prüft den Nonce
 * der Media-Form vor dem Filter "attachment_fields_to_save" selbst; diese
 * Klasse ergänzt zusätzlich die notwendige Berechtigungs- und MIME-Prüfung.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MGD_AI_Image_Labels_Media_Fields {

	/** Registriert die beiden von WordPress vorgesehenen Medien-Hooks. */
	public static function register(): void {
		add_filter( 'attachment_fields_to_edit', array( self::class, 'add_fields' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( self::class, 'save_fields' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_media_script' ) );
	}

	/**
	 * Ergänzt drei zugängliche Auswahlfelder im Anhangsformular.
	 *
	 * @param array<string, mixed> $fields Bereits vorhandene WordPress-Felder.
	 * @param WP_Post              $post   Bearbeiteter Medien-Anhang.
	 * @return array<string, mixed>
	 */
	public static function add_fields( array $fields, WP_Post $post ): array {
		if ( ! self::can_edit_image( (int) $post->ID ) ) {
			return $fields;
		}

		$values = MGD_AI_Image_Labels_Attachment_Meta::get_values( (int) $post->ID );

		$fields['mgd_ail_status'] = self::select_field(
			'KI-Kennzeichnung',
			$values['status'],
			array(
				'none'                => 'Keine KI',
				'generated'           => 'Mit KI erstellt',
				'partially-generated' => 'Teilweise KI generiert',
				'modified'            => 'Mit KI bearbeitet',
				'deepfake'            => 'Deepfake / täuschend echt',
			),
			'Legt die transparente Kennzeichnung für dieses Bild fest.'
		);
		$fields['mgd_ail_position'] = self::select_field(
			'Position der Kennzeichnung',
			$values['position'],
			array(
				'top-left'     => 'Oben links',
				'top-right'    => 'Oben rechts',
				'bottom-left'  => 'Unten links',
				'bottom-right' => 'Unten rechts',
			),
			'Bestimmt die spätere Ecke des Labels auf dem Bild.'
		);
		$fields['mgd_ail_theme'] = self::select_field(
			'Glas-Variante',
			$values['theme'],
			array(
				'auto'  => 'Automatisch',
				'light' => 'Hell',
				'dark'  => 'Dunkel',
			),
			'Wählt die helle oder dunkle Glasoptik; automatisch ist der sichere Standard.'
		);
		$fields['mgd_ail_save'] = array(
			'label' => 'KI-Kennzeichnung speichern',
			'input' => 'html',
			'html'  => self::get_save_button_html( (int) $post->ID ),
			'helps' => 'Übernimmt Status, Position und Glas-Variante für dieses Bild.',
		);

		return $fields;
	}

	/**
	 * Speichert Felder nur in der bereits von WordPress nonce-geprüften
	 * Medienanfrage und ausschließlich für berechtigte Bild-Anhänge.
	 *
	 * @param array<string, mixed> $post       Anhangsdaten, die WordPress speichert.
	 * @param array<string, mixed> $attachment Eingesendete Medienfelder.
	 * @return array<string, mixed>
	 */
	public static function save_fields( array $post, array $attachment ): array {
		$attachment_id = isset( $post['ID'] ) ? (int) $post['ID'] : 0;

		if ( $attachment_id > 0 && self::can_edit_image( $attachment_id ) ) {
			MGD_AI_Image_Labels_Attachment_Meta::save_values( $attachment_id, $attachment );
		}

		return $post;
	}

	/**
	 * Bindet die kleine, lokale Bedienhilfe ausschließlich in WordPress-Medienansichten ein.
	 *
	 * Der Button ist nötig, weil der Medien-Modal eigene Zusatzfelder zwar darstellt,
	 * sie aber nicht in jedem WordPress-Dialog automatisch speichert. Die Datei nutzt
	 * ausschließlich den WordPress-Admin-AJAX-Endpunkt und keine externe Ressource.
	 */
	public static function enqueue_media_script( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'upload.php', 'media-new.php', 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_script(
			'mgd-ail-media-save',
			MGD_AI_IMAGE_LABELS_URL . 'assets/js/media-save.js',
			array( 'jquery' ),
			MGD_AI_IMAGE_LABELS_VERSION,
			true
		);

		wp_localize_script(
			'mgd-ail-media-save',
			'MGDAILMediaSave',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'mgd_ail_save_attachment' ),
				'savingText'    => 'Speichere …',
				'successText'   => 'Kennzeichnung gespeichert.',
				'errorText'     => 'Die Kennzeichnung konnte nicht gespeichert werden.',
			)
		);
	}

	/**
	 * Baut eine klare Aktion mit einer für Screenreader erreichbaren Rückmeldung.
	 *
	 * Die Anhangs-ID wird als ganzzahliges Datenattribut ausgegeben. Es handelt
	 * sich nicht um eine Berechtigung: Der AJAX-Endpunkt prüft sie erneut.
	 */
	public static function get_save_button_html( int $attachment_id ): string {
		return sprintf(
			'<button type="button" class="button button-primary" data-mgd-ail-save="%1$d">%2$s</button><span class="mgd-ail-save-feedback" aria-live="polite"></span>',
			$attachment_id,
			esc_html( 'Kennzeichnung speichern' )
		);
	}

	/** Prüft Berechtigung und stellt sicher, dass es wirklich ein Bild ist. */
	public static function can_edit_image( int $attachment_id ): bool {
		// Eine allgemeine Upload-Berechtigung reicht nicht: Die Person muss den
		// konkreten Anhang auch bearbeiten dürfen. Damit kann ein Nutzer niemals
		// Kennzeichnungen an fremden oder geschützten Medien ändern.
		if ( $attachment_id <= 0 || ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_post', $attachment_id ) ) {
			return false;
		}

		$mime_type = (string) get_post_mime_type( $attachment_id );

		return 0 === strpos( $mime_type, 'image/' );
	}

	/**
	 * Baut das sichere WordPress-Feldformat; alle Texte und Werte werden vor
	 * der Ausgabe escaped, damit Medien-Metadaten niemals HTML einschleusen.
	 *
	 * @param array<string, string> $options
	 * @return array{label: string, input: string, html: string, value: string, helps: string}
	 */
	private static function select_field( string $label, string $selected, array $options, string $help ): array {
		$html = '<select name="attachments[{{ID}}][' . esc_attr( self::field_name_from_label( $label ) ) . ']">';

		foreach ( $options as $value => $option_label ) {
			$html .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $value ),
				selected( $selected, $value, false ),
				esc_html( $option_label )
			);
		}

		$html .= '</select>';

		return array(
			'label' => $label,
			'input' => 'html',
			'html'  => $html,
			'value' => $selected,
			'helps' => $help,
		);
	}

	/** Ordnet die drei festen Anzeigenamen den erlaubten Request-Schlüsseln zu. */
	private static function field_name_from_label( string $label ): string {
		$fields = array(
			'KI-Kennzeichnung'             => 'mgd_ail_status',
			'Position der Kennzeichnung'    => 'mgd_ail_position',
			'Glas-Variante'                 => 'mgd_ail_theme',
		);

		return $fields[ $label ];
	}
}
