<?php
global $R34ICS;
?>

<div class="wrap r34ics">

	<h2><?php echo get_admin_page_title(); ?></h2>
	
	<div class="metabox-holder columns-2">
	
		<div class="column-1">
				
			<div class="postbox" id="basic-example">

				<h3><span><?php _e('Basic Shortcode Example', 'r34ics'); ?></span></h3>
	
				<div class="inside">

					<p><?php printf(__('To insert an %1$s in a page, use the following shortcode format, replacing the all-caps text with your feed URL. Many additional customization options are available. Please see the %2$sUser Guide%3$s for details.', 'r34ics'), 'ICS Calendar', '<strong><a href="https://icscalendar.com/user-guide/" target="_blank">', '</a></strong>'); ?></p>

					<p><input type="text" name="null" readonly="readonly" value="[ics_calendar url=&quot;CALENDAR_FEED_URL&quot;]" style="width: 97%; background: white;" onclick="this.select();" /></p>
			
				</div>
		
			</div>

			<div class="postbox" id="utilities">

				<?php include_once(plugin_dir_path(__FILE__) . 'utilities.php'); ?>

			</div>

		</div>
	
		<div class="column-2">

			<?php include_once(plugin_dir_path(__FILE__) . 'sidebar.php'); ?>
	
		</div>
	
	</div>

</div>