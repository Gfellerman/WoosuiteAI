# Building WooSuite AI

WooSuite AI uses a hybrid architecture with a PHP backend and a React frontend (for the dashboard). This guide explains how to build the project correctly.

## Prerequisites

*   Node.js (v18+) and npm
*   PHP (v7.4+)

## Development Workflow

1.  **Install Dependencies:**
    ```bash
    npm install
    ```

2.  **Start Development Server:**
    ```bash
    npm run dev
    ```
    *Note: Since this is embedded in WordPress, `npm run dev` is mostly for checking compilation. You typically need to build to see changes in WP Admin.*

3.  **Build for Production:**
    To generate the JavaScript and CSS files that WordPress enqueues, you **must** run:
    ```bash
    npm run build
    ```
    This command compiles the React code into `assets/woosuite-app.js` and `assets/woosuite-app.css`.

4.  **Deployment / Zipping:**
    When packaging the plugin, **ensure the `assets/` folder is included**.

    **Do NOT zip the `src` folder or `node_modules`.**

    The critical files for the plugin to work are:
    *   `woosuite-ai.php`
    *   `includes/` (all PHP files)
    *   `assets/` (the built JS/CSS)
    *   `vendor/` (if you add PHP composer dependencies later)

    **Recommended Method:** Use the provided build script:
    ```bash
    sh build_plugin.sh
    ```
    This script automatically installs dependencies, builds the React app, and creates the zip file with the correct exclusions.

## Architecture Notes

*   **PHP (`includes/`):** Handles the WordPress menu, enqueueing scripts, and the REST API.
*   **React (`src/`, `components/`):** The dashboard UI.
*   **Integration:** `includes/class-woosuite-admin.php` enqueues the React assets and passes a global `woosuiteData` object (containing the API nonce and URL) to the frontend.
