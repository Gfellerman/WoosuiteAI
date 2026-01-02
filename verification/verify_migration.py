from playwright.sync_api import sync_playwright
import json

def verify_backup_ui():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page(viewport={'width': 1280, 'height': 800})

        # Mock API Routes with proper headers
        def json_response(route, body):
            route.fulfill(status=200, content_type='application/json', body=body)

        page.route("**/api-mock/backup/analyze", lambda route: json_response(route, '{"risk": "Low", "summary": "System is ready for migration.", "recommendations": ["Backup DB", "Check PHP", "Disable caching"]}'))
        page.route("**/api-mock/backup/export", lambda route: json_response(route, '{"success": true, "method": "php_chunked"}'))
        page.route("**/api-mock/backup/tables", lambda route: json_response(route, '{"tables": [{"name": "wp_options", "rows": 100}, {"name": "wp_posts", "rows": 5000}]}'))

        # Dynamic Chunk Mock
        def handle_chunk(route):
            post_data = route.request.post_data_json
            limit = post_data.get('limit', 1000)
            offset = post_data.get('offset', 0)
            table = post_data.get('table', '')

            # Simulate logic
            total_rows = 100 if table == 'wp_options' else 5000

            remaining = total_rows - offset
            count = min(limit, max(0, remaining))

            json_response(route, json.dumps({"success": True, "count": count}))

        page.route("**/api-mock/backup/export/chunk", handle_chunk)

        page.route("**/api-mock/backup/export/finalize", lambda route: json_response(route, '{"success": true, "result": {"url": "http://example.com/backup.sql", "size": "50 MB"}}'))
        page.route("**/api-mock/stats", lambda route: json_response(route, '{"orders":0,"seo_score":50,"threats_blocked":10}'))
        page.route("**/api-mock/security/status", lambda route: json_response(route, '{}'))
        page.route("**/api-mock/content?type=product&limit=*", lambda route: json_response(route, '{"items":[], "total":0, "pages":0}'))


        print("Navigating...")
        page.goto("http://localhost:8081/verification/mock_index.html")

        print("Clicking Sidebar 'Cloud Backups'...")
        page.locator("aside button").filter(has_text="Cloud Backups").click()

        page.wait_for_selector("h2:has-text('Backups & Migration')")

        print("Clicking 'Site Migration' Tab...")
        page.get_by_role("button", name="Site Migration").click()

        page.get_by_placeholder("livesite.com").fill("new-site.com")

        print("Running Analysis...")
        page.get_by_role("button", name="Run Compatibility Scan").click()

        print("Waiting for 'Proceed to Export'...")
        page.wait_for_selector("text=Proceed to Export", timeout=5000)

        page.get_by_text("Proceed to Export").click()

        print("Starting Export...")
        page.get_by_role("button", name="Generate SQL Dump").click()

        print("Waiting for Export completion...")
        page.wait_for_selector("text=Export Ready!", timeout=10000)

        page.screenshot(path="verification/migration_step2_complete.png")
        print("Step 2 Screenshot taken.")

        browser.close()

if __name__ == "__main__":
    verify_backup_ui()
