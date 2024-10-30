<?php 
/*
*
*	***** Booking 60 *****
*
*	This file initializes all B60 Core components
*	
*/
// If this file is called directly, abort. //
if ( ! defined( 'WPINC' ) ) {die;} // end if
// Define Our Constants
define('B60_CORE',dirname( __FILE__ ).'/');
define('B60_CORE_INC',dirname( __FILE__ ).'/include/');
define('B60_CORE_IMG',plugins_url( 'assets/img/', __FILE__ ));
define('B60_CORE_CSS',plugins_url( 'assets/css/', __FILE__ ));
define('B60_CORE_JS',plugins_url( 'assets/js/', __FILE__ ));
define('B60_CORE_FORMS',plugins_url( 'pages/forms/', __FILE__ ));
define('B60_CORE_PAGES',dirname( '/pages/') );
define( 'B60_STRIPE_API_VERSION', '7.100.0' );

/*
*
*  Register CSS
*
*/
function b60_register_core_css(){
	wp_enqueue_style('b60-core', B60_CORE_CSS . 'b60-core.css',null,time(),'all');
	wp_enqueue_style('b60-fontawesome', B60_CORE_CSS . 'all.min.css',null,time(),'all');
	wp_enqueue_style('b60-multistep', B60_CORE_CSS . 'multi-style.css',null,time(),'all');	
  	wp_enqueue_style('select2', B60_CORE_CSS . 'select2.min.css' );
};
add_action( 'wp_enqueue_scripts', 'b60_register_core_css' );    
/*
*
*  Register JS/Jquery Ready
*
*/
function b60_register_core_js(){
	wp_enqueue_script('b60-core', B60_CORE_JS . 'b60-core.js', array( 'jquery' ), time(), false);
};
add_action( 'wp_enqueue_scripts', 'b60_register_core_js' );    

function b60_register_select_js(){
	wp_enqueue_style('select2', B60_CORE_CSS . 'select2.min.css' );
	wp_enqueue_script('select2', B60_CORE_JS . 'select2.min.js', array( 'jquery' ), B60::VERSION );
	wp_enqueue_script('validate-min', B60_CORE_JS . 'jquery.validate.min.js', array( 'jquery' ), B60::VERSION );
	wp_enqueue_script('select2-services', B60_CORE_JS . 'select2-services.js', array( 'jquery', 'jquery-ui-datepicker'), B60::VERSION, true ); 
	wp_enqueue_script( 'popup-js', B60_CORE_JS . 'jquery.popupoverlay.min.js', array( 'jquery' ) );
	wp_localize_script( 'select2-services', 'ajaxurl', array( 'admin_ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
};
add_action( 'admin_enqueue_scripts', 'b60_register_select_js' );     


/*
*
*  Includes
*
*/ 
// Load the Functions
if ( file_exists( B60_CORE_INC . 'b60-core-functions.php' ) ) {
	require_once B60_CORE_INC . 'b60-core-functions.php';
}     