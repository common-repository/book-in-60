<?php
global $wpdb;
//get the data we need
$formID = 1;
$formType = "payment";
// if ( isset( $_GET['form'] ) ) {
// 	$formID = $_GET['form'];
// }
// if ( isset( $_GET['type'] ) ) {
// 	$formType = $_GET['type'];
// }

$valid = true;
if ( $formID == - 1 || $formType == "" ) {
	$valid = false;
}

$editForm = null;
// $plans    = array();

 if ( $valid ) {

	if ( $formType == "payment" ) {
		$editForm = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "b60_payment_forms WHERE paymentFormID=%d", sanitize_key( $formID ) ) );
	} else {
		$valid = false;
	}

	if ( $editForm == null ) {
		$valid = false;
	}
}

$settings_option = get_option( 'b60_settings_option' );

?>
<div class="wrap">
	<div id="updateDiv"><p><strong id="updateMessage"></strong></p></div>
	<?php if ( ! $valid ): ?>
		<p>Form not found!</p>
	<?php else: ?>
		<?php if ( $formType == "payment" ): ?>
			<form class="form-horizontal" action="" method="POST" id="edit-payment-form">
				<p class="tips"></p>
				<input type="hidden" name="action" value="wp_full_stripe_edit_payment_form">
				<input type="hidden" name="formID" value="<?php echo esc_attr( $editForm->paymentFormID ); ?>">
				<h2>Lead Form Settings</h2>
				<hr>
				<table class="form-table">	
					<?php include( 'fragments/redirect_to_for_lead.php' ); ?>
				</table>
			    <hr>
			    <h2>Booking Form Settings</h2>
			    <hr>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label class="control-label">Redirect On Success?</label>
						</th>
						<td>
							<label class="radio inline">
								<input type="radio" name="form_do_redirect" id="do_redirect_no" value="0" <?php echo ( $editForm->redirectOnSuccess == '0' ) ? esc_attr( 'checked' ) : '' ?> >
								No
							</label> <label class="radio inline">
								<input type="radio" name="form_do_redirect" id="do_redirect_yes" value="1" <?php echo ( $editForm->redirectOnSuccess == '1' ) ? esc_attr( 'checked' ) : '' ?> >
								Yes
							</label>
							<p class="description">When payment is successful you can choose to redirect to another page
								or post</p>
						</td>
					</tr>
					<?php include( 'fragments/redirect_to_for_edit.php' ); ?>
					<tr valign="top">
						<th scope="row"><label for="form-email-to">Help Link</label></th>
						<td>
							<fieldset><legend class="screen-reader-text"><span>Activate</span></legend>
								<label for="enable_help_link">
							<input name="enable_help_link" type="checkbox" id="enable_help_link" value="1" <?php checked(1, esc_attr( $settings_option['enable_help_link'] ), true); ?>>
								Enable</label>
							</fieldset>
						</td>
					</tr>
					<?php include( 'fragments/redirect_to_for_help_link.php' ); ?>
				</table>				
				<p class="submit">
					<button class="button button-primary" type="submit">Save Changes</button>
					<img src="<?php echo plugins_url( '/assets/img/loader.gif', dirname( __FILE__ ) ); ?>" alt="Loading..." class="showLoading"/>
				</p>
			</form>
		<?php endif; ?>
	<?php endif; ?>
</div>