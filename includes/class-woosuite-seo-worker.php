<?php

class WooSuite_Seo_Worker {

    private $groq;
    private $log_option = 'woosuite_debug_log';

    public function __construct() {
        // Switch to Groq
        $this->groq = new WooSuite_Groq();
        add_action( 'woosuite_seo_batch_process', array( $this, 'process_batch' ) );
    }

    private function log( $message ) {
        // TRUNCATE MESSAGE to prevent massive log files from crashing the UI
        // If message is > 500 chars, truncate it.
        if ( strlen( $message ) > 500 ) {
            $message = substr( $message, 0, 500 ) . '... [TRUNCATED]';
        }

        $timestamp = current_time( 'mysql' );
        $entry = "[$timestamp] [SEO Worker] $message";

        // Log to server error log (full message if possible, but keeping it safe)
        error_log( $entry );

        // Log to DB for UI display (keep last 50 lines)
        $logs = get_option( $this->log_option, array() );
        if ( ! is_array( $logs ) ) $logs = array();

        array_unshift( $logs, $entry );
        if ( count( $logs ) > 50 ) {
            $logs = array_slice( $logs, 0, 50 );
        }
        update_option( $this->log_option, $logs, false );
    }

    /**
     * Start the batch process with filters
     * @param array $filters e.g. ['type' => 'product', 'category' => 123]
     */
    public function start_batch( $filters = array() ) {
        update_option( 'woosuite_seo_batch_stop_signal', false );
        update_option( 'woosuite_seo_batch_filters', $filters );

        $total = $this->get_total_unoptimized_count( $filters );
        $type_label = isset( $filters['type'] ) ? ucfirst( $filters['type'] ) : 'Item';

        $this->log( "Starting Batch for {$type_label}s. Total found: $total" );

        update_option( 'woosuite_seo_batch_status', array(
            'status' => 'running',
            'total' => $total,
            'processed' => 0,
            'failed' => 0,
            'start_time' => current_time( 'mysql' ),
            'last_updated' => time(),
            'message' => "Starting optimization of $total {$type_label}s..."
        ));

        if ( ! wp_next_scheduled( 'woosuite_seo_batch_process' ) ) {
            wp_schedule_single_event( time(), 'woosuite_seo_batch_process' );
        }
    }

    public function resume_batch() {
        $status = get_option( 'woosuite_seo_batch_status' );
        // Only resume if not already running (or if stuck)
        // We force it to 'running'
        $status['status'] = 'running';
        $status['message'] = 'Resuming batch process...';
        $status['last_updated'] = time();
        update_option( 'woosuite_seo_batch_status', $status );
        update_option( 'woosuite_seo_batch_stop_signal', false );

        $this->log( "Manual Resume Triggered." );

        if ( ! wp_next_scheduled( 'woosuite_seo_batch_process' ) ) {
            wp_schedule_single_event( time(), 'woosuite_seo_batch_process' );
        }
    }

