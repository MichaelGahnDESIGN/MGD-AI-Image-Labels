/**
 * Lokale Live-Vorschau der KI-Bildkennzeichnung im WordPress-Medienmodal.
 *
 * Die Datei ist bewusst vollständig unabhängig vom Speichern-Handler. Sie
 * verändert weder Anhangsmetadaten noch Formularwerte und versendet keine
 * Netzwerk-Anfrage. Die Vorschau zeigt ausschließlich, wie die drei bereits
 * sichtbaren Auswahlfelder nach einem späteren Speichern aussehen würden.
 */
( function() {
	'use strict';

	var labels = {
		generated: 'AI GENERATED',
		'partially-generated': 'AI PARTIALLY GENERATED',
		modified: 'AI MODIFIED',
		deepfake: 'AI DEEPFAKE'
	};

	/** Ermittelt den zum aktiven Bild gehörenden Medien-Detailbereich. */
	function getScope( element ) {
		return element.closest( '.attachment-details' );
	}

	/** Liest ein Auswahlfeld trotz des von WordPress verwendeten {{ID}}-Platzhalters. */
	function getField( scope, name ) {
		if ( ! scope ) {
			return null;
		}

		return scope.querySelector( '[name$="[' + name + ']"], [name="attachments[{{ID}}][' + name + ']"]' );
	}

	/** Findet das tatsächlich sichtbare Bild und dessen stabilen Layout-Kontext. */
	function getPreviewCanvas( scope ) {
		if ( ! scope ) {
			return null;
		}

		var image = scope.querySelector( '.attachment-media-view img, .thumbnail img, .media-modal .attachment-preview img' );
		var container = image ? image.closest( '.attachment-media-view, .thumbnail, .attachment-preview' ) : null;

		return image && container ? { image: image, container: container } : null;
	}

	/** Entfernt eine nicht mehr benötigte Vorschau ohne Bild oder Formular zu verändern. */
	function removePreview( container ) {
		if ( ! container ) {
			return;
		}

		var preview = container.querySelector( '.mgd-ail-media-preview-canvas' );
		if ( preview ) {
			preview.remove();
		}
	}

	/**
	 * Erstellt eine deckungsgleiche, interaktionsdurchlässige Ebene über dem
	 * tatsächlichen Bild. Der Mediencontainer kann deutlich höher sein als das
	 * Bild; deshalb wird er hier bewusst nicht als geometrische Referenz genutzt.
	 */
	function createCanvas( image, container ) {
		var imageRect = image.getBoundingClientRect();
		var containerRect = container.getBoundingClientRect();
		var canvas = document.createElement( 'span' );

		canvas.className = 'mgd-ail-media-preview-canvas';
		canvas.setAttribute( 'aria-hidden', 'true' );
		canvas.style.left = ( imageRect.left - containerRect.left ) + 'px';
		canvas.style.top = ( imageRect.top - containerRect.top ) + 'px';
		canvas.style.width = imageRect.width + 'px';
		canvas.style.height = imageRect.height + 'px';
		container.appendChild( canvas );

		return canvas;
	}

	/** Erzeugt die dekorative Vorschau ausschließlich aus festen, lokalen Labeltexten. */
	function createPreview( canvas, status, position, theme ) {
		var preview = document.createElement( 'span' );
		var text = document.createElement( 'span' );

		preview.className = 'mgd-ail-media-preview mgd-ail-position-' + position + ' mgd-ail-theme-' + theme;
		preview.setAttribute( 'aria-hidden', 'true' );
		text.className = 'mgd-ail-media-preview__text';
		text.textContent = labels[ status ];
		preview.appendChild( text );
		canvas.appendChild( preview );
	}

	/** Aktualisiert genau eine geöffnete Anhangsvorschau nach einer Auswahländerung. */
	function updatePreview( scope ) {
		var statusField = getField( scope, 'mgd_ail_status' );
		var positionField = getField( scope, 'mgd_ail_position' );
		var themeField = getField( scope, 'mgd_ail_theme' );
		var previewContext = getPreviewCanvas( scope );

		if ( ! statusField || ! positionField || ! themeField || ! previewContext ) {
			return;
		}

		removePreview( previewContext.container );

		if ( ! labels[ statusField.value ] ) {
			return;
		}

		createPreview( createCanvas( previewContext.image, previewContext.container ), statusField.value, positionField.value, themeField.value );
	}

	/** Aktualisiert die Vorschau bei jeder lokalen Feldänderung – ohne Speicherung. */
	document.addEventListener( 'change', function( event ) {
		var target = event.target instanceof Element ? event.target : null;

		if ( ! target || ! target.matches( '[name$="[mgd_ail_status]"], [name$="[mgd_ail_position]"], [name$="[mgd_ail_theme]"]' ) ) {
			return;
		}

		updatePreview( getScope( target ) );
	} );

	/**
	 * WordPress und Divi erzeugen Medienpanels bei einem Bildwechsel dynamisch.
	 * Der Beobachter erkennt diese rein lokal und zeichnet nur die sichtbare
	 * Vorschau nach; er beobachtet keine redaktionellen Inhalte außerhalb des
	 * Medienmodals und löst keine Anfrage aus.
	 */
	var observer = new MutationObserver( function( mutations ) {
		mutations.forEach( function( mutation ) {
			Array.prototype.forEach.call( mutation.addedNodes, function( node ) {
				if ( ! ( node instanceof Element ) ) {
					return;
				}

				/* Das eigene rein dekorative Label darf sich nicht selbst erneut
				 * auslösen, wenn es in die Bildfläche eingefügt wird. */
				if ( node.matches( '.mgd-ail-media-preview, .mgd-ail-media-preview-canvas' ) || node.closest( '.mgd-ail-media-preview, .mgd-ail-media-preview-canvas' ) ) {
					return;
				}

				var scopes = [];
				if ( node.matches( '.attachment-details' ) ) {
					scopes.push( node );
				}
				/* WordPress ersetzt beim Weiter-/Zurückblättern oft nur einzelne
				 * Felder innerhalb des bestehenden Details-Panels. Der nächstgelegene
				 * Panel-Kontext muss daher ebenfalls neu gezeichnet werden. */
				var parentScope = node.closest( '.attachment-details' );
				if ( parentScope && scopes.indexOf( parentScope ) === -1 ) {
					scopes.push( parentScope );
				}
				Array.prototype.push.apply( scopes, node.querySelectorAll( '.attachment-details' ) );
				scopes.forEach( function( scope ) {
					window.requestAnimationFrame( function() {
						updatePreview( scope );
					} );
				} );
			} );
		} );
	} );

	observer.observe( document.documentElement, { childList: true, subtree: true } );
	Array.prototype.forEach.call( document.querySelectorAll( '.attachment-details' ), updatePreview );
	document.addEventListener( 'load', function( event ) {
		var image = event.target instanceof HTMLImageElement ? event.target : null;
		var scope = image ? getScope( image ) : null;
		if ( scope ) {
			updatePreview( scope );
		}
	}, true );
}() );
