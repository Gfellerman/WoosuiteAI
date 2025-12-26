
import json
import time
from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page(viewport={'width': 1280, 'height': 800})

    # Mock Data
    categories = [
        {'id': 119, 'name': 'Jewelry & Watches', 'count': 5},
        {'id': 120, 'name': 'Electronics', 'count': 10}
    ]

    items_all = [
        {'id': 1, 'name': 'Product A', 'description': 'Desc A', 'shortDescription': 'Short A'}
    ]

    items_filtered = [
        {'id': 2, 'name': 'Gold Watch', 'description': 'Shiny watch', 'shortDescription': 'Watch'}
    ]

    # Mock API
    def handle_route(route):
        url = route.request.url
        if 'content/categories' in url:
            route.fulfill(status=200, body=json.dumps(categories))
        elif 'content' in url and 'category=119' in url:
            print("Intercepted Correct Filter Request: " + url)
            route.fulfill(status=200, body=json.dumps({'items': items_filtered, 'pages': 1, 'total': 1}))
        elif 'content' in url:
            route.fulfill(status=200, body=json.dumps({'items': items_all, 'pages': 1, 'total': 1}))
        else:
            route.continue_()

    # Intercept API calls only, let asset calls through to localhost
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

    # Wait for any text to appear
    page.wait_for_selector("body")
    time.sleep(1)

    # Click sidebar navigation "Content Enhancer"
    # Use generic text match but try to click the first one (sidebar)
    try:
        page.get_by_text("Content Enhancer").first.click()
    except:
        print("Could not find Content Enhancer link.")
        page.screenshot(path="verification/debug_home.png")

    # Wait for Content Enhancer Header (Level 2)
    expect(page.get_by_role("heading", level=2, name="Content Enhancer")).to_be_visible()

    # Initial load should trigger fetchItems
    expect(page.get_by_text("Product A")).to_be_visible()

    # Select Category "Jewelry & Watches"
    page.select_option('select:has(option[value="119"])', '119')

    # Verify Request was sent and UI updated
    expect(page.get_by_text("Gold Watch")).to_be_visible()
    expect(page.get_by_text("Product A")).not_to_be_visible()

    print("Success: Category Filter updated list correctly.")

    # Screenshot
    page.screenshot(path="verification/verification_filter.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
