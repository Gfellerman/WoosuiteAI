from playwright.sync_api import sync_playwright
import json
import time

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        page.set_viewport_size({"width": 1280, "height": 800})

        # Inject Data
        page.add_init_script("""
            window.woosuiteData = {
                apiUrl: 'http://localhost/wp-json/woosuite/v1',
                nonce: '12345',
                homeUrl: 'http://localhost'
            };
        """)

        # Mock API
        page.route('**/woosuite/v1/security/status', lambda route: route.fulfill(
            status=200,
            body=json.dumps({
                'firewall_enabled': True,
                'spam_enabled': True,
                'block_sqli': True,
                'block_xss': True,
                'simulation_mode': False,
                'login_enabled': True,
                'last_scan': '2023-10-27 10:00:00',
                'alerts': None
            })
        ))

        page.route('**/woosuite/v1/security/logs', lambda route: route.fulfill(
            status=200,
            body=json.dumps([])
        ))

        page.route('**/woosuite/v1/security/deep-scan/status', lambda route: route.fulfill(
            status=200,
            body=json.dumps({
                'status': 'complete',
                'results': [
                    {'file': 'wp-content/plugins/bad-plugin/bad.php', 'issue': 'Malware', 'verdict': 'Malicious'},
                    {'file': 'wp-content/themes/bad-theme/hack.php', 'issue': 'Shell', 'verdict': 'Malicious'}
                ]
            })
        ))

        # Mock Bulk Action
        page.route('**/woosuite/v1/security/bulk', lambda route: route.fulfill(
            status=200,
            body=json.dumps({'success': True, 'count': 1})
        ))

        # Load App
        page.goto('http://localhost:8080/assets/index.html')

        print("Navigating to Security...")
        # Mobile menu check if needed, but we set desktop size
        page.wait_for_selector('button:has-text("Security & Firewall")')
        page.click('button:has-text("Security & Firewall")')

        # Check Bulk UI
        print("Checking Bulk UI...")
        page.wait_for_selector('text=Suspicious Files Found')

        # Select a threat FIRST to make buttons appear
        checkboxes = page.query_selector_all('input[type="checkbox"]')
        if len(checkboxes) > 0:
            # Click the second one (first row), the first one is likely "Select All" header
            checkboxes[1].click()
            print("Selected a threat.")
        else:
            print("No checkboxes found!")
            browser.close()
            return

        # NOW wait for buttons
        page.wait_for_selector('text=Ignore Selected')
        page.wait_for_selector('text=Delete Selected')

        # Screenshot
        page.screenshot(path='verification/security_bulk.png')
        print("Screenshot saved to verification/security_bulk.png")

        browser.close()

if __name__ == "__main__":
    run()
