<?php
/**
 * Redaktionelle AI-Philosophie und ihre vorsichtige Veröffentlichung.
 *
 * Die Klasse hält den erklärenden Text lokal in WordPress. Weder beim
 * Speichern noch beim Ausgeben werden Inhalte an Dritte übertragen. Die
 * Seitenerstellung ist bewusst idempotent und ergänzt einen Footer nur bei
 * einer zweifelsfrei eindeutigen WordPress-Menüposition.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Kapselt Text, Shortcode und die sichere Seitenerstellung.
 */
final class MGD_AI_Image_Labels_AI_Philosophy {

	/** Optionsname für den redaktionell gepflegten Text. */
	public const OPTION_NAME = 'mgd_ail_ai_philosophy';

	/** Meta-Schlüssel, der ausschließlich von dieser Funktion erzeugte Seiten erkennt. */
	private const PAGE_META_KEY = '_mgd_ail_ai_philosophy_page';

	/** Spezifische Nonce-Aktion für die Seitenerstellung. */
	private const CREATE_PAGE_ACTION = 'mgd_ail_create_ai_philosophy_page';

	/** Formularfeld der Nonce. */
	private const CREATE_PAGE_NONCE = 'mgd_ail_create_ai_philosophy_page_nonce';

	/** Registriert alle ausschließlich lokalen WordPress-Hooks. */
	public static function register(): void {
		add_shortcode( 'mgd_ai_philosophy', array( self::class, 'render_shortcode' ) );
		add_action( 'admin_post_mgd_ail_save_ai_philosophy', array( self::class, 'handle_save_content' ) );
		add_action( 'admin_post_mgd_ail_create_ai_philosophy_page', array( self::class, 'handle_create_page' ) );
	}

	/**
	 * Liefert den verständlichen, datensparsamen Ausgangstext für neue Installationen.
	 */
	public static function get_default_content(): string {
		return '<p>Ich setze künstliche Intelligenz verantwortungsvoll ein: als Werkzeug für Ideen, Entwürfe und effizientere Arbeitsabläufe – nicht als Ersatz für Verantwortung, Prüfung und persönliche Gestaltung.</p>'
			. '<p>Wo KI ein Bild wesentlich erstellt oder verändert hat, kennzeichne ich dies nachvollziehbar. Inhalte werden vor der Veröffentlichung sorgfältig geprüft; Kontext, Urheberrechte und die Wirkung auf Menschen bleiben dabei wichtig.</p>';
	}

	/**
	 * Liest den bereits sicheren Text und normalisiert auch Altdaten beim Ausgeben.
	 */
	public static function get_content(): string {
		$stored = get_option( self::OPTION_NAME, self::get_default_content() );

		return self::sanitize_content( is_string( $stored ) ? $stored : '' );
	}

	/**
	 * Entfernt aktive und nicht benötigte HTML-Elemente vor der Speicherung.
	 *
	 * Links bleiben nur mit href, title und rel erhalten. So können
	 * Redaktionsteams Quellen nennen, ohne Skripte, Inline-Stile, Formulare oder
	 * eingebettete Fremdinhalte zu erlauben.
	 */
	public static function sanitize_content( string $content ): string {
		$content = preg_replace( '#<(script|style)[^>]*>.*?</\\1>#is', '', $content );
		$content = is_string( $content ) ? $content : '';

		return wp_kses(
			$content,
			array(
				'p'      => array(),
				'h2'     => array(),
				'h3'     => array(),
				'h4'     => array(),
				'ul'     => array(),
				'ol'     => array(),
				'li'     => array(),
				'strong' => array(),
				'em'     => array(),
				'br'     => array(),
				'a'      => array(
					'href'   => true,
					'title'  => true,
					'rel'    => true,
				),
			)
		);
	}

	/**
	 * Gibt den Text als sicheren Shortcode-Inhalt aus.
	 *
	 * wpautop ergänzt für Klartextabsätze die übliche WordPress-Formatierung.
	 * Bereits erlaubte Absatz- und Listenstrukturen bleiben dabei erhalten.
	 */
	public static function render_shortcode(): string {
		$content = self::get_content();

		return '' === $content ? '' : wpautop( $content );
	}

