from playwright.sync_api import sync_playwright

def verify_seo_ui(page):
    # Mock global data
    page.add_init_script("""
        window.woosuiteData = {
            apiUrl: 'http://localhost/wp-json/woosuite/v1',
            nonce: '123',
            homeUrl: 'http://localhost'
        };
    """)

    # Mock API (Idle)
    page.route('**/seo/batch-status', lambda route: route.fulfill(
        status=200,
        body='{"status": "idle", "processed": 0, "total": 0, "message": "Idle"}'
    ))
    page.route('**/content?*', lambda route: route.fulfill(
        status=200,
        body='{"items": [], "total": 0, "pages": 0}'
    ))

    page.goto('http://localhost:3001')

    print("Clicking Navigation...")
    page.get_by_role("button", name="AI SEO (GEO)").click()

    page.wait_for_selector('h2:has-text("AI SEO Manager")')

    print("Checking Start Modal...")
    page.get_by_role("button", name="Optimize All Content").click()

    # Check for Rewrite Titles checkbox
    page.wait_for_selector('div:has-text("Simplify Product Names")')
    print("Simplify Product Names option found.")

    page.screenshot(path="verification/seo_start_modal.png")
    print("Screenshot saved.")

if __name__ == "__main__":
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page(viewport={'width': 1280, 'height': 800})
        try:
            verify_seo_ui(page)
        except Exception as e:
            print(f"Error: {e}")
        finally:
            browser.close()
