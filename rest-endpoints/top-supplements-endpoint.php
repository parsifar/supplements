<?php

// Register the REST Route
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'supppick/v1',
			'/top-supplements',
			array(
				'methods'             => 'GET',
				'callback'            => 'get_top_supplements_data',
				'permission_callback' => '__return_true', // Public access
			)
		);
	}
);

// Callback Function
function get_top_supplements_data( $request ) {
	$params = $request->get_params();

	$defaults = array(
		'count'                 => 3,
		'category'              => '',
		'form'                  => '',
		'tag'                   => '',
		'include_ingredient'    => '',
		'exclude_ingredient'    => '',
		'sort_by'               => 'price',
		'secondary_sort_by'     => '',
		'order'                 => 'ASC',
		'secondary_order'       => 'ASC',
		'ingredient_for_dosage' => '',
		'min_rating'            => '',
	);
	$atts     = wp_parse_args( $params, $defaults );

	$args = array(
		'post_type'      => 'supplement',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'tax_query'      => array(),
		'meta_query'     => array(),
	);

	// Taxonomy filters
	foreach ( array(
		'category' => 'supplement-category',
		'form'     => 'product-form',
		'tag'      => 'dietary-tag',
	) as $key => $taxonomy ) {
		if ( ! empty( $atts[ $key ] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $atts[ $key ],
			);
		}
	}

	// Meta filters
	if ( ! empty( $atts['include_ingredient'] ) ) {
		$args['meta_query'][] = array(
			'key'     => 'ingredients',
			'value'   => '"' . intval( $atts['include_ingredient'] ) . '"',
			'compare' => 'LIKE',
		);
	}
	if ( ! empty( $atts['exclude_ingredient'] ) ) {
		$args['meta_query'][] = array(
			'key'     => 'ingredients',
			'value'   => '"' . intval( $atts['exclude_ingredient'] ) . '"',
			'compare' => 'NOT LIKE',
		);
	}
	if ( $atts['min_rating'] !== '' ) {
		$args['meta_query'][] = array(
			'key'     => 'amazon_rating',
			'value'   => floatval( $atts['min_rating'] ),
			'type'    => 'DECIMAL',
			'compare' => '>=',
		);
	}

	$query   = new WP_Query( $args );
	$results = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $post ) {
			$primary_value   = 0;
			$secondary_value = 0;

			if ( in_array( $atts['sort_by'], array( 'price', 'price_per_serving', 'amazon_rating', 'protein_per_serving', 'calorie_protein_ratio', 'protein_per_dollar', 'total_caffeine_content' ) ) ) {
				$primary_value = floatval( get_field( $atts['sort_by'], $post->ID ) );
			} elseif ( $atts['sort_by'] === 'dosage' && ! empty( $atts['ingredient_for_dosage'] ) ) {
				$dosages = get_field( 'dosages', $post->ID );
				if ( is_array( $dosages ) ) {
					foreach ( $dosages as $row ) {
						if ( isset( $row['ingredient'] ) && is_object( $row['ingredient'] ) && intval( $row['ingredient']->ID ) === intval( $atts['ingredient_for_dosage'] ) ) {
							$primary_value += floatval( $row['amount'] );
						}
					}
				}
			}

			if ( ! empty( $atts['secondary_sort_by'] ) ) {
				if ( in_array( $atts['secondary_sort_by'], array( 'price', 'price_per_serving', 'amazon_rating', 'protein_per_serving', 'calorie_protein_ratio', 'protein_per_dollar', 'total_caffeine_content' ) ) ) {
					$secondary_value = floatval( get_field( $atts['secondary_sort_by'], $post->ID ) );
				} elseif ( $atts['secondary_sort_by'] === 'dosage' && ! empty( $atts['ingredient_for_dosage'] ) ) {
					$dosages = get_field( 'dosages', $post->ID );
					if ( is_array( $dosages ) ) {
						foreach ( $dosages as $row ) {
							if ( isset( $row['ingredient'] ) ) {
								$ingredient_id = is_object( $row['ingredient'] ) ? $row['ingredient']->ID : $row['ingredient'];
								if ( intval( $ingredient_id ) === intval( $atts['ingredient_for_dosage'] ) ) {
									$secondary_value += floatval( $row['amount'] );
								}
							}
						}
					}
				}
			}

			$results[] = array(
				'id'                     => $post->ID,
				'title'                  => get_the_title( $post ),
				'link'                   => get_permalink( $post ),
				'thumbnail'              => get_the_post_thumbnail_url( $post->ID, 'medium' ),
				'primary'                => $primary_value,
				'secondary'              => $secondary_value,
				'rating'                 => get_field( 'amazon_rating', $post->ID ),
				'servings_per_container' => get_field( 'servings_per_container', $post->ID ),
				'price'                  => get_field( 'price', $post->ID ),
				'price_per_serving'      => get_field( 'price_per_serving', $post->ID ),
				'affiliate_url'          => get_field( 'affiliate_url', $post->ID ),
			);
		}

		// Sort results
		usort(
			$results,
			function ( $a, $b ) use ( $atts ) {
				$order           = strtoupper( $atts['order'] ) === 'DESC' ? -1 : 1;
				$secondary_order = strtoupper( $atts['secondary_order'] ) === 'DESC' ? -1 : 1;

				if ( $a['primary'] == $b['primary'] ) {
					return ( $a['secondary'] <=> $b['secondary'] ) * $secondary_order;
				}
				return ( $a['primary'] <=> $b['primary'] ) * $order;
			}
		);
	}

	return array_slice( $results, 0, intval( $atts['count'] ) );
}
