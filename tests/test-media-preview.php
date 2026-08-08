<?php
/**
 * Regressionstest für die rein visuelle Vorschau im WordPress-Medienmodal.
 *
 * Die Vorschau darf nie Medienmetadaten speichern oder an externe Dienste
 * senden. Sie liest ausschließlich die drei sichtbaren Auswahlfelder und
 * zeichnet das Kennzeichen als temporären DOM-Knoten über das Vorschaubild.
 */

declare(strict_types=1);

$script = file_get_contents( dirname( __DIR__ ) . '/assets/js/media-preview.js' );
$style  = file_get_contents( dirname( __DIR__ ) . '/assets/css/media-preview.css' );
$fields = file_get_contents( dirname( __DIR__ ) . '/includes/class-media-fields.php' );
$options = file_get_contents( dirname( __DIR__ ) . '/includes/class-plugin-options.php' );

if ( false === $script || false === $style || false === $fields || false === $options ) {
	throw new RuntimeException( 'Die Dateien für die Medienvorschau konnten nicht gelesen werden.' );
}

/** Prüft eine bewusst konkrete technische Eigenschaft der lokalen Vorschau. */
function mgd_ail_preview_assert_contains( string $needle, string $haystack, string $message ): void {
	if ( false === strpos( $haystack, $needle ) ) {
		throw new RuntimeException( $message . ' Fehlend: ' . $needle );
	}
}

mgd_ail_preview_assert_contains( 'mgd-ail-media-preview', $script, 'Die Vorschau braucht einen eindeutig getrennten DOM-Knoten.' );
mgd_ail_preview_assert_contains( 'mgd-ail-media-preview-canvas', $script, 'Die Vorschau braucht eine eigene, auf das sichtbare Bild begrenzte Zeichenfläche.' );
mgd_ail_preview_assert_contains( 'getBoundingClientRect', $script, 'Die Vorschau muss sich an den tatsächlichen Bildmaßen statt am hohen Mediencontainer ausrichten.' );
mgd_ail_preview_assert_contains( 'ResizeObserver', $script, 'Eine geöffnete Vorschau muss sich auch nach einer Größenänderung des WordPress-Modals erneut an der Bildfläche ausrichten.' );
mgd_ail_preview_assert_contains( "window.addEventListener( 'resize'", $script, 'Ein Viewport-Wechsel muss die Vorschau ohne Neuladen neu berechnen.' );
mgd_ail_preview_assert_contains( 'mgd-ail-badge', $script, 'Die Vorschau muss dieselbe Badge-Klasse wie die Frontend-Ausgabe verwenden.' );
mgd_ail_preview_assert_contains( 'AI PARTIALLY GENERATED', $script, 'Die Vorschau muss alle sichtbaren Kennzeichnungsarten abbilden.' );
mgd_ail_preview_assert_contains( "addEventListener( 'change'", $script, 'Die Vorschau muss beim Ändern einer Auswahl sofort reagieren.' );
mgd_ail_preview_assert_contains( "node.closest( '.attachment-details' )", $script, 'Ein Bildwechsel im bestehenden WordPress-Medienmodal muss die Vorschau erneut aufbauen.' );
mgd_ail_preview_assert_contains( "node.closest( '.mgd-ail-media-preview, .mgd-ail-media-preview-canvas' )", $script, 'Die eigene Vorschau darf keinen Beobachter-Kreislauf auslösen.' );
mgd_ail_preview_assert_contains( 'textContent', $script, 'Der sichtbare Labeltext darf nicht als unsicheres HTML eingefügt werden.' );
mgd_ail_preview_assert_contains( 'aria-hidden', $script, 'Die rein dekorative Admin-Vorschau darf Screenreader nicht doppelt informieren.' );

if ( false !== strpos( $script, 'XMLHttpRequest' ) || false !== strpos( $script, 'fetch(' ) ) {
	throw new RuntimeException( 'Die Medienvorschau darf keine Anfrage auslösen oder etwas speichern.' );
}

mgd_ail_preview_assert_contains( '.mgd-ail-media-preview', $style, 'Die Vorschau benötigt eine eigene, auf das Medienmodal begrenzte Gestaltung.' );
mgd_ail_preview_assert_contains( '.mgd-ail-media-preview-canvas', $style, 'Die Vorschaufläche muss exakt über dem sichtbaren Bild positioniert werden können.' );
mgd_ail_preview_assert_contains( 'pointer-events: none', $style, 'Das Vorschau-Label darf Bedienung und Bildauswahl nicht überdecken.' );
mgd_ail_preview_assert_contains( 'media-preview.js', $fields, 'Die Vorschau muss in WordPress und im Divi-5-Medienmodal geladen werden.' );
mgd_ail_preview_assert_contains( 'media-preview.css', $fields, 'Die lokale Vorschau-Gestaltung muss gezielt mitgeladen werden.' );
mgd_ail_preview_assert_contains( 'assets/css/frontend.css', $fields, 'Die Vorschau muss das originale Frontend-Stylesheet des Plugins verwenden.' );
mgd_ail_preview_assert_contains( 'get_media_preview_css_variables', $fields, 'Die Vorschau muss exakt dieselben gespeicherten Darstellungswerte wie das Frontend erhalten.' );
mgd_ail_preview_assert_contains( 'function get_media_preview_css_variables', $options, 'Die Frontend-Variablen brauchen eine ausdrücklich auf die lokale Vorschau begrenzte Variante.' );

echo "PASS: Die Medienvorschau bleibt lokal, zugänglich und rein visuell.\n";
