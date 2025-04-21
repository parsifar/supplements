<?php
/* Template Name: Compare Page */
get_header();

function get_selected_ids() {
	if ( isset( $_GET['ids'] ) ) {
		return array_filter( array_map( 'absint', explode( ',', $_GET['ids'] ) ) );
	} elseif ( isset( $_COOKIE['compare_supplements'] ) ) {
		return array_filter( array_map( 'absint', explode( ',', $_COOKIE['compare_supplements'] ) ) );
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

echo "<script>localStorage.setItem('compare_ids', '" . implode( ',', $ids ) . "');</script>";

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
				<a href="<?php echo get_permalink( $post ); ?>" class="compare__product-link">
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

		<tr class="compare__ingredient-heading-row">
			<td colspan="<?php echo count( $query->posts ) + 1; ?>">Ingredients</td>
		</tr>

		<?php
		foreach ( $ingredient_map as $ingredient_id => $data ) :
			$doses    = array_column( $data['products'], 'amount' );
			$max_dose = max( $doses );
			?>
			<tr>
			<td class="compare__attribute compare__ingredient-name">
				<span class="compare__tooltip">
				<a href="<?php echo get_permalink( $ingredient_id ); ?>" class="compare__product-link">
					<?php echo esc_html( $data['name'] ); ?>
				</a>
				<span class="compare__tooltip-content">
					<?php echo esc_html( get_the_excerpt( $ingredient_id ) ?: 'No description available' ); ?>
				</span>
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
				<a href="<?php echo esc_url( $url ); ?>" class="compare__buy-btn">Buy Now</a>
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
	document.querySelectorAll('.compare__remove-btn').forEach(btn => {
	btn.addEventListener('click', function () {
		const id = this.getAttribute('data-id');
		const params = new URLSearchParams(window.location.search);
		const ids = (params.get('ids') || '').split(',').map(Number).filter(n => n && n !== parseInt(id));
		localStorage.setItem('compare_ids', ids.join(','));
		if (ids.length) {
		window.location.search = '?ids=' + ids.join(',');
		} else {
		window.location.href = window.location.pathname;
		}
	});
	});

	const urlParams = new URLSearchParams(window.location.search);
	const activeIds = urlParams.get('ids');
	if (activeIds) {
	localStorage.setItem('compare_ids', activeIds);
	document.cookie = 'compare_supplements=' + activeIds + ';path=/;max-age=31536000';
	}
</script>

<?php get_footer(); ?>
