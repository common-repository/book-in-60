<?php

/**
 * Class B60_Customer
 *
 * Deals with customer front-end input i.e. payment forms submission.
 *
 */
class B60_Customer {
	private $stripe = null;

	public function __construct() {
		$this->stripe = new B60_Stripe();
		$this->db     = new B60_Database();
		$this->hooks();
	}

	private function hooks() {
		add_action( 'wp_ajax_b60_stripe_payment_charge', array( $this, 'b60_payment_charge' ) );
		add_action( 'wp_ajax_nopriv_b60_stripe_payment_charge', array( $this, 'b60_payment_charge' ) );
		add_action( 'wp_ajax_b60_lead_entries', array( $this, 'b60_lead_entries' ) );
		add_action( 'wp_ajax_nopriv_b60_lead_entries', array( $this, 'b60_lead_entries' ) );
	}

	function b60_lead_entries() {
		
		$paymentForm = $this->db->get_payment_form_by_name( 'default');

		$firstName  = sanitize_text_field( $_POST['first_name'] );
		$lastName  = sanitize_text_field( $_POST['last_name'] );
		$email  = sanitize_text_field( $_POST['email'] );
		$phone  = sanitize_text_field( $_POST['phone'] );

		$lead_entries = array(
			'first_name' => $firstName,
			'last_name' => $lastName,
			'email'  => $email,
			'phone' => $phone,
		);

		$lead_entry = $this->db->b60_insert_lead_entries( $lead_entries );

		$return = array(
			'success' => true,
			'msg'     => __( 'Lead added successfully!', 'bookin60' ),
			'id' => $lead_entry,
			'first_name' => $firstName,
			'last_name' => $lastName,
			'email'  => $email,
			'phone' => $phone,
		);

		$redirectLeadToPage = $paymentForm->redirectLeadToPage;
		$redirectLeadID     = $paymentForm->redirectLeadID;		

		if ( $redirectLeadToPage == 1 ) {
			if ( $redirectLeadID != 0 ) {
				$return['redirectTo'] = $redirectLeadToPage;
				$return['redirectURL'] = get_page_link( $redirectLeadID );
				$return['lead_entry'] = $lead_entry;
			} else {
				error_log( "Inconsistent form data: formName=$formName, redirectLeadID=$redirectLeadID" );
			}
		} else {
			$return['redirectTo'] = $redirectLeadToPage;
		}

		header( "Content-Type: application/json" );
		echo json_encode( apply_filters( 'b60_lead_entries_return_message', $return ) );
		exit;
	}

