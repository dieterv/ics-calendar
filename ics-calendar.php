<?php
/*
Plugin Name: ICS Calendar
Plugin URI: https://icscalendar.com
Description: Embed a live updating iCal (ICS) feed in any page using a shortcode.
Version: 10.3.0
Author: Room 34 Creative Services, LLC
Author URI: https://room34.com
License: GPL2
Text Domain: r34ics
Domain Path: /i18n/languages/
*/

/*
  Copyright 2022 Room 34 Creative Services, LLC (email: info@room34.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/


// Don't load directly
if (!defined('ABSPATH')) { exit; }


// Load required files
require_once(plugin_dir_path(__FILE__) . 'class-r34ics.php');
require_once(plugin_dir_path(__FILE__) . 'functions.php');
require_once(plugin_dir_path(__FILE__) . 'r34ics-ajax.php');


// Backward compatibility for WP < 5.3
if (!function_exists('wp_date')) {
	require_once(plugin_dir_path(__FILE__) . 'compatibility.php');
}


// Initialize plugin functionality
add_action('plugins_loaded', 'r34ics_plugins_loaded');
function r34ics_plugins_loaded() {

	// Instantiate class
	global $R34ICS;
	$R34ICS = new R34ICS();
	
	// Load text domain
	load_plugin_textdomain('r34ics', false, basename(plugin_dir_path(__FILE__)) . '/i18n/languages/');
	
	// Conditionally run update function
	if (is_admin() && version_compare(get_option('r34ics_version'), @R34ICS::VERSION, '<')) { r34ics_update(); }
	
}


// Install
register_activation_hook(__FILE__, 'r34ics_install');
function r34ics_install() {

	// Flush rewrite rules
	flush_rewrite_rules();
	
	// Set version
	update_option('r34ics_version', @R34ICS::VERSION);

	// Admin notice with link to settings
	$notices = get_option('r34ics_deferred_admin_notices', array());
	$notices[] = array(
		'content' => '<p>' . sprintf(__('Thank you for installing %1$s. Before creating your first calendar shortcode, please visit your %2$sGeneral Settings%3$s page and verify that your site language, timezone and date/time format settings are correct. See our %4$sUser Guide%5$s for more information.', 'r34ics'), '<strong>ICS Calendar</strong>', '<a href="' . admin_url('options-general.php') . '">', '</a>', '<a href="https://icscalendar.com/general-wordpress-settings/" target="_blank">', '</a>') . '</p>',
		'status' => 'info'
	);
	update_option('r34ics_deferred_admin_notices', $notices);
	
}


// Updates
function r34ics_update() {

	// Version-specific updates (checking against old version number; must run *before* updating option)
	// v. 6.11.1 Renamed option from 'r34ics_transient_expiration' to 'r34ics_transient_expiration' so it's not a transient itself
	if (version_compare(@R34ICS::VERSION, '6.11.1', '<')) {
		$transients_expiration = get_option('r34ics_transient_expiration') ? get_option('r34ics_transient_expiration') : 3600;
		update_option('r34ics_transients_expiration', $transients_expiration);
		delete_option('r34ics_transient_expiration');
	}

	// Update version
	update_option('r34ics_version', @R34ICS::VERSION);
	
	// Purge calendar transients
	r34ics_purge_calendar_transients();
	
}


// Deferred install/update admin notices
add_action('admin_notices', 'r34ics_deferred_admin_notices');
function r34ics_deferred_admin_notices() {
	if ($notices = get_option('r34ics_deferred_admin_notices', array())) {
		foreach ((array)$notices as $notice) {
			echo '<div class="notice notice-' . esc_attr($notice['status']) . ' is-dismissible r34ics-admin-notice">' . wp_kses_post($notice['content']) . '</div>';
		}
	}
	delete_option('r34ics_deferred_admin_notices');
}


// Purge transients on certain option updates
add_action('update_option_timezone_string', 'r34ics_purge_calendar_transients');
