<?php

add_action(
	'admin_menu',
	function () {
		add_menu_page( 'Amazon HTML Parser', 'Amazon Parser', 'manage_options', 'amazon-html-parser', 'amazon_html_parser_page' );
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

				$nodes = $xpath->query( "//div[contains(@class, 's-result-item') and @data-asin]" );

				foreach ( $nodes as $node ) {
					$asin = $node->getAttribute( 'data-asin' );
					if ( ! $asin || isset( $products[ $asin ] ) ) {
						continue; // skip empty or duplicate asins
					}

					// Title
					$titleNode = $xpath->query( './/h2//span', $node );
					$title     = $titleNode->length ? trim( html_entity_decode( $titleNode->item( 0 )->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) : '';

					// Rating
					$ratingNode = $xpath->query( ".//span[contains(@class, 'a-size-small') and contains(@class, 'a-color-base')]", $node );
					$rating     = $ratingNode->length ? trim( $ratingNode->item( 0 )->textContent ) : '';

					// Price
					$wholePriceNode    = $xpath->query( ".//span[contains(@class,'a-price-whole')]", $node );
					$fractionPriceNode = $xpath->query( ".//span[contains(@class,'a-price-fraction')]", $node );
					$price             = '';
					if ( $wholePriceNode->length ) {
						$price = str_replace( array( '.', ',' ), '', trim( $wholePriceNode->item( 0 )->textContent ) ); // remove any commas or periods in whole part
						if ( $fractionPriceNode->length ) {
							$price .= '.' . trim( $fractionPriceNode->item( 0 )->textContent );
						}
					}

					// Only add if price and rating are present
					if ( ! empty( $price ) && ! empty( $rating ) ) {
						$products[ $asin ] = array(
							'asin'   => $asin,
							'title'  => $title,
							'price'  => $price,
							'rating' => $rating,
						);
					}
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
