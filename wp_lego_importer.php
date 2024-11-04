<?php

// @TODO: Registering new attribute taxonomy doesn't work

//$products = file_get_contents( '/url_or_path_to/file' );
$products = file_get_contents( __DIR__ . '/products/test.json' );
$products = json_decode( $products, true );


foreach ( $products as $i => $product ) {
	$main_product = $product['main'];


	$product_id = create_product( array(
		'type'              => 'variable',
		'name'              => $main_product['name'],
		'description'       => $main_product['description'],
		'short_description' => $main_product['shortDescription'],
		'sku'               => $main_product['id'],
		'category_ids'      => get_category_ids( [ 'Category Name' ] ), // set cat ids
		'image_id'          => get_attachment_id_by_url( $main_product['mainImage'] ),
		'gallery_ids'       => get_attachments_id_by_url( $main_product['galleryImages'] ),
		'regular_price'     => $main_product['regularPrice'],
		'sale_price'        => $main_product['salePrice'],
		'reviews'   => true,
		'attributes'        => array(
			'pa_color' => array(
				'term_names'    => array('value 1', 'value 2'),
				'is_visible'    => true,
				'for_variation' => true,
			),
			'pa_size'  => array(
				'term_names'    => $main_product['sizes'],
				'is_visible'    => true,
				'for_variation' => true,
			),
		),
	) );


	foreach ( $product['variables'] as $variable ) {
		$variation_data = array(
			'name'              => $variable['name'],
			'description'       => $variable['description'],
			'short_description' => $variable['description'],
			'image_id'          => get_attachment_id_by_url( $variable['mainImage'] ),
			'gallery_ids'       => get_attachments_id_by_url( $variable['galleryImages'] ),
			'regular_price'     => $variable['regularPrice'],
			'sale_price'        => $variable['salePrice'],
			'weight'            => $variable['weight'],
            		'height'            => $variable['dimensions']['height'],
            		'width'             => $variable['dimensions']['width'],
            		'length'            => $variable['dimensions']['length'],
			'attributes'        => array(
				'color' => $variable['color'],
				'size'  => $variable['size']
			),
		);
		$variation_ids = create_product_variation( $product_id, $variation_data );

		$product->set_children( $variation_ids ); 
	}

	foreach ( $product['reviews'] as $index => $review ) {
		$comment_id = wp_insert_comment( array(
			'comment_post_ID'      => $product_id, // <=== The product ID where the review will show up
			'comment_author'       => $review['authorName'],
			'comment_author_email' => $review['authorEmail'], // <== Important
			'comment_author_url'   => '',
			'comment_content'      => $review['bodyText'],
			'comment_type'         => 'review',
			'comment_parent'       => 0,
			'user_id'              => 1, // <== Important
			'comment_author_IP'    => '',
			'comment_agent'        => '',
			'comment_date'         => $review['datePublished'],
			'comment_approved'     => 1,
			'comment_meta'         => array(
				'rating'          => $review['rating'],
				// Additional meta
			),
		) );

	}

	echo 'ok';
}









