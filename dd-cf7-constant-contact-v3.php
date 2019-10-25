<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.howardehrenberg.com
 * @since             1.0.0
 * @package           dd_cf7_constant_contact_v3
 *
 * @wordpress-plugin
 * Plugin Name:       Connect Contact Form 7 to Constant Contact V3
 * Plugin URI:        https://www.duckdiverllc.com
 * Description:       Connect Contact form 7 and Constant Contact where it appends existing users - allows for multiple list subscriptions, and conditional subscribe checkbox.
 * Version:           0.0.15
 * Author:            Howard Ehrenberg
 * Author URI:        https://www.howardehrenberg.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dd-cf7-plugin
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/DuckDivers/dd-cf7-constant-contact-v3
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'DD_CF7_CONSTANT_CONTACT_V3_VERSION', '0.0.15' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-dd-cf7-constant-contact-v3-activator.php
 */
function activate_dd_cf7_constant_contact_v3() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dd-cf7-constant-contact-v3-activator.php';
	dd_cf7_constant_contact_v3_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-dd-cf7-constant-contact-v3-deactivator.php
 */
function deactivate_dd_cf7_constant_contact_v3() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-dd-cf7-constant-contact-v3-deactivator.php';
	dd_cf7_constant_contact_v3_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_dd_cf7_constant_contact_v3' );
register_deactivation_hook( __FILE__, 'deactivate_dd_cf7_constant_contact_v3' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-dd-cf7-constant-contact-v3.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_dd_cf7_constant_contact_v3() {

	$plugin = new dd_cf7_constant_contact_v3();
	$plugin->run();

}
run_dd_cf7_constant_contact_v3();
