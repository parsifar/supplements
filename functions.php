<?php
/**
 * Functions
 *
 * @package SuppPick
 */

require_once 'update-price-script.php';

add_action( 'wp_enqueue_scripts', 'astra_child_enqueue_styles' );
function astra_child_enqueue_styles() {
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/dist/style.css', array(), false, 'all' );

	wp_enqueue_script(
		'child-script',
		get_stylesheet_directory_uri() . '/src/js/script.js',
		array(),
		false,
		true
	);

	// Ajax search

	// Detect if we're on a supplement-category archive
	$current_category = '';

	if ( is_tax( 'supplement-category' ) ) {
		$current_term = get_queried_object();
		if ( $current_term && ! is_wp_error( $current_term ) ) {
			$current_category = $current_term->slug; // We'll pass the slug
		}
	}
	wp_enqueue_script( 'supplement-ajax-search', get_stylesheet_directory_uri() . '/src/js/supplement-ajax-search.js', array( 'jquery' ), null, true );

	wp_localize_script(
		'supplement-ajax-search',
		'supplement_ajax_search_params',
		array(
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'category_filter' => $current_category,
		)
	);
}


function filter_supplement_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ( ! is_post_type_archive( 'supplement' ) && ! is_tax( 'supplement-category' ) ) ) {
		return;
	}

	// Taxonomy filters.

	// Check if we're on a taxonomy archive for 'supplement-category'.
	if ( is_tax( 'supplement-category' ) ) {
		// Skip 'supplement-category' taxonomy filter
		$taxonomies = array( 'brand', 'certification', 'dietary-tag', 'product-form' );

	} else {
		// Include 'supplement-category' on main archive pages
		$taxonomies = array( 'brand', 'certification', 'dietary-tag', 'product-form', 'supplement-category' );
	}

	$tax_query = array();

	foreach ( $taxonomies as $taxonomy ) {
		if ( ! empty( $_GET[ 'selected_' . $taxonomy ] ) ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $_GET[ 'selected_' . $taxonomy ] ),
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

	if ( ! empty( $_GET['min_rating'] ) ) {
		$meta_query[] = array(
			'key'     => 'amazon_rating',
			'value'   => floatval( $_GET['min_rating'] ),
			'compare' => '>=',
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
		case 'rating_asc':
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'meta_key', 'amazon_rating' );
			$query->set( 'order', 'ASC' );
			break;
		case 'rating_desc':
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'meta_key', 'amazon_rating' );
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

/**
 * Ajax Search Handler
 */
add_action( 'wp_ajax_supplement_ajax_search', 'supplement_ajax_search' );
add_action( 'wp_ajax_nopriv_supplement_ajax_search', 'supplement_ajax_search' );

add_action( 'wp_ajax_supplement_ajax_search', 'supplement_ajax_search' );
add_action( 'wp_ajax_nopriv_supplement_ajax_search', 'supplement_ajax_search' );

function supplement_ajax_search() {
	$keyword         = sanitize_text_field( $_POST['keyword'] );
	$category_filter = sanitize_text_field( $_POST['category_filter'] );

	// Build tax_query array
	$tax_query = array(
		'relation' => 'AND',
	);

	// If filtering by supplement-category
	if ( ! empty( $category_filter ) ) {
		$tax_query[] = array(
			'taxonomy' => 'supplement-category',
			'field'    => 'slug',
			'terms'    => $category_filter,
		);
	}

	// Add brand matching to the tax_query
	$tax_query[] = array(
		'taxonomy' => 'brand',
		'field'    => 'name',  // search by brand name
		'terms'    => $keyword,
		'operator' => 'LIKE', // allow partial match
	);

	$args = array(
		'post_type'      => 'supplement',
		'posts_per_page' => 10,
		'tax_query'      => $tax_query,
		's'              => $keyword, // still search title normally
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
		echo '<ul>';
		while ( $query->have_posts() ) {
			$query->the_post();
			echo '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></li>';
		}
		echo '</ul>';
	} else {
		echo '<p>No supplements found.</p>';
	}

	wp_die();
}
