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

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	public function __construct() {
		if ( $this->bf_gmw_fs_is_parent_active_and_loaded() ) {
			// If parent already included, init add-on.
			$this->bf_gmw_fs_init();
		} elseif ( $this->bf_gmw_fs_is_parent_active() ) {
			// Init add-on only after the parent is loaded.
			add_action( 'buddyforms_core_fs_loaded', array( $this, 'bf_gmw_fs_init' ) );
		} else {
			// Even though the parent is not activated, execute add-on for activation / uninstall hooks.
			$this->bf_gmw_fs_init();
		}
	}

	public function bf_gmw_fs_is_parent_active_and_loaded() {
		// Check if the parent's init SDK method exists.
		return function_exists( 'buddyforms_core_fs' );
	}

	public function bf_gmw_fs_is_parent_active() {
		$active_plugins = get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
			$active_plugins         = array_merge( $active_plugins, array_keys( $network_active_plugins ) );
		}

		foreach ( $active_plugins as $basename ) {
			if ( 0 === strpos( $basename, 'buddyforms/' ) ||
				 0 === strpos( $basename, 'buddyforms-premium/' )
			) {
				return true;
			}
		}

		return false;
	}

	public function bf_gmw_fs_init() {
		if ( $this->bf_gmw_fs_is_parent_active_and_loaded() ) {
			// Init Freemius.
			$this->bf_gmw_fs();
		}
	}

	/**
	 * @return Freemius
	 */
	public static function getFreemius() {
		global $bf_gmw_fs;

		return $bf_gmw_fs;
	}

	// Create a helper function for easy SDK access.

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function bf_gmw_fs() {
		global $bf_gmw_fs;
		try {
			if ( ! isset( $bf_gmw_fs ) ) {
				// Include Freemius SDK.
				if ( file_exists( dirname( dirname( __FILE__ ) ) . '/buddyforms/includes/resources/freemius/start.php' ) ) {
					// Try to load SDK from parent plugin folder.
					require_once dirname( dirname( __FILE__ ) ) . '/buddyforms/includes/resources/freemius/start.php';
				} elseif ( file_exists( dirname( dirname( __FILE__ ) ) . '/buddyforms-premium/includes/resources/freemius/start.php' ) ) {
					// Try to load SDK from premium parent plugin folder.
					require_once dirname( dirname( __FILE__ ) ) . '/buddyforms-premium/includes/resources/freemius/start.php';
				}

				$bf_gmw_fs = fs_dynamic_init(
					array(
						'id'               => '3376',
						'slug'             => 'bf-geo-my-wp',
						'type'             => 'plugin',
						'public_key'       => 'pk_f9693289c7159670bc642cffeaf07',
						'is_premium'       => true,
						'is_premium_only'  => true,
						'has_paid_plans'   => true,
						'is_org_compliant' => false,
						'trial'            => array(
							'days'               => 14,
							'is_require_payment' => true,
						),
						'parent'           => array(
							'id'         => '391',
							'slug'       => 'buddyforms',
							'public_key' => 'pk_dea3d8c1c831caf06cfea10c7114c',
							'name'       => 'BuddyForms',
						),
						'menu'             => array(
							'first-path' => 'plugins.php',
							'support'    => false,
						),
						'bundle_license_auto_activation' => true,
					)
				);
			}
		} catch ( Exception $ex ) {
			trigger_error( 'BF-GMW::' . $ex->getMessage(), E_USER_NOTICE );
		}

		return $bf_gmw_fs;
	}
}
