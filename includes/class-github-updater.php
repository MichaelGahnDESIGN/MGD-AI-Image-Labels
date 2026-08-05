<?php
/**
 * Sichere Auswertung öffentlicher GitHub-Releases für WordPress-Updates.
 *
 * Die Klasse enthält zunächst bewusst nur reine, testbare Validierungslogik.
 * Netzwerk- und WordPress-Hooks werden erst in einem getrennten Schritt ergänzt.
 * Dadurch kann eine manipulierte oder unvollständige API-Antwort nie als Update
 * an WordPress weitergereicht werden.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prüft Release-Daten des eindeutig festgelegten öffentlichen GitHub-Repositories.
 */
final class MGD_AI_Image_Labels_GitHub_Updater {

	/**
	 * Öffentlicher, fest verdrahteter Endpunkt für den neuesten stabilen Release.
	 */
	private const RELEASE_ENDPOINT = 'https://api.github.com/repos/MichaelGahnDESIGN/MGD-AI-Image-Labels/releases/latest';

	/**
	 * Eindeutiger Plugin-Slug für WordPress und die Update-Informationen.
	 */
	private const PLUGIN_SLUG = 'mgd-ai-image-labels';

	/**
	 * Zwölf Stunden vermeiden unnötige API-Aufrufe und GitHub-Ratenlimits.
	 */
	private const CACHE_TTL = 43200;

	/**
	 * Lokaler Schlüssel für den Netzwerk-Transienten.
	 */
	private const CACHE_KEY = 'mgd_ail_github_latest_release';

	/**
	 * Erwarteter Name des Release-Pakets ohne Versionsnummer.
	 */
	private const PACKAGE_PREFIX = 'mgd-ai-image-labels-';

	/**
	 * Erwartete öffentliche Download-Domain.
	 */
	private const DOWNLOAD_HOST = 'github.com';

	/**
	 * Normalisiert und validiert eine Antwort der GitHub-Releases-API.
	 *
	 * @param array<string, mixed> $release Unverarbeitete API-Antwort.
	 * @return array{version: string, package: string}|array{} Validierte Daten oder einen sicheren Leerwert.
	 */
	public static function normalize_release( array $release ): array {
		$version = self::normalize_version( $release['tag_name'] ?? '' );

		if ( '' === $version || ! is_array( $release['assets'] ?? null ) ) {
			return array();
		}

		$package = self::find_plugin_asset( $release['assets'], $version );

		if ( '' === $package ) {
			return array();
		}

		return array(
			'version' => $version,
			'package' => $package,
		);
	}

