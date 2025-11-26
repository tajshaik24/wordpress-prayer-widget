<?php

/**
 * The plugin bootstrap file
 *
 * @wordpress-plugin
 * Plugin Name:       Prayer Times Widget
 * Description:       Display prayer times from Masjidi API for one or two masjids. Use the shortcode [masjidi_prayer_times] or the widget.
 * Version:           2.0.0
 * Author:            Masjidi
 * Author URI:        https://icfbayarea.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       masjidi
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/* Plugin Name */
$cwebPluginName = "masjidi";

/* Use Domain as the folder name */
$PluginTextDomain = "masjidi";

/**
 * The code that runs during plugin activation.
 */
if (!function_exists('mptsi_activate_ado_plugin')) {
    function mptsi_activate_ado_plugin() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/classes/activate-class.php';
        masjidi_namespace\MPSTI_Plugin_Activator::activate();
    }
}

/**
 * The code that runs during plugin deactivation.
 */
if (!function_exists('mptsi_deactivate_ado_plugin')) {
    function mptsi_deactivate_ado_plugin() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/classes/deactive-class.php';
        masjidi_namespace\MPSTI_Plugin_Deactivator::deactivate();
    }
}

/* Register Hooks For Start And Deactivate */
register_activation_hook( __FILE__, 'mptsi_activate_ado_plugin' );
register_deactivation_hook( __FILE__, 'mptsi_deactivate_ado_plugin' );

/**
 * The core plugin class that is used to define internationalization,
 */
require plugin_dir_path( __FILE__ ) . 'includes/classes/classCweb.php';

/* Include the Files in which we define the shortcodes for front End */
require plugin_dir_path( __FILE__ ) . 'public/short-codes.php';

/**
 * Begins execution of the plugin.
 */
if (!function_exists('mptsi_run_plugin_name_masjidi')) {
    function mptsi_run_plugin_name_masjidi() {
        $plugin = new MPSTI_cWebClassado();
        $plugin->run();
    }
}
mptsi_run_plugin_name_masjidi();

/* Constants */
define('CWEB_MASJIDI_PATH', plugin_dir_path(__FILE__)); 
define('CWEB_MASJIDI_URL', plugin_dir_url(__FILE__)); 

/* Include Functions File */
require plugin_dir_path( __FILE__ ) . 'includes/function/functions.php';
