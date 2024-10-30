<?php
/*
Plugin Name: Book in 60
Plugin URI: https://bookin60.com/
Description: Booking and appointment scheduling plugin for your maid and housekeeping business.
Version: 1.2.0
Author: Get Cleaning Clicks
Author URI: https://getcleaningclicks.com/
Text Domain: b60
*/

// If this file is called directly, abort. //
if ( ! defined( 'WPINC' ) ) {die;} // end if

// Let's Initialize Everything
if ( file_exists( plugin_dir_path( __FILE__ ) . 'core-init.php' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'core-init.php' );
}

//Stripe PHP library
if ( ! class_exists( '\Stripe\Stripe' ) ) {
	require_once( dirname( __FILE__ ) . '/vendor/stripe/stripe-php/init.php' );
} else {
	if ( substr( \Stripe\Stripe::VERSION, 0, strpos( \Stripe\Stripe::VERSION, '.' ) ) != substr( B60_STRIPE_API_VERSION, 0, strpos( B60_STRIPE_API_VERSION, '.' ) ) ) {
		wp_die( plugin_basename( __FILE__ ) . ': ' . __( 'Another plugin has loaded an incompatible Stripe API client. Deactivate all other Stripe plugins, and try to activate Bookin60 again.' ) );
	}
}

if ( file_exists( plugin_dir_path( __FILE__ ) . 'include/b60-main.php' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'include/b60-main.php' );
}

register_activation_hook( __FILE__, array( 'B60', 'setup_db' ) );