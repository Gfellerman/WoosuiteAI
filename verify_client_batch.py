
import json
from playwright.sync_api import sync_playwright, Page, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page(viewport={"width": 1280, "height": 800})

    # Debugging Console
    page.on("console", lambda msg: print(f"BROWSER CONSOLE: {msg.text}"))
    page.on("pageerror", lambda err: print(f"BROWSER ERROR: {err}"))

    # 1. Define Mocks
    api_url = "http://localhost/wp-json/woosuite/v1"

    # Generic Mock for everything else
    page.route("**/*", lambda route: route.continue_() if "assets" in route.request.url or "admin.php" in route.request.url else route.fulfill(status=200, body=json.dumps({"status": "ok"})))

    # Specific Mocks
    # Mock Initial Content Load
    def handle_content(route):
        route.fulfill(
            status=200,
            content_type="application/json",
            body=json.dumps({
                "items": [
                    {"id": 101, "name": "Test Product 1", "type": "product", "metaTitle": "", "metaDescription": "", "lastError": ""},
                    {"id": 102, "name": "Test Product 2", "type": "product", "metaTitle": "Good Title", "metaDescription": "Good Desc", "lastError": ""}
                ],
                "total": 2,
                "pages": 1
            })
        )

    # Mock IDs Fetch (Optimize All)
    def handle_ids_fetch(route):
        print(f"Intercepted IDs fetch: {route.request.url}")
        route.fulfill(
            status=200,
            content_type="application/json",
            body=json.dumps({
                "ids": [101, 102],
                "total": 2
            })
        )

    # Mock Generate (The actual work)
    def handle_generate(route):
        import time
        time.sleep(0.5)
        route.fulfill(
            status=200,
            content_type="application/json",
            body=json.dumps({
                "success": True,
                "data": {
                    "title": "Optimized Title",
                    "description": "Optimized Description"
                }
            })
        )

    def handle_stats(route):
         route.fulfill(status=200, body=json.dumps({
             "orders": 10, "seo_score": 50, "threats_blocked": 5, "ai_searches": 2, "last_backup": "Yesterday"
         }))

    # Route Handlers (Specific override generic)
    page.route(f"{api_url}/stats", handle_stats)
    page.route(f"{api_url}/content?type=product&limit=20&page=1", handle_content)
    page.route(f"{api_url}/content?type=product&filter=unoptimized&fields=ids&limit=500", handle_ids_fetch)
    page.route("**/seo/generate/*", handle_generate)

    # 2. Load Page

    dummy_html = """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>WooSuite AI</title>
        <link rel="stylesheet" href="/assets/woosuite-app.css">
    </head>
    <body class="bg-gray-100">
        <div id="wpwrap">
            <div id="woosuite-app-root"></div>
        </div>
        <script>
            window.woosuiteData = {
                root: 'http://localhost/wp-json/',
                nonce: '12345',
                apiKey: 'test-key',
                homeUrl: 'http://localhost',
                apiUrl: 'http://localhost/wp-json/woosuite/v1'
            };
        </script>
        <script type="module" src="/assets/woosuite-app.js"></script>
    </body>
    </html>
    """

    def handle_root(route):
        route.fulfill(status=200, content_type="text/html", body=dummy_html)

    page.route("http://localhost/admin.php?page=woosuite-ai", handle_root)

    # Serve Assets
    import os
    def handle_assets(route):
        url = route.request.url
        filename = url.split("/")[-1]
        filepath = f"assets/{filename}"
        if os.path.exists(filepath):
            with open(filepath, "rb") as f:
                content_type = "application/javascript" if filename.endswith(".js") else "text/css"
                route.fulfill(status=200, content_type=content_type, body=f.read())
        else:
            print(f"Asset not found: {filepath}")
            route.abort()

    page.route("**/assets/*", handle_assets)

    # Go!
    page.goto("http://localhost/admin.php?page=woosuite-ai")

    # 3. Interactions
    print("Page loaded. Waiting for selector...")

    # Wait for app to mount. If it fails, we check console logs.
    try:
        page.wait_for_selector("text=AI SEO Manager", timeout=10000)
    except:
        print("Timeout waiting for 'AI SEO Manager'. Checking for other content...")
        # Check if maybe the dashboard loaded but we are on wrong tab?
        # The default tab is Dashboard, not SEO Manager.
        # So we need to navigate to SEO Manager!

        # Ah! The previous script assumed we were on SEO Manager because I saw it in my head.
        # But the app defaults to Dashboard.

        # If the Dashboard is loaded, we see "Threats Blocked" or similar.
        if page.is_visible("text=Threats Blocked"):
            print("Dashboard loaded. Navigating to SEO Manager...")
            # Find the nav link. It might be in a sidebar.
            # Based on code reading, there's a sidebar.
            page.click("text=AI SEO (GEO)")
            page.wait_for_selector("text=AI SEO Manager")
        else:
            print("App did not load correctly.")
            # Take screenshot of failure
            page.screenshot(path="verification/failed_load.png")
            raise

    # Check that "Background Optimization Running" banner is GONE
    expect(page.locator("text=Background Optimization Running")).not_to_be_visible()

    # Click "Optimize All (Batch 500)"
    page.on("dialog", lambda dialog: dialog.accept())

    print("Clicking Optimize All...")
    page.click("button:has-text('Optimize All (Batch 500)')")

    # 4. Verify Client Modal
    print("Waiting for modal...")
    expect(page.locator("text=Optimizing Items...")).to_be_visible()
    expect(page.locator("text=Do not close or refresh this tab")).to_be_visible()

    # Take Screenshot 1: Processing
    page.screenshot(path="verification/client_batch_processing.png")
    print("Screenshot 1 taken.")

    # Wait for completion (modal disappears)
    expect(page.locator("text=Optimizing Items...")).not_to_be_visible(timeout=5000)

    # Take Screenshot 2: Done
    page.screenshot(path="verification/client_batch_done.png")
    print("Screenshot 2 taken.")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
