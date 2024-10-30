<?php

//deals with admin back-end input i.e. create plans, transfers
class B60_Admin {
	private $stripe = null;
	private $db = null;

	public function __construct() {
		$this->stripe = new B60_Stripe();
		$this->db     = new B60_Database();
		$this->hooks();
	}

	private function hooks() {
		add_action('wp_ajax_wp_full_stripe_create_payment_form', array($this, 'b60_create_payment_form_post') );
		add_action('wp_ajax_wp_full_stripe_edit_payment_form', array( $this, 'b60_edit_payment_form_post' ) );
		add_action('wp_ajax_update_general_settings', array( $this, 'update_general_settings' ) );
		add_action('wp_ajax_update_email_notifications', array( $this, 'update_email_notifications' ) );
		add_action('wp_ajax_update_giftcards_settings', array( $this, 'update_giftcards_settings' ) );
		add_action('wp_ajax_b60_stripe_update_settings', array( $this, 'b60_stripe_update_settings' ) );
		add_action('wp_ajax_updated_calendar_settings', array( $this, 'update_calendar_settings' ) );
		add_action('wp_ajax_wp_full_stripe_delete_payment_form', array( $this, 'b60_delete_payment_form' ) );
		add_action('wp_ajax_save_lead_formbuilder', array( $this, 'save_lead_formbuilder' ) );
		add_action('wp_ajax_save_booking_formbuilder', array( $this, 'save_booking_formbuilder' ) );
		add_action('wp_ajax_get_ajax_posts', array($this, 'get_ajax_posts'));
		add_action('wp_ajax_get_post_types', array($this, 'get_post_types'));
		add_action('wp_ajax_nopriv_get_post_types', array($this, 'get_post_types'));
		add_action('wp_ajax_check_coupon_code', array($this, 'check_coupon_code'));
		add_action('wp_ajax_nopriv_check_coupon_code', array($this, 'check_coupon_code'));
		add_action('wp_ajax_get_service_meta', array($this, 'get_service_meta'));
		add_action('wp_ajax_get_pricing', array($this, 'get_pricing'));
		add_action('wp_ajax_service_add_slot', array($this, 'service_add_slot'));
		add_action('wp_ajax_save_time_slot', array($this, 'save_time_slot'));
		add_action('wp_ajax_service_delete_slot', array($this, 'service_delete_slot'));
		add_action('wp_ajax_get_service_slot', array($this, 'get_service_slot'));
		add_action('wp_ajax_nopriv_get_service_slot', array($this, 'get_service_slot'));
		add_action('wp_ajax_get_holidays', array($this, 'get_holidays'));
		add_action('wp_ajax_nopriv_get_holidays', array($this, 'get_holidays'));
		add_action('wp_ajax_service_calendar_holiday', array($this, 'service_calendar_holiday'));
		add_action('wp_ajax_service_delete_holiday', array($this, 'service_delete_holiday'));		
		add_action('wp_ajax_save_holiday', array($this, 'save_holiday'));
		add_action('wp_ajax_email_lead_confirmation', array($this, 'email_lead_confirmation'));
		add_action('wp_ajax_nopriv_email_lead_confirmation', array($this, 'email_lead_confirmation'));
		add_action('wp_ajax_email_booking_confirmation', array($this, 'email_booking_confirmation'));
		add_action('wp_ajax_nopriv_email_booking_confirmation', array($this, 'email_booking_confirmation'));
		add_action('wp_ajax_email_booking_confirmation_admin', array($this, 'email_booking_confirmation_admin'));
		add_action('wp_ajax_nopriv_email_booking_confirmation_admin', array($this, 'email_booking_confirmation_admin'));
		add_action('wp_ajax_email_giftcard_confirmation', array($this, 'email_giftcard_confirmation'));
		add_action('wp_ajax_nopriv_email_giftcard_confirmation', array($this, 'email_giftcard_confirmation'));
		add_action('wp_ajax_send_test_email', array($this, 'send_test_email'));
	}

	function get_ajax_posts() {
	    $args = array(
	        'post_type' => array('service'),
	        'post_status' => array('publish'),
	        'posts_per_page' => 40,
	        'nopaging' => true,
	        'order' => 'DESC',
	        'orderby' => 'date'
	    );
	    $ajaxposts = get_posts( $args ); 
	    echo json_encode( $ajaxposts );
	    exit;
	}

