<?php

class WooSuite_Security_Quarantine {

    private $quarantine_dir;
    private $log_file;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->quarantine_dir = $upload_dir['basedir'] . '/woosuite-quarantine/';
        $this->log_file = $this->quarantine_dir . 'index.json';

        if ( ! file_exists( $this->quarantine_dir ) ) {
            wp_mkdir_p( $this->quarantine_dir );
            // Secure the directory
            file_put_contents( $this->quarantine_dir . '.htaccess', "Order Deny,Allow\nDeny from all" );
            file_put_contents( $this->quarantine_dir . 'index.php', '<?php // Silence is golden' );
        }
    }

    /**
     * Move a file to quarantine
     */
    public function quarantine_file( $filepath ) {
        // Normalize path
        $filepath = wp_normalize_path( $filepath );
        $abspath = wp_normalize_path( ABSPATH );

        // Security check: ensure file is inside ABSPATH
        if ( strpos( $filepath, $abspath ) !== 0 ) {
            return new WP_Error( 'invalid_path', 'Cannot quarantine files outside WordPress root.' );
        }

        if ( ! file_exists( $filepath ) ) {
            return new WP_Error( 'file_not_found', 'File does not exist.' );
        }

        $id = uniqid();
        $filename = basename( $filepath );
        $dest = $this->quarantine_dir . $id . '_' . $filename;

        if ( rename( $filepath, $dest ) ) {
            // Log it
            $log = $this->get_log();
            $log[$id] = array(
                'id' => $id,
                'original_path' => $filepath, // Full path
                'relative_path' => str_replace( $abspath, '', $filepath ),
                'filename' => $filename,
                'quarantine_path' => $dest,
                'date' => current_time( 'mysql' ),
                'permissions' => fileperms( $dest ) // Store permissions if needed later
            );
            $this->save_log( $log );

            return $id;
        } else {
            return new WP_Error( 'move_failed', 'Failed to move file to quarantine.' );
        }
    }

    /**
     * Restore a file from quarantine
     */
    public function restore_file( $id ) {
        $log = $this->get_log();

        if ( ! isset( $log[$id] ) ) {
            return new WP_Error( 'invalid_id', 'Quarantine ID not found.' );
        }

        $entry = $log[$id];
        $source = $entry['quarantine_path'];
        $dest = $entry['original_path'];

        if ( ! file_exists( $source ) ) {
            // File might be manually deleted
            unset( $log[$id] );
            $this->save_log( $log );
            return new WP_Error( 'file_gone', 'Quarantined file no longer exists.' );
        }

        // Ensure directory exists
        $dir = dirname( $dest );
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }

        if ( rename( $source, $dest ) ) {
            // Remove from log
            unset( $log[$id] );
            $this->save_log( $log );
            return true;
        } else {
            return new WP_Error( 'restore_failed', 'Failed to move file back.' );
        }
    }

    /**
     * Delete a file permanently from quarantine
     */
    public function delete_file( $id ) {
        $log = $this->get_log();

        if ( ! isset( $log[$id] ) ) {
            return new WP_Error( 'invalid_id', 'Quarantine ID not found.' );
        }

        $entry = $log[$id];
        $file = $entry['quarantine_path'];

        if ( file_exists( $file ) ) {
            unlink( $file );
        }

        unset( $log[$id] );
        $this->save_log( $log );

        return true;
    }

    /**
     * Get all quarantined files
     */
    public function get_quarantined_files() {
        return array_values( $this->get_log() );
    }

    private function get_log() {
        if ( file_exists( $this->log_file ) ) {
            $content = file_get_contents( $this->log_file );
            $data = json_decode( $content, true );
            return is_array( $data ) ? $data : array();
        }
        return array();
    }

    private function save_log( $data ) {
        file_put_contents( $this->log_file, json_encode( $data, JSON_PRETTY_PRINT ) );
    }
}
