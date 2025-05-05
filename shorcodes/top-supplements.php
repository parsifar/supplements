<?php
/**
 * Top supplements shorcode
 *
 * [top_supplements count="3" sort_by="price" order="ASC"]
 * [top_supplements count="3" category="pre-workout" form="powder" sort_by="price_per_serving" order="ASC"]
 * [top_supplements count="3" include_ingredient="123" sort_by="dosage" ingredient_for_dosage="123" order="DESC"]
 *
 * @package SuppPick
 */
function top_supplements_shortcode( $atts ) {
	ob_start();

	$atts = shortcode_atts(
		array(
			'count'                 => 3,
			'category'              => '',
			'form'                  => '',
			'tag'                   => '',
			'include_ingredient'    => '',
			'exclude_ingredient'    => '',
			'sort_by'               => 'price', // primary
			'secondary_sort_by'     => '', // optional
			'order'                 => 'ASC',
			'secondary_order'       => 'ASC',
			'ingredient_for_dosage' => '',
			'min_rating'            => '',
		),
		$atts,
		'top_supplements'
	);

	$args = array(
		'post_type'      => 'supplement',
		'posts_per_page' => -1, // handle sorting manually when needed
		'post_status'    => 'publish',
		'tax_query'      => array(),
		'meta_query'     => array(),
	);

	// Taxonomy filters
	if ( ! empty( $atts['category'] ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'supplement-category',
			'field'    => 'slug',
			'terms'    => $atts['category'],
		);
	}

	if ( ! empty( $atts['form'] ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'product-form',
			'field'    => 'slug',
			'terms'    => $atts['form'],
		);
	}

	if ( ! empty( $atts['tag'] ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'dietary-tag',
			'field'    => 'slug',
			'terms'    => $atts['tag'],
		);
	}

	// Ingredient filters
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

	// Minimum rating filter
	if ( $atts['min_rating'] !== '' ) {
		$args['meta_query'][] = array(
			'key'     => 'amazon_rating',
			'value'   => floatval( $atts['min_rating'] ),
			'type'    => 'DECIMAL',
			'compare' => '>=',
		);
	}

	$sort_by           = $atts['sort_by'];
	$secondary_sort_by = $atts['secondary_sort_by'];
	$order             = strtoupper( $atts['order'] ) === 'DESC' ? 'DESC' : 'ASC';
	$secondary_order   = strtoupper( $atts['secondary_order'] ) === 'DESC' ? 'DESC' : 'ASC';

	$results = array();

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $post ) {
			$primary_value   = 0;
			$secondary_value = 0;

			if ( in_array( $sort_by, array( 'price', 'price_per_serving', 'amazon_rating' ) ) ) {
				$primary_value = floatval( get_field( $sort_by, $post->ID ) );
			} elseif ( $sort_by === 'dosage' && ! empty( $atts['ingredient_for_dosage'] ) ) {
				$dosages = get_field( 'dosages', $post->ID );
				foreach ( $dosages as $row ) {
					if ( isset( $row['ingredient'] ) && is_object( $row['ingredient'] ) ) {
						if ( intval( $row['ingredient']->ID ) === intval( $atts['ingredient_for_dosage'] ) ) {
							$primary_value += floatval( $row['amount'] );
						}
					}
				}
			}

			if ( $secondary_sort_by ) {
				if ( in_array( $secondary_sort_by, array( 'price', 'price_per_serving', 'amazon_rating' ) ) ) {
					$secondary_value = floatval( get_field( $secondary_sort_by, $post->ID ) );
				} elseif ( $secondary_sort_by === 'dosage' && ! empty( $atts['ingredient_for_dosage'] ) ) {
					$dosages = get_field( 'dosages', $post->ID );
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

			$results[] = array(
				'post'      => $post,
				'primary'   => $primary_value,
				'secondary' => $secondary_value,
			);
		}

		// Sort by primary and secondary
		usort(
			$results,
			function ( $a, $b ) use ( $order, $secondary_order ) {
				$primary_compare = $order === 'DESC' ? $b['primary'] <=> $a['primary'] : $a['primary'] <=> $b['primary'];

				if ( $primary_compare !== 0 ) {
					return $primary_compare;
				}

				return $secondary_order === 'DESC'
				? $b['secondary'] <=> $a['secondary']
				: $a['secondary'] <=> $b['secondary'];
			}
		);

		// foreach ( $results as $result ) {
		// pp( $result['post']->post_title );
		// pp( 'primary: ' . $result['primary'] );
		// pp( 'secondary: ' . $result['secondary'] );

		// }

		$results = array_slice( $results, 0, intval( $atts['count'] ) );

		?>
			<section class="top-supplements-shortcode">
				<div class="supplement-grid">
					<?php
					$index = 1;
					foreach ( $results as $item ) {
						global $post;
						$post = $item['post'];
						setup_postdata( $post );
						get_template_part( '/template-parts/top-supplement', 'card', array( 'supplement_index' => $index ) );

						++$index;
					}
					?>
				</div>
			</section>
			<?php
			wp_reset_postdata();

	}

	return ob_get_clean();
}
add_shortcode( 'top_supplements', 'top_supplements_shortcode' );
