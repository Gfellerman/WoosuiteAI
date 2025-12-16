<?php

class WooSuite_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function add_plugin_admin_menu() {
		add_menu_page(
			'WooSuite AI',
			'WooSuite AI',
			'manage_options',
			'woosuite-ai',
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-superhero',
			2
		);
	}

	public function display_plugin_admin_page() {
		// This div is where the React App will mount
		echo '<div id="root"></div>';
	}

	public function enqueue_styles( $hook ) {
        if ( 'toplevel_page_woosuite-ai' !== $hook ) {
            return;
        }

        $css_file = WOOSUITE_AI_PATH . 'assets/woosuite-app.css';
        $version = file_exists( $css_file ) ? filemtime( $css_file ) : $this->version;

		wp_enqueue_style( $this->plugin_name, WOOSUITE_AI_URL . 'assets/woosuite-app.css', array(), $version, 'all' );
	}

	public function enqueue_scripts( $hook ) {
        if ( 'toplevel_page_woosuite-ai' !== $hook ) {
            return;
        }

        $js_file = WOOSUITE_AI_PATH . 'assets/woosuite-app.js';
        $version = file_exists( $js_file ) ? filemtime( $js_file ) : $this->version;

		wp_enqueue_script( $this->plugin_name, WOOSUITE_AI_URL . 'assets/woosuite-app.js', array( 'jquery' ), $version, true );

        // Pass nonce and API url to React
        wp_localize_script( $this->plugin_name, 'woosuiteData', array(
            'root' => esc_url_raw( rest_url() ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'apiUrl' => rest_url( 'woosuite/v1' )
        ));
	}
}
