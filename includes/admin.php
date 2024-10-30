<?php
/**
 * Add Script in Header
 *
 * @package WordPress
 */

add_action( 'admin_menu', 'chirpyweb_menu' );
add_action( 'admin_enqueue_scripts', 'chirpyweb_register_admin_scripts' );
add_action( 'wp_head', 'chirpyweb_script_in_header' );

/**
 * Register admin script.
 */
function chirpyweb_register_admin_scripts() {
	$js_base_path  = esc_url( CHIRPYWEB_BASE_URL ) . 'assets/js';
	$css_base_path = esc_url( CHIRPYWEB_BASE_URL ) . 'assets/css';
	wp_register_script( 'chirpyweb_admin', $js_base_path . '/chirpyweb_admin.js', array( 'jquery' ), true, true );
	wp_register_style( 'chirpyweb_admin_style', $css_base_path . '/chirpyweb_admin_style.css', array(), true );
	wp_register_style( 'google-font-material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons', '', true );
	$admin_email = get_bloginfo( 'admin_email' );
	$admin_user  = get_user_by( 'email', $admin_email )->data;
	$wp_site_url = esc_url( get_site_url() );
	$alert = false;
    if ( ! $admin_user ) {
		$alert = true;
		$email = wp_get_current_user()->data->user_email;
		$user  = get_user_by( 'email', $email )->data;
	} else {
		$email = $admin_email;
		$user  = get_user_by( 'email', $email )->data;
	}
	$user_info = wp_json_encode( $user );
	$cw_data   = array(
		'user_info'   => $user_info,
		'wp_site_url' => $wp_site_url,
		'wp_email'    => $email,
		'admin_email' => $admin_email,
		'alert'       => $alert,
	);
	wp_localize_script( 'chirpyweb_admin', 'ChirpyWeb', $cw_data );
}

/**
 * Add Script in Header
 */
function chirpyweb_script_in_header() {
	$file_name_main = plugin_dir_path( __FILE__ ) . '../templates/header_script.php';
	if ( file_exists( $file_name_main ) && is_readable( $file_name_main ) ) {
		require_once plugin_dir_path( __FILE__ ) . '../templates/header_script.php';
	}
}

/**
 * Load Admin Style
 */
function chirpyweb_cst_admin_style() { ?>
	<style type = "text/css">
	li.toplevel_page_cw-configuration img {
		width: 34px;
		height: 34px;
		padding-top: 0px !important;
		padding-left: 5px !important;
	}
	.toplevel_page_cw-configuration .wp-menu-name {
		margin-left: 10px;
		display: block;
	}
	</style>
	<?php
}
add_action( 'admin_footer', 'chirpyweb_cst_admin_style' );

/**
 * Load enqueue scripts
 *
 * @param array $hook hook.
 */
function chirpyweb_enqueue( $hook ) {
	chirpyweb_register_admin_scripts();
	wp_enqueue_style( 'chirpyweb_admin_style' );
	wp_enqueue_style( 'google-font-material-icons' );
	wp_enqueue_script( 'chirpyweb_admin' );
}

/**
 * Global Settings On reset
 */
$slip_izooto = filter_input( INPUT_POST, 'slipIzooto' );
if ( isset( $slip_izooto ) && is_admin() ) {
	if ( 'reset' === sanitize_text_field( $slip_izooto ) ) {
		delete_option( 'cw-settings' );
	}
}

if ( ! function_exists( 'chirpyweb_menu' ) ) {

	/**
	 * Admin Settings Menu
	 */
	function chirpyweb_menu() {
		$page_title = 'ChirpyWeb';
		$menu_title = 'ChirpyWeb';
		$capability = 'manage_options';
		$menu_slug  = 'cw-configuration';
		$icon_url   = CHIRPYWEB_BASE_URL . 'assets/images/menu-icon.png';
		$hook = add_menu_page( // phpcs:ignore
			$page_title,
			$menu_title,
			$capability,
			$menu_slug,
			function() {
				require_once plugin_dir_path( __FILE__ ) . '../templates/admin.php';
			},
			$icon_url
		);
		add_action( 'load-' . $hook, 'chirpyweb_enqueue' );
	}
}
