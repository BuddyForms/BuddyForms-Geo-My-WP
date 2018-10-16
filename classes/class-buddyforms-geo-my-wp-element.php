<?php
/*
 * @package WordPress
 * @subpackage BuddyForms
 * @author ThemKraft Dev Team
 * @copyright 2018 Themekraft
 * @link http://buddyforms.com
 * @license GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BuddyFormsGeoMyWpElement {

	private $load_script;

	public function __construct() {
		add_filter( 'buddyforms_create_edit_form_display_element', array( $this, 'buddyforms_woocommerce_create_new_form_builder' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 99 );
		add_action( 'wp_ajax_get_new_bf_address_field', array( $this, 'ajax_get_field_row' ) );
		add_action( 'buddyforms_update_post_meta', array( $this, 'buddyforms_geo_my_wp_update_post_meta' ), 11, 2 );
		add_action( 'wp_ajax_delete_bf_address_field', array( $this, 'ajax_delete_field_row' ) );
	}

	public function ajax_delete_field_row() {
		try {
			if ( ! ( is_array( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				return;
			}
			$is_valid = wp_verify_nonce( $_POST['_nonce'], 'buddyforms-geo-field' );
			if ( ! isset( $_POST['action'] ) || ! isset( $_POST['_nonce'] ) || ! $is_valid || ! isset( $_POST['post_id'] ) || ! isset( $_POST['field_number'] ) || ! isset( $_POST['field_name'] ) || ! isset( $_POST['form_slug'] ) || ! isset( $_POST['location_id'] ) ) {
				die( 1 );
			}

			$id        = intval( $_POST['post_id'] );
			$form_slug = sanitize_text_field( $_POST['form_slug'] );

			$form_type = BuddyFormsGeoMyWpElement::get_buddyforms_form_type( $form_slug );

			if ( 'registration' === $form_type ) {
				$id = get_current_user_id();
			}

			$field_number = intval( $_POST['field_number'] );

			$name = sanitize_title( sanitize_text_field( $_POST['field_name'] ) );
			$slug = sanitize_title( $name ) . '_' . ( $field_number );

			$location_id = 0;
			if ( 'registration' !== $form_type ) {
				$meta = get_post_meta( $id, $slug . '_data', true );
			} else {
				$meta = get_user_meta( $id, $slug . '_data', true );
			}

			if ( isset( $meta['location_id'] ) ) {
				$location_id = $meta['location_id'];
			}

			if ( ! empty( $location_id ) ) {
				$del3 = $this->delete( 'registration' === $form_type ? 'user' : 'post', $location_id );
			}

			$remove_result = $this->delete_address_element( $id, $slug, $form_slug );

			$field_number --;

			if ( $field_number >= 0 ) {
				if ( 'registration' !== $form_type ) {
					update_post_meta( $id, $name . '_count', $field_number );
				} else {
					update_user_meta( $id, $name . '_count', $field_number );
				}
			}

			$result           = array();
			$result['result'] = $remove_result;
			$result['count']  = $field_number;
			$result['name']   = $slug;

			wp_send_json( $result );
		} catch ( Exception $ex ) {
			BuddyFormsGeoMyWpLog::log( array(
				'action'         => 'ajax_delete_field_row',
				'object_type'    => BuddyFormsGeoMyWpManager::get_slug(),
				'object_subtype' => 'BuddyFormsGeoMyWpElement',
				'object_name'    => $ex->getMessage(),
			) );
		}
		die();
	}

	public function delete_address_element( $related_id, $slug, $form_slug ) {
		$result = false;
		if ( ! empty( $related_id ) ) {
			if ( BuddyFormsGeoMyWpElement::get_buddyforms_form_type( $form_slug ) !== 'registration' ) {
				$del1 = delete_post_meta( $related_id, $slug );
				$del2 = delete_post_meta( $related_id, $slug . '_data' );
			} else {
				$del1 = delete_user_meta( $related_id, $slug );
				$del2 = delete_user_meta( $related_id, $slug . '_data' );
			}

			$result = ( $del1 && $del2 );
		}

		return $result;
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

						$location_exist = ( ! empty( $data_value['location_id'] ) ) ? GMW_Location::get_by_id( $data_value['location_id'] ) : 0;

						//Check if the information need to be updated or created
						if ( ! empty( $location_exist ) ) {
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
						$this->delete( $type, $meta['location_id'] );
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

	/**
	 * Delete an item from the gmw location table.
	 *
	 * @param string $object_type
	 * @param int $object_id
	 *
	 * @return bool
	 */
	private function delete( $object_type = '', $object_id = 0 ) {

		// verify data
		if ( empty( $object_type ) || empty( $object_id ) ) {
			return false;
		}

		// verify object type
		if ( ! in_array( $object_type, GMW()->object_types ) ) {

			trigger_error( 'Trying to delete a location using invalid object type.', E_USER_NOTICE );

			return false;
		}

		// verify object ID
		if ( ! is_numeric( $object_id ) || ! absint( $object_id ) ) {

			trigger_error( 'Trying to delete a location using invalid object ID.', E_USER_NOTICE );

			return false;
		}

		$object_id = absint( $object_id );

		// get location to make sure it exists
		// this will get the parent location
		$location = GMW_Location::get_by_id( $object_id );

		// abort if no location found
		if ( empty( $location ) ) {
			return false;
		}

		do_action( 'gmw_before_location_deleted', $location->ID, $location );
		do_action( 'gmw_before_' . $object_type . '_location_deleted', $location->ID, $location );

		global $wpdb;

		// delete location from database
		$table   = GMW_Location::get_table();
		$deleted = $wpdb->delete( $table, array( 'ID' => $location->ID ), array( '%d' ) );

		// abort if failed to delete
		if ( empty( $deleted ) ) {
			return false;
		}

		do_action( 'gmw_location_deleted', $location->ID, $location );
		do_action( 'gmw_' . $object_type . '_location_deleted', $location->ID, $location );

		// clear locations from cache
		wp_cache_delete( $object_type . '_' . $object_id, 'gmw_location' );
		wp_cache_delete( $location->ID, 'gmw_location' );
		wp_cache_delete( $object_type . '_' . $object_id, 'gmw_locations' );

		return $location->ID;
	}

	public static function get_buddyforms_form_type( $form_slug ) {
		global $buddyforms;
		if ( ! empty( $form_slug ) && isset( $buddyforms[ $form_slug ]['form_type'] ) ) {
			return $buddyforms[ $form_slug ]['form_type'];
		} else {
			return '';
		}
	}

	public function ajax_get_field_row() {
		try {
			if ( ! ( is_array( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				return;
			}
			$is_valid = wp_verify_nonce( $_POST['_nonce'], 'buddyforms-geo-field' );
			if ( ! isset( $_POST['action'] ) || ! isset( $_POST['_nonce'] ) || ! $is_valid || ! isset( $_POST['form_slug'] ) ) {
				die( 1 );
			}

			$field_number  = intval( $_POST['field_number'] ) + 1;
			$description   = sanitize_text_field( $_POST['description'] );
			$default_value = sanitize_text_field( $_POST['default_value'] );
			$name          = sanitize_text_field( $_POST['field_name'] );

			$slug            = sanitize_title( $name ) . '_' . ( $field_number );
			$field_id        = sanitize_text_field( $_POST['field_id'] );
			$customfield     = array(
				'default' => $default_value,
				'name'    => $name
			);
			$form_type       = sanitize_text_field( $_POST['form_slug'] );
			$result          = array();
			$result['html']  = $this->get_container_with_field( $field_number, $slug, 0, $customfield, $field_id, $description, $form_type );
			$result['count'] = $field_number;
			$result['name']  = $slug;

			wp_send_json( $result );
		} catch ( Exception $ex ) {
			BuddyFormsGeoMyWpLog::log( array(
				'action'         => 'ajax_get_field_row',
				'object_type'    => BuddyFormsGeoMyWpManager::get_slug(),
				'object_subtype' => 'BuddyFormsGeoMyWpElement',
				'object_name'    => $ex->getMessage(),
			) );
		}
		die();
	}

	/**
	 * @param Form $form
	 * @param array $form_args
	 *
	 * @return mixed
	 */
	public function buddyforms_woocommerce_create_new_form_builder( $form, $form_args ) {
		$customfield = false;
		$post_id     = 0;
		$field_id    = '';
		$form_slug   = '';
		extract( $form_args );

		if ( ! isset( $customfield['type'] ) ) {
			return $form;
		}
		if ( $customfield['type'] === 'geo_my_wp_address' && ! empty( $form_slug ) ) {
			$form_type         = self::get_buddyforms_form_type( $form_slug );
			$this->load_script = true;

			if ( isset( $customfield['slug'] ) ) {
				$slug = sanitize_title( $customfield['slug'] );
			}

			if ( empty( $slug ) ) {
				$slug = sanitize_title( $customfield['name'] );
			}

			$description = '';
			if ( isset( $customfield['description'] ) ) {
				$description = stripcslashes( $customfield['description'] );
			}

			$user_id = get_current_user_id();

			$description = apply_filters( 'buddyforms_form_field_description', $description, $post_id );

			if ( $form_type !== 'registration' ) {
				$field_count = get_post_meta( $post_id, $slug . '_count', true );
			} else {
				$field_count = get_user_meta( $user_id, $slug . '_count', true );
			}
			if ( empty( $field_count ) ) {
				$field_count = 0;
			}

			for ( $i = 0; $i <= $field_count; $i ++ ) {
				$internal_slug = $slug . '_' . $i;
				if ( ! isset( $customfield['name'] ) ) {
					$customfield['name'] = '';
				}

				if ( ! isset( $customfield['default'] ) ) {
					$customfield['default'] = '';
				}

				$field_group_string = $this->get_container_with_field( $i, $internal_slug, ( $form_type !== 'registration' ) ? $post_id : $user_id, $customfield, $field_id, $description, $form_type );
				$form->addElement( new Element_HTML( $field_group_string ) );
			}
			$form->addElement( new Element_Hidden( 'geo_my_wp_address_count', $field_count ) );
		}

		return $form;
	}

	/**
	 * Get the field inside the container
	 *
	 * @param $i
	 * @param $slug
	 * @param $related_id
	 * @param $custom_field
	 * @param $field_id
	 * @param $description
	 * @param $form_type
	 *
	 * @return string
	 */
	public function get_container_with_field( $i, $slug, $related_id, $custom_field, $field_id, $description, $form_type ) {
		$add_classes_for_link    = 'geo-address-field-add';
		$delete_classes_for_link = 'geo-address-field-delete';
		$delete_classes_for_link .= ( $i === 0 ) ? ' geo-address-field-0' : '';

		$field_group_string = '<div class="bf_field_group bf-geo-address-fields">';
		$field_group_string .= '<div class="container-for-geo-address-field">';
		$field_group_string .= $this->get_address_elements( $slug, $related_id, $custom_field['default'], $field_id, $i, $custom_field['name'], $description, $form_type );
		$field_group_string .= '</div>';
		$field_group_string .= '<div class="container-for-geo-address-controls">';
		$field_group_string .= '<p class="gmw-lf-field  group_actions message-field message  gmw-lf-form-action error" id="gmw-lf-action-message"><i class="gmw-icon-spin"></i><i class="gmw-icon-cancel"></i><i class="gmw-icon-ok-light"></i></p>';
		$field_group_string .= "<p><a class='${add_classes_for_link}' field_number='${i}' field_name='{$custom_field['name']}' field_id='{$field_id}' data-default-value='{$custom_field['default']}' data-description='{$description}'><span class='dashicons dashicons-plus'></span></a></p>";
		$field_group_string .= "<p><a class='${delete_classes_for_link}' field_number='${i}' field_name='{$custom_field['name']}' field_id='{$field_id}' data-default-value='{$custom_field['default']}' data-description='{$description}'><span class='dashicons dashicons-minus'></span></a></p>";
		$field_group_string .= '</div>';
		$field_group_string .= '</div>';

		return $field_group_string;
	}

	/**
	 * Get the Address field with the hidden field
	 *
	 * @param $slug
	 * @param int $related_id
	 * @param string $default_value
	 * @param $field_id
	 * @param $count
	 * @param $name
	 * @param $description
	 * @param $form_type
	 *
	 * @return string
	 */
	public function get_address_elements( $slug, $related_id, $default_value, $field_id, $count, $name, $description, $form_type ) {
		if ( ! empty( $related_id ) ) {
			if ( $form_type !== 'registration' ) {
				$custom_field_val      = get_post_meta( $related_id, $slug, true );
				$custom_field_val_data = get_post_meta( $related_id, $slug . '_data', true );
			} else {
				$custom_field_val      = get_user_meta( $related_id, $slug, true );
				$custom_field_val_data = get_user_meta( $related_id, $slug . '_data', true );
			}

		} else {
			$custom_field_val      = '';
			$custom_field_val_data = '';
		}

		if ( empty( $custom_field_val ) && isset( $default_value ) ) {
			$custom_field_val = $default_value;
		}

		if ( empty( $custom_field_val_data ) ) {
			$custom_field_val_data = '';
		}

		$name = apply_filters( 'buddyforms_form_field_geo_my_wp_address_name', stripcslashes( $name ), $slug, $related_id );

		$element_attr = array(
			'id'                 => str_replace( "-", "", $slug ),
			'value'              => $custom_field_val,
			'class'              => 'settings-input address-field address bf-address-autocomplete',
			'shortDesc'          => $description,
			'field_id'           => $field_id,
			'field_name'         => $name,
			'autocomplete'       => 'nope',
			'field_number'       => $count,
			'data-description'   => $description,
			'data-default-value' => $default_value,
		);

		$text_box    = new Element_Textbox( $name, $slug, $element_attr );
		$hidden_data = new Element_Hidden( $slug . '_data', wp_json_encode( $custom_field_val_data ) );

		ob_start();
		$text_box->render();
		$hidden_data->render();
		$html = ob_get_clean();

		return $html;
	}

	public function wp_enqueue_scripts() {
		//register google maps api if not already registered
		if ( ! wp_script_is( 'google-maps', 'registered' ) ) {
			//Build Google API url. elements can be modified via filters
			$protocol    = is_ssl() ? 'https' : 'http';
			$gmw_options = gmw_get_options_group();
			$google_url  = apply_filters( 'gmw_google_maps_api_url', array(
				'protocol' => $protocol,
				'url_base' => '://maps.googleapis.com/maps/api/js?',
				'url_data' => http_build_query( apply_filters( 'gmw_google_maps_api_args', array(
					'libraries' => 'places',
					'key'       => gmw_get_option( 'general_settings', 'google_api', '' ),
					'region'    => gmw_get_option( 'general_settings', 'country_code', 'US' ),
					'language'  => gmw_get_option( 'general_settings', 'language_code', 'EN' ),
				) ), '', '&amp;' ),
			), $gmw_options );

			wp_register_script( 'google-maps', implode( '', $google_url ), array( 'jquery' ), false, true );
		}

		$js_asset  = BuddyFormsGeoMyWpManager::assets_path( 'buddyforms-geo-my-wp' );
		$css_asset = BuddyFormsGeoMyWpManager::assets_path( 'buddyforms-geo-my-wp', 'css' );
		wp_register_script( 'buddyforms-geo-field', $js_asset, array( "jquery" ), BuddyFormsGeoMyWpManager::get_version(), true );
		wp_register_style( 'buddyforms-geo-field', $css_asset, array(), BuddyFormsGeoMyWpManager::get_version() );

		//enqueue google maps api if not already enqueued
		if ( ! wp_script_is( 'google-maps' ) ) {
			wp_enqueue_script( 'google-maps' );
		}

		wp_enqueue_script( 'buddyforms-geo-field' );
		wp_localize_script( 'buddyforms-geo-field', 'buddyforms_geo_field', array(
			'admin_url' => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'buddyforms-geo-field' ),
		) );
		wp_enqueue_style( 'buddyforms-geo-field' );
	}
}
