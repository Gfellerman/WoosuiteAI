from playwright.sync_api import sync_playwright
import time

def verify_ui(page):
    page.on("console", lambda msg: print(f"CONSOLE: {msg.text}"))

    # Mock Data
    page.add_init_script("""
        window.woosuiteData = {
            apiUrl: 'http://localhost/wp-json/woosuite/v1',
            nonce: '123',
            homeUrl: 'http://localhost'
        };
    """)

    # Mock Items (Broad Pattern)
    page.route('**/*limit=20*', lambda route: route.fulfill(
        status=200,
        body='{"items": [{"id": 1, "name": "Test Item", "type": "product", "proposedDescription": "Old Prop"}], "total": 1, "pages": 1}'
    ))

    # Mock Rewrite
    page.route('**/content/rewrite', lambda route: route.fulfill(
        status=200,
        body='{"success": true, "rewritten": "New Prop"}'
    ))

    # Mock Batch Status (Empty)
    page.route('**/seo/batch-status', lambda route: route.fulfill(
        status=200,
        body='{"status": "idle"}'
    ))

    print("Loading App...")
    page.goto('http://localhost:3001')
    page.wait_for_selector('text=WooSuite AI')

    # 1. Test Collapsible Sidebar
    print("Testing Sidebar Collapse...")
    page.set_viewport_size({"width": 1280, "height": 800})

    if page.locator('text=Features').is_visible():
        print("Sidebar Expanded: 'Features' label visible.")
    else:
        print("FAIL: Sidebar not expanded initially.")

    collapse_btn = page.locator('aside button').last
    collapse_btn.click()

    page.wait_for_timeout(500)
    if not page.locator('text=Features').is_visible():
        print("PASS: Sidebar Collapsed. 'Features' label hidden.")
    else:
        print("FAIL: Sidebar did not collapse.")

    # 2. Test Content Enhancer Regenerate Spinner
    print("Testing Regenerate Spinner...")
    page.get_by_role("button", name="Content Enhancer").click()

    # Wait for table
    page.wait_for_selector('table')
    page.wait_for_timeout(1000)

    if page.locator('text=Test Item').count() == 0:
        print("FAIL: Test Item not rendered.")
        return

    regen_btn = page.locator('button:has-text("Regenerate")')
    if regen_btn.count() == 0:
        print("FAIL: Regenerate button not found.")
        return

    # Mock delayed response
    def delayed_handler(route):
        time.sleep(1) # Delay 1s
        try:
            route.fulfill(status=200, body='{"success": true, "rewritten": "Delayed"}')
        except:
            pass

    page.unroute('**/content/rewrite')
    page.route('**/content/rewrite', delayed_handler)

    regen_btn.click()

    # Check for spinner immediately
    if page.locator('.animate-spin').count() > 0:
        print("PASS: Spinner visible during regeneration.")
    else:
        print("FAIL: Spinner not found.")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        try:
            verify_ui(page)
        except Exception as e:
            print(f"Error: {e}")
            page.screenshot(path="verification/fail_ui.png")
        finally:
            browser.close()
