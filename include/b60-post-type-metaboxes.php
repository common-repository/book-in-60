<?php
/**
 * Register Metaboxes
 *
 * @link       https://weblaunchlocal.com
 * @since      1.0.0
 *
 * @package    Booking_60
 * @subpackage Booking_60/includes
 */

class B60_Post_Type_Metaboxes {


	public function register_metabox() {
		add_action( 'add_meta_boxes', array( $this, 'services_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_services_meta_box' ),  10, 2 );
		add_action( 'save_post', array( $this, 'save_pricing_parameters_meta_box' ),  20, 2 );
		add_action( 'save_post', array( $this, 'save_in_pricing_parameters_meta_box' ),  20, 2 );
		add_action( 'save_post', array( $this, 'save_service_addons_meta_box' ),  20, 2 );
		add_action( 'save_post', array( $this, 'save_service_frequency_meta_box' ),  30, 2 );
		add_action( 'save_post', array( $this, 'save_addon_cat_meta_box'), 40, 2 );	
		add_action('wp_ajax_price_range', array( $this, 'price_range'));

	}

	public function price_range(){
		$id   = uniqid();
	?>
		<tr class="price-range-content">
		    <td class="col-sm-2" style="width: 27.5%;">
		      <input class="col-sm-3 form-control range-label" id="range-label" name="ranges[<?php echo esc_attr( $id ); ?>][label]" placeholder="i.e. '1000-1500 sq/ft'" type="text" value="<?php echo esc_attr( $ranges[$id]['label'] ); ?>">
		    </td>
		    <td class="col-sm-1" style="width: 10%;">
		      <input class="form-control input-xs range-quantity-minimum" id="range-qty-min" name="ranges[<?php echo esc_attr( $id ); ?>][range_qty_min]" type="number" value="<?php echo esc_attr( $ranges[$id]['range_qty_min'] ); ?>">
		    </td>
		    <td class="col-sm-1" style="width: 10%;">
		      <input class="form-control input-xs range-quantity-maximum" id="range-qty-max" name="ranges[<?php echo esc_attr( $id ); ?>][range_qty_max]" type="number" value="<?php echo esc_attr( $ranges[$id]['range_qty_max'] ); ?>">
		    </td>
		    <td class="col-sm-1" style="width: 14%;">
		      <span class="input-group-addon-l">$</span><input type="number" step="0.01" id="range-price" name="ranges[<?php echo esc_attr( $id ); ?>][range_price]" class="form-control input-xs range-price left-span" value="<?php echo esc_attr( number_format((float)$ranges[$id]['range_price'], 2, '.', '') ); ?>">
		    </td>
		    <td style="width: 45%;">
		      <div class="col-sm-6" style="float:left;padding-right: 14px;">
		        <div class="input-group">
		          <input class="form-control input-xs range-hours right-span n-b-r" type="number" min="0" max="24" name="ranges[<?php echo esc_attr( $id ); ?>][range_hours]" value="<?php echo esc_attr( $ranges[$id]['range_hours'] ); ?>">
		          <span class="input-group-addon">
		            hr
		          </span>
		        </div>
		      </div>
		      <div class="col-sm-6 row">
		        <div class="input-group">
		          <input class="form-control input-xs range-minutes right-span n-b-r" type="number" min="0" max="59" name="ranges[<?php echo esc_attr( $id ); ?>][range_minutes]" value="<?php echo esc_attr( $ranges[$id]['range_minutes'] ); ?>">
		          <span class="input-group-addon">
		            min
		          </span>
		        </div>
		      </div>
		    </td>
		    <td>
		    	<span class="delete-price-parameter" id="delete-price-parameter"></span>
		    </td>
		</tr>
	<?php
		wp_die();
	}

	public function services_meta_boxes() {
		add_meta_box('services_fields', 'Services', array( $this, 'render_services_meta_boxes'), 'service', 'normal', 'high');
		add_meta_box('pricing_parameters_fields', 'Pricing Parameters', array( $this, 'render_pricing_parameters_meta_boxes'), 'pricing_parameters', 'normal', 'high');
		add_meta_box('service_pricing_parameters', 'Services', array( $this, 'render_service_pricing_parameters_meta_boxes'), 'pricing_parameters', 'normal', 'high');
		add_meta_box('service_addons_fields', 'Service Addons', array( $this, 'render_service_addons_meta_boxes'), 'service_addons', 'normal', 'high');
		add_meta_box('service_addons_cats', 'Services', array( $this, 'render_service_addons_cats_meta_boxes'), 'service_addons', 'normal', 'high');
		add_meta_box('service_frequency_fields', 'Service Frequency', array( $this, 'render_service_frequency_meta_boxes'), 'service_frequencies', 'normal', 'high');		
	}

