<?php

class WooSuite_Activator {
	public static function activate() {
		// Activation logic (e.g., creating DB tables) goes here
        flush_rewrite_rules();
	}
}
