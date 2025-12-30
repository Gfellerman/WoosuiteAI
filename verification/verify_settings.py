from playwright.sync_api import sync_playwright, expect
import time

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Subscribe to console logs
        page.on("console", lambda msg: print(f"Browser Console: {msg.text}"))
        page.on("pageerror", lambda err: print(f"Browser Error: {err}"))

        # Use localhost server
        page.goto("http://localhost:8000/verification/mock_index.html")

        # Wait for app to mount
        try:
            page.wait_for_selector("h1", state="visible", timeout=10000) # 'Dashboard' header
            print("App mounted.")
        except:
            print("Timeout waiting for h1. Taking debug screenshot.")
            page.screenshot(path="verification/debug_mount_fail.png")
            browser.close()
            return

        # 1. Navigate to Settings
        print("Navigating to Settings...")
        page.get_by_role("button", name="Settings").click()

        # Verify we are on Settings page
        expect(page.get_by_role("heading", name="Settings", level=1)).to_be_visible()

        # 2. Check for "Bring Your Own LLM" section
        print("Checking BYO-LLM section...")

        # Locate "Use Custom API" checkbox
        checkbox = page.get_by_label("Use Custom API")

        # Take screenshot BEFORE interaction
        page.screenshot(path="verification/settings_initial.png")

        if checkbox.is_visible():
             checkbox.check()
             page.wait_for_selector("text=API Endpoint URL", state="visible")

             page.get_by_label("API Endpoint URL").fill("https://api.z.ai/v1/chat/completions")
             page.get_by_label("Model ID").fill("glm-4.7")

             page.screenshot(path="verification/settings_custom_api.png")
             print("Success! Screenshot saved to settings_custom_api.png")
        else:
             print("Could not find 'Use Custom API' checkbox.")
             page.screenshot(path="verification/debug_fail.png")

        browser.close()

if __name__ == "__main__":
    run()
