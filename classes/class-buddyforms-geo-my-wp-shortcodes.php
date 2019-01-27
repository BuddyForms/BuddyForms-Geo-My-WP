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
	}

	public function callback_bf_geo_my_wp( $attrs ) {
		$attrs = shortcode_atts( array(
			'form_slug'            => '',
			'form'                 => '',
			'logged_in_user'       => 'false',
			'map_width'            => '250px',
			'map_height'           => '250px',
			'elements'             => 'map,distance,location_meta',
			'object'               => 'post',
			'prefix'               => 'pt',
			'location_meta'        => 'address',
			'element_id'           => 0,
			'form_type'            => '',
			'address_fields'       => 'address',
			'units'                => 'metric',
			'map_type'             => 'ROADMAP',
			'zoom_level'           => 13,
			'scrollwheel_map_zoom' => 1,
			'expand_map_on_load'   => 0,
			'map_icon_url'         => '',
			'map_icon_size'        => '',
			'info_window'          => 'title,address,distance',
			'user_map_icon_url'    => '',
			'user_map_icon_size'   => '',
			'user_info_window'     => __( 'Your Location', 'geo-my-wp' ),
			'no_location_message'  => 0,
		), $attrs, 'bf_geo_my_wp' );

		if ( ! isset( $attrs['object'] ) ) {
			$attrs['object'] = 'post';
		}

		$post_form_options = array();
		//Get the form slug from the form id
		if ( ! empty( $attrs['form_slug'] ) ) {
			if ( is_numeric( $attrs['form_slug'] ) ) {
				$post_form          = get_post( $attrs['form_slug'] );
				$attrs['form_slug'] = $post_form->post_name;
			}
			$post_form_options = buddyforms_get_form_by_slug( $attrs['form_slug'] );
		} else {
			gmw_trigger_error( '[bf_geo_my_wp] shortcode attribute form_slug is mandatory.' );
		}

		//Get the form type
		if(!empty($post_form_options) && $post_form_options['form_type'] === 'registration'){
			$attrs['object'] = 'registration';
		}
		//decide the type of shortcode on base of the form type
		require_once 'class-buddyforms-geo-my-wp-locate-posts.php';
		if ( $attrs['object'] === 'registration' ) {
			require_once 'class-buddyforms-geo-my-wp-locate-users.php';
			$instance = new BuddyFormsGeoMyWpLocateUsers( $attrs );
		} else if ( $attrs['object'] === 'post' ) {
			$instance = new BuddyFormsGeoMyWpLocatePosts( $attrs );
		}

		if ( ! empty( $instance ) ) {
			return $instance->map();
		} else {
			return '';
		}
	}
}