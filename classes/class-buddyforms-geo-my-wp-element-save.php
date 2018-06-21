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

class BuddyFormsGeoMyWpElementSave {

	private $bf_wc_save_meta = false;
	private $bf_wc_save_gallery = false;

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

			// Update the post
			if ( ! empty( $_POST[ $slug ] ) ) {
				$value = buddyforms_sanitize( $customfield['type'], $_POST[ $slug ] );
				update_post_meta( $post_id, $slug, $value );

				if ( defined( 'GMW_PT_PATH' ) ) {
					//include the update location file file
					include_once( GMW_PT_PATH . '/includes/gmw-pt-update-location.php' );

					//make sure the file included and the function exists
					if ( ! function_exists( 'gmw_pt_update_location' ) ) {
						return;
					}

					//Create the array that will pass to the function
					$args = array(
						'post_id' => $post_id, //Post Id of the post
						'address' => $value // the address we pull from the custom field above
					);

					//run the udpate location function
					gmw_pt_update_location( $args );
				}
			} else {
				if ( ! is_admin() ) {
					update_post_meta( $post_id, $slug, '' );
				}
			}
		}

	}

	public function buddyforms_geo_my_wp_update_wc_post_meta( $post_id ) {
		if ( $this->bf_wc_save_meta || $this->bf_wc_save_gallery ) {
			$post             = get_post( $post_id );
			$update_post_type = array(
				'ID'          => $post_id,
				'post_name'   => $post->post_title,
				'post_type'   => 'product',
				'post_status' => 'publish'
			);
			$post_updated     = wp_update_post( $update_post_type, true );
			update_post_meta( $post_id, '_visibility', 'visible' );

			if ( $this->bf_wc_save_meta ) {
				WC_Meta_Box_Product_Data::save( $post_id, $post );
			}

			if ( $this->bf_wc_save_gallery ) {
				WC_Meta_Box_Product_Images::save( $post_id, $post );
			}
		}
	}
}