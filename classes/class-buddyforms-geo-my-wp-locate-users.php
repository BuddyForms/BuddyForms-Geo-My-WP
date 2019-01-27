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

class BuddyFormsGeoMyWpLocateUsers extends BuddyFormsGeoMyWpLocatePosts {

	/**
	 * Get all user with a direction belong to the given form
	 *
	 * @param string $form_type
	 * @param int $form_slug
	 * @param bool $cache
	 *
	 * @return array|bool|mixed
	 */
	public function get_locations_by_form( $form_type = '', $form_slug = 0, $cache = true ) {
		// look for locations in cache
		$locations = $cache ? wp_cache_get( $form_type . '_' . $form_slug, 'bf_geo_wp_locations' ) : false;

		// if no locations found in cache get it from database
		if ( false === $locations ) {

			$query = new WP_User_Query( array(
				'include'    => array(),
				'fields'     => 'ID',
				'meta_query' => array(
					array(
						'key'     => 'bf_address_count',
						'compare' => 'EXISTS',
					),
				)
			) );

			if ( ! empty( $query->get_results() ) ) {
				foreach ( $query->get_results() as $user_id ) {
					$locations[] = get_user_meta( $user_id, 'bf_address_count' );
				}
			}

			// save to cache if location found
			if ( ! empty( $locations ) ) {
				wp_cache_set( $form_type . '_' . $form_slug, serialize( $locations ), 'bf_geo_wp_locations' );
			}
		}

		// if no location found
		if ( empty( $locations ) ) {
			return array();
		}

		$locations = maybe_unserialize( $locations );

		return $locations;
	}

	/**
	 * Get the User Display Name to show as title of the info window
	 *
	 * @param $item
	 *
	 * @return mixed
	 */
	public function title( $item ) {
		$title     = get_the_author_meta( 'display_name', $item['user_id'] );
		$permalink = ( function_exists( 'bp_core_get_userlink' ) ) ? bp_core_get_userlink( $item['user_id'], false, true ) : get_author_posts_url( $item['user_id'] );

		return apply_filters( 'gmw_sl_title', "<h3 class=\"gmw-sl-title post-title gmw-sl-element\"><a href=\"{$permalink}\" title=\"{$title}\"'>{$title}</a></h3>", $this->location_data, $this->args, $this->user_position, $this );
	}


}
