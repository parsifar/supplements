<?php
/**
 * Functions
 *
 * @package SuppPick
 */

add_action( 'wp_enqueue_scripts', 'astra_child_enqueue_styles' );
function astra_child_enqueue_styles() {
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/dist/style.css', array(), false, 'all' );
}


function filter_supplement_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! is_post_type_archive( 'supplement' ) ) {
		return;
	}

	// Taxonomy filters.
	$taxonomies = array( 'brand', 'certification', 'dietary-tag', 'product-form', 'supplement-category' );
	$tax_query  = array();

	foreach ( $taxonomies as $taxonomy ) {
		if ( ! empty( $_GET[ $taxonomy ] ) ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $_GET[ $taxonomy ] ),
			);
		}
	}

	if ( ! empty( $tax_query ) ) {
		$query->set(
			'tax_query',
			array(
				'relation' => 'AND',
				...$tax_query,
			)
		);
	}

	// Meta filters.
	$meta_query = array();

	if ( ! empty( $_GET['max_price'] ) ) {
		$meta_query[] = array(
			'key'     => 'price',
			'value'   => floatval( $_GET['max_price'] ),
			'compare' => '<=',
			'type'    => 'DECIMAL',
		);
	}

	if ( ! empty( $_GET['max_pps'] ) ) {
		$meta_query[] = array(
			'key'     => 'price_per_serving',
			'value'   => floatval( $_GET['max_pps'] ),
			'compare' => '<=',
			'type'    => 'DECIMAL',
		);
	}

	if ( ! empty( $meta_query ) ) {
		$query->set( 'meta_query', $meta_query );
	}

	// Sorting.
	$sort = sanitize_text_field( $_GET['sort'] ?? '' );
	switch ( $sort ) {
		case 'price_asc':
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'meta_key', 'price' );
			$query->set( 'order', 'ASC' );
			break;
		case 'price_desc':
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'meta_key', 'price' );
			$query->set( 'order', 'DESC' );
			break;
		case 'pps_asc':
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'meta_key', 'price_per_serving' );
			$query->set( 'order', 'ASC' );
			break;
		case 'pps_desc':
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'meta_key', 'price_per_serving' );
			$query->set( 'order', 'DESC' );
			break;
		case 'name_asc':
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'ASC' );
			break;
		case 'name_desc':
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'DESC' );
			break;
	}
}
add_action( 'pre_get_posts', 'filter_supplement_query' );



/**
 * Resolve the price query precision issue
 */
function cast_decimal_precision( $array ) {

	$array['where'] = str_replace( 'DECIMAL', 'DECIMAL(10,2)', $array['where'] );

	return $array;
}
add_filter( 'get_meta_sql', 'cast_decimal_precision' );
