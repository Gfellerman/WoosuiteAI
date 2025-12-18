from playwright.sync_api import sync_playwright

def verify_security_ui(page):
    # Mock global data
    page.add_init_script("""
        window.woosuiteData = {
            apiUrl: 'http://localhost/wp-json/woosuite/v1',
            nonce: '123',
            homeUrl: 'http://localhost'
        };
    """)

    # Mock API
    page.route('**/security/status', lambda route: route.fulfill(
        status=200,
        body='{"firewall_enabled": true, "spam_enabled": true, "block_sqli": true, "block_xss": true, "simulation_mode": false, "login_enabled": true, "login_max_retries": 3, "last_scan": "2023-10-27 12:00:00", "last_scan_source": "auto"}'
    ))
    page.route('**/security/logs', lambda route: route.fulfill(
        status=200,
        body='[]'
    ))
    page.route('**/security/deep-scan/status', lambda route: route.fulfill(
        status=200,
        body='{"status": "idle", "results": []}'
    ))

    # Load page
    page.goto('http://localhost:3001')

    # Click Navigation
    print("Clicking Navigation...")
    page.get_by_role("button", name="Security & Firewall").click()

    # Wait for UI
    page.wait_for_selector('h2:has-text("Security & Firewall")')

    # Check "Auto-scan every 12h" text
    if page.get_by_text("Auto-scan every 12h").count() > 0:
        print("Text 'Auto-scan every 12h' found.")
    else:
        print("Text 'Auto-scan every 12h' NOT found.")

    # Check Deep Scan Button
    print("Clicking Deep Scan button...")
    page.get_by_role("button", name="Deep Scan").click()

    # Check Modal
    print("Checking Modal...")
    page.get_by_text("Deep Malware Scan").wait_for()
    page.get_by_text("This process will recursively scan").wait_for()

    # Screenshot
    page.screenshot(path="verification_security.png")
    print("Screenshot saved.")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page(viewport={'width': 1280, 'height': 800})
        try:
            verify_security_ui(page)
        except Exception as e:
            print(f"Error: {e}")
        finally:
            browser.close()
