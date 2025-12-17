<?php

class WooSuite_Security {

    private $plugin_name;
    private $version;
    private $table_name;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'woosuite_security_logs';
    }

    public function init() {
        // Run Firewall early
        add_action( 'plugins_loaded', array( $this, 'firewall_check' ), 1 );
    }

    /**
     * The WAF (Web Application Firewall)
     * Inspects incoming requests for malicious patterns.
     */
    public function firewall_check() {
        // Check if Firewall is enabled
        if ( get_option( 'woosuite_firewall_enabled', 'yes' ) !== 'yes' ) {
            return;
        }

        // Allow logged-in admins to do whatever (prevent locking yourself out)
        if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
            return;
        }

        $request_data = array_merge( $_GET, $_POST, $_COOKIE );
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';

        // 1. Check for basic SQL Injection patterns
        $sqli_patterns = array(
            'union select',
            'union all select',
            'drop table',
            'information_schema',
            'or 1=1',
        );

        foreach ( $request_data as $key => $value ) {
            if ( is_array( $value ) ) continue; // Skip arrays for now
            $value_lower = strtolower( urldecode( $value ) );
            foreach ( $sqli_patterns as $pattern ) {
                if ( strpos( $value_lower, $pattern ) !== false ) {
                    $this->block_request( 'SQL Injection Attempt', 'high' );
                }
            }
        }

        // 2. Check for XSS (Cross Site Scripting)
        $xss_patterns = array(
            '<script>',
            'javascript:',
            'onload=',
            'onerror=',
        );

        foreach ( $request_data as $key => $value ) {
            if ( is_array( $value ) ) continue;
            $value_lower = strtolower( urldecode( $value ) );
            foreach ( $xss_patterns as $pattern ) {
                if ( strpos( $value_lower, $pattern ) !== false ) {
                    $this->block_request( 'XSS Attempt', 'medium' );
                }
            }
        }

        // 3. Path Traversal
        if ( strpos( $request_uri, '../' ) !== false || strpos( $request_uri, '..\\' ) !== false ) {
            $this->block_request( 'Path Traversal', 'high' );
        }
    }

    /**
     * Blocks the request and logs it.
     */
    private function block_request( $reason, $severity ) {
        $ip = $this->get_client_ip();
        $this->log_threat( $ip, $reason, $severity, true );

        // Update total blocked count
        $count = (int) get_option( 'woosuite_threats_blocked_count', 0 );
        update_option( 'woosuite_threats_blocked_count', $count + 1 );

        wp_die(
            '<h1>Access Denied</h1><p>Your request was blocked by WooSuite Firewall.</p><p>Reason: ' . esc_html( $reason ) . '</p>',
            'WooSuite Security',
            array( 'response' => 403 )
        );
        exit;
    }

    /**
     * Get Client IP (handling proxies/Cloudflare)
     */
    private function get_client_ip() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Log threat to database
     */
    public function log_threat( $ip, $event, $severity, $blocked = true ) {
        global $wpdb;
        $wpdb->insert(
            $this->table_name,
            array(
                'event' => $event,
                'ip_address' => $ip,
                'severity' => $severity,
                'blocked' => $blocked ? 1 : 0,
                'created_at' => current_time( 'mysql' )
            )
        );
    }

    /**
     * Perform Core Integrity Scan
     * Uses WordPress built-in checksum verification.
     */
    public function perform_core_scan() {
        if ( ! function_exists( 'get_core_checksums' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        if ( ! function_exists( 'get_home_path' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $checksums = get_core_checksums( $GLOBALS['wp_version'], 'en_US' );

        if ( ! is_array( $checksums ) ) {
            return array( 'status' => 'error', 'message' => 'Could not fetch checksums.' );
        }

        $modified_files = array();

        foreach ( $checksums as $file => $checksum ) {
            $filepath = ABSPATH . $file;
            if ( ! file_exists( $filepath ) ) {
                $modified_files[] = array( 'file' => $file, 'status' => 'missing' );
                continue;
            }

            if ( md5_file( $filepath ) !== $checksum ) {
                 $modified_files[] = array( 'file' => $file, 'status' => 'modified' );
            }
        }

        // Save result to transient or option
        update_option( 'woosuite_last_scan_results', $modified_files );
        update_option( 'woosuite_last_scan_time', current_time( 'mysql' ) );

        return array(
            'status' => 'complete',
            'issues_found' => count( $modified_files ),
            'details' => $modified_files
        );
    }

    /**
     * Fetch logs for the API
     */
    public function get_logs( $limit = 20, $offset = 0 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ) );
    }
}
