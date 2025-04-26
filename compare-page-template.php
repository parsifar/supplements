<?php
/* Template Name: Compare Page */
get_header();


function get_selected_ids() {
	if ( isset( $_GET['ids'] ) ) {
		return array_filter( array_map( 'absint', explode( ',', $_GET['ids'] ) ) );
	}
	return array();
}

$ids = get_selected_ids();

if ( ! $ids ) {
	echo '<p class="compare__no-items">No supplements selected for comparison.</p>';
	get_footer();
	exit;
}

$query = new WP_Query(
	array(
		'post_type' => 'supplement',
		'post__in'  => $ids,
		'orderby'   => 'post__in',
	)
);

// Sync localStorage with URL on page load
echo '<script>localStorage.setItem("compareIds", ' . json_encode( json_encode( array_map( 'strval', $ids ) ) ) . ');</script>';







$ingredient_map = array();
foreach ( $query->posts as $post ) {
	$post_id = $post->ID;
	$dosages = get_field( 'dosages', $post_id );

	if ( $dosages ) {
		foreach ( $dosages as $row ) {
			$ingredient_id = $row['ingredient'] ? $row['ingredient']->ID : null;
			if ( ! $ingredient_id ) {
				continue;
			}

			$ingredient_name = get_the_title( $ingredient_id );
			if ( ! isset( $ingredient_map[ $ingredient_id ] ) ) {
				$ingredient_map[ $ingredient_id ] = array(
					'name'     => $ingredient_name,
					'products' => array(),
				);
			}

			$ingredient_map[ $ingredient_id ]['products'][ $post_id ] = array(
				'amount' => $row['amount'],
				'unit'   => $row['unit'],
			);
		}
	}
}

uasort(
	$ingredient_map,
	function ( $a, $b ) {
		return count( $b['products'] ) - count( $a['products'] );
	}
);
?>

