<?php
/**
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Bookings
 * Plugin URI:        https://woocommerce.com/products/woocommerce-bookings/
 * Version:           1.0.0
 * Author:            Tiny Solutions
 * Author URI:        https://www.wptinysolutions.com/
 * Tested up to:      6.4
 * WC tested up to:   8.4
 * Text Domain:       woocommerce-bookings
 * Domain Path:       /languages
 *
 * @package TinySolutions\WM
 */

namespace TinySolutions\cptwoointpro\PluginsSupport\WCBookings;

// Do not allow directly accessing this file.
use TinySolutions\cptwooint\Helpers\Fns;

use TinySolutions\cptwoointpro\Helpers\Functions;
use TinySolutions\cptwoointpro\Traits\SingletonTrait;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

/**
 * LPInit
 */
class WCBookingsInit {
	/**
	 * Singleton
	 */
	use SingletonTrait;

	/**
	 * Class Constructor
	 */
	private function __construct() {
		add_filter( 'woocommerce_data_stores', [ $this, 'cptwoo_data_stores' ], 15 );
		add_filter( 'woocommerce_screen_ids', [ $this, 'woocommerce_screen_ids' ], 20 );
		add_filter( 'get_booking_products_args', [ $this, 'get_booking_products_args' ], 20 );
		add_filter( 'cptwoo_product_is_set_price', [ $this, 'cptwoo_product_set_price' ], 12, 2 );
	}
	/**
	 * Product Data Store update.
	 *
	 * @param bool $is Woo Store.
	 *
	 * @return bool
	 */
	public function cptwoo_product_set_price( $is, $product ) {
		return 'booking' !== $product->get_type();
	}
	/**
	 * Product Data Store update.
	 *
	 * @param array $stores Woo Store.
	 *
	 * @return mixed
	 */
	public function cptwoo_data_stores( $stores ) {
		$stores['product-booking'] = CPTProductBookingDataStoreCPT::class;
		return $stores;
	}
	/**
	 * Product Data Store update.
	 *
	 * @param array $ids Woo Store.
	 *
	 * @return mixed
	 */
	public function woocommerce_screen_ids( $ids ) {
		$edit = [];
		foreach ( Fns::supported_post_types() as $item ) {
			$edit[] = $item;
			$edit[] = 'edit-' . $item;
		}
		return array_merge( $ids, $edit );
	}
	/**
	 * @param array $args argument.
	 * @return array
	 */
	public function get_booking_products_args( $args ) {
		if ( empty( $args['post_type'] ) ) {
			return $args;
		}
		if ( ! is_array( $args['post_type'] ) ) {
			$query_post_types[] = $args['post_type'];
		} else {
			$query_post_types = $args['post_type'];
		}
		$supported         = Fns::supported_post_types();
		$query_post_types  = array_merge( $query_post_types, $supported );
		$args['post_type'] = $query_post_types;
		return $args;
	}
}
