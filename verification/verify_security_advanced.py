from playwright.sync_api import sync_playwright

def verify_security_advanced(page):
    # Mock global data
    page.add_init_script("""
        window.woosuiteData = {
            apiUrl: 'http://localhost/wp-json/woosuite/v1',
            nonce: '123',
            homeUrl: 'http://localhost'
        };
    """)
    page.route('**/security/status', lambda route: route.fulfill(
        status=200, body='{"firewall_enabled": true}'
    ))
    page.route('**/security/logs', lambda route: route.fulfill(status=200, body='[]'))
    page.route('**/security/deep-scan/status', lambda route: route.fulfill(status=200, body='{"status": "idle"}'))

    page.goto('http://localhost:3001')
    page.get_by_role("button", name="Security & Firewall").click()
    page.wait_for_selector('h2:has-text("Security & Firewall")')

    print("Checking for High Security Mode...")
    page.wait_for_selector('h3:has-text("High Security Mode (Heavy)")')
    print("High Security Mode found.")

    page.screenshot(path="verification/security_advanced.png")
    print("Screenshot saved.")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page(viewport={'width': 1280, 'height': 800})
        try:
            verify_security_advanced(page)
        except Exception as e:
            print(f"Error: {e}")
        finally:
            browser.close()
