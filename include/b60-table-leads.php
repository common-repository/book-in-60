<?php

class B60_Leads_Table extends WP_List_Table {
	function __construct() {
		parent::__construct( array(
			'singular' => 'Lead', //Singular label
			'plural'   => 'Leads', //plural label, also this well be one of the table css class
			'ajax'     => false //We won't support Ajax for this table
		) );
	}

	function column_cb($item){
	    return sprintf(
	        '<input type="checkbox" name="Lead[]" value="%1$s" />',
	        /*$1%s*/ $item->leadID                //The value of the checkbox should be the record's id
	    );
	}

	function get_bulk_actions() {
	        $actions = array(
	            //'export'    => 'Export',
	            'delete-bulk'    => 'Delete'
	        );
	        return $actions;
	    }

    	function extra_tablenav($which) {
	    if ($which == "top") {
	        echo '<a class="button button-primary" href="' . admin_url('admin-post.php?action=lead.csv') . '">' . __('Export to CSV') . '</a>';
	    }
	}

    	function process_bulk_action() {

	    	global $wpdb;

	    	$table_name = $wpdb->prefix . 'b60_lead_entries';

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
	                    $ids = isset($_REQUEST['Lead']) ? intval( $_REQUEST['Lead'] ) : array();	                    
	                    if (is_array($ids)) $ids = implode(',', $ids);
	                    if (!empty($ids)) {
	                        $wpdb->query($wpdb->prepare( "DELETE FROM $table_name WHERE leadID IN($ids)" ) );
	                    }
	                }            

	                break;

	            case 'delete-bulk':

	            	if ('delete-bulk' === $this->current_action()) {
	            	    $ids = isset($_REQUEST['Lead']) ? array_map('sanitize_text_field', $_REQUEST['Lead'] ) : array();
	            	    if (is_array($ids)) $ids = implode(',', $ids);
	            	    if (!empty($ids)) {
	            	        $wpdb->query($wpdb->prepare( "DELETE FROM $table_name WHERE leadID IN($ids)" ) );
	            	    }
	            	} 

	                break;

	            case 'export':
	            	
	                //wp_die( 'Export Placeholder' );
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

		if( isset($_GET['s']) ){

			if( $_GET['s'] != NULL ){
				
				$search = sanitize_text_field( trim($_GET['s']) );

				$query = "SELECT * FROM " . $wpdb->prefix . 'b60_lead_entries' . " WHERE " . 'addressLine1' . " LIKE '" . $search . "'";

			}
        } else {
                $query = "SELECT * FROM " . $wpdb->prefix . 'b60_lead_entries';
        }	

        	$this->process_bulk_action();

		//Parameters that are going to be used to order the result
		$orderby = ! empty( $_REQUEST["orderby"] ) ? sanitize_sql_orderby( $_REQUEST["orderby"] ) : 'created';
		$order   = ! empty( $_REQUEST["order"] ) ? sanitize_sql_orderby( $_REQUEST["order"] ) : ( empty( $_REQUEST['orderby'] ) ? 'DESC' : 'ASC' );
		if ( ! empty( $orderby ) && ! empty( $order ) ) {
			$query .= ' ORDER BY ' . $orderby . ' ' . $order;
		}

