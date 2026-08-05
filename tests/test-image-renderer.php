<?php
/**
 * Eigenständiger Test für die reine Frontend-Ausgabe der KI-Bildkennzeichnung.
 *
 * Der Test benötigt keine WordPress-Installation. Er prüft bewusst ausschließlich
 * das erzeugte HTML: Text, Klassen und den unveränderten Bild-Alt-Text.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

/**
 * Der Harness steuert die WordPress-Umgebung explizit. So lässt sich prüfen,
 * dass Backend- und Builder-Vorschauen unverändert bleiben.
 */
function is_admin(): bool {
	return ! empty( $GLOBALS['mgd_ail_test_is_admin'] );
}

$GLOBALS['mgd_ail_test_is_admin'] = false;

/**
 * Der unabhängige Harness simuliert nur die drei Metawerte. Er verwendet keine
 * WordPress-Installation und macht dadurch die Divi-Ausgabe reproduzierbar.
 *
 * @param int    $attachment_id Unveränderte Anhangs-ID des Testbildes.
 * @param string $key Erwarteter Metaschlüssel.
 * @return string Sicherer Testwert oder ein leerer Standardwert.
 */
function get_post_meta( int $attachment_id, string $key, bool $single = true ): string {
	$values = $GLOBALS['mgd_ail_test_meta'][ $attachment_id ] ?? array();

	return is_string( $values[ $key ] ?? null ) ? $values[ $key ] : '';
}

/**
 * Simuliert die von WordPress gelieferte Liste aktiv gekennzeichneter Medien.
 * Der Frontend-Fallback darf daraus ausschließlich bereits redaktionell
 * freigegebene Anhangs-IDs beziehen.
 *
 * @param array<string, mixed> $arguments Nicht ausgewertete WordPress-Abfrage.
 * @return array<int, int>
 */
function get_posts( array $arguments ): array {
	return array( 55 );
}

/**
 * Liefert die kanonische Original-URL des Testanhangs.
 */
function wp_get_attachment_url( int $attachment_id ): string {
	return 'https://beispiel.test/wp-content/uploads/2026/03/schreibmaschine.jpg';
}

/**
 * Simuliert die WordPress-Metadaten inklusive einer automatisch erzeugten
 * Größenvariante. Genau diese Variante erscheint später im Beitragsbild.
 *
 * @return array<string, mixed>
 */
function wp_get_attachment_metadata( int $attachment_id ): array {
	return array(
		'file'  => '2026/03/schreibmaschine.jpg',
		'sizes' => array(
			'large' => array(
				'file' => 'schreibmaschine-980x551.jpg',
			),
		),
	);
}

/**
 * Stellt die lokale Upload-Basis der Testseite bereit.
 *
 * @return array<string, string>
 */
function wp_upload_dir(): array {
	return array( 'baseurl' => 'https://beispiel.test/wp-content/uploads' );
}

/**
 * Nutzt im Test dieselbe sichere JSON-Schnittstelle wie WordPress.
 *
 * @param mixed $value Zu kodierende Testdaten.
 */
function wp_json_encode( $value, int $flags = 0 ): string {
	return (string) json_encode( $value, $flags );
}

$GLOBALS['mgd_ail_test_meta'] = array(
	55 => array(
		'_mgd_ail_status'   => 'generated',
		'_mgd_ail_position' => 'top-left',
		'_mgd_ail_theme'    => 'dark',
	),
);

require_once dirname( __DIR__ ) . '/includes/class-attachment-meta.php';
require_once dirname( __DIR__ ) . '/includes/class-image-renderer.php';

/** @param mixed $expected @param mixed $actual */
function mgd_ail_renderer_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		throw new RuntimeException(
			$message . ' Erwartet: ' . var_export( $expected, true ) . '; erhalten: ' . var_export( $actual, true )
		);
	}
}

function mgd_ail_renderer_assert_contains( string $needle, string $haystack, string $message ): void {
	if ( false === strpos( $haystack, $needle ) ) {
		throw new RuntimeException( $message . ' Fehlend: ' . $needle );
	}
}

$image_html = '<img src="/bild.jpg" alt="Authentisches Produktfoto" class="wp-image-99" />';

$generated = MGD_AI_Image_Labels_Image_Renderer::render_badge(
	$image_html,
	array( 'status' => 'generated', 'position' => 'top-left', 'theme' => 'light' )
);
mgd_ail_renderer_assert_contains( 'AI GENERATED', $generated, 'KI-erstellte Bilder erhalten den sichtbaren Text.' );
mgd_ail_renderer_assert_contains( 'mgd-ail-position-top-left', $generated, 'Die gewählte obere linke Position wird als Klasse ausgegeben.' );
mgd_ail_renderer_assert_contains( 'mgd-ail-theme-light', $generated, 'Die gewählte helle Glasvariante wird als Klasse ausgegeben.' );
mgd_ail_renderer_assert_contains( 'alt="Authentisches Produktfoto"', $generated, 'Der bestehende Alt-Text bleibt unverändert.' );

