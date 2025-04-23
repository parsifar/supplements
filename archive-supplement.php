<?php get_header(); ?>

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

?>

<main class="supplements-main">

	<header class="supplements-header">
		<h1 class="archive-title"><?php post_type_archive_title(); ?></h1>
		<p class="archive-subtitle">Browse all supplements</p>
	</header>

	<!-- MOBILE FILTER TOGGLE BUTTON -->
	<button class="mobile-filter-toggle" onclick="document.querySelector('.filters-sidebar').classList.toggle('open')">
		Filter Results
	</button>

	<div class="supplements-layout">
		<aside class="filters-sidebar">

			<button class="mobile-filter-close" onclick="document.querySelector('.filters-sidebar').classList.remove('open')">
				Close ✕
			</button>
			
			<form method="GET" class="supplements-filter-form">
				<?php
				$taxonomies = array(
					'brand'               => 'Brand',
					'certification'       => 'Certification',
					'dietary-tag'         => 'Dietary Tag',
					'product-form'        => 'Product Form',
					'supplement-category' => 'Category',
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
					<div class="filter-group">
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
					<label for="sort" class="filter-label">Sort By</label>
					<select name="sort" id="sort" class="filter-select">
						<option value="">Default</option>
						<option value="price_asc" <?php selected( $_GET['sort'] ?? '', 'price_asc' ); ?>>Price: Low to High</option>
						<option value="price_desc" <?php selected( $_GET['sort'] ?? '', 'price_desc' ); ?>>Price: High to Low</option>
						<option value="name_asc" <?php selected( $_GET['sort'] ?? '', 'name_asc' ); ?>>Name: A–Z</option>
						<option value="name_desc" <?php selected( $_GET['sort'] ?? '', 'name_desc' ); ?>>Name: Z–A</option>
					</select>
				</div>

				<div class="filter-actions">
					<button type="submit" class="btn-primary">Apply Filters</button>
					<a href="<?php echo get_post_type_archive_link( 'supplement' ); ?>" class="reset-link">Reset Filters</a>
				</div>
			</form>
		</aside>

		<section class="supplements-content">
			<?php if ( have_posts() ) : ?>
				<div class="supplement-grid">
					<?php
					while ( have_posts() ) {
						the_post();
						get_template_part( 'template-parts/supplement-card' );
					}
					?>
				</div>

				<div class="pagination">
					<?php
					the_posts_pagination(
						array(
							'mid_size'           => 1,
							'prev_text'          => '« Previous',
							'next_text'          => 'Next »',
							'screen_reader_text' => '',
						)
					);
					?>
				</div>
			<?php else : ?>
				<p class="no-results">No supplements found.</p>
			<?php endif; ?>
		</section>
	</div>
</main>

<div id="compare-bar" class="compare-bar">
	<a id="compare-link" href="#" class="compare-link">Compare (<span id="compare-count">0</span>)</a>
</div>

<script>
	const maxCompare = 4;
	const compareBar = document.getElementById('compare-bar');
	const compareLink = document.getElementById('compare-link');
	const compareCount = document.getElementById('compare-count');
	const checkboxes = document.querySelectorAll('.compare-checkbox');
	let selectedIds = [];

	function updateUI() {
		compareCount.textContent = selectedIds.length;
		compareBar.style.display = selectedIds.length > 0 ? 'block' : 'none';
		compareLink.href = `/compare/?ids=${selectedIds.join(',')}`;
	}

	checkboxes.forEach(box => {
		box.addEventListener('change', () => {
			const id = box.value;
			if (box.checked) {
				if (selectedIds.length < maxCompare) {
					selectedIds.push(id);
				} else {
					box.checked = false;
					alert(`You can only compare up to ${maxCompare} supplements.`);
				}
			} else {
				selectedIds = selectedIds.filter(i => i !== id);
			}
			updateUI();
		});
	});
</script>

<?php get_footer(); ?>
