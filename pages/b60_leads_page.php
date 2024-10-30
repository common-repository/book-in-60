<?php

global $wpdb;
//get the data we need
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'payments';

?>

<div class="wrap">
	<h2> <?php echo __( 'Leads', 'bookin60' ); ?> </h2>
	<div id="updateDiv"><p><strong id="updateMessage"></strong></p></div>

	<h2 class="nav-tab-wrapper">
		<a href="?page=b60-leads&tab=payments" class="nav-tab <?php echo $active_tab == 'payments' ? 'nav-tab-active' : ''; ?>">Leads List</a>
		<a href="?page=b60-leads&tab=forms" class="nav-tab <?php echo $active_tab == 'forms' ? 'nav-tab-active' : ''; ?>">Lead
			Form</a>		
	</h2>

	<div class="">
		<?php if ( $active_tab == 'payments' ): ?>
			<div class="" id="payments">
				<form id="leads-filter" method="post">
				    <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
				    <?php $table->display(); ?>
				</form>
			</div>
		<?php elseif ( $active_tab == 'forms' ): include B60_CORE . '/pages/b60_lead_formbuilder.php';	?>		
		<?php endif; ?>
	</div>
</div>