    public function process_batch() {
        // WRAP ENTIRE WORKER IN TRY/CATCH TO PREVENT SILENT DEATH
        try {
            // Cleanup stuck items (older than 10 mins)
            $this->cleanup_stuck_items();

            if ( function_exists( 'set_time_limit' ) ) set_time_limit( 300 );

            if ( get_option( 'woosuite_seo_batch_stop_signal' ) ) {
                $this->stop_batch("Process stopped by user.");
                return;
            }

            $status = get_option( 'woosuite_seo_batch_status' );

            // If status is 'paused', it means we were called by the scheduler to RESUME.
            // So we switch back to 'running'.
            if ( isset( $status['status'] ) && $status['status'] === 'paused' ) {
                $status['status'] = 'running';
                $status['message'] = 'Resuming after rate limit pause...';
                update_option( 'woosuite_seo_batch_status', $status );
                $this->log( "Resuming batch after pause..." );
            } elseif ( ! $status || $status['status'] !== 'running' ) {
                return;
            }

            $status['last_updated'] = time();
            update_option( 'woosuite_seo_batch_status', $status );

            $start_time = microtime( true );
            // Groq is fast, but we limit execution time to keep the server happy
            $max_execution_time = 25;

            while ( ( microtime( true ) - $start_time ) < $max_execution_time ) {

                if ( get_option( 'woosuite_seo_batch_stop_signal' ) ) {
                    $this->stop_batch("Process stopped by user.");
                    return;
                }

                $status = get_option( 'woosuite_seo_batch_status' );
                if ( $status['status'] !== 'running' ) {
                    return;
                }

                $filters = get_option( 'woosuite_seo_batch_filters', array() );
                $ids = $this->get_next_batch_items( 1, $filters );

                if ( empty( $ids ) ) {
                    $this->log( "No more items to process. Batch Complete." );
                    $status['status'] = 'complete';
                    $status['message'] = 'Optimization Complete!';
                    $status['processed'] = $status['total'];
                    $status['last_updated'] = time();
                    update_option( 'woosuite_seo_batch_status', $status );
                    return;
                }

                $id = $ids[0];
                $result = $this->process_single_item( $id, $status );

                if ( $result === 'RATE_LIMIT' ) {
                    // Schedule a resume event for 60 seconds later
                    if ( ! get_option( 'woosuite_seo_batch_stop_signal' ) ) {
                        // Clear any existing schedule first to be safe
                        wp_clear_scheduled_hook( 'woosuite_seo_batch_process' );
                        wp_schedule_single_event( time() + 60, 'woosuite_seo_batch_process' );
                        $this->log( "Batch Paused (Rate Limit). Auto-resume scheduled in 60s." );
                    }
                    break;
                }

                // Increment counts handled in process_single_item?
                // No, process_single_item handles 'processed' increment only on success.
                // We need to handle failures here if process_single_item didn't.
                // But process_single_item takes &$status reference.

                // Let's refactor process_single_item to NOT update status counts, or we check result.
                if ( $result === 'ERROR' ) {
                    $status['processed']++;
                    if ( ! isset( $status['failed'] ) ) $status['failed'] = 0;
                    $status['failed']++;
                    update_option( 'woosuite_seo_batch_status', $status );
                }

                // Smart Throttling for Groq Free Tier (approx 30 RPM = 1 request every 2s)
                // We add a slight buffer (2s)
                sleep(2);
            }
        } catch ( Throwable $e ) { // Catch global Throwable to ensure nothing escapes
             $this->log( "FATAL BATCH WORKER ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() );
             // Mark batch as stopped or failed? Maybe keep running to retry?
             // Ideally we stop to prevent infinite crash loops.
             $this->stop_batch("Stopped due to internal error. Check logs.");
        }

        // Reschedule if still running (and not paused by rate limit)
        $status = get_option( 'woosuite_seo_batch_status' ); // Re-fetch to check if paused
        if ( ! get_option( 'woosuite_seo_batch_stop_signal' ) && $status['status'] === 'running' ) {
             wp_schedule_single_event( time() + 1, 'woosuite_seo_batch_process' );
        }
    }

    private function process_single_item( $id, &$status ) {
        $post = get_post( $id );
        if ( ! $post ) {
             update_post_meta( $id, '_woosuite_seo_failed', 1 );
             return 'ERROR';
        }

        $this->log( "Processing ID {$id} ({$post->post_type})..." );

        // Mark as processed immediately to avoid loops
        update_post_meta( $id, '_woosuite_seo_processed_at', time() );

        try {
            $rewrite_titles = get_option( 'woosuite_seo_rewrite_titles', 'no' ) === 'yes';

            if ( $post->post_type === 'attachment' ) {
                $this->process_image( $post );
            } else {
                $this->process_text( $post, $rewrite_titles );
            }

            // Success
            $status['processed']++;
            $status['last_updated'] = time();
            $status['message'] = "Processed ID {$id}: " . substr($post->post_title, 0, 30) . "...";
            update_option( 'woosuite_seo_batch_status', $status );

            return 'SUCCESS';

        } catch ( Throwable $e ) { // Catch everything (Exceptions + Errors)
            $msg = $e->getMessage();
            $this->log( "Error processing ID {$id}: " . $msg );

            if ( $msg === 'RATE_LIMIT_HIT' ) {
                $this->log( "Rate Limit Hit! Pausing batch..." );

                $status['status'] = 'paused';
                $status['message'] = 'Paused due to API Rate Limit. Auto-resuming shortly...';
                $status['last_updated'] = time();
                update_option( 'woosuite_seo_batch_status', $status );

                delete_post_meta( $id, '_woosuite_seo_processed_at' );

                return 'RATE_LIMIT';
            }

            update_post_meta( $id, '_woosuite_seo_failed', 1 );
            update_post_meta( $id, '_woosuite_seo_last_error', substr( $msg, 0, 250 ) );
            return 'ERROR';
        }
    }

    private function stop_batch( $message = "Stopped" ) {
        $this->log( "Batch Stopped: $message" );
        $status = get_option( 'woosuite_seo_batch_status' );
        $status['status'] = 'stopped';
        $status['message'] = $message;
        $status['last_updated'] = time();
        update_option( 'woosuite_seo_batch_status', $status );
    }

    private function process_text( $post, $rewrite_titles ) {
        $item = array(
            'type' => $post->post_type,
            'name' => $post->post_title,
            'description' => strip_tags( $post->post_excerpt ?: $post->post_content ),
            'rewrite_title' => $rewrite_titles
        );

        if ( $post->post_type === 'product' && function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $post->ID );
            if ( $product ) $item['price'] = $product->get_price();
        }

        // Call Groq for Text SEO
        $result = $this->groq->generate_seo_meta( $item );

        if ( is_wp_error( $result ) ) {
            if ( $result->get_error_code() === 'rate_limit' ) {
                throw new Exception( 'RATE_LIMIT_HIT' );
            }
            throw new Exception( $result->get_error_message() );
        }

        if ( empty( $result ) ) {
            throw new Exception( "AI returned empty result." );
        }

        $updates = 0;

        if ( ! empty( $result['title'] ) ) {
            update_post_meta( $post->ID, '_woosuite_meta_title', sanitize_text_field( $result['title'] ) );
            $updates++;
        }

        if ( ! empty( $result['description'] ) ) {
            $desc = sanitize_text_field( $result['description'] );
            update_post_meta( $post->ID, '_woosuite_meta_description', $desc );
            update_post_meta( $post->ID, '_yoast_wpseo_metadesc', $desc );
            update_post_meta( $post->ID, 'rank_math_description', $desc );
            $updates++;
        }

        if ( ! empty( $result['llmSummary'] ) ) {
            update_post_meta( $post->ID, '_woosuite_llm_summary', sanitize_textarea_field( $result['llmSummary'] ) );
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
                wp_set_object_terms( $post->ID, $tags, $taxonomy, false );
                $updates++;
            }
        }

