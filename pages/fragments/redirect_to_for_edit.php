	<tr valign="top">
		<th scope="row">
			<label class="control-label">Redirect to: </label>
		</th>
		<td>
			<label class="radio inline">
				<input type="radio" name="form_redirect_to" id="form_redirect_to_page_or_post" value="page_or_post" <?php echo ( $editForm->redirectOnSuccess == '0' ) ? esc_attr( 'disabled' ) : '' ?> <?php echo ( $editForm->redirectToPageOrPost == 1 ) ? esc_attr( 'checked' ) : '' ?>>
				Page or Post
			</label>
			<label class="radio inline">
				<input type="radio" name="form_redirect_to" id="form_redirect_to_url" value="url" <?php echo ( $editForm->redirectOnSuccess == '0' ) ? esc_attr( 'disabled' ) : '' ?> <?php echo ( $editForm->redirectToPageOrPost == 0 ) ? esc_attr( 'checked' ) : '' ?>>
				URL entered manually
			</label>
			<div id="redirect_to_page_or_post_section" <?php echo( $editForm->redirectToPageOrPost == 0 ? 'style="display: none;"' : '' ) ?>>
				<?php
				$pages = get_pages();
				?>
				<div class="ui-widget">
					<select name="form_redirect_page_or_post_id" id="form_redirect_page_or_post_idx" <?php echo ( $editForm->redirectOnSuccess == '0' ) ? esc_attr( 'disabled' ) : '' ?>>
						<option value=""><?php echo esc_attr( __( 'Select from the list or start typing', 'bookin60' ) ); ?></option>
						<?php
						foreach ( $pages as $page ) {
							if ( $page->post_type == 'post' || $page->post_type == 'page' ) { 								
								?>
								<option value="<?php echo esc_attr( $page->ID ); ?>" <?php 
									if ( $page->ID == $editForm->redirectPostID ) {
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
			<div id="redirect_to_url_section" <?php echo( $editForm->redirectToPageOrPost == 1 ? 'style="display: none;"' : '' ) ?>>
				<input type="text" class="regular-text" name="form_redirect_url" id="form_redirect_url" <?php echo ( $editForm->redirectOnSuccess == '0' ) ? esc_attr( 'disabled' ) : '' ?> placeholder="Enter URL" value="<?php echo esc_attr( $editForm->redirectUrl ); ?>">
			</div>
		</td>
	</tr>
<?php if ( $editForm->redirectOnSuccess == '1' && $editForm->redirectToPageOrPost == 1 ): ?>
	<script type="text/javascript">
		jQuery(document).ready(function () {
			jQuery('.page_or_post-combobox-input').prop('disabled', false);
			jQuery('.page_or_post-combobox-toggle').button("option", "disabled", false);
		});
	</script>
<?php endif; ?>