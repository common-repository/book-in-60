<?php

class B60 {
	const VERSION = '1.2.0';
	/**
	 * @var B60
	 */
	public static $instance;
	/**
	 * @var B60_Customer
	 */
	private $customer = null;
	/**
	 * @var B60_Admin
	 */
	private $admin = null;
	/**
	 * @var B60_Admin_Menu
	 */
	private $adminMenu = null;
	/**
	 * @var B60_Database
	 */
	private $database = null;
	/**
	 * @var B60_Stripe
	 */
	private $stripe = null;

	public function __construct() {
		$this->includes();
		$this->setup();
		$this->hooks();
	}

	function includes() {
		include 'b60-database.php';
		include 'b60-customer.php';
		include 'b60-payments.php';
		include 'b60-admin.php';		
		include 'b60-admin-menu.php';			
		include 'b60-post-types.php';			
		include 'b60-post-type-metaboxes.php';		
		//include 'b60-calendar.php';		

		do_action( 'b60_includes_action' );
	}

	function setup() {
		//set option defaults
		$options = get_option( 'b60_settings_option' );
		if ( $options == '' || $options['b60_version'] != self::VERSION ) {
			$this->set_option_defaults( $options );
		}

		//set API key
		if ( $options['apiMode'] === 'test' ) {
			$this->b60_set_api_key( $options['secretKey_test'] );
		} else {
			$this->b60_set_api_key( $options['secretKey_live'] );
		}

		//setup subclasses to handle everything
		$this->database  = new B60_Database();
		$this->customer  = new B60_Customer();
		$this->admin     = new B60_Admin();
		$this->adminMenu = new B60_Admin_Menu();
		$this->stripe    = new B60_Stripe();

		do_action( 'b60_setup_action' );

	}

	function set_option_defaults( $options ) {
		if ( $options == '' ) {
			$arr = array(
				'secretKey_test'     => 'YOUR_TEST_SECRET_KEY',
				'publishKey_test'    => 'YOUR_TEST_PUBLISHABLE_KEY',
				'secretKey_live'     => 'YOUR_LIVE_SECRET_KEY',
				'publishKey_live'    => 'YOUR_LIVE_PUBLISHABLE_KEY',
				'apiMode'            => 'test',
				'currency'           => 'usd',
				'includeStyles'      => '1',
				'b60_version' => self::VERSION
			);

			update_option( 'b60_settings_option', $arr );
		} else //different version
		{
			$options['b60_version'] = self::VERSION;
			if ( ! array_key_exists( 'secretKey_test', $options ) ) {
				$options['secretKey_test'] = 'YOUR_TEST_SECRET_KEY';
			}
			if ( ! array_key_exists( 'publishKey_test', $options ) ) {
				$options['publishKey_test'] = 'YOUR_TEST_PUBLISHABLE_KEY';
			}
			if ( ! array_key_exists( 'secretKey_live', $options ) ) {
				$options['secretKey_live'] = 'YOUR_LIVE_SECRET_KEY';
			}
			if ( ! array_key_exists( 'publishKey_live', $options ) ) {
				$options['publishKey_live'] = 'YOUR_LIVE_PUBLISHABLE_KEY';
			}
			if ( ! array_key_exists( 'apiMode', $options ) ) {
				$options['apiMode'] = 'test';
			}
			if ( ! array_key_exists( 'currency', $options ) ) {
				$options['currency'] = 'usd';
			}
			if ( ! array_key_exists( 'includeStyles', $options ) ) {
				$options['includeStyles'] = '1';
			}

			update_option( 'b60_settings_option', $options );

			//also, if version changed then the DB might be out of date
			B60_Database::b60_setup_db();
		}

	}

	function b60_set_api_key( $key ) {
		if ( $key != '' && $key != 'YOUR_TEST_SECRET_KEY' && $key != 'YOUR_LIVE_SECRET_KEY' ) {
			try {
				\Stripe\Stripe::setApiKey( $key );
			} catch ( Exception $e ) {
				//invalid key was set, ignore it
			}
		}
	}

