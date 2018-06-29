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

class BuddyFormsGeoMyWpBuilder {

	private $load_script;

	public function __construct() {
		add_filter( 'buddyforms_add_form_element_select_option', array( $this, 'buddyforms_formbuilder_elements_select' ), 10 );
//		add_filter( 'buddyforms_form_element_add_field', array( $this, 'buddyforms_woocommerce_create_new_form_builder_form_element' ), 1, 5 );

		add_action( 'admin_footer', array( $this, 'load_js_for_builder' ) );
	}

	public function load_js_for_builder( $hook ) {
		if ( $this->load_script ) {
			wp_enqueue_script( 'bf_woo_builder', BF_WOO_ELEM_JS_PATH . 'bf_woo_builder.js', array( "jquery" ), null, true );
			wp_enqueue_style( 'bf_woo_builder', BF_WOO_ELEM_CSS_PATH . 'buddyforms-woocommerce.css' );
		}
	}

	public function buddyforms_formbuilder_elements_select( $elements_select_options ) {
		global $post;

		if ( $post->post_type != 'buddyforms' ) {
			return;
		}

		$elements_select_options['geo_my_wp']['label']                       = 'Geo My Wp';
		$elements_select_options['geo_my_wp']['class']                       = 'bf_show_if_f_type_post';
		$elements_select_options['geo_my_wp']['fields']['geo_my_wp_address'] = array(
			'label'  => __( 'Address', 'buddyforms_geo_my_wp_locale' ),
			'unique' => 'unique'
		);

		return $elements_select_options;
	}

	public function buddyforms_woocommerce_create_new_form_builder_form_element( $form_fields, $form_slug, $field_type, $field_id ) {
		global $post, $buddyform;

		if ( $post->post_type != 'buddyforms' ) {
			return;
		}

		$field_id = (string) $field_id;

		$this->load_script = true;

		if ( ! $buddyform ) {
			$buddyform = get_post_meta( $post->ID, '_buddyforms_options', true );
		}

		switch ( $field_type ) {
			case 'geo_my_wp_address':
				unset( $form_fields );
				$form_fields['hidden']['name']         = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][name]", 'Address' );
				$form_fields['hidden']['slug']         = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][slug]", 'geo_my_wp_address' );
				$form_fields['hidden']['type']         = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][type]", $field_type );
				$description                           = isset( $buddyform['form_fields'][ $field_id ]['description'] ) ? stripslashes( $buddyform['form_fields'][ $field_id ]['description'] ) : '';
				$form_fields['Gallery']['description'] = new Element_Textbox( '<b>' . __( 'Description', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][description]", array( 'value' => $description ) );
				$required                              = isset( $buddyform['form_fields'][ $field_id ]['required'] ) ? $buddyform['form_fields'][ $field_id ]['required'] : 'false';
				$form_fields['Gallery']['required']    = new Element_Checkbox( '<b>' . __( 'Required', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][required]", array( 'required' => '<b>' . __( 'Make this field a required field', 'buddyforms' ) . '</b>' ), array(
					'value' => $required,
					'id'    => "buddyforms_options[form_fields][" . $field_id . "][required]"
				) );
				break;
		}

		return $form_fields;
	}
}