	/**
	 * Registriert die nativen WordPress-Filter für verfügbare Plugin-Updates.
	 */
	public static function register(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( self::class, 'inject_update' ) );
		add_filter( 'plugins_api', array( self::class, 'provide_plugin_information' ), 20, 3 );
	}

	/**
	 * Baut ein WordPress-kompatibles Update-Objekt nur für neuere Versionen.
	 *
	 * @param array<string, mixed> $release      Bereits validierte Release-Daten.
	 * @param string               $current      Lokal installierte Plugin-Version.
	 * @param string               $plugin_file  Von WordPress erwarteter Plugin-Dateiname.
	 * @return object|null Ein Update-Objekt oder null, wenn kein Update nötig ist.
	 */
	public static function build_update( array $release, string $current, string $plugin_file ): ?object {
		$version = $release['version'] ?? '';
		$package = $release['package'] ?? '';
		$branding = self::get_branding_urls();

		if ( ! is_string( $version ) || ! is_string( $package ) || '' === $version || '' === $package || ! version_compare( $version, $current, '>' ) ) {
			return null;
		}

		return (object) array(
			'slug'        => self::PLUGIN_SLUG,
			'plugin'      => $plugin_file,
			'new_version' => $version,
			'url'         => 'https://github.com/MichaelGahnDESIGN/MGD-AI-Image-Labels',
			'package'     => $package,
			'tested'      => '6.0',
			'requires'    => '6.0',
			'requires_php'=> '8.1',
			'icons'       => $branding['icons'],
			'banners'     => $branding['banners'],
		);
	}

	/**
	 * Ergänzt WordPress nur dann um ein Update, wenn ein verifizierter Release
	 * wirklich neuer als die lokal installierte Version ist.
	 *
	 * @param mixed $transient WordPress-Transient mit bereits geprüften Plugins.
	 * @return mixed Unveränderter oder um dieses Plugin ergänzter Transient.
	 */
	public static function inject_update( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		$plugin_file = plugin_basename( MGD_AI_IMAGE_LABELS_FILE );
		$update      = self::build_update( self::get_latest_release(), MGD_AI_IMAGE_LABELS_VERSION, $plugin_file );

		if ( null !== $update ) {
			if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
				$transient->response = array();
			}

			$transient->response[ $plugin_file ] = $update;
		}

		return $transient;
	}

	/**
	 * Liefert die Detailansicht, die WordPress im Update-Dialog anzeigen kann.
	 *
	 * @param mixed  $result Bisheriges Ergebnis eines anderen Update-Providers.
	 * @param string $action Angeforderte WordPress-Update-Aktion.
	 * @param mixed  $args   Argumente der Update-Abfrage.
	 * @return mixed Eigenes Detailobjekt oder das unveränderte Fremdergebnis.
	 */
	public static function provide_plugin_information( $result, string $action, $args ) {
		if ( 'plugin_information' !== $action || ! is_object( $args ) || self::PLUGIN_SLUG !== ( $args->slug ?? '' ) ) {
			return $result;
		}

		// Die Detailansicht bleibt bewusst nutzbar, wenn GitHub temporär nicht
		// erreichbar ist. Ein Netzwerkfehler darf Hilfe im WordPress-Backend nie
		// verschwinden lassen; nur der Download-Link bleibt dann leer.
		$release  = self::get_latest_release();
		$branding = self::get_branding_urls();
		$version  = isset( $release['version'] ) && is_string( $release['version'] ) ? $release['version'] : MGD_AI_IMAGE_LABELS_VERSION;
		$package  = isset( $release['package'] ) && is_string( $release['package'] ) ? $release['package'] : '';

		return (object) array(
			'name'          => 'MGD KI-Bildkennzeichnung',
			'slug'          => self::PLUGIN_SLUG,
			'version'       => $version,
			'requires'      => '6.0',
			'requires_php'  => '8.1',
			'author'        => '<a href="https://michael-gahn.de/" target="_blank" rel="noopener noreferrer">Michael Gahn DESIGN</a>',
			'author_profile'=> 'https://michael-gahn.de/',
			'homepage'      => 'https://github.com/MichaelGahnDESIGN/MGD-AI-Image-Labels',
			'support_url'   => 'https://michael-gahn.de/support/',
			'download_link' => $package,
			'icons'         => $branding['icons'],
			'banners'       => $branding['banners'],
			'sections'      => self::get_information_sections(),
		);
	}

	/**
	 * Liefert die verständlichen Inhalte des nativen WordPress-Detailfensters.
	 *
	 * Der Inhalt ist bewusst lokal und statisch: Für diese Hilfe werden keine
	 * personenbezogenen Daten, keine Tracking-Skripte und keine Fremdinhalte
	 * geladen. Die Links öffnen sich aus der Detailansicht heraus separat.
	 *
	 * @return array<string, string>
	 */
	private static function get_information_sections(): array {
		return array(
			'description' => '<h3>Transparente Bildkennzeichnung – direkt in der Mediathek</h3><p>MGD KI-Bildkennzeichnung ergänzt WordPress um eine klare Auswahl für KI-bezogene Bilder. Für jedes Bild lassen sich Status, Position und eine kontraststarke Glas-Variante separat festlegen.</p><h4>Das Plugin bietet</h4><ul><li>fünf eindeutige Kennzeichnungsarten – von „Keine KI“ bis „Deepfake / täuschend echt“;</li><li>eine dezente, barrierefreundliche Ausgabe mit wählbarer Position;</li><li>helle, dunkle oder automatische Glas-Optik;</li><li>eine Speicherung direkt aus den Anhang-Details der Mediathek;</li><li>eine lokale Ausgabe ohne externe Skripte oder Tracking.</li></ul><p><a href="https://michael-gahn.de/" target="_blank" rel="noopener noreferrer">Michael Gahn DESIGN besuchen</a> · <a href="https://michael-gahn.de/support/" target="_blank" rel="noopener noreferrer">Support öffnen</a></p>',
			'installation' => '<h3>Installation und erster Einsatz</h3><ol><li>Die ZIP-Datei unter <strong>Plugins → Installieren → Plugin hochladen</strong> auswählen und aktivieren.</li><li>In der <strong>Mediathek</strong> ein Bild öffnen.</li><li>Unter <strong>KI-Kennzeichnung</strong> den passenden Status, die Position und die Glas-Variante wählen.</li><li><strong>Kennzeichnung speichern</strong> wählen und die Seite mit dem Bild im Frontend prüfen.</li></ol><p>Das Plugin verändert keine Bilddateien. Es speichert nur die gewählte Kennzeichnungs-Information als geschützte WordPress-Anhang-Metadaten.</p>',
			'faq' => '<h3>Häufige Fragen</h3><h4>Warum sehe ich kein Label?</h4><p>Prüfe zuerst, ob für genau dieses Bild ein Status außer „Keine KI“ gespeichert wurde. Leere ggf. den Seiten- und Browser-Cache.</p><h4>Wird mein Bild verändert?</h4><p>Nein. Das Label wird im Frontend ergänzend ausgegeben; die Originaldatei bleibt unverändert.</p><h4>Kann ich die Position pro Bild wählen?</h4><p>Ja. Oben links, oben rechts, unten links und unten rechts stehen pro Medien-Anhang zur Verfügung.</p><h4>Wo erhalte ich Hilfe?</h4><p><a href="https://michael-gahn.de/support/" target="_blank" rel="noopener noreferrer">Support bei Michael Gahn DESIGN</a> sowie die <a href="https://github.com/MichaelGahnDESIGN/MGD-AI-Image-Labels/wiki" target="_blank" rel="noopener noreferrer">Dokumentation im Wiki</a> helfen weiter.</p>',
			'changelog' => '<h3>Änderungsprotokoll</h3><h4>0.6.0</h4><ul><li>Zentrale Verwaltung unter Medien mit globalen Label-Standards, CSS-Klassen und AI-Philosophie ergänzt.</li><li>Hintergrund-Shortcode für Divi-Container und sichere, vorsichtige AI-Philosophie-Seite ergänzt.</li><li>Speichern im sichtbaren Divi-5-Medienfenster gegen verdeckte Anhangfelder abgesichert.</li></ul><h4>0.5.3</h4><ul><li>Professionelles lokales Icon und Banner für den WordPress-Update- und Detaildialog ergänzt.</li><li>Native Detailansicht mit Installation, FAQ, Änderungsprotokoll sowie Website-, Dokumentations-, Support- und GitHub-Links ergänzt.</li></ul><h4>0.5.2</h4><ul><li>Gespeicherte Werte nach dem AJAX-Speichern unmittelbar in die aktuelle Ansicht zurückgeschrieben.</li></ul><h4>0.5.1</h4><ul><li>Status „Teilweise KI generiert“ und sicherer GitHub-basierter Update-Mechanismus ergänzt.</li></ul>',
		);
	}

	/**
	 * Liefert ausschließlich lokale, vom Plugin-Paket ausgelieferte Branding-URLs.
	 *
	 * Der sichere Leerwert hilft den unabhängigen PHP-Tests und verhindert, dass
	 * diese Klasse außerhalb von WordPress versehentlich eine externe URL baut.
	 *
	 * @return array{icons: array<string, string>, banners: array<string, string>}
	 */
	private static function get_branding_urls(): array {
		if ( ! defined( 'MGD_AI_IMAGE_LABELS_URL' ) || ! is_string( MGD_AI_IMAGE_LABELS_URL ) || '' === MGD_AI_IMAGE_LABELS_URL ) {
			return array(
				'icons'   => array(),
				'banners' => array(),
			);
		}

		$base_url = rtrim( MGD_AI_IMAGE_LABELS_URL, '/' ) . '/assets/branding/';

		return array(
			'icons'   => array(
				'1x' => $base_url . 'icon-128x128.png',
				'2x' => $base_url . 'icon-256x256.png',
			),
			'banners' => array(
				'low'  => $base_url . 'banner-772x250.png',
				'high' => $base_url . 'banner-772x250.png',
			),
		);
	}

	/**
	 * Liest eine Release-Antwort höchstens alle zwölf Stunden aus dem öffentlichen
	 * GitHub-Endpunkt. Bei jedem Fehler bleibt WordPress sicher beim bisherigen Stand.
	 *
	 * @return array{version: string, package: string}|array{}
	 */
	private static function get_latest_release(): array {
		$cached = get_site_transient( self::CACHE_KEY );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_safe_remote_get(
			self::RELEASE_ENDPOINT,
			array(
				'timeout' => 8,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'MGD-AI-Image-Labels/' . MGD_AI_IMAGE_LABELS_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$release = self::normalize_release( $decoded );

		if ( ! empty( $release ) ) {
			set_site_transient( self::CACHE_KEY, $release, self::CACHE_TTL );
		}

		return $release;
	}

	/**
	 * Erlaubt ausschließlich dreiteilige, für WordPress vergleichbare Versionen.
	 *
	 * @param mixed $tag_name Wert aus der GitHub-Antwort.
	 */
	private static function normalize_version( $tag_name ): string {
		if ( ! is_string( $tag_name ) ) {
			return '';
		}

		$version = ltrim( trim( $tag_name ), 'vV' );

		if ( 1 !== preg_match( '/^(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?$/', $version ) ) {
			return '';
		}

		return $version;
	}

	/**
	 * Sucht das exakt zur Version passende ZIP-Paket auf GitHub.
	 *
	 * @param array<int, mixed> $assets  Unverarbeitete Asset-Liste der API.
	 * @param string            $version Bereits geprüfte Version.
	 */
	private static function find_plugin_asset( array $assets, string $version ): string {
		$expected_name = self::PACKAGE_PREFIX . $version . '.zip';

		foreach ( $assets as $asset ) {
			if ( ! is_array( $asset ) || $expected_name !== ( $asset['name'] ?? null ) ) {
				continue;
			}

			$url = $asset['browser_download_url'] ?? '';

			if ( is_string( $url ) && self::is_expected_download_url( $url ) ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * Begrenzt Update-Pakete auf HTTPS-Downloads von GitHub.
	 */
	private static function is_expected_download_url( string $url ): bool {
		$parts = parse_url( $url );

		if ( ! is_array( $parts ) || ! isset( $parts['scheme'], $parts['host'] ) ) {
			return false;
		}

		return 'https' === strtolower( (string) $parts['scheme'] )
			&& 0 === strcasecmp( self::DOWNLOAD_HOST, (string) $parts['host'] );
	}
}
