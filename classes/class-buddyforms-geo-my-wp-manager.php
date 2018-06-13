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

class BuddyFormsGeoMyWpManager {
	protected static $version = '1.0.0';
	private static $plugin_slug = 'bf_geo_wp';

	public function __construct() {
		require_once 'class-buddyforms-geo-my-wp-log.php';
		new BuddyFormsGeoMyWpLog();
		try {
			$this->load_dependency();
		} catch ( Exception $ex ) {
			BuddyFormsGeoMyWpLog::log( array(
				'action'         => get_class( $this ),
				'object_type'    => BuddyFormsGeoMyWpManager::get_slug(),
				'object_subtype' => 'loading_dependency',
				'object_name'    => $ex->getMessage(),
			) );

		}
	}

	public function load_dependency() {
		require_once 'class-buddyforms-geo-my-wp-builder.php';
		new BuddyFormsGeoMyWpBuilder();
		require_once 'class-buddyforms-geo-my-wp-element.php';
		new BuddyFormsGeoMyWpElement();
		require_once 'class-buddyforms-geo-my-wp-element-save.php';
		new BuddyFormsGeoMyWpElementSave();
	}

	public static function get_slug() {
		return self::$plugin_slug;
	}

	static function get_version() {
		return self::$version;
	}
}