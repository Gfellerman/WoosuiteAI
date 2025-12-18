from playwright.sync_api import sync_playwright

def verify_seo_manager():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        # Use desktop viewport to prevent mobile menu from hiding navigation/buttons
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        # Mock Global Data (simulate WP environment)
        page.add_init_script("""
            window.woosuiteData = {
                apiUrl: 'http://localhost:3000/api', // Mock URL
                nonce: '123456',
                homeUrl: 'http://localhost:3000'
            };
        """)

        # Mock API responses
        page.route("**/seo/batch-status", lambda route: route.fulfill(
            status=200,
            body='{"status": "running", "total": 100, "processed": 50, "message": "Optimization in progress..."}'
        ))

        page.route("**/content?type=product*", lambda route: route.fulfill(
            status=200,
            body='{"items": [], "total": 0, "pages": 0}'
        ))

        # Security API mocks (so the app doesn't crash if it tries to fetch security status on load)
        page.route("**/security/status", lambda route: route.fulfill(
             status=200,
             body='{"firewall_enabled": true, "spam_enabled": true}'
        ))
        page.route("**/security/logs", lambda route: route.fulfill(status=200, body='[]'))
        page.route("**/security/deep-scan/status", lambda route: route.fulfill(status=200, body='{"status":"idle"}'))


        # Navigate to app
        page.goto("http://localhost:3000/")

        # Wait for React to mount
        page.wait_for_timeout(2000)

        # 1. Click "AI SEO (GEO)" in the sidebar navigation
        try:
             # NavItem id="seo" label="AI SEO (GEO)"
             page.get_by_role("button", name="AI SEO (GEO)").click()
             page.wait_for_timeout(1000) # Wait for component transition
        except Exception as e:
             print(f"Navigation failed: {e}")
             # Take a debugging screenshot
             page.screenshot(path="verification_seo_debug.png")
             browser.close()
             return

        # 2. Verify Banner is Visible
        # "Background Optimization Running"
        if page.get_by_text("Background Optimization Running").is_visible():
            print("Banner Visible")
        else:
            print("Banner NOT Visible")

        # 3. Verify "Stop" button in Banner
        # 4. Verify "Show Progress" button
        try:
            page.get_by_text("Show Progress").click()
            page.wait_for_timeout(500)

            # 5. Take Screenshot of Modal with "Force Reset" button (which I added to the modal)
            page.screenshot(path="verification_seo.png")
            print("Screenshot captured")
        except Exception as e:
             print(f"Interaction failed: {e}")
             page.screenshot(path="verification_seo_error.png")

        browser.close()

if __name__ == "__main__":
    verify_seo_manager()
