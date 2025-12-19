# WooSuite AI - Workflow

## Completed
- [x] Security: Implemented Deep Scan with regex patterns.
- [x] Security: Added Auto-Whitelist for trusted plugins (WooCommerce, Site Kit).
- [x] Security: Added Quarantine UI and Logic (Files moved to uploads/woosuite-quarantine).
- [x] SEO: Implemented Gemini-based metadata generation (Text & Image).
- [x] SEO: Fixed "Fast but No Data" bug by adding robust error logging (`_woosuite_seo_last_error`).
- [x] SEO: Enhanced Image SEO prompt to ignore random filenames.
- [x] SEO: Fixed "Reset Batch" to clear failure flags for retrying.
- [x] Settings: Added native "Test Connection" button to React UI and improved Save feedback.
- [x] SEO: Added visual error reporting (Error badge in table) and alerts for single-item generation failures.
- [x] SEO: Improved Batch stability by reducing loop time to 10s and adding `set_time_limit(0)`.

## In Progress / Planned
- [ ] Security: Connect API endpoints for "Ignore" and "Quarantine" actions (currently UI only).
- [ ] Dashboard: Add "Recent Activity" log widget.

## Completed Tasks
- [x] SEO Module: Fixed silent failure loop by marking failed items and logging specific errors.
- [x] SEO Module: Implemented Image SEO filename cleaning to prevent garbage titles.
- [x] SEO Module: Added "Reset Batch" functionality to clear failure flags.
- [x] General: Added "Connection Test" diagnostic page to verify Gemini API status.
- [x] Security: Reduced false positives by whitelisting trusted plugin directories (WooCommerce, Site Kit, etc).
- [x] Security: Added Quarantine architecture (Folder creation + .htaccess).
- [x] SEO: Fixed "It does not generate any now" by adding logic to `start_seo_batch` that clears the `_woosuite_seo_failed` flags, ensuring a fresh retry.
- [x] SEO: Added anti-loop protection (try/catch) and specific error logging to the worker.
- [x] Security: Added `DOING_CRON` bypass to Firewall to prevent it from blocking the SEO worker loopback requests.
- [x] Diagnostic: Added "Connection Test" button via JS injection (fallback) and a dedicated Submenu Page to ensure visibility.
- [x] Security: Removed unstable/incomplete Quarantine/Ignore API routes to prevent 500 errors.
- [x] Settings: Fixed API Key saving issue (UI showing "Saved" when backend failed) by adding strict response validation.
- [x] SEO: Added `lastError` field to API response to enable frontend error debugging.

## Next Steps
- [ ] Monitor user feedback on the "Connection Test" result (Success/Error code).
- [ ] Verify if the Image SEO quality improves with the filename ignoring logic.