<div class="compare">
	<h1 class="compare__title">Compare Supplements</h1>

	<div class="compare__table-wrapper">
	<table class="compare__table">
		<thead>
		<tr class="compare__table-header">
			<th class="compare__attribute-header">Attribute</th>
			<?php foreach ( $query->posts as $post ) : ?>
			<th class="compare__product-header">
				<div class="compare__product-header-content">
				<a href="<?php echo get_permalink( $post ); ?>" class="compare__product-link  inline-link">
					<?php echo esc_html( $post->post_title ); ?>
				</a>
				<button class="compare__remove-btn" data-id="<?php echo esc_attr( $post->ID ); ?>">Remove</button>
				</div>
			</th>
			<?php endforeach; ?>
		</tr>

		<tr>
			<td class="compare__attribute">Image</td>
			<?php foreach ( $query->posts as $post ) : ?>
			<td>
				<?php if ( has_post_thumbnail( $post ) ) : ?>
				<a href="<?php echo get_permalink( $post ); ?>">
					<?php echo get_the_post_thumbnail( $post, 'medium', array( 'class' => 'compare__product-image' ) ); ?>
				</a>
				<?php else : ?>
				<div class="compare__image-placeholder">No Image</div>
				<?php endif; ?>
			</td>
			<?php endforeach; ?>
		</tr>
		</thead>

		<tbody>
		<tr>
			<td class="compare__attribute">Brand</td>
			<?php
			foreach ( $query->posts as $post ) :
				$brands = get_the_terms( $post, 'brand' );
				?>
			<td><?php echo $brands ? esc_html( $brands[0]->name ) : '—'; ?></td>
			<?php endforeach; ?>
		</tr>

		<tr>
			<td class="compare__attribute">Category</td>
			<?php
			foreach ( $query->posts as $post ) :
				$cats = get_the_terms( $post, 'supplement-category' );
				?>
			<td><?php echo $cats ? esc_html( $cats[0]->name ) : '—'; ?></td>
			<?php endforeach; ?>
		</tr>

		<tr>
			<td class="compare__attribute">Certifications</td>
			<?php foreach ( $query->posts as $post ) : ?>
			<td>
				<?php
				$certs = get_the_terms( $post->ID, 'certification' );
				echo $certs ? implode( ', ', wp_list_pluck( $certs, 'name' ) ) : '—';
				?>
			</td>
			<?php endforeach; ?>
		</tr>

		<tr>
			<td class="compare__attribute">Servings / Container</td>
			<?php foreach ( $query->posts as $post ) : ?>
			<td><?php echo esc_html( get_field( 'servings_per_container', $post->ID ) ?: '—' ); ?></td>
			<?php endforeach; ?>
		</tr>

		<tr>
			<td class="compare__attribute">Price</td>
			<?php foreach ( $query->posts as $post ) : ?>
			<td>$<?php echo esc_html( get_field( 'price', $post->ID ) ?: '—' ); ?></td>
			<?php endforeach; ?>
		</tr>

		<tr>
			<td class="compare__attribute">Price / Serving</td>
			<?php foreach ( $query->posts as $post ) : ?>
			<td>$<?php echo esc_html( get_field( 'price_per_serving', $post->ID ) ?: '—' ); ?></td>
			<?php endforeach; ?>
		</tr>

		<tr>
			<td class="compare__attribute">Average Rating</td>
			<?php foreach ( $query->posts as $post ) : ?>
			<td>
				<?php
				$rating = get_field( 'amazon_rating' );
				if ( $rating ) {
					?>
					<div class="rating-bar" data-rating="<?php echo esc_html( $rating ); ?>">
						<div class="bar-label"><?php echo esc_html( $rating ); ?> out of 5</div>

						<div class="bar-wrapper">
							<div class="bar-bg">
								<div class="bar-fill"></div>
								<div class="bar-ticks">
									<div class="segment" data-label="1"></div>
									<div class="segment" data-label="2"></div>
									<div class="segment" data-label="3"></div>
									<div class="segment" data-label="4"></div>
									<div class="segment last" data-label="5"></div>
								</div>
							</div>
						</div>

					</div>

					<?php
				} else {
					echo '-';
				}
				?>
			</td>
			<?php endforeach; ?>
		</tr>

		<tr class="compare__ingredient-heading-row">
			<td class="compare__attribute compare__ingredient-heading sticky-left">Ingredients</td>
			<td class="compare__ingredient-heading-spacer" colspan="<?php echo count( $query->posts ); ?>"></td>
		</tr>

		<?php
		foreach ( $ingredient_map as $ingredient_id => $data ) :
			$doses    = array_column( $data['products'], 'amount' );
			$max_dose = max( $doses );
			?>
			<tr class="compare__ingredient-row">
				<td class="compare__attribute compare__ingredient-name">
					<span class="tooltip-wrapper">
						<a href="<?php echo get_permalink( $ingredient_id ); ?>" class="ingredient-link inline-link ">
							<?php echo esc_html( $data['name'] ); ?>
						</a>
						<span class="tooltip-text"><?php echo esc_html( get_the_excerpt( $ingredient_id ) ?: 'No description available' ); ?></span>
					</span>
				</td>

				<?php
				foreach ( $query->posts as $post ) :
					$dose            = $data['products'][ $post->ID ] ?? null;
					$highlight_class = ( $dose && $dose['amount'] == $max_dose ) ? 'compare__highlight' : '';
					?>
					<td class="<?php echo $highlight_class; ?>">
					<?php echo $dose ? esc_html( "{$dose['amount']} {$dose['unit']}" ) : '—'; ?>
					</td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>

		<tr>
			<td class="compare__attribute">Buy</td>
			<?php
			foreach ( $query->posts as $post ) :
				$url = get_field( 'affiliate_url', $post->ID );
				?>
			<td>
				<?php if ( $url ) : ?>
				<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="compare__buy-btn btn btn-primary">View on Amazon</a>
				<?php else : ?>
				—
				<?php endif; ?>
			</td>
			<?php endforeach; ?>
		</tr>
		</tbody>
	</table>
	</div>
</div>

<script>
	// Sync localStorage -> URL (if user navigated here directly)
	const localIds = localStorage.getItem('compareIds');
	const urlParams = new URLSearchParams(window.location.search);
	let urlIds = urlParams.get('ids');

	// Only set the URL if there's no existing `ids` parameter and if `localIds` is available
	if (!urlIds && localIds) {
		const idsArray = JSON.parse(localIds);  // Parse the local storage value to get an array
		urlParams.set('ids', idsArray.join(',')); // Join the array into a comma-separated string for the URL
		const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
		window.history.replaceState({}, '', newUrl); // Update the URL without refreshing the page
	}


	// Remove a product from comparison
	document.querySelectorAll('.compare__remove-btn').forEach(btn => {
		btn.addEventListener('click', function () {
		const id = this.getAttribute('data-id');
		let ids = JSON.parse(localStorage.getItem('compareIds') || '[]').filter(n => n !== id);

		localStorage.setItem('compareIds', JSON.stringify(ids));

		// Clean up title
		localStorage.removeItem(`compareTitle-${id}`);

		if (ids.length) {
			window.location.search = '?ids=' + ids.join(',');
		} else {
			window.location.href = window.location.pathname;
		}
	});

	});
</script>



<?php get_footer(); ?>
