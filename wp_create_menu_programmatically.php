<?php
function add_product_cats_to_menu() {
	// Get the 'primary' menu
	$menu = wp_get_nav_menu_object( 'Main menu' );
	
	// Get product categories
	$product_cats = get_terms( [
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
		'parent'     => 0 // get top level categories
	] );
	
	if ( ! empty( $product_cats ) ) {
		foreach ( $product_cats as $product_cat ) {
			// Add each category as a menu item
			$top = wp_update_nav_menu_item( $menu->term_id, 0, array(
				'menu-item-title'     => $product_cat->name,
				'menu-item-object'    => 'product_cat',
				'menu-item-object-id' => $product_cat->term_id,
				'menu-item-type'      => 'taxonomy',
				'menu-item-status'    => 'publish'
			) );
			
			// Get child categories and add them as sub-menu items
			$child_cats = get_terms( [
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'parent'     => $product_cat->term_id // get child categories
			] );
			
			if ( ! empty( $child_cats ) ) {
				foreach ( $child_cats as $child_cat ) {
					// Add each child category as a sub-menu item
					$l1 = wp_update_nav_menu_item( $menu->term_id, 0, array(
						'menu-item-title'     => $child_cat->name,
						'menu-item-object'    => 'product_cat',
						'menu-item-object-id' => $child_cat->term_id,
						'menu-item-type'      => 'taxonomy',
						'menu-item-parent-id' => $top,
						'menu-item-status'    => 'publish'
					) );
					
					$child_cats_2 = get_terms( [
						'taxonomy'   => 'product_cat',
						'hide_empty' => false,
						'orderby'    => 'name',
						'order'      => 'ASC',
						'parent'     => $child_cat->term_id // get child categories
					] );
					
					if ( ! empty( $child_cats_2 ) ) {
						foreach ( $child_cats_2 as $child_cat_2 ) {
							// Add each child category as a sub-menu item
							wp_update_nav_menu_item( $menu->term_id, 0, array(
								'menu-item-title'     => $child_cat_2->name,
								'menu-item-object'    => 'product_cat',
								'menu-item-object-id' => $child_cat_2->term_id,
								'menu-item-type'      => 'taxonomy',
								'menu-item-parent-id' => $l1,
								'menu-item-status'    => 'publish'
							) );
						}
                    }
				}
			}
		}
	}
}

add_action( 'init', 'add_product_cats_to_menu' );
