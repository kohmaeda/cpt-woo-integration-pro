<?php
/**
 * Main initialization class.
 *
 * @package TinySolutions\cptwoointpro
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

use TinySolutions\cptwoointpro\Traits\SingletonTrait;
use TinySolutions\cptwoointpro\Controllers\Installation;
use TinySolutions\cptwoointpro\Controllers\Dependencies;
use TinySolutions\cptwoointpro\Controllers\ProductMetaController;
use TinySolutions\cptwoointpro\Hooks\FilterHooks;
use TinySolutions\cptwoointpro\Hooks\ActionHooks;
use TinySolutions\cptwoointpro\Hooks\StructuredData;
use TinySolutions\cptwoointpro\Controllers\ShortCodes;
use TinySolutions\cptwoointpro\Controllers\AssetsController;
use TinySolutions\cptwoointpro\Hooks\CommentHooks;
use TinySolutions\cptwoointpro\PluginsSupport\RootSupport;


if ( ! class_exists( CptWooProInt::class ) ) {
	/**
	 * Main initialization class.
	 */
	final class CptWooProInt {

		/**
		 * Singleton
		 */
		use SingletonTrait;

		/**
		 * Class Constructor
		 */
		private function __construct() {
			add_action( 'init', [ $this, 'language' ] );
			add_action( 'plugins_loaded', [ $this, 'plugins_loaded_init' ], 12 );
			// Register Plugin Active Hook.
			register_activation_hook( CPTWIP_FILE, [ Installation::class, 'activate' ] );
			// Register Plugin Deactivate Hook.
			register_deactivation_hook( CPTWIP_FILE, [ Installation::class, 'deactivation' ] );
		}

		/**
		 * Assets url generate with given assets file
		 *
		 * @param string $file File.
		 *
		 * @return string
		 */
		public function get_assets_uri( $file ) {
			$file = ltrim( $file, '/' );

			return trailingslashit( CPTWIP_URL . '/assets' ) . $file;
		}

		/**
		 * Get the template path.
		 *
		 * @return string
		 */
		public function get_template_path() {
			return apply_filters( 'cptwooint_template_path', 'templates/' );
		}

		/**
		 * Get the plugin path.
		 *
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( CPTWIP_FILE ) );
		}

		/**
		 * Load Text Domain
		 */
		public function language() {
			load_plugin_textdomain( 'cptwoointpro', false, CPTWIP_ABSPATH . '/languages/' );
		}

		/**
		 * Init
		 *
		 * @return void
		 */
		public function plugins_loaded_init() {
			if ( ! Dependencies::instance()->check() ) {
				return;
			}

			do_action( 'cptwoointpro/before_loaded' );

			// Include File.
			RootSupport::instance();
			AssetsController::instance();
			CommentHooks::init();
			FilterHooks::instance();
			ActionHooks::instance();
			StructuredData::instance();
			if ( is_admin() ) {
				ProductMetaController::instance();
			} else {
				ShortCodes::instance();
			}

			do_action( 'cptwoointpro/after_loaded' );
		}

		/**
		 * Pro Check
		 *
		 * @return bool
		 */
		public function user_can_use_cptwooinitpro() {
			return Dependencies::instance()->is_version_compatibile() && cwip_fs()->can_use_premium_code();
		}
	}

	/**
	 * Instance create
	 *
	 * @return CptWooProInt
	 */
	function cptwoointp() {
		return CptWooProInt::instance();
	}

	cptwoointp();
}