	function get_post_types() {

		$data = [];
		$meta = [];
		$tester = array();	 
		$output_services = array();	 
		$output_add_ons = array();	 

	    $args = array(
	        'post_type' => array('service_frequencies', 'service', 'service_addons', 'pricing_parameters'),
	        'post_status' => array('publish'),
	        'posts_per_page' => -1,
	        'nopaging' => true,
	        'order' => 'DESC',
	        'orderby' => 'date'
	    );


	    $ajaxposts = get_posts( $args ); 

	    foreach ($ajaxposts as $ajaxpost) {  

	      if($ajaxpost->post_type === 'service') {

	      	$parameter_args = array(
				'post_type' => 'pricing_parameters',
				'meta_key' => 'addon_select2_cat',
				'posts_per_page' => -1
		    );

		    $dbResult_pricing = new WP_Query($parameter_args);

		    $addons_args = array(
				'post_type' => 'service_addons',
				'meta_key' => 'addon_select2_cat',
				'posts_per_page' => -1
		    );

		    $dbResult_addons = new WP_Query($addons_args);

	        $obj = array();	     
	        $addons = array();

	        if(get_post_meta($ajaxpost->ID, 'hourly_rate', true) == 'checked') {
				array_push($obj, array(
					'id'    		=> $ajaxpost->ID,
					'title' 		=> $ajaxpost->post_title,
					'pricing_type' 	=> 'service',
					'slug'			=> $ajaxpost->post_name,
					'cleaners_from'	=> get_post_meta($ajaxpost->ID,'cleaners_from', true),
					'cleaners_to'	=> get_post_meta($ajaxpost->ID,'cleaners_to', true),
					'hours_from'	=> get_post_meta($ajaxpost->ID,'hours_from', true),
					'hours_to'		=> get_post_meta($ajaxpost->ID,'hours_to', true),
					'price'			=> $ajaxpost->service_price
				));
	        } else {
	        	if ($dbResult_pricing->have_posts()){
		       		while ( $dbResult_pricing->have_posts() ) {
		        		$dbResult_pricing->the_post();	        			
		        		$servicesArr = get_post_meta(get_the_ID(),'addon_select2_cat', true);

		        		if ($servicesArr) {			            
		    				foreach ($servicesArr as $key => $service) {
		    					if($servicesArr[$key] == $ajaxpost->ID){
						    		if(get_post_meta(get_the_ID(), 'parameter_selection', true) == 'flat_price') {
							    		array_push($obj, array(
							    			'id'    	    => get_the_ID(),
							    			'title'		    => get_post(get_the_ID())->post_title,
							    			'pricing_type'  => 'flat_price',
							    			'slug'			=> get_post(get_the_ID())->post_name,
							    			'range_from'	=> get_post_meta(get_the_ID(),'value_from', true),
							    			'range_to'		=> get_post_meta(get_the_ID(),'value_to', true),
							    			'pricing_parameter_duration_hours'		=> get_post_meta(get_the_ID(),'pricing_parameter_duration_hours', true),
							    			'pricing_parameter_duration_minutes'		=> get_post_meta(get_the_ID(),'pricing_parameter_duration_minutes', true),
							    			'price'			=> get_post_meta(get_the_ID(),'flat_price_amount', true)
							    		));
						    		} else {
						    			$ranges = get_post_meta(get_the_ID(),'ranges', true);
										$range_min = reset($ranges);
						    			$range_max = end($ranges);

						    			array_push($obj, array(
						    				'id'    		=> get_the_ID(),
						    				'title' 		=> get_post(get_the_ID())->post_title,
						    				'pricing_type' 	=> 'range_price',
						    				'slug'			=> get_post(get_the_ID())->post_name,
							    			'range_from'	=> $range_min['range_qty_min'],
							    			'range_to'		=> $range_max['range_qty_max'],
							    			'range_hours'		=> $range_max['range_hours'],
							    			'range_minutes'		=> $range_max['range_minutes'],
							    			'price'			=> get_post_meta(get_the_ID(),'ranges', true)
						    			));
						    		}
		    					} 
		    					
		    				}
		    		    } 
		        	}    
		        }
	        }      

	        if ($dbResult_addons->have_posts()){
	       		while ( $dbResult_addons->have_posts() ) {
	        		$dbResult_addons->the_post();	        			
	        		$servicesArr = get_post_meta(get_the_ID(),'addon_select2_cat', true);

	        		if ($servicesArr) {			            
	    				foreach ($servicesArr as $key => $service) {
	    					if($servicesArr[$key] == $ajaxpost->ID) {
					    		array_push($addons, array(
						    			'id'    	 			   	 => get_the_ID(),
						    			'service_addon_order_no'	 => get_post(get_the_ID())->service_addon_order_no,
						    			'title'		   				 => get_post(get_the_ID())->post_title,
						    			'addon_description'		   	 => get_post(get_the_ID())->addon_description,
						    			'slug'					  	 => get_post(get_the_ID())->post_name,
						    			'addon_sales_tax'			 => get_post(get_the_ID())->addon_sales_tax,
						    			'addon_price'	     => floatval(get_post(get_the_ID())->addon_price + (get_post(get_the_ID())->addon_price * (get_post(get_the_ID())->addon_sales_tax) / 100)),
						    			'thumb'						 => get_the_post_thumbnail_url(get_the_ID(), 'post-thumbnail'),
						    			'quantity_based'			 => get_post(get_the_ID())->quantity_based,
						    			'addon_duration_hours'		 => get_post(get_the_ID())->addon_duration_hours,
						    			'addon_duration_minutes'	 => get_post(get_the_ID())->addon_duration_minutes,
						    			'addon_exclude_from'	 	 => get_post(get_the_ID())->addon_exclude_from,
						    			'addon_code_discounts'	 	 => get_post(get_the_ID())->addon_code_discounts,
						    			'addon_recurring'	 	 	 => get_post(get_the_ID())->addon_recurring,
						    		));
	    					} 
	    					
	    				}
	    		    } 
	        	}    
	        } 

	        if(get_post_meta($ajaxpost->ID, 'hourly_rate', true) != 'checked') {
	        	$service_price = get_post_meta($ajaxpost->ID,'service_price', true);
	        } else {
	        	$service_price = 0;
	        }

	        $data[] = array(
		        'id' => $ajaxpost->ID,
		        'post_title' => $ajaxpost->post_title,
		        'post_name' => $ajaxpost->post_name,
		        'post_type' => $ajaxpost->post_type,
		        'service_price' => $service_price,
		        'meta' => $obj,
		        'addons' => $addons,
		      );    
	      }	

	      if($ajaxpost->post_type === 'service_frequencies') {

	      	$frequency_args = array(
				'post_type' => 'service_frequencies',
				'posts_per_page' => -1
		    );

		    $dbResult_frequencies = new WP_Query($frequency_args);

		    if ($dbResult_frequencies->have_posts()){
	       		while ( $dbResult_frequencies->have_posts() ) {
	        		$dbResult_frequencies->the_post();	        			
	        		$servicesArr = get_post_meta($ajaxpost->ID,'discount_f', true);

	        		if ($servicesArr) {			            
	    				if ($servicesArr == 'amount_d') {
	    					$frequency_price = get_post_meta($ajaxpost->ID,'amount_discount', true);
	    					$frequency_type = 'amount_d';
	    				} else {
	    					$frequency_price = get_post_meta($ajaxpost->ID,'frequency_discount', true);
	    					$frequency_type = 'percentage_d';
	    				}
					}
				}
			}

		    $data[] = array(
		        'id' => $ajaxpost->ID,
		        'post_title' => $ajaxpost->post_title,
		        'post_name' => $ajaxpost->post_name,
		        'post_type' => $ajaxpost->post_type,
		        'frequency_type' => $frequency_type,
		        'default_frequency' => $ajaxpost->default_frequency,
		        'frequency_price' => $frequency_price,
		      );  
	      }      
	    }

	    echo json_encode( $data );
	    exit;
	}

