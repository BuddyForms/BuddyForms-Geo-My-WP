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

class BuddyFormsGeoMyWpLocateEntries {

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
	 * Hold the object data
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

		$this->args = $attr;

		// set random element id if not exists.
		$this->args['element_id'] = ! empty( $this->args['element_id'] ) ? $this->args['element_id'] : $attr['form_slug'];

		// If icon size provided, make it an array.
		if ( ! empty( $this->args['map_icon_size'] ) ) {
			$this->args['map_icon_size'] = explode( ',', $this->args['map_icon_size'] );
		}

		/** @var GEO_MY_WP $geo_my_wp_instance */
		$geo_my_wp_instance = GMW();

		// Default icon URL and size.
		if ( '' === $this->args['map_icon_url'] ) {
			$this->args['map_icon_url'] = BF_GEO_FIELD_IMAGES_PATH . 'text.png';

			// use default icon size if no size provided.
			if ( '' === $this->args['map_icon_size'] ) {
				$this->args['map_icon_size'] = array( 25, 30 );
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

		//Detect if they need to bring data from more than one form
		$this->args['form_slug'] = $this->extract_forms( $this->args['form_slug'] );

		// get the location data.
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
	 * Extract the form or forms from the string. It expect a string separated by coma example: 'form1,form2,form3'
	 *
	 * @param $form_slugs
	 *
	 * @return mixed
	 */
	public function extract_forms( $form_slugs ) {
		if ( strpos( $form_slugs, ',' ) !== false ) {
			$form_slugs = explode( ',', str_replace( ' ', '', $form_slugs ) );
		}

		return $form_slugs;
	}

	/**
	 * Verify that the object exists before getting the location
	 * Object might be deleted or in trash while location data still
	 * exists in databased
	 *
	 * @return bool
	 */
	public function object_exists() {
		return true;
	}

	/**
	 * Get the title
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function title( $item ) {
		if ( isset( $item['form_type'] ) && $item['form_type'] === 'registration' ) {
			return $this->title_of_registration_form( $item );
		} else {
			return $this->title_of_content_form( $item );
		}
	}

	/**
	 * Get the Post Title to show as title of the info window
	 *
	 * @param $item
	 *
	 * @return string
	 */
	public function title_of_content_form( $item ) {
		$title     = get_the_title( $item['post_id'] );
		$permalink = get_the_permalink( $item['post_id'] );

		return apply_filters( 'bf_geo_my_wp_form_content_title', "<h3 class=\"gmw-sl-title post-title gmw-sl-element\"><a href=\"{$permalink}\" title=\"{$title}\"'>{$title}</a></h3>", $this->location_data, $this->args, $this->user_position, $this );
	}

	/**
	 * Get the User Display Name to show as title of the info window
	 *
	 * @param $item
	 *
	 * @return mixed
	 */
	public function title_of_registration_form( $item ) {
		$title     = get_the_author_meta( 'display_name', $item['user_id'] );
		$permalink = ( function_exists( 'bp_core_get_userlink' ) ) ? bp_core_get_userlink( $item['user_id'], false, true ) : get_author_posts_url( $item['user_id'] );

		return apply_filters( 'bf_geo_my_wp_form_registration_title', "<h3 class=\"gmw-sl-title post-title gmw-sl-element\"><a href=\"{$permalink}\" title=\"{$title}\"'>{$title}</a></h3>", $this->location_data, $this->args, $this->user_position, $this );
	}

	/**
	 * Get location data.
	 *
	 * @return array of locations data
	 */
	public function location_data() {

		// check if provided object ID.
		if ( empty( $this->args['form_slug'] ) ) {
			return;
		}

		// get the location data.
		$location = $this->get_locations_by_form( $this->args['form_slug'] );

		return $location;
	}

	/**
	 * Get all locations based on form_type - form_slug pair.
	 *
	 * @param  string|array $form_slug the form Slug
	 * @param  boolean $cache Look for location in cache
	 *
	 * @return array of locations data.
	 */
	public function get_locations_by_form( $form_slug = '', $cache = true ) {
		if ( empty( $form_slug ) ) {
			return array();
		}

		$result = array();
		//get the form type
		if ( is_array( $form_slug ) ) {//is multiples form with an array
			foreach ( $form_slug as $item ) {
				$item              = $this->get_form_slug_from_id( $item );
				$current_form_type = $this->get_form_type( $item );
				if ( $current_form_type !== 'registration' ) {
					$result = array_merge( $this->get_locations_for_content_form( $item, $current_form_type, $cache ), $result );
				} else {
					$result = array_merge( $this->get_locations_for_registration_form( $item, $current_form_type, $cache ), $result );
				}
			}
		} else {//is string with one form
			$form_slug         = $this->get_form_slug_from_id( $form_slug );
			$current_form_type = $this->get_form_type( $form_slug );
			if ( $current_form_type !== 'registration' ) {
				$result = $this->get_locations_for_content_form( $form_slug, $current_form_type, $cache );
			} else {
				$result = $this->get_locations_for_registration_form( $form_slug, $current_form_type, $cache );
			}
		}

		return $result;

	}

	/**
	 * Get the form slug form a form id
	 *
	 * @param string|integer $form_slug
	 *
	 * @return string
	 */
	public function get_form_slug_from_id( $form_slug ) {
		if ( is_numeric( $form_slug ) ) {
			$post_form = get_post( $form_slug );
			$form_slug = $post_form->post_name;
		}

		return $form_slug;
	}

	/**
	 * Get the form type from the string form slug or form id
	 *
	 * @param string $form_slug
	 *
	 * @return string
	 */
	public function get_form_type( $form_slug ) {
		$post_form_options = buddyforms_get_form_by_slug( $form_slug );

		//Return the form type
		return ( ! empty( $post_form_options ) && $post_form_options['form_type'] === 'registration' ) ? 'registration' : 'post';
	}

	/**
	 * Get the form post type from the string form slug or form id
	 *
	 * @param string $form_slug
	 *
	 * @return string
	 */
	public function get_form_post_type( $form_slug ) {
		$post_form_options = buddyforms_get_form_by_slug( $form_slug );

		//Return the form type
		return ( ! empty( $post_form_options ) && ! empty( $post_form_options['post_type'] ) ) ? $post_form_options['post_type'] : 'post';
	}

	/**
	 * Get the entries for a registration form
	 *
	 * @param $form_slug
	 * @param $form_type
	 * @param bool $cache
	 *
	 * @return array|bool
	 */
	public function get_locations_for_registration_form( $form_slug, $form_type, $cache = true ) {
		// look for locations in cache
		$locations = $cache ? wp_cache_get( $form_type . '_' . $form_slug, 'bf_geo_wp_locations' ) : false;

		// if no locations found in cache get it from database
		if ( false === $locations ) {

			$fields        = buddyforms_get_form_fields( $form_slug );

			if ( empty( $fields ) ) {
				return array();
			}

			$fields_result = array();
			foreach ( $fields as $field ) {
				if ( $field['type'] == 'geo_my_wp_address' ) {
					$fields_result[] = $field['slug'];
				}
			}
			$meta_args = array();

			if ( ! empty( $fields_result ) ) {
				foreach ( $fields_result as $field_slug ) {
					$meta_args[] = array(
						'key'     => 'bf_' . $field_slug . '_count',
						'compare' => 'EXISTS',
					);
				}
			} else {
				return array();
			}

			$query_args = array(
				'include'    => array(),
				'fields'     => 'ID',
				'meta_query' => $meta_args
			);

			if ( ! empty( $this->args['logged_in_user'] ) ) {
				$query_args['include'] = array( get_current_user_id() );
			}
			if ( ! empty( $this->args['user_id'] ) ) {
				$query_args['include'] = array( $this->args['user_id'] );
			}

			$query = new WP_User_Query( $query_args );

			$results = $query->get_results();
			if ( ! empty( $results ) ) {
				foreach ( $results as $user_id ) {
					foreach ( $fields_result as $field_slug ) {
						$locations[] = array(
							'form_slug' => $form_slug,
							get_user_meta( $user_id, 'bf_' . $field_slug . '_count' ),
						);
					}
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
	 * Get the entries for a content form
	 *
	 * @param $form_slug
	 * @param $form_type
	 * @param bool $cache
	 *
	 * @return array|bool
	 */
	public function get_locations_for_content_form( $form_slug, $form_type, $cache = true ) {
		// look for locations in cache
		$locations = $cache ? wp_cache_get( $form_type . '_' . $form_slug, 'bf_geo_wp_locations' ) : false;

		// if no locations found in cache get it from database
		if ( false === $locations ) {

			$post_type = $this->get_form_post_type( $form_slug );

			$fields = buddyforms_get_form_fields( $form_slug );

			if ( empty( $fields ) ) {
				return array();
			}

			$fields_result = array();
			foreach ( $fields as $field ) {
				if ( $field['type'] == 'geo_my_wp_address' ) {
					$fields_result[] = $field['slug'];
				}
			}
			$meta_args = array(
				'relation' => 'AND',
				array(
					'key'   => '_bf_form_slug',
					'value' => sanitize_title( $form_slug ),
				)
			);

			if ( ! empty( $fields_result ) ) {
				foreach ( $fields_result as $field_slug ) {
					$meta_args[] = array(
						'key'     => 'bf_' . $field_slug . '_count',
						'compare' => 'EXISTS',
					);
				}
			} else {
				return array();
			}

			$query_args = array(
				'post_type'  => $post_type,
				'fields'     => 'ids',
				'meta_query' => $meta_args
			);

			if ( ! empty( $this->args['logged_in_user'] ) ) {
				$query_args['author'] = get_current_user_id();
			}
			if ( ! empty( $this->args['user_id'] ) ) {
				$query_args['author'] = $this->args['user_id'];
			}

			$query = new WP_Query( $query_args );

			if ( ! empty( $query->posts ) ) {
				foreach ( $query->posts as $post_id ) {
					foreach ( $fields_result as $field_slug ) {
						$locations[] = array(
							'form_slug' => $form_slug,
							get_post_meta( $post_id, 'bf_' . $field_slug . '_count' ),
						);
					}
				}
			}

			if ( empty( $locations ) ) {
				return array();
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
	 * Get the object data
	 *
	 * @return bool
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
	 * @return bool|string
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
					if ( empty( $address_component['types'][0] ) && $address_component['types'][0] === $field ) {
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

		return apply_filters( 'bf_geo_my_wp_address', $output, $address, $this->args, $item['location'], $this->user_position, $this );
	}

	/**
	 * Show Distance
	 *
	 * Get the distance betwwen the user's position to the item being displayed
	 *
	 * @param $item
	 *
	 * @return bool|string
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

		return apply_filters( 'bf_geo_my_wp_distance', $output, $distance, $units, $this->args, $item['location'], $this->user_position, $this );
	}

	/**
	 * Map element
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

		$current_form_slug = '';
		$locations         = array();
		foreach ( $this->location_data as $location_item ) {
			foreach ( $location_item as $parent_item ) {
				if ( is_array( $parent_item ) ) {
					foreach ( $parent_item as $item ) {
						if ( is_array( $item ) ) {
							foreach ( $item as $location_obj ) {
								if ( ! empty( $location_obj->data ) ) {
									$map_icon    = ( $location_obj->data['form_type'] === 'registration' ) ? BF_GEO_FIELD_IMAGES_PATH . 'smiley_happy.png' : BF_GEO_FIELD_IMAGES_PATH . 'text.png';
									$locations[] = array(
										'lat'                 => $location_obj->data['location']['lat'],
										'lng'                 => $location_obj->data['location']['lng'],
										'info_window_content' => $this->info_window_content( $location_obj->data ),
										'map_icon'            => apply_filters( 'bf_geo_my_wp_entry_map_icon', $map_icon, $this->args, $location_obj, $this->user_position, $current_form_slug ),
										'icon_size'           => $this->args['map_icon_size'],
									);
								}
							}
						}
					}
				} else {
					$current_form_slug = $parent_item;
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

		return apply_filters( 'bf_geo_my_wp_directions', $output, $this->args, $this->location_data, $this->user_position, $this );
	}

	/**
	 * Live directions function
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
		$output = apply_filters( 'bf_geo_my_wp_live_directions', $output, $this->args, $this->location_data, $this->user_position, $this );

		return apply_filters( 'bf_geo_my_wp_directions_form', $output, $this->args, $this->location_data, $this->user_position, $this );
	}

	/**
	 * Live directions panel
	 * Holder for the results of the live directions
	 */
	public function directions_panel() {

		$output = gmw_get_directions_panel( $this->args['element_id'] );

		return apply_filters( 'bf_geo_my_wp_directions_panel', $output, $this->args, $this->location_data, $this->user_position, $this );
	}

	/**
	 * Display location meta
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
		$output = apply_filters( 'bf_geo_my_wp_additional_info', $output, $this->args, $this->location_data, $this->user_position, $this );

		return apply_filters( 'bf_geo_my_wp_location_meta', $output, $this->args, $this->location_data, $this->user_position, $this );
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

		$output = apply_filters( 'bf_geo_my_wp_object_info_window', $iw_elements, $this->args, $this->location_data, $this->user_position, $this );

		return implode( ' ', $output );
	}

	/**
	 * Display no location message
	 */
	public function no_location_message() {

		return apply_filters( 'bf_geo_my_wp_no_location_message', '<h3 class="no-location">' . esc_attr( $this->args['no_location_message'] ) . '</h3>', $this->location_data, $this->args, $this->user_position, $this );
	}

	/**
	 * Display elements based on arguments
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

		do_action( 'bf_geo_my_wp_before_output_elements', $this->elements, $this->args, $this->location_data, $this->user_position );

		$output = implode( '', $this->elements );

		return apply_filters( 'bf_geo_my_wp_display_output', $output, $this->elements, $this->args, $this->location_data, $this->user_position, $this );
	}
}
