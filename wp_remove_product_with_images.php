add_action('wp_ajax_nopriv_cosmo_remove_product', function(){
	$product = get_posts( array(
		'post_type' => 'product',
		'numberposts' => 1,
		'post_status' => 'publish',
		'tax_query' => array(
			array(
				'taxonomy' => 'product_cat',
				'field' => 'term_id',
				'terms' => [41, 1629, 1652, 1634, 1631, 1640], /*category name*/
				'operator' => 'IN',
			)
		),
		'fields' => 'ids',
    ));
	
	if( ! $product ){
		echo 'finish';
	}
	wp_delete_post($product[0]);
	echo $product[0];
});

add_action( 'before_delete_post', 'delete_product_images', 10, 1 );

function delete_product_images( $post_id ){
	
	if( $_GET['action'] !== 'cosmo_remove_product' ){
		return;
	}
	
    $product = wc_get_product( $post_id );

    if ( !$product ) {
        return;
    }

    $featured_image_id = $product->get_image_id();
    $image_galleries_id = $product->get_gallery_image_ids();

    if( !empty( $featured_image_id ) ) {
        wp_delete_post( $featured_image_id );
    }

    if( !empty( $image_galleries_id ) ) {
        foreach( $image_galleries_id as $single_image_id ) {
            wp_delete_post( $single_image_id );
        }
    }
}
