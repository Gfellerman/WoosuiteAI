<?php
// Mock WordPress Environment

$wp_options = [];
function get_option($key, $default = false) {
    global $wp_options;
    return isset($wp_options[$key]) ? $wp_options[$key] : $default;
}
function update_option($key, $value) {
    global $wp_options;
    $wp_options[$key] = $value;
}
function is_user_logged_in() { return false; }
function current_time($type) { return date('Y-m-d H:i:s'); }
function esc_html($s) { return htmlspecialchars($s); }

// Mock WPDB
class MockWPDB {
    public $prefix = 'wp_';
    public $logs = [];
    public function insert($table, $data) {
        $this->logs[] = $data;
    }
}
$wpdb = new MockWPDB();

// Mock wp_die
class WPDieException extends Exception {}
function wp_die($message = '', $title = '', $args = []) {
    throw new WPDieException($message);
}

// Load the class (assuming we are running from root)
require_once __DIR__ . '/../includes/class-woosuite-security.php';

// --- TEST 1: WAF Enabled, Simulation OFF, SQLi Attack ---
echo "TEST 1: Blocking SQLi... ";
$wp_options = [
    'woosuite_firewall_enabled' => 'yes',
    'woosuite_firewall_simulation_mode' => 'no',
    'woosuite_firewall_block_sqli' => 'yes',
];
$_GET = ['q' => 'union select 1,2,3'];
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$security = new WooSuite_Security('woosuite-ai', '1.0.0');
try {
    $security->firewall_check();
    echo "FAILED (Should have blocked)\n";
} catch (WPDieException $e) {
    echo "PASSED (Blocked as expected)\n";
}

// --- TEST 2: WAF Enabled, Simulation ON, SQLi Attack ---
echo "TEST 2: Simulation Mode (SQLi)... ";
$wp_options['woosuite_firewall_simulation_mode'] = 'yes';
// clear logs
$wpdb->logs = [];
try {
    $security->firewall_check();
    // Should NOT throw exception
    if (count($wpdb->logs) > 0 && strpos($wpdb->logs[0]['event'], '[Simulated]') !== false) {
         echo "PASSED (Logged but not blocked)\n";
    } else {
         echo "FAILED (No log or wrong log found)\n";
         print_r($wpdb->logs);
    }
} catch (WPDieException $e) {
    echo "FAILED (Blocked but should be simulation)\n";
}

// --- TEST 3: WAF Enabled, SQLi Block Disabled, SQLi Attack ---
echo "TEST 3: Granular Toggle (SQLi Disabled)... ";
$wp_options['woosuite_firewall_simulation_mode'] = 'no';
$wp_options['woosuite_firewall_block_sqli'] = 'no';
$wpdb->logs = [];

try {
    $security->firewall_check();
    echo "PASSED (Allowed as expected)\n";
} catch (WPDieException $e) {
    echo "FAILED (Blocked but SQLi blocking was disabled)\n";
}
