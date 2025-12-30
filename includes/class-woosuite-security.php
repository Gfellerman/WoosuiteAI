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
        // Run Firewall early (init ensures pluggable functions like is_user_logged_in are loaded)
        add_action( 'init', array( $this, 'firewall_check' ), 1 );

        // Scheduled Scans
        add_action( 'woosuite_scheduled_scan', array( $this, 'perform_core_scan' ) );
        add_action( 'woosuite_daily_log_analysis', array( $this, 'perform_log_analysis' ) );

        // Login Protection
        if ( get_option( 'woosuite_login_protection_enabled', 'yes' ) === 'yes' ) {
            add_action( 'wp_login_failed', array( $this, 'log_failed_login' ) );
            add_filter( 'authenticate', array( $this, 'check_login_attempt' ), 1, 3 );
        }

        // Spam Protection
        if ( get_option( 'woosuite_spam_protection_enabled', 'yes' ) === 'yes' ) {
            add_filter( 'comment_form_default_fields', array( $this, 'add_honeypot_field' ) );
            add_filter( 'preprocess_comment', array( $this, 'check_spam_comment' ) );
        }
    }

    /**
     * The WAF (Web Application Firewall)
     * Inspects incoming requests for malicious patterns.
     */
    public function firewall_check() {
        // Skip for Cron Jobs (Loopback requests)
        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            return;
        }

        // Check if Firewall is enabled
        if ( get_option( 'woosuite_firewall_enabled', 'yes' ) !== 'yes' ) {
            return;
        }

        $ip = $this->get_client_ip();

        // Check if IP is Banned (Reputation Check)
        if ( $this->check_ip_reputation( $ip ) ) {
             wp_die(
                '<h1>Access Denied</h1><p>Your IP address has been temporarily blocked due to suspicious activity.</p>',
                'WooSuite Security',
                array( 'response' => 403 )
            );
            exit;
        }

        // Allow logged-in admins to do whatever (prevent locking yourself out)
        if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
            return;
        }

        $request_data = array_merge( $_GET, $_POST, $_COOKIE );
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';

        $simulation_mode = get_option( 'woosuite_firewall_simulation_mode', 'no' ) === 'yes';
        $block_sqli = get_option( 'woosuite_firewall_block_sqli', 'yes' ) === 'yes';
        $block_xss = get_option( 'woosuite_firewall_block_xss', 'yes' ) === 'yes';

        // 1. Check for basic SQL Injection patterns
        if ( $block_sqli ) {
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
                        // Increase violation count
                        $this->track_violation( $ip );
                        $this->block_request( 'SQL Injection Attempt', 'high', $simulation_mode );
                        if ( $simulation_mode ) break; // Log once per request in sim mode
                    }
                }
            }
        }

        // 2. Check for XSS (Cross Site Scripting)
        if ( $block_xss ) {
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
                        $this->track_violation( $ip );
                        $this->block_request( 'XSS Attempt', 'medium', $simulation_mode );
                        if ( $simulation_mode ) break;
                    }
                }
            }
        }

        // 3. Path Traversal (Always check if firewall enabled)
        if ( strpos( $request_uri, '../' ) !== false || strpos( $request_uri, '..\\' ) !== false ) {
            $this->track_violation( $ip );
            $this->block_request( 'Path Traversal', 'high', $simulation_mode );
        }
    }

    /**
     * Checks if the IP is currently banned.
     */
    private function check_ip_reputation( $ip ) {
        // Check for Ban Transient
        $ban_transient = 'woosuite_ban_' . md5( $ip );
        if ( get_transient( $ban_transient ) ) {
            return true;
        }
        return false;
    }

    /**
     * Track violations for IP Reputation logic.
     * "Three Strikes" (or 5) within a time window leads to a ban.
     */
    private function track_violation( $ip ) {
        $transient_name = 'woosuite_violations_' . md5( $ip );
        $violations = get_transient( $transient_name );

        if ( false === $violations ) {
            // First violation, start counter. 10 minute window.
            set_transient( $transient_name, 1, 10 * 60 );
        } else {
            $violations++;
            set_transient( $transient_name, $violations, 10 * 60 );
        }

        // Ban Logic: If > 5 violations in 10 mins -> Ban for 30 mins
        if ( $violations >= 5 ) {
             $ban_transient = 'woosuite_ban_' . md5( $ip );
             set_transient( $ban_transient, true, 30 * 60 );

             // Log the ban
             $this->log_threat( $ip, 'IP Reputation Ban (Too many violations)', 'critical', true );
        }
    }

    /**
     * Blocks the request and logs it.
     */
    private function block_request( $reason, $severity, $simulation_mode = false ) {
        $ip = $this->get_client_ip();

        if ( $simulation_mode ) {
            // Log as 'not blocked' but add [Simulated] to event
            $this->log_threat( $ip, $reason . ' [Simulated]', $severity, false );
            return; // Do not die
        }

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
     * Handle Failed Login Attempts
     */
    public function log_failed_login( $username ) {
        $ip = $this->get_client_ip();
        $transient_name = 'woosuite_login_attempts_' . md5( $ip );
        $attempts = get_transient( $transient_name );

        if ( false === $attempts ) {
            // First failure, start counter. 15 minute window.
            set_transient( $transient_name, 1, 15 * 60 );
        } else {
            $attempts++;
            set_transient( $transient_name, $attempts, 15 * 60 );
        }

        // Log the failure
        $this->log_threat( $ip, 'Failed Login Attempt (' . $username . ')', 'low', false );
    }

    /**
     * Pre-check Login Attempts (Brute Force Protection)
     */
    public function check_login_attempt( $user, $username, $password ) {
        $ip = $this->get_client_ip();
        $transient_name = 'woosuite_login_attempts_' . md5( $ip );
        $attempts = get_transient( $transient_name );
        $max_retries = (int) get_option( 'woosuite_login_max_retries', 3 );

        if ( $attempts && $attempts >= $max_retries ) {
             // Log the blocking event
             $this->log_threat( $ip, 'Login Lockout (Too many attempts)', 'high', true );

             return new WP_Error(
                 'woosuite_lockout',
                 '<strong>ERROR</strong>: Too many failed login attempts. Please try again in 15 minutes.'
             );
        }

        return $user;
    }

    /**
     * Add Honeypot field to comment form (Spam Protection)
     */
    public function add_honeypot_field( $fields ) {
        // Hidden field that humans won't see but bots might fill
        $fields['woosuite_check'] = '<p style="display:none;"><label>Leave this empty:</label><input type="text" name="woosuite_honeypot" value="" /></p>';
        return $fields;
    }

    /**
     * Check Comment for Spam (Honeypot + Links)
     */
    public function check_spam_comment( $commentdata ) {
        // 1. Check Honeypot
        if ( ! empty( $_POST['woosuite_honeypot'] ) ) {
            wp_die( 'Spam detected.' );
        }

        // 2. Check Link Limit
        $content = $commentdata['comment_content'];
        $link_count = preg_match_all( '/http(s)?:\/\//i', $content, $matches );

        if ( $link_count > 2 ) {
            // Mark as spam or pending
            // For now, we flag it as pending moderation if not already
            // Or strictly die? User wanted "Best spam protection".
            // Let's set it to '0' (pending) strictly, or spam.
            // Let's just die for now as requested "not to slowdown...".
            // Actually, "not to slowdown" -> blocking is fast.
            // But user might want real comments with links.
            // Better: mark as spam in DB.
            $commentdata['comment_approved'] = 'spam';
        }

        return $commentdata;
    }

    /**
     * Perform Core Integrity Scan
     * Uses WordPress built-in checksum verification.
     *
     * @param string $source 'manual' or 'auto'
     */
    public function perform_core_scan( $source = 'auto' ) {
        // If called by hook (action), the arg might be different or empty, so check type
        if ( ! is_string( $source ) ) {
            $source = 'auto';
        }

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
        update_option( 'woosuite_last_scan_source', $source );

        return array(
            'status' => 'complete',
            'issues_found' => count( $modified_files ),
            'details' => $modified_files,
            'source' => $source
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

    /**
     * Perform AI Analysis on Logs (Scheduled)
     */
    public function perform_log_analysis() {
        // Fetch recent High severity logs from last 24 hours
        global $wpdb;
        $logs = $wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE severity IN ('high', 'critical') AND created_at > NOW() - INTERVAL 1 DAY ORDER BY created_at DESC LIMIT 50" );

        if ( empty( $logs ) ) {
            delete_option( 'woosuite_security_alerts' ); // Clear alerts if safe
            return;
        }

        // Prepare Summary
        $summary = "Security Events (Last 24h):\n";
        foreach ( $logs as $l ) {
            $summary .= "[{$l->created_at}] IP: {$l->ip_address} - {$l->event} (Severity: {$l->severity})\n";
        }

        $groq = new WooSuite_Groq();
        $analysis = $groq->analyze_security_logs( $summary );

        if ( ! is_wp_error( $analysis ) && isset( $analysis['threatLevel'] ) ) {
            if ( in_array( $analysis['threatLevel'], array( 'Medium', 'Critical' ) ) ) {
                update_option( 'woosuite_security_alerts', $analysis );
            } else {
                delete_option( 'woosuite_security_alerts' );
            }
        }
    }
}
