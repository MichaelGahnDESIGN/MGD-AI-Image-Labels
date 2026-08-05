<?php
/**
 * Sicherheits- und Regressionstest für die AI-Philosophie.
 *
 * Die Datei verwendet einen sehr kleinen WordPress-Test-Harness. Dadurch
 * bleibt sie ohne Datenbank und ohne Netzwerk reproduzierbar ausführbar.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'MGD_AI_IMAGE_LABELS_DIR', dirname( __DIR__ ) . '/' );

/** @var array<string, mixed> Kontrollierte WordPress-Ersatzdaten. */
$GLOBALS['mgd_ail_philosophy_test'] = array(
	'options'       => array(),
	'meta_queries'  => array(),
	'posts'         => array(),
	'inserted_posts' => array(),
	'menu_locations' => array(),
	'menu_items'    => array(),
	'updated_items' => array(),
	'notices'       => array(),
	'capabilities'  => array(
		'manage_options' => true,
		'publish_pages'  => true,
	),
);

function add_action( string $hook, callable $callback ): void {
	$GLOBALS['mgd_ail_philosophy_test']['actions'][ $hook ] = $callback;
}

function add_shortcode( string $tag, callable $callback ): void {
	$GLOBALS['mgd_ail_philosophy_test']['shortcodes'][ $tag ] = $callback;
}

function get_option( string $name, $default = false ) {
	return $GLOBALS['mgd_ail_philosophy_test']['options'][ $name ] ?? $default;
}

function update_option( string $name, $value ): bool {
	$GLOBALS['mgd_ail_philosophy_test']['options'][ $name ] = $value;
	return true;
}

function wp_kses( string $content, array $allowed_html ): string {
	return strip_tags( $content, '<p><h2><h3><h4><ul><ol><li><strong><em><a><br>' );
}

function wpautop( string $content ): string {
	return '<p>' . $content . '</p>';
}

function current_user_can( string $capability ): bool {
	return true === ( $GLOBALS['mgd_ail_philosophy_test']['capabilities'][ $capability ] ?? false );
}

function wp_verify_nonce( string $nonce, string $action ): bool {
	return 'gültig' === $nonce && 'mgd_ail_create_ai_philosophy_page' === $action;
}

function wp_nonce_field( string $action, string $name ): void {
	echo '<input name="' . htmlspecialchars( $name, ENT_QUOTES, 'UTF-8' ) . '" value="gültig">';
}

function submit_button( string $text, string $type = 'primary', string $name = '', bool $wrap = true, array $other_attributes = array() ): void {
	echo '<button name="' . htmlspecialchars( $name, ENT_QUOTES, 'UTF-8' ) . '">' . htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ) . '</button>';
}

function wp_editor( string $content, string $editor_id, array $settings = array() ): void {
	echo '<textarea id="' . htmlspecialchars( $editor_id, ENT_QUOTES, 'UTF-8' ) . '">' . htmlspecialchars( $content, ENT_QUOTES, 'UTF-8' ) . '</textarea>';
}

function wp_insert_post( array $postarr, bool $wp_error = false ) {
	$id = count( $GLOBALS['mgd_ail_philosophy_test']['inserted_posts'] ) + 100;
	$GLOBALS['mgd_ail_philosophy_test']['inserted_posts'][ $id ] = $postarr;
	return $id;
}

function update_post_meta( int $post_id, string $meta_key, $meta_value ): bool {
	$GLOBALS['mgd_ail_philosophy_test']['post_meta'][ $post_id ][ $meta_key ] = $meta_value;
	return true;
}

function get_posts( array $query ): array {
	$GLOBALS['mgd_ail_philosophy_test']['meta_queries'][] = $query;
	return $GLOBALS['mgd_ail_philosophy_test']['posts'];
}

function get_nav_menu_locations(): array {
	return $GLOBALS['mgd_ail_philosophy_test']['menu_locations'];
}

function wp_get_nav_menu_items( int $menu_id ): array {
	return $GLOBALS['mgd_ail_philosophy_test']['menu_items'][ $menu_id ] ?? array();
}

function get_permalink( int $post_id ): string {
	return 'https://example.test/?page_id=' . $post_id;
}

function untrailingslashit( string $value ): string {
	return rtrim( $value, '/' );
}

function wp_update_nav_menu_item( int $menu_id, int $menu_item_db_id, array $menu_item_data ): int {
	$GLOBALS['mgd_ail_philosophy_test']['updated_items'][] = compact( 'menu_id', 'menu_item_db_id', 'menu_item_data' );
	return 1;
}

function add_query_arg( array $args, string $url ): string {
	return $url . '?' . http_build_query( $args );
}

function admin_url( string $path = '' ): string {
	return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
}

function esc_url( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

function esc_html( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

function wp_die( string $message ): void {
	throw new RuntimeException( $message );
}

function is_wp_error( $thing ): bool {
	return false;
}

$class_file = dirname( __DIR__ ) . '/includes/class-ai-philosophy.php';
if ( ! is_file( $class_file ) ) {
	throw new RuntimeException( 'Die AI-Philosophie-Klasse fehlt noch.' );
}

require_once $class_file;

/** @param mixed $expected @param mixed $actual */
function mgd_ail_philosophy_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		throw new RuntimeException( $message . ' Erwartet: ' . var_export( $expected, true ) . '; erhalten: ' . var_export( $actual, true ) );
	}
}