	function get_service_meta() {
	    
	    $ajaxposts = [];

	    $meta = get_post_meta( is_numeric( $_POST['id'] ) );

	    foreach($meta as $key=>$val)
	    {
	        $ajaxposts[$key] = $val;
	    }

	    echo json_encode( $ajaxposts );
	    exit;
	}

	function get_pricing() {
	    $args = array(
	        'post_type' => array('pricing_parameters'),
	        'post_status' => array('publish'),
	        'posts_per_page' => -1,
	        'nopaging' => true,
	        'order' => 'DESC',
	        'orderby' => 'date'
	    );
	    $ajaxposts = get_posts( $args ); 
	    echo json_encode( $ajaxposts );
	    exit;
	}

	
	public function service_add_slot() {
		$name = 'service_custom_slots';
		$uid  = uniqid();
		$id   = wp_insert_post(array(
				  'post_title'  => 'Time Slot', 
				  'post_type'   => 'service_slot',
				  'post_status' => 'publish',
				  'meta_input' => array( 
				        'start_time' => '',
				        'end_time'   => '',
				        'week_day'   => '',
				        'capacity'   => ''
				   ),
				));			  
	?>
	<tr>
		<td>
			<select name="start_time_<?php echo esc_attr( $id ); ?>" id="start_time_<?php echo esc_attr( $id ); ?>">
			<?php
				foreach (get_appointment_time() as $time => $text) {
					echo '<option value=' . esc_attr( $time ) . '>' . esc_html( $text ) . '</option>';
				}
			?>
			</select>
		</td>
		<td>
			<select name="end_time_<?php echo esc_attr( $id ); ?>" id="end_time_<?php echo esc_attr( $id ); ?>">
				<?php
					foreach (get_appointment_time($out = false, $_24 = true) as $time => $text) {
						echo '<option value=' . esc_attr( $time ) . '>' . esc_html( $text ) . '</option>';
					}
				?>
			</select>
		</td>
		<td>
			<?php
				foreach (array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') as $key => $week) {
					$week_short = substr(ucfirst($week), 0, 1);
					echo '<label class="week_day">
								<input type="checkbox" name="week_day_'.esc_attr( $id ).'" value="'.esc_attr( $key ).'">
								<span>'.esc_html( $week_short ).'</span>
							</label>';
				}
			?>
		</td>
		<td>
			<select name="capacity_<?php echo esc_attr( $id ); ?>" id="capacity_<?php echo esc_attr( $id ); ?>">
			    <?php
			            foreach (services_capacity_options() as $num => $text) {
			                echo '<option value=' . esc_attr( $num ) . '>' . esc_html( $text ) . '</option>';
			            }
			            ?>
			</select>
		</td>
		<td>
			<div class="slot-delete" id="<?php echo esc_attr( $id ); ?>">Delete</div><div style="display: inline-flex;margin-left: 5px;">
					<button class="save-slot button button-primary" id="<?php echo esc_attr( $id ); ?>" type="submit">Save</button>
				</div>
		</td>
	</tr>
	<?php
		wp_die();
	}

	public function save_time_slot() {

		$id = sanitize_key( $_POST['id'] );

		update_post_meta($id, 'start_time', sanitize_text_field( $_POST['start_time'] ) );
		update_post_meta($id, 'end_time', sanitize_text_field( $_POST['end_time'] ) );
		update_post_meta($id, 'week_day', array_map( 'sanitize_text_field', $_POST['week_day'] ) );
		update_post_meta($id, 'capacity', sanitize_text_field( $_POST['capacity'] ) );

		header( "Content-Type: application/json" );
		echo json_encode( array(
			'success'  	 => true,
			'id'       	 => $id,
		) );
		exit;
	}

	public function service_delete_slot() {
		wp_delete_post( sanitize_key( $_POST['id'] ) );
	}

	function get_service_slot() {

		$args = array(
		    'post_type' => array('service_slot'),
		    'post_status' => array('publish'),
		    'posts_per_page' => -1,
		    'nopaging' => true,
		    'order' => 'ASC'
		);

		$test = get_option('booking_to_crm')['bookin60_crm_timezone'];

		$ajaxposts = get_posts( $args ); 

		$day_of_week = array('0', '1', '2', '3', '4', '5', '6');

		$monday = array();

		$obj = array();	  

		foreach ($ajaxposts as $ajaxpost) {  
			                

			foreach (get_appointment_time() as $time => $start_time) {
				if(get_post_meta($ajaxpost->ID, 'start_time', true) ==  $time) {
					$start_ = $start_time;
				} 
			}

			foreach (get_appointment_time() as $time => $end_time) {
				if(get_post_meta($ajaxpost->ID, 'end_time', true) ==  $time) {
					$end_ = $end_time;
				} 
			}

			$days = get_post_meta($ajaxpost->ID,'week_day', true);

			if ($days) {			            
				foreach ($days as $key => $value) {
					if (in_array($value, $day_of_week)) {
					    array_push($obj, array(
							'day'  => $value,
							'start_time' => $start_,
							'end_time' => $end_,
						));
					}	
				}
			}

		}

		$monday = array();
		$tuesday = array();
		$wednesday = array();
		$thursday = array();
		$friday = array();
		$saturday = array();
		$sunday = array();

		foreach ($obj as $key => $value) {

			if($value['day'] == '0') {
				array_push($sunday, array(
					'start_time' => $value['start_time'],
					'end_time' => $value['end_time'],
				));
			}
			if($value['day'] == '1') {
				array_push($monday, array(
					'start_time' => $value['start_time'],
					'end_time' => $value['end_time'],
				));
			}
			if($value['day'] == '2') {
				array_push($tuesday, array(
					'start_time' => $value['start_time'],
					'end_time' => $value['end_time'],
				));
			}
			if($value['day'] == '3') {
				array_push($wednesday, array(
					'start_time' => $value['start_time'],
					'end_time' => $value['end_time'],
				));
			}
			if($value['day'] == '4') {
				array_push($thursday, array(
					'start_time' => $value['start_time'],
					'end_time' => $value['end_time'],
				));
			}
			if($value['day'] == '5') {
				array_push($friday, array(
					'start_time' => $value['start_time'],
					'end_time' => $value['end_time'],
				));
			}
			if($value['day'] == '6') {
				array_push($saturday, array(
					'start_time' => $value['start_time'],
					'end_time' => $value['end_time'],
				));
			}			
		}

		$data = array(
			1 => $monday, 
			2 => $tuesday, 
			3 => $wednesday, 
			4 => $thursday, 
			5 => $friday, 
			6 => $saturday, 
			0 => $sunday, 
			"selectedTimezone" => get_option('booking_to_crm')['bookin60_crm_timezone'],
			"selectedOffset" => get_option('booking_to_crm')['bookin60_crm_timezone_offset'],
		);

	    echo json_encode( $data );
	    exit;
	}

