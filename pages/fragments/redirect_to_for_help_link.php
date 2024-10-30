	<tr valign="top" id="enable_help_link_display">
		<th scope="row">
			<label class="control-label">Redirect to: </label>
		</th>
		<td>
			<label class="radio inline">
				<input type="radio" name="form_redirect_to_help" id="form_redirect_to_page_or_post_help" value="page_or_post_help" <?php echo ( $editForm->redirectToPageOrPostHelp == 1 ) ? esc_attr( 'checked' ) : ''; ?>>Page or Post
			</label>
			<label class="radio inline">
				<input type="radio" name="form_redirect_to_help" id="form_redirect_to_url_help" value="url" <?php echo ( $editForm->redirectToPageOrPostHelp == 0 ) ? esc_attr( 'checked' ) : '' ?>>
				URL entered manually
			</label>
			<div id="redirect_to_page_or_post_help_section" <?php echo( $editForm->redirectToPageOrPostHelp == 0 ? 'style="display: none;"' : '' ) ?>>
				<?php
				$pages = get_pages();
				?>
				<div class="ui-widget">
					<select name="form_redirect_page_or_post_id_help" id="form_redirect_page_or_post_id_helpx">
						<option value=""><?php echo esc_attr( __( 'Select from the list or start typing', 'bookin60' ) ); ?></option>
						<?php
						foreach ( $pages as $page ) {
							if ( $page->post_type == 'post' || $page->post_type == 'page' ) { 								
								?>
								<option value="<?php echo esc_attr( $page->ID ); ?>" <?php 
									if ( $page->ID == $editForm->redirectPostIDHelp ) {
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
			<div id="redirect_to_url_help_section" <?php echo( $editForm->redirectToPageOrPostHelp == 1 ? 'style="display: none;"' : '' ) ?>>
				<input type="text" class="regular-text" name="form_redirect_url_help" id="form_redirect_url_help" placeholder="Enter URL" value="<?php echo esc_attr( $editForm->redirectUrlHelp ); ?>">
			</div>
		</td>
	</tr>

	<script type="text/javascript">
		jQuery(document).ready(function () {
			jQuery('.page_or_post-combobox-input').prop('disabled', false);
			jQuery('.page_or_post-combobox-toggle').button("option", "disabled", false);
		});
	</script>