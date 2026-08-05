<?php
/**
 * Ansicht: Globale Standards für die Label-Darstellung.
 *
 * @var array<string, string> $options Bereits streng validierte Anzeigeoptionen.
 *
 * @package MGD_AI_Image_Labels
 */

declare(strict_types=1);
?>
<section class="mgd-ail-admin-section">
	<h2>Globale Label-Standards</h2>
	<p>Diese Werte gelten als Ausgangspunkt für alle Kennzeichnungen. Pro Bild bleiben Status, Position und Glas-Variante weiterhin separat in der Mediathek auswählbar.</p>

	<form action="options.php" method="post">
		<?php settings_fields( MGD_AI_Image_Labels_Plugin_Options::OPTION_NAME ); ?>
		<?php do_settings_sections( MGD_AI_Image_Labels_Plugin_Options::OPTION_NAME ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="mgd-ail-font-size">Schriftgröße</label></th>
				<td><input id="mgd-ail-font-size" name="mgd_ail_display_options[font_size]" type="number" min="6" max="24" value="<?php echo esc_attr( $options['font_size'] ); ?>"> px <p class="description">Erlaubt sind 6 bis 24 Pixel.</p></td>
			</tr>
			<tr>
				<th scope="row"><label for="mgd-ail-offset">Abstand zum Bildrand</label></th>
				<td><input id="mgd-ail-offset" name="mgd_ail_display_options[offset]" type="number" min="0" max="96" value="<?php echo esc_attr( $options['offset'] ); ?>"> px</td>
			</tr>
			<tr>
				<th scope="row"><label for="mgd-ail-padding-y">Innenabstand oben/unten</label></th>
				<td><input id="mgd-ail-padding-y" name="mgd_ail_display_options[padding_y]" type="number" min="2" max="24" value="<?php echo esc_attr( $options['padding_y'] ); ?>"> px</td>
			</tr>
			<tr>
				<th scope="row"><label for="mgd-ail-padding-x">Innenabstand links/rechts</label></th>
				<td><input id="mgd-ail-padding-x" name="mgd_ail_display_options[padding_x]" type="number" min="4" max="40" value="<?php echo esc_attr( $options['padding_x'] ); ?>"> px</td>
			</tr>
			<tr>
				<th scope="row"><label for="mgd-ail-radius">Eckenradius</label></th>
				<td><input id="mgd-ail-radius" name="mgd_ail_display_options[radius]" type="number" min="0" max="999" value="<?php echo esc_attr( $options['radius'] ); ?>"> px</td>
			</tr>
			<tr>
				<th scope="row"><label for="mgd-ail-blur">Glasun­schärfe</label></th>
				<td><input id="mgd-ail-blur" name="mgd_ail_display_options[blur]" type="number" min="0" max="24" value="<?php echo esc_attr( $options['blur'] ); ?>"> px</td>
			</tr>
			<tr>
				<th scope="row"><label for="mgd-ail-theme">Standard-Glasvariante</label></th>
				<td>
					<select id="mgd-ail-theme" name="mgd_ail_display_options[theme]">
						<option value="auto"<?php echo selected( 'auto', $options['theme'], false ); ?>>Automatisch</option>
						<option value="light"<?php echo selected( 'light', $options['theme'], false ); ?>>Hell</option>
						<option value="dark"<?php echo selected( 'dark', $options['theme'], false ); ?>>Dunkel</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="mgd-ail-position">Standard-Position</label></th>
				<td>
					<select id="mgd-ail-position" name="mgd_ail_display_options[position]">
						<option value="bottom-right"<?php echo selected( 'bottom-right', $options['position'], false ); ?>>Unten rechts</option>
						<option value="bottom-left"<?php echo selected( 'bottom-left', $options['position'], false ); ?>>Unten links</option>
						<option value="top-right"<?php echo selected( 'top-right', $options['position'], false ); ?>>Oben rechts</option>
						<option value="top-left"<?php echo selected( 'top-left', $options['position'], false ); ?>>Oben links</option>
					</select>
				</td>
			</tr>
		</table>

		<?php submit_button( 'Globale Standards speichern' ); ?>
	</form>

	<h3>Vorschau</h3>
	<div style="display:inline-block; padding:28px; border-radius:12px; background:#3a3f46; color:#fff;">
		<span style="display:inline-block; padding:<?php echo esc_attr( $options['padding_y'] ); ?>px <?php echo esc_attr( $options['padding_x'] ); ?>px; border:1px solid rgba(255,255,255,.38); border-radius:<?php echo esc_attr( $options['radius'] ); ?>px; background:rgba(20,20,25,.52); font-size:<?php echo esc_attr( $options['font_size'] ); ?>px;">AI GENERATED</span>
	</div>
	<p class="description">Die Vorschau verwendet nur bereits validierte Zahlen und zeigt die globale Ausgangsgestaltung.</p>

	<h3>Shortcodes</h3>
	<p><code>[mgd_ai_label image_id="123" class="mein-div" offset_x="24" offset_y="24"]</code></p>
	<p>Für einen Hintergrund-Container ergänze zusätzlich die CSS-Klasse <code>mgd-ail-background-container</code>. Eine vollständige Anleitung steht im Reiter „CSS-Klassen“.</p>
</section>
