# WooSuite AI - Workflow

## Completed
- [x] Security: Implemented Deep Scan with regex patterns.
- [x] Security: Added Auto-Whitelist for trusted plugins (WooCommerce, Site Kit).
- [x] Security: Added Quarantine UI and Logic (Files moved to uploads/woosuite-quarantine).
- [x] SEO: Implemented Gemini-based metadata generation (Text & Image).
- [x] SEO: Fixed "Fast but No Data" bug by adding robust error logging (`_woosuite_seo_last_error`).
- [x] SEO: Enhanced Image SEO prompt to ignore random filenames.
- [x] SEO: Fixed "Reset Batch" to clear failure flags for retrying.

## In Progress / Planned
- [ ] Security: Connect API endpoints for "Ignore" and "Quarantine" actions (currently UI only).
- [ ] SEO: Improve "Bulk Optimize" speed (consider parallel requests or larger batches if reliable).
- [ ] Dashboard: Add "Recent Activity" log widget.

## Completed Tasks
- [x] SEO Module: Fixed silent failure loop by marking failed items and logging specific errors.
- [x] SEO Module: Implemented Image SEO filename cleaning to prevent garbage titles.
- [x] SEO Module: Added "Reset Batch" functionality to clear failure flags.
- [x] General: Added "Connection Test" diagnostic page to verify Gemini API status.
- [x] Security: Reduced false positives by whitelisting trusted plugin directories (WooCommerce, Site Kit, etc).
- [x] Security: Added Quarantine architecture (Folder creation + .htaccess).

## Next Steps
- [ ] Implement the "Actions" UI (Quarantine/Ignore) in the main Security Dashboard (React).
- [ ] Verify Image SEO quality with real user data.
- [ ] Add "High Security Mode" implementation.

## Completed Tasks
- [x] SEO: Fixed "It does not generate any now" by adding logic to `start_seo_batch` that clears the `_woosuite_seo_failed` flags, ensuring a fresh retry.
- [x] SEO: Added anti-loop protection (try/catch) and specific error logging to the worker.
- [x] Security: Added `DOING_CRON` bypass to Firewall to prevent it from blocking the SEO worker loopback requests.
- [x] Diagnostic: Added "Connection Test" button via JS injection (fallback) and a dedicated Submenu Page to ensure visibility.
- [x] Security: Removed unstable/incomplete Quarantine/Ignore API routes to prevent 500 errors.

## Next Steps
- [ ] Monitor user feedback on the "Connection Test" result (Success/Error code).
- [ ] Verify if the Image SEO quality improves with the filename ignoring logic.
