<?php
// Require object
if (empty($ics_data)) { return false; }

global $R34ICS;
global $wp_locale;

$start_of_week = get_option('start_of_week', 0);

$date_format = r34ics_date_format($args['format']);

$ics_calendar_classes = apply_filters('r34ics_calendar_classes', null, $args, true);

// Feed colors custom CSS
if (!empty($ics_data['colors'])) {
	r34ics_feed_colors_css($ics_data, true);
}

// Prepare event details toggle lightbox
if ($args['toggle'] === 'lightbox') {
	r34ics_lightbox_container();
}

// There doesn't seem to be a clean way to include the masonry script conditionally using wp_enqueue_script():
// - cannot be done in R34ICS->enqueue_scripts() because there's no way to detect the requested view
// - cannot be done in R34ICS->shortcode() or while rendering this template because wp_enqueue_script
//   does not work inside a shortcode ESI subrequest
// wp_enqueue_script('ics-calendar-masonry', R34ICS_PLUGIN_URL . 'vendors/masonry/masonry.pkgd.min.js', array(), $this->version, true);
?>
<script type="text/javascript" src="<?php echo R34ICS_PLUGIN_URL . 'vendors/masonry/masonry.pkgd.min.js' ?>" id="ics-calendar-masonry-js"></script>
<div class="<?php echo esc_attr($ics_calendar_classes); ?>" id="<?php echo esc_attr($ics_data['guid']); ?>" data-masonry='{ "columnWidth": ".ics-calendar-masonry-grid-sizer", "gutter": ".ics-calendar-masonry-gutter", "itemSelector": ".ics-calendar-masonry-grid-item", "percentPosition": "true"}'>
	<div class="ics-calendar-masonry-grid-sizer"></div>
	<div class="ics-calendar-masonry-gutter"></div>

	<?php
	// Title and description
	if (!empty($ics_data['title'])) {
		?>
		<h2 class="ics-calendar-title"><?php echo wp_kses_post($ics_data['title']); ?></h2>
		<?php
	}
	if (!empty($ics_data['description'])) {
		?>
		<p class="ics-calendar-description"><?php echo wp_kses_post($ics_data['description']); ?></p>
		<?php
	}

	// Empty calendar message
	if (empty($ics_data['events']) || r34ics_is_empty_array($ics_data['events'])) {
		?>
		<p class="ics-calendar-error"><?php _e('No events found.', 'r34ics'); ?></p>
		<?php
	}

	// Display calendar
	else {

		// Actions before rendering calendar wrapper (can include additional template output)
		do_action('r34ics_display_calendar_before_wrapper', $view, $args, $ics_data);

		// Color code key
		if (empty($args['legendposition']) || $args['legendposition'] == 'above') {
			echo $R34ICS->color_key_html($args, $ics_data);
		}

		// Build monthly calendars
		$i = 0;
		$skip_i = 0;
		$multiday_events_used = array();
		$years = $ics_data['events'];

		// Reverse?
		if ($args['reverse']) { krsort($years); }

		foreach ((array)$years as $year => $months) {

			// Reverse?
			if ($args['reverse']) { krsort($months); }

			foreach ((array)$months as $month => $days) {
				$ym = $year . $month;

				// Is this month in range? If not, skip to the next
				if (!r34ics_month_in_range($ym, $ics_data)) { continue; }

				$m = intval($month);
				$month_label = ucwords(r34ics_date($args['formatmonthyear'], $m.'/1/'.$year));
				$month_label_shown = false;
				$month_uid = $ics_data['guid'] . '-' . $ym;

				// Build month's calendar
				if (isset($days)) {

					// Reverse?
					if ($args['reverse']) { krsort($days); }

					foreach ((array)$days as $day => $day_events) {

						// Pull out multi-day events and display them separately first
						foreach ((array)$day_events as $time => $events) {

							foreach ((array)$events as $event_key => $event) {

								// We're ONLY looking for multiday events right now
								if (empty($event['multiday'])) { continue; }

								// Give this instance its own unique ID, since multiple instances of a recurring event will have the same UID
								$multiday_instance_uid = $event['uid'] . '-' . $event['multiday']['start_date'];

								// Skip event if under the skip limit (but be sure to count it in $multiday_events_used!)
								if (!empty($args['skip']) && $skip_i < $args['skip']) {
									if (!in_array($multiday_instance_uid, $multiday_events_used)) {
										$multiday_events_used[] = $multiday_instance_uid;
										$skip_i++;
									}
									continue;
								}

								// Have we used this event yet?
								if (!in_array($multiday_instance_uid, $multiday_events_used)) {

									// Format date/time for header
									/*
									Version 9.6.5.1 revises the change from version 9.6.3.2:
									Restructured into MM/DD/YYYY format because, for an unknown reason,
									both wp_date() and r34ics_date() are shifting these back by 1 day if
									in YYYYMMDD format.
									*/
									$md_start = !empty($event['multiday']['start_date'])
										? r34ics_date($date_format,
											substr($event['multiday']['start_date'],4,2) . '/' .
											substr($event['multiday']['start_date'],6,2) . '/' .
											substr($event['multiday']['start_date'],0,4)
										)
										: '';
									$md_end = !empty($event['multiday']['end_date'])
										? r34ics_date($date_format,
											substr($event['multiday']['end_date'],4,2) . '/' .
											substr($event['multiday']['end_date'],6,2) . '/' .
											substr($event['multiday']['end_date'],0,4)
										)
										: '';
									if ($time != 'all-day') {
										$md_start .= ' <small class="time-inline">' . r34ics_time_format($event['multiday']['start_time']) . '</small>';
										$md_end .= ' <small class="time-inline">' . r34ics_time_format($event['multiday']['end_time']) . '</small>';
									}

									$day_label = $md_start . ' &#8211; ' . $md_end;
									$day_uid = $ics_data['guid'] . '-' . r34ics_uid();
									?>
									<div class="ics-calendar-date-wrapper ics-calendar-masonry-grid-item" data-date="<?php echo esc_attr($day_label); ?>">
										<h4 class="ics-calendar-date" id="<?php echo esc_attr($day_uid); ?>"><?php echo wp_kses_post($day_label); ?></h4>
										<dl class="events" aria-labelledby="<?php echo esc_attr($day_uid); ?>">

											<?php
											$has_desc = r34ics_has_desc($args, $event);

											?><dd class="<?php echo r34ics_event_css_classes($event, $time, $args); ?>" data-feed-key="<?php echo intval($event['feed_key']); ?>"<?php
												if (!empty($ics_data['colors'][$event['feed_key']]['base'])) { echo ' data-feed-color="' . esc_attr($ics_data['colors'][$event['feed_key']]['base']) . '"'; }
												if (!empty($event['categories'])) { echo ' data-categories="' . esc_attr($event['categories']) . '"'; }
											?>>
												<?php
												// Event label (title)
												echo $R34ICS->event_label_html($args, $event, (!empty($has_desc) ? array('has_desc') : null));

												// Sub-label
												echo $R34ICS->event_sublabel_html($args, $event, null);

												// Description/Location/Organizer
												echo $R34ICS->event_description_html($args, $event, null, $has_desc);
												?>
											</dd><?php

											// We've now used this event
											$multiday_events_used[] = $multiday_instance_uid;
											$i++;
											if (!empty($args['count']) && $i >= intval($args['count'])) {
												echo '</dl></div></article>';
												break(5);
											}
											?>

										</dl>
									</div>
									<?php
								}

								// Remove event from array (to skip day if it only has multi-day events)
								unset($day_events[$time][$event_key]);

							}

							// Remove time from array if all of its events have been removed
							if (empty($day_events[$time])) { unset($day_events[$time]); }

						}

						// Skip day if all of its events were multi-day
						if (empty($day_events)) { continue; }

						// Loop through day events
						$all_day_indicator_shown = !empty($args['hidealldayindicator']);
						$day_label_shown = false;
						foreach ((array)$day_events as $time => $events) {
							foreach ((array)$events as $event) {

								// We're NOT looking for multiday events right now (these should all be removed above already)
								if (!empty($event['multiday'])) { continue; }

								// Skip event if under the skip limit
								if (!empty($args['skip']) && $skip_i < $args['skip']) {
									$skip_i++; continue;
								}

								// Display month label if needed
								if (empty($args['nomonthheaders']) && empty($month_label_shown)) {
									?>
									<h3 class="ics-calendar-label" id="<?php echo esc_attr($month_uid); ?>"><?php echo wp_kses_post($month_label); ?></h3>
									<?php
									$month_label_shown = true;
								}

								// Show day label if not yet displayed
								if (empty($day_label_shown)) {
									$day_label = r34ics_date($date_format, $month.'/'.$day.'/'.$year);
									$day_uid = $ics_data['guid'] . '-' . $year . $month . $day;
									?>
									<div class="ics-calendar-date-wrapper ics-calendar-masonry-grid-item" data-date="<?php echo esc_attr($day_label); ?>">
										<h4 class="ics-calendar-date" id="<?php echo esc_attr($day_uid); ?>"><?php echo wp_kses_post($day_label); ?></h4>
										<dl class="events" aria-labelledby="<?php echo esc_attr($day_uid); ?>">
									<?php
									$day_label_shown = true;
								}

								$has_desc = r34ics_has_desc($args, $event);
								if ($time == 'all-day') {

									if (empty($args['hidetimes']) && !$all_day_indicator_shown) {

										?><dt class="all-day-indicator" data-feed-key="<?php echo intval($event['feed_key']); ?>"<?php
											if (!empty($ics_data['colors'][$event['feed_key']]['base'])) { echo ' data-feed-color="' . esc_attr($ics_data['colors'][$event['feed_key']]['base']) . '"'; }
											if (!empty($event['categories'])) { echo ' data-categories="' . esc_attr($event['categories']) . '"'; }
										?>><?php _e('All Day', 'r34ics'); ?></dt><?php

										$all_day_indicator_shown = true;
									}

									?><dd class="<?php echo r34ics_event_css_classes($event, $time, $args); ?>" data-feed-key="<?php echo intval($event['feed_key']); ?>"<?php
										if (!empty($ics_data['colors'][$event['feed_key']]['base'])) { echo ' data-feed-color="' . esc_attr($ics_data['colors'][$event['feed_key']]['base']) . '"'; }
										if (!empty($event['categories'])) { echo ' data-categories="' . esc_attr($event['categories']) . '"'; }
									?>>
										<?php
										// Event label (title)
										echo $R34ICS->event_label_html($args, $event, (!empty($has_desc) ? array('has_desc') : null));

										// Sub-label
										echo $R34ICS->event_sublabel_html($args, $event, null);

										// Description/Location/Organizer
										echo $R34ICS->event_description_html($args, $event, null, $has_desc);
										?>
									</dd><?php

								}
								else {

									if (empty($args['hidetimes']) && !empty($event['start'])) {

										?><dt class="time" data-feed-key="<?php echo intval($event['feed_key']); ?>"<?php
											if (!empty($ics_data['colors'][$event['feed_key']]['base'])) { echo ' data-feed-color="' . esc_attr($ics_data['colors'][$event['feed_key']]['base']) . '"'; }
											if (!empty($event['categories'])) { echo ' data-categories="' . esc_attr($event['categories']) . '"'; }
										?>><?php
										echo wp_kses_post($event['start']);
										if (!empty($event['end']) && $event['end'] != $event['start']) {
											if (empty($args['showendtimes'])) {
												?>
												<span class="end_time show_on_hover">&#8211; <?php echo wp_kses_post($event['end']); ?></span>
												<?php
											}
											else {
												?>
												<span class="end_time">&#8211; <?php echo wp_kses_post($event['end']); ?></span>
												<?php
											}
										}
										?></dt><?php

									}

									?><dd class="<?php echo r34ics_event_css_classes($event, $time, $args); ?>" data-feed-key="<?php echo intval($event['feed_key']); ?>"<?php
										if (!empty($ics_data['colors'][$event['feed_key']]['base'])) { echo ' data-feed-color="' . esc_attr($ics_data['colors'][$event['feed_key']]['base']) . '"'; }
										if (!empty($event['categories'])) { echo ' data-categories="' . esc_attr($event['categories']) . '"'; }
									?>>
										<?php
										// Event label (title)
										echo $R34ICS->event_label_html($args, $event, (!empty($has_desc) ? array('has_desc') : null));

										// Sub-label
										echo $R34ICS->event_sublabel_html($args, $event, null);

										// Description/Location/Organizer
										echo $R34ICS->event_description_html($args, $event, null, $has_desc);
										?>
									</dd><?php

								}
								$i++;
								if (!empty($args['count']) && $i >= intval($args['count'])) {
									if (!empty($day_label_shown)) { echo '</dl></div></article>'; }
									break(5);
								}
							}
						}
						if (!empty($day_label_shown)) {
							?>
								</dl>
							</div>
							<?php
						}
					}
				}
			}
		}

		// Color code key
		if (!empty($args['legendposition']) && $args['legendposition'] == 'below') {
			echo $R34ICS->color_key_html($args, $ics_data);
		}

		// Actions after rendering calendar wrapper (can include additional template output)
		do_action('r34ics_display_calendar_after_wrapper', $view, $args, $ics_data);

	}
	?>

</div>
