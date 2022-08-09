<?php

function convert_tax( $old_tax, $new_tax ) {
    $pages = get_posts( array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => $old_tax,
                'operator' => 'EXISTS'
            )
        )
    ) );

    foreach ( $pages as $post ) {
        $old_terms = get_the_terms( $post->ID, $old_tax );
        $post_new_terms = array();

        foreach ( $old_terms as $old_term ) {
            $new_term = get_term_by( 'name', $old_term->name, $new_tax, ARRAY_A );
            if ( $new_term === false ) {
                $new_term = wp_insert_term( $old_term->name, $new_tax, $args = array() );
            }

            $post_new_terms[] = (int) $new_term['term_id'];
        }

        wp_set_object_terms( $post->ID, $post_new_terms, $new_tax );
    }
}

convert_tax( 'pa_manufacturer', 'pa_brand' );
