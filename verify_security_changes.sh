#!/bin/bash

# Define API URL and Nonce
API_URL="http://localhost:8888/wp-json/woosuite/v1"
# We need to simulate a nonce or bypass permission checks for local testing if possible.
# Since we are in a mock environment (no real WP), we might need to rely on the fact that our 'setup_env.sh' doesn't actually spin up a PHP server,
# but the user told me earlier: "The development environment lacks a PHP CLI executable; verification relies on static analysis and frontend simulation".
#
# Wait, if I can't run PHP, I can't run `test_api.sh` against localhost.
# The user said: "Frontend logic involving API interactions ... is verified using Playwright with mocked API routes".
#
# So I cannot verify the PHP API code dynamically. I must rely on Code Review and Static Analysis.
# I verified the PHP code via `read_file` earlier.
#
# However, I CAN verify that the build assets were created (which I did).
# And I CAN verify the frontend UI triggers the calls using Playwright.

echo "Verifying Build Assets..."
if [ -f "assets/woosuite-app.js" ]; then
    echo "✅ React App Built Successfully"
else
    echo "❌ React App Build Failed"
    exit 1
fi

echo "Verifying PHP Syntax (basic check if php is available, else skip)..."
if command -v php >/dev/null 2>&1; then
    php -l includes/security/class-woosuite-security-quarantine.php
    php -l includes/class-woosuite-groq.php
    php -l includes/api/class-woosuite-api.php
else
    echo "⚠️ PHP CLI not available, skipping syntax check."
fi

echo "Verification Complete."
