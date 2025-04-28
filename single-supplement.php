<?php get_header(); ?>

<main class="single-supplement-container">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			?>

			<a href="<?php echo get_post_type_archive_link( 'supplement' ); ?>" class="back-link inline-link">
			← Back to all supplements
			</a>

			<article class="supplement-article">
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
						<h1 class="supplement-title"><?php the_title(); ?></h1>
						
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

						?>
						
						<!-- Quick facts -->
						<div class="quick-facts">
							<?php

							$category = get_the_terms( get_the_ID(), 'supplement-category' );
							if ( ! empty( $category ) ) {
								echo '<p class="quick-fact">Category: <strong>' . esc_html( $category[0]->name ) . '</strong></p>';
							}

							$servings = get_field( 'servings_per_container' );
							if ( $servings ) {
								echo '<p class="quick-fact">Servings per container: <strong>' . esc_html( $servings ) . '</strong></p>';
							}

							$product_form_terms = get_the_terms( get_the_ID(), 'product-form' );
							if ( ! empty( $product_form_terms ) && ! is_wp_error( $product_form_terms ) ) {
								echo '<p class="quick-fact">Supplement form: <strong>' . esc_html( $product_form_terms[0]->name ) . '</strong></p>';
							}
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

						<!-- Amazon Link -->
						<?php $affiliate = get_field( 'affiliate_url' ); ?>
							<?php if ( $affiliate ) : ?>
						<div class="affiliate-wrapper">
							<a href="<?php echo esc_url( $affiliate ); ?>" target="_blank" rel="nofollow noopener" class="affiliate-button btn btn-primary">
								View on Amazon
							</a>
						</div>
						<?php endif; ?>

					</div>

				</header>

				<!-- Content -->
				<div class="supplement-content">
					<?php the_content(); ?>
				</div>

				<!-- Ingredients -->
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
											<span class="tooltip-wrapper">
												<a href="<?php echo esc_url( get_permalink( $ingredient ) ); ?>" class="ingredient-link inline-link ">
													<?php echo esc_html( get_the_title( $ingredient ) ); ?>
												</a>
												<span class="tooltip-text"><?php echo esc_html( get_the_excerpt( $ingredient ) ?: 'No description available' ); ?></span>
											</span>
											<?php
											if ( get_field( 'proprietary_blend', $ingredient ) ) {
												?>
													<span class="tooltip-wrapper">
														<span>⚠️</span>
														<span class="tooltip-text">Proprietary Blend: Exact ingredient amounts are not disclosed.</span>
													</span>

												<?php
											}
											?>
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

				<!-- OtherIngredients -->
				<?php
					$other_ingredients = get_field( 'other_ingredients' );
				if ( ! empty( $other_ingredients ) ) {
					?>
					<div class="other-ingredients">
						<h4>Other Ingredients</h4>
						<?php echo esc_html( $other_ingredients ); ?>
					</div>
					<?php
				}
				?>
					
				

				<!-- Affiliate -->
					<?php $affiliate = get_field( 'affiliate_url' ); ?>
					<?php if ( $affiliate ) : ?>
				<div class="affiliate-wrapper">
					<a href="<?php echo esc_url( $affiliate ); ?>" target="_blank" rel="nofollow noopener" class="affiliate-button btn btn-primary">
						View on Amazon
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
