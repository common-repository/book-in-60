<?php

/**
 * Custom Post Types
 *
 * @link       https://weblaunchlocal.com
 * @since      1.0.0
 *
 * @package    B60
 * @subpackage B60/includes
 */

class B60_Custom_Post_Type {

	function register_columns() {
		add_action('manage_service_posts_custom_column', array( $this, 'manage_services_columns' ), 10, 2);
		add_action('manage_pricing_parameters_posts_custom_column', array( $this, 'manage_parameters_columns' ), 20, 2);
		add_action('manage_service_addons_posts_custom_column', array( $this, 'manage_addons_columns' ), 30, 2);
		add_action('manage_service_frequencies_posts_custom_column', array( $this, 'manage_frequency_columns' ), 100, 2);
		
	}

	function register() {
		$this->register_post_type_services();
		$this->register_taxonomy_services();
		$this->register_post_type_pricing_parameters();
		$this->register_post_type_addons();
		$this->register_post_type_frequency();
	}

	// Services column

	function add_services_columns($services_columns) {
	    $new_columns['cb'] = '<input type="checkbox" />';	     
	    $new_columns['title'] = _x('Service Name', 'column name');
	    $new_columns['price'] = __('Price');
	    $new_columns['duration_hours'] = __('Details');	     
	    //$new_columns['exclude_from_frequency'] = __('Pricing Parameters');	 
	    $new_columns['category'] = __('Service Addons');	 
	    $new_columns['date'] = _x('Date', 'column name');
	 
	    return $new_columns;
	}

	function manage_services_columns($column_name, $id) {

	    global $wpdb;

	    $options         = get_option( 'b60_settings_option' );
	    $currencySymbol = B60::get_currency_symbol_for( $options['currency'] );

	    switch ($column_name) {

	    case 'price':
		    if( get_post_meta($id, 'service_price', true)  != '') {
		        echo esc_html( $currencySymbol ). esc_html( get_post_meta($id, 'service_price', true) );
		    }
	            break;
	    case 'duration_hours':

	        $args = array(
	        	    'post_type' => 'pricing_parameters',
	        	    'meta_key' => 'addon_select2_cat',
	        	    'posts_per_page' => -1
	        	);
	        	$dbResult = new WP_Query($args);

	        	global $post;
	        	$output = array();	        	

	        	if ($dbResult->have_posts()){
	        		while ( $dbResult->have_posts() ) {
	        			$dbResult->the_post();
	        			
	        			$servicesArr = get_post_meta(get_the_ID(),'addon_select2_cat', true);

	        			if ($servicesArr) {			            
	    					foreach ($servicesArr as $key => $service) {
	    						if($servicesArr[$key] == $id){
	    							$output[] = get_post(get_the_ID())->post_title;
	    						} 
	    					}
	    			    } 
	        		}    
	        		
	        		if($output){
	        			echo '<strong>Pricing parameters: </strong>';	 	
	        			echo esc_html( join( ', ', $output ) ).'<br>';
	        		}
	        		
	        		
	        	} 	

	    	if( get_post_meta($id, 'hourly_rate', true)  != '') {
	        	if( (get_post_meta($id, 'cleaners_from', true)  != '') && get_post_meta($id, 'cleaners_to', true)  != '') {
	        		//echo '<br>';
	        		echo '<strong>Cleaner:</strong> '. esc_html( get_post_meta($id, 'cleaners_from', true) ) .' - '. esc_html( get_post_meta($id, 'cleaners_to', true) );
	        	}
	        	if( (get_post_meta($id, 'cleaners_from', true)  != '') && get_post_meta($id, 'hours_from', true)  != '') {
		        	echo '<br>';
		        }
	        	if( (get_post_meta($id, 'hours_from', true)  != '') && get_post_meta($id, 'hours_to', true)  != '') {
	        		echo '<strong>Hours:</strong> '. esc_html( get_post_meta($id, 'hours_from', true) ) .' - '. esc_html( get_post_meta($id, 'hours_to', true) );
	        	}
	        } else {
	        	if( (get_post_meta($id, 'service_duration_hours', true)  != '') && get_post_meta($id, 'service_duration_minutes', true)  != '') {
	        	echo '<br><strong>Duration:</strong> '.esc_html( get_post_meta($id, 'service_duration_hours', true) ) .' hours '. esc_html( get_post_meta($id, 'service_duration_minutes', true) ) .' minutes';
	        	}
	        	
	        }

	        if( (get_post_meta($id, 'cleaners_from', true)  != '') && get_post_meta($id, 'hours_from', true)  != '') {
		        	echo '<br>';
		        }
	        
	            break;
	    
	    case 'category':
	    	$args = array(
	    	    'post_type' => 'service_addons',
	    	    'meta_key' => 'addon_select2_cat',
	    	    'posts_per_page' => -1
	    	);
	    	$dbResult = new WP_Query($args);

	    	global $post;
	    	$output = array();

	    	if ($dbResult->have_posts()){
	    		while ( $dbResult->have_posts() ) {
	    			$dbResult->the_post();
	    			
	    			$servicesArr = get_post_meta(get_the_ID(),'addon_select2_cat', true);

	    			if ($servicesArr) {			            
						foreach ($servicesArr as $key => $service) {
							//$output[] = get_post( (int)$servicesArr[$key] )->post_title;
							if($servicesArr[$key] == $id){
								$output[] = get_post(get_the_ID())->post_title;
							}		
						}
				    }  		
	    		}    	 	
	    		echo esc_html( join( ', ', $output ) );
	    	}	 
	        break;
	 
	    default:
	        break;
	    } // end switch
	}   

