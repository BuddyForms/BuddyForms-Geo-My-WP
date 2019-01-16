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

if ( ! class_exists( 'GMW_Single_Location' ) ) {
	return;
}

class BuddyFormsGeoMyWpShortCodes {

	public function __construct() {
		add_shortcode( 'bf_geo_my_wp', array( $this, 'callback_bf_geo_my_wp' ) );
		add_filter( 'gmw_pt_search_query_args', array( $this,'buddyforms_geowp_data'), 10, 3 );
	}

	public function buddyforms_geowp_data($args, $form, $instance) {

		return $args;
	}
	public function callback_bf_geo_my_wp( $attrs ) {
		$attrs = shortcode_atts( array(
			'form_slug'      => '',
			'logged_in_user' => 'false',
			'post_id'        => 'false'
		), $attrs, 'bf_geo_my_wp' );


		$attrs['object'] = 'post';

		if ( isset( $attrs['post_id'] ) ) {
			$attrs['object_id'] = $attrs['post_id'];
		}

		require_once 'class-buddyforms-geo-my-wp-shortcode-form.php';

		$single_post_location = new BuddyFormsGeoMyWpShortCodeForm( $attrs );

		return $single_post_location->output();
	}
}