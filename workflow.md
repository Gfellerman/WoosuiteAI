# WooSuite AI - Development Workflow

## Project Overview
WooSuite AI is a comprehensive, all-in-one WordPress plugin designed to replace multiple single-purpose plugins. It leverages Google Gemini AI to automate and enhance Security, SEO, Marketing, Backups, and Speed Optimization.

## Current Status
*   **Architecture:** Hybrid (React Dashboard + PHP REST API).
*   **Security Module:** Fully implemented (WAF, Scanner, Login Protection, Logs).
*   **SEO Module:** Significantly Enhanced.
    *   **Scope:** Now supports Products, Posts, Pages, and Images.
    *   **Sitemap:** Custom XML Sitemap generator created (includes Images).
    *   **Image SEO:** AI-powered Alt Text and Title generation (using Base64 image analysis).
    *   **Bulk Optimize:** Browser-based "Daisy Chain" processing implemented to avoid server timeouts.
    *   **UI:** Tabbed interface for different content types.
*   **Dashboard:** Fully functional Security and SEO tabs.

## Recent Changes
- [x] **API Expansion:** Genericized `get_products` to `get_content_items` to support Posts, Pages, and Images.
- [x] **Sitemap:** Created `WooSuite_Sitemap` class to generate `/sitemap.xml` and inject into `robots.txt`.
- [x] **Frontend Types:** Updated `types.ts` with `ContentItem` union type.
- [x] **Gemini Service:** Added `generateImageSeo` (handling Base64 conversion) and updated `generateSeoMeta`.
- [x] **SEO UI:** Rewrote `SeoManager.tsx` to include Tabs, Bulk Optimization logic, and Sitemap Modal.
- [x] **App Integration:** Updated `App.tsx` to work with the new API structure.

## Critical Fixes Required (Next Session Priority)
1.  **Dashboard Polish:** Address any visual inconsistencies in other tabs (Orders).
2.  **Order Manager:** Implement real logic for Order Management features.
3.  **Speed Module:** Begin implementation (Image Compression, Database Cleaner).

## Completed Tasks
- [x] Initial Repository Setup.
- [x] Defined Hybrid Architecture.
- [x] Created PHP Core (Activator, Deactivator, Admin Menu, API Handler).
- [x] Set up React Environment (Vite, Tailwind, TypeScript).
- [x] Implemented initial Dashboard UI.
- [x] Verified Build Process (`npm run build`).
- [x] **Fix Layout:** Adjusted `App.tsx` to use `min-h-screen`.
- [x] **Backend API:** Implemented endpoints for Settings, Products (now Content), and Stats.
- [x] **Real Data:** Connected Dashboard to real Order counts and SEO scores.
- [x] **Settings:** Implemented secure API Key storage.
- [x] **Assets:** Generated production build assets.
- [x] **Security Module:** Implemented PHP WAF, Logs, Scan, Login Protection.
- [x] **SEO Manager:** Complete rewrite for "Whole Site" support (Posts/Pages/Images) + Bulk Tools.

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
- Maintain compatibility with the latest WordPress version.
- Sitemap is available at `/sitemap.xml` when enabled.
