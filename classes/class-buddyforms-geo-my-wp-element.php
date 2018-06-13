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

	public function __construct() {
		add_filter( 'buddyforms_create_edit_form_display_element', array( $this, 'buddyforms_woocommerce_create_new_form_builder' ), 10, 2 );
	}

	/**
	 * @param Form $form
	 * @param array $form_args
	 *
	 * @return mixed
	 */
	public function buddyforms_woocommerce_create_new_form_builder( $form, $form_args ) {
		global $post;
		$customfield = false;
		$post_id     = 0;
		$field_id    = '';
		extract( $form_args, EXTR_IF_EXISTS );

		if ( ! isset( $customfield['type'] ) ) {
			return $form;
		}
		if ( $customfield['type'] === 'geo_my_wp_address' && is_user_logged_in() ) {
			$this->add_scripts( $post );
			$this->add_styles();

			if ( isset( $customfield['slug'] ) ) {
				$slug = sanitize_title( $customfield['slug'] );
			}

			if ( empty( $slug ) ) {
				$slug = sanitize_title( $customfield['name'] );
			}

			$customfield_val = get_post_meta( $post_id, $slug, true );

			if ( empty( $customfield_val ) && isset( $customfield['default'] ) ) {
				$customfield_val = $customfield['default'];
			}

			$name = '';
			if ( isset( $customfield['name'] ) ) {
				$name = stripcslashes( $customfield['name'] );
			}

			$name = apply_filters( 'buddyforms_form_field_name', $name, $post_id );

			$description = '';
			if ( isset( $customfield['description'] ) ) {
				$description = stripcslashes( $customfield['description'] );
			}

			$description = apply_filters( 'buddyforms_form_field_description', $description, $post_id );

			$element_attr = array(
				'id'        => str_replace( "-", "", $slug ),
				'value'     => $customfield_val,
				'class'     => 'settings-input gmw-lf-field address-field address gmw-lf-address-autocomplete',
				'shortDesc' => $description,
				'field_id'  => $field_id
			);
			$form->addElement( new Element_Textbox( $name, $slug, $element_attr ) );
		}

		return $form;
	}

	public function add_scripts( $post ) {

	}

	public function add_styles() {

	}
}