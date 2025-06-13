<?php
// This REST endpoint proxies external image URLs (like supplement thumbnails)
// to bypass CORS restrictions when loading images from another origin (e.g., for drawing on canvas).
// It fetches the image server-side and streams it back with CORS-safe headers.

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'supppick/v1',
			'/proxy-image',
			array(
				'methods'             => 'GET',
				'callback'            => function ( $request ) {
					$url = esc_url_raw( $request->get_param( 'url' ) );

					if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
						return new WP_Error( 'invalid_url', 'Invalid image URL.', array( 'status' => 400 ) );
					}

					$response = wp_remote_get( $url );
					if ( is_wp_error( $response ) ) {
						return new WP_Error( 'fetch_failed', 'Could not fetch image.', array( 'status' => 500 ) );
					}

					$body = wp_remote_retrieve_body( $response );
					$type = wp_remote_retrieve_header( $response, 'content-type' );

					header( "Content-Type: $type" );
					header( 'Access-Control-Allow-Origin: *' ); // Or set to specific origin for security
					echo $body;
					exit;
				},
				'args'                => array(
					'url' => array( 'required' => true ),
				),
				'permission_callback' => '__return_true',
			)
		);
	}
);
