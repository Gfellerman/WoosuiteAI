<?php
// Mock WP Environment
class WP_REST_Response {
    public function __construct($data, $status) {
        print_r($data);
    }
}
class WP_Error {}
function update_option($key, $value) { echo "Updated option $key\n"; }
function get_option($key, $default) { return []; }
function delete_post_meta($id, $key) { echo "Deleted meta $key for post $id\n"; }

// Mock WPDB
class MockWPDB {
    public $postmeta = 'wp_postmeta';
    public function query($query) {
        echo "Executed SQL: $query\n";
    }
}
$wpdb = new MockWPDB();

require_once 'includes/api/class-woosuite-api.php';

echo "Testing Reset Batch:\n";
$api = new WooSuite_Api('woosuite', '1.0');
$api->reset_seo_batch(null);
