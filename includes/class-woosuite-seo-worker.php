<?php

class WooSuite_Seo_Worker {

    private $gemini;
    private $log_option = 'woosuite_debug_log';

    public function __construct() {
        $this->gemini = new WooSuite_Gemini();
        add_action( 'woosuite_seo_batch_process', array( $this, 'process_batch' ) );
    }

    private function log( $message ) {
        $timestamp = current_time( 'mysql' );
        $entry = "[$timestamp] [SEO Worker] $message";

        // Log to server error log
        error_log( $entry );

        // Log to DB for UI display (keep last 50 lines)
        $logs = get_option( $this->log_option, array() );
        if ( ! is_array( $logs ) ) $logs = array();

        array_unshift( $logs, $entry );
        if ( count( $logs ) > 50 ) {
            $logs = array_slice( $logs, 0, 50 );
        }
        update_option( $this->log_option, $logs, false ); // Autoload=false to save memory
    }

    public function start_batch() {
        update_option( 'woosuite_seo_batch_stop_signal', false );

        $total = $this->get_total_unoptimized_count();
        $this->log( "Starting Batch. Total items found: $total" );

        update_option( 'woosuite_seo_batch_status', array(
            'status' => 'running',
            'total' => $total,
            'processed' => 0,
            'start_time' => current_time( 'mysql' ),
            'last_updated' => time(),
            'message' => "Starting optimization of $total items..."
        ));

        // Clear previous "processed" flags if this is a fresh start?
        // No, we rely on 'reset_seo_batch' for that.
        // Here we just pick up where we left off.

        if ( ! wp_next_scheduled( 'woosuite_seo_batch_process' ) ) {
            wp_schedule_single_event( time(), 'woosuite_seo_batch_process' );
        }
    }

    public function process_batch() {
        // Prevent PHP timeouts
        if ( function_exists( 'set_time_limit' ) ) set_time_limit( 300 ); // Try to give it more time if allowed

        // 1. Check Stop Signal
        if ( get_option( 'woosuite_seo_batch_stop_signal' ) ) {
            $this->stop_batch("Process stopped by user.");
            return;
        }

        $status = get_option( 'woosuite_seo_batch_status' );
        if ( ! $status || $status['status'] !== 'running' ) return;

        // Update heartbeat
        $status['last_updated'] = time();
        update_option( 'woosuite_seo_batch_status', $status );

        // 2. Process ONE item at a time (Safest for shared hosting / timeouts)
        // We can loop, but safely.
        $start_time = microtime( true );
        $max_execution_time = 20; // Run for max 20 seconds per batch event

        try {
            while ( ( microtime( true ) - $start_time ) < $max_execution_time ) {

                // Double check stop signal
                if ( get_option( 'woosuite_seo_batch_stop_signal' ) ) {
                    $this->stop_batch("Process stopped by user.");
                    return;
                }

                $ids = $this->get_next_batch_items( 1 );

                if ( empty( $ids ) ) {
                    $this->log( "No more items to process. Batch Complete." );
                    $status['status'] = 'complete';
                    $status['message'] = 'Optimization Complete!';
                    $status['processed'] = $status['total']; // Ensure UI shows 100%
                    $status['last_updated'] = time();
                    update_option( 'woosuite_seo_batch_status', $status );
                    return;
                }

                $id = $ids[0];
                $this->process_single_item( $id, $status );

                // Refresh status from DB in case it changed (though we just updated it in process_single_item)
                $status = get_option( 'woosuite_seo_batch_status' );
            }
        } catch ( Exception $e ) {
            $this->log( "CRITICAL BATCH ERROR: " . $e->getMessage() );
        } catch ( Throwable $e ) {
             $this->log( "FATAL BATCH ERROR: " . $e->getMessage() );
        }

        // 3. Always Schedule Next Run (unless stopped or complete)
        if ( ! get_option( 'woosuite_seo_batch_stop_signal' ) && $status['status'] === 'running' ) {
             // Add a small delay (1s) to prevent CPU spiking
             wp_schedule_single_event( time() + 1, 'woosuite_seo_batch_process' );
        }
    }

