<?php
/**
 * Plugin Name:       MGD KI-Bildkennzeichnung
 * Description:       Sichere Grundlage für die transparente Kennzeichnung von KI-bezogenen Bildern in der WordPress-Mediathek.
 * Version:            0.5.2
 * Requires at least:  6.0
 * Requires PHP:       8.1
 * Author:             Michael Gahn DESIGN
 * Text Domain:        mgd-ai-image-labels
 * Update URI:         https://github.com/MichaelGahnDESIGN/MGD-AI-Image-Labels
 *
 * Das Plugin ergänzt die WordPress-Mediathek um eine sichere Auswahl für
 * KI-Kennzeichnungen und gibt sie bei passenden WordPress- und Divi-5-Bildern
 * lokal sowie barrierefrei aus. Inhalte und Divi-Layouts werden nicht gespeichert.
 */

declare(strict_types=1);

// Direkte Aufrufe dieser Datei außerhalb von WordPress sicher beenden.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Zentrale Konstanten verhindern mehrfaches Initialisieren und erleichtern spätere Erweiterungen.
define( 'MGD_AI_IMAGE_LABELS_VERSION', '0.5.2' );
define( 'MGD_AI_IMAGE_LABELS_FILE', __FILE__ );
define( 'MGD_AI_IMAGE_LABELS_DIR', plugin_dir_path( __FILE__ ) );
define( 'MGD_AI_IMAGE_LABELS_URL', plugin_dir_url( __FILE__ ) );

require_once MGD_AI_IMAGE_LABELS_DIR . 'includes/class-plugin.php';

// Der Startpunkt registriert in dieser Ausbaustufe nur einen leeren, sicheren WordPress-Hook.
MGD_AI_Image_Labels_Plugin::boot();
