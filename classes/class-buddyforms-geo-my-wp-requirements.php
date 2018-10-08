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

class BuddyFormsGeoMyWpRequirements {

	public function __construct() {
		require_once  'resources/tgm/class-tgm-plugin-activation.php';

		add_action( 'init', array( $this, 'setup_init' ), 1, 1 );
	}

	public static function is_geo_my_wp_active() {
		self::load_plugins_dependency();

		return is_plugin_active( 'geo-my-wp/geo-my-wp.php' );
	}

	public static function load_plugins_dependency() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	public static function is_buddy_form_active() {
		self::load_plugins_dependency();

		return ( is_plugin_active( 'buddyforms-premium/BuddyForms.php' ) || is_plugin_active( 'buddyforms/BuddyForms.php' ) );
	}

	public function setup_init() {
		// Only Check for requirements in the admin
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'bf_geo_wp_tgmpa_register', array( $this, 'setup_and_check' ) );
	}

	public function setup_and_check() {
		// Create the required plugins array
		$plugins['geo-my-wp'] = array(
			'name'     => 'Geo My Wp',
			'slug'     => 'geo-my-wp',
			'required' => true,
		);

		if ( ! defined( 'BUDDYFORMS_PRO_VERSION' ) ) {
			$plugins['buddyforms'] = array(
				'name'     => 'BuddyForms',
				'slug'     => 'buddyforms',
				'required' => true,
			);
		}

		$config = array(
			'id'           => 'bf_geo_wp',
			'menu'         => 'bf_geo_wp-install-plugins', // Menu slug.
			'parent_slug'  => 'plugins.php', // Parent menu slug.
			'capability'   => 'manage_options', // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
			'has_notices'  => true, // Show admin notices or not.
			'dismissable'  => false, // If false, a user cannot dismiss the nag message.
			'is_automatic' => true, // Automatically activate plugins after installation or not.
			'strings'      => array(
				'notice_can_install_required'    => _n_noop(
				/* translators: 1: plugin name(s). */
					'<u>BuddyForms Geo My Wp</u> plugin requires the following plugin: %1$s.',
					'<u>BuddyForms Geo My Wp</u> plugin requires the following plugins: %1$s.',
					'buddyforms_geo_my_wp_locale'
				),
				'notice_can_install_recommended' => _n_noop(
				/* translators: 1: plugin name(s). */
					'<u>BuddyForms Geo My Wp</u> plugin recommends the following plugin: %1$s.',
					'<u>BuddyForms Geo My Wp</u> plugin recommends the following plugins: %1$s.',
					'buddyforms_geo_my_wp_locale'
				),
				'notice_can_activate_required'   => _n_noop(
				/* translators: 1: plugin name(s). */
					'The following is a required plugin for <u>BuddyForms Geo My Wp</u> and is currently inactive: %1$s.',
					'The following is a required plugins for <u>BuddyForms Geo My Wp</u> and they are currently inactive: %1$s.',
					'buddyforms_geo_my_wp_locale'
				),
				'notice_ask_to_update'           => _n_noop(
				/* translators: 1: plugin name(s). */
					'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this plugin: %1$s.',
					'The following plugins need to be updated to their latest version to ensure maximum compatibility with this plugin: %1$s.',
					'buddyforms_geo_my_wp_locale'
				),
			),
		);

		// Call the tgmpa function to register the required plugins
		bf_geo_wp_tgmpa( $plugins, $config );
	}
}