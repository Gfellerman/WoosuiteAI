from playwright.sync_api import sync_playwright
import time

def verify_frontend():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context()
        page = context.new_page()

        # Route API requests
        def handle_content(route):
            # Inspect the URL to see if it's requesting product list
            if "type=product" in route.request.url:
                route.fulfill(json={
                    "items": [
                        {"id": 1, "type": "product", "name": "Prod 1", "description": "Desc 1"},
                        {"id": 2, "type": "product", "name": "Prod 2", "description": "Desc 2"},
                    ],
                    "total": 2,
                    "pages": 1
                })
            else:
                route.continue_()

        def handle_status(route):
             route.fulfill(json={"status": "idle"})

        def handle_generate_success(route):
             route.fulfill(json={"success": True, "data": {"title": "Fixed", "description": "Fixed"}})

        def handle_generate_ratelimit(route):
             print("Simulating 429 Rate Limit")
             route.fulfill(status=429, body="Rate Limit")

        page.route("**/content*", handle_content)
        page.route("**/seo/batch-status", handle_status)
        page.route("**/seo/generate/1", handle_generate_success)
        page.route("**/seo/generate/2", handle_generate_ratelimit)

        # Inject Window Data
        page.add_init_script("""
            window.woosuiteData = {
                apiUrl: 'http://localhost:3000/wp-json/woosuite/v1',
                nonce: '123',
                homeUrl: 'http://localhost:3000'
            };
        """)

        page.goto("http://localhost:3000/")

        # Navigate to SEO tab
        # Based on App.tsx, the button has text "AI SEO (GEO)" or "AI SEO"
        # The script failed before finding "Prod 1", so we need to switch view first.

        page.click("button:has-text('AI SEO')")

        # Wait for table
        page.wait_for_selector("text=Prod 1")

        # Select All Checkbox (First one in thead)
        page.locator("thead input[type='checkbox']").check()

        # Click Optimize
        page.click("button:has-text('Optimize Selected')")

        # Wait for Progress Modal
        page.wait_for_selector("text=Optimizing Selected Items...")

        # Wait a bit to let it hit the rate limit logic
        time.sleep(4)

        # Take screenshot of the progress bar (it should be at 50% or stuck)
        page.screenshot(path="verification/verification_paused.png")

        browser.close()

if __name__ == "__main__":
    verify_frontend()
