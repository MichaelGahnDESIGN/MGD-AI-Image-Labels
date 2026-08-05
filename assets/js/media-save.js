/**
 * Speichert die KI-Kennzeichnung aus WordPress- und Divi-5-Medienfenstern.
 *
 * Divi 5 rendert den Visual Builder in einem eigenen Iframe. Deshalb darf der
 * Handler weder auf jQuery noch auf ein nur im äußeren Dokument verfügbares
 * JavaScript-Objekt angewiesen sein. URL und Nonce liegen bewusst am bereits
 * serverseitig gerenderten Button; der Server prüft beides zusätzlich.
 */
( function() {
	'use strict';

	/** Prüft, ob ein DOM-Element sichtbar ist, ohne von jQuery abhängig zu sein. */
	function isVisible( element ) {
		return Boolean( element && ( element.offsetWidth || element.offsetHeight || element.getClientRects().length ) );
	}

	/** Gibt das aktive Detailpanel des aktuell bearbeiteten WordPress-Anhangs zurück. */
	function getDialogScope( button ) {
		var scope = button.closest( '.attachment-details' );

		if ( scope && isVisible( scope ) ) {
			return scope;
		}

		var modal = button.closest( '.media-modal, .media-frame-content' );
		var panels = modal ? Array.prototype.slice.call( modal.querySelectorAll( '.attachment-details' ) ) : [];

		for ( var index = panels.length - 1; index >= 0; index-- ) {
			if ( isVisible( panels[ index ] ) ) {
				return panels[ index ];
			}
		}

		return null;
	}

	/** Liest ein Feld aus dem aktiven Panel, auch wenn WordPress {{ID}} beibehält. */
	function getField( scope, attachmentId, fieldName ) {
		var concreteName = 'attachments[' + attachmentId + '][' + fieldName + ']';
		var templateName = 'attachments[{{ID}}][' + fieldName + ']';
		var fields = scope.querySelectorAll( '[name]' );

		for ( var index = 0; index < fields.length; index++ ) {
			var fieldNameValue = fields[ index ].getAttribute( 'name' );

			if ( concreteName === fieldNameValue || templateName === fieldNameValue ) {
				return fields[ index ];
			}
		}

		return null;
	}

	/** Schreibt die bestätigten Serverwerte zurück in den sichtbaren Dialog. */
	function applySavedValues( scope, attachmentId, values ) {
		if ( ! values ) {
			return;
		}

		var mapping = {
			mgd_ail_status: values.mgd_ail_status || 'none',
			mgd_ail_position: values.mgd_ail_position || 'bottom-right',
			mgd_ail_theme: values.mgd_ail_theme || 'auto'
		};

		Object.keys( mapping ).forEach( function( fieldName ) {
			var field = getField( scope, attachmentId, fieldName );

			if ( field ) {
				field.value = mapping[ fieldName ];
				field.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			}
		} );
	}

	/** Gibt direkt beim zugehörigen Button eine barrierefreie Rückmeldung aus. */
	function setFeedback( button, message, isError ) {
		var feedback = button.parentElement ? button.parentElement.querySelector( '.mgd-ail-save-feedback' ) : null;

		if ( ! feedback ) {
			return;
		}

		feedback.textContent = message;
		feedback.classList.toggle( 'notice-error', Boolean( isError ) );
		feedback.classList.toggle( 'notice-success', ! isError );
	}

	/** Sendet nur die drei whitelistenbasierten Werte an den geschützten AJAX-Endpunkt. */
	function saveValues( button, values, onSuccess, onError ) {
		var request = new XMLHttpRequest();
		var payload = new URLSearchParams();

		payload.set( 'action', 'mgd_ail_save_attachment' );
		payload.set( 'nonce', button.getAttribute( 'data-mgd-ail-nonce' ) || '' );
		payload.set( 'attachment_id', button.getAttribute( 'data-mgd-ail-save' ) || '' );
		payload.set( 'status', values.status );
		payload.set( 'position', values.position );
		payload.set( 'theme', values.theme );

		request.open( 'POST', button.getAttribute( 'data-mgd-ail-ajax-url' ) || '', true );
		request.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8' );
		request.setRequestHeader( 'X-Requested-With', 'XMLHttpRequest' );
		request.onreadystatechange = function() {
			if ( 4 !== request.readyState ) {
				return;
			}

			try {
				var response = JSON.parse( request.responseText || '{}' );

				if ( request.status >= 200 && request.status < 300 && response.success ) {
					onSuccess( response );
					return;
				}

				onError( response && response.data && response.data.message );
			} catch ( error ) {
				onError();
			}
		};
		request.onerror = function() {
			onError();
		};
		request.send( payload.toString() );
	}

	/**
	 * Capture-Phase ist wichtig: Divi kann Medienmodal-Ereignisse in seiner
	 * eigenen UI verarbeiten. Der lokale Speichern-Button wird daher zuverlässig
	 * erkannt, ohne das restliche Divi-Verhalten zu verändern.
	 */
	document.addEventListener( 'click', function( event ) {
		var target = event.target instanceof Element ? event.target : null;
		var button = target ? target.closest( '[data-mgd-ail-save]' ) : null;

		if ( ! button ) {
			return;
		}

		event.preventDefault();

		var attachmentId = Number( button.getAttribute( 'data-mgd-ail-save' ) );
		var scope = getDialogScope( button );
		var statusField = scope ? getField( scope, attachmentId, 'mgd_ail_status' ) : null;
		var positionField = scope ? getField( scope, attachmentId, 'mgd_ail_position' ) : null;
		var themeField = scope ? getField( scope, attachmentId, 'mgd_ail_theme' ) : null;

		if ( ! attachmentId || ! scope || ! statusField || ! positionField || ! themeField || ! button.getAttribute( 'data-mgd-ail-ajax-url' ) || ! button.getAttribute( 'data-mgd-ail-nonce' ) ) {
			setFeedback( button, 'Der aktuelle Bild-Anhang konnte nicht bestimmt werden. Bitte schließe den Dialog und öffne das Bild erneut.', true );
			return;
		}

		button.disabled = true;
		setFeedback( button, 'Speichere …', false );
		saveValues(
			button,
			{
				status: statusField.value,
				position: positionField.value,
				theme: themeField.value
			},
			function( response ) {
				applySavedValues( scope, attachmentId, response.data && response.data.values );
				setFeedback( button, ( response.data && response.data.message ) || 'Kennzeichnung gespeichert.', false );
				button.disabled = false;
			},
			function( message ) {
				setFeedback( button, message || 'Die Kennzeichnung konnte nicht gespeichert werden.', true );
				button.disabled = false;
			}
		);
	}, true );
}() );
