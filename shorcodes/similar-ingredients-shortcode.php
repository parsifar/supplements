<?php
/**
 * File: similar-ingredients-shortcode.php
 *
 * Shortcode to display supplements with similar ingredients.
 *
 * Usage: [similar_supplements id="123"]
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function similar_supplements_shortcode( $atts ) {
	// Parse attributes.
	$atts = shortcode_atts(
		array(
			'id' => '', // ID of the reference supplement.
		),
		$atts
	);

	// If no ID provided, return empty.
	if ( empty( $atts['id'] ) ) {
		return '';
	}

	// Get the reference supplement's category.
	$reference_categories = get_the_terms( $atts['id'], 'supplement-category' );
	if ( ! $reference_categories || is_wp_error( $reference_categories ) ) {
		return '';
	}

	// Get the reference supplement's ingredients.
	$reference_ingredients = get_field( 'ingredients', $atts['id'] );
	if ( ! $reference_ingredients ) {
		return '';
	}

	// Convert reference ingredients to array of IDs.
	$reference_ingredient_ids = array_map(
		function ( $ingredient ) {
			return $ingredient->ID;
		},
		$reference_ingredients
	);

	// Get all supplements in the same category.
	$args = array(
		'post_type'      => 'supplement',
		'posts_per_page' => -1,
		'tax_query'      => array(
			array(
				'taxonomy' => 'supplement-category',
				'field'    => 'term_id',
				'terms'    => array_map(
					function ( $cat ) {
						return $cat->term_id;
					},
					$reference_categories
				),
			),
		),
		'post__not_in'   => array( $atts['id'] ), // Exclude the reference supplement.
	);

	$supplements         = get_posts( $args );
	$similar_supplements = array();

	// Calculate similarity score for each supplement.
	foreach ( $supplements as $supplement ) {
		$ingredients = get_field( 'ingredients', $supplement->ID );
		if ( ! $ingredients ) {
			continue;
		}

		// Convert ingredients to array of IDs.
		$ingredient_ids = array_map(
			function ( $ingredient ) {
				return $ingredient->ID;
			},
			$ingredients
		);

		// Calculate Jaccard similarity coefficient.
		$intersection = count( array_intersect( $reference_ingredient_ids, $ingredient_ids ) );
		$union        = count( array_unique( array_merge( $reference_ingredient_ids, $ingredient_ids ) ) );
		$similarity   = $union > 0 ? $intersection / $union : 0;

		$similar_supplements[] = array(
			'post'       => $supplement,
			'similarity' => $similarity,
		);
	}

	// Sort by similarity score (descending).
	usort(
		$similar_supplements,
		function ( $a, $b ) {
			return $b['similarity'] <=> $a['similarity'];
		}
	);

	// Take top 3 most similar supplements.
	$similar_supplements = array_slice( $similar_supplements, 0, 3 );

	// Start output buffering.
	ob_start();
	?>

	<div class="similar-supplements-shortcode">
		<div class="similar-supplements-grid">
			<?php
			foreach ( $similar_supplements as $similar ) {
				?>
				<div class="similar-supplement-item">
					<div class="similarity-score">
						<?php echo esc_html( round( $similar['similarity'] * 100 ) ); ?>% Similar
					</div>
					<?php
					global $post;
					$post = $similar['post'];
					setup_postdata( $post );
					get_template_part( 'template-parts/supplement-card' );
					?>
				</div>
				<?php
			}
			wp_reset_postdata();
			?>
		</div>
	</div>

	<?php
	return ob_get_clean();
}
add_shortcode( 'similar_supplements', 'similar_supplements_shortcode' );
