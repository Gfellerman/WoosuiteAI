# WooSuite AI - Development Workflow

## Project Overview
WooSuite AI is a comprehensive, all-in-one WordPress plugin designed to replace multiple single-purpose plugins. It leverages Google Gemini AI to automate and enhance Security, SEO, Marketing, Backups, and Speed Optimization.

## Current Status
*   **Architecture:** Hybrid (React Dashboard + PHP REST API).
*   **Core Structure:** Plugin files created, React app configured with Vite & Tailwind CSS.
*   **Deployment:** Successfully built and deployed to a test WordPress environment.
*   **UI/UX:** Dashboard implemented with connectivity to real backend data.

## Completed Tasks
- [x] Initial Repository Setup.
- [x] Defined Hybrid Architecture.
- [x] Created PHP Core (Activator, Deactivator, Admin Menu, API Handler).
- [x] Set up React Environment (Vite, Tailwind, TypeScript).
- [x] Implemented initial Dashboard UI.
- [x] Verified Build Process (`npm run build`).
- [x] **Fix Layout:** Adjusted `App.tsx` to use `min-h-screen` to fit properly within WordPress Admin.
- [x] **Backend API:** Implemented endpoints for Settings, Products, and Stats.
- [x] **Real Data:** Connected Dashboard to real Order counts and SEO scores.
- [x] **Settings:** Implemented secure API Key storage in WordPress database.
- [x] **Assets:** Generated production build assets (`assets/woosuite-app.js`, `assets/woosuite-app.css`).
- [x] **Fix Deployment:** Removed `assets/` from `.gitignore` to ensure built files are committed.
- [x] **Data Integrity:** Removed fake/hardcoded numbers from Dashboard; all metrics (Threats, AI Searches, etc.) now fetch real data (or 0) from the API.
- [x] **Cache Busting:** Implemented `filemtime` versioning for scripts and styles to prevent browser caching issues.
- [x] **Build System:** Created `build_plugin.sh` for reliable one-click zip generation.

## How to Build for Release
To create a production-ready zip file that can be uploaded to WordPress:
1.  Open your terminal in the project root.
2.  Run the build script:
    ```bash
    sh build_plugin.sh
    ```
3.  Download the generated `woosuite-ai.zip` file.
4.  Upload to WordPress via **Plugins > Add New > Upload Plugin**.

## Planned Tasks & Roadmap

### Phase 1: Stability & UI Fixes (Current Focus)
- [x] **Mobile Responsiveness:** Implement functional sidebar toggle for mobile devices (Done).
- [ ] **Style Isolation:** Ensure Tailwind CSS does not bleed into global WordPress Admin styles (Ongoing).

### Phase 2: Feature Implementation
1.  **Speed & Optimization Module**
    *   **Database Cleaner:** One-click removal of post revisions, transients, and spam comments.
    *   **Image Optimizer:** Auto-compress images on upload (WebP conversion).
    *   **Script Manager:** Dequeue unused JS/CSS on specific pages.

2.  **AI Customer Support (Gemini Powered)**
    *   **Chatbot Widget:** A frontend chat widget trained on the store's products and policies.
    *   **Auto-Reply:** Draft AI responses for customer emails in the backend.

3.  **Advanced Analytics & Reporting**
    *   **Review Analysis:** AI sentiment analysis of product reviews to identify common complaints or praises.
    *   **Sales Insights:** specific insights on "Why sales dropped yesterday" using AI analysis of traffic vs. conversion.

4.  **Security Hardening**
    *   **Login Protection:** 2FA and limit login attempts.
    *   **File Monitor:** Scan for core file changes.

5.  **Marketing Automation**
    *   **Abandoned Cart Recovery:** AI-generated emails to recover lost sales.
    *   **Social Media Scheduler:** Auto-post new products to social media with AI captions.

## Notes
- Ensure all React components use the `woosuiteData` global object for API nonces and URLs.
- Maintain compatibility with the latest WordPress version.
