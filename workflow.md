# WooSuite AI - Development Workflow

## Project Overview
WooSuite AI (Branded as **Swiss WP Secure**) is a comprehensive, all-in-one WordPress plugin designed to replace multiple single-purpose plugins. It leverages Google Gemini AI to automate and enhance Security, SEO, Marketing, Backups, and Speed Optimization.

## Current Status
*   **Architecture:** Hybrid (React Dashboard + PHP REST API).
*   **Branding:** Author "Swisswpsecure Team". Plugin internal ID remains `woosuite-ai`.
*   **Security Module:** Fully implemented & Enhanced.
    *   **Firewall:** WAF with SQLi/XSS blocking, Simulation Mode, and IP Reputation.
    *   **Deep Scan:** Background process scanning `wp-content/plugins` and `themes` for malware patterns (`eval`, `shell_exec`, etc.).
    *   **Login Protection:** Configurable max retries (default 3) and lockout.
    *   **Spam Protection:** Honeypot field and Link Limiter for comments.
    *   **UI:** Added Deep Scan Modal, Progress bar, and granular configuration panels.
*   **SEO Module:** Fully Functional & Verified.
    *   **Scope:** Products, Posts, Pages, and Images.
    *   **Batching:** Background worker for bulk optimization.
    *   **Fixes:** Resolved "Unoptimized" filter logic in API.
*   **Dashboard:** Fully functional Security and SEO tabs.

## Recent Changes
- [x] **SEO Fix:** Fixed API logic to correctly filter unoptimized items using robust `meta_query`.
- [x] **Security Deep Scan:** Implemented `WooSuite_Security_Scanner` (Background Process) and Frontend UI with Progress/Results.
- [x] **Security Enhancements:** Added Honeypot (Spam) and Configurable Login Protection (Max Retries).
- [x] **UI Updates:** Added "Deep Scan" modal, "Auto-scan every 12h" text, and Login Configuration panel.
- [x] **Verification:** Verified Security UI with Playwright and screenshot.

## Todo List (Next Priorities)
1.  **Order Manager:** Implement real logic for Order Management features.
2.  **Speed Module:** Begin implementation (Image Compression, Database Cleaner).
3.  **Marketing:** Implement Email automation rules.

## Development Environment Setup
If you are starting a new session or configuring an agent environment:
1. Run the setup script:
   ```bash
   bash setup_env.sh
   ```

## How to Build for Release
To create a production-ready zip file:
1.  Run the build script:
    ```bash
    sh build_plugin.sh
    ```
2.  Upload `woosuite-ai.zip` to WordPress.

## Notes
- Ensure all React components use the `woosuiteData` global object for API nonces and URLs.
- Debug logs for API actions are written to the server error log (look for "WooSuite AI").
