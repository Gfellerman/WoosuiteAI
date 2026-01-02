<?php

class WooSuite_Backup {

    private $plugin_name;
    private $version;
    private $base_dir;
    private $base_url;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $upload_dir = wp_upload_dir();
        $this->base_dir = $upload_dir['basedir'] . '/woosuite-exports-temp';
        $this->base_url = $upload_dir['baseurl'] . '/woosuite-exports-temp';

        if ( ! file_exists( $this->base_dir ) ) {
            wp_mkdir_p( $this->base_dir );
        }
    }

    public function get_system_report() {
        global $wp_version, $wpdb;

        $plugins = get_plugins();
        $active_plugins = get_option( 'active_plugins' );
        $plugin_list = array();

        foreach ( $active_plugins as $plugin_file ) {
            if ( isset( $plugins[$plugin_file] ) ) {
                $plugin_list[] = $plugins[$plugin_file]['Name'] . ' (v' . $plugins[$plugin_file]['Version'] . ')';
            }
        }

        // Get DB Size
        $db_size_mb = $this->get_db_size_mb();

        return array(
            'php_version' => phpversion(),
            'wp_version' => $wp_version,
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            'db_size_mb' => $db_size_mb,
            'memory_limit' => ini_get( 'memory_limit' ),
            'max_execution_time' => ini_get( 'max_execution_time' ),
            'active_plugins' => $plugin_list,
            'active_theme' => wp_get_theme()->get( 'Name' ),
            'is_multisite' => is_multisite() ? 'Yes' : 'No',
            'debug_mode' => defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Enabled' : 'Disabled'
        );
    }

    private function get_db_size_mb() {
        global $wpdb;
        $db_size = 0;
        $rows = $wpdb->get_results( "SHOW TABLE STATUS" );
        foreach ( $rows as $row ) {
            $db_size += $row->Data_length + $row->Index_length;
        }
        return round( $db_size / 1024 / 1024, 2 );
    }

    public function start_export_process() {
        $db_size_mb = $this->get_db_size_mb();
        $free_space_bytes = disk_free_space( $this->base_dir );
        $free_space_mb = $free_space_bytes ? round( $free_space_bytes / 1024 / 1024, 2 ) : 0;

        // Safety margin: 1.1x DB size
        if ( $free_space_mb < ( $db_size_mb * 1.1 ) ) {
            return new WP_Error( 'disk_space', "Insufficient disk space. Need approx " . ($db_size_mb * 1.1) . "MB, found {$free_space_mb}MB." );
        }

        // Clear previous files
        $this->cleanup_temp_files();

        $filename = 'db-backup-' . date( 'Y-m-d-H-i-s' ) . '-' . wp_generate_password( 8, false ) . '.sql';
        $filepath = $this->base_dir . '/' . $filename;
        $error_log = $this->base_dir . '/error.log';
        $done_flag = $this->base_dir . '/done.flag';

        // Save filename to retrieve later
        update_option( 'woosuite_export_filename', $filename );

        if ( $this->command_exists( 'mysqldump' ) ) {
            $db_name = DB_NAME;
            $db_user = DB_USER;
            $db_pass = DB_PASSWORD;
            $db_host = DB_HOST;

            // Handle port
            $host_parts = explode( ':', $db_host );
            $host = $host_parts[0];
            $port = isset( $host_parts[1] ) ? $host_parts[1] : 3306;

            // Background Command
            // nohup sh -c "mysqldump ... > file.sql 2> error.log && echo 1 > done.flag" > /dev/null 2>&1 &
            $cmd = sprintf(
                'nohup sh -c "mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --quick --lock-tables=false %s > %s 2> %s && echo 1 > %s" > /dev/null 2>&1 &',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($db_user),
                escapeshellarg($db_pass),
                escapeshellarg($db_name),
                escapeshellarg($filepath),
                escapeshellarg($error_log),
                escapeshellarg($done_flag)
            );

            exec( $cmd );
            $this->log_info( "Started background export: $filename" );
            return true;
        } else {
             return new WP_Error( 'missing_tool', 'mysqldump not found. Cannot export large database.' );
        }
    }

    public function get_export_status() {
        $filename = get_option( 'woosuite_export_filename' );
        if ( ! $filename ) return array( 'status' => 'idle' );

        $filepath = $this->base_dir . '/' . $filename;
        $done_flag = $this->base_dir . '/done.flag';
        $error_log = $this->base_dir . '/error.log';

        if ( file_exists( $done_flag ) ) {
            return array(
                'status' => 'complete',
                'url' => $this->base_url . '/' . $filename,
                'size' => $this->format_size( filesize( $filepath ) )
            );
        }

        if ( file_exists( $error_log ) && filesize( $error_log ) > 0 ) {
            $error_msg = file_get_contents( $error_log );
            return array( 'status' => 'failed', 'message' => $error_msg );
        }

        if ( file_exists( $filepath ) ) {
            return array(
                'status' => 'processing',
                'size' => $this->format_size( filesize( $filepath ) )
            );
        }

        return array( 'status' => 'starting' );
    }

    private function cleanup_temp_files() {
        array_map( 'unlink', glob( $this->base_dir . '/*.sql' ) );
        array_map( 'unlink', glob( $this->base_dir . '/*.flag' ) );
        array_map( 'unlink', glob( $this->base_dir . '/*.log' ) );
    }

    private function format_size( $bytes ) {
        if ( $bytes >= 1073741824 ) return number_format( $bytes / 1073741824, 2 ) . ' GB';
        if ( $bytes >= 1048576 ) return number_format( $bytes / 1048576, 2 ) . ' MB';
        return number_format( $bytes / 1024, 2 ) . ' KB';
    }

    public function replace_urls( $old, $new ) {
        global $wpdb;

        if ( empty( $old ) || empty( $new ) || $old === $new ) {
            return new WP_Error( 'invalid_params', 'Invalid domain parameters.' );
        }

        // STRATEGY 1: Use WP-CLI if available (Best for 40GB sites)
        if ( $this->command_exists( 'wp' ) ) {
            $cmd = sprintf( "wp search-replace %s %s --all-tables --skip-columns=guid --report=false",
                escapeshellarg( $old ),
                escapeshellarg( $new )
            );
            exec( $cmd );
            return array( 'success' => true, 'rows_affected' => 'Processed via WP-CLI' );
        }

        // STRATEGY 2: PHP Fallback
        $total_rows = 0;

        // 1. Posts
        $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_content = REPLACE(post_content, %s, %s)", $old, $new ) );
        $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_excerpt = REPLACE(post_excerpt, %s, %s)", $old, $new ) );

        // 2. Options
        $options = $wpdb->get_results( $wpdb->prepare( "SELECT option_id, option_name, option_value FROM $wpdb->options WHERE option_value LIKE %s", '%' . $wpdb->esc_like( $old ) . '%' ) );
        foreach ( $options as $opt ) {
            $val = $opt->option_value;
            if ( is_serialized( $val ) ) {
                $unserialized = maybe_unserialize( $val );
                $fixed = $this->recursive_replace( $unserialized, $old, $new );
                $final = maybe_serialize( $fixed );
                $wpdb->update( $wpdb->options, array( 'option_value' => $final ), array( 'option_id' => $opt->option_id ) );
            } else {
                $fixed = str_replace( $old, $new, $val );
                $wpdb->update( $wpdb->options, array( 'option_value' => $fixed ), array( 'option_id' => $opt->option_id ) );
            }
            $total_rows++;
        }

        return array( 'success' => true, 'rows_affected' => $total_rows . ' (Critical Tables Only)' );
    }

    private function recursive_replace( $data, $old, $new ) {
        if ( is_string( $data ) ) {
            return str_replace( $old, $new, $data );
        } elseif ( is_array( $data ) ) {
            foreach ( $data as $key => $value ) {
                $data[$key] = $this->recursive_replace( $value, $old, $new );
            }
            return $data;
        } elseif ( is_object( $data ) ) {
            foreach ( $data as $key => $value ) {
                $data->$key = $this->recursive_replace( $value, $old, $new );
            }
            return $data;
        }
        return $data;
    }

    private function command_exists( $cmd ) {
        if ( ! function_exists( 'shell_exec' ) ) return false;
        $return = shell_exec( sprintf( "which %s", escapeshellarg( $cmd ) ) );
        return ! empty( $return );
    }

    private function log_info( $message ) {
        $logs = get_option( 'woosuite_debug_log', array() );
        $entry = "[" . date( 'Y-m-d H:i:s' ) . "] [INFO] [Backup] " . $message;
        array_unshift( $logs, $entry );
        if ( count( $logs ) > 50 ) array_pop( $logs );
        update_option( 'woosuite_debug_log', $logs );
    }

    private function log_error( $message ) {
        $logs = get_option( 'woosuite_debug_log', array() );
        $entry = "[" . date( 'Y-m-d H:i:s' ) . "] [ERROR] [Backup] " . $message;
        array_unshift( $logs, $entry );
        if ( count( $logs ) > 50 ) array_pop( $logs );
        update_option( 'woosuite_debug_log', $logs );
    }
}
