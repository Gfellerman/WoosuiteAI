<?php

class WooSuite_Seo_Worker {

    private $gemini;

    public function __construct() {
        $this->gemini = new WooSuite_Gemini();
        add_action( 'woosuite_seo_batch_process', array( $this, 'process_batch' ) );
    }

    public function start_batch() {
        update_option( 'woosuite_seo_batch_stop_signal', false );

        $total = $this->get_total_unoptimized_count();

        update_option( 'woosuite_seo_batch_status', array(
            'status' => 'running',
            'total' => $total,
            'processed' => 0,
            'start_time' => current_time( 'mysql' ),
            'last_updated' => time(), // Track activity
            'message' => "Starting optimization of $total items..."
        ));

        if ( ! wp_next_scheduled( 'woosuite_seo_batch_process' ) ) {
            wp_schedule_single_event( time(), 'woosuite_seo_batch_process' );
        }
    }

    public function process_batch() {
        if ( get_option( 'woosuite_seo_batch_stop_signal' ) ) {
            $this->stop_batch("Process stopped by user.");
            return;
        }

        $status = get_option( 'woosuite_seo_batch_status' );

        // Auto-Reset: If running but not updated in 2 minutes, assume zombie
        if ( isset($status['status']) && $status['status'] === 'running' && isset($status['last_updated']) ) {
             if ( (time() - $status['last_updated']) > 120 ) {
                 // Zombie detection logic is usually handled by the UI or a cron watchdog
                 // But here we can just ensure we update 'last_updated' immediately
             }
        }

        if ( ! $status || $status['status'] !== 'running' ) return;

        $start_time = microtime( true );
        $rewrite_titles = get_option( 'woosuite_seo_rewrite_titles', 'no' ) === 'yes';

        // Update heartbeat
        $status['last_updated'] = time();
        update_option( 'woosuite_seo_batch_status', $status );

        // Loop: Reduced time limit to 15s to be safe
        while ( ( microtime( true ) - $start_time ) < 15 ) {

            // Double check stop signal inside loop
            if ( get_option( 'woosuite_seo_batch_stop_signal' ) ) {
                 $this->stop_batch("Process stopped by user.");
                 break;
            }

            $ids = $this->get_next_batch_items( 1 );

            if ( empty( $ids ) ) {
                $status['status'] = 'complete';
                $status['message'] = 'Optimization Complete!';
                $status['processed'] = $status['total'];
                $status['last_updated'] = time();
                update_option( 'woosuite_seo_batch_status', $status );
                return;
            }

            $id = $ids[0];
            $post = get_post( $id );

            if ( ! $post ) {
                 update_post_meta( $id, '_woosuite_seo_failed', 1 );
                 continue;
            }

            // Process Item with robust error handling
            try {
                // Clear previous error if any
                delete_post_meta( $post->ID, '_woosuite_seo_last_error' );

                if ( $post->post_type === 'attachment' ) {
                    $this->process_image( $post );
                } else {
                    $this->process_text( $post, $rewrite_titles );
                }
            } catch ( Exception $e ) {
                // Critical failure on this item
                update_post_meta( $post->ID, '_woosuite_seo_failed', 1 );
                update_post_meta( $post->ID, '_woosuite_seo_last_error', substr( $e->getMessage(), 0, 250 ) );
                error_log( "WooSuite SEO Worker CRITICAL ERROR on ID {$post->ID}: " . $e->getMessage() );
            } catch ( Throwable $e ) {
                 // Catch fatal errors in PHP 7+
                update_post_meta( $post->ID, '_woosuite_seo_failed', 1 );
                update_post_meta( $post->ID, '_woosuite_seo_last_error', substr( $e->getMessage(), 0, 250 ) );
                error_log( "WooSuite SEO Worker FATAL ERROR on ID {$post->ID}: " . $e->getMessage() );
            }

            $status['processed']++;
            $status['last_updated'] = time();
            update_option( 'woosuite_seo_batch_status', $status );
        }

        // Schedule next run
        if ( ! get_option( 'woosuite_seo_batch_stop_signal' ) ) {
             wp_schedule_single_event( time(), 'woosuite_seo_batch_process' );
        }
    }

