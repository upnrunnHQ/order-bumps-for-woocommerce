<?php
/**
 * Plugin Name:     Order Bumps for WooCommerce
 * Plugin URI:      https://github.com/upnrunnHQ/order-bumps
 * Description:     Display order bumps on the checkout page with AJAX updates and complex conditions.
 * Author:          Kishores
 * Author URI:      https://profiles.wordpress.org/kishores
 * Text Domain:     order-bumps
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Order_Bumps
 */

namespace UpnRunn\OrderBumpsForWooCommerce;
// use UpnRunn\OrderBumpsForWooCommerce\Order_Bumps;

defined('ABSPATH') || exit;

define( 'ORDER_BUMPS_VERSION', '0.1.0' );
define( 'ORDER_BUMPS_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'ORDER_BUMPS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'ORDER_BUMPS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Ensure you are using the correct namespace and file path.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-order-bumps.php';

/**
 * Main instance of Order_Bumps.
 *
 * @return Order_Bumps
 */
function ORDERBUMPS() {
    return Order_Bumps::instance();
}

$GLOBALS['order_bumps'] = ORDERBUMPS();