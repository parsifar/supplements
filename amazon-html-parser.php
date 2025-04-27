<?php

add_action(
	'admin_menu',
	function () {
		add_menu_page(
			'Amazon HTML Parser',
			'Amazon Parser',
			'manage_options',
			'amazon-html-parser',
			'amazon_html_parser_page',
			'dashicons-amazon',
			60
		);
	}
);

function amazon_html_parser_page() {
	?>
	<div class="wrap">
		<h1>Amazon HTML Parser</h1>
		<form method="post" enctype="multipart/form-data">
			<input type="file" name="html_files[]" multiple accept=".html,.htm">
			<?php submit_button( 'Parse HTML' ); ?>
		</form>

		<?php
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! empty( $_FILES['html_files'] ) ) {
			$products = array();

			foreach ( $_FILES['html_files']['tmp_name'] as $tmp_name ) {
				$html = file_get_contents( $tmp_name );

				libxml_use_internal_errors( true );
				$dom = new DOMDocument();
				@$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
				$xpath = new DOMXPath( $dom );

				// Find all elements with a data-asin
				$nodes = $xpath->query( "//div[@data-asin and @data-asin != '']" );

				foreach ( $nodes as $node ) {
					$asin = $node->getAttribute( 'data-asin' );
					if ( ! $asin || isset( $products[ $asin ] ) ) {
						continue;
					}

					// Try getting title
					$titleNode = $xpath->query( './/h2//span', $node );
					if ( $titleNode->length ) {
						$title = trim( html_entity_decode( $titleNode->item( 0 )->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
					} else {
						// Try Best Seller structure
						$titleNode = $xpath->query( ".//div[contains(@class, 'p13n-sc-css-line-clamp-3')]", $node );
						$title     = $titleNode->length ? trim( html_entity_decode( $titleNode->item( 0 )->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) : '';
					}

					// Try getting rating
					$ratingNode = $xpath->query( ".//span[contains(@class, 'a-size-small') and contains(@class, 'a-color-base')]", $node );
					if ( $ratingNode->length ) {
						$rating = trim( $ratingNode->item( 0 )->textContent );
					} else {
						// Try Best Seller structure
						$ratingNode = $xpath->query( ".//span[contains(@class, 'a-icon-alt')]", $node );
						if ( $ratingNode->length ) {
							$rawRating = trim( $ratingNode->item( 0 )->textContent );
							// Extract only the numeric part (e.g., "4.3" from "4.3 out of 5 stars")
							if ( preg_match( '/[\d.]+/', $rawRating, $matches ) ) {
								$rating = $matches[0];
							} else {
								$rating = '';
							}
						} else {
							$rating = '';
						}
					}

					// Try getting price
					$wholePriceNode    = $xpath->query( ".//span[contains(@class,'a-price-whole')]", $node );
					$fractionPriceNode = $xpath->query( ".//span[contains(@class,'a-price-fraction')]", $node );
					if ( $wholePriceNode->length ) {
						$price = trim( str_replace( ',', '', $wholePriceNode->item( 0 )->textContent ) ); // Remove commas
						if ( $fractionPriceNode->length ) {
							$price .= '.' . trim( $fractionPriceNode->item( 0 )->textContent );
						}
					} else {
						// Try Best Seller structure
						$priceNode = $xpath->query( ".//span[contains(@class, 'p13n-sc-price')]", $node );
						$price     = $priceNode->length ? trim( str_replace( array( '$', ',' ), '', $priceNode->item( 0 )->textContent ) ) : '';
					}

					// Clean up price (fix cases like 44..99)
					$price = preg_replace( '/\.+/', '.', $price ); // Replace multiple dots with one

					// Skip if no price or no rating
					if ( empty( $price ) || empty( $rating ) ) {
						continue;
					}

					$products[ $asin ] = array(
						'asin'   => $asin,
						'title'  => $title,
						'price'  => $price,
						'rating' => $rating,
					);
				}
			}

			if ( ! empty( $products ) ) {
				$json        = json_encode( array_values( $products ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
				$json_base64 = base64_encode( $json );

				// Show preview
				echo '<h2>Preview (' . count( $products ) . ' items)</h2>';
				echo '<table class="widefat fixed striped">';
				echo '<thead><tr><th>ASIN</th><th>Title</th><th>Price</th><th>Rating</th></tr></thead><tbody>';

				foreach ( $products as $product ) {
					echo '<tr>';
					echo '<td>' . esc_html( $product['asin'] ) . '</td>';
					echo '<td>' . esc_html( $product['title'] ) . '</td>';
					echo '<td>' . esc_html( $product['price'] ) . '</td>';
					echo '<td>' . esc_html( $product['rating'] ) . '</td>';
					echo '</tr>';
				}
				echo '</tbody></table>';

				// Add download button
				echo '<br><button id="download-json" class="button button-primary">Download JSON File</button>';

				// Output hidden JSON for download
				echo '<script>
					document.getElementById("download-json").addEventListener("click", function() {
						const data = "' . $json_base64 . '";
						const blob = new Blob([atob(data)], { type: "application/json" });
						const url = URL.createObjectURL(blob);
						const a = document.createElement("a");
						a.href = url;
						a.download = "amazon_products_' . time() . '.json";
						a.click();
						URL.revokeObjectURL(url);
					});
				</script>';
			} else {
				echo '<div class="notice notice-warning"><p>No products with price and rating found.</p></div>';
			}
		}
		?>
	</div>
	<?php
}
?>
