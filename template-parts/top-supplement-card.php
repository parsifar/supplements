<?php
$index = $args['supplement_index'] ?? 0;

$badge_text = '';
switch ( $index ) {
	case 1:
		$badge_text = '1st';
		break;
	case 2:
		$badge_text = '2nd';
		break;
	case 3:
		$badge_text = '3rd';
		break;
	default:
		$badge_text = $index . 'th';
		break;
}
?>

<article class="top-supplement-card supplement-article">

	<!-- Ranking badge -->
	<?php if ( $badge_text ) : ?>
		<div class="ranking-badge">
			<i class="bi bi-trophy"></i>
			<?php echo esc_html( $badge_text ); ?>
		</div>
	<?php endif; ?>

	<!-- Header -->
	<header class="supplement-header">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="supplement-thumbnail">
				<?php the_post_thumbnail( 'full', array( 'class' => 'thumbnail-image' ) ); ?>
				
				<div class="compare-checkbox-wrapper">
					<label class="compare-checkbox-label">
						<input type="checkbox" class="compare-checkbox" value="<?php the_ID(); ?>" data-title="<?php the_title_attribute(); ?>">
						Compare
					</label>
				</div>
			</div>
		<?php endif; ?>

		<div class="supplement-meta">
			<!-- Brand -->
			<?php
			$brand = get_the_terms( get_the_ID(), 'brand' );
			if ( ! empty( $brand ) && ! is_wp_error( $brand ) ) :
				?>
				<p class="brand h4"><?php echo esc_html( $brand[0]->name ); ?></p>
			<?php endif; ?>
			
			<!-- Title -->
			<h2 class="supplement-title"><?php the_title(); ?></h2>
			
			<!-- Rating -->
			<?php
			$rating = get_field( 'amazon_rating' );
			if ( $rating ) {
				?>
				<div class="rating-bar" data-rating="<?php echo esc_html( $rating ); ?>">
					<div class="bar-label">Rating: <?php echo esc_html( $rating ); ?> out of 5</div>

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

			<!-- Price -->
			<?php
				$price             = get_field( 'price' );
				$price_per_serving = get_field( 'price_per_serving' );

			if ( $price || $price_per_serving ) {
				?>
				<div class="price-section">
					<?php
					if ( $price ) {
						?>
						<div class="price-wrapper">
							<p class="price-label">Price</p>
							<p class="price-amount"><span class="amount">$<?php echo esc_html( $price ); ?></span> /container</p>
						</div>
						<?php
					}

					if ( $price_per_serving ) {
						?>
						<div class="pps-wrapper">
							<p class="price-label">price per serving</p>
							<p class="price-amount"><span class="amount">$<?php echo esc_html( $price_per_serving ); ?></span> /serving</p>
						</div>
						<?php
					}
					?>
					
				</div>
				
				<?php
			}

			// Protein specific info
			$is_protein = has_term( 'protein', 'supplement-category' );

			if ( $is_protein ) {
				$protein_per_serving   = get_field( 'protein_per_serving' );
				$calorie_protein_ratio = get_field( 'calorie_protein_ratio' );
				$protein_per_dollar    = get_field( 'protein_per_dollar' );

				?>
				<div class="protein-section">
					<?php
					if ( $calorie_protein_ratio ) {
						?>
						<div class="cp-ratio-wrapper">
							<p class="price-label">Calories/protein ratio</p>
							<p class="price-amount"><span class="amount"><?php echo esc_html( $calorie_protein_ratio ); ?></span>cal / 1g protein</p>
						</div>
						<?php
					}

					if ( $protein_per_dollar ) {
						?>
						<div class="cp-ratio-wrapper">
							<p class="price-label">Protein per Dollar</p>
							<p class="price-amount"><span class="amount"><?php echo esc_html( $protein_per_dollar ); ?></span>g protein per $1</p>
						</div>
						<?php
					}
					?>

				</div>

				<?php
			}

			?>
			
			<!-- Quick facts -->
			<div class="quick-facts">
				<?php

				// $category = get_the_terms( get_the_ID(), 'supplement-category' );
				// if ( ! empty( $category ) ) {
				// echo '<p class="quick-fact">Category: <strong>' . esc_html( $category[0]->name ) . '</strong></p>';
				// }

				$servings = get_field( 'servings_per_container' );
				if ( $servings ) {
					echo '<p class="quick-fact">Servings per container: <strong>' . esc_html( $servings ) . '</strong></p>';
				}

				// $product_form_terms = get_the_terms( get_the_ID(), 'product-form' );
				// if ( ! empty( $product_form_terms ) && ! is_wp_error( $product_form_terms ) ) {
				// echo '<p class="quick-fact">Supplement form: <strong>' . esc_html( $product_form_terms[0]->name ) . '</strong></p>';
				// }
				?>
			</div>

			<!-- Badges -->
			<div class="badges">
				<?php

				foreach ( array( 'certification', 'dietary-tag' ) as $tax ) {
					$terms = get_the_terms( get_the_ID(), $tax );
					if ( $terms && ! is_wp_error( $terms ) ) {

						foreach ( $terms as $term ) {
							echo '<span class="badge ' . $tax . '-badge">' . esc_html( $term->name ) . '</span>';
						}
					}
				}
				?>
			</div>

			
			<div class="links-wrapper">
				<!-- Amazon Link -->
				<?php $affiliate = get_field( 'affiliate_url' ); ?>
				<?php if ( $affiliate ) : ?>
					<a href="<?php echo esc_url( $affiliate ); ?>" target="_blank" rel="nofollow noopener" class="affiliate-button btn btn-primary">
						View on Amazon
					</a>
				<?php endif; ?>

				<a href="<?php echo get_the_permalink( $post ); ?>" class="btn btn-outline">
					Learn More
				</a>
			</div>
			

		</div>

	</header>


</article>