	function render_services_meta_boxes( $post ) {

		$meta = get_post_custom( $post->ID );
		$service_order_no = ! isset( $meta['service_order_no'][0] ) ? '' : esc_attr( $meta['service_order_no'][0] );
		$service_title = ! isset( $meta['service_title'][0] ) ? '' : esc_attr( $meta['service_title'][0] );
		$service_price = ! isset( $meta['service_price'][0] ) ? '' : esc_attr( $meta['service_price'][0] );
		$hourly_rate = ! isset( $meta['hourly_rate'][0] ) ? '' : esc_attr( $meta['hourly_rate'][0] );
		$allow_increments = ! isset( $meta['allow_increments'][0] ) ? '' : esc_attr( $meta['allow_increments'][0] );
		$duration_hours = ! isset( $meta['service_duration_hours'][0] ) ? 0 : esc_attr( $meta['service_duration_hours'][0] );
		$duration_minutes = ! isset( $meta['service_duration_minutes'][0] ) ? 0 : esc_attr( $meta['service_duration_minutes'][0] );
		$cleaners_from = ! isset( $meta['cleaners_from'][0] ) ? 1 : esc_attr( $meta['cleaners_from'][0] );
		$cleaners_to = ! isset( $meta['cleaners_to'][0] ) ? 1 : esc_attr( $meta['cleaners_to'][0] );
		$hours_from = ! isset( $meta['hours_from'][0] ) ? 1 : esc_attr( $meta['hours_from'][0] );
		$hours_to = ! isset( $meta['hours_to'][0] ) ? 1 : esc_attr( $meta['hours_to'][0] );

		wp_nonce_field( basename( __FILE__ ), 'services_fields' ); ?>

		<table class="form-table">

			<tr>
				<td class="services_meta_box_td" colspan="2">
					<label for="order-no"><strong><?php _e( 'Order No.', 'service-post-type' ); ?></strong> </label>
				</td>
				<td colspan="4">
					<input type="text" id="service_order_no" name="service_order_no" class="small-input" value="<?php echo esc_attr( $service_order_no ); ?>">
				</td>
			</tr>

			<tr>
				<td class="services_meta_box_td" colspan="2">
					<label for="price"><strong><?php _e( 'Price', 'service-post-type' ); ?></strong> <span style="color: #f00;">*</span></label>
				</td>
				<td colspan="4">
					<span class="input-group-addon-l">$</span><input type="text" id="service_price" name="service_price" class="regular-text" value="<?php echo esc_attr( $service_price ); ?>">
				</td>
			</tr>

			<tr>
				<td class="services_meta_box_td" colspan="2"></td>
				<td colspan="4">
					<input type="checkbox" name="hourly_rate" id="hourly_rate" class="regular-text" <?php echo esc_attr( $hourly_rate ); ?> > Hourly Service
				</td>
			</tr>

			<tr id="duration-container" style="display:<?php echo ( esc_attr( $hourly_rate ) !== 'checked' ) ? 'table-row' : 'none' ?>">
				<td class="services_meta_box_td" colspan="2">
					<label for="duration"><strong><?php _e( 'Duration', 'service-post-type' ); ?></strong> <span style="color: #f00;">*</span></label>
				</td>
				<td colspan="4">
					<input type="text" id="duration-hours" name="duration_hours" class="regular-text" value="<?php echo esc_attr( $duration_hours ); ?>"><span class="input-group-addon">hours</span>
					<input type="text" id="duration-minutes" name="duration_minutes" class="regular-text" value="<?php echo esc_attr( $duration_minutes ); ?>"><span class="input-group-addon">minutes</span>
				</td>
			</tr>

			<tr id="cleaners-container" style="display:<?php echo ( esc_attr( $hourly_rate ) == 'checked' ) ? 'table-row' : 'none' ?>">
				<td class="services_meta_box_td" colspan="2">
					<label for="cleaners"><strong><?php _e( 'Cleaners', 'service-post-type' ); ?></strong></label>
				</td>
				<td colspan="4">
					<input type="text" id="cleaners_from" name="cleaners_from" class="small-input" value="<?php echo esc_attr( $cleaners_from ); ?>">&nbsp;&nbsp;&nbsp;to&nbsp;&nbsp;&nbsp;<input type="text" id="cleaners_to" name="cleaners_to" class="small-input" value="<?php echo esc_attr( $cleaners_to ); ?>">
				</td>
			</tr>

			<tr id="hourly-container" style="display:<?php echo ( esc_attr( $hourly_rate ) == 'checked' ) ? 'table-row' : 'none' ?>">
				<td class="services_meta_box_td" colspan="2">
					<label for="cleaners"><strong><?php _e( 'Hours', 'service-post-type' ); ?></strong></label>
				</td>
				<td colspan="4">
					<input type="text" id="hours_from" name="hours_from" class="small-input" value="<?php echo esc_attr( $hours_from ); ?>">&nbsp;&nbsp;&nbsp;to&nbsp;&nbsp;&nbsp;<input type="text" id="hours_to" name="hours_to" class="small-input" value="<?php echo esc_attr( $hours_to ); ?>">
				</td>
			</tr>
		</table>

	<?php }

