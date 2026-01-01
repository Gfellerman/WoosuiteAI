from playwright.sync_api import sync_playwright, expect
import time

def verify_content_enhancer():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={"width": 1280, "height": 800})
        page = context.new_page()

        page.add_init_script("""
            window.woosuiteData = {
                apiUrl: 'http://localhost:8080/wp-json/woosuite/v1',
                nonce: '12345',
                homeUrl: 'http://localhost:8080'
            };
        """)

        # Mock stats (used by Dashboard)
        page.route("**/stats", lambda route: route.fulfill(
            status=200,
            content_type="application/json",
            body='{"seo_score": 50, "threats_blocked": 10, "last_backup": "Yesterday"}'
        ))

        # Mock Products
        page.route("**/content?type=product&limit=20&page=1", lambda route: route.fulfill(
            status=200,
            content_type="application/json",
            body='''{
                "items": [
                    {
                        "id": 101,
                        "name": "Classic T-Shirt",
                        "description": "A nice t-shirt.",
                        "shortDescription": "T-Shirt",
                        "type": "product",
                        "hasHistory": false,
                        "proposedDescription": "A premium classic t-shirt made from 100% cotton."
                    }
                ],
                "total": 1,
                "pages": 1
            }'''
        ))

        page.route("**/content/categories?type=product", lambda route: route.fulfill(
             status=200,
             content_type="application/json",
             body='[{"id": 1, "name": "Clothing", "count": 10}]'
        ))

        # We serve from assets/index.html because the build script outputs there
        page.goto("http://localhost:8080/assets/index.html")

        # Wait for the app root to be populated
        # page.wait_for_selector("#woosuite-app-root > div")
        # Or wait for a known text
        page.wait_for_selector("text=WooSuite AI", timeout=30000)

        # Navigate to Content Enhancer
        page.get_by_role("button", name="Content Enhancer").click()

        # Check for our new features
        # 1. Search Bar
        expect(page.get_by_placeholder("Search products...")).to_be_visible()

        # 2. Textarea for proposal (since we mocked one with proposedDescription)
        # It takes time for the fetch to complete and React to render
        page.wait_for_selector("textarea")
        expect(page.locator("textarea").first).to_have_value("A premium classic t-shirt made from 100% cotton.")

        # Take Screenshot
        page.screenshot(path="verification/content_enhancer_ui.png")
        print("Screenshot taken: verification/content_enhancer_ui.png")

        browser.close()

if __name__ == "__main__":
    verify_content_enhancer()
