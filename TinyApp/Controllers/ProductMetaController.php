<?php

namespace TinySolutions\cptwoointpro\Controllers;

// Do not allow directly accessing this file.
use Automattic\Jetpack\Constants;
use TinySolutions\cptwooint\Helpers\Fns;
use TinySolutions\cptwoointpro\Controllers\Admin\ProductMetaBoxData;
use TinySolutions\cptwoointpro\Traits\SingletonTrait;
use TinySolutions\cptwoointpro\Controllers\Admin\ProductMetaBoxes;
use WC_Meta_Box_Product_Images;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

/**
 * Product meta Controller.
 */
class ProductMetaController {
	/**
	 * Singleton
	 */
	use SingletonTrait;

	/**
	 * Is meta boxes saved once?
	 *
	 * @var boolean
	 */
	private static $saved_meta_boxes = false;

	/**
	 * Init save
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'save_post', [ $this, 'save_meta_boxes' ], 10, 2 );
	}

	/**
	 * Check if we're saving, the trigger an action based on the post type.
	 *
	 * @param int    $post_id Post ID.
	 * @param object $post Post object.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		if ( ! Fns::is_supported( $post->post_type ) ) {
			return;
		}
		$post_id = absint( $post_id );
		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
			return;
		}

		// Dont' save meta boxes for revisions or autosaves.
		if ( Constants::is_true( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		if ( empty( $_POST['woocommerce_meta_nonce'] ) ) {
			return;
		}
		// Check the nonce.
		$nonce = sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'woocommerce_save_data' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
		if ( empty( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== $post_id ) {
			return;
		}

		// Check user has permission to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// We need this save event to run once to avoid potential endless loops. This would have been perfect:
		// remove_action( current_filter(), __METHOD__ );
		// But cannot be used due to https://github.com/woocommerce/woocommerce/issues/6485
		// When that is patched in core we can use the above.
		self::$saved_meta_boxes = true;

		ProductMetaBoxData::save( $post_id, $post );
		WC_Meta_Box_Product_Images::save( $post_id, $post );
	}
}
