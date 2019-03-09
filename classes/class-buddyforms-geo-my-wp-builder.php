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

	public function __construct() {
		add_filter( 'buddyforms_add_form_element_select_option', array( $this, 'buddyforms_formbuilder_elements_select' ), 10 );
		add_filter( 'buddyforms_formbuilder_fields_options', array( $this, 'formbuilder_fields_options' ), 15, 4 );
	}

	public function formbuilder_fields_options( $form_fields, $field_type, $field_id, $form_slug ) {
		if ( $field_type === 'geo_my_wp_address' ) {
			global $buddyform;
			$form_fields['general']['is_multiple'] = new Element_Checkbox( '<b>' . __( 'Multiple', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][is_multiple]",
				array( 'true' => __( 'Enable as Multiple Field', 'buddyforms' ) ),
				array( 'value' => !empty( $buddyform['form_fields'][ $field_id ]['is_multiple'] ))
			);
		}

		return $form_fields;
	}

	public function buddyforms_formbuilder_elements_select( $elements_select_options ) {
		global $post;

		if ( $post->post_type != 'buddyforms' ) {
			return;
		}

		$elements_select_options['geo_my_wp']['label']                       = 'Geo My Wp';
		$elements_select_options['geo_my_wp']['class']                       = 'bf_show_if_f_type_all';
		$elements_select_options['geo_my_wp']['fields']['geo_my_wp_address'] = array(
			'label'  => __( 'Address', 'buddyforms_geo_my_wp_locale' )
		);

		return $elements_select_options;
	}
}