	function hooks() {
		add_shortcode( 'b60', array( $this, 'b60_form' ) );
		add_shortcode( 'b60_gift_card', array( $this, 'b60_gift_card' ) );
		add_filter( 'plugin_action_links_b60', array( $this, 'add_action_links' ) );
		//add_filter( 'page_template', array( $this, 'b60_payment_form' ) );
		do_action( 'b60_main_hooks_action' );
	}

	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new B60();
		}

		return self::$instance;
	}

	public static function setup_db() {
		B60_Database::b60_setup_db();
	}

	public static function echo_translated_label( $label ) {
		echo self::translate_label( $label );
	}

	public static function translate_label( $label ) {
		if ( empty( $label ) ) {
			return '';
		}

		return __( sanitize_text_field( $label ), 'bookin60' );
	}

	function add_action_links( $links ) {
		$links[] = '<a href="' . admin_url( 'admin.php?page=b60-settings' ) . '">Settings</a>';

		return $links;
	}

	function b60_form( $atts ) {
		$form = null;

		extract( shortcode_atts( array(
			'form' => 'default',
		), $atts ) );

		//load scripts and styles
		$this->b60_load_css();
		$this->b60_load_js();
		//load form data into scope
		/** @noinspection PhpUnusedLocalVariableInspection */
		list( $paymentForm, $currencySymbol, $creditCardImage ) = $this->load_payment_form_data( $form );

		//get the form style
		$style = 0;
		if ( ! $paymentForm ) {
			$style = - 1;
		} else {
			$style = $paymentForm->formStyle;
		}

		ob_start();
		/** @noinspection PhpIncludeInspection */
		include $this->get_payment_form_by_style( $style );
		$content = ob_get_clean();

		return apply_filters( 'b60_form', $content );
	} 

	function b60_gift_card( $atts ) {

		//load scripts and styles
		$this->b60_load_css();
		$this->b60_load_js();
		//load form data into scope
		/** @noinspection PhpUnusedLocalVariableInspection */
		// list( $paymentForm, $currencySymbol, $creditCardImage ) = $this->load_payment_form_data( $form );

		// //get the form style
		$style = 0;
		// if ( ! $paymentForm ) {
		// 	$style = - 1;
		// } else {
		// 	$style = $paymentForm->formStyle;
		// }

		ob_start();
		/** @noinspection PhpIncludeInspection */
		include $this->get_giftcard_form_by_style( $style );
		$content = ob_get_clean();

		return apply_filters( 'b60_gift_card', $content );
	} 

	function b60_load_css() {
		$options = get_option( 'b60_settings_option' );
		//if ( $options['includeStyles'] === '1' ) {
			wp_enqueue_style( 'smartwizard-css', plugins_url( '/assets/css/smart_wizard_all.css', dirname( __FILE__ ) ), null, B60::VERSION );
			wp_enqueue_style( 'calendar-css', plugins_url( '/assets/css/datepicker.css', dirname( __FILE__ ) ), null, B60::VERSION );
		//}

		do_action( 'b60_load_css_action' );
	}

	function b60_load_js() {
		$options = get_option( 'b60_settings_option' );
		wp_enqueue_script( 'calendar-js', plugins_url( 'assets/js/datepicker.min.js', dirname( __FILE__ ) ), array( 'jquery' ) );
		wp_enqueue_script( 'calendar-lang-js', plugins_url( 'assets/js//i18n/datepicker.en.js', dirname( __FILE__ ) ), array( 'calendar-js' ) );
		wp_enqueue_script( 'smartwizard-js', plugins_url( 'assets/js/jquery.smartWizard.min.js', dirname( __FILE__ ) ), array( 'jquery' ) );
		wp_enqueue_script( 'popup-js', plugins_url( 'assets/js/jquery.popupoverlay.min.js', dirname( __FILE__ ) ), array( 'jquery' ) );
		wp_enqueue_script( 'stripe-js', '//js.stripe.com/v2/', array( 'jquery' ) );
		wp_enqueue_script('inputmask-js', plugins_url( 'assets/js/jquery.inputmask.min.js', dirname( __FILE__ ) ), array('jquery') );
		wp_enqueue_script( 'b60-js', plugins_url( 'assets/js/b60.js', dirname( __FILE__ ) ), array( 'stripe-js', 'calendar-js' ), B60::VERSION );
		wp_enqueue_script( 'bootstrap-js', plugins_url( 'assets/js/bootstrap.min.js', dirname( __FILE__ ) ), array( 'jquery' ), B60::VERSION );	
		if ( $options['apiMode'] === 'test' ) {
			wp_localize_script( 'b60-js', 'stripe', array( 'key' => $options['publishKey_test'] ) );
		} else {
			wp_localize_script( 'b60-js', 'stripe', array( 'key' => $options['publishKey_live'] ) );
		}

		wp_localize_script( 'b60-js', 'ajaxurl', array( 'admin_ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

		wp_localize_script( 'b60-js', 'wpfsf_L10n', array(
			B60_Stripe::INVALID_NUMBER_ERROR       => $this->stripe->resolve_error_message_by_code( B60_Stripe::INVALID_NUMBER_ERROR ),
			B60_Stripe::INVALID_EXPIRY_MONTH_ERROR => $this->stripe->resolve_error_message_by_code( B60_Stripe::INVALID_EXPIRY_MONTH_ERROR ),
			B60_Stripe::INVALID_EXPIRY_YEAR_ERROR  => $this->stripe->resolve_error_message_by_code( B60_Stripe::INVALID_EXPIRY_YEAR_ERROR ),
			B60_Stripe::INVALID_CVC_ERROR          => $this->stripe->resolve_error_message_by_code( B60_Stripe::INVALID_CVC_ERROR ),
			B60_Stripe::INCORRECT_NUMBER_ERROR     => $this->stripe->resolve_error_message_by_code( B60_Stripe::INCORRECT_NUMBER_ERROR ),
			B60_Stripe::EXPIRED_CARD_ERROR         => $this->stripe->resolve_error_message_by_code( B60_Stripe::EXPIRED_CARD_ERROR ),
			B60_Stripe::INCORRECT_CVC_ERROR        => $this->stripe->resolve_error_message_by_code( B60_Stripe::INCORRECT_CVC_ERROR ),
			B60_Stripe::INCORRECT_ZIP_ERROR        => $this->stripe->resolve_error_message_by_code( B60_Stripe::INCORRECT_ZIP_ERROR ),
			B60_Stripe::CARD_DECLINED_ERROR        => $this->stripe->resolve_error_message_by_code( B60_Stripe::CARD_DECLINED_ERROR ),
			B60_Stripe::MISSING_ERROR              => $this->stripe->resolve_error_message_by_code( B60_Stripe::MISSING_ERROR ),
			B60_Stripe::PROCESSING_ERROR           => $this->stripe->resolve_error_message_by_code( B60_Stripe::PROCESSING_ERROR )
		) );

		do_action( 'b60_load_js_action' );
	}   

	function load_payment_form_data( $form ) {
		list ( $currencySymbol, $creditCardImage ) = $this->get_locale_strings();

		$formData = array(
			$this->database->get_payment_form_by_name( $form ),
			$currencySymbol,
			$creditCardImage
		);

		return $formData;
	}

	function get_locale_strings() {
		$options         = get_option( 'b60_settings_option' );
		$currencySymbol  = B60::get_currency_symbol_for( $options['currency'] );
		$creditCardImage = 'creditcards-us.png';

		if ( $options['currency'] === 'eur' ) {
			$creditCardImage = 'creditcards.png';
		} elseif ( $options['currency'] === 'gbp' ) {
			$creditCardImage = 'creditcards.png';
		}

		return array(
			$currencySymbol,
			$creditCardImage
		);
	}

	public static function get_currency_symbol_for( $currency ) {
		if ( isset( $currency ) ) {

			$available_currencies = B60::get_available_currencies();

			if ( isset( $available_currencies ) && array_key_exists( $currency, $available_currencies ) ) {
				$currency_symbol = $available_currencies[ $currency ]['symbol'];
			} else {
				$currency_symbol = strtoupper( $currency );
			}

			return $currency_symbol;
		}

		return null;
	}

	public static function get_available_currencies() {
		return array(
			'aed' => array(
				'code'   => 'AED',
				'name'   => 'United Arab Emirates Dirham',
				'symbol' => 'DH'
			),
			'afn' => array(
				'code'   => 'AFN',
				'name'   => 'Afghan Afghani',
				'symbol' => '؋'
			),
			'all' => array(
				'code'   => 'ALL',
				'name'   => 'Albanian Lek',
				'symbol' => 'L'
			),
			'amd' => array(
				'code'   => 'AMD',
				'name'   => 'Armenian Dram',
				'symbol' => '֏'
			),
			'ang' => array(
				'code'   => 'ANG',
				'name'   => 'Netherlands Antillean Gulden',
				'symbol' => 'ƒ'
			),
			'aoa' => array(
				'code'   => 'AOA',
				'name'   => 'Angolan Kwanza',
				'symbol' => 'Kz'
			),
			'ars' => array(
				'code'   => 'ARS',
				'name'   => 'Argentine Peso',
				'symbol' => '$'
			),
			'aud' => array(
				'code'   => 'AUD',
				'name'   => 'Australian Dollar',
				'symbol' => '$'
			),
			'awg' => array(
				'code'   => 'AWG',
				'name'   => 'Aruban Florin',
				'symbol' => 'ƒ'
			),
			'azn' => array(
				'code'   => 'AZN',
				'name'   => 'Azerbaijani Manat',
				'symbol' => 'm.'
			),
			'bam' => array(
				'code'   => 'BAM',
				'name'   => 'Bosnia & Herzegovina Convertible Mark',
				'symbol' => 'KM'
			),
			'bbd' => array(
				'code'   => 'BBD',
				'name'   => 'Barbadian Dollar',
				'symbol' => 'Bds$'
			),
			'bdt' => array(
				'code'   => 'BDT',
				'name'   => 'Bangladeshi Taka',
				'symbol' => '৳'
			),
			'bgn' => array(
				'code'   => 'BGN',
				'name'   => 'Bulgarian Lev',
				'symbol' => 'лв'
			),
			'bif' => array(
				'code'   => 'BIF',
				'name'   => 'Burundian Franc',
				'symbol' => 'FBu'
			),
			'bmd' => array(
				'code'   => 'BMD',
				'name'   => 'Bermudian Dollar',
				'symbol' => 'BD$'
			),
			'bnd' => array(
				'code'   => 'BND',
				'name'   => 'Brunei Dollar',
				'symbol' => 'B$'
			),
			'bob' => array(
				'code'   => 'BOB',
				'name'   => 'Bolivian Boliviano',
				'symbol' => 'Bs.'
			),
			'brl' => array(
				'code'   => 'BRL',
				'name'   => 'Brazilian Real',
				'symbol' => 'R$'
			),
			'bsd' => array(
				'code'   => 'BSD',
				'name'   => 'Bahamian Dollar',
				'symbol' => 'B$'
			),
			'bwp' => array(
				'code'   => 'BWP',
				'name'   => 'Botswana Pula',
				'symbol' => 'P'
			),
			'bzd' => array(
				'code'   => 'BZD',
				'name'   => 'Belize Dollar',
				'symbol' => 'BZ$'
			),
			'cad' => array(
				'code'   => 'CAD',
				'name'   => 'Canadian Dollar',
				'symbol' => '$'
			),
			'cdf' => array(
				'code'   => 'CDF',
				'name'   => 'Congolese Franc',
				'symbol' => 'CF'
			),
			'chf' => array(
				'code'   => 'CHF',
				'name'   => 'Swiss Franc',
				'symbol' => 'Fr'
			),
			'clp' => array(
				'code'   => 'CLP',
				'name'   => 'Chilean Peso',
				'symbol' => 'CLP$'
			),
			'cny' => array(
				'code'   => 'CNY',
				'name'   => 'Chinese Renminbi Yuan',
				'symbol' => '¥'
			),
			'cop' => array(
				'code'   => 'COP',
				'name'   => 'Colombian Peso',
				'symbol' => 'COL$'
			),
			'crc' => array(
				'code'   => 'CRC',
				'name'   => 'Costa Rican Colón',
				'symbol' => '₡'
			),
			'cve' => array(
				'code'   => 'CVE',
				'name'   => 'Cape Verdean Escudo',
				'symbol' => 'Esc'
			),
			'czk' => array(
				'code'   => 'CZK',
				'name'   => 'Czech Koruna',
				'symbol' => 'Kč'
			),
			'djf' => array(
				'code'   => 'DJF',
				'name'   => 'Djiboutian Franc',
				'symbol' => 'Fr'
			),
			'dkk' => array(
				'code'   => 'DKK',
				'name'   => 'Danish Krone',
				'symbol' => 'kr'
			),
			'dop' => array(
				'code'   => 'DOP',
				'name'   => 'Dominican Peso',
				'symbol' => 'RD$'
			),
			'dzd' => array(
				'code'   => 'DZD',
				'name'   => 'Algerian Dinar',
				'symbol' => 'DA'
			),
			'egp' => array(
				'code'   => 'EGP',
				'name'   => 'Egyptian Pound',
				'symbol' => 'L.E.'
			),
			'etb' => array(
				'code'   => 'ETB',
				'name'   => 'Ethiopian Birr',
				'symbol' => 'Br'
			),
			'eur' => array(
				'code'   => 'EUR',
				'name'   => 'Euro',
				'symbol' => '€'
			),
			'fjd' => array(
				'code'   => 'FJD',
				'name'   => 'Fijian Dollar',
				'symbol' => 'FJ$'
			),
			'fkp' => array(
				'code'   => 'FKP',
				'name'   => 'Falkland Islands Pound',
				'symbol' => 'FK£'
			),
			'gbp' => array(
				'code'   => 'GBP',
				'name'   => 'British Pound',
				'symbol' => '£'
			),
			'gel' => array(
				'code'   => 'GEL',
				'name'   => 'Georgian Lari',
				'symbol' => 'ლ'
			),
			'gip' => array(
				'code'   => 'GIP',
				'name'   => 'Gibraltar Pound',
				'symbol' => '£'
			),
			'gmd' => array(
				'code'   => 'GMD',
				'name'   => 'Gambian Dalasi',
				'symbol' => 'D'
			),
			'gnf' => array(
				'code'   => 'GNF',
				'name'   => 'Guinean Franc',
				'symbol' => 'FG'
			),
			'gtq' => array(
				'code'   => 'GTQ',
				'name'   => 'Guatemalan Quetzal',
				'symbol' => 'Q'
			),
			'gyd' => array(
				'code'   => 'GYD',
				'name'   => 'Guyanese Dollar',
				'symbol' => 'G$'
			),
			'hkd' => array(
				'code'   => 'HKD',
				'name'   => 'Hong Kong Dollar',
				'symbol' => 'HK$'
			),
			'hnl' => array(
				'code'   => 'HNL',
				'name'   => 'Honduran Lempira',
				'symbol' => 'L'
			),
			'hrk' => array(
				'code'   => 'HRK',
				'name'   => 'Croatian Kuna',
				'symbol' => 'kn'
			),
			'htg' => array(
				'code'   => 'HTG',
				'name'   => 'Haitian Gourde',
				'symbol' => 'G'
			),
			'huf' => array(
				'code'   => 'HUF',
				'name'   => 'Hungarian Forint',
				'symbol' => 'Ft'
			),
			'idr' => array(
				'code'   => 'IDR',
				'name'   => 'Indonesian Rupiah',
				'symbol' => 'Rp'
			),
			'ils' => array(
				'code'   => 'ILS',
				'name'   => 'Israeli New Sheqel',
				'symbol' => '₪'
			),
			'inr' => array(
				'code'   => 'INR',
				'name'   => 'Indian Rupee',
				'symbol' => '₹'
			),
			'isk' => array(
				'code'   => 'ISK',
				'name'   => 'Icelandic Króna',
				'symbol' => 'ikr'
			),
			'jmd' => array(
				'code'   => 'JMD',
				'name'   => 'Jamaican Dollar',
				'symbol' => 'J$'
			),
			'jpy' => array(
				'code'   => 'JPY',
				'name'   => 'Japanese Yen',
				'symbol' => '¥'
			),
			'kes' => array(
				'code'   => 'KES',
				'name'   => 'Kenyan Shilling',
				'symbol' => 'Ksh'
			),
			'kgs' => array(
				'code'   => 'KGS',
				'name'   => 'Kyrgyzstani Som',
				'symbol' => 'COM'
			),
			'khr' => array(
				'code'   => 'KHR',
				'name'   => 'Cambodian Riel',
				'symbol' => '៛'
			),
			'kmf' => array(
				'code'   => 'KMF',
				'name'   => 'Comorian Franc',
				'symbol' => 'CF'
			),
			'krw' => array(
				'code'   => 'KRW',
				'name'   => 'South Korean Won',
				'symbol' => '₩'
			),
			'kyd' => array(
				'code'   => 'KYD',
				'name'   => 'Cayman Islands Dollar',
				'symbol' => 'CI$'
			),
			'kzt' => array(
				'code'   => 'KZT',
				'name'   => 'Kazakhstani Tenge',
				'symbol' => '₸'
			),
			'lak' => array(
				'code'   => 'LAK',
				'name'   => 'Lao Kip',
				'symbol' => '₭'
			),
			'lbp' => array(
				'code'   => 'LBP',
				'name'   => 'Lebanese Pound',
				'symbol' => 'LL'
			),
			'lkr' => array(
				'code'   => 'LKR',
				'name'   => 'Sri Lankan Rupee',
				'symbol' => 'SLRs'
			),
			'lrd' => array(
				'code'   => 'LRD',
				'name'   => 'Liberian Dollar',
				'symbol' => 'L$'
			),
			'lsl' => array(
				'code'   => 'LSL',
				'name'   => 'Lesotho Loti',
				'symbol' => 'M'
			),
			'mad' => array(
				'code'   => 'MAD',
				'name'   => 'Moroccan Dirham',
				'symbol' => 'DH'
			),
			'mdl' => array(
				'code'   => 'MDL',
				'name'   => 'Moldovan Leu',
				'symbol' => 'MDL'
			),
			'mga' => array(
				'code'   => 'MGA',
				'name'   => 'Malagasy Ariary',
				'symbol' => 'Ar'
			),
			'mkd' => array(
				'code'   => 'MKD',
				'name'   => 'Macedonian Denar',
				'symbol' => 'ден'
			),
			'mnt' => array(
				'code'   => 'MNT',
				'name'   => 'Mongolian Tögrög',
				'symbol' => '₮'
			),
			'mop' => array(
				'code'   => 'MOP',
				'name'   => 'Macanese Pataca',
				'symbol' => 'MOP$'
			),
			'mro' => array(
				'code'   => 'MRO',
				'name'   => 'Mauritanian Ouguiya',
				'symbol' => 'UM'
			),
			'mur' => array(
				'code'   => 'MUR',
				'name'   => 'Mauritian Rupee',
				'symbol' => 'Rs'
			),
			'mvr' => array(
				'code'   => 'MVR',
				'name'   => 'Maldivian Rufiyaa',
				'symbol' => 'Rf.'
			),
			'mwk' => array(
				'code'   => 'MWK',
				'name'   => 'Malawian Kwacha',
				'symbol' => 'MK'
			),
			'mxn' => array(
				'code'   => 'MXN',
				'name'   => 'Mexican Peso',
				'symbol' => '$'
			),
			'myr' => array(
				'code'   => 'MYR',
				'name'   => 'Malaysian Ringgit',
				'symbol' => 'RM'
			),
			'mzn' => array(
				'code'   => 'MZN',
				'name'   => 'Mozambican Metical',
				'symbol' => 'MT'
			),
			'nad' => array(
				'code'   => 'NAD',
				'name'   => 'Namibian Dollar',
				'symbol' => 'N$'
			),
			'ngn' => array(
				'code'   => 'NGN',
				'name'   => 'Nigerian Naira',
				'symbol' => '₦'
			),
			'nio' => array(
				'code'   => 'NIO',
				'name'   => 'Nicaraguan Córdoba',
				'symbol' => 'C$'
			),
			'nok' => array(
				'code'   => 'NOK',
				'name'   => 'Norwegian Krone',
				'symbol' => 'kr'
			),
			'npr' => array(
				'code'   => 'NPR',
				'name'   => 'Nepalese Rupee',
				'symbol' => 'NRs'
			),
			'nzd' => array(
				'code'   => 'NZD',
				'name'   => 'New Zealand Dollar',
				'symbol' => 'NZ$'
			),
			'pab' => array(
				'code'   => 'PAB',
				'name'   => 'Panamanian Balboa',
				'symbol' => 'B/.'
			),
			'pen' => array(
				'code'   => 'PEN',
				'name'   => 'Peruvian Nuevo Sol',
				'symbol' => 'S/.'
			),
			'pgk' => array(
				'code'   => 'PGK',
				'name'   => 'Papua New Guinean Kina',
				'symbol' => 'K'
			),
			'php' => array(
				'code'   => 'PHP',
				'name'   => 'Philippine Peso',
				'symbol' => '₱'
			),
			'pkr' => array(
				'code'   => 'PKR',
				'name'   => 'Pakistani Rupee',
				'symbol' => 'PKR'
			),
			'pln' => array(
				'code'   => 'PLN',
				'name'   => 'Polish Złoty',
				'symbol' => 'zł'
			),
			'pyg' => array(
				'code'   => 'PYG',
				'name'   => 'Paraguayan Guaraní',
				'symbol' => '₲'
			),
			'qar' => array(
				'code'   => 'QAR',
				'name'   => 'Qatari Riyal',
				'symbol' => 'QR'
			),
			'ron' => array(
				'code'   => 'RON',
				'name'   => 'Romanian Leu',
				'symbol' => 'RON'
			),
			'rsd' => array(
				'code'   => 'RSD',
				'name'   => 'Serbian Dinar',
				'symbol' => 'дин'
			),
			'rub' => array(
				'code'   => 'RUB',
				'name'   => 'Russian Ruble',
				'symbol' => 'руб'
			),
			'rwf' => array(
				'code'   => 'RWF',
				'name'   => 'Rwandan Franc',
				'symbol' => 'FRw'
			),
			'sar' => array(
				'code'   => 'SAR',
				'name'   => 'Saudi Riyal',
				'symbol' => 'SR'
			),
			'sbd' => array(
				'code'   => 'SBD',
				'name'   => 'Solomon Islands Dollar',
				'symbol' => 'SI$'
			),
			'scr' => array(
				'code'   => 'SCR',
				'name'   => 'Seychellois Rupee',
				'symbol' => 'SRe'
			),
			'sek' => array(
				'code'   => 'SEK',
				'name'   => 'Swedish Krona',
				'symbol' => 'kr'
			),
			'sgd' => array(
				'code'   => 'SGD',
				'name'   => 'Singapore Dollar',
				'symbol' => 'S$'
			),
			'shp' => array(
				'code'   => 'SHP',
				'name'   => 'Saint Helenian Pound',
				'symbol' => '£'
			),
			'sll' => array(
				'code'   => 'SLL',
				'name'   => 'Sierra Leonean Leone',
				'symbol' => 'Le'
			),
			'sos' => array(
				'code'   => 'SOS',
				'name'   => 'Somali Shilling',
				'symbol' => 'Sh.So.'
			),
			'std' => array(
				'code'   => 'STD',
				'name'   => 'São Tomé and Príncipe Dobra',
				'symbol' => 'Db'
			),
			'srd' => array(
				'code'   => 'SRD',
				'name'   => 'Surinamese Dollar',
				'symbol' => 'SRD'
			),
			'svc' => array(
				'code'   => 'SVC',
				'name'   => 'Salvadoran Colón',
				'symbol' => '₡'
			),
			'szl' => array(
				'code'   => 'SZL',
				'name'   => 'Swazi Lilangeni',
				'symbol' => 'E'
			),
			'thb' => array(
				'code'   => 'THB',
				'name'   => 'Thai Baht',
				'symbol' => '฿'
			),
			'tjs' => array(
				'code'   => 'TJS',
				'name'   => 'Tajikistani Somoni',
				'symbol' => 'TJS'
			),
			'top' => array(
				'code'   => 'TOP',
				'name'   => 'Tongan Paʻanga',
				'symbol' => '$'
			),
			'try' => array(
				'code'   => 'TRY',
				'name'   => 'Turkish Lira',
				'symbol' => '₺'
			),
			'ttd' => array(
				'code'   => 'TTD',
				'name'   => 'Trinidad and Tobago Dollar',
				'symbol' => 'TT$'
			),
			'twd' => array(
				'code'   => 'TWD',
				'name'   => 'New Taiwan Dollar',
				'symbol' => 'NT$'
			),
			'tzs' => array(
				'code'   => 'TZS',
				'name'   => 'Tanzanian Shilling',
				'symbol' => 'TSh'
			),
			'uah' => array(
				'code'   => 'UAH',
				'name'   => 'Ukrainian Hryvnia',
				'symbol' => '₴'
			),
			'ugx' => array(
				'code'   => 'UGX',
				'name'   => 'Ugandan Shilling',
				'symbol' => 'USh'
			),
			'usd' => array(
				'code'   => 'USD',
				'name'   => 'United States Dollar',
				'symbol' => '$'
			),
			'uyu' => array(
				'code'   => 'UYU',
				'name'   => 'Uruguayan Peso',
				'symbol' => '$U'
			),
			'uzs' => array(
				'code'   => 'UZS',
				'name'   => 'Uzbekistani Som',
				'symbol' => 'UZS'
			),
			'vnd' => array(
				'code'   => 'VND',
				'name'   => 'Vietnamese Đồng',
				'symbol' => '₫'
			),
			'vuv' => array(
				'code'   => 'VUV',
				'name'   => 'Vanuatu Vatu',
				'symbol' => 'VT'
			),
			'wst' => array(
				'code'   => 'WST',
				'name'   => 'Samoan Tala',
				'symbol' => 'WS$'
			),
			'xaf' => array(
				'code'   => 'XAF',
				'name'   => 'Central African Cfa Franc',
				'symbol' => 'FCFA'
			),
			'xcd' => array(
				'code'   => 'XCD',
				'name'   => 'East Caribbean Dollar',
				'symbol' => 'EC$'
			),
			'xof' => array(
				'code'   => 'XOF',
				'name'   => 'West African Cfa Franc',
				'symbol' => 'CFA'
			),
			'xpf' => array(
				'code'   => 'XPF',
				'name'   => 'Cfp Franc',
				'symbol' => 'F'
			),
			'yer' => array(
				'code'   => 'YER',
				'name'   => 'Yemeni Rial',
				'symbol' => '﷼'
			),
			'zar' => array(
				'code'   => 'ZAR',
				'name'   => 'South African Rand',
				'symbol' => 'R'
			),
			'zmw' => array(
				'code'   => 'ZMW',
				'name'   => 'Zambian Kwacha',
				'symbol' => 'ZK'
			)
		);
	}

	function get_payment_form_by_style( $styleID ) {
		switch ( $styleID ) {
			case - 1:
				return B60_CORE . '/pages/forms/b60_invalid_shortcode.php';

			case 0:				
				return B60_CORE . '/pages/forms/b60_step_form.php';

			case 1:				
				return B60_CORE . '/pages/forms/b60_payment_form.php';

			default:
				return B60_CORE . '/pages/forms/b60_payment_form.php';
		}
	}

	function get_giftcard_form_by_style( $styleID ) {
		switch ( $styleID ) {
			case - 1:
				return B60_CORE . '/pages/forms/b60_invalid_shortcode.php';

			case 0:				
				return B60_CORE . '/pages/forms/b60_gift_card_1.php';

			case 1:				
				return B60_CORE . '/pages/forms/b60_gift_card_2.php';

			default:
				return B60_CORE . '/pages/forms/b60_gift_card_1.php';
		}
	}

}

B60::getInstance();
