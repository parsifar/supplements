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

			<div class="content-header">
				<div class="title-col">
					
					<?php
					$brand = get_the_terms( get_the_ID(), 'brand' );
					if ( ! empty( $brand ) && ! is_wp_error( $brand ) ) {
						echo '<span class="brand h6">' . esc_html( $brand[0]->name ) . '</span>';
					}
					?>

					<h2 class="supplement-title h4"><?php the_title(); ?></h2>

				</div>
				
				<?php
				$price = get_field( 'price' );
				$pps   = get_field( 'price_per_serving' );

				if ( $price || $pps ) {
					echo '<div class="price-col">';
					if ( $price ) {
						echo '<span class="price h4">$' . number_format( $price, 2 ) . '</span>';
					}
					if ( $pps ) {
						echo '<span class="price-per-serving text-tiny">$' . number_format( $pps, 2 ) . '/serving</span>';
					}
					echo '</div>';
				}
				?>

			</div>
			
			

			<?php
			$ingredients = get_field( 'ingredients' );
			if ( $ingredients ) {
				echo '<p class="ingredients-list"><strong>Key Ingredients:</strong> ';
				echo implode(
					', ',
					array_map(
						fn( $i ) => '<span  class="ingredient-item">' . get_the_title( $i ) . '</span>',
						$ingredients
					)
				);
				echo '</p>';
			}
			?>
			
			<div class="badges">
				<?php

				foreach ( array( 'supplement-category', 'certification', 'dietary-tag', 'product-form' ) as $tax ) {
					$terms = get_the_terms( get_the_ID(), $tax );
					if ( $terms && ! is_wp_error( $terms ) ) {

						foreach ( $terms as $term ) {
							echo '<span class="badge ' . $tax . '-badge">' . esc_html( $term->name ) . '</span>';
						}
					}
				}
				?>
			</div>
			<?php

			$servings = get_field( 'servings_per_container' );
			if ( $servings ) {
				echo '<p class="servings">' . esc_html( $servings ) . ' servings</p>';
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
			<a href="<?php echo esc_url( $affiliate ); ?>" class="btn-primary small" target="_blank" rel="nofollow noopener">See on Amazon</a>
		<?php endif; ?>
	</div>
</article>
