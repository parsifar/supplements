<?php

// Setup admin page
add_action(
	'admin_menu',
	function () {
		add_menu_page(
			'Supplement Updater',
			'Supplement Updater',
			'manage_options',
			'supplement-updater',
			'render_supplement_updater_page',
			'dashicons-update',
			60
		);
	}
);

// Enqueue necessary scripts
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( $hook === 'toplevel_page_supplement-updater' ) {
			wp_enqueue_script( 'supplement-updater', get_stylesheet_directory_uri() . '/src/admin/supplement-updater.js', array( 'jquery' ), null, true );
			wp_localize_script(
				'supplement-updater',
				'supplementUpdater',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'supplement_updater_ajax' ),
				)
			);
			wp_enqueue_style( 'supplement-updater-style', get_stylesheet_directory_uri() . '/src/admin/supplement-updater.css' );
		}
	}
);

// Render the page
function render_supplement_updater_page() {
	?>
	<div class="wrap">
		<h1>Supplement Updater</h1>

		<div id="supplement-updater-content">
			<?php
			if ( isset( $_POST['cancel_update'] ) ) {
				delete_option( 'supplement_update_data' );
				delete_option( 'variant_update_data' );
				echo '<div class="notice notice-warning"><p>Update canceled.</p></div>';
				show_upload_form();
			} elseif ( isset( $_FILES['supplement_json'] ) && check_admin_referer( 'supplement_updater_action', 'supplement_updater_nonce' ) ) {
				analyze_uploaded_json( $_FILES['supplement_json'] );
			} else {
				show_upload_form();
			}
			?>
		</div>
	</div>
	<?php
}

// Upload form
function show_upload_form() {
	?>
	<form method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'supplement_updater_action', 'supplement_updater_nonce' ); ?>
		<input type="file" name="supplement_json" accept=".json" required>
		<p><input type="submit" class="button button-primary" value="Upload and Analyze"></p>
	</form>
	<?php
}

