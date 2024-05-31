<?php
/**
 * Main FilterHooks class.
 *
 * @package TinySolutions\WM
 */

namespace TinySolutions\cptwoointpro\Hooks;

use TinySolutions\cptwooint\Helpers\Fns;
use TinySolutions\cptwoointpro\Modal\CptProductGroupedDataStore;
use TinySolutions\cptwoointpro\Traits\SingletonTrait;
use TinySolutions\cptwoointpro\Modal\CptVariableProductDataStore;
use TinySolutions\cptwoointpro\Modal\CptVariationProductDataStore;

defined( 'ABSPATH' ) || exit();

/**
 * Main FilterHooks class.
 */
class FilterHooks {
	/**
	 * Singleton
	 */
	use SingletonTrait;

	/**
	 * Init Hooks.
	 *
	 * @return void
	 */
	private function __construct() {
		// Plugins Setting Page.
		add_filter( 'woocommerce_product_reviews_list_table_prepare_items_args', [ $this, 'reviews_list_table_prepare_items_args' ], 51 );
		add_filter( 'woocommerce_data_stores', [ $this, 'cptwoo_data_stores' ], 99 );
		add_filter( 'woocommerce_product_type_query', [ $this, 'cptwoo_product_type_query' ], 20, 2 );
		add_filter( 'cptwooint/add/get-pro/submenu/label', [ $this, 'submenu_get_pro_label' ], 11 );
		add_filter( 'comments_template', [ $this, 'comments_template_loader' ] );
	}

	/**
	 * Load comments template.
	 *
	 * @param string $template template to load.
	 * @return string
	 */
	public function comments_template_loader( $template ) {
		$type         = get_post_type();
		$is_supported = Fns::is_review_enabled( $type );
		if ( ! $is_supported ) {
			return $template;
		}
		$check_dirs = [
			trailingslashit( get_stylesheet_directory() ) . WC()->template_path(),
			trailingslashit( get_template_directory() ) . WC()->template_path(),
			trailingslashit( get_stylesheet_directory() ),
			trailingslashit( get_template_directory() ),
			trailingslashit( WC()->plugin_path() ) . 'templates/',
		];

		if ( WC_TEMPLATE_DEBUG_MODE ) {
			$check_dirs = [ array_pop( $check_dirs ) ];
		}

		foreach ( $check_dirs as $dir ) {
			if ( file_exists( trailingslashit( $dir ) . 'single-product-reviews.php' ) ) {
				return trailingslashit( $dir ) . 'single-product-reviews.php';
			}
		}
		return $template;
	}

	/**
	 * Submenu Label
	 *
	 * @param string $label Menu Label.
	 * @return mixed|string
	 * @throws \Freemius_Exception Freemius Feature Added.
	 */
	public function submenu_get_pro_label( $label ) {
		if ( cwip_fs()->is_paying() ) {
			$label = 'Get License';
		} else {
			$label = 'Active License';
		}

		return $label;
	}
	
	/**
	 * Retrieve the custom product type for a given product ID.
	 *
	 * @param string $product_type Product type.
	 * @param int    $product_id product id.
	 *
	 * @return mixed|string
	 */
	public function cptwoo_product_type_query( $product_type, $product_id ) {
		$current_post_type = get_post_type( $product_id );
		if ( ! Fns::is_supported( $current_post_type ) ) {
			return $product_type;
		}
		$terms = get_the_terms( $product_id, 'product_type' );

		$product_type = ! empty( $terms ) && ! is_wp_error( $terms ) ? sanitize_title( current( $terms )->name ) : 'simple';

		return $product_type;
	}

	/**
	 * Product Data Store update.
	 *
	 * @param array $stores Woo Store.
	 *
	 * @return mixed
	 */
	public function cptwoo_data_stores( $stores ) {
		$stores['product-variation'] = CptVariationProductDataStore::class;
		$stores['product-variable']  = CptVariableProductDataStore::class;
		$stores['product-grouped']   = CptProductGroupedDataStore::class;

		return $stores;
	}

	/**
	 * @param $args
	 * @return mixed
	 */
	public function reviews_list_table_prepare_items_args( $args ) {
		$add_support = [ 'best-books' ];
		if ( is_array( $args['post_type'] ) ) {
			$args['post_type'] = array_merge( $args['post_type'], $add_support );
		} else {
			$args['post_type'] = array_merge( [ $args['post_type'] ], $add_support );
		}
		return $args;
	}
}
