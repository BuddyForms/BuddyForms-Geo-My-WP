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
		add_action( 'wp_ajax_get_new_bf_address_field', array( $this, 'ajax_get_field_row' ) );
		add_action( 'wp_ajax_delete_bf_address_field', array( $this, 'ajax_delete_field_row' ) );
	}

	public function ajax_delete_field_row() {
		try {
			if ( ! ( is_array( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				return;
			}
			$is_valid = wp_verify_nonce( $_POST['_nonce'], 'buddyforms-geo-field' );
			if (
				! isset( $_POST['action'] ) || ! isset( $_POST['_nonce'] ) || ! $is_valid
				|| ! isset( $_POST['post_id'] ) || ! isset( $_POST['field_number'] ) || ! isset( $_POST['field_name'] )
				|| ! isset( $_POST['form_slug'] )
			) {
				die( 1 );
			}

			$id       = intval( $_POST['post_id'] );
            $form_slug     = sanitize_text_field( $_POST['form_slug'] );

            if(self::get_buddyforms_form_type( $form_slug ) === 'registration'){
               $id = get_current_user_id();
            }

			$field_number  = intval( $_POST['field_number'] );

			$name          = sanitize_text_field( $_POST['field_name'] );
			$slug          = sanitize_title( $name ) . '_' . ( $field_number );
			$remove_result = $this->delete_address_element( $id, $slug, $form_slug );

			if ( $remove_result ) {
				$field_number --;
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

	public static function get_buddyforms_form_type( $form_slug ) {
		global $buddyforms;
		if ( ! empty( $form_slug ) && isset( $buddyforms[ $form_slug ]['form_type'] ) ) {
			return $buddyforms[ $form_slug ]['form_type'];
		} else {
			return '';
		}
	}

	public function delete_address_element( $related_id, $slug, $form_slug ) {
		$result = false;
		if ( ! empty( $related_id ) ) {
			if ( self::get_buddyforms_form_type( $form_slug ) !== 'registration' ) {
				$del1 = delete_post_meta( $related_id, $slug );
				$del2 = delete_post_meta( $related_id, $slug . '_lat' );
				$del3 = delete_post_meta( $related_id, $slug . '_lng' );
				$del4 = delete_post_meta( $related_id, $slug . '_data' );
			} else {
				$del1 = delete_user_meta( $related_id, $slug );
				$del2 = delete_user_meta( $related_id, $slug . '_lat' );
				$del3 = delete_user_meta( $related_id, $slug . '_lng' );
				$del4 = delete_user_meta( $related_id, $slug . '_data' );
			}
			$result = ( $del1 && $del2 && $del3 && $del4 );
		}

		return $result;
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
		$post_id      = 0;
		$field_id     = '';
		$form_slug    = '';
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
		$field_group_string .= "<a class='${add_classes_for_link}' field_number='${i}' field_name='{$custom_field['name']}' field_id='{$field_id}' data-default-value='{$custom_field['default']}' data-description='{$description}'><span class='dashicons dashicons-plus'></span></a>";
		$field_group_string .= "<a class='${delete_classes_for_link}' field_number='${i}' field_name='{$custom_field['name']}' field_id='{$field_id}' data-default-value='{$custom_field['default']}' data-description='{$description}'><span class='dashicons dashicons-minus'></span></a>";
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
				$custom_field_val_lat  = get_post_meta( $related_id, $slug . '_lat', true );
				$custom_field_val_long = get_post_meta( $related_id, $slug . '_lng', true );
				$custom_field_val_data = get_post_meta( $related_id, $slug . '_data', true );
			} else {
				$custom_field_val      = get_user_meta( $related_id, $slug, true );
				$custom_field_val_lat  = get_user_meta( $related_id, $slug . '_lat', true );
				$custom_field_val_long = get_user_meta( $related_id, $slug . '_lng', true );
				$custom_field_val_data = get_user_meta( $related_id, $slug . '_data', true );
			}

		} else {
			$custom_field_val      = '';
			$custom_field_val_lat  = '';
			$custom_field_val_long = '';
			$custom_field_val_data = '';
		}

		if ( empty( $custom_field_val ) && isset( $default_value ) ) {
			$custom_field_val = $default_value;
		}

		if ( empty( $custom_field_val_lat ) ) {
			$custom_field_val_lat = '';
		}

		if ( empty( $custom_field_val_long ) ) {
			$custom_field_val_long = '';
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
		$hidden_lat  = new Element_Hidden( $slug . '_lat', $custom_field_val_lat );
		$hidden_lng  = new Element_Hidden( $slug . '_lng', $custom_field_val_long );
		$hidden_data = new Element_Hidden( $slug . '_data', $custom_field_val_data );

		ob_start();
		$text_box->render();
		$hidden_lat->render();
		$hidden_lng->render();
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
			$google_url  = apply_filters(
				'gmw_google_maps_api_url', array(
					'protocol' => $protocol,
					'url_base' => '://maps.googleapis.com/maps/api/js?',
					'url_data' => http_build_query(
						apply_filters(
							'gmw_google_maps_api_args', array(
								'libraries' => 'places',
								'key'       => gmw_get_option( 'general_settings', 'google_api', '' ),
								'region'    => gmw_get_option( 'general_settings', 'country_code', 'US' ),
								'language'  => gmw_get_option( 'general_settings', 'language_code', 'EN' ),
							)
						), '', '&amp;'
					),
				)
				, $gmw_options
			);

			wp_register_script( 'google-maps', implode( '', $google_url ), array( 'jquery' ), false, true );
		}

		$js_asset  = BuddyFormsGeoMyWpManager::assets_path( 'buddyforms-geo-my-wp' );
		$css_asset = BuddyFormsGeoMyWpManager::assets_path( 'buddyforms-geo-my-wp', 'css' );
		wp_register_script( 'buddyforms-geo-field', $js_asset, array( "jquery" ), false, true );
		wp_register_style( 'buddyforms-geo-field', $css_asset );

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
