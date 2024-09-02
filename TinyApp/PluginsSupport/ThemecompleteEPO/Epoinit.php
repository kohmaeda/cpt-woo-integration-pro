<?php

namespace TinySolutions\cptwoointpro\PluginsSupport\ThemecompleteEPO;

// Do not allow directly accessing this file.
use TinySolutions\cptwooint\Helpers\Fns;
use TinySolutions\cptwoointpro\Traits\SingletonTrait;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

/**
 * LPInit
 */
class Epoinit {
	/**
	 * Singleton
	 */
	use SingletonTrait;
	
	
	/**
	 * @var object
	 */
	protected $loader;
	
	/**
	 * Class Constructor
	 */
	private function __construct() {
		add_filter( 'wc_epo_admin_in_product', [ $this, 'epo_admin_in_product' ] );
	}
	/**
	 * @return array
	 */
	public function epo_admin_in_product( $post_types ) {
		return array_merge( $post_types, Fns::supported_post_types() );
	}
}