	function save_services_meta_box( $post_id ) {

		global $post;

		// Verify nonce
		if ( !isset( $_POST['services_fields'] ) || !wp_verify_nonce( $_POST['services_fields'], basename(__FILE__) ) ) {
			return $post_id;
		}

		// Check Autosave
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ( defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']) ) {
			return $post_id;
		}

		// Don't save if only a revision
		if ( isset( $post->post_type ) && $post->post_type == 'revision' ) {
			return $post_id;
		}

		// Check permissions
		if ( !current_user_can( 'edit_post', $post->ID ) ) {
			return $post_id;
		}		

		$meta['service_order_no'] = ( isset( $_POST['service_order_no'] ) ? sanitize_text_field( $_POST['service_order_no'] ) : '' );
		
		$meta['service_price'] = ( isset( $_POST['service_price'] ) ? number_format((float)sanitize_text_field( $_POST['service_price'] ), 2, '.', '') : '' );

		$meta['service_duration_hours'] = ( isset( $_POST['duration_hours'] ) ? sanitize_text_field( $_POST['duration_hours'] ) : '' );

		$meta['service_duration_minutes'] = ( isset( $_POST['duration_minutes'] ) ? sanitize_text_field( $_POST['duration_minutes'] ) : '' );
		
		$meta['hourly_rate'] = ( isset( $_POST['hourly_rate'] ) ? 'checked' : '' );
		
		$meta['allow_increments'] = ( isset( $_POST['allow_increments'] ) ? 'checked' : '' );
		
		$meta['cleaners_from'] = ( isset( $_POST['cleaners_from'] ) ? sanitize_text_field( $_POST['cleaners_from'] ) : '' );
		
		$meta['cleaners_to'] = ( isset( $_POST['cleaners_to'] ) ? sanitize_text_field( $_POST['cleaners_to'] ) : '' );
		
		$meta['hours_from'] = ( isset( $_POST['hours_from'] ) ? sanitize_text_field( $_POST['hours_from'] ) : '' );
		
		$meta['hours_to'] = ( isset( $_POST['hours_to'] ) ? sanitize_text_field( $_POST['hours_to'] ) : '' );	

		foreach ( $meta as $key => $value ) {
			update_post_meta($post->ID, $key, $value);
		}
	}


	function render_service_addons_meta_boxes( $post ) {

		$meta = get_post_custom( $post->ID );
		$service_addon_order_no = ! isset( $meta['service_addon_order_no'][0] ) ? '' : esc_attr( $meta['service_addon_order_no'][0] );
		$addon_price = ! isset( $meta['addon_price'][0] ) ? '' : esc_attr( $meta['addon_price'][0] );
		$addon_description = ! isset( $meta['addon_description'][0] ) ? '' : esc_attr( $meta['addon_description'][0] );
		$quantity_based = ! isset( $meta['quantity_based'][0] ) ? '' : esc_attr( $meta['quantity_based'][0] );
		$addon_code_discounts = ! isset( $meta['addon_code_discounts'][0] ) ? '' : esc_attr( $meta['addon_code_discounts'][0] );
		$addon_recurring = ! isset( $meta['addon_recurring'][0] ) ? '' : esc_attr( $meta['addon_recurring'][0] );
		$addon_duration_hours = ! isset( $meta['addon_duration_hours'][0] ) ? 0 : esc_attr( $meta['addon_duration_hours'][0] );
		$addon_duration_minutes = ! isset( $meta['addon_duration_minutes'][0] ) ? 0 : esc_attr( $meta['addon_duration_minutes'][0] );
		$addon_sales_tax = ! isset( $meta['addon_sales_tax'][0] ) ? 0 : esc_attr( $meta['addon_sales_tax'][0] );

		wp_nonce_field( basename( __FILE__ ), 'service_addons_fields' ); ?>

		<table class="form-table">

			<tr>
				<td class="services_meta_box_td" colspan="2">
					<label for="order-no"><strong><?php _e( 'Order No.', 'service_addons' ); ?></strong> </label>
				</td>
				<td colspan="4">
					<input type="text" id="service_addon_order_no" name="service_addon_order_no" class="small-input" value="<?php echo esc_attr( $service_addon_order_no ); ?>">
				</td>
			</tr>

			<tr>
				<td class="services_meta_box_td" colspan="2">
					<label for="addon-price"><strong><?php _e( 'Price', 'service_addons' ); ?></strong> <span style="color: #f00;">*</span></label>
				</td>
				<td colspan="4">
					<span class="input-group-addon-l">$</span><input type="text" id="addon_price" name="addon_price" class="regular-text" value="<?php echo esc_attr( $addon_price ); ?>">
				</td>
			</tr>

			<tr>
				<td class="services_meta_box_td" colspan="2"></td>
				<td colspan="4"><input name="quantity_based" type="checkbox" id="quantity_based" value="1" <?php echo esc_attr( $quantity_based ); ?>> Quantity-Based</td>
			</tr>

			<tr>
				<td class="services_meta_box_td" colspan="2">
					<label for="addon-price"><strong><?php _e( 'Description', 'service_addons' ); ?></strong> <span style="color: #f00;">*</span></label>
				</td>
				<td colspan="4">
					<textarea id="addon_description" name="addon_description" class="regular-text"><?php echo esc_textarea( $addon_description ); ?></textarea>
				</td>
			</tr>

			<tr>
				<td class="services_meta_box_td" colspan="2">
					<label for="duration"><strong><?php _e( 'Duration', 'service_addons' ); ?></strong></label>
				</td>
				<td colspan="4">
					<input type="text" id="addon_duration_hours" name="addon_duration_hours" class="small-input" value="<?php echo esc_attr( $addon_duration_hours); ?>"><span class="input-group-addon">hours</span>
					<input type="text" id="addon_duration_minutes" name="addon_duration_minutes" class="small-input" value="<?php echo esc_attr( $addon_duration_minutes ); ?>"><span class="input-group-addon">minutes</span>
				</td>
			</tr>

			<tr>
				<td class="services_meta_box_td" colspan="2">
					<label for="duration"><strong><?php _e( 'Sales Tax', 'service_addons' ); ?></strong></label>
				</td>
				<td colspan="4">
					<input type="text" id="addon_sales_tax" name="addon_sales_tax" class="small-input" value="<?php echo esc_attr( $addon_sales_tax ); ?>"><span class="input-group-addon">%</span>
				</td>
			</tr>
		</table>

	<?php }

