from playwright.sync_api import sync_playwright
import time

def verify_improvements(page):
    # Capture console logs
    page.on("console", lambda msg: print(f"CONSOLE: {msg.text}"))
    page.on("pageerror", lambda err: print(f"PAGE ERROR: {err}"))

    # Mock global data
    page.add_init_script("""
        window.woosuiteData = {
            apiUrl: 'http://localhost/wp-json/woosuite/v1',
            nonce: '123',
            homeUrl: 'http://localhost'
        };
    """)

    # Mock API Responses
    page.route('**/seo/batch-status', lambda route: route.fulfill(
        status=200,
        body='{"status": "idle", "processed": 0, "total": 0, "message": "Idle"}'
    ))

    # Mock Categories
    page.route('**/content/categories*', lambda route: route.fulfill(
        status=200,
        body='[{"id": 1, "name": "Uncategorized", "count": 5}]'
    ))

    # Mock Content Items with History (to trigger Undo button)
    page.route('**/content?type=product*', lambda route: route.fulfill(
        status=200,
        body='''{
            "items": [
                {
                    "id": 1,
                    "name": "Test Product",
                    "type": "product",
                    "hasHistory": true,
                    "metaTitle": "Short Title",
                    "metaDescription": "Missing description"
                }
            ],
            "total": 1,
            "pages": 1
        }'''
    ))

    print("Loading page...")
    page.goto('http://localhost:3001')

    # Wait for App to load
    try:
        page.wait_for_selector('#woosuite-app-root', state='attached', timeout=5000)
    except:
        print("FAIL: App root not found.")
        page.screenshot(path="verification/fail_load.png")
        return

    # Navigate to SEO
    print("Navigating to SEO Tab...")
    try:
        page.get_by_role("button", name="AI SEO (GEO)").click()
        page.wait_for_selector('h2:has-text("AI SEO Manager")', timeout=5000)
    except:
         print("FAIL: Could not navigate to SEO Manager.")
         page.screenshot(path="verification/fail_nav_seo.png")
         return

    # 1. Verify Undo Button Exists (in SEO Manager)
    print("Checking for Undo Button in SEO Manager...")
    undo_btn = page.locator('button:has-text("Undo")')
    try:
        undo_btn.first.wait_for(timeout=2000) # Wait slightly
        if undo_btn.count() > 0:
            print("PASS: Undo button found in SEO Manager.")
        else:
            print("FAIL: Undo button NOT found.")
    except:
         print("FAIL: Undo button check timed out.")
         page.screenshot(path="verification/fail_undo_seo.png")

    # 2. Verify Tooltip Logic (Improvements Badge)
    print("Checking for SEO Status Tooltip...")
    badge = page.locator('span:has-text("Improvements")')
    try:
        if badge.count() > 0:
            print("PASS: 'Improvements' badge correctly displayed for short title.")
            badge.first.hover()
            if page.locator('text=Title too short or missing').is_visible():
                print("PASS: Tooltip content visible on hover.")
            else:
                print("FAIL: Tooltip content NOT visible.")
        else:
            print("FAIL: 'Improvements' badge not found.")
            page.screenshot(path="verification/fail_badge.png")
    except:
         print("FAIL: Badge check failed.")

    # 3. Verify Content Enhancer Restriction (Product Only)
    print("Navigating to Content Enhancer...")
    try:
        page.get_by_role("button", name="Content Enhancer").click()
        page.wait_for_selector('h2:has-text("Content Enhancer")', timeout=5000)
    except:
         print("FAIL: Could not navigate to Content Enhancer.")
         page.screenshot(path="verification/fail_nav_enhancer.png")
         return

    # Check Tabs
    print("Checking Tabs in Content Enhancer...")
    try:
        if page.get_by_role("button", name="Products").is_visible():
            print("PASS: Products tab found.")

        if not page.get_by_role("button", name="Posts").is_visible() and \
           not page.get_by_role("button", name="Pages").is_visible():
            print("PASS: Posts/Pages tabs correctly hidden.")
        else:
            print("FAIL: Posts/Pages tabs are still visible.")
            page.screenshot(path="verification/fail_tabs.png")
    except:
         print("FAIL: Tab check failed.")

    # Check Undo in Content Enhancer
    print("Checking Undo in Content Enhancer...")
    enhancer_undo = page.locator('button[title="Revert to previous version"]')
    try:
        enhancer_undo.first.wait_for(timeout=2000)
        if enhancer_undo.count() > 0:
            print("PASS: Undo button found in Content Enhancer.")
        else:
            print("FAIL: Undo button NOT found in Content Enhancer.")
    except:
         print("FAIL: Content Enhancer Undo check timed out.")

    page.screenshot(path="verification/seo_improvements.png")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page(viewport={'width': 1280, 'height': 800})
        try:
            verify_improvements(page)
        except Exception as e:
            print(f"Error: {e}")
        finally:
            browser.close()
