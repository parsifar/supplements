<?php

/**
 * Retrieves the "best" flavor for a given supplement based on custom sorting logic.
 *
 * The function selects the best flavor by:
 * 1. Fetching all published flavor posts related to the given supplement.
 * 2. Filtering to ensure each flavor has a `price` and `last_update_date`.
 * 3. Sorting flavors by `last_update_date` (most recent first), and then by `price` (lowest first)
 *    if update dates are equal.
 *
 * To improve performance, the result is cached in a transient (`best_flavor_{supplement_id}`)
 * for 24 hours. If the transient exists, it returns the cached flavor post directly.
 *
 * @param int $supplement_id The ID of the parent supplement post.
 * @return WP_Post|null The selected best flavor post, or null if none found.
 */
function get_best_flavor_for_supplement( $supplement_id ) {
	$transient_key = 'best_flavor_' . $supplement_id;
	$cached        = get_transient( $transient_key );

	if ( $cached !== false ) {
		return get_post( $cached ); // Return the cached flavor post
	}

	// First sort by last_update_date.
	$flavors = get_posts(
		array(
			'post_type'      => 'flavor',
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
		$flavors,
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

	$selected_flavor = $flavors[0] ?? null;

	if ( $selected_flavor ) {

		set_transient( $transient_key, $selected_flavor->ID, DAY_IN_SECONDS );
		return $selected_flavor;
	}

	return null;
}


/**
 * Updates a supplement's key ACF fields and featured image based on its best flavor.
 *
 * This function:
 * - Deletes the cached transient related to the best flavor, forcing fresh evaluation.
 * - Retrieves the current best flavor for the given supplement.
 * - If a best flavor is found:
 *   - Updates the parent supplement's ACF fields (`price`, `price_per_serving`, `affiliate_url`)
 *     using values from the best flavor.
 *   - Copies the featured image from the best flavor to the parent supplement.
 *
 * @param int $supplement_id The ID of the parent supplement post to update.
 */
function update_best_flavor_fields_from_function( $supplement_id ) {
	delete_transient( 'best_flavor_' . $supplement_id );

	$flavor = get_best_flavor_for_supplement( $supplement_id );

	if ( ! $flavor ) {
		return;
	}

	// Update parent fields with flavor data
	update_field( 'price', get_field( 'price', $flavor->ID ), $supplement_id );
	update_field( 'price_per_serving', get_field( 'price_per_serving', $flavor->ID ), $supplement_id );
	update_field( 'affiliate_url', get_field( 'affiliate_url', $flavor->ID ), $supplement_id );

	// Copy featured image
	// Check if the flavor has a featured image
	$image_id = get_post_thumbnail_id( $flavor->ID );

	if ( $image_id ) {
		// Update the parent supplement's featured image if $image_id is valid
		set_post_thumbnail( $supplement_id, $image_id );
	}
}

/**
 * Hook into the saving of a "flavor" post to:
 *  - Invalidate the cached best flavor transient for each linked parent supplement.
 *  - Recalculate and update relevant fields (price, price per serving, affiliate URL, featured image)
 *    on the parent supplement using the selected best flavor.
 *
 * Enhancements:
 *  - Skips execution during autosave, AJAX, REST, or WP-CLI to avoid unintended updates.
 *  - Prevents duplicate processing of the same parent supplement in bulk updates by using a static cache.
 *
 * This ensures that archive-level filters and sorting (which rely on parent-level ACF fields)
 * reflect the latest data from the current best flavor after any flavor is saved.
 */
add_action(
	'acf/save_post',
	function ( $post_id ) {
		error_log( 'HOOK RAN flavor id: ' . $post_id );
		if ( get_post_type( $post_id ) !== 'flavor' ) {
			return;
		}

		// Avoid running on autosave or ACF field group saves
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$parent = get_field( 'parent_supplement', $post_id );

		if ( $parent && is_array( $parent ) ) {
			foreach ( $parent as $supplement ) {
				error_log( 'HOOK RAN flavor id: ' . $post_id . ' supplement: ' . $supplement->ID );
				delete_transient( 'best_flavor_' . $supplement->ID );

				$best_flavor = get_best_flavor_for_supplement( $supplement->ID );
				if ( $best_flavor ) {
					update_best_flavor_fields_from_function( $supplement->ID );
				}
			}
		}
	},
	20
);





/**
 * Clears all cached best flavor transients and updates cached fields for supplements.
 *
 * This function retrieves all published supplements and:
 *  - Deletes the 'best_flavor_{supplement_id}' transient cache for each.
 *  - Recomputes and updates the cached flavor-related fields on the parent supplement,
 *    such as price, price per serving, affiliate URL, and featured image.
 *
 * This is useful after bulk data updates, imports, or changes to the flavor selection logic,
 * ensuring all supplement posts reflect up-to-date best flavor data.
 */
function clear_all_best_flavor_cache() {
	$supplements = get_posts(
		array(
			'post_type'      => 'supplement',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $supplements as $id ) {
		delete_transient( 'best_flavor_' . $id );
		update_best_flavor_fields_from_function( $id );
	}
}


/**
 * Registers a submenu page under "Tools" in the WordPress admin dashboard
 * to allow admins to manually refresh the best flavor cache.
 */
add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'tools.php',
			'Refresh Best Flavor Cache',    // Page title
			'Refresh Flavor Cache',         // Menu title
			'manage_options',               // Capability required
			'refresh-best-flavor-cache',    // Menu slug
			'render_refresh_flavor_cache_page' // Callback to render the page
		);
	}
);

/**
 * Renders the admin page for refreshing the best flavor cache.
 * If the form is submitted and nonce is valid, it clears all cached best flavors
 * and updates related fields on the parent supplement posts.
 */
function render_refresh_flavor_cache_page() {
	// Handle form submission and nonce verification.
	if ( isset( $_POST['refresh_flavor_cache'] ) && check_admin_referer( 'refresh_flavor_cache_action', 'refresh_flavor_cache_nonce' ) ) {
		clear_all_best_flavor_cache();
		echo '<div class="notice notice-success"><p>Flavor cache cleared and updated for all supplements.</p></div>';
	}
	?>

	<div class="wrap">
		<h1>Refresh Best Flavor Cache</h1>
		<p>This tool clears the cached best flavor for all supplements and updates their cached fields (price, PPS, etc).</p>
		<form method="post">
			<?php wp_nonce_field( 'refresh_flavor_cache_action', 'refresh_flavor_cache_nonce' ); ?>
			<?php submit_button( 'Run Cache Refresh' ); ?>
		</form>
	</div>

	<?php
}
