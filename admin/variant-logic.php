<?php

/**
 * Retrieves the "best" variant for a given supplement based on custom sorting logic.
 *
 * The function selects the best variant by:
 * 1. Fetching all published variant posts related to the given supplement.
 * 2. Filtering to ensure each variant has a `price` and `last_update_date`.
 * 3. Sorting variants by `last_update_date` (most recent first), and then by `price` (lowest first)
 *    if update dates are equal.
 *
 * To improve performance, the result is cached in a transient (`best_variant_{supplement_id}`)
 * for 24 hours. If the transient exists, it returns the cached variant post directly.
 *
 * @param int $supplement_id The ID of the parent supplement post.
 * @return WP_Post|null The selected best variant post, or null if none found.
 */
function get_best_variant_for_supplement( $supplement_id ) {
	$transient_key = 'best_variant_' . $supplement_id;
	$cached        = get_transient( $transient_key );

	if ( $cached !== false ) {
		return get_post( $cached ); // Return the cached variant post
	}

	// First sort by last_update_date.
	$variants = get_posts(
		array(
			'post_type'      => 'variant',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => 'last_update_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_type'      => 'DATE',
			'meta_query'     => array(
				array(
					'key'     => 'parent_supplement',
					'value'   => '"' . $supplement_id . '"',
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'price',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'last_update_date',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	// Then sort by price if multiple share the same update date
	usort(
		$variants,
		function ( $a, $b ) {
			$dateA = get_field( 'last_update_date', $a->ID );
			$dateB = get_field( 'last_update_date', $b->ID );

			if ( $dateA === $dateB ) {
				$priceA = floatval( get_field( 'price', $a->ID ) );
				$priceB = floatval( get_field( 'price', $b->ID ) );
				return $priceA <=> $priceB; // Lower price first
			}

			// More recent date first
			return strcmp( $dateB, $dateA );
		}
	);

	$selected_variant = $variants[0] ?? null;

	if ( $selected_variant ) {

		set_transient( $transient_key, $selected_variant->ID, DAY_IN_SECONDS );
		return $selected_variant;
	}

	return null;
}


/**
 * Updates a supplement's key ACF fields and featured image based on its best variant.
 *
 * This function:
 * - Deletes the cached transient related to the best variant, forcing fresh evaluation.
 * - Retrieves the current best variant for the given supplement.
 * - If a best variant is found:
 *   - Updates the parent supplement's ACF fields (`price`, `price_per_serving`, `affiliate_url`)
 *     using values from the best variant.
 *   - Copies the featured image from the best variant to the parent supplement.
 *
 * @param int $supplement_id The ID of the parent supplement post to update.
 */
function update_best_variant_fields_from_function( $supplement_id ) {
	delete_transient( 'best_variant_' . $supplement_id );

	$is_protein = has_term( 'protein', 'supplement-category', $supplement_id );

	$best_variant = get_best_variant_for_supplement( $supplement_id );

	if ( ! $best_variant ) {
		return;
	}

	$best_variant_id = $best_variant->ID;

	// Update parent fields with variant data
	update_field( 'price', get_field( 'price', $best_variant_id ), $supplement_id );
	update_field( 'price_per_serving', get_field( 'price_per_serving', $best_variant_id ), $supplement_id );
	update_field( 'affiliate_url', get_field( 'affiliate_url', $best_variant_id ), $supplement_id );
	update_field( 'amazon_rating', get_field( 'amazon_rating', $best_variant_id ), $supplement_id );
	update_field( 'servings_per_container', get_field( 'servings_per_container', $best_variant_id ), $supplement_id );
	update_field( 'calories', get_field( 'calories', $best_variant_id ), $supplement_id );

	// If it's a Protein, update the protein specific fields on the parent.
	if ( $is_protein ) {
		update_field( 'total_carbohydrate', get_field( 'total_carbohydrate', $best_variant_id ), $supplement_id );
		update_field( 'total_fat', get_field( 'total_fat', $best_variant_id ), $supplement_id );
		update_field( 'cholesterol', get_field( 'cholesterol', $best_variant_id ), $supplement_id );
		update_field( 'protein_per_serving', get_field( 'protein_per_serving', $best_variant_id ), $supplement_id );
		update_field( 'calorie_protein_ratio', get_field( 'calorie_protein_ratio', $best_variant_id ), $supplement_id );
		update_field( 'protein_per_dollar', get_field( 'protein_per_dollar', $best_variant_id ), $supplement_id );
	}

	// Copy featured image
	$image_id = get_post_thumbnail_id( $best_variant_id );

	if ( $image_id ) {
		// Update the parent supplement's featured image if $image_id is valid
		set_post_thumbnail( $supplement_id, $image_id );
	}
}

/**
 * Hook into the saving of a "variant" post to:
 *  - Invalidate the cached best variant transient for each linked parent supplement.
 *  - Recalculate and update relevant fields (price, price per serving, affiliate URL, featured image)
 *    on the parent supplement using the selected best variant.
 *
 * Enhancements:
 *  - Skips execution during autosave, AJAX, REST, or WP-CLI to avoid unintended updates.
 *  - Prevents duplicate processing of the same parent supplement in bulk updates by using a static cache.
 *
 * This ensures that archive-level filters and sorting (which rely on parent-level ACF fields)
 * reflect the latest data from the current best variant after any variant is saved.
 */
add_action(
	'acf/save_post',
	function ( $post_id ) {
		if ( get_post_type( $post_id ) !== 'variant' ) {
			return;
		}

		// Avoid running on autosave or ACF field group saves.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$parent = get_field( 'parent_supplement', $post_id );

		if ( $parent && is_array( $parent ) ) {
			foreach ( $parent as $supplement ) {
				delete_transient( 'best_variant_' . $supplement->ID );

				$best_variant = get_best_variant_for_supplement( $supplement->ID );
				if ( $best_variant ) {
					update_best_variant_fields_from_function( $supplement->ID );
				}
			}
		}
	},
	99
);





/**
 * Clears all cached best variant transients and updates cached fields for supplements.
 *
 * This function retrieves all published supplements and:
 *  - Deletes the 'best_variant_{supplement_id}' transient cache for each.
 *  - Recomputes and updates the cached variant-related fields on the parent supplement,
 *    such as price, price per serving, affiliate URL, and featured image.
 *
 * This is useful after bulk data updates, imports, or changes to the variant selection logic,
 * ensuring all supplement posts reflect up-to-date best variant data.
 */
function clear_all_best_variant_cache() {
	$supplements = get_posts(
		array(
			'post_type'      => 'supplement',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $supplements as $id ) {
		delete_transient( 'best_variant_' . $id );
		update_best_variant_fields_from_function( $id );
	}
}


/**
 * Registers a submenu page under "Tools" in the WordPress admin dashboard
 * to allow admins to manually refresh the best variant cache.
 */
add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'tools.php',
			'Refresh Best variant Cache',    // Page title
			'Refresh variant Cache',         // Menu title
			'manage_options',               // Capability required
			'refresh-best-variant-cache',    // Menu slug
			'render_refresh_variant_cache_page' // Callback to render the page
		);
	}
);

/**
 * Renders the admin page for refreshing the best variant cache.
 * If the form is submitted and nonce is valid, it clears all cached best variants
 * and updates related fields on the parent supplement posts.
 */
function render_refresh_variant_cache_page() {
	// Handle form submission and nonce verification.
	if ( isset( $_POST['refresh_variant_cache'] ) && check_admin_referer( 'refresh_variant_cache_action', 'refresh_variant_cache_nonce' ) ) {
		clear_all_best_variant_cache();
		echo '<div class="notice notice-success"><p>variant cache cleared and updated for all supplements.</p></div>';
	}
	?>

	<div class="wrap">
		<h1>Refresh Best variant Cache</h1>
		<p>This tool clears the cached best variant for all supplements and updates their cached fields (price, PPS, etc).</p>
		<form method="post">
			<?php wp_nonce_field( 'refresh_variant_cache_action', 'refresh_variant_cache_nonce' ); ?>
			<?php submit_button( 'Run Cache Refresh' ); ?>
		</form>
	</div>

	<?php
}