	function get_holidays() {

		$args = array(
		    'post_type' => array('calendar_holiday'),
		    'post_status' => array('publish'),
		    'posts_per_page' => -1,
		    'nopaging' => true,
		    'order' => 'ASC'
		);


		$ajaxposts = get_posts( $args ); 

		$obj = array();	  

		foreach ($ajaxposts as $ajaxpost) {  
			array_push($obj, array(
				'holiday_name'  	   => get_post_meta($ajaxpost->ID,'holiday_name', true),
				'holiday_description'  => get_post_meta($ajaxpost->ID,'holiday_description', true),
				'holiday_date'  	   => get_post_meta($ajaxpost->ID,'holiday_date', true)
			));
		}

		$data = $obj;

	    echo json_encode( $data );
	    exit;
	}

	public function service_calendar_holiday() {
		$name = 'service_calendar_holiday';
		$uid  = uniqid();
		$id   = wp_insert_post(array(
				  'post_title'  => 'Calendar Holiday', 
				  'post_type'   => 'calendar_holiday',
				  'post_status' => 'publish',
				  'meta_input' => array( 
				        'holiday_name' => '',
				        'holiday_description'   => '',
				        'holiday_date'   => ''
				   ),
				));			  
	?>
	<tr>
					    	<td>
					    		<input name="holiday_name_<?php echo esc_attr( $id ) ?>" id="holiday_name_<?php echo esc_attr( $id ) ?>" type="text" style="width:100%">
					    	</td>
					    	<td>
					    		<input name="holiday_description_<?php echo esc_attr( $id ) ?>" id="holiday_description_<?php echo esc_attr( $id ) ?>" type="text" style="width:100%">
					    	</td>
					    	<td style="padding:0 30px">
					    		<div class="provider_holidays">
					    			<div class="holiday">
					    				<input type="text" class="holiday_date" name="holiday_date_<?php echo esc_attr( $id ) ?>" id="holiday_date_<?php echo esc_attr( $id ) ?>" placeholder="Select date">
					    			</div>
					    		</div>
					    	</td>
					    	<td>
					    		<div class="slot-delete" id="<?php echo esc_attr( $id ); ?>">Delete</div><div style="display: inline-flex;margin-left: 5px;">
			<button class="save-slot button button-primary" id="<?php echo esc_attr( $id ); ?>" type="submit">Save</button>
		</div>
					    	</td>
					    </tr>							    	
	<?php
		wp_die();
	}

	public function save_holiday() {

		$id = sanitize_key( $_POST['id'] );

		update_post_meta($id, 'holiday_name', sanitize_text_field( $_POST['holiday_name'] ) );
		update_post_meta($id, 'holiday_description', sanitize_text_field( $_POST['holiday_description'] ) );
		update_post_meta($id, 'holiday_date', sanitize_text_field( $_POST['holiday_date'] ) );

		header( "Content-Type: application/json" );
		echo json_encode( array(
			'success'  	 => true,
			'id'       	 => $id,
		) );
		exit;
	}

	public function service_delete_holiday() {
		wp_delete_post( sanitize_key( $_POST['id'] ) );
	}

	function b60_create_payment_form_post() {
		$name             = sanitize_text_field( $_POST['form_name'] );
		$title            = sanitize_text_field( $_POST['form_title'] );
		$amount           = isset( $_POST['form_amount'] ) ? sanitize_text_field( $_POST['form_amount'] ) : '0';
		$custom           = sanitize_text_field( $_POST['form_custom'] );
		$buttonTitle      = sanitize_text_field( $_POST['form_button_text'] );
		$showButtonAmount = sanitize_text_field( $_POST['form_button_amount'] );
		$showEmailInput   = sanitize_text_field( $_POST['form_show_email_input'] );
		$showCustomInput  = sanitize_text_field( $_POST['form_include_custom_input'] );
		$customInputTitle = isset( $_POST['form_custom_input_label'] ) ? sanitize_text_field( $_POST['form_custom_input_label'] ) : '';
		$doRedirect       = sanitize_text_field( $_POST['form_do_redirect'] );
		$redirectTo           = isset( $_POST['form_redirect_to'] ) ? sanitize_text_field( $_POST['form_redirect_to'] ) : null;
		$redirectToHelp           = isset( $_POST['form_redirect_to_help'] ) ? sanitize_text_field( $_POST['form_redirect_to_help'] ) : null;
		$redirectPostID       = isset( $_POST['form_redirect_page_or_post_id'] ) ? sanitize_text_field( $_POST['form_redirect_page_or_post_id'] ) : 0;
		$redirectPostIDHelp       = isset( $_POST['form_redirect_page_or_post_id_help'] ) ? sanitize_text_field( $_POST['form_redirect_page_or_post_id_help'] ) : 0;
		$redirectUrl          = isset( $_POST['form_redirect_url'] ) ? sanitize_text_field( $_POST['form_redirect_url'] ) : null;
		$redirectUrlHelp          = isset( $_POST['form_redirect_url_help'] ) ? sanitize_text_field( $_POST['form_redirect_url_help'] ) : null;
		$redirectToPageOrPost = 1;
		$redirectToPageOrPostHelp = 1;
		if ( 'page_or_post' === $redirectTo ) {
			$redirectToPageOrPost = 1;
		} else if ( 'url' === $redirectTo ) {
			$redirectToPageOrPost = 0;
		}
		if ( 'page_or_post_help' === $redirectToHelp ) {
			$redirectToPageOrPostHelp = 1;
		} else if ( 'url' === $redirectToHelp ) {
			$redirectToPageOrPostHelp = 0;
		}
		$showAddressInput = sanitize_text_field( $_POST['form_show_address_input'] );
		$sendEmailReceipt = isset( $_POST['form_send_email_receipt'] ) ? sanitize_text_field( $_POST['form_send_email_receipt'] ) : 0;
		$formStyle        = sanitize_text_field( $_POST['form_style'] );

		$data = array(
			'name'                 => $name,
			'formTitle'            => $title,
			'amount'               => $amount,
			'customAmount'         => $custom,
			'buttonTitle'          => $buttonTitle,
			'showButtonAmount'     => $showButtonAmount,
			'showEmailInput'       => $showEmailInput,
			'showCustomInput'      => $showCustomInput,
			'customInputTitle'     => $customInputTitle,
			'redirectOnSuccess'    => $doRedirect,
			'redirectPostID'       => $redirectPostID,
			'redirectUrl'          => $redirectUrl,
			'redirectUrlHelp'      => $redirectUrlHelp,
			'redirectToPageOrPost' => $redirectToPageOrPost,
			'redirectToPageOrPostHelp' => $redirectToPageOrPostHelp,
			'showAddress'          => $showAddressInput,
			'sendEmailReceipt'     => $sendEmailReceipt,
			'formStyle'            => $formStyle
		);

		$this->db->insert_payment_form( $data );

		header( "Content-Type: application/json" );
		echo json_encode( array(
			'success'     => true,
			//'redirectURL' => admin_url( 'admin.php?page=b60-payments-f&tab=forms' )
		) );
		exit;
	}

