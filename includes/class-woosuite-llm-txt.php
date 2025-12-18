<?php

class WooSuite_LLM_Txt {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function init() {
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        add_action( 'template_redirect', array( $this, 'render_llms_txt' ) );
    }

    public function add_query_vars( $vars ) {
        $vars[] = 'woosuite_llms_txt';
        return $vars;
    }

    public function add_rewrite_rules() {
        add_rewrite_rule( '^llms\.txt$', 'index.php?woosuite_llms_txt=1', 'top' );
    }

    public function render_llms_txt() {
        if ( get_query_var( 'woosuite_llms_txt' ) ) {
            header( 'Content-Type: text/plain; charset=utf-8' );

            // Site Info
            echo "# " . get_bloginfo( 'name' ) . "\n";
            echo get_bloginfo( 'description' ) . "\n\n";

            echo "## About\n";
            echo "This file provides context for AI agents crawling " . get_site_url() . ".\n\n";

            // Sitemap Link
            echo "## Sitemap\n";
            echo get_site_url() . "/sitemap.xml\n\n";

            // Top Products (if WooCommerce)
            if ( class_exists( 'WooCommerce' ) ) {
                echo "## Top Products\n";
                $args = array(
                    'limit' => 20,
                    'orderby' => 'popularity', // Best selling
                    'order' => 'DESC',
                    'status' => 'publish',
                );
                $products = wc_get_products( $args );
                foreach ( $products as $product ) {
                    echo "- " . $product->get_name() . ": " . $product->get_permalink() . "\n";
                    $summary = get_post_meta( $product->get_id(), '_woosuite_llm_summary', true );
                    if ( $summary ) {
                        // Clean summary of newlines to keep format clean
                        $summary = str_replace( array("\r", "\n"), " ", $summary );
                        echo "  Summary: " . $summary . "\n";
                    } else {
                         // Fallback to short description
                         $desc = strip_tags( $product->get_short_description() ?: $product->get_description() );
                         $desc = substr( $desc, 0, 150 ) . '...';
                         echo "  Description: " . str_replace( array("\r", "\n"), " ", $desc ) . "\n";
                    }
                }
                echo "\n";
            }

            // Recent Posts
            echo "## Recent Posts\n";
            $recent_posts = get_posts( array( 'numberposts' => 10, 'post_status' => 'publish' ) );
            foreach ( $recent_posts as $post ) {
                echo "- " . $post->post_title . ": " . get_permalink( $post->ID ) . "\n";
                $summary = get_post_meta( $post->ID, '_woosuite_llm_summary', true );
                if ( $summary ) {
                    $summary = str_replace( array("\r", "\n"), " ", $summary );
                    echo "  Summary: " . $summary . "\n";
                } else {
                     $excerpt = strip_tags( $post->post_excerpt ?: $post->post_content );
                     $excerpt = substr( $excerpt, 0, 150 ) . '...';
                     echo "  Excerpt: " . str_replace( array("\r", "\n"), " ", $excerpt ) . "\n";
                }
            }

            exit;
        }
    }
}