function mgd_ail_philosophy_assert_contains( string $needle, string $haystack, string $message ): void {
	if ( false === strpos( $haystack, $needle ) ) {
		throw new RuntimeException( $message . ' Fehlend: ' . $needle );
	}
}

MGD_AI_Image_Labels_AI_Philosophy::register();
mgd_ail_philosophy_assert_same( true, isset( $GLOBALS['mgd_ail_philosophy_test']['shortcodes']['mgd_ai_philosophy'] ), 'Der Philosophie-Shortcode wird unter einem festen Namen registriert.' );

$content = MGD_AI_Image_Labels_AI_Philosophy::render_shortcode();
mgd_ail_philosophy_assert_contains( 'verantwortungsvoll', $content, 'Der Standardtext erklärt einen verantwortungsvollen KI-Einsatz.' );
mgd_ail_philosophy_assert_same( '', MGD_AI_Image_Labels_AI_Philosophy::sanitize_content( '<script>alert(1)</script>' ), 'Unsicheres Script-Markup wird nicht gespeichert.' );
mgd_ail_philosophy_assert_contains( '<strong>Transparenz</strong>', MGD_AI_Image_Labels_AI_Philosophy::sanitize_content( '<strong>Transparenz</strong><iframe>weg</iframe>' ), 'Erlaubte Betonungen bleiben erhalten.' );

$created = MGD_AI_Image_Labels_AI_Philosophy::create_page();
mgd_ail_philosophy_assert_same( 100, $created['page_id'], 'Beim ersten Aufruf wird eine einzige Philosophie-Seite angelegt.' );
mgd_ail_philosophy_assert_same( '[mgd_ai_philosophy]', $GLOBALS['mgd_ail_philosophy_test']['inserted_posts'][100]['post_content'], 'Die Seite enthält ausschließlich den wartbaren Shortcode.' );
mgd_ail_philosophy_assert_same( 0, count( $GLOBALS['mgd_ail_philosophy_test']['updated_items'] ), 'Ohne eindeutige Footer-Position bleibt jedes Menü unverändert.' );

$GLOBALS['mgd_ail_philosophy_test']['posts'] = array( (object) array( 'ID' => 100 ) );
$again = MGD_AI_Image_Labels_AI_Philosophy::create_page();
mgd_ail_philosophy_assert_same( 100, $again['page_id'], 'Eine vorhandene, eigene Philosophie-Seite wird niemals doppelt angelegt.' );
mgd_ail_philosophy_assert_same( 1, count( $GLOBALS['mgd_ail_philosophy_test']['inserted_posts'] ), 'Die Duplikatvermeidung schützt Inhalte zuverlässig.' );

$GLOBALS['mgd_ail_philosophy_test']['posts']          = array();
$GLOBALS['mgd_ail_philosophy_test']['menu_locations'] = array( 'footer_navigation' => 9, 'footer_secondary' => 10 );
$GLOBALS['mgd_ail_philosophy_test']['updated_items']  = array();
$multi_footer = MGD_AI_Image_Labels_AI_Philosophy::create_page();
mgd_ail_philosophy_assert_same( 0, count( $GLOBALS['mgd_ail_philosophy_test']['updated_items'] ), 'Mehrere Footer-Menüs werden nie automatisch verändert.' );
mgd_ail_philosophy_assert_contains( 'manuell', $multi_footer['notice'], 'Mehrdeutige Footer-Zuordnungen geben einen klaren manuellen Hinweis.' );

$GLOBALS['mgd_ail_philosophy_test']['menu_locations'] = array( 'footer_navigation' => 9 );
$GLOBALS['mgd_ail_philosophy_test']['updated_items']  = array();
$single_footer = MGD_AI_Image_Labels_AI_Philosophy::create_page();
mgd_ail_philosophy_assert_same( 1, count( $GLOBALS['mgd_ail_philosophy_test']['updated_items'] ), 'Genau ein eindeutiges Footer-Menü darf sicher ergänzt werden.' );
mgd_ail_philosophy_assert_same( 'post_type', $GLOBALS['mgd_ail_philosophy_test']['updated_items'][0]['menu_item_data']['menu-item-type'], 'Der Footer verlinkt über den WordPress-Seitentyp statt über eine freie URL.' );
mgd_ail_philosophy_assert_same( $single_footer['page_id'], $GLOBALS['mgd_ail_philosophy_test']['updated_items'][0]['menu_item_data']['menu-item-object-id'], 'Der Footer verweist auf die erzeugte Philosophie-Seite.' );

$GLOBALS['mgd_ail_philosophy_test']['posts'] = array( (object) array( 'ID' => $single_footer['page_id'] ) );
$GLOBALS['mgd_ail_philosophy_test']['menu_items'][9] = array( (object) array( 'object_id' => 0, 'url' => get_permalink( $single_footer['page_id'] ) ) );
$GLOBALS['mgd_ail_philosophy_test']['updated_items'] = array();
$url_present = MGD_AI_Image_Labels_AI_Philosophy::create_page();
mgd_ail_philosophy_assert_same( 0, count( $GLOBALS['mgd_ail_philosophy_test']['updated_items'] ), 'Ein vorhandener Menü-Link per URL wird nicht doppelt ergänzt.' );
mgd_ail_philosophy_assert_contains( 'bereits', $url_present['notice'], 'Ein vorhandener URL-Link wird nachvollziehbar bestätigt.' );

echo "PASS: Die AI-Philosophie bleibt sicher, eindeutig und footer-schonend.\n";