	function b60_edit_payment_form_post() {
		$id               = is_numeric( $_POST['formID'] );
		$name             = 'default';
		$title            = sanitize_text_field( $_POST['form_title'] );
		$amount           = isset( $_POST['form_amount'] ) ? sanitize_text_field( $_POST['form_amount'] ) : '0';
		$custom           = 1;
		$buttonTitle      = sanitize_text_field( $_POST['form_button_text'] );
		$showButtonAmount = sanitize_text_field( $_POST['form_button_amount'] );
		$showEmailInput   = sanitize_text_field( $_POST['form_show_email_input'] );
		$showCustomInput  = sanitize_text_field( $_POST['form_include_custom_input'] );
		$customInputTitle = isset( $_POST['form_custom_input_label'] ) ? sanitize_text_field( $_POST['form_custom_input_label'] ) : '';
		$doRedirect       = sanitize_text_field( $_POST['form_do_redirect'] );
		$redirectTo           = isset( $_POST['form_redirect_to'] ) ? sanitize_text_field( $_POST['form_redirect_to'] ) : null;
		$redirectToHelp           = isset( $_POST['form_redirect_to_help'] ) ? sanitize_text_field( $_POST['form_redirect_to_help'] ) : null;
		$redirectLeadTo           = isset( $_POST['form_redirect_lead_to'] ) ? sanitize_text_field( $_POST['form_redirect_lead_to'] ) : null;
		$redirectLeadID       = isset( $_POST['form_redirect_lead_to_page_id'] ) ? sanitize_text_field( $_POST['form_redirect_lead_to_page_id'] ) : 0;
		$redirectPostID       = isset( $_POST['form_redirect_page_or_post_id'] ) ? sanitize_text_field( $_POST['form_redirect_page_or_post_id'] ) : 0;
		$redirectPostIDHelp       = isset( $_POST['form_redirect_page_or_post_id_help'] ) ? sanitize_text_field( $_POST['form_redirect_page_or_post_id_help'] ) : 0;
		$redirectUrl          = isset( $_POST['form_redirect_url'] ) ? sanitize_text_field( $_POST['form_redirect_url'] ) : null;
		$redirectUrlHelp          = isset( $_POST['form_redirect_url_help'] ) ? sanitize_text_field( $_POST['form_redirect_url_help'] ) : null;
		$redirectLeadToPage = 1;
		$redirectToPageOrPost = 1;
		$redirectToPageOrPostHelp = 1;
		if ( 'page_or_post' === $redirectTo ) {
			$redirectToPageOrPost = 1;
		} else if ( 'url' === $redirectTo ) {
			$redirectToPageOrPost = 0;
		}
		if ( 'page_or_post_help' === $redirectToHelp ) {
			$redirectToPageOrPostHelp = 1;
		} else if ( 'url' === $redirectToHelp ) {
			$redirectToPageOrPostHelp = 0;
		}
		if ( 'lead_to_page' === $redirectLeadTo ) {
			$redirectLeadToPage = 1;
		} else if ( 'lead_to_steps' === $redirectLeadTo ) {
			$redirectLeadToPage = 0;
		}
		$showAddressInput = sanitize_text_field( $_POST['form_show_address_input'] );
		$sendEmailReceipt = isset( $_POST['form_send_email_receipt'] ) ? sanitize_text_field( $_POST['form_send_email_receipt'] ) : 0;
		$formStyle        = $redirectLeadToPage;

		$data = array(
			'name'                 => $name,
			'formTitle'            => $title,
			'amount'               => $amount,
			'customAmount'         => $custom,
			'buttonTitle'          => $buttonTitle,
			'showButtonAmount'     => $showButtonAmount,
			'showEmailInput'       => $showEmailInput,
			'showCustomInput'      => $showCustomInput,
			'customInputTitle'     => $customInputTitle,
			'redirectOnSuccess'    => $doRedirect,
			'redirectLeadID'       => $redirectLeadID,
			'redirectPostID'       => $redirectPostID,
			'redirectPostIDHelp'       => $redirectPostIDHelp,
			'redirectUrl'          => $redirectUrl,
			'redirectUrlHelp'          => $redirectUrlHelp,
			'redirectToPageOrPost' => $redirectToPageOrPost,
			'redirectToPageOrPostHelp' => $redirectToPageOrPostHelp,
			'redirectLeadToPage'   => $redirectLeadToPage,
			'showAddress'          => $showAddressInput,
			'sendEmailReceipt'     => $sendEmailReceipt,
			'formStyle'            => $formStyle
		);

		$this->db->update_payment_form( $id, $data );

		$options                        = get_option( 'b60_settings_option' );
		$options['lead_to_crm']         = sanitize_text_field( $_POST['lead_to_crm'] );
		$options['booking_to_crm']      = sanitize_text_field( $_POST['booking_to_crm'] );
		$options['enable_help_link']    = sanitize_text_field( $_POST['enable_help_link'] );
		$options['help_link_page_post'] = $redirectToPageOrPostHelp;
		$options['help_link_url']       = $redirectUrlHelp;

		update_option( 'b60_settings_option', $options );

		header( "Content-Type: application/json" );
		echo json_encode( array(
			'success'     => true,
			'redirectURL' => admin_url( 'admin.php?page=b60-settings&tab=form-settings' )
		) );
		exit;
	}

