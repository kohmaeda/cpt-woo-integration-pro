<?php

namespace TinySolutions\cptwoointpro\Controllers;

use TinySolutions\cptwoointpro\Traits\SingletonTrait;

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

/**
 * AssetsController
 */
class AssetsController {
	/**
	 * Singleton
	 */
	use SingletonTrait;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Ajax URL
	 *
	 * @var string
	 */
	private $ajaxurl;

	/**
	 * Class Constructor
	 */
	public function __construct() {
		$this->version = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? time() : CPTWIP_VERSION;
		add_action( 'wp_enqueue_scripts', [ $this, 'frontend_assets' ], 15 );
	}

	/**
	 * Frontend Script
	 */
	public function frontend_assets() {

		wp_deregister_style( 'cptwooint-public' );

		$styles = [
			[
				'handle' => 'cptwooint-public',
				'src'    => cptwoointp()->get_assets_uri( 'css/frontend/frontend.css' ),
			],
		];

		// Register public styles.
		foreach ( $styles as $style ) {
			wp_register_style( $style['handle'], $style['src'], '', $this->version );
		}

		$scripts = [
			[
				'handle' => 'cptwooint-public',
				'src'    => cptwoointp()->get_assets_uri( 'js/frontend/frontend-script.js' ),
				'deps'   => [ 'jquery' ],
				'footer' => true,
			],
			'zoom' => [
				'handle' => 'zoom',
				'src'    => WC()->plugin_url() . '/assets/js/zoom/jquery.zoom.min.js',
				'deps'   => [ 'jquery' ],
				'footer' => true,
			],
		];

		// Register public scripts.
		foreach ( $scripts as $script ) {
			wp_register_script( $script['handle'], $script['src'], $script['deps'], $this->version, $script['footer'] );
		}
	}
}
