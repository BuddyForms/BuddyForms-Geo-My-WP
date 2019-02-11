<?php

// Require all needed files
require( 'shortcodes/shortcodes-to-blocks.php' );

define('IS_ADMIN', false);




gmw_enqueue_scripts();

add_action( 'admin_enqueue_scripts', 'buddyforms_gmw_enqueue_scripts', '999' );


function buddyforms_gmw_enqueue_scripts(){
	$map_scripts      = array( 'jquery', 'gmw' );

	// include GMW main stylesheet
	wp_enqueue_style( 'gmw-frontend', GMW_URL . '/assets/css/gmw.frontend.min.css', array(), GMW_VERSION );

	// Map script.
	wp_register_script( 'gmw-map', GMW_URL . '/assets/js/gmw.map.min.js', $map_scripts, GMW_VERSION, true );

	// load styles in head
	$form_styles = apply_filters( 'gmw_load_form_styles_in_head', array() );

	// load form stylesheets early
	if ( ! empty( $form_styles ) ) {
		foreach ( $form_styles as $form_style ) {
			gmw_enqueue_form_styles( $form_style );
		}
	}
}
