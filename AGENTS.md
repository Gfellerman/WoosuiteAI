# AGENTS.md - WooSuite AI

## Project Overview
WooSuite AI is a comprehensive WordPress plugin combining SEO automation (Gemini AI) and Security features.

## Coding Standards
*   **PHP:** Follow WordPress Coding Standards. Use strict types where possible.
*   **React:** Use functional components and Hooks. State management via local state or Context API.
*   **CSS:** Tailwind CSS v4. Always use `@import "tailwindcss";` in CSS files.
*   **Build:** Use `npm run build` to generate assets in `assets/`.

## SEO Module Instructions
*   **Batch Process:** The SEO Worker (`includes/class-woosuite-seo-worker.php`) runs in background batches (15s limit).
*   **Failure Handling:** Failed items MUST be marked with `_woosuite_seo_failed` to prevent loops, BUT specific error messages MUST be logged to `_woosuite_seo_last_error`.
*   **Image SEO:** The Gemini prompt (`includes/class-woosuite-gemini.php`) must STRICTLY ignore filenames if they appear random/alphanumeric.
*   **Reset:** The "Reset Batch" feature must clear the failure flags (`_woosuite_seo_failed`) from the database to allow retries.

## Security Module Instructions
*   **Scanner:** The Deep Scan uses a whitelist (`safe_slugs`) to skip trusted plugins.
*   **Quarantine:** Suspicious files are moved to `wp-content/uploads/woosuite-quarantine/`.
*   **API:** All interactive features (Ignore, Quarantine) must have corresponding API endpoints in `includes/api/class-woosuite-api.php`.

## Verification
*   **No Live WP:** You cannot run `wp-cli` or access a live DB. Use `tests/` with mocked WP functions.
*   **Frontend:** Use `frontend_verification_instructions` to test UI with Playwright.

## Release
*   Run `./build_plugin.sh` to create a deployment ZIP.
