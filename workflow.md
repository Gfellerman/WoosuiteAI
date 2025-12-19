# WooSuite AI - Workflow

## Completed
- [x] **Architecture**: Switched AI Engine from Google Gemini to **Groq (Llama 3)** for better stability and speed.
- [x] **SEO**: Implemented **Smart Throttling** (sleep 2s) to respect Groq Free Tier limits (30 RPM).
- [x] **SEO**: Fixed "Batch Stops" issue by handling 429 Rate Limits gracefully (Pause & Auto-resume).
- [x] **Settings**: Added native **Test Connection** button to React UI with immediate feedback.
- [x] **Cleanup**: Removed legacy Gemini code and diagnostic test pages.
- [x] **Security**: Implemented Deep Scan with regex patterns and Auto-Whitelist for trusted plugins.
- [x] **Security**: Added Quarantine UI and Logic.
- [x] **Settings**: Improved Save feedback and validation.
- [x] **SEO**: Enhanced Image SEO prompt to ignore random filenames.

## In Progress / Planned
- [ ] **Content**: Create a dedicated "Content Enhancer" module for rewriting Product Titles/Descriptions (separating it from SEO Meta generation).
- [ ] **Security**: Investigate **Llama Guard** integration for AI-powered WAF (Comment/Spam filtering).
- [ ] **Premium**: Plan for "Pro" version with higher limits or advanced models.

## Architecture Notes
- **AI Engine**: Groq (Llama 3.1 8B for Text, Llama 3.2 11B for Vision).
- **Throttling**: Worker sleeps 2s between requests to stay under 30 RPM.
- **State**: Batch process uses `_woosuite_seo_processed_at` to track progress and prevent loops.
