import json
from playwright.sync_api import sync_playwright

def verify_security_dashboard():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        # 1. Set viewport to Desktop to ensure sidebar is visible
        page = browser.new_page(viewport={"width": 1280, "height": 800})

        # 2. Mock Global Data
        page.add_init_script("""
            window.woosuiteData = {
                apiUrl: 'http://localhost/wp-json/woosuite/v1',
                nonce: 'mock-nonce',
                homeUrl: 'http://localhost'
            };
        """)

        # 3. Mock API Routes
        # Settings
        page.route('**/settings', lambda route: route.fulfill(
            status=200,
            content_type='application/json',
            body=json.dumps({"apiKey": "mock-key", "loginMaxRetries": 3})
        ))

        # Security Status
        page.route('**/security/status', lambda route: route.fulfill(
            status=200,
            content_type='application/json',
            body=json.dumps({
                "firewall_enabled": True,
                "spam_enabled": True,
                "block_sqli": True,
                "block_xss": True,
                "simulation_mode": False,
                "login_enabled": True,
                "login_max_retries": 5,
                "last_scan": "2023-10-27 10:00:00",
                "last_scan_source": "manual",
                "threats_blocked": 15
            })
        ))

        # Security Logs
        page.route('**/security/logs', lambda route: route.fulfill(
            status=200,
            content_type='application/json',
            body=json.dumps([
                {"id": 1, "event": "SQL Injection Attempt", "severity": "critical", "ip_address": "192.168.1.5", "created_at": "2023-10-27 12:00:00"},
                {"id": 2, "event": "Failed Login", "severity": "medium", "ip_address": "10.0.0.2", "created_at": "2023-10-27 11:55:00"}
            ])
        ))

        # Deep Scan Status (with results for UI check)
        page.route('**/security/deep-scan/status', lambda route: route.fulfill(
            status=200,
            content_type='application/json',
            body=json.dumps({
                "status": "complete",
                "message": "Scan finished.",
                "processed_folders": 150,
                "total_folders": 150,
                "results": [
                    {"file": "/wp-content/plugins/bad-plugin/malware.php", "issue": "Found 'eval' function"}
                ]
            })
        ))

        # 4. Load App
        # We assume `assets/index.html` exists from the build
        import os
        cwd = os.getcwd()
        page.goto(f"file://{cwd}/assets/index.html")

        # 5. Wait for Dashboard to load
        page.wait_for_selector('h2:has-text("Security & Firewall")')

        # 6. Click "Security & Firewall" in sidebar if needed (it might be default if hash logic exists, but let's assume default view)
        # Actually, let's just click the sidebar link to be sure
        page.click('text="Security & Firewall"')
        page.wait_for_timeout(500) // Animation

        # 7. Take Screenshot 1: Dashboard Overview
        page.screenshot(path="verification_security_dashboard.png")
        print("Captured verification_security_dashboard.png")

        # 8. Test Log Advisor Modal
        page.click('button:has-text("AI Log Advisor")')
        page.wait_for_selector('h3:has-text("Security Log Advisor")')
        page.screenshot(path="verification_security_log_advisor.png")
        print("Captured verification_security_log_advisor.png")

        # Close modal
        page.click('button >> .lucide-x')

        # 9. Test Deep Scan Modal
        page.click('button:has-text("Deep Scan")')
        page.wait_for_selector('h3:has-text("Deep Malware Scan")')
        page.screenshot(path="verification_security_deep_scan.png")
        print("Captured verification_security_deep_scan.png")

        browser.close()

if __name__ == "__main__":
    verify_security_dashboard()
