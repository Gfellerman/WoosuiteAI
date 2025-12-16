<?php
/**
 * Plugin Name: WooSuite AI
 * Plugin URI:  https://woosuite.ai
 * Description: The Ultimate All-in-One WordPress Plugin. Security, SEO, Marketing, Backup, and Speed - Powered by Gemini AI.
 * Version:     1.0.0
 * Author:      WooSuite AI Team
 * Author URI:  https://woosuite.ai
 * License:     GPL-2.0+
 * Text Domain: woosuite-ai
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define Constants
define( 'WOOSUITE_AI_VERSION', '1.0.0' );
define( 'WOOSUITE_AI_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOOSUITE_AI_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_woosuite_ai() {
	require_once WOOSUITE_AI_PATH . 'includes/class-woosuite-activator.php';
	WooSuite_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_woosuite_ai() {
	require_once WOOSUITE_AI_PATH . 'includes/class-woosuite-deactivator.php';
	WooSuite_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woosuite_ai' );
register_deactivation_hook( __FILE__, 'deactivate_woosuite_ai' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once WOOSUITE_AI_PATH . 'includes/class-woosuite-core.php';

/**
 * Begins execution of the plugin.
 */
function run_woosuite_ai() {
	$plugin = new WooSuite_Core();
	$plugin->run();
}
run_woosuite_ai();
