<?php

//Setup hooks and functions for admin menu

class B60_Admin_Menu {

	private $custom_post_type = null;
	private $post_type_metaboxes = null;

	function __construct() {

		$this->custom_post_type = new B60_Custom_Post_Type();
		$this->post_type_metaboxes     = new B60_Post_Type_Metaboxes();
		$this->hooks();
		apply_filters('set-screen-option', array( $this, 'set_screen_option'), 10, 3);
	}

	private function hooks() {

		add_action( 'admin_init', array( $this, 'b60_admin_init' ) );
		add_action( 'admin_menu', array( $this, 'b60_menu_services' ) );
		add_action( 'init', array( $this->post_type_metaboxes, 'register_metabox' ) );
		add_action( 'init',  array( $this->custom_post_type, 'register' ) );	
		
		add_action( 'admin_post_booking.csv',  array( $this, 'booking_csv' ) );	
		add_action( 'admin_post_lead.csv',  array( $this, 'lead_csv' ) );

		add_filter('manage_edit-service_columns', array( $this->custom_post_type, 'add_services_columns') );	
		add_filter('manage_edit-service_addons_columns', array( $this->custom_post_type, 'add_addons_columns') );	
		add_filter('manage_edit-service_frequencies_columns', array( $this->custom_post_type, 'add_frequency_columns') );
		add_filter('manage_edit-pricing_parameters_columns', array( $this->custom_post_type, 'add_parameters_columns') );
		add_action( 'admin_init',  array( $this->custom_post_type, 'register_columns' ) );
		
	}

	function booking_csv() {
	    if ( ! current_user_can( 'manage_options' ) )
	        return;

	    global $wpdb;

    	$table_name = $wpdb->prefix . 'b60_payments';

	    header('Content-Type: application/csv');
	    header('Content-Disposition: inline; filename="bookings_'.date('Y_m_d_H_i_s').'.csv"'); 
	    header('Pragma: no-cache');

	    ob_end_clean();

        $fp = fopen('php://output', 'w');

        $header_row = array(
         	0 => 'bookingID',
         	1 => 'transactionID',
         	2 => 'amount',
         	3 => 'booking_details',
         	4 => 'schedule_date',
         	5 => 'schedule_time',
         	6 => 'frequency',
         	7 => 'address_line1',
         	8 => 'address_line2',
         	9 => 'city',
         	10 => 'state',
         	11 => 'zip',
         	12 => 'created',
        );   

 		fputcsv($fp, $header_row);    

        $rows = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A  );

        if(!empty($rows)){
            foreach($rows as $Record){  
                $OutputRecord = array(
                					$Record['paymentID'],
                                    $Record['eventID'],
                                    sprintf( '%0.2f', $Record['amount'] / 100 ),
                                    $Record['booking_details'],
                                    $Record['schedule_date'],
                                    $Record['schedule_time'],
                                    $Record['frequency'],
                                    $Record['address'],
                                    $Record['apartment'],
                                    $Record['city'],
                                    $Record['state'],
                                    $Record['zip'],
                                    $Record['created'],
                                );
                fputcsv($fp, $OutputRecord);    
            }
        }

        fclose( $fp );        

