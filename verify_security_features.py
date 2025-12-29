import os
from playwright.sync_api import sync_playwright

def verify_security_frontend():
    # Define paths
    base_dir = os.path.abspath("assets")
    index_path = os.path.join(base_dir, "index.html")
    file_url = f"file://{index_path}"

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        # Force desktop viewport to avoid mobile menu logic which might hide buttons
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        # Mock window.woosuiteData BEFORE the script loads
        # We need to simulate the state where "Log Advisor" and "Deep Scan" are visible
        # "Log Advisor" is visible on 'dashboard' tab.
        # "Analyze with AI" is visible in scan results.

        # MOCK API Responses
        page.route('**/security/status', lambda route: route.fulfill(
            status=200,
            content_type='application/json',
            body='{"firewall_enabled": true, "spam_enabled": true, "block_sqli": true, "block_xss": true, "simulation_mode": false, "login_enabled": true, "login_max_retries": 3, "last_scan": "Never", "last_scan_source": "auto", "threats_blocked": 15}'
        ))

        page.route('**/security/logs', lambda route: route.fulfill(
             status=200,
             content_type='application/json',
             body='[{"id":1, "event":"SQL Injection Blocked", "ip_address":"192.168.1.1", "severity":"Critical", "created_at":"2023-10-27 10:00:00"}]'
        ))

        page.route('**/security/deep-scan/status', lambda route: route.fulfill(
            status=200,
            content_type='application/json',
            body='{"status": "complete", "message": "Scan Complete", "processed_folders": 50, "total_folders": 50, "results": [{"file": "wp-content/plugins/bad-plugin/malware.php", "issue": "eval() detected", "severity": "high"}]}'
        ))

        # Inject Data
        page.add_init_script("""
            window.woosuiteData = {
                root: 'http://localhost/wp-json/woosuite/v1',
                homeUrl: 'http://localhost',
                nonce: '12345',
                apiKey: 'test-key'
            };
        """)

        print(f"Navigating to {file_url}")
        page.goto(file_url)

        # Wait for app to mount
        try:
            page.wait_for_selector('h2:has-text("Security & Firewall")', timeout=5000)
            print("Security Hub loaded.")
        except:
            print("Timeout waiting for Security Hub. Dumping content...")
            print(page.content())
            page.screenshot(path="verification_timeout.png")
            return

        # 1. Check "AI Log Advisor" button
        log_advisor_btn = page.get_by_text("AI Log Advisor")
        if log_advisor_btn.is_visible():
            print("✅ Log Advisor button is visible.")
        else:
            print("❌ Log Advisor button is NOT visible.")

        # 2. Check "Deep Scan" Results with "Analyze" button
        # We mocked the status to return results, so the table should be visible.
        analyze_btn = page.get_by_role("button", name="Analyze")

        # Sometimes it takes a moment for the effect to fetch data and render
        page.wait_for_timeout(1000)

        if analyze_btn.first.is_visible():
            print("✅ Analyze button in Scan Results is visible.")
        else:
            print("❌ Analyze button in Scan Results is NOT visible.")
            # Debug: Check if "Deep Scan Status" header is visible
            if page.get_by_text("Deep Scan Status").is_visible():
                 print("   (Deep Scan Status panel is visible though)")
            else:
                 print("   (Deep Scan Status panel is NOT visible - Mock might have failed)")

        # Screenshot
        page.screenshot(path="verification_security_features.png")
        print("Screenshot saved to verification_security_features.png")

        browser.close()

if __name__ == "__main__":
    verify_security_frontend()
