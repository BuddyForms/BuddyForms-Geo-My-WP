<?php
/**
 * Create Blocks from Shortcodes
 *
 * @since 1.0
 *
 */
function buddyforms_gmw_shortcodes_to_block_init() {
	global $buddyforms;

	// Register block editor BuddyForms GMW script.
	wp_register_script(
		'bf-gmw-embed-map',
		plugins_url( 'shortcodes-to-blocks.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' )
	);

	//
	// Localize the BuddyForms GMW script with all needed data
	//

	// All Forms as slug and label
	$forms = array();
	foreach ( $buddyforms as $form_slug => $form ) {
		$forms[ $form_slug ] = $form['name'];
	}
	wp_localize_script( 'bf-gmw-embed-map', 'buddyforms_gmw_maps', $forms );


	//
	// Embed a map
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
}

add_action( 'init', 'buddyforms_gmw_shortcodes_to_block_init' );

/**
 * Render a Form
 *
 * @since 1.0
 *
 */
function buddyforms_gmw_block_render_map( $attributes ) {
	global $buddyforms;

	if ( isset( $attributes['bf_form_slug'] ) && isset( $buddyforms[ $attributes['bf_form_slug'] ] ) ) {

		$attr = array(
			'form_slug'      => empty( $attributes['bf_form_slug'] ) ? '' : $attributes['bf_form_slug'],
			'user_id'        => empty( $attributes['bf_user_id'] ) ? '' : $attributes['bf_user_id'],
			'map_width'      => empty( $attributes['bf_map_width'] ) ? '' : $attributes['bf_map_width'],
			'map_height'     => empty( $attributes['bf_map_height'] ) ? '' : $attributes['bf_map_height'],
			'elements'       => empty( $attributes['bf_elements'] ) ? '' : $attributes['bf_elements'],
			'logged_in_user' => empty( $attributes['bf_logged_in_user'] ) ? '' : $attributes['bf_logged_in_user'],
			'info_window'    => empty( $attributes['bf_info_window'] ) ? '' : $attributes['bf_info_window'],
		);

		$shortcode = new BuddyFormsGeoMyWpShortCodes();
		$tmp       = $shortcode->callback_bf_geo_my_wp( $attr );

		if ( empty( $tmp ) ) {
			return __( 'No entry\'s found', 'buddyforms' );
		}

		/**
		 * Me quede en tratar que se cargen los atributos del mapa desde una variable para que se puedan leer desde js y poder saber el id del mapa para poder
		 */
//		ob_start();
//		echo "<script type=\"text/javascript\">".
//			"var bfGmwMapArgument = " . wp_json_encode( GMW_Maps_API::get_map_args() ) . ";".
//			"console.log('here '+bfGmwMapArgument);".
//			"</script>";
//		$tmp .= ob_get_clean();

		return $tmp;
	} else {
		return '<p>' . __( 'Please select a form in the block settings sidebar!', 'buddyforms' ) . '</p>';
	}
}