    private function process_single_item( $id, &$status ) {
        $post = get_post( $id );
        if ( ! $post ) {
             update_post_meta( $id, '_woosuite_seo_failed', 1 );
             return;
        }

        $this->log( "Processing ID {$id} ({$post->post_type})..." );

        // Mark as 'processing' to prevent other workers picking it up (if parallel)
        // For now, our get_next_batch_items logic handles this via 'NOT EXISTS' checks
        // But we should set a flag to avoid infinite loops if we fail to save description
        update_post_meta( $id, '_woosuite_seo_processed_at', time() );

        try {
            $rewrite_titles = get_option( 'woosuite_seo_rewrite_titles', 'no' ) === 'yes';

            if ( $post->post_type === 'attachment' ) {
                $this->process_image( $post );
            } else {
                $this->process_text( $post, $rewrite_titles );
            }

            // Update Global Status
            $status['processed']++;
            $status['last_updated'] = time();
            $status['message'] = "Processed ID {$id}: " . substr($post->post_title, 0, 30) . "...";
            update_option( 'woosuite_seo_batch_status', $status );

        } catch ( Exception $e ) {
            $this->log( "Error processing ID {$id}: " . $e->getMessage() );
            update_post_meta( $id, '_woosuite_seo_failed', 1 );
            update_post_meta( $id, '_woosuite_seo_last_error', substr( $e->getMessage(), 0, 250 ) );
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

        $result = $this->gemini->generate_seo_meta( $item );

        if ( is_wp_error( $result ) ) {
            if ( $result->get_error_code() === 'rate_limit' ) {
                throw new Exception( 'RATE_LIMIT_HIT' );
            }
            throw new Exception( $result->get_error_message() );
        }

        if ( empty( $result ) ) {
            throw new Exception( "AI returned empty result." );
        }

        // Save Data
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

        if ( $rewrite_titles && ! empty( $result['simplifiedTitle'] ) ) {
            wp_update_post( array(
                'ID' => $post->ID,
                'post_title' => sanitize_text_field( $result['simplifiedTitle'] )
            ) );
            $updates++;
        }

        // Even if description wasn't generated (maybe AI skipped it),
        // we count it as processed because we set '_woosuite_seo_processed_at' earlier.
        // This prevents the infinite loop.

        if ( $updates === 0 ) {
            $this->log( "ID {$post->ID} - AI result valid but no fields were updated." );
        } else {
            // Clear any previous error
            delete_post_meta( $post->ID, '_woosuite_seo_failed' );
            delete_post_meta( $post->ID, '_woosuite_seo_last_error' );
        }
    }

    private function process_image( $post ) {
        $url = wp_get_attachment_url( $post->ID );
        if ( ! $url ) {
            throw new Exception( "Missing attachment URL." );
        }

        $result = $this->gemini->generate_image_seo( $url, basename( $url ) );

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

    private function get_next_batch_items( $limit ) {
        // Updated Logic: We now exclude items that have '_woosuite_seo_processed_at'
        // This is crucial to stop the infinite loop on items where AI fails to generate a Description

        // Text Items
        $posts = get_posts( array(
            'post_type' => array( 'product', 'post', 'page' ),
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
            'meta_query' => array(
                'relation' => 'AND',
                array( 'key' => '_woosuite_meta_description', 'compare' => 'NOT EXISTS' ), // Still check if unoptimized
                array( 'key' => '_woosuite_seo_failed', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_seo_processed_at', 'compare' => 'NOT EXISTS' ) // New safety check
            )
        ) );

        if ( ! empty( $posts ) ) return $posts;

        // Image Items
        $images = get_posts( array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
            'meta_query' => array(
                'relation' => 'AND',
                array( 'key' => '_wp_attachment_image_alt', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_seo_failed', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_seo_processed_at', 'compare' => 'NOT EXISTS' )
            )
        ) );

        return $images;
    }

    private function get_total_unoptimized_count() {
        // Query must match get_next_batch_items logic to be accurate
        $q1 = new WP_Query( array(
            'post_type' => array( 'product', 'post', 'page' ),
            'post_status' => 'publish',
            'fields' => 'ids',
            'meta_query' => array(
                'relation' => 'AND',
                array( 'key' => '_woosuite_meta_description', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_seo_failed', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_seo_processed_at', 'compare' => 'NOT EXISTS' )
            )
        ) );

        $q2 = new WP_Query( array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'fields' => 'ids',
            'meta_query' => array(
                'relation' => 'AND',
                array( 'key' => '_wp_attachment_image_alt', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_seo_failed', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_seo_processed_at', 'compare' => 'NOT EXISTS' )
            )
        ) );

        return $q1->found_posts + $q2->found_posts;
    }
}
