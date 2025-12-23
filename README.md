# WooSuite AI

WooSuite AI is a comprehensive WordPress plugin combining advanced AI SEO automation and robust Security features.

## Core Features

### AI SEO & Content Enhancer
*   **Engine:** Powered by **Groq** using the **Llama 4 Scout (17B)** model (Unified Text & Vision).
*   **Capabilities:**
    *   Automatic generation of Meta Titles, Descriptions, and Summaries.
    *   Image Alt Text generation (analyzing visual content).
    *   Bulk optimization via background workers.
    *   "Content Enhancer" module for rewriting product descriptions with technical precision.
*   **Configuration:** Requires a Groq API Key (entered in Settings).

### Security
*   **Firewall:** Blocks SQL Injection, XSS, and Path Traversal.
*   **Scanner:** Deep Scan (Regex-based) to detect malicious code (eval, shell_exec).
*   **Core Integrity:** Verifies WordPress core files against official checksums.
*   **Protection:** Login limits, Spam Honeypot, and IP Reputation banning.

## Developer Instructions

### Build Process
The dashboard is built with React and Vite.
1.  **Install:** `npm install`
2.  **Dev:** `npm run dev`
3.  **Build:** `npm run build` (Outputs to `assets/`)

### Release
Always use the build script before checking in a release:
```bash
./build_plugin.sh
```
