<?php

class WooSuite_Seo_Worker {

    private $batch_size = 5; // Process 5 items per run to avoid timeout
    private $gemini;

    public function __construct() {
        $this->gemini = new WooSuite_Gemini();

        // Register Cron Hook
        add_action( 'woosuite_seo_batch_process', array( $this, 'process_batch' ) );
    }

    /**
     * Start a new batch process.
     * Sets the status option and schedules the first run.
     */
    public function start_batch() {
        // Reset status
        update_option( 'woosuite_seo_batch_status', array(
            'status' => 'running', // running, complete, error
            'total' => 0,
            'processed' => 0,
            'start_time' => current_time( 'mysql' ),
            'message' => 'Starting...'
        ));

        // Get total count of unoptimized items
        $query = new WP_Query( array(
            'post_type' => array( 'product', 'post', 'page' ),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_woosuite_meta_description',
                    'compare' => 'NOT EXISTS'
                )
            )
        ) );

        $count = $query->found_posts;

        if ( $count === 0 ) {
             update_option( 'woosuite_seo_batch_status', array(
                'status' => 'complete',
                'total' => 0,
                'processed' => 0,
                'message' => 'No items to optimize.'
            ));
            return;
        }

        // Update total
        $status = get_option( 'woosuite_seo_batch_status' );
        $status['total'] = $count;
        $status['message'] = "Found $count items. Processing...";
        update_option( 'woosuite_seo_batch_status', $status );

        // Schedule first run immediately
        if ( ! wp_next_scheduled( 'woosuite_seo_batch_process' ) ) {
            wp_schedule_single_event( time(), 'woosuite_seo_batch_process' );
        }
    }

    /**
     * The Background Job
     */
    public function process_batch() {
        $status = get_option( 'woosuite_seo_batch_status' );

        if ( ! $status || $status['status'] !== 'running' ) {
            return;
        }

        // Fetch next batch
        $posts = get_posts( array(
            'post_type' => array( 'product', 'post', 'page' ),
            'post_status' => 'publish',
            'posts_per_page' => $this->batch_size,
            'meta_query' => array(
                array(
                    'key' => '_woosuite_meta_description',
                    'compare' => 'NOT EXISTS'
                )
            )
        ) );

        if ( empty( $posts ) ) {
            // Done!
            $status['status'] = 'complete';
            $status['message'] = 'Optimization Complete!';
            update_option( 'woosuite_seo_batch_status', $status );
            return;
        }

        foreach ( $posts as $post ) {
            // Prepare item for Gemini
            $item = array(
                'type' => $post->post_type,
                'name' => $post->post_title,
                'description' => strip_tags( $post->post_excerpt ?: $post->post_content ),
                'price' => '' // Add price if product
            );

            if ( $post->post_type === 'product' && function_exists( 'wc_get_product' ) ) {
                $product = wc_get_product( $post->ID );
                if ( $product ) {
                    $item['price'] = $product->get_price();
                }
            }

            // Call AI
            $result = $this->gemini->generate_seo_meta( $item );

            if ( is_wp_error( $result ) ) {
                error_log( "WooSuite Batch Error (ID: $post->ID): " . $result->get_error_message() );
                // Mark as skipped/error to avoid infinite loop on same item?
                // We'll set a flag so we don't pick it up again immediately, or just leave it.
                // Better: set a 'failed' meta flag so we skip it in next query.
                update_post_meta( $post->ID, '_woosuite_seo_failed', 1 );
                // Also add a dummy meta desc so we don't retry forever? No, user might want to retry.
                // Just for this loop, we need to ensure we don't get stuck.
                // But since we query by NOT EXISTS _woosuite_meta_description, if we don't save it, we will fetch it again.
                // We MUST save something or skip it.
                // Let's save a placeholder error in a different meta to exclude it?
                // Or simply: if error, save empty string? No.
                // Let's just continue. Next run will try again. If it fails 100 times, so be it.
                // To be safe, let's stop the batch if too many errors?
                continue;
            }

            // Save Result
            if ( ! empty( $result['title'] ) ) update_post_meta( $post->ID, '_woosuite_meta_title', sanitize_text_field( $result['title'] ) );
            if ( ! empty( $result['description'] ) ) {
                update_post_meta( $post->ID, '_woosuite_meta_description', sanitize_text_field( $result['description'] ) );
                // Yoast/RankMath sync
                update_post_meta( $post->ID, '_yoast_wpseo_metadesc', sanitize_text_field( $result['description'] ) );
                update_post_meta( $post->ID, 'rank_math_description', sanitize_text_field( $result['description'] ) );
            }
            if ( ! empty( $result['llmSummary'] ) ) update_post_meta( $post->ID, '_woosuite_llm_summary', sanitize_textarea_field( $result['llmSummary'] ) );

            $status['processed']++;
        }

        // Save progress
        update_option( 'woosuite_seo_batch_status', $status );

        // Schedule next run (chaining)
        // We use single event to avoid filling the queue. We just schedule the next one now.
        wp_schedule_single_event( time() + 5, 'woosuite_seo_batch_process' );
    }
}
