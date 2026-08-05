<?php
/**
 * Ansicht: Dokumentation kopierbarer CSS-Klassen und des Hintergrund-Shortcodes.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);

$label_classes = array(
	'generated'           => 'AI GENERATED',
	'partially-generated' => 'AI PARTIALLY GENERATED',
	'modified'            => 'AI MODIFIED',
	'deepfake'            => 'AI DEEPFAKE',
	'none'                => 'Keine Ausgabe (Keine KI)',
);

/* Der Beispielcode bleibt Datenwert eines readonly-Feldes und wird nicht als
 * Shortcode verarbeitet. Dadurch kann die Anleitung ohne Seiteneffekt kopiert
 * werden. */
$shortcode_example = '[mgd_ai_label image_id="123" class="mein-div" offset_x="24" offset_y="24"]';
?>
<section class="mgd-ail-admin-section">
	<h2>CSS-Klassen &amp; Hintergrundbilder</h2>
	<p>Die Kennzeichnung wird automatisch an regulär ausgegebene WordPress- und unterstützte Divi-Bilder angehängt. Für ein Bild, das du als Hintergrund eines Divi-Containers verwendest, setzt du den folgenden Shortcode in denselben Container.</p>

	<h3>Hintergrundbild in Divi</h3>
	<ol>
		<li>Wähle im Divi-Container unter <strong>Erweitert → CSS-ID &amp; Klassen</strong> die Klasse <code>mgd-ail-background-container</code>.</li>
		<li>Setze ein Text- oder Code-Modul in denselben Container.</li>
		<li>Füge diesen Shortcode ein und ersetze nur die Bild-ID sowie bei Bedarf die Abstände:</li>
	</ol>
	<p><input class="large-text code" type="text" readonly value="<?php echo esc_attr( $shortcode_example ); ?>" aria-label="Beispiel für den Hintergrund-Shortcode"></p>
	<p class="description">Der Shortcode führt keine fremden Eingaben aus. Er zeigt nur ein Label, wenn die Bild-ID in der Mediathek eine aktive KI-Kennzeichnung hat.</p>

	<h3>Status-Klassen</h3>
	<table class="widefat striped">
		<thead><tr><th>Status</th><th>Sichtbarer Text</th><th>Klasse am Label</th></tr></thead>
		<tbody>
			<?php foreach ( $label_classes as $status => $label ) : ?>
				<tr>
					<td><?php echo esc_html( $status ); ?></td>
					<td><?php echo esc_html( $label ); ?></td>
					<td><input class="regular-text code" type="text" readonly value="<?php echo esc_attr( 'mgd-ail-badge mgd-ail-status-' . $status ); ?>" aria-label="CSS-Klasse für <?php echo esc_attr( $status ); ?>"></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<h3>Darstellungs-Klassen</h3>
	<ul>
		<li><code>mgd-ail-position-top-left</code>, <code>mgd-ail-position-top-right</code>, <code>mgd-ail-position-bottom-left</code>, <code>mgd-ail-position-bottom-right</code></li>
		<li><code>mgd-ail-theme-auto</code>, <code>mgd-ail-theme-light</code>, <code>mgd-ail-theme-dark</code></li>
		<li><code>mgd-ail-background-container</code> als sicherer Positionsbezug für Hintergrundbilder</li>
	</ul>
</section>
