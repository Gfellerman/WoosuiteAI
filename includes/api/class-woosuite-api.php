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

        register_rest_route( $this->namespace, '/settings', array(
            'methods' => 'POST',
            'callback' => array( $this, 'save_settings' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/settings', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_settings' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/products', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_products' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/stats', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_stats' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Security Routes
        register_rest_route( $this->namespace, '/security/logs', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_security_logs' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/security/status', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_security_status' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/security/toggle', array(
            'methods' => 'POST',
            'callback' => array( $this, 'toggle_security_option' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/security/scan', array(
            'methods' => 'POST',
            'callback' => array( $this, 'run_security_scan' ),
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

    public function save_settings( $request ) {
        $params = $request->get_json_params();
        if ( isset( $params['apiKey'] ) ) {
            update_option( 'woosuite_gemini_api_key', sanitize_text_field( $params['apiKey'] ) );
        }
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public function get_settings( $request ) {
        $api_key = get_option( 'woosuite_gemini_api_key', '' );
        return new WP_REST_Response( array( 'apiKey' => $api_key ), 200 );
    }

    public function get_products( $request ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return new WP_REST_Response( array(), 200 );
        }

        $args = array(
            'limit' => 20,
            'status' => 'publish',
        );
        $products = wc_get_products( $args );
        $data = array();

        foreach ( $products as $product ) {
            $data[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'description' => strip_tags( $product->get_short_description() ?: $product->get_description() ),
                'price' => $product->get_price(),
                'metaTitle' => get_post_meta( $product->get_id(), '_woosuite_meta_title', true ),
                'metaDescription' => get_post_meta( $product->get_id(), '_woosuite_meta_description', true ),
                'llmSummary' => get_post_meta( $product->get_id(), '_woosuite_llm_summary', true ),
            );
        }

        return new WP_REST_Response( $data, 200 );
    }

    public function get_stats( $request ) {
        $stats = array(
            'orders' => 0,
            'seo_score' => 0,
            'threats_blocked' => (int) get_option( 'woosuite_threats_blocked_count', 0 ),
            'ai_searches' => (int) get_option( 'woosuite_ai_searches_count', 0 ),
            'last_backup' => get_option( 'woosuite_last_backup_time', 'Never' ),
        );

        if ( class_exists( 'WooCommerce' ) ) {
             // Get order counts
             $order_counts = wc_get_order_status_counts();
             $stats['orders'] = array_sum($order_counts);
        }

        // SEO Score (Simple logic: % of posts with meta desc)
        $posts = get_posts(array('numberposts' => -1, 'post_type' => array('post', 'page', 'product')));
        $total = count($posts);
        $optimized = 0;
        if ($total > 0) {
            foreach ($posts as $p) {
                // Check if our meta or Yoast/RankMath meta exists
                if (get_post_meta($p->ID, '_woosuite_meta_description', true) || get_post_meta($p->ID, '_yoast_wpseo_metadesc', true)) {
                    $optimized++;
                }
            }
            $stats['seo_score'] = round(($optimized / $total) * 100);
        } else {
             $stats['seo_score'] = 0;
        }

        return new WP_REST_Response( $stats, 200 );
    }

    // --- Security Endpoints ---

    public function get_security_logs( $request ) {
        $security = new WooSuite_Security( $this->plugin_name, $this->version );
        $logs = $security->get_logs( 20 );
        return new WP_REST_Response( $logs, 200 );
    }

    public function get_security_status( $request ) {
        $status = array(
            'firewall_enabled' => get_option( 'woosuite_firewall_enabled', 'yes' ) === 'yes',
            'spam_enabled' => get_option( 'woosuite_spam_protection_enabled', 'yes' ) === 'yes',
            'last_scan' => get_option( 'woosuite_last_scan_time', 'Never' ),
            'threats_blocked' => (int) get_option( 'woosuite_threats_blocked_count', 0 ),
        );
        return new WP_REST_Response( $status, 200 );
    }

    public function toggle_security_option( $request ) {
        $params = $request->get_json_params();
        $option = isset( $params['option'] ) ? $params['option'] : '';
        $value = isset( $params['value'] ) ? $params['value'] : false;

        if ( $option === 'firewall' ) {
            update_option( 'woosuite_firewall_enabled', $value ? 'yes' : 'no' );
        } elseif ( $option === 'spam' ) {
            update_option( 'woosuite_spam_protection_enabled', $value ? 'yes' : 'no' );
        } else {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid option' ), 400 );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public function run_security_scan( $request ) {
        $security = new WooSuite_Security( $this->plugin_name, $this->version );
        $result = $security->perform_core_scan();
        return new WP_REST_Response( $result, 200 );
    }

    public function check_permission() {
        return current_user_can( 'manage_options' );
    }
}