	/**
	 * Verarbeitet nur den Redaktions-POST aus der zentralen Medienverwaltung.
	 */
	public static function handle_save_content(): void {
		self::assert_can_manage();

		$nonce = isset( $_POST['mgd_ail_ai_philosophy_nonce'] ) && is_string( $_POST['mgd_ail_ai_philosophy_nonce'] ) ? $_POST['mgd_ail_ai_philosophy_nonce'] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Prüfung erfolgt direkt danach.
		if ( ! wp_verify_nonce( $nonce, 'mgd_ail_save_ai_philosophy' ) ) {
			wp_die( 'Die Sicherheitsprüfung für die AI-Philosophie ist fehlgeschlagen.' );
		}

		$content = isset( $_POST['mgd_ail_ai_philosophy'] ) && is_string( $_POST['mgd_ail_ai_philosophy'] ) ? wp_unslash( $_POST['mgd_ail_ai_philosophy'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Prüfung erfolgte oben; Werte werden streng gefiltert.
		update_option( self::OPTION_NAME, self::sanitize_content( $content ) );

		self::redirect_with_notice( 'Der Text zur AI-Philosophie wurde gespeichert.' );
	}

	/**
	 * Verarbeitet den expliziten Button zum Anlegen der Philosophie-Seite.
	 */
	public static function handle_create_page(): void {
		self::assert_can_create_page();

		$nonce = isset( $_POST[ self::CREATE_PAGE_NONCE ] ) && is_string( $_POST[ self::CREATE_PAGE_NONCE ] ) ? $_POST[ self::CREATE_PAGE_NONCE ] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Prüfung erfolgt direkt danach.
		if ( ! wp_verify_nonce( $nonce, self::CREATE_PAGE_ACTION ) ) {
			wp_die( 'Die Sicherheitsprüfung für die AI-Philosophie-Seite ist fehlgeschlagen.' );
		}

		$result = self::create_page();
		self::redirect_with_notice( $result['notice'] );
	}

	/**
	 * Erstellt genau eine, über eigenes Meta wiedererkennbare Seite.
	 *
	 * @return array{page_id:int, notice:string}
	 */
	public static function create_page(): array {
		self::assert_can_create_page();

		$page_id = self::find_existing_page_id();
		$created = false;

		if ( 0 === $page_id ) {
			$page_id = wp_insert_post(
				array(
					'post_title'   => 'AI-Philosophie',
					'post_content' => '[mgd_ai_philosophy]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				),
				true
			);

			if ( is_wp_error( $page_id ) || ! is_int( $page_id ) || $page_id <= 0 ) {
				return array(
					'page_id' => 0,
					'notice'  => 'Die AI-Philosophie-Seite konnte nicht erstellt werden. Bitte prüfe die WordPress-Rechte.',
				);
			}

			update_post_meta( $page_id, self::PAGE_META_KEY, '1' );
			$created = true;
		}

		$footer_result = self::add_to_unique_footer_menu( $page_id );

		if ( 'added' === $footer_result ) {
			$notice = $created ? 'Die AI-Philosophie-Seite wurde erstellt und dem eindeutigen Footer-Menü hinzugefügt.' : 'Die vorhandene AI-Philosophie-Seite wurde dem eindeutigen Footer-Menü hinzugefügt.';
		} elseif ( 'already-present' === $footer_result ) {
			$notice = $created ? 'Die AI-Philosophie-Seite wurde erstellt. Sie ist bereits im eindeutigen Footer-Menü verlinkt.' : 'Die AI-Philosophie-Seite und ihr Footer-Link sind bereits vorhanden.';
		} else {
			$notice = $created ? 'Die AI-Philosophie-Seite wurde erstellt. Bitte füge sie manuell zu deinem gewünschten Footer-Menü hinzu.' : 'Die AI-Philosophie-Seite ist bereits vorhanden. Bitte prüfe oder ergänze ihren Footer-Link manuell.';
		}

		return array(
			'page_id' => $page_id,
			'notice'  => $notice,
		);
	}

	/**
	 * Sucht nur eine von diesem Plugin markierte Seite, nie allein nach Titel.
	 */
	private static function find_existing_page_id(): int {
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::PAGE_META_KEY,
				'meta_value'     => '1',
			)
		);

		if ( ! is_array( $pages ) || empty( $pages ) ) {
			return 0;
		}

		$first = $pages[0];

		if ( is_int( $first ) ) {
			return $first > 0 ? $first : 0;
		}

		return is_object( $first ) && isset( $first->ID ) ? max( 0, (int) $first->ID ) : 0;
	}

	/**
	 * Ergänzt exakt ein klar erkennbares Footer-Menü und niemals mehrere Menüs.
	 *
	 * @return 'added'|'already-present'|'manual'
	 */
	private static function add_to_unique_footer_menu( int $page_id ): string {
		$locations = get_nav_menu_locations();
		$candidates = array();

		foreach ( $locations as $location_key => $menu_id ) {
			if ( is_string( $location_key ) && str_contains( strtolower( $location_key ), 'footer' ) && is_numeric( $menu_id ) && (int) $menu_id > 0 ) {
				$candidates[] = (int) $menu_id;
			}
		}

		if ( 1 !== count( $candidates ) ) {
			return 'manual';
		}

		$menu_id    = $candidates[0];
		$menu_items = wp_get_nav_menu_items( $menu_id );

		$page_url = get_permalink( $page_id );
		if ( is_array( $menu_items ) ) {
			foreach ( $menu_items as $menu_item ) {
				if ( ! is_object( $menu_item ) ) {
					continue;
				}

				$item_page_id = isset( $menu_item->object_id ) ? (int) $menu_item->object_id : 0;
				$item_url     = isset( $menu_item->url ) && is_string( $menu_item->url ) ? $menu_item->url : '';

				if ( $item_page_id === $page_id || ( '' !== $item_url && untrailingslashit( $item_url ) === untrailingslashit( $page_url ) ) ) {
					return 'already-present';
				}
			}
		}

		$result = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => 'AI-Philosophie',
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $page_id,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);

		return is_wp_error( $result ) || 0 === (int) $result ? 'manual' : 'added';
	}

	/** Erzwingt die Verwaltungsberechtigung für redaktionelle Texte. */
	private static function assert_can_manage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Du bist nicht berechtigt, die AI-Philosophie zu verwalten.' );
		}
	}

	/** Erzwingt zusätzlich die für die Veröffentlichung nötige Seitenberechtigung. */
	private static function assert_can_create_page(): void {
		self::assert_can_manage();

		if ( ! current_user_can( 'publish_pages' ) ) {
			wp_die( 'Du bist nicht berechtigt, eine AI-Philosophie-Seite zu veröffentlichen.' );
		}
	}

	/**
	 * Leitet nach einem POST ohne sensible Daten im Query-String zurück.
	 */
	private static function redirect_with_notice( string $notice ): void {
		$url = add_query_arg(
			array(
				'page'             => 'mgd-ai-image-labels',
				'tab'              => 'ai-philosophy',
				'mgd_ail_notice'   => rawurlencode( $notice ),
			),
			admin_url( 'upload.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}
}