        if ( $rewrite_titles && ! empty( $result['simplifiedTitle'] ) ) {
            wp_update_post( array(
                'ID' => $post->ID,
                'post_title' => sanitize_text_field( $result['simplifiedTitle'] )
            ) );
            $updates++;
        }

        // PRODUCT IMAGE OPTIMIZATION (Contextual)
        if ( $post->post_type === 'product' ) {
            $this->process_product_images( $post->ID, $post->post_title );
        }

        if ( $updates === 0 ) {
            $this->log( "ID {$post->ID} - AI result valid but no fields were updated." );
        } else {
            delete_post_meta( $post->ID, '_woosuite_seo_failed' );
            delete_post_meta( $post->ID, '_woosuite_seo_last_error' );
        }
    }

    private function process_product_images( $product_id, $product_name ) {
        // Get Featured Image
        $featured_id = get_post_thumbnail_id( $product_id );
        $gallery_ids = get_post_meta( $product_id, '_product_image_gallery', true );

        $image_ids = array();
        if ( $featured_id ) $image_ids[] = $featured_id;
        if ( ! empty( $gallery_ids ) ) {
            $ids = explode( ',', $gallery_ids );
            $image_ids = array_merge( $image_ids, $ids );
        }

        $image_ids = array_unique( array_filter( $image_ids ) );

        // Prepare Text Context (Avoid Vision API for speed/stability)
        $product_desc = '';
        if ( function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $product_desc = strip_tags( $product->get_short_description() ?: $product->get_description() );
            }
        }
        $context = array( 'name' => $product_name, 'description' => $product_desc );

        foreach ( $image_ids as $img_id ) {
            // Skip if already optimized
            if ( get_post_meta( $img_id, '_wp_attachment_image_alt', true ) ) {
                continue;
            }

            $img_post = get_post( $img_id );
            if ( ! $img_post ) continue;

            $this->log( "Optimizing Product Image ID {$img_id} for Product: {$product_name}" );

            try {
                $url = wp_get_attachment_url( $img_id );
                if ( ! $url ) continue;

                // Use Text-Based Generation (Passed Context)
                $result = $this->groq->generate_image_seo( $url, $product_name, $context );

                if ( ! is_wp_error( $result ) && ! empty( $result['altText'] ) ) {
                    update_post_meta( $img_id, '_wp_attachment_image_alt', sanitize_text_field( $result['altText'] ) );

                    if ( ! empty( $result['title'] ) ) {
                        wp_update_post( array(
                            'ID' => $img_id,
                            'post_title' => sanitize_text_field( $result['title'] )
                        ) );
                    }

                    // Mark as processed so it doesn't show up in the "Images" tab list
                    update_post_meta( $img_id, '_woosuite_seo_processed_at', time() );
                }

                // Sleep briefly to avoid hammering the API if product has many images
                sleep(1);

            } catch ( Exception $e ) {
                $this->log( "Failed to optimize image {$img_id}: " . $e->getMessage() );
            }
        }
    }

    private function process_image( $post ) {
        $url = wp_get_attachment_url( $post->ID );
        if ( ! $url ) {
            throw new Exception( "Missing attachment URL." );
        }

        $result = $this->groq->generate_image_seo( $url, basename( $url ) );

        if ( is_wp_error( $result ) ) {
             if ( $result->get_error_code() === 'rate_limit' ) {
                 throw new Exception( 'RATE_LIMIT_HIT' );
             }
             throw new Exception( $result->get_error_message() );
        }

        if ( empty( $result ) ) {
            throw new Exception( "AI returned empty result for image." );
        }

        $updates = 0;
        if ( ! empty( $result['altText'] ) ) {
            update_post_meta( $post->ID, '_wp_attachment_image_alt', sanitize_text_field( $result['altText'] ) );
            $updates++;
        }
        if ( ! empty( $result['title'] ) ) {
            wp_update_post( array(
                'ID' => $post->ID,
                'post_title' => sanitize_text_field( $result['title'] )
            ) );
            $updates++;
        }

        if ( $updates > 0 ) {
            delete_post_meta( $post->ID, '_woosuite_seo_failed' );
            delete_post_meta( $post->ID, '_woosuite_seo_last_error' );
        }
    }

    private function get_next_batch_items( $limit, $filters = array() ) {
        // Build Meta Query (Unoptimized)
        $meta_query = array(
            'relation' => 'AND',
            array( 'key' => '_woosuite_seo_failed', 'compare' => 'NOT EXISTS' ),
            array( 'key' => '_woosuite_seo_processed_at', 'compare' => 'NOT EXISTS' )
        );

        // Determine Type
        $type = isset( $filters['type'] ) ? $filters['type'] : 'product';

        $args = array(
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
        );

        if ( $type === 'image' ) {
            $args['post_type'] = 'attachment';
            $args['post_status'] = 'inherit';
            $args['post_mime_type'] = 'image';
            // SKIP ORPHAN IMAGES (User Requirement: Only attached images)
            $args['post_parent__not_in'] = array( 0 );

            // Image specific meta check
            $meta_query[] = array( 'key' => '_wp_attachment_image_alt', 'compare' => 'NOT EXISTS' );

        } else {
            $args['post_type'] = $type; // product, post, page
            $args['post_status'] = 'publish';

            // Text specific meta check
            $meta_query[] = array( 'key' => '_woosuite_meta_description', 'compare' => 'NOT EXISTS' );
        }

        // Apply Category Filter if present
        if ( ! empty( $filters['category'] ) ) {
            $taxonomy = ($type === 'product') ? 'product_cat' : 'category';
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $filters['category'],
                    'include_children' => true
                )
            );
        }

        // Apply Selection Filter (Specific IDs)
        if ( ! empty( $filters['ids'] ) && is_array( $filters['ids'] ) ) {
            $args['post__in'] = $filters['ids'];
            // If IDs are provided, we MIGHT ignore 'processed' checks?
            // No, user probably wants to optimize selected items that NEED optimization.
            // If they are already done, we skip.
            // But if they force 'Optimize Selected', maybe they want to redo?
            // The UI usually filters for 'unoptimized' or assumes user knows.
            // Currently meta_query excludes processed. So re-selecting optimized items won't do anything unless we clear meta first.
            // I'll stick to 'unoptimized' logic for safety.
        }

        $args['meta_query'] = $meta_query;

        return get_posts( $args );
    }

    public function get_total_unoptimized_count( $filters = array() ) {
        $type = isset( $filters['type'] ) ? $filters['type'] : 'product';

        $args = array(
            'fields' => 'ids',
            'posts_per_page' => -1, // Just counting
        );

        $meta_query = array(
            'relation' => 'AND',
            array( 'key' => '_woosuite_seo_failed', 'compare' => 'NOT EXISTS' ),
            array( 'key' => '_woosuite_seo_processed_at', 'compare' => 'NOT EXISTS' )
        );

        if ( $type === 'image' ) {
            $args['post_type'] = 'attachment';
            $args['post_status'] = 'inherit';
            $args['post_mime_type'] = 'image';
            // SKIP ORPHAN IMAGES
            $args['post_parent__not_in'] = array( 0 );
            $meta_query[] = array( 'key' => '_wp_attachment_image_alt', 'compare' => 'NOT EXISTS' );
        } else {
            $args['post_type'] = $type;
            $args['post_status'] = 'publish';
            $meta_query[] = array( 'key' => '_woosuite_meta_description', 'compare' => 'NOT EXISTS' );
        }

        if ( ! empty( $filters['category'] ) ) {
            $taxonomy = ($type === 'product') ? 'product_cat' : 'category';
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $filters['category'],
                    'include_children' => true
                )
            );
        }

        if ( ! empty( $filters['ids'] ) && is_array( $filters['ids'] ) ) {
            $args['post__in'] = $filters['ids'];
        }

        $args['meta_query'] = $meta_query;

        $query = new WP_Query( $args );
        return $query->found_posts;
    }

    private function cleanup_stuck_items() {
        global $wpdb;
        $cutoff = time() - 600; // 10 mins
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM $wpdb->postmeta WHERE meta_key = '_woosuite_seo_processed_at' AND meta_value < %d",
            $cutoff
        ) );
    }
}
