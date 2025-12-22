# WooSuite AI - Current Status Report

## Executive Summary
**Current Phase:** Stabilization & Cleanup
**Primary Goal:** Achieve 100% reliability for AI SEO and Content Enhancer modules.
**Secondary Goal:** Prepare Security module for AI integration (Llama Guard).

The project has successfully migrated from Gemini to Groq (Llama 4 Scout), but this transition introduced data persistence issues due to the new model's output format. Immediate focus must be on fixing these core reliability issues before expanding features.

## 1. Critical Blockers (Priority: High)
These issues prevent the "100% SEO Reliability" goal.

### A. Llama 4 JSON Parsing Failure
*   **Issue:** `Llama-4-Scout-17B` is "chatty" and often wraps JSON in Markdown (` ```json ... ``` `) or adds conversational text ("Here is your JSON:").
*   **Impact:** The current parser in `class-woosuite-groq.php` (using simple `str_replace`) fails to extract the JSON, causing the API to return an error or null. **Data is generated but not saved.**
*   **Solution:** Implement robust Regex-based JSON extraction to isolate the `{...}` block regardless of surrounding text.

### B. Batch Worker Loop Instability
*   **Issue:** The background worker (`WooSuite_Seo_Worker`) stops prematurely.
*   **Analysis:** This is likely a cascade effect from the JSON failures. When the API returns an error (due to parsing), the worker might not be handling the failure state correctly, leading to a "crash" or timeout.
*   **Solution:** Enhance error handling in the worker loop to log failures without stopping the entire batch.

## 2. Cleanup & Technical Debt (Priority: Medium)
Required to align the codebase with the documentation and best practices.

*   **Folder Structure:** React source files (`App.tsx`, `components/`, etc.) are currently in the **root** directory. They should be moved to `src/` to match `BUILD_INSTRUCTIONS.md` and standard standards.
*   **Documentation Drift:**
    *   `README.md`: References "Gemini API Key" (Outdated). Needs update to "Groq API".
    *   `BUILD_INSTRUCTIONS.md`: References `src/` (doesn't exist).
*   **Dead Code:**
    *   `debug_seo_gemini.php`: Placeholder file. **Action: Delete.**
    *   `package.json`: Contains `@google/genai` (Unused). **Action: Uninstall.**

## 3. Security Module Status
*   **Current State:** Fully functional based on **Regex Patterns** (Deep Scan) and **WP Checksums** (Core Scan).
*   **Missing:** AI Integration. The code currently does **not** use `llama-guard-4-12b`.
*   **Next Step:** Once SEO is stable, integrate `llama-guard-4-12b` for advanced log analysis and threat detection.

## 4. Proposed Action Plan
**Phase 1: Cleanup (Immediate)**
1.  Move React files to `src/`.
2.  Update `README.md` and `BUILD_INSTRUCTIONS.md`.
3.  Remove `debug_seo_gemini.php` and unused npm dependencies.

**Phase 2: Core Reliability (Critical)**
1.  **Fix JSON Parsing:** Update `class-woosuite-groq.php` with Regex extraction.
2.  **Fix Batch Loop:** hardening the worker against API errors.

**Phase 3: Security Upgrade**
1.  Integrate `meta-llama/llama-guard-4-12b`.