// ==============================================================================================
// Всё что ниже это функции
// ==============================================================================================
function get_attachments_id_by_url( $urls ){
	$ids = [];
	foreach ( $urls as $url ){
		$ids[] = get_attachment_id_by_url( $url );
	}

	return $ids;
}
function get_attachment_id_by_url( $url ) {
	global $wpdb;
	$query = $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key` = '_image_origin_url' AND `meta_value` = %s;", $url );
	$uploaded = $wpdb->get_var( $query );

	if ( $uploaded ) {
		return (int) $uploaded;
	}

	else {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_sideload_image( $url, 0, null, 'id' );
		if( ! is_wp_error( $attachment_id ) ) {
			update_post_meta( $attachment_id, '_image_origin_url', $url );

			return $attachment_id;
		}
	}
}
function wc_get_product_object_type( $type ) {
	// Get an instance of the WC_Product object (depending on his type)
	if ( $type === 'variable' ) {
		$product = new WC_Product_Variable();
	}
	elseif ( $type === 'grouped' ) {
		$product = new WC_Product_Grouped();
	}
	elseif ( $type === 'external' ) {
		$product = new WC_Product_External();
	}
	else {
		$product = new WC_Product_Simple(); // "simple" By default
	}

	if ( ! is_a( $product, 'WC_Product' ) ) {
		return false;
	}
	else {
		return $product;
	}
}
function wc_prepare_product_attributes( $attributes ) {
	global $woocommerce;

	$data = array();
	$position = 0;


	foreach ( $attributes as $taxonomy => $values ) {
		
		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy( $taxonomy, 'product_variation', array(
				'hierarchical' => false,
				'label'        => ucfirst( explode( 'pa_', $taxonomy )[1] ),
				'query_var'    => true,
				'rewrite'      => array( 'slug' => sanitize_title( explode( 'pa_', $taxonomy )[1] ) ),
				// The base slug
			), );
		}


		// Get an instance of the WC_Product_Attribute Object
		$attribute = new WC_Product_Attribute();

		$term_ids = array();

		// Loop through the term names
		foreach ( $values['term_names'] as $term_name ) {

			if ( ! term_exists( $term_name, $taxonomy ) ) {
				wp_insert_term( $term_name, $taxonomy );
			} // Create the term

			if ( term_exists( $term_name, $taxonomy ) ) // Get and set the term ID in the array from the term name
			{
				$term_ids[] = get_term_by( 'name', $term_name, $taxonomy )->term_id;
			}
			else {
				// Check if the Term name exist and if not we create it.

			}
		}

		$taxonomy_id = wc_attribute_taxonomy_id_by_name( $taxonomy ); // Get taxonomy ID

		$attribute->set_id( $taxonomy_id );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( $term_ids );
		$attribute->set_position( $position );
		$attribute->set_visible( $values['is_visible'] );
		$attribute->set_variation( $values['for_variation'] );

		$data[$taxonomy] = $attribute; // Set in an array

		$position++; // Increase position
	}
	return $data;
}
function create_product( $args ) {

	if ( ! function_exists( 'wc_get_product_object_type' ) && ! function_exists( 'wc_prepare_product_attributes' ) ) {
		return false;
	}

	// Get an empty instance of the product object (defining it's type)
	$product = wc_get_product_object_type( $args['type'] );
	if ( ! $product ) {
		return false;
	}

	// Product name (Title) and slug
	$product->set_name( $args['name'] ); // Name (title).
	
	if ( isset( $args['slug'] ) ) {
		$product->set_slug( $args['slug'] );
	}

	// Description and short description:
	$product->set_description( $args['description'] );
	$product->set_short_description( $args['short_description'] );

	// Status ('publish', 'pending', 'draft' or 'trash')
	$product->set_status( isset( $args['status'] ) ? $args['status'] : 'publish' );

	// Visibility ('hidden', 'visible', 'search' or 'catalog')
	$product->set_catalog_visibility( isset( $args['visibility'] ) ? $args['visibility'] : 'visible' );

	// Featured (boolean)
	$product->set_featured( isset( $args['featured'] ) ? $args['featured'] : false );

	// Virtual (boolean)
	$product->set_virtual( isset( $args['virtual'] ) ? $args['virtual'] : false );

	// Prices
	$product->set_regular_price( $args['regular_price'] );
	$product->set_sale_price( isset( $args['sale_price'] ) ? $args['sale_price'] : '' );
	$product->set_price( isset( $args['sale_price'] ) ? $args['sale_price'] : $args['regular_price'] );
	if ( isset( $args['sale_price'] ) ) {
		$product->set_date_on_sale_from( isset( $args['sale_from'] ) ? $args['sale_from'] : '' );
		$product->set_date_on_sale_to( isset( $args['sale_to'] ) ? $args['sale_to'] : '' );
	}

	// Downloadable (boolean)
	$product->set_downloadable( isset( $args['downloadable'] ) ? $args['downloadable'] : false );
	if ( isset( $args['downloadable'] ) && $args['downloadable'] ) {
		$product->set_downloads( isset( $args['downloads'] ) ? $args['downloads'] : array() );
		$product->set_download_limit( isset( $args['download_limit'] ) ? $args['download_limit'] : '-1' );
		$product->set_download_expiry( isset( $args['download_expiry'] ) ? $args['download_expiry'] : '-1' );
	}

	// Taxes
	if ( get_option( 'woocommerce_calc_taxes' ) === 'yes' ) {
		$product->set_tax_status( isset( $args['tax_status'] ) ? $args['tax_status'] : 'taxable' );
		$product->set_tax_class( isset( $args['tax_class'] ) ? $args['tax_class'] : '' );
	}

	// SKU and Stock (Not a virtual product)
	if ( isset( $args['virtual'] ) && ! $args['virtual'] ) {
		$product->set_manage_stock( isset( $args['manage_stock'] ) ? $args['manage_stock'] : false );
		$product->set_stock_status( isset( $args['stock_status'] ) ? $args['stock_status'] : 'instock' );
		if ( isset( $args['manage_stock'] ) && $args['manage_stock'] ) {
			$product->set_stock_status( $args['stock_qty'] );
			$product->set_backorders( isset( $args['backorders'] ) ? $args['backorders'] : 'no' ); // 'yes', 'no' or 'notify'
		}
	}
	$product->set_sku( isset( $args['sku'] ) ? $args['sku'] : '' );

	// Sold Individually
	$product->set_sold_individually( isset( $args['sold_individually'] ) ? $args['sold_individually'] : false );

	// Weight, dimensions and shipping class
	$product->set_weight( isset( $args['weight'] ) ? $args['weight'] : '' );
	$product->set_length( isset( $args['length'] ) ? $args['length'] : '' );
	$product->set_width( isset( $args['width'] ) ? $args['width'] : '' );
	$product->set_height( isset( $args['height'] ) ? $args['height'] : '' );
	if ( isset( $args['shipping_class_id'] ) ) {
		$product->set_shipping_class_id( $args['shipping_class_id'] );
	}

	// Upsell and Cross sell (IDs)
	$product->set_upsell_ids( isset( $args['upsells'] ) ? $args['upsells'] : '' );
	$product->set_cross_sell_ids( isset( $args['cross_sells'] ) ? $args['upsells'] : '' );

	// Attributes et default attributes
	if ( isset( $args['attributes'] ) ) {
		$product->set_attributes( wc_prepare_product_attributes( $args['attributes'] ) );
	}
	if ( isset( $args['default_attributes'] ) ) {
		$product->set_default_attributes( $args['default_attributes'] );
	} // Needs a special formatting

	// Reviews, purchase note and menu order
	$product->set_reviews_allowed( isset( $args['reviews'] ) ? $args['reviews'] : false );
	$product->set_purchase_note( isset( $args['note'] ) ? $args['note'] : '' );
	if ( isset( $args['menu_order'] ) ) {
		$product->set_menu_order( $args['menu_order'] );
	}

	// Product categories and Tags
	if ( isset( $args['category_ids'] ) ) {
		$product->set_category_ids( $args['category_ids'] );
	}
	if ( isset( $args['tag_ids'] ) ) {
		$product->set_tag_ids( $args['tag_ids'] );
	}


	// Images and Gallery
	$product->set_image_id( isset( $args['image_id'] ) ? $args['image_id'] : "" );
	$product->set_gallery_image_ids( isset( $args['gallery_ids'] ) ? $args['gallery_ids'] : array() );
	## --- SAVE PRODUCT --- ##
	$product_id = $product->save();

	return $product_id;
}
function create_product_variation( $product_id, $variation_data ) {
	// Get the Variable product object (parent)
	$product = wc_get_product( $product_id );

	$variation_post = array(
		'post_title'  => $product->get_name(),
		'post_name'   => 'product-' . $product_id . '-variation',
		'post_status' => 'publish',
		'post_parent' => $product_id,
		'post_type'   => 'product_variation',
		'guid'        => $product->get_permalink()
	);

	// Creating the product variation
	$variation_id = wp_insert_post( $variation_post );

	// Get an instance of the WC_Product_Variation object
	$variation = new WC_Product_Variation( $variation_id );

	// Iterating through the variations attributes
	foreach ( $variation_data['attributes'] as $attribute => $term_name ) {
		$taxonomy = 'pa_' . $attribute; // The attribute taxonomy

		// If taxonomy doesn't exists we create it (Thanks to Carl F. Corneil)
		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy( $taxonomy, 'product_variation', array(
				'hierarchical' => false,
				'label'        => ucfirst( $attribute ),
				'query_var'    => true,
				'rewrite'      => array( 'slug' => sanitize_title( $attribute ) ),
				// The base slug
			), );
		}

		// Check if the Term name exist and if not we create it.
		if ( ! term_exists( $term_name, $taxonomy ) ) {
			wp_insert_term( $term_name, $taxonomy );
		} // Create the term

		$term_slug = get_term_by( 'name', $term_name, $taxonomy )->slug; // Get the term slug

		// Get the post Terms names from the parent variable product.
		$post_term_names = wp_get_post_terms( $product_id, $taxonomy, array( 'fields' => 'names' ) );

		// Check if the post term exist and if not we set it in the parent variable product.
		if ( ! in_array( $term_name, $post_term_names ) ) {
			wp_set_post_terms( $product_id, $term_name, $taxonomy, true );
		}

		// Set/save the attribute data in the product variation
		update_post_meta( $variation_id, 'attribute_' . $taxonomy, $term_slug );
		//update_post_meta( $variation_id, 'attribute_pa_' . strtolower( rawurlencode( $attribute ) ), $term_slug );

	}

	## Set/save all other data

	// SKU
	if ( ! empty( $variation_data['sku'] ) ) {
		$variation->set_sku( $variation_data['sku'] );
	}

	// Prices
	if ( empty( $variation_data['sale_price'] ) ) {
		$variation->set_price( $variation_data['regular_price'] );
	}
	else {
		$variation->set_price( $variation_data['sale_price'] );
		$variation->set_sale_price( $variation_data['sale_price'] );
	}
	$variation->set_regular_price( $variation_data['regular_price'] );

	// Stock
	if ( ! empty( $variation_data['stock_qty'] ) ) {
		$variation->set_stock_quantity( $variation_data['stock_qty'] );
		$variation->set_manage_stock( true );
		$variation->set_stock_status( '' );
	}
	else {
		$variation->set_manage_stock( false );
	}

	if ( ! empty( $variation_data['description'] ) ) {
		$variation->set_description( $variation_data['description'] );
		$variation->set_short_description( $variation_data['description'] );
	}


	// Images
	if ( ! empty( $variation_data['image_id'] ) ) {
		$variation->set_image_id( $variation_data['image_id'] );
	}
	if ( $variation_data['gallery_ids'] ) {
		update_post_meta( $variation_id, '_product_image_gallery', implode(',', $variation_data['gallery_ids']) );
		update_post_meta( $variation_id, 'rtwpvg_images', $variation_data['gallery_ids'] );
		//$variation->set_gallery_image_ids( $variation_data['gallery_ids'] );
	}

	//$variation->set_weight( '' ); // weight (reseting)

	$variation->save(); // Save the data
}

function get_category_ids( $cat_names ){
    $cat_ids = [];
    foreach ( $cat_names as $cat_name ) {
        $new_term = get_term_by( 'name', $cat_name, 'product_cat', ARRAY_A );
        if ( $new_term === false ) {
            $new_term = wp_insert_term( $cat_name, 'product_cat', $args = array() );
        }

        $cat_ids[] = (int) $new_term['term_id'];
    }

    return $cat_ids;
}

function create_term_hierarchy( $term_string ) {
	$terms  = explode( '|', $term_string );
	$parent = 0; // Initialize parent as 0 (top level)
	
	foreach ( $terms as $term_name ) {
		// Check if the term exists
		$existing_term = term_exists( $term_name, 'product_cat', $parent );
		
		if ( $existing_term ) {
			// Term exists, set it as the parent for the next iteration
			$parent = $existing_term[ 'term_id' ];
		}
		else {
			// Term doesn't exist, create it and set it as the parent
			$args     = array(
				'parent' => $parent,
			);
			$new_term = wp_insert_term( $term_name, 'product_cat', $args );
			$parent   = $new_term[ 'term_id' ];
		}
	}
	
	// Return the last created term ID
	return $parent;
}
