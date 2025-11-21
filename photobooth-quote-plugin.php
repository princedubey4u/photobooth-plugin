<?php
/**
 * Plugin Name: Photobooth Quote Request
 * Plugin URI: https://example.com
 * Description: WooCommerce-integrated photobooth quote request form with admin dashboard
 * Version: 1.3.0
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

define('PBQR_PATH', plugin_dir_path(__FILE__));
define('PBQR_URL', plugin_dir_url(__FILE__));
define('PBQR_VERSION', '1.0.0');

// Include required files
require_once PBQR_PATH . 'includes/class-activator.php';
require_once PBQR_PATH . 'includes/class-form-handler.php';
require_once PBQR_PATH . 'includes/class-admin-page.php';

// Activation hook
register_activation_hook(__FILE__, ['PBQR_Activator', 'activate']);

// Check WooCommerce
add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Photobooth Quote Request:</strong> WooCommerce is required. Please install and activate WooCommerce.</p></div>';
        });
    }
});

// Enqueue frontend assets
function pbqr_enqueue_assets() {
    wp_enqueue_style('pbqr-style', PBQR_URL . 'assets/css/style.css', [], PBQR_VERSION);
    wp_enqueue_script('pbqr-script', PBQR_URL . 'assets/js/form.js', ['jquery'], PBQR_VERSION, true);
}
add_action('wp_enqueue_scripts', 'pbqr_enqueue_assets');

// Shortcode
function pbqr_quote_form_shortcode($atts = []) {
    $atts = shortcode_atts(['default_package_id' => 0], $atts);
    $GLOBALS['pbqr_default_package_id'] = intval($atts['default_package_id']);

    ob_start();
    include PBQR_PATH . 'templates/quote-form.php';
    $html = ob_get_clean();

    unset($GLOBALS['pbqr_default_package_id']);
    return $html;
}
add_shortcode('photobooth_quote', 'pbqr_quote_form_shortcode');

// Handle form submission
add_action('init', ['PBQR_Form_Handler', 'handle_submit']);

// Admin menu
add_action('admin_menu', ['PBQR_Admin_Page', 'register_menu']);
