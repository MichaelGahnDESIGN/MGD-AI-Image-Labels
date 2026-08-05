<?php
/**
 * Zentrale, klar getrennte Verwaltungsseite des Plugins.
 *
 * Der Controller enthält nur Routing, Rechteprüfung und die Übergabe sicher
 * gelesener Werte. Die einzelnen Bereiche liegen bewusst in eigenen Views,
 * damit Redaktion und Entwicklung Änderungen schnell einordnen können.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert die Verwaltung unter Medien und begrenzt alle Reiter auf eine
 * feste, lokale Whitelist.
 */
final class MGD_AI_Image_Labels_Admin_Page {

	/** Eindeutiger, konfliktarmer Slug der Medien-Unterseite. */
	private const PAGE_SLUG = 'mgd-ai-image-labels';

	/** @var array<string, string> Feste Reiter ohne frei ladbare Dateinamen. */
	private const TABS = array(
		'settings'      => 'Einstellungen',
		'css-classes'   => 'CSS-Klassen',
		'ai-philosophy' => 'AI-Philosophie',
		'imprint'       => 'Impressum',
	);

	/** Registriert den Menüpunkt erst im regulären WordPress-Admin-Menüaufbau. */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'register_media_page' ) );
	}

	/** Fügt genau eine Verwaltungsseite unter „Medien“ hinzu. */
	public static function register_media_page(): void {
		add_media_page(
			'KI-Bildkennzeichnung',
			'KI-Bildkennzeichnung',
			'manage_options',
			self::PAGE_SLUG,
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Rendert ausschließlich eine freigegebene View.
	 *
	 * Die View-Datei wird nie aus Query-Parametern zusammengesetzt. Damit können
	 * weder Dateipfade noch beliebiger PHP-Code über die URL eingebunden werden.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Du bist nicht berechtigt, diese Seite zu verwalten.' );
		}

		$active_tab = self::get_active_tab();
		$tabs       = self::TABS;
		$options    = MGD_AI_Image_Labels_Plugin_Options::get_options();
		$view_file  = MGD_AI_IMAGE_LABELS_DIR . 'views/admin/' . $active_tab . '.php';

		if ( ! is_file( $view_file ) ) {
			/* Dieser Fallback schützt gegen unvollständige Uploads; die Whitelist
			 * verhindert bereits, dass ein fremder Pfad hierher gelangen kann. */
			$active_tab = 'settings';
			$view_file  = MGD_AI_IMAGE_LABELS_DIR . 'views/admin/settings.php';
		}

		?>
		<div class="wrap">
			<h1>MGD KI-Bildkennzeichnung</h1>
			<p>Verwalte die globalen Standards und nutze die Kennzeichnungen nachvollziehbar in deinen WordPress- und Divi-Inhalten.</p>

			<nav class="nav-tab-wrapper" aria-label="Plugin-Bereiche">
				<?php foreach ( $tabs as $tab_slug => $tab_title ) : ?>
					<a class="nav-tab <?php echo $tab_slug === $active_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( self::get_tab_url( $tab_slug ) ); ?>">
						<?php echo esc_html( $tab_title ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php require $view_file; ?>
		</div>
		<?php
	}

	/** Liefert die URL eines sicheren, fest bekannten Tabs. */
	private static function get_tab_url( string $tab ): string {
		return add_query_arg(
			array(
				'page' => self::PAGE_SLUG,
				'tab'  => $tab,
			),
			admin_url( 'upload.php' )
		);
	}

	/** Liest nur sichere, vorgegebene Tab-Namen aus der URL. */
	private static function get_active_tab(): string {
		$requested_tab = isset( $_GET['tab'] ) && is_string( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reines Lese-Routing ohne Zustandsänderung.

		return array_key_exists( $requested_tab, self::TABS ) ? $requested_tab : 'settings';
	}
}