	function update_general_settings() {
		$options                    = get_option( 'b60_settings_option' );
		$options['industry'] = sanitize_text_field( $_POST['industry'] );
		$options['business_name']  = sanitize_text_field( $_POST['business_name'] );
		$options['email_from'] = sanitize_text_field( $_POST['email_from'] );
		$options['enable_payment_method']        = sanitize_text_field( $_POST['enable_payment_method'] );
		$options['payment_method']   = sanitize_text_field( $_POST['payment_method'] );
		$options['publishKey_test'] = sanitize_text_field( $_POST['publishKey_test'] );
		$options['secretKey_test']  = sanitize_text_field( $_POST['secretKey_test'] );
		$options['publishKey_live'] = sanitize_text_field( $_POST['publishKey_live'] );
		$options['secretKey_live']  = sanitize_text_field( $_POST['secretKey_live'] );
		$options['apiMode']         = sanitize_text_field( $_POST['apiMode'] );
		$options['currency']        = sanitize_text_field( $_POST['currency'] );
		$options['enable_sales_tax']        = sanitize_text_field( $_POST['enable_sales_tax'] );
		$options['sales_tax']        = sanitize_text_field( $_POST['sales_tax'] );
		$options['sales_tax_applied']        = sanitize_text_field( $_POST['sales_tax_applied'] );
		$options['includeStyles']   = sanitize_text_field( $_POST['includeStyles'] );

		update_option( 'b60_settings_option', $options );

		header( "Content-Type: application/json" );
		echo json_encode( array( 'success' => true ) );
		exit;
	}

	

	function update_email_notifications() {
		$options                = get_option( 'b60_email_notifications_option' );
		$options['enable_lead_notification']  = sanitize_text_field( $_POST['enable_lead_notification'] );
		$options['enable_lead_notification_admin']  = sanitize_text_field( $_POST['enable_lead_notification_admin'] );
		$options['lead_notification']  = sanitize_text_field(stripslashes($_POST['lead_notification']));
		$options['enable_booking_notification']  = sanitize_text_field( $_POST['enable_booking_notification'] );
		$options['enable_booking_notification_admin']  = sanitize_text_field( $_POST['enable_booking_notification_admin'] );
		$options['booking_notification']  = sanitize_text_field(stripslashes($_POST['booking_notification']));

		update_option( 'b60_email_notifications_option', $options );

		header( "Content-Type: application/json" );
		echo json_encode( array( 'success' => true, 'content' => $options['lead_notification']) );
		exit;
	}

	function b60_stripe_update_settings() {
		$options                    = get_option( 'b60_options_f' );
		$options['publishKey_test'] = sanitize_text_field( $_POST['publishKey_test'] );
		$options['secretKey_test']  = sanitize_text_field( $_POST['secretKey_test'] );
		$options['publishKey_live'] = sanitize_text_field( $_POST['publishKey_live'] );
		$options['secretKey_live']  = sanitize_text_field( $_POST['secretKey_live'] );
		$options['apiMode']         = sanitize_text_field( $_POST['apiMode'] );
		$options['currency']        = sanitize_text_field( $_POST['currency'] );
		$options['includeStyles']   = sanitize_text_field( $_POST['includeStyles'] );

		update_option( 'b60_options_f', $options );

		header( "Content-Type: application/json" );
		echo json_encode( array( 'success' => true ) );
		exit;
	}

	function b60_delete_payment_form() {
		$id = is_numeric( $_POST['id'] );
		do_action( 'b60_admin_delete_payment_form_action', $id );

		$this->db->delete_payment_form( $id );

		header( "Content-Type: application/json" );
		echo json_encode( array( 'success' => true ) );
		exit;
	}

	function save_lead_formbuilder() {

		//$data = filter_input( INPUT_POST, $_POST['data'], FILTER_SANITIZE_STRING );
		
		update_option( 'b60_lead_formbuilder', sanitize_text_field( $_POST['data'] ) );

		header( "Content-Type: application/json" );
		echo json_encode( array( 'success' => true ) );
		exit;
	}

	function save_booking_formbuilder() {
		
		update_option( 'b60_booking_formbuilder', sanitize_text_field( $_POST['data'] ) );

		header( "Content-Type: application/json" );
		echo json_encode( array( 'success' => true ) );
		exit;
	}

	function send_test_email() {

		$email_notification_option = get_option( 'b60_email_notifications_option' );

		$message = $email_notification_option['booking_notification'];
		$fields = array(
			'%customer_first_name%',
			'%customer_last_name%',
			'%customer_phone%',
			'%customer_email%',
			'%frequency%',
		);

		$content = preg_replace_callback($fields, array( $this, 'replaceBookingTag' ), $message );

		$tag = str_replace( '%', '', $content );

		header( "Content-Type: application/json" );
		echo json_encode( array( 'success' => true, 'result' => $tag) );
		exit;
	}

