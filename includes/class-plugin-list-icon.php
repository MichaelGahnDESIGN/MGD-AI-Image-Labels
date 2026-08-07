<?php
/**
 * Lokales Branding-Icon für die WordPress-Liste installierter Plugins.
 *
 * WordPress zeigt bei manuell oder über GitHub installierten Plugins in der
 * normalen Listenansicht kein Icon aus dem Release-Objekt an. Diese kleine,
 * bewusst getrennte Komponente ergänzt deshalb ausschließlich die eigene
 * Plugin-Zeile mit dem bereits im Paket enthaltenen Icon.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MGD_AI_Image_Labels_Plugin_List_Icon {

	/** Registriert das lokale Styling nur für die relevante Administrationsansicht. */
	public static function register(): void {
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/**
	 * Ergänzt das Icon ohne JavaScript und ohne externe Ressource.
	 *
	 * @param string $hook_suffix Aktuelle WordPress-Administrationsseite.
	 */
	public static function enqueue( string $hook_suffix ): void {
		if ( 'plugins.php' !== $hook_suffix ) {
			return;
		}

		wp_register_style( 'mgd-ail-plugin-list-icon', false, array(), MGD_AI_IMAGE_LABELS_VERSION );
		wp_enqueue_style( 'mgd-ail-plugin-list-icon' );

		$icon_url = esc_url( MGD_AI_IMAGE_LABELS_URL . 'assets/branding/icon-128x128.png' );
		$css      = sprintf(
			'tr[data-plugin="mgd-ai-image-labels/mgd-ai-image-labels.php"] .plugin-title strong{display:inline-flex;align-items:center;gap:10px;}tr[data-plugin="mgd-ai-image-labels/mgd-ai-image-labels.php"] .plugin-title strong:before{content:"";display:inline-block;flex:0 0 36px;width:36px;height:36px;border-radius:9px;background:center/cover no-repeat url("%1$s");box-shadow:0 2px 8px rgba(0,0,0,.16);}',
			$icon_url
		);

		wp_add_inline_style( 'mgd-ail-plugin-list-icon', $css );
	}
}
