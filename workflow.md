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
