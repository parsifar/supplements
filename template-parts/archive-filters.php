<?php
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

?>
<form method="GET" class="supplements-filter-form">
	<?php
	$taxonomies = array(
		'supplement-category' => 'Category',
		'brand'               => 'Brand',
		'certification'       => 'Certification',
		'dietary-tag'         => 'Dietary Tag',
		'product-form'        => 'Product Form',
	);

	foreach ( $taxonomies as $taxonomy => $label ) :
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			)
		);
		if ( ! empty( $terms ) ) :
			?>
		<div class="filter-group select-wrapper">
			<label for="<?php echo esc_attr( $taxonomy ); ?>" class="filter-label"><?php echo esc_html( $label ); ?></label>
			<select name="<?php echo esc_attr( $taxonomy ); ?>" id="<?php echo esc_attr( $taxonomy ); ?>" class="filter-select">
				<option value="">All <?php echo esc_html( $label ); ?></option>
				<?php foreach ( $terms as $term ) : ?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $_GET[ $taxonomy ] ?? '', $term->slug ); ?>>
						<?php echo esc_html( $term->name ); ?>
					</option>
				<?php endforeach; ?>
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
			<option value="price_asc" <?php selected( $_GET['sort'] ?? '', 'price_asc' ); ?>>Price: Low to High</option>
			<option value="price_desc" <?php selected( $_GET['sort'] ?? '', 'price_desc' ); ?>>Price: High to Low</option>
			<option value="pps_asc" <?php selected( $_GET['sort'] ?? '', 'pps_asc' ); ?>>Price per serving: Low to High</option>
			<option value="pps_desc" <?php selected( $_GET['sort'] ?? '', 'pps_desc' ); ?>>Price per serving: High to Low</option>
			<option value="rating_asc" <?php selected( $_GET['sort'] ?? '', 'rating_asc' ); ?>>Rating: Low to High</option>
			<option value="rating_desc" <?php selected( $_GET['sort'] ?? '', 'rating_desc' ); ?>>Rating: High to Low</option>
		</select>
	</div>

	<div class="filter-actions">
		<button type="submit" class="btn btn-secondary small">Apply Filters</button>
		<a href="<?php echo get_post_type_archive_link( 'supplement' ); ?>" class="reset-link">Reset Filters</a>
	</div>
</form>
