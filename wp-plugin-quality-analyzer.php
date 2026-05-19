<?php
/**
 * Plugin Name: WP Plugin Quality Analyzer
 * Plugin URI: https://github.com/bijit027/wp-plugin-quality-analyzer
 * Description: Empirical research tool for analyzing software quality metrics across WordPress plugins. Built for MS CS thesis research.
 * Version: 1.0.0
 * Author: Bijit Deb
 * Author URI: https://profiles.wordpress.org/bijit027
 * License: GPL v2 or later
 * Text Domain: wp-pqa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPPQA_VERSION', '1.0.0' );
define( 'WPPQA_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPPQA_URL', plugin_dir_url( __FILE__ ) );
define( 'WPPQA_DB_VERSION', '1.0.0' );

// Autoload classes from includes/ folder
spl_autoload_register( function ( $class ) {
	if ( strpos( $class, 'WPPQA_' ) !== 0 ) {
		return;
	}

	$class_name = str_replace( 'WPPQA_', '', $class );
	$class_name = strtolower( str_replace( '_', '-', $class_name ) );
	$file       = WPPQA_PATH . 'includes/class-' . $class_name . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

// On plugin activation, call the database creation method
register_activation_hook( __FILE__, [ 'WPPQA_Database', 'create_tables' ] );

// Hook admin_menu to register admin pages
add_action( 'admin_menu', function() {
	if ( class_exists( 'WPPQA_Admin_Pages' ) ) {
		WPPQA_Admin_Pages::register_menu();
	}
} );

// Hook rest_api_init to register REST routes
add_action( 'rest_api_init', function() {
	if ( class_exists( 'WPPQA_REST_API' ) ) {
		$api = new WPPQA_REST_API();
		$api->register_routes();
	}
} );

// Hook admin_enqueue_scripts
add_action( 'admin_enqueue_scripts', function( $hook ) {
	if ( class_exists( 'WPPQA_Admin_Pages' ) ) {
		WPPQA_Admin_Pages::enqueue_scripts( $hook );
	}
} );

// Add type="module" to the Vite build script
add_filter( 'script_loader_tag', function( $tag, $handle ) {
	if ( 'wppqa-admin' === $handle ) {
		return str_replace( '<script ', '<script type="module" ', $tag );
	}
	return $tag;
}, 10, 2 );
