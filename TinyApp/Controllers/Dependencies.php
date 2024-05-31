<?php

namespace TinySolutions\cptwoointpro\Controllers;

use TinySolutions\cptwoointpro\Traits\SingletonTrait;

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}

/**
 * Dependencies
 */
class Dependencies {
	/**
	 * Singleton
	 */
	use SingletonTrait;

	/**
	 * Plugin Name
	 */
	const PLUGIN_NAME = 'Custom Post Type Woocommerce Integration';

	/**
	 * Php Version
	 */
	const MINIMUM_PHP_VERSION = '7.4';

	/**
	 * Main Plugin Version
	 */
	const MINIMUM_CPTWI_VERSION = '1.3.1';
	/**
	 * Statur
	 *
	 * @var bool
	 */
	private $allOk = true;
	/**
	 * Statur
	 *
	 * @var bool
	 */
	private $is_active_main = false;
	/**
	 * Statur
	 *
	 * @var bool
	 */
	private $is_php_compatible = true;

	/**
	 * Checking Status
	 *
	 * @return bool
	 */
	public function check() {
		if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
			$this->allOk             = false;
			$this->is_php_compatible = false;
		}
        //$is_active = in_array( 'cpt-woo-integration/cpt-woo-integration.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true );
        //if ( is_multisite() ) {
        //    if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
        //        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        //    }
        //    if ( function_exists( 'is_plugin_active_for_network' ) ) {
        //        $is_active = is_plugin_active_for_network( 'cpt-woo-integration/cpt-woo-integration.php' ) || $is_active;
        //    }
        //}
		// $this->is_active_main = $is_active;
		//if ( ! $is_active ) {
		//	$this->allOk = false;
		//}
		add_action( 'admin_notices', [ $this, 'show_admin_notice' ] );
		return $this->allOk;
	}
	/**
	 * Checking Status
	 *
	 * @return bool
	 */
	public function is_version_compatibile() {
		if ( defined( 'CPTWI_VERSION' ) && version_compare( CPTWI_VERSION, self::MINIMUM_CPTWI_VERSION, '<' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Checking Status
	 *
	 * @return void
	 */
	public function show_admin_notice() {
		if ( ! $this->is_version_compatibile() ) {
			 $this->main_plugin_minimum_version();
		}
		if ( ! $this->is_php_compatible ) {
			$this->minimum_php_version();
		}
	}
	/**
	 * Plugin Version Notice
	 *
	 * @return void
	 */
	public function main_plugin_minimum_version() {
		$link = add_query_arg(
			[
				'tab'       => 'plugin-information',
				'plugin'    => 'cpt-woo-integration',
				'TB_iframe' => 'true',
				'width'     => '640',
				'height'    => '500',
			],
			admin_url( 'plugin-install.php' )
		);
		?>
		<div class="notice notice-error">
			<p>
				<strong>Custom Post Type Woocommerce Integration PRO</strong>
				is not working, You need to install and activate
				<a class="thickbox open-plugin-details-modal" href="<?php echo esc_url( $link ); ?>">
					<strong>Custom Post Type Woocommerce Integration</strong>
				</a> free,
				<strong>Minimum version <?php echo esc_html( self::MINIMUM_CPTWI_VERSION ); ?></strong>.
			</p>
		</div>
		<?php
	}

	/**
	 * Admin Notice For Required PHP Version
	 */
	public function minimum_php_version() {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php echo esc_html( self::PLUGIN_NAME ); ?> <strong> requires PHP </strong>
				version <?php echo esc_html( self::MINIMUM_PHP_VERSION ); ?> or greater.
			</p>
		</div>
		<?php
	}
}