$modified = MGD_AI_Image_Labels_Image_Renderer::render_badge(
	$image_html,
	array( 'status' => 'modified', 'position' => 'bottom-left', 'theme' => 'dark' )
);
mgd_ail_renderer_assert_contains( 'AI MODIFIED', $modified, 'KI-bearbeitete Bilder erhalten den sichtbaren Text.' );
mgd_ail_renderer_assert_contains( 'mgd-ail-position-bottom-left', $modified, 'Die gewählte untere linke Position wird als Klasse ausgegeben.' );
mgd_ail_renderer_assert_contains( 'mgd-ail-theme-dark', $modified, 'Die gewählte dunkle Glasvariante wird als Klasse ausgegeben.' );

$partially_generated = MGD_AI_Image_Labels_Image_Renderer::render_badge(
	$image_html,
	array( 'status' => 'partially-generated', 'position' => 'bottom-right', 'theme' => 'auto' )
);
mgd_ail_renderer_assert_contains( 'AI PARTIALLY GENERATED', $partially_generated, 'Teilweise KI-generierte Bilder erhalten den sichtbaren Text.' );
mgd_ail_renderer_assert_contains( 'mgd-ail-position-bottom-right', $partially_generated, 'Teilweise KI-generierte Bilder respektieren die gewählte Position.' );

$deepfake = MGD_AI_Image_Labels_Image_Renderer::render_badge(
	$image_html,
	array( 'status' => 'deepfake', 'position' => 'top-right', 'theme' => 'auto' )
);
mgd_ail_renderer_assert_contains( 'AI DEEPFAKE', $deepfake, 'Deepfakes erhalten den sichtbaren Text.' );
mgd_ail_renderer_assert_contains( 'mgd-ail-position-top-right', $deepfake, 'Die gewählte obere rechte Position wird als Klasse ausgegeben.' );
mgd_ail_renderer_assert_contains( 'mgd-ail-theme-auto', $deepfake, 'Die automatische Glasvariante wird als Klasse ausgegeben.' );
mgd_ail_renderer_assert_contains( 'künstlich erzeugt oder manipuliert', $deepfake, 'Deepfakes erhalten einen ausführlichen Screenreader-Hinweis.' );

$none = MGD_AI_Image_Labels_Image_Renderer::render_badge(
	$image_html,
	array( 'status' => 'none', 'position' => 'bottom-right', 'theme' => 'auto' )
);
mgd_ail_renderer_assert_same( $image_html, $none, 'Bilder ohne KI-Kennzeichnung bleiben exakt unverändert.' );

/*
 * Divi 5 reicht Bildmodule als eigene Blöcke durch. Die Bild-ID darf deshalb
 * ausschließlich aus den validierten Block-Attributen kommen – nie aus einer
 * CSS-Klasse oder aus der URL im gerenderten HTML.
 */
$divi_html = '<div class="et_pb_module et_pb_image"><span class="et_pb_image_wrap"><a href="/projekt"><img src="/bild.jpg" alt="Authentisches Produktfoto" class="wp-image-55" /></a></span></div>';
$divi_block = array(
	'blockName' => 'divi/image',
	'attrs'     => array(
		'module' => array(
			'image' => array(
				'innerContent' => array(
					'desktop' => array(
						'value' => array( 'id' => 55 ),
					),
				),
			),
		),
	),
);
$divi_generated = MGD_AI_Image_Labels_Image_Renderer::filter_divi_image_block( $divi_html, $divi_block );
mgd_ail_renderer_assert_contains( 'et_pb_image_wrap mgd-ail-image-wrapper', $divi_generated, 'Nur die Divi-Bildhülle erhält den relativen Badge-Kontext.' );
mgd_ail_renderer_assert_contains( 'AI GENERATED', $divi_generated, 'Ein Divi-Bildmodul erhält anhand seiner validierten ID das Badge.' );
mgd_ail_renderer_assert_contains( 'alt="Authentisches Produktfoto"', $divi_generated, 'Der Alt-Text des Divi-Bildes bleibt unverändert.' );
mgd_ail_renderer_assert_contains( '<a href="/projekt"><img', $divi_generated, 'Der Link des Divi-Bildes bleibt erhalten.' );
mgd_ail_renderer_assert_same( 1, substr_count( $divi_generated, 'class="mgd-ail-badge ' ), 'Das Divi-Bildmodul enthält genau ein Badge.' );

