<?php
/**
 * Plugin name: ChirpyWeb - Web Push Notifications
 * Plugin URI: https://wordpress.org/plugins/chirpyweb
 * Description: ChirpyWeb supports push notifications on all browsers including Chrome, Firefox, Safari, Microsoft Edge, Opera and many more.
 * Author: ChirpyWeb
 * Author URI: https://chirpyweb.com/
 * Text Domain: chirpyweb
 * Version: 1.4
 * License: GPL v2 or late
 *
 * @package WordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'CHIRPYWEB_BASE_URL' ) ) {
	define( 'CHIRPYWEB_BASE_URL', plugin_dir_url( __FILE__ ) );
}
define( 'CHIRPYWEBVERSION', '1.2' );

if ( defined( 'CW_TEST_MODE' ) && CW_TEST_MODE ) {
	define( 'CHIRPY_WP_API', 'https://test-api.chirpyweb.com/v1/' );
} else {
	define( 'CHIRPY_WP_API', 'https://admin.chirpyweb.com/v1/' );
}

// Include Plugin Files.
require_once plugin_dir_path( __FILE__ ) . 'includes/admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-chirpyweb-init.php';

/**
 * Plugin Activation code.
 */
function chirpyweb_activate() {
		$wpurl    = get_bloginfo( 'wpurl' );
		$email    = get_bloginfo( 'admin_email' );
		$wpurl    = ( '' !== $wpurl ) ? $wpurl : '';
		$email    = ( '' !== $email ) ? $email : '';
		$wpurl    = esc_url_raw( $wpurl );
		$url      = add_query_arg(
			array(
				'ref'              => 'wp',
				'act'              => 'install',
				'url'              => rawurlencode( $wpurl ),
				'email'            => rawurlencode( sanitize_email( $email ) ),
				'chirpywebversion' => CHIRPYWEBVERSION,
			),
			CHIRPY_WP_API
		);
		$response = Chirpyweb_Init::chirpyweb_wp_remote_get( $url );
}
register_activation_hook( __FILE__, 'chirpyweb_activate' );

/**
 * Plugin Deactivation code.
 */
function chirpyweb_deactivate() {
	$wpurl        = get_bloginfo( 'wpurl' );
		$email    = get_bloginfo( 'admin_email' );
		$wpurl    = ( '' !== $wpurl ) ? $wpurl : '';
		$email    = ( '' !== $email ) ? $email : '';
		$wpurl    = esc_url_raw( $wpurl );
		$url      = add_query_arg(
			array(
				'ref'              => 'wp',
				'act'              => 'uninstall',
				'url'              => rawurlencode( $wpurl ),
				'email'            => rawurlencode( sanitize_email( $email ) ),
				'chirpywebversion' => CHIRPYWEBVERSION,
			),
			CHIRPY_WP_API
		);
		$response = Chirpyweb_Init::chirpyweb_wp_remote_get( $url );
		delete_option( 'cw-script' );
}
register_deactivation_hook( __FILE__, 'chirpyweb_activate' );

/**
 * Load plugin Text Domain
 */
function chirpyweb_initialize_plugin() {
	load_plugin_textdomain( 'chirpyweb', false, plugin_dir_path( __FILE__ ) . '/languages' );
}
add_action( 'plugins_loaded', 'chirpyweb_initialize_plugin' );