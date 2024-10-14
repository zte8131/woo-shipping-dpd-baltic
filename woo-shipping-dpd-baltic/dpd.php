<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://dpd.com
 * @since             1.0.0
 * @package           Dpd
 *
 * @wordpress-plugin
 * Plugin Name:       DPD Baltic Shipping
 * Description:       DPD baltic shipping plugin for WooCommerce.
 * Version:           1.2.83
 * Author:            DPD
 * Author URI:        https://dpd.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-shipping-dpd-baltic
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Tested up to: 6.6.1
 * WC requires at least: 8.2.0
 * WC tested up to: 8.9.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'DPD_NAME_VERSION', '1.2.80' );

/**
 * Currently plugin name.
 */
define( 'DPD_PLUGIN_NAME', 'DPD Baltic Shipping' );

/**
 * Compatibility wordpress version.
 */
define( 'DPD_COMPATIBILITY_WP_VERSION', '6.2' );

/**
 * Compatibility woocommerce version.
 */
define( 'DPD_COMPATIBILITY_WOOCOMMERCE_VERSION', '8.2.0' );

/**
 * Compatibility HPOS woocommerce version.
 */
define( 'DPD_COMPATIBILITY_MINIMUM_HPOS_WOOCOMMERCE_VERSION', '8.2.0' );

const DPD_DOES_NOT_FIT_IN_TERMINAL = 'dpd_does_not_fit_in_terminal';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-dpd-baltic-activator.php
 */
function activate_dpd() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dpd-baltic-activator.php';
	Dpd_Baltic_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-dpd-baltic-deactivator.php
 */
function deactivate_dpd() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dpd-baltic-deactivator.php';
	Dpd_Baltic_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_dpd' );
register_deactivation_hook( __FILE__, 'deactivate_dpd' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-dpd.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_dpd() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dpd-baltic-activator.php';
	Dpd_Baltic_Activator::update();

	$plugin = new Dpd();
	$plugin->run();
}

run_dpd();
