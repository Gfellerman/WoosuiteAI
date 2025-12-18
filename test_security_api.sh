#!/bin/bash

# Configuration
SITE_URL="http://localhost:8888" # Adjust if needed, but in this sandbox usually localhost works if we can find the port.
# Wait, I don't have the site URL in env vars. I'll rely on WP-CLI or direct curl if I can determine the URL.
# Actually, I can use wp-cli to check options directly, which is more reliable for backend state.
# But to test the API, I need the nonce. Getting a nonce in a script is hard without auth.
# Instead, I will use WP-CLI to verify the 'update_option' works via the class method if I could mock it,
# or simpler: I trust the code if the WP-CLI option update works.
# Better: I will create a PHP script that mocks the request and calls the class method directly.

wp eval '
  $_GET["page"] = "woosuite-ai"; // Mock admin context if needed
  require_once "'$(pwd)'/includes/api/class-woosuite-api.php";

  // Mock Request for Toggle
  $api = new WooSuite_Api( "woosuite-ai", "1.0.0" );

  // Test 1: Toggle Firewall to "no"
  $request = new WP_REST_Request( "POST", "/woosuite/v1/security/toggle" );
  $request->set_body_params( array( "option" => "firewall", "value" => false ) );
  $response = $api->toggle_security_option( $request );

  echo "Toggle Response: " . json_encode( $response->get_data() ) . "\n";
  echo "Option Value: " . get_option( "woosuite_firewall_enabled" ) . "\n";

  // Test 2: Toggle back to "yes"
  $request->set_body_params( array( "option" => "firewall", "value" => true ) );
  $api->toggle_security_option( $request );
  echo "Option Value (Reset): " . get_option( "woosuite_firewall_enabled" ) . "\n";
'
