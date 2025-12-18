# WooSuite AI - Development Workflow

## Project Overview
WooSuite AI (Branded as **Swiss WP Secure**) is a comprehensive, all-in-one WordPress plugin designed to replace multiple single-purpose plugins. It leverages Google Gemini AI to automate and enhance Security, SEO, Marketing, Backups, and Speed Optimization.

## Current Status
*   **Architecture:** Hybrid. PHP backend (REST API) + React frontend (Dashboard).
*   **Branding:** Author "Swisswpsecure Team". Plugin internal ID remains `woosuite-ai`.
*   **Security Module:** ✅ Stable.
    *   **Features:** WAF, Deep Scan (Background), Login/Spam Protection, High Security Placeholder.
*   **SEO Module:** ✅ Stable.
    *   **Features:** Bulk Optimization (Time-based), Image SEO (Server-side), Unoptimized Filter, Product Title Simplification (Prototype).
*   **Dashboard:** Fully functional.

## Recent Changes
- [x] **SEO Batch Upgrade:** Implemented Time-Based Loop (20s) and Stop Button for background optimization.
- [x] **Image SEO:** Migrated image analysis from client-side (browser) to server-side (PHP) to fix filename fallback issues.
- [x] **Product Titles:** Added "Simplify Product Names" (Max 6 words) option to SEO Batch as a prototype.
- [x] **SEO Filter Fix:** Rewrote `get_content_items` to strictly exclude optimized items (Green status).
- [x] **Security UI:** Added "High Security Mode" (Heavy) toggle placeholder.
- [x] **Documentation:** Updated `AGENTS.md` with new architectural rules for AI and Batching.

## Todo List (Priorities)

### 1. New Module: Product Content Enhancer 🌟
*   **Concept:** A dedicated module (separate from SEO) for deep content rewriting.
*   **Features:**
    *   **Simplify Name:** Rewrite product titles to strictly 5-6 words (removing specs/clutter).
    *   **Short Description:** Rewrite to max 2 words (concise).
    *   **Description:** Enhanced, fluent description (replacing poor translations).
*   **Implementation:** Will use the Batch Worker architecture.

### 2. Order Manager
*   Implement real logic for Order Management features.

### 3. Speed Module
*   Begin implementation (Image Compression, Database Cleaner).

### 4. Marketing
*   Implement Email automation rules.

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
