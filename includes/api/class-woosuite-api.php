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

        register_rest_route( $this->namespace, '/content', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_content_items' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/content/(?P<id>\d+)', array(
            'methods' => 'POST',
            'callback' => array( $this, 'update_content_item' ),
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

        register_rest_route( $this->namespace, '/security/deep-scan/start', array(
            'methods' => 'POST',
            'callback' => array( $this, 'start_deep_scan' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/security/deep-scan/status', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_deep_scan_status' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // SEO Batch Routes
        register_rest_route( $this->namespace, '/seo/batch', array(
            'methods' => 'POST',
            'callback' => array( $this, 'start_seo_batch' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/seo/batch-status', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_seo_batch_status' ),
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
        if ( isset( $params['loginMaxRetries'] ) ) {
            update_option( 'woosuite_login_max_retries', (int) $params['loginMaxRetries'] );
        }
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public function get_settings( $request ) {
        $api_key = get_option( 'woosuite_gemini_api_key', '' );
        return new WP_REST_Response( array( 'apiKey' => $api_key ), 200 );
    }

    public function get_content_items( $request ) {
        $type = $request->get_param('type') ?: 'product';
        $limit = $request->get_param('limit') ?: 50;
        $page = $request->get_param('page') ?: 1;
        $filter = $request->get_param('filter'); // 'unoptimized' or empty

        $data = array();
        $total = 0;
        $pages = 0;

        // Handle Products (WooCommerce)
        if ( $type === 'product' && class_exists( 'WooCommerce' ) ) {
            $args = array(
                'limit' => $limit,
                'page' => $page,
                'paginate' => true,
                'status' => 'publish',
            );

            if ( $filter === 'unoptimized' ) {
                 $args['meta_query'] = array(
                     'relation' => 'OR',
                     array(
                         'key'     => '_woosuite_meta_description',
                         'compare' => 'NOT EXISTS',
                     ),
                     array(
                         'key'     => '_woosuite_meta_description',
                         'value'   => '',
                         'compare' => '=',
                     )
                 );
            }

            $results = wc_get_products( $args );

            $products = $results->products;
            $total = $results->total;
            $pages = $results->max_num_pages;

            foreach ( $products as $product ) {
                $data[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'description' => strip_tags( $product->get_short_description() ?: $product->get_description() ),
                    'price' => $product->get_price(),
                    'metaTitle' => get_post_meta( $product->get_id(), '_woosuite_meta_title', true ),
                    'metaDescription' => get_post_meta( $product->get_id(), '_woosuite_meta_description', true ),
                    'llmSummary' => get_post_meta( $product->get_id(), '_woosuite_llm_summary', true ),
                    'type' => 'product',
                    'permalink' => get_permalink( $product->get_id() )
                );
            }
            return new WP_REST_Response( array( 'items' => $data, 'total' => $total, 'pages' => $pages ), 200 );
        }

        // Handle Posts, Pages, Images
        $args = array(
            'posts_per_page' => $limit,
            'paged' => $page,
            'post_status' => 'publish',
        );

        if ( $type === 'image' ) {
            $args['post_type'] = 'attachment';
            $args['post_status'] = 'inherit';
            $args['post_mime_type'] = 'image';
        } else {
            $args['post_type'] = $type; // post, page
        }

        if ( $filter === 'unoptimized' ) {
             $args['meta_query'] = array(
                 'relation' => 'OR',
                 array(
                     'key'     => '_woosuite_meta_description',
                     'compare' => 'NOT EXISTS',
                 ),
                 array(
                     'key'     => '_woosuite_meta_description',
                     'value'   => '',
                     'compare' => '=',
                 )
             );
        }

        $query = new WP_Query( $args );
        $posts = $query->posts;
        $total = $query->found_posts;
        $pages = $query->max_num_pages;

        foreach ( $posts as $post ) {
            $item = array(
                'id' => $post->ID,
                'name' => $post->post_title,
                'description' => strip_tags( $post->post_excerpt ?: $post->post_content ),
                'metaTitle' => get_post_meta( $post->ID, '_woosuite_meta_title', true ),
                'metaDescription' => get_post_meta( $post->ID, '_woosuite_meta_description', true ),
                'llmSummary' => get_post_meta( $post->ID, '_woosuite_llm_summary', true ),
                'type' => $type,
                'permalink' => get_permalink( $post->ID )
            );

            // Add Image specific data
            if ( $type === 'image' ) {
                $item['imageUrl'] = wp_get_attachment_url( $post->ID );
                $item['permalink'] = $item['imageUrl']; // Use direct link for images
                $item['altText'] = get_post_meta( $post->ID, '_wp_attachment_image_alt', true );
                // Use caption for description if excerpt is empty
                if ( empty( $item['description'] ) ) {
                    $item['description'] = $post->post_excerpt;
                }
            }

            $data[] = $item;
        }

        return new WP_REST_Response( array( 'items' => $data, 'total' => $total, 'pages' => $pages ), 200 );
    }

    public function update_content_item( $request ) {
        $id = $request->get_param( 'id' );
        $params = $request->get_json_params();

        // Debug Log
        error_log( "WooSuite AI: Update Content Item ID: $id" );
        error_log( "WooSuite AI: Params: " . print_r( $params, true ) );

        // Validate content exists
        $post = get_post( $id );
        if ( ! $post ) {
             return new WP_REST_Response( array( 'success' => false, 'message' => 'Content not found' ), 404 );
        }

        // Update Meta (Common)
        if ( isset( $params['metaTitle'] ) ) {
            update_post_meta( $id, '_woosuite_meta_title', sanitize_text_field( $params['metaTitle'] ) );
        }
        if ( isset( $params['metaDescription'] ) ) {
            update_post_meta( $id, '_woosuite_meta_description', sanitize_text_field( $params['metaDescription'] ) );

            // Sync with Yoast/RankMath
            update_post_meta( $id, '_yoast_wpseo_metadesc', sanitize_text_field( $params['metaDescription'] ) );
            update_post_meta( $id, 'rank_math_description', sanitize_text_field( $params['metaDescription'] ) );
        }
        if ( isset( $params['llmSummary'] ) ) {
            update_post_meta( $id, '_woosuite_llm_summary', sanitize_textarea_field( $params['llmSummary'] ) );
        }

        // Image Specific
        if ( isset( $params['altText'] ) ) {
            update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $params['altText'] ) );
        }

        // Update Title (Standard WP Post Title) - Useful for renaming images/posts
        if ( isset( $params['title'] ) ) {
             $post_update = array(
                'ID' => $id,
                'post_title' => sanitize_text_field( $params['title'] )
             );
             wp_update_post( $post_update );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
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
            'block_sqli' => get_option( 'woosuite_firewall_block_sqli', 'yes' ) === 'yes',
            'block_xss' => get_option( 'woosuite_firewall_block_xss', 'yes' ) === 'yes',
            'simulation_mode' => get_option( 'woosuite_firewall_simulation_mode', 'no' ) === 'yes',
            'login_enabled' => get_option( 'woosuite_login_protection_enabled', 'yes' ) === 'yes',
            'login_max_retries' => (int) get_option( 'woosuite_login_max_retries', 3 ),
            'last_scan' => get_option( 'woosuite_last_scan_time', 'Never' ),
            'last_scan_source' => get_option( 'woosuite_last_scan_source', 'auto' ),
            'threats_blocked' => (int) get_option( 'woosuite_threats_blocked_count', 0 ),
        );
        return new WP_REST_Response( $status, 200 );
    }

    public function toggle_security_option( $request ) {
        $params = $request->get_json_params();
        $option = isset( $params['option'] ) ? $params['option'] : '';
        $value = isset( $params['value'] ) ? $params['value'] : false;

        $value_str = $value ? 'yes' : 'no';

        if ( $option === 'firewall' ) {
            update_option( 'woosuite_firewall_enabled', $value_str );
        } elseif ( $option === 'spam' ) {
            update_option( 'woosuite_spam_protection_enabled', $value_str );
        } elseif ( $option === 'block_sqli' ) {
            update_option( 'woosuite_firewall_block_sqli', $value_str );
        } elseif ( $option === 'block_xss' ) {
            update_option( 'woosuite_firewall_block_xss', $value_str );
        } elseif ( $option === 'simulation_mode' ) {
            update_option( 'woosuite_firewall_simulation_mode', $value_str );
        } elseif ( $option === 'login' ) {
            update_option( 'woosuite_login_protection_enabled', $value_str );
        } else {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid option' ), 400 );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public function run_security_scan( $request ) {
        $security = new WooSuite_Security( $this->plugin_name, $this->version );
        $result = $security->perform_core_scan( 'manual' );
        return new WP_REST_Response( $result, 200 );
    }

    public function start_deep_scan( $request ) {
        if ( ! class_exists( 'WooSuite_Security_Scanner' ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Scanner class not found' ), 500 );
        }
        $scanner = new WooSuite_Security_Scanner();
        $count = $scanner->start_scan();
        return new WP_REST_Response( array( 'success' => true, 'count' => $count ), 200 );
    }

    public function get_deep_scan_status( $request ) {
        $status = get_option( 'woosuite_security_scan_status', array( 'status' => 'idle' ) );
        $results = get_option( 'woosuite_security_scan_results', array() );
        $status['results'] = $results;
        return new WP_REST_Response( $status, 200 );
    }

    // --- SEO Batch ---

    public function start_seo_batch( $request ) {
        if ( ! class_exists( 'WooSuite_Seo_Worker' ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Worker class not found' ), 500 );
        }
        $worker = new WooSuite_Seo_Worker();
        $worker->start_batch();
        return new WP_REST_Response( array( 'success' => true, 'message' => 'Batch started' ), 200 );
    }

    public function get_seo_batch_status( $request ) {
        $status = get_option( 'woosuite_seo_batch_status', array( 'status' => 'idle' ) );
        return new WP_REST_Response( $status, 200 );
    }

    public function check_permission() {
        return current_user_can( 'manage_options' );
    }
}