	// Pricing parameters column

	function add_parameters_columns($parameters_columns) {
	    $new_columns['cb'] = '<input type="checkbox" />';
	    $new_columns['title'] = _x('Parameter Name', 'column name');
	    $new_columns['price'] = __('Price');
	    $new_columns['pricing_parameter_duration_hours'] = __('');	
	    $new_columns['category'] = __('Services');	 
	    $new_columns['date'] = _x('Date', 'column name');
	 
	    return $new_columns;
	}

	function manage_parameters_columns($column_name, $id) {
	    global $wpdb;
	    switch ($column_name) {
	    case 'price':
			    if( get_post_meta($id, 'parameter_selection', true)  == 'flat_price') {
			    	if( get_post_meta($id, 'flat_price_amount', true)  != '') {
				        echo '$'. esc_html( get_post_meta($id, 'flat_price_amount', true) );
				    }
				} else {
					echo 'Price varies';
				}
	            break;
	    case 'pricing_parameter_duration_hours':
	    	if(get_post_meta($id, 'parameter_selection', true) == 'flat_price') {
	    		if( (get_post_meta($id, 'pricing_parameter_duration_hours', true)  != '') && get_post_meta($id, 'pricing_parameter_duration_minutes', true)  != '') {
	    			echo '<strong>Duration:</strong> '. esc_html( get_post_meta($id, 'pricing_parameter_duration_hours', true) ) .' hours '. esc_html( get_post_meta($id, 'pricing_parameter_duration_minutes', true) ) .' minutes';
	    		}
	    		if( (get_post_meta($id, 'pricing_parameter_duration_hours', true)  != '') && get_post_meta($id, 'value_from', true)  != '') {
	    			echo '<br>';
	    		}
	    		if( (get_post_meta($id, 'value_from', true)  != '') && get_post_meta($id, 'value_to', true)  != '') {
	    			echo '<strong>Range:</strong> '. esc_html( get_post_meta($id, 'value_from', true) ) .' - '. esc_html( get_post_meta($id, 'value_to', true) );
	    		}
	    	} else {
	    		echo '<strong>Duration:</strong> Varies<br>';
	    		$ranges = get_post_meta($id,'ranges', true);
	    		foreach ($ranges as $key => $range) {
	    			echo esc_html( $ranges[$key]['label'] ) .' ($'. esc_html( number_format($ranges[$key]['range_price'], 2, '.', '') ) .')<br>';
	    		}
	    	}
	        
	        break;
	    
	    case 'category':
	    	$services = get_post_meta($id, 'addon_select2_cat', true);

	    	if ($services) {
	            $output = array();
	            foreach ($services as $key => $service) {
	                $output[] = get_post( (int)$services[$key] )->post_title;
	            }
	            echo esc_html( join( ', ', $output ) );
	        }
		 
	        break;
	 
	    default:
	        break;
	    } // end switch
	}   

