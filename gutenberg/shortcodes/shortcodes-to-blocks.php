<?php
/**
 * Create Blocks from Shortcodes
 *
 * @since 2.3.1
 *
 */
function buddyforms_gmw_shortcodes_to_block_init() {
	global $buddyforms;

	// Register block editor BuddyForms script.
	wp_register_script(
		'bf-gmw-embed-map',
		plugins_url( 'shortcodes-to-blocks.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' )
	);

	//
	// Localize the BuddyForms script with all needed data
	//

	// All Forms as slug and label
	$forms = array();
	foreach ( $buddyforms as $form_slug => $form ) {
		$forms[ $form_slug ] = $form['name'];
	}
	wp_localize_script( 'bf-gmw-embed-map', 'buddyforms_gmw_maps', $forms );


	//
	// Embed a form
	//
	register_block_type( 'buddyforms/bf-gmw-embed-map', array(
		'attributes'      => array(
			'bf_form_slug' => array(
				'type' => 'string',
			)
		),
		'editor_script'   => 'bf-gmw-embed-map',
		'render_callback' => 'buddyforms_gmw_block_render_map',
	) );


}

add_action( 'init', 'buddyforms_gmw_shortcodes_to_block_init' );

/**
 * Render a Form
 *
 * @since 2.3.1
 *
 */
function buddyforms_gmw_block_render_map( $attributes ) {
	global $buddyforms;

	if ( isset( $attributes['bf_form_slug'] ) && isset( $buddyforms[ $attributes['bf_form_slug'] ] ) ) {
		$tmp =  BuddyFormsGeoMyWpShortCodes::callback_bf_geo_my_wp( array( 'form_slug' => $attributes['bf_form_slug'] ) );

//		$tmp = do_shortcode('[bf_geo_my_wp form_slug="' . $attributes['bf_form_slug'] . '"]');
		return $tmp;
	} else {
		return '<p>' . __( 'Please select a form in the block settings sidebar!', 'buddyforms' ) . '</p>';
	}
}