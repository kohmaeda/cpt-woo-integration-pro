<?php
/**
 * Fns Helpers class
 *
 * @package  TinySolutions\cptwoointpro
 */

namespace TinySolutions\cptwoointpro\Helpers;

// Do not allow directly accessing this file.
use TinySolutions\cptwooint\Helpers\Fns;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

/**
 * Fns class
 */
class Functions {

	/**
	 * Check is plugin installed
	 *
	 * @param string $plugin_file_path file path.
	 *
	 * @return bool
	 */
	public static function is_plugins_installed( $plugin_file_path = null ) {
		$installed_plugins_list = get_plugins();

		return isset( $installed_plugins_list[ $plugin_file_path ] );
	}

	/**
	 * Outputs a list of product attributes for a product.
	 *
	 * @param \WC_Product $product Product Object.
	 *
	 * @since  3.0.0
	 */
	public static function display_product_attributes( $product ) {
		$product_attributes = [];
		// Display weight and dimensions before attribute list.
		$display_dimensions = apply_filters( 'wc_product_enable_dimensions_display', $product->has_weight() || $product->has_dimensions() );
		if ( $display_dimensions && $product->has_weight() ) {
			$product_attributes['weight'] = [
				'label' => __( 'Weight', 'woocommerce' ),
				'value' => wc_format_weight( $product->get_weight() ),
			];
		}

		if ( $display_dimensions && $product->has_dimensions() ) {
			$product_attributes['dimensions'] = [
				'label' => __( 'Dimensions', 'woocommerce' ),
				'value' => wc_format_dimensions( $product->get_dimensions( false ) ),
			];
		}

		// Add product attributes to list.
		$attributes = array_filter( $product->get_attributes(), 'wc_attributes_array_filter_visible' );

		foreach ( $attributes as $attribute ) {
			$values = [];

			if ( $attribute->is_taxonomy() ) {
				$attribute_values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), [ 'fields' => 'all' ] );
				foreach ( $attribute_values as $attribute_value ) {
					$values[] = esc_html( $attribute_value->name );
				}
			} else {
				$values = $attribute->get_options();
				foreach ( $values as &$value ) {
					$value = make_clickable( esc_html( $value ) );
				}
			}

			$product_attributes[ 'attribute_' . sanitize_title_with_dashes( $attribute->get_name() ) ] = [
				'label' => wc_attribute_label( $attribute->get_name() ),
				'value' => apply_filters( 'woocommerce_attribute', wpautop( wptexturize( implode( ', ', $values ) ) ), $attribute, $values ),
			];
		}

		/**
		 * Hook: woocommerce_display_product_attributes.
		 *
		 * @param array $product_attributes Array of attributes to display; label, value.
		 * @param WC_Product $product Showing attributes for this product.
		 *
		 * @since 3.6.0.
		 */
		$product_attributes = apply_filters( 'woocommerce_display_product_attributes', $product_attributes, $product );

		wc_get_template(
			'single-product/product-attributes.php',
			[
				'product_attributes' => $product_attributes,
				// Legacy params.
				'product'            => $product,
				'attributes'         => $attributes,
				'display_dimensions' => $display_dimensions,
			]
		);
	}


	/**
	 *
	 * @return array|int[]|string[]
	 */
	public static function get_supported_post_type_in_shop() {
		$options = Fns::get_options();
		$enabled = $options['enable_post_for_shop_page'] ?? [];
		if ( ! is_array( $enabled ) ) {
			return [];
		}
		$supported = Fns::supported_post_types();
		return array_intersect( $supported, $enabled );
	}

	/**
	 *
	 * @return int[]
	 */
	public static function get_hide_post_ids_for_shop_page() {
		$ids       = [];
		$supported = self::get_supported_post_type_in_shop();
		if ( empty( $supported ) ) {
			return $ids;
		}

		$options       = Fns::get_options();
		$shop_page_ids = $options['cpt_hide_post_for_shop_page'] ?? [];
		if ( empty( $shop_page_ids ) ) {
			return $ids;
		}

		foreach ( $supported as $items ) {
			if ( empty( $shop_page_ids[ $items ] ) || ! is_array( $shop_page_ids[ $items ] ) ) {
				continue;
			}
			$ids = array_merge( $ids, $shop_page_ids[ $items ] );
		}

		return $ids;
	}
}
