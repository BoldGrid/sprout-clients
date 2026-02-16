<?php
/*
 * Plugin Name: Sprout Clients
 * Plugin URI: https://sproutinvoices.com/sprout-clients/
 * Description: Contact relationship management to increase productivity in gaining clients and business relationships.
 * Author: Sprout Apps
 * Version: 3.2.3
 * Author URI: https://sproutinvoices.com
 * Text Domain: sprout-invoices
 * Domain Path: languages
*/

/**
 * SC directory
 */
define( 'SC_PATH', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );
/**
 * Plugin File
 */
define( 'SC_PLUGIN_FILE', __FILE__ );

/**
 * SC URL
 */
define( 'SC_URL', plugins_url( '', __FILE__ ) );
/**
 * URL to resources directory
 */
define( 'SC_RESOURCES', plugins_url( 'resources/', __FILE__ ) );

/**
 * Minimum supported verscon of WordPress
 */
define( 'SC_SUPPORTED_WP_VERSION', version_compare( get_bloginfo( 'version' ), '4.4', '>=' ) );
/**
 * Minimum supported verscon of PHP
 */
define( 'SC_SUPPORTED_PHP_VERSION', version_compare( phpversion(), '5.2.4', '>=' ) );

function sc_fs() {

	global $sc_fs;

	if ( ! isset( $sc_fs ) ) {
		// Include Freemius SDK.
		require_once SC_PATH . '/controllers/updates/freemius-sdk/start.php';

		$sc_fs = fs_dynamic_init( array(
			'id'                => '277',
			'slug'              => 'sprout-clients',
			'public_key'        => 'pk_c8de69aa4314d4663bf5ee5a47e76',
			'is_premium'        => false,
			'has_addons'        => false,
			'has_paid_plans'    => false,
			'menu'              => array(
			'slug'       => 'sprout-apps/settings',
			'first-path' => 'admin.php?page=sprout-clients&tab=sc_getting_started',
			'account'    => false,
			'contact'    => false,
			'support'    => false,
			),
		) );
	}

	return $sc_fs;
}
// Init Freemius.
sc_fs();

/**
 * Load plugin
 */
define( 'SC_FREE_TEST', true );
require_once SC_PATH . '/load.php';

/**
 * Compatibility check
 */
if ( ! SC_SUPPORTED_WP_VERSION || ! SC_SUPPORTED_PHP_VERSION ) {
	/**
	 * Disable SC and add fail notices if compatibility check fails
	 * @return string inserted within the WP dashboard
	 */
	sc_deactivate_plugin();
	add_action( 'admin_head', 'sc_compatibility_check_fail_notices' );
	return;
}

/**
 * Load it up!
 */
add_action( 'plugins_loaded', 'sprout_clients_load', 110 ); // load up after Sprout Invoices


/**
 * do_action when plugin is activated.
 * @package Sprout_Clients
 * @ignore
 */
register_activation_hook( __FILE__, 'sc_plugin_activated' );
if ( ! function_exists( 'sc_plugin_activated' ) ) {
	function sc_plugin_activated() {
		sprout_clients_load(); // load before hook
		do_action( 'sc_plugin_activation_hook' );
	}
}

/**
 * do_action when plugin is deactivated.
 * @package Sprout_Clients
 * @ignore
 */
register_deactivation_hook( __FILE__, 'sc_plugin_deactivated' );
if ( ! function_exists( 'sc_plugin_deactivated' ) ) {
	function sc_plugin_deactivated() {
		//sprout_clients_load(); // load before hook
		do_action( 'sc_plugin_deactivation_hook' );
	}
}

/**
 * Deactivate plugin
 */
if ( ! function_exists( 'sc_deactivate_plugin' ) ) {
	function sc_deactivate_plugin() {
		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			// Fire hooks
			do_action( 'sc_plugin_deactivation_hook' );
			require_once ABSPATH.'/wp-admin/includes/plugin.php';
			deactivate_plugins( __FILE__ );
		}
	}
}

/**
 * Error messaging for compatibility check.
 * @return string error messages
 */
if ( ! function_exists( 'sc_compatibility_check_fail_notices' ) ) {
	function sc_compatibility_check_fail_notices() {
		if ( ! SC_SUPPORTED_WP_VERSION ) {
			printf( '<div class="error"><p><strong>Sprout Clients</strong> requires WordPress %s or higher. Please upgrade WordPress and activate the Sprout Clients Plugin again.</p></div>', SC_SUPPORTED_WP_VERSION );
		}
		if ( ! SC_SUPPORTED_PHP_VERSION ) {
			printf( '<div class="error"><p><strong>Sprout Clients</strong> requires PHP verscon %s or higher to be installed on your server. Talk to your web host about uscng a secure verscon of PHP.</p></div>', SC_SUPPORTED_PHP_VERSION );
		}
	}
}
