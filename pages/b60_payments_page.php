<?php
global $wpdb;
//get the data we need
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'payments';

?>



<div class="wrap">
	<h2> <?php echo __( 'Bookings', 'bookin60' ); ?> </h2>
	<div id="updateDiv"><p><strong id="updateMessage"></strong></p></div>

	<h2 class="nav-tab-wrapper">
		<a href="?page=b60-bookings&tab=payments" class="nav-tab <?php echo $active_tab == 'payments' ? 'nav-tab-active' : ''; ?>">Bookings List</a>
		<a href="?page=b60-bookings&tab=forms" class="nav-tab <?php echo $active_tab == 'forms' ? 'nav-tab-active' : ''; ?>">Booking
			Form</a>
		<a href="?page=b60-bookings&tab=calendar" class="nav-tab <?php echo $active_tab == 'calendar' ? 'nav-tab-active' : ''; ?>">Calendar</a>
	</h2>

	<div class="">
		<?php if ( $active_tab == 'payments' ): ?>
			<div class="" id="payments">

				<?php if ( isset($_GET['bookingID'] ) ) { 
						$booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "b60_payments WHERE paymentID=%d", sanitize_key( $_GET['bookingID'] ) ) );
						$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "b60_lead_entries WHERE leadID=%d", sanitize_key( $booking->customerID ) ) );						
				?>			
					<div class="pt-md" id="forms">
					<div class="booking-details-form">
						<div class="invoice-container">
								<tr>
									<td>
										<div class="invoice-box">
											<table cellpadding="0" cellspacing="0">
												<tr class="top">
													<td colspan="2">
														<table>
															<tr>
																<td class="title">
																	<h1><?php echo esc_html( $customer->first_name .' '.$customer->last_name ); ?></h1>
																</td>
																<td class="title">
																	<h1>Booking / <?php echo esc_html( $booking->schedule_date ); ?> / <?php echo esc_html( $booking->schedule_time ); ?></h1>
																</td>
															</tr>
														</table>
													</td>
												</tr>
												<tr class="information">
													<td colspan="2">
														<table>
															<tr>
																<td>																	
																	<?php echo esc_html( $booking->apartment ); ?><br />
																	<?php echo esc_html( $booking->address ); ?><br />
																	<?php echo esc_html( $booking->city ); ?>
																	<?php echo esc_html( $booking->state ); ?>
																	<?php echo esc_html( $booking->zip ); ?><br />
																	<?php echo esc_html( $customer->email ); ?><br />
																	<?php echo esc_html( $customer->phone ); ?>
																</td>
																<td>
																	Date: <?php echo esc_html( $booking->schedule_date ); ?><br />
																	Time: <?php echo esc_html( $booking->schedule_time ); ?><br />
																	Frequency of Service: <?php echo esc_html( $booking->frequency ); ?><br />
																</td>
															</tr>
														</table>
													</td>
												</tr>

												<tr class="heading">
													<td>Payment Method</td>
													<td>Transaction ID</td>
												</tr>

												<tr class="details">
													<td>Stripe</td>
													<td>
														<?php
															$stripeLink = "https://manage.stripe.com/";
									                            if ( $booking->livemode == 0 ) {
									                                $stripeLink .= 'test/';
									                            }
									                            $stripeLink .= "charges/" . $booking->eventID;													
							                            ?>
							                            <a href="<?php echo esc_url($stripeLink); ?>" target="_blank"><?php echo esc_html( $booking->eventID ); ?></a>
												</tr>

												<tr class="heading">
													<td>Item</td>

													<td>Price</td>
												</tr>

													<?php

															$bookingDetails = json_decode(stripslashes($booking->booking_details));
															$service_total = 0;
															$service_ = 0;
															$extras = 0;
															$total_summary = 0;
															$total_list = 0;
															$frequency_discount = 0;
															$sales_tax = 0;

															foreach ($bookingDetails[0] as $obj) {
																 echo '<tr class="">';
															   echo '<td style="text-transform:capitalize">'.esc_html( $obj->title ).'</td>';
															   echo '<td>'.esc_html( $obj->price ).'</td>';
															   echo '</tr>';

															   $service_ += $obj->price;															   
															}

															$service_total = sprintf( '%0.2f', $service_ / 1 );

															if($bookingDetails[1] != '') {
																 foreach ($bookingDetails[1] as $obj) {
																	 echo '<tr class="">';
																   echo '<td style="text-transform:capitalize">'.esc_html( $obj->title ).' (extra)</td>';
																   echo '<td>'.esc_html( $obj->price ).'</td>';
																   echo '</tr>';
																}

																
																if($bookingDetails[1] != '') {
																		foreach ($bookingDetails[1] as $obj) {
																			$extras += $obj->price;
																		}
																		$extras_display = '$'. sprintf( '%0.2f', $extras / 1 );
																} else {
																		$extras_display = '$0.00';
																}
															}

															$total_list = $service_ + $extras;

															if($bookingDetails[2]->frequency_discount != '') {
																	$frequency_discount = $bookingDetails[2]->frequency_discount;
															} 

															if($bookingDetails[2]->sales_tax != '') {
																	$sales_tax = $bookingDetails[2]->sales_tax;
															}
													?>		

												<tr class="total">
													<td></td>

													<td>Sub Total: $<?php echo esc_html( $bookingDetails[2]->subtotal ); ?></td>
												</tr>
											</table>
										</div>
									</td>
								</tr>								
						</div>
						<div class="button-container">
							<div class="tile-block tile-cyan">
							          <div class="tile-header">
							            <h3>
							              Summary
							            </h3>
							          </div>
							          <div class="tile-content">
							            <table class="table table-condensed">
							              <tbody>
							                <tr>
							                  <td>
							                    <label>
							                      Service
							                    </label>
							                  </td>
							                  <td class="text-right">
							                    <div class="price_figures booking-summary-service ng-binding" id="service_price_figure">
							                      $<?php echo esc_html( $service_total ); ?>
							                    </div>
							                  </td>
							                </tr>
							                <tr>
							                  <td>
							                    <label>
							                      Extras
							                    </label>
							                  </td>
							                  <td class="text-right">
							                    <div class="price_figures booking-summary-extras ng-binding" id="extras_price_figure">
							                      <?php
							                      		echo esc_html( $extras_display );
							                      ?>
							                    </div>
							                  </td>
							                </tr>
							                  <?php 
							                  		if($bookingDetails[2]->sales_tax_symbol == 'before') {
							                  ?>
							                  <tr>
							                    <td>
							                      <label>
							                        Sales Tax
							                      </label>
							                    </td>
							                    <td class="text-right">
							                      <div class="price_figures booking-summary-tip ng-binding" id="tip_figure">
							                        <?php echo esc_html( '$'.$bookingDetails[2]->sales_tax ); ?>
							                      </div>
							                    </td>
							                  </tr>
							                <?php } ?>
							                <tr>
							                  <td>
							                    <label>
							                      Frequency Discount
							                    </label>
							                  </td>
							                  <td class="text-right">
							                    <div class="price_figures booking-summary-adjustment ng-binding" id="price_adjustment_figure">
							                    	<?php echo esc_html( '($'.sprintf( '%0.2f', $frequency_discount / 1 ) . ')' ); ?>
							                    </div>
							                  </td>
							                </tr>
							                <?php 
							                		if($bookingDetails[2]->sales_tax_symbol == 'after') {
							                ?>
							                <tr>
							                  <td>
							                    <label>
							                      Sales Tax
							                    </label>
							                  </td>
							                  <td class="text-right">
							                    <div class="price_figures booking-summary-tip ng-binding" id="tip_figure">
							                      <?php echo esc_html( '$'.$bookingDetails[2]->sales_tax ); ?>
							                    </div>
							                  </td>
							                </tr>
							              <?php } ?>
							              </tbody>
							            </table>
							          </div>
							          <div class="tile-footer total-tile-footer">
							            <table class="table">
							              <tbody>
							                <tr class="ng-scope">
							                  <td>
							                    <h4 style="text-transform: uppercase">
							                      <strong>
							                        Total
							                      </strong>
							                    </h4>
							                  </td>
							                  <td class="tile-header text-right">
							                    <h3 class="price_figures booking-summary-total ng-binding">
							                      $<?php echo esc_html( $bookingDetails[2]->total_sales ); ?>
							                    </h3>
							                  </td>
							                </tr>
							              </tbody>
							            </table>
							          </div>
							        </div><br>
							<a href="?page=b60-bookings" class="view-booking-btns-back button button-primary" >Back</a>
						</div>
					</div>
				</div>
				<?php } else { ?>

					<form id="payments-filter" method="post">
					    <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>" />
					    <?php $table->display(); ?>
					</form>

				<?php } ?>
				
			</div>
		<?php elseif ( $active_tab == 'forms' ): 	?>

			<?php 

			$services = get_posts( 
			    [
			        'post_type' => 'service', 
			        'posts_per_page' => -1
			    ] 
			);

			//if ($services) {
			    include B60_CORE . '/pages/b60_booking_formbuilder.php';
			//} else { ?>
				<!-- <p class="alert alert-info">You have created no service yet. Use the Create New Form tab
					to get started</p> -->
			<?php //} ?>		

		
		<?php elseif ( $active_tab == 'calendar' ): ?>
			<div class="pt-md" id="forms">
				<form class="form-horizontal" action="" method="POST" id="time-slot-form">
					<p class="tips"></p>
					<input type="hidden" name="action" value="wp_ajax_save_time_slot">
					<table class="calendar" id="time-slots">
						<thead>
							<tr>
								<td colspan="5"><h1>Time Slots</h1></td>
							</tr>
						</thead>
						<tbody class="time-slots-body">
							<tr>
								<th>Start Time</th>
								<th>End Time</th>
								<th>Availability</th>		
								<th>Quantity</th>		
								<th></th>							
							</tr>
						</tbody>
						<?php

							$args = array(  
							        'post_type' => 'service_slot',
							        'post_status' => 'publish',
							        'posts_per_page' => -1, 
							        // 'orderby' => 'title', 
							        'order' => 'ASC', 
							    );

							    $loop = new WP_Query( $args ); 

							    if ( $loop->have_posts() ) :
							        
							    while ( $loop->have_posts() ) : $loop->the_post(); 
						?>
							        <?php //echo get_post_meta(get_the_ID(), 'start_time', true); ?>
							    <tr>
							    	<td>
							    		<select name="start_time_<?php echo esc_attr( get_the_ID() ) ?>" id="start_time_<?php echo esc_attr( get_the_ID() ) ?>">
							    		<?php
							    			foreach (get_appointment_time() as $time => $text) {
							    				if(get_post_meta(get_the_ID(), 'start_time', true) ==  $time) {
							    					$selected = 'selected';
							    				} else { $selected = ''; }
							    				echo '<option value="' . esc_attr( $time ) .'" ' .esc_attr( $selected ). '>' . esc_html( $text ) . '</option>';
							    			}
							    		?>
							    		</select>
							    	</td>
							    	<td>
							    		<select name="end_time_<?php echo esc_attr( get_the_ID() ) ?>" id="end_time_<?php echo esc_attr( get_the_ID() ) ?>">
							    			<?php
							    				foreach (get_appointment_time($out = false, $_24 = true) as $time => $text) {
							    					if(get_post_meta(get_the_ID(), 'end_time', true) ==  $time) {
							    						$selected = 'selected';
							    					} else { $selected = ''; }
							    					echo '<option value="' . esc_attr( $time ) .'" ' .esc_attr( $selected ). '>' . esc_html( $text ) . '</option>';
							    				}
							    			?>
							    		</select>
							    	</td>
							    	<td>
							    		<?php
							    			$week_day = get_post_meta(get_the_ID(), 'week_day', true);
							    			foreach (array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') as $key => $week) {
							    				$week_short = substr(ucfirst($week), 0, 1);

							    				$id = get_the_ID();
							    				$checked = '';

							    				if($week_day != NULL) {
							    					if (in_array($key, $week_day)) {
							    					    $checked = 'checked';
							    					} else { $checked = ''; }
							    				}

							    				echo '<label class="week_day">
							    							<input type="checkbox" name="week_day_'.esc_attr( $id ).'" value="'.esc_attr( $key ).'" '.esc_attr( $checked ).'>
							    							<span>'.esc_html( $week_short ).'</span>
							    						</label>';
							    			}
							    		?>
							    	</td>
							    	<td>
							    		<select name="capacity_<?php echo esc_attr( get_the_ID() ) ?>" id="capacity_<?php echo esc_attr( get_the_ID() ) ?>">
							    		    <?php
							    		            foreach (services_capacity_options() as $num => $text) {

							    		            	if(get_post_meta(get_the_ID(), 'capacity', true) ==  $num) {
							    		            		$selected = 'selected';
							    		            	} else { $selected = ''; }

							    		                echo '<option value="' . esc_attr( $num ) .'" ' .esc_attr( $selected ). '>' . esc_html( $text ) . '</option>';
							    		            }
							    		            ?>
							    		</select>
							    	</td>
							    	<td>
							    		<div class="slot-delete" id="<?php echo esc_attr( get_the_ID() ); ?>">Delete</div><div style="display: inline-flex;margin-left: 5px;">
					<button class="save-slot button button-primary" id="<?php echo esc_attr( get_the_ID() ); ?>" type="submit">Save</button>
					<!-- <img src="<?php echo plugins_url( '/assets/img/loader.gif', dirname( __FILE__ ) ); ?>" alt="Loading..." class="showLoading"/> -->
				</div>
							    	</td>
							    </tr>
						<?php	    endwhile;
						 wp_reset_postdata(); 

						else :
						 	//_e( 'Sorry, no posts were found.', 'textdomain' );
						endif;
							   
						?>
						
						<tfoot>
							<tr>
								<td colspan="4">
									<div class="add-slot button button-ga" style="margin-right:5px">Add new</div>
								</td>
							</tr>
						</tfoot>
					</table>
				</form>
				<br>
				<form class="form-horizontal" action="" method="POST" id="holiday-form">
					<p class="tips"></p>
					<input type="hidden" name="action" value="wp_ajax_save_holiday">
					<table class="calendar" id="holiday-slots">
						<thead>
							<tr>
								<td colspan="5"><h1>Holidays</h1></td>
							</tr>
						</thead>
						<tbody class="holiday-body">
							<tr>
								<th style="width: 18.5%;">Name</th>
								<th style="width: 41.5%;">Description</th>	
								<th style="width: 20%;">Date</th>		
								<th></th>							
							</tr>
						</tbody>
						<?php

							$args = array(  
							        'post_type' => 'calendar_holiday',
							        'post_status' => 'publish',
							        'posts_per_page' => -1, 
							        // 'orderby' => 'title', 
							        'order' => 'ASC', 
							    );

							    $loop = new WP_Query( $args ); 

							    if ( $loop->have_posts() ) :
							        
							    while ( $loop->have_posts() ) : $loop->the_post(); 

							    	$holiday_name = get_post_meta(get_the_ID(), 'holiday_name', true);
							    	$holiday_description = get_post_meta(get_the_ID(), 'holiday_description', true);
							    	$holiday_date = get_post_meta(get_the_ID(), 'holiday_date', true);
						?>
							        <?php //echo get_post_meta(get_the_ID(), 'start_time', true); ?>
							    <tr>
							    	<td>
							    		<input name="holiday_name_<?php echo esc_attr( get_the_ID() ) ?>" id="holiday_name_<?php echo esc_attr( get_the_ID() ) ?>" type="text" style="width:100%" value="<?php echo esc_attr( $holiday_name ); ?>">
							    	</td>
							    	<td>
							    		<input name="holiday_description_<?php echo esc_attr( get_the_ID() ) ?>" id="holiday_description_<?php echo esc_attr( get_the_ID() ) ?>" type="text" style="width:100%" value="<?php echo esc_attr( $holiday_description ); ?>">
							    	</td>
							    	<td style="padding:0 30px">
							    		<div class="provider_holidays">
							    			<div class="holiday">
							    				<input type="text" class="holiday_date" name="holiday_date_<?php echo esc_attr( get_the_ID() ) ?>" id="holiday_date_<?php echo esc_attr( get_the_ID() ) ?>" value="<?php echo esc_attr( $holiday_date ); ?>" placeholder="Select date">
							    			</div>
							    		</div>
							    	</td>
							    	<td>
							    		<div class="slot-delete" id="<?php echo esc_attr( get_the_ID() ); ?>">Delete</div><div style="display: inline-flex;margin-left: 5px;">
					<button class="save-slot button button-primary" id="<?php echo esc_attr( get_the_ID() ); ?>" type="submit">Save</button>
				</div>
							    	</td>
							    </tr>
						<?php	    endwhile;
						 wp_reset_postdata(); 

						else :
						 	//_e( 'Sorry, no posts were found.', 'textdomain' );
						endif;
							   
						?>
						
						<tfoot>
							<tr>
								<td colspan="4">
									<div class="add-slot button button-ga" style="margin-right:5px">Add a holiday</div>
								</td>
							</tr>
						</tfoot>
					</table>
				</form>
			</div>			
		<?php endif; ?>
	</div>
</div>

