<?php
/*
 * Plugin Name: BuddyForms Geo My WP
 * Plugin URI: http://buddyforms.com/
 * Description: This Plugin brings the power of Geo My Wp into BuddyForms
 * Version: 1.2.2
 * Author: ThemeKraft Dev Team
 * Author URI: https://themkraft.com/#team
 * License: GPLv2 or later
 * Text Domain: buddyforms_geo_my_wp_locale
 *
 * @package buddyforms_geo_my_wp
 *
 *****************************************************************************
 *
 * This script is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 ****************************************************************************
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'buddyforms_geo_my_wp' ) ) {

	require_once dirname( __FILE__ ) . '/classes/class-buddyforms-geo-my-wp-fs.php';
	new BuddyFormsGeoMyWpFs();

	class buddyforms_geo_my_wp {

		/**
		 * Instance of this class
		 *
		 * @var $instance buddyforms_geo_my_wp
		 */
		protected static $instance = null;

		private function __construct() {
			$this->constants();
			$this->load_plugin_textdomain();
			require_once 'classes/class-buddyforms-geo-my-wp-requirements.php';
			new BuddyFormsGeoMyWpRequirements();
			$bf_freemius = BuddyFormsGeoMyWpFs::getFreemius();
			if ( ! empty( $bf_freemius ) && $bf_freemius->is_plan( 'professional' ) ) {
				if ( BuddyFormsGeoMyWpRequirements::is_buddy_form_active() && BuddyFormsGeoMyWpRequirements::is_geo_my_wp_active() ) {
					require_once 'classes/class-buddyforms-geo-my-wp-manager.php';
					new BuddyFormsGeoMyWpManager();
				}
			} else {
				add_action( 'admin_notices', array( $this, 'buddyforms_geo_my_wp_free_version_admin_notice' ) );
			}
		}

		function buddyforms_geo_my_wp_free_version_admin_notice() {
			?>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'You need to activate the license of the extension <u>BuddyForms Geo My Wp.</u>', 'buddyforms_geo_my_wp_locale' ); ?></p>
			</div>
			<?php
		}

		private function constants() {
			define( 'BF_GEO_FIELD_CSS_PATH', plugin_dir_url( __FILE__ ) . 'assets/css/' );
			define( 'BF_GEO_FIELD_JS_PATH', plugin_dir_url( __FILE__ ) . 'assets/js/' );
			define( 'BF_GEO_FIELD_IMAGES_PATH', plugin_dir_url( __FILE__ ) . 'assets/images/' );
			define( 'BF_GEO_FIELD_VIEW_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR );
		}

		/**
		 * Return an instance of this class.
		 *
		 * @return buddyforms_geo_my_wp A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'buddyforms_geo_my_wp_locale', false, basename( dirname( __FILE__ ) ) . '/languages' );
		}

	}

	add_action( 'plugins_loaded', array( 'buddyforms_geo_my_wp', 'get_instance' ), 1 );
}
