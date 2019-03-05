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

function bf_geo_my_wp_get_user_location_meta( $user_id, $field_slug ) {
	if ( empty( $user_id ) || empty( $field_slug ) ) {
		return false;
	}
	
	return get_user_meta( $user_id, 'bf_' . $field_slug . '_count' );
}

function bf_geo_my_wp_get_content_location_meta( $post_id, $field_slug ) {
	if ( empty( $post_id ) || empty( $field_slug ) ) {
		return false;
	}
	
	return get_post_meta( $post_id, 'bf_' . $field_slug . '_count' );
}

