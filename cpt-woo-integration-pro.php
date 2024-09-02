<?php

/**
 * Plugin Name:       Custom Post Type WooCommerce Integration Pro
 * Plugin URI:        https://www.wptinysolutions.com/tiny-products/cpt-woo-integration/
 * Description:       Integrate custom post type with woocommerce. Sell Any Kind Of Custom Post
 * Version:           1.2.6
 * Update URI: https://api.freemius.com
 * Author:            Tiny Solutions
 * Author URI:        https://wptinysolutions.com/
 * Tested up to:        6.5
 * WC requires at least:3.2
 * WC tested up to:     8.3
 * Text Domain:       cptwoointpro
 * Domain Path:       /languages
 *
 * @package TinySolutions\WM
 */
// Do not allow directly accessing this file.
if ( !defined( 'ABSPATH' ) ) {
    exit( 'This script cannot be accessed directly.' );
}
use TinySolutions\cptwoointpro\Controllers\Dependencies;
/**
 * Define cptwooint Constant.
 */
define( 'CPTWIP_VERSION', '1.2.6' );
define( 'CPTWIP_FILE', __FILE__ );
define( 'CPTWIP_BASENAME', plugin_basename( CPTWIP_FILE ) );
define( 'CPTWIP_URL', plugins_url( '', CPTWIP_FILE ) );
define( 'CPTWIP_ABSPATH', dirname( CPTWIP_FILE ) );
define( 'CPTWIP_PATH', plugin_dir_path( __FILE__ ) );
// HPOS.
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );
require_once CPTWIP_PATH . 'vendor/autoload.php';
$is_active = in_array( 'cpt-woo-integration/cpt-woo-integration.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
if ( is_multisite() ) {
    if ( !function_exists( 'is_plugin_active_for_network' ) ) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if ( function_exists( 'is_plugin_active_for_network' ) ) {
        $is_active = is_plugin_active_for_network( 'cpt-woo-integration/cpt-woo-integration.php' ) || $is_active;
    }
}
if ( $is_active ) {
    if ( !function_exists( 'cwip_fs' ) ) {
        /**
         * Include Freemius SDK.
         *
         * @return Freemius
         * @throws Freemius_Exception  Freemius Feature Added.
         */
        function cwip_fs() {
            global $cwip_fs;
            if ( !isset( $cwip_fs ) ) {
                // Include Freemius SDK.
                if ( !file_exists( CPTWIP_PATH . 'freemius/start.php' ) ) {
                    return $cwip_fs;
                }
                require_once CPTWIP_PATH . 'freemius/start.php';
                $cwip_fs = fs_dynamic_init( [
                    'id'               => '13672',
                    'slug'             => 'cpt-woo-integration-pro',
                    'premium_slug'     => 'cpt-woo-integration-pro',
                    'type'             => 'plugin',
                    'public_key'       => 'pk_9ee73548ab8553ef17a0668ce57c3',
                    'is_premium'       => true,
                    'is_premium_only'  => true,
                    'has_addons'       => false,
                    'has_paid_plans'   => true,
                    'is_org_compliant' => false,
                    'menu'             => [
                        'slug'       => 'cptwooint-get-pro',
                        'first-path' => 'admin.php?page=cptwooint-admin',
                        'contact'    => false,
                        'support'    => false,
                        'parent'     => [
                            'slug' => 'cptwooint-admin',
                        ],
                    ],
                    'is_live'          => true,
                ] );
            }
            return $cwip_fs;
        }

        // Init Freemius.
        cwip_fs();
        // Signal that SDK was initiated.
        do_action( 'cwip_fs_loaded' );
    }
    /**
     * App Init.
     */
    require_once 'TinyApp/cptwooint-pro.php';
} else {
    Dependencies::instance()->check();
}