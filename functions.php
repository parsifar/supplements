<?php
/**
 * Functions
 *
 * @package SuppPick
 */

// Enqueue parent and child theme styles.
add_action( 'wp_enqueue_scripts', 'astra_child_enqueue_styles' );


function astra_child_enqueue_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
