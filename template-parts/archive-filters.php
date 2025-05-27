<?php
/**
 * Template part for displaying archive filters.
 *
 * @package Supp_Pick
 */

// Get max price
$max_price_post = get_posts(
	array(
		'post_type'      => 'supplement',
		'posts_per_page' => 1,
		'orderby'        => 'meta_value_num',
		'meta_key'       => 'price',
		'order'          => 'DESC',
		'fields'         => 'ids',
	)
);
$price_limit    = $max_price_post ? floatval( get_post_meta( $max_price_post[0], 'price', true ) ) : 100.00;

// Get max price per serving
$max_pps_post = get_posts(
	array(
		'post_type'      => 'supplement',
		'posts_per_page' => 1,
		'orderby'        => 'meta_value_num',
		'meta_key'       => 'price_per_serving',
		'order'          => 'DESC',
		'fields'         => 'ids',
	)
);
$pps_limit    = $max_pps_post ? floatval( get_post_meta( $max_pps_post[0], 'price_per_serving', true ) ) : 10.00;

// Get current filter values from URL or fall back to limits
$current_price = isset( $_GET['max_price'] ) ? floatval( $_GET['max_price'] ) : $price_limit;
$current_pps   = isset( $_GET['max_pps'] ) ? floatval( $_GET['max_pps'] ) : $pps_limit;

$current_rating = isset( $_GET['min_rating'] ) ? floatval( $_GET['min_rating'] ) : 1;

/**
 * Recursively displays taxonomy terms in a hierarchical structure.
 *
 * @param array  $terms    Array of term objects.
 * @param string $taxonomy Taxonomy name.
 * @param int    $depth    Current depth level for indentation.
 */
function supp_pick_display_terms_hierarchically( $terms, $taxonomy, $depth = 0 ) {
	foreach ( $terms as $term ) {
		$indent = str_repeat( '— ', $depth );
		?>
		<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( wp_unslash( $_GET[ 'selected_' . $taxonomy ] ?? '' ), $term->slug ); ?>>
			<?php echo esc_html( $indent . $term->name ); ?>
		</option>
		<?php
		// Get child terms.
		$child_terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'parent'     => $term->term_id,
			)
		);
		if ( ! empty( $child_terms ) && ! is_wp_error( $child_terms ) ) {
			supp_pick_display_terms_hierarchically( $child_terms, $taxonomy, $depth + 1 );
		}
	}
}

