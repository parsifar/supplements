<?php get_header(); ?>

<main class="single-ingredient-container">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			?>

			

			<article class="ingredient-article">
				

				<!-- Content -->
				<div class="ingredient-content">
					<?php the_content(); ?>
				</div>

				

				
			</article>

			<?php
			// Top supplements containing the ingredient.
			$ingredient_id = get_the_ID();

			// Set cache key
			$cache_key       = 'top_supplements_for_ingredient_' . $ingredient_id;
			$top_supplements = get_transient( $cache_key );

			if ( $top_supplements === false ) {
				// Query all supplements.
				$supplements = get_posts(
					array(
						'post_type'      => 'supplement',
						'posts_per_page' => -1,
						'post_status'    => 'publish',
						'fields'         => 'ids',
					)
				);

				$supplement_amounts = array();

				// Loop through supplements to find ones using this ingredient.
				foreach ( $supplements as $supplement_id ) {
					if ( have_rows( 'dosages', $supplement_id ) ) {
						while ( have_rows( 'dosages', $supplement_id ) ) {
							the_row();
							$dosage_ingredient = get_sub_field( 'ingredient' )->ID;
							$amount            = get_sub_field( 'amount' );

							if ( $dosage_ingredient && intval( $dosage_ingredient ) === $ingredient_id ) {
								$supplement_amounts[ $supplement_id ] = floatval( $amount );
								break; // Only count once per supplement.
							}
						}
					}
				}

				// Sort supplements by amount descending.
				arsort( $supplement_amounts );

				// Get top 3
				$top_ids = array_slice( array_keys( $supplement_amounts ), 0, 3, true );

				// Store post objects.
				$top_supplements = get_posts(
					array(
						'post_type'   => 'supplement',
						'post__in'    => $top_ids,
						'orderby'     => 'post__in', // Keep same order
						'numberposts' => 3,
					)
				);

				// Cache for 24 hours.
				set_transient( $cache_key, $top_supplements, 12 * HOUR_IN_SECONDS );
			}

			// Render section.
			if ( ! empty( $top_supplements ) ) :
				?>
				<section class="top-supplements">
					<h2 class="section-title">Supplements with the most <?php the_title(); ?> per serving</h2>
					<div class="supplement-list">
									<?php
									foreach ( $top_supplements as $supplement ) :
										global $post;
										$post = $supplement;
										setup_postdata( $post ); // Set global $post
										get_template_part( 'template-parts/supplement-card' );
									endforeach;
									wp_reset_postdata();
									?>
					</div>
				</section>
				<?php
			endif;

			endwhile;
			endif;
	?>
</main>

<?php get_footer(); ?>
