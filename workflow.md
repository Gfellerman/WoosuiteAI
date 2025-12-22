# WooSuite AI - Workflow

## Completed
- [x] **Architecture**: Switched AI Engine from Google Gemini to **Groq (Llama 3)** for better stability and speed.
- [x] **SEO**: Implemented **Smart Throttling** (sleep 2s) to respect Groq Free Tier limits (30 RPM).
- [x] **SEO Fix**: Implemented **Auto-Resume** for Batch Worker when hitting API Rate Limits (schedules retry +60s).
- [x] **SEO UI**: Added "Paused" state to SEO Dashboard with clear status message.
- [x] **SEO**: Fixed "Batch Stops" issue by handling 429 Rate Limits gracefully AND recovering "Stuck Items" (older than 10 mins).
- [x] **SEO**: Added 4MB size limit for Image Analysis to prevent crashes.
- [x] **Settings**: Added native **Test Connection** button to React UI with immediate feedback.
- [x] **Content Enhancer**: Fixed **Hallucinations** by injecting context (Description/Content) when rewriting Titles/Short Descriptions.
- [x] **Content Enhancer**: Enforced user constraints (Title max 5 words, Short Desc 1 word).
- [x] **UI**: Cleaned up redundant Test API buttons.
- [x] **Cleanup**: Removed legacy Gemini code and diagnostic test pages.
- [x] **Security**: Implemented Deep Scan with regex patterns and Auto-Whitelist for trusted plugins.
- [x] **Security**: Added Quarantine UI and Logic.
- [x] **Settings**: Improved Save feedback and validation.
- [x] **SEO**: Enhanced Image SEO prompt to ignore random filenames.
- [x] **Content Enhancer**: Added **Bulk Apply** and **Pagination** (up to 500 items).
- [x] **Content Enhancer**: Implemented **Category Filtering** and **Status Filtering**.
- [x] **Content Enhancer**: Fixed "Wrong Source Data" bug (separate Short/Long descriptions).
- [x] **AI**: Implemented **Technical Tone** with specific prompt instructions.
- [x] **AI**: Enforced **Strict Title Context** to prevent invalid descriptions.
- [x] **SEO**: Implemented **Product Tags** generation and saving.
- [x] **SEO**: Fixed "Changes Not Applied" by implementing **Auto-Save** for manual "Generate" actions.
- [x] **Filters**: Enhanced Category Filter to include Subcategories (`include_children`).
- [x] **UI**: Added Pagination Controls (20/50/100/500 items) to both SEO Manager and Content Enhancer.
- [x] **AI**: Boosted priority of "Extra Instructions" in prompts.
- [x] **SEO Fix**: Restricted **Batch Scope** to active tab (Products vs Images) to prevent 40k item queues and crashes.
- [x] **SEO Fix**: Implemented **Product-Context Image Optimization** (Images optimized inside Product loop) for better quality.
- [x] **SEO UI**: Added **Resume** button and "Stuck Detection" for batch processes to handle WP Cron reliability.
- [x] **Enhancement**: Implemented **Undo/Rollback** system for SEO and Content Enhancer with `save_history` backend logic.
- [x] **UI**: Restricted Content Enhancer to **Products Only** (removed Posts/Pages tabs) per user request.
- [x] **UI**: Added detailed **SEO Analysis Tooltip** visualizing Title/Meta Desc length checks and Alt Text presence.
- [x] **Persistence**: "Optimize Selected" now triggers robust background worker instead of fragile client-side loop.
- [x] **SEO**: Added **Scan Website** feature with health score and breakdown (Products/Images/Posts).
- [x] **SEO UI**: Added **Failure Tracking** to Batch Progress (counts and displays failed items instead of stalling).
- [x] **SEO UI**: Added **View Data** button to item rows, allowing users to verify stored SEO data (Title, Meta, Alt) directly.
- [x] **Stability**: Implemented **Hybrid Batch Strategy** (Client-Side Loop for < 50 items, Background Worker for >= 50) to ensure reliability for small batches.
- [x] **Fix**: Corrected **Resume Logic** to preserve existing filters (preventing "Resume" from optimizing all 3700 items instead of selected 20).
- [x] **Core Fix**: Implemented `WooSuite_Frontend` class to **Output Meta Tags** to `wp_head`, fixing verification issues.
- [x] **SEO UI**: Added **Tags Display** to "View Data" modal.
- [x] **Batch Fix**: "Resume" now triggers a synchronous batch step (Manual Trigger) to bypass stuck/disabled WP Cron.
- [x] **UI**: Implemented **Collapsible Sidebar** for Desktop.
- [x] **UI**: Added **Loading Spinner** to Content Enhancer Regenerate button.
- [x] **Stability**: Added **Client-Side Heartbeat** to SEO Manager to auto-kick stalled batches.
- [x] **Fix**: Relaxed SEO Badge logic (50 chars min) to correctly reflect AI-generated content status.
- [x] **Fix**: Removed "Simplify Product Names" checkbox from Optimize All modal.

## In Progress / Planned
- [ ] **Security**: Investigate **Llama Guard** integration for AI-powered WAF (Comment/Spam filtering).
- [ ] **Premium**: Plan for "Pro" version with higher limits or advanced models.

## Architecture Notes
- **AI Engine**: Groq (Llama 3.1 8B for Text, Llama 3.2 11B for Vision).
- **Throttling**: Worker sleeps 2s between requests to stay under 30 RPM.
- **State**: Batch process uses `_woosuite_seo_processed_at` to track progress and prevent loops.
- **History**: Content changes are stored in `_woosuite_seo_history_title`, `_desc`, `_meta` to allow rollback.
