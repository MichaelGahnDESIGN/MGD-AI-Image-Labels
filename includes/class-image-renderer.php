<?php
/**
 * Ausgabe der KI-Bildkennzeichnung im Frontend.
 *
 * Die Klasse arbeitet ausschließlich mit dem bereits von WordPress erzeugten
 * Bild-HTML. Sie verändert weder Bilddateien, Alt-Texte noch gespeicherte
 * Divi-Inhalte. Dadurch bleibt die Kennzeichnung pro Ausgabe reversibel.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MGD_AI_Image_Labels_Image_Renderer {

	/** @var array<string, string> */
	private const LABELS = array(
		'generated'           => 'AI GENERATED',
		'partially-generated' => 'AI PARTIALLY GENERATED',
		'modified'            => 'AI MODIFIED',
		'deepfake'            => 'AI DEEPFAKE',
	);

	/**
	 * Registriert gezielt WordPress-Bildfilter und die lokale Stylesheet-Datei.
	 * Divi nutzt für seine Bildmodule in der Regel dieselbe WordPress-Ausgabe;
	 * eigene Divi-Layouts oder Inhalte werden deshalb nicht umgeschrieben.
	 */
	public static function register(): void {
		add_filter( 'wp_get_attachment_image', array( self::class, 'filter_attachment_image' ), 20, 5 );
		add_filter( 'post_thumbnail_html', array( self::class, 'filter_post_thumbnail' ), 20, 5 );
		add_filter( 'render_block_divi/image', array( self::class, 'filter_divi_image_block' ), 10, 2 );
		/*
		 * Bestehende Divi-Seiten können noch aus klassischen Divi-Modulen
		 * bestehen. Diese durchlaufen keinen Divi-5-Blockfilter und erzeugen
		 * ihr Bild direkt im Seiteninhalt. Der gezielte Inhaltsfilter ergänzt
		 * deshalb ausschließlich solche Bildmodule; Header, Footer und sonstige
		 * Bilder bleiben unangetastet.
		 */
		add_filter( 'the_content', array( self::class, 'filter_legacy_divi_image_content' ), 20 );
		/*
		 * Einige klassische Divi-Ausgaben entstehen erst nach `the_content`.
		 * Der kleine, lokale Fallback ergänzt ausschließlich bereits markierte
		 * Divi-Bilder direkt im Browser – ohne externe Dienste oder Datentransfer.
		 */
		add_action( 'wp_footer', array( self::class, 'render_legacy_divi_runtime' ), 100 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_styles' ) );
	}

	/**
	 * Gibt einen lokalen, barrierefreien Fallback für spät gerenderte klassische
	 * Divi-Bildmodule aus. Die Konfiguration enthält ausschließlich Anhangs-ID,
	 * Kennzeichnungsstatus, Position und Glasvariante – keine Personen-, Nutzungs-
	 * oder Besucherdaten. Sie wird nur ausgegeben, wenn mindestens ein Bild eine
	 * aktive Kennzeichnung besitzt.
	 */
	public static function render_legacy_divi_runtime(): void {
		if ( self::should_skip_divi_preview() || ! function_exists( 'get_posts' ) ) {
			return;
		}

		$attachment_ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Es werden nur explizit gekennzeichnete Medien gelesen.
					array(
						'key'     => MGD_AI_Image_Labels_Attachment_Meta::STATUS_KEY,
						'compare' => 'EXISTS',
					),
				),
			)
		);

		/*
		 * Klassische Themes geben Beitragsbilder und Archivkarten zum Teil ohne
		 * `wp-image-{ID}`-Klasse aus. Deshalb wird die Kennzeichnung nicht nur
		 * über die Anhangs-ID, sondern zusätzlich über alle von WordPress
		 * erzeugten lokalen Upload-Pfade verfügbar gemacht. Es handelt sich
		 * bewusst um einen exakten Pfadvergleich, nie um eine unscharfe Suche
		 * nach Dateinamen oder um die Kennzeichnung fremder Bildquellen.
		 *
		 * @var array<string, array<string, bool|string>> $labels
		 */
		$labels = array();
		foreach ( $attachment_ids as $attachment_id ) {
			$values = MGD_AI_Image_Labels_Attachment_Meta::get_values( (int) $attachment_id );
			$status = self::sanitize_status( $values['status'] ?? 'none' );

			if ( 'none' === $status ) {
				continue;
			}

			$configuration = array(
				'label'    => self::LABELS[ $status ],
				'position' => self::sanitize_value( $values['position'] ?? 'bottom-right', array( 'top-left', 'top-right', 'bottom-left', 'bottom-right' ), 'bottom-right' ),
				'theme'    => self::sanitize_value( $values['theme'] ?? 'auto', array( 'auto', 'light', 'dark' ), 'auto' ),
				'deepfake' => 'deepfake' === $status,
			);

			foreach ( self::get_attachment_upload_paths( (int) $attachment_id ) as $path ) {
				$labels[ $path ] = $configuration;
			}
		}

		if ( array() === $labels ) {
			return;
		}

		$json = function_exists( 'wp_json_encode' )
			? wp_json_encode( $labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT )
			: json_encode( $labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

		if ( ! is_string( $json ) || '' === $json ) {
			return;
		}

		?>
		<script id="mgd-ai-image-labels-legacy-runtime">
		(function () {
			'use strict';
			const labels = <?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sicheres JSON mit Hex-Escaping für einen JavaScript-Kontext. ?>;
			const getUploadPath = (image) => {
				try {
					return new URL(image.currentSrc || image.src, document.baseURI).pathname;
				} catch (error) {
					return '';
				}
			};
			const createSafeWrapper = (image) => {
				const diviWrap = image.closest('.et_pb_image_wrap');
				if (diviWrap) return diviWrap;

				/* Ein Archivbild liegt häufig allein in einem Link. In diesem Fall
				 * bleibt der Link der Layoutkontext; Klick- und Tastaturverhalten
				 * bleiben vollständig beim Theme. */
				const link = image.closest('a');
				if (link && 1 === link.querySelectorAll('img').length && '' === link.textContent.trim()) return link;

				/* Einzelbeiträge enthalten das Bild oft direkt neben Überschrift und
				 * Metadaten. Ein eigener, nur um dieses Bild gelegter Wrapper verhindert,
				 * dass sich die Badge-Ecke versehentlich auf den ganzen Beitrag bezieht. */
				const wrapper = document.createElement('span');
				wrapper.className = 'mgd-ail-image-wrapper';
				image.parentNode.insertBefore(wrapper, image);
				wrapper.appendChild(image);
				return wrapper;
			};
			for (const image of document.querySelectorAll('img')) {
				const config = labels[getUploadPath(image)];
				if (!config) continue;
				const wrap = createSafeWrapper(image);
				if (!wrap || wrap.querySelector('.mgd-ail-badge')) continue;
				wrap.classList.add('mgd-ail-image-wrapper');
				const badge = document.createElement('span');
				badge.className = 'mgd-ail-badge mgd-ail-position-' + config.position + ' mgd-ail-theme-' + config.theme;
				badge.setAttribute('role', 'note');
				const text = document.createElement('span');
				text.className = 'mgd-ail-badge__text';
				text.textContent = config.label;
				badge.appendChild(text);
				if (config.deepfake) {
					const detail = document.createElement('span');
					detail.className = 'screen-reader-text';
					detail.textContent = 'Dieses Bild wurde künstlich erzeugt oder manipuliert und kann einen authentischen Eindruck erwecken.';
					badge.appendChild(detail);
				}
				wrap.appendChild(badge);
			}
		}());
		</script>
		<?php
	}

	/**
	 * Ermittelt die öffentlich ausgegebenen Upload-Pfade eines Bildanhangs.
	 *
	 * WordPress kann ein Originalbild in mehreren selbst erzeugten Größen
	 * ausgeben. Ein Theme verwendet beispielsweise im Archiv `-400x284`, im
	 * Einzelbeitrag aber `-980x551`. Beide Pfade werden ausschließlich aus den
	 * WordPress-Metadaten des identischen Anhangs abgeleitet.
	 *
	 * @param int $attachment_id Positive WordPress-Anhangs-ID.
	 * @return array<int, string> Eindeutige absolute URL-Pfade ohne Query-String.
	 */
	private static function get_attachment_upload_paths( int $attachment_id ): array {
		if (
			$attachment_id <= 0 ||
			! function_exists( 'wp_get_attachment_url' ) ||
			! function_exists( 'wp_get_attachment_metadata' ) ||
			! function_exists( 'wp_upload_dir' )
		) {
			return array();
		}

		$paths        = array();
		$original_url = wp_get_attachment_url( $attachment_id );
		$original     = self::get_url_path( is_string( $original_url ) ? $original_url : '' );

		if ( '' !== $original ) {
			$paths[] = $original;
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );
		$upload   = wp_upload_dir();
		$file     = is_array( $metadata ) && is_string( $metadata['file'] ?? null ) ? ltrim( $metadata['file'], '/' ) : '';
		$base_url = is_array( $upload ) && is_string( $upload['baseurl'] ?? null ) ? rtrim( $upload['baseurl'], '/' ) : '';

		if ( '' === $file || '' === $base_url || ! is_array( $metadata['sizes'] ?? null ) ) {
			return array_values( array_unique( $paths ) );
		}

		$directory = dirname( $file );
		$directory = '.' === $directory ? '' : trim( $directory, '/' );

		foreach ( $metadata['sizes'] as $size ) {
			$size_file = is_array( $size ) && is_string( $size['file'] ?? null ) ? ltrim( $size['file'], '/' ) : '';

			if ( '' === $size_file ) {
				continue;
			}

			$path = self::get_url_path( $base_url . '/' . ( '' === $directory ? '' : $directory . '/' ) . $size_file );

			if ( '' !== $path ) {
				$paths[] = $path;
			}
		}

		return array_values( array_unique( $paths ) );
	}

	/**
	 * Normalisiert eine Bild-URL für einen datensparsamen, exakten Browservergleich.
	 *
	 * Die Domain, Query-Parameter und Fragmente werden nicht übertragen oder
	 * verglichen. Damit funktionieren auch HTTPS, ein lokales CDN oder ein
	 * Cache-Buster, ohne aus einer URL jemals eine Anhangs-ID zu erraten.
	 */
	private static function get_url_path( string $url ): string {
		$path = parse_url( $url, PHP_URL_PATH );

		return is_string( $path ) && '' !== $path && '/' === $path[0] ? $path : '';
	}

	/**
	 * Ergänzt Badges in klassischen Divi-Bildmodulen.
	 *
	 * Die WordPress-Klasse `wp-image-{ID}` ist der vom Kern ausgegebene,
	 * semantische Bezug zwischen Bild-HTML und Mediathek. Sie wird hier nur
	 * innerhalb von `.et_pb_image` gelesen, vollständig als Ganzzahl validiert
	 * und nie aus URLs oder freiem Text abgeleitet. Damit bleiben beliebige
	 * redaktionelle Bilder außerhalb eines Divi-Bildmoduls unverändert.
	 *
	 * @param string $content Bereits gerenderter Seiteninhalt.
	 * @return string Unveränderter Inhalt oder Inhalt mit gezielten Badges.
	 */
	public static function filter_legacy_divi_image_content( string $content ): string {
		if ( '' === $content || self::should_skip_divi_preview() || ! class_exists( 'DOMDocument' ) ) {
			return $content;
		}

		$previous_errors = libxml_use_internal_errors( true );
		$document        = new DOMDocument( '1.0', 'UTF-8' );
		$container_id    = 'mgd-ail-legacy-divi-fragment';
		$fragment        = '<div id="' . $container_id . '">' . $content . '</div>';
		$loaded          = $document->loadHTML( '<?xml encoding="utf-8" ?>' . $fragment, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );

		if ( false === $loaded ) {
			return $content;
		}

		$xpath   = new DOMXPath( $document );
		$modules = $xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " et_pb_image ")]' );

		if ( false === $modules || 0 === $modules->length ) {
			return $content;
		}

		$changed = false;
		foreach ( $modules as $module ) {
			if ( ! $module instanceof DOMElement ) {
				continue;
			}

			$changed = self::append_badge_to_legacy_divi_module( $document, $xpath, $module ) || $changed;
		}

		/* Ohne Badge bleibt das Quell-HTML bytegleich; DOMDocument normalisiert
		 * sonst beispielsweise selbstschließende img-Tags. */
		if ( ! $changed ) {
			return $content;
		}

		$container = $document->getElementById( $container_id );

		if ( ! $container instanceof DOMElement ) {
			return $content;
		}

		$output = '';
		foreach ( $container->childNodes as $child ) {
			$output .= $document->saveHTML( $child );
		}

		return '' === $output ? $content : $output;
	}

	/**
	 * Ergänzt ein Divi-5-Bildmodul erst nach seiner Block-Ausgabe.
	 *
	 * Divi 5 rendert seine Bildmodule als eigene Gutenberg-Blöcke und verwendet
	 * dabei nicht verlässlich die WordPress-Bildfilter. Die Attachment-ID wird
	 * deshalb ausschließlich aus der von Divi übergebenen Block-Konfiguration
	 * gelesen. Sie wird niemals aus Klassen, URLs oder anderem Ausgabe-HTML
	 * geraten. Im Visual Builder bleibt das Ergebnis unverändert, damit dort
	 * keine gespeicherte Vorschau oder Bedienoberfläche beeinflusst wird.
	 *
	 * @param string               $block_content Vollständig gerendertes aktuelle Divi-Bildmodul.
	 * @param array<string, mixed> $block         Geparster Block inklusive Attribute.
	 * @return string Unverändertes Block-HTML oder ein gezielt ergänztes Badge.
	 */
	public static function filter_divi_image_block( string $block_content, array $block ): string {
		if ( '' === $block_content || self::should_skip_divi_preview() ) {
			return $block_content;
		}

		$attachment_id = self::get_divi_attachment_id( $block );

		if ( $attachment_id <= 0 ) {
			return $block_content;
		}

		$values = MGD_AI_Image_Labels_Attachment_Meta::get_values( $attachment_id );

		if ( 'none' === self::sanitize_status( $values['status'] ?? 'none' ) ) {
			return $block_content;
		}

		return self::append_badge_to_divi_image_wrap( $block_content, $values );
	}

	/**
	 * Ergänzt ein direkt über WordPress ausgegebenes Anhangsbild.
	 *
	 * @param string $html Bereits erzeugtes Bild-HTML.
	 * @param int    $attachment_id ID des Bild-Anhangs.
	 * @return string Unverändertes Bild oder Bild mit reinem Ausgabewrapper.
	 */
	public static function filter_attachment_image( string $html, int $attachment_id ): string {
		return self::render_for_attachment( $html, $attachment_id );
	}

	/**
	 * Ergänzt Beitragsbilder, soweit sie nicht bereits vom Bildfilter umschlossen
	 * wurden. Die Doppelprüfng verhindert verschachtelte Badges.
	 *
	 * @param string $html Bereits erzeugtes Beitragsbild-HTML.
	 * @param int    $post_id Beitrags-ID (wird von WordPress übergeben).
	 * @param int    $thumbnail_id Anhangs-ID des Beitragsbilds.
	 * @return string Unverändertes Bild oder Bild mit reinem Ausgabewrapper.
	 */
	public static function filter_post_thumbnail( string $html, int $post_id, int $thumbnail_id ): string {
		return self::render_for_attachment( $html, $thumbnail_id );
	}

	/**
	 * Bindet ausschließlich die lokale CSS-Datei ein. Es gibt keine externen
	 * Schriften, Skripte, Analyseaufrufe oder Cookies.
	 */
	public static function enqueue_styles(): void {
		wp_enqueue_style(
			'mgd-ai-image-labels',
			MGD_AI_IMAGE_LABELS_URL . 'assets/css/frontend.css',
			array(),
			MGD_AI_IMAGE_LABELS_VERSION
		);
		wp_add_inline_style( 'mgd-ai-image-labels', MGD_AI_Image_Labels_Plugin_Options::get_css_variables() );
	}

	/**
	 * Liest die Auswahl nur für die aktuelle Ausgabe. Es erfolgen keine
	 * Datenbank-Schreibvorgänge oder pauschalen Änderungen vorhandener Medien.
	 */
	private static function render_for_attachment( string $html, int $attachment_id ): string {
		if ( '' === $html || $attachment_id <= 0 || false !== strpos( $html, 'mgd-ail-image-wrapper' ) ) {
			return $html;
		}

		return self::render_badge( $html, MGD_AI_Image_Labels_Attachment_Meta::get_values( $attachment_id ) );
	}

	/**
	 * Erzeugt ausschließlich das Badge eines gekennzeichneten Anhangs.
	 *
	 * Diese öffentliche Schnittstelle ist für Ausgaben gedacht, bei denen das
	 * eigentliche Bild bereits als CSS-Hintergrund eines bestehenden Containers
	 * eingebunden ist. Sie verändert und umschließt deshalb weder ein Bild noch
	 * anderes Layout-HTML. Der Kennzeichnungsstatus stammt immer aus den sicher
	 * gelesenen Anhangsmetadaten und kann nicht überschrieben werden.
	 *
	 * @param int                  $attachment_id Positive WordPress-Anhangs-ID.
	 * @param array<string, mixed> $overrides Optionale Ausgabevarianten für Position und Theme.
	 */
	public static function render_label_only( int $attachment_id, array $overrides ): string {
		if ( $attachment_id <= 0 ) {
			return '';
		}

		$values = MGD_AI_Image_Labels_Attachment_Meta::get_values( $attachment_id );

		/* Nur reine Darstellungswerte dürfen lokal abweichen. Der Status bleibt
		 * unveränderlich an die redaktionell geprüfte Bild-ID gebunden. */
		if ( array_key_exists( 'position', $overrides ) ) {
			$values['position'] = $overrides['position'];
		}

		if ( array_key_exists( 'theme', $overrides ) ) {
			$values['theme'] = $overrides['theme'];
		}

		return self::render_badge_html( $values );
	}

	/**
	 * Erzeugt die isolierte, testbare Ausgabe eines Badges.
	 *
	 * Das übergebene Bild-HTML bleibt wortgleich enthalten. Der Alt-Text wird
	 * damit weder ersetzt noch ergänzt; die Kennzeichnung hat eigene Semantik.
	 *
	 * @param array<string, mixed> $values Ungeprüfte, bereits gelesene Metadaten.
	 */
	public static function render_badge( string $image_html, array $values ): string {
		if ( '' === $image_html ) {
			return $image_html;
		}

		$badge_html = self::render_badge_html( $values );

		if ( '' === $badge_html ) {
			return $image_html;
		}

		return '<span class="mgd-ail-image-wrapper">' . $image_html . $badge_html . '</span>';
	}

	/**
	 * Baut die gemeinsame, bereits vollständig escapte Badge-Ausgabe.
	 *
	 * Bildfilter und Hintergrund-Shortcode verwenden bewusst dieselbe Methode.
	 * Sichtbarer Labeltext und Deepfake-Hinweis existieren dadurch nur an einem
	 * Ort und können bei späteren Textänderungen nicht auseinanderlaufen.
	 *
	 * @param array<string, mixed> $values Ungeprüfte Metadaten oder Ausgabevarianten.
	 */
	private static function render_badge_html( array $values ): string {
		$status = self::sanitize_status( $values['status'] ?? 'none' );

		if ( 'none' === $status ) {
			return '';
		}

		$position = self::sanitize_value(
			$values['position'] ?? 'bottom-right',
			array( 'top-left', 'top-right', 'bottom-left', 'bottom-right' ),
			'bottom-right'
		);
		$theme    = self::sanitize_value( $values['theme'] ?? 'auto', array( 'auto', 'light', 'dark' ), 'auto' );
		$label    = self::LABELS[ $status ];
		$detail   = 'deepfake' === $status
			? '<span class="screen-reader-text">Dieses Bild wurde künstlich erzeugt oder manipuliert und kann einen authentischen Eindruck erwecken.</span>'
			: '';

		return sprintf(
			'<span class="mgd-ail-badge mgd-ail-position-%1$s mgd-ail-theme-%2$s" role="note"><span class="mgd-ail-badge__text">%3$s</span>%4$s</span>',
			self::escape_class( $position ),
			self::escape_class( $theme ),
			self::escape_text( $label ),
			$detail
		);
	}

	/**
	 * Liest nur die in Divi 5 dokumentiert gespeicherte lokale Bild-ID.
	 *
	 * Divi legt Modulwerte in aktuellen Blöcken unter "module" ab. Ausschließlich
	 * dieser vollständige Pfad ist zulässig; es gibt absichtlich keinen Fallback
	 * für abweichende Attributstrukturen, HTML oder sonstige Ersatzquellen.
	 *
	 * @param array<string, mixed> $block Geparster WordPress-Block.
	 */
	private static function get_divi_attachment_id( array $block ): int {
		$attrs = $block['attrs'] ?? null;

		if ( ! is_array( $attrs ) ) {
			return 0;
		}

		$image = $attrs['module']['image'] ?? null;

		if ( ! is_array( $image ) ) {
			return 0;
		}

		$id = $image['innerContent']['desktop']['value']['id'] ?? null;

		if ( is_int( $id ) && $id > 0 ) {
			return $id;
		}

		if ( is_string( $id ) && ctype_digit( $id ) && (int) $id > 0 ) {
			return (int) $id;
		}

		return 0;
	}

	/**
	 * Ergänzt ausschließlich die Divi-eigene Bildhülle des aktuellen Blocks.
	 * DOMDocument validiert die Fragmentstruktur; eine Regex-Manipulation von
	 * HTML wird bewusst nicht eingesetzt. Kann der sichere DOM-Weg nicht
	 * angewendet werden, bleibt der Block vollständig unverändert.
	 *
	 * @param array<string, mixed> $values Sicher gelesene Anhangsmetadaten.
	 */
	private static function append_badge_to_divi_image_wrap( string $block_content, array $values ): string {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return $block_content;
		}

		$previous_errors = libxml_use_internal_errors( true );
		$document        = new DOMDocument( '1.0', 'UTF-8' );
		$container_id    = 'mgd-ail-divi-fragment';
		$fragment        = '<div id="' . $container_id . '">' . $block_content . '</div>';
		$loaded          = $document->loadHTML( '<?xml encoding="utf-8" ?>' . $fragment, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );

		if ( false === $loaded ) {
			return $block_content;
		}

		$xpath = new DOMXPath( $document );
		$wraps = $xpath->query( '//*[@class and contains(concat(" ", normalize-space(@class), " "), " et_pb_image_wrap ")]' );

		if ( false === $wraps || 1 !== $wraps->length || ! $wraps->item( 0 ) instanceof DOMElement ) {
			return $block_content;
		}

		$wrap = $wraps->item( 0 );

		if ( self::divi_wrap_already_has_badge( $xpath, $wrap ) ) {
			return $block_content;
		}

		$wrap->setAttribute( 'class', trim( $wrap->getAttribute( 'class' ) . ' mgd-ail-image-wrapper' ) );
		$wrap->appendChild( self::create_badge_element( $document, $values ) );

		$container = $document->getElementById( $container_id );

		if ( ! $container instanceof DOMElement ) {
			return $block_content;
		}

		$output = '';
		foreach ( $container->childNodes as $child ) {
			$output .= $document->saveHTML( $child );
		}

		return '' === $output ? $block_content : $output;
	}

	/**
	 * Ergänzt ein Badge innerhalb genau eines klassischen Divi-Bildmoduls.
	 *
	 * Der DOM-Weg akzeptiert nur eine eindeutige WordPress-Anhangsklasse und
	 * genau eine Divi-Bildhülle. Mehrdeutige oder unvollständige Module werden
	 * absichtlich übersprungen, damit die Ausgabe nie geraten oder beschädigt
	 * wird.
	 */
	private static function append_badge_to_legacy_divi_module( DOMDocument $document, DOMXPath $xpath, DOMElement $module ): bool {
		$images = $xpath->query( './/img', $module );

		if ( false === $images || 1 !== $images->length || ! $images->item( 0 ) instanceof DOMElement ) {
			return false;
		}

		$attachment_id = self::get_attachment_id_from_wordpress_image_class( $images->item( 0 )->getAttribute( 'class' ) );

		if ( $attachment_id <= 0 ) {
			return false;
		}

		$values = MGD_AI_Image_Labels_Attachment_Meta::get_values( $attachment_id );

		if ( 'none' === self::sanitize_status( $values['status'] ?? 'none' ) ) {
			return false;
		}

		$wraps = $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " et_pb_image_wrap ")]', $module );

		if ( false === $wraps || 1 !== $wraps->length || ! $wraps->item( 0 ) instanceof DOMElement ) {
			return false;
		}

		$wrap = $wraps->item( 0 );

		if ( self::divi_wrap_already_has_badge( $xpath, $wrap ) ) {
			return false;
		}

		$wrap->setAttribute( 'class', trim( $wrap->getAttribute( 'class' ) . ' mgd-ail-image-wrapper' ) );
		$wrap->appendChild( self::create_badge_element( $document, $values ) );

		return true;
	}

	/**
	 * Liest eine WordPress-Anhangs-ID nur aus einem exakt passenden Klassentoken.
	 *
	 * @param string $classes Vollständiger Klassenwert eines Bildes.
	 */
	private static function get_attachment_id_from_wordpress_image_class( string $classes ): int {
		if ( 1 !== preg_match( '/(?:^|\\s)wp-image-([1-9][0-9]*)(?:\\s|$)/', $classes, $matches ) ) {
			return 0;
		}

		return isset( $matches[1] ) ? (int) $matches[1] : 0;
	}

	/**
	 * Prüft mit XPath nur innerhalb der aktuellen Divi-Bildhülle, ob der Filter
	 * bereits gelaufen ist. Das verhindert verschachtelte Wrapper bei mehrfacher
	 * Block-Ausgabe durch Caches oder weitere WordPress-Filter.
	 */
	private static function divi_wrap_already_has_badge( DOMXPath $xpath, DOMElement $wrap ): bool {
		$badges = $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " mgd-ail-badge ")]', $wrap );

		return false !== $badges && $badges->length > 0;
	}

	/**
	 * Erstellt das Badge als DOM-Knoten. Dadurch werden Label und Klassen nicht
	 * per String-Verkettung in das Divi-HTML eingefügt.
	 *
	 * @param array<string, mixed> $values Sicher gelesene Anhangsmetadaten.
	 */
	private static function create_badge_element( DOMDocument $document, array $values ): DOMElement {
		$status   = self::sanitize_status( $values['status'] ?? 'none' );
		$position = self::sanitize_value( $values['position'] ?? 'bottom-right', array( 'top-left', 'top-right', 'bottom-left', 'bottom-right' ), 'bottom-right' );
		$theme    = self::sanitize_value( $values['theme'] ?? 'auto', array( 'auto', 'light', 'dark' ), 'auto' );
		$badge    = $document->createElement( 'span' );
		$badge->setAttribute( 'class', 'mgd-ail-badge mgd-ail-position-' . $position . ' mgd-ail-theme-' . $theme );
		$badge->setAttribute( 'role', 'note' );

		$text = $document->createElement( 'span' );
		$text->setAttribute( 'class', 'mgd-ail-badge__text' );
		$text->appendChild( $document->createTextNode( self::LABELS[ $status ] ) );
		$badge->appendChild( $text );

		if ( 'deepfake' === $status ) {
			$detail = $document->createElement( 'span' );
			$detail->setAttribute( 'class', 'screen-reader-text' );
			$detail->appendChild( $document->createTextNode( 'Dieses Bild wurde künstlich erzeugt oder manipuliert und kann einen authentischen Eindruck erwecken.' ) );
			$badge->appendChild( $detail );
		}

		return $badge;
	}

	/**
	 * Der öffentliche Seitenaufruf darf ausgegeben werden. Backend-Ansichten und
	 * die Divi-Visual-Builder-Vorschau werden dagegen bewusst nicht verändert.
	 */
	private static function should_skip_divi_preview(): bool {
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return true;
		}

		if ( defined( 'ET_BUILDER_VISUAL_BUILDER' ) && ET_BUILDER_VISUAL_BUILDER ) {
			return true;
		}

		return isset( $_GET['et_fb'] ) && '1' === (string) $_GET['et_fb']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reine Vorschau-Erkennung ohne Zustandsänderung.
	}

	/** @param mixed $value */
	private static function sanitize_status( $value ): string {
		return self::sanitize_value( $value, array_keys( self::LABELS ), 'none' );
	}

	/** @param mixed $value @param array<int, string> $allowed */
	private static function sanitize_value( $value, array $allowed, string $fallback ): string {
		if ( ! is_string( $value ) ) {
			return $fallback;
		}

		$value = strtolower( trim( $value ) );

		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	private static function escape_class( string $value ): string {
		return function_exists( 'esc_attr' ) ? esc_attr( $value ) : htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}

	private static function escape_text( string $value ): string {
		return function_exists( 'esc_html' ) ? esc_html( $value ) : htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}
}
