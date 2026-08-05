<?php
/**
 * Ansicht: Redaktioneller Text und explizite Veröffentlichung der AI-Philosophie.
 *
 * Die Verarbeitung der Formulare bleibt vollständig in der zugehörigen Klasse.
 * Diese View enthält keine Datenbanklogik und keine frei ausführbaren Skripte.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);
?>
<section class="mgd-ail-admin-section">
	<h2>AI-Philosophie</h2>
	<p>Erkläre hier transparent, wie du künstliche Intelligenz einsetzt. Der Text bleibt auf deiner WordPress-Installation und kann mit einem Shortcode auf jeder Seite ausgegeben werden.</p>

	<?php if ( isset( $_GET['mgd_ail_notice'] ) && is_string( $_GET['mgd_ail_notice'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reine, nach einem geschützten POST erzeugte Statusmeldung. ?>
		<div class="notice notice-info is-dismissible"><p><?php echo esc_html( rawurldecode( wp_unslash( $_GET['mgd_ail_notice'] ) ) ); ?></p></div>
	<?php endif; ?>

	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="mgd_ail_save_ai_philosophy">
		<?php wp_nonce_field( 'mgd_ail_save_ai_philosophy', 'mgd_ail_ai_philosophy_nonce' ); ?>
		<?php
		wp_editor(
			MGD_AI_Image_Labels_AI_Philosophy::get_content(),
			'mgd_ail_ai_philosophy',
			array(
				'textarea_name' => 'mgd_ail_ai_philosophy',
				'media_buttons' => false,
				'textarea_rows' => 12,
			)
		);
		?>
		<p class="description">Erlaubt sind Absätze, Überschriften, Listen, Hervorhebungen und Links. Skripte, eingebettete Fremdinhalte und Inline-Stile werden aus Sicherheitsgründen entfernt.</p>
		<?php submit_button( 'AI-Philosophie speichern' ); ?>
	</form>

	<h3>Auf deiner Website ausgeben</h3>
	<p>Füge diesen Shortcode in ein Text- oder Code-Modul ein:</p>
	<p><code>[mgd_ai_philosophy]</code></p>

	<h3>Eigene Seite anlegen</h3>
	<p>Der Button erstellt einmalig die Seite „AI-Philosophie“ mit diesem Shortcode. Ein Footer-Menü wird nur ergänzt, wenn WordPress genau eine eindeutige Menüposition mit „footer“ erkennt. Bei keiner oder mehreren passenden Positionen bleibt jedes Menü unverändert.</p>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<input type="hidden" name="action" value="mgd_ail_create_ai_philosophy_page">
		<?php wp_nonce_field( 'mgd_ail_create_ai_philosophy_page', 'mgd_ail_create_ai_philosophy_page_nonce' ); ?>
		<?php submit_button( 'AI-Philosophie-Seite anlegen', 'secondary', 'mgd_ail_create_ai_philosophy_page' ); ?>
	</form>
</section>