// Analyze uploaded JSON
function analyze_uploaded_json( $file ) {
	if ( $file['error'] !== UPLOAD_ERR_OK ) {
		echo '<div class="notice notice-error"><p>Upload failed. Please try again.</p></div>';
		show_upload_form();
		return;
	}

	$json        = file_get_contents( $file['tmp_name'] );
	$supplements = json_decode( $json, true );

	if ( empty( $supplements ) || ! is_array( $supplements ) ) {
		echo '<div class="notice notice-error"><p>Invalid JSON file.</p></div>';
		show_upload_form();
		return;
	}

	$supplement_updates = array();
	$variant_updates    = array();
	$found_asins        = array();

	// for each item in the json file find the variant and its parent and populate the update arrays
	foreach ( $supplements as $item ) {
		if ( empty( $item['asin'] ) ) {
			continue;
		}

		// get the asin, price and rating of the current item in the json.
		$asin            = trim( $item['asin'] );
		$new_price       = floatval( $item['price'] ?? 0 );
		$clean_new_price = floatval( preg_replace( '/[^0-9\.]/', '', $new_price ) );
		$new_rating      = floatval( $item['rating'] ?? 0 );

		// find the variant post with this asin.
		$variant_query = new WP_Query(
			array(
				'post_type'           => 'variant',
				'posts_per_page'      => 1,
				'ignore_sticky_posts' => true,
				'meta_query'          => array(
					array(
						'key'     => 'asin',
						'value'   => $asin,
						'compare' => '=',
					),
				),
			)
		);

		// if found the variant with the same asin.
		if ( $variant_query->have_posts() ) {
			$variant    = $variant_query->posts[0];
			$variant_id = $variant->ID;

			// get the variants parent.
			$parent    = get_field( 'parent_supplement', $variant_id )[0];
			$parent_id = $parent->ID ?? null;

			// get the servings and old price.
			$servings   = floatval( get_field( 'servings_per_container', $variant_id ) );
			$old_price  = floatval( get_field( 'price', $variant_id ) );
			$old_pps    = floatval( get_field( 'price_per_serving', $variant_id ) );
			$old_rating = get_field( 'amazon_rating', $variant_id );

			// calculate new pps.
			$new_pps = $servings > 0 ? round( $clean_new_price / $servings, 2 ) : 0;

			// add the current variant data to the $variant_updates array.
			$variant_updates[ $variant_id ] = array(
				'post_id'    => $variant_id,
				'post_title' => $variant->post_title,
				'asin'       => $asin,
				'old_price'  => $old_price,
				'new_price'  => $clean_new_price,
				'old_pps'    => $old_pps,
				'new_pps'    => $new_pps,
				'old_rating' => $old_rating,
				'new_rating' => $new_rating,
			);

			// add the current variant asin to the $found_asins array.
			$found_asins[] = $asin;

			// Check if parent is a protein supplement and calculate protein per dollar
			if ( $parent_id ) {
				$is_protein  = has_term( 'protein', 'supplement-category', $parent_id );
				$update_data = array(
					'post_id'    => $parent_id,
					'post_title' => $parent->post_title,
				);

				if ( $is_protein ) {
					$protein_per_serving = floatval( get_field( 'protein_per_serving', $parent_id ) );
					$protein_per_dollar  = $protein_per_serving > 0 ? round( $protein_per_serving / $new_pps, 2 ) : 0;

					// Only add protein_per_dollar if it's a valid number
					if ( is_numeric( $protein_per_dollar ) && $protein_per_dollar > 0 ) {
						$update_data['protein_per_dollar'] = $protein_per_dollar;
					}
				}

				// Add the parent supplement to updates
				$supplement_updates[ $parent_id ] = $update_data;
			}
		}

		wp_reset_postdata();
	}

	if ( empty( $variant_updates ) || empty( $supplement_updates ) ) {
		echo '<div class="notice notice-warning"><p>No matching supplements found to update.</p></div>';
		show_upload_form();
		return;
	}

	// store the information about the variant and supplement updates in the options table
	update_option( 'supplement_update_data', $supplement_updates );
	update_option( 'variant_update_data', $variant_updates );

	// Preview tables
	echo '<h2>Preview Updates</h2>';

	// variants preview table
	echo '<h3>Variant Updates</h3>';
	echo '<table class="widefat"><thead><tr><th>Title</th><th>ASIN</th><th>Old Price</th><th>New Price</th><th>Old PPS</th><th>New PPS</th></tr></thead><tbody>';
	foreach ( $variant_updates as $update ) {
		echo '<tr>';
		echo '<td><a href="' . get_edit_post_link( $update['post_id'] ) . '" target="_blank">' . esc_html( $update['post_title'] ) . '</a></td>';
		echo '<td>' . esc_html( $update['asin'] ) . '</td>';
		echo '<td>' . esc_html( $update['old_price'] ) . '</td>';
		echo '<td>' . esc_html( $update['new_price'] ) . '</td>';
		echo '<td>' . esc_html( $update['old_pps'] ) . '</td>';
		echo '<td>' . esc_html( $update['new_pps'] ) . '</td>';
		echo '</tr>';
	}
	echo '</tbody></table>';

	// Supplement preview table
	echo '<h3>Supplement Updates</h3>';
	echo '<table class="widefat"><thead><tr><th>Title</th><th>Last updated</th><th>Old Protein/$</th><th>New Protein/$</th></tr></thead><tbody>';
	foreach ( $supplement_updates as $update ) {
		echo '<tr>';
		echo '<td><a href="' . get_edit_post_link( $update['post_id'] ) . '" target="_blank">' . esc_html( $update['post_title'] ) . '</a></td>';
		echo '<td>' . esc_html( get_field( 'last_update_date', $update['post_id'] ) ) . '</td>';

		// Show protein per dollar values if it's a protein supplement
		if ( has_term( 'protein', 'supplement-category', $update['post_id'] ) ) {
			$old_protein_per_dollar = get_field( 'protein_per_dollar', $update['post_id'] );
			echo '<td>' . esc_html( $old_protein_per_dollar ) . '</td>';
			echo '<td>' . esc_html( $update['protein_per_dollar'] ?? '' ) . '</td>';
		} else {
			echo '<td>-</td><td>-</td>';
		}

		echo '</tr>';
	}
	echo '</tbody></table>';

	// Dropdown for category.
	$terms = get_terms(
		array(
			'taxonomy'   => 'supplement-category',
			'hide_empty' => false,
		)
	);

	if ( ! empty( $terms ) ) {
		echo '<h2>Supplements not in JSON (by Category)</h2>';
		echo '<select id="supplement-category-dropdown">';
		echo '<option value="">Select a Category</option>';
		echo '<option value="all">All Categories</option>';
		foreach ( $terms as $term ) {
			echo '<option value="' . esc_attr( $term->term_id ) . '">' . esc_html( $term->name ) . '</option>';
		}
		echo '</select>';
		echo '<div id="missing-supplements-table" style="margin-top:20px;"></div>';
	}

	// Buttons
	?>
	<form method="post">
		<?php wp_nonce_field( 'confirm_supplement_update', 'supplement_updater_nonce' ); ?>
		<p>
			<button type="button" id="confirm-update" class="button button-primary">Confirm and Update</button>
			<input type="submit" name="cancel_update" class="button" value="Cancel">
		</p>
	</form>

	<div id="progress-wrapper" style="margin-top:30px;display:none;">
		<h2>Updating...</h2>
		<div id="progress-bar" style="background:#ccc;width:100%;height:30px;position:relative;">
			<div id="progress-fill" style="background:#0073aa;width:0;height:100%;transition:width 0.3s;"></div>
			<div id="progress-text" style="position:absolute;width:100%;text-align:center;top:0;line-height:30px;color:white;">0%</div>
		</div>
	</div>
	<?php
}

