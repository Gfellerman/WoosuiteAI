from playwright.sync_api import sync_playwright
import time

def verify_fixes(page):
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

    # Mock Scan
    page.route('**/seo/scan', lambda route: route.fulfill(
        status=200,
        body='''{
            "score": 75,
            "total_items": 100,
            "optimized_items": 75,
            "details": {
                "product": { "total": 50, "missing": 10 },
                "post": { "total": 10, "missing": 5 },
                "image": { "total": 40, "missing": 10 }
            }
        }'''
    ))

    # Mock Batch Status with FAILURES
    page.route('**/seo/batch-status', lambda route: route.fulfill(
        status=200,
        body='''{
            "status": "running",
            "processed": 10,
            "failed": 5,
            "total": 50,
            "message": "Processing..."
        }'''
    ))

    # Mock Items
    page.route('**/content?type=product*', lambda route: route.fulfill(
        status=200,
        body='''{
            "items": [
                {
                    "id": 1,
                    "name": "Test Product",
                    "type": "product",
                    "hasHistory": true,
                    "metaTitle": "SEO Title",
                    "metaDescription": "SEO Desc"
                }
            ],
            "total": 1,
            "pages": 1
        }'''
    ))

    print("Loading page...")
    page.goto('http://localhost:3001')

    # 1. Check Scan Button
    print("Navigating to SEO Tab...")
    page.get_by_role("button", name="AI SEO (GEO)").click()
    page.wait_for_selector('h2:has-text("AI SEO Manager")')

    print("Checking Scan Button...")
    scan_btn = page.locator('button:has-text("Scan Website")')
    if scan_btn.count() > 0:
        print("PASS: Scan Website button found.")
        scan_btn.click()
        page.wait_for_selector('h3:has-text("Website SEO Health")')
        if page.locator('text=75/100').is_visible():
            print("PASS: Scan Modal opened and showed score.")
        else:
             print("FAIL: Scan Modal content missing.")
        # Close modal
        page.locator('button:has-text("Close")').click()
    else:
        print("FAIL: Scan Website button NOT found.")

    # 2. Check Failed Count in Progress Bar
    print("Checking Progress Bar for Failures...")
    # The batch status is mocked as 'running' so banner should be visible
    # We look for "5 Failed" text
    if page.locator('text=5 Failed').is_visible():
        print("PASS: Failed count displayed in progress banner.")
    else:
        # Maybe banner not shown?
        # Banner shows if status is running/paused.
        # Check if banner exists
        if page.locator('text=Background Optimization Running').count() > 0:
             print("FAIL: Banner visible but '5 Failed' text missing.")
        else:
             print("FAIL: Background Banner not visible.")

    # 3. Check View Data (Preview)
    print("Checking View Data button...")
    view_btn = page.locator('button:has-text("View Data")')
    if view_btn.count() > 0:
        print("PASS: View Data button found.")
        view_btn.click()
        page.wait_for_selector('h3:has-text("SEO Data Preview")')
        if page.locator('text=SEO Title').is_visible():
            print("PASS: Preview Modal showing correct data.")
        else:
            print("FAIL: Preview Modal data missing.")
        # Close
        page.locator('button:has-text("Close")').last.click()
    else:
        print("FAIL: View Data button not found.")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page(viewport={'width': 1280, 'height': 800})
        try:
            verify_fixes(page)
        except Exception as e:
            print(f"Error: {e}")
            page.screenshot(path="verification/fail_fixes.png")
        finally:
            browser.close()
