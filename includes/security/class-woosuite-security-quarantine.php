<?php

class WooSuite_Security_Quarantine {

    private $quarantine_dir;
    private $option_key = 'woosuite_security_quarantine_index';

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->quarantine_dir = $upload_dir['basedir'] . '/woosuite-quarantine';
        $this->init_quarantine_dir();
    }

    private function init_quarantine_dir() {
        if ( ! file_exists( $this->quarantine_dir ) ) {
            wp_mkdir_p( $this->quarantine_dir );
        }

        // Protect directory
        $htaccess = $this->quarantine_dir . '/.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            file_put_contents( $htaccess, "Order deny,allow\nDeny from all" );
        }

        $index = $this->quarantine_dir . '/index.php';
        if ( ! file_exists( $index ) ) {
            file_put_contents( $index, "<?php // Silence is golden." );
        }
    }

    public function get_quarantined_files() {
        $files = get_option( $this->option_key, array() );
        if ( ! is_array( $files ) ) $files = array();

        // Validate if files actually exist on disk, clean up DB if not
        $valid_files = array();
        $updated = false;
        foreach ( $files as $file ) {
            if ( file_exists( $this->quarantine_dir . '/' . $file['stored_name'] ) ) {
                $valid_files[] = $file;
            } else {
                $updated = true;
            }
        }

        if ( $updated ) {
            update_option( $this->option_key, $valid_files );
        }

        return array_reverse( $valid_files );
    }

    public function quarantine_file( $filepath ) {
        // Handle relative paths (from Deep Scan results)
        if ( strpos( $filepath, ABSPATH ) === false ) {
            $filepath = ABSPATH . $filepath;
        }

        // Sanity Check
        if ( ! file_exists( $filepath ) ) {
            return new WP_Error( 'file_not_found', 'File does not exist: ' . $filepath );
        }

        // Security: Prevent quarantining critical WP files (wp-config.php, etc.)
        if ( $filepath === ABSPATH . 'wp-config.php' || $filepath === ABSPATH . 'index.php' ) {
            return new WP_Error( 'forbidden', 'Cannot quarantine critical system files.' );
        }

        $filename = basename( $filepath );
        // Create a unique stored name to prevent collisions
        $stored_name = time() . '_' . md5( $filepath ) . '.php.suspected';
        // We append .suspected to prevent execution if .htaccess fails

        $destination = $this->quarantine_dir . '/' . $stored_name;

        if ( ! rename( $filepath, $destination ) ) {
            return new WP_Error( 'move_failed', 'Could not move file. Check permissions.' );
        }

        // Record Metadata
        $record = array(
            'id' => md5( $stored_name ),
            'filename' => $filename,
            'stored_name' => $stored_name,
            'original_path' => $filepath,
            'relative_path' => str_replace( ABSPATH, '', $filepath ),
            'date' => current_time( 'mysql' ),
            'timestamp' => time()
        );

        $index = get_option( $this->option_key, array() );
        $index[] = $record;
        update_option( $this->option_key, $index );

        return true;
    }

    public function restore_file( $id ) {
        $index = get_option( $this->option_key, array() );
        $file_record = null;
        $key_index = -1;

        foreach ( $index as $k => $item ) {
            if ( $item['id'] === $id ) {
                $file_record = $item;
                $key_index = $k;
                break;
            }
        }

        if ( ! $file_record ) {
            return new WP_Error( 'not_found', 'File not found in quarantine index.' );
        }

        $source = $this->quarantine_dir . '/' . $file_record['stored_name'];
        $destination = $file_record['original_path'];

        if ( ! file_exists( $source ) ) {
            // Remove from index if source is gone
            unset( $index[ $key_index ] );
            update_option( $this->option_key, array_values( $index ) );
            return new WP_Error( 'source_missing', 'Quarantined file is missing from disk.' );
        }

        // Ensure directory exists (if it was deleted)
        $dir = dirname( $destination );
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }

        if ( ! rename( $source, $destination ) ) {
            return new WP_Error( 'restore_failed', 'Could not restore file. Check permissions.' );
        }

        // Remove from index
        unset( $index[ $key_index ] );
        update_option( $this->option_key, array_values( $index ) );

        return true;
    }

    public function delete_file( $id ) {
        $index = get_option( $this->option_key, array() );
        $key_index = -1;
        $file_record = null;

        foreach ( $index as $k => $item ) {
            if ( $item['id'] === $id ) {
                $file_record = $item;
                $key_index = $k;
                break;
            }
        }

        if ( ! $file_record ) {
            return new WP_Error( 'not_found', 'File not found.' );
        }

        $source = $this->quarantine_dir . '/' . $file_record['stored_name'];

        if ( file_exists( $source ) ) {
            unlink( $source );
        }

        // Remove from index
        unset( $index[ $key_index ] );
        update_option( $this->option_key, array_values( $index ) );

        return true;
    }
}