// AJAX endpoint for updating variants
add_action(
	'wp_ajax_variant_updater_update',
	function () {
		check_ajax_referer( 'supplement_updater_ajax', 'nonce' );

		$variant_id = sanitize_text_field( $_POST['variant_id'] );
		$updates    = get_option( 'variant_update_data' );

		if ( ! isset( $updates[ $variant_id ] ) ) {
			wp_send_json_error( 'variant_id not found.' );
		}

		$update = $updates[ $variant_id ];

		update_field( 'price', $update['new_price'], $variant_id );
		update_field( 'price_per_serving', $update['new_pps'], $variant_id );
		update_field( 'amazon_rating', $update['new_rating'], $variant_id );
		update_post_meta( $variant_id, 'last_update_date', current_time( 'Y-m-d' ) );
		update_post_meta( $variant_id, 'last_update_time', current_time( 'H:i:s' ) );

		wp_send_json_success( 'Updated ' . $update['post_title'] );
	}
);
// AJAX endpoint for updating supplements
add_action(
	'wp_ajax_supplement_updater_update',
	function () {
		check_ajax_referer( 'supplement_updater_ajax', 'nonce' );

		$supplement_id = sanitize_text_field( $_POST['supplement_id'] );
		$updates       = get_option( 'supplement_update_data' );

		if ( ! isset( $updates[ $supplement_id ] ) ) {
			wp_send_json_error( 'supplement_id not found.' );
		}

		$update = $updates[ $supplement_id ];

		update_post_meta( $supplement_id, 'last_update_date', current_time( 'Y-m-d' ) );
		update_post_meta( $supplement_id, 'last_update_time', current_time( 'H:i:s' ) );

		// Update protein_per_dollar if it exists in the updates
		if ( isset( $update['protein_per_dollar'] ) ) {
			update_field( 'protein_per_dollar', $update['protein_per_dollar'], $supplement_id );
		}

		// reset the best variant of this supplement
		update_best_variant_fields_from_function( $supplement_id );

		wp_send_json_success( 'Updated ' . $update['post_title'] );
	}
);

// AJAX endpoint for getting missing supplements
add_action(
	'wp_ajax_get_missing_supplements',
	function () {
		check_ajax_referer( 'supplement_updater_ajax', 'nonce' );

		$category_id            = sanitize_text_field( $_POST['category_id'] ); // can be 'all' or a number
		$updates                = get_option( 'supplement_update_data' );
		$updated_supplement_ids = is_array( $updates ) ? array_keys( $updates ) : array();

		// Prepare the query args
		$args = array(
			'post_type'      => 'supplement',
			'posts_per_page' => -1,
		);

		// If not "all", filter by selected category
		if ( 'all' !== $category_id ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'supplement-category',
					'field'    => 'term_id',
					'terms'    => intval( $category_id ),
				),
			);
		}

		$query = new WP_Query( $args );

		$missing_supplements = array();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$supplement_id = $post->ID;
				if ( ! in_array( $supplement_id, $updated_supplement_ids, true ) ) {
					$missing_supplements[] = array(
						'title'            => get_the_title( $supplement_id ),
						'edit_link'        => get_edit_post_link( $supplement_id ),
						'last_update_date' => get_field( 'last_update_date', $supplement_id ),
					);
				}
			}
		}
		wp_reset_postdata();

		wp_send_json_success( $missing_supplements );
	}
);

// AJAX endpoint for the JS to  get the update info of the variants and supplements
add_action(
	'wp_ajax_get_supplement_update_data',
	function () {
		check_ajax_referer( 'supplement_updater_ajax', 'nonce' );

		$variant_updates    = get_option( 'variant_update_data', array() );
		$supplement_updates = get_option( 'supplement_update_data', array() );

		wp_send_json_success(
			array(
				'variant_updates'    => $variant_updates,
				'supplement_updates' => $supplement_updates,
			)
		);
	}
);
