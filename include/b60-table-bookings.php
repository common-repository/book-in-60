<?php

class B60_Payments_Table extends WP_List_Table {	

	function __construct() {
		parent::__construct( array(
			'singular' => 'Payment', //Singular label
			'plural'   => 'Payments', //plural label, also this well be one of the table css class
			'ajax'     => false //We won't support Ajax for this table
		) );

		add_filter('set-screen-option', 'set_screen_option', 10, 3);
	}


	function set_screen_option($status, $option, $value) {
		if ( 'per_page' == $option ) return $value;
	}

	/*
	 * Add extra markup in the toolbars before or after the list
	 *
	 * @param string $which , helps you decide if you add the markup after (bottom) or before (top) the list
	 */

	function extra_tablenav($which) {
    if ($which == "top") {
        echo '<a class="button button-primary" href="' . admin_url('admin-post.php?action=booking.csv') . '">' . __('Export to CSV') . '</a>';
    }
}

	function column_cb($item){
	    return sprintf(
	        '<input type="checkbox" name="Payment[]" value="%1$s" />',
	        /*$1%s*/ $item->paymentID                //The value of the checkbox should be the record's id
	    );
	}

	function get_bulk_actions() {
        $actions = array(
            'delete-bulk'    => 'Delete'
        );
        return $actions;
    }

    /**
	 * Output the headers to trigger a download.
	 */
	protected function csv_headers() {
		header( 'Content-Type: text/csv; charset=utf-8' );
		header('Content-Disposition: inline; filename="users_'.date('Y_m_d_H_i_s').'.csv"');    
	}
    
    function process_bulk_action() {

    	global $wpdb;

    	$table_name = $wpdb->prefix . 'b60_payments';

        // security check!
        if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {

            $nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
            $action = 'bulk-' . $this->_args['plural'];

            if ( ! wp_verify_nonce( $nonce, $action ) )
                wp_die( 'Nope! Security check failed!' );

        }

        $action = $this->current_action();

        switch ( $action ) {

            case 'delete-single':                

                if ('delete-single' === $this->current_action()) {
                    $ids = isset($_REQUEST['Payment']) ? intval( $_REQUEST['Payment'] ) : array();
                    if (is_array($ids)) $ids = implode(',', $ids);
                    if (!empty($ids)) {
                    	                        
                        $wpdb->query($wpdb->prepare( "DELETE FROM $table_name WHERE paymentID IN($ids)") );
                    }
                }            

                break;

            case 'delete-bulk':

            	if ('delete-bulk' === $this->current_action()) {
            	    $ids = isset($_REQUEST['Payment']) ? array_map('sanitize_text_field', $_REQUEST['Payment'] ) : array();
            	    if (is_array($ids)) $ids = implode(',', $ids);
            	    if (!empty($ids)) {
            	        $wpdb->query($wpdb->prepare( "DELETE FROM $table_name WHERE paymentID IN($ids)") );
            	    }
            	} 

                break;
                
            default:
                // do nothing or something else
                return;
                break;
        }

        return;
    }

    

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 */
	function prepare_items() {
		global $wpdb;
		$screen = get_current_screen();

		$this->process_bulk_action();

		if( isset($_GET['s']) ){

			if( $_GET['s'] != NULL ){
				
				$search = sanitize_text_field( trim($_GET['s']) );

				$query = "SELECT * FROM " . $wpdb->prefix . 'b60_payments' . " WHERE CONCAT(eventID, ' ',
                  description, ' ',
                  address, ' ',
                  amount) LIKE '" . $search . "'";

			}

	    } else {
	        $query = "SELECT * FROM " . $wpdb->prefix . 'b60_payments';
	    }	

	    	//Parameters that are going to be used to order the result
			$orderby = ! empty( $_REQUEST["orderby"] ) ? sanitize_sql_orderby( $_REQUEST["orderby"] ) : 'schedule_date';
			$order   = ! empty( $_REQUEST["order"] ) ? sanitize_sql_orderby( $_REQUEST["order"] ) : ( empty( $_REQUEST['orderby'] ) ? 'DESC' : 'ASC' );
			if ( ! empty( $orderby ) && ! empty( $order ) ) {
				$query .= ' ORDER BY ' . $orderby . ' ' . $order;
			}

			// get the current user ID
			$user = get_current_user_id();
			// get the current admin screen
			$screen = get_current_screen();
			// retrieve the "perpage" option
			$screen_option = $screen->get_option('per_page', 'option');
			// retrieve the value of the option stored for the current user
			$perpage = get_user_meta($user, $screen_option, true);

			//var_dump($perpage);


			//Number of elements in your table?
			$totalitems = $wpdb->query( $wpdb->prepare( $query ) ); //return the total number of affected rows
			//How many to display per page?
			$perpage = 30;
			//Which page is this?
			
			if ( empty ( $perpage) || $perpage < 1 ) {
				// get the default value if none is set
				$perpage = $screen->get_option( 'per_page', 'default' );
			} 
			// now use $perpage to set the number of items displayed

