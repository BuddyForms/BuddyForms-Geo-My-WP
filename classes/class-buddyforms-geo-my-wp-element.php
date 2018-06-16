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

class BuddyFormsGeoMyWpElement {

	private $load_script;

	public function __construct() {
		add_filter( 'buddyforms_create_edit_form_display_element', array( $this, 'buddyforms_woocommerce_create_new_form_builder' ), 10, 2 );
		add_action( 'wp_footer', array( $this, 'add_scripts' ), 11 );
//		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 11 );
	}

	public function wp_enqueue_scripts() {
		if ( function_exists( 'gmw_enqueue_scripts' ) ) {
			gmw_enqueue_scripts();
		}
	}

	/**
	 * @param Form $form
	 * @param array $form_args
	 *
	 * @return mixed
	 */
	public function buddyforms_woocommerce_create_new_form_builder( $form, $form_args ) {
		global $post;
		$customfield = false;
		$post_id     = 0;
		$field_id    = '';
		extract( $form_args, EXTR_IF_EXISTS );

		if ( ! isset( $customfield['type'] ) ) {
			return $form;
		}
		if ( $customfield['type'] === 'geo_my_wp_address' && is_user_logged_in() ) {
			$this->load_script = true;

			if ( isset( $customfield['slug'] ) ) {
				$slug = sanitize_title( $customfield['slug'] );
			}

			if ( empty( $slug ) ) {
				$slug = sanitize_title( $customfield['name'] );
			}

			$customfield_val = get_post_meta( $post_id, $slug, true );

			if ( empty( $customfield_val ) && isset( $customfield['default'] ) ) {
				$customfield_val = $customfield['default'];
			}

			$name = '';
			if ( isset( $customfield['name'] ) ) {
				$name = stripcslashes( $customfield['name'] );
			}

			$name = apply_filters( 'buddyforms_form_field_name', $name, $post_id );

			$description = '';
			if ( isset( $customfield['description'] ) ) {
				$description = stripcslashes( $customfield['description'] );
			}

			$description = apply_filters( 'buddyforms_form_field_description', $description, $post_id );

			$element_attr = array(
				'id'        => str_replace( "-", "", $slug ),
				'value'     => $customfield_val,
				'class'     => 'settings-input address-field address gmw-address-autocomplete',
				'shortDesc' => $description,
				'field_id'  => $field_id
			);
			ob_start();
			require BF_GEO_FIELD_VIEW_PATH . 'field.php';
			$get_contents = ob_get_contents();
			ob_clean();
			$form->addElement( new Element_HTML( $get_contents ) );
		}

		return $form;
	}

	public function add_scripts() {
		if ( $this->load_script ) {
			// load main JavaScript and Google APIs
			if ( ! wp_script_is( 'gmw', 'enqueued' ) ) {
				wp_enqueue_script( 'gmw' );
			}
		}
	}
}
