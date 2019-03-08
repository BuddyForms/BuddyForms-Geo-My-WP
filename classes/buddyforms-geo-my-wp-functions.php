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

/**
 * Get the address from the user meta grabbing from specific field slug
 *
 * @param        $user_id
 * @param        $field_slug
 * @param array  $components The address component to build the address @readmore https://developers.google.com/maps/documentation/geocoding/intro#Types
 * @param string $glue
 *
 * @return array(string)
 */
function bf_geo_my_wp_get_user_address_by_component( $user_id, $field_slug, $components = array( 'formatted_address' ), $glue = ', ' ) {
	$meta = bf_geo_my_wp_get_user_location_meta( $user_id, $field_slug );
	
	if ( ! empty( $meta ) && is_array( $meta ) ) {
		$result = array();
		foreach ( $meta as $item ) {
			$result[] = BuddyFormsGeoMyWpLocateEntries::get_address_component_from_item( $item->data, $components, $glue );
		}
		
		return $result;
	}
	
	return array();
}

/**
 * Get the address from the post meta grabbing from specific field slug
 *
 * @param        $post_id
 * @param        $field_slug
 * @param array  $components The address component to build the address @readmore https://developers.google.com/maps/documentation/geocoding/intro#Types
 * @param string $glue
 *
 * @return array(string)
 */
function bf_geo_my_wp_get_content_address_by_component( $post_id, $field_slug, $components = array( 'formatted_address' ), $glue = ', ' ) {
	$meta = bf_geo_my_wp_get_content_location_meta( $post_id, $field_slug );
	
	if ( ! empty( $meta ) && is_array( $meta ) ) {
		$result = array();
		foreach ( $meta as $item ) {
			$result[] = BuddyFormsGeoMyWpLocateEntries::get_address_component_from_item( $item->data, $components, $glue );
		}
		
		return $result;
	}
	
	return array();
}

/**
 * Get the user location from the given user_id and field slug
 *
 * @param      $user_id
 * @param      $field_slug
 * @param bool $is_single
 *
 * @return array
 */
function bf_geo_my_wp_get_user_location_meta( $user_id, $field_slug, $is_single = true ) {
	if ( empty( $user_id ) || empty( $field_slug ) ) {
		return array();
	}
	
	$meta = get_user_meta( $user_id, 'bf_' . $field_slug . '_count', $is_single );
	
	return ( empty( $meta ) ) ? array() : $meta;
}

/**
 * Get the location for the given post and field slug
 *
 * @param      $post_id
 * @param      $field_slug
 * @param bool $is_single
 *
 * @return array
 */
function bf_geo_my_wp_get_content_location_meta( $post_id, $field_slug, $is_single = true  ) {
	if ( empty( $post_id ) || empty( $field_slug ) ) {
		return array();
	}
	
	$meta = get_post_meta( $post_id, 'bf_' . $field_slug . '_count', $is_single );
	
	return ( empty( $meta ) ) ? array() : $meta;
}

