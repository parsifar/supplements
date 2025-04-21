<?php get_header(); ?>

<main class="single-supplement-container">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			?>

			<a href="<?php echo get_post_type_archive_link( 'supplement' ); ?>" class="back-link">
			← Back to all supplements
			</a>

			<article class="supplement-article">
				<!-- Header -->
				<header class="supplement-header">
					<div class="supplement-meta">
						<h1 class="supplement-title"><?php the_title(); ?></h1>

						<?php
						$brand = get_the_terms( get_the_ID(), 'brand' );
						if ( ! empty( $brand ) ) {
							echo '<p class="supplement-brand">Brand: ' . esc_html( $brand[0]->name ) . '</p>';
						}

						$category = get_the_terms( get_the_ID(), 'supplement-category' );
						if ( ! empty( $category ) ) {
							echo '<p class="supplement-category">Category: ' . esc_html( $category[0]->name ) . '</p>';
						}
						?>
					</div>

					<?php if ( has_post_thumbnail() ) : ?>
					<div class="supplement-thumbnail">
						<?php the_post_thumbnail( 'medium', array( 'class' => 'thumbnail-image' ) ); ?>
					</div>
					<?php endif; ?>
				</header>

				<!-- Content -->
				<div class="supplement-content">
					<?php the_content(); ?>
				</div>

				<!-- Meta info -->
				<section class="supplement-details">

					<div class="details-list">
						<?php
						$servings = get_field( 'servings_per_container' );
						if ( $servings ) {
							echo '<p><strong>Servings per container:</strong> ' . esc_html( $servings ) . '</p>';
						}

						$price = get_field( 'price' );
						if ( $price ) {
							echo '<p><strong>Price:</strong> $' . esc_html( $price ) . '</p>';
						}

						$price_per_serving = get_field( 'price_per_serving' );
						if ( $price_per_serving ) {
							echo '<p><strong>Price per serving:</strong> $' . esc_html( $price_per_serving ) . '</p>';
						}

						$amazon_rating = get_field( 'amazon_rating' );
						if ( $amazon_rating ) {
							echo '<p><strong>Amazon rating:</strong> ' . esc_html( $amazon_rating ) . '</p>';
						}

						?>
						
					</div>
					<?php


					$dosages = get_field( 'dosages' );
					if ( $dosages ) :
						?>
					<div class="ingredient-breakdown">
						<h3 class="ingredient-title">Ingredient Breakdown</h3>
						<table class="ingredient-table">
							<thead>
								<tr>
									<th>Ingredient</th>
									<th>Amount</th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $dosages as $row ) :
									$ingredient = $row['ingredient'];
									$amount     = $row['amount'];
									$unit       = $row['unit'];
									?>
									<tr>
										<td>
											<?php if ( $ingredient ) : ?>
											<a href="<?php echo esc_url( get_permalink( $ingredient ) ); ?>" class="ingredient-link">
												<?php echo esc_html( get_the_title( $ingredient ) ); ?>
											</a>
											<?php else : ?>
											<span class="text-muted italic">Unknown</span>
											<?php endif; ?>
										</td>
										<td>
											<?php
											if ( $amount ) {
												echo esc_html( $amount );
												if ( $unit ) {
													echo ' ' . esc_html( $unit );
												}
											} else {
												echo '<span class="text-muted italic">N/A</span>';
											}
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php endif; ?>

					<?php
					$taxonomy_keys = array( 'certification', 'dietary-tag', 'product-form' );
					foreach ( $taxonomy_keys as $tax ) {
						$terms = get_the_terms( get_the_ID(), $tax );
						if ( $terms && ! is_wp_error( $terms ) ) {
							echo '<div class="taxonomy-list">';
							echo '<strong class="taxonomy-label">' . str_replace( '-', ' ', $tax ) . ':</strong>';
							foreach ( $terms as $term ) {
								echo '<span class="taxonomy-pill">' . esc_html( $term->name ) . '</span>';
							}
							echo '</div>';
						}
					}
					?>
				</section>

				<!-- Affiliate -->
					<?php $affiliate = get_field( 'affiliate_url' ); ?>
					<?php if ( $affiliate ) : ?>
				<div class="affiliate-wrapper">
					<a href="<?php echo esc_url( $affiliate ); ?>" target="_blank" rel="nofollow noopener" class="affiliate-button">
						Buy on Amazon
					</a>
				</div>
				<?php endif; ?>
			</article>

			<?php
		endwhile;
	endif;
	?>
</main>

<?php get_footer(); ?>
