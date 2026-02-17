<?php

/**
 * Clients Controller
 *
 *
 * @package Sprout_Clients
 * @subpackage Clients
 */
class SC_Clients_AJAX extends SC_Clients {
	const SUBMISSION_NONCE = 'sc_client_submission';

	public static function init() {

		if ( is_admin() ) {

			// AJAX
			add_action( 'wp_ajax_sa_create_client',  array( __CLASS__, 'maybe_create_client' ), 5, 0 );
			add_action( 'wp_ajax_sa_create_user',  array( __CLASS__, 'maybe_create_user' ), 5, 0 );

			add_action( 'wp_ajax_sc_associate_user',  array( __CLASS__, 'maybe_associate_or_remove_user' ), 10, 0 );

			add_action( 'wp_ajax_sa_client_associated_users_metabox',  array( __CLASS__, 'associated_users_meta_box_view' ), 10, 0 );

			add_action( 'wp_ajax_sc_change_client_type',  array( __CLASS__, 'maybe_change_client_type' ), 10, 0 );

			add_action( 'wp_ajax_sc_edit_client_status',  array( __CLASS__, 'maybe_edit_client_status' ), 10, 0 );

			add_action( 'wp_ajax_sc_save_user_note',  array( __CLASS__, 'maybe_save_user_note' ), 10, 0 );

			// SI Compatibility
			remove_action( 'wp_ajax_sa_create_client',  array( 'SI_Clients', 'maybe_create_client' ), 10 );
			remove_action( 'wp_ajax_sa_create_user',  array( 'SI_Clients', 'maybe_create_user' ), 10 );
		}

	}

