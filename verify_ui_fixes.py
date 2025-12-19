from playwright.sync_api import sync_playwright

def verify_fixes():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        page.add_init_script("""
            window.woosuiteData = {
                apiUrl: 'http://localhost:3000/api',
                nonce: '123456',
                homeUrl: 'http://localhost:3000',
                apiKey: 'gsk_test_key'
            };
        """)

        # Mock API
        page.route("**/seo/batch-status", lambda route: route.fulfill(status=200, body='{"status": "idle", "total": 0, "processed": 0}'))
        page.route("**/content*", lambda route: route.fulfill(status=200, body='{"items": [], "total": 0, "pages": 0}'))
        page.route("**/security/status", lambda route: route.fulfill(status=200, body='{}'))
        page.route("**/security/deep-scan/status", lambda route: route.fulfill(status=200, body='{"status":"idle"}'))
        page.route("**/security/logs", lambda route: route.fulfill(status=200, body='[]'))
        page.route("**/stats", lambda route: route.fulfill(status=200, body='{"orders":0, "seo_score":0, "threats_blocked":0, "ai_searches":0, "last_backup":"Never"}'))

        # Mock Test Connection
        page.route("**/settings/test-connection", lambda route: route.fulfill(status=200, body='{"success": true, "message": "Connection Successful!"}'))

        # Load App
        page.goto("http://localhost:3000/")
        page.wait_for_timeout(1000)

        # 1. Verify Settings (Installation Tab Removal)
        print("Navigating to Settings...")
        page.get_by_role("button", name="Settings").click()
        page.wait_for_timeout(500)

        # Check tabs
        if page.get_by_role("button", name="Installation").is_visible():
            print("FAIL: Installation tab is still visible!")
        else:
            print("PASS: Installation tab is gone.")

        # Test Connection in Settings
        print("Testing Connection...")
        try:
            page.get_by_role("button", name="Test Connection").click()
            page.wait_for_timeout(1000)
            if page.get_by_text("Success").is_visible():
                print("PASS: Settings Test Connection worked.")
            else:
                print("FAIL: Settings Test Connection failed (Success message not found).")
        except Exception as e:
            print(f"FAIL: Could not click Test Connection: {e}")

        # 2. Verify SEO Sitemap Link
        print("Navigating to SEO...")
        page.get_by_role("button", name="AI SEO (GEO)").click()
        page.wait_for_timeout(500)

        print("Opening Sitemap Modal...")
        page.get_by_role("button", name="Sitemap").click()
        page.wait_for_timeout(500)

        # Check for link
        # Look for the external link icon or the <a> tag
        link = page.locator("a[title='Open Sitemap']")
        if link.is_visible():
            print("PASS: Sitemap Link is visible.")
            href = link.get_attribute("href")
            print(f"Sitemap Href: {href}")
        else:
            print("FAIL: Sitemap Link not found.")

        # Screenshot
        page.screenshot(path="verification_ui_fixes.png")
        print("Screenshot saved to verification_ui_fixes.png")

        browser.close()

if __name__ == "__main__":
    verify_fixes()
