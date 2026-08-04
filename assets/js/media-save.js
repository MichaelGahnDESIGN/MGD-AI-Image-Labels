/* global MGDAILMediaSave, jQuery */
/**
 * Speichert die KI-Kennzeichnung im WordPress-Medien-Dialog bewusst erst nach
 * einem klaren Klick. Der Code verwendet ausschließlich WordPress-Admin-AJAX.
 */
( function( $ ) {
	'use strict';

	/**
	 * Liest ein Auswahlfeld sicher aus dem aktuell geöffneten Medien-Dialog.
	 *
	 * In der WordPress-Mediathek werden die Anhangsfelder clientseitig erzeugt.
	 * Dabei bleibt im name-Attribut je nach Ansicht der Platzhalter {{ID}}
	 * stehen. Die konkrete ID wird ausschließlich für die gesicherte AJAX-Anfrage
	 * verwendet; beim Auslesen akzeptieren wir beide von WordPress erzeugten
	 * Feldvarianten. So bleibt der Button sowohl im Dialog als auch auf der
	 * Detailansicht des Anhangs funktionsfähig.
	 */
	function readField( attachmentId, fieldName ) {
		var selector = [
			'[name="attachments[' + attachmentId + '][' + fieldName + ']" ]',
			'[name="attachments[{{ID}}][' + fieldName + ']" ]'
		].join( ',' );

		return $( selector ).val() || '';
	}

	/** Zeigt Erfolg oder Fehler im zugehörigen, zugänglichen Statusbereich. */
	function setFeedback( $button, message, isError ) {
		var $feedback = $button.siblings( '.mgd-ail-save-feedback' );
		$feedback
			.text( message )
			.toggleClass( 'notice-error', Boolean( isError ) )
			.toggleClass( 'notice-success', ! isError );
	}

	$( document ).on( 'click', '[data-mgd-ail-save]', function() {
		var $button = $( this );
		var attachmentId = Number( $button.data( 'mgd-ail-save' ) );

		if ( ! attachmentId || ! window.MGDAILMediaSave ) {
			setFeedback( $button, 'Der aktuelle Bild-Anhang konnte nicht bestimmt werden.', true );
			return;
		}

		$button.prop( 'disabled', true );
		setFeedback( $button, MGDAILMediaSave.savingText, false );

		$.post( MGDAILMediaSave.ajaxUrl, {
			action: 'mgd_ail_save_attachment',
			nonce: MGDAILMediaSave.nonce,
			attachment_id: attachmentId,
			status: readField( attachmentId, 'mgd_ail_status' ),
			position: readField( attachmentId, 'mgd_ail_position' ),
			theme: readField( attachmentId, 'mgd_ail_theme' )
		} ).done( function( response ) {
			if ( response && response.success ) {
				setFeedback( $button, response.data.message || MGDAILMediaSave.successText, false );
				return;
			}

			setFeedback( $button, ( response && response.data && response.data.message ) || MGDAILMediaSave.errorText, true );
		} ).fail( function( response ) {
			var message = response.responseJSON && response.responseJSON.data && response.responseJSON.data.message;
			setFeedback( $button, message || MGDAILMediaSave.errorText, true );
		} ).always( function() {
			$button.prop( 'disabled', false );
		} );
	} );
}( jQuery ) );
