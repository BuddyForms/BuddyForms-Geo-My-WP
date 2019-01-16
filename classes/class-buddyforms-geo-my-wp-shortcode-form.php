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

class BuddyFormsGeoMyWpShortCodeForm extends GMW_Single_Location{
	/**
	 * Extends the default shortcode atts
	 * @since 2.6.1
	 * Public $args
	 *
	 */
	protected $args = array(
		'elements'			=> 'title,address,map,distance,location_meta,directions_link',
		'object'			=> 'post',
		'prefix'	 		=> 'pt',
		'location_meta' 	=> 'address,phone,fax,email,website',
		'item_info_window'	=> 'title,address,distance,location_meta',
	);

	/**
	 * Trt and get post ID if missing.
	 *
	 * @return [type] [description]
	 */
	public function get_object_id() {

		$object_id = get_queried_object_id();

		return ! empty( $object_id  ) ? $object_id : false;
	}

	/**
	 * Get the post title
	 *
	 * @return [type] [description]
	 */
	public function title() {

		$title     = get_the_title( $this->args['object_id'] );
		$permalink = get_the_permalink( $this->args['object_id'] );

		return apply_filters( 'gmw_sl_title', "<h3 class=\"gmw-sl-title post-title gmw-sl-element\"><a href=\"{$permalink}\" title=\"{$title}\"'>{$title}</a></h3>", $this->location_data, $this->args, $this->user_position, $this );
	}
}