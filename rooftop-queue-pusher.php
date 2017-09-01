<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://errorstudio.co.uk
 * @since             1.0.0
 * @package           Rooftop_Queue_Pusher
 *
 * @wordpress-plugin
 * Plugin Name:       Rooftop Queue Pusher
 * Plugin URI:        https://github.com/rooftopcms/rooftop-queue-pusher
 * Description:       Handles pushing events onto the queues, and queue runners for hosted Rooftop.
 * Version:           1.2.1
 * Author:            RooftopCMS
 * Author URI:        http://rooftopcms.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       rooftop-queue-pusher
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rooftop-queue-pusher-activator.php
 */
function activate_rooftop_queue_pusher() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rooftop-queue-pusher-activator.php';
	Rooftop_Queue_Pusher_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-rooftop-queue-pusher-deactivator.php
 */
function deactivate_rooftop_queue_pusher() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rooftop-queue-pusher-deactivator.php';
	Rooftop_Queue_Pusher_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_rooftop_queue_pusher' );
register_deactivation_hook( __FILE__, 'deactivate_rooftop_queue_pusher' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-rooftop-queue-pusher.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_rooftop_queue_pusher() {

	$plugin = new Rooftop_Queue_Pusher();
	$plugin->run();

}
run_rooftop_queue_pusher();
