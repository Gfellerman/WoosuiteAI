<?php

class WooSuite_Activator {

	public static function activate() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'woosuite_security_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			event varchar(255) NOT NULL,
			ip_address varchar(45) NOT NULL,
			severity varchar(20) NOT NULL,
			blocked tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		// Set default options if they don't exist
		add_option( 'woosuite_firewall_enabled', 'yes' );
		add_option( 'woosuite_spam_protection_enabled', 'yes' );
		add_option( 'woosuite_threats_blocked_count', 0 );

        // Schedule Automatic Scan
        if ( ! wp_next_scheduled( 'woosuite_scheduled_scan' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'woosuite_scheduled_scan' );
		}

        flush_rewrite_rules();
	}
}
