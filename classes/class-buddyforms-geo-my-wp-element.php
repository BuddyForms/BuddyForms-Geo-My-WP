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
		add_filter( 'buddyforms_create_edit_form_display_element', array( $this, 'buddyforms_create_new_form_field' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 99 );
		add_action( 'buddyforms_front_js_css_after_enqueue', array( $this, 'wp_enqueue_scripts' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 99 );
		add_action( 'buddyforms_update_post_meta', array( $this, 'buddyforms_geo_my_wp_update_post_meta' ), 11, 2 );
	}

	/**
	 * Update address related meta
	 *
	 * @param $customfield
	 * @param $post_id
	 */
	public function buddyforms_geo_my_wp_update_post_meta( $customfield, $post_id ) {
		if ( $customfield['type'] == 'geo_my_wp_address' ) {
			if ( isset( $customfield['slug'] ) ) {
				$slug = $customfield['slug'];
			}

			if ( empty( $slug ) ) {
				$slug = buddyforms_sanitize_slug( $customfield['name'] );
			}

			if ( empty( $slug ) ) {
				return;
			}

			$form_slug = isset( $_POST['form_slug'] ) ? buddyforms_sanitize_slug( $_POST['form_slug'] ) : '';
			if ( empty( $form_slug ) ) {
				$form_slug = isset( $_POST['_bf_form_slug'] ) ? buddyforms_sanitize_slug( $_POST['_bf_form_slug'] ) : '';
			}

			if ( empty( $form_slug ) ) {
				return;
			}

			global $buddyforms;

			$user_id = get_current_user_id();//Ensure the user modify the data belong to him;

			$form_type = BuddyFormsGeoMyWpElement::get_buddyforms_form_type( $form_slug );
			$type      = 'post';
			$id        = $post_id;
			if ( isset( $buddyforms[ $form_slug ] ) && 'registration' === $form_type ) {
				$type                    = 'user';
				$bf_registration_user_id = get_post_meta( $post_id, '_bf_registration_user_id', true );
				if ( ! empty( $bf_registration_user_id ) ) {
					$user_id = $bf_registration_user_id;
				} else {
					update_post_meta( $post_id, '_bf_registration_user_id', $user_id );
				}
			}

			$field_data_string = 0;
			if ( ! empty( $_POST[ 'bf_' . $slug . '_count' ] ) ) {
				$field_data_string = $_POST[ 'bf_' . $slug . '_count' ];
			}

			if ( ! empty( $field_data_string ) ) {
				$field_data = json_decode( stripslashes_deep( $field_data_string ), true );
				if ( is_array( $field_data ) && count( $field_data ) > 0 ) {
					$new_field_data = array();
					$field_data     = array_unique( $field_data, SORT_REGULAR );
					foreach ( $field_data as $field_datum ) {
						if ( ! empty( $field_datum['field'] ) ) {
							$internal_slug = $field_datum['field'];
							if ( ! empty( $_POST[ $internal_slug . '_data' ] ) ) {
								$data_value = buddyforms_sanitize( $customfield['type'], $_POST[ $internal_slug . '_data' ] );
								$data_value = (array) json_decode( stripslashes_deep( $data_value ), true );

								if ( empty( $data_value['delete'] ) ) {

									$lat_value = isset( $data_value['location']['lat'] ) ? $data_value['location']['lat'] : 0;
									$lng_value = isset( $data_value['location']['lng'] ) ? $data_value['location']['lng'] : 0;

									if ( ! empty( $data_value ) && ! empty( $lat_value ) && ! empty( $lng_value ) ) {
										//include the update location file file
										include_once( GMW_PLUGINS_PATH . '/posts-locator/includes/gmw-posts-locator-functions.php' );
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

										$data_value['post_id']     = $post_id;
										$data_value['user_id']     = $user_id;
										$data_value['location_id'] = $location_id;
										$data_value['form_type']   = $form_type;

										$temp_data        = new stdClass();
										$temp_data->field = $internal_slug;
										$temp_data->data  = $data_value;
										$new_field_data[] = $temp_data;
										unset( $temp_data );
									}
								} else {
									if ( ! empty( $data_value['delete'] ) ) {
										$this->delete( $type, $data_value['delete'] );
									}
								}
							}
						}
					}
					if ( count( $new_field_data ) > 0 ) {
						if ( 'registration' !== $form_type ) {
							update_post_meta( $post_id, 'bf_' . $slug . '_count', $new_field_data );
						} else {
							update_user_meta( $user_id, 'bf_' . $slug . '_count', $new_field_data );
						}
					} else {
						if ( 'registration' !== $form_type ) {
							delete_post_meta( $post_id, 'bf_' . $slug . '_count' );
						} else {
							delete_user_meta( $user_id, 'bf_' . $slug . '_count' );
						}
					}
				}
			}
		}
	}

	/**
	 * Validate Address related information
	 *
	 * @param $args
	 *
	 * @return array|bool
	 */
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

		if ( is_user_logged_in() ) {
			// verify user ID
			if ( ! GMW_Location::verify_id( $location_data['user_id'] ) ) {

				trigger_error( 'Trying to update a location using invalid user ID.', E_USER_NOTICE );

				return false;
			}
		}

		return $location_data;
	}

	/**
	 * Save location - Save a location to gmw_locations database table.
	 * IMPORTANT: This function not check for existing location.
	 *
	 * @param array $args array of location fields and data.
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

	/**
	 * Get buddyforms form type
	 *
	 * @param $form_slug
	 *
	 * @return string
	 */
	public static function get_buddyforms_form_type( $form_slug ) {
		global $buddyforms;
		if ( ! empty( $form_slug ) && isset( $buddyforms[ $form_slug ]['form_type'] ) ) {
			return $buddyforms[ $form_slug ]['form_type'];
		} else {
			return '';
		}
	}

	/**
	 * Build the fields inside the form
	 *
	 * @param Form $form
	 * @param array $form_args
	 *
	 * @return mixed
	 */
	public function buddyforms_create_new_form_field( $form, $form_args ) {
		$customfield = array();
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
				$slug = buddyforms_sanitize_slug( $customfield['slug'] );
			}

			if ( empty( $slug ) ) {
				$slug = buddyforms_sanitize_slug( $customfield['name'] );
			}

			$description = '';
			if ( isset( $customfield['description'] ) ) {
				$description = stripcslashes( $customfield['description'] );
			}

			if ( ! isset( $customfield['name'] ) ) {
				$customfield['name'] = '';
			} else {
				$customfield['name'] = esc_attr( $customfield['name'] );
			}

			if ( ! isset( $customfield['custom_class'] ) ) {
				$customfield['custom_class'] = '';
			}

			if ( ! isset( $customfield['default'] ) ) {
				$customfield['default'] = '';
			}

			$description = apply_filters( 'buddyforms_form_field_description', $description, $post_id );

			global $buddyforms;

			$labels_layout = isset( $buddyforms[ $form_slug ]['layout']['labels_layout'] ) ? $buddyforms[ $form_slug ]['layout']['labels_layout'] : 'inline';
			$is_multiple   = ! empty( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['is_multiple'][0] ) && $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['is_multiple'][0] === 'true';

			if ( empty( $is_multiple ) ) {
				$customfield['custom_class'] .= ' is-single';
			}

			$label_string = '';
			if ( $labels_layout != 'inline' ) {
				$label_string .= sprintf( "<label for=\"_%s\"> %s", esc_attr( $slug ), $customfield['name'] );
				if ( isset( $customfield['required'] ) ) {
					$label_string .= sprintf("<span class='required'>%s</span>", $form->getRequiredSignal());
				}
				$label_string .= '</label>';
			} else {
				$required = '';
				if ( isset( $customfield['required'] ) ) {
					$required = $form->getRequiredPlainSignal();
				}
				$customfield['name'] .= $required;
			}
			$customfield['slug']      = $slug;
			$customfield['form_slug'] = $form_slug;
			wp_localize_script( 'buddyforms-geo-field', 'buddyforms_geo_field', $customfield );

			//Build the base field to hide in the front to generate the others fields.
			$field_group_string = '<div class="bf_field_group">' . $label_string;
			$field_group_string .= $this->get_container_with_field( 0, $slug, 0, $customfield, $field_id, $description, $is_multiple );
			$field_group_string .= '</div>';
			$form->addElement( new Element_HTML( $field_group_string ) );
			$field_data = array();
			if ( $form_type !== 'registration' ) {
				$field_data = get_post_meta( $post_id, 'bf_' . $slug . '_count', true );
			} else {
				$bf_registration_user_id = get_post_meta( $post_id, '_bf_registration_user_id', true );
				if ( ! empty( $bf_registration_user_id ) ) {
					$field_data = get_user_meta( $bf_registration_user_id, 'bf_' . $slug . '_count', true );
				}
			}
			if ( ! empty( $field_data ) ) {
				//Hidden field with the fields data
				$field_data = wp_json_encode( $field_data );
			}
			$form->addElement( new Element_Hidden( 'bf_' . $slug . '_count', $field_data, array( 'field_name' => $slug, 'data-rule-address-required' => true ) ) );
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
	 * @param $is_multiple
	 *
	 * @return string
	 */
	public function get_container_with_field( $i, $slug, $related_id, $custom_field, $field_id, $description, $is_multiple ) {
		$field_group_string = '<div class="bf-geo-address-fields bf-geo-address-example">';
		$field_group_string .= '<div class="container-for-geo-address-field ' . ( empty( $is_multiple ) ? 'is-single' : '' ) . '">';
		$field_group_string .= $this->get_address_elements( $slug, $related_id, $custom_field['default'], $field_id, $custom_field['name'], $description, $custom_field['custom_class'], $custom_field );
		$field_group_string .= '</div>';
		$field_group_string .= '<div class="container-for-geo-address-controls">';
		$field_group_string .= sprintf( "<span><a title=\"%s\" class=\"bf-geo-address-clean-control\"><i class=\"dashicons dashicons-update-alt\" title=\"%s\"></i></a></span>", __( 'Clean your location', 'buddyforms_geo_my_wp_locale' ), __( 'Clean your location', 'buddyforms_geo_my_wp_locale' ) );;
		$field_group_string .= sprintf( "<span><a title=\"%s\" class=\"bf-geo-address-user-location\"><i class=\"dashicons dashicons-location\" title=\"%s\"></i></a></span>", __( 'Get you current location', 'buddyforms_geo_my_wp_locale' ), __( 'Get you current location', 'buddyforms_geo_my_wp_locale' ) );;
		$field_group_string .= '<p class="bfgmw-action gmw-lf-field group_actions message-field message gmw-lf-form-action error" id="gmw-lf-action-message"><i class="gmw-icon-spin"></i><i class="gmw-icon-cancel"></i><i class="gmw-icon-ok-light"></i></p>';
		if ( ! empty( $is_multiple ) ) {
			$field_group_string .= "<p class='bfgmw-action'><a class='geo-address-field-add' field_name='{$slug}' data-default-value='{$custom_field['default']}' data-description='{$description}'><span class='dashicons dashicons-plus'></span></a></p>";
		}
		$field_group_string .= "<p class='bfgmw-action'><a class='geo-address-field-delete' field_name='{$slug}' data-default-value='{$custom_field['default']}' data-description='{$description}'><span class='dashicons dashicons-minus'></span></a></p>";
		$field_group_string .= '</div>';
		$field_group_string .= '</div>';

		return $field_group_string;
	}

	/**
	 * Get the Address field with the hidden field
	 *
	 * @param        $slug
	 * @param int $related_id
	 * @param string $default_value
	 * @param        $field_id
	 * @param        $name
	 * @param        $description
	 * @param        $classes
	 *
	 * @return string
	 */
	public function get_address_elements( $slug, $related_id, $default_value, $field_id, $name, $description, $classes, $custom_field ) {
		$name = apply_filters( 'buddyforms_form_field_geo_my_wp_address_name', stripcslashes( $name ), $slug, $related_id );

		$element_attr = array(
			'id'                 => str_replace( "-", "", $slug ),
			'value'              => '',
			'class'              => $classes . ' settings-input address-field address bf-address-autocomplete bf-address-autocomplete-example',
			'shortDesc'          => $description,
			'field_id'           => $field_id,
			'field_name'         => $name,
			'autocomplete'       => 'nope',
			'data-description'   => $description,
			'data-default-value' => $default_value,
			'data-form'          => $custom_field['form_slug'],
		);

		if ( isset( $custom_field['required'] ) ) {
			$element_attr['data-rule-address-required'] = "true";
		}

		$text_box = new Element_Textbox( $name, $slug, $element_attr, $custom_field );
		$hidden_data = new Element_Hidden( $slug . '_data', '' );

		ob_start();
		$text_box->render();
		$hidden_data->render();
		$html = ob_get_clean();

		return $html;
	}

	public function wp_enqueue_scripts( $content ) {
		global $buddyforms, $form_slug, $post;

		$form_slug = '';
		global $wp_query;
		if ( ! empty( $wp_query->query_vars['bf_form_slug'] ) ) {
			$form_slug = buddyforms_sanitize_slug( $wp_query->query_vars['bf_form_slug'] );
		} else if ( ! empty( $_GET['form_slug'] ) ) {
			$form_slug = buddyforms_sanitize_slug( $_GET['form_slug'] );
		} else if ( ! empty( $wp_query->query_vars['form_slug'] ) ) {
			$form_slug = buddyforms_sanitize_slug( $wp_query->query_vars['form_slug'] );
		} else if ( function_exists( 'bp_current_component' ) && function_exists( 'bp_current_action' ) && function_exists( 'buddyforms_members_get_form_by_member_type' ) ) {
			global $buddyforms_member_tabs;
			$bp_action    = bp_current_action();
			$bp_component = bp_current_component();
			if ( ! empty( $buddyforms_member_tabs ) && 'xprofile' !== $bp_component ) {
				$form_slug = ! empty( $buddyforms_member_tabs[ bp_current_component() ][ bp_current_action() ] ) ? $buddyforms_member_tabs[ bp_current_component() ][ bp_current_action() ] : '';
				if ( $form_slug . '-create' !== $bp_action && $form_slug . '-edit' !== $bp_action && $form_slug . '-revision' !== $bp_action ) {
					$member_type = bp_get_member_type( get_current_user_id() );
					$form_slug   = buddyforms_members_get_form_by_member_type( $member_type );
					if ( ! $form_slug ) {
						$form_slug = buddyforms_members_get_form_by_member_type( 'none' );
					}
				}
			}
		} else if ( ! empty( $post ) ) {
			$post_content = ! empty( $content ) ? $content : $post->post_content;
			if ( ! empty( $post->post_name ) && $post->post_type === 'buddyforms' ) {
				$form_slug = $post->post_name;
			} else if ( ! empty( $post_content ) ) {
				//Extract the shortcode inside the content
				$form_slug = buddyforms_get_form_slug_from_content( $post_content );
				if ( empty( $form_slug ) ) {
					$form_slug = buddyforms_get_form_slug_by_post_id( $post->ID );
				}
			}
		}

		if ( empty( $buddyforms[ $form_slug ] ) ) {
			return;
		}

		$exist = buddyforms_exist_field_type_in_form( $form_slug, 'geo_my_wp_address' );

		if ( ! $exist ) {
			return;
		}

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
		wp_register_script( 'buddyforms-geo-field', $js_asset, array( "jquery" ), BuddyFormsGeoMyWpManager::get_version() );
		wp_register_style( 'buddyforms-geo-field', $css_asset, array(), BuddyFormsGeoMyWpManager::get_version() );

		//enqueue google maps api if not already enqueued
		if ( ! wp_script_is( 'google-maps' ) ) {
			wp_enqueue_script( 'google-maps' );
		}

		$args = array(
			'admin_url'     => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'buddyforms-geo-field' ),
			'country_code'  => 'us',
			'language_code' => 'en',
		);

		$field_args = array();
		if ( ! empty( $buddyforms[ $form_slug ]['form_fields'] ) ) {
			foreach ( $buddyforms[ $form_slug ]['form_fields'] as $field_id => $field_data ) {
				if ( $field_data['type'] === 'geo_my_wp_address' ) {
					$field_args[ $field_id ] = array(
						'is_multi'                      => ! empty( $field_data['is_multiple'][0] ) && $field_data['is_multiple'][0] === 'true',
						'is_load_user_location_enabled' => ! empty( $field_data['is_load_user_location_enabled'][0] ) && $field_data['is_load_user_location_enabled'][0] === 'true',
						'is_user_location_icon_enabled' => ! empty( $field_data['is_user_location_icon_enabled'][0] ) && $field_data['is_user_location_icon_enabled'][0] === 'true',
						'is_clean_enabled'              => ! empty( $field_data['is_clean_enabled'][0] ) && $field_data['is_clean_enabled'][0] === 'true',
						'validation_error_message'      => ! empty( $field_data['validation_error_message'] ) ? $field_data['validation_error_message'] : __( 'This Field is required', 'buddyforms_geo_my_wp_locale' )
					);
				}
			}
		}

		if ( ! empty( $field_args ) ) {
			$args['fields'] = $field_args;
		}

		wp_enqueue_script( 'buddyforms-geo-field' );
		wp_localize_script( 'buddyforms-geo-field', 'buddyforms_geo_field', $args );
		wp_enqueue_style( 'buddyforms-geo-field' );
	}
}