	function email_lead_confirmation() {
		$email_notification_option = get_option( 'b60_email_notifications_option' );
		$message = $email_notification_option['lead_notification'];
		$fields = array(
			'%customer_first_name%',
			'%customer_last_name%',
			'%customer_phone%',
			'%customer_email%',
		);

		$booking_fields = sanitize_user_object( $_POST['fields'] );
		$customer_email = $booking_fields['email'];

		$content = preg_replace_callback($fields, array( $this, 'replaceLeadTag' ), $message );

		$tag = str_replace( '%', '', $content );

		$options = get_option( 'b60_settings_option' );
		$email_from = $options['email_from'];
		$headers[] = 'From: '.$email_from; 
		$headers[] = 'Content-Type: text/html; charset=UTF-8';

		if( $email_notification_option['enable_lead_notification'] == 1 ) {			 
			$result = wp_mail( $customer_email, 'Customer Details', $tag, $headers );
		} 

		if( $email_notification_option['enable_lead_notification_admin'] == 1 ) {			 
			$result = wp_mail( $email_from, 'Lead Notification', $tag, array('Content-Type: text/html; charset=UTF-8') );
		}

		header( "Content-Type: application/json" );
		echo json_encode( array( 'success' => true, 'customer_email' => $booking_fields, 'email_from' => $email_from, 'notification' => $email_notification_option['enable_lead_notification_admin'],'result' => $result) );
		exit;
	}

	function replaceLeadTag( $match ){

		$tag = str_replace( '%', '', $match[0] );
		$booking_fields = sanitize_user_object( $_POST['fields'] );

		$customer_first_name = filter_input( INPUT_POST, trim( $booking_fields['first_name'] ), FILTER_SANITIZE_STRING );
		$customer_last_name = filter_input( INPUT_POST, trim( $booking_fields['last_name'] ), FILTER_SANITIZE_STRING );
		$customer_phone = filter_input( INPUT_POST, trim( $booking_fields['phone'] ), FILTER_SANITIZE_STRING );
		$customer_email = filter_input( INPUT_POST, trim( $booking_fields['email'] ), FILTER_SANITIZE_STRING );

		$replaceText = '';

		switch ( $tag ) {
			case 'customer_first_name': 
				$replaceText = $customer_first_name;
				break;

			case 'customer_last_name': 
				$replaceText = $customer_last_name;
				break;

			case 'customer_phone': 
				$replaceText = $customer_phone;
				break;

			case 'customer_email': 
				$replaceText = $customer_email;
				break;

			case 'customer_phone': 
				$replaceText = $customer_phone;
				break;
		}

		return $replaceText;
	}

	function email_booking_confirmation() {

		$email_notification_option = get_option( 'b60_email_notifications_option' );
		$message = $email_notification_option['booking_notification'];
		$booking_fields = sanitize_user_object( $_POST['data'] );
		$customer_email = $booking_fields['customer_info']['email'];

		$fields = array(
			'%selected_service%', 
			'%customer_first_name%',
			'%customer_last_name%',
			'%customer_phone%',
			'%customer_email%',
			'%customer_address%',
			'%customer_apartment%',
			'%customer_city%',
			'%customer_state%',
			'%customer_zip%',
			'%frequency%', 
			'%booking_service_date%', 
			'%booking_service_time%',
			'%subtotal%',
			'%discount%',
			'%sales_tax%',
			'%total_sales%',
		);

		$content = preg_replace_callback($fields, array( $this, 'replaceBookingTag' ), $message );

		$tag = str_replace( '%', '', $content );

		$options = get_option( 'b60_settings_option' );
		$email_from = $options['email_from'];
		$headers[] = 'From: '.$email_from; 
		$headers[] = 'Content-Type: text/html; charset=UTF-8'; 

		if( $email_notification_option['enable_booking_notification'] == 1 ) {			 
			$result = wp_mail( $customer_email, 'Booking Confirmation', $tag, $headers );
		} 

		header( "Content-Type: application/json" );
		echo json_encode( array( 'success' => true, 'email_from' => $email_from, 'notification' => $email_notification_option['enable_booking_notification_admin'],'result' => $result) );
		exit;
	}

	function email_booking_confirmation_admin() {

		$email_notification_option = get_option( 'b60_email_notifications_option' );
		$message = $email_notification_option['booking_notification'];
		$booking_fields = sanitize_user_object( $_POST['data'] );
		$customer_email = $booking_fields['customer_info']['email'];

		$fields = array(
			'%selected_service%', 
			'%customer_first_name%',
			'%customer_last_name%',
			'%customer_phone%',
			'%customer_email%',
			'%customer_address%',
			'%customer_apartment%',
			'%customer_city%',
			'%customer_state%',
			'%customer_zip%',
			'%frequency%', 
			'%booking_service_date%', 
			'%booking_service_time%',
			'%subtotal%',
			'%discount%',
			'%sales_tax%',
			'%total_sales%',
		);

		$content = preg_replace_callback($fields, array( $this, 'replaceBookingTag' ), $message );

		$tag = str_replace( '%', '', $content );

		$options = get_option( 'b60_settings_option' );
		$email_from = $options['email_from'];
		$headers[] = 'From: '.$email_from; 
		$headers[] = 'Content-Type: text/html; charset=UTF-8'; 

		if( $email_notification_option['enable_booking_notification_admin'] == 1 ) {			 
			$result = wp_mail( $email_from, 'Booking Notification', $tag, array('Content-Type: text/html; charset=UTF-8') );
		}

		header( "Content-Type: application/json" );
		echo json_encode( array( 'success' => true, 'email_from' => $email_from, 'notification' => $email_notification_option['enable_booking_notification_admin'],'result' => $result) );
		exit;
	}