	// Addons column

	function add_addons_columns($addons_columns) {
	    $new_columns['cb'] = '<input type="checkbox" />';
	    $new_columns['title'] = _x('Service Addon Name', 'column name');
	    $new_columns['price'] = __('Price');
	    $new_columns['addon_duration_hours'] = __('Duration');	
	    $new_columns['quantity_based'] = __('Quantity');	   
	    $new_columns['category'] = __('Services');	 
	    $new_columns['date'] = _x('Date', 'column name');
	 
	    return $new_columns;
	}

	function manage_addons_columns($column_name, $id) {
	    global $wpdb;
	    switch ($column_name) {
	    case 'price':
	    	if( get_post_meta($id, 'addon_price', true)  != '') {
		        echo '$'. esc_html( get_post_meta($id, 'addon_price', true) );
		    }
	            break;
	    case 'addon_duration_hours':
	        if( (get_post_meta($id, 'addon_duration_hours', true)  != '') && get_post_meta($id, 'addon_duration_minutes', true)  != '') {
	        	echo esc_html( get_post_meta($id, 'addon_duration_hours', true) ) .' hours '. esc_html( get_post_meta($id, 'addon_duration_minutes', true) ) .' minutes';
	        }
	        break;
	    case 'quantity_based':
	        if(get_post_meta($id, 'quantity_based', true) === 'checked'){
	        	echo '<i class="fa fa-check"></i>';
	        }
	        break;
	    
	    case 'category':
	    	$services = get_post_meta($id, 'addon_select2_cat', true);

	    	if ($services) {
	            $output = array();
	            foreach ($services as $key => $service) {
	                $output[] = get_post( (int)$services[$key] )->post_title;
	            }
	            echo esc_html( join( ', ', $output ) );
	        }
		 
	        break;
	 
	    default:
	        break;
	    } // end switch
	}   

	// Frequency column

	function add_frequency_columns($frequency_columns) {
	    $new_columns['cb'] = '<input type="checkbox" />';
	    $new_columns['title'] = _x('Frequency Name', 'column name');
	    $new_columns['frequency'] = __('Frequency');	 
	    $new_columns['default_frequency'] = __('Default');	 
	    $new_columns['frequency_discount'] = __('Frequency Discount');	 
	    $new_columns['date'] = _x('Date', 'column name');
	 
	    return $new_columns;
	}

	function manage_frequency_columns($column_name, $id) {
	    global $wpdb;
	    switch ($column_name) {
	 
	    case 'frequency':
	        $frequency = get_post_meta($id, 'frequency', true);
	        
	        if($frequency === '1w') {
	        	echo 'Every Week';
	        } elseif($frequency === '2w') {
	        	echo 'Every 2 Weeks';
	        } elseif($frequency === '3w') {
	        	echo 'Every 3 Weeks';
	        } elseif($frequency === '4w') {
	        	echo 'Every 4 Weeks';
	        } elseif($frequency === 'c') {
	        	echo 'Every ' . esc_html( get_post_meta($id, 'repeats_every', true) ) . ' Weeks';
	        } elseif($frequency === 'o') {
	        	echo 'One Time';
	        }

	        break;

	    case 'default_frequency':
	        if(get_post_meta($id, 'default_frequency', true) === 'checked'){
	        	echo '<i class="fa fa-check ng-scope"></i>';
	        }
	        
	            break;

	    case 'frequency_discount':
		    if(get_post_meta($id, 'discount_f', true) === 'percentage_d'){
		    	echo esc_html( get_post_meta($id, 'frequency_discount', true) ).'%';
		    } else {
		    	echo '$'. esc_html( get_post_meta($id, 'amount_discount', true) );
		    }
	        
	            break;
	    default:
	        break;
	    } // end switch
	}   

