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

class BuddyFormsGeoMyWpSubmission {

	public function __construct() {
		add_filter( 'bf_submission_column_default', array( $this, 'buddyforms_element_submission_value' ), 10, 4 );
	}

	/**
	 * Ge the value to show in the submission list
	 *
	 * @param $bf_value
	 * @param WP_Post $item
	 * @param string $field_type
	 * @param string $field_slug
	 *
	 * @return mixed
	 */
	public function buddyforms_element_submission_value( $bf_value, $item, $field_type, $field_slug ) {
		if ( $field_type === 'geo_my_wp_address' ) {
			$form_slug = get_post_meta( $item->ID, '_bf_form_slug', true );
			if ( empty( $form_slug ) ) {
				return $bf_value;
			}
			$form_type = BuddyFormsGeoMyWpElement::get_buddyforms_form_type( $form_slug );
			if ( 'registration' !== $form_type ) {
				$field_count = get_post_meta( $item->ID, $field_slug . '_count', true );
			} else {
				$field_count = get_user_meta( $item->post_author, $field_slug . '_count', true );
			}
			if ( empty( $field_count ) ) {
				$field_count = 0;
			}
			$addresses = array();
			for ( $i = 0; $i <= $field_count; $i ++ ) {
				if ( 'registration' !== $form_type ) {
					$addresses[] = get_post_meta( $item->ID, $field_slug . '_' . $i, true );
				} else {
					$addresses[] = get_user_meta( $item->post_author, $field_slug . '_' . $i, true );
				}
			}

			$bf_value = implode( '<br/>', $addresses );
		}

		return $bf_value;
	}
}
