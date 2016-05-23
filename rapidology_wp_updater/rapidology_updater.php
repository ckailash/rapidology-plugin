<?php
/*
* Plugin Name: Rapidology By Leadpages Updater
* Plugin URI: http://www.rapidology.com?utm_campaign=rp-rp&utm_medium=wp-plugin-screen
* Version: 1.0
* Description: 100% Free List Building & Popup Plugin...With Over 100 Responsive Templates & 6 Different Display Types For Growing Your Email Newsletter
* Author: Rapidology
* Author URI: http://www.rapidology.com?utm_campaign=rp-rp&utm_medium=wp-plugin-screen
* License: GPLv2 or later
*/

/**
 *
 * This is a very simple plugin that should be deleted as soon as the update is finished.
 * Upon acceptance from the WordPress repo we need to rename the plugin folder from rapidology to rapidology-by-leadpages
 * to recieve updates from the repo
 *
 */

function rapidology_updater(){
	add_option('update_refresh', 0);
	$old_file = WP_PLUGIN_DIR.'/rapidology';
	$new_file = WP_PLUGIN_DIR.'/rapidology-by-leadpages';
	$update_folder = WP_PLUGIN_DIR.'/rapidology-by-leadpages.zip';
	//deactivate old rapidology
	if (empty($wp_filesystem)) {
		require_once(ABSPATH . '/wp-admin/includes/file.php');
		WP_Filesystem();
	}
	if(!file_exists($new_file)) {
		mkdir( $new_file, 0777 );
		$result = copy_dir( $old_file, $new_file, array( '.DS_Store', '.git', '.gitignore', '.idea' ) );
		deactivate_plugins( 'rapidology/rapidology.php' );
		delTree( $old_file );
		update_option('update_refresh', true);
	}
}

function activate_new_rapidology() {
	if ( ! is_plugin_active( 'rapidology-by-leadpages/rapidology.php' ) ) {
		$result = activate_plugin( 'rapidology-by-leadpages/rapidology.php' );
		if ( is_wp_error( $result ) ) {
			print_r( $result );
			die();
		}
		echo '<script>location.reload();</script>';
	}
}

//register_activation_hook( __FILE__, 'rapidology_updater' );
//add_action('admin_init', 'rapidology_updater');
//add_action('admin_init', 'activate_new_rapidology');

