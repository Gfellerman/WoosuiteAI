#!/bin/bash
# test_security_scanner.sh

# Mock WordPress environment
cat > mock_wp_scanner.php <<EOF
<?php
define('ABSPATH', __DIR__ . '/verification/malware_test/');
define('WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins');
define('WP_CONTENT_DIR', ABSPATH . 'wp-content');

// Mock WP Functions
function get_theme_root() { return ABSPATH . 'wp-content/themes'; }
function get_option(\$key, \$default = false) {
    global \$options;
    return isset(\$options[\$key]) ? \$options[\$key] : \$default;
}
function update_option(\$key, \$val) {
    global \$options;
    \$options[\$key] = \$val;
    // echo "Updated \$key\n";
}
function wp_next_scheduled(\$hook) { return false; }
function wp_schedule_single_event(\$time, \$hook) {}
function current_time(\$type) { return date('Y-m-d H:i:s'); }
function is_wp_error(\$thing) { return false; }
function wp_mkdir_p(\$dir) { if (!is_dir(\$dir)) mkdir(\$dir, 0755, true); }
function wp_upload_dir() { return ['basedir' => ABSPATH . 'wp-content/uploads']; }

// Mock Groq
class WooSuite_Groq {
    public function analyze_security_threat(\$snippet, \$filename) {
        // Mock AI Response based on content
        if (strpos(\$snippet, 'eval') !== false) {
             return [
                 'verdict' => 'Malicious',
                 'confidence' => 'High',
                 'explanation' => 'Eval is dangerous.'
             ];
        }
        return ['verdict' => 'Safe'];
    }
}

// Load Class
require_once 'includes/class-woosuite-security-scanner.php';

// Run Test
\$scanner = new WooSuite_Security_Scanner();
echo "Starting Scan...\n";
\$count = \$scanner->start_scan();
echo "Queue Count: \$count\n";

// Process Batch
\$scanner->process_batch();

// Check Results
\$results = get_option('woosuite_security_scan_results');
if (!empty(\$results)) {
    foreach (\$results as \$r) {
        echo "Found: " . \$r['file'] . " [" . \$r['ai_verdict'] . "]\n";
    }
} else {
    echo "No issues found.\n";
}

EOF

php mock_wp_scanner.php
rm mock_wp_scanner.php
