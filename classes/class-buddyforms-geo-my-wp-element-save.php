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

class BuddyFormsGeoMyWpElementSave {

	public function __construct() {
		add_action( 'buddyforms_update_post_meta', array( $this, 'buddyforms_geo_my_wp_update_post_meta' ), 11, 2 );

	}

	public function buddyforms_geo_my_wp_update_post_meta( $customfield, $post_id ) {
		if ( $customfield['type'] == 'geo_my_wp_address' ) {
			if ( isset( $customfield['slug'] ) ) {
				$slug = $customfield['slug'];
			}

			if ( empty( $slug ) ) {
				$slug = sanitize_title( $customfield['name'] );
			}

			$amount_of_fields = 1;
			if ( ! empty( $_POST['geo_my_wp_address_count'] ) ) {
				$amount_of_fields = intval( $_POST['geo_my_wp_address_count'] );
			}
			update_post_meta( $post_id, $slug . '_count', $amount_of_fields );
			for ( $i = 0; $i <= $amount_of_fields; $i ++ ) {
				$internal_slug = $slug . '_' . $i;
				if ( ! empty( $_POST[ $internal_slug ] ) &&  ! empty( $_POST[ $internal_slug . '_lat' ] ) &&  ! empty( $_POST[ $internal_slug . '_lng' ] ) ) {
					$string_value = buddyforms_sanitize( $customfield['type'], $_POST[ $internal_slug ] );
					$lat_value    = buddyforms_sanitize( $customfield['type'], $_POST[ $internal_slug . '_lat' ] );
					$lng_value    = buddyforms_sanitize( $customfield['type'], $_POST[ $internal_slug . '_lng' ] );

					if ( ! empty( $string_value ) ) {
						update_post_meta( $post_id, $internal_slug, $string_value );
					}
					if ( ! empty( $lat_value ) ) {
						update_post_meta( $post_id, $internal_slug . '_lat', $lat_value );
					}
					if ( ! empty( $lng_value ) ) {
						update_post_meta( $post_id, $internal_slug . '_lng', $lng_value );
					}

					if ( defined( 'GMW_PT_PATH' ) ) {
						//include the update location file file
						include_once( GMW_PT_PATH . '/includes/gmw-pt-update-location.php' );
						//make sure the file included and the function exists
						if ( ! function_exists( 'gmw_pt_update_location' ) ) {
							return;
						}
						global $form_slug, $buddyforms;
						$type    = 'post';
						$id      = $post_id;
						$user_id = 0;
						if ( isset( $buddyforms[ $form_slug ] ) && $buddyforms[ $form_slug ]['form_type'] === 'registration' ) {
							$type = 'user';
						}
						$location = array(
							'lat' => $lat_value,
							'lng' => $lng_value
						);

						gmw_update_location( $type, $id, $location, $user_id, true );
					}
				} else {
					if ( ! is_admin() ) {
						update_post_meta( $post_id, $slug, '' );
					}
				}
			}
		}

	}
}