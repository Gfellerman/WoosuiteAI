from playwright.sync_api import sync_playwright

def debug():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Inject Window Data
        page.add_init_script("""
            window.woosuiteData = {
                apiUrl: 'http://localhost:3000/wp-json/woosuite/v1',
                nonce: '123',
                homeUrl: 'http://localhost:3000'
            };
        """)

        page.goto("http://localhost:3000/")
        page.wait_for_timeout(3000)
        page.screenshot(path="verification/debug_fail.png")
        print(page.content())
        browser.close()

if __name__ == "__main__":
    debug()
