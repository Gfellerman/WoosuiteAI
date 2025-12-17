<?php

class WooSuite_Deactivator {
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'woosuite_scheduled_scan' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'woosuite_scheduled_scan' );
		}
        flush_rewrite_rules();
	}
}
