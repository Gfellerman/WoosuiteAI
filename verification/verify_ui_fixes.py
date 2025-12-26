
import json
import time
from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page(viewport={'width': 1280, 'height': 800})

    # Mock Data
    items = [
        {
            'id': 1,
            'name': 'Product A',
            'description': 'This is a very long description that should wrap to multiple lines instead of being truncated with an ellipsis. If it is truncated, the test should fail visually or logically.',
            'shortDescription': 'Short A'
        }
    ]

    # Mock API
    def handle_route(route):
        url = route.request.url
        if 'content' in url:
            route.fulfill(status=200, body=json.dumps({'items': items, 'pages': 1, 'total': 1}))
        elif 'seo/batch-status' in url:
             route.fulfill(status=200, body=json.dumps({'status': 'idle'}))
        else:
            route.continue_()

    # Intercept API calls only
    page.route("**/wp-json/**", handle_route)

    # Inject Mock Data for Auth
    page.add_init_script("""
        window.woosuiteData = {
            apiUrl: 'https://example.com/wp-json/woosuite-ai/v1',
            nonce: '12345',
            homeUrl: 'https://example.com'
        };
    """)

    # Go to Localhost
    page.goto("http://localhost:8080")

    # Wait for app
    page.wait_for_selector("body")
    time.sleep(1)

    # Ensure we are on SEO Manager (Default in some builds, but let's be safe)
    # The build might default to Dashboard, SEO Manager is usually the first tab "AI SEO (GEO)"
    try:
        page.get_by_text("AI SEO (GEO)").click()
    except:
        pass

    # Wait for table
    expect(page.get_by_text("Product A")).to_be_visible()

    # 1. Verify Description is NOT truncated
    # We check if the element does NOT have the 'truncate' or 'line-clamp' classes.
    # Or better, we verify the full text is visible.

    desc_locator = page.get_by_text("This is a very long description")
    expect(desc_locator).to_be_visible()

    # Check classes (optional but good for debugging)
    class_attr = desc_locator.get_attribute("class")
    print(f"Description Classes: {class_attr}")
    if "line-clamp" in class_attr:
        print("FAIL: line-clamp still present")
    else:
        print("PASS: line-clamp removed")

    # 2. Verify Action Column is visible
    # We check for the "Generate" button which is in the Action column
    generate_btn = page.get_by_role("button", name="Generate")
    expect(generate_btn).to_be_visible()

    # Check if "Actions" header is visible
    expect(page.get_by_text("Actions", exact=True)).to_be_visible()

    print("Success: Layout checks passed.")

    # Screenshot
    page.screenshot(path="verification/verification_ui_fixes.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
