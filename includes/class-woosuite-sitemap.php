<?php

class WooSuite_Sitemap {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function init() {
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $this, 'render_sitemap' ) );
        add_filter( 'robots_txt', array( $this, 'add_to_robots' ), 100 );
    }

    public function add_rewrite_rules() {
        add_rewrite_rule( '^sitemap\.xml$', 'index.php?woosuite_sitemap=1', 'top' );
    }

    public function add_query_vars( $vars ) {
        $vars[] = 'woosuite_sitemap';
        return $vars;
    }

    public function render_sitemap() {
        if ( get_query_var( 'woosuite_sitemap' ) ) {
            // Check if enabled
            if ( get_option( 'woosuite_sitemap_enabled', 'yes' ) !== 'yes' ) {
                return;
            }

            header( 'Content-Type: application/xml; charset=utf-8' );
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

            // Home
            echo '<url><loc>' . home_url( '/' ) . '</loc><changefreq>daily</changefreq><priority>1.0</priority></url>';

            // Posts & Pages & Products
            $post_types = array( 'post', 'page' );
            if ( class_exists( 'WooCommerce' ) ) {
                $post_types[] = 'product';
            }

            $posts = get_posts( array(
                'numberposts' => -1,
                'post_type' => $post_types,
                'post_status' => 'publish',
                'orderby' => 'modified',
                'order' => 'DESC'
            ) );

            foreach ( $posts as $post ) {
                $last_mod = get_the_modified_date( 'c', $post );
                echo '<url>';
                echo '<loc>' . get_permalink( $post ) . '</loc>';
                echo '<lastmod>' . $last_mod . '</lastmod>';
                echo '<changefreq>weekly</changefreq>';
                echo '<priority>0.8</priority>';

                // Add Featured Image
                $thumb_id = get_post_thumbnail_id( $post->ID );
                if ( $thumb_id ) {
                    $img_url = wp_get_attachment_url( $thumb_id );
                    if ( $img_url ) {
                        echo '<image:image>';
                        echo '<image:loc>' . esc_url( $img_url ) . '</image:loc>';
                        // Add Title as caption if available
                        $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
                        if ($alt) {
                             echo '<image:title>' . esc_html($alt) . '</image:title>';
                        }
                        echo '</image:image>';
                    }
                }

                echo '</url>';
            }

            echo '</urlset>';
            exit;
        }
    }

    public function add_to_robots( $output ) {
        if ( get_option( 'woosuite_sitemap_enabled', 'yes' ) === 'yes' ) {
            $output .= "\nSitemap: " . home_url( '/sitemap.xml' );
        }
        return $output;
    }
}
