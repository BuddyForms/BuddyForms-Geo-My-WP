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

class BuddyFormsGeoMyWpManager {
	protected static $version = '1.1.9';
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
		require_once 'class-buddyforms-geo-my-wp-submission.php';
		new BuddyFormsGeoMyWpSubmission();
		require_once 'class-buddyforms-geo-my-wp-locate-entries.php';
		require_once 'buddyforms-geo-my-wp-functions.php';
		require_once 'class-buddyforms-geo-my-wp-shortcodes.php';
		new BuddyFormsGeoMyWpShortCodes();
	}

	public static function get_slug() {
		return self::$plugin_slug;
	}

	static function get_version() {
		return self::$version;
	}

	public static function assets_path( $name, $extension = 'js' ) {
		$base_path = ( $extension == 'js' ) ? BF_GEO_FIELD_JS_PATH : BF_GEO_FIELD_CSS_PATH;
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		return $base_path . $name . $suffix . '.' . $extension;
	}
}
