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
		add_action( 'wp_ajax_nopriv_get_new_bf_address_field', array( $this, 'ajax_get_field_row' ) );
	}

	public function ajax_get_field_row() {
		try {
			if ( ! ( is_array( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				return;
			}
			$is_valid = wp_verify_nonce( $_POST['_nonce'], 'buddyforms-geo-field' );
			if ( ! isset( $_POST['action'] ) || ! isset( $_POST['_nonce'] ) || ! $is_valid ) {
				die( 1 );
			}

			$count         = intval( $_POST['count'] );
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
			$result          = array();
			$result['html']  = $this->get_container_with_field( $field_number, $slug, 0, $customfield, $field_id, $description );
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
		extract( $form_args );

		if ( ! isset( $customfield['type'] ) ) {
			return $form;
		}
		if ( $customfield['type'] === 'geo_my_wp_address' && is_user_logged_in() ) {
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

			$description = apply_filters( 'buddyforms_form_field_description', $description, $post_id );

			$field_count = get_post_meta( $post_id, $slug . '_count', true );
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

				$field_group_string = $this->get_container_with_field( $i, $internal_slug, $post_id, $customfield, $field_id, $description );
				$form->addElement( new Element_HTML( $field_group_string ) );
			}
		}

		$form->addElement( new Element_Hidden( 'geo_my_wp_address_count', $field_count ) );

		return $form;
	}

	/**
	 * Get the field inside the container
	 *
	 * @param $i
	 * @param $slug
	 * @param $post_id
	 * @param $customfield
	 * @param $field_id
	 * @param $description
	 *
	 * @return string
	 */
	public function get_container_with_field( $i, $slug, $post_id, $customfield, $field_id, $description ) {
		$add_classes_for_link    = 'geo-address-field-add';
		$delete_classes_for_link = 'geo-address-field-delete';
		$delete_classes_for_link .= ( $i === 0 ) ? ' geo-address-field-0' : '';

		$field_group_string = '<div class="bf_field_group bf-geo-address-fields">';
		$field_group_string .= '<div class="container-for-geo-address-field">';
		$field_group_string .= $this->get_address_elements( $slug, $post_id, $customfield['default'], $field_id, $i, $customfield['name'], $description );
		$field_group_string .= '</div>';
		$field_group_string .= '<div class="container-for-geo-address-controls">';
		$field_group_string .= "<a class='${add_classes_for_link}' field_number='${i}' field_name='{$customfield['name']}' field_id='{$field_id}' data-default-value='{$customfield['default']}' data-description='{$description}'><span class='dashicons dashicons-plus'></span></a>";
		$field_group_string .= "<a class='${delete_classes_for_link}' field_number='${i}' field_name='{$customfield['name']}' field_id='{$field_id}' data-default-value='{$customfield['default']}' data-description='{$description}'><span class='dashicons dashicons-minus'></span></a>";
		$field_group_string .= '<span class="spinner"></span>';
		$field_group_string .= '</div>';
		$field_group_string .= '</div>';

		return $field_group_string;
	}

	/**
	 * Get the Address field with the hidden field
	 *
	 * @param $slug
	 * @param $post_id
	 * @param $default_value
	 * @param $field_id
	 * @param $count
	 * @param $name
	 * @param $description
	 *
	 * @return string
	 */
	public function get_address_elements( $slug, $post_id = 0, $default_value = '', $field_id, $count, $name, $description ) {
		if ( ! empty( $post_id ) ) {
			$customfield_val      = get_post_meta( $post_id, $slug, true );
			$customfield_val_lat  = get_post_meta( $post_id, $slug . '_lat', true );
			$customfield_val_long = get_post_meta( $post_id, $slug . '_lng', true );
		} else {
			$customfield_val      = '';
			$customfield_val_lat  = '';
			$customfield_val_long = '';
		}

		if ( empty( $customfield_val ) && isset( $default_value ) ) {
			$customfield_val = $default_value;
		}

		if ( empty( $customfield_val_lat ) ) {
			$customfield_val_lat = '';
		}

		if ( empty( $customfield_val_long ) ) {
			$customfield_val_long = '';
		}

		$name = apply_filters( 'buddyforms_form_field_geo_my_wp_address_name', stripcslashes( $name ), $slug, $post_id );

		$element_attr = array(
			'id'                 => str_replace( "-", "", $slug ),
			'value'              => $customfield_val,
			'class'              => 'settings-input address-field address bf-address-autocomplete',
			'shortDesc'          => $description,
			'field_id'           => $field_id,
			'field_name'         => $name,
			'autocomplete'       => 'nope',
			'field_number'       => $count,
			'data-description'   => $description,
			'data-default-value' => $default_value,
		);

		$text_box   = new Element_Textbox( $name, $slug, $element_attr );
		$hidden_lat = new Element_Hidden( $slug . '_lat', $customfield_val_lat );
		$hidden_lng = new Element_Hidden( $slug . '_lng', $customfield_val_long );

		ob_start();
		$text_box->render();
		$hidden_lat->render();
		$hidden_lng->render();
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
