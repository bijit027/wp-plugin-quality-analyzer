<?php
class WPPQA_Admin_Pages {

	public static function register_menu() {
		add_menu_page(
			'Plugin Analyzer',
			'Plugin Analyzer',
			'manage_options',
			'wp-plugin-quality-analyzer',
			[ __CLASS__, 'render_page' ],
			'dashicons-chart-bar'
		);
	}

	public static function enqueue_scripts( $hook ) {
		if ( $hook !== 'toplevel_page_wp-plugin-quality-analyzer' ) {
			return;
		}
		
		$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
		
		wp_enqueue_style( 'wppqa-admin', $plugin_url . 'assets/build/app.css' );
		wp_enqueue_script( 'wppqa-admin', $plugin_url . 'assets/build/app.js', [], WPPQA_VERSION, true );
		
		wp_localize_script( 'wppqa-admin', 'wppqaData', [
			'apiUrl'  => rest_url( 'wppqa/v1' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'siteUrl' => get_site_url(),
		] );
	}

	public static function render_page() {
		echo '<div id="wppqa-root"></div>';
	}
}