	function register_post_type_services() {

		$labels = array(
			'name'               => __( 'Services', 'b60' ),
			'singular_name'      => __( 'Service', 'b60' ),
			'add_new'            => __( 'Add Service', 'b60' ),
			'add_new_item'       => __( 'Add Service', 'b60' ),
			'edit_item'          => __( 'Edit Service', 'b60' ),
			'new_item'           => __( 'New Service', 'b60' ),
			'view_item'          => __( 'View Service', 'b60' ),
			'search_items'       => __( 'Search Services', 'b60' ),
			'not_found'          => __( 'No services found', 'b60' ),
			'not_found_in_trash' => __( 'No services in the trash', 'b60' ),
		);

		$supports = array(
			'title',
			'thumbnail',
			//'revisions',
		);

		$args = array(
			'labels'          => $labels,
			'supports'        => $supports,
			'public'          => false,
			'capability_type' => 'post',
			'rewrite'         => array( 'slug' => 'services', ), // Permalinks format
			'menu_position'   => 10,
			'menu_icon'       => 'dashicons-admin-multisite',
			'show_in_nav_menus' => false,
			'show_ui'           => true,
			'show_in_menu' => 'b60-services',
			//'taxonomies'    => array('service-category'),
		);

		$args = apply_filters( 'services_post_type_args', $args );

		register_post_type('service', $args);
	}

