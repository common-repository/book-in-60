<tr valign="top">
	<th scope="row">
		<label class="control-label">Redirect to:</label>
	</th>
	<td>
		<label class="radio inline">
			<input type="radio" name="form_redirect_to" id="form_redirect_to_page_or_post" value="page_or_post" disabled>
			Page or Post
		</label>
		<label class="radio inline">
			<input type="radio" name="form_redirect_to" id="form_redirect_to_url" value="url" disabled> URL entered
			manually
		</label>
		<div id="redirect_to_page_or_post_section">
			<?php
			$pages = get_pages();
			?>
			<div class="ui-widget">
				<select name="form_redirect_page_or_post_id" id="form_redirect_page_or_post_idx" disabled>
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
		<div id="redirect_to_url_section" style="display: none;">
			<input type="text" class="regular-text" name="form_redirect_url" id="form_redirect_url" disabled placeholder="Enter URL">
		</div>
	</td>
</tr>
