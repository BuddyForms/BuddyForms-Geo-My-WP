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

class BuddyFormsGeoMyWpFs {

	public function __construct() {
		try {


		} catch ( Exception $ex ) {
			BuddyFormsGeoMyWpLog::log( array(
				'action'         => get_class( $this ),
				'object_type'    => BuddyFormsGeoMyWpManager::get_slug(),
				'object_subtype' => 'loading_dependency',
				'object_name'    => $ex->getMessage(),
			) );
		}
	}
}