# WooSuite AI - Agent Guidelines

## 🧠 Project Context
**WooSuite AI** is an all-in-one WordPress plugin (Security, SEO, Marketing, Backup, Speed) powered by Google Gemini AI.
- **Architecture:** Hybrid. PHP backend (REST API) + React frontend (Dashboard).
- **Styling:** Tailwind CSS v4 (via PostCSS).

## ⚠️ CRITICAL RULES (Do Not Break)

### 1. Build & Release
- **Source of Truth:** The release zip MUST be created using `sh build_plugin.sh`.
- **Assets:** The `assets/` directory (containing compiled `.js` and `.css`) **MUST** be committed to the repository.
- **Cache Busting:** All `wp_enqueue_script` and `wp_enqueue_style` calls must use `filemtime( $file_path )` as the version argument to prevent browser caching issues.

### 2. Frontend (React in WP Admin)
- **Layout:** NEVER use `h-screen` or `overflow-hidden` on the main container. It breaks the WordPress Admin bar/menu.
    - ✅ USE: `min-h-screen`, `w-full`.
- **Global Data:** React receives data from WordPress via `window.woosuiteData` (contains `nonce`, `apiUrl`, `root`).
- **Data Integrity:** NEVER use hardcoded "marketing" numbers. If data is missing, display `0` or `null`.

### 3. AI & Backend Architecture
- **Server-Side AI First:** All AI generation (Text, Image, Data) must be performed on the backend (PHP `WooSuite_Gemini` class) to ensure reliability, CORS compliance, and support for batch processing. Client-side AI calls are deprecated.
- **Batch Processing:** Background workers must use **Time-Based Loops** (e.g., `microtime` check with 20s limit) rather than fixed item counts. This maximizes throughput on varying server environments.
- **Stop Capability:** All background processes must implement a "Stop Signal" check (via `get_option`) to allow users to cancel long-running operations.

### 4. Workflow
- **Update `workflow.md`:** You MUST update `workflow.md` at the start/end of every session to track progress.

## 🛠️ Tech Stack & Commands
- **Install:** `npm install`
- **Dev:** `npm run dev`
- **Build:** `npm run build` (Outputs to `assets/`)
- **Release:** `sh build_plugin.sh` (Creates `woosuite-ai.zip`)

## 📂 File Structure
- `woosuite-ai.php`: Main plugin file.
- `includes/`: PHP classes (Admin, API, Activator).
- `assets/`: Compiled output (JS/CSS).
- `components/`: React components.
- `services/`: Frontend API services.
