<?php
// Setup admin page
add_action(
	'admin_menu',
	function () {
		add_menu_page(
			'Supplement Info',
			'Supplement Info',
			'manage_options',
			'supplement-info-viewer',
			'render_product_info_viewer',
			'dashicons-info',
			61
		);
	}
);


function render_product_info_viewer() {
	?>
	<div class="wrap">
		<h1>Supplement Info Viewer</h1>
		<form method="get" style="margin-bottom:2rem">
			<input type="hidden" name="page" value="supplement-info-viewer">
			<label for="supplement_category">Filter by Category: </label>
			<select name="supplement_category" id="supplement_category" onchange="this.form.submit()">
				<option value="">All Categories</option>
				<?php
				$terms       = get_terms(
					array(
						'taxonomy'   => 'supplement-category',
						'hide_empty' => false,
					)
				);
				$current_cat = isset( $_GET['supplement_category'] ) ? sanitize_text_field( $_GET['supplement_category'] ) : '';
				foreach ( $terms as $term ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $term->slug ),
						selected( $current_cat, $term->slug, false ),
						esc_html( $term->name )
					);
				}
				?>
			</select>
		</form>

		
		<?php
		$args = array(
			'post_type'      => 'supplement',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);
		if ( ! empty( $current_cat ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'supplement-category',
					'field'    => 'slug',
					'terms'    => $current_cat,
				),
			);
		}

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) :
			?>
			<table class="widefat fixed striped sortable">
				<thead>
				<tr>
					<th><a href="#">Title</a></th>
					<th><a href="#">Brand</a></th>
					<th><a href="#">Last Update</a></th>
					<th><a href="#">Flavors</a></th>
				</tr>
				</thead>
				<tbody>
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();

					$supplement_id = get_the_ID();

					// brand
					$brands = get_the_terms( $supplement_id, 'brand' );

					// best variant
					$best_variant = get_best_variant_for_supplement( $supplement_id );

					$best_variant_id = $best_variant ? $best_variant->ID : null;
					// last update.
					$last_update = get_post_meta( $supplement_id, 'last_update_date', true ) . get_post_meta( $supplement_id, 'last_update_time', true );

					// variants list.
					$variant_ids   = get_field( 'variants', $supplement_id );
					$variants_list = '';

					if ( $variant_ids && is_array( $variant_ids ) ) {

						foreach ( $variant_ids as $variant_id ) {
							$flavor_name  = get_field( 'flavor_name', $variant_id );
							$variant_link = get_edit_post_link( $variant_id );

							$classes  = 'variant-link';
							$classes .= ( $variant_id === $best_variant_id ) ? ' active' : '';

							if ( $flavor_name && $variant_link ) {
								$variants_list .= '<a href="' . esc_url( $variant_link ) . '" class="' . esc_attr( $classes )
								. '">' . esc_html( $flavor_name ) . '</a>';
							}
						}
					}
					?>
					<tr>
						<td><a href="<?php echo esc_url( get_permalink() ); ?>" target="_blank"><?php the_title(); ?></a></td>
						<td><?php echo $brands ? esc_html( $brands[0]->name ) : '—'; ?></td>
					
						<td data-order="<?php echo esc_attr( $last_update ); ?>">
							<?php
							if ( ! empty( $last_update ) ) {
								$timestamp = strtotime( $last_update ); // Convert datetime to timestamp
								if ( $timestamp ) {
									echo esc_html( human_time_diff( $timestamp, current_time( 'timestamp' ) ) ) . ' ago';
								} else {
									echo '-';
								}
							} else {
								echo '-';
							}
							?>
						</td>

						<td class="variants-list"><?php echo $variants_list; ?></td>
					</tr>
				<?php endwhile; ?>
				</tbody>
			</table>

			<style>
				.sortable th {
					cursor: pointer;
				}
				th.sorted-asc:after {
					content: " 🔼";
				}
				th.sorted-desc:after {
					content: " 🔽";
				}

				.variants-list{
					display: flex;
					flex-wrap: wrap;
					gap:5px;
				}

				a.variant-link{
					border:1px solid #aaa;
					display:inline-block;
					padding-inline:3px;
					border-radius: 5px;
				}

				a.variant-link.active{
					background: #D4EDDA;
					font-weight: bold;
				}
			</style>

			<script>
			// Simple client-side sorting
			document.addEventListener('DOMContentLoaded', function() {
			document.querySelectorAll('.sortable th').forEach(function(header, index) {
				header.addEventListener('click', function() {
					const table = header.closest('table');
					const tbody = table.querySelector('tbody');
					const rows = Array.from(tbody.querySelectorAll('tr'));

					const isNumeric = index >= 4; // Last update is numeric timestamp
					const direction = header.dataset.sort === 'asc' ? 'desc' : 'asc';
					header.dataset.sort = direction;

					// Remove sorting icons from all headers
					document.querySelectorAll('.sortable th').forEach(function(header) {
						header.classList.remove('sorted-asc', 'sorted-desc');
					});

					// Add the sorting icon to the clicked column header
					if (direction === 'asc') {
						header.classList.add('sorted-asc');
					} else {
						header.classList.add('sorted-desc');
					}

					// Sort rows
					rows.sort(function(a, b) {
						let aText = a.children[index].dataset.order || a.children[index].innerText;
						let bText = b.children[index].dataset.order || b.children[index].innerText;

						if (isNumeric) {
							aText = parseInt(aText) || 0;
							bText = parseInt(bText) || 0;
						} else {
							aText = aText.toLowerCase();
							bText = bText.toLowerCase();
						}

						if (aText < bText) return direction === 'asc' ? -1 : 1;
						if (aText > bText) return direction === 'asc' ? 1 : -1;
						return 0;
					});

					// Append the sorted rows back to the table body
					rows.forEach(row => tbody.appendChild(row));
				});
			});
		});


			</script>
			<?php
		else :
			echo '<p>No products found.</p>';
		endif;
		wp_reset_postdata();
		?>
	</div>
	<?php
}
?>
