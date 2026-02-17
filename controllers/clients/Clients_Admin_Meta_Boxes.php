<?php

/**
 * Clients Controller
 *
 *
 * @package Sprout_Clients
 * @subpackage Clients
 */
class SC_Clients_Admin_Meta_Boxes extends SC_Clients {

	public static function init() {

		if ( is_admin() ) {

			// Meta boxes
			add_action( 'admin_init', array( __CLASS__, 'register_meta_boxes' ), 5 );
			add_action( 'admin_init', array( __CLASS__, 'register_dynamic_meta_boxes' ) );
			// add_filter( 'wp_insert_post_data', array( __CLASS__, 'update_post_data' ), 100, 2 );
			add_action( 'do_meta_boxes', array( __CLASS__, 'modify_meta_boxes' ) );
			add_action( 'edit_form_top', array( __CLASS__, 'name_box' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_twitter_widget_script' ) );

			remove_action( 'edit_form_top', array( 'SI_Clients', 'name_box' ) );

		}

	}

	/////////////////
	// Meta boxes //
	/////////////////

	/**
	 * Regsiter meta boxes for estimate editing.
	 *
	 * @return
	 */
	public static function register_meta_boxes() {
		// estimate specific
		$args = array(
			'si_client_users' => array(
				'title' => sc__( 'Associated Contacts' ),
				'show_callback' => array( __CLASS__, 'show_people_meta_box' ),
				'save_callback' => array( __CLASS__, 'save_meta_box_people' ),
				'context' => 'normal',
				'priority' => 'high',
				'save_priority' => 0,
				'weight' => 10,
			),
			'si_client_information' => array(
				'title' => sc__( 'Business Detail' ),
				'show_callback' => array( __CLASS__, 'show_information_meta_box' ),
				'save_callback' => array( __CLASS__, 'save_meta_box_client_information' ),
				'context' => 'normal',
				'priority' => 'high',
				'save_priority' => 0,
				'weight' => 15,
			),
			'si_client_submit' => array(
				'title' => 'Update',
				'show_callback' => array( __CLASS__, 'show_submit_meta_box' ),
				'save_callback' => array( __CLASS__, 'save_submit_meta_box' ),
				'context' => 'side',
				'priority' => 'high',
			),
			'si_client_communication' => array(
				'title' => sc__( 'Lead Communications' ),
				'show_callback' => array( __CLASS__, 'show_communication_meta_box' ),
				'save_callback' => array( __CLASS__, 'save_meta_box_client_communication' ),
				'context' => 'side',
				'priority' => 'high',
				'save_priority' => 0,
				'weight' => 15,
			),
			'si_client_notes' => array(
				'title' => sc__( 'Notes' ),
				'show_callback' => array( __CLASS__, 'show_client_notes_view' ),
				'save_callback' => array( __CLASS__, '_save_null' ),
				'context' => 'normal',
				'priority' => 'low',
				'weight' => 50,
			),
			'si_invoices' => array(
				'title' => 'Sprout Invoices',
				'show_callback' => array( __CLASS__, 'show_si_ad_meta_box' ),
				'save_callback' => array( __CLASS__, '_save_null' ),
				'context' => 'side',
				'priority' => 'low',
			),
		);
		if ( function_exists( 'sprout_invoices_load' ) ) {
			unset( $args['si_invoices'] );
		}
		do_action( 'sprout_meta_box', $args, Sprout_Client::POST_TYPE );
	}

	/**
	 * Regsiter meta boxes for estimate editing.
	 *
	 * @return
	 */
	public static function register_dynamic_meta_boxes() {
		if ( ! is_admin() || ! isset( $_GET['post'] ) || get_post_type( $_GET['post'] ) !== Sprout_Client::POST_TYPE ) {
			return;
		}
		// TODO disabled since the widget needs an id.
		//return;

		// estimate specific
		$args = array(
			'si_client_twitter_feed' => array(
				'title' => sc__( 'Twitter Feed' ),
				'show_callback' => array( __CLASS__, 'show_twitter_feed' ),
				'save_callback' => null,
				'context' => 'side',
				'priority' => 'high',
				'save_priority' => 0,
				'weight' => 500,
			),
		);
		do_action( 'sprout_meta_box', $args, Sprout_Client::POST_TYPE );
	}

	/**
	 * Remove publish box and add something custom for estimates
	 *
	 * @param string  $post_type
	 * @return
	 */
	public static function modify_meta_boxes( $post_type ) {
		if ( Sprout_Client::POST_TYPE === $post_type ) {
			remove_meta_box( 'submitdiv', null, 'side' );
		}
	}

	/**
	 * Add quick links
	 * @param  object $post
	 * @return
	 */
	public static function name_box( $post ) {
		if ( get_post_type( $post ) === Sprout_Client::POST_TYPE ) {
			$client = Sprout_Client::get_instance( $post->ID );
			self::load_view( 'admin/meta-boxes/clients/name', array(
					'client' => $client,
					'id' => $post->ID,
					'type' => $client->get_type(),
					'statuses' => $client->get_statuses(),
					'all_statuses' => sc_get_client_statuses(),
					'post_status' => $post->post_status,
			) );
		}
	}

	/**
	 * Show custom submit box.
	 * @param  WP_Post $post
	 * @param  array $metabox
	 * @return
	 */
	public static function show_submit_meta_box( $post, $metabox ) {
		$client = Sprout_Client::get_instance( $post->ID );

		$args = apply_filters( 'si_get_users_for_association_args', array( 'fields' => array( 'ID', 'user_email', 'display_name' ) ) );
		$users = get_users( $args );
		self::load_view( 'admin/meta-boxes/clients/submit', array(
				'id' => $post->ID,
				'client' => $client,
				'post' => $post,
				'associated_users' => $client->get_associated_users(),
				'users' => $users,
				'invoices' => $client->get_invoices(),
				'estimates' => $client->get_estimates(),
		), false );
	}


	/**
	 * People
	 * @param  object $post
	 * @return
	 */
	public static function show_people_meta_box( $post ) {
		if ( get_post_type( $post ) === Sprout_Client::POST_TYPE ) {
			$client = Sprout_Client::get_instance( $post->ID );
			$args = apply_filters( 'si_get_users_for_association_args', array( 'fields' => array( 'ID', 'user_email', 'display_name' ) ) );
			$users = get_users( $args );
			self::load_view( 'admin/meta-boxes/clients/associated-users', array(
					'client' => $client,
					'id' => $post->ID,
					'associated_users' => $client->get_associated_users(),
					'users' => $users,
			) );

			add_thickbox();

			// add the user creation modal
			$fields = self::user_form_fields( $post->ID );
			self::load_view( 'admin/meta-boxes/clients/create-user-modal', array( 'fields' => $fields ) );
		}
	}

	/**
	 * Information
	 * @param  object $post
	 * @return
	 */
	public static function show_information_meta_box( $post ) {
		if ( get_post_type( $post ) === Sprout_Client::POST_TYPE ) {
			$client = Sprout_Client::get_instance( $post->ID );
			self::load_view( 'admin/meta-boxes/clients/info', array(
					'client' => $client,
					'id' => $post->ID,
					'associated_users' => $client->get_associated_users(),
					'fields' => self::form_fields( false, $client ),
					'address' => $client->get_address(),
			) );
		}
	}

	/**
	 * Saving info meta
	 * @param  int $post_id
	 * @param  object $post
	 * @param  array $callback_args
	 * @return
	 */
	public static function save_meta_box_client_information( $post_id, $post, $callback_args ) {
		// name is updated in the title div
		$website = ( isset( $_POST['sa_metabox_website'] ) && '' !== $_POST['sa_metabox_website'] ) ? esc_url_raw( wp_unslash( $_POST['sa_metabox_website'] ) ) : '' ;

		$address = array(
			'street' => isset( $_POST['sa_metabox_street'] ) ? sanitize_text_field( wp_unslash( $_POST['sa_metabox_street'] ) ) : '',
			'city' => isset( $_POST['sa_metabox_city'] ) ? sanitize_text_field( wp_unslash( $_POST['sa_metabox_city'] ) ) : '',
			'zone' => isset( $_POST['sa_metabox_zone'] ) ? sanitize_text_field( wp_unslash( $_POST['sa_metabox_zone'] ) ) : '',
			'postal_code' => isset( $_POST['sa_metabox_postal_code'] ) ? sanitize_text_field( wp_unslash( $_POST['sa_metabox_postal_code'] ) ) : '',
			'country' => isset( $_POST['sa_metabox_country'] ) ? sanitize_text_field( wp_unslash( $_POST['sa_metabox_country'] ) ) : '',
		);

		$client = Sprout_Client::get_instance( $post_id );
		$client->set_website( $website );
		$client->set_address( $address );

		$user_id = 0;
		// Attempt to create a user
		if ( isset( $_POST['sa_metabox_email'] ) && '' !== $_POST['sa_metabox_email'] ) {
			$email = sanitize_email( wp_unslash( $_POST['sa_metabox_email'] ) );
			$user_args = array(
				'user_login' => $email,
				'display_name' => isset( $_POST['sa_metabox_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sa_metabox_name'] ) ) : $email,
				'user_pass' => wp_generate_password(), // random password
				'user_email' => $email,
				'first_name' => isset( $_POST['sa_metabox_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sa_metabox_first_name'] ) ) : '',
				'last_name' => isset( $_POST['sa_metabox_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sa_metabox_last_name'] ) ) : '',
				'user_url' => isset( $_POST['sa_metabox_website'] ) ? esc_url_raw( wp_unslash( $_POST['sa_metabox_website'] ) ) : '',
			);
			$user_id = self::create_user( $user_args );
		}

