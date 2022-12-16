<h3><?php _e('Utilities', 'r34ics'); ?></h3>

<div class="inside">

	<form id="r34ics-purge-calendar-transients" method="post" action="">
		<?php
		wp_nonce_field('r34ics','r34ics-purge-calendar-transients-nonce');
		?>
		<input type="submit" class="button button-primary" value="<?php echo esc_attr(__('Clear Cached Calendar Data', 'r34ics')); ?>" />
		<p><?php _e('This will immediately clear all existing cached calendar data (purge transients), forcing WordPress to reload all calendars the next time they are viewed. Caching will then resume as before.', 'r34ics'); ?></p>
	</form>

</div>
	
<hr />

<h3><?php _e('ICS Feed URL Tester', 'r34ics'); ?></h3>

<div class="inside">

	<p><?php _e('If you are concerned that the plugin is not properly retrieving your feed, you can test the URL here. The raw data received by the plugin will be displayed below.', 'r34ics'); ?></p>

	<form id="r34ics-url-tester" method="post" action="">
		<?php
		wp_nonce_field('r34ics','r34ics-url-tester-nonce');
		?>
		<div class="r34ics-input">
			<label for="r34ics-url-tester-url_to_test"><input type="text" name="url_to_test" id="r34ics-url-tester-url_to_test" value="<?php if (!empty($url_to_test)) { echo esc_attr($url_to_test); } ?>" placeholder="<?php echo esc_attr(__('Enter feed URL...', 'r34ics')); ?>" style="width: 50%;" /></label> <input type="submit" class="button button-primary" value="<?php echo esc_attr(__('Test URL', 'r34ics')); ?>" />
		</div>
	</form>
	
	<?php
	if (!empty($url_tester_result)) {
		?>
		<p><strong style="color: green;"><?php printf(__('%s received.', 'r34ics'), size_format(strlen($url_tester_result), 2)); ?></strong></p>
		<?php
		if (strpos($url_tester_result,'BEGIN:VCALENDAR') !== 0) {
			?>
			<p><strong style="background: yellow; color: red; padding: 2px 5px;"><?php _e('This does not appear to be a valid ICS feed URL.', 'r34ics'); ?></strong></p>
			<?php
		}
		?>
		<textarea class="diagnostics-window" readonly="readonly" style="cursor: copy;" onclick="this.select(); document.execCommand('copy');"><?php echo htmlentities($url_tester_result); ?></textarea>
		<?php
	}
	elseif (!empty($url_to_test)) {
		?>
		<p><strong style="color: red;"><?php _e('Could not retrieve data from the requested URL.', 'r34ics'); ?></strong></p>
		<?php
	}
	elseif (isset($_POST['r34ics-url-tester-nonce'])) {
		?>
		<p><strong style="color: red;"><?php _e('An unknown error occurred while attempting to retrieve the requested URL.', 'r34ics'); ?></strong></p>
		<?php
	}
	?>

</div>

<hr />

<h3><?php _e('System Report', 'r34ics'); ?></h3>

<div class="inside">

	<p><strong style="color: red;"><?php _e('Please copy the following text and include it in your message when emailing support.', 'r34ics'); ?><br />
	<?php printf(__('Also please include the %1$s shortcode exactly as you have it entered on the affected page.', 'r34ics'), 'ICS Calendar'); ?></strong></p>
	
	<textarea class="diagnostics-window" readonly="readonly" style="cursor: copy;" onclick="this.select(); document.execCommand('copy');"><?php r34ics_system_report(); ?></textarea>

</div>