$divi_twice = MGD_AI_Image_Labels_Image_Renderer::filter_divi_image_block( $divi_generated, $divi_block );
mgd_ail_renderer_assert_same( $divi_generated, $divi_twice, 'Ein zweiter Divi-Filterdurchlauf erzeugt keinen zweiten Wrapper.' );

/*
 * Bestehende Seiten können noch das klassische Divi-Bildmodul ausgeben.
 * Es stellt die WordPress-Anhangs-ID im offiziellen Klassenformat bereit,
 * durchläuft aber keinen Divi-5-Blockfilter. Die kompatible Ausgabe muss
 * deshalb denselben Schutz und dieselbe einmalige Kennzeichnung erhalten.
 */
$legacy_divi_html = '<div class="et_pb_module et_pb_image"><span class="et_pb_image_wrap"><img src="/bild.jpg" alt="Authentisches Produktfoto" class="wp-image-55" /></span></div>';
$legacy_generated = MGD_AI_Image_Labels_Image_Renderer::filter_legacy_divi_image_content( $legacy_divi_html );
mgd_ail_renderer_assert_contains( 'et_pb_image_wrap mgd-ail-image-wrapper', $legacy_generated, 'Klassische Divi-Bildhüllen erhalten den relativen Badge-Kontext.' );
mgd_ail_renderer_assert_contains( 'AI GENERATED', $legacy_generated, 'Klassische Divi-Bildmodule erhalten anhand ihrer WordPress-Anhangsklasse das Badge.' );
mgd_ail_renderer_assert_contains( 'alt="Authentisches Produktfoto"', $legacy_generated, 'Der Alt-Text des klassischen Divi-Bildes bleibt unverändert.' );

$legacy_twice = MGD_AI_Image_Labels_Image_Renderer::filter_legacy_divi_image_content( $legacy_generated );
mgd_ail_renderer_assert_same( 1, substr_count( $legacy_twice, 'class="mgd-ail-badge ' ), 'Ein zweiter Durchlauf erzeugt für klassische Divi-Bilder kein zweites Badge.' );

$legacy_without_id = '<div class="et_pb_module et_pb_image"><span class="et_pb_image_wrap"><img src="/bild.jpg" alt="Authentisches Produktfoto" /></span></div>';
mgd_ail_renderer_assert_same( $legacy_without_id, MGD_AI_Image_Labels_Image_Renderer::filter_legacy_divi_image_content( $legacy_without_id ), 'Klassische Divi-Bilder ohne eindeutige WordPress-Anhangsklasse bleiben unverändert.' );

$GLOBALS['mgd_ail_test_meta'][55]['_mgd_ail_status'] = 'none';
$divi_none = MGD_AI_Image_Labels_Image_Renderer::filter_divi_image_block( $divi_html, $divi_block );
mgd_ail_renderer_assert_same( $divi_html, $divi_none, 'Divi-Bilder ohne KI-Kennzeichnung bleiben bytegleich.' );
$GLOBALS['mgd_ail_test_meta'][55]['_mgd_ail_status'] = 'generated';

$invalid_divi_block              = $divi_block;
$invalid_divi_block['attrs']['module']['image']['innerContent']['desktop']['value']['id'] = '55<script>';
$divi_invalid                    = MGD_AI_Image_Labels_Image_Renderer::filter_divi_image_block( $divi_html, $invalid_divi_block );
mgd_ail_renderer_assert_same( $divi_html, $divi_invalid, 'Ungültige Divi-Bild-IDs verändern das HTML nicht.' );

$missing_divi_block = array( 'blockName' => 'divi/image', 'attrs' => array() );
$divi_missing       = MGD_AI_Image_Labels_Image_Renderer::filter_divi_image_block( $divi_html, $missing_divi_block );
mgd_ail_renderer_assert_same( $divi_html, $divi_missing, 'Fehlende Divi-Bild-IDs verändern das HTML nicht.' );

/* Nur der offizielle Divi-5-Pfad unter attrs.module ist zulässig. */
$fallback_divi_block = array(
	'blockName' => 'divi/image',
	'attrs'     => array(
		'image' => array(
			'innerContent' => array(
				'desktop' => array(
					'value' => array( 'id' => 55 ),
				),
			),
		),
	),
);
$divi_fallback = MGD_AI_Image_Labels_Image_Renderer::filter_divi_image_block( $divi_html, $fallback_divi_block );
mgd_ail_renderer_assert_same( $divi_html, $divi_fallback, 'Ein abweichender Divi-Attributpfad darf kein Badge erzeugen.' );

