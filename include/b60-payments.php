<?php

//deals with calls to Stripe API
class B60_Stripe {

	/**
	 * @var string
	 */
	const INVALID_NUMBER_ERROR = 'invalid_number';
	/**
	 * @var string
	 */
	const INVALID_EXPIRY_MONTH_ERROR = 'invalid_expiry_month';
	/**
	 * @var string
	 */
	const INVALID_EXPIRY_YEAR_ERROR = 'invalid_expiry_year';
	/**
	 * @var string
	 */
	const INVALID_CVC_ERROR = 'invalid_cvc';
	/**
	 * @var string
	 */
	const INCORRECT_NUMBER_ERROR = 'incorrect_number';
	/**
	 * @var string
	 */
	const EXPIRED_CARD_ERROR = 'expired_card';
	/**
	 * @var string
	 */
	const INCORRECT_CVC_ERROR = 'incorrect_cvc';
	/**
	 * @var string
	 */
	const INCORRECT_ZIP_ERROR = 'incorrect_zip';
	/**
	 * @var string
	 */
	const CARD_DECLINED_ERROR = 'card_declined';
	/**
	 * @var string
	 */
	const MISSING_ERROR = 'missing';
	/**
	 * @var string
	 */
	const PROCESSING_ERROR = 'processing_error';

	function get_error_codes() {
		return array(
			self::INVALID_NUMBER_ERROR,
			self::INVALID_EXPIRY_MONTH_ERROR,
			self::INVALID_EXPIRY_YEAR_ERROR,
			self::INVALID_CVC_ERROR,
			self::INCORRECT_NUMBER_ERROR,
			self::EXPIRED_CARD_ERROR,
			self::INCORRECT_CVC_ERROR,
			self::INCORRECT_ZIP_ERROR,
			self::CARD_DECLINED_ERROR,
			self::MISSING_ERROR,
			self::PROCESSING_ERROR
		);
	}

	function resolve_error_message_by_code( $code ) {
		if ( $code === self::INVALID_NUMBER_ERROR ) {
			$resolved_message =  /* translators: message for Stripe error code 'invalid_number' */
				__( 'The card number is not a valid credit card number.', 'bookin60' );
		} elseif ( $code === self::INVALID_EXPIRY_MONTH_ERROR ) {
			$resolved_message = /* translators: message for Stripe error code 'invalid_expiry_month' */
				__( 'The card\'s expiration month is invalid.', 'bookin60' );
		} elseif ( $code === self::INVALID_EXPIRY_YEAR_ERROR ) {
			$resolved_message = /* translators: message for Stripe error code 'invalid_expiry_year' */
				__( 'The card\'s expiration year is invalid.', 'bookin60' );
		} elseif ( $code === self::INVALID_CVC_ERROR ) {
			$resolved_message = /* translators: message for Stripe error code 'invalid_cvc' */
				__( 'The card\'s security code is invalid.', 'bookin60' );
		} elseif ( $code === self::INCORRECT_NUMBER_ERROR ) {
			$resolved_message = /* translators: message for Stripe error code 'incorrect_number' */
				__( 'The card number is incorrect.', 'bookin60' );
		} elseif ( $code === self::EXPIRED_CARD_ERROR ) {
			$resolved_message = /* translators: message for Stripe error code 'expired_card' */
				__( 'The card has expired.', 'bookin60' );
		} elseif ( $code === self::INCORRECT_CVC_ERROR ) {
			$resolved_message = /* translators: message for Stripe error code 'incorrect_cvc' */
				__( 'The card\'s security code is incorrect.', 'bookin60' );
		} elseif ( $code === self::INCORRECT_ZIP_ERROR ) {
			$resolved_message = /* translators: message for Stripe error code 'incorrect_zip' */
				__( 'The card\'s zip code failed validation.', 'bookin60' );
		} elseif ( $code === self::CARD_DECLINED_ERROR ) {
			$resolved_message = /* translators: message for Stripe error code 'card_declined' */
				__( 'The card was declined.', 'bookin60' );
		} elseif ( $code === self::MISSING_ERROR ) {
			$resolved_message = /* translators: message for Stripe error code 'missing' */
				__( 'There is no card on a customer that is being charged.', 'bookin60' );
		} elseif ( $code === self::PROCESSING_ERROR ) {
			$resolved_message = /* translators: message for Stripe error code 'processing_error' */
				__( 'An error occurred while processing the card.', 'bookin60' );
		} else {
			$resolved_message = null;
		}

		return $resolved_message;
	}

	/**
	 * @param $amount
	 * @param $card
	 * @param $description
	 * @param null $metadata
	 * @param null $stripeEmail
	 *
	 * @return \Stripe\Charge
	 */
	function charge( $amount, $card, $description, $metadata = null, $stripeEmail = null ) {
		$options = get_option( 'b60_settings_option' );

		$data = array(
			'card'          => $card,
			'amount'        => $amount,
			'currency'      => $options['currency'],
			'description'   => $description,
			'receipt_email' => $stripeEmail
		);

		if ( $metadata ) {
			$data['metadata'] = $metadata;
		}

		$charge = \Stripe\Charge::create( $data );

		return $charge;
	}
}
