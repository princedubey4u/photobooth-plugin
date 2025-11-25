<?php
/**
 * Plugin Name: Photobooth Quote Request
 * Plugin URI: https://example.com
 * Description: WooCommerce-integrated photobooth quote request form with admin dashboard
 * Version: 1.4.0
 * Author: Odylabs
 * Author URI: https://odylabs.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pbqr
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('PBQR_VERSION', '2.0.0');
define('PBQR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PBQR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once PBQR_PLUGIN_DIR . 'includes/class-activator.php';
require_once PBQR_PLUGIN_DIR . 'includes/class-admin-page.php';
require_once PBQR_PLUGIN_DIR . 'includes/class-calendar-manager.php';
require_once PBQR_PLUGIN_DIR . 'includes/class-form-handler.php';

// Activation hook
register_activation_hook(__FILE__, ['PBQR_Activator', 'activate']);

// Initialize plugin
add_action('plugins_loaded', 'pbqr_init');
function pbqr_init() {
    // Admin menu
    add_action('admin_menu', ['PBQR_Admin_Page', 'register_menu']);
    add_action('admin_menu', ['PBQR_Calendar_Manager', 'register_menu']);
    
    // Form handler
    add_action('init', ['PBQR_Form_Handler', 'handle_submit']);
    
    // Enqueue assets
    add_action('wp_enqueue_scripts', 'pbqr_enqueue_assets');
    
    // Register shortcode
    add_shortcode('photobooth_quote', 'pbqr_shortcode');
}

// Enqueue CSS and JS
function pbqr_enqueue_assets() {
    wp_enqueue_style('pbqr-style', PBQR_PLUGIN_URL . 'assets/css/style.css', [], PBQR_VERSION);
    wp_enqueue_script('pbqr-script', PBQR_PLUGIN_URL . 'assets/js/form.js', ['jquery'], PBQR_VERSION, true);
}

// Shortcode handler
function pbqr_shortcode($atts) {
    $atts = shortcode_atts([
        'default_package_id' => 0,
    ], $atts);
    
    $GLOBALS['pbqr_default_package_id'] = intval($atts['default_package_id']);
    
    ob_start();
    include PBQR_PLUGIN_DIR . 'templates/quote-form.php';
    return ob_get_clean();
}
