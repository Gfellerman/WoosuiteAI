# WooSuite AI - Development Workflow

## Project Overview
WooSuite AI (Branded as **Swiss WP Secure**) is a comprehensive, all-in-one WordPress plugin designed to replace multiple single-purpose plugins. It leverages Google Gemini AI to automate and enhance Security, SEO, Marketing, Backups, and Speed Optimization.

## Current Status
*   **Architecture:** Hybrid (React Dashboard + PHP REST API).
*   **Branding:** Updated Author to "Swisswpsecure Team". Plugin internal ID remains `woosuite-ai`.
*   **Security Module:** Fully implemented (WAF, Scanner, Login Protection, Logs).
*   **SEO Module:** Fully Functional & Verified.
    *   **Scope:** Supports Products, Posts, Pages, and Images.
    *   **Features:** Bulk Optimization, Image Analysis, Custom Sitemap.
    *   **Fixes:** Resolved data persistence issues with robust error handling and logging.
    *   **UI:** Updated terminology to highlight "Traditional & AI Search" optimization.
*   **Dashboard:** Fully functional Security and SEO tabs.

## Recent Changes
- [x] **Branding:** Updated `woosuite-ai.php` author details.
- [x] **SEO Persistence:** Enhanced `SeoManager.tsx` to trap and report save errors; added debug logging to `class-woosuite-api.php`.
- [x] **UI Polish:** Updated SEO Manager header to explain value proposition (Search Engines + LLMs).
- [x] **Testing:** Added `tests/` with mock verification scripts for API logic.
- [x] **Build:** Re-compiled frontend assets.

## Todo List (Next Priorities)
1.  **Security UI Connection:** Ensure the Security page in React fully syncs with the Backend WAF status.
2.  **Order Manager:** Implement real logic for Order Management features.
3.  **Speed Module:** Begin implementation (Image Compression, Database Cleaner).

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
