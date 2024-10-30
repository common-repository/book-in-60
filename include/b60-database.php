<?php

class B60_Database {
	
	const TABLE_PAYMENTS = 'b60_payments';
	const TABLE_PAYMENT_FORMS = 'b60_payment_forms';
	const TABLE_LEAD_ENTRIES = 'b60_lead_entries';

	public static function b60_setup_db() {
		//require for dbDelta()
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_LEAD_ENTRIES;

		$sql = "CREATE TABLE " . $table . " (
        leadID INT NOT NULL AUTO_INCREMENT,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(100) NOT NULL,        
        has_booking TINYINT(1),
        created DATETIME NOT NULL,
        PRIMARY KEY leadID (leadID)
        );";

		//database write/update
		dbDelta( $sql );

		$table = $wpdb->prefix . self::TABLE_PAYMENTS;

		$sql = "CREATE TABLE " . $table . " (
        paymentID INT NOT NULL AUTO_INCREMENT,
        customerID INT NOT NULL,
        eventID VARCHAR(100) NOT NULL,
        description VARCHAR(255) NOT NULL,
        paid TINYINT(1),
        livemode TINYINT(1),
        amount INT NOT NULL,
        redeemed_amount INT NOT NULL,
        fee INT NOT NULL,
        booking_details LONGTEXT NOT NULL,
        schedule_date VARCHAR(100) NOT NULL,
        schedule_time VARCHAR(100) NOT NULL,
        frequency VARCHAR(100) NOT NULL,
        address VARCHAR(500) NOT NULL,
        apartment VARCHAR(500) NOT NULL,
        city VARCHAR(500) NOT NULL,
        state VARCHAR(255) NOT NULL,
        zip VARCHAR(100) NOT NULL,
        created DATETIME NOT NULL,
        PRIMARY KEY paymentID (paymentID)
        );";

		//database write/update
		dbDelta( $sql );

		$table = $wpdb->prefix . self::TABLE_PAYMENT_FORMS;

		$sql = "CREATE TABLE " . $table . " (
        paymentFormID INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        formTitle VARCHAR(100) NOT NULL,
        amount INT NOT NULL,
        customAmount TINYINT(1) DEFAULT '0',
        buttonTitle VARCHAR(100) NOT NULL DEFAULT 'Make Payment',
        showButtonAmount TINYINT(1) DEFAULT '1',
        showEmailInput TINYINT(1) DEFAULT '0',
        showCustomInput TINYINT(1) DEFAULT '0',
        customInputTitle VARCHAR(100) NOT NULL DEFAULT 'Extra Information',
        redirectOnSuccess TINYINT(1) DEFAULT '0',
        redirectUrl VARCHAR(1024) DEFAULT NULL,
        redirectUrlHelp VARCHAR(1024) DEFAULT NULL,
        redirectToPageOrPost TINYINT(1) DEFAULT '1',
        redirectToPageOrPostHelp TINYINT(1) DEFAULT '1',
        redirectPostID INT(5) DEFAULT 0,
        redirectPostIDHelp INT(5) DEFAULT 0,
        redirectLeadToPage TINYINT(1) DEFAULT '1',
        redirectLeadID INT(5) DEFAULT 0,
        showAddress TINYINT(1) DEFAULT '0',
        sendEmailReceipt TINYINT(1) DEFAULT '0',
        formStyle INT(5) DEFAULT 0,
        PRIMARY KEY paymentFormID (paymentFormID)
        );";

		//database write/update
		dbDelta( $sql );

		//default form
		$defaultPaymentForm = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "b60_payment_forms" . " WHERE name='default';" ) );
		if ( $defaultPaymentForm === null ) {
			$data    = array(
				'name'      => 'default',
				'formTitle' => 'Payment',
				'amount'    => 1000 //$10.00
			);
			$formats = array( '%s', '%s', '%d' );
			$wpdb->insert( $wpdb->prefix . self::TABLE_PAYMENT_FORMS, $data, $formats );
		}

		$data_lead = '[{\"label\":\"First Name\",\"field_type\":\"text\",\"required\":true,\"size\":\"large\",\"min_max_length_units\":\"characters\",\"cid\":\"c1\"},{\"label\":\"Last Name\",\"field_type\":\"text\",\"required\":true,\"size\":\"large\",\"min_max_length_units\":\"characters\",\"cid\":\"c2\"},{\"label\":\"Email\",\"field_type\":\"text\",\"required\":true,\"size\":\"large\",\"min_max_length_units\":\"characters\",\"cid\":\"c6\"},{\"label\":\"Phone\",\"field_type\":\"text\",\"required\":true,\"size\":\"large\",\"min_max_length_units\":\"characters\",\"cid\":\"c10\"}]';

		$data_booking = '[{\"label\":\"Step 1\",\"step\":\"step_1\",\"field_type\":\"section_break\"},{\"label\":\"Some info about your home\",\"description\":\"\",\"field_type\":\"sd_service\",\"required\":true,\"Formbuilder\":{\"options\":{\"mappings\":{}}},\"services\":\"\",\"Formbuilder\":{\"options\":{\"mappings\":{}}},\"pricing\":\"\",\"cid\":\"c34\"},{\"label\":\"Step 2\",\"step\":\"step_2\",\"field_type\":\"section_break\"},{\"label\":\"Select Extras\",\"description\":\"\",\"field_type\":\"sd_addon\",\"required\":true,\"Formbuilder\":{\"options\":{\"mappings\":{}}},\"addons\":\"\",\"cid\":\"c34\"},{\"label\":\"Step 3\",\"step\":\"step_3\",\"field_type\":\"section_break\"},{\"label\":\"How often would you like service?\",\"description\":\"\",\"field_type\":\"sd_frequency\",\"required\":true,\"Formbuilder\":{\"options\":{\"mappings\":{}}},\"frequencies\":\"\",\"cid\":\"c34\"},{\"label\":\"Step 4\",\"step\":\"step_4\",\"field_type\":\"section_break\"},{\"label\":\"When would you like us to come?\",\"field_type\":\"sd_calendar\",\"required\":true,\"Formbuilder\":{\"options\":{\"mappings\":{}}},\"cid\":\"c34\"},{\"label\":\"Step 5\",\"step\":\"step_5\",\"field_type\":\"section_break\"},{\"label\":\"Who you are\",\"field_type\":\"sd_customer_info\",\"required\":true,\"Formbuilder\":{\"options\":{\"mappings\":{}}},\"cid\":\"c48\"},{\"label\":\"Your Address\",\"field_type\":\"sd_address\",\"required\":true,\"Formbuilder\":{\"options\":{\"mappings\":{}}},\"cid\":\"c42\"},{\"label\":\"Discount  code\",\"field_type\":\"sd_discount\",\"required\":false,\"Formbuilder\":{\"options\":{\"mappings\":{}}},\"cid\":\"c49\"}]';

		update_option( 'b60_lead_formbuilder', $data_lead );
		update_option( 'b60_booking_formbuilder', $data_booking );

		do_action( 'b60_setup_db' );
	}

	function b60_insert_lead_entries( $entries ) {
		global $wpdb;

		$data = array(
			'first_name' => $entries['first_name'],
			'last_name' => $entries['last_name'],
			'email'  => $entries['email'],
			'phone' => $entries['phone'],
			'created'      => date( 'Y-m-d H:i:s' )
		);

		$get_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . self::TABLE_LEAD_ENTRIES . " WHERE email=%s", $entries['email'] ) );

		if ( null !== $get_row ) {
		  	$wpdb->update( $wpdb->prefix . self::TABLE_LEAD_ENTRIES, $data, array( 'leadID' => $get_row->leadID ) );	
		  	return $get_row->leadID;
		} else {
			$wpdb->insert( $wpdb->prefix . self::TABLE_LEAD_ENTRIES, apply_filters( 'insert_lead_entries', $data ) );
			return $wpdb->insert_id;		  
		}
	}

	function b60_insert_payment( $payment, $bookingDetails ) {
		global $wpdb;

		$data = array(
			'eventID'      => $payment->id,
			'customerID'      => $bookingDetails['customerID'],
			'description'  => $payment->description,
			'paid'         => $payment->paid,
			'livemode'     => $payment->livemode,
			'amount'       => $payment->amount,
			'fee'          => ( isset( $payment->fee ) && ! is_null( $payment->fee ) ) ? $payment->fee : 0,
			'booking_details' => $bookingDetails['booking_details'],
			'schedule_date' => $bookingDetails['schedule_date'],
			'schedule_time' => $bookingDetails['schedule_time'],
			'frequency' => $bookingDetails['frequency'],
			'address' 	   => $bookingDetails['line1'],
			'apartment'	   => $bookingDetails['line2'],
			'city'  	   => $bookingDetails['city'],
			'state' 	   => $bookingDetails['state'],
			'zip'  		   => $bookingDetails['zip'],			
			'created'      => date( 'Y-m-d H:i:s', $payment->created )
		);

		$wpdb->insert( $wpdb->prefix . self::TABLE_PAYMENTS, apply_filters( 'insert_booking_entries', $data ) );
	}

	function insert_payment_form( $form ) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . self::TABLE_PAYMENT_FORMS, $form );
	}

	function update_payment_form( $id, $form ) {
		global $wpdb;
		$wpdb->update( $wpdb->prefix . self::TABLE_PAYMENT_FORMS, $form, array( 'paymentFormID' => $id ) );
	}

	function update_lead( $id ) {
		global $wpdb;

		$wpdb->update( $wpdb->prefix . self::TABLE_LEAD_ENTRIES, array( 'has_booking' => '1' ), array( 'leadID' => $id ) );	
	}

	function delete_payment_form( $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->prefix . self::TABLE_PAYMENT_FORMS . " WHERE paymentFormID=%d", $id) );
	}

	function get_payment_form_by_name( $name ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . self::TABLE_PAYMENT_FORMS . " WHERE name='" . $name . "';" ) );
	}

	function get_lead($id) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . self::TABLE_LEAD_ENTRIES . " WHERE leadID=".$id.";" ) );
	}

	function get_booking($id) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . self::TABLE_PAYMENTS . " WHERE paymentID=".$id.";" ) );
	}
}