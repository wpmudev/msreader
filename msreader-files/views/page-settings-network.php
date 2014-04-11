<div class="wrap">

	<?php screen_icon(); ?>
	<h2><?php _e('MSReader', 'wmd_msreader') ?></h2>
	<form action="settings.php?index.php" method="post" >

		<?php
		settings_fields('wmd_msreader_options');
		$options = $this->plugin['options'];

		do_settings_sections('wmd_msreader_options_general');
		?>

		<h3><?php _e('MSReader settings', 'wmd_msreader') ?></h3>
		<p><?php _e('MSReader settings description.', 'wmd_msreader') ?></p>

		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="wmd_msreader_options[select]"><?php _e('MSReader test setting: select', 'wmd_msreader') ?></label>
				</th>

				<td>
					<?php
					$select_options = array( 'value_one' => 'Label One', 'value_two' => 'Label Two' );
					?>
					<select name="wmd_msreader_options[select]">
						<?php $this->helpers->the_select_options($select_options, $options['select']); ?>
					</select>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">
					<label for="wmd_msreader_options[text]"><?php _e('MSReader test setting: text', 'wmd_msreader') ?></label>
				</th>

				<td>
					<input type="text" class="regular-text ltr" name="wmd_msreader_options[text]" value="<?php echo esc_attr($options['text']); ?>" />
					<p class="description"><?php _e('Description', 'wmd_msreader') ?></p>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">
					<label for="wmd_msreader_options[text]"><?php _e('MSReader test setting: textarea', 'wmd_msreader') ?></label>
				</th>

				<td>
					<textarea class="large-text ltr" name="wmd_msreader_options[textarea]" /><?php echo esc_textarea($options['textarea']); ?></textarea>
					<p class="description"><?php _e('Description', 'wmd_msreader') ?></p>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes', 'wmd_msreader') ?>" />
		</p>
	</form>

</div>