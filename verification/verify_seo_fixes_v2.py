from playwright.sync_api import sync_playwright
import time

def verify_fixes_v2(page):
    # Capture console
    page.on("console", lambda msg: print(f"CONSOLE: {msg.text}"))

    # Mock global
    page.add_init_script("""
        window.woosuiteData = {
            apiUrl: 'http://localhost/wp-json/woosuite/v1',
            nonce: '123',
            homeUrl: 'http://localhost'
        };
    """)

    # Mock Status
    page.route('**/seo/batch-status', lambda route: route.fulfill(
        status=200,
        body='{"status": "idle", "processed": 0, "total": 0}'
    ))

    # Mock Items (20 items)
    items_json = '{"items": [' + ','.join([f'{{"id": {i}, "name": "Item {i}", "type": "product"}}' for i in range(1, 21)]) + '], "total": 20, "pages": 1}'

    # Use broader pattern
    page.route('**/*limit=20*', lambda route: route.fulfill(
        status=200,
        body=items_json
    ))

    # Mock Items (60 items)
    items_100_json = '{"items": [' + ','.join([f'{{"id": {i}, "name": "Item {i}", "type": "product"}}' for i in range(1, 61)]) + '], "total": 60, "pages": 1}'
    page.route('**/*limit=100*', lambda route: route.fulfill(
        status=200,
        body=items_100_json
    ))

    # Mock Resume
    page.route('**/seo/batch/resume', lambda route: route.fulfill(status=200, body='{"success": true}'))

    # Mock Generate (Client Loop)
    page.route('**/seo/generate/*', lambda route: route.fulfill(
        status=200,
        body='{"success": true, "data": {"title": "Optimized", "description": "Desc"}}'
    ))

    # Mock Batch Start
    page.route('**/seo/batch', lambda route: route.fulfill(
        status=200,
        body='{"success": true}'
    ))

    print("Loading page...")
    page.goto('http://localhost:3001')
    page.get_by_role("button", name="AI SEO (GEO)").click()
    page.wait_for_selector('h2:has-text("AI SEO Manager")')

    # Wait for table data
    page.wait_for_selector('td')

    # Test 1: Client Side Batch (20 items)
    print("Testing Client Side Batch (< 50 items)...")
    # Select All (20 items default)
    page.locator('input[type="checkbox"]').first.click() # Select All

    # Click Optimize
    btn_20 = page.locator('button:has-text("Optimize Selected (20)")')
    btn_20.wait_for()

    try:
        with page.expect_request('**/seo/generate/*') as req:
            btn_20.click()
    except Exception as e:
        print("Failed to click Optimize Selected (20).")
        raise e

    # Check for Client Modal
    page.wait_for_selector('h3:has-text("Optimizing Selected Items...")')
    print("PASS: Client Side Batch started.")

    # Handle Alerts
    page.on("dialog", lambda dialog: dialog.accept())

    # Test 2: Background Batch (> 50 items)
    print("Testing Background Batch (>= 50 items)...")
    page.reload()
    page.get_by_role("button", name="AI SEO (GEO)").click()
    page.wait_for_selector('td')

    # Change Limit to 100
    page.select_option('select', '100')
    # Wait for fetch (wait for new table content or network idle)
    page.wait_for_timeout(2000)

    # Select All (60 items)
    page.locator('input[type="checkbox"]').first.click()

    # Check Button Text
    btn = page.locator('button:has-text("Optimize Selected (60)")')
    btn.wait_for()
    print("Selection confirmed (60 items).")

    # Click Optimize
    # Should trigger /seo/batch
    with page.expect_request('**/seo/batch') as req:
        btn.click()

    # Check for Background Modal
    page.wait_for_selector('h3:has-text("Background Optimization")')
    print("PASS: Background Batch started for large selection.")

    # Test 3: Resume Button
    print("Testing Resume Button...")
    # Mock stuck status
    page.route('**/seo/batch-status', lambda route: route.fulfill(
        status=200,
        body=f'{{"status": "running", "processed": 0, "total": 100, "last_updated": {time.time() - 200}}}'
    ))

    # Trigger poll
    page.wait_for_timeout(4000)

    # Check for Resume Button
    resume_btn = page.locator('button:has-text("Resume / Kickstart")')
    if resume_btn.count() > 0:
        print("Resume button visible.")
        with page.expect_request('**/seo/batch/resume') as req:
            resume_btn.click()
        print("PASS: Resume button called correct endpoint.")
    else:
        print("FAIL: Resume button not found (Stuck detection logic).")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page(viewport={'width': 1280, 'height': 800})
        try:
            verify_fixes_v2(page)
        except Exception as e:
            print(f"Error: {e}")
            page.screenshot(path="verification/fail_fixes_v2.png")
        finally:
            browser.close()