$GLOBALS['mgd_ail_test_is_admin'] = true;
$divi_admin                         = MGD_AI_Image_Labels_Image_Renderer::filter_divi_image_block( $divi_html, $divi_block );
mgd_ail_renderer_assert_same( $divi_html, $divi_admin, 'Die WordPress-Backend-Vorschau bleibt bytegleich.' );
$GLOBALS['mgd_ail_test_is_admin'] = false;

$_GET['et_fb'] = '1';
$divi_builder = MGD_AI_Image_Labels_Image_Renderer::filter_divi_image_block( $divi_html, $divi_block );
mgd_ail_renderer_assert_same( $divi_html, $divi_builder, 'Die Divi-Visual-Builder-Vorschau bleibt bytegleich.' );
unset( $_GET['et_fb'] );

$frontend_css = file_get_contents( dirname( __DIR__ ) . '/assets/css/frontend.css' );
if ( false === $frontend_css ) {
	throw new RuntimeException( 'Die lokale Frontend-CSS-Datei konnte nicht gelesen werden.' );
}
mgd_ail_renderer_assert_contains( '.mgd-ail-badge .screen-reader-text', $frontend_css, 'Der Deepfake-Hinweis wird mit einer Plugin-eigenen Screenreader-Klasse abgesichert.' );
if ( 1 !== preg_match( '/\\.mgd-ail-badge \\.screen-reader-text\\s*\\{(?=[^}]*position:\\s*absolute)(?=[^}]*width:\\s*1px)(?=[^}]*clip:\\s*rect\\(0,\\s*0,\\s*0,\\s*0\\))[^}]*\\}/s', $frontend_css ) ) {
	throw new RuntimeException( 'Die lokale Screenreader-Regel enthält nicht alle nötigen, visuell verbergenden Eigenschaften.' );
}

$renderer_source = file_get_contents( dirname( __DIR__ ) . '/includes/class-image-renderer.php' );
if ( false === $renderer_source ) {
	throw new RuntimeException( 'Die Ausgabeklasse konnte nicht für den Divi-Laufzeit-Fallback gelesen werden.' );
}
mgd_ail_renderer_assert_contains( "add_action( 'wp_footer', array( self::class, 'render_legacy_divi_runtime' ), 100 );", $renderer_source, 'Spät gerenderte klassische Divi-Bilder erhalten einen lokalen Frontend-Fallback.' );
mgd_ail_renderer_assert_contains( "document.createElement('span')", $renderer_source, 'Der Laufzeit-Fallback erzeugt das Badge als DOM-Knoten statt mit unsicherem HTML.' );
mgd_ail_renderer_assert_contains( "text.textContent = config.label;", $renderer_source, 'Der Laufzeit-Fallback schreibt das Label als Text und nicht per HTML-Injektion.' );
mgd_ail_renderer_assert_contains( "document.querySelectorAll('img')", $renderer_source, 'Der Laufzeit-Fallback berücksichtigt auch Theme-Ausgaben ohne Divi-spezifische Anhangsklasse.' );

/*
 * Ein Beitragsbild erhält vom Theme häufig keine `wp-image-{ID}`-Klasse. Der
 * Fallback muss daher sowohl das Original als auch jede WordPress-Größenvariante
 * über ihren lokalen Upload-Pfad kennen. Nur so kann er das sichtbare Bild im
 * Einzelbeitrag oder Blog-Archiv sicher der geprüften Mediathek zuordnen.
 */
ob_start();
MGD_AI_Image_Labels_Image_Renderer::render_legacy_divi_runtime();
$runtime_output = (string) ob_get_clean();
mgd_ail_renderer_assert_contains( 'wp-content\\/uploads\\/2026\\/03\\/schreibmaschine.jpg', $runtime_output, 'Der Frontend-Fallback kennt den kanonischen Upload-Pfad des gekennzeichneten Bildes.' );
mgd_ail_renderer_assert_contains( 'wp-content\\/uploads\\/2026\\/03\\/schreibmaschine-980x551.jpg', $runtime_output, 'Der Frontend-Fallback kennt die von WordPress erzeugte Bildgröße für Beitragsbilder.' );
mgd_ail_renderer_assert_contains( "document.querySelectorAll('img')", $runtime_output, 'Der Frontend-Fallback prüft auch Theme- und Archivbilder ohne Divi-spezifische Anhangsklasse.' );

echo "PASS: Barrierefreie KI-Badges werden rein und sicher gerendert.\n";