	function replaceBookingTag( $match ){

		$tag = str_replace( '%', '', $match[0] );
		$booking_fields = sanitize_user_object( $_POST['data'] );

		$customer_first_name = filter_input( INPUT_POST, trim( $booking_fields['customer_info']['first_name'] ), FILTER_SANITIZE_STRING );
		$customer_last_name = filter_input( INPUT_POST, trim( $booking_fields['customer_info']['last_name'] ), FILTER_SANITIZE_STRING );
		$customer_phone = filter_input( INPUT_POST, trim( $booking_fields['customer_info']['phone'] ), FILTER_SANITIZE_STRING );
		$customer_email = filter_input( INPUT_POST, trim( $booking_fields['customer_info']['email'] ), FILTER_SANITIZE_STRING );
		$customer_address = filter_input( INPUT_POST, trim( $booking_fields['customer_info']['address'] ), FILTER_SANITIZE_STRING );
		$customer_apartment = filter_input( INPUT_POST, trim( $booking_fields['customer_info']['apartment'] ), FILTER_SANITIZE_STRING );
		$customer_city = filter_input( INPUT_POST, trim( $booking_fields['customer_info']['city'] ), FILTER_SANITIZE_STRING );
		$customer_state = filter_input( INPUT_POST, trim( $booking_fields['customer_info']['state'] ), FILTER_SANITIZE_STRING );
		$customer_zip = filter_input( INPUT_POST, trim( $booking_fields['customer_info']['zip'] ), FILTER_SANITIZE_STRING );
		$booking_service_date = filter_input( INPUT_POST, trim( $booking_fields['appointment_schedule']['date'] ), FILTER_SANITIZE_STRING );
		$booking_service_time = filter_input( INPUT_POST, trim( $booking_fields['appointment_schedule']['time'] ), FILTER_SANITIZE_STRING );
		$frequency = filter_input( INPUT_POST, trim( $booking_fields['frequency_discount'] ), FILTER_SANITIZE_STRING );
		$frequency_type = filter_input( INPUT_POST, trim( $booking_fields['frequency_type'] ), FILTER_SANITIZE_STRING );
		$subtotal = filter_input( INPUT_POST, trim( $booking_fields['total_summary']['subtotal'] ), FILTER_SANITIZE_STRING );
		$discount = filter_input( INPUT_POST, trim( $booking_fields['total_summary']['discount'] ), FILTER_SANITIZE_STRING );
		$sales_tax = filter_input( INPUT_POST, trim( $booking_fields['total_summary']['sales_tax'] ), FILTER_SANITIZE_STRING );
		$total_sales = filter_input( INPUT_POST, trim( $booking_fields['total_summary']['total_sales'] ), FILTER_SANITIZE_STRING );
		$giftcard_amount = filter_input( INPUT_POST, trim( $booking_fields['giftcard_amount'] ), FILTER_SANITIZE_STRING );
		$giftcard_recipient = filter_input( INPUT_POST, trim( $booking_fields['recipient'] ), FILTER_SANITIZE_STRING );
		$giftcard_recipient_email = filter_input( INPUT_POST, trim( $booking_fields['recipient_email'] ), FILTER_SANITIZE_STRING );
		$giftcard_sender = filter_input( INPUT_POST, trim( $booking_fields['sender'] ), FILTER_SANITIZE_STRING );
		$giftcard_sender_email = filter_input( INPUT_POST, trim( $booking_fields['sender_email'] ), FILTER_SANITIZE_STRING );
		$giftcard_message = filter_input( INPUT_POST, trim( $booking_fields['giftcard_message'] ), FILTER_SANITIZE_STRING );
		$coupon_code = filter_input( INPUT_POST, trim( $booking_fields['giftcard_code'] ), FILTER_SANITIZE_STRING );
		$giftcard_date_purchase = filter_input( INPUT_POST, trim( $booking_fields['giftcard_date_purchase'] ), FILTER_SANITIZE_STRING );

		$replaceText = '';

		switch ( $tag ) {
			case 'selected_service': 
				foreach($booking_fields['selected_service'] as $key => $value) {
					$selected_service .= $value['title'] .'    $'.$value['price'] .'<br/>';
				}
				$replaceText = $selected_service;
				break;

			case 'customer_first_name': 
				$replaceText = $customer_first_name;
				break;

			case 'customer_last_name': 
				$replaceText = $customer_last_name;
				break;

			case 'customer_phone': 
				$replaceText = $customer_phone;
				break;

			case 'customer_email': 
				$replaceText = $customer_email;
				break;

			case 'customer_phone': 
				$replaceText = $customer_phone;
				break;

			case 'customer_address': 
				$replaceText = $customer_address;
				break;

			case 'customer_apartment': 
				$replaceText = $customer_apartment;
				break;

			case 'customer_city': 
				$replaceText = $customer_city;
				break;

			case 'customer_state': 
				$replaceText = $customer_state;
				break;

			case 'customer_zip': 
				$replaceText = $customer_zip;
				break;

			case 'frequency':
				
				$search = preg_replace('/[^0-9]/', '', $frequency); 

				if($frequency_type == 'amount_discount') {
					$outputString = $frequency; 
				} else {
					$outputString = str_replace( $search, $search.' percent', $frequency );
				}
				
				$replaceText = $outputString;
				break;

			case 'booking_service_date':
				$replaceText = $booking_service_date;
				break;

			case 'booking_service_time':
				$replaceText = $booking_service_time;
				break;

			case 'subtotal':
				$replaceText = '$'.$subtotal;
				break;

			case 'discount':
				$replaceText = '$'.$discount;
				break;

			case 'sales_tax':
				$replaceText = '$'.$sales_tax;
				break;

			case 'total_sales':
				$replaceText = '$'.$total_sales;
				break;

			case 'giftcard_amount':
				$replaceText = $giftcard_amount;
				break;

			case 'giftcard_recipient':
				$replaceText = $giftcard_recipient;
				break;

			case 'giftcard_recipient_email':
				$replaceText = $giftcard_recipient_email;
				break;

			case 'giftcard_sender':
				$replaceText = $giftcard_sender;
				break;

			case 'giftcard_sender_email':
				$replaceText = $giftcard_sender_email;
				break;

			case 'giftcard_message':
				$replaceText = $giftcard_message;
				break;

			case 'coupon_code':
				$replaceText = $coupon_code;
				break;

			case 'giftcard_date_purchase':
				$replaceText =  date("F j, Y, g:i a");
				break;
		}

		return $replaceText;

	}

}
