<?php

class WooSuite_Frontend {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function init() {
        // Meta Tags
        add_action( 'wp_head', array( $this, 'output_meta_tags' ), 1 );

        // Title Tag Override
        add_filter( 'pre_get_document_title', array( $this, 'override_document_title' ), 15 );

        // Remove existing description if possible (to avoid duplicates with themes)
        remove_action( 'wp_head', 'noindex', 1 );
    }

    public function override_document_title( $title ) {
        if ( is_admin() ) return $title;

        $post_id = get_the_ID();
        if ( ! $post_id ) return $title;

        $custom_title = get_post_meta( $post_id, '_woosuite_meta_title', true );
        if ( ! empty( $custom_title ) ) {
            return $custom_title;
        }

        return $title;
    }

    public function output_meta_tags() {
        if ( is_admin() ) return;

        $post_id = get_the_ID();
        if ( ! $post_id ) return;

        $desc = get_post_meta( $post_id, '_woosuite_meta_description', true );

        if ( ! empty( $desc ) ) {
            echo '<meta name="description" content="' . esc_attr( $desc ) . '" />' . "\n";
            // Output OG tags as well for social sharing/verification tools
            echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
        }

        $title = get_post_meta( $post_id, '_woosuite_meta_title', true );
        if ( ! empty( $title ) ) {
             echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
        }

        // Output Generator tag
        echo '<meta name="generator" content="WooSuite AI ' . esc_attr( $this->version ) . '" />' . "\n";
    }
}
