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

			global $buddyforms;

			$form_slug = sanitize_title( $_POST['form_slug'] );

			$user_id = get_current_user_id();
			if ( empty( $user_id ) ) {
				$user_id = 0;
			}

			$form_type = BuddyFormsGeoMyWpElement::get_buddyforms_form_type( $form_slug );
			$type      = 'post';
			$id        = $post_id;
			if ( isset( $buddyforms[ $form_slug ] ) && 'registration' === $form_type ) {
				$type = 'user';
				$id   = $user_id;
			}

			$amount_of_fields = 0;
			if ( ! empty( $_POST['geo_my_wp_address_count'] ) ) {
				$amount_of_fields = intval( $_POST['geo_my_wp_address_count'] );
			}
			for ( $i = 0; $i <= $amount_of_fields; $i ++ ) {
				$internal_slug = $slug . '_' . $i;
				if ( ! empty( $_POST[ $internal_slug ] ) && ! empty( $_POST[ $internal_slug . '_data' ] ) ) {
					$string_value     = buddyforms_sanitize( $customfield['type'], $_POST[ $internal_slug ] );
					$data_location_id = buddyforms_sanitize( $customfield['type'], $_POST[ $internal_slug ] );
					$data_value       = buddyforms_sanitize( $customfield['type'], $_POST[ $internal_slug . '_data' ] );
					$data_value       = (array) json_decode( stripslashes_deep( $data_value ), true );

					$lat_value = isset( $data_value['location']['lat'] ) ? $data_value['location']['lat'] : 0;
					$lng_value = isset( $data_value['location']['lng'] ) ? $data_value['location']['lng'] : 0;

					if ( defined( 'GMW_PT_PATH' ) && ! empty( $data_value ) && ! empty( $lat_value ) && ! empty( $lng_value ) ) {
						//include the update location file file
						include_once( GMW_PT_PATH . '/includes/gmw-pt-update-location.php' );
						//make sure the file included and the function exists
						if ( ! function_exists( 'gmw_update_location' ) ) {
							return;
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
							'featured'          => apply_filters( 'buddyforms-geo-my-wp-location-featured', 0, $form_slug, $form_type ),
							'address'           => is_array( $location ) ? implode( ' ', $location ) : $location,
							'formatted_address' => $data_value['formatted_address'],
							'place_id'          => $data_value['place_id']
						);

						$location_data = array_merge( $location_data, $address );

						//Check if the information need to be updated or created
						if ( isset( $data_value['location_id'] ) && $this->check_if_location_exist( $data_value['location_id'] ) ) {
							//Update the information
							$location_data['ID'] = $data_value['location_id'];
							$location_id         = $this->update_address( $location_data );
						} else {
							// Save information to database
							$location_id = $this->add_new_address( $location_data );
						}

						if ( ! isset( $location_data['ID'] ) ) {
							// Add Location ID to location data array
							$location_data['ID'] = $location_id;
						}
						//clean cache values
						wp_cache_delete( $location_data['object_type'] . '_' . $location_data['object_id'], 'gmw_locations' );
						wp_cache_delete( $location_id, 'gmw_location' );

						// set updated location in cache
						wp_cache_set( $location_data['object_type'] . '_' . $location_data['object_id'], $location_data, 'gmw_location' );
						wp_cache_set( $location_id, $location_data, 'gmw_location' );

						if ( ! empty( $location_id ) ) {
							if ( ! empty( $string_value ) ) {
								if ( 'registration' !== $form_type ) {
									update_post_meta( $post_id, $internal_slug, $string_value );
								} else {
									update_user_meta( $user_id, $internal_slug, $string_value );
								}
							}

							$data_value['location_id'] = $location_id;
							if ( ! empty( $data_value ) ) {
								if ( 'registration' !== $form_type ) {
									$data_value['post_id'] = $post_id;
									update_post_meta( $post_id, $internal_slug . '_data', $data_value );
								} else {
									$data_value['user_id'] = $user_id;
									update_user_meta( $user_id, $internal_slug . '_data', $data_value );
								}
							}
						}
					}
				} else {
					//Check if the value exist in db. If exist and it not come trough post then remove it
					if ( 'registration' !== $form_type ) {
						$meta = get_post_meta( $post_id, $internal_slug . '_data', true );
					} else {
						$meta = get_user_meta( $user_id, $internal_slug . '_data', true );
					}

					if ( $meta !== false && isset( $meta['location_id'] ) ) {
						if ( 'registration' !== $form_type ) {
							$this->delete_meta( $post_id, $internal_slug );
						} else {
							$this->delete_meta( $user_id, $internal_slug, 'delete_user_meta' );
						}
						//Delete the location from gmw table
						GMW_Location::delete( $type, $meta['location_id'] );
						$amount_of_fields --;
					}
				}
				if ( $amount_of_fields >= 0 ) {
					if ( 'registration' !== $form_type ) {
						update_post_meta( $post_id, $slug . '_count', $amount_of_fields );
					} else {
						update_user_meta( $user_id, $slug . '_count', $amount_of_fields );
					}
				} else {
					if ( 'registration' !== $form_type ) {
						delete_post_meta( $post_id, $slug . '_count' );
					} else {
						delete_user_meta( $user_id, $slug . '_count' );
					}
				}
			}
		}
	}

	public function delete_meta( $id, $slug, $type = 'delete_post_meta' ) {
		$type( $id, $slug );
		$type( $id, $slug . '_data' );
	}

	/**
	 * Check if location exist.
	 *
	 * @param $object_id
	 *
	 * @return bool
	 */
	private function check_if_location_exist( $object_id ) {
		if ( ! GMW_Location::verify_id( $object_id ) ) {
			return false;
		}

		global $wpdb;

		$table     = GMW_Location::get_table();
		$object_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $table WHERE  ID = %d", $object_id ) );

		return ! empty( $object_id ) ? true : false;
	}

	private function validate_address_args( $args ) {
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

		return $location_data;
	}

	/**
	 * Save location - Save a location to gmw_locations database table.
	 * IMPORTANT: This function not check for existing location.
	 *
	 * @param  array $args array of location fields and data.
	 *
	 * @return int location ID
	 */
	private function add_new_address( $args ) {
		$location_data = $this->validate_address_args( $args );

		if ( ! empty( $location_data ) ) {
			global $wpdb;

			$table = GMW_Location::get_table();

			// udpate the current data - time
			$location_data['created'] = current_time( 'mysql' );

			// insert new location to database
			$wpdb->insert( $table, $location_data, GMW_Location::$format );

			// get the new location ID
			$location_id = $wpdb->insert_id;

			return $location_id;
		}

		return false;
	}

	/**
	 * Update the address from gmw location table
	 *
	 * @param array $location_data
	 *
	 * @return bool
	 */
	private function update_address( $location_data ) {
		$location_data = $this->validate_address_args( $location_data );
		if ( ! empty( $location_data ) ) {
			global $wpdb;

			$table = GMW_Location::get_table();

			// get existing location ID
			$location_id = isset( $location_data['ID'] ) ? (int) $location_data['ID'] : 0;

			// verify location ID
			if ( ! is_int( $location_id ) || 0 == $location_id ) {
				return false;
			}

			// Keep created time as its original time
			$last_location_data       = GMW_Location::get_by_id( $location_id );
			$location_data['created'] = $last_location_data->created;

			// updated time based on current time
			$location_data['updated'] = current_time( 'mysql' );

			// update location
			$wpdb->update( $table, $location_data, array( 'ID' => $location_id ), GMW_Location::$format, array( '%d' ) );

			return $location_id;
		}

		return false;
	}

}