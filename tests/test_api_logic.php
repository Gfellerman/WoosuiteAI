<?php
require_once 'mock_wp.php';

// Mock Constants
define('WOOSUITE_AI_VERSION', '1.0.0');

// Load API Class
require_once '../includes/api/class-woosuite-api.php';

// Setup Data
global $mock_db;
$mock_db[123] = [
    'ID' => 123,
    'post_title' => 'Original Title',
    'meta' => []
];

$api = new WooSuite_Api('woosuite-ai', '1.0.0');

echo "Test 1: Update Meta Title and Description\n";
$request = new WP_REST_Request();
$request->set_param('id', 123);
$request->set_json_params([
    'metaTitle' => 'New SEO Title',
    'metaDescription' => 'New Description'
]);

$response = $api->update_content_item($request);

if ($mock_db[123]['meta']['_woosuite_meta_title'] === 'New SEO Title') {
    echo "PASS: Meta Title updated.\n";
} else {
    echo "FAIL: Meta Title not updated.\n";
}

if ($mock_db[123]['meta']['_woosuite_meta_description'] === 'New Description') {
    echo "PASS: Meta Description updated.\n";
} else {
    echo "FAIL: Meta Description not updated.\n";
}

echo "\nTest 2: Update Image Alt Text and Title\n";
// Reset
$mock_db[999] = [
    'ID' => 999,
    'post_title' => 'Image 1',
    'meta' => []
];
$request = new WP_REST_Request();
$request->set_param('id', 999);
$request->set_json_params([
    'altText' => 'Alt Text',
    'title' => 'New Image Title'
]);

$response = $api->update_content_item($request);

if ($mock_db[999]['meta']['_wp_attachment_image_alt'] === 'Alt Text') {
    echo "PASS: Alt Text updated.\n";
} else {
    echo "FAIL: Alt Text not updated.\n";
}

if ($mock_db[999]['post_title'] === 'New Image Title') {
    echo "PASS: Post Title updated.\n";
} else {
    echo "FAIL: Post Title not updated. Got: " . $mock_db[999]['post_title'] . "\n";
}