			$paged = ! empty( $_GET["paged"] ) ? sanitize_key( $_GET["paged"] ) : '';
			//Page Number
			if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
				$paged = 1;
			}
			//How many pages do we have in total?
			$totalpages = ceil( $totalitems / $perpage );
			//adjust the query to take pagination into account
			if ( ! empty( $paged ) && ! empty( $perpage ) ) {
				$offset = ( $paged - 1 ) * $perpage;
				$query .= ' LIMIT ' . (int) $offset . ',' . (int) $perpage;
			}

			// Register the pagination
			$this->set_pagination_args( array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page"    => $perpage,
			) );
			//The pagination links are automatically built according to those parameters

			//Register the Columns
			$columns               = $this->get_columns();
			$hidden                = array();
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );

			// Fetch the items
			$this->items = $wpdb->get_results( $wpdb->prepare( $query ) );
			
	}

	/**
	 * Define the columns that are going to be used in the table
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns() {
		return $columns = array(	
			'cb'          => '<input type="checkbox" />',			
			'service_date' => __( 'Schedule Date', 'bookin60' ),
			'customer'        => __( 'Customer', 'bookin60' ),
			'service_location'     => __( 'Service Location', 'bookin60' ),
			'frequency'      => __( 'Frequency', 'bookin60' ),
			'view'      => __( '', 'bookin60' )
		);
	}

	/**
	 * Decide which columns to activate the sorting functionality on
	 * @return array $sortable, the array of columns that can be sorted by the user
	 */
	public function get_sortable_columns() {
		return $sortable = array(
			'service_date'    => array( 'schedule_date', false ),
			'customer'  => array( 'customer', false ),
			'service_location'     => array( 'service_location', false ),
			'frequency' => array( 'frequency', false )
		);
	}

	function display_rows() {
		//Get the records registered in the prepare_items method
		$records = $this->items;

		//Get the columns registered in the get_columns and get_sortable_columns methods
		list( $columns, $hidden ) = $this->get_column_info();

		//Get the correct currency symbol to use
		$options        = get_option( 'b60_settings_option' );
		$currencySymbol = B60::get_currency_symbol_for( $options['currency'] );
		global $wpdb;

		//Loop for each record
		if ( ! empty( $records ) ) {
			foreach ( $records as $rec ) {

				$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "b60_lead_entries WHERE leadID=%d", intval( $rec->customerID ) ) );

				//Open the line
				echo '<tr id="record_' . intval( $rec->paymentID ) . '">';
				foreach ( $columns as $column_name => $column_display_name ) {
					//Style attributes for each col
					$class = "class=column-$column_name";
					$style = "";
					if ( in_array( $column_name, $hidden ) ) {
						$style = ' style="display:none;"';
					}
					$attributes = $class . $style;

					$actions = array(
					    //'view'      => sprintf('<a href="?page=%s&bookingID=%s">View Booking</a>',$_REQUEST['page'],$rec->paymentID),
					    'delete-single'    => sprintf('<a href="?page=%s&action=%s&Payment=%s">Delete</a>', sanitize_key( $_REQUEST['page'] ),'delete-single', sanitize_key( $rec->paymentID ) ),
					);


					//Display the cell
					switch ( $column_name ) {
						case "cb":
							echo '<th class="check-column"><input type="checkbox" name="Payment[]" value="'.intval($rec->paymentID).'" /></th>';
							break;
						case "service_date":
							echo '<td ' . esc_attr($attributes) . '>' . esc_html( $rec->schedule_date ) .' / '. esc_html( $rec->schedule_time ) . wp_kses_post($this->row_actions($actions)).'</td>';
							break;
						case "customer":
							echo '<td ' . esc_attr($attributes) . '><span style="text-transform:capitalize">'.esc_html( $customer->first_name ).'</span> <span style="text-transform:capitalize">'.esc_html( $customer->last_name ).'</span></td>';
							break;
						case "service_location":
							$address = $this->format_address( $rec );
							echo '<td ' . esc_attr($attributes) . '>' . esc_html( $address ) . '</td>';
							break;
						case "frequency":
							echo '<td ' . esc_attr($attributes) . '>' . esc_html( $rec->frequency ) .'</td>';
							break;						
						case "created":
							echo '<td ' . esc_attr($attributes) . '>' . esc_html( date( 'F jS Y H:i', strtotime( $rec->created ) ) ) . '</td>';
							break;

						case "view":
							echo '<td ' . esc_attr($attributes) . ' style="text-align:center"><a class="button button-primary" href="?page=b60-bookings&bookingID=' .intval( $rec->paymentID ). '">View Booking</a></td>';
							break;
					}
				}

				//Close the line
				echo '</tr>';
			}
		}
	}

	private function format_address( $rec ) {
		if ( $rec->address == "" ) {
			return "";
		}

		$address = $rec->apartment == "" ? "" : esc_html( $rec->apartment );
		$address .= $rec->address == "" ? "" : " ".esc_html( $rec->address );
		$address .= $rec->city == "" ? "" : ", ".esc_html( $rec->city );
		$address .= $rec->state == "" ? "" : ", ".esc_html( $rec->state );
		$address .= $rec->zip == "" ? "" : ", ".esc_html( $rec->zip );

		return $address;

	}

	/**
	 * Output the CSV header row.
	 */
	protected function print_column_headers_csv() {
		list( $columns, , , ) = $this->get_column_info();
		$headers = array();
		foreach ( $columns as $column_key => $column_display_name ) {
			if ( in_array( $column_key, $this->hidden_columns_csv() ) ||
				'cb' === $column_key ) {
				continue;
			}
			$headers[] = $column_display_name;
		}
		$this->put_csv( $headers );
	}
}