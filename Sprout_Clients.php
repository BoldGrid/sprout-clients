<?php


/**
 * A fundamental class from which all other classes in the plugin should be derived.
 * The purpose of this class is to hold data useful to all classes.
 * @package SI
 */

if ( ! defined( 'SC_FREE_TEST' ) ) {
	define( 'SC_FREE_TEST', false ); }

if ( ! defined( 'SC_DEV' ) ) {
	define( 'SC_DEV', false ); }

abstract class Sprout_Clients {

	/**
	 * Application app-domain
	 */
	const APP_DOMAIN = 'sprout-clients';

	/**
	 * Application text-domain
	 */
	const TEXT_DOMAIN = 'sprout-clients';
	/**
	 * Application text-domain
	 */
	const PLUGIN_URL = 'https://sproutinvoices.com/sprout-clients/';
	/**
	 * Current version. Should match sprout-invoices.php plugin version.
	 */
	const SC_VERSION = '3.2.2';
	/**
	 * DB Version
	 */
	const DB_VERSION = 1;
	/**
	 * Application Name
	 */
	const PLUGIN_NAME = 'Sprout Clients';
	const PLUGIN_FILE = SC_PLUGIN_FILE;
	/**
	 * SC_DEV constant within the wp-config to turn on SI debugging
	 * <code>
	 * define( 'SC_DEV', TRUE/FALSE )
	 * </code>
	 */
	const DEBUG = SC_DEV;

	/**
	 * A wrapper around WP's __() to add the plugin's text domain
	 *
	 * @param string  $string
	 * @return string|void
	 */
	public static function __( $string ) {
		return __( apply_filters( 'sc_string_'.sanitize_title( $string ), $string ), self::TEXT_DOMAIN );
	}

	/**
	 * A wrapper around WP's _e() to add the plugin's text domain
	 *
	 * @param string  $string
	 * @return void
	 */
	public static function _e( $string ) {
		return _e( apply_filters( 'sc_string_'.sanitize_title( $string ), $string ), self::TEXT_DOMAIN );
	}

	/**
	 * Wrapper around esc_attr__
	 * @param  string $string
	 * @return
	 */
	public static function esc__( $string ) {
		return esc_attr__( $string, self::TEXT_DOMAIN );
	}

	/**
	 * Wrapper around esc_attr__
	 * @param  string $string
	 * @return
	 */
	public static function esc_e( $string ) {
		return esc_attr_e( $string, self::TEXT_DOMAIN );
	}
}
