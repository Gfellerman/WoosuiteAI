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
    *   **Advanced:** Added "High Security Mode" (Heavy) UI placeholder/warning.
*   **SEO Module:** Fully Functional & Enhanced.
    *   **Scope:** Products, Posts, Pages, and Images.
    *   **Batching:** Upgraded background worker (Time-based loop, Stop Capability).
    *   **Image SEO:** Server-side processing (PHP) for reliability.
    *   **Title Rewrite:** Option to "Simplify Product Names" using AI.
    *   **Fixes:** Resolved "Unoptimized" filter logic in API (verified).
*   **Dashboard:** Fully functional Security and SEO tabs.

## Recent Changes
- [x] **SEO Batch Upgrade:** Implemented Time-Based Loop (20s) and Stop Button for background optimization.
- [x] **Image SEO:** Moved image analysis to PHP backend to solve client-side CORS/filename issues.
- [x] **Product Titles:** Added "Simplify Product Names" (Max 6 words) option to SEO Batch.
- [x] **SEO Filter Fix:** Rewrote `get_content_items` to strictly exclude optimized items (Green status).
- [x] **Security UI:** Added "High Security Mode" (Heavy) toggle placeholder with performance warning.

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
