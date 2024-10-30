<?php
/**
 * Main class
 *
 * @package WordPress
 */

/**
 * Declare class `Chirpyweb_Init.
 */
class Chirpyweb_Init {

	/**
	 * Setting options
	 *
	 * @var array $options got global options
	 */
	private $options = array();
	/**
	 * Calling class construct.
	 */
	public function __construct() {
		$this->options_pre = 'cw_options_';
		$this->options_key = 'cw-settings';
		add_action( 'wp_ajax_error_alert', 'chirpyweb_error_alert' );
		add_action( 'admin_init', array( $this, 'chirpyweb_submit_data' ) );
		add_action( 'wp_loaded', array( $this, 'chirpyweb_revenue_cookie' ), 10, 1 );
		add_action( 'wp_loaded', array( $this, 'chirpyweb_woo_enable' ) );
		add_action( 'save_post_product', array( $this, 'chirpyweb__price_drop' ), 10, 1 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'chirpyweb_order_shipping' ), 10, 3 );
		add_action( 'woocommerce_cart_updated', array( $this, 'chirpyweb_cart_data' ), 10, 1 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'chirpyweb_cart_data' ), 10, 1 );
		add_action( 'woocommerce_update_cart_action_cart_updated', array( $this, 'chirpyweb_cart_data' ), 10, 1 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'chirpyweb_cart_data' ), 10, 1 );
		add_action( 'woocommerce_cart_item_restored', array( $this, 'chirpyweb_cart_data' ), 10, 1 );
		add_action( 'rest_api_init', array( $this, 'chiryweb_custom_product_api' ) );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'chirpyweb_checkout_create_order' ), 10, 2 );
		add_action( 'woocommerce_thankyou', array( $this, 'chirpyweb_order_after' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'chirpyweb_revenue_notification' ), 10, 1 );
		add_action( 'woocommerce_cart_is_empty', array( $this, 'chirpyweb_destroy_cart_session' ), 10, 1 );
	}
	/**
	 * Get all pre options.
	 */
	public function get_options_pre() {
		return $this->options_pre;
	}

	/**
	 * Get all plugin options.
	 *
	 * @param string $key key.
	 * @param bool   $fetch featch Default false.
	 *
	 * @return string
	 */
	public function chirpyweb_get_option( $key, $fetch = false ) {
		if ( false === $fetch ) {
			$transient_key  = $this->options_pre . $key;
			$transient_body = $this->chirpyweb_transient_get( $transient_key );
			if ( false !== $transient_body ) {
				$option = $transient_body;
			} else {
				$option        = get_option( $key );
				$transient_key = $this->options_pre . $key;
				$this->chirpyweb_transient_set( $transient_key, $option );
			}
		} else {
			$option = get_option( $key );
		}
		return $option;
	}

	/**
	 * Update plugin options.
	 *
	 * @param string $key key.
	 * @param string $option option.
	 * @param bool   $cache cache Default true.
	 *
	 * @return string
	 */
	public function chirpyweb_update_option( $key, $option, $cache = true ) {
		$resp = update_option( $key, $option );
		if ( false !== $resp && true === $cache ) {
			$transient_key = $this->options_pre . $key;
			$this->chirpyweb_transient_set( $transient_key, $option );
		}
		return $resp;
	}

	/**
	 * Add Plugin options.
	 *
	 * @param string $key key.
	 * @param array  $option option.
	 * @param bool   $cache cache Default true.
	 *
	 * @return string
	 */
	public function chirpyweb_add_option( $key, $option, $cache = true ) {
		$resp = add_option( $key, $option );
		if ( $resp && true === $cache ) {
			$transient_key = $this->options_pre . $key;
			$this->chirpyweb_transient_set( $transient_key, $option );
		}
		return $resp;
	}

	/**
	 * Get Remote Url.
	 *
	 * @param string $url URL.
	 *
	 * @return array
	 */
	public static function chirpyweb_wp_remote_get( $url ) {
		$response = wp_safe_remote_get( $url );
		return $response;
	}

	/**
	 * Empty Configuration.
	 */
	public function chirpyweb_empty_config() {
		$cw_op          = array();
		$cw_op['url']   = '';
		$cw_op['uid']   = '';
		$cw_op['pid']   = '';
		$cw_op['cdn']   = '';
		$cw_op['sw']    = '';
		$cw_op['gcm']   = '';
		$cw_op['token'] = '';
		return $cw_op;
	}

	/**
	 * Verify the Key based on response
	 *
	 * @param string $key key.
	 * @return array
	 */
	public function chirpyweb_request( $key ) {
		$key            = sanitize_text_field( $key );
		$transient_key  = 'cw_config_' . $key;
		$transient_body = $this->chirpyweb_transient_get( $transient_key );
		if ( false !== $transient_body ) { // if cached.
			$response = $transient_body;
		} else {
			$options  = array(
				'timeout' => 30,
				'body'    => $key,
			);
			$response = wp_safe_remote_post(
				CHIRPY_WP_API . 'wp/code-snippet/',
				array(
					'body' => array(
						'website_key' => $key,
					),
				)
			);
			if ( 201 === wp_remote_retrieve_response_code( $response ) ) {
				$this->chirpyweb_transient_set( $transient_key, $response, 60 * 60 );
			}
		}
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 201 !== $response_code ) {
			self::chirpyweb_add_action( 'admin_notices', 'chirpyweb_error_token' );
		}
		if ( is_array( $response ) ) {
			$header = $response['headers'];
			$body   = $response['body'];
		}
			return $body;
	}

	/**
	 * Genrate sw-js file
	 *
	 * @param array $key key.
	 * @return string
	 */
	public function chirpyweb_sw_request( $key ) {
		$key            = sanitize_text_field( $key );
		$transient_key  = 'cw_config_' . $key;
		$transient_body = $this->chirpyweb_transient_get( $transient_key );
		$options        = array(
			'timeout' => 30,
			'body'    => $key,
		);
		$response       = wp_safe_remote_post(
			CHIRPY_WP_API . 'wp/service-worker/',
			array(
				'body' => array(
					'website_key' => $key,
				),
			)
		);
		$response_code  = wp_remote_retrieve_response_code( $response );
		if ( 201 !== $response_code ) {
			self::chirpyweb_add_action( 'admin_notices', 'chirpyweb_error_token' );
		}
		if ( is_array( $response ) ) {
			$header = $response['headers'];
			$body   = $response['body'];
		}
		return $body;
	}

	/**
	 * Transition Function
	 *
	 * @param string $key key.
	 * @return string
	 */
	public function chirpyweb_transient_get( $key ) {
		$response = get_transient( $key );
		return $response;
	}

	/**
	 * Transition Fubction
	 *
	 * @param string $key key.
	 *
	 * @param string $value value.
	 *
	 * @param string $exp_time exp_time.
	 * @return string
	 */
	public function chirpyweb_transient_set( $key, $value, $exp_time = 43200 ) {
		$response = set_transient( $key, $value, $exp_time );
		return $response;
	}

	/**
	 * Delete Transition
	 *
	 * @param array $key key.
	 * @return string
	 */
	public function chirpyweb_transient_del( $key ) {
		$response = delete_transient( $key );
		return $response;
	}

	/**
	 * Invalid Token
	 */
	public static function invalid_token() {
		add_settings_error( 'chirpyweb-notice', esc_html__( 'invalid', 'chirpyweb' ), esc_html__( "Reach out to ChirpyWeb's chat support or email at mailto:support@chirpyweb.com for assistance." ), 'error' );
		settings_errors( 'chirpyweb-notice' );
	}

	/**
	 * Empty Token
	 */
	public static function empty_token() {
		add_settings_error( 'chirpyweb-notice', esc_html__( 'empty', 'chirpyweb' ), esc_html__( 'ID cannot be empty. Submit your  ID to activate web push. Contact ChirpyWeb for any queries.' ), 'error' );
		settings_errors( 'chirpyweb-notice' );
	}

	/**
	 * Success message oF tokan
	 */
	public static function chirpyweb_notice_messages() {
		add_settings_error( 'chirpyweb-notice', esc_html__( 'api-updated', 'chirpyweb' ), esc_html__( 'ChirpyWeb push has been successfully activated on your WordPress site.' ), 'updated' );
		settings_errors( 'chirpyweb-notice' );
	}

	/**
	 * Success message oF tokan
	 *
	 * @param array $handle handle.
	 *
	 * @param array $func_name func_name.
	 *
	 * @param array $static static.
	 */
	public static function chirpyweb_add_action( $handle, $func_name, $static = true ) {
		if ( true === $static ) {
			$func_name = __CLASS__ . '::' . $func_name;
		}
		add_action( $handle, $func_name );
	}

	/**
	 * Error message oF invalid tokan
	 */
	public static function chirpyweb_error_token() {
		add_settings_error( 'chirpyweb-notice', esc_html__( 'invalid', 'chirpyweb' ), esc_html__( 'Invalid Website Key, Please re-verify your Website Key.' ), 'error' );
	}

	/**
	 * Error alert.
	 */
	public function chirpyweb_error_alert() {
		$action = filter_input( INPUT_POST, 'action' );
		if ( 'error_alert' !== $action ) {
			echo false;
			wp_die();
		}
		$msg                = filter_input( INPUT_POST, 'message' );
		$endpoint           = CHIRPY_WP_API . '?ref=wp&act=error_alert&chirpywebversion=' . CHIRPYWEBVERSION;
		$email              = get_bloginfo( 'admin_email' );
		$user               = get_user_by( 'email', $email )->data;
		$user['user_email'] = $email;
		$user_info          = wp_json_encode( $user );
		$wp_site_url        = esc_url( get_site_url() );
		$endpoint           = add_query_arg(
			array(
				'email'       => rawurlencode( sanitize_email( $email ) ),
				'url'         => rawurlencode( $wp_site_url ),
				'userdetails' => rawurlencode( $user_info ),
				'message'     => $msg,
			),
			CHIRPY_WP_API
		);
		$status             = self::chirpyweb_wp_remote_get( $endpoint );
		$response           = array(
			'status'          => 'logged',
			'WordPress Email' => $email,
			'WordPress Site'  => $wp_site_url,
			'UserDetails'     => $user,
		);
		echo wp_json_encode( $status );
		wp_die();
	}

	/**
	 * Validate & submit form data.
	 */
	public function chirpyweb_submit_data() {
		$cw          = new Chirpyweb_Init();
		$tokensubmit = filter_input( INPUT_POST, 'tokensubmit' );
		if ( isset( $tokensubmit ) && sanitize_text_field( $tokensubmit ) ) {
			$err   = 0;
			$cw_op = array();
			$token = filter_input( INPUT_POST, 'token' );
			if ( empty( $token ) || '' === sanitize_text_field( $token ) ) {
				$err   = 1;
				$cw_op = $cw->chirpyweb_empty_config();
				$cw->chirpyweb_update_option( 'cw-settings', $cw_op );
				self::chirpyweb_add_action( 'admin_notices', 'empty_token' );
			}
			if ( 0 === $err ) {
				$get_json1 = $cw->chirpyweb_sw_request( sanitize_text_field( $token ) );
				$get_user1 = json_decode( $get_json1, true );
				$get_json  = $cw->chirpyweb_request( sanitize_text_field( $token ) );
				$get_user  = json_decode( $get_json, true );
				if ( isset( $get_user['errors'] ) || isset( $get_user['error'] ) ) {
					$err       = 1;
					$cw_op     = $cw->chirpyweb_empty_config();
					$code      = '';
					$file_name = ABSPATH . 'sw-cw.js';
					if ( file_exists( $file_name ) ) {
						unlink( $file_name );
					}
					self::chirpyweb_add_action( 'admin_notices', 'invalid_token' );
					$cw->chirpyweb_update_option( 'cw-settings', $cw_op );
					$cw->chirpyweb_update_option( 'cw-script', $code );
					$file_name_main   = plugin_dir_path( __FILE__ ) . '../templates/header_script.php';
					$file_exists_main = file_exists( $file_name_main );
					if ( $file_exists_main ) {
						unlink( $file_name_main );
					}
				} else {
					$code        = $get_user['code'];
					$code1       = $get_user1['code'];
					$file_name   = ABSPATH . 'sw-cw.js';
					$file_exists = file_exists( $file_name );
					if ( ! $file_exists ) {
						$create_file = touch( $file_name );
						if ( $create_file ) {
							file_put_contents( $file_name, $code1, FILE_APPEND); // phpcs:ignore
						}
					} else {
					    file_put_contents( $file_name, $code1); // phpcs:ignore
					}
					$cw_op['token'] = sanitize_text_field( $token );
					$cw->chirpyweb_update_option( 'cw-settings', $cw_op );
					$cw->chirpyweb_update_option( 'cw-script', $code );
					self::chirpyweb_add_action( 'admin_notices', 'chirpyweb_notice_messages' );
					$file_name_main   = plugin_dir_path( __FILE__ ) . '../templates/header_script.php';
					$file_exists_main = file_exists( $file_name_main );
					if ( ! $file_exists_main ) {
						$create_file_main = touch( $file_name_main );
						if ( $create_file_main ) {
							file_put_contents( $file_name_main, $code, FILE_APPEND | LOCK_EX ); // phpcs:ignore
						}
					} else {
					    file_put_contents( $file_name_main, $code); // phpcs:ignore
					}
				}
			}
		}
	}
	/**
	 * Woocommerce Enable API
	 *
	 * @param boolean $status status.
	 */
	public function chirpyweb_woo_enable( $status ) {
		$wpurl    = get_bloginfo( 'wpurl' );
		$settings = get_option( 'cw-settings' );
		if( !empty( $settings ) ) {
		$tokan    = $settings['token'];
		}
		global  $woocommerce;
		$currency = get_option('woocommerce_currency');
		if ( ( ! empty( $settings['token'] ) ) && ! empty( $currency ) ) {
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				$param    = array(
					'is_woo_commerce_enabled' => true,
					'currency'                => $currency,
				);
				$response = wp_safe_remote_post(
					CHIRPY_WP_API . 'wp/woo-com-enable/',
					array(
						'headers' => array(
							'x-website-key' => $tokan,
							'x-website-url' => $wpurl,
						),
						'body'    => $param,
					)
				);
			}
			$response_code = json_decode( wp_remote_retrieve_body( $response ) );

			if ( is_array( $response ) ) {
				$header = $response['headers'];
				$body   = $response['body'];
			}
			return $body;
		}
	}
	/**
	 * Price Drop
	 *
	 * @param string $product_id product_id.
	 */
	public function chirpyweb__price_drop( $product_id ) {
		global $product;
		$cw            = new Chirpyweb_Init();
		$settings      = get_option( 'cw-settings' );
		$tokan         = $settings['token'];
		$wpurl         = get_bloginfo( 'wpurl' );
		$product       = wc_get_product( $product_id );
		$sku           = $product->get_sku();
		$stock         = $product->get_stock_status();
		$stock_qty     = $product->get_stock_quantity();
		$product_price = $product->get_price();
		if ( $product->is_on_sale() ) {
			$product_price = $product->get_sale_price();
		} else {
			$product_price = $product->get_regular_price();
		}
		$permalink = $product->get_permalink();
		$name      = $product->get_name();
		if ( $product->is_on_sale() ) {
			$product_new_price = sanitize_text_field( $_POST['_sale_price'] ); // phpcs:ignore
		} else {
			$product_new_price = sanitize_text_field( $_POST['_regular_price'] ); // phpcs:ignore
		}
		$back_in_stock     = sanitize_text_field( $_POST['_stock_status'] ); // phpcs:ignore
		$back_in_stock_qty = sanitize_text_field( $_POST['_stock'] ); // phpcs:ignore
		$compare           = strcmp( $stock, $back_in_stock );
		$get_data          = $cw->chirpyweb_woo_enable( $tokan );
		$user_status       = json_decode( $get_data, true );
		$status            = $user_status['woo_commerce'];
		if ( $product_new_price < $product_price ) {
			$price = true;
		} else {
			$price = false;
		}
		if ( $stock !== $back_in_stock || $back_in_stock_qty > $stock_qty ) {
			$stock = true;
		} else {
			$stock = false;
		}
		if ( $status == 'activated' ) { // phpcs:ignore
			if ( $product_new_price < $product_price || $compare != '0' || $back_in_stock_qty > $stock_qty ) { // phpcs:ignore
				$temp     = array(
					'sku'              => $sku,
					'is_price_drop'    => $price,
					'is_back_in_stock' => $stock,
					'product_name'     => $name,
					'product_link'     => $permalink,
				);
				$response = wp_safe_remote_post(
					CHIRPY_WP_API . 'wp/product/',
					array(
						'headers' => array(
							'x-website-key' => $tokan,
							'x-website-url' => $wpurl,
						),
						'body'    => $temp,
					)
				);

				$response_code = json_decode( wp_remote_retrieve_body( $response ) );
				
				$price_drop    = $response_code->is_price_drop;
				$back_in_stock = $response_code->is_back_in_stock;
				if ( $price_drop == 1 || $back_in_stock == 1 ) {  // phpcs:ignore
					if ( ! isset( $_COOKIE['cw_price_drop'] ) ) {
						wc_setcookie( 'cw_price_drop', '1', strtotime( '+1 day' ) );
					}
				}
				$message = 'Price Drop & back in stock called sucessfully.';
				$file    = fopen( '../custom_logs.txt', 'a' );
				echo fwrite( $file, "\n" . date( 'Y-m-d h:i:s' ) . ' :: ' . $message ); // phpcs:ignore
				fclose( $file ); // phpcs:ignore
			}
			if ( isset( $_COOKIE['cw_price_drop'] ) ) {
				unset( $_COOKIE['cw_price_drop'] );
				// Clear token.
				setcookie( 'cw_price_drop', null, -1, COOKIEPATH );
			}
		}
	}


	/**
	 * Shipping Order
	 *
	 * @param string $order_id order_id.
	 *
	 * @param string $old_status old_status.
	 *
	 * @param string $new_status new_status.
	 */
	public function chirpyweb_order_shipping( $order_id, $old_status, $new_status ) {

		$cw         = new Chirpyweb_Init();
		$settings   = get_option( 'cw-settings' );
		$tokan      = $settings['token'];
		$wpurl      = get_bloginfo( 'wpurl' );
		$order      = wc_get_order( $order_id );
		$order_data = $order->get_data();
		$items      = $order->get_items();
		$sub_id     = get_post_meta( $order_id, 'subscription_id', true );
		foreach ( $items as $item_id => $item ) {
			$custom_field = wc_get_order_item_meta( $item_id, '_vi_wot_order_item_tracking_data', true );
			// If it is an array of values.
			$array           = json_decode( $custom_field );
			$tracking_number = $array[0]->tracking_number;
		}
		$link_address = $order->get_checkout_order_received_url();
		if ( $old_status != 'completed' && $new_status == 'completed' ) { // phpcs:ignore
			$temp = array(
				'subscription_id' => $sub_id,
				'order_url'       => $link_address,
				'website_key'     => $tokan,
				'tracking_id'     => $tracking_number,
			);

			$response      = wp_safe_remote_post(
				CHIRPY_WP_API . 'wp/shipping-order/',
				array(
					'headers'     => array(
						'x-website-key' => $tokan,
						'x-website-url' => $wpurl,
					),
					'body'        => $temp,
					'data_format' => 'body',
				)
			);
			$response_code = json_decode( wp_remote_retrieve_body( $response ) );
			$val           = $response_code->detail;
			if ( $val == 'order updated' || $val == 'order created' ) { // phpcs:ignore
				$message = "Shipping Order API called sucessfully with Subsciption ID= '" . $sub_id . "' & Tracking Number = '" . $tracking_number . "'";
				$file    = fopen( '../custom_logs.txt', 'a' );
				echo fwrite( $file, "\n" . date( 'Y-m-d h:i:s' ) . ' :: ' . $message ); // phpcs:ignore
				fclose( $file ); // phpcs:ignore
			}
		}
	}


	/**
	 * Abandoned Cart API
	 */
	public function chirpyweb_cart_data() {
			$cart_session  = WC()->session->get( 'cw_cart_token' );
			
			$wpurl         = get_bloginfo( 'wpurl' );
			$cart_contents = WC()->cart->get_cart_contents();
			$cw            = new Chirpyweb_Init();
			$settings      = get_option( 'cw-settings' );
			$tokan         = $settings['token'];
			$cart_url      = wc_get_cart_url();
			if ( isset( $_COOKIE['subscription_id'] ) ) {
			$sub_id        = sanitize_text_field( $_COOKIE['subscription_id'] );
			 // phpcs:ignore
			}
		if ( empty( $cart_session ) ) {
			$cart_session = WC()->session->set( 'cw_cart_token', bin2hex( random_bytes( 20 ) ) );
			$cart_session = WC()->session->get( 'cw_cart_token' );
			wc_setcookie( '_cw_cart_token', $cart_session, strtotime( '+1 month' ) );
		}
		if ( ! empty( $cart_session ) && ! empty( $cart_contents ) ) {
			$cart_contents = reset( $cart_contents );
			$total         = WC()->cart->get_subtotal();
			// Send update cart request.
					$temp     = array(
						'subscription_id' => $sub_id,
						'cart_id'         => $cart_session,
						'value'           => $total,
						'is_abandoned'    => true,
						 'is_empty_cart'  => false,
						'cart_url'        => $cart_url,
						'website_key'     => $tokan,
					);
					$response = wp_safe_remote_post(
						CHIRPY_WP_API . 'wp/cart/',
						array(
							'headers' => array(
								'x-website-key' => $tokan,
								'x-website-url' => $wpurl,
							),
							'body'    => $temp,
						)
					);
			$response_code    = json_decode( wp_remote_retrieve_body( $response ) );
			$cart_val         = $response_code->detail;
		}
		
		if( empty( $cart_contents ) ) {
		    $total         = WC()->cart->get_subtotal();
		    	$temp     = array(
						'subscription_id' => $sub_id,
						'cart_id'         => $cart_session,
						'value'           => $total,
						'is_abandoned'    => true,
						 'is_empty_cart'  => true,
						'cart_url'        => $cart_url,
						'website_key'     => $tokan,
					);
					$response = wp_safe_remote_post(
						CHIRPY_WP_API . 'wp/cart/',
						array(
							'headers' => array(
								'x-website-key' => $tokan,
								'x-website-url' => $wpurl,
							),
							'body'    => $temp,
						)
					);
			$response_code    = json_decode( wp_remote_retrieve_body( $response ) );
			$cart_val         = $response_code->detail;
		}
		
	}

		/**
		 * Destroy cart session data.
		 */
	public function chirpyweb_destroy_cart_session() {
		// Clear cart token.
		WC()->session->set( 'cw_cart_token', null );
		setcookie( '_cw_cart_token', null, -1, COOKIEPATH );
	}

		/**
		 * Checkout create order.
		 *
		 * @param object       $order Order Data.
		 * @param object|array $data Data.
		 */
	public function chirpyweb_checkout_create_order( $order, $data ) {
		$cw_cart_token = WC()->session->get( 'cw_cart_token' );
		$order->update_meta_data( 'cw_cart_token', $cw_cart_token );
	}


		/**
		 * WooCommerce order after get order data.
		 *
		 * @param int $order_id Order ID.
		 */
	public function chirpyweb_order_after( $order_id ) {
		// If check order ID exist OR not.
		if ( ! $order_id ) {
			return;
		}
		// Get cart data.
		$order = new WC_Order( $order_id );
		if ( isset( $_COOKIE['subscription_id'] ) ) {
			$sub_id = sanitize_text_field( $_COOKIE['subscription_id'] ); // phpcs:ignore
		}
		$wpurl    = get_bloginfo( 'wpurl' );
		$items    = $order->get_items();
		$total    = $order->get_total();
		$cw       = new Chirpyweb_Init();
		$settings = get_option( 'cw-settings' );
		$tokan    = $settings['token'];
		$cart_url = wc_get_cart_url();
		$cart_id = WC()->session->get( 'cw_cart_token' );
		update_post_meta( $order_id, 'subscription_id', $sub_id );
		$order->save;
		// Send update cart request.
			$temp      = array(
				'subscription_id' => $sub_id,
				'cart_id'         => $cart_id,
				'value'           => $total,
				'is_abandoned'    => false,
				'is_empty_cart'  =>  true,
				'cart_url'        => $cart_url,
				'website_key'     => $tokan,
			);
			$response  = wp_safe_remote_post(
				CHIRPY_WP_API . 'wp/cart/',
				array(
					'headers' => array(
						'x-website-key' => $tokan,
						'x-website-url' => $wpurl,
					),
					'body'    => $temp,
				)
			);
		$response_code = json_decode( wp_remote_retrieve_body( $response ) );
		// Clear token.
		setcookie( '_cw_cart_token', null, -1, COOKIEPATH );
	}


		/**
		 * Set Cookie For Revenue Notification
		 */
		public function chirpyweb_revenue_cookie() {
		    if( isset( $_GET['alert'] ) ) {
			$alert = $_GET['alert'];
		    }
		    if( isset( $_GET['id'] ) ) {
			$id  = $_GET['id'];
		    }
			if ( isset( $alert ) && isset( $id ) ) {
				if ( ! isset( $_COOKIE['cw_revenue_alert'] ) && ! isset( $_COOKIE['cw_revenue_id'] ) && !empty( $alert ) && !empty( $id ) ) {
					wc_setcookie( 'cw_revenue_alert', $alert, strtotime( '+1 day' ) );
					wc_setcookie( 'cw_revenue_id', $id, strtotime( '+1 day' ) );
				}
			}
		}

		/**
		 * Revenue Notification API
		 *
		 * @param int $order_id Order ID.
		 */
	public function chirpyweb_revenue_notification( $order_id ) {
		// If check order ID exist OR not.
		if ( ! $order_id ) {
			return;
		}
		if ( isset( $_COOKIE['cw_revenue_alert'] ) && isset( $_COOKIE['cw_revenue_id'] ) ) {
				$alert     = sanitize_text_field( $_COOKIE['cw_revenue_alert'] ); // phpcs:ignore
				$id        = sanitize_text_field( $_COOKIE['cw_revenue_id'] );  // phpcs:ignore
				$wpurl     = get_bloginfo( 'wpurl' );
				$order     = new WC_Order( $order_id );
				$total     = $order->get_total();
				$cw        = new Chirpyweb_Init();
				$settings  = get_option( 'cw-settings' );
				$tokan     = $settings['token'];
				$temp      = array(
					'id'      => $id,
					'alert'   => $alert,
					'revenue' => $total,
				);
				$response  = wp_safe_remote_post(
					CHIRPY_WP_API . 'wp/revenue/',
					array(
						'headers' => array(
							'x-website-key' =>  $tokan,
							'x-website-url' =>  $wpurl,
						),
						'body'    => $temp,
					)
				);
			$response_code = json_decode( wp_remote_retrieve_body( $response ) );
			$val           = $response_code->detail;
			if ( $val == 'updated' ) {  // phpcs:ignore
				unset( $_COOKIE['cw_revenue_alert'] );
				// Clear token.
				setcookie( 'cw_revenue_alert', null, -1, COOKIEPATH );
				unset( $_COOKIE['cw_revenue_id'] );
				// Clear token.
				setcookie( 'cw_revenue_id', null, -1, COOKIEPATH );
			}
		}
	}


	/**
	 * Register rest route
	 */
	public function chiryweb_custom_product_api() {
		register_rest_route(
			'cw/v1',
			'/product_data',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'chirypweb_product_data' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Search Projects.
	 *
	 * @param array $request request.
	 */
	public function chirypweb_product_data( $request ) {
		// Get sent data and set default value.
		$params = wp_parse_args(
			$request->get_params(),
			array(
				'sku' => '',
			)
		);

		$args        = array(
			'post_type'      => array( 'product' ),
			'post_status'    => array( 'publish' ),
			'posts_per_page' => '-1',
			'meta_query'     => array(
				array(
					'key'     => '_sku',
					'value'   => $params['sku'],
					'compare' => '=',
				),
			),
		);
		$product_id  = get_posts( $args );
		$pro_id      = $product_id[0]->ID;
		$pro_name    = $product_id[0]->post_title;
		$_product    = wc_get_product( $pro_id );
		$pro_price   = $_product->get_price();
		$pro_sku     = $_product->get_sku();
		$stock_qty   = $_product->get_stock_quantity();
		$image       = wp_get_attachment_image_src( get_post_thumbnail_id( $pro_id ), 'full' );
		$combine_val = array(
			'sku'           => $pro_sku,
			'product_name'  => $pro_name,
			'price'         => $pro_price,
			'stock'         => $stock_qty,
			'product_image' => $image[0],
		);
		return $combine_val;
	}
}
$cw = new Chirpyweb_Init();