?>
<form method="GET" id="filter-form" class="supplements-filter-form">
	<?php
	$taxonomies = array(
		'supplement-category' => 'Category',
		'brand'               => 'Brand',
		'certification'       => 'Certification',
		'dietary-tag'         => 'Dietary Tag',
		'product-form'        => 'Product Form',
	);

	// Remove 'supplement-category' if we're on a category archive page.
	if ( is_tax( 'supplement-category' ) ) {
		unset( $taxonomies['supplement-category'] );
	}

	foreach ( $taxonomies as $taxonomy => $label ) :
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'parent'     => 0, // Get only top-level terms.
			)
		);

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) :
			?>
		<div class="filter-group select-wrapper">
			<label for="<?php echo esc_attr( $taxonomy ); ?>" class="filter-label"><?php echo esc_html( $label ); ?></label>
			<select name="<?php echo 'selected_' . esc_attr( $taxonomy ); ?>" id="<?php echo esc_attr( $taxonomy ); ?>" class="filter-select">
				<option value="">All <?php echo esc_html( $label ); ?></option>
				<?php supp_pick_display_terms_hierarchically( $terms, $taxonomy ); ?>
			</select>
		</div>
			<?php
		endif;
	endforeach;
	?>

	<div class="filter-group">
		<label for="max_price" class="filter-label">
			Max Price ($<span id="priceValue"><?php echo esc_html( $current_price ); ?></span>)
		</label>
		<input type="range" name="max_price" id="max_price" min="0" max="<?php echo esc_attr( $price_limit ); ?>" step="0.01" value="<?php echo esc_attr( $current_price ); ?>" class="filter-range" oninput="document.getElementById('priceValue').textContent = this.value">
	</div>

	<div class="filter-group">
		<label for="max_pps" class="filter-label">
			Max Price Per Serving ($<span id="ppsValue"><?php echo esc_html( $current_pps ); ?></span>)
		</label>
		<input type="range" name="max_pps" id="max_pps" min="0" max="<?php echo esc_attr( $pps_limit ); ?>" step="0.01" value="<?php echo esc_attr( $current_pps ); ?>" class="filter-range" oninput="document.getElementById('ppsValue').textContent = this.value">
	</div>

	<div class="filter-group">
		<label for="min_rating" class="filter-label">
			Min Average Rating (<span id="ratingValue"><?php echo esc_html( $current_rating ); ?></span> out of 5)
		</label>
		<input type="range" name="min_rating" id="min_rating" min="1" max="5" step="0.1" value="<?php echo esc_attr( $current_rating ); ?>" class="filter-range" oninput="document.getElementById('ratingValue').textContent = this.value">
	</div>

	<div class="filter-group select-wrapper">
		<label for="sort" class="filter-label">Sort By</label>
		<select name="sort" id="sort" class="filter-select">
			<option value="">Default</option>
			<option value="price_asc" <?php selected( wp_unslash( $_GET['sort'] ?? '' ), 'price_asc' ); ?>>Price: Low to High</option>
			<option value="price_desc" <?php selected( wp_unslash( $_GET['sort'] ?? '' ), 'price_desc' ); ?>>Price: High to Low</option>
			<option value="pps_asc" <?php selected( wp_unslash( $_GET['sort'] ?? '' ), 'pps_asc' ); ?>>Price per serving: Low to High</option>
			<option value="pps_desc" <?php selected( wp_unslash( $_GET['sort'] ?? '' ), 'pps_desc' ); ?>>Price per serving: High to Low</option>
			<option value="rating_asc" <?php selected( wp_unslash( $_GET['sort'] ?? '' ), 'rating_asc' ); ?>>Rating: Low to High</option>
			<option value="rating_desc" <?php selected( wp_unslash( $_GET['sort'] ?? '' ), 'rating_desc' ); ?>>Rating: High to Low</option>
			<?php
			// Add protein-specific sorting options if we're on a protein-related term
			if ( is_tax( 'supplement-category' ) ) {
				$current_term       = get_queried_object();
				$is_protein_related = false;
				$is_preworkout      = false;

				// Check if current term is protein or a child of protein
				if ( $current_term && ! is_wp_error( $current_term ) ) {
					$ancestors    = get_ancestors( $current_term->term_id, 'supplement-category' );
					$protein_term = get_term_by( 'slug', 'protein', 'supplement-category' );

					if ( $protein_term && ! is_wp_error( $protein_term ) ) {
						$is_protein_related = ( $current_term->term_id === $protein_term->term_id || in_array( $protein_term->term_id, $ancestors ) );
					}

					$is_preworkout = ( $current_term->slug === 'pre-workout' );
				}

				if ( $is_protein_related ) {
					?>
					<option value="protein_per_serving_asc" <?php selected( wp_unslash( $_GET['sort'] ?? '' ), 'protein_per_serving_asc' ); ?>>Protein per serving: Low to High</option>
					<option value="protein_per_serving_desc" <?php selected( wp_unslash( $_GET['sort'] ?? '' ), 'protein_per_serving_desc' ); ?>>Protein per serving: High to Low</option>
					<option value="calorie_protein_ratio_asc" <?php selected( wp_unslash( $_GET['sort'] ?? '' ), 'calorie_protein_ratio_asc' ); ?>>Calorie/Protein ratio: Low to High</option>
					<option value="calorie_protein_ratio_desc" <?php selected( wp_unslash( $_GET['sort'] ?? '' ), 'calorie_protein_ratio_desc' ); ?>>Calorie/Protein ratio: High to Low</option>
					<option value="protein_per_dollar_asc" <?php selected( wp_unslash( $_GET['sort'] ?? '' ), 'protein_per_dollar_asc' ); ?>>Protein per dollar: Low to High</option>
					<option value="protein_per_dollar_desc" <?php selected( wp_unslash( $_GET['sort'] ?? '' ), 'protein_per_dollar_desc' ); ?>>Protein per dollar: High to Low</option>
					<?php
				}

				if ( $is_preworkout ) {
					?>
					<option value="caffeine_asc" <?php selected( wp_unslash( $_GET['sort'] ?? '' ), 'caffeine_asc' ); ?>>Caffeine content: Low to High</option>
					<option value="caffeine_desc" <?php selected( wp_unslash( $_GET['sort'] ?? '' ), 'caffeine_desc' ); ?>>Caffeine content: High to Low</option>
					<?php
				}
			}
			?>
		</select>
	</div>

	<?php
	// Check if we're on the category archive page.
	if ( is_tax( 'supplement-category' ) ) {
		// On category archive, reset filters but keep the category in the URL.
		$reset_url = remove_query_arg( array( 'selected_brand', 'selected_certification', 'selected_dietary-tag', 'selected_product-form', 'max_price', 'max_pps', 'min_rating', 'sort' ) );
	} else {
		// On the main archive page, just go to the main supplement archive.
		$reset_url = get_post_type_archive_link( 'supplement' );
	}
	?>

	<!-- Add the current page to the form -->
	<input type="hidden" id="paged-input" name="paged" value="<?php echo get_query_var( 'paged' ) ?: 1; ?>">

	<div class="filter-actions">
		<button type="submit" class="btn btn-secondary small">Apply Filters</button>
		<a href="<?php echo esc_url( $reset_url ); ?>" class="reset-link">Reset Filters</a>
	</div>
</form>

<script>
document.getElementById('filter-form').addEventListener('submit', function() {
	// Always reset to page 1 when the form is submitted
	document.getElementById('paged-input').value = 1;
});
</script>
