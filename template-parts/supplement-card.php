<article class="supplement-card">
	<a href="<?php the_permalink(); ?>" class="card-link">
		<div class="image-wrapper">
			<?php
			if ( has_post_thumbnail() ) :
				the_post_thumbnail( 'medium', array( 'class' => 'supplement-thumbnail' ) );
			endif;
			?>
		</div>

		<div class="card-content">
			<h2 class="supplement-title"><?php the_title(); ?></h2>
			<p class="supplement-excerpt"><?php echo wp_trim_words( get_the_excerpt(), 18 ); ?></p>

			<?php
			$brand = get_the_terms( get_the_ID(), 'brand' );
			if ( ! empty( $brand ) && ! is_wp_error( $brand ) ) {
				echo '<span class="badge badge-brand">Brand: ' . esc_html( $brand[0]->name ) . '</span>';
			}
			$category = get_the_terms( get_the_ID(), 'supplement-category' );
			if ( ! empty( $category ) && ! is_wp_error( $category ) ) {
				echo '<span class="badge badge-category">Category: ' . esc_html( $category[0]->name ) . '</span>';
			}

			foreach ( array( 'certification', 'dietary-tag', 'product-form' ) as $tax ) {
				$terms = get_the_terms( get_the_ID(), $tax );
				if ( $terms && ! is_wp_error( $terms ) ) {
					echo '<div class="badge-group">';
					foreach ( $terms as $term ) {
						echo '<span class="badge badge-neutral">' . esc_html( $term->name ) . '</span>';
					}
					echo '</div>';
				}
			}

			$servings = get_field( 'servings_per_container' );
			if ( $servings ) {
				echo '<p class="supplement-info"><strong>Servings:</strong> ' . esc_html( $servings ) . '</p>';
			}

			$ingredients = get_field( 'ingredients' );
			if ( $ingredients ) {
				echo '<p class="supplement-info"><strong>Ingredients:</strong> ';
				echo implode(
					', ',
					array_map(
						fn( $i ) => '<a href="' . get_permalink( $i ) . '" class="ingredient-link">' . get_the_title( $i ) . '</a>',
						$ingredients
					)
				);
				echo '</p>';
			}

			$price = get_field( 'price' );
			$pps   = get_field( 'price_per_serving' );

			if ( $price || $pps ) {
				echo '<div class="pricing">';
				if ( $price ) {
					echo '<p class="supplement-info"><strong>Price:</strong> $' . number_format( $price, 2 ) . '</p>';
				}
				if ( $pps ) {
					echo '<p class="supplement-info"><strong>Price/Serving:</strong> $' . number_format( $pps, 2 ) . '</p>';
				}
				echo '</div>';
			}
			?>
		</div>
	</a>

	<div class="card-footer">
		<label class="compare-checkbox-label">
			<input type="checkbox" class="compare-checkbox" value="<?php the_ID(); ?>">
			Compare
		</label>

		<?php if ( $affiliate = get_field( 'affiliate_url' ) ) : ?>
			<a href="<?php echo esc_url( $affiliate ); ?>" class="btn-primary small" target="_blank" rel="nofollow noopener">Buy on Amazon</a>
		<?php endif; ?>
	</div>
</article>
