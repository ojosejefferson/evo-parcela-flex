<?php
/**
 * Plugin Name: Evo Parcela Flex for Woo
 * Description: Gerencie taxas e descontos por método de pagamento, parcelamento e descontos por quantidade com alta performance.
 * Version: 5.0.5
 * Author: José Jefferson
 * Author URI: https://evo.com.br
 * Text Domain: evo-parcela-flex
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Constants
define( 'EVO_PARCELA_FLEX_VERSION', '5.0.5' );
define( 'EVO_PARCELA_FLEX_PATH', plugin_dir_path( __FILE__ ) );
define( 'EVO_PARCELA_FLEX_URL', plugin_dir_url( __FILE__ ) );
define( 'EVO_PARCELA_FLEX_BASENAME', plugin_basename( __FILE__ ) );

// Load Autoloader
require_once EVO_PARCELA_FLEX_PATH . 'includes/Autoloader.php';

use EvoParcelaFlex\Autoloader;
use EvoParcelaFlex\Controller\AdminController;
use EvoParcelaFlex\Controller\FrontendController;
use EvoParcelaFlex\Controller\CheckoutController;

/**
 * Main Plugin Class
 */
class EvoParcelaFlex {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init();
    }

    private function init() {
        // Initialize Autoloader
        Autoloader::register();

        // HPOS Compatibility
        add_action( 'before_woocommerce_init', [ $this, 'declare_hpos_compatibility' ] );

        // Initialize Controllers
        if ( is_admin() ) {
            new AdminController();
        }

        new FrontendController();
        new CheckoutController();
    }

    /**
     * Declare compatibility with High-Performance Order Storage (HPOS)
     */
    public function declare_hpos_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', EVO_PARCELA_FLEX_BASENAME, true );
        }
    }
}

// Initialize the plugin
function evo_parcela_flex() {
    return EvoParcelaFlex::instance();
}

add_action( 'plugins_loaded', 'evo_parcela_flex' );
