<?php
/*
 * @package WordPress
 * @subpackage BuddyForms, GEO My WP
 * @author ThemKraft Dev Team
 * @copyright 2018 Themekraft
 * @link http://buddyforms.com
 * @license GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BuddyFormsGeoMyWpLocatePosts {

	/**
	 * Array for child class to extends the main array above
	 *
	 * @var array
	 */
	protected $args = array();

	/**
	 * Object contains the item location information
	 *
	 * @var object
	 *
	 * Public $location_data
	 */
	public $location_data;

	/**
	 * Hold the object data ( post, user, group ... ).
	 *
	 * @var object
	 */
	public $object_data;

	/**
	 * Holds the location meta data.
	 *
	 * @var array
	 */
	public $location_meta = false;

	/**
	 * Array contains the current user position if exists
	 *
	 * @var array
	 *
	 * @since 2.6.1.
	 *
	 * Public $user_position
	 */
	public $user_position = array(
		'exists'  => false,
		'lat'     => false,
		'lng'     => false,
		'address' => false,
	);

	/**
	 * Array contains the elements to be output
	 *
	 * @var array
	 *
	 * @since 2.6.1
	 *
	 * Public $this->elements
	 */
	public $elements = array();

	public function __construct( $attr = array() ) {

		if ( empty( $attr['object'] ) ) {
			gmw_trigger_error( '[bf_geo_my_wp] You need to set the object between post and user' );
		}

		if ( empty( $attr['form_slug'] ) ) {
			gmw_trigger_error( '[bf_geo_my_wp] shortcode attribute form_slug is mandatory.' );
		}

		if ( isset( $attr['object_info_window'] ) ) {

			$attr['info_window'] = $attr['object_info_window'];

			unset( $attr['object_info_window'] );
		}

		if ( isset( $attr['user_map_icon'] ) ) {

			$attr['user_map_icon_url'] = $attr['user_map_icon'];

			unset( $attr['user_map_icon'] );
		}

		$this->args = $attr;

		// set random element id if not exists.
		$this->args['element_id'] = ! empty( $this->args['element_id'] ) ? $this->args['element_id'] : wp_rand( 100, 549 );

		// in case form_type is missing.
		if ( empty( $this->args['form_type'] ) ) {
			$this->args['form_type'] = $this->args['object'];
		}

		// If icon size provided, make it an array.
		if ( ! empty( $this->args['map_icon_size'] ) ) {
			$this->args['map_icon_size'] = explode( ',', $this->args['map_icon_size'] );
		}

		/** @var GEO_MY_WP $geo_my_wp_instance */
		$geo_my_wp_instance = GMW();

		// Default icon URL and size.
		if ( '' === $this->args['map_icon_url'] ) {
			$this->args['map_icon_url'] = $geo_my_wp_instance->default_icons['location_icon_url'];

			// use default icon size if no size provided.
			if ( '' === $this->args['map_icon_size'] ) {
				$this->args['map_icon_size'] = $geo_my_wp_instance->default_icons['location_icon_size'];
			}
		}

		// If icon size provided, make it an array.
		if ( ! empty( $this->args['user_map_icon_size'] ) ) {
			$this->args['user_map_icon_size'] = explode( ',', $this->args['user_map_icon_size'] );
		}

		// Default icon URL and size.
		if ( '' === $this->args['user_map_icon_url'] ) {

			$this->args['user_map_icon_url'] = $geo_my_wp_instance->default_icons['user_location_icon_url'];

			// use default icon size if no size provided.
			if ( '' === $this->args['user_map_icon_size'] ) {
				$this->args['user_map_icon_size'] = $geo_my_wp_instance->default_icons['user_location_icon_size'];
			}
		}

		// get elements to display.
		$this->elements_value = explode( ',', str_replace( ' ', '', $this->args['elements'] ) );

		$object_exists = $this->object_exists();

		// check that object exists before anything else.
		if ( empty( $object_exists ) ) {
			return;
		}

		// check that we have at least one element to display.
		if ( empty( $this->elements_value ) ) {
			return;
		}

		if ( empty( $this->args['form_slug'] ) ) {
			$this->args['form_slug'] = 0;
		}

		// get the locaiton data.
		$this->location_data = $this->location_data();

		// abort if no location found and no need to show message.
		if ( empty( $this->location_data ) && empty( $this->args['no_location_message'] ) ) {
			return;
		}

		// generate the elements array.
		$this->elements['element_wrap_start'] = '<div id="gmw-single-location-wrapper-' . esc_attr( $this->args['element_id'] ) . '" class="gmw-single-location-wrapper gmw-sl-wrapper ' . esc_attr( $this->args['object'] ) . ' gmw-single-' . esc_attr( $this->args['object'] ) . '-sc-wrapper">';

		// if no location found.
		if ( empty( $this->location_data ) ) {

			// generate element for the title ( if title exists in elements ).
			if ( in_array( 'title', $this->elements_value, true ) ) {
				$this->elements['title'] = false;
			}

			// generate element for the no location message.
			$this->elements['no_location_message'] = false;

			// otherwise, generate additional data.
		} else {

			// get labels.
			$this->labels = array(
				'distance'        => __( 'Distance: ', 'geo-my-wp' ),
				'directions'      => __( 'Directions', 'geo-my-wp' ),
				'from'            => __( 'From:', 'geo-my-wp' ),
				'show_directions' => __( 'Show directions', 'geo-my-wp' ),
			);

			// check for last location in URL.
			if ( ! empty( $_GET['lat'] ) && ! empty( $_GET['lng'] ) ) { // WPCS: CSRF ok.

				$this->user_position['exists'] = true;
				$this->user_position['lat']    = sanitize_text_field( wp_unslash( $_GET['lat'] ) ); // WPCS: CSRF ok.
				$this->user_position['lng']    = sanitize_text_field( wp_unslash( $_GET['lng'] ) ); // WPCS: CSRF ok.

				$address = '';

				if ( ! empty( $_GET['address'] ) ) {

					if ( is_array( $_GET['address'] ) ) {

						$address = implode( ' ', $_GET['address'] ); // WPCS: XSS ok, sanitization ok, CSRF ok.

					} else {
						$address = $_GET['address']; // WPCS: XSS ok, sanitization ok, CSRF ok.
					}
				}

				$this->user_position['address'] = sanitize_text_field( wp_unslash( $address ) );

				// Otherwise check for user location in cookies.
			} elseif ( ! empty( $_COOKIE['gmw_ul_lat'] ) && ! empty( $_COOKIE['gmw_ul_lng'] ) ) {

				$this->user_position['exists']  = true;
				$this->user_position['lat']     = urldecode( wp_unslash( $_COOKIE['gmw_ul_lat'] ) ); // WPCS: sanitization ok.
				$this->user_position['lng']     = urldecode( wp_unslash( $_COOKIE['gmw_ul_lng'] ) ); // WPCS: sanitization ok.
				$this->user_position['address'] = ! empty( $_COOKIE['gmw_ul_address'] ) ? urldecode( wp_unslash( $_COOKIE['gmw_ul_address'] ) ) : ''; // WPCS: sanitization ok.
			}

			// generate elements.
			foreach ( $this->elements_value as $value ) {
				$this->elements[ $value ] = false;
			}
		}

		$this->elements['element_wrap_end'] = '</div>';
	}

	/**
	 * Verify that the object exists before getting the location
	 * Object might be deleted or in trash while location data still
	 * exists in databased
	 *
	 * @return [type] [description]
	 */
	public function object_exists() {
		return true;
	}

	/**
	 * Get the post title
	 *
	 * @param $item
	 *
	 * @return mixed
	 */
	public function title( $item ) {

		$title     = get_the_title( $item['post_id'] );
		$permalink = get_the_permalink( $item['post_id'] );

		return apply_filters( 'gmw_sl_title', "<h3 class=\"gmw-sl-title post-title gmw-sl-element\"><a href=\"{$permalink}\" title=\"{$title}\"'>{$title}</a></h3>", $this->location_data, $this->args, $this->user_position, $this );
	}

	/**
	 * Get location data.
	 *
	 * @return [type] [description]
	 */
	public function location_data() {

		// check if provided object ID.
		if ( empty( $this->args['form_type'] ) || empty( $this->args['form_slug'] ) ) {
			return;
		}

		// get the location data.
		$location = $this->get_locations_by_form( $this->args['form_type'], $this->args['form_slug'] );

		return $location;
	}

	/**
	 * Get all locations based on form_type - form_slug pair.
	 *
	 * @param  string $form_type the object type ( post, registration ).
	 * @param  integer $form_slug the form Slug
	 * @param  boolean $cache Look for location in cache
	 *
	 * @return array of locations data.
	 *
	 * since 3.2
	 */
	public function get_locations_by_form( $form_type = '', $form_slug = 0, $cache = true ) {

//		// verify object type
//		if ( ! in_array( $form_type, GMW()->form_types ) ) {
//
//			trigger_error( 'Trying to get a location using invalid object type.', E_USER_NOTICE );
//
//			return false;
//		}

		// verify object ID
//		if ( ! is_numeric( $form_id ) || ! absint( $form_id ) ) {
//
//			trigger_error( 'Trying to get a locations using invalid object ID.', E_USER_NOTICE );
//
//			return false;
//		}

//		$form_slug = absint( $form_slug );

		// look for locations in cache
		$locations = $cache ? wp_cache_get( $form_type . '_' . $form_slug, 'bf_geo_wp_locations' ) : false;

		// if no locations found in cache get it from database
		if ( false === $locations ) {

			$query = new WP_Query( array(
				'post_type'  => 'post',
				'fields'     => 'ids',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'   => '_bf_form_slug',
						'value' => sanitize_title( $form_slug ),
					),
					array(
						'key'     => 'bf_address_count',
						'compare' => 'EXISTS',
					),
				)
			) );

			if ( ! empty( $query->posts ) ) {
				foreach ( $query->posts as $post_id ) {
					$locations[] = get_post_meta( $post_id, 'bf_address_count' );
				}
			}

			// save to cache if location found
			if ( ! empty( $locations ) ) {
				wp_cache_set( $form_type . '_' . $form_slug, serialize( $locations ), 'bf_geo_wp_locations' );
			}
		}

		// if no location found
		if ( empty( $locations ) ) {
			return array();
		}

		$locations = maybe_unserialize( $locations );

		return $locations;
	}

	/**
	 * Get the object data ( post, member, user... ).
	 *
	 * @return [type] [description]
	 */
	public function get_object_data() {
		return false;
	}

	/**
	 *
	 * Get address
	 *
	 * The address of the displayed item
	 *
	 * @param $item
	 *
	 * @return bool|mixed
	 */
	public function address( $item ) {

		// if item has no location, abort!
		if ( empty( $item['location'] ) ) {
			return ! empty( $this->args['no_location_message'] ) ? $this->no_location_message() : false;
		}

		// get the full address.
		if ( empty( $this->args['address_fields'] ) || 'address' === $this->args['address_fields'] ) {

			$address = ! empty( $item['formatted_address'] ) ? $item['formatted_address'] : '';

			// Otherwise, get specific address fields.
		} else {

			$this->args['address_fields'] = ! is_array( $this->args['address_fields'] ) ? explode( ',', $this->args['address_fields'] ) : $this->args['address_fields'];

			$address_array = array();

			foreach ( $this->args['address_fields'] as $field ) {
				foreach ( $item['address_components'] as $address_component ) {
					if ( empty( $address_component['types'][0] === $field ) ) {
						continue;
					}
					if ( ! empty( $address_component['long_name'] ) ) {
						$address_array[] = $address_component['long_name'];
					}
				}
			}

			$address = implode( ' ', $address_array );
		}

		$output = '<div class="gmw-sl-address gmw-sl-element"><i class="gmw-location-icon gmw-icon-location"></i><span class="address">' . esc_attr( stripslashes( $address ) ) . '</span></div>';

		return apply_filters( 'gmw_sl_address', $output, $address, $this->args, $item['location'], $this->user_position, $this );
	}

	/**
	 * Show Distance
	 *
	 * Get the distance betwwen the user's position to the item being displayed
	 *
	 * @param $item
	 *
	 * @return bool|mixed
	 */
	public function distance( $item ) {

		// if item has no location, abort!
		if ( empty( $item ) ) {
			return ! empty( $this->args['no_location_message'] ) ? $this->no_location_message() : false;
		}

		// check for user position.
		if ( ! $this->user_position['exists'] ) {
			return;
		}

		if ( 'k' === $this->args['units'] || 'metric' === $this->args['units'] ) {
			$units = 'km';
		} else {
			$units = 'mi';
		}

		$distance = gmw_calculate_distance( $this->user_position['lat'], $this->user_position['lng'], $item['location']['lat'], $item['location']['lng'], $this->args['units'] );

		$output = '<div class="gmw-sl-distance gmw-sl-element">';
		$output .= '<i class="gmw-distance-icon gmw-icon-compass"></i>';
		$output .= '<span class="label">' . esc_attr( $this->labels['distance'] ) . '</span> ';
		$output .= '<span>' . $distance . ' ' . $units . '</span></div>';

		return apply_filters( 'gmw_sl_distance', $output, $distance, $units, $this->args, $item['location'], $this->user_position, $this );
	}

	/**
	 * Map element
	 *
	 * @since 2.6.1
	 * @access public
	 */
	public function map() {

		// if item has no location, abort!
		if ( empty( $this->location_data ) ) {
			return ! empty( $this->args['no_location_message'] ) ? $this->no_location_message() : false;
		}

		// map args.
		$map_args = array(
			'map_id'         => $this->args['element_id'],
			'map_type'       => 'single_location',
			'prefix'         => 'sl',
			'map_width'      => $this->args['map_width'],
			'map_height'     => $this->args['map_height'],
			'expand_on_load' => $this->args['expand_map_on_load'],
			'init_visible'   => true,
		);

		$locations = array();
		foreach ( $this->location_data as $location_item ) {
			foreach ( $location_item as $parent_item ) {
				foreach ( $parent_item as $item ) {
					if ( ! empty( $item->data ) ) {
						$locations[] = array(
							'lat'                 => $item->data['location']['lat'],
							'lng'                 => $item->data['location']['lng'],
							'info_window_content' => $this->info_window_content( $item->data ),
							'map_icon'            => apply_filters( 'gmw_sl_post_map_icon', $this->args['map_icon_url'], $this->args, $item->data['location'], $this->user_position, $this ),
							'icon_size'           => $this->args['map_icon_size'],
						);
					}
				}
			}
		}

		if ( empty( $locations ) ) {
			return ! empty( $this->args['no_location_message'] ) ? $this->no_location_message() : false;
		}

		$map_options = array(
			'mapTypeId'         => $this->args['map_type'],
			'zoom'              => 'auto',
			'mapTypeControl'    => true,
			'streetViewControl' => false,
			'scrollwheel'       => ! empty( $this->args['scrollwheel_map_zoom'] ) ? true : false,
			'panControl'        => false,
		);

		$user_position = array(
			'lat'        => $this->user_position['lat'],
			'lng'        => $this->user_position['lng'],
			'address'    => $this->user_position['address'],
			'map_icon'   => $this->args['user_map_icon_url'],
			'icon_size'  => $this->args['user_map_icon_size'],
			'iw_content' => ! empty( $this->args['user_info_window'] ) ? $this->args['user_info_window'] : null,
		);

		return gmw_get_map( $map_args, $map_options, $locations, $user_position );
	}

	/**
	 * Directions function
	 *
	 * @since 2.6.1
	 *
	 * @access public
	 */
	public function directions_link() {

		// if item has no location, abort!
		if ( empty( $this->location_data ) ) {
			return ! empty( $this->args['no_location_message'] ) ? $this->no_location_message() : false;
		}

		$element_id = esc_attr( $this->args['element_id'] );
		$object     = esc_attr( $this->args['object'] );

		$output = '';
		$output .= "<div id=\"gmw-sl-directions-link-wrapper-{$element_id}\" class=\"gmw-sl-directions-link-wrapper gmw-sl-element gmw-sl-{$object}-direction-link-wrapper\">";
		$output .= '<div class="trigger-wrapper">';
		$output .= '<i class="gmw-icon-location-thin"></i>';
		$output .= "<a href=\"#\" id=\"form-trigger-{$element_id}\" class=\"form-trigger\" onclick=\"event.preventDefault();jQuery(this).closest( '.gmw-sl-element' ).find( '.directions-link-form-wrapper' ).slideToggle();\">" . esc_attr( $this->labels['directions'] ) . '</a>';
		$output .= '</div>';
		$output .= "<div id=\"directions-link-form-wrapper-{$element_id}\" class=\"directions-link-form-wrapper\" style=\"display:none;\">";
		$output .= '<form action="https://maps.google.com/maps" method="get" target="_blank">';
		$output .= '<div class="address-field-wrapper">';
		$output .= '<label for="start-address-' . $element_id . '">' . esc_attr( $this->labels['from'] ) . ' </label>';
		$output .= '<input type="text" size="35" id="origin-' . $element_id . '" class="origin-field" name="saddr" value="' . esc_attr( $this->user_position['address'] ) . '" placeholder="Your location" />';
		$output .= "<a href=\"#\" class=\"get-directions-link-submit gmw-icon-search\" onclick=\"jQuery( this ).closest( 'form' ).submit();\"></a>";
		$output .= '</div>';
		$output .= '<input type="hidden" name="daddr" value="' . esc_attr( $this->location_data->address ) . '" />';
		$output .= '</form>';
		$output .= '</div>';
		$output .= '</div>';

		return apply_filters( 'gmw_sl_directions', $output, $this->args, $this->location_data, $this->user_position, $this );
	}

	/**
	 * Live directions function
	 *
	 * @since 2.6.1
	 * @access public
	 */
	public function directions_form() {

		// if item has no location, abort!
		if ( empty( $this->location_data ) ) {
			return ! empty( $this->args['no_location_message'] ) ? $this->no_location_message() : false;
		}

		$element_id = esc_attr( $this->args['element_id'] );

		$args = array(
			'element_id'  => $this->args['element_id'],
			'origin'      => $this->user_position['address'],
			'destination' => $this->location_data->address,
		);

		$output = '<div class="gmw-sl-directions-trigger-wrapper">';
		$output .= '<i class="gmw-directions-icon gmw-icon-location-thin"></i>';
		$output .= "<a href=\"#\" id=\"gmw-sl-directions-trigger-{$element_id}\" class=\"gmw-sl-directions-trigger\" onclick=\"event.preventDefault();jQuery('#gmw-directions-form-wrapper-{$element_id}, #gmw-directions-panel-wrapper-{$element_id}').slideToggle();\">" . esc_attr( $this->labels['show_directions'] ) . '</a>';
		$output .= '</div>';

		$output .= gmw_get_directions_form( $args );

		// for older versions.
		$output = apply_filters( 'gmw_sl_live_directions', $output, $this->args, $this->location_data, $this->user_position, $this );

		return apply_filters( 'gmw_sl_directions_form', $output, $this->args, $this->location_data, $this->user_position, $this );
	}

	/**
	 * Live directions panel
	 * Holder for the results of the live directions
	 *
	 * @since 2.6.1
	 */
	public function directions_panel() {

		$output = gmw_get_directions_panel( $this->args['element_id'] );

		return apply_filters( 'gmw_sl_directions_panel', $output, $this->args, $this->location_data, $this->user_position, $this );
	}

	/**
	 * Display location meta
	 *
	 * @since 3.0
	 *
	 * @access public
	 */
	public function location_meta() {

		if ( empty( $this->args['location_meta'] ) ) {
			return false;
		}

		$contact_info = explode( ',', $this->args['location_meta'] );

		$output = '<div class="gmw-sl-location-metas gmw-sl-element gmw-sl-additional-info-wrapper">';
		$output .= gmw_get_location_meta_list( $this->location_data->ID, $contact_info );
		$output .= '</div>';

		// for older version - to be removed.
		$output = apply_filters( 'gmw_sl_additional_info', $output, $this->args, $this->location_data, $this->user_position, $this );

		return apply_filters( 'gmw_sl_location_meta', $output, $this->args, $this->location_data, $this->user_position, $this );
	}

	/**
	 * Create the content of the info window
	 *
	 * @param $item
	 *
	 * @return bool|string
	 */
	public function info_window_content( $item ) {

		if ( empty( $this->args['info_window'] ) ) {
			return false;
		}

		// get info window elements.
		$iw_elements_array = explode( ',', $this->args['info_window'] );

		$iw_elements = array();

		$iw_elements['iw_start'] = '<div class="gmw-iw-wrapper gmw-sl-iw-wrapper ' . esc_attr( $this->args['object'] ) . '">';

		foreach ( $iw_elements_array as $value ) {
			$iw_elements[ $value ] = false;
		}

		$iw_elements['iw_end'] = '</div>';

		if ( isset( $iw_elements['distance'] ) ) {
			$iw_elements['distance'] = $this->distance( $item );
		}
		if ( isset( $iw_elements['title'] ) ) {
			$iw_elements['title'] = $this->title( $item );
		}
		if ( isset( $iw_elements['address'] ) ) {
			$iw_elements['address'] = $this->address( $item );
		}

		$output = apply_filters( 'gmw_sl_object_info_window', $iw_elements, $this->args, $this->location_data, $this->user_position, $this );

		return implode( ' ', $output );
	}

	/**
	 * Display no location message
	 *
	 * @since 2.6.1
	 *
	 * @access public
	 */
	public function no_location_message() {

		return apply_filters( 'gmw_sl_no_location_message', '<h3 class="no-location">' . esc_attr( $this->args['no_location_message'] ) . '</h3>', $this->location_data, $this->args, $this->user_position, $this );
	}

	/**
	 * Display elements based on arguments
	 *
	 * @since 2.6.1
	 *
	 * @access public
	 */
	public function output() {

		// check that we have at least one element to display.
		if ( empty( $this->elements_value ) ) {
			return;
		}

		/** If ( ! empty( $this->elements['widget_title'] ) ) {
		 * // $this->elements['widget_title'] = html_entity_decode( $this->args['widget_title'] );
		 * // } */

		// loop through and generate the elements.
		foreach ( $this->elements as $element => $value ) {

			if ( method_exists( $this, $element ) ) {

				$this->elements[ $element ] = $this->$element();
			}
		}

		do_action( 'gmw_sl_before_output_elements', $this->elements, $this->args, $this->location_data, $this->user_position );

		$output = implode( '', $this->elements );

		return apply_filters( 'gmw_sl_display_output', $output, $this->elements, $this->args, $this->location_data, $this->user_position, $this );
	}
}
