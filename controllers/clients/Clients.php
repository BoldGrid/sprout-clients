<?php

/**
 * Clients Controller
 *
 *
 * @package Sprout_Clients
 * @subpackage Clients
 */
class SC_Clients extends SC_Controller {
	const SUBMISSION_NONCE = 'sc_client_submission';

	public static function init() {

		if ( is_admin() ) {
			// Help Sections
			add_action( 'admin_menu', array( __CLASS__, 'help_sections' ) );
		}

		// Prevent Client role admin access
		add_action( 'admin_init', array( __CLASS__, 'redirect_clients' ) );

		// Add default types and statuses
		add_action( 'admin_init', array( __CLASS__, 'add_defaults' ) );
		add_action( 'admin_menu', array( __CLASS__, 'remove_projects_menu' ) );

	}



	public static function remove_projects_menu() {
		remove_submenu_page( 'edit.php?post_type=sa_client', 'edit-tags.php?taxonomy=sc_client_type&amp;post_type=sa_client' );
		remove_submenu_page( 'edit.php?post_type=sa_client', 'edit-tags.php?taxonomy=sc_client_status&amp;post_type=sa_client' );
	}

	/**
	 * Redirect any clients away from the admin.
	 * @return
	 */
	public static function redirect_clients() {
		// Don't redirect admin-ajax.php requests
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! isset( $user->roles ) || ( ! empty( $user->roles ) && $user->roles[0] == 'sa_client' ) ) {
			wp_redirect( home_url() );
			exit();
		}
	}

	/**
	 * Add default statuses and types.
	 */
	public static function add_defaults() {
		$loaded = get_option( 'sc_load_client_defaults', 0 );
		if ( $loaded ) { // not anything new
			return;
		}

		$default_types = array(
			'associate' => 'Associate',
			'company' => 'Company',
			'client' => 'Client',
			'customer' => 'Customer',
			'dev' => 'Developer',
			'lead' => 'Lead',
			'prospect' => 'Prospect',
			'supplier' => 'Supplier',
			'vip' => 'VIP',
			);
		foreach ( $default_types as $slug => $name ) {
			$term = wp_insert_term( $name, Sprout_Client::TYPE_TAXONOMY, array( 'slug' => $slug ) );
		}

		$default_statuses = array(
			'action-required' => 'Action Required',
			'archives' => 'Archived',
			'follow-up' => 'Follow up',
			'ignorable' => 'Ignorable',
			'important' => 'Important',
			'new' => 'New',
			'stale' => 'Stale',
			'waiting' => 'Waiting',
			);
		foreach ( $default_statuses as $slug => $name ) {
			wp_insert_term( $name, Sprout_Client::STATUS_TAXONOMY, array( 'slug' => $slug ) );
		}

		update_option( 'sc_load_client_defaults', self::SC_VERSION );

	}

	//////////////
	// Utility //
	//////////////

	/**
	 * Maybe create a user if one is not already created.
	 * @param  array  $args
	 * @return $user_id
	 */
	public static function create_user( $args = array() ) {
		$defaults = array(
			'user_login' => '',
			'user_name' => '',
			'user_pass' => '',
			'user_email' => '',
			'first_name' => '',
			'last_name' => '',
			'user_url' => '',
			'phone' => '',
			'dob' => '',
			'role' => Sprout_Client::USER_ROLE,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		// check if the user already exists.
		if ( $user = get_user_by( 'email', $parsed_args['user_email'] ) ) {
			return $user->ID;
		}

		$user_id = wp_insert_user( $parsed_args );

		update_user_meta( $user_id, SC_Users::DOB, $parsed_args['dob'] );
		update_user_meta( $user_id, SC_Users::PHONE, $parsed_args['phone'] );

		do_action( 'si_user_created', $user_id, $parsed_args );
		return $user_id;
	}


	////////////
	// Forms //
	////////////

	public static function form_fields( $required = true, $client = 0 ) {
		$fields = array();
		$fields['name'] = array(
			'weight' => 1,
			'label' => __( 'Contact Name' , 'sprout-invoices' ),
			'type' => 'text',
			'required' => $required, // always necessary
			'default' => '',
		);

		if ( ! $client ) {
			$fields['email'] = array(
				'weight' => 3,
				'label' => __( 'Email' , 'sprout-invoices' ),
				'type' => 'text',
				'required' => $required,
				'description' => __( 'This e-mail will be used to create a new client user. Leave blank if associating an existing user.' , 'sprout-invoices' ),
				'default' => '',
			);
		}

		$fields['website'] = array(
			'weight' => 120,
			'label' => __( 'Website' , 'sprout-invoices' ),
			'type' => 'text',
			'required' => $required,
			'default' => ( $client ) ? $client->get_website() : '',
			'placeholder' => 'http://',
		);

		$fields['nonce'] = array(
			'type' => 'hidden',
			'value' => wp_create_nonce( self::SUBMISSION_NONCE ),
			'weight' => 10000,
		);

		$fields = array_merge( $fields, self::get_standard_address_fields( $required ) );

		// Don't add fields to add new clients when the client exists
		if ( $client ) {
			unset( $fields['first_name'] );
			unset( $fields['last_name'] );
		}

		$fields = apply_filters( 'si_client_form_fields', $fields );
		uasort( $fields, array( __CLASS__, 'sort_by_weight' ) );
		return $fields;
	}

	public static function comms_fields( $required = true, $client = 0 ) {
		$fields = array();

		$fields['phone'] = array(
			'weight' => 10,
			'label' => __( 'Phone' , 'sprout-invoices' ),
			'type' => 'text',
			'required' => $required,
			'default' => ( $client ) ? $client->get_phone() : '',
			'placeholder' => '+18051234242',
		);

		$skype_button = '';
		if ( $client && $client->get_skype() ) {
			$skype_button = sprintf( '&nbsp;<script type="text/javascript" src="http://www.skypeassets.com/i/scom/js/skype-uri.js"></script>
				<div id="SkypeButton_Call_Sprout_Client">
					<script type="text/javascript">
					Skype.ui({
						"name": "call",
						"element": "SkypeButton_Call_Sprout_Client",
						"participants": ["%s"],
						"imageSize": 12
					});
					</script>
				</div>', $client->get_skype() );
		}

		$fields['skype'] = array(
			'weight' => 15,
			'label' => __( 'Skype' , 'sprout-invoices' ) . $skype_button,
			'type' => 'text',
			'required' => $required,
			'default' => ( $client ) ? $client->get_skype() : '',
			'placeholder' => 'sproutclients',
		);

		$twitter_button = '';
		if ( $client && $client->get_twitter() ) {
			$twitter_button = sprintf( '&nbsp;<a href="#si_client_twitter_feed"><span class="dashicons dashicons-twitter"></span></a>', esc_attr( $client->get_twitter() ) );
		}
		$fields['twitter'] = array(
			'weight' => 50,
			'label' => __( 'Twitter' , 'sprout-invoices' ) . $twitter_button,
			'type' => 'text',
			'required' => $required,
			'default' => ( $client ) ? $client->get_twitter() : '',
			'placeholder' => '@_sproutapps',
		);

		$fb_button = '';
		if ( $client && $client->get_facebook() ) {
			$fb_button = sprintf( '&nbsp;<a href="%1$s" target="_blank"><span class="dashicons dashicons-facebook"></span></a>', esc_url( $client->get_facebook() ) );
		}
		$fields['facebook'] = array(
			'weight' => 55,
			'label' => __( 'Facebook' , 'sprout-invoices' ) . $fb_button,
			'type' => 'text',
			'required' => $required,
			'default' => ( $client ) ? $client->get_facebook() : '',
			'placeholder' => 'https://www.facebook.com/sproutapps',
		);

		$linkedin_button = '';
		if ( $client && $client->get_linkedin() ) {
			$linkedin_button = sprintf( '&nbsp;<a href="%1$s" target="_blank"><span class="dashicons dashicons-external"></span></a>', esc_url( $client->get_linkedin() ) );
		}
		$fields['linkedin'] = array(
			'weight' => 60,
			'label' => __( 'Linkedin' , 'sprout-invoices' ) . $linkedin_button,
			'type' => 'text',
			'required' => $required,
			'default' => ( $client ) ? $client->get_linkedin() : '',
			'placeholder' => 'https://linkedin.com/',
		);

		$fields['nonce'] = array(
			'type' => 'hidden',
			'value' => wp_create_nonce( self::SUBMISSION_NONCE ),
			'weight' => 10000,
		);

		$fields = apply_filters( 'si_client_comms_form_fields', $fields );
		uasort( $fields, array( __CLASS__, 'sort_by_weight' ) );
		return $fields;
	}

	public static function user_form_fields( $client_id = 0 ) {
		$fields = array();
		$fields['display_name'] = array(
			'weight' => 1,
			'label' => __( 'Full Name & Title' , 'sprout-invoices' ),
			'type' => 'text',
			'required' => false,
		);

		$fields['email'] = array(
			'weight' => 3,
			'label' => __( 'Email' , 'sprout-invoices' ),
			'type' => 'text',
			'required' => false, // required but the modal will block updates
			'default' => '',
		);

		$fields['first_name'] = array(
			'weight' => 50,
			'label' => __( 'First Name' , 'sprout-invoices' ),
			'placeholder' => __( 'First Name' , 'sprout-invoices' ),
			'type' => 'text',
			'required' => false,
		);
		$fields['last_name'] = array(
			'weight' => 51,
			'label' => __( 'Last Name' , 'sprout-invoices' ),
			'placeholder' => __( 'Last Name' , 'sprout-invoices' ),
			'type' => 'text',
			'required' => false,
		);

		$fields['phone'] = array(
			'weight' => 60,
			'label' => __( 'Phone' , 'sprout-invoices' ),
			'placeholder' => __( '+1 (805) 288-2222', 'sprout-invoices' ),
			'type' => 'text',
			'required' => false,
		);

		$fields['dob'] = array(
			'weight' => 65,
			'label' => __( 'Date of Birth' , 'sprout-invoices' ),
			'placeholder' => __( 'Date of Birth' , 'sprout-invoices' ),
			'type' => 'text',
			'required' => false,
		);

		$fields['client_id'] = array(
			'type' => 'hidden',
			'value' => $client_id,
			'weight' => 10000,
		);

		$fields['nonce'] = array(
			'type' => 'hidden',
			'value' => wp_create_nonce( self::SUBMISSION_NONCE ),
			'weight' => 10000,
		);

		$fields = apply_filters( 'si_user_form_fields', $fields );
		uasort( $fields, array( __CLASS__, 'sort_by_weight' ) );
		return $fields;
	}


	////////////////
	// Admin Help //
	////////////////

	public static function help_sections() {
		add_action( 'load-edit.php', array( __CLASS__, 'help_tabs' ) );
		add_action( 'load-post.php', array( __CLASS__, 'help_tabs' ) );
		add_action( 'load-post-new.php', array( __CLASS__, 'help_tabs' ) );
	}

	public static function help_tabs() {
		$post_type = '';

		$screen = get_current_screen();
		$screen_post_type = str_replace( 'edit-', '', $screen->id );
		if ( Sprout_Client::POST_TYPE === $screen_post_type ) {
			// get screen and add sections.
			$screen = get_current_screen();

			$screen->add_help_tab( array(
				'id' => 'edit-clients',
				'title' => __( 'Manage Clients' , 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p><p>%s</p>', __( 'The information here is used for estimates and invoices and includes settings to: Edit Company Name, Edit the company address, and Edit their website url.' , 'sprout-invoices' ), __( '<b>Important note:</b> when clients are created new WordPress users are also created and given the “client” role. Creating users will allow for future functionality, i.e. client dashboards.' , 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'associate-users',
				'title' => __( 'Associated Users' , 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p>', __( 'When clients are created a WP user is created and associated and clients are not limited to a single user. Not limited a client to a single user allows for you to have multiple points of contact at/for a company/client. Example, the recipients for sending estimate and invoice notifications are these associated users.' , 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'client-history',
				'title' => __( 'Client History' , 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p>', __( 'Important points are shown in the client history and just like estimate and invoices private notes can be added for only you and other team members to see.' , 'sprout-invoices' ) ),
			) );

			$screen->add_help_tab( array(
				'id' => 'client-invoices',
				'title' => __( 'Invoices and Estimates' , 'sprout-invoices' ),
				'content' => sprintf( '<p>%s</p>', __( 'All invoices and estimates associated with the client are shown below the associated users option. This provides a quick way to jump to the record you need to see.' , 'sprout-invoices' ) ),
			) );

			$screen->set_help_sidebar(
				sprintf( '<p><strong>%s</strong></p>', __( 'For more information:' , 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/knowledgebase/sprout-invoices/clients/', __( 'Documentation' , 'sprout-invoices' ) ) .
				sprintf( '<p><a href="%s" class="button">%s</a></p>', 'https://sproutapps.co/support/', __( 'Support' , 'sprout-invoices' ) )
			);
		}
	}
}
