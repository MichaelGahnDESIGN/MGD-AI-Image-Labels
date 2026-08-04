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
	function getDialogScope( $button ) {
		var $scope = $button.closest( '.attachment-details' );

		/*
		 * WordPress zeigt je nach Medienansicht unterschiedliche Hüllen an. Der
		 * sichtbare Detailsbereich ist ein sicherer Fallback, falls der Button
		 * von einer abweichenden WordPress-Ansicht verschoben wurde.
		 */
		if ( ! $scope.length ) {
			$scope = $button.closest( '.media-frame-content' ).find( '.attachment-details:visible' ).last();
		}

		return $scope;
	}

	function readField( $scope, attachmentId, fieldName ) {
		var selector = [
			'[name="attachments[' + attachmentId + '][' + fieldName + ']" ]',
			'[name="attachments[{{ID}}][' + fieldName + ']" ]'
		].join( ',' );

		/*
		 * Die Suche darf nicht das gesamte Medienfenster durchlaufen: WordPress
		 * kann dort Felder zuvor geöffneter Anhänge im DOM behalten. .first()
		 * bezieht sich deshalb ausschließlich auf das aktive Detailpanel.
		 */
		return $scope.find( selector ).first().val() || '';
	}

	function writeField( $scope, attachmentId, fieldName, value ) {
		var selector = [
			'[name="attachments[' + attachmentId + '][' + fieldName + ']" ]',
			'[name="attachments[{{ID}}][' + fieldName + ']" ]'
		].join( ',' );

		$scope.find( selector ).first().val( value ).trigger( 'change' );
	}

	function applySavedValues( $scope, attachmentId, values ) {
		if ( ! values ) {
			return;
		}

		writeField( $scope, attachmentId, 'mgd_ail_status', values.mgd_ail_status || 'none' );
		writeField( $scope, attachmentId, 'mgd_ail_position', values.mgd_ail_position || 'bottom-right' );
		writeField( $scope, attachmentId, 'mgd_ail_theme', values.mgd_ail_theme || 'auto' );
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
		var $scope = getDialogScope( $button );
		var values = {
			status: readField( $scope, attachmentId, 'mgd_ail_status' ),
			position: readField( $scope, attachmentId, 'mgd_ail_position' ),
			theme: readField( $scope, attachmentId, 'mgd_ail_theme' )
		};

		if ( ! attachmentId || ! window.MGDAILMediaSave || ! $scope.length ) {
			setFeedback( $button, 'Der aktuelle Bild-Anhang konnte nicht bestimmt werden.', true );
			return;
		}

		if ( ! values.status || ! values.position || ! values.theme ) {
			setFeedback( $button, 'Die Kennzeichnungsfelder konnten nicht gelesen werden. Bitte schließe den Dialog und öffne das Bild erneut.', true );
			return;
		}

		$button.prop( 'disabled', true );
		setFeedback( $button, MGDAILMediaSave.savingText, false );

		$.post( MGDAILMediaSave.ajaxUrl, {
			action: 'mgd_ail_save_attachment',
			nonce: MGDAILMediaSave.nonce,
			attachment_id: attachmentId,
			status: values.status,
			position: values.position,
			theme: values.theme
		} ).done( function( response ) {
			if ( response && response.success ) {
				applySavedValues( $scope, attachmentId, response.data && response.data.values );
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