	function register_taxonomy_services() {
		$labels = array(
			'name'                       => __( 'Service Categories', 'b60' ),
			'singular_name'              => __( 'Service Category', 'b60' ),
			'menu_name'                  => __( 'Service Categories', 'b60' ),
			'edit_item'                  => __( 'Edit Service Category', 'b60' ),
			'update_item'                => __( 'Update Service Category', 'b60' ),
			'add_new_item'               => __( 'Add New Service Category', 'b60' ),
			'new_item_name'              => __( 'New Service Category Name', 'b60' ),
			'parent_item'                => __( 'Parent Service Category', 'b60' ),
			'parent_item_colon'          => __( 'Parent Service Category:', 'b60' ),
			'all_items'                  => __( 'All Service Categories', 'b60' ),
			'search_items'               => __( 'Search Service Categories', 'b60' ),
			'popular_items'              => __( 'Popular Service Categories', 'b60' ),
			'separate_items_with_commas' => __( 'Separate service categories with commas', 'b60' ),
			'add_or_remove_items'        => __( 'Add or remove service categories', 'b60' ),
			'choose_from_most_used'      => __( 'Choose from the most used service categories', 'b60' ),
			'not_found'                  => __( 'No service categories found.', 'b60' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'show_in_nav_menus' => false,
			'show_ui'           => true,
			'show_in_menu' 		=> false,
			'show_tagcloud'     => true,
			'hierarchical'      => true,
			'rewrite'           => array( 'slug' => 'service-category' ),
			'show_admin_column' => true,
			'query_var'         => true,
		);

		$args = apply_filters( 'service_post_type_category_args', $args );

		register_taxonomy( 'service-category', array('service'), $args );
	}

	function register_post_type_pricing_parameters() {

		$labels = array(
			'name'               => __( 'Pricing Parameters', 'pricing-parameters' ),
			'singular_name'      => __( 'Pricing Parameter', 'pricing-parameters' ),
			'add_new'            => __( 'Add Pricing Parameter', 'pricing-parameters' ),
			'add_new_item'       => __( 'Add Pricing Parameter', 'pricing-parameters' ),
			'edit_item'          => __( 'Edit Pricing Parameter', 'pricing-parameters' ),
			'new_item'           => __( 'New Pricing Parameter', 'pricing-parameters' ),
			'view_item'          => __( 'View Pricing Parameter', 'pricing-parameters' ),
			'search_items'       => __( 'Search Pricing Parametera', 'pricing-parameters' ),
			'not_found'          => __( 'No pricing parameters found', 'pricing-parameters' ),
			'not_found_in_trash' => __( 'No pricing parameters in the trash', 'pricing-parameters' ),
		);

		$supports = array(
			'title',
			//'revisions',
		);

		$args = array(
			'labels'          => $labels,
			'supports'        => $supports,
			'public'          => false,
			'capability_type' => 'post',
			'rewrite'         => array( 'slug' => 'pricing_parameters', ), // Permalinks format
			'menu_position'   => 10,
			'menu_icon'       => 'dashicons-admin-multisite',
			'show_in_nav_menus' => false,
			'show_ui'           => true,
			'show_in_menu' => 'b60-services',
			//'taxonomies'    => array('service-category'),
		);

		$args = apply_filters( 'pricing_parameters_post_type_args', $args );

		register_post_type('pricing_parameters', $args);
	}

	function register_post_type_addons() {

		$labels = array(
			'name'               => __( 'Service Addons', 'addons-post-type' ),
			'singular_name'      => __( 'Service Addons', 'addons-post-type' ),
			'add_new'            => __( 'Add Service addons', 'addons-post-type' ),
			'add_new_item'       => __( 'Add Service addons', 'addons-post-type' ),
			'edit_item'          => __( 'Edit Service addons', 'addons-post-type' ),
			'new_item'           => __( 'New Service addons', 'addons-post-type' ),
			'view_item'          => __( 'View Service addons', 'addons-post-type' ),
			'search_items'       => __( 'Search Service Addons', 'addons-post-type' ),
			'not_found'          => __( 'No service addons found', 'addons-post-type' ),
			'not_found_in_trash' => __( 'No service addons in the trash', 'addons-post-type' ),
		);

		$supports = array(
			'title',
			'thumbnail',
			//'revisions',
		);

		$args = array(
			'labels'          => $labels,
			'supports'        => $supports,
			'public'          => false,
			'capability_type' => 'post',
			'rewrite'         => array( 'slug' => 'service_addons', ), // Permalinks format
			'menu_position'   => 20,
			'menu_icon'       => 'dashicons-id',
			'show_in_nav_menus' => false,
			'show_ui'           => true,
			'show_in_menu' => 'b60-services',
		);

		$args = apply_filters( 'service_addons_post_type_args', $args );

		register_post_type('service_addons', $args);
	}

	function register_post_type_frequency() {

		$labels = array(
			'name'               => __( 'Service Frequency', 'frequency-post-type' ),
			'singular_name'      => __( 'Service Frequency', 'frequency-post-type' ),
			'add_new'            => __( 'Add Service Frequency', 'frequency-post-type' ),
			'add_new_item'       => __( 'Add Service Frequency', 'frequency-post-type' ),
			'edit_item'          => __( 'Edit Service Frequency', 'frequency-post-type' ),
			'new_item'           => __( 'New Service Frequency', 'frequency-post-type' ),
			'view_item'          => __( 'View Service Frequency', 'frequency-post-type' ),
			'search_items'       => __( 'Search Service Frequency', 'frequency-post-type' ),
			'not_found'          => __( 'No service frequency found', 'frequency-post-type' ),
			'not_found_in_trash' => __( 'No service frequency in the trash', 'frequency-post-type' ),
		);

		$supports = array(
			'title',
			//'revisions',
		);

		$args = array(
			'labels'          => $labels,
			'supports'        => $supports,
			'public'          => false,
			'capability_type' => 'post',
			'rewrite'         => array( 'slug' => 'service_frequency', ), // Permalinks format
			'menu_position'   => 20,
			'menu_icon'       => 'dashicons-id',
			'show_in_nav_menus' => false,
			'show_ui'           => true,
			'show_in_menu' => 'b60-services',
		);

		$args = apply_filters( 'service_frequency_post_type_args', $args );

		register_post_type('service_frequencies', $args);
	}	 
	
}
