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

        register_rest_route( $this->namespace, '/settings/test-connection', array(
            'methods' => 'POST',
            'callback' => array( $this, 'test_api_connection' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/system-logs', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_system_logs' ),
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

        // Quarantine Routes
        register_rest_route( $this->namespace, '/security/quarantine', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_quarantined_files' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/security/quarantine/move', array(
            'methods' => 'POST',
            'callback' => array( $this, 'move_to_quarantine' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/security/quarantine/restore', array(
            'methods' => 'POST',
            'callback' => array( $this, 'restore_from_quarantine' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/security/quarantine/delete', array(
            'methods' => 'POST',
            'callback' => array( $this, 'delete_from_quarantine' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Ignore Routes
        register_rest_route( $this->namespace, '/security/ignore', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_ignored_paths' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/security/ignore', array(
            'methods' => 'POST',
            'callback' => array( $this, 'add_ignored_path' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/security/ignore/remove', array(
            'methods' => 'POST',
            'callback' => array( $this, 'remove_ignored_path' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // AI Security Analysis
        register_rest_route( $this->namespace, '/security/analyze-file', array(
            'methods' => 'POST',
            'callback' => array( $this, 'analyze_security_file' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/security/analyze-logs', array(
            'methods' => 'POST',
            'callback' => array( $this, 'analyze_security_logs' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/security/analyze-firewall', array(
            'methods' => 'POST',
            'callback' => array( $this, 'analyze_firewall_logs' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/security/bulk', array(
            'methods' => 'POST',
            'callback' => array( $this, 'bulk_security_action' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/maintenance', array(
            'methods' => 'POST',
            'callback' => array( $this, 'perform_maintenance' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // SEO Batch Routes
        register_rest_route( $this->namespace, '/seo/batch', array(
            'methods' => 'POST',
            'callback' => array( $this, 'start_seo_batch' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/seo/batch/resume', array(
            'methods' => 'POST',
            'callback' => array( $this, 'resume_seo_batch' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/seo/batch/stop', array(
            'methods' => 'POST',
            'callback' => array( $this, 'stop_seo_batch' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/seo/batch/reset', array(
            'methods' => 'POST',
            'callback' => array( $this, 'reset_seo_batch' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/seo/batch-status', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_seo_batch_status' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/seo/scan', array(
            'methods' => 'GET',
            'callback' => array( $this, 'run_seo_scan' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // SEO Generate (Single Item) - Server Side
        register_rest_route( $this->namespace, '/seo/generate/(?P<id>\d+)', array(
            'methods' => 'POST',
            'callback' => array( $this, 'generate_content_item' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Content Rewrite Routes
        register_rest_route( $this->namespace, '/content/rewrite', array(
            'methods' => 'POST',
            'callback' => array( $this, 'rewrite_content_item' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/content/apply', array(
            'methods' => 'POST',
            'callback' => array( $this, 'apply_content_rewrite' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/content/restore', array(
            'methods' => 'POST',
            'callback' => array( $this, 'restore_content_item' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/content/bulk-apply', array(
            'methods' => 'POST',
            'callback' => array( $this, 'bulk_apply_content_rewrite' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        register_rest_route( $this->namespace, '/content/categories', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_categories' ),
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

        // Custom API / BYO-LLM Settings
        if ( isset( $params['useCustomApi'] ) ) {
            update_option( 'woosuite_use_custom_api', $params['useCustomApi'] ? 'yes' : 'no' );
        }
        if ( isset( $params['customApiUrl'] ) ) {
            update_option( 'woosuite_api_url_custom', esc_url_raw( $params['customApiUrl'] ) );
        }
        if ( isset( $params['customModelId'] ) ) {
            update_option( 'woosuite_api_model_custom', sanitize_text_field( $params['customModelId'] ) );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public function get_system_logs( $request ) {
        $logs = get_option( 'woosuite_debug_log', array() );
        if ( is_array( $logs ) && count( $logs ) > 100 ) {
            $logs = array_slice( $logs, 0, 100 );
        }
        return new WP_REST_Response( array( 'logs' => $logs ), 200 );
    }

    public function get_settings( $request ) {
        $api_key = get_option( 'woosuite_gemini_api_key', '' );
        return new WP_REST_Response( array(
            'apiKey' => $api_key,
            'useCustomApi' => get_option( 'woosuite_use_custom_api', 'no' ) === 'yes',
            'customApiUrl' => get_option( 'woosuite_api_url_custom', '' ),
            'customModelId' => get_option( 'woosuite_api_model_custom', '' )
        ), 200 );
    }

    public function test_api_connection( $request ) {
        $params = $request->get_json_params();
        $provided_key = isset( $params['apiKey'] ) ? sanitize_text_field( $params['apiKey'] ) : null;

        // DEBUG: Log the key check to system logs to diagnose issues
        $debug_logs = get_option('woosuite_debug_log', array());
        $timestamp = date('Y-m-d H:i:s');
        $key_status = empty($provided_key) ? 'Missing/Empty' : 'Present (Length: ' . strlen($provided_key) . ')';
        $debug_entry = "[$timestamp] [DEBUG] Test Connection. Key provided in request: $key_status";
        array_unshift($debug_logs, $debug_entry);
        update_option('woosuite_debug_log', array_slice($debug_logs, 0, 50));

        $groq = new WooSuite_Groq( $provided_key );
        $result = $groq->test_connection();

        if ( is_wp_error( $result ) ) {
            // Log Failure
            $debug_logs = get_option('woosuite_debug_log', array());
            $fail_entry = "[$timestamp] [ERROR] Test Connection Failed: " . $result->get_error_message();
            array_unshift($debug_logs, $fail_entry);
            update_option('woosuite_debug_log', array_slice($debug_logs, 0, 50));

            return new WP_REST_Response( array(
                'success' => false,
                'message' => $result->get_error_message(),
                'code' => $result->get_error_code(),
                'data' => $result->get_error_data()
            ), 500 );
        }

        // Log Success
        $debug_logs = get_option('woosuite_debug_log', array());
        $success_entry = "[$timestamp] [INFO] Test Connection Successful.";
        array_unshift($debug_logs, $success_entry);
        update_option('woosuite_debug_log', array_slice($debug_logs, 0, 50));

        // Handle warning from fallback model usage
        $warning = isset($result['warning']) ? $result['warning'] : null;
        $message = 'Connection Successful!';
        if ($warning) {
            $message .= ' ' . $warning;
        }

        return new WP_REST_Response( array(
            'success' => true,
            'message' => $message,
            'data' => $result
        ), 200 );
    }

    public function get_content_items( $request ) {
        $type = $request->get_param('type') ?: 'product';
        $limit = $request->get_param('limit') ?: 20;
        $page = $request->get_param('page') ?: 1;
        $filter = $request->get_param('filter'); // 'unoptimized' or empty
        $category = $request->get_param('category');
        $status = $request->get_param('status'); // 'enhanced', 'not_enhanced'
        $search = $request->get_param('search'); // NEW: Search support
        $return_ids_only = $request->get_param('fields') === 'ids';

        $args = array(
            'posts_per_page' => $limit,
            'paged' => $page,
            'post_status' => 'publish',
        );

        if ( $return_ids_only ) {
            $args['fields'] = 'ids';
        }

        if ( $type === 'image' ) {
            $args['post_type'] = 'attachment';
            $args['post_status'] = 'inherit';
            $args['post_mime_type'] = 'image';
        } else {
            $args['post_type'] = $type; // post, page, product
        }

        // Search Filter
        if ( ! empty( $search ) ) {
            $args['s'] = sanitize_text_field( $search );
        }

        // Category Filter
        if ( ! empty( $category ) ) {
            $taxonomy = ($type === 'product') ? 'product_cat' : 'category';
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => intval($category), // Strict casting
                    'include_children' => true
                )
            );
        }

        // --- DEBUG LOGGING START ---
        // Log the constructed query to help debug filter issues (e.g., recursive categories)
        $debug_logs = get_option('woosuite_debug_log', array());
        $debug_msg = 'API Query Args: ' . json_encode(array(
            'tax_query' => isset($args['tax_query']) ? $args['tax_query'] : 'none',
            'category_param' => $category,
            'post_type' => $type,
            'search' => $search
        ));
        $timestamp = date('Y-m-d H:i:s');
        $debug_entry = "[$timestamp] [DEBUG] $debug_msg";

        array_unshift($debug_logs, $debug_entry);
        if (count($debug_logs) > 50) array_pop($debug_logs);
        update_option('woosuite_debug_log', $debug_logs);
        // --- DEBUG LOGGING END ---

        $meta_query = array();

        if ( $filter === 'unoptimized' ) {
            if ( $type === 'image' ) {
                $meta_query[] = array(
                    'relation' => 'OR',
                    array( 'key' => '_wp_attachment_image_alt', 'compare' => 'NOT EXISTS' ),
                    array( 'key' => '_wp_attachment_image_alt', 'value' => '', 'compare' => '=' )
                );
            } else {
                $meta_query[] = array(
                    'relation' => 'OR',
                    array( 'key' => '_woosuite_meta_description', 'compare' => 'NOT EXISTS' ),
                    array( 'key' => '_woosuite_meta_description', 'value' => '', 'compare' => '=' )
                );
            }
        }

        // Status Filter (Enhanced vs Not Enhanced)
        if ( $status === 'enhanced' ) {
            // "Enhanced" means the item has been modified by AI (has history) OR has a pending proposal?
            // User requirement: "As there is a track of what has been modified in order to undo the changes..."
            // So we check for _woosuite_history_... keys.
            // Since there are multiple history keys (title, content, excerpt, meta...), we use OR relation.
            $meta_query[] = array(
                'relation' => 'OR',
                array( 'key' => '_woosuite_history_post_title', 'compare' => 'EXISTS' ),
                array( 'key' => '_woosuite_history_post_content', 'compare' => 'EXISTS' ),
                array( 'key' => '_woosuite_history_post_excerpt', 'compare' => 'EXISTS' ),
                // Also check if there is a pending proposal? User might want to see items with proposals ready to review.
                array( 'key' => '_woosuite_proposed_title', 'compare' => 'EXISTS' ),
                array( 'key' => '_woosuite_proposed_description', 'compare' => 'EXISTS' ),
            );
        } elseif ( $status === 'not_enhanced' ) {
            // "Not Enhanced" means NO history AND NO proposals.
            $meta_query[] = array(
                'relation' => 'AND',
                array( 'key' => '_woosuite_history_post_title', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_history_post_content', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_history_post_excerpt', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_proposed_title', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_proposed_description', 'compare' => 'NOT EXISTS' ),
            );
        }

        if ( ! empty( $meta_query ) ) {
            $args['meta_query'] = $meta_query;
        }

        $query = new WP_Query( $args );
        $posts = $query->posts;
        $total = $query->found_posts;
        $pages = $query->max_num_pages;

        if ( $return_ids_only ) {
            return new WP_REST_Response( array( 'ids' => $posts, 'total' => $total, 'pages' => $pages ), 200 );
        }

        $data = array();
        foreach ( $posts as $post ) {
            $item = array(
                'id' => $post->ID,
                'name' => $post->post_title,
                // Separate description fields explicitly
                'description' => strip_tags( $post->post_content ),
                'shortDescription' => $post->post_excerpt,
                // Legacy support (fallback)
                'fallbackDescription' => strip_tags( $post->post_excerpt ?: $post->post_content ),

                'metaTitle' => get_post_meta( $post->ID, '_woosuite_meta_title', true ),
                'metaDescription' => get_post_meta( $post->ID, '_woosuite_meta_description', true ),
                'llmSummary' => get_post_meta( $post->ID, '_woosuite_llm_summary', true ),
                'lastError' => get_post_meta( $post->ID, '_woosuite_seo_last_error', true ),
                'type' => $type,
                'permalink' => get_permalink( $post->ID ),
                'proposedTitle' => get_post_meta( $post->ID, '_woosuite_proposed_title', true ),
                'proposedDescription' => get_post_meta( $post->ID, '_woosuite_proposed_description', true ),
                'proposedShortDescription' => get_post_meta( $post->ID, '_woosuite_proposed_short_description', true ),
                'hasHistory' => ! empty( get_post_meta( $post->ID, '_woosuite_history_post_content', true ) ) ||
                                ! empty( get_post_meta( $post->ID, '_woosuite_history__woosuite_meta_description', true ) ) ||
                                ! empty( get_post_meta( $post->ID, '_woosuite_history__wp_attachment_image_alt', true ) ) ||
                                ! empty( get_post_meta( $post->ID, '_woosuite_history_post_title', true ) ) ||
                                ! empty( get_post_meta( $post->ID, '_woosuite_history_post_excerpt', true ) ),
                'tags' => array()
            );

            $taxonomy = ($type === 'product') ? 'product_tag' : 'post_tag';
            $terms = get_the_terms( $post->ID, $taxonomy );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                $item['tags'] = wp_list_pluck( $terms, 'name' );
            }

            // Add Image specific data
            if ( $type === 'image' ) {
                $item['imageUrl'] = wp_get_attachment_url( $post->ID );
                $item['permalink'] = $item['imageUrl'];
                $item['altText'] = get_post_meta( $post->ID, '_wp_attachment_image_alt', true );
                if ( empty( $item['description'] ) ) {
                    $item['description'] = $post->post_excerpt; // Caption
                }
            } elseif ( $type === 'product' && function_exists( 'wc_get_product' ) ) {
                 $product = wc_get_product( $post->ID );
                 if ( $product ) {
                     $item['price'] = $product->get_price();
                 }
            }

            $data[] = $item;
        }

        return new WP_REST_Response( array( 'items' => $data, 'total' => $total, 'pages' => $pages ), 200 );
    }

    public function update_content_item( $request ) {
        $id = $request->get_param( 'id' );
        $params = $request->get_json_params();

        // Validate content exists
        $post = get_post( $id );
        if ( ! $post ) {
             return new WP_REST_Response( array( 'success' => false, 'message' => 'Content not found' ), 404 );
        }

        // Update Meta (Common)
        if ( isset( $params['metaTitle'] ) ) {
            $this->save_meta_history( $id, '_woosuite_meta_title' );
            update_post_meta( $id, '_woosuite_meta_title', sanitize_text_field( $params['metaTitle'] ) );
        }
        if ( isset( $params['metaDescription'] ) ) {
            $this->save_meta_history( $id, '_woosuite_meta_description' );
            update_post_meta( $id, '_woosuite_meta_description', sanitize_text_field( $params['metaDescription'] ) );

            // Sync with Yoast/RankMath
            update_post_meta( $id, '_yoast_wpseo_metadesc', sanitize_text_field( $params['metaDescription'] ) );
            update_post_meta( $id, 'rank_math_description', sanitize_text_field( $params['metaDescription'] ) );
        }
        if ( isset( $params['llmSummary'] ) ) {
            $this->save_meta_history( $id, '_woosuite_llm_summary' );
            update_post_meta( $id, '_woosuite_llm_summary', sanitize_textarea_field( $params['llmSummary'] ) );
        }

        // Image Specific
        if ( isset( $params['altText'] ) ) {
            $this->save_meta_history( $id, '_wp_attachment_image_alt' );
            update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $params['altText'] ) );
        }

        // Update Title (Standard WP Post Title)
        if ( isset( $params['title'] ) ) {
             // Save history for native fields logic is custom
             update_post_meta( $id, '_woosuite_history_post_title', $post->post_title );

             $post_update = array(
                'ID' => $id,
                'post_title' => sanitize_text_field( $params['title'] )
             );
             wp_update_post( $post_update );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public function generate_content_item( $request ) {
        $id = $request->get_param( 'id' );
        $params = $request->get_json_params();
        $rewrite_title = isset( $params['rewriteTitle'] ) ? $params['rewriteTitle'] : false;

        $post = get_post( $id );
        if ( ! $post ) return new WP_REST_Response( array( 'success' => false, 'message' => 'Not found' ), 404 );

        $groq = new WooSuite_Groq();

        if ( $post->post_type === 'attachment' ) {
            $url = wp_get_attachment_url( $id );
            $result = $groq->generate_image_seo( $url, basename( $url ) );
        } else {
             $item = array(
                'type' => $post->post_type,
                'name' => $post->post_title,
                'description' => strip_tags( $post->post_excerpt ?: $post->post_content ),
                'rewrite_title' => $rewrite_title
            );
             if ( $post->post_type === 'product' && function_exists( 'wc_get_product' ) ) {
                $product = wc_get_product( $post->ID );
                if ( $product ) $item['price'] = $product->get_price();
            }
            $result = $groq->generate_seo_meta( $item );
        }

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => $result->get_error_message() ), 500 );
        }

        // AUTO-SAVE: Immediately save the generated results to the database
        // This ensures that "Generate" actions in the UI are persistent.

        if ( ! empty( $result['title'] ) ) {
            $this->save_meta_history( $id, '_woosuite_meta_title' );
            update_post_meta( $id, '_woosuite_meta_title', sanitize_text_field( $result['title'] ) );
        }

        if ( ! empty( $result['description'] ) ) {
            $this->save_meta_history( $id, '_woosuite_meta_description' );
            $desc = sanitize_text_field( $result['description'] );
            update_post_meta( $id, '_woosuite_meta_description', $desc );
            update_post_meta( $id, '_yoast_wpseo_metadesc', $desc );
            update_post_meta( $id, 'rank_math_description', $desc );
        }

        if ( ! empty( $result['llmSummary'] ) ) {
            $this->save_meta_history( $id, '_woosuite_llm_summary' );
            update_post_meta( $id, '_woosuite_llm_summary', sanitize_textarea_field( $result['llmSummary'] ) );
        }

        if ( ! empty( $result['tags'] ) ) {
            $tags = explode( ',', $result['tags'] );
            $tags = array_map( 'trim', $tags );
            $tags = array_filter( $tags ); // Remove empty

            $taxonomy = 'post_tag';
            if ( $post->post_type === 'product' ) {
                $taxonomy = 'product_tag';
            }

            if ( taxonomy_exists( $taxonomy ) && ! empty( $tags ) ) {
                // User requested to remove old tags and keep only enhanced ones.
                wp_set_object_terms( $id, $tags, $taxonomy, false ); // false = Replace
            }
        }

        return new WP_REST_Response( array( 'success' => true, 'data' => $result ), 200 );
    }

    public function rewrite_content_item( $request ) {
        $params = $request->get_json_params();
        $id = isset( $params['id'] ) ? intval( $params['id'] ) : 0;
        $field = isset( $params['field'] ) ? sanitize_text_field( $params['field'] ) : 'description';
        $tone = isset( $params['tone'] ) ? sanitize_text_field( $params['tone'] ) : 'Professional';
        $instructions = isset( $params['instructions'] ) ? sanitize_text_field( $params['instructions'] ) : '';

        $post = get_post( $id );
        if ( ! $post ) return new WP_REST_Response( array( 'success' => false, 'message' => 'Not found' ), 404 );

        // Strict Product Only for Content Enhancer
        if ( $post->post_type !== 'product' ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Only products are supported for rewriting.' ), 400 );
        }

        $text = '';
        $context = '';
        $internal_instructions = '';

        if ( $field === 'title' ) {
            $text = $post->post_title;
            // Use description as context so AI knows what the product is (Avoids Real Estate hallucination)
            $context = strip_tags( $post->post_content );
            if ( empty( $context ) ) $context = "Product: " . $post->post_title;
            $internal_instructions = "Minimize the name to max 5 words.";
        } elseif ( $field === 'short_description' ) {
            $text = $post->post_excerpt;
            $context = $post->post_title; // Name is the source
            $internal_instructions = "Give exactly ONE word based on the name.";
            if ( empty( $text ) ) $text = "Generate";
        } elseif ( $field === 'description' ) {
            $text = strip_tags( $post->post_content );
            $internal_instructions = "Write a plain English description. Fix bad translation.";
            // If desc is empty, use title as context to generate it
            if ( empty( $text ) ) {
                $text = "Generate description for: " . $post->post_title;
                $context = $post->post_title;
            }
        }

        // Combine instructions
        $final_instructions = $internal_instructions;
        if ( ! empty( $instructions ) ) {
            $final_instructions .= " User Note: " . $instructions;
        }

        $groq = new WooSuite_Groq();
        // Pass context to prevent hallucinations
        $result = $groq->rewrite_content( $text, $field, $tone, $final_instructions, $context );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => $result->get_error_message() ), 500 );
        }

        if ( ! empty( $result['rewritten'] ) ) {
            update_post_meta( $id, '_woosuite_proposed_' . $field, wp_kses_post( $result['rewritten'] ) );
        }

        return new WP_REST_Response( array( 'success' => true, 'rewritten' => $result['rewritten'] ), 200 );
    }

    public function apply_content_rewrite( $request ) {
        $params = $request->get_json_params();
        $id = isset( $params['id'] ) ? intval( $params['id'] ) : 0;
        $field = isset( $params['field'] ) ? sanitize_text_field( $params['field'] ) : 'description';
        // Allow manual override of the value (User Edited Proposal)
        // If 'value' param is present, use it. Otherwise, fetch from meta.
        $value_override = isset( $params['value'] ) ? $params['value'] : null;

        $post = get_post($id);
        if (!$post) return new WP_REST_Response(array('success' => false, 'message' => 'Not found'), 404);

        $proposed = $value_override;
        if ( $proposed === null ) {
            $proposed = get_post_meta( $id, '_woosuite_proposed_' . $field, true );
        }

        if ( empty( $proposed ) ) {
             return new WP_REST_Response( array( 'success' => false, 'message' => 'No content provided to apply.' ), 400 );
        }

        // Sanitize before saving
        $proposed = wp_kses_post( $proposed );

        $args = array( 'ID' => $id );
        if ( $field === 'title' ) {
            update_post_meta( $id, '_woosuite_history_post_title', $post->post_title );
            $args['post_title'] = $proposed;
        } elseif ( $field === 'short_description' ) {
            update_post_meta( $id, '_woosuite_history_post_excerpt', $post->post_excerpt );
            $args['post_excerpt'] = $proposed;
        } else {
            update_post_meta( $id, '_woosuite_history_post_content', $post->post_content );
            $args['post_content'] = $proposed;
        }

        wp_update_post( $args );
        delete_post_meta( $id, '_woosuite_proposed_' . $field );

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public function restore_content_item( $request ) {
        $params = $request->get_json_params();
        $id = isset( $params['id'] ) ? intval( $params['id'] ) : 0;
        $field = isset( $params['field'] ) ? sanitize_text_field( $params['field'] ) : ''; // e.g., 'description', 'title', 'metaDescription'

        $post = get_post( $id );
        if ( ! $post ) return new WP_REST_Response( array( 'success' => false, 'message' => 'Not found' ), 404 );

        $fields_process = array();
        if ( $field === 'all' ) {
            $fields_process = array( 'title', 'short_description', 'description', 'metaTitle', 'metaDescription', 'llmSummary', 'altText' );
        } else {
            $fields_process[] = $field;
        }

        foreach ( $fields_process as $f ) {
            $history_key = '';
            $is_native = false;
            $native_field = '';

            if ( $f === 'title' ) {
                $history_key = '_woosuite_history_post_title';
                $is_native = true;
                $native_field = 'post_title';
            } elseif ( $f === 'short_description' ) {
                $history_key = '_woosuite_history_post_excerpt';
                $is_native = true;
                $native_field = 'post_excerpt';
            } elseif ( $f === 'description' ) {
                $history_key = '_woosuite_history_post_content';
                $is_native = true;
                $native_field = 'post_content';
            } elseif ( $f === 'metaTitle' ) {
                $history_key = '_woosuite_history__woosuite_meta_title';
            } elseif ( $f === 'metaDescription' ) {
                $history_key = '_woosuite_history__woosuite_meta_description';
            } elseif ( $f === 'llmSummary' ) {
                $history_key = '_woosuite_history__woosuite_llm_summary';
            } elseif ( $f === 'altText' ) {
                $history_key = '_woosuite_history__wp_attachment_image_alt';
            }

            if ( empty( $history_key ) ) continue;

            $prev_value = get_post_meta( $id, $history_key, true );

            // If empty, it might mean no history, OR history was empty string.
            // We check if key exists to be sure?
            // For now, if it returns something distinct or we trust it.
            // get_post_meta returns '' if not found.
            // We can't distinguish "not found" vs "empty string".
            // But if we save history, we save whatever it was.
            // If history doesn't exist, we probably shouldn't overwrite current with empty?
            // Actually, best to check if metadata exists using metadata_exists() (WP function).
            // But we can't easily do that here without global $wpdb or metadata_exists check.

            if ( metadata_exists( 'post', $id, $history_key ) ) {
                if ( $is_native ) {
                    wp_update_post( array( 'ID' => $id, $native_field => $prev_value ) );
                } else {
                    $meta_key = str_replace( '_woosuite_history_', '', $history_key );
                    update_post_meta( $id, $meta_key, $prev_value );
                }
            }
        }

        // We don't delete history so user can undo again (toggle) or we keep last state.

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    private function save_meta_history( $id, $key ) {
        $val = get_post_meta( $id, $key, true );
        update_post_meta( $id, '_woosuite_history_' . $key, $val );
    }

    public function bulk_apply_content_rewrite( $request ) {
        $params = $request->get_json_params();
        $ids = isset( $params['ids'] ) ? $params['ids'] : array();
        $field = isset( $params['field'] ) ? sanitize_text_field( $params['field'] ) : 'description';

        if ( ! is_array( $ids ) || empty( $ids ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'No IDs provided' ), 400 );
        }

        $applied_count = 0;
        foreach ( $ids as $id ) {
            $id = intval( $id );
            $proposed = get_post_meta( $id, '_woosuite_proposed_' . $field, true );

            if ( ! empty( $proposed ) ) {
                $args = array( 'ID' => $id );
                if ( $field === 'title' ) {
                    $args['post_title'] = $proposed;
                } elseif ( $field === 'short_description' ) {
                    $args['post_excerpt'] = $proposed;
                } else {
                    $args['post_content'] = $proposed;
                }

                wp_update_post( $args );
                delete_post_meta( $id, '_woosuite_proposed_' . $field );
                $applied_count++;
            }
        }

        return new WP_REST_Response( array( 'success' => true, 'applied' => $applied_count ), 200 );
    }

    public function get_categories( $request ) {
        $type = $request->get_param('type') ?: 'product';
        $taxonomy = ($type === 'product') ? 'product_cat' : 'category';

        // For image type, we technically don't have categories,
        // but maybe the user wants to filter by attached post's category?
        // For now, let's keep it simple: products -> product_cat, others -> category

        $terms = get_terms( array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'parent' => 0 // Start with main categories, user can't drill down deeper yet in UI
        ) );

        if ( is_wp_error( $terms ) ) {
            return new WP_REST_Response( array(), 200 );
        }

        $data = array();
        foreach ( $terms as $term ) {
            $data[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'count' => $term->count
            );
        }

        return new WP_REST_Response( $data, 200 );
    }

    public function get_stats( $request ) {
        global $wpdb;

        // Threats Blocked: Count rows in security logs where blocked = 1
        $table_logs = $wpdb->prefix . 'woosuite_security_logs';
        $threats_blocked = 0;
        // Check if table exists first
        if ( $wpdb->get_var("SHOW TABLES LIKE '$table_logs'") === $table_logs ) {
             $threats_blocked = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_logs WHERE blocked = 1" );
        }

        // Last Backup
        $last_backup = get_option( 'woosuite_last_backup_time', 'Never' );
        // Format relative time if not "Never"
        if ( $last_backup !== 'Never' ) {
            $timestamp = strtotime( $last_backup );
            if ( $timestamp ) {
                $last_backup = human_time_diff( $timestamp, current_time( 'timestamp' ) ) . ' ago';
            }
        }

        $stats = array(
            'orders' => 0,
            'seo_score' => 0,
            'threats_blocked' => $threats_blocked,
            'ai_searches' => 0, // Feature deprecated
            'last_backup' => $last_backup,
        );

        if ( class_exists( 'WooCommerce' ) ) {
             // Get order counts
             $order_counts = wc_get_order_status_counts();
             $stats['orders'] = array_sum($order_counts);
        }

        // SEO Score: Accurate count including Images, Posts, Pages, Products
        // We use direct SQL for performance
        $post_types = "'post', 'page', 'product'";
        $total_content = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type IN ($post_types) AND post_status = 'publish'" );
        $optimized_content = (int) $wpdb->get_var( "
            SELECT COUNT(DISTINCT post_id) FROM $wpdb->postmeta
            WHERE meta_key IN ('_woosuite_meta_description', '_yoast_wpseo_metadesc', 'rank_math_description')
            AND meta_value != ''
        " );

        // Images
        $total_images = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' AND post_status = 'inherit'" );
        $optimized_images = (int) $wpdb->get_var( "
            SELECT COUNT(DISTINCT post_id) FROM $wpdb->postmeta
            WHERE meta_key = '_wp_attachment_image_alt'
            AND meta_value != ''
        " );

        $total_items = $total_content + $total_images;
        $total_optimized = $optimized_content + $optimized_images;

        if ( $total_items > 0 ) {
            $stats['seo_score'] = round( ($total_optimized / $total_items) * 100 );
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
            'alerts' => get_option( 'woosuite_security_alerts', null )
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

    // --- Quarantine & Ignore Callbacks ---

    public function get_quarantined_files( $request ) {
        $quarantine = new WooSuite_Security_Quarantine();
        return new WP_REST_Response( array( 'files' => $quarantine->get_quarantined_files() ), 200 );
    }

    public function move_to_quarantine( $request ) {
        $params = $request->get_json_params();
        $file = isset( $params['file'] ) ? $params['file'] : '';
        if ( empty( $file ) ) return new WP_REST_Response( array( 'success' => false, 'message' => 'No file specified' ), 400 );

        $quarantine = new WooSuite_Security_Quarantine();
        $result = $quarantine->quarantine_file( $file );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => $result->get_error_message() ), 500 );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public function restore_from_quarantine( $request ) {
        $params = $request->get_json_params();
        $id = isset( $params['id'] ) ? $params['id'] : '';
        if ( empty( $id ) ) return new WP_REST_Response( array( 'success' => false, 'message' => 'No ID specified' ), 400 );

        $quarantine = new WooSuite_Security_Quarantine();
        $result = $quarantine->restore_file( $id );

        if ( is_wp_error( $result ) ) {
             return new WP_REST_Response( array( 'success' => false, 'message' => $result->get_error_message() ), 500 );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public function delete_from_quarantine( $request ) {
        $params = $request->get_json_params();
        $id = isset( $params['id'] ) ? $params['id'] : '';
        if ( empty( $id ) ) return new WP_REST_Response( array( 'success' => false, 'message' => 'No ID specified' ), 400 );

        $quarantine = new WooSuite_Security_Quarantine();
        $result = $quarantine->delete_file( $id );

        if ( is_wp_error( $result ) ) {
             return new WP_REST_Response( array( 'success' => false, 'message' => $result->get_error_message() ), 500 );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public function get_ignored_paths( $request ) {
        $ignored = get_option( 'woosuite_security_ignored_paths', array() );
        return new WP_REST_Response( array( 'ignored' => $ignored ), 200 );
    }

    public function add_ignored_path( $request ) {
        $params = $request->get_json_params();
        $path = isset( $params['path'] ) ? $params['path'] : '';
        if ( empty( $path ) ) return new WP_REST_Response( array( 'success' => false ), 400 );

        // Normalize slashes
        $path = wp_normalize_path( $path );

        $ignored = get_option( 'woosuite_security_ignored_paths', array() );
        if ( ! in_array( $path, $ignored ) ) {
            $ignored[] = $path;
            update_option( 'woosuite_security_ignored_paths', $ignored );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public function bulk_security_action( $request ) {
        $params = $request->get_json_params();
        $action = isset( $params['action'] ) ? $params['action'] : '';
        $items = isset( $params['items'] ) ? $params['items'] : array();

        if ( empty( $items ) || ! is_array( $items ) ) {
             return new WP_REST_Response( array( 'success' => false, 'message' => 'No items selected' ), 400 );
        }

        $count = 0;
        if ( $action === 'ignore' ) {
             $ignored = get_option( 'woosuite_security_ignored_paths', array() );
             foreach ( $items as $path ) {
                 $path = wp_normalize_path( $path );
                 if ( ! in_array( $path, $ignored ) ) {
                     $ignored[] = $path;
                     $count++;
                 }
             }
             if ( $count > 0 ) {
                 update_option( 'woosuite_security_ignored_paths', $ignored );
             }
        } elseif ( $action === 'delete' ) {
             // CAUTION: This deletes files!
             foreach ( $items as $path ) {
                 $full_path = ABSPATH . $path; // Path is relative
                 if ( file_exists( $full_path ) ) {
                     // Check if safe
                     if ( unlink( $full_path ) ) {
                         $count++;
                     }
                 }
             }
        }

        return new WP_REST_Response( array( 'success' => true, 'count' => $count ), 200 );
    }

    public function perform_maintenance( $request ) {
        global $wpdb;
        $params = $request->get_json_params();
        $action = isset( $params['action'] ) ? $params['action'] : '';

        $message = 'No action taken';
        $freed = 0;

        if ( $action === 'clear_transients' ) {
            // Delete expired transients
            $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%'" );
            $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_site_transient_%'" );
            $message = 'Transients cleared.';
        } elseif ( $action === 'delete_revisions' ) {
            $wpdb->query( "DELETE FROM $wpdb->posts WHERE post_type = 'revision'" );
            $message = 'Post revisions deleted.';
        } elseif ( $action === 'spam_comments' ) {
            $wpdb->query( "DELETE FROM $wpdb->comments WHERE comment_approved = 'spam'" );
            $message = 'Spam comments deleted.';
        } elseif ( $action === 'optimize_db' ) {
            // Basic optimization
            $tables = $wpdb->get_results( "SHOW TABLES", ARRAY_N );
            foreach ( $tables as $table ) {
                $wpdb->query( "OPTIMIZE TABLE {$table[0]}" );
            }
            $message = 'Database optimized.';
        }

        return new WP_REST_Response( array( 'success' => true, 'message' => $message ), 200 );
    }

    public function remove_ignored_path( $request ) {
        $params = $request->get_json_params();
        $path = isset( $params['path'] ) ? $params['path'] : '';
        if ( empty( $path ) ) return new WP_REST_Response( array( 'success' => false ), 400 );

        $ignored = get_option( 'woosuite_security_ignored_paths', array() );
        $index = array_search( $path, $ignored );
        if ( $index !== false ) {
            unset( $ignored[$index] );
            update_option( 'woosuite_security_ignored_paths', array_values( $ignored ) );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public function analyze_security_file( $request ) {
        $params = $request->get_json_params();
        $filepath = isset( $params['file'] ) ? sanitize_text_field( $params['file'] ) : '';

        if ( empty( $filepath ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'No file specified.' ), 400 );
        }

        // Convert relative path to absolute
        if ( strpos( $filepath, ABSPATH ) === false ) {
            $real_filepath = ABSPATH . $filepath;
        } else {
            $real_filepath = $filepath;
        }

        if ( ! file_exists( $real_filepath ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'File not found.' ), 404 );
        }

        // Limit file reading to prevent memory issues (Read first 5KB)
        $content = file_get_contents( $real_filepath, false, null, 0, 5120 );
        if ( $content === false ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Could not read file.' ), 500 );
        }

        $groq = new WooSuite_Groq();
        $analysis = $groq->analyze_security_threat( $content, basename( $real_filepath ) );

        if ( is_wp_error( $analysis ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => $analysis->get_error_message() ), 500 );
        }

        return new WP_REST_Response( array( 'success' => true, 'analysis' => $analysis ), 200 );
    }

    public function analyze_firewall_logs( $request ) {
        // Fetch blocked logs from DB
        $security = new WooSuite_Security( $this->plugin_name, $this->version );

        // We need a specific query for blocked requests
        global $wpdb;
        $table = $wpdb->prefix . 'woosuite_security_logs';
        $logs = $wpdb->get_results( "SELECT * FROM $table WHERE blocked = 1 ORDER BY created_at DESC LIMIT 50" );

        if ( empty( $logs ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'No blocked requests found.' ), 400 );
        }

        // Summarize
        $summary = "";
        foreach ( $logs as $l ) {
            $summary .= "[{$l->created_at}] IP: {$l->ip_address} - {$l->event}\n";
        }

        $groq = new WooSuite_Groq();
        $analysis = $groq->analyze_firewall_logs( $summary );

        if ( is_wp_error( $analysis ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => $analysis->get_error_message() ), 500 );
        }

        return new WP_REST_Response( array( 'success' => true, 'analysis' => $analysis ), 200 );
    }

    public function analyze_security_logs( $request ) {
        // Fetch recent security logs
        $security = new WooSuite_Security( $this->plugin_name, $this->version );
        $logs = $security->get_logs( 50 ); // Get last 50 events

        if ( empty( $logs ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'No security logs found to analyze.' ), 400 );
        }

        // Summarize logs for AI context
        $summary = "";
        $counts = array();
        foreach ( $logs as $log ) {
            $event = $log->event;
            if ( ! isset( $counts[$event] ) ) $counts[$event] = 0;
            $counts[$event]++;
        }

        $summary .= "Event Counts:\n";
        foreach ( $counts as $event => $count ) {
            $summary .= "- $event: $count occurrences\n";
        }

        $summary .= "\nRecent Entries:\n";
        $recent = array_slice( $logs, 0, 10 );
        foreach ( $recent as $l ) {
            $summary .= "[{$l->created_at}] IP: {$l->ip_address} - {$l->event} (Severity: {$l->severity})\n";
        }

        $groq = new WooSuite_Groq();
        $analysis = $groq->analyze_security_logs( $summary );

        if ( is_wp_error( $analysis ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => $analysis->get_error_message() ), 500 );
        }

        return new WP_REST_Response( array( 'success' => true, 'analysis' => $analysis ), 200 );
    }

    // --- SEO Batch ---

    public function resume_seo_batch( $request ) {
        if ( ! class_exists( 'WooSuite_Seo_Worker' ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Worker class not found' ), 500 );
        }
        $worker = new WooSuite_Seo_Worker();
        $worker->resume_batch();

        // Attempt to run one batch cycle immediately (Manual Trigger)
        // This helps if WP Cron is stuck or disabled.
        // The worker will run for ~25s and then return.
        $worker->process_batch();

        return new WP_REST_Response( array( 'success' => true, 'message' => 'Batch resumed and triggered' ), 200 );
    }

    public function start_seo_batch( $request ) {
        $params = $request->get_json_params();
        $rewrite = isset( $params['rewriteTitles'] ) && $params['rewriteTitles'] ? 'yes' : 'no';
        update_option( 'woosuite_seo_rewrite_titles', $rewrite );

        $filters = array(
            'type' => isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'product',
            'category' => isset( $params['category'] ) ? intval( $params['category'] ) : 0,
            'ids' => isset( $params['ids'] ) && is_array( $params['ids'] ) ? array_map( 'intval', $params['ids'] ) : array()
        );

        // Reset failure flags to ensure we retry items that failed previously
        global $wpdb;
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_woosuite_seo_failed'" );
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_woosuite_seo_last_error'" );

        if ( ! class_exists( 'WooSuite_Seo_Worker' ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Worker class not found' ), 500 );
        }
        $worker = new WooSuite_Seo_Worker();
        $worker->start_batch( $filters );

        // KICKSTART: Run one batch cycle immediately to bypass potential WP Cron issues
        $worker->process_batch();

        return new WP_REST_Response( array( 'success' => true, 'message' => 'Batch started' ), 200 );
    }

    public function stop_seo_batch( $request ) {
        update_option( 'woosuite_seo_batch_stop_signal', true );
        // Force update status locally to ensure immediate UI feedback
        $status = get_option( 'woosuite_seo_batch_status', array() );
        $status['status'] = 'stopped';
        $status['message'] = 'Process stopped by user.';
        update_option( 'woosuite_seo_batch_status', $status );

        return new WP_REST_Response( array( 'success' => true, 'message' => 'Stopping...' ), 200 );
    }

    public function reset_seo_batch( $request ) {
        global $wpdb;
        update_option( 'woosuite_seo_batch_stop_signal', true );
        update_option( 'woosuite_seo_batch_status', array( 'status' => 'idle' ) );

        // Clear failure flags AND 'processed' timestamp so items can be retried
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_woosuite_seo_failed'" );
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_woosuite_seo_last_error'" );
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_woosuite_seo_processed_at'" );

        // Clear Logs - Reset to empty array to fix crash
        update_option( 'woosuite_debug_log', array() );

        return new WP_REST_Response( array( 'success' => true, 'message' => 'Reset complete' ), 200 );
    }

    public function get_seo_batch_status( $request ) {
        $status = get_option( 'woosuite_seo_batch_status', array( 'status' => 'idle' ) );
        return new WP_REST_Response( $status, 200 );
    }

    public function run_seo_scan( $request ) {
        if ( ! class_exists( 'WooSuite_Seo_Worker' ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Worker class not found' ), 500 );
        }
        $worker = new WooSuite_Seo_Worker();

        // Products
        $total_products = $this->count_posts( 'product' );
        $missing_products = $worker->get_total_unoptimized_count( array( 'type' => 'product' ) );

        // Posts
        $total_posts = $this->count_posts( 'post' );
        $missing_posts = $worker->get_total_unoptimized_count( array( 'type' => 'post' ) );

        // Images
        $total_images = $this->count_posts( 'attachment', 'inherit', 'image' );
        $missing_images = $worker->get_total_unoptimized_count( array( 'type' => 'image' ) );

        $total_items = $total_products + $total_posts + $total_images;
        $total_missing = $missing_products + $missing_posts + $missing_images;
        $optimized = $total_items - $total_missing;

        $score = $total_items > 0 ? round( ( $optimized / $total_items ) * 100 ) : 0;

        $data = array(
            'score' => $score,
            'total_items' => $total_items,
            'optimized_items' => $optimized,
            'details' => array(
                'product' => array( 'total' => $total_products, 'missing' => $missing_products ),
                'post' => array( 'total' => $total_posts, 'missing' => $missing_posts ),
                'image' => array( 'total' => $total_images, 'missing' => $missing_images ),
            )
        );

        return new WP_REST_Response( $data, 200 );
    }

    private function count_posts( $type, $status = 'publish', $mime = '' ) {
        $args = array(
            'post_type' => $type,
            'post_status' => $status,
            'posts_per_page' => -1,
            'fields' => 'ids'
        );
        if ( $mime ) $args['post_mime_type'] = $mime;
        $query = new WP_Query( $args );
        return $query->found_posts;
    }

    public function check_permission() {
        return current_user_can( 'manage_options' );
    }
}
