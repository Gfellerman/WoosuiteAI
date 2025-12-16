<?php

class WooSuite_Deactivator {
	public static function deactivate() {
		// Deactivation logic goes here
        flush_rewrite_rules();
	}
}