	    exit();
	}

		function lead_csv() {
		    if ( ! current_user_can( 'manage_options' ) )
		        return;

		    global $wpdb;

	    	$table_name = $wpdb->prefix . 'b60_lead_entries';

		    header('Content-Type: application/csv');
		    header('Content-Disposition: inline; filename="leads_'.date('Y_m_d_H_i_s').'.csv"'); 
		    header('Pragma: no-cache');

		    ob_end_clean();

	        $fp = fopen('php://output', 'w');

	        $header_row = array(
	         	0 => 'leadID',
	         	1 => 'first_name',
	         	2 => 'last_name',
	         	3 => 'email',
	         	4 => 'phone',
	         	5 => 'has_booking',
	         	6 => 'created',
	        );   

	 		fputcsv($fp, $header_row);    

	        $rows = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A  );

	        if(!empty($rows)){
	            foreach($rows as $Record){  
	                $OutputRecord = array(
	                					$Record['leadID'],
	                                    $Record['first_name'],
	                                    $Record['last_name'],
	                                    $Record['email'],
	                                    $Record['phone'],
	                                    $Record['has_booking'],
	                                    $Record['created'],
	                                );
	                fputcsv($fp, $OutputRecord);    
	            }
	        }

	        fclose( $fp );        

		    exit();
		}


	function b60_admin_init() {
		wp_enqueue_style( 'b60-css', plugins_url( '/assets/css/b60.css', dirname( __FILE__ ) ), null, B60::VERSION );
        wp_enqueue_style( 'b60-admin-css', plugins_url( '/assets/css/b60-admin.css', dirname( __FILE__ ) ), null, B60::VERSION );        
        wp_enqueue_style( 'b60-formbuilder-css', plugins_url( '/assets/css/formbuilder.css', dirname( __FILE__ ) ), null, B60::VERSION );
        wp_enqueue_style( 'b60-fontawesome-css', plugins_url( '/assets/css/all.min.css', dirname( __FILE__ ) ), null, B60::VERSION );
        wp_enqueue_style( 'jquery-ui', plugins_url( '/assets/css/jquery-ui.min.css', dirname( __FILE__ ) ), null, B60::VERSION );
    }


    function pippin_sample_screen_options() {
     
    	global $pippin_sample_page;
     
    	$screen = get_current_screen();
          
    	$args = array(
    		'label' => __('Bookings per page', 'bookin60'),
    		'default' => 1,
    		'option' => 'per_page'
    	);
    	add_screen_option( 'per_page', $args );
    }

   function set_screen_option($status, $option, $value) {
		if ( 'per_page' == $option ) {
			return $value;
		}
	}
	//apply_filters('set-screen-option', array( $this, 'set_screen_option'));



	function mp_add_product_screen_options()
	{
	    $options = 'per_page';

	    $args = array(
	        'label' => 'Per Page',
	        'default' => 20,
	        'option' =>'per_page'
	    );
	    add_screen_option($options, $args);
	}


    function b60_menu_services() {
		// Add the top-level admin menu
		$page_title = 'Book in 60';
		$menu_title = 'Book in 60';
		$capability = 'manage_options';
		$menu_slug  = 'b60-services';
		$function   = '';
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, 'dashicons-admin-multisite', 28 );

		$submenu_page_title = 'Leads';
		$submenu_title      = 'Leads';
		$submenu_slug       = 'b60-leads';
		$submenu_function   = array( $this, 'b60_leads' );
		$menu_hook          = add_submenu_page( $menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function, 0 );
		add_action( 'admin_print_scripts-' . $menu_hook, array( $this, 'b60_admin_scripts' ) );

		$submenu_page_title = 'Bookings';
		$submenu_title      = 'Bookings';
		$submenu_slug       = 'b60-bookings';
		$submenu_function   = array( $this, 'b60_bookings' );
		$menu_hook          = add_submenu_page( $menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function, 1);
		add_action( 'admin_print_scripts-' . $menu_hook, array( $this, 'b60_admin_scripts' ) );

		$sub_menu_title = 'Service Categories';

		$submenu_page_title = 'Settings';
		$submenu_title      = 'Settings';
		$submenu_slug       = 'b60-settings';
		$submenu_function   = array( $this, 'b60_settings' );
		$menu_settings          = add_submenu_page( $menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);
		add_action( 'admin_print_scripts-' . $menu_settings, array( $this, 'b60_admin_scripts' ) );
		
	}

	function b60_menu_pages() {
		// Add the top-level admin menu
		$page_title = 'Book in 60 Settings';
		$menu_title = 'Book in 60';
		$capability = 'manage_options';
		$menu_slug  = 'b60-leads';
		$function   = array( $this, 'b60_leads' );
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, 'dashicons-calendar-alt', 26 );

		$submenu_page_title = 'Leads';
		$submenu_title      = 'Leads';
		$submenu_slug       = 'b60-leads';
		$submenu_function   = array( $this, 'b60_leads' );
		$menu_hook          = add_submenu_page( $menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function );
		add_action( 'admin_print_scripts-' . $menu_hook, array( $this, 'b60_admin_scripts' ) );

		$submenu_page_title = 'Bookings';
		$submenu_title      = 'Bookings';
		$submenu_slug       = 'b60-bookings';
		$submenu_function   = array( $this, 'b60_bookings' );
		$menu_hook          = add_submenu_page( $menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function );
		add_action( 'admin_print_scripts-' . $menu_hook, array( $this, 'b60_admin_scripts' ) );

		$submenu_page_title = 'Settings';
		$submenu_title      = 'Settings';
		$submenu_slug       = 'b60-settings';
		$submenu_function   = array( $this, 'b60_settings' );
		$menu_hook          = add_submenu_page( $menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function );
		add_action( 'admin_print_scripts-' . $menu_hook, array( $this, 'b60_admin_scripts' ) );

		$submenu_page_title = 'Booking 60 Edit Form';
		$submenu_title      = 'Edit Form';
		$submenu_slug       = 'b60-edit-form-f';
		$submenu_function   = array( $this, 'b60_edit_form' );
		$menu_hook          = add_submenu_page( null, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function );
		add_action( 'admin_print_scripts-' . $menu_hook, array( $this, 'b60_admin_scripts' ) );

		do_action( 'b60_admin_menus', $menu_slug );

	}

	function b60_admin_scripts() {

		$options = get_option( 'b60_settings_option' );
		wp_enqueue_media();
		wp_enqueue_script( 'stripe-js', '//js.stripe.com/v2/', array( 'jquery' ) );
		wp_enqueue_script( 'sprintf-js', plugins_url( 'assets/js/sprintf.min.js', dirname( __FILE__ ) ), null, B60::VERSION );
		//wp_enqueue_script( 'vendor-js', plugins_url( 'assets/js/vendor.js', dirname( __FILE__ ) ), null, B60::VERSION );
		wp_enqueue_script( 'scrollwindowto-js', plugins_url( 'assets/js/scrollWindowTo.js', dirname( __FILE__ ) ), null, B60::VERSION );
		wp_enqueue_script( 'underscore-js', plugins_url( 'assets/js/underscore.min.js', dirname( __FILE__ ) ), null, B60::VERSION );
		wp_enqueue_script( 'underscore-mixin-js', plugins_url( 'assets/js/underscore.mixin.js', dirname( __FILE__ ) ), null, B60::VERSION );
		wp_enqueue_script( 'rivets-js', plugins_url( 'assets/js/rivets.bundled.min.js', dirname( __FILE__ ) ), null, B60::VERSION );
		wp_enqueue_script( 'backbone-js', plugins_url( 'assets/js/backbone.min.js', dirname( __FILE__ ) ), null, B60::VERSION );
		wp_enqueue_script( 'backbone-deep-model-js', plugins_url( 'assets/js/deep-model.min.js', dirname( __FILE__ ) ), null, B60::VERSION );
		wp_enqueue_script( 'formbuilder-js', plugins_url( 'assets/js/formbuilder.js', dirname( __FILE__ ) ), array(
			'jquery',
			'jquery-ui-core',
			'jquery-ui-widget',
			'jquery-ui-mouse',
			'jquery-ui-accordion',
			'jquery-ui-draggable',
			'jquery-ui-droppable',
			'jquery-ui-sortable'
		), B60::VERSION );
		wp_enqueue_script( 'form-builder-display-js', plugins_url( 'assets/js/formbuilder-display.js', dirname( __FILE__ ) ), null, B60::VERSION );
		wp_localize_script( 'formbuilder-js', 'fb_ajaxurl', array( 'admin_ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

		wp_enqueue_script( 'b60-admin-js', plugins_url( 'assets/js/b60-admin.js', dirname( __FILE__ ) ), array(
			'sprintf-js',
			'jquery',
			'stripe-js',
			'jquery-ui-tabs',
			'jquery-ui-core',
			'jquery-ui-widget',
			'jquery-ui-autocomplete',
			'jquery-ui-button',
			'jquery-ui-tooltip',
			'jquery-ui-sortable',
			'jquery-ui-datepicker'
		), B60::VERSION );

		if ( $options['apiMode'] === 'test' ) {
			wp_localize_script( 'b60-admin-js', 'stripe', array( 'key' => $options['publishKey_test'] ) );
		} else {
			wp_localize_script( 'b60-admin-js', 'stripe', array( 'key' => $options['publishKey_live'] ) );
		}
		
		wp_localize_script( 'b60-admin-js', 'admin_ajaxurl',  array( 'admin_ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		

		do_action( 'b60_admin_scripts' );
	}

	function b60_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		//include B60_CORE . '/pages/b60_dashboard.php';	
	}

	function b60_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		include B60_CORE . '/pages/b60_settings.php';	
	}

	function b60_leads() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}
		if ( ! class_exists( 'B60_Leads_Table' ) ) {
			require_once( B60_CORE_INC . 'b60-table-leads.php' );
		}

		$table = new B60_Leads_Table();
		$table->prepare_items();

		if ( isset( $_GET['type'] ) && 'payment' == $_GET['type'] )
			include B60_CORE . '/pages/b60_edit_form_page.php';
		else
			include B60_CORE . '/pages/b60_leads_page.php';		
	}

	function b60_bookings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}
		if ( ! class_exists( 'B60_Payments_Table' ) ) {
			require_once( B60_CORE_INC . 'b60-table-bookings.php' );

		}

		$table = new B60_Payments_Table();
		$table->prepare_items();

		if ( isset( $_GET['type'] ) && 'payment' == $_GET['type'] ) {
			include B60_CORE . '/pages/b60_edit_form_page.php';
		} else {
			include B60_CORE . '/pages/b60_payments_page.php';	
		}
	}

	function b60_help() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		include B60_CORE . '/pages/b60_help_page.php';
	}

	function b60_edit_form() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		include B60_CORE . '/pages/b60_edit_form_page.php';
	}
}