		//Number of elements in your table?
		$totalitems = $wpdb->query( $wpdb->prepare( $query ) ); //return the total number of affected rows
		//How many to display per page?
		$perpage = 30;
		//Which page is this?
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
			'cb' => '<input type="checkbox" />',
			'first_name'    => __( 'First Name', 'bookin60' ),
			'last_name' 	=> __( 'Last Name', 'bookin60' ),
			'email'        	=> __( 'Email', 'bookin60' ),
			'phone'    		=> __( 'Phone', 'bookin60' ),
			'created'     	=> __( 'Created Date', 'bookin60' ),
			'more_info'     => __( '', 'bookin60' )
		);
	}

	/**
	 * Decide which columns to activate the sorting functionality on
	 * @return array $sortable, the array of columns that can be sorted by the user
	 */
	public function get_sortable_columns() {
		return $sortable = array(
			'first_name'    => array( 'first_name', true ),
			'last_name'  	=> array( 'last_name', true ),
			'email'     	=> array( 'email', true ),
			'created' 	=> array( 'created', false )
		);
	}

	/**
	 * Display the rows of records in the table
	 * @return string, echo the markup of the rows
	 */
	function display_rows() {
		//Get the records registered in the prepare_items method
		$records = $this->items;

		//Get the columns registered in the get_columns and get_sortable_columns methods
		list( $columns, $hidden ) = $this->get_column_info();

		//Get the correct currency symbol to use
		$options        = get_option( 'b60_settings_option' );
		$currencySymbol = B60::get_currency_symbol_for( $options['currency'] );

		//Loop for each record
		if ( ! empty( $records ) ) {
			global $wpdb;
			foreach ( $records as $rec ) {
				//Open the line
				echo '<tr id="record_' . esc_attr($rec->leadID) . '">';
				foreach ( $columns as $column_name => $column_display_name ) {
					//Style attributes for each col
					$class = "class=column-$column_name";
					$style = "";
					if ( in_array( $column_name, $hidden ) ) {
						$style = ' style="display:none;"';
					}
					
					$attributes = $class . $style;

					$actions = array(
					    'delete-single' => sprintf('<a href="?page=%s&action=%s&Lead=%s">Delete</a>', sanitize_key( $_REQUEST['page'] ),'delete-single', sanitize_key( $rec->leadID )),
					);

					if($rec->has_booking == 1) {

						$disabled = 'disabled';
						$canBeDeleted = '';

						$booking = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "b60_payments" . " WHERE customerID=%d", intval($rec->leadID) ) );

						   echo '<div id="open-modal_' .esc_html($rec->leadID). '" class="modal-window">
							  <div class="modal-container">
							    <a href="#" title="Close" class="modal-close">Close</a>
							    <h3 class="modal-title">Customer Booking Records</h3>
							    <div class="div-table">
							                 <div class="div-table-row-header">
							                    <div class="div-table-col">Customer</div>
							                    <div  class="div-table-col">Service Date</div>
							                    <div  class="div-table-col">Service Location</div>
							                    <div  class="div-table-col">Frequency</div>
							                    <div  class="div-table-col">&nbsp;</div>
							                 </div>';

							    foreach( $booking as $key => $row) {


							    	$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "b60_lead_entries WHERE leadID=%d", intval( $rec->customerID ) ) );

							    	$bookingDetails = json_decode(stripslashes($row->booking_details));

							    	$address = $this->format_address( $row );

							    	echo '<div class="div-table-row">
							                      <div class="div-table-col">'.esc_html($customer->first_name) .' '.esc_html($customer->last_name).'</div>
							                    <div class="div-table-col">'.esc_html($row->schedule_date).'</div>
							                    <div class="div-table-col">'.esc_html($address).'</div>
							                    <div class="div-table-col">'.esc_html($row->frequency).'</div>
							                    <div class="div-table-col"><a href="?page=b60-bookings&bookingID='.intval($row->paymentID).'" class="button">View Booking</a></div>
							                </div>';

							    }

						     echo '</div>
							  </div>
							</div>';

						$view = '<a class="button button-primary" href="#open-modal_' .intval($rec->leadID). '">View Booking</a>';
					} else {
						$disabled = '';
						$view = '';
						$canBeDeleted = $this->row_actions($actions);
						$amount_redeemed = sprintf( '%0.2f', 0 / 1 );
						$balance = sprintf( '%0.2f', 0 / 1 );
					}

					//Display the cell
					switch ( $column_name ) {
						case "cb":
							echo '<th class="check-column"><input type="checkbox" name="Lead[]" value="'.intval($rec->leadID).'" '.esc_attr($disabled).'/></th>';
							break;
						case "first_name":
							echo '<td ' . esc_attr($attributes) . '>' . esc_html($rec->first_name) . wp_kses_post($canBeDeleted).'</td>';
							break;
						case "last_name":
							echo '<td ' . esc_attr($attributes) . '>' . esc_html($rec->last_name) . '</td>';
							break;
						case "email":
							echo '<td ' . esc_attr($attributes) . '>' . esc_html($rec->email) . '</td>';
							break;
						case "phone":
							echo '<td ' . esc_attr($attributes) . '>' . esc_html($rec->phone) . '</td>';
							break;
						case "created":
							echo '<td ' . esc_attr($attributes) . '>' . esc_html(date( 'F jS Y H:i', strtotime( $rec->created ) ) ) . '</td>';
							break;
						case "more_info":
							echo '<td ' . esc_attr($attributes) . ' style="text-align:center">'.wp_kses_post($view).'</td>';
							break;
					}
				}

				//Close the line
				echo '</tr>';
			}
		}
	}

	private function format_address( $row ) {
		if ( $row->address == "" ) {
			return "";
		}

		$address = $row->address . ( $row->apartment == "" ? "" : ", $row->apartment" );
		$address .= $row->city == "" ? "" : ", $row->city";
		$address .= $row->state == "" ? "" : ", $row->state";
		$address .= $row->zip == "" ? "" : ", $row->zip";

		return $address;

	}

}