#!/bin/bash
# test_settings_api.sh

# Mock WP Environment (Simulated)
# We can't curl localhost because there is no server running.
# We must rely on PHP Unit-style checking or just manual verification via code review.
# Since we can't run PHPUnit, we will check syntax errors.

echo "Checking PHP Syntax..."
find . -name "*.php" -print0 | xargs -0 -n1 php -l

echo "Build Plugin..."
./build_plugin.sh

echo "Verifying Zip..."
if [ -f woosuite-ai.zip ]; then
    echo "Zip created successfully."
else
    echo "Zip creation failed."
    exit 1
fi

echo "Done."
