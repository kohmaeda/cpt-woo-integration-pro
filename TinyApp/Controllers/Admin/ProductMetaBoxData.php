<?php
/**
 * Product Data
 *
 * Displays the product data box, tabbed, with several panels covering price, stock etc.
 *
 * @package  WooCommerce\Admin\Meta Boxes
 * @version  3.0.0
 */

namespace TinySolutions\cptwoointpro\Controllers\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WC_Meta_Box_Product_Data;
use WC_Admin_Meta_Boxes;
use WC_Product_Factory;

/**
 * WC_Meta_Box_Product_Data Class.
 */
class ProductMetaBoxData extends WC_Meta_Box_Product_Data {

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id WP post id.
	 * @param WP_Post $post Post object.
	 */
	public static function save( $post_id, $post ) {

		if ( empty( $_POST['woocommerce_meta_nonce'] ) ) {
			return;
		}
		// Check the nonce.
		$nonce = sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'woocommerce_save_data' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Process product type first so we have the correct class to run setters.
		$product_type = empty( $_POST['product-type'] ) ? WC_Product_Factory::get_product_type( $post_id ) : sanitize_title( wp_unslash( $_POST['product-type'] ) );

		$classname = WC_Product_Factory::get_product_classname( $post_id, $product_type ? $product_type : 'simple' );

		$product = new $classname( $post_id );

		$attributes = self::prepare_attributes();
		$stock      = null;

		// Handle stock changes.

		if ( isset( $_POST['_stock'] ) ) {
			if ( isset( $_POST['_original_stock'] ) && wc_stock_amount( $product->get_stock_quantity( 'edit' ) ) !== wc_stock_amount( wp_unslash( $_POST['_original_stock'] ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				/* translators: 1: product ID 2: quantity in stock */
				WC_Admin_Meta_Boxes::add_error( sprintf( __( 'The stock has not been updated because the value has changed since editing. Product %1$d has %2$d units in stock.', 'woocommerce' ), $product->get_id(), $product->get_stock_quantity( 'edit' ) ) );
			} else {
				$stock = wc_stock_amount( wp_unslash( $_POST['_stock'] ) );  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
		}

		// Handle dates.
		$date_on_sale_from = '';
		$date_on_sale_to   = '';

		// Force date from to beginning of day.
		if ( isset( $_POST['_sale_price_dates_from'] ) ) {
			$date_on_sale_from = wc_clean( wp_unslash( $_POST['_sale_price_dates_from'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( ! empty( $date_on_sale_from ) ) {
				$date_on_sale_from = gmdate( 'Y-m-d 00:00:00', strtotime( $date_on_sale_from ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			}
		}

		// Force date to to the end of the day.
		if ( isset( $_POST['_sale_price_dates_to'] ) ) {
			$date_on_sale_to = wc_clean( wp_unslash( $_POST['_sale_price_dates_to'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( ! empty( $date_on_sale_to ) ) {
				$date_on_sale_to = gmdate( 'Y-m-d 23:59:59', strtotime( $date_on_sale_to ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			}
		}

		$errors = $product->set_props(
			[
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'sku'                => isset( $_POST['_sku'] ) ? wc_clean( wp_unslash( $_POST['_sku'] ) ) : null,// WPCS: sanitization ok.
				'purchase_note'      => isset( $_POST['_purchase_note'] ) ? wp_kses_post( wp_unslash( $_POST['_purchase_note'] ) ) : '',
				'downloadable'       => isset( $_POST['_downloadable'] ),
				'virtual'            => isset( $_POST['_virtual'] ),
				'featured'           => isset( $_POST['_featured'] ),
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'catalog_visibility' => isset( $_POST['_visibility'] ) ? wc_clean( wp_unslash( $_POST['_visibility'] ) ) : null, // WPCS: sanitization ok.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'tax_status'         => isset( $_POST['_tax_status'] ) ? wc_clean( wp_unslash( $_POST['_tax_status'] ) ) : null, // WPCS: sanitization ok.
				'tax_class'          => isset( $_POST['_tax_class'] ) ? sanitize_title( wp_unslash( $_POST['_tax_class'] ) ) : null, // WPCS: sanitization ok.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'weight'             => isset( $_POST['_weight'] ) ? wc_clean( wp_unslash( $_POST['_weight'] ) ) : null, // WPCS: sanitization ok.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'length'             => isset( $_POST['_length'] ) ? wc_clean( wp_unslash( $_POST['_length'] ) ) : null, // WPCS: sanitization ok.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'width'              => isset( $_POST['_width'] ) ? wc_clean( wp_unslash( $_POST['_width'] ) ) : null, // WPCS: sanitization ok.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'height'             => isset( $_POST['_height'] ) ? wc_clean( wp_unslash( $_POST['_height'] ) ) : null, // WPCS: sanitization ok.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'shipping_class_id'  => isset( $_POST['product_shipping_class'] ) ? absint( wp_unslash( $_POST['product_shipping_class'] ) ) : null, // WPCS: sanitization ok.
				'sold_individually'  => ! empty( $_POST['_sold_individually'] ),
				'upsell_ids'         => isset( $_POST['upsell_ids'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['upsell_ids'] ) ) : [], // WPCS: sanitization ok.
				'cross_sell_ids'     => isset( $_POST['crosssell_ids'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['crosssell_ids'] ) ) : [], // WPCS: sanitization ok.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'regular_price'      => isset( $_POST['_regular_price'] ) ? wc_clean( wp_unslash( $_POST['_regular_price'] ) ) : null, // WPCS: sanitization ok.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'sale_price'         => isset( $_POST['_sale_price'] ) ? wc_clean( wp_unslash( $_POST['_sale_price'] ) ) : null, // WPCS: sanitization ok.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'date_on_sale_from'  => $date_on_sale_from,
				'date_on_sale_to'    => $date_on_sale_to,
				'manage_stock'       => ! empty( $_POST['_manage_stock'] ),
				'backorders'         => isset( $_POST['_backorders'] ) ? wc_clean( wp_unslash( $_POST['_backorders'] ) ) : null, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'stock_status'       => isset( $_POST['_stock_status'] ) ? wc_clean( wp_unslash( $_POST['_stock_status'] ) ) : null, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'stock_quantity'     => $stock,
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'low_stock_amount'   => isset( $_POST['_low_stock_amount'] ) && '' !== $_POST['_low_stock_amount'] ? wc_stock_amount( wp_unslash( $_POST['_low_stock_amount'] ) ) : '', // WPCS: sanitization ok.
				'download_limit'     => isset( $_POST['_download_limit'] ) && '' !== $_POST['_download_limit'] ? absint( wp_unslash( $_POST['_download_limit'] ) ) : '',
				'download_expiry'    => isset( $_POST['_download_expiry'] ) && '' !== $_POST['_download_expiry'] ? absint( wp_unslash( $_POST['_download_expiry'] ) ) : '',
				// Those are sanitized inside prepare_downloads.
				'downloads'          => self::prepare_downloads(
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					isset( $_POST['_wc_file_names'] ) ? wc_clean( wp_unslash( $_POST['_wc_file_names'] ) ) : [], // WPCS: sanitization ok.
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					isset( $_POST['_wc_file_urls'] ) ? array_map( 'esc_url_raw', wp_unslash( $_POST['_wc_file_urls'] ) ) : [], // WPCS: sanitization ok.
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					isset( $_POST['_wc_file_hashes'] ) ? wc_clean( wp_unslash( $_POST['_wc_file_hashes'] ) ) : [] // WPCS: sanitization ok.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				),
				'product_url'        => isset( $_POST['_product_url'] ) ? esc_url_raw( wp_unslash( $_POST['_product_url'] ) ) : '',
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'button_text'        => isset( $_POST['_button_text'] ) ? wc_clean( wp_unslash( $_POST['_button_text'] ) ) : '', // WPCS: sanitization ok.
				'children'           => 'grouped' === $product_type ? self::prepare_children() : null,
				'reviews_allowed'    => ! empty( $_POST['comment_status'] ) && 'open' === $_POST['comment_status'],
				'attributes'         => $attributes,
				'default_attributes' => self::prepare_set_attributes( $attributes, 'default_attribute_' ),
			]
		);

		if ( is_wp_error( $errors ) ) {
			WC_Admin_Meta_Boxes::add_error( $errors->get_error_message() );
		}

		/**
		 * Set props before save.
		 *
		 * @since 3.0.0
		 */
		do_action( 'woocommerce_admin_process_product_object', $product );

		$product->save();

		if ( $product->is_type( 'variable' ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$original_post_title = isset( $_POST['original_post_title'] ) ? wc_clean( wp_unslash( $_POST['original_post_title'] ) ) : ''; // WPCS: sanitization ok.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$post_title = isset( $_POST['post_title'] ) ? wc_clean( wp_unslash( $_POST['post_title'] ) ) : ''; // WPCS: sanitization ok.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$product->get_data_store()->sync_variation_names( $product, $original_post_title, $post_title );
		}

		/* phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment */
		do_action( 'woocommerce_process_product_meta_' . $product_type, $post_id );
		/* phpcs:enable WordPress.Security.NonceVerification.Missing and WooCommerce.Commenting.CommentHooks.MissingHookComment */
		do_action( 'woocommerce_process_product_meta', $post_id, $post );
	}

	/**
	 * Prepare downloads for save.
	 *
	 * @param array $file_names File names.
	 * @param array $file_urls File urls.
	 * @param array $file_hashes File hashes.
	 *
	 * @return array
	 */
	private static function prepare_downloads( $file_names, $file_urls, $file_hashes ) {
		$downloads = [];

		if ( ! empty( $file_urls ) ) {
			$file_url_size = count( $file_urls );

			for ( $i = 0; $i < $file_url_size; $i++ ) {
				if ( ! empty( $file_urls[ $i ] ) ) {
					$downloads[] = [
						'name'        => wc_clean( $file_names[ $i ] ),
						'file'        => wp_unslash( trim( $file_urls[ $i ] ) ),
						'download_id' => wc_clean( $file_hashes[ $i ] ),
					];
				}
			}
		}

		return $downloads;
	}

	/**
	 * Prepare children for save.
	 *
	 * @return array
	 */
	private static function prepare_children() {
		if ( empty( $_POST['woocommerce_meta_nonce'] ) ) {
			return;
		}
		// Check the nonce.
		$nonce = sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'woocommerce_save_data' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}

		return isset( $_POST['grouped_products'] ) ? array_filter( array_map( 'intval', (array) $_POST['grouped_products'] ) ) : []; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Prepare attributes for a specific variation or defaults.
	 *
	 * @param array  $all_attributes List of attribute keys.
	 * @param string $key_prefix Attribute key prefix.
	 * @param int    $index Attribute array index.
	 *
	 * @return array
	 */
	private static function prepare_set_attributes( $all_attributes, $key_prefix = 'attribute_', $index = null ) {
		$attributes = [];
		if ( empty( $_POST['woocommerce_meta_nonce'] ) ) {
			return;
		}
		// Check the nonce.
		$nonce = sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'woocommerce_save_data' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}

		if ( $all_attributes ) {
			foreach ( $all_attributes as $attribute ) {
				if ( $attribute->get_variation() ) {
					$attribute_key = sanitize_title( $attribute->get_name() );

					if ( ! is_null( $index ) ) {
						// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$value = isset( $_POST[ $key_prefix . $attribute_key ][ $index ] ) ? sanitize_title( wp_unslash( $_POST[ $key_prefix . $attribute_key ][ $index ] ) ) : ''; // WPCS: sanitization ok.
					} else {
						// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$value = isset( $_POST[ $key_prefix . $attribute_key ] ) ? wc_clean( wp_unslash( $_POST[ $key_prefix . $attribute_key ] ) ) : ''; // WPCS: sanitization ok.
					}

					if ( ! $attribute->is_taxonomy() ) {
						$value = html_entity_decode( $value, ENT_QUOTES, get_bloginfo( 'charset' ) ); // WPCS: sanitization ok.
					}

					$attributes[ $attribute_key ] = $value;
				}
			}
		}

		return $attributes;
	}
}
