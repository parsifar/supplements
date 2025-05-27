<?php
// Card Title
$card_title = get_field( 'card_title' );

if ( empty( $card_title ) ) {
	$card_title = get_the_title();
}
// Get supplement categories for the current post
$categories = get_the_terms( get_the_ID(), 'supplement-category' );

// Initialize the highlight content
$highlight = '';

if ( $categories && ! is_wp_error( $categories ) ) {
	foreach ( $categories as $category ) {
		$slug = $category->slug;

		if ( $slug === 'pre-workout' ) {
			// Display total caffeine content if available.
			$caffeine = get_field( 'total_caffeine_content' );
			if ( $caffeine ) {
				$highlight = "<strong>{$caffeine}</strong>mg caffeine per serving";
			} else {
				// Check for L-Citrulline or L-Citrulline Malate
				$dosages = get_field( 'dosages' );
				if ( $dosages ) {
					$citrulline_amount        = 0;
					$citrulline_malate_amount = 0;

					foreach ( $dosages as $dosage ) {
						$ingredient = $dosage['ingredient'];
						if ( $ingredient ) {
							if ( $ingredient->ID === 63 ) { // L-Citrulline
								$citrulline_amount = $dosage['amount'];
							} elseif ( $ingredient->ID === 55 ) { // L-Citrulline Malate
								$citrulline_malate_amount = $dosage['amount'];
							}
						}
					}

					if ( $citrulline_amount > 0 ) {
						$highlight = "<strong>{$citrulline_amount}</strong>mg L-Citrulline per serving";
					} elseif ( $citrulline_malate_amount > 0 ) {
						$highlight = "<strong>{$citrulline_malate_amount}</strong>mg L-Citrulline Malate per serving";
					}
				}
			}
			break;

		} elseif ( $slug === 'protein' ) {
			// Display protein per serving if available.
			$protein = get_field( 'protein_per_serving' );
			if ( $protein ) {
				$highlight = "<strong>{$protein}</strong>g protein per serving";
			}
			break;

		}

		// You can add more category-specific highlights here
		// elseif ($slug === 'multivitamin') {
		// $highlight = "Some multivitamin-specific field";
		// }
	}
}
?>
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

					<h2 class="supplement-title"><?php echo esc_html( $card_title ); ?></h2>

				</div>
			
				
				<?php
				$price    = get_field( 'price' );
				$pps      = get_field( 'price_per_serving' );
				$servings = get_field( 'servings_per_container' );

				if ( $price || $pps ) {
					echo '<div class="price-col">';
					if ( $price ) {
						echo '<span class="price h4">$' . number_format( $price, 2 ) . '</span>';
					}
					if ( $pps ) {
						echo '<span class="price-per-serving text-tiny">$' . number_format( $pps, 2 ) . '/serving</span>';
					}

					if ( $servings ) {
						echo '<span class="servings  text-tiny">' . esc_html( $servings ) . ' servings</span>';
					}
					echo '</div>';
				}
				?>

			</div>
			
			<?php
			// Output the highlight section if not empty.
			if ( $highlight ) :
				?>
				<div class="supplement-highlight">
					<i class="bi bi-rocket-takeoff"></i>
					<?php echo wp_kses_post( $highlight ); ?>
				</div>
			<?php endif; ?>

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

				foreach ( array( 'supplement-category', 'certification', 'product-form' ) as $tax ) {
					$terms = get_the_terms( get_the_ID(), $tax );
					if ( $terms && ! is_wp_error( $terms ) ) {

						$icon = '';
						if ( $tax === 'certification' ) {
							$icon = '<i class="bi bi-award"></i>';
						} elseif ( $tax === 'supplement-category' ) {
							$icon = '<i class="bi bi-tag"></i>';
						} elseif ( $tax === 'product-form' ) {
							$icon = '<i class="bi bi-flask"></i>';
						}

						foreach ( $terms as $term ) {
							echo '<span class="badge ' . $tax . '-badge">' . $icon . esc_html( $term->name ) . '</span>';
						}
					}
				}
				?>
			</div>
			

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
			<a href="<?php echo esc_url( $affiliate ); ?>" class="btn btn-primary small" target="_blank" rel="nofollow noopener"><i class="bi bi-amazon"></i> View on Amazon</a>
		<?php endif; ?>
	</div>
</article>
