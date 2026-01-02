<?php

class WooSuite_Backup {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
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

    public function export_database() {
        global $wpdb;

        // Prepare file
        $upload_dir = wp_upload_dir();
        $public_dir = $upload_dir['basedir'] . '/woosuite-exports-temp';

        if ( ! file_exists( $public_dir ) ) {
            if ( ! wp_mkdir_p( $public_dir ) ) {
                 $this->log_error( "Failed to create directory: $public_dir" );
                 return false;
            }
        }

        $filename = 'db-backup-' . date( 'Y-m-d-H-i-s' ) . '-' . wp_generate_password( 8, false ) . '.sql';
        $filepath = $public_dir . '/' . $filename;
        $file_url = $upload_dir['baseurl'] . '/woosuite-exports-temp/' . $filename;
        $error_log_path = $public_dir . '/error_log.txt'; // Temp error file

        $db_size_mb = $this->get_db_size_mb();
        $is_large_db = $db_size_mb > 1000; // > 1GB

        // Try mysqldump first (Faster & Safer for Large DBs)
        if ( $this->command_exists( 'mysqldump' ) ) {
            $db_name = DB_NAME;
            $db_user = DB_USER;
            $db_pass = DB_PASSWORD;
            $db_host = DB_HOST;

            // Handle port in host
            $host_parts = explode( ':', $db_host );
            $host = $host_parts[0];
            $port = isset( $host_parts[1] ) ? $host_parts[1] : 3306;

            // Secure Command Construction
            // 2> captures stderr to a file so we can read it on failure
            $cmd = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --quick --lock-tables=false %s > %s 2> %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($db_user),
                escapeshellarg($db_pass),
                escapeshellarg($db_name),
                escapeshellarg($filepath),
                escapeshellarg($error_log_path)
            );

            // Execute
            $output = null;
            $result_code = null;
            exec( $cmd, $output, $result_code );

            if ( $result_code === 0 && file_exists( $filepath ) && filesize( $filepath ) > 0 ) {
                $this->log_info( "mysqldump successful. Size: " . filesize($filepath) );
                return $file_url;
            } else {
                // Read error log
                $error_msg = 'Unknown error';
                if ( file_exists( $error_log_path ) ) {
                    $error_msg = file_get_contents( $error_log_path );
                    unlink( $error_log_path ); // Clean up
                }

                $this->log_error( "mysqldump failed (Code: $result_code). Error: $error_msg" );

                // If DB is huge, do not fall back to PHP. Fail fast.
                if ( $is_large_db ) {
                    $this->log_error( "Database too large ({$db_size_mb}MB) for PHP fallback. Aborting." );
                    return false; // Controller handles message
                }
            }
        } else {
             $this->log_error( "mysqldump command not found." );
             if ( $is_large_db ) {
                 $this->log_error( "Database too large ({$db_size_mb}MB) and mysqldump missing. Aborting." );
                 return false;
             }
        }

        // Fallback: PHP Export (Slow, crashes on large DBs)
        $this->log_info( "Attempting PHP fallback export..." );
        if ( $this->php_export( $filepath ) ) {
            return $file_url;
        }

        return false;
    }

    private function log_error( $message ) {
        $logs = get_option( 'woosuite_debug_log', array() );
        $entry = "[" . date( 'Y-m-d H:i:s' ) . "] [ERROR] [Backup] " . $message;
        array_unshift( $logs, $entry );
        if ( count( $logs ) > 50 ) array_pop( $logs );
        update_option( 'woosuite_debug_log', $logs );
    }

    private function log_info( $message ) {
        $logs = get_option( 'woosuite_debug_log', array() );
        $entry = "[" . date( 'Y-m-d H:i:s' ) . "] [INFO] [Backup] " . $message;
        array_unshift( $logs, $entry );
        if ( count( $logs ) > 50 ) array_pop( $logs );
        update_option( 'woosuite_debug_log', $logs );
    }

    private function php_export( $filepath ) {
        global $wpdb;

        // Increase limits for fallback
        @ini_set( 'memory_limit', '1024M' );
        @set_time_limit( 600 ); // 10 minutes

        $tables = $wpdb->get_results( "SHOW TABLES", ARRAY_N );

        $handle = fopen( $filepath, 'w' );
        if ( ! $handle ) {
            $this->log_error( "Cannot write to file: $filepath" );
            return false;
        }

        foreach ( $tables as $table_row ) {
            $table = $table_row[0];

            // Structure
            $row = $wpdb->get_row( "SHOW CREATE TABLE {$table}", ARRAY_N );
            fwrite( $handle, "\n\n" . $row[1] . ";\n\n" );

            // Data
            $limit = 1000;
            $offset = 0;

            do {
                $rows = $wpdb->get_results( "SELECT * FROM {$table} LIMIT {$limit} OFFSET {$offset}", ARRAY_A );
                if ( $rows ) {
                    foreach ( $rows as $row ) {
                        $values = array();
                        foreach ( $row as $value ) {
                            $value = addslashes( $value );
                            $value = str_replace( "\n", "\\n", $value );
                            $values[] = '"' . $value . '"';
                        }
                        fwrite( $handle, "INSERT INTO {$table} VALUES (" . implode( ',', $values ) . ");\n" );
                    }
                }
                $offset += $limit;
            } while ( count( $rows ) == $limit );
        }

        fclose( $handle );
        return true;
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
        $return = shell_exec( sprintf( "which %s", escapeshellarg( $cmd ) ) );
        return ! empty( $return );
    }
}
