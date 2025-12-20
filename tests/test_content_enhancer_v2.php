<?php
// Test Content Enhancer V2 API Logic

require_once 'mock_wp.php';

// Extend Mock WP with WP_Query and Tax Query support (simplified)
class WP_Query {
    public $posts = [];
    public $found_posts = 0;
    public $max_num_pages = 1;

    public function __construct($args) {
        global $mock_db;
        $this->posts = [];
        foreach ($mock_db as $id => $post) {
            // Filter by Post Type
            if (isset($args['post_type']) && $post['post_type'] !== $args['post_type']) continue;

            // Filter by Tax Query (Category)
            if (isset($args['tax_query'])) {
                $tax_query = $args['tax_query'][0];
                $terms = $tax_query['terms'];
                $post_cats = isset($post['taxonomies']['product_cat']) ? $post['taxonomies']['product_cat'] : [];
                if (!array_intersect((array)$terms, $post_cats)) continue;
            }

            // Filter by Meta Query (Status)
            if (isset($args['meta_query'])) {
                // Simplified logic: Check for "enhanced" (OR logic)
                $matches = false;
                foreach ($args['meta_query'][0] as $mq) {
                    if (is_array($mq)) {
                         $key = $mq['key'];
                         $compare = $mq['compare'];
                         $exists = isset($post['meta'][$key]);

                         if ($compare === 'EXISTS' && $exists) $matches = true;
                         if ($compare === 'NOT EXISTS' && !$exists) $matches = true;
                    }
                }

                // Logic for AND/OR
                $relation = isset($args['meta_query'][0]['relation']) ? $args['meta_query'][0]['relation'] : 'OR';
                if ($relation === 'OR' && !$matches) continue;
                // Note: For AND, my loop logic above is insufficient, but for this test 'enhanced' uses OR and 'not_enhanced' uses AND.
                // Let's keep it simple for now.
            }

            $this->posts[] = (object)$post;
        }
        $this->found_posts = count($this->posts);
    }
}

// Mock Functions needed
function strip_tags($str) { return $str; } // Keep simple
function get_permalink($id) { return "http://site.com/?p=$id"; }
function wp_kses_post($str) { return $str; }
function delete_post_meta($id, $key) {
    global $mock_db;
    if (isset($mock_db[$id]['meta'][$key])) {
        unset($mock_db[$id]['meta'][$key]);
    }
}
function get_terms($args) {
    // Mock Categories
    return [
        (object)['term_id' => 10, 'name' => 'Electronics', 'count' => 5],
        (object)['term_id' => 11, 'name' => 'Fashion', 'count' => 3],
    ];
}
function is_wp_error($thing) { return false; }
function current_user_can($cap) { return true; }

// Helper to add post
function add_mock_post($id, $title, $content, $excerpt, $type = 'product', $cats = [], $meta = []) {
    global $mock_db;
    $mock_db[$id] = [
        'ID' => $id,
        'post_title' => $title,
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'post_type' => $type,
        'taxonomies' => ['product_cat' => $cats],
        'meta' => $meta
    ];
}

// Load Class
require_once '../includes/api/class-woosuite-api.php';

// --- SETUP ---
echo "Setting up Mock Data...\n";
add_mock_post(1, 'USB Drive', 'Fast USB 3.0', 'Short USB', 'product', [10]);
add_mock_post(2, 'T-Shirt', 'Cotton Shirt', 'Nice Shirt', 'product', [11]);
// Post 3 is Enhanced
add_mock_post(3, 'Laptop', 'Gaming Laptop', 'Fast Laptop', 'product', [10], [
    '_woosuite_proposed_description' => 'AI: Powerful Gaming Laptop'
]);

$api = new WooSuite_Api('woosuite', '1.0');

// --- TEST 1: Get Content Items (Basic) ---
echo "Test 1: Get Content Items (Check Fields)\n";
$req = new WP_REST_Request();
$res = $api->get_content_items($req);
$item1 = $res->data['items'][0];

if ($item1['description'] === 'Fast USB 3.0' && $item1['shortDescription'] === 'Short USB') {
    echo "PASS: Fields separated correctly.\n";
} else {
    echo "FAIL: Fields incorrect.\n";
    print_r($item1);
}

// --- TEST 2: Filter by Category (Electronics id=10) ---
echo "Test 2: Filter by Category\n";
$req->set_param('category', 10);
$res = $api->get_content_items($req);
$ids = array_map(function($i) { return $i['id']; }, $res->data['items']);
if (in_array(1, $ids) && in_array(3, $ids) && !in_array(2, $ids)) {
    echo "PASS: Category filter worked.\n";
} else {
    echo "FAIL: Category filter failed. IDs: " . implode(',', $ids) . "\n";
}

// --- TEST 3: Filter by Status (Enhanced) ---
echo "Test 3: Filter by Status (Enhanced)\n";
$req = new WP_REST_Request(); // Reset
$req->set_param('status', 'enhanced');
$res = $api->get_content_items($req);
$ids = array_map(function($i) { return $i['id']; }, $res->data['items']);
if (count($ids) === 1 && $ids[0] == 3) {
    echo "PASS: Status 'enhanced' filter worked.\n";
} else {
    echo "FAIL: Status filter failed.\n";
}

// --- TEST 4: Bulk Apply ---
echo "Test 4: Bulk Apply\n";
$req = new WP_REST_Request();
$req->set_json_params(['ids' => [3], 'field' => 'description']);
$res = $api->bulk_apply_content_rewrite($req);

if ($res->data['success'] && $res->data['applied'] === 1) {
    echo "PASS: Bulk apply reported success.\n";

    // Verify Update in DB
    $post3 = get_post(3);
    if ($post3->post_content === 'AI: Powerful Gaming Laptop') {
        echo "PASS: Content updated in DB.\n";
    } else {
        echo "FAIL: Content not updated. Got: " . $post3->post_content . "\n";
    }

    // Verify Meta Deleted
    $meta = get_post_meta(3, '_woosuite_proposed_description');
    if (empty($meta)) {
        echo "PASS: Proposed meta deleted.\n";
    } else {
        echo "FAIL: Meta not deleted.\n";
    }

} else {
    echo "FAIL: Bulk apply API returned error.\n";
    print_r($res);
}
