<?php

class WooSuite_Security_Scanner {

    private $scan_patterns = array(
        'eval\s*\(' => 'Potential Code Execution (eval)',
        'gzinflate\s*\(' => 'Obfuscated Code (gzinflate)',
        'shell_exec\s*\(' => 'System Command Execution',
        'system\s*\(' => 'System Command Execution',
        'passthru\s*\(' => 'System Command Execution',
        'exec\s*\(' => 'System Command Execution',
        'base64_decode\s*\(' => 'Obfuscated Code (base64_decode)',
    );

    public function __construct() {
        add_action( 'woosuite_security_deep_scan_process', array( $this, 'process_batch' ) );
    }

    /**
     * Start the Deep Scan
     */
    public function start_scan() {
        // Build queue: All folders in plugins and themes
        $queue = array();

        $plugin_dir = WP_PLUGIN_DIR;
        // Handle themes: get_theme_root() returns the path to current theme's root, usually wp-content/themes
        $theme_dir = get_theme_root();

        // Add Plugins
        $plugins = glob( $plugin_dir . '/*' , GLOB_ONLYDIR );
        if ( $plugins ) {
            foreach ( $plugins as $p ) {
                $queue[] = $p;
            }
        }

        // Add Themes
        $themes = glob( $theme_dir . '/*' , GLOB_ONLYDIR );
        if ( $themes ) {
            foreach ( $themes as $t ) {
                $queue[] = $t;
            }
        }

        // Initial Status
        update_option( 'woosuite_security_scan_status', array(
            'status' => 'running',
            'total_folders' => count( $queue ),
            'processed_folders' => 0,
            'current_folder' => 'Initializing...',
            'found_issues' => 0,
            'start_time' => current_time( 'mysql' ),
            'message' => 'Initializing scan...'
        ));

        update_option( 'woosuite_security_scan_queue', $queue );
        update_option( 'woosuite_security_scan_results', array() ); // Clear previous results

        // Schedule First Batch
        if ( ! wp_next_scheduled( 'woosuite_security_deep_scan_process' ) ) {
            wp_schedule_single_event( time(), 'woosuite_security_deep_scan_process' );
        }

        return count( $queue );
    }

    /**
     * Process one batch (one directory from queue)
     */
    public function process_batch() {
        $status = get_option( 'woosuite_security_scan_status' );
        $queue = get_option( 'woosuite_security_scan_queue' );
        $results = get_option( 'woosuite_security_scan_results', array() );

        // Validation
        if ( ! $status || $status['status'] !== 'running' ) {
            return;
        }

        if ( empty( $queue ) ) {
            // Done!
            $status['status'] = 'complete';
            $status['message'] = 'Scan Complete.';
            $status['current_folder'] = '';
            // Save final time?
            update_option( 'woosuite_security_scan_status', $status );
            // Also update the main 'last scan' option
            update_option( 'woosuite_last_scan_time', current_time( 'mysql' ) );
            update_option( 'woosuite_last_scan_source', 'deep_scan' );
            return;
        }

        // Pop one folder
        $folder = array_shift( $queue );

        $status['current_folder'] = basename( $folder );
        $status['message'] = "Scanning " . basename( $folder ) . "...";
        update_option( 'woosuite_security_scan_status', $status );

        // Scan it
        $this->scan_directory( $folder, $results );

        // Update Status
        $status['processed_folders']++;
        $status['found_issues'] = count( $results );
        update_option( 'woosuite_security_scan_status', $status );

        // Save Queue and Results
        update_option( 'woosuite_security_scan_queue', $queue );
        update_option( 'woosuite_security_scan_results', $results );

        // Chain next batch immediately
        wp_schedule_single_event( time(), 'woosuite_security_deep_scan_process' );
    }

    private function scan_directory( $dir, &$results ) {
        if ( ! is_dir( $dir ) ) return;

        try {
            $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) );

            foreach ( $iterator as $file ) {
                // Only scan PHP files
                if ( $file->isFile() && $file->getExtension() === 'php' ) {
                    // Skip very large files (> 2MB)
                    if ( $file->getSize() > 2 * 1024 * 1024 ) continue;

                    $this->scan_file( $file->getPathname(), $results );
                }
            }
        } catch ( Exception $e ) {
            error_log( "WooSuite Scan Error in $dir: " . $e->getMessage() );
        }
    }

    private function scan_file( $filepath, &$results ) {
        // Read file
        $content = file_get_contents( $filepath );
        if ( ! $content ) return;

        foreach ( $this->scan_patterns as $pattern => $name ) {
            if ( preg_match( '/' . $pattern . '/i', $content ) ) {
                // Found a match
                $rel_path = str_replace( ABSPATH, '', $filepath );

                $results[] = array(
                    'file' => $rel_path,
                    'issue' => $name,
                    'severity' => 'high', // All patterns are high risk
                    'date' => current_time( 'mysql' )
                );

                // One match per file is enough to flag it
                break;
            }
        }
    }
}
