	<tr valign="top">
		<th scope="row">
			<label class="control-label">Redirect to: </label>
		</th>
		<td>
			<label class="radio inline">
				<input type="radio" name="form_redirect_lead_to" id="form_redirect_lead_to_page" value="lead_to_page" <?php echo ( $editForm->redirectLeadToPage == 1 ) ? esc_attr( 'checked' ) : '' ?>>
				Single Page
			</label>
			<label class="radio inline">
				<input type="radio" name="form_redirect_lead_to" id="form_redirect_lead_to_steps" value="lead_to_steps"  <?php echo ( $editForm->redirectLeadToPage == 0 ) ? esc_attr( 'checked' ) : '' ?>>
				Multi-steps Page
			</label>
			<div id="redirect_lead_to_page_section" <?php echo( $editForm->redirectLeadToPage == 0 ? esc_attr( 'style="display: none;"' ) : '' ) ?>>
				<?php
				$pages = get_pages();
				?>
				<div class="ui-widget">
					<select name="form_redirect_lead_to_page_id" id="form_redirect_lead_to_page_idx" style="margin-top:15px">
						<option value=""><?php echo esc_attr( __( 'Select from the list or start typing', 'bookin60' ) ); ?></option>
						<?php
						foreach ( $pages as $page ) {
							if ( $page->post_type == 'post' || $page->post_type == 'page' ) { 								
								?>
								<option value="<?php echo esc_attr( $page->ID ); ?>" <?php 
									if ( $page->ID == $editForm->redirectLeadID ) {
										echo esc_attr(' selected');
									}
								 ?>>
								<?php echo esc_html( $page->post_title ); ?>
								</option>
						<?php
							}
						}
						?>
					</select>
				</div>
			</div>
		</td>
	</tr>
	<!-- <tr valign="top">
		<th scope="row">
			<label class="control-label">Single Page Container: </label>
		</th>
		<td>
			<input type="text" class="regular-text" name="form_name" id="form_name" value="<?php echo $editForm->name; ?>">
			<p class="description">Class container for the form in single form page</p>
		</td>
	</tr> -->
<?php //if ( $editForm->redirectOnSuccess == '1' && $editForm->redirectLeadToPage == 1 ): ?>
	<script type="text/javascript">
		jQuery(document).ready(function () {
			jQuery('.lead_to_page-combobox-input').prop('disabled', false);
			jQuery('.lead_to_page-combobox-toggle').button("option", "disabled", false);
		});
	</script>
<?php //endif; ?>