	function save_service_addons_meta_box( $post_id ) {

		global $post;

		// Verify nonce
		if ( !isset( $_POST['service_addons_fields'] ) || !wp_verify_nonce( $_POST['service_addons_fields'], basename(__FILE__) ) ) {
			return $post_id;
		}

		// Check Autosave
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ( defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']) ) {
			return $post_id;
		}

		// Don't save if only a revision
		if ( isset( $post->post_type ) && $post->post_type == 'revision' ) {
			return $post_id;
		}

		// Check permissions
		if ( !current_user_can( 'edit_post', $post->ID ) ) {
			return $post_id;
		}

		$meta['service_addon_order_no'] = ( isset( $_POST['service_addon_order_no'] ) ? sanitize_text_field( $_POST['service_addon_order_no'] ) : '' );
		
		$meta['addon_price'] = ( isset( $_POST['addon_price'] ) ? number_format((float)sanitize_text_field( $_POST['addon_price'] ), 2, '.', '') : '' );
		
		$meta['addon_description'] = ( isset( $_POST['addon_description'] ) ? sanitize_textarea_field($_POST['addon_description']) : '' );
		
		$meta['quantity_based'] = ( isset( $_POST['quantity_based'] ) ? 'checked' : '' );

		$meta['addon_recurring'] = ( isset( $_POST['addon_recurring'] ) ? sanitize_text_field( $_POST['addon_recurring'] ) : '' );

		$meta['addon_duration_hours'] = ( isset( $_POST['addon_duration_hours'] ) ? sanitize_text_field( $_POST['addon_duration_hours'] ) : '' );

		$meta['addon_duration_minutes'] = ( isset( $_POST['addon_duration_minutes'] ) ? sanitize_text_field( $_POST['addon_duration_minutes'] ) : '' );
		
		$meta['addon_sales_tax'] = ( isset( $_POST['addon_sales_tax'] ) ? sanitize_text_field( $_POST['addon_sales_tax'] ) : '' );

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post->ID, $key, $value );
		}
	}

	function render_pricing_parameters_meta_boxes( $post ) {

		$meta = get_post_custom( $post->ID );
		$parameter_order_no = ! isset( $meta['parameter_order_no'][0] ) ? '' : esc_attr( $meta['parameter_order_no'][0] );
		$pricing_parameter_price = ! isset( $meta['pricing_parameter_price'][0] ) ? '' : esc_attr( $meta['pricing_parameter_price'][0] );
		$parameter_selection = ! isset( $meta['parameter_selection'][0] ) ? 'flat_price' : esc_attr( $meta['parameter_selection'][0] );
		$flat_price = ! isset( $meta['flat_price'][0] ) ? '' : esc_attr( $meta['flat_price'][0] );
		$flat_price_amount = ! isset( $meta['flat_price_amount'][0] ) ? '' : esc_attr( $meta['flat_price_amount'][0] );
		$value_from = ! isset( $meta['value_from'][0] ) ? 1 : esc_attr( $meta['value_from'][0] );
		$value_to = ! isset( $meta['value_to'][0] ) ? 1 : esc_attr( $meta['value_to'][0] );
		$pricing_parameter_duration_hours = ! isset( $meta['pricing_parameter_duration_hours'][0] ) ? 0 : esc_attr( $meta['pricing_parameter_duration_hours'][0] );
		$pricing_parameter_duration_minutes = ! isset( $meta['pricing_parameter_duration_minutes'][0] ) ? 0 : esc_attr( $meta['pricing_parameter_duration_minutes'][0] );
		$ranges = ! isset( $meta['ranges'][0] ) ? '' : esc_attr( $meta['ranges'][0] );

		wp_nonce_field( basename( __FILE__ ), 'pricing_parameters_fields' ); ?>

		<table class="form-table">

			<tr>
				<td class="services_meta_box_td" colspan="2" style="width: 20%;">
					<label for="order-no"><strong><?php _e( 'Order No.', 'pricing_parameters' ); ?></strong> </label>
				</td>
				<td colspan="4">
					<input type="text" id="parameter_order_no" name="parameter_order_no" class="small-input" value="<?php echo esc_attr( $parameter_order_no ); ?>">
				</td>
			</tr>

			<tr>
				<td class="services_meta_box_td" colspan="2">
					<label for="pricing"><strong><?php _e( 'Pricing', 'pricing_parameters' ); ?></strong></label>
				</td>
				<td colspan="4">
					<label class="radio radio-block">
						<input type="radio" name="parameter_selection" id="flat_price_radio" value="flat_price" <?php echo ( esc_attr( $parameter_selection ) == 'flat_price' ) ? 'checked' : '' ?>>Flat Pricing <i>($10 each)</i>
					</label>
					<label class="radio radio-block">
						<input type="radio" name="parameter_selection" id="price_ranges_radio" value="price_ranges" <?php echo ( esc_attr( $parameter_selection ) == 'price_ranges' ) ? 'checked' : '' ?>>Price Ranges <i>(1-10 = $10, 11-20 = $20, 21-30 = $30)</i>
					</label>
				</td>

			</tr>

			<tr id="flat_pricing_container" style="display:<?php echo ( esc_attr( $parameter_selection ) == 'flat_price' ) ? 'table-row' : 'none' ?>">
				<td class="services_meta_box_td" colspan="2"></td>
				<td colspan="4">
					<div><span class="input-group-addon-l">$</span><input type="number" step="0.01" id="flat_price_amount" name="flat_price_amount" class="regular-text flat_price-input" value="<?php echo esc_attr( $flat_price_amount ); ?>"><span class="input-group-addon">each</span></div>
				</td>
			</tr>

			<tr id="price_ranges_container" style="display:<?php echo ( esc_attr( $parameter_selection ) == 'price_ranges' ) ? 'table-row' : 'none' ?>">
				<td class="services_meta_box_td" colspan="2">
					<label for="order-no"><strong><?php _e( 'Range', 'pricing_parameters' ); ?></strong> </label>
				</td>
				<td colspan="4">
					<div>
						<table class="table table-header table-price-ranges table-hover">
						  <thead>
						    <tr>
						      <th style="width: 27.5%;">Label*</th>
						      <th style="width: 10%;">Min*</th>
						      <th style="width: 10%;">Max*</th>
						      <th style="width: 16%;">Price*</th>
						      <!-- ngIf: ctrl.settings.features.booking.duration -->
						      <th style="width: 45%;">
						        Duration
						      </th><!-- end ngIf: ctrl.settings.features.booking.duration -->
						      <th>
						      </th>
						    </tr>
						  </thead>
						<tbody class="price-range-body">
						<?php
							$ranges = get_post_meta($post->ID,'ranges', true);
							//var_dump($ranges);
	        				if ($ranges) {			            
		    					foreach ($ranges as $key => $range) {  ?>
			    					<tr class="price-range-content">
			    					    <td class="col-sm-2" style="width: 27.5%;">
			    					      <input class="col-sm-3 form-control range-label" name="ranges[<?php echo esc_attr( $key ); ?>][label]" id="range-label" placeholder="i.e. '1000-1500 sq/ft'" type="text" value="<?php echo esc_attr( $ranges[$key]['label'] ); ?>" required>
			    					    </td>
			    					    <td class="col-sm-1" style="width: 10%;">
			    					      <input class="form-control input-xs range-quantity-minimum" name="ranges[<?php echo esc_attr( $key ); ?>][range_qty_min]" id="range-qty-min" type="number" value="<?php echo esc_attr( $ranges[$key]['range_qty_min'] ); ?>" required>
			    					    </td>
			    					    <td class="col-sm-1" style="width: 10%;">
			    					      <input class="form-control input-xs range-quantity-maximum" name="ranges[<?php echo esc_attr( $key ); ?>][range_qty_max]" id="range-qty-max" type="number" minlength="2" value="<?php echo esc_attr( $ranges[$key]['range_qty_max'] ); ?>" required>
			    					    </td>
			    					    <td class="col-sm-1" style="width: 14%;">
			    					      <span class="input-group-addon-l">$</span><input type="number" step="0.01" name="ranges[<?php echo esc_attr( $key ); ?>][range_price]" id="range-price" class="form-control input-xs range-price left-span" value="<?php echo esc_attr( number_format((float)$ranges[$key]['range_price'], 2, '.', '') ); ?>" required>
			    					    </td>
			    					    <td style="width: 45%;">
			    					      <div class="col-sm-6" style="float:left;padding-right: 14px;">
			    					        <div class="input-group">
			    					          <input class="form-control input-xs range-hours right-span n-b-r" name="ranges[<?php echo esc_attr( $key ); ?>][range_hours]" type="number" min="0" max="24" value="<?php echo esc_attr( $ranges[$key]['range_hours'] ); ?>">
			    					          <span class="input-group-addon">
			    					            hr
			    					          </span>
			    					        </div>
			    					      </div>
			    					      <div class="col-sm-6 row">
			    					        <div class="input-group">
			    					          <input class="form-control input-xs range-minutes right-span n-b-r" type="number" min="0" max="59" name="ranges[<?php echo esc_attr( $key ); ?>][range_minutes]" value="<?php echo esc_attr( $ranges[$key]['range_minutes'] ); ?>">
			    					          <span class="input-group-addon">
			    					            min
			    					          </span>
			    					        </div>
			    					      </div>
			    					    </td>
			    					    <td>
			    					    	<span class="delete-price-parameter" id="delete-price-parameter"></span>
			    					    </td>
			    					</tr>
		    				<?php	}
		       				} else {
		       					$key = uniqid();
		    					?>
							<tr class="price-range-content">
							    <td class="col-sm-2" style="width: 27.5%;">
							      <input class="col-sm-3 form-control range-label" name="ranges[<?php echo esc_attr( $key ); ?>][label]" id="range-label" placeholder="i.e. '1000-1500 sq/ft'" type="text" required>
							    </td>
							    <td class="col-sm-1" style="width: 10%;">
							      <input class="form-control input-xs range-quantity-minimum" name="ranges[<?php echo esc_attr( $key ); ?>][range_qty_min]" id="range-qty-min" type="number" required>
							    </td>
							    <td class="col-sm-1" style="width: 10%;">
							      <input class="form-control input-xs range-quantity-maximum" name="ranges[<?php echo esc_attr( $key ); ?>][range_qty_max]" id="range-qty-max" type="number" required>
							    </td>
							    <td class="col-sm-1" style="width: 14%;">
							      <span class="input-group-addon-l">$</span><input type="number" step="0.01" name="ranges[<?php echo esc_attr( $key ); ?>][range_price]" id="range-price" class="form-control input-xs range-price left-span" required>
							    </td>
							    <td style="width: 45%;">
							      <div class="col-sm-6" style="float:left;padding-right: 14px;">
							        <div class="input-group">
							          <input class="form-control input-xs range-hours right-span n-b-r" type="number" min="0" max="24" name="ranges[<?php echo esc_attr( $key ); ?>][range_hours]">
							          <span class="input-group-addon">
							            hr
							          </span>
							        </div>
							      </div>
							      <div class="col-sm-6 row">
							        <div class="input-group">
							          <input class="form-control input-xs range-minutes right-span n-b-r" type="number" min="0" max="59" name="ranges[<?php echo esc_attr( $key ); ?>][range_minutes]">
							          <span class="input-group-addon">
							            min
							          </span>
							        </div>
							      </div>
							    </td>
							    <td>
							    	<span class="delete-price-parameter" id="delete-price-parameter"></span>
							    </td>
							</tr>
						<?php
		    				}
						?>
						  </tbody>
						  <tfoot>
								<tr id="min-max-error" style="display:none">
									<td colspan="6"><span style="color:red">Entered maximum number should be greater than or equal to minimum number</span></td>
								</tr>
								<tr>
									<td colspan="6"><div class="add_price_range button button-ga">Add more</div></td>
								</tr>
							</tfoot>
						</table>
					</div>
				</td>
			</tr>

			<tr id="values-container" style="display:<?php echo ( esc_attr( $parameter_selection ) == 'flat_price' ) ? 'table-row' : 'none' ?>">
				<td class="services_meta_box_td" colspan="2">
					<label for="values"><strong><?php _e( 'Values', 'pricing_parameters' ); ?></strong></label>
				</td>
				<td colspan="4">
					<input type="text" id="hours_from" name="value_from" class="small-input" value="<?php echo esc_attr( $value_from ); ?>">&nbsp;&nbsp;&nbsp;to&nbsp;&nbsp;&nbsp;<input type="text" id="value_to" name="value_to" class="small-input" value="<?php echo esc_attr( $value_to ); ?>">
				</td>
			</tr>

			<tr id="parameter-duration-container" style="display:<?php echo ( esc_attr( $parameter_selection ) == 'flat_price' ) ? 'table-row' : 'none' ?>">
				<td class="services_meta_box_td" colspan="2">
					<label for="duration"><strong><?php _e( 'Duration', 'pricing_parameters' ); ?></strong></label>
				</td>
				<td colspan="4">
					<input type="text" id="pricing_parameter_duration_hours" name="pricing_parameter_duration_hours" class="small-input" value="<?php echo esc_attr( $pricing_parameter_duration_hours ); ?>"><span class="input-group-addon">hours</span>
					<input type="text" id="pricing_parameter_duration_minutes" name="pricing_parameter_duration_minutes" class="small-input" value="<?php echo esc_attr( $pricing_parameter_duration_minutes ); ?>"><span class="input-group-addon">minutes</span>
				</td>
			</tr>

		</table>

	<?php }

	function save_pricing_parameters_meta_box( $post_id ) {

		global $post;

		// Verify nonce
		if ( !isset( $_POST['pricing_parameters_fields'] ) || !wp_verify_nonce( $_POST['pricing_parameters_fields'], basename(__FILE__) ) ) {
			return $post_id;
		}

		// Check Autosave
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ( defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']) ) {
			return $post_id;
		}

		// Don't save if only a revision
		if ( isset( $post->post_type ) && $post->post_type == 'revision' ) {
			return $post_id;
		}

		// Check permissions
		if ( !current_user_can( 'edit_post', $post->ID ) ) {
			return $post_id;
		}

		$meta['parameter_order_no'] = ( isset( $_POST['parameter_order_no'] ) ? sanitize_text_field( $_POST['parameter_order_no'] ) : '' );

		$meta['parameter_selection'] = ( isset( $_POST['parameter_selection'] ) ? sanitize_text_field( $_POST['parameter_selection'] )  : '' );
		
		$meta['flat_price_amount'] = ( isset( $_POST['flat_price_amount'] ) ? number_format((float)sanitize_text_field( $_POST['flat_price_amount'] ), 2, '.', '')  : '' );
		
		$meta['value_to'] = ( isset( $_POST['value_to'] ) ? sanitize_text_field( $_POST['value_to'] )  : '' );
		
		$meta['value_from'] = ( isset( $_POST['value_from'] ) ? sanitize_text_field( $_POST['value_from'] )  : '' );

		$meta['pricing_parameter_duration_hours'] = ( isset( $_POST['pricing_parameter_duration_hours'] ) ? sanitize_text_field( $_POST['pricing_parameter_duration_hours'] ) : '' );

		$meta['pricing_parameter_duration_minutes'] = ( isset( $_POST['pricing_parameter_duration_minutes'] ) ? sanitize_text_field( $_POST['pricing_parameter_duration_minutes'] ) : '' );

		$ranges = $_POST['ranges'];

		array_walk($arr, function(&$value, &$key) {
		    $value['label'] = sanitize_text_field($value['label']);
		    $value['range_qty_min'] = sanitize_text_field($value['range_qty_min']);
		    $value['range_qty_max'] = sanitize_text_field($value['range_qty_max']);
		    $value['range_price'] = sanitize_text_field($value['range_price']);
		    $value['range_hours'] = sanitize_text_field($value['range_hours']);
		    $value['range_minutes'] = sanitize_text_field($value['range_minutes']);
		});


		$meta['ranges'] = ( isset( $_POST['ranges'] ) ?  $ranges : '' );

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post->ID, $key, $value );
		}
	}

	function render_service_addons_cats_meta_boxes( $post_object ) {
		 
			$html = '';
		 
			$appended_tags = get_post_meta( $post_object->ID, 'addon_select2_cat',true );
			$appended_posts = get_post_meta( $post_object->ID, 'addon_select2_posts',true );

			$posts = get_posts([
			  'post_type' => 'service',
			  'post_status' => 'publish',
			  'posts_per_page' => -1
			  // 'order'    => 'ASC'
			]);

			$html .= '<select id="addon_select2_cat" name="addon_select2_cat[]" multiple="multiple" style="width:99%;max-width:25em;">';
				foreach( $posts as $post ) {
					$selected = ( is_array( $appended_tags ) && in_array( $post->ID, $appended_tags ) ) ? ' selected="selected"' : '';
					$html .= '<option value="' .  esc_attr( $post->ID ) . '"' .  esc_attr( $selected ) . '>' . esc_html( $post->post_title ) . '</option>';
				}
			$html .= '<select>';
		 
			echo $html;
	}
		 
	function save_addon_cat_meta_box( $post_id, $post ) {
	 
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
	 
		if ( $post->post_type == 'service_addons' ) {
			if( isset( $_POST['addon_select2_cat'] ) )
				update_post_meta( $post_id, 'addon_select2_cat', sanitize_user_object( $_POST['addon_select2_cat'] ) );
			else
				delete_post_meta( $post_id, 'addon_select2_cat' );
		}
		return $post_id;
	}

	function render_service_pricing_parameters_meta_boxes( $post_object ) {
		 
			$html = '';
		 
			$appended_tags = get_post_meta( $post_object->ID, 'addon_select2_cat',true );
			$appended_posts = get_post_meta( $post_object->ID, 'addon_select2_posts',true );

			$posts = get_posts([
			  'post_type' => 'service',
			  'post_status' => 'publish',
			  'posts_per_page' => -1
			  // 'order'    => 'ASC'
			]);

			$html .= '<select id="addon_select2_cat" name="addon_select2_cat[]" multiple="multiple" style="width:99%;max-width:25em;">';
				foreach( $posts as $post ) {
					$selected = ( is_array( $appended_tags ) && in_array( $post->ID, $appended_tags ) ) ? ' selected="selected"' : '';
					$html .= '<option value="' .  esc_attr( $post->ID ) . '"' .  esc_attr( $selected ) . '>' . esc_html( $post->post_title ) . '</option>';
				}

			$html .= '</select>';

			echo $html;
	}
		 
	function save_in_pricing_parameters_meta_box( $post_id, $post ) {
	 
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
	 
		if ( $post->post_type == 'pricing_parameters' ) {
			if( isset( $_POST['addon_select2_cat'] ) )
				update_post_meta( $post_id, 'addon_select2_cat', sanitize_user_object( $_POST['addon_select2_cat'] ) );
			else
				delete_post_meta( $post_id, 'addon_select2_cat' );
		}
		return $post_id;
	}

	function render_service_frequency_meta_boxes( $post ) {

		$meta = get_post_custom( $post->ID );
		$frequency = ! isset( $meta['frequency'][0] ) ? '' : esc_attr( $meta['frequency'][0] );
		$frequency_discount = ! isset( $meta['frequency_discount'][0] ) ? 0 : esc_attr( $meta['frequency_discount'][0] );
		$amount_discount = ! isset( $meta['amount_discount'][0] ) ? 0 : esc_attr( $meta['amount_discount'][0] );
		$discount_f = ! isset( $meta['discount_f'][0] ) ? 'percentage_d' : esc_attr( $meta['discount_f'][0] );
		$default_frequency = ! isset( $meta['default_frequency'][0] ) ? '' : esc_attr( $meta['default_frequency'][0] );
		$repeats_every = ! isset( $meta['repeats_every'][0] ) ? '' : esc_attr( $meta['repeats_every'][0] );

		wp_nonce_field( basename( __FILE__ ), 'service_frequency_fields' ); ?>

		<table class="form-table">

			<tr>
				<td class="services_meta_box_td" colspan="2">
					<label for="Frequency"><strong><?php _e( 'Frequency', 'frequency' ); ?></strong></label>
				</td>
				<td colspan="4">
					<select class="form-control" id="frequency" name="frequency">
						<option label="One time" value="o" selected="selected" <?php selected($frequency, "o"); ?>>One time</option>
						<option label="Every Week" value="1w" <?php selected($frequency, "1w"); ?>>Every Week</option>
						<option label="Every 2 Weeks" value="2w" <?php selected($frequency, "2w"); ?>>Every 2 Weeks</option>
						<option label="Every 3 Weeks" value="3w" <?php selected($frequency, "3w"); ?>>Every 3 Weeks</option>
						<option label="Every 4 Weeks" value="4w" <?php selected($frequency, "4w"); ?>>Every 4 Weeks</option>
						<option label="Custom" value="c" <?php selected($frequency, "c"); ?>>Custom</option>
					</select>
				</td>
			</tr>

			<tr id="repeats-week-container" style="display:<?php echo ( esc_attr( $frequency ) == 'c' ) ? 'table-row' : 'none' ?>">
				<td class="services_meta_box_td" colspan="2">
					<label for="repeats"><strong><?php _e( 'Repeats every', 'frequency' ); ?></strong></label>
				</td>
				<td colspan="4">
					<input type="text" id="repeats_every" name="repeats_every" class="small-input" value="<?php echo esc_attr( $repeats_every ); ?>"><span class="input-group-addon">weeks</span>
				</td>

			</tr>

			<tr>
				<td class="services_meta_box_td" colspan="2">
					<label for="discount"><strong><?php _e( 'Discount', 'frequency' ); ?></strong> <span style="color: #f00;">*</span></label>
				</td>
				<td colspan="4">
					<label class="radio">
						<input type="radio" name="discount_f" id="percentage_d" value="percentage_d" <?php echo ( esc_attr( $discount_f ) == 'percentage_d' ) ? 'checked' : '' ?>>Percentage
					</label>&nbsp;&nbsp;&nbsp;
					<label class="radio">
						<input type="radio" name="discount_f" id="amount_d" value="amount_d" <?php echo ( esc_attr( $discount_f ) == 'amount_d' ) ? 'checked' : '' ?>>Amount
					</label>
				</td>

			</tr>

			<tr>
				<td class="services_meta_box_td" colspan="2"></td>
				<td colspan="4">
					<div id="discount_container" style="display:<?php echo ( esc_attr( $discount_f ) == 'percentage_d' ) ? 'block' : 'none' ?>"><input type="text" id="frequency_discount" name="frequency_discount" class="regular-text" value="<?php echo esc_attr( $frequency_discount ); ?>"><span class="input-group-addon">%</span></div>
					<div id="amount_discount_container" style="display:<?php echo ( esc_attr( $discount_f ) == 'amount_d' ) ? 'block' : 'none' ?>"><span class="input-group-addon-l">$</span><input type="text" id="amount_discount" name="amount_discount" class="regular-text" value="<?php echo esc_attr( $amount_discount ); ?>"></div>
				</td>

			</tr>

			<tr>
				<td class="services_meta_box_td" colspan="2"></td>
				<td colspan="4">
					<fieldset>
						<label for="default_frequency">
							<input name="default_frequency" type="checkbox" id="default_frequency" value="1" <?php echo esc_attr( $default_frequency ); ?>> Make this the Default frequency selected on the booking form
		    			</label>
					</fieldset>
				</td>

			</tr>

		</table>

	<?php }

	function save_service_frequency_meta_box( $post_id ) {

		global $post;

		// Verify nonce
		if ( !isset( $_POST['service_frequency_fields'] ) || !wp_verify_nonce( $_POST['service_frequency_fields'], basename(__FILE__) ) ) {
			return $post_id;
		}

		// Check Autosave
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ( defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']) ) {
			return $post_id;
		}

		// Don't save if only a revision
		if ( isset( $post->post_type ) && $post->post_type == 'revision' ) {
			return $post_id;
		}

		// Check permissions
		if ( !current_user_can( 'edit_post', $post->ID ) ) {
			return $post_id;
		}

		$meta['frequency'] = ( isset( $_POST['frequency'] ) ? sanitize_text_field( $_POST['frequency'] ) : '' );

		$meta['repeats_every'] = ( isset( $_POST['repeats_every'] ) ? sanitize_text_field( $_POST['repeats_every'] ) : '' );
		
		$meta['frequency_discount'] = ( isset( $_POST['frequency_discount'] ) ? sanitize_text_field( $_POST['frequency_discount'] ) : '' );
		
		$meta['amount_discount'] = ( isset( $_POST['amount_discount'] ) ? sanitize_text_field( $_POST['amount_discount'] ) : '' );
		
		$meta['default_frequency'] = ( isset( $_POST['default_frequency'] ) ? 'checked' : '' );
		
		$meta['discount_f'] = ( isset( $_POST['discount_f'] ) ? sanitize_text_field( $_POST['discount_f'] ) : '' );
		
		$id = $post->ID;

		$query_args = array(
					'post_type'      => 'service_frequencies',
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'posts_per_page' => -1,
					'meta_query'     => array(
					    array(
							'key'   => 'default_frequency',
							'value' => 'checked',
					    ),
					)
				);
				
		$query = new WP_Query( $query_args );



		if ( $query->posts ) {
			foreach ( $query->posts as $key => $post_id ) {
				if ($post_id !== (int)$id) {
					update_post_meta( $post_id, 'default_frequency', '' );
					//var_dump($post_id);
				} else {
					$default_frequency = ! isset( $meta['default_frequency'][0] ) ? '' : sanitize_text_field( $meta['default_frequency'] )[0];
				}
				
				//var_dump((int)$id);
			}
		} else {
			$default_frequency = ! isset( $meta['default_frequency'][0] ) ? '' : sanitize_text_field( $meta['default_frequency'][0] );
		}

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post->ID, $key, $value );
		}
	}	
}