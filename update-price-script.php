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
					'updates' => get_option( 'supplement_update_data', array() ),
					'total'   => count( get_option( 'supplement_update_data', array() ) ),
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

	$updates     = array();
	$found_asins = array();

	foreach ( $supplements as $item ) {
		if ( empty( $item['asin'] ) ) {
			continue;
		}

		$asin  = trim( $item['asin'] );
		$query = new WP_Query(
			array(
				'post_type'      => 'supplement',
				'posts_per_page' => 1,
				'meta_query'     => array(
					array(
						'key'     => 'asin',
						'value'   => $asin,
						'compare' => '=',
					),
				),
			)
		);

		if ( $query->have_posts() ) {
			$post = $query->posts[0];

			$raw_new_price   = $item['price'];
			$clean_new_price = floatval( preg_replace( '/[^0-9\.]/', '', $raw_new_price ) );

			// Get servings_per_container.
			$servings = get_field( 'servings_per_container', $post->ID, );
			if ( $servings && is_numeric( $servings ) && $servings > 0 ) {
				$new_price_per_serving = round( $clean_new_price / $servings, 2 );
			} else {
				$new_price_per_serving = 0;
			}

			$updates[ $asin ] = array(
				'post_id'    => $post->ID,
				'post_title' => $post->post_title,
				'asin'       => $asin,
				'old_price'  => get_field( 'price', $post->ID ),
				'new_price'  => $clean_new_price,
				'old_pps'    => get_field( 'price_per_serving', $post->ID ),
				'new_pps'    => $new_price_per_serving,
				'old_rating' => get_field( 'amazon_rating', $post->ID ),
				'new_rating' => $item['rating'],
			);
			$found_asins[]    = $asin;
		}

		wp_reset_postdata();
	}

	if ( empty( $updates ) ) {
		echo '<div class="notice notice-warning"><p>No matching supplements found to update.</p></div>';
		show_upload_form();
		return;
	}

	update_option( 'supplement_update_data', $updates );

	echo '<h2>Preview Updates</h2>';
	echo '<table class="widefat"><thead><tr><th>Title</th><th>ASIN</th><th>Old Price</th><th>New Price</th><th>Old PPS</th><th>New PPS</th><th>Old Rating</th><th>New Rating</th></tr></thead><tbody>';
	foreach ( $updates as $update ) {
		echo '<tr>';
		echo '<td><a href="' . get_edit_post_link( $update['post_id'] ) . '" target="_blank">' . esc_html( $update['post_title'] ) . '</a></td>';
		echo '<td>' . esc_html( $update['asin'] ) . '</td>';
		echo '<td>' . esc_html( $update['old_price'] ) . '</td>';
		echo '<td>' . esc_html( $update['new_price'] ) . '</td>';
		echo '<td>' . esc_html( $update['old_pps'] ) . '</td>';
		echo '<td>' . esc_html( $update['new_pps'] ) . '</td>';
		echo '<td>' . esc_html( $update['old_rating'] ) . '</td>';
		echo '<td>' . esc_html( $update['new_rating'] ) . '</td>';
		echo '</tr>';
	}
	echo '</tbody></table>';

	// Dropdown for category
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

// AJAX endpoint for updating supplements
add_action(
	'wp_ajax_supplement_updater_update',
	function () {
		check_ajax_referer( 'supplement_updater_ajax', 'nonce' );

		$asin    = sanitize_text_field( $_POST['asin'] );
		$updates = get_option( 'supplement_update_data' );

		if ( ! isset( $updates[ $asin ] ) ) {
			wp_send_json_error( 'ASIN not found.' );
		}

		$update = $updates[ $asin ];

		update_field( 'price', $update['new_price'], $update['post_id'] );
		update_field( 'price_per_serving', $update['new_pps'], $update['post_id'] );
		update_field( 'amazon_rating', $update['new_rating'], $update['post_id'] );
		update_post_meta( $update['post_id'], 'last_price_update', current_time( 'mysql' ) );

		wp_send_json_success( 'Updated ' . $update['post_title'] );
	}
);

// AJAX endpoint for getting missing supplements
add_action(
	'wp_ajax_get_missing_supplements',
	function () {
		check_ajax_referer( 'supplement_updater_ajax', 'nonce' );

		$category_id   = sanitize_text_field( $_POST['category_id'] ); // can be 'all' or a number
		$updates       = get_option( 'supplement_update_data' );
		$updated_asins = is_array( $updates ) ? array_keys( $updates ) : array();

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

		$missing = array();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$asin = get_field( 'asin', $post->ID );
				if ( ! in_array( $asin, $updated_asins, true ) ) {
					$missing[] = array(
						'title'     => get_the_title( $post->ID ),
						'asin'      => $asin,
						'edit_link' => get_edit_post_link( $post->ID ),
					);
				}
			}
		}
		wp_reset_postdata();

		wp_send_json_success( $missing );
	}
);