		if ( $user_id ) {
			$client->add_associated_user( $user_id );
		}
	}

	public static function update_post_data( $data = array(), $post = array() ) {
		if ( Sprout_Client::POST_TYPE === $post['post_type'] ) {
			$title = $post['post_title'];
			if ( isset( $_POST['sa_metabox_name'] ) && '' !== $_POST['sa_metabox_name'] ) {
				$title = $_POST['sa_metabox_name'];
			}
			// modify the post title
			$data['post_title'] = $title;
		}
		return $data;
	}

	/**
	 * Saving submit meta
	 * @param  int $post_id
	 * @param  object $post
	 * @param  array $callback_args
	 * @return
	 */
	public static function save_submit_meta_box( $post_id, $post, $callback_args ) {
		// nothing yet.
	}


	/**
	 * Show the history
	 *
	 * @param WP_Post $post
	 * @param array   $metabox
	 * @return
	 */
	public static function show_client_history_view( $post, $metabox ) {
		if ( 'auto-draft' === $post->post_status ) {
			printf( '<p>%s</p>', sc__( 'No history available.' ) );
			return;
		}
		$client = Sprout_Client::get_instance( $post->ID );
		self::load_view( 'admin/meta-boxes/clients/history', array(
				'id' => $post->ID,
				'post' => $post,
				'client' => $client,
				'history' => $client->get_history(),
		), false );
	}


	/**
	 * Show the notes
	 *
	 * @param WP_Post $post
	 * @param array   $metabox
	 * @return
	 */
	public static function show_client_notes_view( $post, $metabox ) {
		if ( 'auto-draft' === $post->post_status ) {
			printf( '<p>%s</p>', sc__( 'Save before creating any notes.' ) );
			return;
		}
		$client = Sprout_Client::get_instance( $post->ID );
		self::load_view( 'admin/meta-boxes/clients/notes', array(
				'id' => $post->ID,
				'post' => $post,
				'client' => $client,
		), false );
	}



	/**
	 * Information
	 * @param  object $post
	 * @return
	 */
	public static function show_communication_meta_box( $post ) {
		if ( get_post_type( $post ) === Sprout_Client::POST_TYPE ) {
			$client = Sprout_Client::get_instance( $post->ID );
			self::load_view( 'admin/meta-boxes/clients/communication', array(
					'client' => $client,
					'id' => $post->ID,
					'fields' => self::comms_fields( false, $client ),
					'website' => $client->get_website(),
			) );
		}
	}

	/**
	 * Saving communication meta
	 * @param  int $post_id
	 * @param  object $post
	 * @param  array $callback_args
	 * @return
	 */
	public static function save_meta_box_client_communication( $post_id, $post, $callback_args ) {
		// Sanitize communication fields (input sanitization with wp_unslash).
		$phone = ( isset( $_POST['sa_metabox_phone'] ) && '' !== $_POST['sa_metabox_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['sa_metabox_phone'] ) ) : '' ;
		$twitter = ( isset( $_POST['sa_metabox_twitter'] ) && '' !== $_POST['sa_metabox_twitter'] ) ? sanitize_text_field( wp_unslash( $_POST['sa_metabox_twitter'] ) ) : '' ;
		$skype = ( isset( $_POST['sa_metabox_skype'] ) && '' !== $_POST['sa_metabox_skype'] ) ? sanitize_text_field( wp_unslash( $_POST['sa_metabox_skype'] ) ) : '' ;
		$facebook = ( isset( $_POST['sa_metabox_facebook'] ) && '' !== $_POST['sa_metabox_facebook'] ) ? esc_url_raw( wp_unslash( $_POST['sa_metabox_facebook'] ) ) : '' ;
		$linkedin = ( isset( $_POST['sa_metabox_linkedin'] ) && '' !== $_POST['sa_metabox_linkedin'] ) ? esc_url_raw( wp_unslash( $_POST['sa_metabox_linkedin'] ) ) : '' ;

		// Validate Twitter handle format (1-15 chars, alphanumeric and underscores only).
		if ( ! empty( $twitter ) ) {
			$twitter = ltrim( $twitter, '@' ); // Remove @ if present.
			if ( ! preg_match( '/^[A-Za-z0-9_]{1,15}$/', $twitter ) ) {
				$twitter = ''; // Invalid format, don't save.
			}
		}

		// Validate LinkedIn URL format.
		if ( ! empty( $linkedin ) ) {
			// Check if it's a valid URL and contains linkedin.com domain.
			if ( ! filter_var( $linkedin, FILTER_VALIDATE_URL ) || false === stripos( $linkedin, 'linkedin.com' ) ) {
				$linkedin = ''; // Invalid LinkedIn URL, don't save.
			}
		}

		// Validate Facebook URL format.
		if ( ! empty( $facebook ) ) {
			// Check if it's a valid URL and contains facebook.com domain.
			if ( ! filter_var( $facebook, FILTER_VALIDATE_URL ) || false === stripos( $facebook, 'facebook.com' ) ) {
				$facebook = ''; // Invalid Facebook URL, don't save.
			}
		}

		// Validate phone number format (basic validation - at least 7 digits).
		if ( ! empty( $phone ) ) {
			// Remove common separators to count digits.
			$phone_digits = preg_replace( '/[^0-9]/', '', $phone );
			if ( strlen( $phone_digits ) < 7 ) {
				$phone = ''; // Too few digits, don't save.
			}
		}

		$client = Sprout_Client::get_instance( $post_id );
		$client->set_phone( $phone );
		$client->set_twitter( $twitter );
		$client->set_skype( $skype );
		$client->set_facebook( $facebook );
		$client->set_linkedin( $linkedin );

	}

	/**
	 * Enqueue Twitter widget script properly using WordPress enqueue system
	 * Only loads on client edit screens
	 *
	 * @param string $hook Current admin page hook
	 * @return void
	 */
	public static function enqueue_twitter_widget_script( $hook ) {
		// Only enqueue on post edit screens
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		// Only enqueue for client post type
		global $post;
		if ( ! isset( $post->post_type ) || Sprout_Client::POST_TYPE !== $post->post_type ) {
			return;
		}

		// Enqueue Twitter widget script (only once per page load)
		wp_enqueue_script(
			'twitter-widgets',
			'https://platform.twitter.com/widgets.js',
			array(),
			null,
			true
		);
	}

	public static function show_twitter_feed() {
		$client = Sprout_Client::get_instance( get_the_id() );
		if ( ! is_a( $client, 'Sprout_Client' ) ) {
			return;
		}
		if ( '' === $client->get_twitter() ) {
			_e( 'No twitter username assigned.' , 'sprout-invoices' );
			return;
		}
		// Normalize Twitter handle: trim whitespace and strip leading @
		$twitter_handle = ltrim( trim( $client->get_twitter() ), '@' );
		$twitter_url = esc_url( 'https://twitter.com/' . $twitter_handle );
		$twitter_widget_id = esc_attr( apply_filters( 'sc_twitter_widget_id', '492426361349234688' ) );
		$twitter_escaped = esc_attr( $twitter_handle );
		$twitter_text = esc_html( $twitter_handle );
		// Output only the HTML part - Twitter widget script is properly enqueued via enqueue_twitter_widget_script()
		printf( '<a class="twitter-timeline" href="%1$s" data-widget-id="%2$s" data-screen-name="%3$s">Tweets by %4$s</a>', $twitter_url, $twitter_widget_id, $twitter_escaped, $twitter_text );
	}

	public static function show_si_ad_meta_box() {
		printf( '<p class="description help_block"><a href="%s"><img src="%s" width="100%%" height="auto" /></a><br/>%s</p>', 'https://sproutinvoices.com/sprout-invoices/', SC_RESOURCES . 'admin/img/invoice.png', __( 'Check <a href="https://sproutinvoices.com/sprout-invoices/">Sprout Invoices</a> out when you have a chance, it works pretty awesome with Sprout Clients.', 'sprout-invoices' ) );
	}
}
