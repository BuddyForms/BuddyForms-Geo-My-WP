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

class BuddyFormsGeoMyWpElementSave {

	public function __construct() {
		add_action( 'buddyforms_update_post_meta', array( $this, 'buddyforms_geo_my_wp_update_post_meta' ), 11, 2 );

	}

	public function buddyforms_geo_my_wp_update_post_meta( $customfield, $post_id ) {
		if ( $customfield['type'] == 'geo_my_wp_address' ) {
			if ( isset( $customfield['slug'] ) ) {
				$slug = $customfield['slug'];
			}

			if ( empty( $slug ) ) {
				$slug = sanitize_title( $customfield['name'] );
			}

			if ( ! isset( $_POST['form_slug'] ) ) {
				return;
			}

			$form_slug = sanitize_title( $_POST['form_slug'] );
			$user_id   = get_current_user_id();
			if ( empty( $user_id ) ) {
				$user_id = 0;
			}

			$amount_of_fields = 1;
			if ( ! empty( $_POST['geo_my_wp_address_count'] ) ) {
				$amount_of_fields = intval( $_POST['geo_my_wp_address_count'] );
			}
			for ( $i = 0; $i <= $amount_of_fields; $i ++ ) {
				$internal_slug = $slug . '_' . $i;
				if ( ! empty( $_POST[ $internal_slug ] ) && ! empty( $_POST[ $internal_slug . '_lat' ] ) && ! empty( $_POST[ $internal_slug . '_lng' ] ) && ! empty( $_POST[ $internal_slug . '_data' ] ) ) {
					$string_value = buddyforms_sanitize( $customfield['type'], $_POST[ $internal_slug ] );
					$lat_value    = buddyforms_sanitize( $customfield['type'], $_POST[ $internal_slug . '_lat' ] );
					$lng_value    = buddyforms_sanitize( $customfield['type'], $_POST[ $internal_slug . '_lng' ] );
					$data_value   = buddyforms_sanitize( $customfield['type'], $_POST[ $internal_slug . '_data' ] );

					if ( ! empty( $string_value ) ) {
						if ( BuddyFormsGeoMyWpElement::get_buddyforms_form_type( $form_slug ) !== 'registration' ) {
							update_post_meta( $post_id, $internal_slug, $string_value );
						} else {
							update_user_meta( $user_id, $internal_slug, $string_value );
						}
					}
					if ( ! empty( $lat_value ) ) {
						if ( BuddyFormsGeoMyWpElement::get_buddyforms_form_type( $form_slug ) !== 'registration' ) {
							update_post_meta( $post_id, $internal_slug . '_lat', $lat_value );
						} else {
							update_user_meta( $user_id, $internal_slug . '_lat', $lat_value );
						}
					}
					if ( ! empty( $lng_value ) ) {
						if ( BuddyFormsGeoMyWpElement::get_buddyforms_form_type( $form_slug ) !== 'registration' ) {
							update_post_meta( $post_id, $internal_slug . '_lng', $lng_value );
						} else {
							update_user_meta( $user_id, $internal_slug . '_lng', $lng_value );
						}
					}
					if ( ! empty( $data_value ) ) {
						if ( BuddyFormsGeoMyWpElement::get_buddyforms_form_type( $form_slug ) !== 'registration' ) {
							update_post_meta( $post_id, $internal_slug . '_data', $data_value );
						} else {
							update_user_meta( $user_id, $internal_slug . '_data', $data_value );
						}
						$data_value = (array) json_decode( stripslashes_deep( $data_value ), true );
					}

					if ( defined( 'GMW_PT_PATH' ) && ! empty( $data_value ) ) {
						//include the update location file file
						include_once( GMW_PT_PATH . '/includes/gmw-pt-update-location.php' );
						//make sure the file included and the function exists
						if ( ! function_exists( 'gmw_pt_update_location' ) ) {
							return;
						}
						global $form_slug, $buddyforms;
						$type = 'post';
						$id   = $post_id;
						if ( isset( $buddyforms[ $form_slug ] ) && $buddyforms[ $form_slug ]['form_type'] === 'registration' ) {
							$type = 'user';
							$id   = $user_id;
						}
						$location = array(
							'lat' => $lat_value,
							'lng' => $lng_value
						);

						$address_components = array(
							'street_number' => array( 'short_name', 'street_number' ),
							'street_name'   => array( 'long_name', 'route' ),
							'street'        => array( 'short_name', 'route' ),
							'city'          => array( 'long_name', 'administrative_area_level_2' ),
							'region_name'   => array( 'long_name', 'administrative_area_level_1' ),
							'region_code'   => array( 'short_name', 'administrative_area_level_1' ),
							'country_name'  => array( 'long_name', 'country' ),
							'country_code'  => array( 'short_name', 'country' ),
							'county'        => array( 'long_name', 'country' ),
							'postcode'      => array( 'short_name', 'postal_code' ),
						);

						$address = array();
						foreach ( $data_value['address_components'] as $address_item ) {
							$address_item = (array) $address_item;
							foreach ( $address_components as $target => $source ) {
								if ( $address_item['types'][0] === 'sublocality_level_1' || $address_item['types'][0] === 'locality' ) {
									$address['neighborhood'] = $address_item[ $source[0] ];
								} else if ( $address_item['types'][0] === $source[1] ) {
									$address[ $target ] = $address_item[ $source[0] ];
								}
							}
						}

						// collect location data into array
						$location_data = array(
							'object_type'       => $type,
							'object_id'         => $id,
							'user_id'           => $user_id,
							'latitude'          => $lat_value,
							'longitude'         => $lng_value,
							'premise'           => '',
							'address'           => is_array( $location ) ? implode( ' ', $location ) : $location,
							'formatted_address' => $data_value['formatted_address'],
							'place_id'          => $data_value['place_id']
						);

						$location_data = array_merge( $location_data, $address );

						// Save information to database
						self::add_new_address( $location_data );
					}
				} else {
					//Check if the value exist in db. If exist and it not come trough post then remove it
					if ( BuddyFormsGeoMyWpElement::get_buddyforms_form_type( $form_slug ) !== 'registration' ) {
						$meta = get_post_meta( $post_id, $internal_slug, true );
					} else {
						$meta = get_user_meta( $user_id, $internal_slug, true );
					}

					if ( $meta !== false ) {
						if ( BuddyFormsGeoMyWpElement::get_buddyforms_form_type( $form_slug ) !== 'registration' ) {
							$this->delete_meta($post_id, $internal_slug);
						} else {
							$this->delete_meta($user_id, $internal_slug, 'delete_user_meta');
						}
						$amount_of_fields--;
					}
				}
				if ( BuddyFormsGeoMyWpElement::get_buddyforms_form_type( $form_slug ) !== 'registration' ) {
					update_post_meta( $post_id, $slug . '_count', $amount_of_fields );
				} else {
					update_user_meta( $user_id, $slug . '_count', $amount_of_fields );
				}
			}
		}
	}

