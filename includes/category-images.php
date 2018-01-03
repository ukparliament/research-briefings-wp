<?php
function research_briefings_add_category_image_field($category) {
	$category_id = $category->term_id;
	$category_image = get_term_meta($category_id, 'rb_image', true);

	if( intval( $category_image ) > 0 ) {
		// Change with the image size you want to use
		$image = wp_get_attachment_image( $category_image, 'medium', false, array( 'id' => 'research_briefings-preview-image' ) );
	} else {
	    // Some default image
		$image = '<img id="research_briefings-preview-image" src="https://placehold.it/200" />';
	} ?>
	 <tr class="form-field">
			<th scope="row">
				<label for="categoryImage">Research Briefing images</label>
				<td>
				<?php echo $image; ?>
				<input type="hidden" name="research_briefings_image_id" id="research_briefings_image_id" value="<?php echo esc_attr( $category_image ); ?>" class="regular-text" />
				<input type='button' class="button-primary" value="<?php esc_attr_e( 'Select a image', 'mytextdomain' ); ?>" id="research_briefings_media_manager"/>
			</td>
		</th>
	</tr>
	<?php
}
add_action('category_edit_form_fields', 'research_briefings_add_category_image_field', 10);

// Ajax action to refresh the user image
add_action( 'wp_ajax_research_briefings_get_image', 'research_briefings_get_image'   );
function research_briefings_get_image() {
    if(isset($_GET['id']) ){
        $image = wp_get_attachment_image( filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT ), 'medium', false, array( 'id' => 'research_briefings-preview-image' ) );
        $data = array(
            'image'    => $image,
        );
        wp_send_json_success( $data );
    } else {
        wp_send_json_error();
    }
}

/**
 * Get custom CSS for admin
 */
function research_briefings_load_category_image_styles($hook) {
    if($hook != 'term.php') {
        return;
    }
    wp_enqueue_script( 'custom_wp_admin_css', plugins_url('../public/js/category-images.js', __FILE__) );
    wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'research_briefings_load_category_image_styles' );

// /**
//  * Saving Image
//  */
function category_save_image( $term_id ) {

	if ( isset( $_POST['research_briefings_image_id'] ) ) {
		$term_image = $_POST['research_briefings_image_id'];
		if( $term_image ) {
			update_term_meta( $term_id, 'rb_image', $term_image );
		}
	}

}
add_action( 'edited_category', 'category_save_image' );
