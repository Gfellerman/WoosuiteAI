<?php

class WooSuite_Api {

    private $plugin_name;
    private $version;
    private $namespace;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->namespace = 'woosuite/v1';
    }

    public function register_routes() {
        register_rest_route( $this->namespace, '/status', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_status' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );
    }

    public function get_status( $request ) {
        return new WP_REST_Response( array(
            'status' => 'ok',
            'version' => $this->version,
            'message' => 'WooSuite AI is running'
        ), 200 );
    }

    public function check_permission() {
        return current_user_can( 'manage_options' );
    }
}
