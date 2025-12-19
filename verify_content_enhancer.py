from playwright.sync_api import sync_playwright

def verify_content_enhancer():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        page.add_init_script("""
            window.woosuiteData = {
                apiUrl: 'http://localhost:3000/api',
                nonce: '123456',
                homeUrl: 'http://localhost:3000',
                apiKey: 'gsk_test'
            };
        """)

        # Mock APIs
        # Initial Content
        page.route("**/content?type=product*", lambda route: route.fulfill(
            status=200,
            body='{"items": [{"id": 1, "name": "Old Product", "description": "Old Desc", "type": "product"}], "total": 1, "pages": 1}'
        ))

        # Rewrite Mock
        page.route("**/content/rewrite", lambda route: route.fulfill(
            status=200,
            body='{"success": true, "rewritten": "AI Enhanced Product Title"}'
        ))

        # Apply Mock
        page.route("**/content/apply", lambda route: route.fulfill(
            status=200,
            body='{"success": true}'
        ))

        page.route("**/security/status", lambda route: route.fulfill(status=200, body='{}'))

        # Load App
        page.goto("http://localhost:3000/")
        page.wait_for_timeout(1000)

        # Navigate
        print("Navigating to Content Enhancer...")
        try:
            page.get_by_role("button", name="Content Enhancer").click()
            page.wait_for_timeout(1000)
        except:
             print("FAIL: Content Enhancer button not found")
             page.screenshot(path="verification_content_enhancer_fail.png")
             browser.close()
             return

        # Verify Table
        if page.get_by_text("Old Product").is_visible():
            print("PASS: Product table loaded.")
        else:
            print("FAIL: Product not found.")

        # Click Rewrite (First item)
        print("Clicking Rewrite on Row...")
        try:
            # Locate the button strictly inside the row
            row = page.get_by_role("row").filter(has_text="Old Product")
            rewrite_btn = row.get_by_role("button", name="Rewrite")

            if rewrite_btn.is_visible():
                rewrite_btn.click()
                print("Clicked Row Rewrite.")
            else:
                print("Row Rewrite button not found/visible.")

            # Wait for API response and state update
            page.wait_for_timeout(1000)

            # Verify Proposed Text
            if page.get_by_text("AI Enhanced Product Title").is_visible():
                print("PASS: Proposed text appeared.")
            else:
                print("FAIL: Proposed text did NOT appear.")
        except Exception as e:
            print(f"FAIL: Interaction error: {e}")

        # Click Apply
        print("Clicking Apply...")
        try:
            row = page.get_by_role("row").filter(has_text="Old Product")
            apply_btn = row.get_by_role("button", name="Apply")

            if apply_btn.is_visible():
                apply_btn.click()
                print("Apply clicked.")
            else:
                print("Apply button not visible (Mock may have failed to update state?)")

        except Exception as e:
            print(f"FAIL: Apply interaction: {e}")

        # Screenshot
        page.screenshot(path="verification_content_enhancer.png")
        print("Screenshot saved.")

        browser.close()

if __name__ == "__main__":
    verify_content_enhancer()
