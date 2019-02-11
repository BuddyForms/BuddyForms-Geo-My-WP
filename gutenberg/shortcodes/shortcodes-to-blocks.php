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
			'bf_form_slug'      => array(
				'type' => 'string',
			),
			'bf_user_id'        => array(
				'type' => 'string',
			),
			'bf_map_width'      => array(
				'type'    => 'string',
				'default' => '250px',
			),
			'bf_map_height'     => array(
				'type'    => 'string',
				'default' => '250px',
			),
			'bf_elements'       => array(
				'type'    => 'string',
				'default' => 'map,distance,location_meta',
			),
			'bf_logged_in_user' => array(
				'type' => 'string',
			),
			'bf_info_window'    => array(
				'type'    => 'string',
				'default' => 'title,address,distance',
			),
		),
		'editor_script'   => 'bf-gmw-embed-map',
		'render_callback' => 'buddyforms_gmw_block_render_map',
	) );

//	''       => '',
//			''              => '',
//			''            =>
//			''           =>
//			''             => ,
//			'object'               => 'post',
//			'prefix'               => 'pt',
//			'location_meta'        => 'address',
//			'element_id'           => 0,
//			'form_type'            => '',
//			'address_fields'       => 'address',
//			'units'                => 'metric',
//			'map_type'             => 'ROADMAP',
//			'zoom_level'           => 13,
//			'scrollwheel_map_zoom' => 1,
//			'expand_map_on_load'   => 0,
//			'map_icon_url'         => '',
//			'map_icon_size'        => '',
//
//			'user_map_icon_url'    => '',
//			'user_map_icon_size'   => '',
//			'user_info_window'     => __( 'Your Location', 'geo-my-wp' ),
//			'no_location_message'  => 0,
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
		$tmp = BuddyFormsGeoMyWpShortCodes::callback_bf_geo_my_wp(
			array(
				'form_slug'         => $attributes['bf_form_slug'],
				'user_id'           => $attributes['bf_user_id'],
				'map_width'      => $attributes['bf_map_width'],
				'map_height'     => $attributes['bf_map_height'],
				'elements'       => $attributes['bf_elements'],
				'logged_in_user' => $attributes['bf_logged_in_user'],
				'info_window'    => $attributes['bf_info_window'],
			) );

//		$tmp = do_shortcode('[bf_geo_my_wp form_slug="' . $attributes['bf_form_slug'] . '"]');
		return $tmp;
	} else {
		return '<p>' . __( 'Please select a form in the block settings sidebar!', 'buddyforms' ) . '</p>';
	}
}