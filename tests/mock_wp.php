<?php
// Mock WordPress environment

$mock_db = [];

function sanitize_text_field($str) {
    return trim(strip_tags($str));
}

function sanitize_textarea_field($str) {
    return trim(strip_tags($str));
}

function get_post($id) {
    global $mock_db;
    return isset($mock_db[$id]) ? (object)$mock_db[$id] : null;
}

function update_post_meta($id, $key, $value) {
    global $mock_db;
    if (!isset($mock_db[$id])) return false;
    $mock_db[$id]['meta'][$key] = $value;
    return true;
}

function get_post_meta($id, $key, $single = false) {
    global $mock_db;
    if (!isset($mock_db[$id])) return '';
    return isset($mock_db[$id]['meta'][$key]) ? $mock_db[$id]['meta'][$key] : '';
}

function wp_update_post($args) {
    global $mock_db;
    $id = $args['ID'];
    if (isset($args['post_title'])) {
        $mock_db[$id]['post_title'] = $args['post_title'];
    }
}

class WP_REST_Response {
    public $data;
    public $status;
    public function __construct($data, $status) {
        $this->data = $data;
        $this->status = $status;
    }
}

class WP_REST_Request {
    private $params;
    private $body_params;

    public function set_param($key, $val) {
        $this->params[$key] = $val;
    }
    public function set_json_params($params) {
        $this->body_params = $params;
    }
    public function get_param($key) {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }
    public function get_json_params() {
        return $this->body_params;
    }
}

function register_rest_route($ns, $route, $args) {}
function add_action($tag, $callback) {}
function plugin_dir_path($file) { return __DIR__ . '/../'; }
function plugin_dir_url($file) { return 'http://example.com/'; }
