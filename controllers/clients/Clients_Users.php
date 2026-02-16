<?php

/**
 * Clients Controller
 *
 *
 * @package Sprout_Clients
 * @subpackage Clients
 */
class SC_Users extends SC_Clients {

	const DOB = 'sc_dob';
	const PHONE = 'sc_phone';
	const TWITTER = 'sc_twitter';
	const LINKEDIN = 'sc_linkedin';
	const NOTE = 'sc_note';

	public static function init() {
		add_action( 'show_user_profile', array( __CLASS__, 'user_profile_fields' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'user_profile_fields' ) );

		add_action( 'personal_options_update', array( __CLASS__, 'save_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save_profile_fields' ) );
	}

	public static function user_profile_fields( $user ) {
		$user_id = $user->ID;
		self::load_view( 'admin/user/profile_fields.php', array(
			'user' => $user,
			'phone' => self::get_users_phone( $user_id ),
			'twitter' => self::get_users_twitter( $user_id ),
			'linkedin' => self::get_users_linkedin( $user_id ),
			'dob' => self::get_users_dob( $user_id ),
			'note' => self::get_users_note( $user_id ),
			'clients' => Sprout_Client::get_clients_by_user( $user_id ),
			) );
	}

	public static function save_profile_fields( $user_id = 0 ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false; }

		// Sanitize user profile fields (input sanitization with wp_unslash).
		$dob = isset( $_POST['sc_dob'] ) ? sanitize_text_field( wp_unslash( $_POST['sc_dob'] ) ) : '';
		$phone = isset( $_POST['sc_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['sc_phone'] ) ) : '';
		$twitter = isset( $_POST['sc_twitter'] ) ? sanitize_text_field( wp_unslash( $_POST['sc_twitter'] ) ) : '';
		$linkedin = isset( $_POST['sc_linkedin'] ) ? esc_url_raw( wp_unslash( $_POST['sc_linkedin'] ) ) : '';
		$note = isset( $_POST['sc_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sc_note'] ) ) : '';

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

		// Validate date of birth format (YYYY-MM-DD or other common formats).
		if ( ! empty( $dob ) ) {
			// Try to parse the date to ensure it's valid.
			$date_formats = array( 'Y-m-d', 'm/d/Y', 'd/m/Y', 'Y/m/d', 'd-m-Y', 'm-d-Y' );
			$valid_date = false;
			foreach ( $date_formats as $format ) {
				$parsed_date = DateTime::createFromFormat( $format, $dob );
				if ( $parsed_date && $parsed_date->format( $format ) === $dob ) {
					$valid_date = true;
					break;
				}
			}
			if ( ! $valid_date ) {
				$dob = ''; // Invalid date format, don't save.
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

		update_user_meta( $user_id, self::DOB, $dob );
		update_user_meta( $user_id, self::PHONE, $phone );
		update_user_meta( $user_id, self::TWITTER, $twitter );
		update_user_meta( $user_id, self::LINKEDIN, $linkedin );
		update_user_meta( $user_id, self::NOTE, $note );
	}

	public static function get_users_phone( $user_id = 0 ) {
		if ( ! $user_id ) {
			return __( 'N/A' , 'sprout-invoices' );
		}
		return get_the_author_meta( self::PHONE, $user_id );
	}

	public static function get_users_dob( $user_id = 0 ) {
		if ( ! $user_id ) {
			return __( 'N/A' , 'sprout-invoices' );
		}
		return get_the_author_meta( self::DOB, $user_id );
	}

	public static function get_users_twitter( $user_id = 0 ) {
		if ( ! $user_id ) {
			return __( 'N/A' , 'sprout-invoices' );
		}
		return get_the_author_meta( self::TWITTER, $user_id );
	}

	public static function get_users_linkedin( $user_id = 0 ) {
		if ( ! $user_id ) {
			return __( 'N/A' , 'sprout-invoices' );
		}
		return get_the_author_meta( self::LINKEDIN, $user_id );
	}

	public static function get_users_note( $user_id = 0 ) {
		if ( ! $user_id ) {
			return;
		}
		return get_the_author_meta( self::NOTE, $user_id );
	}
}