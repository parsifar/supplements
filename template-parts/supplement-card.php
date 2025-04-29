<article class="supplement-card">
	<a href="<?php the_permalink(); ?>" class="card-link">
		<div class="image-wrapper">
			<?php
			if ( has_post_thumbnail() ) :
				the_post_thumbnail( 'medium', array( 'class' => 'supplement-thumbnail' ) );
			endif;
			?>
			<div class="compare-checkbox-wrapper">
				<label class="compare-checkbox-label">
					<input type="checkbox" class="compare-checkbox" value="<?php the_ID(); ?>" data-title="<?php the_title_attribute(); ?>">
					Compare
				</label>
			</div>
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

			<?php
			$rating = get_field( 'amazon_rating' );
			if ( $rating ) {
				?>
				<div class="rating-bar" data-rating="<?php echo esc_html( $rating ); ?>">
					<div class="bar-label">Rating: <?php echo esc_html( $rating ); ?>/5</div>

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

			}
			?>
			
			
		</div>
	</a>

	<div class="card-footer">
		<?php if ( $affiliate = get_field( 'affiliate_url' ) ) : ?>
			<a href="<?php echo esc_url( $affiliate ); ?>" class="btn btn-primary small" target="_blank" rel="nofollow noopener">View on Amazon</a>
		<?php endif; ?>
	</div>
</article>
