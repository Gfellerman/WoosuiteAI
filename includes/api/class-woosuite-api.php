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
        return new WP_REST_Response( array( 'apiKey' => $api_key ), 200 );
    }

    public function test_api_connection( $request ) {
        $groq = new WooSuite_Groq();
        $result = $groq->test_connection();

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'message' => $result->get_error_message(),
                'code' => $result->get_error_code(),
                'data' => $result->get_error_data()
            ), 500 );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Connection Successful!',
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
            $args['post_type'] = $type; // post, page, product
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
            'post_type' => $type
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
            $meta_query[] = array(
                'relation' => 'OR',
                array( 'key' => '_woosuite_proposed_title', 'compare' => 'EXISTS' ),
                array( 'key' => '_woosuite_proposed_description', 'compare' => 'EXISTS' ),
                array( 'key' => '_woosuite_proposed_short_description', 'compare' => 'EXISTS' ),
            );
        } elseif ( $status === 'not_enhanced' ) {
            $meta_query[] = array(
                'relation' => 'AND',
                array( 'key' => '_woosuite_proposed_title', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_proposed_description', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_proposed_short_description', 'compare' => 'NOT EXISTS' ),
            );
        }

        if ( ! empty( $meta_query ) ) {
            $args['meta_query'] = $meta_query;
        }

        $query = new WP_Query( $args );
        $posts = $query->posts;
        $total = $query->found_posts;
        $pages = $query->max_num_pages;

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
                                ! empty( get_post_meta( $post->ID, '_woosuite_history__wp_attachment_image_alt', true ) ),
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

        $post = get_post($id);
        if (!$post) return new WP_REST_Response(array('success' => false, 'message' => 'Not found'), 404);

        $proposed = get_post_meta( $id, '_woosuite_proposed_' . $field, true );
        if ( empty( $proposed ) ) {
             return new WP_REST_Response( array( 'success' => false, 'message' => 'No proposed content found' ), 400 );
        }

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