	public function delete_meta( $id, $slug, $type = 'delete_post_meta' ) {
		$type( $id, $slug );
		$type( $id, $slug . '_lat' );
		$type( $id, $slug . '_lng' );
		$type( $id, $slug . '_data' );
	}

	/**
	 * Save location - Save a location to gmw_locations database table.
	 * IMPORTANT: This function not check for existing location.
	 *
	 * @param  array $args array of location fields and data.
	 *
	 * @return int location ID
	 */
	public static function add_new_address( $args ) {

		// verify object ID
		if ( ! GMW_Location::verify_id( $args['object_id'] ) ) {

			trigger_error( 'Trying to update a location using invalid object ID.', E_USER_NOTICE );

			return false;
		}

		// verify valid coordinates
		if ( ! is_numeric( $args['latitude'] ) || ! is_numeric( $args['longitude'] ) ) {

			trigger_error( 'Trying to update a location using invalid coordinates.', E_USER_NOTICE );

			return false;
		}

		// parse location args with default location args
		$location_data = wp_parse_args( $args, GMW_Location::default_values() );

		// verify country code
		if ( empty( $location_data['country_code'] ) || strlen( $location_data['country_code'] ) != 2 ) {

			if ( empty( $location_data['country_name'] ) ) {

				$location_data['country_code'] = '';

			} else {

				// get list of countries code. We will use it to make sure that the only the country code passes to the column.
				$countries = gmw_get_countries_list_array();

				// look for the country code based on the country name
				$country_code = array_search( ucwords( $location_data['country_name'] ), $countries );

				// get the country code from the list
				$location_data['country_code'] = ! empty( $country_code ) ? $country_code : '';
			}
		}

		// verify user ID
		if ( ! GMW_Location::verify_id( $location_data['user_id'] ) ) {

			trigger_error( 'Trying to update a location using invalid user ID.', E_USER_NOTICE );

			return false;
		}

		global $wpdb;

		$table = GMW_Location::get_table();

		// insert new location if not already exists in database

		// udpate the current data - time
		$location_data['created'] = current_time( 'mysql' );

		// insert new location to database
		$wpdb->insert( $table, $location_data, GMW_Location::$format );

		// get the new location ID
		$location_id = $wpdb->insert_id;

		$updated = false;

		// append Location ID to location data array
		$location_data = array( 'ID' => $location_id ) + $location_data;

		// make it into an object
		$location_data = (object) $location_data;

		// do some custom functions once location saved
		do_action( 'gmw_save_location', $location_id, $location_data, $updated );
		do_action( "gmw_save_{$location_data->object_type}_location", $location_id, $location_data, $updated );

		// set updated location in cache
		//wp_cache_set( $location_id, $location_data, 'gmw_locations' );
		wp_cache_set( $location_data->object_type . '_' . $location_data->object_id, $location_data, 'gmw_location' );
		wp_cache_set( $location_id, $location_data, 'gmw_location' );

		wp_cache_delete( $location_data->object_type . '_' . $location_data->object_id, 'gmw_locations' );

		return $location_id;
	}
}