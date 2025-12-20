from playwright.sync_api import sync_playwright

def verify_content_enhancer_v2():
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

        # --- MOCKS ---

        # 1. Categories
        page.route("**/content/categories?type=product", lambda route: route.fulfill(
            status=200,
            body='[{"id": 10, "name": "Electronics", "count": 5}, {"id": 11, "name": "Fashion", "count": 3}]'
        ))

        # 2. Items (Default)
        # Note: We include shortDescription field to test the new display logic
        items_json = '''{
            "items": [
                {
                    "id": 1,
                    "name": "Test Product",
                    "description": "Long Description Text",
                    "shortDescription": "Short Excerpt Text",
                    "type": "product"
                }
            ],
            "total": 1,
            "pages": 1
        }'''
        page.route("**/content?type=product&limit=20&page=1", lambda route: route.fulfill(status=200, body=items_json))

        # 3. Filtered Items (Category=10)
        page.route("**/content?type=product&limit=20&page=1&category=10", lambda route: route.fulfill(
            status=200,
            body='{"items": [{"id": 3, "name": "Laptop", "description": "Gaming Laptop", "type": "product"}], "total": 1, "pages": 1}'
        ))

        # 4. Status Filtered Items (Status=enhanced)
        page.route("**/content?type=product&limit=20&page=1&status=enhanced", lambda route: route.fulfill(
            status=200,
            body='{"items": [], "total": 0, "pages": 0}'
        ))

        # 5. Bulk Apply
        page.route("**/content/bulk-apply", lambda route: route.fulfill(
            status=200,
            body='{"success": true, "applied": 1}'
        ))

        page.route("**/security/status", lambda route: route.fulfill(status=200, body='{}'))
        page.route("**/seo/batch-status", lambda route: route.fulfill(status=200, body='{}'))

        # --- TEST EXECUTION ---

        # Load App
        print("Loading App...")
        page.goto("http://localhost:3000/") # Vite configured port
        page.wait_for_timeout(2000)

        # Navigate to Content Enhancer
        print("Navigating to Content Enhancer...")
        try:
            # Depending on layout, might need to find menu item. Assuming tab or button.
            # If default view is Dashboard, check for nav.
            # Looking at previous screenshot/code, it seems to be a tab or route.
            # Assuming 'Content Enhancer' text exists in menu.
            page.get_by_text("Content Enhancer").click()
        except:
             # If direct navigation fails (maybe already there?), check presence
             pass

        page.wait_for_timeout(1000)

        # CHECK 1: Display Logic (Long vs Short)
        print("Test 1: Check Display Logic")
        # Default is 'Description', expect "Long Description Text"
        if page.get_by_text("Long Description Text").is_visible():
            print("PASS: Default View shows Long Description.")
        else:
            print("FAIL: Default View does NOT show Long Description.")

        # Switch to Short Description
        print("Switching to Short Description...")
        page.select_option("select", value="short_description") # Assuming first select is Field
        page.wait_for_timeout(500)

        if page.get_by_text("Short Excerpt Text").is_visible():
            print("PASS: View switched to Short Description.")
        else:
            print("FAIL: View did NOT switch to Short Description.")

        # Switch back
        page.select_option("select", value="description")

        # CHECK 2: Category Filter
        print("Test 2: Check Category Filter")
        # Wait for categories to load
        page.wait_for_timeout(1000)
        # Select Electronics
        # Target the select that contains "All Categories" option
        page.locator("select").filter(has_text="All Categories").select_option("10")

        page.wait_for_timeout(1000)
        # Use exact=True to avoid ambiguity with description
        if page.get_by_text("Laptop", exact=True).is_visible():
            print("PASS: Category Filter loaded 'Laptop'.")
        else:
            print("FAIL: Category Filter failed.")

        # Reset Filter
        page.locator("select").filter(has_text="Electronics").select_option("")
        page.wait_for_timeout(1000)

        # CHECK 3: Bulk Apply UI
        print("Test 3: Bulk Apply UI")
        # Select Item 1 (Test Product)
        # Click checkbox in first row
        page.get_by_role("row").filter(has_text="Test Product").get_by_role("checkbox").click()

        # Check if "Apply (1)" button appears
        apply_btn = page.get_by_role("button", name="Apply (1)")
        if apply_btn.is_visible():
            print("PASS: Bulk Apply button appeared.")
            apply_btn.click()
            page.wait_for_timeout(500)
            # We mock success, so it should just work.
            print("Clicked Bulk Apply.")
        else:
            print("FAIL: Bulk Apply button not visible.")

        # Screenshot
        page.screenshot(path="verification_content_enhancer_v2.png")
        print("Screenshot saved to verification_content_enhancer_v2.png")

        browser.close()

if __name__ == "__main__":
    verify_content_enhancer_v2()