	function b60_payment_charge() {

		$formName = isset( $_POST['formName'] ) ? sanitize_text_field( $_POST['formName'] ) : null;

		if ( ! is_null( $formName ) ) {
			$paymentForm = $this->db->get_payment_form_by_name( $formName );
			if ( isset( $paymentForm ) ) {

				$useCustomAmount      = $paymentForm->customAmount;
				$doRedirect           = $paymentForm->redirectOnSuccess;
				$redirectPostID       = $paymentForm->redirectPostID;
				$redirectUrl          = $paymentForm->redirectUrl;
				$redirectToPageOrPost = $paymentForm->redirectToPageOrPost;
				$showAddress          = $paymentForm->showAddress;
				$sendEmailReceipt     = $paymentForm->sendEmailReceipt;
				$showEmailInput       = $paymentForm->showEmailInput;

				$stripeToken   = sanitize_text_field( $_POST['stripeToken'] );
				$customerID   = sanitize_text_field( $_POST['customerID'] );
				$customerName  = sanitize_text_field( $_POST['b60_name'] );
				$customerEmail = 'n/a';
				if ( isset( $_POST['email'] ) ) {
					$customerEmail = sanitize_text_field( $_POST['email'] );
				}

				$amount = null;
				if ( $useCustomAmount == 1 ) {
					$amount = sanitize_text_field( trim( $_POST['b60_custom_amount'] ) );
					if ( is_numeric( $amount ) ) {
						$amount = $amount * 100;
					}
				} else {
					$amount = $paymentForm->amount;
				}

				$billingAddressLine1 = isset( $_POST['address'] ) ? sanitize_text_field( $_POST['address'] ) : '';
				$billingAddressLine2 = isset( $_POST['apartment'] ) ? sanitize_text_field( $_POST['apartment'] ) : '';
				$billingAddressCity  = isset( $_POST['city'] ) ? sanitize_text_field( $_POST['city'] ) : '';
				$billingAddressState = isset( $_POST['state'] ) ? sanitize_text_field( $_POST['state'] ) : '';
				$billingAddressZip   = isset( $_POST['zip'] ) ? sanitize_text_field( $_POST['zip'] ) : '';

				$customInput = isset( $_POST['b60_custom_input'] ) ? sanitize_text_field( $_POST['b60_custom_input'] ) : 'n/a';

				$valid = true;
				if ( ! is_numeric( trim( $amount ) ) || $amount < 0 ) {
					$valid  = false;
					$return = array(
						'success' => false,
						'msg'     => __( 'The payment amount is invalid, please only use numbers and a decimal point.', 'bookin60' )
					);
				}

				if ( $valid && $showAddress == 1 ) {
					if ( $billingAddressLine1 == '' || $billingAddressCity == '' || $billingAddressZip == '' ) {
						$valid  = false;
						$return = array(
							'success' => false,
							'msg'     => __( 'Please enter a valid billing address.', 'bookin60' )
						);
					}
				}

				if ( $valid && $showEmailInput && ! filter_var( $customerEmail, FILTER_VALIDATE_EMAIL ) ) {
					$valid  = false;
					$return = array(
						'success' => false,
						'msg'     => __( 'Please enter a valid email address.', 'bookin60' )
					);
				}

				if ( $valid ) {

					$description = "Payment from {$customerName} on Bookin60 form.";
					$metadata    = array(
						'customer_name'         => $customerName,
						'customer_email'        => $customerEmail,
						'billing_address_line1' => $billingAddressLine1,
						'billing_address_line2' => $billingAddressLine2,
						'billing_address_city'  => $billingAddressCity,
						'billing_address_state' => $billingAddressState,
						'billing_address_zip'   => $billingAddressZip
					);

					$booking_details = sanitize_text_field( $_POST['bookingDetails'] );

					$bookingDetails = array(
						'customerID' => $customerID,
						//'discountCodeID'  => $discountCodeID,
						'booking_details' => $booking_details,
						'frequency' => isset( $_POST['frequency'] ) ? sanitize_text_field( $_POST['frequency'] ) : '',
						'schedule_date' => isset( $_POST['schedule_date'] ) ? sanitize_text_field( $_POST['schedule_date'] ) : '',
						'schedule_time' => isset( $_POST['schedule_time'] ) ? sanitize_text_field( $_POST['schedule_time'] ) : '',
						'line1' => $billingAddressLine1,
						'line2' => $billingAddressLine2,
						'city'  => $billingAddressCity,
						'state' => $billingAddressState,
						'zip'   => $billingAddressZip
					);

					if($amount > 0) {
						try {

							$sendPluginEmail = true;
							if ( $sendEmailReceipt == 1 && isset( $customerEmail ) ) {
								$sendPluginEmail = false;
							}

							do_action( 'b60_before_payment_charge', $amount );
							$charge = $this->stripe->charge( $amount, $stripeToken, $description, $metadata, ( $sendPluginEmail == false ? $customerEmail : null ) );
							do_action( 'b60_after_payment_charge', $charge );

							
							$this->db->b60_insert_payment( $charge, $bookingDetails );
							$this->db->update_lead( $customerID );

							$return['success'] = true;
						    $return['msg'] = __( 'Booking Successful!', 'bookin60' );
						    $return['booking_details'] = $booking_details;
						    $return['charge'] = $charge;

							if ( $doRedirect == 1 ) {
								if ( $redirectToPageOrPost == 1 ) {
									if ( $redirectPostID != 0 ) {
										$return['redirect']    = true;
										$return['redirectURL'] = get_page_link( $redirectPostID );
									} else {
										error_log( "Inconsistent form data: formName=$formName, doRedirect=$doRedirect, redirectPostID=$redirectPostID" );
									}
								} else {
									$return['redirect']    = true;
									$return['redirectURL'] = $redirectUrl;
								}							
								
							}

						} catch ( \Stripe\Error\Card $e ) {
							$message = $this->stripe->resolve_error_message_by_code( $e->getCode() );
							if ( is_null( $message ) ) {
								$message = B60::translate_label( $e->getMessage() );
							}
							$return = array(
								'success' => false,
								'msg'     => $message
							);
						} catch ( Exception $e ) {
							$return = array(
								'success' => false,
								'msg'     => B60::translate_label( $e->getMessage() )
							);
						}

					} else {

						$charge = array('id'=>'coupon_applied', 'description'=>'Coupon applied total to $0.00', 'paid'=>true, 'livemode'=>false, 'amount'=>0, 'fee'=>0);

						$this->db->b60_insert_payment_coupon_applied( $charge, $bookingDetails );
						$this->db->update_lead( $customerID );

						$return['success'] = true;
						$return['msg'] = __( 'Booking Successful!', 'bookin60' );
						$return['booking_details'] = $booking_details;

						if ( $doRedirect == 1 ) {
							if ( $redirectToPageOrPost == 1 ) {
								if ( $redirectPostID != 0 ) {
									$return['redirect']    = true;
									$return['redirectURL'] = get_page_link( $redirectPostID );
								} else {
									error_log( "Inconsistent form data: formName=$formName, doRedirect=$doRedirect, redirectPostID=$redirectPostID" );
								}
							} else {
								$return['redirect']    = true;
								$return['redirectURL'] = $redirectUrl;
							}		
						}						
					}
					
				} else {
					if ( ! isset( $return ) ) {
						$return = array(
							'success' => false,
							'msg'     => __( 'Incorrect data submitted.', 'bookin60' )
						);
					}

				}

			} else {
				$return = array(
					'success' => false,
					'msg'     => __( 'Invalid form name or form nonce or form not found', 'bookin60' )
				);
			}

		} else {
			$return = array(
				'success' => false,
				'msg'     => __( 'Invalid form name or form nonce', 'bookin60' )
			);
		}

		header( "Content-Type: application/json" );
		echo json_encode( apply_filters( 'b60_payment_charge_return_message', $return ) );
		exit;
	}

}