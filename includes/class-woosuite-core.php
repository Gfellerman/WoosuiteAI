<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 */
class WooSuite_Core {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'woosuite-ai';
		$this->version = WOOSUITE_AI_VERSION;

		$this->load_dependencies();
		$this->define_admin_hooks();
        $this->define_api_hooks();
	}

	private function load_dependencies() {
        // Load the Admin handling class
		require_once WOOSUITE_AI_PATH . 'includes/class-woosuite-admin.php';

        // Load the API handling class
        require_once WOOSUITE_AI_PATH . 'includes/api/class-woosuite-api.php';

        // Load the Security class
        require_once WOOSUITE_AI_PATH . 'includes/class-woosuite-security.php';

        // Load the Sitemap class
        require_once WOOSUITE_AI_PATH . 'includes/class-woosuite-sitemap.php';
	}

    private function define_sitemap_hooks() {
        $plugin_sitemap = new WooSuite_Sitemap( $this->plugin_name, $this->version );
        $plugin_sitemap->init();
    }

    private function define_security_hooks() {
        $plugin_security = new WooSuite_Security( $this->plugin_name, $this->version );
        $plugin_security->init();
    }

	private function define_admin_hooks() {
		$plugin_admin = new WooSuite_Admin( $this->plugin_name, $this->version );
		add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
        add_filter( 'script_loader_tag', array( $plugin_admin, 'add_type_attribute' ), 10, 3 );
	}

    private function define_api_hooks() {
        $plugin_api = new WooSuite_Api( $this->plugin_name, $this->version );
        add_action( 'rest_api_init', array( $plugin_api, 'register_routes' ) );
    }

	public function run() {
        $this->define_security_hooks();
        $this->define_sitemap_hooks();
	}
}
