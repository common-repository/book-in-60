<?php

$settings_option = get_option( 'b60_settings_option' );
$email_notification_option = get_option( 'b60_email_notifications_option' );

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general-settings';

?>
<div class="wrap">
	<h2> <?php echo __( 'Settings', 'bookin60' ); ?> </h2>
	<div id="updateDiv"><p><strong id="updateMessage"></strong></p></div>

	<h2 class="nav-tab-wrapper">
		<a href="?page=b60-settings&tab=general-settings" class="nav-tab <?php echo $active_tab == 'general-settings' ? 'nav-tab-active' : ''; ?>">General Settings</a>
		<a href="?page=b60-settings&tab=form-settings" class="nav-tab <?php echo $active_tab == 'form-settings' ? 'nav-tab-active' : ''; ?>">Form Settings</a>
		<a href="?page=b60-settings&tab=email-notifications" class="nav-tab <?php echo $active_tab == 'email-notifications' ? 'nav-tab-active' : ''; ?>">Email Notifications</a>
	</h2>

	<div class="tab-content">
		<?php if ( $active_tab == 'general-settings' ): ?>
			<br>
		    <h2>General Settings</h2>
		            <form action="" method="post" id="settings-form">
		            	<input type="hidden" name="action" value="update_general_settings"/>		            	
		                <hr>
		                <table class="form-table">
		    				<tbody>
		    					<tr valign="top">
		    						<th scope="row"><label for="form-name">Pick your industry</label></th>
		    						<td>
		    							<select name="industry" id="industry">
		    								<option value="">None</option>
		    								<option value="residential_cleaning" <?php selected( esc_attr( $settings_option['industry']  ), "residential_cleaning"); ?>>Residential Cleaning</option>
		    								<option value="lawn_care" <?php selected( esc_attr( $settings_option['industry'] ), "lawn_care"); ?> disabled>Lawn Care</option>
		    								<option value="home_moving" <?php selected( esc_attr( $settings_option['industry'] ), "home_moving"); ?> disabled>Home Moving</option>
		    								<option value="restaurants" <?php selected( esc_attr( $settings_option['industry'] ), "restaurants"); ?> disabled>Restaurants</option>
		    								<option value="salon" <?php selected( esc_attr( $settings_option['industry'] ), "salon"); ?> disabled>Salon</option>
		    								<option value="barber_shop" <?php selected( esc_attr( $settings_option['industry'] ), "barber_shop"); ?> disabled>Barber Shop</option>
		    								<option value="gym" <?php selected( esc_attr( $settings_option['industry'] ), "gym"); ?> disabled>Gym</option>
		    								<option value="personal_trainer" <?php selected( esc_attr( $settings_option['industry'] ), "personal_trainer"); ?> disabled>Personal Trainer</option>
		    							</select>
		    						</td>
		    					</tr>
		    					<tr valign="top">
		    						<th scope="row"><label for="form-email-subject">Business Name</label></th>
		    						<td>
		    							<input type="text" placeholder="" class="regular-text" id="business_name" name="business_name" value="<?php echo esc_attr( $settings_option['business_name'] ); ?>">
		    						</td>
		    					</tr>
		    					<tr valign="top">
		    						<th scope="row"><label for="form-email-to">Email from</label></th>
		    						<td>
		    							<input type="text" placeholder="" class="regular-text" id="email_from" name="email_from" value="<?php echo esc_attr( $settings_option['email_from'] ); ?>">
		    							<p class="description">The email address the recipient can see.</p>
		    						</td>
		    					</tr>
		    					<tr valign="top">
		    						<th scope="row"><label for="form-email-to">Payment Method</label></th>
		    						<td>
		    							<fieldset><legend class="screen-reader-text"><span>Payment Method</span></legend>
		    								<label for="enable_payment_method">
		    							<input name="enable_payment_method" type="checkbox" id="enable_payment_method" value="1" <?php checked(1, esc_attr( $settings_option['enable_payment_method'] ), true); ?>>
		    								Enable Payment Method</label>
		    							</fieldset>
		    						</td>
		    					</tr>
		    					<tr valign="top" id="stripe-section">
		    						<th scope="row"><label for="form-name">Preferred Payment Gateway</label></th>
		    						<td>
		    							<select name="payment_method" id="payment_method">
		    								<option value="none">None</option>
		    								<option value="payment_method_paypal" disabled="">Paypal Checkout</option>
		    								<option value="payment_method_stripe" <?php selected( esc_attr( $settings_option['payment_method'] ), "payment_method_stripe"); ?>>Stripe</option>
		    							</select>
		    						</td>
		    					</tr>		    					
		    				</tbody>
		    			</table>
		                <table class="form-table" id="stripe-settings">		
		                    <tr valign="top">
		                    	<th scope="row">
		                    		<label class="control-label"><?php _e( "Stripe mode: ", 'bookin60' ); ?> </label>
		                    	</th>
		                    	<td>
		                    		<label class="radio">
		                    			<input type="radio" name="apiMode" id="modeTest" value="test" <?php echo ( $settings_option['apiMode'] == 'test' ) ? esc_attr( 'checked' ) : '' ?> >
		                    			Test
		                    		</label> <label class="radio">
		                    			<input type="radio" name="apiMode" id="modeLive" value="live" <?php echo ( $settings_option['apiMode'] == 'live' ) ? esc_attr( 'checked' ) : '' ?>>
		                    			Live
		                    		</label>
		                    	</td>
		                    </tr>                	
		                	<tr valign="top">
		                		<th scope="row">
		                			<label class="control-label" for="publishKey_test"><?php _e( "Stripe Test Publishable Key: ", 'bookin60' ); ?></label>
		                		</th>
		                		<td>
		                			<input type="text" id="publishKey_test" name="publishKey_test" value="<?php echo esc_attr( $settings_option['publishKey_test'] ); ?>" class="regular-text code">
		                		</td>
		                	</tr>
		                	<tr valign="top">
		                		<th scope="row">
		                			<label class="control-label" for="secretKey_test"><?php _e( "Stripe Test Secret Key: ", 'bookin60' ); ?> </label>
		                		</th>
		                		<td>
		                			<input type="text" name="secretKey_test" id="secretKey_test" value="<?php echo esc_attr( $settings_option['secretKey_test'] ); ?>" class="regular-text code">
		                		</td>
		                	</tr>		                	
		                	<tr valign="top">
		                		<th scope="row">
		                			<label class="control-label" for="publishKey_live"><?php _e( "Stripe Live Publishable Key: ", 'bookin60' ); ?></label>
		                		</th>
		                		<td>
		                			<input type="text" id="publishKey_live" name="publishKey_live" value="<?php echo esc_attr( $settings_option['publishKey_live'] ); ?>" class="regular-text code">
		                		</td>
		                	</tr>
		                	<tr valign="top">
		                		<th scope="row">
		                			<label class="control-label" for="secretKey_live"><?php _e( "Stripe Live Secret Key: ", 'bookin60' ); ?> </label>
		                		</th>
		                		<td>
		                			<input type="text" name="secretKey_live" id="secretKey_live" value="<?php echo esc_attr( $settings_option['secretKey_live'] ); ?>" class="regular-text code">
		                		</td>
		                	</tr>		 
		                	<tr valign="top">
		                		<th scope="row">
		                			<label class="control-label" for="currency"><?php _e( "Payment Currency: ", 'bookin60' ); ?></label>
		                		</th>
		                		<td>
		                			<div class="ui-widget">
		                				<select id="currencyx" name="currency">
		                					<option value=""><?php echo esc_attr( __( 'Select from the list or start typing', 'bookin60' ) ); ?></option>
		                					<?php
		                					?>
		                					<?php foreach ( B60::get_available_currencies() as $currency_key => $currency_obj ) { 
		                						
		                						?>
		                						<option value="<?php echo esc_attr( $currency_key ) ?>" <?php 
			                						if ( $settings_option['currency'] === $currency_key ) {
			                							echo esc_attr( 'selected="selected"' );
			                						}		                						 
		                						?>>
		                						<?php echo esc_html( $currency_obj['name'] . ' (' .  $currency_obj['code'] . ')' ); ?>		                							
		                						</option>
		                					<?php } ?> 
		                					
		                				</select>
		                			</div>
		                		</td>
		                	</tr>
		                	<tr valign="top">
		                		<th scope="row"><label for="form-email-to">Sales Tax:</label></th>
		                		<td>
		                			<fieldset><legend class="screen-reader-text"><span>Sales Tax</span></legend>
		                				<label for="enable_sales_tax">
		                			<input name="enable_sales_tax" type="checkbox" id="enable_sales_tax" value="1" <?php checked(1, esc_attr( $settings_option['enable_sales_tax'] ), true); ?>>
		                				Enable Sales Tax</label>
		                			</fieldset>
		                		</td>
		                	</tr>
		                	<tr valign="top" id="tax_display">
		                		<th scope="row">
		                			<label class="control-label"><?php _e( "Tax Rate: ", 'bookin60' ); ?> </label>
		                		</th>
		                		<td>
		                			<div id="sales_tax" style="display:flex;justify-content:left;align-items:center;width:100px"><input type="number" step="0.01" id="sales_tax" name="sales_tax" class="regular-text" value="<?php echo esc_attr( $settings_option['sales_tax'] ); ?>"><span class="input-group-addon" style="line-height: 1.4;">%</span></div>
		                		</td>
		                	</tr>
		                	<tr valign="top" id="tax_display_">
		                		<th scope="row"><label for="form-name">Tax Applied:</label></th>
		                		<td>
		                			<select name="sales_tax_applied" id="sales_tax_applied">
		                				<option value="sales_tax_applied_before" <?php selected( esc_attr( $settings_option['sales_tax_applied'] ), "sales_tax_applied_before"); ?>>Before Discount</option>
		                				<option value="sales_tax_applied_after" <?php selected( esc_attr( $settings_option['sales_tax_applied'] ), "sales_tax_applied_after"); ?>>After Discount</option>
		                			</select>
		                		</td>
		                	</tr>		            
		                </table>
		                <hr>
		    			<p class="submit">
							<button type="submit" class="button button-primary"><?php esc_attr_e( 'Save Changes', 'bookin60' ) ?></button>
							<img src="<?php echo esc_url( B60_CORE_IMG .'loader.gif' ); ?>" alt="Loading..." class="showLoading" style="display: none;">
						</p>
		            </form>   		    
		<?php elseif ( $active_tab == 'form-settings' ): ?>
			<br>			
		    <?php include(B60_CORE . '/pages/b60_edit_form_page.php'); ?>

		<?php elseif ( $active_tab == 'email-notifications' ): ?>
			<br>			
		    <form class="form-horizontal" action="" method="post" id="email-notifications-form">
		    				<p class="tips"></p>
		    				<input type="hidden" name="action" value="update_email_notifications"/>				
		    				<table class="form-table">
		    					<tbody><tr valign="top">
		    								<th scope="row">Lead Form Notification</th>
		    								<td>
		    									<input type="checkbox" id="enable_lead_notification" name="enable_lead_notification" value="1" <?php checked(1, esc_attr( $email_notification_option['enable_lead_notification'] ), true); ?>>Send email notification after lead form submission to customer  
		    								</td>
		    							</tr>
		    							<tr valign="top">
		    								<th scope="row"></th>
		    								<td style="padding-top: 0!important;">
		    									<input type="checkbox" id="enable_lead_notification_admin" name="enable_lead_notification_admin" value="1" <?php checked(1, esc_attr( $email_notification_option['enable_lead_notification_admin'] ), true); ?>>Send email notification copy to admin		    									
		    								</td>
		    							</tr>
		    							<tr valign="top">
		    								<th scope="row">Tags</th>
		    								<td>
		    									 %customer_first_name%, %customer_last_name%, %customer_phone%, %customer_email%		    									
		    								</td>
		    							</tr>
		    							<tr valign="top">
		    											<th scope="row">Email Template:</th>
		    											<td>
		    												<?php 
		    												    wp_editor( esc_attr( $email_notification_option['lead_notification'] ), 'lead_notification');
		    												    ?>	
		    											</td>
		    										</tr>
		    						    <tr valign="top">
		    						    	<td></td>
		    						    </tr>
		    							<tr valign="top">
		    								<th scope="row">Booking Form Notification</th>
		    								<td>
		    									<input type="checkbox" id="enable_booking_notification" name="enable_booking_notification" value="1" <?php checked(1, esc_attr( $email_notification_option['enable_booking_notification'] ), true); ?>>Send email notification after booking form submission to customer
		    								</td>
		    							</tr>
		    							<tr valign="top">
		    								<th scope="row"></th>
		    								<td style="padding-top: 0!important;">
		    									<input type="checkbox" id="enable_booking_notification_admin" name="enable_booking_notification_admin" value="1" <?php checked(1, esc_attr( $email_notification_option['enable_booking_notification_admin'] ), true); ?>>Send email notification copy to admin	    									
		    								</td>
		    							</tr>
		    							<tr valign="top">
		    								<th scope="row">Tags</th>
		    								<td>
		    									 %customer_first_name%, %customer_last_name%, %customer_phone%, %customer_email%, %customer_address%, %customer_apartment%, %customer_city%, %customer_state%, %customer_zip%, %frequency%, %booking_service_date%, %booking_service_time%, %subtotal%, %discount%, %sales_tax%, %total_sales%  		    									
		    								</td>
		    							</tr>
		    							<tr valign="top">
		    											<th scope="row">Email Template:</th>
		    											<td>
		    												<?php 
		    												    wp_editor( esc_attr( $email_notification_option['booking_notification'] ), 'booking_notification');
		    												    ?>	
		    											</td>
		    										</tr>
		    						    <tr valign="top">
		    						    	<td></td>
		    						    </tr>		    						    
		    						</tbody></table>
		    				<p class="submit">
		    					<button class="button button-primary" type="submit">Save Changes</button>
		    					<img src="<?php echo esc_url( B60_CORE_IMG . 'loader.gif' ); ?>" alt="Loading..." class="showLoading"/>
		    				</p>
		    			</form>
		<?php endif; ?>
	</div>
</div>

