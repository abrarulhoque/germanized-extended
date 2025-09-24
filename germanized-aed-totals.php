<?php
/**
 * Plugin Name: Germanized AED Totals
 * Description: Extends WooCommerce Germanized StoreaBill with AED currency totals for invoices
 * Version: 1.1.0
 * Author: Abrar
 * Author URI: https://abrarulhoque.com
 * Requires at least: 5.4
 * Tested up to: 6.4
 * WC requires at least: 3.9
 * WC tested up to: 8.3
 * Text Domain: germanized-aed-totals
 * Domain Path: /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'GAED_VERSION', '1.1.0' );
define( 'GAED_PLUGIN_FILE', __FILE__ );
define( 'GAED_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'GAED_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Check if WooCommerce Germanized Pro is active
add_action( 'plugins_loaded', 'gaed_init_plugin' );

function gaed_init_plugin() {
    // Check if required dependencies are available
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'gaed_missing_woocommerce_notice' );
        return;
    }

    if ( ! class_exists( '\Vendidero\StoreaBill\Package' ) ) {
        add_action( 'admin_notices', 'gaed_missing_germanized_pro_notice' );
        return;
    }

    // Initialize the plugin
    require_once GAED_PLUGIN_PATH . 'includes/class-gaed-main.php';
    GAED_Main::init();
}

function gaed_missing_woocommerce_notice() {
    echo '<div class="notice notice-error"><p><strong>Germanized AED Totals:</strong> WooCommerce is required for this plugin to work.</p></div>';
}

function gaed_missing_germanized_pro_notice() {
    echo '<div class="notice notice-error"><p><strong>Germanized AED Totals:</strong> WooCommerce Germanized Pro with StoreaBill is required for this plugin to work.</p></div>';
}

// Activation and deactivation hooks
register_activation_hook( __FILE__, 'gaed_plugin_activate' );
register_deactivation_hook( __FILE__, 'gaed_plugin_deactivate' );

function gaed_plugin_activate() {
    // Create tables or options on activation
    if ( ! wp_next_scheduled( 'gaed_update_exchange_rates' ) ) {
        wp_schedule_event( time(), 'daily', 'gaed_update_exchange_rates' );
    }
}

function gaed_plugin_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook( 'gaed_update_exchange_rates' );
}
