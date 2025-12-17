# WooSuite AI - Development Workflow

## Project Overview
WooSuite AI (Branded as **Swiss WP Secure**) is a comprehensive, all-in-one WordPress plugin designed to replace multiple single-purpose plugins. It leverages Google Gemini AI to automate and enhance Security, SEO, Marketing, Backups, and Speed Optimization.

## Current Status
*   **Architecture:** Hybrid (React Dashboard + PHP REST API).
*   **Branding:** Updated Author to "Swisswpsecure Team". Plugin internal ID remains `woosuite-ai`.
*   **Security Module:** Fully implemented (WAF, Scanner, Login Protection, Logs).
    *   **New Features:** Granular Blocking Controls (SQLi/XSS) and Simulation Mode.
    *   **Verification:** Added "Test Firewall" button to verify WAF efficacy against XSS/SQLi.
*   **SEO Module:** Fully Functional & Verified.
    *   **Scope:** Supports Products, Posts, Pages, and Images.
    *   **Features:** Bulk Optimization, Image Analysis, Custom Sitemap.
    *   **Verification:** Added "Verify Meta Tags" (via metatags.io) and "View Image" buttons to UI.
    *   **Fixes:** Resolved data persistence issues with robust error handling and logging.
*   **Dashboard:** Fully functional Security and SEO tabs.

## Recent Changes
- [x] **Security:** Added Granular Blocking Options (SQLi, XSS) and Simulation Mode to Backend and Frontend.
- [x] **Bug Fix:** Fixed "Blank Screen" by injecting `type="module"` for Vite-built assets.
- [x] **Verification:** Added `tests/test_waf_simulation.php` to verify firewall logic.
- [x] **Verification Tools:** Added user-facing tools to verify Security (Test WAF) and SEO (Inspect Live).
- [x] **Branding:** Updated `woosuite-ai.php` author details.
- [x] **SEO Persistence:** Enhanced `SeoManager.tsx` to trap and report save errors; added debug logging to `class-woosuite-api.php`.
- [x] **Bug Fixes:**
    - Moved WAF hook to `init` to prevent fatal errors.
    - Fixed Sitemap URL display.
    - Corrected Image permalink logic.
- [x] **Build:** Re-compiled frontend assets.

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
