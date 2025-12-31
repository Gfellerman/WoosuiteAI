<?php

class WooSuite_Security_Quarantine {

    private $quarantine_dir;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->quarantine_dir = $upload_dir['basedir'] . '/woosuite-quarantine/';

        // Ensure directory exists and is protected
        if ( ! file_exists( $this->quarantine_dir ) ) {
            wp_mkdir_p( $this->quarantine_dir );
            // Add .htaccess to prevent execution in quarantine
            file_put_contents( $this->quarantine_dir . '.htaccess', "Order Deny,Allow\nDeny from all" );
            file_put_contents( $this->quarantine_dir . 'index.php', "<?php // Silence is golden" );
        }
    }

    /**
     * Move a file to quarantine
     */
    public function quarantine_file( $filepath ) {
        // Validation: Ensure file is inside WP root
        $real_filepath = realpath( $filepath );
        if ( ! $real_filepath || strpos( $real_filepath, ABSPATH ) !== 0 ) {
             return new WP_Error( 'invalid_path', 'Cannot quarantine files outside WordPress root.' );
        }

        // Generate safe quarantine name (Base64 of path to allow restore)
        $quarantine_name = base64_encode( $real_filepath ) . '.quarantined';
        $destination = $this->quarantine_dir . $quarantine_name;

        if ( rename( $real_filepath, $destination ) ) {
            // Log logic could go here
            return true;
        }

        return new WP_Error( 'move_failed', 'Could not move file to quarantine.' );
    }

    /**
     * Restore a file from quarantine
     */
    public function restore_file( $quarantined_filename ) {
        $source = $this->quarantine_dir . $quarantined_filename;

        if ( ! file_exists( $source ) ) {
            return new WP_Error( 'file_not_found', 'Quarantined file not found.' );
        }

        // Decode original path
        // format: path_encoded.quarantined
        $original_path_encoded = str_replace( '.quarantined', '', $quarantined_filename );
        $original_path = base64_decode( $original_path_encoded );

        if ( ! $original_path ) {
            return new WP_Error( 'invalid_filename', 'Could not decode original path.' );
        }

        // Ensure directory exists (if plugin was deleted, we might need to recreate folder?)
        // Ideally we just try to move it back.
        $dir = dirname( $original_path );
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }

        if ( rename( $source, $original_path ) ) {
            return true;
        }

        return new WP_Error( 'restore_failed', 'Could not restore file.' );
    }

    /**
     * Delete file permanently
     */
    public function delete_file( $quarantined_filename ) {
        $source = $this->quarantine_dir . $quarantined_filename;
        if ( file_exists( $source ) ) {
            unlink( $source );
            return true;
        }
        return false;
    }

    /**
     * List quarantined files
     */
    public function get_quarantined_files() {
        $files = glob( $this->quarantine_dir . '*.quarantined' );
        $list = array();

        if ( $files ) {
            foreach ( $files as $f ) {
                $filename = basename( $f );
                $original_path_encoded = str_replace( '.quarantined', '', $filename );
                $original_path = base64_decode( $original_path_encoded );

                $list[] = array(
                    'id' => $filename,
                    'original_path' => str_replace( ABSPATH, '', $original_path ),
                    'date' => date( 'Y-m-d H:i:s', filemtime( $f ) ),
                    'size' => size_format( filesize( $f ) )
                );
            }
        }
        return $list;
    }
}
