# AGENTS.md - WooSuite AI

## Project Overview
WooSuite AI is a comprehensive WordPress plugin combining SEO automation (Groq AI) and Security features.

## Core Integrity & Regression Policy (CRITICAL)
*   **Do No Harm:** Every change must be evaluated for its impact on the "Core" of the website (Frontend performance, Critical functionality, WP Admin stability).
*   **Isolation:** Admin features must be strictly scoped to `is_admin()` where possible. Do not leak styles or scripts to the frontend unless explicitly required (e.g., Honeypot).
*   **Performance:** Background processes (SEO Worker, Scanners) must **ALWAYS** use throttling (`sleep()`) and batch limits to ensure they never degrade the live site's performance.
*   **Regression Testing:** Before every submission, ask: "Could this change break the site for a logged-out visitor? Could it crash the server?"
*   **Safe Defaults:** Security features (Firewall) must default to "Simulation Mode" (Log Only) or be carefully tested to avoid blocking legitimate users/bots.

## Coding Standards
*   **PHP:** Follow WordPress Coding Standards. Use strict types where possible.
*   **React:** Use functional components and Hooks. State management via local state or Context API.
*   **CSS:** Tailwind CSS v4. Always use `@import "tailwindcss";` in CSS files.
*   **Build:** Use `npm run build` to generate assets in `assets/`.

## SEO Module Instructions (Groq Engine)
*   **Engine:** The plugin now uses **Groq** (Llama 3.1 8B Text, Llama 3.2 11B Vision) via `includes/class-woosuite-groq.php`.
*   **Batch Process:** The SEO Worker (`includes/class-woosuite-seo-worker.php`) runs in background batches.
*   **Rate Limiting:** Groq Free Tier has ~30 RPM. The worker MUST implementation **Smart Throttling** (`sleep(2)`) between requests.
*   **Failure Handling:**
    *   **Rate Limits (429):** Must pause the batch, NOT mark items as failed.
    *   **Errors:** Genuine errors mark items with `_woosuite_seo_failed` and log the message to `_woosuite_seo_last_error`.
    *   **Stuck Items:** The worker must cleanup items stuck in 'processing' state (older than 10 mins) to prevent indefinite hangs.
*   **Image SEO:**
    *   The prompt must STRICTLY ignore filenames if they appear random/alphanumeric.
    *   **Size Limit:** Images > 4MB must be skipped (return error) to prevent PHP memory exhaustion or API timeouts.

## Content Enhancer Module
*   **Workflow:** Non-destructive AI rewriting.
    1.  Generate proposal -> Stores in `_woosuite_proposed_{field}` post meta.
    2.  User reviews UI.
    3.  User applies -> Updates actual post content/title and deletes proposed meta.
*   **Endpoints:** `/content/rewrite` (Generation), `/content/apply` (Commit).

## Security Module Instructions
*   **Scanner:** The Deep Scan uses a whitelist (`safe_slugs`) to skip trusted plugins.
*   **Quarantine:** Suspicious files are moved to `wp-content/uploads/woosuite-quarantine/`.
*   **API:** All interactive features (Ignore, Quarantine) must have corresponding API endpoints in `includes/api/class-woosuite-api.php`.

## Verification
*   **No Live WP:** You cannot run `wp-cli` or access a live DB. Use `tests/` with mocked WP functions.
*   **Frontend:** Use `frontend_verification_instructions` to test UI with Playwright.

## Release & Submission Checklist (MANDATORY)
*   **Update Zip:** You **MUST** run `./build_plugin.sh` before every submission.
*   **Verify Zip:** Check the modification time of `woosuite-ai.zip` (`ls -l woosuite-ai.zip`). It **must** match the current time.
*   **Never Submit Stale Zip:** If `woosuite-ai.zip` is older than your latest code changes, your submission is incomplete.