    private function stop_batch( $message = "Stopped" ) {
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

        if ( is_wp_error( $result ) || empty( $result ) ) {
            // Log error
            $err = is_wp_error($result) ? $result->get_error_message() : 'Empty Result';
            error_log("WooSuite SEO Error (ID: {$post->ID}): $err");

            update_post_meta( $post->ID, '_woosuite_seo_failed', 1 );
            update_post_meta( $post->ID, '_woosuite_seo_last_error', $err );
            return;
        }

        // Save
        $saved = false;
        if ( ! empty( $result['title'] ) ) {
            update_post_meta( $post->ID, '_woosuite_meta_title', sanitize_text_field( $result['title'] ) );
            $saved = true;
        }
        if ( ! empty( $result['description'] ) ) {
            $desc = sanitize_text_field( $result['description'] );
            update_post_meta( $post->ID, '_woosuite_meta_description', $desc );
            update_post_meta( $post->ID, '_yoast_wpseo_metadesc', $desc );
            update_post_meta( $post->ID, 'rank_math_description', $desc );
            $saved = true;
        }
        if ( ! empty( $result['llmSummary'] ) ) update_post_meta( $post->ID, '_woosuite_llm_summary', sanitize_textarea_field( $result['llmSummary'] ) );

        // Handle Title Rewrite
        if ( $rewrite_titles && ! empty( $result['simplifiedTitle'] ) ) {
            wp_update_post( array(
                'ID' => $post->ID,
                'post_title' => sanitize_text_field( $result['simplifiedTitle'] )
            ) );
        }

        // Anti-Loop Protection
        if ( ! $saved ) {
             update_post_meta( $post->ID, '_woosuite_seo_failed', 1 );
             update_post_meta( $post->ID, '_woosuite_seo_last_error', 'Generated data was empty.' );
        }
    }

    private function process_image( $post ) {
        $url = wp_get_attachment_url( $post->ID );
        if ( ! $url ) {
            update_post_meta( $post->ID, '_woosuite_seo_failed', 1 );
            update_post_meta( $post->ID, '_woosuite_seo_last_error', 'Missing attachment URL.' );
            return;
        }

        $result = $this->gemini->generate_image_seo( $url, basename( $url ) );

        if ( is_wp_error( $result ) || empty( $result ) ) {
             $err = is_wp_error($result) ? $result->get_error_message() : 'Empty Result';
             error_log("WooSuite Image SEO Error (ID: {$post->ID}): $err");

            update_post_meta( $post->ID, '_woosuite_seo_failed', 1 );
            update_post_meta( $post->ID, '_woosuite_seo_last_error', $err );
            return;
        }

        $saved = false;
        if ( ! empty( $result['altText'] ) ) {
            update_post_meta( $post->ID, '_wp_attachment_image_alt', sanitize_text_field( $result['altText'] ) );
            $saved = true;
        }
        if ( ! empty( $result['title'] ) ) {
            // Update attachment title
            wp_update_post( array(
                'ID' => $post->ID,
                'post_title' => sanitize_text_field( $result['title'] )
            ) );
            $saved = true;
        }

        // Anti-Loop Protection
        if ( ! $saved ) {
            update_post_meta( $post->ID, '_woosuite_seo_failed', 1 );
            update_post_meta( $post->ID, '_woosuite_seo_last_error', 'Generated image data was empty.' );
        }
    }

    private function get_next_batch_items( $limit ) {
        // Text
        $posts = get_posts( array(
            'post_type' => array( 'product', 'post', 'page' ),
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'orderby' => 'ID', // Consistent ordering
            'order' => 'ASC',
            'meta_query' => array(
                array( 'key' => '_woosuite_meta_description', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_seo_failed', 'compare' => 'NOT EXISTS' )
            )
        ) );
        if ( ! empty( $posts ) ) return $posts;

        // Images
        $images = get_posts( array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
            'meta_query' => array(
                array( 'key' => '_wp_attachment_image_alt', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_seo_failed', 'compare' => 'NOT EXISTS' )
            )
        ) );
        return $images;
    }

    private function get_total_unoptimized_count() {
        // Just sum the counts of the two queries
        $q1 = new WP_Query( array(
            'post_type' => array( 'product', 'post', 'page' ),
            'post_status' => 'publish',
            'fields' => 'ids',
            'meta_query' => array(
                array( 'key' => '_woosuite_meta_description', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_seo_failed', 'compare' => 'NOT EXISTS' )
            )
        ) );

        $q2 = new WP_Query( array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'fields' => 'ids',
            'meta_query' => array(
                array( 'key' => '_wp_attachment_image_alt', 'compare' => 'NOT EXISTS' ),
                array( 'key' => '_woosuite_seo_failed', 'compare' => 'NOT EXISTS' )
            )
        ) );

        return $q1->found_posts + $q2->found_posts;
    }
}
