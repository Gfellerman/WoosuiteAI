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
        update_option( $this->log_option, $logs, false );
    }

    public function start_batch() {
        update_option( 'woosuite_seo_batch_stop_signal', false );

        $total = $this->get_total_unoptimized_count();
        $this->log( "Starting Batch (Groq Engine). Total items found: $total" );

        update_option( 'woosuite_seo_batch_status', array(
            'status' => 'running',
            'total' => $total,
            'processed' => 0,
            'start_time' => current_time( 'mysql' ),
            'last_updated' => time(),
            'message' => "Starting optimization of $total items..."
        ));

        if ( ! wp_next_scheduled( 'woosuite_seo_batch_process' ) ) {
            wp_schedule_single_event( time(), 'woosuite_seo_batch_process' );
        }
    }

    public function process_batch() {
        if ( function_exists( 'set_time_limit' ) ) set_time_limit( 300 );

        if ( get_option( 'woosuite_seo_batch_stop_signal' ) ) {
            $this->stop_batch("Process stopped by user.");
            return;
        }

        $status = get_option( 'woosuite_seo_batch_status' );
        if ( ! $status || $status['status'] !== 'running' ) return;

        $status['last_updated'] = time();
        update_option( 'woosuite_seo_batch_status', $status );

        $start_time = microtime( true );
        // Groq is fast, but we limit execution time to keep the server happy
        $max_execution_time = 25;

        try {
            while ( ( microtime( true ) - $start_time ) < $max_execution_time ) {

                if ( get_option( 'woosuite_seo_batch_stop_signal' ) ) {
                    $this->stop_batch("Process stopped by user.");
                    return;
                }

                $status = get_option( 'woosuite_seo_batch_status' );
                if ( $status['status'] !== 'running' ) {
                    return;
                }

                $ids = $this->get_next_batch_items( 1 );

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
                    break;
                }

                // Smart Throttling for Groq Free Tier (approx 30 RPM = 1 request every 2s)
                // We add a slight buffer (2s)
                sleep(2);
            }
        } catch ( Exception $e ) {
            $this->log( "CRITICAL BATCH ERROR: " . $e->getMessage() );
        } catch ( Throwable $e ) {
             $this->log( "FATAL BATCH ERROR: " . $e->getMessage() );
        }

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

        update_post_meta( $id, '_woosuite_seo_processed_at', time() );

        try {
            $rewrite_titles = get_option( 'woosuite_seo_rewrite_titles', 'no' ) === 'yes';

            if ( $post->post_type === 'attachment' ) {
                $this->process_image( $post );
            } else {
                $this->process_text( $post, $rewrite_titles );
            }

            $status['processed']++;
            $status['last_updated'] = time();
            $status['message'] = "Processed ID {$id}: " . substr($post->post_title, 0, 30) . "...";
            update_option( 'woosuite_seo_batch_status', $status );

            return 'SUCCESS';

        } catch ( Exception $e ) {
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

        // Call Groq
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

        if ( $rewrite_titles && ! empty( $result['simplifiedTitle'] ) ) {
            wp_update_post( array(
                'ID' => $post->ID,
                'post_title' => sanitize_text_field( $result['simplifiedTitle'] )
            ) );
            $updates++;
        }

        if ( $updates === 0 ) {
            $this->log( "ID {$post->ID} - AI result valid but no fields were updated." );
        } else {
            delete_post_meta( $post->ID, '_woosuite_seo_failed' );
            delete_post_meta( $post->ID, '_woosuite_seo_last_error' );
        }
    }

    private function process_image( $post ) {
        $url = wp_get_attachment_url( $post->ID );
        if ( ! $url ) {
            throw new Exception( "Missing attachment URL." );
        }

        // Call Groq
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

    private function get_next_batch_items( $limit ) {
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
                array( 'key' => '_woosuite_meta_description', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_seo_failed', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_seo_processed_at', 'compare' => 'NOT EXISTS' )
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
