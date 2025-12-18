# WooSuite AI - Development Workflow

## Project Overview
WooSuite AI (Branded as **Swiss WP Secure**) is a comprehensive, all-in-one WordPress plugin designed to replace multiple single-purpose plugins. It leverages Google Gemini AI to automate and enhance Security, SEO, Marketing, Backups, and Speed Optimization.

## Current Status
*   **Architecture:** Hybrid. PHP backend (REST API) + React frontend (Dashboard).
*   **Branding:** Author "Swisswpsecure Team". Plugin internal ID remains `woosuite-ai`.
*   **Security Module:** ✅ Enhanced.
    *   **Features:** WAF, Deep Scan (Background), Login/Spam Protection, Quarantine System, Smart Whitelisting.
*   **SEO Module:** ✅ Optimized.
    *   **Features:** Bulk Optimization (Time-based), Image SEO (Server-side + Gemini Vision), Unoptimized Filter.

## Recent Changes
- [x] **Security Scanner Upgrade:** Implemented Smart Whitelisting (skips trusted plugins like WooCommerce, Site Kit) to reduce false positives.
- [x] **Quarantine System:** Added ability to move suspicious files to a safe quarantine folder instead of deleting them immediately.
- [x] **Scan Results UI:** Added "Ignore", "Quarantine", and "Close" actions to the scan results table. Added a new tab for managing Quarantined & Ignored files.
- [x] **SEO Worker Fix:** Fixed infinite loop/freeze issue by adding anti-loop protection (fails items that return empty data) and reducing loop time to 15s.
- [x] **Image SEO Fix:** Improved Gemini prompt to strictly analyze visual content and ignore filenames, fixing the "07854fdsaKJH..." title issue.

## Todo List (Priorities)

### 1. Product Content Enhancer 🌟
*   **Concept:** A dedicated module (separate from SEO) for deep content rewriting.
*   **Features:**
    *   **Simplify Name:** Rewrite product titles to strictly 5-6 words.
    *   **Short Description:** Rewrite to max 2 words.
    *   **Description:** Enhanced, fluent description.

### 2. Order Manager
*   Implement real logic for Order Management features.

### 3. Speed Module
*   Begin implementation (Image Compression, Database Cleaner).

## Development Environment Setup
1. Run the setup script:
   ```bash
   bash setup_env.sh
   ```

## How to Build for Release
1.  Run the build script:
    ```bash
    sh build_plugin.sh
    ```
2.  Upload `woosuite-ai.zip` to WordPress.
