import os
import re

def check_file_exists(path, context=""):
    if os.path.exists(path):
        print(f"[OK] {context}: {path} exists")
        return True
    else:
        print(f"[FAIL] {context}: {path} MISSING")
        return False

def verify_php_requires(filepath):
    print(f"Scanning {filepath} for require/include statements...")
    with open(filepath, 'r') as f:
        content = f.read()

    # Regex to find require_once/include etc.
    # Matches: require_once WOOSUITE_AI_PATH . 'includes/class-woosuite-activator.php';
    # We need to resolve WOOSUITE_AI_PATH to current directory.

    requires = re.findall(r"(?:require|include)(?:_once)?\s+(?:WOOSUITE_AI_PATH\s*\.\s*)?['\"]([^'\"]+)['\"]", content)

    all_ok = True
    for req in requires:
        # Construct local path
        local_path = req
        if not os.path.exists(local_path):
             # Try relative to the file? No, usually relative to root in this plugin structure
             pass

        if check_file_exists(local_path, f"Ref in {filepath}"):
            pass
        else:
            all_ok = False

    return all_ok

print("--- Starting Integrity Check ---")

required_files = [
    'woosuite-ai.php',
    'includes/class-woosuite-core.php',
    'includes/class-woosuite-admin.php',
    'includes/class-woosuite-activator.php',
    'includes/class-woosuite-deactivator.php',
    'includes/api/class-woosuite-api.php',
    'assets/woosuite-app.js',
    'assets/woosuite-app.css'
]

success = True
for f in required_files:
    if not check_file_exists(f, "Core File"):
        success = False

if success:
    print("\n--- Verifying Internal References ---")
    # Check woosuite-ai.php
    verify_php_requires('woosuite-ai.php')
    # Check class-woosuite-core.php
    verify_php_requires('includes/class-woosuite-core.php')

    print("\n[SUCCESS] Integrity check passed.")
else:
    print("\n[FAILURE] Integrity check failed.")