	/**
	 * AJAX submission from admin.
	 * @return json response
	 */
	public static function maybe_create_client() {
		// form maybe be serialized
		if ( isset( $_REQUEST['serialized_fields'] ) ) {
			foreach ( $_REQUEST['serialized_fields'] as $key => $data ) {
				$_REQUEST[ $data['name'] ] = $data['value'];
			}
		}

		if ( ! isset( $_REQUEST['sa_client_nonce'] ) ) {
			self::ajax_fail( 'Forget something?' ); }

		$nonce = $_REQUEST['sa_client_nonce'];
		if ( ! wp_verify_nonce( $nonce, self::SUBMISSION_NONCE ) ) {

			/**
			 * Sometimes the nonce is mixed with one that SI provides.
			 * This is a bit of a hack to check to see if it validates
			 * against SI before improperly failing.
			 */
			$compatibility_check_passed = false;
			if ( class_exists( 'SI_Clients' ) ) {
				if ( wp_verify_nonce( $nonce, SI_Clients::SUBMISSION_NONCE ) ) {
					$compatibility_check_passed = true;
				}
			}
			if ( ! $compatibility_check_passed ) {
				self::ajax_fail( 'Not going to fall for it!' );
			}
		}

		if ( ! current_user_can( 'publish_posts' ) ) {
			self::ajax_fail( 'User cannot create new posts!' ); }

		if ( ! isset( $_REQUEST['sa_client_name'] ) || '' === $_REQUEST['sa_client_name'] ) {
			self::ajax_fail( 'A company name is required' );
		}

		$user_id = 0;
		// Attempt to create a user
		if ( isset( $_REQUEST['sa_client_email'] ) && '' !== $_REQUEST['sa_client_email'] ) {
			$email = sanitize_email( wp_unslash( $_REQUEST['sa_client_email'] ) );
			$user_args = array(
				'user_login' => $email,
				'display_name' => isset( $_REQUEST['sa_client_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sa_client_name'] ) ) : $email,
				'user_pass' => wp_generate_password(), // random password
				'user_email' => $email,
				'first_name' => isset( $_REQUEST['sa_client_first_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sa_client_first_name'] ) ) : '',
				'last_name' => isset( $_REQUEST['sa_client_last_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sa_client_last_name'] ) ) : '',
				'user_url' => isset( $_REQUEST['sa_client_website'] ) ? esc_url_raw( wp_unslash( $_REQUEST['sa_client_website'] ) ) : '',
			);
			$user_id = self::create_user( $user_args );
		}

		// Create the client
		$address = array(
			'street' => isset( $_REQUEST['sa_client_street'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sa_client_street'] ) ) : '',
			'city' => isset( $_REQUEST['sa_client_city'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sa_client_city'] ) ) : '',
			'zone' => isset( $_REQUEST['sa_client_zone'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sa_client_zone'] ) ) : '',
			'postal_code' => isset( $_REQUEST['sa_client_postal_code'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sa_client_postal_code'] ) ) : '',
			'country' => isset( $_REQUEST['sa_client_country'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sa_client_country'] ) ) : '',
		);
		$args = array(
			'company_name' => isset( $_REQUEST['sa_client_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sa_client_name'] ) ) : '',
			'website' => isset( $_REQUEST['sa_client_website'] ) ? esc_url_raw( wp_unslash( $_REQUEST['sa_client_website'] ) ) : '',
			'address' => $address,
			'user_id' => $user_id,
		);
		$client_id = Sprout_Client::new_client( $args );

		$response = array(
				'id' => $client_id,
				'title' => get_the_title( $client_id ),
			);

		header( 'Content-type: application/json' );
		if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
		echo wp_json_encode( $response );
		exit();
	}

	/**
	 * AJAX submission
	 * @return
	 */
	public static function maybe_create_user() {
		$response = array();

		// form maybe be serialized
		if ( isset( $_REQUEST['serialized_fields'] ) ) {
			foreach ( $_REQUEST['serialized_fields'] as $key => $data ) {
				$_REQUEST[ $data['name'] ] = $data['value'];
			}
		}

		if ( ! isset( $_REQUEST['sa_user_nonce'] ) ) {
			$response['error_message'] = __( 'Forget something?' , 'sprout-invoices' );
		}

		$nonce = $_REQUEST['sa_user_nonce'];
		if ( ! wp_verify_nonce( $nonce, self::SUBMISSION_NONCE ) ) {
			/**
			 * Sometimes the nonce is mixed with one that SI provides.
			 * This is a bit of a hack to check to see if it validates
			 * against SI before improperly failing.
			 */
			$compatibility_check_passed = false;
			if ( class_exists( 'SI_Clients' ) ) {
				if ( wp_verify_nonce( $nonce, SI_Clients::SUBMISSION_NONCE ) ) {
					$compatibility_check_passed = true;
				}
			}
			if ( ! $compatibility_check_passed ) {
				$response['error_message'] = __( 'Not going to fall for it!' , 'sprout-invoices' );
			}
		}

		if ( ! current_user_can( 'publish_posts' ) ) {
			$response['error_message'] = __( 'User cannot create new posts!' , 'sprout-invoices' );
		}

		// Attempt to create a user
		if ( ! isset( $_REQUEST['sa_user_email'] ) || '' === $_REQUEST['sa_user_email'] ) {
			$response['error_message'] = __( 'An e-mail is required' , 'sprout-invoices' );
		}

		$client = Sprout_Client::get_instance( $_REQUEST['sa_user_client_id'] );

		if ( ! is_a( $client, 'Sprout_Client' ) ) {
			$response['error_message'] = __( 'Client not found' , 'sprout-invoices' );
		}

		if ( isset( $response['error_message'] ) ) {
			wp_send_json_error( $response );
		}

		$email = sanitize_email( wp_unslash( $_REQUEST['sa_user_email'] ) );
		$user_args = array(
			'user_login' => $email,
			'display_name' => isset( $_REQUEST['sa_user_display_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sa_user_display_name'] ) ) : $email,
			'user_pass' => wp_generate_password(), // random password
			'user_email' => $email,
			'first_name' => isset( $_REQUEST['sa_user_first_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sa_user_first_name'] ) ) : '',
			'last_name' => isset( $_REQUEST['sa_user_last_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sa_user_last_name'] ) ) : '',
			'phone' => isset( $_REQUEST['sa_user_phone'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sa_user_phone'] ) ) : '',
			'dob' => isset( $_REQUEST['sa_user_dob'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sa_user_dob'] ) ) : '',
		);
		$user_id = self::create_user( $user_args );

		// Don't associated since the table will be refreshed.
		// $client->add_associated_user( $user_id );

		$response = array(
			'user_id' => $user_id,
			'client_id' => $client->get_id(),
			);
		wp_send_json_success( $response );
	}

	public static function maybe_change_client_type() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			self::ajax_fail( 'User cannot create new posts!' );
		}

		$nonce = $_REQUEST['security'];
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' );
		}

		if ( ! isset( $_REQUEST['client_id'] ) ) {
			self::ajax_fail( 'No client ID!' );
		}

		$client = Sprout_Client::get_instance( $_REQUEST['client_id'] );

		if ( ! is_a( $client, 'Sprout_Client' ) ) {
			self::ajax_fail( 'Client not found.' );
		}

		$client->set_type( $_REQUEST['type_id'] );
		print sc_get_type_select( $_REQUEST['client_id'] );
		exit();
	}

	public static function maybe_edit_client_status() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			self::ajax_fail( 'User cannot create new posts!' );
		}

		$nonce = $_REQUEST['security'];
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' );
		}

		if ( ! isset( $_REQUEST['client_id'] ) ) {
			self::ajax_fail( 'No client ID!' );
		}

		$client = Sprout_Client::get_instance( $_REQUEST['client_id'] );

		if ( ! is_a( $client, 'Sprout_Client' ) ) {
			self::ajax_fail( 'Client not found.' );
		}

		if ( 'add' === $_REQUEST['context'] ) {
			$return = $client->add_status( $_REQUEST['type_id'] );
		} else {
			$return = $client->remove_status( $_REQUEST['type_id'] );
		}

		wp_send_json( $return );
	}

	public static function maybe_associate_or_remove_user() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			self::ajax_fail( 'User cannot create new posts!' );
		}

		$nonce = $_REQUEST['security'];
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' );
		}

		if ( ! isset( $_REQUEST['client_id'] ) ) {
			self::ajax_fail( 'No client ID!' );
		}

		$client = Sprout_Client::get_instance( $_REQUEST['client_id'] );

		if ( ! is_a( $client, 'Sprout_Client' ) ) {
			self::ajax_fail( 'Client not found.' );
		}

		if ( ! isset( $_REQUEST['return'] ) || 'meta_box' !== $_REQUEST['return'] ) {
			return $user_id;
		}

		$user_id = $_REQUEST['user_id'];
		if ( ! $client->is_user_associated( $user_id ) ) {
			$client->add_associated_user( $user_id );
		} else {
			$client->remove_associated_user( $user_id );
		}

		ob_start();
		global $post;
		setup_postdata( $client->get_post() );
		print SC_Clients_Admin_Meta_Boxes::show_people_meta_box( $client->get_post() );
		$view = ob_get_clean();

		$response = array();
		$response['view'] = $view;
		wp_send_json( $response );
	}

	public static function maybe_save_user_note() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			self::ajax_fail( 'User cannot create new posts!' );
		}

		$nonce = $_REQUEST['security'];
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' );
		}

		if ( ! isset( $_REQUEST['user_id'] ) ) {
			self::ajax_fail( 'No user ID!' );
		}

		update_user_meta( $_REQUEST['user_id'], SC_Users::NOTE, $_REQUEST['note'] );

		$return = array();
		$return['response'] = __( 'Saved' , 'sprout-invoices' );
		wp_send_json( $return );
	}

	////////////////
	// AJAX View //
	////////////////

	/**
	 * Meta box view
	 * Abstracted to be called via AJAX
	 * @param int $client_id
	 *
	 */
	public static function associated_users_meta_box_view( $client_id = 0 ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			self::ajax_fail( 'User cannot create new posts!' ); }

		if ( ! $client_id && isset( $_REQUEST['client_id'] ) ) {
			$client_id = $_REQUEST['client_id'];
		}

		$client = Sprout_Client::get_instance( $client_id );

		if ( ! is_a( $client, 'Sprout_Client' ) ) {
			self::ajax_fail( 'Client not found.' );
		}

		global $post;
		setup_postdata( $client->get_post() );
		print SC_Clients_Admin_Meta_Boxes::show_people_meta_box( $client->get_post() );
		exit();
	}
}
